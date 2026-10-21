<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $__env->yieldContent('title', 'Peminjam - Sistem Peminjaman Alat'); ?></title>

    
    <?php echo $__env->make('partials.theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <script src="https://cdn.tailwindcss.com"></script>

    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 antialiased">

    
    
    

    <header class="motion-topbar sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="h-16 flex items-center justify-between">

                
                <a href="<?php echo e(route('peminjam.katalog')); ?>"
                   class="flex items-center gap-3">

                    <div class="w-10 h-10 bg-blue-600 rounded-xl
                                flex items-center justify-center
                                text-white font-bold text-lg">
                        PA
                    </div>

                    <div class="hidden sm:block">
                        <h1 class="font-bold text-gray-900 leading-tight">
                            PinjamAlat
                        </h1>

                        <p class="text-xs text-gray-500">
                            Sistem Peminjaman Alat
                        </p>
                    </div>

                </a>


                
                <nav class="motion-sidebar-nav hidden md:flex items-center gap-2">

                    
                    <a href="<?php echo e(route('peminjam.katalog')); ?>"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                       <?php echo e(request()->routeIs('peminjam.katalog')
                            ? 'bg-blue-50 text-blue-600'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'); ?>">

                        Katalog Alat

                    </a>


                    
                    <a href="<?php echo e(route('peminjam.riwayat')); ?>"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                       <?php echo e(request()->routeIs('peminjam.riwayat')
                            ? 'bg-blue-50 text-blue-600'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'); ?>">

                        Riwayat Peminjaman

                    </a>

                </nav>


                
<div class="flex items-center gap-4">

    
    <?php echo $__env->make('partials.theme-toggle', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <details class="relative">

        <summary
    id="notificationBell"
    class="list-none cursor-pointer relative w-8 h-8 flex items-center justify-center
           text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition"
    title="Notifikasi">

            
            <img src="<?php echo e(asset('images/notification.png')); ?>"
                 alt="Notifikasi"
                 class="w-6 h-6 object-contain select-none pointer-events-none">

            <?php if(auth()->user()->unreadNotifications->count() > 0): ?>
                <span
    id="notificationBadge"
    class="absolute -top-1 -right-1 bg-red-500 text-white
           text-xs font-bold min-w-[20px] h-5 px-1
           rounded-full flex items-center justify-center">

    <?php echo e(auth()->user()->unreadNotifications->count()); ?>


</span>
            <?php endif; ?>

        </summary>


        
        <div
            class="absolute right-0 mt-3 w-96 bg-white border
                   border-gray-200 rounded-xl shadow-lg z-50
                   overflow-hidden">

            
            <div class="px-4 py-3 border-b border-gray-200">

                <h3 class="font-semibold text-gray-800">
                    Notifikasi
                </h3>

            </div>


            
            <div class="max-h-96 overflow-y-auto">

                <?php $__empty_1 = true; $__currentLoopData = auth()->user()->notifications()->latest()->take(5)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                    <div
                        class="px-4 py-3 border-b border-gray-100
                               hover:bg-gray-50 transition
                               <?php echo e(is_null($notification->read_at)
                                    ? 'bg-blue-50'
                                    : ''); ?>">

                        <div class="flex items-start gap-3">

                            <div class="text-lg">
                                
                            </div>

                            <div class="flex-1">

                                <p class="text-sm font-semibold text-gray-800">
                                    <?php echo e($notification->data['judul'] ?? 'Notifikasi'); ?>

                                </p>

                                <p class="text-xs text-gray-600 mt-1">
                                    <?php echo e($notification->data['pesan'] ?? ''); ?>

                                </p>

                                <p class="text-[11px] text-gray-400 mt-2">
                                    <?php echo e($notification->created_at->diffForHumans()); ?>

                                </p>

                            </div>

                        </div>

                    </div>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                    <div class="px-4 py-8 text-center text-sm text-gray-500">

                        Belum ada notifikasi.

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </details>


    
    <a href="<?php echo e(route('profile.edit')); ?>"
       class="motion-user-link flex items-center gap-3 pr-2 pl-1 py-1
              rounded-lg hover:bg-gray-100 transition group">

        <div class="hidden sm:block text-right">
            <p class="text-sm font-semibold text-gray-800">
                <?php echo e(auth()->user()->name); ?>

            </p>
            <p class="text-xs text-gray-500">
                <?php echo e(ucfirst(auth()->user()->role)); ?>

            </p>
        </div>

        <?php if(auth()->user()->foto_profile): ?>
            <img src="<?php echo e(asset(auth()->user()->foto_profile)); ?>"
                 alt="<?php echo e(auth()->user()->name); ?>"
                 class="w-10 h-10 rounded-full object-cover border">
        <?php else: ?>
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center
                        text-blue-600 font-bold">
                <?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?>

            </div>
        <?php endif; ?>

    </a>

    <div class="w-px h-8 bg-gray-200"></div>


                    
                    <form action="<?php echo e(route('logout')); ?>" method="POST" data-confirm-logout>

                        <?php echo csrf_field(); ?>

                        <button type="submit"
                            class="hidden sm:block
                                   px-3 py-2
                                   text-sm font-medium
                                   text-red-600
                                   hover:bg-red-50
                                   rounded-lg
                                   transition">

                            Logout

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </header>



    
    
    

    <div class="motion-page md:hidden bg-white border-b border-gray-200">

        <div class="max-w-7xl mx-auto px-4 py-2">

            <div class="flex gap-2 overflow-x-auto">

                <a href="<?php echo e(route('peminjam.katalog')); ?>"
                   class="whitespace-nowrap px-4 py-2 rounded-lg text-sm font-medium
                   <?php echo e(request()->routeIs('peminjam.katalog')
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-600'); ?>">

                    Katalog Alat

                </a>


                <a href="<?php echo e(route('peminjam.riwayat')); ?>"
                   class="whitespace-nowrap px-4 py-2 rounded-lg text-sm font-medium
                   <?php echo e(request()->routeIs('peminjam.riwayat')
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-600'); ?>">

                    Riwayat Peminjaman

                </a>


                <form action="<?php echo e(route('logout')); ?>" method="POST" data-confirm-logout
                      class="inline">

                    <?php echo csrf_field(); ?>

                    <button type="submit"
                        class="whitespace-nowrap px-4 py-2 rounded-lg
                               text-sm font-medium
                               bg-red-50 text-red-600">

                        Logout

                    </button>

                </form>

            </div>

        </div>

    </div>



    
    
    

    <main class="motion-main min-h-[calc(100vh-4rem)]">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            
            <?php if(session('success')): ?>

                <div class="mb-6 flex items-start gap-3
                            bg-emerald-50
                            border border-emerald-200
                            text-emerald-800
                            px-4 py-3
                            rounded-xl">

                    <div class="mt-0.5">
                        ✓
                    </div>

                    <p class="text-sm">
                        <?php echo e(session('success')); ?>

                    </p>

                </div>

            <?php endif; ?>


            
            <?php if(session('error')): ?>

                <div class="mb-6 flex items-start gap-3
                            bg-red-50
                            border border-red-200
                            text-red-800
                            px-4 py-3
                            rounded-xl">

                    <div class="mt-0.5">
                        !
                    </div>

                    <p class="text-sm">
                        <?php echo e(session('error')); ?>

                    </p>

                </div>

            <?php endif; ?>


            
            <?php if (! empty(trim($__env->yieldContent('page-heading')))): ?>

                <div class="mb-6">

                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
                        <?php echo $__env->yieldContent('page-heading'); ?>
                    </h2>

                    <?php if (! empty(trim($__env->yieldContent('page-description')))): ?>

                        <p class="mt-1 text-gray-500">
                            <?php echo $__env->yieldContent('page-description'); ?>
                        </p>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            
            <?php echo $__env->yieldContent('content'); ?>

        </div>

    </main>



    
    
    

    <footer class="border-t border-gray-200 bg-white">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="py-5 text-center text-sm text-gray-500">

                Sistem Peminjaman Alat

                <span class="mx-1">•</span>

                Panel Peminjam

            </div>

        </div>

    </footer>

    
    <?php echo $__env->yieldPushContent('scripts'); ?>

    <?php echo $__env->make('partials.motion', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</body>

</html>

<script>
    const notificationBell = document.getElementById('notificationBell');
    const notificationBadge = document.getElementById('notificationBadge');

    if (notificationBell) {
        notificationBell.addEventListener('click', function () {

            fetch('<?php echo e(route('notifications.readAll')); ?>', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && notificationBadge) {
                    notificationBadge.remove();
                }
            });
        });
    }
</script><?php /**PATH /var/www/resources/views/layouts/peminjam.blade.php ENDPATH**/ ?>