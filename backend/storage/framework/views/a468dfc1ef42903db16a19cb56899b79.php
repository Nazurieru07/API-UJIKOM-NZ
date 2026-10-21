<?php $__env->startSection('title', 'Ajukan Edit Peminjaman'); ?>

<?php $__env->startSection('page-heading', 'Ajukan Edit Peminjaman'); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6 mb-5">
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
        <p class="text-sm text-yellow-800 font-medium">Pilih serial unit yang ingin ditambah/dihapus.</p>
    </div>

    <form action="<?php echo e(route('peminjam.edit.ajukan', $peminjaman->id)); ?>" method="POST">
        <?php echo csrf_field(); ?>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Rencana Kembali Baru</label>
            <input type="date" name="tgl_kembali_plan_baru"
                   value="<?php echo e(old('tgl_kembali_plan_baru') ?? ($peminjaman->tgl_kembali_plan ? $peminjaman->tgl_kembali_plan->format('Y-m-d') : '')); ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   required>
            <?php $__errorArgs = ['tgl_kembali_plan_baru'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Alasan (Opsional)</label>
            <textarea name="alasan" rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Tulis alasan..."><?php echo e(old('alasan')); ?></textarea>
            <?php $__errorArgs = ['alasan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
            <h4 class="font-semibold text-gray-900 mb-3">Unit Saat Ini</h4>
            <div class="space-y-2">
                <?php $__currentLoopData = $peminjaman->detailPinjams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900"><?php echo e($detail->alatUnit?->alat->nama_alat ?? 'Alat Dihapus'); ?></p>
                        <p class="text-xs text-gray-500"><?php echo e($detail->alatUnit?->alat->kategori->nama_kategori ?? 'Tanpa kategori'); ?></p>
                    </div>
                    <p class="text-sm font-semibold text-gray-700 font-mono"><?php echo e($detail->alatUnit?->serial_number ?? '-'); ?></p>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
            <h4 class="font-semibold text-gray-900 mb-3">Tambah/Hapus Unit (Opsional)</h4>
            <p class="text-xs text-gray-600 mb-3">Kosongkan jika hanya ingin mengubah tanggal.</p>
            <div id="new-alats" class="space-y-3">
            </div>
            <button type="button" id="add-alat-btn" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">+ Tambah Unit</button>
        </div>

        <div class="flex gap-3 mt-8">
            <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition">Ajukan Edit Peminjaman</button>
            <a href="<?php echo e(route('peminjam.riwayat')); ?>" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition">Batal</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var addBtn = document.getElementById('add-alat-btn');
    var container = document.getElementById('new-alats');
    var alatOptions = '<?php $__currentLoopData = $alats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><optgroup label="<?php echo e($alat->nama_alat); ?> (Tersedia: <?php echo e($alat->alatUnit->where("kondisi", "tersedia")->count()); ?>)"><?php $__currentLoopData = $alat->alatUnit->where("kondisi", "tersedia"); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($unit->id); ?>"><?php echo e($unit->serial_number); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></optgroup><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>';

    addBtn.addEventListener('click', function() {
        var item = document.createElement('div');
        item.className = 'new-alat-item flex items-center gap-3 p-2 bg-white border border-gray-200 rounded-lg';
        item.innerHTML = '<select name="alat_unit_id[]" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg"><option value="">Pilih Serial Unit</option>' + alatOptions + '</select>' +
            '<select name="aksi[]" class="w-28 px-3 py-2 border border-gray-300 rounded-lg"><option value="tambah">+ Tambah</option><option value="hapus">- Hapus</option></select>' +
            '<button type="button" class="remove-item px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg">X</button>';
        container.appendChild(item);
    });
    
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            e.target.closest('.new-alat-item').remove();
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.peminjam', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/peminjam/edit-peminjaman.blade.php ENDPATH**/ ?>