<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\User;
use Modules\Core\Entities\SekolahIdentitas;

class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Tenant Demo SMA SINTA Nusantara
        $tenant = Tenant::updateOrCreate(
            ['npsn' => '20269999'],
            [
                'nama_sekolah'       => 'SMA SINTA Unggulan Nusantara',
                'subdomain'          => 'sintanusantara',
                'bentuk_pendidikan'  => 'SMA',
                'status_sekolah'     => 'Swasta',
                'akreditasi'         => 'A',
                'alamat'             => 'Jl. Pendidikan Karakter No. 88',
                'kabupaten_kota'     => 'Jakarta Selatan',
                'provinsi'           => 'DKI Jakarta',
                'email'              => 'info@sintanusantara.sch.id',
                'telepon'            => '021-78901234',
                'paket_aktif'        => 'Enterprise',
                'storage_limit_mb'   => 10240, // 10 GB
                'status'             => 'Aktif',
            ]
        );

        // 2. Buat / Perbarui Identitas Sekolah
        SekolahIdentitas::updateOrCreate(
            ['id' => $tenant->id],
            [
                'nama_sekolah'        => $tenant->nama_sekolah,
                'npsn'                => $tenant->npsn,
                'akreditasi'          => 'A',
                'alamat'              => $tenant->alamat,
                'kabupaten_kota'      => $tenant->kabupaten_kota,
                'provinsi'            => $tenant->provinsi,
                'email'               => $tenant->email,
                'telepon'             => $tenant->telepon,
                'nama_kepsek'         => 'Dr. H. Maulana Habibi, M.Pd.',
                'nip_kepsek'          => '198507152010011005',
            ]
        );

        // 3. Map Roles ke role_id
        $rolesMap = DB::table('core.roles')->pluck('id', 'nama_role')->toArray();

        $defaultPassword = Hash::make('password123');

        // Super Admin Platform
        User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'tenant_id'     => '00000000-0000-0000-0000-000000000000',
                'role_id'       => $rolesMap['super_admin'] ?? null,
                'email'         => 'superadmin@sinta.id',
                'nama_lengkap'  => 'Super Administrator',
                'password_hash' => $defaultPassword,
                'is_active'     => true,
            ]
        );

        // Admin Sekolah
        User::updateOrCreate(
            ['username' => 'adminsma', 'tenant_id' => $tenant->id],
            [
                'role_id'       => $rolesMap['admin_sekolah'] ?? null,
                'email'         => 'admin@sintanusantara.sch.id',
                'nama_lengkap'  => 'Administrator Sekolah',
                'password_hash' => $defaultPassword,
                'is_active'     => true,
            ]
        );

        // Guru
        User::updateOrCreate(
            ['username' => 'gurusma', 'tenant_id' => $tenant->id],
            [
                'role_id'       => $rolesMap['guru'] ?? null,
                'email'         => 'guru@sintanusantara.sch.id',
                'nama_lengkap'  => 'Ahmad Fauzi, S.Pd.',
                'password_hash' => $defaultPassword,
                'is_active'     => true,
            ]
        );

        // Bendahara / Kasir
        User::updateOrCreate(
            ['username' => 'kasirsma', 'tenant_id' => $tenant->id],
            [
                'role_id'       => $rolesMap['keuangan'] ?? ($rolesMap['staf_keuangan'] ?? null),
                'email'         => 'keuangan@sintanusantara.sch.id',
                'nama_lengkap'  => 'Siti Aminah, S.E.',
                'password_hash' => $defaultPassword,
                'is_active'     => true,
            ]
        );

        $this->command?->info("✔ Seeding TenantDemoSeeder selesai.");
    }
}
