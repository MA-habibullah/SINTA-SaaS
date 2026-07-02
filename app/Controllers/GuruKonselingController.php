<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class GuruKonselingController extends BaseController {
    public function index() {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        
        // 1. Cek kasus/sesi aktif yang melibatkan guru ini (Merah/Kuning)
        $stmt = $db->prepare("
            SELECT gm.*, sm.tanggal_sesi, sm.status_sesi 
            FROM guru_monitoring gm 
            LEFT JOIN sesi_mentoring sm ON gm.id = sm.monitoring_id 
            WHERE gm.tenant_id = ? AND gm.guru_id = ? AND gm.status_kasus IN ('Merah', 'Kuning')
            ORDER BY gm.created_at DESC
        ");
        $stmt->execute([$tenantId, $userId]);
        $kasusAktif = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->render('guru/konseling_index', [
            'pageTitle' => 'Refleksi & Konseling Privat',
            'role' => $_SESSION['role_name'] ?? '',
            'kasusAktif' => $kasusAktif
        ]);
    }
    
    public function store() {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deskripsi_kasus = $_POST['deskripsi_kasus'] ?? '';
            $kategori_masalah = 'Personal'; // Pengajuan mandiri default Personal
            
            if (!$deskripsi_kasus) {
                $_SESSION['flash_error'] = 'Harap isi alasan pengajuan!';
                header('Location: /SINTA-SaaS/konseling');
                exit;
            }
            
            try {
                // Generate UUID
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $stmt = $db->prepare("INSERT INTO guru_monitoring (id, tenant_id, guru_id, kategori_masalah, sumber_deteksi, deskripsi_kasus, status_kasus) VALUES (?, ?, ?, ?, 'Pengajuan Mandiri', ?, 'Merah')");
                $stmt->execute([$id, $tenantId, $userId, $kategori_masalah, $deskripsi_kasus]);
                
                $_SESSION['flash_success'] = 'Pengajuan konseling berhasil dikirimkan ke Kepala Sekolah.';
            } catch (\PDOException $e) {
                $_SESSION['flash_error'] = 'Terjadi kesalahan sistem saat menyimpan pengajuan.';
            }
            
            header('Location: /SINTA-SaaS/konseling');
            exit;
        }
    }
}
