<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Daftar 18 Peran Utama SINTA (core.roles)
        $roles = [
            'super_admin'      => 'Administrator tertinggi untuk manajemen platform SINTA',
            'admin_sekolah'    => 'Administrator tingkat sekolah/tenant',
            'kepala_sekolah'   => 'Kepala Sekolah',
            'guru'             => 'Tenaga Pengajar',
            'wali_kelas'       => 'Wali Kelas',
            'guru_bk'          => 'Guru Bimbingan Konseling',
            'bk'               => 'Guru BK',
            'staf_tu'          => 'Staf Tata Usaha & Administrasi',
            'operator_sekolah' => 'Operator Sekolah',
            'keuangan'         => 'Bendahara / Kasir Sekolah',
            'staf_keuangan'    => 'Bendahara & Kasir Keuangan',
            'perpustakaan'     => 'Pengelola Perpustakaan',
            'sarpras'          => 'Pengelola Sarpras',
            'kesiswaan'        => 'Kesiswaan',
            'kurikulum'        => 'Tim Kurikulum',
            'humas'            => 'Humas / Hubungan Masyarakat',
            'pembina_ekskul'   => 'Pembina Ekstrakurikuler',
            'siswa'            => 'Peserta Didik',
            'orang_tua'        => 'Orang Tua / Wali Murid',
        ];

        foreach ($roles as $roleName => $label) {
            $existing = DB::table('core.roles')->where('nama_role', $roleName)->first();
            if ($existing) {
                DB::table('core.roles')->where('id', $existing->id)->update([
                    'deskripsi'  => $label,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('core.roles')->insert([
                    'id'         => (string)Str::uuid(),
                    'nama_role'  => $roleName,
                    'deskripsi'  => $label,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $totalRoles = DB::table('core.roles')->count();
        $this->command?->info("✔ Seeding core.roles selesai ({$totalRoles} peran terdaftar).");
    }
}
