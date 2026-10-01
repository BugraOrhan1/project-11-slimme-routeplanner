<?php

namespace App\Http\Controllers;

use App\Models\Inspector;
use App\Models\Route;
use App\Services\RoutePlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US09/US10/US13 – routes laten voorstellen, US24 – planningsoverzicht per week.
 */
class PlanningController extends Controller
{
    public function index(Request $request): Response
    {
        $week = Carbon::parse($request->query('week', now()->toDateString()))->startOfWeek();
        $days = collect(range(0, 4))->map(fn ($i) => $week->copy()->addDays($i));

        $routes = Route::with(['inspector', 'stops.assignment.location.customer'])
            ->whereBetween('date', [$week->toDateString(), $week->copy()->addDays(6)->toDateString()])
            ->get();

        return Inertia::render('Planning/Index', [
            'week' => $week->toDateString(),
            'weekNumber' => $week->isoWeek(),
            'days' => $days->map(fn ($d) => ['date' => $d->toDateString(), 'label' => $d->translatedFormat('l j M'), 'isToday' => $d->isToday()]),
            'inspectors' => Inspector::where('active', true)->orderBy('name')->get(['id', 'name', 'color']),
            'routes' => $routes->map(fn (Route $r) => [
                'id' => $r->id,
                'date' => $r->date->toDateString(),
                'inspectorId' => $r->inspector_id,
                'status' => $r->status,
                'km' => $r->total_km,
                'endTime' => substr((string) $r->end_time, 0, 5),
                'stops' => $r->stops->map(fn ($s) => [
                    'arrival' => substr((string) $s->planned_arrival, 0, 5),
                    'name' => $s->assignment->location->name,
                    'customer' => $s->assignment->location->customer->name,
                    'color' => $s->assignment->location->customer->color,
                    'done' => (bool) $s->finished_at,
                ]),
            ]),
        ]);
    }

    public function create(Request $request, RoutePlanner $planner): Response
    {
        $date = Carbon::parse($request->query('date', now()->addWeekday()->toDateString()));
        $existing = Route::whereDate('date', $date)->get()->keyBy('inspector_id');
        $candidates = $planner->candidates($date);

        return Inertia::render('Planning/Create', [
            'date' => $date->toDateString(),
            'inspectors' => Inspector::with('certificates')->where('active', true)->orderBy('name')->get()
                ->map(fn (Inspector $i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'color' => $i->color,
                    'workStart' => substr($i->work_start, 0, 5),
                    'workEnd' => substr($i->work_end, 0, 5),
                    'startAddress' => $i->start_address,
                    'certificates' => $i->certificates->filter(fn ($c) => ! $c->pivot->valid_until || $c->pivot->valid_until >= $date->toDateString())->pluck('name')->values(),
                    'route' => ($r = $existing->get($i->id)) ? ['id' => $r->id, 'status' => $r->status] : null,
                ]),
            'candidates' => $candidates->take(200)->map(fn ($a) => $this->assignmentData($a))->values(),
            'candidateCount' => $candidates->count(),
            'proposals' => Route::with(['inspector', 'stops.assignment.location.customer', 'stops.assignment.activities', 'approver'])
                ->whereDate('date', $date)->get()->map(fn ($r) => $this->routeData($r)),
        ]);
    }

    public function approveAll(Request $request, RoutePlanner $planner): RedirectResponse
    {
        $date = $request->validate(['date' => ['required', 'date']])['date'];
        $routes = Route::whereDate('date', $date)->where('status', 'voorstel')->get();
        $routes->each(fn (Route $route) => $planner->approve($route, $request->user()->id));

        return back()->with('success', "{$routes->count()} route(s) goedgekeurd.");
    }

    public function propose(Request $request, RoutePlanner $planner): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'inspector_ids' => ['required', 'array', 'min:1'],
            'inspector_ids.*' => ['exists:inspectors,id'],
        ], ['inspector_ids.required' => 'Selecteer minimaal één inspecteur.']);

        $routes = $planner->propose($data['date'], $data['inspector_ids'], $request->user()->id);
        $stops = $routes->sum(fn ($r) => $r->stops->count());

        return redirect()->route('planning.create', ['date' => $data['date']])->with(
            $routes->isEmpty() ? 'error' : 'success',
            $routes->isEmpty()
                ? 'Geen voorstel mogelijk: geen geschikte open opdrachten voor de gekozen inspecteurs.'
                : "Voorstel gemaakt: {$routes->count()} route(s) met {$stops} stations. Controleer en keur goed."
        );
    }
}
