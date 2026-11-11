<?php

namespace App\Exports;

use App\Models\Peminjaman;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export laporan peminjaman aktif ke Excel (.xlsx).
 */
class LaporanPeminjamanAktifExport implements
    FromView,
    ShouldAutoSize,
    WithColumnWidths,
    WithStyles,
    WithProperties
{
    use Exportable;

    /**
     * @param  \Illuminate\Support\Collection<int, Peminjaman>  $peminjamans
     * @param  string|null  $tanggalMulai  Filter tanggal mulai (Y-m-d).
     * @param  string|null  $tanggalSelesai  Filter tanggal selesai (Y-m-d).
     * @param  string  $pemroses  Nama pemroses untuk metadata dokumen.
     */
    public function __construct(
        protected $peminjamans,
        protected ?string $tanggalMulai = null,
        protected ?string $tanggalSelesai = null,
        protected string $pemroses = 'Admin',
    ) {
    }

    public function properties(): array
    {
        return [
            'creator' => 'Sistem Peminjaman Alat',
            'lastModifiedBy' => $this->pemroses,
            'title' => 'Laporan Barang Sedang Dipinjam',
            'subject' => 'Laporan Barang Sedang Dipinjam',
            'description' => 'Diekspor dari Sistem Peminjaman Alat',
            'category' => 'Laporan',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,    // No
            'B' => 22,   // Peminjam
            'C' => 40,   // Alat
            'D' => 14,   // Tgl Pinjam
            'E' => 16,   // Rencana Kembali
            'F' => 16,   // Status
            'G' => 30,   // Petugas Penyetuju
            'H' => 20,   // Unit Dikembalikan
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Judul dokumen
            1 => ['font' => ['bold' => true, 'size' => 14]],
            // Baris header tabel (A4:H4)
            'A4' => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'color' => ['argb' => 'FF333333'],
                ],
                'alignment' => ['horizontal' => 'center'],
            ],
            4 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']]],
        ];
    }

    public function view(): View
    {
        return view('exports.laporan-peminjaman-aktif', [
            'peminjamans' => $this->peminjamans,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai,
            'totalPeminjaman' => $this->peminjamans->count(),
            'totalUnit' => $this->peminjamans->sum(function($peminjaman) {
                return $peminjaman->detailPinjams->count();
            }),
            'totalTelat' => $this->peminjamans->where('status', 'telat')->count(),
        ]);
    }
}