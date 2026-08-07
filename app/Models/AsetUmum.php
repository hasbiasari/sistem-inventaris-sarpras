<?php

namespace App\Models;

use App\Services\PrediksiProyektorService;
use Illuminate\Database\Eloquent\Model;

class AsetUmum extends Model
{
    protected $table = 'aset_umums';

    protected $fillable = ['nama_alat', 'nomor_unit', 'merek', 'kode_aset', 'jumlah_stok', 'status', 'total_jam_pakai', 'batas_jam_maksimal', 'notifikasi_pemeliharaan_at', 'notifikasi_perhatian_at'];

    protected $casts = [
        'total_jam_pakai' => 'decimal:2',
    ];

    // dihitung ulang tiap kali, ikut nempel pas model di-JSON-in
    protected $appends = ['status_efektif', 'jumlah_tersedia_sekarang', 'threshold_servis', 'perlu_pemeliharaan', 'persentase_menuju_servis', 'status_pemeliharaan', 'kelas_baris', 'warna_status'];

    // semua baris peminjaman yang pernah nyantumin barang ini
    public function peminjamanDetails()
    {
        return $this->hasMany(PeminjamanDetail::class);
    }

    // baris peminjaman yang beneran aktif sekarang: udah mulai & masih disetujui
    // (sengaja gak ada batas jam selesai -- kalau udah lewat jam tapi belum dikembalikan,
    // barangnya tetap dianggap kepake, bukan otomatis balik "tersedia")
    public function peminjamanDetailAktifSekarang()
    {
        $sekarang = now()->format('Y-m-d H:i:s');

        return $this->hasMany(PeminjamanDetail::class)->whereHas('peminjaman', function ($q) use ($sekarang) {
            $q->where('status', 'disetujui')
                ->whereRaw('TIMESTAMP(tanggal_pakai, jam_mulai) <= ?', [$sekarang]);
        });
    }

    // total unit yang lagi kepake sekarang
    private function jumlahDipinjamSekarang(): int
    {
        return $this->relationLoaded('peminjamanDetailAktifSekarang')
            ? $this->peminjamanDetailAktifSekarang->sum('jumlah')
            : $this->peminjamanDetailAktifSekarang()->sum('jumlah');
    }

    // status real, ikut peminjaman aktif kalau kolomnya masih "tersedia"
    public function getStatusEfektifAttribute()
    {
        if (in_array($this->status, ['rusak', 'pemeliharaan', 'dipinjam'])) {
            return $this->status;
        }

        return $this->jumlahDipinjamSekarang() > 0 ? 'dipinjam' : 'tersedia';
    }

    // sisa unit yang beneran bisa dipinjam sekarang
    public function getJumlahTersediaSekarangAttribute()
    {
        if ($this->status === 'dipinjam') {
            return 0;
        }

        return max(0, $this->jumlah_stok - $this->jumlahDipinjamSekarang());
    }

    // nama alat + nomor unit (kalau ada), buat bedain unit mana yang lagi dipakai/dipinjam
    public function getNamaLengkapAttribute()
    {
        return $this->nama_alat . ($this->nomor_unit ? " ({$this->nomor_unit})" : '');
    }

    // ===== Bagian di bawah ini cuma delegasi ke PrediksiProyektorService =====
    // Rumus SMA & prediksi pemeliharaannya sendiri ada di sana, biar gampang ditunjuk terpisah
    // dari Model pas jelasin/demoin algoritmanya. Model sengaja gak ngitung apa-apa lagi di sini.

    // histori jam pakai per minggu kalender, dipakai buat hitung SMA & grafik tren
    public function riwayatJamMingguan(int $jumlahMinggu = 4)
    {
        return app(PrediksiProyektorService::class)->riwayatJamMingguan($this, $jumlahMinggu);
    }

    // rata-rata jam pakai per minggu (Simple Moving Average) dari $jumlahMinggu minggu terakhir
    public function smaJamMingguan(int $jumlahMinggu = 4): float
    {
        return app(PrediksiProyektorService::class)->smaJamMingguan($this, $jumlahMinggu);
    }

    // 80% dari batas jam maksimal -- proyektor dianggap perlu servis begitu nyampe titik ini
    public function getThresholdServisAttribute()
    {
        return app(PrediksiProyektorService::class)->thresholdServis($this);
    }

    // udah nyampe/lewat threshold servis apa belum
    public function getPerluPemeliharaanAttribute(): bool
    {
        return app(PrediksiProyektorService::class)->perluPemeliharaan($this);
    }

    // estimasi berapa minggu lagi nyampe threshold servis, berdasarkan SMA jam pakai per minggu
    public function estimasiMingguMenujuServis(int $jumlahMingguSma = 4): ?float
    {
        return app(PrediksiProyektorService::class)->estimasiMingguMenujuServis($this, $jumlahMingguSma);
    }

    // udah berapa persen jalan menuju threshold servis (bisa lebih dari 100 kalau udah kelewat)
    public function getPersentaseMenujuServisAttribute()
    {
        return app(PrediksiProyektorService::class)->persentaseMenujuServis($this);
    }

    // total jam pakai semua unit proyektor digabung per minggu -- buat grafik tren gabungan di dashboard pimpinan
    public static function riwayatJamMingguanGabungan(int $jumlahMinggu = 8)
    {
        return app(PrediksiProyektorService::class)->riwayatJamMingguanGabungan($jumlahMinggu);
    }

    // tren jam pakai gabungan per minggu + overlay SMA -- dipakai di dashboard Pimpinan & halaman Pemeliharaan Proyektor
    public static function trenMingguanGabungan(int $jumlahMinggu = 8): array
    {
        return app(PrediksiProyektorService::class)->trenMingguanGabungan($jumlahMinggu);
    }

    // status pemeliharaan 3 tingkat: normal / perlu_perhatian / perlu_pemeliharaan (+ dalam_pemeliharaan kalau lagi diservis)
    public function getStatusPemeliharaanAttribute(): string
    {
        return app(PrediksiProyektorService::class)->statusPemeliharaan($this);
    }

    // ===== Accessor tampilan (bukan bagian dari algoritma, murni pemetaan ke nama class CSS/Bootstrap) =====

    // nama class CSS buat highlight baris tabel sesuai status_pemeliharaan.
    // Cuma 'row-perlu-pemeliharaan' & 'row-perlu-perhatian' yang beneran punya style
    // (lihat mazer-green-theme.css) -- status lain ('normal', 'dalam_pemeliharaan') gak ngefek apa-apa.
    public function getKelasBarisAttribute(): string
    {
        return 'row-' . str_replace('_', '-', $this->status_pemeliharaan);
    }

    // warna Bootstrap (success/warning/danger) buat progress bar "menuju batas servis".
    // 'dalam_pemeliharaan' sengaja ikut 'success' (netral ijo), bukan warna terpisah.
    public function getWarnaStatusAttribute(): string
    {
        return match ($this->status_pemeliharaan) {
            'perlu_pemeliharaan' => 'danger',
            'perlu_perhatian' => 'warning',
            default => 'success',
        };
    }
}