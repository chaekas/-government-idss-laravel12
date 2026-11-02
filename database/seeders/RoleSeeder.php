<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('idss.roles', []) as $name => $details) {
            Role::updateOrCreate(
                ['name' => $name],
                ['label' => $details['label'], 'description' => $details['description'], 'is_system' => true],
            );
        }
    }
}
