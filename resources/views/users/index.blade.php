@extends('layouts.app')
@section('title','Users management')
@section('content')
<div class="heading"><div><small class="kicker">SUPER ADMINISTRATION / ACCESS CONTROL</small><h1>User management</h1><p>Create and manage Ministry, field and laboratory staff accounts. Farmers register themselves from the login page and are not managed here.</p></div><a class="button primary" href="{{ route('users.create') }}">＋ Create user</a></div>
<section class="panel"><div class="panel-head"><div><h2>Staff accounts</h2><p>{{ $users->total() }} staff accounts</p></div></div><div class="table-wrap"><table><thead><tr><th>Name / Email</th><th>Role</th><th>Organization / Office</th><th>Status</th><th>Manage account</th></tr></thead><tbody>
@forelse($users as $user)
<tr><td><b>{{ $user->name }}</b><br>{{ $user->email }}</td><td>{{ config('idss.roles.'.$user->role.'.label', in_array($user->role, config('idss.administrator_aliases', []), true) ? 'System Administrator' : str_replace('_',' ',ucfirst($user->role))) }}</td><td>{{ $user->organization?->name ?: '—' }}<br><small>{{ $user->department?->name ?: '—' }} · {{ $user->office?->name ?: 'No office assigned' }}</small></td><td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td><td>
<details><summary>Edit details / role</summary><form method="POST" action="{{ route('users.update',$user) }}" style="min-width:250px;padding:8px">@csrf @method('PUT')
<label>Full name</label><input name="name" value="{{ $user->name }}" required><label>Email</label><input type="email" name="email" value="{{ $user->email }}" required>
<label>Mobile number</label><input name="mobile_number" value="{{ $user->mobile_number }}">
<label>Organization</label><select name="organization_id"><option value="">Not assigned</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected($user->organization_id==$organization->id)>{{ $organization->name }}</option>@endforeach</select>
<label>Department</label><select name="department_id"><option value="">Not assigned</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected($user->department_id==$department->id)>{{ $department->name }}</option>@endforeach</select>
<label>Office / duty station</label><select name="office_id"><option value="">Not assigned</option>@foreach($offices as $office)<option value="{{ $office->id }}" @selected($user->office_id==$office->id)>{{ $office->name }}</option>@endforeach</select>
<label>Administrative areas (current geographic access)</label><div class="form-grid">@foreach($administrativeAreas as $area)<label class="field"><span><input type="checkbox" name="administrative_area_ids[]" value="{{ $area->id }}" @checked($user->administrativeAreas->contains('id',$area->id))> {{ $area->name }} ({{ $area->area_type }})</span></label>@endforeach</div>
<label>Laboratories (current laboratory access)</label><div class="form-grid">@foreach($laboratories as $lab)<label class="field"><span><input type="checkbox" name="laboratory_ids[]" value="{{ $lab->id }}" @checked($user->laboratories->contains('id',$lab->id))> {{ $lab->name }}</span></label>@endforeach</div>
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
<tr><td colspan="5">No staff accounts found.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $users->links() }}</div></section>
@endsection
