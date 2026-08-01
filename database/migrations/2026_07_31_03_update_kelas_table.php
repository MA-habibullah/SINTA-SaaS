<?php

return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE akademik.kelas ADD COLUMN IF NOT EXISTS kode_kelas VARCHAR(100) NULL");
        $pdo->exec("ALTER TABLE akademik.kelas ADD COLUMN IF NOT EXISTS id_jenjang VARCHAR(100) NULL");
        $pdo->exec("ALTER TABLE akademik.kelas ADD COLUMN IF NOT EXISTS id_jurusan VARCHAR(100) NULL");
        echo "- Kolom kode_kelas, id_jenjang, id_jurusan berhasil ditambahkan ke akademik.kelas.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE akademik.kelas DROP COLUMN IF EXISTS kode_kelas");
        $pdo->exec("ALTER TABLE akademik.kelas DROP COLUMN IF EXISTS id_jenjang");
        $pdo->exec("ALTER TABLE akademik.kelas DROP COLUMN IF EXISTS id_jurusan");
    },
];
