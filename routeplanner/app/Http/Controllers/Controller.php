<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Route;
use App\Models\RouteStop;

abstract class Controller
{
    /** Opdracht zoals de Vue-pagina's hem gebruiken. */
    protected function assignmentData(Assignment $assignment): array
    {
        $location = $assignment->location;

        return [
            'id' => $assignment->id,
            'status' => $assignment->status,
            'deadline' => $assignment->deadline?->toDateString(),
            'daysLeft' => $assignment->deadline ? (int) now()->startOfDay()->diffInDays($assignment->deadline, false) : null,
            'planMonth' => $assignment->plan_month?->format('Y-m'),
            'priority' => $assignment->priority,
            'notes' => $assignment->notes,
            'minutes' => $assignment->estimatedMinutes(),
            'activities' => $assignment->activities->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'city' => $location->city,
                'region' => $location->region,
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'tanks' => $location->tanks_count ?? $location->tanks()->count(),
                'customer' => [
                    'id' => $location->customer->id,
                    'name' => $location->customer->name,
                    'color' => $location->customer->color,
                ],
            ],
        ];
    }

    protected function routeData(Route $route): array
    {
        $inspector = $route->inspector;

        return [
            'id' => $route->id,
            'date' => $route->date->toDateString(),
            'status' => $route->status,
            'totalKm' => $route->total_km,
            'driveMinutes' => $route->total_drive_minutes,
            'workMinutes' => $route->total_work_minutes,
            'endTime' => substr((string) $route->end_time, 0, 5),
            'approvedBy' => $route->approver?->name,
            'approvedAt' => $route->approved_at?->format('d-m-Y H:i'),
            'inspector' => [
                'id' => $inspector->id,
                'name' => $inspector->name,
                'color' => $inspector->color,
                'start' => ['lat' => $inspector->start_latitude, 'lng' => $inspector->start_longitude, 'address' => $inspector->start_address],
                'workStart' => substr($inspector->work_start, 0, 5),
                'workEnd' => substr($inspector->work_end, 0, 5),
            ],
            'stops' => $route->stops->map(fn (RouteStop $stop) => [
                'id' => $stop->id,
                'position' => $stop->position,
                'arrival' => substr((string) $stop->planned_arrival, 0, 5),
                'departure' => substr((string) $stop->planned_departure, 0, 5),
                'driveMinutes' => $stop->drive_minutes,
                'distanceKm' => $stop->distance_km,
                'startedAt' => $stop->started_at?->format('H:i'),
                'finishedAt' => $stop->finished_at?->format('H:i'),
                'assignment' => $this->assignmentData($stop->assignment),
            ])->values(),
        ];
    }
}
