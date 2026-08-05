<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuktiPengembalian extends Model
{
    protected $fillable = [
        'peminjaman_id',
        'foto',
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class);
    }
}
