<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use App\Helpers\QueueManager;
use App\Jobs\JobDispatcher;
use PDO;

class QueueController extends BaseController {

    public function __construct() {
        parent::__construct();
        
        // 1. Gate Keamanan: Wajib Login
        SessionManager::requireLogin();
        
        // 2. Otorisasi: Hanya super_admin & operator_sekolah
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'super_admin' && $role !== 'operator_sekolah') {
            http_response_code(403);
            echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
            echo "<h1 style='color: #dc3545;'>403 Akses Ditolak</h1>";
            echo "<p style='color: #6c757d;'>Anda tidak memiliki wewenang untuk mengakses dashboard antrean sistem.</p>";
            echo "<a href='/SINTA-SaaS/dashboard'>Kembali ke Dashboard</a>";
            echo "</div>";
            exit;
        }
    }

    /**
     * Tampilkan Halaman Utama Dashboard Antrean
     * GET /utilitas/antrean
     */
    public function index(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $tenantsList = [];

        // Jika super_admin, ambil list tenant untuk dropdown filter
        if ($role === 'super_admin') {
            try {
                $db = Database::getConnection();
                $stmt = $db->query("SELECT id, nama_sekolah, npsn FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC");
                $tenantsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $tenantsList = [];
            }
        }

        $data = [
            'title'       => 'Antrean Sistem & Background Jobs',
            'user_nama'   => $_SESSION['nama_lengkap'] ?? 'User',
            'user_role'   => $role,
            'tenantsList' => $tenantsList
        ];

        $this->render('queue_monitoring', $data);
    }

    /**
     * API: Ambil metrik ringkasan status dan daftar jobs terpaginasi
     * GET /api/v1/queue/data
     */
    public function fetchDataApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $sessionTenantId = $_SESSION['tenant_id'] ?? null;
        
        // Parameter Filter & Paginasi
        $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
        $filterType = isset($_GET['job_type']) ? trim($_GET['job_type']) : '';
        $filterTenantId = isset($_GET['tenant_id']) ? trim($_GET['tenant_id']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 15;
        $offset = ($page - 1) * $limit;

        // Tentukan tenant_id berdasarkan RBAC
        $tenantId = ($role === 'super_admin') ? $filterTenantId : $sessionTenantId;

        try {
            $db = Database::getConnection();

            // 1. Tarik Metrik KPIs
            $metricsSql = "
                SELECT 
                    SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN `status` = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN `status` = 'failed' THEN 1 ELSE 0 END) as failed,
                    COUNT(*) as total
                FROM `system_jobs`
            ";
            
            $whereParts = [];
            $params = [];
            
            if (!empty($tenantId)) {
                $whereParts[] = "`tenant_id` = :tenant_id";
                $params['tenant_id'] = $tenantId;
            }
            
            if (!empty($whereParts)) {
                $metricsSql .= " WHERE " . implode(" AND ", $whereParts);
            }
            
            $stmtMetrics = $db->prepare($metricsSql);
            $stmtMetrics->execute($params);
            $metrics = $stmtMetrics->fetch(PDO::FETCH_ASSOC);

            // Null-safeguard metrics
            $metrics['pending'] = (int)($metrics['pending'] ?? 0);
            $metrics['processing'] = (int)($metrics['processing'] ?? 0);
            $metrics['completed'] = (int)($metrics['completed'] ?? 0);
            $metrics['failed'] = (int)($metrics['failed'] ?? 0);
            $metrics['total'] = (int)($metrics['total'] ?? 0);

            // 2. Tarik Data Jobs (Recent Jobs) dengan filter lengkap
            $dataSql = "
                SELECT j.*, t.nama_sekolah
                FROM `system_jobs` j
                LEFT JOIN `tenants` t ON j.tenant_id = t.id
            ";
            
            $dataWhere = [];
            $dataParams = [];
            
            if (!empty($tenantId)) {
                $dataWhere[] = "j.`tenant_id` = :tenant_id";
                $dataParams['tenant_id'] = $tenantId;
            }
            if (!empty($filterStatus)) {
                $dataWhere[] = "j.`status` = :status";
                $dataParams['status'] = $filterStatus;
            }
            if (!empty($filterType)) {
                $dataWhere[] = "j.`job_type` = :job_type";
                $dataParams['job_type'] = $filterType;
            }

            if (!empty($dataWhere)) {
                $dataSql .= " WHERE " . implode(" AND ", $dataWhere);
            }

            // Hitung Total Halaman
            $countSql = "SELECT COUNT(*) FROM `system_jobs` j";
            if (!empty($dataWhere)) {
                $countSql .= " WHERE " . implode(" AND ", $dataWhere);
            }
            $stmtCount = $db->prepare($countSql);
            $stmtCount->execute($dataParams);
            $totalCount = (int)$stmtCount->fetchColumn();
            $totalPages = ceil($totalCount / $limit);
            if ($totalPages < 1) $totalPages = 1;

            // Sorting & Limit Offset
            $dataSql .= " ORDER BY j.`id` DESC LIMIT {$limit} OFFSET {$offset}";
            $stmtData = $db->prepare($dataSql);
            $stmtData->execute($dataParams);
            $jobs = $stmtData->fetchAll(PDO::FETCH_ASSOC);

            // Format json values
            foreach ($jobs as &$j) {
                $j['payload'] = json_decode($j['payload'], true) ?? [];
            }

            $this->jsonResponse([
                'success'     => true,
                'metrics'     => $metrics,
                'jobs'        => $jobs,
                'current_page'=> $page,
                'total_pages' => $totalPages
            ]);

        } catch (\Throwable $e) {
            error_log("fetchDataApi failed: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat memuat antrean.'], 500);
        }
    }

    /**
     * API: Dispatch pekerjaan demo baru ke antrean (Simulasi)
     * POST /api/v1/queue/dispatch
     */
    public function dispatchDemoJobApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $sessionTenantId = $_SESSION['tenant_id'] ?? null;
        
        $input = $this->getJsonInput();
        $jobType = $input['job_type'] ?? '';
        $payload = $input['payload'] ?? [];
        $tenantId = $sessionTenantId;

        // Jika super_admin, perbolehkan custom tenant_id untuk job simulasi
        if ($role === 'super_admin' && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }

        if (!in_array($jobType, ['DEMO_SYNC', 'DEMO_EMAIL', 'CLEANUP_SESSIONS'], true)) {
            $this->jsonResponse(['error' => 'Tipe pekerjaan tidak valid untuk simulasi.'], 400);
        }

        $success = QueueManager::push($jobType, $payload, $tenantId);

        if ($success) {
            $this->jsonResponse([
                'success' => true,
                'message' => "Pekerjaan '{$jobType}' berhasil ditambahkan ke antrean sistem."
            ]);
        } else {
            $this->jsonResponse(['error' => 'Gagal memasukkan pekerjaan ke antrean database.'], 500);
        }
    }

    /**
     * API: Proses ulang pekerjaan yang gagal (Retry)
     * POST /api/v1/queue/retry
     */
    public function retryJobApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $sessionTenantId = $_SESSION['tenant_id'] ?? null;
        
        $input = $this->getJsonInput();
        $jobId = isset($input['id']) ? (int)$input['id'] : 0;

        if ($jobId <= 0) {
            $this->jsonResponse(['error' => 'ID Pekerjaan tidak valid.'], 400);
        }

        try {
            $db = Database::getConnection();

            // Tarik data job untuk verifikasi
            $stmt = $db->prepare("SELECT * FROM `system_jobs` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                $this->jsonResponse(['error' => 'Pekerjaan tidak ditemukan.'], 404);
            }

            // Cegah operator sekolah meretry job sekolah lain (RBAC Security)
            if ($role !== 'super_admin' && $job['tenant_id'] !== $sessionTenantId) {
                $this->jsonResponse(['error' => 'Akses ditolak. Anda tidak memiliki hak akses atas pekerjaan ini.'], 403);
            }

            // Kembalikan status ke pending
            $stmtRetry = $db->prepare("
                UPDATE `system_jobs` SET
                    `status` = 'pending',
                    `attempts` = 0,
                    `error_message` = NULL,
                    `reserved_at` = NULL,
                    `completed_at` = NULL
                WHERE `id` = :id
            ");
            $stmtRetry->execute(['id' => $jobId]);

            $this->jsonResponse([
                'success' => true,
                'message' => "Pekerjaan #{$jobId} sukses diatur kembali ke status pending."
            ]);

        } catch (\Throwable $e) {
            error_log("retryJobApi failed: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengatur ulang pekerjaan.'], 500);
        }
    }

    /**
     * API: Hapus pekerjaan dari antrean
     * POST /api/v1/queue/delete
     */
    public function deleteJobApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $sessionTenantId = $_SESSION['tenant_id'] ?? null;
        
        $input = $this->getJsonInput();
        $jobId = isset($input['id']) ? (int)$input['id'] : 0;

        if ($jobId <= 0) {
            $this->jsonResponse(['error' => 'ID Pekerjaan tidak valid.'], 400);
        }

        try {
            $db = Database::getConnection();

            // Tarik data job untuk verifikasi
            $stmt = $db->prepare("SELECT * FROM `system_jobs` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                $this->jsonResponse(['error' => 'Pekerjaan tidak ditemukan.'], 404);
            }

            // Proteksi RBAC
            if ($role !== 'super_admin' && $job['tenant_id'] !== $sessionTenantId) {
                $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
            }

            // Hapus pekerjaan
            $stmtDelete = $db->prepare("DELETE FROM `system_jobs` WHERE `id` = :id");
            $stmtDelete->execute(['id' => $jobId]);

            $this->jsonResponse([
                'success' => true,
                'message' => "Pekerjaan #{$jobId} berhasil dihapus dari antrean."
            ]);

        } catch (\Throwable $e) {
            error_log("deleteJobApi failed: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus pekerjaan.'], 500);
        }
    }

    /**
     * API: Jalankan satu pekerjaan terdepan via browser (Web Runner Simulation)
     * POST /api/v1/queue/run-worker
     */
    public function runWorkerApi(): void {
        // Ambil pekerjaan
        $job = QueueManager::pop();

        if (!$job) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Antrean kosong. Tidak ada tugas pending.'
            ]);
        }

        $id = $job['id'];
        $type = $job['job_type'];

        try {
            // Jalankan pekerjaan menggunakan dispatcher
            JobDispatcher::dispatch($job);
            
            // Tandai sukses
            QueueManager::markCompleted($id);
            
            $this->jsonResponse([
                'success' => true,
                'message' => "Pekerjaan #{$id} ({$type}) sukses diselesaikan di latar belakang."
            ]);
        } catch (\Throwable $e) {
            // Tandai gagal
            QueueManager::markFailed($id, $e->getMessage());
            
            $this->jsonResponse([
                'success' => false,
                'error'   => "Pekerjaan #{$id} ({$type}) gagal diproses: " . $e->getMessage()
            ]);
        }
    }
}
