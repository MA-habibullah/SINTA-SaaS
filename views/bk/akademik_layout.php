<?php
/**
 * View: Unified Kesiapan Akademik & PDSS Layout
 * Menggabungkan Penjurusan Mandiri (BK) dan Kesiapan PDSS
 * SINTA SaaS - Executive Modern Standard
 */
$userRole   = $user_role ?? ($_SESSION['role_name'] ?? '');
$tenantList = $tenant_list ?? [];
$tenantId   = $tenant_id ?? ($_GET['tenant_id'] ?? (App\Core\SessionManager::getTenantId() ?: ''));
if ($userRole === 'super_admin' && empty($tenantId) && !empty($tenantList)) {
    $tenantId = $tenantList[0]['id'];
}
?>

<div class="p-3 p-md-4 max-w-7xl mx-auto font-sans animate-fade-in">

    <!-- ═══════════════════════════════════════════════════════════════════════
         1. HERO BANNER & SUPER ADMIN TENANT SELECTOR
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12">
            <div class="p-4 p-md-4.5 rounded-2xl text-white shadow-xs position-relative overflow-hidden" 
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #0d9488 100%);">
                <!-- Ambient Glow Circles -->
                <div class="position-absolute rounded-circle" style="width: 280px; height: 280px; background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 70%); top: -90px; right: -40px; pointer-events: none;"></div>
                <div class="position-absolute rounded-circle" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(20,184,166,0.2) 0%, rgba(255,255,255,0) 70%); bottom: -70px; left: 10%; pointer-events: none;"></div>

                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 position-relative" style="z-index: 2;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <span class="badge px-3 py-1.5 rounded-pill text-xs font-semibold d-inline-flex align-items-center gap-1.5" style="background: rgba(255,255,255,0.18); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.25);">
                                <i class="bi bi-award-fill text-amber-300"></i> Kesiapan Akademik, Penjurusan & PDSS SNBP
                            </span>
                        </div>
                        <h2 class="h3 font-bold text-white mb-1 tracking-tight">Kesiapan Akademik & PDSS</h2>
                        <p class="text-white/85 text-xs mb-0" style="max-width: 680px; line-height: 1.6;">
                            Manajemen penjurusan mandiri siswa, pemetaan eligibilitas PDSS SNBP, simulasi pilihan program studi, dan master data kampus terpadu.
                        </p>
                    </div>

                    <!-- Right Controls: Super Admin Tenant Filter -->
                    <?php if ($userRole === 'super_admin' && !empty($tenantList)): ?>
                    <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
                        <div class="d-flex align-items-center gap-2 bg-white/15 p-2 rounded-xl border border-white/25 shadow-xs" style="backdrop-filter: blur(6px);">
                            <i class="bi bi-buildings text-white fs-6 ms-1.5"></i>
                            <select id="unified-tenant-select" onchange="applyUnifiedFilter(this.value)" class="form-select form-select-sm border-0 text-xs font-semibold bg-white text-slate-800 rounded-lg shadow-2xs cursor-pointer" style="min-width: 240px;">
                                <option value="">Semua Sekolah / Tenant</option>
                                <?php foreach ($tenantList as $t): ?>
                                <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === $tenantId ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($t['nama_sekolah']) ?> (<?= htmlspecialchars($t['npsn'] ?? '-') ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         2. MODERN TAB NAVIGATION BAR DENGAN ARROW & MOUSE SCROLL
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
        <div class="d-flex align-items-center position-relative">
            <!-- Left Scroll Arrow Button -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="scrollNavTabsHorizontal('unifiedAkademikTabs', -220)"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="unifiedAkademikTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition active" id="penjurusan-tab" data-bs-toggle="tab" data-bs-target="#penjurusan" type="button" role="tab">
                            <i class="bi bi-diagram-3 me-2 fs-6"></i> Penjurusan Mandiri
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="kesiapan-tab" data-bs-toggle="tab" data-bs-target="#pdss" type="button" role="tab" onclick="switchVueTab('kesiapan')">
                            <i class="bi bi-award-fill me-2 fs-6"></i> Kesiapan &amp; Eligibilitas Siswa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="simulasi-tab" data-bs-toggle="tab" data-bs-target="#pdss" type="button" role="tab" onclick="switchVueTab('simulasi')">
                            <i class="bi bi-journal-check me-2 fs-6"></i> Simulasi Pilihan Kampus
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="master-kampus-tab" data-bs-toggle="tab" data-bs-target="#master-kampus" type="button" role="tab">
                            <i class="bi bi-building me-2 fs-6"></i> Master Kampus &amp; Prodi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="master-jalur-tab" data-bs-toggle="tab" data-bs-target="#pdss" type="button" role="tab" onclick="switchVueTab('master_jalur')">
                            <i class="bi bi-signpost-split-fill me-2 fs-6"></i> Master Jalur Masuk
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Right Scroll Arrow Button -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="scrollNavTabsHorizontal('unifiedAkademikTabs', 220)"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         3. TAB CONTENT
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="tab-content" id="unifiedAkademikTabsContent">
        
        <!-- TAB 1: Penjurusan Mandiri (BK Module) -->
        <div class="tab-pane fade show active" id="penjurusan" role="tabpanel">
            <?php 
                $allowed_bk_tabs = ['penjurusan']; 
                $is_sub_module   = true;
                include __DIR__ . '/master_bk.php'; 
                unset($allowed_bk_tabs, $is_sub_module);
            ?>
        </div>

        <!-- TAB 2: Pangkalan Data PDSS -->
        <div class="tab-pane fade" id="pdss" role="tabpanel">
            <?php 
                $allowed_pdss_tabs = ['kesiapan', 'master_jalur', 'simulasi']; 
                $hide_pdss_tabs = true;
                $is_sub_module = true;
                include __DIR__ . '/../pdss/pdss_index.php'; 
                unset($allowed_pdss_tabs, $hide_pdss_tabs, $is_sub_module);
            ?>
        </div>

        <!-- TAB 3: Master Kampus & Prodi -->
        <div class="tab-pane fade" id="master-kampus" role="tabpanel">
            <?php 
                include __DIR__ . '/master_kampus_prodi_layout.php'; 
            ?>
        </div>

    </div>

</div>

<script>
function applyUnifiedFilter(tenantId) {
    const url = new URL(window.location.href);
    if (tenantId) {
        url.searchParams.set('tenant_id', tenantId);
    } else {
        url.searchParams.delete('tenant_id');
    }
    window.location.href = url.toString();
}

function switchVueTab(tabName) {
    if (window.vueApps && window.vueApps['#pdssApp'] && window.vueApps['#pdssApp'].instance) {
        const inst = window.vueApps['#pdssApp'].instance;
        inst.activeTab = tabName;
        if (tabName === 'kesiapan') {
            if (typeof inst.fetchKesiapan === 'function') inst.fetchKesiapan();
            if (typeof inst.fetchPdssMapels === 'function') inst.fetchPdssMapels();
        } else if (tabName === 'simulasi') {
            if (typeof inst.fetchSimulasiSettings === 'function') inst.fetchSimulasiSettings();
            if (typeof inst.fetchSimulasi === 'function') inst.fetchSimulasi();
            if (inst.listKampusFlat && inst.listKampusFlat.length === 0 && typeof inst.fetchKampusFlatList === 'function') {
                inst.fetchKampusFlatList();
            }
        } else if (tabName === 'master_jalur') {
            if (typeof inst.fetchJalur === 'function') inst.fetchJalur();
        } else if (tabName === 'master_kampus') {
            if (typeof inst.fetchKampus === 'function') inst.fetchKampus();
        }
    } else {
        window.targetPendingTab = tabName;
    }
}

// Function to scroll nav tabs with buttons
function scrollNavTabsHorizontal(elementId, distance) {
    const container = document.getElementById(elementId);
    if (container) {
        container.scrollBy({ left: distance, behavior: 'smooth' });
    }
}

// Enable Mouse Wheel & Drag-to-Scroll on all scrollable-nav-tabs
document.addEventListener('DOMContentLoaded', function() {
    const triggerElements = ['kesiapan-tab', 'master-jalur-tab', 'simulasi-tab', 'master-kampus-tab'];
    triggerElements.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('shown.bs.tab', function () {
                window.dispatchEvent(new Event('resize'));
            });
        }
    });

    // Auto-attach wheel and drag handlers
    const scrollContainers = document.querySelectorAll('.scrollable-nav-tabs');
    scrollContainers.forEach(container => {
        // 1. Mouse Wheel to Horizontal Scroll
        container.addEventListener('wheel', function(e) {
            if (e.deltaY !== 0) {
                e.preventDefault();
                container.scrollLeft += e.deltaY;
            }
        }, { passive: false });

        // 2. Mouse Drag to Scroll (Touch-like behavior on PC)
        let isDown = false;
        let startX = 0;
        let scrollLeftPos = 0;

        container.addEventListener('mousedown', function(e) {
            isDown = true;
            container.style.cursor = 'grabbing';
            startX = e.pageX - container.offsetLeft;
            scrollLeftPos = container.scrollLeft;
        });

        container.addEventListener('mouseleave', function() {
            isDown = false;
            container.style.cursor = '';
        });

        container.addEventListener('mouseup', function() {
            isDown = false;
            container.style.cursor = '';
        });

        container.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 1.5;
            container.scrollLeft = scrollLeftPos - walk;
        });
    });
});
</script>

<style>
/* Modern Pill Tab Styling identical to Kedisiplinan Reference (Image 1) */
.nav-pills {
    gap: 0.35rem;
}
.nav-pills .nav-link {
    color: #334155 !important;
    background: transparent !important;
    border: none !important;
    border-radius: 0.75rem !important;
    padding: 0.55rem 1.15rem !important;
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    display: inline-flex !important;
    align-items: center !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    white-space: nowrap !important;
}
.nav-pills .nav-link i {
    color: #475569 !important;
    transition: color 0.2s ease !important;
}
.nav-pills .nav-link:hover {
    color: #1d4ed8 !important;
    background: #f1f5f9 !important;
}
.nav-pills .nav-link:hover i {
    color: #1d4ed8 !important;
}
.nav-pills .nav-link.active {
    color: #ffffff !important;
    background: #2563eb !important;
    box-shadow: 0 1px 3px rgba(37, 99, 235, 0.35) !important;
}
.nav-pills .nav-link.active i {
    color: #ffffff !important;
}

/* Hide ugly horizontal scrollbars across all browsers with smooth scrolling */
.scrollable-nav-tabs {
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
    padding-bottom: 0px !important;
    cursor: grab;
}
.scrollable-nav-tabs::-webkit-scrollbar {
    display: none !important;
    height: 0 !important;
    width: 0 !important;
}
</style>
