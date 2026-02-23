<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FoursquareService
{
    private ?string $apiKey;
    private string $baseUrl = 'https://api.foursquare.com/v3';

    /**
     * Occasion → Foursquare search config.
     * Category IDs follow the v3 taxonomy (parent IDs include children).
     */
    private array $occasions = [
        'casual' => [
            'categories' => '13003,13065',              // Bars (all sub-types) + Restaurants
            'sort'       => 'relevance',
            'min_price'  => null,
            'query'      => null,
        ],
        'date' => [
            'categories' => '13065,13032,13005,13024,13009',  // Restaurants, Cafes, Cocktail Bars, Wine Bars, Lounges
            'sort'       => 'rating',
            'min_price'  => 2,
            'query'      => 'romantic',
        ],
        'coffee' => [
            'categories' => '13032',                    // Cafés (includes Coffee Shops)
            'sort'       => 'relevance',
            'min_price'  => null,
            'query'      => null,
        ],
        'work' => [
            'categories' => '13032',
            'sort'       => 'relevance',
            'min_price'  => null,
            'query'      => null,
        ],
        'celebration' => [
            'categories' => null,
            'sort'       => 'relevance',
            'min_price'  => null,
            'query'      => null,
            'entertainment_queries' => ['bowling', 'cinema', 'karaoke', 'escape room', 'arcade', 'mini golf', 'comedy club'],
        ],
    ];

    public function __construct()
    {
        $this->apiKey = config('services.foursquare.key');
    }

    /**
     * Whether the Foursquare API is configured and operational.
     * Currently disabled as Foursquare has migrated to a new platform.
     */
    public function isAvailable(): bool
    {
        return false;
    }

    /**
     * Search for quality venues by occasion near a lat/lng point.
     *
     * Returns a normalised array of venues pre-enriched with ratings,
     * stats, website, and menu — so no extra enrichment calls are needed.
     */
    public function searchPlaces(float $lat, float $lng, string $occasion, int $radius = 2000, int $limit = 10): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $config = $this->occasions[$occasion] ?? $this->occasions['casual'];

        // Entertainment uses a multi-query strategy: one search per activity type
        if (!empty($config['entertainment_queries'])) {
            return $this->searchEntertainment($lat, $lng, $config['entertainment_queries'], $radius, $limit);
        }

        return $this->doFoursquareSearch($lat, $lng, $config, $radius, $limit, $occasion);
    }

    /**
     * Standard single-call Foursquare search for an occasion config.
     */
    private function doFoursquareSearch(float $lat, float $lng, array $config, int $radius, int $limit, string $occasion): array
    {
        $params = [
            'll'         => "{$lat},{$lng}",
            'radius'     => $radius,
            'limit'      => $limit,
            'sort'       => $config['sort'],
            'fields'     => 'fsq_id,name,geocodes,location,categories,rating,stats,website,menu,price',
        ];

        if (!empty($config['categories'])) {
            $params['categories'] = $config['categories'];
        }

        if ($config['min_price']) {
            $params['min_price'] = $config['min_price'];
        }

        if (!empty($config['query'])) {
            $params['query'] = $config['query'];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => $this->apiKey,
                    'Accept'        => 'application/json',
                ])
                ->get("{$this->baseUrl}/places/search", $params);

            if ($response->failed()) {
                Log::warning('Foursquare searchPlaces failed', [
                    'status'   => $response->status(),
                    'occasion' => $occasion,
                ]);

                if ($config['min_price']) {
                    unset($params['min_price']);
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'Authorization' => $this->apiKey,
                            'Accept'        => 'application/json',
                        ])
                        ->get("{$this->baseUrl}/places/search", $params);
                }

                if ($response->failed()) {
                    return [];
                }
            }

            $results = $response->json('results') ?? [];

            $venues = array_values(array_filter(array_map(
                fn($place) => $this->normaliseFoursquarePlace($place),
                $results
            )));

            return $this->filterByOccasion($venues, $occasion);
        } catch (\Exception $e) {
            Log::warning('Foursquare searchPlaces exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search for entertainment venues by running focused queries concurrently
     * ("bowling", "cinema", etc.) and merging deduplicated results.
     */
    private function searchEntertainment(float $lat, float $lng, array $queries, int $radius, int $limit): array
    {
        $fields = 'fsq_id,name,geocodes,location,categories,rating,stats,website,menu,price';

        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($queries, $lat, $lng, $radius, $fields) {
            foreach ($queries as $i => $query) {
                $pool->as("q{$i}")
                    ->timeout(10)
                    ->withHeaders([
                        'Authorization' => $this->apiKey,
                        'Accept'        => 'application/json',
                    ])
                    ->get("{$this->baseUrl}/places/search", [
                        'll'     => "{$lat},{$lng}",
                        'radius' => $radius,
                        'limit'  => 3,
                        'query'  => $query,
                        'fields' => $fields,
                    ]);
            }
        });

        $seen = [];
        $venues = [];

        foreach ($queries as $i => $query) {
            $response = $responses["q{$i}"] ?? null;
            if (!$response instanceof \Illuminate\Http\Client\Response || $response->failed()) {
                continue;
            }

            foreach ($response->json('results') ?? [] as $place) {
                $fsqId = $place['fsq_id'] ?? null;
                if (!$fsqId || isset($seen[$fsqId])) {
                    continue;
                }
                $seen[$fsqId] = true;

                $venue = $this->normaliseFoursquarePlace($place);
                if ($venue) {
                    $venue['type'] = 'entertainment';
                    $venues[] = $venue;
                }
            }
        }

        return array_slice($venues, 0, $limit);
    }

    /**
     * Apply vibe-specific filtering so occasions are meaningfully distinct.
     * Falls back progressively if filtering gets too strict.
     */
    private function filterByOccasion(array $venues, string $occasion): array
    {
        if (empty($venues)) {
            return [];
        }

        if ($occasion === 'date') {
            $strict = array_values(array_filter($venues, function ($v) {
                $isDateType = in_array($v['type'] ?? 'other', ['restaurant', 'cafe'], true);
                $rating = $v['rating'] ?? 0;
                $reviews = $v['review_count'] ?? 0;
                return $isDateType && $rating >= 4.0 && $reviews >= 40;
            }));
            if (count($strict) >= 3) {
                return $strict;
            }

            $medium = array_values(array_filter($venues, function ($v) {
                $isDateType = in_array($v['type'] ?? 'other', ['restaurant', 'cafe'], true);
                $rating = $v['rating'] ?? 0;
                return $isDateType && $rating >= 3.6;
            }));
            if (!empty($medium)) {
                return $medium;
            }

            $loose = array_values(array_filter($venues, fn($v) => in_array($v['type'] ?? 'other', ['restaurant', 'cafe'], true)));
            return !empty($loose) ? $loose : $venues;
        }

        // Celebration results come from searchEntertainment() which already
        // guarantees entertainment-only venues, so no additional filtering needed.
        if ($occasion === 'celebration') {
            return $venues;
        }

        return $venues;
    }

    /**
     * Detect activity-heavy venues for celebration (bowling, karaoke, etc).
     */
    private function looksLikeCelebrationVenue(array $venue): bool
    {
        $name = strtolower($venue['name'] ?? '');
        $categories = strtolower(implode(' ', $venue['category_names'] ?? []));
        $text = $name . ' ' . $categories;

        foreach ([
            'bowling', 'karaoke', 'nightclub', 'arcade', 'comedy', 'escape', 'mini golf',
            'live music', 'music venue', 'dance', 'billiards', 'pool hall', 'cinema', 'theatre', 'theater'
        ] as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert a raw Foursquare place into the normalised venue format
     * used throughout the app (compatible with rankVenuesByFairness).
     */
    private function normaliseFoursquarePlace(array $place): ?array
    {
        $lat = $place['geocodes']['main']['latitude'] ?? null;
        $lng = $place['geocodes']['main']['longitude'] ?? null;

        if (!$lat || !$lng) {
            return null;
        }

        $category = $place['categories'][0] ?? null;

        $venue = [
            'name'    => $place['name'],
            'type'    => $this->mapCategoryToType($category),
            'lat'     => $lat,
            'lng'     => $lng,
            'address' => $this->buildFoursquareAddress($place['location'] ?? []),
            'fsq_id'  => $place['fsq_id'] ?? null,
            'category_names' => array_values(array_filter(array_map(
                fn($c) => $c['name'] ?? null,
                $place['categories'] ?? []
            ))),
        ];

        if (isset($place['rating'])) {
            $venue['rating'] = round($place['rating'] / 2, 1);
        }

        $venue['review_count'] = $place['stats']['total_ratings'] ?? null;

        if (!empty($place['menu'])) {
            $venue['menu_url'] = $place['menu'];
        }
        if (!empty($place['website'])) {
            $venue['website'] = $place['website'];
        }
        if (isset($place['price'])) {
            $venue['price'] = $place['price'];
        }

        return $venue;
    }

    /**
     * Map a Foursquare category to our internal theme type.
     */
    private function mapCategoryToType(?array $category): string
    {
        if (!$category || empty($category['name'])) {
            return 'other';
        }

        $name = strtolower($category['name']);

        if (str_contains($name, 'pub')) return 'pub';
        if (str_contains($name, 'bar') || str_contains($name, 'lounge')) return 'pub';
        if (str_contains($name, 'caf') || str_contains($name, 'coffee')) return 'cafe';
        if (str_contains($name, 'restaurant') || str_contains($name, 'dining')
            || str_contains($name, 'bistro') || str_contains($name, 'pizz')) return 'restaurant';

        // Arts & Entertainment (10000 parent): bowling, karaoke, comedy, nightclub, etc.
        if (str_contains($name, 'bowl') || str_contains($name, 'karaoke')
            || str_contains($name, 'comedy') || str_contains($name, 'nightclub')
            || str_contains($name, 'club') || str_contains($name, 'arcade')
            || str_contains($name, 'entertainment') || str_contains($name, 'theater')
            || str_contains($name, 'theatre') || str_contains($name, 'music')
            || str_contains($name, 'cinema') || str_contains($name, 'escape')) return 'entertainment';

        return 'other';
    }

    private function buildFoursquareAddress(array $location): ?string
    {
        $parts = array_filter([
            $location['address'] ?? null,
            $location['locality'] ?? null,
            $location['postcode'] ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    /**
     * Find a Foursquare place by name near given coordinates.
     *
     * Returns fsq_id, name, rating, stats, menu, website.
     */
    public function findPlace(string $name, float $lat, float $lng): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => $this->apiKey,
                    'Accept'        => 'application/json',
                ])
                ->get("{$this->baseUrl}/places/search", [
                    'query'  => $name,
                    'll'     => "{$lat},{$lng}",
                    'radius' => 500,
                    'limit'  => 1,
                    'fields' => 'fsq_id,name,rating,stats,menu,website',
                ]);

            if ($response->failed()) {
                Log::warning('Foursquare findPlace HTTP error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'query'  => $name,
                ]);
                return null;
            }

            $results = $response->json('results') ?? [];

            return $results[0] ?? null;
        } catch (\Exception $e) {
            Log::warning('Foursquare findPlace failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get user tips (mini-reviews) for a place by its Foursquare ID.
     */
    public function getTips(string $fsqId, int $limit = 2): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => $this->apiKey,
                    'Accept'        => 'application/json',
                ])
                ->get("{$this->baseUrl}/places/{$fsqId}/tips", [
                    'limit' => $limit,
                ]);

            if ($response->failed()) {
                return [];
            }

            $tips = $response->json() ?? [];

            return array_map(fn($tip) => [
                'text'       => $this->truncateText($tip['text'] ?? '', 160),
                'created_at' => $this->formatDate($tip['created_at'] ?? ''),
            ], $tips);
        } catch (\Exception $e) {
            Log::warning('Foursquare getTips failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Enrich a list of ranked venues with Foursquare ratings & tips.
     *
     * The first venue (best) gets full tip snippets; the rest get rating only.
     */
    public function enrichVenues(array $ranked): array
    {
        if (!$this->isAvailable() || empty($ranked)) {
            return $ranked;
        }

        foreach ($ranked as $i => &$venue) {
            $place = $this->findPlace($venue['name'], $venue['lat'], $venue['lng']);

            if (!$place) {
                continue;
            }

            // Foursquare rates out of 10 — convert to a 5-star scale
            if (isset($place['rating'])) {
                $venue['rating'] = round($place['rating'] / 2, 1);
            }

            $venue['review_count'] = $place['stats']['total_ratings'] ?? null;

            // Menu URL (Foursquare returns this when available)
            if (!empty($place['menu'])) {
                $venue['menu_url'] = $place['menu'];
            }

            // Website
            if (!empty($place['website'])) {
                $venue['website'] = $place['website'];
            }

            // Fetch tip snippets only for the best result
            if ($i === 0 && isset($place['fsq_id'])) {
                $venue['reviews'] = $this->getTips($place['fsq_id'], 2);
            }
        }

        return $ranked;
    }

    /**
     * Truncate text to a max length without cutting mid-word.
     */
    private function truncateText(string $text, int $maxLength): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $maxLength);
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return rtrim($truncated, '.,!? ') . '…';
    }

    /**
     * Format an ISO date string into a relative-style label.
     */
    private function formatDate(string $isoDate): string
    {
        if (empty($isoDate)) {
            return '';
        }

        try {
            $date = new \DateTime($isoDate);
            $now = new \DateTime();
            $diff = $now->diff($date);

            if ($diff->y > 0) return $diff->y === 1 ? 'a year ago' : "{$diff->y} years ago";
            if ($diff->m > 0) return $diff->m === 1 ? 'a month ago' : "{$diff->m} months ago";
            if ($diff->d >= 7) {
                $weeks = (int) floor($diff->d / 7);
                return $weeks === 1 ? 'a week ago' : "{$weeks} weeks ago";
            }
            if ($diff->d > 0) return $diff->d === 1 ? 'yesterday' : "{$diff->d} days ago";

            return 'today';
        } catch (\Exception) {
            return '';
        }
    }
}
