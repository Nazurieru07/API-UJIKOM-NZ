<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PeminjamanResource;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LaporanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // 1. Validasi Input Parameter
        $validator = Validator::make($request->all(), [
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'in:diajukan,dipinjam,dikembalikan,telat'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parameter filter tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Eager loading untuk mencegah masalah N+1 Query
        // Setelah migrasi serial: detail_pinjam merujuk ke alat_unit,
        // bukan ke alat langsung. Relasi 'alat' lama sudah dihapus dari
        // DetailPinjam -- pakai alatUnit.alat untuk sampai ke alatnya.
        $query = Peminjaman::with([
            'user',
            'detailPinjams.alatUnit.alat',
            'pengembalian.petugas'
        ]);

        // Filter: Rentang Tanggal Pinjam
        $query->when(
            $request->filled('start_date') && $request->filled('end_date'),
            function ($q) use ($request) {
                $q->whereBetween('tgl_pinjam', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
        );

        // Filter: Status Peminjaman
        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        // Pagination
        $perPage = $request->input('per_page', 15);
        $laporan = $query->latest()->paginate($perPage);

        // API Resource khusus untuk instance Paginate
        return PeminjamanResource::collection($laporan)
            ->additional([
                'message' => 'Laporan peminjaman berhasil ditarik.'
            ])
            ->response();
    }
}