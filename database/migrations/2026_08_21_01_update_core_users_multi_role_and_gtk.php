<?php

/**
 * Migration PostgreSQL: Update core.users GTK & Multi-Role Attributes
 * Format: return ['up' => closure, 'down' => closure] (WAJIB AGENTS.md)
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== MENJALANKAN MIGRASI: CORE USERS GTK & MULTI-ROLE ATTRIBUTES ===\n";

        // 1. Tambahkan kolom GTK pada core.users jika belum ada
        $pdo->exec("
            ALTER TABLE core.users 
            ADD COLUMN IF NOT EXISTS nip VARCHAR(50) NULL,
            ADD COLUMN IF NOT EXISTS nuptk VARCHAR(50) NULL,
            ADD COLUMN IF NOT EXISTS jenis_gtk VARCHAR(50) NULL DEFAULT 'Guru',
            ADD COLUMN IF NOT EXISTS jabatan_struktural VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS status_kepegawaian VARCHAR(50) NULL DEFAULT 'GTY/PTY',
            ADD COLUMN IF NOT EXISTS jam_mengajar SMALLINT NULL DEFAULT 0,
            ADD COLUMN IF NOT EXISTS status_sertifikasi BOOLEAN NOT NULL DEFAULT false,
            ADD COLUMN IF NOT EXISTS no_hp VARCHAR(30) NULL,
            ADD COLUMN IF NOT EXISTS alamat TEXT NULL,
            ADD COLUMN IF NOT EXISTS jenis_kelamin CHAR(1) NULL;
        ");

        // 2. Buat indeks untuk performa pencarian GTK
        $pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_users_tenant_nip ON core.users(tenant_id, nip);
            CREATE INDEX IF NOT EXISTS idx_users_tenant_nuptk ON core.users(tenant_id, nuptk);
            CREATE INDEX IF NOT EXISTS idx_users_tenant_jenis_gtk ON core.users(tenant_id, jenis_gtk);
        ");

        // 3. Pastikan standard roles lengkap di core.roles
        $standardRoles = [
            ['super_admin', 'Super Administrator Platform'],
            ['admin', 'Administrator Sekolah / Kepala Sekolah'],
            ['operator_sekolah', 'Operator Sekolah'],
            ['guru', 'Guru Pengajar'],
            ['karyawan', 'Tenaga Kependidikan / Tata Usaha'],
            ['siswa', 'Siswa'],
            ['wali_kelas', 'Wali Kelas'],
            ['guru_bk', 'Guru Bimbingan Konseling'],
            ['kesiswaan', 'Tim Kesiswaan / Pembina'],
            ['kurikulum', 'Tim Kurikulum / Pengajaran'],
            ['sarpras', 'Pengelola Sarana Prasarana'],
            ['humas', 'Hubungan Masyarakat'],
            ['perpustakaan', 'Pengelola Perpustakaan'],
            ['keuangan', 'Bendahara / Kasir Sekolah'],
            ['pembina_ekskul', 'Pembina Ekstrakurikuler']
        ];

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM core.roles WHERE nama_role = :role");
        $stmtInsert = $pdo->prepare("INSERT INTO core.roles (id, nama_role, deskripsi) VALUES (gen_random_uuid(), :role, :deskripsi)");

        foreach ($standardRoles as $r) {
            $stmtCheck->execute(['role' => $r[0]]);
            if ((int)$stmtCheck->fetchColumn() === 0) {
                $stmtInsert->execute(['role' => $r[0], 'deskripsi' => $r[1]]);
            }
        }

        echo "- Berhasil menambahkan kolom GTK pada core.users dan memastikan standard roles lengkap.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE core.users 
            DROP COLUMN IF EXISTS nip,
            DROP COLUMN IF EXISTS nuptk,
            DROP COLUMN IF EXISTS jenis_gtk,
            DROP COLUMN IF EXISTS jabatan_struktural,
            DROP COLUMN IF EXISTS status_kepegawaian,
            DROP COLUMN IF EXISTS jam_mengajar,
            DROP COLUMN IF EXISTS status_sertifikasi,
            DROP COLUMN IF EXISTS no_hp,
            DROP COLUMN IF EXISTS alamat,
            DROP COLUMN IF EXISTS jenis_kelamin;
        ");
        echo "- Kolom GTK pada core.users berhasil di-rollback.\n";
    }
];
