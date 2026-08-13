<?php

namespace App\Http\Controllers;

use App\Models\AsetUmum;
use App\Models\Notifikasi;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    // dipanggil polling dari JS buat ambil daftar notif punya user yang login
    public function data()
    {
        $user = Auth::user();

        if ($user->role === 'mahasiswa' && $user->mahasiswa) {
            $this->kirimNotifikasiTerlambat($user->mahasiswa);
        }

        if ($user->role === 'admin_tu') {
            $this->kirimNotifikasiPemeliharaan();
        }

        $notifikasi = Notifikasi::where('user_id', Auth::id())
            ->latest()
            ->take(10)
            ->get();

        $jumlahBelumDibaca = Notifikasi::where('user_id', Auth::id())
            ->where('sudah_dibaca', false)
            ->count();

        return response()->json([
            'notifikasi' => $notifikasi,
            'jumlah_belum_dibaca' => $jumlahBelumDibaca,
        ]);
    }

    // cek peminjaman yang udah lewat jam selesai tapi belum dikembalikan, kirim notif sekali per peminjaman
    private function kirimNotifikasiTerlambat($mahasiswa)
    {
        $sekarang = now()->format('Y-m-d H:i:s');

        $terlambat = Peminjaman::where('mahasiswa_id', $mahasiswa->id)
            ->where('status', 'disetujui')
            ->whereNull('notifikasi_terlambat_at')
            ->whereRaw('TIMESTAMP(COALESCE(tanggal_selesai, tanggal_pakai), jam_selesai) < ?', [$sekarang])
            ->get();

        foreach ($terlambat as $peminjaman) {
            Notifikasi::create([
                'user_id' => $mahasiswa->user_id,
                'pesan' => 'Peminjaman kamu sudah melebihi jam waktu, segera kembalikan ke TU.',
                'link' => route('peminjaman.show', $peminjaman->id),
            ]);

            $peminjaman->update(['notifikasi_terlambat_at' => now()]);
        }
    }

    // cek proyektor yang masuk tingkat "perlu perhatian" (70-79.99%) ATAU udah "perlu pemeliharaan" (>=80%),
    // kirim notif ke semua admin -- 2 tingkat ini ditandai kolom terpisah, biar notif "perlu perhatian"
    // yang duluan muncul gak nutupin notif "perlu pemeliharaan" yang lebih mendesak pas nanti nyampe situ
    private function kirimNotifikasiPemeliharaan()
    {
        $this->kirimNotifikasiTierProyektor(
            'perlu_perhatian',
            'notifikasi_perhatian_at',
            fn ($alat) => "{$alat->nama_lengkap} sudah mendekati batas servis ({$alat->persentase_menuju_servis}%), mulai jadwalkan pemeliharaan."
        );

        $this->kirimNotifikasiTierProyektor(
            'perlu_pemeliharaan',
            'notifikasi_pemeliharaan_at',
            fn ($alat) => "{$alat->nama_lengkap} sudah mencapai {$alat->total_jam_pakai} jam pemakaian, perlu segera dijadwalkan pemeliharaan."
        );
    }

    // kirim notif ke semua admin buat proyektor yang baru masuk $statusTarget, sekali per proyektor
    // per kolom penanda (gak berulang tiap polling sampai kolomnya direset pas selesai pemeliharaan)
    private function kirimNotifikasiTierProyektor(string $statusTarget, string $kolomPenanda, \Closure $buatPesan)
    {
        $daftarProyektor = AsetUmum::where('nama_alat', 'Proyektor')
            ->whereNull($kolomPenanda)
            ->get()
            ->filter(fn ($alat) => $alat->status_pemeliharaan === $statusTarget);

        if ($daftarProyektor->isEmpty()) {
            return;
        }

        $adminList = User::where('role', 'admin_tu')->get();

        foreach ($daftarProyektor as $alat) {
            foreach ($adminList as $admin) {
                Notifikasi::create([
                    'user_id' => $admin->id,
                    'pesan' => $buatPesan($alat),
                    'link' => route('admin.pemeliharaan-proyektor'),
                ]);
            }

            $alat->update([$kolomPenanda => now()]);
        }
    }

    // dipanggil pas user klik "Tandai semua dibaca"
    public function tandaiDibaca()
    {
        Notifikasi::where('user_id', Auth::id())
            ->where('sudah_dibaca', false)
            ->update(['sudah_dibaca' => true]);

        return response()->json(['status' => 'ok']);
    }

    // dipanggil pas user klik salah satu notifikasi -- cuma notif itu aja yang ditandai dibaca
    public function tandaiSatuDibaca(Notifikasi $notifikasi)
    {
        if ($notifikasi->user_id !== Auth::id()) {
            abort(403);
        }

        $notifikasi->update(['sudah_dibaca' => true]);

        return response()->json(['status' => 'ok']);
    }
}
