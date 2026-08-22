<?php
return [
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE kesiswaan.prestasi_siswa 
            ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE NULL;
        ");
        echo "- Kolom deleted_at pada kesiswaan.prestasi_siswa berhasil ditambahkan.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE kesiswaan.prestasi_siswa 
            DROP COLUMN IF EXISTS deleted_at;
        ");
    },
];
