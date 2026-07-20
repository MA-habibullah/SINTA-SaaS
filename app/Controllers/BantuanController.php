<?php
namespace App\Controllers;

use App\Core\SessionManager;
use App\Config\Database;
use PDO;

class BantuanController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    public function index(): void {
        $role = $_SESSION['role_name'] ?? '';
        $db = Database::getConnection();

        // Ambil kategori untuk formulir user
        $categories = $db->query("SELECT id, nama_kategori FROM ticket_categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'title' => 'Pusat Bantuan & Tiket',
            'user_role' => $role,
            'categories' => $categories
        ];

        if ($role === 'super_admin') {
            $this->render('bantuan_admin', $data);
        } else {
            $this->render('bantuan_user', $data);
        }
    }

    // API: Buat Tiket Baru
    public function apiCreateTicket(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak diizinkan.'], 405);
        }

        $judul = trim($_POST['judul'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $urgensi = trim($_POST['urgensi'] ?? 'Sedang');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $lastUrl = trim($_POST['last_url'] ?? '');

        if (empty($judul) || empty($deskripsi) || !$categoryId) {
            $this->jsonResponse(['error' => 'Judul, kategori, dan deskripsi wajib diisi.'], 422);
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Hitung SLA Deadline berdasarkan Kategori & Urgensi
        $db = Database::getConnection();
        $stmtCat = $db->prepare("SELECT sla_hours FROM ticket_categories WHERE id = ?");
        $stmtCat->execute([$categoryId]);
        $slaHours = (int)$stmtCat->fetchColumn() ?: 48;
        
        // Sesuaikan SLA berdasarkan Urgensi
        if ($urgensi === 'Kritis') $slaHours = 2;
        elseif ($urgensi === 'Tinggi') $slaHours = 24;

        $slaDeadline = date('Y-m-d H:i:s', strtotime("+$slaHours hours"));

        // Proses Upload Lampiran (Attachment)
        $lampiranPath = null;
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['lampiran'];
            
            // 1. Validasi Ukuran (Max 3MB)
            if ($file['size'] > 3 * 1024 * 1024) {
                $this->jsonResponse(['error' => 'Ukuran file maksimal 3 MB.'], 422);
            }

            // 2. Validasi MIME Type Riil
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMime = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($mime, $allowedMime)) {
                $this->jsonResponse(['error' => 'Format file tidak diizinkan. Hanya menerima PNG/JPG.'], 422);
            }

            // Sanitize file name
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = 'ticket_' . bin2hex(random_bytes(8)) . '.' . $ext;
            
            $targetDir = __DIR__ . '/../../public/uploads/tickets/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $targetDir . $newFileName)) {
                $lampiranPath = '/uploads/tickets/' . $newFileName;
            }
        }

        try {
            $ticketId = $this->generateUuid();
            $stmt = $db->prepare("
                INSERT INTO tickets (id, tenant_id, user_id, category_id, judul, deskripsi, urgensi, lampiran, user_agent, last_url, sla_deadline, user_unread, admin_unread)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)
            ");
            $stmt->execute([
                $ticketId, $tenantId, $userId, $categoryId, $judul, $deskripsi, $urgensi, $lampiranPath, $userAgent, $lastUrl, $slaDeadline
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Tiket berhasil dibuat.', 'ticket_id' => $ticketId]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => 'Gagal membuat tiket: ' . $e->getMessage()], 500);
        }
    }

    // API: Mengambil Daftar Tiket (Pencegahan IDOR)
    public function apiListTickets(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $db = Database::getConnection();

        // Filter parameters
        $status = $_GET['status'] ?? '';
        $category = $_GET['category'] ?? '';

        $query = "
            SELECT t.id, t.judul, t.urgensi, t.status, t.created_at, t.sla_deadline, 
                   t.user_unread, t.admin_unread,
                   tc.nama_kategori, u.nama_lengkap as nama_pelapor, ten.nama_sekolah
            FROM tickets t
            JOIN ticket_categories tc ON t.category_id = tc.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN tenants ten ON t.tenant_id = ten.id
            WHERE 1=1
        ";

        $params = [];
        if ($role !== 'super_admin') {
            $query .= " AND t.tenant_id = ? AND t.user_id = ?";
            $params[] = $tenantId;
            $params[] = $userId;
        }

        if (!empty($status)) {
            $query .= " AND t.status = ?";
            $params[] = $status;
        }
        if (!empty($category)) {
            $query .= " AND t.category_id = ?";
            $params[] = (int)$category;
        }

        $query .= " ORDER BY t.created_at DESC";

        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Hitung sisa waktu SLA
            foreach ($tickets as &$ticket) {
                $ticket['is_overdue'] = false;
                if ($ticket['status'] !== 'Selesai' && $ticket['status'] !== 'Batal') {
                    $ticket['is_overdue'] = (time() > strtotime($ticket['sla_deadline']));
                }
            }

            $this->jsonResponse(['success' => true, 'data' => $tickets]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // API: Mengambil Detail & Thread Percakapan Tiket
    public function apiGetTicketDetail(): void {
        $ticketId = $_GET['id'] ?? '';
        if (empty($ticketId)) {
            $this->jsonResponse(['error' => 'ID Tiket tidak valid.'], 400);
        }

        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $db = Database::getConnection();

        // 1. Ambil Data Tiket Utama
        $queryTicket = "
            SELECT t.*, tc.nama_kategori, u.nama_lengkap as nama_pelapor, ten.nama_sekolah
            FROM tickets t
            JOIN ticket_categories tc ON t.category_id = tc.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN tenants ten ON t.tenant_id = ten.id
            WHERE t.id = ?
        ";

        $params = [$ticketId];
        if ($role !== 'super_admin') {
            $queryTicket .= " AND t.tenant_id = ? AND t.user_id = ?";
            $params[] = $tenantId;
            $params[] = $userId;
        }

        try {
            $stmt = $db->prepare($queryTicket);
            $stmt->execute($params);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                $this->jsonResponse(['error' => 'Tiket tidak ditemukan atau Anda tidak memiliki akses.'], 403);
            }

            // Hitung sisa waktu SLA
            $ticket['is_overdue'] = false;
            if ($ticket['status'] !== 'Selesai' && $ticket['status'] !== 'Batal') {
                $ticket['is_overdue'] = (time() > strtotime($ticket['sla_deadline']));
            }

            // Jika dibaca, tandai unread = 0 secara dinamis
            if ($role === 'super_admin') {
                if ($ticket['admin_unread']) {
                    $db->prepare("UPDATE tickets SET admin_unread = 0 WHERE id = ?")->execute([$ticketId]);
                    $ticket['admin_unread'] = 0;
                }
            } else {
                if ($ticket['user_unread']) {
                    $db->prepare("UPDATE tickets SET user_unread = 0 WHERE id = ?")->execute([$ticketId]);
                    $ticket['user_unread'] = 0;
                }
            }

            // 2. Ambil Thread Percakapan / Balasan
            $stmtReplies = $db->prepare("
                SELECT r.pesan, r.is_superadmin, r.created_at, u.nama_lengkap as nama_pengirim
                FROM ticket_replies r
                JOIN users u ON r.user_id = u.id
                WHERE r.ticket_id = ?
                ORDER BY r.created_at ASC
            ");
            $stmtReplies->execute([$ticketId]);
            $replies = $stmtReplies->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'ticket' => $ticket,
                'replies' => $replies
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // API: Balas Tiket (Thread)
    public function apiReplyTicket(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak diizinkan.'], 405);
        }

        $input = $this->getJsonInput();
        $ticketId = $input['ticket_id'] ?? '';
        $pesan = trim($input['pesan'] ?? '');

        if (empty($ticketId) || empty($pesan)) {
            $this->jsonResponse(['error' => 'ID Tiket dan pesan wajib diisi.'], 422);
        }

        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $db = Database::getConnection();

        // Validasi Kepemilikan Tiket (Cegah IDOR)
        $queryCheck = "SELECT status FROM tickets WHERE id = ?";
        $checkParams = [$ticketId];
        if ($role !== 'super_admin') {
            $queryCheck .= " AND tenant_id = ? AND user_id = ?";
            $checkParams[] = $tenantId;
            $checkParams[] = $userId;
        }

        $stmtCheck = $db->prepare($queryCheck);
        $stmtCheck->execute($checkParams);
        $status = $stmtCheck->fetchColumn();

        if ($status === false) {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
        }

        if ($status === 'Selesai' || $status === 'Batal') {
            $this->jsonResponse(['error' => 'Tiket sudah ditutup dan tidak dapat dibalas.'], 422);
        }

        try {
            $replyId = $this->generateUuid();
            $isSuperAdmin = ($role === 'super_admin') ? 1 : 0;
            $stmt = $db->prepare("
                INSERT INTO ticket_replies (id, ticket_id, user_id, is_superadmin, pesan)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$replyId, $ticketId, $userId, $isSuperAdmin, $pesan]);

            // Tandai unread flag dan update status jika admin membalas
            if ($isSuperAdmin) {
                $stmtUpdate = $db->prepare("UPDATE tickets SET status = 'Diproses', user_unread = 1, admin_unread = 0 WHERE id = ?");
                $stmtUpdate->execute([$ticketId]);
            } else {
                $stmtUpdate = $db->prepare("UPDATE tickets SET user_unread = 0, admin_unread = 1 WHERE id = ?");
                $stmtUpdate->execute([$ticketId]);
            }

            $this->jsonResponse(['success' => true, 'message' => 'Pesan berhasil dikirim.']);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // API: Update Status (Super Admin Only)
    public function apiUpdateStatus(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak diizinkan.'], 405);
        }

        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
        }

        $input = $this->getJsonInput();
        $ticketId = $input['ticket_id'] ?? '';
        $status = $input['status'] ?? '';

        if (empty($ticketId) || !in_array($status, ['Menunggu', 'Diproses', 'Selesai', 'Batal'])) {
            $this->jsonResponse(['error' => 'Data input status tidak valid.'], 422);
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE tickets SET status = ?, user_unread = 1 WHERE id = ?");
            $stmt->execute([$status, $ticketId]);

            $this->jsonResponse(['success' => true, 'message' => 'Status tiket berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // API: Lookup FAQ Pintar (Real-time pencarian judul tiket)
    public function apiFaqLookup(): void {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 3) {
            $this->jsonResponse(['success' => true, 'data' => []]);
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT pertanyaan, jawaban 
                FROM ticket_faqs 
                WHERE pertanyaan LIKE ? OR jawaban LIKE ? 
                LIMIT 5
            ");
            $likeQuery = "%" . $q . "%";
            $stmt->execute([$likeQuery, $likeQuery]);
            $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'data' => $faqs]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // API: Get Canned Responses (Untuk Super Admin)
    public function apiGetCannedResponses(): void {
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
        }

        try {
            $db = Database::getConnection();
            $responses = $db->query("SELECT id, judul, konten FROM ticket_canned_responses ORDER BY judul ASC")->fetchAll(PDO::FETCH_ASSOC);
            $this->jsonResponse(['success' => true, 'data' => $responses]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // API: Get Jumlah Tiket yang Memiliki Pesan Baru (Untuk Sidebar Badge)
    public function apiGetUnreadCount(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $db = Database::getConnection();

        try {
            if ($role === 'super_admin') {
                $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE admin_unread = 1");
                $stmt->execute();
            } else {
                $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE user_unread = 1 AND tenant_id = ? AND user_id = ?");
                $stmt->execute([$tenantId, $userId]);
            }
            $count = (int)$stmt->fetchColumn();
            $this->jsonResponse(['success' => true, 'unread_count' => $count]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function generateUuid(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
