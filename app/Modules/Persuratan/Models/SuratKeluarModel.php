<?php

namespace App\Modules\Persuratan\Models;

use App\Core\BaseModel;
use PDO;

class SuratKeluarModel extends BaseModel
{
    protected static string $table = 'persuratan.surat_keluar';
    protected static string $schema = 'persuratan';

    public static function getSuratKeluar(string $tenantId, int $limit = 50, int $offset = 0): array
    {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT sk.id, sk.tenant_id, sk.no_agenda, sk.nomor_surat, sk.tujuan, sk.perihal, sk.tgl_surat, sk.status_surat, sk.qr_token
            FROM {$table} sk
            WHERE (sk.tenant_id = :tenant_id OR sk.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') AND sk.is_active = TRUE
            ORDER BY sk.tgl_surat DESC, sk.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
