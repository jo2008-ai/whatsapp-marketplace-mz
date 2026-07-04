<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::select('SELECT DISTINCT tenant_id FROM produtos WHERE cores IS NOT NULL OR tamanhos IS NOT NULL');

        foreach ($tenants as $row) {
            $tenantId = $row->tenant_id;

            $corAtributoId = $this->getOrCreateAtributo($tenantId, 'cor', 'Cor', 'cor');
            $tamanhoAtributoId = $this->getOrCreateAtributo($tenantId, 'tamanho', 'Tamanho', 'tamanho');

            $produtos = DB::select('SELECT id, cores, tamanhos, preco, stock, vendedor_id FROM produtos WHERE tenant_id = ? AND (cores IS NOT NULL OR tamanhos IS NOT NULL)', [$tenantId]);

            foreach ($produtos as $produto) {
                $cores = json_decode($produto->cores, true) ?? [];
                $tamanhos = json_decode($produto->tamanhos, true) ?? [];

                if (empty($cores) && empty($tamanhos)) {
                    continue;
                }

                $cores = $cores ?: [null];
                $tamanhos = $tamanhos ?: [null];

                $stockPerVariante = (int) ($produto->stock / max(count($cores) * count($tamanhos), 1));
                $stockRestante = $produto->stock % (count($cores) * count($tamanhos));

                foreach ($cores as $cor) {
                    foreach ($tamanhos as $tamanho) {
                        $atributos = [];
                        if ($cor) {
                            $valorCorId = $this->getOrCreateValor($corAtributoId, $cor);
                            $atributos[$corAtributoId] = ['nome' => 'Cor', 'valor' => $cor, 'valor_id' => $valorCorId];
                        }
                        if ($tamanho) {
                            $valorTamanhoId = $this->getOrCreateValor($tamanhoAtributoId, $tamanho);
                            $atributos[$tamanhoAtributoId] = ['nome' => 'Tamanho', 'valor' => $tamanho, 'valor_id' => $valorTamanhoId];
                        }

                        if (empty($atributos)) {
                            continue;
                        }

                        $stockVariante = $stockPerVariante + ($stockRestante > 0 ? 1 : 0);
                        if ($stockRestante > 0) {
                            $stockRestante--;
                        }

                        DB::table('produto_variantes')->insert([
                            'produto_id' => $produto->id,
                            'preco_override' => null,
                            'stock' => $stockVariante,
                            'disponivel' => true,
                            'atributos' => json_encode($atributos),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('produto_variantes')->where('id', '>', 0)->delete();
        DB::table('atributo_valores')->where('id', '>', 0)->delete();
        DB::table('atributos')->where('id', '>', 0)->delete();
    }

    private function getOrCreateAtributo(int $tenantId, string $codigo, string $nome, string $tipo): int
    {
        $existing = DB::selectOne('SELECT id FROM atributos WHERE tenant_id = ? AND codigo = ?', [$tenantId, $codigo]);

        if ($existing) {
            return $existing->id;
        }

        return DB::table('atributos')->insertGetId([
            'tenant_id' => $tenantId,
            'codigo' => $codigo,
            'nome' => $nome,
            'tipo' => $tipo,
            'is_filterable' => true,
            'is_configurable' => true,
            'swatch_type' => $tipo === 'cor' ? 'color' : null,
            'ordem' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getOrCreateValor(int $atributoId, string $valor): int
    {
        $codigo = strtolower(str_replace(' ', '-', $valor));

        $existing = DB::selectOne('SELECT id FROM atributo_valores WHERE atributo_id = ? AND codigo = ?', [$atributoId, $codigo]);

        if ($existing) {
            return $existing->id;
        }

        return DB::table('atributo_valores')->insertGetId([
            'atributo_id' => $atributoId,
            'codigo' => $codigo,
            'nome' => $valor,
            'valor' => $valor,
            'swatch_url' => null,
            'ordem' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
