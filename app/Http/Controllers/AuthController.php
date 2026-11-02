<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
 public function create() { return view('auth.login'); }
 public function store(Request $request) {
  $credentials=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
  if (!Auth::attempt($credentials,$request->boolean('remember'))) return back()->withErrors(['email'=>'The provided credentials do not match our records.'])->onlyInput('email');
  if (!$request->user()->is_active) { Auth::logout(); return back()->withErrors(['email'=>'This account is inactive. Contact your system administrator.'])->onlyInput('email'); }
  $request->session()->regenerate();
  // Never follow a stale intended URL: in Codespaces it may point to localhost,
  // which is not reachable from the user's browser. Always use the configured app URL.
  return redirect()->route('dashboard');
 }
 public function destroy(Request $request) {
  Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
  return redirect()->route('login')->with('status','You have been signed out.');
 }
}
