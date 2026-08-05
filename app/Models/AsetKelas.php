<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetKelas extends Model
{
    protected $table = 'aset_kelas'; // biar laravel gak nyari tabel "aset_kela"

    protected $fillable = ['nama_ruangan', 'kapasitas', 'jumlah_kursi', 'jumlah_papan_tulis'];
}