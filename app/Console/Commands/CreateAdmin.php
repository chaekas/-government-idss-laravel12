<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'idss:create-admin';
    protected $description = 'Create the first or an additional IDSS system administrator';

    public function handle(): int
    {
        $name = $this->ask('Administrator full name');
        $email = strtolower((string) $this->ask('Administrator work email'));
        $password = (string) $this->secret('Password (minimum 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A name and valid email address are required.');
            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('That email address already exists.');
            return self::FAILURE;
        }
        $validator = validator(['password' => $password], ['password' => ['required', 'string', 'confirmed', Password::min(12)->letters()->numbers()] + []]);
        if ($password !== $confirmation || $validator->fails()) {
            $this->error('Passwords must match, be at least 12 characters, and contain letters and numbers.');
            return self::FAILURE;
        }

        User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password), 'role' => 'system_admin', 'is_active' => true]);
        $this->info('System administrator created successfully. Keep the credentials secure.');
        return self::SUCCESS;
    }
}
