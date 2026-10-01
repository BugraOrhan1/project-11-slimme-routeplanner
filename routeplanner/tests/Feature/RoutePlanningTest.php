<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Certificate;
use App\Models\Route;
use App\Services\DeadlineAlerts;
use App\Services\RoutePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US05 (certificaten), US13 (voorstel), US17 (aanpassen), goedkeuren, US08b (meldingen).
 */
class RoutePlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposal_only_assigns_work_the_inspector_is_certified_for(): void
    {
        $basis = Certificate::create(['name' => 'Basis']);
        $kb = Certificate::create(['name' => 'Kathodische bescherming']);
        $normal = $this->assignment([$this->activity($basis)]);
        $needsKb = $this->assignment([$this->activity($basis), $this->activity($kb)]);
        $inspector = $this->inspector([$basis]);

        $routes = app(RoutePlanner::class)->propose(now()->addDay()->toDateString(), [$inspector->id]);

        $planned = $routes->first()->stops->pluck('assignment_id')->all();
        $this->assertContains($normal->id, $planned);
        $this->assertNotContains($needsKb->id, $planned);
    }

    public function test_expired_certificate_is_not_valid(): void
    {
        $kb = Certificate::create(['name' => 'Kathodische bescherming']);
        $assignment = $this->assignment([$this->activity($kb)]);
        $inspector = $this->inspector();
        $inspector->certificates()->attach($kb->id, ['valid_until' => now()->subDay()->toDateString()]);

        $this->assertFalse($inspector->fresh()->isQualifiedFor($assignment));
    }

    public function test_proposal_fits_in_the_working_day_and_groups_nearby_stations(): void
    {
        $activity = $this->activity(minutes: 120);
        // 6 stations van 2 uur in Dordrecht: maximaal ~4 passen in een werkdag van 9 uur
        foreach (range(1, 6) as $i) {
            $this->assignment([$activity], 51.80 + $i / 1000, 4.70);
        }
        $far = $this->assignment([$activity], 53.21, 6.56); // Groningen
        $inspector = $this->inspector();

        $route = app(RoutePlanner::class)->propose(now()->addDay()->toDateString(), [$inspector->id])->first();

        $this->assertLessThanOrEqual(4, $route->stops->count());
        $this->assertNotContains($far->id, $route->stops->pluck('assignment_id'));
        $this->assertLessThanOrEqual('16:30', substr($route->end_time, 0, 5));
        $this->assertSame('voorstel', $route->status);
    }

    public function test_planner_approves_route_and_assignments_become_planned(): void
    {
        $planner = $this->user('planner');
        $assignment = $this->assignment([$this->activity()]);
        $inspector = $this->inspector();
        $route = app(RoutePlanner::class)->propose(now()->addDay()->toDateString(), [$inspector->id])->first();

        $this->actingAs($planner)->post(route('routes.approve', $route))->assertRedirect();

        $this->assertSame('goedgekeurd', $route->fresh()->status);
        $this->assertSame($planner->id, $route->fresh()->approved_by);
        $this->assertSame('ingepland', $assignment->fresh()->status);
    }

    public function test_reordering_stops_recalculates_times(): void
    {
        $activity = $this->activity();
        $a = $this->assignment([$activity], 51.81, 4.72);
        $b = $this->assignment([$activity], 51.90, 4.48); // Rotterdam
        $route = $this->inspector()->routes()->create(['date' => now()->addDay()->toDateString()]);
        $first = $route->stops()->create(['assignment_id' => $a->id, 'position' => 1]);
        $second = $route->stops()->create(['assignment_id' => $b->id, 'position' => 2]);
        app(RoutePlanner::class)->recalculate($route);

        $this->actingAs($this->user())->put(route('routes.reorder', $route), ['stop_ids' => [$second->id, $first->id]])->assertRedirect();

        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame('07:30', substr($route->inspector->work_start, 0, 5));
        $this->assertGreaterThan($second->fresh()->planned_arrival, $first->fresh()->planned_arrival);
    }

    public function test_rejecting_a_route_releases_the_assignments(): void
    {
        $assignment = $this->assignment([$this->activity()]);
        $route = app(RoutePlanner::class)->propose(now()->addDay()->toDateString(), [$this->inspector()->id])->first();

        $this->actingAs($this->user())->delete(route('routes.destroy', $route))->assertRedirect();

        $this->assertNull(Route::find($route->id));
        $this->assertSame('open', $assignment->fresh()->status);
        $this->assertFalse($assignment->stop()->exists());
    }

    public function test_deadline_alerts_go_to_planner_and_company_manager_only(): void
    {
        $planner = $this->user('planner');
        $manager = $this->user('bedrijfsleider');
        $admin = $this->user('administratie');
        $this->assignment([$this->activity()], attributes: ['deadline' => now()->addDays(5)->toDateString()]);
        $this->assignment([$this->activity()], attributes: ['deadline' => now()->addDays(60)->toDateString()]);

        $created = app(DeadlineAlerts::class)->generate();
        app(DeadlineAlerts::class)->generate(); // geen dubbele meldingen

        $this->assertSame(2, $created);
        $this->assertSame(1, Alert::where('user_id', $planner->id)->count());
        $this->assertSame(1, Alert::where('user_id', $manager->id)->count());
        $this->assertSame(0, Alert::where('user_id', $admin->id)->count());
    }
}
