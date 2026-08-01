<?php

return [
    'up' => function (PDO $pdo): void {
        // 1. sistem.active_sessions
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sistem.active_sessions (
                id VARCHAR(255) PRIMARY KEY,
                tenant_id UUID REFERENCES core.tenants(id) ON DELETE CASCADE,
                user_id UUID,
                ip_address VARCHAR(45),
                user_agent TEXT,
                tanggal_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 2. sistem.session_audit_logs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sistem.session_audit_logs (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID REFERENCES core.tenants(id) ON DELETE CASCADE,
                user_id UUID,
                user_role VARCHAR(100),
                event_type VARCHAR(100),
                ip_address VARCHAR(45),
                user_agent TEXT,
                status VARCHAR(50) DEFAULT 'success',
                detail TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 3. sistem.queue_jobs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sistem.queue_jobs (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID REFERENCES core.tenants(id) ON DELETE CASCADE,
                queue VARCHAR(255) DEFAULT 'default',
                payload JSONB,
                attempts INT DEFAULT 0,
                status VARCHAR(50) DEFAULT 'pending',
                job_type VARCHAR(100),
                error_message TEXT,
                available_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 4. sistem.user_access_overrides
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sistem.user_access_overrides (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID REFERENCES core.tenants(id) ON DELETE CASCADE,
                user_id UUID NOT NULL,
                menu_id UUID REFERENCES core.menus(id) ON DELETE CASCADE,
                is_allowed BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_user_menu UNIQUE (user_id, menu_id)
            );
        ");

        // 5. core.documents
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS core.documents (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id UUID REFERENCES core.tenants(id) ON DELETE CASCADE,
                title VARCHAR(255) NOT NULL,
                file_path TEXT NOT NULL,
                file_size BIGINT DEFAULT 0,
                mime_type VARCHAR(100),
                status VARCHAR(50) DEFAULT 'processed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        echo "- Created system utility tables (sistem.active_sessions, sistem.session_audit_logs, sistem.queue_jobs, sistem.user_access_overrides, core.documents).\n";
    },
    'down' => function (PDO $pdo): void {
        // Rollback
        $pdo->exec("DROP TABLE IF EXISTS core.documents CASCADE;");
        $pdo->exec("DROP TABLE IF EXISTS sistem.user_access_overrides CASCADE;");
        $pdo->exec("DROP TABLE IF EXISTS sistem.queue_jobs CASCADE;");
        $pdo->exec("DROP TABLE IF EXISTS sistem.session_audit_logs CASCADE;");
        $pdo->exec("DROP TABLE IF EXISTS sistem.active_sessions CASCADE;");
    },
];
