<?php $__env->startSection('title', 'Kelola Kategori - Panel Admin'); ?>
<?php $__env->startSection('header-title', 'Manajemen Kategori Alat'); ?>

<?php $__env->startSection('content'); ?>

    <!-- Notifikasi -->
    <?php if(session('success')): ?>
    <div class="flash-success mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>

    <?php if(session('error')): ?>
    <div class="flash-error mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">

        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">

            <h3 class="text-lg font-bold text-gray-800">
                Daftar Kategori Alat
            </h3>

            <div class="flex items-center gap-3 w-full md:w-auto">

                <!-- Form Search -->
                <form action="<?php echo e(route('admin.kategori.index')); ?>" method="GET" class="flex w-full md:w-80">

                    <input
                        type="text"
                        name="search"
                        value="<?php echo e(request('search')); ?>"
                        placeholder="Cari nama kategori..."
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                    <button
                        type="submit"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition">
                        Cari
                    </button>

                    <?php if(request('search')): ?>
                        <a
                            href="<?php echo e(route('admin.kategori.index')); ?>"
                            class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition">
                            Reset
                        </a>
                    <?php endif; ?>

                </form>

                <!-- Tombol Tambah -->
                <a
                    href="<?php echo e(route('admin.kategori.create')); ?>"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
                    + Tambah Kategori
                </a>

            </div>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-left border-collapse">

                <thead>
    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
        <th class="py-3 px-4 border-b w-16 text-center">No</th>
        <th class="py-3 px-4 border-b">Nama Kategori</th>
        <th class="py-3 px-4 border-b w-32 text-center">Jumlah Alat</th>
        <th class="py-3 px-4 border-b w-48">Aksi</th>
    </tr>
</thead>

                <tbody class="text-gray-700 text-sm">

                    <?php $__empty_1 = true; $__currentLoopData = $kategoris; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $kategori): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="py-3 px-4 border-b text-center">
                                <?php echo e($kategoris->firstItem() + $index); ?>

                            </td>

                            <td class="py-3 px-4 border-b font-medium">
                                <a
                                    href="<?php echo e(route('admin.kategori.show', $kategori->id)); ?>"
                                    class="text-blue-600 hover:text-blue-800 hover:underline transition"
                                >
                                    <?php echo e($kategori->nama_kategori); ?>

                                </a>
                            </td>

                            <td class="py-3 px-4 border-b text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full
                                            bg-blue-50 text-blue-600 text-xs font-semibold">
                                    <?php echo e($kategori->alat_count); ?> alat
                                </span>
                            </td>

                            <td class="py-3 px-4 border-b">

                                <div class="flex items-center space-x-2">

                                    <a
                                        href="<?php echo e(route('admin.kategori.edit', $kategori->id)); ?>"
                                        class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                                        Edit
                                    </a>

                                    <?php if($kategori->alat_count > 0): ?>
                                        
                                        <button
                                            type="button"
                                            disabled
                                            title="Kategori masih memiliki <?php echo e($kategori->alat_count); ?> alat dan tidak dapat dihapus."
                                            class="bg-gray-300 text-gray-500 px-3 py-1.5 rounded text-xs font-semibold cursor-not-allowed"
                                        >
                                            Hapus
                                        </button>
                                    <?php else: ?>
                                        <form
                                            action="<?php echo e(route('admin.kategori.destroy', $kategori->id)); ?>"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">

                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>

                                            <button
                                                type="submit"
                                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                                                Hapus
                                            </button>

                                        </form>
                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                        <tr>
                            <td colspan="4" class="py-4 text-center text-gray-500">
                                Belum ada data kategori.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <?php echo e($kategoris->links()); ?>

        </div>

    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/kategori/index.blade.php ENDPATH**/ ?>