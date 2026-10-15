<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateSchoolAdmin extends Command
{
    protected $signature = 'school:create-admin';

    protected $description = 'Create the first school administrator with a private password prompt';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Administrator name'));
        $email = strtolower(trim((string) $this->ask('Administrator email')));

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('An account with that email already exists. Use a different email address.');

            return self::FAILURE;
        }

        $password = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $candidate = $this->secret('Choose a password with at least 12 characters');
            $confirmation = $this->secret('Confirm the password');

            if (! is_string($candidate) || mb_strlen($candidate) < 12) {
                $this->error('The password must contain at least 12 characters.');

                continue;
            }

            if (! is_string($confirmation) || ! hash_equals($candidate, $confirmation)) {
                $this->error('The passwords did not match.');

                continue;
            }

            $password = $candidate;

            break;
        }

        if ($password === null) {
            $this->error('Administrator was not created. Run the command again to retry.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'role' => 'admin',
            'password' => $password,
        ]);

        $this->info("Administrator account created for {$email}.");

        return self::SUCCESS;
    }
}
