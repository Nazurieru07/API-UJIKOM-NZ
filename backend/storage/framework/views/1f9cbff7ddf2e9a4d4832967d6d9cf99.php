<?php $__env->startSection('title', 'Log Aktivitas - Panel Admin'); ?>

<?php $__env->startSection('header-title', 'Log Aktivitas Sistem'); ?>

<?php $__env->startSection('content'); ?>

<style>
    /* Pagination */
.custom-page {
    min-width: 42px;
    height: 42px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;

    background: #171717;
    color: white;

    border: 1px solid #171717;
    border-radius: 8px;

    font-size: 14px;
    font-weight: 600;

    text-decoration: none;
    transition: all 0.2s ease;
}

/* Hover nomor halaman */
.custom-page:hover {
    background: #f59e0b;
    color: #111827;
    border-color: #f59e0b;
}

/* Halaman aktif */
.custom-page.active {
    background: #171717;
    color: white;
    border: 2px solid #f59e0b;
}

/* Prev dan Next */
.custom-page.prev-next {
    min-width: 80px;

    background: #f59e0b;
    color: #111827;
    border-color: #f59e0b;
}

.custom-page.prev-next:hover {
    background: #d97706;
    border-color: #d97706;
}

/* Prev / Next ketika tidak bisa digunakan */
.custom-page.disabled {
    min-width: 80px;

    background: #f59e0b;
    color: #111827;
    border-color: #f59e0b;

    opacity: 0.5;
    cursor: not-allowed;
}
</style>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">

        
        <div class="px-5 py-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">
                Log Aktivitas Sistem
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Riwayat aktivitas yang dilakukan pengguna di dalam sistem.
            </p>
        </div>

        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">
                            Waktu
                        </th>

                        <th class="py-3 px-4 border-b">
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

                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                <?php echo e($log->user->name ?? 'User tidak ditemukan'); ?>

                            </td>

                            <td class="py-3 px-4 border-b">
                                <?php echo e($log->aktivitas); ?>

                            </td>

                        </tr>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                        <tr>
                            <td colspan="3" class="py-6 text-center text-gray-500">
                                Belum ada log aktivitas.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>
        </div>

        
      <?php if($logs->hasPages()): ?>
    <div class="px-5 py-4 border-t border-gray-200">

        <div class="flex justify-end items-center gap-2">

            
            <?php if($logs->onFirstPage()): ?>
                <span class="custom-page disabled">
                    Prev
                </span>
            <?php else: ?>
                <a href="<?php echo e($logs->previousPageUrl()); ?>" class="custom-page prev-next">
                    Prev
                </a>
            <?php endif; ?>


            
            <?php
                $current = $logs->currentPage();
                $last = $logs->lastPage();

                $start = max(1, min($current - 2, $last - 4));
                $end = min($last, $start + 4);
            ?>

            <?php for($page = $start; $page <= $end; $page++): ?>

                <?php if($page == $current): ?>
                    <span class="custom-page active">
                        <?php echo e($page); ?>

                    </span>
                <?php else: ?>
                    <a href="<?php echo e($logs->url($page)); ?>" class="custom-page">
                        <?php echo e($page); ?>

                    </a>
                <?php endif; ?>

            <?php endfor; ?>


            
            <?php if($logs->hasMorePages()): ?>
                <a href="<?php echo e($logs->nextPageUrl()); ?>" class="custom-page prev-next">
                    Next
                </a>
            <?php else: ?>
                <span class="custom-page disabled">
                    Next
                </span>
            <?php endif; ?>

        </div>

    </div>
<?php endif; ?>

    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/log_aktivitas/index.blade.php ENDPATH**/ ?>