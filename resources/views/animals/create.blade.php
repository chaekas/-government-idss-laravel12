@extends('layouts.app')
@section('title','Register animal')
@section('content')
<div class="heading"><div><small class="kicker">TRACEABILITY / REGISTRATION</small><h1>Register animal</h1><p>An Animal ID will be generated when the record is saved.</p></div></div>
<form method="POST" action="{{ route('animals.store') }}" class="panel form-panel">@csrf<h2>Animal details</h2><div class="form-grid">
<div class="field full"><label for="farm_id">Farm *</label><select id="farm_id" name="farm_id" required><option value="">Select registered farm</option>@foreach($farms as $farm)<option value="{{ $farm->id }}" @selected(old('farm_id')==$farm->id)>{{ $farm->farm_code }} — {{ $farm->name }}</option>@endforeach</select></div>
<div class="field"><label for="species">Species *</label><input id="species" name="species" value="{{ old('species','poultry') }}" required></div>
<div class="field"><label for="breed">Breed</label><input id="breed" name="breed" value="{{ old('breed') }}"></div>
<div class="field"><label for="sex">Sex</label><select id="sex" name="sex"><option value="unknown">Unknown</option><option value="female">Female</option><option value="male">Male</option></select></div>
<div class="field"><label for="date_of_birth">Date of birth / hatch</label><input type="date" id="date_of_birth" name="date_of_birth" max="{{ now()->toDateString() }}" value="{{ old('date_of_birth') }}"></div>
<div class="field"><label for="identification_method">Identification method</label><input id="identification_method" name="identification_method" placeholder="Tag, ring, flock batch, etc."></div>
<div class="field"><label for="external_identifier">External identifier</label><input id="external_identifier" name="external_identifier" value="{{ old('external_identifier') }}"></div>
<div class="field full"><label for="notes">Notes</label><textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea></div>
</div><div class="actions"><a class="button secondary" href="{{ route('animals.index') }}">Cancel</a><button class="button primary">Save animal</button></div></form>
@endsection
