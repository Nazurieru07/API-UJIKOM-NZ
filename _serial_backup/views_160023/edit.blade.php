@extends('layouts.app')

@section('title', 'Edit Alat - Panel Admin')
@section('header-title', 'Edit Data Alat')

@section('content')

<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    <form action="{{ route('admin.alat.update', $alat->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Nama Alat
            </label>

            <input
                type="text"
                name="nama_alat"
                value="{{ old('nama_alat', $alat->nama_alat) }}"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Kategori
            </label>

            <select
                name="kategori_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                @foreach($kategoris as $kategori)
                    <option
                        value="{{ $kategori->id }}"
                        {{ old('kategori_id', $alat->kategori_id) == $kategori->id ? 'selected' : '' }}
                    >
                        {{ $kategori->nama_kategori }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Kode Alat
                <span class="text-xs text-gray-400 font-normal">
                    (Prefix serial number, 2 huruf)
                </span>
            </label>

            <input
                type="text"
                name="kode_alat"
                value="{{ old('kode_alat', $alat->kode_alat) }}"
                required
                maxlength="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg uppercase font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            <p class="text-xs text-amber-600 mt-1">
                Changing kode alat tidak mengubah serial number unit yang sudah
                dibuat. Serial lama tetap memakai prefix sebelumnya.
            </p>

            @error('kode_alat')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Deskripsi
            </label>

            <textarea
                name="deskripsi"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >{{ old('deskripsi', $alat->deskripsi) }}</textarea>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Gambar Alat
                <span class="text-xs text-gray-400 font-normal">
                    (Biarkan kosong jika tidak ingin mengubah gambar)
                </span>
            </label>

            @if($alat->gambar)
                <div class="mb-2">
                    <img
                        src="{{ asset($alat->gambar) }}"
                        alt="Preview"
                        class="w-16 h-16 object-cover rounded-lg border"
                    >
                </div>
            @endif

            <input
                type="file"
                name="gambar"
                accept="image/*"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            >
        </div>

        <div class="flex justify-end space-x-2">

            <a
                href="{{ route('admin.alat.index') }}"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Batal
            </a>

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition"
            >
                Perbarui
            </button>

        </div>

    </form>

    {{-- Daftar Unit: tampilan saja. Aksi per unit ada di index (Kelola Unit)
         supaya form edit alat tidak jadi submit kedua. --}}
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-base font-bold text-gray-800 mb-1">Daftar Unit</h3>

        <p class="text-xs text-gray-400 mb-4">
            {{ $alat->alatUnit->count() }} unit terdaftar. Ubah kondisi unit
            (tandai rusak / perbaiki) dari halaman utama lewat tombol
            "Kelola Unit".
        </p>

        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                        <th class="py-2 px-4 border-b">Serial Number</th>
                        <th class="py-2 px-4 border-b">Kondisi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($alat->alatUnit as $unit)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b font-mono text-xs">
                                {{ $unit->serial_number }}
                            </td>

                            <td class="py-2 px-4 border-b">
                                @if($unit->isTersedia())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                        Tersedia
                                    </span>
                                @elseif($unit->isDipinjam())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                                        Dipinjam
                                    </span>
                                @elseif($unit->isRusak())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">
                                        Rusak
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-4 px-4 text-center text-gray-400 text-xs">
                                Belum ada unit terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
