<?php
namespace App\Controllers;

use App\Core\SessionManager;
use App\Config\Database;
use PDO;

class SppController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    private $parsedJsonBody = null;

    /**
     * Resolve Tenant ID dynamically for both normal tenants and super_admin
     */
    private function resolveTenantId(): string {
        $role = $_SESSION['role_name'] ?? '';
        if ($role === 'super_admin') {
            // 1. Direct GET parameter
            if (!empty($_GET['tenant_id'])) {
                return $_GET['tenant_id'];
            }
            // 2. Direct POST parameter
            if (!empty($_POST['tenant_id'])) {
                return $_POST['tenant_id'];
            }
            // 3. JSON body input
            if ($this->parsedJsonBody === null) {
                $this->parsedJsonBody = json_decode(file_get_contents('php://input'), true) ?? [];
            }
            if (!empty($this->parsedJsonBody['tenant_id'])) {
                return $this->parsedJsonBody['tenant_id'];
            }
            // 4. Referer URL query string (for AJAX requests)
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $parts = parse_url($_SERVER['HTTP_REFERER']);
                if (!empty($parts['query'])) {
                    parse_str($parts['query'], $query);
                    if (!empty($query['tenant_id'])) {
                        return $query['tenant_id'];
                    }
                }
            }
            // 5. Fallback: Ambil tenant pertama dari database jika super_admin tidak mengirimkan tenant_id
            try {
                $db = Database::getConnection();
                $stmt = $db->query("SELECT id FROM tenants LIMIT 1");
                $fallbackId = $stmt->fetchColumn();
                if ($fallbackId) {
                    return $fallbackId;
                }
            } catch (\Throwable $e) {
                // Abaikan error koneksi database di fallback
            }
        }
        return $_SESSION['tenant_id'] ?? '';
    }

    public function apiTenants(): void {
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $this->jsonResponse(['success' => false, 'error' => 'Forbidden.'], 403);
        }
        try {
            $db = Database::getConnection();
            $tenants = $db->query("SELECT id, nama_sekolah FROM tenants ORDER BY nama_sekolah ASC")->fetchAll(PDO::FETCH_ASSOC);
            $this->jsonResponse(['success' => true, 'data' => $tenants]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiToggleKomponen(): void {
        try {
            $tenantId = $this->resolveTenantId();
            $raw = json_decode(file_get_contents('php://input'), true);
            $id = (int)($raw['id'] ?? 0);
            $isActive = (int)($raw['is_active'] ?? 0);

            if (!$id) {
                $this->jsonResponse(['success' => false, 'error' => 'ID komponen wajib diisi.'], 422);
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE transaksi_spp_komponen SET is_active = ? WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$isActive, $id, $tenantId]);

            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    // ----------------------------------------------------
    // PAGE RENDERERS
    // ----------------------------------------------------

    public function dashboard(): void {
        $this->render('keuangan/dashboard', ['title' => 'Dashboard Keuangan']);
    }

    public function master(): void {
        $db = Database::getConnection();
        
        $kelas = $db->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
        $jenjang = $db->query("SELECT id, nama_jenjang FROM jenjang ORDER BY nama_jenjang ASC")->fetchAll(PDO::FETCH_ASSOC);
        $tahunAjaran = $db->query("SELECT id, tahun_ajaran, IF(is_active = 1, 'Aktif', 'Non-Aktif') as status FROM tahun_ajaran ORDER BY tahun_ajaran DESC")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('keuangan/master', [
            'title' => 'Master Tarif & Biaya',
            'list_kelas' => $kelas,
            'list_jenjang' => $jenjang,
            'list_ta' => $tahunAjaran
        ]);
    }

    public function keringanan(): void {
        $db = Database::getConnection();
        $tenantId = $this->resolveTenantId();
        $stmt = $db->prepare("SELECT id, nama_komponen FROM transaksi_spp_komponen WHERE tenant_id = ? ORDER BY nama_komponen ASC");
        $stmt->execute([$tenantId]);
        $komponen = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('keuangan/keringanan', [
            'title' => 'Keringanan & Beasiswa',
            'list_komponen' => $komponen
        ]);
    }

    public function generate(): void {
        $db = Database::getConnection();
        $tenantId = $this->resolveTenantId();
        
        $kelas = $db->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
        $jenjang = $db->query("SELECT id, nama_jenjang FROM jenjang ORDER BY nama_jenjang ASC")->fetchAll(PDO::FETCH_ASSOC);
        $tahunAjaran = $db->query("SELECT id, tahun_ajaran, IF(is_active = 1, 'Aktif', 'Non-Aktif') as status FROM tahun_ajaran ORDER BY tahun_ajaran DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("SELECT id, nama_komponen, tipe_periode FROM transaksi_spp_komponen WHERE tenant_id = ? AND is_active = 1 ORDER BY nama_komponen ASC");
        $stmt->execute([$tenantId]);
        $komponen = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('keuangan/generate', [
            'title' => 'Generate Tagihan Massal',
            'list_kelas' => $kelas,
            'list_jenjang' => $jenjang,
            'list_ta' => $tahunAjaran,
            'list_komponen' => $komponen
        ]);
    }

    public function kasir(): void {
        $this->render('keuangan/kasir', ['title' => 'Loket Pembayaran Kasir']);
    }

    public function laporan(): void {
        $this->render('keuangan/laporan', ['title' => 'Laporan Keuangan']);
    }

    public function pengaturan(): void {
        $this->render('keuangan/pengaturan', ['title' => 'Pengaturan Modul Keuangan']);
    }

    public function tagihanSaya(): void {
        $this->render('keuangan/tagihan_saya', ['title' => 'Tagihan Keuangan Saya']);
    }

    // ----------------------------------------------------
    // API ENDPOINTS
    // ----------------------------------------------------

    // API: Dashboard Metrics
    public function apiDashboardMetrics(): void {
        $tenantId = $this->resolveTenantId();
        $db = Database::getConnection();

        try {
            // 1. Total Tunggakan
            $stmtTunggakan = $db->prepare("
                SELECT SUM(nominal_tagihan - nominal_bayar) 
                FROM transaksi_spp_tagihan 
                WHERE tenant_id = ? AND status_lunas != 'Lunas'
            ");
            $stmtTunggakan->execute([$tenantId]);
            $totalTunggakan = (float)$stmtTunggakan->fetchColumn();

            // 2. Pemasukan Hari Ini
            $stmtHariIni = $db->prepare("
                SELECT SUM(nominal_dibayar) 
                FROM transaksi_spp_pembayaran 
                WHERE tenant_id = ? AND DATE(tanggal_bayar) = CURDATE() AND status_transaksi = 'Success'
            ");
            $stmtHariIni->execute([$tenantId]);
            $pemasukanHariIni = (float)$stmtHariIni->fetchColumn();

            // 3. Pemasukan Bulan Ini
            $stmtBulanIni = $db->prepare("
                SELECT SUM(nominal_dibayar) 
                FROM transaksi_spp_pembayaran 
                WHERE tenant_id = ? 
                  AND MONTH(tanggal_bayar) = MONTH(CURDATE()) 
                  AND YEAR(tanggal_bayar) = YEAR(CURDATE())
                  AND status_transaksi = 'Success'
            ");
            $stmtBulanIni->execute([$tenantId]);
            $pemasukanBulanIni = (float)$stmtBulanIni->fetchColumn();

            // 4. Progres Pelunasan per Jenjang/Kelas
            $stmtProgres = $db->prepare("
                SELECT k.nama_kelas,
                       SUM(t.nominal_tagihan) as total_tagihan,
                       SUM(t.nominal_bayar) as total_bayar
                FROM transaksi_spp_tagihan t
                JOIN siswa s ON t.siswa_id = s.id
                JOIN kelas k ON s.id_kelas = k.id
                WHERE t.tenant_id = ?
                GROUP BY k.id
                ORDER BY k.nama_kelas ASC
            ");
            $stmtProgres->execute([$tenantId]);
            $progresKelas = $stmtProgres->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'total_tunggakan' => $totalTunggakan,
                    'pemasukan_hari_ini' => $pemasukanHariIni,
                    'pemasukan_bulan_ini' => $pemasukanBulanIni,
                    'progres_kelas' => $progresKelas
                ]
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: CRUD Komponen Biaya
    public function apiKomponen(): void {
        try {
            $tenantId = $this->resolveTenantId();
            $db = Database::getConnection();
            $method = $_SERVER['REQUEST_METHOD'];

            if ($method === 'GET') {
                $role = $_SESSION['role_name'] ?? '';
                if ($role === 'super_admin' && empty($_GET['tenant_id'])) {
                    $stmt = $db->query("SELECT k.*, tn.nama_sekolah FROM transaksi_spp_komponen k JOIN tenants tn ON k.tenant_id = tn.id ORDER BY k.id DESC");
                    $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                } else {
                    $stmt = $db->prepare("SELECT k.*, tn.nama_sekolah FROM transaksi_spp_komponen k JOIN tenants tn ON k.tenant_id = tn.id WHERE k.tenant_id = ? ORDER BY k.id DESC");
                    $stmt->execute([$tenantId]);
                    $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                }
            } elseif ($method === 'POST') {
                $raw = json_decode(file_get_contents('php://input'), true);
                $id = (int)($raw['id'] ?? 0);
                $nama = trim($raw['nama_komponen'] ?? '');
                $tipe = trim($raw['tipe_periode'] ?? 'Bulanan');
                $role = $_SESSION['role_name'] ?? '';
                if ($role === 'super_admin' && !empty($raw['tenant_id'])) {
                    $tenantId = $raw['tenant_id'];
                } else {
                    $tenantId = $this->resolveTenantId();
                }

                if (empty($nama)) {
                    $this->jsonResponse(['success' => false, 'error' => 'Nama komponen wajib diisi.'], 422);
                }

                if ($id > 0) {
                    // Update
                    $stmt = $db->prepare("UPDATE transaksi_spp_komponen SET nama_komponen = ?, tipe_periode = ? WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$nama, $tipe, $id, $tenantId]);
                } else {
                    // Insert
                    $stmt = $db->prepare("INSERT INTO transaksi_spp_komponen (tenant_id, nama_komponen, tipe_periode) VALUES (?, ?, ?)");
                    $stmt->execute([$tenantId, $nama, $tipe]);
                }
                $this->jsonResponse(['success' => true]);
            } elseif ($method === 'DELETE') {
                $id = (int)($_GET['id'] ?? 0);
                $stmt = $db->prepare("DELETE FROM transaksi_spp_komponen WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenantId]);
                $this->jsonResponse(['success' => true]);
            }
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: CRUD Tarif Default
    public function apiTarif(): void {
        try {
            $tenantId = $this->resolveTenantId();
            $db = Database::getConnection();
            $method = $_SERVER['REQUEST_METHOD'];

            if ($method === 'GET') {
                $role = $_SESSION['role_name'] ?? '';
                if ($role === 'super_admin' && empty($_GET['tenant_id'])) {
                    $stmt = $db->prepare("
                        SELECT t.*, k.nama_komponen, c.nama_kelas, j.nama_jenjang, ta.tahun_ajaran, tn.nama_sekolah
                        FROM transaksi_spp_tarif t
                        JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
                        LEFT JOIN kelas c ON t.kelas_id = c.id
                        LEFT JOIN jenjang j ON t.jenjang_id = j.id
                        JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
                        JOIN tenants tn ON t.tenant_id = tn.id
                        ORDER BY ta.tahun_ajaran DESC, t.id DESC
                    ");
                    $stmt->execute([]);
                } else {
                    $stmt = $db->prepare("
                        SELECT t.*, k.nama_komponen, c.nama_kelas, j.nama_jenjang, ta.tahun_ajaran, tn.nama_sekolah
                        FROM transaksi_spp_tarif t
                        JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
                        LEFT JOIN kelas c ON t.kelas_id = c.id
                        LEFT JOIN jenjang j ON t.jenjang_id = j.id
                        JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
                        JOIN tenants tn ON t.tenant_id = tn.id
                        WHERE t.tenant_id = ?
                        ORDER BY ta.tahun_ajaran DESC, t.id DESC
                    ");
                    $stmt->execute([$tenantId]);
                }
                $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } elseif ($method === 'POST') {
                $raw = json_decode(file_get_contents('php://input'), true);
                $komponenId = (int)($raw['komponen_id'] ?? 0);
                $kelasId = !empty($raw['kelas_id']) ? (int)$raw['kelas_id'] : null;
                $jenjangId = !empty($raw['jenjang_id']) ? (int)$raw['jenjang_id'] : null;
                $jalurMasuk = !empty($raw['jalur_masuk']) ? trim($raw['jalur_masuk']) : null;
                $nominal = (float)($raw['nominal'] ?? 0.00);
                $tahunAjaranId = (int)($raw['tahun_ajaran_id'] ?? 0);

                if (!$komponenId || !$tahunAjaranId || $nominal <= 0) {
                    $this->jsonResponse(['success' => false, 'error' => 'Komponen, tahun ajaran, dan nominal wajib diisi.'], 422);
                }

                // Get target tenant_id from selected component
                $stmtComp = $db->prepare("SELECT tenant_id FROM transaksi_spp_komponen WHERE id = ?");
                $stmtComp->execute([$komponenId]);
                $compTenantId = $stmtComp->fetchColumn();

                if (!$compTenantId) {
                    $this->jsonResponse(['success' => false, 'error' => 'Komponen tidak valid.'], 422);
                }

                $stmt = $db->prepare("
                    INSERT INTO transaksi_spp_tarif (tenant_id, komponen_id, kelas_id, jenjang_id, jalur_masuk, nominal, tahun_ajaran_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$compTenantId, $komponenId, $kelasId, $jenjangId, $jalurMasuk, $nominal, $tahunAjaranId]);
                $this->jsonResponse(['success' => true]);
            } elseif ($method === 'DELETE') {
                $id = (int)($_GET['id'] ?? 0);
                $stmt = $db->prepare("DELETE FROM transaksi_spp_tarif WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenantId]);
                $this->jsonResponse(['success' => true]);
            }
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: CRUD Keringanan & Beasiswa
    public function apiKeringanan(): void {
        try {
            $tenantId = $this->resolveTenantId();
            $db = Database::getConnection();
            $method = $_SERVER['REQUEST_METHOD'];

            if ($method === 'GET') {
                $stmt = $db->prepare("
                    SELECT k.*, s.nama_lengkap as nama_siswa, s.nisn, komp.nama_komponen
                    FROM transaksi_spp_keringanan k
                    JOIN siswa s ON k.siswa_id = s.id
                    JOIN transaksi_spp_komponen komp ON k.komponen_id = komp.id
                    WHERE k.tenant_id = ?
                    ORDER BY k.id DESC
                ");
                $stmt->execute([$tenantId]);
                $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } elseif ($method === 'POST') {
                $raw = json_decode(file_get_contents('php://input'), true);
                $siswaId = trim($raw['siswa_id'] ?? '');
                $komponenId = (int)($raw['komponen_id'] ?? 0);
                $tipe = trim($raw['tipe_keringanan'] ?? 'Nominal');
                $nilai = (float)($raw['nilai'] ?? 0);
                $keterangan = trim($raw['keterangan'] ?? '');

                if (empty($siswaId) || !$komponenId || $nilai <= 0) {
                    $this->jsonResponse(['success' => false, 'error' => 'Siswa, komponen, dan nilai beasiswa wajib diisi.'], 422);
                }

                $stmt = $db->prepare("
                    INSERT INTO transaksi_spp_keringanan (tenant_id, siswa_id, komponen_id, tipe_keringanan, nilai, keterangan)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$tenantId, $siswaId, $komponenId, $tipe, $nilai, $keterangan]);
                $this->jsonResponse(['success' => true]);
            } elseif ($method === 'DELETE') {
                $id = (int)($_GET['id'] ?? 0);
                $stmt = $db->prepare("DELETE FROM transaksi_spp_keringanan WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenantId]);
                $this->jsonResponse(['success' => true]);
            }
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Generate Tagihan Massal
    public function apiGenerateTagihan(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
        }

        $tenantId = $this->resolveTenantId();
        $raw = json_decode(file_get_contents('php://input'), true);
        
        $komponenId = (int)($raw['komponen_id'] ?? 0);
        $tahunAjaranId = (int)($raw['tahun_ajaran_id'] ?? 0);
        $bulan = !empty($raw['bulan']) ? (int)$raw['bulan'] : null;
        $kelasId = !empty($raw['kelas_id']) ? (int)$raw['kelas_id'] : null;
        $jenjangId = !empty($raw['jenjang_id']) ? (int)$raw['jenjang_id'] : null;

        if (!$komponenId || !$tahunAjaranId) {
            $this->jsonResponse(['success' => false, 'error' => 'Komponen dan tahun ajaran wajib diisi.'], 422);
        }

        $db = Database::getConnection();

        try {
            // Ambil data komponen biaya
            $stmtComp = $db->prepare("SELECT tipe_periode FROM transaksi_spp_komponen WHERE id = ? AND tenant_id = ?");
            $stmtComp->execute([$komponenId, $tenantId]);
            $tipePeriode = $stmtComp->fetchColumn();
            if (!$tipePeriode) {
                $this->jsonResponse(['success' => false, 'error' => 'Komponen biaya tidak valid.'], 422);
            }

            // Validasi bulan jika bulanan
            if ($tipePeriode === 'Bulanan' && !$bulan) {
                $this->jsonResponse(['success' => false, 'error' => 'Bulan wajib ditentukan untuk komponen bulanan.'], 422);
            }

            $siswaIds = !empty($raw['siswa_ids']) && is_array($raw['siswa_ids']) ? $raw['siswa_ids'] : [];

            // Ambil daftar siswa aktif untuk target filter
            $sqlSiswa = "SELECT s.id, s.id_kelas, r.jalur_diterima as jalur_masuk 
                         FROM siswa s 
                         LEFT JOIN registrasi r ON s.id = r.id_siswa 
                         WHERE s.tenant_id = ? AND s.status = 'Aktif' AND s.deleted_at IS NULL";
            $paramsSiswa = [$tenantId];

            if ($kelasId) {
                $sqlSiswa .= " AND s.id_kelas = ?";
                $paramsSiswa[] = $kelasId;
            } elseif ($jenjangId) {
                $sqlSiswa .= " AND s.id_kelas IN (SELECT id FROM kelas WHERE id_jenjang = ?)";
                $paramsSiswa[] = $jenjangId;
            }

            if (!empty($siswaIds)) {
                $placeholders = implode(',', array_fill(0, count($siswaIds), '?'));
                $sqlSiswa .= " AND s.id IN ($placeholders)";
                foreach ($siswaIds as $sid) {
                    $paramsSiswa[] = $sid;
                }
            }

            $stmtSiswa = $db->prepare($sqlSiswa);
            $stmtSiswa->execute($paramsSiswa);
            $students = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

            if (empty($students)) {
                $this->jsonResponse(['success' => false, 'error' => 'Tidak ditemukan siswa aktif untuk kriteria yang dipilih.'], 422);
            }

            // Mulai DB Transaction agar insert masif cepat dan aman
            $db->beginTransaction();

            $stmtTarif = $db->prepare("
                SELECT id, nominal, kelas_id, jenjang_id, jalur_masuk 
                FROM transaksi_spp_tarif 
                WHERE komponen_id = ? AND tahun_ajaran_id = ? AND tenant_id = ?
            ");
            $stmtTarif->execute([$komponenId, $tahunAjaranId, $tenantId]);
            $tarifs = $stmtTarif->fetchAll(PDO::FETCH_ASSOC);

            // Statement checking for duplicate invoice to make generator idempotent
            $stmtCheckDup = $db->prepare("
                SELECT id FROM transaksi_spp_tagihan 
                WHERE tenant_id = ? AND siswa_id = ? AND komponen_id = ? AND tahun_ajaran_id = ? 
                  AND (bulan = ? OR (bulan IS NULL AND ? IS NULL))
            ");

            $stmtInsert = $db->prepare("
                INSERT INTO transaksi_spp_tagihan (id, tenant_id, siswa_id, komponen_id, tarif_id, tahun_ajaran_id, bulan, nominal_tagihan, status_lunas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Belum')
            ");

            $generatedCount = 0;

            foreach ($students as $stu) {
                // Tentukan tarif terbaik berdasarkan prioritas: Kelas > Jenjang > Jalur Masuk > General
                $matchedTarif = null;
                $highestNominal = 0.00;

                foreach ($tarifs as $tr) {
                    if ($tr['kelas_id'] == $stu['id_kelas']) {
                        $matchedTarif = $tr;
                        break; // Kelas memiliki prioritas tertinggi
                    }
                    if ($tr['jenjang_id'] && !$tr['kelas_id']) {
                        // lookup jenjang kelas
                        $stmtCheckJenjang = $db->prepare("SELECT id FROM kelas WHERE id = ? AND id_jenjang = ? LIMIT 1");
                        $stmtCheckJenjang->execute([$stu['id_kelas'], $tr['jenjang_id']]);
                        if ($stmtCheckJenjang->fetchColumn()) {
                            $matchedTarif = $tr;
                        }
                    }
                    if ($tr['jalur_masuk'] && $tr['jalur_masuk'] === $stu['jalur_masuk']) {
                        $matchedTarif = $tr;
                    }
                    if (!$tr['kelas_id'] && !$tr['jenjang_id'] && !$tr['jalur_masuk']) {
                        // General fallback
                        if (!$matchedTarif) {
                            $matchedTarif = $tr;
                        }
                    }
                }

                if (!$matchedTarif) {
                    continue; // Lewati jika tidak ada tarif default terdefinisi
                }

                // Cek duplikasi
                $stmtCheckDup->execute([$tenantId, $stu['id'], $komponenId, $tahunAjaranId, $bulan, $bulan]);
                if ($stmtCheckDup->fetchColumn()) {
                    continue; // Skip jika sudah digenerate sebelumnya
                }

                $nominalTagihan = (float)$matchedTarif['nominal'];

                // Hitung diskon/keringanan siswa
                $stmtKeringanan = $db->prepare("
                    SELECT tipe_keringanan, nilai 
                    FROM transaksi_spp_keringanan 
                    WHERE siswa_id = ? AND komponen_id = ? AND tenant_id = ? LIMIT 1
                ");
                $stmtKeringanan->execute([$stu['id'], $komponenId, $tenantId]);
                $discount = $stmtKeringanan->fetch(PDO::FETCH_ASSOC);

                if ($discount) {
                    if ($discount['tipe_keringanan'] === 'Nominal') {
                        $nominalTagihan = max(0.00, $nominalTagihan - (float)$discount['nilai']);
                    } elseif ($discount['tipe_keringanan'] === 'Persentase') {
                        $nominalTagihan = max(0.00, $nominalTagihan * (1 - ((float)$discount['nilai'] / 100)));
                    }
                }

                $uuid = bin2hex(random_bytes(16));
                $uuidFormatted = sprintf('%08s-%04s-%04s-%04s-%12s',
                    substr($uuid, 0, 8),
                    substr($uuid, 8, 4),
                    substr($uuid, 12, 4),
                    substr($uuid, 16, 4),
                    substr($uuid, 20, 12)
                );

                $stmtInsert->execute([
                    $uuidFormatted,
                    $tenantId,
                    $stu['id'],
                    $komponenId,
                    $matchedTarif['id'],
                    $tahunAjaranId,
                    $bulan,
                    $nominalTagihan
                ]);

                $generatedCount++;
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'count' => $generatedCount]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Preview Target Siswa & Nominal sebelum Generate Tagihan
    public function apiPreviewGenerate(): void {
        $tenantId = $this->resolveTenantId();
        $raw = array_merge($_GET, json_decode(file_get_contents('php://input'), true) ?? []);
        
        $komponenId = (int)($raw['komponen_id'] ?? $_GET['komponen_id'] ?? 0);
        $tahunAjaranId = (int)($raw['tahun_ajaran_id'] ?? $_GET['tahun_ajaran_id'] ?? 0);
        $bulan = !empty($raw['bulan']) ? (int)$raw['bulan'] : (!empty($_GET['bulan']) ? (int)$_GET['bulan'] : null);
        $kelasId = !empty($raw['kelas_id']) ? (int)$raw['kelas_id'] : (!empty($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : null);
        $jenjangId = !empty($raw['jenjang_id']) ? (int)$raw['jenjang_id'] : (!empty($_GET['jenjang_id']) ? (int)$_GET['jenjang_id'] : null);

        if (!$komponenId || !$tahunAjaranId) {
            $this->jsonResponse(['success' => false, 'error' => 'Komponen dan tahun ajaran wajib diisi.'], 422);
        }

        $db = Database::getConnection();

        try {
            // Ambil data komponen biaya
            $stmtComp = $db->prepare("SELECT tipe_periode FROM transaksi_spp_komponen WHERE id = ? AND tenant_id = ?");
            $stmtComp->execute([$komponenId, $tenantId]);
            $tipePeriode = $stmtComp->fetchColumn();
            if (!$tipePeriode) {
                $this->jsonResponse(['success' => false, 'error' => 'Komponen biaya tidak valid.'], 422);
            }

            $siswaIds = !empty($raw['siswa_ids']) && is_array($raw['siswa_ids']) ? $raw['siswa_ids'] : [];

            // Ambil daftar siswa aktif untuk target filter
            $sqlSiswa = "SELECT s.id, s.nama_lengkap as nama, s.nisn, k.nama_kelas, s.id_kelas, r.jalur_diterima as jalur_masuk 
                         FROM siswa s 
                         JOIN kelas k ON s.id_kelas = k.id
                         LEFT JOIN registrasi r ON s.id = r.id_siswa 
                         WHERE s.tenant_id = ? AND s.status = 'Aktif' AND s.deleted_at IS NULL";
            $paramsSiswa = [$tenantId];

            if ($kelasId) {
                $sqlSiswa .= " AND s.id_kelas = ?";
                $paramsSiswa[] = $kelasId;
            } elseif ($jenjangId) {
                $sqlSiswa .= " AND s.id_kelas IN (SELECT id FROM kelas WHERE id_jenjang = ?)";
                $paramsSiswa[] = $jenjangId;
            }

            if (!empty($siswaIds)) {
                $placeholders = implode(',', array_fill(0, count($siswaIds), '?'));
                $sqlSiswa .= " AND s.id IN ($placeholders)";
                foreach ($siswaIds as $sid) {
                    $paramsSiswa[] = $sid;
                }
            }
            $sqlSiswa .= " ORDER BY s.nama_lengkap ASC";

            $stmtSiswa = $db->prepare($sqlSiswa);
            $stmtSiswa->execute($paramsSiswa);
            $students = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

            // Ambil tarif default
            $stmtTarif = $db->prepare("
                SELECT id, nominal, kelas_id, jenjang_id, jalur_masuk 
                FROM transaksi_spp_tarif 
                WHERE komponen_id = ? AND tahun_ajaran_id = ? AND tenant_id = ?
            ");
            $stmtTarif->execute([$komponenId, $tahunAjaranId, $tenantId]);
            $tarifs = $stmtTarif->fetchAll(PDO::FETCH_ASSOC);

            // Statement checking duplicate
            $stmtCheckDup = $db->prepare("
                SELECT id FROM transaksi_spp_tagihan 
                WHERE tenant_id = ? AND siswa_id = ? AND komponen_id = ? AND tahun_ajaran_id = ? 
                  AND (bulan = ? OR (bulan IS NULL AND ? IS NULL))
            ");

            // Statement diskon
            $stmtKeringanan = $db->prepare("
                SELECT tipe_keringanan, nilai 
                FROM transaksi_spp_keringanan 
                WHERE siswa_id = ? AND komponen_id = ? AND tenant_id = ? LIMIT 1
            ");

            $preview = [];

            foreach ($students as $stu) {
                $matchedTarif = null;
                foreach ($tarifs as $tr) {
                    if ($tr['kelas_id'] == $stu['id_kelas']) {
                        $matchedTarif = $tr;
                        break;
                    }
                    if ($tr['jenjang_id'] && !$tr['kelas_id']) {
                        $stmtCheckJenjang = $db->prepare("SELECT id FROM kelas WHERE id = ? AND id_jenjang = ? LIMIT 1");
                        $stmtCheckJenjang->execute([$stu['id_kelas'], $tr['jenjang_id']]);
                        if ($stmtCheckJenjang->fetchColumn()) {
                            $matchedTarif = $tr;
                        }
                    }
                    if ($tr['jalur_masuk'] && $tr['jalur_masuk'] === $stu['jalur_masuk']) {
                        $matchedTarif = $tr;
                    }
                    if (!$tr['kelas_id'] && !$tr['jenjang_id'] && !$tr['jalur_masuk']) {
                        if (!$matchedTarif) {
                            $matchedTarif = $tr;
                        }
                    }
                }

                if (!$matchedTarif) {
                    continue; // lewati jika tidak ada tarif default
                }

                // Cek duplikasi
                $stmtCheckDup->execute([$tenantId, $stu['id'], $komponenId, $tahunAjaranId, $bulan, $bulan]);
                $sudahAda = (bool)$stmtCheckDup->fetchColumn();

                $nominalAsli = (float)$matchedTarif['nominal'];
                $potongan = 0.00;

                // Hitung diskon/keringanan
                $stmtKeringanan->execute([$stu['id'], $komponenId, $tenantId]);
                $discount = $stmtKeringanan->fetch(PDO::FETCH_ASSOC);
                if ($discount) {
                    if ($discount['tipe_keringanan'] === 'Nominal') {
                        $potongan = (float)$discount['nilai'];
                    } elseif ($discount['tipe_keringanan'] === 'Persentase') {
                        $potongan = $nominalAsli * ((float)$discount['nilai'] / 100);
                    }
                }

                $nominalAkhir = max(0.00, $nominalAsli - $potongan);

                $preview[] = [
                    'id' => $stu['id'],
                    'nama' => $stu['nama'],
                    'nisn' => $stu['nisn'],
                    'nama_kelas' => $stu['nama_kelas'],
                    'nominal_asli' => $nominalAsli,
                    'potongan' => $potongan,
                    'nominal_akhir' => $nominalAkhir,
                    'sudah_ada' => $sudahAda
                ];
            }

            $this->jsonResponse(['success' => true, 'data' => $preview]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Dapatkan daftar Tahun Ajaran disaring berdasarkan Tenant
    public function apiTahunAjaran(): void {
        $tenantId = $this->resolveTenantId();
        $db = Database::getConnection();
        
        try {
            $role = $_SESSION['role_name'] ?? '';
            if ($role === 'super_admin' && empty($_GET['tenant_id'])) {
                // Untuk Super Admin view global (tanpa tenant terpilih), group by nama tahun ajaran agar unik
                $stmt = $db->query("
                    SELECT MIN(id) as id, tahun_ajaran, MAX(is_active) as is_active 
                    FROM tahun_ajaran 
                    WHERE deleted_at IS NULL 
                    GROUP BY tahun_ajaran 
                    ORDER BY tahun_ajaran DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Saring tepat sesuai tenant_id yang aktif
                $stmt = $db->prepare("
                    SELECT id, tahun_ajaran, is_active 
                    FROM tahun_ajaran 
                    WHERE tenant_id = ? AND deleted_at IS NULL 
                    ORDER BY tahun_ajaran DESC
                ");
                $stmt->execute([$tenantId]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Format status agar kompatibel dengan data-ta sebelumnya
            foreach ($data as &$d) {
                $d['status'] = $d['is_active'] == 1 ? 'Aktif' : 'Non-Aktif';
            }
            
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Dapatkan daftar Kelas disaring berdasarkan Tenant & Jenjang
    public function apiKelas(): void {
        $tenantId = $this->resolveTenantId();
        $jenjangId = !empty($_GET['jenjang_id']) ? (int)$_GET['jenjang_id'] : null;
        $db = Database::getConnection();
        
        try {
            $role = $_SESSION['role_name'] ?? '';
            if ($role === 'super_admin' && empty($_GET['tenant_id'])) {
                // Mode global Super Admin: kembalikan semua kelas aktif
                $sql = "SELECT id, nama_kelas FROM kelas WHERE deleted_at IS NULL";
                $params = [];
                if ($jenjangId) {
                    $sql .= " AND id_jenjang = ?";
                    $params[] = $jenjangId;
                }
                $sql .= " ORDER BY nama_kelas ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Saring tepat sesuai tenant_id yang aktif
                $sql = "SELECT id, nama_kelas FROM kelas WHERE tenant_id = ? AND deleted_at IS NULL";
                $params = [$tenantId];
                if ($jenjangId) {
                    $sql .= " AND id_jenjang = ?";
                    $params[] = $jenjangId;
                }
                $sql .= " ORDER BY nama_kelas ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Dapatkan daftar Jenjang disaring berdasarkan Tenant
    public function apiJenjang(): void {
        $tenantId = $this->resolveTenantId();
        $db = Database::getConnection();
        
        try {
            $role = $_SESSION['role_name'] ?? '';
            if ($role === 'super_admin' && empty($_GET['tenant_id'])) {
                // Mode global Super Admin: kembalikan semua jenjang aktif
                $stmt = $db->query("SELECT id, nama_jenjang FROM jenjang WHERE deleted_at IS NULL ORDER BY nama_jenjang ASC");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Saring tepat sesuai tenant_id yang aktif
                $stmt = $db->prepare("SELECT id, nama_jenjang FROM jenjang WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY nama_jenjang ASC");
                $stmt->execute([$tenantId]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Daftar Tagihan Siswa Terbit (filtered & paginated)
    public function apiDaftarTagihan(): void {
        $tenantId = $this->resolveTenantId();
        $db = Database::getConnection();
        
        $search = trim($_GET['q'] ?? '');
        $kelasId = !empty($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : null;
        $taId = !empty($_GET['tahun_ajaran_id']) ? (int)$_GET['tahun_ajaran_id'] : null;
        $komponenId = !empty($_GET['komponen_id']) ? (int)$_GET['komponen_id'] : null;
        $statusLunas = !empty($_GET['status_lunas']) ? trim($_GET['status_lunas']) : '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = max(1, (int)($_GET['page_size'] ?? 10));
        $offset = ($page - 1) * $pageSize;

        try {
            $role = $_SESSION['role_name'] ?? '';
            
            $sqlWhere = "WHERE 1=1";
            $params = [];

            if ($role === 'super_admin' && empty($_GET['tenant_id'])) {
                // No tenant constraint
            } else {
                $sqlWhere .= " AND t.tenant_id = ?";
                $params[] = $tenantId;
            }

            if (!empty($search)) {
                $sqlWhere .= " AND (s.nama_lengkap LIKE ? OR s.nisn LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if ($kelasId) {
                $sqlWhere .= " AND s.id_kelas = ?";
                $params[] = $kelasId;
            }

            if ($taId) {
                $sqlWhere .= " AND t.tahun_ajaran_id = ?";
                $params[] = $taId;
            }

            if ($komponenId) {
                $sqlWhere .= " AND t.komponen_id = ?";
                $params[] = $komponenId;
            }

            if (!empty($statusLunas)) {
                $sqlWhere .= " AND t.status_lunas = ?";
                $params[] = $statusLunas;
            }

            // Get total count
            $sqlCount = "SELECT COUNT(*) 
                         FROM transaksi_spp_tagihan t
                         JOIN siswa s ON t.siswa_id = s.id
                         $sqlWhere";
            $stmtCount = $db->prepare($sqlCount);
            $stmtCount->execute($params);
            $totalRows = (int)$stmtCount->fetchColumn();

            // Get paginated data
            $sqlSelect = "
                SELECT t.*, s.nama_lengkap as nama_siswa, s.nisn, k.nama_kelas, komp.nama_komponen, komp.tipe_periode, ta.tahun_ajaran, tn.nama_sekolah
                FROM transaksi_spp_tagihan t
                JOIN siswa s ON t.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN transaksi_spp_komponen komp ON t.komponen_id = komp.id
                LEFT JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
                LEFT JOIN tenants tn ON t.tenant_id = tn.id
                $sqlWhere
                ORDER BY t.created_at DESC
                LIMIT $pageSize OFFSET $offset
            ";
            $stmtSelect = $db->prepare($sqlSelect);
            $stmtSelect->execute($params);
            $data = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

            $totalPages = ceil($totalRows / $pageSize) ?: 1;

            $this->jsonResponse([
                'success' => true,
                'data' => $data,
                'total_rows' => $totalRows,
                'page' => $page,
                'total_pages' => $totalPages
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Ekspor data tagihan SPP ke Excel (.xlsx) dengan Pivot dinamis
    public function apiExportTagihanExcel(): void {
        $tenantId = $this->resolveTenantId();
        $db = Database::getConnection();

        $q = $_GET['q'] ?? '';
        $kelasId = $_GET['kelas_id'] ?? '';
        $taId = $_GET['tahun_ajaran_id'] ?? '';
        $komponenId = $_GET['komponen_id'] ?? '';
        $statusLunas = $_GET['status_lunas'] ?? '';

        // Wajib minimal 1 filter (di luar tenant)
        if (empty($q) && empty($kelasId) && empty($taId) && empty($komponenId) && empty($statusLunas)) {
            echo "Galat: Anda wajib memilih minimal satu filter sebelum mengunduh laporan Excel.";
            exit();
        }

        try {
            // Build query
            $sqlWhere = " WHERE t.tenant_id = ? AND s.deleted_at IS NULL AND s.status = 'Aktif'";
            $params = [$tenantId];

            if (!empty($q)) {
                $sqlWhere .= " AND (s.nama_lengkap LIKE ? OR s.nisn LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }

            if ($kelasId) {
                $sqlWhere .= " AND s.id_kelas = ?";
                $params[] = $kelasId;
            }

            if ($taId) {
                $sqlWhere .= " AND t.tahun_ajaran_id = ?";
                $params[] = $taId;
            }

            if ($komponenId) {
                $sqlWhere .= " AND t.komponen_id = ?";
                $params[] = $komponenId;
            }

            if (!empty($statusLunas)) {
                $sqlWhere .= " AND t.status_lunas = ?";
                $params[] = $statusLunas;
            }

            $sqlSelect = "
                SELECT 
                    t.siswa_id,
                    s.nama_lengkap as nama_siswa,
                    s.nisn,
                    k.nama_kelas,
                    t.nominal_tagihan,
                    t.nominal_bayar,
                    t.status_lunas,
                    t.tahun_ajaran_id,
                    ta.tahun_ajaran,
                    t.komponen_id,
                    komp.nama_komponen
                FROM transaksi_spp_tagihan t
                JOIN siswa s ON t.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN transaksi_spp_komponen komp ON t.komponen_id = komp.id
                LEFT JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
                $sqlWhere
                ORDER BY s.nama_lengkap ASC
            ";

            $stmt = $db->prepare($sqlSelect);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($records)) {
                echo "Galat: Tidak ada data tagihan yang ditemukan untuk kriteria filter tersebut.";
                exit();
            }

            // Ekstrak tahun ajaran dan komponen unik
            $ta_list = []; // $ta => [ $komponen_name => [ 'komponen_id' => ..., 'tahun_ajaran_id' => ... ] ]
            $students = []; // $siswa_id => [ 'nama' => ..., 'nisn' => ..., 'kelas' => ..., 'tagihan' => [ "$ta_id-$komp_id" => ... ] ]

            foreach ($records as $r) {
                $ta = $r['tahun_ajaran'];
                $taId = $r['tahun_ajaran_id'];
                $komp = $r['nama_komponen'];
                $kompId = $r['komponen_id'];

                if (!isset($ta_list[$ta])) {
                    $ta_list[$ta] = [];
                }
                if (!isset($ta_list[$ta][$komp])) {
                    $ta_list[$ta][$komp] = [
                        'komponen_id' => $kompId,
                        'tahun_ajaran_id' => $taId
                    ];
                }

                $sId = $r['siswa_id'];
                if (!isset($students[$sId])) {
                    $students[$sId] = [
                        'nama' => $r['nama_siswa'],
                        'nisn' => $r['nisn'],
                        'kelas' => $r['nama_kelas'] ?? '-',
                        'tagihan' => []
                    ];
                }

                $students[$sId]['tagihan']["{$taId}-{$kompId}"] = [
                    'nominal' => (float)$r['nominal_tagihan'],
                    'bayar' => (float)$r['nominal_bayar'],
                    'kurang' => (float)($r['nominal_tagihan'] - $r['nominal_bayar']),
                    'status' => $r['status_lunas']
                ];
            }

            // Urutkan tahun ajaran menurun, komponen naik
            krsort($ta_list);
            foreach ($ta_list as $ta => &$components) {
                ksort($components);
            }
            unset($components);

            // Bikin baris header
            $row1 = ['nama', 'nisn', 'kelas'];
            $row2 = ['', '', ''];
            $row3 = ['', '', ''];

            $merges = ['A1:A3', 'B1:B3', 'C1:C3'];
            $currentColIndex = 3;

            // Helper mengubah nomor kolom ke Huruf Excel (0 = A, 25 = Z, 26 = AA)
            $getColLetter = function ($num) use (&$getColLetter) {
                $numeric = $num % 26;
                $letter = chr(65 + $numeric);
                $num2 = intval($num / 26);
                if ($num2 > 0) {
                    return $getColLetter($num2 - 1) . $letter;
                }
                return $letter;
            };

            foreach ($ta_list as $ta => $components) {
                $taStartCol = $currentColIndex;
                $taColCount = count($components) * 4;

                $row1[] = '<style bgcolor="#E2EFDA"><b>' . $ta . '</b></style>';
                for ($i = 1; $i < $taColCount; $i++) {
                    $row1[] = '';
                }

                $taStartLetter = $getColLetter($taStartCol);
                $taEndLetter = $getColLetter($taStartCol + $taColCount - 1);
                $merges[] = $taStartLetter . '1:' . $taEndLetter . '1';

                foreach ($components as $kompName => $info) {
                    $kompStartCol = $currentColIndex;

                    $row2[] = '<style bgcolor="#F2F2F2"><b>' . $kompName . '</b></style>';
                    for ($i = 1; $i < 4; $i++) {
                        $row2[] = '';
                    }

                    $kompStartLetter = $getColLetter($kompStartCol);
                    $kompEndLetter = $getColLetter($kompStartCol + 3);
                    $merges[] = $kompStartLetter . '2:' . $kompEndLetter . '2';

                    $row3[] = '<style bgcolor="#FFF2CC"><b>nominal</b></style>';
                    $row3[] = '<style bgcolor="#FFF2CC"><b>telah di bayar</b></style>';
                    $row3[] = '<style bgcolor="#FFF2CC"><b>Kurang bayar</b></style>';
                    $row3[] = '<style bgcolor="#FFF2CC"><b>status</b></style>';

                    $currentColIndex += 4;
                }
            }

            $xlsx_data = [$row1, $row2, $row3];

            // Masukkan data siswa
            foreach ($students as $s) {
                $rowData = [
                    $s['nama'],
                    $s['nisn'],
                    $s['kelas']
                ];

                foreach ($ta_list as $ta => $components) {
                    foreach ($components as $kompName => $info) {
                        $key = "{$info['tahun_ajaran_id']}-{$info['komponen_id']}";
                        if (isset($s['tagihan'][$key])) {
                            $t = $s['tagihan'][$key];
                            $rowData[] = $t['nominal'];
                            $rowData[] = $t['bayar'];
                            $rowData[] = $t['kurang'];
                            $rowData[] = $t['status'];
                        } else {
                            $rowData[] = '';
                            $rowData[] = '';
                            $rowData[] = '';
                            $rowData[] = '';
                        }
                    }
                }
                $xlsx_data[] = $rowData;
            }

            // Generate dengan SimpleXLSXGen
            $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($xlsx_data);
            foreach ($merges as $m) {
                $xlsx->mergeCells($m);
            }

            $xlsx->downloadAs('laporan_tagihan_spp_' . date('Ymd_His') . '.xlsx');
            exit();

        } catch (\Throwable $e) {
            echo "Galat ekspor: " . $e->getMessage();
            exit();
        }
    }

    // API: Ubah Nominal Tagihan Aktif
    public function apiEditTagihanNominal(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
        }

        $tenantId = $this->resolveTenantId();
        $db = Database::getConnection();
        $raw = json_decode(file_get_contents('php://input'), true);

        $id = trim($raw['id'] ?? '');
        $newNominal = (float)($raw['nominal_tagihan'] ?? 0.00);

        if (empty($id) || $newNominal < 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Data tidak lengkap atau nominal tidak valid.'], 422);
        }

        try {
            $db->beginTransaction();

            $stmtTagihan = $db->prepare("SELECT nominal_tagihan, nominal_bayar FROM transaksi_spp_tagihan WHERE id = ? AND tenant_id = ? FOR UPDATE");
            $stmtTagihan->execute([$id, $tenantId]);
            $tagihan = $stmtTagihan->fetch(PDO::FETCH_ASSOC);

            if (!$tagihan) {
                throw new \Exception("Tagihan tidak ditemukan.");
            }

            $nominalBayar = (float)$tagihan['nominal_bayar'];

            $statusLunas = 'Belum';
            if ($nominalBayar >= $newNominal) {
                $statusLunas = 'Lunas';
            } elseif ($nominalBayar > 0) {
                $statusLunas = 'Cicil';
            }

            $stmtUpdate = $db->prepare("UPDATE transaksi_spp_tagihan SET nominal_tagihan = ?, status_lunas = ? WHERE id = ? AND tenant_id = ?");
            $stmtUpdate->execute([$newNominal, $statusLunas, $id, $tenantId]);

            $stmtAudit = $db->prepare("
                INSERT INTO transaksi_spp_audit_log (tenant_id, user_id, aksi, tabel_target, target_id, data_sebelum, data_sesudah, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtAudit->execute([
                $tenantId,
                $_SESSION['user_id'] ?? '',
                'UPDATE_NOMINAL',
                'transaksi_spp_tagihan',
                $id,
                json_encode(['nominal_tagihan' => $tagihan['nominal_tagihan']]),
                json_encode(['nominal_tagihan' => $newNominal, 'status_lunas' => $statusLunas]),
                "Mengubah nominal tagihan menjadi Rp " . number_format($newNominal)
            ]);

            $db->commit();
            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Hapus Tagihan Aktif (aman jika belum dibayar)
    public function apiHapusTagihan(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
        }

        $tenantId = $this->resolveTenantId();
        $db = Database::getConnection();
        $id = trim($_GET['id'] ?? '');

        if (empty($id)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID tagihan wajib diisi.'], 422);
        }

        try {
            $db->beginTransaction();

            $stmtTagihan = $db->prepare("SELECT nominal_bayar, nominal_tagihan FROM transaksi_spp_tagihan WHERE id = ? AND tenant_id = ? FOR UPDATE");
            $stmtTagihan->execute([$id, $tenantId]);
            $tagihan = $stmtTagihan->fetch(PDO::FETCH_ASSOC);

            if (!$tagihan) {
                throw new \Exception("Tagihan tidak ditemukan.");
            }

            if ((float)$tagihan['nominal_bayar'] > 0) {
                $this->jsonResponse(['success' => false, 'error' => 'Tagihan sudah dicicil/dibayar. Harap batalkan pembayaran terlebih dahulu.'], 422);
                return;
            }

            $stmtDelete = $db->prepare("DELETE FROM transaksi_spp_tagihan WHERE id = ? AND tenant_id = ?");
            $stmtDelete->execute([$id, $tenantId]);

            $stmtAudit = $db->prepare("
                INSERT INTO transaksi_spp_audit_log (tenant_id, user_id, aksi, tabel_target, target_id, data_sebelum, data_sesudah, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtAudit->execute([
                $tenantId,
                $_SESSION['user_id'] ?? '',
                'DELETE_TAGIHAN',
                'transaksi_spp_tagihan',
                $id,
                json_encode($tagihan),
                null,
                "Menghapus tagihan belum terbayar."
            ]);

            $db->commit();
            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Cari Siswa Dinamis
    public function apiCariSiswa(): void {
        $tenantId = $this->resolveTenantId();
        $query = trim($_GET['q'] ?? '');
        $kelasId = !empty($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : null;
        $jenjangId = !empty($_GET['jenjang_id']) ? (int)$_GET['jenjang_id'] : null;

        if (empty($query) && empty($kelasId) && empty($jenjangId)) {
            $this->jsonResponse(['success' => true, 'data' => []]);
        }

        $db = Database::getConnection();
        
        $sql = "
            SELECT s.id, s.nama_lengkap as nama, s.nisn, k.nama_kelas
            FROM siswa s
            JOIN kelas k ON s.id_kelas = k.id
            WHERE s.tenant_id = ? AND s.status = 'Aktif' AND s.deleted_at IS NULL
        ";
        $params = [$tenantId];

        if (!empty($query)) {
            $sql .= " AND (s.nama_lengkap LIKE ? OR s.nisn LIKE ?)";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }

        if ($kelasId) {
            $sql .= " AND s.id_kelas = ?";
            $params[] = $kelasId;
        }

        if ($jenjangId) {
            $sql .= " AND k.id_jenjang = ?";
            $params[] = $jenjangId;
        }

        $sql .= " ORDER BY s.nama_lengkap ASC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // API: Get Tagihan Siswa Terpilih
    public function apiGetTagihanSiswa(): void {
        $tenantId = $this->resolveTenantId();
        $siswaId = trim($_GET['siswa_id'] ?? '');

        if (empty($siswaId)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID Siswa tidak boleh kosong.'], 422);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT t.*, k.nama_komponen, k.tipe_periode, ta.tahun_ajaran,
                   COALESCE(
                       (SELECT nama_kelas_tujuan FROM riwayat_kenaikan_kelas WHERE siswa_id = t.siswa_id AND tahun_ajaran = ta.tahun_ajaran LIMIT 1),
                       (SELECT nama_kelas_asal FROM riwayat_kenaikan_kelas WHERE siswa_id = t.siswa_id AND tahun_ajaran > ta.tahun_ajaran ORDER BY tahun_ajaran ASC LIMIT 1),
                       (SELECT name_k.nama_kelas FROM siswa name_s JOIN kelas name_k ON name_s.id_kelas = name_k.id WHERE name_s.id = t.siswa_id LIMIT 1),
                       '-'
                   ) as nama_kelas_history,
                   (SELECT p.nomor_kwitansi FROM transaksi_spp_pembayaran p WHERE p.tagihan_id = t.id ORDER BY p.tanggal_bayar DESC LIMIT 1) as latest_nomor_kwitansi,
                   (SELECT p.tanggal_bayar FROM transaksi_spp_pembayaran p WHERE p.tagihan_id = t.id ORDER BY p.tanggal_bayar DESC LIMIT 1) as latest_tgl_bayar,
                   (SELECT p.metode_pembayaran FROM transaksi_spp_pembayaran p WHERE p.tagihan_id = t.id ORDER BY p.tanggal_bayar DESC LIMIT 1) as latest_metode
            FROM transaksi_spp_tagihan t
            JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
            JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
            WHERE t.siswa_id = ? AND t.tenant_id = ?
            ORDER BY ta.tahun_ajaran DESC, k.nama_komponen ASC, CAST(t.bulan AS UNSIGNED) ASC
        ");
        $stmt->execute([$siswaId, $tenantId]);
        $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // API: Checkout Pembayaran Kasir (Ledger POS)
    public function apiBayarTagihan(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
        }

        $tenantId = $this->resolveTenantId();
        $kasirId = $_SESSION['user_id'] ?? '';
        $raw = json_decode(file_get_contents('php://input'), true);

        $siswaId = trim($raw['siswa_id'] ?? '');
        $items = $raw['items'] ?? []; // Array of { tagihan_id, nominal_dibayar }
        $metode = trim($raw['metode_pembayaran'] ?? 'Tunai');
        $keterangan = trim($raw['keterangan'] ?? '');

        if (empty($siswaId) || empty($items)) {
            $this->jsonResponse(['success' => false, 'error' => 'Data pembayaran tidak lengkap.'], 422);
        }

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            $pembayaranIds = [];
            $kwitansiBase = 'KW/' . date('Ymd') . '/' . strtoupper(bin2hex(random_bytes(3)));
            
            // Hitung valid items
            $validItems = array_filter($items, function($it) {
                return !empty($it['tagihan_id']) && (float)($it['nominal_dibayar'] ?? 0) > 0;
            });
            $itemCount = count($validItems);
            $itemIndex = 0;

            // Loop dan simpan pembayaran individual
            $stmtTagihan = $db->prepare("SELECT nominal_tagihan, nominal_bayar FROM transaksi_spp_tagihan WHERE id = ? AND tenant_id = ? FOR UPDATE");
            $stmtUpdateTagihan = $db->prepare("UPDATE transaksi_spp_tagihan SET nominal_bayar = ?, status_lunas = ? WHERE id = ? AND tenant_id = ?");
            $stmtInsertPay = $db->prepare("
                INSERT INTO transaksi_spp_pembayaran (id, tenant_id, tagihan_id, siswa_id, nominal_dibayar, metode_pembayaran, kasir_id, nomor_kwitansi, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtAudit = $db->prepare("
                INSERT INTO transaksi_spp_audit_log (tenant_id, user_id, aksi, tabel_target, target_id, data_sebelum, data_sesudah, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($items as $item) {
                $tagihanId = trim($item['tagihan_id'] ?? '');
                $nominalDibayar = (float)($item['nominal_dibayar'] ?? 0.00);

                if (empty($tagihanId) || $nominalDibayar <= 0) {
                    continue;
                }

                $itemIndex++;
                $kwitansiNo = ($itemCount > 1) ? "{$kwitansiBase}-{$itemIndex}" : $kwitansiBase;

                // Ambil tagihan saat ini
                $stmtTagihan->execute([$tagihanId, $tenantId]);
                $tagihan = $stmtTagihan->fetch(PDO::FETCH_ASSOC);
                if (!$tagihan) {
                    throw new \Exception("Tagihan ID {$tagihanId} tidak ditemukan.");
                }

                $nominalTagihan = (float)$tagihan['nominal_tagihan'];
                $oldNominalBayar = (float)$tagihan['nominal_bayar'];
                $newNominalBayar = $oldNominalBayar + $nominalDibayar;

                // Tentukan status lunas
                $statusLunas = 'Cicil';
                if ($newNominalBayar >= $nominalTagihan) {
                    $statusLunas = 'Lunas';
                } elseif ($newNominalBayar <= 0) {
                    $statusLunas = 'Belum';
                }

                // Simpan transaksi pembayaran
                $uuid = bin2hex(random_bytes(16));
                $uuidFormatted = sprintf('%08s-%04s-%04s-%04s-%12s',
                    substr($uuid, 0, 8),
                    substr($uuid, 8, 4),
                    substr($uuid, 12, 4),
                    substr($uuid, 16, 4),
                    substr($uuid, 20, 12)
                );

                $stmtInsertPay->execute([
                    $uuidFormatted,
                    $tenantId,
                    $tagihanId,
                    $siswaId,
                    $nominalDibayar,
                    $metode,
                    $kasirId,
                    $kwitansiNo,
                    $keterangan
                ]);

                // Update Tagihan
                $stmtUpdateTagihan->execute([$newNominalBayar, $statusLunas, $tagihanId, $tenantId]);

                // Simpan Audit Log
                $stmtAudit->execute([
                    $tenantId,
                    $kasirId,
                    'PAYMENT_SPP',
                    'transaksi_spp_pembayaran',
                    $uuidFormatted,
                    json_encode($tagihan),
                    json_encode(['nominal_bayar' => $newNominalBayar, 'status_lunas' => $statusLunas]),
                    "Pembayaran kasir SPP sebesar Rp " . number_format($nominalDibayar, 0, ',', '.')
                ]);

                $pembayaranIds[] = $uuidFormatted;
            }

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'nomor_kwitansi' => $kwitansiBase,
                'pembayaran_ids' => $pembayaranIds
            ]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Pembatalan / Void Transaksi Pembayaran Kasir
    public function apiBatalPembayaran(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
        }

        $tenantId = $this->resolveTenantId();
        $userId = $_SESSION['user_id'] ?? '';
        $raw = json_decode(file_get_contents('php://input'), true);

        $tagihanId = trim($raw['tagihan_id'] ?? '');
        $alasanBatal = trim($raw['alasan_batal'] ?? '');

        if (empty($tagihanId)) {
            $this->jsonResponse(['success' => false, 'error' => 'ID Tagihan tidak boleh kosong.'], 422);
        }
        if (empty($alasanBatal)) {
            $this->jsonResponse(['success' => false, 'error' => 'Alasan pembatalan wajib diisi.'], 422);
        }

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            // 1. Dapatkan riwayat pembayaran terbaru untuk tagihan ini
            $stmtPay = $db->prepare("
                SELECT * FROM transaksi_spp_pembayaran 
                WHERE tagihan_id = ? AND tenant_id = ? 
                ORDER BY tanggal_bayar DESC LIMIT 1
            ");
            $stmtPay->execute([$tagihanId, $tenantId]);
            $pembayaran = $stmtPay->fetch(PDO::FETCH_ASSOC);

            if (!$pembayaran) {
                throw new \Exception("Riwayat transaksi pembayaran tidak ditemukan untuk tagihan ini.");
            }

            $payId = $pembayaran['id'];
            $nominalDibayar = (float)$pembayaran['nominal_dibayar'];
            $kwitansiNo = $pembayaran['nomor_kwitansi'];

            // 2. Ambil tagihan saat ini & Kunci baris
            $stmtTagihan = $db->prepare("SELECT * FROM transaksi_spp_tagihan WHERE id = ? AND tenant_id = ? FOR UPDATE");
            $stmtTagihan->execute([$tagihanId, $tenantId]);
            $tagihan = $stmtTagihan->fetch(PDO::FETCH_ASSOC);

            if (!$tagihan) {
                throw new \Exception("Data tagihan tidak ditemukan.");
            }

            $nominalTagihan = (float)$tagihan['nominal_tagihan'];
            $oldNominalBayar = (float)$tagihan['nominal_bayar'];
            $newNominalBayar = max(0.00, $oldNominalBayar - $nominalDibayar);

            // Tentukan status pelunasan baru
            $newStatusLunas = 'Cicil';
            if ($newNominalBayar <= 0) {
                $newStatusLunas = 'Belum';
            } elseif ($newNominalBayar >= $nominalTagihan) {
                $newStatusLunas = 'Lunas';
            }

            // 3. Update tagihan
            $stmtUpdate = $db->prepare("UPDATE transaksi_spp_tagihan SET nominal_bayar = ?, status_lunas = ? WHERE id = ? AND tenant_id = ?");
            $stmtUpdate->execute([$newNominalBayar, $newStatusLunas, $tagihanId, $tenantId]);

            // 4. Hapus record transaksi pembayaran dari transaksi_spp_pembayaran
            $stmtDeletePay = $db->prepare("DELETE FROM transaksi_spp_pembayaran WHERE id = ? AND tenant_id = ?");
            $stmtDeletePay->execute([$payId, $tenantId]);

            // 5. Simpan ke Audit Log
            $stmtAudit = $db->prepare("
                INSERT INTO transaksi_spp_audit_log (tenant_id, user_id, aksi, tabel_target, target_id, data_sebelum, data_sesudah, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtAudit->execute([
                $tenantId,
                $userId,
                'VOID_PAYMENT',
                'transaksi_spp_pembayaran',
                $payId,
                json_encode(['pembayaran' => $pembayaran, 'tagihan_sebelum' => $tagihan]),
                json_encode(['nominal_bayar' => $newNominalBayar, 'status_lunas' => $newStatusLunas, 'alasan_batal' => $alasanBatal]),
                "Pembatalan pembayaran kwitansi {$kwitansiNo} sebesar Rp " . number_format($nominalDibayar, 0, ',', '.') . ". Alasan: " . $alasanBatal
            ]);

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => "Pembayaran kwitansi {$kwitansiNo} sebesar Rp " . number_format($nominalDibayar, 0, ',', '.') . " berhasil dibatalkan."
            ]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Laporan Pemasukan & Tunggakan
    public function apiLaporanRekap(): void {
        $tenantId = $this->resolveTenantId();
        $db = Database::getConnection();
        $tipe = trim($_GET['tipe'] ?? 'pemasukan'); // pemasukan / tunggakan

        try {
            // Fetch Master Options for Filters
            $kelasList = $db->query("
                SELECT DISTINCT kl.id, kl.nama_kelas 
                FROM kelas kl 
                JOIN siswa s ON s.id_kelas = kl.id 
                WHERE kl.tenant_id = " . $db->quote($tenantId) . " 
                ORDER BY kl.nama_kelas ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $jenjangList = $db->query("
                SELECT DISTINCT j.id, j.nama_jenjang 
                FROM jenjang j 
                WHERE j.tenant_id = " . $db->quote($tenantId) . " 
                ORDER BY j.nama_jenjang ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $tahunAjaranList = $db->query("
                SELECT DISTINCT ta.id, ta.tahun_ajaran 
                FROM tahun_ajaran ta 
                WHERE ta.tenant_id = " . $db->quote($tenantId) . " 
                ORDER BY ta.tahun_ajaran DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $komponenList = $db->query("
                SELECT DISTINCT id, nama_komponen 
                FROM transaksi_spp_komponen 
                WHERE tenant_id = " . $db->quote($tenantId) . " 
                ORDER BY nama_komponen ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $options = [
                'kelas_list' => $kelasList,
                'jenjang_list' => $jenjangList,
                'tahun_ajaran_list' => $tahunAjaranList,
                'komponen_list' => $komponenList
            ];

            if ($tipe === 'pemasukan') {
                $stmt = $db->prepare("
                    SELECT 
                        p.*, 
                        s.nama_lengkap as nama_siswa, 
                        s.nisn, 
                        COALESCE(kl.nama_kelas, '-') as nama_kelas,
                        COALESCE(j.nama_jenjang, j2.nama_jenjang, '-') as nama_jenjang,
                        COALESCE(ta.tahun_ajaran, '-') as tahun_ajaran,
                        k.nama_komponen, 
                        u.nama_lengkap as nama_kasir
                    FROM transaksi_spp_pembayaran p
                    JOIN siswa s ON p.siswa_id = s.id
                    LEFT JOIN kelas kl ON s.id_kelas = kl.id
                    LEFT JOIN jenjang j ON s.id_jenjang = j.id
                    LEFT JOIN jenjang j2 ON kl.id_jenjang = j2.id
                    JOIN transaksi_spp_tagihan t ON p.tagihan_id = t.id
                    LEFT JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
                    JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
                    JOIN users u ON p.kasir_id = u.id
                    WHERE p.tenant_id = ?
                    ORDER BY p.tanggal_bayar DESC
                ");
                $stmt->execute([$tenantId]);
                $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'options' => $options]);
            } else {
                // Tunggakan
                $stmt = $db->prepare("
                    SELECT 
                        t.*, 
                        s.nama_lengkap as nama_siswa, 
                        s.nisn, 
                        COALESCE(kl.nama_kelas, '-') as nama_kelas,
                        COALESCE(j.nama_jenjang, j2.nama_jenjang, '-') as nama_jenjang,
                        k.nama_komponen, 
                        COALESCE(ta.tahun_ajaran, '-') as tahun_ajaran
                    FROM transaksi_spp_tagihan t
                    JOIN siswa s ON t.siswa_id = s.id
                    LEFT JOIN kelas kl ON s.id_kelas = kl.id
                    LEFT JOIN jenjang j ON s.id_jenjang = j.id
                    LEFT JOIN jenjang j2 ON kl.id_jenjang = j2.id
                    JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
                    JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
                    WHERE t.tenant_id = ? AND t.status_lunas != 'Lunas'
                    ORDER BY t.created_at DESC
                ");
                $stmt->execute([$tenantId]);
                $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'options' => $options]);
            }
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Settings Terminology & Visibility
    public function apiGetPengaturan(): void {
        $tenantId = $this->resolveTenantId();
        $db = Database::getConnection();

        try {
            $stmt = $db->prepare("SELECT * FROM transaksi_spp_pengaturan WHERE tenant_id = ?");
            $stmt->execute([$tenantId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                // Default fallback jika belum ada pengaturan tersimpan
                $settings = [
                    'tenant_id' => $tenantId,
                    'nama_modul' => 'Keuangan & SPP',
                    'istilah_tagihan' => 'Tagihan',
                    'istilah_tunggakan' => 'Tunggakan',
                    'visibilitas_siswa' => 1
                ];
            }

            $this->jsonResponse(['success' => true, 'data' => $settings]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiSavePengaturan(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
        }

        $tenantId = $this->resolveTenantId();
        $raw = json_decode(file_get_contents('php://input'), true);

        $namaModul = trim($raw['nama_modul'] ?? 'Keuangan & SPP');
        $istilahTagihan = trim($raw['istilah_tagihan'] ?? 'Tagihan');
        $istilahTunggakan = trim($raw['istilah_tunggakan'] ?? 'Tunggakan');
        $visibilitas = (int)($raw['visibilitas_siswa'] ?? 1);

        $db = Database::getConnection();

        try {
            $stmtCheck = $db->prepare("SELECT tenant_id FROM transaksi_spp_pengaturan WHERE tenant_id = ?");
            $stmtCheck->execute([$tenantId]);

            if ($stmtCheck->fetchColumn()) {
                $stmt = $db->prepare("
                    UPDATE transaksi_spp_pengaturan 
                    SET nama_modul = ?, istilah_tagihan = ?, istilah_tunggakan = ?, visibilitas_siswa = ? 
                    WHERE tenant_id = ?
                ");
                $stmt->execute([$namaModul, $istilahTagihan, $istilahTunggakan, $visibilitas, $tenantId]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO transaksi_spp_pengaturan (tenant_id, nama_modul, istilah_tagihan, istilah_tunggakan, visibilitas_siswa)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$tenantId, $namaModul, $istilahTagihan, $istilahTunggakan, $visibilitas]);
            }

            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API: Tagihan Saya (Dashboard Siswa)
    public function apiGetTagihanSaya(): void {
        $tenantId = $this->resolveTenantId();
        $siswaId = $_SESSION['user_id'] ?? ''; // Jika siswa login, user_id menyimpan ID siswa

        if (empty($siswaId)) {
            $this->jsonResponse(['success' => false, 'error' => 'Sesi Anda tidak valid.'], 403);
        }

        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                SELECT t.*, k.nama_komponen, k.tipe_periode, ta.tahun_ajaran
                FROM transaksi_spp_tagihan t
                JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
                JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
                WHERE t.siswa_id = ? AND t.tenant_id = ?
                ORDER BY t.status_lunas ASC, ta.tahun_ajaran DESC, t.bulan ASC
            ");
            $stmt->execute([$siswaId, $tenantId]);
            $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // API Hook: Buat Tagihan PPDB Otomatis saat calon siswa mendaftar
    public function apiCreatePpdbInvoice(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
        }

        $tenantId = $this->resolveTenantId();
        $raw = json_decode(file_get_contents('php://input'), true);

        $siswaId = trim($raw['siswa_id'] ?? '');
        $nominal = (float)($raw['nominal'] ?? 0.00);
        $komponenName = trim($raw['nama_komponen'] ?? 'Biaya Formulir Pendaftaran PPDB');
        $tahunAjaranId = (int)($raw['tahun_ajaran_id'] ?? 0);

        if (empty($siswaId) || $nominal <= 0 || !$tahunAjaranId) {
            $this->jsonResponse(['success' => false, 'error' => 'Data pendaftar PPDB tidak lengkap.'], 422);
        }

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            // 1. Dapatkan atau buat komponen "Formulir Pendaftaran PPDB"
            $stmtComp = $db->prepare("SELECT id FROM transaksi_spp_komponen WHERE tenant_id = ? AND nama_komponen = ? LIMIT 1");
            $stmtComp->execute([$tenantId, $komponenName]);
            $komponenId = $stmtComp->fetchColumn();

            if (!$komponenId) {
                $stmtInsertComp = $db->prepare("INSERT INTO transaksi_spp_komponen (tenant_id, nama_komponen, tipe_periode) VALUES (?, ?, 'Bebas')");
                $stmtInsertComp->execute([$tenantId, $komponenName]);
                $komponenId = $db->lastInsertId();
            }

            // 2. Dapatkan atau buat tarif default untuk tracking
            $stmtTarif = $db->prepare("SELECT id FROM transaksi_spp_tarif WHERE tenant_id = ? AND komponen_id = ? AND tahun_ajaran_id = ? LIMIT 1");
            $stmtTarif->execute([$tenantId, $komponenId, $tahunAjaranId]);
            $tarifId = $stmtTarif->fetchColumn();

            if (!$tarifId) {
                $stmtInsertTarif = $db->prepare("INSERT INTO transaksi_spp_tarif (tenant_id, komponen_id, nominal, tahun_ajaran_id) VALUES (?, ?, ?, ?)");
                $stmtInsertTarif->execute([$tenantId, $komponenId, $nominal, $tahunAjaranId]);
                $tarifId = $db->lastInsertId();
            }

            // 3. Cek duplikasi tagihan PPDB untuk siswa_id ini
            $stmtCheck = $db->prepare("SELECT id FROM transaksi_spp_tagihan WHERE tenant_id = ? AND siswa_id = ? AND komponen_id = ? LIMIT 1");
            $stmtCheck->execute([$tenantId, $siswaId, $komponenId]);
            
            if ($stmtCheck->fetchColumn()) {
                $db->commit();
                $this->jsonResponse(['success' => true, 'message' => 'Tagihan PPDB sudah terdaftar sebelumnya.']);
                return;
            }

            // 4. Buat Tagihan
            $uuid = bin2hex(random_bytes(16));
            $uuidFormatted = sprintf('%08s-%04s-%04s-%04s-%12s',
                substr($uuid, 0, 8),
                substr($uuid, 8, 4),
                substr($uuid, 12, 4),
                substr($uuid, 16, 4),
                substr($uuid, 20, 12)
            );

            $stmtInsertTagihan = $db->prepare("
                INSERT INTO transaksi_spp_tagihan (id, tenant_id, siswa_id, komponen_id, tarif_id, tahun_ajaran_id, nominal_tagihan, status_lunas)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Belum')
            ");
            $stmtInsertTagihan->execute([
                $uuidFormatted,
                $tenantId,
                $siswaId,
                $komponenId,
                $tarifId,
                $tahunAjaranId,
                $nominal
            ]);

            $db->commit();
            $this->jsonResponse(['success' => true, 'tagihan_id' => $uuidFormatted]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // View: Halaman Audit Trail & Log Security (Khusus Super Admin)
    public function auditLog(): void {
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            http_response_code(403);
            echo "<div style='font-family: sans-serif; padding: 50px; text-align: center;'>
                    <h1 style='color: #e11d48;'>403 Forbidden</h1>
                    <p style='color: #475569;'>Akses Ditolak. Halaman Audit Trail & Log Security hanya dapat diakses oleh <strong>Super Admin Platform</strong>.</p>
                    <a href='/SINTA-SaaS/keuangan/dashboard' style='color: #2563eb; text-decoration: none; font-weight: bold;'>&larr; Kembali ke Dashboard Keuangan</a>
                  </div>";
            exit;
        }
        $this->render('keuangan/audit_log', ['title' => 'Audit Trail & Log Security']);
    }

    // API: Dapatkan Data Audit Log (Khusus Super Admin)
    public function apiAuditLog(): void {
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'super_admin') {
            $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak. Fitur audit log hanya khusus Super Admin.'], 403);
        }

        $db = \App\Config\Database::getConnection();

        $search = trim($_GET['q'] ?? '');
        $aksi = trim($_GET['aksi'] ?? '');
        $tenantId = trim($_GET['tenant_id'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = max(1, min(200, (int)($_GET['page_size'] ?? 25)));
        $offset = ($page - 1) * $pageSize;

        try {
            $sqlWhere = "WHERE 1=1";
            $params = [];

            if (!empty($tenantId)) {
                $sqlWhere .= " AND a.tenant_id = ?";
                $params[] = $tenantId;
            }

            if (!empty($aksi)) {
                $sqlWhere .= " AND a.aksi = ?";
                $params[] = $aksi;
            }

            if (!empty($dateFrom)) {
                $sqlWhere .= " AND DATE(a.created_at) >= ?";
                $params[] = $dateFrom;
            }

            if (!empty($dateTo)) {
                $sqlWhere .= " AND DATE(a.created_at) <= ?";
                $params[] = $dateTo;
            }

            if (!empty($search)) {
                $sqlWhere .= " AND (u.nama_lengkap LIKE ? OR u.email LIKE ? OR a.keterangan LIKE ? OR a.target_id LIKE ? OR t.nama_sekolah LIKE ?)";
                $s = "%{$search}%";
                array_push($params, $s, $s, $s, $s, $s);
            }

            // Count Total
            $stmtCount = $db->prepare("
                SELECT COUNT(*) 
                FROM transaksi_spp_audit_log a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN tenants t ON a.tenant_id = t.id
                {$sqlWhere}
            ");
            $stmtCount->execute($params);
            $totalRecords = (int)$stmtCount->fetchColumn();

            // Fetch Items
            $sqlFetch = "
                SELECT 
                    a.id,
                    a.tenant_id,
                    a.user_id,
                    a.aksi,
                    a.tabel_target,
                    a.target_id,
                    a.data_sebelum,
                    a.data_sesudah,
                    a.keterangan,
                    a.created_at,
                    u.nama_lengkap as nama_user,
                    u.email as username,
                    u.role_id,
                    r.nama_role as role_name,
                    t.nama_sekolah
                FROM transaksi_spp_audit_log a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN tenants t ON a.tenant_id = t.id
                {$sqlWhere}
                ORDER BY a.created_at DESC
                LIMIT {$pageSize} OFFSET {$offset}
            ";
            $stmtFetch = $db->prepare($sqlFetch);
            $stmtFetch->execute($params);
            $logs = $stmtFetch->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate Metrics
            $stmtMetrics = $db->prepare("
                SELECT 
                    COUNT(*) as total_log,
                    SUM(CASE WHEN aksi = 'PAYMENT_SPP' THEN 1 ELSE 0 END) as total_pembayaran,
                    SUM(CASE WHEN aksi = 'VOID_PAYMENT' THEN 1 ELSE 0 END) as total_void,
                    SUM(CASE WHEN aksi LIKE '%DELETE%' OR aksi LIKE '%EDIT%' OR aksi LIKE '%UPDATE%' THEN 1 ELSE 0 END) as total_modifikasi
                FROM transaksi_spp_audit_log
            ");
            $stmtMetrics->execute();
            $metrics = $stmtMetrics->fetch(\PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => $logs,
                'metrics' => $metrics,
                'pagination' => [
                    'current_page' => $page,
                    'page_size' => $pageSize,
                    'total_records' => $totalRecords,
                    'total_pages' => (int)ceil($totalRecords / $pageSize)
                ]
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
