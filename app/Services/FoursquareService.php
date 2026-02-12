<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FoursquareService
{
    private ?string $apiKey;
    private string $baseUrl = 'https://api.foursquare.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.foursquare.key');
    }

    /**
     * Whether the Foursquare API is configured.
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
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
