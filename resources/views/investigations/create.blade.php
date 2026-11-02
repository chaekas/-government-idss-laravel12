@extends('layouts.app')
@section('title','Start investigation')
@section('content')
<div class="heading"><div><small class="kicker">FIELD OPERATIONS / INVESTIGATION</small><h1>Start investigation</h1><p>Link the field investigation to a reported disease case.</p></div></div>
<form method="POST" action="{{ route('investigations.store') }}" class="panel form-panel">@csrf<h2>Investigation record</h2><div class="form-grid">
<div class="field full"><label>Disease case *</label><select name="disease_case_id" required><option value="">Select case</option>@foreach($cases as $case)<option value="{{ $case->id }}">{{ $case->case_code }} — {{ $case->disease_suspected }} ({{ $case->farm?->name }})</option>@endforeach</select></div>
<div class="field full"><label>Assign field officer</label><select name="assigned_to"><option value="">Assign later</option>@foreach($officers as $officer)<option value="{{ $officer->id }}">{{ $officer->name }} ({{ str_replace('_',' ',$officer->role) }})</option>@endforeach</select></div>
<div class="field full"><label>Field observations *</label><textarea name="field_observations" rows="4" required>{{ old('field_observations') }}</textarea></div>
<div class="field full"><label>Epidemiological findings</label><textarea name="epidemiological_findings" rows="3">{{ old('epidemiological_findings') }}</textarea></div>
<div class="field full"><label>Recommended response actions</label><textarea name="recommended_actions" rows="3">{{ old('recommended_actions') }}</textarea></div>
<div class="field"><label>Latitude</label><input type="number" step="any" min="-90" max="90" name="latitude"></div><div class="field"><label>Longitude</label><input type="number" step="any" min="-180" max="180" name="longitude"></div>
</div><div class="actions"><a class="button secondary" href="{{ route('investigations.index') }}">Cancel</a><button class="button primary">Save investigation</button></div></form>
@endsection
