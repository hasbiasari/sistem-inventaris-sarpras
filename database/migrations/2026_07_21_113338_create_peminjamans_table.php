<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjamans', function (Blueprint $table) {
            $table->id();

            // buat bedain ini peminjaman dari mahasiswa atau booking manual admin buat pihak luar
            $table->enum('jenis_peminjam', ['mahasiswa', 'eksternal'])->default('mahasiswa');

            // relasi ke mahasiswa (nullable, soalnya kalau eksternal ga ada mahasiswa-nya)
            $table->foreignId('mahasiswa_id')->nullable()->constrained('mahasiswas')->nullOnDelete();

            // khusus buat booking manual pihak eksternal (requirement 7)
            $table->string('nama_eksternal')->nullable(); // nama orang/pihak luar
            $table->string('keterangan_eksternal')->nullable(); // misal nama instansi/acara

            // kategori peminjaman, cuma keisi kalau jenis_peminjam = mahasiswa
            $table->enum('kategori', ['kuliah', 'organisasi'])->nullable();

            // status keseluruhan pengajuan
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'dibatalkan', 'selesai'])
                  ->default('menunggu');

            // path file PDF dokumen izin, wajib diisi kalau kategori organisasi
            $table->string('dokumen_izin')->nullable();

            // buat admin nulis alasan kalau tolak/batalin, optional
            $table->text('catatan_admin')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjamans');
    }
};