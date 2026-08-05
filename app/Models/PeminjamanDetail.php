<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeminjamanDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'peminjaman_id',
        'aset_umum_id',
        'jumlah',
    ];

    // induknya, si peminjaman
    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class);
    }

    // barang yang dipinjam
    public function asetUmum()
    {
        return $this->belongsTo(AsetUmum::class);
    }
}