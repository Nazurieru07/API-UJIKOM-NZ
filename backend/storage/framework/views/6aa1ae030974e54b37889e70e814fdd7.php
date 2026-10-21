<?php $__env->startSection('title', 'Pemantauan Pengembalian - Petugas'); ?>
<?php $__env->startSection('header-title', 'Pemantauan Pengembalian'); ?>

<?php $__env->startSection('content'); ?>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">

    
    <div class="p-4 border-b border-gray-200">

        <h2 class="text-lg font-semibold text-gray-800">
            Daftar Peminjaman yang Belum Dikembalikan
        </h2>

        <p class="text-sm text-gray-500 mt-1">
            Data peminjaman yang saat ini masih dalam status dipinjam.
        </p>

        
        <form
            action="<?php echo e(route('petugas.pengembalian.index')); ?>"
            method="GET"
            class="mt-4 flex flex-col md:flex-row flex-wrap gap-2 w-full"
        >

            
            <input
                type="text"
                name="search"
                value="<?php echo e(request('search')); ?>"
                placeholder="Cari nama peminjam..."
                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            
            <select
                name="kategori_id"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">Semua Kategori</option>

                <?php $__currentLoopData = $kategoris; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kategori): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option
                        value="<?php echo e($kategori->id); ?>"
                        <?php echo e((string) request('kategori_id') === (string) $kategori->id ? 'selected' : ''); ?>

                    >
                        <?php echo e($kategori->nama_kategori); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            
            <input
                type="date"
                name="tanggal_dari"
                value="<?php echo e(request('tanggal_dari')); ?>"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            
            <input
                type="date"
                name="tanggal_sampai"
                value="<?php echo e(request('tanggal_sampai')); ?>"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            
            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition"
            >
                Cari
            </button>

            
            <?php if(request()->hasAny([
                'search',
                'kategori_id',
                'tanggal_dari',
                'tanggal_sampai'
            ])): ?>
                <a
                    href="<?php echo e(route('petugas.pengembalian.index')); ?>"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm flex items-center justify-center transition"
                >
                    Reset
                </a>
            <?php endif; ?>

        </form>

    </div>


    
    <?php if(session('success')): ?>
        <div class="m-4 bg-green-50 border border-green-200 text-green-800 p-3 rounded-lg text-sm">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>


    
    <?php if(session('error')): ?>
        <div class="m-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg text-sm">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>


    
    <div class="overflow-x-auto">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">

                <tr>

                    <th class="px-4 py-3">
                        Peminjam
                    </th>

                    <th class="px-4 py-3">
                        Alat
                    </th>

                    <th class="px-4 py-3">
                        Tanggal Pinjam
                    </th>

                    <th class="px-4 py-3">
                        Rencana Kembali
                    </th>

                    <th class="px-4 py-3 text-center">
                        Status
                    </th>

                    <th class="px-4 py-3 text-center">
                        Aksi
                    </th>

                </tr>

            </thead>

            <tbody class="divide-y divide-gray-200">

                <?php $__empty_1 = true; $__currentLoopData = $peminjamans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $peminjaman): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                    <tr class="hover:bg-gray-50">

                        
                        <td class="px-4 py-4 font-medium text-gray-800">

                            <?php echo e($peminjaman->user->name ?? 'User Dihapus'); ?>


                        </td>

                        
                        <td class="px-4 py-4">

                            <ul class="list-disc list-inside space-y-1">

                                <?php $__currentLoopData = $peminjaman->detailPinjams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                    <li>

                                        <?php echo e($detail->alatUnit->alat->nama_alat ?? 'Alat Dihapus'); ?>


                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                            <?php echo e($detail->alatUnit?->serial_number ?? '-'); ?>

                                        </span>

                                        <?php if($detail->alatUnit && $detail->alatUnit->alat && $detail->alatUnit->alat->kategori): ?>
                                            <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded">
                                                <?php echo e($detail->alatUnit->alat->kategori->nama_kategori); ?>

                                            </span>
                                        <?php endif; ?>

                                    </li>

                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            </ul>

                        </td>

                        
                        <td class="px-4 py-4 text-gray-600">

                            <?php echo e($peminjaman->tgl_pinjam->format('d-m-Y')); ?>


                        </td>

                        
                        <td class="px-4 py-4 text-gray-600">

                            <?php echo e($peminjaman->tgl_kembali_plan->format('d-m-Y')); ?>


                        </td>

                        
                        <td class="px-4 py-4 text-center">

                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">
                                <?php echo e(ucfirst($peminjaman->status)); ?>

                            </span>

                        </td>

                        
<td class="px-4 py-4 text-center">

    <?php
        // Dua jalur berbeda:
        // - pengajuan sudah ada dan dialokasikan ke petugas
        //   (peminjam yang mengajukan sendiri) -> petugas MEMERIKSA
        //   lalu menyetujui, tanpa mengisi ulang kondisi di sini.
        // - belum ada pengajuan -> petugas yang MengAJUKAN dengan
        //   mengisi kondisi + denda (form lama).
        $sudahDiajukan = $peminjaman->pengembalian
            && $peminjaman->pengembalian->status_request === 'menunggu';

        $dariPeminjam = $sudahDiajukan
            && $peminjaman->pengembalian->diproses_oleh === 'petugas';
    ?>

    <?php if($dariPeminjam): ?>

        <div class="flex flex-col gap-2 items-center">

            <span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-medium">
                Dari Peminjam
            </span>

            <?php if($peminjaman->pengembalian->catatan_peminjam): ?>
                <p class="text-xs text-gray-500 max-w-[220px] italic">
                    &ldquo;<?php echo e($peminjaman->pengembalian->catatan_peminjam); ?>&rdquo;
                </p>
            <?php endif; ?>

            <a
                href="<?php echo e(route('petugas.pengembalian.periksa', $peminjaman->pengembalian->id)); ?>"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
            >
                Periksa
            </a>

        </div>

    <?php else: ?>

    <form
        action="<?php echo e(route('petugas.pengembalian.ajukan', $peminjaman->id)); ?>"
        method="POST"
    >

        <?php echo csrf_field(); ?>

        <div class="flex flex-col gap-2">

            
            <select
                name="kondisi_kembali"
                required
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

                <option value="">
                    Pilih Kondisi
                </option>

                <option value="Baik">
                    Baik
                </option>

                <option value="Rusak">
                    Rusak
                </option>

            </select>

            
            <input
                type="number"
                name="denda_kerusakan"
                min="0"
                value="0"
                required
                placeholder="Denda Kerusakan"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >


            
            <button
                type="submit"
                onclick="return confirm('Apakah alat sudah benar-benar dikembalikan dan ingin mengajukan pengembalian kepada Admin?')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
            >
                Ajukan Pengembalian
            </button>

        </div>

    </form>

    <?php endif; ?>

</td>

                    </tr>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                    <tr>

                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-gray-500"
                        >
                            Tidak ada peminjaman yang sedang dipinjam.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="mt-4">
        <?php echo e($peminjamans->links()); ?>

    </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/petugas/pengembalian/index.blade.php ENDPATH**/ ?>