<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'Painel') — {{ $tenant->nome_loja ?? 'Marketplace' }}</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ $tenant->cor_primaria ?? '#2563EB' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Marketplace">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ preg_replace('/[^#a-fA-F0-9]/', '', $tenant->cor_primaria ?? "#2563EB") }}',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-white shadow-lg fixed h-full z-30 transform -translate-x-full lg:translate-x-0 transition-transform">
            <div class="p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800 truncate">{{ $tenant->nome_loja ?? 'Marketplace' }}</h2>
                <span class="text-xs px-2 py-1 rounded-full
                    {{ ($tenant->plano ?? '') === 'enterprise' ? 'bg-purple-100 text-purple-700' : (($tenant->plano ?? '') === 'pro' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                    {{ ucfirst($tenant->plano ?? 'basic') }}
                </span>
            </div>
            <nav class="p-4 space-y-1">
                <a href="/painel" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 {{ request()->is('painel') && !request()->is('painel/*') ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700' }}">
                    📊 Dashboard
                </a>
                <a href="/painel/produtos" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 {{ request()->is('painel/produtos*') ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700' }}">
                    📦 Produtos
                </a>
                <a href="/painel/categorias" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 {{ request()->is('painel/categorias*') ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700' }}">
                    🏷️ Categorias
                </a>
                <a href="/painel/vendedores" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 {{ request()->is('painel/vendedores*') ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700' }}">
                    👥 Vendedores
                </a>
                <a href="/painel/encomendas" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 {{ request()->is('painel/encomendas*') ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700' }}">
                    📋 Encomendas
                </a>
                <a href="/painel/whatsapp" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 {{ request()->is('painel/whatsapp*') ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700' }}">
                    💬 WhatsApp
                </a>
                <a href="/painel/definicoes" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 {{ request()->is('painel/definicoes*') ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700' }}">
                    ⚙️ Definições
                </a>
                <a href="/painel/plano" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 {{ request()->is('painel/plano*') ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700' }}">
                    💎 Plano
                    @if(($tenant->plano ?? 'basic') === 'basic')
                        <span class="ml-auto px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">Upgrade</span>
                    @endif
                </a>
            </nav>
            <div class="absolute bottom-0 w-full p-4 border-t">
                <form method="POST" action="/logout">
                    @csrf
                    <button class="w-full text-left px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg">
                        🚪 Sair
                    </button>
                </form>
            </div>
        </aside>

        <!-- Overlay mobile -->
        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

        <!-- Main -->
        <div class="flex-1 lg:ml-64">
            <!-- Top bar -->
            <header class="bg-white shadow-sm p-4 flex items-center justify-between sticky top-0 z-10">
                <button onclick="toggleSidebar()" class="lg:hidden text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-xl font-semibold text-gray-800">@yield('title', 'Painel')</h1>
                <span class="text-sm text-gray-500">{{ auth()->user()->name ?? '' }}</span>
            </header>

            <!-- Alerts -->
            @if(session('success'))
                <div class="mx-4 mt-4 p-3 bg-green-100 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mx-4 mt-4 p-3 bg-red-100 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

            @if(isset($tenant) && $tenant->estado === 'trial' && $tenant->trial_termina_em && $tenant->trial_termina_em->diffInDays() < 3)
                <div class="mx-4 mt-4 p-3 bg-yellow-100 text-yellow-800 rounded-lg text-sm">
                    ⚠️ Trial expira em {{ $tenant->trial_termina_em->diffForHumans() }}. Renova a subscrição para continuar.
                </div>
            @endif

            @if(isset($tenant) && !$tenant->ativo() && $tenant->estado !== 'trial')
                <div class="mx-4 mt-4 p-3 bg-red-100 text-red-800 rounded-lg text-sm">
                    🔴 Subscrição expirada. O painel será bloqueado em breve.
                </div>
            @endif

            <!-- Content -->
            <main class="p-4">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('overlay').classList.toggle('hidden');
        }
    </script>
    @stack('scripts')

    <script>
        // PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((reg) => console.log('SW registered:', reg.scope))
                    .catch((err) => console.log('SW error:', err));
            });
        }
    </script>
</body>
</html>
