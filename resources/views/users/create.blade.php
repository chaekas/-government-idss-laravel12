@extends('layouts.app')
@section('title','Create user')
@section('content')
<div class="heading"><div><small class="kicker">ADMINISTRATION / ACCESS CONTROL</small><h1>Create staff account</h1><p>Only authorized system administrators may create accounts.</p></div></div>
<form method="POST" action="{{ route('users.store') }}" class="panel form-panel">@csrf<h2>Account details</h2><div class="form-grid">
<div class="field"><label>Full name *</label><input name="name" value="{{ old('name') }}" required></div><div class="field"><label>Work email *</label><input type="email" name="email" value="{{ old('email') }}" required></div>
<div class="field full"><label>Role *</label><select name="role" required>@foreach(['farmer','field_officer','laboratory_technician','laboratory_manager','regional_veterinary_officer','zonal_laboratory_administrator','mlf_officer','system_admin'] as $role)<option value="{{ $role }}" @selected(old('role','field_officer')===$role)>{{ str_replace('_',' ',ucfirst($role)) }}</option>@endforeach</select></div>
<div class="field full"><label>Linked farmer record (required for Farmer role)</label><select name="farmer_id"><option value="">Select farmer</option>@foreach($farmers as $farmer)<option value="{{ $farmer->id }}" @selected((string) old('farmer_id')===(string) $farmer->id)>{{ $farmer->farmer_code }} — {{ $farmer->full_name }}</option>@endforeach</select><small>Register the farmer first, then link the farmer login account.</small></div>
<div class="field"><label>Password (min 12 chars, letters and numbers) *</label><input type="password" name="password" minlength="12" required autocomplete="new-password"></div><div class="field"><label>Confirm password *</label><input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></div>
</div><div class="actions"><a class="button secondary" href="{{ route('users.index') }}">Cancel</a><button class="button primary">Create account</button></div></form>
@endsection
