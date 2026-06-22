<?php

namespace App\Jobs;

use App\Config\Database;
use Exception;
use PDO;

class JobDispatcher {

    /**
     * Jalankan pekerjaan berdasarkan tipe pekerjaan
     * 
     * @throws Exception Jika pekerjaan gagal diproses
     */
    public static function dispatch(array $job): void {
        $jobType = $job['job_type'];
        $payload = $job['payload'];
        $tenantId = $job['tenant_id'];

        switch ($jobType) {
            case 'DEMO_SYNC':
                self::processDemoSync($payload, $tenantId);
                break;

            case 'DEMO_EMAIL':
                self::processDemoEmail($payload, $tenantId);
                break;

            case 'CLEANUP_SESSIONS':
                self::processCleanupSessions($payload, $tenantId);
                break;

            default:
                throw new Exception("Tipe pekerjaan '{$jobType}' tidak dikenali oleh sistem.");
        }
    }

    /**
     * Simulasi Sinkronisasi Data Pusdatin
     */
    private static function processDemoSync(array $payload, ?string $tenantId): void {
        $forceFail = $payload['force_fail'] ?? false;
        
        // Simulasi proses berat (delay 5 detik)
        sleep(5);

        if ($forceFail) {
            throw new Exception("Koneksi gagal: Server Kemendikbudristek (Pusdatin) tidak merespons. (Error Simulasi)");
        }
        
        error_log("JobDispatcher: DEMO_SYNC berhasil dijalankan untuk sekolah ID: " . ($tenantId ?? 'Global'));
    }

    /**
     * Simulasi Pengiriman Email Blast Massal
     */
    private static function processDemoEmail(array $payload, ?string $tenantId): void {
        $recipientCount = $payload['recipient_count'] ?? 50;
        $subject = $payload['subject'] ?? 'Informasi Pembayaran Pendaftaran SPMB';

        if (empty($subject)) {
            throw new Exception("Validasi Gagal: Subjek email kosong.");
        }

        // Simulasi kirim email blast (delay 3 detik)
        sleep(3);

        error_log("JobDispatcher: DEMO_EMAIL sukses mengirim {$recipientCount} email resmi untuk sekolah ID: " . ($tenantId ?? 'Global'));
    }

    /**
     * Pembersihan otomatis log riwayat sesi lama
     */
    private static function processCleanupSessions(array $payload, ?string $tenantId): void {
        $dateLimit = $payload['date_limit'] ?? null;
        
        if (empty($dateLimit)) {
            // Default bersihkan sesi sebelum 7 hari yang lalu
            $dateLimit = date('Y-m-d', strtotime('-7 days'));
        }

        $db = Database::getConnection();
        
        if (!empty($tenantId)) {
            $stmt = $db->prepare("
                DELETE FROM `active_sessions` 
                WHERE `tanggal_login` <= :date_limit AND `tenant_id` = :tenant_id
            ");
            $stmt->execute([
                'date_limit' => $dateLimit,
                'tenant_id'  => $tenantId
            ]);
        } else {
            $stmt = $db->prepare("
                DELETE FROM `active_sessions` 
                WHERE `tanggal_login` <= :date_limit
            ");
            $stmt->execute([
                'date_limit' => $dateLimit
            ]);
        }

        error_log("JobDispatcher: CLEANUP_SESSIONS selesai. Log sesi lama sebelum tanggal {$dateLimit} telah dibersihkan.");
    }
}
