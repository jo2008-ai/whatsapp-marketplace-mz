<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — WhatsApp Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-600 to-purple-700 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">WhatsApp Marketplace</h1>
            <p class="text-gray-500 mt-1">Acede ao teu painel</p>
        </div>

        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <form method="POST" action="/login" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código de acesso</label>
                <input type="text" name="login_code" value="{{ old('login_code') }}" required
                       maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                       placeholder="000000"
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-center text-2xl tracking-[0.5em] font-mono @error('login_code') border-red-500 @enderror">
                @error('login_code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-semibold">
                Entrar
            </button>
        </form>

        <p class="text-center mt-6 text-sm text-gray-500">
            Contacta o administrador para obter o teu código de acesso.
        </p>
    </div>

    <script>
        document.querySelector('input[name="login_code"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
