<?php $__env->startSection('title', 'Dashboard Admin - Sistem Peminjaman'); ?>

<?php $__env->startSection('header-title', 'Ringkasan Aktivitas Sistem'); ?>

<?php $__env->startSection('content'); ?>

    
    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm">
        Selamat datang, <strong class="font-semibold"><?php echo e(auth()->user()->name); ?></strong>!
        Anda login sebagai hak akses
        <span class="uppercase font-bold text-emerald-900">
            <?php echo e(auth()->user()->role); ?>

        </span>.
    </div>


    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-700">
                Total Alat
            </p>

            <p class="text-3xl font-bold text-blue-900 mt-2">
                <?php echo e($jumlahAlat); ?>

            </p>

            <p class="text-xs text-blue-600 mt-1">
                Data alat
            </p>
        </div>


        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-purple-700">
                Total User
            </p>

            <p class="text-3xl font-bold text-purple-900 mt-2">
                <?php echo e($jumlahUser); ?>

            </p>

            <p class="text-xs text-purple-600 mt-1">
                Pengguna sistem
            </p>
        </div>


        
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-700">
                Total Kategori
            </p>

            <p class="text-3xl font-bold text-amber-900 mt-2">
                <?php echo e($jumlahKategori); ?>

            </p>

            <p class="text-xs text-amber-600 mt-1">
                Kategori alat
            </p>
        </div>


        
        <div class="bg-green-50 border border-green-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-green-700">
                Total Peminjaman
            </p>

            <p class="text-3xl font-bold text-green-900 mt-2">
                <?php echo e($jumlahPeminjaman); ?>

            </p>

            <p class="text-xs text-green-600 mt-1">
                Data peminjaman
            </p>
        </div>


        
        <div class="bg-red-50 border border-red-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-red-700">
                Total Pengembalian
            </p>

            <p class="text-3xl font-bold text-red-900 mt-2">
                <?php echo e($jumlahPengembalian); ?>

            </p>

            <p class="text-xs text-red-600 mt-1">
                Data pengembalian
            </p>
        </div>

    </div>


    
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">

        <div class="px-5 py-5 border-b border-gray-200 bg-gray-50">

            <h3 class="text-lg font-bold text-gray-800">
                Log Aktivitas Terbaru
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Menampilkan aktivitas terbaru yang terjadi di dalam sistem.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left border-collapse">

                <thead>

                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">

                        <th class="py-3 px-4 border-b whitespace-nowrap">
                            Waktu
                        </th>

                        <th class="py-3 px-4 border-b whitespace-nowrap">
                            User
                        </th>

                        <th class="py-3 px-4 border-b">
                            Aktivitas
                        </th>

                    </tr>

                </thead>


                <tbody class="text-gray-700 text-sm">

                    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="py-3 px-4 border-b whitespace-nowrap">
                                <?php echo e($log->created_at->format('d-m-Y H:i:s')); ?>

                            </td>

                            <td class="py-3 px-4 border-b font-medium text-gray-900 whitespace-nowrap">
                                <?php echo e($log->user->name ?? 'User tidak ditemukan'); ?>

                            </td>

                            <td class="py-3 px-4 border-b">
                                <?php echo e($log->aktivitas); ?>

                            </td>

                        </tr>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                        <tr>

                            <td
                                colspan="3"
                                class="py-6 text-center text-gray-500"
                            >
                                Belum ada log aktivitas.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>