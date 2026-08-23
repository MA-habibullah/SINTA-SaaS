<?php

namespace App\Modules\Core\Models;

use App\Core\BaseModel;
use PDO;

class TenantModel extends BaseModel {
    protected static string $table = 'core.tenants';
    protected static string $schema = 'core';

    /**
     * Ambil seluruh data sekolah (tenants)
     */
    public function getAllTenants(): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->query("
            SELECT 
                id, 
                nama_sekolah, 
                npsn, 
                subdomain, 
                custom_domain, 
                cname_alias, 
                status, 
                paket_aktif, 
                storage_limit_mb,
                max_siswa_limit,
                max_staff_limit,
                enable_bk,
                enable_tracer,
                status_sinkronisasi, 
                created_at, 
                updated_at
            FROM {$table} 
            ORDER BY nama_sekolah ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Cari sekolah berdasarkan ID
     */
    public static function findById(string $id): ?array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT 
                id, 
                nama_sekolah, 
                npsn, 
                subdomain, 
                custom_domain, 
                status, 
                paket_aktif, 
                storage_limit_mb,
                max_siswa_limit,
                max_staff_limit,
                enable_bk,
                enable_tracer,
                status_sinkronisasi, 
                created_at, 
                updated_at 
            FROM {$table} 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Cari sekolah berdasarkan subdomain
     */
    public static function findBySubdomain(string $subdomain): ?array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare("
            SELECT 
                id, 
                nama_sekolah, 
                npsn, 
                subdomain, 
                custom_domain, 
                status, 
                paket_aktif, 
                storage_limit_mb,
                max_siswa_limit,
                max_staff_limit,
                enable_bk,
                enable_tracer,
                status_sinkronisasi, 
                created_at, 
                updated_at 
            FROM {$table} 
            WHERE subdomain = :subdomain 
            LIMIT 1
        ");
        $stmt->bindValue(':subdomain', $subdomain);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Simpan (Tambah Baru / Update) Sekolah (SaaS Tenant)
     */
    public function saveTenant(array $data): array {
        $pdo = self::getPdo();
        $id = trim($data['id'] ?? '');

        $namaSekolah = trim($data['nama_sekolah'] ?? '');
        $npsn = trim($data['npsn'] ?? '');
        $subdomain = trim($data['subdomain'] ?? '');
        $customDomain = !empty($data['custom_domain']) ? trim($data['custom_domain']) : null;
        $status = !empty($data['status']) ? trim($data['status']) : 'active';
        $paketAktif = !empty($data['paket_aktif']) ? trim($data['paket_aktif']) : 'Premium SaaS';
        $statusSinkronisasi = !empty($data['status_sinkronisasi']) ? trim($data['status_sinkronisasi']) : 'Tersinkronisasi';

        $storageLimitMb = isset($data['storage_limit_mb']) ? (int)$data['storage_limit_mb'] : 1024;
        $maxSiswaLimit  = isset($data['max_siswa_limit']) ? (int)$data['max_siswa_limit'] : 1000;
        $maxStaffLimit  = isset($data['max_staff_limit']) ? (int)$data['max_staff_limit'] : 100;
        $enableBk       = !empty($data['enable_bk']) ? 1 : 0;
        $enableTracer   = !empty($data['enable_tracer']) ? 1 : 0;

        if (empty($namaSekolah) || empty($npsn) || empty($subdomain)) {
            throw new \InvalidArgumentException('Nama Sekolah, NPSN, dan Subdomain wajib diisi.');
        }

        if (!empty($id)) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE core.tenants SET
                    nama_sekolah = :nama_sekolah,
                    npsn = :npsn,
                    subdomain = :subdomain,
                    custom_domain = :custom_domain,
                    status = :status,
                    paket_aktif = :paket_aktif,
                    storage_limit_mb = :storage_limit_mb,
                    max_siswa_limit = :max_siswa_limit,
                    max_staff_limit = :max_staff_limit,
                    enable_bk = :enable_bk,
                    enable_tracer = :enable_tracer,
                    status_sinkronisasi = :status_sinkronisasi,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                'nama_sekolah'        => $namaSekolah,
                'npsn'                => $npsn,
                'subdomain'            => $subdomain,
                'custom_domain'       => $customDomain,
                'status'              => $status,
                'paket_aktif'         => $paketAktif,
                'storage_limit_mb'    => $storageLimitMb,
                'max_siswa_limit'     => $maxSiswaLimit,
                'max_staff_limit'     => $maxStaffLimit,
                'enable_bk'           => $enableBk,
                'enable_tracer'       => $enableTracer,
                'status_sinkronisasi' => $statusSinkronisasi,
                'id'                  => $id
            ]);
            return static::findById($id);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO core.tenants 
                    (id, nama_sekolah, npsn, subdomain, custom_domain, status, paket_aktif, storage_limit_mb, max_siswa_limit, max_staff_limit, enable_bk, enable_tracer, status_sinkronisasi)
                VALUES 
                    (gen_random_uuid(), :nama_sekolah, :npsn, :subdomain, :custom_domain, :status, :paket_aktif, :storage_limit_mb, :max_siswa_limit, :max_staff_limit, :enable_bk, :enable_tracer, :status_sinkronisasi)
                RETURNING id
            ");
            $stmt->execute([
                'nama_sekolah'        => $namaSekolah,
                'npsn'                => $npsn,
                'subdomain'            => $subdomain,
                'custom_domain'       => $customDomain,
                'status'              => $status,
                'paket_aktif'         => $paketAktif,
                'storage_limit_mb'    => $storageLimitMb,
                'max_siswa_limit'     => $maxSiswaLimit,
                'max_staff_limit'     => $maxStaffLimit,
                'enable_bk'           => $enableBk,
                'enable_tracer'       => $enableTracer,
                'status_sinkronisasi' => $statusSinkronisasi,
            ]);
            $newId = $stmt->fetchColumn();
            return static::findById($newId);
        }
    }

    /**
     * Hapus (Nonaktifkan) Sekolah
     */
    public function deleteTenant(string $id): bool {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("UPDATE core.tenants SET status = 'suspended', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Ubah status sekolah (active / suspended)
     */
    public function updateStatus(string $id, string $status): bool {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("UPDATE core.tenants SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
