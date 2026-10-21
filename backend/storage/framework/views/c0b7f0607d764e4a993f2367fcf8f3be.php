<?php $__env->startSection('title', 'Profil Saya'); ?>
<?php $__env->startSection('header-title', 'Profil Saya'); ?>

<?php $__env->startSection('content'); ?>

    <?php if(session('success')): ?>
        <div class="flash-success fixed top-20 right-4 z-50 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl shadow-lg text-sm max-w-sm">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="flash-error fixed top-20 right-4 z-50 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-lg text-sm max-w-sm">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="max-w-3xl mx-auto">

        
        <?php
            $urlKembali = \App\Http\Middleware\SimpanRiwayatHalaman::urlSebelumnya(url()->current());
            $targetKembali = $urlKembali ?: route('profile.edit');
        ?>

        <div class="mb-4 flex items-center justify-between gap-3">

            <?php if($urlKembali): ?>
                <a href="<?php echo e($targetKembali); ?>"
                   class="inline-flex items-center gap-2 text-sm font-medium text-gray-600
                          hover:text-gray-900 transition">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-4 h-4"
                         fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>

                    Kembali
                </a>
            <?php else: ?>
                <span class="inline-flex items-center gap-2 text-sm text-gray-400">
                    
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-4 h-4"
                         fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>

                    Profil
                </span>
            <?php endif; ?>

            <span class="text-sm text-gray-400">
                Profil Saya
            </span>

        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-8 flex items-center gap-6">
                <?php if($user->foto_profile): ?>
                    <img id="fotoHeader" src="<?php echo e(asset($user->foto_profile)); ?>" alt="<?php echo e($user->name); ?>" class="w-24 h-24 rounded-full object-cover border-4 border-white/30 shadow-lg">
                <?php else: ?>
                    <div id="fotoHeader" class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center text-white text-3xl font-bold border-4 border-white/30">
                        <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                    </div>
                <?php endif; ?>

                <div>
                    <h2 class="text-2xl font-bold text-white"><?php echo e($user->name); ?></h2>
                    <p class="text-blue-100 text-sm mt-1"><?php echo e($user->email); ?></p>
                    <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/90 text-blue-800">
                        <?php echo e(ucfirst($user->role)); ?>

                    </span>
                </div>
            </div>

            <form action="<?php echo e(route('profile.update')); ?>" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Foto Profil</label>

                    <div class="flex items-center gap-4">
                        <?php if($user->foto_profile): ?>
                            <img id="fotoPreview" src="<?php echo e(asset($user->foto_profile)); ?>" alt="Preview" class="w-20 h-20 rounded-full object-cover border">
                        <?php else: ?>
                            <div id="fotoPreview" class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-2xl border">
                                <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                            </div>
                        <?php endif; ?>

                        <div class="flex-1">
                            <input type="file" name="foto_profile" id="fotoInput" accept="image/*" class="hidden">

                            <button type="button" onclick="document.getElementById('fotoInput').click()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                                Pilih Foto
                            </button>

                            <p class="text-xs text-gray-400 mt-2">JPG, JPEG, PNG, atau WebP. Maksimal 2MB.</p>
                        </div>
                    </div>

                    <?php $__errorArgs = ['foto_profile'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" id="name" name="name" value="<?php echo e(old('name', $user->name)); ?>" required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo e(old('email', $user->email)); ?>" required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="no_hp" class="block text-sm font-semibold text-gray-700 mb-2">No. HP</label>
                        <input type="text" id="no_hp" name="no_hp" value="<?php echo e(old('no_hp', $user->no_hp)); ?>" placeholder="08xxxxxxxxxx"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php $__errorArgs = ['no_hp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="jenis_kelamin" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih...</option>
                            <option value="Laki-laki" <?php echo e(old('jenis_kelamin', $user->jenis_kelamin) === 'Laki-laki' ? 'selected' : ''); ?>>Laki-laki</option>
                            <option value="Perempuan" <?php echo e(old('jenis_kelamin', $user->jenis_kelamin) === 'Perempuan' ? 'selected' : ''); ?>>Perempuan</option>
                        </select>
                        <?php $__errorArgs = ['jenis_kelamin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                </div>

                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-1">Ganti Password</h3>
                    <p class="text-xs text-gray-500 mb-4">Opsional. Biarkan kosong jika tidak ingin mengubah password.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password Baru</label>
                            <input type="password" id="password" name="password" autocomplete="new-password"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Ulangi Password Baru</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>

    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const fotoInput = document.getElementById('fotoInput');

    if (fotoInput) {
        fotoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (ev) {
                document.getElementById('fotoPreview').src = ev.target.result;
                document.getElementById('fotoHeader').src = ev.target.result;
            };
            reader.readAsDataURL(file);
        });
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make($user->role === 'peminjam' ? 'layouts.peminjam' : 'layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/profile/edit.blade.php ENDPATH**/ ?>