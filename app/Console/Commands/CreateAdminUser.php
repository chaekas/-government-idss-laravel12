<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
class CreateAdminUser extends Command {
 protected $signature='idss:create-admin';
 protected $description='Interactively create the first authorized IDSS administrator account';
 public function handle(): int {
  $name=trim((string)$this->ask('Administrator full name'));
  $email=strtolower(trim((string)$this->ask('Administrator work email')));
  if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) { $this->error('A name and valid email are required.'); return self::FAILURE; }
  if (User::where('email',$email)->exists()) { $this->error('An account with that email already exists.'); return self::FAILURE; }
  $password=(string)$this->secret('Choose a strong password (input hidden)');
  $confirm=(string)$this->secret('Confirm password');
  if (strlen($password)<12 || $password!==$confirm) { $this->error('Passwords must match and be at least 12 characters long.'); return self::FAILURE; }
  User::create(['name'=>$name,'email'=>$email,'password'=>Hash::make($password),'role'=>'super_admin','is_active'=>true]);
  $this->info('Administrator account created. Sign in at /login.');
  return self::SUCCESS;
 }
}
