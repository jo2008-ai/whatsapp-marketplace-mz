<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Repôr Password — WhatsApp Marketplace',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.repor-password',
            with: [
                'user' => $this->user,
                'token' => $this->token,
                'resetUrl' => config('app.url') . '/repor-password/' . $this->token . '?email=' . urlencode($this->user->email),
            ],
        );
    }
}
