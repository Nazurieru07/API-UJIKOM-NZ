{{-- Template Excel untuk Laporan Peminjaman Aktif.
     Dipakai oleh App\Exports\LaporanPeminjamanAktifExport (FromView).
     Baris 1: judul, 2: periode, 3: ringkasan, 4: header tabel. --}}

<table>
    <tr>
        <td colspan="8" style="font-size:14px;font-weight:bold;text-align:center;">
            LAPORAN BARANG SEDANG DIPINJAM
        </td>
    </tr>
    <tr>
        <td colspan="8" style="text-align:center;">
            Sistem Peminjaman Alat
        </td>
    </tr>
    <tr>
        <td colspan="8" style="text-align:center;">
            @if($tanggalMulai || $tanggalSelesai)
                Periode:
                {{ $tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('d-m-Y') : '...' }}
                s/d
                {{ $tanggalSelesai ? \Carbon\Carbon::parse($tanggalSelesai)->format('d-m-Y') : '...' }}
            @else
                Semua Data Peminjaman Aktif
            @endif
        </td>
    </tr>
    <tr>
        <td colspan="8"></td>
    </tr>
    <tr>
        <td style="font-weight:bold;">Total Peminjaman Aktif</td>
        <td style="font-weight:bold;">{{ $totalPeminjaman }}</td>
        <td style="font-weight:bold;">Total Unit Dipinjam</td>
        <td style="font-weight:bold;">{{ $totalUnit }}</td>
        <td style="font-weight:bold;">Terlambat</td>
        <td colspan="3" style="font-weight:bold;">{{ $totalTelat }}</td>
    </tr>
    <tr></tr>
    <tr>
        <th>No</th>
        <th>Peminjam</th>
        <th>Alat</th>
        <th>Tgl Pinjam</th>
        <th>Rencana Kembali</th>
        <th>Status</th>
        <th>Petugas Penyetuju</th>
        <th>Unit Dikembalikan</th>
    </tr>

    @forelse($peminjamans as $peminjaman)
        <tr>
            <td style="text-align:center;">{{ $loop->iteration }}</td>
            <td>{{ $peminjaman->user->name ?? 'User Dihapus' }}</td>
            <td>
                @foreach($peminjaman->detailPinjams as $detail)
                    {{ $detail->alatUnit?->alat?->nama_alat ?? 'Alat Dihapus' }} ({{ $detail->alatUnit?->serial_number ?? '-' }})@if(!$loop->last), @endif
                @endforeach
            </td>
            <td style="text-align:center;">
                {{ $peminjaman->tgl_pinjam->format('d-m-Y') }}
            </td>
            <td style="text-align:center;">
                {{ $peminjaman->tgl_kembali_plan->format('d-m-Y') }}
            </td>
            <td style="text-align:center;">
                @if($peminjaman->status == 'dipinjam')
                    <span style="color:#1d4ed8;font-weight:bold;">Sedang Dipinjam</span>
                @elseif($peminjaman->status == 'telat')
                    <span style="color:#dc2626;font-weight:bold;">Terlambat</span>
                @endif
            </td>
            <td>
                @if($peminjaman->pengembalian && $peminjaman->pengembalian->petugas)
                    {{ $peminjaman->pengembalian->petugas->name }}
                @else
                    Belum ada pengembalian
                @endif
            </td>
            <td style="text-align:center;">
                @if($peminjaman->pengembalian && $peminjaman->pengembalian->is_parsial)
                    {{ $peminjaman->pengembalian->jumlah_unit_dikembalikan }} / {{ $peminjaman->detailPinjams->count() }}
                @else
                    —
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="text-align:center;">Tidak ada data peminjaman aktif.</td>
        </tr>
    @endforelse
</table>