@extends('layouts.app')

@section('title', 'Tambah Alat - Panel Admin')
@section('header-title', 'Tambah Alat Baru')

@section('content')

<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    <form action="{{ route('admin.alat.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Nama Alat
            </label>

            <input
                type="text"
                name="nama_alat"
                value="{{ old('nama_alat') }}"
                required
                placeholder="Contoh: Multimeter Digital"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            @error('nama_alat')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
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
                <option value="">-- Pilih Kategori --</option>

                @foreach($kategoris as $kategori)
                    <option
                        value="{{ $kategori->id }}"
                        {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}
                    >
                        {{ $kategori->nama_kategori }}
                    </option>
                @endforeach
            </select>

            @error('kategori_id')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">
                    Stok
                </label>

                <input
                    type="number"
                    name="stok"
                    value="{{ old('stok') }}"
                    min="0"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                @error('stok')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div>
    <label class="block text-gray-700 text-sm font-semibold mb-2">
        Status Kondisi
    </label>

    <select
        name="status_kondisi"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-lg
               focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
        <option value="Baik"
            {{ old('status_kondisi', 'Baik') === 'Baik' ? 'selected' : '' }}>
            Baik
        </option>

        <option value="Rusak"
            {{ old('status_kondisi') === 'Rusak' ? 'selected' : '' }}>
            Rusak
        </option>

        <option value="Rusak Parah"
            {{ old('status_kondisi') === 'Rusak Parah' ? 'selected' : '' }}>
            Rusak Parah
        </option>

        <option value="Kosong"
            {{ old('status_kondisi') === 'Kosong' ? 'selected' : '' }}>
            Kosong
        </option>
    </select>

    @error('status_kondisi')
        <span class="text-red-500 text-xs">{{ $message }}</span>
    @enderror
</div>

        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Deskripsi (Opsional)
            </label>

            <textarea
                name="deskripsi"
                rows="3"
                placeholder="Keterangan tambahan tentang alat..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >{{ old('deskripsi') }}</textarea>

            @error('deskripsi')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Gambar Alat (Opsional)
            </label>

            <input
                type="file"
                name="gambar"
                accept="image/*"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700"
            >

            @error('gambar')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
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
                Simpan
            </button>

        </div>

    </form>

</div>

@endsection