<?php

namespace App\Modules\Akademik\Models;

use App\Core\BaseModel;
use PDO;

class RombelModel extends BaseModel {
    protected static string $table = 'rombel';
    protected static string $schema = 'akademik';

    public static function findById(string $tenantId, string $rombelId): ?array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT id, tenant_id, tahun_ajaran_id, ptk_id, nama_rombel, tingkat, is_active
            FROM {$table}
            WHERE tenant_id = :tenant_id AND id = :id
            LIMIT 1
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':id', $rombelId);
        $stmt->execute();

        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function getActiveRombel(string $tenantId, string $tahunAjaranId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT r.id, r.tenant_id, r.nama_rombel, r.tingkat, ptk.nama_lengkap AS wali_kelas
            FROM {$table} r
            LEFT JOIN kepegawaian.ptk_identitas ptk ON r.ptk_id = ptk.id
            WHERE r.tenant_id = :tenant_id AND r.tahun_ajaran_id = :tahun_ajaran_id AND r.is_active = true
            ORDER BY r.tingkat ASC, r.nama_rombel ASC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':tahun_ajaran_id', $tahunAjaranId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
