<?php
/**
 * View: Manajemen Pengumuman & Informasi Sekolah
 * SINTA SaaS - Standardized Enterprise UI/UX 7-Blok Modular
 */
$pageTitle = $title ?? 'Manajemen Pengumuman & Informasi Sekolah';
$isSuperAdmin = !empty($isSuperAdmin);
$tenants = $tenants ?? [];
$selectedTenantId = $selectedTenantId ?? '';
?>

<style>
    [v-cloak] { display: none !important; }
    .fs-9 { font-size: 0.725rem !important; }
    .fs-8 { font-size: 0.815rem !important; }
    .fs-7\.5 { font-size: 0.875rem !important; }

    /* Custom Table Styles */
    .pengguna-table {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 980px;
    }
    .pengguna-table thead th {
        background: #f8fafc !important;
        border-bottom: 2px solid #e2e8f0 !important;
        font-weight: 700;
        font-size: 0.72rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #475569;
        padding: 0.85rem 0.75rem;
        white-space: nowrap;
    }
    .pengguna-table tbody td {
        padding: 0.85rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .pengguna-table tbody tr:hover td {
        background-color: #f8fafc !important;
    }

    /* Custom Slim Scrollbar 6px */
    .table-responsive::-webkit-scrollbar {
        height: 6px;
        width: 6px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 9999px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Modern Pill NavTabs */
    .scrollable-nav-tabs {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .scrollable-nav-tabs::-webkit-scrollbar {
        display: none;
    }
    .nav-pills .nav-link {
        color: #475569;
        background: transparent;
        border-radius: 0.75rem;
        padding: 0.5rem 1.15rem;
        font-size: 0.825rem;
        font-weight: 600;
        transition: all 0.15s ease-in-out;
        white-space: nowrap;
    }
    .nav-pills .nav-link:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    .nav-pills .nav-link.active {
        color: #ffffff !important;
        background: #2563eb !important;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
    }

    /* Pagination Modern */
    .pagination-modern {
        display: flex;
        gap: 0.25rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .pagination-modern .page-item .page-link {
        border: 1px solid #e2e8f0;
        border-radius: 0.625rem;
        color: #475569;
        font-size: 0.815rem;
        font-weight: 600;
        padding: 0.35rem 0.75rem;
        background: #ffffff;
        transition: all 0.15s ease-in-out;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
    }
    .pagination-modern .page-item.active .page-link {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
    }
    .pagination-modern .page-item.disabled .page-link {
        color: #94a3b8;
        background-color: #f8fafc;
        border-color: #e2e8f0;
        pointer-events: none;
    }

    .perpage-select {
        width: 76px !important;
        height: 32px !important;
        font-size: 0.815rem !important;
        font-weight: 600 !important;
        color: #334155 !important;
        border-color: #cbd5e1 !important;
        border-radius: 0.5rem !important;
        padding: 0.25rem 1.75rem 0.25rem 0.65rem !important;
    }

    /* KPI Cards */
    .kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .kpi-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        display: block;
        margin-bottom: 0.25rem;
    }
    .kpi-value {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 0;
        font-family: ui-monospace, monospace;
    }
    .kpi-icon-box {
        width: 42px;
        height: 42px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    /* Modal Backdrop Blur */
    .custom-modal-backdrop {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: rgba(15, 23, 42, 0.65) !important;
        backdrop-filter: blur(6px) !important;
        -webkit-backdrop-filter: blur(6px) !important;
        z-index: 99999 !important;
        overflow: hidden !important;
        padding: 1rem !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .modal-animate-in {
        animation: modalScaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes modalScaleUp {
        from { opacity: 0; transform: scale(0.96) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Audience Selectable Cards */
    .audience-card {
        border: 1.5px solid #e2e8f0;
        user-select: none;
        transition: all 0.2s ease;
    }
    .audience-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
    }
    .audience-card.active {
        border-color: #2563eb !important;
        background-color: #eff6ff !important;
    }

    .shadow-2xs { box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); }
    .shadow-xs { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); }
    .hover-lift { transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .hover-lift:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.06); }
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<div id="pengumumanApp" 
     data-is-super-admin="<?= htmlspecialchars($isSuperAdmin ? 'true' : 'false', ENT_QUOTES, 'UTF-8') ?>" 
     data-tenant-id="<?= htmlspecialchars((string)($selectedTenantId ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
     v-cloak>

    <!-- 1. Row Header & Action Toolbar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-blue-600 text-white rounded-2xl d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 48px; height: 48px;">
                <i class="bi bi-megaphone-fill fs-4"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold text-slate-900 fs-4 mb-0">Manajemen Pengumuman</h3>
                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-1 fs-9 font-bold">
                        <i class="bi bi-broadcast text-blue-600 me-1"></i>Humas & Informasi
                    </span>
                </div>
                <p class="text-slate-500 fs-8 mb-0 mt-0.5">Pusat penerbitan warta sekolah, jadwal agenda penting, surat edaran resmi, dan regulasi civitas terpadu.</p>
            </div>
        </div>
        
        <!-- Action Toolbar -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button v-if="activeTab === 'pengumuman'" 
                    type="button" 
                    class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                    @click="openModalPengumuman()">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Buat Pengumuman</span>
            </button>
            <button v-else-if="activeTab === 'kategori'" 
                    type="button" 
                    class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                    @click="openModalKategori()">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Tambah Kategori</span>
            </button>

            <!-- Manual Refresh Button -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                    @click="refreshAll()" 
                    :disabled="loading"
                    title="Segarkan Data">
                <i class="bi bi-arrow-clockwise" :class="{'spin': loading}"></i>
                <span>Segarkan</span>
            </button>
        </div>
    </div>

    <!-- 2. Compact School Selector Auto-Filter Banner (Khusus Super Admin) -->
    <div class="mb-4 p-3 px-md-4 rounded-2xl shadow-2xs border border-blue-100 bg-white" 
         v-if="isSuperAdmin && tenants.length > 0">
        <div class="d-flex align-items-center flex-wrap gap-2.5">
            <div class="bg-blue-50 text-blue-600 p-2 rounded-xl d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                <i class="bi bi-buildings fs-6"></i>
            </div>
            <div>
                <span class="fs-8 fw-bold text-slate-800 me-1">Pilih Instansi Sekolah:</span>
            </div>
            
            <div class="my-1 my-md-0" style="min-width: 220px; max-width: 300px;">
                <select id="sa-filter-sekolah-pengumuman" 
                        class="form-select form-select-sm bg-slate-50 border-slate-200 rounded-xl text-slate-800 fs-8 font-semibold shadow-2xs cursor-pointer focus:bg-white w-100" 
                        style="height: 38px;" 
                        v-model="filterTenantId" 
                        @change="onTenantChange()">
                    <option value="">-- Semua Sekolah / Tenant --</option>
                    <option value="global">🌐 Pengumuman Global (Pusat / Seluruh Sekolah)</option>
                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}{{ t.npsn ? ' (' + t.npsn + ')' : '' }}</option>
                </select>
            </div>

            <!-- Badge Data Aktif Tepat di Samping Filter -->
            <div class="d-inline-flex align-items-center flex-shrink-0 ms-md-1">
                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 px-3 py-2 rounded-pill fs-8 font-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs" 
                      style="max-width: 340px;" 
                      :title="'Data Aktif: ' + getSelectedTenantLabel()">
                    <i class="bi bi-shield-fill-check text-blue-600 flex-shrink-0"></i>
                    <span class="text-truncate d-inline-block" style="max-width: 280px;">
                        Data Aktif: <strong>{{ getSelectedTenantLabel() }}</strong>
                    </span>
                </span>
            </div>
        </div>
    </div>

    <!-- 3. KPI Summary Metric Cards (Ditempatkan di Atas NavTabs Sesuai Standar UI/UX) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Pengumuman -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="kpi-label">Total Pengumuman</span>
                        <h4 class="kpi-value text-blue-600">{{ stats.total_pengumuman || 0 }}</h4>
                    </div>
                    <div class="kpi-icon-box bg-blue-50 text-blue-600">
                        <i class="bi bi-broadcast"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Pengumuman Aktif -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="kpi-label">Pengumuman Aktif</span>
                        <h4 class="kpi-value text-emerald-600">{{ stats.total_aktif || 0 }}</h4>
                    </div>
                    <div class="kpi-icon-box bg-emerald-50 text-emerald-600">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Kategori Informasi -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="kpi-label">Kategori Informasi</span>
                        <h4 class="kpi-value text-indigo-600">{{ stats.total_kategori || 0 }}</h4>
                    </div>
                    <div class="kpi-icon-box bg-indigo-50 text-indigo-600">
                        <i class="bi bi-tags-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Sasaran Publik -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="kpi-label">Sasaran Publik</span>
                        <h4 class="kpi-value text-amber-600">{{ stats.total_public || 0 }}</h4>
                    </div>
                    <div class="kpi-icon-box bg-amber-50 text-amber-600">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Horizontal NavTabs (Single-Row Modern Pill NavTab & 3-Way Scroller Engine) -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
        <div class="d-flex align-items-center position-relative">
            <!-- Tombol Panah Kiri -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('pengumumanNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Container Deretan Tab -->
            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="pengumumanNavTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'pengumuman'}" 
                                @click="switchTab('pengumuman')">
                            <i class="bi bi-megaphone-fill me-2 fs-6"></i> Daftar Pengumuman
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'kategori'}" 
                                @click="switchTab('kategori')">
                            <i class="bi bi-tags-fill me-2 fs-6"></i> Manajemen Kategori
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tombol Panah Kanan -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('pengumumanNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         5. TAB CONTENT 1: DAFTAR PENGUMUMAN
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'pengumuman'">
        
        <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 mb-4 animate-fade-in">
            
            <!-- Filter Lanjutan & Toolbar -->
            <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-3.5 p-md-4 mb-4 shadow-2xs">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-slate-200/60">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-funnel-fill text-blue-600 fs-7"></i>
                        <span class="fs-8 fw-bold text-slate-800 text-uppercase tracking-wider">Penyaringan & Filter Pengumuman</span>
                    </div>
                    <button v-if="searchQuery || filterKategoriId || filterVisibilitas || filterStatus !== ''" 
                            type="button" 
                            @click="resetFilters()" 
                            class="btn btn-sm btn-link text-slate-500 hover:text-rose-600 p-0 fs-8 text-decoration-none d-flex align-items-center gap-1">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                    </button>
                </div>

                <div class="row g-3 align-items-end">
                    <!-- Filter 1: Kategori -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Kategori Informasi</label>
                        <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                                v-model="filterKategoriId" 
                                @change="fetchPengumuman()"
                                style="height: 38px;">
                            <option value="">-- Semua Kategori --</option>
                            <option v-for="kat in kategoriList" :key="kat.id" :value="kat.id">{{ kat.nama_kategori }}</option>
                        </select>
                    </div>

                    <!-- Filter 2: Sasaran Audiens -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Sasaran Audiens</label>
                        <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                                v-model="filterVisibilitas" 
                                @change="fetchPengumuman()"
                                style="height: 38px;">
                            <option value="">-- Semua Sasaran --</option>
                            <option value="public">🌐 Publik & Warga Sekolah</option>
                            <option value="guru">👨‍🏫 Dewan Guru & Tendik</option>
                            <option value="siswa">🎓 Peserta Didik (Siswa)</option>
                            <option value="private">🔒 Role Spesifik</option>
                        </select>
                    </div>

                    <!-- Filter 3: Status Publikasi -->
                    <div class="col-12 col-sm-6 col-lg-2">
                        <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Status</label>
                        <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                                v-model="filterStatus" 
                                @change="fetchPengumuman()"
                                style="height: 38px;">
                            <option value="">-- Semua --</option>
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>

                    <!-- Filter 4: Pencarian Universal -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Pencarian Judul / Warta</label>
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute top-50 translate-middle-y text-slate-400 ms-3" style="font-size: 0.85rem;"></i>
                            <input type="text" 
                                   class="form-control form-control-sm ps-5 pe-5 bg-white border-slate-200 rounded-xl text-slate-800 fs-8 font-medium shadow-2xs" 
                                   placeholder="Cari judul, warta, isi..." 
                                   v-model="searchQuery" 
                                   @input="debounceSearch()"
                                   style="height: 38px;">
                            <button v-if="searchQuery" type="button" class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y text-slate-400 hover:text-slate-600 text-decoration-none p-0 me-3" @click="searchQuery = ''; fetchPengumuman()">
                                <i class="bi bi-x-circle-fill fs-7"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Table Architecture: Pengumuman -->
            <div class="table-responsive" style="margin-bottom: 1.25rem;">
                <table class="pengguna-table table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 55px;">NO</th>
                            <th>INFORMASI & WARTA PENGUMUMAN</th>
                            <th class="text-center" style="width: 170px;">KATEGORI</th>
                            <th class="text-center" style="width: 140px;">AUDIENS</th>
                            <th class="text-center" style="width: 120px;">STATUS</th>
                            <th class="text-center" style="width: 120px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="loadingPengumuman">
                            <td colspan="6" class="text-center py-5 text-slate-400">
                                <div class="spinner-border spinner-border-sm text-blue-600 me-2" role="status"></div>
                                <span class="font-semibold fs-8">Memuat daftar pengumuman...</span>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-else-if="filteredPengumumanList.length === 0">
                            <td colspan="6" class="text-center py-5">
                                <div class="py-4">
                                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 d-inline-flex align-items-center justify-content-center fs-3 mb-2 shadow-2xs" style="width: 48px; height: 48px;">
                                        <i class="bi bi-megaphone-fill"></i>
                                    </div>
                                    <h6 class="fw-bold text-slate-800 fs-7 mb-1">Belum Ada Pengumuman</h6>
                                    <p class="text-slate-400 fs-8 mb-3">Tidak ada rekaman warta yang cocok dengan kriteria filter.</p>
                                    <button type="button" class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs" @click="openModalPengumuman()">
                                        <i class="bi bi-plus-circle-fill me-1"></i> Buat Pengumuman Baru
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <tr v-else v-for="(item, index) in paginatedPengumumanList" :key="item.id">
                            <!-- 1. No -->
                            <td class="text-center font-bold text-slate-400 fs-8">
                                {{ (currentPage - 1) * perPage + index + 1 }}
                            </td>

                            <!-- 2. Informasi & Warta -->
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <a href="javascript:void(0)" 
                                           @click="previewPengumuman(item)" 
                                           class="fw-bold text-slate-800 fs-8 hover:text-blue-600 transition text-decoration-none">
                                            {{ item.judul }}
                                        </a>
                                        <span v-if="!item.tenant_id" class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded-pill fs-9 font-bold">
                                            <i class="bi bi-globe me-0.5"></i> Global
                                        </span>
                                        <span v-else-if="item.nama_sekolah" class="badge bg-slate-100 text-slate-600 border border-slate-200 px-2 py-0.5 rounded-pill fs-9 font-medium">
                                            <i class="bi bi-building me-0.5"></i> {{ item.nama_sekolah }}
                                        </span>
                                    </div>
                                    <p class="text-slate-500 fs-9 mb-0 text-truncate" style="max-width: 520px;">
                                        {{ item.deskripsi || item.isi_pengumuman || '— Tidak ada ringkasan deskripsi —' }}
                                    </p>
                                    <div class="d-flex align-items-center gap-3 text-slate-400 fs-9 font-medium mt-0.5 flex-wrap">
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-person-fill text-slate-400"></i> {{ item.nama_pembuat || 'Admin' }}
                                        </span>
                                        <span class="text-slate-300">•</span>
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-calendar3 text-slate-400"></i> {{ formatDateIndo(item.created_at) }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 3. Kategori -->
                            <td class="text-center">
                                <span class="badge px-2.5 py-1 rounded-pill fs-9 font-bold border d-inline-flex align-items-center gap-1" :style="getKategoriBadgeStyle(item.nama_kategori)">
                                    <i class="bi bi-tag-fill"></i> {{ item.nama_kategori || 'Umum' }}
                                </span>
                            </td>

                            <!-- 4. Audiens -->
                            <td class="text-center">
                                <span class="badge px-2.5 py-1 rounded-pill fs-9 font-bold border d-inline-flex align-items-center gap-1" :class="getVisibilitasBadgeClass(item.visibilitas)">
                                    <i class="bi" :class="getVisibilitasIcon(item.visibilitas)"></i>
                                    {{ getVisibilitasLabel(item.visibilitas) }}
                                </span>
                                <div v-if="item.visibilitas === 'private' && item.target_roles" class="text-slate-400 fs-9 mt-1 font-semibold">
                                    {{ formatTargetRoles(item.target_roles) }}
                                </div>
                            </td>

                            <!-- 5. Status -->
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-sm rounded-pill px-2.5 py-1 fs-9 font-bold border shadow-2xs transition d-inline-flex align-items-center gap-1"
                                        :class="item.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'"
                                        @click="toggleStatusPengumuman(item)" 
                                        title="Beralih Status">
                                    <i class="bi" :class="item.is_active ? 'bi-check-circle-fill text-emerald-600' : 'bi-dash-circle text-slate-400'"></i>
                                    {{ item.is_active ? 'Aktif' : 'Draft' }}
                                </button>
                            </td>

                            <!-- 6. Aksi -->
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center align-items-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-light border border-slate-200 text-blue-600 rounded-xl d-inline-flex align-items-center justify-content-center shadow-2xs hover-lift"
                                            style="width: 32px; height: 32px;"
                                            @click="previewPengumuman(item)" 
                                            title="Lihat Detail Warta">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-light border border-slate-200 text-amber-600 rounded-xl d-inline-flex align-items-center justify-content-center shadow-2xs hover-lift"
                                            style="width: 32px; height: 32px;"
                                            @click="editPengumuman(item)" 
                                            title="Edit Warta">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-light border border-slate-200 text-rose-600 rounded-xl d-inline-flex align-items-center justify-content-center shadow-2xs hover-lift"
                                            style="width: 32px; height: 32px;"
                                            @click="deletePengumuman(item)" 
                                            title="Hapus Warta">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Pagination Toolbar Standard: Pengumuman -->
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top border-slate-100 mt-2 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="fs-8 text-slate-500">
                        Menampilkan <strong>{{ filteredPengumumanList.length === 0 ? 0 : (currentPage - 1) * perPage + 1 }}</strong> s.d. <strong>{{ Math.min(currentPage * perPage, filteredPengumumanList.length) }}</strong> dari <strong>{{ filteredPengumumanList.length }}</strong> warta
                    </span>
                    <div class="d-flex align-items-center gap-1.5 ms-2">
                        <span class="fs-9 text-slate-400 text-uppercase font-bold">Baris:</span>
                        <select id="per_page_select_pengumuman" 
                                class="form-select form-select-sm perpage-select shadow-2xs cursor-pointer" 
                                v-model="perPage" 
                                @change="currentPage = 1" 
                                title="Jumlah baris per halaman">
                            <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>
                </div>

                <nav v-if="totalPages > 1">
                    <ul class="pagination pagination-modern m-0">
                        <li class="page-item" :class="{disabled: currentPage === 1}">
                            <a class="page-link" href="#" @click.prevent="currentPage = 1" title="Halaman Pertama">&laquo;</a>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === 1}">
                            <a class="page-link" href="#" @click.prevent="currentPage--" title="Halaman Sebelumnya">&lsaquo;</a>
                        </li>
                        <li class="page-item" v-for="page in displayedPages" :key="page" 
                            :class="{active: page === currentPage, disabled: page === '...'}">
                            <a class="page-link" href="#" @click.prevent="page !== '...' ? currentPage = page : null">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === totalPages}">
                            <a class="page-link" href="#" @click.prevent="currentPage++" title="Halaman Selanjutnya">&rsaquo;</a>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === totalPages}">
                            <a class="page-link" href="#" @click.prevent="currentPage = totalPages" title="Halaman Terakhir">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         6. TAB CONTENT 2: MANAJEMEN KATEGORI
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'kategori'">
        
        <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 mb-4 animate-fade-in">
            
            <!-- Filter & Search Toolbar Kategori (Clean & Modern Standard) -->
            <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-3 mb-4 shadow-2xs">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2.5">
                    <!-- Search Box Universal Kategori -->
                    <div class="position-relative flex-grow-1" style="min-width: 240px; max-width: 440px;">
                        <i class="bi bi-search position-absolute top-50 translate-middle-y text-slate-400 ms-3" style="font-size: 0.85rem;"></i>
                        <input type="text" 
                               class="form-control form-control-sm ps-5 pe-5 bg-white border-slate-200 rounded-xl text-slate-800 fs-8 font-medium shadow-2xs focus:bg-white" 
                               placeholder="Cari nama kategori informasi..." 
                               v-model="searchKategori"
                               @input="currentPageKategori = 1"
                               style="height: 38px;">
                        <button v-if="searchKategori" 
                                type="button" 
                                class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y text-slate-400 hover:text-slate-600 text-decoration-none p-0 me-3" 
                                @click="searchKategori = ''; currentPageKategori = 1"
                                title="Hapus pencarian">
                            <i class="bi bi-x-circle-fill fs-7"></i>
                        </button>
                    </div>

                    <!-- Action & Result Badge -->
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                        <span v-if="searchKategori" class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-pill px-3 py-2 fs-8 font-semibold shadow-2xs">
                            Ditemukan: <strong>{{ filteredKategoriList.length }}</strong> kategori
                        </span>
                        <button type="button" 
                                class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-xl px-3 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                                @click="fetchKategoriList()" 
                                title="Segarkan Data Kategori">
                            <i class="bi bi-arrow-clockwise" :class="{'spin': loadingKategori}"></i>
                            <span class="d-none d-sm-inline">Segarkan</span>
                        </button>
                        <button type="button" 
                                class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                                @click="openModalKategori()">
                            <i class="bi bi-plus-circle-fill"></i>
                            <span>Tambah Kategori</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modern Table Architecture: Kategori -->
            <div class="table-responsive" style="margin-bottom: 1.25rem;">
                <table class="pengguna-table table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 55px;">NO</th>
                            <th>NAMA KATEGORI INFORMASI</th>
                            <th class="text-center" style="width: 170px;">JUMLAH WARTA</th>
                            <th class="text-center" style="width: 180px;">LINGKUP SEKOLAH</th>
                            <th class="text-center" style="width: 110px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="loadingKategori">
                            <td colspan="5" class="text-center py-5 text-slate-400">
                                <div class="spinner-border spinner-border-sm text-indigo-600 me-2" role="status"></div>
                                <span class="font-semibold fs-8">Memuat kategori...</span>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-else-if="filteredKategoriList.length === 0">
                            <td colspan="5" class="text-center py-5">
                                <div class="py-4">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 d-inline-flex align-items-center justify-content-center fs-3 mb-2 shadow-2xs" style="width: 48px; height: 48px;">
                                        <i class="bi bi-tags-fill"></i>
                                    </div>
                                    <h6 class="fw-bold text-slate-800 fs-7 mb-1">Belum Ada Kategori</h6>
                                    <p class="text-slate-400 fs-8 mb-3">Tambahkan rubrik kategori untuk mengklasifikasi warta.</p>
                                    <button type="button" class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs" @click="openModalKategori()">
                                        <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kategori Baru
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <tr v-else v-for="(kat, kIdx) in paginatedKategoriList" :key="kat.id">
                            <td class="text-center font-bold text-slate-400 fs-8">
                                {{ (currentPageKategori - 1) * perPageKategori + kIdx + 1 }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 d-flex align-items-center justify-content-center fs-6 flex-shrink-0 shadow-2xs" style="width: 36px; height: 36px;">
                                        <i class="bi bi-bookmark-fill"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-slate-900 fs-8 d-block">{{ kat.nama_kategori }}</span>
                                        <span class="text-slate-400 fs-9">Dibuat {{ formatDateIndo(kat.created_at) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-pill font-bold fs-9">
                                    {{ kat.total_pengumuman || 0 }} Warta
                                </span>
                            </td>
                            <td class="text-center">
                                <span v-if="!kat.tenant_id" class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-1 rounded-pill fs-9 font-bold">
                                    <i class="bi bi-globe me-0.5"></i> Global
                                </span>
                                <span v-else class="badge bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-1 rounded-pill fs-9 font-medium">
                                    <i class="bi bi-building me-0.5"></i> {{ kat.nama_sekolah || 'Sekolah' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center align-items-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-light border border-slate-200 text-amber-600 rounded-xl d-inline-flex align-items-center justify-content-center shadow-2xs hover-lift"
                                            style="width: 32px; height: 32px;"
                                            @click="editKategori(kat)" 
                                            title="Edit Kategori">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-light border border-slate-200 text-rose-600 rounded-xl d-inline-flex align-items-center justify-content-center shadow-2xs hover-lift"
                                            style="width: 32px; height: 32px;"
                                            @click="deleteKategori(kat)" 
                                            title="Hapus Kategori">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Pagination Toolbar Standard: Kategori -->
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top border-slate-100 mt-2 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="fs-8 text-slate-500">
                        Menampilkan <strong>{{ filteredKategoriList.length === 0 ? 0 : (currentPageKategori - 1) * perPageKategori + 1 }}</strong> s.d. <strong>{{ Math.min(currentPageKategori * perPageKategori, filteredKategoriList.length) }}</strong> dari <strong>{{ filteredKategoriList.length }}</strong> kategori
                    </span>
                    <div class="d-flex align-items-center gap-1.5 ms-2">
                        <span class="fs-9 text-slate-400 text-uppercase font-bold">Baris:</span>
                        <select class="form-select form-select-sm perpage-select shadow-2xs cursor-pointer" 
                                v-model="perPageKategori" 
                                @change="currentPageKategori = 1" 
                                title="Jumlah baris per halaman">
                            <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>
                </div>

                <nav v-if="totalKategoriPages > 1">
                    <ul class="pagination pagination-modern m-0">
                        <li class="page-item" :class="{disabled: currentPageKategori === 1}">
                            <a class="page-link" href="#" @click.prevent="currentPageKategori = 1" title="Halaman Pertama">&laquo;</a>
                        </li>
                        <li class="page-item" :class="{disabled: currentPageKategori === 1}">
                            <a class="page-link" href="#" @click.prevent="currentPageKategori--" title="Halaman Sebelumnya">&lsaquo;</a>
                        </li>
                        <li class="page-item" v-for="page in displayedKategoriPages" :key="page" 
                            :class="{active: page === currentPageKategori, disabled: page === '...'}">
                            <a class="page-link" href="#" @click.prevent="page !== '...' ? currentPageKategori = page : null">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{disabled: currentPageKategori === totalKategoriPages}">
                            <a class="page-link" href="#" @click.prevent="currentPageKategori++" title="Halaman Selanjutnya">&rsaquo;</a>
                        </li>
                        <li class="page-item" :class="{disabled: currentPageKategori === totalKategoriPages}">
                            <a class="page-link" href="#" @click.prevent="currentPageKategori = totalKategoriPages" title="Halaman Terakhir">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
          7. MODAL 1: BUAT / EDIT PENGUMUMAN
          ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-flex': modalPengumuman.show}" tabindex="-1" v-if="modalPengumuman.show">
        <div class="modal-dialog modal-dialog-centered modal-lg my-auto" style="width: 100%; max-width: 820px; max-height: 90vh;">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in d-flex flex-column" style="max-height: 90vh;">
                <!-- Header -->
                <div class="modal-header px-4 py-3 bg-blue-600 text-white d-flex align-items-center justify-content-between border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs flex-shrink-0">
                            <i class="bi" :class="modalPengumuman.isEdit ? 'bi-pencil-square' : 'bi-megaphone-fill'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-white text-base mb-0">
                                {{ modalPengumuman.isEdit ? 'Edit Warta Pengumuman' : 'Terbitkan Pengumuman Baru' }}
                            </h5>
                            <span class="text-blue-100 fs-9">
                                Lengkapi rincian berita, kategori topik, dan sasaran pembaca
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalPengumuman.show = false" aria-label="Tutup"></button>
                </div>

                <form @submit.prevent="submitPengumuman()" class="d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 0;">
                    <div class="modal-body p-4 text-slate-700 fs-8 bg-slate-50/50 overflow-y-auto flex-grow-1" style="max-height: calc(90vh - 140px);">
                        <div class="row g-3">
                            
                            <!-- Judul Pengumuman -->
                            <div class="col-12">
                                <label class="form-label fs-9 font-bold text-slate-600 text-uppercase mb-1">
                                    Judul Pengumuman <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       v-model="modalPengumuman.form.judul" 
                                       required 
                                       placeholder="Contoh: Jadwal Pelaksanaan Asesmen Sumatif Akhir Semester (ASAS)" 
                                       class="form-control form-control-sm rounded-xl border-slate-200 fs-8 font-semibold p-2.5 shadow-2xs bg-white">
                            </div>

                            <!-- Kategori & Scope Tenant -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fs-9 font-bold text-slate-600 text-uppercase mb-1">
                                    Kategori Informasi <span class="text-rose-500">*</span>
                                </label>
                                <select v-model="modalPengumuman.form.kategori_id" 
                                        required 
                                        class="form-select form-select-sm rounded-xl border-slate-200 fs-8 font-semibold p-2.5 shadow-2xs bg-white cursor-pointer">
                                    <option value="" disabled>-- Pilih Kategori Topik --</option>
                                    <option v-for="kat in modalKategoriOptions" :key="kat.id" :value="kat.id">
                                        {{ kat.nama_kategori }}
                                    </option>
                                </select>
                            </div>

                            <!-- Superadmin Tenant Scope -->
                            <div class="col-12 col-md-6" v-if="isSuperAdmin">
                                <label class="form-label fs-9 font-bold text-slate-600 text-uppercase mb-1">
                                    Lingkup Sekolah / Tenant <span class="text-rose-500">*</span>
                                </label>
                                <select v-model="modalPengumuman.form.tenant_id" 
                                        @change="onModalTenantChange()" 
                                        class="form-select form-select-sm rounded-xl border-slate-200 fs-8 font-semibold p-2.5 shadow-2xs bg-white cursor-pointer">
                                    <option value="global">🌐 Pengumuman Global (Seluruh Sekolah/Tenant)</option>
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <!-- Interactive Audience Selection Grid -->
                            <div class="col-12">
                                <label class="form-label fs-9 font-bold text-slate-600 text-uppercase mb-1.5">
                                    Sasaran Audiens (Target Pembaca) <span class="text-rose-500">*</span>
                                </label>
                                
                                <div class="row g-2">
                                    <!-- Public -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="{'active': modalPengumuman.form.visibilitas === 'public'}">
                                            <input type="radio" value="public" v-model="modalPengumuman.form.visibilitas" class="d-none">
                                            <div class="w-8 h-8 rounded-xl d-flex align-items-center justify-content-center mb-1.5 fs-5"
                                                 :class="modalPengumuman.form.visibilitas === 'public' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-globe2"></i>
                                            </div>
                                            <span class="fw-bold fs-8">Publik</span>
                                            <small class="fs-9 text-slate-400">Semua Warga</small>
                                        </label>
                                    </div>

                                    <!-- Guru & Tendik -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="{'active': modalPengumuman.form.visibilitas === 'guru'}">
                                            <input type="radio" value="guru" v-model="modalPengumuman.form.visibilitas" class="d-none">
                                            <div class="w-8 h-8 rounded-xl d-flex align-items-center justify-content-center mb-1.5 fs-5"
                                                 :class="modalPengumuman.form.visibilitas === 'guru' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-person-badge-fill"></i>
                                            </div>
                                            <span class="fw-bold fs-8">Dewan Guru</span>
                                            <small class="fs-9 text-slate-400">Guru & Tendik</small>
                                        </label>
                                    </div>

                                    <!-- Siswa -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="{'active': modalPengumuman.form.visibilitas === 'siswa'}">
                                            <input type="radio" value="siswa" v-model="modalPengumuman.form.visibilitas" class="d-none">
                                            <div class="w-8 h-8 rounded-xl d-flex align-items-center justify-content-center mb-1.5 fs-5"
                                                 :class="modalPengumuman.form.visibilitas === 'siswa' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-mortarboard-fill"></i>
                                            </div>
                                            <span class="fw-bold fs-8">Peserta Didik</span>
                                            <small class="fs-9 text-slate-400">Khusus Siswa</small>
                                        </label>
                                    </div>

                                    <!-- Spesifik Role -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="{'active': modalPengumuman.form.visibilitas === 'private'}">
                                            <input type="radio" value="private" v-model="modalPengumuman.form.visibilitas" class="d-none">
                                            <div class="w-8 h-8 rounded-xl d-flex align-items-center justify-content-center mb-1.5 fs-5"
                                                 :class="modalPengumuman.form.visibilitas === 'private' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-lock-fill"></i>
                                            </div>
                                            <span class="fw-bold fs-8">Role Spesifik</span>
                                            <small class="fs-9 text-slate-400">Kustom Role</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Target Roles Checkbox Group (Conditional if Private) -->
                            <div class="col-12" v-if="modalPengumuman.form.visibilitas === 'private'">
                                <div class="p-3 bg-rose-50/60 border border-rose-200 rounded-2xl">
                                    <div class="d-flex align-items-center gap-1.5 mb-2">
                                        <i class="bi bi-shield-lock-fill text-rose-600"></i>
                                        <span class="fw-bold text-rose-900 fs-8">Pilih Role Khusus Penerima Warta:</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <label v-for="r in rolesList" :key="r.id" class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-xl border bg-white cursor-pointer transition fs-8 font-semibold"
                                               :class="modalPengumuman.form.target_roles.includes(r.nama_role) ? 'border-rose-500 text-rose-700 bg-rose-50/60 shadow-2xs' : 'border-slate-200 text-slate-600'">
                                            <input class="form-check-input text-rose-600 cursor-pointer m-0" type="checkbox" :value="r.nama_role" v-model="modalPengumuman.form.target_roles">
                                            <span>{{ r.nama_role }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Isi Lengkap Pengumuman -->
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label fs-9 font-bold text-slate-600 text-uppercase mb-0">
                                        Isi Lengkap Pengumuman <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="badge bg-slate-100 text-slate-500 font-monospace px-2 py-0.5 rounded-pill fs-9">
                                        {{ (modalPengumuman.form.deskripsi || '').length }} karakter
                                    </span>
                                </div>
                                <textarea v-model="modalPengumuman.form.deskripsi" 
                                          required 
                                          rows="6" 
                                          placeholder="Tuliskan rincian lengkap instruksi, waktu pelaksanaan, lokasi kegiatan, atau lampiran ketentuan warta di sini..." 
                                          class="form-control rounded-2xl border-slate-200 p-3 shadow-2xs bg-white fs-8"></textarea>
                            </div>

                            <!-- Status Penayangan Toggle Card -->
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center fs-5"
                                             :class="modalPengumuman.form.is_active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400'">
                                            <i class="bi" :class="modalPengumuman.form.is_active ? 'bi-broadcast-pin' : 'bi-pause-circle-fill'"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-slate-800 fs-8 d-block">Status Penayangan</span>
                                            <span class="text-slate-400 fs-9">
                                                {{ modalPengumuman.form.is_active ? 'Warta ini akan langsung tampil di beranda portal sekolah.' : 'Warta disimpan sebagai draft (tidak tampil di publik).' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch fs-5 mb-0">
                                        <input class="form-check-input cursor-pointer" type="checkbox" v-model="modalPengumuman.form.is_active">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="modal-footer px-4 py-3 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                        <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl font-semibold px-4" @click="modalPengumuman.show = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-xl font-bold px-4 d-flex align-items-center gap-1.5" :disabled="modalPengumuman.saving">
                            <span v-if="modalPengumuman.saving" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-send-fill text-xs"></i>
                            <span>{{ modalPengumuman.saving ? 'Menyimpan...' : (modalPengumuman.isEdit ? 'Simpan Perubahan' : 'Terbitkan Pengumuman') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
          8. MODAL 2: DETAIL / PREVIEW PENGUMUMAN
          ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-flex': modalPreview.show}" tabindex="-1" v-if="modalPreview.show">
        <div class="modal-dialog modal-dialog-centered modal-lg my-auto" style="width: 100%; max-width: 820px; max-height: 90vh;">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in d-flex flex-column" style="max-height: 90vh;">
                <!-- Header -->
                <div class="modal-header px-4 py-3 bg-slate-900 text-white d-flex align-items-center justify-content-between border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs flex-shrink-0">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-white text-base mb-0">
                                Pratinjau Warta Informasi
                            </h5>
                            <span class="text-slate-400 fs-9">Tampilan resmi portal pengumuman sekolah</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalPreview.show = false" aria-label="Tutup"></button>
                </div>

                <div class="modal-body p-4 text-slate-700 fs-8 bg-slate-50/50 overflow-y-auto flex-grow-1" style="max-height: calc(90vh - 140px);">
                    <div v-if="modalPreview.item" class="d-flex flex-column gap-3">
                        
                        <!-- Top Metadata Badges -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge px-2.5 py-1 rounded-pill fs-9 font-bold border shadow-2xs d-inline-flex align-items-center gap-1" :style="getKategoriBadgeStyle(modalPreview.item.nama_kategori)">
                                <i class="bi bi-tag-fill"></i> {{ modalPreview.item.nama_kategori || 'Umum' }}
                            </span>
                            <span class="badge px-2.5 py-1 rounded-pill fs-9 font-bold border shadow-2xs d-inline-flex align-items-center gap-1" :class="getVisibilitasBadgeClass(modalPreview.item.visibilitas)">
                                <i class="bi" :class="getVisibilitasIcon(modalPreview.item.visibilitas)"></i>
                                {{ getVisibilitasLabel(modalPreview.item.visibilitas) }}
                            </span>
                            <span v-if="modalPreview.item.is_active" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 fs-9 font-bold px-2.5 py-1 rounded-pill shadow-2xs d-inline-flex align-items-center gap-1">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i> Aktif
                            </span>
                            <span v-else class="badge bg-slate-100 text-slate-500 border border-slate-200 fs-9 font-bold px-2.5 py-1 rounded-pill shadow-2xs d-inline-flex align-items-center gap-1">
                                <i class="bi bi-pause-circle text-slate-400"></i> Draft
                            </span>
                        </div>

                        <!-- Article Title -->
                        <h4 class="fw-bold text-slate-900 fs-5 mb-0" style="line-height: 1.4;">
                            {{ modalPreview.item.judul }}
                        </h4>

                        <!-- Publisher Card Info -->
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 border border-blue-100 d-flex align-items-center justify-content-center font-bold fs-8 flex-shrink-0">
                                    {{ (modalPreview.item.nama_pembuat || 'A').substring(0, 2).toUpperCase() }}
                                </div>
                                <div>
                                    <span class="fw-bold text-slate-800 fs-8 d-block">{{ modalPreview.item.nama_pembuat || 'Administrator Humas' }}</span>
                                    <span class="text-slate-400 fs-9">Penulis & Editor Warta</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-slate-500 fs-8 font-medium flex-wrap">
                                <span v-if="!modalPreview.item.tenant_id" class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 fs-9 font-bold px-2 py-0.5 rounded-pill">
                                    <i class="bi bi-globe me-1"></i> Global (Pusat)
                                </span>
                                <span v-else-if="modalPreview.item.nama_sekolah" class="badge bg-slate-100 text-slate-700 border border-slate-200 fs-9 font-bold px-2 py-0.5 rounded-pill">
                                    <i class="bi bi-building me-1"></i> {{ modalPreview.item.nama_sekolah }}
                                </span>
                                <span class="d-inline-flex align-items-center gap-1 text-slate-500 fs-9">
                                    <i class="bi bi-calendar-event text-blue-600"></i> {{ formatDateIndo(modalPreview.item.created_at) }}
                                </span>
                            </div>
                        </div>

                        <!-- Target Roles Info -->
                        <div v-if="modalPreview.item.visibilitas === 'private' && modalPreview.item.target_roles" class="p-3 bg-rose-50/60 border border-rose-200 rounded-2xl">
                            <div class="d-flex align-items-center gap-1.5 mb-1.5">
                                <i class="bi bi-shield-lock-fill text-rose-600"></i>
                                <span class="fw-bold text-rose-900 fs-8">Penerima Khusus (Role Terpilih):</span>
                            </div>
                            <div class="d-flex flex-wrap gap-1.5">
                                <span v-for="r in (typeof modalPreview.item.target_roles === 'string' ? JSON.parse(modalPreview.item.target_roles) : modalPreview.item.target_roles)" :key="r"
                                      class="badge bg-white text-rose-700 border border-rose-200 font-semibold px-2 py-1 rounded-lg fs-9 shadow-2xs">
                                    {{ r }}
                                </span>
                            </div>
                        </div>

                        <!-- Content Body Card -->
                        <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-2xs text-slate-800 fs-8 leading-relaxed" style="white-space: pre-wrap; line-height: 1.8;">
{{ modalPreview.item.deskripsi || modalPreview.item.isi_pengumuman }}
                        </div>

                    </div>
                </div>

                <div class="modal-footer px-4 py-3 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl font-semibold px-4" @click="modalPreview.show = false">
                        Tutup
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-xl font-bold px-3 py-1.5 fs-8 shadow-2xs d-inline-flex align-items-center gap-1.5" @click="modalPreview.show = false; deletePengumuman(modalPreview.item)">
                            <i class="bi bi-trash3"></i>
                            <span>Hapus</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 fs-8 shadow-2xs d-inline-flex align-items-center gap-1.5" @click="modalPreview.show = false; editPengumuman(modalPreview.item)">
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit Pengumuman</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
          9. MODAL 3: TAMBAH / EDIT KATEGORI
          ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-flex': modalKategori.show}" tabindex="-1" v-if="modalKategori.show">
        <div class="modal-dialog modal-dialog-centered modal-md my-auto" style="width: 100%; max-width: 520px; max-height: 90vh;">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in d-flex flex-column" style="max-height: 90vh;">
                <!-- Header -->
                <div class="modal-header px-4 py-3 bg-indigo-600 text-white d-flex align-items-center justify-content-between border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs flex-shrink-0">
                            <i class="bi bi-tags-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-white text-base mb-0">
                                {{ modalKategori.isEdit ? 'Edit Kategori Warta' : 'Tambah Kategori Informasi' }}
                            </h5>
                            <span class="text-indigo-100 fs-9">Klasifikasi topik & rubrik pengumuman</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalKategori.show = false" aria-label="Tutup"></button>
                </div>

                <form @submit.prevent="submitKategori()" class="d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 0;">
                    <div class="modal-body p-4 text-slate-700 fs-8 bg-slate-50/50 overflow-y-auto flex-grow-1" style="max-height: calc(90vh - 140px);">
                        <div class="mb-3">
                            <label class="form-label fs-9 font-bold text-slate-600 text-uppercase mb-1">
                                Nama Kategori Informasi <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   v-model="modalKategori.form.nama_kategori" 
                                   required 
                                   placeholder="Contoh: Akademik & Ujian, Kedisiplinan, Humas" 
                                   class="form-control form-control-sm rounded-xl border-slate-200 fs-8 font-semibold p-2.5 shadow-2xs bg-white">
                        </div>

                        <div class="mb-0" v-if="isSuperAdmin">
                            <label class="form-label fs-9 font-bold text-slate-600 text-uppercase mb-1">
                                Lingkup Sekolah / Tenant
                            </label>
                            <select v-model="modalKategori.form.tenant_id" 
                                    class="form-select form-select-sm rounded-xl border-slate-200 fs-8 font-semibold p-2.5 shadow-2xs bg-white cursor-pointer">
                                <option value="global">🌐 Kategori Global (Seluruh Sekolah/Tenant)</option>
                                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer px-4 py-3 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                        <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl font-semibold px-4" @click="modalKategori.show = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-xl font-bold px-4 d-flex align-items-center gap-1.5" :disabled="modalKategori.saving" style="background-color: #4f46e5; border-color: #4f46e5;">
                            <span v-if="modalKategori.saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-save-fill text-xs"></i>
                            <span>{{ modalKategori.saving ? 'Menyimpan...' : 'Simpan Kategori' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     10. VUE 3 CONTROLLER SETUP
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
{
    const { ref, computed, onMounted, watch } = Vue;

    window.VueAppRegistry.register('#pengumumanApp', {
        setup() {
            const rootEl = document.getElementById('pengumumanApp');
            const _baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '<?= htmlspecialchars($this->getBaseUrl(), ENT_QUOTES, 'UTF-8') ?>';
            const isSuperAdmin = ref(rootEl?.dataset?.isSuperAdmin === 'true');
            const tenants = ref(<?= json_encode($tenants, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const currentTenantId = ref(rootEl?.dataset?.tenantId || null);

            const activeTab = ref('pengumuman');
            const loading = ref(false);
            const loadingPengumuman = ref(false);
            const loadingKategori = ref(false);

            // Filters & Searches
            const urlParams = new URLSearchParams(window.location.search);
            const urlTenantId = urlParams.get('tenant_id');
            const initialTenant = (urlTenantId !== null && urlTenantId !== '')
                ? urlTenantId
                : (currentTenantId.value && currentTenantId.value !== 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' ? currentTenantId.value : '');

            const filterTenantId = ref(initialTenant);
            const searchQuery = ref('');
            const filterKategoriId = ref('');
            const filterVisibilitas = ref('');
            const filterStatus = ref('');
            const searchKategori = ref('');

            // Data Stores
            const pengumumanList = ref([]);
            const kategoriList = ref([]);
            const allKategoriList = ref([]);
            const rolesList = ref([]);
            const stats = ref({
                total_pengumuman: 0,
                total_aktif: 0,
                total_kategori: 0,
                total_public: 0
            });

            // Pagination State
            const perPageOptions = [10, 25, 50, 100];
            const perPage = ref(10);
            const currentPage = ref(1);
            const perPageKategori = ref(10);
            const currentPageKategori = ref(1);

            // Modals State
            const modalPengumuman = ref({
                show: false,
                isEdit: false,
                saving: false,
                form: {
                    id: '',
                    judul: '',
                    kategori_id: '',
                    visibilitas: 'public',
                    target_roles: [],
                    deskripsi: '',
                    is_active: true,
                    tenant_id: filterTenantId.value || 'global'
                }
            });

            const modalPreview = ref({
                show: false,
                item: null
            });

            const modalKategori = ref({
                show: false,
                isEdit: false,
                saving: false,
                form: {
                    id: '',
                    nama_kategori: '',
                    tenant_id: filterTenantId.value || 'global'
                }
            });

            const getSelectedTenantLabel = () => {
                if (!filterTenantId.value) return 'Semua Sekolah / Tenant';
                if (filterTenantId.value === 'global') return 'Pengumuman Global (Pusat)';
                const found = (tenants.value || []).find(t => String(t.id) === String(filterTenantId.value));
                return found ? found.nama_sekolah : 'Sekolah Terpilih';
            };

            const modalKategoriOptions = computed(() => {
                const modalTenant = modalPengumuman.value.form.tenant_id;
                const pool = (allKategoriList.value && allKategoriList.value.length > 0) 
                    ? allKategoriList.value 
                    : (kategoriList.value || []);

                if (!pool || pool.length === 0) return [];

                if (!modalTenant || modalTenant === 'global') {
                    const globals = pool.filter(k => !k.tenant_id);
                    return globals.length > 0 ? globals : pool;
                }
                const filtered = pool.filter(k => k.tenant_id === modalTenant);
                return filtered.length > 0 ? filtered : pool;
            });

            const onModalTenantChange = () => {
                const opts = modalKategoriOptions.value;
                if (opts && opts.length > 0) {
                    const exists = opts.some(o => o.id === modalPengumuman.value.form.kategori_id);
                    if (!exists) {
                        modalPengumuman.value.form.kategori_id = opts[0].id;
                    }
                }
            };

            const fetchOptionsAndStats = async () => {
                try {
                    let url = `${_baseUrl}/api/v1/pengumuman/options`;
                    if (filterTenantId.value) {
                        url += `?tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    }
                    const res = await axios.get(url);
                    if (res.data && res.data.success) {
                        if (res.data.data.tenants && res.data.data.tenants.length > 0) {
                            tenants.value = res.data.data.tenants;
                        }
                        allKategoriList.value = res.data.data.kategori || [];
                        rolesList.value = res.data.data.roles || [];
                        if (res.data.data.stats) {
                            stats.value = res.data.data.stats;
                        }
                    }
                } catch (e) {
                    console.error("Gagal memuat master options pengumuman:", e);
                }
            };

            const fetchPengumuman = async () => {
                loadingPengumuman.value = true;
                try {
                    let params = new URLSearchParams();
                    if (searchQuery.value) params.append('search', searchQuery.value);
                    if (filterKategoriId.value) params.append('kategori_id', filterKategoriId.value);
                    if (filterVisibilitas.value) params.append('visibilitas', filterVisibilitas.value);
                    if (filterStatus.value !== '') params.append('is_active', filterStatus.value);
                    if (filterTenantId.value) params.append('tenant_id', filterTenantId.value);

                    const res = await axios.get(`${_baseUrl}/api/v1/pengumuman?${params.toString()}`);
                    if (res.data && res.data.success) {
                        pengumumanList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error("Gagal memuat pengumuman:", e);
                } finally {
                    loadingPengumuman.value = false;
                }
            };

            const fetchKategori = async () => {
                loadingKategori.value = true;
                try {
                    let params = new URLSearchParams();
                    if (searchKategori.value) params.append('search', searchKategori.value);
                    if (filterTenantId.value) params.append('tenant_id', filterTenantId.value);

                    const res = await axios.get(`${_baseUrl}/api/v1/pengumuman/kategori?${params.toString()}`);
                    if (res.data && res.data.success) {
                        kategoriList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error("Gagal memuat kategori:", e);
                } finally {
                    loadingKategori.value = false;
                }
            };

            const refreshAll = async () => {
                loading.value = true;
                await Promise.all([
                    fetchOptionsAndStats(),
                    fetchPengumuman(),
                    fetchKategori()
                ]);
                loading.value = false;
            };

            const switchTab = (tab) => {
                activeTab.value = tab;
                if (tab === 'pengumuman') fetchPengumuman();
                else if (tab === 'kategori') fetchKategori();
            };

            const onTenantChange = async () => {
                const url = new URL(window.location.href);
                if (filterTenantId.value) {
                    url.searchParams.set('tenant_id', filterTenantId.value);
                } else {
                    url.searchParams.delete('tenant_id');
                }
                window.history.replaceState({}, '', url.toString());
                await refreshAll();
            };

            let searchTimeout = null;
            const debounceSearch = () => {
                currentPage.value = 1;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchPengumuman();
                }, 300);
            };

            const resetFilters = () => {
                searchQuery.value = '';
                filterKategoriId.value = '';
                filterVisibilitas.value = '';
                filterStatus.value = '';
                currentPage.value = 1;
                fetchPengumuman();
            };

            const filteredPengumumanList = computed(() => {
                return pengumumanList.value;
            });

            const totalPages = computed(() => {
                return Math.ceil(filteredPengumumanList.value.length / perPage.value) || 1;
            });

            const paginatedPengumumanList = computed(() => {
                const start = (currentPage.value - 1) * perPage.value;
                return filteredPengumumanList.value.slice(start, start + perPage.value);
            });

            const displayedPages = computed(() => {
                const total = totalPages.value;
                const current = currentPage.value;
                if (total <= 7) {
                    return Array.from({ length: total }, (_, i) => i + 1);
                }
                const pages = [];
                if (current <= 3) {
                    pages.push(1, 2, 3, 4, '...', total);
                } else if (current >= total - 2) {
                    pages.push(1, '...', total - 3, total - 2, total - 1, total);
                } else {
                    pages.push(1, '...', current - 1, current, current + 1, '...', total);
                }
                return pages;
            });

            watch(searchKategori, () => {
                currentPageKategori.value = 1;
            });

            const filteredKategoriList = computed(() => {
                if (!searchKategori.value || String(searchKategori.value).trim() === '') {
                    return kategoriList.value || [];
                }
                const q = String(searchKategori.value).trim().toLowerCase();
                return (kategoriList.value || []).filter(k => {
                    const nama = String(k.nama_kategori || '').toLowerCase();
                    const sekolah = String(k.nama_sekolah || '').toLowerCase();
                    return nama.includes(q) || sekolah.includes(q);
                });
            });

            const totalKategoriPages = computed(() => {
                return Math.ceil(filteredKategoriList.value.length / perPageKategori.value) || 1;
            });

            const paginatedKategoriList = computed(() => {
                const start = (currentPageKategori.value - 1) * perPageKategori.value;
                return filteredKategoriList.value.slice(start, start + perPageKategori.value);
            });

            const displayedKategoriPages = computed(() => {
                const total = totalKategoriPages.value;
                const current = currentPageKategori.value;
                if (total <= 7) {
                    return Array.from({ length: total }, (_, i) => i + 1);
                }
                const pages = [];
                if (current <= 3) {
                    pages.push(1, 2, 3, 4, '...', total);
                } else if (current >= total - 2) {
                    pages.push(1, '...', total - 3, total - 2, total - 1, total);
                } else {
                    pages.push(1, '...', current - 1, current, current + 1, '...', total);
                }
                return pages;
            });

            const openModalPengumuman = () => {
                const targetTenant = filterTenantId.value || (isSuperAdmin.value ? 'global' : currentTenantId.value);
                modalPengumuman.value.isEdit = false;
                modalPengumuman.value.form = {
                    id: '',
                    judul: '',
                    kategori_id: '',
                    visibilitas: 'public',
                    target_roles: [],
                    deskripsi: '',
                    is_active: true,
                    tenant_id: targetTenant
                };

                const opts = modalKategoriOptions.value;
                if (opts && opts.length > 0) {
                    modalPengumuman.value.form.kategori_id = opts[0].id;
                }
                modalPengumuman.value.show = true;
            };

            const editPengumuman = (item) => {
                let parsedRoles = [];
                if (item.target_roles) {
                    try {
                        parsedRoles = typeof item.target_roles === 'string' ? JSON.parse(item.target_roles) : item.target_roles;
                    } catch (e) {
                        parsedRoles = [];
                    }
                }

                const targetTenant = item.tenant_id || 'global';
                modalPengumuman.value.isEdit = true;
                modalPengumuman.value.form = {
                    id: item.id,
                    judul: item.judul,
                    kategori_id: item.kategori_id || '',
                    visibilitas: item.visibilitas || 'public',
                    target_roles: Array.isArray(parsedRoles) ? parsedRoles : [],
                    deskripsi: item.deskripsi || item.isi_pengumuman || '',
                    is_active: item.is_active,
                    tenant_id: targetTenant
                };

                const opts = modalKategoriOptions.value;
                if (opts && opts.length > 0 && !opts.some(o => o.id === modalPengumuman.value.form.kategori_id)) {
                    modalPengumuman.value.form.kategori_id = opts[0].id;
                }

                modalPengumuman.value.show = true;
            };

            const previewPengumuman = (item) => {
                modalPreview.value.item = item;
                modalPreview.value.show = true;
            };

            const submitPengumuman = async () => {
                modalPengumuman.value.saving = true;
                try {
                    const res = await axios.post(`${_baseUrl}/api/v1/pengumuman/save`, modalPengumuman.value.form);
                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.data.message || 'Pengumuman berhasil disimpan.',
                            timer: 1800,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl' }
                        });
                        modalPengumuman.value.show = false;
                        await refreshAll();
                    } else {
                        Swal.fire('Gagal!', res.data.error || 'Terjadi kesalahan saat menyimpan.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error!', e.response?.data?.error || 'Gagal menyimpan pengumuman.', 'error');
                } finally {
                    modalPengumuman.value.saving = false;
                }
            };

            const toggleStatusPengumuman = async (item) => {
                try {
                    const res = await axios.post(`${_baseUrl}/api/v1/pengumuman/toggle-status`, { id: item.id });
                    if (res.data && res.data.success) {
                        item.is_active = !item.is_active;
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: `Status: ${item.is_active ? 'Aktif' : 'Non-Aktif'}`
                        });
                    }
                } catch (e) {
                    Swal.fire('Error!', 'Gagal memperbarui status pengumuman.', 'error');
                }
            };

            const deletePengumuman = (item) => {
                Swal.fire({
                    title: '<span class="text-rose-600 fw-bold">Hapus Pengumuman Ini?</span>',
                    html: `<p class="text-slate-500 fs-8">Warta "<strong>${item.judul}</strong>" akan dihapus secara permanen dari portal.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    customClass: { popup: 'rounded-3xl' }
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const res = await axios.post(`${_baseUrl}/api/v1/pengumuman/delete`, { id: item.id });
                            if (res.data && res.data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus!',
                                    text: 'Pengumuman telah berhasil dihapus.',
                                    timer: 1500,
                                    showConfirmButton: false,
                                    customClass: { popup: 'rounded-3xl' }
                                });
                                await refreshAll();
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Gagal menghapus pengumuman.', 'error');
                        }
                    }
                });
            };

            const openModalKategori = () => {
                modalKategori.value.isEdit = false;
                modalKategori.value.form = {
                    id: '',
                    nama_kategori: '',
                    keterangan: '',
                    tenant_id: filterTenantId.value || (isSuperAdmin.value ? 'global' : currentTenantId.value)
                };
                modalKategori.value.show = true;
            };

            const editKategori = (kat) => {
                modalKategori.value.isEdit = true;
                modalKategori.value.form = {
                    id: kat.id,
                    nama_kategori: kat.nama_kategori,
                    keterangan: kat.keterangan || '',
                    tenant_id: kat.tenant_id || 'global'
                };
                modalKategori.value.show = true;
            };

            const submitKategori = async () => {
                modalKategori.value.saving = true;
                try {
                    const res = await axios.post(`${_baseUrl}/api/v1/pengumuman/kategori/save`, modalKategori.value.form);
                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.data.message || 'Kategori berhasil disimpan.',
                            timer: 1800,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl' }
                        });
                        modalKategori.value.show = false;
                        await refreshAll();
                    } else {
                        Swal.fire('Gagal!', res.data.error || 'Terjadi kesalahan saat menyimpan.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error!', e.response?.data?.error || 'Gagal menyimpan kategori.', 'error');
                } finally {
                    modalKategori.value.saving = false;
                }
            };

            const deleteKategori = (kat) => {
                Swal.fire({
                    title: '<span class="text-rose-600 fw-bold">Hapus Kategori Ini?</span>',
                    html: `<p class="text-slate-500 fs-8">Kategori "<strong>${kat.nama_kategori}</strong>" akan dihapus. Pengumuman terkait akan dialihkan ke kategori umum.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    customClass: { popup: 'rounded-3xl' }
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const res = await axios.post(`${_baseUrl}/api/v1/pengumuman/kategori/delete`, { id: kat.id });
                            if (res.data && res.data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus!',
                                    text: 'Kategori telah berhasil dihapus.',
                                    timer: 1500,
                                    showConfirmButton: false,
                                    customClass: { popup: 'rounded-3xl' }
                                });
                                await refreshAll();
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Gagal menghapus kategori.', 'error');
                        }
                    }
                });
            };

            const getKategoriBadgeStyle = (kategoriName) => {
                const k = (kategoriName || '').toLowerCase();
                if (k.includes('ujian') || k.includes('akademik')) {
                    return 'background-color: #eff6ff !important; color: #1d4ed8 !important; border: 1px solid #bfdbfe !important;';
                }
                if (k.includes('kegiatan') || k.includes('ekskul')) {
                    return 'background-color: #f0fdf4 !important; color: #15803d !important; border: 1px solid #bbf7d0 !important;';
                }
                if (k.includes('administrasi') || k.includes('keuangan')) {
                    return 'background-color: #fefce8 !important; color: #a16207 !important; border: 1px solid #fef08a !important;';
                }
                if (k.includes('disiplin') || k.includes('tertib')) {
                    return 'background-color: #fff1f2 !important; color: #be123c !important; border: 1px solid #fecdd3 !important;';
                }
                if (k.includes('libur') || k.includes('hari')) {
                    return 'background-color: #faf5ff !important; color: #7e22ce !important; border: 1px solid #e9d5ff !important;';
                }
                return 'background-color: #f8fafc !important; color: #475569 !important; border: 1px solid #e2e8f0 !important;';
            };

            const getVisibilitasBadgeClass = (vis) => {
                if (vis === 'public') return 'bg-blue-50 text-blue-700 border-blue-200';
                if (vis === 'guru') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                if (vis === 'siswa') return 'bg-purple-50 text-purple-700 border-purple-200';
                if (vis === 'private') return 'bg-rose-50 text-rose-700 border-rose-200';
                return 'bg-slate-100 text-slate-700 border-slate-200';
            };

            const getVisibilitasIcon = (vis) => {
                if (vis === 'public') return 'bi-globe2';
                if (vis === 'guru') return 'bi-person-badge-fill';
                if (vis === 'siswa') return 'bi-mortarboard-fill';
                if (vis === 'private') return 'bi-lock-fill';
                return 'bi-info-circle';
            };

            const getVisibilitasLabel = (vis) => {
                if (vis === 'public') return 'Publik';
                if (vis === 'guru') return 'Guru & Tendik';
                if (vis === 'siswa') return 'Siswa';
                if (vis === 'private') return 'Spesifik';
                return 'Semua';
            };

            const formatTargetRoles = (rolesJson) => {
                try {
                    const roles = typeof rolesJson === 'string' ? JSON.parse(rolesJson) : rolesJson;
                    if (Array.isArray(roles) && roles.length) {
                        return roles.join(', ');
                    }
                } catch (e) {}
                return '';
            };

            const formatDateIndo = (dateStr) => {
                if (!dateStr) return '—';
                try {
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                } catch(e) { return dateStr; }
            };

            watch(activeTab, (newTab) => {
                currentPage.value = 1;
                currentPageKategori.value = 1;
                if (newTab === 'pengumuman') fetchPengumuman();
                else if (newTab === 'kategori') fetchKategori();
            });

            watch(filterTenantId, () => {
                currentPage.value = 1;
                currentPageKategori.value = 1;
                refreshAll();
            });

            onMounted(() => {
                refreshAll();
            });

            return {
                isSuperAdmin,
                tenants,
                currentTenantId,
                activeTab,
                loading,
                loadingPengumuman,
                loadingKategori,
                filterTenantId,
                searchQuery,
                filterKategoriId,
                filterVisibilitas,
                filterStatus,
                searchKategori,
                pengumumanList,
                kategoriList,
                allKategoriList,
                modalKategoriOptions,
                rolesList,
                stats,
                modalPengumuman,
                modalPreview,
                modalKategori,
                filteredPengumumanList,
                perPageOptions,
                perPage,
                currentPage,
                totalPages,
                displayedPages,
                paginatedPengumumanList,
                filteredKategoriList,
                perPageKategori,
                currentPageKategori,
                totalKategoriPages,
                displayedKategoriPages,
                paginatedKategoriList,
                fetchPengumuman,
                fetchKategori,
                refreshAll,
                switchTab,
                onTenantChange,
                onModalTenantChange,
                getSelectedTenantLabel,
                debounceSearch,
                resetFilters,
                openModalPengumuman,
                editPengumuman,
                previewPengumuman,
                submitPengumuman,
                toggleStatusPengumuman,
                deletePengumuman,
                openModalKategori,
                editKategori,
                submitKategori,
                deleteKategori,
                getKategoriBadgeStyle,
                getVisibilitasBadgeClass,
                getVisibilitasIcon,
                getVisibilitasLabel,
                formatTargetRoles,
                formatDateIndo
            };
        }
    });
}
</script>
