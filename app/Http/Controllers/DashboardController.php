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
use App\Services\UserDataScope;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $scope = app(UserDataScope::class);

        if ($user->role === 'farmer') {
            $farmIds = $scope->farms(Farm::query(), $user)->select('id');
            $caseIds = $scope->cases(DiseaseCase::query(), $user)->select('id');
            $investigations = $scope->investigations(Investigation::query(), $user);
            $samples = $scope->samples(Sample::query(), $user);

            return view('dashboard', [
                'dashboardMode' => 'farmer',
                'farmerCount' => $user->farmer_id ? 1 : 0,
                'farmCount' => (clone $farmIds)->count(),
                'animalCount' => Animal::whereIn('farm_id', $farmIds)->count(),
                'caseCount' => (clone $caseIds)->count(),
                'openCaseCount' => (clone $caseIds)->whereIn('status', ['reported', 'under_investigation', 'sample_collected', 'laboratory_testing'])->count(),
                'investigationCount' => (clone $investigations)->whereIn('status', ['pending', 'under_investigation', 'sample_collected'])->count(),
                'sampleCount' => (clone $samples)->count(),
                'pendingSampleCount' => (clone $samples)->whereIn('test_status', ['pending', 'in_progress', 'completed'])->count(),
                'pendingResultCount' => 0,
                'outbreakCount' => 0,
                'recentCases' => $scope->cases(DiseaseCase::with('farm')->latest(), $user)->take(8)->get(),
            ]);
        }

        if ($user->hasRole(['laboratory_technician', 'laboratory_manager', 'zonal_laboratory_administrator'])) {
            $samples = $scope->samples(Sample::query(), $user);
            $results = $scope->laboratoryResults(LaboratoryTestResult::query(), $user);

            return view('dashboard', [
                'dashboardMode' => 'laboratory',
                'farmerCount' => 0,
                'farmCount' => 0,
                'animalCount' => 0,
                'caseCount' => 0,
                'openCaseCount' => 0,
                'investigationCount' => 0,
                'sampleCount' => (clone $samples)->count(),
                'pendingSampleCount' => (clone $samples)->whereIn('test_status', ['pending', 'in_progress', 'completed'])->count(),
                'pendingResultCount' => (clone $results)->where('status', 'submitted_for_review')->count(),
                'outbreakCount' => 0,
                'recentCases' => collect(),
            ]);
        }

        $farms = $scope->farms(Farm::query(), $user);
        $cases = $scope->cases(DiseaseCase::query(), $user);
        $investigations = $scope->investigations(Investigation::query(), $user);
        $samples = $scope->samples(Sample::query(), $user);
        $results = $scope->laboratoryResults(LaboratoryTestResult::query(), $user);
        $outbreaks = $scope->outbreaks(Outbreak::query(), $user);
        $farmers = Farmer::whereHas('farms', fn ($query) => $scope->farms($query, $user));

        return view('dashboard', [
            'dashboardMode' => 'surveillance',
            'farmerCount' => (clone $farmers)->count(),
            'farmCount' => (clone $farms)->count(),
            'animalCount' => Animal::whereIn('farm_id', (clone $farms)->select('id'))->count(),
            'caseCount' => (clone $cases)->count(),
            'openCaseCount' => (clone $cases)->whereIn('status', ['reported', 'under_investigation', 'sample_collected', 'laboratory_testing'])->count(),
            'investigationCount' => (clone $investigations)->whereIn('status', ['pending', 'under_investigation', 'sample_collected'])->count(),
            'sampleCount' => (clone $samples)->count(),
            'pendingSampleCount' => (clone $samples)->whereIn('test_status', ['pending', 'in_progress', 'completed'])->count(),
            'pendingResultCount' => (clone $results)->where('status', 'submitted_for_review')->count(),
            'outbreakCount' => (clone $outbreaks)->whereIn('status', ['suspected', 'active'])->count(),
            'recentCases' => $scope->cases(DiseaseCase::with('farm')->latest(), $user)->take(8)->get(),
        ]);
    }
}
