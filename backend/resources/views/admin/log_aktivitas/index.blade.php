@extends('layouts.app')

@section('title', 'Log Aktivitas - Panel Admin')

@section('header-title', 'Log Aktivitas Sistem')

@section('content')

<style>
    /* Pagination */
.custom-page {
    min-width: 42px;
    height: 42px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;

    background: #171717;
    color: white;

    border: 1px solid #171717;
    border-radius: 8px;

    font-size: 14px;
    font-weight: 600;

    text-decoration: none;
    transition: all 0.2s ease;
}

/* Hover nomor halaman */
.custom-page:hover {
    background: #f59e0b;
    color: #111827;
    border-color: #f59e0b;
}

/* Halaman aktif */
.custom-page.active {
    background: #171717;
    color: white;
    border: 2px solid #f59e0b;
}

/* Prev dan Next */
.custom-page.prev-next {
    min-width: 80px;

    background: #f59e0b;
    color: #111827;
    border-color: #f59e0b;
}

.custom-page.prev-next:hover {
    background: #d97706;
    border-color: #d97706;
}

/* Prev / Next ketika tidak bisa digunakan */
.custom-page.disabled {
    min-width: 80px;

    background: #f59e0b;
    color: #111827;
    border-color: #f59e0b;

    opacity: 0.5;
    cursor: not-allowed;
}
</style>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">

        {{-- Header --}}
        <div class="px-5 py-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">
                Log Aktivitas Sistem
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Riwayat aktivitas yang dilakukan pengguna di dalam sistem.
            </p>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">
                            Waktu
                        </th>

                        <th class="py-3 px-4 border-b">
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

                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $log->user->name ?? 'User tidak ditemukan' }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $log->aktivitas }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="3" class="py-6 text-center text-gray-500">
                                Belum ada log aktivitas.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        {{-- Pagination --}}
      @if ($logs->hasPages())
    <div class="px-5 py-4 border-t border-gray-200">

        <div class="flex justify-end items-center gap-2">

            {{-- Previous --}}
            @if ($logs->onFirstPage())
                <span class="custom-page disabled">
                    Prev
                </span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="custom-page prev-next">
                    Prev
                </a>
            @endif


            {{-- Nomor halaman --}}
            @php
                $current = $logs->currentPage();
                $last = $logs->lastPage();

                $start = max(1, min($current - 2, $last - 4));
                $end = min($last, $start + 4);
            @endphp

            @for ($page = $start; $page <= $end; $page++)

                @if ($page == $current)
                    <span class="custom-page active">
                        {{ $page }}
                    </span>
                @else
                    <a href="{{ $logs->url($page) }}" class="custom-page">
                        {{ $page }}
                    </a>
                @endif

            @endfor


            {{-- Next --}}
            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="custom-page prev-next">
                    Next
                </a>
            @else
                <span class="custom-page disabled">
                    Next
                </span>
            @endif

        </div>

    </div>
@endif

    </div>

@endsection