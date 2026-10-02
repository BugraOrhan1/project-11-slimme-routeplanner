<?php

namespace Tests\Feature;

use App\Models\Inspector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US25/US26 – inloggen en rollen.
 */
class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_planner_logs_in_and_lands_on_dashboard(): void
    {
        $user = $this->user('planner');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->get('/')->assertOk();
    }

    public function test_wrong_password_shows_error(): void
    {
        $user = $this->user();

        $this->post('/login', ['email' => $user->email, 'password' => 'fout'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inspector_only_sees_own_route_page(): void
    {
        $user = $this->user('inspecteur');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('my-route'));
        $this->get('/opdrachten')->assertRedirect(route('my-route'));
        $this->get('/mijn-route')->assertOk();
    }

    public function test_inspector_cannot_access_user_management(): void
    {
        $this->actingAs($this->user('inspecteur'))
            ->get('/gebruikers')
            ->assertRedirect(route('my-route'));
    }

    public function test_office_roles_can_use_planning_pages(): void
    {
        foreach (['planner', 'bedrijfsleider', 'technisch_manager', 'administratie', 'beheerder'] as $role) {
            $this->actingAs($this->user($role))->get('/planning/nieuw')->assertOk();
        }
    }

    public function test_only_admin_roles_can_manage_users(): void
    {
        $this->actingAs($this->user('planner'))->get('/gebruikers')->assertRedirect(route('dashboard'));
        $this->actingAs($this->user('bedrijfsleider'))->get('/gebruikers')->assertOk();
        $this->actingAs($this->user('beheerder'))->get('/gebruikers')->assertOk();
    }

    public function test_unknown_role_cannot_access_office(): void
    {
        $this->actingAs($this->user('onbekend'))->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_inspector_cannot_finish_stop_of_someone_else(): void
    {
        $other = $this->user('inspecteur');
        $inspector = $this->inspector(attributes: ['user_id' => $this->user('inspecteur')->id]);
        $assignment = $this->assignment([$this->activity()]);
        $route = $inspector->routes()->create(['date' => now()->toDateString(), 'status' => 'goedgekeurd']);
        $stop = $route->stops()->create(['assignment_id' => $assignment->id, 'position' => 1]);

        $this->actingAs($other)->post(route('my-route.finish', $stop))->assertForbidden();
        $this->assertInstanceOf(Inspector::class, $inspector);
    }
}
