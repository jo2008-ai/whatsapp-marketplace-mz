<?php

namespace App\Console\Commands;

use App\Models\Encomenda;
use App\Models\Produto;
use App\Models\Tenant;
use Illuminate\Console\Command;

class ExportarDados extends Command
{
    protected $signature = 'marketplace:exportar {tenant_id : ID da loja} {--tipo=produtos : Tipo (produtos|encomendas)}';
    protected $description = 'Exporta dados de um tenant para CSV';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant_id');
        $tipo = $this->option('tipo');

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant {$tenantId} não encontrado.");
            return self::FAILURE;
        }

        $filename = "{$tenant->nome_loja}_{$tipo}_" . now()->format('Y-m-d') . '.csv';

        if ($tipo === 'produtos') {
            $this->exportarProdutos($tenant, $filename);
        } elseif ($tipo === 'encomendas') {
            $this->exportarEncomendas($tenant, $filename);
        } else {
            $this->error("Tipo inválido. Use: produtos ou encomendas");
            return self::FAILURE;
        }

        $this->info("Exportado para: storage/app/{$filename}");
        return self::SUCCESS;
    }

    private function exportarProdutos(Tenant $tenant, string $filename): void
    {
        $produtos = Produto::where('tenant_id', $tenant->id)
            ->with(['categoria', 'vendedor'])
            ->get();

        $csv = "ID,Nome,Categoria,Vendedor,Preço,Stock,Disponível,Destaque\n";
        foreach ($produtos as $p) {
            $csv .= implode(',', [
                $p->id,
                '"' . $p->nome . '"',
                '"' . ($p->categoria->nome ?? '') . '"',
                '"' . ($p->vendedor->nome ?? '') . '"',
                $p->preco,
                $p->stock,
                $p->disponivel ? 'Sim' : 'Não',
                $p->destaque ? 'Sim' : 'Não',
            ]) . "\n";
        }

        file_put_contents(storage_path('app/' . $filename), $csv);
    }

    private function exportarEncomendas(Tenant $tenant, string $filename): void
    {
        $encomendas = Encomenda::where('tenant_id', $tenant->id)
            ->with('produto')
            ->get();

        $csv = "ID,Cliente,Número,Produto,Quantidade,Total,Estado,Data\n";
        foreach ($encomendas as $e) {
            $csv .= implode(',', [
                $e->id,
                '"' . ($e->nome_cliente ?? '') . '"',
                $e->numero_cliente,
                '"' . ($e->produto->nome ?? '') . '"',
                $e->quantidade,
                $e->preco_total,
                $e->estado,
                \Illuminate\Support\Carbon::parse($e->created_at)->format('Y-m-d H:i'),
            ]) . "\n";
        }

        file_put_contents(storage_path('app/' . $filename), $csv);
    }
}
