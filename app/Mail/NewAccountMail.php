<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAccountMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $user, public ?string $password = null, public string $variant = 'staff')
    {
        $this->password = filled($this->password) ? $this->password : null;
    }

    public function envelope(): Envelope
    {
        $first = trim((string) str($this->user->name)->before(' '));

        $subjects = [
            'staff' => "Your Envoy login is ready, {$first}",
            'trainee' => "Your Envoy Academy login is ready, {$first}",
        ];

        return new Envelope(
            subject: $subjects[$this->variant] ?? $subjects['staff'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user.new-account',
            with: [
                'user' => $this->user,
                'name' => $this->user->name,
                'first' => trim((string) str($this->user->name)->before(' ')),
                'email' => $this->user->email,
                'data_password' => $this->password,
                'variant' => $this->variant,
                'loginUrl' => $this->variant === 'trainee' ? route('portal.login') : route('login'),
                'resetUrl' => route('password.request'),
            ],
        );
    }
}
