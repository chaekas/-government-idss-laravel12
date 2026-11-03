@extends('layouts.app')
@section('title','Reports')
@section('content')
<div class="heading"><div><small class="kicker">ANALYTICS / SURVEILLANCE REPORTING</small><h1>Surveillance reports</h1><p>Review operational totals, print management reports, export registers and schedule recurring reports.</p></div></div>
<section class="panel"><div class="panel-head"><div><h2>Reporting period</h2><p>Filter case, sample, laboratory result and outbreak totals by record creation date.</p></div></div>
<form method="GET" action="{{ route('reports.index') }}" class="form-grid" style="padding:18px"><div class="field"><label>From date</label><input type="date" name="from" value="{{ $from }}"></div><div class="field"><label>To date</label><input type="date" name="to" value="{{ $to }}"></div><div class="field actions"><button class="button primary">Apply filters</button><a class="button secondary" href="{{ route('reports.index') }}">Clear</a></div></form>
</section>
<div class="stats"><article><span>Registered farmers</span><strong>{{ number_format($farmerCount) }}</strong><small>All current records</small></article><article><span>Registered farms</span><strong>{{ number_format($farmCount) }}</strong><small>All current records</small></article><article><span>Cases in period</span><strong>{{ number_format($caseCount) }}</strong><small>{{ number_format($openCaseCount) }} requiring attention</small></article><article><span>Samples in period</span><strong>{{ number_format($sampleCount) }}</strong><small>Collection and custody register</small></article><article><span>Results awaiting review</span><strong>{{ number_format($pendingResultCount) }}</strong><small>Not yet approved</small></article><article><span>Suspected / active outbreaks</span><strong>{{ number_format($outbreakCount) }}</strong><small>All current records</small></article></div>
<section class="panel"><div class="panel-head"><div><h2>Export registers</h2><p>CSV files open in spreadsheet software. Exports contain sensitive surveillance information; store and share them according to Ministry policy.</p></div></div><div class="actions" style="padding:18px"><a class="button primary" href="{{ route('reports.cases', request()->only('from','to')) }}">Cases (Excel-compatible CSV)</a><a class="button secondary" href="{{ route('reports.samples', request()->only('from','to')) }}">Samples (CSV)</a><a class="button secondary" href="{{ route('reports.results', request()->only('from','to')) }}">Laboratory results (CSV)</a><a class="button secondary" href="{{ route('reports.outbreaks', request()->only('from','to')) }}">Outbreaks (CSV)</a><a class="button secondary" href="{{ route('reports.print', request()->only('from','to')) }}" target="_blank">Print / Save as PDF</a><a class="button secondary" href="{{ route('gis.index') }}">Open GIS map</a></div></section>

<section class="panel form-panel">
    <h2>Schedule recurring report</h2>
    <p>Choose the report type, frequency and authorized user accounts that should receive an in-app notification when the CSV report is ready.</p>
    <form method="POST" action="{{ route('reports.schedule') }}" class="form-grid">@csrf
        <div class="field"><label>Schedule name *</label><input name="name" required maxlength="180" placeholder="Weekly regional case report"></div>
        <div class="field"><label>Report type *</label><select name="report_type" required><option value="cases">Disease cases</option><option value="samples">Sample tracking</option><option value="results">Laboratory results</option><option value="outbreaks">Outbreak register</option></select></div>
        <div class="field"><label>Frequency *</label><select name="frequency" required><option value="daily">Daily</option><option value="weekly" selected>Weekly</option><option value="monthly">Monthly</option></select></div>
        <div class="field"><label>From date (optional)</label><input type="date" name="from"></div>
        <div class="field"><label>To date (optional)</label><input type="date" name="to"></div>
        <div class="field full"><label>Notification recipients *</label><div class="form-grid">@foreach($recipients as $recipient)<label class="field"><span><input type="checkbox" name="recipient_user_ids[]" value="{{ $recipient->id }}"> {{ $recipient->name }} ({{ $recipient->email }})</span></label>@endforeach</div></div>
        <div class="field"><button class="button primary">Save report schedule</button></div>
    </form>
    <small>Scheduled generation runs through Laravel's scheduler. In a local Codespace demo, run <code>php artisan schedule:work</code> to keep the scheduler active.</small>
</section>

<section class="panel">
    <h2>Scheduled reports</h2>
    <div class="table-wrap"><table><thead><tr><th>Schedule</th><th>Frequency</th><th>Recipients</th><th>Next run</th><th>Last run</th><th>Status</th></tr></thead><tbody>
    @forelse($scheduledReports as $schedule)<tr><td><b>{{ $schedule->name }}</b><br><small>{{ ucfirst($schedule->report_type) }}</small></td><td>{{ ucfirst($schedule->frequency) }}</td><td>{{ count($schedule->recipient_user_ids ?? []) }} users</td><td>{{ $schedule->next_run_at?->format('d M Y H:i') }}</td><td>{{ $schedule->last_run_at?->format('d M Y H:i') ?: 'Not run yet' }}</td><td>{{ $schedule->is_active ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="6">No recurring report schedules configured.</td></tr>@endforelse
    </tbody></table></div>{{ $scheduledReports->links() }}
</section>

<section class="panel">
    <h2>Generated report archive</h2>
    <div class="table-wrap"><table><thead><tr><th>Generated</th><th>Report</th><th>Format</th><th>Action</th></tr></thead><tbody>
    @forelse($generatedReports as $report)<tr><td>{{ $report->generated_at?->format('d M Y H:i') }}</td><td>{{ $report->parameters['report_name'] ?? $report->definition?->name ?? 'IDSS report' }}</td><td>{{ strtoupper($report->file_format ?: 'CSV') }}</td><td><a class="button secondary" href="{{ route('reports.generated.download',$report) }}">Download</a></td></tr>@empty<tr><td colspan="4">No scheduled reports have been generated yet.</td></tr>@endforelse
    </tbody></table></div>{{ $generatedReports->links() }}
</section>
@endsection
