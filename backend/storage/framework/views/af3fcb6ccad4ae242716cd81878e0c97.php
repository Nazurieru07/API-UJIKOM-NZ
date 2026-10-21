<?php $__env->startSection('title', 'Riwayat Peminjaman - Peminjam'); ?>

<?php $__env->startSection('page-heading', 'Riwayat Peminjaman'); ?>

<?php $__env->startSection('page-description'); ?>
    Pantau pengajuan dan status peminjaman alat kamu.
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    
    <?php if(session('error')): ?>
        <div class="mb-5 px-4 py-3 rounded-xl
                    bg-red-50 border border-red-200 text-red-800 text-sm">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('success')): ?>
        <div class="mb-5 px-4 py-3 rounded-xl
                    bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    
    
    

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

        

        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500">
                        Total Peminjaman
                    </p>

                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        <?php echo e($peminjamans->count()); ?>

                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl bg-blue-50
                            flex items-center justify-center text-blue-600">

                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12
                               a2 2 0 002 2h10a2 2 0 002-2V7
                               a2 2 0 00-2-2h-2
                               M9 5a3 3 0 006 0
                               M9 5h6"
                        />
                    </svg>

                </div>

            </div>

        </div>


        

        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500">
                        Menunggu Persetujuan
                    </p>

                    <p class="mt-1 text-2xl font-bold text-yellow-600">

                        <?php echo e($peminjamans->where('status', 'diajukan')->count()); ?>


                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl bg-yellow-50
                            flex items-center justify-center text-yellow-600">

                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 2m6-2a9 9 0 11-18 0
                               9 9 0 0118 0z"
                        />
                    </svg>

                </div>

            </div>

        </div>


        

        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500">
                        Sedang Dipinjam
                    </p>

                    <p class="mt-1 text-2xl font-bold text-blue-600">

                        <?php echo e($peminjamans->whereIn('status', ['dipinjam', 'telat'])->count()); ?>


                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl bg-blue-50
                            flex items-center justify-center text-blue-600">

                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6v6l4 2"
                        />
                    </svg>

                </div>

            </div>

        </div>

    </div>


    
    
    

    <?php $__empty_1 = true; $__currentLoopData = $peminjamans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $peminjaman): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

        <div
            class="bg-white border border-gray-200 rounded-2xl
                   shadow-sm hover:shadow-md transition mb-5 overflow-hidden"
        >

            
            
            

            <div class="p-5 sm:p-6 border-b border-gray-100">

                <div class="flex flex-col sm:flex-row
                            sm:items-center sm:justify-between gap-3">

                    <div>

                        <p class="text-xs font-semibold uppercase
                                  tracking-wide text-blue-600">

                            Peminjaman #<?php echo e($peminjaman->id); ?>


                        </p>

                        <h3 class="mt-1 text-lg font-bold text-gray-900">

                            Pengajuan Peminjaman Alat

                        </h3>

                    </div>


                    

                    <?php if($peminjaman->status === 'diajukan'): ?>

                        <span
                            class="inline-flex items-center gap-2
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-yellow-50 text-yellow-700
                                   border border-yellow-200"
                        >

                            <span class="w-2 h-2 rounded-full bg-yellow-500"></span>

                            Menunggu Persetujuan

                        </span>

                    <?php elseif($peminjaman->status === 'dipinjam'): ?>

                        <span
                            class="inline-flex items-center gap-2
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-blue-50 text-blue-700
                                   border border-blue-200"
                        >

                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>

                            Sedang Dipinjam

                        </span>

                    <?php elseif($peminjaman->status === 'telat'): ?>

                        <span
                            class="inline-flex items-center gap-2
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-red-50 text-red-700
                                   border border-red-200"
                        >

                            <span class="w-2 h-2 rounded-full bg-red-500"></span>

                            Terlambat

                        </span>

                    <?php elseif($peminjaman->status === 'dikembalikan'): ?>

                        <span
                            class="inline-flex items-center gap-2
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-emerald-50 text-emerald-700
                                   border border-emerald-200"
                        >

                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>

                            Sudah Dikembalikan

                        </span>

                    <?php else: ?>

                        <span
                            class="inline-flex items-center
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-gray-100 text-gray-600"
                        >

                            <?php echo e(ucfirst($peminjaman->status)); ?>


                        </span>

                    <?php endif; ?>

                </div>

            </div>


            
            
            

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 sm:p-6
                        bg-gray-50/70">

                <div class="flex items-start gap-3">

                    <div class="w-10 h-10 rounded-xl bg-white
                                border border-gray-200
                                flex items-center justify-center">

                        📅

                    </div>

                    <div>

                        <p class="text-xs text-gray-500">
                            Tanggal Pinjam
                        </p>

                        <p class="mt-1 font-semibold text-gray-800">

                            <?php echo e($peminjaman->tgl_pinjam?->format('d F Y') ?? '-'); ?>


                        </p>

                    </div>

                </div>


                <div class="flex items-start gap-3">

                    <div class="w-10 h-10 rounded-xl bg-white
                                border border-gray-200
                                flex items-center justify-center">

                        🔄

                    </div>

                    <div>

                        <p class="text-xs text-gray-500">
                            Rencana Dikembalikan
                        </p>

                        <p class="mt-1 font-semibold text-gray-800">

                            <?php echo e($peminjaman->tgl_kembali_plan?->format('d F Y') ?? '-'); ?>


                        </p>

                    </div>

                </div>

            </div>


            
            
            

            <?php if(in_array($peminjaman->status, ['dipinjam', 'telat'])): ?>

                <div class="px-5 sm:px-6 pb-5">

                    <a
                        href="<?php echo e(route('peminjam.edit.form', $peminjaman->id)); ?>"
                        class="inline-flex items-center gap-2
                               px-4 py-2.5 rounded-xl
                               bg-amber-50 text-amber-700
                               border border-amber-200
                               font-semibold text-sm
                               hover:bg-amber-100
                               transition"
                    >

                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>

                        Ajukan Edit Peminjaman

                    </a>

                </div>

            <?php endif; ?>


            
            
            
            <?php if(in_array($peminjaman->status, ['dipinjam', 'telat'])): ?>

                <div class="px-5 sm:px-6 pb-5 flex flex-wrap items-center gap-3">

                    <?php
                        $pg = $peminjaman->pengembalian;

                        // Kalau validasi gagal, form harus langsung
                        // terbuka supaya peminjam melihat isi formnya
                        // beserta pesan error -- bukan halaman yang
                        // kembali ke tombol "Ajukan Lagi" tanpa
                        // penjelasan kenapa gagal.
                        $formTerbuka = $errors->has('catatan')
                            || $errors->has('diproses_oleh');
                    ?>

                    <?php if($pg && $pg->status_request === 'menunggu'): ?>

                        
                        <span class="inline-flex items-center gap-2
                                     px-3 py-1.5 rounded-full text-xs font-semibold
                                     bg-amber-50 text-amber-700 border border-amber-200">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Pengembalian Diajukan
                        </span>

                        <span class="text-xs text-gray-500">
                            Menunggu pemeriksaan Admin.
                        </span>

                    <?php elseif($pg && $pg->status_request === 'ditolak'): ?>

                        
                        <span class="inline-flex items-center gap-2
                                     px-3 py-1.5 rounded-full text-xs font-semibold
                                     bg-red-50 text-red-700 border border-red-200">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            Pengembalian Ditolak
                        </span>

                        
                        <button type="button"
                                onclick="document.getElementById('form-pengembalian-<?php echo e($peminjaman->id); ?>').classList.toggle('hidden')"
                                class="inline-flex items-center gap-2
                                       px-4 py-2 rounded-xl text-sm font-semibold
                                       bg-red-600 text-white
                                       hover:bg-red-700 transition">
                            Ajukan Lagi
                        </button>

                    <?php elseif($pg && $pg->status_request === 'disetujui'): ?>

                        <span class="inline-flex items-center gap-2
                                     px-3 py-1.5 rounded-full text-xs font-semibold
                                     bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Pengembalian Disetujui
                        </span>

                    <?php else: ?>

                        
                        <button type="button"
                                onclick="document.getElementById('form-pengembalian-<?php echo e($peminjaman->id); ?>').classList.toggle('hidden')"
                                class="inline-flex items-center gap-2
                                       px-4 py-2.5 rounded-xl text-sm font-semibold
                                       bg-emerald-600 text-white
                                       hover:bg-emerald-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 10h18M3 10a2 2 0 01.701-1.526l7-5.5a2 2 0 012.598 0l7 5.5A2 2 0 0121 10v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8z" />
                            </svg>
                            Kembalikan Alat
                        </button>

                    <?php endif; ?>

                </div>

                
                <?php if(!$pg || $pg->status_request !== 'menunggu'): ?>

                    <div id="form-pengembalian-<?php echo e($peminjaman->id); ?>"
                         class="<?php echo e($formTerbuka ? '' : 'hidden'); ?> px-5 sm:px-6 pb-5">

                        <form action="<?php echo e(route('peminjam.pengembalian.ajukan', $peminjaman->id)); ?>"
                              method="POST"
                              class="space-y-3 p-4 rounded-xl
                                     border border-emerald-200
                                     bg-emerald-50/50">

                            <?php echo csrf_field(); ?>

                            
                            <div>
                                <label for="diproses_oleh-<?php echo e($peminjaman->id); ?>"
                                       class="block text-sm font-semibold text-gray-800 mb-1.5">
                                    Diproses Oleh
                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    id="diproses_oleh-<?php echo e($peminjaman->id); ?>"
                                    name="diproses_oleh"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg
                                           focus:outline-none focus:ring-2 focus:ring-emerald-500
                                           text-sm bg-white">
                                    <option value="admin">Admin</option>
                                    <option value="petugas">Petugas</option>
                                </select>

                                <?php $__errorArgs = ['diproses_oleh'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                <p class="mt-1.5 text-xs text-gray-500">
                                    Pengajuan ini masuk ke antrean yang Anda pilih,
                                    dan masuk ke laporan milik orang yang menyetujuinya.
                                </p>
                            </div>

                            <div>
                                <label for="catatan-<?php echo e($peminjaman->id); ?>"
                                       class="block text-sm font-semibold text-gray-800 mb-1.5">
                                    Catatan Pengembalian
                                    <span class="text-red-500">*</span>
                                </label>

                                <textarea
                                    id="catatan-<?php echo e($peminjaman->id); ?>"
                                    name="catatan"
                                    rows="3"
                                    required
                                    minlength="3"
                                    maxlength="500"
                                    placeholder="Mis. sudah saya titip di pos satpam / saya bawa langsung ke kantor"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg
                                           focus:outline-none focus:ring-2 focus:ring-emerald-500
                                           text-sm resize-none"
                                ><?php echo e(old('catatan')); ?></textarea>

                                <?php $__errorArgs = ['catatan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                <p class="mt-1.5 text-xs text-gray-500">
                                    Kondisi barang dan denda kerusakan akan diperiksa
                                    oleh petugas/admin. Pastikan barang sudah diserahkan.
                                </p>
                            </div>

                            <button type="submit"
                                    class="inline-flex items-center gap-2
                                           px-4 py-2.5 rounded-xl text-sm font-semibold
                                           bg-emerald-600 text-white
                                           hover:bg-emerald-700 transition">
                                Kirim Pengajuan Pengembalian
                            </button>

                        </form>

                    </div>

                <?php endif; ?>

            <?php endif; ?>


            
            
            

            <div class="p-5 sm:p-6">

                <div class="flex items-center justify-between mb-4">

                    <div>

                        <h4 class="font-semibold text-gray-900">
                            Alat yang Dipinjam
                        </h4>

                        <p class="text-xs text-gray-500 mt-1">
                            <?php echo e($peminjaman->detailPinjams->count()); ?> jenis alat
                        </p>

                    </div>

                </div>


                <div class="space-y-3">

                    <?php $__currentLoopData = $peminjaman->detailPinjams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                        <div
                            class="flex items-center gap-4 p-3
                                   rounded-xl border border-gray-100
                                   bg-gray-50"
                        >

                            

                            <div
                                class="w-16 h-16 flex-shrink-0
                                       rounded-xl overflow-hidden
                                       bg-white border border-gray-200"
                            >

                                <?php if($detail->alatUnit?->alat?->gambar): ?>

                                    <img
                                        src="<?php echo e(asset($detail->alatUnit->alat->gambar)); ?>"
                                        alt="<?php echo e($detail->alatUnit->alat->nama_alat); ?>"
                                        class="w-full h-full object-cover"
                                    >

                                <?php else: ?>

                                    <div class="w-full h-full
                                                flex items-center justify-center
                                                text-gray-400 text-xl">

                                        📦

                                    </div>

                                <?php endif; ?>

                            </div>


                            

                            <div class="min-w-0 flex-1">

                                <h5 class="font-semibold text-gray-800 truncate">

                                    <?php echo e($detail->alatUnit?->alat->nama_alat ?? 'Alat Dihapus'); ?>


                                </h5>

                                <p class="text-xs text-gray-500 mt-1">

                                    <?php echo e($detail->alatUnit?->alat->kategori->nama_kategori ?? 'Tanpa kategori'); ?>


                                </p>

                            </div>


                            

                            <div class="text-right flex-shrink-0">

                                <p class="text-xs text-gray-500">
                                    Serial
                                </p>

                                <p class="font-bold text-gray-800 font-mono">

                                    <?php echo e($detail->alatUnit?->serial_number ?? '-'); ?>


                                </p>

                            </div>

                        </div>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </div>

            </div>

        </div>

    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

        
        
        

        <div
            class="bg-white border border-gray-200 rounded-2xl
                   shadow-sm p-10 text-center"
        >

            <div
                class="w-20 h-20 mx-auto rounded-2xl
                       bg-blue-50 flex items-center justify-center
                       text-4xl"
            >
                📋
            </div>

            <h3 class="mt-5 text-xl font-bold text-gray-900">

                Belum Ada Riwayat

            </h3>

            <p class="mt-2 max-w-md mx-auto text-gray-500">

                Kamu belum memiliki pengajuan peminjaman.
                Silakan pilih alat yang ingin kamu pinjam dari katalog.

            </p>

            <a
                href="<?php echo e(route('peminjam.katalog')); ?>"
                class="inline-flex items-center gap-2
                       mt-6 px-5 py-3
                       rounded-xl bg-blue-600 text-white
                       font-semibold hover:bg-blue-700
                       transition"
            >

                + Ajukan Peminjaman

            </a>

        </div>

    <?php endif; ?>


    
    
    

    <?php if($peminjamans->count() > 0): ?>

        <div class="flex justify-center mt-8">

            <a
                href="<?php echo e(route('peminjam.katalog')); ?>"
                class="inline-flex items-center gap-2
                       px-6 py-3 rounded-xl
                       bg-blue-600 text-white
                       font-semibold hover:bg-blue-700
                       shadow-sm hover:shadow-md transition"
            >

                <span class="text-lg">
                    +
                </span>

                Ajukan Peminjaman Baru

            </a>

        </div>

    <?php endif; ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.peminjam', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/peminjam/riwayat.blade.php ENDPATH**/ ?>