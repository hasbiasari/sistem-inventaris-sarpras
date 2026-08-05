<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    protected $fillable = ['user_id', 'nim', 'nama', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 1 mahasiswa bisa punya banyak riwayat peminjaman
    public function peminjamans()
    {
        return $this->hasMany(Peminjaman::class);
    }
}