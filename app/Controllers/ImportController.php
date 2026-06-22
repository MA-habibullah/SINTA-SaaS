<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use PDO;

class ImportController extends BaseController {

    public function __construct() {
        parent::__construct();
        
        // 1. Gate Keamanan: Wajib Login
        SessionManager::requireLogin();

        // 2. Otorisasi: Hanya super_admin atau operator_sekolah yang boleh import
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName !== 'super_admin' && $roleName !== 'operator_sekolah') {
            $this->jsonResponse(['error' => 'Akses ditolak. Fitur ini memerlukan wewenang Admin Sekolah atau Super Admin.'], 403);
        }
    }

    /**
     * API: Import data siswa dari file CSV
     * POST /api/v1/siswa/import
     */
    public function import(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'Berkas upload tidak ditemukan atau terjadi kesalahan saat pengiriman.'], 400);
        }

        $fileTmp = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validasi ekstensi
        if ($fileExt !== 'csv') {
            $this->jsonResponse(['error' => 'Format file tidak valid. Fitur ini saat ini melayani file CSV (.csv) hasil ekspor Excel.'], 400);
        }

        $handle = fopen($fileTmp, 'r');
        if (!$handle) {
            $this->jsonResponse(['error' => 'Gagal membuka file untuk dibaca.'], 500);
        }

        // Auto-detect delimiter (koma vs titik koma)
        $firstLine = fgets($handle);
        $delimiter = ',';
        if (strpos($firstLine, ';') !== false) {
            $delimiter = ';';
        }
        rewind($handle);

        // Ambil baris pertama sebagai Header
        $header = fgetcsv($handle, 1000, $delimiter);
        if (!$header) {
            fclose($handle);
            $this->jsonResponse(['error' => 'Berkas CSV kosong atau tidak valid.'], 400);
        }

        // Hilangkan UTF-8 BOM jika ada di kolom pertama
        if (isset($header[0])) {
            $header[0] = preg_replace('/^[\x{FEFF}\x{200B}]+/u', '', $header[0]);
        }

        // Normalisasi nama kolom header (trim & lowercase)
        $header = array_map(function($h) {
            return strtolower(trim($h));
        }, $header);

        // Temukan indeks posisi masing-masing kolom yang diharapkan
        $npsnIdx = -1;
        $namaIdx = -1;
        $nisnIdx = -1;
        $tglLahirIdx = -1;
        $emailIdx = -1;

        foreach ($header as $idx => $col) {
            if (strpos($col, 'npsn') !== false || strpos($col, 'sekolah') !== false) {
                $npsnIdx = $idx;
            } elseif (strpos($col, 'nama') !== false || strpos($col, 'lengkap') !== false) {
                $namaIdx = $idx;
            } elseif (strpos($col, 'nisn') !== false) {
                $nisnIdx = $idx;
            } elseif (strpos($col, 'lahir') !== false || strpos($col, 'tanggal') !== false) {
                $tglLahirIdx = $idx;
            } elseif (strpos($col, 'email') !== false) {
                $emailIdx = $idx;
            }
        }

        // Validasi kolom wajib
        if ($namaIdx === -1 || $nisnIdx === -1 || $tglLahirIdx === -1 || $emailIdx === -1) {
            fclose($handle);
            $this->jsonResponse([
                'error' => 'Kolom header file CSV tidak sesuai dengan format template yang dibutuhkan.',
                'details' => 'Kolom yang wajib ada: Nama Lengkap Siswa, NISN, Tanggal Lahir, Email.'
            ], 400);
        }

        $roleName = $_SESSION['role_name'] ?? '';
        $sessionTenantId = $_SESSION['tenant_id'] ?? null;

        if ($roleName === 'super_admin' && $npsnIdx === -1) {
            fclose($handle);
            $this->jsonResponse(['error' => 'Untuk peran Super Admin, kolom NPSN Sekolah wajib dicantumkan di dalam file CSV.'], 400);
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        $rowCount = 0;
        $successCount = 0;
        $errors = [];

        try {
            // Siapkan query prepared statement
            $stmtTenantLookup = $db->prepare("SELECT id FROM tenants WHERE npsn = :npsn AND deleted_at IS NULL LIMIT 1");
            $stmtNisnCheck = $db->prepare("SELECT COUNT(*) FROM siswa WHERE nisn = :nisn AND deleted_at IS NULL");
            $stmtSiswaInsert = $db->prepare("
                INSERT INTO siswa (
                    id, tenant_id, nisn, nama_lengkap, tanggal_lahir, 
                    jenis_kelamin, password, is_first_login, created_at, updated_at
                ) VALUES (
                    :id, :tenant_id, :nisn, :nama_lengkap, :tanggal_lahir, 
                    'L', :password, 1, NOW(), NOW()
                )
            ");
            $stmtKontakInsert = $db->prepare("
                INSERT INTO kontak (
                    id_siswa, email, no_telepon_siswa
                ) VALUES (
                    :id_siswa, :email, '-'
                )
            ");

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $rowCount++;
                
                // Lewati jika seluruh baris kosong
                if (empty(array_filter($row))) {
                    continue;
                }

                $rawNpsn = ($npsnIdx !== -1 && isset($row[$npsnIdx])) ? trim($row[$npsnIdx]) : '';
                $rawNama = isset($row[$namaIdx]) ? trim($row[$namaIdx]) : '';
                $rawNisn = isset($row[$nisnIdx]) ? trim($row[$nisnIdx]) : '';
                $rawTglLahir = isset($row[$tglLahirIdx]) ? trim($row[$tglLahirIdx]) : '';
                $rawEmail = ($emailIdx !== -1 && isset($row[$emailIdx])) ? trim($row[$emailIdx]) : '';

                // 1. Validasi Nama Lengkap
                if (empty($rawNama)) {
                    $errors[] = "Baris {$rowCount}: Nama Lengkap Siswa tidak boleh kosong.";
                    continue;
                }

                // 2. Validasi NISN (Wajib 10 digit angka)
                if (empty($rawNisn) || !preg_match('/^[0-9]{10}$/', $rawNisn)) {
                    $errors[] = "Baris {$rowCount}: NISN '{$rawNisn}' harus berupa 10 digit angka.";
                    continue;
                }

                // 3. Validasi Keunikan NISN
                $stmtNisnCheck->execute(['nisn' => $rawNisn]);
                if ((int)$stmtNisnCheck->fetchColumn() > 0) {
                    $errors[] = "Baris {$rowCount}: NISN '{$rawNisn}' sudah terdaftar pada siswa lain.";
                    continue;
                }

                // 4. Validasi Format Tanggal Lahir (YYYY-MM-DD)
                if (empty($rawTglLahir) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTglLahir)) {
                    $errors[] = "Baris {$rowCount}: Tanggal lahir '{$rawTglLahir}' harus berformat YYYY-MM-DD (Contoh: 2008-05-14).";
                    continue;
                }

                // 4.5. Validasi Email
                if (empty($rawEmail) || !filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Baris {$rowCount}: Alamat Email '{$rawEmail}' tidak valid.";
                    continue;
                }

                // 5. Penentuan Tenant ID
                $tenantId = null;
                if ($roleName === 'super_admin') {
                    if (empty($rawNpsn)) {
                        $errors[] = "Baris {$rowCount}: NPSN Sekolah wajib diisi untuk Super Admin.";
                        continue;
                    }
                    // Cari UUID tenant berdasarkan NPSN
                    $stmtTenantLookup->execute(['npsn' => $rawNpsn]);
                    $tenantId = $stmtTenantLookup->fetchColumn();
                    if (!$tenantId) {
                        $errors[] = "Baris {$rowCount}: NPSN Sekolah '{$rawNpsn}' tidak terdaftar di sistem.";
                        continue;
                    }
                } else {
                    // Operator Sekolah: gunakan tenant ID admin login
                    $tenantId = $sessionTenantId;
                }

                if (!$tenantId) {
                    $errors[] = "Baris {$rowCount}: Tenant ID tidak valid.";
                    continue;
                }

                // Enkripsi password default (Bcrypt Tanggal Lahir)
                $hashedPassword = password_hash($rawTglLahir, PASSWORD_BCRYPT);
                $siswaId = $this->generateUuidV4();

                // Simpan data siswa baru
                $stmtSiswaInsert->execute([
                    'id' => $siswaId,
                    'tenant_id' => $tenantId,
                    'nisn' => $rawNisn,
                    'nama_lengkap' => $rawNama,
                    'tanggal_lahir' => $rawTglLahir,
                    'password' => $hashedPassword
                ]);

                // Simpan kontak siswa (email)
                $stmtKontakInsert->execute([
                    'id_siswa' => $siswaId,
                    'email' => $rawEmail
                ]);

                $successCount++;
            }

            fclose($handle);

            if (!empty($errors)) {
                // Batalkan seluruh data jika terdapat kesalahan validasi data baris
                $db->rollBack();
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Gagal memproses file. Beberapa baris data tidak valid.',
                    'errors' => $errors
                ], 422);
            }

            $db->commit();
            $this->jsonResponse([
                'success' => true,
                'message' => "Berhasil mengimport {$successCount} data siswa baru."
            ]);

        } catch (\Throwable $e) {
            fclose($handle);
            $db->rollBack();
            error_log("Gagal import siswa: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download template CSV untuk import siswa
     * GET /api/v1/siswa/import/template
     */
    public function downloadTemplate(): void {
        // Set header response
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_import_siswa.csv"');
        
        // Output CSV content
        $output = fopen('php://output', 'w');
        
        // Header columns
        fputcsv($output, ['NPSN Sekolah', 'Nama Lengkap Siswa', 'NISN', 'Tanggal Lahir', 'Email']);
        
        // Contoh data baris 1
        fputcsv($output, ['10203040', 'Ahmad Dani', '0081234567', '2008-04-12', 'ahmad.dani@example.com']);
        
        // Contoh data baris 2
        fputcsv($output, ['10203040', 'Siti Rahma', '0098765432', '2009-09-21', 'siti.rahma@example.com']);
        
        fclose($output);
        exit;
    }

    /**
     * Generate standard UUID v4
     */
    private function generateUuidV4(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
