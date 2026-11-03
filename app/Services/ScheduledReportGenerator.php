<?php

namespace App\Services;

use App\Models\DiseaseCase;
use App\Models\GeneratedReport;
use App\Models\LaboratoryTestResult;
use App\Models\Outbreak;
use App\Models\Sample;
use App\Models\ScheduledReport;
use App\Models\UserNotification;
use App\Models\User;
use App\Services\UserDataScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScheduledReportGenerator
{
    private ?User $scopeUser = null;
    public function runDue(): int
    {
        $count = 0;
        ScheduledReport::where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->get()
            ->each(function (ScheduledReport $schedule) use (&$count) {
                $this->generate($schedule);
                $count++;
            });

        return $count;
    }

    public function generate(ScheduledReport $schedule): GeneratedReport
    {
        $this->scopeUser = $schedule->creator;
        if (!$this->scopeUser) { throw new \RuntimeException('Scheduled report creator no longer exists; refusing to generate an unscoped report.'); }
        $filters = $schedule->filters ?? [];
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");

        match ($schedule->report_type) {
            'cases' => $this->writeCases($stream, $filters),
            'samples' => $this->writeSamples($stream, $filters),
            'results' => $this->writeResults($stream, $filters),
            'outbreaks' => $this->writeOutbreaks($stream, $filters),
        };

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        $path = 'reports/scheduled/'.$schedule->id.'/'.now()->format('Ymd-His').'-'.Str::lower(Str::random(5)).'.csv';
        Storage::disk('local')->put($path, $contents);

        $report = GeneratedReport::create([
            'report_definition_id' => null,
            'generated_by' => $schedule->created_by,
            'file_path' => $path,
            'file_format' => 'csv',
            'parameters' => [
                'scheduled_report_id' => $schedule->id,
                'report_name' => $schedule->name,
                'recipient_user_ids' => $schedule->recipient_user_ids,
                'filters' => $filters,
            ],
            'generated_at' => now(),
        ]);

        foreach ($schedule->recipient_user_ids ?? [] as $userId) {
            UserNotification::create([
                'user_id' => $userId,
                'title' => 'Scheduled report is ready',
                'message' => 'The scheduled report "'.$schedule->name.'" has been generated. Open Reports & exports to download it.',
                'category' => 'reports',
            ]);
        }

        $next = match ($schedule->frequency) {
            'daily' => now()->addDay()->startOfDay(),
            'weekly' => now()->addWeek()->startOfDay(),
            'monthly' => now()->addMonthNoOverflow()->startOfDay(),
        };
        $schedule->update(['last_run_at' => now(), 'next_run_at' => $next]);

        return $report;
    }

    private function applyDates($query, array $filters)
    {
        if (!empty($filters['from'])) $query->whereDate('created_at', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->whereDate('created_at', '<=', $filters['to']);
        return $query;
    }

    private function writeCases($stream, array $filters): void
    {
        fputcsv($stream, ['Case ID','Farm ID','Farm','Region','District','Suspected disease','Date observed','Severity','Status','Reported at']);
        $query = $this->applyDates(app(UserDataScope::class)->cases(DiseaseCase::with('farm')->orderBy('created_at'), $this->scopeUser), $filters);
        foreach ($query->cursor() as $case) {
            fputcsv($stream, [$case->case_code,$case->farm?->farm_code,$case->farm?->name,$case->farm?->region,$case->farm?->district,$case->disease_suspected,$case->date_observed?->toDateString(),$case->severity,$case->status,$case->created_at?->toDateTimeString()]);
        }
    }

    private function writeSamples($stream, array $filters): void
    {
        fputcsv($stream, ['Sample ID','Case ID','Sample type','Laboratory','Collection date','Transport status','Quality status','Test status','Released at']);
        $query = $this->applyDates(app(UserDataScope::class)->samples(Sample::with(['diseaseCase','laboratory'])->orderBy('created_at'), $this->scopeUser), $filters);
        foreach ($query->cursor() as $sample) {
            fputcsv($stream, [$sample->sample_code,$sample->diseaseCase?->case_code,$sample->sample_type,$sample->laboratory?->name,$sample->collection_date?->toDateString(),$sample->transport_status,$sample->quality_status,$sample->test_status,$sample->released_at?->toDateTimeString()]);
        }
    }

    private function writeResults($stream, array $filters): void
    {
        fputcsv($stream, ['Result ID','Sample ID','Laboratory','Diagnostic test','Method','Finding','Status','Tested at','Reviewed at']);
        $query = $this->applyDates(app(UserDataScope::class)->laboratoryResults(LaboratoryTestResult::with(['sample','laboratory','testMethod'])->orderBy('created_at'), $this->scopeUser), $filters);
        foreach ($query->cursor() as $result) {
            $finding = $result->qualitative_result ?: ($result->numeric_result !== null ? $result->numeric_result.' '.$result->numeric_unit : $result->observations);
            fputcsv($stream, [$result->result_code,$result->sample?->sample_code,$result->laboratory?->name,$result->test_name,$result->testMethod?->name ?: $result->method,$finding,$result->status,$result->tested_at?->toDateTimeString(),$result->approved_at?->toDateTimeString()]);
        }
    }

    private function writeOutbreaks($stream, array $filters): void
    {
        fputcsv($stream, ['Outbreak ID','Disease','Region','District','Start date','Cases','Deaths','Severity','Status']);
        $query = $this->applyDates(app(UserDataScope::class)->outbreaks(Outbreak::orderBy('created_at'), $this->scopeUser), $filters);
        foreach ($query->cursor() as $outbreak) {
            fputcsv($stream, [$outbreak->outbreak_code,$outbreak->disease_name,$outbreak->region,$outbreak->district,$outbreak->start_date?->toDateString(),$outbreak->cases_count,$outbreak->deaths_count,$outbreak->severity,$outbreak->status]);
        }
    }
}
