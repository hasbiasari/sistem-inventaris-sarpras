<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // semua nama ruangan yang sudah ada -> uppercase, biar konsisten
        foreach (DB::table('aset_kelas')->get(['id', 'nama_ruangan']) as $ruangan) {
            DB::table('aset_kelas')->where('id', $ruangan->id)->update([
                'nama_ruangan' => mb_strtoupper($ruangan->nama_ruangan),
            ]);
        }

        // DKV pecah jadi 2 sub-ruangan: baris lama dipakai buat "Teori", baris baru buat "Praktik"
        DB::table('aset_kelas')->where('nama_ruangan', 'DKV')->update([
            'nama_ruangan' => 'BLK DKV RUANGAN TEORI',
        ]);

        if (! DB::table('aset_kelas')->where('nama_ruangan', 'BLK DKV RUANGAN PRAKTIK')->exists()) {
            $referensi = DB::table('aset_kelas')->where('nama_ruangan', 'BLK DKV RUANGAN TEORI')->first();

            DB::table('aset_kelas')->insert([
                'nama_ruangan' => 'BLK DKV RUANGAN PRAKTIK',
                'kapasitas' => $referensi->kapasitas ?? 0,
                'jumlah_kursi' => $referensi->jumlah_kursi ?? 0,
                'jumlah_papan_tulis' => $referensi->jumlah_papan_tulis ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (['SISPROD', 'PERPUSTAKAAN'] as $nama) {
            if (! DB::table('aset_kelas')->where('nama_ruangan', $nama)->exists()) {
                DB::table('aset_kelas')->insert([
                    'nama_ruangan' => $nama,
                    'kapasitas' => 0,
                    'jumlah_kursi' => 0,
                    'jumlah_papan_tulis' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('aset_kelas')->where('nama_ruangan', 'BLK DKV RUANGAN PRAKTIK')->delete();
        DB::table('aset_kelas')->where('nama_ruangan', 'SISPROD')->delete();
        DB::table('aset_kelas')->where('nama_ruangan', 'PERPUSTAKAAN')->delete();

        DB::table('aset_kelas')->where('nama_ruangan', 'BLK DKV RUANGAN TEORI')->update([
            'nama_ruangan' => 'DKV',
        ]);
    }
};
