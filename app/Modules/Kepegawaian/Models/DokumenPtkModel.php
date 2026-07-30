<?php

namespace App\Modules\Kepegawaian\Models;

use App\Core\BaseModel;
use PDO;

class DokumenPtkModel extends BaseModel {
    protected static string $table = 'dokumen_ptk';
    protected static string $schema = 'kepegawaian';

    public static function getDokumenByPtk(string $tenantId, string $ptkId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT id, tenant_id, ptk_id, jenis_dokumen, nama_dokumen, file_path, file_size
            FROM {$table}
            WHERE tenant_id = :tenant_id AND ptk_id = :ptk_id
            ORDER BY jenis_dokumen ASC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':ptk_id', $ptkId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
