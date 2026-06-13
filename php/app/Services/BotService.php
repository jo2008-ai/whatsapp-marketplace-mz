<?php

namespace App\Services;

use App\Jobs\NotificarVendedorJob;
use App\Models\Categoria;
use App\Models\Encomenda;
use App\Models\Produto;
use App\Models\SessaoBot;
use App\Models\Tenant;
use App\Models\Vendedor;
use Illuminate\Support\Facades\Log;

class BotService
{
    private NotificacaoService $notificacaoService;

    public function __construct(NotificacaoService $notificacaoService)
    {
        $this->notificacaoService = $notificacaoService;
    }

    public function responder(Tenant $tenant, string $numero, string $mensagem, string $nome = ''): array|string
    {
        if (!$tenant->activo) {
            return 'Serviço temporariamente indisponível. Contacta o suporte.';
        }

        $sessao = SessaoBot::obter($tenant->id, $numero);
        $msg = mb_strtolower(trim($mensagem));

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
            default => $this->menuPrincipal($tenant, $nome),
        };
    }

    private function menuPrincipal(Tenant $tenant, string $nome): string
    {
        $saudacao = $nome ? "Olá {$nome}!" : 'Olá!';

        if ($tenant->mensagem_boas_vindas) {
            return $tenant->mensagem_boas_vindas;
        }

        return "{$saudacao} 👋 Bem-vindo(a) à *{$tenant->nome_loja}*!\n\n"
             . "1️⃣ Ver produtos por categoria\n"
             . "2️⃣ Pesquisar produto\n"
             . "3️⃣ As minhas encomendas\n"
             . "4️⃣ Falar com vendedor";
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
            '4' => $this->mostrarVendedores($tenant),
            default => "Não entendi 😅 Escreve o número da opção ou *menu* para recomeçar.\n\n"
                      . "1️⃣ Ver produtos por categoria\n"
                      . "2️⃣ Pesquisar produto\n"
                      . "3️⃣ As minhas encomendas\n"
                      . "4️⃣ Falar com vendedor",
        };
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
            return "Ainda não há categorias disponíveis. Volta mais tarde! 🙂";
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
            return "Nenhum produto disponível nesta categoria. 🙁";
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

        $produto = $tenant->produtos()->find($produtoId);

        if (!$produto || !$produto->disponivel) {
            $sessao->atualizarEstado('inicio');
            return "Produto não encontrado ou indisponível. 🙁\n\n" . $this->menuPrincipal($tenant, $nome);
        }

        if ($produto->stock <= 0) {
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
        $texto = "Qual a cor pretendida?\n\n";
        foreach ($produto->cores as $i => $cor) {
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
        foreach ($produto->tamanhos as $i => $tamanho) {
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

        $produto = $tenant->produtos()->find($produtoId);

        if (!$produto) {
            $sessao->atualizarEstado('inicio');
            return "Produto não encontrado.\n\n" . $this->menuPrincipal($tenant, '');
        }

        $index = (int) $msg - 1;

        if ($index < 0 || $index >= count($produto->cores)) {
            return "Opção inválida. Escolhe um número de 1 a " . count($produto->cores) . " ou *0* para voltar.";
        }

        $novaDados = array_merge($dados, ['cor_escolhida' => $produto->cores[$index]]);

        if ($produto->temTamanhos()) {
            $sessao->atualizarEstado('escolher_tamanho', $novaDados);
            return $this->montarMensagemTamanhos($produto);
        }

        return $this->criarEncomenda($tenant, $sessao, $produto, '', '', $novaDados);
    }

    private function processarEscolherTamanho(Tenant $tenant, SessaoBot $sessao, string $msg): string
    {
        $dados = $sessao->dados;
        $produtoId = $dados['produto_id'] ?? null;

        if (!$produtoId) {
            $sessao->atualizarEstado('inicio');
            return $this->menuPrincipal($tenant, '');
        }

        $produto = $tenant->produtos()->find($produtoId);

        if (!$produto) {
            $sessao->atualizarEstado('inicio');
            return "Produto não encontrado.\n\n" . $this->menuPrincipal($tenant, '');
        }

        $index = (int) $msg - 1;

        if ($index < 0 || $index >= count($produto->tamanhos)) {
            return "Opção inválida. Escolhe um número de 1 a " . count($produto->tamanhos) . " ou *0* para voltar.";
        }

        $novaDados = array_merge($dados, ['tamanho_escolhido' => $produto->tamanhos[$index]]);

        return $this->criarEncomenda($tenant, $sessao, $produto, '', '', $novaDados);
    }

    private function criarEncomenda(Tenant $tenant, SessaoBot $sessao, Produto $produto, string $numero, string $nome, array $dadosSessao = []): string
    {
        if (empty($dadosSessao)) {
            $dadosSessao = $sessao->dados;
        }

        $cor = $dadosSessao['cor_escolhida'] ?? null;
        $tamanho = $dadosSessao['tamanho_escolhido'] ?? null;

        $encomenda = Encomenda::create([
            'tenant_id' => $tenant->id,
            'numero_cliente' => $numero,
            'nome_cliente' => $nome,
            'produto_id' => $produto->id,
            'cor_escolhida' => $cor,
            'tamanho_escolhido' => $tamanho,
            'vendedor_id' => $produto->vendedor_id,
            'quantidade' => 1,
            'preco_total' => $produto->preco,
            'estado' => 'pendente',
        ]);

        $produto->decrement('stock');

        if ($encomenda->vendedor) {
            NotificarVendedorJob::dispatch($encomenda->id);
        }

        $sessao->atualizarEstado('inicio');

        $variante = $this->formatarVariante($cor, $tamanho);
        $linhaVariante = $variante ? " — {$variante}" : '';
        $vendedorInfo = $encomenda->vendedor ? "\n📱 O vendedor *{$encomenda->vendedor->nome}* irá contactar-te." : '';

        return "✅ Encomenda feita com sucesso!\n\n"
             . "📋 *{$produto->nome}{$linhaVariante}* — {$produto->preco} MZN{$vendedorInfo}\n\n"
             . "Obrigado pela preferência! 🙏";
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
            return "Nenhum produto encontrado para \"{$msg}\". Tenta outra palavra.\n\n0️⃣ Menu principal";
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
            $encomenda = $tenant->encomendas()
                ->with('produto', 'vendedor')
                ->find($encomendaId);

            if (!$encomenda || $encomenda->estado !== 'pendente') {
                $sessao->atualizarEstado('inicio');
                return "Esta encomenda já não pode ser cancelada.\n\n" . $this->menuPrincipal($tenant, '');
            }

            $encomenda->update(['estado' => 'cancelada']);

            $encomenda->produto->increment('stock');

            $this->notificarDonoCancelamento($tenant, $encomenda);

            Log::info("Encomenda cancelada pelo cliente via bot", [
                'encomenda_id' => $encomenda->id,
                'tenant_id' => $tenant->id,
                'numero_cliente' => $numero,
            ]);

            $sessao->atualizarEstado('inicio');

            return "✅ Encomenda #{$encomenda->id} cancelada com sucesso.\n\n"
                 . "O stock foi reposto. Se precisares de algo, estamos cá!\n\n"
                 . $this->menuPrincipal($tenant, '');
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

        try {
            \Illuminate\Support\Facades\Http::timeout(10)->post(
                config('services.python.url') . '/enviar',
                [
                    'tenant_id' => $tenant->id,
                    'numero' => $dono->telefone ?? $encomenda->vendedor?->numero_whatsapp,
                    'mensagem' => $mensagem,
                    'instance_name' => 'default',
                ]
            );
        } catch (\Exception $e) {
            Log::error("Erro ao notificar dono sobre cancelamento: " . $e->getMessage());
        }
    }

    private function mostrarVendedores(Tenant $tenant): string
    {
        $vendedores = $tenant->vendedores()->where('ativo', true)->get();

        if ($vendedores->isEmpty()) {
            return "Ainda não há vendedores disponíveis.\n\n0️⃣ Menu principal";
        }

        $texto = "🏪 *Vendedores:*\n\n";
        foreach ($vendedores as $v) {
            $desc = $v->descricao ? " — {$v->descricao}" : '';
            $texto .= "• *{$v->nome}*{$desc}\n  📱 {$v->numero_whatsapp}\n\n";
        }

        $texto .= "0️⃣ Menu principal";
        return $texto;
    }
}
