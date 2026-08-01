<?php

require_once __DIR__ . '/../../app/Core/Env.php';
\App\Core\Env::load(dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../app/Config/Database.php';

use App\Config\Database;

try {
    $db = Database::getConnection();
    $db->beginTransaction();

    echo "=== Memulai Eksekusi KelembagaanDummySeeder ===\n";

    // Ambil daftar Tenant ID sekolah aktif (tanpa Pusat Kendali Global Super Admin)
    $tenants = [
        '11111111-1111-1111-1111-111111111111', // SMK Negeri 1 Dummy
        '9c4a0ac5-2c15-4642-bd64-4bb0cac5fc72', // SMA Negeri 1 Dummy
        '488e37fc-fff0-4eb0-a224-dbe2ccc32e6e'  // SMP Negeri 2 Dummy
    ];

    function genUuid(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // -------------------------------------------------------------------------
    // 1. JENJANG (core.jenjang)
    // -------------------------------------------------------------------------
    echo "1. Seeding core.jenjang...\n";
    $jenjangList = [
        ['SD', 'Sekolah Dasar'],
        ['SMP', 'Sekolah Menengah Pertama'],
        ['SMA', 'Sekolah Menengah Atas'],
        ['SMK', 'Sekolah Menengah Kejuruan'],
        ['MI', 'Madrasah Ibtidaiyah'],
        ['MTS', 'Madrasah Tsanawiyah'],
        ['MA', 'Madrasah Aliyah'],
        ['MAK', 'Madrasah Aliyah Kejuruan']
    ];

    $stmtJenjang = $db->prepare("
        INSERT INTO core.jenjang (id, tenant_id, kode_jenjang, nama_jenjang, is_active)
        VALUES (?, ?, ?, ?, true)
        ON CONFLICT DO NOTHING
    ");

    foreach ($tenants as $tId) {
        foreach ($jenjangList as $j) {
            $stmtJenjang->execute([genUuid(), $tId, $j[0], $j[1]]);
        }
    }

    // -------------------------------------------------------------------------
    // 2. JURUSAN (akademik.jurusan)
    // -------------------------------------------------------------------------
    echo "2. Seeding akademik.jurusan...\n";
    $jurusanList = [
        ['Teknik Komputer dan Jaringan', 'TKJ', 'Keahlian bidang jaringan komputer, server, dan infrastruktur IT.'],
        ['Rekayasa Perangkat Lunak', 'RPL', 'Keahlian dalam pengodingan, pemrograman web, mobile, dan sistem basis data.'],
        ['Akuntansi dan Keuangan Lembaga', 'AKL', 'Keahlian dalam pembukuan, laporan keuangan, akuntansi pajak, dan audit.'],
        ['Desain Komunikasi Visual', 'DKV', 'Keahlian dalam multimedia, desain grafis, animasi, dan ilustrasi digital.'],
        ['Ilmu Pengetahuan Alam', 'IPA', 'Peminatan rumpun sains, matematika murni, fisika, kimia, dan biologi.'],
        ['Ilmu Pengetahuan Sosial', 'IPS', 'Peminatan rumpun sosiologi, geografi, ekonomi, dan sejarah.'],
        ['Teknik Kendaraan Ringan', 'TKR', 'Keahlian otomotif, mesin mobil, dan sistem elektronika kendaraan.']
    ];

    $stmtJurusan = $db->prepare("
        INSERT INTO akademik.jurusan (id, tenant_id, nama_jurusan, kategori, deskripsi, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, true, NOW(), NOW())
    ");

    foreach ($tenants as $tId) {
        foreach ($jurusanList as $jur) {
            $stmtJurusan->execute([genUuid(), $tId, $jur[0], $jur[1], $jur[2]]);
        }
    }

    // -------------------------------------------------------------------------
    // 3. KELAS (akademik.kelas)
    // -------------------------------------------------------------------------
    echo "3. Seeding akademik.kelas...\n";
    $kelasList = [
        ['X RPL 1', 'RPL', 'Kelas 10 Rekayasa Perangkat Lunak Rombel 1'],
        ['X RPL 2', 'RPL', 'Kelas 10 Rekayasa Perangkat Lunak Rombel 2'],
        ['X TKJ 1', 'TKJ', 'Kelas 10 Teknik Komputer dan Jaringan Rombel 1'],
        ['XI TKJ 1', 'TKJ', 'Kelas 11 Teknik Komputer dan Jaringan Rombel 1'],
        ['XI AKL 1', 'AKL', 'Kelas 11 Akuntansi Rombel 1'],
        ['XII DKV 1', 'DKV', 'Kelas 12 Desain Komunikasi Visual Rombel 1'],
        ['X IPA 1', 'IPA', 'Kelas 10 MIPA Rombel 1'],
        ['XI IPA 1', 'IPA', 'Kelas 11 MIPA Rombel 1'],
        ['XII IPS 1', 'IPS', 'Kelas 12 IPS Rombel 1'],
        ['VII A', 'REGULER', 'Kelas 7 Rombel A'],
        ['VIII B', 'REGULER', 'Kelas 8 Rombel B'],
        ['IX C', 'REGULER', 'Kelas 9 Rombel C']
    ];

    $stmtKelas = $db->prepare("
        INSERT INTO akademik.kelas (id, tenant_id, nama_kelas, kategori, deskripsi, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, true, NOW(), NOW())
    ");

    foreach ($tenants as $tId) {
        foreach ($kelasList as $k) {
            $stmtKelas->execute([genUuid(), $tId, $k[0], $k[1], $k[2]]);
        }
    }

    // -------------------------------------------------------------------------
    // 4. MATA PELAJARAN (akademik.mata_pelajaran)
    // -------------------------------------------------------------------------
    echo "4. Seeding akademik.mata_pelajaran...\n";
    $mapelList = [
        ['Pendidikan Agama dan Budi Pekerti', 'Wajib A', 'Mata pelajaran pembinaan karakter dan keagamaan.'],
        ['Pancasila dan Kewarganegaraan', 'Wajib A', 'Mata pelajaran pendidikan wawasan kebangsaan.'],
        ['Bahasa Indonesia', 'Wajib A', 'Mata pelajaran literasi dan kebahasaan nasional.'],
        ['Matematika', 'Wajib A', 'Mata pelajaran penalaran numerik dan logika.'],
        ['Bahasa Inggris', 'Wajib A', 'Mata pelajaran komunikasi internasional.'],
        ['Informatika', 'Kejuruan / Sains', 'Mata pelajaran berpikir komputasional dan teknologi digital.'],
        ['Pemrograman Web & Bergerak', 'Kejuruan RPL', 'Mata pelajaran keahlian frontend dan backend web.'],
        ['Administrasi Infrastruktur Jaringan', 'Kejuruan TKJ', 'Mata pelajaran keahlian routing, switching, dan firewall.'],
        ['Fisika Terapan', 'Peminatan IPA', 'Mata pelajaran sains kealaman dan mekanika.'],
        ['Kimia Dasar', 'Peminatan IPA', 'Mata pelajaran struktur zat dan reaksi kimia.'],
        ['Ekonomi & Bisnis', 'Peminatan IPS', 'Mata pelajaran tata kelola finansial dan pasar.']
    ];

    $stmtMapel = $db->prepare("
        INSERT INTO akademik.mata_pelajaran (id, tenant_id, nama_mata_pelajaran, kategori, deskripsi, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, true, NOW(), NOW())
    ");

    foreach ($tenants as $tId) {
        foreach ($mapelList as $mp) {
            $stmtMapel->execute([genUuid(), $tId, $mp[0], $mp[1], $mp[2]]);
        }
    }

    // -------------------------------------------------------------------------
    // 5. PENDIDIKAN (akademik.pendidikan)
    // -------------------------------------------------------------------------
    echo "5. Seeding akademik.pendidikan...\n";
    $pendidikanList = [
        ['Pendidikan Formal Reguler', 'FORMAL', 'Jalur pendidikan terstruktur dari jenjang dasar hingga menengah.'],
        ['Pendidikan Vokasi Kejuruan', 'VOKASI', 'Jalur pendidikan siap kerja berorientasi keahlian praktis.'],
        ['Pendidikan Khusus / Inklusi', 'INKLUSI', 'Jalur layanan pendidikan untuk peserta didik berkebutuhan khusus.'],
        ['Program Keahlian Dwi Sistem (Dual System)', 'DUAL', 'Program magang industri terintegrasi dengan kurikulum kerja.']
    ];

    $stmtPendidikan = $db->prepare("
        INSERT INTO akademik.pendidikan (id, tenant_id, nama_pendidikan, kategori, deskripsi, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, true, NOW(), NOW())
    ");

    foreach ($tenants as $tId) {
        foreach ($pendidikanList as $p) {
            $stmtPendidikan->execute([genUuid(), $tId, $p[0], $p[1], $p[2]]);
        }
    }

    // -------------------------------------------------------------------------
    // 6. PROGRAM PENGAJARAN (akademik.program_pengajaran)
    // -------------------------------------------------------------------------
    echo "6. Seeding akademik.program_pengajaran...\n";
    $programList = [
        ['PROG-REG-01', 'Program Pengajaran Reguler 5 Hari Kerja'],
        ['PROG-REG-02', 'Program Pengajaran Reguler 6 Hari Kerja'],
        ['PROG-VOK-01', 'Program Pengajaran Vokasi & Kelas Industri'],
        ['PROG-BIL-01', 'Program Pengajaran Kelas Bilingual (Bahasa Inggris)'],
        ['PROG-AKS-01', 'Program Akselerasi Akademik Cepat']
    ];

    $stmtProgram = $db->prepare("
        INSERT INTO akademik.program_pengajaran (id, tenant_id, kode_program, nama_program, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, true, NOW(), NOW())
    ");

    foreach ($tenants as $tId) {
        foreach ($programList as $pr) {
            $stmtProgram->execute([genUuid(), $tId, $pr[0], $pr[1]]);
        }
    }

    // -------------------------------------------------------------------------
    // 7. TAHUN AJARAN (akademik.tahun_ajaran)
    // -------------------------------------------------------------------------
    echo "7. Seeding akademik.tahun_ajaran...\n";
    $taList = [
        ['2023/2024', 'Lampau', 'Tahun ajaran akademik 2023/2024 telah usai.'],
        ['2024/2025', 'Aktif', 'Tahun ajaran akademik berjalan saat ini.'],
        ['2025/2026', 'Mendatang', 'Tahun ajaran akademik periode mendatang.'],
        ['2026/2027', 'Mendatang', 'Tahun ajaran akademik perencanaan jangka panjang.']
    ];

    $stmtTa = $db->prepare("
        INSERT INTO akademik.tahun_ajaran (id, tenant_id, nama_tahun_ajaran, kategori, deskripsi, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, true, NOW(), NOW())
    ");

    foreach ($tenants as $tId) {
        foreach ($taList as $ta) {
            $stmtTa->execute([genUuid(), $tId, $ta[0], $ta[1], $ta[2]]);
        }
    }

    // -------------------------------------------------------------------------
    // 8. ANGKATAN (akademik.angkatan)
    // -------------------------------------------------------------------------
    echo "8. Seeding akademik.angkatan...\n";
    $angkatanList = [
        ['2023', 'Angkatan 2023', 'Peserta didik yang diterima pada tahun 2023.'],
        ['2024', 'Angkatan 2024', 'Peserta didik yang diterima pada tahun 2024.'],
        ['2025', 'Angkatan 2025', 'Peserta didik yang diterima pada tahun 2025.'],
        ['2026', 'Angkatan 2026', 'Peserta didik penerimaan baru tahun 2026.']
    ];

    $stmtAngkatan = $db->prepare("
        INSERT INTO akademik.angkatan (id, tenant_id, nama_angkatan, kategori, deskripsi, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, true, NOW(), NOW())
    ");

    foreach ($tenants as $tId) {
        foreach ($angkatanList as $ang) {
            $stmtAngkatan->execute([genUuid(), $tId, $ang[0], $ang[1], $ang[2]]);
        }
    }

    // -------------------------------------------------------------------------
    // 9. KURIKULUM (akademik.ref_kurikulum)
    // -------------------------------------------------------------------------
    echo "9. Seeding akademik.ref_kurikulum...\n";
    $kurikulumList = [
        ['Kurikulum Merdeka (KMA 2024 / BSKAP)', 'sederhana', 'Kurikulum nasional berbasis intrakulikuler dan Proyek Penguatan Profil Pelajar Pancasila (P5).'],
        ['Kurikulum 2013 Revisi 2018', 'klasik', 'Kurikulum berbasis Kompetensi Inti (KI) dan Kompetensi Dasar (KD).'],
        ['Kurikulum Vokasi Industri Dual System', 'kompleks', 'Kurikulum berbasis keahlian praktis terintegrasi langsung dengan standar dunia kerja.']
    ];

    $stmtKurikulum = $db->prepare("
        INSERT INTO akademik.ref_kurikulum (id, tenant_id, nama_ref_kurikulum, kategori, deskripsi, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, true, NOW(), NOW())
    ");

    foreach ($tenants as $tId) {
        foreach ($kurikulumList as $kur) {
            $stmtKurikulum->execute([genUuid(), $tId, $kur[0], $kur[1], $kur[2]]);
        }
    }

    $db->commit();
    echo "\nSELESAI! Dummy Data untuk seluruh 9 Modul Kelembagaan berhasil ditambahkan ke database!\n";

} catch (\Throwable $e) {
    if (isset($db)) $db->rollBack();
    echo "\nERROR Gagal Seeding: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
