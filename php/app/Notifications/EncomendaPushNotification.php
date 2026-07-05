<?php

namespace App\Notifications;

use App\Models\Encomenda;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class EncomendaPushNotification extends Notification
{
    use Queueable;

    public function __construct(public Encomenda $encomenda)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->encomenda->id,
            'cliente' => $this->encomenda->nome_cliente ?? $this->encomenda->numero_cliente,
            'produto' => $this->encomenda->produto?->nome,
            'total' => (float) $this->encomenda->preco_total,
            'estado' => $this->encomenda->estado,
            'mensagem' => "Nova encomenda de {$this->encomenda->nome_cliente}!",
        ]);
    }
}
