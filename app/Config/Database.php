<?php

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $connection = null;

    private string $host = 'localhost';
    private string $dbName = 'dapodik_spmb';
    private string $username = 'root';
    private string $password = ''; // Sesuaikan dengan password database Anda
    private string $charset = 'utf8mb4';

    /**
     * Get the singleton database connection instance.
     */
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            $instance = new self();
            self::$connection = $instance->connect();
        }
        return self::$connection;
    }

    /**
     * Establish PDO Connection
     */
    private function connect(): PDO {
        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";
        
        $options = [
            // 1. Lempar exception jika terjadi error (Secure by Design)
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            
            // 2. Nonaktifkan Emulated Prepares (Wajib untuk mencegah SQL Injection & menjaga keaslian tipe data)
            PDO::ATTR_EMULATE_PREPARES => false,
            
            // 3. Set hasil fetch default menjadi associative array
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            
            // 4. Pastikan koneksi menggunakan persistent connection jika diperlukan (opsional, default false)
            PDO::ATTR_PERSISTENT => false,
        ];

        try {
            return new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Secure by Design: Jangan tampilkan pesan error detail (termasuk kredensial) ke publik
            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed. Please check the logs for details.");
        }
    }
}
