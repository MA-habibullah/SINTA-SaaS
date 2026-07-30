<?php

namespace App\Modules\Kesiswaan\Models;

use App\Core\BaseModel;
use PDO;

class PrestasiSiswaModel extends BaseModel {
    protected static string $table = 'prestasi_siswa';
    protected static string $schema = 'kesiswaan';

    public static function getPrestasiBySiswa(string $tenantId, string $siswaId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT p.id, p.tenant_id, p.siswa_id, p.nama_prestasi, p.tingkat, p.peringkat, p.tanggal_prestasi
            FROM {$table} p
            WHERE p.tenant_id = :tenant_id AND p.siswa_id = :siswa_id
            ORDER BY p.tanggal_prestasi DESC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':siswa_id', $siswaId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
