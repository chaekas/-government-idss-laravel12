<?php

use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiseaseCaseController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\FarmerController;
use App\Http\Controllers\InvestigationController;
use App\Http\Controllers\LaboratoryController;
use App\Http\Controllers\LaboratoryResultController;
use App\Http\Controllers\OutbreakController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('farmers', FarmerController::class)->only(['index', 'create', 'store']);
    Route::resource('farms', FarmController::class)->only(['index', 'create', 'store']);
    Route::resource('cases', DiseaseCaseController::class)->only(['index', 'create', 'store']);
    Route::resource('animals', AnimalController::class)->only(['index', 'create', 'store']);
    Route::get('/investigations', [InvestigationController::class, 'index'])->name('investigations.index');
    Route::get('/investigations/create', [InvestigationController::class, 'create'])->name('investigations.create');
    Route::post('/investigations', [InvestigationController::class, 'store'])->name('investigations.store');
    Route::put('/investigations/{investigation}', [InvestigationController::class, 'update'])->name('investigations.update');
    Route::resource('samples', SampleController::class)->only(['index', 'create', 'store', 'update']);
    Route::middleware('role:system_admin,administrator,admin,mlf_officer,zonal_laboratory_administrator')->group(function () {
        Route::get('/laboratories', [LaboratoryController::class, 'index'])->name('laboratories.index');
        Route::get('/laboratories/create', [LaboratoryController::class, 'create'])->name('laboratories.create');
        Route::post('/laboratories', [LaboratoryController::class, 'store'])->name('laboratories.store');
    });

    Route::middleware('role:laboratory_technician,laboratory_manager,system_admin,administrator,admin')->group(function () {
        Route::get('/laboratory', [LaboratoryResultController::class, 'index'])->name('laboratory.index');
        Route::get('/laboratory/create', [LaboratoryResultController::class, 'create'])->name('laboratory.create');
        Route::post('/laboratory', [LaboratoryResultController::class, 'store'])->name('laboratory.store');
        Route::put('/laboratory/{result}/review', [LaboratoryResultController::class, 'approve'])->name('laboratory.approve');
    });
    Route::resource('outbreaks', OutbreakController::class)->only(['index', 'create', 'store']);
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::middleware('role:system_admin,administrator,admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
