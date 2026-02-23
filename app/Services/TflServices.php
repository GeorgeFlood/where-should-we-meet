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
                $journeys = collect($data['journeys'] ?? []);
                $best = $journeys->sortBy('duration')->first();

                if (!$best || !isset($best['duration'])) {
                    continue;
                }

                $entry = [
                    'from'     => $postcode,
                    'duration' => $best['duration'],
                    'legs'     => $this->extractLegs($best),
                ];

                $fare = $this->extractFare($best);
                if ($fare !== null) {
                    $entry['fare'] = $fare;
                }

                $disruptions = $this->extractDisruptions($best);
                if (!empty($disruptions)) {
                    $entry['disruptions'] = $disruptions;
                }

                $times[] = $entry;
            }

            // Only include venues where we got a time for every person
            if (count($times) !== count($postcodes)) {
                continue;
            }

            $durations = array_column($times, 'duration');

            $fares = array_filter(array_map(fn($t) => $t['fare']['total_pence'] ?? null, $times));
            $fareData = [];
            if (count($fares) === count($times)) {
                $totalFare = array_sum($fares);
                $fairShare = (int) round($totalFare / count($fares));
                $fareData = [
                    'total_fare_pence' => $totalFare,
                    'fair_share_pence' => $fairShare,
                ];
            }

            $results[] = array_merge($venue, $fareData, [
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

    /**
     * Extract fare info (in pence) from a TfL journey.
     * Returns cost in pence, or null if no fare data.
     */
    private function extractFare(array $journey): ?array
    {
        $fare = $journey['fare'] ?? null;
        if (!$fare || !isset($fare['totalCost'])) {
            return null;
        }

        $fares = $fare['fares'][0] ?? [];

        return [
            'total_pence' => (int) $fare['totalCost'],
            'peak_pence'  => isset($fares['peak']) ? (int) $fares['peak'] : null,
            'off_peak_pence' => isset($fares['offPeak']) ? (int) $fares['offPeak'] : null,
            'zone_low'    => $fares['lowZone'] ?? null,
            'zone_high'   => $fares['highZone'] ?? null,
            'charge_level' => $fares['chargeLevel'] ?? null,
        ];
    }

    /**
     * Extract a compact list of legs from a TfL journey.
     */
    private function extractLegs(array $journey): array
    {
        $legs = [];

        foreach ($journey['legs'] ?? [] as $leg) {
            $routeOptions = $leg['routeOptions'] ?? [];
            $lineName = $routeOptions[0]['name'] ?? ($routeOptions[0]['lineIdentifier']['name'] ?? null);

            $legs[] = [
                'summary'  => $leg['instruction']['summary'] ?? '',
                'mode'     => $leg['mode']['name'] ?? 'Walk',
                'line'     => $lineName,
                'duration' => $leg['duration'] ?? 0,
            ];
        }

        return $legs;
    }

    /**
     * Extract unique disruption descriptions from all legs of a journey.
     */
    private function extractDisruptions(array $journey): array
    {
        $seen = [];
        $disruptions = [];

        foreach ($journey['legs'] ?? [] as $leg) {
            foreach ($leg['disruptions'] ?? [] as $d) {
                $desc = $d['description'] ?? '';
                if ($desc && !isset($seen[$desc])) {
                    $seen[$desc] = true;
                    $disruptions[] = [
                        'description' => $desc,
                        'category'    => $d['category'] ?? 'Information',
                    ];
                }
            }
        }

        return $disruptions;
    }

    /**
     * Fetch current TfL line disruptions (non-"Good Service" statuses).
     *
     * Returns an array of [ line, status, reason ] for disrupted lines.
     */
    public function getLineDisruptions(): array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/line/mode/tube,overground,dlr,elizabeth-line/status");

            if ($response->failed()) {
                return [];
            }

            $disruptions = [];

            foreach ($response->json() ?? [] as $line) {
                foreach ($line['lineStatuses'] ?? [] as $status) {
                    if (($status['statusSeverityDescription'] ?? '') !== 'Good Service') {
                        $disruptions[] = [
                            'line'   => $line['name'] ?? '',
                            'status' => $status['statusSeverityDescription'] ?? '',
                            'reason' => $status['reason'] ?? null,
                        ];
                    }
                }
            }

            return $disruptions;
        } catch (\Exception $e) {
            return [];
        }
    }
}
