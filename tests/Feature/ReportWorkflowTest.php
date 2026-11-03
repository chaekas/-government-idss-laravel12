<?php

namespace Tests\Feature;

use App\Models\ScheduledReport;
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

    public function test_non_leadership_staff_cannot_open_reports(): void
    {
        $this->actingAs($this->user('field_officer'))
            ->get(route('reports.index'))
            ->assertForbidden();
    }
}
