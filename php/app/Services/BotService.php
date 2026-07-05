<?php

namespace App\Services;

use App\Jobs\NotificarVendedorJob;
use App\Models\Categoria;
use App\Models\Encomenda;
use App\Models\Produto;
use App\Models\SessaoBot;
use App\Models\Tenant;
use App\Models\Vendedor;
use App\Services\TypebotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BotService
{
    private NotificacaoService $notificacaoService;
    private TypebotService $typebotService;
    private WahaService $wahaService;

    public function __construct(NotificacaoService $notificacaoService, TypebotService $typebotService, WahaService $wahaService)
    {
        $this->notificacaoService = $notificacaoService;
        $this->typebotService = $typebotService;
        $this->wahaService = $wahaService;
    }

    public function responder(Tenant $tenant, string $numero, string $mensagem, string $nome = ''): array|string
    {
        if (!$tenant->activo) {
            return 'Serviço temporariamente indisponível. Contacta o suporte.';
        }

        $sessao = SessaoBot::obter($tenant->id, $numero);
        $msg = mb_strtolower(trim($mensagem));

        if ($sessao->estado === 'transferido_vendedor') {
            return $this->processarTransferidoVendedor($tenant, $sessao, $msg, $numero, $nome);
        }

        if ($tenant->usar_typebot && $tenant->typebot_bot_id) {
            return $this->processarTypebot($tenant, $sessao, $msg, $mensagem, $numero, $nome);
        }

        if (in_array($msg, ['0', 'menu', 'voltar', 'inicio'])) {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, $nome);
        }

        return match ($sessao->estado) {
            'inicio' => $this->processarInicio($tenant, $sessao, $msg, $nome),
            'menu' => $this->processarMenu($tenant, $sessao, $msg, $nome),
            'categorias' => $this->processarCategorias($tenant, $sessao, $msg),
            'produtos_categoria' => $this->processarProdutosCategoria($tenant, $sessao, $msg),
            'produto_detalhe' => $this->processarProdutoDetalhe($tenant, $sessao, $msg, $numero, $nome),
            'escolher_cor' => $this->processarEscolherCor($tenant, $sessao, $msg),
            'escolher_tamanho' => $this->processarEscolherTamanho($tenant, $sessao, $msg),
            'pesquisa' => $this->processarPesquisa($tenant, $sessao, $msg),
            'pesquisa_resultados' => $this->processarPesquisaResultados($tenant, $sessao, $msg, $numero, $nome),
            'ver_encomendas' => $this->processarVerEncomendas($tenant, $sessao, $msg, $numero),
            'detalhe_encomenda' => $this->processarDetalheEncomenda($tenant, $sessao, $msg, $numero),
            'confirmar_cancelamento' => $this->processarConfirmarCancelamento($tenant, $sessao, $msg, $numero),
            'escolher_vendedor' => $this->processarEscolherVendedor($tenant, $sessao, $msg, $numero, $nome),
            'transferido_vendedor' => $this->processarTransferidoVendedor($tenant, $sessao, $msg, $numero, $nome),
            default => $this->menuPrincipal($tenant, $nome),
        };
    }

    private function processarTypebot(Tenant $tenant, SessaoBot $sessao, string $msg, string $mensagemOriginal, string $numero, string $nome): string
    {
        $typebotData = $sessao->dados['typebot'] ?? null;

        if (!$typebotData || !isset($typebotData['session_id'])) {
            $resultado = $this->typebotService->iniciarSessao($tenant, $numero, $mensagemOriginal, $nome);

            if (!$resultado || empty($resultado['session_id'])) {
                $sessao->atualizarEstado('inicio');
                return $this->menuPrincipal($tenant, $nome);
            }

            $sessao->atualizarEstado('typebot', [
                'typebot' => [
                    'session_id' => $resultado['session_id'],
                ],
            ]);

            $mensagens = $this->typebotService->processarRespostas($resultado['messages']);
            $texto = $this->formatarMensagensTypebot($mensagens);

            return $texto ?: $this->menuPrincipal($tenant, $nome);
        }

        $resultado = $this->typebotService->enviarMensagem(
            $tenant,
            $typebotData['session_id'],
            $mensagemOriginal
        );

        if (!$resultado) {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, $nome);
        }

        $mensagens = $this->typebotService->processarRespostas($resultado['messages']);
        $texto = $this->formatarMensagensTypebot($mensagens);

        return $texto ?: $this->menuPrincipal($tenant, $nome);
    }

    private function formatarMensagensTypebot(array $mensagens): string
    {
        $texto = '';

        foreach ($mensagens as $msg) {
            if ($msg['tipo'] === 'texto') {
                $texto .= ($texto ? "\n\n" : '') . $msg['conteudo'];
            }

            if ($msg['tipo'] === 'botoes') {
                $texto .= ($texto ? "\n\n" : '') . $msg['conteudo'];
                foreach ($msg['botoes'] as $i => $botao) {
                    $num = $i + 1;
                    $texto .= "\n{$num}️⃣ {$botao}";
                }
            }
        }

        return $texto;
    }

    private function menuPrincipal(Tenant $tenant, string $nome): string
    {
        $saudacao = $nome ? "Olá {$nome}!" : 'Olá!';

        if ($tenant->mensagem_boas_vindas) {
            return $tenant->mensagem_boas_vindas;
        }

        $banners = $this->obterBanners($tenant);

        $menu = "{$saudacao} 👋 Bem-vindo(a) à *{$tenant->nome_loja}*!\n\n";

        if ($banners) {
            $menu .= $banners . "\n";
        }

        $menu .= "1️⃣ Ver produtos por categoria\n"
              . "2️⃣ Pesquisar produto\n"
              . "3️⃣ As minhas encomendas\n"
              . "4️⃣ Falar com vendedor";

        return $menu;
    }

    private function obterBanners(Tenant $tenant): string
    {
        $banners = '';

        if ($tenant->bannerGlobalActivo()) {
            $cor = $tenant->banner_global_cor ?: '#2563EB';
            $banners .= "📢 *[{$tenant->banner_global_titulo}]*\n";
            if ($tenant->banner_global_texto) {
                $banners .= "{$tenant->banner_global_texto}\n";
            }
            $banners .= "\n";
        }

        if ($tenant->bannerPromoActivo()) {
            $cor = $tenant->banner_promo_cor ?: '#FF6B35';
            $banners .= "🔥 *[{$tenant->banner_promo_titulo}]*\n";
            if ($tenant->banner_promo_texto) {
                $banners .= "{$tenant->banner_promo_texto}\n";
            }
            $banners .= "\n";
        }

        return $banners;
    }

    private function processarInicio(Tenant $tenant, SessaoBot $sessao, string $msg, string $nome): string
    {
        $saudacoes = ['oi', 'olá', 'ola', 'bom dia', 'boa tarde', 'boa noite', 'hello', 'hi', 'hey'];
        if (in_array($msg, $saudacoes) || $msg === '1' || $msg === 'menu') {
            $sessao->atualizarEstado('menu');
            return $this->menuPrincipal($tenant, $nome);
        }

        $sessao->atualizarEstado('menu');
        return $this->menuPrincipal($tenant, $nome);
    }

    private function processarMenu(Tenant $tenant, SessaoBot $sessao, string $msg, string $nome): string
    {
        return match ($msg) {
            '1' => $this->mostrarCategorias($tenant, $sessao),
            '2' => $this->iniciarPesquisa($sessao),
            '3' => $this->mostrarEncomendas($tenant, $sessao, $msg, $nome),
            '4' => $this->mostrarVendedores($tenant, $sessao),
            default => $this->mensagemErroMenu($tenant, $nome),
        };
    }

    private function mensagemErroMenu(Tenant $tenant, string $nome): string
    {
        if ($tenant->mensagem_erro_menu) {
            return $tenant->mensagem_erro_menu;
        }

        $banners = $this->obterBanners($tenant);

        $msg = "Não entendi 😅 Escreve o número da opção ou *menu* para recomeçar.\n\n";

        if ($banners) {
            $msg .= $banners . "\n";
        }

        $msg .= "1️⃣ Ver produtos por categoria\n"
             . "2️⃣ Pesquisar produto\n"
             . "3️⃣ As minhas encomendas\n"
             . "4️⃣ Falar com vendedor";

        return $msg;
    }

    private function mostrarCategorias(Tenant $tenant, SessaoBot $sessao): string
    {
        $categorias = $tenant->categorias()
            ->where('ativo', true)
            ->orderBy('ordem')
            ->withCount(['produtos' => fn($q) => $q->where('disponivel', true)])
            ->get();

        if ($categorias->isEmpty()) {
            $sessao->atualizarEstado('inicio');
            return $tenant->mensagem_categoria_vazia ?: "Ainda não há categorias disponíveis. Volta mais tarde! 🙂";
        }

        $sessao->atualizarEstado('categorias');

        $texto = "🛍️ Escolhe uma categoria:\n\n";
        foreach ($categorias as $i => $cat) {
            $icone = $cat->icone ?: '📦';
            $num = $i + 1;
            $texto .= "{$num}️⃣ {$icone} {$cat->nome} ({$cat->produtos_count} produtos)\n";
        }
        $texto .= "\n0️⃣ Menu principal";

        return $texto;
    }

    private function processarCategorias(Tenant $tenant, SessaoBot $sessao, string $msg): string
    {
        $categorias = $tenant->categorias()
            ->where('ativo', true)
            ->orderBy('ordem')
            ->withCount(['produtos' => fn($q) => $q->where('disponivel', true)])
            ->get();

        $index = (int) $msg - 1;

        if ($index < 0 || $index >= $categorias->count()) {
            return "Opção inválida. Escolhe um número de 1 a {$categorias->count()} ou *0* para voltar.";
        }

        $categoria = $categorias[$index];

        $sessao->atualizarEstado('produtos_categoria', [
            'categoria_id' => $categoria->id,
            'categoria_nome' => $categoria->nome,
            'pagina' => 1,
        ]);

        return $this->listarProdutos($tenant, $sessao, $categoria->id, 1, $categoria->nome);
    }

    private function listarProdutos(Tenant $tenant, SessaoBot $sessao, int $categoriaId, int $pagina, string $categoriaNome): string
    {
        $porPagina = 5;
        $produtos = $tenant->produtos()
            ->where('categoria_id', $categoriaId)
            ->where('disponivel', true)
            ->orderBy('destaque', 'desc')
            ->orderBy('nome')
            ->skip(($pagina - 1) * $porPagina)
            ->take($porPagina + 1)
            ->get();

        if ($produtos->isEmpty() && $pagina === 1) {
            $sessao->atualizarEstado('inicio');
            return $tenant->mensagem_categoria_vazia ?: "Nenhum produto disponível nesta categoria. 🙁";
        }

        $temMais = $produtos->count() > $porPagina;
        $produtos = $produtos->take($porPagina);

        $texto = "📦 *{$categoriaNome}*\n\n";
        foreach ($produtos as $i => $p) {
            $num = $i + 1;
            $destaque = $p->destaque ? '⭐ ' : '';
            $texto .= "{$num}️⃣ {$destaque}{$p->nome} — {$p->preco} MZN\n";
        }

        if ($temMais) {
            $texto .= "\n➕ Ver mais";
        }
        $texto .= "\n0️⃣ Voltar";

        return $texto;
    }

    private function processarProdutosCategoria(Tenant $tenant, SessaoBot $sessao, string $msg): array|string
    {
        $dados = $sessao->dados;
        $categoriaId = $dados['categoria_id'] ?? null;
        $categoriaNome = $dados['categoria_nome'] ?? 'Produtos';
        $pagina = $dados['pagina'] ?? 1;

        if (!$categoriaId) {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, '');
        }

        if ($msg === '+' || $msg === 'mais' || $msg === 'ver mais') {
            $novaPagina = $pagina + 1;
            /** @var array<string, mixed> $dados */
            $sessao->atualizarEstado('produtos_categoria', array_merge($dados, ['pagina' => $novaPagina]));
            return $this->listarProdutos($tenant, $sessao, $categoriaId, $novaPagina, $categoriaNome);
        }

        $produtos = $tenant->produtos()
            ->where('categoria_id', $categoriaId)
            ->where('disponivel', true)
            ->orderBy('destaque', 'desc')
            ->orderBy('nome')
            ->skip(($pagina - 1) * 5)
            ->take(5)
            ->get();

        $index = (int) $msg - 1;

        if ($index < 0 || $index >= $produtos->count()) {
            return "Opção inválida. Escolhe um número ou *0* para voltar.";
        }

        /** @var Produto $produto */
        $produto = $produtos[$index];

        $sessao->atualizarEstado('produto_detalhe', ['produto_id' => $produto->id]);

        $vendedor = $produto->vendedor ? "\n🏪 {$produto->vendedor->nome}" : '';
        $stock = $produto->stock > 0 ? "📦 Stock: {$produto->stock}" : '⚠️ Sem stock';

        $texto = "🏷️ *{$produto->nome}*\n"
             . ($produto->descricao ? "{$produto->descricao}\n" : '')
             . "💰 {$produto->preco} MZN\n"
             . "{$stock}{$vendedor}\n\n"
             . "1️⃣ Encomendar\n"
             . "0️⃣ Voltar";

        $imagens = array_filter([
            $produto->imagem_url,
            $produto->imagem2_url ?? null,
        ]);

        if (!empty($imagens)) {
            return ['texto' => $texto, 'imagens' => array_values($imagens)];
        }

        return $texto;
    }

    private function processarProdutoDetalhe(Tenant $tenant, SessaoBot $sessao, string $msg, string $numero, string $nome): string
    {
        $dados = $sessao->dados;
        $produtoId = $dados['produto_id'] ?? null;

        if (!$produtoId || $msg !== '1') {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, $nome);
        }

        /** @var Produto|null $produto */
        $produto = $tenant->produtos()->find($produtoId);

        if (!$produto || !$produto->disponivel) {
            $sessao->atualizarEstado('inicio');
            return "Produto não encontrado ou indisponível. 🙁\n\n" . $this->menuPrincipal($tenant, $nome);
        }

        if ($produto->temStock() === false) {
            return "⚠️ Este produto está sem stock no momento. Tenta novamente mais tarde.";
        }

        if ($produto->temCores()) {
            $sessao->atualizarEstado('escolher_cor', ['produto_id' => $produto->id]);
            return $this->montarMensagemCores($produto);
        }

        if ($produto->temTamanhos()) {
            $sessao->atualizarEstado('escolher_tamanho', ['produto_id' => $produto->id]);
            return $this->montarMensagemTamanhos($produto);
        }

        return $this->criarEncomenda($tenant, $sessao, $produto, $numero, $nome);
    }

    private function montarMensagemCores(Produto $produto): string
    {
        $emojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
        $cores = $produto->obterCoresDisponiveis();
        $texto = "Qual a cor pretendida?\n\n";
        foreach ($cores as $i => $cor) {
            $emoji = $emojis[$i] ?? ($i + 1);
            $texto .= "{$emoji} {$cor}\n";
        }
        $texto .= "\n0️⃣ Voltar";
        return $texto;
    }

    private function montarMensagemTamanhos(Produto $produto): string
    {
        $texto = "Qual o tamanho?\n\n";
        $emojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
        $tamanhos = $produto->obterTamanhosDisponiveis();
        foreach ($tamanhos as $i => $tamanho) {
            $emoji = $emojis[$i] ?? ($i + 1);
            $texto .= "{$emoji} {$tamanho}\n";
        }
        $texto .= "\n0️⃣ Voltar";
        return $texto;
    }

    private function processarEscolherCor(Tenant $tenant, SessaoBot $sessao, string $msg): string
    {
        $dados = $sessao->dados;
        $produtoId = $dados['produto_id'] ?? null;

        if (!$produtoId) {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, '');
        }

        /** @var Produto $produto */
        $produto = $tenant->produtos()->find($produtoId);

        if (!$produto) {
            $sessao->atualizarEstado('inicio');
            return "Produto não encontrado.\n\n" . $this->menuPrincipal($tenant, '');
        }

        $cores = $produto->obterCoresDisponiveis();
        $index = (int) $msg - 1;

        if ($index < 0 || $index >= count($cores)) {
            return "Opção inválida. Escolhe um número de 1 a " . count($cores) . " ou *0* para voltar.";
        }

        /** @var array<string, mixed> $dados */
        $novaDados = array_merge($dados, ['cor_escolhida' => $cores[$index]]);

        if ($produto->temTamanhos()) {
            $sessao->atualizarEstado('escolher_tamanho', $novaDados);
            return $this->montarMensagemTamanhos($produto);
        }

        return $this->criarEncomenda($tenant, $sessao, $produto, $sessao->numero_whatsapp, '', $novaDados);
    }

    private function processarEscolherTamanho(Tenant $tenant, SessaoBot $sessao, string $msg): string
    {
        $dados = $sessao->dados;
        $produtoId = $dados['produto_id'] ?? null;

        if (!$produtoId) {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, '');
        }

        /** @var Produto $produto */
        $produto = $tenant->produtos()->find($produtoId);

        if (!$produto) {
            $sessao->atualizarEstado('inicio');
            return "Produto não encontrado.\n\n" . $this->menuPrincipal($tenant, '');
        }

        $tamanhos = $produto->obterTamanhosDisponiveis();
        $index = (int) $msg - 1;

        if ($index < 0 || $index >= count($tamanhos)) {
            return "Opção inválida. Escolhe um número de 1 a " . count($tamanhos) . " ou *0* para voltar.";
        }

        /** @var array<string, mixed> $dados */
        $novaDados = array_merge($dados, ['tamanho_escolhido' => $tamanhos[$index]]);

        return $this->criarEncomenda($tenant, $sessao, $produto, $sessao->numero_whatsapp, '', $novaDados);
    }

    private function criarEncomenda(Tenant $tenant, SessaoBot $sessao, Produto $produto, string $numero, string $nome, array $dadosSessao = []): string
    {
        if (empty($dadosSessao)) {
            $dadosSessao = $sessao->dados;
        }

        $cor = $dadosSessao['cor_escolhida'] ?? null;
        $tamanho = $dadosSessao['tamanho_escolhido'] ?? null;

        $resultado = DB::transaction(function () use ($tenant, $produto, $numero, $nome, $cor, $tamanho) {
            $produtoAtualizado = Produto::lockForUpdate()->find($produto->id);

            if (!$produtoAtualizado) {
                return null;
            }

            $variante = null;
            $precoFinal = $produtoAtualizado->preco;
            $stockDisponivel = $produtoAtualizado->stock;

            if ($produtoAtualizado->temVariantesNovas()) {
                $variante = $produtoAtualizado->obterVariante($cor, $tamanho);

                if (!$variante || !$variante->temStock()) {
                    return null;
                }

                $variante->decrement('stock');
                $precoFinal = $variante->precoFinal();
                $stockDisponivel = $variante->stock;
            } else {
                if ($produtoAtualizado->stock <= 0) {
                    return null;
                }

                $produtoAtualizado->decrement('stock');
            }

            $encomenda = Encomenda::create([
                'tenant_id' => $tenant->id,
                'numero_cliente' => $numero,
                'nome_cliente' => $nome,
                'produto_id' => $produtoAtualizado->id,
                'variante_id' => $variante?->id,
                'cor_escolhida' => $cor,
                'tamanho_escolhido' => $tamanho,
                'vendedor_id' => $produtoAtualizado->vendedor_id,
                'quantidade' => 1,
                'preco_total' => $precoFinal,
                'estado' => 'pendente',
            ]);

            return $encomenda;
        });

        if ($resultado === null) {
            $sessao->atualizarEstado('inicio');
            return "⚠️ Desculpa, o produto esgotou enquanto fazias a encomenda.\n\n"
                 . $this->menuPrincipal($tenant, $nome);
        }

        $encomenda = $resultado;

        if ($encomenda->vendedor) {
            NotificarVendedorJob::dispatch($encomenda->id);
        }

        $sessao->atualizarEstado('inicio');

        $variante = $this->formatarVariante($cor, $tamanho);
        $linhaVariante = $variante ? " — {$variante}" : '';
        $vendedorInfo = $encomenda->vendedor ? "\n📱 O vendedor *{$encomenda->vendedor->nome}* irá contactar-te." : '';

        $mensagemSucesso = $tenant->mensagem_pedido_sucesso ?: "✅ Encomenda feita com sucesso!\n\n"
             . "📋 *{$produto->nome}{$linhaVariante}* — {$encomenda->preco_total} MZN{$vendedorInfo}\n\n"
             . "Obrigado pela preferência! 🙏";

        return $mensagemSucesso;
    }

    private function formatarVariante(?string $cor, ?string $tamanho): string
    {
        $parts = array_filter([$cor, $tamanho]);
        return implode(' · ', $parts);
    }

    private function iniciarPesquisa(SessaoBot $sessao): string
    {
        $sessao->atualizarEstado('pesquisa');
        return "🔍 Escreve o que procuras:";
    }

    private function processarPesquisa(Tenant $tenant, SessaoBot $sessao, string $msg): string
    {
        if (strlen($msg) < 2) {
            return "Escreve pelo menos 2 caracteres para pesquisar.";
        }

        $produtos = $tenant->produtos()
            ->where('disponivel', true)
            ->where(function ($q) use ($msg) {
                $q->where('nome', 'ILIKE', "%{$msg}%")
                  ->orWhere('descricao', 'ILIKE', "%{$msg}%");
            })
            ->limit(5)
            ->get();

        if ($produtos->isEmpty()) {
            return $tenant->mensagem_pesquisa_vazia ?: "Nenhum produto encontrado para \"{$msg}\". Tenta outra palavra.\n\n0️⃣ Menu principal";
        }

        $sessao->atualizarEstado('pesquisa_resultados', [
            'produtos' => $produtos->pluck('id')->toArray(),
        ]);

        $texto = "🔍 Resultados para \"{$msg}\":\n\n";
        foreach ($produtos as $i => $p) {
            $num = $i + 1;
            $cat = $p->categoria ? "[{$p->categoria->nome}] " : '';
            $texto .= "{$num}️⃣ {$cat}{$p->nome} — {$p->preco} MZN\n";
        }
        $texto .= "\nEscolhe o número para ver detalhes.\n0️⃣ Menu principal";

        return $texto;
    }

    private function processarPesquisaResultados(Tenant $tenant, SessaoBot $sessao, string $msg, string $numero, string $nome): array|string
    {
        $dados = $sessao->dados;
        $produtoIds = $dados['produtos'] ?? [];

        if (empty($produtoIds)) {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, $nome);
        }

        $index = (int) $msg - 1;

        if ($index < 0 || $index >= count($produtoIds)) {
            return "Opção inválida. Escolhe um número de 1 a " . count($produtoIds) . " ou *0* para voltar.";
        }

        /** @var Produto|null $produto */
        $produto = $tenant->produtos()->find($produtoIds[$index]);

        if (!$produto) {
            $sessao->atualizarEstado('inicio');
            return "Produto não encontrado. Escreve *menu* para recomeçar.";
        }

        $sessao->atualizarEstado('produto_detalhe', ['produto_id' => $produto->id]);

        $vendedor = $produto->vendedor ? "\n🏪 {$produto->vendedor->nome}" : '';
        $stock = $produto->stock > 0 ? "📦 Stock: {$produto->stock}" : '⚠️ Sem stock';

        $texto = "🏷️ *{$produto->nome}*\n"
             . ($produto->descricao ? "{$produto->descricao}\n" : '')
             . "💰 {$produto->preco} MZN\n"
             . "{$stock}{$vendedor}\n\n"
             . "1️⃣ Encomendar\n"
             . "0️⃣ Voltar";

        $imagens = array_filter([
            $produto->imagem_url,
            $produto->imagem2_url ?? null,
        ]);

        if (!empty($imagens)) {
            return ['texto' => $texto, 'imagens' => array_values($imagens)];
        }

        return $texto;
    }

    private function mostrarEncomendas(Tenant $tenant, SessaoBot $sessao, string $msg, string $nome): string
    {
        $encomendas = $tenant->encomendas()
            ->where('numero_cliente', $sessao->numero_whatsapp)
            ->whereIn('estado', ['pendente', 'confirmada'])
            ->with('produto')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        if ($encomendas->isEmpty()) {
            return "📋 Não tem encomendas activas.\n\n0️⃣ Menu principal";
        }

        $texto = "📋 *As suas encomendas activas:*\n\n";
        foreach ($encomendas as $i => $e) {
            $num = $i + 1;
            $estado = $e->estado === 'pendente' ? '🟡 Pendente' : '🔵 Confirmada';
            $data = $e->created_at->format('d/m/Y');
            $variante = $this->formatarVariante($e->cor_escolhida, $e->tamanho_escolhido);
            $linhaVar = $variante ? " — {$variante}" : '';
            $texto .= "{$num}️⃣ {$e->produto->nome}{$linhaVar} — {$e->preco_total} MZN\n  {$estado} ({$data})\n\n";
        }

        $texto .= "Escolhe o número para ver detalhes e cancelar.\n0️⃣ Menu principal";

        $sessao->atualizarEstado('ver_encomendas');

        return $texto;
    }

    private function processarVerEncomendas(Tenant $tenant, SessaoBot $sessao, string $msg, string $numero): string
    {
        $encomendas = $tenant->encomendas()
            ->where('numero_cliente', $numero)
            ->whereIn('estado', ['pendente', 'confirmada'])
            ->with('produto')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $index = (int) $msg - 1;

        if ($index < 0 || $index >= $encomendas->count()) {
            return "Opção inválida. Escolhe um número de 1 a {$encomendas->count()} ou *0* para voltar.";
        }

        /** @var Encomenda $encomenda */
        $encomenda = $encomendas[$index];

        $sessao->atualizarEstado('detalhe_encomenda', ['encomenda_id' => $encomenda->id]);

        $estado = $encomenda->estado === 'pendente' ? '🟡 Pendente' : '🔵 Confirmada';
        $data = $encomenda->created_at->format('d/m/Y H:i');
        $variante = $this->formatarVariante($encomenda->cor_escolhida, $encomenda->tamanho_escolhido);
        $linhaVar = $variante ? "🎨 {$variante}\n" : '';

        return "📋 *Encomenda #{$encomenda->id}*\n\n"
             . "🏷️ Produto: {$encomenda->produto->nome}\n"
             . $linhaVar
             . "💰 Total: {$encomenda->preco_total} MZN\n"
             . "📊 Estado: {$estado}\n"
             . "📅 Data: {$data}\n\n"
             . ($encomenda->estado === 'pendente'
                 ? "1️⃣ Cancelar encomenda\n0️⃣ Voltar"
                 : "Esta encomenda já não pode ser cancelada.\n0️⃣ Voltar");
    }

    private function processarDetalheEncomenda(Tenant $tenant, SessaoBot $sessao, string $msg, string $numero): string
    {
        $dados = $sessao->dados;
        $encomendaId = $dados['encomenda_id'] ?? null;

        if (!$encomendaId) {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, '');
        }

        /** @var Encomenda|null $encomenda */
        $encomenda = $tenant->encomendas()
            ->with('produto', 'vendedor')
            ->find($encomendaId);

        if (!$encomenda) {
            $sessao->atualizarEstado('inicio');
            return "Encomenda não encontrada.\n\n" . $this->menuPrincipal($tenant, '');
        }

        if ($msg === '1' && $encomenda->estado === 'pendente') {
            $sessao->atualizarEstado('confirmar_cancelamento', ['encomenda_id' => $encomenda->id]);

            $variante = $this->formatarVariante($encomenda->cor_escolhida, $encomenda->tamanho_escolhido);
            $linhaVar = $variante ? " — {$variante}" : '';

            return "⚠️ Tem a certeza que deseja cancelar esta encomenda?\n\n"
                 . "📋 {$encomenda->produto->nome}{$linhaVar} — {$encomenda->preco_total} MZN\n\n"
                 . "1️⃣ Sim, cancelar\n2️⃣ Não, manter";
        }

        $sessao->atualizarEstado('inicio');
        return $this->menuPrincipal($tenant, '');
    }

    private function processarConfirmarCancelamento(Tenant $tenant, SessaoBot $sessao, string $msg, string $numero): string
    {
        $dados = $sessao->dados;
        $encomendaId = $dados['encomenda_id'] ?? null;

        if (!$encomendaId) {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, '');
        }

        if ($msg === '1') {
            /** @var Encomenda|null $resultado */
            $resultado = DB::transaction(function () use ($tenant, $encomendaId) {
                /** @var Encomenda|null $encomenda */
                $encomenda = $tenant->encomendas()
                    ->with('produto', 'vendedor')
                    ->lockForUpdate()
                    ->find($encomendaId);

                if (!$encomenda || $encomenda->estado !== 'pendente') {
                    return null;
                }

                $encomenda->update(['estado' => 'cancelada']);
                $encomenda->produto->increment('stock');

                return $encomenda;
            });

            if ($resultado === null) {
                $sessao->atualizarEstado('inicio');
                return "Esta encomenda já não pode ser cancelada.\n\n" . $this->menuPrincipal($tenant, '');
            }

            $encomenda = $resultado;

            $this->notificarDonoCancelamento($tenant, $encomenda);

            Log::info("Encomenda cancelada pelo cliente via bot", [
                'encomenda_id' => $encomenda->id,
                'tenant_id' => $tenant->id,
                'numero_cliente' => $numero,
            ]);

            $sessao->atualizarEstado('inicio');

            $mensagemCancelamento = $tenant->mensagem_pedido_cancelado ?: "✅ Encomenda #{$encomenda->id} cancelada com sucesso.\n\n"
                 . "O stock foi reposto. Se precisares de algo, estamos cá!\n\n"
                 . $this->menuPrincipal($tenant, '');

            return $mensagemCancelamento;
        }

        if ($msg === '2') {
            $sessao->atualizarEstado('inicio');
            return "Encomenda mantida! ✅\n\n" . $this->menuPrincipal($tenant, '');
        }

        return "Escolhe *1* para cancelar ou *2* para manter.";
    }

    private function notificarDonoCancelamento(Tenant $tenant, Encomenda $encomenda): void
    {
        $dono = $tenant->users()->first();

        if (!$dono) {
            return;
        }

        $instancia = $tenant->instancias()
            ->where('estado', 'conectada')
            ->first();

        if (!$instancia) {
            return;
        }

        $mensagem = "❌ *Encomenda Cancelada*\n"
                  . "👤 Cliente: {$encomenda->nome_cliente}\n"
                  . "📱 Número: {$encomenda->numero_cliente}\n"
                  . "🏷️ Produto: {$encomenda->produto->nome}";

        $variantePartes = array_filter([
            $encomenda->cor_escolhida ? "Cor: {$encomenda->cor_escolhida}" : null,
            $encomenda->tamanho_escolhido ? "Tamanho: {$encomenda->tamanho_escolhido}" : null,
        ]);

        if (!empty($variantePartes)) {
            $mensagem .= "\n🎨 " . implode(' · ', $variantePartes);
        }

        $mensagem .= "\n💰 Total: {$encomenda->preco_total} MZN\n"
                   . "🕐 " . now()->format('d/m/Y H:i');

        $numeroDestino = $dono->telefone ?? $encomenda->vendedor?->numero_whatsapp;

        if ($numeroDestino) {
            try {
                $this->wahaService->enviarMensagem($tenant->id, $numeroDestino, $mensagem);
            } catch (\Exception $e) {
                Log::error("Erro ao notificar dono sobre cancelamento: " . $e->getMessage());
            }
        }
    }

    private function mostrarVendedores(Tenant $tenant, SessaoBot $sessao): string
    {
        $vendedores = $tenant->vendedores()->where('ativo', true)->get();

        if ($vendedores->isEmpty()) {
            return $tenant->mensagem_vendedores_indisponivel ?: "Ainda não há vendedores disponíveis.\n\n0️⃣ Menu principal";
        }

        $texto = "🏪 *Escolha um vendedor para falar:*\n\n";
        foreach ($vendedores as $i => $v) {
            $num = $i + 1;
            $desc = $v->descricao ? " — {$v->descricao}" : '';
            $texto .= "{$num}️⃣ *{$v->nome}*{$desc}\n";
        }
        $texto .= "\n0️⃣ Menu principal";

        $sessao->atualizarEstado('escolher_vendedor');

        return $texto;
    }

    private function processarEscolherVendedor(Tenant $tenant, SessaoBot $sessao, string $msg, string $numero, string $nome): string
    {
        $vendedores = $tenant->vendedores()->where('ativo', true)->get();

        $index = (int) $msg - 1;

        if ($index < 0 || $index >= $vendedores->count()) {
            return "Opção inválida. Escolhe um número de 1 a {$vendedores->count()} ou *0* para voltar.";
        }

        /** @var Vendedor $vendedor */
        $vendedor = $vendedores[$index];

        $this->notificarVendedor($tenant, $vendedor, $numero, $nome);

        $sessao->atualizarEstado('transferido_vendedor', [
            'vendedor_id' => $vendedor->id,
            'vendedor_nome' => $vendedor->nome,
            'cliente_numero' => $numero,
            'cliente_nome' => $nome,
        ]);

        $mensagemTransferencia = $tenant->mensagem_transferencia ?: "✅ A sua conversa foi encaminhada para *{$vendedor->nome}*.\n\n"
             . "📱 Ele irá contactar-te no número *{$numero}* em breve.\n\n"
             . "Escreve *menu* para voltar ao menu principal.";

        return $mensagemTransferencia;
    }

    private function notificarVendedor(Tenant $tenant, Vendedor $vendedor, string $numeroCliente, string $nomeCliente): void
    {
        $mensagem = "📞 *Novo pedido de atendimento*\n\n"
                  . "👤 Cliente: " . ($nomeCliente ?: 'Não informado') . "\n"
                  . "📱 Número: {$numeroCliente}\n\n"
                  . "Por favor, entre em contacto com o cliente.";

        try {
            $this->wahaService->enviarMensagem($tenant->id, $vendedor->numero_whatsapp, $mensagem);
        } catch (\Exception $e) {
            Log::error("Erro ao notificar vendedor: " . $e->getMessage());
        }
    }

    private function processarTransferidoVendedor(Tenant $tenant, SessaoBot $sessao, string $msg, string $numero, string $nome): string
    {
        if ($msg === 'menu' || $msg === '0' || $msg === 'voltar') {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, $nome);
        }

        $vendedorNome = $sessao->dados['vendedor_nome'] ?? 'o vendedor';
        
        $mensagemTransferencia = $tenant->mensagem_transferencia ?: "⏳ A sua conversa está encaminhada para *{$vendedorNome}*.\n\n"
             . "Ele irá responder-te em breve. Escreve *menu* para voltar ao menu principal.";

        return $mensagemTransferencia;
    }
}
