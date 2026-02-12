<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PostcodeService
{
    /**
     * Batch geocode postcodes using postcodes.io (free, no auth).
     *
     * Returns an array of ['postcode' => ..., 'lat' => ..., 'lng' => ...] in the same order,
     * or null for any postcode that couldn't be resolved.
     */
    public function geocode(array $postcodes): array
    {
        $response = Http::timeout(10)
            ->post('https://api.postcodes.io/postcodes', [
                'postcodes' => $postcodes,
            ]);

        if ($response->failed()) {
            return [];
        }

        $results = [];

        foreach ($response->json('result') ?? [] as $item) {
            $result = $item['result'] ?? null;

            if ($result) {
                $results[] = [
                    'postcode' => $result['postcode'],
                    'lat'      => $result['latitude'],
                    'lng'      => $result['longitude'],
                ];
            } else {
                $results[] = null;
            }
        }

        return $results;
    }

    /**
     * Calculate the geographic centroid (arithmetic mean) of a set of coordinates.
     * Good enough for London-scale distances where Earth curvature is negligible.
     */
    public function centroid(array $points): array
    {
        $latSum = 0;
        $lngSum = 0;
        $count = count($points);

        foreach ($points as $point) {
            $latSum += $point['lat'];
            $lngSum += $point['lng'];
        }

        return [
            'lat' => $latSum / $count,
            'lng' => $lngSum / $count,
        ];
    }
}
