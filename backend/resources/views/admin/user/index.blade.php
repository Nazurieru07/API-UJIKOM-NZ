@extends('layouts.app')

@section('title', 'Kelola User - Panel Admin')
@section('header-title', 'Manajemen Pengguna Sistem')

@section('content')

    <!-- Notifikasi -->
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

        <div class="p-5 border-b border-gray-200 bg-gray-50">
    <h3 class="text-lg font-bold text-gray-800">
        Daftar Pengguna Sistem
    </h3>

    <div class="flex items-center gap-3 w-full md:w-auto mt-4">

    <!-- Form Search dan Filter -->
    <form action="{{ route('admin.user.index') }}" method="GET"
        class="flex flex-wrap items-center gap-2 w-full">

        <!-- Search -->
<input
    type="text"
    name="search"
    value="{{ request('search') }}"
    placeholder="Cari nama, email, role..."
    class="w-full md:w-64 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

<!-- Filter Role -->
<select
    name="role"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

    <option value="">Semua Role</option>

    <option value="admin"
        {{ request('role') == 'admin' ? 'selected' : '' }}>
        Admin
    </option>

    <option value="petugas"
        {{ request('role') == 'petugas' ? 'selected' : '' }}>
        Petugas
    </option>

    <option value="peminjam"
        {{ request('role') == 'peminjam' ? 'selected' : '' }}>
        Peminjam
    </option>

</select>

<!-- Filter Jenis Kelamin -->
<select
    name="jenis_kelamin"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

    <option value="">Semua Jenis Kelamin</option>

    <option value="Laki-laki"
        {{ request('jenis_kelamin') == 'Laki-laki' ? 'selected' : '' }}>
        Laki-laki
    </option>

    <option value="Perempuan"
        {{ request('jenis_kelamin') == 'Perempuan' ? 'selected' : '' }}>
        Perempuan
    </option>

</select>

<button
    type="submit"
    class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-lg transition">
    Cari
</button>

    </form>

    <!-- Tombol Reset -->
    @if(request('search') || request('role') || request('jenis_kelamin'))
        <a href="{{ route('admin.user.index') }}"
            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition whitespace-nowrap"
            title="Reset Pencarian dan Filter">
            Reset
        </a>
    @endif

    <!-- Tombol Tambah User -->
    <a href="{{ route('admin.user.create') }}"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
        + Tambah User
    </a>

</div>
</div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Foto</th>
                        <th class="py-3 px-4 border-b">Nama</th>
                        <th class="py-3 px-4 border-b">Email</th>
                        <th class="py-3 px-4 border-b">Role / Hak Akses</th>
                        <th class="py-3 px-4 border-b">No. HP</th>
                        <th class="py-3 px-4 border-b">Jenis Kelamin</th>
                        <th class="py-3 px-4 border-b">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition">
                             <td class="py-3 px-4 border-b">
                            @if($user->foto_profile)
                                <img
                                    src="{{ asset($user->foto_profile) }}"
                                    alt="{{ $user->name }}"
                                    class="w-12 h-12 object-cover rounded-full border"
                                >
                            @else
                                <span class="text-xs text-gray-400 italic">
                                    Tidak ada
                                </span>
                            @endif
                        </td>
                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $user->name }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $user->email }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                <span
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full
                                    @if($user->role == 'admin')
                                        bg-purple-100 text-purple-800
                                    @elseif($user->role == 'petugas')
                                        bg-blue-100 text-blue-800
                                    @else
                                        bg-green-100 text-green-800
                                    @endif">

                                    {{ ucfirst($user->role) }}

                                </span>
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $user->no_hp ?? '-' }}
                            </td>

                            <td class="py-3 px-4 border-b">
                            @if($user->jenis_kelamin == 'Laki-laki')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                    Laki-laki
                                </span>
                            @elseif($user->jenis_kelamin == 'Perempuan')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-pink-100 text-pink-700">
                                    Perempuan
                                </span>
                            @else
                                <span class="text-gray-400">
                                    -
                                </span>
                            @endif
                        </td>

                            <td class="py-3 px-4 border-b">

                                <div class="flex items-center space-x-2">

                                    <!-- Tombol Edit -->
                                    <a href="{{ route('admin.user.edit', $user->id) }}"
                                        class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                                        Edit
                                    </a>

                                    @if($user->id !== auth()->id())
                    <!-- Tombol Hapus -->
                    <form action="{{ route('admin.user.destroy', $user->id) }}"
                        method="POST"
                        onsubmit="return confirm('Yakin ingin menghapus user ini?')">

                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                            Hapus
                        </button>
                    </form>
                @endif

                                </div>

                            </td>

                        </tr>
                    @empty

                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-500">
                            Belum ada data pengguna.
                        </td>
                        </tr>

                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="p-4 border-t border-gray-200 bg-gray-50">
    {{ $users->links() }}
</div>

    </div>

@endsection