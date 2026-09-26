<?php

namespace App\Exports;

use App\Models\Pengembalian;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export laporan pengembalian ke Excel (.xlsx).
 *
 * FromView dipakai agar struktur baris identik dengan laporan web/PDF dan
 * tidak ada logika baris yang ditulis dua kali.
 */
class LaporanPengembalianExport implements
    FromView,
    ShouldAutoSize,
    WithColumnWidths,
    WithStyles,
    WithProperties
{
    // Exportable menyediakan method download()/store() untuk streamed export.
    use Exportable;

    /**
     * @param  \Illuminate\Support\Collection<int, Pengembalian>  $pengembalians
     * @param  string|null  $tanggalMulai  Filter tanggal mulai (Y-m-d).
     * @param  string|null  $tanggalSelesai  Filter tanggal selesai (Y-m-d).
     * @param  string  $pemroses  Nama pemroses untuk metadata dokumen.
     */
    public function __construct(
        protected $pengembalians,
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
            'title' => 'Laporan Pengembalian Alat',
            'subject' => 'Laporan Pengembalian Alat',
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
            'E' => 14,   // Tgl Kembali
            'F' => 16,   // Kondisi
            'G' => 14,   // Denda
            'H' => 18,   // Denda Kerusakan
            'I' => 20,   // Petugas
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Judul dokumen
            1 => ['font' => ['bold' => true, 'size' => 14]],
            // Baris header tabel (A4:I4)
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
        return view('exports.laporan-pengembalian', [
            'pengembalians' => $this->pengembalians,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai,
            'totalDenda' => (int) $this->pengembalians->sum('denda'),
            'totalDendaKerusakan' => (int) $this->pengembalians->sum('denda_kerusakan'),
        ]);
    }
}
