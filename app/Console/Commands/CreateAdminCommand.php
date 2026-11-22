<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'create:admin
        {--name= : Full name of the admin}
        {--email= : Email address the admin logs in with}';

    protected $description = 'Create an admin account. The password is always asked for, so it never appears in the shell history';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Full name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->secret('Password (typing is hidden)');
        $confirmation = $this->secret('Repeat the password');

        // The same rules as the public registration form.
        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // The role cannot be mass assigned, so it is set on purpose here.
        $admin = new User([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $admin->forceFill(['role' => Role::Admin, 'email_verified_at' => now()])->save();

        $this->info("Admin account created for {$admin->email}.");

        return self::SUCCESS;
    }
}
