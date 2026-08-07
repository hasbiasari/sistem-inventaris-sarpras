<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // "Kunci DKV" lama jadi kunci umum "Kunci BLK DKV"
        DB::table('aset_umums')->where('nama_alat', 'Kunci DKV')->update([
            'nama_alat' => 'Kunci BLK DKV',
            'kode_aset' => 'BLK DKV',
        ]);

        // tambah kunci masing-masing sub-ruangan
        foreach (['Ruangan Teori', 'Ruangan Praktik'] as $subRuangan) {
            $namaAlat = 'Kunci BLK DKV ' . $subRuangan;

            if (! DB::table('aset_umums')->where('nama_alat', $namaAlat)->exists()) {
                DB::table('aset_umums')->insert([
                    'nama_alat' => $namaAlat,
                    'kode_aset' => 'BLK DKV ' . strtoupper($subRuangan),
                    'jumlah_stok' => 1,
                    'status' => 'tersedia',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('aset_umums')->where('nama_alat', 'Kunci BLK DKV Ruangan Teori')->delete();
        DB::table('aset_umums')->where('nama_alat', 'Kunci BLK DKV Ruangan Praktik')->delete();

        DB::table('aset_umums')->where('nama_alat', 'Kunci BLK DKV')->update([
            'nama_alat' => 'Kunci DKV',
            'kode_aset' => 'DKV',
        ]);
    }
};
