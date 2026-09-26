<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserWelcomeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $user, public string $variant = 'admin') {}

    public function envelope(): Envelope
    {
        $first = trim((string) str($this->user->name)->before(' '));

        $subjects = [
            'admin' => "Welcome to Envoy, {$first}!",
            'portal' => "Welcome to Envoy Academy, {$first}!",
            'buyer' => "Welcome to Envoy, {$first}! Your account is ready.",
        ];

        return new Envelope(
            subject: $subjects[$this->variant] ?? $subjects['admin'],
        );
    }

    public function content(): Content
    {
        $first = trim((string) str($this->user->name)->before(' '));

        return new Content(
            view: 'emails.user.welcome',
            with: [
                'user' => $this->user,
                'name' => $this->user->name,
                'first' => $first,
                'email' => $this->user->email,
                'variant' => $this->variant,
                'loginUrl' => $this->variant === 'portal' ? route('portal.login') : route('login'),
                'exploreUrl' => match ($this->variant) {
                    'portal' => route('training'),
                    'buyer' => route('buyer.dashboard'),
                    default => route('dashboard'),
                },
            ],
        );
    }
}
