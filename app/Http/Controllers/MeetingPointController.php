<?php

namespace App\Http\Controllers;

use App\Services\TflServices;
use Illuminate\Http\Request;

class MeetingPointController extends Controller
{
    public function __construct (private TflServices $tfl) {}

    public function calculate(Request $request){


        $validated = $request->validate([
          'locations' => 'required|array|min:2',
          'locations.*' => 'required|string',
          'destination' => 'required|string',
        ]);

        $times = [];

        foreach($validated['locations'] as $location){
            $duration = $this->tfl->getJourneyTime($location, $validated['destination']);
            $times[] = [
                'from' => $location,
                'duration' => $duration,
            ];
        }

        $duration = array_column($times, 'duration');

       return response()->json([
        'destination' => $validated['destination'],
        'travellers' => $times,
        'max_time' => max($duration),
        'min_time' => min($duration),
        'spread' => max($duration) - min($duration),
       ]);
    }
}
