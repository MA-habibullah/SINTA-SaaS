<?php

namespace App\Helpers;

use App\Config\Database;
use PDO;

class SessionTracker {
    /**
     * Catat atau perbarui sesi aktif pengguna untuk analitik harian
     * 
     * @return void
     */
    public static function track(): void {
        // Mulai session jika belum berjalan
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Hanya lacak jika pengguna telah login
        if (!$userId) {
            return;
        }

        // Ambil IP Address (antisipasi proxy header spoofing)
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }
        $ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : '127.0.0.1';

        // Ambil User Agent dan potong jika melebihi 255 karakter
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
        
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        try {
            $db = Database::getConnection();

            // Lakukan Upsert Harian menggunakan MySQL ON DUPLICATE KEY UPDATE
            // (Lebih hemat 1 roundtrip kueri daripada melakukan cek SELECT lalu INSERT/UPDATE terpisah)
            $stmt = $db->prepare("
                INSERT INTO `active_sessions` (
                    `id`, `user_id`, `tenant_id`, `ip_address`, `user_agent`, `tanggal_login`, `last_activity`
                ) VALUES (
                    UUID(), :user_id, :tenant_id, :ip_address, :user_agent, :tanggal_login, :last_activity
                )
                ON DUPLICATE KEY UPDATE 
                    `ip_address` = VALUES(`ip_address`),
                    `user_agent` = VALUES(`user_agent`),
                    `last_activity` = VALUES(`last_activity`)
            ");

            $stmt->execute([
                'user_id'       => $userId,
                'tenant_id'     => $tenantId,
                'ip_address'    => $ipAddress,
                'user_agent'    => $userAgent,
                'tanggal_login' => $today,
                'last_activity' => $now
            ]);
        } catch (\Throwable $e) {
            // Cybersecurity best practice: kegagalan tracking log tidak boleh
            // menghentikan/memblokir proses fungsional aplikasi utama pengguna.
            error_log("SessionTracker error: " . $e->getMessage());
        }
    }
}
