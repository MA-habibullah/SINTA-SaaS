<?php

namespace App\Helpers;

use App\Core\StorageGuard;
use Exception;
use InvalidArgumentException;

class SecurityUploadHelper
{
    /**
     * Whitelist MIME types dan ekstensi yang diizinkan untuk gambar
     */
    private const ALLOWED_IMAGE_MIMES = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif']
    ];

    /**
     * Whitelist MIME types dan ekstensi yang diizinkan untuk dokumen
     */
    private const ALLOWED_DOCUMENT_MIMES = [
        'pdf'  => ['application/pdf'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'xls'  => ['application/vnd.ms-excel'],
        'csv'  => ['text/csv', 'text/plain', 'application/csv'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'doc'  => ['application/msword'],
        'zip'  => ['application/zip', 'application/x-zip-compressed']
    ];

    /**
     * Magic Bytes / File Signatures
     */
    private const MAGIC_BYTES = [
        'jpg'  => ["\xFF\xD8\xFF"],
        'jpeg' => ["\xFF\xD8\xFF"],
        'png'  => ["\x89PNG\r\n\x1a\n"],
        'webp' => ["RIFF"],
        'pdf'  => ["%PDF-"],
        'zip'  => ["PK\x03\x04"],
        'xlsx' => ["PK\x03\x04"],
        'docx' => ["PK\x03\x04"]
    ];

    /**
     * Validasi berkas upload secara menyeluruh (MIME type, Magic Bytes, ekstensi, batas ukuran)
     *
     * @param array $file Array dari $_FILES[$key]
     * @param array $allowedExtensions Array ekstensi yang diizinkan (misal ['jpg', 'png', 'pdf'])
     * @param int $maxSizeBytes Batas ukuran file dalam bytes (default 10MB)
     * @return array ['valid' => bool, 'error' => ?string, 'extension' => ?string, 'mime' => ?string]
     */
    public static function validateFile(array $file, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'], int $maxSizeBytes = 10485760): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            return [
                'valid' => false,
                'error' => self::getUploadErrorMessage($errorCode),
                'extension' => null,
                'mime' => null
            ];
        }

        $tmpPath = $file['tmp_name'] ?? '';
        $origName = $file['name'] ?? '';
        $size = (int)($file['size'] ?? 0);

        if (!is_uploaded_file($tmpPath) || !file_exists($tmpPath)) {
            return [
                'valid' => false,
                'error' => 'Berkas yang diunggah tidak valid atau tidak ditemukan di server.',
                'extension' => null,
                'mime' => null
            ];
        }

        if ($size > $maxSizeBytes) {
            $maxMb = round($maxSizeBytes / (1024 * 1024), 1);
            return [
                'valid' => false,
                'error' => "Ukuran berkas ({$size} bytes) melebihi batas maksimal server ({$maxMb} MB).",
                'extension' => null,
                'mime' => null
            ];
        }

        // 1. Validasi Ekstensi (Whitelist & No Double Extension)
        $cleanOrigName = strtolower($origName);
        if (preg_match('/\.(php|phtml|php3|php4|php5|php7|phps|phar|inc|pl|py|cgi|asp|aspx|sh|bash|exe|bat|cmd|vbs|js|html|htm)\./i', $cleanOrigName)) {
            return [
                'valid' => false,
                'error' => 'Berkas mengandung ekstensi ganda yang berpotensi berbahaya.',
                'extension' => null,
                'mime' => null
            ];
        }

        $ext = strtolower(pathinfo($cleanOrigName, PATHINFO_EXTENSION));
        $allowedExtensions = array_map('strtolower', $allowedExtensions);
        if (!in_array($ext, $allowedExtensions, true)) {
            $allowedStr = implode(', ', $allowedExtensions);
            return [
                'valid' => false,
                'error' => "Format berkas (.{$ext}) tidak diizinkan. Ekstensi yang diperbolehkan: {$allowedStr}.",
                'extension' => null,
                'mime' => null
            ];
        }

        // 2. Validasi MIME Type via finfo_file
        $detectedMime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detectedMime = finfo_file($finfo, $tmpPath) ?: $detectedMime;
                finfo_close($finfo);
            }
        } elseif (function_exists('mime_content_type')) {
            $detectedMime = mime_content_type($tmpPath) ?: $detectedMime;
        }

        $allMimes = array_merge(self::ALLOWED_IMAGE_MIMES, self::ALLOWED_DOCUMENT_MIMES);
        $expectedMimes = $allMimes[$ext] ?? [];

        if (!empty($expectedMimes) && !in_array($detectedMime, $expectedMimes, true)) {
            return [
                'valid' => false,
                'error' => "Konten berkas (MIME: {$detectedMime}) tidak cocok dengan ekstensi (.{$ext}). Kemungkinan berkas berbahaya.",
                'extension' => $ext,
                'mime' => $detectedMime
            ];
        }

        // 3. Validasi Magic Bytes
        if (isset(self::MAGIC_BYTES[$ext])) {
            $handle = fopen($tmpPath, 'rb');
            if ($handle) {
                $bytes = fread($handle, 16);
                fclose($handle);
                $matchedMagic = false;
                foreach (self::MAGIC_BYTES[$ext] as $magic) {
                    if (str_starts_with($bytes, $magic)) {
                        $matchedMagic = true;
                        break;
                    }
                }
                if (!$matchedMagic) {
                    return [
                        'valid' => false,
                        'error' => "Header biner berkas (.{$ext}) tidak valid atau rusak.",
                        'extension' => $ext,
                        'mime' => $detectedMime
                    ];
                }
            }
        }

        return [
            'valid' => true,
            'error' => null,
            'extension' => $ext,
            'mime' => $detectedMime
        ];
    }

    /**
     * Memproses upload berkas secara aman, men-generate nama acak UUID, dan menyimpannya di direktori terisolasi
     *
     * @param array $file Array $_FILES[$key]
     * @param string $targetDir Direktori absolut tujuan penyimpanan
     * @param array $allowedExtensions Whitelist ekstensi
     * @param int $maxSizeBytes Batas ukuran file
     * @param ?string $tenantId ID Tenant untuk kuota storage
     * @return array ['success' => bool, 'fileName' => ?string, 'filePath' => ?string, 'fileSize' => int, 'error' => ?string]
     */
    public static function processSecureUpload(
        array $file,
        string $targetDir,
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        int $maxSizeBytes = 10485760,
        ?string $tenantId = null
    ): array {
        $validation = self::validateFile($file, $allowedExtensions, $maxSizeBytes);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'fileName' => null,
                'filePath' => null,
                'fileSize' => 0,
                'error' => $validation['error']
            ];
        }

        $size = (int)$file['size'];
        if (!empty($tenantId) && class_exists(StorageGuard::class)) {
            if (!StorageGuard::checkStorageLimit($tenantId, $size)) {
                return [
                    'success' => false,
                    'fileName' => null,
                    'filePath' => null,
                    'fileSize' => 0,
                    'error' => 'Kapasitas penyimpanan sekolah penuh (melebihi limit kuota paket).'
                ];
            }
        }

        // Pastikan direktori tujuan ada dan terproteksi
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        self::protectUploadDirectory($targetDir);

        $ext = $validation['extension'];
        $secureFileName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetFilePath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $secureFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            return [
                'success' => false,
                'fileName' => null,
                'filePath' => null,
                'fileSize' => 0,
                'error' => 'Gagal memindahkan berkas ke penyimpanan server.'
            ];
        }

        chmod($targetFilePath, 0644);

        return [
            'success' => true,
            'fileName' => $secureFileName,
            'filePath' => $targetFilePath,
            'fileSize' => $size,
            'error' => null
        ];
    }

    /**
     * Melindungi direktori upload dari eksekusi skrip PHP
     */
    public static function protectUploadDirectory(string $dir): void
    {
        $htaccess = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            $content = "# Proteksi Eksekusi Skrip pada Direktori Unggahan\n"
                     . "<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|phps|phar|inc|cgi|pl|py|asp|aspx|sh|bash|exe|bat|cmd|vbs|js)$\">\n"
                     . "    Order Allow,Deny\n"
                     . "    Deny from all\n"
                     . "</FilesMatch>\n"
                     . "Options -ExecCGI -Indexes\n"
                     . "php_flag engine off\n";
            @file_put_contents($htaccess, $content);
        }
    }

    private static function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE   => 'Ukuran berkas melebihi batas upload_max_filesize di php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'Ukuran berkas melebihi batas MAX_FILE_SIZE formulir.',
            UPLOAD_ERR_PARTIAL    => 'Berkas hanya terunggah sebagian (koneksi terputus).',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada berkas yang diunggah.',
            UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara server tidak ditemukan.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis berkas ke disk penyimpanan server.',
            UPLOAD_ERR_EXTENSION  => 'Unggahan berkas dihentikan oleh ekstensi PHP.',
            default               => "Terjadi kesalahan unggah berkas (Kode: {$errorCode})."
        };
    }
}
