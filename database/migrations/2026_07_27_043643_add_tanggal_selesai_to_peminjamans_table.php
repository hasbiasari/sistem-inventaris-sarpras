<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            // buat peminjaman organisasi/eksternal yang bisa dipakai beberapa hari (ruangan
            // dipesen dari tanggal_pakai s/d tanggal_selesai). Nullable karena kuliah & peminjaman
            // sehari tetap cukup pakai tanggal_pakai doang.
            $table->date('tanggal_selesai')->nullable()->after('tanggal_pakai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropColumn('tanggal_selesai');
        });
    }
};
