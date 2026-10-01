<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Peminjaman\StorePeminjamanRequest;
use App\Http\Resources\PeminjamanResource;
use App\Models\AlatUnit;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use Exception;

class PeminjamanController extends Controller
{
    public function index() : JsonResponse
    {
        $user = auth()->user();
        $query = Peminjaman::with(['user', 'detailPinjams.alatUnit.alat', 'pengembalian']);
        if ($user->role === 'peminjam') {
            $query->where('user_id', $user->id);
        }
        $peminjaman = $query->latest()->get();

        return response()->json([
            'message'   => 'Daftar peminjaman berhasil diambil.',
            'data'      => PeminjamanResource::collection($peminjaman)
        ]);
    }

    public function store(StorePeminjamanRequest $request) : JsonResponse
    {
        try {
            $peminjaman = DB::transaction(function () use ($request) {
                $user = auth()->user();
                $peminjaman = Peminjaman::create([
                    'user_id'           => $user->id,
                    'tgl_pinjam'        => now()->toDateString(),
                    'tgl_kembali_plan'  => $request->tgl_kembali_plan,
                    'status'            => 'diajukan',
                ]);

                $unitIds = array_map(fn ($item) => (int) $item['alat_unit_id'], $request->items);

                // Satu unit tidak boleh masuk dua kali dalam satu pengajuan.
                if (count($unitIds) !== count(array_unique($unitIds))) {
                    throw new Exception('Ada unit alat yang dipilih lebih dari sekali.');
                }

                // Unit yang sudah dipinjam/rusak harus ditolak di sini,
                // supaya pemohon dapat pesan ulang tanpa menunggu approve.
                $units = AlatUnit::whereIn('id', $unitIds)->get()->keyBy('id');

                foreach ($request->items as $item) {
                    $unitId = (int) $item['alat_unit_id'];
                    $unit = $units->get($unitId);

                    if (!$unit || !$unit->isTersedia()) {
                        throw new Exception('Unit alat yang dipilih sudah tidak tersedia (sedang dipinjam atau rusak).');
                    }

                    DetailPinjam::create([
                        'peminjaman_id' => $peminjaman->id,
                        'alat_unit_id'  => $unitId,
                    ]);
                }
                return $peminjaman->load(['user', 'detailPinjams.alatUnit.alat']);
            });

            return response()->json([
                'message'       => 'Peminjaman berhasil diajukan. Menunggu persetujuan petugas.',
                'data'          => new PeminjamanResource($peminjaman)
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Peminjaman $peminjaman) : JsonResponse
    {
        $user = auth()->user();
        if ($user->role === 'peminjam' && $peminjaman->user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'message'   => 'Detail peminjaman berhasil diambil',
            'data'      => new PeminjamanResource($peminjaman->load(['user', 'detailPinjams.alatUnit.alat', 'pengembalian']))
        ]);
    }

    public function update(StorePeminjamanRequest $request, Peminjaman $peminjaman) : JsonResponse
    {
        $user = auth()->user();
        if ($user->role === 'peminjam' && $peminjaman->user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($peminjaman->status !== 'diajukan') {
            return response()->json([
                'message' => "Peminjaman tidak dapat diubah karena status saat ini: {$peminjaman->status}."
            ], 400);
        }

        try {
            DB::transaction(function () use ($request, $peminjaman) {
                $peminjaman->update([
                    'tgl_kembali_plan' => $request->tgl_kembali_plan,
                ]);

                $peminjaman->detailPinjams()->delete();

                $unitIds = array_map(fn ($item) => (int) $item['alat_unit_id'], $request->items);

                if (count($unitIds) !== count(array_unique($unitIds))) {
                    throw new Exception('Ada unit alat yang dipilih lebih dari sekali.');
                }

                $units = AlatUnit::whereIn('id', $unitIds)->get()->keyBy('id');

                foreach ($request->items as $item) {
                    $unitId = (int) $item['alat_unit_id'];
                    $unit = $units->get($unitId);

                    if (!$unit || !$unit->isTersedia()) {
                        throw new Exception('Unit alat yang dipilih sudah tidak tersedia (sedang dipinjam atau rusak).');
                    }

                    DetailPinjam::create([
                        'peminjaman_id' => $peminjaman->id,
                        'alat_unit_id'  => $unitId,
                    ]);
                }
            });

            return response()->json([
                'message'   => 'Data permohonan peminjaman berhasil diperbarui.',
                'data'      => new PeminjamanResource($peminjaman->load(['user', 'detailPinjams.alatUnit.alat']))
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Peminjaman $peminjaman) : JsonResponse
    {
        $user = auth()->user();
        if ($user->role === 'peminjam' && $peminjaman->user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($peminjaman->status !== 'diajukan') {
            return response()->json([
                'message'   => 'Peminjaman tidak dapat dibatalkan.'
            ], 400);
        }

        DB::transaction(function () use ($peminjaman) {
            $peminjaman->detailPinjams()->delete();
            $peminjaman->delete();
        });

        return response()->json([
            'message'   => 'Permohonan peminjaman berhasil dibatalkan dan dihapus.'
        ]);
    }

    public function approve(Peminjaman $peminjaman) : JsonResponse
    {
        if ($peminjaman->status !== 'diajukan') {
            return response()->json([
                'message'   => "Persetujuan gagal. Status saat ini: {$peminjaman->status}."
            ], 400);
        }

        try {
            DB::transaction(function () use ($peminjaman) {
                $peminjaman->load('detailPinjams.alatUnit.alat');

                foreach ($peminjaman->detailPinjams as $detail) {
                    $unit = $detail->alatUnit;

                    if (!$unit) {
                        throw new Exception("Persetujuan gagal. Unit pada item #{$detail->id} tidak ditemukan.");
                    }

                    // PENTING: cek ketersediaan WAJIB atomic. Dua petugas bisa
                    // menyetujui pengajuan bersamaan; hanya satu yang rowCount()==1.
                    $affected = AlatUnit::where('id', $unit->id)
                        ->where('kondisi', 'tersedia')
                        ->update(['kondisi' => 'dipinjam']);

                    if ($affected !== 1) {
                        $nama = $unit->alat?->nama_alat ?? 'tidak dikenal';
                        throw new Exception("Persetujuan gagal. Unit '{$unit->serial_number}' dari alat '{$nama}' sudah tidak tersedia (sedang dipinjam atau rusak).");
                    }
                }

                $peminjaman->update(['status' => 'dipinjam']);
            });

            return response()->json([
                'message'   => 'Peminjaman disetujui. Unit alat ditandai dipinjam.',
                'data'      => new PeminjamanResource($peminjaman->load(['user', 'detailPinjams.alatUnit.alat']))
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function riwayat() : JsonResponse
    {
        $riwayat = Peminjaman::with(['detailPinjams.alatUnit.alat', 'pengembalian'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json([
            'message'   => 'Riwayat peminjaman anda.',
            'data'      => PeminjamanResource::collection($riwayat)
        ]);
    }
}
