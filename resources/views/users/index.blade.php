@extends('layouts.app')
@section('title','User management')
@section('content')
<div class="heading"><div><small class="kicker">ADMINISTRATION / ACCESS CONTROL</small><h1>User management</h1><p>Create staff accounts, assign roles and deactivate access when required.</p></div><a class="button primary" href="{{ route('users.create') }}">＋ Create user</a></div>
<section class="panel"><div class="panel-head"><div><h2>System accounts</h2><p>{{ $users->total() }} accounts</p></div></div><div class="table-wrap"><table><thead><tr><th>Name / Email</th><th>Role</th><th>Status</th><th>Update account</th></tr></thead><tbody>@foreach($users as $user)<tr><td><b>{{ $user->name }}</b><br>{{ $user->email }}</td><td>{{ str_replace('_',' ',ucfirst($user->role)) }}</td><td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td><td><details><summary>Edit</summary><form data-user-edit method="POST" action="{{ route('users.update',$user) }}" style="min-width:250px;padding:8px">@csrf @method('PUT')
<label>Name</label><input name="name" value="{{ $user->name }}" required><label>Email</label><input type="email" name="email" value="{{ $user->email }}" required>
<label>Role</label><select name="role" data-role-select>@foreach(['farmer','field_officer','laboratory_technician','laboratory_manager','regional_veterinary_officer','zonal_laboratory_administrator','mlf_officer','system_admin'] as $role)<option value="{{ $role }}" @selected($user->role===$role)>{{ str_replace('_',' ',ucfirst($role)) }}</option>@endforeach</select>
<div data-farmer-link><label>Linked farmer record (required for Farmer role)</label><select name="farmer_id"><option value="">Select farmer</option>@foreach($farmers as $farmer)<option value="{{ $farmer->id }}" @selected($user->farmer_id==$farmer->id)>{{ $farmer->farmer_code }} — {{ $farmer->full_name }}</option>@endforeach</select><small>Staff accounts do not need a farmer link.</small></div>
<label>Status</label><select name="is_active"><option value="1" @selected($user->is_active)>Active</option><option value="0" @selected(!$user->is_active)>Inactive</option></select>
<label>New password (optional)</label><input type="password" name="password" minlength="12" autocomplete="new-password"><label>Confirm new password</label><input type="password" name="password_confirmation" minlength="12" autocomplete="new-password"><div class="actions"><button class="button primary">Save changes</button></div></form></details></td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $users->links() }}</div></section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-user-edit]').forEach(function (form) {
        const role = form.querySelector('[data-role-select]');
        const field = form.querySelector('[data-farmer-link]');
        const farmer = field.querySelector('select[name="farmer_id"]');
        function toggleFarmerLink() {
            const isFarmer = role.value === 'farmer';
            field.hidden = !isFarmer;
            farmer.required = isFarmer;
            if (!isFarmer) farmer.value = '';
        }
        role.addEventListener('change', toggleFarmerLink);
        toggleFarmerLink();
    });
});
</script>
@endsection
