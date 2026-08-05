<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            // kelas/rombongan belajar mahasiswa yang minjem, contoh: "IF-VIII-A" - diisi manual tiap peminjaman
            $table->string('kelas')->nullable()->after('mahasiswa_id');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });
    }
};
