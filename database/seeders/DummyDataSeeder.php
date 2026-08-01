<?php
require_once __DIR__ . '/../../app/Core/Env.php';
\App\Core\Env::load(dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../app/Config/Database.php';

use App\Config\Database;

try {
    $db = Database::getConnection();
    $db->beginTransaction();

    echo "=== Memulai eksekusi DummyDataSeeder ===\n";

    // 1. Bersihkan Data Dummy Lama (akan otomatis Cascade ke Users dan Siswa)
    echo "1. Membersihkan data dummy lama (jika ada)...\n";
    $db->exec("DELETE FROM core.tenants WHERE npsn = '12345678'");

    // 2. Buat Tenant Sekolah Dummy
    echo "2. Membuat Data Sekolah (Tenant)...\n";
    $tenantId = '11111111-1111-1111-1111-111111111111';
    $stmtTenant = $db->prepare("INSERT INTO core.tenants (id, nama_sekolah, npsn, subdomain, status, paket_aktif) VALUES (?, 'SMK Negeri 1 Dummy', '12345678', 'smkn1dummy', 'active', 'Premium SaaS')");
    $stmtTenant->execute([$tenantId]);

    // 3. Buat Role Dasar (Jika belum ada)
    echo "3. Menyiapkan Roles...\n";
    $roles = [
        'admin_sekolah' => '22222222-2222-2222-2222-222222222221',
        'guru' => '22222222-2222-2222-2222-222222222222',
        'siswa' => '22222222-2222-2222-2222-222222222223'
    ];
    $stmtRole = $db->prepare("INSERT INTO core.roles (id, nama_role, deskripsi) VALUES (?, ?, ?) ON CONFLICT (nama_role) DO NOTHING");
    $stmtRole->execute([$roles['admin_sekolah'], 'admin_sekolah', 'Administrator tingkat sekolah/tenant']);
    $stmtRole->execute([$roles['guru'], 'guru', 'Tenaga Pengajar']);
    $stmtRole->execute([$roles['siswa'], 'siswa', 'Peserta Didik']);

    // Karena menggunakan ON CONFLICT DO NOTHING, kita perlu ambil ID pastinya untuk insert users
    foreach ($roles as $namaRole => &$idRole) {
        $stmtGetRole = $db->prepare("SELECT id FROM core.roles WHERE nama_role = ?");
        $stmtGetRole->execute([$namaRole]);
        $idRole = $stmtGetRole->fetchColumn();
    }

    // 4. Buat Akun Users Dummy (Admin & Guru)
    echo "4. Membuat Akun Pengguna (Admin & Guru)...\n";
    $password = password_hash('password123', PASSWORD_BCRYPT);
    $stmtUser = $db->prepare("INSERT INTO core.users (id, tenant_id, role_id, username, email, password_hash, nama_lengkap, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, true)");
    
    // Admin Sekolah
    $adminId = '33333333-3333-3333-3333-333333333331';
    $stmtUser->execute([$adminId, $tenantId, $roles['admin_sekolah'], 'admin_smk1', 'admin@smk1dummy.sch.id', $password, 'Budi Administrator']);
    
    // 2 Guru
    $guru1Id = '33333333-3333-3333-3333-333333333332';
    $guru2Id = '33333333-3333-3333-3333-333333333333';
    $stmtUser->execute([$guru1Id, $tenantId, $roles['guru'], 'guru_matematika', 'guru1@smk1dummy.sch.id', $password, 'Siti Matematika, S.Pd']);
    $stmtUser->execute([$guru2Id, $tenantId, $roles['guru'], 'guru_bahasa', 'guru2@smk1dummy.sch.id', $password, 'Andi Bahasa, M.Pd']);

    // 5. Buat Data Siswa Dummy dan Orang Tua
    echo "5. Membuat Data Siswa & Orang Tua...\n";
    $stmtSiswa = $db->prepare("
        INSERT INTO siswa.siswa (id, tenant_id, nisn, nis, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, kelas_saat_ini, status_siswa)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
    ");
    $stmtOrtu = $db->prepare("
        INSERT INTO siswa.orang_tua (siswa_id, tenant_id, hubungan, nama_lengkap, pekerjaan, no_hp)
        VALUES (?, ?, 'ayah', ?, ?, ?)
    ");

    $dataSiswa = [
        ['44444444-4444-4444-4444-444444444441', '0010000001', '22231001', 'Ahmad Siswa Pertama', 'L', 'Jakarta', '2008-01-15', 'Islam', 'X RPL 1', 'Ayah Ahmad', 'PNS', '081200000001'],
        ['44444444-4444-4444-4444-444444444442', '0010000002', '22231002', 'Bunga Siswi Kedua', 'P', 'Bandung', '2008-05-20', 'Islam', 'X RPL 1', 'Ayah Bunga', 'Wiraswasta', '081200000002'],
        ['44444444-4444-4444-4444-444444444443', '0010000003', '22231003', 'Caca Siswi Ketiga', 'P', 'Surabaya', '2008-08-08', 'Kristen', 'X TKJ 2', 'Ayah Caca', 'Pegawai Swasta', '081200000003'],
        ['44444444-4444-4444-4444-444444444444', '0010000004', '22231004', 'Doni Siswa Keempat', 'L', 'Semarang', '2007-12-12', 'Islam', 'XI RPL 1', 'Ayah Doni', 'TNI/Polri', '081200000004'],
        ['44444444-4444-4444-4444-444444444445', '0010000005', '22231005', 'Eko Siswa Kelima', 'L', 'Malang', '2007-03-03', 'Hindu', 'XII TKJ 1', 'Ayah Eko', 'Petani', '081200000005'],
    ];

    foreach ($dataSiswa as $index => $s) {
        $siswaId = $s[0];
        // Insert Siswa Profile
        $stmtSiswa->execute([
            $siswaId, $tenantId, $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8]
        ]);
        
        // Insert Akun Login Siswa ke tabel core.users
        $userId = '55555555-5555-5555-5555-55555555555' . ($index + 1);
        $username = strtolower(explode(' ', $s[3])[0]) . $s[2]; // e.g. ahmad22231001
        $email = $username . '@siswa.smk1dummy.sch.id';
        $stmtUser->execute([
            $userId, $tenantId, $roles['siswa'], $username, $email, $password, $s[3]
        ]);

        // Insert Data Orang Tua Siswa
        $stmtOrtu->execute([
            $siswaId, $tenantId, $s[9], $s[10], $s[11]
        ]);
    }

    $db->commit();
    echo "SELESAI! DummyDataSeeder berhasil mengeksekusi semua data.\n";
    echo "\n=== KREDENSIAL UJI COBA ===\n";
    echo "- Admin Sekolah : admin@smk1dummy.sch.id / password123\n";
    echo "- Guru 1        : guru1@smk1dummy.sch.id / password123\n";
    echo "- Siswa 1       : ahmad22231001@siswa.smk1dummy.sch.id / password123\n";
} catch (\Exception $e) {
    if (isset($db)) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
