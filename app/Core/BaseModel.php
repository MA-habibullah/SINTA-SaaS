<?php

namespace App\Core;

use App\Config\Database;
use PDO;

abstract class BaseModel {
    protected static string $table = '';
    protected static string $schema = 'public';

    public static function getTableName(): string {
        if (!empty(static::$schema) && static::$schema !== 'public') {
            return static::$schema . '.' . static::$table;
        }
        return static::$table;
    }

    public static function getPdo(): PDO {
        return Database::getConnection();
    }

    /**
     * Helper untuk query terisolasi tenant_id (Multi-Tenant Safety)
     */
    public static function fetchByTenant(string $tenantId, array $columns = ['*'], int $limit = 50, int $offset = 0): array {
        $table = static::getTableName();
        $cols = implode(', ', $columns);
        
        $sql = "SELECT {$cols} FROM {$table} WHERE tenant_id = :tenant_id LIMIT :limit OFFSET :offset";
        $stmt = self::getPdo()->prepare($sql);
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
