<?php

namespace App\Modules\Core\Models;

use App\Config\Database;
use PDO;

class KategoriPengumumanModel {
    private PDO $db;
    private ?string $tenantId;

    public function __construct(?string $tenantId = null) {
        $this->db = Database::getConnection();
        $this->tenantId = $tenantId;
    }

    public function getAll(array $filters = []): array {
        $params = [];
        $whereCondition = "1=1";
        
        $targetTenant = array_key_exists('tenant_id', $filters) ? $filters['tenant_id'] : $this->tenantId;
        if (!empty($targetTenant)) {
            if ($targetTenant === 'global' || $targetTenant === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
                $whereCondition = "(k.tenant_id IS NULL OR k.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
            } else {
                $whereCondition = "k.tenant_id = :filter_tenant_id";
                $params[':filter_tenant_id'] = $targetTenant;
            }
        }

        if (!empty($filters['search'])) {
            $whereCondition .= ($whereCondition === "1=1" ? "" : " AND") . " k.nama_kategori ILIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT k.*, COALESCE(t.nama_sekolah, 'Global / Pusat') as nama_sekolah, COUNT(p.id) as total_pengumuman
                FROM sistem.kategori_pengumuman k 
                LEFT JOIN core.tenants t ON k.tenant_id = t.id 
                LEFT JOIN sistem.pengumuman p ON p.kategori_id = k.id AND (p.tenant_id = k.tenant_id OR (k.tenant_id IS NULL AND p.tenant_id IS NULL))
                WHERE $whereCondition
                GROUP BY k.id, t.nama_sekolah
                ORDER BY k.nama_kategori ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$r) {
            $r['total_pengumuman'] = (int)($r['total_pengumuman'] ?? 0);
        }
        return $rows;
    }

    public function findById(string $id, ?string $tenantId = null): ?array {
        $targetTenant = $tenantId ?? $this->tenantId;
        $sql = "SELECT * FROM sistem.kategori_pengumuman 
                WHERE id = :id::uuid AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $targetTenant);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function create(array $data): string {
        $stmt = $this->db->prepare("
            INSERT INTO sistem.kategori_pengumuman 
            (id, tenant_id, nama_kategori, created_at, updated_at) 
            VALUES 
            (gen_random_uuid(), :tenant_id, :nama_kategori, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'tenant_id' => $data['tenant_id'] ?? $this->tenantId,
            'nama_kategori' => trim($data['nama_kategori'])
        ]);
        return (string)$stmt->fetchColumn();
    }

    public function update(string $id, string $nama_kategori, ?string $tenantId = null): bool {
        $targetTenant = $tenantId ?? $this->tenantId;
        $sql = "UPDATE sistem.kategori_pengumuman 
                SET nama_kategori = :nama_kategori, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id::uuid AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':nama_kategori', trim($nama_kategori));
        $stmt->bindValue(':tenant_id', $targetTenant);
        return $stmt->execute();
    }

    public function delete(string $id, ?string $tenantId = null): bool {
        $targetTenant = $tenantId ?? $this->tenantId;
        // Detach pengumuman associated with this category
        $sqlDetach = "UPDATE sistem.pengumuman 
                      SET kategori_id = NULL 
                      WHERE kategori_id = :id::uuid AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)";
        $stmtDetach = $this->db->prepare($sqlDetach);
        $stmtDetach->bindValue(':id', $id);
        $stmtDetach->bindValue(':tenant_id', $targetTenant);
        $stmtDetach->execute();

        $sql = "DELETE FROM sistem.kategori_pengumuman 
                WHERE id = :id::uuid AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $targetTenant);
        return $stmt->execute();
    }
}
