<?php
return [
    'up' => function (PDO $pdo): void {
        // 1. Update bk.master_jalur_masuk
        $pdo->exec("
            ALTER TABLE bk.master_jalur_masuk 
            ADD COLUMN IF NOT EXISTS nama_jalur VARCHAR(255),
            ADD COLUMN IF NOT EXISTS kategori VARCHAR(100) DEFAULT 'SNBP';

            UPDATE bk.master_jalur_masuk 
            SET nama_jalur = COALESCE(nama_jalur, nama_master_jalur_masuk) 
            WHERE nama_jalur IS NULL AND nama_master_jalur_masuk IS NOT NULL;
        ");

        // Seed default jalur masuk into bk.master_jalur_masuk if empty
        $cnt = (int)$pdo->query("SELECT count(*) FROM bk.master_jalur_masuk")->fetchColumn();
        if ($cnt === 0) {
            $pdo->exec("
                INSERT INTO bk.master_jalur_masuk (id, tenant_id, nama_jalur, nama_master_jalur_masuk, kategori, deskripsi, is_active)
                VALUES 
                    (gen_random_uuid(), NULL, 'SNBP', 'SNBP', 'SNBP', 'Seleksi Nasional Berdasarkan Prestasi', TRUE),
                    (gen_random_uuid(), NULL, 'SNBT', 'SNBT', 'SNBT', 'Seleksi Nasional Berdasarkan Tes (UTBK)', TRUE),
                    (gen_random_uuid(), NULL, 'Mandiri PTN', 'Mandiri PTN', 'Mandiri', 'Seleksi Mandiri Perguruan Tinggi Negeri', TRUE),
                    (gen_random_uuid(), NULL, 'Kedinasan', 'Kedinasan', 'Kedinasan', 'Sekolah Kedinasan / Ikatan Dinas', TRUE),
                    (gen_random_uuid(), NULL, 'PTS / Swasta', 'PTS / Swasta', 'Lainnya', 'Seleksi Masuk Perguruan Tinggi Swasta', TRUE);
            ");
        }

        // 2. Update pdss.master_jalur_masuk
        $pdo->exec("
            ALTER TABLE pdss.master_jalur_masuk 
            ADD COLUMN IF NOT EXISTS tenant_id UUID,
            ADD COLUMN IF NOT EXISTS kategori VARCHAR(100) DEFAULT 'SNBP';
        ");

        echo "- Skema dan data master jalur masuk berhasil diperbarui.\n";
    },
    'down' => function (PDO $pdo): void {
        // Rollback opsional
    }
];
