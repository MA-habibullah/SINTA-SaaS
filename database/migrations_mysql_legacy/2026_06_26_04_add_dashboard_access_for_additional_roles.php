<?php
/**
 * Migration: Add Dashboard Access for Additional Roles
 * 
 * Memberikan akses ke menu Dashboard (ID 1) untuk role guru_bk (20), guru_pembina (21), dan kesiswaan (22).
 */

return [
    'up' => function(PDO $pdo): void {
        $pdo->exec("INSERT IGNORE INTO role_menu_access (role_id, menu_id, tenant_id) VALUES 
            (20, 1, '00000000-0000-0000-0000-000000000000'),
            (21, 1, '00000000-0000-0000-0000-000000000000'),
            (22, 1, '00000000-0000-0000-0000-000000000000')
        ");
        echo "  OK Dashboard access for guru_bk, guru_pembina, and kesiswaan added.\n";
    },

    'down' => function(PDO $pdo): void {
        $pdo->exec("DELETE FROM role_menu_access WHERE menu_id = 1 AND role_id IN (20, 21, 22) AND tenant_id = '00000000-0000-0000-0000-000000000000'");
        echo "  OK Rollback dashboard access for additional roles completed.\n";
    }
];
