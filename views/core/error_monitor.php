<?php
/**
 * View: Error Monitor — System Debugger
 * Akses: Super Admin Only
 * SINTA SaaS - Standardized Vue 3 Dynamic Single Page Experience
 */
$isSuperAdmin = $isSuperAdmin ?? true;
$tenants = $tenants ?? [];
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
        min-width: 1050px;
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
        vertical-align: top;
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

    .error-msg-box {
        color: #0f172a;
        font-weight: 600;
        font-size: 0.825rem;
        line-height: 1.5;
        word-break: break-word;
        white-space: normal;
    }
    
    .source-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 6px 10px;
        transition: all 0.2s;
    }
    .source-box:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .tenant-chip {
        font-size: 0.7rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        padding: 2px 8px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        max-width: 100%;
    }

    .method-badge {
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        padding: 3px 6px;
        border-radius: 6px;
        font-family: ui-monospace, monospace;
    }
    .method-get { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .method-post { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .method-client { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
    .method-put { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .method-delete { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .line-badge {
        background: #fefce8;
        color: #854d0e;
        border: 1px solid #fde68a;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
    }

    .badge-super-admin {
        background-color: #ffe4e6 !important;
        color: #9f1239 !important;
        border: 1px solid #fecdd3 !important;
    }

    /* High-Contrast Level Badges */
    .badge-level {
        font-family: ui-monospace, monospace !important;
        font-size: 0.725rem !important;
        font-weight: 700 !important;
        padding: 0.25rem 0.65rem !important;
        border-radius: 9999px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
        line-height: 1.3 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        white-space: nowrap !important;
        max-width: 150px !important;
    }
    .badge-level-fatal {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        border: 1px solid #fca5a5 !important;
    }
    .badge-level-warning {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fcd34d !important;
    }
    .badge-level-js {
        background-color: #f3e8ff !important;
        color: #6b21a8 !important;
        border: 1px solid #d8b4fe !important;
    }
    .badge-level-api {
        background-color: #ffedd5 !important;
        color: #9a3412 !important;
        border: 1px solid #fdba74 !important;
    }
    .badge-level-notice {
        background-color: #e0f2fe !important;
        color: #0369a1 !important;
        border: 1px solid #7dd3fc !important;
    }
    .badge-level-deprecated {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
    }
    .badge-level-default {
        background-color: #f8fafc !important;
        color: #0f172a !important;
        border: 1px solid #cbd5e1 !important;
    }

    .header-icon-box-danger {
        width: 48px !important;
        height: 48px !important;
        background: linear-gradient(135deg, #e11d48 0%, #be123c 100%) !important;
        color: #ffffff !important;
        border-radius: 1rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.28) !important;
        flex-shrink: 0 !important;
    }

    .shadow-2xs { box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); }
    .shadow-xs { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); }
    .hover-lift { transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .hover-lift:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.06); }
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<div id="errorMonitorApp" v-cloak>

    <!-- 1. Row Header & Action Toolbar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="header-icon-box-danger shadow-xs">
                <i class="bi bi-bug-fill fs-4 text-white"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold text-slate-900 fs-4 mb-0">Error Monitor</h3>
                    <span class="badge-super-admin rounded-pill px-2.5 py-1 fs-9 font-bold d-inline-flex align-items-center gap-1.5 shadow-2xs">
                        <i class="bi bi-shield-fill-check text-rose-600 fs-7"></i>
                        <span class="text-rose-900 font-bold">Super Admin</span>
                    </span>
                </div>
                <p class="text-slate-500 fs-8 mb-0 mt-0.5">Pelacakan real-time exception PHP, fatal error, SQL failure, dan crash log telemetri klien secara terpusat.</p>
            </div>
        </div>
        
        <!-- Action Toolbar -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Clear All Logs -->
            <button type="button" 
                    id="btn-clear-all-errors"
                    class="btn btn-sm btn-light border border-rose-200 text-rose-700 bg-rose-50/60 rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                    @click="confirmClearAll"
                    :disabled="loadingClear || totalErrors === 0">
                <span v-if="loadingClear" class="spinner-border spinner-border-sm" role="status"></span>
                <i v-else class="bi bi-trash3-fill text-rose-600"></i>
                <span>Clear All Logs</span>
                <span class="badge rounded-pill ms-1" :class="totalErrors > 0 ? 'bg-danger text-white' : 'bg-slate-200 text-slate-600'">
                    {{ totalErrors }}
                </span>
            </button>

            <!-- Auto Refresh Selector -->
            <div class="d-flex align-items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-2.5 py-1 shadow-2xs" style="height: 38px;">
                <span v-if="autoRefreshSeconds > 0" class="spinner-grow spinner-grow-sm text-danger flex-shrink-0" style="width: 8px; height: 8px;"></span>
                <i v-else class="bi bi-broadcast text-slate-400 fs-7"></i>
                <select class="form-select form-select-sm border-0 bg-transparent text-slate-700 fs-8 font-semibold p-0 cursor-pointer" 
                        style="width: 86px;" 
                        v-model="autoRefreshSeconds" 
                        @change="onAutoRefreshChange">
                    <option :value="0">Auto: Off</option>
                    <option :value="5">Auto: 5s</option>
                    <option :value="10">Auto: 10s</option>
                    <option :value="30">Auto: 30s</option>
                </select>
            </div>

            <!-- Manual Refresh Button -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                    @click="loadErrors(currentPage)" 
                    :disabled="loading"
                    title="Segarkan Data Log">
                <i class="bi bi-arrow-clockwise" :class="{'spin': loading}"></i>
                <span>Segarkan</span>
            </button>
        </div>
    </div>

    <!-- 2. Compact School Selector Banner (Khusus Super Admin Auto-Filter) -->
    <div class="mb-4 p-3 px-md-4 rounded-2xl shadow-2xs border border-blue-100 bg-white" 
         v-if="tenants.length > 0">
        <div class="d-flex align-items-center flex-wrap gap-2.5">
            <div class="bg-blue-50 text-blue-600 p-2 rounded-xl d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                <i class="bi bi-buildings fs-6"></i>
            </div>
            <div>
                <span class="fs-8 fw-bold text-slate-800 me-1">Pilih Instansi Sekolah:</span>
            </div>
            
            <div class="my-1 my-md-0" style="min-width: 220px; max-width: 300px;">
                <select id="sa-filter-sekolah-errors" 
                        class="form-select form-select-sm bg-slate-50 border-slate-200 rounded-xl text-slate-800 fs-8 font-semibold shadow-2xs cursor-pointer focus:bg-white w-100" 
                        style="height: 38px;" 
                        v-model="tenantIdFilter" 
                        @change="loadErrors(1)">
                    <option value="">-- Semua Sekolah / Tenant --</option>
                    <option value="global">🌐 Sistem (Super Admin / Global)</option>
                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
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

    <!-- 3. KPI Summary Metric Cards (Statistik Error) -->
    <div class="row g-3 mb-4" v-if="stats.length > 0">
        <div v-for="stat in stats" :key="stat.error_level" class="col-6 col-md-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="kpi-label text-truncate d-block" style="max-width: 140px;" :title="stat.error_level">{{ stat.error_level }}</span>
                        <h4 class="kpi-value" :class="levelTextColor(stat.error_level)">{{ stat.jumlah }}</h4>
                    </div>
                    <div class="kpi-icon-box" :class="levelIconBg(stat.error_level)">
                        <i :class="levelIcon(stat.error_level)"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Sukses Jika 0 Error -->
    <div class="row g-3 mb-4" v-else-if="!loading">
        <div class="col-12">
            <div class="alert border-0 rounded-2xl d-flex align-items-center gap-3 mb-0 shadow-2xs py-3.5 px-4 bg-emerald-50 text-emerald-800 border-start border-emerald-500 border-4">
                <div class="rounded-xl d-flex align-items-center justify-content-center flex-shrink-0 bg-emerald-600 text-white" style="width: 40px; height: 40px;">
                    <i class="bi bi-shield-check fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-emerald-900 fs-7 mb-0.5">Semua Sistem Berjalan Normal!</h6>
                    <p class="fs-8 text-emerald-700 mb-0">Tidak ada log error PHP/SQL yang tercatat. Seluruh modul platform berjalan dengan lancar.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Main Card Grid & Table Section -->
    <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 mb-4 animate-fade-in">
        
        <!-- Filter Lanjutan & Toolbar -->
        <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-3.5 p-md-4 mb-4 shadow-2xs">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-slate-200/60">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-funnel-fill text-rose-600 fs-7"></i>
                    <span class="fs-8 fw-bold text-slate-800 text-uppercase tracking-wider">Penyaringan & Filter Log Error</span>
                </div>
                <button v-if="searchQuery || levelFilter" 
                        type="button" 
                        @click="searchQuery = ''; levelFilter = ''; loadErrors(1);" 
                        class="btn btn-sm btn-link text-slate-500 hover:text-rose-600 p-0 fs-8 text-decoration-none d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                </button>
            </div>

            <div class="row g-3 align-items-end">
                <!-- Filter 1: Level Error -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Level / Tingkat Error</label>
                    <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer font-monospace" 
                            v-model="levelFilter" 
                            @change="loadErrors(1)"
                            style="height: 38px;">
                        <option value="">-- Semua Level Error --</option>
                        <option v-for="s in stats" :key="s.error_level" :value="s.error_level">
                            {{ s.error_level }} ({{ s.jumlah }})
                        </option>
                    </select>
                </div>

                <!-- Filter 2: Pencarian Pesan / File -->
                <div class="col-12 col-sm-6 col-lg-8">
                    <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Cari Pesan, File Sumber, atau URL</label>
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute top-50 translate-middle-y text-slate-400 ms-3" style="font-size: 0.85rem;"></i>
                        <input id="errSearchQuery" name="search" type="text" 
                               class="form-control form-control-sm ps-5 pe-5 bg-white border-slate-200 rounded-xl text-slate-800 fs-8 font-medium shadow-2xs" 
                               placeholder="Cari pesan error, file sumber, endpoint..." 
                               v-model="searchQuery" 
                               @input="onSearch"
                               style="height: 38px;">
                        <button v-if="searchQuery" type="button" class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y text-slate-400 hover:text-slate-600 text-decoration-none p-0 me-3" @click="searchQuery = ''; loadErrors(1)">
                            <i class="bi bi-x-circle-fill fs-7"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Modern Table Architecture -->
        <div class="table-responsive" style="margin-bottom: 1.25rem;">
            <table class="pengguna-table table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 150px;">WAKTU & TANGGAL</th>
                        <th style="width: 140px;">LEVEL ERROR</th>
                        <th>PESAN & SUMBER TENANT</th>
                        <th style="width: 250px;">FILE SUMBER : BARIS</th>
                        <th style="width: 180px;">ENDPOINT / REQUEST</th>
                        <th class="text-center" style="width: 110px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading State -->
                    <tr v-if="loading">
                        <td colspan="6" class="text-center py-5 text-slate-400">
                            <div class="spinner-border spinner-border-sm text-rose-600 me-2" role="status"></div>
                            <span class="font-semibold fs-8">Memuat rekam jejak error monitor...</span>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-else-if="errors.length === 0">
                        <td colspan="6" class="text-center py-5">
                            <div class="py-4">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 d-inline-flex align-items-center justify-content-center fs-3 mb-2 shadow-2xs" style="width: 48px; height: 48px;">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h6 class="fw-bold text-slate-800 fs-7 mb-1">Tidak Ada Log Error</h6>
                                <p class="text-slate-400 fs-8 mb-0">Tidak ada rekaman error yang cocok dengan kriteria filter.</p>
                            </div>
                        </td>
                    </tr>

                    <!-- Data Rows -->
                    <tr v-else v-for="err in errors" :key="err.id">
                        
                        <!-- 1. Waktu & Tanggal -->
                        <td class="text-nowrap">
                            <div class="fw-bold text-slate-800 font-monospace fs-8">
                                {{ formatTimeOnly(err.created_at) }}
                            </div>
                            <div class="fs-9 text-slate-400 font-medium">
                                {{ formatDateOnly(err.created_at) }}
                            </div>
                        </td>

                        <!-- 2. Level Badge -->
                        <td>
                            <span class="badge-level shadow-2xs fs-9"
                                  :class="levelBadgeClasses(err.error_level)"
                                  :title="err.error_level || 'ERROR'">
                                <i :class="levelDotIcon(err.error_level)"></i>
                                <span class="text-truncate" style="max-width: 110px;">{{ formatLevelLabel(err.error_level) }}</span>
                            </span>
                        </td>

                        <!-- 3. Pesan & Sumber Tenant -->
                        <td>
                            <div class="error-msg-box" :title="err.message">
                                {{ err.message }}
                            </div>
                            <div class="mt-1.5 d-flex align-items-center gap-1.5 flex-wrap">
                                <span v-if="err.nama_sekolah" class="tenant-chip" :title="err.nama_sekolah">
                                    <i class="bi bi-building text-primary"></i>
                                    <span class="text-truncate" style="max-width: 250px;">{{ err.nama_sekolah }}</span>
                                </span>
                                <span v-else class="tenant-chip text-slate-500">
                                    <i class="bi bi-globe text-secondary"></i>
                                    <span>Pusat Kendali SaaS (Global)</span>
                                </span>
                            </div>
                        </td>

                        <!-- 4. File Sumber & Baris -->
                        <td class="pe-3">
                            <div class="source-box shadow-2xs">
                                <div class="d-flex align-items-center justify-content-between gap-1 mb-1 border-bottom pb-1 border-slate-200/60">
                                    <span class="fs-9 fw-bold text-slate-500 font-monospace">SOURCE</span>
                                    <span class="line-badge font-monospace">
                                        L.{{ err.line || '?' }}
                                    </span>
                                </div>
                                <div class="font-monospace text-primary fs-8 text-truncate fw-semibold" :title="err.file">
                                    {{ shortenPath(err.file) }}
                                </div>
                            </div>
                        </td>

                        <!-- 5. Request URL & IP Address -->
                        <td>
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="method-badge flex-shrink-0" :class="methodBadgeClass(err.request_method)">
                                    {{ (err.request_method || 'GET').toUpperCase() }}
                                </span>
                                <span class="text-slate-800 font-monospace fs-8 text-truncate fw-semibold" style="max-width: 120px;" :title="err.request_url">
                                    {{ shortenUrl(err.request_url) }}
                                </span>
                            </div>
                            <div class="text-slate-400 font-monospace fs-9 d-flex align-items-center gap-1">
                                <i class="bi bi-hdd-network text-slate-400"></i>
                                <span>{{ err.ip_address || '127.0.0.1' }}</span>
                            </div>
                        </td>

                        <!-- 6. Aksi -->
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center align-items-center">
                                <button type="button" 
                                        class="btn btn-sm btn-primary rounded-xl px-2.5 py-1 fs-8 font-semibold d-inline-flex align-items-center gap-1 shadow-2xs hover-lift"
                                        @click="openTraceModal(err)"
                                        title="Lihat Detail Stack Trace">
                                    <i class="bi bi-bug-fill"></i> Trace
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-light border border-slate-200 text-rose-600 rounded-xl d-inline-flex align-items-center justify-content-center shadow-2xs hover-lift"
                                        style="width: 32px; height: 32px;"
                                        @click="deleteOne(err.id)"
                                        title="Hapus log ini">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>

                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 6. Bottom Pagination Toolbar Standard -->
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top border-slate-100 mt-2 pt-4">
            <div class="d-flex align-items-center gap-3">
                <span class="fs-8 text-slate-500">
                    Menampilkan <strong>{{ totalErrors === 0 ? 0 : (currentPage - 1) * perPage + 1 }}</strong> s.d. <strong>{{ Math.min(currentPage * perPage, totalErrors) }}</strong> dari <strong>{{ totalErrors }}</strong> log
                </span>
                <div class="d-flex align-items-center gap-1.5 ms-2">
                    <span class="fs-9 text-slate-400 text-uppercase font-bold">Baris:</span>
                    <select id="per_page_select_errors" name="per_page" 
                            class="form-select form-select-sm perpage-select shadow-2xs cursor-pointer" 
                            v-model="perPage" 
                            @change="loadErrors(1)" 
                            title="Jumlah baris per halaman">
                        <option :value="10">10</option>
                        <option :value="20">20</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
            </div>

            <nav v-if="totalPages > 1">
                <ul class="pagination pagination-modern m-0">
                    <li class="page-item" :class="{disabled: currentPage === 1 || loading}">
                        <a class="page-link" href="#" @click.prevent="loadErrors(currentPage - 1)" title="Halaman Sebelumnya">&laquo;</a>
                    </li>
                    <li class="page-item" v-for="(p, pIdx) in visiblePages" :key="pIdx" 
                        :class="{active: p === currentPage, disabled: p === '...' || loading}">
                        <a class="page-link" href="#" @click.prevent="p !== '...' ? loadErrors(p) : null">{{ p }}</a>
                    </li>
                    <li class="page-item" :class="{disabled: currentPage === totalPages || loading}">
                        <a class="page-link" href="#" @click.prevent="loadErrors(currentPage + 1)" title="Halaman Selanjutnya">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>

    </div>

</div>

<!-- 7. Modal Detail Stack Trace -->
<div class="modal fade" id="modalErrorTrace" tabindex="-1" aria-labelledby="modalErrorTraceLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header px-4 py-3 bg-slate-900 text-white d-flex align-items-center justify-content-between border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/20 text-rose-400 d-flex align-items-center justify-content-center fs-5 shadow-xs">
                        <i class="bi bi-bug-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-bold text-white text-base mb-0" id="modalErrorTraceLabel">
                            Detail Stack Trace & System Telemetry
                        </h5>
                        <span class="text-slate-400 fs-9 font-monospace" id="modal-trace-subtitle">
                            Memuat...
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 text-slate-700 text-xs" id="modal-trace-body">
                <div class="text-center py-4 text-slate-400 fs-8">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    Memuat detail trace...
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer px-4 py-3 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                <button type="button" class="btn btn-sm btn-light rounded-xl font-semibold px-4" data-bs-dismiss="modal">
                    Tutup
                </button>
                <button type="button" class="btn btn-sm btn-danger rounded-xl font-bold px-4 d-flex align-items-center gap-1.5" id="btn-modal-delete-error">
                    <i class="bi bi-trash3-fill"></i> Hapus Log Ini
                </button>
            </div>
        </div>
    </div>
</div>

<!-- VUE 3 REGISTRATION SCRIPT -->
<script>
{
    window.VueAppRegistry.register('#errorMonitorApp', {
        data() {
            return {
                errors:              [],
                stats:               [],
                tenants:             <?= json_encode($tenants, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                tenantIdFilter:      '',
                loading:             false,
                loadingClear:        false,
                searchQuery:         '',
                levelFilter:         '',
                perPage:             20,
                currentPage:         1,
                totalErrors:         0,
                totalPages:          1,
                searchTimer:         null,
                autoRefreshSeconds:  0,
                autoRefreshTimer:    null,
                lastUpdatedTime:     '',
                _traceModal:         null,
                _currentErrId:       null,
            };
        },

        computed: {
            visiblePages() {
                const pages = [];
                const total = this.totalPages;
                const current = this.currentPage;
                if (total <= 7) {
                    for (let i = 1; i <= total; i++) pages.push(i);
                } else {
                    if (current <= 4) {
                        pages.push(1, 2, 3, 4, 5, '...', total);
                    } else if (current >= total - 3) {
                        pages.push(1, '...', total - 4, total - 3, total - 2, total - 1, total);
                    } else {
                        pages.push(1, '...', current - 1, current, current + 1, '...', total);
                    }
                }
                return pages;
            }
        },

        mounted() {
            const modalEl = document.getElementById('modalErrorTrace');
            if (modalEl) {
                this._traceModal = new bootstrap.Modal(modalEl);
            }

            const btnDelete = document.getElementById('btn-modal-delete-error');
            if (btnDelete) {
                btnDelete.onclick = () => {
                    if (this._currentErrId) {
                        this.deleteOne(this._currentErrId);
                        if (this._traceModal) this._traceModal.hide();
                    }
                };
            }

            this.loadErrors(1);
        },

        beforeUnmount() {
            if (this.autoRefreshTimer) {
                clearInterval(this.autoRefreshTimer);
                this.autoRefreshTimer = null;
            }
            if (this.searchTimer) {
                clearTimeout(this.searchTimer);
            }
        },

        methods: {
            getSelectedTenantLabel() {
                if (!this.tenantIdFilter) return 'Semua Sekolah / Tenant';
                if (this.tenantIdFilter === 'global') return 'Sistem (Super Admin / Global)';
                const found = (this.tenants || []).find(t => String(t.id) === String(this.tenantIdFilter));
                return found ? found.nama_sekolah : 'Sekolah Terpilih';
            },

            async loadErrors(page = 1, isSilent = false) {
                if (!isSilent) {
                    this.loading = true;
                }
                this.currentPage = page;
                try {
                    const params = new URLSearchParams({
                        page:         page,
                        per_page:     this.perPage,
                        search:       this.searchQuery,
                        level_filter: this.levelFilter,
                        tenant_id:    this.tenantIdFilter,
                    });
                    const res  = await axios.get(`<?= $this->getBaseUrl() ?>/api/v1/error-monitor?${params}`);
                    const data = res.data;

                    this.errors          = data.data              || [];
                    this.stats           = data.stats             || [];
                    this.totalErrors     = (data && data.pagination && data.pagination.total) || 0;
                    this.totalPages      = (data && data.pagination && data.pagination.pages) || 1;
                    
                    const now = new Date();
                    this.lastUpdatedTime = now.toLocaleTimeString('id-ID');
                } catch {
                    if (!isSilent) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat',
                            text: 'Tidak dapat memuat data error log. Periksa koneksi server.',
                            confirmButtonColor: '#2563eb'
                        });
                    }
                } finally {
                    this.loading = false;
                }
            },

            onSearch() {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => this.loadErrors(1), 300);
            },

            onAutoRefreshChange() {
                if (this.autoRefreshTimer) {
                    clearInterval(this.autoRefreshTimer);
                    this.autoRefreshTimer = null;
                }
                const sec = parseInt(this.autoRefreshSeconds) || 0;
                if (sec > 0) {
                    this.autoRefreshTimer = setInterval(() => {
                        this.loadErrors(this.currentPage, true);
                    }, sec * 1000);
                }
            },

            openTraceModal(err) {
                this._currentErrId = err.id;

                const subtitleEl = document.getElementById('modal-trace-subtitle');
                if (subtitleEl) {
                    subtitleEl.textContent = `${err.error_level} — ${this.shortenPath(err.file)} (Baris ${err.line || '?'})`;
                }

                const esc = (s) => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

                let traceHtml = '';
                try {
                    const frames = JSON.parse(err.trace || '[]');
                    if (Array.isArray(frames) && frames.length > 0) {
                        const rows = frames.map((f, i) => `
                            <tr>
                                <td class="text-slate-400 ps-3">${i}</td>
                                <td>
                                    ${f.class ? `<span class="text-info">${esc(f.class)}</span>` : ''}
                                    ${f.type  ? `<span class="text-slate-400">${esc(f.type)}</span>`  : ''}
                                    <span class="text-amber-400 fw-semibold">${esc(f.function)}</span>
                                    <span class="text-slate-400">()</span>
                                </td>
                                <td class="text-slate-300 text-truncate" style="max-width:220px;" title="${esc(f.file)}">
                                    ${esc(this.shortenPath(f.file))}
                                </td>
                                <td class="text-center pe-3">
                                    ${f.line
                                        ? `<span class="badge" style="background:#fefce8;color:#854d0e;border:1px solid #fde68a;">${f.line}</span>`
                                        : '<span class="text-slate-400">-</span>'}
                                </td>
                            </tr>`).join('');

                        traceHtml = `
                            <div class="border border-slate-700 rounded-2xl overflow-hidden shadow-2xs">
                                <table class="table table-sm table-dark align-middle mb-0 font-monospace" style="font-size:0.72rem;">
                                    <thead>
                                        <tr class="bg-slate-800 text-slate-300 border-bottom border-slate-700">
                                            <th class="ps-3" style="width:36px;">#</th>
                                            <th>Fungsi / Metode</th>
                                            <th style="width:230px;">File</th>
                                            <th class="text-center pe-3" style="width:60px;">Baris</th>
                                        </tr>
                                    </thead>
                                    <tbody>${rows}</tbody>
                                </table>
                            </div>`;
                    } else {
                        traceHtml = `<pre class="bg-slate-950 text-emerald-400 p-3 rounded-2xl mb-0 font-monospace" style="font-size:0.72rem;max-height:280px;overflow-y:auto;">${esc(err.trace)}</pre>`;
                    }
                } catch {
                    traceHtml = `<pre class="bg-slate-950 text-emerald-400 p-3 rounded-2xl mb-0 font-monospace" style="font-size:0.72rem;max-height:280px;overflow-y:auto;">${esc(err.trace)}</pre>`;
                }

                let contextHtml = '';
                if (err.context) {
                    try {
                        const ctx = typeof err.context === 'string' ? JSON.parse(err.context) : err.context;
                        const ctxString = JSON.stringify(ctx, null, 2);
                        contextHtml = `
                        <div class="mb-3">
                            <div class="fs-8 fw-bold text-slate-600 mb-2 text-uppercase d-flex align-items-center gap-1.5">
                                <i class="bi bi-info-square-fill text-blue-600"></i> Context & Telemetry Payload
                            </div>
                            <pre class="bg-slate-950 text-blue-300 p-3 rounded-2xl mb-0 font-monospace" style="font-size:0.72rem;max-height:220px;overflow-y:auto;">${esc(ctxString)}</pre>
                        </div>
                        `;
                    } catch (e) {
                        contextHtml = ``;
                    }
                }

                const modalBodyEl = document.getElementById('modal-trace-body');
                if (modalBodyEl) {
                    const htmlContent = `
                        <div class="row g-2.5 mb-3">
                            <div class="col-md-4">
                                <div class="rounded-2xl p-3 bg-slate-50 border border-slate-200">
                                    <div class="fs-9 text-slate-500 font-semibold text-uppercase mb-1">Level Error</div>
                                    <span class="badge fw-bold font-monospace px-2.5 py-1 rounded-pill ${this.levelBadgeClass(err.error_level)}">${esc(err.error_level)}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="rounded-2xl p-3 bg-slate-50 border border-slate-200">
                                    <div class="fs-9 text-slate-500 font-semibold text-uppercase mb-1">Waktu Kejadian</div>
                                    <div class="fs-8 font-monospace text-slate-800 font-bold">${esc(this.formatDateTime(err.created_at))}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="rounded-2xl p-3 bg-slate-50 border border-slate-200">
                                    <div class="fs-9 text-slate-500 font-semibold text-uppercase mb-1">IP & Lingkup</div>
                                    <span class="fs-8 font-monospace text-slate-700">${esc(err.nama_sekolah || 'Global / Sistem')}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl p-3 mb-3 bg-slate-50 border border-slate-200">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="fs-9 text-slate-500 font-semibold text-uppercase mb-1">Request Endpoint</div>
                                    <div class="fs-8 font-monospace text-truncate text-blue-600 font-bold" title="${esc(err.request_url || '-')}">
                                        <span class="badge bg-blue-600 text-white me-1">${esc(err.request_method || 'GET')}</span>
                                        ${esc(err.request_url || '-')}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="fs-9 text-slate-500 font-semibold text-uppercase mb-1">File Sumber</div>
                                    <div class="fs-8 font-monospace text-truncate text-slate-800 font-bold" title="${esc(err.file)}:${esc(String(err.line || ''))}">
                                        ${esc(err.file)}
                                        <span class="text-rose-600">:${esc(String(err.line || ''))}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="fs-8 fw-bold text-slate-600 text-uppercase d-flex align-items-center gap-1.5">
                                    <i class="bi bi-exclamation-octagon-fill text-rose-600"></i> Pesan Error Lengkap
                                </div>
                                <button type="button" 
                                        class="btn btn-xs btn-light border border-slate-200 text-slate-600 rounded-lg px-2 py-1 fs-9 font-semibold hover-lift"
                                        id="btn-copy-error-msg">
                                    <i class="bi bi-clipboard me-1"></i> Salin Pesan
                                </button>
                            </div>
                            <div class="rounded-2xl p-3 bg-rose-50/60 border border-rose-200">
                                <code class="text-rose-700" style="font-size:0.8rem;white-space:pre-wrap;word-break:break-word;">${esc(err.message)}</code>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="fs-8 fw-bold text-slate-600 text-uppercase d-flex align-items-center gap-1.5">
                                    <i class="bi bi-list-ol text-blue-600"></i> Stack Trace Frame
                                </div>
                                <button type="button" 
                                        class="btn btn-xs btn-light border border-slate-200 text-slate-600 rounded-lg px-2 py-1 fs-9 font-semibold hover-lift"
                                        id="btn-copy-trace-raw">
                                    <i class="bi bi-code-square me-1"></i> Salin Raw Trace
                                </button>
                            </div>
                            ${traceHtml}
                        </div>

                        ${contextHtml}
                    `;
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(htmlContent, 'text/html');
                    modalBodyEl.replaceChildren(...doc.body.childNodes);

                    // Hook up copy buttons
                    const copyMsgBtn = document.getElementById('btn-copy-error-msg');
                    if (copyMsgBtn) {
                        copyMsgBtn.onclick = () => {
                            navigator.clipboard.writeText(err.message || '');
                            Swal.fire({
                                icon: 'success',
                                title: 'Pesan Error Disalin',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: { popup: 'rounded-2xl' }
                            });
                        };
                    }

                    const copyTraceBtn = document.getElementById('btn-copy-trace-raw');
                    if (copyTraceBtn) {
                        copyTraceBtn.onclick = () => {
                            navigator.clipboard.writeText(err.trace || '');
                            Swal.fire({
                                icon: 'success',
                                title: 'Raw Stack Trace Disalin',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: { popup: 'rounded-2xl' }
                            });
                        };
                    }
                }

                if (this._traceModal) {
                    this._traceModal.show();
                }
            },

            async confirmClearAll() {
                const result = await Swal.fire({
                    title: '<span class="text-rose-600 fw-bold">Hapus Semua Log Error?</span>',
                    html: `<p class="text-slate-500 fs-8">Tindakan ini akan menghapus seluruh <strong class="text-rose-600">${this.totalErrors}</strong> entri error secara permanen.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus Semua',
                    cancelButtonText: 'Batal',
                    customClass: { popup: 'rounded-3xl' }
                });
                if (!result.isConfirmed) return;

                this.loadingClear = true;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/error-monitor/clear');
                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Dihapus',
                            text: 'Semua log error telah dibersihkan.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl' }
                        });
                        this.loadErrors(1);
                    } else {
                        throw new Error(res.data.message || 'Gagal menghapus log error');
                    }
                } catch (err) {
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Gagal', 
                        text: err.response?.data?.error || err.message || 'Tidak dapat menghapus log error.', 
                        confirmButtonColor: '#2563eb',
                        customClass: { popup: 'rounded-3xl' }
                    });
                } finally {
                    this.loadingClear = false;
                }
            },

            async deleteOne(id) {
                const result = await Swal.fire({
                    title: '<span class="text-rose-600 fw-bold">Hapus Log Ini?</span>',
                    html: '<p class="text-slate-500 fs-8">Log error ini akan dihapus secara permanen dari basis data sistem.</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    customClass: { popup: 'rounded-3xl' }
                });
                if (!result.isConfirmed) return;

                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/error-monitor/delete', { id });
                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Log Berhasil Dihapus',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            customClass: { popup: 'rounded-3xl' }
                        });
                        this.loadErrors(this.currentPage, true);
                    } else {
                        throw new Error(res.data.message || 'Gagal menghapus log');
                    }
                } catch (err) {
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Gagal', 
                        text: err.response?.data?.error || err.message || 'Tidak dapat menghapus log ini.', 
                        confirmButtonColor: '#2563eb',
                        customClass: { popup: 'rounded-3xl' }
                    });
                }
            },

            formatDateTime(raw) {
                if (!raw) return '-';
                const d = new Date(raw.replace(/-/g, '/'));
                if (isNaN(d)) return raw;
                return d.toLocaleString('id-ID', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
            },

            formatTimeOnly(raw) {
                if (!raw) return '--:--:--';
                const d = new Date(raw.replace(/-/g, '/'));
                if (isNaN(d)) return raw;
                return d.toLocaleTimeString('id-ID', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
            },

            formatDateOnly(raw) {
                if (!raw) return '-';
                const d = new Date(raw.replace(/-/g, '/'));
                if (isNaN(d)) return raw;
                return d.toLocaleDateString('id-ID', {
                    day: '2-digit', month: 'short', year: 'numeric'
                });
            },

            shortenPath(path) {
                if (!path) return '-';
                const clean = (path || '').replace(/\\/g, '/');
                const parts = clean.split('/');
                if (parts.length <= 3) return clean;
                return '…/' + parts.slice(-2).join('/');
            },

            shortenUrl(url) {
                if (!url) return '-';
                try { return new URL(url).pathname; } catch { return (url + '').substring(0, 30); }
            },

            formatLevelLabel(level) {
                if (!level || level === '' || level === '.') return 'ERROR';
                const s = String(level).trim();
                if (s.startsWith('[') || s.length > 18) {
                    if (s.toLowerCase().includes('controller') || s.toLowerCase().includes('model')) {
                        return 'APP_ERROR';
                    }
                    return s.substring(0, 15) + '…';
                }
                return s;
            },

            levelBadgeClasses(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('parseerror') || l.includes('fatal') || l.includes('exception') || l.includes('typeerror') || l.includes('argumentcount') || l === 'e_error' || l === 'error') {
                    return 'badge-level-fatal';
                }
                if (l.includes('warning') || l.includes('e_warning') || l.includes('e_user_warning')) {
                    return 'badge-level-warning';
                }
                if (l.includes('js') || l.includes('promise') || l.includes('javascript') || l.includes('runtime')) {
                    return 'badge-level-js';
                }
                if (l.includes('api') || l.includes('http') || l.includes('500') || l.includes('server')) {
                    return 'badge-level-api';
                }
                if (l.includes('notice') || l.includes('e_notice') || l.includes('info')) {
                    return 'badge-level-notice';
                }
                if (l.includes('deprecat')) {
                    return 'badge-level-deprecated';
                }
                return 'badge-level-default';
            },

            levelDotIcon(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('parseerror') || l.includes('fatal') || l.includes('exception') || l.includes('typeerror') || l.includes('argumentcount') || l === 'e_error' || l === 'error') {
                    return 'bi bi-x-circle-fill text-danger';
                }
                if (l.includes('warning') || l.includes('e_warning')) {
                    return 'bi bi-exclamation-triangle-fill text-warning';
                }
                if (l.includes('js') || l.includes('promise') || l.includes('javascript') || l.includes('runtime')) {
                    return 'bi bi-lightning-charge-fill text-purple-600';
                }
                if (l.includes('api') || l.includes('http') || l.includes('500')) {
                    return 'bi bi-hdd-network-fill text-orange-600';
                }
                if (l.includes('notice') || l.includes('e_notice')) {
                    return 'bi bi-info-circle-fill text-info';
                }
                if (l.includes('deprecat')) {
                    return 'bi bi-clock-history text-slate-500';
                }
                return 'bi bi-bug-fill text-slate-700';
            },

            methodBadgeClass(method) {
                const m = (method || 'GET').toUpperCase();
                if (m === 'POST') return 'method-post';
                if (m === 'PUT' || m === 'PATCH') return 'method-put';
                if (m === 'DELETE') return 'method-delete';
                if (m === 'CLIENT') return 'method-client';
                return 'method-get';
            },

            levelBadgeClass(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('fatal') || l.includes('exception') || l === 'e_error') return 'bg-rose-600 text-white';
                if (l.includes('warning') || l === 'e_warning') return 'bg-amber-500 text-slate-900';
                if (l.includes('notice')  || l === 'e_notice')  return 'bg-sky-500 text-white';
                if (l.includes('deprecat'))                     return 'bg-slate-600 text-white';
                return 'bg-slate-800 text-white';
            },

            levelTextColor(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('fatal') || l.includes('exception') || l === 'e_error') return 'text-rose-600';
                if (l.includes('warning') || l === 'e_warning') return 'text-amber-600';
                if (l.includes('notice')  || l === 'e_notice')  return 'text-sky-600';
                return 'text-slate-800';
            },

            levelIconBg(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('fatal') || l.includes('exception') || l === 'e_error') return 'bg-rose-50 text-rose-600';
                if (l.includes('warning')) return 'bg-amber-50 text-amber-600';
                if (l.includes('notice'))  return 'bg-sky-50 text-sky-600';
                return 'bg-slate-100 text-slate-600';
            },

            levelIcon(level) {
                const l = (level || '').toLowerCase();
                if (l.includes('fatal') || l.includes('exception') || l === 'e_error') return 'bi bi-x-octagon-fill';
                if (l.includes('warning')) return 'bi bi-exclamation-triangle-fill';
                if (l.includes('notice'))  return 'bi bi-info-circle-fill';
                return 'bi bi-bug-fill';
            },
        }
    });

    if (window.VueAppRegistry && typeof window.VueAppRegistry.mountAll === 'function') {
        window.VueAppRegistry.mountAll();
    }
}
</script>
