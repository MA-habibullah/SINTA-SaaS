<?php

namespace App\Modules\Sistem\Controllers;

use App\Core\BaseController;
use App\Config\Database;
use App\Core\SessionManager;
use App\Helpers\QueueManager;
use App\Jobs\JobDispatcher;
use PDO;

class QueueModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        
        SessionManager::requireLogin();
        
        if (!\App\Core\RouteGuard::checkCurrent(['super_admin', 'operator_sekolah'])) {
            if ($this->isJsonRequest()) {
                $this->jsonResponse(false, null, 'Akses ditolak. Fitur ini khusus Super Admin dan Operator Sekolah.', 403);
            }
            header('Location: ' . $this->getBaseUrl() . '/dashboard');
            exit;
        }
    }

    /**
     * Tampilkan Halaman Utama Dashboard Antrean
     * GET /utilitas/antrean
     */
    public function index(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantsList = [];

        if ($role === 'super_admin') {
            try {
                $db = Database::getConnection();
                $stmt = $db->query("SELECT id, nama_sekolah, npsn FROM core.tenants ORDER BY nama_sekolah ASC");
                $tenantsList = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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

        $this->render('sistem/queue_monitoring', $data);
    }

    /**
     * API: Ambil metrik ringkasan status dan daftar jobs terpaginasi
     * GET /api/v1/queue/data
     */
    public function fetchDataApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $sessionTenantId = $_SESSION['tenant_id'] ?? null;
        
        $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
        $filterType = isset($_GET['job_type']) ? trim($_GET['job_type']) : '';
        $filterTenantId = isset($_GET['tenant_id']) ? trim($_GET['tenant_id']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $tenantId = ($role === 'super_admin') ? $filterTenantId : $sessionTenantId;

        try {
            $db = Database::getConnection();

            // 1. Tarik Metrik KPIs dari sistem.queue_jobs
            $metricsSql = "
                SELECT 
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    COUNT(*) as total
                FROM sistem.queue_jobs
            ";
            
            $whereParts = [];
            $params = [];
            
            if (!empty($tenantId)) {
                $whereParts[] = "tenant_id = :tenant_id";
                $params['tenant_id'] = $tenantId;
            }
            
            if (!empty($whereParts)) {
                $metricsSql .= " WHERE " . implode(" AND ", $whereParts);
            }
            
            $stmtMetrics = $db->prepare($metricsSql);
            $stmtMetrics->execute($params);
            $metrics = $stmtMetrics->fetch(PDO::FETCH_ASSOC) ?: [];

            $metrics['pending']    = (int)($metrics['pending'] ?? 0);
            $metrics['processing'] = (int)($metrics['processing'] ?? 0);
            $metrics['completed']  = (int)($metrics['completed'] ?? 0);
            $metrics['failed']     = (int)($metrics['failed'] ?? 0);
            $metrics['total']      = (int)($metrics['total'] ?? 0);

            // 2. Tarik Data Jobs (Recent Jobs)
            $dataSql = "
                SELECT j.*, t.nama_sekolah
                FROM sistem.queue_jobs j
                LEFT JOIN core.tenants t ON j.tenant_id = t.id
            ";
            
            $dataWhere = [];
            $dataParams = [];
            
            if (!empty($tenantId)) {
                $dataWhere[] = "j.tenant_id = :tenant_id";
                $dataParams['tenant_id'] = $tenantId;
            }
            if (!empty($filterStatus)) {
                $dataWhere[] = "j.status = :status";
                $dataParams['status'] = $filterStatus;
            }
            if (!empty($filterType)) {
                $dataWhere[] = "j.job_type = :job_type";
                $dataParams['job_type'] = $filterType;
            }

            if (!empty($dataWhere)) {
                $dataSql .= " WHERE " . implode(" AND ", $dataWhere);
            }

            $countSql = "SELECT COUNT(*) FROM sistem.queue_jobs j";
            if (!empty($dataWhere)) {
                $countSql .= " WHERE " . implode(" AND ", $dataWhere);
            }
            $stmtCount = $db->prepare($countSql);
            $stmtCount->execute($dataParams);
            $totalCount = (int)$stmtCount->fetchColumn();
            $totalPages = (int)ceil($totalCount / $limit);
            if ($totalPages < 1) $totalPages = 1;

            $dataSql .= " ORDER BY j.created_at DESC LIMIT {$limit} OFFSET {$offset}";
            $stmtData = $db->prepare($dataSql);
            $stmtData->execute($dataParams);
            $jobs = $stmtData->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($jobs as &$j) {
                if (isset($j['payload']) && is_string($j['payload'])) {
                    $j['payload'] = json_decode($j['payload'], true) ?? [];
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success'      => true,
                'metrics'      => $metrics,
                'jobs'         => $jobs,
                'current_page' => $page,
                'total_pages'  => $totalPages
            ]);
            exit;

        } catch (\Throwable $e) {
            error_log("fetchDataApi failed: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Terjadi kesalahan sistem saat memuat antrean.', 500);
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

        if ($role === 'super_admin' && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }

        if (!in_array($jobType, ['DEMO_SYNC', 'DEMO_EMAIL', 'CLEANUP_SESSIONS'], true)) {
            $this->jsonResponse(false, null, 'Tipe pekerjaan tidak valid untuk simulasi.', 400);
            return;
        }

        $success = QueueManager::push($jobType, $payload, $tenantId);

        if ($success) {
            $this->jsonResponse(true, null, "Pekerjaan '{$jobType}' berhasil ditambahkan ke antrean sistem.");
        } else {
            $this->jsonResponse(false, null, 'Gagal memasukkan pekerjaan ke antrean database.', 500);
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
        $jobId = $input['id'] ?? '';

        if (empty($jobId) || !preg_match('/^[a-f0-9\-]{36}$/i', (string)$jobId)) {
            $this->jsonResponse(false, null, 'ID Pekerjaan tidak valid.', 400);
            return;
        }

        try {
            $db = Database::getConnection();

            if ($role === 'super_admin') {
                $stmt = $db->prepare("SELECT * FROM sistem.queue_jobs WHERE id = :id::uuid LIMIT 1");
                $stmt->execute(['id' => $jobId]);
            } else {
                $stmt = $db->prepare("SELECT * FROM sistem.queue_jobs WHERE id = :id::uuid AND (tenant_id = :tenant_id::uuid OR tenant_id IS NULL) LIMIT 1");
                $stmt->execute(['id' => $jobId, 'tenant_id' => $sessionTenantId]);
            }
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                $this->jsonResponse(false, null, 'Pekerjaan tidak ditemukan.', 404);
                return;
            }

            if ($role !== 'super_admin' && !empty($sessionTenantId) && $job['tenant_id'] !== $sessionTenantId) {
                $this->jsonResponse(false, null, 'Akses ditolak. Anda tidak memiliki hak akses atas pekerjaan ini.', 403);
                return;
            }

            if ($role === 'super_admin') {
                $stmtRetry = $db->prepare("
                    UPDATE sistem.queue_jobs SET
                        status = 'pending',
                        attempts = 0,
                        error_message = NULL
                    WHERE id = :id::uuid
                ");
                $stmtRetry->execute(['id' => $jobId]);
            } else {
                $stmtRetry = $db->prepare("
                    UPDATE sistem.queue_jobs SET
                        status = 'pending',
                        attempts = 0,
                        error_message = NULL
                    WHERE id = :id::uuid AND (tenant_id = :tenant_id::uuid OR tenant_id IS NULL)
                ");
                $stmtRetry->execute(['id' => $jobId, 'tenant_id' => $sessionTenantId]);
            }

            $this->jsonResponse(true, null, "Pekerjaan sukses diatur kembali ke status pending.");

        } catch (\Throwable $e) {
            error_log("retryJobApi failed: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Gagal mengatur ulang pekerjaan.', 500);
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
        $jobId = $input['id'] ?? '';

        if (empty($jobId) || !preg_match('/^[a-f0-9\-]{36}$/i', (string)$jobId)) {
            $this->jsonResponse(false, null, 'ID Pekerjaan tidak valid.', 400);
            return;
        }

        try {
            $db = Database::getConnection();

            if ($role === 'super_admin') {
                $stmt = $db->prepare("SELECT * FROM sistem.queue_jobs WHERE id = :id::uuid LIMIT 1");
                $stmt->execute(['id' => $jobId]);
            } else {
                $stmt = $db->prepare("SELECT * FROM sistem.queue_jobs WHERE id = :id::uuid AND (tenant_id = :tenant_id::uuid OR tenant_id IS NULL) LIMIT 1");
                $stmt->execute(['id' => $jobId, 'tenant_id' => $sessionTenantId]);
            }
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                $this->jsonResponse(false, null, 'Pekerjaan tidak ditemukan.', 404);
                return;
            }

            if ($role !== 'super_admin' && !empty($sessionTenantId) && $job['tenant_id'] !== $sessionTenantId) {
                $this->jsonResponse(false, null, 'Akses ditolak.', 403);
                return;
            }

            if ($role === 'super_admin') {
                $stmtDelete = $db->prepare("DELETE FROM sistem.queue_jobs WHERE id = :id::uuid");
                $stmtDelete->execute(['id' => $jobId]);
            } else {
                $stmtDelete = $db->prepare("DELETE FROM sistem.queue_jobs WHERE id = :id::uuid AND (tenant_id = :tenant_id::uuid OR tenant_id IS NULL)");
                $stmtDelete->execute(['id' => $jobId, 'tenant_id' => $sessionTenantId]);
            }

            $this->jsonResponse(true, null, "Pekerjaan berhasil dihapus dari antrean.");

        } catch (\Throwable $e) {
            error_log("deleteJobApi failed: " . $e->getMessage());
            $this->jsonResponse(false, null, 'Gagal menghapus pekerjaan.', 500);
        }
    }

    /**
     * API: Jalankan satu pekerjaan terdepan via browser (Web Runner Simulation)
     * POST /api/v1/queue/run-worker
     */
    public function runWorkerApi(): void {
        $job = QueueManager::pop();

        if (!$job) {
            $this->jsonResponse(false, null, 'Antrean kosong. Tidak ada tugas pending.');
            return;
        }

        $id = $job['id'];
        $type = $job['job_type'];

        try {
            JobDispatcher::dispatch($job);
            QueueManager::markCompleted($id);
            
            $this->jsonResponse(true, null, "Pekerjaan '{$type}' sukses diselesaikan di latar belakang.");
        } catch (\Throwable $e) {
            QueueManager::markFailed($id, $e->getMessage());
            $this->jsonResponse(false, null, "Pekerjaan '{$type}' gagal diproses: " . $e->getMessage(), 500);
        }
    }
}
