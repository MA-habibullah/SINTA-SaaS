<?php

namespace App\Modules\Perpustakaan\Models;

use App\Core\BaseModel;
use PDO;

class BibliografiModel extends BaseModel {
    protected static string $table = 'perpustakaan.perpus_bibliografi';
    protected static string $schema = 'perpustakaan';

    public static function getKatalog(string $tenantId, int $limit = 50, int $offset = 0): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT b.id, b.tenant_id, b.judul, b.pengarang, b.isbn_issn, b.penerbit, b.tahun_terbit, b.jumlah_eksemplar
            FROM {$table} b
            WHERE b.tenant_id = :tenant_id
            ORDER BY b.judul ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
