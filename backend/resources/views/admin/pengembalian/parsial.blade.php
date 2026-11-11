@extends('layouts.app')

@section('title', 'Pengembalian Parsial - Admin')

@section('page-heading', 'Pengembalian Parsial')

@section('page-description')
    Periksa dan kembalikan sebagian alat dari peminjaman.
@endsection

@section('content')

    @if(session('error'))
        <div class="mb-5 px-4 py-3 rounded-xl
                    bg-red-50 border border-red-200 text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mb-6">
        
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-900">
                    Peminjaman #{{ $peminjaman->id }}
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Peminjam: {{ $peminjaman->user->name }}
                </p>
            </div>
            
            <div class="text-right">
                <span class="inline-flex items-center gap-2
                             px-3 py-1.5 rounded-full text-xs font-semibold
                             {{ $peminjaman->status === 'dipinjam' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                    <span class="w-2 h-2 rounded-full {{ $peminjaman->status === 'dipinjam' ? 'bg-blue-500' : 'bg-red-500' }}"></span>
                    {{ $peminjaman->status === 'dipinjam' ? 'Sedang Dipinjam' : 'Terlambat' }}
                </span>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <p class="text-sm text-gray-500">Tanggal Pinjam</p>
                <p class="font-semibold text-gray-800">
                    {{ $peminjaman->tgl_pinjam?->format('d F Y') ?? '-' }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Rencana Kembali</p>
                <p class="font-semibold text-gray-800">
                    {{ $peminjaman->tgl_kembali_plan?->format('d F Y') ?? '-' }}
                </p>
            </div>
        </div>
        
        <form action="{{ route('admin.pengembalian.parsial.proses', $peminjaman->id) }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 mb-4">
                    Pilih Alat yang Dikembalikan
                </h4>
                
                <div class="space-y-4">
                    @foreach($peminjaman->detailPinjams as $detail)
                        @php
                            $sudahDikembalikan = false;
                            $kondisiSebelumnya = null;
                            $dendaSebelumnya = 0;
                            
                            if ($peminjaman->pengembalian) {
                                $detailPengembalian = $peminjaman->pengembalian->detailPengembalians
                                    ->where('detail_pinjam_id', $detail->id)
                                    ->first();
                                
                                if ($detailPengembalian) {
                                    $sudahDikembalikan = true;
                                    $kondisiSebelumnya = $detailPengembalian->kondisi_kembali;
                                    $dendaSebelumnya = $detailPengembalian->denda_kerusakan;
                                }
                            }
                        @endphp
                        
                        <div class="p-4 border border-gray-200 rounded-xl {{ $sudahDikembalikan ? 'bg-gray-50' : 'bg-white' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            id="unit-{{ $detail->id }}"
                                            name="detail_pinjam_id[]"
                                            value="{{ $detail->id }}"
                                            {{ $sudahDikembalikan ? 'disabled' : '' }}
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        >
                                        
                                        <div>
                                            <label for="unit-{{ $detail->id }}" class="font-semibold text-gray-800 cursor-pointer">
                                                {{ $detail->alatUnit?->alat->nama_alat ?? 'Alat Dihapus' }}
                                            </label>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Serial: {{ $detail->alatUnit?->serial_number ?? '-' }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    @if($sudahDikembalikan)
                                        <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <p class="text-sm text-green-700">
                                                ✓ Unit ini sudah dikembalikan sebelumnya
                                            </p>
                                            <p class="text-xs text-green-600 mt-1">
                                                Kondisi: {{ $kondisiSebelumnya }} | 
                                                Denda Kerusakan: Rp {{ number_format($dendaSebelumnya, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    @else
                                        <div class="mt-3 space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Kondisi Saat Dikembalikan
                                                </label>
                                                <select
                                                    name="kondisi_kembali[{{ $detail->id }}]"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg
                                                           focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                                >
                                                    <option value="Baik">Baik</option>
                                                    <option value="Rusak">Rusak</option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Denda Kerusakan (Rp)
                                                </label>
                                                <input
                                                    type="number"
                                                    name="denda_kerusakan[{{ $detail->id }}]"
                                                    value="0"
                                                    min="0"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg
                                                           focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                                >
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Isi 0 jika tidak ada kerusakan
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <a href="{{ route('admin.peminjaman.index') }}"
                   class="px-4 py-2.5 rounded-xl text-sm font-semibold
                          border border-gray-300 text-gray-700
                          hover:bg-gray-50 transition">
                    Kembali
                </a>
                
                <button type="submit"
                        class="inline-flex items-center gap-2
                               px-6 py-3 rounded-xl text-sm font-semibold
                               bg-blue-600 text-white
                               hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Proses Pengembalian
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5">
        <div class="flex items-start gap-3">
            <div class="text-blue-600">
                💡
            </div>
            <div>
                <h4 class="font-semibold text-blue-800">Informasi Pengembalian Parsial</h4>
                <p class="text-sm text-blue-700 mt-1">
                    Fitur ini memungkinkan Anda untuk mengembalikan sebagian alat dari peminjaman.
                    Sistem akan mencatat setiap unit yang dikembalikan beserta kondisinya.
                    Peminjaman akan tetap berstatus "Dipinjam" sampai semua unit dikembalikan.
                </p>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tampilkan/sembunyikan form kondisi saat checkbox dicentang
        const checkboxes = document.querySelectorAll('input[name="detail_pinjam_id[]"]');
        
        checkboxes.forEach(checkbox => {
    const unitCard = checkbox.closest('.border');
    const conditionFields = unitCard.querySelectorAll(
        'select[name^="kondisi_kembali["], input[name^="denda_kerusakan["]'
    );

    // Atur kondisi awal berdasarkan checkbox
    conditionFields.forEach(field => {
        field.disabled = !checkbox.checked;

        if (field.disabled) {
            field.classList.add('bg-gray-100');
        } else {
            field.classList.remove('bg-gray-100');
        }
    });

    // Aktifkan atau nonaktifkan kolom ketika checkbox berubah
    checkbox.addEventListener('change', function() {
        conditionFields.forEach(field => {
            field.disabled = !this.checked;

            if (field.disabled) {
                field.classList.add('bg-gray-100');
            } else {
                field.classList.remove('bg-gray-100');
            }
        });
    });
});
        
        // Validasi minimal satu unit dipilih
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('input[name="detail_pinjam_id[]"]:checked:not([disabled])');
            
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Pilih minimal satu alat yang akan dikembalikan.');
            }
        });
    });
</script>
@endpush