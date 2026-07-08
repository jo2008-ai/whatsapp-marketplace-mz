<?php

namespace App\Events;

use App\Models\Encomenda;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EncomendaActualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Encomenda $encomenda)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->encomenda->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'encomenda.actualizada';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->encomenda->id,
            'estado' => $this->encomenda->estado,
            'cliente' => $this->encomenda->nome_cliente ?? $this->encomenda->numero_cliente,
            'produto' => $this->encomenda->produto?->nome,
            'total' => (float) $this->encomenda->preco_total,
            'data' => $this->encomenda->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
