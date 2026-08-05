<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            // ruangan yang mau dipakai bareng peminjaman ini (opsional, gak semua peminjaman butuh ruangan)
            $table->foreignId('aset_kelas_id')->nullable()->after('mahasiswa_id')->constrained('aset_kelas')->nullOnDelete();

            // tanggal & jam pemakaian ruangan, biar kelihatan status "lagi dipakai" secara real-time
            $table->date('tanggal_pakai')->nullable()->after('kategori');
            $table->time('jam_mulai')->nullable()->after('tanggal_pakai');
            $table->time('jam_selesai')->nullable()->after('jam_mulai');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['aset_kelas_id']);
            $table->dropColumn(['aset_kelas_id', 'tanggal_pakai', 'jam_mulai', 'jam_selesai']);
        });
    }
};
