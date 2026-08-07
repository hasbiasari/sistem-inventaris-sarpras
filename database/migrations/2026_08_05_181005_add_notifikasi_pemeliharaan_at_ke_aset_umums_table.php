<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset_umums', function (Blueprint $table) {
            $table->timestamp('notifikasi_pemeliharaan_at')->nullable()->after('batas_jam_maksimal');
        });
    }

    public function down(): void
    {
        Schema::table('aset_umums', function (Blueprint $table) {
            $table->dropColumn('notifikasi_pemeliharaan_at');
        });
    }
};
