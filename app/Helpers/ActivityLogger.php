<?php

namespace App\Helpers;

use App\Config\Database;
use PDO;

class ActivityLogger {
    /**
     * Catat log audit trail aktivitas pengguna
     * 
     * @param string $action Jenis aksi (INSERT, UPDATE, DELETE)
     * @param string $tableName Nama tabel yang dimanipulasi
     * @param string $recordId Primary key ID dari baris data
     * @param array|null $oldData State data sebelum perubahan (untuk UPDATE/DELETE)
     * @param array|null $newData State data sesudah perubahan (untuk INSERT/UPDATE)
     * @return bool
     */
    public static function record(string $action, string $tableName, string $recordId, ?array $oldData = null, ?array $newData = null): bool {
        // Mulai session jika belum dimulai (untuk CLI migrations/seeder aman)
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Ambil data aktor dari session
        $userId   = $_SESSION['user_id'] ?? null;
        $userRole = $_SESSION['role_name'] ?? 'system';
        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Ambil IP Address (mengantisipasi spoofing via proxy headers)
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }
        $ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : '127.0.0.1';

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO `activity_logs` (
                    `id`, `tenant_id`, `user_id`, `user_role`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`
                ) VALUES (
                    UUID(), :tenant_id, :user_id, :user_role, :action, :table_name, :record_id, :old_data, :new_data, :ip_address
                )
            ");

            return $stmt->execute([
                'tenant_id'  => $tenantId,
                'user_id'    => $userId,
                'user_role'  => $userRole,
                'action'     => strtoupper($action),
                'table_name' => $tableName,
                'record_id'  => $recordId,
                'old_data'   => $oldData !== null ? json_encode($oldData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'new_data'   => $newData !== null ? json_encode($newData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $ipAddress
            ]);
        } catch (\Throwable $e) {
            // Cybersecurity fail-safe: log audit error tidak boleh memblokir alur kerja aplikasi utama
            error_log("Failed to write Activity Log: " . $e->getMessage());
            return false;
        }
    }
}
