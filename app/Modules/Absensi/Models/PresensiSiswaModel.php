<?php

namespace App\Modules\Absensi\Models;

use App\Core\BaseModel;
use PDO;

class PresensiSiswaModel extends BaseModel {
    protected static string $table = 'presensi_siswa_harian';
    protected static string $schema = 'absensi';

    public static function getPresensiByTanggal(string $tenantId, string $tanggal): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT ps.id, ps.tenant_id, ps.siswa_id, s.nama_lengkap, s.nis,
                   ps.tgl_presensi, ps.status_kehadiran, ps.jam_masuk, ps.jam_pulang, ps.metode_presensi
            FROM {$table} ps
            INNER JOIN siswa.siswa s ON ps.siswa_id = s.id
            WHERE ps.tenant_id = :tenant_id AND ps.tgl_presensi = :tgl_presensi
            ORDER BY s.nama_lengkap ASC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':tgl_presensi', $tanggal);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
