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
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $resolvedTenantId = $tenantId ?: ($_SESSION['tenant_id'] ?? $_SESSION['user']['tenant_id'] ?? null);
            if ($resolvedTenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' || $resolvedTenantId === 'global') {
                $resolvedTenantId = null;
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO sistem.queue_jobs (id, tenant_id, job_type, payload, status, created_at, available_at)
                VALUES (gen_random_uuid(), :tenant_id, :job_type, :payload, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            return $stmt->execute([
                'tenant_id' => $resolvedTenantId,
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
    public static function pop(?string $tenantId = null): ?array {
        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $sql = "SELECT * FROM sistem.queue_jobs WHERE status = 'pending'";
            $params = [];
            if ($tenantId) {
                $sql .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
                $params['tenant_id'] = $tenantId;
            }
            $sql .= " ORDER BY created_at ASC LIMIT 1 FOR UPDATE";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($job) {
                // Isolasi Tenant: Update status pekerjaan dengan filter tenant_id yang ketat
                if (!empty($job['tenant_id'])) {
                    $stmtUpdate = $db->prepare("
                        UPDATE sistem.queue_jobs 
                        SET status = 'processing', attempts = attempts + 1 
                        WHERE id = :id AND tenant_id = :tenant_id
                    ");
                    $stmtUpdate->execute([
                        'id' => $job['id'],
                        'tenant_id' => $job['tenant_id']
                    ]);
                } else {
                    // Global system background job (Platform Scope)
                    $stmtUpdate = $db->prepare("
                        UPDATE sistem.queue_jobs 
                        SET status = 'processing', attempts = attempts + 1 
                        WHERE id = :id AND tenant_id IS NULL
                    ");
                    $stmtUpdate->execute(['id' => $job['id']]);
                }
                
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
    public static function markCompleted(string $jobId, ?string $tenantId = null): void {
        try {
            $db = Database::getConnection();
            if (!empty($tenantId)) {
                $stmt = $db->prepare("
                    UPDATE sistem.queue_jobs 
                    SET status = 'completed' 
                    WHERE id = :id AND (tenant_id = :tenant_id OR tenant_id IS NULL)
                ");
                $stmt->execute(['id' => $jobId, 'tenant_id' => $tenantId]);
            } else {
                $stmt = $db->prepare("
                    UPDATE sistem.queue_jobs 
                    SET status = 'completed' 
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $jobId]);
            }
        } catch (\Throwable $e) {
            error_log("QueueManager::markCompleted failed: " . $e->getMessage());
        }
    }

    /**
     * Tandai pekerjaan gagal dengan menyimpan pesan error
     */
    public static function markFailed(string $jobId, string $error, ?string $tenantId = null): void {
        try {
            $db = Database::getConnection();
            if (!empty($tenantId)) {
                $stmt = $db->prepare("
                    UPDATE sistem.queue_jobs 
                    SET status = 'failed', error_message = :error 
                    WHERE id = :id AND (tenant_id = :tenant_id OR tenant_id IS NULL)
                ");
                $stmt->execute(['id' => $jobId, 'error' => $error, 'tenant_id' => $tenantId]);
            } else {
                $stmt = $db->prepare("
                    UPDATE sistem.queue_jobs 
                    SET status = 'failed', error_message = :error 
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $jobId, 'error' => $error]);
            }
        } catch (\Throwable $e) {
            error_log("QueueManager::markFailed failed: " . $e->getMessage());
        }
    }
}
