<?php
/**
 * Reusable Hero Banner Header Mandiri untuk Modul Persuratan & Tata Usaha
 * SINTA SaaS Platform — Standar Hero Gradient Gambar 1 & Multi-Tenant Isolated
 * 
 * Parameter yang didukung:
 * - $pageTitle: Judul Halaman Mandiri
 * - $pageSubtitle: Deskripsi Singkat Halaman
 * - $pageIcon: Icon Bootstrap
 * - $heroBadge: Teks Badge Modul (default: 'Modul Tata Usaha & Kearsipan')
 * - $heroButtons: HTML Tombol Cepat Tambahan di Sisi Kanan (opsional)
 */
$pageIcon     = $pageIcon     ?? 'bi-envelope-paper-fill';
$pageTitle    = $pageTitle    ?? 'Persuratan & Tata Usaha';
$pageSubtitle = $pageSubtitle ?? 'Sistem Tata Kelola Administrasi Persuratan, Arsip Digital, dan Naskah Dinas Sekolah.';
$heroBadge    = $heroBadge    ?? 'Modul Tata Usaha & Kearsipan';

$tenants          = $tenants          ?? ($data['tenants'] ?? []);
$isSuperAdmin     = $isSuperAdmin     ?? ($data['is_super_admin'] ?? false);
$selectedTenantId = $selectedTenantId ?? ($data['selected_tenant_id'] ?? ($tenant_id ?? ''));
$baseUrl          = $this->getBaseUrl();
?>
<style>
.persuratan-tab-btn {
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.45rem 0.85rem;
    border-radius: 0.75rem;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.persuratan-tab-btn:hover {
    color: #1e293b;
    background: #f1f5f9;
    transform: translateY(-1px);
}
.persuratan-tab-btn.active {
    color: #ffffff !important;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    border-color: #2563eb !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25) !important;
}
.persuratan-tab-btn.active i {
    color: #ffffff !important;
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════
     HERO HEADER BANNER MANDIRI PERSURATAN (BEBAS DARI GLOBAL TABS REDUNDAN)
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
                            <i class="bi <?= htmlspecialchars($pageIcon) ?> text-amber-300"></i> <?= htmlspecialchars($heroBadge) ?>
                        </span>
                    </div>
                    <h2 class="h3 font-bold text-white mb-1 tracking-tight"><?= htmlspecialchars($pageTitle) ?></h2>
                    <p class="text-white/85 text-xs mb-0" style="max-width: 680px; line-height: 1.6;">
                        <?= htmlspecialchars($pageSubtitle) ?>
                    </p>
                </div>

                <!-- Right Controls: Super Admin Tenant Filter & Hero Buttons -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0 align-self-start align-self-md-center flex-wrap">
                    <?php if (!empty($heroButtons)): ?>
                        <?= $heroButtons ?>
                    <?php endif; ?>

                    <?php if ($isSuperAdmin && !empty($tenants)): ?>
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
</div>
