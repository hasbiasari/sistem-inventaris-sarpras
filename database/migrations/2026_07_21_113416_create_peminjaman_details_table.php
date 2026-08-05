<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_details', function (Blueprint $table) {
            $table->id();

            // induknya, 1 peminjaman bisa punya banyak detail barang
            $table->foreignId('peminjaman_id')->constrained('peminjamans')->cascadeOnDelete();

            // barang yang dipinjam, ambil dari tabel alat_terpisahs yang udah ada
            $table->foreignId('alat_terpisah_id')->constrained('alat_terpisahs')->cascadeOnDelete();

            // jumlah unit yang dipinjam buat barang ini
            $table->integer('jumlah')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_details');
    }
};