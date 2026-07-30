<?php
/**
 * Migration 010 - Seed Wilayah from CSV Files
 * Membaca data CSV untuk provinsi, kota, kecamatan, kelurahan secara batch insert, dan mengisi default settings.
 */

return [
    'up' => function(PDO $pdo) {
        // Matikan index & foreign key checks agar proses insert batch besar berjalan sangat cepat
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("SET UNIQUE_CHECKS = 0;");

        // Lokasi CSV di folder lokal seeds
        $basePath = __DIR__ . '/../seeds/';

        // 1. Seed Provinsi
        $provinsiFile = $basePath . 'provinsi.csv';
        echo "Mengimpor data provinsi...\n";
        seedCsvData($pdo, $provinsiFile, 'provinsi', ['id_provinsi', 'nama_provinsi']);

        // 2. Seed Kota
        $kotaFile = $basePath . 'kota.csv';
        echo "Mengimpor data kota...\n";
        seedCsvData($pdo, $kotaFile, 'kota', ['id_kota', 'id_provinsi', 'nama_kota']);

        // 3. Seed Kecamatan
        $kecamatanFile = $basePath . 'kecamatan.csv';
        echo "Mengimpor data kecamatan...\n";
        seedCsvData($pdo, $kecamatanFile, 'kecamatan', ['id_kecamatan', 'id_kota', 'nama_kecamatan']);

        // 4. Seed Kelurahan
        $kelurahanFile = $basePath . 'kelurahan.csv';
        echo "Mengimpor data kelurahan (83.000+ baris)...\n";
        seedCsvData($pdo, $kelurahanFile, 'kelurahan', ['id_kelurahan', 'id_kecamatan', 'nama_kelurahan']);

        // 5. Seed Pengaturan
        echo "Mengimpor default data pengaturan...\n";
        $pdo->exec("INSERT INTO pengaturan (id_pengaturan, nama_pengaturan, nilai) VALUES
            (1, 'nama_sekolah', NULL),
            (2, 'email_sekolah', NULL),
            (3, 'no_telepon_sekolah', NULL),
            (4, 'alamat_sekolah', NULL),
            (5, 'nama_kepala_sekolah', NULL),
            (6, 'nip_kepala_sekolah', NULL),
            (7, 'npsn_sekolah', NULL),
            (8, 'logo_sekolah', NULL)
        ON DUPLICATE KEY UPDATE nama_pengaturan=VALUES(nama_pengaturan);");

        $pdo->exec("SET UNIQUE_CHECKS = 1;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE kelurahan;");
        $pdo->exec("TRUNCATE TABLE kecamatan;");
        $pdo->exec("TRUNCATE TABLE kota;");
        $pdo->exec("TRUNCATE TABLE provinsi;");
        $pdo->exec("TRUNCATE TABLE pengaturan;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
];

/**
 * Fungsi helper untuk membaca CSV dengan pembatas semicolon (;) dan melakukan batch insert
 */
function seedCsvData(PDO $pdo, string $filePath, string $tableName, array $columns): void {
    if (!file_exists($filePath)) {
        echo "Peringatan: File {$filePath} tidak ditemukan. Proses seeder untuk tabel {$tableName} dilewati.\n";
        return;
    }

    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        echo "Galat: Gagal membuka file {$filePath}.\n";
        return;
    }

    // Lewati baris header CSV
    $header = fgetcsv($handle, 0, ';');

    $batchSize = 1000;
    $rows = [];
    $count = 0;

    $colNames = implode(', ', $columns);
    $sqlBase = "INSERT INTO {$tableName} ({$colNames}) VALUES ";

    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        // Bersihkan data spasi liar
        $data = array_map(function($val) {
            $trimmed = trim($val);
            return $trimmed === '' ? null : $trimmed;
        }, $data);

        // Abaikan baris jika jumlah kolom tidak sesuai
        if (count($data) !== count($columns)) {
            continue;
        }

        $rows[] = $data;
        $count++;

        // Jika buffer mencapai ukuran batch, langsung eksekusi insert
        if (count($rows) >= $batchSize) {
            executeInsertBatch($pdo, $sqlBase, $rows, count($columns));
            $rows = [];
        }
    }

    // Kirim sisa baris di buffer
    if (count($rows) > 0) {
        executeInsertBatch($pdo, $sqlBase, $rows, count($columns));
    }

    fclose($handle);
    echo "Sukses: Berhasil memasukkan {$count} baris ke tabel {$tableName}.\n";
}

/**
 * Helper untuk menyusun multi-row insert statement dan mengeksekusi secara atomic
 */
function executeInsertBatch(PDO $pdo, string $sqlBase, array $rows, int $colCount): void {
    $batchCount = count($rows);
    $placeholders = array_fill(0, $batchCount, '(' . implode(', ', array_fill(0, $colCount, '?')) . ')');
    $sql = $sqlBase . implode(', ', $placeholders);

    $stmt = $pdo->prepare($sql);

    $flatValues = [];
    foreach ($rows as $row) {
        foreach ($row as $val) {
            $flatValues[] = $val;
        }
    }

    $stmt->execute($flatValues);
}
