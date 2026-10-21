<?php $__env->startSection('title', 'Edit Alat - Panel Admin'); ?>
<?php $__env->startSection('header-title', 'Edit Data Alat'); ?>

<?php $__env->startSection('content'); ?>

<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    <form action="<?php echo e(route('admin.alat.update', $alat->id)); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Nama Alat
            </label>

            <input
                type="text"
                name="nama_alat"
                value="<?php echo e(old('nama_alat', $alat->nama_alat)); ?>"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Kategori
            </label>

            <select
                name="kategori_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <?php $__currentLoopData = $kategoris; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kategori): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option
                        value="<?php echo e($kategori->id); ?>"
                        <?php echo e(old('kategori_id', $alat->kategori_id) == $kategori->id ? 'selected' : ''); ?>

                    >
                        <?php echo e($kategori->nama_kategori); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Kode Alat
                <span class="text-xs text-gray-400 font-normal">
                    (Prefix serial number, 2 huruf)
                </span>
            </label>

            <input
                type="text"
                name="kode_alat"
                value="<?php echo e(old('kode_alat', $alat->kode_alat)); ?>"
                required
                maxlength="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg uppercase font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            <p class="text-xs text-amber-600 mt-1">
                Changing kode alat tidak mengubah serial number unit yang sudah
                dibuat. Serial lama tetap memakai prefix sebelumnya.
            </p>

            <?php $__errorArgs = ['kode_alat'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <span class="text-red-500 text-xs"><?php echo e($message); ?></span>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Deskripsi
            </label>

            <textarea
                name="deskripsi"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ><?php echo e(old('deskripsi', $alat->deskripsi)); ?></textarea>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Gambar Alat
                <span class="text-xs text-gray-400 font-normal">
                    (Biarkan kosong jika tidak ingin mengubah gambar)
                </span>
            </label>

            <?php if($alat->gambar): ?>
                <div class="mb-2">
                    <img
                        src="<?php echo e(asset($alat->gambar)); ?>"
                        alt="Preview"
                        class="w-16 h-16 object-cover rounded-lg border"
                    >
                </div>
            <?php endif; ?>

            <input
                type="file"
                name="gambar"
                accept="image/*"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            >
        </div>

        <div class="flex justify-end space-x-2">

            <a
                href="<?php echo e(route('admin.alat.index')); ?>"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Batal
            </a>

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition"
            >
                Perbarui
            </button>

        </div>

    </form>

    
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-base font-bold text-gray-800 mb-1">Daftar Unit</h3>

        <p class="text-xs text-gray-400 mb-4">
            <?php echo e($alat->alatUnit->count()); ?> unit terdaftar. Ubah kondisi unit
            (tandai rusak / perbaiki) dari halaman utama lewat tombol
            "Kelola Unit".
        </p>

        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                        <th class="py-2 px-4 border-b">Serial Number</th>
                        <th class="py-2 px-4 border-b">Kondisi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $alat->alatUnit; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b font-mono text-xs">
                                <?php echo e($unit->serial_number); ?>

                            </td>

                            <td class="py-2 px-4 border-b">
                                <?php if($unit->isTersedia()): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                        Tersedia
                                    </span>
                                <?php elseif($unit->isDipinjam()): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                                        Dipinjam
                                    </span>
                                <?php elseif($unit->isRusak()): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">
                                        Rusak
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="2" class="py-4 px-4 text-center text-gray-400 text-xs">
                                Belum ada unit terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/alat/edit.blade.php ENDPATH**/ ?>