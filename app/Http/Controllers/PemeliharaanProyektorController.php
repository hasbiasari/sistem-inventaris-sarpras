<?php

namespace App\Http\Controllers;

use App\Exports\ProyektorExport;
use App\Models\AsetUmum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PemeliharaanProyektorController extends Controller
{
    // daftar proyektor + prediksi pemeliharaan (SMA)
    public function index()
    {
        $daftarProyektor = $this->ambilDaftarProyektor();

        $ringkasanStatus = $this->hitungRingkasanStatus($daftarProyektor);

        // tren pemakaian per minggu (gabungan semua unit) + overlay SMA rolling 4 minggu
        $trenProyektor = AsetUmum::trenMingguanGabungan(8);

        return view('pemeliharaan-proyektor.index', compact('daftarProyektor', 'ringkasanStatus', 'trenProyektor'));
    }

    // daftar proyektor, yang paling kritis (persentase menuju threshold tertinggi) di atas
    private function ambilDaftarProyektor()
    {
        return AsetUmum::where('nama_alat', 'Proyektor')
            ->get()
            ->sortByDesc('persentase_menuju_servis')
            ->values();
    }

    private function hitungRingkasanStatus($daftarProyektor): array
    {
        return [
            'normal' => $daftarProyektor->where('status_pemeliharaan', 'normal')->count(),
            'perlu_perhatian' => $daftarProyektor->where('status_pemeliharaan', 'perlu_perhatian')->count(),
            'perlu_pemeliharaan' => $daftarProyektor->where('status_pemeliharaan', 'perlu_pemeliharaan')->count(),
            'dalam_pemeliharaan' => $daftarProyektor->where('status_pemeliharaan', 'dalam_pemeliharaan')->count(),
        ];
    }

    // export laporan servis proyektor ke PDF -- halaman ini belum ada filter tab, jadi selalu semua data
    public function exportPdf()
    {
        $daftarProyektor = $this->ambilDaftarProyektor();
        $ringkasanStatus = $this->hitungRingkasanStatus($daftarProyektor);

        $judulLaporan = 'Laporan Servis Proyektor';
        $labelFilter = 'Semua Data';

        $pdf = Pdf::loadView('pemeliharaan-proyektor.laporan-pdf', compact('daftarProyektor', 'ringkasanStatus', 'judulLaporan', 'labelFilter'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-servis-proyektor-' . now()->format('Y-m-d') . '.pdf');
    }

    // export laporan servis proyektor ke Excel -- sama kayak PDF, selalu semua data
    public function exportExcel()
    {
        $daftarProyektor = $this->ambilDaftarProyektor();
        $ringkasanStatus = $this->hitungRingkasanStatus($daftarProyektor);

        return Excel::download(
            new ProyektorExport($daftarProyektor, 'Semua Data', $ringkasanStatus),
            'laporan-servis-proyektor-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // proyektor ditarik buat diservis
    public function setPemeliharaan(AsetUmum $asetUmum)
    {
        if ($asetUmum->nama_alat !== 'Proyektor') {
            abort(403);
        }

        $asetUmum->update(['status' => 'pemeliharaan']);

        return redirect()->route('admin.pemeliharaan-proyektor')->with('success', "{$asetUmum->nama_lengkap} ditandai sedang pemeliharaan.");
    }

    // proyektor selesai diservis: jam pakai direset, status balik tersedia, boleh dinotif lagi kalau nanti nyampe threshold lagi
    public function selesaiPemeliharaan(AsetUmum $asetUmum)
    {
        if ($asetUmum->nama_alat !== 'Proyektor') {
            abort(403);
        }

        $asetUmum->update([
            'status' => 'tersedia',
            'total_jam_pakai' => 0,
            'notifikasi_pemeliharaan_at' => null,
            'notifikasi_perhatian_at' => null,
        ]);

        return redirect()->route('admin.pemeliharaan-proyektor')->with('success', "{$asetUmum->nama_lengkap} selesai pemeliharaan, jam pakai direset.");
    }

    // koreksi manual jam pakai & batas jam maksimal -- dipakai buat input jam pakai awal
    // (proyektor yang udah lama dipakai sebelum sistem ini ada) atau ganti spesifikasi lampu per merek/tipe
    public function updateJamPakai(Request $request, AsetUmum $asetUmum)
    {
        if ($asetUmum->nama_alat !== 'Proyektor') {
            abort(403);
        }

        $validated = $request->validate([
            'total_jam_pakai' => 'required|numeric|min:0',
            'batas_jam_maksimal' => 'required|integer|min:1',
        ], [
            'total_jam_pakai.required' => 'Total jam pakai wajib diisi.',
            'total_jam_pakai.numeric' => 'Total jam pakai harus berupa angka.',
            'total_jam_pakai.min' => 'Total jam pakai gak boleh negatif.',
            'batas_jam_maksimal.required' => 'Batas jam maksimal wajib diisi.',
            'batas_jam_maksimal.integer' => 'Batas jam maksimal harus berupa angka bulat.',
            'batas_jam_maksimal.min' => 'Batas jam maksimal minimal 1 jam.',
        ]);

        $asetUmum->update($validated);
        $asetUmum->refresh();

        // kalau status barunya udah di bawah tingkat yang pernah dinotif, reset penandanya --
        // biar kalau nanti naik lagi bisa dinotif ulang, gak ke-skip gara-gara penanda lama nyangkut
        // dari sebelum jam pakainya dikoreksi
        $resetPenanda = [];
        if ($asetUmum->status_pemeliharaan !== 'perlu_pemeliharaan') {
            $resetPenanda['notifikasi_pemeliharaan_at'] = null;
        }
        if ($asetUmum->status_pemeliharaan === 'normal') {
            $resetPenanda['notifikasi_perhatian_at'] = null;
        }
        if ($resetPenanda) {
            $asetUmum->update($resetPenanda);
        }

        return redirect()->route('admin.pemeliharaan-proyektor')->with('success', "Jam pakai {$asetUmum->nama_lengkap} berhasil diupdate.");
    }
}
