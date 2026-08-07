<?php

namespace Database\Seeders;

use App\Models\AsetUmum;
use Illuminate\Database\Seeder;

class SesuaikanJamPakaiProyektorSeeder extends Seeder
{
    // batas jam maksimal per merek, ngikutin spek umur lampu masing-masing tipe
    // (Epson EB-X500: spek resmi 6000 jam mode normal; EB-X600 & InFocus: estimasi kelas serupa)
    private const BATAS_PER_MEREK = [
        'Epson EB-X500' => 6000,
        'EB-X600' => 5000,
        'InFocus' => 4000,
    ];

    public function run(): void
    {
        $daftarProyektor = AsetUmum::where('nama_alat', 'Proyektor')->get();

        if ($daftarProyektor->isEmpty()) {
            $this->command?->warn('Belum ada data Proyektor, jalankan AsetUmumSeeder dulu.');

            return;
        }

        // total_jam_pakai per unit -- #4 & #5 dipatok pas (contoh kasus "Perlu Pemeliharaan" & "Perlu Perhatian"),
        // sisanya diacak dalam rentang biar variatif & realistis buat demo (bukan seragam "baru dipakai dikit" semua)
        $targetJamPakai = [
            '4' => 4950,               // 82.5% dari 6000 -> Perlu Pemeliharaan
            '5' => 4700,                // 78% dari 6000 -> Perlu Perhatian
            '6' => random_int(1500, 2500),
            '8' => random_int(1500, 2500),
            '7' => random_int(1000, 1800),
            '9' => random_int(1000, 1800),
            '10' => random_int(500, 2000),
            '11' => random_int(500, 2000),
            '12' => random_int(500, 2000),
            '13' => random_int(500, 2000),
            '14' => random_int(500, 2000),
        ];

        foreach ($daftarProyektor as $proyektor) {
            $batasBaru = self::BATAS_PER_MEREK[$proyektor->merek] ?? $proyektor->batas_jam_maksimal;
            $jamBaru = $targetJamPakai[$proyektor->nomor_unit] ?? $proyektor->total_jam_pakai;

            $proyektor->update([
                'batas_jam_maksimal' => $batasBaru,
                'total_jam_pakai' => $jamBaru,
            ]);
            $proyektor->refresh();

            // reset penanda notifikasi kalau status barunya udah di bawah tingkat yang pernah dinotif,
            // biar kalau nanti naik lagi bisa dinotif ulang (konsisten sama logic di fitur edit manual)
            $resetPenanda = [];
            if ($proyektor->status_pemeliharaan !== 'perlu_pemeliharaan') {
                $resetPenanda['notifikasi_pemeliharaan_at'] = null;
            }
            if ($proyektor->status_pemeliharaan === 'normal') {
                $resetPenanda['notifikasi_perhatian_at'] = null;
            }
            if ($resetPenanda) {
                $proyektor->update($resetPenanda);
            }

            $this->command?->info(
                "Unit #{$proyektor->nomor_unit} ({$proyektor->merek}): {$jamBaru}/{$batasBaru} jam -> {$proyektor->status_pemeliharaan}"
            );
        }
    }
}
