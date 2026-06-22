<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use App\Helpers\ActivityLogger;
use PDO;

class SekolahController extends BaseController {

    public function __construct() {
        parent::__construct();
        
        // 1. Wajib Login (Security Gate)
        SessionManager::requireLogin();
        
        // 2. Hak Akses: Hanya Super Admin & Operator Sekolah
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'super_admin' && $role !== 'operator_sekolah') {
            http_response_code(403);
            echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
            echo "<h1 style='color: #dc3545;'>403 Akses Ditolak</h1>";
            echo "<p style='color: #6c757d;'>Anda tidak memiliki wewenang untuk mengakses manajemen profil sekolah.</p>";
            echo "<a href='/SINTA-SaaS/dashboard'>Kembali ke Dashboard</a>";
            echo "</div>";
            exit;
        }
    }

    /**
     * Tampilkan Halaman Utama Profil / Identitas Sekolah
     * GET /sekolah/identitas
     */
    public function showProfile(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $tenantsList = [];

        // Fallback untuk super admin (bisa lewat GET parameter, jika kosong default ke tenant pertama)
        if ($role === 'super_admin') {
            $getTenantId = $_GET['tenant_id'] ?? null;
            if (!empty($getTenantId)) {
                $tenantId = $getTenantId;
            } elseif (empty($tenantId)) {
                try {
                    $db = Database::getConnection();
                    $tenantId = $db->query("SELECT id FROM tenants WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
                } catch (\Throwable $e) {
                    $tenantId = null;
                }
            }

            // Dapatkan seluruh daftar sekolah untuk dropdown filter super admin
            try {
                $db = Database::getConnection();
                $stmtList = $db->query("SELECT id, nama_sekolah, npsn FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC");
                $tenantsList = $stmtList->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $tenantsList = [];
            }
        }

        if (empty($tenantId)) {
            header('Location: /SINTA-SaaS/dashboard?error=' . urlencode('Data sekolah tidak ditemukan.'));
            exit;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM tenants WHERE id = :id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['id' => $tenantId]);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tenant) {
                header('Location: /SINTA-SaaS/dashboard?error=' . urlencode('Sekolah tidak ditemukan.'));
                exit;
            }

            $data = [
                'title'       => 'Profil & Identitas Sekolah',
                'user_nama'   => $_SESSION['nama_lengkap'] ?? 'User',
                'user_role'   => $role,
                'tenant'      => $tenant,
                'tenantsList' => $tenantsList
            ];

            $this->render('sekolah_profil', $data);
        } catch (\Throwable $e) {
            error_log("Failed to load school profile view: " . $e->getMessage());
            header('Location: /SINTA-SaaS/dashboard?error=' . urlencode('Terjadi kesalahan sistem.'));
            exit;
        }
    }

    /**
     * API: Update profil sekolah (Identitas & Unggah File)
     * POST /api/v1/sekolah/update
     */
    public function updateProfile(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;

        if ($role === 'super_admin') {
            $postTenantId = $_POST['tenant_id'] ?? null;
            if (!empty($postTenantId)) {
                $tenantId = $postTenantId;
            } elseif (empty($tenantId)) {
                try {
                    $db = Database::getConnection();
                    $tenantId = $db->query("SELECT id FROM tenants WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
                } catch (\Throwable $e) {
                    $tenantId = null;
                }
            }
        }

        if (empty($tenantId)) {
            $this->jsonResponse(['error' => 'ID Sekolah tidak valid.'], 400);
        }

        // Ambil data POST (Multipart Form-Data)
        $alamat = isset($_POST['alamat_sekolah']) ? trim(strip_tags($_POST['alamat_sekolah'])) : '';
        $rtRw = isset($_POST['rt_rw']) ? trim(strip_tags($_POST['rt_rw'])) : '';
        $kodePos = isset($_POST['kode_pos']) ? trim(strip_tags($_POST['kode_pos'])) : '';
        $kelurahan = isset($_POST['kelurahan']) ? trim(strip_tags($_POST['kelurahan'])) : '';
        $kecamatan = isset($_POST['kecamatan']) ? trim(strip_tags($_POST['kecamatan'])) : 'Kec. Tandes';
        $kabupaten = isset($_POST['kabupaten_kota']) ? trim(strip_tags($_POST['kabupaten_kota'])) : 'Kota Surabaya';
        $provinsi = isset($_POST['provinsi']) ? trim(strip_tags($_POST['provinsi'])) : 'Prov. Jawa Timur';
        $noTelp = isset($_POST['no_telp']) ? trim(strip_tags($_POST['no_telp'])) : '';
        $emailSekolah = isset($_POST['email_sekolah']) ? trim(strip_tags($_POST['email_sekolah'])) : '';
        $website = isset($_POST['website']) ? trim(strip_tags($_POST['website'])) : '';
        $namaKepsek = isset($_POST['nama_kepsek']) ? trim(strip_tags($_POST['nama_kepsek'])) : 'Nana Petty Puspitasari';
        $nipKepsek = isset($_POST['nip_kepsek']) ? trim(strip_tags($_POST['nip_kepsek'])) : '';
        $namaOperator = isset($_POST['nama_operator']) ? trim(strip_tags($_POST['nama_operator'])) : 'Edi Sugiarto';
        $emailOperator = isset($_POST['email_operator']) ? trim(strip_tags($_POST['email_operator'])) : 'aidasugiarto@gmail.com';
        $akreditasi = isset($_POST['akreditasi']) ? trim(strip_tags($_POST['akreditasi'])) : 'A (Unggul)';

        // 1. Validasi Input Dasar
        $errors = [];
        if (empty($alamat)) $errors['alamat_sekolah'] = ['Alamat sekolah wajib diisi.'];
        if (empty($rtRw)) $errors['rt_rw'] = ['RT / RW wajib diisi.'];
        if (empty($kodePos)) $errors['kode_pos'] = ['Kode pos wajib diisi.'];
        if (empty($kelurahan)) $errors['kelurahan'] = ['Kelurahan wajib diisi.'];
        
        if (empty($emailSekolah)) {
            $errors['email_sekolah'] = ['Email resmi sekolah wajib diisi.'];
        } elseif (!filter_var($emailSekolah, FILTER_VALIDATE_EMAIL)) {
            $errors['email_sekolah'] = ['Format email resmi sekolah tidak valid.'];
        }

        if (empty($emailOperator)) {
            $errors['email_operator'] = ['Email operator wajib diisi.'];
        } elseif (!filter_var($emailOperator, FILTER_VALIDATE_EMAIL)) {
            $errors['email_operator'] = ['Format email operator tidak valid.'];
        }

        // 2. Validasi File Upload
        $maxFileSize = 500 * 1024; // 500 KB

        // Validasi Logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoSize = $_FILES['logo']['size'];
            $logoExt = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (!in_array($logoExt, ['jpg', 'jpeg', 'png'], true)) {
                $errors['logo'] = ['Logo harus berupa gambar (.jpg, .jpeg, .png).'];
            }
            if ($logoSize > $maxFileSize) {
                $errors['logo'] = ['Ukuran file logo tidak boleh melebihi 500 KB.'];
            }
        }

        // Validasi Sertifikat Akreditasi
        if (isset($_FILES['sertifikat_akreditasi']) && $_FILES['sertifikat_akreditasi']['error'] === UPLOAD_ERR_OK) {
            $sertifSize = $_FILES['sertifikat_akreditasi']['size'];
            $sertifExt = strtolower(pathinfo($_FILES['sertifikat_akreditasi']['name'], PATHINFO_EXTENSION));
            if (!in_array($sertifExt, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
                $errors['sertifikat_akreditasi'] = ['Berkas akreditasi harus berupa gambar atau PDF (.jpg, .jpeg, .png, .pdf).'];
            }
            if ($sertifSize > $maxFileSize) {
                $errors['sertifikat_akreditasi'] = ['Ukuran file akreditasi tidak boleh melebihi 500 KB.'];
            }
        }

        if (!empty($errors)) {
            $this->jsonResponse(['errors' => $errors], 422);
        }

        try {
            $db = Database::getConnection();

            // Ambil data sekolah lama untuk keperluan penghapusan file lama & pencatatan log
            $stmtOld = $db->prepare("SELECT * FROM tenants WHERE id = :id AND deleted_at IS NULL LIMIT 1");
            $stmtOld->execute(['id' => $tenantId]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                $this->jsonResponse(['error' => 'Sekolah tidak ditemukan.'], 404);
            }

            // Tentukan direktori upload fisik dan path relatif DB
            $uploadBaseDir = __DIR__ . '/../../storage/app/public/uploads/tenants/' . $tenantId . '/';
            $relativeDir = 'uploads/tenants/' . $tenantId . '/';
            if (!is_dir($uploadBaseDir)) {
                mkdir($uploadBaseDir, 0755, true);
            }

            $logoPath = $oldData['logo'];
            $sertifPath = $oldData['sertifikat_akreditasi'];

            // 3. Proses Upload File Logo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                // Hapus file fisik logo lama
                if (!empty($oldData['logo'])) {
                    $oldAbsLogo = __DIR__ . '/../../storage/app/public/' . $oldData['logo'];
                    if (file_exists($oldAbsLogo)) {
                        @unlink($oldAbsLogo);
                    }
                }
                $logoExt = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $newLogoName = 'logo_' . bin2hex(random_bytes(10)) . '.' . $logoExt;
                move_uploaded_file($_FILES['logo']['tmp_name'], $uploadBaseDir . $newLogoName);
                $logoPath = $relativeDir . $newLogoName;
            }

            // 4. Proses Upload File Sertifikat Akreditasi
            if (isset($_FILES['sertifikat_akreditasi']) && $_FILES['sertifikat_akreditasi']['error'] === UPLOAD_ERR_OK) {
                // Hapus file fisik sertifikat lama
                if (!empty($oldData['sertifikat_akreditasi'])) {
                    $oldAbsSertif = __DIR__ . '/../../storage/app/public/' . $oldData['sertifikat_akreditasi'];
                    if (file_exists($oldAbsSertif)) {
                        @unlink($oldAbsSertif);
                    }
                }
                $sertifExt = strtolower(pathinfo($_FILES['sertifikat_akreditasi']['name'], PATHINFO_EXTENSION));
                $newSertifName = 'akreditasi_' . bin2hex(random_bytes(10)) . '.' . $sertifExt;
                move_uploaded_file($_FILES['sertifikat_akreditasi']['tmp_name'], $uploadBaseDir . $newSertifName);
                $sertifPath = $relativeDir . $newSertifName;
            }

            // 5. Jalankan Kueri UPDATE dengan Prepared Statement PDO
            $stmtUpdate = $db->prepare("
                UPDATE tenants SET
                    alamat_sekolah = :alamat_sekolah,
                    rt_rw = :rt_rw,
                    kode_pos = :kode_pos,
                    kelurahan = :kelurahan,
                    kecamatan = :kecamatan,
                    kabupaten_kota = :kabupaten_kota,
                    provinsi = :provinsi,
                    no_telp = :no_telp,
                    email_sekolah = :email_sekolah,
                    website = :website,
                    nama_kepsek = :nama_kepsek,
                    nip_kepsek = :nip_kepsek,
                    nama_operator = :nama_operator,
                    email_operator = :email_operator,
                    logo = :logo,
                    sertifikat_akreditasi = :sertifikat_akreditasi,
                    akreditasi = :akreditasi,
                    updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL
            ");

            $stmtUpdate->execute([
                'alamat_sekolah' => $alamat,
                'rt_rw' => $rtRw,
                'kode_pos' => $kodePos,
                'kelurahan' => $kelurahan,
                'kecamatan' => $kecamatan,
                'kabupaten_kota' => $kabupaten,
                'provinsi' => $provinsi,
                'no_telp' => $noTelp,
                'email_sekolah' => $emailSekolah,
                'website' => $website,
                'nama_kepsek' => $namaKepsek,
                'nip_kepsek' => $nipKepsek,
                'nama_operator' => $namaOperator,
                'email_operator' => $emailOperator,
                'logo' => $logoPath,
                'sertifikat_akreditasi' => $sertifPath,
                'akreditasi' => $akreditasi,
                'id' => $tenantId
            ]);

            // Ambil data baru untuk perbandingan log audit
            $stmtNew = $db->prepare("SELECT * FROM tenants WHERE id = :id AND deleted_at IS NULL LIMIT 1");
            $stmtNew->execute(['id' => $tenantId]);
            $newData = $stmtNew->fetch(PDO::FETCH_ASSOC);

            // Bandingkan perubahan
            $cleanOld = [];
            $cleanNew = [];
            foreach ($newData as $k => $v) {
                if ($oldData[$k] != $v) {
                    $cleanOld[$k] = $oldData[$k];
                    $cleanNew[$k] = $v;
                }
            }

            if (!empty($cleanNew)) {
                ActivityLogger::record('UPDATE', 'tenants', $tenantId, $cleanOld, $cleanNew);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Identitas & Profil Sekolah berhasil diperbarui.'
            ]);

        } catch (\Throwable $e) {
            error_log("Failed to update school profile: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat menyimpan profil.'], 500);
        }
    }
}
