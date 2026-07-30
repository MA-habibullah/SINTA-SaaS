<?php

namespace App\Modules\Keuangan\Models;

use App\Core\BaseModel;
use PDO;

class TagihanSiswaModel extends BaseModel {
    protected static string $table = 'transaksi_spp_tagihan';
    protected static string $schema = 'keuangan';

    public static function getTagihanUnpaid(string $tenantId, string $siswaId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT t.id, t.tenant_id, t.siswa_id, t.bulan_tagihan, t.tahun_tagihan, t.total_tagihan, t.status_tagihan
            FROM {$table} t
            WHERE t.tenant_id = :tenant_id AND t.siswa_id = :siswa_id AND t.status_tagihan != 'lunas'
            ORDER BY t.tahun_tagihan DESC, t.bulan_tagihan DESC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':siswa_id', $siswaId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
