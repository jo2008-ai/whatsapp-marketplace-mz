<?php

namespace App\Services;

use App\Models\Produto;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

class ProdutoService
{
    public function __construct(
        private ImageService $imageService
    ) {}

    public function listar(Tenant $tenant, Request $request): LengthAwarePaginator
    {
        $query = $tenant->produtos()->with(['categoria:id,nome,icone', 'vendedor:id,nome']);

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('pesquisa') || $request->filled('busca')) {
            $pesquisa = $request->input('pesquisa') ?? $request->input('busca');
            $query->where(fn($q) => $q->where('nome', 'ILIKE', "%{$pesquisa}%")->orWhere('descricao', 'ILIKE', "%{$pesquisa}%"));
        }

        if ($request->has('disponivel')) {
            $query->where('disponivel', $request->boolean('disponivel'));
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function obterPorId(Tenant $tenant, int $id): ?Produto
    {
        return $tenant->produtos()
            ->with(['categoria:id,nome,icone', 'vendedor:id,nome,numero_whatsapp'])
            ->find($id);
    }

    public function criar(Tenant $tenant, array $validated, ?UploadedFile $imagem = null, ?UploadedFile $imagem2 = null): Produto
    {
        $validated['tenant_id'] = $tenant->id;
        $validated = $this->imageService->processarImagens($validated, $imagem, $imagem2);

        $produto = Produto::create($validated);
        $produto->load(['categoria:id,nome,icone', 'vendedor:id,nome']);

        return $produto;
    }

    public function actualizar(Tenant $tenant, int $id, array $validated, ?UploadedFile $imagem = null, ?UploadedFile $imagem2 = null): ?Produto
    {
        $produto = $this->obterPorId($tenant, $id);

        if (!$produto) {
            return null;
        }

        if ($imagem) {
            $validated['imagem_url'] = $this->imageService->guardarImagem($imagem);
        } elseif ($imagem === null && !isset($validated['imagem_url'])) {
            unset($validated['imagem_url']);
        }

        if ($imagem2) {
            $validated['imagem2_url'] = $this->imageService->guardarImagem($imagem2);
        } elseif ($imagem2 === null && !isset($validated['imagem2_url'])) {
            unset($validated['imagem2_url']);
        }

        unset($validated['imagem'], $validated['imagem2']);

        $produto->update($validated);
        $produto->load(['categoria:id,nome,icone', 'vendedor:id,nome']);

        return $produto;
    }

    public function eliminar(Tenant $tenant, int $id): bool
    {
        $produto = $this->obterPorId($tenant, $id);

        if (!$produto) {
            return false;
        }

        $produto->delete();
        return true;
    }

    public function toggleDisponivel(Tenant $tenant, int $id): ?array
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
