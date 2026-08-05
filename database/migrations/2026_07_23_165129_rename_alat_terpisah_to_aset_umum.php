<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename tabel utama: alat_terpisahs -> aset_umums
        Schema::rename('alat_terpisahs', 'aset_umums');

        // 2. Rename kolom foreign key di peminjaman_details
        DB::statement('ALTER TABLE peminjaman_details CHANGE alat_terpisah_id aset_umum_id BIGINT UNSIGNED NOT NULL');

        // 3. Ganti nilai status "diarsipkan" -> "pemeliharaan"
        //    enum diperlebar dulu biar data lama gak ke-truncate pas proses update
        DB::statement("ALTER TABLE aset_umums MODIFY status ENUM('tersedia', 'dipinjam', 'rusak', 'diarsipkan', 'pemeliharaan') DEFAULT 'tersedia'");
        DB::table('aset_umums')->where('status', 'diarsipkan')->update(['status' => 'pemeliharaan']);
        DB::statement("ALTER TABLE aset_umums MODIFY status ENUM('tersedia', 'dipinjam', 'rusak', 'pemeliharaan') DEFAULT 'tersedia'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE aset_umums MODIFY status ENUM('tersedia', 'dipinjam', 'rusak', 'diarsipkan', 'pemeliharaan') DEFAULT 'tersedia'");
        DB::table('aset_umums')->where('status', 'pemeliharaan')->update(['status' => 'diarsipkan']);
        DB::statement("ALTER TABLE aset_umums MODIFY status ENUM('tersedia', 'dipinjam', 'rusak', 'diarsipkan') DEFAULT 'tersedia'");

        DB::statement('ALTER TABLE peminjaman_details CHANGE aset_umum_id alat_terpisah_id BIGINT UNSIGNED NOT NULL');

        Schema::rename('aset_umums', 'alat_terpisahs');
    }
};
