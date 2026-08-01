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
                INSERT INTO sistem.queue_jobs (id, tenant_id, job_type, payload, status, created_at, available_at)
                VALUES (gen_random_uuid(), :tenant_id, :job_type, :payload, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            return $stmt->execute([
                'tenant_id' => $tenantId ?: null,
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

            $stmt = $db->prepare("
                SELECT * FROM sistem.queue_jobs
                WHERE status = 'pending'
                ORDER BY created_at ASC
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($job) {
                $stmtUpdate = $db->prepare("
                    UPDATE sistem.queue_jobs SET
                        status = 'processing',
                        attempts = attempts + 1
                    WHERE id = :id
                ");
                $stmtUpdate->execute(['id' => $job['id']]);
                
                $db->commit();
                
                if (is_string($job['payload'])) {
                    $job['payload'] = json_decode($job['payload'], true) ?? [];
                }
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
    public static function markCompleted(string $jobId): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE sistem.queue_jobs SET
                    status = 'completed'
                WHERE id = :id
            ");
            $stmt->execute(['id' => $jobId]);
        } catch (\Throwable $e) {
            error_log("QueueManager::markCompleted failed: " . $e->getMessage());
        }
    }

    /**
     * Tandai pekerjaan gagal dengan menyimpan pesan error
     */
    public static function markFailed(string $jobId, string $error): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE sistem.queue_jobs SET
                    status = 'failed',
                    error_message = :error
                WHERE id = :id
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
