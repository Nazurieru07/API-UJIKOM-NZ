<?php $__env->startSection('title', 'Cetak Laporan - Petugas'); ?>
<?php $__env->startSection('header-title', 'Cetak Laporan'); ?>

<?php $__env->startSection('content'); ?>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">

    
    <div class="p-5 border-b border-gray-200">

        <div class="flex items-center justify-between">

            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Laporan Pengembalian Alat
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Daftar pencatatan pengembalian alat oleh petugas.
                </p>
            </div>

            
            <div class="relative inline-block text-left" id="cetakDropdown">
                <button
                    type="button"
                    onclick="document.getElementById('cetakMenu').classList.toggle('hidden')"
                    class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2"
                >
                    Cetak Laporan
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div
                    id="cetakMenu"
                    class="hidden absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
                >
                    <a
                        href="<?php echo e(route('petugas.laporan.excel', [
                            'tanggal_mulai' => $tanggalMulai,
                            'tanggal_selesai' => $tanggalSelesai
                        ])); ?>"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-100"
                    >
                        <span class="text-green-600">&#9638;</span>
                        Excel (.xlsx)
                    </a>

                    <a
                        href="<?php echo e(route('petugas.laporan.pdf', [
                            'tanggal_mulai' => $tanggalMulai,
                            'tanggal_selesai' => $tanggalSelesai
                        ])); ?>"
                        target="_blank"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <span class="text-red-600">&#9001;</span>
                        PDF (cetak)
                    </a>
                </div>
            </div>

        </div>

    </div>


    
    <div class="p-5 border-b border-gray-200 print:hidden">

        <form
            action="<?php echo e(route('petugas.laporan.index')); ?>"
            method="GET"
            class="flex flex-wrap items-end gap-3"
        >

            
            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Dari Tanggal
                </label>

                <input
                    type="date"
                    name="tanggal_mulai"
                    value="<?php echo e($tanggalMulai); ?>"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

            </div>


            
            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Sampai Tanggal
                </label>

                <input
                    type="date"
                    name="tanggal_selesai"
                    value="<?php echo e($tanggalSelesai); ?>"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

            </div>


            
            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Filter
            </button>


            
            <a
                href="<?php echo e(route('petugas.laporan.index')); ?>"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Reset
            </a>

        </form>

    </div>


    
    <div id="area-laporan" class="p-5">

        
        <div class="hidden print:block text-center mb-6">

            <h1 class="text-xl font-bold text-gray-800">
                LAPORAN PENGEMBALIAN ALAT
            </h1>

            <p class="text-sm text-gray-600 mt-1">
                Sistem Peminjaman Alat
            </p>

            <?php if($tanggalMulai || $tanggalSelesai): ?>

                <p class="text-sm text-gray-600 mt-2">

                    Periode:
                    
                    <?php echo e($tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('d-m-Y') : '...'); ?>


                    s/d

                    <?php echo e($tanggalSelesai ? \Carbon\Carbon::parse($tanggalSelesai)->format('d-m-Y') : '...'); ?>


                </p>

            <?php endif; ?>

        </div>


        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">

                <p class="text-sm text-gray-500">
                    Total Pengembalian
                </p>

                <p class="text-2xl font-bold text-gray-800 mt-1">
                    <?php echo e($pengembalians->count()); ?>

                </p>

            </div>


            <div class="bg-red-50 border border-red-200 rounded-lg p-4">

                <p class="text-sm text-red-600">
                    Total Denda
                </p>

                <p class="text-2xl font-bold text-red-700 mt-1">
                    Rp <?php echo e(number_format($pengembalians->sum('denda'), 0, ',', '.')); ?>

                </p>

            </div>


            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">

                <p class="text-sm text-orange-600">
                    Total Denda Kerusakan
                </p>

                <p class="text-2xl font-bold text-orange-700 mt-1">
                    Rp <?php echo e(number_format($pengembalians->sum('denda_kerusakan'), 0, ',', '.')); ?>

                </p>

            </div>

        </div>


        
        <div class="overflow-x-auto">

            <table class="w-full text-sm text-left border-collapse">

                <thead>

                    <tr class="bg-gray-100 text-gray-700 uppercase text-xs">

                        <th class="px-3 py-3 border">
                            No
                        </th>

                        <th class="px-3 py-3 border">
                            Peminjam
                        </th>

                        <th class="px-3 py-3 border">
                            Alat
                        </th>

                        <th class="px-3 py-3 border">
                            Tgl Pinjam
                        </th>

                        <th class="px-3 py-3 border">
                            Tgl Kembali
                        </th>

                        <th class="px-3 py-3 border">
                            Kondisi
                        </th>

                        <th class="px-3 py-3 border">
                            Denda
                        </th>

                        <th class="px-3 py-3 border">
                            Denda Kerusakan
                        </th>

                        <th class="px-3 py-3 border">
                            Petugas
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php $__empty_1 = true; $__currentLoopData = $pengembalians; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pengembalian): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                        <tr>

                            
                            <td class="px-3 py-3 border text-center">
                                <?php echo e($loop->iteration); ?>

                            </td>


                            
                            <td class="px-3 py-3 border">

                                <?php echo e($pengembalian->peminjaman->user->name ?? 'User Dihapus'); ?>


                            </td>


                            
                            <td class="px-3 py-3 border">

                                <ul class="list-disc list-inside">

                                    <?php $__currentLoopData = $pengembalian->peminjaman->detailPinjams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                        <li>
                                            <?php echo e($detail->alatUnit?->alat->nama_alat ?? 'Alat Dihapus'); ?>

                                            (<?php echo e($detail->alatUnit?->serial_number ?? '-'); ?>)
                                        </li>

                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                </ul>

                            </td>


                            
                            <td class="px-3 py-3 border">

                                <?php echo e($pengembalian->peminjaman->tgl_pinjam->format('d-m-Y')); ?>


                            </td>


                            
                            <td class="px-3 py-3 border">

                                <?php echo e($pengembalian->tgl_kembali->format('d-m-Y')); ?>


                            </td>


                            
                            <td class="px-3 py-3 border">

                                <?php echo e($pengembalian->kondisi_kembali); ?>


                            </td>


                            
                            <td class="px-3 py-3 border text-right">

                                Rp <?php echo e(number_format($pengembalian->denda, 0, ',', '.')); ?>


                            </td>


                            
                            <td class="px-3 py-3 border text-right">

                                Rp <?php echo e(number_format($pengembalian->denda_kerusakan, 0, ',', '.')); ?>


                            </td>


                            
                            <td class="px-3 py-3 border">

                                <?php if($pengembalian->petugas): ?>
                                    <?php echo e($pengembalian->petugas->name); ?>

                                <?php else: ?>
                                    Admin
                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="px-4 py-8 border text-center text-gray-500"
                            >
                                Belum ada data pengembalian.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        
        <div class="mt-4 print:hidden">
            <?php echo e($pengembalians->links()); ?>

        </div>


        
        <div class="hidden print:flex justify-end mt-10">

            <div class="text-center w-56">

                <p class="mb-16">
                    Petugas,
                </p>

                <p class="font-semibold border-b border-gray-800 pb-1">
                    <?php echo e(auth()->user()->name); ?>

                </p>

            </div>

        </div>

    </div>

</div>



<style>

@media print {

    @page {
        size: landscape;
        margin: 15mm;
    }

    body {
        background: white !important;
    }

    aside,
    header {
        display: none !important;
    }

    main {
        padding: 0 !important;
    }

    #area-laporan {
        padding: 0 !important;
    }

    table {
        font-size: 11px;
    }

    th,
    td {
        padding: 6px !important;
    }

}

</style>

<?php $__env->stopSection(); ?>


<script>
    document.addEventListener('click', function (e) {
        var dropdown = document.getElementById('cetakDropdown');
        var menu = document.getElementById('cetakMenu');
        if (dropdown && menu && !dropdown.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/petugas/laporan/index.blade.php ENDPATH**/ ?>