<?php
return [
    'up' => function (PDO $pdo): void {
        // Ubah tipe kolom jalur_masuk_id pada tracer.riwayat_kuliah menjadi VARCHAR(64) agar kompatibel dengan UUID
        $pdo->exec("
            ALTER TABLE tracer.riwayat_kuliah 
            ALTER COLUMN jalur_masuk_id TYPE VARCHAR(64) USING (jalur_masuk_id::text);
        ");
        echo "- Kolom tracer.riwayat_kuliah.jalur_masuk_id berhasil diubah menjadi VARCHAR(64).\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("
            ALTER TABLE tracer.riwayat_kuliah 
            ALTER COLUMN jalur_masuk_id TYPE INTEGER USING (NULL);
        ");
    },
];
