@extends('layouts.app')
@section('title','Create staff user')
@section('content')
<div class="heading"><div><small class="kicker">SUPER ADMINISTRATION / ACCESS CONTROL</small><h1>Create staff user</h1><p>Create an account for a field officer, laboratory user, regional officer or Ministry staff member. Farmer accounts are self-registered.</p></div></div>
<form method="POST" action="{{ route('users.store') }}" class="panel form-panel">@csrf<h2>Staff account details</h2><div class="form-grid">
<div class="field"><label>Full name *</label><input name="name" value="{{ old('name') }}" required></div><div class="field"><label>Email address *</label><input type="email" name="email" value="{{ old('email') }}" required></div>
<div class="field full"><label>Role *</label><select name="role" required>@foreach($roles as $role)<option value="{{ $role }}" @selected(old('role','field_officer')===$role)>{{ config('idss.roles.'.$role.'.label') }}</option>@endforeach</select></div>
<div class="field"><label>Initial password (min 12 characters, letters and numbers) *</label><input type="password" name="password" minlength="12" required autocomplete="new-password"></div><div class="field"><label>Confirm password *</label><input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></div>
</div><div class="actions"><a class="button secondary" href="{{ route('users.index') }}">Cancel</a><button class="button primary">Create staff account</button></div></form>
@endsection
