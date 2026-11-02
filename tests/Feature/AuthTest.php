<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_active_user_can_sign_in_and_view_dashboard(): void
    {
        $user = User::create([
            'name' => 'Test Officer',
            'email' => 'officer@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => 'field_officer',
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Correct-Horse-Battery-42!',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        $user = User::create([
            'name' => 'Inactive Officer',
            'email' => 'inactive@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => 'field_officer',
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Correct-Horse-Battery-42!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
