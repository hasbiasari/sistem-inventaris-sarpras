<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjamans';

    protected $casts = [
        'tanggal_pakai' => 'date',
        'tanggal_selesai' => 'date',
        'waktu_kembali' => 'datetime',
    ];

    protected $fillable = [
        'jenis_peminjam',
        'mahasiswa_id',
        'kelas',
        'ormawa',
        'nama_eksternal',
        'keterangan_eksternal',
        'kategori',
        'status',
        'waktu_kembali',
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

    // format tanggal buat ditampilin: sehari doang -> "27/07/2026", multi-hari -> "27/07/2026 - 29/07/2026"
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

    // format tanggal+jam gabungan buat ditampilin, sebagai SATU rentang waktu yang jalan terus
    // (bukan jam yang sama diulang tiap hari): sehari doang -> "27/07/2026 (13:38-14:00)",
    // multi-hari -> "27/07/2026 13:38 s.d. 29/07/2026 14:00"
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

    // scope biar gampang filter peminjaman punya 1 mahasiswa doang (buat requirement 8: riwayat mahasiswa)
    public function scopeMilikMahasiswa($query, $mahasiswaId)
    {
        return $query->where('mahasiswa_id', $mahasiswaId);
    }
}