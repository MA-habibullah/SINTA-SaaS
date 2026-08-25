<?php

namespace App\Modules\Core\Models;

use App\Config\Database;
use PDO;

class PengumumanModel {
    private ?string $tenantId;

    public function __construct(?string $tenantId = null) {
        $this->tenantId = $tenantId;
    }

    public function getAll(array $filters = [], int $limit = 50, int $offset = 0): array {
        $db = Database::getConnection();
        
        $params = [];
        $whereCondition = "1=1";

        // Tenant Filter Resolution
        $targetTenant = !empty($filters['tenant_id']) ? $filters['tenant_id'] : $this->tenantId;
        if (!empty($targetTenant)) {
            if ($targetTenant === 'global' || $targetTenant === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
                $whereCondition = "(p.tenant_id IS NULL OR p.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
            } else {
                $whereCondition = "p.tenant_id = :tenant_id";
                $params[':tenant_id'] = $targetTenant;
            }
        }

        if (!empty($filters['search'])) {
            $whereCondition .= " AND (p.judul ILIKE :search OR p.deskripsi ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['kategori_id'])) {
            $whereCondition .= " AND p.kategori_id = :kategori_id";
            $params[':kategori_id'] = $filters['kategori_id'];
        }
        if (!empty($filters['visibilitas'])) {
            $whereCondition .= " AND p.visibilitas = :visibilitas";
            $params[':visibilitas'] = $filters['visibilitas'];
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== 'all') {
            $whereCondition .= " AND p.is_active = :is_active";
            $params[':is_active'] = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'TRUE' : 'FALSE';
        }
        if (!empty($filters['tanggal'])) {
            $whereCondition .= " AND DATE(p.created_at) = :tanggal";
            $params[':tanggal'] = $filters['tanggal'];
        }
        
        $sql = "SELECT p.*, p.deskripsi as isi_pengumuman, COALESCE(u.nama_lengkap, 'Administrator') as nama_pembuat, COALESCE(r.nama_role, 'super_admin') as pembuat_role, COALESCE(t.nama_sekolah, 'Global / Pusat') as nama_sekolah, COALESCE(k.nama_kategori, 'Umum') as nama_kategori 
                FROM sistem.pengumuman p 
                LEFT JOIN core.users u ON p.created_by = u.id 
                LEFT JOIN core.roles r ON u.role_id = r.id
                LEFT JOIN core.tenants t ON p.tenant_id = t.id
                LEFT JOIN sistem.kategori_pengumuman k ON p.kategori_id = k.id
                WHERE $whereCondition 
                ORDER BY p.created_at DESC 
                LIMIT :limit OFFSET :offset";
                
        $stmt = $db->prepare($sql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($results as &$r) {
            $r['is_active'] = (bool)$r['is_active'];
        }
        return $results;
    }

    public function countAll(array $filters = []): int {
        $db = Database::getConnection();
        
        $params = [];
        $whereCondition = "1=1";

        // Tenant Filter Resolution
        $targetTenant = !empty($filters['tenant_id']) ? $filters['tenant_id'] : $this->tenantId;
        if (!empty($targetTenant)) {
            if ($targetTenant === 'global' || $targetTenant === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
                $whereCondition = "(p.tenant_id IS NULL OR p.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
            } else {
                $whereCondition = "p.tenant_id = :tenant_id";
                $params[':tenant_id'] = $targetTenant;
            }
        }

        if (!empty($filters['search'])) {
            $whereCondition .= " AND (p.judul ILIKE :search OR p.deskripsi ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['kategori_id'])) {
            $whereCondition .= " AND p.kategori_id = :kategori_id";
            $params[':kategori_id'] = $filters['kategori_id'];
        }
        if (!empty($filters['visibilitas'])) {
            $whereCondition .= " AND p.visibilitas = :visibilitas";
            $params[':visibilitas'] = $filters['visibilitas'];
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== 'all') {
            $whereCondition .= " AND p.is_active = :is_active";
            $params[':is_active'] = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'TRUE' : 'FALSE';
        }
        if (!empty($filters['tanggal'])) {
            $whereCondition .= " AND DATE(p.created_at) = :tanggal";
            $params[':tanggal'] = $filters['tanggal'];
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM sistem.pengumuman p 
                WHERE $whereCondition";
                
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }
    
    public function getActiveForUser(string $roleName, int $limit = 5, int $offset = 0, string $searchQuery = '', string $kategoriId = '', string $tanggal = ''): array {
        $db = Database::getConnection();
        
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(p.tenant_id = :tenant_id OR p.tenant_id IS NULL OR p.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
        
        $roleNameStr = strtolower($roleName);
        $isAdmin = in_array($roleNameStr, ['super_admin', 'admin', 'admin_sekolah']) ? 1 : 0;
        $guruRoles = ['guru', 'staff', 'karyawan', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'waka_sarpras', 'waka_humas', 'pembina_osis', 'pembina_ekskul', 'koordinator_bk'];
        $isGuru = in_array($roleNameStr, $guruRoles) ? 1 : 0;
        $isSiswa = ($roleNameStr === 'siswa') ? 1 : 0;
        
        $roleCondition = "(:is_admin = 1 OR p.visibilitas = 'public' 
                          OR (p.visibilitas = 'guru' AND :is_guru = 1) 
                          OR (p.visibilitas = 'siswa' AND :is_siswa = 1) 
                          OR (p.visibilitas = 'private' AND p.target_roles LIKE :role_name_like))";
                          
        $searchCondition = "";
        if (!empty($searchQuery)) {
            $searchCondition .= " AND p.judul ILIKE :search ";
        }
        if (!empty($kategoriId)) {
            $searchCondition .= " AND p.kategori_id = :kategori_id ";
        }
        if (!empty($tanggal)) {
            $searchCondition .= " AND DATE(p.created_at) = :tanggal ";
        }
        
        $sql = "SELECT p.*, p.deskripsi as isi_pengumuman, u.nama_lengkap as nama_pembuat, r.nama_role as pembuat_role, COALESCE(t.nama_sekolah, 'Global / Pusat') as nama_sekolah, COALESCE(k.nama_kategori, 'Umum') as nama_kategori 
                FROM sistem.pengumuman p 
                LEFT JOIN core.users u ON p.created_by = u.id 
                LEFT JOIN core.roles r ON u.role_id = r.id
                LEFT JOIN core.tenants t ON p.tenant_id = t.id
                LEFT JOIN sistem.kategori_pengumuman k ON p.kategori_id = k.id
                WHERE $whereTenant AND p.is_active = true AND $roleCondition $searchCondition
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
                
        $stmt = $db->prepare($sql);
        
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        $stmt->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);
        $stmt->bindValue(':is_guru', $isGuru, PDO::PARAM_INT);
        $stmt->bindValue(':is_siswa', $isSiswa, PDO::PARAM_INT);
        $stmt->bindValue(':role_name_like', '%"' . $roleNameStr . '"%');
        
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
    
    public function countActiveForUser(string $roleName, string $searchQuery = '', string $kategoriId = '', string $tanggal = ''): int {
        $db = Database::getConnection();
        
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(p.tenant_id = :tenant_id OR p.tenant_id IS NULL OR p.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
        
        $roleNameStr = strtolower($roleName);
        $isAdmin = in_array($roleNameStr, ['super_admin', 'admin', 'admin_sekolah']) ? 1 : 0;
        $guruRoles = ['guru', 'staff', 'karyawan', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'waka_sarpras', 'waka_humas', 'pembina_osis', 'pembina_ekskul', 'koordinator_bk'];
        $isGuru = in_array($roleNameStr, $guruRoles) ? 1 : 0;
        $isSiswa = ($roleNameStr === 'siswa') ? 1 : 0;
        
        $roleCondition = "(:is_admin = 1 OR p.visibilitas = 'public' 
                          OR (p.visibilitas = 'guru' AND :is_guru = 1) 
                          OR (p.visibilitas = 'siswa' AND :is_siswa = 1) 
                          OR (p.visibilitas = 'private' AND p.target_roles LIKE :role_name_like))";
                          
        $searchCondition = "";
        if (!empty($searchQuery)) {
            $searchCondition .= " AND p.judul ILIKE :search ";
        }
        if (!empty($kategoriId)) {
            $searchCondition .= " AND p.kategori_id = :kategori_id ";
        }
        if (!empty($tanggal)) {
            $searchCondition .= " AND DATE(p.created_at) = :tanggal ";
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM sistem.pengumuman p 
                WHERE $whereTenant AND p.is_active = true AND $roleCondition $searchCondition";
                
        $stmt = $db->prepare($sql);
        
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        $stmt->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);
        $stmt->bindValue(':is_guru', $isGuru, PDO::PARAM_INT);
        $stmt->bindValue(':is_siswa', $isSiswa, PDO::PARAM_INT);
        $stmt->bindValue(':role_name_like', '%"' . $roleNameStr . '"%');
        
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

    public function getStatsSummary(?string $tenantId = null): array {
        $db = Database::getConnection();
        $targetTenant = $tenantId !== null ? $tenantId : $this->tenantId;
        
        $params = [];
        $paramsKat = [];
        $whereTenant = "1=1";
        $whereKategori = "1=1";

        if (!empty($targetTenant)) {
            if ($targetTenant === 'global' || $targetTenant === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
                $whereTenant = "(p.tenant_id IS NULL OR p.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
                $whereKategori = "(k.tenant_id IS NULL OR k.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
            } else {
                $whereTenant = "p.tenant_id = :tenant_id";
                $params[':tenant_id'] = $targetTenant;

                $whereKategori = "k.tenant_id = :tenant_id";
                $paramsKat[':tenant_id'] = $targetTenant;
            }
        }

        $sql = "SELECT 
                    COUNT(*) as total_pengumuman,
                    COUNT(CASE WHEN p.is_active = true THEN 1 END) as total_aktif,
                    COUNT(CASE WHEN p.visibilitas = 'public' THEN 1 END) as total_public,
                    COUNT(DISTINCT p.kategori_id) as total_kategori
                FROM sistem.pengumuman p
                WHERE $whereTenant";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Count total categories in master (terisolasi tenant secara presisi)
        $sqlKat = "SELECT COUNT(*) FROM sistem.kategori_pengumuman k WHERE $whereKategori";
        $stmtKat = $db->prepare($sqlKat);
        $stmtKat->execute($paramsKat);
        $totalKatMaster = (int)$stmtKat->fetchColumn();

        return [
            'total_pengumuman' => (int)($row['total_pengumuman'] ?? 0),
            'total_aktif' => (int)($row['total_aktif'] ?? 0),
            'total_public' => (int)($row['total_public'] ?? 0),
            'total_kategori' => $totalKatMaster
        ];
    }

    public function toggleActive(string $id, ?string $tenantId = null): bool {
        $db = Database::getConnection();
        $targetTenant = $tenantId ?? $this->tenantId;
        $stmt = $db->prepare("
            UPDATE sistem.pengumuman 
            SET is_active = NOT is_active, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id::uuid AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)
            RETURNING is_active
        ");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $targetTenant);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    public function findById(string $id, ?string $tenantId = null): ?array {
        $db = Database::getConnection();
        $targetTenant = $tenantId ?? $this->tenantId;
        $stmt = $db->prepare("
            SELECT p.*, p.deskripsi as isi_pengumuman, COALESCE(u.nama_lengkap, 'Administrator') as nama_pembuat, COALESCE(r.nama_role, 'super_admin') as pembuat_role, t.nama_sekolah, COALESCE(k.nama_kategori, 'Umum') as nama_kategori
            FROM sistem.pengumuman p 
            LEFT JOIN core.users u ON p.created_by = u.id 
            LEFT JOIN core.roles r ON u.role_id = r.id
            LEFT JOIN core.tenants t ON p.tenant_id = t.id
            LEFT JOIN sistem.kategori_pengumuman k ON p.kategori_id = k.id
            WHERE p.id = :id::uuid AND (:tenant_id::uuid IS NULL OR p.tenant_id = :tenant_id::uuid OR p.tenant_id IS NULL)
        ");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $targetTenant);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['is_active'] = (bool)$row['is_active'];
        }
        return $row ?: null;
    }

    public function create(array $data): string {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO sistem.pengumuman 
            (id, tenant_id, kategori_id, created_by, judul, deskripsi, visibilitas, target_roles, is_active, created_at, updated_at)
            VALUES 
            (gen_random_uuid(), :tenant_id, :kategori_id, :created_by, :judul, :deskripsi, :visibilitas, :target_roles, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        
        $isActive = array_key_exists('is_active', $data) ? (filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'TRUE' : 'FALSE') : 'TRUE';
        $targetRoles = !empty($data['target_roles']) ? (is_array($data['target_roles']) ? json_encode(array_values($data['target_roles'])) : $data['target_roles']) : null;
        $kategoriId = !empty($data['kategori_id']) ? $data['kategori_id'] : null;
        $tenantId = array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $this->tenantId;

        $stmt->execute([
            'tenant_id'      => $tenantId,
            'kategori_id'    => $kategoriId,
            'created_by'     => $data['created_by'] ?? null,
            'judul'          => $data['judul'],
            'deskripsi'      => $data['isi_pengumuman'] ?? ($data['deskripsi'] ?? ''),
            'visibilitas'    => $data['visibilitas'] ?? 'public',
            'target_roles'   => $targetRoles,
            'is_active'      => $isActive
        ]);
        return (string)$stmt->fetchColumn();
    }

    public function update(string $id, array $data, ?string $tenantId = null): bool {
        $db = Database::getConnection();
        $targetTenant = $tenantId ?? $this->tenantId;
        
        $isActive = array_key_exists('is_active', $data) ? (filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'TRUE' : 'FALSE') : 'TRUE';
        $targetRoles = !empty($data['target_roles']) ? (is_array($data['target_roles']) ? json_encode(array_values($data['target_roles'])) : $data['target_roles']) : null;
        $kategoriId = !empty($data['kategori_id']) ? $data['kategori_id'] : null;
        $newTenantId = array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $targetTenant;

        $sql = "UPDATE sistem.pengumuman SET 
                    tenant_id = :set_tenant_id,
                    judul = :judul, 
                    kategori_id = :kategori_id,
                    deskripsi = :deskripsi, 
                    visibilitas = :visibilitas, 
                    target_roles = :target_roles, 
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id::uuid AND (:filter_tenant_id::uuid IS NULL OR tenant_id = :filter_tenant_id::uuid OR tenant_id IS NULL)";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':set_tenant_id', $newTenantId);
        $stmt->bindValue(':judul', $data['judul'] ?? '');
        $stmt->bindValue(':kategori_id', $kategoriId);
        $stmt->bindValue(':deskripsi', $data['isi_pengumuman'] ?? ($data['deskripsi'] ?? ''));
        $stmt->bindValue(':visibilitas', $data['visibilitas'] ?? 'public');
        $stmt->bindValue(':target_roles', $targetRoles);
        $stmt->bindValue(':is_active', $isActive);
        $stmt->bindValue(':filter_tenant_id', $targetTenant);

        return $stmt->execute();
    }

    public function delete(string $id, ?string $tenantId = null): bool {
        $db = Database::getConnection();
        $targetTenant = $tenantId ?? $this->tenantId;
        $sql = "DELETE FROM sistem.pengumuman WHERE id = :id::uuid AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $targetTenant);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
