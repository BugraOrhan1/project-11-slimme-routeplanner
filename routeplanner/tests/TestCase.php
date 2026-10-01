<?php

namespace Tests;

use App\Models\Activity;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Customer;
use App\Models\Inspector;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function user(string $role = 'planner'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    protected function inspector(array $certificates = [], array $attributes = []): Inspector
    {
        $inspector = Inspector::create([
            'name' => 'Test Inspecteur',
            'start_address' => 'Dordrecht',
            'start_latitude' => 51.80,
            'start_longitude' => 4.70,
            ...$attributes,
        ]);
        foreach ($certificates as $certificate) {
            $inspector->certificates()->attach($certificate->id, ['valid_until' => now()->addYear()->toDateString()]);
        }

        return $inspector;
    }

    protected function assignment(array $activities, float $lat = 51.81, float $lng = 4.72, array $attributes = []): Assignment
    {
        $customer = Customer::firstOrCreate(['name' => 'Shell']);
        $location = Location::create([
            'customer_id' => $customer->id,
            'name' => 'Shell '.uniqid(),
            'address' => 'Voorbeeldweg 1',
            'city' => 'Dordrecht',
            'region' => 'Zuid-Holland',
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
        $location->tanks()->createMany([['product' => 'Diesel'], ['product' => 'Euro 95']]);

        $assignment = Assignment::create([
            'location_id' => $location->id,
            'deadline' => now()->addDays(10)->toDateString(),
            'status' => 'open',
            ...$attributes,
        ]);
        $assignment->activities()->sync(collect($activities)->pluck('id'));

        return $assignment;
    }

    protected function activity(?Certificate $certificate = null, int $minutes = 20, bool $perTank = false): Activity
    {
        return Activity::create([
            'name' => 'Activiteit '.uniqid(),
            'duration_minutes' => $minutes,
            'per_tank' => $perTank,
            'certificate_id' => $certificate?->id,
        ]);
    }
}
