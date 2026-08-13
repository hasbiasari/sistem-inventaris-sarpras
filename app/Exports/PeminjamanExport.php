<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PeminjamanExport implements FromArray, WithStyles, WithColumnWidths
{
    private int $barisHeaderTabel = 5;

    private int $barisHeaderRingkasan = 0;

    public function __construct(
        private Collection $daftarPeminjaman,
        private string $labelFilter,
        private array $ringkasanStatus
    ) {}

    public function array(): array
    {
        $baris = [
            ['LAPORAN PEMINJAMAN'],
            ['Dicetak: ' . now()->format('d/m/Y H:i') . ' WIB'],
            ['Filter: ' . $this->labelFilter],
            [],
            ['No', 'Nama Peminjam', 'Kategori', 'Kelas/Ormawa/Instansi', 'Ruangan', 'Barang', 'Tanggal', 'Status'],
        ];

        foreach ($this->daftarPeminjaman as $i => $p) {
            $kategoriTampil = $p->jenis_peminjam === 'eksternal' ? 'Eksternal' : ucfirst($p->kategori ?? '-');
            $keteranganTambahan = $p->kelas ?? $p->ormawa ?? $p->keterangan_eksternal ?? '-';
            $labelStatus = $p->status === 'selesai' ? 'Dikembalikan' : ucfirst($p->status);
            $barang = $p->details->isNotEmpty()
                ? $p->details->map(fn ($d) => ($d->asetUmum->nama_alat ?? '-') . ' x' . $d->jumlah)->join(', ')
                : '-';

            $baris[] = [
                $i + 1,
                $p->nama_peminjam,
                $kategoriTampil,
                $keteranganTambahan,
                $p->asetKelas->nama_ruangan ?? '-',
                $barang,
                $p->created_at->format('d/m/Y'),
                $labelStatus,
            ];
        }

        $baris[] = [];
        $baris[] = ['RINGKASAN'];
        $this->barisHeaderRingkasan = count($baris) + 1;
        $baris[] = ['Menunggu', 'Disetujui', 'Ditolak', 'Dibatalkan', 'Selesai', 'Total'];
        $baris[] = [
            $this->ringkasanStatus['menunggu'],
            $this->ringkasanStatus['disetujui'],
            $this->ringkasanStatus['ditolak'],
            $this->ringkasanStatus['dibatalkan'],
            $this->ringkasanStatus['selesai'],
            $this->daftarPeminjaman->count(),
        ];

        return $baris;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setItalic(true);

        $sheet->getStyle("A{$this->barisHeaderTabel}:H{$this->barisHeaderTabel}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$this->barisHeaderTabel}:H{$this->barisHeaderTabel}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F6B4C');

        if ($this->barisHeaderRingkasan > 0) {
            $sheet->getStyle("A{$this->barisHeaderRingkasan}:F{$this->barisHeaderRingkasan}")->getFont()->setBold(true);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 24,
            'C' => 12,
            'D' => 22,
            'E' => 16,
            'F' => 32,
            'G' => 12,
            'H' => 14,
        ];
    }
}
