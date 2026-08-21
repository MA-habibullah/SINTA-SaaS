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
        
        if ($this->tenantId !== null) {
            $whereCondition = "(k.tenant_id = :scoped_tenant_id OR k.tenant_id IS NULL)";
            $params[':scoped_tenant_id'] = $this->tenantId;
        }

        if (!empty($filters['tenant_id'])) {
            if ($filters['tenant_id'] === 'global') {
                $whereCondition .= " AND k.tenant_id IS NULL";
            } else {
                $whereCondition .= " AND k.tenant_id = :filter_tenant_id";
                $params[':filter_tenant_id'] = $filters['tenant_id'];
            }
        }

        if (!empty($filters['search'])) {
            $whereCondition .= " AND k.nama_kategori ILIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT k.*, t.nama_sekolah, COUNT(p.id) as total_pengumuman
                FROM sistem.kategori_pengumuman k 
                LEFT JOIN core.tenants t ON k.tenant_id = t.id 
                LEFT JOIN sistem.pengumuman p ON p.kategori_id = k.id
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

    public function findById(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM sistem.kategori_pengumuman WHERE id = :id");
        $stmt->execute(['id' => $id]);
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

    public function update(string $id, string $nama_kategori): bool {
        $stmt = $this->db->prepare("
            UPDATE sistem.kategori_pengumuman 
            SET nama_kategori = :nama_kategori, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'nama_kategori' => trim($nama_kategori)
        ]);
    }

    public function delete(string $id): bool {
        // Detach pengumuman associated with this category
        $stmtDetach = $this->db->prepare("UPDATE sistem.pengumuman SET kategori_id = NULL WHERE kategori_id = :id");
        $stmtDetach->execute(['id' => $id]);

        $stmt = $this->db->prepare("DELETE FROM sistem.kategori_pengumuman WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
