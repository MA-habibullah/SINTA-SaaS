<?php

namespace App\Controllers;

use PDO;
use App\Config\Database;

class KampusController extends BaseController
{
    private function checkAccess()
    {
        \App\Core\SessionManager::requireLogin();
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $hasAccess = false;
        foreach ($roles as $r) {
            if (in_array($r, ['operator_sekolah', 'super_admin', 'guru_bk'])) {
                $hasAccess = true;
                break;
            }
        }
        if (!$hasAccess) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
        }

        // Prioritas tenant_id:
        // 1. Dari sesi (untuk operator_sekolah / guru_bk yang hanya punya 1 tenant)
        // 2. Dari parameter GET ?tenant_id=... (untuk super_admin yang memfilter dari UI)
        $tenantId = $_SESSION['tenant_id'] ?? '';
        if (empty($tenantId) && !empty($_GET['tenant_id'])) {
            $tenantId = trim($_GET['tenant_id']);
        }

        if (empty($tenantId)) {
            $this->jsonResponse(['error' => 'Tenant tidak terdeteksi. Silakan pilih sekolah terlebih dahulu.'], 400);
        }

        return $tenantId;
    }

    // ========================================================
    // MASTER KAMPUS
    // ========================================================
    public function apiGetKampus()
    {
        $tenantId = $this->checkAccess();
        $db = Database::getConnection();

        // Ambil semua kampus dan prodinya
        $stmt = $db->prepare("
            SELECT k.*, 
                   (SELECT COUNT(*) FROM master_kampus_prodi p WHERE p.kampus_id = k.id) as total_prodi
            FROM master_kampus k
            WHERE k.tenant_id = ?
            ORDER BY k.nama_kampus ASC
        ");
        $stmt->execute([$tenantId]);
        $kampusList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse([
            'success' => true,
            'data'    => $kampusList
        ]);
    }

    public function apiSaveKampus()
    {
        $tenantId = $this->checkAccess();
        $body = $this->getJsonInput();

        $id           = $this->sanitizeStr($body['id'] ?? '');
        $nama_kampus  = $this->sanitizeStr($body['nama_kampus'] ?? '');
        $kota_kampus  = $this->sanitizeStr($body['kota_kampus'] ?? '');
        $alamat_kampus= $this->sanitizeStr($body['alamat_kampus'] ?? '');
        $jenis_kampus = $this->sanitizeStr($body['jenis_kampus'] ?? 'Negeri');

        if (empty($nama_kampus)) {
            $this->jsonResponse(['error' => 'Nama kampus wajib diisi.'], 422);
        }

        $db = Database::getConnection();
        try {
            if (empty($id)) {
                $id = $this->generateUuidV4();
                $stmt = $db->prepare("
                    INSERT INTO master_kampus (id, tenant_id, nama_kampus, kota_kampus, alamat_kampus, jenis_kampus)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id, $tenantId, $nama_kampus, $kota_kampus, $alamat_kampus, $jenis_kampus]);
                $msg = "Kampus berhasil ditambahkan.";
            } else {
                $stmt = $db->prepare("
                    UPDATE master_kampus 
                    SET nama_kampus=?, kota_kampus=?, alamat_kampus=?, jenis_kampus=? 
                    WHERE id=? AND tenant_id=?
                ");
                $stmt->execute([$nama_kampus, $kota_kampus, $alamat_kampus, $jenis_kampus, $id, $tenantId]);
                $msg = "Kampus berhasil diperbarui.";
            }
            $this->jsonResponse(['success' => true, 'message' => $msg, 'id' => $id]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Gagal menyimpan kampus: ' . $e->getMessage()], 500);
        }
    }

    public function apiDeleteKampus()
    {
        $tenantId = $this->checkAccess();
        $body = $this->getJsonInput();
        $id   = $this->sanitizeStr($body['id'] ?? '');

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID Kampus wajib diisi.'], 422);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM master_kampus WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);

        $this->jsonResponse(['success' => true, 'message' => 'Kampus berhasil dihapus.']);
    }

    // ========================================================
    // MASTER PRODI
    // ========================================================
    public function apiGetProdi()
    {
        $tenantId = $this->checkAccess();
        $kampusId = $_GET['kampus_id'] ?? '';
        
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.* 
            FROM master_kampus_prodi p
            JOIN master_kampus k ON p.kampus_id = k.id
            WHERE k.tenant_id = ? AND p.kampus_id = ?
            ORDER BY p.program_studi ASC
        ");
        $stmt->execute([$tenantId, $kampusId]);
        $prodiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse(['success' => true, 'data' => $prodiList]);
    }

    public function apiSaveProdi()
    {
        $tenantId = $this->checkAccess();
        $body = $this->getJsonInput();

        $id            = $this->sanitizeStr($body['id'] ?? '');
        $kampus_id     = $this->sanitizeStr($body['kampus_id'] ?? '');
        $kode_prodi    = $this->sanitizeStr($body['kode_prodi'] ?? '');
        $fakultas      = $this->sanitizeStr($body['fakultas'] ?? '');
        $program_studi = $this->sanitizeStr($body['program_studi'] ?? '');
        $jenjang       = $this->sanitizeStr($body['jenjang'] ?? 'S1');
        $portofolio    = $this->sanitizeStr($body['jenis_portofolio'] ?? 'Tidak Ada');

        if (empty($kampus_id) || empty($program_studi)) {
            $this->jsonResponse(['error' => 'Kampus dan Program Studi wajib diisi.'], 422);
        }

        $db = Database::getConnection();
        
        $stmtCheckCol = $db->prepare("SHOW COLUMNS FROM `master_kampus_prodi` LIKE 'kode_prodi'");
        $stmtCheckCol->execute();
        $hasKodeCol = $stmtCheckCol->fetch() !== false;
        
        try {
            if (empty($id)) {
                $id = $this->generateUuidV4();
                if ($hasKodeCol) {
                    $stmt = $db->prepare("INSERT INTO master_kampus_prodi (id, kampus_id, kode_prodi, fakultas, program_studi, jenjang, jenis_portofolio) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$id, $kampus_id, $kode_prodi, $fakultas, $program_studi, $jenjang, $portofolio]);
                } else {
                    $stmt = $db->prepare("INSERT INTO master_kampus_prodi (id, kampus_id, fakultas, program_studi, jenjang) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$id, $kampus_id, $fakultas, $program_studi, $jenjang]);
                }
                $msg = "Program Studi berhasil ditambahkan.";
            } else {
                if ($hasKodeCol) {
                    $stmt = $db->prepare("UPDATE master_kampus_prodi SET kode_prodi=?, fakultas=?, program_studi=?, jenjang=?, jenis_portofolio=? WHERE id=?");
                    $stmt->execute([$kode_prodi, $fakultas, $program_studi, $jenjang, $portofolio, $id]);
                } else {
                    $stmt = $db->prepare("UPDATE master_kampus_prodi SET fakultas=?, program_studi=?, jenjang=? WHERE id=?");
                    $stmt->execute([$fakultas, $program_studi, $jenjang, $id]);
                }
                $msg = "Program Studi berhasil diperbarui.";
            }
            $this->jsonResponse(['success' => true, 'message' => $msg, 'id' => $id]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Gagal menyimpan prodi: ' . $e->getMessage()], 500);
        }
    }

    public function apiDeleteProdi()
    {
        $tenantId = $this->checkAccess();
        $body = $this->getJsonInput();
        $id   = $this->sanitizeStr($body['id'] ?? '');

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID Prodi wajib diisi.'], 422);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM master_kampus_prodi WHERE id = ?");
        $stmt->execute([$id]);

        $this->jsonResponse(['success' => true, 'message' => 'Prodi berhasil dihapus.']);
    }

    // ========================================================
    // RIWAYAT KEKETATAN PRODI
    // ========================================================
    public function apiGetRiwayat()
    {
        $tenantId = $this->checkAccess();
        $prodiId = $_GET['prodi_id'] ?? '';
        
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM kampus_prodi_riwayat
            WHERE prodi_id = ?
            ORDER BY tahun DESC
        ");
        $stmt->execute([$prodiId]);
        $riwayatList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse(['success' => true, 'data' => $riwayatList]);
    }

    public function apiSaveRiwayat()
    {
        $tenantId = $this->checkAccess();
        $body = $this->getJsonInput();

        $prodi_id         = $this->sanitizeStr($body['prodi_id'] ?? '');
        $tahun            = (int)($body['tahun'] ?? 0);
        $daya_tampung     = (int)($body['daya_tampung'] ?? 0);
        $jumlah_pendaftar = (int)($body['jumlah_pendaftar'] ?? 0);

        if (empty($prodi_id) || $tahun < 2000) {
            $this->jsonResponse(['error' => 'Prodi dan Tahun (>= 2000) wajib diisi.'], 422);
        }

        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO kampus_prodi_riwayat (prodi_id, tahun, daya_tampung, jumlah_pendaftar)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE daya_tampung = VALUES(daya_tampung), jumlah_pendaftar = VALUES(jumlah_pendaftar)
            ");
            $stmt->execute([$prodi_id, $tahun, $daya_tampung, $jumlah_pendaftar]);
            $this->jsonResponse(['success' => true, 'message' => 'Riwayat berhasil disimpan.']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Gagal menyimpan riwayat: ' . $e->getMessage()], 500);
        }
    }

    public function apiDeleteRiwayat()
    {
        $tenantId = $this->checkAccess();
        $body = $this->getJsonInput();
        $id   = (int)($body['id'] ?? 0);

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID Riwayat wajib diisi.'], 422);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM kampus_prodi_riwayat WHERE id = ?");
        $stmt->execute([$id]);

        $this->jsonResponse(['success' => true, 'message' => 'Riwayat berhasil dihapus.']);
    }

    // ========================================================
    // MASTER JALUR MASUK
    // ========================================================
    public function apiGetJalurMasuk()
    {
        $tenantId = $this->checkAccess();
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT * FROM master_jalur_masuk
            WHERE tenant_id = ?
            ORDER BY nama_jalur ASC
        ");
        $stmt->execute([$tenantId]);
        
        $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function apiSaveJalurMasuk()
    {
        $tenantId = $this->checkAccess();
        $body = $this->getJsonInput();

        $id         = (int)($body['id'] ?? 0);
        $nama_jalur = $this->sanitizeStr($body['nama_jalur'] ?? '');
        $kategori   = $this->sanitizeStr($body['kategori'] ?? 'Lainnya');

        if (empty($nama_jalur)) {
            $this->jsonResponse(['error' => 'Nama Jalur wajib diisi.'], 422);
        }

        $db = Database::getConnection();
        try {
            if (empty($id)) {
                $stmt = $db->prepare("INSERT INTO master_jalur_masuk (tenant_id, nama_jalur, kategori) VALUES (?, ?, ?)");
                $stmt->execute([$tenantId, $nama_jalur, $kategori]);
            } else {
                $stmt = $db->prepare("UPDATE master_jalur_masuk SET nama_jalur=?, kategori=? WHERE id=? AND tenant_id=?");
                $stmt->execute([$nama_jalur, $kategori, $id, $tenantId]);
            }
            $this->jsonResponse(['success' => true, 'message' => 'Jalur masuk berhasil disimpan.']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Gagal menyimpan jalur masuk: ' . $e->getMessage()], 500);
        }
    }

    public function apiDeleteJalurMasuk()
    {
        $tenantId = $this->checkAccess();
        $body = $this->getJsonInput();
        $id   = (int)($body['id'] ?? 0);

        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID wajib diisi.'], 422);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM master_jalur_masuk WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);

        $this->jsonResponse(['success' => true, 'message' => 'Jalur masuk berhasil dihapus.']);
    }


    // ========================================================
    // EXCEL IMPORT & EXPORT
    // ========================================================
    public function apiDownloadTemplate()
    {
        $this->checkAccess();
        require_once __DIR__ . '/../../vendor/autoload.php';

        $data = [
            ['KODE', 'NAMA', 'JENJANG', 'DAYA TAMPUNG ' . date('Y'), 'PEMINAT ' . (date('Y') - 1), 'JENIS PORTOFOLIO', 'kampus', 'kota'],
            ['13211001', 'PENDIDIKAN DOKTER', 'Sarjana', 60, 1720, 'Tidak Ada', 'UI (Universitas Indonesia)', 'jakarta'],
            ['13211009', 'TEKNIK SIPIL', 'Sarjana', 40, 494, 'Tidak Ada', 'UI (Universitas Indonesia)', 'jakarta'],
            ['', 'Contoh Fakultas/Jurusan Tanpa Kode', 'Diploma III', 20, 100, 'Olahraga', 'Universitas Contoh', 'Bandung']
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $xlsx->downloadAs('Template_SNBP_Master_Kampus.xlsx');
        exit;
    }

    public function apiImportExcel()
    {
        $tenantId = $this->checkAccess();
        require_once __DIR__ . '/../../vendor/autoload.php';

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'File Excel gagal diunggah.'], 400);
        }

        $fileTmp = $_FILES['excel_file']['tmp_name'];
        if ($xlsx = \Shuchkin\SimpleXLSX::parse($fileTmp)) {
            $db = Database::getConnection();
            $db->beginTransaction();
            try {
                $rows = $xlsx->rows();
                if (count($rows) <= 1) {
                    throw new \Exception("File Excel kosong atau hanya berisi header.");
                }

                $header = $rows[0];
                $colMap = [];
                $dtYear = null;
                $pmYear = null;

                foreach ($header as $i => $col) {
                    $colClean = strtolower(trim((string)$col));
                    if (empty($colClean)) continue;

                    if (strpos($colClean, 'kode') !== false) {
                        $colMap['kode'] = $i;
                    } elseif (strpos($colClean, 'nama') !== false && strpos($colClean, 'kampus') === false) {
                        $colMap['nama'] = $i;
                    } elseif (strpos($colClean, 'jenjang') !== false) {
                        $colMap['jenjang'] = $i;
                    } elseif (strpos($colClean, 'portofolio') !== false) {
                        $colMap['portofolio'] = $i;
                    } elseif (strpos($colClean, 'kampus') !== false) {
                        $colMap['kampus'] = $i;
                    } elseif (strpos($colClean, 'kota') !== false) {
                        $colMap['kota'] = $i;
                    }

                    if (preg_match('/daya tampung\s*(\d{4})/i', $colClean, $matches)) {
                        $colMap['daya_tampung'] = $i;
                        $dtYear = (int)$matches[1];
                    }
                    if (preg_match('/peminat\s*(\d{4})/i', $colClean, $matches)) {
                        $colMap['peminat'] = $i;
                        $pmYear = (int)$matches[1];
                    }
                }

                if (!isset($colMap['nama']) || !isset($colMap['kampus'])) {
                    throw new \Exception("Format Excel tidak valid. Pastikan ada kolom 'NAMA' (Prodi) dan 'kampus'.");
                }

                $insertedKampus = 0;
                $insertedProdi = 0;
                $insertedRiwayat = 0;

                // Loop over rows starting from index 1 (skip header)
                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    
                    $namaProdi = isset($colMap['nama']) ? trim((string)($row[$colMap['nama']] ?? '')) : '';
                    $namaKampus = isset($colMap['kampus']) ? trim((string)($row[$colMap['kampus']] ?? '')) : '';
                    
                    if (empty($namaProdi) || empty($namaKampus)) {
                        continue;
                    }

                    $kodeProdi = isset($colMap['kode']) ? trim((string)($row[$colMap['kode']] ?? '')) : null;
                    $jenjangRaw = isset($colMap['jenjang']) ? strtolower(trim((string)($row[$colMap['jenjang']] ?? ''))) : 's1';
                    $portofolio = isset($colMap['portofolio']) ? trim((string)($row[$colMap['portofolio']] ?? 'Tidak Ada')) : 'Tidak Ada';
                    $kota = isset($colMap['kota']) ? trim((string)($row[$colMap['kota']] ?? '')) : '';
                    
                    $dtVal = isset($colMap['daya_tampung']) ? (int)($row[$colMap['daya_tampung']] ?? 0) : 0;
                    $pmVal = isset($colMap['peminat']) ? (int)($row[$colMap['peminat']] ?? 0) : 0;

                    $jenjang = 'S1';
                    if (strpos($jenjangRaw, 'diploma iii') !== false || $jenjangRaw === 'd3') $jenjang = 'D3';
                    elseif (strpos($jenjangRaw, 'diploma iv') !== false || $jenjangRaw === 'd4') $jenjang = 'D4';
                    elseif (strpos($jenjangRaw, 'sarjana') !== false || $jenjangRaw === 's1') $jenjang = 'S1';
                    elseif (strpos($jenjangRaw, 'magister') !== false || $jenjangRaw === 's2') $jenjang = 'S2';
                    elseif (strpos($jenjangRaw, 'doktor') !== false || $jenjangRaw === 's3') $jenjang = 'S3';
                    elseif (strpos($jenjangRaw, 'profesi') !== false) $jenjang = 'Profesi';
                    elseif (strpos($jenjangRaw, 'd1') !== false) $jenjang = 'D1';
                    elseif (strpos($jenjangRaw, 'd2') !== false) $jenjang = 'D2';

                    // 1. Find or create Kampus
                    $stmt = $db->prepare("SELECT id FROM master_kampus WHERE nama_kampus = ? AND tenant_id = ?");
                    $stmt->execute([$namaKampus, $tenantId]);
                    $kampusId = $stmt->fetchColumn();

                    if (!$kampusId) {
                        $kampusId = $this->generateUuidV4();
                        $stmtIns = $db->prepare("INSERT INTO master_kampus (id, tenant_id, nama_kampus, kota_kampus, jenis_kampus) VALUES (?, ?, ?, ?, 'Negeri')");
                        $stmtIns->execute([$kampusId, $tenantId, $namaKampus, $kota]);
                        $insertedKampus++;
                    }

                    // 2. Find or create Prodi
                    $stmtCheckCol = $db->prepare("SHOW COLUMNS FROM `master_kampus_prodi` LIKE 'kode_prodi'");
                    $stmtCheckCol->execute();
                    $hasKodeCol = $stmtCheckCol->fetch() !== false;

                    if ($hasKodeCol) {
                        $stmtProdi = $db->prepare("SELECT id FROM master_kampus_prodi WHERE kampus_id = ? AND program_studi = ? AND jenjang = ?");
                        $stmtProdi->execute([$kampusId, $namaProdi, $jenjang]);
                        $prodiId = $stmtProdi->fetchColumn();

                        if (!$prodiId) {
                            $prodiId = $this->generateUuidV4();
                            $stmtInsP = $db->prepare("INSERT INTO master_kampus_prodi (id, kampus_id, kode_prodi, fakultas, program_studi, jenjang, jenis_portofolio) VALUES (?, ?, ?, '', ?, ?, ?)");
                            $stmtInsP->execute([$prodiId, $kampusId, $kodeProdi, $namaProdi, $jenjang, $portofolio]);
                            $insertedProdi++;
                        } else {
                            $db->prepare("UPDATE master_kampus_prodi SET kode_prodi = ?, jenis_portofolio = ? WHERE id = ?")->execute([$kodeProdi, $portofolio, $prodiId]);
                        }
                    } else {
                        $stmtProdi = $db->prepare("SELECT id FROM master_kampus_prodi WHERE kampus_id = ? AND program_studi = ? AND jenjang = ?");
                        $stmtProdi->execute([$kampusId, $namaProdi, $jenjang]);
                        $prodiId = $stmtProdi->fetchColumn();

                        if (!$prodiId) {
                            $prodiId = $this->generateUuidV4();
                            $stmtInsP = $db->prepare("INSERT INTO master_kampus_prodi (id, kampus_id, fakultas, program_studi, jenjang) VALUES (?, ?, '', ?, ?)");
                            $stmtInsP->execute([$prodiId, $kampusId, $namaProdi, $jenjang]);
                            $insertedProdi++;
                        }
                    }

                    // 3. Insert or Update Riwayat
                    $activeYear = $dtYear ?: date('Y');
                    $stmtRiw = $db->prepare("
                        INSERT INTO kampus_prodi_riwayat (prodi_id, tahun, daya_tampung, jumlah_pendaftar)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE daya_tampung = VALUES(daya_tampung), jumlah_pendaftar = VALUES(jumlah_pendaftar)
                    ");
                    $stmtRiw->execute([$prodiId, $activeYear, $dtVal, $pmVal]);
                    $insertedRiwayat++;
                }

                $db->commit();
                $this->jsonResponse([
                    'success' => true,
                    'message' => "Import berhasil: +{$insertedKampus} Kampus, +{$insertedProdi} Prodi, +{$insertedRiwayat} Riwayat."
                ]);
            } catch (\Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $this->jsonResponse(['error' => 'Gagal import: ' . $e->getMessage()], 500);
            }
        } else {
            $this->jsonResponse(['error' => \Shuchkin\SimpleXLSX::parseError()], 422);
        }
    }

    // ========================================================
    // GET ALL DATA (For Dropdowns)
    // ========================================================
    public function apiGetAllKampusProdi()
    {
        $tenantId = $this->checkAccess();
        $db = Database::getConnection();

        $stmtCheckCol = $db->prepare("SHOW COLUMNS FROM `master_kampus_prodi` LIKE 'kode_prodi'");
        $stmtCheckCol->execute();
        $hasKodeCol = $stmtCheckCol->fetch() !== false;

        $selectCols = "p.id as prodi_id, k.id as kampus_id, k.nama_kampus, p.program_studi, p.fakultas, p.jenjang";
        if ($hasKodeCol) {
            $selectCols .= ", p.kode_prodi, p.jenis_portofolio";
        }

        $stmt = $db->prepare("
            SELECT $selectCols
            FROM master_kampus_prodi p
            JOIN master_kampus k ON p.kampus_id = k.id
            WHERE k.tenant_id = ?
            ORDER BY k.nama_kampus ASC, p.program_studi ASC
        ");
        $stmt->execute([$tenantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    private function generateUuidV4(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function sanitizeStr($str) {
        return trim(htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'));
    }

    public function apiGetMasterKampusProdiFlat()
    {
        $tenantId = $this->checkAccess();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT 
                k.id as kampus_id, k.nama_kampus, k.kota_kampus, k.jenis_kampus,
                p.id as prodi_id, p.kode_prodi, p.fakultas, p.program_studi, p.jenjang, p.jenis_portofolio
            FROM master_kampus k
            LEFT JOIN master_kampus_prodi p ON p.kampus_id = k.id
            WHERE k.tenant_id = ?
            ORDER BY k.nama_kampus ASC, p.program_studi ASC
        ");
        $stmt->execute([$tenantId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $prodiIds = array_filter(array_column($rows, 'prodi_id'));
        $riwayatMap = [];

        if (!empty($prodiIds)) {
            $inClause = implode(',', array_fill(0, count($prodiIds), '?'));
            $stmtRiwayat = $db->prepare("SELECT prodi_id, tahun, daya_tampung, jumlah_pendaftar FROM kampus_prodi_riwayat WHERE prodi_id IN ($inClause) ORDER BY tahun DESC");
            $stmtRiwayat->execute($prodiIds);
            $riwayatRows = $stmtRiwayat->fetchAll(PDO::FETCH_ASSOC);

            foreach ($riwayatRows as $r) {
                $pid = $r['prodi_id'];
                if (!isset($riwayatMap[$pid])) $riwayatMap[$pid] = [];
                $riwayatMap[$pid][] = $r;
            }
        }

        foreach ($rows as &$row) {
            $pid = $row['prodi_id'];
            $row['riwayat'] = $pid && isset($riwayatMap[$pid]) ? $riwayatMap[$pid] : [];
        }

        $this->jsonResponse(['success' => true, 'data' => $rows]);
    }

    public function apiBulkDeleteRiwayat()
    {
        $tenantId = $this->checkAccess();
        $body = $this->getJsonInput();

        $tahun = isset($body['tahun']) ? (int)$body['tahun'] : null;
        $kampusId = $this->sanitizeStr($body['kampus_id'] ?? '');

        if (!$tahun && !$kampusId) {
            $this->jsonResponse(['error' => 'Pilih Tahun atau Kampus untuk dihapus'], 422);
        }

        $db = Database::getConnection();
        
        if ($kampusId && !$tahun) {
            $stmt = $db->prepare("DELETE r FROM kampus_prodi_riwayat r JOIN master_kampus_prodi p ON p.id = r.prodi_id JOIN master_kampus k ON k.id = p.kampus_id WHERE k.id = ? AND k.tenant_id = ?");
            $stmt->execute([$kampusId, $tenantId]);
        } elseif ($tahun && !$kampusId) {
            $stmt = $db->prepare("DELETE r FROM kampus_prodi_riwayat r JOIN master_kampus_prodi p ON p.id = r.prodi_id JOIN master_kampus k ON k.id = p.kampus_id WHERE r.tahun = ? AND k.tenant_id = ?");
            $stmt->execute([$tahun, $tenantId]);
        } else {
            $stmt = $db->prepare("DELETE r FROM kampus_prodi_riwayat r JOIN master_kampus_prodi p ON p.id = r.prodi_id JOIN master_kampus k ON k.id = p.kampus_id WHERE r.tahun = ? AND k.id = ? AND k.tenant_id = ?");
            $stmt->execute([$tahun, $kampusId, $tenantId]);
        }

        $this->jsonResponse(['success' => true, 'message' => 'Riwayat berhasil dihapus']);
    }

    public function apiExportDayaTampung()
    {
        $tenantId = $this->checkAccess();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT 
                k.nama_kampus,
                p.kode_prodi,
                p.program_studi
            FROM master_kampus k
            JOIN master_kampus_prodi p ON p.kampus_id = k.id
            WHERE k.tenant_id = ?
            ORDER BY k.nama_kampus ASC, p.program_studi ASC
        ");
        $stmt->execute([$tenantId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            [
                '[[b]]KODE_PRODI',
                '[[b]]NAMA_PRODI',
                '[[b]]KAMPUS',
                '[[b]]TAHUN',
                '[[b]]DAYA_TAMPUNG',
                '[[b]]JUMLAH_PENDAFTAR',
            ]
        ];

        if (count($rows) === 0) {
            $data[] = ['', 'Belum ada prodi, tambahkan dulu dari sistem', '', 2026, 0, 0];
        } else {
            foreach ($rows as $row) {
                $data[] = [
                    (string)($row['kode_prodi'] ?? ''),
                    (string)($row['program_studi'] ?? ''),
                    (string)($row['nama_kampus'] ?? ''),
                    2026,
                    0,
                    0,
                ];
            }
        }

        \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs('Template_Update_Daya_Tampung.xlsx');
        exit;
    }

    public function apiImportDayaTampung()
    {
        $tenantId = $this->checkAccess();
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'Gagal mengupload file.'], 400);
        }

        $tmpName = $_FILES['file']['tmp_name'];
        
        try {
            $xlsx = \Shuchkin\SimpleXLSX::parse($tmpName);
            if (!$xlsx) {
                throw new \Exception('Gagal membaca berkas Excel (.xlsx): ' . \Shuchkin\SimpleXLSX::parseError());
            }
            $rows = $xlsx->rows();
            if (count($rows) < 2) {
                throw new \Exception('File Excel kosong atau tidak sesuai template.');
            }
            
            $header = array_map(function($h) {
                $h = preg_replace('/^\[\[[^\]]+\]\]/', '', (string)$h);
                return strtolower(trim($h));
            }, $rows[0]);
            
            $colMap = [
                'kode' => array_search('kode_prodi', $header),
                'prodi' => array_search('nama_prodi', $header),
                'kampus' => array_search('kampus', $header),
                'tahun' => array_search('tahun', $header),
                'daya_tampung' => array_search('daya_tampung', $header),
                'pendaftar' => array_search('jumlah_pendaftar', $header)
            ];

            if ($colMap['prodi'] === false || $colMap['kampus'] === false || $colMap['tahun'] === false) {
                throw new \Exception('Kolom wajib (NAMA_PRODI, KAMPUS, TAHUN) tidak ditemukan. Kolom terdeteksi: ' . implode(', ', $header));
            }

            $db = Database::getConnection();
            $db->beginTransaction();

            $updated = 0;
            $inserted = 0;

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                $kode = $colMap['kode'] !== false ? trim((string)($row[$colMap['kode']] ?? '')) : '';
                $prodi = trim((string)($row[$colMap['prodi']] ?? ''));
                $kampus = trim((string)($row[$colMap['kampus']] ?? ''));
                $tahun = (int)($row[$colMap['tahun']] ?? 0);
                $dt = $colMap['daya_tampung'] !== false ? (int)($row[$colMap['daya_tampung']] ?? 0) : 0;
                $pendaftar = $colMap['pendaftar'] !== false ? (int)($row[$colMap['pendaftar']] ?? 0) : 0;

                if (!$prodi || !$kampus || !$tahun) continue;

                // Cari Prodi
                $stmtFind = $db->prepare("
                    SELECT p.id 
                    FROM master_kampus_prodi p 
                    JOIN master_kampus k ON k.id = p.kampus_id 
                    WHERE k.tenant_id = ? AND k.nama_kampus = ? AND p.program_studi = ?
                ");
                $stmtFind->execute([$tenantId, $kampus, $prodi]);
                $prodiId = $stmtFind->fetchColumn();

                if (!$prodiId) {
                    continue; // Skip jika tidak ditemukan
                }

                // Upsert Riwayat
                $stmtRiwayat = $db->prepare("
                    INSERT INTO kampus_prodi_riwayat (prodi_id, tahun, daya_tampung, jumlah_pendaftar)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE daya_tampung = VALUES(daya_tampung), jumlah_pendaftar = VALUES(jumlah_pendaftar)
                ");
                $stmtRiwayat->execute([$prodiId, $tahun, $dt, $pendaftar]);
                $inserted++;
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => "Berhasil memperbarui $inserted riwayat daya tampung."]);

        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 422);
        }
    }


    public function apiExportKampusProdi()
    {
        $tenantId = $this->checkAccess();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT 
                k.nama_kampus,
                k.kota_kampus,
                k.jenis_kampus,
                p.kode_prodi,
                p.program_studi,
                p.fakultas,
                p.jenjang,
                p.jenis_portofolio
            FROM master_kampus k
            LEFT JOIN master_kampus_prodi p ON p.kampus_id = k.id
            WHERE k.tenant_id = ?
            ORDER BY k.nama_kampus ASC, p.program_studi ASC
        ");
        $stmt->execute([$tenantId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            [
                '[[b]]NAMA_KAMPUS',
                '[[b]]KOTA_KAMPUS',
                '[[b]]JENIS_KAMPUS',
                '[[b]]KODE_PRODI',
                '[[b]]NAMA_PRODI',
                '[[b]]FAKULTAS',
                '[[b]]JENJANG',
                '[[b]]JENIS_PORTOFOLIO',
            ]
        ];

        if (count($rows) === 0) {
            $data[] = [
                'Contoh: UI (Universitas Indonesia)', 'Jakarta', 'PTN',
                '13211001', 'PENDIDIKAN DOKTER', 'Kedokteran', 'Sarjana', 'Tidak Ada'
            ];
        } else {
            foreach ($rows as $row) {
                $data[] = [
                    (string)($row['nama_kampus'] ?? ''),
                    (string)($row['kota_kampus'] ?? ''),
                    (string)($row['jenis_kampus'] ?? 'PTN'),
                    (string)($row['kode_prodi'] ?? ''),
                    (string)($row['program_studi'] ?? ''),
                    (string)($row['fakultas'] ?? ''),
                    (string)($row['jenjang'] ?? 'Sarjana'),
                    (string)($row['jenis_portofolio'] ?? 'Tidak Ada'),
                ];
            }
        }

        \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs('Template_Kampus_Prodi.xlsx');
        exit;
    }

    public function apiImportKampusProdi()
    {
        $tenantId = $this->checkAccess();
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'Gagal mengupload file.'], 400);
        }

        $tmpName = $_FILES['file']['tmp_name'];
        
        try {
            $xlsx = \Shuchkin\SimpleXLSX::parse($tmpName);
            if (!$xlsx) {
                throw new \Exception('Gagal membaca berkas Excel (.xlsx): ' . \Shuchkin\SimpleXLSX::parseError());
            }
            $rows = $xlsx->rows();
            if (count($rows) < 2) {
                throw new \Exception('File Excel kosong atau tidak sesuai template.');
            }

            $header = array_map(function($h) {
                $h = preg_replace('/^\[\[[^\]]+\]\]/', '', (string)$h);
                return strtolower(trim($h));
            }, $rows[0]);

            $colNamaKampus   = array_search('nama_kampus',       $header);
            $colKotaKampus   = array_search('kota_kampus',       $header);
            $colJenisKampus  = array_search('jenis_kampus',      $header);
            $colKodeProdi    = array_search('kode_prodi',        $header);
            $colNamaProdi    = array_search('nama_prodi',        $header);
            $colFakultas     = array_search('fakultas',          $header);
            $colJenjang      = array_search('jenjang',           $header);
            $colPortofolio   = array_search('jenis_portofolio',  $header);

            if ($colNamaKampus === false || $colNamaProdi === false) {
                throw new \Exception('Kolom wajib (NAMA_KAMPUS, NAMA_PRODI) tidak ditemukan. Kolom terdeteksi: ' . implode(', ', $header));
            }

            $db = Database::getConnection();
            $db->beginTransaction();

            $kampusCache = [];
            $inserted = 0;
            $updated  = 0;

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                $namaKampus  = trim((string)($row[$colNamaKampus] ?? ''));
                $kotaKampus  = $colKotaKampus  !== false ? trim((string)($row[$colKotaKampus]  ?? '')) : '';
                $jenisKampus = $colJenisKampus  !== false ? trim((string)($row[$colJenisKampus] ?? 'PTN')) : 'PTN';
                $kodeProdi   = $colKodeProdi    !== false ? trim((string)($row[$colKodeProdi]   ?? '')) : '';
                $namaProdi   = trim((string)($row[$colNamaProdi] ?? ''));
                $fakultas    = $colFakultas     !== false ? trim((string)($row[$colFakultas]    ?? '')) : '';
                $jenjang     = $colJenjang      !== false ? trim((string)($row[$colJenjang]     ?? 'Sarjana')) : 'Sarjana';
                $portofolio  = $colPortofolio   !== false ? trim((string)($row[$colPortofolio]  ?? 'Tidak Ada')) : 'Tidak Ada';

                if (!$namaKampus || !$namaProdi) continue;

                // Upsert Kampus
                if (!isset($kampusCache[$namaKampus])) {
                    $stmtFind = $db->prepare("SELECT id FROM master_kampus WHERE tenant_id = ? AND nama_kampus = ? LIMIT 1");
                    $stmtFind->execute([$tenantId, $namaKampus]);
                    $kampusId = $stmtFind->fetchColumn();

                    if (!$kampusId) {
                        $newId = sprintf('%08x-%04x-4%03x-%04x-%012x',
                            mt_rand(0, 0xffffffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff), mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffffffffffff));
                        $stmtIns = $db->prepare("INSERT INTO master_kampus (id, tenant_id, nama_kampus, kota_kampus, jenis_kampus) VALUES (?, ?, ?, ?, ?)");
                        $stmtIns->execute([$newId, $tenantId, $namaKampus, $kotaKampus, $jenisKampus]);
                        $kampusId = $newId;
                    } elseif ($kotaKampus) {
                        // UPDATE hanya boleh jika kampus memang milik tenant ini
                        $stmtUpd = $db->prepare("UPDATE master_kampus SET kota_kampus = ?, jenis_kampus = ? WHERE id = ? AND tenant_id = ?");
                        $stmtUpd->execute([$kotaKampus, $jenisKampus, $kampusId, $tenantId]);
                    }
                    $kampusCache[$namaKampus] = $kampusId;
                }
                $kampusId = $kampusCache[$namaKampus];

                // Upsert Prodi — pastikan kampus_id yang dipilih memang milik tenant ini
                $stmtFindProdi = $db->prepare("
                    SELECT p.id FROM master_kampus_prodi p
                    JOIN master_kampus k ON k.id = p.kampus_id
                    WHERE p.kampus_id = ? AND p.program_studi = ? AND k.tenant_id = ?
                    LIMIT 1
                ");
                $stmtFindProdi->execute([$kampusId, $namaProdi, $tenantId]);
                $prodiId = $stmtFindProdi->fetchColumn();

                if (!$prodiId) {
                    $newProdiId = sprintf('%08x-%04x-4%03x-%04x-%012x',
                        mt_rand(0, 0xffffffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff), mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffffffffffff));
                    $stmtInsProdi = $db->prepare("INSERT INTO master_kampus_prodi (id, kampus_id, kode_prodi, program_studi, fakultas, jenjang, jenis_portofolio) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmtInsProdi->execute([$newProdiId, $kampusId, $kodeProdi, $namaProdi, $fakultas, $jenjang, $portofolio]);
                    $inserted++;
                } else {
                    // UPDATE prodi — validasi ownership kembali melalui kampus
                    $stmtUpdProdi = $db->prepare("
                        UPDATE master_kampus_prodi p
                        JOIN master_kampus k ON k.id = p.kampus_id
                        SET p.kode_prodi = ?, p.fakultas = ?, p.jenjang = ?, p.jenis_portofolio = ?
                        WHERE p.id = ? AND k.tenant_id = ?
                    ");
                    $stmtUpdProdi->execute([$kodeProdi, $fakultas, $jenjang, $portofolio, $prodiId, $tenantId]);
                    $updated++;
                }
            }

            $db->commit();
            $this->jsonResponse([
                'success' => true,
                'message' => "Berhasil: $inserted prodi baru ditambahkan, $updated prodi diperbarui."
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 422);
        }
    }

}
