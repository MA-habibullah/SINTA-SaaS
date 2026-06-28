<?php
/**
 * Database Migration Runner (CLI-Only)
 * Mengelola eksekusi naik (up) dan turun (rollback) dari skema tabel database.
 */

// Keamanan: Tolak eksekusi jika diakses dari web browser (Secure by Design)
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("Forbidden: Migrations can only be run via CLI.\n");
}

// 1. PSR-4 Autoloader
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

use App\Config\Database;

try {
    $pdo = Database::getConnection();
    echo "=========================================\n";
    echo "Koneksi Database Berhasil Diperoleh.\n";
    echo "=========================================\n";

    // 2. Buat tabel log pencatat migrasi jika belum ada di database
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");

    // Tentukan direktori letak file migrasi
    $migrationsDir = __DIR__ . '/database/migrations';
    if (!is_dir($migrationsDir)) {
        mkdir($migrationsDir, 0755, true);
    }

    // Ambil daftar file migrasi dan urutkan
    $files = glob($migrationsDir . '/*.php');
    sort($files); 

    // Ambil data migrasi yang sudah pernah terdaftar/dieksekusi
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Dapatkan aksi dari argumen terminal (default: up)
    $action = $argv[1] ?? 'up';

    if ($action === 'fresh') {
        echo "=========================================\n";
        echo "PERINGATAN: Menjalankan Perintah FRESH!\n";
        echo "Semua tabel aplikasi akan dihapus & di-migrate ulang.\n";
        echo "=========================================\n";

        // Nonaktifkan pemeriksaan foreign key sementara untuk drop aman
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Ambil semua tabel secara dinamis
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`;");
            echo "Menghapus tabel: {$table}...\n";
        }

        // Aktifkan kembali pemeriksaan foreign key
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "Semua tabel berhasil dihapus.\n";
        echo "Menginisialisasi ulang database...\n";

        // Buat kembali tabel log migrations
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ");

        // Reset list migrasi yang tereksekusi agar memicu migration ulang dari awal
        $executedMigrations = [];
        $action = 'up';
    }

    if ($action === 'rollback') {
        // Jalankan aksi rollback (down) untuk file migrasi terakhir
        if (empty($executedMigrations)) {
            echo "Informasi: Belum ada migrasi yang tercatat untuk di-rollback.\n";
            exit;
        }

        $lastMigration = end($executedMigrations);
        $filePath = $migrationsDir . '/' . $lastMigration;

        if (file_exists($filePath)) {
            echo "Memproses Rollback: {$lastMigration}...\n";
            
            $migrationData = require $filePath;
            if (isset($migrationData['down']) && is_callable($migrationData['down'])) {
                // Jalankan fungsi down
                $migrationData['down']($pdo);
                
                // Hapus dari catatan log migrasi
                $stmt = $pdo->prepare("DELETE FROM migrations WHERE migration = :migration");
                $stmt->execute(['migration' => $lastMigration]);
                
                echo "Sukses: Rollback untuk {$lastMigration} selesai.\n";
            } else {
                echo "Galat: File migrasi tidak menyediakan fungsi 'down'.\n";
            }
        } else {
            echo "Galat: File fisik migrasi tidak ditemukan: {$lastMigration}\n";
        }
    } else {
        // Jalankan aksi migrasi baru (up)
        $newMigrations = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $executedMigrations)) {
                $newMigrations[] = $file;
            }
        }

        if (empty($newMigrations)) {
            echo "Informasi: Database sudah mutakhir. Tidak ada migrasi baru.\n";
            exit;
        }

        foreach ($newMigrations as $file) {
            $filename = basename($file);
            echo "Menjalankan Migrasi: {$filename}...\n";

            $migrationData = require $file;
            if (isset($migrationData['up']) && is_callable($migrationData['up'])) {
                // Eksekusi fungsi up
                $migrationData['up']($pdo);
                
                // Catat dalam log migrations agar tidak dijalankan ulang
                $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
                $stmt->execute(['migration' => $filename]);
                
                echo "Sukses: Migrasi {$filename} selesai.\n";
            } else {
                echo "Galat: File migrasi tidak menyediakan fungsi 'up'.\n";
            }
        }
        echo "=========================================\n";
        echo "Sukses: Semua migrasi berhasil diterapkan.\n";
        echo "=========================================\n";
    }

} catch (\Throwable $e) {
    echo "GALAT MIGRASI: " . $e->getMessage() . "\n";
    exit(1);
}
