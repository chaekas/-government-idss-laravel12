@extends('layouts.app')
@section('title','Record outbreak')
@section('content')
<div class="heading"><div><small class="kicker">RESPONSE / OUTBREAK REPORT</small><h1>Record suspected outbreak</h1><p>This record is an operational alert, not a substitute for official confirmation.</p></div></div>
<form method="POST" action="{{ route('outbreaks.store') }}" class="panel form-panel">@csrf<h2>Outbreak details</h2><div class="form-grid">
<div class="field"><label>Disease *</label><input name="disease_name" required></div><div class="field"><label>Start date *</label><input type="date" name="start_date" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div>
<div class="field"><label>Region *</label><input name="region" required></div><div class="field"><label>District *</label><input name="district" required></div>
<div class="field"><label>Cases *</label><input type="number" min="1" name="cases_count" required></div><div class="field"><label>Deaths *</label><input type="number" min="0" name="deaths_count" value="0" required></div>
<div class="field"><label>Severity *</label><select name="severity" required><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
<div class="field"><label>Latitude</label><input type="number" step="any" min="-90" max="90" name="latitude"></div><div class="field"><label>Longitude</label><input type="number" step="any" min="-180" max="180" name="longitude"></div>
<div class="field full"><label>Response actions / notes</label><textarea name="response_actions" rows="4"></textarea></div>
</div><div class="actions"><a class="button secondary" href="{{ route('outbreaks.index') }}">Cancel</a><button class="button primary">Save outbreak</button></div></form>
@endsection
