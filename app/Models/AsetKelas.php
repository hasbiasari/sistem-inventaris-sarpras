<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetKelas extends Model
{
    protected $table = 'aset_kelas'; // biar laravel gak nyari tabel "aset_kela"

    protected $fillable = ['nama_ruangan', 'gedung', 'kapasitas', 'jumlah_kursi', 'jumlah_papan_tulis'];

    // nama ruangan selalu kapital, biar konsisten di semua tampilan
    public function setNamaRuanganAttribute($value)
    {
        $this->attributes['nama_ruangan'] = mb_strtoupper($value);
    }
}
