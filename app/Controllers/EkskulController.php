<?php

namespace App\Controllers;

use App\Core\SessionManager;
use PDO;

/**
 * EkskulController
 * 
 * Menangani Modul Kesiswaan - Ekstrakurikuler:
 * - Master Data Ekskul
 * - Kelola Anggota Ekskul
 * - Jurnal & Kegiatan
 * - Presensi & Penilaian
 */
class EkskulController extends BaseController {

    private const ALLOWED_ROLES = ['super_admin', 'operator_sekolah', 'guru_pembina', 'guru_bk', 'kepala_sekolah', 'kesiswaan'];

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->guardRole();
    }

    private function guardRole(): void {
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $hasAllowedRole = false;
        foreach ($roles as $r) {
            if (in_array($r, self::ALLOWED_ROLES, true)) {
                $hasAllowedRole = true;
                break;
            }
        }
        if (!$hasAllowedRole) {
            $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/SINTA-SaaS/api/');
            if ($isApi) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Role tidak memiliki akses ke modul Ekskul.']);
                exit;
            } else {
                header("Location: /SINTA-SaaS/dashboard?error=forbidden");
                exit;
            }
        }
    }

    /**
     * Menampilkan halaman utama modul Ekskul
     */
    public function index() {
        $tenant_id = $_SESSION['tenant_id'];
        $role = $_SESSION['role_name'] ?? '';
        
        $db = \App\Config\Database::getConnection();
        $tenants = [];
        
        if ($role === 'super_admin') {
            $tenants = $db->query("SELECT id, nama_sekolah FROM tenants WHERE status = 'active' AND deleted_at IS NULL ORDER BY nama_sekolah ASC")->fetchAll(PDO::FETCH_ASSOC);
            if (isset($_GET['tenant_id']) && !empty($_GET['tenant_id'])) {
                $tenant_id = $_GET['tenant_id'];
            } else if (!empty($tenants)) {
                $tenant_id = $tenants[0]['id'];
            }
        }

        // Get Master Ekskul
        $user_id = $_SESSION['user_id'];
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isAdminOrKesiswaan = in_array('super_admin', $roles, true) || in_array('operator_sekolah', $roles, true) || in_array('kesiswaan', $roles, true);

        if ($isAdminOrKesiswaan) {
            $stmt = $db->prepare("
                SELECT e.*, u.nama_lengkap as nama_pembina 
                FROM master_ekskul e
                LEFT JOIN users u ON e.pembina_id = u.id
                WHERE e.tenant_id = ? AND e.deleted_at IS NULL
            ");
            $stmt->execute([$tenant_id]);
        } else {
            $stmt = $db->prepare("
                SELECT e.*, u.nama_lengkap as nama_pembina 
                FROM master_ekskul e
                LEFT JOIN users u ON e.pembina_id = u.id
                WHERE e.tenant_id = ? AND e.pembina_id = ? AND e.deleted_at IS NULL
            ");
            $stmt->execute([$tenant_id, $user_id]);
        }
        $master_ekskul = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get Pembina List
        $stmtPembina = $db->prepare("
            SELECT u.id, u.nama_lengkap, u.email, u.status 
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.id
            WHERE u.tenant_id = ? AND r.nama_role = 'guru_pembina' AND u.deleted_at IS NULL
        ");
        $stmtPembina->execute([$tenant_id]);
        $pembina_list = $stmtPembina->fetchAll(PDO::FETCH_ASSOC);

        // Get Active Tahun Ajaran
        $stmtTa = $db->prepare("SELECT id, tahun_ajaran FROM tahun_ajaran WHERE tenant_id = ? AND is_active = 1 ORDER BY tahun_ajaran DESC LIMIT 1");
        $stmtTa->execute([$tenant_id]);
        $active_ta = $stmtTa->fetch(PDO::FETCH_ASSOC);
        if (!$active_ta) {
            $stmtTa = $db->prepare("SELECT id, tahun_ajaran FROM tahun_ajaran WHERE tenant_id = ? ORDER BY tahun_ajaran DESC LIMIT 1");
            $stmtTa->execute([$tenant_id]);
            $active_ta = $stmtTa->fetch(PDO::FETCH_ASSOC);
        }
        $active_ta_id = $active_ta['id'] ?? null;

        $selected_ekskul_id = $_GET['ekskul_id'] ?? '';
        if (empty($selected_ekskul_id) && !empty($master_ekskul)) {
            foreach ($master_ekskul as $me) {
                if (($me['status'] ?? 'active') === 'active') {
                    $selected_ekskul_id = $me['id'];
                    break;
                }
            }
            if (empty($selected_ekskul_id)) {
                $selected_ekskul_id = $master_ekskul[0]['id'];
            }
        }
        if (!empty($selected_ekskul_id) && !$isAdminOrKesiswaan) {
            $hasAccess = false;
            foreach ($master_ekskul as $me) {
                if ($me['id'] === $selected_ekskul_id) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                if (!empty($master_ekskul)) {
                    $selected_ekskul_id = $master_ekskul[0]['id'];
                } else {
                    $selected_ekskul_id = '';
                }
            }
        }
        $selected_semester = $_GET['semester'] ?? 'Ganjil';
        if (!in_array($selected_semester, ['Ganjil', 'Genap'], true)) {
            $selected_semester = 'Ganjil';
        }
        $selected_kelas_id = $_GET['kelas_id'] ?? '';

        // Get all active classes for dropdown filter
        $stmtKelas = $db->prepare("SELECT id, nama_kelas FROM kelas WHERE tenant_id = ? AND is_active = 1 AND deleted_at IS NULL ORDER BY nama_kelas ASC");
        $stmtKelas->execute([$tenant_id]);
        $kelas_list = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

        // Pagination for current members
        $per_page      = 30;
        $page_anggota  = max(1, (int)($_GET['page_anggota'] ?? 1));
        $offset_anggota = ($page_anggota - 1) * $per_page;

        // Pagination for available students
        $page_available  = max(1, (int)($_GET['page_available'] ?? 1));
        $offset_available = ($page_available - 1) * $per_page;

        $current_members = [];
        $available_students = [];
        $total_members   = 0;
        $total_available = 0;

        if (!empty($selected_ekskul_id)) {
            // Count total current members
            $stmtCountMembers = $db->prepare("
                SELECT COUNT(*) FROM anggota_ekskul ae
                JOIN siswa s ON ae.siswa_id = s.id
                WHERE ae.ekskul_id = ? AND ae.tahun_ajaran_id = ? AND ae.semester = ? AND ae.tenant_id = ? AND s.deleted_at IS NULL
            ");
            $stmtCountMembers->execute([$selected_ekskul_id, $active_ta_id, $selected_semester, $tenant_id]);
            $total_members = (int)$stmtCountMembers->fetchColumn();

            // Get paginated current members
            $stmtMembers = $db->prepare("
                SELECT ae.id as membership_id, s.id as siswa_id, s.nama_lengkap, s.nisn, k.nama_kelas
                FROM anggota_ekskul ae
                JOIN siswa s ON ae.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                WHERE ae.ekskul_id = ? AND ae.tahun_ajaran_id = ? AND ae.semester = ? AND ae.tenant_id = ? AND s.deleted_at IS NULL
                ORDER BY s.nama_lengkap ASC
                LIMIT ? OFFSET ?
            ");
            $stmtMembers->execute([$selected_ekskul_id, $active_ta_id, $selected_semester, $tenant_id, $per_page, $offset_anggota]);
            $current_members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

            // Build available students query with count
            $whereAvailable = "s.tenant_id = :tenant_id AND s.status = 'Aktif' AND s.deleted_at IS NULL
                AND s.id NOT IN (SELECT siswa_id FROM anggota_ekskul WHERE ekskul_id = :ekskul_id AND tahun_ajaran_id = :ta_id AND semester = :semester AND tenant_id = :sub_tenant_id)";
            $paramsAvailable = [
                'tenant_id'     => $tenant_id,
                'sub_tenant_id' => $tenant_id,
                'ekskul_id'     => $selected_ekskul_id,
                'ta_id'         => $active_ta_id,
                'semester'      => $selected_semester
            ];

            if (!empty($selected_kelas_id)) {
                $whereAvailable .= ' AND s.id_kelas = :kelas_id';
                $paramsAvailable['kelas_id'] = $selected_kelas_id;
            }

            $stmtCountAvail = $db->prepare("SELECT COUNT(*) FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id WHERE $whereAvailable");
            $stmtCountAvail->execute($paramsAvailable);
            $total_available = (int)$stmtCountAvail->fetchColumn();

            $stmtAvailable = $db->prepare("SELECT s.id as siswa_id, s.nama_lengkap, s.nisn, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id WHERE $whereAvailable ORDER BY s.nama_lengkap ASC LIMIT :limit OFFSET :offset_val");
            // PDO named params workaround for LIMIT/OFFSET
            foreach ($paramsAvailable as $k => $v) {
                $stmtAvailable->bindValue(":$k", $v);
            }
            $stmtAvailable->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmtAvailable->bindValue(':offset_val', $offset_available, PDO::PARAM_INT);
            $stmtAvailable->execute();
            $available_students = $stmtAvailable->fetchAll(PDO::FETCH_ASSOC);
        }

        $total_pages_anggota   = $total_members   > 0 ? (int)ceil($total_members   / $per_page) : 1;
        $total_pages_available = $total_available > 0 ? (int)ceil($total_available / $per_page) : 1;

        $nilai_list = [];
        $kunci_anggota = false;
        $kunci_nilai = false;

        if (!empty($selected_ekskul_id)) {
            // Fetch lock states
            $stmtKunci = $db->prepare("SELECT kunci_anggota, kunci_nilai FROM kunci_ekskul WHERE ekskul_id = ? AND tahun_ajaran_id = ? AND semester = ?");
            $stmtKunci->execute([$selected_ekskul_id, $active_ta_id, $selected_semester]);
            $kunci = $stmtKunci->fetch(PDO::FETCH_ASSOC);
            if ($kunci) {
                $kunci_anggota = (bool)$kunci['kunci_anggota'];
                $kunci_nilai = (bool)$kunci['kunci_nilai'];
            }

            $stmtNilai = $db->prepare("
                SELECT 
                    ae.siswa_id, 
                    s.nama_lengkap, 
                    s.nisn, 
                    k.nama_kelas,
                    ne.id as nilai_id,
                    ne.poin,
                    ne.nilai,
                    ne.deskripsi,
                    ne.sakit,
                    ne.izin,
                    ne.alfa
                FROM anggota_ekskul ae
                JOIN siswa s ON ae.siswa_id = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN nilai_ekskul ne ON (
                    ae.ekskul_id = ne.ekskul_id 
                    AND ae.siswa_id = ne.siswa_id 
                    AND ae.tahun_ajaran_id = ne.tahun_ajaran_id 
                    AND ae.semester = ne.semester
                )
                WHERE ae.ekskul_id = ? 
                  AND ae.tahun_ajaran_id = ? 
                  AND ae.semester = ? 
                  AND ae.tenant_id = ?
                  AND s.deleted_at IS NULL
                ORDER BY s.nama_lengkap ASC
            ");
            $stmtNilai->execute([$selected_ekskul_id, $active_ta_id, $selected_semester, $tenant_id]);
            $nilai_list = $stmtNilai->fetchAll(PDO::FETCH_ASSOC);
        }

        // Render View
        $this->render('kesiswaan_ekskul', [
            'master_ekskul' => $master_ekskul,
            'pembina_list' => $pembina_list,
            'tenants' => $tenants,
            'selected_tenant' => $tenant_id,
            'is_super_admin' => ($role === 'super_admin'),
            'role' => $role,
            'active_ta' => $active_ta,
            'kelas_list' => $kelas_list,
            'selected_ekskul_id' => $selected_ekskul_id,
            'selected_kelas_id' => $selected_kelas_id,
            'selected_semester' => $selected_semester,
            'current_members' => $current_members,
            'available_students' => $available_students,
            'nilai_list' => $nilai_list,
            'kunci_anggota' => $kunci_anggota,
            'kunci_nilai' => $kunci_nilai,
            // Pagination
            'page_anggota' => $page_anggota,
            'page_available' => $page_available,
            'per_page' => $per_page,
            'total_members' => $total_members,
            'total_available' => $total_available,
            'total_pages_anggota' => $total_pages_anggota,
            'total_pages_available' => $total_pages_available,
        ]);
    }


    /**
     * Menambahkan Ekskul Baru
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'];
        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            if (isset($_POST['tenant_id']) && !empty($_POST['tenant_id'])) {
                $tenant_id = $_POST['tenant_id'];
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Sekolah (Tenant) wajib dipilih.']);
                exit;
            }
        }

        $nama_ekskul = $_POST['nama_ekskul'] ?? '';
        $kategori = $_POST['kategori'] ?? '';
        $pembina_id = $_POST['pembina_id'] ?? null;
        if (empty($pembina_id)) {
            $pembina_id = null;
        }

        if (empty($nama_ekskul) || empty($kategori)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Nama Ekskul dan Kategori wajib diisi.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("INSERT INTO master_ekskul (id, tenant_id, nama_ekskul, kategori, pembina_id) VALUES (UUID(), ?, ?, ?, ?)");
        
        if ($stmt->execute([$tenant_id, $nama_ekskul, $kategori, $pembina_id])) {
            $successMsg = urlencode('Ekskul "' . $nama_ekskul . '" berhasil ditambahkan.');
            header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=master&success={$successMsg}" . (($_SESSION['role_name'] ?? '') === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ekskul.']);
            exit;
        }
    }

    /**
     * Menambahkan Guru Pembina Baru
     */
    public function storePembina() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'];
        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            if (isset($_POST['tenant_id']) && !empty($_POST['tenant_id'])) {
                $tenant_id = $_POST['tenant_id'];
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Sekolah (Tenant) wajib dipilih.']);
                exit;
            }
        }

        $nama_lengkap = $_POST['nama_lengkap'] ?? '';
        $email        = $_POST['email'] ?? '';
        $password     = $_POST['password'] ?? '';
        $no_telp      = trim($_POST['no_telp'] ?? '');

        if (empty($nama_lengkap) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();
        
        // Check if email already exists
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar.']);
            exit;
        }

        $db->beginTransaction();
        try {
            // Gunakan UUID bawaan database
            $stmtUuid = $db->query("SELECT UUID() as uuid");
            $user_id = $stmtUuid->fetch(PDO::FETCH_ASSOC)['uuid'];

            // Dapatkan role_id terlebih dahulu karena tabel users membutuhkan role_id
            $stmtRole = $db->prepare("SELECT id FROM roles WHERE nama_role = 'guru_pembina'");
            $stmtRole->execute();
            $role_id = $stmtRole->fetch(PDO::FETCH_ASSOC)['id'];

            if (!$role_id) {
                throw new \Exception("Role guru_pembina tidak ditemukan di database.");
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmtUser = $db->prepare("INSERT INTO users (id, tenant_id, role_id, nama_lengkap, email, no_telp, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtUser->execute([$user_id, $tenant_id, $role_id, $nama_lengkap, $email, $no_telp ?: null, $hashed_password]);

            if ($role_id) {
                $stmtUserRole = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $stmtUserRole->execute([$user_id, $role_id]);
            }
            
            $db->commit();
            $successMsg = urlencode('Guru Pembina "' . $nama_lengkap . '" berhasil ditambahkan.');
            header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=pembina&success={$successMsg}" . (($_SESSION['role_name'] ?? '') === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data pembina.']);
            exit;
        }
    }

    /**
     * Memperbarui Data Ekskul
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $id = $_POST['id'] ?? '';
        $tenant_id = $_SESSION['tenant_id'];

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID Ekskul tidak ditemukan.']);
            exit;
        }

        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            if (isset($_POST['tenant_id']) && !empty($_POST['tenant_id'])) {
                $tenant_id = $_POST['tenant_id'];
            }
        }

        $nama_ekskul = $_POST['nama_ekskul'] ?? '';
        $kategori = $_POST['kategori'] ?? 'Pilihan';
        $pembina_id = !empty($_POST['pembina_id']) ? $_POST['pembina_id'] : null;

        if (empty($nama_ekskul)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Nama ekskul wajib diisi.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();
        
        // Verifikasi kepemilikan tenant jika bukan super admin
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $stmtCheck = $db->prepare("SELECT id FROM master_ekskul WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtCheck->execute([$id, $tenant_id]);
            if ($stmtCheck->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk mengubah data ini.']);
                exit;
            }
        }

        $stmt = $db->prepare("UPDATE master_ekskul SET nama_ekskul = ?, kategori = ?, pembina_id = ? WHERE id = ? AND deleted_at IS NULL");
        
        if ($stmt->execute([$nama_ekskul, $kategori, $pembina_id, $id])) {
            $successMsg = urlencode('Data Ekskul berhasil diperbarui.');
            header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=master&success={$successMsg}" . (($_SESSION['role_name'] ?? '') === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data ekskul.']);
            exit;
        }
    }

    /**
     * Memperbarui Data Guru Pembina
     */
    public function updatePembina() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $id = $_POST['id'] ?? '';
        $tenant_id = $_SESSION['tenant_id'];

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID Pembina tidak ditemukan.']);
            exit;
        }

        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            if (isset($_POST['tenant_id']) && !empty($_POST['tenant_id'])) {
                $tenant_id = $_POST['tenant_id'];
            }
        }

        $nama_lengkap = $_POST['nama_lengkap'] ?? '';
        $email        = $_POST['email'] ?? '';
        $password     = $_POST['password'] ?? '';
        $no_telp      = trim($_POST['no_telp'] ?? '');

        if (empty($nama_lengkap) || empty($email)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Nama lengkap dan email wajib diisi.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();

        // Verifikasi kepemilikan tenant jika bukan super admin
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtCheck->execute([$id, $tenant_id]);
            if ($stmtCheck->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk mengubah data ini.']);
                exit;
            }
        }

        // Cek email konflik
        $stmtCheckEmail = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL");
        $stmtCheckEmail->execute([$email, $id]);
        if ($stmtCheckEmail->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar pada pengguna lain.']);
            exit;
        }

        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET nama_lengkap = ?, email = ?, no_telp = ?, password = ? WHERE id = ?");
                $stmt->execute([$nama_lengkap, $email, $no_telp ?: null, $hashed_password, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET nama_lengkap = ?, email = ?, no_telp = ? WHERE id = ?");
                $stmt->execute([$nama_lengkap, $email, $no_telp ?: null, $id]);
            }

            $successMsg = urlencode('Data Pembina berhasil diperbarui.');
            header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=pembina&success={$successMsg}" . (($_SESSION['role_name'] ?? '') === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data pembina.']);
            exit;
        }
    }

    /**
     * Aktifkan / Nonaktifkan Ekstrakurikuler
     */
    public function toggleStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $id = $_POST['id'] ?? '';
        $new_status = $_POST['new_status'] ?? 'active';
        $tenant_id = $_SESSION['tenant_id'];

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID Ekskul tidak ditemukan.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();

        // Verifikasi kepemilikan tenant jika bukan super admin
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $stmtCheck = $db->prepare("SELECT id FROM master_ekskul WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtCheck->execute([$id, $tenant_id]);
            if ($stmtCheck->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk mengubah data ini.']);
                exit;
            }
        }

        $stmt = $db->prepare("UPDATE master_ekskul SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $id])) {
            header("Location: /SINTA-SaaS/kesiswaan/ekskul" . (($_SESSION['role_name'] ?? '') === 'super_admin' && isset($_GET['tenant_id']) ? "?tenant_id={$_GET['tenant_id']}" : ""));
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status ekstrakurikuler.']);
            exit;
        }
    }

    /**
     * Aktifkan / Nonaktifkan Guru Pembina
     */
    public function togglePembinaStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $id = $_POST['id'] ?? '';
        $new_status = $_POST['new_status'] ?? 'active';
        $tenant_id = $_SESSION['tenant_id'];

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID Pembina tidak ditemukan.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();

        // Verifikasi kepemilikan tenant jika bukan super admin
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtCheck->execute([$id, $tenant_id]);
            if ($stmtCheck->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk mengubah data ini.']);
                exit;
            }
        }

        $db->beginTransaction();
        try {
            // Jika dinonaktifkan, cabut dari daftar ekskul yang sedang diampunya
            if ($new_status === 'inactive') {
                $stmtUpdateEkskul = $db->prepare("UPDATE master_ekskul SET pembina_id = NULL WHERE pembina_id = ?");
                $stmtUpdateEkskul->execute([$id]);
            }

            // Update status user
            $stmtUser = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmtUser->execute([$new_status, $id]);

            $db->commit();
            header("Location: /SINTA-SaaS/kesiswaan/ekskul" . (($_SESSION['role_name'] ?? '') === 'super_admin' && isset($_GET['tenant_id']) ? "?tenant_id={$_GET['tenant_id']}" : ""));
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status pembina.']);
            exit;
        }
    }

    /**
     * Menambahkan Anggota Ekskul Baru
     */
    public function addMembers() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'];
        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            $tenant_id = $_POST['tenant_id'] ?? $_GET['tenant_id'] ?? '';
            if (empty($tenant_id)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Sekolah (Tenant) wajib dipilih.']);
                exit;
            }
        }

        $ekskul_id = $_POST['ekskul_id'] ?? '';
        $siswa_ids = $_POST['siswa_ids'] ?? [];
        $tahun_ajaran_id = $_POST['tahun_ajaran_id'] ?? '';
        $semester = $_POST['semester'] ?? 'Ganjil';
        if (!in_array($semester, ['Ganjil', 'Genap'], true)) {
            $semester = 'Ganjil';
        }

        if (empty($ekskul_id) || empty($tahun_ajaran_id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Ekstrakurikuler dan Tahun Ajaran wajib diisi.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();

        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isAdminOrKesiswaan = in_array('super_admin', $roles, true) || in_array('operator_sekolah', $roles, true) || in_array('kesiswaan', $roles, true);

        if (!$isAdminOrKesiswaan) {
            $stmtCheckPembina = $db->prepare("SELECT COUNT(*) FROM master_ekskul WHERE id = ? AND pembina_id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtCheckPembina->execute([$ekskul_id, $_SESSION['user_id'], $tenant_id]);
            if ((int)$stmtCheckPembina->fetchColumn() === 0) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengelola ekstrakurikuler ini.']);
                exit;
            }
        }

        // Check if members lock is active
        $stmtKunci = $db->prepare("SELECT kunci_anggota FROM kunci_ekskul WHERE ekskul_id = ? AND tahun_ajaran_id = ? AND semester = ?");
        $stmtKunci->execute([$ekskul_id, $tahun_ajaran_id, $semester]);
        if ($stmtKunci->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Kelola Anggota untuk semester ini telah dikunci.']);
            exit;
        }

        if (empty($siswa_ids)) {
            header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=anggota&ekskul_id={$ekskul_id}&semester={$semester}" . (($_SESSION['role_name'] ?? '') === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
            exit;
        }

        $db->beginTransaction();
        try {
            $stmtInsert = $db->prepare("
                INSERT INTO anggota_ekskul (id, tenant_id, ekskul_id, siswa_id, tahun_ajaran_id, semester) 
                VALUES (UUID(), ?, ?, ?, ?, ?)
            ");

            foreach ($siswa_ids as $siswa_id) {
                // Check if already member
                $stmtCheck = $db->prepare("SELECT id FROM anggota_ekskul WHERE tenant_id = ? AND ekskul_id = ? AND siswa_id = ? AND tahun_ajaran_id = ? AND semester = ?");
                $stmtCheck->execute([$tenant_id, $ekskul_id, $siswa_id, $tahun_ajaran_id, $semester]);
                if ($stmtCheck->rowCount() === 0) {
                    $stmtInsert->execute([$tenant_id, $ekskul_id, $siswa_id, $tahun_ajaran_id, $semester]);
                }
            }

            $db->commit();
            $count = count($siswa_ids);
            $successMsg = urlencode("{$count} siswa berhasil ditambahkan sebagai anggota.");
            header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=anggota&ekskul_id={$ekskul_id}&semester={$semester}&success={$successMsg}" . (($_SESSION['role_name'] ?? '') === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan anggota ekskul: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Menghapus Anggota Ekskul
     */
    public function removeMember() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'];
        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            $tenant_id = $_POST['tenant_id'] ?? $_GET['tenant_id'] ?? '';
        }

        $membership_id = $_POST['membership_id'] ?? '';
        $ekskul_id = $_POST['ekskul_id'] ?? '';
        $semester = $_POST['semester'] ?? 'Ganjil';

        if (empty($membership_id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID Keanggotaan tidak ditemukan.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();

        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isAdminOrKesiswaan = in_array('super_admin', $roles, true) || in_array('operator_sekolah', $roles, true) || in_array('kesiswaan', $roles, true);

        if (!$isAdminOrKesiswaan) {
            $stmtCheckPembina = $db->prepare("SELECT COUNT(*) FROM master_ekskul WHERE id = ? AND pembina_id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtCheckPembina->execute([$ekskul_id, $_SESSION['user_id'], $tenant_id]);
            if ((int)$stmtCheckPembina->fetchColumn() === 0) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengelola ekstrakurikuler ini.']);
                exit;
            }
        }

        // Check if members lock is active
        $stmtMember = $db->prepare("SELECT tahun_ajaran_id FROM anggota_ekskul WHERE id = ?");
        $stmtMember->execute([$membership_id]);
        $ta_id = $stmtMember->fetchColumn();
        if ($ta_id) {
            $stmtKunci = $db->prepare("SELECT kunci_anggota FROM kunci_ekskul WHERE ekskul_id = ? AND tahun_ajaran_id = ? AND semester = ?");
            $stmtKunci->execute([$ekskul_id, $ta_id, $semester]);
            if ($stmtKunci->fetchColumn()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Kelola Anggota untuk semester ini telah dikunci.']);
                exit;
            }
        }

        // Verifikasi kepemilikan tenant jika bukan super admin
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $stmtCheck = $db->prepare("SELECT id FROM anggota_ekskul WHERE id = ? AND tenant_id = ?");
            $stmtCheck->execute([$membership_id, $tenant_id]);
            if ($stmtCheck->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk menghapus data ini.']);
                exit;
            }
        }

        $stmt = $db->prepare("DELETE FROM anggota_ekskul WHERE id = ?");
        if ($stmt->execute([$membership_id])) {
            $successMsg = urlencode('Siswa berhasil dikeluarkan dari ekstrakurikuler.');
            header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=anggota&ekskul_id={$ekskul_id}&semester={$semester}&success={$successMsg}" . (($_SESSION['role_name'] ?? '') === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengeluarkan siswa dari ekskul.']);
        }
    }

    /**
     * Menyimpan Penilaian & Presensi Siswa
     */
    public function saveGrades() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'];
        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            $tenant_id = $_POST['tenant_id'] ?? $_GET['tenant_id'] ?? '';
            if (empty($tenant_id)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Sekolah (Tenant) wajib dipilih.']);
                exit;
            }
        }

        $ekskul_id = $_POST['ekskul_id'] ?? '';
        $tahun_ajaran_id = $_POST['tahun_ajaran_id'] ?? '';
        $semester = $_POST['semester'] ?? 'Ganjil';
        $grades = $_POST['grades'] ?? []; // Array indexed by siswa_id

        if (empty($ekskul_id) || empty($tahun_ajaran_id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Ekstrakurikuler dan Tahun Ajaran wajib diisi.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();

        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isAdminOrKesiswaan = in_array('super_admin', $roles, true) || in_array('operator_sekolah', $roles, true) || in_array('kesiswaan', $roles, true);

        if (!$isAdminOrKesiswaan) {
            $stmtCheckPembina = $db->prepare("SELECT COUNT(*) FROM master_ekskul WHERE id = ? AND pembina_id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtCheckPembina->execute([$ekskul_id, $_SESSION['user_id'], $tenant_id]);
            if ((int)$stmtCheckPembina->fetchColumn() === 0) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengelola ekstrakurikuler ini.']);
                exit;
            }
        }

        // Check if grades lock is active
        $stmtKunci = $db->prepare("SELECT kunci_nilai FROM kunci_ekskul WHERE ekskul_id = ? AND tahun_ajaran_id = ? AND semester = ?");
        $stmtKunci->execute([$ekskul_id, $tahun_ajaran_id, $semester]);
        if ($stmtKunci->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Penilaian & Presensi untuk semester ini telah dikunci.']);
            exit;
        }

        $db->beginTransaction();
        try {
            // Prepared statement for INSERT/UPDATE
            $stmtUpsert = $db->prepare("
                INSERT INTO nilai_ekskul (id, tenant_id, ekskul_id, siswa_id, tahun_ajaran_id, semester, poin, nilai, deskripsi, sakit, izin, alfa)
                VALUES (UUID(), :tenant_id, :ekskul_id, :siswa_id, :tahun_ajaran_id, :semester, :poin, :nilai, :deskripsi, :sakit, :izin, :alfa)
                ON DUPLICATE KEY UPDATE 
                    poin = VALUES(poin),
                    nilai = VALUES(nilai),
                    deskripsi = VALUES(deskripsi),
                    sakit = VALUES(sakit),
                    izin = VALUES(izin),
                    alfa = VALUES(alfa)
            ");

            foreach ($grades as $siswa_id => $data) {
                $rawPoin = isset($data['poin']) ? trim((string)$data['poin']) : '';
                $poin = $rawPoin !== '' ? (int)$rawPoin : null;
                if ($poin !== null && ($poin < 0 || $poin > 100)) {
                    $poin = null;
                }

                $nilai = $data['nilai'] ?? 'B';
                $deskripsi = $data['deskripsi'] ?? '';
                $sakit = isset($data['sakit']) ? (int)$data['sakit'] : 0;
                $izin = isset($data['izin']) ? (int)$data['izin'] : 0;
                $alfa = isset($data['alfa']) ? (int)$data['alfa'] : 0;

                // Validate nilai enum
                if (!in_array($nilai, ['A', 'B', 'C', 'D'], true)) {
                    $nilai = 'B';
                }

                $stmtUpsert->execute([
                    'tenant_id' => $tenant_id,
                    'ekskul_id' => $ekskul_id,
                    'siswa_id' => $siswa_id,
                    'tahun_ajaran_id' => $tahun_ajaran_id,
                    'semester' => $semester,
                    'poin' => $poin,
                    'nilai' => $nilai,
                    'deskripsi' => $deskripsi,
                    'sakit' => $sakit,
                    'izin' => $izin,
                    'alfa' => $alfa
                ]);
            }

            $db->commit();
            $successMsg = urlencode('Penilaian & Presensi berhasil disimpan.');
            header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=nilai&ekskul_id={$ekskul_id}&semester={$semester}&success={$successMsg}" . (($_SESSION['role_name'] ?? '') === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan penilaian: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Toggle status kunci keanggotaan
     */
    public function toggleLockAnggota() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $canLock = in_array('super_admin', $roles, true) || in_array('operator_sekolah', $roles, true) || in_array('kesiswaan', $roles, true);
        if (!$canLock) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Hanya Admin, Operator Sekolah, atau Kesiswaan yang dapat mengunci data.']);
            exit;
        }

        $role = $_SESSION['role_name'] ?? '';
        $tenant_id = $_SESSION['tenant_id'];
        if ($role === 'super_admin') {
            $tenant_id = $_POST['tenant_id'] ?? $_GET['tenant_id'] ?? '';
        }

        $ekskul_id = $_POST['ekskul_id'] ?? '';
        $tahun_ajaran_id = $_POST['tahun_ajaran_id'] ?? '';
        $semester = $_POST['semester'] ?? 'Ganjil';
        $new_state = isset($_POST['lock']) ? (int)$_POST['lock'] : 0;

        if (empty($ekskul_id) || empty($tahun_ajaran_id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO kunci_ekskul (id, tenant_id, ekskul_id, tahun_ajaran_id, semester, kunci_anggota)
            VALUES (UUID(), :tenant_id, :ekskul_id, :ta_id, :semester, :kunci)
            ON DUPLICATE KEY UPDATE kunci_anggota = VALUES(kunci_anggota)
        ");
        $stmt->execute([
            'tenant_id' => $tenant_id,
            'ekskul_id' => $ekskul_id,
            'ta_id' => $tahun_ajaran_id,
            'semester' => $semester,
            'kunci' => $new_state
        ]);

        $lockLabel = $new_state ? 'Keanggotaan berhasil dikunci.' : 'Kunci keanggotaan berhasil dibuka.';
        $successMsg = urlencode($lockLabel);
        header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=anggota&ekskul_id={$ekskul_id}&semester={$semester}&success={$successMsg}" . ($role === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
        exit;
    }

    /**
     * Toggle status kunci penilaian
     */
    public function toggleLockNilai() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $canLock = in_array('super_admin', $roles, true) || in_array('operator_sekolah', $roles, true) || in_array('kesiswaan', $roles, true);
        if (!$canLock) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Hanya Admin, Operator Sekolah, atau Kesiswaan yang dapat mengunci data.']);
            exit;
        }

        $role = $_SESSION['role_name'] ?? '';
        $tenant_id = $_SESSION['tenant_id'];
        if ($role === 'super_admin') {
            $tenant_id = $_POST['tenant_id'] ?? $_GET['tenant_id'] ?? '';
        }

        $ekskul_id = $_POST['ekskul_id'] ?? '';
        $tahun_ajaran_id = $_POST['tahun_ajaran_id'] ?? '';
        $semester = $_POST['semester'] ?? 'Ganjil';
        $new_state = isset($_POST['lock']) ? (int)$_POST['lock'] : 0;

        if (empty($ekskul_id) || empty($tahun_ajaran_id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO kunci_ekskul (id, tenant_id, ekskul_id, tahun_ajaran_id, semester, kunci_nilai)
            VALUES (UUID(), :tenant_id, :ekskul_id, :ta_id, :semester, :kunci)
            ON DUPLICATE KEY UPDATE kunci_nilai = VALUES(kunci_nilai)
        ");
        $stmt->execute([
            'tenant_id' => $tenant_id,
            'ekskul_id' => $ekskul_id,
            'ta_id' => $tahun_ajaran_id,
            'semester' => $semester,
            'kunci' => $new_state
        ]);

        $lockLabel = $new_state ? 'Penilaian berhasil dikunci.' : 'Kunci penilaian berhasil dibuka.';
        $successMsg = urlencode($lockLabel);
        header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=nilai&ekskul_id={$ekskul_id}&semester={$semester}&success={$successMsg}" . ($role === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
        exit;
    }

    /**
     * Ekspor Penilaian & Presensi ke Excel (xlsx)
     */
    public function exportGrades() {
        $tenant_id = $_SESSION['tenant_id'];
        $role = $_SESSION['role_name'] ?? '';
        if ($role === 'super_admin' && isset($_GET['tenant_id'])) {
            $tenant_id = $_GET['tenant_id'];
        }

        $ekskul_id = $_GET['ekskul_id'] ?? '';
        $semester = $_GET['semester'] ?? 'Ganjil';
        $tahun_ajaran_id = $_GET['tahun_ajaran_id'] ?? '';

        if (empty($ekskul_id) || empty($tahun_ajaran_id)) {
            die("Parameter ekskul_id dan tahun_ajaran_id wajib diisi.");
        }

        $db = \App\Config\Database::getConnection();

        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isAdminOrKesiswaan = in_array('super_admin', $roles, true) || in_array('operator_sekolah', $roles, true) || in_array('kesiswaan', $roles, true);

        if (!$isAdminOrKesiswaan) {
            $stmtCheckPembina = $db->prepare("SELECT COUNT(*) FROM master_ekskul WHERE id = ? AND pembina_id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtCheckPembina->execute([$ekskul_id, $_SESSION['user_id'], $tenant_id]);
            if ((int)$stmtCheckPembina->fetchColumn() === 0) {
                die("Anda tidak memiliki hak akses untuk mengunduh nilai ekstrakurikuler ini.");
            }
        }

        // Get Ekskul name
        $stmtE = $db->prepare("SELECT nama_ekskul FROM master_ekskul WHERE id = ?");
        $stmtE->execute([$ekskul_id]);
        $ekskul_nama = $stmtE->fetchColumn() ?: 'Ekskul';

        // Get TA name
        $stmtTa = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ?");
        $stmtTa->execute([$tahun_ajaran_id]);
        $ta_name = $stmtTa->fetchColumn() ?: 'TA';

        // Fetch students and grades
        $stmtNilai = $db->prepare("
            SELECT 
                s.nisn, 
                s.nama_lengkap, 
                k.nama_kelas,
                ne.poin,
                ne.nilai,
                ne.deskripsi,
                ne.sakit,
                ne.izin,
                ne.alfa
            FROM anggota_ekskul ae
            JOIN siswa s ON ae.siswa_id = s.id
            LEFT JOIN kelas k ON s.id_kelas = k.id
            LEFT JOIN nilai_ekskul ne ON (
                ae.ekskul_id = ne.ekskul_id 
                AND ae.siswa_id = ne.siswa_id 
                AND ae.tahun_ajaran_id = ne.tahun_ajaran_id 
                AND ae.semester = ne.semester
            )
            WHERE ae.ekskul_id = ? 
              AND ae.tahun_ajaran_id = ? 
              AND ae.semester = ? 
              AND ae.tenant_id = ?
              AND s.deleted_at IS NULL
            ORDER BY s.nama_lengkap ASC
        ");
        $stmtNilai->execute([$ekskul_id, $tahun_ajaran_id, $semester, $tenant_id]);
        $list = $stmtNilai->fetchAll(PDO::FETCH_ASSOC);

        $excelData = [];
        $excelData[] = ['FORMAT PENILAIAN DAN PRESENSI EKSTRAKURIKULER'];
        $excelData[] = ['Nama Ekskul:', $ekskul_nama];
        $excelData[] = ['Semester:', $semester];
        $excelData[] = ['Tahun Ajaran:', $ta_name];
        $excelData[] = []; // Empty row separator
        
        $excelData[] = [
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Poin (1-100)',
            'Predikat (A/B/C/D)',
            'Catatan Deskripsi Penilaian',
            'Sakit',
            'Izin',
            'Alfa'
        ];

        foreach ($list as $row) {
            $excelData[] = [
                (string)$row['nisn'],
                (string)$row['nama_lengkap'],
                (string)($row['nama_kelas'] ?? 'Tanpa Kelas'),
                $row['poin'] !== null ? (int)$row['poin'] : '',
                (string)($row['nilai'] ?? 'B'),
                (string)($row['deskripsi'] ?? ''),
                (int)($row['sakit'] ?? 0),
                (int)($row['izin'] ?? 0),
                (int)($row['alfa'] ?? 0)
            ];
        }

        $cleanEkskulName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $ekskul_nama);
        $filename = "format_nilai_{$cleanEkskulName}_{$semester}_{$tahun_ajaran_id}.xlsx";

        \Shuchkin\SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
        exit;
    }

    /**
     * Impor Penilaian & Presensi dari Excel (xlsx)
     */
    public function importGrades() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'];
        $role = $_SESSION['role_name'] ?? '';
        if ($role === 'super_admin') {
            $tenant_id = $_POST['tenant_id'] ?? $_GET['tenant_id'] ?? '';
        }

        $ekskul_id = $_POST['ekskul_id'] ?? '';
        $tahun_ajaran_id = $_POST['tahun_ajaran_id'] ?? '';
        $semester = $_POST['semester'] ?? 'Ganjil';

        if (empty($ekskul_id) || empty($tahun_ajaran_id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
            exit;
        }

        $db = \App\Config\Database::getConnection();

        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isAdminOrKesiswaan = in_array('super_admin', $roles, true) || in_array('operator_sekolah', $roles, true) || in_array('kesiswaan', $roles, true);

        if (!$isAdminOrKesiswaan) {
            $stmtCheckPembina = $db->prepare("SELECT COUNT(*) FROM master_ekskul WHERE id = ? AND pembina_id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtCheckPembina->execute([$ekskul_id, $_SESSION['user_id'], $tenant_id]);
            if ((int)$stmtCheckPembina->fetchColumn() === 0) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengelola ekstrakurikuler ini.']);
                exit;
            }
        }

        // Check if grades lock is active
        $stmtKunci = $db->prepare("SELECT kunci_nilai FROM kunci_ekskul WHERE ekskul_id = ? AND tahun_ajaran_id = ? AND semester = ?");
        $stmtKunci->execute([$ekskul_id, $tahun_ajaran_id, $semester]);
        if ($stmtKunci->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Penilaian & Presensi untuk semester ini telah dikunci.']);
            exit;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah berkas. Silakan coba lagi.']);
            exit;
        }

        $fileTmp = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'xlsx') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Format berkas tidak valid. Harap gunakan berkas .xlsx']);
            exit;
        }

        $xlsx = \Shuchkin\SimpleXLSX::parse($fileTmp);
        if (!$xlsx) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Gagal membaca berkas Excel: ' . \Shuchkin\SimpleXLSX::parseError()]);
            exit;
        }

        $rows = $xlsx->rows();
        if (empty($rows)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Berkas Excel kosong atau tidak terbaca.']);
            exit;
        }

        $headerRowIdx = -1;
        $nisnIdx = -1;
        $poinIdx = -1;
        $nilaiIdx = -1;
        $descIdx = -1;
        $sakitIdx = -1;
        $izinIdx = -1;
        $alfaIdx = -1;

        foreach ($rows as $rNum => $row) {
            if (empty(array_filter($row))) continue;
            
            if (isset($row[0])) {
                $row[0] = preg_replace('/^[\x{FEFF}\x{200B}]+/u', '', $row[0]);
            }

            $normalized = array_map(function($cell) {
                return strtolower(trim((string)$cell));
            }, $row);

            $tempNisn = -1;
            $tempPoin = -1;
            $tempNilai = -1;
            $tempDesc = -1;
            $tempSakit = -1;
            $tempIzin = -1;
            $tempAlfa = -1;

            foreach ($normalized as $idx => $cell) {
                if (strpos($cell, 'nisn') !== false) {
                    $tempNisn = $idx;
                } elseif (strpos($cell, 'poin') !== false || strpos($cell, 'nilai (1-100)') !== false || strpos($cell, 'skor') !== false) {
                    $tempPoin = $idx;
                } elseif (strpos($cell, 'predikat') !== false || strpos($cell, 'nilai (a/b/c/d)') !== false) {
                    $tempNilai = $idx;
                } elseif (strpos($cell, 'deskripsi') !== false || strpos($cell, 'catatan') !== false || strpos($cell, 'keterangan') !== false) {
                    $tempDesc = $idx;
                } elseif (strpos($cell, 'sakit') !== false) {
                    $tempSakit = $idx;
                } elseif (strpos($cell, 'izin') !== false) {
                    $tempIzin = $idx;
                } elseif (strpos($cell, 'alfa') !== false || strpos($cell, 'tanpa keterangan') !== false) {
                    $tempAlfa = $idx;
                }
            }

            if ($tempNisn !== -1 && $tempNilai !== -1 && $tempDesc !== -1) {
                $headerRowIdx = $rNum;
                $nisnIdx = $tempNisn;
                $poinIdx = $tempPoin;
                $nilaiIdx = $tempNilai;
                $descIdx = $tempDesc;
                $sakitIdx = $tempSakit;
                $izinIdx = $tempIzin;
                $alfaIdx = $tempAlfa;
                break;
            }

            if ($rNum > 10) break;
        }

        if ($headerRowIdx === -1) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Format header Excel tidak sesuai. Pastikan kolom NISN, Predikat (A/B/C/D), dan Catatan Deskripsi Penilaian tersedia.']);
            exit;
        }

        $db->beginTransaction();
        try {
            $stmtGetSiswa = $db->prepare("
                SELECT s.id as siswa_id 
                FROM anggota_ekskul ae
                JOIN siswa s ON ae.siswa_id = s.id
                WHERE s.nisn = ? AND ae.ekskul_id = ? AND ae.tahun_ajaran_id = ? AND ae.semester = ? AND ae.tenant_id = ?
                LIMIT 1
            ");

            $stmtUpsert = $db->prepare("
                INSERT INTO nilai_ekskul (id, tenant_id, ekskul_id, siswa_id, tahun_ajaran_id, semester, poin, nilai, deskripsi, sakit, izin, alfa)
                VALUES (UUID(), :tenant_id, :ekskul_id, :siswa_id, :tahun_ajaran_id, :semester, :poin, :nilai, :deskripsi, :sakit, :izin, :alfa)
                ON DUPLICATE KEY UPDATE 
                    poin = VALUES(poin),
                    nilai = VALUES(nilai),
                    deskripsi = VALUES(deskripsi),
                    sakit = VALUES(sakit),
                    izin = VALUES(izin),
                    alfa = VALUES(alfa)
            ");

            $successCount = 0;
            $skippedCount = 0;

            for ($i = $headerRowIdx + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) continue;

                $nisn = trim((string)($row[$nisnIdx] ?? ''));
                if (empty($nisn)) {
                    $skippedCount++;
                    continue;
                }

                $stmtGetSiswa->execute([$nisn, $ekskul_id, $tahun_ajaran_id, $semester, $tenant_id]);
                $siswa_id = $stmtGetSiswa->fetchColumn();

                if (!$siswa_id) {
                    $skippedCount++;
                    continue;
                }

                $rawPoin = $poinIdx !== -1 && isset($row[$poinIdx]) ? trim((string)$row[$poinIdx]) : '';
                $poin = $rawPoin !== '' ? (int)$rawPoin : null;
                if ($poin !== null && ($poin < 0 || $poin > 100)) {
                    $poin = null;
                }

                $nilai = strtoupper(trim((string)($row[$nilaiIdx] ?? 'B')));
                if (strlen($nilai) > 1) {
                    $nilai = substr($nilai, 0, 1);
                }
                if (!in_array($nilai, ['A', 'B', 'C', 'D'], true)) {
                    $nilai = 'B';
                }

                $deskripsi = trim((string)($row[$descIdx] ?? ''));
                $sakit = $sakitIdx !== -1 && isset($row[$sakitIdx]) ? (int)$row[$sakitIdx] : 0;
                $izin = $izinIdx !== -1 && isset($row[$izinIdx]) ? (int)$row[$izinIdx] : 0;
                $alfa = $alfaIdx !== -1 && isset($row[$alfaIdx]) ? (int)$row[$alfaIdx] : 0;

                $stmtUpsert->execute([
                    'tenant_id' => $tenant_id,
                    'ekskul_id' => $ekskul_id,
                    'siswa_id' => $siswa_id,
                    'tahun_ajaran_id' => $tahun_ajaran_id,
                    'semester' => $semester,
                    'poin' => $poin,
                    'nilai' => $nilai,
                    'deskripsi' => $deskripsi,
                    'sakit' => $sakit,
                    'izin' => $izin,
                    'alfa' => $alfa
                ]);
                $successCount++;
            }

            $db->commit();

            $importMsg = "Berhasil mengimpor penilaian: $successCount siswa diperbarui." . ($skippedCount > 0 ? " ($skippedCount baris dilewati karena siswa tidak terdaftar sebagai anggota ekskul ini)." : "");
            $successMsg = urlencode($importMsg);
            header("Location: /SINTA-SaaS/kesiswaan/ekskul?tab=nilai&ekskul_id={$ekskul_id}&semester={$semester}&success={$successMsg}" . ($role === 'super_admin' ? "&tenant_id={$tenant_id}" : ""));
            exit;

        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses data Excel: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Ekspor Rekap Daftar Anggota per Ekskul ke Excel
     */
    public function exportMembers() {
        $tenant_id = $_SESSION['tenant_id'];
        $role = $_SESSION['role_name'] ?? '';
        if ($role === 'super_admin' && isset($_GET['tenant_id'])) {
            $tenant_id = $_GET['tenant_id'];
        }

        $ekskul_id        = $_GET['ekskul_id'] ?? '';
        $semester         = $_GET['semester']  ?? 'Ganjil';
        $tahun_ajaran_id  = $_GET['tahun_ajaran_id'] ?? '';

        if (empty($ekskul_id) || empty($tahun_ajaran_id)) {
            die('Parameter ekskul_id dan tahun_ajaran_id wajib diisi.');
        }

        $db = \App\Config\Database::getConnection();

        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $isAdminOrKesiswaan = in_array('super_admin', $roles, true) || in_array('operator_sekolah', $roles, true) || in_array('kesiswaan', $roles, true);

        if (!$isAdminOrKesiswaan) {
            $stmtCheckPembina = $db->prepare('SELECT COUNT(*) FROM master_ekskul WHERE id = ? AND pembina_id = ? AND tenant_id = ? AND deleted_at IS NULL');
            $stmtCheckPembina->execute([$ekskul_id, $_SESSION['user_id'], $tenant_id]);
            if ((int)$stmtCheckPembina->fetchColumn() === 0) {
                die('Anda tidak memiliki hak akses untuk mengunduh rekap anggota ekstrakurikuler ini.');
            }
        }

        // Get Ekskul info
        $stmtE = $db->prepare('SELECT e.nama_ekskul, u.nama_lengkap as nama_pembina FROM master_ekskul e LEFT JOIN users u ON e.pembina_id = u.id WHERE e.id = ?');
        $stmtE->execute([$ekskul_id]);
        $ekskulInfo = $stmtE->fetch(PDO::FETCH_ASSOC);
        $ekskul_nama  = $ekskulInfo['nama_ekskul']   ?? 'Ekskul';
        $nama_pembina = $ekskulInfo['nama_pembina']  ?? '-';

        // Get TA name
        $stmtTa = $db->prepare('SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ?');
        $stmtTa->execute([$tahun_ajaran_id]);
        $ta_name = $stmtTa->fetchColumn() ?: 'TA';

        // Get school name
        $stmtSchool = $db->prepare('SELECT nama_sekolah FROM tenants WHERE id = ?');
        $stmtSchool->execute([$tenant_id]);
        $nama_sekolah = $stmtSchool->fetchColumn() ?: '-';

        // Fetch members
        $stmtMembers = $db->prepare('
            SELECT s.nisn, s.nama_lengkap, k.nama_kelas, s.jenis_kelamin
            FROM anggota_ekskul ae
            JOIN siswa s ON ae.siswa_id = s.id
            LEFT JOIN kelas k ON s.id_kelas = k.id
            WHERE ae.ekskul_id = ? AND ae.tahun_ajaran_id = ? AND ae.semester = ? AND ae.tenant_id = ? AND s.deleted_at IS NULL
            ORDER BY k.nama_kelas ASC, s.nama_lengkap ASC
        ');
        $stmtMembers->execute([$ekskul_id, $tahun_ajaran_id, $semester, $tenant_id]);
        $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

        // Build Excel data
        $excelData = [];
        $excelData[] = ['REKAP DAFTAR ANGGOTA EKSTRAKURIKULER'];
        $excelData[] = ['Sekolah:', $nama_sekolah];
        $excelData[] = ['Nama Ekskul:', $ekskul_nama];
        $excelData[] = ['Guru Pembina:', $nama_pembina];
        $excelData[] = ['Semester:', $semester];
        $excelData[] = ['Tahun Ajaran:', $ta_name];
        $excelData[] = ['Total Anggota:', count($members) . ' siswa'];
        $excelData[] = []; // separator
        $excelData[] = ['No', 'NISN', 'Nama Lengkap', 'Kelas', 'Jenis Kelamin'];

        $no = 1;
        foreach ($members as $row) {
            $excelData[] = [
                $no++,
                (string)$row['nisn'],
                (string)$row['nama_lengkap'],
                (string)($row['nama_kelas'] ?? 'Tanpa Kelas'),
                (string)($row['jenis_kelamin'] ?? '-'),
            ];
        }

        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $ekskul_nama);
        $filename  = "rekap_anggota_{$cleanName}_{$semester}_{$ta_name}.xlsx";

        \Shuchkin\SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
        exit;
    }
}
