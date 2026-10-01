<?php

return [
    // Rijtijdschatting (zie App\Services\TravelEstimator)
    'road_factor' => env('PLANNING_ROAD_FACTOR', 1.3),
    'average_kmh' => env('PLANNING_AVERAGE_KMH', 75),
    'parking_minutes' => env('PLANNING_PARKING_MINUTES', 5),

    // Zoveel dagen voor de deadline krijgen planner en bedrijfsleider een melding
    'alert_days' => env('PLANNING_ALERT_DAYS', 14),

    // Opdrachten met een deadline binnen dit aantal dagen krijgen voorrang in het routevoorstel
    'urgent_days' => env('PLANNING_URGENT_DAYS', 21),

    // Opdrachten uit maandblokken tot zoveel maanden na de routedatum mogen worden meegepland
    'lookahead_months' => env('PLANNING_LOOKAHEAD_MONTHS', 1),

    // Startadres kantoor (Bamendaweg 62, Dordrecht)
    'office' => [
        'address' => 'Bamendaweg 62, 3319 GS Dordrecht',
        'latitude' => 51.8013109,
        'longitude' => 4.7081615,
    ],
];
