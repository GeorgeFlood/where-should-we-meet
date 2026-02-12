<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VenueService
{
    private string $overpassUrl = 'https://overpass-api.de/api/interpreter';

    /**
     * OSM tag mappings for each venue type we support.
     */
    private array $venueTypes = [
        'pub'        => ['amenity' => 'pub'],
        'cafe'       => ['amenity' => 'cafe'],
        'restaurant' => ['amenity' => 'restaurant'],
        'station'    => ['railway' => 'station'],
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
        $query = $this->buildOverpassQuery($lat, $lng, $type, $radius);

        $response = Http::timeout(15)
            ->asForm()
            ->post($this->overpassUrl, ['data' => $query]);

        if ($response->failed()) {
            return [];
        }

        $elements = $response->json('elements') ?? [];

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

            $venues[] = [
                'name'     => $name,
                'type'     => $this->resolveType($tags),
                'lat'      => $venueLat,
                'lng'      => $venueLng,
                'address'  => $this->buildAddress($tags),
                'distance' => $this->haversine($lat, $lng, $venueLat, $venueLng),
            ];
        }

        // Sort by distance from centroid, take closest N
        usort($venues, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return array_slice($venues, 0, $limit);
    }

    /**
     * Build an Overpass QL query for the given type and area.
     */
    private function buildOverpassQuery(float $lat, float $lng, string $type, int $radius): string
    {
        $filters = [];

        if ($type === 'any') {
            foreach ($this->venueTypes as $typeFilters) {
                foreach ($typeFilters as $key => $value) {
                    $filters[] = "node[\"{$key}\"=\"{$value}\"](around:{$radius},{$lat},{$lng});";
                    $filters[] = "way[\"{$key}\"=\"{$value}\"](around:{$radius},{$lat},{$lng});";
                }
            }
        } elseif (isset($this->venueTypes[$type])) {
            foreach ($this->venueTypes[$type] as $key => $value) {
                $filters[] = "node[\"{$key}\"=\"{$value}\"](around:{$radius},{$lat},{$lng});";
                $filters[] = "way[\"{$key}\"=\"{$value}\"](around:{$radius},{$lat},{$lng});";
            }
        }

        $filterStr = implode("\n  ", $filters);

        return <<<OVERPASS
[out:json][timeout:10];
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

        return 'other';
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
