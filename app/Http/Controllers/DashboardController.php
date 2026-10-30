<?php
namespace App\Http\Controllers;
use App\Models\DiseaseCase;
use App\Models\Farmer;
use App\Models\Farm;
class DashboardController extends Controller {
 public function __invoke() {
  return view('dashboard',[
   'farmerCount'=>Farmer::count(),'farmCount'=>Farm::count(),'caseCount'=>DiseaseCase::count(),
   'openCaseCount'=>DiseaseCase::whereIn('status',['reported','under_investigation'])->count(),
   'recentCases'=>DiseaseCase::with('farm')->latest()->take(8)->get()
  ]);
 }
}
