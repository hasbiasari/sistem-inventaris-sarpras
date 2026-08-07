<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            // dicatat pas notif "sudah melebihi jam, segera kembalikan" dikirim, biar gak dikirim berulang
            $table->timestamp('notifikasi_terlambat_at')->nullable()->after('waktu_kembali');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropColumn('notifikasi_terlambat_at');
        });
    }
};
