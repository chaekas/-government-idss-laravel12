@extends('layouts.app')
@section('title','Report disease case')
@section('content')
<div class="heading"><div><small class="kicker">SURVEILLANCE / FIELD REPORT</small><h1>Report a suspected disease case</h1><p>Record the event, affected poultry, clinical signs and outbreak location. Submission does not confirm a diagnosis.</p></div><a class="button secondary" href="{{ route('cases.index') }}">← Back</a></div>
@if($farms->isEmpty())<div class="notice"><b>!</b><div><strong>A registered farm is required</strong><p>Register the farm or ask the responsible field officer to link it to your farmer account.</p></div></div>@else
<form class="panel form-panel" method="POST" enctype="multipart/form-data" action="{{ route('cases.store') }}">@csrf
<h2>Incident and farm details</h2><div class="form-grid">
<div class="field full"><label>Farm *</label><select name="farm_id" required><option value="">Select farm</option>@foreach($farms as $farm)<option value="{{ $farm->id }}" @selected(old('farm_id')==$farm->id)>{{ $farm->farm_code }} — {{ $farm->name }} ({{ $farm->district }}, {{ $farm->region }})</option>@endforeach</select></div>
<div class="field"><label>Suspected disease *</label><input name="disease_suspected" required maxlength="160" value="{{ old('disease_suspected') }}"></div>
<div class="field"><label>Disease classification</label><select name="disease_category_id"><option value="">Not classified / unknown</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('disease_category_id')==$category->id)>{{ $category->name }}</option>@endforeach</select></div>
<div class="field"><label>Affected species *</label><input name="affected_species" required maxlength="120" value="{{ old('affected_species','Poultry') }}"></div>
<div class="field"><label>Individual animal (if identified)</label><select name="animal_id"><option value="">Farm-level / multiple animals</option>@foreach($animals as $animal)<option value="{{ $animal->id }}" @selected(old('animal_id')==$animal->id)>{{ $animal->animal_code }} — {{ $animal->species }} {{ $animal->external_identifier ? '('.$animal->external_identifier.')' : '' }}</option>@endforeach</select></div>
<div class="field"><label>Date signs first observed *</label><input type="date" name="date_observed" max="{{ now()->toDateString() }}" required value="{{ old('date_observed') }}"></div>
<div class="field"><label>Number affected *</label><input type="number" min="1" name="birds_affected" required value="{{ old('birds_affected',1) }}"></div>
<div class="field"><label>Number dead *</label><input type="number" min="0" name="birds_dead" required value="{{ old('birds_dead',0) }}"></div>
<div class="field"><label>Initial severity *</label><select name="severity" required>@foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $value=>$label)<option value="{{ $value }}" @selected(old('severity','medium')===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field full"><label>Clinical signs and symptoms observed *</label><textarea name="symptoms" rows="4" required maxlength="5000">{{ old('symptoms') }}</textarea></div>
<div class="field full"><label>Symptoms from approved catalogue</label><div class="form-grid">@foreach($symptoms as $symptom)<label><input type="checkbox" name="symptom_ids[]" value="{{ $symptom->id }}" @checked(in_array($symptom->id,old('symptom_ids',[])))> {{ $symptom->name }}</label>@endforeach</div></div>
</div>
<h2 class="section-title">Outbreak location</h2><div class="form-grid">
<div class="field"><label>Latitude</label><input type="number" step="any" min="-90" max="90" name="latitude" value="{{ old('latitude') }}"></div>
<div class="field"><label>Longitude</label><input type="number" step="any" min="-180" max="180" name="longitude" value="{{ old('longitude') }}"></div>
<div class="field"><label>Administrative area</label><select name="administrative_area_id"><option value="">Select if available</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(old('administrative_area_id')==$area->id)>{{ $area->name }} ({{ $area->area_type }})</option>@endforeach</select></div>
<div class="field"><label>Location description</label><input name="location_description" maxlength="255" value="{{ old('location_description') }}" placeholder="Village, ward, landmark"></div>
<div class="field full"><label>Photographs / supporting evidence (up to 5 files, JPG/PNG/PDF, max 10 MB each)</label><input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf"></div>
</div>
<div class="actions"><a class="button secondary" href="{{ route('cases.index') }}">Cancel</a><button class="button primary">Submit disease report →</button></div></form>
@endif
@endsection
