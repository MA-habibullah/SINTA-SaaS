<?php
/**
 * Reusable Hero Banner & Sub-Navigation Header untuk Modul Persuratan & Tata Usaha
 * Standar Arsitektur SINTA — Hero Gradient Acuan Gambar 1 & Navigation Tabs Terpadu
 * 
 * Parameter yang diterima:
 * - $activeMenu: 'dashboard' | 'surat_masuk' | 'surat_keluar' | 'pengajuan_bk' | 'template' | 'master' | 'verifikasi'
 * - $pageTitle: Judul Halaman
 * - $pageSubtitle: Deskripsi Singkat Halaman
 * - $pageIcon: Icon Bootstrap
 */
$activeMenu = $activeMenu ?? 'dashboard';
$pageIcon = $pageIcon ?? 'bi-envelope-paper-heart-fill';
$pageTitle = $pageTitle ?? 'Persuratan & Tata Usaha';
$pageSubtitle = $pageSubtitle ?? 'Sistem Tata Kelola Administrasi Persuratan, Arsip Digital, dan Naskah Dinas Sekolah.';

$tenants = $tenants ?? ($data['tenants'] ?? []);
$isSuperAdmin = $isSuperAdmin ?? ($data['is_super_admin'] ?? false);
$selectedTenantId = $selectedTenantId ?? ($data['selected_tenant_id'] ?? ($tenant_id ?? ''));
$baseUrl = $this->getBaseUrl();

$menuList = [
    [
        'id'    => 'dashboard',
        'url'   => $baseUrl . '/persuratan/dashboard',
        'title' => 'Dashboard',
        'icon'  => 'bi-speedometer2',
    ],
    [
        'id'    => 'surat_masuk',
        'url'   => $baseUrl . '/persuratan/surat-masuk',
        'title' => 'Surat Masuk',
        'icon'  => 'bi-inbox-fill',
    ],
    [
        'id'    => 'surat_keluar',
        'url'   => $baseUrl . '/persuratan/surat-keluar',
        'title' => 'Surat Keluar',
        'icon'  => 'bi-send-fill',
    ],
    [
        'id'    => 'pengajuan_bk',
        'url'   => $baseUrl . '/persuratan/pengajuan-bk',
        'title' => 'Pengajuan BK',
        'icon'  => 'bi-person-lines-fill',
    ],
    [
        'id'    => 'template',
        'url'   => $baseUrl . '/persuratan/template',
        'title' => 'Template Naskah',
        'icon'  => 'bi-file-earmark-text-fill',
    ],
    [
        'id'    => 'master',
        'url'   => $baseUrl . '/persuratan/master',
        'title' => 'Master & Kop Surat',
        'icon'  => 'bi-gear-wide-connected',
    ],
    [
        'id'    => 'verifikasi',
        'url'   => $baseUrl . '/persuratan/verifikasi',
        'title' => 'Verifikasi TTE',
        'icon'  => 'bi-patch-check-fill',
    ],
];
?>

<!-- ═══════════════════════════════════════════════════════════════════════
     1. HERO HEADER BANNER DENGAN DESAIN GAMBAR 1 (PERSURATAN & TATA USAHA)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="row g-3 g-md-4 mb-4">
    <div class="col-12">
        <div class="p-4 p-md-4.5 rounded-2xl text-white shadow-xs position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #0d9488 100%);">
            <!-- Ambient Glow Circles -->
            <div class="position-absolute rounded-circle" style="width: 280px; height: 280px; background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 70%); top: -90px; right: -40px; pointer-events: none;"></div>
            <div class="position-absolute rounded-circle" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(20,184,166,0.2) 0%, rgba(255,255,255,0) 70%); bottom: -70px; left: 10%; pointer-events: none;"></div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative" style="z-index: 2;">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge px-3 py-1.5 rounded-pill text-xs font-semibold d-inline-flex align-items-center gap-1.5" style="background: rgba(255,255,255,0.18); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.25);">
                            <i class="bi <?= htmlspecialchars($pageIcon) ?> text-amber-300"></i> Modul Tata Usaha &amp; Kearsipan
                        </span>
                    </div>
                    <h2 class="h3 font-bold text-white mb-1 tracking-tight"><?= htmlspecialchars($pageTitle) ?></h2>
                    <p class="text-white/85 text-xs mb-0" style="max-width: 680px; line-height: 1.6;">
                        <?= htmlspecialchars($pageSubtitle) ?>
                    </p>
                </div>

                <!-- Right Controls: Super Admin Tenant Filter (Persis Desain Acuan Gambar 1) -->
                <?php if ($isSuperAdmin && !empty($tenants)): ?>
                <div class="d-flex align-items-center gap-2 flex-shrink-0 align-self-start align-self-md-center">
                    <div class="d-flex align-items-center gap-2 bg-white/15 p-2 ps-3 pe-2 rounded-2xl border border-white/30 shadow-xs" style="backdrop-filter: blur(8px); width: fit-content;">
                        <i class="bi bi-buildings text-white fs-6"></i>
                        <select id="persuratanTenantSelector" onchange="switchPersuratanTenant(this.value)" class="form-select form-select-sm border-0 text-xs font-bold rounded-xl shadow-2xs cursor-pointer py-2 px-3" style="width: auto; min-width: 220px; max-width: 280px; background-color: #ffffff !important; color: #0f172a !important; -webkit-text-fill-color: #0f172a !important;">
                            <option value="">Semua Sekolah / Tenant</option>
                            <?php foreach ($tenants as $t): ?>
                            <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === $selectedTenantId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nama_sekolah']) ?><?= !empty($t['npsn']) ? ' (' . htmlspecialchars($t['npsn']) . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <script>
                function switchPersuratanTenant(tenantId) {
                    const url = new URL(window.location.href);
                    if (tenantId) {
                        url.searchParams.set('tenant_id', tenantId);
                    } else {
                        url.searchParams.delete('tenant_id');
                    }
                    window.location.href = url.toString();
                }
                </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     2. NAVIGATION SUB-MENU TABS BAR (7 HALAMAN MANDIRI PERSURATAN)
     ═══════════════════════════════════════════════════════════════════════ -->
<style>
.persuratan-nav-tabs {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.persuratan-nav-tabs::-webkit-scrollbar {
    display: none;
}
.persuratan-tab-btn {
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.815rem;
    font-weight: 600;
    padding: 0.55rem 1rem;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}
.persuratan-tab-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}
.persuratan-tab-btn.active {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    border-color: #2563eb !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28) !important;
}
.persuratan-tab-btn.active i {
    color: #ffffff !important;
}
</style>

<div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
    <div class="d-flex align-items-center position-relative">
        <!-- 1 Tombol Panah Kiri -->
        <button type="button" 
                class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                style="width: 34px; height: 34px; z-index: 5;" 
                onclick="document.getElementById('persuratanGlobalNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                title="Geser ke Kiri">
            <i class="bi bi-chevron-left"></i>
        </button>

        <!-- Container Deretan Tab -->
        <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
            <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="persuratanGlobalNavTabs" role="tablist">
                <?php foreach ($menuList as $m): ?>
                <li class="nav-item" role="presentation">
                    <a href="<?= htmlspecialchars($m['url']) ?>" 
                       class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center text-decoration-none <?= ($m['id'] === $activeMenu) ? 'active' : '' ?>">
                        <i class="bi <?= htmlspecialchars($m['icon']) ?> me-2 fs-6 <?= ($m['id'] === $activeMenu) ? 'text-white' : 'text-primary' ?>"></i> <?= htmlspecialchars($m['title']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- 1 Tombol Panah Kanan -->
        <button type="button" 
                class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                style="width: 34px; height: 34px; z-index: 5;" 
                onclick="document.getElementById('persuratanGlobalNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                title="Geser ke Kanan">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>
</div>
