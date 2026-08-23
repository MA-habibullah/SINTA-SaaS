<?php

namespace App\Modules\Bk\Models;

use App\Core\BaseModel;
use PDO;

class CatatanBkModel extends BaseModel {
    protected static string $table = 'bk.catatan_bk';
    protected static string $schema = 'bk';

    public static function getCatatanBySiswa(string $tenantId, string $siswaId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT cb.id, cb.tenant_id, cb.siswa_id, cb.tanggal, cb.kategori, cb.deskripsi_masalah, cb.tindak_lanjut, cb.status
            FROM {$table} cb
            WHERE cb.tenant_id = :tenant_id AND cb.siswa_id = :siswa_id
            ORDER BY cb.tanggal DESC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':siswa_id', $siswaId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
