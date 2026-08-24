<?php

namespace App\Helpers;

use App\Config\Database;
use PDO;

class SessionTracker {
    /**
     * Catat atau perbarui sesi aktif pengguna di sistem.active_sessions
     * 
     * @return void
     */
    public static function track(): void {
        if (session_status() === PHP_SESSION_NONE) {
            \App\Core\SessionManager::start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $tenantId = $_SESSION['tenant_id'] ?? null;

        if (!$userId) {
            return;
        }

        // --- LAZY CLEANUP TRIGGER (5% probability) ---
        if (rand(1, 100) <= 5) {
            \App\Core\SessionManager::cleanupStaleSessions();
        }

        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }
        $ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

        try {
            $db = Database::getConnection();

            // Cek apakah sesi aktif pengguna ini sudah ada di sistem.active_sessions
            $sqlCheck = "SELECT id FROM sistem.active_sessions WHERE user_id = :user_id";
            $paramsCheck = ['user_id' => $userId];
            if ($tenantId) {
                $sqlCheck .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
                $paramsCheck['tenant_id'] = $tenantId;
            }
            $sqlCheck .= " LIMIT 1";
            $checkStmt = $db->prepare($sqlCheck);
            $checkStmt->execute($paramsCheck);
            $existingId = $checkStmt->fetchColumn();

            if ($existingId) {
                // Update sesi yang ada
                $sqlUpdate = "
                    UPDATE sistem.active_sessions SET
                        ip_address = :ip_address,
                        user_agent = :user_agent,
                        last_activity = CURRENT_TIMESTAMP
                    WHERE id = :id
                ";
                $paramsUpdate = [
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'id'         => $existingId
                ];
                if ($tenantId) {
                    $sqlUpdate = "
                        UPDATE sistem.active_sessions SET
                            ip_address = :ip_address,
                            user_agent = :user_agent,
                            last_activity = CURRENT_TIMESTAMP
                        WHERE id = :id AND (tenant_id = :tenant_id OR tenant_id IS NULL)
                    ";
                    $paramsUpdate['tenant_id'] = $tenantId;
                }
                $updateStmt = $db->prepare($sqlUpdate);
                $updateStmt->execute($paramsUpdate);
            } else {
                // Insert sesi baru
                $insertStmt = $db->prepare("
                    INSERT INTO sistem.active_sessions (
                        id, user_id, tenant_id, ip_address, user_agent, tanggal_login, last_activity
                    ) VALUES (
                        gen_random_uuid()::text, :user_id, :tenant_id, :ip_address, :user_agent, CURRENT_DATE, CURRENT_TIMESTAMP
                    )
                ");
                $insertStmt->execute([
                    'user_id'    => $userId,
                    'tenant_id'  => $tenantId,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent
                ]);
            }
        } catch (\Throwable $e) {
            error_log("SessionTracker error: " . $e->getMessage());
        }
    }
}
