<?php

namespace App\Mail;

use App\Models\Encomenda;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NovaEncomendaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Encomenda $encomenda,
    ) {
        $this->encomenda->load(['produto', 'vendedor', 'tenant']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nova Encomenda — ' . $this->encomenda->tenant?->nome_loja,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nova-encomenda',
        );
    }
}
