@extends('layouts.app')

@section('title', 'Dashboard Admin - Sistem Peminjaman')

@section('header-title', 'Ringkasan Aktivitas Sistem')

@section('content')

    {{-- Alert Selamat Datang --}}
    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm">
        Selamat datang, <strong class="font-semibold">{{ auth()->user()->name }}</strong>!
        Anda login sebagai hak akses
        <span class="uppercase font-bold text-emerald-900">
            {{ auth()->user()->role }}
        </span>.
    </div>


    {{-- Kartu Statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

        {{-- Total Alat --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-700">
                Total Alat
            </p>

            <p class="text-3xl font-bold text-blue-900 mt-2">
                {{ $jumlahAlat }}
            </p>

            <p class="text-xs text-blue-600 mt-1">
                Data alat
            </p>
        </div>


        {{-- Total User --}}
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-purple-700">
                Total User
            </p>

            <p class="text-3xl font-bold text-purple-900 mt-2">
                {{ $jumlahUser }}
            </p>

            <p class="text-xs text-purple-600 mt-1">
                Pengguna sistem
            </p>
        </div>


        {{-- Total Kategori --}}
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-700">
                Total Kategori
            </p>

            <p class="text-3xl font-bold text-amber-900 mt-2">
                {{ $jumlahKategori }}
            </p>

            <p class="text-xs text-amber-600 mt-1">
                Kategori alat
            </p>
        </div>


        {{-- Total Peminjaman --}}
        <div class="bg-green-50 border border-green-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-green-700">
                Total Peminjaman
            </p>

            <p class="text-3xl font-bold text-green-900 mt-2">
                {{ $jumlahPeminjaman }}
            </p>

            <p class="text-xs text-green-600 mt-1">
                Data peminjaman
            </p>
        </div>


        {{-- Total Pengembalian --}}
        <div class="bg-red-50 border border-red-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-red-700">
                Total Pengembalian
            </p>

            <p class="text-3xl font-bold text-red-900 mt-2">
                {{ $jumlahPengembalian }}
            </p>

            <p class="text-xs text-red-600 mt-1">
                Data pengembalian
            </p>
        </div>

    </div>


    {{-- Tabel Log Aktivitas --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">

        <div class="px-5 py-5 border-b border-gray-200 bg-gray-50">

            <h3 class="text-lg font-bold text-gray-800">
                Log Aktivitas Terbaru
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Menampilkan aktivitas terbaru yang terjadi di dalam sistem.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left border-collapse">

                <thead>

                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">

                        <th class="py-3 px-4 border-b whitespace-nowrap">
                            Waktu
                        </th>

                        <th class="py-3 px-4 border-b whitespace-nowrap">
                            User
                        </th>

                        <th class="py-3 px-4 border-b">
                            Aktivitas
                        </th>

                    </tr>

                </thead>


                <tbody class="text-gray-700 text-sm">

                    @forelse($logs as $log)

                        <tr class="hover:bg-gray-50 transition">

                            <td class="py-3 px-4 border-b whitespace-nowrap">
                                {{ $log->created_at->format('d-m-Y H:i:s') }}
                            </td>

                            <td class="py-3 px-4 border-b font-medium text-gray-900 whitespace-nowrap">
                                {{ $log->user->name ?? 'User tidak ditemukan' }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $log->aktivitas }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="3"
                                class="py-6 text-center text-gray-500"
                            >
                                Belum ada log aktivitas.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

@endsection