<?php

return [
    'up' => function (PDO $pdo): void {
        $nationalTenantId = '11111111-1111-1111-1111-111111111111';

        // 1. Pastikan Kurikulum Standar Nasional selalu terikat pada UUID Pemerintah ('11111111-1111-1111-1111-111111111111')
        $pdo->exec("
            UPDATE akademik.ref_kurikulum 
            SET tenant_id = '{$nationalTenantId}'::uuid
            WHERE nama_ref_kurikulum IN (
                'Kurikulum Merdeka (KMA 2024 / BSKAP)',
                'Kurikulum 2013 Revisi 2018',
                'Kurikulum Vokasi Industri Dual System',
                'KTSP (Kurikulum Tingkat Satuan Pendidikan)',
                'KBK (Kurikulum Berbasis Kompetensi)'
            )
        ");

        // 2. Alihkan kurikulum kustom sekolah (non-nasional) yang tidak sengaja tertandai sebagai nasional
        // ke tenant_id sekolah pertama secara dinamis dari tabel core.tenants (Aman untuk server produksi)
        $pdo->exec("
            UPDATE akademik.ref_kurikulum 
            SET tenant_id = (
                SELECT id FROM core.tenants 
                WHERE id NOT IN (
                    '11111111-1111-1111-1111-111111111111'::uuid, 
                    'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12'::uuid
                )
                ORDER BY created_at ASC LIMIT 1
            )
            WHERE tenant_id::text = '{$nationalTenantId}'
            AND nama_ref_kurikulum NOT IN (
                'Kurikulum Merdeka (KMA 2024 / BSKAP)',
                'Kurikulum 2013 Revisi 2018',
                'Kurikulum Vokasi Industri Dual System',
                'KTSP (Kurikulum Tingkat Satuan Pendidikan)',
                'KBK (Kurikulum Berbasis Kompetensi)'
            )
            AND EXISTS (
                SELECT 1 FROM core.tenants 
                WHERE id NOT IN (
                    '11111111-1111-1111-1111-111111111111'::uuid, 
                    'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12'::uuid
                )
            )
        ");

        echo "- Dynamically fixed kurikulum tenant IDs (National vs School Custom).\n";
    },
    'down' => function (PDO $pdo): void {
        // Rollback logic
    },
];
