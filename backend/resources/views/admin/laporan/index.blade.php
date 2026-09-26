@extends('layouts.app')

@section('title', 'Cetak Laporan - Admin')
@section('header-title', 'Cetak Laporan')

@section('content')

<div class="bg-white rounded-lg shadow-sm border border-gray-200">

    {{-- Header --}}
    <div class="p-5 border-b border-gray-200">

        <div class="flex flex-wrap items-center justify-between gap-3">

            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Laporan Pengembalian Alat
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Seluruh data pengembalian dari semua petugas.
                </p>
            </div>

            {{-- Tombol Cetak: dropdown pilih format --}}
            <div class="relative inline-block text-left" id="cetakDropdown">
                <button
                    type="button"
                    onclick="document.getElementById('cetakMenu').classList.toggle('hidden')"
                    class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2"
                >
                    Cetak Laporan
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div
                    id="cetakMenu"
                    class="hidden absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
                >
                    <a
                        href="{{ route('admin.laporan.excel', [
                            'tanggal_mulai' => $tanggalMulai,
                            'tanggal_selesai' => $tanggalSelesai,
                        ]) }}"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-100"
                    >
                        <span class="text-green-600">▦</span>
                        Excel (.xlsx)
                    </a>

                    <a
                        href="{{ route('admin.laporan.pdf', [
                            'tanggal_mulai' => $tanggalMulai,
                            'tanggal_selesai' => $tanggalSelesai,
                        ]) }}"
                        target="_blank"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <span class="text-red-600">⎙</span>
                        PDF (cetak)
                    </a>
                </div>
            </div>

        </div>

    </div>


    {{-- Filter --}}
    <div class="p-5 border-b border-gray-200 print:hidden">

        <form
            action="{{ route('admin.laporan.index') }}"
            method="GET"
            class="flex flex-wrap items-end gap-3"
        >

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


            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Filter
            </button>


            <a
                href="{{ route('admin.laporan.index') }}"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Reset
            </a>

        </form>

    </div>


    {{-- Area Laporan --}}
    <div id="area-laporan" class="p-5">

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

                            <td class="px-3 py-3 border text-center">
                                {{ $pengembalians->firstItem() + $loop->iteration - 1 }}
                            </td>

                            <td class="px-3 py-3 border">
                                {{ $pengembalian->peminjaman->user->name ?? 'User Dihapus' }}
                            </td>

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

                            <td class="px-3 py-3 border">
                                {{ $pengembalian->peminjaman->tgl_pinjam->format('d-m-Y') }}
                            </td>

                            <td class="px-3 py-3 border">
                                {{ $pengembalian->tgl_kembali->format('d-m-Y') }}
                            </td>

                            <td class="px-3 py-3 border">
                                {{ $pengembalian->kondisi_kembali }}
                            </td>

                            <td class="px-3 py-3 border text-right">
                                Rp {{ number_format($pengembalian->denda, 0, ',', '.') }}
                            </td>

                            <td class="px-3 py-3 border text-right">
                                Rp {{ number_format($pengembalian->denda_kerusakan, 0, ',', '.') }}
                            </td>

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
                            <td colspan="9" class="px-3 py-6 border text-center text-gray-500">
                                Belum ada data pengembalian.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        <div class="mt-5 print:hidden">
            {{ $pengembalians->links() }}
        </div>

    </div>

</div>

{{-- Tutup dropdown saat klik di luar --}}
<script>
    document.addEventListener('click', function (e) {
        var dropdown = document.getElementById('cetakDropdown');
        var menu = document.getElementById('cetakMenu');
        if (dropdown && menu && !dropdown.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>

@endsection
