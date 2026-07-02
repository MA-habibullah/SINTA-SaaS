<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class KategoriAgendaModel {
    private ?string $tenantId;

    public function __construct(?string $tenantId) {
        $this->tenantId = $tenantId;
    }

    public function getAll(): array {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(k.tenant_id = :tenant_id OR k.tenant_id IS NULL)";
        $stmt = $db->prepare("SELECT k.*, t.nama_sekolah FROM kategori_agenda k LEFT JOIN tenants t ON k.tenant_id = t.id WHERE $whereTenant ORDER BY k.nama_kategori ASC");
        
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool {
        $db = Database::getConnection();
        
        // Generate UUID v4
        $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $stmt = $db->prepare("INSERT INTO kategori_agenda (id, tenant_id, nama_kategori, kode_warna, is_active, created_at) VALUES (:id, :tenant_id, :nama_kategori, :kode_warna, 1, NOW())");
        $stmt->bindValue(':id', $id);
        
        $tenant = array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $this->tenantId;
        $stmt->bindValue(':tenant_id', $tenant);
        
        $stmt->bindValue(':nama_kategori', $data['nama_kategori']);
        $stmt->bindValue(':kode_warna', $data['kode_warna'] ?? '#0b5ed7');
        return $stmt->execute();
    }

    public function update(string $id, array $data): bool {
        $db = Database::getConnection();
        // Hanya bisa update kategori miliknya (atau bisa semua jika tenantId null)
        $whereTenant = ($this->tenantId === null) ? "id = :id" : "id = :id AND tenant_id = :tenant_id";
        
        if (array_key_exists('tenant_id', $data)) {
            $stmt = $db->prepare("UPDATE kategori_agenda SET nama_kategori = :nama_kategori, kode_warna = :kode_warna, tenant_id = :new_tenant_id, updated_at = NOW() WHERE $whereTenant");
            $stmt->bindValue(':new_tenant_id', $data['tenant_id']);
        } else {
            $stmt = $db->prepare("UPDATE kategori_agenda SET nama_kategori = :nama_kategori, kode_warna = :kode_warna, updated_at = NOW() WHERE $whereTenant");
        }
        
        $stmt->bindValue(':id', $id);
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        $stmt->bindValue(':nama_kategori', $data['nama_kategori']);
        $stmt->bindValue(':kode_warna', $data['kode_warna']);
        return $stmt->execute();
    }

    public function delete(string $id): bool {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "id = :id" : "id = :id AND tenant_id = :tenant_id";
        $stmt = $db->prepare("DELETE FROM kategori_agenda WHERE $whereTenant");
        $stmt->bindValue(':id', $id);
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        return $stmt->execute();
    }
}
