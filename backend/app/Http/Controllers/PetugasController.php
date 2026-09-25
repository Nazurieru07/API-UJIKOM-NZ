<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Notifications\PeminjamanDisetujuiNotification;
use App\Models\User;
use App\Notifications\PengembalianDiajukanNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\PermintaanEditPeminjaman;
use App\Models\DetailPermintaanEdit;
use App\Models\DetailPinjam;
use App\Notifications\PermintaanEditDiprosesNotification;
use Illuminate\Support\Facades\DB;

class PetugasController extends Controller
{
    // =========================================================
    // PEMINJAMAN
    // =========================================================

    /**
     * Menampilkan daftar pengajuan peminjaman.
     *
     * Daftar difilter berdasarkan: search (nama peminjam),
     * jenis_kelamin, dan rentang tanggal tgl_pinjam.
     * Filter pakai when() supaya query tanpa filter tetap berjalan.
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
     *
     * Titik kritis alur: stok_baik dan stok_total baru dikurangi di
     * method ini, BUKAN saat peminjaman diajukan. Kalau pengajuan
     * ditolak, stok tidak perlu dikembalikan lagi.
     *
     * lockForUpdate + transaksi: cegah 2 petugas approve bersamaan
     * yang bisa membuat stok minus.
     */
    public function setujuiPeminjaman($id)
    {
        DB::beginTransaction();

        try {
            $peminjaman = Peminjaman::with('detailPinjams')
                ->lockForUpdate()
                ->findOrFail($id);

            // Pastikan pengajuan memang belum diproses
            if ($peminjaman->status !== 'diajukan') {
                throw new \Exception(
                    'Pengajuan peminjaman ini sudah diproses.'
                );
            }

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

    /*
    | decrement() = operasi atomik di database
    | (UPDATE ... SET stok = stok - N), lebih aman terhadap race
    | condition daripada baca stok -> kurang -> save.
    | Kedua stok (total + kondisi baik) berkurang jumlah yang sama.
    */
    $alat->decrement('stok', $detail->jumlah);
    $alat->decrement('stok_baik', $detail->jumlah);
}

           DB::commit();

// Notifikasi masuk antrian (ShouldQueue). Jika queue worker tidak
// jalan, notifikasi numpuk di tabel jobs dan tidak pernah dikirim.
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
     * Pengajuan yang masih berstatus "diajukan" dihapus total,
     * karena alat belum pernah dikeluarkan (stok belum berkurang).
     * Menghapus memungkinkan peminjam mengajukan ulang dengan benar.
     *
     * Catatan: delete() memicu PeminjamanObserver::deleted, jadi
     * penolakan tetap tercatat di log aktivitas.
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
     *
     * Hanya peminjaman berstatus dipinjam/telat. Ditampilkan juga
     * pengembalian yang status_request-nya ditolak, supaya petugas
     * bisa mengajukan ulang peminjaman yang gagal sebelumnya.
     */
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');
        $kategoriId = $request->input('kategori_id');
        $tanggalDari = $request->input('tanggal_dari');
        $tanggalSampai = $request->input('tanggal_sampai');

        $peminjamans = Peminjaman::with([
            'user',
            'detailPinjams.alat.kategori',
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
            ->when($kategoriId, function ($query, $kategoriId) {
                $query->whereHas('detailPinjams.alat', function ($q) use ($kategoriId) {
                    $q->where('kategori_id', $kategoriId);
                });
            })
            ->when($tanggalDari, function ($query, $tanggalDari) {
                $query->whereDate('tgl_pinjam', '>=', $tanggalDari);
            })
            ->when($tanggalSampai, function ($query, $tanggalSampai) {
                $query->whereDate('tgl_pinjam', '<=', $tanggalSampai);
            })
            ->latest('tgl_pinjam')
            ->paginate(10)
            ->withQueryString();

        // Daftar kategori untuk dropdown filter
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view(
            'petugas.pengembalian.index',
            compact('peminjamans', 'search', 'kategoriId', 'tanggalDari', 'tanggalSampai', 'kategoris')
        );
    }


    /**
     * Mengajukan pengembalian kepada Admin.
     *
     * Petugas menentukan:
     * - kondisi alat (Baik / Rusak Ringan / Rusak Berat)
     * - denda kerusakan (angka bebas, bisa Rp0)
     *
     * Kenapa denda KETERLAMBATAN tidak diisi petugas:
     * denda ini dihitung sistem dari selisih tgl_kembali_plan vs
     * tgl_kembali aktual, agar tidak bisa dimanipulasi. Dihitung saat
     * Admin menyetujui (AdminController::setujuiPengembalian), bukan sini.
     *
     * Relasi peminjaman -> pengembalian itu 1:1 (unique), jadi jika
     * pengembalian sebelumnya DITOLAK, data lama dipakai ulang (update)
     * bukan dibuat baru, agar tidak melanggar constraint unik.
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

            // Hanya peminjaman yang masih berjalan yang bisa
            // diajukan pengembaliannya. Yang sudah dikembalikan ditolak.
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
     * Hanya pengembalian status_request=disetujui yang masuk ke laporan
     * resmi. Yang masih menunggu/ditolak TIDAK masuk, karena transaksinya
     * belum final (alat belum resmi kembali).
     *
     * petugas_id NULL -> pengembalian ditangani Admin langsung, tampil
     * sebagai "Admin" di laporan (bukan kosong/error).
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
            ->paginate(10)
            ->withQueryString();

        return view('petugas.laporan.index', [
            'pengembalians' => $pengembalians,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ]);
    }


    /**
     * Mencetak laporan pengembalian dalam bentuk PDF.
     *
     * Hanya pengembalian yang sudah disetujui Admin yang dicetak.
     *
     * Kenapa query diulang (tidak pakai method terpisah): laporan web
     * dan PDF butuh format data berbeda (paginate untuk web, semua
     * baris untuk PDF), jadi tidak bisa dibagikan langsung.
     * after_or_equal:tanggal_mulai mencegah rentang tanggal terbalik.
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

    /**
     * Daftar permintaan edit peminjaman (status menunggu).
     */
    public function indexEditPeminjaman()
    {
        $permintaanEdits = PermintaanEditPeminjaman::with(['peminjaman.user', 'user', 'detailEdits.alat'])
            ->where('status', 'menunggu')
            ->latest()
            ->paginate(10);

        return view('petugas.edit-peminjaman.index', compact('permintaanEdits'));
    }

    /**
     * Setujui permintaan edit: apply perubahan ke peminjaman + stok alat.
     */
    public function setujuiEditPeminjaman($id)
    {
        $permintaanEdit = PermintaanEditPeminjaman::with(['peminjaman.detailPinjams', 'detailEdits.alat'])
            ->lockForUpdate()
            ->findOrFail($id);

        if ($permintaanEdit->status !== 'menunggu') {
            return redirect()->back()->with('error', 'Permintaan ini sudah diproses.');
        }

        DB::beginTransaction();

        try {
            $peminjaman = $permintaanEdit->peminjaman;

            foreach ($permintaanEdit->detailEdits as $detail) {
                if ($detail->aksi === 'tambah') {
                    $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                    if ($alat->stok_baik < $detail->jumlah) {
                        throw new \Exception('Stok ' . $alat->nama_alat . ' tidak cukup.');
                    }
                    $alat->decrement('stok_baik', $detail->jumlah);
                    $alat->decrement('stok', $detail->jumlah);
                    DetailPinjam::create([
                        'peminjaman_id' => $peminjaman->id,
                        'alat_id' => $detail->alat_id,
                        'jumlah' => $detail->jumlah,
                    ]);
                } elseif ($detail->aksi === 'hapus') {
                    $dp = DetailPinjam::where('peminjaman_id', $peminjaman->id)
                        ->where('alat_id', $detail->alat_id)
                        ->first();
                    if (!$dp || $dp->jumlah < $detail->jumlah) {
                        throw new \Exception('Jumlah hapus tidak valid.');
                    }
                    if ($dp->jumlah === $detail->jumlah) {
                        $dp->delete();
                    } else {
                        $dp->decrement('jumlah', $detail->jumlah);
                    }
                    $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                    $alat->increment('stok_baik', $detail->jumlah);
                    $alat->increment('stok', $detail->jumlah);
                }
            }

            if ($permintaanEdit->tgl_kembali_plan_baru) {
                $peminjaman->tgl_kembali_plan = $permintaanEdit->tgl_kembali_plan_baru;
                $peminjaman->save();
            }

            $permintaanEdit->update([
                'status' => 'disetujui',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            DB::commit();

            $permintaanEdit->user->notify(new PermintaanEditDiprosesNotification($permintaanEdit));

            return redirect()->route('petugas.edit-peminjaman.index')->with('success', 'Permintaan edit disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * Tolak permintaan edit.
     */
    public function tolakEditPeminjaman(Request $request, $id)
    {
        $request->validate(['catatan_penolakan' => 'nullable|string|max:500']);
        $permintaanEdit = PermintaanEditPeminjaman::findOrFail($id);

        if ($permintaanEdit->status !== 'menunggu') {
            return redirect()->back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $permintaanEdit->update([
            'status' => 'ditolak',
            'catatan_penolakan' => $request->catatan_penolakan,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        $permintaanEdit->user->notify(new PermintaanEditDiprosesNotification($permintaanEdit));

        return redirect()->route('petugas.edit-peminjaman.index')->with('success', 'Permintaan edit ditolak.');
    }
}
