@extends('layouts.app')
@section('title','Edit disease case')
@section('content')
<div class="heading"><div><small class="kicker">SURVEILLANCE / CASE CORRECTION</small><h1>Edit {{ $case->case_code }}</h1><p>Reports can be corrected only before an investigation begins.</p></div><a class="button secondary" href="{{ route('cases.index') }}">← Back</a></div>
<form class="panel form-panel" method="POST" enctype="multipart/form-data" action="{{ route('cases.update',$case) }}">@csrf @method('PUT')
<div class="form-grid">
<div class="field full"><label>Farm *</label><select name="farm_id" required>@foreach($farms as $farm)<option value="{{ $farm->id }}" @selected(old('farm_id',$case->farm_id)==$farm->id)>{{ $farm->farm_code }} — {{ $farm->name }}</option>@endforeach</select></div>
<div class="field"><label>Suspected disease *</label><input name="disease_suspected" required value="{{ old('disease_suspected',$case->disease_suspected) }}"></div>
<div class="field"><label>Disease classification</label><select name="disease_category_id"><option value="">Not classified</option>@foreach($categories as $item)<option value="{{ $item->id }}" @selected(old('disease_category_id',$case->disease_category_id)==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
<div class="field"><label>Affected species *</label><input name="affected_species" required value="{{ old('affected_species',$case->affected_species) }}"></div>
<div class="field"><label>Individual animal</label><select name="animal_id"><option value="">Farm-level / multiple</option>@foreach($animals as $animal)<option value="{{ $animal->id }}" @selected(old('animal_id',$case->animal_id)==$animal->id)>{{ $animal->animal_code }} — {{ $animal->species }}</option>@endforeach</select></div>
<div class="field"><label>Date observed *</label><input type="date" name="date_observed" max="{{ now()->toDateString() }}" required value="{{ old('date_observed',$case->date_observed?->toDateString()) }}"></div>
<div class="field"><label>Affected *</label><input type="number" min="1" name="birds_affected" required value="{{ old('birds_affected',$case->birds_affected) }}"></div>
<div class="field"><label>Deaths *</label><input type="number" min="0" name="birds_dead" required value="{{ old('birds_dead',$case->birds_dead) }}"></div>
<div class="field"><label>Severity *</label><select name="severity">@foreach(['low','medium','high','critical'] as $s)<option value="{{ $s }}" @selected(old('severity',$case->severity)===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="field full"><label>Observed symptoms *</label><textarea name="symptoms" rows="4" required>{{ old('symptoms',$case->symptoms) }}</textarea></div>
<div class="field full"><label>Symptom catalogue</label><div class="form-grid">@foreach($symptoms as $symptom)<label><input type="checkbox" name="symptom_ids[]" value="{{ $symptom->id }}" @checked(in_array($symptom->id,old('symptom_ids',$case->symptoms->pluck('id')->all())))> {{ $symptom->name }}</label>@endforeach</div></div>
<div class="field"><label>Latitude</label><input type="number" step="any" name="latitude" value="{{ old('latitude',$case->latitude) }}"></div>
<div class="field"><label>Longitude</label><input type="number" step="any" name="longitude" value="{{ old('longitude',$case->longitude) }}"></div>
<div class="field"><label>Administrative area</label><select name="administrative_area_id"><option value="">Select</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(old('administrative_area_id',$case->administrative_area_id)==$area->id)>{{ $area->name }}</option>@endforeach</select></div>
<div class="field"><label>Location description</label><input name="location_description" value="{{ old('location_description',$case->location_description) }}"></div>
<div class="field full"><label>Add supporting files (JPG/PNG/PDF, max 10 MB each)</label><input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf"></div>
</div>
@if($case->attachments->isNotEmpty())<h2 class="section-title">Existing evidence</h2><ul>@foreach($case->attachments as $attachment)<li><a href="{{ route('surveillance.case-attachments.show',$attachment) }}">{{ $attachment->original_name }}</a> — {{ $attachment->uploaded_at?->format('d M Y H:i') }}</li>@endforeach</ul>@endif
<div class="actions"><a class="button secondary" href="{{ route('cases.index') }}">Cancel</a><button class="button primary">Save corrections</button></div></form>
@endsection
