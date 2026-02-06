<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class TflServices {

    private string $baseUrl = 'https://api.tfl.gov.uk';

    private array $candidateStations = [
        'WC2N5DU', // Charing Cross
        'W1D3AF', // Oxford Circus
        'EC4M8AD', // Bank
        'N1C4QL', // King's Cross
        'SE18SW', // Waterloo
        'SW1V1JU', // Victoria
        'W21HB', // Paddington
        'EC1A4JA', // Farringdon
        'E145AB', // Canary Wharf
        'NW12DU', // Camden Town
    ];
   
    public function getJourneyTime(string $from, string $to): ?int {
      $from = str_replace(' ', '', $from);
      $to = str_replace(' ', '', $to);
      
      $response = Http::get("{$this->baseUrl}/Journey/JourneyResults/{$from}/to/{$to}");

      if($response->failed()) {
        return null;
    }
 
    $data = $response->json();

    return collect($data['journeys'] ?? []) -> pluck('duration') -> min();
  }

  public function findFairestMeetingPoint(array $locations): ?array {

    $results = [];

    foreach($this->candidateStations as $station) {
        $times = [];
        foreach($locations as $location) {
            $duration = this->getJourneyTime($location, $station);
             if($duration === null) continue;
             $times[] = $duration;
        } 
    };

        $results[] = [
            'station' => $station,
            'times' => $times,
            'max' => max($times),
            'spread' => max($times) - min($times),
        ];

        usort($results, fn($a, $b) => $a['max'] <=> $b['max']);
    

    return $results[0] ?? null;
  }
}