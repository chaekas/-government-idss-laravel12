@extends('layouts.app')
@section('title','Start investigation')
@section('content')
<div class="heading"><div><small class="kicker">FIELD OPERATIONS / INVESTIGATION</small><h1>Start disease investigation</h1><p>Assign a Poultry Field Officer and capture the field epidemiology record for an existing suspected case.</p></div><a class="button secondary" href="{{ route('investigations.index') }}">← Back</a></div>
@if($cases->isEmpty())<div class="notice"><b>!</b><div><strong>No eligible cases awaiting investigation</strong><p>New suspected cases appear here until an investigation is started.</p><a href="{{ route('cases.index') }}">Open case register →</a></div></div>@else
<form method="POST" enctype="multipart/form-data" action="{{ route('investigations.store') }}" class="panel form-panel">@csrf
<h2>Investigation assignment</h2><div class="form-grid">
<div class="field full"><label>Disease case *</label><select name="disease_case_id" required><option value="">Select case</option>@foreach($cases as $case)<option value="{{ $case->id }}" @selected(old('disease_case_id')==$case->id)>{{ $case->case_code }} — {{ $case->disease_suspected }} ({{ $case->farm?->name }})</option>@endforeach</select></div>
<div class="field"><label>Assigned Poultry Field Officer *</label><select name="assigned_to" required><option value="">Select officer</option>@foreach($officers as $officer)<option value="{{ $officer->id }}" @selected(old('assigned_to')==$officer->id)>{{ $officer->name }} ({{ $officer->email }})</option>@endforeach</select></div>
<div class="field"><label>Investigation date *</label><input type="date" name="investigated_at" max="{{ now()->toDateString() }}" required value="{{ old('investigated_at',now()->toDateString()) }}"></div>
<div class="field full"><label>Field observations *</label><textarea name="field_observations" rows="4" required>{{ old('field_observations') }}</textarea></div>
<div class="field full"><label>Epidemiological findings *</label><textarea name="epidemiological_findings" rows="3">{{ old('epidemiological_findings') }}</textarea></div>
<div class="field full"><label>Possible disease source</label><textarea name="possible_source" rows="2">{{ old('possible_source') }}</textarea></div>
<div class="field full"><label>Suspected transmission route</label><textarea name="transmission_route" rows="2">{{ old('transmission_route') }}</textarea></div>
<div class="field full"><label>Biosecurity observations</label><textarea name="biosecurity_observations" rows="2">{{ old('biosecurity_observations') }}</textarea></div>
<div class="field full"><label>Immediate control measures taken</label><textarea name="immediate_control_measures" rows="2">{{ old('immediate_control_measures') }}</textarea></div>
<div class="field full"><label>Recommendations / response actions</label><textarea name="recommended_actions" rows="3">{{ old('recommended_actions') }}</textarea></div>
<div class="field"><label>Investigation latitude</label><input type="number" step="any" min="-90" max="90" name="latitude" value="{{ old('latitude') }}"></div><div class="field"><label>Investigation longitude</label><input type="number" step="any" min="-180" max="180" name="longitude" value="{{ old('longitude') }}"></div>
<div class="field full"><label>Photographs / documents (up to 10, JPG/PNG/PDF/DOC/DOCX, max 15 MB each)</label><input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"></div>
</div><div class="actions"><a class="button secondary" href="{{ route('investigations.index') }}">Cancel</a><button class="button primary">Save and assign investigation →</button></div></form>
@endif
@endsection
