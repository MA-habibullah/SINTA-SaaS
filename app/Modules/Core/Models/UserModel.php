<?php

namespace App\Modules\Core\Models;

use App\Core\BaseModel;
use PDO;

class UserModel extends BaseModel {
    protected static string $schema = 'core';
    protected static string $table = 'core.users';

    /**
     * Cari Pengguna berdasarkan Email dan Context Tenant (PostgreSQL Multi-Schema / MySQL Fallback)
     */
    public function findByEmailAndTenant(string $email, ?string $tenantId): ?array {
        try {
            if ($tenantId === null) {
                $sql = "SELECT u.id, u.tenant_id, u.role_id, u.nama_lengkap, u.username, u.email, u.password_hash as password, u.is_active, r.nama_role 
                        FROM core.users u
                        JOIN core.roles r ON u.role_id = r.id
                        WHERE u.email = :email AND (u.tenant_id IS NULL OR u.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['email' => $email]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } else {
                $sql = "SELECT u.id, u.tenant_id, u.role_id, u.nama_lengkap, u.username, u.email, u.password_hash as password, u.is_active, r.nama_role 
                        FROM core.users u
                        JOIN core.roles r ON u.role_id = r.id
                        WHERE u.email = :email AND u.tenant_id = :tenant_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) return $user;

                $sqlSuper = "SELECT u.id, u.tenant_id, u.role_id, u.nama_lengkap, u.username, u.email, u.password_hash as password, u.is_active, r.nama_role 
                             FROM core.users u
                             JOIN core.roles r ON u.role_id = r.id
                             WHERE u.email = :email AND (u.tenant_id IS NULL OR u.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
                $stmtSuper = $this->db->prepare($sqlSuper);
                $stmtSuper->execute(['email' => $email]);
                return $stmtSuper->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } catch (\Throwable $e) {
            if ($tenantId === null) {
            $sql = "SELECT u.*, r.nama_role FROM core.users u JOIN core.roles r ON u.role_id = r.id WHERE u.email = :email AND (u.tenant_id IS NULL OR u.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['email' => $email]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } else {
            $sql = "SELECT u.*, r.nama_role FROM core.users u JOIN core.roles r ON u.role_id = r.id WHERE u.email = :email AND u.tenant_id = :tenant_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) return $user;

            $sqlSuper = "SELECT u.*, r.nama_role FROM core.users u JOIN core.roles r ON u.role_id = r.id WHERE u.email = :email AND (u.tenant_id IS NULL OR u.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')";
                $stmtSuper = $this->db->prepare($sqlSuper);
                $stmtSuper->execute(['email' => $email]);
                return $stmtSuper->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }
    }
}
