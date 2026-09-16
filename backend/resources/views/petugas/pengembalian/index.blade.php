@extends('layouts.app')

@section('title', 'Pemantauan Pengembalian - Petugas')
@section('header-title', 'Pemantauan Pengembalian')

@section('content')

<div class="bg-white rounded-lg shadow-sm border border-gray-200">

    {{-- Header --}}
    <div class="p-4 border-b border-gray-200">

        <h2 class="text-lg font-semibold text-gray-800">
            Daftar Peminjaman yang Belum Dikembalikan
        </h2>

        <p class="text-sm text-gray-500 mt-1">
            Data peminjaman yang saat ini masih dalam status dipinjam.
        </p>

    </div>


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


    {{-- Table --}}
    <div class="overflow-x-auto">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">

                <tr>

                    <th class="px-4 py-3">
                        Peminjam
                    </th>

                    <th class="px-4 py-3">
                        Alat
                    </th>

                    <th class="px-4 py-3">
                        Tanggal Pinjam
                    </th>

                    <th class="px-4 py-3">
                        Rencana Kembali
                    </th>

                    <th class="px-4 py-3 text-center">
                        Status
                    </th>

                    <th class="px-4 py-3 text-center">
                        Aksi
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-200">

                @forelse($peminjamans as $peminjaman)

                    <tr class="hover:bg-gray-50">

                        {{-- Peminjam --}}
                        <td class="px-4 py-4 font-medium text-gray-800">

                            {{ $peminjaman->user->name ?? 'User Dihapus' }}

                        </td>


                        {{-- Alat --}}
                        <td class="px-4 py-4">

                            <ul class="list-disc list-inside space-y-1">

                                @foreach($peminjaman->detailPinjams as $detail)

                                    <li>

                                        {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}

                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                            {{ $detail->jumlah }} pcs
                                        </span>

                                    </li>

                                @endforeach

                            </ul>

                        </td>


                        {{-- Tanggal Pinjam --}}
                        <td class="px-4 py-4 text-gray-600">

                            {{ $peminjaman->tgl_pinjam->format('d-m-Y') }}

                        </td>


                        {{-- Rencana Kembali --}}
                        <td class="px-4 py-4 text-gray-600">

                            {{ $peminjaman->tgl_kembali_plan->format('d-m-Y') }}

                        </td>


                        {{-- Status --}}
                        <td class="px-4 py-4 text-center">

                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">
                                {{ ucfirst($peminjaman->status) }}
                            </span>

                        </td>


                        {{-- Aksi --}}
<td class="px-4 py-4 text-center">

    <form
        action="{{ route('petugas.pengembalian.ajukan', $peminjaman->id) }}"
        method="POST"
    >

        @csrf

        <div class="flex flex-col gap-2">

            {{-- Kondisi --}}
            <select
                name="kondisi_kembali"
                required
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

                <option value="">
                    Pilih Kondisi
                </option>

                <option value="Baik">
                    Baik
                </option>

                <option value="Rusak Ringan">
                    Rusak Ringan
                </option>

                <option value="Rusak Berat">
                    Rusak Berat
                </option>

            </select>


            {{-- Denda Kerusakan --}}
            <input
                type="number"
                name="denda_kerusakan"
                min="0"
                value="0"
                required
                placeholder="Denda Kerusakan"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >


            {{-- Tombol --}}
            <button
                type="submit"
                onclick="return confirm('Apakah alat sudah benar-benar dikembalikan dan ingin mengajukan pengembalian kepada Admin?')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
            >
                Ajukan Pengembalian
            </button>

        </div>

    </form>

</td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-gray-500"
                        >
                            Tidak ada peminjaman yang sedang dipinjam.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection