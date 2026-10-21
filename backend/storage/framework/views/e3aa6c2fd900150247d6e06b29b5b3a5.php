<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Peminjaman Alat</title>

    <!-- Memuat Tailwind CSS melalui CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    
    <?php echo $__env->make('partials.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body class="motion-page bg-gray-100 flex items-center justify-center min-h-screen px-4">

    
    <div class="fixed top-4 right-4 z-50">
        <?php echo $__env->make('partials.theme-toggle', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    <!-- Card Container -->
    <div class="motion-login-card bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
        <h3 class="text-2xl font-bold text-center text-gray-800 mb-6">
            Login Sistem
        </h3>

        
        <!-- Alert Registrasi Berhasil -->
        <?php if(session('success')): ?>
            <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded text-sm">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>


        <!-- Alert Error Session -->
        <?php if(session('error')): ?>
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

        <!-- Alert Error Validasi -->
        <?php if($errors->any()): ?>
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                <ul class="list-disc pl-5 mb-0">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="motion-login-form" action="<?php echo e(route('login')); ?>" method="POST">
            <?php echo csrf_field(); ?>

            <!-- Input Email -->
            <div class="motion-field mb-4">
                <label class="block text-gray-700 text-sm font-semibold mb-2">
                    Email
                </label>
                <input
                    type="email"
                    name="email"
                    value="<?php echo e(old('email')); ?>"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Input Password -->
            <div class="motion-field mb-6">
                <label class="block text-gray-700 text-sm font-semibold mb-2">
                    Password
                </label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Tombol Submit -->
            <button
                type="submit"
                class="motion-submit w-full bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition duration-200"
            >
                Masuk
            </button>
        </form>
        
        <!-- Link Registrasi -->
        <p class="text-sm text-center text-gray-600 mt-6">
            Belum punya akun?
            <a href="<?php echo e(route('register')); ?>" class="text-blue-600 hover:text-blue-700 font-semibold">
                Daftar sebagai Peminjam
            </a>
        </p>

    </div>

    <?php echo $__env->make('partials.motion', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</body>
</html><?php /**PATH /var/www/resources/views/auth/login.blade.php ENDPATH**/ ?>