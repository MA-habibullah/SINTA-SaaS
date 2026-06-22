<?php

namespace App\Controllers;

use App\Core\SessionManager;
use PDO;

class BulkPhotoController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();

        // Security check: restrict to operator_sekolah and super_admin roles
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $allowed = array_intersect($roles, ['super_admin', 'operator_sekolah']);
        if (empty($allowed)) {
            $this->jsonResponse(['error' => 'Anda tidak memiliki otorisasi untuk fitur ini.'], 403);
        }
    }

    /**
     * POST /api/v1/siswa/bulk-photo
     * Receives a ZIP file containing student photos named NPSN_NISN.jpg/png.
     */
    public function uploadZip(): void {
        $db = \App\Config\Database::getConnection();

        // 1. Validate File Upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'Gagal mengunggah file ZIP.'], 400);
        }

        $tmpPath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'zip') {
            $this->jsonResponse(['error' => 'Format file tidak didukung. Mohon unggah file ZIP (.zip).'], 400);
        }

        // 2. Resolve Tenant Constraints
        $sessionTenantId = SessionManager::getTenantId();
        $operatorNpsn = null;
        if ($sessionTenantId) {
            $stmtNpsn = $db->prepare("SELECT npsn FROM tenants WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtNpsn->execute([$sessionTenantId]);
            $operatorNpsn = $stmtNpsn->fetchColumn();
            if (!$operatorNpsn) {
                $this->jsonResponse(['error' => 'Data sekolah login tidak ditemukan.'], 400);
            }
        }

        // 3. Extract ZIP to a temporary directory
        $tempExtractDir = __DIR__ . '/../../storage/app/public/temp_bulk_' . microtime(true) . '_' . bin2hex(random_bytes(5));
        if (!is_dir($tempExtractDir)) {
            mkdir($tempExtractDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            $this->recursiveRmdir($tempExtractDir);
            $this->jsonResponse(['error' => 'Gagal membuka atau membaca file ZIP.'], 400);
        }

        $zip->extractTo($tempExtractDir);
        $zip->close();

        // 4. Scan all extracted files
        $allFiles = [];
        $this->scanAllFiles($tempExtractDir, $allFiles);

        $successCount = 0;
        $failedCount = 0;
        $report = [];

        foreach ($allFiles as $filePath) {
            $filename = basename($filePath);

            // Filter out system files or __MACOSX files
            if (str_starts_with($filename, '.') || str_contains($filePath, '__MACOSX')) {
                continue;
            }

            // Validate extension
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $failedCount++;
                $report[] = [
                    'file' => $filename,
                    'status' => 'failed',
                    'message' => 'Format tidak didukung. Diizinkan: jpg, jpeg, png.'
                ];
                continue;
            }

            // Validate individual file size <= 500 KB
            $fileSize = filesize($filePath);
            if ($fileSize > 500 * 1024) {
                $failedCount++;
                $report[] = [
                    'file' => $filename,
                    'status' => 'failed',
                    'message' => 'Ukuran file melebihi batas 500 KB.'
                ];
                continue;
            }

            // Parse NPSN and NISN (splitting by _ or -)
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $parts = preg_split('/[_-]/', $baseName);
            if (count($parts) < 2) {
                $failedCount++;
                $report[] = [
                    'file' => $filename,
                    'status' => 'failed',
                    'message' => 'Nama file harus menggunakan format NPSN_NISN (Contoh: 20524512_0051234567.jpg).'
                ];
                continue;
            }

            $npsn = trim($parts[0]);
            $nisn = trim($parts[1]);

            // Security: Enforce that School Admin/Operator can only update their own school's student photos
            if ($sessionTenantId && $npsn !== $operatorNpsn) {
                $failedCount++;
                $report[] = [
                    'file' => $filename,
                    'status' => 'failed',
                    'message' => "NPSN {$npsn} tidak sesuai dengan NPSN sekolah Anda."
                ];
                continue;
            }

            // Match student in database
            $stmtSiswa = $db->prepare("
                SELECT s.id AS siswa_id, s.tenant_id, rp.foto_profil, d.file_sizes, rp.id_rincian_pelajar, d.id_dokumen
                FROM siswa s
                JOIN tenants t ON s.tenant_id = t.id
                LEFT JOIN rincian_pelajar rp ON s.id = rp.id_siswa
                LEFT JOIN dokumen d ON s.id = d.id_siswa
                WHERE t.npsn = :npsn
                  AND s.nisn = :nisn
                  AND s.deleted_at IS NULL
                  AND t.deleted_at IS NULL
                LIMIT 1
            ");
            $stmtSiswa->execute(['npsn' => $npsn, 'nisn' => $nisn]);
            $siswa = $stmtSiswa->fetch(PDO::FETCH_ASSOC);

            if (!$siswa) {
                $failedCount++;
                $report[] = [
                    'file' => $filename,
                    'status' => 'failed',
                    'message' => "Siswa dengan NPSN {$npsn} dan NISN {$nisn} tidak ditemukan."
                ];
                continue;
            }

            $siswaId = $siswa['siswa_id'];
            $tenantId = $siswa['tenant_id'];

            // Prepare target upload directory
            $baseDir = __DIR__ . '/../../storage/app/public/uploads/' . $tenantId . '/' . $siswaId . '/';
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }

            $newFileName = bin2hex(random_bytes(20)) . '.' . $ext;
            $destPath = $baseDir . $newFileName;

            if (!rename($filePath, $destPath)) {
                $failedCount++;
                $report[] = [
                    'file' => $filename,
                    'status' => 'failed',
                    'message' => 'Gagal memindahkan file ke direktori penyimpanan.'
                ];
                continue;
            }

            // Delete old photo if it exists on disk
            $oldRelativePath = $siswa['foto_profil'] ?? '';
            $trustedPrefix = 'uploads/' . $tenantId . '/' . $siswaId . '/';
            if (!empty($oldRelativePath) && str_starts_with($oldRelativePath, $trustedPrefix)) {
                $oldAbsPath = __DIR__ . '/../../storage/app/public/' . $oldRelativePath;
                if (file_exists($oldAbsPath)) {
                    @unlink($oldAbsPath);
                }
            }

            // Update Database: rincian_pelajar
            if (!empty($siswa['id_rincian_pelajar'])) {
                $stmtUpdateRP = $db->prepare("UPDATE rincian_pelajar SET foto_profil = :foto_profil, updated_at = NOW() WHERE id_siswa = :siswa_id");
                $stmtUpdateRP->execute([
                    'foto_profil' => 'uploads/' . $tenantId . '/' . $siswaId . '/' . $newFileName,
                    'siswa_id' => $siswaId
                ]);
            } else {
                $stmtInsertRP = $db->prepare("
                    INSERT INTO rincian_pelajar 
                        (id_siswa, lingkar_kepala, tinggi_badan, berat_badan, golongan_darah, anak_ke, jarak_rumah, transportasi, jumlah_saudara, foto_profil) 
                    VALUES 
                        (:siswa_id, 0, 0, 0, 'A', 1, 0, 'Lainnya', 0, :foto_profil)
                ");
                $stmtInsertRP->execute([
                    'siswa_id' => $siswaId,
                    'foto_profil' => 'uploads/' . $tenantId . '/' . $siswaId . '/' . $newFileName
                ]);
            }

            // Update Database: dokumen (file_sizes)
            $oldSizes = [];
            if (!empty($siswa['file_sizes'])) {
                $oldSizes = json_decode($siswa['file_sizes'], true) ?: [];
            }
            $oldSizes['foto_profil'] = $fileSize;
            $fileSizesJson = json_encode($oldSizes);

            if (!empty($siswa['id_dokumen'])) {
                $stmtUpdateDok = $db->prepare("UPDATE dokumen SET file_sizes = :file_sizes WHERE id_siswa = :siswa_id");
                $stmtUpdateDok->execute([
                    'file_sizes' => $fileSizesJson,
                    'siswa_id' => $siswaId
                ]);
            } else {
                $stmtInsertDok = $db->prepare("
                    INSERT INTO dokumen 
                        (id_siswa, file_sizes) 
                    VALUES 
                        (:siswa_id, :file_sizes)
                ");
                $stmtInsertDok->execute([
                    'siswa_id' => $siswaId,
                    'file_sizes' => $fileSizesJson
                ]);
            }

            $successCount++;
            $report[] = [
                'file' => $filename,
                'status' => 'success',
                'message' => 'Foto profil berhasil dipasang.'
            ];
        }

        // 5. Clean up temporary extraction folder
        $this->recursiveRmdir($tempExtractDir);

        // 6. Return response report
        $this->jsonResponse([
            'total_files' => count($allFiles),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'report' => $report
        ]);
    }

    /**
     * Recursively list all files in directory
     */
    private function scanAllFiles(string $dir, array &$results = []): void {
        $files = scandir($dir);
        foreach ($files as $value) {
            $path = $dir . DIRECTORY_SEPARATOR . $value;
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                $this->scanAllFiles($path, $results);
            }
        }
    }

    /**
     * Recursively remove directory and all contents
     */
    private function recursiveRmdir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->recursiveRmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
