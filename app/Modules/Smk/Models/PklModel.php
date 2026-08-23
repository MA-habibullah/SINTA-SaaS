<?php

namespace App\Modules\Smk\Models;

use App\Core\BaseModel;
use PDO;

class PklModel extends BaseModel {
    protected static string $table = 'smk.pkl_penempatan';
    protected static string $schema = 'smk';

    public static function getPenempatanPkl(string $tenantId, string $siswaId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT p.id, p.tenant_id, p.siswa_id, p.nama_dudi, p.alamat_dudi, p.tgl_mulai, p.tgl_selesai, p.status
            FROM {$table} p
            WHERE p.tenant_id = :tenant_id AND p.siswa_id = :siswa_id
            ORDER BY p.tgl_mulai DESC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':siswa_id', $siswaId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
