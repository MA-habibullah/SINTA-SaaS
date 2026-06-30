<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class PengumumanModel {
    private ?string $tenantId;

    public function __construct(?string $tenantId) {
        $this->tenantId = $tenantId;
    }

    public function getAll(int $limit = 100, int $offset = 0): array {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(p.tenant_id = :tenant_id OR p.tenant_id IS NULL)";
        $sql = "SELECT p.*, u.nama_lengkap as nama_pembuat, r.nama_role as pembuat_role, t.nama_sekolah 
                FROM pengumuman p 
                JOIN users u ON p.created_by = u.id 
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN tenants t ON p.tenant_id = t.id
                WHERE $whereTenant 
                ORDER BY p.created_at DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveForUser(int $userRoleId): array {
        $db = Database::getConnection();
        // Public + Role specific logic
        // We fetch all active announcements where visibilitas = public OR
        // visibilitas = 'guru' AND role is guru OR
        // visibilitas = 'siswa' AND role is siswa OR
        // visibilitas = 'private' AND userRoleId IN (target_roles)
        
        $whereTenant = ($this->tenantId === null) ? "p.tenant_id IS NULL" : "(p.tenant_id = :tenant_id OR p.tenant_id IS NULL)";
        $sql = "SELECT p.*, u.nama_lengkap as nama_pembuat, r.nama_role as pembuat_role 
                FROM pengumuman p 
                JOIN users u ON p.created_by = u.id 
                JOIN roles r ON u.role_id = r.id
                WHERE $whereTenant AND p.is_active = 1
                ORDER BY p.created_at DESC";
        $stmt = $db->prepare($sql);
        $params = [];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }
        $stmt->execute($params);
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filtered = [];
        $isAdmin = in_array($userRoleId, [1, 2]); // Super Admin, Operator Sekolah
        $isGuru = in_array($userRoleId, [3, 20, 21]); // Mapel, BK, Pembina
        $isSiswa = ($userRoleId == 6);
        
        foreach ($all as $p) {
            $vis = $p['visibilitas'];
            if ($isAdmin || $vis === 'public') {
                $filtered[] = $p;
            } elseif ($vis === 'guru' && $isGuru) {
                $filtered[] = $p;
            } elseif ($vis === 'siswa' && $isSiswa) {
                $filtered[] = $p;
            } elseif ($vis === 'private' && !empty($p['target_roles'])) {
                $targets = json_decode($p['target_roles'], true) ?? [];
                if (in_array((string)$userRoleId, $targets) || in_array($userRoleId, $targets)) {
                    $filtered[] = $p;
                }
            }
        }
        
        return $filtered;
    }

    public function findById(string $id): ?array {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "tenant_id IS NULL" : "tenant_id = :tenant_id";
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
            (id, tenant_id, created_by, judul, isi_pengumuman, lampiran_file, visibilitas, target_roles, is_active)
            VALUES 
            (UUID(), :tenant_id, :created_by, :judul, :isi_pengumuman, :lampiran_file, :visibilitas, :target_roles, :is_active)
        ");
        return $stmt->execute([
            'tenant_id'      => array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $this->tenantId,
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
        $whereTenant = ($this->tenantId === null) ? "tenant_id IS NULL" : "tenant_id = :tenant_id";
        $stmt = $db->prepare("DELETE FROM pengumuman WHERE id = :id AND $whereTenant");
        $params = ['id' => $id];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }
        return $stmt->execute($params);
    }
}
