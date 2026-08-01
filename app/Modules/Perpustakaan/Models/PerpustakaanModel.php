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

    public function getPengaturan(string $tenantId): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_pengaturan WHERE tenant_id = :tenant_id LIMIT 1");
            $stmt->execute(['tenant_id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $id = $this->generateUuid();
                $stmtInit = $this->db->prepare("INSERT INTO perpustakaan.perpus_pengaturan (id, tenant_id, nama_perpustakaan) VALUES (:id, :tenant_id, 'Perpustakaan Digital')");
                $stmtInit->execute(['id' => $id, 'tenant_id' => $tenantId]);

                $stmt->execute(['tenant_id' => $tenantId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            }

            return $row;
        } catch (\Throwable $e) { return []; }
    }

    public function updatePengaturan(string $tenantId, array $data): bool {
        $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_pengaturan SET 
            nama_perpustakaan = :nama,
            nomor_pokok = :nomor_pokok,
            kepala_perpustakaan = :kepala,
            nip_kepala = :nip,
            tarif_denda_per_hari = :tarif_denda,
            max_hari_pinjam_siswa = :max_siswa,
            max_hari_pinjam_guru = :max_guru,
            opac_aktif = :opac_aktif,
            wa_gateway_url = :wa_url,
            wa_gateway_api_key = :wa_key,
            auto_notif_wa_aktif = :auto_wa,
            auto_notif_email_aktif = :auto_email
            WHERE tenant_id = :tenant_id");

        return $stmt->execute([
            'nama' => $data['nama_perpustakaan'] ?? 'Perpustakaan Digital',
            'nomor_pokok' => $data['nomor_pokok'] ?? '',
            'kepala' => $data['kepala_perpustakaan'] ?? '',
            'nip' => $data['nip_kepala'] ?? '',
            'tarif_denda' => (float)($data['tarif_denda_per_hari'] ?? 500),
            'max_siswa' => (int)($data['max_hari_pinjam_siswa'] ?? 7),
            'max_guru' => (int)($data['max_hari_pinjam_guru'] ?? 14),
            'opac_aktif' => (int)($data['opac_aktif'] ?? 1),
            'wa_url' => $data['wa_gateway_url'] ?? null,
            'wa_key' => $data['wa_gateway_api_key'] ?? null,
            'auto_wa' => (int)($data['auto_notif_wa_aktif'] ?? 1),
            'auto_email' => (int)($data['auto_notif_email_aktif'] ?? 0),
            'tenant_id' => $tenantId
        ]);
    }

    public function getBibliografiList(string $tenantId, array $filters = [], int $limit = 50, int $offset = 0): array {
        try {
            $sql = "SELECT b.*, t.nama_sekolah as tenant_name,
                COUNT(e.id) as total_eksemplar,
                SUM(CASE WHEN e.status = 'Tersedia' THEN 1 ELSE 0 END) as total_tersedia
                FROM perpustakaan.perpus_bibliografi b
                LEFT JOIN perpustakaan.perpus_eksemplar e ON b.id = e.id
                LEFT JOIN core.tenants t ON b.tenant_id = t.id
                WHERE b.tenant_id = :tenant_id AND b.is_active = true";

            $params = ['tenant_id' => $tenantId];

            if (!empty($filters['search'])) {
                $sql .= " AND (b.judul ILIKE :search OR b.isbn ILIKE :search OR b.penulis ILIKE :search OR b.klasifikasi_ddc ILIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['jenis_buku'])) {
                $sql .= " AND b.jenis_buku = :jenis_buku";
                $params['jenis_buku'] = $filters['jenis_buku'];
            }

            $sql .= " GROUP BY b.id, t.nama_sekolah ORDER BY b.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }

    public function getBibliografiById(string $tenantId, string $id): ?array {
        try {
            $stmt = $this->db->prepare("SELECT b.*, t.nama_sekolah as tenant_name
                FROM perpustakaan.perpus_bibliografi b
                LEFT JOIN core.tenants t ON b.tenant_id = t.id
                WHERE b.id = :id AND b.tenant_id = :tenant_id AND b.is_active = true
                LIMIT 1");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) { return []; }
    }

    public function saveBibliografi(string $tenantId, array $data, ?string $id = null): string {
        $pengarangInput = $data['pengarang'] ?? ($data['penulis'] ?? '');
        $penulisJson = is_array($pengarangInput) ? json_encode($pengarangInput, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : json_encode([$pengarangInput]);

        $subjekInput = $data['subjek'] ?? [];
        if (is_string($subjekInput)) {
            $subjekInput = array_map('trim', explode(';', $subjekInput));
        }
        $subjekJson = is_array($subjekInput) ? json_encode($subjekInput, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : json_encode([]);

        if ($id) {
            $setCover = isset($data['cover']) ? ', cover = :cover' : '';
            $setEbook = isset($data['file_ebook']) ? ', file_ebook = :file_ebook' : '';
            $stmt = $this->db->prepare("UPDATE perpustakaan.perpus_bibliografi SET
                isbn = :isbn, judul = :judul, judul_seri = :judul_seri, edisi = :edisi, penulis = :penulis, penerbit = :penerbit,
                kota_terbit = :kota_terbit, tahun_terbit = :tahun_terbit, halaman = :halaman, dimensi = :dimensi, bahasa = :bahasa,
                klasifikasi_ddc = :ddc, nomor_panggil = :panggil, subjek = :subjek, abstrak = :abstrak,
                jenis_buku = :jenis, status_opac = :opac, is_ebook = :is_ebook$setCover$setEbook
                WHERE id = :id AND tenant_id = :tenant_id");
            $params = [
                'isbn' => $data['isbn'] ?? null,
                'judul' => $data['judul'],
                'judul_seri' => $data['judul_seri'] ?? null,
                'edisi' => $data['edisi'] ?? null,
                'penulis' => $penulisJson,
                'penerbit' => $data['penerbit'] ?? null,
                'kota_terbit' => $data['kota_terbit'] ?? null,
                'tahun_terbit' => !empty($data['tahun_terbit']) ? (int)$data['tahun_terbit'] : null,
                'halaman' => !empty($data['halaman']) ? (int)$data['halaman'] : null,
                'dimensi' => $data['dimensi'] ?? null,
                'bahasa' => $data['bahasa'] ?? 'Indonesia',
                'ddc' => $data['klasifikasi_ddc'] ?? null,
                'panggil' => $data['nomor_panggil'] ?? null,
                'subjek' => $subjekJson,
                'abstrak' => $data['abstrak'] ?? null,
                'jenis' => $data['jenis_buku'] ?? 'Umum',
                'opac' => (int)($data['status_opac'] ?? 1),
                'is_ebook' => (int)($data['is_ebook'] ?? 0),
                'id' => $id,
                'tenant_id' => $tenantId
            ];
            if (isset($data['cover'])) $params['cover'] = $data['cover'];
            if (isset($data['file_ebook'])) $params['file_ebook'] = $data['file_ebook'];
            $stmt->execute($params);
            return $id;
        } else {
            $newId = $this->generateUuid();
            $stmt = $this->db->prepare("INSERT INTO perpustakaan.perpus_bibliografi
                (id, tenant_id, isbn, judul, judul_seri, edisi, penulis, penerbit, kota_terbit, tahun_terbit, halaman, dimensi, bahasa, klasifikasi_ddc, nomor_panggil, subjek, abstrak, jenis_buku, status_opac, is_ebook, cover, file_ebook)
                VALUES (:id, :tenant_id, :isbn, :judul, :judul_seri, :edisi, :penulis, :penerbit, :kota_terbit, :tahun_terbit, :halaman, :dimensi, :bahasa, :ddc, :panggil, :subjek, :abstrak, :jenis, :opac, :is_ebook, :cover, :file_ebook)");
            $stmt->execute([
                'id' => $newId,
                'tenant_id' => $tenantId,
                'isbn' => $data['isbn'] ?? null,
                'judul' => $data['judul'],
                'judul_seri' => $data['judul_seri'] ?? null,
                'edisi' => $data['edisi'] ?? null,
                'penulis' => $penulisJson,
                'penerbit' => $data['penerbit'] ?? null,
                'kota_terbit' => $data['kota_terbit'] ?? null,
                'tahun_terbit' => !empty($data['tahun_terbit']) ? (int)$data['tahun_terbit'] : null,
                'halaman' => !empty($data['halaman']) ? (int)$data['halaman'] : null,
                'dimensi' => $data['dimensi'] ?? null,
                'bahasa' => $data['bahasa'] ?? 'Indonesia',
                'ddc' => $data['klasifikasi_ddc'] ?? null,
                'panggil' => $data['nomor_panggil'] ?? null,
                'subjek' => $subjekJson,
                'abstrak' => $data['abstrak'] ?? null,
                'jenis' => $data['jenis_buku'] ?? 'Umum',
                'opac' => (int)($data['status_opac'] ?? 1),
                'is_ebook' => (int)($data['is_ebook'] ?? 0),
                'cover' => $data['cover'] ?? null,
                'file_ebook' => $data['file_ebook'] ?? null,
            ]);
            return $newId;
        }
    }

    public function getDashboardSummary(string $tenantId): array {
        try {
            $stmtB = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_bibliografi WHERE tenant_id = :tid AND is_active = true");
            $stmtB->execute(['tid' => $tenantId]);
            $totalJudul = (int)$stmtB->fetchColumn();

            $stmtE = $this->db->prepare("SELECT COUNT(*) as total, 
                SUM(CASE WHEN status = 'Tersedia' THEN 1 ELSE 0 END) as tersedia,
                SUM(CASE WHEN status LIKE 'Dipinjam%' THEN 1 ELSE 0 END) as dipinjam
                FROM perpustakaan.perpus_eksemplar WHERE tenant_id = :tid");
            $stmtE->execute(['tid' => $tenantId]);
            $eksStats = $stmtE->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'tersedia' => 0, 'dipinjam' => 0];

            $stmtA = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_anggota WHERE tenant_id = :tid AND status = 'Aktif'");
            $stmtA->execute(['tid' => $tenantId]);
            $totalAnggota = (int)$stmtA->fetchColumn();

            $stmtK = $this->db->prepare("SELECT COUNT(*) FROM perpustakaan.perpus_buku_tamu WHERE tenant_id = :tid AND tanggal = CURRENT_DATE");
            $stmtK->execute(['tid' => $tenantId]);
            $kunjunganHariIni = (int)$stmtK->fetchColumn();

            return [
                'total_judul' => $totalJudul,
                'total_eksemplar' => (int)$eksStats['total'],
                'total_tersedia' => (int)$eksStats['tersedia'],
                'total_dipinjam' => (int)$eksStats['dipinjam'],
                'total_anggota_aktif' => $totalAnggota,
                'kunjungan_hari_ini' => $kunjunganHariIni
            ];
        } catch (\Throwable $e) { return []; }
    }

    public function getAccreditationStats(string $tenantId): array {
        try {
            $stmt = $this->db->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN jenis_buku = 'Fiksi' THEN 1 ELSE 0 END) as fiksi,
                SUM(CASE WHEN jenis_buku IN ('Umum', 'Non-Fiksi', 'Referensi', 'Paket Pelajaran', 'OSN', 'Majalah', 'Lainnya') THEN 1 ELSE 0 END) as non_fiksi
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
        } catch (\Throwable $e) { return []; }
    }

    public function getLokasiRakList(string $tenantId): array {
        try {
            $stmt = $this->db->prepare("SELECT r.*, COUNT(e.id) as total_buku 
                FROM perpustakaan.perpus_lokasi_rak r
                LEFT JOIN perpustakaan.perpus_eksemplar e ON r.id = e.lokasi_rak_id
                WHERE r.tenant_id = :tenant_id 
                GROUP BY r.id ORDER BY r.kode ASC");
            $stmt->execute(['tenant_id' => $tenantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }

    public function getKategoriDdcList(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM perpustakaan.perpus_kategori_ddc ORDER BY kode ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }

    public function getUsulanBukuList(string $tenantId): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_usulan_buku WHERE tenant_id = :tenant_id ORDER BY created_at DESC");
            $stmt->execute(['tenant_id' => $tenantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }

    public function getSerialBerkalaList(string $tenantId): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM perpustakaan.perpus_serial_berkala WHERE tenant_id = :tid ORDER BY created_at DESC");
            $stmt->execute(['tid' => $tenantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }

    private function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
