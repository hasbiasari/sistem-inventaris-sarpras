<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProyektorExport implements FromArray, WithStyles, WithColumnWidths
{
    private int $barisHeaderTabel = 5;

    private int $barisHeaderRingkasan = 0;

    public function __construct(
        private Collection $daftarProyektor,
        private string $labelFilter,
        private array $ringkasanStatus
    ) {}

    public function array(): array
    {
        $baris = [
            ['LAPORAN SERVIS PROYEKTOR'],
            ['Dicetak: ' . now()->format('d/m/Y H:i') . ' WIB'],
            ['Filter: ' . $this->labelFilter],
            [],
            ['Unit', 'Merek', 'Total Jam Pakai', 'Batas Jam Maksimal', 'Menuju Batas Servis (%)', 'Estimasi Servis', 'Status'],
        ];

        foreach ($this->daftarProyektor as $p) {
            $estimasi = $p->estimasiMingguMenujuServis();
            $labelEstimasi = match (true) {
                $p->status === 'pemeliharaan' => 'Sedang diservis',
                $estimasi === null => 'Belum cukup data',
                $estimasi <= 0 => 'Sekarang',
                default => "~{$estimasi} minggu lagi",
            };
            $labelStatus = match ($p->status_pemeliharaan) {
                'dalam_pemeliharaan' => 'Dalam Pemeliharaan',
                'perlu_pemeliharaan' => 'Perlu Pemeliharaan',
                'perlu_perhatian' => 'Perlu Perhatian',
                default => 'Normal',
            };

            $baris[] = [
                '#' . $p->nomor_unit,
                $p->merek ?? '-',
                $p->total_jam_pakai,
                $p->batas_jam_maksimal,
                $p->persentase_menuju_servis,
                $labelEstimasi,
                $labelStatus,
            ];
        }

        $baris[] = [];
        $baris[] = ['RINGKASAN'];
        $this->barisHeaderRingkasan = count($baris) + 1;
        $baris[] = ['Normal', 'Perlu Perhatian', 'Perlu Pemeliharaan', 'Dalam Pemeliharaan', 'Total'];
        $baris[] = [
            $this->ringkasanStatus['normal'],
            $this->ringkasanStatus['perlu_perhatian'],
            $this->ringkasanStatus['perlu_pemeliharaan'],
            $this->ringkasanStatus['dalam_pemeliharaan'],
            $this->daftarProyektor->count(),
        ];

        return $baris;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setItalic(true);

        $sheet->getStyle("A{$this->barisHeaderTabel}:G{$this->barisHeaderTabel}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$this->barisHeaderTabel}:G{$this->barisHeaderTabel}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F6B4C');

        if ($this->barisHeaderRingkasan > 0) {
            $sheet->getStyle("A{$this->barisHeaderRingkasan}:E{$this->barisHeaderRingkasan}")->getFont()->setBold(true);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 16,
            'C' => 16,
            'D' => 18,
            'E' => 22,
            'F' => 20,
            'G' => 20,
        ];
    }
}
