<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Investigation report {{ $investigation->investigation_code }}</title><meta name="viewport" content="width=device-width, initial-scale=1"><style>
body{font:14px/1.55 Arial,sans-serif;color:#1b2a22;max-width:900px;margin:32px auto;padding:0 24px}h1,h2{color:#174c35}h1{margin-bottom:4px}.muted{color:#64756b}.section{border-top:1px solid #ccd8d0;padding:14px 0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.label{font-size:11px;text-transform:uppercase;color:#607469}.value{font-weight:600}.toolbar{display:flex;justify-content:space-between;margin-bottom:20px}.toolbar a,.toolbar button{padding:9px 14px;border:1px solid #9aada0;background:white;border-radius:6px;text-decoration:none;color:#174c35}li{margin:5px 0}@media print{.toolbar{display:none}body{margin:0;max-width:none}.section{break-inside:avoid}}
</style></head><body>
<div class="toolbar"><a href="{{ route('investigations.index') }}">← Back to investigations</a><button onclick="window.print()">Print / Save PDF</button></div>
<h1>Integrated Disease Surveillance System</h1><h2>Field Disease Investigation Report</h2><p class="muted">Investigation ID: {{ $investigation->investigation_code }} · Generated: {{ now()->format('d M Y H:i') }}</p>
<div class="section"><h2>1. Case and farm information</h2><div class="grid">
<div><div class="label">Case ID</div><div class="value">{{ $investigation->diseaseCase?->case_code }}</div></div>
<div><div class="label">Suspected disease</div><div class="value">{{ $investigation->diseaseCase?->disease_suspected }}</div></div>
<div><div class="label">Farm</div><div class="value">{{ $investigation->diseaseCase?->farm?->name }}</div></div>
<div><div class="label">Location</div><div class="value">{{ $investigation->diseaseCase?->farm?->district }}, {{ $investigation->diseaseCase?->farm?->region }}</div></div>
<div><div class="label">Date first observed</div><div class="value">{{ $investigation->diseaseCase?->date_observed?->format('d M Y') }}</div></div>
<div><div class="label">Investigation date</div><div class="value">{{ $investigation->investigated_at?->format('d M Y') }}</div></div>
<div><div class="label">Assigned officer</div><div class="value">{{ $investigation->assignee?->name }}</div></div>
<div><div class="label">Investigation status</div><div class="value">{{ ucfirst(str_replace('_',' ',$investigation->status)) }}</div></div>
</div></div>
<div class="section"><h2>2. Field observations</h2><p>{{ $investigation->field_observations }}</p></div>
<div class="section"><h2>3. Epidemiological findings</h2><p>{{ $investigation->epidemiological_findings ?: 'Not recorded' }}</p></div>
<div class="section"><h2>4. Possible disease source</h2><p>{{ $investigation->possible_source ?: 'Not recorded' }}</p></div>
<div class="section"><h2>5. Suspected transmission route</h2><p>{{ $investigation->transmission_route ?: 'Not recorded' }}</p></div>
<div class="section"><h2>6. Biosecurity observations</h2><p>{{ $investigation->biosecurity_observations ?: 'Not recorded' }}</p></div>
<div class="section"><h2>7. Immediate control measures</h2><p>{{ $investigation->immediate_control_measures ?: 'Not recorded' }}</p></div>
<div class="section"><h2>8. Recommendations</h2><p>{{ $investigation->recommended_actions ?: 'Not recorded' }}</p></div>
<div class="section"><h2>9. Supporting evidence</h2><ul>@forelse($investigation->attachments as $attachment)<li>{{ $attachment->original_name }} ({{ $attachment->mime_type }}, {{ number_format($attachment->file_size/1024,1) }} KB)</li>@empty<li>No supporting files attached</li>@endforelse</ul></div>
<div class="section"><h2>10. Approval</h2><div class="grid"><div><div class="label">Approved by</div><div class="value">{{ $investigation->approver?->name ?? 'Not approved' }}</div></div><div><div class="label">Approval date</div><div class="value">{{ $investigation->approved_at?->format('d M Y H:i') ?? 'Pending' }}</div></div></div></div>
<div class="section"><h2>11. Investigation history</h2><ul>@foreach($investigation->history as $entry)<li>{{ $entry->changed_at?->format('d M Y H:i') }} — {{ ucfirst($entry->event_type) }} by {{ $entry->changedBy?->name ?? 'System' }}. {{ $entry->notes }}</li>@endforeach</ul></div>
<p class="muted">This report reflects information recorded in IDSS. Suspected disease events are not laboratory-confirmed diagnoses unless linked to an approved laboratory result.</p>
</body></html>
