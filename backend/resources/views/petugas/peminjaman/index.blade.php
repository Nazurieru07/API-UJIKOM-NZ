@extends('layouts.app')

@section('title', 'Persetujuan Peminjaman - Dashboard Petugas')
@section('header-title', 'Daftar Pengajuan Peminjaman Alat')

@section('content')

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Menunggu Verifikasi Persetujuan</h3>

            <form action="{{ route('petugas.peminjaman.index') }}" method="GET" class="flex w-full md:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama peminjam..."
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

                <button type="submit"
                    class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition">
                    Cari
                </button>

                @if(request('search'))
                    <a href="{{ route('petugas.peminjaman.index') }}"
                        class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Peminjam</th>
                        <th class="py-3 px-4 border-b">Tanggal Pinjam</th>
                        <th class="py-3 px-4 border-b">Rencana Kembali</th>
                        <th class="py-3 px-4 border-b">Detail Alat</th>
                        <th class="py-3 px-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse($peminjamans as $item)
                        <tr class="hover:bg-gray-50 transition align-top">

                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $item->user->name ?? 'User Dihapus' }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $item->tgl_pinjam }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $item->tgl_kembali_plan }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    @foreach($item->detailPinjams as $detail)
                                        <li>
                                            <span class="font-semibold">
                                                {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}
                                            </span>
                                            <br>
                                            (Jumlah: {{ $detail->jumlah }})
                                        </li>
                                    @endforeach
                                </ul>
                            </td>

                            <td class="py-3 px-4 border-b text-center">

    @if($item->status == 'diajukan')

        <div class="flex justify-center items-center space-x-2">

            {{-- Tombol Setujui --}}
            <form action="{{ route('petugas.peminjaman.setujui', $item->id) }}" method="POST">
                @csrf

                <button type="submit"
                    onclick="return confirm('Setujui peminjaman alat ini?')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition shadow-sm">
                    Setujui
                </button>
            </form>


            {{-- Tombol Tolak --}}
            <form action="{{ route('petugas.peminjaman.tolak', $item->id) }}" method="POST">
                @csrf

                <button type="submit"
                    onclick="return confirm('Yakin ingin menolak pengajuan peminjaman ini?')"
                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition shadow-sm">
                    Tolak
                </button>
            </form>

        </div>

    @else

        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2.5 py-1 rounded">
            {{ ucfirst($item->status) }}
        </span>

    @endif

</td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-500">
                                Tidak ada pengajuan peminjaman baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection