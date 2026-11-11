<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <title>Laporan Barang Sedang Dipinjam</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 25px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
        }

        .header h2 {
            margin: 4px 0 0;
            font-size: 14px;
            font-weight: normal;
        }

        .judul {
            text-align: center;
            margin-bottom: 15px;
        }

        .judul h3 {
            margin: 0;
            font-size: 16px;
        }

        .periode {
            margin-top: 5px;
            font-size: 11px;
        }

        .ringkasan {
            width: 100%;
            margin-bottom: 15px;
        }

        .ringkasan td {
            width: 33.33%;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        .ringkasan .label {
            font-size: 10px;
            color: #555;
        }

        .ringkasan .nilai {
            font-size: 14px;
            font-weight: bold;
            margin-top: 3px;
        }

        table.laporan {
            width: 100%;
            border-collapse: collapse;
        }

        table.laporan th,
        table.laporan td {
            border: 1px solid #999;
            padding: 6px;
            vertical-align: top;
        }

        table.laporan th {
            background-color: #eeeeee;
            text-align: center;
            font-weight: bold;
        }

        table.laporan td {
            text-align: left;
        }

        .center {
            text-align: center !important;
        }

        .right {
            text-align: right !important;
        }

        ul {
            margin: 0;
            padding-left: 15px;
        }

        .ttd {
            width: 220px;
            margin-left: auto;
            margin-top: 40px;
            text-align: center;
        }

        .ttd .jarak {
            height: 60px;
        }

        .ttd .nama {
            font-weight: bold;
            border-bottom: 1px solid #222;
            padding-bottom: 3px;
        }

        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #666;
        }
        
        .status-dipinjam {
            color: #1d4ed8;
            font-weight: bold;
        }
        
        .status-telat {
            color: #dc2626;
            font-weight: bold;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <h1>SISTEM PEMINJAMAN ALAT</h1>
        <h2>LAPORAN BARANG SEDANG DIPINJAM</h2>
    </div>

    {{-- JUDUL --}}
    <div class="judul">
        <h3>Laporan Barang Sedang Dipinjam</h3>

        @if($tanggalMulai || $tanggalSelesai)
            <div class="periode">
                Periode:
                {{ $tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('d-m-Y') : '...' }}
                s/d
                {{ $tanggalSelesai ? \Carbon\Carbon::parse($tanggalSelesai)->format('d-m-Y') : '...' }}
            </div>
        @else
            <div class="periode">
                Semua Data Peminjaman Aktif
            </div>
        @endif
    </div>

    {{-- RINGKASAN --}}
    <table class="ringkasan">
        <tr>
            <td>
                <div class="label">Total Peminjaman Aktif</div>
                <div class="nilai">
                    {{ $peminjamans->count() }}
                </div>
            </td>

            <td>
                <div class="label">Total Unit Dipinjam</div>
                <div class="nilai">
                    {{ $peminjamans->sum(function($peminjaman) {
                        return $peminjaman->detailPinjams->count();
                    }) }}
                </div>
            </td>

            <td>
                <div class="label">Terlambat</div>
                <div class="nilai">
                    {{ $peminjamans->where('status', 'telat')->count() }}
                </div>
            </td>
        </tr>
    </table>

    {{-- TABEL LAPORAN --}}
    <table class="laporan">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="15%">Peminjam</th>
                <th width="25%">Alat</th>
                <th width="10%">Tgl Pinjam</th>
                <th width="12%">Rencana Kembali</th>
                <th width="12%">Status</th>
                <th width="12%">Petugas Penyetuju</th>
                <th width="10%">Unit Dikembalikan</th>
            </tr>
        </thead>

        <tbody>

            @forelse($peminjamans as $peminjaman)

                <tr>
                    <td class="center">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $peminjaman->user->name ?? 'User Dihapus' }}
                    </td>

                    <td>
                        <ul>
                            @foreach($peminjaman->detailPinjams as $detail)
                                <li>
                                    {{ $detail->alatUnit?->alat->nama_alat ?? 'Alat Dihapus' }}
                                    ({{ $detail->alatUnit?->serial_number ?? '-' }})
                                </li>
                            @endforeach
                        </ul>
                    </td>

                    <td class="center">
                        {{ $peminjaman->tgl_pinjam->format('d-m-Y') }}
                    </td>

                    <td class="center">
                        {{ $peminjaman->tgl_kembali_plan->format('d-m-Y') }}
                    </td>

                    <td class="center">
                        @if($peminjaman->status == 'dipinjam')
                            <span class="status-dipinjam">Sedang Dipinjam</span>
                        @elseif($peminjaman->status == 'telat')
                            <span class="status-telat">Terlambat</span>
                        @endif
                    </td>

                    <td>
                        @if($peminjaman->pengembalian && $peminjaman->pengembalian->petugas)
                            {{ $peminjaman->pengembalian->petugas->name }}
                        @else
                            Belum ada pengembalian
                        @endif
                    </td>
                    
                    <td class="center">
                        @if($peminjaman->pengembalian && $peminjaman->pengembalian->is_parsial)
                            {{ $peminjaman->pengembalian->jumlah_unit_dikembalikan }} / {{ $peminjaman->detailPinjams->count() }}
                        @else
                            -
                        @endif
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="8" class="center">
                        Tidak ada data peminjaman aktif.
                    </td>
                </tr>

            @endforelse

        </tbody>
    </table>

    {{-- TANDA TANGAN --}}
    <div class="ttd">

        <div>
            {{ now()->format('d-m-Y') }}
        </div>

        <div>Petugas,</div>

        <div class="jarak"></div>

        <div class="nama">
            {{ auth()->user()->name }}
        </div>

    </div>

    <div class="footer">
        Dokumen ini dibuat oleh Sistem Peminjaman Alat.
    </div>

</body>
</html>