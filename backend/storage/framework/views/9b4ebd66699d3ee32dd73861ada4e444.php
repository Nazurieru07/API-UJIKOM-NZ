<?php $__env->startSection('title', 'Kelola User - Panel Admin'); ?>
<?php $__env->startSection('header-title', 'Manajemen Pengguna Sistem'); ?>

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

        <div class="p-5 border-b border-gray-200 bg-gray-50">
    <h3 class="text-lg font-bold text-gray-800">
        Daftar Pengguna Sistem
    </h3>

    <div class="flex items-center gap-3 w-full md:w-auto mt-4">

    <!-- Form Search dan Filter -->
    <form action="<?php echo e(route('admin.user.index')); ?>" method="GET"
        class="flex flex-wrap items-center gap-2 w-full">

        <!-- Search -->
<input
    type="text"
    name="search"
    value="<?php echo e(request('search')); ?>"
    placeholder="Cari nama, email, role..."
    class="w-full md:w-64 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

<!-- Filter Role -->
<select
    name="role"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

    <option value="">Semua Role</option>

    <option value="admin"
        <?php echo e(request('role') == 'admin' ? 'selected' : ''); ?>>
        Admin
    </option>

    <option value="petugas"
        <?php echo e(request('role') == 'petugas' ? 'selected' : ''); ?>>
        Petugas
    </option>

    <option value="peminjam"
        <?php echo e(request('role') == 'peminjam' ? 'selected' : ''); ?>>
        Peminjam
    </option>

</select>

<!-- Filter Jenis Kelamin -->
<select
    name="jenis_kelamin"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

    <option value="">Semua Jenis Kelamin</option>

    <option value="Laki-laki"
        <?php echo e(request('jenis_kelamin') == 'Laki-laki' ? 'selected' : ''); ?>>
        Laki-laki
    </option>

    <option value="Perempuan"
        <?php echo e(request('jenis_kelamin') == 'Perempuan' ? 'selected' : ''); ?>>
        Perempuan
    </option>

</select>

<!-- Filter Status Akun -->
<select
    name="status"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

    <option value="">Semua Status</option>

    <option value="aktif"
        <?php echo e(request('status') == 'aktif' ? 'selected' : ''); ?>>
        Aktif
    </option>

    <option value="nonaktif"
        <?php echo e(request('status') == 'nonaktif' ? 'selected' : ''); ?>>
        Nonaktif
    </option>

</select>

<button
    type="submit"
    class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-lg transition">
    Cari
</button>

    </form>

    <!-- Tombol Reset -->
    <?php if(request('search') || request('role') || request('jenis_kelamin') || request('status')): ?>
        <a href="<?php echo e(route('admin.user.index')); ?>"
            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition whitespace-nowrap"
            title="Reset Pencarian dan Filter">
            Reset
        </a>
    <?php endif; ?>

    <!-- Tombol Tambah User -->
    <a href="<?php echo e(route('admin.user.create')); ?>"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
        + Tambah User
    </a>

</div>
</div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Foto</th>
                        <th class="py-3 px-4 border-b">Nama</th>
                        <th class="py-3 px-4 border-b">Email</th>
                        <th class="py-3 px-4 border-b">Role / Hak Akses</th>
                        <th class="py-3 px-4 border-b">No. HP</th>
                        <th class="py-3 px-4 border-b">Jenis Kelamin</th>
                        <th class="py-3 px-4 border-b">Status Akun</th>
                        <th class="py-3 px-4 border-b">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-gray-50 transition">
                             <td class="py-3 px-4 border-b">
                            <?php if($user->foto_profile): ?>
                                <img
                                    src="<?php echo e(asset($user->foto_profile)); ?>"
                                    alt="<?php echo e($user->name); ?>"
                                    class="w-12 h-12 object-cover rounded-full border"
                                >
                            <?php else: ?>
                                <span class="text-xs text-gray-400 italic">
                                    Tidak ada
                                </span>
                            <?php endif; ?>
                        </td>
                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                <?php echo e($user->name); ?>

                            </td>

                            <td class="py-3 px-4 border-b">
                                <?php echo e($user->email); ?>

                            </td>

                            <td class="py-3 px-4 border-b">
                                <span
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full
                                    <?php if($user->role == 'admin'): ?>
                                        bg-purple-100 text-purple-800
                                    <?php elseif($user->role == 'petugas'): ?>
                                        bg-blue-100 text-blue-800
                                    <?php else: ?>
                                        bg-green-100 text-green-800
                                    <?php endif; ?>">

                                    <?php echo e(ucfirst($user->role)); ?>


                                </span>
                            </td>

                            <td class="py-3 px-4 border-b">
                                <?php echo e($user->no_hp ?? '-'); ?>

                            </td>

                            <td class="py-3 px-4 border-b">
                            <?php if($user->jenis_kelamin == 'Laki-laki'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                    Laki-laki
                                </span>
                            <?php elseif($user->jenis_kelamin == 'Perempuan'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-pink-100 text-pink-700">
                                    Perempuan
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">
                                    -
                                </span>
                            <?php endif; ?>
                        </td>

                            <td class="py-3 px-4 border-b">
                                <?php if($user->is_aktif): ?>
                                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3 px-4 border-b">

                                <div class="flex items-center space-x-2">

                                    <!-- Tombol Edit -->
                                    <a href="<?php echo e(route('admin.user.edit', $user->id)); ?>"
                                        class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                                        Edit
                                    </a>

                                    <?php if($user->id !== auth()->id()): ?>
                    <!-- Tombol Hapus -->
                    <form action="<?php echo e(route('admin.user.destroy', $user->id)); ?>"
                        method="POST"
                        onsubmit="return confirm('Yakin ingin menghapus user ini?')">

                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>

                        <button type="submit"
                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                            Hapus
                        </button>
                    </form>
                <?php endif; ?>

                    <!-- Tombol Nonaktifkan / Aktifkan -->
                    <?php if($user->id !== auth()->id()): ?>
                        <form action="<?php echo e(route('admin.user.toggle-aktif', $user->id)); ?>"
                            method="POST"
                            onsubmit="return confirm('<?php echo e($user->is_aktif ? 'Nonaktifkan' : 'Aktifkan kembali'); ?> akun <?php echo e($user->name); ?>? User nonaktif tidak dapat login.'">

                            <?php echo csrf_field(); ?>

                            <?php if($user->is_aktif): ?>
                                <button type="submit"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                                    Nonaktifkan
                                </button>
                            <?php else: ?>
                                <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                                    Aktifkan
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                        <tr>
                            <td colspan="8" class="py-4 text-center text-gray-500">
                            Belum ada data pengguna.
                        </td>
                        </tr>

                    <?php endif; ?>
                </tbody>

            </table>
        </div>

        <div class="p-4 border-t border-gray-200 bg-gray-50">
    <?php echo e($users->links()); ?>

</div>

    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/user/index.blade.php ENDPATH**/ ?>