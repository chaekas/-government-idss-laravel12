<?php

namespace Database\Seeders;

use App\Models\SurveillanceAlertRule;
use Illuminate\Database\Seeder;

class SurveillanceAlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        SurveillanceAlertRule::firstOrCreate(
            ['code' => 'HIGH_SEVERITY_CASE'],
            [
                'name' => 'High severity suspected disease case',
                'severity' => 'high',
                'case_threshold' => 1,
                'within_hours' => 24,
                'notify_field_officers' => true,
                'notify_regional_officers' => true,
                'notify_ministry_officers' => true,
                'is_active' => true,
            ]
        );
    }
}
