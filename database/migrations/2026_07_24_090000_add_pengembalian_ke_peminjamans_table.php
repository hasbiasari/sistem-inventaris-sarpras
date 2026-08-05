<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->timestamp('waktu_kembali')->nullable()->after('status');
        });

        Schema::create('bukti_pengembalians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjamans')->cascadeOnDelete();
            $table->string('foto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti_pengembalians');

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropColumn('waktu_kembali');
        });
    }
};
