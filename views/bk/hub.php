<?php
$active_group = $active_group ?? 'layanan';

if ($active_group === 'layanan') {
    $allowed_bk_tabs = ['dashboard', 'jurnal', 'prestasi', 'kehadiran', 'pelanggaran', 'beasiswa'];
    include __DIR__ . '/../master_bk.php';
} elseif ($active_group === 'akademik') {
    // Flag to tell sub-modules to hide their redundant headers/filters
    $is_sub_module = true;
    include __DIR__ . '/akademik_layout.php';
} elseif ($active_group === 'alumni') {
    // Flag to tell sub-modules to hide their redundant headers/filters
    $is_sub_module = true;
    include __DIR__ . '/alumni_layout.php';
}
?>
