<?php

namespace App\Modules\Pdss\Controllers;

use App\Core\BaseController;
use App\Core\FileStorage;
use App\Core\SessionManager;
use PDO;

/**
 * PdssDetailModuleController
 *
 * Menangani fitur PDSS & Pelacakan Karir Alumni (Multi-Schema PostgreSQL Native):
 * - Langkah 1: Penentuan Mata Pelajaran PDSS per Kelas / Jurusan & Semester (1-5/6)
 * - Langkah 2: Evaluasi Nilai Rapor & Kuota Eligibilitas Siswa Kelas 12
 * - Langkah 3: Ranking Paralel, Status SNBP, & Audit Detail Transkrip Siswa
 * - Tracking Karir & Rekam Jejak Kuliah Alumni
 * - Konfigurasi Master Kampus & Prodi
 * - Simulasi Pemilihan Kampus & Deteksi Konflik Pilihan
 *
 * SECURITY & ARCHITECTURE:
 * - Multi-tenant isolation: semua query terkunci via parameterized tenant_id.
 * - Anti-SQLi: Prepared statements PDO.
 * - PostgreSQL multi-schema: `pdss.*`, `akademik.*`, `siswa.*`, `tracer.*`, `core.*`.
 */
class PdssDetailModuleController extends BaseController {

    private const WRITE_ALLOWED_ROLES = ['super_admin', 'operator_sekolah', 'guru_bk'];

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    /**
     * Memeriksa hak akses tulis (CRUD)
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
        if (in_array('super_admin', $roles, true) || in_array('superadmin', $roles, true)) {
            try {
                $db = \App\Config\Database::getConnection();
                $tenantList = $db->query("SELECT id, nama_sekolah FROM core.tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC")
                                 ->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {}
        }
        
        $this->render('pdss/pdss_index', [
            'title' => 'PDSS & Tracking Alumni',
            'can_write' => $this->canWrite(),
            'user_role' => $userRole,
            'roles' => $roles,
            'tenant_id' => $tenantId,
            'tenant_list' => $tenantList
        ]);
    }

    /**
     * Helper: Mendapatkan Tenant ID yang aman
     */
    private function getSecureTenantId(): ?string {
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $tenantId = SessionManager::getTenantId();

        if (in_array('super_admin', $roles, true) || in_array('superadmin', $roles, true)) {
            $tid = $_GET['tenant_id'] ?? $_POST['tenant_id'] ?? null;
            if (empty($tid)) {
                $body = $this->getJsonInput();
                $tid = $body['tenant_id'] ?? null;
            }

            if (!empty($tid) && $tid !== '00000000-0000-0000-0000-000000000000') {
                try {
                    $db = \App\Config\Database::getConnection();
                    $stmt = $db->prepare("SELECT id FROM core.tenants WHERE id = ? LIMIT 1");
                    $stmt->execute([$tid]);
                    $valid = $stmt->fetchColumn();
                    if ($valid) return $valid;
                } catch (\Throwable $e) {}
            }

            try {
                $db = \App\Config\Database::getConnection();
                $stmtDefault = $db->query("SELECT id FROM core.tenants WHERE id != '00000000-0000-0000-0000-000000000000' AND status = 'active' ORDER BY nama_sekolah ASC LIMIT 1");
                $firstId = $stmtDefault->fetchColumn();
                if ($firstId) return $firstId;
            } catch (\Throwable $e) {}
        }

        if (empty($tenantId) || $tenantId === '00000000-0000-0000-0000-000000000000') {
            try {
                $db = \App\Config\Database::getConnection();
                $stmtDefault = $db->query("SELECT id FROM core.tenants WHERE id != '00000000-0000-0000-0000-000000000000' AND status = 'active' ORDER BY nama_sekolah ASC LIMIT 1");
                $tenantId = $stmtDefault->fetchColumn() ?: null;
            } catch (\Throwable $e) {}
        }

        return $tenantId;
    }

    /**
     * Helper: Mendapatkan seluruh siswa aktif kelas 12 dari anggota_kelas, riwayat kenaikan, dan kelas saat ini
     */
    private function getKelas12Students(string $tenantId, ?string $selectedTaName = null): array {
        $db = \App\Config\Database::getConnection();

        // Jika selectedTaName diisi secara spesifik (misal 2026/2027 atau 2024/2025)
        if ($selectedTaName) {
            $sql = "
                SELECT DISTINCT ON (s.id)
                    s.id, 
                    s.id AS siswa_id,
                    s.nama_lengkap, 
                    s.nisn, 
                    s.nis, 
                    COALESCE(j_by_k.nama_jurusan, j.nama_jurusan, s.jurusan, 'Umum') AS nama_jurusan,
                    COALESCE(k_ak.nama_kelas, k_rkk.nama_kelas, rkk.dari_kelas, k_curr.nama_kelas, 'XII') AS nama_kelas,
                    s.jurusan AS kode_jurusan,
                    COALESCE(k_ak.id_jurusan, k_rkk.id_jurusan, k_curr.id_jurusan, j.id::varchar, s.jurusan) AS id_jurusan
                FROM siswa.siswa s
                LEFT JOIN siswa.anggota_kelas ak ON s.id = ak.siswa_id AND ak.tenant_id = :tenant_id AND ak.tahun_ajaran = :ta_name
                LEFT JOIN akademik.kelas k_ak ON (ak.kelas_id = k_ak.id OR ak.kelas_id::varchar = k_ak.nama_kelas) AND k_ak.tenant_id = :tenant_id
                LEFT JOIN siswa.riwayat_kenaikan_kelas rkk ON s.id = rkk.siswa_id AND rkk.tenant_id = :tenant_id AND rkk.tahun_ajaran = :ta_name
                LEFT JOIN akademik.kelas k_rkk ON (rkk.dari_kelas = k_rkk.nama_kelas OR rkk.ke_kelas = k_rkk.nama_kelas OR rkk.dari_kelas = k_rkk.id::varchar) AND k_rkk.tenant_id = :tenant_id
                LEFT JOIN akademik.kelas k_curr ON (s.kelas_saat_ini = k_curr.nama_kelas OR s.kelas_saat_ini = k_curr.id::varchar) AND k_curr.tenant_id = :tenant_id
                LEFT JOIN akademik.jurusan j ON (s.jurusan = j.nama_jurusan OR s.jurusan = j.id::varchar) AND j.tenant_id = :tenant_id
                LEFT JOIN akademik.jurusan j_by_k ON (COALESCE(k_ak.id_jurusan, k_rkk.id_jurusan, k_curr.id_jurusan) = j_by_k.id::varchar) AND j_by_k.tenant_id = :tenant_id
                WHERE s.tenant_id = :tenant_id
                  AND s.status_siswa = 'Aktif'
                  AND (
                      (ak.tahun_ajaran = :ta_name AND (k_ak.nama_kelas ILIKE '%12%' OR k_ak.nama_kelas ILIKE '%XII%'))
                      OR (rkk.tahun_ajaran = :ta_name AND (k_rkk.nama_kelas ILIKE '%12%' OR k_rkk.nama_kelas ILIKE '%XII%' OR rkk.dari_kelas ILIKE '%12%' OR rkk.dari_kelas ILIKE '%XII%' OR rkk.ke_kelas ILIKE '%12%' OR rkk.ke_kelas ILIKE '%XII%'))
                  )
                ORDER BY s.id, s.nama_lengkap ASC
            ";
            $params = [':tenant_id' => $tenantId, ':ta_name' => $selectedTaName];
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $dedup = [];
            foreach ($rows as $r) {
                $dedup[(string)$r['id']] = $r;
            }
            return array_values($dedup);
        }

        // Fallback default jika tidak ada filter TA
        $sql = "
            SELECT DISTINCT ON (s.id)
                s.id, s.id as siswa_id, s.nama_lengkap, s.nisn, s.nis,
                COALESCE(j.nama_jurusan, 'Umum') AS nama_jurusan,
                COALESCE(k.nama_kelas, s.kelas_saat_ini, 'XII') AS nama_kelas,
                s.jurusan AS kode_jurusan,
                s.jurusan AS id_jurusan
            FROM siswa.siswa s
            LEFT JOIN akademik.kelas k ON (s.kelas_saat_ini = k.nama_kelas OR s.kelas_saat_ini = k.id::varchar) AND k.tenant_id = :tenant_id
            LEFT JOIN akademik.jurusan j ON (s.jurusan = j.nama_jurusan OR s.jurusan = j.id::varchar) AND j.tenant_id = :tenant_id
            WHERE s.tenant_id = :tenant_id
              AND s.status_siswa = 'Aktif'
              AND (k.nama_kelas ILIKE '%12%' OR k.nama_kelas ILIKE '%XII%' OR s.kelas_saat_ini ILIKE '%12%' OR s.kelas_saat_ini ILIKE '%XII%')
            ORDER BY s.id, s.nama_lengkap ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dedup = [];
        foreach ($rows as $r) {
            $dedup[(string)$r['id']] = $r;
        }
        return array_values($dedup);
    }

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
            $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
            if (empty($tahunAjaranId) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tahunAjaranId)) {
                $stmtActiveTa = $db->prepare("SELECT id FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtActiveTa->execute([$tenantId]);
                $tahunAjaranId = $stmtActiveTa->fetchColumn() ?: null;
            }

            // Ambil semua Jurusan dari Master Data (akademik.jurusan)
            $stmtMajors = $db->prepare("
                SELECT id, nama_jurusan, kategori, deskripsi 
                FROM akademik.jurusan 
                WHERE tenant_id = ? AND is_active = true 
                ORDER BY nama_jurusan ASC
            ");
            $stmtMajors->execute([$tenantId]);
            $majors = $stmtMajors->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Ambil semua mapel aktif dari Master Data (akademik.mata_pelajaran)
            $stmtAll = $db->prepare("
                SELECT id, nama_mata_pelajaran AS nama_mapel, kategori, deskripsi
                FROM akademik.mata_pelajaran
                WHERE tenant_id = ? AND is_active = true
                ORDER BY kategori ASC, nama_mata_pelajaran ASC
            ");
            $stmtAll->execute([$tenantId]);
            $allMapels = $stmtAll->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $jurusanId = $_GET['jurusan_id'] ?? null;
            if ($jurusanId && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $jurusanId)) {
                $jurusanId = null;
            }

            // Ambil konfigurasi mapel tersimpan untuk tahun ajaran ini
            $selectedMapels = [];
            if ($tahunAjaranId) {
                if ($jurusanId) {
                    $stmtSelected = $db->prepare("
                        SELECT mapel_id, sem_1, sem_2, sem_3, sem_4, sem_5, sem_6, jurusan_id 
                        FROM pdss.pdss_config_mapel 
                        WHERE tenant_id = ? AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL)
                          AND (jurusan_id = ? OR jurusan_id IS NULL)
                    ");
                    $stmtSelected->execute([$tenantId, $tahunAjaranId, $jurusanId]);
                } else {
                    $stmtSelected = $db->prepare("
                        SELECT mapel_id, sem_1, sem_2, sem_3, sem_4, sem_5, sem_6, jurusan_id 
                        FROM pdss.pdss_config_mapel 
                        WHERE tenant_id = ? AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL)
                    ");
                    $stmtSelected->execute([$tenantId, $tahunAjaranId]);
                }
                $selectedMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } else {
                if ($jurusanId) {
                    $stmtSelected = $db->prepare("
                        SELECT mapel_id, sem_1, sem_2, sem_3, sem_4, sem_5, sem_6, jurusan_id 
                        FROM pdss.pdss_config_mapel 
                        WHERE tenant_id = ? AND tahun_ajaran_id IS NULL AND (jurusan_id = ? OR jurusan_id IS NULL)
                    ");
                    $stmtSelected->execute([$tenantId, $jurusanId]);
                } else {
                    $stmtSelected = $db->prepare("
                        SELECT mapel_id, sem_1, sem_2, sem_3, sem_4, sem_5, sem_6, jurusan_id 
                        FROM pdss.pdss_config_mapel 
                        WHERE tenant_id = ? AND tahun_ajaran_id IS NULL
                    ");
                    $stmtSelected->execute([$tenantId]);
                }
                $selectedMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            $selectedIndexed = [];
            foreach ($selectedMapels as $sm) {
                $selectedIndexed[(string)$sm['mapel_id']] = $sm;
            }

            // Cek ketersediaan nilai riil di detail_nilai_rapor
            $stmtGradesCheck = $db->prepare("
                SELECT dnr.mapel_id, dnr.semester, k.nama_kelas
                FROM akademik.detail_nilai_rapor dnr
                LEFT JOIN akademik.kelas k ON dnr.kelas_id = k.id::varchar OR dnr.kelas_id = k.nama_kelas
                WHERE dnr.tenant_id = ?
            ");
            $stmtGradesCheck->execute([$tenantId]);
            $allGradesCheck = $stmtGradesCheck->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $gradesAvailability = [];
            foreach ($allGradesCheck as $g) {
                $mid = (string)$g['mapel_id'];
                $semLevel = $this->getSemesterLevel($g['nama_kelas'] ?? '', $g['semester'] ?? '');
                if ($semLevel !== null) {
                    $gradesAvailability[$mid][$semLevel] = true;
                }
            }

            foreach ($allMapels as &$m) {
                $mid = (string)$m['id'];
                $m['kode_mapel'] = $m['kategori'] ?? '';

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
                    $m['jurusan_id'] = $selectedIndexed[$mid]['jurusan_id'] ?? null;
                    $m['is_selected'] = true;
                } else {
                    $m['sem_1'] = false;
                    $m['sem_2'] = false;
                    $m['sem_3'] = false;
                    $m['sem_4'] = false;
                    $m['sem_5'] = false;
                    $m['sem_6'] = false;
                    $m['jurusan_id'] = null;
                    $m['is_selected'] = false;
                }
            }
            unset($m);

            // Ambil semua kelas aktif untuk filter tingkat/rombel
            $stmtClasses = $db->prepare("SELECT id, nama_kelas, kategori, kode_kelas FROM akademik.kelas WHERE tenant_id = ? AND is_active = true ORDER BY nama_kelas ASC");
            $stmtClasses->execute([$tenantId]);
            $classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $categories = array_values(array_unique(array_filter(array_column($allMapels, 'kategori'))));

            $this->jsonResponse([
                'success' => true,
                'majors' => $majors,
                'classes' => $classes,
                'categories' => $categories,
                'data' => $allMapels
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetPdssMapels] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat konfigurasi mapel PDSS: ' . $e->getMessage()], 500);
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
        if (empty($tahunAjaranId) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tahunAjaranId)) {
            $tahunAjaranId = null;
        }
        $targetJurusanId = $input['jurusan_id'] ?? null;
        if ($targetJurusanId && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $targetJurusanId)) {
            $targetJurusanId = null;
        }

        if (!is_array($configs)) {
            $this->jsonResponse(['error' => 'Data konfigurasi tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            if (empty($tahunAjaranId)) {
                $stmtActiveTa = $db->prepare("SELECT id FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtActiveTa->execute([$tenantId]);
                $tahunAjaranId = $stmtActiveTa->fetchColumn() ?: null;
            }

            // Periksa status terkunci untuk Langkah 1
            if ($tahunAjaranId) {
                $stmtLock = $db->prepare("SELECT is_locked FROM pdss.pdss_lock WHERE tenant_id = ? AND step = 1 AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL) LIMIT 1");
                $stmtLock->execute([$tenantId, $tahunAjaranId]);
            } else {
                $stmtLock = $db->prepare("SELECT is_locked FROM pdss.pdss_lock WHERE tenant_id = ? AND step = 1 AND tahun_ajaran_id IS NULL LIMIT 1");
                $stmtLock->execute([$tenantId]);
            }
            $isLocked = (int)$stmtLock->fetchColumn();
            if ($isLocked === 1) {
                $this->jsonResponse(['error' => 'Langkah 1 (Konfigurasi Mapel) telah dikunci oleh Guru BK.'], 400);
                return;
            }

            $db->beginTransaction();

            // Hapus konfigurasi lama untuk tahun ajaran & jurusan ini
            if ($targetJurusanId) {
                if ($tahunAjaranId) {
                    $stmtDel = $db->prepare("DELETE FROM pdss.pdss_config_mapel WHERE tenant_id = ? AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL) AND jurusan_id = ?");
                    $stmtDel->execute([$tenantId, $tahunAjaranId, $targetJurusanId]);
                } else {
                    $stmtDel = $db->prepare("DELETE FROM pdss.pdss_config_mapel WHERE tenant_id = ? AND tahun_ajaran_id IS NULL AND jurusan_id = ?");
                    $stmtDel->execute([$tenantId, $targetJurusanId]);
                }
            } else {
                if ($tahunAjaranId) {
                    $stmtDel = $db->prepare("DELETE FROM pdss.pdss_config_mapel WHERE tenant_id = ? AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL)");
                    $stmtDel->execute([$tenantId, $tahunAjaranId]);
                } else {
                    $stmtDel = $db->prepare("DELETE FROM pdss.pdss_config_mapel WHERE tenant_id = ? AND tahun_ajaran_id IS NULL");
                    $stmtDel->execute([$tenantId]);
                }
            }

            // Masukkan konfigurasi baru
            if (!empty($configs)) {
                $stmtIns = $db->prepare("
                    INSERT INTO pdss.pdss_config_mapel 
                    (id, tenant_id, tahun_ajaran_id, jurusan_id, mapel_id, sem_1, sem_2, sem_3, sem_4, sem_5, sem_6, is_active) 
                    VALUES (gen_random_uuid(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, true)
                ");

                foreach ($configs as $cfg) {
                    $mid = $cfg['mapel_id'] ?? null;
                    if (empty($mid)) continue;

                    $jurId = !empty($cfg['jurusan_id']) ? $cfg['jurusan_id'] : $targetJurusanId;
                    $s1 = !empty($cfg['sem_1']) ? true : false;
                    $s2 = !empty($cfg['sem_2']) ? true : false;
                    $s3 = !empty($cfg['sem_3']) ? true : false;
                    $s4 = !empty($cfg['sem_4']) ? true : false;
                    $s5 = !empty($cfg['sem_5']) ? true : false;
                    $s6 = !empty($cfg['sem_6']) ? true : false;

                    if ($s1 || $s2 || $s3 || $s4 || $s5 || $s6) {
                        $stmtIns->execute([
                            $tenantId,
                            $tahunAjaranId,
                            $jurId,
                            $mid,
                            $s1 ? 1 : 0,
                            $s2 ? 1 : 0,
                            $s3 ? 1 : 0,
                            $s4 ? 1 : 0,
                            $s5 ? 1 : 0,
                            $s6 ? 1 : 0
                        ]);
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
            $this->jsonResponse(['error' => 'Gagal menyimpan konfigurasi mapel PDSS: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Menentukan nomor semester (1 s.d 6) dari nama kelas & string semester
     */
    private function getSemesterLevel(string $className, string $semester): ?int {
        $className = strtoupper(trim($className));
        $semStr = strtolower(trim($semester));

        // Jika string semester adalah angka 3, 4, 5, 6 secara eksplisit tanpa info kelas
        if ($semStr === '3' || $semStr === '4' || $semStr === '5' || $semStr === '6') {
            return (int)$semStr;
        }

        // Tentukan apakah semester Ganjil (1) atau Genap (2)
        $isGanjil = (
            $semStr === '1' || 
            $semStr === 'ganjil' || 
            $semStr === 'odd' || 
            strpos($semStr, 'ganjil') !== false || 
            $semStr === 'sem 1' ||
            $semStr === 'semester 1' ||
            strpos($semStr, '1') !== false
        );

        // 1. Jika kelas adalah Kelas XII / 12 / Fase F Akhir
        if (preg_match('/\b(12|XII)\b/i', $className) || strpos($className, 'XII') !== false || strpos($className, '12') !== false) {
            return $isGanjil ? 5 : 6;
        }

        // 2. Jika kelas adalah Kelas XI / 11 / Fase F Awal
        if (preg_match('/\b(11|XI)\b/i', $className) || strpos($className, 'XI') !== false || strpos($className, '11') !== false) {
            return $isGanjil ? 3 : 4;
        }

        // 3. Jika kelas adalah Kelas X / 10 / Fase E
        if (preg_match('/\b(10|X)\b/i', $className) || strpos($className, 'X') !== false || strpos($className, '10') !== false || strpos($className, 'FASE E') !== false) {
            return $isGanjil ? 1 : 2;
        }

        // Fallback jika tidak ada info kelas
        if (is_numeric($semStr)) {
            $num = (int)$semStr;
            if ($num >= 1 && $num <= 6) return $num;
        }

        return null;
    }

    /**
     * Helper: Mendeteksi klaster mapel agama (SMA Umum / MA Sub-Subjects) via Smart Fuzzy Pattern
     * Return: 'MA_SUB' | 'STANDARD' | 'NONE'
     */
    private function detectReligionCluster(string $mapelName, string $mapelCode = ''): string {
        $text = strtolower(trim($mapelName . ' ' . $mapelCode));

        // 1. Cek apakah Sub-Mapel Agama khusus Madrasah Aliyah (MA)
        if (preg_match('/(akidah|aqidah|akhlak|fiqih|fiqh|fikh|al-qur[\'’]?an|qur[\'’]?an|hadits?|hadis|sejarah kebudayaan islam|\bski\b)/i', $text)) {
            return 'MA_SUB';
        }

        // 2. Cek apakah Mapel Agama Standar (SMA / SMK / Umum)
        if (preg_match('/(agama|islam|kristen|katolik|protestan|hindu|buddha|budha|khonghucu|konghucu|\bpabp\b|\bpai\b|\bpak\b|\bpab\b|\bpah\b|\bpakat\b|\bpakho\b|teologi)/i', $text)) {
            return 'STANDARD';
        }

        return 'NONE';
    }

    /**
     * API: Mendapatkan data Kesiapan PDSS & Simulasi Ranking Paralel Siswa Kelas 12
     * GET /api/v1/pdss/kesiapan
     */
    public function apiGetKesiapan(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Pilih sekolah terlebih dahulu.'], 200);
            return;
        }

        try {
            $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? null;
            $quotaPercent = isset($_GET['quota_percent']) ? (int)$_GET['quota_percent'] : null;
            $useERapor = !empty($_GET['use_e_rapor']);

            $result = $this->computeKesiapanData($tenantId, $tahunAjaranId, $quotaPercent, $useERapor);
            $this->jsonResponse($result);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetKesiapan] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal memuat data: ' . $e->getMessage()], 200);
        }
    }

    /**
     * Helper: Menghitung secara dinamis simulasi perangkingan dan kelayakan siswa PDSS
     */
    private function computeKesiapanData(string $tenantId, ?string $tahunAjaranId = null, ?int $quotaPercent = null, bool $useERapor = false): array {
        $db = \App\Config\Database::getConnection();

        // 1. Ambil daftar tahun ajaran langsung dari master data (akademik.tahun_ajaran)
        $stmtYears = $db->prepare("SELECT id, nama_tahun_ajaran AS tahun_ajaran, is_active FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY nama_tahun_ajaran DESC");
        $stmtYears->execute([$tenantId]);
        $years = $stmtYears->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($tahunAjaranId)) {
            foreach ($years as $yr) {
                if ((int)$yr['is_active'] === 1 || $yr['is_active'] === true || $yr['is_active'] === 't') {
                    $tahunAjaranId = $yr['id'];
                    break;
                }
            }
            if (empty($tahunAjaranId) && !empty($years)) {
                $tahunAjaranId = $years[0]['id'];
            }
        }

        $selectedTaName = '';
        foreach ($years as $yr) {
            if ($yr['id'] == $tahunAjaranId) {
                $selectedTaName = $yr['tahun_ajaran'];
                break;
            }
        }

        // 2. Ambil akreditasi sekolah & tentukan kuota default
        $stmtAcc = $db->prepare("SELECT akreditasi FROM core.tenants WHERE id = ? LIMIT 1");
        $stmtAcc->execute([$tenantId]);
        $accreditation = $stmtAcc->fetchColumn() ?: 'A (Unggul)';

        $baseQuota = 5;
        if (stripos($accreditation, 'A') !== false) {
            $baseQuota = 40;
        } elseif (stripos($accreditation, 'B') !== false) {
            $baseQuota = 25;
        }
        
        if ($quotaPercent === null || $quotaPercent <= 0 || $quotaPercent > 100) {
            $quotaPercent = $baseQuota;
        }

        // 3. Ambil status terkunci (Steps 1, 2, 3, 4)
        $stmtLock = $db->prepare("SELECT step, is_locked, locked_by, locked_at FROM pdss.pdss_lock WHERE tenant_id = ? AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL)");
        $stmtLock->execute([$tenantId, $tahunAjaranId]);
        $lockRows = $stmtLock->fetchAll(PDO::FETCH_ASSOC);

        $locks = [
            1 => ['is_locked' => false, 'locked_by' => null, 'locked_at' => null],
            2 => ['is_locked' => false, 'locked_by' => null, 'locked_at' => null],
            3 => ['is_locked' => false, 'locked_by' => null, 'locked_at' => null],
            4 => ['is_locked' => false, 'locked_by' => null, 'locked_at' => null],
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

        // 4. Ambil data pengunduran diri
        $stmtResign = $db->prepare("
            SELECT id, siswa_id, nomor_surat, tanggal_surat, alasan, nama_file, path_file, ukuran_file, status_verifikasi, created_at 
            FROM pdss.pengunduran_diri 
            WHERE tenant_id = ? AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL)
        ");
        $stmtResign->execute([$tenantId, $tahunAjaranId]);
        $resignRows = $stmtResign->fetchAll(PDO::FETCH_ASSOC);
        $resignedMap = [];
        foreach ($resignRows as $rr) {
            $resignedMap[(string)$rr['siswa_id']] = $rr;
        }

        // 5. Ambil manual eligible status
        $stmtManual = $db->prepare("SELECT siswa_id, status_eligible FROM pdss.pdss_manual_eligible WHERE tenant_id = ?");
        $stmtManual->execute([$tenantId]);
        $manualEligible = $stmtManual->fetchAll(PDO::FETCH_ASSOC);
        $manualEligibleMap = [];
        foreach ($manualEligible as $me) {
            $manualEligibleMap[(string)$me['siswa_id']] = $me['status_eligible'];
        }

        // 6. Ambil seluruh siswa aktif kelas 12 dari anggota_kelas, riwayat kenaikan, dan kelas saat ini
        $students = $this->getKelas12Students($tenantId, $selectedTaName);

        if (empty($students)) {
            return [
                'success' => true,
                'accreditation' => $accreditation,
                'quota_percent' => $quotaPercent,
                'major_quota_limits' => [],
                'major_totals' => [],
                'total_configured_semesters' => 0,
                'locks' => $locks,
                'years' => $years,
                'data' => []
            ];
        }

        // 7. Ambil konfigurasi mata pelajaran PDSS
        $stmtConfig = $db->prepare("
            SELECT DISTINCT ON (mp.id) pcm.mapel_id, mp.nama_mata_pelajaran AS nama_mapel, mp.kategori AS kode_mapel,
                   pcm.sem_1, pcm.sem_2, pcm.sem_3, pcm.sem_4, pcm.sem_5, pcm.sem_6,
                   pcm.jurusan_id
            FROM pdss.pdss_config_mapel pcm
            JOIN akademik.mata_pelajaran mp ON pcm.mapel_id = mp.id
            WHERE pcm.tenant_id = ? " . ($tahunAjaranId ? "AND pcm.tahun_ajaran_id = ?" : "") . "
            ORDER BY mp.id, pcm.created_at DESC
        ");
        $paramsConfig = $tahunAjaranId ? [$tenantId, $tahunAjaranId] : [$tenantId];
        $stmtConfig->execute($paramsConfig);
        $configMapels = $stmtConfig->fetchAll(PDO::FETCH_ASSOC);

        if (empty($configMapels)) {
            $stmtConfig = $db->prepare("
                SELECT DISTINCT ON (mp.id) pcm.mapel_id, mp.nama_mata_pelajaran AS nama_mapel, mp.kategori AS kode_mapel,
                       pcm.sem_1, pcm.sem_2, pcm.sem_3, pcm.sem_4, pcm.sem_5, pcm.sem_6,
                       pcm.jurusan_id
                FROM pdss.pdss_config_mapel pcm
                JOIN akademik.mata_pelajaran mp ON pcm.mapel_id = mp.id
                WHERE pcm.tenant_id = ?
                ORDER BY mp.id, pcm.created_at DESC
            ");
            $stmtConfig->execute([$tenantId]);
            $configMapels = $stmtConfig->fetchAll(PDO::FETCH_ASSOC);
        }

        $totalConfiguredSemesters = 0;
        foreach ($configMapels as $cm) {
            for ($sNum = 1; $sNum <= 6; $sNum++) {
                if (!empty($cm["sem_$sNum"])) $totalConfiguredSemesters++;
            }
        }

        // 8. Ambil nilai siswa
        $studentIds = array_column($students, 'id');
        $idPlaceholders = implode(',', array_fill(0, count($studentIds), '?'));
        $sqlGrades = "
            SELECT dnr.siswa_id, dnr.mapel_id, dnr.nilai_akhir, dnr.semester, dnr.tahun_ajaran, k.nama_kelas
            FROM akademik.detail_nilai_rapor dnr
            LEFT JOIN akademik.kelas k ON dnr.kelas_id = k.id::varchar OR dnr.kelas_id = k.nama_kelas
            WHERE dnr.siswa_id IN ($idPlaceholders) 
              AND dnr.tenant_id = ?
            ORDER BY dnr.tahun_ajaran DESC, dnr.id DESC
        ";
        $stmtGrades = $db->prepare($sqlGrades);
        $paramsGrades = array_merge($studentIds, [$tenantId]);
        $stmtGrades->execute($paramsGrades);
        $allGrades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

        $studentGradesMap = [];
        $repeatedSemestersCount = [];
        foreach ($allGrades as $g) {
            $sid = (string)$g['siswa_id'];
            $mid = (string)$g['mapel_id'];
            $semLevel = $this->getSemesterLevel($g['nama_kelas'] ?? '', $g['semester'] ?? '');
            if ($semLevel === null) continue;

            if (isset($studentGradesMap[$sid][$mid][$semLevel])) {
                $repeatedSemestersCount[$sid][$semLevel] = ($repeatedSemestersCount[$sid][$semLevel] ?? 1) + 1;
                continue;
            }
            $studentGradesMap[$sid][$mid][$semLevel] = (float)$g['nilai_akhir'];
        }

        $processedStudents = [];
        $majorTotals = [];
        foreach ($students as $s) {
            $sid = (string)$s['id'];
            $jur = $s['nama_jurusan'] ?: 'Umum';
            $jurId = (string)($s['id_jurusan'] ?? '');
            $majorTotals[$jur] = ($majorTotals[$jur] ?? 0) + 1;

            $semSums = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0, 5 => 0.0, 6 => 0.0];
            $semCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $semAvgs = [];
            $sum = 0.0;
            $cnt = 0;
            $mapelGradesDetail = [];
            $maSubGradesPerSem = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];

            foreach ($configMapels as $cfg) {
                $mid = (string)$cfg['mapel_id'];
                $cfgJur = (string)($cfg['jurusan_id'] ?? '');

                if (!empty($cfgJur) && $cfgJur !== $jurId && !empty($s['kode_jurusan']) && $cfg['kode_mapel'] !== $s['kode_jurusan']) {
                    continue;
                }

                $relType = $this->detectReligionCluster($cfg['nama_mapel'] ?? '', $cfg['kode_mapel'] ?? '');

                for ($sem = 1; $sem <= 6; $sem++) {
                    if (!empty($cfg["sem_$sem"])) {
                        $grade = $studentGradesMap[$sid][$mid][$sem] ?? null;
                        if ($grade !== null && $grade > 0) {
                            if ($relType === 'MA_SUB') {
                                $maSubGradesPerSem[$sem][] = $grade;
                            } else {
                                $sum += $grade;
                                $cnt++;
                                $semSums[$sem] += $grade;
                                $semCounts[$sem]++;
                            }
                        }
                        if ($grade !== null) {
                            $mapelGradesDetail[$mid][$sem] = $grade;
                        }
                    }
                }
            }

            // Agregasi Sub-Mapel Agama MA menjadi 1 nilai resmi PABP per semester
            for ($sem = 1; $sem <= 6; $sem++) {
                if (!empty($maSubGradesPerSem[$sem])) {
                    $maAvg = round(array_sum($maSubGradesPerSem[$sem]) / count($maSubGradesPerSem[$sem]), 2);
                    $sum += $maAvg;
                    $cnt++;
                    $semSums[$sem] += $maAvg;
                    $semCounts[$sem]++;
                }
            }

            for ($sem = 1; $sem <= 6; $sem++) {
                $semAvgs[$sem] = $semCounts[$sem] > 0 ? round($semSums[$sem] / $semCounts[$sem], 2) : null;
            }

            $isRetained = !empty($repeatedSemestersCount[$sid]);
            $rataRata = $cnt > 0 ? round($sum / $cnt, 2) : 0.0;

            $processedStudents[] = [
                'id' => $s['id'],
                'nama_lengkap' => $s['nama_lengkap'],
                'nisn' => $s['nisn'],
                'nis' => $s['nis'],
                'nama_jurusan' => $jur,
                'nama_kelas' => $s['nama_kelas'] ?: 'XII',
                'kode_jurusan' => $s['kode_jurusan'],
                'id_jurusan' => $s['id_jurusan'],
                'rata_rata' => $rataRata,
                'jumlah_nilai' => $cnt,
                'total_nilai' => round($sum, 2),
                'total_mapel_diambil' => $cnt,
                'semester_avgs' => $semAvgs,
                'is_retained' => $isRetained,
                'status_eligible' => $manualEligibleMap[$sid] ?? 'auto',
                'is_eligible' => false,
                'is_eligible_final' => false,
                'rank_paralel' => 0,
                'majorRank' => 0,
                'mapel_grades' => $mapelGradesDetail,
                'is_resigned' => isset($resignedMap[$sid]),
                'status_pengunduran_diri' => isset($resignedMap[$sid]) ? $resignedMap[$sid]['status_verifikasi'] : null,
                'pengunduran_diri' => $resignedMap[$sid] ?? null
            ];
        }

        $majorQuotaLimits = [];
        foreach ($majorTotals as $jur => $tot) {
            $majorQuotaLimits[$jur] = (int)ceil($tot * ($quotaPercent / 100));
        }

        $jurusanGroups = [];
        foreach ($processedStudents as $stu) {
            $jur = $stu['nama_jurusan'] ?: 'Umum';
            $jurusanGroups[$jur][] = $stu;
        }

        $finalStudentsList = [];
        foreach ($jurusanGroups as $jur => $group) {
            usort($group, function($a, $b) {
                if ($a['rata_rata'] == $b['rata_rata']) return strcmp($a['nama_lengkap'], $b['nama_lengkap']);
                return ($a['rata_rata'] > $b['rata_rata']) ? -1 : 1;
            });

            $quotaLimit = $majorQuotaLimits[$jur] ?? 0;
            $activeEligibleCount = 0;

            foreach ($group as $idx => &$stu) {
                $stu['majorRank'] = $idx + 1;
                $stu['ranking_jurusan'] = $idx + 1;
                $stu['nilai_rata_rata'] = $stu['rata_rata'] ?? 0.0;
                $sid = (string)$stu['id'];
                $isResigned = isset($resignedMap[$sid]);
                $stu['is_resigned'] = $isResigned;
                $stu['pengunduran_diri'] = $isResigned ? $resignedMap[$sid] : null;

                if ($isResigned) {
                    $stu['is_eligible'] = false;
                    $stu['status_keterangan'] = 'Mengundurkan Diri';
                } elseif ($stu['status_eligible'] === 'eligible') {
                    $stu['is_eligible'] = true;
                    $stu['status_keterangan'] = 'Eligible (Manual)';
                    $activeEligibleCount++;
                } elseif ($stu['status_eligible'] === 'tidak_eligible') {
                    $stu['is_eligible'] = false;
                    $stu['status_keterangan'] = 'Tidak Eligible (Manual)';
                } else {
                    if ($activeEligibleCount < $quotaLimit && $stu['rata_rata'] > 0) {
                        $stu['is_eligible'] = true;
                        $activeEligibleCount++;
                        $stu['is_replacement'] = ($idx + 1 > $quotaLimit);
                        $stu['status_keterangan'] = $stu['is_replacement'] ? 'Eligible (Pelimpahan Kuota)' : 'Eligible (Kuota Standar)';
                    } else {
                        $stu['is_eligible'] = false;
                        $stu['status_keterangan'] = 'Di Luar Kuota';
                    }
                }
                $finalStudentsList[] = $stu;
            }
        }

        usort($finalStudentsList, fn($a, $b) => ($a['nama_jurusan'] !== $b['nama_jurusan']) ? strcmp($a['nama_jurusan'], $b['nama_jurusan']) : $a['majorRank'] - $b['majorRank']);

        return [
            'success' => true,
            'accreditation' => $accreditation,
            'quota_percent' => $quotaPercent,
            'major_quota_limits' => $majorQuotaLimits,
            'major_totals' => $majorTotals,
            'total_configured_semesters' => $totalConfiguredSemesters,
            'locks' => $locks,
            'years' => $years,
            'data' => $finalStudentsList
        ];
    }

    /**
     * API: Hitung Ulang & Cache Ranking Paralel PDSS
     * POST /api/v1/pdss/recalc-kesiapan
     */
    public function apiRecalcKesiapan(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak.'], 200);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 200);
            return;
        }

        $input = $this->getJsonInput();
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? $_GET['tahun_ajaran_id'] ?? null;

        try {
            $db = \App\Config\Database::getConnection();

            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }

            // Ambil akreditasi
            $stmtAcc = $db->prepare("SELECT akreditasi FROM core.tenants WHERE id = ? LIMIT 1");
            $stmtAcc->execute([$tenantId]);
            $baseQuota = (stripos($stmtAcc->fetchColumn(), 'A') !== false) ? 40 : 25;

            // Ambil mapel PDSS
            $stmtSelected = $db->prepare("
                SELECT pcm.mapel_id, pcm.sem_1, pcm.sem_2, pcm.sem_3, pcm.sem_4, pcm.sem_5, pcm.sem_6
                FROM pdss.pdss_config_mapel pcm
                WHERE pcm.tenant_id = ? AND (pcm.tahun_ajaran_id = ? OR pcm.tahun_ajaran_id IS NULL)
            ");
            $stmtSelected->execute([$tenantId, $tahunAjaranId]);
            $configMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC);

            if (empty($configMapels)) {
                $this->jsonResponse(['success' => false, 'error' => 'Mata pelajaran PDSS belum dikonfigurasi di Langkah 1.'], 200);
                return;
            }

            // Ambil nama tahun ajaran
            $stmtTaName = $db->prepare("SELECT nama_tahun_ajaran FROM akademik.tahun_ajaran WHERE id = ? LIMIT 1");
            $stmtTaName->execute([$tahunAjaranId]);
            $selectedTaName = $stmtTaName->fetchColumn() ?: null;

            // Ambil siswa kelas 12 via helper cohort terpadu
            $students = $this->getKelas12Students($tenantId, $selectedTaName);

            if (empty($students)) {
                $this->jsonResponse(['success' => false, 'error' => 'Tidak ditemukan data siswa aktif kelas 12.'], 200);
                return;
            }

            $siswaIds = array_column($students, 'id');
            $mapelIds = array_column($configMapels, 'mapel_id');
            $siswaPlaceholders = implode(',', array_fill(0, count($siswaIds), '?'));
            $mapelPlaceholders = implode(',', array_fill(0, count($mapelIds), '?'));

            $stmtGrades = $db->prepare("
                SELECT dnr.siswa_id, dnr.mapel_id, dnr.nilai_akhir, dnr.semester, dnr.tahun_ajaran, k.nama_kelas
                FROM akademik.detail_nilai_rapor dnr
                LEFT JOIN akademik.kelas k ON (dnr.kelas_id = k.id::varchar OR dnr.kelas_id = k.nama_kelas) AND k.tenant_id = ?
                WHERE dnr.tenant_id = ?
                  AND dnr.mapel_id IN ($mapelPlaceholders)
                  AND dnr.siswa_id IN ($siswaPlaceholders)
            ");
            $stmtGrades->execute(array_merge([$tenantId, $tenantId], $mapelIds, $siswaIds));
            $allGrades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

            $studentGradesMap = [];
            foreach ($allGrades as $g) {
                $sid = (string)$g['siswa_id'];
                $mid = (string)$g['mapel_id'];
                $semLevel = $this->getSemesterLevel($g['nama_kelas'] ?? '', $g['semester'] ?? '');
                if ($semLevel === null) continue;
                $val = (float)$g['nilai_akhir'];
                $studentGradesMap[$sid][$mid][$semLevel] = $val;
            }

            // Hitung rata-rata
            $computedList = [];
            $majorTotals = [];
            foreach ($students as $s) {
                $sid = (string)$s['id'];
                $jur = $s['nama_jurusan'] ?: 'Umum';
                $majorTotals[$jur] = ($majorTotals[$jur] ?? 0) + 1;

                $sum = 0.0;
                $cnt = 0;
                foreach ($configMapels as $cfg) {
                    $mid = (string)$cfg['mapel_id'];
                    for ($sem = 1; $sem <= 6; $sem++) {
                        if (!empty($cfg["sem_$sem"]) && isset($studentGradesMap[$sid][$mid][$sem])) {
                            $sum += $studentGradesMap[$sid][$mid][$sem];
                            $cnt++;
                        }
                    }
                }
                $avg = $cnt > 0 ? round($sum / $cnt, 2) : 0.0;
                $computedList[] = [
                    'siswa_id' => $sid,
                    'nama_jurusan' => $jur,
                    'rata_rata' => $avg
                ];
            }

            usort($computedList, fn($a, $b) => ($a['rata_rata'] == $b['rata_rata']) ? 0 : (($a['rata_rata'] > $b['rata_rata']) ? -1 : 1));

            $jurusanCounters = [];
            $db->beginTransaction();

            $stmtUpsertKesiapan = $db->prepare("
                INSERT INTO pdss.kesiapan_siswa (id, tenant_id, siswa_id, tahun_ajaran_id, nilai_rata_rata, ranking_sekolah, ranking_jurusan, is_eligible)
                VALUES (gen_random_uuid(), ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT (tenant_id, siswa_id, COALESCE(tahun_ajaran_id, '00000000-0000-0000-0000-000000000000'))
                DO UPDATE SET 
                    nilai_rata_rata = EXCLUDED.nilai_rata_rata,
                    ranking_sekolah = EXCLUDED.ranking_sekolah,
                    ranking_jurusan = EXCLUDED.ranking_jurusan,
                    is_eligible = EXCLUDED.is_eligible,
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmtUpsertRanking = $db->prepare("
                INSERT INTO pdss.pdss_ranking (id, tenant_id, siswa_id, tahun_ajaran_id, nama_jurusan, nilai_rata_rata, ranking_jurusan, ranking_sekolah, is_eligible)
                VALUES (gen_random_uuid(), ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT (tenant_id, siswa_id, COALESCE(tahun_ajaran_id, '00000000-0000-0000-0000-000000000000'))
                DO UPDATE SET 
                    nama_jurusan = EXCLUDED.nama_jurusan,
                    nilai_rata_rata = EXCLUDED.nilai_rata_rata,
                    ranking_jurusan = EXCLUDED.ranking_jurusan,
                    ranking_sekolah = EXCLUDED.ranking_sekolah,
                    is_eligible = EXCLUDED.is_eligible,
                    updated_at = CURRENT_TIMESTAMP
            ");

            $rankSekolah = 1;
            foreach ($computedList as $item) {
                $jur = $item['nama_jurusan'];
                if (!isset($jurusanCounters[$jur])) $jurusanCounters[$jur] = 1;
                $rankJur = $jurusanCounters[$jur]++;

                $quotaLimit = (int)ceil(($majorTotals[$jur] ?? 0) * ($baseQuota / 100));
                $isEligible = ($rankJur <= $quotaLimit && $item['rata_rata'] > 0);

                $stmtUpsertKesiapan->execute([
                    $tenantId, $item['siswa_id'], $tahunAjaranId,
                    $item['rata_rata'], $rankSekolah, $rankJur, $isEligible ? 'TRUE' : 'FALSE'
                ]);

                $stmtUpsertRanking->execute([
                    $tenantId, $item['siswa_id'], $tahunAjaranId,
                    $jur, $item['rata_rata'], $rankJur, $rankSekolah, $isEligible ? 'TRUE' : 'FALSE'
                ]);

                $rankSekolah++;
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Ranking paralel ' . count($computedList) . ' siswa berhasil dihitung ulang dan disimpan.']);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('[PDSSController::apiRecalcKesiapan] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal menghitung ulang ranking: ' . $e->getMessage()], 200);
        }
    }

    /**
     * API: Ekspor Data Kelayakan Siswa PDSS SNBP (Excel/CSV)
     * GET /api/v1/pdss/export-snbp
     */
    public function apiExportSnbp(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            echo "Akses ditolak atau tenant tidak terdeteksi.";
            return;
        }

        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? null;
        try {
            // Hitung data kelayakan siswa secara dinamis & konsisten
            $kesiapanResult = $this->computeKesiapanData($tenantId, $tahunAjaranId);
            $students = $kesiapanResult['data'] ?? [];

            $xlsxRows = [];
            $header = ['No', 'Ranking Jurusan', 'NISN', 'NIS', 'Nama Siswa', 'Kelas', 'Jurusan', 'Rata-rata Sem 1-5', 'Status SNBP', 'Keterangan Kelayakan'];
            $xlsxRows[] = $header;

            $no = 1;
            foreach ($students as $s) {
                $statusEligible = !empty($s['is_eligible']) ? 'ELIGIBLE' : ($s['is_resigned'] ? 'MENGUNDURKAN DIRI' : 'TIDAK ELIGIBLE');
                $xlsxRows[] = [
                    $no++,
                    '#' . ($s['majorRank'] ?? $no),
                    $s['nisn'] ?: '—',
                    $s['nis'] ?: '—',
                    $s['nama_lengkap'],
                    $s['nama_kelas'] ?: '—',
                    $s['nama_jurusan'] ?: 'Umum',
                    $s['rata_rata'] ? number_format((float)$s['rata_rata'], 2, '.', '') : '0.00',
                    $statusEligible,
                    $s['status_keterangan'] ?? '—'
                ];
            }

            $filename = 'rekap_kelayakan_pdss_snbp_' . date('Ymd_His') . '.xlsx';
            if (class_exists('\Shuchkin\SimpleXLSXGen')) {
                \Shuchkin\SimpleXLSXGen::fromArray($xlsxRows)->downloadAs($filename);
                exit;
            } else {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . str_replace('.xlsx', '.csv', $filename));
                $output = fopen('php://output', 'w');
                foreach ($xlsxRows as $r) {
                    fputcsv($output, $r);
                }
                fclose($output);
                exit;
            }
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiExportSnbp] ' . $e->getMessage());
            echo "Terjadi kesalahan sistem saat mengekspor data: " . htmlspecialchars($e->getMessage());
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

            // 1. Ambil data siswa
            $stmtSiswa = $db->prepare("
                SELECT id, nama_lengkap, nisn, nis, kelas_saat_ini AS nama_kelas, jurusan AS nama_jurusan
                FROM siswa.siswa
                WHERE id = ? AND tenant_id = ? LIMIT 1
            ");
            $stmtSiswa->execute([$siswaId, $tenantId]);
            $student = $stmtSiswa->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                $this->jsonResponse(['error' => 'Siswa tidak ditemukan atau akses ditolak.'], 404);
                return;
            }

            // 2. Ambil seluruh mata pelajaran pilihan (DISTINCT ON mp.id agar tidak duplikat multi-tahun)
            $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
            if ($tahunAjaranId && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tahunAjaranId)) {
                $stmtSelected = $db->prepare("
                    SELECT DISTINCT ON (mp.id) pcm.mapel_id, mp.nama_mata_pelajaran AS nama_mapel, mp.kategori AS kode_mapel,
                           pcm.sem_1, pcm.sem_2, pcm.sem_3, pcm.sem_4, pcm.sem_5, pcm.sem_6
                    FROM pdss.pdss_config_mapel pcm
                    JOIN akademik.mata_pelajaran mp ON pcm.mapel_id = mp.id
                    WHERE pcm.tenant_id = ? AND pcm.tahun_ajaran_id = ?
                    ORDER BY mp.id, pcm.created_at DESC
                ");
                $stmtSelected->execute([$tenantId, $tahunAjaranId]);
                $configMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC);
            }

            if (empty($configMapels)) {
                $stmtSelected = $db->prepare("
                    SELECT DISTINCT ON (mp.id) pcm.mapel_id, mp.nama_mata_pelajaran AS nama_mapel, mp.kategori AS kode_mapel,
                           pcm.sem_1, pcm.sem_2, pcm.sem_3, pcm.sem_4, pcm.sem_5, pcm.sem_6
                    FROM pdss.pdss_config_mapel pcm
                    JOIN akademik.mata_pelajaran mp ON pcm.mapel_id = mp.id
                    WHERE pcm.tenant_id = ?
                    ORDER BY mp.id, pcm.created_at DESC
                ");
                $stmtSelected->execute([$tenantId]);
                $configMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC);
            }

            // Sort alfabetis berdasarkan nama mapel
            usort($configMapels, function($a, $b) {
                return strcmp($a['nama_mapel'], $b['nama_mapel']);
            });

            if (empty($configMapels)) {
                $this->jsonResponse(true, ['student' => $student, 'grades' => []]);
                return;
            }

            $mapelIds = array_column($configMapels, 'mapel_id');
            $mapelPlaceholders = implode(',', array_fill(0, count($mapelIds), '?'));

            // 3. Ambil seluruh data nilai rapor siswa
            $sqlGrades = "
                SELECT dnr.mapel_id, dnr.nilai_akhir, dnr.semester, dnr.tahun_ajaran, k.nama_kelas
                FROM akademik.detail_nilai_rapor dnr
                LEFT JOIN akademik.kelas k ON dnr.kelas_id = k.id::varchar OR dnr.kelas_id = k.nama_kelas
                WHERE dnr.siswa_id = ? 
                  AND dnr.tenant_id = ?
                  AND dnr.mapel_id IN ($mapelPlaceholders)
            ";
            $stmtGrades = $db->prepare($sqlGrades);
            $params = array_merge([$siswaId, $tenantId], $mapelIds);
            $stmtGrades->execute($params);
            $rawGrades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

            $groupedGrades = [];
            foreach ($rawGrades as $rg) {
                $mid = (string)$rg['mapel_id'];
                $semLevel = $this->getSemesterLevel($rg['nama_kelas'] ?? '', $rg['semester'] ?? '');
                if ($semLevel === null) continue;

                $val = (float)$rg['nilai_akhir'];
                $year = $rg['tahun_ajaran'] ?? '';

                if (!isset($groupedGrades[$mid][$semLevel]) || $year > ($groupedGrades[$mid][$semLevel]['tahun_ajaran'] ?? '')) {
                    $groupedGrades[$mid][$semLevel] = [
                        'nilai' => $val,
                        'tahun_ajaran' => $year
                    ];
                }
            }

            // 4. Susun struktur response tabel audit (dengan rincian sub-mapel MA + baris gabungan resmi PDSS)
            $gradesResponse = [];
            $semSums = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0, 5 => 0.0, 6 => 0.0];
            $semCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $totalSum = 0.0;
            $totalCount = 0;

            $maSubGradesPerSem = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];
            $hasMaSubSubjects = false;

            foreach ($configMapels as $cfg) {
                $mid = (string)$cfg['mapel_id'];
                $mSum = 0.0;
                $mCnt = 0;
                $relType = $this->detectReligionCluster($cfg['nama_mapel'], $cfg['kode_mapel']);
                
                $row = [
                    'mapel_id' => $mid,
                    'nama_mapel' => $cfg['nama_mapel'],
                    'kode_mapel' => $cfg['kode_mapel'],
                    'rel_type' => $relType,
                    'is_ma_sub' => ($relType === 'MA_SUB'),
                    'is_ma_aggregated' => false
                ];

                if ($relType === 'MA_SUB') {
                    $hasMaSubSubjects = true;
                }

                for ($sem = 1; $sem <= 6; $sem++) {
                    $isConfigured = !empty($cfg["sem_$sem"]);
                    $row["sem_$sem"] = [
                        'is_configured' => $isConfigured,
                        'nilai' => null,
                        'tahun_ajaran' => null
                    ];

                    if ($isConfigured && isset($groupedGrades[$mid][$sem])) {
                        $val = (float)$groupedGrades[$mid][$sem]['nilai'];
                        $year = $groupedGrades[$mid][$sem]['tahun_ajaran'];
                        $row["sem_$sem"]['nilai'] = $val;
                        $row["sem_$sem"]['tahun_ajaran'] = $year;
                        
                        if ($val > 0) {
                            $mSum += $val;
                            $mCnt++;

                            if ($relType === 'MA_SUB') {
                                $maSubGradesPerSem[$sem][] = [
                                    'mapel' => $cfg['nama_mapel'],
                                    'nilai' => $val,
                                    'tahun' => $year
                                ];
                            } else {
                                $semSums[$sem] += $val;
                                $semCounts[$sem]++;
                                $totalSum += $val;
                                $totalCount++;
                            }
                        }
                    }
                }

                $row['rata_rata'] = $mCnt > 0 ? round($mSum / $mCnt, 2) : null;
                $row['jumlah_nilai'] = $mCnt;
                $gradesResponse[] = $row;
            }

            // Jika terdapat Sub-Mapel Agama MA, tambahkan Baris Gabungan Rumpun Resmi PDSS
            if ($hasMaSubSubjects) {
                $maUnifiedRow = [
                    'mapel_id' => 'ma_unified_pabp',
                    'nama_mapel' => 'Pendidikan Agama dan Budi Pekerti (Gabungan Rumpun MA)',
                    'kode_mapel' => 'Rumpun Resmi PDSS',
                    'rel_type' => 'MA_AGGREGATED',
                    'is_ma_sub' => false,
                    'is_ma_aggregated' => true
                ];

                $maUnifiedSum = 0.0;
                $maUnifiedCnt = 0;

                for ($sem = 1; $sem <= 6; $sem++) {
                    $subs = $maSubGradesPerSem[$sem];
                    if (!empty($subs)) {
                        $subVals = array_column($subs, 'nilai');
                        $avgVal = round(array_sum($subVals) / count($subVals), 2);
                        $maUnifiedRow["sem_$sem"] = [
                            'is_configured' => true,
                            'nilai' => $avgVal,
                            'tahun_ajaran' => $subs[0]['tahun'] ?? null,
                            'breakdown' => $subs
                        ];
                        $maUnifiedSum += $avgVal;
                        $maUnifiedCnt++;

                        // Masukkan ke pembagi total rata-rata siswa sebagai 1 komponen resmi
                        $semSums[$sem] += $avgVal;
                        $semCounts[$sem]++;
                        $totalSum += $avgVal;
                        $totalCount++;
                    } else {
                        $maUnifiedRow["sem_$sem"] = [
                            'is_configured' => true,
                            'nilai' => null,
                            'tahun_ajaran' => null,
                            'breakdown' => []
                        ];
                    }
                }

                $maUnifiedRow['rata_rata'] = $maUnifiedCnt > 0 ? round($maUnifiedSum / $maUnifiedCnt, 2) : null;
                $maUnifiedRow['jumlah_nilai'] = $maUnifiedCnt;
                $gradesResponse[] = $maUnifiedRow;
            }

            $semAvgs = [];
            for ($sem = 1; $sem <= 6; $sem++) {
                $semAvgs[$sem] = $semCounts[$sem] > 0 ? round($semSums[$sem] / $semCounts[$sem], 2) : null;
            }

            $overallAvg = $totalCount > 0 ? round($totalSum / $totalCount, 2) : 0.0;

            $this->jsonResponse(true, [
                'student' => $student,
                'grades' => $gradesResponse,
                'has_ma_subs' => $hasMaSubSubjects,
                'semester_avgs' => $semAvgs,
                'total_nilai' => round($totalSum, 2),
                'total_mapel_diambil' => $totalCount,
                'rata_rata' => $overallAvg
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetStudentGrades] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat detail nilai rapor siswa: ' . $e->getMessage()], 500);
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
        $status = $input['status_eligible'] ?? 'auto';

        if (empty($siswaId) || !in_array($status, ['auto', 'eligible', 'tidak_eligible'], true)) {
            $this->jsonResponse(['error' => 'Parameter tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO pdss.pdss_manual_eligible (id, tenant_id, siswa_id, status_eligible)
                VALUES (gen_random_uuid(), ?, ?, ?)
                ON CONFLICT (tenant_id, siswa_id) DO UPDATE SET status_eligible = EXCLUDED.status_eligible, updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$tenantId, $siswaId, $status]);

            $this->jsonResponse(['success' => true, 'message' => 'Status kelayakan manual siswa berhasil diperbarui.']);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiSaveManualEligible] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memperbarui status kelayakan manual.'], 500);
        }
    }

    /**
     * API: Reset seluruh status kelayakan manual siswa (kembali ke auto)
     * POST /api/v1/pdss/reset-eligible
     */
    public function apiResetEligible(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $input = $this->getJsonInput();
            $tahunAjaranId = $input['tahun_ajaran_id'] ?? $_GET['tahun_ajaran_id'] ?? null;

            // Hapus seluruh record override manual untuk tenant ini
            $stmt = $db->prepare("DELETE FROM pdss.pdss_manual_eligible WHERE tenant_id = ?");
            $stmt->execute([$tenantId]);

            // Hitung ulang dan persistensi data ranking
            $kesiapan = $this->computeKesiapanData($tenantId, $tahunAjaranId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Seluruh status siswa eligible berhasil di-reset ke kalkulasi otomatis (kuota & nilai rapor).',
                'data' => $kesiapan['data'] ?? []
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiResetEligible] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mereset status siswa eligible: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Mengunci atau membuka kunci data kesiapan PDSS
     * POST /api/v1/pdss/lock
     */
    public function apiToggleLock(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak.'], 200);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 200);
            return;
        }

        $input = $this->getJsonInput();
        $isLocked = !empty($input['is_locked']) ? 'TRUE' : 'FALSE';
        $step = isset($input['step']) ? (int)$input['step'] : 1;
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? null;
        
        $userName = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Guru BK';
        $now = date('Y-m-d H:i:s');

        try {
            $db = \App\Config\Database::getConnection();

            // Sequential lock enforcement
            if ($isLocked === 'TRUE' && $step > 1) {
                // Check if previous step is locked
                $prevStep = $step - 1;
                $stmtCheck = $db->prepare("
                    SELECT is_locked FROM pdss.pdss_lock 
                    WHERE tenant_id = ? AND step = ? AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL)
                    LIMIT 1
                ");
                $stmtCheck->execute([$tenantId, $prevStep, $tahunAjaranId]);
                $isPrevLocked = (bool)$stmtCheck->fetchColumn();

                if (!$isPrevLocked) {
                    $stepNames = [1 => 'Mata Pelajaran', 2 => 'Kuota & Filter', 3 => 'Kelayakan & Ranking'];
                    $prevName = $stepNames[$prevStep] ?? "Langkah $prevStep";
                    $this->jsonResponse([
                        'success' => false,
                        'error' => "Langkah {$prevStep} ({$prevName}) harus dikunci terlebih dahulu sebelum mengunci Langkah {$step}."
                    ], 200);
                    return;
                }
            }

            // Sequential unlock enforcement
            if ($isLocked === 'FALSE' && $step < 3) {
                $nextStep = $step + 1;
                $stmtCheckNext = $db->prepare("
                    SELECT is_locked FROM pdss.pdss_lock 
                    WHERE tenant_id = ? AND step = ? AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL)
                    LIMIT 1
                ");
                $stmtCheckNext->execute([$tenantId, $nextStep, $tahunAjaranId]);
                $isNextLocked = (bool)$stmtCheckNext->fetchColumn();

                if ($isNextLocked) {
                    $stepNames = [1 => 'Mata Pelajaran', 2 => 'Kuota & Filter', 3 => 'Kelayakan & Ranking'];
                    $nextName = $stepNames[$nextStep] ?? "Langkah $nextStep";
                    $this->jsonResponse([
                        'success' => false,
                        'error' => "Langkah {$nextStep} ({$nextName}) saat ini terkunci. Buka kunci Langkah {$nextStep} terlebih dahulu sebelum membuka kunci Langkah {$step}."
                    ], 200);
                    return;
                }
            }

            $stmt = $db->prepare("
                INSERT INTO pdss.pdss_lock (id, tenant_id, step, tahun_ajaran_id, is_locked, locked_by, locked_at)
                VALUES (gen_random_uuid(), ?, ?, ?, {$isLocked}, ?, ?)
                ON CONFLICT (tenant_id, step, COALESCE(tahun_ajaran_id, '00000000-0000-0000-0000-000000000000')) 
                DO UPDATE SET is_locked = {$isLocked}, locked_by = EXCLUDED.locked_by, locked_at = EXCLUDED.locked_at, updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$tenantId, $step, $tahunAjaranId, $isLocked === 'TRUE' ? $userName : null, $isLocked === 'TRUE' ? $now : null]);

            $msg = ($isLocked === 'TRUE') ? "Langkah $step berhasil dikunci." : "Kunci Langkah $step berhasil dibuka.";
            $this->jsonResponse(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiToggleLock] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal mengubah status penguncian: ' . $e->getMessage()], 200);
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

            $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
            if (empty($tahunAjaranId)) {
                $stmtActiveTa = $db->prepare("SELECT id FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtActiveTa->execute([$tenantId]);
                $tahunAjaranId = $stmtActiveTa->fetchColumn();
            }

            // Ambil mapel aktif untuk semester ini
            $stmtSelected = $db->prepare("
                SELECT pcm.mapel_id, mp.nama_mata_pelajaran AS nama_mapel
                FROM pdss.pdss_config_mapel pcm
                JOIN akademik.mata_pelajaran mp ON pcm.mapel_id = mp.id
                WHERE pcm.tenant_id = ? AND (pcm.tahun_ajaran_id = ? OR pcm.tahun_ajaran_id IS NULL)
                  AND pcm.sem_$semester = true
                ORDER BY mp.nama_mata_pelajaran ASC
            ");
            $stmtSelected->execute([$tenantId, $tahunAjaranId]);
            $configMapels = $stmtSelected->fetchAll(PDO::FETCH_ASSOC);

            // Ambil data siswa aktif kelas 12
            $stmtTaName = $db->prepare("SELECT nama_tahun_ajaran FROM akademik.tahun_ajaran WHERE id = ? LIMIT 1");
            $stmtTaName->execute([$tahunAjaranId]);
            $selectedTaName = $stmtTaName->fetchColumn() ?: null;

            $students = $this->getKelas12Students($tenantId, $selectedTaName);

            $siswaIds = array_column($students, 'id');
            $mapelIds = array_column($configMapels, 'mapel_id');

            $allGrades = [];
            if (!empty($siswaIds) && !empty($mapelIds)) {
                $siswaPlaceholders = implode(',', array_fill(0, count($siswaIds), '?'));
                $mapelPlaceholders = implode(',', array_fill(0, count($mapelIds), '?'));

                $stmtGrades = $db->prepare("
                    SELECT dnr.siswa_id, dnr.mapel_id, dnr.nilai_akhir, dnr.semester, k.nama_kelas
                    FROM akademik.detail_nilai_rapor dnr
                    LEFT JOIN akademik.kelas k ON dnr.kelas_id = k.id::varchar OR dnr.kelas_id = k.nama_kelas
                    WHERE dnr.tenant_id = ?
                      AND dnr.mapel_id IN ($mapelPlaceholders)
                      AND dnr.siswa_id IN ($siswaPlaceholders)
                ");
                $stmtGrades->execute(array_merge([$tenantId], $mapelIds, $siswaIds));
                $allGrades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);
            }

            $studentGradesMap = [];
            foreach ($allGrades as $g) {
                $sid = (string)$g['siswa_id'];
                $mid = (string)$g['mapel_id'];
                $semLevel = $this->getSemesterLevel($g['nama_kelas'] ?? '', $g['semester'] ?? '');
                if ($semLevel === $semester) {
                    $studentGradesMap[$sid][$mid] = (float)$g['nilai_akhir'];
                }
            }

            $xlsxRows = [];
            $header = ['No', 'NISN', 'NIS', 'Nama Siswa', 'Kelas', 'Jurusan'];
            foreach ($configMapels as $col) {
                $header[] = $col['nama_mapel'];
            }
            $xlsxRows[] = $header;

            $no = 1;
            foreach ($students as $s) {
                $sid = (string)$s['id'];
                $row = [
                    $no++,
                    $s['nisn'],
                    $s['nis'],
                    $s['nama_lengkap'],
                    $s['nama_kelas'],
                    $s['nama_jurusan']
                ];

                foreach ($configMapels as $col) {
                    $mid = (string)$col['mapel_id'];
                    $row[] = isset($studentGradesMap[$sid][$mid]) ? $studentGradesMap[$sid][$mid] : '';
                }
                $xlsxRows[] = $row;
            }

            $filename = 'leger_nilai_pdss_semester_' . $semester . '_' . date('Ymd_His') . '.xlsx';
            if (class_exists('\Shuchkin\SimpleXLSXGen')) {
                \Shuchkin\SimpleXLSXGen::fromArray($xlsxRows)->downloadAs($filename);
                exit;
            } else {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . str_replace('.xlsx', '.csv', $filename));
                $output = fopen('php://output', 'w');
                foreach ($xlsxRows as $r) {
                    fputcsv($output, $r);
                }
                fclose($output);
                exit;
            }
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiDownloadLeger] ' . $e->getMessage());
            echo "Terjadi kesalahan sistem saat mengekspor leger: " . htmlspecialchars($e->getMessage());
        }
    }

    /**
     * API: Daftar tracking alumni (studi lanjut PTN/PTS)
     * GET /api/v1/pdss/alumni-tracks
     */
    public function apiGetAlumniTracks(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Pilih sekolah terlebih dahulu.'], 200);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    rk.id, 
                    a.siswa_id,
                    COALESCE(s.nama_lengkap, a.nama_alumni, 'Alumni') AS nama_alumni, 
                    rk.tahun_masuk, 
                    COALESCE(mk.jenis, 'Negeri') AS jenis_kampus,
                    COALESCE(mk.jenis, 'Negeri') AS jenis_campus, 
                    COALESCE(rk.jalur_masuk, 'SNBP') AS jalur_masuk, 
                    rk.nama_kampus AS universitas_nama, 
                    rk.nama_prodi AS jurusan_nama, 
                    COALESCE(rk.status_kuliah, 'Aktif') AS status
                FROM tracer.riwayat_kuliah rk
                LEFT JOIN tracer.alumni a ON rk.alumni_id = a.id
                LEFT JOIN siswa.siswa s ON a.siswa_id = s.id
                LEFT JOIN pdss.master_kampus mk ON LOWER(rk.nama_kampus) = LOWER(mk.nama_kampus)
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
            $this->jsonResponse(['success' => false, 'error' => 'Gagal memuat data tracking alumni: ' . $e->getMessage()], 200);
        }
    }

    /**
     * API: Simpan / Update tracking alumni
     * POST /api/v1/pdss/alumni-tracks
     */
    public function apiSaveAlumniTrack(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak.'], 200);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak teridenteksi.'], 200);
            return;
        }

        $input = $this->getJsonInput();
        $id = $this->sanitize($input['id'] ?? '');
        $idSiswa = $this->sanitize($input['id_siswa'] ?? '');
        $namaAlumni = $this->sanitize($input['nama_alumni'] ?? '');
        $tahunMasuk = (int)($input['tahun_masuk'] ?? 0);
        $jalurMasuk = $this->sanitize($input['jalur_masuk'] ?? '');
        $universitasNama = $this->sanitize($input['universitas_nama'] ?? '');
        $jurusanNama = $this->sanitize($input['jurusan_nama'] ?? '');
        $status = $this->sanitize($input['status'] ?? '');

        if (empty($idSiswa)) {
            $idSiswa = null;
        }

        if (empty($namaAlumni) || $tahunMasuk < 1900 || empty($universitasNama) || empty($jurusanNama)) {
            $this->jsonResponse(['success' => false, 'error' => 'Mohon lengkapi seluruh field dengan benar.'], 200);
            return;
        }

        $statusDb = ($status === 'Aktif Kuliah' || empty($status)) ? 'Aktif' : $status;

        try {
            $db = \App\Config\Database::getConnection();

            // Dapatkan atau buat entri alumni di tracer.alumni
            $alumniId = null;
            if (!empty($idSiswa)) {
                $stmtCheck = $db->prepare("SELECT id FROM tracer.alumni WHERE tenant_id = ? AND siswa_id = ? LIMIT 1");
                $stmtCheck->execute([$tenantId, $idSiswa]);
                $alumniId = $stmtCheck->fetchColumn();
            }
            if (!$alumniId) {
                $stmtCheck2 = $db->prepare("SELECT id FROM tracer.alumni WHERE tenant_id = ? AND LOWER(nama_alumni) = LOWER(?) LIMIT 1");
                $stmtCheck2->execute([$tenantId, $namaAlumni]);
                $alumniId = $stmtCheck2->fetchColumn();
            }
            if (!$alumniId) {
                $stmtNewAlumni = $db->prepare("
                    INSERT INTO tracer.alumni (id, tenant_id, siswa_id, nama_alumni, status_tracer)
                    VALUES (gen_random_uuid(), ?, ?, ?, 'Kuliah')
                    RETURNING id
                ");
                $stmtNewAlumni->execute([$tenantId, $idSiswa, $namaAlumni]);
                $alumniId = $stmtNewAlumni->fetchColumn();
            }

            if (empty($id)) {
                $stmt = $db->prepare("
                    INSERT INTO tracer.riwayat_kuliah 
                        (id, alumni_id, tenant_id, nama_kampus, nama_prodi, tahun_masuk, jalur_masuk, status_kuliah)
                    VALUES 
                        (gen_random_uuid(), ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $alumniId, $tenantId, $universitasNama, $jurusanNama, $tahunMasuk, $jalurMasuk, $statusDb
                ]);
                $msg = 'Data alumni baru berhasil ditambahkan.';
            } else {
                $stmt = $db->prepare("
                    UPDATE tracer.riwayat_kuliah 
                    SET 
                        alumni_id = ?,
                        nama_kampus = ?, 
                        nama_prodi = ?, 
                        tahun_masuk = ?, 
                        jalur_masuk = ?, 
                        status_kuliah = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND tenant_id = ?
                ");
                $stmt->execute([
                    $alumniId, $universitasNama, $jurusanNama, $tahunMasuk, $jalurMasuk, $statusDb, $id, $tenantId
                ]);
                $msg = 'Data alumni berhasil diperbarui.';
            }

            $this->jsonResponse(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiSaveAlumniTrack] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal menyimpan data alumni: ' . $e->getMessage()], 200);
        }
    }

    /**
     * API: Hapus tracking alumni
     * POST /api/v1/pdss/alumni-tracks/delete
     */
    public function apiDeleteAlumniTrack(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak.'], 200);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant ID tidak terdeteksi.'], 200);
            return;
        }

        $input = $this->getJsonInput();
        $id = $this->sanitize($input['id'] ?? '');

        if (empty($id)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID data wajib disertakan.'], 200);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("DELETE FROM tracer.riwayat_kuliah WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenantId]);

            $this->jsonResponse(['success' => true, 'message' => 'Data tracking alumni berhasil dihapus.']);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiDeleteAlumniTrack] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal menghapus data alumni.'], 200);
        }
    }

    /**
     * API: Mencari siswa
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
                FROM siswa.siswa 
                WHERE tenant_id = ? 
            ";
            $params = [$tenantId];

            if (!empty($query)) {
                $sql .= " AND (nama_lengkap ILIKE ? OR nisn ILIKE ? OR nis ILIKE ?)";
                $searchVal = '%' . $query . '%';
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
                FROM pdss.target_kampus
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

        try {
            $db = \App\Config\Database::getConnection();

            if (empty($id)) {
                $stmt = $db->prepare("
                    INSERT INTO pdss.target_kampus (id, tenant_id, nama_kampus, jenis_kampus, kuota_target)
                    VALUES (gen_random_uuid(), ?, ?, ?, ?)
                ");
                $stmt->execute([$tenantId, $namaKampus, $jenisKampus, $kuotaTarget]);
                $msg = 'Target kampus baru berhasil disimpan.';
            } else {
                $stmt = $db->prepare("
                    UPDATE pdss.target_kampus 
                    SET nama_kampus = ?, jenis_kampus = ?, kuota_target = ?, updated_at = CURRENT_TIMESTAMP
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
            $stmt = $db->prepare("DELETE FROM pdss.target_kampus WHERE id = ? AND tenant_id = ?");
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
                INSERT INTO pdss.target_kampus (id, tenant_id, nama_kampus, jenis_kampus, kuota_target) 
                VALUES (gen_random_uuid(), ?, ?, ?, ?)
            ");

            foreach ($defaultList as $item) {
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pdss.target_kampus WHERE tenant_id = ? AND nama_kampus = ?");
                $stmtCheck->execute([$tenantId, $item[0]]);
                if ((int)$stmtCheck->fetchColumn() > 0) {
                    continue;
                }
                $stmt->execute([$tenantId, $item[0], $item[1], $item[2]]);
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
                $stmtTA = $db->prepare("SELECT id FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }

            $stmt = $db->prepare("SELECT no_simulasi, is_open FROM pdss.simulasi_setting WHERE tenant_id = ? AND (tahun_ajaran_id = ? OR tahun_ajaran_id IS NULL)");
            $stmt->execute([$tenantId, $tahunAjaranId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $settings = [1 => ['is_open' => 0, 'is_locked' => 0], 2 => ['is_open' => 0, 'is_locked' => 0], 3 => ['is_open' => 0, 'is_locked' => 0]];
            foreach ($rows as $r) {
                $settings[(int)$r['no_simulasi']] = [
                    'is_open' => (int)$r['is_open'],
                    'is_locked' => 0
                ];
            }
            $this->jsonResponse(['success' => true, 'data' => $settings, 'tahun_ajaran_id' => $tahunAjaranId]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetSimulasiSetting] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal memuat setting simulasi.'], 500);
        }
    }

    /**
     * API: Toggle buka/tutup fase simulasi
     * POST /api/v1/pdss/simulasi/setting
     */
    public function apiToggleSimulasiSetting(): void {
        if (!$this->canWrite()) { $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak.'], 200); return; }
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['success' => false, 'error' => 'Tenant tidak terdeteksi.'], 200); return; }

        $input = $this->getJsonInput();
        $noSimulasi  = (int)($input['no_simulasi'] ?? 0);
        $action      = $this->sanitize($input['action'] ?? '');
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? null;

        if (!in_array($noSimulasi, [1,2,3]) || !in_array($action, ['open','close','lock'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Parameter tidak valid.'], 200); return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }

            $isOpen = ($action === 'open') ? 'TRUE' : 'FALSE';
            $db->prepare("
                INSERT INTO pdss.simulasi_setting (id, tenant_id, tahun_ajaran_id, no_simulasi, is_open) 
                VALUES (gen_random_uuid(), ?, ?, ?, {$isOpen}) 
                ON CONFLICT (tenant_id, COALESCE(tahun_ajaran_id, '00000000-0000-0000-0000-000000000000'), no_simulasi) 
                DO UPDATE SET is_open = {$isOpen}, updated_at = CURRENT_TIMESTAMP
            ")->execute([$tenantId, $tahunAjaranId, $noSimulasi]);

            $this->jsonResponse(['success' => true, 'message' => "Simulasi $noSimulasi berhasil diubah statusnya."]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiToggleSimulasiSetting] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengubah setting simulasi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Ambil daftar pilihan simulasi kampus
     * GET /api/v1/pdss/simulasi
     */
    public function apiGetSimulasi(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400); return; }

        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
        $noSimulasi    = (int)($_GET['no_simulasi'] ?? 1);
        if (!in_array($noSimulasi, [1,2,3])) $noSimulasi = 1;

        try {
            $db = \App\Config\Database::getConnection();
            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }

            // Ambil data kesiapan & evaluasi lengkap untuk mendapatkan daftar siswa yang lolos eligible
            $kesiapanResult = $this->computeKesiapanData($tenantId, $tahunAjaranId);
            $allStudents = $kesiapanResult['data'] ?? [];

            // Filter khusus siswa yang lolos eligible
            $eligibleStudents = array_values(array_filter($allStudents, function($s) {
                return !empty($s['is_eligible']);
            }));

            // Urutkan siswa eligible langsung berdasarkan nilai rerata DESC
            usort($eligibleStudents, function($a, $b) {
                $scoreA = (float)($a['rata_rata'] ?? $a['nilai_rata_rata'] ?? 0);
                $scoreB = (float)($b['rata_rata'] ?? $b['nilai_rata_rata'] ?? 0);
                if ($scoreB !== $scoreA) {
                    return $scoreB <=> $scoreA;
                }
                return strcmp($a['nama_lengkap'] ?? '', $b['nama_lengkap'] ?? '');
            });

            // Ambil pilihan kampus siswa (pastikan hanya data terbaru per siswa dan nomor pilihan)
            $stmtPilihan = $db->prepare("
                SELECT DISTINCT ON (pk.siswa_id, pk.no_pilihan) pk.*, mk.nama_kampus, COALESCE(mp.program_studi, mp.nama_prodi, '') AS nama_prodi
                FROM pdss.pilihan_kampus pk
                LEFT JOIN pdss.master_kampus mk ON pk.kampus_id = mk.id
                LEFT JOIN pdss.master_kampus_prodi mp ON pk.prodi_id = mp.id
                WHERE pk.tenant_id = ? AND pk.no_simulasi = ? AND (pk.tahun_ajaran_id = ? OR pk.tahun_ajaran_id IS NULL)
                ORDER BY pk.siswa_id, pk.no_pilihan, pk.created_at DESC
            ");
            $stmtPilihan->execute([$tenantId, $noSimulasi, $tahunAjaranId]);
            $pilihanRows = $stmtPilihan->fetchAll(PDO::FETCH_ASSOC);

            $pilihanIndexed = [];
            foreach ($pilihanRows as $pr) {
                $sid = (string)$pr['siswa_id'];
                $slot = (int)$pr['no_pilihan'];
                $pilihanIndexed[$sid][$slot] = $pr;
            }

            // Buat lookup siswa eligible berdasarkan ID untuk detail analisis konflik
            $studentLookup = [];
            $rankNum = 1;
            foreach ($eligibleStudents as $s) {
                $sid = (string)$s['id'];
                $studentLookup[$sid] = [
                    'siswa_id' => $sid,
                    'nama_lengkap' => $s['nama_lengkap'],
                    'nisn' => $s['nisn'],
                    'nis' => $s['nis'],
                    'nama_kelas' => $s['nama_kelas'],
                    'nama_jurusan' => $s['nama_jurusan'],
                    'rank_eligible' => $rankNum++,
                    'rata_rata' => number_format((float)($s['rata_rata'] ?? $s['nilai_rata_rata'] ?? 0), 2),
                    'nilai_numeric' => (float)($s['rata_rata'] ?? $s['nilai_rata_rata'] ?? 0)
                ];
            }

            // Hitung konflik pilihan prodi di satu sekolah beserta daftar siswa bersangkutan
            $pilihan1Counts = [];
            $pilihan2Counts = [];
            $prodiStudentsMap = [];
            $seenStudentInProdi = [];
            foreach ($pilihanRows as $pr) {
                if (!empty($pr['prodi_id'])) {
                    $pid = (string)$pr['prodi_id'];
                    $sid = (string)$pr['siswa_id'];
                    $slot = (int)$pr['no_pilihan'];
                    if ($slot === 1) $pilihan1Counts[$pid] = ($pilihan1Counts[$pid] ?? 0) + 1;
                    if ($slot === 2) $pilihan2Counts[$pid] = ($pilihan2Counts[$pid] ?? 0) + 1;

                    if (isset($studentLookup[$sid]) && empty($seenStudentInProdi[$pid][$sid])) {
                        $seenStudentInProdi[$pid][$sid] = true;
                        $info = $studentLookup[$sid];
                        $info['no_pilihan'] = $slot;
                        $info['kampus_nama'] = $pr['nama_kampus'];
                        $info['prodi_nama'] = $pr['nama_prodi'];
                        $prodiStudentsMap[$pid][] = $info;
                    }
                }
            }

            // Urutkan siswa yang memilih prodi yang sama:
            // 1. Pilihan 1 adalah Prioritas Utama SNBP dibanding Pilihan 2
            // 2. Di antara sesama tingkat pilihan, diurutkan berdasarkan Nilai Rerata Tertinggi (DESC)
            foreach ($prodiStudentsMap as $pid => &$pStudents) {
                usort($pStudents, function($a, $b) {
                    // Prioritas 1: No Pilihan (Pilihan 1 selalu diprioritaskan di atas Pilihan 2)
                    if ($a['no_pilihan'] !== $b['no_pilihan']) {
                        return $a['no_pilihan'] <=> $b['no_pilihan'];
                    }
                    // Prioritas 2: Nilai Rapor Tertinggi
                    if ($b['nilai_numeric'] != $a['nilai_numeric']) {
                        return ($b['nilai_numeric'] > $a['nilai_numeric']) ? 1 : -1;
                    }
                    return $a['rank_eligible'] <=> $b['rank_eligible'];
                });
            }
            unset($pStudents);

            $output = [];
            $displayRank = 1;
            foreach ($eligibleStudents as $s) {
                $sid = (string)$s['id'];
                $p1 = $pilihanIndexed[$sid][1] ?? null;
                $p2 = $pilihanIndexed[$sid][2] ?? null;

                $pid1 = $p1['prodi_id'] ?? null;
                $pid2 = $p2['prodi_id'] ?? null;
                $isKonflik1 = !empty($pid1) && ($pilihan1Counts[$pid1] ?? 0) > 1;
                $isKonflik2 = !empty($pid2) && ($pilihan2Counts[$pid2] ?? 0) > 1;

                $conflicts1 = ($isKonflik1 && !empty($pid1)) ? ($prodiStudentsMap[$pid1] ?? []) : [];
                $conflicts2 = ($isKonflik2 && !empty($pid2)) ? ($prodiStudentsMap[$pid2] ?? []) : [];

                $currentRank = $displayRank++;
                $output[] = [
                    'siswa_id' => $sid,
                    'nama_lengkap' => $s['nama_lengkap'],
                    'nisn' => $s['nisn'],
                    'nis' => $s['nis'],
                    'nama_kelas' => $s['nama_kelas'],
                    'nama_jurusan' => $s['nama_jurusan'],
                    'kode_jurusan' => $s['kode_jurusan'] ?? $s['nama_jurusan'],
                    'rank_eligible' => $currentRank,
                    'rank_sekolah' => $currentRank,
                    'rata_rata' => number_format((float)($s['rata_rata'] ?? $s['nilai_rata_rata'] ?? 0), 2),
                    'is_eligible' => true,
                    'status_eligible' => $s['status_eligible'] ?? 'auto',
                    'sudah_isi' => ($p1 !== null || $p2 !== null),
                    'kampus_id_1' => $p1['kampus_id'] ?? null,
                    'prodi_id_1' => $p1['prodi_id'] ?? null,
                    'kampus_nama_1' => $p1['nama_kampus'] ?? null,
                    'prodi_nama_1' => $p1['nama_prodi'] ?? null,
                    'kampus_id_2' => $p2['kampus_id'] ?? null,
                    'prodi_id_2' => $p2['prodi_id'] ?? null,
                    'kampus_nama_2' => $p2['nama_kampus'] ?? null,
                    'prodi_nama_2' => $p2['nama_prodi'] ?? null,
                    'status' => $p1['status'] ?? 'draft',
                    'is_konflik_1' => $isKonflik1,
                    'konflik_info_1' => $isKonflik1 ? ($pilihan1Counts[$pid1] . ' siswa memilih prodi ini') : null,
                    'konflik_detail_1' => $conflicts1,
                    'is_konflik_2' => $isKonflik2,
                    'konflik_info_2' => $isKonflik2 ? ($pilihan2Counts[$pid2] . ' siswa memilih prodi ini') : null,
                    'konflik_detail_2' => $conflicts2
                ];
            }

            $totalEligible = count($output);
            $sudahIsi = count(array_filter($output, fn($r) => $r['sudah_isi']));
            $belumIsi = $totalEligible - $sudahIsi;
            $totalKonflik = count(array_filter($output, fn($r) => $r['is_konflik_1'] || $r['is_konflik_2']));

            $this->jsonResponse([
                'success' => true,
                'data' => $output,
                'stats' => [
                    'total_eligible' => $totalEligible,
                    'sudah_isi' => $sudahIsi,
                    'belum_isi' => $belumIsi,
                    'total_konflik' => $totalKonflik
                ],
                'tahun_ajaran_id' => $tahunAjaranId
            ]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data simulasi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Simpan pilihan simulasi seorang siswa
     * POST /api/v1/pdss/simulasi
     */
    public function apiSaveSimulasi(): void {
        if (!$this->canWrite()) { $this->jsonResponse(['error' => 'Akses ditolak.'], 403); return; }
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400); return; }

        $input = $this->getJsonInput();
        $siswaId = $this->sanitize($input['siswa_id'] ?? '');
        $tahunAjaranId = $input['tahun_ajaran_id'] ?? null;
        $noSimulasi = (int)($input['no_simulasi'] ?? 1);
        $kampusId1 = $this->sanitize($input['kampus_id_1'] ?? '');
        $prodiId1 = $this->sanitize($input['prodi_id_1'] ?? '');
        $kampusId2 = $this->sanitize($input['kampus_id_2'] ?? '');
        $prodiId2 = $this->sanitize($input['prodi_id_2'] ?? '');

        if (empty($siswaId) || !in_array($noSimulasi, [1, 2, 3])) {
            $this->jsonResponse(['error' => 'Data tidak lengkap.'], 422); return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $db->beginTransaction();

            // Hapus pilihan sebelumnya untuk siswa ini pada nomor simulasi aktif agar tidak terjadi duplikasi data
            $stmtDel = $db->prepare("DELETE FROM pdss.pilihan_kampus WHERE tenant_id = ? AND siswa_id = ? AND no_simulasi = ?");
            $stmtDel->execute([$tenantId, $siswaId, $noSimulasi]);

            // Pilihan 1
            if (!empty($kampusId1)) {
                $stmt1 = $db->prepare("
                    INSERT INTO pdss.pilihan_kampus (id, tenant_id, siswa_id, tahun_ajaran_id, no_simulasi, no_pilihan, kampus_id, prodi_id, status)
                    VALUES (gen_random_uuid(), ?, ?, ?, ?, 1, ?, ?, 'submitted')
                ");
                $stmt1->execute([$tenantId, $siswaId, $tahunAjaranId, $noSimulasi, $kampusId1, $prodiId1 ?: null]);
            }

            // Pilihan 2
            if (!empty($kampusId2)) {
                $stmt2 = $db->prepare("
                    INSERT INTO pdss.pilihan_kampus (id, tenant_id, siswa_id, tahun_ajaran_id, no_simulasi, no_pilihan, kampus_id, prodi_id, status)
                    VALUES (gen_random_uuid(), ?, ?, ?, ?, 2, ?, ?, 'submitted')
                ");
                $stmt2->execute([$tenantId, $siswaId, $tahunAjaranId, $noSimulasi, $kampusId2, $prodiId2 ?: null]);
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Pilihan simulasi berhasil disimpan.']);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('[PDSSController::apiSaveSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan pilihan simulasi.'], 500);
        }
    }

    /**
     * API: Hapus pilihan simulasi seorang siswa
     * POST /api/v1/pdss/simulasi/delete
     */
    public function apiDeleteSimulasi(): void {
        if (!$this->canWrite()) { $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak.'], 200); return; }
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['success' => false, 'error' => 'Tenant tidak terdeteksi.'], 200); return; }

        $input = $this->getJsonInput();
        $siswaId = $this->sanitize($input['siswa_id'] ?? '');
        $noSimulasi = (int)($input['no_simulasi'] ?? 0);

        if (empty($siswaId) || !in_array($noSimulasi, [1, 2, 3])) {
            $this->jsonResponse(['success' => false, 'error' => 'Parameter tidak valid.'], 200); return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $db->prepare("DELETE FROM pdss.pilihan_kampus WHERE tenant_id = ? AND siswa_id = ? AND no_simulasi = ?")
               ->execute([$tenantId, $siswaId, $noSimulasi]);
            $this->jsonResponse(['success' => true, 'message' => 'Pilihan simulasi berhasil dihapus.']);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiDeleteSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal menghapus pilihan simulasi.'], 200);
        }
    }

    /**
     * API: Upload Berkas Bukti Simulasi SNBP
     * POST /api/v1/pdss/simulasi/upload-bukti
     */
    public function apiUploadBuktiSimulasi(): void {
        if (!$this->canWrite()) {
            $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak.'], 200);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant tidak terdeteksi.'], 200);
            return;
        }

        $siswaId = $_POST['siswa_id'] ?? '';
        $tahunAjaranId = $_POST['tahun_ajaran_id'] ?? null;
        $noSimulasi = (int)($_POST['no_simulasi'] ?? 3);

        if (empty($siswaId) || empty($_FILES['bukti_file'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Pilih file bukti yang akan diunggah.'], 200);
            return;
        }

        $file = $_FILES['bukti_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['success' => false, 'error' => 'Terjadi kesalahan saat mengunggah file.'], 200);
            return;
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            $this->jsonResponse(['success' => false, 'error' => 'Ukuran file maksimal 10MB.'], 200);
            return;
        }

        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            $this->jsonResponse(['success' => false, 'error' => 'Format file tidak didukung. Hanya PDF, JPG, PNG, WEBP.'], 200);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }

            // Simpan file via FileStorage
            $storedRelPath = FileStorage::store($file['tmp_name'], 'pdss', $tenantId, $siswaId, 'doc');
            if (!$storedRelPath) {
                // Fallback manual upload directory
                $uploadDir = dirname(__DIR__, 4) . "/storage/app/public/pdss/{$tenantId}/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'bukti_sim_' . $noSimulasi . '_' . $siswaId . '_' . time() . '.' . $ext;
                $targetAbs = $uploadDir . $filename;
                if (!move_uploaded_file($file['tmp_name'], $targetAbs)) {
                    $this->jsonResponse(['success' => false, 'error' => 'Gagal menyimpan file ke penyimpanan server.'], 200);
                    return;
                }
                $storedRelPath = "storage/app/public/pdss/{$tenantId}/" . $filename;
            }

            // Simpan ke database pdss.pdss_bukti_simulasi
            $stmt = $db->prepare("
                INSERT INTO pdss.pdss_bukti_simulasi 
                    (id, tenant_id, siswa_id, tahun_ajaran_id, no_simulasi, nama_file, path_file, ukuran_file, mime_type)
                VALUES 
                    (gen_random_uuid(), ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $tenantId,
                $siswaId,
                $tahunAjaranId,
                $noSimulasi,
                $file['name'],
                $storedRelPath,
                $file['size'],
                $file['type'] ?? 'application/octet-stream'
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Berkas bukti simulasi berhasil diunggah.']);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiUploadBuktiSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal mengunggah bukti: ' . $e->getMessage()], 200);
        }
    }

    /**
     * API: Ambil Daftar Berkas Bukti Simulasi
     * GET /api/v1/pdss/simulasi/bukti
     */
    public function apiGetBuktiSimulasi(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => false, 'error' => 'Tenant tidak terdeteksi.'], 200);
            return;
        }

        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? null;
        $noSimulasi = (int)($_GET['no_simulasi'] ?? 3);

        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("
                SELECT bs.*, s.nama_lengkap, s.nisn 
                FROM pdss.pdss_bukti_simulasi bs
                LEFT JOIN siswa.siswa s ON bs.siswa_id = s.id
                WHERE bs.tenant_id = ? AND bs.no_simulasi = ? AND (bs.tahun_ajaran_id = ? OR bs.tahun_ajaran_id IS NULL)
                ORDER BY bs.uploaded_at DESC
            ");
            $stmt->execute([$tenantId, $noSimulasi, $tahunAjaranId]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'data' => $list]);
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiGetBuktiSimulasi] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal memuat bukti simulasi.'], 200);
        }
    }

    /**
     * API: Export Rekap Pilihan Simulasi Kampus (Excel/CSV)
     * GET /api/v1/pdss/simulasi/export
     */
    public function apiExportSimulasi(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            echo "Akses ditolak atau tenant tidak terdeteksi.";
            return;
        }

        $tahunAjaranId = $_GET['tahun_ajaran_id'] ?? '';
        $noSimulasi = (int)($_GET['no_simulasi'] ?? 1);
        if (!in_array($noSimulasi, [1, 2, 3])) $noSimulasi = 1;

        try {
            $db = \App\Config\Database::getConnection();
            if (empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT id FROM akademik.tahun_ajaran WHERE tenant_id = ? ORDER BY is_active DESC, id DESC LIMIT 1");
                $stmtTA->execute([$tenantId]);
                $tahunAjaranId = $stmtTA->fetchColumn();
            }

            // Ambil data kesiapan & evaluasi lengkap untuk mendapatkan daftar siswa yang lolos eligible
            $kesiapanResult = $this->computeKesiapanData($tenantId, $tahunAjaranId);
            $allStudents = $kesiapanResult['data'] ?? [];

            // Filter khusus siswa yang lolos eligible
            $eligibleStudents = array_values(array_filter($allStudents, function($s) {
                return !empty($s['is_eligible']);
            }));

            // Urutkan siswa eligible langsung berdasarkan nilai rerata DESC
            usort($eligibleStudents, function($a, $b) {
                $scoreA = (float)($a['rata_rata'] ?? $a['nilai_rata_rata'] ?? 0);
                $scoreB = (float)($b['rata_rata'] ?? $b['nilai_rata_rata'] ?? 0);
                if ($scoreB !== $scoreA) {
                    return $scoreB <=> $scoreA;
                }
                return strcmp($a['nama_lengkap'] ?? '', $b['nama_lengkap'] ?? '');
            });

            // Ambil pilihan
            $stmtPilihan = $db->prepare("
                SELECT pk.*, mk.nama_kampus, COALESCE(mp.program_studi, mp.nama_prodi, '') AS nama_prodi
                FROM pdss.pilihan_kampus pk
                LEFT JOIN pdss.master_kampus mk ON pk.kampus_id = mk.id
                LEFT JOIN pdss.master_kampus_prodi mp ON pk.prodi_id = mp.id
                WHERE pk.tenant_id = ? AND pk.no_simulasi = ? AND (pk.tahun_ajaran_id = ? OR pk.tahun_ajaran_id IS NULL)
            ");
            $stmtPilihan->execute([$tenantId, $noSimulasi, $tahunAjaranId]);
            $pilihanRows = $stmtPilihan->fetchAll(PDO::FETCH_ASSOC);

            $pilihanIndexed = [];
            foreach ($pilihanRows as $pr) {
                $sid = (string)$pr['siswa_id'];
                $slot = (int)$pr['no_pilihan'];
                $pilihanIndexed[$sid][$slot] = $pr;
            }

            $xlsxRows = [];
            $header = ['No', 'Rank', 'NISN', 'NIS', 'Nama Siswa', 'Kelas', 'Jurusan', 'Rata-rata', 'Pilihan 1 - Kampus', 'Pilihan 1 - Prodi', 'Pilihan 2 - Kampus', 'Pilihan 2 - Prodi', 'Status'];
            $xlsxRows[] = $header;

            $no = 1;
            foreach ($eligibleStudents as $s) {
                $sid = (string)$s['id'];
                $p1 = $pilihanIndexed[$sid][1] ?? null;
                $p2 = $pilihanIndexed[$sid][2] ?? null;

                $currentRank = $no++;
                $xlsxRows[] = [
                    $currentRank,
                    $currentRank,
                    $s['nisn'],
                    $s['nis'],
                    $s['nama_lengkap'],
                    $s['nama_kelas'],
                    $s['nama_jurusan'],
                    number_format((float)($s['rata_rata'] ?? $s['nilai_rata_rata'] ?? 0), 2),
                    $p1['nama_kampus'] ?? '-',
                    $p1['nama_prodi'] ?? '-',
                    $p2['nama_kampus'] ?? '-',
                    $p2['nama_prodi'] ?? '-',
                    ($p1 !== null || $p2 !== null) ? 'Sudah Memilih' : 'Belum Memilih'
                ];
            }

            $filename = 'rekap_simulasi_' . $noSimulasi . '_' . date('Ymd_His') . '.xlsx';
            if (class_exists('\Shuchkin\SimpleXLSXGen')) {
                \Shuchkin\SimpleXLSXGen::fromArray($xlsxRows)->downloadAs($filename);
                exit;
            } else {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . str_replace('.xlsx', '.csv', $filename));
                $output = fopen('php://output', 'w');
                foreach ($xlsxRows as $r) {
                    fputcsv($output, $r);
                }
                fclose($output);
                exit;
            }
        } catch (\Throwable $e) {
            error_log('[PDSSController::apiExportSimulasi] ' . $e->getMessage());
            echo "Terjadi kesalahan sistem saat mengekspor: " . htmlspecialchars($e->getMessage());
        }
    }

    /**
     * Helper sanitasi string
     */
    private function sanitize(mixed $val): string {
        if (is_null($val) || is_array($val) || is_object($val)) {
            return '';
        }
        return htmlspecialchars(strip_tags(trim((string)$val)), ENT_QUOTES, 'UTF-8');
    }
}
