<?php

namespace App\Services;

use App\Mail\NewAccountMail;
use App\Mail\UserWelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends human account emails (welcome / new-account) without ever breaking the
 * request that triggered them — failures are silently logged instead.
 */
class UserMailService
{
    public function welcome(User $user, string $variant = 'admin'): bool
    {
        return $this->dispatch(fn () => new UserWelcomeMail($user, $variant), $user);
    }

    public function newAccount(User $user, ?string $password = null, string $variant = 'staff'): bool
    {
        return $this->dispatch(fn () => new NewAccountMail($user, $password, $variant), $user);
    }

    protected function dispatch(\Closure $build, User $user): bool
    {
        try {
            Mail::to($user->email, $user->name)->send($build());

            return true;
        } catch (\Throwable $e) {
            Log::error('Account email failed to send', [
                'user_email' => $user->email,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
