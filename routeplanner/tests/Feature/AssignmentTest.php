<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\ChangeLog;
use App\Models\Customer;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * US01 (toevoegen), US01b (importeren), US15 (duur), US27 (wijzigingslog).
 */
class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_planner_can_add_an_assignment_and_it_is_logged(): void
    {
        $planner = $this->user('planner');
        $activity = $this->activity();
        $location = $this->assignment([$activity])->location;

        $this->actingAs($planner)->post(route('assignments.store'), [
            'location_id' => $location->id,
            'status' => 'open',
            'deadline' => now()->addMonth()->toDateString(),
            'plan_month' => now()->addMonth()->format('Y-m'),
            'priority' => 2,
            'activity_ids' => [$activity->id],
        ])->assertRedirect(route('assignments.index'));

        $this->assertSame(2, Assignment::count());
        $log = ChangeLog::latest('id')->first();
        $this->assertSame($planner->id, $log->user_id);
        $this->assertSame('aangemaakt', $log->action);
    }

    public function test_assignment_requires_at_least_one_activity(): void
    {
        $location = $this->assignment([$this->activity()])->location;

        $this->actingAs($this->user())->post(route('assignments.store'), [
            'location_id' => $location->id,
            'status' => 'open',
            'deadline' => now()->toDateString(),
            'priority' => 2,
            'activity_ids' => [],
        ])->assertSessionHasErrors('activity_ids');
    }

    public function test_duration_is_sum_of_activities_and_depends_on_tank_count(): void
    {
        $fixed = $this->activity(minutes: 20);
        $perTank = $this->activity(minutes: 10, perTank: true);
        $assignment = $this->assignment([$fixed, $perTank]); // 2 tanks

        $this->assertSame(40, $assignment->estimatedMinutes());

        $assignment->location->tanks()->create(['product' => 'Euro 98']);
        $this->assertSame(50, $assignment->fresh()->estimatedMinutes());
    }

    public function test_changes_record_old_and_new_value(): void
    {
        $assignment = $this->assignment([$this->activity()])->fresh();

        $this->actingAs($this->user('administratie'));
        $assignment->update(['priority' => 1]);

        $log = ChangeLog::where('action', 'gewijzigd')->latest('id')->first();
        $this->assertSame(['priority' => 2], $log->changes['oud']);
        $this->assertSame(['priority' => 1], $log->changes['nieuw']);
    }

    public function test_csv_import_creates_customer_location_and_assignment(): void
    {
        Http::fake(['api.pdok.nl/*' => Http::response(['response' => ['docs' => [[
            'weergavenaam' => 'Bamendaweg 62, 3319GS Dordrecht',
            'centroide_ll' => 'POINT(4.70816153 51.80131087)',
            'postcode' => '3319GS',
            'woonplaatsnaam' => 'Dordrecht',
            'provincienaam' => 'Zuid-Holland',
        ]]]])]);
        $activity = $this->activity();
        $activity->update(['name' => 'Visuele inspectie tank']);

        $csv = "klant;station;adres;postcode;plaats;deadline;maand;activiteiten;tanks\n"
            ."BP;BP Dordrecht Test;Bamendaweg 62;;Dordrecht;2026-11-15;2026-11;Visuele inspectie tank;Diesel|Euro 95|Euro 98\n"
            .";zonder klant;x;;y;2026-11-15;;;\n";

        $this->actingAs($this->user())->post(route('assignments.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('opdrachten.csv', $csv),
        ])->assertRedirect(route('assignments.import'))
            ->assertSessionHas('success', '1 opdracht(en) geïmporteerd.')
            ->assertSessionHas('error');

        $location = Location::where('name', 'BP Dordrecht Test')->firstOrFail();
        $this->assertSame('BP', Customer::find($location->customer_id)->name);
        $this->assertSame('Zuid-Holland', $location->region);
        $this->assertSame(3, $location->tanks()->count());
        $this->assertSame('2026-11-01', $location->assignments()->first()->plan_month->toDateString());
    }
}
