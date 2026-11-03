<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\GeneratedReport;
use App\Models\LaboratoryTestResult;
use App\Models\Outbreak;
use App\Models\Sample;
use App\Models\ScheduledReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
    }

    private function applyDates($query, array $filters)
    {
        if (!empty($filters['from'])) $query->whereDate('created_at', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->whereDate('created_at', '<=', $filters['to']);
        return $query;
    }

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $cases = $this->applyDates(DiseaseCase::query(), $filters);
        $samples = $this->applyDates(Sample::query(), $filters);
        $results = $this->applyDates(LaboratoryTestResult::query(), $filters);
        $outbreaks = $this->applyDates(Outbreak::query(), $filters);

        return view('reports.index', [
            'from' => $filters['from'] ?? '',
            'to' => $filters['to'] ?? '',
            'farmerCount' => Farmer::count(),
            'farmCount' => Farm::count(),
            'caseCount' => (clone $cases)->count(),
            'openCaseCount' => (clone $cases)->whereIn('status', ['reported', 'under_investigation', 'sample_collected', 'laboratory_testing'])->count(),
            'sampleCount' => (clone $samples)->count(),
            'pendingResultCount' => (clone $results)->where('status', 'submitted_for_review')->count(),
            'outbreakCount' => (clone $outbreaks)->whereIn('status', ['suspected', 'active'])->count(),
            'scheduledReports' => ScheduledReport::with('creator')->latest()->paginate(10, ['*'], 'schedules_page'),
            'generatedReports' => GeneratedReport::with('definition')->latest('generated_at')->paginate(10, ['*'], 'generated_page'),
            'recipients' => User::where('is_active', true)->whereIn('role', ['regional_veterinary_officer','mlf_officer','system_admin','super_admin','super-admin','administrator','admin'])->orderBy('name')->get(),
        ]);
    }

    public function cases(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->applyDates(DiseaseCase::with(['farm', 'reporter'])->orderBy('created_at'), $filters);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Case ID', 'Farm ID', 'Farm', 'Region', 'District', 'Suspected disease', 'Date observed', 'Symptoms', 'Birds affected', 'Birds dead', 'Severity', 'Status', 'Reported by', 'Created at']);
            foreach ($query->cursor() as $case) {
                fputcsv($out, [$case->case_code, $case->farm?->farm_code, $case->farm?->name, $case->farm?->region, $case->farm?->district, $case->disease_suspected, $case->date_observed?->toDateString(), $case->symptoms, $case->birds_affected, $case->birds_dead, $case->severity, $case->status, $case->reporter?->name, $case->created_at?->toDateTimeString()]);
            }
            fclose($out);
        }, 'idss-disease-surveillance.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function samples(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->applyDates(Sample::with(['diseaseCase', 'laboratory'])->orderBy('created_at'), $filters);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Sample ID', 'Case ID', 'Sample type', 'Laboratory', 'Collection date', 'Transport status', 'Quality status', 'Test status', 'Created at']);
            foreach ($query->cursor() as $sample) {
                fputcsv($out, [$sample->sample_code, $sample->diseaseCase?->case_code, $sample->sample_type, $sample->laboratory?->name, $sample->collection_date?->toDateString(), $sample->transport_status, $sample->quality_status, $sample->test_status, $sample->created_at?->toDateTimeString()]);
            }
            fclose($out);
        }, 'idss-sample-tracking.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function results(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->applyDates(LaboratoryTestResult::with(['sample', 'laboratory', 'testMethod'])->orderBy('created_at'), $filters);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Result ID','Sample ID','Laboratory','Diagnostic test','Method','Finding','Status','Tested at','Approved at']);
            foreach ($query->cursor() as $result) {
                $finding = $result->qualitative_result ?: ($result->numeric_result !== null ? $result->numeric_result.' '.$result->numeric_unit : $result->observations);
                fputcsv($out, [$result->result_code,$result->sample?->sample_code,$result->laboratory?->name,$result->test_name,$result->testMethod?->name ?: $result->method,$finding,$result->status,$result->tested_at?->toDateTimeString(),$result->approved_at?->toDateTimeString()]);
            }
            fclose($out);
        }, 'idss-laboratory-results.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function outbreaks(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->applyDates(Outbreak::orderBy('created_at'), $filters);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Outbreak ID','Disease','Region','District','Start date','Cases','Deaths','Severity','Status']);
            foreach ($query->cursor() as $outbreak) {
                fputcsv($out, [$outbreak->outbreak_code,$outbreak->disease_name,$outbreak->region,$outbreak->district,$outbreak->start_date?->toDateString(),$outbreak->cases_count,$outbreak->deaths_count,$outbreak->severity,$outbreak->status]);
            }
            fclose($out);
        }, 'idss-outbreak-register.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Request $request)
    {
        $filters = $this->filters($request);
        $cases = $this->applyDates(DiseaseCase::with(['farm','reporter'])->latest(), $filters)->limit(500)->get();
        $samples = $this->applyDates(Sample::with(['diseaseCase','laboratory'])->latest(), $filters)->limit(500)->get();
        $results = $this->applyDates(LaboratoryTestResult::with(['sample','laboratory'])->latest(), $filters)->limit(500)->get();
        $outbreaks = $this->applyDates(Outbreak::latest(), $filters)->limit(500)->get();

        return view('reports.print', [
            'from' => $filters['from'] ?? null, 'to' => $filters['to'] ?? null,
            'cases' => $cases, 'samples' => $samples, 'results' => $results, 'outbreaks' => $outbreaks,
        ]);
    }

    public function schedule(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:180'],
            'report_type' => ['required', 'in:cases,samples,results,outbreaks'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'from' => ['nullable','date'],
            'to' => ['nullable','date','after_or_equal:from'],
            'recipient_user_ids' => ['required','array','min:1'],
            'recipient_user_ids.*' => ['required','integer','distinct','exists:users,id'],
        ]);
        $filters = array_filter(['from' => $data['from'] ?? null, 'to' => $data['to'] ?? null]);
        $next = match ($data['frequency']) {
            'daily' => now()->addDay()->startOfDay(),
            'weekly' => now()->addWeek()->startOfDay(),
            'monthly' => now()->addMonthNoOverflow()->startOfDay(),
        };
        $schedule = ScheduledReport::create([
            'name' => $data['name'],
            'report_type' => $data['report_type'],
            'frequency' => $data['frequency'],
            'filters' => $filters,
            'recipient_user_ids' => $data['recipient_user_ids'],
            'next_run_at' => $next,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);
        AuditLog::create([
            'user_id' => $request->user()->id, 'action' => 'report.schedule_created',
            'entity_type' => 'scheduled_report', 'entity_id' => (string) $schedule->id,
            'ip_address' => $request->ip(), 'description' => 'Scheduled '.$schedule->report_type.' report '.$schedule->name.'.',
            'created_at' => now(),
        ]);

        return redirect()->route('reports.index')->with('status', 'Report schedule created. Scheduled reports are generated by the Laravel scheduler.');
    }

    public function download(Request $request, GeneratedReport $report)
    {
        $recipients = $report->parameters['recipient_user_ids'] ?? [];
        $isAdmin = in_array($request->user()->role, ['system_admin','super_admin','super-admin','administrator','admin'], true);
        abort_unless($isAdmin || (int) $report->generated_by === (int) $request->user()->id || in_array($request->user()->id, $recipients), 403);
        abort_unless(Storage::disk('local')->exists($report->file_path), 404);

        return Storage::disk('local')->download($report->file_path, basename($report->file_path));
    }
}
