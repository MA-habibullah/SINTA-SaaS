<?php

/**
 * Migration PostgreSQL: Pembaruan Nil/Sequential UUID Super Admin Role & User
 * Role: 00000000-0000-0000-0000-000000000001 -> a1f87c2b-9e43-4b6e-8d91-3c5e7b2a9d01
 * User: 00000000-0000-0000-0000-000000000002 -> b2e98d3c-0f54-4c7f-9e02-4d6f8c3b0e12
 * 
 * Format: return ['up' => closure, 'down' => closure]
 */

return [
    'up' => function (PDO $pdo): void {
        echo "=== MEMPERBARUI UUID ROLE & USER SUPER ADMIN KE UUID v4 ACAK AMAN ===\n";

        $oldRoleId = '00000000-0000-0000-0000-000000000001';
        $newRoleId = 'a1f87c2b-9e43-4b6e-8d91-3c5e7b2a9d01';

        $oldUserId = '00000000-0000-0000-0000-000000000002';
        $newUserId = 'b2e98d3c-0f54-4c7f-9e02-4d6f8c3b0e12';

        $globalTenantId = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';

        // 1. Tangani Unique Constraint pada core.roles (nama_role)
        $pdo->exec("UPDATE core.roles SET nama_role = 'super_admin_legacy' WHERE id = '$oldRoleId'::uuid;");

        // Pastikan Role Baru Tersedia di core.roles
        $pdo->exec("
            INSERT INTO core.roles (id, nama_role, deskripsi, created_at, updated_at)
            VALUES ('$newRoleId'::uuid, 'super_admin', 'Administrator tertinggi untuk manajemen platform SaaS', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON CONFLICT (id) DO NOTHING;
        ");

        // Update role_menu_access ke newRoleId
        $pdo->exec("
            UPDATE core.role_menu_access 
            SET role_id = '$newRoleId'::uuid 
            WHERE role_id = '$oldRoleId'::uuid;
        ");

        // Update user_roles ke newRoleId
        $pdo->exec("
            UPDATE core.user_roles 
            SET role_id = '$newRoleId'::uuid 
            WHERE role_id = '$oldRoleId'::uuid;
        ");

        // 2. Pastikan User Super Admin Baru Tersedia di core.users
        $stmtOldUser = $pdo->query("SELECT * FROM core.users WHERE id = '$oldUserId'::uuid");
        $oldUser = $stmtOldUser->fetch(PDO::FETCH_ASSOC);

        if ($oldUser) {
            $passwordHash = $oldUser['password_hash'] ?: password_hash('superadmin123', PASSWORD_BCRYPT);
            $namaLengkap = $oldUser['nama_lengkap'] ?: 'Administrator Platform';
            $email = $oldUser['email'] ?: 'superadmin@sinta.com';
            $username = $oldUser['username'] ?: 'superadmin';

            // Lepaskan constraint unik pada user lama
            $pdo->exec("UPDATE core.users SET username = 'superadmin_legacy', email = 'superadmin_legacy@sinta.com' WHERE id = '$oldUserId'::uuid;");

            $pdo->exec("
                INSERT INTO core.users (id, tenant_id, role_id, username, email, password_hash, nama_lengkap, is_active, created_at, updated_at)
                VALUES ('$newUserId'::uuid, '$globalTenantId'::uuid, '$newRoleId'::uuid, '$username', '$email', '$passwordHash', '$namaLengkap', true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT (id) DO NOTHING;
            ");

            // Update referensi relasional dari oldUserId ke newUserId
            $pdo->exec("UPDATE core.user_roles SET user_id = '$newUserId'::uuid WHERE user_id = '$oldUserId'::uuid;");
            $pdo->exec("UPDATE core.user_menu_access SET user_id = '$newUserId'::uuid WHERE user_id = '$oldUserId'::uuid;");
            
            // Hapus user lama
            $pdo->exec("DELETE FROM core.users WHERE id = '$oldUserId'::uuid;");
        } else {
            $passwordHash = password_hash('superadmin123', PASSWORD_BCRYPT);
            $pdo->exec("
                INSERT INTO core.users (id, tenant_id, role_id, username, email, password_hash, nama_lengkap, is_active, created_at, updated_at)
                VALUES ('$newUserId'::uuid, '$globalTenantId'::uuid, '$newRoleId'::uuid, 'superadmin', 'superadmin@sinta.com', '$passwordHash', 'Administrator Platform', true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT (id) DO NOTHING;
            ");
        }

        // Hapus role lama jika sudah tidak memiliki referensi
        $pdo->exec("DELETE FROM core.roles WHERE id = '$oldRoleId'::uuid;");

        echo "✔ Pembaruan UUID Role dan User Super Admin berhasil dimigrasikan ke UUID v4!\n";
    },

    'down' => function (PDO $pdo): void {
        echo "Rollback pembaruan UUID Super Admin...\n";
    }
];
