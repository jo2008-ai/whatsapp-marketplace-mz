<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Loja — WhatsApp Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">WhatsApp Marketplace</h1>
            <p class="text-gray-500">Cria o bot WhatsApp da tua loja em minutos</p>
        </div>

        <!-- Planos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-5 border-2 border-gray-200" id="plano-basic">
                <h3 class="text-lg font-bold text-gray-800">Basic</h3>
                <div class="text-2xl font-bold text-blue-600 my-2">500 MZN<span class="text-sm font-normal text-gray-400">/mês</span></div>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✅ 50 produtos</li>
                    <li>✅ 1 número WhatsApp</li>
                    <li>✅ Bot com catálogo</li>
                    <li>✅ Painel admin</li>
                </ul>
            </div>
            <div class="bg-white rounded-xl shadow p-5 border-2 border-blue-500 relative" id="plano-pro">
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs px-3 py-0.5 rounded-full">Popular</span>
                <h3 class="text-lg font-bold text-gray-800">Pro</h3>
                <div class="text-2xl font-bold text-blue-600 my-2">1.500 MZN<span class="text-sm font-normal text-gray-400">/mês</span></div>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✅ 500 produtos</li>
                    <li>✅ 3 números WhatsApp</li>
                    <li>✅ Bot com catálogo</li>
                    <li>✅ Painel admin</li>
                    <li>✅ Prioridade suporte</li>
                </ul>
            </div>
            <div class="bg-white rounded-xl shadow p-5 border-2 border-gray-200" id="plano-enterprise">
                <h3 class="text-lg font-bold text-gray-800">Enterprise</h3>
                <div class="text-2xl font-bold text-blue-600 my-2">Sob consulta</div>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✅ Produtos ilimitados</li>
                    <li>✅ Números ilimitados</li>
                    <li>✅ Bot personalizado</li>
                    <li>✅ Suporte dedicado</li>
                </ul>
            </div>
        </div>

        <!-- Formulário -->
        <div class="bg-white rounded-xl shadow p-6 max-w-xl mx-auto">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Registar a tua loja</h2>

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc pl-4">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="/registar">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Loja *</label>
                        <input type="text" name="nome_loja" value="{{ old('nome_loja') }}" required
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="text" name="telefone" value="{{ old('telefone') }}"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="+25884XXXXXXX">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                        <input type="password" name="password" required
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Password *</label>
                        <input type="password" name="password_confirmation" required
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plano *</label>
                        <select name="plano" required class="w-full px-3 py-2 border rounded-lg"
                            onchange="document.querySelectorAll('[id^=plano-]').forEach(e => e.classList.remove('border-blue-500')); document.getElementById('plano-'+this.value).classList.add('border-blue-500');">
                            <option value="basic" {{ old('plano') === 'basic' ? 'selected' : '' }}>Basic — 500 MZN/mês</option>
                            <option value="pro" {{ old('plano') === 'pro' ? 'selected' : '' }}>Pro — 1.500 MZN/mês</option>
                            <option value="enterprise" {{ old('plano') === 'enterprise' ? 'selected' : '' }}>Enterprise — Sob consulta</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-semibold">
                        Criar Conta — 7 dias grátis
                    </button>
                </div>
            </form>

            <p class="text-center text-sm text-gray-500 mt-4">
                Já tens conta? <a href="/login" class="text-blue-600 hover:underline">Entrar</a>
            </p>
        </div>
    </div>
</body>
</html>
