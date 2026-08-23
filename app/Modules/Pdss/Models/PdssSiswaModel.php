<?php

namespace App\Modules\Pdss\Models;

use App\Core\BaseModel;
use PDO;

class PdssSiswaModel extends BaseModel {
    protected static string $table = 'pdss.pdss_manual_eligible';
    protected static string $schema = 'pdss';

    public static function getEligibleSiswa(string $tenantId, string $tahunAjaranId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT ps.id, ps.tenant_id, ps.siswa_id, s.nama_lengkap, s.nisn, ps.peringkat_pararel, ps.rata_rata_nilai, ps.status_eligible
            FROM {$table} ps
            INNER JOIN siswa.siswa s ON ps.siswa_id = s.id
            WHERE ps.tenant_id = :tenant_id AND ps.tahun_ajaran_id = :tahun_ajaran_id
            ORDER BY ps.peringkat_pararel ASC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':tahun_ajaran_id', $tahunAjaranId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
