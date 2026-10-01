<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\ChangeLog;
use App\Models\Inspector;
use App\Models\Route;
use App\Services\DeadlineAlerts;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US23 – Planner dashboard.
 */
class DashboardController extends Controller
{
    public function __invoke(DeadlineAlerts $alerts): Response
    {
        // Meldingen actueel houden, ook als de scheduler niet draait
        $alerts->generate();

        $with = ['location' => fn ($q) => $q->withCount('tanks'), 'location.customer', 'activities'];
        $today = now()->toDateString();

        $open = Assignment::with($with)->where('status', 'open')->orderBy('deadline')->get();
        $plannedIds = Route::where('status', 'goedgekeurd')->with('stops')->get()->flatMap->stops->pluck('assignment_id');

        $todayRoutes = Route::with(['inspector', 'stops'])->whereDate('date', $today)->get();
        $busy = $todayRoutes->pluck('inspector_id');

        return Inertia::render('Dashboard', [
            'stats' => [
                'open' => $open->count(),
                'ingepland' => Assignment::where('status', 'ingepland')->count(),
                'uitgevoerd' => Assignment::where('status', 'uitgevoerd')->count(),
                'overdue' => $open->filter(fn ($a) => $a->deadline->isPast())->count(),
                'proposals' => Route::where('status', 'voorstel')->count(),
            ],
            'deadlines' => $open->reject(fn ($a) => $plannedIds->contains($a->id))
                ->filter(fn ($a) => $a->deadline->lte(now()->addDays(30)))
                ->take(8)->map(fn ($a) => $this->assignmentData($a))->values(),
            'mapPoints' => $open->map(fn ($a) => [
                'id' => $a->id,
                'lat' => $a->location->latitude,
                'lng' => $a->location->longitude,
                'title' => $a->location->customer->name.' – '.$a->location->name,
                'deadline' => $a->deadline->format('d-m-Y'),
                'daysLeft' => (int) now()->startOfDay()->diffInDays($a->deadline, false),
            ])->filter(fn ($p) => $p['lat'])->values(),
            'todayRoutes' => $todayRoutes->map(fn (Route $r) => [
                'id' => $r->id,
                'status' => $r->status,
                'inspector' => $r->inspector->name,
                'color' => $r->inspector->color,
                'stops' => $r->stops->count(),
                'done' => $r->stops->whereNotNull('finished_at')->count(),
                'km' => $r->total_km,
                'endTime' => substr((string) $r->end_time, 0, 5),
            ]),
            'availableInspectors' => Inspector::where('active', true)->whereNotIn('id', $busy)->orderBy('name')
                ->get(['id', 'name', 'color']),
            'recentChanges' => ChangeLog::with('user')->latest('created_at')->take(6)->get()->map(fn ($c) => [
                'id' => $c->id,
                'user' => $c->user?->name,
                'description' => $c->description,
                'at' => $c->created_at->diffForHumans(),
            ]),
        ]);
    }
}
