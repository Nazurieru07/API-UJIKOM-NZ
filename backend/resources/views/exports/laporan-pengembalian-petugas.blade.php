
{{-- Template Excel khusus laporan pengembalian Petugas. --}}

<table>
    <tr>
        <td colspan="9" style="font-size:14px;font-weight:bold;text-align:center;">
            LAPORAN PENGEMBALIAN ALAT
        </td>
    </tr>

    <tr>
        <td colspan="9" style="text-align:center;">
            Sistem Peminjaman Alat - Laporan Petugas
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
        <td style="font-weight:bold;">
            Rp {{ number_format($totalDenda, 0, ',', '.') }}
        </td>
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
            <td style="text-align:center;">
                {{ $loop->iteration }}
            </td>

            <td>
                {{ $pengembalian->peminjaman->user->name ?? 'User Dihapus' }}
            </td>

            {{-- Hanya unit yang diproses Petugas ini --}}
            <td>
                @foreach($pengembalian->detailPengembalians as $detailPengembalian)
                    {{ $detailPengembalian->detailPinjam?->alatUnit?->alat?->nama_alat ?? 'Alat Dihapus' }}
                    ({{ $detailPengembalian->detailPinjam?->alatUnit?->serial_number ?? '-' }})@if(!$loop->last), @endif
                @endforeach
            </td>

            <td style="text-align:center;">
                {{ $pengembalian->peminjaman->tgl_pinjam->format('d-m-Y') }}
            </td>

            <td style="text-align:center;">
                {{ $pengembalian->tgl_kembali->format('d-m-Y') }}
            </td>

            {{-- Kondisi per unit --}}
            <td>
                @foreach($pengembalian->detailPengembalians as $detailPengembalian)
                    {{ $detailPengembalian->kondisi_kembali }}@if(!$loop->last), @endif
                @endforeach
            </td>

            {{-- Denda keterlambatan milik Petugas finalisasi --}}
            <td style="text-align:right;">
                @php
                    $dendaPetugas = (int) $pengembalian->petugas_id === (int) auth()->id()
                        ? (int) $pengembalian->denda
                        : 0;
                @endphp

                Rp {{ number_format($dendaPetugas, 0, ',', '.') }}
            </td>

            {{-- Denda kerusakan milik Petugas --}}
            <td style="text-align:right;">

            @php
                // Denda hanya berasal dari unit yang diproses Petugas ini.
                $dendaKerusakanPetugas = $pengembalian->detailPengembalians
                    ->sum('denda_kerusakan');
            @endphp

                Rp {{ number_format($dendaKerusakanPetugas, 0, ',', '.') }}
            </td>

            <td>
                {{ auth()->user()->name ?? 'Petugas' }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="9" style="text-align:center;">
                Belum ada data pengembalian.
            </td>
        </tr>
    @endforelse
</table>
