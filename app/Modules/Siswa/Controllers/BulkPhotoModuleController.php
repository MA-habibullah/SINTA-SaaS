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

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(false, null, 'Gagal mengunggah file ZIP.', 400);
        }

        $tmpPath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'zip') {
            $this->jsonResponse(false, null, 'Format file tidak didukung. Mohon unggah file ZIP (.zip).', 400);
        }

        $sessionTenantId = SessionManager::getTenantId();
        $operatorNpsn = null;
        if ($sessionTenantId) {
            $stmtNpsn = $db->prepare("SELECT npsn FROM core.tenants WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtNpsn->execute([$sessionTenantId]);
            $operatorNpsn = $stmtNpsn->fetchColumn();
            if (!$operatorNpsn) {
                $this->jsonResponse(false, null, 'Data sekolah login tidak ditemukan.', 400);
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
        }

        $zip->extractTo($tempExtractDir);
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
                SELECT s.id AS siswa_id, s.tenant_id, s.foto_url AS foto_profil, d.file_sizes, d.id AS id_dokumen
                FROM siswa.siswa s
                JOIN core.tenants t ON s.tenant_id = t.id
                LEFT JOIN siswa.dokumen d ON (s.id = d.siswa_id OR s.id::text = d.siswa_id::text)
                WHERE t.npsn = :npsn
                  AND s.nisn = :nisn
                  AND (s.is_active = true OR s.is_active IS NULL)
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

            $stmtUpdateS = $db->prepare("UPDATE siswa.siswa SET foto_url = :foto_url, updated_at = NOW() WHERE id::text = :siswa_id");
            $stmtUpdateS->execute([
                'foto_url' => 'uploads/' . $tenantId . '/' . $siswaId . '/' . $newFileName,
                'siswa_id' => $siswaId
            ]);

            $oldSizes = [];
            if (!empty($siswa['file_sizes'])) {
                $oldSizes = json_decode($siswa['file_sizes'], true) ?: [];
            }
            $oldSizes['foto_profil'] = $fileSize;
            $fileSizesJson = json_encode($oldSizes);

            if (!empty($siswa['id_dokumen'])) {
                $stmtUpdateDok = $db->prepare("UPDATE siswa.dokumen SET file_sizes = :file_sizes WHERE id_siswa = :siswa_id");
                $stmtUpdateDok->execute([
                    'file_sizes' => $fileSizesJson,
                    'siswa_id' => $siswaId
                ]);
            } else {
                $stmtInsertDok = $db->prepare("
                    INSERT INTO siswa.dokumen 
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
