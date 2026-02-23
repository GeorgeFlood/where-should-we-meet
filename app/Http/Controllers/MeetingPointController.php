<?php

namespace App\Http\Controllers;

use App\Services\FoursquareService;
use App\Services\PostcodeService;
use App\Services\TflServices;
use App\Services\VenueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MeetingPointController extends Controller
{
    public function __construct(
        private TflServices $tfl,
        private PostcodeService $postcodes,
        private VenueService $venues,
        private FoursquareService $foursquare,
    ) {}

    /**
     * POST /api/find
     *
     * The main algorithm:
     *  1. Geocode all postcodes
     *  2. Calculate the geographic centroid
     *  3. Search for quality venues near the centroid
     *     → Foursquare-first when an occasion is provided (pre-enriched results)
     *     → OSM fallback for legacy type searches or when Foursquare is unavailable
     *  4. Get TFL journey times from every person to every candidate venue (concurrently)
     *  5. Rank venues by minimax fairness (lowest worst-case journey)
     *  6. Return the best match + runner-ups
     */
    public function find(Request $request)
    {
        set_time_limit(120);

        $validated = $request->validate([
            'locations'   => 'required|array|min:2',
            'locations.*' => 'required|string',
            'type'        => 'nullable|string|in:pub,cafe,restaurant,station,entertainment,any',
            'occasion'    => 'nullable|string|in:casual,date,coffee,work,celebration',
        ]);

        $postcodes = $validated['locations'];
        $occasion = $validated['occasion'] ?? null;
        $type = $validated['type'] ?? 'any';

        // 1. Geocode all postcodes
        $geocoded = $this->postcodes->geocode($postcodes);

        $validPoints = array_filter($geocoded);
        if (count($validPoints) < 2) {
            return response()->json([
                'error' => 'Could not geocode one or more postcodes. Please check they are valid UK postcodes.',
            ], 422);
        }

        // 2. Calculate centroid
        $centroid = $this->postcodes->centroid($validPoints);

        // 3. Search for venues via OSM
        $venues = [];
        $searchType = $occasion ? $this->occasionToType($occasion) : $type;

        foreach ([3000, 6000] as $radius) {
            $venues = $this->venues->search(
                $centroid['lat'],
                $centroid['lng'],
                $searchType,
                $radius,
                limit: 10,
            );
            if (count($venues) >= 3) break;
        }

        if (empty($venues)) {
            return response()->json([
                'error' => 'No suitable venues found in the area. Try a different occasion or search.',
            ], 404);
        }

        // 4 & 5. Get journey times and rank by fairness
        $ranked = $this->tfl->rankVenuesByFairness($venues, $postcodes);

        if (empty($ranked)) {
            return response()->json([
                'error' => 'Could not calculate journey times. The TFL API may be temporarily unavailable.',
            ], 502);
        }

        // 6. Top results
        $topResults = array_slice($ranked, 0, 3);

        // 7. Collect line names used in journeys and fetch relevant disruptions
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

        // 8. Return best result + up to 2 alternatives + alerts
        return response()->json([
            'best'         => $topResults[0],
            'alternatives' => array_slice($topResults, 1, 2),
            'centroid'     => $centroid,
            'alerts'       => $alerts,
        ]);
    }

    /**
     * Map an occasion to an OSM venue type for fallback searches.
     */
    private function occasionToType(string $occasion): string
    {
        return match ($occasion) {
            'date'           => 'restaurant',
            'coffee', 'work' => 'cafe',
            'celebration'    => 'entertainment',
            'casual'         => 'pub',
            default          => 'any',
        };
    }

    /**
     * POST /api/calculate
     *
     * Calculate journey times from multiple locations to a specific destination.
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'locations'   => 'required|array|min:2',
            'locations.*' => 'required|string',
            'destination' => 'required|string',
        ]);

        $times = [];

        foreach ($validated['locations'] as $location) {
            $duration = $this->tfl->getJourneyTime($location, $validated['destination']);
            $times[] = [
                'from'     => $location,
                'duration' => $duration,
            ];
        }

        $durations = array_filter(array_column($times, 'duration'));

        if (empty($durations)) {
            return response()->json(['error' => 'Could not calculate any journey times.'], 400);
        }

        return response()->json([
            'destination' => $validated['destination'],
            'travellers'  => $times,
            'max_time'    => max($durations),
            'min_time'    => min($durations),
            'spread'      => max($durations) - min($durations),
        ]);
    }

    /**
     * POST /api/share
     *
     * Store meeting plan data and return a shareable link.
     */
    public function share(Request $request)
    {
        $validated = $request->validate([
            'venue'   => 'required|array',
            'occasion' => 'nullable|string',
        ]);

        $id = Str::random(10);

        Cache::put("share:{$id}", [
            'venue'    => $validated['venue'],
            'occasion' => $validated['occasion'] ?? null,
            'created'  => now()->toIso8601String(),
        ], now()->addHours(48));

        return response()->json([
            'url' => url("/plan/{$id}"),
            'id'  => $id,
        ]);
    }

    /**
     * GET /plan/{id}
     *
     * Render the shareable meeting plan page.
     */
    public function viewPlan(string $id)
    {
        $data = Cache::get("share:{$id}");

        if (!$data) {
            return response()->view('plan-expired', [], 404);
        }

        return view('plan', ['plan' => $data, 'planId' => $id]);
    }

    /**
     * GET /api/plan/{id}/status
     *
     * Return the live status of all people in a shared plan.
     */
    public function getTrackerStatus(string $id)
    {
        $data = Cache::get("share:{$id}");

        if (!$data) {
            return response()->json(['error' => 'Plan not found.'], 404);
        }

        $times = $data['venue']['times'] ?? [];
        $statuses = [];

        foreach ($times as $i => $t) {
            $tracker = Cache::get("tracker:{$id}:{$i}");
            $entry = [
                'person'     => $i,
                'postcode'   => $t['from'],
                'status'     => $tracker['status'] ?? 'pending',
                'updated_at' => $tracker['updated_at'] ?? null,
            ];

            if (isset($tracker['lat'], $tracker['lng'])) {
                $entry['lat'] = $tracker['lat'];
                $entry['lng'] = $tracker['lng'];
                $entry['distance_metres'] = $tracker['distance_metres'] ?? null;
            }

            $statuses[] = $entry;
        }

        return response()->json(['statuses' => $statuses]);
    }

    /**
     * POST /api/plan/{id}/status
     *
     * Update a person's live status in a shared plan.
     */
    public function updateTrackerStatus(Request $request, string $id)
    {
        $data = Cache::get("share:{$id}");

        if (!$data) {
            return response()->json(['error' => 'Plan not found.'], 404);
        }

        $validated = $request->validate([
            'person'          => 'required|integer|min:0',
            'status'          => 'required|string|in:pending,on_my_way,arrived',
            'lat'             => 'nullable|numeric|between:-90,90',
            'lng'             => 'nullable|numeric|between:-180,180',
            'distance_metres' => 'nullable|numeric|min:0',
        ]);

        $times = $data['venue']['times'] ?? [];
        $personIndex = $validated['person'];

        if ($personIndex >= count($times)) {
            return response()->json(['error' => 'Invalid person index.'], 422);
        }

        $trackerData = [
            'status'     => $validated['status'],
            'updated_at' => now()->toIso8601String(),
        ];

        if (isset($validated['lat'], $validated['lng'])) {
            $trackerData['lat'] = $validated['lat'];
            $trackerData['lng'] = $validated['lng'];
            $trackerData['distance_metres'] = $validated['distance_metres'] ?? null;
        }

        Cache::put("tracker:{$id}:{$personIndex}", $trackerData, now()->addHours(48));

        return response()->json(['ok' => true]);
    }
}
