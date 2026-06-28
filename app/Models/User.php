<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model {
    
    /**
     * Find user by email and tenant subdomain context
     * For security, we also join with roles to fetch their role name.
     */
    public function findByEmailAndTenant(string $email, ?string $tenantId): ?array {
        // Jika tenantId null, berarti user mencoba login sebagai Super Admin platform
        if ($tenantId === null) {
            $sql = "SELECT u.*, r.nama_role 
                    FROM users u
                    JOIN roles r ON u.role_id = r.id
                    WHERE u.email = :email AND u.tenant_id IS NULL AND u.deleted_at IS NULL";
            $params = ['email' => $email];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() ?: null;
        } else {
            // 1. Coba cari user di sekolah spesifik
            $sql = "SELECT u.*, r.nama_role 
                    FROM users u
                    JOIN roles r ON u.role_id = r.id
                    WHERE u.email = :email AND u.tenant_id = :tenant_id AND u.deleted_at IS NULL";
            $params = [
                'email' => $email,
                'tenant_id' => $tenantId
            ];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $user = $stmt->fetch();

            if ($user) {
                return $user;
            }

            // 2. Jika tidak ditemukan di sekolah tersebut, cari apakah dia Super Admin (Bisa login dari mana saja)
            $sqlSuper = "SELECT u.*, r.nama_role 
                         FROM users u
                         JOIN roles r ON u.role_id = r.id
                         WHERE u.email = :email AND u.tenant_id IS NULL AND u.deleted_at IS NULL";
            $stmtSuper = $this->db->prepare($sqlSuper);
            $stmtSuper->execute(['email' => $email]);
            return $stmtSuper->fetch() ?: null;
        }
    }

    /**
     * Create a new user with secure password hashing (Secure by Design)
     */
    public function create(array $data): string {
        $id = $this->generateUuidV4();
        
        // Secure by Design: Hash password menggunakan Argon2id (rekomendasi PHP 8.x) atau bcrypt
        // Parameter PASSWORD_DEFAULT akan menggunakan bcrypt atau argon2id tergantung versi PHP & konfigurasi
        $hashedPassword = password_hash($data['password'], PASSWORD_ARGON2ID);

        $sql = "INSERT INTO users (id, tenant_id, role_id, nama_lengkap, email, password, status) 
                VALUES (:id, :tenant_id, :role_id, :nama_lengkap, :email, :password, :status)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $data['tenant_id'] ?? null, // Nullable untuk super admin
            'role_id' => $data['role_id'],
            'nama_lengkap' => $data['nama_lengkap'],
            'email' => strtolower(trim($data['email'])),
            'password' => $hashedPassword,
            'status' => $data['status'] ?? 'active'
        ]);

        return $id;
    }

    /**
     * Generate standard UUID v4
     */
    private function generateUuidV4(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
