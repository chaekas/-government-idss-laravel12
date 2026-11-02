@extends('layouts.app')
@section('title','Register laboratory')
@section('content')
<div class="heading"><div><small class="kicker">LABORATORY / FACILITY MANAGEMENT</small><h1>Register laboratory</h1><p>Only approved facilities should be registered for operational use.</p></div></div>
<form method="POST" action="{{ route('laboratories.store') }}" class="panel form-panel">@csrf<h2>Facility details</h2><div class="form-grid">
<div class="field full"><label>Laboratory name *</label><input name="name" required value="{{ old('name') }}"></div>
<div class="field"><label>Level *</label><select name="level" required><option value="district">District</option><option value="regional" selected>Regional</option><option value="zonal">Zonal</option><option value="national">National</option><option value="private">Private</option></select></div>
<div class="field"><label>Region</label><input name="region" value="{{ old('region') }}"></div><div class="field"><label>District</label><input name="district" value="{{ old('district') }}"></div><div class="field"><label>Contact email</label><input type="email" name="contact_email" value="{{ old('contact_email') }}"></div><div class="field"><label>Contact phone</label><input name="contact_phone" value="{{ old('contact_phone') }}"></div><div class="field full"><label>Address</label><input name="address" value="{{ old('address') }}"></div>
</div><div class="actions"><a class="button secondary" href="{{ route('laboratories.index') }}">Cancel</a><button class="button primary">Register laboratory</button></div></form>
@endsection
