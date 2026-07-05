<?php

namespace App\Console\Commands;

use App\Models\Produto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateProdutosImagens extends Command
{
    protected $signature = 'produtos:migrar-imagens';

    protected $description = 'Migra imagens existentes das colunas imagem_url/imagem2_url para o Spatie Media Library';

    public function handle(): int
    {
        $produtos = Produto::whereNotNull('imagem_url')
            ->orWhereNotNull('imagem2_url')
            ->get();

        $total = $produtos->count();
        $this->info("Encontrados {$total} produtos com imagens para migrar.");

        if ($total === 0) {
            $this->info('Nenhuma imagem para migrar.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Deseja continuar com a migração?')) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrados = 0;
        $erros = 0;

        foreach ($produtos as $produto) {
            try {
                if ($produto->imagem_url && !$this->jaExisteNoMedia($produto, $produto->imagem_url)) {
                    $this->migrarImagem($produto, $produto->imagem_url, 'principal');
                }

                if ($produto->imagem2_url && !$this->jaExisteNoMedia($produto, $produto->imagem2_url)) {
                    $this->migrarImagem($produto, $produto->imagem2_url, 'secundaria');
                }

                $migrados++;
            } catch (\Exception $e) {
                $erros++;
                $this->newLine();
                $this->error("Erro ao migrar produto #{$produto->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migração concluída!");
        $this->info("Produtos migrados: {$migrados}");
        if ($erros > 0) {
            $this->error("Erros: {$erros}");
        }

        return self::SUCCESS;
    }

    private function jaExisteNoMedia(Produto $produto, string $url): bool
    {
        return $produto->getMedia('imagens')
            ->contains('custom_properties->original_url', $url);
    }

    private function migrarImagem(Produto $produto, string $url, string $nome): void
    {
        $conteudo = $this->obterConteudoImagem($url);

        if (!$conteudo) {
            return;
        }

        $extensao = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $tempPath = tempnam(sys_get_temp_dir(), 'migrate_img_') . '.' . $extensao;
        file_put_contents($tempPath, $conteudo);

        $produto->addMedia($tempPath)
            ->setName($nome)
            ->withCustomProperties(['original_url' => $url])
            ->toMediaCollection('imagens');

        @unlink($tempPath);
    }

    private function obterConteudoImagem(string $url): ?string
    {
        if (str_starts_with($url, 'http')) {
            $context = stream_context_create([
                'http' => ['timeout' => 10],
            ]);
            return @file_get_contents($url, false, $context);
        }

        $path = str_replace(url('storage') . '/', '', $url);
        $fullPath = storage_path('app/public/' . $path);

        if (file_exists($fullPath)) {
            return file_get_contents($fullPath);
        }

        return null;
    }
}
