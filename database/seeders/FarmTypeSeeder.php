<?php

namespace Database\Seeders;

use App\Models\FarmType;
use Illuminate\Database\Seeder;

class FarmTypeSeeder extends Seeder
{
    public function run(): void
    {
        // These classifications are explicitly named in SRS FR-026.
        $types = [
            ['code' => 'PASTORAL', 'name' => 'Pastoral'],
            ['code' => 'AGRO_PASTORAL', 'name' => 'Agro-pastoral'],
            ['code' => 'COMMERCIAL', 'name' => 'Commercial'],
            ['code' => 'DAIRY', 'name' => 'Dairy'],
            ['code' => 'RANCH', 'name' => 'Ranch'],
            ['code' => 'POULTRY', 'name' => 'Poultry'],
        ];

        foreach ($types as $index => $type) {
            FarmType::updateOrCreate(['code' => $type['code']], [
                'name' => $type['name'], 'is_active' => true, 'sort_order' => $index + 1,
            ]);
        }
    }
}
