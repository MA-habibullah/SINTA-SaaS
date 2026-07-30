<?php

namespace App\Modules\Keuangan\Models;

use App\Core\BaseModel;
use PDO;

class TransaksiPembayaranModel extends BaseModel {
    protected static string $table = 'transaksi_spp_pembayaran';
    protected static string $schema = 'keuangan';

    public static function getTransaksiByTagihan(string $tenantId, string $tagihanId): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT tp.id, tp.tenant_id, tp.tagihan_id, tp.jumlah_bayar, tp.tanggal_bayar, tp.metode_bayar, tp.no_referensi
            FROM {$table} tp
            WHERE tp.tenant_id = :tenant_id AND tp.tagihan_id = :tagihan_id
            ORDER BY tp.tanggal_bayar DESC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':tagihan_id', $tagihanId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
