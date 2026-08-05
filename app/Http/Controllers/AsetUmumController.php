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
        $status = $request->input('status');

        $asetUmum = AsetUmum::when($keyword, function ($query, $keyword) {
                $query->where('nama_alat', 'like', "%{$keyword}%")
                      ->orWhere('merek', 'like', "%{$keyword}%")
                      ->orWhere('nomor_unit', 'like', "%{$keyword}%")
                      ->orWhere('kode_aset', 'like', "%{$keyword}%");
            })
            // dari dashboard admin, tiap status (Tersedia/Dipinjam/Rusak/Pemeliharaan) bisa diklik
            // biar langsung liat daftarnya di sini, gak usah nyari manual. Rusak/pemeliharaan
            // masih kolom manual jadi bisa difilter langsung di query; tersedia/dipinjam sekarang
            // dihitung ulang dari peminjaman aktif (status_efektif), jadi difilter sesudah di-get()
            ->when($status && in_array($status, ['rusak', 'pemeliharaan']), fn ($query) => $query->where('status', $status))
            // biar admin tau siapa yang lagi minjem alat "dipinjam", bukan cuma liat status
            // doang tanpa konteks -- ambil peminjaman yang beneran aktif SEKARANG buat tiap alat.
            ->with(['peminjamanDetailAktifSekarang.peminjaman.mahasiswa'])
            // urutin: Kunci dulu, terus Proyektor, baru alat lain-lain
            ->orderByRaw("
                CASE
                    WHEN nama_alat LIKE 'Kunci%' THEN 1
                    WHEN nama_alat = 'Proyektor' THEN 2
                    ELSE 3
                END
            ")
            // dalam grup Kunci/Lainnya, urutin alfabet nama
            ->orderBy('nama_alat')
            // dalam grup Proyektor, urutin angka nomor unit (4,5,6...bukan 10,11,12,4,5)
            ->orderByRaw('CAST(nomor_unit AS UNSIGNED) ASC')
            ->get();

        if ($status && in_array($status, ['tersedia', 'dipinjam'])) {
            $asetUmum = $asetUmum->filter(fn ($alat) => $alat->status_efektif === $status)->values();
        }

        return view('aset-umum.index', compact('asetUmum', 'keyword', 'status'));
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
            'status'      => 'required|in:tersedia,dipinjam,rusak,pemeliharaan',
        ]);

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
            'status'      => 'required|in:tersedia,dipinjam,rusak,pemeliharaan',
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
