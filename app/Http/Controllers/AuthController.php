<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function storeRegistration(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'region' => ['required', 'string', 'max:120'],
            'district' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        $user = DB::transaction(function () use ($data) {
            $farmer = Farmer::create([
                'farmer_code' => 'FAR-' . Str::upper(Str::random(10)),
                'full_name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'region' => $data['region'],
                'district' => $data['district'],
            ]);

            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                // Public registration must never grant staff or administrator access.
                'role' => 'farmer',
                'is_active' => true,
                'farmer_id' => $farmer->id,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your farmer account has been created successfully.');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
        }

        if (!$request->user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'This account is inactive. Contact your system administrator.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Never follow a stale intended URL: in Codespaces it may point to localhost,
        // which is not reachable from the user's browser. Always use the configured app URL.
        return redirect()->route('dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been signed out.');
    }
}
