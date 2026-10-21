<?php $__env->startSection('title', 'Persetujuan Peminjaman - Dashboard Petugas'); ?>
<?php $__env->startSection('header-title', 'Daftar Pengajuan Peminjaman Alat'); ?>

<?php $__env->startSection('content'); ?>

    <?php if(session('success')): ?>
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Menunggu Verifikasi Persetujuan</h3>

            <form action="<?php echo e(route('petugas.peminjaman.index')); ?>" method="GET"
    class="flex flex-col md:flex-row gap-2 w-full">

    <input
        type="text"
        name="search"
        value="<?php echo e(request('search')); ?>"
        placeholder="Cari nama peminjam..."
        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <select
        name="jenis_kelamin"
        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

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
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <input
    type="date"
    name="tanggal_sampai"
    value="<?php echo e(request('tanggal_sampai')); ?>"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <select
    name="alat_id"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <option value="">Semua Alat</option>

    <?php $__currentLoopData = $daftarAlat; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option
            value="<?php echo e($alat->id); ?>"
            <?php echo e(request('alat_id') == $alat->id ? 'selected' : ''); ?>>
            <?php echo e($alat->nama_alat); ?>

        </option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

</select>

<select
    name="status"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <option value="">Semua Status</option>

    <option value="diajukan" <?php echo e(request('status') == 'diajukan' ? 'selected' : ''); ?>>
        Diajukan
    </option>

    <option value="dipinjam" <?php echo e(request('status') == 'dipinjam' ? 'selected' : ''); ?>>
        Dipinjam
    </option>

    <option value="dikembalikan" <?php echo e(request('status') == 'dikembalikan' ? 'selected' : ''); ?>>
        Dikembalikan
    </option>

    <option value="telat" <?php echo e(request('status') == 'telat' ? 'selected' : ''); ?>>
        Telat
    </option>

</select>

    <button
        type="submit"
        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition">
        Cari
    </button>

    <?php if(request()->hasAny([
    'search',
    'jenis_kelamin',
    'tanggal_dari',
    'tanggal_sampai',
    'alat_id',
    'status'
])): ?>
        <a href="<?php echo e(route('petugas.peminjaman.index')); ?>"
            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center justify-center transition">
            Reset
        </a>
    <?php endif; ?>

</form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                    <th class="py-3 px-4 border-b">Peminjam</th>
                    <th class="py-3 px-4 border-b">Jenis Kelamin</th>
                    <th class="py-3 px-4 border-b">Tanggal Pinjam</th>
                    <th class="py-3 px-4 border-b">Rencana Kembali</th>
                    <th class="py-3 px-4 border-b">Detail Alat</th>
                    <th class="py-3 px-4 border-b text-center">Status Peminjaman</th>
                    <th class="py-3 px-4 border-b text-center">Aksi</th>
                </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    <?php $__empty_1 = true; $__currentLoopData = $peminjamans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-gray-50 transition align-top">

                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                <?php echo e($item->user->name ?? 'User Dihapus'); ?>

                            </td>

                            <td class="py-3 px-4 border-b">
    <?php if(optional($item->user)->jenis_kelamin == 'Laki-laki'): ?>
        <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">
            Laki-laki
        </span>

    <?php elseif(optional($item->user)->jenis_kelamin == 'Perempuan'): ?>
        <span class="px-2 py-0.5 text-xs rounded-full bg-pink-100 text-pink-700">
            Perempuan
        </span>

    <?php else: ?>
        <span class="text-gray-400 text-xs">-</span>
    <?php endif; ?>
</td>

                            <td class="py-3 px-4 border-b">
                                <?php echo e($item->tgl_pinjam); ?>

                            </td>

                            <td class="py-3 px-4 border-b">
                                <?php echo e($item->tgl_kembali_plan); ?>

                            </td>

                            <td class="py-3 px-4 border-b">
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    <?php $__currentLoopData = $item->detailPinjams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li>
                                            <button
                                            type="button"
                                            class="font-semibold text-blue-600 hover:text-blue-800 hover:underline transition"
                                            onclick='openAlatModal({
                                                nama: <?php echo json_encode($detail->alatUnit->alat->nama_alat, 15, 512) ?>,
                                                kategori: <?php echo json_encode(optional($detail->alatUnit->alat->kategori)->nama_kategori ?? "-", 15, 512) ?>,
                                                jumlah_tersedia: <?php echo e($detail->alatUnit->alat->alatUnit->where('kondisi', 'tersedia')->count()); ?>,
                                                jumlah_dipinjam: <?php echo e($detail->alatUnit->alat->alatUnit->where('kondisi', 'dipinjam')->count()); ?>,
                                                jumlah_rusak: <?php echo e($detail->alatUnit->alat->alatUnit->where('kondisi', 'rusak')->count()); ?>,
                                                deskripsi: <?php echo json_encode($detail->alatUnit->alat->deskripsi ?? "Tidak ada deskripsi.", 15, 512) ?>
                                            })'>

                                            <?php echo e($detail->alatUnit->alat->nama_alat ?? 'Alat Dihapus'); ?>


                                        </button>
                                            <br>
                                            (<?php echo e($detail->alatUnit?->serial_number ?? '-'); ?>)
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </td>

                            <td class="py-3 px-4 border-b text-center">
    <?php if($item->status == 'diajukan'): ?>
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">
            Diajukan
        </span>

    <?php elseif($item->status == 'dipinjam'): ?>
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
            Dipinjam
        </span>

    <?php elseif($item->status == 'dikembalikan'): ?>
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
            Dikembalikan
        </span>

    <?php elseif($item->status == 'telat'): ?>
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
            Telat
        </span>

    <?php else: ?>
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
            <?php echo e(ucfirst($item->status)); ?>

        </span>
    <?php endif; ?>
</td>

                            <td class="py-3 px-4 border-b text-center">

    <?php if($item->status == 'diajukan'): ?>

        <div class="flex justify-center items-center space-x-2">

            
            <form action="<?php echo e(route('petugas.peminjaman.setujui', $item->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <button type="submit"
                    onclick="return confirm('Setujui peminjaman alat ini?')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition shadow-sm">
                    Setujui
                </button>
            </form>


            
            <form action="<?php echo e(route('petugas.peminjaman.tolak', $item->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <button type="submit"
                    onclick="return confirm('Yakin ingin menolak pengajuan peminjaman ini?')"
                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition shadow-sm">
                    Tolak
                </button>
            </form>

        </div>

   <?php else: ?>
    <span class="text-gray-400 text-sm">-</span>
<?php endif; ?>

</td>

                        </tr>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-500">
                                Tidak ada pengajuan peminjaman baru.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div id="alatModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">

        <div class="flex justify-between items-center p-5 border-b">
            <h3 class="text-lg font-bold">Detail Alat</h3>

            <button
                type="button"
                onclick="closeAlatModal()"
                class="text-gray-500 hover:text-red-500 text-2xl">

                ×

            </button>
        </div>

        <div class="p-5 space-y-4">

            <div>
                <p class="text-sm text-gray-500">Nama Alat</p>
                <p id="modalNama" class="font-semibold"></p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Kategori</p>
                <p id="modalKategori"></p>
            </div>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <p class="text-sm text-gray-500">Tersedia</p>
                    <p id="modalTersedia" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Sedang Dipinjam</p>
                    <p id="modalDipinjam" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Rusak</p>
                    <p id="modalRusak" class="font-semibold"></p>
                </div>

            </div>

            <div>
                <p class="text-sm text-gray-500">Deskripsi</p>
                <p id="modalDeskripsi" class="text-sm"></p>
            </div>

        </div>

        <div class="border-t p-4 flex justify-end">

            <button
                type="button"
                onclick="closeAlatModal()"
                class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg">

                Tutup

            </button>

        </div>

    </div>

</div>

        </div>
    </div>


<script>
function openAlatModal(alat) {

    document.getElementById('modalNama').textContent = alat.nama;
    document.getElementById('modalKategori').textContent = alat.kategori;
    document.getElementById('modalTersedia').textContent = alat.jumlah_tersedia + ' unit';
    document.getElementById('modalDipinjam').textContent = alat.jumlah_dipinjam + ' unit';
    document.getElementById('modalRusak').textContent = alat.jumlah_rusak + ' unit';
    document.getElementById('modalDeskripsi').textContent = alat.deskripsi;

    const modal = document.getElementById('alatModal');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAlatModal() {

    const modal = document.getElementById('alatModal');

    modal.classList.remove('flex');
    modal.classList.add('hidden');
}
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/petugas/peminjaman/index.blade.php ENDPATH**/ ?>