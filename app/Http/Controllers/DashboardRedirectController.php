<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardRedirectController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return match ($user->role) {
            'admin_tu'  => redirect()->route('admin.dashboard'),
            'pimpinan'  => redirect()->route('pimpinan.dashboard'),
            'mahasiswa' => redirect()->route('mahasiswa.dashboard'),
            default     => redirect()->route('login'),
        };
    }
}