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
        $komponen = $db->query("SELECT id, nama_komponen FROM transaksi_spp_komponen WHERE tenant_id = '{$tenantId}' ORDER BY nama_komponen ASC")->fetchAll(PDO::FETCH_ASSOC);

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
        $komponen = $db->query("SELECT id, nama_komponen, tipe_periode FROM transaksi_spp_komponen WHERE tenant_id = '{$tenantId}' AND is_active = 1 ORDER BY nama_komponen ASC")->fetchAll(PDO::FETCH_ASSOC);

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
                $stmt = $db->prepare("SELECT * FROM transaksi_spp_komponen WHERE tenant_id = ? ORDER BY id DESC");
                $stmt->execute([$tenantId]);
                $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } elseif ($method === 'POST') {
                $raw = json_decode(file_get_contents('php://input'), true);
                $id = (int)($raw['id'] ?? 0);
                $nama = trim($raw['nama_komponen'] ?? '');
                $tipe = trim($raw['tipe_periode'] ?? 'Bulanan');

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
                $stmt = $db->prepare("
                    SELECT t.*, k.nama_komponen, c.nama_kelas, j.nama_jenjang, ta.tahun_ajaran
                    FROM transaksi_spp_tarif t
                    JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
                    LEFT JOIN kelas c ON t.kelas_id = c.id
                    LEFT JOIN jenjang j ON t.jenjang_id = j.id
                    JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
                    WHERE t.tenant_id = ?
                    ORDER BY t.id DESC
                ");
                $stmt->execute([$tenantId]);
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

                $stmt = $db->prepare("
                    INSERT INTO transaksi_spp_tarif (tenant_id, komponen_id, kelas_id, jenjang_id, jalur_masuk, nominal, tahun_ajaran_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$tenantId, $komponenId, $kelasId, $jenjangId, $jalurMasuk, $nominal, $tahunAjaranId]);
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
                $sqlSiswa .= " AND s.id_kelas IN (SELECT id FROM kelas WHERE jenjang_id = ?)";
                $paramsSiswa[] = $jenjangId;
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
                        $stmtCheckJenjang = $db->prepare("SELECT id FROM kelas WHERE id = ? AND jenjang_id = ? LIMIT 1");
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

    // API: Cari Siswa Dinamis
    public function apiCariSiswa(): void {
        $tenantId = $this->resolveTenantId();
        $query = trim($_GET['q'] ?? '');

        if (empty($query)) {
            $this->jsonResponse(['success' => true, 'data' => []]);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT s.id, s.nama_lengkap as nama, s.nisn, k.nama_kelas
            FROM siswa s
            JOIN kelas k ON s.id_kelas = k.id
            WHERE s.tenant_id = ? AND s.status = 'Aktif' AND s.deleted_at IS NULL
              AND (s.nama_lengkap LIKE ? OR s.nisn LIKE ?)
            LIMIT 15
        ");
        $stmt->execute([$tenantId, "%$query%", "%$query%"]);
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
            SELECT t.*, k.nama_komponen, k.tipe_periode, ta.tahun_ajaran
            FROM transaksi_spp_tagihan t
            JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
            JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
            WHERE t.siswa_id = ? AND t.tenant_id = ? AND t.status_lunas != 'Lunas'
            ORDER BY ta.tahun_ajaran DESC, t.bulan ASC
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
            $kwitansiNo = 'KW/' . date('Ymd') . '/' . strtoupper(bin2hex(random_bytes(3)));

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
                    'CREATE_PAYMENT',
                    'transaksi_spp_tagihan',
                    $tagihanId,
                    json_encode(['nominal_bayar' => $oldNominalBayar]),
                    json_encode(['nominal_bayar' => $newNominalBayar, 'status_lunas' => $statusLunas]),
                    "Pembayaran via loket sebesar Rp " . number_format($nominalDibayar)
                ]);

                $pembayaranIds[] = $uuidFormatted;
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'nomor_kwitansi' => $kwitansiNo]);
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
            if ($tipe === 'pemasukan') {
                $stmt = $db->prepare("
                    SELECT p.*, s.nama_lengkap as nama_siswa, s.nisn, k.nama_komponen, u.nama_lengkap as nama_kasir
                    FROM transaksi_spp_pembayaran p
                    JOIN siswa s ON p.siswa_id = s.id
                    JOIN transaksi_spp_tagihan t ON p.tagihan_id = t.id
                    JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
                    JOIN users u ON p.kasir_id = u.id
                    WHERE p.tenant_id = ?
                    ORDER BY p.tanggal_bayar DESC
                ");
                $stmt->execute([$tenantId]);
                $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } else {
                // Tunggakan
                $stmt = $db->prepare("
                    SELECT t.*, s.nama_lengkap as nama_siswa, s.nisn, k.nama_komponen, ta.tahun_ajaran
                    FROM transaksi_spp_tagihan t
                    JOIN siswa s ON t.siswa_id = s.id
                    JOIN transaksi_spp_komponen k ON t.komponen_id = k.id
                    JOIN tahun_ajaran ta ON t.tahun_ajaran_id = ta.id
                    WHERE t.tenant_id = ? AND t.status_lunas != 'Lunas'
                    ORDER BY t.created_at DESC
                ");
                $stmt->execute([$tenantId]);
                $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
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
}
