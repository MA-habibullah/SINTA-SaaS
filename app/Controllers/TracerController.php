<?php

namespace App\Controllers;

use App\Core\SessionManager;

/**
 * TracerController
 *
 * Menangani modul Tracer Study: Riwayat Kuliah & Riwayat Pekerjaan.
 *
 * SECURITY MODEL:
 * - id_siswa dan tenant_id SELALU diambil dari $_SESSION (bukan dari input user).
 * - Hanya siswa berstatus 'Lulus' yang boleh melakukan INSERT data tracer miliknya sendiri.
 * - Admin & Super Admin dapat membaca data tracer semua siswa di bawah tenant mereka.
 * - Semua operasi tulis dibungkus dalam DB transaction untuk integritas data.
 */
class TracerController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    // =========================================================================
    // PAGE: Halaman Tracer Study (View)
    // GET /tracer-study
    // =========================================================================
    public function index(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';

        // Hanya siswa 'Lulus' atau admin yang dapat mengakses halaman ini
        if ($roleName === 'siswa') {
            $status = $this->getSiswaStatus($siswaId);
            if ($status !== 'Lulus') {
                // Siswa aktif / bukan lulus tidak punya akses
                http_response_code(403);
                $this->render('error_403', [
                    'title'   => '403 Akses Ditolak',
                    'message' => 'Halaman Tracer Study hanya tersedia bagi siswa yang telah dinyatakan Lulus.'
                ]);
                return;
            }
        }

        $db       = \App\Config\Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if (empty($tenantId) && !empty($_GET['tenant_id'])) {
            $tenantId = trim($_GET['tenant_id']);
        }

        // Super Admin dapat membaca tracer seluruh platform (tanpa filter tenant)
        // Admin Sekolah hanya melihat data dalam tenantnya
        if ($roleName === 'siswa') {
            // Siswa hanya melihat datanya sendiri
            $kuliah    = $db->prepare("SELECT * FROM riwayat_kuliah WHERE id_siswa = ? ORDER BY tahun_masuk DESC");
            $pekerjaan = $db->prepare("SELECT * FROM riwayat_pekerjaan WHERE id_siswa = ? ORDER BY tahun_mulai DESC");
            $kuliah->execute([$siswaId]);
            $pekerjaan->execute([$siswaId]);
        } elseif ($tenantId) {
            // Admin sekolah — lihat semua alumni di tenant ini
            $targetId  = $_GET['siswa_id'] ?? '';
            if ($targetId) {
                $kuliah    = $db->prepare("SELECT * FROM riwayat_kuliah WHERE id_siswa = ? AND tenant_id = ? ORDER BY tahun_masuk DESC");
                $pekerjaan = $db->prepare("SELECT * FROM riwayat_pekerjaan WHERE id_siswa = ? AND tenant_id = ? ORDER BY tahun_mulai DESC");
                $kuliah->execute([$targetId, $tenantId]);
                $pekerjaan->execute([$targetId, $tenantId]);
            } else {
                $kuliah    = $db->prepare("SELECT rk.*, s.nama_lengkap FROM riwayat_kuliah rk JOIN siswa s ON rk.id_siswa = s.id WHERE rk.tenant_id = ? ORDER BY rk.created_at DESC");
                $pekerjaan = $db->prepare("SELECT rp.*, s.nama_lengkap FROM riwayat_pekerjaan rp JOIN siswa s ON rp.id_siswa = s.id WHERE rp.tenant_id = ? ORDER BY rp.created_at DESC");
                $kuliah->execute([$tenantId]);
                $pekerjaan->execute([$tenantId]);
            }
        } else {
            // Super Admin — tampilkan semua (dengan JOIN nama kampus)
            $kuliah    = $db->query("SELECT rk.*, s.nama_lengkap, t.nama_sekolah FROM riwayat_kuliah rk JOIN siswa s ON rk.id_siswa = s.id JOIN tenants t ON rk.tenant_id = t.id ORDER BY rk.created_at DESC LIMIT 200");
            $pekerjaan = $db->query("SELECT rp.*, s.nama_lengkap, t.nama_sekolah FROM riwayat_pekerjaan rp JOIN siswa s ON rp.id_siswa = s.id JOIN tenants t ON rp.tenant_id = t.id ORDER BY rp.created_at DESC LIMIT 200");
        }

        $this->render('tracer_study', [
            'title'             => 'Tracer Study / Portofolio Alumni',
            'user_role'         => $roleName,
            'user_nama'         => $_SESSION['nama_lengkap'] ?? '',
            'riwayat_kuliah'    => $kuliah->fetchAll(\PDO::FETCH_ASSOC),
            'riwayat_pekerjaan' => $pekerjaan->fetchAll(\PDO::FETCH_ASSOC),
        ]);
    }

    // =========================================================================
    // API: Simpan Riwayat Kuliah
    // POST /api/v1/tracer/kuliah
    // =========================================================================
    public function storeKuliah(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';
        $tenantId = SessionManager::getTenantId();
        if (empty($tenantId) && !empty($_GET['tenant_id'])) {
            $tenantId = trim($_GET['tenant_id']);
        }

        // --- GATEKEEPER: Siswa hanya boleh insert jika statusnya 'Lulus' ---
        if ($roleName === 'siswa') {
            if (empty($siswaId)) {
                $this->jsonResponse(['error' => 'Session tidak valid. Silakan login ulang.'], 401);
            }
            $status = $this->getSiswaStatus($siswaId);
            if ($status !== 'Lulus') {
                $this->jsonResponse([
                    'error' => 'Akses ditolak. Fitur Tracer Study hanya tersedia untuk siswa yang telah dinyatakan Lulus.'
                ], 403);
            }
            // KUNCI: id_siswa & tenant_id dari SESSION — tidak bisa dimanipulasi user
            // Tidak perlu membaca dari request body sama sekali
        } elseif (!in_array($roleName, ['admin', 'operator', 'super_admin', 'operator_sekolah', 'guru_bk'], true)) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
        } else {
            // Admin mengisi tracer untuk siswa lain → siswa_id dari body, tapi tenant dikunci dari session
            $body    = $this->getJsonInput();
            $siswaId = $this->sanitizeStr($body['siswa_id'] ?? '');
            $namaAlumni = $this->sanitizeStr($body['nama_alumni'] ?? '');
            if (empty($siswaId) && empty($namaAlumni)) {
                $this->jsonResponse(['error' => 'siswa_id atau nama_alumni wajib diisi.'], 422);
            }
        }

        $body = $this->getJsonInput();

        // --- VALIDASI & SANITASI SERVER-SIDE (mencegah XSS / injection) ---
        $namaAlumni   = $this->sanitizeStr($body['nama_alumni']   ?? '');
        $namaKampus   = $this->sanitizeStr($body['nama_kampus']   ?? '');
        $fakultas     = $this->sanitizeStr($body['fakultas']      ?? '');
        $jurusan      = $this->sanitizeStr($body['jurusan']       ?? '');
        $kampusProdiId= !empty($body['kampus_prodi_id']) ? $this->sanitizeStr($body['kampus_prodi_id']) : null;
        $jalurMasukId = !empty($body['jalur_masuk_id']) ? (int)$body['jalur_masuk_id'] : null;
        $tahunMasuk   = (int)($body['tahun_masuk']  ?? 0);
        $tahunLulus   = !empty($body['tahun_lulus']) ? (int)$body['tahun_lulus'] : null;
        $statusKuliah = $this->sanitizeStr($body['status_kuliah'] ?? 'Aktif');

        $errors = [];
        if (empty($namaKampus))  $errors[] = 'Nama kampus wajib diisi.';
        if ($tahunMasuk < 1990 || $tahunMasuk > (int)date('Y') + 1)
            $errors[] = 'Tahun masuk tidak valid (1990 – sekarang).';
        if (!in_array($statusKuliah, ['Aktif', 'Lulus', 'Drop'], true))
            $errors[] = 'Status kuliah tidak valid.';
        if ($tahunLulus !== null && ($tahunLulus < $tahunMasuk || $tahunLulus > (int)date('Y') + 5))
            $errors[] = 'Tahun lulus tidak valid.';

        if (!empty($errors)) {
            $this->jsonResponse(['error' => implode(' ', $errors)], 422);
        }

        $db = \App\Config\Database::getConnection();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO riwayat_kuliah
                    (id_siswa, tenant_id, nama_alumni, kampus_prodi_id, jalur_masuk_id, nama_kampus, fakultas, jurusan, tahun_masuk, tahun_lulus, status_kuliah)
                VALUES
                    (:id_siswa, :tenant_id, :nama_alumni, :kampus_prodi_id, :jalur_masuk_id, :nama_kampus, :fakultas, :jurusan, :tahun_masuk, :tahun_lulus, :status_kuliah)
            ");
            $stmt->execute([
                'id_siswa'        => $siswaId ?: null,
                'tenant_id'       => $tenantId,
                'nama_alumni'     => $namaAlumni ?: null,
                'kampus_prodi_id' => $kampusProdiId,
                'jalur_masuk_id'  => $jalurMasukId,
                'nama_kampus'     => $namaKampus,
                'fakultas'        => $fakultas ?: null,
                'jurusan'         => $jurusan  ?: null,
                'tahun_masuk'     => $tahunMasuk,
                'tahun_lulus'     => $tahunLulus,
                'status_kuliah'   => $statusKuliah,
            ]);
            $newId = $db->lastInsertId();
            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'id'      => $newId,
                'message' => 'Riwayat kuliah berhasil disimpan.'
            ]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[TracerController::storeKuliah] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan riwayat kuliah. Coba lagi.'], 500);
        }
    }

    // =========================================================================
    // API: Simpan Riwayat Pekerjaan
    // POST /api/v1/tracer/pekerjaan
    // =========================================================================
    public function storePekerjaan(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';
        $tenantId = SessionManager::getTenantId();
        if (empty($tenantId) && !empty($_GET['tenant_id'])) {
            $tenantId = trim($_GET['tenant_id']);
        }

        // --- GATEKEEPER ---
        if ($roleName === 'siswa') {
            if (empty($siswaId)) {
                $this->jsonResponse(['error' => 'Session tidak valid.'], 401);
            }
            $status = $this->getSiswaStatus($siswaId);
            if ($status !== 'Lulus') {
                $this->jsonResponse([
                    'error' => 'Akses ditolak. Fitur Tracer Study hanya tersedia untuk siswa yang telah dinyatakan Lulus.'
                ], 403);
            }
        } elseif (!in_array($roleName, ['admin', 'operator', 'super_admin', 'operator_sekolah', 'guru_bk'], true)) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
        } else {
            $body    = $this->getJsonInput();
            $siswaId = $this->sanitizeStr($body['siswa_id'] ?? '');
            $namaAlumni = $this->sanitizeStr($body['nama_alumni'] ?? '');
            if (empty($siswaId) && empty($namaAlumni)) {
                $this->jsonResponse(['error' => 'siswa_id atau nama_alumni wajib diisi.'], 422);
            }
        }

        $body = $this->getJsonInput();

        // --- VALIDASI & SANITASI ---
        $namaAlumni        = $this->sanitizeStr($body['nama_alumni']        ?? '');
        $namaPerusahaan    = $this->sanitizeStr($body['nama_perusahaan']    ?? '');
        $posisiJabatan     = $this->sanitizeStr($body['posisi_jabatan']     ?? '');
        $pendapatanBulanan = $this->sanitizeStr($body['pendapatan_bulanan'] ?? '');
        $tahunMulai        = (int)($body['tahun_mulai']   ?? 0);
        $tahunSelesai      = !empty($body['tahun_selesai']) ? (int)$body['tahun_selesai'] : null;
        $statusKerja       = $this->sanitizeStr($body['status_kerja']       ?? 'Kontrak');

        $errors = [];
        if (empty($namaPerusahaan))  $errors[] = 'Nama perusahaan wajib diisi.';
        if (empty($posisiJabatan))   $errors[] = 'Posisi/jabatan wajib diisi.';
        if ($tahunMulai < 1990 || $tahunMulai > (int)date('Y') + 1)
            $errors[] = 'Tahun mulai tidak valid.';
        if (!in_array($statusKerja, ['Kontrak', 'Tetap', 'Magang'], true))
            $errors[] = 'Status kerja tidak valid.';
        if ($tahunSelesai !== null && $tahunSelesai < $tahunMulai)
            $errors[] = 'Tahun selesai tidak boleh lebih kecil dari tahun mulai.';

        if (!empty($errors)) {
            $this->jsonResponse(['error' => implode(' ', $errors)], 422);
        }

        $db = \App\Config\Database::getConnection();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO riwayat_pekerjaan
                    (id_siswa, tenant_id, nama_alumni, nama_perusahaan, posisi_jabatan, pendapatan_bulanan, tahun_mulai, tahun_selesai, status_kerja)
                VALUES
                    (:id_siswa, :tenant_id, :nama_alumni, :nama_perusahaan, :posisi_jabatan, :pendapatan_bulanan, :tahun_mulai, :tahun_selesai, :status_kerja)
            ");
            $stmt->execute([
                'id_siswa'           => $siswaId ?: null,
                'tenant_id'          => $tenantId,
                'nama_alumni'        => $namaAlumni ?: null,
                'nama_perusahaan'    => $namaPerusahaan,
                'posisi_jabatan'     => $posisiJabatan,
                'pendapatan_bulanan' => $pendapatanBulanan ?: null,
                'tahun_mulai'        => $tahunMulai,
                'tahun_selesai'      => $tahunSelesai,
                'status_kerja'       => $statusKerja,
            ]);
            $newId = $db->lastInsertId();
            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'id'      => $newId,
                'message' => 'Riwayat pekerjaan berhasil disimpan.'
            ]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[TracerController::storePekerjaan] ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menyimpan riwayat pekerjaan. Coba lagi.'], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Ambil status siswa dari DB (field `status` di tabel `siswa`).
     * Selalu query DB — tidak bergantung pada session agar tidak bisa dimanipulasi.
     */
    private function getSiswaStatus(string $siswaId): ?string {
        try {
            $db   = \App\Config\Database::getConnection();
            $stmt = $db->prepare("SELECT status FROM siswa WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$siswaId]);
            return $stmt->fetchColumn() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Sanitasi string input: trim + strip_tags + htmlspecialchars.
     * Mencegah XSS pada semua kolom teks yang masuk ke DB.
     */
    private function sanitizeStr(mixed $value): string {
        if (!is_string($value)) return '';
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * API: Get Riwayat Kuliah (GET)
     */
    public function apiGetKuliah(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';
        $tenantId = SessionManager::getTenantId();
        if (empty($tenantId) && !empty($_GET['tenant_id'])) {
            $tenantId = trim($_GET['tenant_id']);
        }

        $db = \App\Config\Database::getConnection();

        if ($roleName === 'siswa') {
            $stmt = $db->prepare("SELECT * FROM riwayat_kuliah WHERE id_siswa = ? ORDER BY tahun_masuk DESC");
            $stmt->execute([$siswaId]);
        } elseif ($tenantId) {
            $targetId = $_GET['siswa_id'] ?? '';
            if ($targetId) {
                $stmt = $db->prepare("SELECT * FROM riwayat_kuliah WHERE id_siswa = ? AND tenant_id = ? ORDER BY tahun_masuk DESC");
                $stmt->execute([$targetId, $tenantId]);
            } else {
                $stmt = $db->prepare("SELECT rk.*, s.nama_lengkap FROM riwayat_kuliah rk LEFT JOIN siswa s ON rk.id_siswa = s.id WHERE rk.tenant_id = ? ORDER BY rk.created_at DESC");
                $stmt->execute([$tenantId]);
            }
        } else {
            $stmt = $db->query("SELECT rk.*, s.nama_lengkap, t.nama_sekolah FROM riwayat_kuliah rk LEFT JOIN siswa s ON rk.id_siswa = s.id JOIN tenants t ON rk.tenant_id = t.id ORDER BY rk.created_at DESC LIMIT 200");
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
        ]);
    }

    /**
     * API: Get Riwayat Pekerjaan (GET)
     */
    public function apiGetPekerjaan(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $siswaId  = $_SESSION['user_id']   ?? '';
        $tenantId = SessionManager::getTenantId();
        if (empty($tenantId) && !empty($_GET['tenant_id'])) {
            $tenantId = trim($_GET['tenant_id']);
        }

        $db = \App\Config\Database::getConnection();

        if ($roleName === 'siswa') {
            $stmt = $db->prepare("SELECT * FROM riwayat_pekerjaan WHERE id_siswa = ? ORDER BY tahun_mulai DESC");
            $stmt->execute([$siswaId]);
        } elseif ($tenantId) {
            $targetId = $_GET['siswa_id'] ?? '';
            if ($targetId) {
                $stmt = $db->prepare("SELECT * FROM riwayat_pekerjaan WHERE id_siswa = ? AND tenant_id = ? ORDER BY tahun_mulai DESC");
                $stmt->execute([$targetId, $tenantId]);
            } else {
                $stmt = $db->prepare("SELECT rp.*, s.nama_lengkap FROM riwayat_pekerjaan rp LEFT JOIN siswa s ON rp.id_siswa = s.id WHERE rp.tenant_id = ? ORDER BY rp.created_at DESC");
                $stmt->execute([$tenantId]);
            }
        } else {
            $stmt = $db->query("SELECT rp.*, s.nama_lengkap, t.nama_sekolah FROM riwayat_pekerjaan rp LEFT JOIN siswa s ON rp.id_siswa = s.id JOIN tenants t ON rp.tenant_id = t.id ORDER BY rp.created_at DESC LIMIT 200");
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
        ]);
    }

    // =========================================================================
    // API: Hapus Riwayat Kuliah (DELETE)
    // DELETE /api/v1/tracer/kuliah/delete?id={id}
    // Hanya admin, guru_bk, operator_sekolah yang boleh menghapus.
    // =========================================================================
    public function deleteKuliah(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $tenantId = SessionManager::getTenantId();
        if (empty($tenantId) && !empty($_GET['tenant_id'])) {
            $tenantId = trim($_GET['tenant_id']);
        }

        // Hanya role admin yang boleh hapus
        if (!in_array($roleName, ['super_admin', 'admin', 'operator', 'operator_sekolah', 'guru_bk'], true)) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse(['error' => 'ID tidak valid.'], 400);
            return;
        }

        $db = \App\Config\Database::getConnection();

        // Verifikasi bahwa record milik tenant ini (kecuali super_admin)
        if ($roleName !== 'super_admin') {
            $stmt = $db->prepare("SELECT id FROM riwayat_kuliah WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenantId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404);
                return;
            }
        }

        $stmt = $db->prepare("DELETE FROM riwayat_kuliah WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $this->jsonResponse(['success' => true, 'message' => 'Riwayat kuliah berhasil dihapus.']);
        } else {
            $this->jsonResponse(['error' => 'Data tidak ditemukan.'], 404);
        }
    }

    // =========================================================================
    // API: Hapus Riwayat Pekerjaan (DELETE)
    // DELETE /api/v1/tracer/pekerjaan/delete?id={id}
    // =========================================================================
    public function deletePekerjaan(): void {
        $roleName = $_SESSION['role_name'] ?? '';
        $tenantId = SessionManager::getTenantId();
        if (empty($tenantId) && !empty($_GET['tenant_id'])) {
            $tenantId = trim($_GET['tenant_id']);
        }

        if (!in_array($roleName, ['super_admin', 'admin', 'operator', 'operator_sekolah', 'guru_bk'], true)) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            return;
        }

        $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse(['error' => 'ID tidak valid.'], 400);
            return;
        }

        $db = \App\Config\Database::getConnection();

        if ($roleName !== 'super_admin') {
            $stmt = $db->prepare("SELECT id FROM riwayat_pekerjaan WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenantId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'Data tidak ditemukan atau akses ditolak.'], 404);
                return;
            }
        }

        $stmt = $db->prepare("DELETE FROM riwayat_pekerjaan WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $this->jsonResponse(['success' => true, 'message' => 'Riwayat pekerjaan berhasil dihapus.']);
        } else {
            $this->jsonResponse(['error' => 'Data tidak ditemukan.'], 404);
        }
    }
}
