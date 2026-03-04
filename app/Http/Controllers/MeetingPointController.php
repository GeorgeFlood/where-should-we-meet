<?php

namespace App\Http\Controllers;

use App\Services\MeetingPointService;
use App\Services\TflServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MeetingPointController extends Controller
{
    public function __construct(
        private MeetingPointService $meetingPointService,
        private TflServices $tfl,
    ) {}

    /**
     * POST /api/find
     *
     * Validates input and delegates to MeetingPointService.
     */
    public function find(Request $request)
    {
        $validated = $request->validate([
            'locations'   => 'required|array|min:2',
            'locations.*' => 'required|string',
            'type'        => 'nullable|string|in:pub,cafe,restaurant,station,entertainment,any',
            'occasion'    => 'nullable|string|in:casual,date,coffee,work,celebration',
        ]);

        $result = $this->meetingPointService->findVenues(
            $validated['locations'],
            $validated['occasion'] ?? null,
            $validated['type'] ?? 'any',
        );

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        return response()->json([
            'best'         => $result['venues'][0],
            'alternatives' => array_slice($result['venues'], 1),
            'centroid'     => $result['centroid'],
            'alerts'       => $result['alerts'],
        ]);
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

    /**
     * Generate a stable cache key for a venue based on name + coordinates.
     */
    private function venueKey(string $name, float $lat, float $lng): string
    {
        return 'reviews:' . md5(strtolower(trim($name)) . ':' . round($lat, 3) . ':' . round($lng, 3));
    }

    /**
     * POST /api/review
     */
    public function submitReview(Request $request)
    {
        $validated = $request->validate([
            'venue_name' => 'required|string|max:200',
            'venue_lat'  => 'required|numeric|between:-90,90',
            'venue_lng'  => 'required|numeric|between:-180,180',
            'rating'     => 'required|string|in:positive,negative',
            'plan_id'    => 'nullable|string|max:20',
        ]);

        $key = $this->venueKey($validated['venue_name'], $validated['venue_lat'], $validated['venue_lng']);
        $reviews = Cache::get($key, []);

        if ($validated['plan_id']) {
            foreach ($reviews as $r) {
                if (($r['plan_id'] ?? null) === $validated['plan_id']) {
                    return response()->json(['ok' => true, 'duplicate' => true]);
                }
            }
        }

        $reviews[] = [
            'rating'     => $validated['rating'],
            'plan_id'    => $validated['plan_id'] ?? null,
            'created_at' => now()->toIso8601String(),
        ];

        Cache::put($key, $reviews, now()->addDays(365));

        return response()->json(['ok' => true]);
    }

    /**
     * GET /api/venue-reviews
     */
    public function getVenueReviews(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'lat'  => 'required|numeric',
            'lng'  => 'required|numeric',
        ]);

        $key = $this->venueKey($validated['name'], (float) $validated['lat'], (float) $validated['lng']);
        $reviews = Cache::get($key, []);

        if (empty($reviews)) {
            return response()->json(['has_reviews' => false]);
        }

        $total = count($reviews);
        $positive = count(array_filter($reviews, fn($r) => $r['rating'] === 'positive'));
        $pct = round($positive / $total * 100);

        $label = match (true) {
            $pct >= 95 && $total >= 10 => 'Overwhelmingly Positive',
            $pct >= 80 && $total >= 5  => 'Very Positive',
            $pct >= 80                 => 'Positive',
            $pct >= 70                 => 'Mostly Positive',
            $pct >= 40                 => 'Mixed',
            $pct >= 20                 => 'Mostly Negative',
            $pct < 20 && $total >= 5   => 'Very Negative',
            $pct < 20 && $total >= 10  => 'Overwhelmingly Negative',
            default                    => 'Negative',
        };

        $color = match (true) {
            $pct >= 70 => '#22c55e',
            $pct >= 40 => '#f59e0b',
            default    => '#ef4444',
        };

        return response()->json([
            'has_reviews' => true,
            'label'       => $label,
            'color'       => $color,
            'percentage'  => $pct,
            'total'       => $total,
            'positive'    => $positive,
        ]);
    }
}
