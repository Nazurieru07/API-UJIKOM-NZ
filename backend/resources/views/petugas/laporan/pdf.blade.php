<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <title>Laporan Pengembalian Alat</title>

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
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <h1>SISTEM PEMINJAMAN ALAT</h1>
        <h2>LAPORAN PENGEMBALIAN ALAT</h2>
    </div>

    {{-- JUDUL --}}
    <div class="judul">
        <h3>Laporan Pengembalian Alat</h3>

        @if($tanggalMulai || $tanggalSelesai)
            <div class="periode">
                Periode:
                {{ $tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('d-m-Y') : '...' }}
                s/d
                {{ $tanggalSelesai ? \Carbon\Carbon::parse($tanggalSelesai)->format('d-m-Y') : '...' }}
            </div>
        @else
            <div class="periode">
                Semua Data Pengembalian
            </div>
        @endif
    </div>

    {{-- RINGKASAN --}}
    <table class="ringkasan">
        <tr>
            <td>
                <div class="label">Total Pengembalian</div>
                <div class="nilai">
                    {{ $pengembalians->count() }}
                </div>
            </td>

            <td>
                <div class="label">Total Denda</div>
                <div class="nilai">
                    Rp {{ number_format($pengembalians->sum('denda'), 0, ',', '.') }}
                </div>
            </td>

            <td>
                <div class="label">Total Denda Kerusakan</div>
                <div class="nilai">
                    Rp {{ number_format($pengembalians->sum('denda_kerusakan'), 0, ',', '.') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- TABEL LAPORAN --}}
    <table class="laporan">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="12%">Peminjam</th>
                <th width="22%">Alat</th>
                <th width="10%">Tgl Pinjam</th>
                <th width="10%">Tgl Kembali</th>
                <th width="10%">Kondisi</th>
                <th width="10%">Denda</th>
                <th width="12%">Denda Kerusakan</th>
                <th width="10%">Petugas</th>
            </tr>
        </thead>

        <tbody>

            @forelse($pengembalians as $pengembalian)

                <tr>
                    <td class="center">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $pengembalian->peminjaman->user->name ?? 'User Dihapus' }}
                    </td>

                    <td>
                        <ul>
                            @foreach($pengembalian->peminjaman->detailPinjams as $detail)
                                <li>
                                    {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}
                                    ({{ $detail->jumlah }} pcs)
                                </li>
                            @endforeach
                        </ul>
                    </td>

                    <td class="center">
                        {{ $pengembalian->peminjaman->tgl_pinjam->format('d-m-Y') }}
                    </td>

                    <td class="center">
                        {{ $pengembalian->tgl_kembali->format('d-m-Y') }}
                    </td>

                    <td>
                        {{ $pengembalian->kondisi_kembali }}
                    </td>

                    <td class="right">
                        Rp {{ number_format($pengembalian->denda, 0, ',', '.') }}
                    </td>

                    <td class="right">
                        Rp {{ number_format($pengembalian->denda_kerusakan, 0, ',', '.') }}
                    </td>

                    <td>
                        {{ $pengembalian->petugas->name ?? 'Petugas Dihapus' }}
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="9" class="center">
                        Belum ada data pengembalian.
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