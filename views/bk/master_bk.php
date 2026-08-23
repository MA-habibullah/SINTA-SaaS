<?php
/**
 * View: Bimbingan Konseling — Master BK
 * Module hub dengan tab yang disesuaikan berdasarkan active_group:
 *   layanan  → Dashboard, Jurnal, Prestasi, Kehadiran, Pelanggaran, Beasiswa
 *   akademik → Penjurusan, Kesiapan (PDSS), Simulasi, Master Kampus, Master Jalur Masuk
 *   alumni   → Tracking Alumni, Riwayat Kuliah, Riwayat Pekerjaan
 */
$userRole        = $data['user_role']        ?? ($_SESSION['role_name']    ?? '');
$userNama        = $data['user_nama']        ?? ($_SESSION['nama_lengkap'] ?? '');
$tenantId        = $data['tenant_id']        ?? '';
$tenantList      = $data['tenant_list']      ?? [];
$tahunAjaranList = $data['tahun_ajaran_list'] ?? [];
$baseUrl         = $data['baseUrl'] ?? (rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));
$activeGroup     = $data['active_group']     ?? 'layanan';

// Mapping active_group → tab config
if ($activeGroup === 'akademik') {
    $pageTitle       = 'BK Akademik & Penjurusan';
    $pageSubtitle    = 'Rekomendasi penjurusan, PDSS, dan simulasi pilihan kampus.';
    $pageIcon        = 'bi-mortarboard-fill';
    $allowed_bk_tabs = ['penjurusan', 'kesiapan', 'simulasi', 'master_kampus', 'master_jalur'];
} elseif ($activeGroup === 'alumni') {
    $pageTitle       = 'BK Alumni & Tracer Study';
    $pageSubtitle    = 'Pelacakan alumni, penelusuran karir, dan studi lanjut PTN/PTS.';
    $pageIcon        = 'bi-people-fill';
    $allowed_bk_tabs = ['tracking', 'riwayat_kuliah', 'riwayat_pekerjaan'];
} elseif ($activeGroup === 'kedisiplinan') {
    $pageTitle       = 'Kedisiplinan Siswa';
    $pageSubtitle    = 'Poin pelanggaran, tata tertib, rekam kasus konseling, dan buku sanksi.';
    $pageIcon        = 'bi-shield-exclamation';
    $allowed_bk_tabs = ['p_dashboard', 'jurnal', 'p_input', 'p_sanksi', 'p_master'];
} else { // default: layanan
    $pageTitle       = 'Layanan BK';
    $pageSubtitle    = 'Pusat monitoring, konseling, dan prestasi siswa.';
    $pageIcon        = 'bi-person-badge';
    $allowed_bk_tabs = ['dashboard', 'prestasi', 'kehadiran', 'beasiswa'];
}

$defaultMainTab = $allowed_bk_tabs[0] ?? 'dashboard';
if (str_starts_with($defaultMainTab, 'p_')) {
    $defaultSubTab = $defaultMainTab;
    $defaultMainTab = 'pelanggaran';
} else {
    $defaultSubTab = 'p_dashboard';
}
?>

<style>
    [v-cloak] {
        display: none !important;
    }

    /* ─── Design Tokens ─────────────────────────────── */
    :root {
        --bk-primary:   #7c3aed;   /* Violet — identitas BK */
        --bk-p-light:   #f5f3ff;
        --bk-green:     #10b981;
        --bk-amber:     #f59e0b;
        --bk-red:       #ef4444;
        --bk-blue:      #2563eb;
        --bk-border:    #e2e8f0;
    }

    /* ─── Modal & Backdrop Stacking Fix ───────────── */
    .modal {
        z-index: 1060 !important;
    }
    .modal-backdrop {
        z-index: 1050 !important;
    }

    /* ─── Tab Nav ────────────────────────────────────── */
    .bk-tabs {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.25rem;
        border-bottom: 2px solid var(--bk-border);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 5px;
    }
    .bk-tabs::-webkit-scrollbar {
        height: 5px;
    }
    .bk-tabs::-webkit-scrollbar-track {
        background: transparent;
    }
    .bk-tabs::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 99px;
    }
    .bk-tabs::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .bk-tab-btn {
        flex-shrink: 0;
        padding: 0.65rem 1.25rem;
        border: none;
        background: transparent;
        font-weight: 600;
        font-size: 0.85rem;
        color: #64748b;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .bk-tab-btn:hover { color: var(--bk-primary); background: var(--bk-p-light); }
    .bk-tab-btn.active {
        color: var(--bk-primary);
        border-bottom-color: var(--bk-primary);
        background: var(--bk-p-light);
    }
    .bk-tab-btn i { margin-right: 0.4rem; }

    /* ─── KPI Cards ──────────────────────────────────── */
    .kpi-card {
        background: #fff;
        border-radius: 1rem;
        border: none;
        box-shadow: 0 2px 12px rgba(15,23,42,0.06);
        padding: 1.25rem 1.5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(15,23,42,0.1); }
    .kpi-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem;
    }
    .kpi-value { font-size: 1.85rem; font-weight: 800; line-height: 1; }

    /* ─── Section Card ───────────────────────────────── */
    .bk-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 2px 12px rgba(15,23,42,0.06);
        border: none;
    }

    /* ─── Status Badges ──────────────────────────────── */
    .badge-terbuka  { background: #fef3c7; color: #92400e; }
    .badge-proses   { background: #dbeafe; color: #1e40af; }
    .badge-selesai  { background: #d1fae5; color: #065f46; }

    /* ─── Form ───────────────────────────────────────── */
    .bk-form-card {
        background: var(--bk-bg);
        border: 1px dashed var(--bk-border);
        border-radius: 0.875rem;
        transition: border-color 0.2s;
    }
    .bk-form-card:hover { border-color: var(--bk-primary); }

    /* ─── Auto-fill Fields (NISN & Kelas Snapshot) ───── */
    .autofill-panel {
        border-radius: 0.625rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 0.75rem;
        margin-top: 0.5rem;
    }
    .autofill-confirm-bar {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        margin-bottom: 0.6rem;
    }
    .autofill-input {
        cursor: default !important;
        letter-spacing: 0.3px;
        font-size: 0.82rem;
    }
    .autofill-input.nisn-field {
        background: #fffbeb !important;
        border-color: #fde68a !important;
        color: #92400e !important;
        font-family: monospace;
        font-weight: 700;
    }
    .autofill-input.kelas-field {
        background: #eff6ff !important;
        border-color: #bfdbfe !important;
        color: #1e40af !important;
        font-weight: 700;
    }
    .autofill-badge {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 0.6rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        vertical-align: middle;
        margin-left: 3px;
    }
    .autofill-badge.nisn { background: #fef3c7; color: #92400e; }
    .autofill-badge.snap { background: #dbeafe; color: #1e40af; }
    .autofill-hint {
        font-size: 0.69rem;
        color: #94a3b8;
        margin-top: 0.35rem;
        display: flex;
        align-items: flex-start;
        gap: 4px;
    }

    /* ─── Pie Wrapper ────────────────────────────────── */
    .pie-legend-dot {
        width: 12px; height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    /* ─── PDSS Table ─────────────────────────────────── */
    .pdss-eligible-badge {
        background: linear-gradient(135deg, #7c3aed, #2563eb);
        color: #fff;
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .animate-fade-in {
        animation: fadeIn 0.35s ease-out forwards;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .hover-bg-slate:hover {
        background-color: #f1f5f9;
    }
    .cursor-pointer {
        cursor: pointer;
    }

    [v-cloak] { display: none !important; }

    /* ─── Pelanggaran & Custom Modal backdrop ──────────────────────── */
    .modal-backdrop-custom {
        backdrop-filter: blur(4px);
    }
    .col-lg-2.4 {
        flex: 0 0 auto;
        width: 20%;
    }
    .border-violet {
        border-left-color: var(--bk-primary) !important;
    }
    .text-area-vertical {
        resize: vertical;
    }
    @media (max-width: 991.98px) {
        .col-lg-2.4 {
            width: 50%;
        }
    }

    /* ─── Modern Segmented Tabs ──────────────────────── */
    .sub-tabs-segment {
        display: inline-flex;
        padding: 0.25rem;
        background: #f1f5f9;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        gap: 0.25rem;
    }
    .sub-tab-btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sub-tab-btn-modern:hover {
        color: var(--bk-primary);
        background: rgba(124, 58, 237, 0.05);
    }
    .sub-tab-btn-modern.active {
        color: var(--bk-primary);
        background: #ffffff;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
    }

    /* ─── Kehadiran Grid & Dirty State styles ─────────── */
    .dirty-row {
        background-color: #eff6ff !important;
    }
    .dirty-cell {
        background-color: #dbeafe !important;
    }
    .cell-warning {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        font-weight: bold;
    }
    .cell-caution {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        font-weight: bold;
    }
    .grid-input-number {
        text-align: center;
        font-family: monospace;
        font-weight: bold;
        width: 50px;
    }
    .grid-input-number::-webkit-inner-spin-button,
    .grid-input-number::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .grid-input-number {
        -moz-appearance: textfield;
    }
    /* Navigation Tabs Styling */
    .scrollable-nav-tabs {
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        padding-bottom: 2px;
    }
    .scrollable-nav-tabs::-webkit-scrollbar {
        height: 4px;
    }
    .scrollable-nav-tabs::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 9999px;
    }
    .nav-tabs-wrapper .nav-link {
        font-size: 13px;
        color: #475569;
        background-color: transparent;
        border: 1px solid transparent;
        border-radius: 0.75rem;
        font-weight: 700;
        padding: 8px 16px;
        transition: all 0.2s ease-in-out;
    }
    .nav-tabs-wrapper .nav-link:hover {
        color: #2563eb;
        background-color: #f1f5f9;
    }
    .nav-tabs-wrapper .nav-link.active {
        color: #ffffff !important;
        background: linear-gradient(135deg, #1e40af, #2563eb) !important;
        border-color: transparent !important;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);
    }
</style>

<?php if(empty($is_sub_module)): ?>
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
                            <i class="bi <?= htmlspecialchars($pageIcon) ?> text-amber-300"></i>
                            <?= ($activeGroup === 'kedisiplinan') ? 'Manajemen Tata Tertib & Kedisiplinan Siswa' : 'Pusat Bimbingan & Konseling Siswa' ?>
                        </span>
                    </div>
                    <h2 class="h3 font-bold text-white mb-1 tracking-tight"><?= htmlspecialchars($pageTitle) ?></h2>
                    <p class="text-white/85 text-xs mb-0" style="max-width: 680px; line-height: 1.6;">
                        <?= htmlspecialchars($pageSubtitle) ?>
                    </p>
                </div>

                <!-- Right Controls: Super Admin Tenant Filter -->
                <?php if ($userRole === 'super_admin' && !empty($tenantList)): ?>
                <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
                    <div class="d-flex align-items-center gap-2 bg-white/15 p-2 rounded-xl border border-white/25 shadow-xs" style="backdrop-filter: blur(6px);">
                        <i class="bi bi-buildings text-white fs-6 ms-1.5"></i>
                        <select id="sa-tenant-select" name="sa-tenant-select" onchange="applySuperAdminTenantFilter(this.value)" class="form-select form-select-sm border-0 text-xs font-semibold bg-white text-slate-800 rounded-lg shadow-2xs cursor-pointer" style="min-width: 240px;">
                            <option value="">Semua Sekolah / Tenant</option>
                            <?php foreach ($tenantList as $t): ?>
                            <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === $tenantId ? 'selected' : '') ?>>
                                <?= htmlspecialchars($t['nama_sekolah']) ?> (<?= htmlspecialchars($t['npsn'] ?? '-') ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <script>
                function applySuperAdminTenantFilter(tenantId) {
                    const currentUrl = new URL(window.location.href);
                    if (tenantId) {
                        currentUrl.searchParams.set('tenant_id', tenantId);
                    } else {
                        currentUrl.searchParams.delete('tenant_id');
                    }
                    window.location.href = currentUrl.toString();
                }
                </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ─── Vue App Mount ────────────────────────────────────────── -->
<div id="bkApp" v-cloak class="font-sans">

    <!-- ═══ HORIZONTAL TAB NAVIGATION ═══════════════════════════ -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative" <?php if(!empty($is_sub_module)) echo 'style="display:none;"'; ?>>
        <div class="d-flex align-items-center position-relative">
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('bkMasterNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="bkMasterNavTabs" role="tablist">
                    <?php if(!isset($allowed_bk_tabs) || in_array('dashboard', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'dashboard'}"
                                @click="switchTab('dashboard')" id="tab-dashboard">
                            <i class="bi bi-speedometer2 me-2 fs-6"></i> Dashboard
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('penjurusan', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'penjurusan'}"
                                @click="switchTab('penjurusan')" id="tab-tab-penjurusan">
                            <i class="bi bi-diagram-3 me-2 fs-6"></i> Penjurusan Mandiri
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('tracer', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'tracer'}"
                                @click="switchTab('tracer')" id="tab-tracer">
                            <i class="bi bi-mortarboard me-2 fs-6"></i> Tracer Study
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('prestasi', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'prestasi'}"
                                @click="switchTab('prestasi')" id="tab-prestasi">
                            <i class="bi bi-trophy me-2 fs-6"></i> Prestasi Siswa
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('kehadiran', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'kehadiran'}"
                                @click="switchTab('kehadiran')" id="tab-kehadiran">
                            <i class="bi bi-calendar-check me-2 fs-6"></i> Kehadiran Semester
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('p_dashboard', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'pelanggaran' && activeSubTab === 'p_dashboard'}"
                                @click="switchSubTab('p_dashboard')" id="tab-p-dashboard">
                            <i class="bi bi-speedometer2 me-2 fs-6"></i> Dashboard &amp; Tren
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('jurnal', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'jurnal'}"
                                @click="switchTab('jurnal')" id="tab-jurnal">
                            <i class="bi bi-journal-text me-2 fs-6"></i> Rekam Kasus &amp; Jurnal
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('p_input', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'pelanggaran' && activeSubTab === 'p_input'}"
                                @click="switchSubTab('p_input')" id="tab-p-input">
                            <i class="bi bi-plus-circle me-2 fs-6"></i> Catat Pelanggaran
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('p_sanksi', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'pelanggaran' && activeSubTab === 'p_sanksi'}"
                                @click="switchSubTab('p_sanksi')" id="tab-p-sanksi">
                            <i class="bi bi-journal-bookmark me-2 fs-6"></i> Buku Catatan Sanksi
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('p_master', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'pelanggaran' && activeSubTab === 'p_master'}"
                                @click="switchSubTab('p_master')" id="tab-p-master">
                            <i class="bi bi-gear-fill me-2 fs-6"></i> Master Kategori &amp; Poin
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('beasiswa', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'beasiswa'}"
                                @click="switchTab('beasiswa')" id="tab-beasiswa">
                            <i class="bi bi-gift me-2 fs-6"></i> Beasiswa Siswa
                        </button>
                    </li>
                    <?php endif; ?>
                    <!-- ═══ AKADEMIK/PDSS TABS ═══ -->
                    <?php if(!isset($allowed_bk_tabs) || in_array('kesiapan', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'kesiapan'}"
                                @click="switchTab('kesiapan')" id="tab-kesiapan">
                            <i class="bi bi-person-check me-2 fs-6"></i> Kesiapan & Eligibilitas
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('simulasi', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'simulasi'}"
                                @click="switchTab('simulasi')" id="tab-simulasi">
                            <i class="bi bi-building-check me-2 fs-6"></i> Simulasi Pilihan Kampus
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('master_kampus', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'master_kampus'}"
                                @click="switchTab('master_kampus')" id="tab-master-kampus">
                            <i class="bi bi-bank me-2 fs-6"></i> Master Kampus & Prodi
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('master_jalur', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'master_jalur'}"
                                @click="switchTab('master_jalur')" id="tab-master-jalur">
                            <i class="bi bi-door-open me-2 fs-6"></i> Master Jalur Masuk
                        </button>
                    </li>
                    <?php endif; ?>
                    <!-- ═══ ALUMNI TABS ═══ -->
                    <?php if(!isset($allowed_bk_tabs) || in_array('tracking', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'tracking'}"
                                @click="switchTab('tracking')" id="tab-tracking">
                            <i class="bi bi-people me-2 fs-6"></i> Tracking Data Alumni
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('riwayat_kuliah', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'riwayat_kuliah'}"
                                @click="switchTab('riwayat_kuliah')" id="tab-riwayat-kuliah">
                            <i class="bi bi-book me-2 fs-6"></i> Riwayat Kuliah
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if(!isset($allowed_bk_tabs) || in_array('riwayat_pekerjaan', $allowed_bk_tabs)): ?>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" :class="{'active': activeTab === 'riwayat_pekerjaan'}"
                                @click="switchTab('riwayat_pekerjaan')" id="tab-riwayat-pekerjaan">
                            <i class="bi bi-briefcase me-2 fs-6"></i> Riwayat Pekerjaan
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('bkMasterNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 1: DASHBOARD MONITORING
    ════════════════════════════════════════════════════════════ -->
    <?php if(empty($allowed_bk_tabs) || in_array('dashboard', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'dashboard'">
        <!-- Loading State -->
        <div v-if="loadingDashboard" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="text-muted mt-2 fs-7">Memuat data monitoring...</p>
        </div>

        <div v-else>
            <!-- KPI Row -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Siswa Aktif</p>
                                <div class="kpi-value text-dark">{{ kpi?.total_siswa_aktif ?? 0 }}</div>
                            </div>
                            <div class="kpi-icon" style="background:#eff6ff;color:#2563eb;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Kasus Bulan Ini</p>
                                <div class="kpi-value" style="color:var(--bk-amber);">{{ kpi?.kasus_bulan_ini ?? 0 }}</div>
                            </div>
                            <div class="kpi-icon" style="background:#fff7ed;color:#f59e0b;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Kasus Terbuka</p>
                                <div class="kpi-value" style="color:var(--bk-red);">{{ kpi?.kasus_terbuka ?? 0 }}</div>
                            </div>
                            <div class="kpi-icon" style="background:#fef2f2;color:#ef4444;">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Total Alumni</p>
                                <div class="kpi-value" style="color:var(--bk-green);">{{ kpi?.total_alumni ?? 0 }}</div>
                            </div>
                            <div class="kpi-icon" style="background:#ecfdf5;color:#10b981;">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribusi Kasus -->
            <div class="bk-card p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2" style="color:var(--bk-primary);"></i>Distribusi Kasus per Jenis</h6>
                <div v-if="Array.isArray(kpi.distribusi_kasus) && kpi.distribusi_kasus.length > 0">
                    <div class="row g-2">
                        <div v-for="(item, idx) in kpi.distribusi_kasus" :key="idx" class="col-md-4">
                            <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:var(--bk-bg);">
                                <span class="pie-legend-dot" :style="'background:' + (pieColors && pieColors.length ? pieColors[idx % pieColors.length] : '#7c3aed')"></span>
                                <span class="fw-semibold fs-7">{{ item.jenis_kasus }}</span>
                                <span class="ms-auto badge bg-secondary rounded-pill">{{ item.total }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-4 text-muted">
                    <i class="bi bi-pie-chart fs-1 d-block mb-2"></i>
                    Belum ada data kasus yang tercatat.
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 2: PENJURUSAN MANDIRI
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && in_array('penjurusan', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'penjurusan'">

        <!-- Alert -->
        <div v-if="alertPenjurusan.msg" :class="'alert alert-' + alertPenjurusan.type + ' border-0 rounded-4 mb-3'" role="alert">
            <i class="bi bi-info-circle me-2"></i>{{ alertPenjurusan.msg }}
            <button type="button" class="btn-close float-end" @click="alertPenjurusan.msg=''"></button>
        </div>

        <!-- Loading -->
        <div v-if="loadingPenjurusan" class="text-center py-5">
            <div class="spinner-border" style="color:var(--bk-primary);"></div>
            <p class="text-muted mt-2 fs-7">Memuat data penjurusan...</p>
        </div>

        <div v-else>
            <!-- Summary Cards Per Jurusan -->
            <div v-if="penjurusanSummary?.length > 0" class="row g-3 mb-4">
                <div v-for="s in penjurusanSummary" :key="s.kode_jurusan" class="col-md-4 col-lg-3">
                    <div class="kpi-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge rounded-pill fw-bold px-3" style="background:var(--bk-p-light);color:var(--bk-primary);">
                                {{ s.kode_jurusan }}
                            </span>
                            <span class="fw-bold text-dark fs-5">{{ s.total }}</span>
                        </div>
                        <p class="fw-semibold text-dark fs-7 mb-2" style="line-height:1.3;">{{ s.nama_jurusan }}</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;font-size:.68rem;">
                                {{ s.pending }} Pending
                            </span>
                            <span class="badge rounded-pill" style="background:#d1fae5;color:#065f46;font-size:.68rem;">
                                {{ s.terverifikasi }} Verified
                            </span>
                            <span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;font-size:.68rem;">
                                {{ s.ditolak }} Ditolak
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bk-card p-3 mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="input-search-penjurusan" class="form-label fw-semibold fs-8 mb-1">Cari Siswa</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0 bg-white">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 rounded-end-3"
                                   v-model="filterPenjurusan.search"
                                   placeholder="Nama / NISN..."
                                   id="input-search-penjurusan"
                                   name="search_penjurusan">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="select-filter-status" class="form-label fw-semibold fs-8 mb-1">Filter Status</label>
                        <select class="form-select form-select-sm rounded-3" v-model="filterPenjurusan.status"
                                id="select-filter-status" name="status">
                            <option value="">Semua Status</option>
                            <option value="Diajukan">Diajukan</option>
                            <option value="Diverifikasi">Diverifikasi</option>
                            <option value="Ditolak">Ditolak</option>
                            <option value="Override_BK">Override BK</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="select-filter-jurusan" class="form-label fw-semibold fs-8 mb-1">Filter Jurusan</label>
                        <select class="form-select form-select-sm rounded-3" v-model="filterPenjurusan.jurusan_id"
                                id="select-filter-jurusan" name="jurusan_id">
                            <option value="">Semua Jurusan</option>
                            <option v-for="j in jurusanList" :key="j.id" :value="j.id">
                                {{ j.kode_jurusan }} — {{ j.nama_jurusan }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm w-100 rounded-3 fw-semibold"
                                style="background:var(--bk-primary);color:#fff;"
                                @click="loadPenjurusan" id="btn-filter-penjurusan">
                            <i class="bi bi-funnel me-1"></i> Terapkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bk-card p-0 overflow-hidden">
                <div v-if="penjurusanData?.length > 0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" id="tbl-penjurusan">
                            <thead style="background:var(--bk-bg);border-bottom:2px solid var(--bk-border);">
                                <tr>
                                    <th class="ps-4 py-3 fw-semibold fs-7 text-muted">Siswa</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Kelas</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Pilihan Jurusan</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Status</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Dikunci</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted">Diajukan Oleh</th>
                                    <th class="py-3 fw-semibold fs-7 text-muted text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in filteredPenjurusan" :key="p.id">
                                    <td class="ps-4">
                                        <div class="fw-semibold">{{ p.nama_siswa }}</div>
                                        <div class="text-muted fs-8 font-monospace">{{ p.nisn }}</div>
                                    </td>
                                    <td class="fs-7 text-muted">{{ p.nama_kelas || '—' }}</td>
                                    <td>
                                        <span class="fw-semibold" style="color:var(--bk-primary);">{{ p.kode_jurusan }}</span>
                                        <div class="text-muted fs-8">{{ p.nama_jurusan }}</div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill fw-semibold fs-8 px-3 py-1"
                                              :style="statusStyle(p.status)">
                                            {{ p.status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <i v-if="p.dikunci == 1" class="bi bi-lock-fill text-warning fs-5" title="Terkunci"></i>
                                        <i v-else class="bi bi-unlock text-muted fs-5" title="Tidak Terkunci"></i>
                                    </td>
                                    <td class="fs-7 text-muted">{{ p.diajukan_oleh }}</td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                                            <!-- Verifikasi -->
                                            <button v-if="p.status === 'Diajukan' && !p.dikunci"
                                                    class="btn btn-xs btn-success rounded-2 fw-semibold"
                                                    style="font-size:.72rem;padding:3px 8px;"
                                                    @click="doVerifikasi(p, 'Verifikasi')"
                                                    :id="'btn-verif-' + p.id">
                                                <i class="bi bi-check-lg"></i> Verifikasi
                                            </button>
                                            <!-- Tolak -->
                                            <button v-if="p.status === 'Diajukan' && !p.dikunci"
                                                    class="btn btn-xs btn-danger rounded-2 fw-semibold"
                                                    style="font-size:.72rem;padding:3px 8px;"
                                                    @click="doVerifikasi(p, 'Tolak')"
                                                    :id="'btn-tolak-' + p.id">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                            <!-- Override -->
                                            <button class="btn btn-xs rounded-2 fw-semibold"
                                                    style="font-size:.72rem;padding:3px 8px;background:var(--bk-primary);color:#fff;"
                                                    @click="openOverride(p)"
                                                    :id="'btn-override-' + p.id">
                                                <i class="bi bi-arrow-repeat"></i> Override
                                            </button>
                                            <!-- Buka/Kunci -->
                                            <button class="btn btn-xs rounded-2 fw-semibold"
                                                    :style="p.dikunci == 1 ? 'background:#fef3c7;color:#92400e;' : 'background:#f0fdf4;color:#166534;'"
                                                    style="font-size:.72rem;padding:3px 8px;"
                                                    @click="doToggleKunci(p)"
                                                    :id="'btn-kunci-' + p.id">
                                                <i :class="p.dikunci == 1 ? 'bi bi-unlock-fill' : 'bi bi-lock-fill'"></i>
                                                {{ p.dikunci == 1 ? 'Buka' : 'Kunci' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="text-center py-5 text-muted">
                    <i class="bi bi-diagram-3 fs-1 d-block mb-2"></i>
                    <p class="mb-0">Belum ada data pilihan penjurusan untuk filter ini.</p>
                    <p class="fs-7">Pastikan siswa sudah mengajukan pilihan jurusan mandiri.</p>
                </div>
            </div>
        </div>

        <!-- ═══ MODAL OVERRIDE ══════════════════════════════════════ -->
        <div v-if="overrideModal.show"
             class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
             style="background:rgba(0,0,0,0.55);z-index:9999;"
             id="modal-override-backdrop">
            <div class="bg-white rounded-4 shadow-lg p-4" style="max-width:520px;width:92%;">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="kpi-icon flex-shrink-0" style="background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Override Pilihan Jurusan</h5>
                        <p class="text-muted fs-7 mb-0">Siswa: <strong>{{ overrideModal.siswa.nama_siswa }}</strong></p>
                    </div>
                </div>

                <div class="mb-3 p-3 rounded-3" style="background:#fef3c7;border:1px solid #fde68a;">
                    <p class="fs-7 fw-semibold mb-1" style="color:#92400e;">⚠️ Peringatan ACID Lock</p>
                    <p class="fs-8 mb-0 text-muted">Override akan mengubah jurusan siswa secara permanen, mengunci pilihan,
                    dan mencatat tindakan di log audit. Tindakan ini tidak bisa dibatalkan tanpa membuka kunci manual.</p>
                </div>

                <div class="mb-3">
                    <label for="input-jurusan-sekarang" class="form-label fw-semibold fs-7">Jurusan Saat Ini</label>
                    <input type="text" class="form-control rounded-3 bg-light" readonly
                           id="input-jurusan-sekarang" name="jurusan_sekarang"
                           :value="overrideModal.siswa.kode_jurusan + ' — ' + overrideModal.siswa.nama_jurusan">
                </div>

                <div class="mb-3">
                    <label for="select-override-jurusan" class="form-label fw-semibold fs-7">Jurusan Tujuan Override <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3" v-model="overrideModal.id_jurusan_baru"
                            id="select-override-jurusan" name="id_jurusan_baru">
                        <option value="">-- Pilih Jurusan Tujuan --</option>
                        <option v-for="j in jurusanList" :key="j.id" :value="j.id"
                                :disabled="j.id == overrideModal.siswa.id_jurusan">
                            {{ j.kode_jurusan }} — {{ j.nama_jurusan }}
                        </option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="input-override-catatan" class="form-label fw-semibold fs-7">Alasan Override <span class="text-danger">*</span> <small class="text-muted fw-normal">(wajib untuk audit)</small></label>
                    <textarea class="form-control rounded-3" rows="3"
                              v-model="overrideModal.catatan_bk"
                              placeholder="Tuliskan alasan resmi penggantian jurusan (contoh: hasil tes psikologi, kapasitas penuh, rekomendasi BK)..."
                              id="input-override-catatan" name="catatan_bk"></textarea>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary rounded-3" style="color:#334155; border-color:#94a3b8;"
                            @click="overrideModal.show = false" id="btn-batal-override">
                        Batal
                    </button>
                    <button class="btn rounded-3 fw-semibold"
                            style="background:var(--bk-primary);color:#fff;"
                            :disabled="loadingOverride"
                            @click="submitOverride" id="btn-konfirmasi-override">
                        <span v-if="loadingOverride" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-arrow-repeat me-2"></i>
                        {{ loadingOverride ? 'Memproses...' : 'Konfirmasi Override' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 3: TRACER STUDY
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && in_array('tracer', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'tracer'">
        <div class="bk-card p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="kpi-icon" style="background:#ecfdf5;color:#10b981;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0">Monitoring Tracer Study Alumni</h5>
                    <p class="text-muted fs-7 mb-0">Grafik dan statistik penelusuran alumni (kuliah vs bekerja).</p>
                </div>
            </div>

            <!-- KPI Tracer -->
            <div v-if="loadingTracer" class="text-center py-4">
                <div class="spinner-border text-success"></div>
            </div>
            <div v-else>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="kpi-card text-center">
                            <div class="kpi-icon mx-auto mb-2" style="background:#ecfdf5;color:#10b981;">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                            <div class="kpi-value" style="color:#10b981;">{{ tracerData.kuliah }}</div>
                            <p class="text-muted fs-8 mb-0">Alumni Kuliah</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card text-center">
                            <div class="kpi-icon mx-auto mb-2" style="background:#eff6ff;color:#2563eb;">
                                <i class="bi bi-briefcase-fill"></i>
                            </div>
                            <div class="kpi-value" style="color:#2563eb;">{{ tracerData.pekerjaan }}</div>
                            <p class="text-muted fs-8 mb-0">Alumni Bekerja</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card text-center">
                            <div class="kpi-icon mx-auto mb-2" style="background:var(--bk-p-light);color:var(--bk-primary);">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div class="kpi-value" style="color:var(--bk-primary);">{{ tracerData.total }}</div>
                            <p class="text-muted fs-8 mb-0">Total Rekaman</p>
                        </div>
                    </div>
                </div>
                <!-- Pie Chart Visual Sederhana (CSS-only) -->
                <div class="bk-form-card p-4 text-center" v-if="tracerData.total > 0">
                    <p class="text-muted fs-7 mb-3">Distribusi Alumni</p>
                    <div class="d-flex justify-content-center gap-4 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pie-legend-dot" style="background:#10b981;"></span>
                            <span class="fs-7">Kuliah: <strong>{{ Math.round(tracerData.kuliah / tracerData.total * 100) }}%</strong></span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="pie-legend-dot" style="background:#2563eb;"></span>
                            <span class="fs-7">Bekerja: <strong>{{ Math.round(tracerData.pekerjaan / tracerData.total * 100) }}%</strong></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="<?= $baseUrl ?>/tracer-study" class="btn btn-sm rounded-3"
                           style="background:var(--bk-primary);color:#fff;">
                            <i class="bi bi-arrow-up-right-circle me-1"></i> Buka Halaman Tracer Lengkap
                        </a>
                    </div>
                </div>
                <div v-else class="text-center py-4 text-muted">
                    <i class="bi bi-mortarboard fs-1 d-block mb-2"></i>
                    Belum ada data tracer study alumni.
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>



    <!-- ═══════════════════════════════════════════════════════════
         TAB 5: REKAM KASUS & JURNAL BK
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && (in_array('jurnal', $allowed_bk_tabs) || in_array('p_dashboard', $allowed_bk_tabs))): ?>
<div v-show="activeTab === 'jurnal'">

        <!-- Alert Feedback -->
        <div v-if="alertJurnal.msg" :class="'alert alert-' + alertJurnal.type + ' border-0 rounded-4 mb-3'" role="alert">
            {{ alertJurnal.msg }}
            <button type="button" class="btn-close float-end" @click="alertJurnal.msg = ''"></button>
        </div>

        <div class="row g-4">
            <!-- Full Width: Tabel Riwayat Kasus -->
            <div class="col-12">
                <div class="bk-card p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold mb-0" style="color:var(--bk-primary);">
                                <i class="bi bi-journal-text me-2"></i>Riwayat Kasus & Jurnal Konseling
                            </h6>
                            <small class="text-muted fs-8" v-if="kasusList?.length > 0">
                                {{ kasusList?.length || 0 }} catatan ditemukan
                            </small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary rounded-3" style="color:#334155; border-color:#94a3b8;"
                                    @click="loadKasus" :disabled="loadingKasusList">
                                <span v-if="loadingKasusList" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="bi bi-arrow-clockwise me-1"></i>Refresh
                            </button>
                            <button type="button" @click="openTambahKasusModal" class="btn btn-sm btn-primary rounded-3 px-3 fw-semibold d-inline-flex align-items-center gap-1">
                                <i class="bi bi-plus-lg fs-6"></i> Catat Jurnal Kasus
                            </button>
                        </div>
                    </div>

                    <!-- Search & Status Filter Bar -->
                    <div v-if="kasusList?.length > 0" class="row g-2 mb-3 align-items-center">
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                <label for="input-search-kasus-list" class="visually-hidden">Cari riwayat kasus</label>
                                <input type="text" class="form-control rounded-end-3 shadow-none"
                                       v-model="kasusListSearch"
                                       placeholder="Filter nama siswa, kelas, jenis kasus..."
                                       id="input-search-kasus-list" name="search_kasus_list">
                                <button v-if="kasusListSearch" class="btn btn-outline-secondary btn-sm" style="color:#334155; border-color:#94a3b8;"
                                        @click="kasusListSearch=''" type="button">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted fw-semibold" style="font-size:0.75rem;"><i class="bi bi-funnel me-1"></i>Status:</span>
                                <select class="form-select form-select-sm rounded-end-3 fs-8 fw-semibold" v-model="kasusStatusFilter" id="select-filter-status-kasus">
                                    <option value="aktif">🟢 Kasus Aktif (Terbuka & Proses)</option>
                                    <option value="Terbuka">🔵 Terbuka</option>
                                    <option value="Proses">🟡 Dalam Proses</option>
                                    <option value="Selesai">⚪ Selesai (Tertutup)</option>
                                    <option value="semua">📋 Semua Status Kasus</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div v-if="loadingKasusList" class="text-center py-4">
                        <div class="spinner-border" style="color:var(--bk-primary);"></div>
                    </div>

                    <div v-else-if="filteredKasusList?.length > 0" class="table-responsive" style="max-height:600px;overflow-y:auto;">
                        <table class="table table-hover align-middle fs-8" id="tbl-kasus" style="font-size:0.82rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-nowrap">Tanggal</th>
                                    <th>Siswa</th>
                                    <th class="text-nowrap">Kelas Saat Itu</th>
                                    <th>Jenis</th>
                                    <th>Status</th>
                                    <th class="text-center"><i class="bi bi-lock" title="Privasi"></i></th>
                                    <th class="text-end pe-3" style="width:130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="k in paginatedKasusList" :key="k.id">
                                    <!-- Tanggal -->
                                    <td class="text-muted text-nowrap" style="width:90px;">
                                        {{ k.tanggal_konseling }}
                                    </td>
                                    <!-- Siswa + NIS/NISN -->
                                    <td>
                                        <div class="fw-semibold text-dark">{{ k.nama_siswa || '—' }}</div>
                                        <div class="d-flex gap-1 flex-wrap mt-1">
                                            <span v-if="k.nis" class="badge rounded-pill"
                                                  style="background:#dcfce7;color:#166534;font-size:.68rem;">
                                                NIS: {{ k.nis }}
                                            </span>
                                            <span v-if="k.nisn" class="badge rounded-pill"
                                                  style="background:#fef3c7;color:#92400e;font-size:.68rem;">
                                                NISN: {{ k.nisn }}
                                            </span>
                                        </div>
                                    </td>
                                    <!-- Kelas Snapshot (Historical) -->
                                    <td style="min-width:110px;">
                                        <div v-if="k.kelas_saat_kejadian" class="d-flex align-items-center gap-1">
                                            <span class="badge rounded-pill"
                                                  style="background:#dbeafe;color:#1e40af;font-size:.7rem;">
                                                <i class="bi bi-camera me-1" title="Data historis terkunci saat kasus ini direkam"></i>{{ k.kelas_saat_kejadian }}
                                            </span>
                                        </div>
                                        <span v-else class="text-muted" style="font-size:.75rem;">
                                            {{ k.nama_kelas || '—' }}
                                        </span>
                                    </td>
                                    <!-- Jenis -->
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:.72rem;">
                                            {{ k.jenis_kasus }}
                                        </span>
                                    </td>
                                    <!-- Status -->
                                    <td>
                                        <span class="badge rounded-pill fw-semibold"
                                              :class="{
                                                  'badge-terbuka': k.status_kasus === 'Terbuka',
                                                  'badge-proses':  k.status_kasus === 'Proses',
                                                  'badge-selesai': k.status_kasus === 'Selesai'
                                              }">{{ k.status_kasus }}</span>
                                    </td>
                                    <!-- Privasi -->
                                    <td class="text-center">
                                        <i v-if="k.is_rahasia == 1"
                                           class="bi bi-lock-fill text-warning" title="Rahasia — hanya terlihat oleh Guru BK yang merekam"></i>
                                        <i v-else
                                           class="bi bi-unlock text-muted" title="Tidak Rahasia"></i>
                                    </td>
                                    <!-- Aksi -->
                                    <td class="text-end pe-3">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button v-if="canEditKasus(k)"
                                                    class="btn btn-xs btn-outline-warning rounded-2 py-0 px-2 fw-semibold"
                                                    style="font-size:0.7rem; line-height:1.5;"
                                                    @click="openEditKasus(k)"
                                                    :id="'btn-edit-kasus-' + k.id"
                                                    title="Edit Catatan Kasus">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button v-if="canEditKasus(k)"
                                                    class="btn btn-xs btn-outline-primary rounded-2 py-0 px-2 fw-semibold"
                                                    style="font-size:0.7rem; line-height:1.5;"
                                                    @click="openChangeStatus(k)"
                                                    :id="'btn-status-' + k.id"
                                                    title="Ubah Status Kasus">
                                                <i class="bi bi-pencil-square"></i> Status
                                            </button>
                                            <button class="btn btn-xs btn-outline-secondary rounded-2 py-0 px-2 fw-semibold"
                                                    style="font-size:0.7rem; line-height:1.5; color:#475569; border-color:#cbd5e1;"
                                                    @click="openLogs(k)"
                                                    :id="'btn-logs-' + k.id"
                                                    title="Riwayat Penanganan / Log Kasus">
                                                <i class="bi bi-clock-history"></i> Log
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls: Rekam Kasus -->
                    <div v-if="filteredKasusList?.length > 0" class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                        <div class="text-muted fs-8">
                            Menampilkan <strong>{{ (currentPageKasus - 1) * perPageKasus + 1 }}</strong> - <strong>{{ Math.min(currentPageKasus * perPageKasus, filteredKasusList.length) }}</strong> dari <strong>{{ filteredKasusList.length }}</strong> catatan
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button class="btn btn-xs btn-outline-secondary rounded-2 px-2" :disabled="currentPageKasus === 1" @click="currentPageKasus--">
                                <i class="bi bi-chevron-left me-1"></i>Sebelumnya
                            </button>
                            <template v-for="p in totalKasusPages" :key="'kasus_p_' + p">
                                <button v-if="p === 1 || p === totalKasusPages || (p >= currentPageKasus - 1 && p <= currentPageKasus + 1)"
                                        class="btn btn-xs rounded-2 px-2.5 fw-semibold"
                                        :class="p === currentPageKasus ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary text-dark'"
                                        @click="currentPageKasus = p">
                                    {{ p }}
                                </button>
                                <span v-else-if="(p === 2 && currentPageKasus > 3) || (p === totalKasusPages - 1 && currentPageKasus < totalKasusPages - 2)" class="px-1 text-muted fs-8">...</span>
                            </template>
                            <button class="btn btn-xs btn-outline-secondary rounded-2 px-2" :disabled="currentPageKasus === totalKasusPages" @click="currentPageKasus++">
                                Selanjutnya<i class="bi bi-chevron-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Empty state setelah filter -->
                    <div v-else-if="(kasusList?.length > 0) && (filteredKasusList?.length === 0)"
                         class="text-center py-4 text-muted">
                        <i class="bi bi-funnel fs-2 d-block mb-2 text-warning"></i>
                        Tidak ada kasus konseling yang cocok dengan kata kunci atau filter status terpilih.
                        <div class="mt-2">
                            <button type="button" @click="kasusListSearch=''; kasusStatusFilter='semua';" class="btn btn-xs btn-outline-secondary rounded-pill px-3">
                                Reset Filter (Tampilkan Semua Status)
                            </button>
                        </div>
                    </div>

                    <!-- Benar-benar kosong -->
                    <div v-else class="text-center py-5 text-muted">
                        <i class="bi bi-journal fs-1 d-block mb-2"></i>
                        Belum ada catatan kasus konseling. Klik tombol <strong>+ Catat Jurnal Kasus</strong> untuk menambahkan catatan baru.
                        <div class="mt-3">
                            <button type="button" @click="openTambahKasusModal" class="btn btn-sm btn-primary rounded-3 px-3">
                                <i class="bi bi-plus-lg me-1"></i> Catat Jurnal Kasus
                            </button>
                        </div>
                    </div>

                    <!-- Info badge snapshot -->
                    <div v-if="kasusList?.length > 0" class="mt-3 rounded-3 px-3 py-2 d-flex align-items-start gap-2"
                         style="background:#eff6ff;border:1px solid #bfdbfe;font-size:.75rem;">
                        <i class="bi bi-camera text-primary mt-1 flex-shrink-0"></i>
                        <span class="text-muted">
                            Kolom <strong>"Kelas Saat Itu"</strong> ditandai <i class="bi bi-camera text-primary"></i>
                            menyimpan data historis yang terkunci saat kasus direkam.
                            Jika siswa naik kelas, histori ini tetap menunjukkan kelas lamanya.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Popup Form Rekam Kasus & Jurnal Konseling (Harmonized UI/UX & Tall Spacious Dropdown) -->
        <div class="modal fade" id="modalFormKasus" tabindex="-1" aria-labelledby="modalFormKasusLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow" style="min-height: 560px;">
                    <div class="modal-header border-bottom py-3 bg-light px-4">
                        <h6 class="modal-title fw-bold text-dark d-flex align-items-center" id="modalFormKasusLabel">
                            <i class="bi bi-journal-plus me-2" style="color:var(--bk-primary);"></i>
                            {{ formKasus.id ? 'Edit Rekam Kasus & Jurnal Konseling' : 'Tambah Rekam Kasus & Jurnal Konseling' }}
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closeKasusModal"></button>
                    </div>
                    
                    <div class="modal-body p-4" style="min-height: 480px; overflow: visible !important;">
                        <!-- Alert Info / Warning Form -->
                        <div v-if="alertJurnal.msg" :class="'alert alert-' + alertJurnal.type + ' border-0 rounded-3 py-2 px-3 mb-3 fs-7 animate-fade-in'">
                            {{ alertJurnal.msg }}
                        </div>

                        <!-- A. TAMPILAN JIKA SISWA BELUM DIPILIH (Pencarian Nama) -->
                        <div v-if="!formKasus.id_siswa" class="animate-fade-in py-2" style="min-height: 420px;">
                            <div class="p-3 mb-4 rounded-4 border bg-light text-center">
                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-white shadow-sm" style="width:48px; height:48px;">
                                    <i class="bi bi-person-search fs-4 text-primary"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Cari & Pilih Siswa</h6>
                                <p class="text-muted fs-8 mb-0 mx-auto" style="max-width:400px;">
                                    Ketik nama lengkap, NIS, atau NISN siswa untuk mulai merekam catatan kasus konseling.
                                </p>
                            </div>

                            <div class="mb-5 pb-4">
                                <label for="input-siswa-kasus-modal" class="form-label fw-bold fs-7 mb-1 text-dark">
                                    Pilih Siswa <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                        <input type="text" 
                                               class="form-control form-control-sm border-start-0 ps-1 rounded-end-3"
                                               v-model="kasusSearchSiswa"
                                               @input="searchSiswaDebounce"
                                               @blur="hideDropdownDelay"
                                               @focus="onSearchFocus"
                                               placeholder="Ketik Nama, NISN, atau NIK Siswa..."
                                               id="input-siswa-kasus-modal"
                                               name="siswa_kasus"
                                               autocomplete="off">
                                    </div>

                                    <!-- Dropdown Autocomplete Pencarian Siswa -->
                                    <div v-if="showSiswaDropdown && siswaOptions?.length > 0"
                                         class="position-absolute w-100 bg-white border rounded-3 shadow-lg p-1 mt-1"
                                         style="max-height: 240px; overflow-y: auto; z-index: 1070;">
                                        <div v-for="s in siswaOptions" :key="s.id"
                                             @mousedown.prevent="selectSiswa(s)"
                                             class="p-2 rounded-2 hover-bg-slate cursor-pointer fs-7 d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold text-dark">{{ s.nama_lengkap }}</div>
                                                <div class="text-muted fs-8">NISN: {{ s.nisn || '-' }} | Kelas: {{ s.nama_kelas || '-' }}</div>
                                            </div>
                                            <i class="bi bi-plus-circle-fill text-primary fs-6"></i>
                                        </div>
                                    </div>
                                    <div v-else-if="showSiswaDropdown && loadingSearchSiswa" 
                                         class="position-absolute w-100 bg-white border rounded-3 shadow-lg p-3 text-center mt-1"
                                         style="z-index: 1070;">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                        <span class="fs-7 text-muted ms-2">Mencari data siswa...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- B. TAMPILAN JIKA SISWA SUDAH DIPILIH (Form Rekam Kasus Dinamis) -->
                        <div v-else class="animate-fade-in">
                            <!-- Selected Student Card (Styled like Form Prestasi) -->
                            <div class="mb-3">
                                <label class="form-label fw-bold fs-7 mb-1 text-dark">
                                    Siswa Terpilih <span class="text-danger">*</span>
                                </label>

                                <!-- Jika Mode Edit: Tampilkan Badge Siswa Terkunci -->
                                <div v-if="formKasus.id" class="p-2 px-3 border rounded-3 bg-light d-flex align-items-center justify-content-between mb-2">
                                    <span class="fs-8 text-muted fw-semibold d-flex align-items-center gap-1">
                                        <i class="bi bi-lock-fill text-warning"></i>
                                        Siswa Otomatis Terkunci (Mode Edit Data)
                                    </span>
                                    <span class="badge bg-secondary rounded-pill px-2 py-1 fs-9">Terkunci</span>
                                </div>

                                <div class="badge d-flex align-items-center justify-content-between p-2 rounded-3 text-dark w-100 text-start" 
                                     style="background: var(--bk-p-light); color: var(--bk-primary); border: 1px solid #ddd;">
                                    <div>
                                        <span class="fw-bold text-dark fs-7 me-2">{{ selectedSiswaInfo.nama_lengkap }}</span>
                                        <span class="badge bg-primary rounded-pill px-2 py-1 fs-9 me-2">Kelas: {{ selectedSiswaInfo.nama_kelas || '-' }}</span>
                                        <span class="text-muted fs-8">NISN: {{ selectedSiswaInfo.nisn || '-' }}</span>
                                    </div>
                                    <button v-if="!formKasus.id" type="button" class="btn btn-xs btn-outline-secondary rounded-2 py-0 px-2 fw-semibold"
                                            @click="clearSiswa" id="btn-ganti-siswa-modal" title="Ganti Siswa">
                                        <i class="bi bi-arrow-left-right me-1"></i> Ganti
                                    </button>
                                    <i v-else class="bi bi-lock-fill text-muted ms-1" style="font-size:0.85rem;" title="Siswa terkunci saat edit data"></i>
                                </div>
                            </div>

                            <!-- Form Input Details -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label for="input-tgl-konseling-modal" class="form-label fw-bold fs-7 mb-1 text-dark">
                                        Tanggal Catat Kasus <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control form-control-sm rounded-3"
                                           v-model="formKasus.tanggal_konseling"
                                           :max="today" id="input-tgl-konseling-modal" name="tanggal_konseling">
                                </div>
                                <div class="col-md-6">
                                    <label for="select-jenis-kasus-modal" class="form-label fw-bold fs-7 mb-1 text-dark">
                                        Jenis Kasus <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-sm rounded-3" v-model="formKasus.jenis_kasus" id="select-jenis-kasus-modal" name="jenis_kasus">
                                        <option value="">— Pilih Jenis Kasus —</option>
                                        <option value="Akademik">Akademik</option>
                                        <option value="Perilaku">Perilaku</option>
                                        <option value="Keluarga">Keluarga</option>
                                        <option value="Karir">Karir & Masa Depan</option>
                                        <option value="Kesehatan Mental">Kesehatan Mental</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="input-catatan-kasus-modal" class="form-label fw-bold fs-7 mb-1 text-dark">
                                    Catatan Konseling <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control form-control-sm rounded-3" rows="3"
                                          v-model="formKasus.catatan"
                                          placeholder="Tuliskan catatan observasi, keluhan, dan temuan konseling secara lengkap..."
                                          id="input-catatan-kasus-modal" name="catatan_kasus"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="input-tindak-lanjut-modal" class="form-label fw-bold fs-7 mb-1 text-dark">
                                    Rencana Tindak Lanjut
                                </label>
                                <textarea class="form-control form-control-sm rounded-3" rows="2"
                                          v-model="formKasus.tindak_lanjut"
                                          placeholder="Rencana tindak lanjut, konseling lanjutan, rekomendasi..."
                                          id="input-tindak-lanjut-modal" name="tindak_lanjut"></textarea>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label for="select-status-kasus-modal" class="form-label fw-bold fs-7 mb-1 text-dark">
                                        Status Kasus
                                    </label>
                                    <select class="form-select form-select-sm rounded-3" v-model="formKasus.status_kasus" id="select-status-kasus-modal" name="status_kasus">
                                        <option value="Terbuka">Terbuka (Baru Dibuat)</option>
                                        <option value="Proses">Dalam Proses Penanganan</option>
                                        <option value="Selesai">Selesai (Tertutup)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="cb-rahasia-modal" name="is_rahasia"
                                               v-model="formKasus.is_rahasia" :true-value="1" :false-value="0">
                                        <label class="form-check-label fw-bold fs-7 text-dark" for="cb-rahasia-modal">
                                            <i class="bi bi-lock-fill me-1 text-warning"></i>Rahasia (Hanya Konselor BK)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer border-top bg-light py-2 px-4 d-flex justify-content-end gap-2">
                        <button type="button" @click="closeKasusModal" class="btn btn-sm btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button v-if="formKasus.id_siswa" class="btn btn-sm btn-primary rounded-3 px-4 fw-bold" :disabled="loadingKasus"
                                @click="submitKasus" id="btn-simpan-kasus-modal">
                            <span v-if="loadingKasus" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-floppy me-1"></i>
                            {{ loadingKasus ? 'Menyimpan...' : 'Simpan Rekam Kasus' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 6: PRESTASI SISWA
    ════════════════════════════════════════════════════════════ -->
    <?php if(empty($allowed_bk_tabs) || in_array('prestasi', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'prestasi'">
        <!-- Warning untuk Super Admin jika belum memilih sekolah -->
        <div v-if="userRole === 'super_admin' && !currentTenantId" class="text-center py-5">
            <div class="card border-0 shadow-sm rounded-4 p-5 mx-auto animate-fade-in" style="max-width: 500px; background: #fff;">
                <i class="bi bi-funnel text-warning fs-1 mb-3"></i>
                <h5 class="fw-bold mb-2 text-dark">Pilih Sekolah Terlebih Dahulu</h5>
                <p class="text-muted fs-7 mb-0">
                    Silakan gunakan filter di bagian atas halaman untuk memilih sekolah sebelum mengelola data prestasi siswa.
                </p>
            </div>
        </div>

        <div v-else class="row g-4">
            <!-- Full Width: Tabel List Prestasi -->
            <div class="col-12">
                <div class="bk-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-list-stars me-2" style="color:var(--bk-primary);"></i>
                            Daftar Prestasi Siswa
                        </h6>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary rounded-pill px-3 py-2 fs-7">{{ filteredPrestasiList?.length || prestasiList?.length || 0 }} Data</span>
                            <button type="button" @click="exportPrestasiExcel" class="btn btn-sm btn-outline-success rounded-3 px-3 fw-semibold d-inline-flex align-items-center gap-1" title="Unduh Berkas Excel (.xlsx) dengan Filter Tahun Ajaran">
                                <i class="bi bi-file-earmark-excel fs-6"></i> Download Excel (.xlsx)
                            </button>
                            <button type="button" @click="openTambahPrestasiModal" class="btn btn-sm btn-primary rounded-3 px-3 fw-semibold d-inline-flex align-items-center gap-1">
                                <i class="bi bi-plus-lg fs-6"></i> Tambah Data Prestasi
                            </button>
                        </div>
                    </div>

                    <!-- Filter Bar Prestasi Siswa -->
                    <div v-if="prestasiList?.length > 0" class="row g-2 mb-3 align-items-center">
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control rounded-end-3 shadow-none"
                                       v-model="filterPrestasiSearch"
                                       placeholder="Cari nama siswa, lomba, bidang, sertifikat..."
                                       id="input-search-prestasi-list">
                                <button v-if="filterPrestasiSearch" class="btn btn-outline-secondary btn-sm" @click="filterPrestasiSearch=''" type="button">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted fw-semibold" style="font-size:0.75rem;"><i class="bi bi-calendar3 me-1"></i>Tahun:</span>
                                <select class="form-select form-select-sm rounded-end-3 fs-8 fw-semibold" v-model="filterPrestasiTahunAjaran" id="select-filter-prestasi-ta">
                                    <option value="">Semua Tahun Ajaran</option>
                                    <option v-for="y in activeYearsList" :key="y.id" :value="y.id">
                                        {{ y.tahun_ajaran }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted fw-semibold" style="font-size:0.75rem;"><i class="bi bi-trophy me-1"></i>Tingkat:</span>
                                <select class="form-select form-select-sm rounded-end-3 fs-8 fw-semibold" v-model="filterPrestasiTingkat" id="select-filter-prestasi-tingkat">
                                    <option value="">Semua Tingkat Kejuaraan</option>
                                    <option value="Kabupaten/Kota">Kabupaten/Kota</option>
                                    <option value="Provinsi">Provinsi</option>
                                    <option value="Nasional">Nasional</option>
                                    <option value="Internasional">Internasional</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loadingPrestasiList" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="text-muted mt-2 fs-7">Memuat daftar prestasi...</p>
                    </div>

                    <!-- Table List -->
                    <div v-else-if="filteredPrestasiList?.length > 0" class="table-responsive" style="max-height: 800px; overflow-y: auto;">
                        <table class="table table-hover align-middle table-sm border-top" style="font-size:0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2" style="width:30%;">Siswa & Prestasi</th>
                                    <th class="py-2" style="width:30%;">Detail Event</th>
                                    <th class="py-2 text-center" style="width:10%;">Poin</th>
                                    <th class="py-2 text-center" style="width:15%;">Berkas</th>
                                    <th class="pe-3 py-2 text-end" style="width:15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in paginatedPrestasiList" :key="p.id" class="align-middle">
                                    <td class="ps-3 py-3">
                                        <!-- Siswa Names -->
                                        <div class="mb-1">
                                            <span v-for="(s, idx) in (p.siswa_list || [])" :key="s.id || idx">
                                                <span class="fw-bold text-dark">{{ s.nama_lengkap }}</span>
                                                <span class="text-muted fs-8"> ({{ s.nama_kelas || '-' }})</span>
                                                <span v-if="p.siswa_list && idx < p.siswa_list.length - 1">, </span>
                                            </span>
                                        </div>
                                        <!-- Kategori & Bidang -->
                                        <div class="d-flex align-items-center gap-1 flex-wrap mt-1">
                                            <span class="badge bg-secondary rounded-pill" style="font-size: 0.65rem;">{{ p.kategori }}</span>
                                            <span class="badge rounded-pill" style="font-size: 0.65rem; background: var(--bk-p-light); color: var(--bk-primary);">{{ p.bidang_lomba }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div class="fw-bold text-slate-800">{{ p.nama_lomba }}</div>
                                        <div class="text-muted fs-8">
                                            <span class="badge bg-light text-dark border me-1" style="font-size: 0.62rem;">{{ p.tingkat_kejuaraan }}</span>
                                            <span class="fw-semibold text-primary">{{ p.juara }}</span>
                                        </div>
                                        <div class="text-muted fs-8 mt-1">
                                            <i class="bi bi-calendar-event me-1"></i>{{ p.tanggal_lomba }} | {{ p.tempat_lomba }}
                                        </div>
                                        <div v-if="p.guru_pendamping" class="text-muted fs-8 mt-1">
                                            <i class="bi bi-person-badge me-1"></i>Pendamping: {{ p.guru_pendamping }}
                                        </div>
                                    </td>
                                    <td class="text-center py-3 fw-bold text-success fs-7">
                                        {{ p.poin_prestasi }}
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                                            <a v-if="p.foto_bukti_prestasi" 
                                               :href="getFileUrl(p.foto_bukti_prestasi)" 
                                               target="_blank" 
                                               class="btn btn-xs btn-outline-info p-1 rounded-2" 
                                               title="Foto Bukti Sertifikat">
                                                <i class="bi bi-file-earmark-image"></i>
                                            </a>
                                            <a v-if="p.foto_siswa_prestasi" 
                                               :href="getFileUrl(p.foto_siswa_prestasi)" 
                                               target="_blank" 
                                               class="btn btn-xs btn-outline-info p-1 rounded-2" 
                                               title="Foto Siswa / Penyerahan Juara">
                                                <i class="bi bi-person-badge"></i>
                                            </a>
                                            <a v-if="p.foto_kegiatan_lomba" 
                                               :href="getFileUrl(p.foto_kegiatan_lomba)" 
                                               target="_blank" 
                                               class="btn btn-xs btn-outline-info p-1 rounded-2" 
                                               title="Foto Kegiatan">
                                                <i class="bi bi-camera"></i>
                                            </a>
                                            <a v-if="p.surat_tugas_pdf" 
                                               :href="getFileUrl(p.surat_tugas_pdf)" 
                                               target="_blank" 
                                               class="btn btn-xs btn-outline-info p-1 rounded-2" 
                                               title="Surat Tugas">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                            <span v-if="!p.foto_bukti_prestasi && !p.foto_siswa_prestasi && !p.foto_kegiatan_lomba && !p.surat_tugas_pdf" class="text-muted fs-8">—</span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3 py-3">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button @click="openEditPrestasiModal(p)" 
                                                    class="btn btn-xs btn-outline-primary rounded-2 py-0 px-2 fw-semibold" 
                                                    style="font-size:0.7rem; line-height:1.5;" 
                                                    title="Edit Data Prestasi">
                                                <i class="bi bi-pencil me-1"></i> Edit
                                            </button>
                                            <button @click="deletePrestasi(p.id)" 
                                                    class="btn btn-xs btn-outline-danger rounded-2 py-0 px-2 fw-semibold" 
                                                    style="font-size:0.7rem; line-height:1.5;" 
                                                    title="Hapus Data">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-5">
                        <i class="bi bi-trophy text-muted display-4 mb-3 d-block opacity-50"></i>
                        <h6 class="fw-bold text-dark mb-1">Belum Ada Data Prestasi</h6>
                        <p class="text-muted fs-7 mb-3">Klik tombol <strong>+ Tambah Data Prestasi</strong> untuk menambahkan catatan prestasi siswa.</p>
                        <button type="button" @click="openTambahPrestasiModal" class="btn btn-sm btn-primary rounded-3 px-3">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Prestasi Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Popup Form Input/Edit Prestasi Siswa (Lebih Luas & Modern) -->
        <div class="modal fade" id="modalFormPrestasi" tabindex="-1" aria-labelledby="modalFormPrestasiLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1120px;">
                <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                    <div class="modal-header border-bottom py-3 px-4 bg-light d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/15 text-amber-600 d-flex align-items-center justify-content-center fs-5">
                                <i class="bi bi-trophy-fill"></i>
                            </div>
                            <div>
                                <h6 class="modal-title fw-bold text-dark mb-0" id="modalFormPrestasiLabel">
                                    {{ formPrestasi.id ? 'Edit Data Prestasi Siswa' : 'Tambah Data Prestasi Siswa' }}
                                </h6>
                                <small class="text-muted fs-9">Form pencatatan riwayat penghargaan &amp; sertifikat prestasi siswa</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closePrestasiModal"></button>
                    </div>
                    <div class="modal-body p-4 bg-white">
                        <!-- Alert Info / Warning Form -->
                        <div v-if="alertPrestasi.msg" :class="'alert alert-' + alertPrestasi.type + ' border-0 rounded-3 py-2 px-3 mb-3 fs-7 animate-fade-in'">
                            {{ alertPrestasi.msg }}
                        </div>

                        <form @submit.prevent="submitPrestasi" id="form-prestasi-modal" enctype="multipart/form-data">
                            <div class="row g-4">
                                <!-- KOLOM KIRI: Identitas Siswa & Informasi Lomba -->
                                <div class="col-12 col-lg-6">
                                    <div class="p-0 bg-white border-0 h-100">
                                        <h6 class="fw-bold text-primary fs-7 mb-3 d-flex align-items-center gap-1.5">
                                            <i class="bi bi-person-badge-fill"></i> 1. Identitas Siswa &amp; Lomba
                                        </h6>

                                        <!-- Siswa Selection -->
                                        <div class="mb-3">
                                            <label for="input-prestasi-cari-siswa" class="form-label fw-bold fs-7 mb-1 text-dark">
                                                Pilih Siswa <span class="text-danger">*</span>
                                            </label>
                                            
                                            <!-- Jika Mode Edit: Tampilkan Badge Siswa Terkunci -->
                                            <div v-if="formPrestasi.id" class="p-2 px-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                                                <span class="fs-8 text-muted fw-semibold d-flex align-items-center gap-1">
                                                    <i class="bi bi-lock-fill text-warning"></i>
                                                    Siswa Otomatis Terkunci (Mode Edit Data)
                                                </span>
                                                <span class="badge bg-secondary rounded-pill px-2 py-1 fs-9">Terkunci</span>
                                            </div>
                                            
                                            <!-- Jika Mode Tambah: Tampilkan Input Search Autocomplete -->
                                            <div v-else class="position-relative">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                                    <input type="text" 
                                                           id="input-prestasi-cari-siswa"
                                                           name="prestasi_cari_siswa"
                                                           class="form-control form-control-sm border-start-0 ps-1 rounded-end-3" 
                                                           placeholder="Ketik Nama, NISN, atau NIK Siswa..."
                                                           v-model="prestasiSearchSiswa"
                                                           @input="searchSiswaPrestasiDebounce"
                                                           @focus="showPrestasiSiswaDropdown = true"
                                                           @blur="hidePrestasiDropdownDelay" />
                                                </div>
                                                
                                                <!-- Dropdown Pencarian Siswa -->
                                                <div v-if="showPrestasiSiswaDropdown && prestasiSiswaOptions?.length > 0" 
                                                     class="position-absolute w-100 bg-white border rounded-3 shadow-lg p-1 mt-1 z-3"
                                                     style="max-height: 220px; overflow-y: auto;">
                                                    <div v-for="s in prestasiSiswaOptions" 
                                                         :key="s.id" 
                                                         @mousedown.prevent="selectSiswaPrestasi(s)"
                                                         class="p-2 rounded-2 hover-bg-slate cursor-pointer fs-7 d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div class="fw-bold text-dark">{{ s.nama_lengkap }}</div>
                                                            <div class="text-muted fs-8">NISN: {{ s.nisn }} | Kelas: {{ s.nama_kelas || '-' }}</div>
                                                        </div>
                                                        <i class="bi bi-plus-circle-fill text-primary fs-6"></i>
                                                    </div>
                                                </div>
                                                <div v-else-if="showPrestasiSiswaDropdown && loadingSearchPrestasiSiswa" 
                                                     class="position-absolute w-100 bg-white border rounded-3 shadow-lg p-3 text-center mt-1 z-3">
                                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                                    <span class="fs-7 text-muted ms-2">Mencari...</span>
                                                </div>
                                            </div>

                                            <!-- List Siswa Terpilih -->
                                            <div v-if="selectedPrestasiSiswa?.length > 0" class="mt-2 d-flex flex-wrap gap-2">
                                                <div v-for="s in selectedPrestasiSiswa" :key="s.id" 
                                                     class="badge d-inline-flex align-items-center gap-2 p-2 rounded-3 text-dark" 
                                                     style="background: var(--bk-p-light); color: var(--bk-primary); border: 1px solid #ddd;">
                                                    <span class="fw-semibold">{{ s.nama_lengkap }} ({{ s.nama_kelas || '-' }})</span>
                                                    <button v-if="!formPrestasi.id" type="button" class="btn-close" style="font-size: 0.6rem; margin-left: 5px;" @click="removeSiswaPrestasi(s.id)" title="Hapus dari daftar"></button>
                                                    <i v-else class="bi bi-lock-fill text-muted ms-1" style="font-size:0.75rem;" title="Siswa terkunci saat edit data"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Kategori & Tahun Ajaran -->
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label for="select-prestasi-kategori" class="form-label fw-bold fs-7 mb-1 text-dark">Kategori <span class="text-danger">*</span></label>
                                                <select id="select-prestasi-kategori" name="kategori" v-model="formPrestasi.kategori" class="form-select form-select-sm rounded-3">
                                                    <option value="Personal">Personal (Individu)</option>
                                                    <option value="Regu">Regu (Kelompok)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label for="select-prestasi-tahun-ajaran" class="form-label fw-bold fs-7 mb-1 text-dark">Tahun Ajaran <span class="text-danger">*</span></label>
                                                <select id="select-prestasi-tahun-ajaran" name="tahun_ajaran_id" v-model="formPrestasi.tahun_ajaran_id" class="form-select form-select-sm rounded-3">
                                                    <option value="">— Pilih Tahun —</option>
                                                    <option v-for="y in activeYearsList" :key="y.id" :value="y.id">
                                                        {{ y.tahun_ajaran }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Semester & Tingkat Kejuaraan -->
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label for="select-prestasi-semester" class="form-label fw-bold fs-7 mb-1 text-dark">Semester <span class="text-danger">*</span></label>
                                                <select id="select-prestasi-semester" name="semester" v-model="formPrestasi.semester" class="form-select form-select-sm rounded-3">
                                                    <option value="Ganjil">Ganjil</option>
                                                    <option value="Genap">Genap</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label for="select-prestasi-tingkat" class="form-label fw-bold fs-7 mb-1 text-dark">Tingkat Kejuaraan <span class="text-danger">*</span></label>
                                                <select id="select-prestasi-tingkat" name="tingkat_kejuaraan" v-model="formPrestasi.tingkat_kejuaraan" @change="autoCalculatePrestasiPoint" class="form-select form-select-sm rounded-3">
                                                    <option value="">— Pilih Tingkat —</option>
                                                    <option value="Kabupaten/Kota">Kabupaten/Kota</option>
                                                    <option value="Provinsi">Provinsi</option>
                                                    <option value="Nasional">Nasional</option>
                                                    <option value="Internasional">Internasional</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Bidang Lomba & Nama Lomba -->
                                        <div class="mb-3">
                                            <label for="input-prestasi-bidang" class="form-label fw-bold fs-7 mb-1 text-dark">Bidang Lomba / Prestasi <span class="text-danger">*</span></label>
                                            <input type="text" id="input-prestasi-bidang" name="bidang_lomba" v-model="formPrestasi.bidang_lomba" class="form-control form-control-sm rounded-3" placeholder="Contoh: Sains/OSN, Olahraga/O2SN, Seni, Robotik, dll." />
                                        </div>

                                        <div class="mb-3">
                                            <label for="input-prestasi-nama-lomba" class="form-label fw-bold fs-7 mb-1 text-dark">Nama Perlombaan / Kegiatan <span class="text-danger">*</span></label>
                                            <input type="text" id="input-prestasi-nama-lomba" name="nama_lomba" v-model="formPrestasi.nama_lomba" class="form-control form-control-sm rounded-3" placeholder="Contoh: Olimpiade Matematika Nasional 2026" />
                                        </div>

                                        <!-- Peringkat Juara & Nomor Sertifikat -->
                                        <div class="row g-2 mb-0">
                                            <div class="col-6">
                                                <label for="select-prestasi-juara" class="form-label fw-bold fs-7 mb-1 text-dark">Peringkat Juara <span class="text-danger">*</span></label>
                                                <select id="select-prestasi-juara" name="juara" v-model="formPrestasi.juara" @change="autoCalculatePrestasiPoint" class="form-select form-select-sm rounded-3">
                                                    <option value="">— Pilih Juara —</option>
                                                    <option value="Juara 1">Juara 1</option>
                                                    <option value="Juara 2">Juara 2</option>
                                                    <option value="Juara 3">Juara 3</option>
                                                    <option value="Harapan 1">Juara Harapan 1</option>
                                                    <option value="Harapan 2">Juara Harapan 2</option>
                                                    <option value="Harapan 3">Juara Harapan 3</option>
                                                    <option value="Lainnya">Lainnya (Tulis Keterangan)</option>
                                                </select>
                                            </div>
                                            <div class="col-6" v-if="formPrestasi.juara === 'Lainnya'">
                                                <label for="input-prestasi-juara-lainnya" class="form-label fw-bold fs-7 mb-1 text-dark">Keterangan Juara <span class="text-danger">*</span></label>
                                                <input type="text" id="input-prestasi-juara-lainnya" name="juara_lainnya" v-model="formPrestasi.juara_lainnya" class="form-control form-control-sm rounded-3" placeholder="Contoh: Gold Medal / Favorit" />
                                            </div>
                                            <div class="col-6" v-else>
                                                <label for="input-prestasi-sertifikat" class="form-label fw-bold fs-7 mb-1 text-dark">Nomor Sertifikat / Piagam</label>
                                                <input type="text" id="input-prestasi-sertifikat" name="nomor_sertifikat" v-model="formPrestasi.nomor_sertifikat" class="form-control form-control-sm rounded-3" placeholder="No. Sertifikat jika ada" />
                                            </div>
                                        </div>

                                        <div class="mt-2" v-if="formPrestasi.juara === 'Lainnya'">
                                            <label for="input-prestasi-sertifikat-lainnya" class="form-label fw-bold fs-7 mb-1 text-dark">Nomor Sertifikat / Piagam</label>
                                            <input type="text" id="input-prestasi-sertifikat-lainnya" name="nomor_sertifikat" v-model="formPrestasi.nomor_sertifikat" class="form-control form-control-sm rounded-3" placeholder="No. Sertifikat jika ada" />
                                        </div>
                                    </div>
                                </div>

                                <!-- KOLOM KANAN: Detail Pelaksanaan & Berkas Unggahan -->
                                <div class="col-12 col-lg-6">
                                    <div class="p-0 bg-white border-0 h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <h6 class="fw-bold text-primary fs-7 mb-3 d-flex align-items-center gap-1.5">
                                                <i class="bi bi-geo-alt-fill"></i> 2. Detail Pelaksanaan &amp; Berkas
                                            </h6>

                                            <!-- Jenis Pelaksanaan & Tanggal -->
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label for="select-prestasi-jenis" class="form-label fw-bold fs-7 mb-1 text-dark">Jenis Pelaksanaan <span class="text-danger">*</span></label>
                                                    <select id="select-prestasi-jenis" name="jenis_lomba" v-model="formPrestasi.jenis_lomba" class="form-select form-select-sm rounded-3">
                                                        <option value="Offline">Offline (Luring)</option>
                                                        <option value="Online">Online (Daring)</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label for="input-prestasi-tanggal" class="form-label fw-bold fs-7 mb-1 text-dark">Tanggal Kegiatan <span class="text-danger">*</span></label>
                                                    <input type="date" id="input-prestasi-tanggal" name="tanggal_lomba" v-model="formPrestasi.tanggal_lomba" class="form-control form-control-sm rounded-3" />
                                                </div>
                                            </div>

                                            <!-- Tempat & Penyelenggara -->
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label for="input-prestasi-tempat" class="form-label fw-bold fs-7 mb-1 text-dark">Tempat / Kota <span class="text-danger">*</span></label>
                                                    <input type="text" id="input-prestasi-tempat" name="tempat_lomba" v-model="formPrestasi.tempat_lomba" class="form-control form-control-sm rounded-3" placeholder="Contoh: Jakarta" />
                                                </div>
                                                <div class="col-6">
                                                    <label for="input-prestasi-penyelenggara" class="form-label fw-bold fs-7 mb-1 text-dark">Penyelenggara <span class="text-danger">*</span></label>
                                                    <input type="text" id="input-prestasi-penyelenggara" name="penyelenggara" v-model="formPrestasi.penyelenggara" class="form-control form-control-sm rounded-3" placeholder="Contoh: Puspresnas / Kemendikbud" />
                                                </div>
                                            </div>

                                            <!-- Guru Pendamping & Poin Prestasi -->
                                            <div class="row g-2 mb-3">
                                                <div class="col-8">
                                                    <label for="input-prestasi-guru" class="form-label fw-bold fs-7 mb-1 text-dark">Guru Pendamping</label>
                                                    <input type="text" id="input-prestasi-guru" name="guru_pendamping" v-model="formPrestasi.guru_pendamping" class="form-control form-control-sm rounded-3" placeholder="Nama Guru Pendamping (Opsional)" />
                                                </div>
                                                <div class="col-4">
                                                    <label for="input-prestasi-poin" class="form-label fw-bold fs-7 mb-1 text-dark">Poin Prestasi <span class="text-danger">*</span></label>
                                                    <input type="number" id="input-prestasi-poin" name="poin_prestasi" v-model.number="formPrestasi.poin_prestasi" class="form-control form-control-sm rounded-3 fw-bold text-success" min="0" max="1000" placeholder="Poin" />
                                                </div>
                                            </div>

                                            <!-- Files Upload Grid 2x2 -->
                                            <div class="border-0 rounded-3 p-3 bg-light mb-0">
                                                <div class="fw-bold fs-8 text-dark mb-2 d-flex align-items-center justify-content-between">
                                                    <span><i class="bi bi-file-earmark-arrow-up me-1 text-primary"></i>Berkas Pendukung (Maks. 1 MB per file)</span>
                                                </div>
                                                
                                                <div class="row g-2">
                                                    <div class="col-12 col-sm-6">
                                                        <label for="input-file-bukti" class="form-label fs-9 fw-semibold mb-1 text-muted">1. Foto Bukti Sertifikat (.jpg/.png)</label>
                                                        <input type="file" id="input-file-bukti" name="foto_bukti_prestasi" class="form-control form-control-sm prestasi-file-input text-xs" accept="image/*" @change="handleFileUpload($event, 'foto_bukti_prestasi')" />
                                                        <div v-if="formPrestasi.existing_foto_bukti" class="fs-9 mt-1 text-success">
                                                            <i class="bi bi-check-circle-fill"></i> <a :href="getFileUrl(formPrestasi.existing_foto_bukti)" target="_blank" class="fw-bold text-success">Lihat File</a>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-sm-6">
                                                        <label for="input-file-siswa" class="form-label fs-9 fw-semibold mb-1 text-muted">2. Foto Penghargaan/Siswa (.jpg/.png)</label>
                                                        <input type="file" id="input-file-siswa" name="foto_siswa_prestasi" class="form-control form-control-sm prestasi-file-input text-xs" accept="image/*" @change="handleFileUpload($event, 'foto_siswa_prestasi')" />
                                                        <div v-if="formPrestasi.existing_foto_siswa" class="fs-9 mt-1 text-success">
                                                            <i class="bi bi-check-circle-fill"></i> <a :href="getFileUrl(formPrestasi.existing_foto_siswa)" target="_blank" class="fw-bold text-success">Lihat File</a>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-sm-6">
                                                        <label for="input-file-kegiatan" class="form-label fs-9 fw-semibold mb-1 text-muted">3. Dokumentasi Kegiatan (.jpg/.png)</label>
                                                        <input type="file" id="input-file-kegiatan" name="foto_kegiatan_lomba" class="form-control form-control-sm prestasi-file-input text-xs" accept="image/*" @change="handleFileUpload($event, 'foto_kegiatan_lomba')" />
                                                        <div v-if="formPrestasi.existing_foto_kegiatan" class="fs-9 mt-1 text-success">
                                                            <i class="bi bi-check-circle-fill"></i> <a :href="getFileUrl(formPrestasi.existing_foto_kegiatan)" target="_blank" class="fw-bold text-success">Lihat File</a>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-sm-6">
                                                        <label for="input-file-surat-tugas" class="form-label fs-9 fw-semibold mb-1 text-muted">4. Surat Tugas (.pdf/.jpg/.png)</label>
                                                        <input type="file" id="input-file-surat-tugas" name="surat_tugas_pdf" class="form-control form-control-sm prestasi-file-input text-xs" accept=".pdf,image/*" @change="handleFileUpload($event, 'surat_tugas_pdf')" />
                                                        <div v-if="formPrestasi.existing_surat_tugas" class="fs-9 mt-1 text-success">
                                                            <i class="bi bi-check-circle-fill"></i> <a :href="getFileUrl(formPrestasi.existing_surat_tugas)" target="_blank" class="fw-bold text-success">Lihat Berkas</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-top bg-light py-2.5 px-4 d-flex justify-content-end gap-2">
                        <button type="button" @click="closePrestasiModal" class="btn btn-sm btn-outline-secondary rounded-3 px-4 font-semibold fs-7" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" form="form-prestasi-modal" class="btn btn-sm btn-primary rounded-3 px-4 fw-bold fs-7 shadow-xs" :disabled="loadingPrestasi">
                            <span v-if="loadingPrestasi" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check2-circle me-1"></i> Simpan Prestasi Siswa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 6: KEHADIRAN SISWA SEMESTERAN
    ════════════════════════════════════════════════════════════ -->
    <?php if(empty($allowed_bk_tabs) || in_array('kehadiran', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'kehadiran'" class="animate-fade-in">
        <!-- Filter Card & Toolbar -->
        <div class="bk-card p-4 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-3 border-bottom">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-check-fill text-primary"></i>
                        Rekapitulasi & Input Kehadiran Siswa Semesteran
                    </h5>
                    <p class="text-muted fs-8 mb-0">Kelola angka akumulasi absensi (Sakit, Izin, Alfa) per kelas untuk keperluan rapor, pembinaan BK, dan evaluasi siswa.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Tombol Impor Excel (Popup Modal) -->
                    <button class="btn btn-sm btn-outline-success rounded-3 d-inline-flex align-items-center gap-1 fw-semibold fs-8 px-3 shadow-sm" @click="openModalImportKehadiran" :disabled="isKehadiranLocked">
                        <i class="bi bi-file-earmark-arrow-up-fill text-success"></i>
                        Impor Excel
                    </button>

                    <!-- Tombol Ekspor Excel (Popup Modal) -->
                    <button class="btn btn-sm btn-outline-primary rounded-3 d-inline-flex align-items-center gap-1 fw-semibold fs-8 px-3 shadow-sm" @click="openModalExportKehadiran">
                        <i class="bi bi-file-earmark-arrow-down-fill text-primary"></i>
                        Ekspor Excel
                    </button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="select-ta-kehadiran" class="form-label fw-semibold fs-8 mb-1">Tahun Ajaran</label>
                    <select id="select-ta-kehadiran" name="ta_kehadiran" class="form-select form-select-sm rounded-3" v-model="filterKehadiran.tahun_ajaran_id" @change="loadKehadiran">
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.id">
                            {{ ta.tahun_ajaran }} ({{ ta.status === 'Aktif' ? 'Aktif' : 'Non-Aktif' }})
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="select-sm-kehadiran" class="form-label fw-semibold fs-8 mb-1">Semester</label>
                    <select id="select-sm-kehadiran" name="sm_kehadiran" class="form-select form-select-sm rounded-3" v-model="filterKehadiran.semester" @change="loadKehadiran">
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="select-kls-kehadiran" class="form-label fw-semibold fs-8 mb-1">Kelas</label>
                    <select id="select-kls-kehadiran" name="kls_kehadiran" class="form-select form-select-sm rounded-3" v-model="filterKehadiran.kelas_id" @change="loadKehadiran">
                        <option value="">-- Pilih Kelas --</option>
                        <option v-for="k in listKelasKehadiran" :key="k.id" :value="k.id">
                            {{ k.nama_kelas }}
                        </option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-primary rounded-3 w-100 fw-semibold" @click="loadKehadiran" :disabled="loadingKehadiran" id="btn-muat-kehadiran">
                        <span v-if="loadingKehadiran" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-search"></i> Muat Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Workspace (Full Width Grid Table) -->
        <div class="row g-4" v-if="kehadiranData?.length > 0">
            <div class="col-12">
                <div class="bk-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-grid-3x3-gap-fill text-primary"></i>Input Absensi Kehadiran Siswa
                                <span v-if="isKehadiranLocked" class="badge bg-danger rounded-pill fs-8">
                                    <i class="bi bi-lock-fill me-1"></i>Terkunci (Read Only)
                                </span>
                                <span v-else class="badge bg-success rounded-pill fs-8">
                                    <i class="bi bi-unlock-fill me-1"></i>Bisa Diedit
                                </span>
                            </h6>
                            <small class="text-muted fs-8">Gunakan tombol arah keyboard &larr; &rarr; &uarr; &darr; atau Enter untuk berpindah sel.</small>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <!-- Toggle Kunci Button -->
                            <button v-if="isKehadiranLocked" class="btn btn-sm btn-outline-danger rounded-3 fw-semibold" @click="toggleLockKehadiran" id="btn-toggle-kunci">
                                <i class="bi bi-unlock-fill me-1"></i> Buka Kunci Data
                            </button>
                            <button v-else class="btn btn-sm btn-warning rounded-3 text-dark fw-semibold" @click="toggleLockKehadiran" id="btn-toggle-kunci">
                                <i class="bi bi-lock-fill me-1"></i> Kunci Data Kehadiran
                            </button>
                            <!-- Set Kosong Button -->
                            <button class="btn btn-sm btn-outline-secondary rounded-3 text-dark fw-semibold" @click="setAllEmptyToZero" :disabled="isKehadiranLocked" id="btn-set-nol">
                                Set Kosong &rarr; 0
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tbl-kehadiran-grid">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 40px;">No</th>
                                    <th>Siswa</th>
                                    <th>NIS / NISN</th>
                                    <th class="text-center" style="width: 100px;">Jenis Kelamin</th>
                                    <th>Kelas</th>
                                    <th>Tahun Ajaran</th>
                                    <th class="text-center">Semester</th>
                                    <th class="text-center" style="width: 100px;">Sakit (S)</th>
                                    <th class="text-center" style="width: 100px;">Izin (I)</th>
                                    <th class="text-center" style="width: 100px;">Alfa (A)</th>
                                    <th class="text-center" style="width: 150px;">Status Peringatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, idx) in kehadiranData" :key="s.siswa_id" :class="{'dirty-row': isRowDirty(s.siswa_id)}">
                                    <td class="text-center text-muted fw-semibold">{{ idx + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ s.nama_lengkap }}</div>
                                    </td>
                                    <td>
                                        <span class="text-dark font-monospace">{{ s.nis || '-' }}</span> / <span class="text-muted font-monospace">{{ s.nisn || '-' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="s.jenis_kelamin === 'L' || s.jenis_kelamin === 'Laki-Laki' || s.jenis_kelamin === 'Laki-laki'" 
                                              class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 text-xs fw-semibold">
                                            <i class="bi bi-gender-male me-1"></i>L (Laki-laki)
                                        </span>
                                        <span v-else-if="s.jenis_kelamin === 'P' || s.jenis_kelamin === 'Perempuan' || s.jenis_kelamin === 'perempuan'" 
                                              class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 text-xs fw-semibold">
                                            <i class="bi bi-gender-female me-1"></i>P (Perempuan)
                                        </span>
                                        <span v-else class="badge bg-light text-secondary rounded-pill px-2 py-1 text-xs">
                                            {{ s.jenis_kelamin || '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary border rounded-3 fw-semibold">{{ s.nama_kelas || getNamaKelas(filterKehadiran.kelas_id) }}</span>
                                        <span v-if="isRowDirty(s.siswa_id)" class="badge bg-primary text-white ms-1" style="font-size:0.65rem;">Belum Disimpan</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-3 fw-semibold">{{ s.tahun_ajaran || getNamaTahunAjaran(filterKehadiran.tahun_ajaran_id) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-3 fw-semibold">{{ s.semester || filterKehadiran.semester }}</span>
                                    </td>
                                    <!-- Sakit input -->
                                    <td class="text-center" :class="{'dirty-cell': isCellDirty(s.siswa_id, 'sakit')}">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:22px;height:22px;line-height:0.5;" :disabled="isKehadiranLocked" @click="decrementAbsen(s.siswa_id, 'sakit')">-</button>
                                            <label :for="'sakit-' + s.siswa_id" class="visually-hidden">Sakit {{ s.nama_lengkap }}</label>
                                            <input type="number" :id="'sakit-' + s.siswa_id" :name="'sakit_' + s.siswa_id" class="form-control form-control-sm grid-input-number rounded-2 p-1 text-center font-monospace" 
                                                   v-model.number="s.sakit" min="0" :disabled="isKehadiranLocked" @keydown="handleGridKeydown($event, idx, 'sakit')">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:22px;height:22px;line-height:0.5;" :disabled="isKehadiranLocked" @click="incrementAbsen(s.siswa_id, 'sakit')">+</button>
                                        </div>
                                    </td>
                                    <!-- Izin input -->
                                    <td class="text-center" :class="{'dirty-cell': isCellDirty(s.siswa_id, 'izin')}">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:22px;height:22px;line-height:0.5;" :disabled="isKehadiranLocked" @click="decrementAbsen(s.siswa_id, 'izin')">-</button>
                                            <label :for="'izin-' + s.siswa_id" class="visually-hidden">Izin {{ s.nama_lengkap }}</label>
                                            <input type="number" :id="'izin-' + s.siswa_id" :name="'izin_' + s.siswa_id" class="form-control form-control-sm grid-input-number rounded-2 p-1 text-center font-monospace" 
                                                   v-model.number="s.izin" min="0" :disabled="isKehadiranLocked" @keydown="handleGridKeydown($event, idx, 'izin')">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:22px;height:22px;line-height:0.5;" :disabled="isKehadiranLocked" @click="incrementAbsen(s.siswa_id, 'izin')">+</button>
                                        </div>
                                    </td>
                                    <!-- Alfa input -->
                                    <td class="text-center" :class="{'dirty-cell': isCellDirty(s.siswa_id, 'alfa')}" 
                                        :class="s.alfa > 5 ? 'cell-warning' : ((s.sakit + s.izin + s.alfa) > 7 ? 'cell-caution' : '')">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:22px;height:22px;line-height:0.5;" :disabled="isKehadiranLocked" @click="decrementAbsen(s.siswa_id, 'alfa')">-</button>
                                            <label :for="'alfa-' + s.siswa_id" class="visually-hidden">Alfa {{ s.nama_lengkap }}</label>
                                            <input type="number" :id="'alfa-' + s.siswa_id" :name="'alfa_' + s.siswa_id" class="form-control form-control-sm grid-input-number rounded-2 p-1 text-center font-monospace" 
                                                   v-model.number="s.alfa" min="0" :disabled="isKehadiranLocked" @keydown="handleGridKeydown($event, idx, 'alfa')">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:22px;height:22px;line-height:0.5;" :disabled="isKehadiranLocked" @click="incrementAbsen(s.siswa_id, 'alfa')">+</button>
                                        </div>
                                    </td>
                                    <!-- Status -->
                                    <td class="text-center">
                                        <span v-if="s.alfa > 5" class="badge bg-danger rounded-3 py-1.5 px-2">Bahaya (BK Alert)</span>
                                        <span v-else-if="(s.sakit + s.izin + s.alfa) > 7" class="badge bg-warning text-dark rounded-3 py-1.5 px-2">Perlu Binaan</span>
                                        <span v-else class="badge bg-success rounded-3 py-1.5 px-2">Aman</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm" @click="saveKehadiran" :disabled="savingKehadiran || isKehadiranLocked" id="btn-simpan-kehadiran">
                            <span v-if="savingKehadiran" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-floppy-fill me-1"></i> Simpan Absensi Kelas
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loader State -->
        <div v-else-if="loadingKehadiran" class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
            <div class="spinner-border text-primary mb-2"></div>
            <span class="fs-7 fw-semibold">Memuat data absensi kehadiran siswa...</span>
        </div>

        <!-- Empty state with specific filter information -->
        <div v-else-if="filterKehadiran.tahun_ajaran_id && filterKehadiran.semester && filterKehadiran.kelas_id && (!kehadiranData || kehadiranData.length === 0)" class="animate-fade-in">
            <div class="bk-card p-5 text-center my-3 border border-dashed rounded-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-person-x-fill fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Tidak Ada Data Siswa Ditemukan</h5>
                <p class="text-muted fs-8 mb-3" style="max-width: 540px; margin: 0 auto; line-height: 1.6;">
                    Tidak ditemukan data siswa aktif untuk kelas <strong class="text-dark">{{ getNamaKelas(filterKehadiran.kelas_id) }}</strong> pada Tahun Ajaran <strong class="text-dark">{{ getNamaTahunAjaran(filterKehadiran.tahun_ajaran_id) }}</strong> Semester <strong class="text-dark">{{ filterKehadiran.semester }}</strong>.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                    <button class="btn btn-sm btn-outline-primary rounded-3 px-3 fs-8" @click="loadKehadiran">
                        <i class="bi bi-arrow-clockwise me-1"></i> Muat Ulang
                    </button>
                    <button class="btn btn-sm btn-outline-success rounded-3 px-3 fs-8" @click="openModalImportKehadiran" :disabled="isKehadiranLocked">
                        <i class="bi bi-file-earmark-arrow-up-fill me-1"></i> Impor Data Excel Kelas Ini
                    </button>
                </div>
            </div>
        </div>

        <!-- Initial unselected state -->
        <div v-else class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-muted opacity-50"></i>
            <h6 class="fw-bold text-dark mb-1">Belum Ada Data Yang Ditampilkan</h6>
            <p class="fs-8 text-muted mb-0">Silakan pilih Tahun Ajaran, Semester, dan Kelas di atas untuk menampilkan grid absensi.</p>
        </div>


    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 7: TATA TERTIB & POIN PELANGGARAN
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && (in_array('p_dashboard', $allowed_bk_tabs) || in_array('pelanggaran', $allowed_bk_tabs))): ?>
<div v-show="activeTab === 'pelanggaran'" class="animate-fade-in">
        <?php if ($activeGroup !== 'kedisiplinan'): ?>
        <!-- Sub navigation segment control -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 border-bottom pb-2">
            <div class="sub-tabs-segment">
                <button id="sub-tab-dashboard-btn" name="sub_tab_dashboard_btn" class="sub-tab-btn-modern" 
                        :class="{ active: activeSubTab === 'p_dashboard' }"
                        @click="switchSubTab('p_dashboard')">
                    <i class="bi bi-speedometer2"></i> Dashboard & Tren
                </button>
                <button id="sub-tab-input-btn" name="sub_tab_input_btn" class="sub-tab-btn-modern" 
                        :class="{ active: activeSubTab === 'p_input' }"
                        @click="switchSubTab('p_input')">
                    <i class="bi bi-plus-circle"></i> Catat Pelanggaran
                </button>
                <button id="sub-tab-sanksi-btn" name="sub_tab_sanksi_btn" class="sub-tab-btn-modern" 
                        :class="{ active: activeSubTab === 'p_sanksi' }"
                        @click="switchSubTab('p_sanksi')">
                    <i class="bi bi-journal-bookmark"></i> Buku Catatan Sanksi
                </button>
                <button id="sub-tab-master-btn" name="sub_tab_master_btn" class="sub-tab-btn-modern" 
                        :class="{ active: activeSubTab === 'p_master' }"
                        @click="switchSubTab('p_master')">
                    <i class="bi bi-gear-fill"></i> Master Kategori & Poin
                </button>
            </div>
            <div class="text-muted fs-8">
                <i class="bi bi-info-circle me-1"></i> Akumulasi poin dihitung berdasarkan tahun ajaran berjalan.
            </div>
        </div>
        <?php endif; ?>

        <!-- SUB-TAB 1: DASHBOARD & TREN -->
        <div v-show="activeSubTab === 'p_dashboard'" class="animate-fade-in">
            <div v-if="loadingPelanggaranDashboard" class="text-center py-5">
                <div class="spinner-border text-danger"></div>
                <p class="text-muted mt-2 fs-7">Memuat analisis pelanggaran...</p>
            </div>
            <div v-else>
                <!-- KPI Cards Row -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4 col-lg-2.4 col-xl">
                        <div class="kpi-card h-100 border-start border-4 border-violet">
                            <p class="text-muted fs-9 fw-semibold text-uppercase mb-1">Total Melanggar</p>
                            <div class="kpi-value text-dark fs-3">{{ pelanggaranKpi.total_siswa_melanggar }}</div>
                            <small class="text-muted fs-9">Siswa berpoin aktif</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2.4 col-xl">
                        <div class="kpi-card h-100 border-start border-4 border-info">
                            <p class="text-muted fs-9 fw-semibold text-uppercase mb-1">Peringatan Wali Kelas</p>
                            <div class="kpi-value text-info fs-3">{{ pelanggaranKpi.wali_kelas }}</div>
                            <small class="text-muted fs-9">Rentang 25 - 49 Poin</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2.4 col-xl">
                        <div class="kpi-card h-100 border-start border-4 border-warning">
                            <p class="text-muted fs-9 fw-semibold text-uppercase mb-1">SP 1 / BK</p>
                            <div class="kpi-value text-warning fs-3">{{ pelanggaranKpi.sp1_bk }}</div>
                            <small class="text-muted fs-9">Rentang 50 - 74 Poin</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2.4 col-xl">
                        <div class="kpi-card h-100 border-start border-4 text-orange" style="border-left-color: #f97316 !important;">
                            <p class="text-muted fs-9 fw-semibold text-uppercase mb-1">SP 2 / Skorsing</p>
                            <div class="kpi-value fs-3" style="color: #f97316;">{{ pelanggaranKpi.sp2_skorsing }}</div>
                            <small class="text-muted fs-9">Rentang 75 - 99 Poin</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2.4 col-xl">
                        <div class="kpi-card h-100 border-start border-4 border-danger">
                            <p class="text-muted fs-9 fw-semibold text-uppercase mb-1">SP 3 / Evaluasi DO</p>
                            <div class="kpi-value text-danger fs-3">{{ pelanggaranKpi.sp3_do }}</div>
                            <small class="text-muted fs-9">Akumulasi >= 100 Poin</small>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Top Violators List (Left) -->
                    <div class="col-lg-6">
                        <div class="bk-card p-4 h-100">
                            <h6 class="fw-bold mb-3 text-dark">
                                <i class="bi bi-trophy-fill me-2 text-danger"></i>Akumulasi Poin Tertinggi Minggu Ini
                            </h6>
                            <div v-if="pelanggaranTopStudents?.length > 0" class="table-responsive">
                                <table class="table table-hover align-middle fs-8">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Siswa</th>
                                            <th>Kelas</th>
                                            <th class="text-center">Total Poin</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="s in pelanggaranTopStudents" :key="s.siswa_id">
                                            <td>
                                                <div class="fw-bold text-dark">{{ s.nama_lengkap }}</div>
                                                <div class="text-muted" style="font-size:0.7rem;">NISN: {{ s.nisn }}</div>
                                            </td>
                                            <td>{{ s.nama_kelas || '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge px-2 py-1.5 rounded-3 fw-semibold"
                                                      :class="{
                                                          'bg-danger': s.total_poin >= 100,
                                                          'bg-warning text-dark': s.total_poin >= 75 && s.total_poin < 100,
                                                          'bg-info text-dark': s.total_poin >= 50 && s.total_poin < 75,
                                                          'bg-secondary': s.total_poin >= 25 && s.total_poin < 50,
                                                          'bg-success': s.total_poin < 25
                                                      }">
                                                    {{ s.total_poin }} Poin
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-xs btn-outline-primary rounded-pill px-2"
                                                        @click="openSanksiDetail(s.siswa_id)">
                                                    Pembinaan
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-5 text-muted">
                                <i class="bi bi-emoji-smile fs-2 mb-2 d-block text-success"></i>
                                Tidak ada data pelanggaran aktif. Pertahankan lingkungan sekolah yang aman!
                            </div>
                        </div>
                    </div>

                    <!-- Trend Chart (Right) -->
                    <div class="col-lg-6">
                        <div class="bk-card p-4 h-100">
                            <h6 class="fw-bold mb-3 text-dark">
                                <i class="bi bi-graph-up-arrow me-2 text-danger"></i>Tren Kejadian Pelanggaran Bulanan
                            </h6>
                            <div class="position-relative" style="height: 280px; width: 100%;">
                                <canvas id="pelanggaranTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Riwayat Laporan Hari Ini & Terkini (Dashboard View) -->
                <div class="mt-4">
                    <div class="bk-card p-4 rounded-4 shadow-sm border bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-clock-history me-2 text-danger"></i>Riwayat Laporan Hari Ini &amp; Terkini
                                </h6>
                                <small class="text-muted fs-8">{{ filteredCatatanPelanggaran?.length || 0 }} kejadian terdaftar</small>
                            </div>
                            <div class="d-flex gap-2">
                                <label for="catatan-dashboard-search" class="visually-hidden">Cari riwayat laporan</label>
                                <input type="text" id="catatan-dashboard-search" name="catatan_dashboard_search" class="form-control form-control-sm rounded-3" style="width: 220px;" 
                                       v-model="catatanListSearch" placeholder="Cari nama, kelas...">
                                <button class="btn btn-sm btn-outline-secondary rounded-3" @click="loadPelanggaranCatatan" :disabled="loadingPelanggaranCatatan" id="btn-refresh-catatan-dash">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>

                        <div v-if="loadingPelanggaranCatatan" class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                            <span class="ms-2 text-muted fs-8">Memuat riwayat laporan...</span>
                        </div>
                        <div v-else-if="filteredCatatanPelanggaran?.length > 0" class="table-responsive" style="max-height:380px; overflow-y:auto;">
                            <table class="table table-hover align-middle fs-8" id="tbl-riwayat-laporan-dash">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama Siswa</th>
                                        <th>NISN</th>
                                        <th>Kelas</th>
                                        <th>Kejadian Pelanggaran</th>
                                        <th class="text-center">Bukti</th>
                                        <th class="text-end" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in filteredCatatanPelanggaran" :key="'dash_' + c.id">
                                        <td class="text-muted text-nowrap">{{ formatTanggalIndo(c.tanggal_kejadian) }}</td>
                                        <td class="fw-bold text-dark">{{ c.nama_siswa }}</td>
                                        <td class="text-muted">{{ c.nisn || '-' }}</td>
                                        <td>
                                            <span class="badge bg-light text-primary border rounded-3">{{ c.nama_kelas || '-' }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                <span class="badge me-1" 
                                                      :class="getKategoriBadge(c.kategori)">{{ c.kategori }}</span>
                                                {{ c.nama_pelanggaran }}
                                            </div>
                                            <div class="text-muted fs-9 mt-1" v-if="c.catatan_keterangan">
                                                {{ c.catatan_keterangan }}
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge rounded-pill bg-light text-danger border" style="font-size: 0.65rem;">
                                                    +{{ c.bobot_poin }} Poin
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <a v-if="c.foto_bukti" @click.prevent="showFotoModal(secureFileUrl(c.foto_bukti))" href="#" 
                                               class="btn btn-xs btn-outline-info p-1 rounded-2" title="Foto Bukti">
                                                <i class="bi bi-image"></i>
                                            </a>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-xs btn-outline-primary me-1 rounded-2 py-0 px-2 fw-semibold" style="font-size:0.7rem; line-height:1.5;" @click="switchSubTab('p_input'); editPelanggaran(c)" title="Edit Laporan">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-xs btn-outline-danger rounded-2 py-0 px-2 fw-semibold" style="font-size:0.7rem; line-height:1.5;" @click="deletePelanggaran(c.id)" title="Hapus Laporan">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-4 text-muted">
                            <i class="bi bi-journal-x fs-3 mb-2 d-block"></i>
                            Belum ada riwayat laporan hari ini / terkini.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SUB-TAB 2: CATAT PELANGGARAN (TRANSAKSI) -->
        <div v-show="activeSubTab === 'p_input'" class="animate-fade-in">
            <div class="bk-card p-4 rounded-4 shadow-sm border bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-shield-exclamation me-2 text-danger"></i>Catatan Pelanggaran Siswa
                        </h6>
                        <small class="text-muted fs-8">{{ filteredCatatanPelanggaran?.length || 0 }} kejadian terdaftar</small>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <label for="catatan-list-search" class="visually-hidden">Cari riwayat laporan</label>
                        <input type="text" id="catatan-list-search" name="catatan_list_search" class="form-control form-control-sm rounded-3" style="width: 220px;" 
                               v-model="catatanListSearch" placeholder="Cari nama, kelas...">
                        <button class="btn btn-sm btn-outline-secondary rounded-3" @click="loadPelanggaranCatatan" :disabled="loadingPelanggaranCatatan" id="btn-refresh-catatan" title="Refresh Data">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button class="btn btn-sm btn-primary rounded-3 text-white fw-bold shadow-sm d-flex align-items-center gap-1 ms-1" @click="openPelanggaranModal" id="btn-open-modal-pelanggaran">
                            <i class="bi bi-plus-circle-fill"></i> Catat Pelanggaran Baru
                        </button>
                    </div>
                </div>

                <div v-if="loadingPelanggaranCatatan" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
                <div v-else-if="filteredCatatanPelanggaran?.length > 0" class="table-responsive" style="max-height:540px; overflow-y:auto;">
                    <table class="table table-hover align-middle fs-8" id="tbl-riwayat-laporan">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama Siswa</th>
                                <th>NISN</th>
                                <th>Kelas</th>
                                <th>Kejadian Pelanggaran</th>
                                <th class="text-center">Bukti</th>
                                <th class="text-end" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in paginatedCatatanPelanggaran" :key="c.id">
                                <td class="text-muted text-nowrap">{{ formatTanggalIndo(c.tanggal_kejadian) }}</td>
                                <td class="fw-bold text-dark">{{ c.nama_siswa }}</td>
                                <td class="text-muted">{{ c.nisn || '-' }}</td>
                                <td>
                                    <span class="badge bg-light text-primary border rounded-3">{{ c.nama_kelas || '-' }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <span class="badge me-1" 
                                              :class="getKategoriBadge(c.kategori)">{{ c.kategori }}</span>
                                        {{ c.nama_pelanggaran }}
                                    </div>
                                    <div class="text-muted fs-9 mt-1" v-if="c.catatan_keterangan">
                                        {{ c.catatan_keterangan }}
                                    </div>
                                    <div class="mt-1">
                                        <span class="badge rounded-pill bg-light text-danger border" style="font-size: 0.65rem;">
                                            +{{ c.bobot_poin }} Poin
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a v-if="c.foto_bukti" @click.prevent="showFotoModal(secureFileUrl(c.foto_bukti))" href="#" 
                                       class="btn btn-xs btn-outline-info p-1 rounded-2" title="Foto Bukti">
                                        <i class="bi bi-image"></i>
                                    </a>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-xs btn-outline-primary me-1 rounded-2 py-0 px-2 fw-semibold" style="font-size:0.7rem; line-height:1.5;" @click="editPelanggaran(c)" title="Edit Laporan">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-danger rounded-2 py-0 px-2 fw-semibold" style="font-size:0.7rem; line-height:1.5;" @click="deletePelanggaran(c.id)" title="Hapus Laporan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls: Catatan Pelanggaran -->
                <div v-if="filteredCatatanPelanggaran?.length > 0" class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                    <div class="text-muted fs-8">
                        Menampilkan <strong>{{ (currentPagePelanggaran - 1) * perPagePelanggaran + 1 }}</strong> - <strong>{{ Math.min(currentPagePelanggaran * perPagePelanggaran, filteredCatatanPelanggaran.length) }}</strong> dari <strong>{{ filteredCatatanPelanggaran.length }}</strong> kejadian
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-xs btn-outline-secondary rounded-2 px-2" :disabled="currentPagePelanggaran === 1" @click="currentPagePelanggaran--">
                            <i class="bi bi-chevron-left me-1"></i>Sebelumnya
                        </button>
                        <template v-for="p in totalPelanggaranPages" :key="'pelanggaran_p_' + p">
                            <button v-if="p === 1 || p === totalPelanggaranPages || (p >= currentPagePelanggaran - 1 && p <= currentPagePelanggaran + 1)"
                                    class="btn btn-xs rounded-2 px-2.5 fw-semibold"
                                    :class="p === currentPagePelanggaran ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary text-dark'"
                                    @click="currentPagePelanggaran = p">
                                {{ p }}
                            </button>
                            <span v-else-if="(p === 2 && currentPagePelanggaran > 3) || (p === totalPelanggaranPages - 1 && currentPagePelanggaran < totalPelanggaranPages - 2)" class="px-1 text-muted fs-8">...</span>
                        </template>
                        <button class="btn btn-xs btn-outline-secondary rounded-2 px-2" :disabled="currentPagePelanggaran === totalPelanggaranPages" @click="currentPagePelanggaran++">
                            Selanjutnya<i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
                <div v-else class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-2 mb-2 d-block"></i>
                    Belum ada data pelanggaran siswa. Klik tombol <strong>+ Catat Pelanggaran Baru</strong> di atas untuk membuat catatan.
                </div>
            </div>
        </div>

        <!-- Modal Popup Form Input/Edit Catatan Pelanggaran Siswa -->
        <div class="modal fade" id="modalFormCatatPelanggaran" tabindex="-1" aria-labelledby="modalFormCatatPelanggaranLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-bottom py-3 bg-light">
                        <h6 class="modal-title fw-bold text-dark d-flex align-items-center" id="modalFormCatatPelanggaranLabel">
                            <i class="bi bi-shield-exclamation me-2 text-danger fs-5"></i>
                            {{ formInputPelanggaran.id ? 'Edit Catatan Pelanggaran' : 'Catat Pelanggaran Siswa Baru' }}
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closePelanggaranModal"></button>
                    </div>
                    <div class="modal-body p-4" style="min-height: 360px;">
                        <form @submit.prevent="submitPelanggaran" id="form-pelanggaran-modal" enctype="multipart/form-data">
                            <!-- Siswa Selection -->
                            <div class="mb-3">
                                <label for="pelanggaran-siswa-search" class="form-label fw-bold fs-7 mb-1 text-dark">
                                    Pilih Siswa <span class="text-danger">*</span>
                                </label>
                                
                                <!-- Jika Mode Edit: Tampilkan Info Siswa Terkunci -->
                                <div v-if="formInputPelanggaran.id" class="p-2 px-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="fw-bold text-dark me-2 fs-7">{{ selectedPelanggaranSiswa.nama_lengkap || 'Siswa' }}</span>
                                        <span class="badge bg-primary rounded-3 me-1 fs-9">{{ selectedPelanggaranSiswa.nama_kelas || '-' }}</span>
                                        <small class="text-muted fs-9">NISN: {{ selectedPelanggaranSiswa.nisn || '-' }}</small>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill px-2 py-1 fs-9 d-flex align-items-center gap-1">
                                        <i class="bi bi-lock-fill"></i> Terkunci
                                    </span>
                                </div>

                                <!-- Jika Mode Tambah: Input Search Autocomplete -->
                                <div v-else class="position-relative">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                        <input type="text" 
                                               id="pelanggaran-siswa-search"
                                               name="pelanggaran_siswa_search"
                                               class="form-control form-control-sm border-start-0 ps-1 rounded-end-3" 
                                               placeholder="Ketik Nama, NISN, atau NIK Siswa..."
                                               v-model="pelanggaranSearchSiswa"
                                               @input="searchSiswaPelanggaranDebounce"
                                               @focus="showPelanggaranSiswaDropdown = true"
                                               @blur="hidePelanggaranDropdownDelay"
                                               autocomplete="off" />
                                    </div>
                                    
                                    <!-- Dropdown Autocomplete Pencarian Siswa -->
                                    <div v-if="showPelanggaranSiswaDropdown && pelanggaranSiswaOptions?.length > 0" 
                                         class="position-absolute w-100 bg-white border rounded-3 shadow-lg p-1 mt-1 z-3"
                                         style="max-height: 220px; overflow-y: auto;">
                                        <div v-for="s in pelanggaranSiswaOptions" 
                                             :key="s.id" 
                                             @mousedown.prevent="selectSiswaPelanggaran(s)"
                                             class="p-2 rounded-2 hover-bg-slate cursor-pointer fs-7 d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold text-dark">{{ s.nama_lengkap }}</div>
                                                <div class="text-muted fs-8">NISN: {{ s.nisn || '-' }} | Kelas: {{ s.nama_kelas || '-' }}</div>
                                            </div>
                                            <i class="bi bi-plus-circle-fill text-primary fs-6"></i>
                                        </div>
                                    </div>
                                    <div v-else-if="showPelanggaranSiswaDropdown && loadingSearchPelanggaranSiswa" 
                                         class="position-absolute w-100 bg-white border rounded-3 shadow-lg p-3 text-center mt-1 z-3">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                        <span class="fs-7 text-muted ms-2">Mencari...</span>
                                    </div>
                                </div>

                                <!-- Siswa Terpilih Badge -->
                                <div v-if="selectedPelanggaranSiswa?.id" class="mt-2 d-flex flex-wrap gap-2">
                                    <div class="badge d-inline-flex align-items-center gap-2 p-2 rounded-3 text-dark" 
                                         style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">
                                        <span class="fw-semibold">{{ selectedPelanggaranSiswa.nama_lengkap }} ({{ selectedPelanggaranSiswa.nama_kelas || '-' }})</span>
                                        <button v-if="!formInputPelanggaran.id" type="button" class="btn-close" style="font-size: 0.6rem; margin-left: 5px;" @click="clearSiswaPelanggaran" title="Hapus / Ganti Siswa"></button>
                                        <i v-else class="bi bi-lock-fill text-muted ms-1" style="font-size:0.75rem;" title="Siswa terkunci saat edit data"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Form fields row: Rule & Date -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <label for="input-pelanggaran-id" class="form-label fw-bold fs-7 mb-1 text-dark">Aturan Pelanggaran & Poin <span class="text-danger">*</span></label>
                                    <select id="input-pelanggaran-id" name="pelanggaran_id" class="form-select form-select-sm rounded-3" v-model="formInputPelanggaran.pelanggaran_id">
                                        <option value="">-- Pilih Aturan Pelanggaran --</option>
                                        <option v-for="rule in pelanggaranMasterList" :key="rule.id" :value="rule.id">
                                            [{{ rule.kategori }}] {{ rule.nama_pelanggaran }} (+{{ rule.bobot_poin }} Poin)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="input-tanggal-kejadian" class="form-label fw-bold fs-7 mb-1 text-dark">Tanggal Kejadian <span class="text-danger">*</span></label>
                                    <input type="date" id="input-tanggal-kejadian" name="tanggal_kejadian" class="form-control form-control-sm rounded-3" v-model="formInputPelanggaran.tanggal_kejadian">
                                </div>
                            </div>

                            <!-- Kronologi -->
                            <div class="mb-3">
                                <label for="input-catatan-keterangan" class="form-label fw-bold fs-7 mb-1 text-dark">Kronologi / Catatan Kejadian</label>
                                <textarea id="input-catatan-keterangan" name="catatan_keterangan" class="form-control form-control-sm rounded-3 text-area-vertical" rows="3" 
                                          v-model="formInputPelanggaran.catatan_keterangan"
                                          placeholder="Tuliskan detail kronologi atau catatan penting kejadian..."></textarea>
                            </div>

                            <!-- Upload Foto Bukti -->
                            <div class="mb-2">
                                <label for="input-foto-bukti-file" class="form-label fw-bold fs-7 mb-1 text-dark">
                                    <i class="bi bi-camera-fill text-primary me-1"></i>Foto Bukti
                                    <span class="badge bg-info text-dark fw-normal ms-1 fs-9">Kamera HP / File &bull; Otomatis dikompres &lt;1MB</span>
                                </label>
                                <input type="file" id="input-foto-bukti-file" name="foto_bukti" class="form-control form-control-sm rounded-3" 
                                       accept="image/*" capture="environment" @change="handleFotoUpload">
                                <div v-if="formInputPelanggaran.existing_foto" class="mt-2">
                                    <small class="text-muted d-block">Foto Sebelumnya:</small>
                                    <a @click.prevent="showFotoModal(secureFileUrl(formInputPelanggaran.existing_foto))" href="#" class="fs-8 text-primary fw-bold text-decoration-underline cursor-pointer">
                                        <i class="bi bi-image me-1"></i>Lihat Foto Bukti Saat Ini
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-top bg-light py-2 px-4">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal" @click="closePelanggaranModal">
                            Batal
                        </button>
                        <button type="button" class="btn btn-sm btn-primary text-white fw-bold rounded-3 px-4" @click="submitPelanggaran" :disabled="submittingPelanggaran">
                            <i class="bi bi-floppy me-1"></i>
                            {{ formInputPelanggaran.id ? 'Perbarui Laporan' : 'Simpan Pelanggaran' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SUB-TAB 3: BUKU CATATAN SANKSI -->
        <div v-show="activeSubTab === 'p_sanksi'" class="animate-fade-in">
            <div class="bk-card p-4">
                <!-- Search & Filters -->
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-5">
                        <label for="input-sanksi-search" class="form-label fw-semibold fs-8 mb-1">Cari Siswa</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="input-sanksi-search" name="sanksi_search" class="form-control rounded-end-3 fs-7 border-start-0 shadow-none" 
                                   v-model="sanksiSearch" placeholder="Cari nama siswa, kelas, NISN...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="select-sanksi-status" class="form-label fw-semibold fs-8 mb-1">Filter Status Sanksi</label>
                        <select id="select-sanksi-status" name="sanksi_status" class="form-select form-select-sm rounded-3 fs-7" v-model="sanksiStatus">
                            <option value="">-- Semua Status Sanksi --</option>
                            <option value="Aman">Aman (Poin &lt; 25)</option>
                            <option value="Wali Kelas">Peringatan Wali Kelas (Poin 25 - 49)</option>
                            <option value="SP 1">SP 1 / Panggilan Orang Tua (Poin 50 - 74)</option>
                            <option value="SP 2">SP 2 / Skorsing (Poin 75 - 99)</option>
                            <option value="SP 3">SP 3 / Rekomendasi DO (Poin &gt;= 100)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-sm btn-outline-secondary w-100 rounded-3 fs-7 fw-semibold" @click="loadPelanggaranSanksi" :disabled="loadingPelanggaranSanksi" id="btn-refresh-sanksi">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Data
                        </button>
                    </div>
                </div>

                <div v-if="loadingPelanggaranSanksi" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
                <div v-else-if="filteredSanksiBuku?.length > 0" class="table-responsive">
                    <table class="table table-hover align-middle fs-8" id="tbl-buku-sanksi">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap" style="min-width: 150px;">Siswa</th>
                                <th class="text-nowrap" style="min-width: 100px;">NISN</th>
                                <th class="text-nowrap" style="min-width: 80px;">Kelas</th>
                                <th class="text-nowrap" style="min-width: 200px;">Distribusi Akumulasi Poin</th>
                                <th class="text-nowrap" style="min-width: 140px;">Status Peringatan</th>
                                <th class="text-nowrap" style="min-width: 200px;">Kebijakan Penanganan</th>
                                <th class="text-center text-nowrap" style="min-width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in paginatedSanksiBuku" :key="item.siswa_id">
                                <td><div class="fw-bold text-dark text-nowrap">{{ item.nama_lengkap }}</div></td>
                                <td class="text-muted text-nowrap">{{ item.nisn || '-' }}</td>
                                <td>
                                    <span class="badge bg-light text-primary border rounded-3 text-nowrap">{{ item.nama_kelas || '-' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px; border-radius: 99px; overflow: hidden; background-color: #f1f5f9;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill" role="progressbar" 
                                                 :style="{
                                                     width: Math.min(100, item.total_poin) + '%',
                                                     backgroundColor: item.total_poin >= 100 ? '#ef4444' : 
                                                                      item.total_poin >= 75 ? '#f97316' : 
                                                                      item.total_poin >= 50 ? '#f59e0b' : 
                                                                      item.total_poin >= 25 ? '#2563eb' : '#10b981'
                                                 }"
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <span class="fw-bold text-dark fs-8">{{ item.total_poin }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="{
                                              'bg-danger': item.total_poin >= 100,
                                              'bg-warning text-dark': item.total_poin >= 75 && item.total_poin < 100,
                                              'bg-info text-dark': item.total_poin >= 50 && item.total_poin < 75,
                                              'bg-secondary': item.total_poin >= 25 && item.total_poin < 50,
                                              'bg-success': item.total_poin < 25
                                          }">
                                        {{ item.status_label }}
                                    </span>
                                </td>
                                <td><small class="text-muted">{{ item.sanksi_detail }}</small></td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-primary rounded-pill px-3 py-1.5 fw-semibold text-white d-inline-flex align-items-center gap-1"
                                            @click="openSanksiDetail(item.siswa_id)">
                                        <i class="bi bi-shield-shaded"></i> Detail & Pembinaan
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls: Buku Catatan Sanksi -->
                <div v-if="filteredSanksiBuku?.length > 0" class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                    <div class="text-muted fs-8">
                        Menampilkan <strong>{{ (currentPageSanksi - 1) * perPageSanksi + 1 }}</strong> - <strong>{{ Math.min(currentPageSanksi * perPageSanksi, filteredSanksiBuku.length) }}</strong> dari <strong>{{ filteredSanksiBuku.length }}</strong> siswa
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-xs btn-outline-secondary rounded-2 px-2" :disabled="currentPageSanksi === 1" @click="currentPageSanksi--">
                            <i class="bi bi-chevron-left me-1"></i>Sebelumnya
                        </button>
                        <template v-for="p in totalSanksiPages" :key="'sanksi_p_' + p">
                            <button v-if="p === 1 || p === totalSanksiPages || (p >= currentPageSanksi - 1 && p <= currentPageSanksi + 1)"
                                    class="btn btn-xs rounded-2 px-2.5 fw-semibold"
                                    :class="p === currentPageSanksi ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary text-dark'"
                                    @click="currentPageSanksi = p">
                                {{ p }}
                            </button>
                            <span v-else-if="(p === 2 && currentPageSanksi > 3) || (p === totalSanksiPages - 1 && currentPageSanksi < totalSanksiPages - 2)" class="px-1 text-muted fs-8">...</span>
                        </template>
                        <button class="btn btn-xs btn-outline-secondary rounded-2 px-2" :disabled="currentPageSanksi === totalSanksiPages" @click="currentPageSanksi++">
                            Selanjutnya<i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
                <div v-else class="text-center py-5 text-muted">
                    <i class="bi bi-people-fill fs-2 mb-2 d-block"></i>
                    Tidak ada siswa berpoin yang memenuhi kriteria pencarian Anda.
                </div>
            </div>
        </div>

        <!-- SUB-TAB 4: MASTER DATA KATEGORI & POIN -->
        <div v-show="activeSubTab === 'p_master'" class="animate-fade-in">
            <div class="row g-4">
                <!-- Form Input/Edit Aturan (Kiri) -->
                <div class="col-lg-4">
                    <div class="bk-form-card p-4 rounded-4 shadow-sm border bg-white">
                        <h6 class="fw-bold mb-3" style="color:var(--bk-primary);">
                            <i class="bi" :class="masterModal.isEdit ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
                            {{ masterModal.isEdit ? 'Edit Aturan Pelanggaran' : 'Tambah Aturan Pelanggaran' }}
                        </h6>

                        <div class="mb-3">
                            <label for="master-kategori" class="form-label fw-semibold fs-7">Kategori Pelanggaran <span class="text-danger">*</span></label>
                            <select id="master-kategori" name="master_kategori" class="form-select form-select-sm rounded-3" v-model="masterModal.form.kategori">
                                <option value="Ringan">Ringan (Teguran / Persuasif)</option>
                                <option value="Sedang">Sedang (Konseling / Administratif)</option>
                                <option value="Berat">Berat (Pernyataan SP / Skorsing)</option>
                                <option value="Khusus">Khusus (DO / Hukum Negara)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="master-nama-pelanggaran" class="form-label fw-semibold fs-7">Nama / Deskripsi Pelanggaran <span class="text-danger">*</span></label>
                            <input type="text" id="master-nama-pelanggaran" name="master_nama_pelanggaran" class="form-control form-control-sm rounded-3" 
                                   v-model="masterModal.form.nama_pelanggaran" 
                                   placeholder="Contoh: Terlambat masuk sekolah">
                        </div>

                        <div class="mb-3">
                            <label for="master-bobot-poin" class="form-label fw-semibold fs-7">Bobot Poin Pelanggaran <span class="text-danger">*</span></label>
                            <input type="number" id="master-bobot-poin" name="master_bobot_poin" class="form-control form-control-sm rounded-3" 
                                   v-model.number="masterModal.form.bobot_poin" min="1">
                            <div class="form-text fs-9 text-muted">Direkomendasikan: Ringan (5-10), Sedang (15-20), Berat (30-50), Khusus (75-100).</div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-sm btn-outline-secondary rounded-3 w-50" type="button" @click="openMasterModal(null)" id="btn-reset-master-form">
                                Batal / Reset
                            </button>
                            <button class="btn btn-sm btn-primary rounded-3 w-50 text-white fw-bold" @click="submitMasterRule" :disabled="masterModal.saving" id="btn-submit-master">
                                <i class="bi bi-floppy me-1"></i>
                                {{ masterModal.isEdit ? 'Perbarui' : 'Simpan' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Daftar Aturan (Kanan) -->
                <div class="col-lg-8">
                    <div class="bk-card p-4 rounded-4 shadow-sm border bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-gear-fill me-2 text-violet"></i>Aturan Pelanggaran Sekolah
                                </h6>
                                <small class="text-muted fs-8">Kategori tata tertib & alokasi akumulasi poin</small>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary rounded-3" @click="loadPelanggaranMaster" :disabled="loadingPelanggaranMaster" id="btn-refresh-master">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>

                        <div v-if="loadingPelanggaranMaster" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div v-else-if="pelanggaranMasterList?.length > 0" class="table-responsive">
                            <table class="table table-hover align-middle fs-8" id="tbl-master-aturan">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kategori</th>
                                        <th>Nama Pelanggaran</th>
                                        <th class="text-center">Bobot Poin</th>
                                        <th class="text-end" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="rule in paginatedPelanggaranMasterList" :key="rule.id">
                                        <td>
                                            <span class="badge" :class="getKategoriBadge(rule.kategori)">{{ rule.kategori }}</span>
                                        </td>
                                        <td class="fw-semibold text-dark">{{ rule.nama_pelanggaran }}</td>
                                        <td class="text-center fw-bold text-danger">{{ rule.bobot_poin }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-xs btn-outline-primary me-1 rounded-2 py-0 px-2 fw-semibold" style="font-size:0.7rem; line-height:1.5;" @click="openMasterModal(rule)" title="Edit Aturan">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-xs btn-outline-danger rounded-2 py-0 px-2 fw-semibold" style="font-size:0.7rem; line-height:1.5;" @click="deleteMasterRule(rule.id)" title="Hapus Aturan">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls: Master Aturan -->
                        <div v-if="pelanggaranMasterList?.length > 0" class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                            <div class="text-muted fs-8">
                                Menampilkan <strong>{{ (currentPageMaster - 1) * perPageMaster + 1 }}</strong> - <strong>{{ Math.min(currentPageMaster * perPageMaster, pelanggaranMasterList.length) }}</strong> dari <strong>{{ pelanggaranMasterList.length }}</strong> aturan
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button class="btn btn-xs btn-outline-secondary rounded-2 px-2" :disabled="currentPageMaster === 1" @click="currentPageMaster--">
                                    <i class="bi bi-chevron-left me-1"></i>Sebelumnya
                                </button>
                                <template v-for="p in totalMasterPages" :key="'master_p_' + p">
                                    <button v-if="p === 1 || p === totalMasterPages || (p >= currentPageMaster - 1 && p <= currentPageMaster + 1)"
                                            class="btn btn-xs rounded-2 px-2.5 fw-semibold"
                                            :class="p === currentPageMaster ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary text-dark'"
                                            @click="currentPageMaster = p">
                                        {{ p }}
                                    </button>
                                    <span v-else-if="(p === 2 && currentPageMaster > 3) || (p === totalMasterPages - 1 && currentPageMaster < totalMasterPages - 2)" class="px-1 text-muted fs-8">...</span>
                                </template>
                                <button class="btn btn-xs btn-outline-secondary rounded-2 px-2" :disabled="currentPageMaster === totalMasterPages" @click="currentPageMaster++">
                                    Selanjutnya<i class="bi bi-chevron-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-center py-5 text-muted">
                            <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                            Belum ada master aturan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- SANKSI & COUNSELING DETAIL MODAL (REACTIVE MODAL) -->
    <div v-if="sanksiDetailModal.show" class="modal-backdrop-custom d-flex align-items-center justify-content-center" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,23,42,0.6); z-index:1050;">
        <div class="modal-dialog modal-dialog-centered modal-xl animate-fade-in" style="width: 90%; max-width: 1100px; max-height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-content border-0 rounded-4 shadow-lg bg-white" style="height: 100%; display: flex; flex-direction: column; overflow: hidden;">
                
                <!-- Modal Header -->
                <div class="modal-header border-bottom px-4 py-3 d-flex justify-content-between align-items-center bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-shaded text-primary fs-4"></i>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">Profil Kedisiplinan & Intervensi Siswa</h5>
                            <small class="text-muted">Manajemen Sanksi & Buku Pembinaan Guru BK</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close rounded-circle" @click="sanksiDetailModal.show = false" aria-label="Close" id="btn-close-sanksi-modal"></button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="modal-body p-4 fs-8" style="overflow-y: auto; flex: 1;">
                    <!-- Student Header Details -->
                    <div class="p-3 mb-4 rounded-4 shadow-sm border bg-white">
                        <div class="row g-3">
                            <div class="col-md-6 border-end">
                                <span class="badge bg-light text-primary border mb-1">PROFIL SISWA</span>
                                <h5 class="fw-bold text-dark mb-1">{{ sanksiDetailModal.student?.nama_lengkap || 'Memuat Data Siswa...' }}</h5>
                                <div class="text-muted fs-8 d-flex flex-wrap gap-3">
                                    <span><strong>NISN:</strong> {{ sanksiDetailModal.student?.nisn || '-' }}</span>
                                    <span><strong>NIS:</strong> {{ sanksiDetailModal.student?.nis || '-' }}</span>
                                    <span><strong>Kelas:</strong> {{ sanksiDetailModal.student?.nama_kelas || '-' }}</span>
                                </div>
                            </div>
                            <!-- Point Gauge and Status Indicators -->
                            <div class="col-md-6 d-flex flex-column justify-content-center">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark fs-8">Status Akumulasi Poin Siswa</span>
                                    <span class="badge fw-bold"
                                          :class="{
                                              'bg-danger': sanksiDetailModal.total_poin >= 100,
                                              'bg-warning text-dark': sanksiDetailModal.total_poin >= 75 && sanksiDetailModal.total_poin < 100,
                                              'bg-info text-dark': sanksiDetailModal.total_poin >= 50 && sanksiDetailModal.total_poin < 75,
                                              'bg-secondary': sanksiDetailModal.total_poin >= 25 && sanksiDetailModal.total_poin < 50,
                                              'bg-success': sanksiDetailModal.total_poin < 25
                                          }">
                                        {{ 
                                            sanksiDetailModal.total_poin >= 100 ? 'SP 3 / Evaluasi DO' :
                                            sanksiDetailModal.total_poin >= 75 ? 'SP 2 / Skorsing' :
                                            sanksiDetailModal.total_poin >= 50 ? 'SP 1 / Panggilan Ortu' :
                                            sanksiDetailModal.total_poin >= 25 ? 'Peringatan Wali Kelas' : 'Status Aman'
                                        }}
                                    </span>
                                </div>
                                <div class="progress mb-2" style="height: 16px; border-radius: 99px; overflow: hidden; border:1px solid #e2e8f0; background-color: #f1f5f9;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill" role="progressbar" 
                                         :style="{
                                             width: Math.min(100, sanksiDetailModal.total_poin) + '%',
                                             backgroundColor: sanksiDetailModal.total_poin >= 100 ? '#ef4444' : 
                                                              sanksiDetailModal.total_poin >= 75 ? '#f97316' : 
                                                              sanksiDetailModal.total_poin >= 50 ? '#f59e0b' : 
                                                              sanksiDetailModal.total_poin >= 25 ? '#2563eb' : '#10b981'
                                         }"
                                         aria-valuemin="0" aria-valuemax="100">
                                        <span class="fw-bold px-2 text-white" style="font-size: 0.72rem;">{{ sanksiDetailModal.total_poin }} / 100 Poin</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between text-muted" style="font-size:0.68rem;">
                                    <span>0 Poin (Aman)</span>
                                    <span>25 (Wali Kelas)</span>
                                    <span>50 (SP 1)</span>
                                    <span>75 (SP 2)</span>
                                    <span>100 (DO)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Banner Notifikasi Pemanggilan ke Tata Usaha (TU) jika Poin >= 50 -->
                        <div v-if="sanksiDetailModal.total_poin >= 50" class="mt-3 p-3 rounded-3 border border-warning bg-warning bg-opacity-10 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-bell-fill text-danger fs-4"></i>
                                <div>
                                    <strong class="text-dark">Peringatan: Akumulasi Poin Siswa Membutuhkan Pemanggilan Orang Tua</strong>
                                    <div class="text-muted fs-8">Guru BK berhak mengirimkan notifikasi pengajuan penerbitan surat resmi ke bagian Tata Usaha (TU).</div>
                                </div>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1.5 fw-semibold shadow-sm text-white" @click="openModalPengajuanTu(sanksiDetailModal)">
                                    <i class="bi bi-send-fill me-1"></i>Kirim Notifikasi ke TU
                                </button>
                            </div>
                        </div>

                        <!-- Status Notifikasi Surat Keluar dari TU -->
                        <div v-if="statusPengajuanTuList && statusPengajuanTuList.length > 0" class="mt-2 p-2.5 rounded-3 bg-light border">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold fs-8 text-teal"><i class="bi bi-envelope-check-fill me-1"></i>Status Persuratan Tata Usaha (TU):</span>
                            </div>
                            <div v-for="st in statusPengajuanTuList" :key="st.id" class="d-flex justify-content-between align-items-center py-1 border-bottom fs-8">
                                <div>
                                    <span class="fw-semibold text-dark">{{ st.jenis_panggilan }}</span>
                                    <span class="text-muted ms-1">({{ st.rencana_tanggal_menghadap || '-' }})</span>
                                </div>
                                <div>
                                    <span class="badge" :class="st.status_pengajuan === 'Surat Resmi Telah Terbit' ? 'bg-success text-white' : 'bg-warning text-dark'">
                                        {{ st.status_pengajuan }}
                                    </span>
                                    <span v-if="st.nomor_surat" class="font-monospace fw-bold text-teal ms-1">
                                        No: {{ st.nomor_surat }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Split Panels: Timeline (Left) & Pembinaan Log (Right) -->
                    <div class="row g-4">
                        
                        <!-- Timeline of Incidents (Kiri) -->
                        <div class="col-lg-6">
                            <div class="p-3 border rounded-4 bg-light" style="max-height: 400px; overflow-y: auto;">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2 text-danger"></i>Timeline Riwayat Pelanggaran</h6>
                                
                                <div v-if="sanksiDetailModal.violations && sanksiDetailModal.violations.length > 0" class="timeline-container px-2">
                                    <div v-for="v in sanksiDetailModal.violations" :key="v.id" class="border-start border-2 border-danger pb-3 ps-3 position-relative">
                                        <!-- Timeline node dot -->
                                        <div class="rounded-circle bg-danger position-absolute" style="width: 10px; height: 10px; left: -6px; top: 4px;"></div>
                                        
                                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                                            <span class="badge bg-danger rounded-3 fs-9">+{{ v.bobot_poin }} Poin</span>
                                            <small class="text-muted fw-semibold">{{ formatTanggalIndo(v.tanggal_kejadian) }}</small>
                                        </div>
                                        <div class="fw-bold text-dark mt-1">{{ v.nama_pelanggaran }}</div>
                                        <p class="text-muted mb-1 fs-8 mt-1" v-if="v.catatan_keterangan">
                                            {{ v.catatan_keterangan }}
                                        </p>
                                        
                                        <!-- Image Proof Thumbnail -->
                                        <div v-if="v.foto_bukti" class="mt-2">
                                            <a @click.prevent="showFotoModal(secureFileUrl(v.foto_bukti))" href="#" class="d-inline-flex align-items-center gap-1 btn btn-xs btn-outline-secondary p-1 rounded-2">
                                                <i class="bi bi-file-earmark-image text-info"></i>
                                                <span class="fs-9">Lihat Bukti Foto</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                                    Siswa belum memiliki riwayat pelanggaran di tahun ajaran ini.
                                </div>
                            </div>
                        </div>

                        <!-- Follow-up Counseling & Action Logs (Kanan) -->
                        <div class="col-lg-6">
                            <div class="p-3 border rounded-4 bg-light mb-3" style="max-height: 250px; overflow-y: auto;">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-journal-check me-2 text-violet"></i>Log Intervensi & Pembinaan BK</h6>
                                
                                <div v-if="sanksiDetailModal.followUps && sanksiDetailModal.followUps.length > 0" class="timeline-container px-2">
                                    <div v-for="f in sanksiDetailModal.followUps" :key="f.id" class="border-start border-2 border-primary pb-3 ps-3 position-relative">
                                        <!-- Timeline node dot -->
                                        <div class="rounded-circle bg-primary position-absolute" style="width: 10px; height: 10px; left: -6px; top: 4px;"></div>
                                        
                                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                                            <span class="badge bg-primary rounded-3 fs-9">{{ f.jenis_tindakan }}</span>
                                            <small class="text-muted fw-semibold">{{ formatTanggalIndo(f.tanggal_tindakan) }}</small>
                                        </div>
                                        <p class="text-dark mb-1 fs-8 mt-1 fw-semibold">
                                            {{ f.keterangan_tindakan }}
                                        </p>
                                        <div v-if="f.surat_panggilan_pdf || f.foto_bukti" class="mt-2 d-flex gap-2 flex-wrap">
                                            <a v-if="f.surat_panggilan_pdf" :href="secureFileUrl(f.surat_panggilan_pdf)" target="_blank" class="badge bg-danger text-white text-decoration-none rounded-2 py-1 px-2">
                                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Surat Panggilan
                                            </a>
                                            <button v-if="f.foto_bukti" type="button" @click="showFotoModal(secureFileUrl(f.foto_bukti))" class="badge bg-info text-dark border-0 rounded-2 py-1 px-2 cursor-pointer">
                                                <i class="bi bi-image-fill me-1"></i> Foto Dokumentasi
                                            </button>
                                        </div>
                                        <div class="text-muted fs-9 text-end mt-1">
                                            Diinput oleh: <strong>{{ f.nama_guru }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-5 text-muted">
                                    <i class="bi bi-info-circle fs-3 text-muted d-block mb-1"></i>
                                    Belum ada log pembinaan BK/Wali Kelas.
                                </div>
                            </div>

                            <!-- Input Form for Counseling Logs -->
                            <div class="p-3 border rounded-4 bg-white">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-plus-circle me-1"></i>Input Log Pembinaan Baru</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label for="input-tindak-tanggal" class="form-label fw-semibold fs-8 mb-1">Tanggal Tindakan <span class="text-danger">*</span></label>
                                        <input type="date" id="input-tindak-tanggal" name="tanggal_tindakan" class="form-control form-control-sm rounded-3 fs-8" v-model="formTindakLanjut.tanggal_tindakan">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="input-tindak-jenis" class="form-label fw-semibold fs-8 mb-1">Jenis Tindakan <span class="text-danger">*</span></label>
                                        <select id="input-tindak-jenis" name="jenis_tindakan" class="form-select form-select-sm rounded-3 fs-8" v-model="formTindakLanjut.jenis_tindakan">
                                            <option value="Pembinaan Wali Kelas">Pembinaan Wali Kelas</option>
                                            <option value="Konseling BK">Konseling BK</option>
                                            <option value="Pemanggilan Orang Tua">Pemanggilan Orang Tua</option>
                                            <option value="Surat Peringatan (SP)">Surat Peringatan (SP)</option>
                                            <option value="Skorsing Akademik">Skorsing Akademik</option>
                                            <option value="Evaluasi / DO">Evaluasi / DO</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <label for="input-tindak-keterangan" class="form-label fw-semibold fs-8 mb-1">Hasil / Keterangan Tindakan <span class="text-danger">*</span></label>
                                        <textarea id="input-tindak-keterangan" name="keterangan_tindakan" class="form-control form-control-sm rounded-3 fs-8 text-area-vertical" rows="2" 
                                                  v-model="formTindakLanjut.keterangan_tindakan" placeholder="Deskripsikan hasil konseling, komitmen siswa, atau sanksi administratif..."></textarea>
                                    </div>

                                    <!-- Upload Files Fields -->
                                    <div class="col-md-6 mt-2">
                                        <label for="input-tindak-surat" class="form-label fw-semibold fs-8 mb-1 text-dark">
                                            <i class="bi bi-file-earmark-pdf text-danger me-1"></i>Surat Panggilan (PDF / Foto)
                                        </label>
                                        <input type="file" id="input-tindak-surat" class="form-control form-control-sm rounded-3 fs-8" accept=".pdf,.jpg,.jpeg,.png,.webp" @change="handleSuratPanggilanUpload">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label for="input-tindak-foto" class="form-label fw-semibold fs-8 mb-1 text-dark">
                                            <i class="bi bi-camera-fill text-primary me-1"></i>Foto Bukti / Dokumentasi
                                            <span class="badge bg-info text-dark fw-normal ms-1 fs-9">Kamera HP &bull; Otomatis &lt;1MB</span>
                                        </label>
                                        <input type="file" id="input-tindak-foto" class="form-control form-control-sm rounded-3 fs-8" accept="image/*" capture="environment" @change="handleFotoPembinaanUpload">
                                    </div>

                                    <div class="col-md-12 mt-3 text-end">
                                        <button class="btn btn-sm btn-primary rounded-3 px-3 py-1.5 fw-bold" @click="submitTindakLanjut(sanksiDetailModal.student.id)" :disabled="submittingTindakLanjut" id="btn-save-tindakan">
                                            <span v-if="submittingTindakLanjut" class="spinner-border spinner-border-sm me-1"></span>
                                            <i v-else class="bi bi-check-circle me-1"></i> Simpan Catatan Pembinaan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL PENGAJUAN NOTIFIKASI PEMANGGILAN ORANG TUA KE TATA USAHA (TU) -->
    <div v-if="modalPengajuanTu.show" class="modal-backdrop-custom d-flex align-items-center justify-content-center" style="position:fixed; top:0; left:0; right:0; bottom:0; width:100vw; height:100vh; background:rgba(15,23,42,0.7); z-index:99999; overflow-y:auto;">
        <div class="modal-dialog modal-dialog-centered modal-lg my-auto animate-fade-in" style="width: 90%; max-width: 700px;">
            <div class="modal-content border-0 rounded-4 shadow-lg bg-white overflow-hidden">
                <div class="modal-header bg-danger text-white px-4 py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-send-fill fs-5"></i>
                        <h6 class="modal-title fw-bold mb-0">Pengajuan Notifikasi Pemanggilan ke Tata Usaha (TU)</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalPengajuanTu.show = false"></button>
                </div>
                <form @submit.prevent="submitNotifikasiPengajuanTu">
                    <div class="modal-body p-4 fs-8">
                        <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center gap-2 fs-8 rounded-3">
                            <i class="bi bi-info-circle-fill fs-5 text-warning"></i>
                            <div>
                                Guru BK mengirim usulan pemanggilan orang tua untuk siswa <strong>{{ modalPengajuanTu.siswa_nama }}</strong> ({{ modalPengajuanTu.siswa_kelas }}) dengan akumulasi <strong>{{ modalPengajuanTu.total_poin }} Poin</strong>. Surat dinas resmi akan diterbitkan oleh Tata Usaha.
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1">Jenis Surat Pemanggilan <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm rounded-3" v-model="modalPengajuanTu.form.jenis_panggilan" required>
                                    <option value="Surat Panggilan Orang Tua I">Surat Panggilan Orang Tua I</option>
                                    <option value="Surat Panggilan Orang Tua II">Surat Panggilan Orang Tua II</option>
                                    <option value="Surat Panggilan Orang Tua III">Surat Panggilan Orang Tua III</option>
                                    <option value="Panggilan Khusus / Darurat">Panggilan Khusus / Darurat</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1">Usulan Ruangan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm rounded-3" v-model="modalPengajuanTu.form.ruangan" required placeholder="Contoh: Ruang Konseling BK / Ruang Kepala Sekolah">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1">Usulan Tanggal Menghadap <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm rounded-3" v-model="modalPengajuanTu.form.rencana_tanggal_menghadap" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1">Usulan Jam Menghadap <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm rounded-3" v-model="modalPengajuanTu.form.rencana_jam_menghadap" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1">Alasan / Catatan Pendukung untuk Tata Usaha <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm rounded-3" rows="3" v-model="modalPengajuanTu.form.alasan_pemanggilan" required placeholder="Jelaskan ringkasan pelanggaran dan urgensi pemanggilan wali murid..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" @click="modalPengajuanTu.show = false">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger text-white fw-bold rounded-3 px-4" :disabled="modalPengajuanTu.submitting">
                            <span v-if="modalPengajuanTu.submitting" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-send-fill me-1"></i> Kirim Notifikasi ke TU
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PROOF IMAGE LIGHTBOX MODAL -->
    <div v-if="fotoModal.show" class="modal-backdrop-custom d-flex align-items-center justify-content-center" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,23,42,0.85); z-index:99999;" @click="fotoModal.show = false">
        <div class="position-relative p-2" style="max-width:90%; max-height:90%; z-index:100000;" @click.stop>
            <img :src="fotoModal.src" class="img-fluid rounded-3 shadow-lg bg-white" style="max-height:80vh;" alt="Foto bukti fisik pelanggaran">
            <button class="btn btn-dark rounded-circle position-absolute" style="top:-15px; right:-15px; width:36px; height:36px; display:flex; align-items:center; justify-content:center; z-index:100001;" @click="fotoModal.show = false" id="btn-close-foto-modal">
                <i class="bi bi-x fs-4"></i>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 8: BEASISWA SISWA
    ════════════════════════════════════════════════════════════ -->
    <?php if(empty($allowed_bk_tabs) || in_array('beasiswa', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'beasiswa'">
        <!-- Warning untuk Super Admin jika belum memilih sekolah -->
        <div v-if="userRole === 'super_admin' && !currentTenantId" class="text-center py-5">
            <div class="card border-0 shadow-sm rounded-4 p-5 mx-auto animate-fade-in" style="max-width: 500px; background: #fff;">
                <i class="bi bi-funnel text-warning fs-1 mb-3"></i>
                <h5 class="fw-bold mb-2 text-dark">Pilih Sekolah Terlebih Dahulu</h5>
                <p class="text-muted fs-7 mb-0">
                    Silakan gunakan filter di bagian atas halaman untuk memilih sekolah sebelum mengelola data beasiswa siswa.
                </p>
            </div>
        </div>

        <div v-else class="animate-fade-in">
            <!-- Full Width Card: Daftar Seluruh Beasiswa & Search/Filter/Add/Ekspor -->
            <div class="bk-card p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-gift-fill text-success"></i>
                            Manajemen Data Beasiswa Siswa
                        </h5>
                        <p class="text-muted fs-8 mb-0">Kelola riwayat penerima beasiswa prestasi, bantuan sosial (PIP/KIP), yayasan, dan beasiswa kedinasan.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Filter Tahun Penerimaan Beasiswa (Dinamis Sesuai Database Tenant) -->
                        <select class="form-select form-select-sm rounded-3 shadow-none" style="width: 145px; font-size: 0.8rem;" v-model="filterBeasiswaTahun" @change="loadAllBeasiswa">
                            <option value="">Semua Tahun</option>
                            <option v-for="thn in beasiswaTahunList" :key="thn" :value="thn">Tahun {{ thn }}</option>
                        </select>
                        
                        <!-- Download Excel Button -->
                        <button class="btn btn-sm btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-1 fw-semibold fs-8" @click="exportBeasiswaExcel">
                            <i class="bi bi-file-earmark-spreadsheet text-success"></i>
                            Ekspor Excel
                        </button>

                        <!-- Tombol Tambah Beasiswa (Popup Modal) -->
                        <button class="btn btn-sm btn-success text-white rounded-3 d-inline-flex align-items-center gap-1 fw-bold fs-8 px-3 shadow-sm" @click="openModalTambahBeasiswa">
                            <i class="bi bi-plus-circle-fill me-1"></i>
                            Tambah Beasiswa
                        </button>
                    </div>
                </div>

                <!-- Live Search Bar Filter -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6 col-lg-5">
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute text-muted" style="top: 9px; left: 14px;"></i>
                            <input type="text" 
                                   class="form-control form-control-sm rounded-3 ps-5 fs-8" 
                                   placeholder="Cari Nama Siswa, NISN, Kelas, Jenis Beasiswa..." 
                                   v-model="filterBeasiswaSearch">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-7 d-flex align-items-center justify-content-md-end text-muted fs-8">
                        Menampilkan <strong class="mx-1 text-dark">{{ filteredBeasiswaList.length }}</strong> dari {{ allBeasiswaList.length }} penerima beasiswa
                    </div>
                </div>

                <!-- Loader -->
                <div v-if="loadingBeasiswaList" class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                    <div class="spinner-border text-success spinner-border-sm mb-2"></div>
                    <span class="fs-7">Memuat data beasiswa...</span>
                </div>

                <!-- Tabel Data Beasiswa -->
                <div v-else class="table-responsive rounded-3 border">
                    <table class="table table-hover align-middle fs-8 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 4%" class="text-center">No.</th>
                                <th style="width: 25%">Nama Siswa & NISN</th>
                                <th style="width: 10%">Kelas</th>
                                <th style="width: 18%">Jenis Beasiswa</th>
                                <th style="width: 15%">Penyelenggara / Sumber</th>
                                <th style="width: 8%" class="text-center">Tahun</th>
                                <th style="width: 12%" class="text-end">Nominal</th>
                                <th style="width: 8%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(b, bIdx) in filteredBeasiswaList" :key="b.id">
                                <td class="text-center text-muted fw-semibold">{{ bIdx + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ b.nama_lengkap }}</div>
                                    <div class="text-muted fs-9" style="font-family: monospace;">NISN: {{ b.nisn || '-' }}</div>
                                    <div v-if="b.keterangan" class="text-muted fs-9 fst-italic mt-1"><i class="bi bi-info-circle me-1"></i>{{ b.keterangan }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-9">
                                        {{ b.nama_kelas || '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-8 fw-semibold">
                                        {{ b.jenis_beasiswa }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-dark">{{ b.sumber || '-' }}</span>
                                </td>
                                <td class="text-center font-monospace fw-bold text-dark">
                                    {{ b.tahun_menerima }}
                                </td>
                                <td class="text-end fw-semibold text-success">
                                    {{ b.nominal ? 'Rp ' + Number(b.nominal).toLocaleString('id-ID') : '-' }}
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary btn-xs rounded-2 px-2 py-1 me-1" @click="openModalEditBeasiswa(b)" title="Edit Beasiswa">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-xs rounded-2 px-2 py-1" @click="hapusBeasiswa(b.id)" title="Hapus Data">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredBeasiswaList || filteredBeasiswaList.length === 0">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                                    Tidak ada data beasiswa yang sesuai dengan kriteria pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL POPUP TAMBAH / EDIT BEASISWA -->
    <div v-if="modalBeasiswa.show" class="modal-backdrop-custom d-flex align-items-center justify-content-center" style="position:fixed; top:0; left:0; right:0; bottom:0; width:100vw; height:100vh; background:rgba(15,23,42,0.7); z-index:99999; overflow-y:auto;">
        <div class="modal-dialog modal-dialog-centered modal-lg my-auto animate-fade-in" style="width: 92%; max-width: 680px;">
            <div class="modal-content border-0 rounded-4 shadow-lg bg-white overflow-hidden">
                <div class="modal-header bg-success text-white px-4 py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-gift-fill fs-5"></i>
                        <h6 class="modal-title fw-bold mb-0">
                            {{ modalBeasiswa.isEdit ? 'Edit Data Beasiswa Siswa' : 'Tambah Data Beasiswa Siswa' }}
                        </h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalBeasiswa.show = false"></button>
                </div>
                <form @submit.prevent="simpanBeasiswaModal">
                    <div class="modal-body p-4 fs-8">
                        <!-- Mode Tambah: Input Siswa dengan Autocomplete -->
                        <div v-if="!modalBeasiswa.isEdit" class="mb-3">
                            <label class="form-label fw-bold mb-1 text-dark">Pilih Siswa <span class="text-danger">*</span></label>
                            
                            <!-- Siswa Belum Terpilih: Tampilkan Search Bar -->
                            <div v-if="!modalBeasiswa.selectedSiswa" class="position-relative">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" 
                                           class="form-control form-control-sm border-start-0 rounded-end-3" 
                                           placeholder="Ketik Nama, NISN, atau NIK Siswa..."
                                           v-model="modalBeasiswa.searchSiswa"
                                           @input="searchSiswaModalDebounce"
                                           @focus="modalBeasiswa.showDropdown = true"
                                           @blur="hideModalDropdownDelay"
                                           required />
                                </div>
                                
                                <!-- Dropdown Pencarian Siswa -->
                                <div v-if="modalBeasiswa.showDropdown && modalBeasiswa.siswaOptions?.length > 0" 
                                     class="position-absolute w-100 bg-white border rounded-3 shadow-lg p-1 mt-1 z-3"
                                     style="max-height: 220px; overflow-y: auto;">
                                    <div v-for="s in modalBeasiswa.siswaOptions" 
                                         :key="s.id" 
                                         @mousedown.prevent="selectSiswaModal(s)"
                                         class="p-2 rounded-2 hover-bg-slate cursor-pointer fs-8 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold text-dark">{{ s.nama_lengkap }}</div>
                                            <div class="text-muted fs-9">NISN: {{ s.nisn || '-' }} | Kelas: {{ s.nama_kelas || '-' }}</div>
                                        </div>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 fs-9">Pilih</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Siswa Sudah Terpilih: Tampilkan Info Ringkas -->
                            <div v-else class="p-3 bg-light border rounded-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                        {{ modalBeasiswa.selectedSiswa.nama_lengkap.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ modalBeasiswa.selectedSiswa.nama_lengkap }}</div>
                                        <div class="text-muted fs-9">NISN: {{ modalBeasiswa.selectedSiswa.nisn || '-' }} | Kelas: {{ modalBeasiswa.selectedSiswa.nama_kelas || '-' }}</div>
                                    </div>
                                </div>
                                <button type="button" @click="clearSiswaModal" class="btn btn-xs btn-outline-secondary rounded-3 py-1 px-2 fw-semibold fs-9">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Ganti
                                </button>
                            </div>
                        </div>

                        <!-- Mode Edit: Tampilkan Info Siswa yang Diedit -->
                        <div v-else class="mb-3 p-3 bg-light border rounded-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                    {{ modalBeasiswa.selectedSiswa?.nama_lengkap?.charAt(0) || 'S' }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ modalBeasiswa.selectedSiswa?.nama_lengkap }}</div>
                                    <div class="text-muted fs-9">NISN: {{ modalBeasiswa.selectedSiswa?.nisn || '-' }} | Kelas: {{ modalBeasiswa.selectedSiswa?.nama_kelas || '-' }}</div>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 fs-9">Siswa Terpilih</span>
                        </div>

                        <!-- Shortcut Pilihan Beasiswa Populer -->
                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1 text-dark">Pilihan Jenis Beasiswa Cepat:</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" 
                                        v-for="tag in ['PIP (Program Indonesia Pintar)', 'KIP-Kuliah', 'Beasiswa Prestasi Akademik', 'Beasiswa Non-Akademik', 'Beasiswa Pemda', 'Beasiswa Yayasan', 'Baznas', 'CSR Perusahaan']" 
                                        :key="tag" 
                                        @click="modalBeasiswa.form.jenis_beasiswa = tag"
                                        class="btn btn-xs btn-outline-success rounded-pill px-2 py-1 fs-9">
                                    + {{ tag }}
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1 text-dark">Jenis / Nama Beasiswa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm rounded-3" v-model="modalBeasiswa.form.jenis_beasiswa" required placeholder="Contoh: PIP, Beasiswa Prestasi, Pemda">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1 text-dark">Penyelenggara / Sumber Beasiswa</label>
                                <input type="text" class="form-control form-control-sm rounded-3" v-model="modalBeasiswa.form.sumber" placeholder="Contoh: Kemenag, Kemendikbud, CSR Bank">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1 text-dark">Tahun Menerima <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm rounded-3 font-monospace" v-model.number="modalBeasiswa.form.tahun_menerima" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1 text-dark">Nominal Bantuan (Rp) (Opsional)</label>
                                <input type="number" class="form-control form-control-sm rounded-3 font-monospace" v-model.number="modalBeasiswa.form.nominal" placeholder="Contoh: 1000000">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1 text-dark">Keterangan Tambahan / Catatan</label>
                            <textarea class="form-control form-control-sm rounded-3" rows="2" v-model="modalBeasiswa.form.keterangan" placeholder="Contoh: Bantuan semester genap, pencairan tahap 1..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" @click="modalBeasiswa.show = false">Batal</button>
                        <button type="submit" class="btn btn-sm btn-success text-white fw-bold rounded-3 px-4" :disabled="modalBeasiswa.saving">
                            <span v-if="modalBeasiswa.saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-circle-fill me-1"></i>
                            {{ modalBeasiswa.isEdit ? 'Simpan Perubahan' : 'Simpan Beasiswa' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: KESIAPAN & ELIGIBILITAS SISWA (PDSS)
    ════════════════════════════════════════════════════════════ -->
    <!-- ═══════════════════════════════════════════════════════════
         TAB: KESIAPAN & ELIGIBILITAS SISWA (PDSS)
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && in_array('kesiapan', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'kesiapan'">
        <!-- Premium Header Banner -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);">
            <div class="card-body p-4 text-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 fs-8 text-uppercase tracking-wider fw-bold">
                                <i class="bi bi-patch-check-fill text-warning me-1"></i> Portal PDSS SNPMB
                            </span>
                            <span class="badge bg-success bg-opacity-25 text-success-light rounded-pill px-3 py-1 fs-8 border border-success border-opacity-25">
                                Real-Time Analytics
                            </span>
                        </div>
                        <h4 class="fw-bold mb-1 text-white"><i class="bi bi-award-fill me-2 text-warning"></i>Kesiapan & Eligibilitas Siswa PDSS</h4>
                        <p class="text-white-50 fs-7 mb-0" style="max-width: 680px;">
                            Sistem perangkingan otomatis rata-rata nilai rapor 5 semester dan penentuan eligibilitas siswa Kelas 12 sesuai kuota akreditasi sekolah untuk pendaftaran SNBP.
                        </p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <button class="btn btn-warning text-dark font-semibold rounded-3 px-3 shadow-sm d-flex align-items-center gap-2"
                                @click="autoCalculateKesiapan()" :disabled="autoCalculatingKesiapan">
                            <span v-if="autoCalculatingKesiapan" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-lightning-charge-fill fs-6"></i>
                            <span>Hitung Eligibilitas Otomatis</span>
                        </button>
                        <button class="btn btn-outline-light rounded-3 px-3 d-flex align-items-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#modalConfigKuotaPDSS">
                            <i class="bi bi-sliders"></i>
                            <span>Kuota ({{ selectedKuotaPct }}%)</span>
                        </button>
                        <button class="btn btn-success rounded-3 px-3 d-flex align-items-center gap-2 shadow-sm"
                                @click="exportKesiapanExcel()" :disabled="!kesiapanList || kesiapanList?.length === 0">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                            <span>Export Excel</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white hover-shadow transition">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 p-3 d-flex align-items-center justify-content-center" style="background:#eef2ff; color:#4f46e5; width:54px; height:54px;">
                            <i class="bi bi-people-fill fs-3"></i>
                        </div>
                        <div>
                            <div class="fs-8 text-muted fw-semibold">Total Siswa Kelas 12</div>
                            <div class="fs-3 fw-bold text-dark">{{ kesiapanSummary.total_siswa || 0 }} <span class="fs-8 text-muted font-normal">Siswa</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white hover-shadow transition">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 p-3 d-flex align-items-center justify-content-center" style="background:#ecfdf5; color:#10b981; width:54px; height:54px;">
                            <i class="bi bi-check-circle-fill fs-3"></i>
                        </div>
                        <div>
                            <div class="fs-8 text-muted fw-semibold">Siswa Eligible SNBP</div>
                            <div class="fs-3 fw-bold text-success">{{ kesiapanSummary.total_eligible || 0 }} <span class="fs-8 text-muted font-normal">Siswa</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white hover-shadow transition">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 p-3 d-flex align-items-center justify-content-center" style="background:#fffbeb; color:#f59e0b; width:54px; height:54px;">
                            <i class="bi bi-pie-chart-fill fs-3"></i>
                        </div>
                        <div>
                            <div class="fs-8 text-muted fw-semibold">Kuota Terpenuhi</div>
                            <div class="fs-3 fw-bold text-warning">{{ kesiapanSummary.persentase_eligible || 0 }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white hover-shadow transition">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 p-3 d-flex align-items-center justify-content-center" style="background:#faf5ff; color:#a855f7; width:54px; height:54px;">
                            <i class="bi bi-graph-up-arrow fs-3"></i>
                        </div>
                        <div>
                            <div class="fs-8 text-muted fw-semibold">Rata-Rata Tertinggi</div>
                            <div class="fs-3 fw-bold text-purple">{{ kesiapanSummary.max_nilai_rata_rata || '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar & Table Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 rounded-top-4 p-4 pb-3">
                <div class="row g-2 align-items-center">
                    <!-- Search Input -->
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control form-control-sm bg-light border-start-0 rounded-end-3"
                                   placeholder="Cari Nama Siswa, NISN, atau Kelas..." v-model="kesiapanFilter.search" @input="loadKesiapan()">
                        </div>
                    </div>

                    <!-- Filter Jurusan -->
                    <div class="col-6 col-md-3">
                        <select class="form-select form-select-sm rounded-3 bg-light border-0" v-model="kesiapanFilter.jurusan" @change="loadKesiapan()">
                            <option value="">— Semua Jurusan / Program —</option>
                            <option value="MIPA">MIPA / IPA</option>
                            <option value="IPS">IPS</option>
                            <option value="Bahasa">Bahasa</option>
                            <option value="TKJ">Teknik Komputer & Jaringan</option>
                            <option value="RPL">Rekayasa Perangkat Lunak</option>
                        </select>
                    </div>

                    <!-- Filter Status Eligible -->
                    <div class="col-6 col-md-3">
                        <select class="form-select form-select-sm rounded-3 bg-light border-0" v-model="kesiapanFilter.status_eligible" @change="loadKesiapan()">
                            <option value="">— Semua Status Status —</option>
                            <option value="eligible">✅ Eligible SNBP saja</option>
                            <option value="non_eligible">❌ Tidak Eligible saja</option>
                        </select>
                    </div>

                    <!-- Filter Tahun Ajaran -->
                    <div class="col-12 col-md-2">
                        <select class="form-select form-select-sm rounded-3 bg-light border-0" v-model="kesiapanFilter.tahun_ajaran_id" @change="loadKesiapan()">
                            <option value="">— Tahun Ajaran —</option>
                            <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div v-if="loadingKesiapan" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="text-muted mt-2 fs-7">Memuat data kesiapan PDSS siswa...</p>
                </div>
                <div v-else>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-8">
                            <thead class="bg-light text-muted text-uppercase fs-9 tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-center" style="width:70px;">Rank</th>
                                    <th class="py-3">Nama Siswa</th>
                                    <th class="py-3">NISN</th>
                                    <th class="py-3">Kelas & Jurusan</th>
                                    <th class="py-3 text-center">Rata-Rata 5 Sem</th>
                                    <th class="py-3 text-center">Status Eligible</th>
                                    <th class="py-3 text-center" style="width:210px;" v-if="userRole !== 'siswa'">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, idx) in kesiapanList" :key="s.siswa_id"
                                    :class="{'bg-success bg-opacity-10': s.is_eligible}">
                                    <!-- Rank Badge -->
                                    <td class="text-center px-4">
                                        <span v-if="s.ranking_sekolah === 1" class="badge rounded-pill bg-warning text-dark px-2.5 py-1.5 fs-8 shadow-sm">🥇 #1</span>
                                        <span v-else-if="s.ranking_sekolah === 2" class="badge rounded-pill bg-secondary text-white px-2.5 py-1.5 fs-8 shadow-sm">🥈 #2</span>
                                        <span v-else-if="s.ranking_sekolah === 3" class="badge rounded-pill bg-amber text-dark px-2.5 py-1.5 fs-8 shadow-sm" style="background:#cd7f32; color:white!important;">🥉 #3</span>
                                        <span v-else-if="s.ranking_sekolah > 0" class="fw-bold text-muted">#{{ s.ranking_sekolah }}</span>
                                        <span v-else class="text-muted fs-9">-</span>
                                    </td>

                                    <!-- Student Name -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center"
                                                 style="width:36px; height:36px; font-size:14px;">
                                                {{ s.nama_lengkap.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <a href="javascript:void(0)" class="fw-bold text-dark text-decoration-none hover-primary"
                                                   @click="openDetailNilaiSiswa(s.siswa_id)">
                                                    {{ s.nama_lengkap }}
                                                </a>
                                                <div class="fs-9 text-muted">ID: {{ s.siswa_id.substring(0,8) }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- NISN -->
                                    <td class="font-monospace fw-semibold text-secondary">{{ s.nisn || '-' }}</td>

                                    <!-- Class & Major -->
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 me-1">
                                            <i class="bi bi-mortarboard me-1"></i>{{ s.nama_kelas }}
                                        </span>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1">
                                            {{ s.jurusan }}
                                        </span>
                                    </td>

                                    <!-- 5-Semester Average -->
                                    <td class="text-center">
                                        <div class="d-inline-block text-center">
                                            <span class="badge rounded-pill px-3 py-1.5 fs-8 fw-bold shadow-2xs"
                                                  :class="s.nilai_rata_rata >= 85 ? 'bg-success text-white' : s.nilai_rata_rata >= 78 ? 'bg-primary text-white' : 'bg-warning text-dark'">
                                                {{ s.nilai_rata_rata ? Number(s.nilai_rata_rata).toFixed(2) : '-' }}
                                            </span>
                                            <div class="progress mt-1" style="height: 4px; width: 64px;">
                                                <div class="progress-bar bg-success" :style="{ width: Math.min(100, s.nilai_rata_rata) + '%' }"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status Eligible -->
                                    <td class="text-center">
                                        <span v-if="s.is_eligible" class="badge bg-success rounded-pill px-3 py-1.5 shadow-2xs">
                                            <i class="bi bi-check-circle-fill me-1"></i> Eligible SNBP
                                        </span>
                                        <span v-else class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill px-3 py-1.5">
                                            Tidak Eligible
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center" v-if="userRole !== 'siswa'">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1 fs-9"
                                                    @click="openDetailNilaiSiswa(s.siswa_id)" title="Rincian Nilai 5 Semester">
                                                <i class="bi bi-eye me-1"></i> Rapor
                                            </button>
                                            <button class="btn btn-sm rounded-3 px-2 py-1 fs-9"
                                                    :class="s.is_eligible ? 'btn-outline-danger' : 'btn-outline-success'"
                                                    @click="toggleEligible(s.siswa_id, s.is_eligible)"
                                                    title="Ubah Status Eligible Manual">
                                                <i :class="s.is_eligible ? 'bi bi-x-circle me-1' : 'bi bi-check-circle me-1'"></i>
                                                {{ s.is_eligible ? 'Batalkan' : 'Set Eligible' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!kesiapanList || kesiapanList?.length === 0">
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                        Tidak ada data siswa Kelas 12 yang cocok dengan filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Modal Konfigurasi Kuota Akreditasi -->
    <div class="modal fade" id="modalConfigKuotaPDSS" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-sliders text-primary me-2"></i>Konfigurasi Kuota Akreditasi Sekolah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted fs-8">Pilih persentase kuota siswa eligible sesuai standar akreditasi sekolah Anda untuk pendaftaran SNBP:</p>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <button class="btn w-100 py-3 rounded-3 text-start border"
                                    :class="selectedKuotaPct === 40 ? 'btn-primary border-primary fw-bold' : 'btn-light'"
                                    @click="selectedKuotaPct = 40">
                                <div class="fs-6 mb-1">Akreditasi A (40%)</div>
                                <div class="fs-9 opacity-75">40% siswa terbaik per jurusan</div>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn w-100 py-3 rounded-3 text-start border"
                                    :class="selectedKuotaPct === 25 ? 'btn-primary border-primary fw-bold' : 'btn-light'"
                                    @click="selectedKuotaPct = 25">
                                <div class="fs-6 mb-1">Akreditasi B (25%)</div>
                                <div class="fs-9 opacity-75">25% siswa terbaik per jurusan</div>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn w-100 py-3 rounded-3 text-start border"
                                    :class="selectedKuotaPct === 5 ? 'btn-primary border-primary fw-bold' : 'btn-light'"
                                    @click="selectedKuotaPct = 5">
                                <div class="fs-6 mb-1">Akreditasi C (5%)</div>
                                <div class="fs-9 opacity-75">5% siswa terbaik per jurusan</div>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn w-100 py-3 rounded-3 text-start border"
                                    :class="selectedKuotaPct === 100 ? 'btn-primary border-primary fw-bold' : 'btn-light'"
                                    @click="selectedKuotaPct = 100">
                                <div class="fs-6 mb-1">Semua Siswa (100%)</div>
                                <div class="fs-9 opacity-75">100% siswa Kelas 12</div>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary rounded-3" data-bs-dismiss="modal" @click="autoCalculateKesiapan()">
                        Simpan & Hitung Ulang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Nilai Rapor 5 Semester -->
    <div class="modal fade" id="modalDetailNilaiPDSS" tabindex="-1" aria-hidden="true" :class="{'show d-block': detailNilaiModal.show}" style="background: rgba(0,0,0,0.5);" v-if="detailNilaiModal.show">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Rincian Nilai Rapor 5 Semester</h5>
                        <p class="text-muted fs-8 mb-0" v-if="detailNilaiModal.siswa">
                            <strong>{{ detailNilaiModal.siswa.nama_lengkap }}</strong> (NISN: {{ detailNilaiModal.siswa.nisn || '-' }}) — {{ detailNilaiModal.siswa.kelas_saat_ini }}
                        </p>
                    </div>
                    <button type="button" class="btn-close" @click="detailNilaiModal.show = false"></button>
                </div>
                <div class="modal-body p-4">
                    <div v-if="detailNilaiModal.loading" class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="text-muted mt-2 fs-7">Memuat rincian nilai rapor...</p>
                    </div>
                    <div v-else>
                        <div class="alert alert-primary border-0 rounded-3 py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                            <span class="fs-8 fw-semibold"><i class="bi bi-info-circle me-1"></i> Rata-Rata Overall 5 Semester:</span>
                            <span class="fs-6 fw-bold text-primary">{{ detailNilaiModal.overallAverage }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle fs-8 mb-0">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th class="text-start">Mata Pelajaran Utama PDSS</th>
                                        <th>Sem 1</th>
                                        <th>Sem 2</th>
                                        <th>Sem 3</th>
                                        <th>Sem 4</th>
                                        <th>Sem 5</th>
                                        <th>Rata-Rata</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in detailNilaiModal.breakdown" :key="b.mata_pelajaran">
                                        <td class="fw-semibold">{{ b.mata_pelajaran }}</td>
                                        <td class="text-center">{{ b.sem_1 }}</td>
                                        <td class="text-center">{{ b.sem_2 }}</td>
                                        <td class="text-center">{{ b.sem_3 }}</td>
                                        <td class="text-center">{{ b.sem_4 }}</td>
                                        <td class="text-center">{{ b.sem_5 }}</td>
                                        <td class="text-center fw-bold text-success">{{ b.rata_rata }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-3" @click="detailNilaiModal.show = false">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: SIMULASI PILIHAN KAMPUS (PDSS)
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && in_array('simulasi', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'simulasi'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 rounded-top-4 p-4 pb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-building-check me-2 text-primary"></i>Simulasi Pilihan Kampus</h5>
                        <p class="text-muted fs-8 mb-0">Kelola pilihan kampus & prodi siswa eligible per periode simulasi SNBP.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <select class="form-select form-select-sm rounded-3" v-model="simulasiFilter.tahun_ajaran_id" @change="loadSimulasi()" style="min-width:180px;">
                            <option value="">— Tahun Ajaran —</option>
                            <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                        </select>
                        <button class="btn btn-sm btn-success rounded-3" @click="exportSimulasiXlsx()" :disabled="!simulasiList || simulasiList?.length === 0">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export XLSX SNBP
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Simulasi Setting Locks -->
                <div class="row g-3 mb-4" v-if="userRole !== 'siswa'">
                    <div class="col-12">
                        <div class="p-3 rounded-3 border" style="background:#f8fafc;">
                            <h6 class="fw-bold mb-3"><i class="bi bi-lock me-2 text-warning"></i>Pengaturan Periode Simulasi</h6>
                            <div class="d-flex flex-wrap gap-3">
                                <div v-for="n in [1,2]" :key="n" class="d-flex align-items-center gap-2 p-2 rounded-3 border bg-white">
                                    <span class="badge rounded-pill px-3 py-2" :class="simulasiSettings[n] && simulasiSettings[n].is_open ? 'bg-success' : 'bg-secondary'">
                                        Simulasi {{ n }}
                                    </span>
                                    <span class="fs-8 text-muted">{{ simulasiSettings[n] && simulasiSettings[n].is_open ? 'Dibuka' : 'Ditutup' }}</span>
                                    <button class="btn btn-xs rounded-2 px-2 py-1"
                                            :class="simulasiSettings[n] && simulasiSettings[n].is_open ? 'btn-outline-danger' : 'btn-outline-success'"
                                            @click="toggleSimulasiSetting(n)">
                                        {{ simulasiSettings[n] && simulasiSettings[n].is_open ? 'Tutup' : 'Buka' }}
                                    </button>
                                </div>
                            </div>
                            <p class="fs-9 text-muted mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Simulasi 2 hanya bisa dibuka setelah Simulasi 1 dikunci.</p>
                        </div>
                    </div>
                </div>
                <!-- Simulasi Data -->
                <div v-if="loadingSimulasi" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="text-muted mt-2 fs-7">Memuat data simulasi...</p>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-8">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">#</th>
                                <th>Nama Siswa</th>
                                <th>NISN</th>
                                <th class="text-center">Sim</th>
                                <th class="text-center">Pilihan</th>
                                <th>Nama Kampus</th>
                                <th>Program Studi</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(s, idx) in simulasiList" :key="s.id">
                                <td class="px-3 text-muted">{{ idx + 1 }}</td>
                                <td class="fw-semibold">{{ s.nama_lengkap }}</td>
                                <td class="font-monospace">{{ s.nisn || '-' }}</td>
                                <td class="text-center"><span class="badge bg-primary rounded-pill">Sim {{ s.no_simulasi }}</span></td>
                                <td class="text-center"><span class="badge bg-info text-dark rounded-pill">Pilihan {{ s.no_pilihan }}</span></td>
                                <td>{{ s.nama_kampus || '-' }}</td>
                                <td>{{ s.nama_prodi || '-' }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill" :class="s.status === 'final' ? 'bg-success' : 'bg-warning text-dark'">
                                        {{ s.status === 'final' ? 'Final' : 'Draft' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="simulasiList.length === 0">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    Belum ada data simulasi pilihan kampus.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: MASTER KAMPUS & PRODI
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && in_array('master_kampus', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'master_kampus'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 rounded-top-4 p-4 pb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-bank me-2 text-primary"></i>Master Kampus & Program Studi</h5>
                        <p class="text-muted fs-8 mb-0">Kelola database kampus PTN/PTS dan program studi untuk referensi simulasi pilihan siswa.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="text" class="form-control form-control-sm rounded-3" placeholder="Cari kampus..." v-model="kampusSearch" @input="loadKampus()" style="min-width:220px;">
                        <button class="btn btn-sm btn-primary rounded-3" @click="openKampusModal(null)" v-if="userRole !== 'siswa'">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Kampus
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div v-if="loadingKampus" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-8">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">#</th>
                                <th>Nama Kampus</th>
                                <th>Jenis</th>
                                <th>Kota</th>
                                <th class="text-center">Akreditasi</th>
                                <th class="text-center">Jumlah Prodi</th>
                                <th class="text-center" v-if="userRole !== 'siswa'">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(k, idx) in kampusList" :key="k.id">
                                <td class="px-4 text-muted">{{ idx + 1 }}</td>
                                <td class="fw-semibold">{{ k.nama_kampus }}</td>
                                <td><span class="badge rounded-pill" :class="k.jenis === 'PTN' ? 'bg-primary' : k.jenis === 'PTS' ? 'bg-info text-dark' : 'bg-secondary'">{{ k.jenis }}</span></td>
                                <td>{{ k.kota || '-' }}</td>
                                <td class="text-center"><span class="badge bg-success rounded-pill">{{ k.akreditasi || '-' }}</span></td>
                                <td class="text-center fw-bold text-primary">{{ k.jumlah_prodi || 0 }}</td>
                                <td class="text-center" v-if="userRole !== 'siswa'">
                                    <button class="btn btn-xs btn-outline-primary rounded-2 me-1" @click="openKampusModal(k)" title="Edit"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-xs btn-outline-danger rounded-2" @click="deleteKampus(k.id)" title="Hapus"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <tr v-if="!kampusList || kampusList?.length === 0">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-building fs-1 d-block mb-2 opacity-25"></i>
                                    Belum ada data kampus. Klik "Tambah Kampus" untuk memulai.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Modal Kampus -->
        <div v-if="kampusModal.show" class="modal-overlay-bk" @click.self="kampusModal.show = false">
            <div class="modal-card-bk p-4">
                <h6 class="fw-bold mb-3">{{ kampusModal.isEdit ? 'Edit Kampus' : 'Tambah Kampus Baru' }}</h6>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fs-8 fw-semibold">Nama Kampus</label><input type="text" class="form-control form-control-sm" v-model="kampusModal.form.nama_kampus" placeholder="Universitas Indonesia"></div>
                    <div class="col-6"><label class="form-label fs-8 fw-semibold">Jenis</label>
                        <select class="form-select form-select-sm" v-model="kampusModal.form.jenis">
                            <option>PTN</option><option>PTS</option><option>Kedinasan</option>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label fs-8 fw-semibold">Akreditasi BAN-PT</label><input type="text" class="form-control form-control-sm" v-model="kampusModal.form.akreditasi" placeholder="Unggul / A / B"></div>
                    <div class="col-6"><label class="form-label fs-8 fw-semibold">Kota</label><input type="text" class="form-control form-control-sm" v-model="kampusModal.form.kota" placeholder="Jakarta"></div>
                    <div class="col-6"><label class="form-label fs-8 fw-semibold">Provinsi</label><input type="text" class="form-control form-control-sm" v-model="kampusModal.form.provinsi" placeholder="DKI Jakarta"></div>
                </div>
                <div class="d-flex gap-2 mt-4 justify-content-end">
                    <button class="btn btn-sm btn-secondary rounded-3" @click="kampusModal.show = false">Batal</button>
                    <button class="btn btn-sm btn-primary rounded-3" @click="submitKampus()" :disabled="kampusModal.saving">
                        <span v-if="kampusModal.saving" class="spinner-border spinner-border-sm me-1"></span>
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: MASTER JALUR MASUK
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && in_array('master_jalur', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'master_jalur'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 rounded-top-4 p-4 pb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-door-open me-2 text-primary"></i>Master Jalur Masuk PTN</h5>
                        <p class="text-muted fs-8 mb-0">Daftar jalur seleksi masuk PTN: SNBP, SNBT, Mandiri, SPAN-PTKIN, dan jalur khusus lainnya.</p>
                    </div>
                    <button class="btn btn-sm btn-primary rounded-3" @click="openJalurModal(null)" v-if="userRole !== 'siswa'">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Jalur
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div v-if="loadingJalur" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-8">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">#</th>
                                <th>Nama Jalur</th>
                                <th>Deskripsi</th>
                                <th>Persyaratan</th>
                                <th class="text-center" v-if="userRole !== 'siswa'">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(j, idx) in jalurList" :key="j.id">
                                <td class="px-4 text-muted">{{ idx + 1 }}</td>
                                <td class="fw-semibold"><span class="badge bg-primary rounded-pill me-2">{{ j.nama_jalur }}</span></td>
                                <td class="text-muted">{{ j.deskripsi || '-' }}</td>
                                <td class="text-muted">{{ j.persyaratan || '-' }}</td>
                                <td class="text-center" v-if="userRole !== 'siswa'">
                                    <button class="btn btn-xs btn-outline-primary rounded-2 me-1" @click="openJalurModal(j)"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-xs btn-outline-danger rounded-2" @click="deleteJalur(j.id)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <tr v-if="!jalurList || jalurList?.length === 0">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-door-open fs-1 d-block mb-2 opacity-25"></i>
                                    Belum ada data jalur masuk. Klik "Tambah Jalur" untuk memulai.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Modal Jalur -->
        <div v-if="jalurModal.show" class="modal-overlay-bk" @click.self="jalurModal.show = false">
            <div class="modal-card-bk p-4">
                <h6 class="fw-bold mb-3">{{ jalurModal.isEdit ? 'Edit Jalur' : 'Tambah Jalur Masuk Baru' }}</h6>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fs-8 fw-semibold">Nama Jalur</label><input type="text" class="form-control form-control-sm" v-model="jalurModal.form.nama_jalur" placeholder="SNBP / SNBT / Mandiri"></div>
                    <div class="col-12"><label class="form-label fs-8 fw-semibold">Deskripsi Singkat</label><textarea class="form-control form-control-sm" v-model="jalurModal.form.deskripsi" rows="2" placeholder="Seleksi Nasional Berdasarkan Prestasi..."></textarea></div>
                    <div class="col-12"><label class="form-label fs-8 fw-semibold">Persyaratan Umum</label><textarea class="form-control form-control-sm" v-model="jalurModal.form.persyaratan" rows="2" placeholder="Siswa kelas 12 dengan peringkat top 40%..."></textarea></div>
                </div>
                <div class="d-flex gap-2 mt-4 justify-content-end">
                    <button class="btn btn-sm btn-secondary rounded-3" @click="jalurModal.show = false">Batal</button>
                    <button class="btn btn-sm btn-primary rounded-3" @click="submitJalur()" :disabled="jalurModal.saving">
                        <span v-if="jalurModal.saving" class="spinner-border spinner-border-sm me-1"></span>
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: TRACKING DATA ALUMNI
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && in_array('tracking', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'tracking'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 rounded-top-4 p-4 pb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-people-fill me-2 text-primary"></i>Tracking Data Alumni</h5>
                        <p class="text-muted fs-8 mb-0">Monitor status dan penelusuran alumni pasca kelulusan.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <select class="form-select form-select-sm rounded-3" v-model="alumniFilter.tahun_lulus" @change="loadAlumniTracking()" style="min-width:160px;">
                            <option value="">— Semua Tahun Lulus —</option>
                            <option v-for="y in alumniTahunLulusList" :key="y" :value="y">{{ y }}</option>
                        </select>
                        <select class="form-select form-select-sm rounded-3" v-model="alumniFilter.status" @change="loadAlumniTracking()" style="min-width:160px;">
                            <option value="">— Semua Status —</option>
                            <option value="kuliah">Kuliah</option>
                            <option value="bekerja">Bekerja</option>
                            <option value="wirausaha">Wirausaha</option>
                            <option value="tidak_diketahui">Tidak Diketahui</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- KPI Alumni -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="kpi-card text-center">
                            <div class="fs-3 fw-bold text-primary">{{ alumniKpi.total }}</div>
                            <div class="fs-8 text-muted">Total Alumni</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card text-center">
                            <div class="fs-3 fw-bold text-success">{{ alumniKpi.kuliah }}</div>
                            <div class="fs-8 text-muted">Kuliah</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card text-center">
                            <div class="fs-3 fw-bold text-info">{{ alumniKpi.bekerja }}</div>
                            <div class="fs-8 text-muted">Bekerja</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card text-center">
                            <div class="fs-3 fw-bold text-warning">{{ alumniKpi.tidak_diketahui }}</div>
                            <div class="fs-8 text-muted">Tidak Terdata</div>
                        </div>
                    </div>
                </div>
                <!-- Tabel Alumni -->
                <div v-if="loadingAlumniTracking" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-8">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">#</th>
                                <th>Nama Alumni</th>
                                <th>NISN</th>
                                <th class="text-center">Tahun Lulus</th>
                                <th class="text-center">Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(a, idx) in alumniTrackingList" :key="a.id">
                                <td class="px-4 text-muted">{{ idx + 1 }}</td>
                                <td class="fw-semibold">{{ a.nama_lengkap || a.nama_alumni || '-' }}</td>
                                <td class="font-monospace">{{ a.nisn || '-' }}</td>
                                <td class="text-center">{{ a.tahun_lulus || '-' }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-3"
                                        :class="a.status_tracer === 'kuliah' ? 'bg-success' : a.status_tracer === 'bekerja' ? 'bg-info text-dark' : a.status_tracer === 'wirausaha' ? 'bg-warning text-dark' : 'bg-secondary'">
                                        {{ a.status_tracer || 'Belum Diisi' }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ a.keterangan || '-' }}</td>
                            </tr>
                            <tr v-if="!alumniTrackingList || alumniTrackingList?.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                                    Belum ada data alumni yang terdata.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: RIWAYAT KULIAH
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && in_array('riwayat_kuliah', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'riwayat_kuliah'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 rounded-top-4 p-4 pb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-book me-2 text-primary"></i>Riwayat Kuliah Alumni</h5>
                        <p class="text-muted fs-8 mb-0">Data perkuliahan alumni di PTN/PTS lengkap dengan jalur masuk dan prodi yang dipilih.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary rounded-3" @click="openKuliahModal(null)" v-if="userRole !== 'siswa'">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Riwayat Kuliah
                        </button>
                        <button class="btn btn-sm btn-outline-success rounded-3" @click="exportKuliahExcel()">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div v-if="loadingKuliah" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-8">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">#</th>
                                <th>Nama Alumni</th>
                                <th>NISN</th>
                                <th>Kampus</th>
                                <th>Prodi</th>
                                <th class="text-center">Jalur Masuk</th>
                                <th class="text-center">Tahun Masuk</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" v-if="userRole !== 'siswa'">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(k, idx) in riwayatKuliahList" :key="k.id">
                                <td class="px-4 text-muted">{{ idx + 1 }}</td>
                                <td class="fw-semibold">{{ k.nama_lengkap || k.nama_alumni || '-' }}</td>
                                <td class="font-monospace">{{ k.nisn || '-' }}</td>
                                <td>{{ k.nama_kampus || '-' }}</td>
                                <td>{{ k.nama_prodi || '-' }}</td>
                                <td class="text-center"><span class="badge bg-primary rounded-pill">{{ k.jalur_masuk || '-' }}</span></td>
                                <td class="text-center fw-bold">{{ k.tahun_masuk || '-' }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill" :class="k.status_kuliah === 'aktif' ? 'bg-success' : k.status_kuliah === 'lulus' ? 'bg-info text-dark' : 'bg-secondary'">{{ k.status_kuliah || 'aktif' }}</span>
                                </td>
                                <td class="text-center" v-if="userRole !== 'siswa'">
                                    <button class="btn btn-xs btn-outline-danger rounded-2" @click="deleteKuliah(k.id)" title="Hapus"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <tr v-if="!riwayatKuliahList || riwayatKuliahList?.length === 0">
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-book fs-1 d-block mb-2 opacity-25"></i>
                                    Belum ada data riwayat kuliah alumni.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: RIWAYAT PEKERJAAN
    ════════════════════════════════════════════════════════════ -->
    <?php if(!empty($allowed_bk_tabs) && in_array('riwayat_pekerjaan', $allowed_bk_tabs)): ?>
<div v-show="activeTab === 'riwayat_pekerjaan'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 rounded-top-4 p-4 pb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-briefcase me-2 text-primary"></i>Riwayat Pekerjaan Alumni</h5>
                        <p class="text-muted fs-8 mb-0">Data karir alumni: perusahaan, posisi jabatan, dan jenis instansi tempat bekerja.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary rounded-3" @click="openPekerjaanModal(null)" v-if="userRole !== 'siswa'">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Riwayat Pekerjaan
                        </button>
                        <button class="btn btn-sm btn-outline-success rounded-3" @click="exportPekerjaanExcel()">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div v-if="loadingPekerjaan" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-8">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">#</th>
                                <th>Nama Alumni</th>
                                <th>NISN</th>
                                <th>Perusahaan/Instansi</th>
                                <th>Posisi</th>
                                <th class="text-center">Jenis Instansi</th>
                                <th class="text-center">Tahun Mulai</th>
                                <th class="text-center" v-if="userRole !== 'siswa'">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(p, idx) in riwayatPekerjaanList" :key="p.id">
                                <td class="px-4 text-muted">{{ idx + 1 }}</td>
                                <td class="fw-semibold">{{ p.nama_lengkap || p.nama_alumni || '-' }}</td>
                                <td class="font-monospace">{{ p.nisn || '-' }}</td>
                                <td>{{ p.nama_perusahaan || '-' }}</td>
                                <td>{{ p.posisi || '-' }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill" :class="p.jenis_instansi === 'BUMN' ? 'bg-primary' : p.jenis_instansi === 'PNS' ? 'bg-success' : p.jenis_instansi === 'Swasta' ? 'bg-info text-dark' : 'bg-secondary'">{{ p.jenis_instansi || '-' }}</span>
                                </td>
                                <td class="text-center fw-bold">{{ p.tahun_mulai || '-' }}</td>
                                <td class="text-center" v-if="userRole !== 'siswa'">
                                    <button class="btn btn-xs btn-outline-danger rounded-2" @click="deletePekerjaan(p.id)" title="Hapus"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <tr v-if="!riwayatPekerjaanList || riwayatPekerjaanList?.length === 0">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-briefcase fs-1 d-block mb-2 opacity-25"></i>
                                    Belum ada data riwayat pekerjaan alumni.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         ROOT MODALS: IMPOR & EKSPOR KEHADIRAN SISWA
    ════════════════════════════════════════════════════════════ -->
    <!-- POPUP MODAL 1: IMPOR KEHADIRAN EXCEL -->
    <div v-if="modalImportKehadiran.show" class="modal-backdrop-custom d-flex align-items-center justify-content-center" style="position:fixed; top:0; left:0; right:0; bottom:0; width:100vw; height:100vh; background:rgba(15,23,42,0.7); z-index:99999; overflow-y:auto;">
        <div class="modal-dialog modal-dialog-centered modal-lg my-auto" style="width: 92%; max-width: 620px;">
            <div class="modal-content border-0 rounded-4 shadow-lg bg-white overflow-hidden">
                <div class="modal-header bg-success text-white px-4 py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-arrow-up-fill fs-5"></i>
                        <h6 class="modal-title fw-bold mb-0">Impor Data Kehadiran Siswa (.xlsx)</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="closeModalImportKehadiran"></button>
                </div>
                <div class="modal-body p-4 fs-8">
                    <!-- Guidance Alert -->
                    <div class="alert alert-info border-0 rounded-3 mb-3 d-flex gap-3 align-items-start">
                        <i class="bi bi-info-circle-fill fs-5 text-info mt-1"></i>
                        <div>
                            <div class="fw-bold mb-1">Panduan Impor Kehadiran:</div>
                            <div class="text-muted fs-8">
                                Unggah berkas Excel (.xlsx) yang sesuai dengan format template resmi. Pastikan kolom <strong>UUID Sekolah, NISN, Sakit, Izin, dan Alfa</strong> tidak diubah judul kolomnya.
                            </div>
                            <button type="button" @click="unduhTemplateKehadiran" class="btn btn-xs btn-outline-primary rounded-pill mt-2 px-3 py-1 fw-bold">
                                <i class="bi bi-download me-1"></i> Unduh Template Kelas Terpilih
                            </button>
                        </div>
                    </div>

                    <!-- Target Target Filter Info -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-dark">Tahun Ajaran <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm rounded-3" v-model="modalImportKehadiran.tahun_ajaran_id">
                                <option value="">-- Pilih TA --</option>
                                <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-dark">Semester <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm rounded-3" v-model="modalImportKehadiran.semester">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-dark">Kelas Target <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm rounded-3" v-model="modalImportKehadiran.kelas_id">
                                <option value="">-- Pilih Kelas --</option>
                                <option v-for="k in listKelasKehadiran" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- File Input -->
                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1 text-dark">Pilih Berkas Excel (.xlsx) <span class="text-danger">*</span></label>
                        <input type="file" 
                               id="modal-file-import-kehadiran" 
                               @change="handleModalFileImportChange" 
                               class="form-control form-control-sm rounded-3" 
                               accept=".xlsx">
                        <div v-if="modalImportKehadiran.file" class="mt-2 text-success fw-semibold fs-9 d-flex align-items-center gap-1">
                            <i class="bi bi-file-earmark-check-fill"></i>
                            Berkas terpilih: {{ modalImportKehadiran.file.name }} ({{ (modalImportKehadiran.file.size / 1024).toFixed(1) }} KB)
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" @click="closeModalImportKehadiran">Batal</button>
                    <button type="button" class="btn btn-sm btn-success text-white fw-bold rounded-3 px-4 shadow-sm" @click="submitModalImportKehadiran" :disabled="modalImportKehadiran.loading">
                        <span v-if="modalImportKehadiran.loading" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-upload me-1"></i> Mulai Impor Kehadiran
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- POPUP MODAL 2: EKSPOR KEHADIRAN EXCEL -->
    <div v-if="modalExportKehadiran.show" class="modal-backdrop-custom d-flex align-items-center justify-content-center" style="position:fixed; top:0; left:0; right:0; bottom:0; width:100vw; height:100vh; background:rgba(15,23,42,0.7); z-index:99999; overflow-y:auto;">
        <div class="modal-dialog modal-dialog-centered modal-md my-auto" style="width: 92%; max-width: 540px;">
            <div class="modal-content border-0 rounded-4 shadow-lg bg-white overflow-hidden">
                <div class="modal-header bg-primary text-white px-4 py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-arrow-down-fill fs-5"></i>
                        <h6 class="modal-title fw-bold mb-0">Ekspor Data Kehadiran (.xlsx)</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="closeModalExportKehadiran"></button>
                </div>
                <div class="modal-body p-4 fs-8">
                    <p class="text-muted mb-3">Pilih kriteria data kehadiran yang ingin diekspor ke format berkas Excel (.xlsx). Berkas yang diunduh dapat diedit dan diimpor kembali.</p>

                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1 text-dark">Tahun Ajaran <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm rounded-3" v-model="modalExportKehadiran.tahun_ajaran_id">
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1 text-dark">Semester <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm rounded-3" v-model="modalExportKehadiran.semester">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1 text-dark">Kelas <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm rounded-3" v-model="modalExportKehadiran.kelas_id">
                            <option value="">-- Pilih Kelas --</option>
                            <option v-for="k in listKelasKehadiran" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" @click="closeModalExportKehadiran">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary text-white fw-bold rounded-3 px-4 shadow-sm" @click="submitModalExportKehadiran">
                        <i class="bi bi-download me-1"></i> Unduh Berkas Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

</div><!-- End #bkApp -->

<script>
{
const { ref, computed, onMounted, nextTick } = Vue;

// Inject PHP variables safely
const _tenantId = '';
const _userRole = '';
const _userId = '';
const _baseUrl   = '<?php echo $baseUrl ?? rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'); ?>';
const _tahunAjaranList = [];

window.VueAppRegistry.register('#bkApp', {
    setup() {
        const { ref, computed, watch, onMounted } = Vue;
        const userRole       = _userRole;
        const baseUrl        = _baseUrl;

        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (t) => {
                t.addEventListener('mouseenter', Swal.stopTimer)
                t.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // ─── State ──────────────────────────────────────────
        const activeTab      = ref('<?= $defaultMainTab ?>');
        const activeSubTab   = ref('<?= $defaultSubTab ?>');
        const currentTenantId= ref(_tenantId);

        // ─── Kehadiran State ────────────────────────────────
        const loadingKehadiran = ref(false);
        const savingKehadiran = ref(false);
        const importingKehadiran = ref(false);
        const filterKehadiran = ref({ tahun_ajaran_id: '', semester: 'Ganjil', kelas_id: '' });
        const kehadiranData = ref([]);
        const originalKehadiranData = ref([]);
        const listKelasKehadiran = ref([]);
        const fileImportKehadiran = ref(null);
        const tahunAjaranList = ref(_tahunAjaranList || []);
        const isKehadiranLocked = ref(false);

        const modalImportKehadiran = ref({
            show: false,
            loading: false,
            file: null,
            tahun_ajaran_id: '',
            semester: 'Ganjil',
            kelas_id: ''
        });

        const modalExportKehadiran = ref({
            show: false,
            tahun_ajaran_id: '',
            semester: 'Ganjil',
            kelas_id: ''
        });

        // ─── Pelanggaran & Poin State ────────────────────────
        const loadingPelanggaranDashboard = ref(false);
        const pelanggaranKpi = ref({ wali_kelas: 0, sp1_bk: 0, sp2_skorsing: 0, sp3_do: 0, total_siswa_melanggar: 0 });
        const pelanggaranTopStudents = ref([]);
        let pelanggaranChartInstance = null;

        // Master Rules CRUD
        const loadingPelanggaranMaster = ref(false);
        const pelanggaranMasterList = ref([]);
        const masterModal = ref({
            show: false,
            isEdit: false,
            saving: false,
            form: { id: '', kategori: 'Ringan', nama_pelanggaran: '', bobot_poin: 5 }
        });

        // Catatan Pelanggaran
        const submittingPelanggaran = ref(false);
        const pelanggaranSearchSiswa = ref('');
        const pelanggaranSiswaOptions = ref([]);
        const selectedPelanggaranSiswa = ref({});
        const loadingSearchPelanggaranSiswa = ref(false);
        const showPelanggaranSiswaDropdown = ref(false);
        const formInputPelanggaran = ref({
            id: '',
            siswa_id: '',
            pelanggaran_id: '',
            tanggal_kejadian: new Date().toISOString().split('T')[0],
            catatan_keterangan: '',
            foto_bukti: null,
            existing_foto: null
        });
        const loadingPelanggaranCatatan = ref(false);
        const pelanggaranCatatanList = ref([]);
        const catatanListSearch = ref('');
        const fotoModal = ref({ show: false, src: '' });

        // Buku Sanksi & Detail Modal
        const loadingPelanggaranSanksi = ref(false);
        const pelanggaranSanksiList = ref([]);
        const sanksiSearch = ref('');
        const sanksiStatus = ref('');
        const sanksiDetailModal = ref({
            show: false,
            student: {},
            total_poin: 0,
            violations: [],
            followUps: []
        });
        const statusPengajuanTuList = ref([]);
        const modalPengajuanTu = ref({
            show: false,
            siswa_nama: '',
            siswa_kelas: '',
            total_poin: 0,
            submitting: false,
            form: {
                id_siswa: '',
                jenis_panggilan: 'Surat Panggilan Orang Tua I',
                alasan_pemanggilan: '',
                rencana_tanggal_menghadap: new Date().toISOString().split('T')[0],
                rencana_jam_menghadap: '09:00',
                ruangan: 'Ruang Konseling BK'
            }
        });
        const formTindakLanjut = ref({
            tanggal_tindakan: new Date().toISOString().split('T')[0],
            jenis_tindakan: 'Konseling BK',
            keterangan_tindakan: ''
        });
        const submittingTindakLanjut = ref(false);
        const siswaHoverPelanggaran = ref(null);
        let debounceTimerPelanggaran = null;

        // Dashboard
        const loadingDashboard = ref(false);
        const kpi = ref({
            total_siswa_aktif: 0,
            kasus_bulan_ini: 0,
            kasus_terbuka: 0,
            total_alumni: 0,
            distribusi_kasus: [],
            data: {
                total_siswa_aktif: 0,
                kasus_bulan_ini: 0,
                kasus_terbuka: 0,
                total_alumni: 0
            }
        });
        const pieColors = ['#7c3aed','#2563eb','#10b981','#f59e0b','#ef4444','#64748b'];

        // Prestasi Filter State (placed early for computed dependencies)
        const activeYearsList = ref(_tahunAjaranList || []);
        const prestasiList = ref([]);
        const filterPrestasiTahunAjaran = ref('');
        const filterPrestasiTingkat     = ref('');
        const filterPrestasiSearch      = ref('');

        // Tracer
        const loadingTracer = ref(false);
        const tracerData    = ref({ kuliah: 0, pekerjaan: 0, total: 0 });



        // Jurnal / Kasus
        const loadingKasus      = ref(false);
        const loadingKasusList  = ref(false);
        const kasusList         = ref([]);
        const kasusListSearch   = ref(''); // Filter lokal untuk tabel riwayat
        const kasusStatusFilter = ref('aktif'); // Default: 'aktif' (Otomatis tampilkan Terbuka & Dalam Proses)
        const alertJurnal       = ref({ msg: '', type: 'success' });
        const kasusSearchSiswa  = ref('');
        const siswaOptions      = ref([]);
        const selectedSiswaInfo = ref({});
        const loadingSearchSiswa= ref(false);
        const showSiswaDropdown = ref(false);
        const siswaHover        = ref(null);
        const kelasList         = ref([]);
        const filterKelasId     = ref('');
        const today = new Date().toISOString().split('T')[0];
        let debounceTimer = null;

        // Computed: filter tabel riwayat kasus secara lokal (Default otomatis: Terbuka & Proses)
        const filteredKasusList = computed(() => {
            let list = kasusList.value || [];

            // Filter berdasarkan status kasus (Default: 'aktif' = Terbuka & Dalam Proses)
            if (kasusStatusFilter.value === 'aktif') {
                list = list.filter(k => k.status_kasus === 'Terbuka' || k.status_kasus === 'Proses');
            } else if (kasusStatusFilter.value === 'Terbuka') {
                list = list.filter(k => k.status_kasus === 'Terbuka');
            } else if (kasusStatusFilter.value === 'Proses') {
                list = list.filter(k => k.status_kasus === 'Proses');
            } else if (kasusStatusFilter.value === 'Selesai') {
                list = list.filter(k => k.status_kasus === 'Selesai');
            }

            if (!kasusListSearch.value.trim()) return list;
            const q = kasusListSearch.value.toLowerCase();
            return list.filter(k =>
                (k.nama_siswa         || '').toLowerCase().includes(q) ||
                (k.nisn               || '').includes(q) ||
                (k.nis                || '').includes(q) ||
                (k.nama_kelas         || '').toLowerCase().includes(q) ||
                (k.kelas_saat_kejadian|| '').toLowerCase().includes(q) ||
                (k.jenis_kasus        || '').toLowerCase().includes(q) ||
                (k.status_kasus       || '').toLowerCase().includes(q)
            );
        });
        // ─── Pelanggaran Computed ───────────────────────────
        const masterRulesFiltered = computed(() => {
            return pelanggaranMasterList.value;
        });

        const filteredCatatanPelanggaran = computed(() => {
            if (!catatanListSearch.value.trim()) return pelanggaranCatatanList.value;
            const q = catatanListSearch.value.toLowerCase();
            return pelanggaranCatatanList.value.filter(c =>
                (c.nama_siswa || '').toLowerCase().includes(q) ||
                (c.nisn || '').includes(q) ||
                (c.nama_kelas || '').toLowerCase().includes(q) ||
                (c.nama_pelanggaran || '').toLowerCase().includes(q) ||
                (c.kategori || '').toLowerCase().includes(q) ||
                (c.catatan_keterangan || '').toLowerCase().includes(q)
            );
        });

        const filteredSanksiBuku = computed(() => {
            let list = pelanggaranSanksiList.value;
            if (sanksiStatus.value) {
                const fs = sanksiStatus.value;
                list = list.filter(item => {
                    if (fs === 'Aman') return item.total_poin < 25;
                    if (fs === 'Wali Kelas') return item.total_poin >= 25 && item.total_poin < 50;
                    if (fs === 'SP 1') return item.total_poin >= 50 && item.total_poin < 75;
                    if (fs === 'SP 2') return item.total_poin >= 75 && item.total_poin < 100;
                    if (fs === 'SP 3') return item.total_poin >= 100;
                    return true;
                });
            }
            if (sanksiSearch.value.trim()) {
                const q = sanksiSearch.value.toLowerCase();
                list = list.filter(item =>
                    (item.nama_lengkap || '').toLowerCase().includes(q) ||
                    (item.nisn || '').includes(q) ||
                    (item.nama_kelas || '').toLowerCase().includes(q)
                );
            }
            return list;
        });

        const filteredPrestasiList = computed(() => {
            let list = [...(prestasiList.value || [])];

            // 1. Filter Tahun Ajaran
            if (filterPrestasiTahunAjaran.value) {
                list = list.filter(p => 
                    String(p.tahun_ajaran_id) === String(filterPrestasiTahunAjaran.value) ||
                    String(p.tahun_ajaran || '') === String(filterPrestasiTahunAjaran.value)
                );
            }

            // 2. Filter Tingkat Kejuaraan
            if (filterPrestasiTingkat.value) {
                list = list.filter(p => p.tingkat_kejuaraan === filterPrestasiTingkat.value);
            }

            // 3. Filter Pencarian Teks
            if (filterPrestasiSearch.value.trim()) {
                const q = filterPrestasiSearch.value.toLowerCase();
                list = list.filter(p =>
                    (p.nama_lomba         || '').toLowerCase().includes(q) ||
                    (p.bidang_lomba       || '').toLowerCase().includes(q) ||
                    (p.juara              || '').toLowerCase().includes(q) ||
                    (p.tingkat_kejuaraan  || '').toLowerCase().includes(q) ||
                    (p.nomor_sertifikat   || '').toLowerCase().includes(q) ||
                    (p.penyelenggara      || '').toLowerCase().includes(q) ||
                    (p.siswa_list || []).some(s => 
                        (s.nama_lengkap   || '').toLowerCase().includes(q) ||
                        (s.nisn           || '').includes(q) ||
                        (s.nama_kelas     || '').toLowerCase().includes(q)
                    )
                );
            }

            // 4. Tampilkan yang Terbaru (Sort Descending)
            list.sort((a, b) => {
                const dateA = a.tanggal_lomba || a.created_at || a.id || '';
                const dateB = b.tanggal_lomba || b.created_at || b.id || '';
                if (dateA < dateB) return 1;
                if (dateA > dateB) return -1;
                return 0;
            });

            return list;
        });

        // ─── Pagination States & Computed ───────────────────
        const currentPageKasus = ref(1);
        const perPageKasus = ref(10);
        const totalKasusPages = computed(() => Math.ceil((filteredKasusList.value?.length || 0) / perPageKasus.value) || 1);
        const paginatedKasusList = computed(() => {
            const start = (currentPageKasus.value - 1) * perPageKasus.value;
            return (filteredKasusList.value || []).slice(start, start + perPageKasus.value);
        });
        watch([kasusListSearch, kasusStatusFilter, filteredKasusList], () => { currentPageKasus.value = 1; });

        const currentPagePelanggaran = ref(1);
        const perPagePelanggaran = ref(10);
        const totalPelanggaranPages = computed(() => Math.ceil((filteredCatatanPelanggaran.value?.length || 0) / perPagePelanggaran.value) || 1);
        const paginatedCatatanPelanggaran = computed(() => {
            const start = (currentPagePelanggaran.value - 1) * perPagePelanggaran.value;
            return (filteredCatatanPelanggaran.value || []).slice(start, start + perPagePelanggaran.value);
        });
        watch([catatanListSearch, filteredCatatanPelanggaran], () => { currentPagePelanggaran.value = 1; });

        const currentPageSanksi = ref(1);
        const perPageSanksi = ref(10);
        const totalSanksiPages = computed(() => Math.ceil((filteredSanksiBuku.value?.length || 0) / perPageSanksi.value) || 1);
        const paginatedSanksiBuku = computed(() => {
            const start = (currentPageSanksi.value - 1) * perPageSanksi.value;
            return (filteredSanksiBuku.value || []).slice(start, start + perPageSanksi.value);
        });
        watch([sanksiStatus, filteredSanksiBuku], () => { currentPageSanksi.value = 1; });

        const currentPageMaster = ref(1);
        const perPageMaster = ref(10);
        const totalMasterPages = computed(() => Math.ceil((pelanggaranMasterList.value?.length || 0) / perPageMaster.value) || 1);
        const paginatedPelanggaranMasterList = computed(() => {
            const start = (currentPageMaster.value - 1) * perPageMaster.value;
            return (pelanggaranMasterList.value || []).slice(start, start + perPageMaster.value);
        });
        watch([pelanggaranMasterList], () => { currentPageMaster.value = 1; });

        const currentPagePrestasi = ref(1);
        const perPagePrestasi = ref(10);
        const totalPrestasiPages = computed(() => Math.ceil((filteredPrestasiList.value?.length || 0) / perPagePrestasi.value) || 1);
        const paginatedPrestasiList = computed(() => {
            const start = (currentPagePrestasi.value - 1) * perPagePrestasi.value;
            return (filteredPrestasiList.value || []).slice(start, start + perPagePrestasi.value);
        });
        watch([filterPrestasiTahunAjaran, filterPrestasiTingkat, filterPrestasiSearch, filteredPrestasiList], () => { currentPagePrestasi.value = 1; });

        const formKasus = ref({
            id_siswa: '',
            tanggal_konseling: today,
            jenis_kasus: '',
            catatan: '',
            tindak_lanjut: '',
            status_kasus: 'Terbuka',
            is_rahasia: 1
        });

        // ─── Tab Switch + Lazy Load ──────────────────────────
        const tabsLoaded = ref({ dashboard: false, penjurusan: false, tracer: false, jurnal: false, prestasi: false, kehadiran: false, pelanggaran: false, beasiswa: false, kesiapan: false, simulasi: false, master_kampus: false, master_jalur: false, tracking: false, riwayat_kuliah: false, riwayat_pekerjaan: false });

        function switchTab(tab) {
            activeTab.value = tab;
            if (!tabsLoaded.value[tab]) {
                tabsLoaded.value[tab] = true;
                if (tab === 'dashboard')          loadDashboard();
                if (tab === 'penjurusan')         loadPenjurusan();
                if (tab === 'tracer')             loadTracer();
                if (tab === 'jurnal')             { loadKasus(); loadKelasList(); }
                if (tab === 'prestasi')           { loadPrestasi(); }
                if (tab === 'kehadiran')          { loadKelasKehadiran(); }
                if (tab === 'pelanggaran')        { switchSubTab(activeSubTab.value); }
                if (tab === 'beasiswa')           { loadAllBeasiswa(); }
                // PDSS & Akademik
                if (tab === 'kesiapan')           { loadKesiapan(); }
                if (tab === 'simulasi')           { loadSimulasiSettings(); loadSimulasi(); }
                if (tab === 'master_kampus')      { loadKampus(); }
                if (tab === 'master_jalur')       { loadJalur(); }
                // Alumni
                if (tab === 'tracking')           { loadAlumniTracking(); }
                if (tab === 'riwayat_kuliah')     { loadRiwayatKuliah(); }
                if (tab === 'riwayat_pekerjaan')  { loadRiwayatPekerjaan(); }
            } else {
                if (tab === 'pelanggaran') { switchSubTab(activeSubTab.value); }
            }
        }

        // ─── API: Dashboard ──────────────────────────────────
        async function loadDashboard() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingDashboard.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/dashboard`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data && res.data.success) {
                    const payload = res.data.data || res.data;
                    Object.assign(kpi.value, payload);
                    if (!kpi.value.data) kpi.value.data = payload;
                }
            } catch (e) { console.error('BK Dashboard load error', e); }
            finally { loadingDashboard.value = false; }
        }

        // ─── API: Penjurusan ─────────────────────────────────
        const loadingPenjurusan  = ref(false);
        const penjurusanData     = ref([]);
        const penjurusanSummary  = ref([]);
        const jurusanList        = ref([]);
        const alertPenjurusan    = ref({ msg: '', type: 'success' });
        const loadingOverride    = ref(false);
        const filterPenjurusan   = ref({ search: '', status: '', jurusan_id: '' });
        const overrideModal      = ref({
            show: false, siswa: {}, id_jurusan_baru: '', catatan_bk: ''
        });

        const filteredPenjurusan = computed(() => {
            const q = filterPenjurusan.value.search.toLowerCase();
            if (!q) return penjurusanData.value;
            return penjurusanData.value.filter(p =>
                (p.nama_siswa || '').toLowerCase().includes(q) ||
                (p.nisn || '').includes(q)
            );
        });

        function statusStyle(status) {
            const map = {
                'Diajukan':    'background:#fef3c7;color:#92400e;',
                'Diverifikasi':'background:#d1fae5;color:#065f46;',
                'Ditolak':     'background:#fee2e2;color:#991b1b;',
                'Override_BK': 'background:#ede9fe;color:#5b21b6;',
            };
            return map[status] || 'background:#f1f5f9;color:#475569;';
        }

        async function loadPenjurusan() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingPenjurusan.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/penjurusan`;
                const params = [];
                if (currentTenantId.value)               params.push(`tenant_id=${currentTenantId.value}`);
                if (filterPenjurusan.value.status)        params.push(`status=${encodeURIComponent(filterPenjurusan.value.status)}`);
                if (filterPenjurusan.value.jurusan_id)    params.push(`jurusan_id=${filterPenjurusan.value.jurusan_id}`);
                if (filterPenjurusan.value.search)        params.push(`search=${encodeURIComponent(filterPenjurusan.value.search)}`);
                if (params.length) url += '?' + params.join('&');
                const res = await axios.get(url);
                if (res.data.success) {
                    penjurusanData.value    = res.data.data    || [];
                    penjurusanSummary.value = res.data.summary || [];
                    jurusanList.value       = res.data.jurusan_list || [];
                }
            } catch (e) { console.error('BK Penjurusan load error', e); }
            finally { loadingPenjurusan.value = false; }
        }

        async function doVerifikasi(p, aksi) {
            if (!confirm(`${aksi} pilihan jurusan ${p.nama_siswa}?`)) return;
            try {
                const res = await axios.post(`${_baseUrl}/api/v1/bk/penjurusan/verifikasi`,
                    { id_pilihan: p.id, aksi, tenant_id: currentTenantId.value },
                    { headers: { 'Content-Type': 'application/json' } }
                );
                alertPenjurusan.value = {
                    msg: res.data.success ? '✅ ' + res.data.message : '❌ ' + (res.data.error || 'Gagal.'),
                    type: res.data.success ? 'success' : 'danger'
                };
                if (res.data.success) loadPenjurusan();
            } catch (err) {
                alertPenjurusan.value = { msg: '❌ ' + ((err.response && err.response.data && err.response.data.error) || 'Koneksi gagal.'), type: 'danger' };
            }
        }

        function openOverride(p) {
            overrideModal.value = { show: true, siswa: p, id_jurusan_baru: '', catatan_bk: '' };
        }

        async function submitOverride() {
            if (!overrideModal.value.id_jurusan_baru) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Pilih jurusan tujuan terlebih dahulu.',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }
            if (!overrideModal.value.catatan_bk.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Alasan override wajib diisi untuk keperluan audit.',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }
            loadingOverride.value = true;
            try {
                const res = await axios.post(`${_baseUrl}/api/v1/bk/penjurusan/override`, {
                    id_pilihan:     overrideModal.value.siswa.id,
                    id_jurusan_baru:overrideModal.value.id_jurusan_baru,
                    catatan_bk:     overrideModal.value.catatan_bk,
                    tenant_id:      currentTenantId.value
                }, { headers: { 'Content-Type': 'application/json' } });

                overrideModal.value.show = false;
                alertPenjurusan.value = {
                    msg: res.data.success ? '✅ ' + res.data.message : '❌ ' + (res.data.error || 'Override gagal.'),
                    type: res.data.success ? 'success' : 'danger'
                };
                if (res.data.success) loadPenjurusan();
            } catch (err) {
                alertPenjurusan.value = { msg: '❌ ' + ((err.response && err.response.data && err.response.data.error) || 'Koneksi gagal.'), type: 'danger' };
            } finally {
                loadingOverride.value = false;
            }
        }

        async function doToggleKunci(p) {
            const newKunci = p.dikunci == 1 ? 0 : 1;
            const label    = newKunci ? 'mengunci' : 'membuka kunci';
            if (!confirm(`Konfirmasi ${label} pilihan penjurusan ${p.nama_siswa}?`)) return;
            try {
                const res = await axios.post(`${_baseUrl}/api/v1/bk/penjurusan/kunci`,
                    { id_pilihan: p.id, dikunci: newKunci, tenant_id: currentTenantId.value },
                    { headers: { 'Content-Type': 'application/json' } }
                );
                alertPenjurusan.value = {
                    msg: res.data.success ? '✅ ' + res.data.message : '❌ ' + (res.data.error || 'Gagal.'),
                    type: res.data.success ? 'success' : 'danger'
                };
                if (res.data.success) loadPenjurusan();
            } catch (err) {
                alertPenjurusan.value = { msg: '❌ ' + ((err.response && err.response.data && err.response.data.error) || 'Koneksi gagal.'), type: 'danger' };
            }
        }

        // ─── API: Tracer ─────────────────────────────────────
        async function loadTracer() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingTracer.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/tracer`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) tracerData.value = res.data;
            } catch (e) { console.error('BK Tracer load error', e); }
            finally { loadingTracer.value = false; }
        }



        // ─── API: Kasus List ─────────────────────────────────
        async function loadKasus() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingKasusList.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/kasus`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) kasusList.value = res.data.data || [];
            } catch (e) { console.error('BK Kasus load error', e); }
            finally { loadingKasusList.value = false; }
        }

        // ─── Kelas List untuk Filter Rekam Kasus ────────────────
        async function loadKelasList() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            try {
                let url = `${_baseUrl}/api/v1/bk/kelas`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) kelasList.value = res.data.data || [];
            } catch (e) { console.error('loadKelasList error', e); }
        }

        // ─── Pencarian Siswa (Debounce) + Filter Kelas ───────────
        function searchSiswaDebounce() {
            clearTimeout(debounceTimer);
            const q = kasusSearchSiswa.value.trim();
            if (q.length < 1 && !filterKelasId.value) {
                siswaOptions.value  = [];
                showSiswaDropdown.value = false;
                return;
            }
            debounceTimer = setTimeout(async () => {
                loadingSearchSiswa.value = true;
                showSiswaDropdown.value  = true;
                try {
                    const params = new URLSearchParams();
                    if (q)                      params.set('q', q);
                    if (filterKelasId.value)    params.set('kelas_id', filterKelasId.value);
                    if (currentTenantId.value)  params.set('tenant_id', currentTenantId.value);
                    params.set('limit', '12');
                    const res = await axios.get(`${_baseUrl}/api/v1/bk/siswa?${params}`);
                    siswaOptions.value = res.data.data || [];
                } catch (e) { siswaOptions.value = []; }
                finally { loadingSearchSiswa.value = false; }
            }, 280);
        }

        function onFilterKelasChange() {
            // Saat filter kelas berubah, refresh hasil pencarian jika ada query
            // atau tampilkan semua siswa kelas yang dipilih
            searchSiswaDebounce();
        }

        function onSearchFocus() {
            if (siswaOptions.value.length > 0) showSiswaDropdown.value = true;
            // Jika kelas dipilih tapi belum ada ketikan, tampilkan siswa di kelas itu
            if (!kasusSearchSiswa.value && filterKelasId.value) {
                searchSiswaDebounce();
            }
        }

        function hideDropdownDelay() {
            // Delay agar mousedown pada item sempat terpicu sebelum blur
            setTimeout(() => { showSiswaDropdown.value = false; }, 200);
        }

        function selectSiswa(s) {
            formKasus.value.id_siswa = s.id;
            selectedSiswaInfo.value  = s;
            kasusSearchSiswa.value   = s.nama_lengkap;
            siswaOptions.value       = [];
            showSiswaDropdown.value  = false;
        }

        function clearSiswa() {
            formKasus.value.id_siswa = '';
            selectedSiswaInfo.value  = {};
            kasusSearchSiswa.value   = '';
            siswaOptions.value       = [];
            showSiswaDropdown.value  = false;
        }

        function openTambahKasusModal() {
            clearSiswa();
            formKasus.value = {
                id_siswa: '',
                tanggal_konseling: today,
                jenis_kasus: '',
                catatan: '',
                tindak_lanjut: '',
                status_kasus: 'Terbuka',
                is_rahasia: 1
            };
            const modalEl = document.getElementById('modalFormKasus');
            if (modalEl && window.bootstrap) {
                if (modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
                const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }

        function openEditKasus(k) {
            const sId = k.id_siswa || k.siswa_id || k.id;
            selectedSiswaInfo.value = {
                id: sId,
                nama_lengkap: k.nama_siswa || k.nama_lengkap || 'Siswa',
                nisn: k.nisn || '-',
                nama_kelas: k.kelas_saat_kejadian || k.nama_kelas || '-'
            };
            formKasus.value = {
                id: k.id,
                id_siswa: sId,
                tanggal_konseling: k.tanggal_konseling ? String(k.tanggal_konseling).substring(0, 10) : today,
                jenis_kasus: k.jenis_kasus || '',
                catatan: k.catatan || k.catatan_keterangan || '',
                tindak_lanjut: k.tindak_lanjut || '',
                status_kasus: k.status_kasus || 'Terbuka',
                is_rahasia: (k.is_rahasia == 1 || k.is_rahasia === true) ? 1 : 0
            };
            const modalEl = document.getElementById('modalFormKasus');
            if (modalEl && window.bootstrap) {
                if (modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
                const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }

        function closeKasusModal() {
            const modalEl = document.getElementById('modalFormKasus');
            if (modalEl && window.bootstrap) {
                const modal = window.bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        }

        // ─── Submit Kasus ────────────────────────────────────
        async function submitKasus() {
            if (!formKasus.value.id_siswa)      { Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih siswa terlebih dahulu.', confirmButtonColor: 'var(--bk-primary)' }); return; }
            if (!formKasus.value.jenis_kasus)   { Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Jenis kasus wajib dipilih.', confirmButtonColor: 'var(--bk-primary)' }); return; }
            if (!formKasus.value.catatan.trim()){ Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Catatan konseling wajib diisi.', confirmButtonColor: 'var(--bk-primary)' }); return; }

            loadingKasus.value  = true;
            alertJurnal.value   = { msg: '', type: 'success' };
            try {
                const payload = { ...formKasus.value };
                if (currentTenantId.value) payload.tenant_id = currentTenantId.value;

                const res = await axios.post(`${_baseUrl}/api/v1/bk/kasus`, payload, {
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.data.success) {
                    const msgTxt = res.data.message || res.data.msg || 'Catatan BK berhasil disimpan.';
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: msgTxt,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    formKasus.value = { id_siswa: '', tanggal_konseling: today, jenis_kasus: '', catatan: '', tindak_lanjut: '', status_kasus: 'Terbuka', is_rahasia: 1 };
                    selectedSiswaInfo.value = {};
                    kasusSearchSiswa.value  = '';
                    closeKasusModal();
                    loadKasus();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: res.data.error || res.data.msg || 'Gagal menyimpan catatan.',
                        confirmButtonColor: 'var(--bk-primary)'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan',
                    text: (err.response && err.response.data && (err.response.data.error || err.response.data.msg)) || 'Koneksi ke server gagal.',
                    confirmButtonColor: 'var(--bk-primary)'
                });
            } finally {
                loadingKasus.value = false;
            }
        }

        // ─── Status & Log Penanganan Kasus ───────────────────
        function canEditKasus(k) {
            if (_userRole === 'super_admin' || _userRole === 'operator_sekolah') {
                return true;
            }
            if (_userRole === 'guru_bk') {
                if (k.is_rahasia == 0) return true;
                return k.id_guru_bk === _userId;
            }
            return false;
        }

        async function openChangeStatus(k) {
            const { value: newStatus } = await Swal.fire({
                title: 'Ubah Status Kasus',
                text: `Siswa: ${k.nama_siswa}`,
                input: 'select',
                inputOptions: {
                    'Terbuka': 'Terbuka',
                    'Proses': 'Proses (Dalam Proses)',
                    'Selesai': 'Selesai'
                },
                inputValue: k.status_kasus,
                showCancelButton: true,
                confirmButtonColor: 'var(--bk-primary)',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    return new Promise((resolve) => {
                        if (value) {
                            resolve();
                        } else {
                            resolve('Pilih status kasus!');
                        }
                    });
                }
            });

            if (newStatus && newStatus !== k.status_kasus) {
                try {
                    const res = await axios.post(`${_baseUrl}/api/v1/bk/kasus/update-status`, {
                        id_kasus: k.id,
                        status_kasus: newStatus,
                        tenant_id: currentTenantId.value
                    }, { headers: { 'Content-Type': 'application/json' } });

                    if (res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sukses',
                            text: res.data.message,
                            confirmButtonColor: 'var(--bk-primary)'
                        });
                        loadKasus();
                        loadDashboard();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: res.data.error || 'Terjadi kesalahan.',
                            confirmButtonColor: 'var(--bk-primary)'
                        });
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Koneksi Gagal',
                        text: (err.response && err.response.data && err.response.data.error) || 'Gagal menghubungi server.',
                        confirmButtonColor: 'var(--bk-primary)'
                    });
                }
            }
        }

        async function openLogs(k) {
            Swal.fire({
                title: 'Memuat Log...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                let url = `${_baseUrl}/api/v1/bk/kasus/logs?id_kasus=${k.id}`;
                if (currentTenantId.value) url += `&tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) {
                    const logs = res.data.data || [];
                    if (logs.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Riwayat Log',
                            text: 'Belum ada log aktivitas untuk kasus ini.',
                            confirmButtonColor: 'var(--bk-primary)'
                        });
                        return;
                    }

                    let htmlContent = `
                        <div class="text-start mt-2 px-1" style="max-height: 380px; overflow-y: auto; font-family: sans-serif;">
                            <div class="position-relative ps-4 border-start border-2" style="border-color: #ede9fe !important; margin-left: 10px;">
                    `;

                    logs.forEach((log) => {
                        const dateObj = new Date(log.created_at);
                        const formattedDate = dateObj.toLocaleDateString('id-ID', {
                            day: 'numeric', month: 'short', year: 'numeric',
                            hour: '2-digit', minute: '2-digit'
                        });

                        let badgeColor = 'bg-secondary';
                        let actionText = '';
                        if (log.status_lama === null) {
                            badgeColor = 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;';
                            actionText = `Merekam kasus baru dengan status awal <span class="badge rounded-pill badge-terbuka font-semibold px-2 py-0.5" style="font-size: 0.7rem;">${log.status_baru}</span>`;
                        } else {
                            if (log.status_baru === 'Terbuka') badgeColor = 'background: #fef3c7; color: #92400e; border: 1px solid #fde68a;';
                            else if (log.status_baru === 'Proses') badgeColor = 'background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;';
                            else if (log.status_baru === 'Selesai') badgeColor = 'background: #d1fae5; color: #065f46; border: 1px solid #bbf7d0;';
                            
                            actionText = `Mengubah status dari <strong>${log.status_lama}</strong> menjadi <span class="badge rounded-pill px-2 py-0.5 fw-semibold" style="${badgeColor} font-size: 0.7rem;">${log.status_baru}</span>`;
                        }

                        let roleBadgeColor = 'background: #f1f5f9; color: #475569;';
                        if (log.peran_user === 'super_admin') roleBadgeColor = 'background: #fee2e2; color: #991b1b;';
                        else if (log.peran_user === 'operator_sekolah') roleBadgeColor = 'background: #ede9fe; color: #5b21b6;';

                        const cleanRole = log.peran_user.replace('_', ' ').toUpperCase();

                        htmlContent += `
                            <div class="mb-4 position-relative">
                                <div class="position-absolute" style="left: -32px; top: 4px; width: 14px; height: 14px; border-radius: 50%; background: #7c3aed; border: 3px solid #fff; box-shadow: 0 0 0 2px #ddd;"></div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted fw-normal" style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i>${formattedDate}</span>
                                    <span class="badge rounded-pill fw-bold" style="${roleBadgeColor} font-size: 0.62rem;">${cleanRole}</span>
                                </div>
                                <div class="fw-bold text-dark fs-7 mb-1">${log.nama_user}</div>
                                <p class="text-muted fs-8 mb-0" style="line-height: 1.4;">${actionText}</p>
                            </div>
                        `;
                    });

                    htmlContent += `
                            </div>
                        </div>
                    `;

                    Swal.fire({
                        title: `<span style="font-size: 1.15rem; font-weight: 700; color: #7c3aed;"><i class="bi bi-clock-history me-2"></i>Log Riwayat Kasus</span>`,
                        html: htmlContent,
                        width: '480px',
                        confirmButtonColor: '#7c3aed',
                        confirmButtonText: 'Tutup'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: res.data.error || 'Gagal memuat log.',
                        confirmButtonColor: 'var(--bk-primary)'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan',
                    text: (err.response && err.response.data && err.response.data.error) || 'Koneksi ke server gagal.',
                    confirmButtonColor: 'var(--bk-primary)'
                });
            }
        }

        // ─── Prestasi Siswa ──────────────────────────────────
        const guruList = ref([]);
        const loadingPrestasi = ref(false);
        const loadingPrestasiList = ref(false);
        const prestasiSearchSiswa = ref('');
        const prestasiSiswaOptions = ref([]);
        const selectedPrestasiSiswa = ref([]);
        const showPrestasiSiswaDropdown = ref(false);
        const loadingSearchPrestasiSiswa = ref(false);
        const alertPrestasi = ref({ msg: '', type: 'success' });
        
        const formPrestasi = ref({
            id: '',
            tahun_ajaran_id: '',
            semester: 'Ganjil',
            bidang_lomba: '',
            nama_lomba: '',
            nomor_sertifikat: '',
            juara: '',
            juara_lainnya: '',
            kategori: 'Personal',
            tingkat_kejuaraan: '',
            jenis_lomba: 'Offline',
            tempat_lomba: '',
            tanggal_lomba: '',
            penyelenggara: '',
            guru_pendamping: '',
            poin_prestasi: 0,
            foto_bukti_prestasi: null,
            foto_siswa_prestasi: null,
            foto_kegiatan_lomba: null,
            surat_tugas_pdf: null,
            existing_foto_bukti: null,
            existing_foto_siswa: null,
            existing_foto_kegiatan: null,
            existing_surat_tugas: null
        });

        function removeSiswaPrestasi(id) {
            if (formPrestasi.value.id) return; // Mode Edit: Siswa otomatis terkunci
            selectedPrestasiSiswa.value = selectedPrestasiSiswa.value.filter(s => s.id !== id);
        }

        function exportPrestasiExcel() {
            if (_userRole === 'super_admin' && !currentTenantId.value) {
                Swal.fire('Perhatian', 'Pilih sekolah terlebih dahulu.', 'warning');
                return;
            }
            const params = new URLSearchParams();
            if (filterPrestasiTahunAjaran.value) {
                params.set('tahun_ajaran_id', filterPrestasiTahunAjaran.value);
            }
            if (currentTenantId.value) {
                params.set('tenant_id', currentTenantId.value);
            }
            window.open(`${_baseUrl}/api/v1/bk/prestasi/export?${params.toString()}`, '_blank');
        }

        function autoCalculatePrestasiPoint() {
            const tingkat = (formPrestasi.value.tingkat_kejuaraan || '').toLowerCase();
            const juaraText = (formPrestasi.value.juara === 'Lainnya' ? formPrestasi.value.juara_lainnya : formPrestasi.value.juara || '').toLowerCase();
            if (!tingkat || !juaraText) return;

            if (tingkat.includes('internasional')) {
                if (juaraText.includes('1')) formPrestasi.value.poin_prestasi = 100;
                else if (juaraText.includes('2')) formPrestasi.value.poin_prestasi = 95;
                else if (juaraText.includes('3')) formPrestasi.value.poin_prestasi = 90;
                else if (juaraText.includes('harapan 1')) formPrestasi.value.poin_prestasi = 85;
                else if (juaraText.includes('harapan 2')) formPrestasi.value.poin_prestasi = 80;
                else if (juaraText.includes('harapan 3')) formPrestasi.value.poin_prestasi = 75;
                else formPrestasi.value.poin_prestasi = 70;
            } else if (tingkat.includes('nasional')) {
                if (juaraText.includes('1')) formPrestasi.value.poin_prestasi = 90;
                else if (juaraText.includes('2')) formPrestasi.value.poin_prestasi = 85;
                else if (juaraText.includes('3')) formPrestasi.value.poin_prestasi = 80;
                else if (juaraText.includes('harapan 1')) formPrestasi.value.poin_prestasi = 75;
                else if (juaraText.includes('harapan 2')) formPrestasi.value.poin_prestasi = 70;
                else if (juaraText.includes('harapan 3')) formPrestasi.value.poin_prestasi = 65;
                else formPrestasi.value.poin_prestasi = 60;
            } else if (tingkat.includes('provinsi')) {
                if (juaraText.includes('1')) formPrestasi.value.poin_prestasi = 80;
                else if (juaraText.includes('2')) formPrestasi.value.poin_prestasi = 75;
                else if (juaraText.includes('3')) formPrestasi.value.poin_prestasi = 70;
                else if (juaraText.includes('harapan 1')) formPrestasi.value.poin_prestasi = 65;
                else if (juaraText.includes('harapan 2')) formPrestasi.value.poin_prestasi = 60;
                else if (juaraText.includes('harapan 3')) formPrestasi.value.poin_prestasi = 55;
                else formPrestasi.value.poin_prestasi = 50;
            } else {
                if (juaraText.includes('1')) formPrestasi.value.poin_prestasi = 70;
                else if (juaraText.includes('2')) formPrestasi.value.poin_prestasi = 65;
                else if (juaraText.includes('3')) formPrestasi.value.poin_prestasi = 60;
                else if (juaraText.includes('harapan 1')) formPrestasi.value.poin_prestasi = 55;
                else if (juaraText.includes('harapan 2')) formPrestasi.value.poin_prestasi = 50;
                else if (juaraText.includes('harapan 3')) formPrestasi.value.poin_prestasi = 45;
                else formPrestasi.value.poin_prestasi = 40;
            }
        }

        let debouncePrestasiTimer = null;
        function searchSiswaPrestasiDebounce() {
            clearTimeout(debouncePrestasiTimer);
            const q = prestasiSearchSiswa.value.trim();
            if (q.length < 1) {
                prestasiSiswaOptions.value = [];
                showPrestasiSiswaDropdown.value = false;
                return;
            }
            debouncePrestasiTimer = setTimeout(async () => {
                loadingSearchPrestasiSiswa.value = true;
                showPrestasiSiswaDropdown.value = true;
                try {
                    const params = new URLSearchParams();
                    params.set('q', q);
                    if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
                    params.set('limit', '12');
                    const res = await axios.get(`${_baseUrl}/api/v1/bk/siswa?${params}`);
                    prestasiSiswaOptions.value = res.data.data || [];
                } catch (e) {
                    prestasiSiswaOptions.value = [];
                } finally {
                    loadingSearchPrestasiSiswa.value = false;
                }
            }, 280);
        }

        function hidePrestasiDropdownDelay() {
            setTimeout(() => { showPrestasiSiswaDropdown.value = false; }, 200);
        }

        function selectSiswaPrestasi(s) {
            if (formPrestasi.value.kategori === 'Personal') {
                selectedPrestasiSiswa.value = [s];
            } else {
                if (!selectedPrestasiSiswa.value.some(item => item.id === s.id)) {
                    selectedPrestasiSiswa.value.push(s);
                }
            }
            prestasiSearchSiswa.value = '';
            prestasiSiswaOptions.value = [];
            showPrestasiSiswaDropdown.value = false;
        }

        function removeSiswaPrestasi(id) {
            selectedPrestasiSiswa.value = selectedPrestasiSiswa.value.filter(s => s.id !== id);
        }

        function handleFileUpload(event, fieldName) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 1024 * 1024) {
                    alert('Ukuran berkas melebihi batas 1 MB.');
                    event.target.value = '';
                    return;
                }
                formPrestasi.value[fieldName] = file;
            }
        }

        /**
         * Buat URL aman untuk mengakses file upload.
         * File storage dialihkan melalui /api/v1/file/serve?path=...
         * agar tidak bisa diakses langsung tanpa login.
         */
        function getFileUrl(path) {
            if (!path) return '#';
            if (path.startsWith('http://') || path.startsWith('https://')) return path;
            const cleanPath = String(path).replace(/^\/+/, '');
            // Jika ini file storage sensitif → wajib melalui endpoint aman
            if (cleanPath.startsWith('storage/')) {
                return _baseUrl + '/api/v1/file/serve?path=' + encodeURIComponent(cleanPath);
            }
            return _baseUrl + '/' + cleanPath;
        }

        /**
         * Buat secure URL untuk foto-foto yang ditampilkan langsung di modal/tabel.
         */
        function secureFileUrl(relativePath) {
            if (!relativePath) return '';
            if (relativePath.startsWith('http://') || relativePath.startsWith('https://')) return relativePath;
            const cleanPath = String(relativePath).replace(/^\/+/, '');
            if (cleanPath.startsWith('storage/')) {
                return _baseUrl + '/api/v1/file/serve?path=' + encodeURIComponent(cleanPath);
            }
            return _baseUrl + '/' + cleanPath;
        }

        async function loadPrestasi() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingPrestasiList.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/prestasi`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) {
                    prestasiList.value = res.data.data || [];
                }
            } catch (e) {
                console.error('loadPrestasi error', e);
            } finally {
                loadingPrestasiList.value = false;
            }
        }

        async function loadGuruList() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            try {
                let url = `${_baseUrl}/api/v1/bk/guru`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) {
                    guruList.value = res.data.data || [];
                }
            } catch (e) {
                console.error('loadGuruList error', e);
            }
        }

        async function submitPrestasi() {
            alertPrestasi.value = { msg: '', type: 'success' };
            
            if (!formPrestasi.value.tahun_ajaran_id) { alertPrestasi.value = { msg: 'Tahun Ajaran wajib dipilih.', type: 'danger' }; return; }
            if (!formPrestasi.value.semester) { alertPrestasi.value = { msg: 'Semester wajib dipilih.', type: 'danger' }; return; }
            if (!formPrestasi.value.bidang_lomba) { alertPrestasi.value = { msg: 'Bidang Lomba wajib diisi.', type: 'danger' }; return; }
            if (!formPrestasi.value.nama_lomba) { alertPrestasi.value = { msg: 'Nama Lomba wajib diisi.', type: 'danger' }; return; }
            if (!formPrestasi.value.juara) { alertPrestasi.value = { msg: 'Kategori Juara wajib diisi.', type: 'danger' }; return; }
            if (formPrestasi.value.juara === 'Lainnya' && !formPrestasi.value.juara_lainnya.trim()) { alertPrestasi.value = { msg: 'Keterangan Juara Lainnya wajib diisi.', type: 'danger' }; return; }
            if (!formPrestasi.value.tingkat_kejuaraan) { alertPrestasi.value = { msg: 'Tingkat Kejuaraan wajib dipilih.', type: 'danger' }; return; }
            if (!formPrestasi.value.tempat_lomba) { alertPrestasi.value = { msg: 'Tempat Lomba wajib diisi.', type: 'danger' }; return; }
            if (!formPrestasi.value.tanggal_lomba) { alertPrestasi.value = { msg: 'Tanggal Lomba wajib diisi.', type: 'danger' }; return; }
            if (!formPrestasi.value.penyelenggara) { alertPrestasi.value = { msg: 'Penyelenggara wajib diisi.', type: 'danger' }; return; }
            // Mode Edit: siswa terkunci di backend, bypass validasi client-side
            if (!formPrestasi.value.id && selectedPrestasiSiswa.value.length === 0) { alertPrestasi.value = { msg: 'Minimal pilih satu siswa peraih prestasi.', type: 'danger' }; return; }

            loadingPrestasi.value = true;
            try {
                const formData = new FormData();
                if (formPrestasi.value.id) formData.append('id', formPrestasi.value.id);
                if (currentTenantId.value) formData.append('tenant_id', currentTenantId.value);
                formData.append('tahun_ajaran_id', formPrestasi.value.tahun_ajaran_id);
                formData.append('semester', formPrestasi.value.semester);
                formData.append('bidang_lomba', formPrestasi.value.bidang_lomba);
                formData.append('nama_lomba', formPrestasi.value.nama_lomba);
                formData.append('nomor_sertifikat', formPrestasi.value.nomor_sertifikat);
                formData.append('juara', formPrestasi.value.juara);
                formData.append('juara_lainnya', formPrestasi.value.juara_lainnya);
                formData.append('kategori', formPrestasi.value.kategori);
                formData.append('tingkat_kejuaraan', formPrestasi.value.tingkat_kejuaraan);
                formData.append('jenis_lomba', formPrestasi.value.jenis_lomba);
                formData.append('tempat_lomba', formPrestasi.value.tempat_lomba);
                formData.append('tanggal_lomba', formPrestasi.value.tanggal_lomba);
                formData.append('penyelenggara', formPrestasi.value.penyelenggara);
                formData.append('guru_pendamping', formPrestasi.value.guru_pendamping);
                formData.append('poin_prestasi', formPrestasi.value.poin_prestasi || 0);

                // Mode Edit: kirim siswa_ids hanya jika ada (jika kosong, backend akan keep existing)
                const siswaIds = selectedPrestasiSiswa.value.map(s => s.id);
                formData.append('siswa_ids', JSON.stringify(siswaIds));

                if (formPrestasi.value.foto_bukti_prestasi) formData.append('foto_bukti_prestasi', formPrestasi.value.foto_bukti_prestasi);
                if (formPrestasi.value.foto_siswa_prestasi) formData.append('foto_siswa_prestasi', formPrestasi.value.foto_siswa_prestasi);
                if (formPrestasi.value.foto_kegiatan_lomba) formData.append('foto_kegiatan_lomba', formPrestasi.value.foto_kegiatan_lomba);
                if (formPrestasi.value.surat_tugas_pdf) formData.append('surat_tugas_pdf', formPrestasi.value.surat_tugas_pdf);

                const isUpdate = !!formPrestasi.value.id;
                const endpoint = isUpdate ? `${_baseUrl}/api/v1/bk/prestasi/update` : `${_baseUrl}/api/v1/bk/prestasi`;

                const res = await axios.post(endpoint, formData, {
                    headers: { 'Content-Type': 'multipart/form-data', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (res.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses',
                        text: res.data.message || res.data.msg || 'Data prestasi berhasil disimpan.',
                        confirmButtonColor: 'var(--bk-primary)'
                    });
                    closePrestasiModal();
                    clearFormPrestasi();
                    loadPrestasi();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: res.data.error || res.data.msg || 'Terjadi kesalahan.',
                        confirmButtonColor: 'var(--bk-primary)'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan',
                    text: (err.response && err.response.data && (err.response.data.error || err.response.data.msg)) || 'Koneksi ke server gagal.',
                    confirmButtonColor: 'var(--bk-primary)'
                });
            } finally {
                loadingPrestasi.value = false;
            }
        }

        function openTambahPrestasiModal() {
            clearFormPrestasi();
            const modalEl = document.getElementById('modalFormPrestasi');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }

        function openEditPrestasiModal(p) {
            editPrestasi(p);
            const modalEl = document.getElementById('modalFormPrestasi');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }

        function closePrestasiModal() {
            const modalEl = document.getElementById('modalFormPrestasi');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        }

        function editPrestasi(p) {
            clearFormPrestasi();
            formPrestasi.value.id = p.id;
            formPrestasi.value.tahun_ajaran_id = p.tahun_ajaran_id;
            formPrestasi.value.semester = p.semester;
            formPrestasi.value.bidang_lomba = p.bidang_lomba;
            formPrestasi.value.nama_lomba = p.nama_lomba;
            formPrestasi.value.nomor_sertifikat = p.nomor_sertifikat || '';
            if (['Juara 1', 'Juara 2', 'Juara 3', 'Harapan 1', 'Harapan 2', 'Harapan 3'].includes(p.juara)) {
                formPrestasi.value.juara = p.juara;
                formPrestasi.value.juara_lainnya = '';
            } else {
                formPrestasi.value.juara = 'Lainnya';
                formPrestasi.value.juara_lainnya = p.juara;
            }
            formPrestasi.value.kategori = p.kategori;
            formPrestasi.value.tingkat_kejuaraan = p.tingkat_kejuaraan;
            formPrestasi.value.jenis_lomba = p.jenis_lomba;
            formPrestasi.value.tempat_lomba = p.tempat_lomba;
            formPrestasi.value.tanggal_lomba = p.tanggal_lomba;
            formPrestasi.value.penyelenggara = p.penyelenggara;
            formPrestasi.value.guru_pendamping = p.guru_pendamping || '';
            formPrestasi.value.poin_prestasi = p.poin_prestasi || 0;
            
            formPrestasi.value.existing_foto_bukti = p.foto_bukti_prestasi;
            formPrestasi.value.existing_foto_siswa = p.foto_siswa_prestasi;
            formPrestasi.value.existing_foto_kegiatan = p.foto_kegiatan_lomba;
            formPrestasi.value.existing_surat_tugas = p.surat_tugas_pdf;

            // Populasi selectedPrestasiSiswa dari siswa_list (join pivot)
            // Fallback: jika siswa_list kosong (data lama), gunakan field top-level
            if (p.siswa_list && p.siswa_list.length > 0) {
                selectedPrestasiSiswa.value = p.siswa_list.map(s => ({
                    id: s.id,
                    nama_lengkap: s.nama_lengkap || s.nama || '-',
                    nisn: s.nisn || '-',
                    nama_kelas: s.nama_kelas || s.kelas_saat_ini || '-'
                }));
            } else if (p.nama_siswa && p.nama_siswa !== '—') {
                // Fallback dari field agregasi top-level (data lama tanpa relasi pivot)
                selectedPrestasiSiswa.value = [{
                    id: p.siswa_id || 'legacy-' + p.id,
                    nama_lengkap: p.nama_siswa,
                    nisn: p.nisn || '-',
                    nama_kelas: p.nama_kelas || '-'
                }];
            } else {
                selectedPrestasiSiswa.value = [];
            }
        }

        async function deletePrestasi(id) {
            const confirmResult = await Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus data prestasi ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--bk-red)',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            });

            if (confirmResult.isConfirmed) {
                try {
                    const res = await axios.post(`${_baseUrl}/api/v1/bk/prestasi/delete`, {
                        id: id,
                        tenant_id: currentTenantId.value
                    }, { headers: { 'Content-Type': 'application/json' } });

                    if (res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Dihapus',
                            text: res.data.message,
                            confirmButtonColor: 'var(--bk-primary)'
                        });
                        loadPrestasi();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: res.data.error || 'Gagal menghapus data.',
                            confirmButtonColor: 'var(--bk-primary)'
                        });
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan',
                        text: (err.response && err.response.data && err.response.data.error) || 'Koneksi ke server gagal.',
                        confirmButtonColor: 'var(--bk-primary)'
                    });
                }
            }
        }

        function clearFormPrestasi() {
            formPrestasi.value = {
                id: '',
                tahun_ajaran_id: '',
                semester: 'Ganjil',
                bidang_lomba: '',
                nama_lomba: '',
                nomor_sertifikat: '',
                juara: '',
                juara_lainnya: '',
                kategori: 'Personal',
                tingkat_kejuaraan: '',
                jenis_lomba: 'Offline',
                tempat_lomba: '',
                tanggal_lomba: '',
                penyelenggara: '',
                guru_pendamping: '',
                poin_prestasi: 0,
                foto_bukti_prestasi: null,
                foto_siswa_prestasi: null,
                foto_kegiatan_lomba: null,
                surat_tugas_pdf: null,
                existing_foto_bukti: null,
                existing_foto_siswa: null,
                existing_foto_kegiatan: null,
                existing_surat_tugas: null
            };
            selectedPrestasiSiswa.value = [];
            prestasiSearchSiswa.value = '';
            prestasiSiswaOptions.value = [];
            showPrestasiSiswaDropdown.value = false;
            
            document.querySelectorAll('.prestasi-file-input').forEach(input => {
                input.value = '';
            });
        }

        // ─── Kehadiran Methods ──────────────────────────────
        async function loadKelasKehadiran() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            try {
                let url = `${_baseUrl}/api/v1/bk/kelas`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) {
                    listKelasKehadiran.value = res.data.data || [];
                    if (listKelasKehadiran.value.length > 0) {
                        const exists = listKelasKehadiran.value.some(k => k.id === filterKehadiran.value.kelas_id);
                        if (!filterKehadiran.value.kelas_id || !exists) {
                            filterKehadiran.value.kelas_id = listKelasKehadiran.value[0].id;
                        }
                    }
                }
                if (!filterKehadiran.value.tahun_ajaran_id && (tahunAjaranList.value || []).length > 0) {
                    const activeTa = tahunAjaranList.value.find(ta => ta.status === 'Aktif') || tahunAjaranList.value[0];
                    if (activeTa) filterKehadiran.value.tahun_ajaran_id = activeTa.id;
                }
                if (filterKehadiran.value.tahun_ajaran_id && filterKehadiran.value.semester && filterKehadiran.value.kelas_id) {
                    loadKehadiran();
                }
            } catch (e) { console.error('loadKelasKehadiran error', e); }
        }

        async function loadKehadiran() {
            if (!filterKehadiran.value.tahun_ajaran_id || !filterKehadiran.value.semester || !filterKehadiran.value.kelas_id) {
                kehadiranData.value = [];
                originalKehadiranData.value = [];
                isKehadiranLocked.value = false;
                return;
            }
            loadingKehadiran.value = true;
            kehadiranData.value = [];
            originalKehadiranData.value = [];
            try {
                const params = new URLSearchParams();
                params.set('tahun_ajaran_id', filterKehadiran.value.tahun_ajaran_id);
                params.set('semester', filterKehadiran.value.semester);
                params.set('kelas_id', filterKehadiran.value.kelas_id);
                if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
                const res = await axios.get(`${_baseUrl}/api/v1/bk/absensi-semester?${params}`);
                if (res.data.success) {
                    kehadiranData.value = res.data.data.map(item => ({
                        ...item,
                        sakit: (item.sakit === null || item.sakit === '' || item.sakit === undefined) ? 0 : (parseInt(item.sakit) || 0),
                        izin: (item.izin === null || item.izin === '' || item.izin === undefined) ? 0 : (parseInt(item.izin) || 0),
                        alfa: (item.alfa === null || item.alfa === '' || item.alfa === undefined) ? 0 : (parseInt(item.alfa) || 0)
                    }));
                    isKehadiranLocked.value = !!res.data.is_locked;
                    originalKehadiranData.value = JSON.parse(JSON.stringify(kehadiranData.value));
                }
            } catch (e) {
                console.error('loadKehadiran error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal mengambil data kehadiran.' });
            } finally {
                loadingKehadiran.value = false;
            }
        }

        function isCellDirty(siswaId, field) {
            const orig = originalKehadiranData.value.find(item => item.siswa_id === siswaId);
            const current = kehadiranData.value.find(item => item.siswa_id === siswaId);
            if (!orig || !current) return false;
            return orig[field] !== current[field];
        }

        function isRowDirty(siswaId) {
            const current = kehadiranData.value.find(item => item.siswa_id === siswaId);
            const orig = originalKehadiranData.value.find(item => item.siswa_id === siswaId);
            if (!current || !orig) return false;
            return current.sakit !== orig.sakit || current.izin !== orig.izin || current.alfa !== orig.alfa;
        }

        function setAllEmptyToZero() {
            if (isKehadiranLocked.value) {
                Swal.fire('Terperinci', 'Data kehadiran kelas ini sedang dikunci oleh Admin/Kurikulum.', 'warning');
                return;
            }
            let updatedCount = 0;
            kehadiranData.value.forEach(item => {
                if (item.sakit === null || item.sakit === '' || item.sakit === undefined || isNaN(item.sakit)) { item.sakit = 0; updatedCount++; }
                if (item.izin === null || item.izin === '' || item.izin === undefined || isNaN(item.izin)) { item.izin = 0; updatedCount++; }
                if (item.alfa === null || item.alfa === '' || item.alfa === undefined || isNaN(item.alfa)) { item.alfa = 0; updatedCount++; }
            });
            toast.fire({ icon: 'info', title: 'Seluruh sel kosong telah diisi angka 0. Silakan klik Simpan.' });
        }

        function incrementAbsen(siswaId, field) {
            if (isKehadiranLocked.value) return;
            const item = kehadiranData.value.find(s => s.siswa_id === siswaId);
            if (!item) return;
            let val = parseInt(item[field]);
            if (isNaN(val)) val = 0;
            item[field] = val + 1;
        }

        function decrementAbsen(siswaId, field) {
            if (isKehadiranLocked.value) return;
            const item = kehadiranData.value.find(s => s.siswa_id === siswaId);
            if (!item) return;
            let val = parseInt(item[field]);
            if (isNaN(val) || val <= 0) {
                item[field] = 0;
            } else {
                item[field] = val - 1;
            }
        }

        async function toggleLockKehadiran() {
            const nextState = !isKehadiranLocked.value;
            const actionText = nextState ? 'KUNCI' : 'BUKA KUNCI';
            const confirmMsg = nextState 
                ? 'Apakah Anda yakin ingin MENGUNCI data kehadiran kelas ini? Data yang dikunci tidak dapat diubah tanpa persetujuan Admin/Kurikulum.' 
                : 'Apakah Anda yakin ingin MEMBUKA KUNCI data kehadiran kelas ini?';

            const res = await Swal.fire({
                title: `Konfirmasi ${actionText} Data Kehadiran`,
                text: confirmMsg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: nextState ? '#ef4444' : '#10b981',
                confirmButtonText: `Ya, ${actionText}`
            });

            if (!res.isConfirmed) return;

            try {
                const payload = {
                    tahun_ajaran_id: filterKehadiran.value.tahun_ajaran_id,
                    semester: filterKehadiran.value.semester,
                    kelas_id: filterKehadiran.value.kelas_id,
                    is_locked: nextState
                };
                if (currentTenantId.value) payload.tenant_id = currentTenantId.value;

                const resp = await axios.post(`${_baseUrl}/api/v1/bk/absensi-semester/toggle-lock`, payload);
                if (resp.data && resp.data.success) {
                    isKehadiranLocked.value = nextState;
                    toast.fire({ icon: 'success', title: resp.data.message || 'Status kunci diperbarui.' });
                    loadKehadiran();
                }
            } catch (e) {
                console.error('toggleLockKehadiran error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || 'Gagal mengubah status kunci.' });
            }
        }

        async function saveKehadiran() {
            if (kehadiranData.value.length === 0) return;
            if (isKehadiranLocked.value) {
                Swal.fire('Terperinci', 'Data kehadiran kelas ini sedang dikunci oleh Admin/Kurikulum.', 'warning');
                return;
            }
            savingKehadiran.value = true;
            try {
                const payload = {
                    tahun_ajaran_id: filterKehadiran.value.tahun_ajaran_id,
                    semester: filterKehadiran.value.semester,
                    kelas_id: filterKehadiran.value.kelas_id,
                    attendance: kehadiranData.value.map(item => ({
                        siswa_id: item.siswa_id,
                        sakit: parseInt(item.sakit) || 0,
                        izin: parseInt(item.izin) || 0,
                        alfa: parseInt(item.alfa) || 0
                    }))
                };
                if (currentTenantId.value) payload.tenant_id = currentTenantId.value;

                const res = await axios.post(`${_baseUrl}/api/v1/bk/absensi-semester`, payload, {
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.data.success) {
                    Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false });
                    originalKehadiranData.value = JSON.parse(JSON.stringify(kehadiranData.value));
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menyimpan data.' });
                }
            } catch (e) {
                console.error('saveKehadiran error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: (e.response && e.response.data && e.response.data.error) || 'Gagal menghubungi server.' });
            } finally {
                savingKehadiran.value = false;
            }
        }

        function openModalImportKehadiran() {
            // Guard: blokir aksi jika data sudah dikunci
            if (isKehadiranLocked.value) {
                Swal.fire('Data Terkunci', 'Data kehadiran kelas ini sedang dikunci. Buka kunci terlebih dahulu untuk mengimpor data.', 'warning');
                return;
            }
            modalImportKehadiran.value.show = true;
            modalImportKehadiran.value.loading = false;
            modalImportKehadiran.value.file = null;
            modalImportKehadiran.value.tahun_ajaran_id = filterKehadiran.value.tahun_ajaran_id || (tahunAjaranList.value[0]?.id || '');
            modalImportKehadiran.value.semester = filterKehadiran.value.semester || 'Ganjil';
            modalImportKehadiran.value.kelas_id = filterKehadiran.value.kelas_id || (listKelasKehadiran.value[0]?.id || '');
            
            const fileInput = document.getElementById('modal-file-import-kehadiran');
            if (fileInput) fileInput.value = '';
        }

        function closeModalImportKehadiran() {
            modalImportKehadiran.value.show = false;
            modalImportKehadiran.value.loading = false;
            modalImportKehadiran.value.file = null;
        }

        function handleModalFileImportChange(event) {
            modalImportKehadiran.value.file = event.target.files[0] || null;
        }

        function unduhTemplateKehadiran() {
            const ta = modalImportKehadiran.value.tahun_ajaran_id || filterKehadiran.value.tahun_ajaran_id;
            const sm = modalImportKehadiran.value.semester || filterKehadiran.value.semester;
            const kl = modalImportKehadiran.value.kelas_id || filterKehadiran.value.kelas_id;

            if (!ta || !sm || !kl) {
                Swal.fire('Perhatian', 'Pilih Tahun Ajaran, Semester, dan Kelas target terlebih dahulu.', 'warning');
                return;
            }

            const params = new URLSearchParams();
            params.set('tahun_ajaran_id', ta);
            params.set('semester', sm);
            params.set('kelas_id', kl);
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            window.open(`${_baseUrl}/api/v1/bk/absensi-semester/export?${params.toString()}`, '_blank');
        }

        async function submitModalImportKehadiran() {
            // Guard: blokir submit jika data sudah dikunci (double-check keamanan)
            if (isKehadiranLocked.value) {
                Swal.fire('Data Terkunci', 'Data kehadiran kelas ini sedang dikunci. Buka kunci terlebih dahulu sebelum mengimpor data.', 'warning');
                return;
            }
            if (!modalImportKehadiran.value.file) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih berkas template Excel (.xlsx) yang ingin diimpor terlebih dahulu.' });
                return;
            }
            if (!modalImportKehadiran.value.tahun_ajaran_id || !modalImportKehadiran.value.semester || !modalImportKehadiran.value.kelas_id) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pastikan Tahun Ajaran, Semester, dan Kelas target sudah dipilih.' });
                return;
            }

            modalImportKehadiran.value.loading = true;
            try {
                const formData = new FormData();
                formData.append('file', modalImportKehadiran.value.file);
                formData.append('tahun_ajaran_id', modalImportKehadiran.value.tahun_ajaran_id);
                formData.append('semester', modalImportKehadiran.value.semester);
                formData.append('kelas_id', modalImportKehadiran.value.kelas_id);
                if (currentTenantId.value) formData.append('tenant_id', currentTenantId.value);
                
                const res = await axios.post(`${_baseUrl}/api/v1/bk/absensi-semester/import`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.data.success) {
                    Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message || 'Data kehadiran berhasil diimpor.' });
                    closeModalImportKehadiran();
                    // Sinkronkan filter utama ke kelas yang baru diimpor
                    filterKehadiran.value.tahun_ajaran_id = modalImportKehadiran.value.tahun_ajaran_id;
                    filterKehadiran.value.semester = modalImportKehadiran.value.semester;
                    filterKehadiran.value.kelas_id = modalImportKehadiran.value.kelas_id;
                    await loadKehadiran();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal mengimpor data kehadiran.' });
                }
            } catch (e) {
                console.error(e);
                const errors = (e.response && e.response.data && e.response.data.errors);
                const errorText = (e.response && e.response.data && e.response.data.error);
                const detailsText = (e.response && e.response.data && e.response.data.details);

                if (errors && errors.length > 0) {
                    let errMsg = `<div class="text-start" style="max-height:200px; overflow-y:auto; font-size: 0.85rem;">`;
                    errors.forEach(err => { errMsg += `<p class="text-danger mb-1">• ${err}</p>`; });
                    errMsg += `</div>`;
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Baris Data',
                        html: errMsg,
                        width: '460px'
                    });
                } else {
                    let errorHtml = errorText || 'Terjadi kesalahan saat mengimpor file.';
                    if (detailsText) {
                        errorHtml += `<div class="mt-2 text-start text-muted fs-8 bg-light p-2 rounded">${detailsText}</div>`;
                    }
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Gagal Mengimpor', 
                        html: errorHtml 
                    });
                }
            } finally {
                modalImportKehadiran.value.loading = false;
            }
        }

        function openModalExportKehadiran() {
            modalExportKehadiran.value.tahun_ajaran_id = filterKehadiran.value.tahun_ajaran_id || (tahunAjaranList.value[0]?.id || '');
            modalExportKehadiran.value.semester = filterKehadiran.value.semester || 'Ganjil';
            modalExportKehadiran.value.kelas_id = filterKehadiran.value.kelas_id || (listKelasKehadiran.value[0]?.id || '');
            modalExportKehadiran.value.show = true;
        }

        function closeModalExportKehadiran() {
            modalExportKehadiran.value.show = false;
        }

        function submitModalExportKehadiran() {
            if (!modalExportKehadiran.value.tahun_ajaran_id || !modalExportKehadiran.value.semester || !modalExportKehadiran.value.kelas_id) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih filter Tahun Ajaran, Semester, dan Kelas terlebih dahulu.' });
                return;
            }
            const params = new URLSearchParams();
            params.set('tahun_ajaran_id', modalExportKehadiran.value.tahun_ajaran_id);
            params.set('semester', modalExportKehadiran.value.semester);
            params.set('kelas_id', modalExportKehadiran.value.kelas_id);
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            window.open(`${_baseUrl}/api/v1/bk/absensi-semester/export?${params.toString()}`, '_blank');
            closeModalExportKehadiran();
        }

        function exportKehadiran() {
            openModalExportKehadiran();
        }

        function getNamaKelas(id) {
            if (!id) return '-';
            const k = (listKelasKehadiran.value || []).find(item => item.id === id || item.nama_kelas === id);
            return k ? k.nama_kelas : id;
        }

        function getNamaTahunAjaran(id) {
            if (!id) return '-';
            const ta = (tahunAjaranList.value || []).find(item => item.id === id || item.tahun_ajaran === id);
            return ta ? ta.tahun_ajaran : id;
        }

        function handleGridKeydown(event, rowIndex, colName) {
            const inputs = document.querySelectorAll('.grid-input-number');
            const totalInputs = inputs.length;
            if (totalInputs === 0) return;

            const currentEl = document.activeElement;
            let currentIdx = -1;
            for (let i = 0; i < totalInputs; i++) {
                if (inputs[i] === currentEl) {
                    currentIdx = i;
                    break;
                }
            }
            if (currentIdx === -1) return;

            const colsPerRow = 3; 
            let targetIdx = -1;

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                targetIdx = currentIdx - colsPerRow;
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                targetIdx = currentIdx + colsPerRow;
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                targetIdx = currentIdx - 1;
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                targetIdx = currentIdx + 1;
            } else if (event.key === 'Enter') {
                event.preventDefault();
                targetIdx = currentIdx + colsPerRow;
            }

            if (targetIdx >= 0 && targetIdx < totalInputs) {
                inputs[targetIdx].focus();
                inputs[targetIdx].select();
            }
        }

        // ─── Pelanggaran & Poin Methods ──────────────────────
        function switchSubTab(subTab) {
            activeTab.value = 'pelanggaran';
            activeSubTab.value = subTab;
            if (subTab === 'p_dashboard') {
                loadPelanggaranDashboard();
            } else if (subTab === 'p_input') {
                loadPelanggaranCatatan();
                loadPelanggaranMaster();
            } else if (subTab === 'p_master') {
                loadPelanggaranMaster();
            } else if (subTab === 'p_sanksi') {
                loadPelanggaranSanksi();
            }
        }

        async function loadPelanggaranDashboard() {
            loadingPelanggaranDashboard.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/pelanggaran/dashboard`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data && res.data.success) {
                    const resData = (res.data.data && typeof res.data.data === 'object') ? res.data.data : res.data;
                    pelanggaranKpi.value = resData.kpi || { wali_kelas: 0, sp1_bk: 0, sp2_skorsing: 0, sp3_do: 0, total_siswa_melanggar: 0 };
                    pelanggaranTopStudents.value = resData.top_students || [];
                    
                    nextTick(() => {
                        renderPelanggaranChart(resData.chart);
                    });
                } else {
                    pelanggaranKpi.value = { wali_kelas: 0, sp1_bk: 0, sp2_skorsing: 0, sp3_do: 0, total_siswa_melanggar: 0 };
                }
            } catch (e) {
                console.error('loadPelanggaranDashboard error', e);
                pelanggaranKpi.value = { wali_kelas: 0, sp1_bk: 0, sp2_skorsing: 0, sp3_do: 0, total_siswa_melanggar: 0 };
            } finally {
                loadingPelanggaranDashboard.value = false;
                loadPelanggaranCatatan();
            }
        }

        function renderPelanggaranChart(chartData) {
            const ctx = document.getElementById('pelanggaranTrendChart');
            if (!ctx) return;
            if (pelanggaranChartInstance) {
                pelanggaranChartInstance.destroy();
            }
            if (window.Chart && chartData) {
                pelanggaranChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels || [],
                        datasets: [{
                            label: 'Jumlah Pelanggaran',
                            data: chartData.data || [],
                            borderColor: '#7c3aed',
                            backgroundColor: 'rgba(124, 58, 237, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }
        }

        async function loadPelanggaranMaster() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingPelanggaranMaster.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/pelanggaran/master`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) {
                    pelanggaranMasterList.value = res.data.data || [];
                }
            } catch (e) {
                console.error('loadPelanggaranMaster error', e);
            } finally {
                loadingPelanggaranMaster.value = false;
            }
        }

        function openMasterModal(rule) {
            if (rule) {
                masterModal.value.isEdit = true;
                masterModal.value.form = {
                    id: rule.id,
                    kategori: rule.kategori,
                    nama_pelanggaran: rule.nama_pelanggaran,
                    bobot_poin: parseInt(rule.bobot_poin) || 5
                };
            } else {
                masterModal.value.isEdit = false;
                masterModal.value.form = {
                    id: '',
                    kategori: 'Ringan',
                    nama_pelanggaran: '',
                    bobot_poin: 5
                };
            }
        }

        async function submitMasterRule() {
            if (!masterModal.value.form.nama_pelanggaran.trim()) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Nama pelanggaran wajib diisi.' });
                return;
            }
            if (!masterModal.value.form.bobot_poin || masterModal.value.form.bobot_poin < 1) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Bobot poin minimal 1.' });
                return;
            }
            masterModal.value.saving = true;
            try {
                const payload = {
                    kategori: masterModal.value.form.kategori,
                    nama_pelanggaran: masterModal.value.form.nama_pelanggaran,
                    bobot_poin: masterModal.value.form.bobot_poin
                };
                if (currentTenantId.value) payload.tenant_id = currentTenantId.value;

                let url = `${_baseUrl}/api/v1/bk/pelanggaran/master`;
                let res;
                if (masterModal.value.isEdit) {
                    payload.id = masterModal.value.form.id;
                    res = await axios.post(`${url}/update`, payload, {
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                } else {
                    res = await axios.post(url, payload, {
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                }

                if (res.data.success) {
                    Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false });
                    openMasterModal(null);
                    loadPelanggaranMaster();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menyimpan aturan.' });
                }
            } catch (e) {
                console.error('submitMasterRule error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: (e.response && e.response.data && e.response.data.error) || 'Terjadi kesalahan.' });
            } finally {
                masterModal.value.saving = false;
            }
        }

        async function deleteMasterRule(id) {
            const confirmResult = await Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus aturan pelanggaran ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--bk-red)',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            });

            if (confirmResult.isConfirmed) {
                try {
                    const payload = { id };
                    if (currentTenantId.value) payload.tenant_id = currentTenantId.value;
                    const res = await axios.post(`${_baseUrl}/api/v1/bk/pelanggaran/master/delete`, payload, {
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false });
                        loadPelanggaranMaster();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menghapus aturan.' });
                    }
                } catch (e) {
                    console.error('deleteMasterRule error', e);
                    Swal.fire({ icon: 'error', title: 'Gagal', text: (e.response && e.response.data && e.response.data.error) || 'Terjadi kesalahan.' });
                }
            }
        }

        function searchSiswaPelanggaranDebounce() {
            clearTimeout(debounceTimerPelanggaran);
            const q = pelanggaranSearchSiswa.value.trim();
            if (q.length < 1) {
                pelanggaranSiswaOptions.value = [];
                showPelanggaranSiswaDropdown.value = false;
                return;
            }
            debounceTimerPelanggaran = setTimeout(async () => {
                loadingSearchPelanggaranSiswa.value = true;
                showPelanggaranSiswaDropdown.value = true;
                try {
                    const params = new URLSearchParams();
                    params.set('q', q);
                    if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
                    params.set('limit', '12');
                    const res = await axios.get(`${_baseUrl}/api/v1/bk/siswa?${params.toString()}`);
                    if (res.data.success) {
                        pelanggaranSiswaOptions.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('searchSiswaPelanggaranDebounce error', e);
                    pelanggaranSiswaOptions.value = [];
                } finally {
                    loadingSearchPelanggaranSiswa.value = false;
                }
            }, 280);
        }

        function hidePelanggaranDropdownDelay() {
            setTimeout(() => { showPelanggaranSiswaDropdown.value = false; }, 200);
        }

        function selectSiswaPelanggaran(s) {
            selectedPelanggaranSiswa.value = s;
            formInputPelanggaran.value.siswa_id = s.id;
            pelanggaranSearchSiswa.value = s.nama_lengkap;
            pelanggaranSiswaOptions.value = [];
            showPelanggaranSiswaDropdown.value = false;
        }

        function clearSiswaPelanggaran() {
            selectedPelanggaranSiswa.value = {};
            pelanggaranSearchSiswa.value = '';
            formInputPelanggaran.value = {
                id: '',
                siswa_id: '',
                pelanggaran_id: '',
                tanggal_kejadian: new Date().toISOString().split('T')[0],
                catatan_keterangan: '',
                foto_bukti: null,
                existing_foto: null
            };
            const fileInput = document.getElementById('input-foto-bukti-file');
            if (fileInput) fileInput.value = '';
        }

        /**
         * Kompres gambar ke bawah maxSizeBytes menggunakan Canvas API.
         * Mengembalikan Promise<File> yang sudah dikompres.
         */
        function compressImageBelow1MB(file, maxSizeBytes = 1 * 1024 * 1024, quality = 0.85) {
            return new Promise((resolve) => {
                if (file.size <= maxSizeBytes) { resolve(file); return; }
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let { width, height } = img;
                        // Turunkan resolusi jika file masih terlalu besar
                        const scaleFactor = Math.sqrt(maxSizeBytes / file.size) * 0.9;
                        canvas.width  = Math.round(width  * scaleFactor);
                        canvas.height = Math.round(height * scaleFactor);
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                        // Coba kompres iteratif sampai di bawah batas
                        let q = quality;
                        const tryCompress = () => {
                            canvas.toBlob((blob) => {
                                if (blob.size <= maxSizeBytes || q <= 0.3) {
                                    const compressed = new File([blob], file.name, { type: blob.type || 'image/jpeg', lastModified: Date.now() });
                                    resolve(compressed);
                                } else {
                                    q -= 0.1;
                                    tryCompress();
                                }
                            }, 'image/jpeg', q);
                        };
                        tryCompress();
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        async function handleFotoUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'File harus berupa gambar (JPG, PNG, WEBP, dll).' });
                event.target.value = '';
                return;
            }
            // Auto-kompres jika di atas 1MB
            if (file.size > 1 * 1024 * 1024) {
                const toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                toast.fire({ icon: 'info', title: 'Mengompres foto... 🗜️' });
                const compressed = await compressImageBelow1MB(file, 1 * 1024 * 1024);
                formInputPelanggaran.value.foto_bukti = compressed;
                const kb = Math.round(compressed.size / 1024);
                toast.fire({ icon: 'success', title: `Foto dikompres: ${kb} KB ✅` });
            } else {
                formInputPelanggaran.value.foto_bukti = file;
            }
        }

        async function submitPelanggaran() {
            const targetSId = formInputPelanggaran.value.siswa_id || selectedPelanggaranSiswa.value.id || formInputPelanggaran.value.id;
            if (!targetSId) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih siswa terlebih dahulu.' });
                return;
            }
            if (!formInputPelanggaran.value.pelanggaran_id) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih jenis pelanggaran.' });
                return;
            }
            if (!formInputPelanggaran.value.tanggal_kejadian) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih tanggal kejadian.' });
                return;
            }
            submittingPelanggaran.value = true;
            try {
                const formData = new FormData();
                if (formInputPelanggaran.value.id) formData.append('id', formInputPelanggaran.value.id);
                formData.append('siswa_id', targetSId);
                formData.append('pelanggaran_id', formInputPelanggaran.value.pelanggaran_id);
                formData.append('tanggal_kejadian', formInputPelanggaran.value.tanggal_kejadian);
                formData.append('catatan_keterangan', formInputPelanggaran.value.catatan_keterangan || '');
                if (formInputPelanggaran.value.foto_bukti) {
                    formData.append('foto_bukti', formInputPelanggaran.value.foto_bukti);
                }
                if (currentTenantId.value) formData.append('tenant_id', currentTenantId.value);

                let url = `${_baseUrl}/api/v1/bk/pelanggaran/catatan`;
                if (formInputPelanggaran.value.id) {
                    url = `${_baseUrl}/api/v1/bk/pelanggaran/catatan/update`;
                }

                const res = await axios.post(url, formData, {
                    headers: { 'Content-Type': 'multipart/form-data', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (res.data.success) {
                    Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false });
                    closePelanggaranModal();
                    clearSiswaPelanggaran();
                    catatanListSearch.value = '';
                    loadPelanggaranCatatan();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menyimpan catatan.' });
                }
            } catch (e) {
                console.error('submitPelanggaran error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: (e.response && e.response.data && e.response.data.error) || 'Terjadi kesalahan.' });
            } finally {
                submittingPelanggaran.value = false;
            }
        }

        function openPelanggaranModal() {
            clearSiswaPelanggaran();
            const modalEl = document.getElementById('modalFormCatatPelanggaran');
            if (modalEl) {
                if (modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
                const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                bsModal.show();
            }
        }

        function closePelanggaranModal() {
            const modalEl = document.getElementById('modalFormCatatPelanggaran');
            if (modalEl) {
                const bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) bsModal.hide();
            }
        }

        function editPelanggaran(c) {
            const targetSiswaId = c.siswa_id || c.id;
            selectedPelanggaranSiswa.value = {
                id: targetSiswaId,
                nama_lengkap: c.nama_siswa || c.nama_lengkap || 'Siswa',
                nisn: c.nisn || '-',
                nama_kelas: c.nama_kelas || '-'
            };

            let pId = c.pelanggaran_id || '';
            if (!pId && pelanggaranMasterList.value && pelanggaranMasterList.value.length > 0) {
                const matched = pelanggaranMasterList.value.find(r => 
                    r.kategori === c.kategori || 
                    (c.catatan_keterangan && c.catatan_keterangan.toLowerCase().includes(r.nama_pelanggaran.toLowerCase())) ||
                    (c.nama_pelanggaran && c.nama_pelanggaran.toLowerCase().includes(r.nama_pelanggaran.toLowerCase()))
                );
                if (matched) pId = matched.id;
            }

            formInputPelanggaran.value = {
                id: c.id,
                siswa_id: targetSiswaId,
                pelanggaran_id: pId,
                tanggal_kejadian: c.tanggal_kejadian ? String(c.tanggal_kejadian).substring(0, 10) : today,
                catatan_keterangan: c.catatan_keterangan || '',
                foto_bukti: null,
                existing_foto: c.foto_bukti || null
            };
            const fileInput = document.getElementById('input-foto-bukti-file');
            if (fileInput) fileInput.value = '';

            const modalEl = document.getElementById('modalFormCatatPelanggaran');
            if (modalEl) {
                if (modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
                const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                bsModal.show();
            }
        }

        async function deletePelanggaran(id) {
            const confirmResult = await Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus catatan pelanggaran ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--bk-red)',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            });

            if (confirmResult.isConfirmed) {
                try {
                    const payload = { id };
                    if (currentTenantId.value) payload.tenant_id = currentTenantId.value;
                    const res = await axios.post(`${_baseUrl}/api/v1/bk/pelanggaran/catatan/delete`, payload, {
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false });
                        loadPelanggaranCatatan();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menghapus catatan.' });
                    }
                } catch (e) {
                    console.error('deletePelanggaran error', e);
                    Swal.fire({ icon: 'error', title: 'Gagal', text: (e.response && e.response.data && e.response.data.error) || 'Terjadi kesalahan.' });
                }
            }
        }

        async function loadPelanggaranCatatan() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingPelanggaranCatatan.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/pelanggaran/catatan`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) {
                    pelanggaranCatatanList.value = res.data.data || [];
                }
            } catch (e) {
                console.error('loadPelanggaranCatatan error', e);
            } finally {
                loadingPelanggaranCatatan.value = false;
            }
        }

        async function loadPelanggaranSanksi() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingPelanggaranSanksi.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/pelanggaran/sanksi`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) {
                    pelanggaranSanksiList.value = res.data.data || [];
                }
            } catch (e) {
                console.error('loadPelanggaranSanksi error', e);
            } finally {
                loadingPelanggaranSanksi.value = false;
            }
        }

        async function openSanksiDetail(siswaId) {
            sanksiDetailModal.value.show = true;
            sanksiDetailModal.value.total_poin = 0;
            sanksiDetailModal.value.student = {};
            sanksiDetailModal.value.violations = [];
            sanksiDetailModal.value.followUps = [];

            formTindakLanjut.value = {
                tanggal_tindakan: new Date().toISOString().split('T')[0],
                jenis_tindakan: 'Konseling BK',
                keterangan_tindakan: '',
                surat_panggilan: null,
                foto_pembinaan: null
            };
            const sInput = document.getElementById('input-tindak-surat');
            if (sInput) sInput.value = '';
            const fInput = document.getElementById('input-tindak-foto');
            if (fInput) fInput.value = '';

            try {
                let url = `${_baseUrl}/api/v1/bk/pelanggaran/sanksi/detail?siswa_id=${siswaId}`;
                if (currentTenantId.value) url += `&tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data && res.data.success) {
                    const payloadData = (res.data.data && typeof res.data.data === 'object') ? res.data.data : res.data;
                    sanksiDetailModal.value.student = payloadData.student || {};
                    sanksiDetailModal.value.total_poin = parseInt(payloadData.total_poin) || 0;
                    sanksiDetailModal.value.violations = payloadData.violations || [];
                    sanksiDetailModal.value.followUps = payloadData.follow_ups || [];
                }
                await loadStatusPengajuanTu(siswaId);
            } catch (e) {
                console.error('openSanksiDetail error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal mengambil detail sanksi siswa.' });
            }
        }

        async function loadStatusPengajuanTu(siswaId) {
            try {
                let url = `${_baseUrl}/api/v1/bk/pelanggaran/notifikasi-panggilan/status?siswa_id=${siswaId}`;
                if (currentTenantId.value) url += `&tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data?.success) {
                    statusPengajuanTuList.value = res.data.data || [];
                }
            } catch (e) {
                console.error('loadStatusPengajuanTu error', e);
            }
        }

        function openModalPengajuanTu(detailModal) {
            modalPengajuanTu.value.siswa_nama = detailModal.student?.nama_lengkap || 'Siswa';
            modalPengajuanTu.value.siswa_kelas = detailModal.student?.nama_kelas || '-';
            modalPengajuanTu.value.total_poin = detailModal.total_poin || 0;
            modalPengajuanTu.value.form.id_siswa = detailModal.student?.id || '';
            modalPengajuanTu.value.form.jenis_panggilan = detailModal.total_poin >= 100 ? 'Surat Panggilan Orang Tua III' : (detailModal.total_poin >= 75 ? 'Surat Panggilan Orang Tua II' : 'Surat Panggilan Orang Tua I');
            modalPengajuanTu.value.form.alasan_pemanggilan = `Akumulasi pelanggaran kedisiplinan siswa telah mencapai ${detailModal.total_poin} poin. Diperlukan kehadiran orang tua/wali murid untuk klarifikasi dan pembinaan lanjutan.`;
            modalPengajuanTu.value.form.rencana_tanggal_menghadap = new Date().toISOString().split('T')[0];
            modalPengajuanTu.value.form.rencana_jam_menghadap = '09:00';
            modalPengajuanTu.value.form.ruangan = 'Ruang Konseling BK';
            modalPengajuanTu.value.show = true;
        }

        async function submitNotifikasiPengajuanTu() {
            modalPengajuanTu.value.submitting = true;
            try {
                const payload = {
                    id_siswa: modalPengajuanTu.value.form.id_siswa,
                    jenis_panggilan: modalPengajuanTu.value.form.jenis_panggilan,
                    alasan_pemanggilan: modalPengajuanTu.value.form.alasan_pemanggilan,
                    rencana_tanggal_menghadap: modalPengajuanTu.value.form.rencana_tanggal_menghadap,
                    rencana_jam_menghadap: modalPengajuanTu.value.form.rencana_jam_menghadap,
                    ruangan: modalPengajuanTu.value.form.ruangan
                };
                if (currentTenantId.value) payload.tenant_id = currentTenantId.value;

                const res = await axios.post(`${_baseUrl}/api/v1/bk/pelanggaran/notifikasi-panggilan/kirim`, payload, {
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.data?.success) {
                    Swal.fire({ icon: 'success', title: 'Terkirim!', text: res.data.message || 'Notifikasi pemanggilan berhasil dikirim ke Tata Usaha.', timer: 2000, showConfirmButton: false });
                    modalPengajuanTu.value.show = false;
                    await loadStatusPengajuanTu(modalPengajuanTu.value.form.id_siswa);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.data?.error || 'Gagal mengirim notifikasi.' });
                }
            } catch (e) {
                console.error('submitNotifikasiPengajuanTu error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || 'Terjadi kesalahan sistem.' });
            } finally {
                modalPengajuanTu.value.submitting = false;
            }
        }

        function handleSuratPanggilanUpload(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Ukuran berkas surat panggilan maksimal 2MB.' });
                    event.target.value = '';
                    return;
                }
                formTindakLanjut.value.surat_panggilan = file;
            }
        }

        async function handleFotoPembinaanUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'File harus berupa gambar (JPG, PNG, WEBP, dll).' });
                event.target.value = '';
                return;
            }
            // Auto-kompres jika di atas 1MB
            if (file.size > 1 * 1024 * 1024) {
                const toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                toast.fire({ icon: 'info', title: 'Mengompres foto... 🗜️' });
                const compressed = await compressImageBelow1MB(file, 1 * 1024 * 1024);
                formTindakLanjut.value.foto_pembinaan = compressed;
                const kb = Math.round(compressed.size / 1024);
                toast.fire({ icon: 'success', title: `Foto dikompres: ${kb} KB ✅` });
            } else {
                formTindakLanjut.value.foto_pembinaan = file;
            }
        }

        async function submitTindakLanjut(siswaId) {
            if (!formTindakLanjut.value.tanggal_tindakan) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tanggal tindakan wajib diisi.' });
                return;
            }
            if (!formTindakLanjut.value.jenis_tindakan) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Jenis tindakan wajib diisi.' });
                return;
            }
            if (!formTindakLanjut.value.keterangan_tindakan.trim()) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Keterangan/hasil tindakan wajib diisi.' });
                return;
            }

            submittingTindakLanjut.value = true;
            try {
                const formData = new FormData();
                formData.append('siswa_id', siswaId);
                formData.append('tanggal_tindakan', formTindakLanjut.value.tanggal_tindakan);
                formData.append('jenis_tindakan', formTindakLanjut.value.jenis_tindakan);
                formData.append('keterangan_tindakan', formTindakLanjut.value.keterangan_tindakan);
                if (formTindakLanjut.value.surat_panggilan) {
                    formData.append('surat_panggilan', formTindakLanjut.value.surat_panggilan);
                }
                if (formTindakLanjut.value.foto_pembinaan) {
                    formData.append('foto_pembinaan', formTindakLanjut.value.foto_pembinaan);
                }
                if (currentTenantId.value) formData.append('tenant_id', currentTenantId.value);

                const res = await axios.post(`${_baseUrl}/api/v1/bk/pelanggaran/sanksi/tindak-lanjut`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (res.data.success) {
                    Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false });
                    await openSanksiDetail(siswaId);
                    loadPelanggaranSanksi();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menyimpan catatan.' });
                }
            } catch (e) {
                console.error('submitTindakLanjut error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: (e.response && e.response.data && e.response.data.error) || 'Terjadi kesalahan.' });
            } finally {
                submittingTindakLanjut.value = false;
            }
        }

        function showFotoModal(src) {
            fotoModal.value.show = true;
            // Pastikan URL gambar selalu melalui endpoint aman jika berasal dari storage
            if (src && !src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith(_baseUrl + '/api/v1/file/serve')) {
                const cleanPath = String(src).replace(/^\/+/, '').replace(_baseUrl.replace(/^\/+/, ''), '').replace(/^\/+/, '');
                fotoModal.value.src = _baseUrl + '/api/v1/file/serve?path=' + encodeURIComponent(cleanPath);
            } else {
                fotoModal.value.src = src;
            }
        }

        function getKategoriBadge(kategori) {
            if (kategori === 'Ringan') return 'bg-info text-dark fw-bold border border-info px-2 py-1 rounded-2';
            if (kategori === 'Sedang') return 'bg-warning text-dark fw-bold border border-warning px-2 py-1 rounded-2';
            if (kategori === 'Berat') return 'bg-danger text-white fw-bold border border-danger px-2 py-1 rounded-2';
            if (kategori === 'Khusus') return 'bg-dark text-white fw-bold border border-secondary px-2 py-1 rounded-2';
            return 'bg-secondary text-white fw-bold px-2 py-1 rounded-2';
        }

        function getPoinBadgeClass(poin) {
            if (poin >= 100) return 'bg-danger text-white';
            if (poin >= 75) return 'bg-warning text-dark';
            if (poin >= 50) return 'bg-info text-dark';
            if (poin >= 25) return 'bg-secondary text-white';
            return 'bg-success text-white';
        }

        function formatTanggalIndo(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        // --- BEASISWA STATE & METHODS ---
        const allBeasiswaList = ref([]);
        const beasiswaTahunOptions = ref([]);
        const filterBeasiswaSearch = ref('');
        const filterBeasiswaTahun = ref('');
        const loadingBeasiswaList = ref(false);

        const beasiswaTahunList = computed(() => {
            const set = new Set(beasiswaTahunOptions.value || []);
            (allBeasiswaList.value || []).forEach(b => {
                if (b.tahun_menerima) set.add(Number(b.tahun_menerima));
            });
            return Array.from(set).filter(Boolean).sort((a, b) => b - a);
        });

        const filteredBeasiswaList = computed(() => {
            let list = allBeasiswaList.value || [];
            if (filterBeasiswaTahun.value) {
                list = list.filter(b => String(b.tahun_menerima) === String(filterBeasiswaTahun.value));
            }
            if (!filterBeasiswaSearch.value.trim()) return list;
            const q = filterBeasiswaSearch.value.toLowerCase().trim();
            return list.filter(b => {
                const nama = (b.nama_lengkap || '').toLowerCase();
                const nisn = (b.nisn || '').toLowerCase();
                const jenis = (b.jenis_beasiswa || '').toLowerCase();
                const sumber = (b.sumber || '').toLowerCase();
                const kelas = (b.nama_kelas || '').toLowerCase();
                const ket = (b.keterangan || '').toLowerCase();
                return nama.includes(q) || nisn.includes(q) || jenis.includes(q) || sumber.includes(q) || kelas.includes(q) || ket.includes(q);
            });
        });

        const modalBeasiswa = ref({
            show: false,
            isEdit: false,
            saving: false,
            selectedSiswa: null,
            searchSiswa: '',
            siswaOptions: [],
            loadingSearchSiswa: false,
            showDropdown: false,
            form: {
                id: '',
                siswa_id: '',
                jenis_beasiswa: '',
                sumber: '',
                tahun_menerima: new Date().getFullYear(),
                nominal: '',
                keterangan: ''
            }
        });

        async function loadAllBeasiswa() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingBeasiswaList.value = true;
            try {
                const params = new URLSearchParams();
                if (filterBeasiswaTahun.value) params.set('tahun', filterBeasiswaTahun.value);
                if (filterBeasiswaSearch.value) params.set('search', filterBeasiswaSearch.value);
                if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
                const res = await axios.get(`${_baseUrl}/api/v1/bk/beasiswa/list?${params.toString()}`);
                if (res.data && res.data.success) {
                    allBeasiswaList.value = res.data.data || [];
                    if (res.data.tahun_options) {
                        beasiswaTahunOptions.value = res.data.tahun_options;
                    }
                }
            } catch (e) {
                console.error('loadAllBeasiswa error', e);
            } finally {
                loadingBeasiswaList.value = false;
            }
        }

        function exportBeasiswaExcel() {
            const params = new URLSearchParams();
            if (filterBeasiswaTahun.value) params.set('tahun', filterBeasiswaTahun.value);
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            window.open(`${_baseUrl}/api/v1/bk/beasiswa/export?${params.toString()}`, '_blank');
        }

        function openModalTambahBeasiswa() {
            modalBeasiswa.value.show = true;
            modalBeasiswa.value.isEdit = false;
            modalBeasiswa.value.saving = false;
            modalBeasiswa.value.selectedSiswa = null;
            modalBeasiswa.value.searchSiswa = '';
            modalBeasiswa.value.siswaOptions = [];
            modalBeasiswa.value.showDropdown = false;
            modalBeasiswa.value.form = {
                id: '',
                siswa_id: '',
                jenis_beasiswa: '',
                sumber: '',
                tahun_menerima: new Date().getFullYear(),
                nominal: '',
                keterangan: ''
            };
        }

        function openModalEditBeasiswa(b) {
            modalBeasiswa.value.show = true;
            modalBeasiswa.value.isEdit = true;
            modalBeasiswa.value.saving = false;
            modalBeasiswa.value.selectedSiswa = {
                id: b.siswa_id,
                nama_lengkap: b.nama_lengkap,
                nisn: b.nisn,
                nama_kelas: b.nama_kelas
            };
            modalBeasiswa.value.form = {
                id: b.id,
                siswa_id: b.siswa_id,
                jenis_beasiswa: b.jenis_beasiswa,
                sumber: b.sumber,
                tahun_menerima: b.tahun_menerima || new Date().getFullYear(),
                nominal: b.nominal || '',
                keterangan: b.keterangan || ''
            };
        }

        let searchSiswaModalTimer = null;
        function searchSiswaModalDebounce() {
            clearTimeout(searchSiswaModalTimer);
            const q = modalBeasiswa.value.searchSiswa?.trim();
            if (!q || q.length < 1) {
                modalBeasiswa.value.siswaOptions = [];
                return;
            }
            searchSiswaModalTimer = setTimeout(async () => {
                modalBeasiswa.value.loadingSearchSiswa = true;
                try {
                    const params = new URLSearchParams({ q, limit: '15' });
                    if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
                    const res = await axios.get(`${_baseUrl}/api/v1/bk/siswa?${params.toString()}`);
                    if (res.data && res.data.success) {
                        modalBeasiswa.value.siswaOptions = res.data.data || [];
                    }
                } catch (e) {
                    console.error('searchSiswaModalDebounce error', e);
                } finally {
                    modalBeasiswa.value.loadingSearchSiswa = false;
                }
            }, 250);
        }

        function hideModalDropdownDelay() {
            setTimeout(() => {
                modalBeasiswa.value.showDropdown = false;
            }, 200);
        }

        function selectSiswaModal(s) {
            modalBeasiswa.value.selectedSiswa = s;
            modalBeasiswa.value.form.siswa_id = s.id;
            modalBeasiswa.value.showDropdown = false;
        }

        function clearSiswaModal() {
            modalBeasiswa.value.selectedSiswa = null;
            modalBeasiswa.value.form.siswa_id = '';
            modalBeasiswa.value.searchSiswa = '';
        }

        async function simpanBeasiswaModal() {
            if (!modalBeasiswa.value.form.siswa_id) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih siswa terlebih dahulu.' });
                return;
            }
            if (!modalBeasiswa.value.form.jenis_beasiswa) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Jenis beasiswa wajib diisi.' });
                return;
            }

            modalBeasiswa.value.saving = true;
            try {
                const payload = {
                    ...modalBeasiswa.value.form
                };
                if (currentTenantId.value) payload.tenant_id = currentTenantId.value;

                const res = await axios.post(`${_baseUrl}/api/v1/bk/beasiswa/save`, payload, {
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (res.data && res.data.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message || 'Data beasiswa berhasil disimpan.', timer: 1500, showConfirmButton: false });
                    modalBeasiswa.value.show = false;
                    loadAllBeasiswa();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menyimpan beasiswa.' });
                }
            } catch (e) {
                console.error('simpanBeasiswaModal error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: (e.response && e.response.data && e.response.data.error) || 'Terjadi kesalahan sistem.' });
            } finally {
                modalBeasiswa.value.saving = false;
            }
        }

        async function hapusBeasiswa(id) {
            const confirmResult = await Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus data beasiswa ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--bk-red)',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            });

            if (confirmResult.isConfirmed) {
                try {
                    const payload = { id };
                    if (currentTenantId.value) payload.tenant_id = currentTenantId.value;
                    const res = await axios.post(`${_baseUrl}/api/v1/bk/beasiswa/delete`, payload, {
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.data && res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message || 'Data beasiswa berhasil dihapus.', timer: 1500, showConfirmButton: false });
                        loadAllBeasiswa();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menghapus beasiswa.' });
                    }
                } catch (e) {
                    console.error('hapusBeasiswa error', e);
                    Swal.fire({ icon: 'error', title: 'Gagal', text: (e.response && e.response.data && e.response.data.error) || 'Terjadi kesalahan sistem.' });
                }
            }
        }

        // ─── KESIAPAN & ELIGIBILITAS (PDSS) STATE ────────────────
        const loadingKesiapan = ref(false);
        const autoCalculatingKesiapan = ref(false);
        const kesiapanList = ref([]);
        const kesiapanSummary = ref({ total_siswa: 0, total_eligible: 0, persentase_eligible: 0, max_nilai_rata_rata: '-' });
        const kesiapanFilter = ref({ tahun_ajaran_id: '', search: '', jurusan: '', status_eligible: '' });
        const selectedKuotaPct = ref(40);
        const detailNilaiModal = ref({
            show: false,
            loading: false,
            siswa: null,
            breakdown: [],
            overallAverage: '-'
        });

        // ─── SIMULASI PILIHAN KAMPUS STATE ──────────────────────────
        const loadingSimulasi = ref(false);
        const simulasiList = ref([]);
        const simulasiSettings = ref({ 1: { is_open: false }, 2: { is_open: false } });
        const simulasiFilter = ref({ tahun_ajaran_id: '' });

        // ─── MASTER KAMPUS STATE ────────────────────────────────────
        const loadingKampus = ref(false);
        const kampusList = ref([]);
        const kampusSearch = ref('');
        const kampusModal = ref({
            show: false, isEdit: false, saving: false,
            form: { id: '', nama_kampus: '', jenis: 'PTN', akreditasi: '', kota: '', provinsi: '' }
        });

        // ─── MASTER JALUR MASUK STATE ───────────────────────────────
        const loadingJalur = ref(false);
        const jalurList = ref([]);
        const jalurModal = ref({
            show: false, isEdit: false, saving: false,
            form: { id: '', nama_jalur: '', deskripsi: '', persyaratan: '' }
        });

        // ─── ALUMNI TRACKING STATE ──────────────────────────────────
        const loadingAlumniTracking = ref(false);
        const alumniTrackingList = ref([]);
        const alumniKpi = ref({ total: 0, kuliah: 0, bekerja: 0, tidak_diketahui: 0 });
        const alumniFilter = ref({ tahun_lulus: '', status: '' });
        const alumniTahunLulusList = ref([]);

        // ─── RIWAYAT KULIAH STATE ───────────────────────────────────
        const loadingKuliah = ref(false);
        const riwayatKuliahList = ref([]);

        // ─── RIWAYAT PEKERJAAN STATE ────────────────────────────────
        const loadingPekerjaan = ref(false);
        const riwayatPekerjaanList = ref([]);


        function openModalTambahBeasiswa() {
            modalBeasiswa.value.isEdit = false;
            modalBeasiswa.value.saving = false;
            modalBeasiswa.value.selectedSiswa = null;
            modalBeasiswa.value.searchSiswa = '';
            modalBeasiswa.value.siswaOptions = [];
            modalBeasiswa.value.showDropdown = false;
            modalBeasiswa.value.form = {
                id: '',
                siswa_id: '',
                jenis_beasiswa: '',
                sumber: '',
                tahun_menerima: new Date().getFullYear(),
                nominal: '',
                keterangan: ''
            };
            modalBeasiswa.value.show = true;
        }

        function openModalEditBeasiswa(b) {
            modalBeasiswa.value.isEdit = true;
            modalBeasiswa.value.saving = false;
            modalBeasiswa.value.selectedSiswa = {
                id: b.siswa_id,
                nama_lengkap: b.nama_lengkap,
                nisn: b.nisn,
                nama_kelas: b.nama_kelas
            };
            modalBeasiswa.value.searchSiswa = b.nama_lengkap;
            modalBeasiswa.value.siswaOptions = [];
            modalBeasiswa.value.showDropdown = false;
            modalBeasiswa.value.form = {
                id: b.id,
                siswa_id: b.siswa_id,
                jenis_beasiswa: b.jenis_beasiswa,
                sumber: b.sumber || '',
                tahun_menerima: b.tahun_menerima || new Date().getFullYear(),
                nominal: b.nominal || '',
                keterangan: b.keterangan || ''
            };
            modalBeasiswa.value.show = true;
        }

        let modalSearchSiswaTimeout = null;
        function searchSiswaModalDebounce() {
            clearTimeout(modalSearchSiswaTimeout);
            modalSearchSiswaTimeout = setTimeout(() => {
                const q = (modalBeasiswa.value.searchSiswa || '').trim();
                if (q.length < 2) {
                    modalBeasiswa.value.siswaOptions = [];
                    return;
                }
                modalBeasiswa.value.loadingSearchSiswa = true;
                const params = new URLSearchParams();
                params.set('q', q);
                if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
                params.set('limit', '10');

                axios.get(`${_baseUrl}/api/v1/bk/siswa?${params.toString()}`)
                    .then(res => {
                        modalBeasiswa.value.siswaOptions = res.data.data || [];
                    })
                    .catch(() => {
                        modalBeasiswa.value.siswaOptions = [];
                    })
                    .finally(() => {
                        modalBeasiswa.value.loadingSearchSiswa = false;
                    });
            }, 280);
        }

        function selectSiswaModal(s) {
            modalBeasiswa.value.selectedSiswa = s;
            modalBeasiswa.value.form.siswa_id = s.id;
            modalBeasiswa.value.searchSiswa = s.nama_lengkap;
            modalBeasiswa.value.siswaOptions = [];
            modalBeasiswa.value.showDropdown = false;
        }

        function clearSiswaModal() {
            modalBeasiswa.value.selectedSiswa = null;
            modalBeasiswa.value.form.siswa_id = '';
            modalBeasiswa.value.searchSiswa = '';
            modalBeasiswa.value.siswaOptions = [];
        }

        function hideModalDropdownDelay() {
            setTimeout(() => {
                modalBeasiswa.value.showDropdown = false;
            }, 250);
        }

        async function simpanBeasiswaModal() {
            if (!modalBeasiswa.value.form.siswa_id) {
                Swal.fire('Perhatian', 'Pilih siswa terlebih dahulu.', 'warning');
                return;
            }
            if (!modalBeasiswa.value.form.jenis_beasiswa) {
                Swal.fire('Perhatian', 'Jenis Beasiswa wajib diisi.', 'warning');
                return;
            }

            modalBeasiswa.value.saving = true;
            const payload = {
                id: modalBeasiswa.value.isEdit ? modalBeasiswa.value.form.id : undefined,
                siswa_id: modalBeasiswa.value.form.siswa_id,
                jenis_beasiswa: modalBeasiswa.value.form.jenis_beasiswa,
                sumber: modalBeasiswa.value.form.sumber,
                tahun_menerima: parseInt(modalBeasiswa.value.form.tahun_menerima) || new Date().getFullYear(),
                nominal: modalBeasiswa.value.form.nominal !== '' ? parseFloat(modalBeasiswa.value.form.nominal) : null,
                keterangan: modalBeasiswa.value.form.keterangan || ''
            };

            try {
                const res = await axios.post(`${_baseUrl}/api/v1/buku-induk/beasiswa`, payload);
                if (res.data && res.data.success) {
                    toast.fire({ icon: 'success', title: res.data.message || 'Data beasiswa berhasil disimpan.' });
                    modalBeasiswa.value.show = false;
                    loadAllBeasiswa();
                } else {
                    Swal.fire('Gagal!', res.data?.error || 'Gagal menyimpan data beasiswa.', 'error');
                }
            } catch (err) {
                console.error('simpanBeasiswaModal error:', err);
                Swal.fire('Gagal!', (err.response && err.response.data && err.response.data.error) || 'Terjadi kesalahan sistem.', 'error');
            } finally {
                modalBeasiswa.value.saving = false;
            }
        }

        // ─── PDSS & ALUMNI API METHODS ─────────────────────
        function loadKesiapan() {
            loadingKesiapan.value = true;
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            if (kesiapanFilter.value.tahun_ajaran_id) params.set('tahun_ajaran_id', kesiapanFilter.value.tahun_ajaran_id);
            if (kesiapanFilter.value.search) params.set('search', kesiapanFilter.value.search);
            if (kesiapanFilter.value.jurusan) params.set('jurusan', kesiapanFilter.value.jurusan);
            if (kesiapanFilter.value.status_eligible) params.set('status_eligible', kesiapanFilter.value.status_eligible);

            axios.get(`${_baseUrl}/api/v1/bk/kesiapan/list?${params.toString()}`)
                .then(res => {
                    kesiapanList.value = res.data.data || [];
                    if (res.data.summary) kesiapanSummary.value = res.data.summary;
                })
                .catch(() => {
                    kesiapanList.value = [];
                    toast.fire({ icon: 'error', title: 'Gagal memuat data kesiapan PDSS.' });
                })
                .finally(() => { loadingKesiapan.value = false; });
        }

        function autoCalculateKesiapan() {
            Swal.fire({
                title: 'Hitung Eligibilitas PDSS Otomatis?',
                text: `Sistem akan merangking seluruh siswa Kelas 12 berdasarkan rata-rata nilai 5 semester dan menetapkan ${selectedKuotaPct.value}% kuota eligible SNBP per jurusan.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: '⚡ Ya, Hitung Otomatis!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    autoCalculatingKesiapan.value = true;
                    axios.post(`${_baseUrl}/api/v1/bk/kesiapan/auto-calculate`, {
                        tenant_id: currentTenantId.value,
                        tahun_ajaran_id: kesiapanFilter.value.tahun_ajaran_id,
                        quota_pct: selectedKuotaPct.value
                    })
                    .then(res => {
                        Swal.fire({
                            title: 'Selesai!',
                            text: res.data.message || 'Perhitungan eligibilitas PDSS selesai.',
                            icon: 'success'
                        });
                        loadKesiapan();
                    })
                    .catch(err => {
                        Swal.fire('Gagal', err.response?.data?.error || 'Gagal menghitung eligibilitas otomatis.', 'error');
                    })
                    .finally(() => {
                        autoCalculatingKesiapan.value = false;
                    });
                }
            });
        }

        function openDetailNilaiSiswa(siswaId) {
            detailNilaiModal.value.loading = true;
            detailNilaiModal.value.show = true;
            axios.get(`${_baseUrl}/api/v1/bk/kesiapan/detail-nilai`, {
                params: { siswa_id: siswaId, tenant_id: currentTenantId.value }
            })
            .then(res => {
                detailNilaiModal.value.siswa = res.data.siswa;
                detailNilaiModal.value.breakdown = res.data.data;
                detailNilaiModal.value.overallAverage = res.data.overall_average;
            })
            .catch(() => {
                toast.fire({ icon: 'error', title: 'Gagal memuat detail nilai rapor.' });
            })
            .finally(() => {
                detailNilaiModal.value.loading = false;
            });
        }

        function toggleEligible(siswaId, currentStatus) {
            axios.post(`${_baseUrl}/api/v1/bk/kesiapan/toggle-eligible`, {
                siswa_id: siswaId,
                is_eligible: !currentStatus,
                tenant_id: currentTenantId.value
            })
            .then(res => {
                toast.fire({ icon: 'success', title: res.data.message || 'Status eligible diperbarui.' });
                loadKesiapan();
            })
            .catch(err => {
                Swal.fire('Gagal', err.response?.data?.error || 'Gagal mengubah status eligible.', 'error');
            });
        }

        function exportKesiapanExcel() {
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            if (kesiapanFilter.value.tahun_ajaran_id) params.set('tahun_ajaran_id', kesiapanFilter.value.tahun_ajaran_id);
            window.open(`${_baseUrl}/api/v1/bk/kesiapan/export?${params.toString()}`, '_blank');
        }

        function loadSimulasiSettings() {
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            axios.get(`${_baseUrl}/api/v1/bk/simulasi/setting?${params.toString()}`)
                .then(res => {
                    if (res.data.data) simulasiSettings.value = res.data.data;
                });
        }

        function loadSimulasi() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingSimulasi.value = true;
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            if (simulasiFilter.value.tahun_ajaran_id) params.set('tahun_ajaran_id', simulasiFilter.value.tahun_ajaran_id);

            axios.get(`${_baseUrl}/api/v1/bk/simulasi/list?${params.toString()}`)
                .then(res => {
                    simulasiList.value = res.data.data || [];
                })
                .catch(() => { simulasiList.value = []; })
                .finally(() => { loadingSimulasi.value = false; });
        }

        function toggleSimulasiSetting(noSimulasi) {
            const currentObj = simulasiSettings.value[noSimulasi] || { is_open: false };
            axios.post(`${_baseUrl}/api/v1/bk/simulasi/toggle-setting`, {
                no_simulasi: noSimulasi,
                is_open: !currentObj.is_open,
                tenant_id: currentTenantId.value
            })
            .then(res => {
                toast.fire({ icon: 'success', title: res.data.message || 'Pengaturan simulasi diperbarui.' });
                loadSimulasiSettings();
            });
        }

        function exportSimulasiXlsx() {
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            window.open(`${_baseUrl}/api/v1/bk/simulasi/export?${params.toString()}`, '_blank');
        }

        function loadKampus() {
            loadingKampus.value = true;
            axios.get(`${_baseUrl}/api/v1/bk/kampus/list`)
                .then(res => { kampusList.value = res.data.data || []; })
                .catch(() => { kampusList.value = []; })
                .finally(() => { loadingKampus.value = false; });
        }

        function openKampusModal(item = null) {
            if (item) {
                kampusModal.value = { show: true, isEdit: true, saving: false, form: { ...item } };
            } else {
                kampusModal.value = { show: true, isEdit: false, saving: false, form: { id: '', nama_kampus: '', jenis: 'PTN', akreditasi: 'A', kota: '', provinsi: '' } };
            }
        }

        function submitKampus() {
            if (!kampusModal.value.form.nama_kampus) {
                Swal.fire('Perhatian', 'Nama Kampus wajib diisi.', 'warning');
                return;
            }
            kampusModal.value.saving = true;
            const url = kampusModal.value.isEdit ? `${_baseUrl}/api/v1/bk/kampus/update` : `${_baseUrl}/api/v1/bk/kampus/create`;
            axios.post(url, kampusModal.value.form)
                .then(res => {
                    toast.fire({ icon: 'success', title: res.data.message || 'Data kampus disimpan.' });
                    kampusModal.value.show = false;
                    loadKampus();
                })
                .catch(err => {
                    Swal.fire('Gagal', err.response?.data?.error || 'Terjadi kesalahan.', 'error');
                })
                .finally(() => { kampusModal.value.saving = false; });
        }

        function deleteKampus(id) {
            Swal.fire({
                title: 'Hapus Master Kampus?',
                text: 'Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus'
            }).then(r => {
                if (r.isConfirmed) {
                    axios.post(`${_baseUrl}/api/v1/bk/kampus/delete`, { id })
                        .then(() => {
                            toast.fire({ icon: 'success', title: 'Kampus berhasil dihapus.' });
                            loadKampus();
                        });
                }
            });
        }

        function loadJalur() {
            loadingJalur.value = true;
            axios.get(`${_baseUrl}/api/v1/bk/jalur-masuk/list`)
                .then(res => { jalurList.value = res.data.data || []; })
                .catch(() => { jalurList.value = []; })
                .finally(() => { loadingJalur.value = false; });
        }

        function openJalurModal(item = null) {
            if (item) {
                jalurModal.value = { show: true, isEdit: true, saving: false, form: { ...item } };
            } else {
                jalurModal.value = { show: true, isEdit: false, saving: false, form: { id: '', nama_jalur: '', deskripsi: '', persyaratan: '' } };
            }
        }

        function submitJalur() {
            if (!jalurModal.value.form.nama_jalur) {
                Swal.fire('Perhatian', 'Nama Jalur Masuk wajib diisi.', 'warning');
                return;
            }
            jalurModal.value.saving = true;
            const url = jalurModal.value.isEdit ? `${_baseUrl}/api/v1/bk/jalur-masuk/update` : `${_baseUrl}/api/v1/bk/jalur-masuk/create`;
            axios.post(url, jalurModal.value.form)
                .then(res => {
                    toast.fire({ icon: 'success', title: res.data.message || 'Jalur masuk disimpan.' });
                    jalurModal.value.show = false;
                    loadJalur();
                })
                .catch(err => {
                    Swal.fire('Gagal', err.response?.data?.error || 'Terjadi kesalahan.', 'error');
                })
                .finally(() => { jalurModal.value.saving = false; });
        }

        function deleteJalur(id) {
            Swal.fire({
                title: 'Hapus Jalur Masuk?',
                text: 'Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus'
            }).then(r => {
                if (r.isConfirmed) {
                    axios.post(`${_baseUrl}/api/v1/bk/jalur-masuk/delete`, { id })
                        .then(() => {
                            toast.fire({ icon: 'success', title: 'Jalur masuk berhasil dihapus.' });
                            loadJalur();
                        });
                }
            });
        }

        function loadAlumniTracking() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingAlumniTracking.value = true;
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            if (alumniFilter.value.tahun_lulus) params.set('tahun_lulus', alumniFilter.value.tahun_lulus);
            if (alumniFilter.value.status) params.set('status', alumniFilter.value.status);

            axios.get(`${_baseUrl}/api/v1/bk/alumni/tracking?${params.toString()}`)
                .then(res => {
                    alumniTrackingList.value = res.data.data || [];
                    if (res.data.kpi) alumniKpi.value = res.data.kpi;
                    if (res.data.tahun_lulus_options) alumniTahunLulusList.value = res.data.tahun_lulus_options;
                })
                .catch(() => { alumniTrackingList.value = []; })
                .finally(() => { loadingAlumniTracking.value = false; });
        }

        function loadRiwayatKuliah() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingKuliah.value = true;
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);

            axios.get(`${_baseUrl}/api/v1/bk/alumni/riwayat-kuliah?${params.toString()}`)
                .then(res => { riwayatKuliahList.value = res.data.data || []; })
                .catch(() => { riwayatKuliahList.value = []; })
                .finally(() => { loadingKuliah.value = false; });
        }

        function deleteKuliah(id) {
            Swal.fire({
                title: 'Hapus Riwayat Kuliah?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus'
            }).then(r => {
                if (r.isConfirmed) {
                    axios.post(`${_baseUrl}/api/v1/bk/alumni/riwayat-kuliah/delete`, { id })
                        .then(() => {
                            toast.fire({ icon: 'success', title: 'Data riwayat kuliah dihapus.' });
                            loadRiwayatKuliah();
                        });
                }
            });
        }

        function exportKuliahExcel() {
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            window.open(`${_baseUrl}/api/v1/bk/alumni/riwayat-kuliah/export?${params.toString()}`, '_blank');
        }

        function openKuliahModal() {
            Swal.fire('Info', 'Form tambah riwayat kuliah dapat diisi melalui menu Tracer Study.', 'info');
        }

        function loadRiwayatPekerjaan() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingPekerjaan.value = true;
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);

            axios.get(`${_baseUrl}/api/v1/bk/alumni/riwayat-pekerjaan?${params.toString()}`)
                .then(res => { riwayatPekerjaanList.value = res.data.data || []; })
                .catch(() => { riwayatPekerjaanList.value = []; })
                .finally(() => { loadingPekerjaan.value = false; });
        }

        function deletePekerjaan(id) {
            Swal.fire({
                title: 'Hapus Riwayat Pekerjaan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus'
            }).then(r => {
                if (r.isConfirmed) {
                    axios.post(`${_baseUrl}/api/v1/bk/alumni/riwayat-pekerjaan/delete`, { id })
                        .then(() => {
                            toast.fire({ icon: 'success', title: 'Data riwayat pekerjaan dihapus.' });
                            loadRiwayatPekerjaan();
                        });
                }
            });
        }

        function exportPekerjaanExcel() {
            const params = new URLSearchParams();
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            window.open(`${_baseUrl}/api/v1/bk/alumni/riwayat-pekerjaan/export?${params.toString()}`, '_blank');
        }

        function openPekerjaanModal() {
            Swal.fire('Info', 'Form tambah riwayat pekerjaan dapat diisi melalui menu Tracer Study.', 'info');
        }

        // ─── Init ────────────────────────────────────────────
        onMounted(() => {
            switchTab(activeTab.value);
        });

        return {
            activeTab, switchTab, kpi, pieColors,
            loadingDashboard,
            // Penjurusan
            loadingPenjurusan, penjurusanData, penjurusanSummary, jurusanList,
            filterPenjurusan, filteredPenjurusan, alertPenjurusan,
            overrideModal, loadingOverride,
            loadPenjurusan, doVerifikasi, openOverride, submitOverride, doToggleKunci,
            statusStyle,
            // Tracer
            loadingTracer, tracerData,
            // Jurnal — Rekam Kasus
            loadingKasus, loadingKasusList, kasusList,
            kasusListSearch, kasusStatusFilter, filteredKasusList,
            alertJurnal, kasusSearchSiswa, siswaOptions,
            selectedSiswaInfo, formKasus, today,
            showSiswaDropdown, siswaHover, loadingSearchSiswa,
            kelasList, filterKelasId,
            submitKasus, loadKasus, loadKelasList,
            searchSiswaDebounce, selectSiswa, clearSiswa,
            onFilterKelasChange, onSearchFocus, hideDropdownDelay,
            canEditKasus, openChangeStatus, openLogs, openTambahKasusModal, openEditKasus, closeKasusModal,
            // Prestasi Siswa
            activeYearsList, prestasiList, filterPrestasiTahunAjaran, filterPrestasiTingkat, filterPrestasiSearch, exportPrestasiExcel, filteredPrestasiList, guruList, loadingPrestasi, loadingPrestasiList,
            prestasiSearchSiswa, prestasiSiswaOptions, selectedPrestasiSiswa,
            showPrestasiSiswaDropdown, loadingSearchPrestasiSiswa, alertPrestasi, formPrestasi,
            searchSiswaPrestasiDebounce, hidePrestasiDropdownDelay, selectSiswaPrestasi,
            removeSiswaPrestasi, handleFileUpload, getFileUrl, submitPrestasi, loadPrestasi, loadGuruList, autoCalculatePrestasiPoint,
            editPrestasi, deletePrestasi, clearFormPrestasi, openTambahPrestasiModal, openEditPrestasiModal, closePrestasiModal, userRole, baseUrl, currentTenantId,
            // Kehadiran
            loadingKehadiran, savingKehadiran, importingKehadiran, filterKehadiran, tahunAjaranList,
            kehadiranData, listKelasKehadiran, isKehadiranLocked,
            modalImportKehadiran, modalExportKehadiran,
            openModalImportKehadiran, closeModalImportKehadiran, handleModalFileImportChange, unduhTemplateKehadiran, submitModalImportKehadiran,
            openModalExportKehadiran, closeModalExportKehadiran, submitModalExportKehadiran,
            getNamaKelas, getNamaTahunAjaran,
            loadKehadiran, loadKelasKehadiran, isCellDirty, isRowDirty, setAllEmptyToZero,
            incrementAbsen, decrementAbsen, saveKehadiran, exportKehadiran, toggleLockKehadiran,
            handleGridKeydown,
            // Pelanggaran & Poin
            activeSubTab, switchSubTab,
            loadingPelanggaranDashboard, pelanggaranKpi, pelanggaranTopStudents,
            loadingPelanggaranMaster, pelanggaranMasterList, masterModal,
            submittingPelanggaran, pelanggaranSearchSiswa, pelanggaranSiswaOptions,
            selectedPelanggaranSiswa, loadingSearchPelanggaranSiswa, showPelanggaranSiswaDropdown, siswaHoverPelanggaran,
            formInputPelanggaran, loadingPelanggaranCatatan, pelanggaranCatatanList, fotoModal,
            loadingPelanggaranSanksi, pelanggaranSanksiList,
            loadPelanggaranDashboard, loadPelanggaranMaster, masterRulesFiltered, filteredCatatanPelanggaran, filteredSanksiBuku, openMasterModal,
            submitMasterRule, deleteMasterRule, searchSiswaPelanggaranDebounce, hidePelanggaranDropdownDelay,
            selectSiswaPelanggaran, clearSiswaPelanggaran, handleFotoUpload, submitPelanggaran, editPelanggaran, deletePelanggaran,
            openPelanggaranModal, closePelanggaranModal,
            // Pagination Properties
            currentPageKasus, perPageKasus, totalKasusPages, paginatedKasusList,
            currentPagePelanggaran, perPagePelanggaran, totalPelanggaranPages, paginatedCatatanPelanggaran,
            currentPageSanksi, perPageSanksi, totalSanksiPages, paginatedSanksiBuku,
            currentPageMaster, perPageMaster, totalMasterPages, paginatedPelanggaranMasterList,
            currentPagePrestasi, perPagePrestasi, totalPrestasiPages, paginatedPrestasiList,
            loadPelanggaranCatatan, loadPelanggaranSanksi, showFotoModal, getKategoriBadge, secureFileUrl,
            getPoinBadgeClass, formatTanggalIndo, sanksiSearch, sanksiStatus, sanksiDetailModal,
            statusPengajuanTuList, modalPengajuanTu, loadStatusPengajuanTu, openModalPengajuanTu, submitNotifikasiPengajuanTu,
            formTindakLanjut, submittingTindakLanjut, openSanksiDetail, submitTindakLanjut,
            handleSuratPanggilanUpload, handleFotoPembinaanUpload,
            // Beasiswa
            toast, allBeasiswaList, filterBeasiswaSearch, filterBeasiswaTahun, beasiswaTahunList, beasiswaTahunOptions, loadingBeasiswaList, filteredBeasiswaList,
            modalBeasiswa, openModalTambahBeasiswa, openModalEditBeasiswa, searchSiswaModalDebounce, selectSiswaModal, clearSiswaModal,
            hideModalDropdownDelay, simpanBeasiswaModal, loadAllBeasiswa, exportBeasiswaExcel, hapusBeasiswa,
            // PDSS & Akademik
            loadingKesiapan, autoCalculatingKesiapan, kesiapanList, kesiapanSummary, kesiapanFilter, selectedKuotaPct, detailNilaiModal,
            loadKesiapan, autoCalculateKesiapan, openDetailNilaiSiswa, toggleEligible, exportKesiapanExcel,
            loadingSimulasi, simulasiList, simulasiSettings, simulasiFilter, loadSimulasi, loadSimulasiSettings, toggleSimulasiSetting, exportSimulasiXlsx,
            loadingKampus, kampusList, kampusSearch, kampusModal, loadKampus, openKampusModal, submitKampus, deleteKampus,
            loadingJalur, jalurList, jalurModal, loadJalur, openJalurModal, submitJalur, deleteJalur,
            // Alumni
            loadingAlumniTracking, alumniTrackingList, alumniKpi, alumniFilter, alumniTahunLulusList, loadAlumniTracking,
            loadingKuliah, riwayatKuliahList, loadRiwayatKuliah, deleteKuliah, exportKuliahExcel, openKuliahModal,
            loadingPekerjaan, riwayatPekerjaanList, loadRiwayatPekerjaan, deletePekerjaan, exportPekerjaanExcel, openPekerjaanModal
        };
    }
});
if (window.VueAppRegistry && typeof window.VueAppRegistry.mountAll === 'function') {
    window.VueAppRegistry.mountAll();
}
}

// Super Admin tenant filter
<?php if ($userRole === 'super_admin'): ?>
(function() {
    let btn = document.getElementById('btn-apply-tenant'); if(btn) btn.addEventListener('click', function() {
        const tid = (document.getElementById('sa-tenant-select') ? document.getElementById('sa-tenant-select').value : null) || '';
        const url = new URL(window.location.href);
        if (tid) { url.searchParams.set('tenant_id', tid); }
        else { url.searchParams.delete('tenant_id'); }
        window.location.href = url.toString();
    });
})();
<?php endif; ?>
</script>

