<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class PembinaanController extends BaseController {
    public function index() {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        // Cek jika ini adalah Kepala Sekolah / Admin
        // Jika Guru biasa, arahkan ke halaman Refleksi Mandiri (belum dibuat)
        
        $roleName = $_SESSION['role_name'] ?? '';
        $isSuperAdmin = ($roleName === 'super_admin');
        
        $selectedTenantId = $_GET['tenant_id'] ?? null;
        
        if ($isSuperAdmin && !$selectedTenantId) {
            $stmt = $db->query("SELECT id, nama_sekolah, npsn FROM tenants ORDER BY nama_sekolah ASC");
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->render('sekolah/pembinaan_pilih_tenant', [
                'pageTitle' => 'Pilih Sekolah (Tenant)',
                'role' => $roleName,
                'tenants' => $tenants
            ]);
            return;
        }
        
        $effectiveTenantId = $isSuperAdmin ? $selectedTenantId : $tenantId;
        
        // 1. Ambil Statistik Traffic Light
        $stmt = $db->prepare("SELECT status_kasus, COUNT(*) as total FROM guru_monitoring WHERE tenant_id = ? GROUP BY status_kasus");
        $stmt->execute([$effectiveTenantId]);
        $statsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'Merah' => 0,
            'Kuning' => 0,
            'Hijau' => 0
        ];
        foreach ($statsRaw as $row) {
            $stats[$row['status_kasus']] = $row['total'];
        }
        
        // 2. Ambil Daftar Guru yang Butuh Pendampingan (Merah & Kuning)
        $stmt = $db->prepare("
            SELECT gm.*, u.nama_lengkap as nama_guru, 
                   sm.id as sesi_id, sm.status_sesi
            FROM guru_monitoring gm 
            JOIN users u ON gm.guru_id = u.id 
            LEFT JOIN sesi_mentoring sm ON gm.id = sm.monitoring_id
            WHERE gm.tenant_id = ? AND gm.status_kasus IN ('Merah', 'Kuning')
            ORDER BY gm.status_kasus ASC, gm.created_at DESC
        ");
        $stmt->execute([$effectiveTenantId]);
        $kasusAktif = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Ambil Daftar Riwayat Kasus Selesai (Hijau)
        $stmt = $db->prepare("
            SELECT gm.*, u.nama_lengkap as nama_guru, 
                   sm.id as sesi_id, sm.status_sesi
            FROM guru_monitoring gm 
            JOIN users u ON gm.guru_id = u.id 
            LEFT JOIN sesi_mentoring sm ON gm.id = sm.monitoring_id
            WHERE gm.tenant_id = ? AND gm.status_kasus = 'Hijau'
            ORDER BY gm.updated_at DESC
        ");
        $stmt->execute([$effectiveTenantId]);
        $riwayatKasus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 4. Ambil Daftar Guru untuk Dropdown Form Input Manual
        $stmt = $db->prepare("
            SELECT u.id, u.nama_lengkap 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.tenant_id = ? AND r.nama_role LIKE '%guru%' 
            ORDER BY u.nama_lengkap ASC
        ");
        $stmt->execute([$effectiveTenantId]);
        $guruList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Render view for the dashboard 3M
        $this->render('sekolah/pembinaan_index', [
            'pageTitle' => 'Dasbor Pembinaan & Supervisi (3M)',
            'role' => $roleName,
            'stats' => $stats,
            'kasusAktif' => $kasusAktif,
            'riwayatKasus' => $riwayatKasus,
            'guruList' => $guruList
        ]);
    }
    
    public function store() {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $guru_id = $_POST['guru_id'] ?? '';
            $kategori_masalah = $_POST['kategori_masalah'] ?? '';
            $sumber_deteksi = $_POST['sumber_deteksi'] ?? 'Manual Atasan';
            $deskripsi_kasus = $_POST['deskripsi_kasus'] ?? '';
            
            if (!$guru_id || !$kategori_masalah || !$deskripsi_kasus) {
                $_SESSION['flash_error'] = 'Harap isi semua kolom wajib!';
                header('Location: /SINTA-SaaS/pembinaan');
                exit;
            }
            
            $postedTenantId = $_POST['tenant_id'] ?? null;
            $lampiran_path = null;
            
            // Handle file upload
            if (isset($_FILES['lampiran_bukti']) && $_FILES['lampiran_bukti']['error'] === UPLOAD_ERR_OK) {
                $lampiran_path = $this->handleLampiranUpload($_FILES['lampiran_bukti']);
            }
            
            try {
                // Generate a simple UUID v4 in PHP
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $targetTenantId = $postedTenantId ?: $tenantId;
                $stmt = $db->prepare("INSERT INTO guru_monitoring (id, tenant_id, guru_id, kategori_masalah, sumber_deteksi, deskripsi_kasus, status_kasus, lampiran_bukti) VALUES (?, ?, ?, ?, ?, ?, 'Merah', ?)");
                $stmt->execute([$id, $targetTenantId, $guru_id, $kategori_masalah, $sumber_deteksi, $deskripsi_kasus, $lampiran_path]);
                
                $_SESSION['flash_success'] = 'Kasus pembinaan berhasil ditambahkan (Status Merah).';
            } catch (\PDOException $e) {
                $_SESSION['flash_error'] = 'Terjadi kesalahan sistem saat menyimpan data.';
            }
            
            $redirectUrl = '/SINTA-SaaS/pembinaan' . ($postedTenantId ? '?tenant_id=' . urlencode($postedTenantId) : '');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    private function handleLampiranUpload($file) {
        $uploadDir = __DIR__ . '/../../storage/uploads/pembinaan/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        if (!in_array($ext, $allowedExt)) return null;
        
        $filename = uniqid('bukti_') . '.' . $ext;
        $destPath = $uploadDir . $filename;
        
        // If it's an image, compress it to be under ~500kb
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $this->compressImage($file['tmp_name'], $destPath, 75);
        } else {
            // Document, move normally
            move_uploaded_file($file['tmp_name'], $destPath);
        }
        
        return 'storage/uploads/pembinaan/' . $filename;
    }
    
    private function compressImage($source, $destination, $quality) {
        $info = getimagesize($source);
        if (!$info) return move_uploaded_file($source, $destination);
        
        if ($info['mime'] == 'image/jpeg') 
            $image = imagecreatefromjpeg($source);
        elseif ($info['mime'] == 'image/png') 
            $image = imagecreatefrompng($source);
        else 
            return move_uploaded_file($source, $destination);
            
        // Resize if too large (Max Width 1280px)
        $width = $info[0];
        $height = $info[1];
        if ($width > 1280) {
            $newWidth = 1280;
            $newHeight = floor($height * (1280 / $width));
            $tmpImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparency for PNG
            if ($info['mime'] == 'image/png') {
                imagealphablending($tmpImage, false);
                imagesavealpha($tmpImage, true);
                $transparent = imagecolorallocatealpha($tmpImage, 255, 255, 255, 127);
                imagefilledrectangle($tmpImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            imagecopyresampled($tmpImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $tmpImage;
        }
        
        if ($info['mime'] == 'image/jpeg') {
            imagejpeg($image, $destination, $quality);
        } elseif ($info['mime'] == 'image/png') {
            // Convert JPEG quality (0-100) to PNG compression level (0-9)
            $pngQuality = round((100 - $quality) / 10);
            imagepng($image, $destination, $pngQuality);
        }
        
        imagedestroy($image);
        return true;
    }
    
    public function jadwalkan_sesi() {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $monitoring_id = $_POST['monitoring_id'] ?? '';
            $tanggal_sesi = $_POST['tanggal_sesi'] ?? '';
            $kepsek_id = $_SESSION['user_id'] ?? '';
            
            $postedTenantId = $_POST['tenant_id'] ?? null;
            $targetTenantId = $postedTenantId ?: $tenantId;
            
            if (!$monitoring_id || !$tanggal_sesi) {
                $_SESSION['flash_error'] = 'Harap isi tanggal jadwal sesi!';
                $redirectUrl = '/SINTA-SaaS/pembinaan' . ($postedTenantId ? '?tenant_id=' . urlencode($postedTenantId) : '');
                header('Location: ' . $redirectUrl);
                exit;
            }
            
            try {
                // Generate UUID
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $stmt = $db->prepare("INSERT INTO sesi_mentoring (id, tenant_id, monitoring_id, kepsek_id, tanggal_sesi, status_sesi) VALUES (?, ?, ?, ?, ?, 'Dijadwalkan')");
                $stmt->execute([$id, $targetTenantId, $monitoring_id, $kepsek_id, $tanggal_sesi]);
                
                $_SESSION['flash_success'] = 'Sesi pembinaan berhasil dijadwalkan.';
            } catch (\PDOException $e) {
                $_SESSION['flash_error'] = 'Gagal menjadwalkan sesi: ' . $e->getMessage();
            }
            
            $redirectUrl = '/SINTA-SaaS/pembinaan' . ($postedTenantId ? '?tenant_id=' . urlencode($postedTenantId) : '');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    public function sesi() {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $sesi_id = $_GET['id'] ?? '';
        
        $roleName = $_SESSION['role_name'] ?? '';
        $isSuperAdmin = ($roleName === 'super_admin');
        
        $selectedTenantId = $_GET['tenant_id'] ?? null;
        $effectiveTenantId = $isSuperAdmin ? $selectedTenantId : $tenantId;
        
        if (!$sesi_id) {
            $redirectUrl = '/SINTA-SaaS/pembinaan' . ($selectedTenantId ? '?tenant_id=' . urlencode($selectedTenantId) : '');
            header('Location: ' . $redirectUrl);
            exit;
        }
        
        // Ambil data sesi
        if ($isSuperAdmin) {
            $stmt = $db->prepare("
                SELECT sm.*, gm.deskripsi_kasus, gm.kategori_masalah, u.nama_lengkap as nama_guru 
                FROM sesi_mentoring sm
                JOIN guru_monitoring gm ON sm.monitoring_id = gm.id
                JOIN users u ON gm.guru_id = u.id
                WHERE sm.id = ?
            ");
            $stmt->execute([$sesi_id]);
        } else {
            $stmt = $db->prepare("
                SELECT sm.*, gm.deskripsi_kasus, gm.kategori_masalah, u.nama_lengkap as nama_guru 
                FROM sesi_mentoring sm
                JOIN guru_monitoring gm ON sm.monitoring_id = gm.id
                JOIN users u ON gm.guru_id = u.id
                WHERE sm.id = ? AND sm.tenant_id = ?
            ");
            $stmt->execute([$sesi_id, $tenantId]);
        }
        $sesi = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sesi) {
            $redirectUrl = '/SINTA-SaaS/pembinaan' . ($selectedTenantId ? '?tenant_id=' . urlencode($selectedTenantId) : '');
            header('Location: ' . $redirectUrl);
            exit;
        }
        
        // Ensure tenantParam is correct for super admin (derive from sesi's tenant_id)
        $actualTenantId = $sesi['tenant_id'];
        $tenantParam = $isSuperAdmin ? '?tenant_id=' . urlencode($actualTenantId) : '';
        
        $this->render('sekolah/pembinaan_sesi', [
            'pageTitle' => 'Pelaksanaan Sesi Mentoring',
            'role' => $roleName,
            'sesi' => $sesi,
            'tenantParam' => $tenantParam
        ]);
    }
    
    public function simpan_sesi() {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sesi_id = $_POST['sesi_id'] ?? '';
            $catatan_fakta = $_POST['catatan_fakta'] ?? '';
            $rencana_tindak_lanjut = $_POST['rencana_tindak_lanjut'] ?? '';
            $ttd_kepsek = $_POST['ttd_kepsek'] ?? '';
            $ttd_guru = $_POST['ttd_guru'] ?? '';
            $monitoring_id = $_POST['monitoring_id'] ?? '';
            
            $postedTenantId = $_POST['tenant_id'] ?? null;
            $targetTenantId = $postedTenantId ?: $tenantId;
            
            try {
                // Update sesi_mentoring
                $stmt = $db->prepare("
                    UPDATE sesi_mentoring 
                    SET catatan_fakta = ?, rencana_tindak_lanjut = ?, ttd_digital_kepsek = ?, ttd_digital_guru = ?, status_sesi = 'Selesai'
                    WHERE id = ? AND tenant_id = ?
                ");
                $stmt->execute([$catatan_fakta, $rencana_tindak_lanjut, $ttd_kepsek, $ttd_guru, $sesi_id, $targetTenantId]);
                
                // Ubah status monitoring dari Merah ke Kuning (karena sudah diselesaikan dan masuk masa pemantauan)
                $stmt2 = $db->prepare("UPDATE guru_monitoring SET status_kasus = 'Kuning' WHERE id = ? AND tenant_id = ?");
                $stmt2->execute([$monitoring_id, $targetTenantId]);
                
                $_SESSION['flash_success'] = 'Sesi mentoring berhasil diselesaikan. Status kasus kini menjadi Pemantauan (Kuning).';
            } catch (\PDOException $e) {
                $_SESSION['flash_error'] = 'Terjadi kesalahan sistem saat menyimpan hasil sesi.';
            }
            
            $redirectUrl = '/SINTA-SaaS/pembinaan' . ($postedTenantId ? '?tenant_id=' . urlencode($postedTenantId) : '');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    public function evaluasi() {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $monitoring_id = $_POST['monitoring_id'] ?? '';
            $sesi_id = $_POST['sesi_id'] ?? '';
            $hasil_evaluasi = $_POST['hasil_evaluasi'] ?? '';
            $tindakan_lanjutan = $_POST['tindakan_lanjutan'] ?? '';
            $catatan_perkembangan = $_POST['catatan_perkembangan'] ?? '';
            
            $postedTenantId = $_POST['tenant_id'] ?? null;
            $targetTenantId = $postedTenantId ?: $tenantId;
            
            try {
                // Generate UUID untuk evaluasi
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                // 1. Simpan ke tabel evaluasi_pemantauan
                $stmt = $db->prepare("INSERT INTO evaluasi_pemantauan (id, tenant_id, mentoring_id, tanggal_evaluasi, hasil_evaluasi, tindakan_lanjutan, catatan_perkembangan) VALUES (?, ?, ?, CURDATE(), ?, ?, ?)");
                $stmt->execute([$id, $targetTenantId, $sesi_id, $hasil_evaluasi, $tindakan_lanjutan, $catatan_perkembangan]);
                
                // 2. Ubah status monitoring berdasarkan hasil evaluasi
                $status_baru = 'Kuning';
                if ($tindakan_lanjutan === 'Selesai') {
                    $status_baru = 'Hijau';
                } else if ($tindakan_lanjutan === 'Teguran') {
                    $status_baru = 'Merah';
                }
                
                $stmt2 = $db->prepare("UPDATE guru_monitoring SET status_kasus = ? WHERE id = ? AND tenant_id = ?");
                $stmt2->execute([$status_baru, $monitoring_id, $targetTenantId]);
                
                $_SESSION['flash_success'] = 'Evaluasi berhasil disimpan. Status kinerja telah diperbarui menjadi ' . $status_baru;
            } catch (\PDOException $e) {
                $_SESSION['flash_error'] = 'Terjadi kesalahan sistem saat menyimpan evaluasi.';
            }
            
            $redirectUrl = '/SINTA-SaaS/pembinaan' . ($postedTenantId ? '?tenant_id=' . urlencode($postedTenantId) : '');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    public function cetak() {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $roleName = $_SESSION['role_name'] ?? '';
        $isSuperAdmin = ($roleName === 'super_admin');
        
        $effectiveTenantId = $isSuperAdmin ? ($_GET['tenant_id'] ?? null) : $tenantId;
        
        if ($isSuperAdmin && !$effectiveTenantId) {
            // Redirect back or show error if superadmin didn't select tenant
            header('Location: /SINTA-SaaS/pembinaan');
            exit;
        }
        
        // Cek jika mencetak spesifik 1 kasus
        $spesifikId = $_GET['id'] ?? null;
        
        $sql = "
            SELECT gm.*, u.nama_lengkap as nama_guru, 
                   sm.tanggal_sesi, sm.catatan_fakta, sm.rencana_tindak_lanjut, 
                   sm.ttd_digital_kepsek, sm.ttd_digital_guru,
                   ep.hasil_evaluasi, ep.tindakan_lanjutan, ep.catatan_perkembangan
            FROM guru_monitoring gm 
            JOIN users u ON gm.guru_id = u.id 
            JOIN sesi_mentoring sm ON gm.id = sm.monitoring_id
            LEFT JOIN evaluasi_pemantauan ep ON sm.id = ep.mentoring_id
            WHERE gm.tenant_id = ?
        ";
        $params = [$effectiveTenantId];
        
        if ($spesifikId) {
            $sql .= " AND gm.id = ?";
            $params[] = $spesifikId;
        }
        
        $sql .= " ORDER BY gm.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $data = [
            'pageTitle' => 'Laporan Akreditasi Pembinaan Guru',
            'role' => $roleName,
            'laporan' => $laporan
        ];
        
        extract($data);
        require __DIR__ . '/../../views/sekolah/pembinaan_cetak.php';
    }
}
