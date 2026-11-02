<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\Investigation;
use App\Models\Outbreak;
use App\Models\Sample;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'farmerCount' => Farmer::count(),
            'farmCount' => Farm::count(),
            'animalCount' => Animal::count(),
            'caseCount' => DiseaseCase::count(),
            'openCaseCount' => DiseaseCase::whereIn('status', ['reported', 'under_investigation'])->count(),
            'investigationCount' => Investigation::whereIn('status', ['pending', 'under_investigation', 'sample_collected'])->count(),
            'sampleCount' => Sample::count(),
            'pendingSampleCount' => Sample::whereIn('test_status', ['pending', 'in_progress', 'completed'])->count(),
            'outbreakCount' => Outbreak::whereIn('status', ['suspected', 'active'])->count(),
            'recentCases' => DiseaseCase::with('farm')->latest()->take(8)->get(),
        ]);
    }
}
