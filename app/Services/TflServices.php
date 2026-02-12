<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

class TflServices
{
    private string $baseUrl = 'https://api.tfl.gov.uk';

    /**
     * Get a single journey time between two locations (postcodes or "lat,lng" strings).
     */
    public function getJourneyTime(string $from, string $to): ?int
    {
        $from = str_replace(' ', '', $from);
        $to = str_replace(' ', '', $to);

        $response = Http::timeout(30)->get("{$this->baseUrl}/Journey/JourneyResults/{$from}/to/{$to}");

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return collect($data['journeys'] ?? [])->pluck('duration')->min();
    }

    /**
     * Given a list of candidate venues (each with lat/lng) and a list of starting postcodes,
     * evaluate all journey times concurrently and return venues ranked by fairness (minimax).
     *
     * @param array $venues   Array of venue arrays, each with at least 'lat' and 'lng'
     * @param array $postcodes  Starting postcodes (strings)
     * @return array  Venues enriched with 'times', 'max', 'min', 'spread', sorted fairest-first
     */
    public function rankVenuesByFairness(array $venues, array $postcodes): array
    {
        if (empty($venues) || empty($postcodes)) {
            return [];
        }

        // Build all requests concurrently: every (venue, person) combination
        $responses = Http::pool(function (Pool $pool) use ($venues, $postcodes) {
            foreach ($venues as $vi => $venue) {
                $to = $venue['lat'] . ',' . $venue['lng'];
                foreach ($postcodes as $pi => $postcode) {
                    $from = str_replace(' ', '', $postcode);
                    $pool->as("v{$vi}_p{$pi}")
                         ->timeout(30)
                         ->get("{$this->baseUrl}/Journey/JourneyResults/{$from}/to/{$to}");
                }
            }
        });

        // Parse responses and build results
        $results = [];

        foreach ($venues as $vi => $venue) {
            $times = [];

            foreach ($postcodes as $pi => $postcode) {
                $key = "v{$vi}_p{$pi}";
                $response = $responses[$key] ?? null;

                if (!$response || $response->failed()) {
                    continue;
                }

                $data = $response->json();
                $duration = collect($data['journeys'] ?? [])->pluck('duration')->min();

                if ($duration !== null) {
                    $times[] = [
                        'from'     => $postcode,
                        'duration' => $duration,
                    ];
                }
            }

            // Only include venues where we got a time for every person
            if (count($times) !== count($postcodes)) {
                continue;
            }

            $durations = array_column($times, 'duration');

            $results[] = array_merge($venue, [
                'times'  => $times,
                'max'    => max($durations),
                'min'    => min($durations),
                'spread' => max($durations) - min($durations),
            ]);
        }

        // Sort by max time (minimax fairness), then spread as tiebreaker
        usort($results, function ($a, $b) {
            return $a['max'] <=> $b['max'] ?: $a['spread'] <=> $b['spread'];
        });

        return $results;
    }
}
