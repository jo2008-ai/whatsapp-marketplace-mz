<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci Password — WhatsApp Marketplace</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="bg-gradient-to-br from-blue-600 to-purple-700 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">WhatsApp Marketplace</h1>
            <p class="text-gray-500 mt-1">Repor password</p>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <form method="POST" action="/esqueci-password" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-semibold">
                Enviar link de reposição
            </button>
        </form>

        <p class="text-center mt-6 text-sm text-gray-500">
            <a href="/login" class="text-blue-600 hover:underline font-medium">Voltar ao login</a>
        </p>
    </div>
</body>
</html>
