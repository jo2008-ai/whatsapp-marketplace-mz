<?php

namespace App\Services;

use App\Actions\Produto\ActualizarProduto;
use App\Actions\Produto\CriarProduto;
use App\Actions\Produto\EliminarProduto;
use App\Context\TenantContext;
use App\Models\Produto;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

class ProdutoService
{
    public function __construct(
        private CriarProduto $criarProduto,
        private ActualizarProduto $actualizarProduto,
        private EliminarProduto $eliminarProduto
    ) {}

    private function resolveTenant(?Tenant $tenant = null): Tenant
    {
        if ($tenant) {
            return $tenant;
        }

        return app(TenantContext::class)->tenant();
    }

    /** @return LengthAwarePaginator<array-key, \App\Models\Produto> */
    public function listar(?Tenant $tenant = null, Request $request = null): LengthAwarePaginator
    {
        $tenant = $this->resolveTenant($tenant);
        $query = Produto::with(['categoria:id,nome,icone', 'vendedor:id,nome']);

        if ($request && $request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request && ($request->filled('pesquisa') || $request->filled('busca'))) {
            $pesquisa = $request->input('pesquisa') ?? $request->input('busca');
            $query->where(fn($q) => $q->where('nome', 'ILIKE', "%{$pesquisa}%")->orWhere('descricao', 'ILIKE', "%{$pesquisa}%"));
        }

        if ($request && $request->has('disponivel')) {
            $query->where('disponivel', $request->boolean('disponivel'));
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function obterPorId(?Tenant $tenant = null, int $id): ?Produto
    {
        $tenant = $this->resolveTenant($tenant);

        return Produto::with(['categoria:id,nome,icone', 'vendedor:id,nome,numero_whatsapp'])
            ->find($id);
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function criar(?Tenant $tenant = null, array $validated, ?UploadedFile $imagem = null, ?UploadedFile $imagem2 = null): Produto
    {
        $tenant = $this->resolveTenant($tenant);

        return $this->criarProduto->handle($tenant, $validated, $imagem, $imagem2);
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function actualizar(?Tenant $tenant = null, int $id, array $validated, ?UploadedFile $imagem = null, ?UploadedFile $imagem2 = null): ?Produto
    {
        $tenant = $this->resolveTenant($tenant);

        return $this->actualizarProduto->handle($tenant, $id, $validated, $imagem, $imagem2);
    }

    public function eliminar(?Tenant $tenant = null, int $id): bool
    {
        $tenant = $this->resolveTenant($tenant);

        return $this->eliminarProduto->handle($tenant, $id);
    }

    /** @return array{id: int, disponivel: bool}|null */
    public function toggleDisponivel(?Tenant $tenant = null, int $id): ?array
    {
        $produto = $this->obterPorId($tenant, $id);

        if (!$produto) {
            return null;
        }

        $produto->update(['disponivel' => !$produto->disponivel]);

        return [
            'id' => $produto->id,
            'disponivel' => $produto->disponivel,
        ];
    }
}
