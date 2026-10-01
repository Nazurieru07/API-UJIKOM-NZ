<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengembalian\StorePengembalianRequest;
use App\Http\Requests\Pengembalian\UpdatePengembalianRequest;
use App\Models\AlatUnit;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class PengembalianController extends Controller
{
    public function index() : JsonResponse
    {
        $user = auth()->user();
        $query = Pengembalian::with(['peminjaman.user', 'peminjaman.detailPinjams.alatUnit.alat', 'petugas']);
        if ($user->role === 'peminjam') {
            $query->whereHas('peminjaman', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $pengembalian = $query->latest()->get();
        return response()->json([
            'message'   => 'Riwayat pengembalian berhasil diambil.',
            'data'      => $pengembalian
        ]);
    }

    /**
     * kondisi_kembali bebas teks ('Baik', 'Rusak Ringan', 'Rusak Parah', ...).
     * Yang penting unit TIDAK kembali ke katalog kalau kondisi != 'Baik':
     * unit rusak harus tersembunyi sampai admin memperbaikinya.
     */
    private function kondisiUnit(int|string|null $kondisiKembali): string
    {
        return mb_strtolower(trim((string) $kondisiKembali)) === 'baik'
            ? 'tersedia'
            : 'rusak';
    }

    public function store(StorePengembalianRequest $request) : JsonResponse
    {
        try {
            $pengembalian = DB::transaction(function () use ($request) {
                $peminjaman = Peminjaman::with('detailPinjams.alatUnit')->lockForUpdate()->find($request->peminjaman_id);

                if (!$peminjaman) {
                    throw new Exception('Data ditolak. Data peminjaman tidak ditemukan.');
                }

                if ($peminjaman->status !== 'dipinjam') {
                    throw new Exception("Data ditolak. Peminjaman ini berstatus '{$peminjaman->status}', bukan 'dipinjam'.");
                }

                $tglKembaliPlan = Carbon::parse($peminjaman->tgl_kembali_plan)->startOfDay();
                $hariIni = Carbon::now()->startOfDay();

                $statusPeminjamanBaru = $hariIni->greaterThan($tglKembaliPlan) ? 'telat' : 'dikembalikan';

                $pengembalian = Pengembalian::create([
                    'peminjaman_id'     => $peminjaman->id,
                    'tgl_kembali'       => now()->toDateString(),
                    'kondisi_kembali'   => $request->kondisi_kembali,
                    'denda'             => $request->denda ?? 0,
                    'petugas_id'        => auth()->id(),
                ]);

                $peminjaman->update(['status' => $statusPeminjamanBaru]);

                $kondisiUnitBaru = $this->kondisiUnit($request->kondisi_kembali);

                foreach ($peminjaman->detailPinjams as $detail) {
                    $unit = $detail->alatUnit;

                    if (!$unit) {
                        throw new Exception("Pengembalian gagal. Unit pada item #{$detail->id} tidak ditemukan.");
                    }

                    // Dipinjam -> tersedia/rusak. Penulisannya idempoten
                    // karena pengembalian hanya bisa dibuat saat status
                    // peminjaman masih 'dipinjam' (dijaga cek di atas).
                    AlatUnit::where('id', $unit->id)->update(['kondisi' => $kondisiUnitBaru]);
                }

                auth()->user()->logAktivitas()->create([
                    'aktivitas' => "Memproses pengembalian peminjaman ID: #{$peminjaman->id} dengan status akhir: {$statusPeminjamanBaru}."
                ]);

                return $pengembalian->load(['peminjaman.user', 'petugas']);
            });

            return response()->json([
                'message'   => 'Proses pengembalian alat berhasil diselesaikan.',
                'data'      => $pengembalian
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(Pengembalian $pengembalian) : JsonResponse
    {
        $user = auth()->user();

        $pengembalian->load(['peminjaman.user', 'peminjaman.detailPinjams.alatUnit.alat', 'petugas']);

        if ($user->role === 'peminjam' && $pengembalian->peminjaman->user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        return response()->json([
            'message'   => 'Detail pengembalian berhasil diambil.',
            'data'      => $pengembalian
        ]);
    }

    public function update(UpdatePengembalianRequest $request, Pengembalian $pengembalian) : JsonResponse
    {
        $pengembalian->update([
            'kondisi_kembali'   => $request->kondisi_kembali,
            'denda'             => $request->denda ?? $pengembalian->denda,
        ]);

        return response()->json([
            'message'   => 'Data pengembalian berhasil diperbarui.',
            'data'      => $pengembalian->load(['peminjaman.user', 'petugas'])
        ]);
    }

    public function destroy(Pengembalian $pengembalian) : JsonResponse
    {
        try {
            DB::transaction(function () use ($pengembalian) {
                $peminjaman = Peminjaman::with('detailPinjams.alatUnit')->lockForUpdate()->findOrFail($pengembalian->peminjaman_id);

                foreach ($peminjaman->detailPinjams as $detail) {
                    $unit = $detail->alatUnit;

                    if (!$unit) {
                        throw new Exception("Gagal membatalkan pengembalian. Unit pada item #{$detail->id} tidak ditemukan.");
                    }

                    // Batal pengembalian = unit balik lagi ke peminjam.
                    // Unit kembali ke status dipinjam.
                    AlatUnit::where('id', $unit->id)->update(['kondisi' => 'dipinjam']);
                }

                $peminjaman->update(['status' => 'dipinjam']);

                auth()->user()->logAktivitas()?->create([
                    'aktivitas' => "Membatalkan pengembalian ID: #{$pengembalian->id}"
                ]);
                $pengembalian->delete();
            });
            return response()->json([
                'message' => 'Data pengembalian berhasil dihapus. Unit alat dan status peminjaman telah dikembalikan ke kondisi semula.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
