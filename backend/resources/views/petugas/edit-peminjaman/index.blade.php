@extends('layouts.app')

@section('title', 'Permintaan Edit Peminjaman - Dashboard Petugas')
@section('header-title', 'Daftar Permintaan Edit Peminjaman')

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
        <h3 class="text-lg font-bold text-gray-800">Permintaan Edit Menunggu Persetujuan</h3>
        <p class="text-sm text-gray-600 mt-1">{{ $permintaanEdits->total() }} permintaan</p>

        <form action="{{ route('petugas.edit-peminjaman.index') }}" method="GET"
              class="flex flex-col md:flex-row gap-2 w-full mt-4">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama, email, atau alasan..."
                   class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

            <select name="peminjam_id"
                    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">Semua Peminjam</option>
                @foreach($daftarPeminjam as $peminjam)
                    <option value="{{ $peminjam->id }}"
                            {{ request('peminjam_id') == $peminjam->id ? 'selected' : '' }}>
                        {{ $peminjam->name }} ({{ $peminjam->email }})
                    </option>
                @endforeach
            </select>

            <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}"
                   class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}"
                   class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

            <button type="submit"
                    class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition">
                Cari
            </button>

            @if(request()->hasAny(['search', 'peminjam_id', 'tanggal_dari', 'tanggal_sampai']))
                <a href="{{ route('petugas.edit-peminjaman.index') }}"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center justify-center transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Peminjam</th>
                    <th class="px-4 py-3">Peminjaman</th>
                    <th class="px-4 py-3">Tanggal Baru</th>
                    <th class="px-4 py-3">Alasan</th>
                    <th class="px-4 py-3">Perubahan Alat</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($permintaanEdits as $req)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">#{{ $req->id }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900">{{ $req->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $req->user->email }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-xs text-gray-600">Peminjaman #{{ $req->peminjaman->id }}</p>
                        <p class="text-xs text-gray-500">{{ $req->peminjaman->tgl_pinjam->format('d M Y') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @if($req->tgl_kembali_plan_baru)
                        <p class="text-xs"><span class="font-medium">Dari:</span> {{ $req->peminjaman->tgl_kembali_plan?->format('d M Y') }}</p>
                        <p class="text-xs"><span class="font-medium">Ke:</span> {{ $req->tgl_kembali_plan_baru->format('d M Y') }}</p>
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-xs text-gray-700 max-w-xs truncate" title="{{ $req->alasan }}">
                            {{ $req->alasan ?? '-' }}
                        </p>
                    </td>
                    <td class="px-4 py-3">
                        <div class="space-y-1">
                            @foreach($req->detailEdits as $detail)
                            <div class="text-xs flex items-center gap-2">
                                @if($detail->aksi === 'tambah')
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded">+ Tambah</span>
                                @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded">- Hapus</span>
                                @endif
                                <span>{{ $detail->alatUnit?->alat?->nama_alat ?? 'Alat Dihapus' }}</span>
                                <span class="font-semibold">{{ $detail->alatUnit?->serial_number ?? '-' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <form action="{{ route('petugas.edit-peminjaman.setujui', $req->id) }}" method="POST" 
                                  onsubmit="return confirm('Setujui permintaan edit ini?')">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-1.5 bg-emerald-600 text-white text-xs rounded-lg hover:bg-emerald-700 transition">
                                    ✓ Setujui
                                </button>
                            </form>
                            
                            <button type="button" 
                                    onclick="openTolakModal({{ $req->id }})"
                                    class="px-3 py-1.5 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition">
                                ✗ Tolak
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        Tidak ada permintaan edit yang menunggu persetujuan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-gray-200">
        {{ $permintaanEdits->links() }}
    </div>
</div>

<div id="tolakModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Tolak Permintaan Edit</h3>
        <form id="tolakForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Penolakan (Opsional)</label>
                <textarea name="catatan_penolakan" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"
                          placeholder="Tulis alasan penolakan..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Tolak Permintaan
                </button>
                <button type="button" onclick="closeTolakModal()" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openTolakModal(id) {
    document.getElementById('tolakForm').action = '/petugas/edit-peminjaman/' + id + '/tolak';
    document.getElementById('tolakModal').classList.remove('hidden');
}

function closeTolakModal() {
    document.getElementById('tolakModal').classList.add('hidden');
}
</script>

@endsection
