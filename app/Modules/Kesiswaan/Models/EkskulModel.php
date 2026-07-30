<?php

namespace App\Modules\Kesiswaan\Models;

use App\Core\BaseModel;
use PDO;

class EkskulModel extends BaseModel {
    protected static string $table = 'ekstrakurikuler';
    protected static string $schema = 'kesiswaan';

    public static function getActiveEkskul(string $tenantId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT e.id, e.tenant_id, e.nama_ekskul, e.kategori_ekskul, e.is_active
            FROM {$table} e
            WHERE e.tenant_id = :tenant_id AND e.is_active = true
            ORDER BY e.nama_ekskul ASC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
