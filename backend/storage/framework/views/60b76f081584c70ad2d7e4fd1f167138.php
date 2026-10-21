
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Peminjam - Sistem Peminjaman Alat</title>

    <!-- Memuat Tailwind CSS melalui CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    
    <?php echo $__env->make('partials.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body class="motion-page bg-gray-100 flex items-center justify-center min-h-screen px-4 py-8">

    
    <div class="fixed top-4 right-4 z-50">
        <?php echo $__env->make('partials.theme-toggle', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    <!-- Card Registrasi -->
    <div class="motion-login-card bg-white p-8 rounded-lg shadow-md w-full max-w-sm">

        <h3 class="text-2xl font-bold text-center text-gray-800 mb-2">
            Registrasi Peminjam
        </h3>

        <p class="text-sm text-gray-500 text-center mb-6">
            Buat akun untuk mulai meminjam alat.
        </p>

        <!-- Alert Error Validasi -->
        <?php if($errors->any()): ?>
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                <ul class="list-disc pl-5">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form
            class="motion-login-form"
            action="<?php echo e(route('register')); ?>"
            method="POST"
        >
            <?php echo csrf_field(); ?>

            <!-- Nama Lengkap -->
            <div class="motion-field mb-4">
                <label for="name" class="block text-gray-700 text-sm font-semibold mb-2">
                    Nama Lengkap
                </label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="<?php echo e(old('name')); ?>"
                    required
                    maxlength="255"
                    autocomplete="name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Email -->
            <div class="motion-field mb-4">
                <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">
                    Email
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?php echo e(old('email')); ?>"
                    required
                    maxlength="255"
                    autocomplete="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Password -->
            <div class="motion-field mb-4">
                <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">
                    Password
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p class="text-xs text-gray-500 mt-1">
                    Minimal 8 karakter.
                </p>
            </div>

            <!-- Konfirmasi Password -->
            <div class="motion-field mb-6">
                <label for="password_confirmation" class="block text-gray-700 text-sm font-semibold mb-2">
                    Konfirmasi Password
                </label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Tombol Registrasi -->
            <button
                type="submit"
                class="motion-submit w-full bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition duration-200"
            >
                Daftar
            </button>
        </form>

        <!-- Link Login -->
        <p class="text-sm text-center text-gray-600 mt-6">
            Sudah punya akun?
            <a href="<?php echo e(route('login')); ?>" class="text-blue-600 hover:text-blue-700 font-semibold">
                Masuk di sini
            </a>
        </p>

    </div>

    <?php echo $__env->make('partials.motion', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</body>
</html>
<?php /**PATH /var/www/resources/views/auth/register.blade.php ENDPATH**/ ?>