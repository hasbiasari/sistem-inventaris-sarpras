<?php

namespace App\Imports;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class MahasiswaImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public $jumlahBaru = 0;
    public $jumlahUpdate = 0;

    public function collection($rows)
    {
        foreach ($rows as $row) {
            $nim   = trim($row['nim'] ?? '');
            $nama  = trim($row['nama'] ?? '');
            $email = trim($row['email'] ?? '');

            if (!$nim || !$nama || !$email) {
                continue;
            }

            $mahasiswa = Mahasiswa::where('nim', $nim)->first();

            if ($mahasiswa) {
                $mahasiswa->update(['nama' => $nama]);

                $mahasiswa->user->update([
                    'name'  => $nama,
                    'email' => $email,
                ]);

                $this->jumlahUpdate++;
            } else {
                // belum ada -> buat akun baru
                $user = User::create([
                    'name'     => $nama,
                    'email'    => $email,
                    'password' => Hash::make($nim),
                    'role'     => 'mahasiswa',
                ]);

                Mahasiswa::create([
                    'user_id' => $user->id,
                    'nim'     => $nim,
                    'nama'    => $nama,
                    'status'  => 'Aktif',
                ]);

                $this->jumlahBaru++;
            }
        }
    }
}