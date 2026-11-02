@extends('layouts.app')
@section('title','Record laboratory test')
@section('content')
<div class="heading"><div><small class="kicker">DIAGNOSTICS / TEST ENTRY</small><h1>Record an assigned laboratory test</h1><p>Only samples received by the laboratory, quality-accepted, and assigned to your account can be tested. Submitted findings go to an independent reviewer.</p></div></div>
<form method="POST" action="{{ route('laboratory.store') }}" class="panel form-panel">@csrf
    <h2>Test details</h2>
    @if($samples->isEmpty())
        <div class="empty"><b>No samples assigned to you</b><p>Ask the laboratory manager or zonal laboratory administrator to assign you a received, quality-accepted sample and laboratory section.</p></div>
    @else
        <div class="form-grid">
            <div class="field full"><label>Sample assigned to you *</label><select name="sample_id" id="sample_id" required><option value="">Select assigned sample</option>@foreach($samples as $sample)<option value="{{ $sample->id }}" data-lab="{{ $sample->laboratory_id }}">{{ $sample->sample_code }} — {{ $sample->sample_type }} · {{ $sample->laboratory?->name }} · {{ $sample->laboratory_section }}</option>@endforeach</select></div>
            <div class="field full"><label>Laboratory *</label><select name="laboratory_id" id="laboratory_id" required><option value="">Select sample first</option>@foreach($laboratories as $lab)<option value="{{ $lab->id }}">{{ $lab->name }}</option>@endforeach</select></div>
            <div class="field"><label>Diagnostic test *</label><input name="test_name" required placeholder="e.g. Newcastle disease PCR"></div>
            <div class="field"><label>Method</label><input name="method" placeholder="Approved laboratory SOP / method"></div>
            <div class="field full"><label>Observations</label><textarea name="observations" rows="3"></textarea></div>
            <div class="field"><label>Qualitative result</label><input name="qualitative_result" placeholder="e.g. positive, negative, inconclusive"></div>
            <div class="field"><label>Numeric result</label><input type="number" step="any" name="numeric_result"></div>
            <div class="field"><label>Unit</label><input name="numeric_unit"></div>
            <div class="field full"><label>Technician comments</label><textarea name="technician_comments" rows="3"></textarea></div>
        </div>
        <div class="actions"><a class="button secondary" href="{{ route('laboratory.index') }}">Cancel</a><button class="button primary">Submit result for review</button></div>
    @endif
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sample = document.getElementById('sample_id');
    const laboratory = document.getElementById('laboratory_id');
    if (!sample || !laboratory) return;
    function syncLaboratory() {
        const selected = sample.options[sample.selectedIndex];
        const labId = selected ? selected.dataset.lab : '';
        if (labId) laboratory.value = labId;
    }
    sample.addEventListener('change', syncLaboratory);
    syncLaboratory();
});
</script>
@endsection
