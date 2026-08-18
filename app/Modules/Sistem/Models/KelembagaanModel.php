<?php

namespace App\Modules\Sistem\Models;

use App\Core\Model;
use PDO;

class KelembagaanModel extends Model {

    protected ?string $tenantId = null;

    public function __construct(?string $tenantId = null) {
        parent::__construct();
        $this->tenantId = $tenantId;
    }

    public function setTenantId(?string $tenantId): void {
        $this->tenantId = $tenantId;
    }

    // Schema Mapper: Pemetaan module ke schema.tabel PostgreSQL dan kolom presisi
    private array $allowedTables = [
        'jenjang' => [
            'schema'         => 'core',
            'table'          => 'jenjang',
            'code_field'     => 'kode_jenjang',
            'name_field'     => 'nama_jenjang',
            'search_cols'    => ['kode_jenjang', 'nama_jenjang'],
            'has_created_at' => false
        ],
        'jurusan' => [
            'schema'         => 'akademik',
            'table'          => 'jurusan',
            'code_field'     => 'id',
            'name_field'     => 'nama_jurusan',
            'search_cols'    => ['nama_jurusan', 'kategori', 'deskripsi'],
            'has_created_at' => true
        ],
        'kelas' => [
            'schema'         => 'akademik',
            'table'          => 'kelas',
            'code_field'     => 'id',
            'name_field'     => 'nama_kelas',
            'search_cols'    => ['nama_kelas', 'kategori', 'deskripsi'],
            'has_created_at' => true
        ],
        'mata_pelajaran' => [
            'schema'         => 'akademik',
            'table'          => 'mata_pelajaran',
            'code_field'     => 'id',
            'name_field'     => 'nama_mata_pelajaran',
            'search_cols'    => ['nama_mata_pelajaran', 'kategori', 'deskripsi'],
            'has_created_at' => true
        ],
        'pendidikan' => [
            'schema'         => 'akademik',
            'table'          => 'pendidikan',
            'code_field'     => 'id',
            'name_field'     => 'nama_pendidikan',
            'search_cols'    => ['nama_pendidikan', 'kategori', 'deskripsi'],
            'has_created_at' => true
        ],
        'program_pengajaran' => [
            'schema'         => 'akademik',
            'table'          => 'program_pengajaran',
            'code_field'     => 'kode_program',
            'name_field'     => 'nama_program',
            'search_cols'    => ['kode_program', 'nama_program'],
            'has_created_at' => true
        ],
        'tahun_ajaran' => [
            'schema'         => 'akademik',
            'table'          => 'tahun_ajaran',
            'code_field'     => 'id',
            'name_field'     => 'nama_tahun_ajaran',
            'search_cols'    => ['nama_tahun_ajaran', 'kategori', 'deskripsi'],
            'has_created_at' => true
        ],
        'angkatan' => [
            'schema'         => 'akademik',
            'table'          => 'angkatan',
            'code_field'     => 'id',
            'name_field'     => 'nama_angkatan',
            'search_cols'    => ['nama_angkatan', 'kategori', 'deskripsi'],
            'has_created_at' => true
        ],
        'kurikulum' => [
            'schema'         => 'akademik',
            'table'          => 'ref_kurikulum',
            'code_field'     => 'id',
            'name_field'     => 'nama_ref_kurikulum',
            'search_cols'    => ['nama_ref_kurikulum', 'kategori', 'deskripsi'],
            'has_created_at' => true
        ]
    ];

    private function validateTableName(string $table): void {
        if (!array_key_exists($table, $this->allowedTables)) {
            throw new \InvalidArgumentException("Modul kelembagaan '$table' tidak valid atau dilarang.");
        }
    }

    public function getTableFullName(string $table): string {
        $this->validateTableName($table);
        $meta = $this->allowedTables[$table];
        return $meta['schema'] . '.' . $meta['table'];
    }

    public function getPaginated(string $table, array $filters = []): array {
        $this->validateTableName($table);

        $search    = $filters['search'] ?? '';
        $perPage   = (int)($filters['per_page'] ?? 10);
        $page      = (int)($filters['page'] ?? 1);
        $offset    = ($page - 1) * $perPage;
        $trashMode = ($filters['trash'] ?? 'false') === 'true';

        $params = [];
        $isSuperAdmin = $this->isSuperAdminContext();
        if (!$isSuperAdmin) {
            $params['tenant_id'] = $this->tenantId;
        }

        $fullTable = $this->getTableFullName($table);
        $meta = $this->allowedTables[$table];

        if ($table === 'kelas') {
            $selectSql = "SELECT k.*, k.nama_kelas as nama, k.kode_kelas as kode,
                                 j.nama_jenjang, jur.nama_jurusan, t.nama_sekolah 
                          FROM akademik.kelas k
                          LEFT JOIN core.jenjang j ON k.id_jenjang::text = j.id::text
                          LEFT JOIN akademik.jurusan jur ON k.id_jurusan::text = jur.id::text
                          LEFT JOIN core.tenants t ON k.tenant_id::text = t.id::text";
        } elseif ($table === 'tahun_ajaran') {
            $selectSql = "SELECT k.*, k.nama_tahun_ajaran as nama, k.nama_tahun_ajaran as tahun_ajaran, t.nama_sekolah 
                          FROM akademik.tahun_ajaran k
                          LEFT JOIN core.tenants t ON k.tenant_id::text = t.id::text";
        } elseif ($table === 'angkatan') {
            $selectSql = "SELECT k.*, k.nama_angkatan as nama, k.nama_angkatan as tahun_angkatan, t.nama_sekolah 
                          FROM akademik.angkatan k
                          LEFT JOIN core.tenants t ON k.tenant_id::text = t.id::text";
        } else {
            $selectSql = "SELECT k.*, k.{$meta['name_field']} as nama, t.nama_sekolah 
                          FROM {$fullTable} k
                          LEFT JOIN core.tenants t ON k.tenant_id::text = t.id::text";
        }
        if ($isSuperAdmin && empty($this->tenantId)) {
            $whereClause = " WHERE 1=1";
        } else {
            $effectiveTenant = !empty($this->tenantId) ? $this->tenantId : SessionManager::getTenantId();
            if ($table === 'kurikulum') {
                $whereClause = " WHERE (k.tenant_id::text = :tenant_id OR k.tenant_id::text = '11111111-1111-1111-1111-111111111111' OR k.tenant_id IS NULL)";
            } else {
                $whereClause = " WHERE k.tenant_id::text = :tenant_id";
            }
            $params['tenant_id'] = $effectiveTenant;
        }

        $countSql = "SELECT COUNT(*) FROM {$fullTable} k LEFT JOIN core.tenants t ON k.tenant_id::text = t.id::text";

        if ($trashMode) {
            $whereClause .= " AND k.is_active = false";
        } else {
            $whereClause .= " AND k.is_active = true";
        }

        if ($search !== '') {
            $searchParts = [];
            $cols = [];
            foreach ($meta['search_cols'] as $col) {
                $cols[] = "k.{$col}";
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

        $countStmt = $this->db->prepare($countSql . $whereClause);
        if (isset($params['tenant_id'])) {
            $countStmt->bindValue(':tenant_id', $params['tenant_id'], PDO::PARAM_STR);
        }
        foreach ($params as $key => $val) {
            if ($key !== 'tenant_id') {
                $countStmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
            }
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        if ($meta['has_created_at']) {
            $orderBy = " ORDER BY k.created_at DESC";
        } else {
            $orderBy = " ORDER BY k.id DESC";
        }
        $limitClause = " LIMIT :limit OFFSET :offset";
        
        $dataStmt = $this->db->prepare($selectSql . $whereClause . $orderBy . $limitClause);
        
        if (isset($params['tenant_id'])) {
            $dataStmt->bindValue(':tenant_id', $params['tenant_id'], PDO::PARAM_STR);
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
            'data'         => $list,
            'current_page' => $page,
            'last_page'    => $totalPages,
            'per_page'     => $perPage,
            'total'        => $total,
            'from'         => $from,
            'to'           => $to
        ];
    }

    public function getOptions(string $table): array {
        $this->validateTableName($table);
        $fullTable = $this->getTableFullName($table);
        $meta = $this->allowedTables[$table];

        $nameCol   = $meta['name_field'];
        $extraCols = "";
        if ($table === 'kelas') {
            $extraCols = ", id_jenjang, kode_kelas, nama_kelas";
        } elseif ($table === 'jenjang') {
            $extraCols = ", kode_jenjang";
        }

        if (!empty($this->tenantId) && $this->tenantId !== '00000000-0000-0000-0000-000000000000') {
            $sql = "SELECT id, {$nameCol} AS nama {$extraCols} 
                    FROM {$fullTable} 
                    WHERE (tenant_id::text = :tenant_id OR tenant_id IS NULL) AND is_active = true 
                    ORDER BY {$nameCol} ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tenant_id' => (string)$this->tenantId]);
        } else {
            $sql = "SELECT DISTINCT ON ({$nameCol}) id, {$nameCol} AS nama {$extraCols} 
                    FROM {$fullTable} 
                    WHERE is_active = true 
                    ORDER BY {$nameCol} ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(string $table, string $id): ?array {
        $this->validateTableName($table);
        $isSuperAdmin = $this->isSuperAdminContext();
        $fullTable = $this->getTableFullName($table);
        
        if ($isSuperAdmin) {
            $sql = "SELECT * FROM {$fullTable} WHERE id::text = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
        } else {
            $sql = "SELECT * FROM {$fullTable} WHERE id::text = :id AND tenant_id::text = :tenant_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id'        => $id,
                'tenant_id' => $this->tenantId
            ]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function isCodeUnique(string $table, string $code, ?string $excludeId = null): bool {
        $this->validateTableName($table);
        $meta = $this->allowedTables[$table];
        $codeCol = $meta['code_field'];
        $isSuperAdmin = $this->isSuperAdminContext();
        $fullTable = $this->getTableFullName($table);
        
        if ($isSuperAdmin) {
            $sql = "SELECT COUNT(*) FROM {$fullTable} 
                    WHERE {$codeCol} = :code AND is_active = true";
        } else {
            $sql = "SELECT COUNT(*) FROM {$fullTable} 
                    WHERE tenant_id = :tenant_id AND {$codeCol} = :code AND is_active = true";
        }
        
        if ($excludeId !== null) {
            $sql .= " AND id::text != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $params = ['code' => $code];
        if (!$isSuperAdmin) {
            $params['tenant_id'] = $this->tenantId;
        }
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }

        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    public function create(string $table, array $data): string {
        $this->validateTableName($table);
        $fullTable = $this->getTableFullName($table);
        $meta      = $this->allowedTables[$table];
        
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $targetTenantId = !empty($this->tenantId) 
            ? $this->tenantId 
            : (!empty($data['tenant_id']) ? $data['tenant_id'] : SessionManager::getTenantId());

        if (empty($targetTenantId) || $targetTenantId === '00000000-0000-0000-0000-000000000000') {
            throw new \InvalidArgumentException("Pilih instansi sekolah terlebih dahulu.");
        }

        if ($table === 'kelas') {
            $fields = ['id', 'tenant_id', 'nama_kelas', 'kode_kelas', 'id_jenjang', 'id_jurusan', 'is_active'];
            $placeholders = [':id', ':tenant_id', ':nama_kelas', ':kode_kelas', ':id_jenjang', ':id_jurusan', ':is_active'];
            $params = [
                'id'         => $uuid,
                'tenant_id'  => $targetTenantId,
                'nama_kelas' => strip_tags(trim($data['nama_kelas'] ?? $data['nama'] ?? '')),
                'kode_kelas' => strip_tags(trim($data['kode_kelas'] ?? $data['kode'] ?? '')),
                'id_jenjang' => !empty($data['id_jenjang']) ? strip_tags(trim($data['id_jenjang'])) : null,
                'id_jurusan' => !empty($data['id_jurusan']) ? strip_tags(trim($data['id_jurusan'])) : null,
                'is_active'  => true
            ];
        } elseif ($table === 'kurikulum') {
            $fields = ['id', 'tenant_id', 'nama_ref_kurikulum', 'kategori', 'is_active'];
            $placeholders = [':id', ':tenant_id', ':nama_ref_kurikulum', ':kategori', ':is_active'];
            $params = [
                'id'                 => $uuid,
                'tenant_id'          => $targetTenantId,
                'nama_ref_kurikulum' => strip_tags(trim($data['nama_kurikulum'] ?? $data['nama'] ?? '')),
                'kategori'           => strip_tags(trim($data['tipe_penilaian'] ?? 'sederhana')),
                'is_active'          => true
            ];
        } else {
            $namaVal = strip_tags(trim($data['nama'] ?? $data[$meta['name_field']] ?? $data['kode'] ?? ''));
            $fields  = ['id', 'tenant_id', $meta['name_field'], 'is_active'];
            $placeholders = [':id', ':tenant_id', ':nama', ':is_active'];
            $params  = [
                'id'        => $uuid,
                'tenant_id' => $targetTenantId,
                'nama'      => $namaVal,
                'is_active' => true
            ];

            if ($meta['code_field'] !== 'id' && $meta['code_field'] !== $meta['name_field']) {
                $fields[] = $meta['code_field'];
                $placeholders[] = ':code_val';
                $params['code_val'] = strip_tags(trim($data['kode'] ?? $data[$meta['code_field']] ?? ''));
            }
        }

        try {
            $this->db->beginTransaction();
            $sql = "INSERT INTO {$fullTable} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $this->db->commit();
            return $uuid;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(string $table, string $id, array $data): bool {
        $this->validateTableName($table);
        $fullTable = $this->getTableFullName($table);
        $meta      = $this->allowedTables[$table];

        if ($table === 'kelas') {
            $sets = ["nama_kelas = :nama_kelas", "kode_kelas = :kode_kelas", "id_jenjang = :id_jenjang", "id_jurusan = :id_jurusan"];
            $params = [
                'id'         => $id,
                'nama_kelas' => strip_tags(trim($data['nama_kelas'] ?? $data['nama'] ?? '')),
                'kode_kelas' => strip_tags(trim($data['kode_kelas'] ?? $data['kode'] ?? '')),
                'id_jenjang' => !empty($data['id_jenjang']) ? strip_tags(trim($data['id_jenjang'])) : null,
                'id_jurusan' => !empty($data['id_jurusan']) ? strip_tags(trim($data['id_jurusan'])) : null
            ];
            if ($this->tenantId !== null) {
                $params['tenant_id'] = $this->tenantId;
            }
        } elseif ($table === 'kurikulum') {
            $sets = ["nama_ref_kurikulum = :nama_ref_kurikulum", "kategori = :kategori"];
            $params = [
                'id'                 => $id,
                'nama_ref_kurikulum' => strip_tags(trim($data['nama_kurikulum'] ?? $data['nama'] ?? '')),
                'kategori'           => strip_tags(trim($data['tipe_penilaian'] ?? 'sederhana'))
            ];
            if ($this->tenantId !== null) {
                $params['tenant_id'] = $this->tenantId;
            }
        } else {
            $namaVal = strip_tags(trim($data['nama'] ?? $data[$meta['name_field']] ?? $data['kode'] ?? ''));
            $sets    = ["{$meta['name_field']} = :nama"];
            $params  = [
                'id'   => $id,
                'nama' => $namaVal
            ];

            if ($this->tenantId !== null) {
                $params['tenant_id'] = $this->tenantId;
            }

            if ($meta['code_field'] !== 'id' && $meta['code_field'] !== $meta['name_field']) {
                $sets[] = "{$meta['code_field']} = :code_val";
                $params['code_val'] = strip_tags(trim($data['kode'] ?? $data[$meta['code_field']] ?? ''));
            }
        }

        try {
            $this->db->beginTransaction();
            $sql = "UPDATE {$fullTable} SET " . implode(', ', $sets) . " WHERE id::text = :id";
            if ($this->tenantId !== null) {
                $sql .= " AND tenant_id::text = :tenant_id";
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

    public function checkDataInUse(string $table, string $id): array {
        $this->validateTableName($table);
        $item = $this->findById($table, $id);
        if (!$item) {
            return ['in_use' => false, 'reasons' => []];
        }

        $reasons = [];

        if ($table === 'mata_pelajaran') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM akademik.pemetaan_mapel WHERE mapel_id::text = ? AND is_active = true");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $reasons[] = "Pemetaan Kelompok Mata Pelajaran";
            }
        } elseif ($table === 'kelas') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM siswa.siswa WHERE (kelas_saat_ini::text = ? OR kelas_saat_ini = ?) AND is_active = true");
            $stmt->execute([$id, $id]);
            if ($stmt->fetchColumn() > 0) {
                $reasons[] = "Data Siswa Aktif";
            }
        }

        return [
            'in_use'  => !empty($reasons),
            'reasons' => $reasons
        ];
    }

    public function delete(string $table, string $id): bool {
        $this->validateTableName($table);
        $fullTable = $this->getTableFullName($table);

        $usage = $this->checkDataInUse($table, $id);
        if ($usage['in_use']) {
            $reasonStr = implode(', ', $usage['reasons']);
            throw new \InvalidArgumentException("Data ini tidak dapat dihapus karena sedang terhubung/digunakan pada: {$reasonStr}. Silakan nonaktifkan status keaktifannya melalui saklar status jika tidak lagi digunakan.");
        }

        try {
            $this->db->beginTransaction();
            $sql = "UPDATE {$fullTable} SET is_active = false WHERE id::text = :id";
            $params = ['id' => $id];
            if ($this->tenantId !== null) {
                $sql .= " AND tenant_id::text = :tenant_id";
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

    public function restore(string $table, string $id): bool {
        $this->validateTableName($table);
        $fullTable = $this->getTableFullName($table);

        try {
            $this->db->beginTransaction();
            $sql = "UPDATE {$fullTable} SET is_active = true WHERE id::text = :id";
            $params = ['id' => $id];
            if ($this->tenantId !== null) {
                $sql .= " AND tenant_id::text = :tenant_id";
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

    public function toggleStatus(string $table, string $id): bool {
        $this->validateTableName($table);
        $fullTable = $this->getTableFullName($table);

        try {
            $this->db->beginTransaction();
            
            $sql = "SELECT is_active FROM {$fullTable} WHERE id::text = :id LIMIT 1";
            $params = ['id' => $id];
            if ($this->tenantId !== null) {
                $sql = "SELECT is_active FROM {$fullTable} WHERE id::text = :id AND tenant_id::text = :tenant_id LIMIT 1";
                $params['tenant_id'] = $this->tenantId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                $this->db->rollBack();
                return false;
            }

            $newStatus = $current['is_active'] ? 'false' : 'true';

            $updateSql = "UPDATE {$fullTable} SET is_active = {$newStatus} WHERE id::text = :id";
            $updateParams = ['id' => $id];
            if ($this->tenantId !== null) {
                $updateSql .= " AND tenant_id::text = :tenant_id";
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

    private function isSuperAdminContext(): bool {
        return empty($this->tenantId) || $this->tenantId === '00000000-0000-0000-0000-000000000000';
    }

    public function getTenants(): array {
        $stmt = $this->db->query("SELECT id, nama_sekolah, npsn FROM core.tenants WHERE status = 'active' AND id != '00000000-0000-0000-0000-000000000000' ORDER BY nama_sekolah ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}