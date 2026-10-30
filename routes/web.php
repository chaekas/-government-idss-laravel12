<?php
use App\\Http\\Controllers\\AuthController;
use App\\Http\\Controllers\\DashboardController;
use App\\Http\\Controllers\\DiseaseCaseController;
use App\\Http\\Controllers\\FarmerController;
use Illuminate\\Support\\Facades\\Route;
Route::get('/',fn()=>redirect()->route('dashboard'));
Route::middleware('guest')->group(function(){
 Route::get('/login',[AuthController::class,'create'])->name('login');
 Route::post('/login',[AuthController::class,'store'])->middleware('throttle:login')->name('login.store');
});
Route::middleware('auth')->group(function(){
 Route::post('/logout',[AuthController::class,'destroy'])->name('logout');
 Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::resource('farmers',FarmerController::class)->only(['index','create','store']);
 Route::resource('cases',DiseaseCaseController::class)->only(['index','create','store']);
});
