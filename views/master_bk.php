<?php
/**
 * View: Bimbingan Konseling — Master BK
 * Module hub dengan 5 horizontal tab: Dashboard, Penjurusan, Tracer Study, PDSS, Jurnal BK
 */
$userRole   = $data['user_role']   ?? ($_SESSION['role_name']    ?? '');
$userNama   = $data['user_nama']   ?? ($_SESSION['nama_lengkap'] ?? '');
$tenantId   = $data['tenant_id']   ?? '';
$tenantList = $data['tenant_list'] ?? [];
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
        gap: 0.25rem;
        border-bottom: 2px solid var(--bk-border);
        overflow-x: auto;
        scrollbar-width: none;
        padding-bottom: 0;
    }
    .bk-tabs::-webkit-scrollbar { display: none; }

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

    [v-cloak] { display: none !important; }
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
                @click="switchTab('penjurusan')" id="tab-penjurusan">
            <i class="bi bi-diagram-3"></i> Penjurusan Mandiri
        </button>
        <button class="bk-tab-btn" :class="{'active': activeTab === 'tracer'}"
                @click="switchTab('tracer')" id="tab-tracer">
            <i class="bi bi-mortarboard"></i> Tracer Study
        </button>
        <button class="bk-tab-btn" :class="{'active': activeTab === 'pdss'}"
                @click="switchTab('pdss')" id="tab-pdss">
            <i class="bi bi-award"></i> Kesiapan PDSS
        </button>
        <button class="bk-tab-btn" :class="{'active': activeTab === 'jurnal'}"
                @click="switchTab('jurnal')" id="tab-jurnal">
            <i class="bi bi-journal-text"></i> Rekam Kasus & Jurnal
        </button>
        <button class="bk-tab-btn" :class="{'active': activeTab === 'kelulusan'}"
                @click="switchTab('kelulusan')" id="tab-kelulusan">
            <i class="bi bi-mortarboard-fill"></i> Kelola Kelulusan & Alumni
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
                    <label class="form-label fw-semibold fs-7">Jurusan Saat Ini</label>
                    <input type="text" class="form-control rounded-3 bg-light" readonly
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
         TAB 4: KESIAPAN PDSS (SNBP ELIGIBLE)
    ════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'pdss'">
        <div class="bk-card p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="kpi-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0">Kesiapan PDSS & Eligibilitas SNBP</h5>
                    <p class="text-muted fs-7 mb-0">Pemetaan siswa kelas 12 yang memenuhi syarat SNBP (berdasarkan nama kelas).</p>
                </div>
            </div>

            <div v-if="loadingPdss" class="text-center py-4">
                <div class="spinner-border" style="color:var(--bk-amber);"></div>
            </div>
            <div v-else>
                <div v-if="pdssData.length > 0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-semibold text-dark">
                            <i class="bi bi-person-check-fill me-1" style="color:var(--bk-primary);"></i>
                            {{ pdssData.length }} Siswa Eligible Terdeteksi
                        </span>
                        <label for="input-pdss-search" class="visually-hidden">Cari siswa eligible SNBP</label>
                        <input type="text" class="form-control form-control-sm rounded-3"
                               v-model="pdssSearch" placeholder="Cari nama/NISN..."
                               id="input-pdss-search" name="pdss_search"
                               style="max-width:220px;">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tbl-pdss">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                    <th>Kelas</th>
                                    <th>Jurusan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, idx) in filteredPdss" :key="s.id">
                                    <td class="text-muted">{{ idx + 1 }}</td>
                                    <td class="fw-semibold">{{ s.nama_lengkap }}</td>
                                    <td class="font-monospace fs-7">{{ s.nisn || '—' }}</td>
                                    <td>{{ s.nama_kelas || '—' }}</td>
                                    <td>{{ s.nama_jurusan || '—' }}</td>
                                    <td><span class="pdss-eligible-badge">ELIGIBLE</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="text-center py-5 text-muted">
                    <i class="bi bi-award fs-1 d-block mb-2"></i>
                    Tidak ada siswa kelas 12 yang terdeteksi. Pastikan nama kelas mengandung "12".
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
         TAB 6: KELOLA KELULUSAN & ALUMNI
    ════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'kelulusan'" class="animate-fade-in">
        <!-- Header -->
        <div class="bk-card p-4 mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon text-white" style="background:linear-gradient(135deg,#059669,#10b981); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-mortarboard-fill fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Kelola Kelulusan & Alumni</h5>
                    <p class="text-muted mb-0" style="font-size:0.82rem;">Ubah status siswa menjadi <b>Lulus</b> secara massal. Setiap aksi kelulusan akan tercatat dalam riwayat kenaikan kelas / kelulusan.</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bk-card p-4 mb-4">
            <div class="row g-3 align-items-end">
                <!-- Filter Sekolah (Super Admin Only) -->
                <div class="col-12 col-md-4" v-if="userRole === 'super_admin'">
                    <label for="ls-tenant" class="form-label fw-semibold fs-8 text-muted mb-1"><i class="bi bi-building me-1"></i> Instansi Sekolah <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3" v-model="aksiTenantId" @change="onAksiTenantChange" id="ls-tenant" name="aksi_tenant_id">
                        <option value="">-- Pilih Sekolah --</option>
                        <option v-for="t in listTenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div class="col-12 col-md-4">
                    <label for="ls-kelas" class="form-label fw-semibold fs-8 text-muted mb-1"><i class="bi bi-door-open me-1"></i> Kelas / Rombel <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3" v-model="aksiKelasAsalId" @change="onAksiKelasAsalChange" id="ls-kelas" name="aksi_kelas_asal_id" :disabled="userRole === 'super_admin' && !aksiTenantId">
                        <option value="">-- Pilih Kelas --</option>
                        <option v-for="k in aksiListKelas" :key="k.id" :value="k.id">{{ k.nama_jenjang }} &ndash; {{ k.nama_kelas }}</option>
                    </select>
                </div>

                <!-- Tahun Ajaran -->
                <div class="col-12 col-md-2">
                    <label for="ls-tahun" class="form-label fw-semibold fs-8 text-muted mb-1"><i class="bi bi-calendar3 me-1"></i> Tahun Ajaran <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3" v-model="aksiTahunAjaran" placeholder="2024/2025" id="ls-tahun" name="aksi_tahun_ajaran">
                </div>
            </div>
            <!-- Catatan -->
            <div class="row g-3 mt-1">
                <div class="col-12">
                    <label for="ls-catatan" class="form-label fw-semibold fs-8 text-muted mb-1"><i class="bi bi-chat-left-text me-1"></i> Catatan (opsional)</label>
                    <input type="text" class="form-control rounded-3" v-model="aksiCatatan" placeholder="Misal: Kelulusan tahun ajaran 2024/2025" id="ls-catatan" name="aksi_catatan">
                </div>
            </div>
        </div>

        <!-- Tabel Siswa -->
        <div class="bk-card p-4">
            <div v-if="userRole === 'super_admin' && !aksiTenantId" class="text-center py-4 text-muted">
                <i class="bi bi-building fs-1 text-muted opacity-50 d-block mb-2"></i>
                <span>Pilih instansi sekolah terlebih dahulu.</span>
            </div>
            <div v-else-if="!aksiKelasAsalId" class="text-center py-4 text-muted">
                <i class="bi bi-funnel fs-1 text-muted opacity-50 d-block mb-2"></i>
                <span>Pilih kelas untuk menampilkan daftar siswa yang akan diluluskan.</span>
            </div>
            <div v-else-if="aksiLoading" class="text-center py-5">
                <div class="spinner-border text-success" role="status"></div>
                <p class="text-muted mt-2">Memuat daftar siswa...</p>
            </div>
            <div v-else>
                <!-- Toolbar checklist -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2" v-if="aksiListSiswa.length > 0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" v-model="aksiSelectAll" @change="toggleAksiSelectAll" id="ls-select-all" name="select_all">
                            <label class="form-check-label fw-semibold text-dark fs-7" for="ls-select-all">Pilih Semua ({{ aksiListSiswa.length }} siswa)</label>
                        </div>
                        <span class="badge bg-success rounded-pill" v-if="aksiSelectedIds.length > 0">{{ aksiSelectedIds.length }} terpilih</span>
                    </div>
                    <button class="btn btn-success rounded-3 px-4 fw-semibold border-0 fs-8" @click="submitLuluskan" :disabled="aksiSubmitLoading || aksiSelectedIds.length === 0" id="btn-luluskan">
                        <span v-if="aksiSubmitLoading" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-mortarboard me-1" v-else></i>
                        Luluskan Siswa Terpilih
                    </button>
                </div>

                <div v-if="aksiListSiswa.length === 0" class="text-center py-4 text-muted">
                    <i class="bi bi-person-slash fs-1 text-muted opacity-50 d-block mb-2"></i>
                    <span>Tidak ada siswa aktif di kelas ini.</span>
                </div>

                <div class="table-responsive" v-if="aksiListSiswa.length > 0">
                    <table class="table table-hover align-middle mb-0" style="font-size:0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">
                                    <input class="form-check-input" type="checkbox" v-model="aksiSelectAll" @change="toggleAksiSelectAll" id="ls-select-all-header" name="select_all_header" aria-label="Pilih semua siswa">
                                </th>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>NISN</th>
                                <th>NIS</th>
                                <th>Kelas</th>
                                <th>Jenjang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(s, i) in aksiListSiswa" :key="s.id" :class="{'table-success bg-opacity-10': aksiSelectedIds.includes(s.id)}">
                                <td>
                                    <input class="form-check-input" type="checkbox" :id="'cb-siswa-' + s.id" name="siswa_ids[]" :value="s.id" v-model="aksiSelectedIds" @change="onAksiCheckboxChange" :aria-label="'Pilih siswa ' + s.nama_lengkap">
                                </td>
                                <td class="text-muted">{{ i + 1 }}</td>
                                <td class="fw-semibold text-dark">{{ s.nama_lengkap }}</td>
                                <td><span class="badge bg-light text-dark border font-monospace">{{ s.nisn || '-' }}</span></td>
                                <td><span class="badge bg-light text-dark border font-monospace">{{ s.nis || '-' }}</span></td>
                                <td><span class="badge text-bg-success px-2 py-1 fs-9 rounded-2" style="font-size:0.75rem;">{{ s.nama_kelas }}</span></td>
                                <td class="text-muted">{{ s.nama_jenjang }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div><!-- End #bkApp -->

<script>
{
const { ref, computed, onMounted } = Vue;

// Inject PHP variables safely
const _tenantId  = <?= json_encode($tenantId) ?>;
const _userRole  = <?= json_encode($userRole) ?>;
const _baseUrl   = '<?= $baseUrl ?>';

window.VueAppRegistry.register('#bkApp', {
    setup() {
        // ─── State ──────────────────────────────────────────
        const activeTab      = ref('dashboard');
        const currentTenantId= ref(_tenantId);

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

        // PDSS
        const loadingPdss   = ref(false);
        const pdssData      = ref([]);
        const pdssSearch    = ref('');
        const filteredPdss  = computed(() => {
            if (!pdssSearch.value) return pdssData.value;
            const q = pdssSearch.value.toLowerCase();
            return pdssData.value.filter(s =>
                (s.nama_lengkap || '').toLowerCase().includes(q) ||
                (s.nisn || '').includes(q)
            );
        });

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
        const tabsLoaded = ref({ dashboard: false, penjurusan: false, tracer: false, pdss: false, jurnal: false, kelulusan: false });

        function switchTab(tab) {
            activeTab.value = tab;
            if (!tabsLoaded.value[tab]) {
                tabsLoaded.value[tab] = true;
                if (tab === 'dashboard')  loadDashboard();
                if (tab === 'penjurusan') loadPenjurusan();
                if (tab === 'tracer')     loadTracer();
                if (tab === 'pdss')       loadPdss();
                if (tab === 'jurnal')     { loadKasus(); loadKelasList(); }
                if (tab === 'kelulusan') {
                    if (_userRole !== 'super_admin') {
                        fetchAksiKelas();
                    } else if (aksiTenantId.value) {
                        fetchAksiKelas();
                    }
                }
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

        // ─── API: PDSS ───────────────────────────────────────
        async function loadPdss() {
            if (_userRole === 'super_admin' && !currentTenantId.value) return;
            loadingPdss.value = true;
            try {
                let url = `${_baseUrl}/api/v1/bk/pdss`;
                if (currentTenantId.value) url += `?tenant_id=${currentTenantId.value}`;
                const res = await axios.get(url);
                if (res.data.success) pdssData.value = res.data.data || [];
            } catch (e) { console.error('BK PDSS load error', e); }
            finally { loadingPdss.value = false; }
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
                    alertJurnal.value = { msg: '❌ ' + (res.data.error || 'Gagal menyimpan.'), type: 'danger' };
                }
            } catch (err) {
                const msg = err.response?.data?.error || 'Koneksi gagal.';
                alertJurnal.value = { msg: '❌ ' + msg, type: 'danger' };
            } finally {
                loadingKasus.value = false;
            }
        }

        // ─── Kelola Kelulusan & Alumni (BK) ──────────────────
        const aksiTenantId      = ref('');
        const aksiKelasAsalId   = ref('');
        const aksiTahunAjaran   = ref('');
        const aksiCatatan       = ref('');
        const aksiListKelas     = ref([]);
        const aksiListSiswa     = ref([]);
        const aksiSelectedIds   = ref([]);
        const aksiLoading       = ref(false);
        const aksiSubmitLoading = ref(false);
        const aksiSelectAll     = ref(false);
        const listTenants       = ref(<?= json_encode($tenantList) ?>);

        // Hitung tahun ajaran default
        const now = new Date();
        const y = now.getFullYear();
        const m = now.getMonth() + 1; // 1-indexed
        aksiTahunAjaran.value = m >= 7 ? `${y}/${y+1}` : `${y-1}/${y}`;

        function onAksiTenantChange() {
            aksiKelasAsalId.value = '';
            aksiListSiswa.value = [];
            aksiSelectedIds.value = [];
            aksiSelectAll.value = false;
            if (aksiTenantId.value) {
                fetchAksiKelas();
            } else {
                aksiListKelas.value = [];
            }
        }

        async function fetchAksiKelas() {
            let params = {};
            if (_userRole === 'super_admin') {
                if (!aksiTenantId.value) return;
                params.tenant_id = aksiTenantId.value;
            }
            try {
                const res = await axios.get(`${_baseUrl}/api/v1/pengguna/aksi/kelas`, { params });
                aksiListKelas.value = res.data.data || [];
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: err.response?.data?.error || 'Gagal memuat daftar kelas.', confirmButtonColor: '#ef4444' });
            }
        }

        function onAksiKelasAsalChange() {
            aksiListSiswa.value = [];
            aksiSelectedIds.value = [];
            aksiSelectAll.value = false;
            if (!aksiKelasAsalId.value) return;
            fetchAksiSiswa();
        }

        async function fetchAksiSiswa() {
            if (!aksiKelasAsalId.value) return;
            const params = { kelas_id: aksiKelasAsalId.value };
            if (_userRole === 'super_admin') {
                if (!aksiTenantId.value) return;
                params.tenant_id = aksiTenantId.value;
            }
            aksiLoading.value = true;
            try {
                const res = await axios.get(`${_baseUrl}/api/v1/pengguna/aksi/siswa`, { params });
                aksiListSiswa.value = res.data.data || [];
                aksiSelectedIds.value = [];
                aksiSelectAll.value = false;
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: err.response?.data?.error || 'Gagal memuat daftar siswa.', confirmButtonColor: '#ef4444' });
            } finally {
                aksiLoading.value = false;
            }
        }

        function toggleAksiSelectAll() {
            if (aksiSelectAll.value) {
                aksiSelectedIds.value = aksiListSiswa.value.map(s => s.id);
            } else {
                aksiSelectedIds.value = [];
            }
        }

        function onAksiCheckboxChange() {
            aksiSelectAll.value = aksiSelectedIds.value.length === aksiListSiswa.value.length && aksiListSiswa.value.length > 0;
        }

        function submitLuluskan() {
            if (aksiSelectedIds.value.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal satu siswa.', confirmButtonColor: '#7c3aed' }); return;
            }
            if (!aksiTahunAjaran.value) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Isi tahun ajaran.', confirmButtonColor: '#7c3aed' }); return;
            }

            Swal.fire({
                title: 'Konfirmasi Luluskan Siswa',
                html: `Anda akan meluluskan <b>${aksiSelectedIds.value.length} siswa</b>.<br>Tahun Ajaran: <b>${aksiTahunAjaran.value}</b><br><span class='text-danger'>Status siswa akan berubah menjadi <b>Lulus</b> secara permanen.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Luluskan!',
                cancelButtonText: 'Batal'
            }).then(async result => {
                if (!result.isConfirmed) return;
                aksiSubmitLoading.value = true;
                const payload = {
                    siswa_ids: aksiSelectedIds.value,
                    tahun_ajaran: aksiTahunAjaran.value,
                    catatan: aksiCatatan.value
                };
                if (_userRole === 'super_admin') payload.tenant_id = aksiTenantId.value;

                try {
                    const res = await axios.post(`${_baseUrl}/api/v1/pengguna/aksi/luluskan`, payload);
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.data.message, confirmButtonColor: '#10b981' });
                    aksiKelasAsalId.value = '';
                    aksiListSiswa.value = [];
                    aksiSelectedIds.value = [];
                    aksiSelectAll.value = false;
                    aksiCatatan.value = '';
                } catch (err) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: err.response?.data?.error || 'Terjadi kesalahan.', confirmButtonColor: '#ef4444' });
                } finally {
                    aksiSubmitLoading.value = false;
                }
            });
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
            // PDSS
            loadingPdss, pdssData, pdssSearch, filteredPdss,
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
            // Kelola Kelulusan & Alumni (BK)
            aksiTenantId, aksiKelasAsalId, aksiTahunAjaran, aksiCatatan,
            aksiListKelas, aksiListSiswa, aksiSelectedIds, aksiLoading,
            aksiSubmitLoading, aksiSelectAll, listTenants,
            onAksiTenantChange, fetchAksiKelas, onAksiKelasAsalChange,
            fetchAksiSiswa, toggleAksiSelectAll, onAksiCheckboxChange, submitLuluskan
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

