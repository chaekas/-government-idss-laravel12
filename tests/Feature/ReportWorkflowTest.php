<?php

namespace Tests\Feature;

use App\Models\ScheduledReport;
use App\Models\GeneratedReport;
use App\Models\AdministrativeArea;
use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\User;
use App\Services\ScheduledReportGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Report Test '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_authorized_officer_can_create_a_recurring_report_schedule(): void
    {
        $admin = $this->user('system_admin');
        $recipient = $this->user('mlf_officer');

        $this->actingAs($admin)->post(route('reports.schedule'), [
            'name' => 'Weekly case summary',
            'report_type' => 'cases',
            'frequency' => 'weekly',
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
            'recipient_user_ids' => [$recipient->id],
        ])->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('scheduled_reports', [
            'name' => 'Weekly case summary',
            'report_type' => 'cases',
            'frequency' => 'weekly',
            'created_by' => $admin->id,
        ]);
    }

    public function test_due_scheduled_report_creates_private_csv_and_notifies_recipients(): void
    {
        Storage::fake('local');
        $admin = $this->user('system_admin');
        $recipient = $this->user('mlf_officer');
        $schedule = ScheduledReport::create([
            'name' => 'Daily sample report',
            'report_type' => 'samples',
            'frequency' => 'daily',
            'filters' => [],
            'recipient_user_ids' => [$recipient->id],
            'next_run_at' => now()->subMinute(),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $count = app(ScheduledReportGenerator::class)->runDue();

        $this->assertSame(1, $count);
        $this->assertNotNull($schedule->fresh()->last_run_at);
        $this->assertTrue($schedule->fresh()->next_run_at->isFuture());
        $report = \App\Models\GeneratedReport::where('generated_by', $admin->id)->latest('generated_at')->firstOrFail();
        Storage::disk('local')->assertExists($report->file_path);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $recipient->id,
            'category' => 'reports',
            'title' => 'Scheduled report is ready',
        ]);
    }

    public function test_leadership_can_open_print_report_and_export_endpoints(): void
    {
        $officer = $this->user('regional_veterinary_officer');
        $this->actingAs($officer);

        $this->get(route('reports.print'))->assertOk()->assertSee('Print / Save as PDF');
        $this->get(route('reports.results'))->assertOk();
        $this->get(route('reports.outbreaks'))->assertOk();
    }

    public function test_regional_report_schedule_cannot_be_shared_outside_the_assigned_geographic_scope(): void
    {
        $owner = $this->user('regional_veterinary_officer');
        $recipient = $this->user('regional_veterinary_officer');
        $regionA = AdministrativeArea::create(['code' => 'REPORT-REG-A', 'name' => 'Report Region A', 'area_type' => 'region', 'is_active' => true]);
        $regionB = AdministrativeArea::create(['code' => 'REPORT-REG-B', 'name' => 'Report Region B', 'area_type' => 'region', 'is_active' => true]);
        $owner->administrativeAreas()->attach($regionA->id, ['effective_from' => now()->toDateString()]);
        $recipient->administrativeAreas()->attach($regionB->id, ['effective_from' => now()->toDateString()]);

        $this->actingAs($owner)->post(route('reports.schedule'), [
            'name' => 'Restricted regional report',
            'report_type' => 'cases',
            'frequency' => 'weekly',
            'recipient_user_ids' => [$recipient->id],
        ])->assertSessionHasErrors('recipient_user_ids');

        $this->assertDatabaseMissing('scheduled_reports', ['name' => 'Restricted regional report']);
    }

    public function test_scheduled_case_report_contains_only_records_in_the_creators_geographic_scope(): void
    {
        Storage::fake('local');
        $owner = $this->user('regional_veterinary_officer');
        $regionA = AdministrativeArea::create(['code' => 'REPORT-SCOPE-A', 'name' => 'Scope Region A', 'area_type' => 'region', 'is_active' => true]);
        $districtA = AdministrativeArea::create(['parent_id' => $regionA->id, 'code' => 'REPORT-DIST-A', 'name' => 'Scope District A', 'area_type' => 'district', 'is_active' => true]);
        $regionB = AdministrativeArea::create(['code' => 'REPORT-SCOPE-B', 'name' => 'Scope Region B', 'area_type' => 'region', 'is_active' => true]);
        $districtB = AdministrativeArea::create(['parent_id' => $regionB->id, 'code' => 'REPORT-DIST-B', 'name' => 'Scope District B', 'area_type' => 'district', 'is_active' => true]);
        $owner->administrativeAreas()->attach($regionA->id, ['effective_from' => now()->toDateString()]);

        $farmer = Farmer::create(['farmer_code' => 'FRM-REPORT-SCOPE', 'full_name' => 'Report Scope Farmer', 'region' => 'Scope Region A', 'district' => 'Scope District A']);
        $farmA = Farm::create(['farm_code' => 'FARM-REPORT-A', 'farmer_id' => $farmer->id, 'name' => 'Report Farm A', 'region' => 'Scope Region A', 'district' => 'Scope District A', 'administrative_area_id' => $districtA->id, 'bird_count' => 5]);
        $farmB = Farm::create(['farm_code' => 'FARM-REPORT-B', 'farmer_id' => $farmer->id, 'name' => 'Report Farm B', 'region' => 'Scope Region B', 'district' => 'Scope District B', 'administrative_area_id' => $districtB->id, 'bird_count' => 5]);
        foreach ([[$farmA, 'CASE-REPORT-IN'], [$farmB, 'CASE-REPORT-OUT']] as [$farm, $code]) {
            DiseaseCase::create([
                'case_code' => $code, 'farm_id' => $farm->id, 'reported_by' => $owner->id,
                'disease_suspected' => 'Test disease', 'date_observed' => now()->toDateString(),
                'symptoms' => 'Test signs', 'birds_affected' => 2, 'birds_dead' => 0,
                'severity' => 'low', 'status' => 'reported', 'administrative_area_id' => $farm->administrative_area_id,
            ]);
        }

        $schedule = ScheduledReport::create([
            'name' => 'Creator-scoped report', 'report_type' => 'cases', 'frequency' => 'weekly',
            'filters' => [], 'recipient_user_ids' => [$owner->id], 'next_run_at' => now()->addWeek(),
            'is_active' => true, 'created_by' => $owner->id,
        ]);
        $report = app(ScheduledReportGenerator::class)->generate($schedule);
        $contents = Storage::disk('local')->get($report->file_path);

        $this->assertStringContainsString('CASE-REPORT-IN', $contents);
        $this->assertStringNotContainsString('CASE-REPORT-OUT', $contents);
    }

    public function test_report_archive_only_shows_schedules_and_files_shared_with_the_signed_in_user(): void
    {
        $owner = $this->user('regional_veterinary_officer');
        $other = $this->user('regional_veterinary_officer');

        ScheduledReport::create([
            'name' => 'Private owner schedule',
            'report_type' => 'cases',
            'frequency' => 'weekly',
            'filters' => [],
            'recipient_user_ids' => [],
            'next_run_at' => now()->addWeek(),
            'is_active' => true,
            'created_by' => $owner->id,
        ]);
        ScheduledReport::create([
            'name' => 'My regional schedule',
            'report_type' => 'cases',
            'frequency' => 'weekly',
            'filters' => [],
            'recipient_user_ids' => [],
            'next_run_at' => now()->addWeek(),
            'is_active' => true,
            'created_by' => $other->id,
        ]);

        GeneratedReport::create([
            'generated_by' => $owner->id,
            'file_path' => 'reports/private-owner.csv',
            'file_format' => 'csv',
            'parameters' => ['report_name' => 'Private owner generated report', 'recipient_user_ids' => []],
            'generated_at' => now(),
        ]);
        GeneratedReport::create([
            'generated_by' => $other->id,
            'file_path' => 'reports/my-regional.csv',
            'file_format' => 'csv',
            'parameters' => ['report_name' => 'My regional generated report', 'recipient_user_ids' => []],
            'generated_at' => now(),
        ]);

        $this->actingAs($other)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('My regional schedule')
            ->assertSee('My regional generated report')
            ->assertDontSee('Private owner schedule')
            ->assertDontSee('Private owner generated report');
    }

    public function test_non_leadership_staff_cannot_open_reports(): void
    {
        $this->actingAs($this->user('field_officer'))
            ->get(route('reports.index'))
            ->assertForbidden();
    }
}
