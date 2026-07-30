<?php

namespace App\Modules\Persuratan\Models;

use App\Core\BaseModel;
use PDO;

class SuratMasukModel extends BaseModel {
    protected static string $table = 'surat_masuk';
    protected static string $schema = 'persuratan';

    public static function getSuratMasuk(string $tenantId, int $limit = 50, int $offset = 0): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT sm.id, sm.tenant_id, sm.no_agenda, sm.no_surat, sm.pengirim, sm.perihal, sm.tgl_surat, sm.tgl_terima, sm.status_disposisi
            FROM {$table} sm
            WHERE sm.tenant_id = :tenant_id
            ORDER BY sm.tgl_terima DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
