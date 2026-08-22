<?php

/**
 * Seeder Standar Nasional Kode Klasifikasi Surat Dinas (Kemendikbud / Kemenag / ANRI)
 * Lokasi: database/seeds/SeedKodeKlasifikasiSurat.php
 * Eksekusi: php database/seeds/SeedKodeKlasifikasiSurat.php
 */

define('BASE_DIR', realpath(__DIR__ . '/../../'));

$envFile = BASE_DIR . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
        putenv(trim($k) . "=" . trim($v));
    }
}

require_once BASE_DIR . '/app/Config/Database.php';
use App\Config\Database;

try {
    $db = Database::getConnection();

    echo "=========================================================================\n";
    echo "  MEMULAI SEEDING KODE KLASIFIKASI SURAT DINAS KE POSTGRESQL (persuratan)\n";
    echo "=========================================================================\n";

    // 1. Pastikan Schema & Tabel serta Kolom-Kolom persuratan.kode_klasifikasi_surat Tersedia
    $db->exec("CREATE SCHEMA IF NOT EXISTS persuratan;");
    $db->exec("
        CREATE TABLE IF NOT EXISTS persuratan.kode_klasifikasi_surat (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            tenant_id UUID NULL,
            kode_klasifikasi VARCHAR(50) NOT NULL,
            nama_klasifikasi TEXT NOT NULL,
            nama_kode_klasifikasi_surat TEXT NULL,
            parent_kode VARCHAR(50) NULL,
            level_klasifikasi INT DEFAULT 1,
            kategori_utama VARCHAR(100) NULL,
            kategori VARCHAR(100) DEFAULT 'Umum',
            deskripsi TEXT NULL,
            retensi_aktif_tahun INT DEFAULT 5,
            retensi_inaktif_tahun INT DEFAULT 5,
            retensi_tahun INT DEFAULT 5,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
        ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS kode_klasifikasi VARCHAR(50) NULL;
        ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS nama_klasifikasi TEXT NULL;
        ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS nama_kode_klasifikasi_surat TEXT NULL;
        ALTER TABLE persuratan.kode_klasifikasi_surat ALTER COLUMN nama_klasifikasi TYPE TEXT;
        ALTER TABLE persuratan.kode_klasifikasi_surat ALTER COLUMN nama_kode_klasifikasi_surat TYPE TEXT;
        ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS parent_kode VARCHAR(50) NULL;
        ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS level_klasifikasi INT DEFAULT 1;
        ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS kategori_utama VARCHAR(100) NULL;
        ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS retensi_aktif_tahun INT DEFAULT 5;
        ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS retensi_inaktif_tahun INT DEFAULT 5;
        ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS retensi_tahun INT DEFAULT 5;
    ");

    // 2. Baca Berkas JSON Klasifikasi Surat
    $jsonPath = __DIR__ . '/kode_klasifikasi_surat.json';
    if (!file_exists($jsonPath)) {
        $jsonPath = BASE_DIR . '/scratch/docs/portal_schema/seed_kode_klasifikasi_surat.json';
    }

    if (!file_exists($jsonPath)) {
        throw new Exception("Berkas JSON data klasifikasi surat tidak ditemukan: $jsonPath");
    }

    echo "1. Membaca Dataset Klasifikasi Surat ($jsonPath)...\n";
    $jsonContent = file_get_contents($jsonPath);
    $items = json_decode($jsonContent, true);

    if (!is_array($items)) {
        throw new Exception("Format JSON tidak valid atau gagal didecode.");
    }

    $totalItems = count($items);
    echo "✔ Dataset Berhasil Dibaca: {$totalItems} baris kode klasifikasi.\n\n";

    // 3. Eksekusi Batch Upsert ke PostgreSQL
    echo "2. Memulai proses Seeding ke persuratan.kode_klasifikasi_surat...\n";
    $startTime = microtime(true);

    $db->beginTransaction();

    $checkStmt = $db->prepare("
        SELECT id FROM persuratan.kode_klasifikasi_surat 
        WHERE kode_klasifikasi = :kode AND tenant_id IS NULL
        LIMIT 1
    ");

    $insertStmt = $db->prepare("
        INSERT INTO persuratan.kode_klasifikasi_surat (
            id, tenant_id, kode_klasifikasi, nama_klasifikasi, nama_kode_klasifikasi_surat,
            parent_kode, level_klasifikasi, kategori_utama, kategori, retensi_aktif_tahun,
            retensi_inaktif_tahun, retensi_tahun, is_active, created_at, updated_at
        ) VALUES (
            gen_random_uuid(), NULL, :kode_klasifikasi, :nama_klasifikasi, :nama_kode_klasifikasi_surat,
            :parent_kode, :level_klasifikasi, :kategori_utama, :kategori, :retensi_aktif_tahun,
            :retensi_inaktif_tahun, :retensi_tahun, TRUE, NOW(), NOW()
        )
    ");

    $updateStmt = $db->prepare("
        UPDATE persuratan.kode_klasifikasi_surat SET
            nama_klasifikasi = :nama_klasifikasi,
            nama_kode_klasifikasi_surat = :nama_kode_klasifikasi_surat,
            parent_kode = :parent_kode,
            level_klasifikasi = :level_klasifikasi,
            kategori_utama = :kategori_utama,
            kategori = :kategori,
            retensi_aktif_tahun = :retensi_aktif_tahun,
            retensi_inaktif_tahun = :retensi_inaktif_tahun,
            retensi_tahun = :retensi_tahun,
            updated_at = NOW()
        WHERE id = :id
    ");

    $inserted = 0;
    $updated = 0;

    foreach ($items as $idx => $item) {
        $kode = trim($item['kode_klasifikasi'] ?? '');
        $nama = trim($item['nama_klasifikasi'] ?? '');
        $parent = !empty($item['parent_kode']) ? trim($item['parent_kode']) : null;
        $level = isset($item['level_klasifikasi']) ? (int)$item['level_klasifikasi'] : 1;
        $kategoriUtama = trim($item['kategori_utama'] ?? 'Umum/Organisasi');
        $retensiAktif = isset($item['retensi_aktif_tahun']) ? (int)$item['retensi_aktif_tahun'] : 5;
        $retensiInaktif = isset($item['retensi_inaktif_tahun']) ? (int)$item['retensi_inaktif_tahun'] : 5;

        if (empty($kode) || empty($nama)) {
            continue;
        }

        $checkStmt->execute([':kode' => $kode]);
        $existingId = $checkStmt->fetchColumn();

        $params = [
            ':nama_klasifikasi' => $nama,
            ':nama_kode_klasifikasi_surat' => $nama,
            ':parent_kode' => $parent,
            ':level_klasifikasi' => $level,
            ':kategori_utama' => $kategoriUtama,
            ':kategori' => $kategoriUtama,
            ':retensi_aktif_tahun' => $retensiAktif,
            ':retensi_inaktif_tahun' => $retensiInaktif,
            ':retensi_tahun' => $retensiAktif
        ];

        if ($existingId) {
            $params[':id'] = $existingId;
            $updateStmt->execute($params);
            $updated++;
        } else {
            $params[':kode_klasifikasi'] = $kode;
            $insertStmt->execute($params);
            $inserted++;
        }

        if (($idx + 1) % 500 === 0 || ($idx + 1) === $totalItems) {
            echo "   -> Diproses " . ($idx + 1) . " / {$totalItems} data...\n";
        }
    }

    $db->commit();
    $duration = round(microtime(true) - $startTime, 2);

    echo "\n=========================================================================\n";
    echo "  ✔ SEEDING DATASET KODE KLASIFIKASI SURAT BERHASIL 100%!\n";
    echo "=========================================================================\n";
    echo "  Total Dataset        : {$totalItems} baris\n";
    echo "  Baru Ditambahkan     : {$inserted} baris\n";
    echo "  Diperbarui           : {$updated} baris\n";
    echo "  Waktu Eksekusi       : {$duration} detik\n";
    echo "=========================================================================\n\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ [ERROR SEEDING]: " . $e->getMessage() . "\n";
    exit(1);
}
