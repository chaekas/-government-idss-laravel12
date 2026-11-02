@extends('layouts.app')
@section('title','Register sample')
@section('content')
<div class="heading"><div><small class="kicker">LABORATORY / SAMPLE COLLECTION</small><h1>Register sample</h1><p>A unique Sample ID and barcode value will be generated.</p></div></div>
<form method="POST" action="{{ route('samples.store') }}" class="panel form-panel">@csrf<h2>Collection details</h2><div class="form-grid">
<div class="field full"><label>Disease case *</label><select name="disease_case_id" required><option value="">Select case</option>@foreach($cases as $case)<option value="{{ $case->id }}">{{ $case->case_code }} — {{ $case->disease_suspected }} ({{ $case->farm?->name }})</option>@endforeach</select></div>
<div class="field"><label>Designated laboratory *</label><select name="laboratory_id" required><option value="">Select registered laboratory</option>@foreach($laboratories as $lab)<option value="{{ $lab->id }}">{{ $lab->name }} — {{ $lab->level }}</option>@endforeach</select></div>
<div class="field"><label>Collection date *</label><input type="date" name="collection_date" max="{{ now()->toDateString() }}" value="{{ old('collection_date',now()->toDateString()) }}" required></div>
<div class="field full"><label>Sample type *</label><input name="sample_type" placeholder="e.g. swab, blood, tissue" required value="{{ old('sample_type') }}"></div>
<div class="field full"><label>Collection notes</label><textarea name="collection_notes" rows="3">{{ old('collection_notes') }}</textarea></div>
</div><div class="actions"><a class="button secondary" href="{{ route('samples.index') }}">Cancel</a><button class="button primary">Register sample</button></div></form>
@endsection
