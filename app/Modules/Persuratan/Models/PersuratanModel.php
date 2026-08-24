<?php

namespace App\Modules\Persuratan\Models;

use App\Config\Database;
use PDO;
use Exception;

class PersuratanModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Mengambil statistik ringkas dashboard persuratan
     */
    public function getDashboardStats(string $tenantId): array
    {
        $globalTenant = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';

        // 1. Total Surat Masuk
        $stmtSm = $this->db->prepare("SELECT COUNT(*) FROM persuratan.surat_masuk WHERE (tenant_id = :tid OR tenant_id = :gtid) AND is_active = TRUE");
        $stmtSm->execute([':tid' => $tenantId, ':gtid' => $globalTenant]);
        $totalSuratMasuk = (int)$stmtSm->fetchColumn();

        // 2. Total Surat Keluar
        $stmtSk = $this->db->prepare("SELECT COUNT(*) FROM persuratan.surat_keluar WHERE (tenant_id = :tid OR tenant_id = :gtid) AND is_active = TRUE");
        $stmtSk->execute([':tid' => $tenantId, ':gtid' => $globalTenant]);
        $totalSuratKeluar = (int)$stmtSk->fetchColumn();

        // 3. Disposisi Pending
        $stmtDisp = $this->db->prepare("SELECT COUNT(*) FROM persuratan.disposisi_surat WHERE (tenant_id = :tid OR tenant_id = :gtid) AND status = 'Pending' AND is_active = TRUE");
        $stmtDisp->execute([':tid' => $tenantId, ':gtid' => $globalTenant]);
        $disposisiPending = (int)$stmtDisp->fetchColumn();

        // 4. Antrean Pengajuan Surat Panggilan BK Pending
        $stmtBk = $this->db->prepare("SELECT COUNT(*) FROM persuratan.pengajuan_surat_bk WHERE (tenant_id = :tid OR tenant_id = :gtid) AND status_pengajuan = 'Menunggu Penerbitan TU' AND is_active = TRUE");
        $stmtBk->execute([':tid' => $tenantId, ':gtid' => $globalTenant]);
        $pengajuanBkPending = (int)$stmtBk->fetchColumn();

        // 5. Data Chart Bulanan (6 Bulan Terakhir)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthTime = strtotime("-$i month");
            $monthNum  = date('m', $monthTime);
            $yearNum   = date('Y', $monthTime);
            $monthName = date('M Y', $monthTime);

            $stmtCountSm = $this->db->prepare("
                SELECT COUNT(*) FROM persuratan.surat_masuk 
                WHERE (tenant_id = :tid OR tenant_id = :gtid) AND is_active = TRUE
                  AND EXTRACT(MONTH FROM COALESCE(tgl_terima, tgl_surat, created_at)) = :m
                  AND EXTRACT(YEAR FROM COALESCE(tgl_terima, tgl_surat, created_at)) = :y
            ");
            $stmtCountSm->execute([':tid' => $tenantId, ':gtid' => $globalTenant, ':m' => $monthNum, ':y' => $yearNum]);
            $countSm = (int)$stmtCountSm->fetchColumn();

            $stmtCountSk = $this->db->prepare("
                SELECT COUNT(*) FROM persuratan.surat_keluar 
                WHERE (tenant_id = :tid OR tenant_id = :gtid) AND is_active = TRUE
                  AND EXTRACT(MONTH FROM COALESCE(tgl_surat, created_at)) = :m
                  AND EXTRACT(YEAR FROM COALESCE(tgl_surat, created_at)) = :y
            ");
            $stmtCountSk->execute([':tid' => $tenantId, ':gtid' => $globalTenant, ':m' => $monthNum, ':y' => $yearNum]);
            $countSk = (int)$stmtCountSk->fetchColumn();

            $chartData[] = [
                'bulan' => $monthName,
                'surat_masuk' => $countSm,
                'surat_keluar' => $countSk
            ];
        }

        return [
            'total_surat_masuk' => $totalSuratMasuk,
            'total_surat_keluar' => $totalSuratKeluar,
            'disposisi_pending' => $disposisiPending,
            'pengajuan_bk_pending' => $pengajuanBkPending,
            'chart_data' => $chartData
        ];
    }

    /**
     * Mengambil daftar Surat Masuk dengan filter dan pagination
     */
    public function getSuratMasuk(string $tenantId, array $filters = []): array
    {
        $globalTenant = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
        $params = [':tid' => $tenantId, ':gtid' => $globalTenant];
        $where = ["(sm.tenant_id = :tid OR sm.tenant_id = :gtid)", "sm.is_active = TRUE"];

        if (!empty($filters['search'])) {
            $where[] = "(sm.no_surat ILIKE :search OR sm.pengirim ILIKE :search OR sm.perihal ILIKE :search OR sm.no_agenda ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status_disposisi'])) {
            $where[] = "sm.status_disposisi = :status";
            $params[':status'] = $filters['status_disposisi'];
        }

        if (!empty($filters['tgl_mulai']) && !empty($filters['tgl_selesai'])) {
            $where[] = "sm.tgl_terima BETWEEN :tgl_mulai AND :tgl_selesai";
            $params[':tgl_mulai'] = $filters['tgl_mulai'];
            $params[':tgl_selesai'] = $filters['tgl_selesai'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "
            SELECT 
                sm.id, sm.tenant_id, sm.no_agenda, sm.no_surat, sm.pengirim, sm.perihal,
                sm.tgl_surat, sm.tgl_terima, sm.ringkasan_isi, sm.file_lampiran,
                sm.status_disposisi, sm.tingkat_keamanan, sm.sifat_surat, sm.created_at,
                (SELECT COUNT(*) FROM persuratan.disposisi_surat ds WHERE ds.id_surat_masuk = sm.id AND ds.is_active = TRUE) AS total_disposisi
            FROM persuratan.surat_masuk sm
            WHERE {$whereClause}
            ORDER BY sm.tgl_terima DESC, sm.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Menyimpan / Mengupdate Surat Masuk
     */
    public function saveSuratMasuk(string $tenantId, array $data): string
    {
        $id = $data['id'] ?? null;
        $noSurat = trim($data['no_surat'] ?? '');
        $pengirim = trim($data['pengirim'] ?? '');
        $perihal = trim($data['perihal'] ?? '');
        $tglSurat = !empty($data['tgl_surat']) ? $data['tgl_surat'] : date('Y-m-d');
        $tglTerima = !empty($data['tgl_terima']) ? $data['tgl_terima'] : date('Y-m-d');
        $ringkasan = trim($data['ringkasan_isi'] ?? '');
        $fileLampiran = $data['file_lampiran'] ?? null;
        $sifat = $data['sifat_surat'] ?? 'Biasa';
        $keamanan = $data['tingkat_keamanan'] ?? 'Biasa';

        if (empty($noSurat) || empty($pengirim) || empty($perihal)) {
            throw new Exception('Nomor surat, pengirim, dan perihal wajib diisi.');
        }

        if (empty($id)) {
            // Auto generate no agenda jika kosong
            $stmtCount = $this->db->prepare("
                SELECT COUNT(*) FROM persuratan.surat_masuk 
                WHERE (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
                  AND EXTRACT(YEAR FROM COALESCE(tgl_terima, CURRENT_DATE)) = EXTRACT(YEAR FROM CURRENT_DATE)
            ");
            $stmtCount->execute([':tid' => $tenantId]);
            $seq = ((int)$stmtCount->fetchColumn()) + 1;
            $noAgenda = 'AGM-' . date('Y') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

            $stmt = $this->db->prepare("
                INSERT INTO persuratan.surat_masuk (
                    id, tenant_id, no_agenda, no_surat, pengirim, perihal,
                    tgl_surat, tgl_terima, ringkasan_isi, file_lampiran,
                    status_disposisi, tingkat_keamanan, sifat_surat, is_active, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), :tid, :no_agenda, :no_surat, :pengirim, :perihal,
                    :tgl_surat, :tgl_terima, :ringkasan, :file_lampiran,
                    'Menunggu Disposisi', :keamanan, :sifat, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                ) RETURNING id
            ");
            $stmt->execute([
                ':tid' => $tenantId,
                ':no_agenda' => $noAgenda,
                ':no_surat' => $noSurat,
                ':pengirim' => $pengirim,
                ':perihal' => $perihal,
                ':tgl_surat' => $tglSurat,
                ':tgl_terima' => $tglTerima,
                ':ringkasan' => $ringkasan,
                ':file_lampiran' => $fileLampiran,
                ':keamanan' => $keamanan,
                ':sifat' => $sifat,
            ]);
            return (string)$stmt->fetchColumn();
        } else {
            $stmt = $this->db->prepare("
                UPDATE persuratan.surat_masuk SET
                    no_surat = :no_surat,
                    pengirim = :pengirim,
                    perihal = :perihal,
                    tgl_surat = :tgl_surat,
                    tgl_terima = :tgl_terima,
                    ringkasan_isi = :ringkasan,
                    tingkat_keamanan = :keamanan,
                    sifat_surat = :sifat,
                    " . ($fileLampiran ? "file_lampiran = :file_lampiran," : "") . "
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
            ");
            $binds = [
                ':id' => $id,
                ':tid' => $tenantId,
                ':no_surat' => $noSurat,
                ':pengirim' => $pengirim,
                ':perihal' => $perihal,
                ':tgl_surat' => $tglSurat,
                ':tgl_terima' => $tglTerima,
                ':ringkasan' => $ringkasan,
                ':keamanan' => $keamanan,
                ':sifat' => $sifat,
            ];
            if ($fileLampiran) {
                $binds[':file_lampiran'] = $fileLampiran;
            }
            $stmt->execute($binds);
            return $id;
        }
    }

    /**
     * Hapus Surat Masuk (Soft Delete)
     */
    public function deleteSuratMasuk(string $tenantId, string $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE persuratan.surat_masuk 
            SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
        ");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

    /**
     * Ambil riwayat disposisi surat masuk
     */
    public function getDisposisi(string $tenantId, string $idSuratMasuk): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                ds.id, ds.id_surat_masuk, ds.nama_pemberi_disposisi, ds.nama_penerima_disposisi,
                ds.instruksi_disposisi, ds.catatan, ds.tgl_disposisi, ds.batas_waktu, ds.status, ds.created_at
            FROM persuratan.disposisi_surat ds
            WHERE ds.id_surat_masuk = :id_sm AND (ds.tenant_id = :tid OR ds.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') AND ds.is_active = TRUE
            ORDER BY ds.created_at DESC
        ");
        $stmt->execute([':id_sm' => $idSuratMasuk, ':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Simpan lembar disposisi baru
     */
    public function saveDisposisi(string $tenantId, array $data): string
    {
        $idSuratMasuk = $data['id_surat_masuk'] ?? '';
        $instruksi = trim($data['instruksi_disposisi'] ?? '');
        $pemberiNama = trim($data['nama_pemberi_disposisi'] ?? 'Kepala Sekolah');
        $pemberiId = $data['pemberi_disposisi_id'] ?? null;
        $penerimaNama = trim($data['nama_penerima_disposisi'] ?? 'Wakasek / Guru');
        $penerimaId = $data['penerima_disposisi_id'] ?? null;
        $catatan = trim($data['catatan'] ?? '');
        $tglDisposisi = !empty($data['tgl_disposisi']) ? $data['tgl_disposisi'] : date('Y-m-d');
        $batasWaktu = !empty($data['batas_waktu']) ? $data['batas_waktu'] : null;

        if (empty($idSuratMasuk) || empty($instruksi)) {
            throw new Exception('Surat masuk dan instruksi disposisi wajib diisi.');
        }

        $stmt = $this->db->prepare("
            INSERT INTO persuratan.disposisi_surat (
                id, tenant_id, id_surat_masuk, pemberi_disposisi_id, nama_pemberi_disposisi,
                penerima_disposisi_id, nama_penerima_disposisi, instruksi_disposisi,
                catatan, tgl_disposisi, batas_waktu, status, is_active, created_at, updated_at
            ) VALUES (
                gen_random_uuid(), :tid, :id_sm, :p_id, :p_nama,
                :r_id, :r_nama, :instruksi,
                :catatan, :tgl_disp, :batas, 'Pending', TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            ) RETURNING id
        ");
        $stmt->execute([
            ':tid' => $tenantId,
            ':id_sm' => $idSuratMasuk,
            ':p_id' => $pemberiId,
            ':p_nama' => $pemberiNama,
            ':r_id' => $penerimaId,
            ':r_nama' => $penerimaNama,
            ':instruksi' => $instruksi,
            ':catatan' => $catatan,
            ':tgl_disp' => $tglDisposisi,
            ':batas' => $batasWaktu,
        ]);
        $dispId = (string)$stmt->fetchColumn();

        // Update status di surat masuk menjadi 'Didisposisikan'
        $stmtUpdateSm = $this->db->prepare("
            UPDATE persuratan.surat_masuk 
            SET status_disposisi = 'Didisposisikan', updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
        ");
        $stmtUpdateSm->execute([':id' => $idSuratMasuk, ':tid' => $tenantId]);

        return $dispId;
    }

    /**
     * Mengambil daftar Surat Keluar
     */
    public function getSuratKeluar(string $tenantId, array $filters = []): array
    {
        $globalTenant = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
        $params = [':tid' => $tenantId, ':gtid' => $globalTenant];
        $where = ["(sk.tenant_id = :tid OR sk.tenant_id = :gtid)", "sk.is_active = TRUE"];

        if (!empty($filters['search'])) {
            $where[] = "(sk.nomor_surat ILIKE :search OR sk.tujuan ILIKE :search OR sk.perihal ILIKE :search OR sk.no_agenda ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['id_kode_klasifikasi'])) {
            $where[] = "sk.id_kode_klasifikasi = :klas";
            $params[':klas'] = $filters['id_kode_klasifikasi'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "
            SELECT 
                sk.id, sk.tenant_id, sk.no_agenda, sk.nomor_surat, sk.id_kode_klasifikasi,
                sk.id_jenis_surat, sk.id_template, sk.tujuan, sk.perihal, sk.tgl_surat,
                sk.ringkasan_isi, sk.nama_pembuat, sk.nama_penandatangan, sk.jabatan_penandatangan,
                sk.status_surat, sk.id_referensi_modul, sk.nama_modul_referensi, sk.file_lampiran,
                sk.qr_token, sk.created_at,
                kk.kode_klasifikasi, kk.nama_klasifikasi,
                js.nama_jenis_surat
            FROM persuratan.surat_keluar sk
            LEFT JOIN persuratan.kode_klasifikasi_surat kk ON sk.id_kode_klasifikasi = kk.id
            LEFT JOIN persuratan.jenis_surat js ON sk.id_jenis_surat = js.id
            WHERE {$whereClause}
            ORDER BY sk.tgl_surat DESC, sk.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Auto-numbering generator untuk surat keluar
     */
    public function generateNomorSurat(string $tenantId, ?string $idKodeKlasifikasi = null, ?string $tglSurat = null): array
    {
        $date = !empty($tglSurat) ? strtotime($tglSurat) : time();
        $year = date('Y', $date);
        $month = (int)date('n', $date);

        $romanMonths = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        $monthRomawi = $romanMonths[$month] ?? 'I';

        // Ambil kode klasifikasi
        $kodeKlasifikasi = '421';
        if ($idKodeKlasifikasi) {
            $stmtK = $this->db->prepare("
                SELECT kode_klasifikasi FROM persuratan.kode_klasifikasi_surat 
                WHERE id::text = :id OR kode_klasifikasi = :id 
                LIMIT 1
            ");
            $stmtK->execute([':id' => $idKodeKlasifikasi]);
            $k = $stmtK->fetchColumn();
            if ($k) {
                $kodeKlasifikasi = $k;
            } else {
                $kodeKlasifikasi = $idKodeKlasifikasi;
            }
        }

        // Ambil kode sekolah / singkatan dari core.tenants
        $tenantCode = 'SMAN';
        try {
            $stmtT = $this->db->prepare("SELECT nama_sekolah, npsn FROM core.tenants WHERE id = :tid LIMIT 1");
            $stmtT->execute([':tid' => $tenantId]);
            $t = $stmtT->fetch(PDO::FETCH_ASSOC);
            if ($t && !empty($t['nama_sekolah'])) {
                // Buat singkatan nama sekolah (misal: SMAN 1 Surabaya -> SMAN1SBY)
                $words = preg_split('/\s+/', strtoupper(trim($t['nama_sekolah'])));
                $abbr = '';
                foreach ($words as $w) {
                    $abbr .= $w[0] ?? '';
                }
                $tenantCode = strlen($abbr) >= 2 ? $abbr : 'SMAN';
            }
        } catch (\Throwable $e) {}

        // Hitung urutan surat keluar tahun ini
        $stmtSeq = $this->db->prepare("
            SELECT COUNT(*) FROM persuratan.surat_keluar
            WHERE (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
              AND EXTRACT(YEAR FROM COALESCE(tgl_surat, CURRENT_DATE)) = :y
        ");
        $stmtSeq->execute([':tid' => $tenantId, ':y' => $year]);
        $seq = ((int)$stmtSeq->fetchColumn()) + 1;
        $nomorUrut = str_pad((string)$seq, 3, '0', STR_PAD_LEFT);

        $noAgenda = 'AGK-' . $year . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
        $nomorSurat = "{$nomorUrut}/{$kodeKlasifikasi}/{$tenantCode}/{$monthRomawi}/{$year}";

        return [
            'no_agenda' => $noAgenda,
            'nomor_surat' => $nomorSurat,
            'nomor_urut' => $nomorUrut,
            'kode_klasifikasi' => $kodeKlasifikasi,
            'tenant_code' => $tenantCode,
            'bulan_romawi' => $monthRomawi,
            'tahun' => $year
        ];
    }

    /**
     * Menyimpan / Mengupdate Surat Keluar TU
     */
    public function saveSuratKeluar(string $tenantId, array $data): string
    {
        $id = $data['id'] ?? null;
        $nomorSurat = trim($data['nomor_surat'] ?? '');
        $tujuan = trim($data['tujuan'] ?? '');
        $perihal = trim($data['perihal'] ?? '');
        $tglSurat = !empty($data['tgl_surat']) ? $data['tgl_surat'] : date('Y-m-d');
        $ringkasan = trim($data['ringkasan_isi'] ?? '');
        $idKodeKlas = !empty($data['id_kode_klasifikasi']) ? $data['id_kode_klasifikasi'] : null;
        $idJenis = !empty($data['id_jenis_surat']) ? $data['id_jenis_surat'] : null;
        $idTemplate = !empty($data['id_template']) ? $data['id_template'] : null;
        $namaPembuat = $data['nama_pembuat'] ?? ($_SESSION['nama_lengkap'] ?? 'Petugas TU');
        $idPembuat = $data['id_pembuat'] ?? ($_SESSION['user_id'] ?? null);
        $namaPenandatangan = $data['nama_penandatangan'] ?? 'Kepala Sekolah';
        $jabatanPenandatangan = $data['jabatan_penandatangan'] ?? 'Kepala Sekolah';
        $statusSurat = $data['status_surat'] ?? 'Diterbitkan';
        $idRefModul = $data['id_referensi_modul'] ?? null;
        $namaModulRef = $data['nama_modul_referensi'] ?? null;

        if (empty($nomorSurat) || empty($tujuan) || empty($perihal)) {
            throw new Exception('Nomor surat, tujuan, dan perihal surat wajib diisi.');
        }

        if (empty($id)) {
            // Generate No Agenda & QR Token
            $gen = $this->generateNomorSurat($tenantId, $idKodeKlas, $tglSurat);
            $noAgenda = $data['no_agenda'] ?? $gen['no_agenda'];
            $qrToken = bin2hex(random_bytes(16));

            $stmt = $this->db->prepare("
                INSERT INTO persuratan.surat_keluar (
                    id, tenant_id, no_agenda, nomor_surat, id_kode_klasifikasi,
                    id_jenis_surat, id_template, tujuan, perihal, tgl_surat,
                    ringkasan_isi, id_pembuat, nama_pembuat, nama_penandatangan,
                    jabatan_penandatangan, status_surat, id_referensi_modul,
                    nama_modul_referensi, qr_token, is_active, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), :tid, :no_agenda, :nomor_surat, :id_klas,
                    :id_jenis, :id_tpl, :tujuan, :perihal, :tgl_surat,
                    :ringkasan, :id_pembuat, :nama_pembuat, :nama_ttd,
                    :jab_ttd, :status_surat, :id_ref,
                    :modul_ref, :qr_token, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                ) RETURNING id
            ");
            $stmt->execute([
                ':tid' => $tenantId,
                ':no_agenda' => $noAgenda,
                ':nomor_surat' => $nomorSurat,
                ':id_klas' => $idKodeKlas,
                ':id_jenis' => $idJenis,
                ':id_tpl' => $idTemplate,
                ':tujuan' => $tujuan,
                ':perihal' => $perihal,
                ':tgl_surat' => $tglSurat,
                ':ringkasan' => $ringkasan,
                ':id_pembuat' => $idPembuat,
                ':nama_pembuat' => $namaPembuat,
                ':nama_ttd' => $namaPenandatangan,
                ':jab_ttd' => $jabatanPenandatangan,
                ':status_surat' => $statusSurat,
                ':id_ref' => $idRefModul,
                ':modul_ref' => $namaModulRef,
                ':qr_token' => $qrToken
            ]);
            $skId = (string)$stmt->fetchColumn();

            // Daftarkan ke persuratan.tte_qr_validation
            $stmtTte = $this->db->prepare("
                INSERT INTO persuratan.tte_qr_validation (
                    id, tenant_id, id_surat_keluar, qr_token, hash_dokumen, penandatangan, is_valid, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), :tid, :sk_id, :qr_token, :hash, :ttd, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ");
            $hashDoc = hash('sha256', $nomorSurat . '|' . $tglSurat . '|' . $tujuan . '|' . $tenantId);
            $stmtTte->execute([
                ':tid' => $tenantId,
                ':sk_id' => $skId,
                ':qr_token' => $qrToken,
                ':hash' => $hashDoc,
                ':ttd' => $namaPenandatangan
            ]);

            return $skId;
        } else {
            $stmt = $this->db->prepare("
                UPDATE persuratan.surat_keluar SET
                    nomor_surat = :nomor_surat,
                    id_kode_klasifikasi = :id_klas,
                    id_jenis_surat = :id_jenis,
                    id_template = :id_tpl,
                    tujuan = :tujuan,
                    perihal = :perihal,
                    tgl_surat = :tgl_surat,
                    ringkasan_isi = :ringkasan,
                    nama_penandatangan = :nama_ttd,
                    jabatan_penandatangan = :jab_ttd,
                    status_surat = :status_surat,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
            ");
            $stmt->execute([
                ':id' => $id,
                ':tid' => $tenantId,
                ':nomor_surat' => $nomorSurat,
                ':id_klas' => $idKodeKlas,
                ':id_jenis' => $idJenis,
                ':id_tpl' => $idTemplate,
                ':tujuan' => $tujuan,
                ':perihal' => $perihal,
                ':tgl_surat' => $tglSurat,
                ':ringkasan' => $ringkasan,
                ':nama_ttd' => $namaPenandatangan,
                ':jab_ttd' => $jabatanPenandatangan,
                ':status_surat' => $statusSurat
            ]);
            return $id;
        }
    }

    /**
     * Hapus Surat Keluar (Soft Delete)
     */
    public function deleteSuratKeluar(string $tenantId, string $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE persuratan.surat_keluar 
            SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
        ");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

    /**
     * Mengambil Detail Dokumen Siap Cetak (Kop Surat + Konten + TTE QR)
     */
    public function getSuratKeluarDetailCetak(string $tenantId, string $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                sk.*,
                kk.kode_klasifikasi, kk.nama_klasifikasi,
                tpl.judul_surat, tpl.konten_html, tpl.kode_template,
                tte.qr_token, tte.hash_dokumen, tte.created_at AS tgl_tanda_tangan
            FROM persuratan.surat_keluar sk
            LEFT JOIN persuratan.kode_klasifikasi_surat kk ON sk.id_kode_klasifikasi = kk.id
            LEFT JOIN persuratan.template_surat tpl ON sk.id_template = tpl.id
            LEFT JOIN persuratan.tte_qr_validation tte ON sk.id = tte.id_surat_keluar
            WHERE sk.id = :id AND (sk.tenant_id = :tid OR sk.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $surat = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$surat) return null;

        $kop = $this->getKopSurat($tenantId);

        return [
            'surat' => $surat,
            'kop' => $kop
        ];
    }

    /**
     * Menyimpan Pengajuan / Notifikasi Surat Panggilan dari BK
     */
    public function simpanPengajuanBk(string $tenantId, array $data): string
    {
        $stmt = $this->db->prepare("
            INSERT INTO persuratan.pengajuan_surat_bk (
                id, tenant_id, id_siswa, nama_siswa, nisn, kelas, total_poin,
                jenis_panggilan, alasan_pemanggilan, rencana_tanggal_menghadap,
                rencana_jam_menghadap, ruangan, guru_bk_pengaju, id_guru_bk,
                status_pengajuan, is_active, created_at, updated_at
            ) VALUES (
                gen_random_uuid(), :tid, :siswa_id, :nama_siswa, :nisn, :kelas, :poin,
                :jenis, :alasan, :tgl, :jam, :ruang, :guru_nama, :guru_id,
                'Menunggu Penerbitan TU', TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            ) RETURNING id
        ");
        $stmt->execute([
            ':tid' => $tenantId,
            ':siswa_id' => $data['id_siswa'] ?? null,
            ':nama_siswa' => $data['nama_siswa'] ?? 'Siswa',
            ':nisn' => $data['nisn'] ?? '-',
            ':kelas' => $data['kelas'] ?? '-',
            ':poin' => (int)($data['total_poin'] ?? 0),
            ':jenis' => $data['jenis_panggilan'] ?? 'Surat Panggilan Orang Tua I',
            ':alasan' => $data['alasan_pemanggilan'] ?? '',
            ':tgl' => $data['rencana_tanggal_menghadap'] ?? date('Y-m-d'),
            ':jam' => $data['rencana_jam_menghadap'] ?? '09:00',
            ':ruang' => $data['ruangan'] ?? 'Ruang Konseling BK',
            ':guru_nama' => $data['guru_bk_pengaju'] ?? 'Guru BK',
            ':guru_id' => $data['id_guru_bk'] ?? null
        ]);
        return (string)$stmt->fetchColumn();
    }

    /**
     * Mengambil daftar Notifikasi / Pengajuan Surat Pemanggilan dari BK
     */
    public function getPengajuanBk(string $tenantId, array $filters = []): array
    {
        $globalTenant = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
        $params = [':tid' => $tenantId, ':gtid' => $globalTenant];
        $where = ["(pb.tenant_id = :tid OR pb.tenant_id = :gtid)", "pb.is_active = TRUE"];

        if (!empty($filters['status'])) {
            $where[] = "pb.status_pengajuan = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(pb.nama_siswa ILIKE :search OR pb.nisn ILIKE :search OR pb.kelas ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        $sql = "
            SELECT 
                pb.*,
                sk.nomor_surat AS nomor_surat_terdaftar,
                sk.tgl_surat AS tgl_surat_terbit,
                sk.qr_token
            FROM persuratan.pengajuan_surat_bk pb
            LEFT JOIN persuratan.surat_keluar sk ON pb.id_surat_keluar = sk.id
            WHERE {$whereClause}
            ORDER BY pb.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Tata Usaha Memproses Penerbitan Surat Resmi dari Pengajuan BK
     */
    public function prosesTerbitPengajuanBk(string $tenantId, string $idPengajuan, array $dataSurat): array
    {
        // 1. Ambil data pengajuan
        $stmtP = $this->db->prepare("
            SELECT * FROM persuratan.pengajuan_surat_bk 
            WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') 
            LIMIT 1
        ");
        $stmtP->execute([':id' => $idPengajuan, ':tid' => $tenantId]);
        $pengajuan = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$pengajuan) {
            throw new Exception('Data pengajuan pemanggilan tidak ditemukan.');
        }

        // 2. Ambil atau tentukan template & klasifikasi
        $stmtKlas = $this->db->prepare("SELECT id FROM persuratan.kode_klasifikasi_surat WHERE kode_klasifikasi = '421.3' AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' OR tenant_id IS NULL) LIMIT 1");
        $stmtKlas->execute([':tid' => $tenantId]);
        $idKlas = $stmtKlas->fetchColumn() ?: null;

        $stmtTpl = $this->db->prepare("SELECT id FROM persuratan.template_surat WHERE kode_template = 'SP-ORTU-01' AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' OR tenant_id IS NULL) LIMIT 1");
        $stmtTpl->execute([':tid' => $tenantId]);
        $idTpl = $stmtTpl->fetchColumn() ?: null;

        $stmtJenis = $this->db->prepare("SELECT id FROM persuratan.jenis_surat WHERE kode_jenis = 'SP-ORTU' AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' OR tenant_id IS NULL) LIMIT 1");
        $stmtJenis->execute([':tid' => $tenantId]);
        $idJenis = $stmtJenis->fetchColumn() ?: null;

        // 3. Generate nomor surat resmi jika belum diinput manual
        $nomorSurat = trim($dataSurat['nomor_surat'] ?? '');
        $tglSurat = !empty($dataSurat['tgl_surat']) ? $dataSurat['tgl_surat'] : date('Y-m-d');
        if (empty($nomorSurat)) {
            $gen = $this->generateNomorSurat($tenantId, $idKlas, $tglSurat);
            $nomorSurat = $gen['nomor_surat'];
        }

        $tujuan = "Orang Tua / Wali dari " . $pengajuan['nama_siswa'] . " (Kelas " . ($pengajuan['kelas'] ?: '-') . ")";
        $perihal = $dataSurat['perihal'] ?? ("Surat Pemanggilan Orang Tua / Wali Siswa (" . $pengajuan['jenis_panggilan'] . ")");
        $ringkasan = "Pemanggilan orang tua siswa an. " . $pengajuan['nama_siswa'] . " terkait akumulasi " . $pengajuan['total_poin'] . " poin pelanggaran. Menghadap tgl " . ($pengajuan['rencana_tanggal_menghadap'] ?: '-') . " di " . ($pengajuan['ruangan'] ?: 'Ruang BK') . ".";

        // 4. Buat Surat Keluar di TU
        $skData = [
            'nomor_surat' => $nomorSurat,
            'id_kode_klasifikasi' => $idKlas,
            'id_jenis_surat' => $idJenis,
            'id_template' => $idTpl,
            'tujuan' => $tujuan,
            'perihal' => $perihal,
            'tgl_surat' => $tglSurat,
            'ringkasan_isi' => $ringkasan,
            'nama_penandatangan' => $dataSurat['nama_penandatangan'] ?? 'Kepala Sekolah',
            'jabatan_penandatangan' => $dataSurat['jabatan_penandatangan'] ?? 'Kepala Sekolah',
            'status_surat' => 'Diterbitkan',
            'id_referensi_modul' => $idPengajuan,
            'nama_modul_referensi' => 'bk'
        ];
        $suratKeluarId = $this->saveSuratKeluar($tenantId, $skData);

        // 5. Update status pengajuan di persuratan.pengajuan_surat_bk
        $stmtUpd = $this->db->prepare("
            UPDATE persuratan.pengajuan_surat_bk SET
                status_pengajuan = 'Surat Resmi Telah Terbit',
                id_surat_keluar = :sk_id,
                nomor_surat_terbit = :no_surat,
                tgl_terbit_surat = CURRENT_TIMESTAMP,
                catatan_tu = :catatan,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmtUpd->execute([
            ':id' => $idPengajuan,
            ':sk_id' => $suratKeluarId,
            ':no_surat' => $nomorSurat,
            ':catatan' => $dataSurat['catatan_tu'] ?? 'Surat resmi telah diterbitkan oleh Tata Usaha.'
        ]);

        // 6. Update log relasi di bk.pembinaan_monitoring jika ada
        $stmtUpdBk = $this->db->prepare("
            UPDATE bk.pembinaan_monitoring SET
                id_surat_keluar = :sk_id,
                nomor_surat_resmi = :no_surat,
                status_surat = 'Surat Resmi Telah Terbit',
                updated_at = CURRENT_TIMESTAMP
            WHERE id_pengajuan_surat = :id_p OR (kategori = :siswa_id AND status_surat = 'Menunggu Penerbitan TU')
        ");
        $stmtUpdBk->execute([
            ':sk_id' => $suratKeluarId,
            ':no_surat' => $nomorSurat,
            ':id_p' => $idPengajuan,
            ':siswa_id' => (string)($pengajuan['id_siswa'] ?? '')
        ]);

        return [
            'success' => true,
            'surat_keluar_id' => $suratKeluarId,
            'nomor_surat' => $nomorSurat,
            'message' => "Surat panggilan resmi nomor {$nomorSurat} berhasil diterbitkan oleh Tata Usaha."
        ];
    }

    /**
     * Mengambil Master Template Naskah Dinas
     */
    public function getTemplates(string $tenantId): array
    {
        $stmt = $this->db->prepare("
            SELECT tpl.*, js.nama_jenis_surat 
            FROM persuratan.template_surat tpl
            LEFT JOIN persuratan.jenis_surat js ON tpl.id_jenis_surat = js.id
            WHERE (tpl.tenant_id = :tid OR tpl.tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') AND tpl.is_active = TRUE
            ORDER BY tpl.created_at ASC
        ");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Simpan / Update Template Naskah Dinas
     */
    public function saveTemplate(string $tenantId, array $data): string
    {
        $id = $data['id'] ?? null;
        $namaTemplate = trim($data['nama_template_surat'] ?? '');
        $kodeTemplate = trim($data['kode_template'] ?? '');
        $judulSurat = trim($data['judul_surat'] ?? '');
        $kontenHtml = $data['konten_html'] ?? '';
        $idJenisSurat = !empty($data['id_jenis_surat']) ? $data['id_jenis_surat'] : null;

        if (empty($namaTemplate)) {
            throw new Exception('Nama template surat wajib diisi.');
        }

        if (empty($id)) {
            $stmt = $this->db->prepare("
                INSERT INTO persuratan.template_surat (
                    id, tenant_id, id_jenis_surat, nama_template_surat,
                    kode_template, judul_surat, konten_html, is_active, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), :tid, :id_jenis, :nama,
                    :kode, :judul, :konten, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                ) RETURNING id
            ");
            $stmt->execute([
                ':tid' => $tenantId,
                ':id_jenis' => $idJenisSurat,
                ':nama' => $namaTemplate,
                ':kode' => $kodeTemplate,
                ':judul' => $judulSurat,
                ':konten' => $kontenHtml
            ]);
            return (string)$stmt->fetchColumn();
        } else {
            $stmt = $this->db->prepare("
                UPDATE persuratan.template_surat SET
                    id_jenis_surat = :id_jenis,
                    nama_template_surat = :nama,
                    kode_template = :kode,
                    judul_surat = :judul,
                    konten_html = :konten,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
            ");
            $stmt->execute([
                ':id' => $id,
                ':tid' => $tenantId,
                ':id_jenis' => $idJenisSurat,
                ':nama' => $namaTemplate,
                ':kode' => $kodeTemplate,
                ':judul' => $judulSurat,
                ':konten' => $kontenHtml
            ]);
            return $id;
        }
    }

    /**
     * Hapus Template Surat (Soft Delete)
     */
    public function deleteTemplate(string $tenantId, string $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE persuratan.template_surat 
            SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
        ");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

    /**
     * Mengambil Master Kode Klasifikasi Surat dengan filter tahun berlaku, status aktif, & pencarian
     */
    public function getKlasifikasi(string $tenantId, array $filters = []): array
    {
        $globalTenant = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
        $params = [':tid' => $tenantId, ':gtid' => $globalTenant];
        $where = ["(tenant_id = :tid OR tenant_id = :gtid OR tenant_id IS NULL)"];

        // Filter status aktif
        $statusAktif = $filters['status_aktif'] ?? 'aktif';
        if ($statusAktif === 'aktif') {
            $where[] = "is_active = TRUE";
        } elseif ($statusAktif === 'nonaktif') {
            $where[] = "is_active = FALSE";
        }
        // jika 'semua', tidak memfilter is_active

        if (!empty($filters['tahun'])) {
            $tahun = (int)$filters['tahun'];
            $where[] = "(tahun_berlaku_mulai <= :tahun AND (tahun_berlaku_selesai IS NULL OR tahun_berlaku_selesai >= :tahun))";
            $params[':tahun'] = $tahun;
        }

        if (!empty($filters['versi_regulasi'])) {
            $where[] = "versi_regulasi = :versi";
            $params[':versi'] = $filters['versi_regulasi'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(kode_klasifikasi ILIKE :search OR nama_klasifikasi ILIKE :search OR kategori_utama ILIKE :search OR deskripsi ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT id, tenant_id, kode_klasifikasi, nama_klasifikasi, parent_kode,
                   level_klasifikasi, kategori_utama, deskripsi, retensi_aktif_tahun,
                   retensi_inaktif_tahun, retensi_tahun, tahun_berlaku_mulai,
                   tahun_berlaku_selesai, versi_regulasi, is_active, created_at
            FROM persuratan.kode_klasifikasi_surat 
            WHERE {$whereClause}
            ORDER BY kode_klasifikasi ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Toggle Aktif / Nonaktif Kode Klasifikasi
     */
    public function toggleStatusKlasifikasi(string $tenantId, string $id, bool $isActive): bool
    {
        $stmt = $this->db->prepare("
            UPDATE persuratan.kode_klasifikasi_surat 
            SET is_active = :status, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' OR tenant_id IS NULL)
        ");
        return $stmt->execute([
            ':status' => $isActive ? 1 : 0,
            ':id' => $id,
            ':tid' => $tenantId
        ]);
    }

    /**
     * Toggle Aktif / Nonaktif Massal Berdasarkan Tahun Regulasi
     */
    public function toggleStatusByTahun(string $tenantId, int $tahun, bool $isActive): int
    {
        $stmt = $this->db->prepare("
            UPDATE persuratan.kode_klasifikasi_surat 
            SET is_active = :status, updated_at = CURRENT_TIMESTAMP 
            WHERE (tahun_berlaku_mulai <= :tahun AND (tahun_berlaku_selesai IS NULL OR tahun_berlaku_selesai >= :tahun))
              AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' OR tenant_id IS NULL)
        ");
        $stmt->execute([
            ':status' => $isActive ? 1 : 0,
            ':tahun' => $tahun,
            ':tid' => $tenantId
        ]);
        return $stmt->rowCount();
    }

    /**
     * Simpan / Update Kode Klasifikasi
     */
    public function saveKlasifikasi(string $tenantId, array $data): string
    {
        $id = $data['id'] ?? null;
        $kode = trim($data['kode_klasifikasi'] ?? '');
        $nama = trim($data['nama_klasifikasi'] ?? '');
        $deskripsi = trim($data['deskripsi'] ?? '');
        $retensi = (int)($data['retensi_tahun'] ?? 5);
        $tahunMulai = (int)($data['tahun_berlaku_mulai'] ?? 2025);
        $tahunSelesai = !empty($data['tahun_berlaku_selesai']) ? (int)$data['tahun_berlaku_selesai'] : null;
        $versi = trim($data['versi_regulasi'] ?? 'Permendagri/Disdik 2025');
        $isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;

        if (empty($kode) || empty($nama)) {
            throw new Exception('Kode dan nama klasifikasi surat wajib diisi.');
        }

        if (empty($id)) {
            $stmt = $this->db->prepare("
                INSERT INTO persuratan.kode_klasifikasi_surat (
                    id, tenant_id, kode_klasifikasi, nama_klasifikasi, deskripsi, retensi_tahun,
                    tahun_berlaku_mulai, tahun_berlaku_selesai, versi_regulasi, is_active, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), :tid, :kode, :nama, :deskripsi, :retensi,
                    :tmulai, :tselesai, :versi, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                ) RETURNING id
            ");
            $stmt->execute([
                ':tid' => $tenantId,
                ':kode' => $kode,
                ':nama' => $nama,
                ':deskripsi' => $deskripsi,
                ':retensi' => $retensi,
                ':tmulai' => $tahunMulai,
                ':tselesai' => $tahunSelesai,
                ':versi' => $versi,
                ':is_active' => $isActive ? 1 : 0
            ]);
            return (string)$stmt->fetchColumn();
        } else {
            $stmt = $this->db->prepare("
                UPDATE persuratan.kode_klasifikasi_surat SET
                    kode_klasifikasi = :kode,
                    nama_klasifikasi = :nama,
                    deskripsi = :deskripsi,
                    retensi_tahun = :retensi,
                    tahun_berlaku_mulai = :tmulai,
                    tahun_berlaku_selesai = :tselesai,
                    versi_regulasi = :versi,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' OR tenant_id IS NULL)
            ");
            $stmt->execute([
                ':id' => $id,
                ':tid' => $tenantId,
                ':kode' => $kode,
                ':nama' => $nama,
                ':deskripsi' => $deskripsi,
                ':retensi' => $retensi,
                ':tmulai' => $tahunMulai,
                ':tselesai' => $tahunSelesai,
                ':versi' => $versi,
                ':is_active' => $isActive ? 1 : 0
            ]);
            return $id;
        }
    }

    /**
     * Import / Sinkronisasi Massal Kode Klasifikasi
     */
    public function importKlasifikasiBulk(string $tenantId, array $items, string $versiRegulasi = 'Permendagri/Disdik 2025', int $tahunMulai = 2025): array
    {
        $inserted = 0;
        $updated = 0;

        $this->db->beginTransaction();
        try {
            $stmtCheck = $this->db->prepare("
                SELECT id FROM persuratan.kode_klasifikasi_surat 
                WHERE kode_klasifikasi = :kode AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' OR tenant_id IS NULL)
                LIMIT 1
            ");

            $stmtInsert = $this->db->prepare("
                INSERT INTO persuratan.kode_klasifikasi_surat (
                    id, tenant_id, kode_klasifikasi, nama_klasifikasi, parent_kode,
                    level_klasifikasi, kategori_utama, retensi_aktif_tahun, retensi_inaktif_tahun,
                    retensi_tahun, tahun_berlaku_mulai, versi_regulasi, is_active, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), :tid, :kode, :nama, :parent,
                    :level, :kat, :ret_aktif, :ret_inaktif,
                    :ret_aktif, :tahun, :versi, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ");

            $stmtUpdate = $this->db->prepare("
                UPDATE persuratan.kode_klasifikasi_surat SET
                    nama_klasifikasi = :nama,
                    parent_kode = :parent,
                    level_klasifikasi = :level,
                    kategori_utama = :kat,
                    retensi_aktif_tahun = :ret_aktif,
                    retensi_inaktif_tahun = :ret_inaktif,
                    tahun_berlaku_mulai = :tahun,
                    versi_regulasi = :versi,
                    is_active = TRUE,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' OR tenant_id IS NULL)
            ");

            foreach ($items as $item) {
                $kode = trim($item['kode_klasifikasi'] ?? '');
                $nama = trim($item['nama_klasifikasi'] ?? '');
                if (empty($kode) || empty($nama)) continue;

                $parent = !empty($item['parent_kode']) ? trim($item['parent_kode']) : null;
                $level = !empty($item['level_klasifikasi']) ? (int)$item['level_klasifikasi'] : 1;
                $kat = !empty($item['kategori_utama']) ? trim($item['kategori_utama']) : 'Umum/Organisasi';
                $retAktif = !empty($item['retensi_aktif_tahun']) ? (int)$item['retensi_aktif_tahun'] : 5;
                $retInaktif = !empty($item['retensi_inaktif_tahun']) ? (int)$item['retensi_inaktif_tahun'] : 5;

                $stmtCheck->execute([':kode' => $kode, ':tid' => $tenantId]);
                $existingId = $stmtCheck->fetchColumn();

                if ($existingId) {
                    $stmtUpdate->execute([
                        ':id' => $existingId,
                        ':tid' => $tenantId,
                        ':nama' => $nama,
                        ':parent' => $parent,
                        ':level' => $level,
                        ':kat' => $kat,
                        ':ret_aktif' => $retAktif,
                        ':ret_inaktif' => $retInaktif,
                        ':tahun' => $tahunMulai,
                        ':versi' => $versiRegulasi
                    ]);
                    $updated++;
                } else {
                    $stmtInsert->execute([
                        ':tid' => $tenantId,
                        ':kode' => $kode,
                        ':nama' => $nama,
                        ':parent' => $parent,
                        ':level' => $level,
                        ':kat' => $kat,
                        ':ret_aktif' => $retAktif,
                        ':ret_inaktif' => $retInaktif,
                        ':tahun' => $tahunMulai,
                        ':versi' => $versiRegulasi
                    ]);
                    $inserted++;
                }
            }

            $this->db->commit();
            return [
                'success' => true,
                'inserted' => $inserted,
                'updated' => $updated,
                'total' => $inserted + $updated
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Hapus Kode Klasifikasi (Soft Delete / Hapus)
     */
    public function deleteKlasifikasi(string $tenantId, string $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM persuratan.kode_klasifikasi_surat 
            WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' OR tenant_id IS NULL)
        ");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

    /**
     * Mengambil Kop Surat Aktif Sekolah
     */
    public function getKopSurat(string $tenantId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM persuratan.kop_surat 
            WHERE (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') AND is_active = TRUE
            ORDER BY is_default DESC, created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([':tid' => $tenantId]);
        $kop = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$kop) {
            $namaSekolah = 'SMA NEGERI CONTOH';
            $npsn = '20100001';
            $alamat = 'Jl. Pendidikan No. 123';
            $telepon = '(031) 1234567';
            $email = 'info@sekolah.sch.id';

            try {
                $stmtT = $this->db->prepare("SELECT * FROM core.tenants WHERE id = :tid LIMIT 1");
                $stmtT->execute([':tid' => $tenantId]);
                $t = $stmtT->fetch(PDO::FETCH_ASSOC);
                if ($t) {
                    $namaSekolah = $t['nama_sekolah'] ?? ($t['name'] ?? $namaSekolah);
                    $npsn = $t['npsn'] ?? $npsn;
                    $alamat = $t['alamat'] ?? ($t['address'] ?? $alamat);
                    $telepon = $t['telepon'] ?? ($t['phone'] ?? $telepon);
                    $email = $t['email'] ?? $email;
                }
            } catch (Exception $e) {}

            $kop = [
                'nama_instansi_atas' => 'PEMERINTAH PROVINSI / KABUPATEN',
                'nama_sekolah' => $namaSekolah,
                'npsn' => $npsn,
                'akreditasi' => 'A (Unggul)',
                'alamat' => $alamat,
                'telepon' => $telepon,
                'email' => $email,
                'website' => 'www.sekolah.sch.id',
                'logo_kiri' => '',
                'logo_kanan' => '',
                'garis_kop' => true
            ];
        }

        return $kop;
    }

    /**
     * Simpan / Update Kop Surat
     */
    public function saveKopSurat(string $tenantId, array $data): string
    {
        $id = $data['id'] ?? null;
        $instansi = trim($data['nama_instansi_atas'] ?? 'PEMERINTAH DAERAH');
        $namaSekolah = trim($data['nama_sekolah'] ?? '');
        $npsn = trim($data['npsn'] ?? '');
        $akreditasi = trim($data['akreditasi'] ?? '');
        $alamat = trim($data['alamat'] ?? '');
        $telepon = trim($data['telepon'] ?? '');
        $email = trim($data['email'] ?? '');
        $website = trim($data['website'] ?? '');
        $logoKiri = $data['logo_kiri'] ?? null;
        $logoKanan = $data['logo_kanan'] ?? null;

        if (empty($id)) {
            $stmt = $this->db->prepare("
                INSERT INTO persuratan.kop_surat (
                    id, tenant_id, nama_instansi_atas, nama_sekolah, npsn, akreditasi,
                    alamat, telepon, email, website, logo_kiri, logo_kanan,
                    garis_kop, is_default, is_active, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), :tid, :instansi, :sekolah, :npsn, :akreditasi,
                    :alamat, :telepon, :email, :website, :logo_kiri, :logo_kanan,
                    TRUE, TRUE, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                ) RETURNING id
            ");
            $stmt->execute([
                ':tid' => $tenantId,
                ':instansi' => $instansi,
                ':sekolah' => $namaSekolah,
                ':npsn' => $npsn,
                ':akreditasi' => $akreditasi,
                ':alamat' => $alamat,
                ':telepon' => $telepon,
                ':email' => $email,
                ':website' => $website,
                ':logo_kiri' => $logoKiri,
                ':logo_kanan' => $logoKanan,
            ]);
            return (string)$stmt->fetchColumn();
        } else {
            $stmt = $this->db->prepare("
                UPDATE persuratan.kop_surat SET
                    nama_instansi_atas = :instansi,
                    nama_sekolah = :sekolah,
                    npsn = :npsn,
                    akreditasi = :akreditasi,
                    alamat = :alamat,
                    telepon = :telepon,
                    email = :email,
                    website = :website,
                    " . ($logoKiri ? "logo_kiri = :logo_kiri," : "") . "
                    " . ($logoKanan ? "logo_kanan = :logo_kanan," : "") . "
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND (tenant_id = :tid OR tenant_id = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12')
            ");
            $binds = [
                ':id' => $id,
                ':tid' => $tenantId,
                ':instansi' => $instansi,
                ':sekolah' => $namaSekolah,
                ':npsn' => $npsn,
                ':akreditasi' => $akreditasi,
                ':alamat' => $alamat,
                ':telepon' => $telepon,
                ':email' => $email,
                ':website' => $website,
            ];
            if ($logoKiri) $binds[':logo_kiri'] = $logoKiri;
            if ($logoKanan) $binds[':logo_kanan'] = $logoKanan;
            $stmt->execute($binds);
            return $id;
        }
    }

    /**
     * Verifikasi Publik Token TTE QR Dokumen
     */
    public function verifyTteToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                tte.*,
                sk.nomor_surat, sk.perihal, sk.tujuan, sk.tgl_surat,
                t.nama_sekolah, t.npsn
            FROM persuratan.tte_qr_validation tte
            LEFT JOIN persuratan.surat_keluar sk ON tte.id_surat_keluar = sk.id
            LEFT JOIN core.tenants t ON tte.tenant_id = t.id
            WHERE tte.qr_token = :token AND tte.is_valid = TRUE
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($doc) {
            // Update hit counter
            $stmtUpd = $this->db->prepare("
                UPDATE persuratan.tte_qr_validation 
                SET total_verifikasi = total_verifikasi + 1, last_verified_at = CURRENT_TIMESTAMP 
                WHERE qr_token = :token
            ");
            $stmtUpd->execute([':token' => $token]);
        }

        return $doc ?: null;
    }
}
