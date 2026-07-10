<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class AgendaModel {
    private ?string $tenantId;

    public function __construct(?string $tenantId) {
        $this->tenantId = $tenantId;
    }

    public function getAll(int $limit = 100, int $offset = 0): array {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(a.tenant_id = :tenant_id OR a.tenant_id IS NULL)";
        $sql = "SELECT a.*, u.nama_lengkap as nama_pembuat, r.nama_role as pembuat_role, t.nama_sekolah, k.nama_kategori, k.kode_warna, pic.nama_lengkap as nama_pic 
                FROM agenda_sekolah a 
                JOIN users u ON a.created_by = u.id 
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN tenants t ON a.tenant_id = t.id
                LEFT JOIN kategori_agenda k ON a.kategori_id = k.id
                LEFT JOIN users pic ON a.pic_id = pic.id
                WHERE $whereTenant 
                ORDER BY IFNULL(a.waktu_mulai, a.tanggal_mulai) DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUpcomingAgendas(int $limit = 5): array {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "1=1" : "(a.tenant_id = :tenant_id OR a.tenant_id IS NULL)";
        $sql = "SELECT a.*, k.kode_warna 
                FROM agenda_sekolah a 
                LEFT JOIN kategori_agenda k ON a.kategori_id = k.id
                WHERE $whereTenant AND IFNULL(a.waktu_mulai, a.tanggal_mulai) >= CURDATE()
                ORDER BY IFNULL(a.waktu_mulai, a.tanggal_mulai) ASC 
                LIMIT :limit";
        $stmt = $db->prepare($sql);
        if ($this->tenantId !== null) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveForUser(int $userRoleId): array {
        $db = Database::getConnection();
        
        $whereTenant = ($this->tenantId === null) ? "a.tenant_id IS NULL" : "(a.tenant_id = :tenant_id OR a.tenant_id IS NULL)";
        $sql = "SELECT a.*, u.nama_lengkap as nama_pembuat, r.nama_role as pembuat_role 
                FROM agenda_sekolah a 
                JOIN users u ON a.created_by = u.id 
                JOIN roles r ON u.role_id = r.id
                WHERE $whereTenant
                ORDER BY a.tanggal_mulai ASC, a.waktu ASC";
        $stmt = $db->prepare($sql);
        $params = [];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }
        $stmt->execute($params);
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filtered = [];
        $isAdmin = in_array($userRoleId, [1, 2]);
        $isGuru = in_array($userRoleId, [3, 20, 21]);
        
        foreach ($all as $a) {
            $vis = $a['visibilitas'];
            if ($isAdmin || $vis === 'public') {
                $filtered[] = $a;
            } elseif ($vis === 'guru' && $isGuru) {
                $filtered[] = $a;
            } elseif ($vis === 'private' && !empty($a['target_roles'])) {
                $targets = json_decode($a['target_roles'], true) ?? [];
                if (in_array((string)$userRoleId, $targets) || in_array($userRoleId, $targets)) {
                    $filtered[] = $a;
                }
            }
        }
        
        return $filtered;
    }

    public function findById(string $id): ?array {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "tenant_id IS NULL" : "tenant_id = :tenant_id";
        $stmt = $db->prepare("SELECT * FROM agenda_sekolah WHERE id = :id AND $whereTenant");
        $params = ['id' => $id];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO agenda_sekolah 
            (id, tenant_id, kategori_id, lokasi, pic_id, target_audiens, waktu_mulai, waktu_selesai, created_by, judul, deskripsi, tanggal_mulai, tanggal_selesai, waktu, tipe, status_kegiatan, visibilitas, target_roles, lampiran_file)
            VALUES 
            (UUID(), :tenant_id, :kategori_id, :lokasi, :pic_id, :target_audiens, :waktu_mulai, :waktu_selesai, :created_by, :judul, :deskripsi, :tanggal_mulai, :tanggal_selesai, :waktu, :tipe, :status_kegiatan, :visibilitas, :target_roles, :lampiran_file)
        ");
        return $stmt->execute([
            'tenant_id'      => array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $this->tenantId,
            'kategori_id'    => $data['kategori_id'] ?? null,
            'lokasi'         => $data['lokasi'] ?? null,
            'pic_id'         => $data['pic_id'] ?? null,
            'target_audiens' => $data['target_audiens'] ?? 'Semua',
            'waktu_mulai'    => $data['waktu_mulai'] ?? null,
            'waktu_selesai'  => $data['waktu_selesai'] ?? null,
            'created_by'     => $data['created_by'],
            'judul'          => $data['judul'],
            'deskripsi'      => $data['deskripsi'],
            'tanggal_mulai'  => $data['tanggal_mulai'] ?? null,
            'tanggal_selesai'=> $data['tanggal_selesai'] ?? null,
            'waktu'          => !empty($data['waktu']) ? $data['waktu'] : null,
            'tipe'           => $data['tipe'] ?? 'Agenda Umum',
            'status_kegiatan'=> $data['status_kegiatan'] ?? 'Rencana',
            'visibilitas'    => $data['visibilitas'] ?? 'public',
            'target_roles'   => !empty($data['target_roles']) ? json_encode($data['target_roles']) : null,
            'lampiran_file'  => $data['lampiran_file'] ?? null
        ]);
    }

    public function update(string $id, array $data): bool {
        $db = Database::getConnection();
        
        $setTenantClause = "";
        $params = [
            'kategori_id'    => $data['kategori_id'] ?? null,
            'lokasi'         => $data['lokasi'] ?? null,
            'pic_id'         => $data['pic_id'] ?? null,
            'target_audiens' => $data['target_audiens'] ?? 'Semua',
            'waktu_mulai'    => $data['waktu_mulai'] ?? null,
            'waktu_selesai'  => $data['waktu_selesai'] ?? null,
            'judul'          => $data['judul'],
            'deskripsi'      => $data['deskripsi'],
            'tanggal_mulai'  => $data['tanggal_mulai'] ?? null,
            'tanggal_selesai'=> $data['tanggal_selesai'] ?? null,
            'waktu'          => !empty($data['waktu']) ? $data['waktu'] : null,
            'tipe'           => $data['tipe'] ?? 'Agenda Umum',
            'status_kegiatan'=> $data['status_kegiatan'] ?? 'Rencana',
            'visibilitas'    => $data['visibilitas'] ?? 'public',
            'target_roles'   => !empty($data['target_roles']) ? json_encode($data['target_roles']) : null,
            'lampiran_file'  => $data['lampiran_file'] ?? null,
            'id'             => $id
        ];

        if (array_key_exists('tenant_id', $data)) {
            $setTenantClause = "tenant_id = :set_tenant_id, ";
            $params['set_tenant_id'] = $data['tenant_id'];
        }

        $whereTenant = ($this->tenantId === null) ? "1=1" : "tenant_id = :where_tenant_id";
        if ($this->tenantId !== null) {
            $params['where_tenant_id'] = $this->tenantId;
        }

        $stmt = $db->prepare("
            UPDATE agenda_sekolah SET 
                $setTenantClause
                kategori_id = :kategori_id,
                lokasi = :lokasi,
                pic_id = :pic_id,
                target_audiens = :target_audiens,
                waktu_mulai = :waktu_mulai,
                waktu_selesai = :waktu_selesai,
                judul = :judul, 
                deskripsi = :deskripsi, 
                tanggal_mulai = :tanggal_mulai,
                tanggal_selesai = :tanggal_selesai,
                waktu = :waktu,
                tipe = :tipe,
                status_kegiatan = :status_kegiatan,
                visibilitas = :visibilitas, 
                target_roles = :target_roles, 
                lampiran_file = :lampiran_file
            WHERE id = :id AND $whereTenant
        ");
        
        return $stmt->execute($params);
    }

    public function delete(string $id): bool {
        $db = Database::getConnection();
        $whereTenant = ($this->tenantId === null) ? "tenant_id IS NULL" : "tenant_id = :tenant_id";
        $stmt = $db->prepare("DELETE FROM agenda_sekolah WHERE id = :id AND $whereTenant");
        $params = ['id' => $id];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }
        return $stmt->execute($params);
    }
}
