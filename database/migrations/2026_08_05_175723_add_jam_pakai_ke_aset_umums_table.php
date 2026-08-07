<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset_umums', function (Blueprint $table) {
            $table->decimal('total_jam_pakai', 8, 2)->default(0)->after('status');
            $table->unsignedInteger('batas_jam_maksimal')->nullable()->after('total_jam_pakai');
        });

        // proyektor yang udah ada dikasih batas jam maksimal default (khusus proyektor)
        DB::table('aset_umums')->where('nama_alat', 'Proyektor')->update(['batas_jam_maksimal' => 2000]);
    }

    public function down(): void
    {
        Schema::table('aset_umums', function (Blueprint $table) {
            $table->dropColumn(['total_jam_pakai', 'batas_jam_maksimal']);
        });
    }
};
