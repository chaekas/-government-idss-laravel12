<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdssRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::create([
            'name' => 'Role Matrix '.$role,
            'email' => $role.'-matrix@example.test',
            'password' => 'Strong-Role-Matrix-2026',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_exactly_eight_canonical_idss_roles_are_configured_with_labels_and_responsibilities(): void
    {
        $roles = config('idss.roles');

        $this->assertSame([
            'farmer',
            'field_officer',
            'laboratory_technician',
            'laboratory_manager',
            'regional_veterinary_officer',
            'zonal_laboratory_administrator',
            'mlf_officer',
            'system_admin',
        ], array_keys($roles));

        foreach ($roles as $role) {
            $this->assertNotEmpty($role['label']);
            $this->assertNotEmpty($role['description']);
        }
    }

    public function test_regional_veterinary_officer_has_surveillance_oversight_but_not_account_administration(): void
    {
        $this->actingAs($this->account('regional_veterinary_officer'));

        $this->get(route('investigations.index'))->assertOk();
        $this->get(route('outbreaks.index'))->assertOk();
        $this->get(route('reports.index'))->assertOk();
        $this->get(route('gis.index'))->assertOk();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('audit.index'))->assertForbidden();
    }

    public function test_zonal_laboratory_administrator_has_laboratory_and_sample_oversight_only(): void
    {
        $this->actingAs($this->account('zonal_laboratory_administrator'));

        $this->get(route('samples.index'))->assertOk();
        $this->get(route('laboratories.index'))->assertOk();
        $this->get(route('laboratory.index'))->assertOk();
        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('gis.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_mlf_officer_has_national_surveillance_read_access_without_user_administration(): void
    {
        $this->actingAs($this->account('mlf_officer'));

        $this->get(route('cases.index'))->assertOk();
        $this->get(route('samples.index'))->assertOk();
        $this->get(route('laboratory.index'))->assertOk();
        $this->get(route('outbreaks.index'))->assertOk();
        $this->get(route('reports.index'))->assertOk();
        $this->get(route('gis.index'))->assertOk();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_inactive_account_is_denied_even_when_its_role_is_authorized(): void
    {
        $user = $this->account('field_officer');
        $user->update(['is_active' => false]);

        $this->actingAs($user)->get(route('samples.index'))->assertForbidden();
    }
    public function test_legacy_super_admin_account_can_update_its_profile_without_losing_administrator_access(): void
    {
        $user = $this->account('super_admin');

        $this->actingAs($user)->put(route('users.update', $user), [
            'name' => 'Updated Legacy Administrator',
            'email' => $user->email,
            'role' => 'super_admin',
            'farmer_id' => null,
            'is_active' => 1,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'super_admin',
            'is_active' => true,
            'name' => 'Updated Legacy Administrator',
        ]);
    }

    public function test_super_admin_can_create_edit_and_reset_staff_accounts(): void
    {
        $admin = $this->account('system_admin');
        $this->actingAs($admin);

        $this->get(route('users.index'))->assertOk()->assertSee('Users management');
        $this->get(route('users.create'))->assertOk()->assertDontSee('Farmer / Livestock Owner');

        $this->post(route('users.store'), [
            'name' => 'New Lab Technician',
            'email' => 'new.tech@example.test',
            'role' => 'laboratory_technician',
            'password' => 'New-Staff-Password-2026',
            'password_confirmation' => 'New-Staff-Password-2026',
        ])->assertRedirect(route('users.index'));

        $staff = User::where('email', 'new.tech@example.test')->firstOrFail();
        $this->assertSame('laboratory_technician', $staff->role);
        $this->assertNull($staff->farmer_id);

        $this->put(route('users.update', $staff), [
            'name' => 'Updated Lab Technician',
            'email' => $staff->email,
            'role' => 'laboratory_manager',
            'is_active' => 1,
        ])->assertRedirect(route('users.index'));

        $this->put(route('users.password.reset', $staff), [
            'password' => 'Reset-Staff-Password-2026',
            'password_confirmation' => 'Reset-Staff-Password-2026',
        ])->assertRedirect(route('users.index'));

        $staff->refresh();
        $this->assertSame('Updated Lab Technician', $staff->name);
        $this->assertSame('laboratory_manager', $staff->role);
        $this->assertTrue(\\\\Illuminate\\\\Support\\\\Facades\\\\Hash::check('Reset-Staff-Password-2026', $staff->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_reset', 'entity_id' => (string) $staff->id]);
    }

    public function test_super_admin_cannot_create_or_edit_farmer_accounts_or_assign_farmer_role(): void
    {
        $admin = $this->account('system_admin');
        $farmer = $this->account('farmer');
        $this->actingAs($admin);

        $this->post(route('users.store'), [
            'name' => 'Admin-created farmer',
            'email' => 'admin-farmer@example.test',
            'role' => 'farmer',
            'password' => 'Farmer-Password-2026',
            'password_confirmation' => 'Farmer-Password-2026',
        ])->assertSessionHasErrors('role');

        $this->put(route('users.update', $farmer), [
            'name' => $farmer->name,
            'email' => $farmer->email,
            'role' => 'field_officer',
            'is_active' => 1,
        ])->assertNotFound();

        $this->put(route('users.password.reset', $farmer), [
            'password' => 'Reset-Farmer-Password-2026',
            'password_confirmation' => 'Reset-Farmer-Password-2026',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'admin-farmer@example.test']);
        $this->assertSame('farmer', $farmer->fresh()->role);
    }

    public function test_non_admin_cannot_access_staff_user_management_or_password_reset(): void
    {
        $staff = $this->account('field_officer');
        $target = $this->account('laboratory_technician');
        $this->actingAs($staff);
        $this->get(route('users.index'))->assertForbidden();
        $this->put(route('users.password.reset', $target), [
            'password' => 'Reset-Staff-Password-2026',
            'password_confirmation' => 'Reset-Staff-Password-2026',
        ])->assertForbidden();
    }

}
