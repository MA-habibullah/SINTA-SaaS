<?php
return [
    'up' => function (PDO $pdo): void {
        // Tambah indeks untuk optimalisasi pencarian dan pengurutan jutaan data tagihan
        $pdo->exec("CREATE INDEX `idx_spp_tagihan_created` ON `transaksi_spp_tagihan` (`created_at`) USING BTREE");
        $pdo->exec("CREATE INDEX `idx_spp_tagihan_tenant_created` ON `transaksi_spp_tagihan` (`tenant_id`, `created_at`) USING BTREE");
        $pdo->exec("CREATE INDEX `idx_spp_tagihan_status` ON `transaksi_spp_tagihan` (`status_lunas`) USING BTREE");
        echo "- Indeks performa berhasil ditambahkan pada tabel transaksi_spp_tagihan.\n";
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE `transaksi_spp_tagihan` DROP INDEX `idx_spp_tagihan_created`");
        $pdo->exec("ALTER TABLE `transaksi_spp_tagihan` DROP INDEX `idx_spp_tagihan_tenant_created`");
        $pdo->exec("ALTER TABLE `transaksi_spp_tagihan` DROP INDEX `idx_spp_tagihan_status`");
        echo "- Indeks performa berhasil dihapus dari tabel transaksi_spp_tagihan.\n";
    },
];
