@extends('layouts.app')

@section('title', 'Tambah User - Panel Admin')
@section('header-title', 'Tambah Pengguna Baru')

@section('content')

<div class="max-w-xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    <form action="{{ route('admin.user.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Nama Lengkap
            </label>

            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

            @error('name')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Email
            </label>

            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

            @error('email')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Password
            </label>

            <input
                type="password"
                name="password"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

            @error('password')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Role / Hak Akses
            </label>

            <select
                name="role"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                <option value="peminjam">Peminjam</option>
                <option value="petugas">Petugas</option>
                <option value="admin">Admin</option>

            </select>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                No. HP (Opsional)
            </label>

            <input
                type="text"
                name="no_hp"
                value="{{ old('no_hp') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-6">
    <label class="block text-gray-700 text-sm font-semibold mb-2">
        Jenis Kelamin
    </label>

    <select
        name="jenis_kelamin"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

        <option value="">-- Pilih Jenis Kelamin --</option>
        <option value="Laki-laki" {{ old('jenis_kelamin') == 'Laki-laki' ? 'selected' : '' }}>
            Laki-laki
        </option>
        <option value="Perempuan" {{ old('jenis_kelamin') == 'Perempuan' ? 'selected' : '' }}>
            Perempuan
        </option>

    </select>

    @error('jenis_kelamin')
        <span class="text-red-500 text-xs">{{ $message }}</span>
    @enderror
</div>

        <!-- Foto Profil -->
<div class="mb-6">
    <label class="block text-gray-700 text-sm font-semibold mb-2">
        Foto Profil (Opsional)
    </label>

    <input
        type="file"
        name="foto_profile"
        accept="image/*"
        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700"
    >

    @error('foto_profile')
        <span class="text-red-500 text-xs">{{ $message }}</span>
    @enderror
</div>

<!-- Tombol -->
<div class="flex justify-end space-x-2">

    <a href="{{ route('admin.user.index') }}"
        class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
        Batal
    </a>

    <button
        type="submit"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
        Simpan
    </button>

</div>

        

    </form>

</div>

@endsection