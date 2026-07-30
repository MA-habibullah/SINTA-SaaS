<?php

namespace App\Modules\Core\Models;

use App\Core\BaseModel;
use PDO;

class TenantModel extends BaseModel {
    protected static string $table = 'tenants';
    protected static string $schema = 'core';

    public static function findById(string $id): ?array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("SELECT id, nama_sekolah, npsn, subdomain, custom_domain, status, is_active FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findBySubdomain(string $subdomain): ?array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("SELECT id, nama_sekolah, npsn, subdomain, custom_domain, status, is_active FROM {$table} WHERE subdomain = :subdomain LIMIT 1");
        $stmt->bindValue(':subdomain', $subdomain);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
