<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pesan',
        'link',
        'sudah_dibaca',
    ];

    // punya siapa notif ini
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}