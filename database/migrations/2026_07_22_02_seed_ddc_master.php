<?php
/**
 * Migration: Seed DDC Master Data in perpus_kategori_ddc
 */
return [
    'up' => function (PDO $pdo): void {
        $ddcData = [
            // Top Level 100s
            ['000', 'Ilmu Komputer, Informasi & Karya Umum', NULL, 1],
            ['100', 'Filsafat & Psikologi', NULL, 1],
            ['200', 'Agama', NULL, 1],
            ['300', 'Ilmu Sosial', NULL, 1],
            ['400', 'Bahasa', NULL, 1],
            ['500', 'Sains & Matematika', NULL, 1],
            ['600', 'Teknologi & Ilmu Terapan', NULL, 1],
            ['700', 'Seni & Rekreasi', NULL, 1],
            ['800', 'Sastra', NULL, 1],
            ['900', 'Sejarah & Geografi', NULL, 1],

            // Level 2 Sub-divisions
            ['004', 'Pemrograman & Olah Data Komputer', '000', 2],
            ['005', 'Pemrograman Komputer, Program & Data', '000', 2],
            ['150', 'Psikologi', '100', 2],
            ['297', 'Agama Islam', '200', 2],
            ['330', 'Ekonomi', '300', 2],
            ['370', 'Pendidikan & Pembelajaran', '300', 2],
            ['410', 'Linguistik & Tata Bahasa', '400', 2],
            ['420', 'Bahasa Inggris', '400', 2],
            ['499', 'Bahasa Indonesia & Daerah', '400', 2],
            ['510', 'Matematika', '500', 2],
            ['530', 'Fisika', '500', 2],
            ['540', 'Kimia', '500', 2],
            ['570', 'Biologi', '500', 2],
            ['610', 'Kedokteran & Kesehatan', '600', 2],
            ['650', 'Manajemen & Bisnis', '600', 2],
            ['780', 'Musik', '700', 2],
            ['796', 'Olahraga & Permainan Otot', '700', 2],
            ['810', 'Sastra Indonesia', '800', 2],
            ['820', 'Sastra Inggris', '800', 2],
            ['959', 'Sejarah Indonesia & Asia Tenggara', '900', 2]
        ];

        $stmt = $pdo->prepare("INSERT INTO perpus_kategori_ddc (kode, nama, induk_kode, tingkat) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE nama=VALUES(nama), induk_kode=VALUES(induk_kode), tingkat=VALUES(tingkat)");

        foreach ($ddcData as $row) {
            $stmt->execute($row);
        }

        echo "- Seed data DDC master berhasil ditambahkan.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("DELETE FROM perpus_kategori_ddc;");
        echo "- Rollback data DDC master selesai.\n";
    }
];
