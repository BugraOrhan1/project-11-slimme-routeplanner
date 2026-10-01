<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Route;
use App\Models\RouteStop;
use App\Services\RoutePlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US16 (kaart), US17 (handmatig aanpassen), US18 (opslaan) en goedkeuren door de planner.
 */
class RouteController extends Controller
{
    public function __construct(private RoutePlanner $planner) {}

    public function show(Route $route): Response
    {
        $route->load(['inspector.certificates', 'stops.assignment.location.customer', 'stops.assignment.activities', 'approver']);
        $route->stops->each(fn ($s) => $s->assignment->location->loadCount('tanks'));

        // Andere routes op dezelfde dag (om een stop naar te verplaatsen)
        $siblings = Route::with('inspector')->whereDate('date', $route->date)->where('id', '!=', $route->id)->get();

        return Inertia::render('Routes/Show', [
            'plan' => $this->routeData($route),
            'conflicts' => $this->planner->conflicts($route),
            'siblings' => $siblings->map(fn ($r) => ['id' => $r->id, 'inspector' => $r->inspector->name, 'status' => $r->status]),
            'addable' => $this->planner->candidates($route->date)
                ->filter(fn ($a) => $route->inspector->isQualifiedFor($a, $route->date))
                ->take(100)->map(fn ($a) => $this->assignmentData($a))->values(),
        ]);
    }

    public function reorder(Request $request, Route $route): RedirectResponse
    {
        $ids = $request->validate(['stop_ids' => ['required', 'array'], 'stop_ids.*' => ['integer']])['stop_ids'];

        DB::transaction(function () use ($route, $ids) {
            foreach ($ids as $i => $id) {
                $route->stops()->whereKey($id)->update(['position' => $i + 1]);
            }
            $route->writeChangeLog('gewijzigd', null, "Volgorde van route {$route->logName()} aangepast");
            $this->planner->recalculate($route);
        });

        return back()->with('success', 'Volgorde opgeslagen, tijden zijn opnieuw berekend.');
    }

    public function addStop(Request $request, Route $route): RedirectResponse
    {
        $assignment = Assignment::findOrFail($request->validate(['assignment_id' => ['required', 'exists:assignments,id']])['assignment_id']);

        if ($assignment->stop()->exists()) {
            return back()->with('error', 'Deze opdracht staat al in een route.');
        }

        DB::transaction(function () use ($route, $assignment) {
            $route->stops()->create(['assignment_id' => $assignment->id, 'position' => $route->stops()->count() + 1]);
            if ($route->status === 'goedgekeurd') {
                $assignment->update(['status' => 'ingepland']);
            }
            $route->writeChangeLog('gewijzigd', null, "{$assignment->logName()} toegevoegd aan route {$route->logName()}");
            $this->planner->recalculate($route);
        });

        return back()->with('success', "{$assignment->logName()} is toegevoegd.");
    }

    public function removeStop(Route $route, RouteStop $stop): RedirectResponse
    {
        abort_unless($stop->route_id === $route->id, 404);

        DB::transaction(function () use ($route, $stop) {
            $assignment = $stop->assignment;
            $stop->delete();
            if ($assignment->status === 'ingepland') {
                $assignment->update(['status' => 'open']);
            }
            $route->writeChangeLog('gewijzigd', null, "{$assignment->logName()} uit route {$route->logName()} gehaald");
            $this->planner->recalculate($route);
        });

        return back()->with('success', 'Stop is uit de route gehaald en staat weer open.');
    }

    public function moveStop(Request $request, Route $route, RouteStop $stop): RedirectResponse
    {
        abort_unless($stop->route_id === $route->id, 404);
        $target = Route::findOrFail($request->validate(['target_route_id' => ['required', 'exists:routes,id']])['target_route_id']);

        DB::transaction(function () use ($route, $stop, $target) {
            $stop->update(['route_id' => $target->id, 'position' => $target->stops()->count() + 1]);
            $stop->assignment->update(['status' => $target->status === 'goedgekeurd' ? 'ingepland' : 'open']);
            $route->writeChangeLog('gewijzigd', null, "{$stop->assignment->logName()} verplaatst naar route {$target->logName()}");
            $this->planner->recalculate($route);
            $this->planner->recalculate($target);
        });

        return back()->with('success', "Stop is verplaatst naar {$target->inspector->name}.");
    }

    public function approve(Request $request, Route $route): RedirectResponse
    {
        $this->planner->approve($route, $request->user()->id);

        return back()->with('success', "Route van {$route->inspector->name} is goedgekeurd en staat klaar voor de inspecteur.");
    }

    public function unapprove(Route $route): RedirectResponse
    {
        DB::transaction(function () use ($route) {
            $route->update(['status' => 'voorstel', 'approved_by' => null, 'approved_at' => null]);
            Assignment::whereIn('id', $route->stops()->pluck('assignment_id'))->where('status', 'ingepland')->update(['status' => 'open']);
        });

        return back()->with('success', 'Route is teruggezet naar voorstel.');
    }

    public function destroy(Route $route): RedirectResponse
    {
        $date = $route->date->toDateString();
        DB::transaction(function () use ($route) {
            Assignment::whereIn('id', $route->stops()->pluck('assignment_id'))->where('status', 'ingepland')->update(['status' => 'open']);
            $route->delete();
        });

        return redirect()->route('planning.create', ['date' => $date])->with('success', 'Route is afgewezen en verwijderd; de opdrachten staan weer open.');
    }
}
