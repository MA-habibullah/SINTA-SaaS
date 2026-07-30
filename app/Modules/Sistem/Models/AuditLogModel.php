<?php

namespace App\Modules\Sistem\Models;

use App\Core\BaseModel;
use PDO;

class AuditLogModel extends BaseModel {
    protected static string $table = 'activity_logs';
    protected static string $schema = 'sistem';

    public static function getLogs(string $tenantId, int $limit = 50, int $offset = 0): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT al.id, al.tenant_id, al.user_id, al.entity_type, al.entity_id, al.action, al.ip_address, al.created_at
            FROM {$table} al
            WHERE al.tenant_id = :tenant_id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
