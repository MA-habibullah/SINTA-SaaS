<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Kelembagaan extends Model {

    // Daftar tabel yang diizinkan untuk keamanan database
    private array $allowedTables = [
        'jenjang' => [
            'code_field' => 'kode_jenjang',
            'name_field' => 'nama_jenjang',
            'search_cols' => ['kode_jenjang', 'nama_jenjang']
        ],
        'jurusan' => [
            'code_field' => 'kode_jurusan',
            'name_field' => 'nama_jurusan',
            'search_cols' => ['kode_jurusan', 'nama_jurusan']
        ],
        'kelas' => [
            'code_field' => 'kode_kelas',
            'name_field' => 'nama_kelas',
            'search_cols' => ['kode_kelas', 'nama_kelas']
        ],
        'mata_pelajaran' => [
            'code_field' => 'kode_mapel',
            'name_field' => 'nama_mapel',
            'search_cols' => ['kode_mapel', 'nama_mapel']
        ],
        'pendidikan' => [
            'code_field' => 'kode_pendidikan',
            'name_field' => 'nama_pendidikan',
            'search_cols' => ['kode_pendidikan', 'nama_pendidikan']
        ],
        'program_pengajaran' => [
            'code_field' => 'kode_program',
            'name_field' => 'nama_program',
            'search_cols' => ['kode_program', 'nama_program']
        ],
        'tahun_ajaran' => [
            'code_field' => 'tahun_ajaran',
            'name_field' => 'tahun_ajaran',
            'search_cols' => ['tahun_ajaran']
        ],
        'angkatan' => [
            'code_field' => 'tahun_angkatan',
            'name_field' => 'tahun_angkatan',
            'search_cols' => ['tahun_angkatan']
        ]
    ];

    /**
     * Memastikan nama tabel valid dan aman
     */
    private function validateTableName(string $table): void {
        if (!array_key_exists($table, $this->allowedTables)) {
            throw new \InvalidArgumentException("Tabel '{$table}' tidak valid atau dilarang.");
        }
    }

    /**
     * Ambil data terpaginasi dengan filter pencarian, status aktif, dan mode tong sampah
     */
    public function getPaginated(string $table, array $filters = []): array {
        $this->validateTableName($table);

        $search = $filters['search'] ?? '';
        $perPage = (int)($filters['per_page'] ?? 10);
        $page = (int)($filters['page'] ?? 1);
        $offset = ($page - 1) * $perPage;
        $trashMode = ($filters['trash'] ?? 'false') === 'true';

        $params = [];
        $isSuperAdmin = ($this->tenantId === null);
        if (!$isSuperAdmin) {
            $params['tenant_id'] = $this->tenantId;
        }

        // Memilih query dasar
        if ($table === 'kelas') {
            // Relasi belongsTo Jenjang dan Jurusan
            $selectSql = "SELECT k.*, j.nama_jenjang, ju.nama_jurusan, t.nama_sekolah 
                          FROM kelas k
                          LEFT JOIN jenjang j ON k.id_jenjang = j.id
                          LEFT JOIN jurusan ju ON k.id_jurusan = ju.id
                          LEFT JOIN tenants t ON k.tenant_id = t.id";
            $whereClause = $isSuperAdmin ? " WHERE 1=1" : " WHERE k.tenant_id = :tenant_id";
        } else {
            $selectSql = "SELECT k.*, t.nama_sekolah 
                          FROM {$table} k
                          LEFT JOIN tenants t ON k.tenant_id = t.id";
            $whereClause = $isSuperAdmin ? " WHERE 1=1" : " WHERE k.tenant_id = :tenant_id";
        }
        $countSql = "SELECT COUNT(*) FROM {$table} k LEFT JOIN tenants t ON k.tenant_id = t.id";

        // Filter status soft delete
        if ($trashMode) {
            $whereClause .= " AND k.deleted_at IS NOT NULL";
        } else {
            $whereClause .= " AND k.deleted_at IS NULL";
        }

        // Fitur Pencarian Global
        if ($search !== '') {
            $searchParts = [];
            $meta = $this->allowedTables[$table];
            
            $cols = [];
            if ($table === 'kelas') {
                $cols[] = "k.nama_kelas";
                $cols[] = "k.kode_kelas";
                $cols[] = "j.nama_jenjang";
                $cols[] = "ju.nama_jurusan";
            } else {
                foreach ($meta['search_cols'] as $col) {
                    $cols[] = "k.{$col}";
                }
            }

            if ($isSuperAdmin) {
                $cols[] = "t.nama_sekolah";
            }
            
            foreach ($cols as $i => $col) {
                $paramName = "search_" . $i;
                $searchParts[] = "{$col} LIKE :{$paramName}";
                $params[$paramName] = "%{$search}%";
            }
            
            if (!empty($searchParts)) {
                $whereClause .= " AND (" . implode(" OR ", $searchParts) . ")";
            }
        }

        // Hitung total data
        $countStmt = $this->db->prepare($countSql . $whereClause);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Ambil data halaman aktif dengan pagination
        $orderBy = " ORDER BY k.id DESC"; // default sort
        $limitClause = " LIMIT :limit OFFSET :offset";
        
        $dataStmt = $this->db->prepare($selectSql . $whereClause . $orderBy . $limitClause);
        
        // Bind parameters manually for offset and limit (must be INT for PDO)
        if (!$isSuperAdmin) {
            $dataStmt->bindValue(':tenant_id', $this->tenantId, PDO::PARAM_STR);
        }
        foreach ($params as $key => $val) {
            if ($key !== 'tenant_id') {
                $dataStmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
            }
        }
        $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $dataStmt->execute();
        $list = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($total / $perPage);
        $from = $total > 0 ? $offset + 1 : 0;
        $to = min($offset + $perPage, $total);

        return [
            'data' => $list,
            'current_page' => $page,
            'last_page' => $totalPages,
            'per_page' => $perPage,
            'total' => $total,
            'from' => $from,
            'to' => $to
        ];
    }

    /**
     * Ambil opsi helper (untuk dropdown select di form Kelas)
     */
    public function getOptions(string $table): array {
        $this->validateTableName($table);
        $isSuperAdmin = ($this->tenantId === null);
        
        if ($isSuperAdmin) {
            $sql = "SELECT id, " . $this->allowedTables[$table]['name_field'] . " AS nama 
                    FROM {$table} 
                    WHERE deleted_at IS NULL AND is_active = 1 
                    ORDER BY nama ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            $sql = "SELECT id, " . $this->allowedTables[$table]['name_field'] . " AS nama 
                    FROM {$table} 
                    WHERE tenant_id = :tenant_id AND deleted_at IS NULL AND is_active = 1 
                    ORDER BY nama ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tenant_id' => $this->tenantId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil satu data detail berdasarkan ID
     */
    public function findById(string $table, int $id): ?array {
        $this->validateTableName($table);
        $isSuperAdmin = ($this->tenantId === null);
        
        if ($isSuperAdmin) {
            $sql = "SELECT * FROM {$table} WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
        } else {
            $sql = "SELECT * FROM {$table} WHERE id = :id AND tenant_id = :tenant_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'tenant_id' => $this->tenantId
            ]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Memeriksa keunikan kode data per sekolah (tenant)
     */
    public function isCodeUnique(string $table, string $code, ?int $excludeId = null): bool {
        $this->validateTableName($table);
        $codeCol = $this->allowedTables[$table]['code_field'];
        $isSuperAdmin = ($this->tenantId === null);
        
        if ($isSuperAdmin) {
            $sql = "SELECT COUNT(*) FROM {$table} 
                    WHERE {$codeCol} = :code AND deleted_at IS NULL";
        } else {
            $sql = "SELECT COUNT(*) FROM {$table} 
                    WHERE tenant_id = :tenant_id AND {$codeCol} = :code AND deleted_at IS NULL";
        }
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $params = [
            'code' => $code
        ];
        if (!$isSuperAdmin) {
            $params['tenant_id'] = $this->tenantId;
        }
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }

        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Simpan data baru
     */
    public function create(string $table, array $data): int {
        $this->validateTableName($table);
        
        $fields = ['tenant_id'];
        $placeholders = [':tenant_id'];
        $params = ['tenant_id' => $this->tenantId];

        if ($table === 'kelas') {
            $fields[] = 'id_jenjang';
            $fields[] = 'id_jurusan';
            $fields[] = 'kode_kelas';
            $fields[] = 'nama_kelas';
            
            $placeholders[] = ':id_jenjang';
            $placeholders[] = ':id_jurusan';
            $placeholders[] = ':kode_kelas';
            $placeholders[] = ':nama_kelas';

            $params['id_jenjang'] = (int)$data['id_jenjang'];
            $params['id_jurusan'] = (int)$data['id_jurusan'];
            $params['kode_kelas'] = strip_tags(trim($data['kode_kelas']));
            $params['nama_kelas'] = strip_tags(trim($data['nama_kelas']));
        } else {
            $meta = $this->allowedTables[$table];
            $codeCol = $meta['code_field'];
            $nameCol = $meta['name_field'];

            $fields[] = $codeCol;
            $placeholders[] = ":{$codeCol}";
            $params[$codeCol] = strip_tags(trim($data['kode']));

            // Jika kolom kode dan nama sama (seperti tahun_ajaran dan angkatan)
            if ($codeCol !== $nameCol) {
                $fields[] = $nameCol;
                $placeholders[] = ":{$nameCol}";
                $params[$nameCol] = strip_tags(trim($data['nama']));
            }
        }

        try {
            $this->db->beginTransaction();
            $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $lastId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $lastId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Perbarui data yang ada
     */
    public function update(string $table, int $id, array $data): bool {
        $this->validateTableName($table);

        $sets = [];
        $params = [
            'id' => $id
        ];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }

        if ($table === 'kelas') {
            $sets[] = "id_jenjang = :id_jenjang";
            $sets[] = "id_jurusan = :id_jurusan";
            $sets[] = "kode_kelas = :kode_kelas";
            $sets[] = "nama_kelas = :nama_kelas";

            $params['id_jenjang'] = (int)$data['id_jenjang'];
            $params['id_jurusan'] = (int)$data['id_jurusan'];
            $params['kode_kelas'] = strip_tags(trim($data['kode_kelas']));
            $params['nama_kelas'] = strip_tags(trim($data['nama_kelas']));
        } else {
            $meta = $this->allowedTables[$table];
            $codeCol = $meta['code_field'];
            $nameCol = $meta['name_field'];

            $sets[] = "{$codeCol} = :{$codeCol}";
            $params[$codeCol] = strip_tags(trim($data['kode']));

            if ($codeCol !== $nameCol) {
                $sets[] = "{$nameCol} = :{$nameCol}";
                $params[$nameCol] = strip_tags(trim($data['nama']));
            }
        }

        try {
            $this->db->beginTransaction();
            $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = :id AND deleted_at IS NULL";
            if ($this->tenantId !== null) {
                $sql .= " AND tenant_id = :tenant_id";
            }
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);
            $this->db->commit();
            return $success;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Soft Delete (Pindahkan ke Tong Sampah)
     */
    public function delete(string $table, int $id): bool {
        $this->validateTableName($table);
        try {
            $this->db->beginTransaction();
            $sql = "UPDATE {$table} SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
            $params = ['id' => $id];
            if ($this->tenantId !== null) {
                $sql .= " AND tenant_id = :tenant_id";
                $params['tenant_id'] = $this->tenantId;
            }
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);
            $this->db->commit();
            return $success;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Pulihkan dari Tong Sampah
     */
    public function restore(string $table, int $id): bool {
        $this->validateTableName($table);
        try {
            $this->db->beginTransaction();
            $sql = "UPDATE {$table} SET deleted_at = NULL WHERE id = :id";
            $params = ['id' => $id];
            if ($this->tenantId !== null) {
                $sql .= " AND tenant_id = :tenant_id";
                $params['tenant_id'] = $this->tenantId;
            }
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);
            $this->db->commit();
            return $success;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ubah status keaktifan switch toggle
     */
    public function toggleStatus(string $table, int $id): bool {
        $this->validateTableName($table);
        
        try {
            $this->db->beginTransaction();
            
            // Ambil status saat ini
            $sql = "SELECT is_active FROM {$table} WHERE id = :id AND deleted_at IS NULL LIMIT 1";
            $params = ['id' => $id];
            if ($this->tenantId !== null) {
                $sql = "SELECT is_active FROM {$table} WHERE id = :id AND tenant_id = :tenant_id AND deleted_at IS NULL LIMIT 1";
                $params['tenant_id'] = $this->tenantId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                $this->db->rollBack();
                return false;
            }

            $newStatus = $current['is_active'] ? 0 : 1;

            $updateSql = "UPDATE {$table} SET is_active = :status WHERE id = :id";
            $updateParams = ['status' => $newStatus, 'id' => $id];
            if ($this->tenantId !== null) {
                $updateSql .= " AND tenant_id = :tenant_id";
                $updateParams['tenant_id'] = $this->tenantId;
            }
            
            $updateStmt = $this->db->prepare($updateSql);
            $success = $updateStmt->execute($updateParams);
            
            $this->db->commit();
            return $success;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ambil daftar semua tenant aktif
     */
    public function getTenants(): array {
        $stmt = $this->db->query("SELECT id, nama_sekolah FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
