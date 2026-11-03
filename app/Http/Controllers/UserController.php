<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private function staffRoles(): array
    {
        return array_values(array_diff(array_keys(config('idss.roles')), ['farmer']));
    }

    private function assignmentOptions(): array
    {
        return [
            'organizations' => Organization::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateAssignmentHierarchy(array $data): void
    {
        if (!empty($data['department_id']) && !empty($data['organization_id'])) {
            if (!Department::whereKey($data['department_id'])->where('organization_id', $data['organization_id'])->exists()) { throw ValidationException::withMessages(['department_id' => 'The selected department does not belong to the selected organization.']); }
        }
        if (!empty($data['office_id'])) {
            $office = Office::findOrFail($data['office_id']);
            if (!empty($data['organization_id'])) {
                if ((int) $office->organization_id !== (int) $data['organization_id']) { throw ValidationException::withMessages(['office_id' => 'The selected office does not belong to the selected organization.']); }
            }
            if (!empty($data['department_id'])) {
                if ((int) $office->department_id !== (int) $data['department_id']) { throw ValidationException::withMessages(['office_id' => 'The selected office does not belong to the selected department.']); }
            }
        }
    }

    public function index()
    {
        return view('users.index', [
            'users' => User::with(['organization', 'department', 'office'])->where('role', '<>', 'farmer')->orderBy('name')->paginate(20),
            ...$this->assignmentOptions(),
        ]);
    }

    public function create()
    {
        return view('users.create', ['roles' => $this->staffRoles(), ...$this->assignmentOptions()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'role' => ['required', Rule::in($this->staffRoles())],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
            'mobile_number' => ['nullable', 'string', 'max:40'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'office_id' => ['nullable', 'exists:offices,id'],
        ]);
        $this->validateAssignmentHierarchy($data);

        $data['is_active'] = true;
        $user = User::create($data);
        if ($role = Role::where('name', $user->role)->first()) { $user->roles()->sync([$role->id]); }
        $this->audit($request, 'user.created', $user, 'Created staff account '.$user->email.' with role '.$user->role);

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->role === 'farmer', 404);

        $adminRoleAliases = ['system_admin', ...config('idss.administrator_aliases', [])];
        $allowedRoles = array_values(array_unique([...$this->staffRoles(), ...(in_array($user->role, $adminRoleAliases, true) ? [$user->role] : [])]));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email,'.$user->id],
            'role' => ['required', Rule::in($allowedRoles)],
            'is_active' => ['required', 'boolean'],
            'mobile_number' => ['nullable', 'string', 'max:40'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'office_id' => ['nullable', 'exists:offices,id'],
        ]);
        $this->validateAssignmentHierarchy($data);

        $isAdministratorAccount = in_array($user->role, $adminRoleAliases, true);
        if ($user->is($request->user()) && (!in_array($data['role'], $adminRoleAliases, true) || !$request->boolean('is_active'))) {
            return back()->withErrors(['role' => 'You cannot demote or deactivate your own administrator account.']);
        }

        $removingAdminAccess = $isAdministratorAccount
            && (!in_array($data['role'], $adminRoleAliases, true) || !$request->boolean('is_active'));
        if ($removingAdminAccess) {
            $otherActiveAdmins = User::whereIn('role', $adminRoleAliases)
                ->where('is_active', true)
                ->where('id', '<>', $user->id)
                ->count();

            if ($otherActiveAdmins < 1) {
                return back()->withErrors(['role' => 'At least one active administrator must remain.']);
            }
        }

        $user->update($data);
        $canonicalRole = in_array($user->role, config('idss.administrator_aliases', []), true) ? 'system_admin' : $user->role;
        if ($role = Role::where('name', $canonicalRole)->first()) { $user->roles()->sync([$role->id]); }
        $this->audit($request, 'user.updated', $user, 'Updated staff account '.$user->email);

        return redirect()->route('users.index')->with('status', 'Staff account updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_if($user->role === 'farmer', 404);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        $this->audit($request, 'user.password_reset', $user, 'Reset password for staff account '.$user->email);

        return redirect()->route('users.index')->with('status', 'Password reset successfully for '.$user->email.'.');
    }

    private function audit(Request $request, string $action, User $user, string $description): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'entity_type' => 'user',
            'entity_id' => (string) $user->id,
            'ip_address' => $request->ip(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
