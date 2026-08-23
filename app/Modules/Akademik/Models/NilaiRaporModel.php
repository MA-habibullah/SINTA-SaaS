<?php

namespace App\Modules\Akademik\Models;

use App\Core\BaseModel;
use PDO;

class NilaiRaporModel extends BaseModel {
    protected static string $table = 'akademik.nilai_sumatif';
    protected static string $schema = 'akademik';

    public static function getNilaiBySiswaSemester(string $tenantId, string $siswaId, string $semester): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT nr.id, nr.tenant_id, nr.siswa_id, nr.rombel_id, nr.mapel_id,
                   mp.nama_mata_pelajaran, nr.nilai_akhir, nr.predikat, nr.catatan
            FROM {$table} nr
            INNER JOIN akademik.mata_pelajaran mp ON nr.mapel_id = mp.id
            WHERE nr.tenant_id = :tenant_id AND nr.siswa_id = :siswa_id AND nr.semester = :semester
            ORDER BY mp.urutan ASC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':siswa_id', $siswaId);
        $stmt->bindValue(':semester', $semester);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
