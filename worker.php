<?php
/**
 * CLI Background Job Worker
 * 
 * Command Line Interface tool to process background queue jobs.
 * Usage:
 *   php worker.php --run    (Memproses seluruh antrean pekerjaan)
 *   php worker.php --once   (Memproses satu pekerjaan saja)
 */

if (php_sapi_name() !== 'cli') {
    die("Error: Skrip ini hanya dapat dijalankan melalui PHP CLI (Command Line).\n");
}

// 1. Register PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Helpers\QueueManager;
use App\Jobs\JobDispatcher;

// 2. Parsing opsi argumen CLI
$args = $argv;
$runAll = in_array('--run', $args, true);
$runOnce = in_array('--once', $args, true);

if (!$runAll && !$runOnce) {
    echo "====================================================\n";
    echo "CLI Background Job Worker\n";
    echo "====================================================\n";
    echo "Penggunaan:\n";
    echo "  php worker.php --run    (Memproses seluruh antrean pekerjaan)\n";
    echo "  php worker.php --once   (Memproses satu pekerjaan saja)\n";
    echo "====================================================\n";
    exit(0);
}

echo "[Worker] [" . date('Y-m-d H:i:s') . "] Memulai background job worker...\n";

if ($runOnce) {
    $job = QueueManager::pop();
    if ($job) {
        processSingleJob($job);
    } else {
        echo "[Worker] Tidak ada pekerjaan pending di dalam antrean.\n";
    }
} elseif ($runAll) {
    $processedCount = 0;
    while (true) {
        $job = QueueManager::pop();
        if (!$job) {
            echo "[Worker] Antrean kosong. Total diproses: {$processedCount} pekerjaan.\n";
            break;
        }
        processSingleJob($job);
        $processedCount++;
    }
}

/**
 * Fungsi untuk mengeksekusi satu pekerjaan
 */
function processSingleJob(array $job): void {
    $id = $job['id'];
    $type = $job['job_type'];
    $tenant = $job['tenant_id'] ?? 'Global/System';
    
    echo "[Worker] [" . date('Y-m-d H:i:s') . "] Memproses Job #{$id} ({$type}) - Tenant: {$tenant}...\n";
    
    try {
        // Panggil dispatcher untuk memproses job logic
        JobDispatcher::dispatch($job);
        
        // Tandai sukses di database
        QueueManager::markCompleted($id);
        echo "[Worker] OK: Job #{$id} sukses diselesaikan.\n";
    } catch (\Throwable $e) {
        // Tandai gagal di database dan simpan pesan error
        QueueManager::markFailed($id, $e->getMessage());
        echo "[Worker] ERROR: Job #{$id} gagal diproses. Alasan: " . $e->getMessage() . "\n";
    }
}
