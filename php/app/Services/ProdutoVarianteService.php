<?php

namespace App\Services;

use App\Models\Atributo;
use App\Models\AtributoValor;
use App\Models\Produto;
use App\Models\ProdutoVariante;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class ProdutoVarianteService
{
    /** @return \Illuminate\Database\Eloquent\Collection<int, ProdutoVariante> */
    public function listarVariantes(?Tenant $tenant = null, Produto $produto): \Illuminate\Database\Eloquent\Collection
    {
        return $produto->variantes()
            ->with('produto')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function criarVariante(?Tenant $tenant = null, Produto $produto, array $validated): ProdutoVariante
    {
        /** @var ProdutoVariante */
        return DB::transaction(function () use ($produto, $validated) {
            $atributos = $validated['atributos'] ?? [];

            $atributosProcessados = [];
            foreach ($atributos as $atributoData) {
                $atributo = Atributo::find($atributoData['atributo_id']);

                if (!$atributo) {
                    throw new \InvalidArgumentException("Atributo não encontrado: {$atributoData['atributo_id']}");
                }

                $valor = $atributoData['valor'] ?? $atributoData['valor_id'] ?? null;

                if ($atributo->tipo === 'cor' && !empty($atributoData['valor_hex'])) {
                    $valor = $atributoData['valor_hex'];
                }

                $atributosProcessados[] = [
                    'codigo' => $atributo->codigo,
                    'nome' => $atributo->nome,
                    'tipo' => $atributo->tipo,
                    'valor' => $valor,
                ];
            }

            $variante = $produto->variantes()->create([
                'sku' => $validated['sku'] ?? null,
                'preco_override' => $validated['preco_override'] ?? null,
                'stock' => $validated['stock'] ?? 0,
                'disponivel' => $validated['disponivel'] ?? true,
                'imagem_url' => $validated['imagem_url'] ?? null,
                'atributos' => $atributosProcessados,
            ]);

            return $variante;
        });
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function actualizarVariante(?Tenant $tenant = null, ProdutoVariante $variante, array $validated): ProdutoVariante
    {
        return DB::transaction(function () use ($variante, $validated) {
            if (isset($validated['atributos'])) {
                $atributosProcessados = [];
                foreach ($validated['atributos'] as $atributoData) {
                    $atributo = Atributo::find($atributoData['atributo_id']);

                    if (!$atributo) {
                        throw new \InvalidArgumentException("Atributo não encontrado: {$atributoData['atributo_id']}");
                    }

                    $valor = $atributoData['valor'] ?? $atributoData['valor_id'] ?? null;

                    if ($atributo->tipo === 'cor' && !empty($atributoData['valor_hex'])) {
                        $valor = $atributoData['valor_hex'];
                    }

                    $atributosProcessados[] = [
                        'codigo' => $atributo->codigo,
                        'nome' => $atributo->nome,
                        'tipo' => $atributo->tipo,
                        'valor' => $valor,
                    ];
                }

                $validated['atributos'] = $atributosProcessados;
            }

            $variante->update($validated);

            return $variante;
        });
    }

    public function eliminarVariante(?Tenant $tenant = null, ProdutoVariante $variante): bool
    {
        return $variante->delete();
    }

    public function toggleDisponivel(?Tenant $tenant = null, ProdutoVariante $variante): ProdutoVariante
    {
        $variante->update(['disponivel' => !$variante->disponivel]);

        return $variante;
    }

    /**
     * @param array<int, string>|null $cores
     * @param array<int, string>|null $tamanhos
     */
    public function criarVariantesViaJSON(?Tenant $tenant = null, Produto $produto, ?array $cores, ?array $tamanhos): void
    {
        if (empty($cores) && empty($tamanhos)) {
            return;
        }

        $corAtributoId = $this->getOrCreateAtributo($produto->tenant_id, 'cor', 'Cor', 'cor');
        $tamanhoAtributoId = $this->getOrCreateAtributo($produto->tenant_id, 'tamanho', 'Tamanho', 'tamanho');

        $cores = $cores ?: [null];
        $tamanhos = $tamanhos ?: [null];

        $stockPerVariante = (int) ($produto->stock / max(count($cores) * count($tamanhos), 1));
        $stockRestante = $produto->stock % (count($cores) * count($tamanhos));

        foreach ($cores as $cor) {
            foreach ($tamanhos as $tamanho) {
                $atributos = [];
                if ($cor) {
                    $atributos[] = [
                        'codigo' => 'cor',
                        'nome' => 'Cor',
                        'tipo' => 'cor',
                        'valor' => $cor,
                    ];
                }
                if ($tamanho) {
                    $atributos[] = [
                        'codigo' => 'tamanho',
                        'nome' => 'Tamanho',
                        'tipo' => 'tamanho',
                        'valor' => $tamanho,
                    ];
                }

                if (empty($atributos)) {
                    continue;
                }

                $stockVariante = $stockPerVariante + ($stockRestante > 0 ? 1 : 0);
                if ($stockRestante > 0) {
                    $stockRestante--;
                }

                $produto->variantes()->create([
                    'stock' => $stockVariante,
                    'disponivel' => true,
                    'atributos' => $atributos,
                ]);
            }
        }
    }

    private function getOrCreateAtributo(int $tenantId, string $codigo, string $nome, string $tipo): int
    {
        $atributo = Atributo::where('tenant_id', $tenantId)
            ->where('codigo', $codigo)
            ->first();

        if ($atributo) {
            return $atributo->id;
        }

        $atributo = Atributo::create([
            'tenant_id' => $tenantId,
            'codigo' => $codigo,
            'nome' => $nome,
            'tipo' => $tipo,
            'is_filterable' => true,
            'is_configurable' => true,
            'swatch_type' => $tipo === 'cor' ? 'color' : null,
            'ordem' => 0,
        ]);

        return $atributo->id;
    }
}
