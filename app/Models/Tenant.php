<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Tenant extends Model {

    /**
     * Get all active (non-soft-deleted) tenants
     */
    public function findAll(): array {
        $sql = "SELECT * FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a tenant by ID
     */
    public function findById(string $id): ?array {
        $sql = "SELECT * FROM tenants WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Create a new tenant with UUID v4
     */
    public function create(array $data): string {
        $id = $this->generateUuidV4();

        $sql = "INSERT INTO tenants (
                    id, nama_sekolah, npsn, subdomain, domain, 
                    status, paket_aktif, status_sinkronisasi, 
                    storage_limit_mb, max_siswa_limit, max_staff_limit, enable_bk, enable_tracer,
                    created_at, updated_at
                ) VALUES (
                    :id, :nama_sekolah, :npsn, :subdomain, :domain, 
                    :status, :paket_aktif, :status_sinkronisasi, 
                    :storage_limit_mb, :max_siswa_limit, :max_staff_limit, :enable_bk, :enable_tracer,
                    NOW(), NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'nama_sekolah' => $data['nama_sekolah'],
            'npsn' => $data['npsn'],
            'subdomain' => $data['subdomain'],
            'domain' => !empty($data['domain']) ? $data['domain'] : null,
            'status' => $data['status'] ?? 'active',
            'paket_aktif' => $data['paket_aktif'] ?? 'Premium SaaS',
            'status_sinkronisasi' => $data['status_sinkronisasi'] ?? 'Tersinkronisasi',
            'storage_limit_mb' => (int)($data['storage_limit_mb'] ?? 100),
            'max_siswa_limit' => (int)($data['max_siswa_limit'] ?? 500),
            'max_staff_limit' => (int)($data['max_staff_limit'] ?? 50),
            'enable_bk' => (int)($data['enable_bk'] ?? 1),
            'enable_tracer' => (int)($data['enable_tracer'] ?? 1)
        ]);

        return $id;
    }

    /**
     * Update an existing tenant
     */
    public function updateTenant(string $id, array $data): bool {
        $sql = "UPDATE tenants SET 
                    nama_sekolah = :nama_sekolah, 
                    npsn = :npsn, 
                    subdomain = :subdomain, 
                    domain = :domain, 
                    status = :status, 
                    paket_aktif = :paket_aktif, 
                    status_sinkronisasi = :status_sinkronisasi,
                    storage_limit_mb = :storage_limit_mb,
                    max_siswa_limit = :max_siswa_limit,
                    max_staff_limit = :max_staff_limit,
                    enable_bk = :enable_bk,
                    enable_tracer = :enable_tracer,
                    updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nama_sekolah' => $data['nama_sekolah'],
            'npsn' => $data['npsn'],
            'subdomain' => $data['subdomain'],
            'domain' => !empty($data['domain']) ? $data['domain'] : null,
            'status' => $data['status'],
            'paket_aktif' => $data['paket_aktif'],
            'status_sinkronisasi' => $data['status_sinkronisasi'],
            'storage_limit_mb' => (int)$data['storage_limit_mb'],
            'max_siswa_limit' => (int)$data['max_siswa_limit'],
            'max_staff_limit' => (int)$data['max_staff_limit'],
            'enable_bk' => (int)$data['enable_bk'],
            'enable_tracer' => (int)$data['enable_tracer'],
            'id' => $id
        ]);
    }

    /**
     * Soft delete a tenant (set deleted_at)
     */
    public function deleteTenant(string $id): bool {
        $sql = "UPDATE tenants SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Check if NPSN is unique
     */
    public function isNpsnUnique(string $npsn, ?string $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM tenants WHERE npsn = :npsn AND deleted_at IS NULL";
        $params = ['npsn' => $npsn];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Check if subdomain is unique
     */
    public function isSubdomainUnique(string $subdomain, ?string $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM tenants WHERE subdomain = :subdomain AND deleted_at IS NULL";
        $params = ['subdomain' => $subdomain];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Check if domain is unique
     */
    public function isDomainUnique(string $domain, ?string $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM tenants WHERE domain = :domain AND deleted_at IS NULL";
        $params = ['domain' => $domain];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Generate standard UUID v4
     */
    private function generateUuidV4(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
