<?php

namespace App\Modules\Kepegawaian\Models;

use App\Core\BaseModel;
use PDO;

class PtkIdentitasModel extends BaseModel {
    protected static string $table = 'kepegawaian.ptk_identitas';
    protected static string $schema = 'kepegawaian';

    public static function findById(string $tenantId, string $ptkId): ?array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT id, tenant_id, nik, nip, nuptk, nama_lengkap, jenis_kelamin, status_kepegawaian, is_active
            FROM {$table}
            WHERE tenant_id = :tenant_id AND id = :id
            LIMIT 1
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':id', $ptkId);
        $stmt->execute();

        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function getActivePtk(string $tenantId, int $limit = 50, int $offset = 0): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT id, tenant_id, nip, nuptk, nama_lengkap, jenis_kelamin, status_kepegawaian
            FROM {$table}
            WHERE tenant_id = :tenant_id AND is_active = true
            ORDER BY nama_lengkap ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
