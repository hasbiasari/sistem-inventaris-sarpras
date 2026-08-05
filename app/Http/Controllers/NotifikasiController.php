<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    // dipanggil polling dari JS buat ambil daftar notif punya user yang login
    public function data()
    {
        $notifikasi = Notifikasi::where('user_id', Auth::id())
            ->latest()
            ->take(10)
            ->get();

        $jumlahBelumDibaca = Notifikasi::where('user_id', Auth::id())
            ->where('sudah_dibaca', false)
            ->count();

        return response()->json([
            'notifikasi' => $notifikasi,
            'jumlah_belum_dibaca' => $jumlahBelumDibaca,
        ]);
    }

    // dipanggil pas user klik ikon lonceng, biar semua notif dianggap udah dibaca
    public function tandaiDibaca()
    {
        Notifikasi::where('user_id', Auth::id())
            ->where('sudah_dibaca', false)
            ->update(['sudah_dibaca' => true]);

        return response()->json(['status' => 'ok']);
    }
}