<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Notifications\PeminjamanDisetujuiNotification;
use App\Models\User;
use App\Notifications\PengembalianDiajukanNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetugasController extends Controller
{
    // =========================================================
    // PEMINJAMAN
    // =========================================================

    /**
     * Menampilkan daftar pengajuan peminjaman.
     */
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');
        $jenisKelamin = $request->input('jenis_kelamin');
        $tanggalDari = $request->input('tanggal_dari');
        $tanggalSampai = $request->input('tanggal_sampai');
        $alatId = $request->input('alat_id');
        $status = $request->input('status');

            $peminjamans = Peminjaman::with([
                'user',
                'detailPinjams.alat.kategori'
            ])
            ->when($search, function ($query, $search) {
    $query->whereHas('user', function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%");
            });
        })
        ->when($jenisKelamin, function ($query, $jenisKelamin) {
            $query->whereHas('user', function ($q) use ($jenisKelamin) {
                $q->where('jenis_kelamin', $jenisKelamin);
            });
        })
        ->when($tanggalDari, function ($query, $tanggalDari) {
            $query->whereDate('tgl_pinjam', '>=', $tanggalDari);
        })
        ->when($tanggalSampai, function ($query, $tanggalSampai) {
            $query->whereDate('tgl_pinjam', '<=', $tanggalSampai);
        })
            ->when($alatId, function ($query, $alatId) {
        $query->whereHas('detailPinjams', function ($q) use ($alatId) {
            $q->where('alat_id', $alatId);
        });
    })
    ->when($status, function ($query, $status) {
    $query->where('status', $status);
})


           ->orderByRaw("
    CASE status
        WHEN 'diajukan' THEN 1
        WHEN 'telat' THEN 2
        WHEN 'dipinjam' THEN 3
        WHEN 'dikembalikan' THEN 4
        ELSE 5
    END
")
->latest('created_at')
->paginate(10)
->withQueryString();

            $daftarAlat = Alat::select('alat.id', 'alat.nama_alat')
            ->join('detail_pinjam', 'alat.id', '=', 'detail_pinjam.alat_id')
            ->distinct()
            ->orderBy('nama_alat')
            ->get();

        return view(
    'petugas.peminjaman.index',
    compact(
    'peminjamans',
    'search',
    'jenisKelamin',
    'tanggalDari',
    'tanggalSampai',
    'alatId',
    'status',
    'daftarAlat'
    )
    );
    }


    /**
     * Menyetujui peminjaman dan mengurangi stok alat.
     */
    public function setujuiPeminjaman($id)
    {
        DB::beginTransaction();

        try {
            $peminjaman = Peminjaman::with('detailPinjams')
                ->findOrFail($id);

            $peminjaman->update([
                'status' => 'dipinjam'
            ]);

            // Kurangi stok alat dari kondisi Baik
foreach ($peminjaman->detailPinjams as $detail) {
    $alat = Alat::findOrFail($detail->alat_id);

    // Pastikan stok total cukup
    if ($alat->stok < $detail->jumlah) {
        throw new \Exception(
            "Stok alat '{$alat->nama_alat}' tidak mencukupi."
        );
    }

    // Pastikan stok dalam kondisi baik cukup untuk dipinjam
    if ($alat->stok_baik < $detail->jumlah) {
        throw new \Exception(
            "Stok alat '{$alat->nama_alat}' dalam kondisi baik tidak mencukupi."
        );
    }

    // Kurangi stok total
    $alat->decrement('stok', $detail->jumlah);

    // Kurangi stok kondisi baik
    $alat->decrement('stok_baik', $detail->jumlah);
}

           DB::commit();

// Kirim notifikasi kepada Peminjam
$peminjaman->user->notify(
    new PeminjamanDisetujuiNotification($peminjaman)
);

return redirect()
    ->back()
    ->with(
        'success',
        'Peminjaman disetujui dan stok alat dikurangi.'
    );

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Terjadi kesalahan: ' . $e->getMessage()
                );
        }
    }


    /**
     * Menolak peminjaman.
     *
     * Pengajuan yang masih berstatus "diajukan"
     * akan dihapus agar peminjam dapat mengajukan kembali.
     */
    public function tolakPeminjaman($id)
    {
        try {
            $peminjaman = Peminjaman::findOrFail($id);

            // Pastikan status masih diajukan
            if ($peminjaman->status !== 'diajukan') {
                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'Status peminjaman sudah berubah.'
                    );
            }

            $peminjaman->delete();

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Pengajuan peminjaman berhasil ditolak.'
                );

        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Terjadi kesalahan: ' . $e->getMessage()
                );
        }
    }


    // =========================================================
    // PENGEMBALIAN
    // =========================================================

    /**
     * Menampilkan daftar peminjaman yang dapat diajukan
     * sebagai pengembalian.
     */
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');

        $peminjamans = Peminjaman::with([
            'user',
            'detailPinjams.alat',
            'pengembalian'
        ])
            ->whereIn('status', ['dipinjam', 'telat'])
            ->where(function ($query) {
                $query->whereDoesntHave('pengembalian')
                    ->orWhereHas('pengembalian', function ($q) {
                        $q->where('status_request', 'ditolak');
                    });
            })
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest('tgl_pinjam')
            ->get();

        return view(
            'petugas.pengembalian.index',
            compact('peminjamans', 'search')
        );
    }


    /**
     * Mengajukan pengembalian kepada Admin.
     *
     * Petugas menentukan:
     * - kondisi alat
     * - denda kerusakan
     *
     * Denda keterlambatan dihitung oleh sistem
     * saat Admin menyetujui pengembalian.
     */
    public function ajukanPengembalian(Request $request, $peminjamanId)
    {
        $request->validate([
            'kondisi_kembali' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'denda_kerusakan' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $peminjaman = Peminjaman::with('pengembalian')
                ->findOrFail($peminjamanId);

            // Pastikan peminjaman masih aktif
            if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
                throw new \Exception(
                    'Peminjaman ini tidak dapat diajukan sebagai pengembalian.'
                );
            }

            $pengembalian = $peminjaman->pengembalian;

            // Pengajuan masih menunggu Admin
            if (
                $pengembalian &&
                $pengembalian->status_request === 'menunggu'
            ) {
                throw new \Exception(
                    'Pengajuan pengembalian ini masih menunggu persetujuan Admin.'
                );
            }

            // Pengembalian sudah disetujui
            if (
                $pengembalian &&
                $pengembalian->status_request === 'disetujui'
            ) {
                throw new \Exception(
                    'Pengembalian untuk peminjaman ini sudah disetujui Admin.'
                );
            }

            /*
             * Jika pengajuan sebelumnya ditolak,
             * gunakan kembali data pengembalian tersebut.
             */
            if ($pengembalian) {

                $pengembalian->update([
                    'tgl_kembali' => now()->toDateString(),
                    'kondisi_kembali' => $request->kondisi_kembali,
                    'denda' => 0,
                    'denda_kerusakan' => $request->denda_kerusakan,
                    'petugas_id' => auth()->id(),
                    'status_request' => 'menunggu',
                ]);

            } else {

                // Jika belum pernah ada pengajuan
                $pengembalian = Pengembalian::create([
                    'peminjaman_id' => $peminjaman->id,
                    'tgl_kembali' => now()->toDateString(),
                    'kondisi_kembali' => $request->kondisi_kembali,
                    'denda' => 0,
                    'denda_kerusakan' => $request->denda_kerusakan,
                    'petugas_id' => auth()->id(),
                    'status_request' => 'menunggu',
                ]);
            }

            DB::commit();

// Kirim notifikasi ke semua Admin
User::where('role', 'admin')
    ->get()
    ->each(function ($admin) use ($pengembalian) {
        $admin->notify(
            new PengembalianDiajukanNotification($pengembalian)
        );
    });

return redirect()
    ->back()
                ->with(
                    'success',
                    'Pengajuan pengembalian berhasil dikirim ke Admin untuk diperiksa.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Terjadi kesalahan: ' . $e->getMessage()
                );
        }
    }


    // =========================================================
    // LAPORAN
    // =========================================================

    /**
     * Menampilkan laporan pengembalian.
     *
     * Hanya pengembalian yang sudah disetujui Admin
     * yang masuk ke laporan resmi.
     */
    public function indexLaporan(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alat',
            'petugas'
        ])
            ->where('status_request', 'disetujui');

        // Filter tanggal mulai
        if (!empty($tanggalMulai)) {
            $query->whereDate(
                'tgl_kembali',
                '>=',
                $tanggalMulai
            );
        }

        // Filter tanggal selesai
        if (!empty($tanggalSelesai)) {
            $query->whereDate(
                'tgl_kembali',
                '<=',
                $tanggalSelesai
            );
        }

        $pengembalians = $query
            ->orderBy('tgl_kembali', 'desc')
            ->get();

        return view('petugas.laporan.index', [
            'pengembalians' => $pengembalians,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ]);
    }


    /**
     * Mencetak laporan pengembalian dalam bentuk PDF.
     *
     * Hanya pengembalian yang sudah disetujui Admin
     * yang dicetak.
     */
    public function cetakLaporan(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alat',
            'petugas'
        ])
            ->where('status_request', 'disetujui');

        // Filter tanggal mulai
        if ($tanggalMulai) {
            $query->whereDate(
                'tgl_kembali',
                '>=',
                $tanggalMulai
            );
        }

        // Filter tanggal selesai
        if ($tanggalSelesai) {
            $query->whereDate(
                'tgl_kembali',
                '<=',
                $tanggalSelesai
            );
        }

        $pengembalians = $query
            ->orderBy('tgl_kembali', 'desc')
            ->get();

        $pdf = Pdf::loadView('petugas.laporan.pdf', [
            'pengembalians' => $pengembalians,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream(
            'laporan-pengembalian-alat.pdf'
        );
    }
}
