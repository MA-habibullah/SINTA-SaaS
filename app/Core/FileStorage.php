<?php

namespace App\Core;

/**
 * FileStorage — Utility class terpusat untuk semua upload file.
 *
 * Konvensi standar path:
 *   storage/app/public/{modul}/{tenant_id}/{entity_id}/{sha1_hash}.{ext}
 */
class FileStorage
{
    private const STORAGE_ROOT = 'storage/app/public/';

    private const ALLOWED_EXT = [
        'default'    => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'],
        'image_only' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'pdf_only'   => ['pdf'],
        'doc'        => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'xlsx', 'xls'],
    ];

    /**
     * Simpan file dengan konvensi standar.
     * Format: storage/app/public/{modul}/{tenant_id}/{entity_id?}/{sha1}.{ext}
     */
    public static function store(
        string  $tmpPath,
        string  $modul,
        string  $tenantId,
        ?string $entityId     = null,
        string  $allowedGroup = 'default'
    ): ?string {
        if (!is_file($tmpPath) || !is_readable($tmpPath)) {
            return null;
        }

        $ext = self::resolveExtension($tmpPath, $allowedGroup);
        if ($ext === null) {
            return null;
        }

        $baseDir = self::getBaseDir();
        $relDir  = self::STORAGE_ROOT . $modul . '/' . $tenantId . '/';
        if ($entityId !== null && $entityId !== '') {
            $relDir .= $entityId . '/';
        }
        $absDir = $baseDir . '/' . $relDir;

        if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            error_log('[FileStorage::store] Gagal membuat direktori: ' . $absDir);
            return null;
        }

        $hash    = sha1_file($tmpPath);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if (!$detectedMime) {
            error_log('[FileStorage::store] Gagal mendeteksi MIME type file: ' . $tmpPath);
            return null;
        }

        $relPath = $relDir . $hash . '.' . $ext;
        $absPath = $baseDir . '/' . $relPath;

        // finfo_file Magic Bytes & MIME type verified above
        $moved = is_uploaded_file($tmpPath)
            ? move_uploaded_file($tmpPath, $absPath)
            : rename($tmpPath, $absPath);

        if (!$moved) {
            error_log('[FileStorage::store] Gagal memindahkan file ke: ' . $absPath);
            return null;
        }

        return $relPath;
    }

    /**
     * Hapus file fisik lama secara aman (anti path-traversal + validasi kepemilikan tenant).
     */
    public static function deleteOld(string $relativePath, string $tenantId): bool
    {
        if (empty($relativePath)) {
            return true;
        }

        $relativePath = ltrim($relativePath, '/\\');

        if (!str_contains($relativePath, $tenantId)) {
            error_log('[FileStorage::deleteOld][SECURITY] Path tidak mengandung tenant_id. Path: ' . $relativePath);
            return false;
        }

        $baseDir     = self::getBaseDir();
        $absPath     = realpath($baseDir . '/' . $relativePath);
        $storageRoot = realpath($baseDir . '/' . self::STORAGE_ROOT);

        if ($absPath === false || $storageRoot === false || !str_starts_with($absPath, $storageRoot)) {
            error_log('[FileStorage::deleteOld][SECURITY] Blocked path traversal. Path: ' . $relativePath);
            return false;
        }

        if (!is_file($absPath)) {
            return true;
        }

        if (!@unlink($absPath)) {
            error_log('[FileStorage::deleteOld] Gagal menghapus file: ' . $absPath);
            return false;
        }

        return true;
    }

    /**
     * Migrasi file dari path lama ke format standar baru.
     */
    public static function migrateOldPath(
        string  $oldRelativePath,
        string  $modul,
        string  $tenantId,
        ?string $entityId = null
    ): ?string {
        if (empty($oldRelativePath)) {
            return null;
        }

        $baseDir    = self::getBaseDir();
        $oldAbsPath = $baseDir . '/' . ltrim($oldRelativePath, '/');

        if (!is_file($oldAbsPath)) {
            error_log('[FileStorage::migrateOldPath] File tidak ditemukan: ' . $oldAbsPath);
            return null;
        }

        $ext = self::resolveExtension($oldAbsPath, 'default');
        if ($ext === null) {
            return null;
        }

        $relDir = self::STORAGE_ROOT . $modul . '/' . $tenantId . '/';
        if ($entityId !== null && $entityId !== '') {
            $relDir .= $entityId . '/';
        }
        $absDir = $baseDir . '/' . $relDir;

        if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            error_log('[FileStorage::migrateOldPath] Gagal membuat direktori: ' . $absDir);
            return null;
        }

        $hash       = sha1_file($oldAbsPath);
        $newRelPath = $relDir . $hash . '.' . $ext;
        $newAbsPath = $baseDir . '/' . $newRelPath;

        if (!is_file($newAbsPath)) {
            if (!copy($oldAbsPath, $newAbsPath)) {
                error_log('[FileStorage::migrateOldPath] Gagal copy file ke: ' . $newAbsPath);
                return null;
            }
        }

        @unlink($oldAbsPath);

        return $newRelPath;
    }

    /**
     * Bangun URL aman untuk frontend melalui SecureFileController.
     */
    public static function secureUrl(string $relativePath, string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        return $baseUrl . '/api/v1/file/serve?path=' . urlencode(ltrim($relativePath, '/'));
    }

    // ─── Private Helpers ────────────────────────────────────────────────────

    private static function resolveExtension(string $filePath, string $allowedGroup): ?string
    {
        $allowedExts = self::ALLOWED_EXT[$allowedGroup] ?? self::ALLOWED_EXT['default'];

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $mimeToExt = [
            'image/jpeg'                                                         => 'jpg',
            'image/png'                                                          => 'png',
            'image/webp'                                                         => 'webp',
            'image/gif'                                                          => 'gif',
            'application/pdf'                                                    => 'pdf',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel'                                           => 'xls',
        ];

        $ext = $mimeToExt[$mimeType] ?? null;
        if ($ext === null || !in_array($ext, $allowedExts, true)) {
            error_log('[FileStorage::resolveExtension] MIME tidak diizinkan: ' . $mimeType);
            return null;
        }

        return $ext;
    }

    private static function getBaseDir(): string
    {
        return defined('BASE_DIR')
            ? rtrim(\BASE_DIR, '/\\')
            : rtrim(realpath(__DIR__ . '/../../'), '/\\');
    }
}