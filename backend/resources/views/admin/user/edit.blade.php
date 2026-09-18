@extends('layouts.app')

@section('title', 'Edit User - Panel Admin')
@section('header-title', 'Edit Data Pengguna')

@section('content')

<div class="max-w-xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    <form action="{{ route('admin.user.update', $user->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Nama Lengkap
            </label>

            <input
                type="text"
                name="name"
                value="{{ old('name', $user->name) }}"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Email
            </label>

            <input
                type="email"
                name="email"
                value="{{ old('email', $user->email) }}"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Password Baru
                <span class="text-xs text-gray-400 font-normal">
                    (Kosongkan jika tidak ingin mengubah password)
                </span>
            </label>

            <input
                type="password"
                name="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Role / Hak Akses
            </label>

            <select
                name="role"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                <option value="peminjam" {{ $user->role == 'peminjam' ? 'selected' : '' }}>
                    Peminjam
                </option>

                <option value="petugas" {{ $user->role == 'petugas' ? 'selected' : '' }}>
                    Petugas
                </option>

                <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>
                    Admin
                </option>

            </select>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                No. HP
            </label>

            <input
                type="text"
                name="no_hp"
                value="{{ old('no_hp', $user->no_hp) }}"
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

        <option value="Laki-laki"
            {{ old('jenis_kelamin', $user->jenis_kelamin) == 'Laki-laki' ? 'selected' : '' }}>
            Laki-laki
        </option>

        <option value="Perempuan"
            {{ old('jenis_kelamin', $user->jenis_kelamin) == 'Perempuan' ? 'selected' : '' }}>
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
        Foto Profil
        <span class="text-xs text-gray-400 font-normal">
            (Biarkan kosong jika tidak ingin mengubah foto)
        </span>
    </label>

    @if($user->foto_profile)
        <div class="mb-3">
            <img
                src="{{ asset($user->foto_profile) }}"
                alt="{{ $user->name }}"
                class="w-20 h-20 object-cover rounded-full border"
            >
        </div>
    @endif

    <input
        type="file"
        name="foto_profile"
        accept="image/*"
        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
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
        Perbarui
    </button>

</div>

    </form>

</div>

@endsection