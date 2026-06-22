<?php

namespace App\Controllers;

use App\Models\Tenant;
use App\Core\SessionManager;

class TenantManagementController extends BaseController {

    private Tenant $tenantModel;

    public function __construct() {
        parent::__construct();

        // 1. Gate Keamanan: Wajib Login
        SessionManager::requireLogin();

        // 2. Middleware khusus CheckIsSuperAdmin (Cybersecurity Enforcement)
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName !== 'super_admin') {
            $isApi = str_starts_with($_SERVER['REQUEST_URI'], '/dapodik-spmb/api/');
            if ($isApi) {
                $this->jsonResponse(['error' => 'Akses ditolak. Fitur ini memerlukan wewenang Super Admin Platform.'], 403);
            } else {
                header('Location: /dapodik-spmb/dashboard?error=' . urlencode('Akses ditolak. Halaman tersebut khusus untuk Super Admin Platform.'));
                exit;
            }
        }

        $this->tenantModel = new Tenant();
    }

    /**
     * Tampilkan halaman utama kelola sekolah (SaaS Tenant Management)
     * GET /super-admin/tenants
     */
    public function index(): void {
        $data = [
            'title' => 'Kelola Sekolah (SaaS Tenant Management)',
            'user_nama' => $_SESSION['nama_lengkap'] ?? 'Super Admin',
            'user_role' => $_SESSION['role_name'] ?? 'super_admin'
        ];

        $this->render('tenants_index', $data);
    }

    /**
     * API: Ambil daftar seluruh sekolah (tenants)
     * GET /api/v1/super-admin/tenants
     */
    public function fetchApi(): void {
        try {
            $tenants = $this->tenantModel->findAll();
            $this->jsonResponse([
                'success' => true,
                'data' => $tenants
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to fetch tenants: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat mengambil data sekolah.'], 500);
        }
    }

    /**
     * API: Simpan (Tambah Baru / Update) Sekolah (SaaS Tenant)
     * POST /api/v1/super-admin/tenants/simpan
     */
    public function storeApi(): void {
        $input = $this->getJsonInput();

        $id = isset($input['id']) ? trim($input['id']) : '';
        $isEdit = !empty($id);

        $namaSekolah = isset($input['nama_sekolah']) ? strip_tags(trim($input['nama_sekolah'])) : '';
        $npsn = isset($input['npsn']) ? trim($input['npsn']) : '';
        $subdomain = isset($input['subdomain']) ? strtolower(trim($input['subdomain'])) : '';
        $domain = isset($input['domain']) ? strtolower(trim($input['domain'])) : '';
        $paketAktif = isset($input['paket_aktif']) ? trim($input['paket_aktif']) : 'Premium SaaS';
        $statusSinkronisasi = isset($input['status_sinkronisasi']) ? trim($input['status_sinkronisasi']) : 'Tersinkronisasi';
        $status = isset($input['status']) ? trim($input['status']) : 'active';

        // Extract new capacity and feature parameters
        $storageLimitMb = isset($input['storage_limit_mb']) ? (int)$input['storage_limit_mb'] : 100;
        $maxSiswaLimit = isset($input['max_siswa_limit']) ? (int)$input['max_siswa_limit'] : 500;
        $maxStaffLimit = isset($input['max_staff_limit']) ? (int)$input['max_staff_limit'] : 50;
        $enableBk = isset($input['enable_bk']) ? (int)$input['enable_bk'] : 1;
        $enableTracer = isset($input['enable_tracer']) ? (int)$input['enable_tracer'] : 1;

        // 1. Validasi Kolom
        $errors = [];

        if (empty($namaSekolah)) {
            $errors['nama_sekolah'] = ['Nama sekolah wajib diisi.'];
        } elseif (strlen($namaSekolah) > 255) {
            $errors['nama_sekolah'] = ['Nama sekolah tidak boleh melebihi 255 karakter.'];
        }

        if (empty($npsn)) {
            $errors['npsn'] = ['NPSN wajib diisi.'];
        } elseif (!preg_match('/^[0-9]{8}$/', $npsn)) {
            $errors['npsn'] = ['NPSN harus berupa tepat 8 digit angka.'];
        } elseif (!$this->tenantModel->isNpsnUnique($npsn, $isEdit ? $id : null)) {
            $errors['npsn'] = ['NPSN sudah terdaftar pada sekolah lain.'];
        }

        if (empty($subdomain)) {
            $errors['subdomain'] = ['Subdomain wajib diisi.'];
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            $errors['subdomain'] = ['Subdomain hanya boleh berisi huruf kecil, angka, dan tanda hubung (-).'];
        } elseif (strlen($subdomain) > 100) {
            $errors['subdomain'] = ['Subdomain tidak boleh melebihi 100 karakter.'];
        } elseif (!$this->tenantModel->isSubdomainUnique($subdomain, $isEdit ? $id : null)) {
            $errors['subdomain'] = ['Subdomain sudah terdaftar pada sekolah lain.'];
        }

        if (!empty($domain)) {
            if (strlen($domain) > 255) {
                $errors['domain'] = ['Domain tidak boleh melebihi 255 karakter.'];
            } elseif (!$this->tenantModel->isDomainUnique($domain, $isEdit ? $id : null)) {
                $errors['domain'] = ['Domain kustom sudah terdaftar pada sekolah lain.'];
            }
        }

        $allowedPaket = ['Basic', 'Pro', 'Premium SaaS', 'Enterprise SaaS'];
        if (!in_array($paketAktif, $allowedPaket)) {
            $errors['paket_aktif'] = ['Paket langganan tidak valid.'];
        }

        if ($storageLimitMb <= 0) {
            $errors['storage_limit_mb'] = ['Limit penyimpanan harus lebih besar dari 0 MB.'];
        }
        if ($maxSiswaLimit < 0) {
            $errors['max_siswa_limit'] = ['Batas maksimal siswa tidak boleh bernilai negatif.'];
        }
        if ($maxStaffLimit < 0) {
            $errors['max_staff_limit'] = ['Batas maksimal guru & staf tidak boleh bernilai negatif.'];
        }

        $allowedSinkronisasi = ['Tersinkronisasi', 'Menunggu', 'Gagal'];
        if (!in_array($statusSinkronisasi, $allowedSinkronisasi)) {
            $errors['status_sinkronisasi'] = ['Status sinkronisasi tidak valid.'];
        }

        $allowedStatus = ['active', 'inactive', 'suspended'];
        if (!in_array($status, $allowedStatus)) {
            $errors['status'] = ['Status akses tidak valid.'];
        }

        if (!empty($errors)) {
            $this->jsonResponse(['errors' => $errors], 422);
        }

        try {
            $data = [
                'nama_sekolah' => $namaSekolah,
                'npsn' => $npsn,
                'subdomain' => $subdomain,
                'domain' => $domain,
                'paket_aktif' => $paketAktif,
                'status_sinkronisasi' => $statusSinkronisasi,
                'status' => $status,
                'storage_limit_mb' => $storageLimitMb,
                'max_siswa_limit' => $maxSiswaLimit,
                'max_staff_limit' => $maxStaffLimit,
                'enable_bk' => $enableBk,
                'enable_tracer' => $enableTracer
            ];

            if ($isEdit) {
                // UPDATE
                $this->tenantModel->updateTenant($id, $data);
                $message = 'Sekolah berhasil diperbarui.';
            } else {
                // CREATE
                $id = $this->tenantModel->create($data);
                $message = 'Sekolah baru berhasil didaftarkan.';
            }

            $this->jsonResponse([
                'success' => true,
                'message' => $message,
                'data' => array_merge(['id' => $id], $data)
            ]);

        } catch (\Throwable $e) {
            error_log("Failed to save tenant: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat menyimpan data sekolah.'], 500);
        }
    }

    /**
     * API: Hapus Sekolah (Soft Delete)
     * POST /api/v1/super-admin/tenants/hapus
     */
    public function deleteApi(): void {
        $input = $this->getJsonInput();
        $id = isset($input['id']) ? trim($input['id']) : '';

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID Sekolah tidak valid.'], 400);
        }

        try {
            $tenant = $this->tenantModel->findById($id);
            if (!$tenant) {
                $this->jsonResponse(['error' => 'Sekolah tidak ditemukan.'], 404);
            }

            $this->tenantModel->deleteTenant($id);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Sekolah berhasil dihapus (Soft Delete).'
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to soft delete tenant: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat menghapus data sekolah.'], 500);
        }
    }

    /**
     * API: Ubah Status Akses Tenant (active / inactive / suspended)
     * POST /api/v1/super-admin/tenants/toggle-status
     */
    public function toggleStatusApi(): void {
        $input = $this->getJsonInput();
        $id = isset($input['id']) ? trim($input['id']) : '';
        $status = isset($input['status']) ? trim($input['status']) : '';

        if (empty($id) || empty($status)) {
            $this->jsonResponse(['error' => 'ID Sekolah dan Status wajib diisi.'], 400);
        }

        $allowedStatus = ['active', 'inactive', 'suspended'];
        if (!in_array($status, $allowedStatus)) {
            $this->jsonResponse(['error' => 'Status akses tidak valid.'], 400);
        }

        try {
            $tenant = $this->tenantModel->findById($id);
            if (!$tenant) {
                $this->jsonResponse(['error' => 'Sekolah tidak ditemukan.'], 404);
            }

            // Update status
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("UPDATE tenants SET status = :status, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL");
            $stmt->execute(['status' => $status, 'id' => $id]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Status akses sekolah berhasil diperbarui.'
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to update tenant status: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat memperbarui status akses.'], 500);
        }
    }
}
