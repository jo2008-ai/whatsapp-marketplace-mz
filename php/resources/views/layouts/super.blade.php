<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Super Admin') — Marketplace SaaS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-gray-900 text-white fixed h-full z-30 transform -translate-x-full lg:translate-x-0 transition-transform">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-lg font-bold">🛠️ Super Admin</h2>
                <p class="text-xs text-gray-400">WhatsApp Marketplace SaaS</p>
            </div>
            <nav class="p-4 space-y-1">
                <a href="/super" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->is('super') && !request()->is('super/*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">
                    📊 Dashboard
                </a>
                <a href="/super/lojas" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->is('super/lojas*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">
                    🏪 Lojas
                </a>
                <a href="/super/lojas/criar" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->is('super/lojas/criar') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">
                    ➕ Criar Loja
                </a>
                <a href="/super/receita" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->is('super/receita*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">
                    💰 Receita
                </a>
                <a href="/super/instancias" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->is('super/instancias*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">
                    📱 Instâncias WhatsApp
                </a>
            </nav>
            <div class="absolute bottom-0 w-full p-4 border-t border-gray-700">
                <form method="POST" action="/logout">
                    @csrf
                    <button class="w-full text-left px-3 py-2 text-red-400 hover:bg-gray-800 rounded-lg">
                        🚪 Sair
                    </button>
                </form>
            </div>
        </aside>

        <!-- Overlay mobile -->
        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

        <!-- Main -->
        <div class="flex-1 lg:ml-64">
            <header class="bg-white shadow-sm p-4 flex items-center justify-between sticky top-0 z-10">
                <button onclick="toggleSidebar()" class="lg:hidden text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-xl font-semibold text-gray-800">@yield('title', 'Super Admin')</h1>
                <span class="text-sm text-gray-500">{{ auth()->user()->name ?? '' }}</span>
            </header>

            @if(session('success'))
                <div class="mx-4 mt-4 p-3 bg-green-100 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mx-4 mt-4 p-3 bg-red-100 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

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
</body>
</html>
