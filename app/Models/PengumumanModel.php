<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class PengumumanModel {
    private ?string $tenantId;

    public function __construct(?string $tenantId) {
        $this->tenantId = $tenantId;
    }

    public function getAll(int $limit = 100, int $offset = 0, array $filters = []): array {
        $db = Database::getConnection();
        
        // Tenant scope
        $whereCondition = "1=1";
        if ($this->tenantId !== null) {
            $whereCondition .= " AND (p.tenant_id = :scoped_tenant_id OR p.tenant_id IS NULL)";
        }
        
        if (!empty($filters['search'])) {
            $whereCondition .= " AND p.judul LIKE :search";
        }
        if (!empty($filters['kategori_id'])) {
            $whereCondition .= " AND p.kategori_id = :kategori_id";
        }
        if (!empty($filters['tanggal'])) {
            $whereCondition .= " AND DATE(p.created_at) = :tanggal";
        }
        // Specific tenant filter from super admin
        if (!empty($filters['tenant_id'])) {
            $whereCondition .= " AND p.tenant_id = :filter_tenant_id";
        }
        
        $sql = "SELECT p.*, u.nama_lengkap as nama_pembuat, r.nama_role as pembuat_role, t.nama_sekolah, k.nama_kategori 
                FROM pengumuman p 
                JOIN users u ON p.created_by = u.id 
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN tenants t ON p.tenant_id = t.id
                LEFT JOIN kategori_pengumuman k ON p.kategori_id = k.id
                WHERE $whereCondition 
                ORDER BY p.created_at DESC 
                LIMIT :limit OFFSET :offset";
                
        $stmt = $db->prepare($sql);
        
        if ($this->tenantId !== null) {
            $stmt->bindValue(':scoped_tenant_id', $this->tenantId);
        }
        if (!empty($filters['search'])) {
            $stmt->bindValue(':search', '%' . $filters['search'] . '%');
        }
        if (!empty($filters['kategori_id'])) {
            $stmt->bindValue(':kategori_id', $filters['kategori_id']);
        }
        if (!empty($filters['tanggal'])) {
            $stmt->bindValue(':tanggal', $filters['tanggal']);
        }
        if (!empty($filters['tenant_id'])) {
            $stmt->bindValue(':filter_tenant_id', $filters['tenant_id']);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveForUser(int $userRoleId, int $limit = 100, int $offset = 0, string $searchQuery = '', string $kategoriId = '', string $tanggal = ''): array {
        $db = Database::getConnection();
        
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(p.tenant_id = :tenant_id OR p.tenant_id IS NULL)";
        
        $isAdmin = in_array($userRoleId, [1, 2]) ? 1 : 0;
        $isGuru = in_array($userRoleId, [3, 20, 21]) ? 1 : 0;
        $isSiswa = ($userRoleId == 6) ? 1 : 0;
        
        $roleCondition = "(:is_admin = 1 OR p.visibilitas = 'public' 
                          OR (p.visibilitas = 'guru' AND :is_guru = 1) 
                          OR (p.visibilitas = 'siswa' AND :is_siswa = 1) 
                          OR (p.visibilitas = 'private' AND p.target_roles LIKE :role_id_like))";
                          
        $searchCondition = "";
        if (!empty($searchQuery)) {
            $searchCondition .= " AND p.judul LIKE :search ";
        }
        if (!empty($kategoriId)) {
            $searchCondition .= " AND p.kategori_id = :kategori_id ";
        }
        if (!empty($tanggal)) {
            $searchCondition .= " AND DATE(p.created_at) = :tanggal ";
        }
        
        $sql = "SELECT p.*, u.nama_lengkap as nama_pembuat, r.nama_role as pembuat_role, t.nama_sekolah, k.nama_kategori 
                FROM pengumuman p 
                JOIN users u ON p.created_by = u.id 
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN tenants t ON p.tenant_id = t.id
                LEFT JOIN kategori_pengumuman k ON p.kategori_id = k.id
                WHERE $whereTenant AND p.is_active = 1 AND $roleCondition $searchCondition
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
                
        $stmt = $db->prepare($sql);
        
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        $stmt->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);
        $stmt->bindValue(':is_guru', $isGuru, PDO::PARAM_INT);
        $stmt->bindValue(':is_siswa', $isSiswa, PDO::PARAM_INT);
        $stmt->bindValue(':role_id_like', '%"' . $userRoleId . '"%');
        
        if (!empty($searchQuery)) {
            $stmt->bindValue(':search', '%' . $searchQuery . '%');
        }
        if (!empty($kategoriId)) {
            $stmt->bindValue(':kategori_id', $kategoriId);
        }
        if (!empty($tanggal)) {
            $stmt->bindValue(':tanggal', $tanggal);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function countActiveForUser(int $userRoleId, string $searchQuery = '', string $kategoriId = '', string $tanggal = ''): int {
        $db = Database::getConnection();
        
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(p.tenant_id = :tenant_id OR p.tenant_id IS NULL)";
        
        $isAdmin = in_array($userRoleId, [1, 2]) ? 1 : 0;
        $isGuru = in_array($userRoleId, [3, 20, 21]) ? 1 : 0;
        $isSiswa = ($userRoleId == 6) ? 1 : 0;
        
        $roleCondition = "(:is_admin = 1 OR p.visibilitas = 'public' 
                          OR (p.visibilitas = 'guru' AND :is_guru = 1) 
                          OR (p.visibilitas = 'siswa' AND :is_siswa = 1) 
                          OR (p.visibilitas = 'private' AND p.target_roles LIKE :role_id_like))";
                          
        $searchCondition = "";
        if (!empty($searchQuery)) {
            $searchCondition .= " AND p.judul LIKE :search ";
        }
        if (!empty($kategoriId)) {
            $searchCondition .= " AND p.kategori_id = :kategori_id ";
        }
        if (!empty($tanggal)) {
            $searchCondition .= " AND DATE(p.created_at) = :tanggal ";
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM pengumuman p 
                WHERE $whereTenant AND p.is_active = 1 AND $roleCondition $searchCondition";
                
        $stmt = $db->prepare($sql);
        
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        $stmt->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);
        $stmt->bindValue(':is_guru', $isGuru, PDO::PARAM_INT);
        $stmt->bindValue(':is_siswa', $isSiswa, PDO::PARAM_INT);
        $stmt->bindValue(':role_id_like', '%"' . $userRoleId . '"%');
        
        if (!empty($searchQuery)) {
            $stmt->bindValue(':search', '%' . $searchQuery . '%');
        }
        if (!empty($kategoriId)) {
            $stmt->bindValue(':kategori_id', $kategoriId);
        }
        if (!empty($tanggal)) {
            $stmt->bindValue(':tanggal', $tanggal);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function findById(string $id): ?array {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(tenant_id = :tenant_id OR tenant_id IS NULL)";
        $stmt = $db->prepare("SELECT * FROM pengumuman WHERE id = :id AND $whereTenant");
        $params = ['id' => $id];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO pengumuman 
            (id, tenant_id, kategori_id, created_by, judul, isi_pengumuman, lampiran_file, visibilitas, target_roles, is_active)
            VALUES 
            (UUID(), :tenant_id, :kategori_id, :created_by, :judul, :isi_pengumuman, :lampiran_file, :visibilitas, :target_roles, :is_active)
        ");
        return $stmt->execute([
            'tenant_id'      => array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $this->tenantId,
            'kategori_id'    => !empty($data['kategori_id']) ? $data['kategori_id'] : null,
            'created_by'     => $data['created_by'],
            'judul'          => $data['judul'],
            'isi_pengumuman' => $data['isi_pengumuman'],
            'lampiran_file'  => $data['lampiran_file'] ?? null,
            'visibilitas'    => $data['visibilitas'],
            'target_roles'   => !empty($data['target_roles']) ? json_encode($data['target_roles']) : null,
            'is_active'      => $data['is_active'] ?? 1
        ]);
    }

    public function update(string $id, array $data): bool {
        $db = Database::getConnection();
        
        $setTenantClause = "";
        $params = [
            'judul'          => $data['judul'],
            'kategori_id'    => !empty($data['kategori_id']) ? $data['kategori_id'] : null,
            'isi_pengumuman' => $data['isi_pengumuman'],
            'lampiran_file'  => $data['lampiran_file'] ?? null,
            'visibilitas'    => $data['visibilitas'],
            'target_roles'   => !empty($data['target_roles']) ? json_encode($data['target_roles']) : null,
            'is_active'      => $data['is_active'] ?? 1,
            'id'             => $id
        ];

        if (array_key_exists('tenant_id', $data)) {
            $setTenantClause = "tenant_id = :set_tenant_id, ";
            $params['set_tenant_id'] = $data['tenant_id'];
        }

        $whereTenant = ($this->tenantId === null) ? "1=1" : "tenant_id = :where_tenant_id";
        if ($this->tenantId !== null) {
            $params['where_tenant_id'] = $this->tenantId;
        }

        $stmt = $db->prepare("
            UPDATE pengumuman SET 
                $setTenantClause
                judul = :judul, 
                kategori_id = :kategori_id,
                isi_pengumuman = :isi_pengumuman, 
                lampiran_file = :lampiran_file, 
                visibilitas = :visibilitas, 
                target_roles = :target_roles, 
                is_active = :is_active
            WHERE id = :id AND $whereTenant
        ");
        
        return $stmt->execute($params);
    }

    public function delete(string $id): bool {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(tenant_id = :tenant_id OR tenant_id IS NULL)";
        $stmt = $db->prepare("DELETE FROM pengumuman WHERE id = :id AND $whereTenant");
        $params = ['id' => $id];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }
        return $stmt->execute($params);
    }
}
