<?php

namespace App\Controllers;

use App\Core\SessionManager;
use PDO;

class KunciAkademikController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    private function hasAuthority() {
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $allowed = array_intersect($roles, ['super_admin', 'operator_sekolah']);
        return !empty($allowed);
    }

    public function getStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
        }

        $tenantId = SessionManager::getTenantId();
        $filterTenant = $_GET['filter_tenant_id'] ?? '';
        
        // Super admin filter
        if (!$tenantId && $filterTenant) {
            $tenantId = $filterTenant;
        }

        if (!$tenantId) {
            $this->jsonResponse(['is_locked_kurikulum' => 0, 'is_locked_nilai' => 0]);
        }

        $tahunAjaran = $_GET['tahun_ajaran'] ?? '';
        $semester = $_GET['semester'] ?? '';

        if (!$tahunAjaran || !$semester) {
            $this->jsonResponse(['is_locked_kurikulum' => 0, 'is_locked_nilai' => 0]);
        }

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("SELECT is_locked_kurikulum, is_locked_nilai FROM kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ?");
        $stmt->execute([$tenantId, $tahunAjaran, $semester]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $this->jsonResponse([
                'is_locked_kurikulum' => (int)$row['is_locked_kurikulum'],
                'is_locked_nilai' => (int)$row['is_locked_nilai']
            ]);
        } else {
            $this->jsonResponse(['is_locked_kurikulum' => 0, 'is_locked_nilai' => 0]);
        }
    }

    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
        }

        if (!$this->hasAuthority()) {
            $this->jsonResponse(['error' => 'Anda tidak memiliki otoritas untuk mengunci atau membuka kunci.'], 403);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $tenantId = SessionManager::getTenantId();
        
        if (!$tenantId && !empty($data['filter_tenant_id'])) {
            $tenantId = $data['filter_tenant_id'];
        }

        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant/Sekolah tidak valid'], 400);
        }

        $tahunAjaran = $data['tahun_ajaran'] ?? '';
        $semester = $data['semester'] ?? '';
        $type = $data['type'] ?? ''; // 'kurikulum' or 'nilai'
        $status = isset($data['status']) ? (int)$data['status'] : null;

        if (!$tahunAjaran || !$semester || !in_array($type, ['kurikulum', 'nilai']) || $status === null) {
            $this->jsonResponse(['error' => 'Data parameter tidak lengkap.'], 400);
        }

        $db = \App\Config\Database::getConnection();
        
        // Check if exists
        $stmt = $db->prepare("SELECT id FROM kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ?");
        $stmt->execute([$tenantId, $tahunAjaran, $semester]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Update
            $col = $type === 'kurikulum' ? 'is_locked_kurikulum' : 'is_locked_nilai';
            $update = $db->prepare("UPDATE kunci_akademik SET {$col} = ? WHERE id = ?");
            $update->execute([$status, $row['id']]);
        } else {
            // Insert
            $col = $type === 'kurikulum' ? 'is_locked_kurikulum' : 'is_locked_nilai';
            $insert = $db->prepare("INSERT INTO kunci_akademik (tenant_id, tahun_ajaran, semester, {$col}) VALUES (?, ?, ?, ?)");
            $insert->execute([$tenantId, $tahunAjaran, $semester, $status]);
        }

        $this->jsonResponse(['success' => true, 'message' => 'Status kunci berhasil diperbarui.']);
    }
}
