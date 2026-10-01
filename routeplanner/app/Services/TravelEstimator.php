<?php

namespace App\Services;

/**
 * Schat afstand en rijtijd tussen twee punten.
 *
 * Eerste versie: hemelsbrede afstand x omrijfactor en een gemiddelde snelheid.
 * Kan later vervangen worden door OSRM / OpenRouteService (TECH06) zonder de planner aan te passen.
 */
class TravelEstimator
{
    public function __construct(
        private float $roadFactor = 1.3,
        private float $averageKmh = 75,
        private int $parkingMinutes = 5,
    ) {
        $this->roadFactor = (float) config('planning.road_factor', $roadFactor);
        $this->averageKmh = (float) config('planning.average_kmh', $averageKmh);
        $this->parkingMinutes = (int) config('planning.parking_minutes', $parkingMinutes);
    }

    public function km(array $from, array $to): float
    {
        [$lat1, $lng1] = $from;
        [$lat2, $lng2] = $to;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)) * $this->roadFactor;
    }

    public function minutes(array $from, array $to): int
    {
        $km = $this->km($from, $to);

        return $km < 0.5 ? 0 : (int) round($km / $this->averageKmh * 60) + $this->parkingMinutes;
    }
}
