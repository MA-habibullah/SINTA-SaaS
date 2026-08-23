<?php

namespace App\Modules\Tracer\Models;

use App\Core\BaseModel;
use PDO;

class TracerStudyModel extends BaseModel {
    protected static string $table = 'tracer.tracer_study_alumni';
    protected static string $schema = 'tracer';

    public static function getTracerBySiswa(string $tenantId, string $siswaId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT ts.id, ts.tenant_id, ts.siswa_id, ts.status_alumni, ts.nama_instansi_kampus, ts.jabatan_prodi, ts.tahun_mulai
            FROM {$table} ts
            WHERE ts.tenant_id = :tenant_id AND ts.siswa_id = :siswa_id
            ORDER BY ts.tahun_mulai DESC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':siswa_id', $siswaId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
