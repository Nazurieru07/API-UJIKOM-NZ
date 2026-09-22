@extends('layouts.app')

@section('title', 'Cetak Laporan - Petugas')
@section('header-title', 'Cetak Laporan')

@section('content')

<div class="bg-white rounded-lg shadow-sm border border-gray-200">

    {{-- Header --}}
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

            {{-- Tombol Cetak --}}
            <a
    href="{{ route('petugas.laporan.pdf', [
        'tanggal_mulai' => $tanggalMulai,
        'tanggal_selesai' => $tanggalSelesai
    ]) }}"
    target="_blank"
    class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold transition"
>
    Cetak / Lihat PDF
</a>

        </div>

    </div>


    {{-- Filter --}}
    <div class="p-5 border-b border-gray-200 print:hidden">

        <form
            action="{{ route('petugas.laporan.index') }}"
            method="GET"
            class="flex flex-wrap items-end gap-3"
        >

            {{-- Tanggal Mulai --}}
            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Dari Tanggal
                </label>

                <input
                    type="date"
                    name="tanggal_mulai"
                    value="{{ $tanggalMulai }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

            </div>


            {{-- Tanggal Selesai --}}
            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Sampai Tanggal
                </label>

                <input
                    type="date"
                    name="tanggal_selesai"
                    value="{{ $tanggalSelesai }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

            </div>


            {{-- Tombol Filter --}}
            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Filter
            </button>


            {{-- Reset --}}
            <a
                href="{{ route('petugas.laporan.index') }}"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Reset
            </a>

        </form>

    </div>


    {{-- Area Laporan --}}
    <div id="area-laporan" class="p-5">

        {{-- Judul untuk hasil cetak --}}
        <div class="hidden print:block text-center mb-6">

            <h1 class="text-xl font-bold text-gray-800">
                LAPORAN PENGEMBALIAN ALAT
            </h1>

            <p class="text-sm text-gray-600 mt-1">
                Sistem Peminjaman Alat
            </p>

            @if($tanggalMulai || $tanggalSelesai)

                <p class="text-sm text-gray-600 mt-2">

                    Periode:
                    
                    {{ $tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('d-m-Y') : '...' }}

                    s/d

                    {{ $tanggalSelesai ? \Carbon\Carbon::parse($tanggalSelesai)->format('d-m-Y') : '...' }}

                </p>

            @endif

        </div>


        {{-- Ringkasan --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">

                <p class="text-sm text-gray-500">
                    Total Pengembalian
                </p>

                <p class="text-2xl font-bold text-gray-800 mt-1">
                    {{ $pengembalians->count() }}
                </p>

            </div>


            <div class="bg-red-50 border border-red-200 rounded-lg p-4">

                <p class="text-sm text-red-600">
                    Total Denda
                </p>

                <p class="text-2xl font-bold text-red-700 mt-1">
                    Rp {{ number_format($pengembalians->sum('denda'), 0, ',', '.') }}
                </p>

            </div>


            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">

                <p class="text-sm text-orange-600">
                    Total Denda Kerusakan
                </p>

                <p class="text-2xl font-bold text-orange-700 mt-1">
                    Rp {{ number_format($pengembalians->sum('denda_kerusakan'), 0, ',', '.') }}
                </p>

            </div>

        </div>


        {{-- Tabel --}}
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

                    @forelse($pengembalians as $pengembalian)

                        <tr>

                            {{-- Nomor --}}
                            <td class="px-3 py-3 border text-center">
                                {{ $loop->iteration }}
                            </td>


                            {{-- Peminjam --}}
                            <td class="px-3 py-3 border">

                                {{ $pengembalian->peminjaman->user->name ?? 'User Dihapus' }}

                            </td>


                            {{-- Alat --}}
                            <td class="px-3 py-3 border">

                                <ul class="list-disc list-inside">

                                    @foreach($pengembalian->peminjaman->detailPinjams as $detail)

                                        <li>
                                            {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}
                                            ({{ $detail->jumlah }} pcs)
                                        </li>

                                    @endforeach

                                </ul>

                            </td>


                            {{-- Tanggal Pinjam --}}
                            <td class="px-3 py-3 border">

                                {{ $pengembalian->peminjaman->tgl_pinjam->format('d-m-Y') }}

                            </td>


                            {{-- Tanggal Kembali --}}
                            <td class="px-3 py-3 border">

                                {{ $pengembalian->tgl_kembali->format('d-m-Y') }}

                            </td>


                            {{-- Kondisi --}}
                            <td class="px-3 py-3 border">

                                {{ $pengembalian->kondisi_kembali }}

                            </td>


                            {{-- Denda --}}
                            <td class="px-3 py-3 border text-right">

                                Rp {{ number_format($pengembalian->denda, 0, ',', '.') }}

                            </td>


                            {{-- Denda Kerusakan --}}
                            <td class="px-3 py-3 border text-right">

                                Rp {{ number_format($pengembalian->denda_kerusakan, 0, ',', '.') }}

                            </td>


                            {{-- Petugas --}}
                            <td class="px-3 py-3 border">

                                @if($pengembalian->petugas)
                                    {{ $pengembalian->petugas->name }}
                                @else
                                    Admin
                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="9"
                                class="px-4 py-8 border text-center text-gray-500"
                            >
                                Belum ada data pengembalian.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Tanda tangan --}}
        <div class="hidden print:flex justify-end mt-10">

            <div class="text-center w-56">

                <p class="mb-16">
                    Petugas,
                </p>

                <p class="font-semibold border-b border-gray-800 pb-1">
                    {{ auth()->user()->name }}
                </p>

            </div>

        </div>

    </div>

</div>


{{-- CSS khusus saat mencetak --}}
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

@endsection