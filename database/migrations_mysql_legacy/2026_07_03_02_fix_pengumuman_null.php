<?php

return [
    'up' => function (PDO $pdo) {
        $queries = [
            // Allow Global (NULL) for Pengumuman
            "ALTER TABLE `pengumuman` MODIFY COLUMN `tenant_id` char(36) DEFAULT NULL",
            "ALTER TABLE `pengumuman` MODIFY COLUMN `kategori_id` char(36) DEFAULT NULL",
            
            // Allow Global (NULL) for Kategori Pengumuman
            "ALTER TABLE `kategori_pengumuman` MODIFY COLUMN `tenant_id` char(36) DEFAULT NULL"
        ];

        foreach ($queries as $sql) {
            try {
                $pdo->exec($sql);
            } catch (\PDOException $e) {
                error_log("Migration warning for pengumuman schema: " . $e->getMessage());
            }
        }
    },
    
    'down' => function (PDO $pdo) {
        // Rollback
    }
];
