<?php
/**
 * View: Bimbingan Konseling — Master BK
 * Module hub dengan 5 horizontal tab: Dashboard, Penjurusan, Tracer Study, PDSS, Jurnal BK
 */
$userRole   = $data['user_role']   ?? ($_SESSION['role_name']    ?? '');
$userNama   = $data['user_nama']   ?? ($_SESSION['nama_lengkap'] ?? '');
$tenantId   = $data['tenant_id']   ?? '';
$tenantList = $data['tenant_list'] ?? [];
$tahunAjaranList = $data['tahun_ajaran_list'] ?? [];
$baseUrl    = '/SINTA-SaaS';
?>

<style>
    /* ─── Design Tokens ─────────────────────────────── */
    :root {
        --bk-primary:   #7c3aed;   /* Violet — identitas BK */
        --bk-p-light:   #f5f3ff;
        --bk-green:     #10b981;
        --bk-amber:     #f59e0b;
        --bk-red:       #ef4444;
        --bk-blue:      #2563eb;
        --bk-border:    #e2e8f0;
        --bk-bg:        #f8fafc;
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
</style>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            <i class="bi bi-heart-pulse-fill me-2" style="color:var(--bk-primary);"></i>
            Bimbingan Konseling
        </h2>
        <p class="text-muted fs-7 mb-0">
            Pusat monitoring, konseling, dan rekam kasus siswa.
            <span class="badge ms-1 rounded-pill" style="background:var(--bk-p-light);color:var(--bk-primary);">
                <?= htmlspecialchars(strtoupper($userRole)) ?>
            </span>
        </p>
    </div>
</div>

<!-- Super Admin: Pilih Sekolah Terlebih Dahulu -->
<?php if ($userRole === 'super_admin'): ?>
<div class="alert border-0 rounded-4 p-3 mb-4 d-flex align-items-center gap-3"
     style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);">
    <i class="bi bi-funnel-fill fs-4" style="color:var(--bk-primary);"></i>
    <div class="d-flex align-items-center gap-2 flex-wrap w-100">
        <label for="sa-tenant-select" class="fw-semibold text-dark mb-0" style="white-space:nowrap;">
            Filter Sekolah (Super Admin):
        </label>
        <select id="sa-tenant-select" name="sa-tenant-select" class="form-select form-select-sm rounded-3" style="max-width:320px;">
            <option value="">— Semua Sekolah —</option>
            <?php foreach ($tenantList as $t): ?>
            <option value="<?= htmlspecialchars($t['id']) ?>"
                <?= ($t['id'] === $tenantId ? 'selected' : '') ?>>
                <?= htmlspecialchars($t['nama_sekolah']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary rounded-3" id="btn-apply-tenant">
            <i class="bi bi-funnel me-1"></i> Terapkan Filter
        </button>
    </div>
</div>
<?php endif; ?>

<!-- ─── Vue App Mount ────────────────────────────────────────── -->
<div id="bkApp" v-cloak>

    <!-- ═══ HORIZONTAL TAB NAVIGATION ═══════════════════════════ -->
    <div class="bk-tabs mb-4" id="bk-tab-nav">
        <button class="bk-tab-btn" :class="{'active': activeTab === 'dashboard'}"
                @click="switchTab('dashboard')" id="tab-dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
        </button>
        <button class="bk-tab-btn" :class="{'active': activeTab === 'penjurusan'}"
                @click="switchTab('penjurusan')" id="tab-tab-penjurusan">
            <i class="bi bi-diagram-3"></i> Penjurusan Mandiri
        </button>
        <button class="bk-tab-btn" :class="{'active': activeTab === 'tracer'}"
                @click="switchTab('tracer')" id="tab-tracer">
            <i class="bi bi-mortarboard"></i> Tracer Study
        </button>

        <button class="bk-tab-btn" :class="{'active': activeTab === 'jurnal'}"
                @click="switchTab('jurnal')" id="tab-jurnal">
            <i class="bi bi-journal-text"></i> Rekam Kasus & Jurnal
        </button>
        <button class="bk-tab-btn" :class="{'active': activeTab === 'prestasi'}"
                @click="switchTab('prestasi')" id="tab-prestasi">
            <i class="bi bi-trophy"></i> Prestasi Siswa
        </button>
        <button class="bk-tab-btn" :class="{'active': activeTab === 'kehadiran'}"
                @click="switchTab('kehadiran')" id="tab-kehadiran">
            <i class="bi bi-calendar-check"></i> Kehadiran Semester
        </button>
        <button class="bk-tab-btn" :class="{'active': activeTab === 'pelanggaran'}"
                @click="switchTab('pelanggaran')" id="tab-pelanggaran">
            <i class="bi bi-shield-exclamation"></i> Tata Tertib & Poin
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 1: DASHBOARD MONITORING
    ════════════════════════════════════════════════════════════ -->
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
                                <div class="kpi-value text-dark">{{ kpi.total_siswa_aktif }}</div>
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
                                <div class="kpi-value" style="color:var(--bk-amber);">{{ kpi.kasus_bulan_ini }}</div>
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
                                <div class="kpi-value" style="color:var(--bk-red);">{{ kpi.kasus_terbuka }}</div>
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
                                <div class="kpi-value" style="color:var(--bk-green);">{{ kpi.total_alumni }}</div>
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
                <div v-if="kpi.distribusi_kasus && kpi.distribusi_kasus.length > 0">
                    <div class="row g-2">
                        <div v-for="(item, idx) in kpi.distribusi_kasus" :key="idx" class="col-md-4">
                            <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:var(--bk-bg);">
                                <span class="pie-legend-dot" :style="'background:' + pieColors[idx % pieColors.length]"></span>
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

    <!-- ═══════════════════════════════════════════════════════════
         TAB 2: PENJURUSAN MANDIRI
    ════════════════════════════════════════════════════════════ -->
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
            <div v-if="penjurusanSummary.length > 0" class="row g-3 mb-4">
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
                <div v-if="penjurusanData.length > 0">
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

    <!-- ═══════════════════════════════════════════════════════════
         TAB 3: TRACER STUDY
    ════════════════════════════════════════════════════════════ -->
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



    <!-- ═══════════════════════════════════════════════════════════
         TAB 5: REKAM KASUS & JURNAL BK
    ════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'jurnal'">

        <!-- Alert Feedback -->
        <div v-if="alertJurnal.msg" :class="'alert alert-' + alertJurnal.type + ' border-0 rounded-4 mb-3'" role="alert">
            {{ alertJurnal.msg }}
            <button type="button" class="btn-close float-end" @click="alertJurnal.msg = ''"></button>
        </div>

        <div class="row g-4">
            <!-- Form Tambah Kasus (Kiri / Atas) -->
            <div class="col-lg-5">
                <div class="bk-form-card p-4 h-100">
                    <h6 class="fw-bold mb-3" style="color:var(--bk-primary);">
                        <i class="bi bi-plus-circle-fill me-2"></i>Rekam Kasus Baru
                    </h6>

                    <!-- A. TAMPILAN JIKA SISWA BELUM DIPILIH (Pencarian Nama) -->
                    <div v-if="!formKasus.id_siswa" class="animate-fade-in">
                        <h6 class="fw-bold mb-3" style="color:var(--bk-primary);">
                            <i class="bi bi-search me-2"></i>Pencarian Siswa
                        </h6>
                        <p class="text-muted fs-8 mb-4">
                            Cari nama siswa terlebih dahulu untuk mulai merekam kasus konseling.
                        </p>
                        
                        <div class="mb-3">
                            <label for="input-siswa-kasus" class="form-label fw-semibold fs-7">Cari Nama Siswa <span class="text-danger">*</span></label>
                            <div style="position:relative;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" class="form-control rounded-end-3 fs-7"
                                           v-model="kasusSearchSiswa"
                                           @input="searchSiswaDebounce"
                                           @blur="hideDropdownDelay"
                                           @focus="onSearchFocus"
                                           placeholder="Ketik nama siswa..."
                                           id="input-siswa-kasus"
                                           name="siswa_kasus"
                                           autocomplete="off">
                                </div>

                                <!-- Dropdown autocomplete -->
                                <div v-show="showSiswaDropdown && siswaOptions.length > 0"
                                     class="border rounded-3 bg-white shadow mt-1"
                                     style="position:absolute;z-index:200;max-height:220px;overflow-y:auto;width:100%;">
                                    <div v-for="s in siswaOptions" :key="s.id"
                                         class="px-3 py-2 border-bottom hover-bg"
                                         @mousedown.prevent="selectSiswa(s)"
                                         style="cursor:pointer;font-size:0.82rem;"
                                         :style="{background: siswaHover===s.id?'#eff6ff':'#fff'}"
                                         @mouseenter="siswaHover=s.id" @mouseleave="siswaHover=null">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="fw-semibold text-dark">{{ s.nama_lengkap }}</div>
                                            <span class="badge rounded-pill" style="background:#eff6ff;color:#1e40af;font-size:.68rem;border:1px solid #bfdbfe;">
                                                {{ s.nama_kelas || 'Tanpa Kelas' }}
                                            </span>
                                        </div>
                                        <div class="d-flex gap-2 mt-1 flex-wrap" style="font-size: 0.72rem;">
                                            <span v-if="s.nisn" class="text-muted">NISN: {{ s.nisn }}</span>
                                            <span v-if="s.nis" class="text-muted">| NIS: {{ s.nis }}</span>
                                        </div>
                                    </div>
                                    <div v-if="loadingSearchSiswa" class="text-center py-2 text-muted fs-8">
                                        <span class="spinner-border spinner-border-sm me-1"></span>Mencari...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- B. TAMPILAN JIKA SISWA SUDAH DIPILIH (Form Rekam Kasus Dinamis) -->
                    <div v-else class="animate-fade-in">
                        <h6 class="fw-bold mb-3" style="color:var(--bk-primary);">
                            <i class="bi bi-journal-plus me-2"></i>Detail Rekam Kasus
                        </h6>

                        <!-- Beautiful Selected Student Card -->
                        <div class="p-3 mb-3 rounded-4 shadow-sm border" style="background:#fff;border-color:var(--bk-border) !important;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="text-truncate" style="max-width: 75%;">
                                    <h6 class="fw-bold text-dark mb-0 text-truncate" title="Nama Lengkap">{{ selectedSiswaInfo.nama_lengkap }}</h6>
                                    <p class="text-muted fs-8 mb-0 text-truncate">{{ selectedSiswaInfo.nama_jurusan || 'Tanpa Jurusan' }}</p>
                                </div>
                                <button class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1 fs-8 fw-semibold d-flex align-items-center gap-1"
                                        style="color:#334155; border-color:#94a3b8;"
                                        type="button" @click="clearSiswa" id="btn-ganti-siswa" title="Pilih siswa lain">
                                    <i class="bi bi-arrow-left-right"></i> Ganti
                                </button>
                            </div>
                            
                            <div class="row g-2 mt-2 pt-2 border-top">
                                <div class="col-6">
                                    <small class="text-muted d-block fs-9 mb-1">KELAS (SNAPSHOT)</small>
                                    <span class="badge w-100 rounded-3 text-start py-2 px-2 text-truncate" style="background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe; font-size:0.75rem;">
                                        <i class="bi bi-camera me-1"></i>{{ selectedSiswaInfo.nama_kelas || '—' }}
                                    </span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block fs-9 mb-1">NISN</small>
                                    <span class="badge w-100 rounded-3 text-start py-2 px-2 text-truncate" style="background:#fffbeb;color:#92400e;border:1px solid #fde68a; font-size:0.75rem;">
                                        <i class="bi bi-card-text me-1"></i>{{ selectedSiswaInfo.nisn || '—' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-2 pt-1" v-if="selectedSiswaInfo.nis">
                                <div class="col-12">
                                    <small class="text-muted d-block fs-9 mb-1">NIS</small>
                                    <span class="badge w-100 rounded-3 text-start py-2 px-2 text-truncate" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0; font-size:0.75rem;">
                                        <i class="bi bi-person-badge me-1"></i>{{ selectedSiswaInfo.nis }}
                                    </span>
                                </div>
                            </div>

                            <!-- Snapshot Info Hint -->
                            <div class="autofill-hint mt-2">
                                <i class="bi bi-shield-lock-fill flex-shrink-0" style="color:#7c3aed;margin-top:1px;"></i>
                                <span style="font-size:0.65rem;color:#64748b;">
                                    Kelas saat ini akan **dikunci sebagai snapshot historis** saat data disimpan.
                                </span>
                            </div>
                        </div>

                        <!-- Form Input Details -->
                        <div class="mb-3">
                            <label for="input-tgl-konseling" class="form-label fw-semibold fs-7">Tanggal Pembuatan / Catat Kasus <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-3"
                                   v-model="formKasus.tanggal_konseling"
                                   :max="today" id="input-tgl-konseling" name="tanggal_konseling">
                        </div>

                        <div class="mb-3">
                            <label for="select-jenis-kasus" class="form-label fw-semibold fs-7">Jenis Kasus <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" v-model="formKasus.jenis_kasus" id="select-jenis-kasus" name="jenis_kasus">
                                <option value="">-- Pilih --</option>
                                <option value="Akademik">Akademik</option>
                                <option value="Perilaku">Perilaku</option>
                                <option value="Keluarga">Keluarga</option>
                                <option value="Karir">Karir</option>
                                <option value="Kesehatan Mental">Kesehatan Mental</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="input-catatan-kasus" class="form-label fw-semibold fs-7">Catatan Konseling <span class="text-danger">*</span></label>
                            <textarea class="form-control rounded-3" rows="4"
                                      v-model="formKasus.catatan"
                                      placeholder="Tuliskan catatan observasi, keluhan, dan temuan konseling secara lengkap..."
                                      id="input-catatan-kasus" name="catatan_kasus"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="input-tindak-lanjut" class="form-label fw-semibold fs-7">Tindak Lanjut</label>
                            <textarea class="form-control rounded-3" rows="2"
                                      v-model="formKasus.tindak_lanjut"
                                      placeholder="Rencana tindak lanjut dan rekomendasi..."
                                      id="input-tindak-lanjut" name="tindak_lanjut"></textarea>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label for="select-status-kasus" class="form-label fw-semibold fs-7">Status Kasus</label>
                                <select class="form-select rounded-3" v-model="formKasus.status_kasus" id="select-status-kasus" name="status_kasus">
                                    <option value="Terbuka">Terbuka</option>
                                    <option value="Proses">Dalam Proses</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="cb-rahasia" name="is_rahasia"
                                           v-model="formKasus.is_rahasia" :true-value="1" :false-value="0">
                                    <label class="form-check-label fw-semibold fs-7" for="cb-rahasia">
                                        <i class="bi bi-lock-fill me-1 text-warning"></i>Rahasia
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons Row -->
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary rounded-3 w-50" style="color:#334155; border-color:#94a3b8;" type="button" @click="clearSiswa" id="btn-batal-kasus">
                                Batal
                            </button>
                            <button class="btn rounded-3 fw-semibold w-50 text-white" :disabled="loadingKasus"
                                    @click="submitKasus" id="btn-simpan-kasus"
                                    style="background:var(--bk-primary);">
                                <span v-if="loadingKasus" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-floppy me-2"></i>
                                {{ loadingKasus ? 'Menyimpan...' : 'Rekam Kasus' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Kasus (Kanan / Bawah) -->
            <div class="col-lg-7">
                <div class="bk-card p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0" style="color:var(--bk-primary);">
                                <i class="bi bi-journal-text me-2"></i>Riwayat Kasus
                            </h6>
                            <small class="text-muted fs-8" v-if="kasusList.length > 0">
                                {{ kasusList.length }} catatan ditemukan
                            </small>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary rounded-3" style="color:#334155; border-color:#94a3b8;"
                                @click="loadKasus" :disabled="loadingKasusList">
                            <span v-if="loadingKasusList" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>

                    <!-- Search filter lokal -->
                    <div v-if="kasusList.length > 0" class="mb-3">
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

                    <div v-if="loadingKasusList" class="text-center py-4">
                        <div class="spinner-border" style="color:var(--bk-primary);"></div>
                    </div>

                    <div v-else-if="filteredKasusList.length > 0" class="table-responsive" style="max-height:480px;overflow-y:auto;">
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
                                <tr v-for="k in filteredKasusList" :key="k.id">
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

                    <!-- Empty state setelah filter -->
                    <div v-else-if="kasusList.length > 0 && filteredKasusList.length === 0"
                         class="text-center py-4 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2"></i>
                        Tidak ada kasus yang cocok dengan filter "<strong>{{ kasusListSearch }}</strong>".
                    </div>

                    <!-- Benar-benar kosong -->
                    <div v-else class="text-center py-5 text-muted">
                        <i class="bi bi-journal fs-1 d-block mb-2"></i>
                        Belum ada catatan kasus. Rekam kasus baru di panel sebelah kiri.
                    </div>

                    <!-- Info badge snapshot -->
                    <div v-if="kasusList.length > 0" class="mt-3 rounded-3 px-3 py-2 d-flex align-items-start gap-2"
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
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 6: PRESTASI SISWA
    ════════════════════════════════════════════════════════════ -->
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
            <!-- Panel Kiri: Form Input/Edit Prestasi -->
            <div class="col-lg-5">
                <div class="bk-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-trophy-fill me-2" style="color:var(--bk-primary);"></i>
                            {{ formPrestasi.id ? 'Edit Data Prestasi' : 'Tambah Data Prestasi' }}
                        </h6>
                        <button v-if="formPrestasi.id" @click="clearFormPrestasi" class="btn btn-xs btn-outline-secondary rounded-3 py-1 px-2 fw-semibold" style="font-size: 0.72rem;">
                            <i class="bi bi-plus-circle me-1"></i> Mode Tambah
                        </button>
                    </div>

                    <!-- Alert Info / Warning Form -->
                    <div v-if="alertPrestasi.msg" :class="'alert alert-' + alertPrestasi.type + ' border-0 rounded-3 py-2 px-3 mb-3 fs-7 animate-fade-in'">
                        {{ alertPrestasi.msg }}
                    </div>                    <form @submit.prevent="submitPrestasi" enctype="multipart/form-data">
                        <!-- Siswa Selection -->
                        <div class="mb-3">
                            <label for="input-prestasi-cari-siswa" class="form-label fw-bold fs-7 mb-1 text-dark">Pilih Siswa <span class="text-danger">*</span></label>
                            <div class="position-relative">
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
                                <div v-if="showPrestasiSiswaDropdown && prestasiSiswaOptions.length > 0" 
                                     class="position-absolute w-100 bg-white border rounded-3 shadow-lg p-1 mt-1 z-3"
                                     style="max-height: 250px; overflow-y: auto;">
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
                            <div v-if="selectedPrestasiSiswa.length > 0" class="mt-2 d-flex flex-wrap gap-2">
                                <div v-for="s in selectedPrestasiSiswa" :key="s.id" 
                                     class="badge d-inline-flex align-items-center gap-2 p-2 rounded-3 text-dark" 
                                     style="background: var(--bk-p-light); color: var(--bk-primary); border: 1px solid #ddd;">
                                    <span class="fw-semibold">{{ s.nama_lengkap }} ({{ s.nama_kelas || '-' }})</span>
                                    <button type="button" class="btn-close" style="font-size: 0.6rem; margin-left: 5px;" @click="removeSiswaPrestasi(s.id)"></button>
                                </div>
                            </div>
                        </div>

                        <!-- Kategori & Tahun Ajaran & Semester -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="select-prestasi-kategori" class="form-label fw-bold fs-7 mb-1 text-dark">Kategori Kepesertaan <span class="text-danger">*</span></label>
                                <select id="select-prestasi-kategori" name="kategori" v-model="formPrestasi.kategori" class="form-select form-select-sm rounded-3">
                                    <option value="Personal">Personal (Individu)</option>
                                    <option value="Regu">Regu (Kelompok)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="select-prestasi-tahun-ajaran" class="form-label fw-bold fs-7 mb-1 text-dark">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select id="select-prestasi-tahun-ajaran" name="tahun_ajaran_id" v-model="formPrestasi.tahun_ajaran_id" class="form-select form-select-sm rounded-3">
                                    <option value="">— Pilih Tahun —</option>
                                    <option v-for="y in activeYearsList" :key="y.id" :value="y.id">
                                        {{ y.tahun_ajaran }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="select-prestasi-semester" class="form-label fw-bold fs-7 mb-1 text-dark">Semester <span class="text-danger">*</span></label>
                                <select id="select-prestasi-semester" name="semester" v-model="formPrestasi.semester" class="form-select form-select-sm rounded-3">
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="select-prestasi-tingkat" class="form-label fw-bold fs-7 mb-1 text-dark">Tingkat Kejuaraan <span class="text-danger">*</span></label>
                                <select id="select-prestasi-tingkat" name="tingkat_kejuaraan" v-model="formPrestasi.tingkat_kejuaraan" class="form-select form-select-sm rounded-3">
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
                            <input type="text" id="input-prestasi-bidang" name="bidang_lomba" v-model="formPrestasi.bidang_lomba" class="form-control form-control-sm rounded-3" placeholder="Contoh: Sains/OSN, Olahraga/O2SN, Seni, dll." />
                        </div>

                        <div class="mb-3">
                            <label for="input-prestasi-nama-lomba" class="form-label fw-bold fs-7 mb-1 text-dark">Nama Perlombaan / Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" id="input-prestasi-nama-lomba" name="nama_lomba" v-model="formPrestasi.nama_lomba" class="form-control form-control-sm rounded-3" placeholder="Contoh: Olimpiade Matematika Nasional 2026" />
                        </div>

                        <!-- Kategori Juara & Nomor Sertifikat -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="select-prestasi-juara" class="form-label fw-bold fs-7 mb-1 text-dark">Peringkat Juara <span class="text-danger">*</span></label>
                                <select id="select-prestasi-juara" name="juara" v-model="formPrestasi.juara" class="form-select form-select-sm rounded-3">
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
                            <div class="col-md-6" v-if="formPrestasi.juara === 'Lainnya'">
                                <label for="input-prestasi-juara-lainnya" class="form-label fw-bold fs-7 mb-1 text-dark">Keterangan Juara <span class="text-danger">*</span></label>
                                <input type="text" id="input-prestasi-juara-lainnya" name="juara_lainnya" v-model="formPrestasi.juara_lainnya" class="form-control form-control-sm rounded-3" placeholder="Contoh: Gold Medal / Juara Favorit" />
                            </div>
                            <div class="col-md-6" v-else>
                                <label for="input-prestasi-sertifikat" class="form-label fw-bold fs-7 mb-1 text-dark">Nomor Sertifikat / Piagam</label>
                                <input type="text" id="input-prestasi-sertifikat" name="nomor_sertifikat" v-model="formPrestasi.nomor_sertifikat" class="form-control form-control-sm rounded-3" placeholder="No. Sertifikat jika ada" />
                            </div>
                        </div>

                        <div class="mb-3" v-if="formPrestasi.juara === 'Lainnya'">
                            <label for="input-prestasi-sertifikat-lainnya" class="form-label fw-bold fs-7 mb-1 text-dark">Nomor Sertifikat / Piagam</label>
                            <input type="text" id="input-prestasi-sertifikat-lainnya" name="nomor_sertifikat" v-model="formPrestasi.nomor_sertifikat" class="form-control form-control-sm rounded-3" placeholder="No. Sertifikat jika ada" />
                        </div>

                        <!-- Detail Pelaksanaan -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="select-prestasi-jenis" class="form-label fw-bold fs-7 mb-1 text-dark">Jenis Pelaksanaan <span class="text-danger">*</span></label>
                                <select id="select-prestasi-jenis" name="jenis_lomba" v-model="formPrestasi.jenis_lomba" class="form-select form-select-sm rounded-3">
                                    <option value="Offline">Offline (Luring)</option>
                                    <option value="Online">Online (Daring)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="input-prestasi-tanggal" class="form-label fw-bold fs-7 mb-1 text-dark">Tanggal Kegiatan <span class="text-danger">*</span></label>
                                <input type="date" id="input-prestasi-tanggal" name="tanggal_lomba" v-model="formPrestasi.tanggal_lomba" class="form-control form-control-sm rounded-3" />
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="input-prestasi-tempat" class="form-label fw-bold fs-7 mb-1 text-dark">Tempat / Kota <span class="text-danger">*</span></label>
                                <input type="text" id="input-prestasi-tempat" name="tempat_lomba" v-model="formPrestasi.tempat_lomba" class="form-control form-control-sm rounded-3" placeholder="Contoh: Jakarta" />
                            </div>
                            <div class="col-md-6">
                                <label for="input-prestasi-penyelenggara" class="form-label fw-bold fs-7 mb-1 text-dark">Penyelenggara <span class="text-danger">*</span></label>
                                <input type="text" id="input-prestasi-penyelenggara" name="penyelenggara" v-model="formPrestasi.penyelenggara" class="form-control form-control-sm rounded-3" placeholder="Contoh: Puspresnas / Kemendikbud" />
                            </div>
                        </div>

                        <!-- Guru Pendamping -->
                        <div class="mb-3">
                            <label for="input-prestasi-guru" class="form-label fw-bold fs-7 mb-1 text-dark">Guru Pendamping</label>
                            <input type="text" id="input-prestasi-guru" name="guru_pendamping" v-model="formPrestasi.guru_pendamping" class="form-control form-control-sm rounded-3" placeholder="Tulis nama Guru Pendamping jika ada (Opsional)" />
                        </div>

                        <!-- Files Upload -->
                        <div class="border rounded-3 p-3 bg-light mb-4">
                            <div class="fw-bold fs-7 text-dark mb-2"><i class="bi bi-file-earmark-arrow-up me-1"></i>Berkas Pendukung (Masing-masing maks. 1 MB)</div>
                            
                            <div class="mb-2">
                                <label for="input-file-bukti" class="form-label fs-8 fw-semibold mb-1 text-muted">Foto Bukti Sertifikat (.jpg, .jpeg, .png)</label>
                                <input type="file" id="input-file-bukti" name="foto_bukti_prestasi" class="form-control form-control-sm prestasi-file-input" accept="image/*" @change="handleFileUpload($event, 'foto_bukti_prestasi')" />
                                <div v-if="formPrestasi.existing_foto_bukti" class="fs-8 mt-1 text-success">
                                    <i class="bi bi-check-circle-fill"></i> Sudah ada file terunggah:
                                    <a :href="baseUrl + '/storage/app/public/' + formPrestasi.existing_foto_bukti" target="_blank" class="fw-bold">Lihat Foto</a>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="input-file-siswa" class="form-label fs-8 fw-semibold mb-1 text-muted">Foto Penerimaan Penghargaan / Siswa (.jpg, .jpeg, .png)</label>
                                <input type="file" id="input-file-siswa" name="foto_siswa_prestasi" class="form-control form-control-sm prestasi-file-input" accept="image/*" @change="handleFileUpload($event, 'foto_siswa_prestasi')" />
                                <div v-if="formPrestasi.existing_foto_siswa" class="fs-8 mt-1 text-success">
                                    <i class="bi bi-check-circle-fill"></i> Sudah ada file terunggah:
                                    <a :href="baseUrl + '/storage/app/public/' + formPrestasi.existing_foto_siswa" target="_blank" class="fw-bold">Lihat Foto</a>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="input-file-kegiatan" class="form-label fs-8 fw-semibold mb-1 text-muted">Foto Dokumentasi Kegiatan (.jpg, .jpeg, .png)</label>
                                <input type="file" id="input-file-kegiatan" name="foto_kegiatan_lomba" class="form-control form-control-sm prestasi-file-input" accept="image/*" @change="handleFileUpload($event, 'foto_kegiatan_lomba')" />
                                <div v-if="formPrestasi.existing_foto_kegiatan" class="fs-8 mt-1 text-success">
                                    <i class="bi bi-check-circle-fill"></i> Sudah ada file terunggah:
                                    <a :href="baseUrl + '/storage/app/public/' + formPrestasi.existing_foto_kegiatan" target="_blank" class="fw-bold">Lihat Foto</a>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label for="input-file-surat-tugas" class="form-label fs-8 fw-semibold mb-1 text-muted">Surat Tugas PDF/Gambar (.pdf, .jpg, .jpeg, .png)</label>
                                <input type="file" id="input-file-surat-tugas" name="surat_tugas_pdf" class="form-control form-control-sm prestasi-file-input" accept=".pdf,image/*" @change="handleFileUpload($event, 'surat_tugas_pdf')" />
                                <div v-if="formPrestasi.existing_surat_tugas" class="fs-8 mt-1 text-success">
                                    <i class="bi bi-check-circle-fill"></i> Sudah ada file terunggah:
                                    <a :href="baseUrl + '/storage/app/public/' + formPrestasi.existing_surat_tugas" target="_blank" class="fw-bold">Lihat Berkas</a>
                                </div>
                            </div>
                        </div>

                        <!-- Form Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary rounded-3 px-4 w-100" :disabled="loadingPrestasi">
                                <span v-if="loadingPrestasi" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="bi bi-save me-1"></i> Simpan Prestasi
                            </button>
                            <button type="button" @click="clearFormPrestasi" class="btn btn-sm btn-outline-secondary rounded-3 px-3">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Panel Kanan: Tabel List Prestasi -->
            <div class="col-lg-7">
                <div class="bk-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-list-stars me-2" style="color:var(--bk-primary);"></i>
                            Daftar Prestasi Siswa
                        </h6>
                        <span class="badge bg-primary rounded-pill">{{ prestasiList.length }} Data</span>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loadingPrestasiList" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="text-muted mt-2 fs-7">Memuat daftar prestasi...</p>
                    </div>

                    <!-- Table List -->
                    <div v-else-if="prestasiList.length > 0" class="table-responsive" style="max-height: 800px; overflow-y: auto;">
                        <table class="table table-hover align-middle table-sm border-top" style="font-size:0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2" style="width:35%;">Siswa & Prestasi</th>
                                    <th class="py-2" style="width:30%;">Detail Event</th>
                                    <th class="py-2 text-center" style="width:10%;">Poin</th>
                                    <th class="py-2 text-center" style="width:10%;">Berkas</th>
                                    <th class="pe-3 py-2 text-end" style="width:15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in prestasiList" :key="p.id" class="align-middle">
                                    <td class="ps-3 py-3">
                                        <!-- Siswa Names -->
                                        <div class="mb-1">
                                            <span v-for="(s, idx) in p.siswa_list" :key="s.id">
                                                <span class="fw-bold text-dark">{{ s.nama_lengkap }}</span>
                                                <span class="text-muted fs-8"> ({{ s.nama_kelas || '-' }})</span>
                                                <span v-if="idx < p.siswa_list.length - 1">, </span>
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
                                               :href="baseUrl + '/storage/app/public/' + p.foto_bukti_prestasi" 
                                               target="_blank" 
                                               class="btn btn-xs btn-outline-info p-1 rounded-2" 
                                               title="Foto Bukti Sertifikat">
                                                <i class="bi bi-file-earmark-image"></i>
                                            </a>
                                            <a v-if="p.foto_siswa_prestasi" 
                                               :href="baseUrl + '/storage/app/public/' + p.foto_siswa_prestasi" 
                                               target="_blank" 
                                               class="btn btn-xs btn-outline-info p-1 rounded-2" 
                                               title="Foto Siswa / Penyerahan Juara">
                                                <i class="bi bi-person-badge"></i>
                                            </a>
                                            <a v-if="p.foto_kegiatan_lomba" 
                                               :href="baseUrl + '/storage/app/public/' + p.foto_kegiatan_lomba" 
                                               target="_blank" 
                                               class="btn btn-xs btn-outline-info p-1 rounded-2" 
                                               title="Foto Kegiatan">
                                                <i class="bi bi-camera"></i>
                                            </a>
                                            <a v-if="p.surat_tugas_pdf" 
                                               :href="baseUrl + '/storage/app/public/' + p.surat_tugas_pdf" 
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
                                            <button @click="editPrestasi(p)" 
                                                    class="btn btn-xs btn-outline-primary rounded-2 py-0 px-2 fw-semibold" 
                                                    style="font-size:0.7rem; line-height:1.5;" 
                                                    title="Edit Data Prestasi">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button @click="deletePrestasi(p.id)" 
                                                    class="btn btn-xs btn-outline-danger rounded-2 py-0 px-2 fw-semibold" 
                                                    style="font-size:0.7rem; line-height:1.5;" 
                                                    title="Hapus Data Prestasi">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="text-center py-5 text-muted">
                        <i class="bi bi-trophy fs-1 d-block mb-2"></i>
                        Belum ada catatan prestasi siswa yang terdaftar.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 6: KEHADIRAN SISWA SEMESTERAN
    ════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'kehadiran'" class="animate-fade-in">
        <!-- Filter Card -->
        <div class="bk-card p-4 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="select-ta-kehadiran" class="form-label fw-semibold fs-8 mb-1">Tahun Ajaran</label>
                    <select id="select-ta-kehadiran" name="ta_kehadiran" class="form-select form-select-sm rounded-3" v-model="filterKehadiran.tahun_ajaran_id" v-if="tahunAjaranList && tahunAjaranList.length">
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.id">
                            {{ ta.tahun_ajaran }} ({{ ta.status === 'Aktif' ? 'Aktif' : 'Non-Aktif' }})
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="select-sm-kehadiran" class="form-label fw-semibold fs-8 mb-1">Semester</label>
                    <select id="select-sm-kehadiran" name="sm_kehadiran" class="form-select form-select-sm rounded-3" v-model="filterKehadiran.semester">
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="select-kls-kehadiran" class="form-label fw-semibold fs-8 mb-1">Kelas</label>
                    <select id="select-kls-kehadiran" name="kls_kehadiran" class="form-select form-select-sm rounded-3" v-model="filterKehadiran.kelas_id">
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

        <!-- Main Workspace -->
        <div class="row g-4" v-if="kehadiranData.length > 0">
            <!-- Grid Table (Left) -->
            <div class="col-lg-8">
                <div class="bk-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Input Absensi Kehadiran Siswa
                            </h6>
                            <small class="text-muted fs-8">Gunakan tombol arah keyboard &larr; &rarr; &uarr; &darr; atau Enter untuk berpindah sel.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary rounded-3 text-dark fw-semibold" @click="setAllEmptyToZero" id="btn-set-nol">
                                Set Kosong &rarr; 0
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tbl-kehadiran-grid">
                            <thead class="table-light">
                                <tr>
                                    <th>Siswa</th>
                                    <th>NISN</th>
                                    <th>Kelas</th>
                                    <th class="text-center" style="width: 80px;">Sakit (S)</th>
                                    <th class="text-center" style="width: 80px;">Izin (I)</th>
                                    <th class="text-center" style="width: 80px;">Alfa (A)</th>
                                    <th class="text-center" style="width: 140px;">Status Peringatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, idx) in kehadiranData" :key="s.siswa_id" :class="{'dirty-row': isRowDirty(s.siswa_id)}">
                                    <td>
                                        <div class="fw-bold text-dark">{{ s.nama_lengkap }}</div>
                                    </td>
                                    <td><span class="text-muted">{{ s.nisn || '-' }}</span></td>
                                    <td>
                                        <span class="badge bg-light text-primary border rounded-3">{{ s.nama_kelas || '-' }}</span>
                                        <span v-if="isRowDirty(s.siswa_id)" class="badge bg-primary text-white ms-1" style="font-size:0.65rem;">Belum Disimpan</span>
                                    </td>
                                    <!-- Sakit input -->
                                    <td class="text-center" :class="{'dirty-cell': isCellDirty(s.siswa_id, 'sakit')}">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:20px;height:20px;line-height:0.5;" @click="decrementAbsen(s.siswa_id, 'sakit')">-</button>
                                            <label :for="'sakit-' + s.siswa_id" class="visually-hidden">Sakit {{ s.nama_lengkap }}</label>
                                            <input type="number" :id="'sakit-' + s.siswa_id" :name="'sakit_' + s.siswa_id" class="form-control form-control-sm grid-input-number rounded-2 p-1" 
                                                   v-model.number="s.sakit" min="0" @keydown="handleGridKeydown($event, idx, 'sakit')">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:20px;height:20px;line-height:0.5;" @click="incrementAbsen(s.siswa_id, 'sakit')">+</button>
                                        </div>
                                    </td>
                                    <!-- Izin input -->
                                    <td class="text-center" :class="{'dirty-cell': isCellDirty(s.siswa_id, 'izin')}">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:20px;height:20px;line-height:0.5;" @click="decrementAbsen(s.siswa_id, 'izin')">-</button>
                                            <label :for="'izin-' + s.siswa_id" class="visually-hidden">Izin {{ s.nama_lengkap }}</label>
                                            <input type="number" :id="'izin-' + s.siswa_id" :name="'izin_' + s.siswa_id" class="form-control form-control-sm grid-input-number rounded-2 p-1" 
                                                   v-model.number="s.izin" min="0" @keydown="handleGridKeydown($event, idx, 'izin')">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:20px;height:20px;line-height:0.5;" @click="incrementAbsen(s.siswa_id, 'izin')">+</button>
                                        </div>
                                    </td>
                                    <!-- Alfa input -->
                                    <td class="text-center" :class="{'dirty-cell': isCellDirty(s.siswa_id, 'alfa')}" 
                                        :class="s.alfa > 5 ? 'cell-warning' : ((s.sakit + s.izin + s.alfa) > 7 ? 'cell-caution' : '')">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:20px;height:20px;line-height:0.5;" @click="decrementAbsen(s.siswa_id, 'alfa')">-</button>
                                            <label :for="'alfa-' + s.siswa_id" class="visually-hidden">Alfa {{ s.nama_lengkap }}</label>
                                            <input type="number" :id="'alfa-' + s.siswa_id" :name="'alfa_' + s.siswa_id" class="form-control form-control-sm grid-input-number rounded-2 p-1" 
                                                   v-model.number="s.alfa" min="0" @keydown="handleGridKeydown($event, idx, 'alfa')">
                                            <button class="btn btn-xs btn-light border p-1 rounded-circle" style="width:20px;height:20px;line-height:0.5;" @click="incrementAbsen(s.siswa_id, 'alfa')">+</button>
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
                        <button class="btn btn-primary rounded-3 px-4 fw-bold" @click="saveKehadiran" :disabled="savingKehadiran" id="btn-simpan-kehadiran">
                            <span v-if="savingKehadiran" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-floppy-fill me-1"></i> Simpan Absensi Kelas
                        </button>
                    </div>
                </div>
            </div>

            <!-- Import/Export Tools (Right) -->
            <div class="col-lg-4">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light h-100">
                            <div class="fw-bold fs-7 text-dark mb-1"><i class="bi bi-file-earmark-arrow-down me-2"></i>📤 Ekspor Kehadiran ke Excel</div>
                            <p class="fs-8 text-muted mb-3">Unduh template data kehadiran kelas terpilih untuk diisi offline melalui Excel.</p>
                            <button class="btn btn-sm btn-outline-primary rounded-3 fw-semibold" @click="exportKehadiran" id="btn-ekspor-kehadiran">
                                <i class="bi bi-download"></i> Unduh Berkas Excel
                            </button>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light h-100">
                            <div class="fw-bold fs-7 text-dark mb-1"><i class="bi bi-file-earmark-arrow-up me-2"></i>📥 Impor Kehadiran dari Excel / CSV</div>
                            <p class="fs-8 text-muted mb-2">Unggah berkas template Excel (.xlsx, .xls) atau CSV (.csv) yang sudah diedit. Pastikan kolom **NISN, Sakit, Izin, Alfa** tetap ada.</p>
                            <div class="d-flex gap-2 align-items-center">
                                <label for="file-import-kehadiran" class="visually-hidden">Unggah template kehadiran</label>
                                <input type="file" id="file-import-kehadiran" name="file_import_kehadiran" @change="handleFileImportChange" class="form-control form-control-sm" accept=".xlsx,.xls,.csv">
                                <button class="btn btn-sm btn-success rounded-3 fw-semibold" @click="importKehadiran" :disabled="importingKehadiran" id="btn-proses-impor-kehadiran">
                                    <span v-if="importingKehadiran" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="bi bi-upload"></i> Impor
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
            <h6 class="fw-bold">Belum Ada Data Yang Ditampilkan</h6>
            <p class="fs-7">Silakan pilih Tahun Ajaran, Semester, dan Kelas di atas untuk menampilkan grid input.</p>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 7: TATA TERTIB & POIN PELANGGARAN
    ════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'pelanggaran'" class="animate-fade-in">
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
                            <div v-if="pelanggaranTopStudents.length > 0" class="table-responsive">
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
            </div>
        </div>

        <!-- SUB-TAB 2: CATAT PELANGGARAN (TRANSAKSI) -->
        <div v-show="activeSubTab === 'p_input'" class="animate-fade-in">
            <div class="row g-4">
                <!-- Form Input/Edit Laporan (Kiri) -->
                <div class="col-lg-4">
                    <div class="bk-form-card p-4 rounded-4 shadow-sm border bg-white">
                        <h6 class="fw-bold mb-3" style="color:var(--bk-primary);">
                            <i class="bi" :class="formInputPelanggaran.id ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
                            {{ formInputPelanggaran.id ? 'Edit Laporan Pelanggaran' : 'Catat Pelanggaran Siswa' }}
                        </h6>

                        <!-- Autocomplete search input -->
                        <div v-if="!selectedPelanggaranSiswa.id" class="mb-3">
                            <label for="pelanggaran-siswa-search" class="form-label fw-semibold fs-7">Cari Nama Siswa <span class="text-danger">*</span></label>
                            <div style="position:relative;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="pelanggaran-siswa-search" name="pelanggaran_siswa_search" class="form-control rounded-end-3 fs-7"
                                           v-model="pelanggaranSearchSiswa"
                                           @input="searchSiswaPelanggaranDebounce"
                                           @blur="hidePelanggaranDropdownDelay"
                                           placeholder="Ketik nama siswa..."
                                           autocomplete="off">
                                </div>
                                <!-- Dropdown Autocomplete -->
                                <div v-show="showPelanggaranSiswaDropdown && pelanggaranSiswaOptions.length > 0"
                                     class="border rounded-3 bg-white shadow mt-1"
                                     style="position:absolute;z-index:200;max-height:200px;overflow-y:auto;width:100%;">
                                    <div v-for="s in pelanggaranSiswaOptions" :key="s.id"
                                         class="px-3 py-2 border-bottom"
                                         @mousedown.prevent="selectSiswaPelanggaran(s)"
                                         style="cursor:pointer;font-size:0.8rem;"
                                         :style="{background: siswaHoverPelanggaran === s.id ? '#eff6ff' : '#fff'}"
                                         @mouseenter="siswaHoverPelanggaran = s.id" @mouseleave="siswaHoverPelanggaran = null">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="fw-semibold text-dark">{{ s.nama_lengkap }}</div>
                                            <span class="badge rounded-pill bg-light text-primary border" style="font-size:0.65rem;">
                                                {{ s.nama_kelas || 'Tanpa Kelas' }}
                                            </span>
                                        </div>
                                        <div class="text-muted" style="font-size:0.7rem;">NISN: {{ s.nisn || '-' }}</div>
                                    </div>
                                    <div v-if="loadingSearchPelanggaranSiswa" class="text-center py-2 text-muted fs-8">
                                        <span class="spinner-border spinner-border-sm me-1"></span>Mencari...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card info siswa terpilih -->
                        <div v-else class="p-3 mb-3 rounded-4 shadow-sm border bg-light">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="text-truncate" style="max-width:75%;">
                                    <h6 class="fw-bold text-dark mb-0 text-truncate">{{ selectedPelanggaranSiswa.nama_lengkap }}</h6>
                                    <p class="text-muted fs-9 mb-0">NISN: {{ selectedPelanggaranSiswa.nisn || '-' }}</p>
                                </div>
                                <button v-if="!formInputPelanggaran.id" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0.5 fs-9"
                                        type="button" @click="clearSiswaPelanggaran" id="btn-clear-siswa-pelanggaran">
                                    <i class="bi bi-arrow-left-right"></i> Ganti
                                </button>
                            </div>
                            <div class="border-top pt-2 mt-2">
                                <span class="badge bg-primary rounded-3 py-1.5 px-2.5 fs-9">
                                    {{ selectedPelanggaranSiswa.nama_kelas || '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Form fields -->
                        <div class="mb-3">
                            <label for="input-pelanggaran-id" class="form-label fw-semibold fs-7">Aturan Pelanggaran & Poin <span class="text-danger">*</span></label>
                            <select id="input-pelanggaran-id" name="pelanggaran_id" class="form-select form-select-sm rounded-3" v-model="formInputPelanggaran.pelanggaran_id">
                                <option value="">-- Pilih Pelanggaran --</option>
                                <option v-for="rule in pelanggaranMasterList" :key="rule.id" :value="rule.id">
                                    [{{ rule.kategori }}] {{ rule.nama_pelanggaran }} ({{ rule.bobot_poin }} Poin)
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="input-tanggal-kejadian" class="form-label fw-semibold fs-7">Tanggal Kejadian <span class="text-danger">*</span></label>
                            <input type="date" id="input-tanggal-kejadian" name="tanggal_kejadian" class="form-control form-control-sm rounded-3" v-model="formInputPelanggaran.tanggal_kejadian">
                        </div>

                        <div class="mb-3">
                            <label for="input-catatan-keterangan" class="form-label fw-semibold fs-7">Kronologi / Catatan Kejadian</label>
                            <textarea id="input-catatan-keterangan" name="catatan_keterangan" class="form-control form-control-sm rounded-3 text-area-vertical" rows="3" 
                                      v-model="formInputPelanggaran.catatan_keterangan"
                                      placeholder="Tulis detail kronologi pelanggaran..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="input-foto-bukti-file" class="form-label fw-semibold fs-7">Foto Bukti (Maks 2MB)</label>
                            <input type="file" id="input-foto-bukti-file" name="foto_bukti" class="form-control form-control-sm rounded-3 mb-2" 
                                   accept="image/*" @change="handleFotoUpload">
                            <div v-if="formInputPelanggaran.existing_foto" class="mt-2">
                                <small class="text-muted d-block">Foto Sebelumnya:</small>
                                <a :href="baseUrl + '/' + formInputPelanggaran.existing_foto" target="_blank" class="fs-8 text-primary fw-bold text-decoration-underline">
                                    <i class="bi bi-image me-1"></i>Lihat Foto Bukti
                                </a>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-sm btn-outline-secondary rounded-3 w-50" type="button" @click="clearSiswaPelanggaran" id="btn-reset-pelanggaran-form">
                                Batal / Reset
                            </button>
                            <button class="btn btn-sm btn-primary rounded-3 w-50 text-white fw-bold" @click="submitPelanggaran" :disabled="submittingPelanggaran" id="btn-submit-pelanggaran">
                                <i class="bi bi-floppy me-1"></i>
                                {{ formInputPelanggaran.id ? 'Perbarui' : 'Simpan' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Laporan (Kanan) -->
                <div class="col-lg-8">
                    <div class="bk-card p-4 rounded-4 shadow-sm border bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-clock-history me-2 text-danger"></i>Riwayat Laporan Hari Ini & Terkini
                                </h6>
                                <small class="text-muted fs-8">{{ filteredCatatanPelanggaran.length }} kejadian terdaftar</small>
                            </div>
                            <div class="d-flex gap-2">
                                <label for="catatan-list-search" class="visually-hidden">Cari riwayat laporan</label>
                                <input type="text" id="catatan-list-search" name="catatan_list_search" class="form-control form-control-sm rounded-3" style="width: 200px;" 
                                       v-model="catatanListSearch" placeholder="Cari nama, kelas...">
                                <button class="btn btn-sm btn-outline-secondary rounded-3" @click="loadPelanggaranCatatan" :disabled="loadingPelanggaranCatatan" id="btn-refresh-catatan">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>

                        <div v-if="loadingPelanggaranCatatan" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div v-else-if="filteredCatatanPelanggaran.length > 0" class="table-responsive" style="max-height:480px; overflow-y:auto;">
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
                                    <tr v-for="c in filteredCatatanPelanggaran" :key="c.id">
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
                                            <a v-if="c.foto_bukti" @click.prevent="showFotoModal(baseUrl + '/' + c.foto_bukti)" href="#" 
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
                        <div v-else class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-2 mb-2 d-block"></i>
                            Tidak ada data pelanggaran.
                        </div>
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
                <div v-else-if="filteredSanksiBuku.length > 0" class="table-responsive">
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
                            <tr v-for="item in filteredSanksiBuku" :key="item.siswa_id">
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
                        <div v-else-if="pelanggaranMasterList.length > 0" class="table-responsive">
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
                                    <tr v-for="rule in pelanggaranMasterList" :key="rule.id">
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
                        <div v-else class="text-center py-5 text-muted">
                            <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                            Belum ada master aturan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                <h5 class="fw-bold text-dark mb-1">{{ sanksiDetailModal.student.nama_lengkap }}</h5>
                                <div class="text-muted fs-8 d-flex flex-wrap gap-3">
                                    <span><strong>NISN:</strong> {{ sanksiDetailModal.student.nisn || '-' }}</span>
                                    <span><strong>NIS:</strong> {{ sanksiDetailModal.student.nis || '-' }}</span>
                                    <span><strong>Kelas:</strong> {{ sanksiDetailModal.student.nama_kelas || '-' }}</span>
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
                    </div>

                    <!-- Split Panels: Timeline (Left) & Pembinaan Log (Right) -->
                    <div class="row g-4">
                        
                        <!-- Timeline of Incidents (Kiri) -->
                        <div class="col-lg-6">
                            <div class="p-3 border rounded-4 bg-light" style="max-height: 400px; overflow-y: auto;">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2 text-danger"></i>Timeline Riwayat Pelanggaran</h6>
                                
                                <div v-if="sanksiDetailModal.violations.length > 0" class="timeline-container px-2">
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
                                            <a @click.prevent="showFotoModal(baseUrl + '/' + v.foto_bukti)" href="#" class="d-inline-flex align-items-center gap-1 btn btn-xs btn-outline-secondary p-1 rounded-2">
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
                                
                                <div v-if="sanksiDetailModal.followUps.length > 0" class="timeline-container px-2">
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
                                        <div class="text-muted fs-9 text-end">
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

    <!-- PROOF IMAGE LIGHTBOX MODAL -->
    <div v-if="fotoModal.show" class="modal-backdrop-custom d-flex align-items-center justify-content-center" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,23,42,0.8); z-index:1060;" @click="fotoModal.show = false">
        <div class="position-relative p-2" style="max-width:90%; max-height:90%;">
            <img :src="fotoModal.src" class="img-fluid rounded-3 shadow-lg" style="max-height:80vh;" alt="Foto bukti fisik pelanggaran">
            <button class="btn btn-dark rounded-circle position-absolute" style="top:-15px; right:-15px; width:36px; height:36px; display:flex; align-items:center; justify-content:center;" @click="fotoModal.show = false" id="btn-close-foto-modal">
                <i class="bi bi-x fs-4"></i>
            </button>
        </div>
    </div>

</div><!-- End #bkApp -->

<script>
{
const { ref, computed, onMounted, nextTick } = Vue;

// Inject PHP variables safely
const _tenantId  = <?= json_encode($tenantId) ?>;
const _userRole  = <?= json_encode($userRole) ?>;
const _userId    = <?= json_encode($_SESSION['user_id'] ?? '') ?>;
const _baseUrl   = '<?= $baseUrl ?>';
const _tahunAjaranList = <?= json_encode($tahunAjaranList) ?>;

window.VueAppRegistry.register('#bkApp', {
    setup() {
        const userRole       = _userRole;
        const baseUrl        = _baseUrl;

        // ─── State ──────────────────────────────────────────
        const activeTab      = ref('dashboard');
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

        // ─── Pelanggaran & Poin State ────────────────────────
        const activeSubTab = ref('p_dashboard');
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
            total_siswa_aktif: '—', kasus_bulan_ini: '—',
            kasus_terbuka: '—', total_alumni: '—', distribusi_kasus: []
        });
        const pieColors = ['#7c3aed','#2563eb','#10b981','#f59e0b','#ef4444','#64748b'];

        // Tracer
        const loadingTracer = ref(false);
        const tracerData    = ref({ kuliah: 0, pekerjaan: 0, total: 0 });



        // Jurnal / Kasus
        const loadingKasus      = ref(false);
        const loadingKasusList  = ref(false);
        const kasusList         = ref([]);
        const kasusListSearch   = ref(''); // Filter lokal untuk tabel riwayat
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

        // Computed: filter tabel riwayat kasus secara lokal
        const filteredKasusList = computed(() => {
            if (!kasusListSearch.value.trim()) return kasusList.value;
            const q = kasusListSearch.value.toLowerCase();
            return kasusList.value.filter(k =>
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
        const tabsLoaded = ref({ dashboard: false, penjurusan: false, tracer: false, jurnal: false, prestasi: false, kehadiran: false, pelanggaran: false });

        function switchTab(tab) {
            activeTab.value = tab;
            if (!tabsLoaded.value[tab]) {
                tabsLoaded.value[tab] = true;
                if (tab === 'dashboard')  loadDashboard();
                if (tab === 'penjurusan') loadPenjurusan();
                if (tab === 'tracer')     loadTracer();
                if (tab === 'jurnal')     { loadKasus(); loadKelasList(); }
                if (tab === 'prestasi')   { loadPrestasi(); }
                if (tab === 'kehadiran')  { loadKelasKehadiran(); }
                if (tab === 'pelanggaran') { switchSubTab(activeSubTab.value); }
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
                if (res.data.success) Object.assign(kpi.value, res.data);
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
                alertPenjurusan.value = { msg: '❌ ' + (err.response?.data?.error || 'Koneksi gagal.'), type: 'danger' };
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
                alertPenjurusan.value = { msg: '❌ ' + (err.response?.data?.error || 'Koneksi gagal.'), type: 'danger' };
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
                alertPenjurusan.value = { msg: '❌ ' + (err.response?.data?.error || 'Koneksi gagal.'), type: 'danger' };
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

        // ─── Submit Kasus ────────────────────────────────────
        async function submitKasus() {
            if (!formKasus.value.id_siswa)      { alertJurnal.value = { msg: 'Pilih siswa terlebih dahulu.', type: 'danger' }; return; }
            if (!formKasus.value.jenis_kasus)   { alertJurnal.value = { msg: 'Jenis kasus wajib dipilih.', type: 'danger' }; return; }
            if (!formKasus.value.catatan.trim()){ alertJurnal.value = { msg: 'Catatan konseling wajib diisi.', type: 'danger' }; return; }

            loadingKasus.value  = true;
            alertJurnal.value   = { msg: '', type: 'success' };
            try {
                const payload = { ...formKasus.value };
                if (currentTenantId.value) payload.tenant_id = currentTenantId.value;

                const res = await axios.post(`${_baseUrl}/api/v1/bk/kasus`, payload, {
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.data.success) {
                    alertJurnal.value = { msg: '✅ ' + res.data.message, type: 'success' };
                    formKasus.value = { id_siswa: '', tanggal_konseling: today, jenis_kasus: '', catatan: '', tindak_lanjut: '', status_kasus: 'Terbuka', is_rahasia: 1 };
                    selectedSiswaInfo.value = {};
                    kasusSearchSiswa.value  = '';
                    loadKasus();
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
                    text: err.response?.data?.error || 'Koneksi ke server gagal.',
                    confirmButtonColor: 'var(--bk-primary)'
                });
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
                        text: err.response?.data?.error || 'Gagal menghubungi server.',
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
                const res = await axios.get(`${_baseUrl}/api/v1/bk/kasus/logs?id_kasus=${k.id}`);
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
                    text: err.response?.data?.error || 'Koneksi ke server gagal.',
                    confirmButtonColor: 'var(--bk-primary)'
                });
            }
        }

        // ─── Prestasi Siswa ──────────────────────────────────
        const activeYearsList = ref(_tahunAjaranList);
        const prestasiList = ref([]);
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
            foto_bukti_prestasi: null,
            foto_siswa_prestasi: null,
            foto_kegiatan_lomba: null,
            surat_tugas_pdf: null,
            existing_foto_bukti: null,
            existing_foto_siswa: null,
            existing_foto_kegiatan: null,
            existing_surat_tugas: null
        });

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
            if (selectedPrestasiSiswa.value.length === 0) { alertPrestasi.value = { msg: 'Minimal pilih satu siswa peraih prestasi.', type: 'danger' }; return; }

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
                        text: res.data.message,
                        confirmButtonColor: 'var(--bk-primary)'
                    });
                    clearFormPrestasi();
                    loadPrestasi();
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
                    title: 'Kesalahan',
                    text: err.response?.data?.error || 'Koneksi ke server gagal.',
                    confirmButtonColor: 'var(--bk-primary)'
                });
            } finally {
                loadingPrestasi.value = false;
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
            
            formPrestasi.value.existing_foto_bukti = p.foto_bukti_prestasi;
            formPrestasi.value.existing_foto_siswa = p.foto_siswa_prestasi;
            formPrestasi.value.existing_foto_kegiatan = p.foto_kegiatan_lomba;
            formPrestasi.value.existing_surat_tugas = p.surat_tugas_pdf;

            selectedPrestasiSiswa.value = (p.siswa_list || []).map(s => ({
                id: s.id,
                nama_lengkap: s.nama_lengkap,
                nisn: s.nisn,
                nama_kelas: s.nama_kelas
            }));
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
                        text: err.response?.data?.error || 'Koneksi ke server gagal.',
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
                if (res.data.success) listKelasKehadiran.value = res.data.data || [];
            } catch (e) { console.error('loadKelasKehadiran error', e); }
        }

        async function loadKehadiran() {
            if (!filterKehadiran.value.tahun_ajaran_id || !filterKehadiran.value.semester || !filterKehadiran.value.kelas_id) {
                kehadiranData.value = [];
                originalKehadiranData.value = [];
                return;
            }
            loadingKehadiran.value = true;
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
                        sakit: parseInt(item.sakit) || 0,
                        izin: parseInt(item.izin) || 0,
                        alfa: parseInt(item.alfa) || 0
                    }));
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
            kehadiranData.value.forEach(item => {
                if (item.sakit === null || item.sakit === '') item.sakit = 0;
                if (item.izin === null || item.izin === '') item.izin = 0;
                if (item.alfa === null || item.alfa === '') item.alfa = 0;
            });
        }

        function incrementAbsen(siswaId, field) {
            const item = kehadiranData.value.find(s => s.siswa_id === siswaId);
            if (!item) return;
            let val = parseInt(item[field]);
            if (isNaN(val)) val = 0;
            item[field] = val + 1;
        }

        function decrementAbsen(siswaId, field) {
            const item = kehadiranData.value.find(s => s.siswa_id === siswaId);
            if (!item) return;
            let val = parseInt(item[field]);
            if (isNaN(val)) val = 0;
            if (val > 0) {
                item[field] = val - 1;
            } else {
                item[field] = 0;
            }
        }

        async function saveKehadiran() {
            if (kehadiranData.value.length === 0) return;
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
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || 'Gagal menghubungi server.' });
            } finally {
                savingKehadiran.value = false;
            }
        }

        function exportKehadiran() {
            if (!filterKehadiran.value.tahun_ajaran_id || !filterKehadiran.value.semester || !filterKehadiran.value.kelas_id) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih filter Tahun Ajaran, Semester, dan Kelas terlebih dahulu.' });
                return;
            }
            const params = new URLSearchParams();
            params.set('tahun_ajaran_id', filterKehadiran.value.tahun_ajaran_id);
            params.set('semester', filterKehadiran.value.semester);
            params.set('kelas_id', filterKehadiran.value.kelas_id);
            if (currentTenantId.value) params.set('tenant_id', currentTenantId.value);
            window.open(`${_baseUrl}/api/v1/bk/absensi-semester/export?${params.toString()}`, '_blank');
        }

        function handleFileImportChange(event) {
            fileImportKehadiran.value = event.target.files[0];
        }

        async function importKehadiran() {
            if (!fileImportKehadiran.value) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih file yang ingin diimpor terlebih dahulu.' });
                return;
            }
            if (!filterKehadiran.value.tahun_ajaran_id || !filterKehadiran.value.semester || !filterKehadiran.value.kelas_id) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih filter Tahun Ajaran, Semester, dan Kelas terlebih dahulu.' });
                return;
            }
            importingKehadiran.value = true;
            try {
                const formData = new FormData();
                formData.append('file', fileImportKehadiran.value);
                formData.append('tahun_ajaran_id', filterKehadiran.value.tahun_ajaran_id);
                formData.append('semester', filterKehadiran.value.semester);
                formData.append('kelas_id', filterKehadiran.value.kelas_id);
                if (currentTenantId.value) formData.append('tenant_id', currentTenantId.value);
                
                const res = await axios.post(`${_baseUrl}/api/v1/bk/absensi-semester/import`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.data.success) {
                    Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message });
                    fileImportKehadiran.value = null;
                    const fileInput = document.getElementById('file-import-kehadiran');
                    if (fileInput) fileInput.value = '';
                    await loadKehadiran();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal mengimpor data.' });
                }
            } catch (e) {
                console.error(e);
                const errors = e.response?.data?.errors;
                const errorText = e.response?.data?.error;
                const detailsText = e.response?.data?.details;

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
                importingKehadiran.value = false;
            }
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
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingPelanggaranDashboard.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/pelanggaran/dashboard`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) {
                    pelanggaranKpi.value = res.data.kpi;
                    pelanggaranTopStudents.value = res.data.top_students || [];
                    
                    nextTick(() => {
                        renderPelanggaranChart(res.data.chart);
                    });
                }
            } catch (e) {
                console.error('loadPelanggaranDashboard error', e);
            } finally {
                loadingPelanggaranDashboard.value = false;
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
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || 'Terjadi kesalahan.' });
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
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || 'Terjadi kesalahan.' });
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

        function handleFotoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Ukuran foto maksimal 2MB.' });
                    event.target.value = '';
                    return;
                }
                formInputPelanggaran.value.foto_bukti = file;
            }
        }

        async function submitPelanggaran() {
            if (!formInputPelanggaran.value.siswa_id) {
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
                formData.append('siswa_id', formInputPelanggaran.value.siswa_id);
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
                    clearSiswaPelanggaran();
                    loadPelanggaranCatatan();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menyimpan catatan.' });
                }
            } catch (e) {
                console.error('submitPelanggaran error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || 'Terjadi kesalahan.' });
            } finally {
                submittingPelanggaran.value = false;
            }
        }

        function editPelanggaran(c) {
            selectedPelanggaranSiswa.value = {
                id: c.siswa_id,
                nama_lengkap: c.nama_siswa,
                nisn: c.nisn,
                nama_kelas: c.nama_kelas
            };
            formInputPelanggaran.value = {
                id: c.id,
                siswa_id: c.siswa_id,
                pelanggaran_id: c.pelanggaran_id,
                tanggal_kejadian: c.tanggal_kejadian,
                catatan_keterangan: c.catatan_keterangan || '',
                foto_bukti: null,
                existing_foto: c.foto_bukti || null
            };
            const fileInput = document.getElementById('input-foto-bukti-file');
            if (fileInput) fileInput.value = '';
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
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || 'Terjadi kesalahan.' });
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
                keterangan_tindakan: ''
            };

            try {
                let url = `${_baseUrl}/api/v1/bk/pelanggaran/sanksi/detail?siswa_id=${siswaId}`;
                if (currentTenantId.value) url += `&tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) {
                    sanksiDetailModal.value.student = res.data.student || {};
                    sanksiDetailModal.value.total_poin = parseInt(res.data.total_poin) || 0;
                    sanksiDetailModal.value.violations = res.data.violations || [];
                    sanksiDetailModal.value.followUps = res.data.follow_ups || [];
                }
            } catch (e) {
                console.error('openSanksiDetail error', e);
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal mengambil detail sanksi siswa.' });
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
                const payload = {
                    siswa_id: siswaId,
                    tanggal_tindakan: formTindakLanjut.value.tanggal_tindakan,
                    jenis_tindakan: formTindakLanjut.value.jenis_tindakan,
                    keterangan_tindakan: formTindakLanjut.value.keterangan_tindakan
                };
                if (currentTenantId.value) payload.tenant_id = currentTenantId.value;

                const res = await axios.post(`${_baseUrl}/api/v1/bk/pelanggaran/sanksi/tindak-lanjut`, payload, {
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
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
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || 'Terjadi kesalahan.' });
            } finally {
                submittingTindakLanjut.value = false;
            }
        }

        function showFotoModal(src) {
            fotoModal.value.show = true;
            fotoModal.value.src = src;
        }

        function getKategoriBadge(kategori) {
            if (kategori === 'Ringan') return 'badge-secondary';
            if (kategori === 'Sedang') return 'badge-info';
            if (kategori === 'Berat') return 'badge-warning';
            if (kategori === 'Khusus') return 'badge-danger';
            return 'badge-secondary';
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

        // ─── Init ────────────────────────────────────────────
        onMounted(() => {
            loadDashboard();
            tabsLoaded.value.dashboard = true;
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
            kasusListSearch, filteredKasusList,
            alertJurnal, kasusSearchSiswa, siswaOptions,
            selectedSiswaInfo, formKasus, today,
            showSiswaDropdown, siswaHover, loadingSearchSiswa,
            kelasList, filterKelasId,
            submitKasus, loadKasus, loadKelasList,
            searchSiswaDebounce, selectSiswa, clearSiswa,
            onFilterKelasChange, onSearchFocus, hideDropdownDelay,
            canEditKasus, openChangeStatus, openLogs,
            // Prestasi Siswa
            activeYearsList, prestasiList, guruList, loadingPrestasi, loadingPrestasiList,
            prestasiSearchSiswa, prestasiSiswaOptions, selectedPrestasiSiswa,
            showPrestasiSiswaDropdown, loadingSearchPrestasiSiswa, alertPrestasi, formPrestasi,
            searchSiswaPrestasiDebounce, hidePrestasiDropdownDelay, selectSiswaPrestasi,
            removeSiswaPrestasi, handleFileUpload, submitPrestasi, loadPrestasi, loadGuruList,
            editPrestasi, deletePrestasi, clearFormPrestasi, userRole, baseUrl, currentTenantId,
            // Kehadiran
            loadingKehadiran, savingKehadiran, importingKehadiran, filterKehadiran, tahunAjaranList,
            kehadiranData, listKelasKehadiran, fileImportKehadiran,
            loadKehadiran, loadKelasKehadiran, isCellDirty, isRowDirty, setAllEmptyToZero,
            incrementAbsen, decrementAbsen, saveKehadiran, exportKehadiran,
            handleFileImportChange, importKehadiran, handleGridKeydown,
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
            loadPelanggaranCatatan, loadPelanggaranSanksi, showFotoModal, getKategoriBadge,
            getPoinBadgeClass, formatTanggalIndo, sanksiSearch, sanksiStatus, sanksiDetailModal,
            formTindakLanjut, submittingTindakLanjut, openSanksiDetail, submitTindakLanjut
        };
    }
});
}

// Super Admin tenant filter
<?php if ($userRole === 'super_admin'): ?>
(function() {
    document.getElementById('btn-apply-tenant')?.addEventListener('click', function() {
        const tid = document.getElementById('sa-tenant-select')?.value || '';
        const url = new URL(window.location.href);
        if (tid) { url.searchParams.set('tenant_id', tid); }
        else { url.searchParams.delete('tenant_id'); }
        window.location.href = url.toString();
    });
})();
<?php endif; ?>
</script>

