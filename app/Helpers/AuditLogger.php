<?php
namespace App\Helpers;

use App\Config\Database;
use PDO;

/**
 * AuditLogger Helper
 * Merekam aktivitas pengguna/petugas secara otomatis & terstruktur
 */
class AuditLogger {
    public static function log(
        string $aksi,
        string $tabelTarget,
        string $targetId,
        $dataSebelum = null,
        $dataSesudah = null,
        ?string $keterangan = null,
        ?string $tenantId = null,
        ?string $userId = null
    ): bool {
        try {
            $db = Database::getConnection();

            $tid = $tenantId ?: ($_SESSION['tenant_id'] ?? '00000000-0000-0000-0000-000000000000');
            $uid = $userId ?: ($_SESSION['user_id'] ?? '');

            if (empty($uid)) {
                // Return silently if no user context
                return false;
            }

            $beforeStr = is_string($dataSebelum) ? $dataSebelum : ($dataSebelum !== null ? json_encode($dataSebelum) : null);
            $afterStr = is_string($dataSesudah) ? $dataSesudah : ($dataSesudah !== null ? json_encode($dataSesudah) : null);

            $stmt = $db->prepare("
                INSERT INTO transaksi_spp_audit_log (tenant_id, user_id, aksi, tabel_target, target_id, data_sebelum, data_sesudah, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([
                $tid,
                $uid,
                $aksi,
                $tabelTarget,
                $targetId,
                $beforeStr,
                $afterStr,
                $keterangan
            ]);
        } catch (\Throwable $e) {
            error_log("AuditLogger Error: " . $e->getMessage());
            return false;
        }
    }
}
