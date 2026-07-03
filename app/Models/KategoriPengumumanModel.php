<?php

namespace App\Models;

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
        $sql = "SELECT k.*, t.nama_sekolah 
                FROM kategori_pengumuman k 
                LEFT JOIN tenants t ON k.tenant_id = t.id 
                WHERE ";
                
        $params = [];
        if ($this->tenantId === null) {
            $sql .= "1=1 ";
        } else {
            $sql .= "(k.tenant_id = :scoped_tenant_id OR k.tenant_id IS NULL) ";
            $params['scoped_tenant_id'] = $this->tenantId;
        }

        if (!empty($filters['tenant_id'])) {
            if ($filters['tenant_id'] === 'global') {
                $sql .= " AND k.tenant_id IS NULL ";
            } else {
                $sql .= " AND k.tenant_id = :filter_tenant_id ";
                $params['filter_tenant_id'] = $filters['tenant_id'];
            }
        }

        $sql .= " ORDER BY k.nama_kategori ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM kategori_pengumuman WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare("INSERT INTO kategori_pengumuman (id, tenant_id, nama_kategori) VALUES (UUID(), :tenant_id, :nama_kategori)");
        return $stmt->execute([
            'tenant_id' => $data['tenant_id'] ?? $this->tenantId,
            'nama_kategori' => $data['nama_kategori']
        ]);
    }

    public function update(string $id, string $nama_kategori): bool {
        $stmt = $this->db->prepare("UPDATE kategori_pengumuman SET nama_kategori = :nama_kategori WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'nama_kategori' => $nama_kategori
        ]);
    }

    public function delete(string $id): bool {
        $stmt = $this->db->prepare("DELETE FROM kategori_pengumuman WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
