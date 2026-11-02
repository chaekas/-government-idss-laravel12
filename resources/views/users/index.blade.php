@extends('layouts.app')
@section('title','Users management')
@section('content')
<div class="heading"><div><small class="kicker">SUPER ADMINISTRATION / ACCESS CONTROL</small><h1>Users management</h1><p>Create and manage Ministry, field and laboratory staff accounts. Farmers register themselves from the login page and are not managed here.</p></div><a class="button primary" href="{{ route('users.create') }}">＋ Create staff user</a></div>
<section class="panel"><div class="panel-head"><div><h2>Staff accounts</h2><p>{{ $users->total() }} staff accounts</p></div></div><div class="table-wrap"><table><thead><tr><th>Name / Email</th><th>Role</th><th>Status</th><th>Manage account</th></tr></thead><tbody>
@forelse($users as $user)
<tr><td><b>{{ $user->name }}</b><br>{{ $user->email }}</td><td>{{ config('idss.roles.'.$user->role.'.label', in_array($user->role, config('idss.administrator_aliases', []), true) ? 'System Administrator' : str_replace('_',' ',ucfirst($user->role))) }}</td><td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td><td>
<details><summary>Edit details / role</summary><form method="POST" action="{{ route('users.update',$user) }}" style="min-width:250px;padding:8px">@csrf @method('PUT')
<label>Full name</label><input name="name" value="{{ $user->name }}" required><label>Email</label><input type="email" name="email" value="{{ $user->email }}" required>
<label>Role</label><select name="role" required>@foreach(array_keys(config('idss.roles')) as $role)@continue($role === 'farmer')<option value="{{ $role }}" @selected($user->role===$role)>{{ config('idss.roles.'.$role.'.label') }}</option>@endforeach
@if(in_array($user->role, config('idss.administrator_aliases', []), true))<option value="{{ $user->role }}" selected>System Administrator (legacy role)</option>@endif</select>
<label>Account status</label><select name="is_active"><option value="1" @selected($user->is_active)>Active</option><option value="0" @selected(!$user->is_active)>Inactive</option></select>
<div class="actions"><button class="button primary">Save changes</button></div></form></details>
<details style="margin-top:8px"><summary>Reset password</summary><form method="POST" action="{{ route('users.password.reset',$user) }}" style="min-width:250px;padding:8px">@csrf @method('PUT')
<label>New password (minimum 12 characters, letters and numbers)</label><input type="password" name="password" minlength="12" autocomplete="new-password" required>
<label>Confirm new password</label><input type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required>
<div class="actions"><button class="button secondary">Reset password</button></div></form></details>
</td></tr>
@empty
<tr><td colspan="4">No staff accounts found.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $users->links() }}</div></section>
@endsection
