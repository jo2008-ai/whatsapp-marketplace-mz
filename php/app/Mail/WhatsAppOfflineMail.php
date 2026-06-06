<?php

namespace App\Mail;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WhatsAppOfflineMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public InstanciaWhatsApp $instancia,
        public string $linkReconnect,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ WhatsApp da loja {$this->tenant->nome_loja} desconectado",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.whatsapp-offline',
        );
    }
}
