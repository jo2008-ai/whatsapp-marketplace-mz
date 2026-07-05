<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Encomenda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiEncomendaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $query = Encomenda::where('tenant_id', $tenant->id)
            ->with(['produto:id,nome,preco,imagem_url', 'vendedor:id,nome']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $encomendas = $query->orderByDesc('created_at')->paginate(20);

        return $this->success($encomendas);
    }

    /**
     * @param int $id
     */
    public function atualizarEstado(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $encomenda = Encomenda::where('tenant_id', $tenant->id)->find($id);

        if (!$encomenda) {
            return $this->notFound('Encomenda não encontrada.');
        }

        Gate::authorize('update', $encomenda);

        $request->validate([
            'estado' => 'required|in:pendente,confirmada,entregue,cancelada',
        ]);

        $encomenda->update(['estado' => $request->estado]);

        return $this->success([
            'id' => $encomenda->id,
            'estado' => $encomenda->estado,
        ], 'Estado actualizado.');
    }
}
