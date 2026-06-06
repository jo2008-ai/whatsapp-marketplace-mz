<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PedidoUpgradeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $plano,
        public string $referenciaPagamento,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔄 Pedido de upgrade — {$this->tenant->nome_loja}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pedido-upgrade',
        );
    }
}
