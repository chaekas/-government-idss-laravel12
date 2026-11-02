<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('idss.roles', []) as $name => $details) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                ['label' => $details['label'], 'description' => $details['description'], 'is_system' => true],
            );

            User::where('role', $name)->each(fn (User $user) => $user->roles()->syncWithoutDetaching([$role->id]));
        }

        // Keep legacy administrator labels working while mapping them to the canonical
        // System Administrator role in the normalized authorization tables.
        $administrator = Role::where('name', 'system_admin')->first();
        if ($administrator) {
            User::whereIn('role', config('idss.administrator_aliases', []))
                ->each(fn (User $user) => $user->roles()->syncWithoutDetaching([$administrator->id]));
        }
    }
}
