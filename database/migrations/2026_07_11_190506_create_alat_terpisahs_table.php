<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alat_terpisahs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_alat');
            $table->string('kode_aset')->nullable(); // khusus buat kunci, contoh: B202
            $table->integer('jumlah_stok')->default(0);
            $table->enum('status', ['tersedia', 'dipinjam', 'rusak', 'diarsipkan'])->default('tersedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alat_terpisahs');
    }
};