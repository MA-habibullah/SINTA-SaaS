<?php

/**
 * Seeder Dataset Resmi SNPMB SNBP: PTN, Program Studi, dan Historis Peminat & Keketatan
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

$db = Database::getConnection();

echo "=========================================================================\n";
echo "  MEMULAI SEEDING DATASET SNPMB SNBP KE POSTGRESQL (SCHEMA pdss)\n";
echo "=========================================================================\n";

$db->exec("ALTER TABLE pdss.master_kampus ADD COLUMN IF NOT EXISTS tenant_id UUID NULL;");
$db->exec("ALTER TABLE pdss.master_kampus_prodi ADD COLUMN IF NOT EXISTS tenant_id UUID NULL;");

$db->beginTransaction();

try {
    // 1. SEED PTN
    $ptnCsv = BASE_DIR . '/database/seeds/snpmb_snbp_ptn.csv';
    if (!file_exists($ptnCsv)) {
        throw new Exception("Berkas PTN tidak ditemukan: $ptnCsv");
    }

    echo "1. Membaca & Seeding PTN ($ptnCsv)...\n";
    $handle = fopen($ptnCsv, 'r');
    $header = fgetcsv($handle); // id_ptn,kode_ptn,nama_ptn,web,jenis,alamat,provinsi1,provinsi2,kota

    $stmtPtn = $db->prepare("
        INSERT INTO pdss.master_kampus (
            id_ptn, kode_ptn, nama_kampus, web, jenis, jenis_kampus, alamat, alamat_kampus, provinsi, kota, kota_kampus, tenant_id
        ) VALUES (
            :id_ptn, :kode_ptn, :nama_kampus, :web, :jenis, :jenis_kampus, :alamat, :alamat_kampus, :provinsi, :kota, :kota_kampus, NULL
        )
        ON CONFLICT (id) DO NOTHING
    ");

    // We also want to map id_ptn to kampus UUID
    $kampusMap = []; // id_ptn => uuid

    $countPtn = 0;
    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[0])) continue;
        $idPtn = trim($row[0]);
        $kodePtn = trim($row[1]);
        $namaPtn = trim($row[2]);
        $web = trim($row[3] ?? '');
        $jenis = trim($row[4] ?? 'PTN Akademik');
        $alamat = trim($row[5] ?? '');
        $provinsi = trim($row[6] ?? '');
        $kota = trim($row[8] ?? '');

        // Cek apakah sudah ada di database
        $stCheck = $db->prepare("SELECT id FROM pdss.master_kampus WHERE id_ptn = ? OR nama_kampus = ? LIMIT 1");
        $stCheck->execute([$idPtn, $namaPtn]);
        $existingId = $stCheck->fetchColumn();

        if ($existingId) {
            $stUpd = $db->prepare("
                UPDATE pdss.master_kampus SET 
                    id_ptn = :id_ptn,
                    kode_ptn = :kode_ptn,
                    nama_kampus = :nama_kampus,
                    web = :web,
                    jenis = :jenis,
                    jenis_kampus = 'Negeri',
                    alamat = :alamat,
                    alamat_kampus = :alamat,
                    provinsi = :provinsi,
                    kota = :kota,
                    kota_kampus = :kota
                WHERE id = :id
            ");
            $stUpd->execute([
                ':id_ptn' => $idPtn,
                ':kode_ptn' => $kodePtn,
                ':nama_kampus' => $namaPtn,
                ':web' => $web,
                ':jenis' => $jenis,
                ':alamat' => $alamat,
                ':provinsi' => $provinsi,
                ':kota' => $kota,
                ':id' => $existingId
            ]);
            $kampusMap[$idPtn] = $existingId;
        } else {
            $stIns = $db->prepare("
                INSERT INTO pdss.master_kampus (
                    id_ptn, kode_ptn, nama_kampus, web, jenis, jenis_kampus, alamat, alamat_kampus, provinsi, kota, kota_kampus, tenant_id
                ) VALUES (
                    :id_ptn, :kode_ptn, :nama_kampus, :web, :jenis, 'Negeri', :alamat, :alamat, :provinsi, :kota, :kota, NULL
                ) RETURNING id
            ");
            $stIns->execute([
                ':id_ptn' => $idPtn,
                ':kode_ptn' => $kodePtn,
                ':nama_kampus' => $namaPtn,
                ':web' => $web,
                ':jenis' => $jenis,
                ':alamat' => $alamat,
                ':provinsi' => $provinsi,
                ':kota' => $kota
            ]);
            $newId = $stIns->fetchColumn();
            $kampusMap[$idPtn] = $newId;
        }
        $countPtn++;
    }
    fclose($handle);
    echo "  ✔ Selesai: $countPtn PTN berhasil diproses.\n\n";

    // 2. SEED PROGRAM STUDI
    $prodiCsv = BASE_DIR . '/database/seeds/snpmb_snbp_prodi.csv';
    if (!file_exists($prodiCsv)) {
        throw new Exception("Berkas Prodi tidak ditemukan: $prodiCsv");
    }

    echo "2. Membaca & Seeding Program Studi ($prodiCsv)...\n";
    $handle = fopen($prodiCsv, 'r');
    $header = fgetcsv($handle); // id_prodi,kode_prodi,id_ptn,nama_prodi,jenjang,daya_tampung_sekarang,jenis_portofolio

    $prodiMap = []; // id_prodi => uuid
    $prodiKodeMap = []; // kode_prodi => uuid

    $countProdi = 0;
    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[0])) continue;
        $idProdi = trim($row[0]);
        $kodeProdi = trim($row[1]);
        $idPtn = trim($row[2]);
        $namaProdi = trim($row[3]);
        $jenjang = trim($row[4] ?? 'S1');
        $dayaTampungSekarang = (int)($row[5] ?? 0);
        $jenisPortofolio = trim($row[6] ?? 'Tidak Ada');

        $kampusUuid = $kampusMap[$idPtn] ?? null;

        // Cek existing prodi
        $stCheck = $db->prepare("SELECT id FROM pdss.master_kampus_prodi WHERE id_prodi = ? OR (kampus_id = ? AND kode_prodi = ?) LIMIT 1");
        $stCheck->execute([$idProdi, $kampusUuid, $kodeProdi]);
        $existingProdiId = $stCheck->fetchColumn();

        if ($existingProdiId) {
            $stUpd = $db->prepare("
                UPDATE pdss.master_kampus_prodi SET
                    kampus_id = :kampus_id,
                    id_prodi = :id_prodi,
                    kode_prodi = :kode_prodi,
                    id_ptn = :id_ptn,
                    program_studi = :nama_prodi,
                    nama_prodi = :nama_prodi,
                    jenjang = :jenjang,
                    daya_tampung_sekarang = :daya_tampung,
                    jenis_portofolio = :portofolio,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stUpd->execute([
                ':kampus_id' => $kampusUuid,
                ':id_prodi' => $idProdi,
                ':kode_prodi' => $kodeProdi,
                ':id_ptn' => $idPtn,
                ':nama_prodi' => $namaProdi,
                ':jenjang' => $jenjang,
                ':daya_tampung' => $dayaTampungSekarang,
                ':portofolio' => $jenisPortofolio,
                ':id' => $existingProdiId
            ]);
            $prodiMap[$idProdi] = $existingProdiId;
            $prodiKodeMap[$kodeProdi] = $existingProdiId;
        } else {
            $stIns = $db->prepare("
                INSERT INTO pdss.master_kampus_prodi (
                    kampus_id, id_prodi, kode_prodi, id_ptn, program_studi, nama_prodi, jenjang, daya_tampung_sekarang, jenis_portofolio, tenant_id
                ) VALUES (
                    :kampus_id, :id_prodi, :kode_prodi, :id_ptn, :nama_prodi, :nama_prodi, :jenjang, :daya_tampung, :portofolio, NULL
                ) RETURNING id
            ");
            $stIns->execute([
                ':kampus_id' => $kampusUuid,
                ':id_prodi' => $idProdi,
                ':kode_prodi' => $kodeProdi,
                ':id_ptn' => $idPtn,
                ':nama_prodi' => $namaProdi,
                ':jenjang' => $jenjang,
                ':daya_tampung' => $dayaTampungSekarang,
                ':portofolio' => $jenisPortofolio
            ]);
            $newProdiId = $stIns->fetchColumn();
            $prodiMap[$idProdi] = $newProdiId;
            $prodiKodeMap[$kodeProdi] = $newProdiId;
        }
        $countProdi++;
    }
    fclose($handle);
    echo "  ✔ Selesai: $countProdi Program Studi berhasil diproses.\n\n";

    // 3. SEED HISTORIS PEMINAT
    $peminatCsv = BASE_DIR . '/database/seeds/snpmb_snbp_historis_peminat.csv';
    if (!file_exists($peminatCsv)) {
        throw new Exception("Berkas Historis Peminat tidak ditemukan: $peminatCsv");
    }

    echo "3. Membaca & Seeding Historis Peminat & Keketatan ($peminatCsv)...\n";
    $handle = fopen($peminatCsv, 'r');
    $header = fgetcsv($handle); // id_prodi,kode_prodi,tahun,daya_tampung,peminat,diterima,keketatan

    $stmtUpsertRiwayat = $db->prepare("
        INSERT INTO pdss.kampus_prodi_riwayat (
            prodi_id, id_prodi, kode_prodi, tahun, daya_tampung, jumlah_pendaftar, diterima, keketatan
        ) VALUES (
            :prodi_id, :id_prodi, :kode_prodi, :tahun, :daya_tampung, :peminat, :diterima, :keketatan
        )
        ON CONFLICT (prodi_id, tahun) DO UPDATE SET
            id_prodi = EXCLUDED.id_prodi,
            kode_prodi = EXCLUDED.kode_prodi,
            daya_tampung = EXCLUDED.daya_tampung,
            jumlah_pendaftar = EXCLUDED.jumlah_pendaftar,
            diterima = EXCLUDED.diterima,
            keketatan = EXCLUDED.keketatan,
            updated_at = CURRENT_TIMESTAMP
    ");

    $countRiwayat = 0;
    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[0])) continue;
        $idProdi = trim($row[0]);
        $kodeProdi = trim($row[1]);
        $tahun = (int)($row[2]);
        $dayaTampung = (int)($row[3] ?? 0);
        $peminat = (int)($row[4] ?? 0);
        $diterima = (int)($row[5] ?? 0);
        $keketatan = trim($row[6] ?? '');

        $prodiUuid = $prodiMap[$idProdi] ?? ($prodiKodeMap[$kodeProdi] ?? null);
        if (!$prodiUuid) continue;

        $stmtUpsertRiwayat->execute([
            ':prodi_id' => $prodiUuid,
            ':id_prodi' => $idProdi,
            ':kode_prodi' => $kodeProdi,
            ':tahun' => $tahun,
            ':daya_tampung' => $dayaTampung,
            ':peminat' => $peminat,
            ':diterima' => $diterima,
            ':keketatan' => $keketatan
        ]);
        $countRiwayat++;
    }
    fclose($handle);
    echo "  ✔ Selesai: $countRiwayat Rekam Historis Peminat (2021-2025) berhasil diproses.\n\n";

    $db->commit();
    echo "=========================================================================\n";
    echo "  ✔ SEEDING BERHASIL 100%! SEMUA DATA TELAH TERSIMPAN DI POSTGRESQL\n";
    echo "=========================================================================\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "\n❌ ERROR SAAT SEEDING: " . $e->getMessage() . "\n";
    exit(1);
}
