<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\DetailPinjam;
use App\Notifications\PengembalianDisetujuiNotification;
use App\Notifications\PengembalianDitolakNotification;
use App\Notifications\PengembalianSelesaiNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // =========================================================
    // DASHBOARD
    // =========================================================

    /**
     * Menampilkan dashboard Admin dan log aktivitas.
     */
    public function index()
{
    $jumlahAlat = Alat::count();
    $jumlahUser = User::count();
    $jumlahKategori = Kategori::count();
    $jumlahPeminjaman = Peminjaman::count();
    $jumlahPengembalian = Pengembalian::count();

    $logs = LogAktivitas::with('user')
        ->latest()
        ->take(10)
        ->get();

    return view('admin.dashboard', compact(
        'jumlahAlat',
        'jumlahUser',
        'jumlahKategori',
        'jumlahPeminjaman',
        'jumlahPengembalian',
        'logs'
    ));
}


    // =========================================================
    // CRUD ALAT
    // =========================================================

    /**
     * Menampilkan daftar alat.
     */
    public function indexAlat(Request $request)
{
    $search = $request->input('search');
    $kategoriFilter = $request->input('kategori');
    $kondisi = $request->input('kondisi');

    // Ambil semua kategori untuk dropdown filter
    $kategori = Kategori::orderBy('nama_kategori')->get();

    $alats = Alat::with('kategori')

        // =========================
        // SEARCH
        // =========================
        ->when($search !== null && $search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('status_kondisi', 'like', "%{$search}%")
                    ->orWhereHas('kategori', function ($kategoriQuery) use ($search) {
                        $kategoriQuery->where(
                            'nama_kategori',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        })

        // =========================
        // FILTER KATEGORI
        // =========================
        ->when($kategoriFilter !== null && $kategoriFilter !== '', function ($query) use ($kategoriFilter) {
            $query->where('kategori_id', $kategoriFilter);
        })

        // =========================
// FILTER KONDISI
// =========================
->when($kondisi !== null && $kondisi !== '', function ($query) use ($kondisi) {
    if ($kondisi === 'Baik') {
        $query->where('stok_baik', '>', 0);
    } elseif ($kondisi === 'Rusak') {
        $query->where('stok_rusak', '>', 0);
    } elseif ($kondisi === 'Rusak Parah') {
        $query->where('stok_rusak_parah', '>', 0);
    }
})

        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view(
        'admin.alat.index',
        compact(
            'alats',
            'search',
            'kategori',
            'kategoriFilter',
            'kondisi'
        )
    );
}


    /**
     * Menampilkan form tambah alat.
     */
    public function createAlat()
    {
        $kategoris = Kategori::all();

        return view(
            'admin.alat.create',
            compact('kategoris')
        );
    }


    /**
     * Menyimpan alat baru.
     */
    public function storeAlat(Request $request)
    {
        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'status_kondisi' => 'required|in:Baik,Rusak,Rusak Parah',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only([
            'nama_alat',
            'kategori_id',
            'stok',
            'status_kondisi',
            'deskripsi',
        ]);

        // Set stok kondisi berdasarkan status_kondisi
        if ($data['status_kondisi'] === 'Baik') {
            $data['stok_baik'] = $data['stok'];
            $data['stok_rusak'] = 0;
            $data['stok_rusak_parah'] = 0;
        } elseif ($data['status_kondisi'] === 'Rusak') {
            $data['stok_baik'] = 0;
            $data['stok_rusak'] = $data['stok'];
            $data['stok_rusak_parah'] = 0;
        } elseif ($data['status_kondisi'] === 'Rusak Parah') {
            $data['stok_baik'] = 0;
            $data['stok_rusak'] = 0;
            $data['stok_rusak_parah'] = $data['stok'];
        }

        // Upload gambar
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');

            $filename = time() . '_' . $file->getClientOriginalName();

            $file->move(
                public_path('storage/alat'),
                $filename
            );

            $data['gambar'] = 'storage/alat/' . $filename;
        }

        Alat::create($data);

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'success',
                'Data alat berhasil ditambahkan.'
            );
    }


    /**
     * Menampilkan form edit alat.
     */
    public function editAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $kategoris = Kategori::all();

        return view(
            'admin.alat.edit',
            compact('alat', 'kategoris')
        );
    }


    /**
     * Memperbarui data alat.
     */
    public function updateAlat(Request $request, $id)
{
    $alat = Alat::findOrFail($id);

    $request->validate([
        'nama_alat' => 'required|string|max:255',
        'kategori_id' => 'required|exists:kategori,id',
        'stok' => 'required|integer|min:0',
        'status_kondisi' => 'required|in:Baik,Rusak,Rusak Parah',
        'deskripsi' => 'nullable|string',
        'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $stokBaru = (int) $request->stok;

    // Jumlah alat yang sedang berada dalam kondisi rusak
    $totalRusak =
        (int) $alat->stok_rusak +
        (int) $alat->stok_rusak_parah;

    /*
     * Stok baru tidak boleh lebih kecil dari jumlah
     * alat yang sudah tercatat sebagai rusak.
     */
    if ($stokBaru < $totalRusak) {
        return back()
            ->withInput()
            ->with(
                'error',
                'Stok tidak boleh lebih kecil dari jumlah alat yang rusak.'
            );
    }

    /*
     * Stok baik dihitung sebagai sisa dari stok total
     * setelah dikurangi alat rusak.
     */
    $stokBaikBaru = $stokBaru - $totalRusak;

    $data = [
        'nama_alat' => $request->nama_alat,
        'kategori_id' => $request->kategori_id,
        'stok' => $stokBaru,
        'stok_baik' => $stokBaikBaru,
        'stok_rusak' => $alat->stok_rusak,
        'stok_rusak_parah' => $alat->stok_rusak_parah,
        'status_kondisi' => $request->status_kondisi,
        'deskripsi' => $request->deskripsi,
    ];

    // Upload gambar baru
    if ($request->hasFile('gambar')) {

        // Hapus gambar lama
        if (
            $alat->gambar &&
            file_exists(public_path($alat->gambar))
        ) {
            unlink(public_path($alat->gambar));
        }

        $file = $request->file('gambar');

        $filename =
            time() . '_' . $file->getClientOriginalName();

        $file->move(
            public_path('storage/alat'),
            $filename
        );

        $data['gambar'] = 'storage/alat/' . $filename;
    }

    $alat->update($data);

    return redirect()
        ->route('admin.alat.index')
        ->with(
            'success',
            'Data alat berhasil diperbarui.'
        );
}

/**
 * Memperbaiki alat yang rusak menjadi kondisi baik.
 */
public function perbaikiAlat(Request $request, $id)
{
    $alat = Alat::findOrFail($id);

    $request->validate([
        'kondisi' => 'required|in:Rusak,Rusak Parah',
        'jumlah' => 'required|integer|min:1',
    ]);

    $jumlah = $request->jumlah;

    DB::beginTransaction();

    try {

        // Perbaikan dari Rusak Ringan
        if ($request->kondisi === 'Rusak') {

            if ($alat->stok_rusak < $jumlah) {
                throw new \Exception(
                    'Jumlah alat rusak ringan tidak mencukupi.'
                );
            }

            $alat->decrement('stok_rusak', $jumlah);
            $alat->increment('stok_baik', $jumlah);

        }

        // Perbaikan dari Rusak Parah
        elseif ($request->kondisi === 'Rusak Parah') {

            if ($alat->stok_rusak_parah < $jumlah) {
                throw new \Exception(
                    'Jumlah alat rusak parah tidak mencukupi.'
                );
            }

            $alat->decrement('stok_rusak_parah', $jumlah);
            $alat->increment('stok_baik', $jumlah);
        }

        DB::commit();

        // Catat aktivitas Admin
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' =>
                "Memperbaiki {$jumlah} pcs alat '{$alat->nama_alat}' " .
                "dari kondisi {$request->kondisi} menjadi Baik.",
        ]);

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'success',
                "{$jumlah} pcs {$alat->nama_alat} berhasil diperbaiki dan dikembalikan ke kondisi Baik."
            );

    } catch (\Exception $e) {

        DB::rollBack();

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'error',
                'Perbaikan alat gagal: ' . $e->getMessage()
            );
    }
}

    /**
     * Mengubah kondisi sebagian alat (per pcs).
     *
     * Memindahkan jumlah tertentu dari satu kondisi ke kondisi lain.
     * Contoh: 2 pcs dari Baik -> Rusak Ringan.
     */
    public function ubahKondisiAlat(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'kondisi_asal' => 'required|in:Baik,Rusak,Rusak Parah',
            'kondisi_tujuan' => 'required|in:Baik,Rusak,Rusak Parah|different:kondisi_asal',
            'jumlah' => 'required|integer|min:1',
        ]);

        $kolom = [
            'Baik' => 'stok_baik',
            'Rusak' => 'stok_rusak',
            'Rusak Parah' => 'stok_rusak_parah',
        ];

        $asal = $kolom[$request->kondisi_asal];
        $tujuan = $kolom[$request->kondisi_tujuan];
        $jumlah = (int) $request->jumlah;

        DB::beginTransaction();

        try {
            if ($alat->{$asal} < $jumlah) {
                throw new \Exception(
                    "Jumlah alat dengan kondisi {$request->kondisi_asal} tidak mencukupi (tersedia {$alat->{$asal}} pcs)."
                );
            }

            $alat->decrement($asal, $jumlah);
            $alat->increment($tujuan, $jumlah);

            // Update status_kondisi utama sesuai kondisi mayoritas
            $alat->refresh();

            $kondisiMayoritas = collect($kolom)
                ->map(fn ($kol, $nama) => ['nama' => $nama, 'jumlah' => $alat->{$kol}])
                ->sortByDesc('jumlah')
                ->first()['nama'];

            $alat->status_kondisi = $kondisiMayoritas;
            $alat->saveQuietly();

            DB::commit();

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' =>
                    "Mengubah {$jumlah} pcs alat '{$alat->nama_alat}' dari kondisi {$request->kondisi_asal} menjadi {$request->kondisi_tujuan}.",
            ]);

            return redirect()
                ->route('admin.alat.index')
                ->with(
                    'success',
                    "{$jumlah} pcs {$alat->nama_alat} berhasil diubah dari kondisi {$request->kondisi_asal} menjadi {$request->kondisi_tujuan}."
                );

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                'Gagal mengubah kondisi alat: ' . $e->getMessage()
            );
        }
    }

    /**
     * Menghapus alat.
     */
    public function destroyAlat($id)
    {
        $alat = Alat::findOrFail($id);

        // Hapus gambar fisik
        if (
            $alat->gambar &&
            file_exists(public_path($alat->gambar))
        ) {
            unlink(public_path($alat->gambar));
        }

        $alat->delete();

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'success',
                'Data alat berhasil dihapus.'
            );
    }


    // =========================================================
    // PENGEMBALIAN
    // =========================================================

    /**
     * Menampilkan daftar pengembalian.
     */
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');
        $kondisi = $request->input('kondisi');
        $statusRequest = $request->input('status_request');
        $tanggalDari = $request->input('tanggal_dari');
        $tanggalSampai = $request->input('tanggal_sampai');
        $jenisKelamin = $request->input('jenis_kelamin');

        $pengembalians = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alat',
            'petugas'
        ])
            ->when($search, function ($query, $search) {
                $query->whereHas(
                    'peminjaman.user',
                    function ($q) use ($search) {
                        $q->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    }
                );
            })

            ->when($kondisi, function ($query, $kondisi) {
    $query->where('kondisi_kembali', $kondisi);
})

->when($statusRequest, function ($query, $statusRequest) {
    $query->where('status_request', $statusRequest);
})

->when($tanggalDari, function ($query, $tanggalDari) {
    $query->whereDate('tgl_kembali', '>=', $tanggalDari);
})
->when($tanggalSampai, function ($query, $tanggalSampai) {
    $query->whereDate('tgl_kembali', '<=', $tanggalSampai);
})

->when($jenisKelamin, function ($query, $jenisKelamin) {
    $query->whereHas('peminjaman.user', function ($user) use ($jenisKelamin) {
        $user->where('jenis_kelamin', $jenisKelamin);
    });
})

            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.pengembalian.index',
            compact('pengembalians', 'search', 'kondisi', 'statusRequest', 'tanggalDari', 'tanggalSampai', 'jenisKelamin')
        );
    }


    /**
     * Method lama untuk halaman create pengembalian.
     *
     * Tidak digunakan dalam alur baru.
     */
    public function createPengembalian()
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Pengembalian sekarang diajukan oleh Petugas dan harus disetujui Admin.'
            );
    }

    /**
     * Method lama untuk menyimpan pengembalian langsung.
     *
     * Tidak digunakan agar Admin tidak dapat melewati
     * proses pengajuan Petugas.
     */
    public function storePengembalian(Request $request)
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Pengembalian harus diajukan oleh Petugas terlebih dahulu.'
            );
    }

    /**
     * Menampilkan detail pengajuan pengembalian
     * untuk diperiksa Admin.
     */
    public function editPengembalian($id)
    {
        $pengembalian = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alat',
            'petugas'
        ])->findOrFail($id);

        // Hanya pengajuan yang masih menunggu
        // yang dapat diperiksa.
        if ($pengembalian->status_request !== 'menunggu') {
            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'error',
                    'Pengajuan pengembalian ini sudah diproses.'
                );
        }

        return view(
            'admin.pengembalian.edit',
            compact('pengembalian')
        );
    }


    /**
     * Menyetujui pengajuan pengembalian.
     *
     * Petugas menentukan:
     * - kondisi alat
     * - denda kerusakan
     *
     * Admin menentukan validasi akhir.
     *
     * Denda keterlambatan dihitung otomatis
     * sebesar Rp1.000 per hari.
     */
    public function setujuiPengembalian($id)
    {
        DB::beginTransaction();

        try {
           $pengembalian = Pengembalian::with([
            'petugas',
            'peminjaman.user',
            'peminjaman.detailPinjams.alat'
        ])->findOrFail($id);

            if ($pengembalian->status_request !== 'menunggu') {
                throw new \Exception(
                    'Pengajuan pengembalian ini sudah diproses.'
                );
            }

            $peminjaman = $pengembalian->peminjaman;

            if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
                throw new \Exception(
                    'Peminjaman ini tidak dapat diproses sebagai pengembalian.'
                );
            }

            $tglRencana = Carbon::parse($peminjaman->tgl_kembali_plan);
            $tglKembali = Carbon::parse($pengembalian->tgl_kembali);

            if ($tglKembali->greaterThan(Carbon::today())) {
                throw new \Exception(
                    'Tanggal pengembalian tidak boleh melebihi hari ini.'
                );
            }

            $hariTerlambat = 0;

            if ($tglKembali->greaterThan($tglRencana)) {
                $hariTerlambat = $tglRencana->diffInDays($tglKembali);
            }

            // Denda keterlambatan berdasarkan tarif pada config/denda.php
                $dendaKeterlambatan =
                $hariTerlambat * config('denda.keterlambatan_per_hari');

            // Denda kerusakan sudah ditentukan oleh Petugas.
            $dendaKerusakan = (int) ($pengembalian->denda_kerusakan ?? 0);

            // Simpan masing-masing denda ke kolomnya sendiri.
            $pengembalian->update([
                'denda' => $dendaKeterlambatan,
                'status_request' => 'disetujui',
            ]);

            // Stok dikembalikan setelah Admin menyetujui.
// Stok kondisi juga disesuaikan dengan kondisi alat saat dikembalikan.
foreach ($peminjaman->detailPinjams as $detail) {
    $alat = $detail->alat;
    $jumlah = $detail->jumlah;

    // Stok total selalu bertambah karena alat sudah kembali
    $alat->increment('stok', $jumlah);

    if ($pengembalian->kondisi_kembali === 'Baik') {

        // Alat kembali dalam kondisi baik
        $alat->increment('stok_baik', $jumlah);

    } elseif ($pengembalian->kondisi_kembali === 'Rusak Ringan') {

        // Stok_baik sudah di-decrement saat peminjaman disetujui.
        // Alat rusak ringan dipindahkan ke stok_rusak.
        $alat->increment('stok_rusak', $jumlah);

    } elseif ($pengembalian->kondisi_kembali === 'Rusak Berat') {

        // Stok_baik sudah di-decrement saat peminjaman disetujui.
        // Alat rusak berat dipindahkan ke stok_rusak_parah.
        $alat->increment('stok_rusak_parah', $jumlah);
    }
}

            $peminjaman->update([
                'status' => 'dikembalikan',
            ]);

            DB::commit();

            // Kirim notifikasi kepada Petugas yang mengajukan
if ($pengembalian->petugas) {
    $pengembalian->petugas->notify(
        new PengembalianDisetujuiNotification($pengembalian)
    );
}

// Kirim notifikasi kepada Peminjam
if ($pengembalian->peminjaman->user) {
    $pengembalian->peminjaman->user->notify(
        new PengembalianSelesaiNotification($pengembalian)
    );
}

            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'success',
                    'Pengembalian berhasil disetujui, stok alat dikembalikan, dan total denda telah dihitung.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                'Pengembalian gagal disetujui: ' . $e->getMessage()
            );
        }
    }

    public function formPengembalianAdmin($id)
{
    $peminjaman = Peminjaman::with([
        'user',
        'detailPinjams.alat',
        'pengembalian'
    ])->findOrFail($id);

    if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'error',
                'Peminjaman ini tidak dapat dibuatkan pengembalian.'
            );
    }

    if (
        $peminjaman->pengembalian &&
        $peminjaman->pengembalian->status_request === 'menunggu'
    ) {
        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'error',
                'Pengajuan pengembalian ini masih menunggu proses.'
            );
    }

    if (
        $peminjaman->pengembalian &&
        $peminjaman->pengembalian->status_request === 'disetujui'
    ) {
        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'error',
                'Pengembalian ini sudah disetujui.'
            );
    }

    return view(
        'admin.pengembalian.create-from-peminjaman',
        compact('peminjaman')
    );
}


    /**
 * Admin membuat pengajuan pengembalian secara langsung.
 *
 * Digunakan sebagai jalur alternatif apabila Petugas
 * tidak dapat melakukan proses pengembalian.
 */
public function ajukanPengembalianAdmin(Request $request, $peminjamanId)
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

        // Jika masih menunggu Admin
        if (
            $pengembalian &&
            $pengembalian->status_request === 'menunggu'
        ) {
            throw new \Exception(
                'Pengajuan pengembalian ini masih menunggu proses.'
            );
        }

        // Jika sudah disetujui
        if (
            $pengembalian &&
            $pengembalian->status_request === 'disetujui'
        ) {
            throw new \Exception(
                'Pengembalian untuk peminjaman ini sudah disetujui.'
            );
        }

        /*
         * Jika sebelumnya pernah ditolak,
         * gunakan data pengembalian yang sama.
         */
        if ($pengembalian) {

            $pengembalian->update([
                'tgl_kembali' => now()->toDateString(),
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda' => 0,
                'denda_kerusakan' => $request->denda_kerusakan,
                'status_request' => 'menunggu',
            ]);
} else {

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

        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'success',
                'Pengajuan pengembalian berhasil dibuat dan menunggu proses persetujuan.'
            );

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with(
            'error',
            'Pengajuan pengembalian gagal: ' . $e->getMessage()
        );
    }
}


    /**
     * Menolak pengajuan pengembalian.
     *
     * Data tidak dihapus agar Petugas dapat
     * memperbaiki dan mengajukan kembali.
     */
    public function tolakPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::with('petugas')->findOrFail($id);

            if ($pengembalian->status_request !== 'menunggu') {
                throw new \Exception(
                    'Pengajuan pengembalian ini sudah diproses.'
                );
            }

            $pengembalian->update([
                'status_request' => 'ditolak',
                'denda' => 0,
            ]);

            DB::commit();

// Kirim notifikasi kepada Petugas yang mengajukan
if ($pengembalian->petugas) {
    $pengembalian->petugas->notify(
        new PengembalianDitolakNotification($pengembalian)
    );
}

return redirect()
    ->route('admin.pengembalian.index')
    ->with(
        'success',
        'Pengajuan pengembalian ditolak dan dapat diperbaiki oleh Petugas.'
    );

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                'Pengajuan gagal ditolak: ' . $e->getMessage()
            );
        }
    }

    /**
     * Method lama untuk update pengembalian.
     *
     * Tidak digunakan dalam alur approval baru.
     */
    public function updatePengembalian(Request $request, $id)
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Data pengembalian diproses melalui pemeriksaan dan persetujuan Admin.'
            );
    }

    /**
     * Menghapus data pengembalian.
     *
     * Jika sudah disetujui, stok akan dikurangi kembali
     * karena sebelumnya sudah ditambahkan saat persetujuan.
     */
    public function destroyPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::with([
                'peminjaman.detailPinjams.alat'
            ])->findOrFail($id);

            $peminjaman = $pengembalian->peminjaman;

            // Hanya pengembalian yang sudah disetujui yang pernah
            // menambahkan stok, sehingga hanya itu yang perlu dibatalkan.
            if ($pengembalian->status_request === 'disetujui') {
                foreach ($peminjaman->detailPinjams as $detail) {
                    $alat = $detail->alat;

                    if ($alat->stok < $detail->jumlah) {
                        throw new \Exception(
                            "Stok alat '{$alat->nama_alat}' tidak mencukupi untuk menghapus data pengembalian."
                        );
                    }

                    $alat->decrement('stok', $detail->jumlah);

                    // Sesuaikan stok kondisi sesuai kondisi pengembalian.
                    // Jika alat sudah diperbaiki, stok kondisi bisa kurang
                    // dari jumlah yang dikembalikan, jadi cek dulu.
                    if ($pengembalian->kondisi_kembali === 'Baik') {
                        if ($alat->stok_baik < $detail->jumlah) {
                            throw new \Exception(
                                "Stok baik alat '{$alat->nama_alat}' tidak mencukupi untuk menghapus data pengembalian."
                            );
                        }
                        $alat->decrement('stok_baik', $detail->jumlah);
                    } elseif ($pengembalian->kondisi_kembali === 'Rusak Ringan') {
                        if ($alat->stok_rusak < $detail->jumlah) {
                            throw new \Exception(
                                "Stok rusak alat '{$alat->nama_alat}' tidak mencukupi untuk menghapus data pengembalian."
                            );
                        }
                        $alat->decrement('stok_rusak', $detail->jumlah);
                    } elseif ($pengembalian->kondisi_kembali === 'Rusak Berat') {
                        if ($alat->stok_rusak_parah < $detail->jumlah) {
                            throw new \Exception(
                                "Stok rusak parah alat '{$alat->nama_alat}' tidak mencukupi untuk menghapus data pengembalian."
                            );
                        }
                        $alat->decrement('stok_rusak_parah', $detail->jumlah);
                    }
                }

                $peminjaman->update([
                    'status' => 'dipinjam',
                ]);
            }

            // Jika masih menunggu atau ditolak, stok tidak diubah.
            $pengembalian->delete();

            DB::commit();

            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'success',
                    'Data pengembalian berhasil dihapus.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /**
     * Method lama untuk memproses pengembalian langsung.
     *
     * Tidak digunakan agar pengembalian tidak dapat
     * melewati persetujuan Admin.
     */
    public function prosesPengembalian($id)
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Pengembalian harus melalui pengajuan Petugas dan persetujuan Admin.'
            );
    }

    // =========================================================
    // CRUD USER
    // =========================================================

    /**
     * Menampilkan daftar user.
     */
    public function indexUser(Request $request)
{
    $search = $request->input('search');
    $jenisKelamin = $request->input('jenis_kelamin');
    $role = $request->input('role');

    $users = User::when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('role', 'like', "%{$search}%");
        });
    })
        ->when($jenisKelamin, function ($query, $jenisKelamin) {
            $query->where('jenis_kelamin', $jenisKelamin);
        })
        ->when($role, function ($query, $role) {
            $query->where('role', $role);
        })
        
        ->orderByRaw("CASE
    WHEN role = 'admin' THEN 1
    WHEN role = 'petugas' THEN 2
    WHEN role = 'peminjam' THEN 3
    ELSE 4
END")
->latest()
->paginate(10)
->withQueryString();

    return view(
        'admin.user.index',
        compact('users', 'search', 'jenisKelamin', 'role')
    );
}


    /**
     * Menampilkan form tambah user.
     */
    public function createUser()
    {
        return view('admin.user.create');
    }


    /**
     * Menyimpan user baru.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|string|min:8',
    'role' => 'required|in:admin,petugas,peminjam',
    'no_hp' => 'nullable|string|max:20',
    'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
    'foto_profile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'no_hp' => $request->no_hp,
            'jenis_kelamin' => $request->jenis_kelamin,
        ];

        // Upload foto profil
        if ($request->hasFile('foto_profile')) {

            $file = $request->file('foto_profile');

            $filename =
                time() . '_' .
                $file->getClientOriginalName();

            $file->move(
                public_path('storage/profile'),
                $filename
            );

            $data['foto_profile'] =
                'storage/profile/' . $filename;
        }

        User::create($data);

        return redirect()
            ->route('admin.user.index')
            ->with(
                'success',
                'User berhasil ditambahkan.'
            );
    }


    /**
     * Menampilkan form edit user.
     */
    public function editUser($id)
    {
        $user = User::findOrFail($id);

        return view(
            'admin.user.edit',
            compact('user')
        );
    }


    /**
     * Memperbarui data user.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' =>
                'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:admin,petugas,peminjam',
            'no_hp' => 'nullable|string|max:20',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'password' => 'nullable|string|min:8',
            'foto_profile' =>
                'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'no_hp' => $request->no_hp,
            'jenis_kelamin' => $request->jenis_kelamin,
        ];

        // Update password jika diisi
        if ($request->filled('password')) {
            $data['password'] =
                Hash::make($request->password);
        }

        // Upload foto baru
        if ($request->hasFile('foto_profile')) {

            if (
                $user->foto_profile &&
                file_exists(
                    public_path($user->foto_profile)
                )
            ) {
                unlink(
                    public_path($user->foto_profile)
                );
            }

            $file = $request->file('foto_profile');

            $filename =
                time() . '_' .
                $file->getClientOriginalName();

            $file->move(
                public_path('storage/profile'),
                $filename
            );

            $data['foto_profile'] =
                'storage/profile/' . $filename;
        }

        $user->update($data);

        return redirect()
            ->route('admin.user.index')
            ->with(
                'success',
                'Data user berhasil diperbarui.'
            );
    }


    /**
     * Menghapus user.
     */
    public function destroyUser($id)
{
    $user = User::findOrFail($id);

    // Admin tidak boleh menghapus akun sendiri
    if ($user->id === auth()->id()) {
        return redirect()
            ->route('admin.user.index')
            ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
    }

    // User yang masih memiliki peminjaman aktif tidak boleh dihapus
    // karena data peminjaman & stok alat akan ikut terhapus (cascade).
    $peminjamanAktif = Peminjaman::where('user_id', $user->id)
        ->whereIn('status', ['diajukan', 'dipinjam', 'telat'])
        ->exists();

    if ($peminjamanAktif) {
        return redirect()
            ->route('admin.user.index')
            ->with(
                'error',
                'User ini masih memiliki peminjaman aktif. Selesaikan atau hapus data peminjamannya terlebih dahulu.'
            );
    }

    // Hapus foto profil
    if (
        $user->foto_profile &&
        file_exists(
            public_path($user->foto_profile)
        )
    ) {
        unlink(
            public_path($user->foto_profile)
        );
    }

    $user->delete();

    return redirect()
        ->route('admin.user.index')
        ->with(
            'success',
            'User berhasil dihapus.'
        );
}


    // =========================================================
    // CRUD KATEGORI
    // =========================================================

    /**
     * Menampilkan daftar kategori.
     */
    public function indexKategori(Request $request)
{
    $search = $request->input('search');

    $kategoris = Kategori::withCount('alat')
        ->when(
            $search,
            function ($query, $search) {
                $query->where(
                    'nama_kategori',
                    'like',
                    "%{$search}%"
                );
            }
        )
        ->latest()
        ->paginate(5)
        ->withQueryString();

    return view(
        'admin.kategori.index',
        compact('kategoris', 'search')
    );
}

/**show Kategori */
public function showKategori($id)
{
    $kategori = Kategori::with('alat')
        ->withCount('alat')
        ->findOrFail($id);

    $kategori->jumlah_baik = $kategori->alat->sum('stok_baik');
    $kategori->jumlah_rusak = $kategori->alat->sum('stok_rusak');
    $kategori->jumlah_rusak_parah = $kategori->alat->sum('stok_rusak_parah');

    return view(
        'admin.kategori.show',
        compact('kategori')
    );
}


    /**
     * Menampilkan form tambah kategori.
     */
    public function createKategori()
    {
        return view('admin.kategori.create');
    }


    /**
     * Menyimpan kategori baru.
     */
    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' =>
                'required|string|max:255|unique:kategori,nama_kategori',
        ]);

        Kategori::create([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'success',
                'Kategori berhasil ditambahkan.'
            );
    }


    /**
     * Menampilkan form edit kategori.
     */
    public function editKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        return view(
            'admin.kategori.edit',
            compact('kategori')
        );
    }


    /**
     * Memperbarui kategori.
     */
    public function updateKategori(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'nama_kategori' =>
                'required|string|max:255|unique:kategori,nama_kategori,' . $id,
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'success',
                'Kategori berhasil diperbarui.'
            );
    }


    /**
     * Menghapus kategori.
     */
    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        $kategori->delete();

        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'success',
                'Kategori berhasil dihapus.'
            );
    }


    // =========================================================
    // CRUD PEMINJAMAN
    // =========================================================

    /**
     * Menampilkan daftar peminjaman.
     */
    public function indexPeminjaman(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Cek otomatis peminjaman yang terlambat
    |--------------------------------------------------------------------------
    | Jika status masih dipinjam dan tanggal rencana kembali
    | sudah lewat dari hari ini, ubah status menjadi telat.
    */
    Peminjaman::where('status', 'dipinjam')
        ->whereDate('tgl_kembali_plan', '<', now()->toDateString())
        ->update([
            'status' => 'telat'
        ]);

    $search = $request->input('search');
    $status = $request->input('status');
    $jenisKelamin = $request->input('jenis_kelamin');
    $tanggalDari = $request->input('tanggal_dari');
    $tanggalSampai = $request->input('tanggal_sampai');

    $peminjamans = Peminjaman::with([
        'user',
        'detailPinjams.alat'
    ])
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'status',
                    'like',
                    "%{$search}%"
                )
                ->orWhereHas('user', function ($user) use ($search) {
                    $user->where(
                        'name',
                        'like',
                        "%{$search}%"
                    );
                });
            });
        })
        ->when($status, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($jenisKelamin, function ($query, $jenisKelamin) {
            $query->whereHas('user', function ($user) use ($jenisKelamin) {
                $user->where(
                    'jenis_kelamin',
                    $jenisKelamin
                );
            });
        })
        ->when($tanggalDari, function ($query, $tanggalDari) {
            $query->whereDate(
                'tgl_pinjam',
                '>=',
                $tanggalDari
            );
        })
        ->when($tanggalSampai, function ($query, $tanggalSampai) {
            $query->whereDate(
                'tgl_pinjam',
                '<=',
                $tanggalSampai
            );
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view(
        'admin.peminjaman.index',
        compact(
            'peminjamans',
            'search',
            'status',
            'jenisKelamin',
            'tanggalDari',
            'tanggalSampai'
        )
    );
}


    /**
     * Menampilkan form tambah peminjaman.
     */
    public function createPeminjaman()
    {
        $users = User::where(
            'role',
            'peminjam'
        )->get();

        $alats = Alat::where(
            'stok_baik',
            '>',
            0
        )->get();

        return view(
            'admin.peminjaman.create',
            compact('users', 'alats')
        );
    }


    /**
     * Menyimpan peminjaman baru.
     */
    public function storePeminjaman(Request $request)
    {
        $request->validate([
            'user_id' =>
                'required|exists:users,id',

            'tgl_pinjam' =>
                'required|date',

            'tgl_kembali_plan' =>
                'required|date|after_or_equal:tgl_pinjam',

            'alat_id' =>
                'required|array',

            'alat_id.*' =>
                'exists:alat,id',

            'jumlah' =>
                'required|array',

            'jumlah.*' =>
                'integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $peminjaman = Peminjaman::create([
                'user_id' => $request->user_id,
                'tgl_pinjam' => $request->tgl_pinjam,
                'tgl_kembali_plan' =>
                    $request->tgl_kembali_plan,
                'status' => 'diajukan',
            ]);


            foreach (
                $request->alat_id
                as $index => $alatId
            ) {

                $jumlahPinjam =
                    $request->jumlah[$index];

                $alat = Alat::findOrFail($alatId);

                // Validasi stok baik
                if (
                    $alat->stok_baik <
                    $jumlahPinjam
                ) {
                    throw new \Exception(
                        "Stok alat '{$alat->nama_alat}' dalam kondisi baik tidak mencukupi."
                    );
                }

                DetailPinjam::create([
                    'peminjaman_id' =>
                        $peminjaman->id,

                    'alat_id' =>
                        $alatId,

                    'jumlah' =>
                        $jumlahPinjam,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.peminjaman.index')
                ->with(
                    'success',
                    'Data peminjaman berhasil diajukan.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }


    /**
     * Mengubah status peminjaman.
     *
     * Pengembalian TIDAK dapat dilakukan langsung
     * dari sini. Pengembalian harus melalui:
     *
     * Petugas → Pengajuan → Admin → Persetujuan.
     */
    public function updateStatusPeminjaman(
        Request $request,
        $id
    ) {
        $peminjaman = Peminjaman::with([
            'detailPinjams.alat',
            'pengembalian'
        ])->findOrFail($id);

        $request->validate([
            'status' =>
                'required|in:diajukan,dipinjam,telat,dikembalikan',
        ]);

        DB::beginTransaction();

        try {

            $statusLama = $peminjaman->status;
            $statusBaru = $request->status;


            // =====================================================
            // DIAJUKAN → DIPINJAM
            // =====================================================

            if (
                $statusLama === 'diajukan' &&
                $statusBaru === 'dipinjam'
            ) {

                foreach (
                    $peminjaman->detailPinjams
                    as $detail
                ) {

                    $alat = $detail->alat;

                    if (
                        $alat->stok_baik <
                        $detail->jumlah
                    ) {
                        throw new \Exception(
                            "Stok alat '{$alat->nama_alat}' dalam kondisi baik tidak mencukupi untuk dipinjam."
                        );
                    }

                    $alat->decrement(
                        'stok',
                        $detail->jumlah
                    );

                    // Kurangi juga stok kondisi baik
                    $alat->decrement(
                        'stok_baik',
                        $detail->jumlah
                    );
                }
            }


            // =====================================================
            // DIPINJAM / TELAT → DIKEMBALIKAN
            // =====================================================

            if (
                $statusBaru === 'dikembalikan' &&
                $statusLama !== 'dikembalikan'
            ) {
                throw new \Exception(
                    'Status dikembalikan hanya dapat diberikan setelah pengajuan pengembalian disetujui Admin.'
                );
            }


            // Update status biasa
            $peminjaman->update([
                'status' => $statusBaru
            ]);

            DB::commit();

            return redirect()
                ->route('admin.peminjaman.index')
                ->with(
                    'success',
                    'Status peminjaman berhasil diperbarui.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }


    /**
     * Menghapus data peminjaman.
     */
    public function destroyPeminjaman($id)
    {
        $peminjaman = Peminjaman::with([
            'detailPinjams.alat'
        ])->findOrFail($id);

        // Jika sedang dipinjam, kembalikan stok
        if (in_array($peminjaman->status, ['dipinjam', 'telat'])) {

            foreach (
                $peminjaman->detailPinjams
                as $detail
            ) {

                $detail->alat->increment(
                    'stok',
                    $detail->jumlah
                );

                // Kembalikan juga stok_baik
                $detail->alat->increment(
                    'stok_baik',
                    $detail->jumlah
                );
            }
        }

        $peminjaman->delete();

        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'success',
                'Data peminjaman berhasil dihapus.'
            );
    }


    // =========================================================
    // SEARCH USER
    // =========================================================

    /**
     * Pencarian user untuk form peminjaman.
     */
    public function searchUser(Request $request)
    {
        $keyword = $request->input('search');

        if (strlen($keyword) < 3) {
            return response()->json([]);
        }

        $users = User::where(
            'role',
            'peminjam'
        )
            ->where(function ($query) use ($keyword) {
                $query->where(
                    'name',
                    'like',
                    "%{$keyword}%"
                )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$keyword}%"
                    );
            })
            ->limit(10)
            ->get([
                'id',
                'name',
                'email'
            ]);

        return response()->json($users);
    }


    // =========================================================
    // SEARCH ALAT
    // =========================================================

    /**
     * Pencarian alat untuk form peminjaman.
     */
    public function searchAlat(Request $request)
    {
        $keyword = $request->input('search');

        if (strlen($keyword) < 3) {
            return response()->json([]);
        }

        $alats = Alat::where(
            'stok_baik',
            '>',
            0
        )
            ->where(
                'nama_alat',
                'like',
                "%{$keyword}%"
            )
            ->limit(10)
            ->get([
                'id',
                'nama_alat',
                'stok_baik'
            ]);

        return response()->json($alats);
    }

    //=====================================
    //LOG AKTIVITAS//
    //=====================================
    public function indexLogAktivitas(Request $request)
{
    $logs = LogAktivitas::with('user')
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view('admin.log_aktivitas.index', compact('logs'));
}
}
