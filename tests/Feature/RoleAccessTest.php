<?php

namespace Tests\Feature;

use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?int $farmerId = null): User
    {
        return User::create([
            'name' => 'Test '.str_replace('_', ' ', $role),
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => $role,
            'farmer_id' => $farmerId,
            'is_active' => true,
        ]);
    }

    public function test_field_officer_cannot_open_administrator_user_management(): void
    {
        $this->actingAs($this->makeUser('field_officer'))
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_farmer_cannot_open_staff_farmer_registry(): void
    {
        $farmerRecord = Farmer::create([
            'farmer_code' => 'FRM-TEST-OWN01',
            'full_name' => 'Test Farmer',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
        ]);

        $this->actingAs($this->makeUser('farmer', $farmerRecord->id))
            ->get(route('farmers.index'))
            ->assertForbidden();
    }

    public function test_farmer_cannot_access_staff_or_laboratory_workflows(): void
    {
        $farmerRecord = Farmer::create([
            'farmer_code' => 'FRM-TEST-OWN03',
            'full_name' => 'Farmer Workflow Test',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
        ]);
        $farmer = $this->makeUser('farmer', $farmerRecord->id);

        $this->actingAs($farmer)->get(route('farms.index'))->assertForbidden();
        $this->get(route('samples.index'))->assertForbidden();
        $this->get(route('laboratory.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_farmer_only_sees_cases_linked_to_their_farm(): void
    {
        $ownFarmer = Farmer::create([
            'farmer_code' => 'FRM-TEST-OWN02',
            'full_name' => 'Owner Farmer',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
        ]);
        $otherFarmer = Farmer::create([
            'farmer_code' => 'FRM-TEST-OTH01',
            'full_name' => 'Other Farmer',
            'region' => 'Arusha',
            'district' => 'Arusha',
        ]);
        $ownFarm = Farm::create([
            'farm_code' => 'FARM-TEST-OWN01',
            'farmer_id' => $ownFarmer->id,
            'name' => 'Owner Poultry Farm',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
            'bird_count' => 20,
        ]);
        $otherFarm = Farm::create([
            'farm_code' => 'FARM-TEST-OTH01',
            'farmer_id' => $otherFarmer->id,
            'name' => 'Other Poultry Farm',
            'region' => 'Arusha',
            'district' => 'Arusha',
            'bird_count' => 40,
        ]);
        $farmerUser = $this->makeUser('farmer', $ownFarmer->id);
        $otherReporter = $this->makeUser('field_officer');

        foreach ([[$ownFarm, 'CASE-OWN-0001', $farmerUser->id], [$otherFarm, 'CASE-OTHER-01', $otherReporter->id]] as [$farm, $caseCode, $reporterId]) {
            DiseaseCase::create([
                'case_code' => $caseCode,
                'farm_id' => $farm->id,
                'reported_by' => $reporterId,
                'disease_suspected' => 'Newcastle disease',
                'date_observed' => now()->toDateString(),
                'symptoms' => 'Reduced feed intake and respiratory signs',
                'birds_affected' => 2,
                'birds_dead' => 1,
                'severity' => 'medium',
                'status' => 'reported',
            ]);
        }

        $this->actingAs($farmerUser)->get(route('cases.index'))
            ->assertOk()
            ->assertSee('CASE-OWN-0001')
            ->assertDontSee('CASE-OTHER-01');
    }

    public function test_laboratory_technician_can_open_results_but_not_field_investigations_or_reports(): void
    {
        $this->actingAs($this->makeUser('laboratory_technician'))
            ->get(route('laboratory.index'))->assertOk();

        $this->get(route('investigations.index'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_laboratory_manager_can_review_results_but_cannot_submit_technician_results(): void
    {
        $this->actingAs($this->makeUser('laboratory_manager'))
            ->get(route('laboratory.index'))->assertOk();

        $this->get(route('laboratory.create'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_mlf_officer_can_view_reports_but_cannot_manage_accounts_or_farm_registry(): void
    {
        $this->actingAs($this->makeUser('mlf_officer'))
            ->get(route('reports.index'))->assertOk();

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('farms.index'))->assertForbidden();
    }

    public function test_zonal_laboratory_administrator_can_view_laboratory_registry_but_not_user_management(): void
    {
        $this->actingAs($this->makeUser('zonal_laboratory_administrator'))
            ->get(route('laboratories.index'))->assertOk();

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
    }

    public function test_only_system_administrator_can_access_user_management(): void
    {
        $this->actingAs($this->makeUser('system_admin'))
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_staff_account_does_not_require_or_retain_a_farmer_link(): void
    {
        $farmerRecord = Farmer::create([
            'farmer_code' => 'FRM-STAFF-LINK-01',
            'full_name' => 'Existing Farmer',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
        ]);
        $this->makeUser('farmer', $farmerRecord->id);
        $admin = $this->makeUser('system_admin');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Laboratory Technician',
            'email' => 'lab-tech-unique@example.test',
            'role' => 'laboratory_technician',
            // Even if a stale/hidden form submits an already-used farmer ID,
            // it must be ignored for staff roles.
            'farmer_id' => $farmerRecord->id,
            'password' => 'Strong-Staff-Password-2026',
            'password_confirmation' => 'Strong-Staff-Password-2026',
        ])->assertRedirect(route('users.index'))
          ->assertSessionHas('status', 'User created successfully.');

        $this->assertDatabaseHas('users', [
            'email' => 'lab-tech-unique@example.test',
            'role' => 'laboratory_technician',
            'farmer_id' => null,
        ]);
    }

    public function test_existing_super_admin_role_can_access_user_management_and_sees_sidebar_link(): void
    {
        $this->actingAs($this->makeUser('super_admin'))
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('User management')
            ->assertSee('Create user');
    }

    public function test_legacy_admin_role_can_access_user_management(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('User management');
    }
    public function test_system_administrator_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = $this->makeUser('system_admin');

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'field_officer',
                'farmer_id' => null,
                'is_active' => 1,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'system_admin',
            'is_active' => true,
        ]);
    }

}
