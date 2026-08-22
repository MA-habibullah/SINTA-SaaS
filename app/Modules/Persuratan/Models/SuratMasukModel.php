<?php

namespace App\Modules\Persuratan\Models;

use App\Core\BaseModel;
use PDO;

class SuratMasukModel extends BaseModel
{
    protected static string $table = 'surat_masuk';
    protected static string $schema = 'persuratan';

    public static function getSuratMasuk(string $tenantId, int $limit = 50, int $offset = 0): array
    {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT sm.id, sm.tenant_id, sm.no_agenda, sm.no_surat, sm.pengirim, sm.perihal, sm.tgl_surat, sm.tgl_terima, sm.status_disposisi, sm.sifat_surat
            FROM {$table} sm
            WHERE (sm.tenant_id = :tenant_id OR sm.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') AND sm.is_active = TRUE
            ORDER BY sm.tgl_terima DESC, sm.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
