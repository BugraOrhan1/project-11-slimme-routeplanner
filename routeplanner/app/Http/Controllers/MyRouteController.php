<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US19 – Inspecteur bekijkt zijn eigen (goedgekeurde) dagroute op de computer.
 * US21/US22 – Opdracht starten en afronden.
 */
class MyRouteController extends Controller
{
    public function show(Request $request): Response
    {
        $inspector = $request->user()->inspector;
        $date = Carbon::parse($request->query('date', now()->toDateString()));

        $route = $inspector
            ? Route::with(['inspector', 'stops.assignment.location.customer', 'stops.assignment.activities', 'approver'])
                ->where('inspector_id', $inspector->id)->whereDate('date', $date)->where('status', 'goedgekeurd')->first()
            : null;
        $route?->stops->each(fn ($s) => $s->assignment->location->loadCount('tanks'));

        $upcoming = $inspector
            ? Route::withCount('stops')->where('inspector_id', $inspector->id)->where('status', 'goedgekeurd')
                ->whereDate('date', '>=', now()->toDateString())->orderBy('date')->take(10)->get()
                ->map(fn ($r) => ['date' => $r->date->toDateString(), 'stops' => $r->stops_count, 'km' => $r->total_km])
            : [];

        return Inertia::render('MyRoute', [
            'date' => $date->toDateString(),
            'hasInspector' => (bool) $inspector,
            'plan' => $route ? $this->routeData($route) : null,
            'upcoming' => $upcoming,
        ]);
    }

    public function start(Request $request, RouteStop $stop): RedirectResponse
    {
        $this->authorizeStop($request, $stop);
        $stop->update(['started_at' => now()]);
        $stop->route->writeChangeLog('gewijzigd', null, "{$stop->assignment->logName()} gestart door {$request->user()->name}");

        return back()->with('success', 'Opdracht gestart.');
    }

    public function finish(Request $request, RouteStop $stop): RedirectResponse
    {
        $this->authorizeStop($request, $stop);
        $stop->update(['finished_at' => now(), 'started_at' => $stop->started_at ?? now()]);
        $stop->assignment->update(['status' => 'uitgevoerd']);

        return back()->with('success', 'Opdracht afgerond.');
    }

    private function authorizeStop(Request $request, RouteStop $stop): void
    {
        $user = $request->user();
        abort_unless(! $user->isInspector() || $stop->route->inspector->user_id === $user->id, 403);
    }
}
