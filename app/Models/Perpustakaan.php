<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

class Perpustakaan {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // -------------------------------------------------------------------------
    // 1. PENGATURAN & SAAS TOGGLE
    // -------------------------------------------------------------------------

    public function getPengaturan(string $tenantId): array {
        $stmt = $this->db->prepare("SELECT * FROM perpus_pengaturan WHERE tenant_id = :tenant_id LIMIT 1");
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // Auto initialize default pengaturan
            $id = $this->generateUuid();
            $stmtInit = $this->db->prepare("INSERT INTO perpus_pengaturan (id, tenant_id, nama_perpustakaan) VALUES (:id, :tenant_id, 'Perpustakaan Digital')");
            $stmtInit->execute(['id' => $id, 'tenant_id' => $tenantId]);

            $stmt->execute(['tenant_id' => $tenantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        return $row;
    }

    public function updatePengaturan(string $tenantId, array $data): bool {
        $stmt = $this->db->prepare("UPDATE perpus_pengaturan SET 
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

    // -------------------------------------------------------------------------
    // 2. KATALOG BIBLIOGRAFI & EKSEMPLAR
    // -------------------------------------------------------------------------

    public function getBibliografiList(string $tenantId, array $filters = [], int $limit = 50, int $offset = 0): array {
        $sql = "SELECT b.*, 
            COUNT(e.id) as total_eksemplar,
            SUM(CASE WHEN e.status = 'Tersedia' THEN 1 ELSE 0 END) as total_tersedia
            FROM perpus_bibliografi b
            LEFT JOIN perpus_eksemplar e ON b.id = e.bibliografi_id
            WHERE b.tenant_id = :tenant_id AND b.deleted_at IS NULL";

        $params = ['tenant_id' => $tenantId];

        if (!empty($filters['search'])) {
            $sql .= " AND (b.judul LIKE :search OR b.isbn LIKE :search OR b.penulis LIKE :search OR b.klasifikasi_ddc LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['jenis_buku'])) {
            $sql .= " AND b.jenis_buku = :jenis_buku";
            $params['jenis_buku'] = $filters['jenis_buku'];
        }

        $sql .= " GROUP BY b.id ORDER BY b.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveBibliografi(string $tenantId, array $data, ?string $id = null): string {
        $penulisJson = is_array($data['penulis']) ? json_encode($data['penulis'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : json_encode([$data['penulis'] ?? '']);
        $subjekJson = is_array($data['subjek']) ? json_encode($data['subjek'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : json_encode([]);

        if ($id) {
            $stmt = $this->db->prepare("UPDATE perpus_bibliografi SET
                isbn = :isbn, judul = :judul, edisi = :edisi, penulis = :penulis, penerbit = :penerbit,
                kota_terbit = :kota_terbit, tahun_terbit = :tahun_terbit, halaman = :halaman,
                klasifikasi_ddc = :ddc, nomor_panggil = :panggil, subjek = :subjek, abstrak = :abstrak,
                jenis_buku = :jenis, status_opac = :opac
                WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute([
                'isbn' => $data['isbn'] ?? null,
                'judul' => $data['judul'],
                'edisi' => $data['edisi'] ?? null,
                'penulis' => $penulisJson,
                'penerbit' => $data['penerbit'] ?? null,
                'kota_terbit' => $data['kota_terbit'] ?? null,
                'tahun_terbit' => $data['tahun_terbit'] ?? null,
                'halaman' => $data['halaman'] ?? null,
                'ddc' => $data['klasifikasi_ddc'] ?? null,
                'panggil' => $data['nomor_panggil'] ?? null,
                'subjek' => $subjekJson,
                'abstrak' => $data['abstrak'] ?? null,
                'jenis' => $data['jenis_buku'] ?? 'Umum',
                'opac' => (int)($data['status_opac'] ?? 1),
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            return $id;
        } else {
            $newId = $this->generateUuid();
            $stmt = $this->db->prepare("INSERT INTO perpus_bibliografi 
                (id, tenant_id, isbn, judul, edisi, penulis, penerbit, kota_terbit, tahun_terbit, halaman, klasifikasi_ddc, nomor_panggil, subjek, abstrak, jenis_buku, status_opac)
                VALUES (:id, :tenant_id, :isbn, :judul, :edisi, :penulis, :penerbit, :kota_terbit, :tahun_terbit, :halaman, :ddc, :panggil, :subjek, :abstrak, :jenis, :opac)");
            $stmt->execute([
                'id' => $newId,
                'tenant_id' => $tenantId,
                'isbn' => $data['isbn'] ?? null,
                'judul' => $data['judul'],
                'edisi' => $data['edisi'] ?? null,
                'penulis' => $penulisJson,
                'penerbit' => $data['penerbit'] ?? null,
                'kota_terbit' => $data['kota_terbit'] ?? null,
                'tahun_terbit' => $data['tahun_terbit'] ?? null,
                'halaman' => $data['halaman'] ?? null,
                'ddc' => $data['klasifikasi_ddc'] ?? null,
                'panggil' => $data['nomor_panggil'] ?? null,
                'subjek' => $subjekJson,
                'abstrak' => $data['abstrak'] ?? null,
                'jenis' => $data['jenis_buku'] ?? 'Umum',
                'opac' => (int)($data['status_opac'] ?? 1)
            ]);
            return $newId;
        }
    }

    public function saveEksemplar(string $tenantId, array $data): string {
        $id = $this->generateUuid();
        $stmt = $this->db->prepare("INSERT INTO perpus_eksemplar 
            (id, tenant_id, bibliografi_id, barcode, nomor_induk, tanggal_masuk, sumber_buku, lokasi_rak_id, kondisi, status, harga_perolehan)
            VALUES (:id, :tenant_id, :bibliografi_id, :barcode, :nomor_induk, :tanggal_masuk, :sumber_buku, :lokasi_rak_id, :kondisi, 'Tersedia', :harga)");
        
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'bibliografi_id' => $data['bibliografi_id'],
            'barcode' => $data['barcode'],
            'nomor_induk' => $data['nomor_induk'],
            'tanggal_masuk' => $data['tanggal_masuk'] ?? date('Y-m-d'),
            'sumber_buku' => $data['sumber_buku'] ?? 'Dana BOS',
            'lokasi_rak_id' => $data['lokasi_rak_id'] ?? null,
            'kondisi' => $data['kondisi'] ?? 'Baik',
            'harga' => $data['harga_perolehan'] ?? 0
        ]);

        return $id;
    }

    public function getEksemplarByBarcode(string $tenantId, string $barcode): ?array {
        $stmt = $this->db->prepare("SELECT e.*, b.judul, b.penulis, b.klasifikasi_ddc, b.nomor_panggil, r.nama as lokasi_nama
            FROM perpus_eksemplar e
            JOIN perpus_bibliografi b ON e.bibliografi_id = b.id
            LEFT JOIN perpus_lokasi_rak r ON e.lokasi_rak_id = r.id
            WHERE e.barcode = :barcode AND e.tenant_id = :tenant_id LIMIT 1");
        $stmt->execute(['barcode' => $barcode, 'tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // -------------------------------------------------------------------------
    // 3. KEANGGOTAAN & BEBAS PUSTAKA
    // -------------------------------------------------------------------------

    public function getAnggotaByNo(string $tenantId, string $noAnggota): ?array {
        $stmt = $this->db->prepare("SELECT a.*, s.nama_lengkap as nama_siswa, s.nisn as nisn_siswa
            FROM perpus_anggota a
            LEFT JOIN siswa s ON a.siswa_id = s.id
            WHERE a.no_anggota = :no AND a.tenant_id = :tenant_id LIMIT 1");
        $stmt->execute(['no' => $noAnggota, 'tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function syncAnggotaSiswa(string $tenantId): int {
        // Auto register all active students as library members if not exist yet
        $stmt = $this->db->prepare("SELECT s.id, s.nisn, s.nama_lengkap FROM siswa s 
            LEFT JOIN perpus_anggota a ON s.id = a.siswa_id AND a.tenant_id = :tenant_id
            WHERE s.tenant_id = :tenant_id AND s.status = 'Aktif' AND a.id IS NULL");
        $stmt->execute(['tenant_id' => $tenantId]);
        $unregistered = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        $stmtInsert = $this->db->prepare("INSERT INTO perpus_anggota (id, tenant_id, tipe_anggota, siswa_id, nisn, no_anggota, status)
            VALUES (:id, :tenant_id, 'Siswa', :siswa_id, :nisn, :no_anggota, 'Aktif')");

        foreach ($unregistered as $s) {
            $id = $this->generateUuid();
            $noAnggota = 'LIB-' . ($s['nisn'] ?: rand(100000, 999999));
            try {
                $stmtInsert->execute([
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'siswa_id' => $s['id'],
                    'nisn' => $s['nisn'],
                    'no_anggota' => $noAnggota
                ]);
                $count++;
            } catch (Exception $e) {
                // Ignore duplicate
            }
        }

        return $count;
    }

    public function cekBebasPustaka(string $tenantId, string $siswaId): array {
        // 1. Check sirkulasi reguler aktif
        $stmtReg = $this->db->prepare("SELECT s.*, b.judul 
            FROM perpus_sirkulasi s
            JOIN perpus_anggota a ON s.anggota_id = a.id
            JOIN perpus_eksemplar e ON s.eksemplar_id = e.id
            JOIN perpus_bibliografi b ON e.bibliografi_id = b.id
            WHERE a.siswa_id = :siswa_id AND s.tenant_id = :tenant_id AND s.status IN ('Dipinjam','Terlambat')");
        $stmtReg->execute(['siswa_id' => $siswaId, 'tenant_id' => $tenantId]);
        $reguler = $stmtReg->fetchAll(PDO::FETCH_ASSOC);

        // 2. Check buku paket aktif
        $stmtPaket = $this->db->prepare("SELECT d.*, b.judul 
            FROM perpus_distribusi_paket d
            JOIN perpus_bibliografi b ON d.bibliografi_id = b.id
            WHERE d.siswa_id = :siswa_id AND d.tenant_id = :tenant_id AND d.status = 'Dipinjam'");
        $stmtPaket->execute(['siswa_id' => $siswaId, 'tenant_id' => $tenantId]);
        $paket = $stmtPaket->fetchAll(PDO::FETCH_ASSOC);

        // 3. Check denda belum bayar
        $stmtDenda = $this->db->prepare("SELECT d.* 
            FROM perpus_denda d
            JOIN perpus_anggota a ON d.anggota_id = a.id
            WHERE a.siswa_id = :siswa_id AND d.tenant_id = :tenant_id AND d.status = 'Belum Dibayar'");
        $stmtDenda->execute(['siswa_id' => $siswaId, 'tenant_id' => $tenantId]);
        $denda = $stmtDenda->fetchAll(PDO::FETCH_ASSOC);

        $lunas = empty($reguler) && empty($paket) && empty($denda);

        return [
            'bebas_pustaka' => $lunas,
            'pinjaman_reguler' => $reguler,
            'pinjaman_paket' => $paket,
            'denda_tanggungan' => $denda
        ];
    }

    // -------------------------------------------------------------------------
    // 4. SIRKULASI REGULER
    // -------------------------------------------------------------------------

    public function prosesPinjamReguler(string $tenantId, string $anggotaId, string $eksemplarId, string $pustakawanId, int $durasiHari = 7): array {
        $this->db->beginTransaction();
        try {
            // Cek status eksemplar
            $stmtEks = $this->db->prepare("SELECT * FROM perpus_eksemplar WHERE id = :id AND tenant_id = :tenant_id FOR UPDATE");
            $stmtEks->execute(['id' => $eksemplarId, 'tenant_id' => $tenantId]);
            $eks = $stmtEks->fetch(PDO::FETCH_ASSOC);

            if (!$eks || $eks['status'] !== 'Tersedia') {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Buku tidak dalam status Tersedia untuk dipinjam.'];
            }

            // Cek limit anggota
            $stmtAng = $this->db->prepare("SELECT * FROM perpus_anggota WHERE id = :id AND tenant_id = :tenant_id");
            $stmtAng->execute(['id' => $anggotaId, 'tenant_id' => $tenantId]);
            $ang = $stmtAng->fetch(PDO::FETCH_ASSOC);

            if (!$ang || $ang['status'] !== 'Aktif') {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Status keanggotaan tidak aktif / dibekukan.'];
            }

            // Hitung peminjaman aktif
            $stmtActiveCount = $this->db->prepare("SELECT COUNT(*) FROM perpus_sirkulasi WHERE anggota_id = :aid AND status IN ('Dipinjam','Terlambat')");
            $stmtActiveCount->execute(['aid' => $anggotaId]);
            $activeCount = (int)$stmtActiveCount->fetchColumn();

            if ($activeCount >= (int)$ang['limit_pinjam_reguler']) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Anggota telah mencapai limit maksimal peminjaman reguler.'];
            }

            $noTransaksi = 'CIRC-' . date('Ymd') . '-' . rand(1000, 9999);
            $tglPinjam = date('Y-m-d');
            $tglKembaliRencana = date('Y-m-d', strtotime("+{$durasiHari} days"));
            $circId = $this->generateUuid();

            // Insert sirkulasi
            $stmtIns = $this->db->prepare("INSERT INTO perpus_sirkulasi 
                (id, tenant_id, no_transaksi, anggota_id, eksemplar_id, pustakawan_id, tanggal_pinjam, tanggal_kembali_rencana, status)
                VALUES (:id, :tenant_id, :no_transaksi, :anggota_id, :eksemplar_id, :pustakawan_id, :tgl_pinjam, :tgl_kembali, 'Dipinjam')");
            $stmtIns->execute([
                'id' => $circId,
                'tenant_id' => $tenantId,
                'no_transaksi' => $noTransaksi,
                'anggota_id' => $anggotaId,
                'eksemplar_id' => $eksemplarId,
                'pustakawan_id' => $pustakawanId,
                'tgl_pinjam' => $tglPinjam,
                'tgl_kembali' => $tglKembaliRencana
            ]);

            // Update status eksemplar
            $stmtUpdEks = $this->db->prepare("UPDATE perpus_eksemplar SET status = 'Dipinjam Reguler' WHERE id = :id");
            $stmtUpdEks->execute(['id' => $eksemplarId]);

            $this->db->commit();
            return [
                'success' => true,
                'no_transaksi' => $noTransaksi,
                'tanggal_kembali' => $tglKembaliRencana
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function prosesKembaliReguler(string $tenantId, string $sirkulasiId, float $tarifDendaPerHari = 500): array {
        $this->db->beginTransaction();
        try {
            $stmtSirk = $this->db->prepare("SELECT * FROM perpus_sirkulasi WHERE id = :id AND tenant_id = :tenant_id FOR UPDATE");
            $stmtSirk->execute(['id' => $sirkulasiId, 'tenant_id' => $tenantId]);
            $sirk = $stmtSirk->fetch(PDO::FETCH_ASSOC);

            if (!$sirk || $sirk['status'] === 'Dikembalikan') {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Transaksi sirkulasi tidak ditemukan atau sudah dikembalikan.'];
            }

            $tglAktual = date('Y-m-d');
            $tglRencana = $sirk['tanggal_kembali_rencana'];
            $denda = 0;
            $hariTerlambat = 0;

            if ($tglAktual > $tglRencana) {
                $diff = (strtotime($tglAktual) - strtotime($tglRencana)) / (60 * 60 * 24);
                $hariTerlambat = (int)$diff;
                $denda = $hariTerlambat * $tarifDendaPerHari;
            }

            // Update status sirkulasi
            $stmtUpdSirk = $this->db->prepare("UPDATE perpus_sirkulasi SET status = 'Dikembalikan', tanggal_kembali_aktual = :tgl WHERE id = :id");
            $stmtUpdSirk->execute(['tgl' => $tglAktual, 'id' => $sirkulasiId]);

            // Update status eksemplar
            $stmtUpdEks = $this->db->prepare("UPDATE perpus_eksemplar SET status = 'Tersedia' WHERE id = :id");
            $stmtUpdEks->execute(['id' => $sirk['eksemplar_id']]);

            // If denda, create record
            $dendaId = null;
            if ($denda > 0) {
                $dendaId = $this->generateUuid();
                $stmtDenda = $this->db->prepare("INSERT INTO perpus_denda
                    (id, tenant_id, sumber_transaksi, sirkulasi_id, anggota_id, jenis_denda, jumlah_hari, nominal_per_hari, total_denda, status)
                    VALUES (:id, :tenant_id, 'Sirkulasi Reguler', :sirkulasi_id, :anggota_id, 'Keterlambatan', :jumlah_hari, :nominal_per_hari, :total_denda, 'Belum Dibayar')");
                $stmtDenda->execute([
                    'id' => $dendaId,
                    'tenant_id' => $tenantId,
                    'sirkulasi_id' => $sirkulasiId,
                    'anggota_id' => $sirk['anggota_id'],
                    'jumlah_hari' => $hariTerlambat,
                    'nominal_per_hari' => $tarifDendaPerHari,
                    'total_denda' => $denda
                ]);
            }

            $this->db->commit();
            return [
                'success' => true,
                'hari_terlambat' => $hariTerlambat,
                'total_denda' => $denda,
                'denda_id' => $dendaId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // 5. OPAC PUBLIK & BUKU TAMU
    // -------------------------------------------------------------------------

    public function searchOpacPublic(string $tenantId, string $query, int $limit = 20, int $offset = 0): array {
        $sql = "SELECT b.id, b.isbn, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.klasifikasi_ddc, b.nomor_panggil, b.cover, b.jenis_buku,
            COUNT(e.id) as total_eksemplar,
            SUM(CASE WHEN e.status = 'Tersedia' THEN 1 ELSE 0 END) as total_tersedia
            FROM perpus_bibliografi b
            LEFT JOIN perpus_eksemplar e ON b.id = e.bibliografi_id
            WHERE b.tenant_id = :tenant_id AND b.status_opac = 1 AND b.deleted_at IS NULL";

        $params = ['tenant_id' => $tenantId];

        if ($query !== '') {
            $sql .= " AND (b.judul LIKE :q OR b.penulis LIKE :q OR b.isbn LIKE :q OR b.klasifikasi_ddc LIKE :q)";
            $params['q'] = '%' . $query . '%';
        }

        $sql .= " GROUP BY b.id ORDER BY b.judul ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertBukuTamu(string $tenantId, array $data): bool {
        $stmt = $this->db->prepare("INSERT INTO perpus_buku_tamu
            (id, tenant_id, nisn, nama_pengunjung, kelas, tujuan, tanggal, jam_masuk)
            VALUES (:id, :tenant_id, :nisn, :nama, :kelas, :tujuan, :tanggal, :jam)");

        return $stmt->execute([
            'id' => $this->generateUuid(),
            'tenant_id' => $tenantId,
            'nisn' => $data['nisn'] ?? null,
            'nama' => $data['nama_pengunjung'],
            'kelas' => $data['kelas'] ?? null,
            'tujuan' => $data['tujuan'] ?? 'Membaca',
            'tanggal' => date('Y-m-d'),
            'jam' => date('H:i:s')
        ]);
    }

    // -------------------------------------------------------------------------
    // 6. MANAJEMEN RAK FISIK (SHELVING)
    // -------------------------------------------------------------------------

    public function getLokasiRakList(string $tenantId): array {
        $stmt = $this->db->prepare("SELECT r.*, COUNT(e.id) as total_buku 
            FROM perpus_lokasi_rak r
            LEFT JOIN perpus_eksemplar e ON r.id = e.lokasi_rak_id
            WHERE r.tenant_id = :tenant_id 
            GROUP BY r.id ORDER BY r.kode ASC");
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveLokasiRak(string $tenantId, array $data, ?string $id = null): string {
        if ($id) {
            $stmt = $this->db->prepare("UPDATE perpus_lokasi_rak SET
                kode = :kode, nama = :nama, gedung = :gedung, lantai = :lantai, ruangan = :ruangan,
                nama_rak = :nama_rak, baris = :baris, kapasitas = :kapasitas, ddc_mulai = :ddc_mulai, ddc_selesai = :ddc_selesai, keterangan = :keterangan
                WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute([
                'kode' => $data['kode'],
                'nama' => $data['nama'],
                'gedung' => $data['gedung'] ?? null,
                'lantai' => $data['lantai'] ?? null,
                'ruangan' => $data['ruangan'] ?? null,
                'nama_rak' => $data['nama_rak'] ?? null,
                'baris' => $data['baris'] ?? null,
                'kapasitas' => (int)($data['kapasitas'] ?? 50),
                'ddc_mulai' => $data['ddc_mulai'] ?? null,
                'ddc_selesai' => $data['ddc_selesai'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            return $id;
        } else {
            $newId = $this->generateUuid();
            $stmt = $this->db->prepare("INSERT INTO perpus_lokasi_rak
                (id, tenant_id, kode, nama, gedung, lantai, ruangan, nama_rak, baris, kapasitas, ddc_mulai, ddc_selesai, keterangan)
                VALUES (:id, :tenant_id, :kode, :nama, :gedung, :lantai, :ruangan, :nama_rak, :baris, :kapasitas, :ddc_mulai, :ddc_selesai, :keterangan)");
            $stmt->execute([
                'id' => $newId,
                'tenant_id' => $tenantId,
                'kode' => $data['kode'],
                'nama' => $data['nama'],
                'gedung' => $data['gedung'] ?? null,
                'lantai' => $data['lantai'] ?? null,
                'ruangan' => $data['ruangan'] ?? null,
                'nama_rak' => $data['nama_rak'] ?? null,
                'baris' => $data['baris'] ?? null,
                'kapasitas' => (int)($data['kapasitas'] ?? 50),
                'ddc_mulai' => $data['ddc_mulai'] ?? null,
                'ddc_selesai' => $data['ddc_selesai'] ?? null,
                'keterangan' => $data['keterangan'] ?? null
            ]);
            return $newId;
        }
    }

    // -------------------------------------------------------------------------
    // 7. BUKU PAKET (DISTRIBUSI MASSAL PER KELAS)
    // -------------------------------------------------------------------------

    public function createPaketBuku(string $tenantId, array $data, string $createdBy): string {
        $id = $this->generateUuid();
        $stmt = $this->db->prepare("INSERT INTO perpus_paket_buku
            (id, tenant_id, nama_paket, kelas_id, jenjang, jurusan, tahun_ajaran_id, semester, durasi_pinjam, tanggal_mulai, tanggal_selesai, status, keterangan, created_by)
            VALUES (:id, :tenant_id, :nama, :kelas_id, :jenjang, :jurusan, :ta_id, :semester, :durasi, :tgl_mulai, :tgl_selesai, 'Draft', :ket, :created_by)");
        
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'nama' => $data['nama_paket'],
            'kelas_id' => $data['kelas_id'] ?? null,
            'jenjang' => $data['jenjang'] ?? null,
            'jurusan' => $data['jurusan'] ?? null,
            'ta_id' => $data['tahun_ajaran_id'],
            'semester' => (int)($data['semester'] ?? 1),
            'durasi' => $data['durasi_pinjam'] ?? '1 Semester',
            'tgl_mulai' => $data['tanggal_mulai'] ?? date('Y-m-d'),
            'tgl_selesai' => $data['tanggal_selesai'] ?? date('Y-m-d', strtotime('+180 days')),
            'ket' => $data['keterangan'] ?? null,
            'created_by' => $createdBy
        ]);

        return $id;
    }

    public function addPaketItem(string $paketId, string $bibliografiId, string $mataPelajaran): string {
        $id = $this->generateUuid();
        $stmt = $this->db->prepare("INSERT INTO perpus_paket_item (id, paket_id, bibliografi_id, mata_pelajaran) VALUES (:id, :pid, :bid, :mapel)");
        $stmt->execute([
            'id' => $id,
            'pid' => $paketId,
            'bid' => $bibliografiId,
            'mapel' => $mataPelajaran
        ]);
        return $id;
    }

    // -------------------------------------------------------------------------
    // 8. EVENT KHUSUS (OSN, OLIMPIADE)
    // -------------------------------------------------------------------------

    public function createEventPinjam(string $tenantId, array $data, string $createdBy): string {
        $id = $this->generateUuid();
        $stmt = $this->db->prepare("INSERT INTO perpus_event_pinjam
            (id, tenant_id, nama_event, kategori, tanggal_mulai, tanggal_selesai, penanggung_jawab, keterangan, created_by)
            VALUES (:id, :tenant_id, :nama, :kategori, :mulai, :selesai, :pj, :ket, :created_by)");
        
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'nama' => $data['nama_event'],
            'kategori' => $data['kategori'] ?? 'OSN',
            'mulai' => $data['tanggal_mulai'] ?? date('Y-m-d'),
            'selesai' => $data['tanggal_selesai'] ?? date('Y-m-d', strtotime('+30 days')),
            'pj' => $data['penanggung_jawab'] ?? null,
            'ket' => $data['keterangan'] ?? null,
            'created_by' => $createdBy
        ]);

        return $id;
    }

    // -------------------------------------------------------------------------
    // 9. DENDA & INTEGRASI BILLING SPP
    // -------------------------------------------------------------------------

    public function getDendaList(string $tenantId, string $status = 'Belum Dibayar'): array {
        $stmt = $this->db->prepare("SELECT d.*, a.no_anggota, s.nama_lengkap as nama_siswa
            FROM perpus_denda d
            JOIN perpus_anggota a ON d.anggota_id = a.id
            LEFT JOIN siswa s ON a.siswa_id = s.id
            WHERE d.tenant_id = :tenant_id AND d.status = :status
            ORDER BY d.created_at DESC");
        $stmt->execute(['tenant_id' => $tenantId, 'status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function bayarDendaTunai(string $tenantId, string $dendaId, string $dibayarOleh): bool {
        $stmt = $this->db->prepare("UPDATE perpus_denda SET 
            status = 'Dibayar Tunai', 
            dibayar_at = CURRENT_TIMESTAMP, 
            dibayar_oleh = :dibayar_oleh 
            WHERE id = :id AND tenant_id = :tenant_id");
        return $stmt->execute([
            'dibayar_oleh' => $dibayarOleh,
            'id' => $dendaId,
            'tenant_id' => $tenantId
        ]);
    }

    // -------------------------------------------------------------------------
    // 10. DASHBOARD SUMMARY STATISTIK
    // -------------------------------------------------------------------------

    public function getDashboardSummary(string $tenantId): array {
        // Total Judul
        $stmtB = $this->db->prepare("SELECT COUNT(*) FROM perpus_bibliografi WHERE tenant_id = :tid AND deleted_at IS NULL");
        $stmtB->execute(['tid' => $tenantId]);
        $totalJudul = (int)$stmtB->fetchColumn();

        // Total Eksemplar & Tersedia
        $stmtE = $this->db->prepare("SELECT COUNT(*) as total, 
            SUM(CASE WHEN status = 'Tersedia' THEN 1 ELSE 0 END) as tersedia,
            SUM(CASE WHEN status LIKE 'Dipinjam%' THEN 1 ELSE 0 END) as dipinjam
            FROM perpus_eksemplar WHERE tenant_id = :tid");
        $stmtE->execute(['tid' => $tenantId]);
        $eksStats = $stmtE->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'tersedia' => 0, 'dipinjam' => 0];

        // Total Anggota Active
        $stmtA = $this->db->prepare("SELECT COUNT(*) FROM perpus_anggota WHERE tenant_id = :tid AND status = 'Aktif'");
        $stmtA->execute(['tid' => $tenantId]);
        $totalAnggota = (int)$stmtA->fetchColumn();

        // Total Kunjungan Hari Ini
        $stmtK = $this->db->prepare("SELECT COUNT(*) FROM perpus_buku_tamu WHERE tenant_id = :tid AND tanggal = CURRENT_DATE()");
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
    }

    // -------------------------------------------------------------------------
    // 11. ULASAN, RATING & DUTA BACA LEADERBOARD
    // -------------------------------------------------------------------------

    public function saveUlasan(string $tenantId, string $bibliografiId, string $anggotaId, int $rating, string $ulasan): string {
        $id = $this->generateUuid();
        $stmt = $this->db->prepare("INSERT INTO perpus_ulasan
            (id, tenant_id, bibliografi_id, anggota_id, rating, ulasan, status)
            VALUES (:id, :tenant_id, :bid, :aid, :rating, :ulasan, 'Disetujui')");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'bid' => $bibliografiId,
            'aid' => $anggotaId,
            'rating' => max(1, min(5, $rating)),
            'ulasan' => strip_tags($ulasan)
        ]);
        return $id;
    }

    public function getDutaBacaLeaderboard(string $tenantId, int $limit = 10): array {
        $stmt = $this->db->prepare("SELECT a.id, a.no_anggota, s.nama_lengkap as nama_siswa,
            COUNT(sirk.id) as total_pinjam
            FROM perpus_anggota a
            JOIN siswa s ON a.siswa_id = s.id
            JOIN perpus_sirkulasi sirk ON a.id = sirk.anggota_id
            WHERE a.tenant_id = :tenant_id
            GROUP BY a.id ORDER BY total_pinjam DESC LIMIT " . (int)$limit);
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // 12. CRON NOTIFICATION REMINDER WITH TOGGLE CHECK
    // -------------------------------------------------------------------------

    public function runCronNotifReminder(string $tenantId): array {
        $pengaturan = $this->getPengaturan($tenantId);
        $waAktif = (int)($pengaturan['auto_notif_wa_aktif'] ?? 1);
        $emailAktif = (int)($pengaturan['auto_notif_email_aktif'] ?? 0);

        if (!$waAktif && !$emailAktif) {
            return [
                'success' => true,
                'message' => 'Pengiriman notifikasi otomatis WhatsApp & Email ditonaktifkan (OFF) untuk tenant ini.',
                'processed' => 0
            ];
        }

        // Cari transaksi sirkulasi H-2 jatuh tempo
        $stmtDue = $this->db->prepare("SELECT s.*, a.no_anggota, a.siswa_id, b.judul
            FROM perpus_sirkulasi s
            JOIN perpus_anggota a ON s.anggota_id = a.id
            JOIN perpus_eksemplar e ON s.eksemplar_id = e.id
            JOIN perpus_bibliografi b ON e.bibliografi_id = b.id
            WHERE s.tenant_id = :tenant_id 
              AND s.status = 'Dipinjam'
              AND s.tanggal_kembali_rencana = DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY)");
        $stmtDue->execute(['tenant_id' => $tenantId]);
        $dueItems = $stmtDue->fetchAll(PDO::FETCH_ASSOC);

        $processed = 0;
        $stmtLog = $this->db->prepare("INSERT INTO perpus_notifikasi_log
            (id, tenant_id, anggota_id, media, tujuan, pesan, status)
            VALUES (:id, :tid, :aid, :media, :tujuan, :pesan, 'Terkirim')");

        foreach ($dueItems as $item) {
            $pesan = "Peringatan H-2 Perpustakaan: Buku '" . $item['judul'] . "' jatuh tempo pada " . $item['tanggal_kembali_rencana'] . ". Mohon segera dikembalikan.";
            
            if ($waAktif) {
                $stmtLog->execute([
                    'id' => $this->generateUuid(),
                    'tid' => $tenantId,
                    'aid' => $item['anggota_id'],
                    'media' => 'WhatsApp',
                    'tujuan' => $item['no_anggota'],
                    'pesan' => $pesan
                ]);
                $processed++;
            }
        }

        return [
            'success' => true,
            'message' => "Cron reminder selesai diproses. Total notifikasi terkirim: {$processed}.",
            'processed' => $processed,
            'wa_status' => $waAktif ? 'ON' : 'OFF',
            'email_status' => $emailAktif ? 'ON' : 'OFF'
        ];
    }

    // -------------------------------------------------------------------------
    // UTILITY HELPER
    // -------------------------------------------------------------------------

    private function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

