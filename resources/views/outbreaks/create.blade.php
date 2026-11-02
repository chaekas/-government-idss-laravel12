@extends('layouts.app')
@section('title','Record outbreak')
@section('content')
<div class="heading"><div><small class="kicker">RESPONSE / OUTBREAK REPORT</small><h1>Record suspected outbreak</h1><p>Register a suspected cluster and link disease case reports from the same district. This is not a laboratory-confirmed diagnosis.</p></div></div>
<form method="POST" action="{{ route('outbreaks.store') }}" class="panel form-panel">@csrf<h2>Outbreak details</h2><div class="form-grid">
<div class="field"><label>Disease *</label><input name="disease_name" required value="{{ old('disease_name') }}"></div>
<div class="field"><label>Disease classification</label><select name="disease_category_id"><option value="">Not classified</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(old('disease_category_id')==$cat->id)>{{ $cat->name }}</option>@endforeach</select></div>
<div class="field"><label>Start date *</label><input type="date" name="start_date" max="{{ now()->toDateString() }}" value="{{ old('start_date',now()->toDateString()) }}" required></div>
<div class="field"><label>Region *</label><input name="region" required value="{{ old('region') }}"></div><div class="field"><label>District *</label><input name="district" required value="{{ old('district') }}"></div>
<div class="field"><label>Administrative area</label><select name="administrative_area_id"><option value="">Select if available</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(old('administrative_area_id')==$area->id)>{{ $area->name }}</option>@endforeach</select></div>
<div class="field"><label>Cases (aggregate) *</label><input type="number" min="1" name="cases_count" value="{{ old('cases_count',1) }}" required></div><div class="field"><label>Deaths (aggregate) *</label><input type="number" min="0" name="deaths_count" value="{{ old('deaths_count',0) }}" required></div>
<div class="field"><label>Severity *</label><select name="severity">@foreach(['low','medium','high','critical'] as $s)<option value="{{ $s }}" @selected(old('severity','medium')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="field"><label>Latitude</label><input type="number" step="any" min="-90" max="90" name="latitude" value="{{ old('latitude') }}"></div><div class="field"><label>Longitude</label><input type="number" step="any" min="-180" max="180" name="longitude" value="{{ old('longitude') }}"></div>
<div class="field full"><label>Link existing case reports (optional; select only cases from this district and region)</label><select name="case_ids[]" multiple size="6">@foreach($cases as $case)<option value="{{ $case->id }}" @selected(in_array($case->id,old('case_ids',[])))>{{ $case->case_code }} — {{ $case->disease_suspected }} · {{ $case->farm?->district }}, {{ $case->farm?->region }}</option>@endforeach</select><small>If cases are linked, the case and death totals are recalculated from those linked reports.</small></div>
<div class="field full"><label>Response actions / notes</label><textarea name="response_actions" rows="4">{{ old('response_actions') }}</textarea></div>
</div><div class="actions"><a class="button secondary" href="{{ route('outbreaks.index') }}">Cancel</a><button class="button primary">Save suspected outbreak</button></div></form>
@endsection
