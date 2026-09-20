@extends('layouts.app')

@section('title', 'Detail Kategori - Panel Admin')
@section('header-title', 'Detail Kategori')

@section('content')

    <div class="mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                {{ $kategori->nama_kategori }}
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Daftar alat dalam kategori ini
            </p>
        </div>

        <a
            href="{{ route('admin.kategori.index') }}"
            class="bg-gray-200 hover:bg-gray-300 text-gray-700
                   px-4 py-2 rounded-lg text-sm font-semibold transition"
        >
            ← Kembali
        </a>
    </div>

    {{-- Ringkasan Kondisi Alat --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Total Alat --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">
            Total Alat
        </p>

        <p class="text-2xl font-bold text-blue-600 mt-1">
            {{ $kategori->alat_count }}
        </p>

        <p class="text-xs text-gray-400 mt-1">
            alat
        </p>
    </div>

    {{-- Baik --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">
            Kondisi Baik
        </p>

        <p class="text-2xl font-bold text-emerald-600 mt-1">
            {{ $kategori->jumlah_baik }}
        </p>

        <p class="text-xs text-gray-400 mt-1">
            pcs
        </p>
    </div>

    {{-- Rusak --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">
            Kondisi Rusak
        </p>

        <p class="text-2xl font-bold text-amber-600 mt-1">
            {{ $kategori->jumlah_rusak }}
        </p>

        <p class="text-xs text-gray-400 mt-1">
            pcs
        </p>
    </div>

    {{-- Rusak Parah --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">
            Kondisi Rusak Parah
        </p>

        <p class="text-2xl font-bold text-red-600 mt-1">
            {{ $kategori->jumlah_rusak_parah }}
        </p>

        <p class="text-xs text-gray-400 mt-1">
            pcs
        </p>
    </div>

</div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">

        <div class="p-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">
                Daftar Alat
            </h3>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-left border-collapse">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">

                        <th class="py-3 px-4 border-b w-16 text-center">
                            No
                        </th>

                        <th class="py-3 px-4 border-b">
                            Nama Alat
                        </th>

                        <th class="py-3 px-4 border-b text-center">
                            Stok
                        </th>

                        <th class="py-3 px-4 border-b text-center">
                            Kondisi
                        </th>

                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">

                    @forelse($kategori->alat as $index => $alat)

                        <tr class="hover:bg-gray-50 transition">

                            <td class="py-3 px-4 border-b text-center">
                                {{ $index + 1 }}
                            </td>

                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $alat->nama_alat }}
                            </td>

                            <td class="py-3 px-4 border-b text-center">
                                {{ $alat->stok }}
                            </td>

                            <td class="py-3 px-4 border-b">
    <div class="flex flex-wrap justify-center gap-2">

        {{-- Kondisi Baik --}}
        <span class="inline-flex items-center px-3 py-1 rounded-full
                     bg-emerald-50 text-emerald-700
                     text-xs font-semibold">
            Baik: {{ $alat->stok_baik }} pcs
        </span>

        {{-- Kondisi Rusak --}}
        <span class="inline-flex items-center px-3 py-1 rounded-full
                     bg-amber-50 text-amber-700
                     text-xs font-semibold">
            Rusak: {{ $alat->stok_rusak }} pcs
        </span>

        {{-- Kondisi Rusak Parah --}}
        <span class="inline-flex items-center px-3 py-1 rounded-full
                     bg-red-50 text-red-700
                     text-xs font-semibold">
            Rusak Parah: {{ $alat->stok_rusak_parah }} pcs
        </span>

    </div>
</td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="4"
                                class="py-8 text-center text-gray-500"
                            >
                                Belum ada alat dalam kategori ini.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

@endsection