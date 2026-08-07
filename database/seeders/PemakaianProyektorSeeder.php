<?php

namespace Database\Seeders;

use App\Models\AsetUmum;
use App\Models\Peminjaman;
use App\Models\PeminjamanDetail;
use Illuminate\Database\Seeder;

class PemakaianProyektorSeeder extends Seeder
{
    private const JUMLAH_MINGGU = 10;

    public function run(): void
    {
        $daftarProyektor = AsetUmum::where('nama_alat', 'Proyektor')->get();

        if ($daftarProyektor->isEmpty()) {
            $this->command?->warn('Belum ada data Proyektor, jalankan AsetUmumSeeder dulu.');

            return;
        }

        $this->bersihkanDataLama($daftarProyektor);

        foreach ($daftarProyektor as $proyektor) {
            for ($mingguKe = self::JUMLAH_MINGGU - 1; $mingguKe >= 0; $mingguKe--) {
                $this->buatPemakaianSatuMinggu($proyektor, $mingguKe);
            }
        }
    }

    // buang dummy peminjaman lama (kalau seeder ini pernah di-run sebelumnya) & reset jam pakai proyektor,
    // biar aman dijalankan berkali-kali tanpa dobel akumulasi
    private function bersihkanDataLama($daftarProyektor): void
    {
        $peminjamanLama = Peminjaman::where('nama_eksternal', Peminjaman::PENANDA_SIMULASI_SMA)->pluck('id');

        PeminjamanDetail::whereIn('peminjaman_id', $peminjamanLama)->delete();
        Peminjaman::whereIn('id', $peminjamanLama)->delete();

        AsetUmum::whereIn('id', $daftarProyektor->pluck('id'))->update(['total_jam_pakai' => 0]);
    }

    private function buatPemakaianSatuMinggu(AsetUmum $proyektor, int $mingguKe): void
    {
        $awalMinggu = now()->subWeeks($mingguKe)->startOfWeek();
        $jumlahSesi = random_int(1, 3);

        for ($sesi = 0; $sesi < $jumlahSesi; $sesi++) {
            $tanggalPakai = $awalMinggu->copy()->addDays(random_int(0, 5)); // Senin-Sabtu
            $jamMulaiJam = random_int(7, 15);
            $durasiJam = random_int(1, 3);
            $jamMulai = sprintf('%02d:00:00', $jamMulaiJam);
            $jamSelesai = sprintf('%02d:00:00', $jamMulaiJam + $durasiJam);

            $peminjaman = Peminjaman::create([
                'jenis_peminjam' => 'eksternal',
                'nama_eksternal' => Peminjaman::PENANDA_SIMULASI_SMA,
                'keterangan_eksternal' => 'Data dummy histori pemakaian proyektor buat demo SMA',
                'status' => 'selesai',
                'tanggal_pakai' => $tanggalPakai->toDateString(),
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'waktu_kembali' => $tanggalPakai->copy()->setTimeFromTimeString($jamSelesai),
            ]);

            PeminjamanDetail::create([
                'peminjaman_id' => $peminjaman->id,
                'aset_umum_id' => $proyektor->id,
                'jumlah' => 1,
            ]);

            $proyektor->increment('total_jam_pakai', $peminjaman->durasi_jam);
        }
    }
}
