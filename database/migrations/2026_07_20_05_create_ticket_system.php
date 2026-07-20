<?php
/**
 * Migration: Create Ticketing System Tables and Menus
 * Location: database/migrations/2026_07_20_05_create_ticket_system.php
 */

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Tabel Kategori Tiket
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nama_kategori` VARCHAR(100) NOT NULL,
            `sla_hours` INT NOT NULL DEFAULT 48
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Seed Kategori Default
        $stmt = $pdo->prepare("INSERT INTO `ticket_categories` (id, nama_kategori, sla_hours) VALUES (?, ?, ?)");
        $categories = [
            [1, 'Laporan Bug / Sistem Error', 24],
            [2, 'Request Fitur / Menu Baru', 168],
            [3, 'Kendala Data Pokok / Dapodik', 48],
            [4, 'Bantuan Penggunaan', 48],
            [5, 'Kritik & Saran', 168]
        ];
        foreach ($categories as $cat) {
            try {
                $stmt->execute($cat);
            } catch (\PDOException $e) {}
        }

        // 2. Tabel FAQ Pintar (Knowledge Base)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_faqs` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `pertanyaan` VARCHAR(255) NOT NULL,
            `jawaban` TEXT NOT NULL,
            `kategori` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Seed FAQ Awal
        $stmtFaq = $pdo->prepare("INSERT INTO `ticket_faqs` (pertanyaan, jawaban, kategori) VALUES (?, ?, ?)");
        $faqs = [
            ['Bagaimana cara mereset password Akun Guru?', 'Untuk mereset password akun guru, silakan hubungi Operator Sekolah Anda untuk melakukan reset melalui menu Manajemen Pengguna.', 'Bantuan Penggunaan'],
            ['Mengapa saya tidak dapat mengakses menu PPDB?', 'Akses menu PPDB dinonaktifkan secara default untuk Guru/Karyawan. Jika Anda ditugaskan menjadi panitia, silakan minta Admin Sekolah untuk memberikan Kunci Akses Khusus melalui profil pengguna Anda.', 'Bantuan Penggunaan'],
            ['Bagaimana cara membetulkan data NISN siswa yang salah?', 'Data NISN ditarik langsung dari sistem Dapodik Kemendikbud. Pastikan data di Dapodik lokal sudah sinkron dengan server pusat, lalu sistem SINTA akan memperbarui data secara berkala.', 'Kendala Data Pokok / Dapodik'],
            ['Aplikasi lambat atau halaman blank putih', 'Silakan lakukan pembersihan cache browser Anda dengan menekan tombol Ctrl + F5 secara bersamaan, atau coba akses menggunakan mode Incognito.', 'Laporan Bug / Sistem Error']
        ];
        foreach ($faqs as $faq) {
            try {
                $stmtFaq->execute($faq);
            } catch (\PDOException $e) {}
        }

        // 3. Tabel Balasan Cepat (Canned Responses)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_canned_responses` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `judul` VARCHAR(100) NOT NULL,
            `konten` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Seed Balasan Cepat Awal
        $stmtCanned = $pdo->prepare("INSERT INTO `ticket_canned_responses` (judul, konten) VALUES (?, ?)");
        $canneds = [
            ['Panduan Reset Password', "Halo,\n\nUntuk kendala lupa password atau reset password akun, Administrator Sekolah Anda dapat meresetnya secara langsung melalui menu:\n1. Masuk ke menu \"Pengaturan & Utilitas\" -> \"Manajemen Pengguna\".\n2. Cari nama Anda, klik aksi Edit.\n3. Masukkan password baru dan simpan.\n\nTerima kasih."],
            ['Perbaikan Bug Selesai', "Halo,\n\nTerima kasih atas laporan Anda. Laporan bug ini telah berhasil kami perbaiki dan rilis pada pembaruan sistem terbaru. Silakan muat ulang halaman (Ctrl + F5) dan coba kembali.\n\nSalam,\nTim IT Support SINTA-SaaS"],
            ['Data Menunggu Sinkronisasi', "Halo,\n\nPerubahan data pokok/dapodik memerlukan waktu sinkronisasi berkala antara server lokal dan cloud. Silakan tunggu 1x24 jam. Jika data belum berubah, hubungi operator dapodik sekolah untuk memverifikasi status sinkronisasi.\n\nTerima kasih."]
        ];
        foreach ($canneds as $canned) {
            try {
                $stmtCanned->execute($canned);
            } catch (\PDOException $e) {}
        }

        // 4. Tabel Utama Tiket (SaaS Multi-Tenant Safe & UUID)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tickets` (
            `id` CHAR(36) PRIMARY KEY,
            `tenant_id` CHAR(36) NOT NULL,
            `user_id` CHAR(36) NOT NULL,
            `category_id` INT UNSIGNED NOT NULL,
            `judul` VARCHAR(255) NOT NULL,
            `deskripsi` TEXT NOT NULL,
            `urgensi` ENUM('Rendah', 'Sedang', 'Tinggi', 'Kritis') DEFAULT 'Rendah',
            `status` ENUM('Menunggu', 'Diproses', 'Selesai', 'Batal') DEFAULT 'Menunggu',
            `lampiran` VARCHAR(255) DEFAULT NULL,
            `user_agent` VARCHAR(255) DEFAULT NULL,
            `last_url` VARCHAR(255) DEFAULT NULL,
            `user_unread` TINYINT(1) DEFAULT 0,
            `admin_unread` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `sla_deadline` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_tickets_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `ticket_categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 5. Tabel Balasan Percakapan (Thread)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_replies` (
            `id` CHAR(36) PRIMARY KEY,
            `ticket_id` CHAR(36) NOT NULL,
            `user_id` CHAR(36) NOT NULL,
            `is_superadmin` TINYINT(1) DEFAULT 0,
            `pesan` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // 6. Registrasi Menu Baru: Pusat Bantuan (ID: 61)
        $stmtMenu = $pdo->prepare("INSERT INTO `menus` (id, nama_menu, url, parent_id, icon, urutan) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmtMenu->execute([61, 'Pusat Bantuan', '/SINTA-SaaS/bantuan', null, 'bi bi-question-circle', 100]);
        } catch (\PDOException $e) {}

        // 7. Berikan Akses Menu Default ke Semua Role
        $tenants = $pdo->query("SELECT id FROM tenants WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        $roles = $pdo->query("SELECT id FROM roles")->fetchAll(PDO::FETCH_COLUMN);

        // Tambah ke tenant_menu_access
        $stmtTMA = $pdo->prepare("INSERT INTO `tenant_menu_access` (tenant_id, menu_id) VALUES (?, 61)");
        foreach ($tenants as $tId) {
            try {
                $stmtTMA->execute([$tId]);
            } catch (\PDOException $e) {}
        }

        // Tambah ke role_menu_access
        $stmtRMA = $pdo->prepare("INSERT INTO `role_menu_access` (tenant_id, role_id, menu_id) VALUES (?, ?, 61)");
        foreach ($tenants as $tId) {
            foreach ($roles as $rId) {
                try {
                    $stmtRMA->execute([$tId, $rId]);
                } catch (\PDOException $e) {}
            }
        }
        // Tambahkan juga untuk global tenant fallback
        foreach ($roles as $rId) {
            try {
                $stmtRMA->execute(['00000000-0000-0000-0000-000000000000', $rId]);
            } catch (\PDOException $e) {}
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Tabel-tabel Pusat Bantuan dan registrasi menu berhasil dibuat.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DELETE FROM `role_menu_access` WHERE menu_id = 61");
        $pdo->exec("DELETE FROM `tenant_menu_access` WHERE menu_id = 61");
        $pdo->exec("DELETE FROM `menus` WHERE id = 61");
        $pdo->exec("DROP TABLE IF EXISTS `ticket_replies`");
        $pdo->exec("DROP TABLE IF EXISTS `tickets`");
        $pdo->exec("DROP TABLE IF EXISTS `ticket_canned_responses`");
        $pdo->exec("DROP TABLE IF EXISTS `ticket_faqs`");
        $pdo->exec("DROP TABLE IF EXISTS `ticket_categories`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "- Tabel-tabel Pusat Bantuan berhasil di-rollback.\n";
    },
];
