@extends('layouts.app')

@section('title', 'Periksa Pengembalian - Panel Admin')
@section('header-title', 'Periksa Pengembalian')

@section('content')

<div class="max-w-5xl mx-auto">
    
{{-- Alert Error --}}
@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg text-sm">
        {{ session('error') }}
    </div>
@endif


{{-- Header --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-5">

    <div class="p-5 border-b border-gray-200">

        <h2 class="text-lg font-semibold text-gray-800">
            Periksa Pengajuan Pengembalian
        </h2>

        <p class="text-sm text-gray-500 mt-1">
            Periksa hasil pemeriksaan Petugas sebelum menyetujui pengembalian alat.
        </p>

    </div>


    {{-- Informasi Peminjaman --}}
    <div class="p-5">

        <h3 class="text-sm font-semibold text-gray-800 mb-4">
            Informasi Peminjaman
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Peminjam --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Nama Peminjam
                </label>

                <div class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-800">
                    {{ $pengembalian->peminjaman->user->name }}
                </div>
            </div>


            {{-- Petugas --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Petugas Pemeriksa
                </label>

                <div class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-800">
                    {{ $pengembalian->petugas?->name ?? 'Admin' }}
                </div>
            </div>


            {{-- Tanggal Pinjam --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Tanggal Peminjaman
                </label>

                <div class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-800">
                    {{ $pengembalian->peminjaman->tgl_pinjam->format('d-m-Y') }}
                </div>
            </div>


            {{-- Batas Pengembalian --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Batas Pengembalian
                </label>

                <div class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-800">
                    {{ $pengembalian->peminjaman->tgl_kembali_plan->format('d-m-Y') }}
                </div>
            </div>


            {{-- Tanggal Kembali --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Tanggal Pengembalian
                </label>

                <div class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-800">
                    {{ $pengembalian->tgl_kembali->format('d-m-Y') }}
                </div>
            </div>


            {{-- Status Peminjaman --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Status Peminjaman
                </label>

                <div class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-800">
                    @if($pengembalian->peminjaman->status == 'telat')
                        <span class="text-red-600 font-semibold">
                            Terlambat
                        </span>
                    @else
                        <span class="text-blue-600 font-semibold">
                            Dipinjam
                        </span>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>


{{-- Alat yang Dikembalikan --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-5">

    <div class="p-5 border-b border-gray-200">

        <h3 class="text-sm font-semibold text-gray-800">
            Alat yang Dikembalikan
        </h3>

    </div>


    <div class="p-5">

        <div class="overflow-x-auto">

            <table class="w-full text-sm text-left">

                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">

                    <tr>

                        <th class="px-4 py-3">
                            No
                        </th>

                        <th class="px-4 py-3">
                            Nama Alat
                        </th>

                        <th class="px-4 py-3">
                            Jumlah
                        </th>

                        <th class="px-4 py-3">
                            Kondisi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-200">

                    @foreach($pengembalian->peminjaman->detailPinjams as $detail)

                        <tr>

                            <td class="px-4 py-3 text-gray-600">
                                {{ $loop->iteration }}
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $detail->alat->nama_alat }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ $detail->jumlah }} pcs
                            </td>

                            <td class="px-4 py-3">

                                @if($pengembalian->kondisi_kembali == 'Baik')

                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">
                                        Baik
                                    </span>

                                @elseif($pengembalian->kondisi_kembali == 'Rusak Ringan')

                                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-medium">
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

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- Hasil Pemeriksaan Petugas --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-5">

    <div class="p-5 border-b border-gray-200">

        <h3 class="text-sm font-semibold text-gray-800">
            Hasil Pemeriksaan Petugas
        </h3>

        <p class="text-xs text-gray-500 mt-1">
            Data berikut berasal dari pemeriksaan Petugas di lapangan.
        </p>

    </div>


    <div class="p-5">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Kondisi --}}
            <div>

                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Kondisi Alat
                </label>

                <div class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm">

                    @if($pengembalian->kondisi_kembali == 'Baik')

                        <span class="text-green-700 font-semibold">
                            Baik
                        </span>

                    @elseif($pengembalian->kondisi_kembali == 'Rusak Ringan')

                        <span class="text-yellow-700 font-semibold">
                            Rusak Ringan
                        </span>

                    @elseif($pengembalian->kondisi_kembali == 'Rusak Berat')

                        <span class="text-red-700 font-semibold">
                            Rusak Berat
                        </span>

                    @else

                        <span class="text-gray-700 font-semibold">
                            {{ $pengembalian->kondisi_kembali }}
                        </span>

                    @endif

                </div>

            </div>


            {{-- Denda Kerusakan --}}
            <div>

                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Denda Kerusakan
                </label>

                <div class="border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm font-semibold text-red-600">
                    Rp {{ number_format($pengembalian->denda_kerusakan ?? 0, 0, ',', '.') }}
                </div>

            </div>

        </div>


        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">

            <p class="text-sm text-blue-800">
                <span class="font-semibold">Catatan:</span>
                Denda kerusakan ditentukan oleh Petugas berdasarkan hasil pemeriksaan alat.
                Admin hanya melakukan validasi dan persetujuan terhadap pengajuan pengembalian.
            </p>

        </div>

    </div>

</div>


{{-- Perhitungan Denda --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-5">

    <div class="p-5 border-b border-gray-200">

        <h3 class="text-sm font-semibold text-gray-800">
            Perhitungan Denda
        </h3>

    </div>


    <div class="p-5">

        @php
            $tglKembaliPlan = \Carbon\Carbon::parse($pengembalian->peminjaman->tgl_kembali_plan);
            $tglKembali = \Carbon\Carbon::parse($pengembalian->tgl_kembali);

            $hariTerlambat = max(
                0,
                $tglKembaliPlan->diffInDays($tglKembali, false)
            );

           $dendaKeterlambatan = $hariTerlambat * config('denda.keterlambatan_per_hari');

            $dendaKerusakan = $pengembalian->denda_kerusakan ?? 0;

            $totalDenda = $dendaKeterlambatan + $dendaKerusakan;
        @endphp


        <div class="space-y-3">

            {{-- Denda Keterlambatan --}}
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">

                <div>

                    <p class="text-sm font-medium text-gray-700">
                        Denda Keterlambatan
                    </p>

                    <p class="text-xs text-gray-500 mt-1">
                    {{ $hariTerlambat }} hari ×
                    Rp {{ number_format(config('denda.keterlambatan_per_hari'), 0, ',', '.') }}
                </p>

                </div>

                <span class="text-sm font-semibold text-red-600">
                    Rp {{ number_format($dendaKeterlambatan, 0, ',', '.') }}
                </span>

            </div>


            {{-- Denda Kerusakan --}}
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">

                <div>

                    <p class="text-sm font-medium text-gray-700">
                        Denda Kerusakan
                    </p>

                    <p class="text-xs text-gray-500 mt-1">
                        Berdasarkan hasil pemeriksaan Petugas
                    </p>

                </div>

                <span class="text-sm font-semibold text-red-600">
                    Rp {{ number_format($dendaKerusakan, 0, ',', '.') }}
                </span>

            </div>


            {{-- Total --}}
            <div class="flex items-center justify-between bg-red-50 border border-red-200 rounded-lg p-4 mt-4">

                <div>

                    <p class="text-sm font-semibold text-red-800">
                        Total Denda
                    </p>

                    <p class="text-xs text-red-600 mt-1">
                        Denda keterlambatan + denda kerusakan
                    </p>

                </div>

                <span class="text-xl font-bold text-red-700">
                    Rp {{ number_format($totalDenda, 0, ',', '.') }}
                </span>

            </div>

        </div>

    </div>

</div>


{{-- Tombol Aksi --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200">

    <div class="p-5 flex flex-col sm:flex-row items-center justify-between gap-3">

        {{-- Kembali --}}
        <a
            href="{{ route('admin.pengembalian.index') }}"
            class="w-full sm:w-auto text-center bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2.5 rounded-lg text-sm font-semibold transition"
        >
            Kembali
        </a>


        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">

            {{-- Tolak --}}
            <form
                action="{{ route('admin.pengembalian.reject', $pengembalian->id) }}"
                method="POST"
                onsubmit="return confirm('Apakah kamu yakin ingin menolak pengajuan pengembalian ini?')"
                class="w-full sm:w-auto"
            >

                @csrf

                <button
                    type="submit"
                    class="w-full bg-red-500 hover:bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition"
                >
                    Tolak Pengajuan
                </button>

            </form>


            {{-- Setujui --}}
            <form
                action="{{ route('admin.pengembalian.setujui', $pengembalian->id) }}"
                method="POST"
                onsubmit="return confirm('Apakah kamu yakin ingin menyetujui pengembalian ini?')"
                class="w-full sm:w-auto"
            >

                @csrf

                <button
                    type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition"
                >
                    Setujui Pengembalian
                </button>

            </form>

        </div>

    </div>

</div>

</div>

@endsection
