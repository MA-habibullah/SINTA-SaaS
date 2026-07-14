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

        // Auto-create pdss_config_mapel table if not exists
        try {
            $db = \App\Config\Database::getConnection();
            $db->exec("CREATE TABLE IF NOT EXISTS `pdss_config_mapel` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `tenant_id` CHAR(36) NOT NULL,
                `mapel_id` INT UNSIGNED NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_tenant_mapel` (`tenant_id`, `mapel_id`),
                CONSTRAINT `fk_pdss_mapel_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_pdss_mapel_id` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB;");
        } catch (\Throwable $e) {
            error_log('[PDSSController::__construct] failed to create pdss_config_mapel: ' . $e->getMessage());
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
    public function apiGetPdssMapels(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // Ambil semua mapel aktif
            $stmtAll = $db->prepare("SELECT id, kode_mapel, nama_mapel FROM mata_pelajaran WHERE tenant_id = ? AND is_active = 1 AND deleted_at IS NULL ORDER BY nama_mapel ASC");
            $stmtAll->execute([$tenantId]);
            $allMapels = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

            // Ambil mapel terpilih untuk PDSS
            $stmtSelected = $db->prepare("SELECT mapel_id FROM pdss_config_mapel WHERE tenant_id = ?");
            $stmtSelected->execute([$tenantId]);
            $selectedMapels = $stmtSelected->fetchAll(PDO::FETCH_COLUMN);

            foreach ($allMapels as &$m) {
                $m['is_selected'] = in_array((int)$m['id'], array_map('intval', $selectedMapels), true);
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
        $mapelIds = $input['mapel_ids'] ?? [];

        if (!is_array($mapelIds)) {
            $this->jsonResponse(['error' => 'Data mapel tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $db->beginTransaction();

            // 1. Hapus konfigurasi lama
            $stmtDel = $db->prepare("DELETE FROM pdss_config_mapel WHERE tenant_id = ?");
            $stmtDel->execute([$tenantId]);

            // 2. Insert yang baru
            if (!empty($mapelIds)) {
                $stmtIns = $db->prepare("INSERT INTO pdss_config_mapel (tenant_id, mapel_id) VALUES (?, ?)");
                foreach ($mapelIds as $mid) {
                    $stmtIns->execute([$tenantId, (int)$mid]);
                }
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Konfigurasi mata pelajaran PDSS berhasil disimpan.']);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
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
    public function apiGetKesiapan(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // 1. Ambil akreditasi sekolah
            $stmtAcc = $db->prepare("SELECT akreditasi FROM tenants WHERE id = ? LIMIT 1");
            $stmtAcc->execute([$tenantId]);
            $accreditation = $stmtAcc->fetchColumn() ?: 'A (Unggul)';

            // 2. Ambil mapel terpilih untuk PDSS
            $stmtSelected = $db->prepare("SELECT mapel_id FROM pdss_config_mapel WHERE tenant_id = ?");
            $stmtSelected->execute([$tenantId]);
            $pdssMapelIds = $stmtSelected->fetchAll(PDO::FETCH_COLUMN);

            if (empty($pdssMapelIds)) {
                $this->jsonResponse([
                    'success' => true,
                    'accreditation' => $accreditation,
                    'mapel_not_configured' => true,
                    'data' => [],
                    'total_configured_mapels' => 0
                ]);
                return;
            }

            // 3. Ambil data siswa aktif kelas 12 dengan rekap nilai rata-rata 5 semester untuk mapel terpilih
            $placeholders = implode(',', array_fill(0, count($pdssMapelIds), '?'));
            $sql = "
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
                    COALESCE(AVG(g.nilai_akhir), 0) AS rata_rata,
                    COUNT(g.nilai_akhir) AS jumlah_nilai
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN jurusan j ON s.id_jurusan = j.id
                LEFT JOIN (
                    SELECT dnr.siswa_id, dnr.nilai_akhir, dnr.mapel_id
                    FROM detail_nilai_rapor dnr
                    JOIN kelas k_grade ON dnr.kelas_id = k_grade.id
                    WHERE dnr.tenant_id = ?
                      AND dnr.deleted_at IS NULL
                      AND dnr.mapel_id IN ($placeholders)
                      AND (
                        ( (k_grade.nama_kelas LIKE '%12%' OR k_grade.nama_kelas LIKE '%XII%') AND dnr.semester = 'Ganjil' )
                        OR ( (k_grade.nama_kelas LIKE '%11%' OR k_grade.nama_kelas LIKE '%XI%') AND dnr.semester IN ('Ganjil', 'Genap') )
                        OR ( (k_grade.nama_kelas LIKE '%10%' OR k_grade.nama_kelas LIKE '%X%') AND dnr.semester IN ('Ganjil', 'Genap') )
                      )
                ) g ON s.id = g.siswa_id
                WHERE s.tenant_id = ?
                  AND s.status = 'Aktif'
                  AND s.deleted_at IS NULL
                  AND (k.nama_kelas LIKE '%12%' OR k.nama_kelas LIKE '%XII%')
                GROUP BY s.id, s.nama_lengkap, s.nisn, s.nis, s.id_jurusan, k.nama_kelas, s.id_kelas, j.nama_jurusan, j.kode_jurusan
                ORDER BY j.nama_jurusan ASC, rata_rata DESC, s.nama_lengkap ASC
            ";

            $stmt = $db->prepare($sql);
            $params = array_merge([$tenantId], $pdssMapelIds, [$tenantId]);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format tipe data hasil query
            foreach ($students as &$s) {
                $s['rata_rata'] = (float)$s['rata_rata'];
                $s['jumlah_nilai'] = (int)$s['jumlah_nilai'];
            }
            unset($s);

            $this->jsonResponse([
                'success' => true,
                'accreditation' => $accreditation,
                'mapel_not_configured' => false,
                'total_configured_mapels' => count($pdssMapelIds),
                'data' => $students
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
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[PDSSController::apiSeedTargetKampus] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal melakukan seeding data target kampus.'], 500);
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
}
