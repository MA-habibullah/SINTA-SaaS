<?php

namespace App\Modules\Alumni\Models;

use App\Core\BaseModel;
use PDO;

class AlumniModel extends BaseModel {

    protected string $table = 'alumni.alumni';

    /**
     * Ambil daftar alumni terpaginasi
     */
    public function getAlumniList(?string $tenantId, int $page = 1, int $limit = 20): array {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT a.*, s.nama_lengkap, s.nisn 
                FROM alumni.alumni a
                JOIN siswa.siswa s ON a.siswa_id = s.id
                WHERE 1=1";
        $params = [];

        if ($tenantId) {
            $sql .= " AND a.tenant_id = :tenant_id";
            $params['tenant_id'] = $tenantId;
        }

        $sql .= " ORDER BY a.tahun_lulus DESC, s.nama_lengkap ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":$k", $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
