<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'idss:create-admin-legacy';
    protected $description = 'Legacy alias for creating an IDSS system administrator';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Administrator full name'));
        $email = strtolower(trim((string) $this->ask('Administrator work email'));
        $password = (string) $this->secret('Choose a strong password (minimum 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A name and valid email address are required.');
            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('An account with that email already exists.');
            return self::FAILURE;
        }
        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirmation],
            ['password' => ['required', 'string', 'confirmed', Password::min(12)->letters()->numbers()]]
        );
        if ($validator->fails()) {
            $this->error('Passwords must match, be at least 12 characters, and contain letters and numbers.');
            return self::FAILURE;
        }

        User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password), 'role' => 'system_admin', 'is_active' => true]);
        $this->info('System administrator created successfully.');
        return self::SUCCESS;
    }
}
