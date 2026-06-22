<?php

namespace App\Helpers;

use App\Config\Database;
use PDO;

class QueueManager {

    /**
     * Masukkan pekerjaan baru ke antrean
     */
    public static function push(string $jobType, array $payload, ?string $tenantId = null): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO `system_jobs` (tenant_id, job_type, payload, status, created_at, updated_at)
                VALUES (:tenant_id, :job_type, :payload, 'pending', NOW(), NOW())
            ");
            return $stmt->execute([
                'tenant_id' => $tenantId,
                'job_type'  => $jobType,
                'payload'   => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ]);
        } catch (\Throwable $e) {
            error_log("QueueManager::push failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil satu pekerjaan tertua berstatus pending untuk diproses (with row locking)
     */
    public static function pop(): ?array {
        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // Kueri dengan FOR UPDATE untuk mengunci baris agar tidak diambil worker lain secara paralel
            $stmt = $db->prepare("
                SELECT * FROM `system_jobs`
                WHERE `status` = 'pending'
                ORDER BY `id` ASC
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($job) {
                // Tandai pekerjaan sebagai sedang diproses
                $stmtUpdate = $db->prepare("
                    UPDATE `system_jobs` SET
                        `status` = 'processing',
                        `attempts` = `attempts` + 1,
                        `reserved_at` = NOW()
                    WHERE `id` = :id
                ");
                $stmtUpdate->execute(['id' => $job['id']]);
                
                $db->commit();
                
                // Parse payload string menjadi array kembali
                $job['payload'] = json_decode($job['payload'], true) ?? [];
                return $job;
            }

            $db->commit();
            return null;
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("QueueManager::pop failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Tandai pekerjaan selesai sukses
     */
    public static function markCompleted(int $jobId): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE `system_jobs` SET
                    `status` = 'completed',
                    `completed_at` = NOW()
                WHERE `id` = :id
            ");
            $stmt->execute(['id' => $jobId]);
        } catch (\Throwable $e) {
            error_log("QueueManager::markCompleted failed: " . $e->getMessage());
        }
    }

    /**
     * Tandai pekerjaan gagal dengan menyimpan pesan error
     */
    public static function markFailed(int $jobId, string $error): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE `system_jobs` SET
                    `status` = 'failed',
                    `error_message` = :error,
                    `completed_at` = NOW()
                WHERE `id` = :id
            ");
            $stmt->execute([
                'id'    => $jobId,
                'error' => $error
            ]);
        } catch (\Throwable $e) {
            error_log("QueueManager::markFailed failed: " . $e->getMessage());
        }
    }
}
