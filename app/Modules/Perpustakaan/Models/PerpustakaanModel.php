<?php

namespace App\Modules\Perpustakaan\Models;

use App\Config\Database;
use PDO;
use Exception;

class PerpustakaanModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function isValidUuid(?string $uuid): bool {
        if (empty($uuid)) return false;
        return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    public function getDefaultTenantId(): string {
        try {
            $stmt = $this->db->query("SELECT id FROM core.tenants WHERE id != 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' AND status = 'active' ORDER BY nama_sekolah ASC LIMIT 1");
            return (string)($stmt->fetchColumn() ?: '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function resolveTenantId(?string $tenantId): string {
        if (!empty($tenantId) && $this->isValidUuid($tenantId) && $tenantId !== 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
            return (string)$tenantId;
        }
        return $this->getDefaultTenantId();
    }

    // =========================================================================
    // 1. PENGATURAN PERPUSTAKAAN
    // =========================================================================
    public function getPengaturan(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [
                    'nama_perpustakaan' => 'Perpustakaan Digital',
                    'tarif_denda_per_hari' => 500,
                    'max_hari_pinjam_siswa' => 7,
                    'max_hari_pinjam_guru' => 14,
                    'opac_aktif' => 1,
                    'auto_notif_wa_aktif' => 1,
                    'auto_notif_email_aktif' => 0
                ];
            }
            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_pengaturan WHERE tenant_id = :tenant_id LIMIT 1");
            $stmt->execute(['tenant_id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $id = $this->generateUuid();
                $defaultDeskripsi = json_encode([
                    'nomor_pokok' => '',
                    'kepala_perpustakaan' => 'Pustakawan Utama',
                    'nip_kepala' => '',
                    'tarif_denda_per_hari' => 500,
                    'max_hari_pinjam_siswa' => 7,
                    'max_hari_pinjam_guru' => 14,
                    'opac_aktif' => 1,
                    'wa_gateway_url' => '',
                    'wa_gateway_api_key' => '',
                    'auto_notif_wa_aktif' => 1,
                    'auto_notif_email_aktif' => 0
                ]);

                $stmtInit = $this->db->prepare("INSERT INTO perpustakaan.perpus_pengaturan 
                    (id, tenant_id, nama_perpus_pengaturan, kategori, deskripsi, is_active, created_at, updated_at) 
                    VALUES (:id, :tenant_id, 'Perpustakaan Digital', 'Utama', :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $stmtInit->execute([
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'deskripsi' => $defaultDeskripsi
                ]);

                $stmt->execute(['tenant_id' => $tenantId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            }

            return $this->formatPengaturanData($row);
        } catch (\Throwable $e) {
            return [
                'nama_perpustakaan' => 'Perpustakaan Digital',
                'tarif_denda_per_hari' => 500,
                'max_hari_pinjam_siswa' => 7,
                'max_hari_pinjam_guru' => 14,
                'opac_aktif' => 1,
                'auto_notif_wa_aktif' => 1,
                'auto_notif_email_aktif' => 0
            ];
        }
    }

    public function updatePengaturan(string $tenantId, array $data): bool {
        try {
            $existing = $this->getPengaturan($tenantId);
            $merged = array_merge($existing, $data);
            $nama = $merged['nama_perpustakaan'] ?? 'Perpustakaan Digital';
            $deskripsi = json_encode($merged);

            $stmtCheck = $this->db->prepare("SELECT id FROM perpustakaan.perpus_pengaturan WHERE tenant_id = :tenant_id LIMIT 1");
            $stmtCheck->execute(['tenant_id' => $tenantId]);
            $exists = $stmtCheck->fetchColumn();

            if ($exists) {
                $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_pengaturan SET 
                    nama_perpus_pengaturan = :nama,
                    deskripsi = :deskripsi,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE tenant_id = :tenant_id");
                return $stmt->execute([
                    'nama' => $nama,
                    'deskripsi' => $deskripsi,
                    'tenant_id' => $tenantId
                ]);
            } else {
                $newId = $this->generateUuid();
                $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_pengaturan 
                    (id, tenant_id, nama_perpus_pengaturan, kategori, deskripsi, is_active, created_at, updated_at)
                    VALUES (:id, :tenant_id, :nama, 'Utama', :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                return $stmt->execute([
                    'id' => $newId,
                    'tenant_id' => $tenantId,
                    'nama' => $nama,
                    'deskripsi' => $deskripsi
                ]);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function formatPengaturanData(array $row): array {
        $meta = [];
        if (!empty($row['deskripsi'])) {
            $decoded = json_decode($row['deskripsi'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        return array_merge($meta, [
            'id' => $row['id'] ?? null,
            'tenant_id' => $row['tenant_id'] ?? null,
            'nama_perpustakaan' => $row['nama_perpus_pengaturan'] ?? ($meta['nama_perpustakaan'] ?? 'Perpustakaan Digital'),
            'kategori' => $row['kategori'] ?? 'Utama',
            'is_active' => (bool)($row['is_active'] ?? true)
        ]);
    }

    // =========================================================================
    // 2. KATALOG & BIBLIOGRAFI
    // =========================================================================
    public function getBibliografiList(string $tenantId, array $filters = [], int $limit = 50, int $offset = 0): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [];
            }

            $sql = "SELECT b.id, b.tenant_id, b.nama_perpus_bibliografi, b.kategori, b.deskripsi, b.is_active, b.created_at, b.updated_at,
                    t.nama_sekolah as tenant_name
                    FROM perpustakaan.perpus_bibliografi b
                    LEFT JOIN core.tenants t ON b.tenant_id = t.id
                    WHERE b.tenant_id = :tenant_id AND b.is_active = true";

            $params = ['tenant_id' => $tenantId];

            if (!empty($filters['search'])) {
                $sql .= " AND (b.nama_perpus_bibliografi ILIKE :search OR b.kategori ILIKE :search OR b.deskripsi ILIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['kategori'])) {
                $sql .= " AND b.kategori = :kategori";
                $params['kategori'] = $filters['kategori'];
            }

            $sql .= " ORDER BY b.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $result[] = $this->formatBibliografiData($r);
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function countBibliografi(string $tenantId, array $filters = []): int {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return 0;
            }

            $sql = "SELECT COUNT(*) FROM perpustakaan.perpus_bibliografi b WHERE b.tenant_id = :tenant_id AND b.is_active = true";
            $params = ['tenant_id' => $tenantId];

            if (!empty($filters['search'])) {
                $sql .= " AND (b.nama_perpus_bibliografi ILIKE :search OR b.kategori ILIKE :search OR b.deskripsi ILIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['kategori'])) {
                $sql .= " AND b.kategori = :kategori";
                $params['kategori'] = $filters['kategori'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function getBibliografiById(string $tenantId, string $id): ?array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return null;
            }

            $stmt = $this->db->prepare("SELECT b.*, t.nama_sekolah as tenant_name
                FROM perpustakaan.perpus_bibliografi b
                LEFT JOIN core.tenants t ON b.tenant_id = t.id
                WHERE b.id = :id AND b.tenant_id = :tenant_id AND b.is_active = true
                LIMIT 1");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->formatBibliografiData($row) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function saveBibliografi(string $tenantId, array $data, ?string $id = null): string {
        $judul = trim($data['judul'] ?? ($data['nama_perpus_bibliografi'] ?? 'Judul Buku'));
        $kategori = trim($data['kategori'] ?? ($data['jenis_buku'] ?? 'Umum'));

        $meta = [
            'judul' => $judul,
            'isbn' => $data['isbn'] ?? '',
            'penulis' => $data['penulis'] ?? ($data['pengarang'] ?? ''),
            'penerbit' => $data['penerbit'] ?? '',
            'kota_terbit' => $data['kota_terbit'] ?? '',
            'tahun_terbit' => !empty($data['tahun_terbit']) ? (int)$data['tahun_terbit'] : (int)date('Y'),
            'halaman' => !empty($data['halaman']) ? (int)$data['halaman'] : null,
            'dimensi' => $data['dimensi'] ?? '',
            'bahasa' => $data['bahasa'] ?? 'Indonesia',
            'klasifikasi_ddc' => $data['klasifikasi_ddc'] ?? '000',
            'nomor_panggil' => $data['nomor_panggil'] ?? '',
            'subjek' => is_array($data['subjek'] ?? null) ? $data['subjek'] : array_filter(array_map('trim', explode(';', $data['subjek'] ?? ''))),
            'abstrak' => $data['abstrak'] ?? '',
            'jenis_buku' => $data['jenis_buku'] ?? $kategori,
            'status_opac' => isset($data['status_opac']) ? (int)$data['status_opac'] : 1,
            'is_ebook' => isset($data['is_ebook']) ? (int)$data['is_ebook'] : 0,
            'cover' => $data['cover'] ?? '',
            'file_ebook' => $data['file_ebook'] ?? '',
            'total_eksemplar' => isset($data['total_eksemplar']) ? (int)$data['total_eksemplar'] : 1,
            'total_tersedia' => isset($data['total_tersedia']) ? (int)$data['total_tersedia'] : 1,
            'lokasi_rak' => $data['lokasi_rak'] ?? ''
        ];

        $deskripsiJson = json_encode($meta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        if ($id) {
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_bibliografi SET 
                nama_perpus_bibliografi = :nama,
                kategori = :kategori,
                deskripsi = :deskripsi,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute([
                'nama' => $judul,
                'kategori' => $kategori,
                'deskripsi' => $deskripsiJson,
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            return $id;
        } else {
            $newId = $this->generateUuid();
            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_bibliografi 
                (id, tenant_id, nama_perpus_bibliografi, kategori, deskripsi, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :nama, :kategori, :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([
                'id' => $newId,
                'tenant_id' => $tenantId,
                'nama' => $judul,
                'kategori' => $kategori,
                'deskripsi' => $deskripsiJson
            ]);

            // Auto create single exemplar record if needed
            $this->createEksemplarDefault($tenantId, $newId, $judul, $meta['isbn'] ?: 'EKS-' . substr($newId, 0, 8), $meta['lokasi_rak'] ?: 'R-01');
            return $newId;
        }
    }

    public function deleteBibliografi(string $tenantId, string $id): bool {
        try {
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_bibliografi SET is_active = false, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id");
            return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function createEksemplarDefault(string $tenantId, string $bibliografiId, string $judul, string $barcode, string $rak): void {
        try {
            $eksId = $this->generateUuid();
            $meta = [
                'bibliografi_id' => $bibliografiId,
                'barcode' => $barcode,
                'no_induk' => 'IND-' . date('Y') . '-' . substr($eksId, 0, 6),
                'lokasi_rak' => $rak,
                'status' => 'Tersedia',
                'sumber_dana' => 'BOS / Hibah',
                'harga' => 0
            ];
            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_eksemplar 
                (id, tenant_id, nama_perpus_eksemplar, kategori, deskripsi, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :nama, 'Fisik', :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([
                'id' => $eksId,
                'tenant_id' => $tenantId,
                'nama' => $judul . ' (Eksemplar 1)',
                'deskripsi' => json_encode($meta)
            ]);
        } catch (\Throwable $e) {}
    }

    private function formatBibliografiData(array $r): array {
        $meta = [];
        if (!empty($r['deskripsi'])) {
            $decoded = json_decode($r['deskripsi'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $penulis = $meta['penulis'] ?? ($meta['pengarang'] ?? '-');
        if (is_array($penulis)) {
            $penulis = implode(', ', $penulis);
        }

        return array_merge($meta, [
            'id' => $r['id'] ?? null,
            'tenant_id' => $r['tenant_id'] ?? null,
            'tenant_name' => $r['tenant_name'] ?? 'Sekolah Aktif',
            'judul' => $r['nama_perpus_bibliografi'] ?? ($meta['judul'] ?? 'Tanpa Judul'),
            'kategori' => $r['kategori'] ?? ($meta['jenis_buku'] ?? 'Umum'),
            'penulis' => $penulis,
            'pengarang' => $penulis,
            'penerbit' => $meta['penerbit'] ?? '-',
            'tahun_terbit' => $meta['tahun_terbit'] ?? date('Y'),
            'isbn' => $meta['isbn'] ?? '-',
            'klasifikasi_ddc' => $meta['klasifikasi_ddc'] ?? '000',
            'nomor_panggil' => $meta['nomor_panggil'] ?? ($meta['klasifikasi_ddc'] ?? '000'),
            'total_eksemplar' => (int)($meta['total_eksemplar'] ?? 1),
            'total_tersedia' => (int)($meta['total_tersedia'] ?? 1),
            'is_ebook' => (int)($meta['is_ebook'] ?? 0),
            'status_opac' => (int)($meta['status_opac'] ?? 1),
            'cover' => $meta['cover'] ?? '',
            'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }

    // =========================================================================
    // 3. ANGGOTA PERPUSTAKAAN (AUTO-FEDERATION: SISWA, GURU, TENDIK, UMUM)
    // =========================================================================
    public function getAnggotaList(string $tenantId, array $filters = [], int $limit = 50, int $offset = 0): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [];
            }

            $search = trim($filters['search'] ?? '');
            $kategori = trim($filters['kategori'] ?? '');
            $kelas = trim($filters['kelas'] ?? '');

            $params = [
                'tid1' => $tenantId,
                'tid2' => $tenantId,
                'tid3' => $tenantId,
            ];

            $whereClause = "WHERE 1=1";
            if (!empty($search)) {
                $whereClause .= " AND (u.nama_lengkap ILIKE :search OR u.no_anggota ILIKE :search OR u.nisn ILIKE :search OR u.nip ILIKE :search OR u.nama_kelas ILIKE :search)";
                $params['search'] = '%' . $search . '%';
            }

            if (!empty($kategori)) {
                $whereClause .= " AND u.kategori = :kategori";
                $params['kategori'] = $kategori;
            }

            if (!empty($kelas)) {
                $whereClause .= " AND u.nama_kelas ILIKE :kelas";
                $params['kelas'] = '%' . $kelas . '%';
            }

            $sql = "
                WITH unified_members AS (
                    -- 1. SISWA AKTIF DARI siswa.siswa
                    SELECT 
                        s.id::text as id,
                        s.tenant_id::text as tenant_id,
                        s.nama_lengkap as nama_lengkap,
                        'Siswa' as kategori,
                        'Siswa' as tipe_anggota,
                        COALESCE('SIS-' || NULLIF(TRIM(s.nisn), ''), 'SIS-' || NULLIF(TRIM(s.nis), ''), 'SIS-' || SUBSTRING(s.id::text, 1, 8)) as no_anggota,
                        COALESCE(NULLIF(TRIM(s.nisn), ''), '-') as nisn,
                        COALESCE(NULLIF(TRIM(s.nis), ''), '-') as nip,
                        COALESCE(k.nama_kelas, NULLIF(TRIM(s.kelas_saat_ini), ''), 'Kelas Reguler') as nama_kelas,
                        COALESCE(NULLIF(TRIM(s.no_hp), ''), '-') as kontak,
                        COALESCE(NULLIF(TRIM(s.alamat), ''), '-') as alamat,
                        COALESCE(s.foto_url, '') as foto,
                        s.created_at
                    FROM siswa.siswa s
                    LEFT JOIN akademik.kelas k ON (s.kelas_saat_ini = k.id::text OR s.kelas_saat_ini = k.nama_kelas)
                    WHERE s.tenant_id = :tid1 AND (s.is_active = true OR s.is_active IS NULL)

                    UNION ALL

                    -- 2. GURU & KARYAWAN / TENDIK DARI core.users
                    SELECT 
                        u.id::text as id,
                        u.tenant_id::text as tenant_id,
                        u.nama_lengkap as nama_lengkap,
                        CASE WHEN r.nama_role = 'guru' THEN 'Guru' ELSE 'Tendik' END as kategori,
                        CASE WHEN r.nama_role = 'guru' THEN 'Guru' ELSE 'Tendik' END as tipe_anggota,
                        CASE 
                            WHEN r.nama_role = 'guru' THEN 'GUR-' || u.username
                            ELSE 'STF-' || u.username
                        END as no_anggota,
                        '-' as nisn,
                        u.username as nip,
                        CASE 
                            WHEN r.nama_role = 'guru' THEN 'Dewan Guru'
                            ELSE 'Tenaga Kependidikan / ' || INITCAP(REPLACE(COALESCE(r.nama_role, 'Staf'), '_', ' '))
                        END as nama_kelas,
                        COALESCE(u.email, '-') as kontak,
                        '-' as alamat,
                        '' as foto,
                        u.created_at
                    FROM core.users u
                    LEFT JOIN core.roles r ON u.role_id = r.id
                    WHERE u.tenant_id = :tid2 
                      AND u.is_active = true 
                      AND r.nama_role NOT IN ('super_admin', 'siswa')

                    UNION ALL

                    -- 3. ANGGOTA MANUAL / UMUM DARI perpustakaan.perpus_anggota
                    SELECT 
                        a.id::text as id,
                        a.tenant_id::text as tenant_id,
                        a.nama_perpus_anggota as nama_lengkap,
                        a.kategori as kategori,
                        a.kategori as tipe_anggota,
                        COALESCE(a.deskripsi::json->>'no_anggota', 'LIB-' || SUBSTRING(a.id::text, 1, 8)) as no_anggota,
                        COALESCE(a.deskripsi::json->>'nisn', '-') as nisn,
                        COALESCE(a.deskripsi::json->>'nip', '-') as nip,
                        COALESCE(a.deskripsi::json->>'nama_kelas', 'Umum / Tamu') as nama_kelas,
                        COALESCE(a.deskripsi::json->>'kontak', '-') as kontak,
                        COALESCE(a.deskripsi::json->>'alamat', '-') as alamat,
                        COALESCE(a.deskripsi::json->>'foto', '') as foto,
                        a.created_at
                    FROM perpustakaan.perpus_anggota a
                    WHERE a.tenant_id = :tid3 AND a.is_active = true
                )
                SELECT u.*,
                    COALESCE((
                        SELECT COUNT(*) 
                        FROM perpustakaan.perpus_sirkulasi s 
                        WHERE s.tenant_id = :tid1 
                          AND s.is_active = true 
                          AND (s.kategori = 'Dipinjam' OR s.deskripsi::json->>'status' = 'Dipinjam')
                          AND (
                              s.deskripsi::json->>'anggota_id' = u.id 
                              OR s.deskripsi::json->>'anggota_id' = u.no_anggota 
                              OR s.deskripsi::json->>'anggota_id' = u.nisn 
                              OR s.deskripsi::json->>'anggota_id' = u.nip
                          )
                    ), 0) as pinjam_aktif,
                    COALESCE((
                        SELECT SUM(COALESCE((s.deskripsi::json->>'denda')::numeric, 0)) 
                        FROM perpustakaan.perpus_sirkulasi s 
                        WHERE s.tenant_id = :tid1 
                          AND s.is_active = true 
                          AND (
                              s.deskripsi::json->>'anggota_id' = u.id 
                              OR s.deskripsi::json->>'anggota_id' = u.no_anggota 
                              OR s.deskripsi::json->>'anggota_id' = u.nisn 
                              OR s.deskripsi::json->>'anggota_id' = u.nip
                          )
                    ), 0) as total_denda
                FROM unified_members u
                {$whereClause}
                ORDER BY u.kategori ASC, u.nama_lengkap ASC 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $pinjamAktif = (int)($r['pinjam_aktif'] ?? 0);
                $totalDenda = (float)($r['total_denda'] ?? 0.0);
                $statusBebas = ($pinjamAktif === 0 && $totalDenda <= 0) ? 1 : 0;

                $result[] = [
                    'id' => $r['id'],
                    'tenant_id' => $r['tenant_id'],
                    'nama_lengkap' => $r['nama_lengkap'],
                    'kategori' => $r['kategori'],
                    'tipe_anggota' => $r['tipe_anggota'],
                    'no_anggota' => $r['no_anggota'],
                    'nisn' => $r['nisn'],
                    'nip' => $r['nip'],
                    'nama_kelas' => $r['nama_kelas'],
                    'kontak' => $r['kontak'],
                    'alamat' => $r['alamat'],
                    'foto' => $r['foto'],
                    'pinjam_aktif' => $pinjamAktif,
                    'total_denda' => $totalDenda,
                    'status_bebas_pustaka' => $statusBebas,
                    'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function countAnggota(string $tenantId, array $filters = []): int {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return 0;
            }

            $search = trim($filters['search'] ?? '');
            $kategori = trim($filters['kategori'] ?? '');
            $kelas = trim($filters['kelas'] ?? '');

            $params = [
                'tid1' => $tenantId,
                'tid2' => $tenantId,
                'tid3' => $tenantId,
            ];

            $whereClause = "WHERE 1=1";
            if (!empty($search)) {
                $whereClause .= " AND (u.nama_lengkap ILIKE :search OR u.no_anggota ILIKE :search OR u.nisn ILIKE :search OR u.nip ILIKE :search OR u.nama_kelas ILIKE :search)";
                $params['search'] = '%' . $search . '%';
            }

            if (!empty($kategori)) {
                $whereClause .= " AND u.kategori = :kategori";
                $params['kategori'] = $kategori;
            }

            if (!empty($kelas)) {
                $whereClause .= " AND u.nama_kelas ILIKE :kelas";
                $params['kelas'] = '%' . $kelas . '%';
            }

            $sql = "
                WITH unified_members AS (
                    SELECT s.id::text as id, s.tenant_id::text as tenant_id, s.nama_lengkap, 'Siswa' as kategori,
                        COALESCE('SIS-' || NULLIF(TRIM(s.nisn), ''), 'SIS-' || NULLIF(TRIM(s.nis), ''), 'SIS-' || SUBSTRING(s.id::text, 1, 8)) as no_anggota,
                        COALESCE(NULLIF(TRIM(s.nisn), ''), '-') as nisn, COALESCE(NULLIF(TRIM(s.nis), ''), '-') as nip,
                        COALESCE(k.nama_kelas, NULLIF(TRIM(s.kelas_saat_ini), ''), 'Kelas Reguler') as nama_kelas
                    FROM siswa.siswa s
                    LEFT JOIN akademik.kelas k ON (s.kelas_saat_ini = k.id::text OR s.kelas_saat_ini = k.nama_kelas)
                    WHERE s.tenant_id = :tid1 AND (s.is_active = true OR s.is_active IS NULL)

                    UNION ALL

                    SELECT u.id::text as id, u.tenant_id::text as tenant_id, u.nama_lengkap,
                        CASE WHEN r.nama_role = 'guru' THEN 'Guru' ELSE 'Tendik' END as kategori,
                        CASE WHEN r.nama_role = 'guru' THEN 'GUR-' || u.username ELSE 'STF-' || u.username END as no_anggota,
                        '-' as nisn, u.username as nip,
                        CASE WHEN r.nama_role = 'guru' THEN 'Dewan Guru' ELSE 'Tenaga Kependidikan' END as nama_kelas
                    FROM core.users u
                    LEFT JOIN core.roles r ON u.role_id = r.id
                    WHERE u.tenant_id = :tid2 AND u.is_active = true AND r.nama_role NOT IN ('super_admin', 'siswa')

                    UNION ALL

                    SELECT a.id::text as id, a.tenant_id::text as tenant_id, a.nama_perpus_anggota as nama_lengkap, a.kategori,
                        COALESCE(a.deskripsi::json->>'no_anggota', 'LIB-' || SUBSTRING(a.id::text, 1, 8)) as no_anggota,
                        COALESCE(a.deskripsi::json->>'nisn', '-') as nisn, COALESCE(a.deskripsi::json->>'nip', '-') as nip,
                        COALESCE(a.deskripsi::json->>'nama_kelas', 'Umum / Tamu') as nama_kelas
                    FROM perpustakaan.perpus_anggota a
                    WHERE a.tenant_id = :tid3 AND a.is_active = true
                )
                SELECT COUNT(*) FROM unified_members u {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function findMemberByIdentifier(string $tenantId, string $identifier): ?array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $identifier = trim($identifier);
            if (empty($identifier)) return null;

            $sql = "
                WITH unified_members AS (
                    SELECT 
                        s.id::text as id,
                        s.tenant_id::text as tenant_id,
                        s.nama_lengkap as nama_lengkap,
                        'Siswa' as kategori,
                        'Siswa' as tipe_anggota,
                        COALESCE('SIS-' || NULLIF(TRIM(s.nisn), ''), 'SIS-' || NULLIF(TRIM(s.nis), ''), 'SIS-' || SUBSTRING(s.id::text, 1, 8)) as no_anggota,
                        COALESCE(NULLIF(TRIM(s.nisn), ''), '-') as nisn,
                        COALESCE(NULLIF(TRIM(s.nis), ''), '-') as nip,
                        COALESCE(k.nama_kelas, NULLIF(TRIM(s.kelas_saat_ini), ''), 'Kelas Reguler') as nama_kelas,
                        COALESCE(NULLIF(TRIM(s.no_hp), ''), '-') as kontak,
                        COALESCE(NULLIF(TRIM(s.alamat), ''), '-') as alamat,
                        COALESCE(s.foto_url, '') as foto,
                        s.created_at
                    FROM siswa.siswa s
                    LEFT JOIN akademik.kelas k ON (s.kelas_saat_ini = k.id::text OR s.kelas_saat_ini = k.nama_kelas)
                    WHERE s.tenant_id = :tid1 AND (s.is_active = true OR s.is_active IS NULL)

                    UNION ALL

                    SELECT 
                        u.id::text as id,
                        u.tenant_id::text as tenant_id,
                        u.nama_lengkap as nama_lengkap,
                        CASE WHEN r.nama_role = 'guru' THEN 'Guru' ELSE 'Tendik' END as kategori,
                        CASE WHEN r.nama_role = 'guru' THEN 'Guru' ELSE 'Tendik' END as tipe_anggota,
                        CASE WHEN r.nama_role = 'guru' THEN 'GUR-' || u.username ELSE 'STF-' || u.username END as no_anggota,
                        '-' as nisn,
                        u.username as nip,
                        CASE WHEN r.nama_role = 'guru' THEN 'Dewan Guru' ELSE 'Tenaga Kependidikan' END as nama_kelas,
                        COALESCE(u.email, '-') as kontak,
                        '-' as alamat,
                        '' as foto,
                        u.created_at
                    FROM core.users u
                    LEFT JOIN core.roles r ON u.role_id = r.id
                    WHERE u.tenant_id = :tid2 AND u.is_active = true AND r.nama_role NOT IN ('super_admin', 'siswa')

                    UNION ALL

                    SELECT 
                        a.id::text as id,
                        a.tenant_id::text as tenant_id,
                        a.nama_perpus_anggota as nama_lengkap,
                        a.kategori as kategori,
                        a.kategori as tipe_anggota,
                        COALESCE(a.deskripsi::json->>'no_anggota', 'LIB-' || SUBSTRING(a.id::text, 1, 8)) as no_anggota,
                        COALESCE(a.deskripsi::json->>'nisn', '-') as nisn,
                        COALESCE(a.deskripsi::json->>'nip', '-') as nip,
                        COALESCE(a.deskripsi::json->>'nama_kelas', 'Umum / Tamu') as nama_kelas,
                        COALESCE(a.deskripsi::json->>'kontak', '-') as kontak,
                        COALESCE(a.deskripsi::json->>'alamat', '-') as alamat,
                        COALESCE(a.deskripsi::json->>'foto', '') as foto,
                        a.created_at
                    FROM perpustakaan.perpus_anggota a
                    WHERE a.tenant_id = :tid3 AND a.is_active = true
                )
                SELECT * FROM unified_members 
                WHERE (nisn = :q OR nip = :q OR no_anggota = :q OR id = :q OR nama_lengkap ILIKE :q_like)
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'tid1' => $tenantId,
                'tid2' => $tenantId,
                'tid3' => $tenantId,
                'q' => $identifier,
                'q_like' => '%' . $identifier . '%'
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function checkBebasPustaka(string $tenantId, string $identifier): array {
        try {
            $member = $this->findMemberByIdentifier($tenantId, $identifier);
            if (!$member) {
                return [
                    'success' => false,
                    'message' => 'Anggota dengan NISN/NIP/Nama "' . $identifier . '" tidak ditemukan.'
                ];
            }

            // Ambil semua transaksi sirkulasi terkait anggota ini
            $stmt = $this->db->prepare("
                SELECT id, nama_perpus_sirkulasi, kategori, deskripsi, created_at 
                FROM perpustakaan.perpus_sirkulasi 
                WHERE tenant_id = :tenant_id 
                  AND is_active = true
                  AND (
                      deskripsi::json->>'anggota_id' = :id 
                      OR deskripsi::json->>'anggota_id' = :no_anggota 
                      OR deskripsi::json->>'anggota_id' = :nisn 
                      OR deskripsi::json->>'anggota_id' = :nip
                      OR deskripsi::json->>'nama_anggota' ILIKE :nama
                  )
                ORDER BY created_at DESC
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'id' => $member['id'],
                'no_anggota' => $member['no_anggota'],
                'nisn' => $member['nisn'],
                'nip' => $member['nip'],
                'nama' => '%' . $member['nama_lengkap'] . '%'
            ]);
            $circRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $pinjamanAktif = [];
            $riwayatPinjam = [];
            $totalDenda = 0.0;

            foreach ($circRows as $r) {
                $meta = json_decode($r['deskripsi'] ?? '{}', true) ?: [];
                $status = $meta['status'] ?? $r['kategori'];
                $denda = (float)($meta['denda'] ?? 0.0);
                $totalDenda += $denda;

                $item = [
                    'id' => $r['id'],
                    'barcode' => $meta['barcode'] ?? '-',
                    'judul_buku' => $meta['judul_buku'] ?? ($r['nama_perpus_sirkulasi'] ?? 'Buku'),
                    'tgl_pinjam' => $meta['tgl_pinjam'] ?? substr($r['created_at'], 0, 10),
                    'tgl_harus_kembali' => $meta['tgl_harus_kembali'] ?? '-',
                    'tgl_kembali' => $meta['tgl_kembali'] ?? null,
                    'status' => $status,
                    'denda' => $denda
                ];

                if ($status === 'Dipinjam' || empty($meta['tgl_kembali'])) {
                    $pinjamanAktif[] = $item;
                } else {
                    $riwayatPinjam[] = $item;
                }
            }

            $isClear = (count($pinjamanAktif) === 0 && $totalDenda <= 0);

            return [
                'success' => true,
                'is_clear' => $isClear,
                'status_label' => $isClear ? 'BEBAS PUSTAKA (CLEAR)' : 'MASIH ADA TANGGUNGAN',
                'member' => $member,
                'pinjaman_aktif' => $pinjamanAktif,
                'riwayat_pinjam' => $riwayatPinjam,
                'total_pinjam_aktif' => count($pinjamanAktif),
                'total_denda_tertunggak' => $totalDenda,
                'nomor_surat' => '421.3/' . rand(100, 999) . '/PERPUS/' . date('Y'),
                'tanggal_terbit' => date('d F Y')
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal verifikasi bebas pustaka: ' . $e->getMessage()
            ];
        }
    }

    public function saveAnggota(string $tenantId, array $data, ?string $id = null): string {
        $nama = trim($data['nama_lengkap'] ?? ($data['nama_perpus_anggota'] ?? 'Nama Anggota'));
        $kategori = trim($data['tipe_anggota'] ?? ($data['kategori'] ?? 'Umum'));
        $noAnggota = trim($data['no_anggota'] ?? ($data['nomor_anggota'] ?? ('LIB-' . date('Y') . '-' . rand(1000, 9999))));

        $meta = [
            'no_anggota' => $noAnggota,
            'nisn' => $data['nisn'] ?? ($data['nip'] ?? ''),
            'nip' => $data['nip'] ?? '',
            'nama_lengkap' => $nama,
            'tipe_anggota' => $kategori,
            'nama_kelas' => $data['nama_kelas'] ?? ($data['kelas'] ?? 'Umum / Tamu'),
            'jenis_kelamin' => $data['jenis_kelamin'] ?? 'L',
            'kontak' => $data['kontak'] ?? ($data['no_wa'] ?? ''),
            'alamat' => $data['alamat'] ?? '',
            'pinjam_aktif' => 0,
            'total_denda' => 0.0,
            'status_bebas_pustaka' => 1,
            'foto' => $data['foto'] ?? ''
        ];

        $deskripsiJson = json_encode($meta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        if ($id) {
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_anggota SET 
                nama_perpus_anggota = :nama,
                kategori = :kategori,
                deskripsi = :deskripsi,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute([
                'nama' => $nama,
                'kategori' => $kategori,
                'deskripsi' => $deskripsiJson,
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            return $id;
        } else {
            $newId = $this->generateUuid();
            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_anggota 
                (id, tenant_id, nama_perpus_anggota, kategori, deskripsi, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :nama, :kategori, :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([
                'id' => $newId,
                'tenant_id' => $tenantId,
                'nama' => $nama,
                'kategori' => $kategori,
                'deskripsi' => $deskripsiJson
            ]);
            return $newId;
        }
    }

    public function deleteAnggota(string $tenantId, string $id): bool {
        try {
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_anggota SET is_active = false, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id");
            return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getVisitorStats(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $tglHariIni = date('Y-m-d');
            $tglAwalBulan = date('Y-m-01');

            $stmtHari = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_buku_tamu WHERE tenant_id = :tenant_id AND (is_active = true OR is_active IS NULL) AND created_at::date = :tgl");
            $stmtHari->execute(['tenant_id' => $tenantId, 'tgl' => $tglHariIni]);
            $totalHariIni = (int)$stmtHari->fetchColumn();

            $stmtBulan = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_buku_tamu WHERE tenant_id = :tenant_id AND (is_active = true OR is_active IS NULL) AND created_at::date >= :tgl");
            $stmtBulan->execute(['tenant_id' => $tenantId, 'tgl' => $tglAwalBulan]);
            $totalBulanIni = (int)$stmtBulan->fetchColumn();

            $stmtSiswa = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_buku_tamu WHERE tenant_id = :tenant_id AND (is_active = true OR is_active IS NULL) AND (LOWER(kategori) = 'siswa' OR deskripsi ILIKE '%\"tipe\":\"Siswa\"%')");
            $stmtSiswa->execute(['tenant_id' => $tenantId]);
            $totalSiswa = (int)$stmtSiswa->fetchColumn();

            $stmtGuru = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_buku_tamu WHERE tenant_id = :tenant_id AND (is_active = true OR is_active IS NULL) AND (LOWER(kategori) IN ('guru', 'tendik', 'ptk', 'staf') OR deskripsi ILIKE '%\"tipe\":\"Guru\"%' OR deskripsi ILIKE '%\"tipe\":\"Tendik\"%')");
            $stmtGuru->execute(['tenant_id' => $tenantId]);
            $totalGuru = (int)$stmtGuru->fetchColumn();

            return [
                'total_hari_ini' => $totalHariIni,
                'total_bulan_ini' => $totalBulanIni,
                'total_siswa' => $totalSiswa,
                'total_guru_staf' => $totalGuru
            ];
        } catch (\Throwable $e) {
            return [
                'total_hari_ini' => 0,
                'total_bulan_ini' => 0,
                'total_siswa' => 0,
                'total_guru_staf' => 0
            ];
        }
    }

    // =========================================================================
    // 4. SIRKULASI (PINJAM, KEMBALI, PERPANJANG)
    // =========================================================================
    public function getSirkulasiList(string $tenantId, array $filters = [], int $limit = 50, int $offset = 0): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [];
            }

            $sql = "SELECT s.id, s.tenant_id, s.nama_perpus_sirkulasi, s.kategori, s.deskripsi, s.is_active, s.created_at,
                    t.nama_sekolah as tenant_name
                    FROM perpustakaan.perpus_sirkulasi s
                    LEFT JOIN core.tenants t ON s.tenant_id = t.id
                    WHERE s.tenant_id = :tenant_id AND s.is_active = true";

            $params = ['tenant_id' => $tenantId];

            if (!empty($filters['status'])) {
                $sql .= " AND s.kategori = :status";
                $params['status'] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (s.nama_perpus_sirkulasi ILIKE :search OR s.deskripsi ILIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }

            $sql .= " ORDER BY s.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $result[] = $this->formatSirkulasiData($r);
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function prosesPeminjaman(string $tenantId, array $data): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $anggotaId = trim($data['anggota_id'] ?? '');
            $barcode = trim($data['eksemplar_id'] ?? ($data['barcode'] ?? ''));
            $durasiHari = (int)($data['durasi_hari'] ?? 7);

            if (empty($anggotaId) || empty($barcode)) {
                return ['success' => false, 'message' => 'Nomor Anggota/NISN/NIP dan Barcode Buku wajib diisi!'];
            }

            // Auto-resolve member jika nama belum ada
            $namaAnggota = trim($data['nama_anggota'] ?? '');
            $tipeAnggota = 'Siswa';
            $member = $this->findMemberByIdentifier($tenantId, $anggotaId);
            if ($member) {
                $namaAnggota = $member['nama_lengkap'];
                $tipeAnggota = $member['kategori'];
                // Standardize anggota_id to no_anggota or identifier
                if (empty($data['anggota_id_raw'])) {
                    $anggotaId = $member['no_anggota'];
                }
            }
            if (empty($namaAnggota)) {
                $namaAnggota = 'Anggota (' . $anggotaId . ')';
            }

            // Auto-resolve book title jika belum ada
            $judulBuku = trim($data['judul_buku'] ?? '');
            if (empty($judulBuku)) {
                $buku = $this->getKatalogDetailByBarcode($tenantId, $barcode);
                if ($buku && !empty($buku['judul'])) {
                    $judulBuku = $buku['judul'];
                } else {
                    $judulBuku = 'Buku Referensi (' . $barcode . ')';
                }
            }

            $id = $this->generateUuid();
            $tglPinjam = date('Y-m-d');
            $tglHarusKembali = date('Y-m-d', strtotime("+{$durasiHari} days"));

            $meta = [
                'anggota_id' => $anggotaId,
                'nama_anggota' => $namaAnggota,
                'tipe_anggota' => $tipeAnggota,
                'barcode' => $barcode,
                'judul_buku' => $judulBuku,
                'tgl_pinjam' => $tglPinjam,
                'tgl_harus_kembali' => $tglHarusKembali,
                'durasi_hari' => $durasiHari,
                'status' => 'Dipinjam',
                'denda' => 0
            ];

            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_sirkulasi
                (id, tenant_id, nama_perpus_sirkulasi, kategori, deskripsi, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :nama, 'Dipinjam', :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([
                'id' => $id,
                'tenant_id' => $tenantId,
                'nama' => 'Peminjaman ' . $barcode . ' oleh ' . $namaAnggota,
                'deskripsi' => json_encode($meta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            ]);

            return ['success' => true, 'id' => $id, 'message' => 'Peminjaman berhasil dicatat untuk ' . $namaAnggota . '!'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Gagal memproses peminjaman: ' . $e->getMessage()];
        }
    }

    public function prosesPengembalian(string $tenantId, string $sirkulasiId, string $kondisi = 'Baik'): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_sirkulasi WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
            $stmt->execute(['id' => $sirkulasiId, 'tenant_id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return ['success' => false, 'message' => 'Data transaksi sirkulasi tidak ditemukan!'];
            }

            $meta = json_decode($row['deskripsi'] ?? '{}', true) ?: [];
            $tglHarusKembali = $meta['tgl_harus_kembali'] ?? date('Y-m-d');
            $tglKembali = date('Y-m-d');

            // Hitung denda jika terlambat
            $hariTelat = max(0, (strtotime($tglKembali) - strtotime($tglHarusKembali)) / 86400);
            $pengaturan = $this->getPengaturan($tenantId);
            $tarifDenda = (float)($pengaturan['tarif_denda_per_hari'] ?? 500);
            $dendaTelat = $hariTelat * $tarifDenda;

            $dendaKondisi = 0;
            if ($kondisi === 'Rusak') $dendaKondisi = 20000;
            if ($kondisi === 'Hilang') $dendaKondisi = 50000;

            $totalDenda = $dendaTelat + $dendaKondisi;

            $meta['tgl_kembali'] = $tglKembali;
            $meta['status'] = 'Dikembalikan';
            $meta['kondisi'] = $kondisi;
            $meta['hari_terlambat'] = $hariTelat;
            $meta['total_denda'] = $totalDenda;

            $stmtUpdate = $this->db->prepare("UPDATE perpustakaan.perpus_sirkulasi SET 
                kategori = 'Dikembalikan',
                deskripsi = :deskripsi,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id");
            $stmtUpdate->execute([
                'deskripsi' => json_encode($meta),
                'id' => $sirkulasiId,
                'tenant_id' => $tenantId
            ]);

            // If denda exists, record in perpus_denda
            if ($totalDenda > 0) {
                $dendaId = $this->generateUuid();
                $dendaMeta = [
                    'sirkulasi_id' => $sirkulasiId,
                    'anggota_id' => $meta['anggota_id'] ?? '',
                    'nama_anggota' => $meta['nama_anggota'] ?? '',
                    'nominal' => $totalDenda,
                    'keterangan' => 'Denda keterlambatan ' . $hariTelat . ' hari + kondisi ' . $kondisi,
                    'status_bayar' => 'Belum Lunas',
                    'tanggal' => $tglKembali
                ];
                $stmtDenda = $this->db->prepare("INSERT INTO perpustakaan.perpus_denda 
                    (id, tenant_id, nama_perpus_denda, kategori, deskripsi, is_active, created_at, updated_at)
                    VALUES (:id, :tenant_id, :nama, 'Belum Lunas', :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $stmtDenda->execute([
                    'id' => $dendaId,
                    'tenant_id' => $tenantId,
                    'nama' => 'Denda ' . ($meta['nama_anggota'] ?? 'Anggota'),
                    'deskripsi' => json_encode($dendaMeta)
                ]);
            }

            return ['success' => true, 'denda' => $totalDenda, 'message' => 'Pengembalian buku berhasil diproses!'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Gagal memproses pengembalian: ' . $e->getMessage()];
        }
    }

    private function formatSirkulasiData(array $r): array {
        $meta = [];
        if (!empty($r['deskripsi'])) {
            $decoded = json_decode($r['deskripsi'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        return array_merge($meta, [
            'id' => $r['id'] ?? null,
            'tenant_id' => $r['tenant_id'] ?? null,
            'tenant_name' => $r['tenant_name'] ?? 'Sekolah Aktif',
            'status' => $r['kategori'] ?? ($meta['status'] ?? 'Dipinjam'),
            'judul_buku' => $meta['judul_buku'] ?? ($r['nama_perpus_sirkulasi'] ?? '-'),
            'nama_anggota' => $meta['nama_anggota'] ?? '-',
            'anggota_id' => $meta['anggota_id'] ?? '-',
            'barcode' => $meta['barcode'] ?? '-',
            'tgl_pinjam' => $meta['tgl_pinjam'] ?? date('Y-m-d'),
            'tgl_harus_kembali' => $meta['tgl_harus_kembali'] ?? date('Y-m-d', strtotime('+7 days')),
            'tgl_kembali' => $meta['tgl_kembali'] ?? null,
            'total_denda' => (float)($meta['total_denda'] ?? 0.0),
            'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }

    // =========================================================================
    // 5. OPAC & PENCARIAN PUBLIK
    // =========================================================================
    public function searchOpacPublic(string $query = '', string $kategori = '', int $limit = 40): array {
        try {
            $sql = "SELECT b.id, b.tenant_id, b.nama_perpus_bibliografi, b.kategori, b.deskripsi, b.created_at,
                    t.nama_sekolah as tenant_name
                    FROM perpustakaan.perpus_bibliografi b
                    LEFT JOIN core.tenants t ON b.tenant_id = t.id
                    WHERE b.is_active = true";

            $params = [];

            if (!empty($query)) {
                $sql .= " AND (b.nama_perpus_bibliografi ILIKE :q OR b.kategori ILIKE :q OR b.deskripsi ILIKE :q)";
                $params['q'] = '%' . $query . '%';
            }

            if (!empty($kategori)) {
                $sql .= " AND b.kategori = :kat";
                $params['kat'] = $kategori;
            }

            $sql .= " ORDER BY b.created_at DESC LIMIT " . (int)$limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $item = $this->formatBibliografiData($r);
                if (!empty($item['status_opac'])) {
                    $result[] = $item;
                }
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    // =========================================================================
    // 6. DASHBOARD, RAK, DDC, USULAN, SERIAL
    // =========================================================================
    public function getDashboardSummary(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [
                    'total_judul' => 0,
                    'total_eksemplar' => 0,
                    'total_tersedia' => 0,
                    'total_dipinjam' => 0,
                    'total_anggota_aktif' => 0,
                    'kunjungan_hari_ini' => 0
                ];
            }

            $stmtB = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_bibliografi WHERE tenant_id = :tid AND is_active = true");
            $stmtB->execute(['tid' => $tenantId]);
            $totalJudul = (int)$stmtB->fetchColumn();

            $stmtE = $this->db->prepare("SELECT COUNT(*) as total FROM perpustakaan.perpus_eksemplar WHERE tenant_id = :tid AND is_active = true");
            $stmtE->execute(['tid' => $tenantId]);
            $totalEksemplar = (int)$stmtE->fetchColumn();

            $stmtS = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_sirkulasi WHERE tenant_id = :tid AND kategori = 'Dipinjam' AND is_active = true");
            $stmtS->execute(['tid' => $tenantId]);
            $totalDipinjam = (int)$stmtS->fetchColumn();

            $stmtA = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_anggota WHERE tenant_id = :tid AND is_active = true");
            $stmtA->execute(['tid' => $tenantId]);
            $totalAnggota = (int)$stmtA->fetchColumn();

            $stmtK = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_buku_tamu WHERE tenant_id = :tid AND created_at >= CURRENT_DATE");
            $stmtK->execute(['tid' => $tenantId]);
            $kunjunganHariIni = (int)$stmtK->fetchColumn();

            return [
                'total_judul' => $totalJudul,
                'total_eksemplar' => max($totalJudul, $totalEksemplar),
                'total_tersedia' => max(0, max($totalJudul, $totalEksemplar) - $totalDipinjam),
                'total_dipinjam' => $totalDipinjam,
                'total_anggota_aktif' => $totalAnggota,
                'kunjungan_hari_ini' => $kunjunganHariIni
            ];
        } catch (\Throwable $e) {
            return [
                'total_judul' => 0,
                'total_eksemplar' => 0,
                'total_tersedia' => 0,
                'total_dipinjam' => 0,
                'total_anggota_aktif' => 0,
                'kunjungan_hari_ini' => 0
            ];
        }
    }

    public function getAccreditationStats(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [
                    'total_judul' => 0,
                    'total_fiksi' => 0,
                    'total_non_fiksi' => 0,
                    'persen_fiksi' => 0.0,
                    'persen_non_fiksi' => 0.0,
                    'is_layak_akreditasi' => false
                ];
            }

            $stmt = $this->db->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN kategori = 'Fiksi' THEN 1 ELSE 0 END) as fiksi,
                SUM(CASE WHEN kategori != 'Fiksi' THEN 1 ELSE 0 END) as non_fiksi
                FROM perpustakaan.perpus_bibliografi
                WHERE tenant_id = :tid AND is_active = true");
            $stmt->execute(['tid' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $total = (int)($row['total'] ?? 0);
            $fiksi = (int)($row['fiksi'] ?? 0);
            $nonFiksi = (int)($row['non_fiksi'] ?? 0);

            $pctFiksi = $total > 0 ? round(($fiksi / $total) * 100, 1) : 0.0;
            $pctNonFiksi = $total > 0 ? round(($nonFiksi / $total) * 100, 1) : 0.0;

            return [
                'total_judul' => $total,
                'total_fiksi' => $fiksi,
                'total_non_fiksi' => $nonFiksi,
                'persen_fiksi' => $pctFiksi,
                'persen_non_fiksi' => $pctNonFiksi,
                'is_layak_akreditasi' => ($pctNonFiksi >= 60.0)
            ];
        } catch (\Throwable $e) {
            return [
                'total_judul' => 0,
                'total_fiksi' => 0,
                'total_non_fiksi' => 0,
                'persen_fiksi' => 0.0,
                'persen_non_fiksi' => 0.0,
                'is_layak_akreditasi' => false
            ];
        }
    }

    public function getLokasiRakList(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [];
            }

            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_lokasi_rak WHERE tenant_id = :tenant_id AND is_active = true ORDER BY created_at ASC");
            $stmt->execute(['tenant_id' => $tenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $meta = json_decode($r['deskripsi'] ?? '{}', true) ?: [];
                $result[] = [
                    'id' => $r['id'],
                    'kode' => $r['kategori'] ?? ($meta['kode'] ?? 'R-01'),
                    'nama' => $r['nama_perpus_lokasi_rak'] ?? ($meta['nama'] ?? 'Rak Buku'),
                    'ruangan' => $meta['ruangan'] ?? 'Ruang Utama',
                    'kapasitas' => (int)($meta['kapasitas'] ?? 50)
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function saveLokasiRak(string $tenantId, array $data, ?string $id = null): string {
        $tenantId = $this->resolveTenantId($tenantId);
        $kode = trim($data['kode'] ?? 'R-01');
        $nama = trim($data['nama'] ?? 'Rak Buku');
        $meta = [
            'kode' => $kode,
            'nama' => $nama,
            'ruangan' => $data['ruangan'] ?? 'Ruang Utama',
            'kapasitas' => (int)($data['kapasitas'] ?? 50)
        ];

        if ($id) {
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_lokasi_rak SET 
                nama_perpus_lokasi_rak = :nama,
                kategori = :kode,
                deskripsi = :deskripsi,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute([
                'nama' => $nama,
                'kode' => $kode,
                'deskripsi' => json_encode($meta),
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            return $id;
        } else {
            $newId = $this->generateUuid();
            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_lokasi_rak 
                (id, tenant_id, nama_perpus_lokasi_rak, kategori, deskripsi, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :nama, :kode, :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([
                'id' => $newId,
                'tenant_id' => $tenantId,
                'nama' => $nama,
                'kode' => $kode,
                'deskripsi' => json_encode($meta)
            ]);
            return $newId;
        }
    }

    public function getKategoriDdcList(): array {
        return [
            ['kode' => '000', 'nama' => 'Karya Umum, Komputer, & Informasi', 'tingkat' => 1],
            ['kode' => '100', 'nama' => 'Filsafat & Psikologi', 'tingkat' => 1],
            ['kode' => '200', 'nama' => 'Agama & Spiritualitas', 'tingkat' => 1],
            ['kode' => '300', 'nama' => 'Ilmu Sosial, Sosiologi, & Hukum', 'tingkat' => 1],
            ['kode' => '400', 'nama' => 'Bahasa & Linguistik', 'tingkat' => 1],
            ['kode' => '500', 'nama' => 'Sains Murni, Matematika, & IPA', 'tingkat' => 1],
            ['kode' => '600', 'nama' => 'Teknologi & Ilmu Terapan', 'tingkat' => 1],
            ['kode' => '700', 'nama' => 'Kesenian, Olahraga, & Rekreasi', 'tingkat' => 1],
            ['kode' => '800', 'nama' => 'Kesusastraan & Novel', 'tingkat' => 1],
            ['kode' => '900', 'nama' => 'Sejarah, Biografi, & Geografi', 'tingkat' => 1]
        ];
    }

    public function getUsulanBukuList(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [];
            }

            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_usulan_buku WHERE tenant_id = :tenant_id AND is_active = true ORDER BY created_at DESC");
            $stmt->execute(['tenant_id' => $tenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $meta = json_decode($r['deskripsi'] ?? '{}', true) ?: [];
                $result[] = [
                    'id' => $r['id'],
                    'judul' => $r['nama_perpus_usulan_buku'] ?? ($meta['judul'] ?? 'Usulan Buku'),
                    'status' => $r['kategori'] ?? ($meta['status'] ?? 'Diajukan'),
                    'pengarang' => $meta['pengarang'] ?? '-',
                    'penerbit' => $meta['penerbit'] ?? '-',
                    'pengusul_nama' => $meta['pengusul_nama'] ?? 'Pustakawan',
                    'tanggal_usulan' => $meta['tanggal_usulan'] ?? date('Y-m-d')
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function saveUsulanBuku(string $tenantId, array $data, ?string $id = null): string {
        $tenantId = $this->resolveTenantId($tenantId);
        $judul = trim($data['judul'] ?? 'Usulan Judul Buku');
        $status = trim($data['status'] ?? 'Diajukan');
        $meta = [
            'judul' => $judul,
            'pengarang' => $data['pengarang'] ?? '',
            'penerbit' => $data['penerbit'] ?? '',
            'pengusul_nama' => $data['pengusul_nama'] ?? 'Pustakawan',
            'tanggal_usulan' => $data['tanggal_usulan'] ?? date('Y-m-d'),
            'status' => $status
        ];

        if ($id) {
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_usulan_buku SET 
                nama_perpus_usulan_buku = :nama,
                kategori = :status,
                deskripsi = :deskripsi,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute([
                'nama' => $judul,
                'status' => $status,
                'deskripsi' => json_encode($meta),
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            return $id;
        } else {
            $newId = $this->generateUuid();
            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_usulan_buku 
                (id, tenant_id, nama_perpus_usulan_buku, kategori, deskripsi, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :nama, :status, :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([
                'id' => $newId,
                'tenant_id' => $tenantId,
                'nama' => $judul,
                'status' => $status,
                'deskripsi' => json_encode($meta)
            ]);
            return $newId;
        }
    }

    public function getSerialBerkalaList(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [];
            }

            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_serial_berkala WHERE tenant_id = :tid AND is_active = true ORDER BY created_at DESC");
            $stmt->execute(['tid' => $tenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $meta = json_decode($r['deskripsi'] ?? '{}', true) ?: [];
                $result[] = [
                    'id' => $r['id'],
                    'nama_media' => $r['nama_perpus_serial_berkala'] ?? ($meta['nama_media'] ?? 'Media Berkala'),
                    'jenis' => $r['kategori'] ?? ($meta['jenis'] ?? 'Majalah'),
                    'frekuensi' => $meta['frekuensi'] ?? 'Bulanan',
                    'issn' => $meta['issn'] ?? '-',
                    'tanggal_berlangganan' => $meta['tanggal_berlangganan'] ?? date('Y-m-d'),
                    'status_aktif' => (int)($meta['status_aktif'] ?? 1)
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function saveSerialBerkala(string $tenantId, array $data, ?string $id = null): string {
        $tenantId = $this->resolveTenantId($tenantId);
        $namaMedia = trim($data['nama_media'] ?? 'Media Berkala');
        $jenis = trim($data['jenis'] ?? 'Majalah');
        $meta = [
            'nama_media' => $namaMedia,
            'jenis' => $jenis,
            'frekuensi' => $data['frekuensi'] ?? 'Bulanan',
            'issn' => $data['issn'] ?? '',
            'tanggal_berlangganan' => $data['tanggal_berlangganan'] ?? date('Y-m-d'),
            'status_aktif' => (int)($data['status_aktif'] ?? 1)
        ];

        if ($id) {
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_serial_berkala SET 
                nama_perpus_serial_berkala = :nama,
                kategori = :jenis,
                deskripsi = :deskripsi,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute([
                'nama' => $namaMedia,
                'jenis' => $jenis,
                'deskripsi' => json_encode($meta),
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            return $id;
        } else {
            $newId = $this->generateUuid();
            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_serial_berkala 
                (id, tenant_id, nama_perpus_serial_berkala, kategori, deskripsi, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :nama, :jenis, :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([
                'id' => $newId,
                'tenant_id' => $tenantId,
                'nama' => $namaMedia,
                'jenis' => $jenis,
                'deskripsi' => json_encode($meta)
            ]);
            return $newId;
        }
    }

    public function getVisitorLogs(string $tenantId, int $limit = 50): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            if (empty($tenantId)) {
                return [];
            }

            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_buku_tamu WHERE tenant_id = :tenant_id AND is_active = true ORDER BY created_at DESC LIMIT " . (int)$limit);
            $stmt->execute(['tenant_id' => $tenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $meta = json_decode($r['deskripsi'] ?? '{}', true) ?: [];
                $result[] = [
                    'id' => $r['id'],
                    'nama_pengunjung' => $r['nama_perpus_buku_tamu'] ?? ($meta['nama'] ?? 'Pengunjung'),
                    'tipe' => $r['kategori'] ?? ($meta['tipe'] ?? 'Siswa'),
                    'tujuan' => $meta['tujuan'] ?? 'Membaca',
                    'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function checkinVisitor(string $tenantId, array $data): bool {
        try {
            $id = $this->generateUuid();
            $nama = trim($data['nama'] ?? ($data['nama_pengunjung'] ?? 'Pengunjung'));
            $tipe = trim($data['tipe'] ?? 'Siswa');
            $meta = [
                'nama' => $nama,
                'tipe' => $tipe,
                'tujuan' => $data['tujuan'] ?? 'Membaca / Meminjam Buku',
                'kontak' => $data['kontak'] ?? ''
            ];

            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_buku_tamu 
                (id, tenant_id, nama_perpus_buku_tamu, kategori, deskripsi, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :nama, :tipe, :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            return $stmt->execute([
                'id' => $id,
                'tenant_id' => $tenantId,
                'nama' => $nama,
                'tipe' => $tipe,
                'deskripsi' => json_encode($meta)
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function deleteLokasiRak(string $tenantId, string $id): bool {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_lokasi_rak SET is_active = false, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id");
            return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function deleteUsulanBuku(string $tenantId, string $id): bool {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_usulan_buku SET is_active = false, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id");
            return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function updateStatusUsulanBuku(string $tenantId, string $id, string $status): bool {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $stmt = $this->db->prepare("SELECT deskripsi FROM perpustakaan.perpus_usulan_buku WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
            $deskripsi = $stmt->fetchColumn();
            $meta = json_decode($deskripsi ?: '{}', true) ?: [];
            $meta['status'] = $status;

            $stmtUpdate = $this->db->prepare("UPDATE perpustakaan.perpus_usulan_buku SET 
                kategori = :status, 
                deskripsi = :deskripsi, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id AND tenant_id = :tenant_id");
            return $stmtUpdate->execute([
                'status' => $status,
                'deskripsi' => json_encode($meta),
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function deleteSerialBerkala(string $tenantId, string $id): bool {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_serial_berkala SET is_active = false, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id");
            return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getEksemplarByBibliografiId(string $tenantId, string $bibliografiId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_eksemplar 
                WHERE tenant_id = :tenant_id AND is_active = true 
                ORDER BY created_at ASC");
            $stmt->execute(['tenant_id' => $tenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $meta = json_decode($r['deskripsi'] ?? '{}', true) ?: [];
                if (!empty($meta['bibliografi_id']) && $meta['bibliografi_id'] === $bibliografiId) {
                    $result[] = [
                        'id' => $r['id'],
                        'barcode' => $meta['barcode'] ?? ('BC-' . substr($r['id'], 0, 8)),
                        'nomor_induk' => $meta['nomor_induk'] ?? ($meta['barcode'] ?? ''),
                        'lokasi_rak' => $meta['lokasi_rak'] ?? ($meta['rak'] ?? 'R-01'),
                        'kondisi' => $meta['kondisi'] ?? 'Baik',
                        'status' => $r['kategori'] ?? ($meta['status'] ?? 'Tersedia'),
                        'sumber' => $meta['sumber'] ?? 'Pengadaan Sekolah',
                        'tanggal_masuk' => $meta['tanggal_masuk'] ?? date('Y-m-d')
                    ];
                }
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function saveEksemplarSingle(string $tenantId, array $data, ?string $id = null): string {
        $tenantId = $this->resolveTenantId($tenantId);
        $bibliografiId = trim($data['bibliografi_id'] ?? '');
        $barcode = trim($data['barcode'] ?? ('BC-' . date('Y') . '-' . rand(1000, 9999)));
        $lokasiRak = trim($data['lokasi_rak'] ?? ($data['rak'] ?? 'R-01'));
        $kondisi = trim($data['kondisi'] ?? 'Baik');
        $status = trim($data['status'] ?? 'Tersedia');
        $nomorInduk = trim($data['nomor_induk'] ?? $barcode);

        $meta = [
            'bibliografi_id' => $bibliografiId,
            'barcode' => $barcode,
            'nomor_induk' => $nomorInduk,
            'lokasi_rak' => $lokasiRak,
            'rak' => $lokasiRak,
            'kondisi' => $kondisi,
            'status' => $status,
            'sumber' => $data['sumber'] ?? 'Pengadaan',
            'tanggal_masuk' => $data['tanggal_masuk'] ?? date('Y-m-d')
        ];

        if ($id) {
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_eksemplar SET 
                nama_perpus_eksemplar = :nama,
                kategori = :status,
                deskripsi = :deskripsi,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute([
                'nama' => 'Eksemplar ' . $barcode,
                'status' => $status,
                'deskripsi' => json_encode($meta),
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            return $id;
        } else {
            $newId = $this->generateUuid();
            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_eksemplar 
                (id, tenant_id, nama_perpus_eksemplar, kategori, deskripsi, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :nama, :status, :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([
                'id' => $newId,
                'tenant_id' => $tenantId,
                'nama' => 'Eksemplar ' . $barcode,
                'status' => $status,
                'deskripsi' => json_encode($meta)
            ]);
            return $newId;
        }
    }

    public function deleteEksemplar(string $tenantId, string $id): bool {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_eksemplar SET is_active = false, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id");
            return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getPengaturanPerpus(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_pengaturan WHERE tenant_id = :tenant_id LIMIT 1");
            $stmt->execute(['tenant_id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $meta = json_decode($row['deskripsi'] ?? '{}', true) ?: [];
                return array_merge([
                    'id' => $row['id'],
                    'nama_perpustakaan' => $row['nama_perpus_pengaturan'] ?? 'Perpustakaan Sekolah',
                    'maks_pinjam_hari' => 7,
                    'maks_buku_pinjam' => 3,
                    'denda_per_hari' => 1000
                ], $meta);
            }
            return [
                'nama_perpustakaan' => 'Perpustakaan Sekolah Digital',
                'maks_pinjam_hari' => 7,
                'maks_buku_pinjam' => 3,
                'denda_per_hari' => 1000
            ];
        } catch (\Throwable $e) {
            return [
                'nama_perpustakaan' => 'Perpustakaan Sekolah Digital',
                'maks_pinjam_hari' => 7,
                'maks_buku_pinjam' => 3,
                'denda_per_hari' => 1000
            ];
        }
    }

    public function savePengaturanPerpus(string $tenantId, array $data): bool {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $stmt = $this->db->prepare("SELECT id FROM perpustakaan.perpus_pengaturan WHERE tenant_id = :tenant_id LIMIT 1");
            $stmt->execute(['tenant_id' => $tenantId]);
            $existingId = $stmt->fetchColumn();

            $nama = trim($data['nama_perpustakaan'] ?? 'Perpustakaan Sekolah');
            $meta = [
                'nama_perpustakaan' => $nama,
                'maks_pinjam_hari' => (int)($data['maks_pinjam_hari'] ?? 7),
                'maks_buku_pinjam' => (int)($data['maks_buku_pinjam'] ?? 3),
                'denda_per_hari' => (int)($data['denda_per_hari'] ?? 1000),
                'jam_buka' => $data['jam_buka'] ?? '07:30 - 15:30',
                'alamat' => $data['alamat'] ?? ''
            ];

            if ($existingId) {
                $stmtU = $this->db->prepare("UPDATE perpustakaan.perpus_pengaturan SET 
                    nama_perpus_pengaturan = :nama,
                    deskripsi = :deskripsi,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id AND tenant_id = :tenant_id");
                return $stmtU->execute([
                    'nama' => $nama,
                    'deskripsi' => json_encode($meta),
                    'id' => $existingId,
                    'tenant_id' => $tenantId
                ]);
            } else {
                $newId = $this->generateUuid();
                $stmtI = $this->db->prepare("INSERT INTO perpustakaan.perpus_pengaturan 
                    (id, tenant_id, nama_perpus_pengaturan, kategori, deskripsi, is_active, created_at, updated_at)
                    VALUES (:id, :tenant_id, :nama, 'Konfigurasi', :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                return $stmtI->execute([
                    'id' => $newId,
                    'tenant_id' => $tenantId,
                    'nama' => $nama,
                    'deskripsi' => json_encode($meta)
                ]);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getMasterReferensiPaket(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);

            // 1. Ambil Kelas dari akademik.kelas
            $stmtKelas = $this->db->prepare("SELECT id, nama_kelas, kode_kelas FROM akademik.kelas WHERE tenant_id = :tenant_id AND (is_active = true OR is_active IS NULL) ORDER BY nama_kelas ASC");
            $stmtKelas->execute(['tenant_id' => $tenantId]);
            $kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 2. Ambil Koleksi Buku dari perpustakaan.perpus_bibliografi
            $stmtBuku = $this->db->prepare("SELECT id, nama_perpus_bibliografi AS judul, deskripsi FROM perpustakaan.perpus_bibliografi WHERE tenant_id = :tenant_id AND (is_active = true OR is_active IS NULL) ORDER BY nama_perpus_bibliografi ASC");
            $stmtBuku->execute(['tenant_id' => $tenantId]);
            $rawBuku = $stmtBuku->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $bukuList = [];
            foreach ($rawBuku as $b) {
                $meta = json_decode($b['deskripsi'] ?? '{}', true) ?: [];
                $bukuList[] = [
                    'id' => $b['id'],
                    'judul' => $b['judul'] ?: ($meta['judul'] ?? 'Buku'),
                    'penulis' => $meta['penulis'] ?? '-',
                    'penerbit' => $meta['penerbit'] ?? '-',
                    'isbn' => $meta['isbn'] ?? '-'
                ];
            }

            // 3. Ambil Mata Pelajaran dari akademik.mata_pelajaran
            $stmtMapel = $this->db->prepare("SELECT id, nama_mata_pelajaran FROM akademik.mata_pelajaran WHERE tenant_id = :tenant_id AND (is_active = true OR is_active IS NULL) ORDER BY nama_mata_pelajaran ASC");
            $stmtMapel->execute(['tenant_id' => $tenantId]);
            $mapelList = $stmtMapel->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 4. Ambil Guru/PTK dari kepegawaian.ptk_identitas
            $stmtGuru = $this->db->prepare("SELECT id, nama_ptk_identitas AS nama, deskripsi FROM kepegawaian.ptk_identitas WHERE tenant_id = :tenant_id AND (is_active = true OR is_active IS NULL) ORDER BY nama_ptk_identitas ASC");
            $stmtGuru->execute(['tenant_id' => $tenantId]);
            $rawGuru = $stmtGuru->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $guruList = [];
            foreach ($rawGuru as $g) {
                $meta = json_decode($g['deskripsi'] ?? '{}', true) ?: [];
                $guruList[] = [
                    'id' => $g['id'],
                    'nama' => $g['nama'] ?: ($meta['nama_lengkap'] ?? 'Guru'),
                    'nuptk' => $meta['nuptk'] ?? '-'
                ];
            }

            return [
                'kelas' => $kelasList,
                'buku' => $bukuList,
                'mapel' => $mapelList,
                'guru' => $guruList
            ];
        } catch (\Throwable $e) {
            return [
                'kelas' => [],
                'buku' => [],
                'mapel' => [],
                'guru' => []
            ];
        }
    }

    public function getDistribusiPaketBukuList(string $tenantId): array {
        try {
            $tenantId = $this->resolveTenantId($tenantId);
            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_paket_buku WHERE tenant_id = :tenant_id AND is_active = true ORDER BY created_at DESC");
            $stmt->execute(['tenant_id' => $tenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result = [];
            foreach ($rows as $r) {
                $meta = json_decode($r['deskripsi'] ?? '{}', true) ?: [];
                $durasi = (int)($meta['durasi_hari'] ?? 180);
                $result[] = [
                    'id' => $r['id'],
                    'nama_kelas' => $r['nama_perpus_paket_buku'] ?? ($meta['nama_kelas'] ?? 'Kelas'),
                    'judul_buku' => $meta['judul_buku'] ?? ($r['kategori'] ?? 'Buku Paket'),
                    'kode_mapel' => $meta['kode_mapel'] ?? '',
                    'guru_mapel' => $meta['guru_mapel'] ?? '',
                    'durasi_hari' => $durasi,
                    'durasi_label' => $durasi >= 365 ? '1 Tahun Ajaran' : '1 Semester (6 Bulan)',
                    'tgl_jatuh_tempo' => $meta['tgl_jatuh_tempo'] ?? date('Y-m-d', strtotime("+{$durasi} days")),
                    'jumlah_eksemplar' => $meta['jumlah_eksemplar'] ?? 1,
                    'catatan' => $meta['catatan'] ?? '',
                    'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function simpanDistribusiPaketBuku(string $tenantId, array $data): string {
        $tenantId = $this->resolveTenantId($tenantId);
        $id = $this->generateUuid();
        $namaKelas = trim($data['nama_kelas'] ?? 'Kelas');
        $judulBuku = trim($data['judul_buku'] ?? 'Buku Paket');
        $durasi = (int)($data['durasi_hari'] ?? 180);
        $jatuhTempo = !empty($data['tgl_jatuh_tempo']) ? $data['tgl_jatuh_tempo'] : date('Y-m-d', strtotime("+{$durasi} days"));

        $meta = [
            'nama_kelas' => $namaKelas,
            'kelas_id' => $data['kelas_id'] ?? null,
            'judul_buku' => $judulBuku,
            'buku_id' => $data['buku_id'] ?? null,
            'guru_mapel' => trim($data['guru_mapel'] ?? ''),
            'durasi_hari' => $durasi,
            'tgl_jatuh_tempo' => $jatuhTempo,
            'jumlah_eksemplar' => (int)($data['jumlah_eksemplar'] ?? 1),
            'catatan' => trim($data['catatan'] ?? '')
        ];

        $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_paket_buku 
            (id, tenant_id, nama_perpus_paket_buku, kategori, deskripsi, is_active, created_at, updated_at)
            VALUES (:id, :tenant_id, :nama, :judul, :deskripsi, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'nama' => $namaKelas,
            'judul' => $judulBuku,
            'deskripsi' => json_encode($meta)
        ]);
        return $id;
    }

    private function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // =========================================================================
    // DENDA, EVENT OSN, OPNAME — Zero Data Leakage Async Endpoints
    // =========================================================================

    /**
     * Daftar denda keterlambatan pengembalian buku per tenant.
     * Jika tabel belum tersedia (fitur baru), kembalikan array kosong agar view tetap bekerja.
     */
    public function getDendaList(string $tenantId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    s.id,
                    COALESCE(a.nama_lengkap, a.nama_siswa, a.username) AS nama_siswa,
                    b.judul AS judul_buku,
                    GREATEST(0, CURRENT_DATE - s.tanggal_kembali_rencana::date) AS hari_terlambat,
                    COALESCE(d.jumlah_denda, GREATEST(0, CURRENT_DATE - s.tanggal_kembali_rencana::date) * COALESCE(pp.denda_per_hari, 1000)) AS jumlah_denda,
                    COALESCE(d.status, 'Belum Lunas') AS status,
                    COALESCE(d.metode_bayar, 'Tunai / SPP') AS metode_bayar,
                    d.id AS denda_id
                FROM perpustakaan.sirkulasi s
                LEFT JOIN perpustakaan.anggota a ON s.anggota_id = a.id
                LEFT JOIN perpustakaan.bibliografi b ON s.bibliografi_id = b.id
                LEFT JOIN perpustakaan.denda d ON d.sirkulasi_id = s.id
                LEFT JOIN perpustakaan.pengaturan pp ON pp.tenant_id = s.tenant_id
                WHERE s.tenant_id = :tenant_id
                  AND s.status_kembali = 'Belum Kembali'
                  AND s.tanggal_kembali_rencana IS NOT NULL
                  AND s.tanggal_kembali_rencana::date < CURRENT_DATE
                ORDER BY s.tanggal_kembali_rencana ASC
                LIMIT 200"
            );
            $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Catat pembayaran denda perpustakaan (Lunas).
     */
    public function bayarDenda(string $tenantId, string $dendaId): bool {
        try {
            $stmt = $this->db->prepare(
                "UPDATE perpustakaan.denda
                 SET status = 'Lunas', metode_bayar = 'Tunai', updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND tenant_id = :tenant_id"
            );
            $stmt->bindValue(':id', $dendaId, \PDO::PARAM_STR);
            $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Daftar event khusus / OSN / olimpiade per tenant.
     */
    public function getEventOSNList(string $tenantId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    e.id,
                    COALESCE(t.nama_sekolah, 'Sekolah Aktif') AS tenant_name,
                    e.nama_event,
                    e.bidang,
                    COALESCE(a.nama_lengkap, a.nama_siswa) AS nama_siswa,
                    b.judul AS judul_buku,
                    e.tanggal_kembali_rencana,
                    COALESCE(e.status_event, 'Aktif / Berjalan') AS status_event
                FROM perpustakaan.event_osn e
                LEFT JOIN sistem.tenants t ON t.id = e.tenant_id
                LEFT JOIN perpustakaan.anggota a ON a.id = e.anggota_id
                LEFT JOIN perpustakaan.bibliografi b ON b.id = e.bibliografi_id
                WHERE e.tenant_id = :tenant_id
                  AND e.deleted_at IS NULL
                ORDER BY e.created_at DESC
                LIMIT 100"
            );
            $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Daftar sesi stock opname per tenant.
     */
    public function getOpnameList(string $tenantId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    o.id,
                    COALESCE(t.nama_sekolah, 'Sekolah Aktif') AS tenant_name,
                    o.nama_sesi,
                    o.tanggal,
                    COALESCE(u.nama_lengkap, u.username) AS petugas,
                    COALESCE(o.total_scanned, 0) AS total_scanned,
                    COALESCE(o.total_selisih, 0) AS total_selisih,
                    COALESCE(o.status_audit, 'Selesai') AS status_audit
                FROM perpustakaan.stock_opname o
                LEFT JOIN sistem.tenants t ON t.id = o.tenant_id
                LEFT JOIN sistem.users u ON u.id = o.user_id
                WHERE o.tenant_id = :tenant_id
                  AND o.deleted_at IS NULL
                ORDER BY o.tanggal DESC
                LIMIT 100"
            );
            $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}

