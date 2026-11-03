<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FarmType;
use App\Models\Farmer;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\Department;
use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Database Test '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_database_contains_srs_major_entities_and_normalized_master_data(): void
    {
        foreach ([
            'organizations', 'departments', 'offices', 'roles', 'permissions', 'role_user',
            'permission_role', 'administrative_areas', 'farmer_contacts', 'farmer_addresses',
            'farm_types', 'farmer_farm', 'farmer_ownership_history', 'species', 'breeds',
            'animal_identifications', 'animal_movements', 'animal_ownership_transfers',
            'vaccination_campaigns', 'vaccinations', 'disease_categories', 'disease_symptoms',
            'animal_disease_records', 'laboratory_sections', 'laboratory_tests', 'test_methods', 'farm_locations', 'laboratory_user', 'laboratory_test_assignments', 'disease_case_symptom', 'outbreak_case', 'report_definitions', 'generated_reports', 'dashboard_definitions', 'system_settings',
            'sample_types', 'sample_transportations', 'sample_storage_records',
            'quality_control_records', 'laboratory_equipment',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing expected IDSS table: {$table}");
        }

        $this->assertTrue(Schema::hasColumn('farmers', 'sex'));
        $this->assertTrue(Schema::hasColumn('farmers', 'date_of_birth'));
        $this->assertTrue(Schema::hasColumn('farmers', 'nationality'));
        $this->assertTrue(Schema::hasColumn('farms', 'farm_type_id'));
        $this->assertTrue(Schema::hasColumn('animals', 'farmer_id'));
        $this->assertTrue(Schema::hasColumn('disease_cases', 'animal_id'));
        $this->assertTrue(Schema::hasColumn('samples', 'sample_type_id'));
    }

    public function test_reference_seeders_are_repeatable_and_seed_only_srs_defined_roles_and_farm_types(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(8, Role::count());
        $this->assertSame(6, FarmType::count());
        $this->assertDatabaseHas('farm_types', ['code' => 'AGRO_PASTORAL', 'name' => 'Agro-pastoral']);
        $this->assertSame(0, Schema::hasTable('administrative_areas') ? \App\Models\AdministrativeArea::count() : -1);
    }

    public function test_multiple_farmers_can_be_linked_to_one_farm_with_ownership_history(): void
    {
        $first = Farmer::create(['farmer_code' => 'FRM-PH1-0001', 'full_name' => 'First Farmer', 'region' => 'Region A', 'district' => 'District A']);
        $second = Farmer::create(['farmer_code' => 'FRM-PH1-0002', 'full_name' => 'Second Farmer', 'region' => 'Region A', 'district' => 'District A']);
        $farm = Farm::create(['farm_code' => 'FARM-PH1-0001', 'farmer_id' => $first->id, 'name' => 'Shared Farm', 'region' => 'Region A', 'district' => 'District A', 'bird_count' => 0]);

        $farm->farmers()->attach([
            $first->id => ['ownership_type' => 'owner', 'is_primary' => true],
            $second->id => ['ownership_type' => 'joint_owner', 'is_primary' => false],
        ]);

        $this->assertCount(2, $farm->fresh()->farmers);
        $this->assertSame(1, $first->fresh()->farms()->count());
    }

    public function test_administrator_can_create_master_data_and_action_is_audited(): void
    {
        $admin = $this->user('system_admin');

        $this->actingAs($admin)->post(route('master-data.store', 'species'), [
            'code' => 'POULTRY',
            'name' => 'Poultry',
            'description' => 'Poultry species catalogue entry.',
            'is_active' => 1,
        ])->assertRedirect(route('master-data.index', 'species'));

        $this->assertDatabaseHas('species', ['code' => 'POULTRY', 'name' => 'Poultry']);
        $this->assertTrue(AuditLog::where('user_id', $admin->id)->where('action', 'master_data.created')->exists());
    }

    public function test_non_administrator_cannot_manage_master_data(): void
    {
        $this->actingAs($this->user('field_officer'))
            ->get(route('master-data.index', 'species'))
            ->assertForbidden();
    }
    public function test_administrator_can_manage_organization_department_and_office_master_data(): void
    {
        $admin = $this->user('system_admin');

        $this->actingAs($admin)->post(route('master-data.store', 'organizations'), [
            'code' => 'MINISTRY-MLF',
            'name' => 'Ministry of Livestock and Fisheries',
            'organization_type' => 'Ministry',
            'is_active' => 1,
        ])->assertRedirect(route('master-data.index', 'organizations'));

        $organization = Organization::where('code', 'MINISTRY-MLF')->firstOrFail();

        $this->post(route('master-data.store', 'departments'), [
            'organization_id' => $organization->id,
            'code' => 'ANIMAL-HEALTH',
            'name' => 'Animal Health Department',
            'is_active' => 1,
        ])->assertRedirect(route('master-data.index', 'departments'));

        $department = Department::where('code', 'ANIMAL-HEALTH')->firstOrFail();

        $this->post(route('master-data.store', 'offices'), [
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'code' => 'DODOMA-REGIONAL',
            'name' => 'Dodoma Regional Veterinary Office',
            'office_type' => 'Regional office',
            'is_active' => 1,
        ])->assertRedirect(route('master-data.index', 'offices'));

        $this->assertDatabaseHas('offices', [
            'code' => 'DODOMA-REGIONAL',
            'organization_id' => $organization->id,
            'department_id' => $department->id,
        ]);
        $this->assertSame(3, AuditLog::where('user_id', $admin->id)->where('action', 'master_data.created')->count());
    }

    public function test_office_cannot_be_linked_to_a_department_from_another_organization(): void
    {
        $admin = $this->user('system_admin');
        $first = Organization::create(['code' => 'ORG-A', 'name' => 'Organization A', 'is_active' => true]);
        $second = Organization::create(['code' => 'ORG-B', 'name' => 'Organization B', 'is_active' => true]);
        $department = Department::create(['organization_id' => $first->id, 'code' => 'DEPT-A', 'name' => 'Department A', 'is_active' => true]);

        $this->actingAs($admin)->from(route('master-data.create', 'offices'))
            ->post(route('master-data.store', 'offices'), [
                'organization_id' => $second->id,
                'department_id' => $department->id,
                'code' => 'OFFICE-B',
                'name' => 'Office B',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors();
    }



    public function test_farmer_profile_can_be_updated_and_archived_without_deleting_history(): void
    {
        $admin = $this->user('system_admin');
        $farmer = Farmer::create([
            'farmer_code' => 'FRM-PH1-EDIT',
            'full_name' => 'Original Farmer',
            'phone' => '0700000001',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->put(route('farmers.update', $farmer), [
            'full_name' => 'Updated Farmer',
            'phone' => '0700000001',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
            'status' => 'active',
        ])->assertRedirect(route('farmers.index'));

        $this->assertSame('Updated Farmer', $farmer->fresh()->full_name);
        $this->delete(route('farmers.destroy', $farmer))->assertRedirect(route('farmers.index'));
        $this->assertSame('inactive', $farmer->fresh()->status);
        $this->assertNotNull($farmer->fresh()->archived_at);
    }

    public function test_farm_update_preserves_previous_owner_and_location_history(): void
    {
        $admin = $this->user('system_admin');
        $first = Farmer::create(['farmer_code' => 'FRM-PH1-A', 'full_name' => 'Owner A', 'region' => 'Region A', 'district' => 'District A']);
        $second = Farmer::create(['farmer_code' => 'FRM-PH1-B', 'full_name' => 'Owner B', 'region' => 'Region A', 'district' => 'District A']);
        $type = FarmType::create(['code' => 'PH1-COMM', 'name' => 'Commercial', 'is_active' => true]);
        $farm = Farm::create([
            'farm_code' => 'FARM-PH1-HISTORY', 'farmer_id' => $first->id, 'farm_type_id' => $type->id,
            'name' => 'History Farm', 'region' => 'Region A', 'district' => 'District A', 'bird_count' => 10,
        ]);
        $farm->farmers()->attach($first->id, ['ownership_type' => 'owner', 'is_primary' => true, 'effective_from' => now()->toDateString()]);
        \App\Models\FarmLocation::create(['farm_id' => $farm->id, 'location_type' => 'primary', 'region' => 'Region A', 'district' => 'District A', 'is_primary' => true]);

        $this->actingAs($admin)->put(route('farms.update', $farm), [
            'farmer_id' => $second->id, 'farm_type_id' => $type->id, 'name' => 'History Farm',
            'region' => 'Region B', 'district' => 'District B', 'bird_count' => 12,
        ])->assertRedirect(route('farms.index'));

        $this->assertSame($second->id, $farm->fresh()->farmer_id);
        $this->assertNotNull($farm->fresh()->farmers()->where('farmers.id', $first->id)->first()->pivot->effective_to);
        $this->assertSame(2, $farm->fresh()->locations()->count());
        $this->assertSame('Region B', $farm->fresh()->locations()->where('is_primary', true)->first()->region);
    }

    public function test_animal_update_records_farm_movement_and_retires_replaced_identifier(): void
    {
        $admin = $this->user('system_admin');
        $farmer = Farmer::create(['farmer_code' => 'FRM-PH1-ANI', 'full_name' => 'Animal Owner', 'region' => 'Region A', 'district' => 'District A']);
        $type = FarmType::create(['code' => 'PH1-ANIMAL', 'name' => 'Animal Farm', 'is_active' => true]);
        $farmA = Farm::create(['farm_code' => 'FARM-PH1-ANI-A', 'farmer_id' => $farmer->id, 'farm_type_id' => $type->id, 'name' => 'Farm A', 'region' => 'Region A', 'district' => 'District A', 'bird_count' => 0]);
        $farmB = Farm::create(['farm_code' => 'FARM-PH1-ANI-B', 'farmer_id' => $farmer->id, 'farm_type_id' => $type->id, 'name' => 'Farm B', 'region' => 'Region A', 'district' => 'District A', 'bird_count' => 0]);
        $animal = \App\Models\Animal::create([
            'animal_code' => 'ANI-PH1-EDIT', 'farm_id' => $farmA->id, 'farmer_id' => $farmer->id,
            'species' => 'Cattle', 'external_identifier' => 'TAG-OLD',
        ]);
        \App\Models\AnimalIdentification::create([
            'animal_id' => $animal->id, 'identification_type' => 'ear_tag',
            'identifier_value' => 'TAG-OLD', 'status' => 'active', 'issued_at' => now()->toDateString(),
        ]);

        $this->actingAs($admin)->put(route('animals.update', $animal), [
            'farm_id' => $farmB->id, 'species' => 'Cattle', 'external_identifier' => 'TAG-NEW',
            'identification_method' => 'ear_tag',
        ])->assertRedirect(route('animals.index'));

        $this->assertSame($farmB->id, $animal->fresh()->farm_id);
        $this->assertDatabaseHas('animal_movements', ['animal_id' => $animal->id, 'from_farm_id' => $farmA->id, 'to_farm_id' => $farmB->id]);
        $this->assertDatabaseHas('animal_identifications', ['animal_id' => $animal->id, 'identifier_value' => 'TAG-OLD', 'status' => 'retired']);
        $this->assertDatabaseHas('animal_identifications', ['animal_id' => $animal->id, 'identifier_value' => 'TAG-NEW', 'status' => 'active']);
    }


}
