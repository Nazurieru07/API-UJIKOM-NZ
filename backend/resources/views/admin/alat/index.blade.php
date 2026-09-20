@extends('layouts.app')

@section('title', 'Kelola Alat - Panel Admin')
@section('header-title', 'Manajemen Data Alat')

@section('content')

    @if(session('success'))
    <div class="flash-success mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="flash-error mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
        {{ session('error') }}
    </div>
@endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-wrap justify-between items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Daftar Alat Laboratorium</h3>

            <div class="flex items-center gap-3 w-full md:w-auto">

    <form
    action="{{ route('admin.alat.index') }}"
    method="GET"
    class="flex flex-wrap items-center gap-2"
>

    {{-- Search --}}
    <div class="flex">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Cari nama alat, kategori..."
            class="w-56 px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >

        <button
            type="submit"
            class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition"
        >
            Cari
        </button>
    </div>


    {{-- Filter Kategori --}}
    <select
    name="kategori"
    onchange="this.form.submit()"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
>
    <option value="">Semua Kategori</option>

    @foreach($kategori as $item)
        <option
            value="{{ $item->id }}"
            {{ request('kategori') == $item->id ? 'selected' : '' }}
        >
            {{ $item->nama_kategori }}
        </option>
    @endforeach
</select>


    {{-- Filter Kondisi --}}
    <select
    name="kondisi"
    onchange="this.form.submit()"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
>
    <option value="">Semua Kondisi</option>

    <option
        value="Baik"
        {{ request('kondisi') == 'Baik' ? 'selected' : '' }}
    >
        Baik
    </option>

    <option
        value="Rusak"
        {{ request('kondisi') == 'Rusak' ? 'selected' : '' }}
    >
        Rusak Ringan
    </option>

    <option
        value="Rusak Parah"
        {{ request('kondisi') == 'Rusak Parah' ? 'selected' : '' }}
    >
        Rusak Parah
    </option>
</select>

    {{-- Reset --}}
    @if(request('search') || request('kategori') || request('kondisi'))
        <a
            href="{{ route('admin.alat.index') }}"
            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg transition"
        >
            Reset
        </a>
    @endif

</form>

                {{-- Tombol Tambah --}}
                <a
                    href="{{ route('admin.alat.create') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap"
                >
                    + Tambah Alat
                </a>

            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Gambar</th>
                        <th class="py-3 px-4 border-b">Nama Alat</th>
                        <th class="py-3 px-4 border-b">Kategori</th>
                        <th class="py-3 px-4 border-b">Stok</th>
                        <th class="py-3 px-4 border-b">Kondisi</th>
                        <th class="py-3 px-4 border-b">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-sm">
                    @forelse($alats as $alat)
                        <tr class="hover:bg-gray-50 transition">

                            <td class="py-3 px-4 border-b">
                                @if($alat->gambar)
                                    <img
                                        src="{{ asset($alat->gambar) }}"
                                        alt="{{ $alat->nama_alat }}"
                                        class="w-12 h-12 object-cover rounded-lg border"
                                    >
                                @else
                                    <span class="text-xs text-gray-400 italic">Tidak ada</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $alat->nama_alat }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $alat->kategori->nama_kategori ?? '-' }}
                            </td>

                            <td class="py-3 px-4 border-b font-semibold">
                                {{ $alat->stok }}
                            </td>

                            <td class="py-3 px-4 border-b">
    <div class="flex flex-wrap items-center gap-2">

        {{-- Kondisi Baik --}}
        <span class="inline-flex items-center px-2.5 py-1
                     text-xs font-semibold rounded-full
                     bg-emerald-50 text-emerald-700">
            Baik: {{ $alat->stok_baik }}
        </span>

        {{-- Kondisi Rusak --}}
        <span class="inline-flex items-center px-2.5 py-1
                     text-xs font-semibold rounded-full
                     bg-amber-50 text-amber-700">
            Rusak Ringan: {{ $alat->stok_rusak }}
        </span>

        {{-- Kondisi Rusak Parah --}}
        <span class="inline-flex items-center px-2.5 py-1
                     text-xs font-semibold rounded-full
                     bg-red-50 text-red-700">
            Rusak Parah: {{ $alat->stok_rusak_parah }}
        </span>

    </div>
</td>

                            <td class="py-3 px-4 border-b">
    <div class="flex flex-wrap items-center gap-2">

        {{-- Edit --}}
        <a
            href="{{ route('admin.alat.edit', $alat->id) }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition"
        >
            Edit
        </a>

        {{-- Perbaiki --}}
        @if($alat->stok_rusak > 0 || $alat->stok_rusak_parah > 0)
            <details class="relative">
                <summary
                    class="list-none cursor-pointer bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition"
                >
                    Perbaiki
                </summary>

                <div class="absolute right-0 z-10 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg p-4">

                    <p class="text-sm font-semibold text-gray-800 mb-3">
                        Perbaiki Alat
                    </p>

                    <form
                        action="{{ route('admin.alat.perbaiki', $alat->id) }}"
                        method="POST"
                    >
                        @csrf

                        {{-- Kondisi --}}
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Kondisi yang diperbaiki
                            </label>

                            <select
                                name="kondisi"
                                required
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            >
                                @if($alat->stok_rusak > 0)
                                    <option value="Rusak">
                                        Rusak Ringan ({{ $alat->stok_rusak }} pcs)
                                    </option>
                                @endif

                                @if($alat->stok_rusak_parah > 0)
                                    <option value="Rusak Parah">
                                        Rusak Parah ({{ $alat->stok_rusak_parah }} pcs)
                                    </option>
                                @endif
                            </select>
                        </div>

                        {{-- Jumlah --}}
                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Jumlah yang diperbaiki
                            </label>

                            <input
                                type="number"
                                name="jumlah"
                                min="1"
                                value="1"
                                required
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            >
                        </div>

                        <button
                            type="submit"
                            onclick="return confirm('Yakin alat ini sudah diperbaiki dan akan dikembalikan ke kondisi Baik?')"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-xs font-semibold transition"
                        >
                            Konfirmasi Perbaikan
                        </button>
                    </form>
                </div>
            </details>
        @endif

        {{-- Hapus --}}
        <form
            action="{{ route('admin.alat.destroy', $alat->id) }}"
            method="POST"
            onsubmit="return confirm('Yakin ingin menghapus alat ini?')"
        >
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition"
            >
                Hapus
            </button>
        </form>

    </div>
</td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center text-gray-500">
                                Belum ada data alat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $alats->links() }}
        </div>

    </div>

@endsection

