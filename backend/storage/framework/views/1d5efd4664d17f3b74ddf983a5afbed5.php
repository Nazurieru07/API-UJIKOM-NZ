

<table>
    <tr>
        <td colspan="9" style="font-size:14px;font-weight:bold;text-align:center;">
            LAPORAN PENGEMBALIAN ALAT
        </td>
    </tr>
    <tr>
        <td colspan="9" style="text-align:center;">
            Sistem Peminjaman Alat
        </td>
    </tr>
    <tr>
        <td colspan="9" style="text-align:center;">
            <?php if($tanggalMulai || $tanggalSelesai): ?>
                Periode:
                <?php echo e($tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('d-m-Y') : '...'); ?>

                s/d
                <?php echo e($tanggalSelesai ? \Carbon\Carbon::parse($tanggalSelesai)->format('d-m-Y') : '...'); ?>

            <?php else: ?>
                Semua Data Pengembalian
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td colspan="9"></td>
    </tr>
    <tr>
        <td style="font-weight:bold;">Total Pengembalian</td>
        <td style="font-weight:bold;"><?php echo e($pengembalians->count()); ?></td>
        <td style="font-weight:bold;">Total Denda</td>
        <td style="font-weight:bold;">Rp <?php echo e(number_format($totalDenda, 0, ',', '.')); ?></td>
        <td style="font-weight:bold;">Total Denda Kerusakan</td>
        <td colspan="4" style="font-weight:bold;">
            Rp <?php echo e(number_format($totalDendaKerusakan, 0, ',', '.')); ?>

        </td>
    </tr>
    <tr></tr>
    <tr>
        <th>No</th>
        <th>Peminjam</th>
        <th>Alat</th>
        <th>Tgl Pinjam</th>
        <th>Tgl Kembali</th>
        <th>Kondisi</th>
        <th>Denda</th>
        <th>Denda Kerusakan</th>
        <th>Petugas</th>
    </tr>

    <?php $__empty_1 = true; $__currentLoopData = $pengembalians; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pengembalian): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
            <td style="text-align:center;"><?php echo e($loop->iteration); ?></td>
            <td><?php echo e($pengembalian->peminjaman->user->name ?? 'User Dihapus'); ?></td>
            <td>
                <?php $__currentLoopData = $pengembalian->peminjaman->detailPinjams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo e($detail->alatUnit?->alat?->nama_alat ?? 'Alat Dihapus'); ?> (<?php echo e($detail->alatUnit?->serial_number ?? '-'); ?>)<?php if(!$loop->last): ?>, <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </td>
            <td style="text-align:center;">
                <?php echo e($pengembalian->peminjaman->tgl_pinjam->format('d-m-Y')); ?>

            </td>
            <td style="text-align:center;">
                <?php echo e($pengembalian->tgl_kembali->format('d-m-Y')); ?>

            </td>
            <td><?php echo e($pengembalian->kondisi_kembali); ?></td>
            <td style="text-align:right;">
                Rp <?php echo e(number_format($pengembalian->denda, 0, ',', '.')); ?>

            </td>
            <td style="text-align:right;">
                Rp <?php echo e(number_format($pengembalian->denda_kerusakan, 0, ',', '.')); ?>

            </td>
            <td>
                <?php if($pengembalian->petugas): ?>
                    <?php echo e($pengembalian->petugas->name); ?>

                <?php else: ?>
                    Admin
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
            <td colspan="9" style="text-align:center;">Belum ada data pengembalian.</td>
        </tr>
    <?php endif; ?>
</table>
<?php /**PATH /var/www/resources/views/exports/laporan-pengembalian.blade.php ENDPATH**/ ?>