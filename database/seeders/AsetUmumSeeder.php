<?php

namespace Database\Seeders;

use App\Models\AsetUmum;
use Illuminate\Database\Seeder;

class AsetUmumSeeder extends Seeder
{
    public function run(): void
    {
        // kunci per ruangan
        $ruanganPunyaKunci = [
            'Labkom 1', 'Labkom 2', 'Aula', 'Kubika', 'B103', 'LAB ERGONOMI',
            'BLK DKV', 'BLK DKV Ruangan Teori', 'BLK DKV Ruangan Praktik',
        ];

        foreach ($ruanganPunyaKunci as $ruangan) {
            AsetUmum::create([
                'nama_alat'   => 'Kunci ' . $ruangan,
                'kode_aset'   => $ruangan,
                'jumlah_stok' => 1,
                'status'      => 'tersedia',
            ]);
        }

        // alat lain-lain
        $alatLain = [
            ['nama_alat' => 'SoundSystem + MIC', 'jumlah_stok' => 1],
            ['nama_alat' => 'Splitter + HDMI', 'jumlah_stok' => 1],
            ['nama_alat' => 'HDMI', 'jumlah_stok' => 1],
            ['nama_alat' => 'Layar Proyektor', 'jumlah_stok' => 3],
            ['nama_alat' => 'Kursi Rotan', 'jumlah_stok' => 6],
            ['nama_alat' => 'Meja (Terpisah)', 'jumlah_stok' => 1],
        ];

        foreach ($alatLain as $alat) {
            AsetUmum::create([
                'nama_alat'   => $alat['nama_alat'],
                'jumlah_stok' => $alat['jumlah_stok'],
                'status'      => 'tersedia',
            ]);
        }

        // proyektor per unit (nomor + merek masing-masing)
        $proyektor = [
            ['nomor' => '4', 'merek' => 'Epson EB-X500'],
            ['nomor' => '5', 'merek' => 'Epson EB-X500'],
            ['nomor' => '6', 'merek' => 'Epson EB-X500'],
            ['nomor' => '8', 'merek' => 'Epson EB-X500'],
            ['nomor' => '7', 'merek' => 'InFocus'],
            ['nomor' => '9', 'merek' => 'InFocus'],
            ['nomor' => '10', 'merek' => 'EB-X600'],
            ['nomor' => '11', 'merek' => 'EB-X600'],
            ['nomor' => '12', 'merek' => 'EB-X600'],
            ['nomor' => '13', 'merek' => 'EB-X600'],
            ['nomor' => '14', 'merek' => 'EB-X600'],
        ];

        foreach ($proyektor as $unit) {
            AsetUmum::create([
                'nama_alat'   => 'Proyektor',
                'nomor_unit'  => $unit['nomor'],
                'merek'       => $unit['merek'],
                'jumlah_stok' => 1,
                'status'      => 'tersedia',
            ]);
        }
    }
}