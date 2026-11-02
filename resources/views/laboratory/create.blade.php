@extends('layouts.app')
@section('title','Record laboratory test')
@section('content')
<div class="heading"><div><small class="kicker">DIAGNOSTICS / TEST ENTRY</small><h1>Record laboratory test</h1><p>Only received, quality-accepted samples are available for testing.</p></div></div>
<form method="POST" action="{{ route('laboratory.store') }}" class="panel form-panel">@csrf<h2>Test details</h2><div class="form-grid">
<div class="field full"><label>Accepted sample *</label><select name="sample_id" required><option value="">Select sample</option>@foreach($samples as $sample)<option value="{{ $sample->id }}">{{ $sample->sample_code }} — {{ $sample->sample_type }}</option>@endforeach</select></div>
<div class="field full"><label>Laboratory *</label><select name="laboratory_id" required><option value="">Select laboratory</option>@foreach($laboratories as $lab)<option value="{{ $lab->id }}">{{ $lab->name }}</option>@endforeach</select></div>
<div class="field"><label>Diagnostic test *</label><input name="test_name" required></div><div class="field"><label>Method</label><input name="method"></div>
<div class="field full"><label>Observations</label><textarea name="observations" rows="3"></textarea></div>
<div class="field"><label>Qualitative result</label><input name="qualitative_result" placeholder="e.g. positive, negative, inconclusive"></div><div class="field"><label>Numeric result</label><input type="number" step="any" name="numeric_result"></div><div class="field"><label>Unit</label><input name="numeric_unit"></div>
<div class="field full"><label>Technician comments</label><textarea name="technician_comments" rows="3"></textarea></div>
</div><div class="actions"><a class="button secondary" href="{{ route('laboratory.index') }}">Cancel</a><button class="button primary">Submit for review</button></div></form>
@endsection
