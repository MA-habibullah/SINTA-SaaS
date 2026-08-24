<?php

namespace App\Modules\Siswa\Controllers;

use App\Core\BaseController;
use App\Config\Database;
use App\Core\FileStorage;
use App\Core\SessionManager;
use PDO;

class BulkPhotoModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();

        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $allowed = array_intersect($roles, ['super_admin', 'operator_sekolah']);
        if (empty($allowed)) {
            $this->jsonResponse(false, null, 'Anda tidak memiliki otorisasi untuk fitur ini.', 403);
        }
    }

    /**
     * POST /api/v1/siswa/bulk-photo
     */
    public function uploadZip(): void {
        $db = Database::getConnection();

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(false, null, 'Silakan pilih berkas ZIP foto terlebih dahulu.', 400);
            return;
        }

        $file = $_FILES['file'];
        $tmpPath = $file['tmp_name'];

        // 1. Magic Bytes & MIME Type check via finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath);
        $allowedZipMimes = ['application/zip', 'application/x-zip-compressed', 'application/x-zip', 'multipart/x-zip', 'application/octet-stream'];
        if (!in_array($mimeType, $allowedZipMimes, true)) {
            $this->jsonResponse(false, null, 'Format berkas tidak valid. Berkas harus berupa arsip ZIP asli.', 400);
            return;
        }

        // 2. Read magic bytes header (PK\x03\x04 or PK\x05\x06)
        $handle = fopen($tmpPath, 'rb');
        $header = $handle ? fread($handle, 4) : '';
        if ($handle) fclose($handle);
        if (!str_starts_with($header, "PK\x03\x04") && !str_starts_with($header, "PK\x05\x06")) {
            $this->jsonResponse(false, null, 'Header berkas ZIP korup atau tidak valid.', 400);
            return;
        }

        // 3. Validate file via SecurityUploadHelper
        $val = \App\Helpers\SecurityUploadHelper::validateFile($file, ['zip'], 50 * 1024 * 1024);
        if (!$val['valid']) {
            $this->jsonResponse(false, null, 'Berkas ZIP tidak valid: ' . $val['error'], 400);
            return;
        }

        $sessionTenantId = SessionManager::getTenantId();
        $operatorNpsn = null;
        if ($sessionTenantId) {
            $stmtNpsn = $db->prepare("SELECT npsn FROM core.tenants WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtNpsn->execute([$sessionTenantId]);
            $operatorNpsn = $stmtNpsn->fetchColumn();
            if (!$operatorNpsn) {
                $this->jsonResponse(false, null, 'Data sekolah login tidak ditemukan.', 400);
                return;
            }
        }

        $tempExtractDir = __DIR__ . '/../../../../storage/app/public/temp_bulk_' . microtime(true) . '_' . bin2hex(random_bytes(5));
        if (!is_dir($tempExtractDir)) {
            mkdir($tempExtractDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            $this->recursiveRmdir($tempExtractDir);
            $this->jsonResponse(false, null, 'Gagal membuka atau membaca file ZIP.', 400);
            return;
        }

        // Zip Slip Protection (CWE-29): Extract files safely using getFromIndex() without extractTo()
        $realTempDir = realpath($tempExtractDir);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            
            // Ignore directory entries
            if (str_ends_with($entryName, '/') || str_ends_with($entryName, '\\')) {
                continue;
            }

            // Extract pure base filename to prevent directory traversal
            $cleanFilename = basename($entryName);
            if (empty($cleanFilename) || str_starts_with($cleanFilename, '.') || str_contains($entryName, '__MACOSX')) {
                continue;
            }

            // Allow only image extensions
            $ext = strtolower(pathinfo($cleanFilename, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }

            $targetPath = $tempExtractDir . DIRECTORY_SEPARATOR . $cleanFilename;

            // Stream / extract file content safely
            $fileContent = $zip->getFromIndex($i);
            if ($fileContent === false) {
                continue;
            }

            file_put_contents($targetPath, $fileContent);

            // Double check realpath bounds
            $realTarget = realpath($targetPath);
            if ($realTarget === false || !str_starts_with($realTarget, $realTempDir)) {
                @unlink($targetPath);
                continue;
            }
        }
        $zip->close();

        $allFiles = [];
        $this->scanAllFiles($tempExtractDir, $allFiles);

        $successCount = 0;
        $failedCount = 0;
        $report = [];

        foreach ($allFiles as $filePath) {
            $filename = basename($filePath);

            if (str_starts_with($filename, '.') || str_contains($filePath, '__MACOSX')) {
                continue;
            }

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

            if ($sessionTenantId && $npsn !== $operatorNpsn) {
                $failedCount++;
                $report[] = [
                    'file' => $filename,
                    'status' => 'failed',
                    'message' => "NPSN {$npsn} tidak sesuai dengan NPSN sekolah Anda."
                ];
                continue;
            }

            $stmtSiswa = $db->prepare("
                SELECT s.id AS siswa_id, s.tenant_id, s.foto_url AS foto_profil
                FROM siswa.siswa s
                JOIN core.tenants t ON s.tenant_id = t.id
                WHERE t.npsn = :npsn
                  AND s.nisn = :nisn
                  AND (s.tenant_id = :session_tenant_id OR :session_tenant_id IS NULL)
                  AND (s.is_active = true OR s.is_active IS NULL)
                LIMIT 1
            ");
            $stmtSiswa->execute([
                'npsn' => $npsn,
                'nisn' => $nisn,
                'session_tenant_id' => $sessionTenantId
            ]);
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

            $baseDir = __DIR__ . '/../../../../storage/app/public/uploads/' . $tenantId . '/' . $siswaId . '/';
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }

            $newFileName = sha1_file($filePath) . '.' . $ext;
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

            $oldRelativePath = $siswa['foto_profil'] ?? '';
            $trustedPrefix = 'uploads/' . $tenantId . '/' . $siswaId . '/';
            if (!empty($oldRelativePath) && str_starts_with($oldRelativePath, $trustedPrefix)) {
                $oldAbsPath = __DIR__ . '/../../../../storage/app/public/' . $oldRelativePath;
                if (file_exists($oldAbsPath)) {
                    @unlink($oldAbsPath);
                }
            }

            $stmtUpdateS = $db->prepare("UPDATE siswa.siswa SET foto_url = :foto_url, updated_at = CURRENT_TIMESTAMP WHERE id::text = :siswa_id AND tenant_id = :tenant_id");
            $stmtUpdateS->execute([
                'foto_url'  => 'uploads/' . $tenantId . '/' . $siswaId . '/' . $newFileName,
                'siswa_id'  => $siswaId,
                'tenant_id' => $tenantId
            ]);

            // Sync with siswa.dokumen
            $stmtDok = $db->prepare("SELECT id FROM siswa.dokumen WHERE siswa_id = :siswa_id::uuid AND jenis_dokumen = 'Foto Profil' AND tenant_id = :tenant_id LIMIT 1");
            $stmtDok->execute([
                'siswa_id'  => $siswaId,
                'tenant_id' => $tenantId
            ]);
            $existingDokId = $stmtDok->fetchColumn();

            if ($existingDokId) {
                $stmtUpdateDok = $db->prepare("UPDATE siswa.dokumen SET url_file = :url_file, nama_file = :nama_file, updated_at = CURRENT_TIMESTAMP WHERE id = :id::uuid AND tenant_id = :tenant_id");
                $stmtUpdateDok->execute([
                    'url_file'  => 'uploads/' . $tenantId . '/' . $siswaId . '/' . $newFileName,
                    'nama_file' => $filename,
                    'id'        => $existingDokId,
                    'tenant_id' => $tenantId
                ]);
            } else {
                $stmtInsertDok = $db->prepare("
                    INSERT INTO siswa.dokumen 
                        (id, siswa_id, tenant_id, jenis_dokumen, nama_file, url_file, keterangan, created_at, updated_at) 
                    VALUES 
                        (gen_random_uuid(), :siswa_id::uuid, :tenant_id::uuid, 'Foto Profil', :nama_file, :url_file, 'Foto Profil Siswa', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $stmtInsertDok->execute([
                    'siswa_id'  => $siswaId,
                    'tenant_id' => $tenantId,
                    'nama_file' => $filename,
                    'url_file'  => 'uploads/' . $tenantId . '/' . $siswaId . '/' . $newFileName
                ]);
            }

            $successCount++;
            $report[] = [
                'file' => $filename,
                'status' => 'success',
                'message' => 'Foto profil berhasil dipasang.'
            ];
        }

        $this->recursiveRmdir($tempExtractDir);

        $this->jsonResponse(true, [
            'total_files' => count($allFiles),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'report' => $report
        ]);
    }

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
