<?php

namespace App\Modules\Siswa\Models;

use App\Core\BaseModel;
use PDO;

class SiswaModel extends BaseModel {
    protected static string $table = 'siswa';
    protected static string $schema = 'siswa';

    public static function findById(string $tenantId, string $siswaId): ?array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("SELECT id, tenant_id, nama_lengkap, nis, nisn, is_active FROM {$table} WHERE tenant_id = :tenant_id AND id = :id LIMIT 1");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':id', $siswaId);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function getActiveSiswa(string $tenantId, int $limit = 50, int $offset = 0): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("SELECT id, tenant_id, nama_lengkap, nis, nisn, jenis_kelamin, status_siswa FROM {$table} WHERE tenant_id = :tenant_id AND is_active = true ORDER BY nama_lengkap ASC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
