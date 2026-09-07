<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Daftar 9 Peran Utama SINTA-SaaS
        $roles = [
            'super_admin'    => 'Super Administrator Platform SaaS',
            'admin_sekolah'  => 'Administrator Lembaga / Sekolah',
            'kepala_sekolah' => 'Kepala Sekolah',
            'guru'           => 'Guru Mata Pelajaran',
            'wali_kelas'     => 'Wali Kelas',
            'staf_tu'        => 'Staf Tata Usaha & Administrasi',
            'staf_keuangan'  => 'Bendahara & Kasir Keuangan',
            'siswa'          => 'Peserta Didik / Siswa',
            'orang_tua'      => 'Orang Tua / Wali Murid',
        ];

        foreach ($roles as $roleName => $label) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Daftar Hak Akses Granular (Permissions)
        $permissions = [
            // Core
            'core.tenants.manage',
            'core.users.manage',
            'core.identitas.manage',
            
            // Siswa & PPDB
            'siswa.buku-induk.view',
            'siswa.buku-induk.create',
            'siswa.buku-induk.edit',
            'siswa.buku-induk.delete',
            'siswa.ppdb.manage',
            'siswa.mutasi.manage',
            'siswa.prestasi.manage',

            // Akademik & Rapor
            'akademik.master.manage',
            'akademik.penilaian.input',
            'akademik.rapor.view',
            'akademik.rapor.print',

            // Keuangan
            'keuangan.tarif.manage',
            'keuangan.tagihan.manage',
            'keuangan.kasir.transaksi',
            'keuangan.laporan.view',

            // Modul Khusus
            'bk.konseling.manage',
            'pdss.ranking.manage',
            'perpustakaan.sirkulasi.manage',
            'persuratan.disposisi.manage',
            'sarpras.aset.manage',
            'smk.pkl.manage',
            'tracer.survey.manage',
            'absensi.tap.manage',
            'kepegawaian.gtk.manage',
            'cms.berita.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 3. Mapping Hak Akses ke Peran
        // Super Admin memiliki semua hak akses
        $superAdmin = Role::findByName('super_admin');
        $superAdmin->givePermissionTo(Permission::all());

        // Admin Sekolah
        $adminSekolah = Role::findByName('admin_sekolah');
        $adminSekolah->givePermissionTo([
            'core.users.manage', 'core.identitas.manage',
            'siswa.buku-induk.view', 'siswa.buku-induk.create', 'siswa.buku-induk.edit', 'siswa.buku-induk.delete',
            'siswa.ppdb.manage', 'siswa.mutasi.manage', 'siswa.prestasi.manage',
            'akademik.master.manage', 'akademik.rapor.view', 'akademik.rapor.print',
            'keuangan.tarif.manage', 'keuangan.tagihan.manage', 'keuangan.kasir.transaksi', 'keuangan.laporan.view',
            'bk.konseling.manage', 'pdss.ranking.manage', 'perpustakaan.sirkulasi.manage',
            'persuratan.disposisi.manage', 'sarpras.aset.manage', 'smk.pkl.manage',
            'tracer.survey.manage', 'absensi.tap.manage', 'kepegawaian.gtk.manage', 'cms.berita.manage',
        ]);

        // Guru & Wali Kelas
        $guru = Role::findByName('guru');
        $guru->givePermissionTo([
            'siswa.buku-induk.view',
            'akademik.penilaian.input',
            'akademik.rapor.view',
            'absensi.tap.manage',
        ]);

        $waliKelas = Role::findByName('wali_kelas');
        $waliKelas->givePermissionTo([
            'siswa.buku-induk.view',
            'akademik.penilaian.input',
            'akademik.rapor.view',
            'akademik.rapor.print',
            'absensi.tap.manage',
            'bk.konseling.manage',
        ]);

        // Staf Keuangan / Kasir
        $stafKeuangan = Role::findByName('staf_keuangan');
        $stafKeuangan->givePermissionTo([
            'siswa.buku-induk.view',
            'keuangan.tagihan.manage',
            'keuangan.kasir.transaksi',
            'keuangan.laporan.view',
        ]);

        // Siswa & Orang Tua
        $siswaRole = Role::findByName('siswa');
        $siswaRole->givePermissionTo([
            'akademik.rapor.view',
            'perpustakaan.sirkulasi.manage',
        ]);

        $orangTua = Role::findByName('orang_tua');
        $orangTua->givePermissionTo([
            'akademik.rapor.view',
        ]);
    }
}
