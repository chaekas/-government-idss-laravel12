@extends('layouts.app')
@section('title','Register farmer')
@section('content')
<div class="heading"><div><small class="kicker">REGISTRY / NEW RECORD</small><h1>Register a farmer</h1><p>Capture the farmer's personal, contact and physical address information.</p></div><a class="button secondary" href="{{ route('farmers.index') }}">← Back</a></div>
<form class="panel form-panel" method="POST" action="{{ route('farmers.store') }}">@csrf
<h2>Personal information</h2><div class="form-grid">
<div class="field full"><label>Full name *</label><input name="full_name" value="{{ old('full_name') }}" required maxlength="160"></div>
<div class="field"><label>Sex</label><input name="sex" value="{{ old('sex') }}" maxlength="30"></div>
<div class="field"><label>Date of birth</label><input type="date" name="date_of_birth" max="{{ now()->subDay()->toDateString() }}" value="{{ old('date_of_birth') }}"></div>
<div class="field"><label>Nationality</label><input name="nationality" value="{{ old('nationality') }}" maxlength="100"></div>
<div class="field"><label>National ID number</label><input name="national_id" value="{{ old('national_id') }}" maxlength="100"></div>
<div class="field"><label>Mobile number *</label><input name="phone" value="{{ old('phone') }}" required maxlength="40"></div>
<div class="field"><label>Email address</label><input type="email" name="email" value="{{ old('email') }}"></div>
<div class="field"><label>Farmer status *</label><select name="status" required>@foreach(['active'=>'Active','inactive'=>'Inactive','deceased'=>'Deceased','suspended'=>'Suspended'] as $value=>$label)<option value="{{ $value }}" @selected(old('status','active')===$value)>{{ $label }}</option>@endforeach</select></div>
</div>
<h2 class="section-title">Physical address</h2><div class="form-grid">
<div class="field"><label>Region *</label><input name="region" value="{{ old('region') }}" required></div>
<div class="field"><label>District *</label><input name="district" value="{{ old('district') }}" required></div>
<div class="field"><label>Ward</label><input name="ward" value="{{ old('ward') }}"></div>
<div class="field"><label>Village</label><input name="village" value="{{ old('village') }}"></div>
<div class="field full"><label>Additional address</label><input name="address" value="{{ old('address') }}"></div>
</div><div class="actions"><a class="button secondary" href="{{ route('farmers.index') }}">Cancel</a><button class="button primary">Save farmer record →</button></div></form>
@endsection
