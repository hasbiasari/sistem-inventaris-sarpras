<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alat_terpisahs', function (Blueprint $table) {
            $table->string('nomor_unit')->nullable()->after('nama_alat');
            $table->string('merek')->nullable()->after('nomor_unit');
        });
    }

    public function down(): void
    {
        Schema::table('alat_terpisahs', function (Blueprint $table) {
            $table->dropColumn(['nomor_unit', 'merek']);
        });
    }
};