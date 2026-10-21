<?php $__env->startSection('title', 'Pengembalian - Panel Admin'); ?>
<?php $__env->startSection('header-title', 'Kelola Pengembalian'); ?>

<?php $__env->startSection('content'); ?>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">

    
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


     
<div class="p-4 border-b border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4">

    <h2 class="text-lg font-semibold text-gray-800">
        Data Pengembalian
    </h2>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">

        
        
        
        <form
            action="<?php echo e(route('admin.pengembalian.index')); ?>"
            method="GET"
            class="flex md:hidden w-full"
        >
            <input
                type="text"
                name="search"
                value="<?php echo e(request('search')); ?>"
                placeholder="Cari nama peminjam"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            <?php if(request('kondisi')): ?>
                <input type="hidden" name="kondisi" value="<?php echo e(request('kondisi')); ?>">
            <?php endif; ?>

            <?php if(request('status_request')): ?>
                <input type="hidden" name="status_request" value="<?php echo e(request('status_request')); ?>">
            <?php endif; ?>

            <?php if(request('jenis_kelamin')): ?>
                <input type="hidden" name="jenis_kelamin" value="<?php echo e(request('jenis_kelamin')); ?>">
            <?php endif; ?>

            <?php if(request('tanggal_dari')): ?>
                <input type="hidden" name="tanggal_dari" value="<?php echo e(request('tanggal_dari')); ?>">
            <?php endif; ?>

            <?php if(request('tanggal_sampai')): ?>
                <input type="hidden" name="tanggal_sampai" value="<?php echo e(request('tanggal_sampai')); ?>">
            <?php endif; ?>
        </form>


        
        
        
        <form
            action="<?php echo e(route('admin.pengembalian.index')); ?>"
            method="GET"
            class="hidden md:flex flex-wrap items-center gap-2"
        >

            
            <input
                type="text"
                name="search"
                value="<?php echo e(request('search')); ?>"
                placeholder="Cari nama peminjam"
                class="w-56 border border-gray-300 rounded-lg px-3 py-2 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            
            <select
                name="kondisi"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm
                       bg-white text-gray-700 focus:outline-none
                       focus:ring-2 focus:ring-blue-500 cursor-pointer"
            >
                <option value="">Semua Kondisi</option>

                <option value="Baik"
                    <?php echo e(request('kondisi') == 'Baik' ? 'selected' : ''); ?>>
                    Baik
                </option>

                <option value="Rusak"
                    <?php echo e(request('kondisi') === 'Rusak' ? 'selected' : ''); ?>>
                    Rusak
                </option>
            </select>


            
            <select
                name="status_request"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm
                       bg-white text-gray-700 focus:outline-none
                       focus:ring-2 focus:ring-blue-500 cursor-pointer"
            >
                <option value="">Semua Status</option>

                <option value="menunggu"
                    <?php echo e(request('status_request') == 'menunggu' ? 'selected' : ''); ?>>
                    Menunggu Persetujuan
                </option>

                <option value="disetujui"
                    <?php echo e(request('status_request') == 'disetujui' ? 'selected' : ''); ?>>
                    Disetujui
                </option>

                <option value="ditolak"
                    <?php echo e(request('status_request') == 'ditolak' ? 'selected' : ''); ?>>
                    Ditolak
                </option>
            </select>


            
            <select
                name="jenis_kelamin"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm
                       bg-white text-gray-700 focus:outline-none
                       focus:ring-2 focus:ring-blue-500 cursor-pointer"
            >
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


            
            <input
                type="date"
                name="tanggal_dari"
                value="<?php echo e(request('tanggal_dari')); ?>"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm
                       bg-white text-gray-700 focus:outline-none
                       focus:ring-2 focus:ring-blue-500"
                title="Tanggal Dari"
            >


            
            <input
                type="date"
                name="tanggal_sampai"
                value="<?php echo e(request('tanggal_sampai')); ?>"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm
                       bg-white text-gray-700 focus:outline-none
                       focus:ring-2 focus:ring-blue-500"
                title="Tanggal Sampai"
            >


            
            <?php if(
                request('search') ||
                request('kondisi') ||
                request('status_request') ||
                request('jenis_kelamin') ||
                request('tanggal_dari') ||
                request('tanggal_sampai')
            ): ?>
                <a
                    href="<?php echo e(route('admin.pengembalian.index')); ?>"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700
                           px-4 py-2 rounded-lg text-sm font-semibold
                           transition whitespace-nowrap"
                >
                    Reset
                </a>
            <?php endif; ?>


            
            <button
                type="submit"
                class="bg-gray-800 hover:bg-gray-900 text-white
                       px-4 py-2 rounded-lg text-sm font-semibold
                       transition"
            >
                Cari
            </button>

        </form>


        
        
        
        <details class="relative md:hidden w-full">

            <summary
                class="list-none cursor-pointer w-full
                       bg-gray-800 hover:bg-gray-900
                       text-white px-4 py-2 rounded-lg
                       text-sm font-semibold text-center
                       transition"
            >
                ☰ Filter
            </summary>


            
            <div
                class="absolute left-0 right-0 top-12
                       max-h-[70vh] overflow-y-auto
                       bg-white border border-gray-200
                       rounded-xl shadow-lg z-50 p-4"
            >

                <h4 class="font-semibold text-gray-800 mb-4">
                    Filter Pengembalian
                </h4>


                <form
                    action="<?php echo e(route('admin.pengembalian.index')); ?>"
                    method="GET"
                    class="space-y-3"
                >

                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Cari Peminjam
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="<?php echo e(request('search')); ?>"
                            placeholder="Cari nama peminjam..."
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                   focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>


                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Kondisi
                        </label>

                        <select
                            name="kondisi"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                   focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Semua Kondisi</option>

                            <option value="Baik"
                                <?php echo e(request('kondisi') == 'Baik' ? 'selected' : ''); ?>>
                                Baik
                            </option>

                            <option value="Rusak"
                                <?php echo e(request('kondisi') === 'Rusak' ? 'selected' : ''); ?>>
                                Rusak
                            </option>
                        </select>
                    </div>


                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status Pengajuan
                        </label>

                        <select
                            name="status_request"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                   focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Semua Status</option>

                            <option value="menunggu"
                                <?php echo e(request('status_request') == 'menunggu' ? 'selected' : ''); ?>>
                                Menunggu Persetujuan
                            </option>

                            <option value="disetujui"
                                <?php echo e(request('status_request') == 'disetujui' ? 'selected' : ''); ?>>
                                Disetujui
                            </option>

                            <option value="ditolak"
                                <?php echo e(request('status_request') == 'ditolak' ? 'selected' : ''); ?>>
                                Ditolak
                            </option>
                        </select>
                    </div>


                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Jenis Kelamin
                        </label>

                        <select
                            name="jenis_kelamin"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                   focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
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
                    </div>


                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tanggal Dari
                        </label>

                        <input
                            type="date"
                            name="tanggal_dari"
                            value="<?php echo e(request('tanggal_dari')); ?>"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                   focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>


                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tanggal Sampai
                        </label>

                        <input
                            type="date"
                            name="tanggal_sampai"
                            value="<?php echo e(request('tanggal_sampai')); ?>"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                   focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>


                    
                    <div class="flex flex-col gap-2 pt-2">

                        <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700
                                   text-white px-4 py-2 rounded-lg
                                   text-sm font-semibold transition"
                        >
                            Cari
                        </button>


                        <?php if(
                            request('search') ||
                            request('kondisi') ||
                            request('status_request') ||
                            request('jenis_kelamin') ||
                            request('tanggal_dari') ||
                            request('tanggal_sampai')
                        ): ?>
                            <a
                                href="<?php echo e(route('admin.pengembalian.index')); ?>"
                                class="w-full bg-gray-200 hover:bg-gray-300
                                       text-gray-700 px-4 py-2 rounded-lg
                                       text-sm font-semibold text-center
                                       transition"
                            >
                                Reset
                            </a>
                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </details>

    </div>

</div>

    
    <div class="overflow-x-auto">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">

                <tr>

                    <th class="px-4 py-3">
                        Peminjam
                    </th>

                    <th class="px-4 py-3">
                        Jenis Kelamin
                    </th>

                    <th class="px-4 py-3">
                        Alat
                    </th>

                    <th class="px-4 py-3">
                        Tanggal Kembali
                    </th>

                    <th class="px-4 py-3">
                        Kondisi
                    </th>

                    <th class="px-4 py-3">
                        Denda Keterlambatan
                    </th>

                    <th class="px-4 py-3">
                        Denda Kerusakan
                    </th>

                    <th class="px-4 py-3">
                        Status Pengajuan
                    </th>

                    <th class="px-4 py-3">
                        Petugas
                    </th>

                    <th class="px-4 py-3 text-center">
                        Aksi
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-200">

                <?php $__empty_1 = true; $__currentLoopData = $pengembalians; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pengembalian): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                    <tr class="hover:bg-gray-50">

                        
                        <td class="px-4 py-4 font-medium text-gray-800">

                            <?php echo e($pengembalian->peminjaman->user->name); ?>


                        </td>

                        <td class="px-4 py-4">
    <?php if($pengembalian->peminjaman->user->jenis_kelamin == 'Laki-laki'): ?>
        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
            Laki-laki
        </span>
    <?php elseif($pengembalian->peminjaman->user->jenis_kelamin == 'Perempuan'): ?>
        <span class="bg-pink-100 text-pink-800 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
            Perempuan
        </span>
    <?php else: ?>
        <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
            -
        </span>
    <?php endif; ?>
</td>


                        
                        <td class="px-4 py-4">

                            <ul class="list-disc list-inside space-y-1 whitespace-nowrap">

                                <?php $__currentLoopData = $pengembalian->peminjaman->detailPinjams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                    <li>

                                        <?php echo e($detail->alatUnit->alat->nama_alat); ?>


                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                            <?php echo e($detail->alatUnit?->serial_number ?? '-'); ?>

                                        </span>

                                    </li>

                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            </ul>

                        </td>


                        
                        <td class="px-4 py-4 text-gray-600">

                            <?php echo e($pengembalian->tgl_kembali->format('d-m-Y')); ?>


                        </td>


                        
                        <td class="px-4 py-4">

                            <?php if($pengembalian->kondisi_kembali == 'Baik'): ?>

                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">
                                    Baik
                                </span>

                            <?php elseif($pengembalian->kondisi_kembali === 'Rusak'
                                || $pengembalian->kondisi_kembali === 'Rusak'): ?>

                                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                                    Rusak
                                </span>

                            <?php else: ?>

                                <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium">
                                    <?php echo e($pengembalian->kondisi_kembali); ?>

                                </span>

                            <?php endif; ?>

                        </td>


                        
                        <td class="px-4 py-4 font-semibold text-red-600">

                            Rp <?php echo e(number_format($pengembalian->denda, 0, ',', '.')); ?>


                        </td>

                        
                        <td class="px-4 py-4 font-semibold text-red-600">

                            Rp <?php echo e(number_format($pengembalian->denda_kerusakan ?? 0, 0, ',', '.')); ?>


                        </td>

                        
                    <td class="px-4 py-4">

                        <?php if($pengembalian->status_request == 'menunggu'): ?>

                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                                Menunggu Persetujuan
                            </span>

                        <?php elseif($pengembalian->status_request == 'disetujui'): ?>

                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">
                                Disetujui
                            </span>

                        <?php elseif($pengembalian->status_request == 'ditolak'): ?>

                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-medium">
                                Ditolak
                            </span>

                        <?php endif; ?>

                    </td>


                        
                        <td class="px-4 py-4 text-gray-600">

                            <?php echo e($pengembalian->petugas?->name ?? 'Admin'); ?>


                        </td>


                        
                        <td class="px-4 py-4 text-center">

                            <div class="flex flex-col gap-2">

                                <?php if($pengembalian->status_request == 'menunggu'): ?>

                                    
                                    <a
                                        href="<?php echo e(route('admin.pengembalian.edit', $pengembalian->id)); ?>"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
                                    >
                                        Periksa
                                    </a>

                                <?php endif; ?>


                                
                                <form
                                    action="<?php echo e(route('admin.pengembalian.destroy', $pengembalian->id)); ?>"
                                    method="POST"
                                    onsubmit="return confirm('Apakah kamu yakin ingin menghapus catatan pengembalian ini?')"
                                >

                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>

                                    <button
                                        type="submit"
                                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
                                    >
                                        Hapus
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                    <tr>

                        <td
                            colspan="10"
                            class="px-4 py-8 text-center text-gray-500"
                        >

                            Belum ada data pengembalian.

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>


    
    <?php if($pengembalians->hasPages()): ?>

        <div class="p-4 border-t border-gray-200">

            <?php echo e($pengembalians->links()); ?>


        </div>

    <?php endif; ?>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/pengembalian/index.blade.php ENDPATH**/ ?>