<?php

namespace App\Http\Controllers;

use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\LaboratoryTestResult;
use App\Models\Outbreak;
use App\Models\Sample;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $cases = DiseaseCase::query();
        $samples = Sample::query();
        $results = LaboratoryTestResult::query();

        foreach ([$cases, $samples, $results] as $query) {
            if (!empty($filters['from'])) {
                $query->whereDate('created_at', '>=', $filters['from']);
            }
            if (!empty($filters['to'])) {
                $query->whereDate('created_at', '<=', $filters['to']);
            }
        }

        return view('reports.index', [
            'from' => $filters['from'] ?? '',
            'to' => $filters['to'] ?? '',
            'farmerCount' => Farmer::count(),
            'farmCount' => Farm::count(),
            'caseCount' => (clone $cases)->count(),
            'openCaseCount' => (clone $cases)->whereIn('status', ['reported', 'under_investigation'])->count(),
            'sampleCount' => (clone $samples)->count(),
            'pendingResultCount' => (clone $results)->where('status', 'submitted_for_review')->count(),
            'outbreakCount' => Outbreak::whereIn('status', ['suspected', 'active'])->count(),
        ]);
    }

    public function cases(Request $request): StreamedResponse
    {
        $filters = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $query = DiseaseCase::with(['farm', 'reporter'])->orderBy('created_at');
        if (!empty($filters['from'])) $query->whereDate('created_at', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->whereDate('created_at', '<=', $filters['to']);

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
        $filters = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $query = Sample::with(['diseaseCase', 'laboratory'])->orderBy('created_at');
        if (!empty($filters['from'])) $query->whereDate('created_at', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->whereDate('created_at', '<=', $filters['to']);

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
}
