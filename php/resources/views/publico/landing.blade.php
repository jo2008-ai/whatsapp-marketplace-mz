<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Marketplace — Vende pelo WhatsApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-blue-600">WhatsApp Marketplace</h1>
            <div class="flex gap-3">
                <a href="/login" class="px-4 py-2 text-gray-700 hover:text-blue-600 font-medium">Entrar</a>
                <a href="/registar" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Criar Loja</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="bg-gradient-to-br from-blue-600 to-purple-700 text-white">
        <div class="max-w-6xl mx-auto px-4 py-20 text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">Vende pelo WhatsApp<br>em minutos</h2>
            <p class="text-xl text-blue-100 mb-10 max-w-2xl mx-auto">
                Cria o bot WhatsApp da tua loja. Catálogo automático, encomendas instantâneas, notificações em tempo real.
            </p>
            <a href="/registar" class="inline-block bg-white text-blue-600 px-8 py-4 rounded-xl text-lg font-bold hover:bg-blue-50 shadow-lg">
                Começar Grátis — 7 dias
            </a>
            <p class="text-blue-200 text-sm mt-4">Sem cartão de crédito. Cancela quando quiseres.</p>
        </div>
    </section>

    <!-- Como funciona -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4">
            <h3 class="text-3xl font-bold text-center text-gray-800 mb-12">Como funciona</h3>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">📱</span>
                    </div>
                    <h4 class="text-lg font-bold text-gray-800 mb-2">1. Liga o WhatsApp</h4>
                    <p class="text-gray-600">Escaneia o QR code com o teu WhatsApp. Sem precisar de segundo telemóvel.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">📦</span>
                    </div>
                    <h4 class="text-lg font-bold text-gray-800 mb-2">2. Adiciona produtos</h4>
                    <p class="text-gray-600">Cria categorias, adiciona produtos com fotos, preços e stock.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🤖</span>
                    </div>
                    <h4 class="text-lg font-bold text-gray-800 mb-2">3. Bot responde sozinho</h4>
                    <p class="text-gray-600">Clientes enviam "Olá" → bot mostra catálogo → fazem encomenda. Tu só entregas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-20">
        <div class="max-w-6xl mx-auto px-4">
            <h3 class="text-3xl font-bold text-center text-gray-800 mb-12">Tudo o que precisas</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="flex gap-4 p-4 rounded-xl hover:bg-gray-50">
                    <span class="text-2xl">🏷️</span>
                    <div>
                        <h4 class="font-bold text-gray-800">Catálogo com categorias</h4>
                        <p class="text-gray-600 text-sm">Organiza produtos por categoria. Clientes navegam facilmente.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 rounded-xl hover:bg-gray-50">
                    <span class="text-2xl">🔔</span>
                    <div>
                        <h4 class="font-bold text-gray-800">Notificações instantâneas</h4>
                        <p class="text-gray-600 text-sm">Recebe notificação no WhatsApp quando há nova encomenda.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 rounded-xl hover:bg-gray-50">
                    <span class="text-2xl">📊</span>
                    <div>
                        <h4 class="font-bold text-gray-800">Painel de controlo</h4>
                        <p class="text-gray-600 text-sm">Dashboard com estatísticas, gráficos, gestão completa.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 rounded-xl hover:bg-gray-50">
                    <span class="text-2xl">📱</span>
                    <div>
                        <h4 class="font-bold text-gray-800">App móvel</h4>
                        <p class="text-gray-600 text-sm">Gere a loja a partir do telemóvel com a app React Native.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 rounded-xl hover:bg-gray-50">
                    <span class="text-2xl">👥</span>
                    <div>
                        <h4 class="font-bold text-gray-800">Múltiplos vendedores</h4>
                        <p class="text-gray-600 text-sm">Cada vendedor com o seu número. Notificações separadas.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 rounded-xl hover:bg-gray-50">
                    <span class="text-2xl">🔍</span>
                    <div>
                        <h4 class="font-bold text-gray-800">Pesquisa inteligente</h4>
                        <p class="text-gray-600 text-sm">Clientes pesquisam por nome do produto directamente no chat.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Preços -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4">
            <h3 class="text-3xl font-bold text-center text-gray-800 mb-4">Planos e Preços</h3>
            <p class="text-center text-gray-500 mb-12">Escolhe o plano ideal para o teu negócio</p>

            <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <!-- Basic -->
                <div class="bg-white rounded-2xl shadow p-8 border-2 border-gray-100">
                    <h4 class="text-lg font-bold text-gray-800">Basic</h4>
                    <div class="my-4">
                        <span class="text-3xl font-bold text-gray-800">500</span>
                        <span class="text-gray-500"> MZN/mês</span>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-600 mb-8">
                        <li>✅ 50 produtos</li>
                        <li>✅ 1 número WhatsApp</li>
                        <li>✅ Bot automático</li>
                        <li>✅ Painel admin</li>
                        <li>✅ Notificações</li>
                    </ul>
                    <a href="/registar?plano=basic" class="block text-center py-3 border-2 border-blue-600 text-blue-600 rounded-lg font-bold hover:bg-blue-50">
                        Começar
                    </a>
                </div>

                <!-- Pro -->
                <div class="bg-white rounded-2xl shadow-lg p-8 border-2 border-blue-600 relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs px-4 py-1 rounded-full font-bold">Popular</span>
                    <h4 class="text-lg font-bold text-gray-800">Pro</h4>
                    <div class="my-4">
                        <span class="text-3xl font-bold text-blue-600">1.500</span>
                        <span class="text-gray-500"> MZN/mês</span>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-600 mb-8">
                        <li>✅ 500 produtos</li>
                        <li>✅ 3 números WhatsApp</li>
                        <li>✅ Bot automático</li>
                        <li>✅ Painel admin</li>
                        <li>✅ App móvel</li>
                        <li>✅ Suporte prioritário</li>
                    </ul>
                    <a href="/registar?plano=pro" class="block text-center py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700">
                        Começar
                    </a>
                </div>

                <!-- Enterprise -->
                <div class="bg-white rounded-2xl shadow p-8 border-2 border-gray-100">
                    <h4 class="text-lg font-bold text-gray-800">Enterprise</h4>
                    <div class="my-4">
                        <span class="text-3xl font-bold text-gray-800">5.000</span>
                        <span class="text-gray-500"> MZN/mês</span>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-600 mb-8">
                        <li>✅ Produtos ilimitados</li>
                        <li>✅ Números ilimitados</li>
                        <li>✅ Bot personalizado</li>
                        <li>✅ API completa</li>
                        <li>✅ Suporte dedicado</li>
                        <li>✅ Marca personalizada</li>
                    </ul>
                    <a href="/registar?plano=enterprise" class="block text-center py-3 border-2 border-blue-600 text-blue-600 rounded-lg font-bold hover:bg-blue-50">
                        Começar
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-20 bg-blue-600 text-white text-center">
        <div class="max-w-3xl mx-auto px-4">
            <h3 class="text-3xl font-bold mb-4">Começa a vender hoje</h3>
            <p class="text-blue-100 text-lg mb-8">7 dias grátis. Sem compromisso. Configuração em 5 minutos.</p>
            <a href="/registar" class="inline-block bg-white text-blue-600 px-8 py-4 rounded-xl text-lg font-bold hover:bg-blue-50">
                Criar a Minha Loja
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <p class="mb-4">WhatsApp Marketplace SaaS</p>
            <div class="flex justify-center gap-6 text-sm">
                <a href="/login" class="hover:text-white">Entrar</a>
                <a href="/registar" class="hover:text-white">Registar</a>
            </div>
        </div>
    </footer>
</body>
</html>
