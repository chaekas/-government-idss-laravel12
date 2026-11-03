@extends('layouts.app')
@section('title', isset($farm) ? 'Edit farm' : 'Register farm')
@section('content')
<div class="heading"><div><small class="kicker">REGISTRY / NEW FARM</small><h1>{{ isset($farm) ? 'Edit farm record' : 'Register a farm' }}</h1><p>Record farm ownership, production system, GPS coordinates and administrative location.</p></div><a class="button secondary" href="{{ route('farms.index') }}">← Back</a></div>
@if($farmers->isEmpty())<div class="notice"><b>!</b><div><strong>Register a farmer first</strong><p>A farm must be associated with a registered farmer.</p><a href="{{ route('farmers.create') }}">Register farmer →</a></div></div>
@elseif($farmTypes->isEmpty())<div class="notice"><b>!</b><div><strong>Farm type catalogue is empty</strong><p>Ask a System Administrator to seed or configure the SRS farm type catalogue.</p></div></div>
@else
<form class="panel form-panel" method="POST" action="{{ isset($farm) ? route('farms.update', $farm) : route('farms.store') }}">@csrf @if(isset($farm)) @method('PUT') @endif<h2>Farm information</h2><div class="form-grid">
<div class="field full"><label>Farm owner *</label><select name="farmer_id" required><option value="">Select farmer</option>@foreach($farmers as $farmer)<option value="{{ $farmer->id }}" @selected(old('farmer_id', $farm->farmer_id ?? '')==$farmer->id)>{{ $farmer->full_name }} ({{ $farmer->farmer_code }})</option>@endforeach</select></div>
<div class="field full"><label>Farm name *</label><input name="name" required value="{{ old('name', $farm->name ?? '') }}"></div>
<div class="field"><label>Production system / farm type *</label><select name="farm_type_id" required><option value="">Select farm type</option>@foreach($farmTypes as $type)<option value="{{ $type->id }}" @selected(old('farm_type_id', $farm->farm_type_id ?? '')==$type->id)>{{ $type->name }}</option>@endforeach</select></div>
<div class="field"><label>Poultry type / production detail</label><input name="poultry_type" value="{{ old('poultry_type', $farm->poultry_type ?? '') }}" placeholder="Optional details"></div>
<div class="field"><label>Bird count *</label><input type="number" min="0" name="bird_count" required value="{{ old('bird_count', $farm->bird_count ?? 0) }}"></div>
</div><h2 class="section-title">Farm location</h2><div class="form-grid">
<div class="field full"><label>Official administrative area</label><select name="administrative_area_id"><option value="">Not linked to official boundary</option>@foreach($administrativeAreas as $area)<option value="{{ $area->id }}" @selected(old('administrative_area_id', $farm->administrative_area_id ?? '')==$area->id)>{{ $area->name }} ({{ $area->area_type }})</option>@endforeach</select></div>
<div class="field"><label>Region *</label><input name="region" required value="{{ old('region', $farm->region ?? '') }}"></div><div class="field"><label>District *</label><input name="district" required value="{{ old('district', $farm->district ?? '') }}"></div>
<div class="field"><label>Ward</label><input name="ward" value="{{ old('ward', $farm->ward ?? '') }}"></div><div class="field"><label>Village</label><input name="village" value="{{ old('village', $farm->village ?? '') }}"></div>
<div class="field"><label>Latitude</label><input type="number" step="any" name="latitude" value="{{ old('latitude', $farm->latitude ?? '') }}"></div><div class="field"><label>Longitude</label><input type="number" step="any" name="longitude" value="{{ old('longitude', $farm->longitude ?? '') }}"></div>
<div class="field full"><label>Notes</label><textarea name="notes" rows="3">{{ old('notes', $farm->notes ?? '') }}</textarea></div>
@if($errors->has('latitude') && str_contains($errors->first('latitude'),'already exists'))
<div class="field full"><label><input type="checkbox" name="duplicate_verification_confirmed" value="1" @checked(old('duplicate_verification_confirmed'))> I have checked the existing record and verified that this is a separate farm despite the matching owner and GPS coordinates.</label></div>
@endif
</div><div class="actions"><a class="button secondary" href="{{ route('farms.index') }}">Cancel</a><button class="button primary">{{ isset($farm) ? 'Update farm record' : 'Save farm record →' }}</button></div></form>
@endif
@endsection
