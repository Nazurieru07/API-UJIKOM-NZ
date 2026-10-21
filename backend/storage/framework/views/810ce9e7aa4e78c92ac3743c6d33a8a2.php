<?php $__env->startSection('title', 'Periksa Pengembalian'); ?>
<?php $__env->startSection('header-title', 'Periksa Pengembalian'); ?>

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

    <?php
        $peminjam = $pengembalian->peminjaman->user;
        $kondisiAda = !empty($pengembalian->kondisi_kembali);
    ?>

    
    <a href="<?php echo e(route('petugas.pengembalian.index')); ?>"
       class="inline-flex items-center gap-2 mb-5
              px-4 py-2.5 rounded-xl
              bg-gray-200 hover:bg-gray-300
              text-gray-800 text-sm font-semibold transition">

        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>

        Kembali

    </a>


    
    
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-5">

        <div class="p-5 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-800">Informasi Pengembalian</h3>
        </div>

        <div class="p-5 space-y-3">

            <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm">

                <div>
                    <span class="text-gray-500">Peminjam:</span>
                    <span class="font-semibold text-gray-800 ml-1"><?php echo e($peminjam?->name ?? '-'); ?></span>
                </div>

                <div>
                    <span class="text-gray-500">ID Peminjaman:</span>
                    <span class="font-semibold text-gray-800 ml-1">#<?php echo e($pengembalian->peminjaman_id); ?></span>
                </div>

                <div>
                    <span class="text-gray-500">Tanggal Kembali:</span>
                    <span class="font-semibold text-gray-800 ml-1">
                        <?php echo e($pengembalian->tgl_kembali->format('d-m-Y')); ?>

                    </span>
                </div>

            </div>


            
            <?php if($pengembalian->catatan_peminjam): ?>

                <div class="mt-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                    <p class="text-xs font-semibold text-emerald-800 mb-1">
                        Catatan Peminjam
                    </p>
                    <p class="text-sm text-emerald-900">
                        <?php echo e($pengembalian->catatan_peminjam); ?>

                    </p>
                </div>

            <?php endif; ?>


            
            <div class="mt-4">
                <p class="text-xs font-semibold text-gray-600 mb-2">Unit yang Dikembalikan</p>

                <ul class="space-y-1.5">
                    <?php $__currentLoopData = $pengembalian->peminjaman->detailPinjams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-center gap-2 text-sm text-gray-700">
                            <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">
                                <?php echo e($detail->alatUnit?->serial_number ?? '-'); ?>

                            </span>
                            <span><?php echo e($detail->alatUnit?->alat?->nama_alat ?? '-'); ?></span>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>

        </div>

    </div>


    
    
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-5">

        <div class="p-5 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-800">Hasil Pemeriksaan Barang</h3>
            <p class="text-xs text-gray-500 mt-1">
                Peminjam tidak mengisi kondisi dan denda. Anda yang menentukannya
                setelah memeriksa barang secara langsung.
            </p>
        </div>

        <form action="<?php echo e(route('petugas.pengembalian.pemeriksaan', $pengembalian->id)); ?>"
              method="POST"
              class="p-5 space-y-4">

            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            
            <div>
                <label for="kondisi_kembali" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Kondisi Barang
                    <span class="text-red-500">*</span>
                </label>

                <select id="kondisi_kembali"
                        name="kondisi_kembali"
                        required
                        class="w-full md:w-64 px-3 py-2.5 border border-gray-300 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">

                    <option value="">-- Pilih kondisi --</option>

                    <option value="Baik"
                        <?php if(old('kondisi_kembali', $pengembalian->kondisi_kembali) === 'Baik'): echo 'selected'; endif; ?>>
                        Baik
                    </option>

                    <option value="Rusak"
                        <?php if(old('kondisi_kembali', $pengembalian->kondisi_kembali) === 'Rusak'): echo 'selected'; endif; ?>>
                        Rusak
                    </option>

                </select>

                <?php $__errorArgs = ['kondisi_kembali'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div>
                <label for="denda_kerusakan" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Denda Kerusakan (Rp)
                    <span class="text-red-500">*</span>
                </label>

                <input type="number"
                       id="denda_kerusakan"
                       name="denda_kerusakan"
                       value="<?php echo e(old('denda_kerusakan', $pengembalian->denda_kerusakan ?? 0)); ?>"
                       min="0"
                       step="1000"
                       required
                       class="w-full md:w-64 px-3 py-2.5 border border-gray-300 rounded-lg
                              focus:outline-none focus:ring-2 focus:ring-blue-500">

                <p class="text-xs text-gray-500 mt-1">
                    Isi 0 jika barang kembali tanpa kerusakan. Denda keterlambatan
                    dihitung sistem, tidak di sini.
                </p>

                <?php $__errorArgs = ['denda_kerusakan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5
                           rounded-lg text-sm font-semibold transition">
                Simpan Hasil Pemeriksaan
            </button>

        </form>

    </div>


    
    
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">

        <div class="p-5 flex flex-col sm:flex-row items-center justify-between gap-3">

            <a href="<?php echo e(route('petugas.pengembalian.index')); ?>"
               class="w-full sm:w-auto text-center bg-gray-200 hover:bg-gray-300
                      text-gray-800 px-5 py-2.5 rounded-lg text-sm font-semibold transition">
                Kembali
            </a>

            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">

                <form action="<?php echo e(route('petugas.pengembalian.tolak', $pengembalian->id)); ?>"
                      method="POST"
                      class="w-full sm:w-auto">

                    <?php echo csrf_field(); ?>

                    <button type="submit"
                            class="w-full bg-red-500 hover:bg-red-600 text-white
                                   px-5 py-2.5 rounded-lg text-sm font-semibold transition">
                        Tolak Pengajuan
                    </button>

                </form>

                <form action="<?php echo e(route('petugas.pengembalian.setujui', $pengembalian->id)); ?>"
                      method="POST"
                      class="w-full sm:w-auto">

                    <?php echo csrf_field(); ?>

                    <button type="submit"
                            <?php if(!$kondisiAda): echo 'disabled'; endif; ?>
                            title="<?php echo e($kondisiAda ? 'Setujui pengembalian ini' : 'Isi kondisi barang terlebih dahulu'); ?>"
                            class="w-full px-5 py-2.5 rounded-lg text-sm font-semibold transition
                                   <?php echo e($kondisiAda
                                       ? 'bg-green-600 hover:bg-green-700 text-white'
                                       : 'bg-gray-200 text-gray-400 cursor-not-allowed'); ?>">
                        Setujui Pengembalian
                    </button>

                </form>

            </div>

        </div>

    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/petugas/pengembalian/periksa.blade.php ENDPATH**/ ?>