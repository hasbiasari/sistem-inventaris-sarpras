<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ruangan');
            $table->integer('kapasitas');
            $table->integer('jumlah_kursi')->default(0);
            $table->integer('jumlah_papan_tulis')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_kelas');
    }
};