<?php

namespace App\Services;

class MeetingPointService
{
    public function __construct(
        private TflServices $tfl,
        private PostcodeService $postcodes,
        private VenueService $venues,
    ) {}

    /**
     * Core search algorithm: geocode postcodes, find venues near centroid, rank by fairness.
     *
     * Returns ['venues' => [...], 'centroid' => [...], 'alerts' => [...]]
     * or ['error' => '...', 'status' => int] on failure.
     */
    public function findVenues(array $postcodeList, ?string $occasion = null, ?string $type = 'any'): array
    {
        set_time_limit(120);

        $geocoded = $this->postcodes->geocode($postcodeList);

        $validPoints = array_filter($geocoded);
        if (count($validPoints) < 2) {
            return [
                'error'  => 'Could not geocode one or more postcodes. Please check they are valid UK postcodes.',
                'status' => 422,
            ];
        }

        $centroid = $this->postcodes->centroid($validPoints);

        $searchType = $occasion ? $this->occasionToType($occasion) : ($type ?? 'any');
        $venueResults = [];

        foreach ([3000, 6000] as $radius) {
            $venueResults = $this->venues->search(
                $centroid['lat'],
                $centroid['lng'],
                $searchType,
                $radius,
                limit: 15,
            );
            if (count($venueResults) >= 5) break;
        }

        if (empty($venueResults)) {
            return [
                'error'  => 'No suitable venues found in the area. Try a different occasion or search.',
                'status' => 404,
            ];
        }

        $ranked = $this->tfl->rankVenuesByFairness($venueResults, $postcodeList);

        if (empty($ranked)) {
            return [
                'error'  => 'Could not calculate journey times. The TFL API may be temporarily unavailable.',
                'status' => 502,
            ];
        }

        $topResults = array_slice($ranked, 0, 5);

        $usedLines = [];
        foreach ($topResults as $venue) {
            foreach ($venue['times'] ?? [] as $t) {
                foreach ($t['legs'] ?? [] as $leg) {
                    if (!empty($leg['line'])) {
                        $usedLines[strtolower($leg['line'])] = $leg['line'];
                    }
                }
            }
        }

        $alerts = [];
        if (!empty($usedLines)) {
            $allDisruptions = $this->tfl->getLineDisruptions();
            foreach ($allDisruptions as $d) {
                if (isset($usedLines[strtolower($d['line'])])) {
                    $alerts[] = $d;
                }
            }
        }

        return [
            'venues'   => $topResults,
            'centroid' => $centroid,
            'alerts'   => $alerts,
        ];
    }

    public function occasionToType(string $occasion): string
    {
        return match ($occasion) {
            'date'           => 'restaurant',
            'coffee', 'work' => 'cafe',
            'celebration'    => 'entertainment',
            'casual'         => 'pub',
            default          => 'any',
        };
    }
}
