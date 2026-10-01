@extends('layouts.peminjam')

@section('title', 'Ajukan Edit Peminjaman')

@section('page-heading', 'Ajukan Edit Peminjaman')

@section('content')
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 sm:p-6 mb-5">
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
        <p class="text-sm text-yellow-800 font-medium">Pilih serial unit yang ingin ditambah/dihapus.</p>
    </div>

    <form action="{{ route('peminjam.edit.ajukan', $peminjaman->id) }}" method="POST">
        @csrf

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Rencana Kembali Baru</label>
            <input type="date" name="tgl_kembali_plan_baru"
                   value="{{ old('tgl_kembali_plan_baru') ?? ($peminjaman->tgl_kembali_plan ? $peminjaman->tgl_kembali_plan->format('Y-m-d') : '') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   required>
            @error('tgl_kembali_plan_baru')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Alasan (Opsional)</label>
            <textarea name="alasan" rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Tulis alasan...">{{ old('alasan') }}</textarea>
            @error('alasan')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
            <h4 class="font-semibold text-gray-900 mb-3">Unit Saat Ini</h4>
            <div class="space-y-2">
                @foreach($peminjaman->detailPinjams as $detail)
                <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">{{ $detail->alatUnit?->alat->nama_alat ?? 'Alat Dihapus' }}</p>
                        <p class="text-xs text-gray-500">{{ $detail->alatUnit?->alat->kategori->nama_kategori ?? 'Tanpa kategori' }}</p>
                    </div>
                    <p class="text-sm font-semibold text-gray-700 font-mono">{{ $detail->alatUnit?->serial_number ?? '-' }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
            <h4 class="font-semibold text-gray-900 mb-3">Tambah/Hapus Unit (Opsional)</h4>
            <p class="text-xs text-gray-600 mb-3">Kosongkan jika hanya ingin mengubah tanggal.</p>
            <div id="new-alats" class="space-y-3">
            </div>
            <button type="button" id="add-alat-btn" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">+ Tambah Unit</button>
        </div>

        <div class="flex gap-3 mt-8">
            <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition">Ajukan Edit Peminjaman</button>
            <a href="{{ route('peminjam.riwayat') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition">Batal</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var addBtn = document.getElementById('add-alat-btn');
    var container = document.getElementById('new-alats');
    var alatOptions = '@foreach($alats as $alat)<optgroup label="{{ $alat->nama_alat }} (Tersedia: {{ $alat->alatUnit->where("kondisi", "tersedia")->count() }})">@foreach($alat->alatUnit->where("kondisi", "tersedia") as $unit)<option value="{{ $unit->id }}">{{ $unit->serial_number }}</option>@endforeach</optgroup>@endforeach';

    addBtn.addEventListener('click', function() {
        var item = document.createElement('div');
        item.className = 'new-alat-item flex items-center gap-3 p-2 bg-white border border-gray-200 rounded-lg';
        item.innerHTML = '<select name="alat_unit_id[]" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg"><option value="">Pilih Serial Unit</option>' + alatOptions + '</select>' +
            '<select name="aksi[]" class="w-28 px-3 py-2 border border-gray-300 rounded-lg"><option value="tambah">+ Tambah</option><option value="hapus">- Hapus</option></select>' +
            '<button type="button" class="remove-item px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg">X</button>';
        container.appendChild(item);
    });
    
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            e.target.closest('.new-alat-item').remove();
        }
    });
});
</script>
@endsection
