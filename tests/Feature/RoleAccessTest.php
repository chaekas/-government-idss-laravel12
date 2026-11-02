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

    public function test_field_officer_cannot_open_administrator_user_management(): void
    {
        $officer = User::create([
            'name' => 'Field Officer',
            'email' => 'field.officer@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => 'field_officer',
            'is_active' => true,
        ]);

        $this->actingAs($officer)->get(route('users.index'))->assertForbidden();
    }

    public function test_farmer_cannot_open_staff_farmer_registry(): void
    {
        $farmerRecord = Farmer::create([
            'farmer_code' => 'FRM-TEST-OWN01',
            'full_name' => 'Test Farmer',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
        ]);
        $farmerUser = User::create([
            'name' => 'Farmer User',
            'email' => 'farmer@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => 'farmer',
            'farmer_id' => $farmerRecord->id,
            'is_active' => true,
        ]);

        $this->actingAs($farmerUser)->get(route('farmers.index'))->assertForbidden();
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
        $farmerUser = User::create([
            'name' => 'Owner Farmer Login',
            'email' => 'owner.farmer@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => 'farmer',
            'farmer_id' => $ownFarmer->id,
            'is_active' => true,
        ]);

        $otherReporter = User::create([
            'name' => 'Other Field Officer',
            'email' => 'other.field.officer@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => 'field_officer',
            'is_active' => true,
        ]);

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

        $response = $this->actingAs($farmerUser)->get(route('cases.index'));

        $response->assertOk()
            ->assertSee('CASE-OWN-0001')
            ->assertDontSee('CASE-OTHER-01');
    }
}
