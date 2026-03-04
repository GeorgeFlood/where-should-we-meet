<?php

namespace App\Http\Controllers;

use App\Services\MeetingPointService;
use App\Services\PostcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    public function __construct(
        private MeetingPointService $meetingPoint,
        private PostcodeService $postcodes,
    ) {}

    /**
     * POST /api/session — Create a new live session.
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'occasion'          => 'nullable|string|in:casual,date,coffee,work,celebration',
            'location.lat'      => 'nullable|numeric|between:-90,90',
            'location.lng'      => 'nullable|numeric|between:-180,180',
            'location.postcode' => 'nullable|string|max:10',
        ]);

        $id = Str::random(8);
        $token = Str::random(16);
        $occasion = $validated['occasion'] ?? 'casual';
        $location = $validated['location'] ?? null;

        $participant = $this->buildParticipant($token, $location);

        $session = [
            'status'           => 'waiting',
            'occasion'         => $occasion,
            'participants'     => $participant ? [$participant] : [],
            'manual_postcodes' => [],
            'venues'           => [],
            'votes'            => [],
            'confirmed_venue'  => null,
            'plan_id'          => null,
            'alerts'           => [],
            'created_at'       => now()->toIso8601String(),
        ];

        Cache::put("session:{$id}", $session, now()->addHours(4));

        return response()->json([
            'id'         => $id,
            'token'      => $token,
            'invite_url' => url("/s/{$id}"),
            'session'    => $this->publicSession($session, $token),
        ]);
    }

    /**
     * GET /api/session/{id} — Poll current session state.
     */
    public function show(string $id, Request $request)
    {
        $session = Cache::get("session:{$id}");

        if (!$session) {
            return response()->json(['error' => 'Session not found.'], 404);
        }

        $token = $request->header('X-Session-Token');

        return response()->json([
            'session' => $this->publicSession($session, $token),
        ]);
    }

    /**
     * POST /api/session/{id}/join — Join a session with location or postcode.
     */
    public function join(Request $request, string $id)
    {
        $validated = $request->validate([
            'lat'      => 'nullable|numeric|between:-90,90',
            'lng'      => 'nullable|numeric|between:-180,180',
            'postcode' => 'nullable|string|max:10',
        ]);

        $session = Cache::get("session:{$id}");
        if (!$session) {
            return response()->json(['error' => 'Session not found.'], 404);
        }

        if ($session['status'] === 'confirmed') {
            return response()->json(['error' => 'Session already confirmed.'], 409);
        }

        $token = Str::random(16);
        $participant = $this->buildParticipant($token, $validated);

        if (!$participant) {
            return response()->json(['error' => 'Provide either lat+lng or a postcode.'], 422);
        }

        $session['participants'][] = $participant;

        $this->maybeSearch($id, $session);

        Cache::put("session:{$id}", $session, now()->addHours(4));

        return response()->json([
            'token'   => $token,
            'session' => $this->publicSession($session, $token),
        ]);
    }

    /**
     * POST /api/session/{id}/postcode — Add a manual postcode for an absent friend.
     */
    public function addPostcode(Request $request, string $id)
    {
        $validated = $request->validate([
            'postcode' => 'required|string|max:10',
        ]);

        $session = Cache::get("session:{$id}");
        if (!$session) {
            return response()->json(['error' => 'Session not found.'], 404);
        }

        if ($session['status'] === 'confirmed') {
            return response()->json(['error' => 'Session already confirmed.'], 409);
        }

        $session['manual_postcodes'][] = $validated['postcode'];

        $this->maybeSearch($id, $session);

        Cache::put("session:{$id}", $session, now()->addHours(4));

        return response()->json([
            'session' => $this->publicSession($session),
        ]);
    }

    /**
     * POST /api/session/{id}/occasion — Change the occasion and re-search venues.
     */
    public function changeOccasion(Request $request, string $id)
    {
        $validated = $request->validate([
            'occasion' => 'required|string|in:casual,date,coffee,work,celebration',
        ]);

        $session = Cache::get("session:{$id}");
        if (!$session) {
            return response()->json(['error' => 'Session not found.'], 404);
        }

        $session['occasion'] = $validated['occasion'];
        $session['status'] = 'waiting';
        $session['venues'] = [];
        $session['votes'] = [];
        $session['confirmed_venue'] = null;
        $session['plan_id'] = null;

        $this->maybeSearch($id, $session);

        Cache::put("session:{$id}", $session, now()->addHours(4));

        return response()->json([
            'session' => $this->publicSession($session),
        ]);
    }

    /**
     * POST /api/session/{id}/vote — Vote for a venue.
     */
    public function vote(Request $request, string $id)
    {
        $validated = $request->validate([
            'token'       => 'required|string',
            'venue_index' => 'required|integer|min:0',
        ]);

        $session = Cache::get("session:{$id}");
        if (!$session) {
            return response()->json(['error' => 'Session not found.'], 404);
        }

        if ($session['status'] !== 'results') {
            return response()->json(['error' => 'Voting is not open.'], 409);
        }

        if ($validated['venue_index'] >= count($session['venues'])) {
            return response()->json(['error' => 'Invalid venue index.'], 422);
        }

        $token = $validated['token'];
        $isParticipant = collect($session['participants'])->contains('token', $token);
        if (!$isParticipant) {
            return response()->json(['error' => 'Not a session participant.'], 403);
        }

        $session['votes'][$token] = $validated['venue_index'];

        $this->checkMajority($id, $session);

        Cache::put("session:{$id}", $session, now()->addHours(4));

        return response()->json([
            'session' => $this->publicSession($session, $token),
        ]);
    }

    /**
     * Build a participant entry from location data.
     * Reverse-geocodes lat/lng→postcode, or forward-geocodes postcode→lat/lng as needed.
     */
    private function buildParticipant(string $token, ?array $location): ?array
    {
        if (!$location) return null;

        $lat = $location['lat'] ?? null;
        $lng = $location['lng'] ?? null;
        $postcode = $location['postcode'] ?? null;

        if (!$postcode && $lat && $lng) {
            $postcode = $this->reverseGeocode($lat, $lng);
        }

        if ($postcode && (!$lat || !$lng)) {
            $geo = $this->postcodes->geocode([$postcode]);
            if (!empty($geo[0])) {
                $lat = $geo[0]['lat'];
                $lng = $geo[0]['lng'];
                $postcode = $geo[0]['postcode'];
            }
        }

        if (!$postcode) return null;

        return [
            'token'     => $token,
            'postcode'  => $postcode,
            'lat'       => $lat,
            'lng'       => $lng,
            'joined_at' => now()->toIso8601String(),
        ];
    }

    private function reverseGeocode(float $lat, float $lng): ?string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->get("https://api.postcodes.io/postcodes", [
                    'lon'   => $lng,
                    'lat'   => $lat,
                    'limit' => 1,
                ]);

            return $response->json('result.0.postcode');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * If we have 2+ locations and haven't searched yet (or need to re-search), trigger venue search.
     */
    private function maybeSearch(string $id, array &$session): void
    {
        $allPostcodes = array_merge(
            array_column($session['participants'], 'postcode'),
            $session['manual_postcodes'],
        );

        $allPostcodes = array_values(array_unique(array_filter($allPostcodes)));

        if (count($allPostcodes) < 2) return;

        if ($session['status'] === 'confirmed') return;

        $session['status'] = 'searching';
        Cache::put("session:{$id}", $session, now()->addHours(4));

        $result = $this->meetingPoint->findVenues(
            $allPostcodes,
            $session['occasion'],
        );

        if (isset($result['error'])) {
            $session['status'] = 'waiting';
            return;
        }

        $session['venues'] = $result['venues'];
        $session['alerts'] = $result['alerts'] ?? [];
        $session['centroid'] = $result['centroid'] ?? null;
        $session['status'] = 'results';
        $session['votes'] = [];
    }

    /**
     * Check if any venue has majority votes. If so, confirm it and auto-create a plan.
     */
    private function checkMajority(string $id, array &$session): void
    {
        $totalVoters = count($session['participants']);
        if ($totalVoters < 2) return;

        $voteCounts = array_count_values($session['votes']);
        arsort($voteCounts);

        foreach ($voteCounts as $venueIndex => $count) {
            if ($count > $totalVoters / 2) {
                $session['confirmed_venue'] = $session['venues'][$venueIndex] ?? null;
                $session['status'] = 'confirmed';

                if ($session['confirmed_venue']) {
                    $planId = Str::random(10);
                    Cache::put("share:{$planId}", [
                        'venue'    => $session['confirmed_venue'],
                        'occasion' => $session['occasion'],
                        'created'  => now()->toIso8601String(),
                    ], now()->addHours(48));
                    $session['plan_id'] = $planId;
                }

                break;
            }
        }
    }

    /**
     * Strip tokens and build the public-facing session state.
     * Postcodes are masked to outward code only for privacy.
     */
    private function publicSession(array $session, ?string $myToken = null): array
    {
        $participants = array_map(function ($p) {
            return [
                'postcode'  => PostcodeService::mask($p['postcode']),
                'lat'       => $p['lat'] ?? null,
                'lng'       => $p['lng'] ?? null,
                'joined_at' => $p['joined_at'],
            ];
        }, $session['participants']);

        $maskedManualPostcodes = array_map(
            fn($pc) => PostcodeService::mask($pc),
            $session['manual_postcodes']
        );

        $venues = $this->maskVenuePostcodes($session['venues']);

        $confirmedVenue = $session['confirmed_venue'];
        if ($confirmedVenue && isset($confirmedVenue['times'])) {
            $confirmedVenue['times'] = array_map(function ($t) {
                if (isset($t['from'])) {
                    $t['from'] = PostcodeService::mask($t['from']);
                }
                return $t;
            }, $confirmedVenue['times']);
        }

        $voteCounts = array_count_values($session['votes'] ?? []);

        $myVote = null;
        if ($myToken && isset($session['votes'][$myToken])) {
            $myVote = $session['votes'][$myToken];
        }

        $voteDetails = [];
        $tokenToIndex = array_flip(array_column($session['participants'], 'token'));
        foreach ($session['votes'] as $token => $venueIndex) {
            if (isset($tokenToIndex[$token])) {
                $pi = $tokenToIndex[$token];
                $voteDetails[] = [
                    'person'      => $pi + 1,
                    'postcode'    => PostcodeService::mask($session['participants'][$pi]['postcode']),
                    'venue_index' => $venueIndex,
                    'venue_name'  => $session['venues'][$venueIndex]['name'] ?? null,
                ];
            }
        }

        return [
            'status'            => $session['status'],
            'occasion'          => $session['occasion'],
            'participants'      => $participants,
            'manual_postcodes'  => $maskedManualPostcodes,
            'participant_count' => count($session['participants']) + count($session['manual_postcodes']),
            'venues'            => $venues,
            'vote_counts'       => (object) $voteCounts,
            'vote_details'      => $voteDetails,
            'my_vote'           => $myVote,
            'confirmed_venue'   => $confirmedVenue,
            'plan_id'           => $session['plan_id'] ?? null,
            'plan_url'          => isset($session['plan_id']) ? url("/plan/{$session['plan_id']}") : null,
            'alerts'            => $session['alerts'] ?? [],
            'centroid'          => $session['centroid'] ?? null,
        ];
    }

    private function maskVenuePostcodes(array $venues): array
    {
        return array_map(function ($venue) {
            if (isset($venue['times'])) {
                $venue['times'] = array_map(function ($t) {
                    if (isset($t['from'])) {
                        $t['from'] = PostcodeService::mask($t['from']);
                    }
                    return $t;
                }, $venue['times']);
            }
            return $venue;
        }, $venues);
    }
}
