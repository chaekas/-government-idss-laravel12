<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private function staffRoles(): array
    {
        return array_values(array_diff(array_keys(config('idss.roles')), ['farmer']));
    }

    public function index()
    {
        return view('users.index', [
            'users' => User::where('role', '<>', 'farmer')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('users.create', ['roles' => $this->staffRoles()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'role' => ['required', Rule::in($this->staffRoles())],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        $data['is_active'] = true;
        $user = User::create($data);
        $this->audit($request, 'user.created', $user, 'Created staff account '.$user->email.' with role '.$user->role);

        return redirect()->route('users.index')->with('status', 'Staff account created successfully.');
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
        ]);

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
