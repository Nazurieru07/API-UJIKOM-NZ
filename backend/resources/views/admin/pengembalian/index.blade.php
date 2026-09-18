@extends('layouts.app')

@section('title', 'Pengembalian - Panel Admin')
@section('header-title', 'Kelola Pengembalian')

@section('content')

<div class="bg-white rounded-lg shadow-sm border border-gray-200">

    {{-- Alert Success --}}
    @if(session('success'))
        <div class="m-4 bg-green-50 border border-green-200 text-green-800 p-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Alert Error --}}
    @if(session('error'))
        <div class="m-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif


    {{-- Header --}}
    <div class="p-4 border-b border-gray-200 flex items-center justify-between">

        <h2 class="text-lg font-semibold text-gray-800">
            Data Pengembalian
        </h2>

        <div class="flex items-center gap-2">

            {{-- Search --}}
            <form
                action="{{ route('admin.pengembalian.index') }}"
                method="GET"
                class="flex"
            >

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari nama peminjam"
                    class="border border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                {{-- Filter Kondisi --}}
                <select
                    name="kondisi"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white text-gray-700
                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                        cursor-pointer"
                >
                    <option value="">Semua Kondisi</option>
                    <option value="Baik" {{ request('kondisi') == 'Baik' ? 'selected' : '' }}>
                        Baik
                    </option>
                    <option value="Rusak Ringan" {{ request('kondisi') == 'Rusak Ringan' ? 'selected' : '' }}>
                        Rusak Ringan
                    </option>
                    <option value="Rusak Berat" {{ request('kondisi') == 'Rusak Berat' ? 'selected' : '' }}>
                        Rusak Berat
                    </option>
                </select>

                {{-- Filter Status Pengajuan --}}
<select
    name="status_request"
    class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white text-gray-700
           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
           cursor-pointer"
>
    <option value="">Semua Status</option>

    <option value="menunggu" {{ request('status_request') == 'menunggu' ? 'selected' : '' }}>
        Menunggu Persetujuan
    </option>

    <option value="disetujui" {{ request('status_request') == 'disetujui' ? 'selected' : '' }}>
        Disetujui
    </option>

    <option value="ditolak" {{ request('status_request') == 'ditolak' ? 'selected' : '' }}>
        Ditolak
    </option>
</select>

{{-- Filter Jenis Kelamin --}}
<select
    name="jenis_kelamin"
    class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white text-gray-700
           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
           cursor-pointer"
>
    <option value="">Semua Jenis Kelamin</option>

    <option value="Laki-laki" {{ request('jenis_kelamin') == 'Laki-laki' ? 'selected' : '' }}>
        Laki-laki
    </option>

    <option value="Perempuan" {{ request('jenis_kelamin') == 'Perempuan' ? 'selected' : '' }}>
        Perempuan
    </option>
</select>

{{-- Filter Tanggal Dari --}}
<input
    type="date"
    name="tanggal_dari"
    value="{{ request('tanggal_dari') }}"
    class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white text-gray-700
           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
    title="Tanggal Dari"
>

{{-- Filter Tanggal Sampai --}}
<input
    type="date"
    name="tanggal_sampai"
    value="{{ request('tanggal_sampai') }}"
    class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white text-gray-700
           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
    title="Tanggal Sampai"
>

                <button
                    type="submit"
                    class="bg-gray-800 text-white px-4 py-2 rounded-r-lg text-sm font-semibold hover:bg-gray-900 transition"
                >
                    Cari
                </button>

                @if(
    request('search') ||
    request('kondisi') ||
    request('status_request') ||
    request('jenis_kelamin') ||
    request('tanggal_dari') ||
    request('tanggal_sampai')
)
    <a
        href="{{ route('admin.pengembalian.index') }}"
        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition"
    >
        Reset
    </a>
@endif

            </form>

        </div>

    </div>


    {{-- Table --}}
    <div class="overflow-x-auto">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">

                <tr>

                    <th class="px-4 py-3">
                        Peminjam
                    </th>

                    <th class="px-4 py-3">
                        Jenis Kelamin
                    </th>

                    <th class="px-4 py-3">
                        Alat
                    </th>

                    <th class="px-4 py-3">
                        Tanggal Kembali
                    </th>

                    <th class="px-4 py-3">
                        Kondisi
                    </th>

                    <th class="px-4 py-3">
                        Denda Keterlambatan
                    </th>

                    <th class="px-4 py-3">
                        Denda Kerusakan
                    </th>

                    <th class="px-4 py-3">
                        Status Pengajuan
                    </th>

                    <th class="px-4 py-3">
                        Petugas
                    </th>

                    <th class="px-4 py-3 text-center">
                        Aksi
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-200">

                @forelse($pengembalians as $pengembalian)

                    <tr class="hover:bg-gray-50">

                        {{-- Peminjam --}}
                        <td class="px-4 py-4 font-medium text-gray-800">

                            {{ $pengembalian->peminjaman->user->name }}

                        </td>

                        <td class="px-4 py-4">
    @if($pengembalian->peminjaman->user->jenis_kelamin == 'Laki-laki')
        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">
            Laki-laki
        </span>
    @elseif($pengembalian->peminjaman->user->jenis_kelamin == 'Perempuan')
        <span class="bg-pink-100 text-pink-800 px-3 py-1 rounded-full text-xs font-medium">
            Perempuan
        </span>
    @else
        <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium">
            -
        </span>
    @endif
</td>


                        {{-- Alat --}}
                        <td class="px-4 py-4">

                            <ul class="list-disc list-inside space-y-1 whitespace-nowrap">

                                @foreach($pengembalian->peminjaman->detailPinjams as $detail)

                                    <li>

                                        {{ $detail->alat->nama_alat }}

                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                            {{ $detail->jumlah }} pcs
                                        </span>

                                    </li>

                                @endforeach

                            </ul>

                        </td>


                        {{-- Tanggal Kembali --}}
                        <td class="px-4 py-4 text-gray-600">

                            {{ $pengembalian->tgl_kembali->format('d-m-Y') }}

                        </td>


                        {{-- Kondisi --}}
                        <td class="px-4 py-4">

                            @if($pengembalian->kondisi_kembali == 'Baik')

                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">
                                    Baik
                                </span>

                            @elseif($pengembalian->kondisi_kembali == 'Rusak Ringan')

                                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                                Rusak Ringan
                            </span>

                            @elseif($pengembalian->kondisi_kembali == 'Rusak Berat')

                                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-medium">
                                    Rusak Berat
                                </span>

                            @else

                                <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium">
                                    {{ $pengembalian->kondisi_kembali }}
                                </span>

                            @endif

                        </td>


                        {{-- Denda Keterlambatan --}}
                        <td class="px-4 py-4 font-semibold text-red-600">

                            Rp {{ number_format($pengembalian->denda, 0, ',', '.') }}

                        </td>

                        {{-- Denda Kerusakan --}}
                        <td class="px-4 py-4 font-semibold text-red-600">

                            Rp {{ number_format($pengembalian->denda_kerusakan ?? 0, 0, ',', '.') }}

                        </td>

                        {{-- Status Pengajuan --}}
                    <td class="px-4 py-4">

                        @if($pengembalian->status_request == 'menunggu')

                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                                Menunggu Persetujuan
                            </span>

                        @elseif($pengembalian->status_request == 'disetujui')

                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">
                                Disetujui
                            </span>

                        @elseif($pengembalian->status_request == 'ditolak')

                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-medium">
                                Ditolak
                            </span>

                        @endif

                    </td>


                        {{-- Petugas --}}
                        <td class="px-4 py-4 text-gray-600">

                            {{ $pengembalian->petugas->name }}

                        </td>


                        {{-- Aksi --}}
                        <td class="px-4 py-4 text-center">

                            <div class="flex flex-col gap-2">

                                @if($pengembalian->status_request == 'menunggu')

                                    {{-- Periksa Pengajuan --}}
                                    <a
                                        href="{{ route('admin.pengembalian.edit', $pengembalian->id) }}"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
                                    >
                                        Periksa
                                    </a>

                                @endif


                                {{-- Hapus --}}
                                <form
                                    action="{{ route('admin.pengembalian.destroy', $pengembalian->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Apakah kamu yakin ingin menghapus catatan pengembalian ini?')"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
                                    >
                                        Hapus
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="10"
                            class="px-4 py-8 text-center text-gray-500"
                        >

                            Belum ada data pengembalian.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- Pagination --}}
    @if($pengembalians->hasPages())

        <div class="p-4 border-t border-gray-200">

            {{ $pengembalians->links() }}

        </div>

    @endif

</div>

@endsection