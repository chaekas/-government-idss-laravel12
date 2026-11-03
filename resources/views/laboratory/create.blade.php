@extends('layouts.app')
@section('title','Record laboratory test')
@section('content')
<div class="heading"><div><small class="kicker">DIAGNOSTICS / TEST ENTRY</small><h1>Record an assigned laboratory test</h1><p>Select a diagnostic test assigned to you. The sample and approved test catalogue are locked to that assignment.</p></div></div>
<form method="POST" action="{{ route('laboratory.store') }}" class="panel form-panel">@csrf
    <h2>Test details</h2>
    @if($assignments->isEmpty())
        <div class="empty"><b>No open diagnostic tests assigned to you</b><p>Ask the laboratory manager to assign an approved catalogue test to a received, quality-accepted sample and start examination.</p></div>
    @else
        <div class="form-grid">
            <div class="field full"><label>Assigned diagnostic test *</label>
                <select name="laboratory_test_assignment_id" required>
                    <option value="">Select assigned test</option>
                    @foreach($assignments as $assignment)
                        <option value="{{ $assignment->id }}">{{ $assignment->sample?->sample_code }} · {{ $assignment->test?->name }} · {{ $assignment->sample?->laboratory?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field full"><label>Approved test method</label><select name="test_method_id"><option value="">Use method noted in technician comments</option>@foreach($methods as $method)<option value="{{ $method->id }}">{{ $method->name }} @if($method->standard_reference)({{ $method->standard_reference }})@endif</option>@endforeach</select></div>
            <div class="field full"><label>Method details (if not selecting a catalogue method)</label><input name="method" maxlength="180"></div>
            <div class="field full"><label>Observations</label><textarea name="observations" rows="3"></textarea></div>
            <div class="field"><label>Qualitative result</label><input name="qualitative_result" placeholder="e.g. positive, negative, inconclusive"></div>
            <div class="field"><label>Numeric result</label><input type="number" step="any" name="numeric_result"></div>
            <div class="field"><label>Unit</label><input name="numeric_unit"></div>
            <div class="field full"><label>Technician comments / control observations</label><textarea name="technician_comments" rows="3"></textarea></div>
        </div>
        <div class="actions"><a class="button secondary" href="{{ route('laboratory.index') }}">Cancel</a><button class="button primary">Submit result for independent review</button></div>
    @endif
</form>
@endsection
