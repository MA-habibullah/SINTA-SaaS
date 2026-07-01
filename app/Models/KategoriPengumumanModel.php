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

    public function getAll(): array {
        $sql = "SELECT * FROM kategori_pengumuman WHERE ";
        if ($this->tenantId === null) {
            $sql .= "tenant_id IS NULL OR tenant_id != ''"; // Super admin sees all or global? Actually super admin sees global ones. Let's just say tenant_id IS NULL for global, or matches tenant.
            // If super admin, maybe return all. Let's return all.
            $sql = "SELECT * FROM kategori_pengumuman ORDER BY nama_kategori ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            $sql .= "(tenant_id = :tenant_id OR tenant_id IS NULL) ORDER BY nama_kategori ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tenant_id' => $this->tenantId]);
        }
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
