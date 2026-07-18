<?php
/**
 * View: Unified Alumni & Tracer Study Layout
 * Menggabungkan Tracer Study dan PDSS Tracking Alumni
 */
$userRole   = $user_role ?? ($_SESSION['role_name'] ?? '');
$tenantList = $tenant_list ?? [];
$tenantId   = $tenant_id ?? '';
?>
<div class="container-fluid py-4 animate-fade-in">

    <!-- Unified Super Admin Filter -->
    <?php if ($userRole === 'super_admin'): ?>
    <div class="alert border-0 rounded-4 p-3 mb-4 d-flex align-items-center gap-3"
         style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);">
        <i class="bi bi-funnel-fill fs-4" style="color:var(--bk-primary, #6366f1);"></i>
        <div class="d-flex align-items-center gap-2 flex-wrap w-100">
            <label for="unified-tenant-select" class="fw-semibold text-dark mb-0" style="white-space:nowrap;">
                Filter Sekolah (Super Admin):
            </label>
            <select id="unified-tenant-select" class="form-select form-select-sm rounded-3" style="max-width:320px;">
                <option value="">— Semua Sekolah —</option>
                <?php foreach ($tenantList as $t): ?>
                <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === $tenantId ? 'selected' : '') ?>>
                    <?= htmlspecialchars($t['nama_sekolah']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary rounded-3" onclick="applyUnifiedFilter()">
                <i class="bi bi-funnel me-1"></i> Terapkan Filter
            </button>
        </div>
    </div>
    <script>
        function applyUnifiedFilter() {
            const tenantId = document.getElementById('unified-tenant-select').value;
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

    <!-- Unified Header -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-3 mb-4 border-bottom">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-mortarboard-fill me-2" style="color:var(--bk-primary, #6366f1);"></i>
                Alumni & Tracer Study
            </h2>
            <p class="text-muted fs-7 mb-0">
                Manajemen penelusuran karir, kuliah, dan portofolio alumni terpusat.
            </p>
        </div>
    </div>

    <!-- Unified Nav Tabs (master_bk style) -->
    <style>
        .nav-tabs-wrapper .nav-link {
            font-size: 14px;
            color: #475569;
            background-color: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            font-weight: 600;
            padding: 10px 16px;
            transition: all 0.2s ease-in-out;
        }
        .nav-tabs-wrapper .nav-link:hover { color: #2563eb; }
        .nav-tabs-wrapper .nav-link.active {
            color: #2563eb !important;
            background-color: transparent !important;
            border-bottom: 2px solid #2563eb !important;
        }
        .scrollable-nav-tabs {
            padding-bottom: 5px; border-bottom: none;
        }
        .scrollable-nav-tabs::-webkit-scrollbar { height: 4px; }
        .scrollable-nav-tabs::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    </style>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2" id="unifiedAlumniTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tracking-tab" data-bs-toggle="tab" data-bs-target="#tracking" type="button" role="tab">
                            <i class="bi bi-search me-2 fs-6"></i> Tracking Data Alumni
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="riwayat-kuliah-tab" data-bs-toggle="tab" data-bs-target="#riwayat-kuliah" type="button" role="tab">
                            <i class="bi bi-mortarboard me-2 fs-6"></i> Riwayat Kuliah
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="riwayat-pekerjaan-tab" data-bs-toggle="tab" data-bs-target="#riwayat-pekerjaan" type="button" role="tab">
                            <i class="bi bi-briefcase me-2 fs-6"></i> Riwayat Pekerjaan
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="unifiedAlumniTabsContent">
        
        <!-- TAB 1: Tracking Data Alumni (PDSS Module) -->
        <div class="tab-pane fade show active" id="tracking" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php 
                        $allowed_pdss_tabs = ['tracking']; 
                        include __DIR__ . '/../pdss_index.php'; 
                    ?>
                </div>
            </div>
        </div>


        <!-- TAB 2: Riwayat Kuliah -->
        <div class="tab-pane fade" id="riwayat-kuliah" role="tabpanel">
            <?php
                $active_tracer_tab = 'kuliah';
                $is_sub_module     = true;
                include __DIR__ . '/../tracer_study.php';
                unset($active_tracer_tab, $is_sub_module);
            ?>
        </div>

        <!-- TAB 3: Riwayat Pekerjaan -->
        <div class="tab-pane fade" id="riwayat-pekerjaan" role="tabpanel">
            <?php
                $active_tracer_tab = 'pekerjaan';
                $is_sub_module     = true;
                include __DIR__ . '/../tracer_study.php';
                unset($active_tracer_tab, $is_sub_module);
            ?>
        </div>

    </div>

</div>

<!-- Auto-resize fix for Vue apps inside hidden Bootstrap tabs -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    ['riwayat-kuliah-tab', 'riwayat-pekerjaan-tab'].forEach(function(tabId) {
        const tabEl = document.getElementById(tabId);
        if (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function () {
                window.dispatchEvent(new Event('resize'));
            });
        }
    });
});
</script>
