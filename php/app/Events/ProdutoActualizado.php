<?php

namespace App\Events;

use App\Models\Produto;
use App\Models\Tenant;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProdutoActualizado implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Produto $produto,
        public ?Tenant $tenant,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenant?->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'produto.actualizado';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->produto->id,
            'nome' => $this->produto->nome,
            'preco' => (float) $this->produto->preco,
            'stock' => $this->produto->stock,
            'disponivel' => $this->produto->disponivel,
            'data' => $this->produto->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
