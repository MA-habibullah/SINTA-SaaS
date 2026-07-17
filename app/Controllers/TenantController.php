<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use PDO;

class TenantController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * View: Halaman Identitas Sekolah
     * GET /sekolah/identitas
     */
    public function index(): void {
        // 1. Wajib Login (Security Gate)
        SessionManager::requireLogin();

        // 2. Otorisasi: Hanya super_admin atau operator_sekolah
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'super_admin' && $role !== 'operator_sekolah') {
            header('Location: /SINTA-SaaS/dashboard?error=' . urlencode('Akses ditolak.'));
            exit;
        }

        // 3. Tentukan tenant ID yang akan dimuat
        $tenantId = null;
        if ($role === 'super_admin') {
            $tenantId = isset($_GET['id']) ? trim($_GET['id']) : null;
            if (empty($tenantId)) {
                try {
                    $db = Database::getConnection();
                    $tenantId = $db->query("SELECT id FROM tenants WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
                } catch (\Throwable $e) {
                    $tenantId = null;
                }
            }
        } else {
            $tenantId = $_SESSION['tenant_id'] ?? null;
        }

        if (empty($tenantId)) {
            header('Location: /SINTA-SaaS/dashboard?error=' . urlencode('Data sekolah tidak ditemukan.'));
            exit;
        }

        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            $action = $_GET['action'] ?? '';
            if ($action === 'get_tenant_detail') {
                try {
                    $db = Database::getConnection();
                    $stmt = $db->prepare("SELECT * FROM tenants WHERE id = :id AND deleted_at IS NULL LIMIT 1");
                    $stmt->execute(['id' => $tenantId]);
                    $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($tenant) {
                        $this->jsonResponse(['success' => true, 'data' => $tenant]);
                    } else {
                        $this->jsonResponse(['error' => 'Sekolah tidak ditemukan.'], 404);
                    }
                } catch (\Throwable $e) {
                    $this->jsonResponse(['error' => $e->getMessage()], 500);
                }
            }
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM tenants WHERE id = :id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['id' => $tenantId]);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tenant) {
                header('Location: /SINTA-SaaS/dashboard?error=' . urlencode('Sekolah tidak ditemukan.'));
                exit;
            }

            $data = [
                'title' => 'Identitas Sekolah',
                'user_nama' => $_SESSION['nama_lengkap'] ?? 'User',
                'user_role' => $role,
                'tenant' => $tenant
            ];

            $this->render('identitas_sekolah', $data);
        } catch (\Throwable $e) {
            error_log("Failed to load tenant identity view: " . $e->getMessage());
            header('Location: /SINTA-SaaS/dashboard?error=' . urlencode('Terjadi kesalahan sistem.'));
            exit;
        }
    }

    /**
     * API: Mencari Tenant berdasarkan NPSN (Context Routing / Sinkronisasi)
     * GET /api/v1/tenant/lookup?npsn=...
     */
    public function lookup(): void {
        $npsn = isset($_GET['npsn']) ? trim($_GET['npsn']) : '';

        if (empty($npsn)) {
            $this->jsonResponse(['error' => 'NPSN wajib diisi'], 400);
        }

        if (!preg_match('/^[0-9]{8}$/', $npsn)) {
            $this->jsonResponse(['error' => 'Format NPSN harus berupa tepat 8 digit angka'], 400);
        }

        try {
            $db = Database::getConnection();
            
            // Prepared Statement untuk mencegah SQL Injection (Cybersecurity Best Practice)
            $stmt = $db->prepare("SELECT id, nama_sekolah, subdomain, status, paket_aktif FROM tenants WHERE npsn = :npsn AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['npsn' => $npsn]);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tenant) {
                $this->jsonResponse(['error' => 'Sekolah dengan NPSN tersebut tidak ditemukan'], 404);
            }

            if ($tenant['status'] !== 'active') {
                $this->jsonResponse(['error' => 'Status sekolah ditangguhkan atau tidak aktif'], 403);
            }

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'tenant_id' => $tenant['id'],
                    'nama_sekolah' => $tenant['nama_sekolah'],
                    'subdomain' => $tenant['subdomain'],
                    'paket_aktif' => $tenant['paket_aktif']
                ]
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to lookup tenant by NPSN: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat mencari sekolah.'], 500);
        }
    }

    /**
     * API: Cek real-time keunikan NPSN saat input (Frontend Validation)
     * GET /api/v1/tenant/check-npsn?npsn=...&exclude_id=...
     */
    public function checkUnique(): void {
        $npsn = isset($_GET['npsn']) ? trim($_GET['npsn']) : '';
        $excludeId = isset($_GET['exclude_id']) ? trim($_GET['exclude_id']) : '';

        if (empty($npsn)) {
            $this->jsonResponse(['available' => false, 'error' => 'NPSN kosong'], 400);
        }

        if (!preg_match('/^[0-9]{8}$/', $npsn)) {
            $this->jsonResponse(['available' => false, 'error' => 'Format NPSN tidak valid (harus 8 digit angka)'], 400);
        }

        try {
            $db = Database::getConnection();
            $sql = "SELECT COUNT(*) FROM tenants WHERE npsn = :npsn AND deleted_at IS NULL";
            $params = ['npsn' => $npsn];

            if (!empty($excludeId)) {
                $sql .= " AND id != :exclude_id";
                $params['exclude_id'] = $excludeId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $count = (int)$stmt->fetchColumn();

            $this->jsonResponse([
                'available' => ($count === 0),
                'message' => ($count === 0) ? 'NPSN tersedia untuk digunakan' : 'NPSN sudah terdaftar oleh sekolah lain'
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['available' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Memperbarui profil tenant (Sekolah)
     * POST /api/v1/tenant/update
     */
    public function update(): void {
        // 1. Wajib Login (Security Gate)
        SessionManager::requireLogin();

        // 2. Otorisasi: Hanya super_admin atau operator_sekolah yang boleh edit profil sekolah
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'super_admin' && $role !== 'operator_sekolah') {
            $this->jsonResponse(['error' => 'Akses ditolak. Anda tidak memiliki wewenang untuk memperbarui profil sekolah.'], 403);
        }

        // 3. Ambil data payload JSON
        $input = $this->getJsonInput();

        $namaSekolah = isset($input['nama_sekolah']) ? strip_tags(trim($input['nama_sekolah'])) : '';
        $npsn = isset($input['npsn']) ? trim($input['npsn']) : '';
        $subdomain = isset($input['subdomain']) ? strtolower(trim($input['subdomain'])) : '';

        // Tentukan tenant ID yang akan diupdate
        $tenantId = null;
        if ($role === 'super_admin') {
            $tenantId = isset($input['id']) ? trim($input['id']) : null;
        } else {
            $tenantId = $_SESSION['tenant_id'] ?? null;
        }

        if (empty($tenantId)) {
            $this->jsonResponse(['error' => 'ID Sekolah tidak valid.'], 400);
        }

        // 4. Validasi Sisi Server (Form Request Validation - Clean & Secure Code)
        $errors = [];

        if (empty($namaSekolah)) {
            $errors['nama_sekolah'] = ['Nama sekolah wajib diisi.'];
        } elseif (strlen($namaSekolah) > 255) {
            $errors['nama_sekolah'] = ['Nama sekolah tidak boleh melebihi 255 karakter.'];
        }

        if (empty($npsn)) {
            $errors['npsn'] = ['Nomor NPSN (Nomor Pokok Sekolah Nasional) wajib diisi.'];
        } elseif (!preg_match('/^[0-9]{8}$/', $npsn)) {
            $errors['npsn'] = ['Nomor NPSN harus berupa angka sepanjang tepat 8 digit.'];
        }

        if (empty($subdomain)) {
            $errors['subdomain'] = ['Subdomain sekolah wajib diisi.'];
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            $errors['subdomain'] = ['Subdomain hanya boleh berisi huruf kecil, angka, dan strip.'];
        } elseif (strlen($subdomain) > 100) {
            $errors['subdomain'] = ['Subdomain tidak boleh melebihi 100 karakter.'];
        }

        try {
            $db = Database::getConnection();

            // Pengecekan Keunikan NPSN Global (Prepared Statements - Anti SQL Injection)
            if (empty($errors['npsn'])) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM tenants WHERE npsn = :npsn AND id != :id AND deleted_at IS NULL");
                $stmt->execute(['npsn' => $npsn, 'id' => $tenantId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $errors['npsn'] = ['Nomor NPSN ini sudah terdaftar oleh sekolah lain di dalam sistem SaaS.'];
                }
            }

            // Pengecekan Keunikan Subdomain Global
            if (empty($errors['subdomain'])) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM tenants WHERE subdomain = :subdomain AND id != :id AND deleted_at IS NULL");
                $stmt->execute(['subdomain' => $subdomain, 'id' => $tenantId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $errors['subdomain'] = ['Subdomain ini sudah digunakan oleh sekolah lain.'];
                }
            }

            // Jika ada error, kirimkan respon HTTP 422 Unprocessable Entity
            if (!empty($errors)) {
                $this->jsonResponse(['errors' => $errors], 422);
            }

            // Ambil data sebelum update untuk Activity Log (Audit Trail)
            $stmtOld = $db->prepare("SELECT nama_sekolah, npsn, subdomain FROM tenants WHERE id = :id AND deleted_at IS NULL LIMIT 1");
            $stmtOld->execute(['id' => $tenantId]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            // 5. Update data sekolah menggunakan database binding
            $stmt = $db->prepare("
                UPDATE tenants 
                SET nama_sekolah = :nama_sekolah, npsn = :npsn, subdomain = :subdomain, updated_at = NOW() 
                WHERE id = :id AND deleted_at IS NULL
            ");
            $stmt->execute([
                'nama_sekolah' => $namaSekolah,
                'npsn' => $npsn,
                'subdomain' => $subdomain,
                'id' => $tenantId
            ]);

            // Catat log aktivitas (Audit Trail)
            $newData = [
                'nama_sekolah' => $namaSekolah,
                'npsn' => $npsn,
                'subdomain' => $subdomain
            ];
            \App\Helpers\ActivityLogger::record('UPDATE', 'tenants', $tenantId, $oldData, $newData);

            // Opsional: Perbarui session jika yang diupdate adalah tenant aktif pengguna saat ini
            if ($role !== 'super_admin' && $_SESSION['tenant_id'] === $tenantId) {
                $_SESSION['nama_sekolah'] = $namaSekolah;
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Profil sekolah berhasil diperbarui.',
                'data' => [
                    'id' => $tenantId,
                    'nama_sekolah' => $namaSekolah,
                    'npsn' => $npsn,
                    'subdomain' => $subdomain
                ]
            ]);

        } catch (\Throwable $e) {
            error_log("Failed to update tenant: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat memperbarui profil sekolah.'], 500);
        }
    }

    /**
     * API: Mencari sekolah/tenant aktif (untuk dropdown pencarian login)
     * GET /api/v1/tenant/search?q=...&page=...&limit=...
     */
    public function searchActiveTenants(): void {
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
        
        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 100) $limit = 15;
        
        $offset = ($page - 1) * $limit;
        
        try {
            $db = Database::getConnection();
            
            $sql = "SELECT id, nama_sekolah, npsn, subdomain, status FROM tenants 
                    WHERE status = 'active' AND deleted_at IS NULL";
            
            if (!empty($search)) {
                $sql .= " AND (nama_sekolah LIKE :search1 OR npsn LIKE :search2 OR subdomain LIKE :search3)";
            }
            
            $sql .= " ORDER BY nama_sekolah ASC LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($sql);
            
            // Bind search parameters if any
            if (!empty($search)) {
                $searchValue = '%' . $search . '%';
                $stmt->bindValue(':search1', $searchValue, PDO::PARAM_STR);
                $stmt->bindValue(':search2', $searchValue, PDO::PARAM_STR);
                $stmt->bindValue(':search3', $searchValue, PDO::PARAM_STR);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Sanitasi output untuk mencegah XSS
            $sanitizedTenants = array_map(function($tenant) {
                return [
                    'id' => $tenant['id'],
                    'nama_sekolah' => htmlspecialchars($tenant['nama_sekolah'], ENT_QUOTES, 'UTF-8'),
                    'npsn' => htmlspecialchars($tenant['npsn'], ENT_QUOTES, 'UTF-8'),
                    'subdomain' => htmlspecialchars($tenant['subdomain'], ENT_QUOTES, 'UTF-8'),
                    'status' => htmlspecialchars($tenant['status'], ENT_QUOTES, 'UTF-8')
                ];
            }, $tenants);
            
            // Cari total records untuk pagination info
            $countSql = "SELECT COUNT(*) FROM tenants WHERE status = 'active' AND deleted_at IS NULL";
            if (!empty($search)) {
                $countSql .= " AND (nama_sekolah LIKE :search1 OR npsn LIKE :search2 OR subdomain LIKE :search3)";
            }
            $countStmt = $db->prepare($countSql);
            if (!empty($search)) {
                $searchValue = '%' . $search . '%';
                $countStmt->bindValue(':search1', $searchValue, PDO::PARAM_STR);
                $countStmt->bindValue(':search2', $searchValue, PDO::PARAM_STR);
                $countStmt->bindValue(':search3', $searchValue, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $totalCount = (int)$countStmt->fetchColumn();
            $hasMore = ($offset + count($tenants)) < $totalCount;
            
            $this->jsonResponse([
                'success' => true,
                'data' => $sanitizedTenants,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'has_more' => $hasMore
                ]
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to search active tenants: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat mengambil data sekolah.'], 500);
        }
    }
}


