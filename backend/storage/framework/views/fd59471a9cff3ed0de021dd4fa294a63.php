<?php $__env->startSection('title', 'Tambah Alat - Panel Admin'); ?>
<?php $__env->startSection('header-title', 'Tambah Alat Baru'); ?>

<?php $__env->startSection('content'); ?>

<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    <form action="<?php echo e(route('admin.alat.store')); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Nama Alat
            </label>

            <input
                type="text"
                name="nama_alat"
                value="<?php echo e(old('nama_alat')); ?>"
                required
                placeholder="Contoh: Multimeter Digital"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            <?php $__errorArgs = ['nama_alat'];
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
                Kategori
            </label>

            <select
                name="kategori_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">-- Pilih Kategori --</option>

                <?php $__currentLoopData = $kategoris; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kategori): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option
                        value="<?php echo e($kategori->id); ?>"
                        <?php echo e(old('kategori_id') == $kategori->id ? 'selected' : ''); ?>

                    >
                        <?php echo e($kategori->nama_kategori); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <?php $__errorArgs = ['kategori_id'];
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
                Kode Alat
                <span class="text-xs text-gray-400 font-normal">
                    (Prefix serial number, 2 huruf)
                </span>
            </label>

            <input
                type="text"
                name="kode_alat"
                id="kode_alat"
                value="<?php echo e(old('kode_alat')); ?>"
                required
                maxlength="2"
                placeholder="Contoh: MD"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg uppercase font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            <p class="text-xs text-gray-400 mt-1">
                Dipakai sebagai prefix serial number setiap unit, misal
                <span class="font-mono">MD-001</span>. Disarankan otomatis dari
                nama alat; boleh ditimpa.
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

        <!--
            Jumlah unit fisik yang dibuat sekaligus. Tanpa field ini,
            form tidak mengirim jumlah_unit; controller memvalidasinya
            required, jadi submit selalu memantul kembali ke form ini
            tanpa pesan yang kelihatan = "looping".
        -->
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Jumlah Unit
                <span class="text-xs text-gray-400 font-normal">
                    (Berapa barang serial dibuat)
                </span>
            </label>

            <input
                type="number"
                name="jumlah_unit"
                id="jumlah_unit"
                value="<?php echo e(old('jumlah_unit', 1)); ?>"
                required
                min="1"
                max="1000"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            <p class="text-xs text-gray-400 mt-1">
                Setiap unit dapat serial berurutan, misal
                <span class="font-mono">MD-001</span>,
                <span class="font-mono">MD-002</span>, dst.
            </p>

            <?php $__errorArgs = ['jumlah_unit'];
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
                Deskripsi (Opsional)
            </label>

            <textarea
                name="deskripsi"
                rows="3"
                placeholder="Keterangan tambahan tentang alat..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ><?php echo e(old('deskripsi')); ?></textarea>

            <?php $__errorArgs = ['deskripsi'];
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

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Gambar Alat (Opsional)
            </label>

            <input
                type="file"
                name="gambar"
                accept="image/*"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700"
            >

            <?php $__errorArgs = ['gambar'];
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
                Simpan
            </button>

        </div>

    </form>

</div>

<script>
    // ponytail: saran kode_alat = inisial nama alat (logika 2 huruf pertama
    // tiap kata), client-side saja. Versi canonical server ada di
    // App\Models\Alat::saranKodeAlat(). Hapus script ini kalau sudah
    // disatukan ke endpoint /search/alats.
    document.addEventListener('DOMContentLoaded', function () {
        const nama = document.querySelector('input[name="nama_alat"]');
        const kode = document.getElementById('kode_alat');

        if (!nama || !kode) return;

        nama.addEventListener('input', function () {
            // jangan timpa yang sudah admin ketik manual
            if (kode.dataset.manual === '1') return;

            const kata = nama.value.trim().split(/\s+/).filter(k => k !== '');
            if (kata.length === 0) {
                // model: 2 huruf pertama default, bukan string kosong
                kode.value = 'AL';
                return;
            }

            if (kata.length === 1) {
                kode.value = kata[0].substring(0, 2).toUpperCase();
                return;
            }

            kode.value = kata.slice(0, 2)
                .map(k => k.charAt(0).toUpperCase())
                .join('');
        });

        kode.addEventListener('input', function () {
            kode.dataset.manual = '1';
        });
    });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/alat/create.blade.php ENDPATH**/ ?>