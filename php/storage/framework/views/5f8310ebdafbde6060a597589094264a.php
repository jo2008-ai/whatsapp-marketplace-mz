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

        <?php if(session('error')): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm"><?php echo e(session('error')); ?></div>
        <?php endif; ?>

        <form method="POST" action="/login" class="space-y-4">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="<?php echo e(old('email')); ?>" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="remember" id="remember" class="rounded">
                <label for="remember" class="ml-2 text-sm text-gray-600">Lembrar-me</label>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-semibold">
                Entrar
            </button>
        </form>

        <p class="text-center mt-6 text-sm text-gray-500">
            Não tens conta? <a href="/registar" class="text-blue-600 hover:underline font-medium">Registar loja</a>
        </p>
    </div>
</body>
</html>
<?php /**PATH /home/mozdv/projectos/whatsapp-marketplace-saas/php/resources/views/auth/login.blade.php ENDPATH**/ ?>