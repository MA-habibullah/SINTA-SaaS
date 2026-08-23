<?php

namespace App\Modules\Core\Models;

use App\Config\Database;
use Exception;
use PDO;

class AgendaModel {
    private PDO $db;
    private ?string $tenantId;

    public function __construct(?string $tenantId = null) {
        $this->db = Database::getConnection();
        $this->tenantId = $tenantId;
    }

    public function getAll(array $filters = [], int $limit = 200, int $offset = 0): array {
        $sql = "SELECT 
                    a.id,
                    a.tenant_id,
                    a.nama_agenda_sekolah,
                    a.kategori,
                    a.deskripsi,
                    a.is_active,
                    a.created_at,
                    a.updated_at,
                    t.nama_sekolah
                FROM sistem.agenda_sekolah a
                LEFT JOIN core.tenants t ON a.tenant_id = t.id
                WHERE 1=1";

        $params = [];

        // Tenant filter
        if (!empty($filters['tenant_id'])) {
            if ($filters['tenant_id'] === 'global') {
                $sql .= " AND a.tenant_id IS NULL";
            } else {
                $sql .= " AND (a.tenant_id = :tenant_id OR a.tenant_id IS NULL)";
                $params[':tenant_id'] = $filters['tenant_id'];
            }
        } elseif (!empty($this->tenantId)) {
            $sql .= " AND (a.tenant_id = :tenant_id OR a.tenant_id IS NULL)";
            $params[':tenant_id'] = $this->tenantId;
        }

        // Search filter (Case-insensitive ILIKE)
        if (!empty($filters['search'])) {
            $sql .= " AND (a.nama_agenda_sekolah ILIKE :search OR a.deskripsi ILIKE :search OR a.kategori ILIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        // Kategori filter
        if (!empty($filters['kategori'])) {
            $sql .= " AND a.kategori = :kategori";
            $params[':kategori'] = $filters['kategori'];
        }

        // Status filter
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND a.is_active = :is_active";
            $params[':is_active'] = (bool)$filters['is_active'] ? 'TRUE' : 'FALSE';
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k === ':is_active') {
                $stmt->bindValue($k, (bool)$filters['is_active'], PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue($k, $v);
            }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Parse structured metadata stored inside deskripsi JSON
        $formatted = array_map(function($row) {
            return $this->formatRow($row);
        }, $rows);

        // Filter by visibilitas if specified
        if (!empty($filters['visibilitas'])) {
            $formatted = array_values(array_filter($formatted, function($item) use ($filters) {
                return ($item['visibilitas'] ?? 'public') === $filters['visibilitas'];
            }));
        }

        // Filter by month if specified (format: YYYY-MM)
        if (!empty($filters['month'])) {
            $m = $filters['month'];
            $formatted = array_values(array_filter($formatted, function($item) use ($m) {
                $startM = substr($item['tanggal_mulai'] ?? '', 0, 7);
                $endM = substr($item['tanggal_selesai'] ?? '', 0, 7);
                return ($startM === $m || $endM === $m || ($startM <= $m && $endM >= $m));
            }));
        }

        return $formatted;
    }

    public function countAll(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM sistem.agenda_sekolah a WHERE 1=1";
        $params = [];

        if (!empty($filters['tenant_id'])) {
            if ($filters['tenant_id'] === 'global') {
                $sql .= " AND a.tenant_id IS NULL";
            } else {
                $sql .= " AND (a.tenant_id = :tenant_id OR a.tenant_id IS NULL)";
                $params[':tenant_id'] = $filters['tenant_id'];
            }
        } elseif (!empty($this->tenantId)) {
            $sql .= " AND (a.tenant_id = :tenant_id OR a.tenant_id IS NULL)";
            $params[':tenant_id'] = $this->tenantId;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (a.nama_agenda_sekolah ILIKE :search OR a.deskripsi ILIKE :search OR a.kategori ILIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['kategori'])) {
            $sql .= " AND a.kategori = :kategori";
            $params[':kategori'] = $filters['kategori'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND a.is_active = :is_active";
            $params[':is_active'] = (bool)$filters['is_active'] ? 'TRUE' : 'FALSE';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k === ':is_active') {
                $stmt->bindValue($k, (bool)$filters['is_active'], PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue($k, $v);
            }
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getStatsSummary(?string $tenantId = null): array {
        $effectiveTenantId = $tenantId ?: $this->tenantId;
        
        $sql = "SELECT 
                    COUNT(*) as total_agenda,
                    COUNT(CASE WHEN is_active = TRUE THEN 1 END) as total_aktif,
                    COUNT(DISTINCT kategori) as total_kategori
                FROM sistem.agenda_sekolah
                WHERE 1=1";
        
        $params = [];
        if (!empty($effectiveTenantId)) {
            if ($effectiveTenantId === 'global') {
                $sql .= " AND tenant_id IS NULL";
            } else {
                $sql .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
                $params[':tenant_id'] = $effectiveTenantId;
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Count for this month
        $sqlMonth = "SELECT COUNT(*) FROM sistem.agenda_sekolah WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)";
        if (!empty($effectiveTenantId)) {
            if ($effectiveTenantId === 'global') {
                $sqlMonth .= " AND tenant_id IS NULL";
            } else {
                $sqlMonth .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
            }
        }
        $stmtMonth = $this->db->prepare($sqlMonth);
        $stmtMonth->execute($params);
        $totalBulanIni = (int)$stmtMonth->fetchColumn();

        return [
            'total_agenda' => (int)($res['total_agenda'] ?? 0),
            'total_aktif' => (int)($res['total_aktif'] ?? 0),
            'total_kategori' => (int)($res['total_kategori'] ?? 0),
            'total_bulan_ini' => $totalBulanIni
        ];
    }

    public function getKategoriList(?string $tenantId = null): array {
        $effectiveTenantId = $tenantId ?: $this->tenantId;
        $sql = "SELECT 
                    kategori as nama_kategori,
                    COUNT(*) as total_agenda
                FROM sistem.agenda_sekolah
                WHERE 1=1";
        $params = [];
        if (!empty($effectiveTenantId)) {
            if ($effectiveTenantId === 'global') {
                $sql .= " AND tenant_id IS NULL";
            } else {
                $sql .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
                $params[':tenant_id'] = $effectiveTenantId;
            }
        }
        $sql .= " GROUP BY kategori ORDER BY total_agenda DESC, kategori ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById(string $id): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, t.nama_sekolah 
            FROM sistem.agenda_sekolah a
            LEFT JOIN core.tenants t ON a.tenant_id = t.id
            WHERE a.id = :id
        ");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->formatRow($row) : null;
    }

    public function create(array $data): string {
        $descPayload = $this->packDescription($data);

        $sql = "INSERT INTO sistem.agenda_sekolah (
                    id, tenant_id, nama_agenda_sekolah, kategori, deskripsi, is_active, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), :tenant_id, :nama_agenda_sekolah, :kategori, :deskripsi, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tenant_id', $data['tenant_id'] ?? null);
        $stmt->bindValue(':nama_agenda_sekolah', $data['nama_agenda_sekolah'] ?? $data['judul'] ?? 'Agenda Tanpa Judul');
        $stmt->bindValue(':kategori', $data['kategori'] ?? 'Umum');
        $stmt->bindValue(':deskripsi', $descPayload);
        $stmt->bindValue(':is_active', isset($data['is_active']) ? (bool)$data['is_active'] : true, PDO::PARAM_BOOL);
        $stmt->execute();

        return (string)$stmt->fetchColumn();
    }

    public function update(string $id, array $data, ?string $tenantId = null): bool {
        $targetTenant = $tenantId ?? $this->tenantId;
        $descPayload = $this->packDescription($data);

        $sql = "UPDATE sistem.agenda_sekolah SET
                    nama_agenda_sekolah = :nama_agenda_sekolah,
                    kategori = :kategori,
                    deskripsi = :deskripsi,
                    is_active = :is_active,
                    tenant_id = :tenant_id,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        if (!empty($targetTenant) && $targetTenant !== 'global') {
            $sql .= " AND (tenant_id = :filter_tenant_id OR tenant_id IS NULL)";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $targetTenant);
        $stmt->bindValue(':nama_agenda_sekolah', $data['nama_agenda_sekolah'] ?? $data['judul'] ?? 'Agenda Tanpa Judul');
        $stmt->bindValue(':kategori', $data['kategori'] ?? 'Umum');
        $stmt->bindValue(':deskripsi', $descPayload);
        $stmt->bindValue(':is_active', isset($data['is_active']) ? (bool)$data['is_active'] : true, PDO::PARAM_BOOL);
        if (!empty($targetTenant) && $targetTenant !== 'global') {
            $stmt->bindValue(':filter_tenant_id', $targetTenant);
        }
        return $stmt->execute();
    }

    public function toggleActive(string $id, ?string $tenantId = null): bool {
        $targetTenant = $tenantId ?? $this->tenantId;
        $sql = "UPDATE sistem.agenda_sekolah SET is_active = NOT is_active, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        if (!empty($targetTenant) && $targetTenant !== 'global') {
            $sql .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
        }
        $sql .= " RETURNING is_active";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        if (!empty($targetTenant) && $targetTenant !== 'global') {
            $stmt->bindValue(':tenant_id', $targetTenant);
        }
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    public function delete(string $id, ?string $tenantId = null): bool {
        $targetTenant = $tenantId ?? $this->tenantId;
        $sql = "DELETE FROM sistem.agenda_sekolah WHERE id = :id";
        if (!empty($targetTenant) && $targetTenant !== 'global') {
            $sql .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        if (!empty($targetTenant) && $targetTenant !== 'global') {
            $stmt->bindValue(':tenant_id', $targetTenant);
        }
        return $stmt->execute();
    }

    private function packDescription(array $data): string {
        $payload = [
            'isi' => trim($data['deskripsi'] ?? ($data['isi'] ?? '')),
            'tanggal_mulai' => $data['tanggal_mulai'] ?? date('Y-m-d'),
            'tanggal_selesai' => $data['tanggal_selesai'] ?? ($data['tanggal_mulai'] ?? date('Y-m-d')),
            'waktu_mulai' => $data['waktu_mulai'] ?? '07:30',
            'waktu_selesai' => $data['waktu_selesai'] ?? '15:00',
            'lokasi' => trim($data['lokasi'] ?? 'Kampus Sekolah'),
            'penanggung_jawab' => trim($data['penanggung_jawab'] ?? 'Waka Humas / Panitia'),
            'visibilitas' => $data['visibilitas'] ?? 'public',
            'target_roles' => $data['target_roles'] ?? []
        ];
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function formatRow(array $row): array {
        $raw = $row['deskripsi'] ?? '';
        $meta = [];
        if (!empty($raw) && (str_starts_with($raw, '{') || str_starts_with($raw, '['))) {
            $parsed = json_decode($raw, true);
            if (is_array($parsed)) {
                $meta = $parsed;
            }
        }

        $tanggalMulai = $meta['tanggal_mulai'] ?? date('Y-m-d', strtotime($row['created_at']));
        $tanggalSelesai = $meta['tanggal_selesai'] ?? $tanggalMulai;
        $waktuMulai = $meta['waktu_mulai'] ?? '08:00';
        $waktuSelesai = $meta['waktu_selesai'] ?? '16:00';
        $lokasi = $meta['lokasi'] ?? 'Kampus Sekolah';
        $pj = $meta['penanggung_jawab'] ?? 'Panitia Acara';
        $visibilitas = $meta['visibilitas'] ?? 'public';
        $targetRoles = $meta['target_roles'] ?? [];
        $isiDeskripsi = $meta['isi'] ?? ($meta['deskripsi'] ?? $raw);

        return [
            'id' => $row['id'],
            'tenant_id' => $row['tenant_id'],
            'nama_sekolah' => $row['nama_sekolah'] ?? null,
            'nama_agenda_sekolah' => $row['nama_agenda_sekolah'],
            'judul' => $row['nama_agenda_sekolah'],
            'kategori' => $row['kategori'] ?: 'Umum',
            'deskripsi' => $isiDeskripsi,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'lokasi' => $lokasi,
            'penanggung_jawab' => $pj,
            'visibilitas' => $visibilitas,
            'target_roles' => $targetRoles,
            'is_active' => (bool)$row['is_active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
}
