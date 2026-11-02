@extends('layouts.app')
@section('title','Create user')
@section('content')
<div class="heading"><div><small class="kicker">ADMINISTRATION / ACCESS CONTROL</small><h1>Create staff account</h1><p>Only authorized system administrators may create accounts.</p></div></div>
<form method="POST" action="{{ route('users.store') }}" class="panel form-panel">@csrf<h2>Account details</h2><div class="form-grid">
<div class="field"><label>Full name *</label><input name="name" value="{{ old('name') }}" required></div><div class="field"><label>Work email *</label><input type="email" name="email" value="{{ old('email') }}" required></div>
<div class="field full"><label>Role *</label><select name="role" required>@foreach(array_keys(config('idss.roles')) as $role)<option value="{{ $role }}" @selected(old('role','field_officer')===$role)>{{ config('idss.roles.'.$role.'.label', str_replace('_',' ',ucfirst($role))) }}</option>@endforeach</select></div>
<div class="field full" id="farmer-link-field"><label>Linked farmer record (required for Farmer role)</label><select name="farmer_id" id="farmer_id"><option value="">Select farmer</option>@foreach($farmers as $farmer)<option value="{{ $farmer->id }}" @selected((string) old('farmer_id')===(string) $farmer->id)>{{ $farmer->farmer_code }} — {{ $farmer->full_name }}</option>@endforeach</select><small>Only farmer login accounts are linked to a farmer record. Staff accounts do not need a farmer link.</small></div>
<div class="field"><label>Password (min 12 chars, letters and numbers) *</label><input type="password" name="password" minlength="12" required autocomplete="new-password"></div><div class="field"><label>Confirm password *</label><input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></div>
</div><div class="actions"><a class="button secondary" href="{{ route('users.index') }}">Cancel</a><button class="button primary">Create account</button></div></form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.querySelector('select[name="role"]');
    const field = document.getElementById('farmer-link-field');
    const farmer = document.getElementById('farmer_id');
    function toggleFarmerLink() {
        const isFarmer = role.value === 'farmer';
        field.hidden = !isFarmer;
        farmer.required = isFarmer;
        if (!isFarmer) farmer.value = '';
    }
    role.addEventListener('change', toggleFarmerLink);
    toggleFarmerLink();
});
</script>
@endsection
