<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\User;
use Modules\Core\Entities\SekolahIdentitas;
use Modules\Akademik\Entities\TahunAjaran;
use Modules\Akademik\Entities\Jurusan;
use Modules\Akademik\Entities\Kelas;
use Modules\Akademik\Entities\MataPelajaran;
use Modules\Siswa\Entities\Siswa;
use Modules\Keuangan\Entities\PosKeuangan;
use Modules\Keuangan\Entities\TarifPembayaran;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\KasBank;

class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Tenant Demo SMA SINTA Nusantara
        $tenant = Tenant::updateOrCreate(
            ['npsn' => '20269999'],
            [
                'nama_sekolah'       => 'SMA SINTA Unggulan Nusantara',
                'jenjang'            => 'SMA',
                'status_sekolah'     => 'Swasta',
                'alamat_jalan'       => 'Jl. Pendidikan Karakter No. 88',
                'kabupaten_kota'     => 'Jakarta Selatan',
                'provinsi'           => 'DKI Jakarta',
                'email'              => 'info@sintanusantara.sch.id',
                'nomor_telepon'      => '021-78901234',
                'paket_aktif'        => 'Enterprise',
                'storage_limit_mb'   => 10240, // 10 GB
                'storage_used_bytes' => 125829120, // 120 MB
                'status_langganan'   => 'Aktif',
                'is_active'          => true,
            ]
        );

        // 2. Buat Identitas Sekolah
        SekolahIdentitas::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'nama_sekolah'        => $tenant->nama_sekolah,
                'npsn'                => $tenant->npsn,
                'akreditasi'          => 'A',
                'kurikulum'           => 'Kurikulum Merdeka',
                'alamat'              => $tenant->alamat_jalan,
                'kabupaten_kota'      => $tenant->kabupaten_kota,
                'provinsi'            => $tenant->provinsi,
                'email'               => $tenant->email,
                'nomor_telepon'       => $tenant->nomor_telepon,
                'nama_kepala_sekolah' => 'Dr. H. Maulana Habibi, M.Pd.',
                'nip_kepala_sekolah'  => '198507152010011005',
                'is_active'           => true,
            ]
        );

        // 3. Buat Pengguna untuk Setiap Peran
        $defaultPassword = Hash::make('password123');

        // Super Admin Platform
        $superAdmin = User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'tenant_id'    => null,
                'email'        => 'superadmin@sinta.id',
                'nama_lengkap' => 'Super Administrator SaaS',
                'password'     => $defaultPassword,
                'role'         => 'super_admin',
                'is_active'    => true,
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        // Admin Sekolah
        $admin = User::updateOrCreate(
            ['username' => 'adminsma', 'tenant_id' => $tenant->id],
            [
                'email'        => 'admin@sintanusantara.sch.id',
                'nama_lengkap' => 'Administrator Sekolah',
                'password'     => $defaultPassword,
                'role'         => 'admin_sekolah',
                'is_active'    => true,
            ]
        );
        $admin->syncRoles(['admin_sekolah']);

        // Guru
        $guru = User::updateOrCreate(
            ['username' => 'gurusma', 'tenant_id' => $tenant->id],
            [
                'email'        => 'guru@sintanusantara.sch.id',
                'nama_lengkap' => 'Ahmad Fauzi, S.Pd.',
                'password'     => $defaultPassword,
                'role'         => 'guru',
                'is_active'    => true,
            ]
        );
        $guru->syncRoles(['guru']);

        // Bendahara / Kasir
        $kasir = User::updateOrCreate(
            ['username' => 'kasirsma', 'tenant_id' => $tenant->id],
            [
                'email'        => 'keuangan@sintanusantara.sch.id',
                'nama_lengkap' => 'Siti Aminah, S.E.',
                'password'     => $defaultPassword,
                'role'         => 'staf_keuangan',
                'is_active'    => true,
            ]
        );
        $kasir->syncRoles(['staf_keuangan']);

        // 4. Data Master Akademik
        $ta = TahunAjaran::updateOrCreate(
            ['tenant_id' => $tenant->id, 'nama_tahun_ajaran' => '2026/2027', 'semester' => 'Ganjil'],
            ['is_active' => true, 'tanggal_mulai' => '2026-07-15', 'tanggal_selesai' => '2026-12-20']
        );

        $mipa = Jurusan::updateOrCreate(
            ['tenant_id' => $tenant->id, 'kode_jurusan' => 'MIPA'],
            ['nama_jurusan' => 'Matematika dan Ilmu Pengetahuan Alam', 'is_active' => true]
        );

        $kelas10 = Kelas::updateOrCreate(
            ['tenant_id' => $tenant->id, 'nama_kelas' => 'X MIPA 1'],
            ['tingkat' => 'X', 'kode_kelas' => 'X-MIPA-1', 'jurusan_id' => $mipa->id, 'tahun_ajaran_id' => $ta->id, 'kapasitas' => 36, 'is_active' => true]
        );

        MataPelajaran::updateOrCreate(
            ['tenant_id' => $tenant->id, 'kode_mapel' => 'MAT-WAJIB'],
            ['nama_mapel' => 'Matematika Umum', 'kelompok' => 'Umum', 'kkm_default' => 75, 'is_active' => true]
        );

        // 5. Data Siswa Demo
        $siswa = Siswa::updateOrCreate(
            ['tenant_id' => $tenant->id, 'nisn' => '0089123456'],
            [
                'nis'            => '20261001',
                'nik'            => '3171012345670001',
                'nama_lengkap'   => 'Muhammad Rizky Pratama',
                'nama_panggilan' => 'Rizky',
                'jenis_kelamin'  => 'L',
                'tempat_lahir'   => 'Jakarta',
                'tanggal_lahir'  => '2009-05-12',
                'agama'          => 'Islam',
                'kelas_saat_ini' => $kelas10->nama_kelas,
                'jurusan'        => $mipa->nama_jurusan,
                'angkatan'       => '2026',
                'nama_ayah'      => 'Bambang Supriyanto',
                'nama_ibu'       => 'Dewi Lestari',
                'no_hp'          => '081298765432',
                'alamat_tinggal' => 'Jl. Kebagusan Raya No. 45, Jakarta Selatan',
                'status_siswa'   => 'Aktif',
            ]
        );

        // 6. Data Kas Bank & Tagihan SPP
        $kas = KasBank::updateOrCreate(
            ['tenant_id' => $tenant->id, 'kode_akun' => '1101'],
            ['nama_kas_bank' => 'Kas Operasional Sekolah', 'saldo_saat_ini' => 15000000, 'is_active' => true]
        );

        $posSpp = PosKeuangan::updateOrCreate(
            ['tenant_id' => $tenant->id, 'kode_pos' => 'SPP-BLN'],
            ['nama_pos' => 'SPP Bulanan', 'tipe_pembayaran' => 'Bulanan', 'is_active' => true]
        );

        TarifPembayaran::updateOrCreate(
            ['tenant_id' => $tenant->id, 'pos_keuangan_id' => $posSpp->id, 'tingkat' => 'X'],
            ['nominal' => 350000]
        );

        TagihanSiswa::updateOrCreate(
            ['tenant_id' => $tenant->id, 'siswa_id' => $siswa->id, 'bulan' => 7, 'tahun' => 2026],
            [
                'pos_keuangan_id'     => $posSpp->id,
                'tahun_ajaran_id'     => $ta->id,
                'nomor_tagihan'       => 'INV/2026/07/00101',
                'total_tagihan'       => 350000,
                'total_terbayar'      => 0,
                'sisa_tagihan'        => 350000,
                'status_pembayaran'   => 'Belum Bayar',
                'tanggal_jatuh_tempo' => '2026-07-10',
            ]
        );
    }
}
