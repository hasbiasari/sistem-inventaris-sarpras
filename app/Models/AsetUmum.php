<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetUmum extends Model
{
    protected $table = 'aset_umums';

    protected $fillable = ['nama_alat', 'nomor_unit', 'merek', 'kode_aset', 'jumlah_stok', 'status'];

    // status_efektif & jumlah_tersedia_sekarang dihitung ulang tiap kali (bukan disimpan), jadi
    // harus ikut nempel pas model di-JSON-in (dipakai di dashboard mahasiswa yang polling via AJAX)
    protected $appends = ['status_efektif', 'jumlah_tersedia_sekarang'];

    // semua baris peminjaman yang pernah nyantumin barang ini
    public function peminjamanDetails()
    {
        return $this->hasMany(PeminjamanDetail::class);
    }

    // baris peminjaman yang BENERAN kejadian SEKARANG (disetujui, dan detik ini ada di antara
    // (tanggal_pakai + jam_mulai) s.d. (tanggal_selesai + jam_selesai) sebagai SATU rentang waktu
    // kontinu) -- bukan cuma "disetujui" doang, soalnya booking yang disetujui tapi jadwalnya
    // masih besok/nanti gak boleh keitung dipinjam sekarang. Rentang multi-hari dianggap kepakai
    // TERUS-MENERUS dari awal ke akhir, bukan jam yang sama berulang tiap hari.
    public function peminjamanDetailAktifSekarang()
    {
        $sekarang = now()->format('Y-m-d H:i:s');

        return $this->hasMany(PeminjamanDetail::class)->whereHas('peminjaman', function ($q) use ($sekarang) {
            $q->where('status', 'disetujui')
                ->whereRaw('TIMESTAMP(tanggal_pakai, jam_mulai) <= ?', [$sekarang])
                ->whereRaw('TIMESTAMP(COALESCE(tanggal_selesai, tanggal_pakai), jam_selesai) >= ?', [$sekarang]);
        });
    }

    // total unit yang lagi kepake sekarang, dari relasi di atas
    private function jumlahDipinjamSekarang(): int
    {
        return $this->relationLoaded('peminjamanDetailAktifSekarang')
            ? $this->peminjamanDetailAktifSekarang->sum('jumlah')
            : $this->peminjamanDetailAktifSekarang()->sum('jumlah');
    }

    // status yang sebenernya SEKARANG: rusak/pemeliharaan/dipinjam yang diset manual sama admin
    // tetep dihargai apa adanya (jangan ketiban balik jadi tersedia). Tapi kalau kolomnya masih
    // "tersedia" DAN ternyata ada peminjaman yang beneran aktif detik ini (mahasiswa minjam lewat
    // sistem tanpa admin sempet ubah status manual), tetep ketauan "dipinjam" -- ini yang benerin
    // bug asli: status gak update otomatis pas ada peminjaman baru.
    public function getStatusEfektifAttribute()
    {
        if (in_array($this->status, ['rusak', 'pemeliharaan', 'dipinjam'])) {
            return $this->status;
        }

        return $this->jumlahDipinjamSekarang() > 0 ? 'dipinjam' : 'tersedia';
    }

    // sisa unit yang beneran bisa dipinjam sekarang. Kalau admin udah manual set "dipinjam",
    // anggap semua stoknya kepake (0 tersedia) -- kalau enggak, hitung dari stok dikurangi
    // peminjaman yang aktif beneran sekarang.
    public function getJumlahTersediaSekarangAttribute()
    {
        if ($this->status === 'dipinjam') {
            return 0;
        }

        return max(0, $this->jumlah_stok - $this->jumlahDipinjamSekarang());
    }
}