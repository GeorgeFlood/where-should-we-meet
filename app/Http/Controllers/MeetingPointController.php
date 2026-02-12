<?php

namespace App\Http\Controllers;

use App\Services\FoursquareService;
use App\Services\PostcodeService;
use App\Services\TflServices;
use App\Services\VenueService;
use Illuminate\Http\Request;

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
     *  3. Search for real venues near the centroid (pubs, cafes, stations…)
     *  4. Get TFL journey times from every person to every candidate venue (concurrently)
     *  5. Rank venues by minimax fairness (lowest worst-case journey)
     *  6. Return the best match + runner-ups
     */
    public function find(Request $request)
    {
        $validated = $request->validate([
            'locations'   => 'required|array|min:2',
            'locations.*' => 'required|string',
            'type'        => 'nullable|string|in:pub,cafe,restaurant,station,any',
        ]);

        $postcodes = $validated['locations'];
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

        // 3. Search for venues near centroid — try expanding radius if too few results
        $venues = [];
        foreach ([1500, 3000, 5000] as $radius) {
            $venues = $this->venues->search(
                $centroid['lat'],
                $centroid['lng'],
                $type,
                $radius,
                limit: 8,
            );

            if (count($venues) >= 3) break;
        }

        if (empty($venues)) {
            return response()->json([
                'error' => 'No suitable venues found in the area. Try a different venue type.',
            ], 404);
        }

        // 4 & 5. Get journey times and rank by fairness
        $ranked = $this->tfl->rankVenuesByFairness($venues, $postcodes);

        if (empty($ranked)) {
            return response()->json([
                'error' => 'Could not calculate journey times. The TFL API may be temporarily unavailable.',
            ], 502);
        }

        // 6. Enrich top results with Google Places ratings & reviews
        $topResults = array_slice($ranked, 0, 3);
        $topResults = $this->foursquare->enrichVenues($topResults);

        // 7. Return best result + up to 2 alternatives
        return response()->json([
            'best'         => $topResults[0],
            'alternatives' => array_slice($topResults, 1, 2),
            'centroid'     => $centroid,
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
}
