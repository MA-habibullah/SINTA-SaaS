<?php

namespace App\Controllers;

use App\Core\SessionManager;
use PDO;

/**
 * PDSSController
 *
 * Menangani fitur PDSS & Alumni Career Tracking Module:
 * - Tab 1: Kesiapan & Eligibilitas Siswa (Simulasi Ranking Paralel SNBP)
 * - Tab 2: Tracking Alumni & Rekam Kampus (CRUD & Privacy Masking)
 * - Tab 3: Konfigurasi Target Kampus (CRUD & Seeder Kampus Utama)
 *
 * SECURITY MODEL:
 * - Otorisasi full access: super_admin, operator_sekolah, guru_bk.
 * - Otorisasi read-only: guru, siswa.
 * - Multi-tenant isolation: semua query dikunci menggunakan tenant_id.
 */
class PDSSController extends BaseController {

    private const WRITE_ALLOWED_ROLES = ['super_admin', 'operator_sekolah', 'guru_bk'];

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();

        try {
            $db = \App\Config\Database::getConnection();
            
            // Drop old pdss_config_mapel if it exists and doesn't have sem_1 column
            try {
                $check = $db->query("SHOW COLUMNS FROM `pdss_config_mapel` LIKE 'sem_1'");
                if ($check && $check->rowCount() === 0) {
                    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
                    $db->exec("DROP TABLE IF EXISTS `pdss_config_mapel`;");
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
                }
            } catch (\Throwable $e) {
                // Table doesn't exist yet, ignore
            }
            
            // Re-create pdss_config_mapel with semester columns
            $db->exec("CREATE TABLE IF NOT EXISTS `pdss_config_mapel` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `tenant_id` CHAR(36) NOT NULL,
                `mapel_id` INT UNSIGNED NOT NULL,
                `sem_1` TINYINT(1) DEFAULT 0,
                `sem_2` TINYINT(1) DEFAULT 0,
                `sem_3` TINYINT(1) DEFAULT 0,
                `sem_4` TINYINT(1) DEFAULT 0,
                `sem_5` TINYINT(1) DEFAULT 0,
                `sem_6` TINYINT(1) DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_tenant_mapel` (`tenant_id`, `mapel_id`),
                CONSTRAINT `fk_pdss_mapel_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_pdss_mapel_id` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

            // Create pdss_manual_eligible table
            $db->exec("CREATE TABLE IF NOT EXISTS `pdss_manual_eligible` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `tenant_id` CHAR(36) NOT NULL,
                `siswa_id` CHAR(36) NOT NULL,
                `status_eligible` ENUM('auto', 'eligible', 'tidak_eligible') DEFAULT 'auto',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_tenant_siswa` (`tenant_id`, `siswa_id`),
                CONSTRAINT `fk_pdss_manual_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_pdss_manual_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

            // Create pdss_lock table
            try {
                $checkLock = $db->query("SHOW COLUMNS FROM `pdss_lock` LIKE 'step'");
                if ($checkLock && $checkLock->rowCount() === 0) {
                    $db->exec("DROP TABLE IF EXISTS `pdss_lock`;");
                }
            } catch (\Throwable $e) {
                // Table doesn't exist yet, ignore
            }

            $db->exec("CREATE TABLE IF NOT EXISTS `pdss_lock` (
                `tenant_id` CHAR(36) NOT NULL,
                `step` TINYINT(1) NOT NULL,
                `is_locked` TINYINT(1) DEFAULT 0,
                `locked_by` VARCHAR(255) DEFAULT NULL,
                `locked_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`tenant_id`, `step`),
                CONSTRAINT `fk_pdss_lock_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
            
        } catch (\Throwable $e) {
            error_log('[PDSSController::__construct] failed to create/update tables: ' . $e->getMessage());
        }
    }

    /**
     * Memeriksa apakah role user saat ini diperbolehkan untuk melakukan operasi penulisan (CRUD)
     */
    private function canWrite(): bool {
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        foreach ($roles as $r) {
            if (in_array($r, self::WRITE_ALLOWED_ROLES, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render Halaman Utama PDSS & Alumni
     */
    public function index(): void {
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $userRole = $_SESSION['role_name'] ?? '';
        $tenantId = $this->getSecureTenantId();

        $tenantList = [];
        if (in_array('super_admin', $roles, true)) {
            try {
                $db = \App\Config\Database::getConnection();
                $tenantList = $db->query("SELECT id, nama_sekolah FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC")
                                 ->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {}
        }
        
        $this->render('pdss_index', [
            'title' => 'PDSS & Tracking Alumni',
            'can_write' => $this->canWrite(),
            'user_role' => $userRole,
            'roles' => $roles,
            'tenant_id' => $tenantId,
            'tenant_list' => $tenantList
        ]);
    }

    /**
     * Helper: Mendapatkan Tenant ID yang aman (dikunci untuk non-super_admin)
     */
    private function getSecureTenantId(): ?string {
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $tenantId = SessionManager::getTenantId();

        if (in_array('super_admin', $roles, true)) {
            $tid = $_GET['tenant_id'] ?? $_POST['tenant_id'] ?? null;
            if (empty($tid)) {
                $body = $this->getJsonInput();
                $tid = $body['tenant_id'] ?? null;
            }

            if (!empty($tid)) {
                try {
                    $db = \App\Config\Database::getConnection();
                    $stmt = $db->prepare("SELECT id FROM tenants WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                    $stmt->execute([$tid]);
                    $valid = $stmt->fetchColumn();
                    return $valid ?: null;
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }

        return $tenantId;
    }

    /**
     * API: Mendapatkan daftar mapel dan status pilihan untuk PDSS
     * GET /api/v1/pdss/config-mapel
     */
    /**
     * API: Mendapatkan daftar mapel dan status pilihan untuk PDSS
     * GET /api/v1/pdss/config-mapel
     */
    public function apiGetPdssMapels(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // Tentukan tahun ajaran
            $tahunAjaranId = isset($_GET['tahun_ajaran_id']) ? $_GET['tahun_ajaran_id'] : '';
            if (empty($tahunAjaranId)) {
                $stmtActiveTa = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtActiveTa->execute([$tenantId]);
                $tahunAjaranId = $stmtActiveTa->fetchColumn();
            }

            $stmtTa = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ? LIMIT 1");
            $stmtTa->execute([$tahunAjaranId]);
            $selectedTaName = $stmtTa->fetchColumn() ?: '';

            // Ambil semua mapel aktif yang terpetakan dalam kurikulum kelas 10 s.d 12 ATAU yang memiliki nilai riil dari siswa cohort ini
            $stmtAll = $db->prepare("
                SELECT DISTINCT mp.id, mp.kode_mapel, mp.nama_mapel 
                FROM mata_pelajaran mp
                LEFT JOIN pemetaan_mapel pm ON mp.id = pm.mapel_id AND pm.tahun_ajaran = ? AND pm.deleted_at IS NULL
                LEFT JOIN kelas k ON pm.kelas_id = k.id
                WHERE mp.tenant_id = ? 
                  AND mp.is_active = 1 
                  AND mp.deleted_at IS NULL
                  AND (
                      -- Kondisi 1: Terpetakan di kurikulum kelas 10 s.d 12
                      (pm.id IS NOT NULL AND (k.nama_kelas LIKE '%10%' OR k.nama_kelas LIKE '%X%' OR k.nama_kelas LIKE '%11%' OR k.nama_kelas LIKE '%XI%' OR k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%'))
                      -- Kondisi 2: Siswa dalam cohort ini memiliki nilai riil untuk mapel tersebut
                      OR mp.id IN (
                          SELECT DISTINCT dnr.mapel_id 
                          FROM detail_nilai_rapor dnr 
                          WHERE dnr.tenant_id = ? 
                            AND dnr.deleted_at IS NULL
                            AND dnr.siswa_id IN (
                                SELECT s2.id 
                                FROM siswa s2
                                LEFT JOIN kelas k2 ON s2.id_kelas = k2.id
                                LEFT JOIN tahun_ajaran ta2 ON s2.id_tahun_ajaran = ta2.id
                                WHERE s2.tenant_id = ?
                                  AND s2.status = 'Aktif'
                                  AND s2.deleted_at IS NULL
                                  AND (
                                      s2.id IN (
                                          SELECT DISTINCT dnr2.siswa_id 
                                          FROM detail_nilai_rapor dnr2 
                                          JOIN kelas k_dnr ON dnr2.kelas_id = k_dnr.id
                                          WHERE dnr2.tenant_id = ? 
                                            AND (k_dnr.nama_kelas LIKE '%12%' OR k_dnr.nama_kelas LIKE '%XII%')
                                            AND dnr2.tahun_ajaran = ?
                                      )
                                      OR (
                                          (k2.nama_kelas LIKE '%12%' OR k2.nama_kelas LIKE '%XII%')
                                          AND (ta2.tahun_ajaran = ? OR ta2.tahun_ajaran IS NULL)
                                      )
                                  )
                            )
                      )
                  )
                ORDER BY mp.nama_mapel ASC
            ");
            $stmtAll->execute([
                $selectedTaName, // pm.tahun_ajaran
                $tenantId,       // mp.tenant_id
                $tenantId,       // dnr.tenant_id
                $tenantId,       // s2.tenant_id
                $tenantId,       // dnr2.tenant_id
                $selectedTaName, // dnr2.tahun_ajaran
                $selectedTaName  // ta2.tahun_ajaran comparison
            ]);
            $allMapels = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

            // Ambil mapel terpilih beserta semester flags berdasarkan tahun_ajaran_id
            $stmtSelected = $db->prepare("SELECT mapel_id, sem_1, sem_2, sem_3, sem_4, sem_5, sem_6 FROM pdss_config_mapel WHERE tenant_id = ? AND tahun_ajaran_id = ?");
            $stmtSelected->execute([$tenantId, $tahunAjaranId]);
            $selectedMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC);

            $selectedIndexed = [];
            foreach ($selectedMapels as $sm) {
                $selectedIndexed[(int)$sm['mapel_id']] = $sm;
            }

            // Ambil semester yang memiliki nilai rapor riil untuk tiap mapel
            $stmtGradesCheck = $db->prepare("
                SELECT dnr.mapel_id, dnr.semester, k.nama_kelas
                FROM detail_nilai_rapor dnr
                JOIN kelas k ON dnr.kelas_id = k.id
                WHERE dnr.tenant_id = ? AND dnr.deleted_at IS NULL
            ");
            $stmtGradesCheck->execute([$tenantId]);
            $allGradesCheck = $stmtGradesCheck->fetchAll(PDO::FETCH_ASSOC);

            $gradesAvailability = [];
            foreach ($allGradesCheck as $g) {
                $mid = (int)$g['mapel_id'];
                $semLevel = $this->getSemesterLevel($g['nama_kelas'], $g['semester']);
                if ($semLevel !== null) {
                    $gradesAvailability[$mid][$semLevel] = true;
                }
            }

            foreach ($allMapels as &$m) {
                $mid = (int)$m['id'];

                $m['has_sem_1'] = isset($gradesAvailability[$mid][1]);
                $m['has_sem_2'] = isset($gradesAvailability[$mid][2]);
                $m['has_sem_3'] = isset($gradesAvailability[$mid][3]);
                $m['has_sem_4'] = isset($gradesAvailability[$mid][4]);
                $m['has_sem_5'] = isset($gradesAvailability[$mid][5]);
                $m['has_sem_6'] = isset($gradesAvailability[$mid][6]);

                if (isset($selectedIndexed[$mid])) {
                    $m['sem_1'] = (bool)$selectedIndexed[$mid]['sem_1'];
                    $m['sem_2'] = (bool)$selectedIndexed[$mid]['sem_2'];
                    $m['sem_3'] = (bool)$selectedIndexed[$mid]['sem_3'];
                    $m['sem_4'] = (bool)$selectedIndexed[$mid]['sem_4'];
                    $m['sem_5'] = (bool)$selectedIndexed[$mid]['sem_5'];
                    $m['sem_6'] = (bool)$selectedIndexed[$mid]['sem_6'];
                    $m['is_selected'] = true;
                } else {
                    $m['sem_1'] = false;
                    $m['sem_2'] = false;
                    $m['sem_3'] = false;
                    $m['sem_4'] = false;
                    $m['sem_5'] = false;
                    $m['sem_6'] = false;
                    $m['is_selected'] = false;
                }
            }
            unset($m);

            $this->jsonResponse([
                'success' => true,
                'data' => $allMapels
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetPdssMapels] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat konfigurasi mapel PDSS.'], 500);
        }
    }

    /**
     * API: Menyimpan daftar mapel pilihan untuk PDSS
     * POST /api/v1/pdss/config-mapel
     */
    public function apiSavePdssMapels(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $configs = $input['configs'] ?? [];
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? '';

        if (!is_array($configs)) {
            $this->jsonResponse(['error' => 'Data konfigurasi tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            if (empty($tahunAjaranId)) {
                $stmtActiveTa = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtActiveTa->execute([$tenantId]);
                $tahunAjaranId = $stmtActiveTa->fetchColumn();
            }

            // Periksa status terkunci untuk Langkah 1
            $stmtLock = $db->prepare("SELECT is_locked FROM pdss_lock WHERE tenant_id = ? AND step = 1 AND tahun_ajaran_id = ? LIMIT 1");
            $stmtLock->execute([$tenantId, $tahunAjaranId]);
            $isLocked = (int)$stmtLock->fetchColumn();
            if ($isLocked === 1) {
                $this->jsonResponse(['error' => 'Langkah 1 (Konfigurasi Mapel) telah dikunci.'], 400);
                return;
            }

            $db->beginTransaction();

            // 1. Hapus konfigurasi lama
            $stmtDel = $db->prepare("DELETE FROM pdss_config_mapel WHERE tenant_id = ? AND tahun_ajaran_id = ?");
            $stmtDel->execute([$tenantId, $tahunAjaranId]);

            // 2. Insert yang baru
            if (!empty($configs)) {
                $stmtIns = $db->prepare("
                    INSERT INTO pdss_config_mapel 
                    (tenant_id, tahun_ajaran_id, mapel_id, sem_1, sem_2, sem_3, sem_4, sem_5, sem_6) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($configs as $cfg) {
                    $mid = (int)($cfg['mapel_id'] ?? 0);
                    if ($mid <= 0) continue;

                    $s1 = ($cfg['sem_1'] ?? false) ? 1 : 0;
                    $s2 = ($cfg['sem_2'] ?? false) ? 1 : 0;
                    $s3 = ($cfg['sem_3'] ?? false) ? 1 : 0;
                    $s4 = ($cfg['sem_4'] ?? false) ? 1 : 0;
                    $s5 = ($cfg['sem_5'] ?? false) ? 1 : 0;
                    $s6 = ($cfg['sem_6'] ?? false) ? 1 : 0;

                    // Hanya masukkan jika minimal ada satu semester yang terpilih
                    if ($s1 || $s2 || $s3 || $s4 || $s5 || $s6) {
                        $stmtIns->execute([$tenantId, $tahunAjaranId, $mid, $s1, $s2, $s3, $s4, $s5, $s6]);
                    }
                }
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Konfigurasi mata pelajaran PDSS berhasil disimpan.']);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[PDSSController::apiSavePdssMapels] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan konfigurasi mapel PDSS.'], 500);
        }
    }

    /**
     * API: Mendapatkan data Kesiapan PDSS & Simulasi Ranking Paralel
     * GET /api/v1/pdss/kesiapan
     */
    private function getSemesterLevel(string $className, string $semester): ?int {
        $className = strtoupper($className);
        $semStr = strtolower(trim($semester));

        // === Support format integer langsung (1-6) ===
        // Jika semester sudah berupa angka (disimpan sebagai '1','2','3','4','5','6')
        if (is_numeric($semStr)) {
            $semNum = (int)$semStr;
            if ($semNum >= 1 && $semNum <= 6) {
                return $semNum; // langsung kembalikan angka semester
            }
        }

        // === Support format string lama: 'Ganjil', 'Genap', 'Semester Ganjil', dst ===
        $isSemGanjil = strpos($semStr, 'ganjil') !== false || strpos($semStr, 'odd') !== false;

        if (strpos($className, '12') !== false || strpos($className, 'XII') !== false) {
            return $isSemGanjil ? 5 : 6;
        }
        if (strpos($className, '11') !== false || strpos($className, 'XI') !== false) {
            return $isSemGanjil ? 3 : 4;
        }
        if (strpos($className, '10') !== false || strpos($className, 'X') !== false) {
            return $isSemGanjil ? 1 : 2;
        }
        return null;
    }

    /**
     * API: Mendapatkan data Kesiapan PDSS & Simulasi Ranking Paralel
     * GET /api/v1/pdss/kesiapan
     */
    public function apiGetKesiapan(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // 1. Ambil daftar tahun ajaran untuk filter
            $stmtYears = $db->prepare("SELECT id, tahun_ajaran, is_active FROM tahun_ajaran WHERE tenant_id = ? ORDER BY tahun_ajaran DESC");
            $stmtYears->execute([$tenantId]);
            $years = $stmtYears->fetchAll(PDO::FETCH_ASSOC);

            // Tentukan tahun ajaran aktif atau fallback ke default
            $tahunAjaranId = isset($_GET['tahun_ajaran_id']) ? $_GET['tahun_ajaran_id'] : '';
            if (empty($tahunAjaranId)) {
                foreach ($years as $yr) {
                    if ((int)$yr['is_active'] === 1) {
                        $tahunAjaranId = $yr['id'];
                        break;
                    }
                }
                if (empty($tahunAjaranId) && !empty($years)) {
                    $tahunAjaranId = $years[0]['id'];
                }
            }

            $selectedTaName = '';
            $systemActiveTaId = '';
            foreach ($years as $yr) {
                if ((int)$yr['is_active'] === 1) {
                    $systemActiveTaId = $yr['id'];
                    break;
                }
            }
            if (empty($systemActiveTaId) && !empty($years)) {
                $systemActiveTaId = $years[0]['id'];
            }

            $isSelectedActive = ($tahunAjaranId == $systemActiveTaId);

            if (!empty($tahunAjaranId)) {
                foreach ($years as $yr) {
                    if ($yr['id'] == $tahunAjaranId) {
                        $selectedTaName = $yr['tahun_ajaran'];
                        break;
                    }
                }
            }

            // 2. Ambil akreditasi sekolah
            $stmtAcc = $db->prepare("SELECT akreditasi FROM tenants WHERE id = ? LIMIT 1");
            $stmtAcc->execute([$tenantId]);
            $accreditation = $stmtAcc->fetchColumn() ?: 'A (Unggul)';

            // 3. Ambil status terkunci untuk semua langkah berdasarkan tahun ajaran
            $stmtLock = $db->prepare("SELECT step, is_locked, locked_by, locked_at FROM pdss_lock WHERE tenant_id = ? AND tahun_ajaran_id = ?");
            $stmtLock->execute([$tenantId, $tahunAjaranId]);
            $lockRows = $stmtLock->fetchAll(PDO::FETCH_ASSOC);

            $locks = [
                1 => ['is_locked' => false, 'locked_by' => null, 'locked_at' => null],
                2 => ['is_locked' => false, 'locked_by' => null, 'locked_at' => null],
                3 => ['is_locked' => false, 'locked_by' => null, 'locked_at' => null],
            ];
            foreach ($lockRows as $lr) {
                $stepNum = (int)$lr['step'];
                if (isset($locks[$stepNum])) {
                    $locks[$stepNum] = [
                        'is_locked' => (bool)$lr['is_locked'],
                        'locked_by' => $lr['locked_by'],
                        'locked_at' => $lr['locked_at']
                    ];
                }
            }

            // 4. Ambil mapel terpilih untuk PDSS berdasarkan tahun ajaran
            $stmtSelected = $db->prepare("
                SELECT pcm.mapel_id, mp.nama_mapel, mp.kode_mapel,
                       pcm.sem_1, pcm.sem_2, pcm.sem_3, pcm.sem_4, pcm.sem_5, pcm.sem_6
                FROM pdss_config_mapel pcm
                JOIN mata_pelajaran mp ON pcm.mapel_id = mp.id
                WHERE pcm.tenant_id = ? AND pcm.tahun_ajaran_id = ? AND mp.deleted_at IS NULL
            ");
            $stmtSelected->execute([$tenantId, $tahunAjaranId]);
            $configMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC);

            if (empty($configMapels)) {
                $this->jsonResponse([
                    'success' => true,
                    'accreditation' => $accreditation,
                    'mapel_not_configured' => true,
                    'locks' => $locks,
                    'years' => $years,
                    'total_configured_semesters' => 0,
                    'data' => []
                ]);
                return;
            }

            // Hitung total semester yang dikonfigurasi secara akumulatif
            $totalConfiguredSemesters = 0;
            $enabledSemestersMap = [];
            foreach ($configMapels as $cfg) {
                $mid = (int)$cfg['mapel_id'];
                for ($sem = 1; $sem <= 6; $sem++) {
                    if ((int)$cfg["sem_$sem"] === 1) {
                        $totalConfiguredSemesters++;
                        $enabledSemestersMap[$mid][$sem] = true;
                    }
                }
            }

            // 4. Ambil manual eligible status
            $stmtManual = $db->prepare("SELECT siswa_id, status_eligible FROM pdss_manual_eligible WHERE tenant_id = ?");
            $stmtManual->execute([$tenantId]);
            $manualEligible = $stmtManual->fetchAll(PDO::FETCH_ASSOC);
            $manualEligibleMap = [];
            foreach ($manualEligible as $me) {
                $manualEligibleMap[$me['siswa_id']] = $me['status_eligible'];
            }



            // 5. Ambil data siswa aktif kelas 12
            $sqlSiswa = "
                SELECT 
                    s.id, 
                    s.nama_lengkap, 
                    s.nisn, 
                    s.nis, 
                    s.id_jurusan,
                    k.nama_kelas, 
                    s.id_kelas,
                    j.nama_jurusan,
                    j.kode_jurusan,
                    s.id_tahun_ajaran
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN jurusan j ON s.id_jurusan = j.id
                LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id
                WHERE s.tenant_id = ?
                  AND s.status = 'Aktif'
                  AND s.deleted_at IS NULL
            ";
            
            $paramsSiswa = [$tenantId];
            if (!empty($tahunAjaranId)) {
                $sqlSiswa .= " AND (
                    -- Kasus 1: Sudah ada nilai semester kelas 12 di tahun ajaran terpilih
                    s.id IN (
                        SELECT DISTINCT dnr.siswa_id 
                        FROM detail_nilai_rapor dnr 
                        JOIN kelas k_dnr ON dnr.kelas_id = k_dnr.id
                        WHERE dnr.tenant_id = ? 
                          AND (k_dnr.nama_kelas LIKE '%12%' OR k_dnr.nama_kelas LIKE '%XII%')
                          AND dnr.tahun_ajaran = ?
                    )
                    -- Kasus 2: Siswa kelas 12 yang terdaftar di tahun ajaran terpilih
                    OR (
                        (k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%')
                        AND (ta.tahun_ajaran = ? OR ta.tahun_ajaran IS NULL)
                    )
                )";
                $paramsSiswa[] = $tenantId;
                $paramsSiswa[] = $selectedTaName;
                $paramsSiswa[] = $selectedTaName;
            } else {
                $sqlSiswa .= " AND (k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%')";
            }

            $stmtSiswa = $db->prepare($sqlSiswa);
            $stmtSiswa->execute($paramsSiswa);
            $students = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

            if (empty($students)) {
                $this->jsonResponse([
                    'success' => true,
                    'accreditation' => $accreditation,
                    'mapel_not_configured' => false,
                    'locks' => $locks,
                    'years' => $years,
                    'total_configured_semesters' => $totalConfiguredSemesters,
                    'data' => []
                ]);
                return;
            }

            $siswaIds = array_column($students, 'id');
            $mapelIds = array_column($configMapels, 'mapel_id');

            // 6. Ambil seluruh data nilai rapor untuk siswa dan mapel terpilih
            $siswaPlaceholders = implode(',', array_fill(0, count($siswaIds), '?'));
            $mapelPlaceholders = implode(',', array_fill(0, count($mapelIds), '?'));

            $sqlGrades = "
                SELECT dnr.siswa_id, dnr.mapel_id, dnr.nilai_akhir, dnr.semester, dnr.tahun_ajaran, k.nama_kelas, dnr.kelas_id
                FROM detail_nilai_rapor dnr
                JOIN kelas k ON dnr.kelas_id = k.id
                WHERE dnr.tenant_id = ?
                  AND dnr.deleted_at IS NULL
                  AND dnr.mapel_id IN ($mapelPlaceholders)
                  AND dnr.siswa_id IN ($siswaPlaceholders)
            ";

            $stmtGrades = $db->prepare($sqlGrades);
            $paramsGrades = array_merge([$tenantId], $mapelIds, $siswaIds);
            $stmtGrades->execute($paramsGrades);
            $allGrades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

            // Identifikasi mata pelajaran Agama dari konfigurasi
            $religionMapelIds = [];
            foreach ($configMapels as $cfg) {
                $mid = (int)$cfg['mapel_id'];
                $mname = strtolower($cfg['nama_mapel'] ?? '');
                if (strpos($mname, 'agama') !== false || strpos($mname, 'kepercayaan') !== false) {
                    $religionMapelIds[$mid] = true;
                }
            }

            // Memetakan kelas dan nilai siswa per semester, memilih tahun ajaran terbaru (tidak naik kelas)
            // Serta memetakan kurikulum aktif (mata pelajaran apa saja yang aktif di tiap kelas pada tiap semester)
            $studentClassMap = [];       // $siswa_id => $semLevel => ['class_id' => X, 'tahun_ajaran' => Y]
            $studentGradesMap = [];      // $siswa_id => $mapel_id => $semLevel => ['nilai' => X, 'tahun_ajaran' => Y]
            $classActiveMapelsMap = [];  // $class_id => $semLevel => $mapel_id => true

            foreach ($allGrades as $g) {
                $sid = $g['siswa_id'];
                $mid = (int)$g['mapel_id'];
                $semLevel = $this->getSemesterLevel($g['nama_kelas'], $g['semester']);
                if ($semLevel === null) continue;

                $cid = $g['kelas_id'];
                $val = (float)$g['nilai_akhir'];
                $year = $g['tahun_ajaran'];

                // 1. Petakan mapel aktif di kelas tersebut pada semester tersebut
                $classActiveMapelsMap[$cid][$semLevel][$mid] = true;

                // 2. Petakan kelas siswa pada semester tersebut (pilih yang terbaru jika mengulang kelas)
                if (!isset($studentClassMap[$sid][$semLevel])) {
                    $studentClassMap[$sid][$semLevel] = [
                        'class_id' => $cid,
                        'tahun_ajaran' => $year
                    ];
                } else {
                    if ($year > $studentClassMap[$sid][$semLevel]['tahun_ajaran']) {
                        $studentClassMap[$sid][$semLevel] = [
                            'class_id' => $cid,
                            'tahun_ajaran' => $year
                        ];
                    }
                }

                // 3. Petakan nilai siswa per mapel per semester (pilih tahun ajaran terbaru jika mengulang kelas)
                if (!isset($studentGradesMap[$sid][$mid][$semLevel])) {
                    $studentGradesMap[$sid][$mid][$semLevel] = [
                        'nilai' => $val,
                        'tahun_ajaran' => $year
                    ];
                } else {
                    if ($year > $studentGradesMap[$sid][$mid][$semLevel]['tahun_ajaran']) {
                        $studentGradesMap[$sid][$mid][$semLevel] = [
                            'nilai' => $val,
                            'tahun_ajaran' => $year
                        ];
                    }
                }
            }

            // 7. Hitung rata-rata dan total nilai dinamis per siswa
            $processedStudents = [];
            foreach ($students as $s) {
                $sid = $s['id'];
                $studentSum = 0.0;
                $studentExpectedCount = 0;
                $totalActual = 0;

                for ($sem = 1; $sem <= 6; $sem++) {
                    // Cek apakah semester ini dikonfigurasi aktif di Langkah 1
                    $semConfigured = false;
                    foreach ($configMapels as $cfg) {
                        if ((int)$cfg["sem_$sem"] === 1) {
                            $semConfigured = true;
                            break;
                        }
                    }
                    if (!$semConfigured) continue;

                    // Ambil kelas siswa pada semester ini
                    $cid = $studentClassMap[$sid][$sem]['class_id'] ?? null;
                    if (!$cid) continue; // Lewati jika tidak ada riwayat kelas (misal siswa pindahan yang belum memiliki nilai semester awal)

                    $semExpected = 0;
                    $semSum = 0.0;

                    // A. Hitung Mapel Non-Agama
                    foreach ($configMapels as $cfg) {
                        $mid = (int)$cfg['mapel_id'];
                        if ((int)$cfg["sem_$sem"] !== 1) continue;
                        if (isset($religionMapelIds[$mid])) continue; // Lewati agama, dihitung di slot khusus

                        // Hanya hitung jika mapel ini aktif di kurikulum kelas siswa tersebut pada semester ini
                        if (empty($classActiveMapelsMap[$cid][$sem][$mid])) continue;

                        $semExpected++;
                        if (isset($studentGradesMap[$sid][$mid][$sem])) {
                            $semSum += $studentGradesMap[$sid][$mid][$sem]['nilai'];
                            $totalActual++;
                        }
                    }

                    // B. Hitung Mapel Agama (Digabung menjadi 1 slot nilai tertinggi)
                    $religionActiveInClass = false;
                    $bestReligionGrade = null;
                    foreach ($configMapels as $cfg) {
                        $mid = (int)$cfg['mapel_id'];
                        if ((int)$cfg["sem_$sem"] !== 1) continue;
                        if (!isset($religionMapelIds[$mid])) continue;

                        // Cek apakah mapel agama ini aktif di kelas siswa tersebut
                        if (!empty($classActiveMapelsMap[$cid][$sem][$mid])) {
                            $religionActiveInClass = true;
                            if (isset($studentGradesMap[$sid][$mid][$sem])) {
                                $rGrade = $studentGradesMap[$sid][$mid][$sem]['nilai'];
                                if ($bestReligionGrade === null || $rGrade > $bestReligionGrade) {
                                    $bestReligionGrade = $rGrade;
                                }
                            }
                        }
                    }

                    if ($religionActiveInClass) {
                        $semExpected++;
                        if ($bestReligionGrade !== null) {
                            $semSum += $bestReligionGrade;
                            $totalActual++;
                        }
                    }

                    $studentSum += $semSum;
                    $studentExpectedCount += $semExpected;
                }

                $rataRata = $studentExpectedCount > 0 ? ($studentSum / $studentExpectedCount) : 0.0;
                $statusEligible = $manualEligibleMap[$sid] ?? 'auto';

                $processedStudents[] = [
                    'id' => $s['id'],
                    'nama_lengkap' => $s['nama_lengkap'],
                    'nisn' => $s['nisn'],
                    'nis' => $s['nis'],
                    'id_jurusan' => $s['id_jurusan'],
                    'nama_kelas' => $s['nama_kelas'],
                    'nama_jurusan' => $s['nama_jurusan'],
                    'kode_jurusan' => $s['kode_jurusan'],
                    'rata_rata' => $rataRata,
                    'total_nilai' => $studentSum,
                    'jumlah_nilai' => $totalActual,
                    'expected_nilai' => $studentExpectedCount, // nilai yang diharapkan per siswa (bukan global)
                    'status_eligible' => $statusEligible
                ];
            }

            // Sort by rata_rata desc, nama_lengkap asc
            usort($processedStudents, function($a, $b) {
                if ($a['rata_rata'] == $b['rata_rata']) {
                    return strcmp($a['nama_lengkap'], $b['nama_lengkap']);
                }
                return ($a['rata_rata'] > $b['rata_rata']) ? -1 : 1;
            });

            $this->jsonResponse([
                'success' => true,
                'accreditation' => $accreditation,
                'mapel_not_configured' => false,
                'total_configured_mapels' => count($configMapels),
                'total_configured_semesters' => $totalConfiguredSemesters,
                'locks' => $locks,
                'years' => $years,
                'data' => $processedStudents
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetKesiapan] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data simulasi PDSS.'], 500);
        }
    }

    /**
     * API: Mencari siswa berdasarkan nama/NISN/NIS di sekolah aktif
     * GET /api/v1/pdss/students/search?q=...
     */
    public function apiSearchStudents(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        $query = isset($_GET['q']) ? trim($_GET['q']) : '';

        try {
            $db = \App\Config\Database::getConnection();
            
            $sql = "
                SELECT id, nama_lengkap, nisn, nis 
                FROM siswa 
                WHERE tenant_id = ? 
                  AND deleted_at IS NULL
            ";
            $params = [$tenantId];

            if (!empty($query)) {
                $sql .= " AND (LOWER(nama_lengkap) LIKE ? OR LOWER(nisn) LIKE ? OR LOWER(nis) LIKE ?)";
                $searchVal = '%' . strtolower($query) . '%';
                $params[] = $searchVal;
                $params[] = $searchVal;
                $params[] = $searchVal;
            }

            $sql .= " ORDER BY nama_lengkap ASC LIMIT 20";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => $students
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiSearchStudents] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mencari data siswa.'], 500);
        }
    }

    /**
     * API: Ambil daftar alumni tracking
     * GET /api/v1/pdss/alumni-tracks
     */
    public function apiGetAlumniTracks(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    rk.id, 
                    rk.id_siswa,
                    COALESCE(s.nama_lengkap, rk.nama_alumni) AS nama_alumni, 
                    rk.tahun_masuk, 
                    rk.jenis_kampus,
                    rk.jenis_kampus AS jenis_campus, 
                    rk.jalur_masuk, 
                    rk.nama_kampus AS universitas_nama, 
                    rk.jurusan AS jurusan_nama, 
                    rk.status_kuliah AS status
                FROM riwayat_kuliah rk
                LEFT JOIN siswa s ON rk.id_siswa = s.id
                WHERE rk.tenant_id = ?
                ORDER BY rk.tahun_masuk DESC, nama_alumni ASC
            ");
            $stmt->execute([$tenantId]);
            $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => $tracks
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetAlumniTracks] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data tracking alumni.'], 500);
        }
    }

    /**
     * API: Simpan / Update tracking alumni
     * POST /api/v1/pdss/alumni-tracks
     */
    public function apiSaveAlumniTrack(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['error' => 'Akses ditolak. Peran Anda tidak diizinkan mengubah data.'], 403);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak teridenteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $this->sanitize($input['id'] ?? '');
        $idSiswa = $this->sanitize($input['id_siswa'] ?? '');
        $namaAlumni = $this->sanitize($input['nama_alumni'] ?? '');
        $tahunMasuk = (int)($input['tahun_masuk'] ?? 0);
        $jenisKampus = $this->sanitize($input['jenis_kampus'] ?? '');
        $jalurMasuk = $this->sanitize($input['jalur_masuk'] ?? '');
        $universitasNama = $this->sanitize($input['universitas_nama'] ?? '');
        $jurusanNama = $this->sanitize($input['jurusan_nama'] ?? '');
        $status = $this->sanitize($input['status'] ?? '');

        if (empty($idSiswa)) {
            $idSiswa = null;
        }

        // Validasi input
        if (empty($namaAlumni) || $tahunMasuk < 1900 || empty($jenisKampus) || empty($jalurMasuk) || empty($universitasNama) || empty($jurusanNama) || empty($status)) {
            $this->jsonResponse(['error' => 'Mohon lengkapi seluruh field dengan benar.'], 422);
            return;
        }

        // Validasi ENUM
        $validJenis = ['Negeri', 'Swasta', 'Kedinasan'];
        $validJalur = ['SNBP', 'SNBT', 'Mandiri', 'Beasiswa', 'Jalur Swasta', 'Kedinasan', 'Lainnya'];
        $validStatus = ['Aktif', 'Aktif Kuliah', 'Lulus', 'Drop'];

        if (!in_array($jenisKampus, $validJenis, true) || !in_array($jalurMasuk, $validJalur, true) || !in_array($status, $validStatus, true)) {
            $this->jsonResponse(['error' => 'Nilai opsi pilihan tidak valid.'], 422);
            return;
        }

        // Map status
        $statusDb = ($status === 'Aktif Kuliah') ? 'Aktif' : $status;

        try {
            $db = \App\Config\Database::getConnection();

            if (empty($id)) {
                // INSERT baru
                $stmt = $db->prepare("
                    INSERT INTO riwayat_kuliah 
                        (id_siswa, tenant_id, nama_alumni, nama_kampus, jurusan, tahun_masuk, jenis_kampus, jalur_masuk, status_kuliah)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $idSiswa, $tenantId, $namaAlumni, $universitasNama, $jurusanNama, $tahunMasuk, $jenisKampus, $jalurMasuk, $statusDb
                ]);
                $msg = 'Data alumni baru berhasil ditambahkan.';
            } else {
                // UPDATE yang sudah ada
                // Pastikan data milik tenant yang sama (anti-IDOR)
                $stmtCheck = $db->prepare("SELECT id FROM riwayat_kuliah WHERE id = ? AND tenant_id = ? LIMIT 1");
                $stmtCheck->execute([$id, $tenantId]);
                if (!$stmtCheck->fetchColumn()) {
                    $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404);
                    return;
                }

                $stmt = $db->prepare("
                    UPDATE riwayat_kuliah 
                    SET 
                        id_siswa = ?,
                        nama_alumni = ?, 
                        tahun_masuk = ?, 
                        jenis_kampus = ?, 
                        jalur_masuk = ?, 
                        nama_kampus = ?, 
                        jurusan = ?, 
                        status_kuliah = ?
                    WHERE id = ? AND tenant_id = ?
                ");
                $stmt->execute([
                    $idSiswa, $namaAlumni, $tahunMasuk, $jenisKampus, $jalurMasuk, $universitasNama, $jurusanNama, $statusDb, $id, $tenantId
                ]);
                $msg = 'Data alumni berhasil diperbarui.';
            }

            $this->jsonResponse(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiSaveAlumniTrack] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan data alumni.'], 500);
        }
    }

    /**
     * API: Hapus tracking alumni
     * POST /api/v1/pdss/alumni-tracks/delete
     */
    public function apiDeleteAlumniTrack(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak teridenteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $this->sanitize($input['id'] ?? '');

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID data wajib disertakan.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("DELETE FROM riwayat_kuliah WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenantId]);

            $this->jsonResponse(['success' => true, 'message' => 'Data tracking alumni berhasil dihapus.']);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiDeleteAlumniTrack] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus data alumni.'], 500);
        }
    }

    /**
     * API: Mendapatkan daftar target kampus
     * GET /api/v1/pdss/target-kampus
     */
    public function apiGetTargetKampus(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("
                SELECT id, nama_kampus, jenis_kampus, kuota_target
                FROM target_kampus
                WHERE tenant_id = ?
                ORDER BY jenis_kampus ASC, nama_kampus ASC
            ");
            $stmt->execute([$tenantId]);
            $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => $campuses
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetTargetKampus] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data target kampus.'], 500);
        }
    }

    /**
     * API: Simpan / Update target kampus
     * POST /api/v1/pdss/target-kampus
     */
    public function apiSaveTargetKampus(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $this->sanitize($input['id'] ?? '');
        $namaKampus = $this->sanitize($input['nama_kampus'] ?? '');
        $jenisKampus = $this->sanitize($input['jenis_kampus'] ?? '');
        $kuotaTarget = (int)($input['kuota_target'] ?? 0);

        if (empty($namaKampus) || empty($jenisKampus) || $kuotaTarget < 0) {
            $this->jsonResponse(['error' => 'Mohon lengkapi data dengan benar.'], 422);
            return;
        }

        $validJenis = ['Negeri', 'Swasta', 'Kedinasan'];
        if (!in_array($jenisKampus, $validJenis, true)) {
            $this->jsonResponse(['error' => 'Jenis kampus tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            if (empty($id)) {
                $id = $this->generateUuidV4();
                $stmt = $db->prepare("
                    INSERT INTO target_kampus (id, tenant_id, nama_kampus, jenis_kampus, kuota_target)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id, $tenantId, $namaKampus, $jenisKampus, $kuotaTarget]);
                $msg = 'Target kampus baru berhasil disimpan.';
            } else {
                // Pastikan data milik tenant (anti-IDOR)
                $stmtCheck = $db->prepare("SELECT id FROM target_kampus WHERE id = ? AND tenant_id = ? LIMIT 1");
                $stmtCheck->execute([$id, $tenantId]);
                if (!$stmtCheck->fetchColumn()) {
                    $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404);
                    return;
                }

                $stmt = $db->prepare("
                    UPDATE target_kampus 
                    SET nama_kampus = ?, jenis_kampus = ?, kuota_target = ? 
                    WHERE id = ? AND tenant_id = ?
                ");
                $stmt->execute([$namaKampus, $jenisKampus, $kuotaTarget, $id, $tenantId]);
                $msg = 'Konfigurasi target kampus berhasil diperbarui.';
            }

            $this->jsonResponse(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiSaveTargetKampus] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan target kampus.'], 500);
        }
    }

    /**
     * API: Hapus target kampus
     * POST /api/v1/pdss/target-kampus/delete
     */
    public function apiDeleteTargetKampus(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $id = $this->sanitize($input['id'] ?? '');

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID data wajib disertakan.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("DELETE FROM target_kampus WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenantId]);

            $this->jsonResponse(['success' => true, 'message' => 'Target kampus berhasil dihapus.']);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiDeleteTargetKampus] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus target kampus.'], 500);
        }
    }

    /**
     * API: Seed target kampus default untuk sekolah
     * POST /api/v1/pdss/target-kampus/seed
     */
    public function apiSeedTargetKampus(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // Default list universitas premium Indonesia
            $defaultList = [
                ['Universitas Indonesia (UI)', 'Negeri', 5],
                ['Institut Teknologi Bandung (ITB)', 'Negeri', 5],
                ['Universitas Gadjah Mada (UGM)', 'Negeri', 5],
                ['Institut Pertanian Bogor (IPB)', 'Negeri', 3],
                ['Institut Teknologi Sepuluh Nopember (ITS)', 'Negeri', 3],
                ['Universitas Airlangga (Unair)', 'Negeri', 3],
                ['Universitas Padjadjaran (Unpad)', 'Negeri', 3],
                ['Universitas Diponegoro (Undip)', 'Negeri', 3],
                ['Universitas Brawijaya (UB)', 'Negeri', 3],
                ['Binus University', 'Swasta', 2],
                ['Telkom University', 'Swasta', 2],
                ['PKN STAN', 'Kedinasan', 1],
                ['Politeknik Statistika STIS', 'Kedinasan', 1],
                ['Sekolah Tinggi Intelijen Negara (STIN)', 'Kedinasan', 1]
            ];

            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO target_kampus (id, tenant_id, nama_kampus, jenis_kampus, kuota_target) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE kuota_target = VALUES(kuota_target)
            ");

            foreach ($defaultList as $item) {
                // Cek dulu apakah nama_kampus sudah ada di tenant ini agar tidak duplikat visual
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM target_kampus WHERE tenant_id = ? AND nama_kampus = ?");
                $stmtCheck->execute([$tenantId, $item[0]]);
                if ((int)$stmtCheck->fetchColumn() > 0) {
                    continue; // Lewati jika sudah ada
                }

                $id = $this->generateUuidV4();
                $stmt->execute([$id, $tenantId, $item[0], $item[1], $item[2]]);
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Seeding data target kampus berhasil diselesaikan.']);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[PDSSController::apiSeedTargetKampus] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal melakukan seeding data target kampus.'], 500);
        }
    }

    /**
     * API: Menyimpan penetapan status kelayakan manual oleh guru BK
     * POST /api/v1/pdss/manual-eligible
     */
    public function apiSaveManualEligible(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $siswaId = $input['siswa_id'] ?? null;
        $status = $input['status_eligible'] ?? 'auto'; // 'auto', 'eligible', 'tidak_eligible'

        // Periksa status terkunci untuk Langkah 3
        try {
            $db = \App\Config\Database::getConnection();

            $tahunAjaranId = '';
            if (!empty($siswaId)) {
                $stmtStudentTa = $db->prepare("SELECT id_tahun_ajaran FROM siswa WHERE id = ? LIMIT 1");
                $stmtStudentTa->execute([$siswaId]);
                $tahunAjaranId = $stmtStudentTa->fetchColumn();
            }
            if (empty($tahunAjaranId)) {
                $stmtActiveTa = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtActiveTa->execute([$tenantId]);
                $tahunAjaranId = $stmtActiveTa->fetchColumn();
            }

            $stmtLock = $db->prepare("SELECT is_locked FROM pdss_lock WHERE tenant_id = ? AND step = 3 AND tahun_ajaran_id = ? LIMIT 1");
            $stmtLock->execute([$tenantId, $tahunAjaranId]);
            $isLocked = (int)$stmtLock->fetchColumn();
            if ($isLocked === 1) {
                $this->jsonResponse(['error' => 'Langkah 3 (Kelayakan & Ranking) telah dikunci.'], 400);
                return;
            }
        } catch (\Throwable $e) {}

        if (empty($siswaId) || !in_array($status, ['auto', 'eligible', 'tidak_eligible'], true)) {
            $this->jsonResponse(['error' => 'Parameter tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            
            // Simpan atau update
            $stmt = $db->prepare("
                INSERT INTO pdss_manual_eligible (tenant_id, siswa_id, status_eligible)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE status_eligible = VALUES(status_eligible)
            ");
            $stmt->execute([$tenantId, $siswaId, $status]);

            $this->jsonResponse(['success' => true, 'message' => 'Status kelayakan manual siswa berhasil diperbarui.']);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiSaveManualEligible] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memperbarui status kelayakan manual.'], 500);
        }
    }

    /**
     * API: Mengunci atau membuka kunci data kesiapan PDSS
     * POST /api/v1/pdss/lock
     */
    public function apiToggleLock(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $input = $this->getJsonInput();
        $isLocked = ($input['is_locked'] ?? false) ? 1 : 0;
        $step = isset($input['step']) ? (int)$input['step'] : 1;
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? '';
        
        $userName = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Guru BK';
        $now = date('Y-m-d H:i:s');

        try {
            $db = \App\Config\Database::getConnection();

            if (empty($tahunAjaranId)) {
                $stmtActiveTa = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtActiveTa->execute([$tenantId]);
                $tahunAjaranId = $stmtActiveTa->fetchColumn();
            }
            
            $stmt = $db->prepare("
                INSERT INTO pdss_lock (tenant_id, step, tahun_ajaran_id, is_locked, locked_by, locked_at)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE is_locked = VALUES(is_locked), locked_by = VALUES(locked_by), locked_at = VALUES(locked_at)
            ");
            $stmt->execute([$tenantId, $step, $tahunAjaranId, $isLocked, $isLocked ? $userName : null, $isLocked ? $now : null]);

            $msg = $isLocked ? "Langkah $step berhasil dikunci." : "Kunci Langkah $step berhasil dibuka.";
            $this->jsonResponse(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiToggleLock] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengubah status penguncian.'], 500);
        }
    }

    /**
     * API: Mendapatkan detail nilai 5 semester per mata pelajaran terkonfigurasi untuk audit BK
     * GET /api/v1/pdss/student-grades?siswa_id=...
     */
    public function apiGetStudentGrades(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        $siswaId = $_GET['siswa_id'] ?? null;
        if (empty($siswaId)) {
            $this->jsonResponse(['error' => 'Siswa ID tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // 1. Validasi siswa milik tenant dan ambil data dasarnya
            $stmtSiswa = $db->prepare("
                SELECT s.id, s.nama_lengkap, s.nisn, k.nama_kelas, j.nama_jurusan, s.id_tahun_ajaran
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN jurusan j ON s.id_jurusan = j.id
                WHERE s.id = ? AND s.tenant_id = ? AND s.deleted_at IS NULL LIMIT 1
            ");
            $stmtSiswa->execute([$siswaId, $tenantId]);
            $student = $stmtSiswa->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                $this->jsonResponse(['error' => 'Siswa tidak ditemukan atau akses ditolak.'], 404);
                return;
            }

            // 2. Ambil seluruh mata pelajaran pilihan beserta config semesternya berdasarkan tahun ajaran siswa
            $tahunAjaranId = $student['id_tahun_ajaran'] ?? '';
            if (empty($tahunAjaranId)) {
                $stmtActiveTa = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtActiveTa->execute([$tenantId]);
                $tahunAjaranId = $stmtActiveTa->fetchColumn();
            }

            $stmtSelected = $db->prepare("
                SELECT pcm.mapel_id, mp.nama_mapel, mp.kode_mapel,
                       pcm.sem_1, pcm.sem_2, pcm.sem_3, pcm.sem_4, pcm.sem_5, pcm.sem_6
                FROM pdss_config_mapel pcm
                JOIN mata_pelajaran mp ON pcm.mapel_id = mp.id
                WHERE pcm.tenant_id = ? AND pcm.tahun_ajaran_id = ? AND mp.deleted_at IS NULL
                ORDER BY mp.nama_mapel ASC
            ");
            $stmtSelected->execute([$tenantId, $tahunAjaranId]);
            $configMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC);

            if (empty($configMapels)) {
                $this->jsonResponse(['success' => true, 'student' => $student, 'grades' => []]);
                return;
            }

            $mapelIds = array_column($configMapels, 'mapel_id');
            $mapelPlaceholders = implode(',', array_fill(0, count($mapelIds), '?'));

            // 3. Ambil seluruh data nilai rapor siswa untuk mapel tersebut
            $sqlGrades = "
                SELECT dnr.mapel_id, dnr.nilai_akhir, dnr.semester, dnr.tahun_ajaran, k.nama_kelas
                FROM detail_nilai_rapor dnr
                JOIN kelas k ON dnr.kelas_id = k.id
                WHERE dnr.siswa_id = ? 
                  AND dnr.tenant_id = ?
                  AND dnr.deleted_at IS NULL
                  AND dnr.mapel_id IN ($mapelPlaceholders)
            ";
            $stmtGrades = $db->prepare($sqlGrades);
            $params = array_merge([$siswaId, $tenantId], $mapelIds);
            $stmtGrades->execute($params);
            $rawGrades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

            // Group grades by mapel -> sem_level (keeping latest tahun_ajaran if duplicate)
            $groupedGrades = [];
            foreach ($rawGrades as $rg) {
                $mid = (int)$rg['mapel_id'];
                $semLevel = $this->getSemesterLevel($rg['nama_kelas'], $rg['semester']);
                if ($semLevel === null) continue;

                $val = (float)$rg['nilai_akhir'];
                $year = $rg['tahun_ajaran'];

                if (!isset($groupedGrades[$mid][$semLevel])) {
                    $groupedGrades[$mid][$semLevel] = [
                        'nilai' => $val,
                        'tahun_ajaran' => $year
                    ];
                } else {
                    $existingYear = $groupedGrades[$mid][$semLevel]['tahun_ajaran'];
                    if ($year > $existingYear) {
                        $groupedGrades[$mid][$semLevel] = [
                            'nilai' => $val,
                            'tahun_ajaran' => $year
                        ];
                    }
                }
            }

            // 4. Susun struktur response tabel audit mapel (semester 1 s.d 6)
            $gradesResponse = [];
            foreach ($configMapels as $cfg) {
                $mid = (int)$cfg['mapel_id'];
                
                $row = [
                    'mapel_id' => $mid,
                    'nama_mapel' => $cfg['nama_mapel'],
                    'kode_mapel' => $cfg['kode_mapel']
                ];

                for ($sem = 1; $sem <= 6; $sem++) {
                    $isConfigured = ((int)$cfg["sem_$sem"] === 1);
                    $row["sem_$sem"] = [
                        'is_configured' => $isConfigured,
                        'nilai' => null,
                        'tahun_ajaran' => null
                    ];

                    if ($isConfigured && isset($groupedGrades[$mid][$sem])) {
                        $row["sem_$sem"]['nilai'] = $groupedGrades[$mid][$sem]['nilai'];
                        $row["sem_$sem"]['tahun_ajaran'] = $groupedGrades[$mid][$sem]['tahun_ajaran'];
                    }
                }

                $gradesResponse[] = $row;
            }

            $this->jsonResponse([
                'success' => true,
                'student' => $student,
                'grades' => $gradesResponse
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetStudentGrades] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat detail nilai rapor siswa.'], 500);
        }
    }

    /**
     * API: Ekspor Buku Leger Rapor 5 Semester untuk Siswa Kelas 12 Aktif
     * GET /api/v1/pdss/download-leger
     */
    public function apiDownloadLeger(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            echo "Akses ditolak atau tenant tidak terdeteksi.";
            return;
        }

        $semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 1;
        if ($semester < 1 || $semester > 6) {
            $semester = 1;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // Filter berdasarkan tahun ajaran jika parameter disediakan
            $tahunAjaranId = isset($_GET['tahun_ajaran_id']) ? $_GET['tahun_ajaran_id'] : '';
            
            // Dapatkan ID Tahun Ajaran aktif sesungguhnya
            $stmtSystemActive = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
            $stmtSystemActive->execute([$tenantId]);
            $systemActiveTaId = $stmtSystemActive->fetchColumn();

            if (empty($tahunAjaranId)) {
                $tahunAjaranId = $systemActiveTaId;
            }

            $isSelectedActive = ($tahunAjaranId == $systemActiveTaId);

            $selectedTaName = '';
            if (!empty($tahunAjaranId)) {
                $stmtTa = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ? LIMIT 1");
                $stmtTa->execute([$tahunAjaranId]);
                $selectedTaName = $stmtTa->fetchColumn() ?: '';
            }

            // 1. Ambil mapel pilihan beserta config semesternya yang aktif pada semester terpilih untuk tahun ajaran tertentu
            // Dan pastikan mapel tersebut ada di pemetaan_mapel (Setting Kurikulum) untuk tahun ajaran tersebut
            $stmtSelected = $db->prepare("
                SELECT DISTINCT pcm.mapel_id, mp.nama_mapel, mp.kode_mapel,
                       pcm.sem_1, pcm.sem_2, pcm.sem_3, pcm.sem_4, pcm.sem_5, pcm.sem_6
                FROM pdss_config_mapel pcm
                JOIN mata_pelajaran mp ON pcm.mapel_id = mp.id
                JOIN pemetaan_mapel pm ON mp.id = pm.mapel_id
                WHERE pcm.tenant_id = ? 
                  AND pcm.tahun_ajaran_id = ? 
                  AND pm.tahun_ajaran = ?
                  AND mp.deleted_at IS NULL 
                  AND pcm.sem_$semester = 1
                ORDER BY mp.nama_mapel ASC
            ");
            $stmtSelected->execute([$tenantId, $tahunAjaranId, $selectedTaName]);
            $configMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC);

            if (empty($configMapels)) {
                echo "Mata pelajaran PDSS belum dikonfigurasi untuk Semester $semester di Langkah 1.";
                return;
            }

            // 2. Identifikasi mata pelajaran Agama dari konfigurasi
            $religionMapelIds = [];
            $religionConfigured = false;
            foreach ($configMapels as $cfg) {
                $mid = (int)$cfg['mapel_id'];
                $mname = strtolower($cfg['nama_mapel'] ?? '');
                if (strpos($mname, 'agama') !== false || strpos($mname, 'kepercayaan') !== false) {
                    $religionMapelIds[$mid] = true;
                    $religionConfigured = true;
                }
            }

            // Susun kolom dinamis untuk leger: Mapel (Agama digabung menjadi 1 slot)
            $dynamicColumns = []; 
            foreach ($configMapels as $cfg) {
                $mid = (int)$cfg['mapel_id'];
                if (isset($religionMapelIds[$mid])) continue;

                $dynamicColumns[] = [
                    'type' => 'non-religion',
                    'mapel_id' => $mid,
                    'label' => $cfg['nama_mapel']
                ];
            }

            if ($religionConfigured) {
                $dynamicColumns[] = [
                    'type' => 'religion',
                    'label' => 'Pendidikan Agama'
                ];
            }



            // 3. Ambil data siswa aktif kelas 12
            $sqlSiswa = "
                SELECT s.id, s.nama_lengkap, s.nisn, s.nis, k.nama_kelas, j.nama_jurusan, s.id_tahun_ajaran
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN jurusan j ON s.id_jurusan = j.id
                LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id
                WHERE s.tenant_id = ?
                  AND s.status = 'Aktif'
                  AND s.deleted_at IS NULL
            ";
            $paramsSiswa = [$tenantId];
            if (!empty($tahunAjaranId)) {
                $sqlSiswa .= " AND (
                    -- Kasus 1: Sudah ada nilai Semester 5 di tahun ajaran terpilih
                    s.id IN (
                        SELECT DISTINCT dnr.siswa_id 
                        FROM detail_nilai_rapor dnr 
                        JOIN kelas k_dnr ON dnr.kelas_id = k_dnr.id
                        WHERE dnr.tenant_id = ? 
                          AND dnr.semester = 'Ganjil' 
                          AND (k_dnr.nama_kelas LIKE '%12%' OR k_dnr.nama_kelas LIKE '%XII%')
                          AND dnr.tahun_ajaran = ?
                    )
                    -- Kasus 2: Belum ada nilai Semester 5 sama sekali, tapi secara angkatan/teoritis dia kelas 12 di tahun ajaran terpilih
                    OR (
                        s.id NOT IN (
                            SELECT DISTINCT dnr.siswa_id 
                            FROM detail_nilai_rapor dnr 
                            JOIN kelas k_dnr ON dnr.kelas_id = k_dnr.id
                            WHERE dnr.tenant_id = ? 
                              AND dnr.semester = 'Ganjil'
                              AND (k_dnr.nama_kelas LIKE '%12%' OR k_dnr.nama_kelas LIKE '%XII%')
                        )
                        AND (k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%')
                        AND (
                            CONCAT(
                                CAST(SUBSTRING(ta.tahun_ajaran, 1, 4) AS UNSIGNED) + 2,
                                '/',
                                CAST(SUBSTRING(ta.tahun_ajaran, 1, 4) AS UNSIGNED) + 3
                            ) = ?
                            OR ta.tahun_ajaran = ?
                        )
                    )
                )";
                $paramsSiswa[] = $tenantId;
                $paramsSiswa[] = $selectedTaName;
                $paramsSiswa[] = $tenantId;
                $paramsSiswa[] = $selectedTaName;
                $paramsSiswa[] = $selectedTaName;
            } else {
                $sqlSiswa .= " AND (k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%')";
            }
            $stmtSiswa = $db->prepare($sqlSiswa);
            $stmtSiswa->execute($paramsSiswa);
            $students = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

            if (empty($students)) {
                echo "Tidak ada data siswa kelas 12 aktif.";
                return;
            }

            $siswaIds = array_column($students, 'id');
            $mapelIds = array_column($configMapels, 'mapel_id');

            // 4. Ambil data nilai
            $siswaPlaceholders = implode(',', array_fill(0, count($siswaIds), '?'));
            $mapelPlaceholders = implode(',', array_fill(0, count($mapelIds), '?'));

            $sqlGrades = "
                SELECT dnr.siswa_id, dnr.mapel_id, dnr.nilai_akhir, dnr.semester, dnr.tahun_ajaran, k.nama_kelas, dnr.kelas_id
                FROM detail_nilai_rapor dnr
                JOIN kelas k ON dnr.kelas_id = k.id
                WHERE dnr.tenant_id = ?
                  AND dnr.deleted_at IS NULL
                  AND dnr.mapel_id IN ($mapelPlaceholders)
                  AND dnr.siswa_id IN ($siswaPlaceholders)
            ";

            $stmtGrades = $db->prepare($sqlGrades);
            $paramsGrades = array_merge([$tenantId], $mapelIds, $siswaIds);
            $stmtGrades->execute($paramsGrades);
            $allGrades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

            // Group & filter grades by student -> mapel -> semester_level
            // Handle siswa tidak naik kelas: choose latest tahun_ajaran
            // Dan memetakan riwayat kelas serta kurikulum aktif
            $studentClassMap = [];       // $siswa_id => $semLevel => ['class_id' => X, 'tahun_ajaran' => Y]
            $studentGradesMap = [];      // $siswa_id => $mapel_id => $semLevel => ['nilai' => X, 'tahun_ajaran' => Y]
            $classActiveMapelsMap = [];  // $class_id => $semLevel => $mapel_id => true

            foreach ($allGrades as $g) {
                $sid = $g['siswa_id'];
                $mid = (int)$g['mapel_id'];
                $semLevel = $this->getSemesterLevel($g['nama_kelas'], $g['semester']);
                if ($semLevel === null) continue;

                $cid = $g['kelas_id'];
                $val = (float)$g['nilai_akhir'];
                $year = $g['tahun_ajaran'];

                $classActiveMapelsMap[$cid][$semLevel][$mid] = true;

                if (!isset($studentClassMap[$sid][$semLevel])) {
                    $studentClassMap[$sid][$semLevel] = [
                        'class_id' => $cid,
                        'nama_kelas' => $g['nama_kelas'],
                        'tahun_ajaran' => $year
                    ];
                } else {
                    if ($year > $studentClassMap[$sid][$semLevel]['tahun_ajaran']) {
                        $studentClassMap[$sid][$semLevel] = [
                            'class_id' => $cid,
                            'nama_kelas' => $g['nama_kelas'],
                            'tahun_ajaran' => $year
                        ];
                    }
                }

                if (!isset($studentGradesMap[$sid][$mid][$semLevel])) {
                    $studentGradesMap[$sid][$mid][$semLevel] = [
                        'nilai' => $val,
                        'tahun_ajaran' => $year
                    ];
                } else {
                    if ($year > $studentGradesMap[$sid][$mid][$semLevel]['tahun_ajaran']) {
                        $studentGradesMap[$sid][$mid][$semLevel] = [
                            'nilai' => $val,
                            'tahun_ajaran' => $year
                        ];
                    }
                }
            }

            // 5. Build Excel Rows
            $xlsxRows = [];

            // Header Row
            $header = ['No', 'NISN', 'NIS', 'Nama Siswa', 'Kelas', 'Jurusan', 'Tahun Ajaran'];
            foreach ($dynamicColumns as $col) {
                $header[] = $col['label'];
            }
            $xlsxRows[] = $header;

            // Data Rows
            $no = 1;
            foreach ($students as $s) {
                $sid = $s['id'];
                $cid = $studentClassMap[$sid][$semester]['class_id'] ?? null;

                $displayClass = $s['nama_kelas'];
                if ($semester >= 1 && $semester <= 4) {
                    $displayClass = $studentClassMap[$sid][$semester]['nama_kelas'] ?? $s['nama_kelas'];
                }

                $taValue = $studentClassMap[$sid][$semester]['tahun_ajaran'] ?? $selectedTaName;

                $row = [
                    $no++,
                    $s['nisn'],
                    $s['nis'],
                    $s['nama_lengkap'],
                    $displayClass,
                    $s['nama_jurusan'],
                    $taValue
                ];

                // Dynamic grade values
                foreach ($dynamicColumns as $col) {
                    if ($col['type'] === 'religion') {
                        $religionActiveInClass = false;
                        $bestReligionGrade = '';

                        foreach ($configMapels as $cfg) {
                            $mid = (int)$cfg['mapel_id'];
                            if (!isset($religionMapelIds[$mid])) continue;

                            if (!empty($classActiveMapelsMap[$cid][$semester][$mid])) {
                                $religionActiveInClass = true;
                                if (isset($studentGradesMap[$sid][$mid][$semester])) {
                                    $rGrade = $studentGradesMap[$sid][$mid][$semester]['nilai'];
                                    if ($bestReligionGrade === '' || $rGrade > $bestReligionGrade) {
                                        $bestReligionGrade = $rGrade;
                                    }
                                }
                            }
                        }

                        if ($religionActiveInClass) {
                            $row[] = $bestReligionGrade !== '' ? (float)$bestReligionGrade : '';
                        } else {
                            $row[] = '';
                        }
                    } else {
                        $mid = $col['mapel_id'];
                        if ($cid && !empty($classActiveMapelsMap[$cid][$semester][$mid])) {
                            if (isset($studentGradesMap[$sid][$mid][$semester])) {
                                $row[] = (float)$studentGradesMap[$sid][$mid][$semester]['nilai'];
                            } else {
                                $row[] = ''; 
                            }
                        } else {
                            $row[] = ''; 
                        }
                    }
                }
                $xlsxRows[] = $row;
            }

            // 6. Output XLSX Leger using SimpleXLSXGen
            $taClean = str_replace('/', '-', $selectedTaName);
            $filename = 'leger_nilai_pdss_ta_' . $taClean . '_semester_' . $semester . '_' . date('Ymd_His') . '.xlsx';
            \Shuchkin\SimpleXLSXGen::fromArray($xlsxRows)->downloadAs($filename);
            exit;
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiDownloadLeger] ' . $e->getMessage());
            echo "Terjadi kesalahan sistem saat mengekspor leger.";
        }
    }

    private function sanitize(mixed $val): string {
        if (is_null($val) || is_array($val) || is_object($val)) {
            return '';
        }
        $val = (string) $val;
        return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
    }

    private function generateUuidV4(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // =========================================================================
    // SIMULASI PEMILIHAN KAMPUS & PRODI
    // =========================================================================

    /**
     * API: Ambil setting buka/tutup/kunci tiap fase simulasi
     * GET /api/v1/pdss/simulasi/setting
     */
    public function apiGetSimulasiSetting(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['success' => false, 'data' => [], 'error' => 'Pilih sekolah terlebih dahulu.'], 200); return; }

        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
        try {
            $db = \App\Config\Database::getConnection();
            if (empty($tahunAjaranId)) {
                $tahunAjaranId = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $tahunAjaranId->execute([$tenantId]);
                $tahunAjaranId = $tahunAjaranId->fetchColumn();
            }

            $stmt = $db->prepare("SELECT no_simulasi, is_open, is_locked, dibuka_oleh, dibuka_at, dikunci_oleh, dikunci_at FROM pdss_simulasi_setting WHERE tenant_id = ? AND tahun_ajaran_id = ?");
            $stmt->execute([$tenantId, $tahunAjaranId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $settings = [1 => ['is_open' => 0, 'is_locked' => 0], 2 => ['is_open' => 0, 'is_locked' => 0], 3 => ['is_open' => 0, 'is_locked' => 0]];
            foreach ($rows as $r) {
                $settings[(int)$r['no_simulasi']] = $r;
            }
            $this->jsonResponse(['success' => true, 'data' => $settings, 'tahun_ajaran_id' => $tahunAjaranId]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetSimulasiSetting] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal memuat setting simulasi: ' . $e->getMessage()], 200);
        }
    }

    /**
     * API: Toggle buka/tutup/kunci fase simulasi
     * POST /api/v1/pdss/simulasi/setting
     */
    public function apiToggleSimulasiSetting(): void {
        if (!$this->canWrite()) { $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak.'], 200); return; }
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['success' => false, 'error' => 'Tenant tidak terdeteksi.'], 200); return; }

        $input = $this->getJsonInput();
        $noSimulasi  = (int)($input['no_simulasi'] ?? 0);
        $action      = $this->sanitize($input['action'] ?? ''); // 'open' | 'close' | 'lock'
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? '';

        if (!in_array($noSimulasi, [1,2,3]) || !in_array($action, ['open','close','lock'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Parameter tidak valid.'], 200); return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }

            // Sequential lock: simulasi 2 harus kunci sim 1 dulu, sim 3 harus kunci sim 2 dulu
            if ($action === 'open' && $noSimulasi > 1) {
                $stmtPrev = $db->prepare("SELECT is_locked FROM pdss_simulasi_setting WHERE tenant_id = ? AND tahun_ajaran_id = ? AND no_simulasi = ?");
                $stmtPrev->execute([$tenantId, $tahunAjaranId, $noSimulasi - 1]);
                $prevLocked = $stmtPrev->fetchColumn();
                if (!$prevLocked) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => "Harap cek simulasi sebelumnya (Simulasi " . ($noSimulasi-1) . ") dan lakukan kunci permanen sebelum melanjutkan simulasi berikutnya."
                    ], 200);
                    return;
                }
            }

            $userName = \App\Core\SessionManager::getUserNama() ?? 'Unknown';
            $now = date('Y-m-d H:i:s');

            if ($action === 'open') {
                $db->prepare("INSERT INTO pdss_simulasi_setting (tenant_id, tahun_ajaran_id, no_simulasi, is_open, is_locked, dibuka_oleh, dibuka_at) VALUES (?,?,?,1,0,?,?) ON DUPLICATE KEY UPDATE is_open=1, is_locked=0, dibuka_oleh=?, dibuka_at=?")
                   ->execute([$tenantId, $tahunAjaranId, $noSimulasi, $userName, $now, $userName, $now]);
                $this->jsonResponse(['success' => true, 'message' => "Simulasi $noSimulasi berhasil dibuka."]);
            } elseif ($action === 'close') {
                $db->prepare("INSERT INTO pdss_simulasi_setting (tenant_id, tahun_ajaran_id, no_simulasi, is_open, is_locked, ditutup_oleh, ditutup_at) VALUES (?,?,?,0,0,?,?) ON DUPLICATE KEY UPDATE is_open=0, ditutup_oleh=?, ditutup_at=?")
                   ->execute([$tenantId, $tahunAjaranId, $noSimulasi, $userName, $now, $userName, $now]);
                $this->jsonResponse(['success' => true, 'message' => "Simulasi $noSimulasi berhasil ditutup."]);
            } elseif ($action === 'lock') {
                $db->prepare("INSERT INTO pdss_simulasi_setting (tenant_id, tahun_ajaran_id, no_simulasi, is_open, is_locked, dikunci_oleh, dikunci_at) VALUES (?,?,?,0,1,?,?) ON DUPLICATE KEY UPDATE is_open=0, is_locked=1, dikunci_oleh=?, dikunci_at=?")
                   ->execute([$tenantId, $tahunAjaranId, $noSimulasi, $userName, $now, $userName, $now]);
                $this->jsonResponse(['success' => true, 'message' => "Simulasi $noSimulasi berhasil dikunci permanen."]);
            }
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiToggleSimulasiSetting] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengubah setting simulasi.'], 500);
        }
    }

    /**
     * API: Ambil daftar siswa eligible + pilihan simulasi + deteksi konflik
     * GET /api/v1/pdss/simulasi?tahun_ajaran_id=&no_simulasi=
     */
    public function apiGetSimulasi(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400); return; }

        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
        $noSimulasi    = (int)($_GET['no_simulasi'] ?? 1);
        if (!in_array($noSimulasi, [1,2,3])) $noSimulasi = 1;

        try {
            $db = \App\Config\Database::getConnection();

            // Resolve tahun ajaran
            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }
            $stmtTaName = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ? LIMIT 1");
            $stmtTaName->execute([$tahunAjaranId]);
            $selectedTaName = $stmtTaName->fetchColumn() ?: '';

            // --- LANGKAH 1: Ambil siswa eligible cohort (reuse logic dari apiGetKesiapan) ---
            // Siswa yang memiliki nilai sem 5 di tahun ini ATAU teoritis angkatan ini
            $stmtSiswa = $db->prepare("
                SELECT DISTINCT s.id, s.nama_lengkap, s.nisn, s.nis,
                       s.id_jurusan, k.nama_kelas, j.nama_jurusan, j.kode_jurusan
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN jurusan j ON s.id_jurusan = j.id
                LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id
                WHERE s.tenant_id = ?
                  AND s.status = 'Aktif'
                  AND s.deleted_at IS NULL
                  AND (
                      s.id IN (
                          SELECT DISTINCT dnr.siswa_id 
                          FROM detail_nilai_rapor dnr
                          JOIN kelas k_dnr ON dnr.kelas_id = k_dnr.id
                          WHERE dnr.tenant_id = ? 
                            AND dnr.semester = 'Ganjil' 
                            AND (k_dnr.nama_kelas LIKE '%12%' OR k_dnr.nama_kelas LIKE '%XII%')
                            AND dnr.tahun_ajaran = ? 
                            AND dnr.deleted_at IS NULL
                      )
                      OR (
                          s.id NOT IN (
                              SELECT DISTINCT dnr2.siswa_id 
                              FROM detail_nilai_rapor dnr2
                              JOIN kelas k_dnr ON dnr2.kelas_id = k_dnr.id
                              WHERE dnr2.tenant_id = ? 
                                AND dnr2.semester = 'Ganjil' 
                                AND (k_dnr.nama_kelas LIKE '%12%' OR k_dnr.nama_kelas LIKE '%XII%')
                                AND dnr2.deleted_at IS NULL
                          )
                          AND (k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%')
                          AND (
                              CONCAT(CAST(SUBSTRING(ta.tahun_ajaran,1,4) AS UNSIGNED)+2,'/',CAST(SUBSTRING(ta.tahun_ajaran,1,4) AS UNSIGNED)+3) = ?
                              OR ta.tahun_ajaran = ?
                          )
                      )
                  )
                ORDER BY j.nama_jurusan ASC, s.nama_lengkap ASC
            ");
            $stmtSiswa->execute([$tenantId, $tenantId, $selectedTaName, $tenantId, $selectedTaName, $selectedTaName]);
            $siswaList = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

            // --- LANGKAH 2: Ambil nilai rata-rata per siswa ---
            $siswaIds = array_column($siswaList, 'id');
            $nilaiMap = [];

            if (!empty($siswaIds)) {
                $siswaPlaceholders = implode(',', array_fill(0, count($siswaIds), '?'));
                $stmtNilaiAll = $db->prepare("
                    SELECT dnr.siswa_id, dnr.nilai_akhir, dnr.semester, k_dnr.nama_kelas
                    FROM detail_nilai_rapor dnr
                    JOIN kelas k_dnr ON dnr.kelas_id = k_dnr.id
                    WHERE dnr.tenant_id = ? AND dnr.deleted_at IS NULL
                      AND dnr.siswa_id IN ($siswaPlaceholders)
                ");
                $stmtNilaiAll->execute(array_merge([$tenantId], $siswaIds));
                $rawGrades = $stmtNilaiAll->fetchAll(PDO::FETCH_ASSOC);

                $studentGradesAccumulator = [];
                foreach ($rawGrades as $rg) {
                    $semLevel = $this->getSemesterLevel($rg['nama_kelas'], $rg['semester']);
                    if ($semLevel !== null && $semLevel >= 1 && $semLevel <= 5) {
                        $sid = $rg['siswa_id'];
                        if (!isset($studentGradesAccumulator[$sid])) {
                            $studentGradesAccumulator[$sid] = ['sum' => 0.0, 'count' => 0];
                        }
                        $studentGradesAccumulator[$sid]['sum'] += (float)$rg['nilai_akhir'];
                        $studentGradesAccumulator[$sid]['count']++;
                    }
                }

                foreach ($studentGradesAccumulator as $sid => $data) {
                    if ($data['count'] > 0) {
                        $nilaiMap[$sid] = $data['sum'] / $data['count'];
                    }
                }
            }

            // --- LANGKAH 3: Beri peringkat per jurusan ---
            $jurusanGroups = [];
            foreach ($siswaList as $s) {
                $jid = $s['id_jurusan'] ?? 'none';
                $jurusanGroups[$jid][] = $s;
            }
            $rankedStudents = [];
            foreach ($jurusanGroups as $jid => $group) {
                usort($group, function($a, $b) use ($nilaiMap) {
                    $ra = $nilaiMap[$a['id']] ?? 0;
                    $rb = $nilaiMap[$b['id']] ?? 0;
                    return $ra == $rb ? strcmp($a['nama_lengkap'], $b['nama_lengkap']) : ($ra > $rb ? -1 : 1);
                });
                $rank = 1;
                foreach ($group as $s) {
                    $s['rank_eligible']  = $rank++;
                    $s['rata_rata']      = round($nilaiMap[$s['id']] ?? 0, 2);
                    $rankedStudents[]    = $s;
                }
            }

            // --- LANGKAH 4: Ambil pilihan simulasi ---
            $stmtSim = $db->prepare("
                SELECT ps.*, mk1.nama_kampus AS kampus_nama_1, mp1.program_studi AS prodi_nama_1,
                       mk2.nama_kampus AS kampus_nama_2, mp2.program_studi AS prodi_nama_2
                FROM pdss_simulasi ps
                LEFT JOIN master_kampus mk1 ON ps.kampus_id_1 = mk1.id
                LEFT JOIN master_kampus_prodi mp1 ON ps.prodi_id_1 = mp1.id
                LEFT JOIN master_kampus mk2 ON ps.kampus_id_2 = mk2.id
                LEFT JOIN master_kampus_prodi mp2 ON ps.prodi_id_2 = mp2.id
                WHERE ps.tenant_id = ? AND ps.tahun_ajaran_id = ? AND ps.no_simulasi = ?
                  AND ps.deleted_at IS NULL
            ");
            $stmtSim->execute([$tenantId, $tahunAjaranId, $noSimulasi]);
            $simMap = [];
            foreach ($stmtSim->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $simMap[$row['siswa_id']] = $row;
            }

            // --- LANGKAH 5: Bangun peta konflik ---
            // $prodiMap["{kampus_id}_{prodi_id}"] = [ [siswa_id, nama, kelas, rank, jurusan], ... ] sorted by rank ASC
            $prodiMap = [];
            foreach ($rankedStudents as $s) {
                $sid = $s['id'];
                if (!isset($simMap[$sid])) continue;
                $sim = $simMap[$sid];

                foreach (['1','2'] as $slot) {
                    $kid = $sim["kampus_id_$slot"] ?? null;
                    $pid = $sim["prodi_id_$slot"] ?? null;
                    if (!$kid || !$pid) continue;
                    $key = "{$kid}_{$pid}";
                    $prodiMap[$key][] = [
                        'siswa_id' => $sid,
                        'nama'     => $s['nama_lengkap'],
                        'kelas'    => $s['nama_kelas'] ?? '-',
                        'rank'     => $s['rank_eligible'],
                        'jurusan'  => $s['nama_jurusan'] ?? '-',
                        'slot'     => $slot,
                    ];
                }
            }
            // Sort setiap prodi group by rank ASC
            foreach ($prodiMap as &$group) {
                usort($group, fn($a, $b) => $a['rank'] - $b['rank']);
            }
            unset($group);

            // --- LANGKAH 6: Tandai konflik di output ---
            // Buat index cepat: siswa_id+slot -> key
            $siswaProdiIndex = []; // siswa_id => ['slot' => key]
            foreach ($prodiMap as $key => $group) {
                foreach ($group as $idx => $entry) {
                    if ($idx > 0) {
                        // Ini pemilih konflik (bukan rank tertinggi)
                        $siswaProdiIndex[$entry['siswa_id']][$entry['slot']] = [
                            'key'         => $key,
                            'pemilik'     => $group[0], // rank tertinggi
                        ];
                    }
                }
            }

            // --- LANGKAH 6b: Ambil manual eligible override ---
            $stmtManual = $db->prepare("SELECT siswa_id, status_eligible FROM pdss_manual_eligible WHERE tenant_id = ?");
            $stmtManual->execute([$tenantId]);
            $manualEligibleMap = [];
            foreach ($stmtManual->fetchAll(PDO::FETCH_ASSOC) as $me) {
                $manualEligibleMap[$me['siswa_id']] = $me['status_eligible'];
            }

            // Ambil config kuota eligible per jurusan dari apiGetKesiapan
            $stmtQuota = $db->prepare("
                SELECT COUNT(DISTINCT s2.id) * 0.4 AS quota, s2.id_jurusan
                FROM siswa s2
                JOIN kelas k2 ON s2.id_kelas = k2.id
                LEFT JOIN tahun_ajaran ta2 ON s2.id_tahun_ajaran = ta2.id
                WHERE s2.tenant_id = ? AND s2.status = 'Aktif' AND s2.deleted_at IS NULL
                  AND (k2.nama_kelas LIKE '%12%' OR k2.nama_kelas LIKE '%XII%')
                  AND (
                      s2.id IN (
                          SELECT DISTINCT dnr.siswa_id 
                          FROM detail_nilai_rapor dnr 
                          JOIN kelas k_dnr ON dnr.kelas_id = k_dnr.id
                          WHERE dnr.tenant_id = ? 
                            AND dnr.semester = 'Ganjil' 
                            AND (k_dnr.nama_kelas LIKE '%12%' OR k_dnr.nama_kelas LIKE '%XII%')
                            AND dnr.tahun_ajaran = ?
                      )
                      OR (
                          s2.id NOT IN (
                              SELECT DISTINCT dnr.siswa_id 
                              FROM detail_nilai_rapor dnr 
                              JOIN kelas k_dnr ON dnr.kelas_id = k_dnr.id
                              WHERE dnr.tenant_id = ? 
                                AND dnr.semester = 'Ganjil'
                                AND (k_dnr.nama_kelas LIKE '%12%' OR k_dnr.nama_kelas LIKE '%XII%')
                          )
                          AND (k2.nama_kelas LIKE '%12%' OR k2.nama_kelas LIKE '%XII%')
                          AND (
                              CONCAT(
                                  CAST(SUBSTRING(ta2.tahun_ajaran, 1, 4) AS UNSIGNED) + 2,
                                  '/',
                                  CAST(SUBSTRING(ta2.tahun_ajaran, 1, 4) AS UNSIGNED) + 3
                              ) = ?
                              OR ta2.tahun_ajaran = ?
                          )
                      )
                  )
                GROUP BY s2.id_jurusan
            ");
            $stmtQuota->execute([
                $tenantId, 
                $tenantId, 
                $selectedTaName, 
                $tenantId, 
                $selectedTaName, 
                $selectedTaName
            ]);
            $quotaMap = []; // id_jurusan => quota
            foreach ($stmtQuota->fetchAll(PDO::FETCH_ASSOC) as $q) {
                $quotaMap[$q['id_jurusan']] = (int)ceil($q['quota']);
            }

            // --- LANGKAH 7: Susun output akhir ---
            $tempOutput = [];
            foreach ($rankedStudents as $s) {
                $sid = $s['id'];
                $sim = $simMap[$sid] ?? null;
                $jid = $s['id_jurusan'] ?? 'none';

                // Hitung is_eligible dengan mempertimbangkan manual override
                $manualStatus = $manualEligibleMap[$sid] ?? 'auto';
                if ($manualStatus === 'eligible') {
                    $isEligible = true;
                } elseif ($manualStatus === 'tidak_eligible') {
                    $isEligible = false;
                } else {
                    // Auto: 40% teratas per jurusan
                    $limit = $quotaMap[$jid] ?? 999;
                    $isEligible = ($s['rank_eligible'] <= $limit) && ($s['rata_rata'] > 0);
                }

                // Saring: Hanya siswa lolos eligible yang dimasukkan ke simulasi
                if (!$isEligible) {
                    continue;
                }

                $row = [
                    'siswa_id'      => $sid,
                    'nama_lengkap'  => $s['nama_lengkap'],
                    'nisn'          => $s['nisn'],
                    'nis'           => $s['nis'],
                    'nama_kelas'    => $s['nama_kelas'] ?? '-',
                    'nama_jurusan'  => $s['nama_jurusan'] ?? '-',
                    'kode_jurusan'  => $s['kode_jurusan'] ?? '-',
                    'rank_eligible' => $s['rank_eligible'], // sementara
                    'rata_rata'     => $s['rata_rata'],
                    'is_eligible'   => true,
                    'status_eligible' => $manualStatus,
                    'sudah_isi'     => !is_null($sim),

                    // Pilihan 1
                    'kampus_id_1'   => $sim['kampus_id_1'] ?? null,
                    'prodi_id_1'    => $sim['prodi_id_1'] ?? null,
                    'kampus_nama_1' => $sim['kampus_nama_1'] ?? $sim['nama_kampus_1'] ?? null,
                    'prodi_nama_1'  => $sim['prodi_nama_1'] ?? $sim['nama_prodi_1'] ?? null,

                    // Pilihan 2
                    'kampus_id_2'   => $sim['kampus_id_2'] ?? null,
                    'prodi_id_2'    => $sim['prodi_id_2'] ?? null,
                    'kampus_nama_2' => $sim['kampus_nama_2'] ?? $sim['nama_kampus_2'] ?? null,
                    'prodi_nama_2'  => $sim['prodi_nama_2'] ?? $sim['nama_prodi_2'] ?? null,

                    // Bukti (simulasi 3)
                    'bukti_file'    => $sim['bukti_file'] ?? null,
                    'bukti_filename'=> $sim['bukti_filename'] ?? null,
                    'status'        => $sim['status'] ?? null,
                    'catatan_bk'    => $sim['catatan_bk'] ?? null,

                    // Konflik
                    'is_konflik_1'  => false,
                    'konflik_info_1'=> null,
                    'is_konflik_2'  => false,
                    'konflik_info_2'=> null,
                ];

                // Terapkan konflik
                foreach (['1','2'] as $slot) {
                    if (isset($siswaProdiIndex[$sid][$slot])) {
                        $pemilik = $siswaProdiIndex[$sid][$slot]['pemilik'];
                        $row["is_konflik_$slot"]   = true;
                        $row["konflik_info_$slot"]  = [
                            'nama'  => $pemilik['nama'],
                            'rank'  => $pemilik['rank'],
                            'kelas' => $pemilik['kelas'],
                        ];
                    }
                }

                $tempOutput[] = $row;
            }

            // Urutkan secara global berdasarkan rata_rata tertinggi ke terendah
            usort($tempOutput, function($a, $b) {
                if ($a['rata_rata'] == $b['rata_rata']) {
                    return strcmp($a['nama_lengkap'], $b['nama_lengkap']);
                }
                return $a['rata_rata'] > $b['rata_rata'] ? -1 : 1;
            });

            // Beri nomor urut rank_eligible baru secara global
            $rank = 1;
            foreach ($tempOutput as &$row) {
                $row['rank_eligible'] = $rank++;
            }
            unset($row);

            $output = $tempOutput;

            // Stats
            $totalEligible = count($output);
            $sudahIsi      = count(array_filter($output, fn($r) => $r['sudah_isi']));
            $belumIsi      = $totalEligible - $sudahIsi;
            $totalKonflik  = count(array_filter($output, fn($r) => $r['is_konflik_1'] || $r['is_konflik_2']));

            $this->jsonResponse([
                'success'       => true,
                'data'          => $output,
                'stats'         => [
                    'total_eligible' => $totalEligible,
                    'sudah_isi'      => $sudahIsi,
                    'belum_isi'      => $belumIsi,
                    'total_konflik'  => $totalKonflik,
                ],
                'tahun_ajaran_id' => $tahunAjaranId,
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data simulasi.'], 500);
        }
    }

    /**
     * API: Simpan / update pilihan simulasi seorang siswa
     * POST /api/v1/pdss/simulasi
     */
    public function apiSaveSimulasi(): void {
        if (!$this->canWrite()) { $this->jsonResponse(['error' => 'Akses ditolak.'], 403); return; }
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400); return; }

        $input = $this->getJsonInput();
        $siswaId       = $this->sanitize($input['siswa_id'] ?? '');
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? '';
        $noSimulasi    = (int)($input['no_simulasi'] ?? 1);
        $kampusId1     = $this->sanitize($input['kampus_id_1'] ?? '');
        $prodiId1      = $this->sanitize($input['prodi_id_1'] ?? '');
        $kampusId2     = $this->sanitize($input['kampus_id_2'] ?? '');
        $prodiId2      = $this->sanitize($input['prodi_id_2'] ?? '');
        $catatanSiswa  = $this->sanitize($input['catatan_siswa'] ?? '');
        $diisiOleh     = 'Guru BK';

        if (empty($siswaId) || !in_array($noSimulasi,[1,2,3])) {
            error_log("[DEBUG apiSaveSimulasi 422] Data tidak lengkap: siswaId='$siswaId', noSimulasi='$noSimulasi'. Input: " . json_encode($input));
            $this->jsonResponse(['error' => 'Data tidak lengkap.'], 422); return;
        }
        if (empty($kampusId1) || empty($prodiId1)) {
            error_log("[DEBUG apiSaveSimulasi 422] Pilihan 1 kosong: kampusId1='$kampusId1', prodiId1='$prodiId1'. Input: " . json_encode($input));
            $this->jsonResponse(['error' => 'Pilihan 1 (kampus dan prodi) wajib diisi.'], 422); return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }

            // Tentukan role pemanggil
            $currentRoles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
            $isAdminOrBK  = !empty(array_intersect($currentRoles, ['super_admin', 'operator_sekolah', 'guru_bk']));

            // Cek setting simulasi
            $stmtSetting = $db->prepare("SELECT is_open, is_locked FROM pdss_simulasi_setting WHERE tenant_id = ? AND tahun_ajaran_id = ? AND no_simulasi = ?");
            $stmtSetting->execute([$tenantId, $tahunAjaranId, $noSimulasi]);
            $setting = $stmtSetting->fetch(PDO::FETCH_ASSOC);

            // Jika simulasi sudah dikunci — siapapun tidak boleh ubah
            if ($setting && (int)$setting['is_locked'] === 1) {
                $this->jsonResponse(['error' => "Simulasi $noSimulasi sudah dikunci. Pilihan tidak dapat diubah."], 400); return;
            }

            // Siswa hanya bisa mengisi jika simulasi sudah dibuka oleh BK
            // Admin / Guru BK bisa mengisi kapan saja (mereka yang membuka/mengatur)
            if (!$isAdminOrBK && (!$setting || (int)$setting['is_open'] === 0)) {
                $this->jsonResponse(['error' => "Simulasi $noSimulasi belum dibuka oleh BK."], 400); return;
            }

            // Set label diisi_oleh berdasarkan role
            $userRoleName = $_SESSION['role_name'] ?? '';
            if (in_array($userRoleName, ['super_admin', 'operator_sekolah'], true)) {
                $diisiOleh = 'Admin';
            } elseif ($userRoleName === 'guru_bk') {
                $diisiOleh = 'Guru BK';
            } else {
                $diisiOleh = 'Siswa';
            }

            // Ambil snapshot nama kampus & prodi
            $namKampus1 = $namProdi1 = $namKampus2 = $namProdi2 = null;
            $stmtK = $db->prepare("SELECT nama_kampus FROM master_kampus WHERE id = ? LIMIT 1");
            $stmtK->execute([$kampusId1]); $namKampus1 = $stmtK->fetchColumn() ?: null;
            $stmtP = $db->prepare("SELECT program_studi FROM master_kampus_prodi WHERE id = ? LIMIT 1");
            $stmtP->execute([$prodiId1]); $namProdi1 = $stmtP->fetchColumn() ?: null;
            if ($kampusId2 && $prodiId2) {
                $stmtK2 = $db->prepare("SELECT nama_kampus FROM master_kampus WHERE id = ? LIMIT 1");
                $stmtK2->execute([$kampusId2]); $namKampus2 = $stmtK2->fetchColumn() ?: null;
                $stmtP2 = $db->prepare("SELECT program_studi FROM master_kampus_prodi WHERE id = ? LIMIT 1");
                $stmtP2->execute([$prodiId2]); $namProdi2 = $stmtP2->fetchColumn() ?: null;
            }

            // --- Conflict check real-time untuk Pilihan 1 ---
            $conflictWarning = null;
            $stmtConflict = $db->prepare("
                SELECT ps.siswa_id, s.nama_lengkap, k.nama_kelas
                FROM pdss_simulasi ps
                JOIN siswa s ON ps.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                WHERE ps.tenant_id = ? AND ps.tahun_ajaran_id = ? AND ps.no_simulasi = ?
                  AND ps.kampus_id_1 = ? AND ps.prodi_id_1 = ?
                  AND ps.siswa_id != ?
                  AND ps.deleted_at IS NULL
                LIMIT 1
            ");
            $stmtConflict->execute([$tenantId, $tahunAjaranId, $noSimulasi, $kampusId1, $prodiId1, $siswaId]);
            $existingPicker = $stmtConflict->fetch(PDO::FETCH_ASSOC);
            if ($existingPicker) {
                $conflictWarning = "Perhatian: Kampus & Prodi Pilihan 1 ini sudah dipilih oleh {$existingPicker['nama_lengkap']} (Kelas: {$existingPicker['nama_kelas']}). Pilihan Anda tetap tersimpan, namun BK akan mengetahui adanya konflik ini.";
            }

            // UPSERT
            $db->prepare("
                INSERT INTO pdss_simulasi
                (tenant_id, siswa_id, tahun_ajaran_id, no_simulasi, kampus_id_1, prodi_id_1, nama_kampus_1, nama_prodi_1, kampus_id_2, prodi_id_2, nama_kampus_2, nama_prodi_2, catatan_siswa, diisi_oleh, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'submitted')
                ON DUPLICATE KEY UPDATE
                    kampus_id_1=VALUES(kampus_id_1), prodi_id_1=VALUES(prodi_id_1),
                    nama_kampus_1=VALUES(nama_kampus_1), nama_prodi_1=VALUES(nama_prodi_1),
                    kampus_id_2=VALUES(kampus_id_2), prodi_id_2=VALUES(prodi_id_2),
                    nama_kampus_2=VALUES(nama_kampus_2), nama_prodi_2=VALUES(nama_prodi_2),
                    catatan_siswa=VALUES(catatan_siswa), diisi_oleh=VALUES(diisi_oleh),
                    status='submitted', updated_at=NOW()
            ")->execute([
                $tenantId, $siswaId, $tahunAjaranId, $noSimulasi,
                $kampusId1, $prodiId1, $namKampus1, $namProdi1,
                $kampusId2 ?: null, $prodiId2 ?: null, $namKampus2, $namProdi2,
                $catatanSiswa ?: null, $diisiOleh
            ]);

            $response = ['success' => true, 'message' => 'Pilihan simulasi berhasil disimpan.'];
            if ($conflictWarning) {
                $response['warning']          = true;
                $response['conflict_message'] = $conflictWarning;
            }
            $this->jsonResponse($response);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiSaveSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan pilihan simulasi.'], 500);
        }
    }

    /**
     * API: Hapus pilihan simulasi seorang siswa
     * POST /api/v1/pdss/simulasi/delete
     */
    public function apiDeleteSimulasi(): void {
        if (!$this->canWrite()) { $this->jsonResponse(['error' => 'Akses ditolak.'], 403); return; }
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400); return; }

        $input         = $this->getJsonInput();
        $siswaId       = $this->sanitize($input['siswa_id'] ?? '');
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? '';
        $noSimulasi    = (int)($input['no_simulasi'] ?? 0);

        if (empty($siswaId) || !in_array($noSimulasi,[1,2,3])) {
            $this->jsonResponse(['error' => 'Parameter tidak valid.'], 422); return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $db->prepare("UPDATE pdss_simulasi SET deleted_at = NOW() WHERE tenant_id = ? AND siswa_id = ? AND tahun_ajaran_id = ? AND no_simulasi = ?")
               ->execute([$tenantId, $siswaId, $tahunAjaranId, $noSimulasi]);
            $this->jsonResponse(['success' => true, 'message' => 'Pilihan simulasi berhasil dihapus.']);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiDeleteSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus pilihan simulasi.'], 500);
        }
    }

    /**
     * API: Upload bukti pemilihan (khusus Simulasi 3)
     * POST /api/v1/pdss/simulasi/upload-bukti (multipart/form-data)
     */
    public function apiUploadBuktiSimulasi(): void {
        if (!$this->canWrite()) { $this->jsonResponse(['error' => 'Akses ditolak.'], 403); return; }
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400); return; }

        $siswaId       = $this->sanitize($_POST['siswa_id'] ?? '');
        $tahunAjaranId = $_POST['tahun_ajaran_id'] ?? '';
        $noSimulasi    = (int)($_POST['no_simulasi'] ?? 3);

        if (empty($siswaId)) { $this->jsonResponse(['error' => 'siswa_id wajib diisi.'], 422); return; }
        if ($noSimulasi !== 3) { $this->jsonResponse(['error' => 'Upload bukti hanya untuk Simulasi 3.'], 422); return; }
        if (!isset($_FILES['bukti_file']) || $_FILES['bukti_file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'File tidak terdeteksi atau terjadi error upload.'], 422); return;
        }

        $file     = $_FILES['bukti_file'];
        $allowed  = ['application/pdf','image/jpeg','image/jpg','image/png'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $maxSize  = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed) || !in_array($ext, ['pdf','jpg','jpeg','png'])) {
            $this->jsonResponse(['error' => 'Format file tidak didukung. Gunakan PDF, JPG, atau PNG.'], 422); return;
        }
        if ($file['size'] > $maxSize) {
            $this->jsonResponse(['error' => 'Ukuran file melebihi 2MB.'], 422); return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }

            $uploadDir = __DIR__ . "/../../storage/uploads/pdss/simulasi/{$tenantId}/{$tahunAjaranId}/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $newFilename = "sim3_{$siswaId}_" . time() . ".$ext";
            $destPath    = $uploadDir . $newFilename;
            $relativePath = "storage/uploads/pdss/simulasi/{$tenantId}/{$tahunAjaranId}/{$newFilename}";

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                $this->jsonResponse(['error' => 'Gagal memindahkan file.'], 500); return;
            }

            $db->prepare("
                UPDATE pdss_simulasi
                SET bukti_file = ?, bukti_filename = ?, bukti_uploaded_at = NOW(), updated_at = NOW()
                WHERE tenant_id = ? AND siswa_id = ? AND tahun_ajaran_id = ? AND no_simulasi = 3 AND deleted_at IS NULL
            ")->execute([$relativePath, $file['name'], $tenantId, $siswaId, $tahunAjaranId]);

            $this->jsonResponse(['success' => true, 'message' => 'Bukti berhasil diupload.', 'file_path' => $relativePath, 'filename' => $file['name']]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiUploadBuktiSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengupload bukti.'], 500);
        }
    }

    /**
     * API: Export Excel hasil simulasi
     * GET /api/v1/pdss/simulasi/export?tahun_ajaran_id=&no_simulasi=
     */
    public function apiExportSimulasi(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400); return; }

        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
        $noSimulasi    = (int)($_GET['no_simulasi'] ?? 1);

        try {
            $db = \App\Config\Database::getConnection();
            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }
            $stmtTaName = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ? LIMIT 1");
            $stmtTaName->execute([$tahunAjaranId]);
            $taNama = $stmtTaName->fetchColumn() ?: 'unknown';

            // Panggil apiGetSimulasi secara internal (ambil data via method yang sama)
            // Tapi untuk export kita langsung query sederhana
            $stmtData = $db->prepare("
                SELECT s.nama_lengkap, k.nama_kelas, j.nama_jurusan,
                       ps.kampus_id_1, COALESCE(mk1.nama_kampus, ps.nama_kampus_1) AS kampus_1,
                       COALESCE(mp1.program_studi, ps.nama_prodi_1) AS prodi_1,
                       COALESCE(mk2.nama_kampus, ps.nama_kampus_2) AS kampus_2,
                       COALESCE(mp2.program_studi, ps.nama_prodi_2) AS prodi_2,
                       ps.bukti_filename, ps.status
                FROM pdss_simulasi ps
                JOIN siswa s ON ps.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN jurusan j ON s.id_jurusan = j.id
                LEFT JOIN master_kampus mk1 ON ps.kampus_id_1 = mk1.id
                LEFT JOIN master_kampus_prodi mp1 ON ps.prodi_id_1 = mp1.id
                LEFT JOIN master_kampus mk2 ON ps.kampus_id_2 = mk2.id
                LEFT JOIN master_kampus_prodi mp2 ON ps.prodi_id_2 = mp2.id
                WHERE ps.tenant_id = ? AND ps.tahun_ajaran_id = ? AND ps.no_simulasi = ? AND ps.deleted_at IS NULL
                ORDER BY j.nama_jurusan, s.nama_lengkap
            ");
            $stmtData->execute([$tenantId, $tahunAjaranId, $noSimulasi]);
            $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

            $safeTA = str_replace('/', '-', $taNama);
            $filename = "simulasi_{$noSimulasi}_ta_{$safeTA}.xlsx";

            $excelData = [
                ['No','Nama Siswa','Kelas','Jurusan','Kampus Pilihan 1','Prodi Pilihan 1','Kampus Pilihan 2','Prodi Pilihan 2','Bukti Upload','Status']
            ];
            $no = 1;
            foreach ($rows as $r) {
                $excelData[] = [
                    (string)$no++,
                    (string)$r['nama_lengkap'],
                    (string)($r['nama_kelas'] ?? '-'),
                    (string)($r['nama_jurusan'] ?? '-'),
                    (string)($r['kampus_1'] ?? '-'),
                    (string)($r['prodi_1'] ?? '-'),
                    (string)($r['kampus_2'] ?? '-'),
                    (string)($r['prodi_2'] ?? '-'),
                    (string)($r['bukti_filename'] ?? '-'),
                    (string)($r['status'] ?? '-')
                ];
            }

            \Shuchkin\SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
            exit;
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiExportSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengekspor data simulasi.'], 500);
        }
    }
}
