<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><title>IDSS Surveillance Report</title>
<style>
body{font-family:Arial,sans-serif;color:#1f2937;margin:28px;font-size:12px}h1,h2{color:#153b2e}h1{font-size:22px}h2{font-size:16px;margin-top:26px}.meta{color:#4b5563;margin-bottom:20px}.actions{margin-bottom:20px}button{padding:9px 16px;border:0;background:#176b4a;color:#fff;border-radius:5px;cursor:pointer}table{width:100%;border-collapse:collapse;margin:10px 0 22px}th,td{border:1px solid #d1d5db;padding:6px;text-align:left;vertical-align:top}th{background:#edf4ef}.small{font-size:10px;color:#6b7280}
@media print{body{margin:10mm;font-size:10px}.actions{display:none}h2{page-break-after:avoid}tr{page-break-inside:avoid}thead{display:table-header-group}}
</style>
</head>
<body>
<div class="actions"><button onclick="window.print()">Print / Save as PDF</button></div>
<h1>Ministry of Livestock and Fisheries</h1>
<h2>Integrated Disease Surveillance System — Management Report</h2>
<p class="meta">Generated: {{ now()->format('d M Y H:i') }} · Period: {{ $from ?: 'All dates' }} to {{ $to ?: 'Present' }} · Maximum 500 records per section</p>
<h2>Disease cases ({{ $cases->count() }})</h2>
<table><thead><tr><th>Case ID</th><th>Disease</th><th>Farm / location</th><th>Observed</th><th>Severity</th><th>Status</th><th>Reporter</th></tr></thead><tbody>
@forelse($cases as $case)<tr><td>{{ $case->case_code }}</td><td>{{ $case->disease_suspected }}</td><td>{{ $case->farm?->name }}<br>{{ $case->farm?->district }}, {{ $case->farm?->region }}</td><td>{{ $case->date_observed?->format('d M Y') }}</td><td>{{ ucfirst($case->severity) }}</td><td>{{ str_replace('_',' ',ucfirst($case->status)) }}</td><td>{{ $case->reporter?->name }}</td></tr>@empty<tr><td colspan="7">No cases in the selected period.</td></tr>@endforelse
</tbody></table>
<h2>Sample tracking ({{ $samples->count() }})</h2>
<table><thead><tr><th>Sample ID</th><th>Case</th><th>Type</th><th>Laboratory</th><th>Transport</th><th>Quality</th><th>Testing</th><th>Released</th></tr></thead><tbody>
@forelse($samples as $sample)<tr><td>{{ $sample->sample_code }}</td><td>{{ $sample->diseaseCase?->case_code }}</td><td>{{ $sample->sample_type }}</td><td>{{ $sample->laboratory?->name }}</td><td>{{ $sample->transport_status }}</td><td>{{ $sample->quality_status }}</td><td>{{ $sample->test_status }}</td><td>{{ $sample->released_at?->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="8">No samples in the selected period.</td></tr>@endforelse
</tbody></table>
<h2>Laboratory results ({{ $results->count() }})</h2>
<table><thead><tr><th>Result ID</th><th>Sample</th><th>Laboratory</th><th>Test</th><th>Finding</th><th>Status</th></tr></thead><tbody>
@forelse($results as $result)<tr><td>{{ $result->result_code }}</td><td>{{ $result->sample?->sample_code }}</td><td>{{ $result->laboratory?->name }}</td><td>{{ $result->test_name }}</td><td>{{ $result->qualitative_result ?: ($result->numeric_result !== null ? $result->numeric_result.' '.$result->numeric_unit : $result->observations) }}</td><td>{{ $result->status }}</td></tr>@empty<tr><td colspan="6">No laboratory results in the selected period.</td></tr>@endforelse
</tbody></table>
<h2>Outbreaks ({{ $outbreaks->count() }})</h2>
<table><thead><tr><th>Outbreak ID</th><th>Disease</th><th>Location</th><th>Start date</th><th>Cases</th><th>Deaths</th><th>Severity</th><th>Status</th></tr></thead><tbody>
@forelse($outbreaks as $outbreak)<tr><td>{{ $outbreak->outbreak_code }}</td><td>{{ $outbreak->disease_name }}</td><td>{{ $outbreak->district }}, {{ $outbreak->region }}</td><td>{{ $outbreak->start_date?->format('d M Y') }}</td><td>{{ $outbreak->cases_count }}</td><td>{{ $outbreak->deaths_count }}</td><td>{{ $outbreak->severity }}</td><td>{{ $outbreak->status }}</td></tr>@empty<tr><td colspan="8">No outbreaks in the selected period.</td></tr>@endforelse
</tbody></table>
<p class="small">Confidential government surveillance information. Handle according to Ministry information-security and records-retention policies.</p>
</body></html>
