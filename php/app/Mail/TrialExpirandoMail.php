<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpirandoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public int $diasRestantes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "O teu trial expira em {$this->diasRestantes} dias!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-expirando',
        );
    }
}
