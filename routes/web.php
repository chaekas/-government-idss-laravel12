<?php

use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiseaseCaseController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\GISController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\FarmerController;
use App\Http\Controllers\InvestigationController;
use App\Http\Controllers\LaboratoryController;
use App\Http\Controllers\LaboratoryOperationsController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\LaboratoryResultController;
use App\Http\Controllers\OutbreakController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\SurveillanceController;
use App\Http\Controllers\SurveillanceAttachmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');

    // Public registration is farmer-only; staff and privileged roles are admin-provisioned.
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'storeRegistration'])->middleware('throttle:5,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/surveillance/case-attachments/{attachment}', [SurveillanceAttachmentController::class, 'caseFile'])->name('surveillance.case-attachments.show');
    Route::get('/surveillance/investigation-attachments/{attachment}', [SurveillanceAttachmentController::class, 'investigationFile'])->name('surveillance.investigation-attachments.show');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Each account can only read and update its own notification inbox.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Farmers report cases for their own farms. Authorized surveillance staff can
    // review the case register; laboratory roles work through samples/results instead.
    Route::middleware('role:farmer,field_officer,regional_veterinary_officer,mlf_officer,system_admin,administrator,admin')->group(function () {
        Route::get('/cases', [DiseaseCaseController::class, 'index'])->name('cases.index');
        Route::get('/cases/create', [DiseaseCaseController::class, 'create'])->name('cases.create');
        Route::post('/cases', [DiseaseCaseController::class, 'store'])->name('cases.store');
        Route::get('/cases/{case}/edit', [DiseaseCaseController::class, 'edit'])->name('cases.edit');
        Route::put('/cases/{case}', [DiseaseCaseController::class, 'update'])->name('cases.update');
    });

    Route::middleware('role:regional_veterinary_officer,mlf_officer,system_admin,super_admin,super-admin,administrator,admin')->post('/cases/{case}/close', [DiseaseCaseController::class, 'close'])->name('cases.close');

    // Field registration and case investigation.
    Route::middleware('role:field_officer,regional_veterinary_officer,system_admin,administrator,admin')->group(function () {
        Route::resource('farmers', FarmerController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('farms', FarmController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('animals', AnimalController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        Route::get('/investigations/create', [InvestigationController::class, 'create'])->name('investigations.create');
        Route::post('/investigations', [InvestigationController::class, 'store'])->name('investigations.store');
        Route::put('/investigations/{investigation}', [InvestigationController::class, 'update'])->name('investigations.update');
    });

    Route::middleware('role:field_officer,regional_veterinary_officer,mlf_officer,system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::get('/investigations', [InvestigationController::class, 'index'])->name('investigations.index');
        Route::get('/investigations/{investigation}/report', [InvestigationController::class, 'report'])->name('investigations.report');
    });

    Route::middleware('role:regional_veterinary_officer,mlf_officer,system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::post('/investigations/{investigation}/approve', [InvestigationController::class, 'approve'])->name('investigations.approve');
    });

    // Ministry and regional leadership can view outbreak records; field officers
    // and designated surveillance leaders may register a suspected outbreak.
    Route::middleware('role:field_officer,regional_veterinary_officer,mlf_officer,system_admin,administrator,admin')->group(function () {
        Route::get('/outbreaks', [OutbreakController::class, 'index'])->name('outbreaks.index');
        Route::get('/outbreaks/create', [OutbreakController::class, 'create'])->name('outbreaks.create');
        Route::post('/outbreaks', [OutbreakController::class, 'store'])->name('outbreaks.store');
        Route::put('/outbreaks/{outbreak}', [OutbreakController::class, 'update'])->name('outbreaks.update');
    });

    // Sample visibility is shared by field, laboratory and surveillance roles.
    Route::middleware('role:field_officer,regional_veterinary_officer,laboratory_technician,laboratory_manager,zonal_laboratory_administrator,mlf_officer,system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::get('/samples', [SampleController::class, 'index'])->name('samples.index');
    });

    // Only field collection staff can create samples. Laboratory staff record
    // custody/receipt events; farmers and unrelated roles cannot mutate custody.
    Route::middleware('role:field_officer,system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::get('/samples/create', [SampleController::class, 'create'])->name('samples.create');
        Route::post('/samples', [SampleController::class, 'store'])->name('samples.store');
    });
    Route::middleware('role:field_officer,regional_veterinary_officer,laboratory_technician,laboratory_manager,zonal_laboratory_administrator,system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::put('/samples/{sample}', [SampleController::class, 'update'])->name('samples.update');
    });
    Route::middleware('role:laboratory_manager,zonal_laboratory_administrator,system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::put('/samples/{sample}/assign', [SampleController::class, 'assign'])->name('samples.assign');
    });

    // Laboratory quality management, equipment, calibration and corrective actions.
    Route::middleware('role:laboratory_manager,zonal_laboratory_administrator,system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::get('/laboratory/operations', [LaboratoryOperationsController::class, 'index'])->name('laboratory.operations');
        Route::post('/laboratory/operations/sections', [LaboratoryOperationsController::class, 'storeSection'])->name('laboratory.sections.store');
        Route::post('/laboratory/operations/equipment', [LaboratoryOperationsController::class, 'storeEquipment'])->name('laboratory.equipment.store');
        Route::put('/laboratory/operations/equipment/{equipment}', [LaboratoryOperationsController::class, 'updateEquipment'])->name('laboratory.equipment.update');
        Route::post('/laboratory/operations/equipment/{equipment}/calibrations', [LaboratoryOperationsController::class, 'recordCalibration'])->name('laboratory.equipment.calibrations.store');
        Route::post('/laboratory/operations/quality-control', [LaboratoryOperationsController::class, 'recordQualityControl'])->name('laboratory.quality-control.store');
        Route::put('/laboratory/operations/corrective-actions/{action}', [LaboratoryOperationsController::class, 'updateCorrectiveAction'])->name('laboratory.corrective-actions.update');
    });

    // Laboratory registry administration.
    Route::middleware('role:system_admin,super_admin,super-admin,administrator,admin,mlf_officer,zonal_laboratory_administrator')->group(function () {
        Route::get('/laboratories', [LaboratoryController::class, 'index'])->name('laboratories.index');
    });
    Route::middleware('role:system_admin,super_admin,super-admin,administrator,admin,zonal_laboratory_administrator')->group(function () {
        Route::get('/laboratories/create', [LaboratoryController::class, 'create'])->name('laboratories.create');
        Route::post('/laboratories', [LaboratoryController::class, 'store'])->name('laboratories.store');
    });

    // Technicians submit results; only an independent laboratory manager (or
    // controlled system administrator) can approve/reject a result.
    Route::middleware('role:laboratory_technician,laboratory_manager,zonal_laboratory_administrator,mlf_officer,system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::get('/laboratory', [LaboratoryResultController::class, 'index'])->name('laboratory.index');
    });
    Route::middleware('role:laboratory_technician')->group(function () {
        Route::get('/laboratory/create', [LaboratoryResultController::class, 'create'])->name('laboratory.create');
        Route::post('/laboratory', [LaboratoryResultController::class, 'store'])->name('laboratory.store');
    });
    Route::middleware('role:laboratory_manager,system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::put('/laboratory/{result}/review', [LaboratoryResultController::class, 'approve'])->name('laboratory.approve');
    });

    // Reports and GIS are leadership functions, not public or laboratory-technician functions.
    Route::middleware('role:regional_veterinary_officer,mlf_officer,system_admin,administrator,admin')->group(function () {
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('/reports/cases.csv', [ReportsController::class, 'cases'])->name('reports.cases');
        Route::get('/reports/samples.csv', [ReportsController::class, 'samples'])->name('reports.samples');
        Route::get('/gis', [GISController::class, 'index'])->name('gis.index');
        Route::get('/surveillance', [SurveillanceController::class, 'index'])->name('surveillance.index');
    });

    // Accept the legacy administrator role labels used by existing seeded accounts.
    // Public registration still cannot assign any of these privileged roles.
    Route::middleware('role:system_admin,super_admin,super-admin,administrator,admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password.reset');
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/master-data/{type}', [MasterDataController::class, 'index'])->name('master-data.index');
        Route::get('/master-data/{type}/create', [MasterDataController::class, 'create'])->name('master-data.create');
        Route::post('/master-data/{type}', [MasterDataController::class, 'store'])->name('master-data.store');
        Route::get('/master-data/{type}/{record}/edit', [MasterDataController::class, 'edit'])->name('master-data.edit');
        Route::put('/master-data/{type}/{record}', [MasterDataController::class, 'update'])->name('master-data.update');
        Route::delete('/master-data/{type}/{record}', [MasterDataController::class, 'destroy'])->name('master-data.destroy');
    });
});
