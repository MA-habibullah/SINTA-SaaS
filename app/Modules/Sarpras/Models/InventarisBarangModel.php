<?php

namespace App\Modules\Sarpras\Models;

use App\Core\BaseModel;
use PDO;

class InventarisBarangModel extends BaseModel {
    protected static string $table = 'sarpras.barang_modal';
    protected static string $schema = 'sarpras';

    public static function getInventaris(string $tenantId, int $limit = 50, int $offset = 0): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT ib.id, ib.tenant_id, ib.kode_barang, ib.nama_barang, ib.kategori, ib.jumlah, ib.kondisi_baik, ib.kondisi_rusak
            FROM {$table} ib
            WHERE ib.tenant_id = :tenant_id
            ORDER BY ib.nama_barang ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
