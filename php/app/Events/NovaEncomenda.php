<?php

namespace App\Events;

use App\Models\Encomenda;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NovaEncomenda implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Encomenda $encomenda)
    {
        $this->encomenda->load(['produto', 'vendedor', 'tenant']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->encomenda->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'nova.encomenda';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->encomenda->id,
            'cliente' => $this->encomenda->nome_cliente ?? $this->encomenda->numero_cliente,
            'produto' => $this->encomenda->produto?->nome,
            'total' => (float) $this->encomenda->preco_total,
            'estado' => $this->encomenda->estado,
            'data' => $this->encomenda->created_at->format('d/m/Y H:i'),
        ];
    }
}
