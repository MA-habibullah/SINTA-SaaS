<?php
/**
 * View: Unified Kesiapan Akademik & PDSS Layout
 * Menggabungkan Penjurusan Mandiri (BK) dan Kesiapan PDSS
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
                <i class="bi bi-journal-check me-2" style="color:var(--bk-primary, #6366f1);"></i>
                Kesiapan Akademik & PDSS
            </h2>
            <p class="text-muted fs-7 mb-0">
                Manajemen penjurusan siswa dan pangkalan data kelayakan SNBP.
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
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2" id="unifiedAkademikTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="penjurusan-tab" data-bs-toggle="tab" data-bs-target="#penjurusan" type="button" role="tab">
                            <i class="bi bi-diagram-3 me-2 fs-6"></i> Penjurusan Mandiri
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pdss-tab" data-bs-toggle="tab" data-bs-target="#pdss" type="button" role="tab">
                            <i class="bi bi-database me-2 fs-6"></i> Pangkalan Data (PDSS)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="master-kampus-tab" data-bs-toggle="tab" data-bs-target="#master-kampus" type="button" role="tab">
                            <i class="bi bi-building me-2 fs-6"></i> Master Kampus & Prodi
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="unifiedAkademikTabsContent">
        
        <!-- TAB 1: Penjurusan Mandiri (BK Module) -->
        <div class="tab-pane fade show active" id="penjurusan" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php 
                        $allowed_bk_tabs = ['penjurusan']; 
                        include __DIR__ . '/../master_bk.php'; 
                    ?>
                </div>
            </div>
        </div>

        <!-- TAB 2: Pangkalan Data PDSS -->
        <div class="tab-pane fade" id="pdss" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php 
                        $allowed_pdss_tabs = ['kesiapan', 'master_jalur']; 
                        include __DIR__ . '/../pdss_index.php'; 
                    ?>
                </div>
            </div>
        </div>

        <!-- TAB 3: Master Kampus & Prodi -->
        <div class="tab-pane fade" id="master-kampus" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php 
                        include __DIR__ . '/master_kampus_prodi_layout.php'; 
                    ?>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Auto-resize fix for Vue apps inside hidden Bootstrap tabs -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pdssTab = document.getElementById('pdss-tab');
    if (pdssTab) {
        pdssTab.addEventListener('shown.bs.tab', function () {
            window.dispatchEvent(new Event('resize'));
        });
    }
});
</script>
