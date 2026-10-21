<?php $__env->startSection('title', 'Kelola Peminjaman - Panel Admin'); ?>
<?php $__env->startSection('header-title', 'Manajemen Transaksi Peminjaman'); ?>

<?php $__env->startSection('content'); ?>

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

        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">

    <h3 class="text-lg font-bold text-gray-800">
        Daftar Transaksi Peminjaman
    </h3>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">

        
        
        
        <form
            action="<?php echo e(route('admin.peminjaman.index')); ?>"
            method="GET"
            class="flex md:hidden w-full"
        >
            <input
                type="text"
                name="search"
                value="<?php echo e(request('search')); ?>"
                placeholder="Cari nama peminjam / status..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            
            <?php if(request('status')): ?>
                <input
                    type="hidden"
                    name="status"
                    value="<?php echo e(request('status')); ?>"
                >
            <?php endif; ?>

            <?php if(request('jenis_kelamin')): ?>
                <input
                    type="hidden"
                    name="jenis_kelamin"
                    value="<?php echo e(request('jenis_kelamin')); ?>"
                >
            <?php endif; ?>

            <?php if(request('tanggal_dari')): ?>
                <input
                    type="hidden"
                    name="tanggal_dari"
                    value="<?php echo e(request('tanggal_dari')); ?>"
                >
            <?php endif; ?>

            <?php if(request('tanggal_sampai')): ?>
                <input
                    type="hidden"
                    name="tanggal_sampai"
                    value="<?php echo e(request('tanggal_sampai')); ?>"
                >
            <?php endif; ?>
        </form>


        
        
        
        <form
            action="<?php echo e(route('admin.peminjaman.index')); ?>"
            method="GET"
            class="hidden md:flex flex-wrap items-center gap-2"
        >

            
            <input
                type="text"
                name="search"
                value="<?php echo e(request('search')); ?>"
                placeholder="Cari nama peminjam / status..."
                class="w-64 px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            
            <select
                name="status"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">Semua Status</option>

                <option value="diajukan"
                    <?php echo e(request('status') == 'diajukan' ? 'selected' : ''); ?>>
                    Diajukan
                </option>

                <option value="dipinjam"
                    <?php echo e(request('status') == 'dipinjam' ? 'selected' : ''); ?>>
                    Dipinjam
                </option>

                <option value="dikembalikan"
                    <?php echo e(request('status') == 'dikembalikan' ? 'selected' : ''); ?>>
                    Dikembalikan
                </option>

                <option value="telat"
                    <?php echo e(request('status') == 'telat' ? 'selected' : ''); ?>>
                    Telat
                </option>
            </select>


            
            <select
                name="jenis_kelamin"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg
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


            
            <input
                type="date"
                name="tanggal_dari"
                value="<?php echo e(request('tanggal_dari')); ?>"
                title="Tanggal Pinjam Dari"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >


            
            <input
                type="date"
                name="tanggal_sampai"
                value="<?php echo e(request('tanggal_sampai')); ?>"
                title="Tanggal Pinjam Sampai"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >


            
            <?php if(
                request('search') ||
                request('status') ||
                request('jenis_kelamin') ||
                request('tanggal_dari') ||
                request('tanggal_sampai')
            ): ?>
                <a
                    href="<?php echo e(route('admin.peminjaman.index')); ?>"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-700
                           px-3 py-2 text-sm rounded-lg flex items-center
                           transition whitespace-nowrap"
                >
                    Reset
                </a>
            <?php endif; ?>


            
            <button
                type="submit"
                class="bg-gray-800 hover:bg-gray-900 text-white
                       px-4 py-2 text-sm font-semibold rounded-lg transition"
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
                    Filter Peminjaman
                </h4>


                <form
                    action="<?php echo e(route('admin.peminjaman.index')); ?>"
                    method="GET"
                    class="space-y-3"
                >

                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Cari Peminjam / Status
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="<?php echo e(request('search')); ?>"
                            placeholder="Cari nama peminjam / status..."
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                        >
                    </div>


                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Semua Status</option>

                            <option value="diajukan"
                                <?php echo e(request('status') == 'diajukan' ? 'selected' : ''); ?>>
                                Diajukan
                            </option>

                            <option value="dipinjam"
                                <?php echo e(request('status') == 'dipinjam' ? 'selected' : ''); ?>>
                                Dipinjam
                            </option>

                            <option value="dikembalikan"
                                <?php echo e(request('status') == 'dikembalikan' ? 'selected' : ''); ?>>
                                Dikembalikan
                            </option>

                            <option value="telat"
                                <?php echo e(request('status') == 'telat' ? 'selected' : ''); ?>>
                                Telat
                            </option>
                        </select>
                    </div>


                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Jenis Kelamin
                        </label>

                        <select
                            name="jenis_kelamin"
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
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
                            Tanggal Pinjam Dari
                        </label>

                        <input
                            type="date"
                            name="tanggal_dari"
                            value="<?php echo e(request('tanggal_dari')); ?>"
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                        >
                    </div>


                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tanggal Pinjam Sampai
                        </label>

                        <input
                            type="date"
                            name="tanggal_sampai"
                            value="<?php echo e(request('tanggal_sampai')); ?>"
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
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


                        
<a
    href="<?php echo e(route('admin.peminjaman.index')); ?>"
    class="w-full bg-gray-300 hover:bg-gray-400
           text-gray-700 px-4 py-2 rounded-lg
           text-sm font-semibold text-center
           transition"
>
    Reset
</a>

                    </div>

                </form>

            </div>

        </details>


        
        
        
        <a
            href="<?php echo e(route('admin.peminjaman.create')); ?>"
            class="bg-blue-600 hover:bg-blue-700 text-white
                   text-sm font-semibold px-4 py-2 rounded-lg
                   transition whitespace-nowrap text-center"
        >
            + Tambah Peminjaman
        </a>

    </div>

</div>

        <div class="overflow-x-auto">

            <table class="w-full text-left border-collapse">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Peminjam</th>
                        <th class="py-3 px-4 border-b">Jenis Kelamin</th>
                        <th class="py-3 px-4 border-b">Alat yang Dipinjam</th>
                        <th class="py-3 px-4 border-b">Tgl Pinjam / Rencana Kembali</th>
                        <th class="py-3 px-4 border-b">Status</th>
                        <th class="py-3 px-4 border-b">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">

                    <?php $__empty_1 = true; $__currentLoopData = $peminjamans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $peminjaman): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                        <tr class="hover:bg-gray-50 transition align-top">

                            <!-- Peminjam -->
                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                <?php echo e($peminjaman->user->name ?? 'User Dihapus'); ?>

                            </td>

                            <!-- Jenis Kelamin -->
<td class="py-3 px-4 border-b">
    <?php if($peminjaman->user?->jenis_kelamin === 'Laki-laki'): ?>
        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
            Laki-laki
        </span>
    <?php elseif($peminjaman->user?->jenis_kelamin === 'Perempuan'): ?>
        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-700">
            Perempuan
        </span>
    <?php else: ?>
        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
            Belum diisi
        </span>
    <?php endif; ?>
</td>

                            <!-- Alat yang Dipinjam -->
                            <td class="py-3 px-4 border-b">

                                <ul class="list-disc list-inside space-y-1">

                                    <?php $__currentLoopData = $peminjaman->detailPinjams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                        <li>
                                            <span class="font-semibold">
                                                <?php echo e($detail->alatUnit->alat->nama_alat ?? 'Alat Dihapus'); ?>

                                            </span>

                                            <span class="text-xs bg-gray-200 px-1.5 py-0.5 rounded">
                                                (<?php echo e($detail->alatUnit?->serial_number ?? '-'); ?>)
                                            </span>
                                        </li>

                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                </ul>

                            </td>

                            <!-- Tanggal -->
                            <td class="py-3 px-4 border-b text-xs text-gray-600">

                                <span class="block">
                                    Pinjam: <?php echo e($peminjaman->tgl_pinjam); ?>

                                </span>

                                <span class="block font-semibold">
                                    Rencana: <?php echo e($peminjaman->tgl_kembali_plan); ?>

                                </span>

                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4 border-b">

                                <span
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full
                                    <?php if($peminjaman->status == 'diajukan'): ?>
                                        bg-yellow-100 text-yellow-800
                                    <?php elseif($peminjaman->status == 'dipinjam'): ?>
                                        bg-blue-100 text-blue-800
                                    <?php elseif($peminjaman->status == 'dikembalikan'): ?>
                                        bg-emerald-100 text-emerald-800
                                    <?php else: ?>
                                        bg-red-100 text-red-800
                                    <?php endif; ?>"
                                >
                                    <?php echo e(ucfirst($peminjaman->status)); ?>

                                </span>

                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 border-b">

                                <div class="flex flex-col space-y-2">

                                    <!-- Aksi Status / Pengembalian -->

<?php if($peminjaman->status === 'diajukan'): ?>

    
    <form
        action="<?php echo e(route('admin.peminjaman.updateStatus', $peminjaman->id)); ?>"
        method="POST"
        class="w-full"
    >

        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <select
            name="status"
            onchange="this.form.submit()"
            class="text-xs font-semibold border-0 rounded-lg px-3 py-2
                   focus:outline-none focus:ring-2 focus:ring-blue-500
                   cursor-pointer appearance-auto
                   bg-yellow-100 text-yellow-800 w-full"
        >

            <option value="diajukan" selected>
                Diajukan
            </option>

            <option value="dipinjam">
                Dipinjam
            </option>

        </select>

    </form>


<?php elseif($peminjaman->status === 'dipinjam' || $peminjaman->status === 'telat'): ?>

    
    <a
        href="<?php echo e(route('admin.peminjaman.pengembalian.form', $peminjaman->id)); ?>"
        class="bg-blue-600 hover:bg-blue-700
               text-white px-3 py-2 rounded-lg
               text-xs font-semibold transition
               text-center w-full"
    >
        Buat Pengembalian
    </a>


<?php elseif($peminjaman->status === 'dikembalikan'): ?>

    
    <span
        class="bg-emerald-100 text-emerald-800
               px-3 py-2 rounded-lg
               text-xs font-semibold text-center"
    >
        Sudah Dikembalikan
    </span>

<?php endif; ?>

                                    <!-- Tombol Hapus -->
                                    <form
                                        action="<?php echo e(route('admin.peminjaman.destroy', $peminjaman->id)); ?>"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus data peminjaman ini?')"
                                    >

                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>

                                        <button
                                            type="submit"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold transition w-full"
                                        >
                                            Hapus
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                        <tr>
                            <td colspan="6" class="py-4 text-center text-gray-500">
                                Belum ada data peminjaman.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <?php echo e($peminjamans->links()); ?>

        </div>

    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/peminjaman/index.blade.php ENDPATH**/ ?>