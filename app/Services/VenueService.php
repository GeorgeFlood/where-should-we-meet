<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VenueService
{
    private array $overpassUrls = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
        'https://lz4.overpass-api.de/api/interpreter',
    ];

    /**
     * OSM tag mappings for each venue type we support.
     * Each type maps to one or more key=value OSM tags.
     */
    private array $venueTypes = [
        'pub'        => [['amenity' => 'pub']],
        'cafe'       => [['amenity' => 'cafe']],
        'restaurant' => [['amenity' => 'restaurant'], ['amenity' => 'cafe']],
        'station'    => [['railway' => 'station']],
        'entertainment' => [
            ['leisure' => 'bowling_alley'],
            ['amenity' => 'cinema'],
            ['amenity' => 'theatre'],
            ['amenity' => 'nightclub'],
            ['leisure' => 'escape_game'],
            ['leisure' => 'amusement_arcade'],
            ['leisure' => 'miniature_golf'],
            ['amenity' => 'events_venue'],
        ],
    ];

    /**
     * Search for venues of a given type near a lat/lng point.
     *
     * @param float  $lat    Centre latitude
     * @param float  $lng    Centre longitude
     * @param string $type   One of: pub, cafe, restaurant, station, any
     * @param int    $radius Search radius in metres
     * @param int    $limit  Max venues to return
     */
    public function search(float $lat, float $lng, string $type = 'any', int $radius = 1500, int $limit = 10): array
    {
        // Round coords to ~110m precision to improve cache hits for nearby queries
        $roundedLat = round($lat, 3);
        $roundedLng = round($lng, 3);
        $cacheKey = "venues:{$roundedLat}:{$roundedLng}:{$type}:{$radius}";

        $elements = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($lat, $lng, $type, $radius) {
            $query = $this->buildOverpassQuery($lat, $lng, $type, $radius);
            return $this->queryOverpass($query);
        });

        // Normalise results and compute distance from centroid
        $venues = [];
        foreach ($elements as $el) {
            $venueLat = $el['lat'] ?? ($el['center']['lat'] ?? null);
            $venueLng = $el['lon'] ?? ($el['center']['lon'] ?? null);

            if (!$venueLat || !$venueLng) continue;

            $tags = $el['tags'] ?? [];
            $name = $tags['name'] ?? null;

            // Skip unnamed venues — they're not useful as meeting points
            if (!$name) continue;

            $venue = [
                'name'     => $name,
                'type'     => $this->resolveType($tags),
                'lat'      => $venueLat,
                'lng'      => $venueLng,
                'address'  => $this->buildAddress($tags),
                'distance' => $this->haversine($lat, $lng, $venueLat, $venueLng),
            ];

            if ($venue['type'] === 'entertainment') {
                $venue['subcategory'] = $this->entertainmentSubcategory($tags);
            }

            if (!empty($tags['website']) || !empty($tags['contact:website'])) {
                $venue['website'] = $tags['website'] ?? $tags['contact:website'];
            }

            if (!empty($tags['phone']) || !empty($tags['contact:phone'])) {
                $venue['phone'] = $tags['phone'] ?? $tags['contact:phone'];
            }

            if (!empty($tags['cuisine'])) {
                $venue['cuisine'] = ucwords(str_replace([';', '_'], [', ', ' '], $tags['cuisine']));
            }

            if (!empty($tags['opening_hours'])) {
                $venue['opening_hours'] = $tags['opening_hours'];
            }

            $venue['quality_score'] = $this->qualityScore($tags);

            $venues[] = $venue;
        }

        if (in_array($type, ['restaurant', 'cafe'])) {
            usort($venues, function ($a, $b) {
                $qDiff = ($b['quality_score'] ?? 0) <=> ($a['quality_score'] ?? 0);
                if ($qDiff !== 0) return $qDiff;
                return $a['distance'] <=> $b['distance'];
            });
        } else {
            usort($venues, fn($a, $b) => $a['distance'] <=> $b['distance']);
        }

        return array_slice($venues, 0, $limit);
    }

    /**
     * Build an Overpass QL query for the given type and area.
     */
    /**
     * Execute an Overpass query, trying fallback servers on failure.
     */
    private function queryOverpass(string $query): array
    {
        foreach ($this->overpassUrls as $url) {
            try {
                $response = Http::timeout(15)
                    ->asForm()
                    ->post($url, ['data' => $query]);

                if ($response->failed()) {
                    Log::info('Overpass request failed', ['url' => $url, 'status' => $response->status()]);
                    continue;
                }

                $elements = $response->json('elements');
                if ($elements === null) {
                    Log::info('Overpass returned non-JSON response', ['url' => $url, 'body_start' => substr($response->body(), 0, 200)]);
                    continue;
                }

                return $elements;
            } catch (\Exception $e) {
                Log::info('Overpass exception', ['url' => $url, 'error' => $e->getMessage()]);
                continue;
            }
        }

        return [];
    }

    private function buildOverpassQuery(float $lat, float $lng, string $type, int $radius): string
    {
        $filters = [];

        $tagSets = [];
        if ($type === 'any') {
            foreach ($this->venueTypes as $typeName => $tags) {
                if ($typeName === 'entertainment') continue; // Skip entertainment in "any" searches
                foreach ($tags as $tag) {
                    $tagSets[] = $tag;
                }
            }
        } elseif (isset($this->venueTypes[$type])) {
            $tagSets = $this->venueTypes[$type];
        }

        foreach ($tagSets as $tag) {
            foreach ($tag as $key => $value) {
                $filters[] = "node[\"{$key}\"=\"{$value}\"](around:{$radius},{$lat},{$lng});";
                $filters[] = "way[\"{$key}\"=\"{$value}\"](around:{$radius},{$lat},{$lng});";
            }
        }

        $filterStr = implode("\n  ", $filters);
        $timeout = count($filters) > 10 ? 20 : 10;

        return <<<OVERPASS
[out:json][timeout:{$timeout}];
(
  {$filterStr}
);
out center body qt;
OVERPASS;
    }

    /**
     * Determine the venue type from OSM tags.
     */
    private function resolveType(array $tags): string
    {
        if (($tags['amenity'] ?? '') === 'pub') return 'pub';
        if (($tags['amenity'] ?? '') === 'cafe') return 'cafe';
        if (($tags['amenity'] ?? '') === 'restaurant') return 'restaurant';
        if (isset($tags['railway'])) return 'station';

        $entertainmentAmenities = ['cinema', 'theatre', 'nightclub', 'events_venue'];
        $entertainmentLeisure = ['bowling_alley', 'escape_game', 'amusement_arcade', 'miniature_golf'];

        if (in_array($tags['amenity'] ?? '', $entertainmentAmenities, true)
            || in_array($tags['leisure'] ?? '', $entertainmentLeisure, true)) {
            return 'entertainment';
        }

        return 'other';
    }

    /**
     * Score venue quality based on OSM metadata completeness.
     * Venues with websites, phone numbers, cuisines, and opening hours
     * are more likely to be established, quality places.
     */
    private function qualityScore(array $tags): int
    {
        $score = 0;
        if (!empty($tags['website']) || !empty($tags['contact:website'])) $score += 3;
        if (!empty($tags['phone']) || !empty($tags['contact:phone'])) $score += 2;
        if (!empty($tags['cuisine'])) $score += 2;
        if (!empty($tags['opening_hours'])) $score += 1;
        if (!empty($tags['brand'])) $score += 1;
        if (!empty($tags['addr:street'])) $score += 1;
        return $score;
    }

    /**
     * Map entertainment OSM tags to a human-readable subcategory label.
     */
    private function entertainmentSubcategory(array $tags): string
    {
        $amenity = $tags['amenity'] ?? '';
        $leisure = $tags['leisure'] ?? '';

        return match (true) {
            $leisure === 'bowling_alley'     => 'Bowling',
            $amenity === 'cinema'            => 'Cinema',
            $amenity === 'theatre'           => 'Theatre',
            $amenity === 'nightclub'         => 'Nightclub',
            $leisure === 'escape_game'       => 'Escape Room',
            $leisure === 'amusement_arcade'  => 'Arcade',
            $leisure === 'miniature_golf'    => 'Mini Golf',
            $amenity === 'events_venue'      => 'Events Venue',
            default                          => 'Entertainment',
        };
    }

    /**
     * Build a rough address string from OSM tags.
     */
    private function buildAddress(array $tags): ?string
    {
        $parts = array_filter([
            $tags['addr:housenumber'] ?? null,
            $tags['addr:street'] ?? null,
            $tags['addr:city'] ?? null,
            $tags['addr:postcode'] ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    /**
     * Haversine distance in metres between two lat/lng points.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000; // Earth radius in metres
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
