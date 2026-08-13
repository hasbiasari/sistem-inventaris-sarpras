<?php

namespace App\Http\Controllers;

use App\Models\AsetUmum;
use Illuminate\Http\Request;

class AsetUmumController extends Controller
{
    // tampilin semua data aset (buat admin)
    public function index(Request $request)
    {
        $keyword = $request->input('cari');
        $filterStatus = in_array($request->input('status'), ['tersedia', 'dipinjam', 'rusak', 'pemeliharaan'])
            ? $request->input('status')
            : null;

        // urutan diinget lewat session, biar gak reset ke a-z tiap balik ke halaman ini
        if ($request->has('urutan')) {
            session(['urutan_aset_umum' => $request->input('urutan')]);
        }
        $urutan = session('urutan_aset_umum', 'a-z');

        $asetUmum = AsetUmum::when($keyword, function ($query, $keyword) {
                $query->where('nama_alat', 'like', "%{$keyword}%")
                      ->orWhere('merek', 'like', "%{$keyword}%")
                      ->orWhere('nomor_unit', 'like', "%{$keyword}%")
                      ->orWhere('kode_aset', 'like', "%{$keyword}%");
            })
            // biar admin tau siapa yang lagi minjem
            ->with(['peminjamanDetailAktifSekarang.peminjaman.mahasiswa'])
            ->when($urutan === 'terbaru', function ($query) {
                $query->orderByDesc('created_at');
            }, function ($query) {
                $query->orderBy('nama_alat')
                    // urutin angka nomor unit (4,5,6...bukan 10,11,12,4,5)
                    ->orderByRaw('CAST(nomor_unit AS UNSIGNED) ASC');
            })
            ->get();

        // status_efektif itu accessor terhitung (ikut peminjaman aktif), bukan kolom DB biasa,
        // jadi filternya di PHP -- dipakai dari kartu ringkasan status di Dashboard Admin TU
        if ($filterStatus) {
            $asetUmum = $asetUmum->filter(fn ($alat) => $alat->status_efektif === $filterStatus)->values();
        }

        return view('aset-umum.index', compact('asetUmum', 'keyword', 'urutan', 'filterStatus'));
    }

    // form tambah aset
    public function create()
    {
        return view('aset-umum.create');
    }

    // simpan aset baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_alat'   => 'required|string|max:255',
            'nomor_unit'  => 'nullable|string|max:50',
            'merek'       => 'nullable|string|max:100',
            'kode_aset'   => 'nullable|string|max:50',
            'jumlah_stok' => 'required|integer|min:0',
            // "dipinjam" sengaja gak termasuk -- itu status otomatis ngikutin peminjaman aktif, bukan manual
            'status'      => 'nullable|in:tersedia,rusak,pemeliharaan',
        ]);

        if ($validated['nama_alat'] === 'Proyektor') {
            $validated['batas_jam_maksimal'] = 2000;
        }

        AsetUmum::create($validated);

        return redirect()->route('admin.aset-umum')->with('success', 'Aset berhasil ditambahkan.');
    }

    // form edit aset
    public function edit(AsetUmum $asetUmum)
    {
        $asetUmum->load(['peminjamanDetailAktifSekarang.peminjaman.mahasiswa']);

        return view('aset-umum.edit', compact('asetUmum'));
    }

    // update aset
    public function update(Request $request, AsetUmum $asetUmum)
    {
        $validated = $request->validate([
            'nama_alat'   => 'required|string|max:255',
            'nomor_unit'  => 'nullable|string|max:50',
            'merek'       => 'nullable|string|max:100',
            'kode_aset'   => 'nullable|string|max:50',
            'jumlah_stok' => 'required|integer|min:0',
            // "dipinjam" sengaja gak termasuk -- itu status otomatis ngikutin peminjaman aktif, bukan manual
            'status'      => 'nullable|in:tersedia,rusak,pemeliharaan',
        ]);

        $asetUmum->update($validated);

        return redirect()->route('admin.aset-umum')->with('success', 'Aset berhasil diupdate.');
    }

    // hapus aset
    public function destroy(AsetUmum $asetUmum)
    {
        $asetUmum->delete();

        return redirect()->route('admin.aset-umum')->with('success', 'Aset berhasil dihapus.');
    }
}
