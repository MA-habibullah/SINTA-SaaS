<?php

namespace App\Modules\Core\Controllers;

use App\Core\BaseController;
use App\Core\FileStorage;
use App\Core\SessionManager;
use App\Config\Database;
use PDO;

class BantuanModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    public function index(): void {
        $role = $_SESSION['role_name'] ?? '';
        $db = Database::getConnection();

        try {
            $categories = $db->query("SELECT id, nama_kategori FROM core.ticket_categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $categories = [
                ['id' => 1, 'nama_kategori' => 'Teknis / Sistem'],
                ['id' => 2, 'nama_kategori' => 'Akun & Akses'],
                ['id' => 3, 'nama_kategori' => 'Keuangan & SPP'],
                ['id' => 4, 'nama_kategori' => 'Pertanyaan Umum']
            ];
        }

        $data = [
            'title' => 'Pusat Bantuan & Tiket',
            'user_role' => $role,
            'categories' => $categories
        ];

        if ($role === 'super_admin') {
            $this->render('core/bantuan_admin', $data);
        } else {
            $this->render('core/bantuan_user', $data);
        }
    }

    public function apiCreateTicket(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, null, 'Metode request tidak diizinkan.', 405);
        }

        $judul = trim($_POST['judul'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $urgensi = trim($_POST['urgensi'] ?? 'Sedang');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $lastUrl = trim($_POST['last_url'] ?? '');

        if (empty($judul) || empty($deskripsi) || !$categoryId) {
            $this->jsonResponse(false, null, 'Judul, kategori, dan deskripsi wajib diisi.', 422);
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $db = Database::getConnection();
        $stmtCat = $db->prepare("SELECT sla_hours FROM core.ticket_categories WHERE id = ?");
        $stmtCat->execute([$categoryId]);
        $slaHours = (int)$stmtCat->fetchColumn() ?: 48;
        
        if ($urgensi === 'Kritis') $slaHours = 2;
        elseif ($urgensi === 'Tinggi') $slaHours = 24;

        $slaDeadline = date('Y-m-d H:i:s', strtotime("+$slaHours hours"));

        $lampiranPath = null;
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['lampiran'];

            if ($file['size'] > 3 * 1024 * 1024) {
                $this->jsonResponse(false, null, 'Ukuran file maksimal 3 MB.', 422);
            }

            // Upload ke: uploads/tickets/{tenant_id}/{user_id}/{sha1}.ext
            $newPath = FileStorage::store(
                $file['tmp_name'],
                'uploads/tickets',
                $tenantId ?? 'global',
                $userId   ?? 'anonymous',
                'image_only'
            );

            if ($newPath === null) {
                $this->jsonResponse(false, null, 'Format file tidak diizinkan. Hanya menerima PNG/JPG.', 422);
            }

            // Path relatif untuk disimpan di DB (strip storage/app/public/)
            $lampiranPath = str_replace('storage/app/public/', '', $newPath);
        }

        try {
            $ticketId = $this->generateUuid();
            $stmt = $db->prepare("
                INSERT INTO core.tickets (id, tenant_id, user_id, category_id, judul, deskripsi, urgensi, lampiran, user_agent, last_url, sla_deadline, user_unread, admin_unread)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, false, true)
            ");
            $stmt->execute([
                $ticketId, $tenantId, $userId, $categoryId, $judul, $deskripsi, $urgensi, $lampiranPath, $userAgent, $lastUrl, $slaDeadline
            ]);

            $this->jsonResponse(true, ['message' => 'Tiket berhasil dibuat.', 'ticket_id' => $ticketId]);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, 'Gagal membuat tiket: ' . $e->getMessage(), 500);
        }
    }

    public function apiListTickets(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $db = Database::getConnection();

        $status = $_GET['status'] ?? '';
        $category = $_GET['category'] ?? '';

        $query = "
            SELECT t.id, t.judul, t.urgensi, t.status, t.created_at, t.sla_deadline, 
                   t.user_unread, t.admin_unread,
                   tc.nama_kategori, u.nama_lengkap as nama_pelapor, ten.nama_sekolah
            FROM core.tickets t
            JOIN core.ticket_categories tc ON t.category_id = tc.id
            JOIN core.users u ON t.user_id = u.id
            LEFT JOIN core.tenants ten ON t.tenant_id = ten.id
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

            foreach ($tickets as &$ticket) {
                $ticket['is_overdue'] = false;
                if ($ticket['status'] !== 'Selesai' && $ticket['status'] !== 'Batal') {
                    $ticket['is_overdue'] = (time() > strtotime($ticket['sla_deadline']));
                }
            }

            $this->jsonResponse(true, $tickets);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetTicketDetail(): void {
        $ticketId = $_GET['id'] ?? '';
        if (empty($ticketId)) {
            $this->jsonResponse(false, null, 'ID Tiket tidak valid.', 400);
        }

        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $db = Database::getConnection();

        $queryTicket = "
            SELECT t.*, tc.nama_kategori, u.nama_lengkap as nama_pelapor, ten.nama_sekolah
            FROM core.tickets t
            JOIN core.ticket_categories tc ON t.category_id = tc.id
            JOIN core.users u ON t.user_id = u.id
            LEFT JOIN core.tenants ten ON t.tenant_id = ten.id
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
                $this->jsonResponse(false, null, 'Tiket tidak ditemukan atau Anda tidak memiliki akses.', 403);
            }

            $ticket['is_overdue'] = false;
            if ($ticket['status'] !== 'Selesai' && $ticket['status'] !== 'Batal') {
                $ticket['is_overdue'] = (time() > strtotime($ticket['sla_deadline']));
            }

            if ($role === 'super_admin') {
                if ($ticket['admin_unread']) {
                    $db->prepare("UPDATE core.tickets SET admin_unread = false WHERE id = ?")->execute([$ticketId]);
                    $ticket['admin_unread'] = false;
                }
            } else {
                if ($ticket['user_unread']) {
                    $db->prepare("UPDATE core.tickets SET user_unread = false WHERE id = ?")->execute([$ticketId]);
                    $ticket['user_unread'] = false;
                }
            }

            $stmtReplies = $db->prepare("
                SELECT r.pesan, r.is_superadmin, r.created_at, u.nama_lengkap as nama_pengirim
                FROM core.ticket_replies r
                JOIN core.users u ON r.user_id = u.id
                WHERE r.ticket_id = ?
                ORDER BY r.created_at ASC
            ");
            $stmtReplies->execute([$ticketId]);
            $replies = $stmtReplies->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse(true, [
                'ticket' => $ticket,
                'replies' => $replies
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiReplyTicket(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, null, 'Metode request tidak diizinkan.', 405);
        }

        $input = $this->getJsonInput();
        $ticketId = $input['ticket_id'] ?? '';
        $pesan = trim($input['pesan'] ?? '');

        if (empty($ticketId) || empty($pesan)) {
            $this->jsonResponse(false, null, 'ID Tiket dan pesan wajib diisi.', 422);
        }

        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $db = Database::getConnection();

        $queryCheck = "SELECT status FROM core.tickets WHERE id = ?";
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
            $this->jsonResponse(false, null, 'Akses ditolak.', 403);
        }

        if ($status === 'Selesai' || $status === 'Batal') {
            $this->jsonResponse(false, null, 'Tiket sudah ditutup dan tidak dapat dibalas.', 422);
        }

        try {
            $replyId = $this->generateUuid();
            $isSuperAdmin = ($role === 'super_admin');
            $stmt = $db->prepare("
                INSERT INTO core.ticket_replies (id, ticket_id, user_id, is_superadmin, pesan)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$replyId, $ticketId, $userId, $isSuperAdmin ? 'true' : 'false', $pesan]);

            if ($isSuperAdmin) {
                $stmtUpdate = $db->prepare("UPDATE core.tickets SET status = 'Diproses', user_unread = true, admin_unread = false WHERE id = ?");
                $stmtUpdate->execute([$ticketId]);
            } else {
                $stmtUpdate = $db->prepare("UPDATE core.tickets SET user_unread = false, admin_unread = true WHERE id = ?");
                $stmtUpdate->execute([$ticketId]);
            }

            $this->jsonResponse(true, ['message' => 'Pesan berhasil dikirim.']);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiUpdateStatus(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, null, 'Metode request tidak diizinkan.', 405);
        }

        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $this->jsonResponse(false, null, 'Akses ditolak.', 403);
        }

        $input = $this->getJsonInput();
        $ticketId = $input['ticket_id'] ?? '';
        $status = $input['status'] ?? '';

        if (empty($ticketId) || !in_array($status, ['Menunggu', 'Diproses', 'Selesai', 'Batal'])) {
            $this->jsonResponse(false, null, 'Data input status tidak valid.', 422);
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE core.tickets SET status = ?, user_unread = true WHERE id = ?");
            $stmt->execute([$status, $ticketId]);

            $this->jsonResponse(true, ['message' => 'Status tiket berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiFaqLookup(): void {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 3) {
            $this->jsonResponse(true, []);
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT pertanyaan, jawaban 
                FROM core.ticket_faqs 
                WHERE pertanyaan ILIKE ? OR jawaban ILIKE ? 
                LIMIT 5
            ");
            $likeQuery = "%" . $q . "%";
            $stmt->execute([$likeQuery, $likeQuery]);
            $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse(true, $faqs);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetCannedResponses(): void {
        if (($_SESSION['role_name'] ?? '') !== 'super_admin') {
            $this->jsonResponse(false, null, 'Akses ditolak.', 403);
        }

        try {
            $db = Database::getConnection();
            $responses = $db->query("SELECT id, judul, konten FROM core.ticket_canned_responses ORDER BY judul ASC")->fetchAll(PDO::FETCH_ASSOC);
            $this->jsonResponse(true, $responses);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetUnreadCount(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $db = Database::getConnection();

        try {
            if ($role === 'super_admin') {
                $stmt = $db->prepare("SELECT COUNT(*) FROM core.tickets WHERE admin_unread = true");
                $stmt->execute();
            } else {
                $stmt = $db->prepare("SELECT COUNT(*) FROM core.tickets WHERE user_unread = true AND tenant_id = ? AND user_id = ?");
                $stmt->execute([$tenantId, $userId]);
            }
            $count = (int)$stmt->fetchColumn();
            $this->jsonResponse(true, ['unread_count' => $count]);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    private function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * API: GET daftar kategori tiket bantuan (untuk dropdown filter async).
     * Menggantikan pola server-side foreach ($categories) di view bantuan_admin & bantuan_user.
     */
    public function apiGetCategories(): void {
        $db = Database::getConnection();
        try {
            $list = $db->query("SELECT id, nama_kategori FROM core.ticket_categories ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $this->jsonResponse(true, $list, 'Kategori berhasil dimuat.');
        } catch (\Throwable $e) {
            // Fallback: kategori default jika tabel belum ada
            $this->jsonResponse(true, [
                ['id' => 1, 'nama_kategori' => 'Teknis / Sistem'],
                ['id' => 2, 'nama_kategori' => 'Akun & Akses'],
                ['id' => 3, 'nama_kategori' => 'Keuangan & SPP'],
                ['id' => 4, 'nama_kategori' => 'Pertanyaan Umum'],
            ]);
        }
    }
}

