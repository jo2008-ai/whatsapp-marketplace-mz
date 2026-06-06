<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BoasVindasMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Tenant $tenant,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao WhatsApp Marketplace!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.boas-vindas',
        );
    }
}
