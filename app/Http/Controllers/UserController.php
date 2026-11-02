<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Farmer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'users' => User::with('farmer')->orderBy('name')->paginate(20),
            'farmers' => Farmer::orderBy('full_name')->get(),
        ]);
    }

    public function create()
    {
        return view('users.create', ['farmers' => Farmer::orderBy('full_name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'role' => ['required', 'in:farmer,field_officer,laboratory_technician,laboratory_manager,regional_veterinary_officer,zonal_laboratory_administrator,mlf_officer,system_admin'],
            'farmer_id' => ['nullable', 'required_if:role,farmer', 'exists:farmers,id', 'unique:users,farmer_id'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        if ($data['role'] !== 'farmer') {
            $data['farmer_id'] = null;
        }
        $data['is_active'] = true;

        $user = User::create($data);
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.created',
            'entity_type' => 'user',
            'entity_id' => (string) $user->id,
            'ip_address' => $request->ip(),
            'description' => 'Created user '.$user->email.' with role '.$user->role,
            'created_at' => now(),
        ]);

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:farmer,field_officer,laboratory_technician,laboratory_manager,regional_veterinary_officer,zonal_laboratory_administrator,mlf_officer,system_admin'],
            'farmer_id' => ['nullable', 'required_if:role,farmer', 'exists:farmers,id', 'unique:users,farmer_id,'.$user->id],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        if ($data['role'] !== 'farmer') {
            $data['farmer_id'] = null;
        }
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        if ($user->is($request->user()) && !$request->boolean('is_active')) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.']);
        }

        $user->update($data);
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.updated',
            'entity_type' => 'user',
            'entity_id' => (string) $user->id,
            'ip_address' => $request->ip(),
            'description' => 'Updated user '.$user->email,
            'created_at' => now(),
        ]);

        return redirect()->route('users.index')->with('status', 'User updated.');
    }
}
