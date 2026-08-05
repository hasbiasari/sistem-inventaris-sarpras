<?php

namespace Database\Seeders;

use App\Models\AsetKelas;
use Illuminate\Database\Seeder;

class AsetKelasSeeder extends Seeder
{
    public function run(): void
    {
        $ruanganKelasBiasa = [
            'A301', 'Labkom 1', 'Labkom 2', 'LAB ERGONOMI',
            'B101', 'B102', 'B201', 'B202', 'B203', 'B301', 'B302',
            'DKV',
        ];

        // kelas biasa: meja 1, papan tulis 1, kursi & kapasitas nanti diisi admin
        foreach ($ruanganKelasBiasa as $nama) {
            AsetKelas::create([
                'nama_ruangan'       => $nama,
                'kapasitas'          => 0,
                'jumlah_kursi'       => 0,
                'jumlah_meja'        => 1,
                'jumlah_papan_tulis' => 1,
            ]);
        }

        // ruangan khusus (bukan kelas kuliah biasa), gak punya meja/papan tulis standar
        $ruanganKhusus = ['B103', 'Kubika', 'Aula'];

        foreach ($ruanganKhusus as $nama) {
            AsetKelas::create([
                'nama_ruangan'       => $nama,
                'kapasitas'          => 0,
                'jumlah_kursi'       => 0,
                'jumlah_meja'        => 0,
                'jumlah_papan_tulis' => 0,
            ]);
        }
    }
}