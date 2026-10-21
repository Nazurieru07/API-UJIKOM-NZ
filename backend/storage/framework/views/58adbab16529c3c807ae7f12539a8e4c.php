<?php $__env->startSection('title', 'Edit User - Panel Admin'); ?>
<?php $__env->startSection('header-title', 'Edit Data Pengguna'); ?>

<?php $__env->startSection('content'); ?>

<div class="max-w-xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    <form action="<?php echo e(route('admin.user.update', $user->id)); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Nama Lengkap
            </label>

            <input
                type="text"
                name="name"
                value="<?php echo e(old('name', $user->name)); ?>"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Email
            </label>

            <input
                type="email"
                name="email"
                value="<?php echo e(old('email', $user->email)); ?>"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Password Baru
                <span class="text-xs text-gray-400 font-normal">
                    (Kosongkan jika tidak ingin mengubah password)
                </span>
            </label>

            <input
                type="password"
                name="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Role / Hak Akses
            </label>

            <select
                name="role"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                <option value="peminjam" <?php echo e($user->role == 'peminjam' ? 'selected' : ''); ?>>
                    Peminjam
                </option>

                <option value="petugas" <?php echo e($user->role == 'petugas' ? 'selected' : ''); ?>>
                    Petugas
                </option>

                <option value="admin" <?php echo e($user->role == 'admin' ? 'selected' : ''); ?>>
                    Admin
                </option>

            </select>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                No. HP
            </label>

            <input
                type="text"
                name="no_hp"
                value="<?php echo e(old('no_hp', $user->no_hp)); ?>"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-6">
    <label class="block text-gray-700 text-sm font-semibold mb-2">
        Jenis Kelamin
    </label>

    <select
        name="jenis_kelamin"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

        <option value="">-- Pilih Jenis Kelamin --</option>

        <option value="Laki-laki"
            <?php echo e(old('jenis_kelamin', $user->jenis_kelamin) == 'Laki-laki' ? 'selected' : ''); ?>>
            Laki-laki
        </option>

        <option value="Perempuan"
            <?php echo e(old('jenis_kelamin', $user->jenis_kelamin) == 'Perempuan' ? 'selected' : ''); ?>>
            Perempuan
        </option>

    </select>

    <?php $__errorArgs = ['jenis_kelamin'];
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

        <!-- Foto Profil -->
<div class="mb-6">
    <label class="block text-gray-700 text-sm font-semibold mb-2">
        Foto Profil
        <span class="text-xs text-gray-400 font-normal">
            (Biarkan kosong jika tidak ingin mengubah foto)
        </span>
    </label>

    <?php if($user->foto_profile): ?>
        <div class="mb-3">
            <img
                src="<?php echo e(asset($user->foto_profile)); ?>"
                alt="<?php echo e($user->name); ?>"
                class="w-20 h-20 object-cover rounded-full border"
            >
        </div>
    <?php endif; ?>

    <input
        type="file"
        name="foto_profile"
        accept="image/*"
        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
    >

    <?php $__errorArgs = ['foto_profile'];
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

<!-- Tombol -->
<div class="flex justify-end space-x-2">

    <a href="<?php echo e(route('admin.user.index')); ?>"
        class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
        Batal
    </a>

    <button
        type="submit"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
        Perbarui
    </button>

</div>

    </form>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/user/edit.blade.php ENDPATH**/ ?>