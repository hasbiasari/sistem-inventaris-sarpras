<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pengelompokan = [
            'Gedung A' => ['SISPROD', 'LABKOM 1', 'LABKOM 2', 'A301', 'LAB ERGONOMI'],
            'Gedung B' => ['B101', 'B102', 'B103', 'B201', 'B202', 'B203', 'B301', 'B302'],
            'Gedung C' => ['PERPUSTAKAAN', 'AULA'],
            'Gedung D' => ['BLK DKV RUANGAN TEORI', 'BLK DKV RUANGAN PRAKTIK'],
        ];

        foreach ($pengelompokan as $gedung => $daftarRuangan) {
            DB::table('aset_kelas')->whereIn('nama_ruangan', $daftarRuangan)->update([
                'gedung' => $gedung,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('aset_kelas')->update(['gedung' => null]);
    }
};
