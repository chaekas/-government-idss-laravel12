<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FarmType;
use App\Models\Farmer;
use App\Models\Farm;
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
            'animal_disease_records', 'laboratory_sections', 'laboratory_tests', 'test_methods',
            'sample_types', 'sample_transportations', 'sample_storage_records',
            'quality_control_records', 'laboratory_equipment',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing expected IDSS table: {$table}");
        }

        $this->assertTrue(Schema::hasColumn('farmers', 'sex'));
        $this->assertTrue(Schema::hasColumn('farmers', 'date_of_birth'));
        $this->assertTrue(Schema::hasColumn('farmers', 'nationality'));
        $this->assertTrue(Schema::hasColumn('farms', 'farm_size'));
        $this->assertTrue(Schema::hasColumn('farms', 'farm_type_id'));
        $this->assertTrue(Schema::hasColumn('animals', 'farmer_id'));
        $this->assertTrue(Schema::hasColumn('animals', 'colour'));
        $this->assertTrue(Schema::hasColumn('disease_cases', 'animal_id'));
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
}
