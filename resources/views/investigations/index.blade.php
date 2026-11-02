@extends('layouts.app')
@section('title','Investigations')
@section('content')
<div class="heading"><div><small class="kicker">FIELD OPERATIONS / INVESTIGATION</small><h1>Disease investigations</h1><p>Manage officer assignments, epidemiological findings, control measures, supporting evidence and approvals.</p></div><a class="button primary" href="{{ route('investigations.create') }}">＋ Start investigation</a></div>
<section class="panel"><div class="panel-head"><div><h2>Investigation register</h2><p>{{ $investigations->total() }} records</p></div></div>
@if($investigations->isEmpty())<div class="empty"><b>No investigations recorded</b><p>Start an investigation from an existing suspected disease case.</p></div>@else
@foreach($investigations as $inv)
<article class="panel" style="margin:14px 0;padding:16px">
<div class="panel-head"><div><h2>{{ $inv->investigation_code }} · {{ $inv->diseaseCase?->case_code }}</h2><p>{{ $inv->diseaseCase?->disease_suspected }} · {{ $inv->diseaseCase?->farm?->name }}</p></div><div><b>{{ str_replace('_',' ',ucfirst($inv->status)) }}</b><small>{{ $inv->investigated_at?->format('d M Y') }}</small></div></div>
<div class="form-grid"><div><small>Assigned officer</small><b>{{ $inv->assignee?->name }}</b></div><div><small>Started by</small><b>{{ \App\Models\User::find($inv->started_by)?->name ?? '—' }}</b></div><div><small>Approval</small><b>{{ $inv->approved_at?->format('d M Y H:i') ?? 'Pending approval' }}</b></div></div>
<p><a href="{{ route('investigations.report',$inv) }}" target="_blank">View / print investigation report →</a></p>
<details style="margin-top:12px"><summary>View investigation record, evidence and history</summary>
<div class="form-grid" style="padding:12px">
<div class="field full"><b>Field observations</b><p>{{ $inv->field_observations }}</p></div>
<div class="field full"><b>Epidemiological findings</b><p>{{ $inv->epidemiological_findings ?: 'Not recorded' }}</p></div>
<div class="field"><b>Possible source</b><p>{{ $inv->possible_source ?: 'Not recorded' }}</p></div>
<div class="field"><b>Transmission route</b><p>{{ $inv->transmission_route ?: 'Not recorded' }}</p></div>
<div class="field"><b>Biosecurity observations</b><p>{{ $inv->biosecurity_observations ?: 'Not recorded' }}</p></div>
<div class="field"><b>Immediate control measures</b><p>{{ $inv->immediate_control_measures ?: 'Not recorded' }}</p></div>
<div class="field full"><b>Recommendations</b><p>{{ $inv->recommended_actions ?: 'Not recorded' }}</p></div>
<div class="field full"><b>Supporting evidence</b>@forelse($inv->attachments as $attachment)<p><a href="{{ route('surveillance.investigation-attachments.show',$attachment) }}">{{ $attachment->original_name }}</a> · {{ number_format($attachment->file_size/1024,1) }} KB</p>@empty<p>No attachments</p>@endforelse</div>
<div class="field full"><b>Investigation history</b><ul>@foreach($inv->history as $history)<li>{{ $history->changed_at?->format('d M Y H:i') }} · {{ ucfirst($history->event_type) }} by {{ $history->changedBy?->name ?? 'System' }} — {{ $history->notes }}</li>@endforeach</ul></div>
</div></details>
@if(!$inv->approved_at)
<details style="margin-top:12px"><summary>Update investigation</summary>
<form method="POST" enctype="multipart/form-data" action="{{ route('investigations.update',$inv) }}" style="padding:12px">@csrf @method('PUT')
<div class="form-grid">
<div class="field"><label>Assigned officer *</label><select name="assigned_to" required>@foreach(\App\Models\User::where('is_active',true)->whereIn('role',['field_officer','regional_veterinary_officer'])->orderBy('name')->get() as $officer)<option value="{{ $officer->id }}" @selected($inv->assigned_to==$officer->id)>{{ $officer->name }}</option>@endforeach</select></div>
<div class="field"><label>Investigation date *</label><input type="date" name="investigated_at" max="{{ now()->toDateString() }}" required value="{{ $inv->investigated_at?->toDateString() }}"></div>
<div class="field"><label>Status *</label><select name="status">@foreach(['under_investigation'=>'Under investigation','sample_collected'=>'Sample collected','completed'=>'Completed — ready for approval'] as $value=>$label)<option value="{{ $value }}" @selected($inv->status===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field full"><label>Field observations *</label><textarea name="field_observations" rows="3" required>{{ $inv->field_observations }}</textarea></div>
<div class="field full"><label>Epidemiological findings</label><textarea name="epidemiological_findings" rows="2">{{ $inv->epidemiological_findings }}</textarea></div>
<div class="field full"><label>Possible source</label><textarea name="possible_source" rows="2">{{ $inv->possible_source }}</textarea></div>
<div class="field full"><label>Suspected transmission route</label><textarea name="transmission_route" rows="2">{{ $inv->transmission_route }}</textarea></div>
<div class="field full"><label>Biosecurity observations</label><textarea name="biosecurity_observations" rows="2">{{ $inv->biosecurity_observations }}</textarea></div>
<div class="field full"><label>Immediate control measures</label><textarea name="immediate_control_measures" rows="2">{{ $inv->immediate_control_measures }}</textarea></div>
<div class="field full"><label>Recommendations (required to complete)</label><textarea name="recommended_actions" rows="3">{{ $inv->recommended_actions }}</textarea></div>
<div class="field"><label>Latitude</label><input type="number" step="any" name="latitude" value="{{ $inv->latitude }}"></div><div class="field"><label>Longitude</label><input type="number" step="any" name="longitude" value="{{ $inv->longitude }}"></div>
<div class="field full"><label>Add photographs / documents</label><input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"></div>
</div><button class="button primary">Save investigation update</button></form></details>
@endif
@if($inv->status==='completed' && !$inv->approved_at && in_array(auth()->user()->role,['regional_veterinary_officer','mlf_officer','system_admin','administrator','admin','super_admin','super-admin'],true))
<details style="margin-top:12px"><summary>Approve investigation report</summary><form method="POST" action="{{ route('investigations.approve',$inv) }}" style="padding:12px">@csrf<label>Approval comments *</label><textarea name="approval_comments" required rows="3"></textarea><button class="button primary">Approve and lock report</button></form></details>
@endif
@if($inv->approved_at)<p><b>Approved by:</b> {{ $inv->approver?->name }} · {{ $inv->approved_at->format('d M Y H:i') }}. This report is locked.</p>@endif
</article>
@endforeach
<div class="pagination">{{ $investigations->links() }}</div>@endif
</section>
@endsection
