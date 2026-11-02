<?php

namespace App\Http\Controllers;

use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Outbreak;

class GISController extends Controller
{
    public function index()
    {
        $points = [];

        foreach (Farm::whereNotNull('latitude')->whereNotNull('longitude')->with('farmer')->get() as $farm) {
            $points[] = [
                'type' => 'farm',
                'label' => $farm->name,
                'code' => $farm->farm_code,
                'latitude' => (float) $farm->latitude,
                'longitude' => (float) $farm->longitude,
                'detail' => $farm->district.', '.$farm->region.' · Farmer: '.($farm->farmer?->full_name ?? 'Not recorded'),
            ];
        }

        foreach (DiseaseCase::with('farm')->whereHas('farm', fn ($query) => $query->whereNotNull('latitude')->whereNotNull('longitude'))->latest()->get() as $case) {
            if (!$case->farm) continue;
            $points[] = [
                'type' => 'case',
                'label' => $case->disease_suspected,
                'code' => $case->case_code,
                'latitude' => (float) $case->farm->latitude,
                'longitude' => (float) $case->farm->longitude,
                'detail' => 'Farm '.$case->farm->name.' · '.$case->severity.' severity · '.str_replace('_', ' ', $case->status),
            ];
        }

        foreach (Outbreak::whereNotNull('latitude')->whereNotNull('longitude')->latest()->get() as $outbreak) {
            $points[] = [
                'type' => 'outbreak',
                'label' => $outbreak->disease_name,
                'code' => $outbreak->outbreak_code,
                'latitude' => (float) $outbreak->latitude,
                'longitude' => (float) $outbreak->longitude,
                'detail' => $outbreak->district.', '.$outbreak->region.' · '.str_replace('_', ' ', $outbreak->status),
            ];
        }

        return view('gis.index', ['mapPoints' => $points]);
    }
}
