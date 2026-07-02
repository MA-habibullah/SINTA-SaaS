<?php
/**
 * Migration: Create Pembinaan 3M Tables
 */
return [
    'up' => function (PDO $pdo): void {
        // 1. Tabel guru_monitoring
        $pdo->exec("CREATE TABLE IF NOT EXISTS guru_monitoring (
            id CHAR(36) PRIMARY KEY,
            tenant_id CHAR(36) NOT NULL,
            guru_id CHAR(36) NOT NULL,
            kategori_masalah ENUM('Kedisiplinan', 'Akademik', 'Personal') NOT NULL,
            sumber_deteksi ENUM('Sistem Otomatis', 'Manual Atasan', 'Pengajuan Mandiri') NOT NULL,
            deskripsi_kasus TEXT NOT NULL,
            status_kasus ENUM('Merah', 'Kuning', 'Hijau') NOT NULL DEFAULT 'Merah',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "  OK Tabel guru_monitoring berhasil dibuat.\n";

        // 2. Tabel sesi_mentoring
        $pdo->exec("CREATE TABLE IF NOT EXISTS sesi_mentoring (
            id CHAR(36) PRIMARY KEY,
            tenant_id CHAR(36) NOT NULL,
            monitoring_id CHAR(36) NOT NULL,
            kepsek_id CHAR(36) NOT NULL,
            tanggal_sesi DATETIME NOT NULL,
            catatan_fakta TEXT,
            rencana_tindak_lanjut TEXT,
            ttd_digital_kepsek LONGTEXT,
            ttd_digital_guru LONGTEXT,
            dokumen_komitmen_pdf VARCHAR(255),
            status_sesi ENUM('Dijadwalkan', 'Berlangsung', 'Selesai') NOT NULL DEFAULT 'Dijadwalkan',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (monitoring_id) REFERENCES guru_monitoring(id) ON DELETE CASCADE,
            FOREIGN KEY (kepsek_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "  OK Tabel sesi_mentoring berhasil dibuat.\n";

        // 3. Tabel evaluasi_pemantauan
        $pdo->exec("CREATE TABLE IF NOT EXISTS evaluasi_pemantauan (
            id CHAR(36) PRIMARY KEY,
            tenant_id CHAR(36) NOT NULL,
            mentoring_id CHAR(36) NOT NULL,
            tanggal_evaluasi DATE NOT NULL,
            hasil_evaluasi ENUM('Membaik', 'Tetap', 'Memburuk') NOT NULL,
            tindakan_lanjutan ENUM('Selesai', 'Perpanjang', 'Teguran') NOT NULL,
            catatan_perkembangan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (mentoring_id) REFERENCES sesi_mentoring(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "  OK Tabel evaluasi_pemantauan berhasil dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS evaluasi_pemantauan");
        $pdo->exec("DROP TABLE IF EXISTS sesi_mentoring");
        $pdo->exec("DROP TABLE IF EXISTS guru_monitoring");
        echo "  OK Tabel-tabel Pembinaan 3M berhasil dihapus.\n";
    }
];
