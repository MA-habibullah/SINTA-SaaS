<?php
/**
 * Migration: Update Identitas Sekolah Menu URL
 * 
 * Mengubah URL menu Identitas Sekolah (ID 14) dari '#' menjadi '/dapodik-spmb/sekolah/identitas'.
 */
return [

    'up' => function (PDO $pdo): void {
        $pdo->exec("
            UPDATE `menus` 
            SET `url` = '/dapodik-spmb/sekolah/identitas' 
            WHERE `id` = 14;
        ");
        echo "  OK Menu Identitas Sekolah berhasil diupdate ke /dapodik-spmb/sekolah/identitas.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            UPDATE `menus` 
            SET `url` = '#' 
            WHERE `id` = 14;
        ");
        echo "  OK Menu Identitas Sekolah berhasil di-rollback ke #.\n";
    },
];
