<?php

namespace App\Modules\Absensi\Models;

use App\Core\BaseModel;
use PDO;

class PresensiPtkModel extends BaseModel {
    protected static string $table = 'presensi_ptk_harian';
    protected static string $schema = 'absensi';

    public static function getPresensiByTanggal(string $tenantId, string $tanggal): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT pp.id, pp.tenant_id, pp.ptk_id, ptk.nama_lengkap, ptk.nip,
                   pp.tgl_presensi, pp.status_kehadiran, pp.jam_masuk, pp.jam_pulang, pp.latitude_masuk, pp.longitude_masuk
            FROM {$table} pp
            INNER JOIN kepegawaian.ptk_identitas ptk ON pp.ptk_id = ptk.id
            WHERE pp.tenant_id = :tenant_id AND pp.tgl_presensi = :tgl_presensi
            ORDER BY ptk.nama_lengkap ASC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':tgl_presensi', $tanggal);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
