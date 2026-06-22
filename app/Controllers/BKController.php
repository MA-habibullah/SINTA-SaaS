<?php

namespace App\Controllers;

use App\Core\SessionManager;

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
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $hasAllowedRole = false;
        foreach ($roles as $r) {
            if (in_array($r, self::ALLOWED_ROLES, true)) {
                $hasAllowedRole = true;
                break;
            }
        }
        if (!$hasAllowedRole) {
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
    public function index(): void {
        $tenantId  = $this->getSecureTenantId();
        $roles     = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        
        // Cari role terkuat yang diizinkan untuk dikirim ke template view
        $role = 'guru_bk';
        foreach (['super_admin', 'operator_sekolah', 'guru_bk'] as $allowed) {
            if (in_array($allowed, $roles)) {
                $role = $allowed;
                break;
            }
        }
        $userNama  = $_SESSION['nama_lengkap'] ?? '';

        // Ambil daftar sekolah untuk dropdown Super Admin
        $tenantList = [];
        if ($role === 'super_admin') {
            try {
                $db         = \App\Config\Database::getConnection();
                $tenantList = $db->query("SELECT id, nama_sekolah FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC")
                                 ->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable) {}
        }

        $this->render('master_bk', [
            'title'       => 'Bimbingan Konseling',
            'user_role'   => $role,
            'user_nama'   => $userNama,
            'tenant_id'   => $tenantId,
            'tenant_list' => $tenantList,
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
    // API: Tab 4 — Siswa Eligible SNBP (Kesiapan PDSS)
    // GET /api/v1/bk/pdss
    // =========================================================================
    public function apiPdss(): void {
        $tenantId = $this->getSecureTenantId();
        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Pilih sekolah terlebih dahulu.'], 400);
            return;
        }

        $db = \App\Config\Database::getConnection();
        try {
            // Siswa aktif di kelas 12 (id_jenjang tertentu, asumsi jenjang_nama LIKE '%12%' atau sesuai konfigurasi)
            $stmt = $db->prepare("
                SELECT s.id, s.nama_lengkap, s.nisn, k.nama_kelas, j.nama_jurusan
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN jurusan j ON s.id_jurusan = j.id
                WHERE s.tenant_id = ?
                  AND s.status = 'Aktif'
                  AND s.deleted_at IS NULL
                  AND k.nama_kelas LIKE '%12%'
                ORDER BY j.nama_jurusan ASC, s.nama_lengkap ASC
                LIMIT 200
            ");
            $stmt->execute([$tenantId]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'data' => $data, 'total' => count($data)]);
        } catch (\Throwable $e) {
            error_log('[BKController::apiPdss] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal memuat data PDSS.'], 500);
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
            if (isset($db) && $db->inTransaction()) $db->rollBack();
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
            if (isset($db) && $db->inTransaction()) $db->rollBack();
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
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('[BKController::apiToggleKunci] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Operasi gagal.'], 500);
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
}
