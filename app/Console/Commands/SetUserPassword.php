<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetUserPassword extends Command
{
    /**
     * @var string
     */
    protected $signature = 'user:set-password {email} {password?}';

    /**
     * @var string
     */
    protected $description = 'Set (or reset) a user password without touching anything else. Useful when credentials are edited from the database.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email: {$this->argument('email')}");

            return self::FAILURE;
        }

        $password = $this->argument('password');

        if ($password === null) {
            $password = $this->secret('New password');
        }

        if ($password === null || Str::length((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user->update(['password' => $password]);

        $this->info("Password updated for {$user->email}.");

        return self::SUCCESS;
    }
}
