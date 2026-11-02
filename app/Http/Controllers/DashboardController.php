<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\Investigation;
use App\Models\LaboratoryTestResult;
use App\Models\Outbreak;
use App\Models\Sample;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'farmer') {
            $farmerId = $user->farmer_id;
            $farmIds = $farmerId ? Farm::where('farmer_id', $farmerId)->select('id') : Farm::whereRaw('1 = 0')->select('id');
            $caseIds = DiseaseCase::whereIn('farm_id', $farmIds)->select('id');

            return view('dashboard', [
                'dashboardMode' => 'farmer',
                'farmerCount' => $farmerId ? 1 : 0,
                'farmCount' => $farmerId ? Farm::where('farmer_id', $farmerId)->count() : 0,
                'animalCount' => Animal::whereIn('farm_id', $farmIds)->count(),
                'caseCount' => DiseaseCase::whereIn('farm_id', $farmIds)->count(),
                'openCaseCount' => DiseaseCase::whereIn('farm_id', $farmIds)->whereIn('status', ['reported', 'under_investigation', 'sample_collected', 'laboratory_testing'])->count(),
                'investigationCount' => Investigation::whereIn('status', ['pending', 'under_investigation', 'sample_collected'])->whereIn('disease_case_id', $caseIds)->count(),
                'sampleCount' => Sample::whereIn('disease_case_id', $caseIds)->count(),
                'pendingSampleCount' => Sample::whereIn('disease_case_id', $caseIds)->whereIn('test_status', ['pending', 'in_progress', 'completed'])->count(),
                'pendingResultCount' => 0,
                'outbreakCount' => 0,
                'recentCases' => DiseaseCase::with('farm')->whereIn('farm_id', $farmIds)->latest()->take(8)->get(),
            ]);
        }

        if (in_array($user->role, ['laboratory_technician', 'laboratory_manager', 'zonal_laboratory_administrator'], true)) {
            return view('dashboard', [
                'dashboardMode' => 'laboratory',
                'farmerCount' => 0,
                'farmCount' => 0,
                'animalCount' => 0,
                'caseCount' => 0,
                'openCaseCount' => 0,
                'investigationCount' => 0,
                'sampleCount' => Sample::count(),
                'pendingSampleCount' => Sample::whereIn('test_status', ['pending', 'in_progress', 'completed'])->count(),
                'pendingResultCount' => LaboratoryTestResult::where('status', 'submitted_for_review')->count(),
                'outbreakCount' => 0,
                'recentCases' => collect(),
            ]);
        }

        return view('dashboard', [
            'dashboardMode' => 'surveillance',
            'farmerCount' => Farmer::count(),
            'farmCount' => Farm::count(),
            'animalCount' => Animal::count(),
            'caseCount' => DiseaseCase::count(),
            'openCaseCount' => DiseaseCase::whereIn('status', ['reported', 'under_investigation', 'sample_collected', 'laboratory_testing'])->count(),
            'investigationCount' => Investigation::whereIn('status', ['pending', 'under_investigation', 'sample_collected'])->count(),
            'sampleCount' => Sample::count(),
            'pendingSampleCount' => Sample::whereIn('test_status', ['pending', 'in_progress', 'completed'])->count(),
            'pendingResultCount' => LaboratoryTestResult::where('status', 'submitted_for_review')->count(),
            'outbreakCount' => Outbreak::whereIn('status', ['suspected', 'active'])->count(),
            'recentCases' => DiseaseCase::with('farm')->latest()->take(8)->get(),
        ]);
    }
}
