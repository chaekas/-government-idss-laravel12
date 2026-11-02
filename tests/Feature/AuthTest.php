<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests_and_links_to_registration(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('register'));
    }

    public function test_registration_page_is_available_to_guests(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create farmer account')
            ->assertSee('Confirm password');
    }

    public function test_public_registration_creates_a_linked_farmer_account_and_signs_user_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New Farmer',
            'email' => 'new.farmer@example.test',
            'phone' => '+255700000001',
            'region' => 'Morogoro',
            'district' => 'Kilosa',
            'password' => 'Secure-Farmer-2026!',
            'password_confirmation' => 'Secure-Farmer-2026!',
            // Public registration must ignore any attempted privilege escalation.
            'role' => 'system_admin',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'new.farmer@example.test')->firstOrFail();
        $this->assertSame('farmer', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->farmer_id);

        $this->assertDatabaseHas('farmers', [
            'id' => $user->farmer_id,
            'full_name' => 'New Farmer',
            'email' => 'new.farmer@example.test',
            'region' => 'Morogoro',
            'district' => 'Kilosa',
        ]);
    }

    public function test_registration_rejects_duplicate_email_addresses(): void
    {
        User::create([
            'name' => 'Existing Farmer',
            'email' => 'existing@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => 'farmer',
            'is_active' => true,
        ]);

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Duplicate Farmer',
            'email' => 'existing@example.test',
            'region' => 'Morogoro',
            'district' => 'Kilosa',
            'password' => 'Secure-Farmer-2026!',
            'password_confirmation' => 'Secure-Farmer-2026!',
        ])->assertRedirect(route('register'))->assertSessionHasErrors('email');

        $this->assertDatabaseCount('farmers', 0);
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

    public function test_login_does_not_redirect_to_a_stale_localhost_intended_url(): void
    {
        $user = User::create([
            'name' => 'Redirect Test Officer',
            'email' => 'redirect-test@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => 'field_officer',
            'is_active' => true,
        ]);

        $this->withSession([
            'url.intended' => 'http://localhost:8000/dashboard',
        ])->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Correct-Horse-Battery-42!',
        ])->assertRedirect(route('dashboard'));
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
