{{-- Template Excel untuk Laporan Pengembalian.
     Dipakai oleh App\Exports\LaporanPengembalianExport (FromView).
     Baris 1: judul, 2: periode, 3: ringkasan, 4: header tabel. --}}

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
            @if($tanggalMulai || $tanggalSelesai)
                Periode:
                {{ $tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('d-m-Y') : '...' }}
                s/d
                {{ $tanggalSelesai ? \Carbon\Carbon::parse($tanggalSelesai)->format('d-m-Y') : '...' }}
            @else
                Semua Data Pengembalian
            @endif
        </td>
    </tr>
    <tr>
        <td colspan="9"></td>
    </tr>
    <tr>
        <td style="font-weight:bold;">Total Pengembalian</td>
        <td style="font-weight:bold;">{{ $pengembalians->count() }}</td>
        <td style="font-weight:bold;">Total Denda</td>
        <td style="font-weight:bold;">Rp {{ number_format($totalDenda, 0, ',', '.') }}</td>
        <td style="font-weight:bold;">Total Denda Kerusakan</td>
        <td colspan="4" style="font-weight:bold;">
            Rp {{ number_format($totalDendaKerusakan, 0, ',', '.') }}
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

    @forelse($pengembalians as $pengembalian)
        <tr>
            <td style="text-align:center;">{{ $loop->iteration }}</td>
            <td>{{ $pengembalian->peminjaman->user->name ?? 'User Dihapus' }}</td>
            <td>
                @foreach($pengembalian->peminjaman->detailPinjams as $detail)
                    {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }} ({{ $detail->jumlah }} pcs)@if(!$loop->last), @endif
                @endforeach
            </td>
            <td style="text-align:center;">
                {{ $pengembalian->peminjaman->tgl_pinjam->format('d-m-Y') }}
            </td>
            <td style="text-align:center;">
                {{ $pengembalian->tgl_kembali->format('d-m-Y') }}
            </td>
            <td>{{ $pengembalian->kondisi_kembali }}</td>
            <td style="text-align:right;">
                Rp {{ number_format($pengembalian->denda, 0, ',', '.') }}
            </td>
            <td style="text-align:right;">
                Rp {{ number_format($pengembalian->denda_kerusakan, 0, ',', '.') }}
            </td>
            <td>
                @if($pengembalian->petugas)
                    {{ $pengembalian->petugas->name }}
                @else
                    Admin
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="9" style="text-align:center;">Belum ada data pengembalian.</td>
        </tr>
    @endforelse
</table>
