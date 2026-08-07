<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjamans';

    // penanda nama_eksternal buat data dummy histori pemakaian proyektor (lihat PemakaianProyektorSeeder) --
    // dipakai biar data simulasi ini gak ikut kehitung sebagai aktivitas peminjaman asli di laporan/dashboard
    const PENANDA_SIMULASI_SMA = 'Simulasi SMA';

    protected $casts = [
        'tanggal_pakai' => 'date',
        'tanggal_selesai' => 'date',
        'waktu_kembali' => 'datetime',
        'notifikasi_terlambat_at' => 'datetime',
    ];

    protected $fillable = [
        'jenis_peminjam',
        'mahasiswa_id',
        'kelas',
        'ormawa',
        'nama_kegiatan',
        'nama_eksternal',
        'keterangan_eksternal',
        'kategori',
        'status',
        'waktu_kembali',
        'notifikasi_terlambat_at',
        'dokumen_izin',
        'catatan_admin',
        'aset_kelas_id',
        'tanggal_pakai',
        'tanggal_selesai',
        'jam_mulai',
        'jam_selesai',
    ];

    // relasi ke mahasiswa yang minjem (kalau eksternal, ini bakal null)
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    // ruangan yang mau dipakai bareng peminjaman ini (opsional, bisa null)
    public function asetKelas()
    {
        return $this->belongsTo(AsetKelas::class);
    }

    // 1 peminjaman bisa punya banyak barang (detail)
    public function details()
    {
        return $this->hasMany(PeminjamanDetail::class);
    }

    // foto bukti pas barang dikembalikan (bisa lebih dari 1 foto)
    public function buktiPengembalian()
    {
        return $this->hasMany(BuktiPengembalian::class);
    }

    public function getNamaPeminjamAttribute()
    {
        if ($this->jenis_peminjam === 'eksternal') {
            return $this->nama_eksternal;
        }

        return $this->mahasiswa->nama ?? '-';
    }

    // format tanggal buat ditampilin
    public function getRentangTanggalAttribute()
    {
        if (! $this->tanggal_pakai) {
            return '-';
        }

        if ($this->tanggal_selesai && ! $this->tanggal_selesai->isSameDay($this->tanggal_pakai)) {
            return $this->tanggal_pakai->format('d/m/Y') . ' - ' . $this->tanggal_selesai->format('d/m/Y');
        }

        return $this->tanggal_pakai->format('d/m/Y');
    }

    // format tanggal+jam gabungan buat ditampilin
    public function getRentangWaktuAttribute()
    {
        if (! $this->tanggal_pakai || ! $this->jam_mulai || ! $this->jam_selesai) {
            return '-';
        }

        $jamMulai = substr($this->jam_mulai, 0, 5);
        $jamSelesai = substr($this->jam_selesai, 0, 5);

        if ($this->tanggal_selesai && ! $this->tanggal_selesai->isSameDay($this->tanggal_pakai)) {
            return $this->tanggal_pakai->format('d/m/Y') . " {$jamMulai} s.d. " . $this->tanggal_selesai->format('d/m/Y') . " {$jamSelesai}";
        }

        return $this->tanggal_pakai->format('d/m/Y') . " ({$jamMulai}-{$jamSelesai})";
    }

    // durasi pemakaian dalam jam (desimal), dari jam_mulai s.d. jam_selesai (ikut tanggal_selesai kalau multi-hari)
    public function getDurasiJamAttribute()
    {
        if (! $this->tanggal_pakai || ! $this->jam_mulai || ! $this->jam_selesai) {
            return 0;
        }

        $mulai = Carbon::parse($this->tanggal_pakai->format('Y-m-d') . ' ' . $this->jam_mulai);
        $tanggalSelesai = $this->tanggal_selesai ?? $this->tanggal_pakai;
        $selesai = Carbon::parse($tanggalSelesai->format('Y-m-d') . ' ' . $this->jam_selesai);

        return $mulai->diffInMinutes($selesai) / 60;
    }

    // scope filter peminjaman punya 1 mahasiswa
    public function scopeMilikMahasiswa($query, $mahasiswaId)
    {
        return $query->where('mahasiswa_id', $mahasiswaId);
    }

    // buang data dummy seeder simulasi SMA dari laporan/aktivitas peminjaman asli --
    // nama_eksternal nullable, jadi harus whereNull juga (!= gak nangkep NULL di SQL)
    public function scopeBukanSimulasi($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('nama_eksternal')->orWhere('nama_eksternal', '!=', self::PENANDA_SIMULASI_SMA);
        });
    }
}