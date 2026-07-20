<?php

namespace App\Helpers;

class CacheInvalidator {
    /**
     * Clear Buku Induk, Rapor, and Transkrip cache for a given student
     */
    public static function clearStudentCache(string $siswaId, ?string $tenantId = null): void {
        if (empty($tenantId)) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->prepare("SELECT tenant_id FROM siswa WHERE id = ?");
                $stmt->execute([$siswaId]);
                $tenantId = $stmt->fetchColumn() ?: '';
            } catch (\Throwable) {
                return;
            }
        }
        if (empty($tenantId)) return;

        $archiveDir = dirname(__DIR__, 2) . "/storage/archive/{$tenantId}/{$siswaId}";
        $files = ['buku_induk.html', 'identitas_rapor.html', 'transkrip.html'];
        
        if (is_dir($archiveDir)) {
            $dh = opendir($archiveDir);
            if ($dh) {
                while (($file = readdir($dh)) !== false) {
                    if ($file === '.' || $file === '..') continue;
                    // Hapus buku_induk, identitas_rapor, transkrip, atau rapor semester
                    if (in_array($file, $files) || str_starts_with($file, 'rapor_')) {
                        @unlink("{$archiveDir}/{$file}");
                    }
                }
                closedir($dh);
            }
        }
    }
}
