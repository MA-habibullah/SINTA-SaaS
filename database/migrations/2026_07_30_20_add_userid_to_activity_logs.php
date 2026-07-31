<?php
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE sistem.activity_logs ADD COLUMN IF NOT EXISTS user_id UUID NULL");
        echo "- Kolom user_id berhasil ditambahkan ke tabel sistem.activity_logs.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE sistem.activity_logs DROP COLUMN IF EXISTS user_id");
        echo "- Kolom user_id berhasil dihapus dari tabel sistem.activity_logs.\n";
    },
];
