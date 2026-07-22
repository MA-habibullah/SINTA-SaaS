<?php

namespace App\Controllers;

use App\Core\SessionManager;
use App\Core\FileCompressor;

/**
 * BimbinganKonselingController (BKController)
 *
 * Menangani seluruh modul Bimbingan Konseling:
 * - Tab 1: Dashboard Monitoring KPI
 * - Tab 2: Penjurusan Mandiri
 * - Tab 3: Tracer Study BK View
 * - Tab 4: Kesiapan PDSS (SNBP Eligible)
 * - Tab 5: Rekam Kasus & Jurnal BK
 *
 * SECURITY MODEL:
 * - Semua query dikunci oleh tenant_id dari session (tidak pernah dari input user).
 * - Role gate: hanya 'super_admin', 'operator_sekolah', 'guru_bk' yang boleh masuk.
 * - Role 'siswa' dan role lain diblokir dengan 403.
 */
class BKController extends BaseController {

    /** Daftar role yang diizinkan mengakses modul BK */
    private const ALLOWED_ROLES = ['super_admin', 'operator_sekolah', 'guru_bk'];

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->guardRole();
    }

    // =========================================================================
    // ROLE GATE — Blokir akses jika bukan role yang diizinkan
    // =========================================================================
    // =========================================================================
    // ROLE GATE — Blokir akses jika bukan role yang diizinkan
    // =========================================================================
    private function guardRole(): void {
        if (!\App\Core\RouteGuard::checkCurrent(self::ALLOWED_ROLES)) {
            $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/SINTA-SaaS/api/');
            if ($isApi) {
                $this->jsonResponse(['error' => 'Akses ditolak. Modul ini hanya untuk Guru BK, Admin Sekolah, dan Super Admin.'], 403);
            }
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 Akses Ditolak</title>'
               . '<link href="/SINTA-SaaS/assets/css/bootstrap.min.css" rel="stylesheet">'
               . '<link href="/SINTA-SaaS/assets/css/bootstrap-icons.css" rel="stylesheet">'
               . '</head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">'
               . '<div class="card shadow-sm p-5 text-center" style="max-width:480px;">'
               . '<i class="bi bi-shield-x text-danger fs-1 mb-3 d-block"></i>'
               . '<h4 class="fw-bold mb-2">403 — Akses Ditolak</h4>'
               . '<p class="text-muted">Modul Bimbingan Konseling hanya dapat diakses oleh <strong>Guru BK</strong>, <strong>Admin Sekolah</strong>, atau <strong>Super Admin</strong>.</p>'
               . '<a href="/SINTA-SaaS/dashboard" class="btn btn-primary mt-2 rounded-3">Kembali ke Dashboard</a>'
               . '</div></body></html>';
            exit;
        }
    }

    // =========================================================================
    // HELPER: Ambil tenant_id yang aman
    // Super Admin bisa pass ?tenant_id= via GET, tapi harus divalidasi keberadaannya di DB.
    // Admin Sekolah / Guru BK selalu dikunci ke tenant dari session.
    // =========================================================================
    private function getSecureTenantId(): ?string {
        $roles    = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $tenantId = SessionManager::getTenantId();

        if (in_array('super_admin', $roles)) {
            $tid = $_GET['tenant_id'] ?? $_POST['tenant_id'] ?? null;
            if (empty($tid)) {
                $body = $this->getJsonInput();
                $tid  = $body['tenant_id'] ?? null;
            }

            if (!empty($tid)) {
                try {
                    $db   = \App\Config\Database::getConnection();
                    $stmt = $db->prepare("SELECT id FROM tenants WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                    $stmt->execute([$tid]);
                    $valid = $stmt->fetchColumn();
                    return $valid ?: null;
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        return $tenantId; // NULL hanya untuk super_admin tanpa filter
    }

    // =========================================================================
    // PAGE: Halaman Utama BK (view master_bk)
    // GET /bk
    // =========================================================================
    public function layanan(): void {
        $this->_renderBkHub('layanan');
    }

    public function akademik(): void {
        $this->_renderBkHub('akademik');
    }

    public function alumni(): void {
        $this->_renderBkHub('alumni');
    }

    private function _renderBkHub(string $activeGroup): void {
        $tenantId  = $this->getSecureTenantId();
        $roles     = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        
        $role = 'guru_bk';
        foreach (['super_admin', 'operator_sekolah', 'guru_bk'] as $allowed) {
            if (in_array($allowed, $roles)) {
                $role = $allowed;
                break;
            }
        }
        $userNama  = $_SESSION['nama_lengkap'] ?? '';

        $tenantList = [];
        if ($role === 'super_admin') {
            try {
                $db         = \App\Config\Database::getConnection();
                $tenantList = $db->query("SELECT id, nama_sekolah FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC")
                                 ->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {}
        }

        $tahunAjaranList = [];
        if ($tenantId) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->prepare("SELECT id, tahun_ajaran FROM tahun_ajaran WHERE tenant_id = ? AND is_active = 1 AND deleted_at IS NULL ORDER BY tahun_ajaran DESC");
                $stmt->execute([$tenantId]);
                $tahunAjaranList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {}
        }

        $this->render('bk/hub', [
            'title'             => 'Bimbingan Konseling',
            'user_role'         => $role,
            'can_write'         => in_array($role, ['guru_bk', 'operator_sekolah', 'super_admin']),
            'user_nama'         => $userNama,
            'tenant_id'         => $tenantId,
            'tenant_list'       => $tenantList,
            'tahun_ajaran_list' => $tahunAjaranList,
            'active_group'      => $activeGroup,
        ]);
    }

    // =========================================================================
    // API: Search Siswa untuk Rekam Kasus (dengan kelas & NISN)
    // GET /api/v1/bk/siswa?q=...&kelas_id=...&limit=10
    //
    // SECURITY: Filter ketat tenant_id (dari session, bukan input) + status='Aktif'
    // =========================================================================
    public function apiSiswaSearch(): void {
        $tenantId = $this->getSecureTenantId();
        $db       = \App\Config\Database::getConnection();

        $q       = $this->sanitize($_GET['q'] ?? '');
        $kelasId = (int)($_GET['kelas_id'] ?? 0);
        $limit   = min((int)($_GET['limit'] ?? 10), 30);

        // Tidak ada query dan tidak ada filter kelas — kembalikan array kosong
        if (strlen($q) < 1 && $kelasId === 0) {
            $this->jsonResponse(['success' => true, 'data' => []]);
            return;
        }

        try {
            $params = [];
            $sql = "SELECT
                        s.id,
                        s.nama_lengkap,
                        s.nisn,
                        s.nis,
                        s.status,
                        s.id_kelas,
                        k.nama_kelas,
                        k.kode_kelas,
                        j.nama_jurusan
                    FROM siswa s
                    LEFT JOIN kelas   k ON s.id_kelas  = k.id
                    LEFT JOIN jurusan j ON s.id_jurusan = j.id
                    WHERE s.deleted_at IS NULL
                      AND s.status = 'Aktif'";

            // Filter tenant (WAJIB untuk non-super_admin, opsional untuk super_admin)
            if ($tenantId) {
                $sql .= " AND s.tenant_id = ?";
                $params[] = $tenantId;
            }
            if ($kelasId > 0) {
                $sql .= " AND s.id_kelas = ?";
                $params[] = $kelasId;
            }
            if ($q !== '') {
                $sql .= " AND (LOWER(s.nama_lengkap) LIKE ? OR LOWER(s.nisn) LIKE ? OR LOWER(s.nis) LIKE ?)";
                $lowerQ = strtolower($q);
                $params[] = "%$lowerQ%";
                $params[] = "%$lowerQ%";
                $params[] = "%$lowerQ%";
            }

            $sql .= " ORDER BY k.nama_kelas ASC, s.nama_lengkap ASC LIMIT ?";
            $stmt = $db->prepare($sql);
            // Bind semua params lalu bind limit sebagai INT
            foreach ($params as $i => $val) {
                $stmt->bindValue($i + 1, $val, \PDO::PARAM_STR);
            }
            $stmt->bindValue(count($params) + 1, $limit, \PDO::PARAM_INT);
            $stmt->execute();

            $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => 'Gagal mencari siswa: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Ambil daftar kelas aktif untuk dropdown filter Rekam Kasus
    // GET /api/v1/bk/kelas
    // =========================================================================
    public function apiKelasList(): void {
        $tenantId = $this->getSecureTenantId();
        $db       = \App\Config\Database::getConnection();

        try {
            $sql = "SELECT k.id, k.nama_kelas, k.kode_kelas,
                           j.nama_jurusan,
                           COUNT(s.id) AS jumlah_siswa
                    FROM kelas k
                    LEFT JOIN jurusan j  ON k.id_jurusan = j.id
                    LEFT JOIN siswa   s  ON s.id_kelas   = k.id
                                        AND s.deleted_at IS NULL
                                        AND s.status = 'Aktif'
                    WHERE k.deleted_at IS NULL
                      AND k.is_active = 1";
            $params = [];
            if ($tenantId) {
                $sql .= " AND k.tenant_id = ?";
                $params[] = $tenantId;
            }
            $sql .= " GROUP BY k.id, k.nama_kelas, k.kode_kelas, j.nama_jurusan
                      ORDER BY k.nama_kelas ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => 'Gagal mengambil kelas: ' . $e->getMessage()], 500);
        }
    }


    // =========================================================================
    // API: Tab 1 — KPI Dashboard Monitoring
    // GET /api/v1/bk/dashboard
    // =========================================================================
    public function apiDashboard(): void {
        $tenantId = $this->getSecureTenantId();
        $db       = \App\Config\Database::getConnection();

        try {
            // Total siswa aktif
            $q = "SELECT COUNT(*) FROM siswa WHERE deleted_at IS NULL AND status = 'Aktif'";
            $params = [];
            if ($tenantId) { $q .= " AND tenant_id = ?"; $params[] = $tenantId; }
            $totalSiswaAktif = (int)$db->prepare($q)->execute($params) ? $db->prepare($q) : 0;
            $stmt = $db->prepare($q); $stmt->execute($params);
            $totalSiswaAktif = (int)$stmt->fetchColumn();

            // Total kasus BK bulan ini
            $q2 = "SELECT COUNT(*) FROM catatan_bk WHERE MONTH(tanggal_konseling) = MONTH(CURDATE()) AND YEAR(tanggal_konseling) = YEAR(CURDATE()) AND deleted_at IS NULL";
            $p2 = [];
            if ($tenantId) { $q2 .= " AND tenant_id = ?"; $p2[] = $tenantId; }
            $st2 = $db->prepare($q2); $st2->execute($p2);
            $kasusBulanIni = (int)$st2->fetchColumn();

            // Kasus terbuka
            $q3 = "SELECT COUNT(*) FROM catatan_bk WHERE status_kasus = 'Terbuka' AND deleted_at IS NULL";
            $p3 = [];
            if ($tenantId) { $q3 .= " AND tenant_id = ?"; $p3[] = $tenantId; }
            $st3 = $db->prepare($q3); $st3->execute($p3);
            $kasusTerbuka = (int)$st3->fetchColumn();

            // Siswa sudah lulus (alumni)
            $q4 = "SELECT COUNT(*) FROM siswa WHERE status = 'Lulus' AND deleted_at IS NULL";
            $p4 = [];
            if ($tenantId) { $q4 .= " AND tenant_id = ?"; $p4[] = $tenantId; }
            $st4 = $db->prepare($q4); $st4->execute($p4);
            $totalAlumni = (int)$st4->fetchColumn();

            // Distribusi kasus per jenis (Pie chart data)
            $q5 = "SELECT jenis_kasus, COUNT(*) as total FROM catatan_bk WHERE deleted_at IS NULL";
            $p5 = [];
            if ($tenantId) { $q5 .= " AND tenant_id = ?"; $p5[] = $tenantId; }
            $q5 .= " GROUP BY jenis_kasus";
            $st5 = $db->prepare($q5); $st5->execute($p5);
            $distribusiKasus = $st5->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success'          => true,
                'total_siswa_aktif'=> $totalSiswaAktif,
                'kasus_bulan_ini'  => $kasusBulanIni,
                'kasus_terbuka'    => $kasusTerbuka,
                'total_alumni'     => $totalAlumni,
                'distribusi_kasus' => $distribusiKasus,
            ]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiDashboard] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data dashboard BK.'], 500);
        }
    }

    // =========================================================================
    // API: Tab 5 — Rekam Kasus BK (INSERT catatan_bk + SNAPSHOT kelas)
    // POST /api/v1/bk/kasus
    //
    // ARSITEKTUR SNAPSHOT HISTORIS:
    // Saat menyimpan kasus, kelas siswa diambil dari DB saat ini dan dikunci
    // permanen ke kolom snapshot_nama_kelas & id_kelas_snapshot.
    // Jika tahun depan siswa naik kelas, histori kasus ini TETAP menampilkan
    // kelas lama — karena data snapshot tidak pernah berubah.
    // =========================================================================
    public function apiStoreKasus(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId   = $this->getSecureTenantId();
        $idGuruBk   = $_SESSION['user_id'] ?? null;
        $body       = $this->getJsonInput();

        $idSiswa      = $this->sanitize($body['id_siswa']         ?? '');
        $tanggal      = $this->sanitize($body['tanggal_konseling'] ?? '');
        $jenisKasus   = $this->sanitize($body['jenis_kasus']       ?? '');
        $catatan      = $this->sanitize($body['catatan']           ?? '');
        $tindakLanjut = $this->sanitize($body['tindak_lanjut']     ?? '');
        $statusKasus  = $this->sanitize($body['status_kasus']      ?? 'Terbuka');
        $isRahasia    = (int)($body['is_rahasia'] ?? 1);

        // ── Validasi Input ────────────────────────────────────────────────────
        $errors = [];
        if (empty($idSiswa))  $errors[] = 'ID Siswa wajib diisi.';
        if (empty($tenantId)) $errors[] = 'Tenant tidak terdeteksi.';
        if (empty($tanggal)  || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal))
            $errors[] = 'Tanggal konseling tidak valid.';
        if (empty($catatan))  $errors[] = 'Catatan konseling wajib diisi.';

        $validJenis  = ['Akademik','Perilaku','Keluarga','Karir','Kesehatan Mental','Lainnya'];
        $validStatus = ['Terbuka','Proses','Selesai'];
        if (!in_array($jenisKasus,  $validJenis,  true)) $errors[] = 'Jenis kasus tidak valid.';
        if (!in_array($statusKasus, $validStatus, true)) $errors[] = 'Status kasus tidak valid.';

        if (!empty($errors)) {
            $this->jsonResponse(['error' => implode(' ', $errors)], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // ── STEP 1: Ambil data snapshot siswa SAAT INI ──────────────────
            // Query ini mengambil kelas, NIS, NISN pada moment rekam kasus.
            // Nilai ini akan dikunci abadi di catatan_bk — TIDAK PERNAH berubah
            // meskipun tahun depan siswa naik kelas (Historical Snapshot Pattern).
            $stmtSnap = $db->prepare("
                SELECT
                    s.id,
                    s.nama_lengkap,
                    s.nisn,
                    s.nis,
                    s.id_kelas,
                    k.nama_kelas
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                WHERE s.id        = ?
                  AND s.tenant_id = ?
                  AND s.deleted_at IS NULL
                LIMIT 1
            ");
            $stmtSnap->execute([$idSiswa, $tenantId]);
            $siswaSnap = $stmtSnap->fetch(\PDO::FETCH_ASSOC);

            if (!$siswaSnap) {
                $this->jsonResponse(['error' => 'Siswa tidak ditemukan di sekolah Anda.'], 404);
                return;
            }

            // Nilai snapshot — diambil dari DB saat ini, bukan dari input user
            $snapNamaSiswa = $siswaSnap['nama_lengkap'] ?? null;
            $snapNisn      = $siswaSnap['nisn']          ?? null;
            $snapNis       = $siswaSnap['nis']           ?? null;
            $snapNamaKelas = $siswaSnap['nama_kelas']    ?? null;
            $snapIdKelas   = $siswaSnap['id_kelas']      ?? null;

            // ── STEP 2: INSERT dengan snapshot terkunci ─────────────────────
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO catatan_bk (
                    id_siswa,
                    snapshot_nama_siswa,
                    snapshot_nisn,
                    snapshot_nis,
                    snapshot_nama_kelas,
                    id_kelas_snapshot,
                    tenant_id,
                    id_guru_bk,
                    tanggal_konseling,
                    jenis_kasus,
                    catatan,
                    tindak_lanjut,
                    status_kasus,
                    is_rahasia
                ) VALUES (
                    :id_siswa,
                    :snap_nama_siswa,
                    :snap_nisn,
                    :snap_nis,
                    :snap_nama_kelas,
                    :snap_id_kelas,
                    :tenant_id,
                    :id_guru_bk,
                    :tanggal,
                    :jenis,
                    :catatan,
                    :tindak,
                    :status,
                    :rahasia
                )
            ");
            $stmt->execute([
                'id_siswa'        => $idSiswa,
                'snap_nama_siswa' => $snapNamaSiswa,
                'snap_nisn'       => $snapNisn,
                'snap_nis'        => $snapNis,
                'snap_nama_kelas' => $snapNamaKelas,
                'snap_id_kelas'   => $snapIdKelas,
                'tenant_id'       => $tenantId,
                'id_guru_bk'      => $idGuruBk,
                'tanggal'         => $tanggal,
                'jenis'           => $jenisKasus,
                'catatan'         => $catatan,
                'tindak'          => $tindakLanjut ?: null,
                'status'          => $statusKasus,
                'rahasia'         => $isRahasia,
            ]);
            $newId = $db->lastInsertId();

            // Ambil role penindak saat ini
            $rolesList = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
            $currentUserRole = 'guru_bk';
            foreach (['super_admin', 'operator_sekolah', 'guru_bk'] as $allowed) {
                if (in_array($allowed, $rolesList)) {
                    $currentUserRole = $allowed;
                    break;
                }
            }
            $currentUserName = $_SESSION['nama_lengkap'] ?? 'Guru BK / Admin';

            // Insert log inisiasi pembuatan kasus ke catatan_bk_log
            $stmtLog = $db->prepare("
                INSERT INTO catatan_bk_log (
                    id_catatan_bk,
                    tenant_id,
                    status_lama,
                    status_baru,
                    id_user,
                    nama_user,
                    peran_user
                ) VALUES (
                    :id_catatan_bk,
                    :tenant_id,
                    NULL,
                    :status_baru,
                    :id_user,
                    :nama_user,
                    :peran_user
                )
            ");
            $stmtLog->execute([
                'id_catatan_bk' => $newId,
                'tenant_id'     => $tenantId,
                'status_baru'   => $statusKasus,
                'id_user'       => $idGuruBk,
                'nama_user'     => $currentUserName,
                'peran_user'    => $currentUserRole
            ]);

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'id'      => $newId,
                'message' => 'Catatan BK berhasil disimpan.',
                'snapshot' => [
                    'nama_siswa' => $snapNamaSiswa,
                    'nisn'       => $snapNisn,
                    'nis'        => $snapNis,
                    'nama_kelas' => $snapNamaKelas,
                ]
            ]);

        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('[BKController::apiStoreKasus] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan catatan. Coba lagi.'], 500);
        }
    }


    // =========================================================================
    // API: Tab 5 — Daftar Kasus (GET) — dengan Historical Snapshot
    // GET /api/v1/bk/kasus
    // =========================================================================
    public function apiListKasus(): void {
        $tenantId = $this->getSecureTenantId();
        $roles    = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isGuruBkOnly = in_array('guru_bk', $roles) && !in_array('operator_sekolah', $roles) && !in_array('super_admin', $roles);
        $db       = \App\Config\Database::getConnection();

        try {
            // Prioritaskan data snapshot (terkunci historis).
            // Jika snapshot_nama_siswa NULL (rekaman lama sebelum migration),
            // fallback ke JOIN langsung ke tabel siswa.
            $q = "
                SELECT
                    cb.id,
                    cb.tanggal_konseling,
                    cb.jenis_kasus,
                    cb.status_kasus,
                    cb.is_rahasia,
                    cb.tindak_lanjut,
                    cb.id_guru_bk,
                    -- Data snapshot (diutamakan — immutable historis)
                    COALESCE(cb.snapshot_nama_siswa, s.nama_lengkap) AS nama_siswa,
                    COALESCE(cb.snapshot_nisn,        s.nisn)         AS nisn,
                    COALESCE(cb.snapshot_nis,         s.nis)          AS nis,
                    COALESCE(cb.snapshot_nama_kelas,  k.nama_kelas)   AS nama_kelas,
                    cb.snapshot_nama_kelas  AS kelas_saat_kejadian,
                    u.nama_lengkap          AS nama_guru_bk
                FROM catatan_bk cb
                LEFT JOIN siswa  s  ON cb.id_siswa   = s.id
                LEFT JOIN kelas  k  ON s.id_kelas    = k.id
                LEFT JOIN users  u  ON cb.id_guru_bk = u.id
                WHERE cb.deleted_at IS NULL
            ";
            $params = [];

            if ($tenantId) {
                $q .= " AND cb.tenant_id = ?";
                $params[] = $tenantId;
            }

            // Guru BK hanya melihat catatan yang tidak rahasia atau yang dia buat
            if ($isGuruBkOnly) {
                $q .= " AND (cb.is_rahasia = 0 OR cb.id_guru_bk = ?)";
                $params[] = $_SESSION['user_id'] ?? '';
            }

            $q .= " ORDER BY cb.tanggal_konseling DESC, cb.id DESC LIMIT 150";
            $stmt = $db->prepare($q);
            $stmt->execute($params);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiListKasus] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data kasus.'], 500);
        }
    }

    // =========================================================================
    // API: Tab 5 — Update Status Riwayat Kasus BK + Log
    // POST /api/v1/bk/kasus/update-status
    // =========================================================================
    public function apiUpdateStatus(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        $roles    = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isGuruBkOnly = in_array('guru_bk', $roles) && !in_array('operator_sekolah', $roles) && !in_array('super_admin', $roles);
        $userId   = $_SESSION['user_id'] ?? null;
        $body     = $this->getJsonInput();

        $idKasus = (int)($body['id_kasus'] ?? 0);
        $status  = $this->sanitize($body['status_kasus'] ?? '');

        if (!$idKasus) {
            $this->jsonResponse(['error' => 'ID Kasus tidak valid.'], 422);
            return;
        }

        $validStatus = ['Terbuka', 'Proses', 'Selesai'];
        if (!in_array($status, $validStatus, true)) {
            $this->jsonResponse(['error' => 'Status kasus tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // Pengecekan data kasus di DB
            $qCheck = "SELECT id, tenant_id, id_guru_bk, is_rahasia, status_kasus FROM catatan_bk WHERE id = ? AND deleted_at IS NULL LIMIT 1";
            $stmtCheck = $db->prepare($qCheck);
            $stmtCheck->execute([$idKasus]);
            $kasus = $stmtCheck->fetch(\PDO::FETCH_ASSOC);

            if (!$kasus) {
                $this->jsonResponse(['error' => 'Catatan kasus tidak ditemukan.'], 404);
                return;
            }

            // Validasi kepemilikan tenant
            if ($tenantId && $kasus['tenant_id'] !== $tenantId) {
                $this->jsonResponse(['error' => 'Akses ditolak. Kasus berada di sekolah lain.'], 403);
                return;
            }

            // Otorisasi guru_bk untuk kasus rahasia
            if ($isGuruBkOnly && (int)$kasus['is_rahasia'] === 1 && $kasus['id_guru_bk'] !== $userId) {
                $this->jsonResponse(['error' => 'Akses ditolak. Anda tidak berhak mengubah kasus rahasia milik rekan guru lain.'], 403);
                return;
            }

            $statusLama = $kasus['status_kasus'];
            if ($statusLama === $status) {
                $this->jsonResponse(['success' => true, 'message' => 'Status kasus tidak berubah.']);
                return;
            }

            // Mulai transaksi untuk ACID lock/log
            $db->beginTransaction();

            // Update status kasus
            $stmtUpdate = $db->prepare("UPDATE catatan_bk SET status_kasus = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$status, $idKasus]);

            // Ambil role penindak saat ini
            $currentUserRole = 'guru_bk';
            foreach (['super_admin', 'operator_sekolah', 'guru_bk'] as $allowed) {
                if (in_array($allowed, $roles)) {
                    $currentUserRole = $allowed;
                    break;
                }
            }
            $currentUserName = $_SESSION['nama_lengkap'] ?? 'Guru BK / Admin';

            // Log update status ke catatan_bk_log
            $stmtLog = $db->prepare("
                INSERT INTO catatan_bk_log (
                    id_catatan_bk,
                    tenant_id,
                    status_lama,
                    status_baru,
                    id_user,
                    nama_user,
                    peran_user
                ) VALUES (
                    :id_catatan_bk,
                    :tenant_id,
                    :status_lama,
                    :status_baru,
                    :id_user,
                    :nama_user,
                    :peran_user
                )
            ");
            $stmtLog->execute([
                'id_catatan_bk' => $idKasus,
                'tenant_id'     => $kasus['tenant_id'],
                'status_lama'   => $statusLama,
                'status_baru'   => $status,
                'id_user'       => $userId,
                'nama_user'     => $currentUserName,
                'peran_user'    => $currentUserRole
            ]);

            $db->commit();

            $this->jsonResponse(['success' => true, 'message' => 'Status kasus berhasil diperbarui menjadi ' . $status]);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('[BKController::apiUpdateStatus] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memperbarui status kasus. Coba lagi.'], 500);
        }
    }

    // =========================================================================
    // API: Tab 5 — Get Log Riwayat Aktivitas Kasus BK
    // GET /api/v1/bk/kasus/logs?id_kasus=X
    // =========================================================================
    public function apiGetLogs(): void {
        $tenantId = $this->getSecureTenantId();
        $roles    = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isGuruBkOnly = in_array('guru_bk', $roles) && !in_array('operator_sekolah', $roles) && !in_array('super_admin', $roles);
        $userId   = $_SESSION['user_id'] ?? null;

        $idKasus = (int)($_GET['id_kasus'] ?? 0);
        if (!$idKasus) {
            $this->jsonResponse(['error' => 'ID Kasus tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // Pengecekan data kasus di DB
            $qCheck = "SELECT id, tenant_id, id_guru_bk, is_rahasia FROM catatan_bk WHERE id = ? AND deleted_at IS NULL LIMIT 1";
            $stmtCheck = $db->prepare($qCheck);
            $stmtCheck->execute([$idKasus]);
            $kasus = $stmtCheck->fetch(\PDO::FETCH_ASSOC);

            if (!$kasus) {
                $this->jsonResponse(['error' => 'Catatan kasus tidak ditemukan.'], 404);
                return;
            }

            // Validasi tenant
            if ($tenantId && $kasus['tenant_id'] !== $tenantId) {
                $this->jsonResponse(['error' => 'Akses ditolak. Kasus berada di sekolah lain.'], 403);
                return;
            }

            // Otorisasi guru_bk untuk kasus rahasia
            if ($isGuruBkOnly && (int)$kasus['is_rahasia'] === 1 && $kasus['id_guru_bk'] !== $userId) {
                $this->jsonResponse(['error' => 'Akses ditolak. Anda tidak berhak melihat log kasus rahasia milik rekan guru lain.'], 403);
                return;
            }

            // Ambil semua log
            $stmtLogs = $db->prepare("
                SELECT 
                    id, 
                    status_lama, 
                    status_baru, 
                    nama_user, 
                    peran_user, 
                    created_at 
                FROM catatan_bk_log 
                WHERE id_catatan_bk = ? 
                ORDER BY created_at DESC, id DESC
            ");
            $stmtLogs->execute([$idKasus]);
            $logs = $stmtLogs->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'data' => $logs]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiGetLogs] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat log riwayat kasus.'], 500);
        }
    }

    // =========================================================================
    // API: Tab 3 — Tracer Study Summary (untuk BK)
    // GET /api/v1/bk/tracer
    // =========================================================================
    public function apiTracerSummary(): void {
        $tenantId = $this->getSecureTenantId();
        $db       = \App\Config\Database::getConnection();

        try {
            $kuliah = $pekerjaan = 0;
            if ($tenantId) {
                $stK = $db->prepare("SELECT COUNT(*) FROM riwayat_kuliah WHERE tenant_id = ?");
                $stK->execute([$tenantId]);
                $kuliah = (int)$stK->fetchColumn();

                $stP = $db->prepare("SELECT COUNT(*) FROM riwayat_pekerjaan WHERE tenant_id = ?");
                $stP->execute([$tenantId]);
                $pekerjaan = (int)$stP->fetchColumn();
            }

            $this->jsonResponse([
                'success'   => true,
                'kuliah'    => $kuliah,
                'pekerjaan' => $pekerjaan,
                'total'     => $kuliah + $pekerjaan,
            ]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiTracerSummary] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data tracer.'], 500);
        }
    }


    // =========================================================================
    // API: Tab 2 — List Pilihan Penjurusan
    // GET /api/v1/bk/penjurusan
    // Query params: ?status=Diajukan|Diverifikasi|Ditolak|Override_BK&jurusan_id=&search=
    // =========================================================================
    public function apiListPenjurusan(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        $db           = \App\Config\Database::getConnection();
        $filterStatus = $this->sanitize($_GET['status'] ?? '');
        $filterJurusan= (int)($_GET['jurusan_id'] ?? 0);
        $search       = $this->sanitize($_GET['search'] ?? '');

        try {
            $params = [$tenantId];
            $q = "
                SELECT
                    pp.id, pp.status, pp.dikunci, pp.diajukan_oleh, pp.catatan_bk,
                    pp.created_at, pp.updated_at,
                    s.id           AS id_siswa,
                    s.nama_lengkap AS nama_siswa,
                    s.nisn,
                    s.id_kelas,
                    k.nama_kelas,
                    j.id           AS id_jurusan,
                    j.nama_jurusan,
                    j.kode_jurusan,
                    u.nama_lengkap AS nama_pemroses,
                    pp.diproses_at
                FROM pilihan_penjurusan pp
                JOIN siswa   s  ON pp.id_siswa   = s.id
                JOIN jurusan j  ON pp.id_jurusan = j.id
                LEFT JOIN kelas k  ON s.id_kelas = k.id
                LEFT JOIN users u  ON pp.diproses_oleh = u.id
                WHERE pp.tenant_id = ?
                  AND pp.deleted_at IS NULL
                  AND s.deleted_at IS NULL
            ";

            if ($filterStatus) {
                $q .= " AND pp.status = ?";
                $params[] = $filterStatus;
            }
            if ($filterJurusan > 0) {
                $q .= " AND pp.id_jurusan = ?";
                $params[] = $filterJurusan;
            }
            if ($search) {
                $q .= " AND (LOWER(s.nama_lengkap) LIKE ? OR LOWER(s.nisn) LIKE ?)";
                $lowerSearch = strtolower($search);
                $params[] = "%$lowerSearch%";
                $params[] = "%$lowerSearch%";
            }

            $q .= " ORDER BY pp.created_at DESC LIMIT 200";
            $stmt = $db->prepare($q);
            $stmt->execute($params);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Ambil distribusi per jurusan untuk summary bar
            $qSummary = "
                SELECT j.nama_jurusan, j.kode_jurusan, COUNT(pp.id) AS total,
                       SUM(pp.status = 'Diverifikasi') AS terverifikasi,
                       SUM(pp.status = 'Diajukan')     AS pending,
                       SUM(pp.status = 'Ditolak')      AS ditolak
                FROM pilihan_penjurusan pp
                JOIN jurusan j ON pp.id_jurusan = j.id
                WHERE pp.tenant_id = ? AND pp.deleted_at IS NULL
                GROUP BY j.id, j.nama_jurusan, j.kode_jurusan
                ORDER BY total DESC
            ";
            $stmtSum = $db->prepare($qSummary);
            $stmtSum->execute([$tenantId]);
            $summary = $stmtSum->fetchAll(\PDO::FETCH_ASSOC);

            // Ambil daftar jurusan untuk filter dropdown
            $stmtJr = $db->prepare("SELECT id, kode_jurusan, nama_jurusan FROM jurusan WHERE tenant_id = ? AND is_active = 1 AND deleted_at IS NULL ORDER BY nama_jurusan");
            $stmtJr->execute([$tenantId]);
            $jurusanList = $stmtJr->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success'      => true,
                'data'         => $data,
                'summary'      => $summary,
                'jurusan_list' => $jurusanList,
                'total'        => count($data),
            ]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiListPenjurusan] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data penjurusan.'], 500);
        }
    }

    // =========================================================================
    // API: Tab 2 — Verifikasi / Tolak Pilihan Penjurusan
    // POST /api/v1/bk/penjurusan/verifikasi
    // Body: { id_pilihan, aksi: "Verifikasi"|"Tolak", catatan_bk }
    // =========================================================================
    public function apiVerifikasiPenjurusan(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId  = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400); return; }

        $body      = $this->getJsonInput();
        $idPilihan = (int)($body['id_pilihan'] ?? 0);
        $aksi      = $this->sanitize($body['aksi'] ?? '');
        $catatanBk = $this->sanitize($body['catatan_bk'] ?? '');
        $userId    = $_SESSION['user_id']    ?? '';
        $userNama  = $_SESSION['nama_lengkap'] ?? '';

        $validAksi = ['Verifikasi', 'Tolak'];
        if ($idPilihan <= 0 || !in_array($aksi, $validAksi, true)) {
            $this->jsonResponse(['error' => 'Parameter tidak valid.'], 422);
            return;
        }

        $db = \App\Config\Database::getConnection();
        try {
            // Ambil data pilihan dan verifikasi kepemilikan tenant (anti-IDOR)
            $stmtGet = $db->prepare("
                SELECT pp.*, j.nama_jurusan, s.nama_lengkap AS nama_siswa
                FROM pilihan_penjurusan pp
                JOIN jurusan j ON pp.id_jurusan = j.id
                JOIN siswa   s ON pp.id_siswa   = s.id
                WHERE pp.id = ? AND pp.tenant_id = ? AND pp.deleted_at IS NULL
                LIMIT 1
            ");
            $stmtGet->execute([$idPilihan, $tenantId]);
            $pilihan = $stmtGet->fetch(\PDO::FETCH_ASSOC);

            if (!$pilihan) {
                $this->jsonResponse(['error' => 'Data pilihan tidak ditemukan.'], 404);
                return;
            }
            if ($pilihan['dikunci']) {
                $this->jsonResponse(['error' => 'Pilihan ini telah dikunci. Buka kunci terlebih dahulu.'], 409);
                return;
            }

            $statusBaru = ($aksi === 'Verifikasi') ? 'Diverifikasi' : 'Ditolak';

            $db->beginTransaction();

            // Update status pilihan
            $stmtUpd = $db->prepare("
                UPDATE pilihan_penjurusan
                SET status = ?, catatan_bk = ?, diproses_oleh = ?, diproses_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmtUpd->execute([$statusBaru, $catatanBk ?: null, $userId, $idPilihan]);

            // Tulis audit log
            $this->writeLog($db, [
                'id_pilihan'     => $idPilihan,
                'id_siswa'       => $pilihan['id_siswa'],
                'tenant_id'      => $tenantId,
                'id_jurusan_lama'=> $pilihan['id_jurusan'],
                'id_jurusan_baru'=> $pilihan['id_jurusan'],
                'status_lama'    => $pilihan['status'],
                'status_baru'    => $statusBaru,
                'aksi'           => $aksi,
                'dilakukan_oleh' => $userId,
                'nama_pelaku'    => $userNama,
                'catatan'        => $catatanBk,
            ]);

            $db->commit();
            $this->jsonResponse([
                'success' => true,
                'message' => "Pilihan penjurusan {$pilihan['nama_siswa']} berhasil di-{$aksi}.",
            ]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[BKController::apiVerifikasiPenjurusan] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Operasi gagal. Coba lagi.'], 500);
        }
    }

    // =========================================================================
    // API: Tab 2 — Override Pilihan Jurusan (ACID Transaction)
    // POST /api/v1/bk/penjurusan/override
    // Body: { id_pilihan, id_jurusan_baru, catatan_bk }
    // Guru BK/Admin dapat memindahkan siswa ke jurusan berbeda
    // =========================================================================
    public function apiOverridePenjurusan(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId    = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400); return; }

        $body          = $this->getJsonInput();
        $idPilihan     = (int)($body['id_pilihan']      ?? 0);
        $idJurusanBaru = (int)($body['id_jurusan_baru'] ?? 0);
        $catatanBk     = $this->sanitize($body['catatan_bk'] ?? '');
        $userId        = $_SESSION['user_id']    ?? '';
        $userNama      = $_SESSION['nama_lengkap'] ?? '';

        if ($idPilihan <= 0 || $idJurusanBaru <= 0) {
            $this->jsonResponse(['error' => 'ID pilihan dan jurusan baru wajib diisi.'], 422);
            return;
        }
        if (empty($catatanBk)) {
            $this->jsonResponse(['error' => 'Catatan alasan override wajib diisi untuk keperluan audit.'], 422);
            return;
        }

        $db = \App\Config\Database::getConnection();
        try {
            // Verifikasi pilihan + ownership tenant (anti-IDOR)
            $stmtGet = $db->prepare("
                SELECT pp.*, s.nama_lengkap AS nama_siswa
                FROM pilihan_penjurusan pp
                JOIN siswa s ON pp.id_siswa = s.id
                WHERE pp.id = ? AND pp.tenant_id = ? AND pp.deleted_at IS NULL LIMIT 1
            ");
            $stmtGet->execute([$idPilihan, $tenantId]);
            $pilihan = $stmtGet->fetch(\PDO::FETCH_ASSOC);

            if (!$pilihan) {
                $this->jsonResponse(['error' => 'Data pilihan tidak ditemukan.'], 404);
                return;
            }

            // Verifikasi jurusan baru milik tenant yang sama
            $stmtJr = $db->prepare("SELECT id, nama_jurusan FROM jurusan WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtJr->execute([$idJurusanBaru, $tenantId]);
            $jurusanBaru = $stmtJr->fetch(\PDO::FETCH_ASSOC);

            if (!$jurusanBaru) {
                $this->jsonResponse(['error' => 'Jurusan tujuan tidak valid atau bukan milik sekolah ini.'], 404);
                return;
            }

            $db->beginTransaction();

            // UPDATE pilihan: jurusan baru, status Override_BK, dikunci = 1
            $stmtUpd = $db->prepare("
                UPDATE pilihan_penjurusan
                SET id_jurusan    = ?,
                    status        = 'Override_BK',
                    dikunci       = 1,
                    catatan_bk    = ?,
                    diajukan_oleh = 'Guru BK',
                    diproses_oleh = ?,
                    diproses_at   = NOW(),
                    updated_at    = NOW()
                WHERE id = ?
            ");
            $stmtUpd->execute([$idJurusanBaru, $catatanBk, $userId, $idPilihan]);

            // Tulis audit log yang lengkap
            $this->writeLog($db, [
                'id_pilihan'     => $idPilihan,
                'id_siswa'       => $pilihan['id_siswa'],
                'tenant_id'      => $tenantId,
                'id_jurusan_lama'=> $pilihan['id_jurusan'],
                'id_jurusan_baru'=> $idJurusanBaru,
                'status_lama'    => $pilihan['status'],
                'status_baru'    => 'Override_BK',
                'aksi'           => 'Override',
                'dilakukan_oleh' => $userId,
                'nama_pelaku'    => $userNama,
                'catatan'        => $catatanBk,
            ]);

            $db->commit();
            $this->jsonResponse([
                'success'      => true,
                'message'      => "Override berhasil! {$pilihan['nama_siswa']} dipindahkan ke jurusan {$jurusanBaru['nama_jurusan']} dan dikunci.",
                'jurusan_baru' => $jurusanBaru['nama_jurusan'],
            ]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[BKController::apiOverridePenjurusan] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Override gagal. ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Tab 2 — Toggle Kunci Pilihan
    // POST /api/v1/bk/penjurusan/kunci
    // Body: { id_pilihan, dikunci: 0|1 }
    // =========================================================================
    public function apiToggleKunci(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId  = $this->getSecureTenantId();
        if (!$tenantId) { $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400); return; }

        $body      = $this->getJsonInput();
        $idPilihan = (int)($body['id_pilihan'] ?? 0);
        $dikunci   = (int)(bool)($body['dikunci'] ?? 0); // 0 atau 1
        $userId    = $_SESSION['user_id']    ?? '';
        $userNama  = $_SESSION['nama_lengkap'] ?? '';

        if ($idPilihan <= 0) {
            $this->jsonResponse(['error' => 'ID pilihan tidak valid.'], 422);
            return;
        }

        $db = \App\Config\Database::getConnection();
        try {
            $stmtGet = $db->prepare("SELECT * FROM pilihan_penjurusan WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtGet->execute([$idPilihan, $tenantId]);
            $pilihan = $stmtGet->fetch(\PDO::FETCH_ASSOC);

            if (!$pilihan) { $this->jsonResponse(['error' => 'Data tidak ditemukan.'], 404); return; }

            $db->beginTransaction();

            $db->prepare("UPDATE pilihan_penjurusan SET dikunci = ?, updated_at = NOW() WHERE id = ?")
               ->execute([$dikunci, $idPilihan]);

            $aksiLabel = $dikunci ? 'Kunci' : 'Buka Kunci';
            $this->writeLog($db, [
                'id_pilihan'     => $idPilihan,
                'id_siswa'       => $pilihan['id_siswa'],
                'tenant_id'      => $tenantId,
                'id_jurusan_lama'=> $pilihan['id_jurusan'],
                'id_jurusan_baru'=> $pilihan['id_jurusan'],
                'status_lama'    => $pilihan['status'],
                'status_baru'    => $pilihan['status'],
                'aksi'           => 'Buka Kunci',
                'dilakukan_oleh' => $userId,
                'nama_pelaku'    => $userNama,
                'catatan'        => $aksiLabel . ' dilakukan oleh admin.',
            ]);

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => "Pilihan berhasil di-{$aksiLabel}.", 'dikunci' => $dikunci]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[BKController::apiToggleKunci] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Operasi gagal.'], 500);
        }
    }

    // =========================================================================
    // API: Tab 6 — Get Guru List
    // GET /api/v1/bk/guru
    // =========================================================================
    public function apiGetGuruList(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            // Ambil guru (role_id = 3 untuk guru, role_id = 20 untuk guru_bk)
            $stmt = $db->prepare("
                SELECT id, nama_lengkap 
                FROM users 
                WHERE tenant_id = ? 
                  AND role_id IN (3, 20) 
                  AND deleted_at IS NULL 
                ORDER BY nama_lengkap ASC
            ");
            $stmt->execute([$tenantId]);
            $gurus = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'data' => $gurus]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiGetGuruList] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data guru.'], 500);
        }
    }

    // =========================================================================
    // API: Tab 6 — List Prestasi Siswa
    // GET /api/v1/bk/prestasi
    // =========================================================================
    public function apiListPrestasi(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    ps.id,
                    ps.tahun_ajaran_id,
                    ta.tahun_ajaran,
                    ps.semester,
                    ps.bidang_lomba,
                    ps.nama_lomba,
                    ps.nomor_sertifikat,
                    ps.juara,
                    ps.kategori,
                    ps.tingkat_kejuaraan,
                    ps.jenis_lomba,
                    ps.tempat_lomba,
                    ps.tanggal_lomba,
                    ps.penyelenggara,
                    ps.guru_pendamping,
                    ps.poin_prestasi,
                    ps.foto_bukti_prestasi,
                    ps.foto_siswa_prestasi,
                    ps.foto_kegiatan_lomba,
                    ps.surat_tugas_pdf
                FROM prestasi_siswa ps
                JOIN tahun_ajaran ta ON ps.tahun_ajaran_id = ta.id
                WHERE ps.tenant_id = ? 
                  AND ps.deleted_at IS NULL
                ORDER BY ps.tanggal_lomba DESC, ps.created_at DESC
            ");
            $stmt->execute([$tenantId]);
            $prestasiList = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Ambil anggota/siswa untuk setiap prestasi
            foreach ($prestasiList as &$p) {
                $stmtAnggota = $db->prepare("
                    SELECT 
                        s.id,
                        s.nama_lengkap,
                        s.nisn,
                        s.nik,
                        k.nama_kelas
                    FROM prestasi_siswa_anggota psa
                    JOIN siswa s ON psa.id_siswa = s.id
                    LEFT JOIN kelas k ON s.id_kelas = k.id
                    WHERE psa.id_prestasi = ? 
                      AND s.deleted_at IS NULL
                ");
                $stmtAnggota->execute([$p['id']]);
                $p['siswa_list'] = $stmtAnggota->fetchAll(\PDO::FETCH_ASSOC);
            }

            $this->jsonResponse(['success' => true, 'data' => $prestasiList]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiListPrestasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat daftar prestasi.'], 500);
        }
    }

    // =========================================================================
    // API: Tab 6 — Store Prestasi Siswa
    // POST /api/v1/bk/prestasi
    // =========================================================================
    public function apiStorePrestasi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400);
            return;
        }

        // Ambil data POST (biasanya multipart/form-data)
        $tahunAjaranId    = (int)($_POST['tahun_ajaran_id']    ?? 0);
        $semester         = $this->sanitize($_POST['semester']         ?? '');
        $bidangLomba      = $this->sanitize($_POST['bidang_lomba']      ?? '');
        $namaLomba        = $this->sanitize($_POST['nama_lomba']        ?? '');
        $nomorSertifikat  = $this->sanitize($_POST['nomor_sertifikat']  ?? '');
        $juara            = $this->sanitize($_POST['juara']            ?? '');
        $juaraLainnya     = $this->sanitize($_POST['juara_lainnya']     ?? '');
        $kategori         = $this->sanitize($_POST['kategori']         ?? 'Personal');
        $tingkatKejuaraan = $this->sanitize($_POST['tingkat_kejuaraan'] ?? '');
        $jenisLomba       = $this->sanitize($_POST['jenis_lomba']       ?? 'Offline');
        $tempatLomba      = $this->sanitize($_POST['tempat_lomba']      ?? '');
        $tanggalLomba     = $this->sanitize($_POST['tanggal_lomba']     ?? '');
        $penyelenggara    = $this->sanitize($_POST['penyelenggara']    ?? '');
        $guruPendamping   = $this->sanitize($_POST['guru_pendamping']   ?? '');

        // Siswa IDs (array JSON atau string koma)
        $siswaIdsJson = $_POST['siswa_ids'] ?? '[]';
        $siswaIds     = json_decode($siswaIdsJson, true);
        if (!is_array($siswaIds) || empty($siswaIds)) {
            $this->jsonResponse(['error' => 'Minimal pilih satu siswa.'], 422);
            return;
        }

        if (empty($guruPendamping)) {
            $guruPendamping = null;
        }

        // Tentukan nilai juara jika manual
        if ($juara === 'Lainnya' && !empty($juaraLainnya)) {
            $juaraText = $juaraLainnya;
        } else {
            $juaraText = $juara;
        }

        // Validasi input wajib
        if (!$tahunAjaranId || empty($semester) || empty($bidangLomba) || empty($namaLomba) || empty($juaraText) || empty($tingkatKejuaraan) || empty($tempatLomba) || empty($tanggalLomba) || empty($penyelenggara)) {
            $this->jsonResponse(['error' => 'Semua kolom bertanda bintang (*) wajib diisi.'], 422);
            return;
        }

        // Otomatis hitung poin
        $poin = $this->calculatePoints($tingkatKejuaraan, $juaraText);

        // Upload files
        $fotoBukti    = null;
        $fotoSiswa    = null;
        $fotoKegiatan = null;
        $suratTugas   = null;

        // Gunakan UUID siswa pertama untuk folder
        $firstSiswaId = $siswaIds[0];
        $basePath     = "tenants/{$tenantId}/prestasi/{$firstSiswaId}/{$tahunAjaranId}/";
        $uploadDir    = __DIR__ . '/../../storage/app/public/' . $basePath;

        try {
            $this->validateAndUploadFiles($uploadDir, $basePath, $fotoBukti, $fotoSiswa, $fotoKegiatan, $suratTugas);

            $db = \App\Config\Database::getConnection();
            $db->beginTransaction();

            $idPrestasi = bin2hex(random_bytes(16)); // UUID generator sederhana atau UUID v4 format
            $idPrestasi = sprintf('%s-%s-%s-%s-%s',
                substr($idPrestasi, 0, 8),
                substr($idPrestasi, 8, 4),
                substr($idPrestasi, 12, 4),
                substr($idPrestasi, 16, 4),
                substr($idPrestasi, 20, 12)
            );

            // Insert prestasi_siswa
            $stmt = $db->prepare("
                INSERT INTO prestasi_siswa (
                    id, tenant_id, tahun_ajaran_id, semester, bidang_lomba, nama_lomba,
                    nomor_sertifikat, juara, kategori, tingkat_kejuaraan, jenis_lomba,
                    tempat_lomba, tanggal_lomba, penyelenggara, guru_pendamping,
                    poin_prestasi, foto_bukti_prestasi, foto_siswa_prestasi,
                    foto_kegiatan_lomba, surat_tugas_pdf
                ) VALUES (
                    :id, :tenant_id, :tahun_ajaran_id, :semester, :bidang_lomba, :nama_lomba,
                    :nomor_sertifikat, :juara, :kategori, :tingkat_kejuaraan, :jenis_lomba,
                    :tempat_lomba, :tanggal_lomba, :penyelenggara, :guru_pendamping,
                    :poin_prestasi, :foto_bukti_prestasi, :foto_siswa_prestasi,
                    :foto_kegiatan_lomba, :surat_tugas_pdf
                )
            ");
            $stmt->execute([
                'id'                  => $idPrestasi,
                'tenant_id'           => $tenantId,
                'tahun_ajaran_id'     => $tahunAjaranId,
                'semester'            => $semester,
                'bidang_lomba'        => $bidangLomba,
                'nama_lomba'          => $namaLomba,
                'nomor_sertifikat'    => $nomorSertifikat,
                'juara'               => $juaraText,
                'kategori'            => $kategori,
                'tingkat_kejuaraan'   => $tingkatKejuaraan,
                'jenis_lomba'         => $jenisLomba,
                'tempat_lomba'        => $tempatLomba,
                'tanggal_lomba'       => $tanggalLomba,
                'penyelenggara'       => $penyelenggara,
                'guru_pendamping'     => $guruPendamping,
                'poin_prestasi'       => $poin,
                'foto_bukti_prestasi' => $fotoBukti,
                'foto_siswa_prestasi' => $fotoSiswa,
                'foto_kegiatan_lomba' => $fotoKegiatan,
                'surat_tugas_pdf'     => $suratTugas
            ]);

            // Insert pivot anggota
            $stmtAnggota = $db->prepare("INSERT INTO prestasi_siswa_anggota (id_prestasi, id_siswa) VALUES (?, ?)");
            foreach ($siswaIds as $sId) {
                $stmtAnggota->execute([$idPrestasi, $sId]);
            }

            $db->commit();

            foreach ($siswaIds as $sId) {
                \App\Helpers\CacheInvalidator::clearStudentCache($sId, $tenantId);
            }

            $this->jsonResponse(['success' => true, 'message' => 'Data prestasi siswa berhasil disimpan.']);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            // Hapus berkas yang baru diunggah jika gagal simpan
            foreach ([$fotoBukti, $fotoSiswa, $fotoKegiatan, $suratTugas] as $file) {
                if ($file && file_exists(__DIR__ . '/../../storage/app/public/' . $file)) {
                    @unlink(__DIR__ . '/../../storage/app/public/' . $file);
                }
            }
            error_log('[BKController::apiStorePrestasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan data prestasi. ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Tab 6 — Update Prestasi Siswa
    // POST /api/v1/bk/prestasi/update
    // =========================================================================
    public function apiUpdatePrestasi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400);
            return;
        }

        $idPrestasi = $this->sanitize($_POST['id'] ?? '');
        if (empty($idPrestasi)) {
            $this->jsonResponse(['error' => 'ID Prestasi tidak valid.'], 422);
            return;
        }

        $db = \App\Config\Database::getConnection();

        try {
            $oldPathsToDelete = [];
            $newUploadedPaths = [];

            // Ambil data prestasi saat ini
            $stmtGet = $db->prepare("SELECT * FROM prestasi_siswa WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtGet->execute([$idPrestasi, $tenantId]);
            $current = $stmtGet->fetch(\PDO::FETCH_ASSOC);

            if (!$current) {
                $this->jsonResponse(['error' => 'Data prestasi tidak ditemukan.'], 404);
                return;
            }

            // Ambil data POST
            $tahunAjaranId    = (int)($_POST['tahun_ajaran_id']    ?? 0);
            $semester         = $this->sanitize($_POST['semester']         ?? '');
            $bidangLomba      = $this->sanitize($_POST['bidang_lomba']      ?? '');
            $namaLomba        = $this->sanitize($_POST['nama_lomba']        ?? '');
            $nomorSertifikat  = $this->sanitize($_POST['nomor_sertifikat']  ?? '');
            $juara            = $this->sanitize($_POST['juara']            ?? '');
            $juaraLainnya     = $this->sanitize($_POST['juara_lainnya']     ?? '');
            $kategori         = $this->sanitize($_POST['kategori']         ?? 'Personal');
            $tingkatKejuaraan = $this->sanitize($_POST['tingkat_kejuaraan'] ?? '');
            $jenisLomba       = $this->sanitize($_POST['jenis_lomba']       ?? 'Offline');
            $tempatLomba      = $this->sanitize($_POST['tempat_lomba']      ?? '');
            $tanggalLomba     = $this->sanitize($_POST['tanggal_lomba']     ?? '');
            $penyelenggara    = $this->sanitize($_POST['penyelenggara']    ?? '');
            $guruPendamping   = $this->sanitize($_POST['guru_pendamping']   ?? '');

            $siswaIdsJson = $_POST['siswa_ids'] ?? '[]';
            $siswaIds     = json_decode($siswaIdsJson, true);
            if (!is_array($siswaIds) || empty($siswaIds)) {
                $this->jsonResponse(['error' => 'Minimal pilih satu siswa.'], 422);
                return;
            }

            if (empty($guruPendamping)) {
                $guruPendamping = null;
            }

            if ($juara === 'Lainnya' && !empty($juaraLainnya)) {
                $juaraText = $juaraLainnya;
            } else {
                $juaraText = $juara;
            }

            if (!$tahunAjaranId || empty($semester) || empty($bidangLomba) || empty($namaLomba) || empty($juaraText) || empty($tingkatKejuaraan) || empty($tempatLomba) || empty($tanggalLomba) || empty($penyelenggara)) {
                $this->jsonResponse(['error' => 'Semua kolom bertanda bintang (*) wajib diisi.'], 422);
                return;
            }

            // Hitung poin
            $poin = $this->calculatePoints($tingkatKejuaraan, $juaraText);

            // Upload files
            $fotoBukti    = $current['foto_bukti_prestasi'];
            $fotoSiswa    = $current['foto_siswa_prestasi'];
            $fotoKegiatan = $current['foto_kegiatan_lomba'];
            $suratTugas   = $current['surat_tugas_pdf'];

            $firstSiswaId = $siswaIds[0];
            $basePath     = "tenants/{$tenantId}/prestasi/{$firstSiswaId}/{$tahunAjaranId}/";
            $uploadDir    = __DIR__ . '/../../storage/app/public/' . $basePath;

            // Helper upload file baru dan jadwalkan hapus file lama
            $fileKeys = [
                'foto_bukti_prestasi' => &$fotoBukti,
                'foto_siswa_prestasi' => &$fotoSiswa,
                'foto_kegiatan_lomba' => &$fotoKegiatan,
                'surat_tugas_pdf'     => &$suratTugas
            ];

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($fileKeys as $formKey => &$dbVal) {
                if (isset($_FILES[$formKey]) && $_FILES[$formKey]['error'] === UPLOAD_ERR_OK) {
                    $tmpName   = $_FILES[$formKey]['tmp_name'];
                    $fileName  = $_FILES[$formKey]['name'];
                    $fileSize  = $_FILES[$formKey]['size'];
                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    $allowedExt = ($formKey === 'surat_tugas_pdf') ? ['pdf', 'jpg', 'jpeg', 'png'] : ['jpg', 'jpeg', 'png'];
                    if (!in_array($extension, $allowedExt, true)) {
                        throw new \Exception("Ekstensi berkas {$formKey} tidak diizinkan.");
                    }
                    if ($fileSize > 1024 * 1024) {
                        throw new \Exception("Ukuran berkas {$formKey} melebihi batas 1 MB.");
                    }

                    // Kompres dan simpan menggunakan FileCompressor
                    try {
                        if ($extension === 'pdf') {
                            $result = FileCompressor::processPdf($tmpName, $uploadDir, 512 * 1024);
                        } else {
                            $result = FileCompressor::compressImage($tmpName, $uploadDir, 1200, 80);
                        }
                    } catch (\RuntimeException $e) {
                        throw new \Exception("Berkas {$formKey}: " . $e->getMessage());
                    }

                    $newFileName = $result['filename'];
                    $destPath    = $result['path'];

                    $newUploadedPaths[] = $destPath;

                    // Jadwalkan hapus file lama jika ada
                    if (!empty($dbVal)) {
                        $trustedPrefix = "tenants/{$tenantId}/prestasi/";
                        if (str_starts_with($dbVal, $trustedPrefix)) {
                            $oldAbsPath = __DIR__ . '/../../storage/app/public/' . $dbVal;
                            if (file_exists($oldAbsPath)) {
                                $oldPathsToDelete[] = $oldAbsPath;
                            }
                        }
                    }

                    // Update data basepath untuk DB
                    $dbVal = $basePath . $newFileName;
                }
            }

            // Mulai transaksi
            $db->beginTransaction();

            $stmtUpdate = $db->prepare("
                UPDATE prestasi_siswa
                SET tahun_ajaran_id = :tahun_ajaran_id,
                    semester = :semester,
                    bidang_lomba = :bidang_lomba,
                    nama_lomba = :nama_lomba,
                    nomor_sertifikat = :nomor_sertifikat,
                    juara = :juara,
                    kategori = :kategori,
                    tingkat_kejuaraan = :tingkat_kejuaraan,
                    jenis_lomba = :jenis_lomba,
                    tempat_lomba = :tempat_lomba,
                    tanggal_lomba = :tanggal_lomba,
                    penyelenggara = :penyelenggara,
                    guru_pendamping = :guru_pendamping,
                    poin_prestasi = :poin_prestasi,
                    foto_bukti_prestasi = :foto_bukti_prestasi,
                    foto_siswa_prestasi = :foto_siswa_prestasi,
                    foto_kegiatan_lomba = :foto_kegiatan_lomba,
                    surat_tugas_pdf = :surat_tugas_pdf,
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmtUpdate->execute([
                'tahun_ajaran_id'     => $tahunAjaranId,
                'semester'            => $semester,
                'bidang_lomba'        => $bidangLomba,
                'nama_lomba'          => $namaLomba,
                'nomor_sertifikat'    => $nomorSertifikat,
                'juara'               => $juaraText,
                'kategori'            => $kategori,
                'tingkat_kejuaraan'   => $tingkatKejuaraan,
                'jenis_lomba'         => $jenisLomba,
                'tempat_lomba'        => $tempatLomba,
                'tanggal_lomba'       => $tanggalLomba,
                'penyelenggara'       => $penyelenggara,
                'guru_pendamping'     => $guruPendamping,
                'poin_prestasi'       => $poin,
                'foto_bukti_prestasi' => $fotoBukti,
                'foto_siswa_prestasi' => $fotoSiswa,
                'foto_kegiatan_lomba' => $fotoKegiatan,
                'surat_tugas_pdf'     => $suratTugas,
                'id'                  => $idPrestasi,
                'tenant_id'           => $tenantId
            ]);

            // Sync anggota pivot table
            $db->prepare("DELETE FROM prestasi_siswa_anggota WHERE id_prestasi = ?")->execute([$idPrestasi]);
            $stmtAnggota = $db->prepare("INSERT INTO prestasi_siswa_anggota (id_prestasi, id_siswa) VALUES (?, ?)");
            foreach ($siswaIds as $sId) {
                $stmtAnggota->execute([$idPrestasi, $sId]);
            }

            $db->commit();

            foreach ($siswaIds as $sId) {
                \App\Helpers\CacheInvalidator::clearStudentCache($sId, $tenantId);
            }

            // Pembersihan File Pasca-Commit Sukses
            foreach ($oldPathsToDelete as $oldFile) {
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $this->jsonResponse(['success' => true, 'message' => 'Data prestasi siswa berhasil diperbarui.']);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Hapus berkas baru jika gagal
            foreach ($newUploadedPaths as $newFile) {
                if (file_exists($newFile)) {
                    @unlink($newFile);
                }
            }
            error_log('[BKController::apiUpdatePrestasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memperbarui data prestasi. ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Tab 6 — Delete Prestasi Siswa (Soft Delete)
    // POST /api/v1/bk/prestasi/delete
    // =========================================================================
    public function apiDeletePrestasi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant tidak terdeteksi.'], 400);
            return;
        }

        $body       = $this->getJsonInput();
        $idPrestasi = $this->sanitize($body['id'] ?? '');

        if (empty($idPrestasi)) {
            $this->jsonResponse(['error' => 'ID Prestasi tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();

            // Get student IDs before soft-deleting prestasi
            $stmtAnggota = $db->prepare("SELECT id_siswa FROM prestasi_siswa_anggota WHERE id_prestasi = ?");
            $stmtAnggota->execute([$idPrestasi]);
            $siswaIds = $stmtAnggota->fetchAll(\PDO::FETCH_COLUMN);

            // Soft delete
            $stmt = $db->prepare("UPDATE prestasi_siswa SET deleted_at = NOW() WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$idPrestasi, $tenantId]);

            foreach ($siswaIds as $sId) {
                \App\Helpers\CacheInvalidator::clearStudentCache($sId, $tenantId);
            }

            $this->jsonResponse(['success' => true, 'message' => 'Data prestasi berhasil dihapus.']);
        } catch (\Throwable $e) {
            error_log('[BKController::apiDeletePrestasi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus data prestasi.'], 500);
        }
    }

    // =========================================================================
    // HELPER: Otomatisasi Poin Prestasi
    // =========================================================================
    private function calculatePoints(string $tingkat, string $juara): int {
        $tingkat = strtolower($tingkat);
        $juara   = strtolower($juara);

        if (str_contains($tingkat, 'internasional')) {
            if (str_contains($juara, '1')) return 100;
            if (str_contains($juara, '2')) return 95;
            if (str_contains($juara, '3')) return 90;
            if (str_contains($juara, 'harapan 1')) return 85;
            if (str_contains($juara, 'harapan 2')) return 80;
            if (str_contains($juara, 'harapan 3')) return 75;
            return 70; // manual / lainnya
        } elseif (str_contains($tingkat, 'nasional')) {
            if (str_contains($juara, '1')) return 90;
            if (str_contains($juara, '2')) return 85;
            if (str_contains($juara, '3')) return 80;
            if (str_contains($juara, 'harapan 1')) return 75;
            if (str_contains($juara, 'harapan 2')) return 70;
            if (str_contains($juara, 'harapan 3')) return 65;
            return 60;
        } elseif (str_contains($tingkat, 'provinsi')) {
            if (str_contains($juara, '1')) return 80;
            if (str_contains($juara, '2')) return 75;
            if (str_contains($juara, '3')) return 70;
            if (str_contains($juara, 'harapan 1')) return 65;
            if (str_contains($juara, 'harapan 2')) return 60;
            if (str_contains($juara, 'harapan 3')) return 55;
            return 50;
        } else { // Kota/Kabupaten
            if (str_contains($juara, '1')) return 70;
            if (str_contains($juara, '2')) return 65;
            if (str_contains($juara, '3')) return 60;
            if (str_contains($juara, 'harapan 1')) return 55;
            if (str_contains($juara, 'harapan 2')) return 50;
            if (str_contains($juara, 'harapan 3')) return 45;
            return 40;
        }
    }

    // =========================================================================
    // HELPER: Upload berkas pendukung
    // =========================================================================
    private function validateAndUploadFiles(
        string $uploadDir, string $basePath,
        ?string &$fotoBukti, ?string &$fotoSiswa, ?string &$fotoKegiatan, ?string &$suratTugas
    ): void {
        $fileKeys = [
            'foto_bukti_prestasi' => &$fotoBukti,
            'foto_siswa_prestasi' => &$fotoSiswa,
            'foto_kegiatan_lomba' => &$fotoKegiatan,
            'surat_tugas_pdf'     => &$suratTugas
        ];

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($fileKeys as $formKey => &$dbVal) {
            if (isset($_FILES[$formKey]) && $_FILES[$formKey]['error'] === UPLOAD_ERR_OK) {
                $tmpName   = $_FILES[$formKey]['tmp_name'];
                $fileName  = $_FILES[$formKey]['name'];
                $fileSize  = $_FILES[$formKey]['size'];
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExt = ($formKey === 'surat_tugas_pdf') ? ['pdf', 'jpg', 'jpeg', 'png'] : ['jpg', 'jpeg', 'png'];
                if (!in_array($extension, $allowedExt, true)) {
                    throw new \Exception("Format berkas {$formKey} tidak diizinkan.");
                }
                if ($fileSize > 1024 * 1024) {
                    throw new \Exception("Ukuran berkas {$formKey} melebihi batas 1 MB.");
                }

                // Kompres dan simpan menggunakan FileCompressor
                try {
                    if ($extension === 'pdf') {
                        $result = FileCompressor::processPdf($tmpName, $uploadDir, 512 * 1024);
                    } else {
                        $result = FileCompressor::compressImage($tmpName, $uploadDir, 1200, 80);
                    }
                } catch (\RuntimeException $e) {
                    throw new \Exception("Berkas {$formKey}: " . $e->getMessage());
                }

                $newFileName = $result['filename'];
                $destPath    = $result['path'];

                $dbVal = $basePath . $newFileName;
            }
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================
    private function sanitize(mixed $val): string {
        if (!is_string($val)) return '';
        return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
    }

    /** Tulis baris ke tabel pilihan_penjurusan_log (Audit Trail) */
    private function writeLog(\PDO $db, array $d): void {
        $db->prepare("
            INSERT INTO pilihan_penjurusan_log
                (id_pilihan, id_siswa, tenant_id, id_jurusan_lama, id_jurusan_baru,
                 status_lama, status_baru, aksi, dilakukan_oleh, nama_pelaku, catatan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $d['id_pilihan'],     $d['id_siswa'],       $d['tenant_id'],
            $d['id_jurusan_lama'],$d['id_jurusan_baru'],$d['status_lama'],
            $d['status_baru'],    $d['aksi'],            $d['dilakukan_oleh'],
            $d['nama_pelaku'],    $d['catatan'],
        ]);
    }

    // =========================================================================
    // API: Ambil Absensi Semester Kehadiran (Tab Kehadiran)
    // GET /api/v1/bk/absensi-semester?tahun_ajaran_id=X&semester=Y&kelas_id=Z
    // =========================================================================
    public function apiGetAbsensiSemester(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        $tahunAjaranId = (int)($_GET['tahun_ajaran_id'] ?? 0);
        $semester = $this->sanitize($_GET['semester'] ?? '');
        $kelasId = (int)($_GET['kelas_id'] ?? 0);

        if (!$tahunAjaranId || !$semester || !$kelasId) {
            $this->jsonResponse(['error' => 'Tahun Ajaran, Semester, dan Kelas wajib dipilih.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $sql = "SELECT 
                        s.id AS siswa_id,
                        s.nama_lengkap,
                        s.nisn,
                        s.nis,
                        s.jenis_kelamin,
                        COALESCE(a.sakit, 0) AS sakit,
                        COALESCE(a.izin, 0) AS izin,
                        COALESCE(a.alfa, 0) AS alfa,
                        a.id AS absensi_id
                    FROM siswa s
                    LEFT JOIN absensi_semester a ON s.id = a.siswa_id 
                        AND a.tahun_ajaran_id = :tahun_ajaran_id 
                        AND a.semester = :semester
                        AND a.tenant_id = :tenant_id_absensi
                    WHERE s.id_kelas = :kelas_id 
                      AND s.status = 'Aktif'
                      AND s.deleted_at IS NULL
                      AND s.tenant_id = :tenant_id_siswa
                    ORDER BY s.nama_lengkap ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'tahun_ajaran_id' => $tahunAjaranId,
                'semester' => $semester,
                'kelas_id' => $kelasId,
                'tenant_id_absensi' => $tenantId,
                'tenant_id_siswa' => $tenantId
            ]);

            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiGetAbsensiSemester] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengambil data kehadiran: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Simpan Bulk Absensi Semester Kehadiran (Tab Kehadiran)
    // POST /api/v1/bk/absensi-semester
    // =========================================================================
    public function apiSaveAbsensiSemesterBulk(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        $body = $this->getJsonInput();
        $tahunAjaranId = (int)($body['tahun_ajaran_id'] ?? 0);
        $semester = $this->sanitize($body['semester'] ?? '');
        $kelasId = (int)($body['kelas_id'] ?? 0);
        $attendance = $body['attendance'] ?? [];

        if (!$tahunAjaranId || !$semester || !$kelasId) {
            $this->jsonResponse(['error' => 'Parameter tidak lengkap.'], 422);
            return;
        }

        if (!is_array($attendance)) {
            $this->jsonResponse(['error' => 'Format data kehadiran tidak valid.'], 422);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $db->beginTransaction();

            $checkStmt = $db->prepare("
                SELECT id FROM absensi_semester 
                WHERE tenant_id = :tenant_id 
                  AND siswa_id = :siswa_id 
                  AND tahun_ajaran_id = :tahun_ajaran_id 
                  AND semester = :semester 
                LIMIT 1
            ");

            $insertStmt = $db->prepare("
                INSERT INTO absensi_semester (
                    tenant_id, siswa_id, tahun_ajaran_id, semester, sakit, izin, alfa
                ) VALUES (
                    :tenant_id, :siswa_id, :tahun_ajaran_id, :semester, :sakit, :izin, :alfa
                )
            ");

            $updateStmt = $db->prepare("
                UPDATE absensi_semester SET 
                    sakit = :sakit,
                    izin = :izin,
                    alfa = :alfa
                WHERE id = :id
            ");

            $verifySiswaStmt = $db->prepare("
                SELECT id FROM siswa 
                WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1
            ");

            foreach ($attendance as $record) {
                $siswaId = $this->sanitize($record['siswa_id'] ?? '');
                $sakit = max(0, (int)($record['sakit'] ?? 0));
                $izin = max(0, (int)($record['izin'] ?? 0));
                $alfa = max(0, (int)($record['alfa'] ?? 0));

                if (empty($siswaId)) {
                    continue;
                }

                $verifySiswaStmt->execute([$siswaId, $tenantId]);
                if (!$verifySiswaStmt->fetchColumn()) {
                    throw new \Exception("Siswa dengan ID {$siswaId} tidak terdaftar di sekolah ini.");
                }

                $checkStmt->execute([
                    'tenant_id' => $tenantId,
                    'siswa_id' => $siswaId,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'semester' => $semester
                ]);
                $existingId = $checkStmt->fetchColumn();

                if ($existingId) {
                    $updateStmt->execute([
                        'sakit' => $sakit,
                        'izin' => $izin,
                        'alfa' => $alfa,
                        'id' => $existingId
                    ]);
                } else {
                    $insertStmt->execute([
                        'tenant_id' => $tenantId,
                        'siswa_id' => $siswaId,
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'semester' => $semester,
                        'sakit' => $sakit,
                        'izin' => $izin,
                        'alfa' => $alfa
                    ]);
                }
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Data kehadiran berhasil disimpan.']);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[BKController::apiSaveAbsensiSemesterBulk] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan data kehadiran: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Ekspor Excel Absensi Semester (.xls dengan mso-number-format)
    // GET /api/v1/bk/absensi-semester/export?tahun_ajaran_id=X&semester=Y&kelas_id=Z
    // =========================================================================
    public function apiExportAbsensiSemester(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            die("Pilih sekolah terlebih dahulu.");
        }

        $tahunAjaranId = (int)($_GET['tahun_ajaran_id'] ?? 0);
        $semester = $this->sanitize($_GET['semester'] ?? '');
        $kelasId = (int)($_GET['kelas_id'] ?? 0);

        if (!$tahunAjaranId || !$semester || !$kelasId) {
            die("Tahun Ajaran, Semester, dan Kelas wajib dipilih.");
        }

        try {
            $db = \App\Config\Database::getConnection();

            // Ambil info nama sekolah, kelas, tahun ajaran untuk header & filename
            $stmtTenant = $db->prepare("SELECT nama_sekolah FROM tenants WHERE id = ? LIMIT 1");
            $stmtTenant->execute([$tenantId]);
            $namaSekolah = $stmtTenant->fetchColumn() ?: "Sekolah";

            $stmtKelas = $db->prepare("SELECT nama_kelas FROM kelas WHERE id = ? AND tenant_id = ? LIMIT 1");
            $stmtKelas->execute([$kelasId, $tenantId]);
            $namaKelas = $stmtKelas->fetchColumn() ?: "Kelas";

            $stmtTa = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ? AND tenant_id = ? LIMIT 1");
            $stmtTa->execute([$tahunAjaranId, $tenantId]);
            $tahunAjaranStr = $stmtTa->fetchColumn() ?: "TahunAjaran";

            $filename = "Absensi_" . str_replace(' ', '_', $namaKelas) . "_" . str_replace('/', '-', $tahunAjaranStr) . "_" . $semester . "_" . date('Ymd_His') . ".xlsx";

            // Query data siswa + absensi
            $sql = "SELECT 
                        s.nama_lengkap,
                        s.nisn,
                        s.nis,
                        s.jenis_kelamin,
                        s.agama,
                        s.tenant_id,
                        COALESCE(a.sakit, 0) AS sakit,
                        COALESCE(a.izin, 0) AS izin,
                        COALESCE(a.alfa, 0) AS alfa
                    FROM siswa s
                    LEFT JOIN absensi_semester a ON s.id = a.siswa_id 
                        AND a.tahun_ajaran_id = :tahun_ajaran_id 
                        AND a.semester = :semester
                        AND a.tenant_id = :tenant_id_absensi
                    WHERE s.id_kelas = :kelas_id 
                      AND s.status = 'Aktif'
                      AND s.deleted_at IS NULL
                      AND s.tenant_id = :tenant_id_siswa
                    ORDER BY s.nama_lengkap ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'tahun_ajaran_id' => $tahunAjaranId,
                'semester' => $semester,
                'kelas_id' => $kelasId,
                'tenant_id_absensi' => $tenantId,
                'tenant_id_siswa' => $tenantId
            ]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Prepare data array for SimpleXLSXGen
            $excelData = [];
            $excelData[] = ['REKAPITULASI KEHADIRAN SISWA PER SEMESTER'];
            $excelData[] = ['Sekolah:', $namaSekolah];
            $excelData[] = ['Kelas:', $namaKelas];
            $excelData[] = ['Tahun Ajaran:', $tahunAjaranStr];
            $excelData[] = ['Semester:', $semester];
            $excelData[] = []; // Empty separator line
            
            $excelData[] = [
                'UUID Sekolah',
                'Nama Lengkap',
                'NISN',
                'NIS',
                'Jenis Kelamin',
                'Agama',
                'Sakit',
                'Izin',
                'Tanpa Keterangan (Alfa)'
            ];

            foreach ($rows as $row) {
                $excelData[] = [
                    (string)($row['tenant_id'] ?? ''),
                    (string)($row['nama_lengkap'] ?? ''),
                    // SimpleXLSXGen will automatically treat numeric strings as text if type-hinted or simple string, keeping leading zeros
                    (string)($row['nisn'] ?? ''),
                    (string)($row['nis'] ?? ''),
                    (string)($row['jenis_kelamin'] ?? ''),
                    (string)($row['agama'] ?? ''),
                    (int)($row['sakit'] ?? 0),
                    (int)($row['izin'] ?? 0),
                    (int)($row['alfa'] ?? 0)
                ];
            }

            \Shuchkin\SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
            exit;

        } catch (\Throwable $e) {
            die("Gagal mengekspor data kehadiran: " . $e->getMessage());
        }
    }

    // =========================================================================
    // API: Impor CSV Absensi Semester Kehadiran (Excel Save As CSV)
    // POST /api/v1/bk/absensi-semester/import
    // =========================================================================
    public function apiImportAbsensiSemester(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        $tahunAjaranId = (int)($_POST['tahun_ajaran_id'] ?? 0);
        $semester = $this->sanitize($_POST['semester'] ?? '');
        $kelasId = (int)($_POST['kelas_id'] ?? 0);

        if (!$tahunAjaranId || !$semester || !$kelasId) {
            $this->jsonResponse(['error' => 'Parameter filter (Tahun Ajaran, Semester, Kelas) tidak lengkap.'], 422);
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'File upload tidak ditemukan.'], 400);
            return;
        }

        $fileTmp = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'xlsx') {
            $this->jsonResponse(['error' => 'Format file tidak valid. Unggah berkas Excel (.xlsx) hasil ekspor template.'], 400);
            return;
        }

        $xlsx = \Shuchkin\SimpleXLSX::parse($fileTmp);
        if (!$xlsx) {
            $this->jsonResponse(['error' => 'Gagal membaca berkas Excel (.xlsx): ' . \Shuchkin\SimpleXLSX::parseError()], 400);
            return;
        }
        $rows = $xlsx->rows();

        if (empty($rows)) {
            $this->jsonResponse(['error' => 'Berkas kosong atau tidak dapat dibaca.'], 400);
            return;
        }

        $header = null;
        $headerRowNumber = 0;
        $tenantIdx = -1;
        $nisnIdx = -1;
        $sakitIdx = -1;
        $izinIdx = -1;
        $alfaIdx = -1;

        foreach ($rows as $rNum => $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            // Clean UTF-8 BOM on the first cell of this row if present
            if (isset($row[0])) {
                $row[0] = preg_replace('/^[\x{FEFF}\x{200B}]+/u', '', $row[0]);
            }

            // Normalize cells for comparison
            $normalizedRow = array_map(function($cell) {
                return strtolower(trim((string)$cell));
            }, $row);

            // Let's check if this row contains required header signatures
            $tempTenantIdx = -1;
            $tempNisnIdx = -1;
            $tempSakitIdx = -1;
            $tempIzinIdx = -1;
            $tempAlfaIdx = -1;

            foreach ($normalizedRow as $idx => $col) {
                if (strpos($col, 'uuid') !== false || strpos($col, 'tenant') !== false || strpos($col, 'sekolah') !== false) {
                    $tempTenantIdx = $idx;
                } elseif (strpos($col, 'nisn') !== false) {
                    $tempNisnIdx = $idx;
                } elseif (strpos($col, 'sakit') !== false) {
                    $tempSakitIdx = $idx;
                } elseif (strpos($col, 'izin') !== false) {
                    $tempIzinIdx = $idx;
                } elseif (strpos($col, 'alfa') !== false || strpos($col, 'tanpa keterangan') !== false || strpos($col, 'keterangan') !== false) {
                    $tempAlfaIdx = $idx;
                }
            }

            // If we found all required columns, this is the header!
            if ($tempTenantIdx !== -1 && $tempNisnIdx !== -1 && $tempSakitIdx !== -1 && $tempIzinIdx !== -1 && $tempAlfaIdx !== -1) {
                $header = $row;
                $headerRowNumber = $rNum + 1;
                $tenantIdx = $tempTenantIdx;
                $nisnIdx = $tempNisnIdx;
                $sakitIdx = $tempSakitIdx;
                $izinIdx = $tempIzinIdx;
                $alfaIdx = $tempAlfaIdx;
                break;
            }

            // Stop looking after 20 rows
            if ($rNum >= 20) {
                break;
            }
        }

        if (!$header) {
            $this->jsonResponse([
                'error' => 'Header berkas tidak valid.',
                'details' => 'Kolom yang wajib ada di dalam template: UUID Sekolah, NISN, Sakit, Izin, Tanpa Keterangan (Alfa).'
            ], 400);
            return;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $db->beginTransaction();

            $rowCount = $headerRowNumber;
            $successCount = 0;
            $errors = [];

            // Prepared statements
            $stmtSiswaCheck = $db->prepare("
                SELECT id, tenant_id FROM siswa 
                WHERE nisn = :nisn 
                  AND deleted_at IS NULL 
                LIMIT 1
            ");

            $checkAbsensiStmt = $db->prepare("
                SELECT id FROM absensi_semester 
                WHERE tenant_id = :tenant_id 
                  AND siswa_id = :siswa_id 
                  AND tahun_ajaran_id = :tahun_ajaran_id 
                  AND semester = :semester 
                LIMIT 1
            ");

            $insertStmt = $db->prepare("
                INSERT INTO absensi_semester (
                    tenant_id, siswa_id, tahun_ajaran_id, semester, sakit, izin, alfa
                ) VALUES (
                    :tenant_id, :siswa_id, :tahun_ajaran_id, :semester, :sakit, :izin, :alfa
                )
            ");

            $updateStmt = $db->prepare("
                UPDATE absensi_semester SET 
                    sakit = :sakit,
                    izin = :izin,
                    alfa = :alfa
                WHERE id = :id
            ");

            for ($i = $headerRowNumber; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowCount++;
                if (empty(array_filter($row))) {
                    continue;
                }

                $rowTenantId = isset($row[$tenantIdx]) ? trim($row[$tenantIdx]) : '';
                // Clean NISN as text
                $rawNisn = isset($row[$nisnIdx]) ? trim($row[$nisnIdx]) : '';
                // Excel sometimes formats numbers in scientific notation or with quotes, clean it
                $rawNisn = preg_replace('/[^0-9]/', '', $rawNisn);

                $sakit = isset($row[$sakitIdx]) ? (int)trim($row[$sakitIdx]) : 0;
                $izin = isset($row[$izinIdx]) ? (int)trim($row[$izinIdx]) : 0;
                $alfa = isset($row[$alfaIdx]) ? (int)trim($row[$alfaIdx]) : 0;

                if (empty($rowTenantId)) {
                    $errors[] = "Baris {$rowCount}: UUID Sekolah tidak boleh kosong.";
                    continue;
                }
                if (strcasecmp($rowTenantId, $tenantId) !== 0) {
                    $errors[] = "Baris {$rowCount}: UUID Sekolah '{$rowTenantId}' tidak cocok dengan sekolah Anda yang sedang aktif.";
                    continue;
                }

                if (empty($rawNisn)) {
                    $errors[] = "Baris {$rowCount}: NISN tidak boleh kosong.";
                    continue;
                }

                // Check student exist by nisn
                $stmtSiswaCheck->execute([
                    'nisn' => $rawNisn
                ]);
                $siswa = $stmtSiswaCheck->fetch(\PDO::FETCH_ASSOC);

                if (!$siswa) {
                    $errors[] = "Baris {$rowCount}: Siswa dengan NISN '{$rawNisn}' tidak ditemukan.";
                    continue;
                }

                if (strcasecmp($siswa['tenant_id'], $tenantId) !== 0) {
                    $errors[] = "Baris {$rowCount}: Siswa dengan NISN '{$rawNisn}' terdaftar di sekolah lain.";
                    continue;
                }

                $siswaId = $siswa['id'];

                // Check if absensi already exists
                $checkAbsensiStmt->execute([
                    'tenant_id' => $tenantId,
                    'siswa_id' => $siswaId,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'semester' => $semester
                ]);
                $existingId = $checkAbsensiStmt->fetchColumn();

                if ($existingId) {
                    // Update
                    $updateStmt->execute([
                        'sakit' => max(0, $sakit),
                        'izin' => max(0, $izin),
                        'alfa' => max(0, $alfa),
                        'id' => $existingId
                    ]);
                } else {
                    // Insert
                    $insertStmt->execute([
                        'tenant_id' => $tenantId,
                        'siswa_id' => $siswaId,
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'semester' => $semester,
                        'sakit' => max(0, $sakit),
                        'izin' => max(0, $izin),
                        'alfa' => max(0, $alfa)
                    ]);
                }

                $successCount++;
            }

            if (!empty($errors)) {
                $db->rollBack();
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Gagal memproses file. Beberapa baris data tidak valid.',
                    'errors' => $errors
                ], 422);
                return;
            }

            $db->commit();
            $this->jsonResponse([
                'success' => true,
                'message' => "Berhasil mengimpor {$successCount} data kehadiran siswa."
            ]);

        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[BKController::apiImportAbsensiSemester] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // STUDENT VIOLATIONS & POINTS (PELANGGARAN & POIN) IMPLEMENTATION
    // =========================================================================

    /** Helper to get active school year */
    private function getActiveTahunAjaranId(\PDO $db, string $tenantId): ?int {
        $stmt = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$tenantId]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int)$id;
        }
        $stmt = $db->prepare("SELECT id FROM tahun_ajaran WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY tahun_ajaran DESC LIMIT 1");
        $stmt->execute([$tenantId]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    /** GET /api/v1/bk/pelanggaran/dashboard */
    public function apiGetPelanggaranDashboard(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();
        $taId = $this->getActiveTahunAjaranId($db, $tenantId);
        if (!$taId) {
            $this->jsonResponse([
                'success' => true,
                'kpi' => ['sp3_do' => 0, 'sp2_skorsing' => 0, 'sp1_bk' => 0, 'wali_kelas' => 0, 'total_siswa_melanggar' => 0],
                'top_students' => [],
                'chart' => ['labels' => [], 'data' => []]
            ]);
        }

        try {
            // 1. KPI Counters
            $stmt = $db->prepare("
                SELECT 
                    SUM(CASE WHEN total_poin >= 100 THEN 1 ELSE 0 END) as sp3_do,
                    SUM(CASE WHEN total_poin >= 75 AND total_poin < 100 THEN 1 ELSE 0 END) as sp2_skorsing,
                    SUM(CASE WHEN total_poin >= 50 AND total_poin < 75 THEN 1 ELSE 0 END) as sp1_bk,
                    SUM(CASE WHEN total_poin >= 25 AND total_poin < 50 THEN 1 ELSE 0 END) as wali_kelas,
                    COUNT(siswa_id) as total_siswa_melanggar
                FROM (
                    SELECT c.siswa_id, SUM(m.bobot_poin) as total_poin
                    FROM catatan_pelanggaran_siswa c
                    JOIN master_pelanggaran m ON c.pelanggaran_id = m.id
                    WHERE c.tenant_id = ? 
                      AND c.tahun_ajaran_id = ? 
                      AND c.deleted_at IS NULL
                      AND m.deleted_at IS NULL
                    GROUP BY c.siswa_id
                ) as student_points
            ");
            $stmt->execute([$tenantId, $taId]);
            $counts = $stmt->fetch(\PDO::FETCH_ASSOC);

            $kpi = [
                'sp3_do' => (int)($counts['sp3_do'] ?? 0),
                'sp2_skorsing' => (int)($counts['sp2_skorsing'] ?? 0),
                'sp1_bk' => (int)($counts['sp1_bk'] ?? 0),
                'wali_kelas' => (int)($counts['wali_kelas'] ?? 0),
                'total_siswa_melanggar' => (int)($counts['total_siswa_melanggar'] ?? 0),
            ];

            // 2. Top Violators
            $stmt = $db->prepare("
                SELECT 
                    s.id as siswa_id,
                    s.nama_lengkap,
                    s.nisn,
                    k.nama_kelas,
                    SUM(m.bobot_poin) as total_poin
                FROM catatan_pelanggaran_siswa c
                JOIN siswa s ON c.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                JOIN master_pelanggaran m ON c.pelanggaran_id = m.id
                WHERE c.tenant_id = ? 
                  AND c.tahun_ajaran_id = ? 
                  AND c.deleted_at IS NULL
                  AND s.deleted_at IS NULL
                  AND m.deleted_at IS NULL
                GROUP BY s.id, s.nama_lengkap, s.nisn, k.nama_kelas
                ORDER BY total_poin DESC
                LIMIT 5
            ");
            $stmt->execute([$tenantId, $taId]);
            $topStudents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // 3. Monthly Trends
            $stmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(c.tanggal_kejadian, '%Y-%m') as bulan,
                    COUNT(c.id) as jumlah_kasus
                FROM catatan_pelanggaran_siswa c
                WHERE c.tenant_id = ? 
                  AND c.tahun_ajaran_id = ? 
                  AND c.deleted_at IS NULL
                GROUP BY DATE_FORMAT(c.tanggal_kejadian, '%Y-%m')
                ORDER BY bulan ASC
            ");
            $stmt->execute([$tenantId, $taId]);
            $trendData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $months = [];
            $countsList = [];
            $indoMonths = [
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            ];

            foreach ($trendData as $row) {
                $parts = explode('-', $row['bulan']);
                $year = $parts[0];
                $monthNum = $parts[1] ?? '';
                $monthName = ($indoMonths[$monthNum] ?? $monthNum) . ' ' . $year;
                $months[] = $monthName;
                $countsList[] = (int)$row['jumlah_kasus'];
            }

            if (empty($months)) {
                $months = ['Belum ada data'];
                $countsList = [0];
            }

            $this->jsonResponse([
                'success' => true,
                'kpi' => $kpi,
                'top_students' => $topStudents,
                'chart' => [
                    'labels' => $months,
                    'datasets' => [
                        [
                            'label' => 'Jumlah Pelanggaran',
                            'data' => $countsList,
                            'fill' => true,
                            'borderColor' => '#ef4444',
                            'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                            'tension' => 0.4
                        ]
                    ]
                ]
            ]);

        } catch (\Throwable $e) {
            error_log('[BKController::apiGetPelanggaranDashboard] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat dashboard: ' . $e->getMessage()], 500);
        }
    }

    /** GET /api/v1/bk/pelanggaran/master */
    public function apiGetMasterPelanggaran(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();

        try {
            $stmt = $db->prepare("
                SELECT id, kategori, nama_pelanggaran, bobot_poin 
                FROM master_pelanggaran 
                WHERE tenant_id = ? AND deleted_at IS NULL 
                ORDER BY kategori ASC, nama_pelanggaran ASC
            ");
            $stmt->execute([$tenantId]);
            $rules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'data' => $rules]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiGetMasterPelanggaran] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat master aturan: ' . $e->getMessage()], 500);
        }
    }

    /** POST /api/v1/bk/pelanggaran/master */
    public function apiStoreMasterPelanggaran(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();
        $input = $this->getJsonInput();

        $kategori = $this->sanitize($input['kategori'] ?? '');
        $namaPelanggaran = $this->sanitize($input['nama_pelanggaran'] ?? '');
        $bobotPoin = (int)($input['bobot_poin'] ?? 0);

        if (!in_array($kategori, ['Ringan', 'Sedang', 'Berat', 'Khusus'], true)) {
            $this->jsonResponse(['error' => 'Kategori tidak valid.'], 400);
        }
        if (empty($namaPelanggaran)) {
            $this->jsonResponse(['error' => 'Nama pelanggaran wajib diisi.'], 400);
        }
        if ($bobotPoin <= 0) {
            $this->jsonResponse(['error' => 'Bobot poin harus lebih besar dari 0.'], 400);
        }

        // Generate UUID
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        try {
            $stmt = $db->prepare("
                INSERT INTO master_pelanggaran (id, tenant_id, kategori, nama_pelanggaran, bobot_poin)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$uuid, $tenantId, $kategori, $namaPelanggaran, $bobotPoin]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Aturan pelanggaran berhasil disimpan.',
                'data' => [
                    'id' => $uuid,
                    'kategori' => $kategori,
                    'nama_pelanggaran' => $namaPelanggaran,
                    'bobot_poin' => $bobotPoin
                ]
            ]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiStoreMasterPelanggaran] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan aturan: ' . $e->getMessage()], 500);
        }
    }

    /** POST /api/v1/bk/pelanggaran/master/update */
    public function apiUpdateMasterPelanggaran(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();
        $input = $this->getJsonInput();

        $id = $this->sanitize($input['id'] ?? '');
        $kategori = $this->sanitize($input['kategori'] ?? '');
        $namaPelanggaran = $this->sanitize($input['nama_pelanggaran'] ?? '');
        $bobotPoin = (int)($input['bobot_poin'] ?? 0);

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID aturan tidak valid.'], 400);
        }
        if (!in_array($kategori, ['Ringan', 'Sedang', 'Berat', 'Khusus'], true)) {
            $this->jsonResponse(['error' => 'Kategori tidak valid.'], 400);
        }
        if (empty($namaPelanggaran)) {
            $this->jsonResponse(['error' => 'Nama pelanggaran wajib diisi.'], 400);
        }
        if ($bobotPoin <= 0) {
            $this->jsonResponse(['error' => 'Bobot poin harus lebih besar dari 0.'], 400);
        }

        try {
            // Verify ownership
            $stmt = $db->prepare("SELECT 1 FROM master_pelanggaran WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$id, $tenantId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'Aturan tidak ditemukan atau bukan milik sekolah Anda.'], 404);
            }

            $stmt = $db->prepare("
                UPDATE master_pelanggaran 
                SET kategori = ?, nama_pelanggaran = ?, bobot_poin = ?
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$kategori, $namaPelanggaran, $bobotPoin, $id, $tenantId]);

            $this->jsonResponse(['success' => true, 'message' => 'Aturan pelanggaran berhasil diubah.']);
        } catch (\Throwable $e) {
            error_log('[BKController::apiUpdateMasterPelanggaran] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengubah aturan: ' . $e->getMessage()], 500);
        }
    }

    /** POST /api/v1/bk/pelanggaran/master/delete */
    public function apiDeleteMasterPelanggaran(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();
        $input = $this->getJsonInput();

        $id = $this->sanitize($input['id'] ?? '');

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID aturan tidak valid.'], 400);
        }

        try {
            // Verify ownership
            $stmt = $db->prepare("SELECT 1 FROM master_pelanggaran WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$id, $tenantId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'Aturan tidak ditemukan atau bukan milik sekolah Anda.'], 404);
            }

            // Soft-delete
            $stmt = $db->prepare("
                UPDATE master_pelanggaran 
                SET deleted_at = NOW() 
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$id, $tenantId]);

            $this->jsonResponse(['success' => true, 'message' => 'Aturan pelanggaran berhasil dihapus.']);
        } catch (\Throwable $e) {
            error_log('[BKController::apiDeleteMasterPelanggaran] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus aturan: ' . $e->getMessage()], 500);
        }
    }

    /** GET /api/v1/bk/pelanggaran/catatan */
    public function apiGetCatatanPelanggaran(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();
        $taId = $this->getActiveTahunAjaranId($db, $tenantId);
        if (!$taId) {
            $this->jsonResponse(['success' => true, 'data' => []]);
        }

        try {
            $stmt = $db->prepare("
                SELECT 
                    c.id,
                    c.siswa_id,
                    c.pelanggaran_id,
                    c.tanggal_kejadian,
                    c.catatan_keterangan,
                    c.foto_bukti,
                    s.nama_lengkap as nama_siswa,
                    s.nisn,
                    k.nama_kelas,
                    m.kategori,
                    m.nama_pelanggaran,
                    m.bobot_poin
                FROM catatan_pelanggaran_siswa c
                JOIN siswa s ON c.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                JOIN master_pelanggaran m ON c.pelanggaran_id = m.id
                WHERE c.tenant_id = ? 
                  AND c.tahun_ajaran_id = ? 
                  AND c.deleted_at IS NULL
                ORDER BY c.tanggal_kejadian DESC, c.created_at DESC
            ");
            $stmt->execute([$tenantId, $taId]);
            $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'data' => $records]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiGetCatatanPelanggaran] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat catatan: ' . $e->getMessage()], 500);
        }
    }

    /** POST /api/v1/bk/pelanggaran/catatan */
    public function apiStoreCatatanPelanggaran(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();

        $siswaId = $this->sanitize($_POST['siswa_id'] ?? '');
        $pelanggaranId = $this->sanitize($_POST['pelanggaran_id'] ?? '');
        $tanggalKejadian = $this->sanitize($_POST['tanggal_kejadian'] ?? '');
        $catatanKeterangan = $this->sanitize($_POST['catatan_keterangan'] ?? '');

        if (empty($siswaId) || empty($pelanggaranId) || empty($tanggalKejadian)) {
            $this->jsonResponse(['error' => 'Siswa, jenis pelanggaran, dan tanggal kejadian wajib diisi.'], 400);
        }

        $taId = $this->getActiveTahunAjaranId($db, $tenantId);
        if (!$taId) {
            $this->jsonResponse(['error' => 'Tahun Ajaran aktif tidak ditemukan.'], 400);
        }

        try {
            // Verify student ownership
            $stmt = $db->prepare("SELECT id FROM siswa WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$siswaId, $tenantId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'Siswa tidak ditemukan atau bukan dari sekolah Anda.'], 404);
            }

            // Verify violation rule ownership
            $stmt = $db->prepare("SELECT id FROM master_pelanggaran WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$pelanggaranId, $tenantId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'Aturan pelanggaran tidak ditemukan.'], 404);
            }

            // Handle file upload
            $fotoBuktiPath = null;
            if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['foto_bukti']['tmp_name'];
                $fileName    = $_FILES['foto_bukti']['name'];
                $fileSize    = $_FILES['foto_bukti']['size'];

                $fileExtension     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($fileExtension, $allowedExtensions, true)) {
                    $this->jsonResponse(['error' => 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.'], 400);
                }

                $uploadDir = __DIR__ . '/../../storage/pelanggaran/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                try {
                    $result      = FileCompressor::compressImage($fileTmpPath, $uploadDir, 1200, 80);
                    $newFileName = $result['filename'];
                    $fotoBuktiPath = 'storage/pelanggaran/' . $newFileName;
                } catch (\RuntimeException $e) {
                    $this->jsonResponse(['error' => 'Gagal memproses foto: ' . $e->getMessage()], 400);
                }
            }

            // Generate UUID
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $guruPelaporId = $_SESSION['user_id'] ?? null;

            $stmt = $db->prepare("
                INSERT INTO catatan_pelanggaran_siswa 
                    (id, tenant_id, siswa_id, tahun_ajaran_id, pelanggaran_id, tanggal_kejadian, catatan_keterangan, guru_pelapor_id, foto_bukti)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $uuid,
                $tenantId,
                $siswaId,
                $taId,
                $pelanggaranId,
                $tanggalKejadian,
                $catatanKeterangan,
                $guruPelaporId,
                $fotoBuktiPath
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Laporan pelanggaran berhasil disimpan.']);

        } catch (\Throwable $e) {
            error_log('[BKController::apiStoreCatatanPelanggaran] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mencatat pelanggaran: ' . $e->getMessage()], 500);
        }
    }

    /** GET /api/v1/bk/pelanggaran/sanksi */
    public function apiGetSanksiBuku(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();
        $taId = $this->getActiveTahunAjaranId($db, $tenantId);
        if (!$taId) {
            $this->jsonResponse(['success' => true, 'data' => []]);
        }

        try {
            $stmt = $db->prepare("
                SELECT 
                    s.id as siswa_id,
                    s.nama_lengkap,
                    s.nisn,
                    k.nama_kelas,
                    SUM(m.bobot_poin) as total_poin
                FROM catatan_pelanggaran_siswa c
                JOIN siswa s ON c.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                JOIN master_pelanggaran m ON c.pelanggaran_id = m.id
                WHERE c.tenant_id = ? 
                  AND c.tahun_ajaran_id = ? 
                  AND c.deleted_at IS NULL
                  AND s.deleted_at IS NULL
                  AND m.deleted_at IS NULL
                GROUP BY s.id, s.nama_lengkap, s.nisn, k.nama_kelas
                ORDER BY total_poin DESC
            ");
            $stmt->execute([$tenantId, $taId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $results = [];
            foreach ($rows as $row) {
                $points = (int)$row['total_poin'];
                
                $statusLabel = 'Aman';
                $statusColor = 'success';
                $sanksiDetail = 'Tidak ada sanksi aktif.';
                
                if ($points >= 100) {
                    $statusLabel = 'Bahaya (SP 3 / DO)';
                    $statusColor = 'danger';
                    $sanksiDetail = 'Surat Peringatan 3 (SP 3) diterbitkan, evaluasi pleno sekolah untuk pengembalian ke Orang Tua.';
                } elseif ($points >= 75) {
                    $statusLabel = 'Peringatan 2 (SP 2 / Skorsing)';
                    $statusColor = 'warning';
                    $sanksiDetail = 'Surat Peringatan 2 (SP 2) & Skorsing Akademik selama 3 Hari Sekolah.';
                } elseif ($points >= 50) {
                    $statusLabel = 'Peringatan 1 (SP 1 / Panggilan Orang Tua)';
                    $statusColor = 'info';
                    $sanksiDetail = 'Surat Peringatan 1 (SP 1) diterbitkan & Pemanggilan Orang Tua oleh Guru BK.';
                } elseif ($points >= 25) {
                    $statusLabel = 'Peringatan Wali Kelas';
                    $statusColor = 'secondary';
                    $sanksiDetail = 'Peringatan persuasif dari Wali Kelas & Pembinaan khusus.';
                }
                
                $results[] = [
                    'siswa_id' => $row['siswa_id'],
                    'nama_lengkap' => $row['nama_lengkap'],
                    'nisn' => $row['nisn'],
                    'nama_kelas' => $row['nama_kelas'] ?? '-',
                    'total_poin' => $points,
                    'status_label' => $statusLabel,
                    'status_color' => $statusColor,
                    'sanksi_detail' => $sanksiDetail
                ];
            }

            $this->jsonResponse(['success' => true, 'data' => $results]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiGetSanksiBuku] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat buku catatan sanksi: ' . $e->getMessage()], 500);
        }
    }

    /** POST /api/v1/bk/pelanggaran/catatan/update */
    public function apiUpdateCatatanPelanggaran(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();

        $id = $this->sanitize($_POST['id'] ?? '');
        $pelanggaranId = $this->sanitize($_POST['pelanggaran_id'] ?? '');
        $tanggalKejadian = $this->sanitize($_POST['tanggal_kejadian'] ?? '');
        $catatanKeterangan = $this->sanitize($_POST['catatan_keterangan'] ?? '');

        if (empty($id) || empty($pelanggaranId) || empty($tanggalKejadian)) {
            $this->jsonResponse(['error' => 'Data tidak lengkap.'], 400);
        }

        try {
            // Verify ownership of the violation report
            $stmt = $db->prepare("SELECT foto_bukti FROM catatan_pelanggaran_siswa WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$id, $tenantId]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$existing) {
                $this->jsonResponse(['error' => 'Catatan pelanggaran tidak ditemukan.'], 404);
            }

            // Verify violation rule ownership
            $stmt = $db->prepare("SELECT id FROM master_pelanggaran WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$pelanggaranId, $tenantId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'Aturan pelanggaran tidak ditemukan.'], 404);
            }

            // Handle file upload if a new one is sent
            $fotoBuktiPath = $existing['foto_bukti'];
            if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['foto_bukti']['tmp_name'];
                $fileName = $_FILES['foto_bukti']['name'];
                $fileSize = $_FILES['foto_bukti']['size'];
                
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (!in_array($fileExtension, $allowedExtensions, true)) {
                    $this->jsonResponse(['error' => 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.'], 400);
                }
                
                if ($fileSize > 2 * 1024 * 1024) {
                    $this->jsonResponse(['error' => 'Ukuran foto bukti maksimal adalah 2MB.'], 400);
                }
                
                $uploadDir = __DIR__ . '/../../storage/pelanggaran/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                try {
                    $result      = FileCompressor::compressImage($fileTmpPath, $uploadDir, 1200, 80);
                    $newFileName = $result['filename'];
                    // Hapus foto lama
                    if ($existing['foto_bukti'] && file_exists(__DIR__ . '/../../' . $existing['foto_bukti'])) {
                        @unlink(__DIR__ . '/../../' . $existing['foto_bukti']);
                    }
                    $fotoBuktiPath = 'storage/pelanggaran/' . $newFileName;
                } catch (\RuntimeException $e) {
                    $this->jsonResponse(['error' => 'Gagal memproses foto: ' . $e->getMessage()], 400);
                }
            }

            $stmt = $db->prepare("
                UPDATE catatan_pelanggaran_siswa 
                SET pelanggaran_id = ?, tanggal_kejadian = ?, catatan_keterangan = ?, foto_bukti = ?
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([
                $pelanggaranId,
                $tanggalKejadian,
                $catatanKeterangan,
                $fotoBuktiPath,
                $id,
                $tenantId
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Laporan pelanggaran berhasil diperbarui.']);
        } catch (\Throwable $e) {
            error_log('[BKController::apiUpdateCatatanPelanggaran] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memperbarui catatan: ' . $e->getMessage()], 500);
        }
    }

    /** POST /api/v1/bk/pelanggaran/catatan/delete */
    public function apiDeleteCatatanPelanggaran(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();

        // Standard JSON payload
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $this->sanitize($input['id'] ?? ($_POST['id'] ?? ''));

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID tidak ditemukan.'], 400);
        }

        try {
            $stmt = $db->prepare("
                UPDATE catatan_pelanggaran_siswa 
                SET deleted_at = CURRENT_TIMESTAMP
                WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$id, $tenantId]);

            if ($stmt->rowCount() > 0) {
                $this->jsonResponse(['success' => true, 'message' => 'Laporan pelanggaran berhasil dihapus.']);
            } else {
                $this->jsonResponse(['error' => 'Data tidak ditemukan atau sudah dihapus.'], 404);
            }
        } catch (\Throwable $e) {
            error_log('[BKController::apiDeleteCatatanPelanggaran] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus catatan: ' . $e->getMessage()], 500);
        }
    }

    /** GET /api/v1/bk/pelanggaran/sanksi/detail */
    public function apiGetSanksiDetail(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();
        $taId = $this->getActiveTahunAjaranId($db, $tenantId);
        if (!$taId) {
            $this->jsonResponse(['error' => 'Tahun Ajaran aktif tidak ditemukan.'], 400);
        }

        $siswaId = $this->sanitize($_GET['siswa_id'] ?? '');
        if (empty($siswaId)) {
            $this->jsonResponse(['error' => 'Siswa ID tidak boleh kosong.'], 400);
        }

        try {
            // 1. Get student basic profile
            $stmt = $db->prepare("
                SELECT s.id, s.nama_lengkap, s.nisn, s.nis, k.nama_kelas, j.nama_jurusan
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN jurusan j ON s.id_jurusan = j.id
                WHERE s.id = ? AND s.tenant_id = ? AND s.deleted_at IS NULL
            ");
            $stmt->execute([$siswaId, $tenantId]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$student) {
                $this->jsonResponse(['error' => 'Siswa tidak ditemukan.'], 404);
            }

            // 2. Get violations timeline
            $stmt = $db->prepare("
                SELECT c.id, c.tanggal_kejadian, c.catatan_keterangan, c.foto_bukti,
                       m.kategori, m.nama_pelanggaran, m.bobot_poin
                FROM catatan_pelanggaran_siswa c
                JOIN master_pelanggaran m ON c.pelanggaran_id = m.id
                WHERE c.siswa_id = ? AND c.tenant_id = ? AND c.tahun_ajaran_id = ? AND c.deleted_at IS NULL
                ORDER BY c.tanggal_kejadian DESC, c.created_at DESC
            ");
            $stmt->execute([$siswaId, $tenantId, $taId]);
            $violations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // 3. Get follow-up logs
            $stmt = $db->prepare("
                SELECT t.id, t.tanggal_tindakan, t.jenis_tindakan, t.keterangan_tindakan, u.nama_lengkap as nama_guru
                FROM tindak_lanjut_sanksi t
                LEFT JOIN users u ON t.guru_id = u.id
                WHERE t.siswa_id = ? AND t.tenant_id = ? AND t.tahun_ajaran_id = ? AND t.deleted_at IS NULL
                ORDER BY t.tanggal_tindakan DESC, t.created_at DESC
            ");
            $stmt->execute([$siswaId, $tenantId, $taId]);
            $followUps = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate total points
            $totalPoints = 0;
            foreach ($violations as $v) {
                $totalPoints += (int)$v['bobot_poin'];
            }

            $this->jsonResponse([
                'success' => true,
                'student' => $student,
                'total_poin' => $totalPoints,
                'violations' => $violations,
                'follow_ups' => $followUps
            ]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiGetSanksiDetail] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat detail sanksi: ' . $e->getMessage()], 500);
        }
    }

    /** POST /api/v1/bk/pelanggaran/sanksi/tindak-lanjut */
    public function apiStoreTindakLanjutSanksi(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak valid.'], 400);
        }
        $db = \App\Config\Database::getConnection();
        $taId = $this->getActiveTahunAjaranId($db, $tenantId);
        if (!$taId) {
            $this->jsonResponse(['success' => true, 'data' => []]);
        }

        // Standard JSON payload
        $input = json_decode(file_get_contents('php://input'), true);
        $siswaId = $this->sanitize($input['siswa_id'] ?? ($_POST['siswa_id'] ?? ''));
        $tanggalTindakan = $this->sanitize($input['tanggal_tindakan'] ?? ($_POST['tanggal_tindakan'] ?? ''));
        $jenisTindakan = $this->sanitize($input['jenis_tindakan'] ?? ($_POST['jenis_tindakan'] ?? ''));
        $keteranganTindakan = $this->sanitize($input['keterangan_tindakan'] ?? ($_POST['keterangan_tindakan'] ?? ''));

        if (empty($siswaId) || empty($tanggalTindakan) || empty($jenisTindakan) || empty($keteranganTindakan)) {
            $this->jsonResponse(['error' => 'Seluruh form tindak lanjut wajib diisi.'], 400);
        }

        try {
            // Verify student ownership
            $stmt = $db->prepare("SELECT id FROM siswa WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$siswaId, $tenantId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'Siswa tidak ditemukan.'], 404);
            }

            // Generate UUID
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $guruId = $_SESSION['user_id'] ?? '';

            $stmt = $db->prepare("
                INSERT INTO tindak_lanjut_sanksi 
                    (id, tenant_id, siswa_id, tahun_ajaran_id, tanggal_tindakan, jenis_tindakan, keterangan_tindakan, guru_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $uuid,
                $tenantId,
                $siswaId,
                $taId,
                $tanggalTindakan,
                $jenisTindakan,
                $keteranganTindakan,
                $guruId
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Catatan tindak lanjut berhasil disimpan.']);
        } catch (\Throwable $e) {
            error_log('[BKController::apiStoreTindakLanjutSanksi] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan tindak lanjut: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: Mendapatkan daftar beasiswa (Tab Beasiswa Siswa)
    // GET /api/v1/bk/beasiswa/list
    // =========================================================================
    public function apiBeasiswaList(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['success' => true, 'data' => []]);
            return;
        }

        $tahunAjaranId = $this->sanitize($_GET['tahun_ajaran_id'] ?? '');

        try {
            $db = \App\Config\Database::getConnection();
            $whereClause = "WHERE rb.tenant_id = :tenant_id";
            $params = ['tenant_id' => $tenantId];

            if (!empty($tahunAjaranId)) {
                $whereClause .= " AND s.id_tahun_ajaran = :tahun_ajaran_id";
                $params['tahun_ajaran_id'] = $tahunAjaranId;
            }

            $sql = "
                SELECT rb.id, rb.jenis_beasiswa, rb.sumber, rb.tahun_menerima, rb.nominal,
                       s.nama_lengkap, s.nisn, k.nama_kelas, ta.tahun_ajaran
                FROM riwayat_beasiswa rb
                JOIN siswa s ON rb.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id
                $whereClause
                ORDER BY rb.tahun_menerima DESC, s.nama_lengkap ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiBeasiswaList] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal mengambil data beasiswa.'], 500);
        }
    }

    // =========================================================================
    // API: Ekspor data beasiswa ke Excel (.xlsx)
    // GET /api/v1/bk/beasiswa/export
    // =========================================================================
    public function apiExportBeasiswa(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            exit('Pilih sekolah terlebih dahulu.');
        }

        $tahunAjaranId = $this->sanitize($_GET['tahun_ajaran_id'] ?? '');

        try {
            $db = \App\Config\Database::getConnection();
            
            // Ambil nama sekolah
            $stmtTenant = $db->prepare("SELECT nama_sekolah FROM tenants WHERE id = ?");
            $stmtTenant->execute([$tenantId]);
            $namaSekolah = $stmtTenant->fetchColumn() ?: 'Sekolah';

            $tahunAjaranStr = 'Semua Tahun Ajaran';
            if (!empty($tahunAjaranId)) {
                $stmtTA = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ?");
                $stmtTA->execute([$tahunAjaranId]);
                $tahunAjaranStr = $stmtTA->fetchColumn() ?: 'Semua Tahun Ajaran';
            }

            $whereClause = "WHERE rb.tenant_id = :tenant_id";
            $params = ['tenant_id' => $tenantId];

            if (!empty($tahunAjaranId)) {
                $whereClause .= " AND s.id_tahun_ajaran = :tahun_ajaran_id";
                $params['tahun_ajaran_id'] = $tahunAjaranId;
            }

            $sql = "
                SELECT rb.jenis_beasiswa, rb.sumber, rb.tahun_menerima, rb.nominal,
                       s.nama_lengkap, s.nisn, k.nama_kelas, ta.tahun_ajaran
                FROM riwayat_beasiswa rb
                JOIN siswa s ON rb.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id
                $whereClause
                ORDER BY rb.tahun_menerima DESC, s.nama_lengkap ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $filename = "Rekap_Beasiswa_" . str_replace(' ', '_', $namaSekolah) . "_" . str_replace('/', '-', $tahunAjaranStr) . "_" . date('Ymd_His') . ".xlsx";

            $excelData = [];
            $excelData[] = ['REKAPITULASI PENERIMA BEASISWA SISWA'];
            $excelData[] = ['Sekolah:', $namaSekolah];
            $excelData[] = ['Tahun Ajaran Filter:', $tahunAjaranStr];
            $excelData[] = []; // Baris pemisah

            $excelData[] = [
                'No.',
                'Nama Lengkap',
                'NISN',
                'Kelas',
                'Tahun Ajaran Masuk',
                'Jenis Beasiswa',
                'Sumber Beasiswa',
                'Tahun Menerima',
                'Nominal (Rp)'
            ];

            $no = 1;
            foreach ($rows as $r) {
                $excelData[] = [
                    $no++,
                    $r['nama_lengkap'],
                    $r['nisn'] ? ' ' . $r['nisn'] : '-',
                    $r['nama_kelas'] ?: 'Belum Masuk Kelas',
                    $r['tahun_ajaran'] ?: '-',
                    $r['jenis_beasiswa'],
                    $r['sumber'] ?: '-',
                    $r['tahun_menerima'],
                    $r['nominal'] ? (float)$r['nominal'] : 0
                ];
            }

            \Shuchkin\SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
        } catch (\Throwable $e) {
            error_log('[BKController::apiExportBeasiswa] ' . $e->getMessage());
            exit('Gagal mengekspor data.');
        }
    }
}
