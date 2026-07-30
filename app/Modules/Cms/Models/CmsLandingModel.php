<?php

namespace App\Modules\Cms\Models;

use App\Core\BaseModel;
use PDO;

class CmsLandingModel extends BaseModel {
    protected static string $table = 'cms_pages';
    protected static string $schema = 'cms';

    public static function getSections(string $tenantId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT cs.id, cs.tenant_id, cs.section_key, cs.judul, cs.subjudul, cs.konten, cs.urutan, cs.is_active
            FROM {$table} cs
            WHERE cs.tenant_id = :tenant_id AND cs.is_active = true
            ORDER BY cs.urutan ASC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
