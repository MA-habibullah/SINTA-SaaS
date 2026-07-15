<?php
/**
 * Migration 2026_07_15_01 - Seed SNPMB SNBP Data from CSV Files
 * Membaca data CSV untuk Kampus, Prodi, dan Historis Peminat, lalu melakukan batch insert untuk semua tenant aktif.
 */

return [
    'up' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("SET UNIQUE_CHECKS = 0;");

        $basePath = __DIR__ . '/../seeds/';
        
        $ptnFile = $basePath . 'snpmb_snbp_ptn.csv';
        $prodiFile = $basePath . 'snpmb_snbp_prodi.csv';
        $historisFile = $basePath . 'snpmb_snbp_historis_peminat.csv';

        if (!file_exists($ptnFile) || !file_exists($prodiFile) || !file_exists($historisFile)) {
            echo "Peringatan: Berkas CSV SNPMB tidak lengkap di folder seeds. Proses seeding dilewati.\n";
            $pdo->exec("SET UNIQUE_CHECKS = 1;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return;
        }

        // Ambil semua tenant aktif
        $stmtTenant = $pdo->query("SELECT id FROM tenants WHERE status = 'active'");
        $tenants = $stmtTenant->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tenants)) {
            echo "Peringatan: Tidak ada tenant aktif untuk diseed.\n";
            $pdo->exec("SET UNIQUE_CHECKS = 1;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return;
        }

        echo "Menjalankan seeding untuk " . count($tenants) . " tenant aktif...\n";

        // Helper UUID Deterministik
        $getUuid = function ($namespace, $name) {
            $nval = md5($namespace . $name);
            return sprintf('%08s-%04s-%04s-%04s-%12s',
                substr($nval, 0, 8),
                substr($nval, 8, 4),
                substr($nval, 12, 4),
                substr($nval, 16, 4),
                substr($nval, 20, 12)
            );
        };

        // Helper Normalisasi Jenjang
        $normalizeJenjang = function (string $raw): string {
            $raw = strtolower(trim($raw));
            if (strpos($raw, 'diploma tiga') !== false || strpos($raw, 'diploma iii') !== false || $raw === 'd3') return 'D3';
            if (strpos($raw, 'sarjana terapan') !== false || strpos($raw, 'diploma iv') !== false || strpos($raw, 'diploma empat') !== false || $raw === 'd4') return 'D4';
            if (strpos($raw, 'sarjana') !== false || strpos($raw, 's1') !== false) return 'S1';
            if (strpos($raw, 'magister') !== false || strpos($raw, 's2') !== false) return 'S2';
            if (strpos($raw, 'doktor') !== false || strpos($raw, 's3') !== false) return 'S3';
            if (strpos($raw, 'profesi') !== false) return 'Profesi';
            if (strpos($raw, 'diploma satu') !== false || strpos($raw, 'diploma i') !== false || $raw === 'd1') return 'D1';
            if (strpos($raw, 'diploma dua') !== false || strpos($raw, 'diploma ii') !== false || $raw === 'd2') return 'D2';
            return 'S1';
        };

        // Helper Batch Insert closure
        $executeBatchInsert = function (PDO $pdo, string $sqlBase, array $rows, int $colCount): void {
            $batchCount = count($rows);
            $placeholders = array_fill(0, $batchCount, '(' . implode(', ', array_fill(0, $colCount, '?')) . ')');
            $sql = $sqlBase . implode(', ', $placeholders) . " ON DUPLICATE KEY UPDATE updated_at = NOW()";
            
            $stmt = $pdo->prepare($sql);
            $flatValues = [];
            foreach ($rows as $row) {
                foreach ($row as $val) {
                    $flatValues[] = $val;
                }
            }
            $stmt->execute($flatValues);
        };

        // Periksa eksistensi kolom-kolom baru
        $hasKodeProdi = false;
        $hasPorto = false;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `master_kampus_prodi` LIKE 'kode_prodi'");
            $hasKodeProdi = $stmt->fetch() !== false;
            $stmt = $pdo->query("SHOW COLUMNS FROM `master_kampus_prodi` LIKE 'jenis_portofolio'");
            $hasPorto = $stmt->fetch() !== false;
        } catch (\Exception $e) {}

        // 1. Baca PTN
        echo "   - Membaca data PTN...\n";
        $ptns = [];
        $handle = fopen($ptnFile, 'r');
        fgetcsv($handle, 0, ','); // skip header
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $ptns[] = [
                'id_ptn' => $row[0],
                'kode_ptn' => $row[1],
                'nama_ptn' => $row[2],
                'web' => $row[3],
                'jenis' => $row[4],
                'alamat' => $row[5],
                'kota' => $row[8]
            ];
        }
        fclose($handle);

        // 2. Baca Prodi
        echo "   - Membaca data Program Studi...\n";
        $prodis = [];
        $handle = fopen($prodiFile, 'r');
        fgetcsv($handle, 0, ','); // skip header
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $prodis[] = [
                'id_prodi' => $row[0],
                'kode_prodi' => $row[1],
                'id_ptn' => $row[2],
                'nama_prodi' => $row[3],
                'jenjang' => $row[4],
                'daya_tampung' => $row[5],
                'portofolio' => $row[6]
            ];
        }
        fclose($handle);

        // 3. Baca Historis
        echo "   - Membaca data Historis Peminat...\n";
        $historis = [];
        $handle = fopen($historisFile, 'r');
        fgetcsv($handle, 0, ','); // skip header
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $historis[] = [
                'id_prodi' => $row[0],
                'kode_prodi' => $row[1],
                'tahun' => $row[2],
                'daya_tampung' => $row[3],
                'peminat' => $row[4],
                'diterima' => $row[5]
            ];
        }
        fclose($handle);

        $batchSize = 500;

        foreach ($tenants as $tid) {
            echo "   -> Mengimpor untuk Tenant ID: {$tid}...\n";

            // A. Batch Insert Kampus
            echo "      * Kampus...\n";
            $sqlK = "INSERT INTO master_kampus (id, tenant_id, nama_kampus, kota_kampus, alamat_kampus, jenis_kampus) VALUES ";
            $rowsK = [];
            foreach ($ptns as $ptn) {
                $ptnUuid = $getUuid($tid . '_ptn_', $ptn['id_ptn']);
                $rowsK[] = [$ptnUuid, $tid, $ptn['nama_ptn'], $ptn['kota'] ?: null, $ptn['alamat'] ?: null, 'Negeri'];
                
                if (count($rowsK) >= $batchSize) {
                    $executeBatchInsert($pdo, $sqlK, $rowsK, 6);
                    $rowsK = [];
                }
            }
            if (count($rowsK) > 0) {
                $executeBatchInsert($pdo, $sqlK, $rowsK, 6);
            }

            // B. Batch Insert Prodi
            echo "      * Program Studi...\n";
            $sqlP = "INSERT INTO master_kampus_prodi (id, kampus_id, fakultas, program_studi, jenjang"
                . ($hasKodeProdi ? ", kode_prodi" : "")
                . ($hasPorto ? ", jenis_portofolio" : "")
                . ") VALUES ";
            $rowsP = [];
            $colCount = 5 + ($hasKodeProdi ? 1 : 0) + ($hasPorto ? 1 : 0);

            foreach ($prodis as $pr) {
                $prodiUuid = $getUuid($tid . '_prodi_', $pr['id_prodi']);
                $ptnUuid = $getUuid($tid . '_ptn_', $pr['id_ptn']);
                $jenjang = $normalizeJenjang($pr['jenjang']);
                
                $item = [$prodiUuid, $ptnUuid, '', $pr['nama_prodi'], $jenjang];
                if ($hasKodeProdi) $item[] = $pr['kode_prodi'];
                if ($hasPorto) $item[] = $pr['portofolio'] ?: 'Tidak Ada';
                
                $rowsP[] = $item;

                if (count($rowsP) >= $batchSize) {
                    $executeBatchInsert($pdo, $sqlP, $rowsP, $colCount);
                    $rowsP = [];
                }
            }
            if (count($rowsP) > 0) {
                $executeBatchInsert($pdo, $sqlP, $rowsP, $colCount);
            }

            // C. Batch Insert Historis
            echo "      * Historis Keketatan...\n";
            $sqlH = "INSERT INTO kampus_prodi_riwayat (prodi_id, tahun, daya_tampung, jumlah_pendaftar) VALUES ";
            $rowsH = [];

            foreach ($historis as $h) {
                $prodiUuid = $getUuid($tid . '_prodi_', $h['id_prodi']);
                $rowsH[] = [$prodiUuid, (int)$h['tahun'], (int)$h['daya_tampung'], (int)$h['peminat']];

                if (count($rowsH) >= $batchSize) {
                    $executeBatchInsert($pdo, $sqlH, $rowsH, 4);
                    $rowsH = [];
                }
            }
            if (count($rowsH) > 0) {
                $executeBatchInsert($pdo, $sqlH, $rowsH, 4);
            }
        }

        $pdo->exec("SET UNIQUE_CHECKS = 1;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "Sukses: Seeding data SNBP selesai.\n";
    },

    'down' => function(PDO $pdo) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE kampus_prodi_riwayat;");
        $pdo->exec("TRUNCATE TABLE master_kampus_prodi;");
        $pdo->exec("TRUNCATE TABLE master_kampus;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "Rollback: Seeding SNBP dibersihkan.\n";
    }
];
