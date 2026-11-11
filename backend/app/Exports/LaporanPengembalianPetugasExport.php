<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export khusus laporan pengembalian Petugas.
 * Data hanya berisi unit yang diproses Petugas yang login.
 */
class LaporanPengembalianPetugasExport implements
    FromView,
    ShouldAutoSize,
    WithColumnWidths,
    WithStyles,
    WithProperties
{
    use Exportable;

    public function __construct(
        protected $pengembalians,
        protected ?string $tanggalMulai = null,
        protected ?string $tanggalSelesai = null,
        protected string $pemroses = 'Petugas',
    ) {
    }

    public function properties(): array
    {
        return [
            'creator' => 'Sistem Peminjaman Alat',
            'lastModifiedBy' => $this->pemroses,
            'title' => 'Laporan Pengembalian Alat Petugas',
            'subject' => 'Laporan Pengembalian Alat',
            'description' => 'Laporan unit yang diproses Petugas',
            'category' => 'Laporan',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 22,
            'C' => 40,
            'D' => 14,
            'E' => 14,
            'F' => 20,
            'G' => 14,
            'H' => 18,
            'I' => 20,
        ];
    }

public function styles(Worksheet $sheet): array
{
    return [
        // Styling judul laporan.
        1 => [
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
        ],

        // Styling seluruh header tabel.
        'A7:I7' => [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'color' => ['argb' => 'FF333333'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
                'wrapText' => true,
            ],
        ],
    ];
}
    public function view(): View
    {
        $totalDenda = $this->pengembalians->sum(function ($pengembalian) {
            return (int) $pengembalian->petugas_id === (int) auth()->id()
                ? (int) $pengembalian->denda
                : 0;
        });

        // Total denda kerusakan hanya dari unit yang diproses Petugas ini.
$totalDendaKerusakan = $this->pengembalians->sum(function ($pengembalian) {
    return $pengembalian->detailPengembalians->sum('denda_kerusakan');
});

        return view('exports.laporan-pengembalian-petugas', [
            'pengembalians' => $this->pengembalians,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai,
            'totalDenda' => (int) $totalDenda,
            'totalDendaKerusakan' => (int) $totalDendaKerusakan,
        ]);
    }
}
