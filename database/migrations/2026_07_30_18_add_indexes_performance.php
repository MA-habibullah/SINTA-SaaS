<?php

/**
 * Migration PostgreSQL Schema 18: PERFORMANCE INDEXES
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== INSTALLING POSTGRESQL SCHEMA 18: PERFORMANCE INDEXES ===\n";

        $pdo->exec("
            -- siswa.siswa (login lookup)
            CREATE INDEX IF NOT EXISTS idx_siswa_tenant_nisn ON siswa.siswa (tenant_id, nisn);
            CREATE INDEX IF NOT EXISTS idx_siswa_is_active ON siswa.siswa (is_active);

            -- core.users (admin login)
            CREATE INDEX IF NOT EXISTS idx_users_email ON core.users (email);
            CREATE INDEX IF NOT EXISTS idx_users_tenant_email ON core.users (tenant_id, email);

            -- sistem
            CREATE INDEX IF NOT EXISTS idx_activity_tenant ON sistem.activity_logs (tenant_id);
            CREATE INDEX IF NOT EXISTS idx_activity_created ON sistem.activity_logs (created_at DESC);

        ");

        echo "- Performance Indexes Berhasil Dibuat.\n";
    },

    'down' => function (PDO $pdo): void {
        $pdo->exec("
            DROP INDEX IF EXISTS siswa.idx_siswa_tenant_nisn;
            DROP INDEX IF EXISTS siswa.idx_siswa_is_active;
            DROP INDEX IF EXISTS core.idx_users_email;
            DROP INDEX IF EXISTS core.idx_users_tenant_email;
            DROP INDEX IF EXISTS sistem.idx_activity_tenant;
            DROP INDEX IF EXISTS sistem.idx_activity_created;
        ");
        echo "- Performance Indexes Berhasil Di-drop.\n";
    }
];
