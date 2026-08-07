<?php

namespace Database\Seeders;

use App\Models\AsetKelas;
use Illuminate\Database\Seeder;

class AsetKelasSeeder extends Seeder
{
    public function run(): void
    {
        $gedungRuangan = [
            'Gedung A' => ['SISPROD', 'LABKOM 1', 'LABKOM 2', 'A301', 'LAB ERGONOMI'],
            'Gedung B' => ['B101', 'B102', 'B103', 'B201', 'B202', 'B203', 'B301', 'B302'],
            'Gedung C' => ['PERPUSTAKAAN', 'AULA'],
            'Gedung D' => ['BLK DKV RUANGAN TEORI', 'BLK DKV RUANGAN PRAKTIK'],
        ];

        $cariGedung = function (string $nama) use ($gedungRuangan) {
            foreach ($gedungRuangan as $gedung => $daftarRuangan) {
                if (in_array($nama, $daftarRuangan, true)) {
                    return $gedung;
                }
            }

            return null;
        };

        $ruanganKelasBiasa = [
            'A301', 'LABKOM 1', 'LABKOM 2', 'LAB ERGONOMI', 'SISPROD',
            'B101', 'B102', 'B201', 'B202', 'B203', 'B301', 'B302',
            'BLK DKV RUANGAN TEORI', 'BLK DKV RUANGAN PRAKTIK',
        ];

        // kelas biasa: meja 1, papan tulis 1, kursi & kapasitas nanti diisi admin
        foreach ($ruanganKelasBiasa as $nama) {
            AsetKelas::create([
                'nama_ruangan'       => $nama,
                'gedung'             => $cariGedung($nama),
                'kapasitas'          => 0,
                'jumlah_kursi'       => 0,
                'jumlah_meja'        => 1,
                'jumlah_papan_tulis' => 1,
            ]);
        }

        // ruangan khusus (bukan kelas kuliah biasa), gak punya meja/papan tulis standar
        $ruanganKhusus = ['B103', 'KUBIKA', 'AULA', 'PERPUSTAKAAN'];

        foreach ($ruanganKhusus as $nama) {
            AsetKelas::create([
                'nama_ruangan'       => $nama,
                'gedung'             => $cariGedung($nama),
                'kapasitas'          => 0,
                'jumlah_kursi'       => 0,
                'jumlah_meja'        => 0,
                'jumlah_papan_tulis' => 0,
            ]);
        }
    }
}
