<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveillanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'field_officer'): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_authenticated_staff_can_open_srs_workflow_registers(): void
    {
        $this->actingAs($this->makeUser());

        $this->get(route('animals.index'))->assertOk();
        $this->get(route('investigations.index'))->assertOk();
        $this->get(route('samples.index'))->assertOk();
        $this->get(route('laboratory.index'))->assertOk();
        $this->get(route('outbreaks.index'))->assertOk();
    }

    public function test_guest_is_redirected_from_surveillance_registers(): void
    {
        $this->get(route('animals.index'))->assertRedirect(route('login'));
        $this->get(route('samples.index'))->assertRedirect(route('login'));
    }

    public function test_non_administrator_cannot_access_user_management(): void
    {
        $this->actingAs($this->makeUser('field_officer'));
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_system_administrator_can_access_user_management(): void
    {
        $this->actingAs($this->makeUser('system_admin'));
        $this->get(route('users.index'))->assertOk();
    }
}
