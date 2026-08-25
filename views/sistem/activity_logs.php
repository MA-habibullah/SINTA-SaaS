<?php
/**
 * View: Log Aktivitas Sistem (Audit Trail & Tracking Perubahan Data)
 * SINTA SaaS - Standardized Vue 3 Dynamic Single Page Experience
 */
$pageTitle = $title ?? 'Audit Trail & Log Aktivitas Sistem';
$userRole = $_SESSION['role_name'] ?? $_SESSION['user']['role'] ?? '';
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
    .shadow-2xs { box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); }
    .shadow-xs { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); }
    .hover-lift { transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .hover-lift:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.06); }
</style>

<div id="activityLogsApp" v-cloak class="p-2 p-md-3">

    <!-- 1. Row Header & Action Toolbar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-blue-600 text-white rounded-2xl d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 48px; height: 48px;">
                <i class="bi bi-journal-text fs-4"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold text-slate-900 fs-4 mb-0">Audit Trail & Log Aktivitas</h3>
                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-1 fs-9 font-bold" v-if="isSuperAdmin">
                        <i class="bi bi-shield-check text-blue-600 me-1"></i>Super Admin
                    </span>
                </div>
                <p class="text-slate-500 fs-8 mb-0 mt-0.5">Rekam jejak transaksi data, audit perubahan nilai (INSERT, UPDATE, DELETE), login, dan pelacakan aktor secara real-time.</p>
            </div>
        </div>
        
        <!-- Action Toolbar -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" 
                    class="btn btn-sm btn-light border border-rose-200 text-rose-700 bg-rose-50/60 rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                    @click="openDeleteModal()">
                <i class="bi bi-trash3-fill text-rose-600"></i>
                <span>Hapus Log</span>
            </button>
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                    @click="refreshAll()" 
                    title="Segarkan Data">
                <i class="bi bi-arrow-clockwise" :class="{'spin': loading}"></i>
                <span>Segarkan</span>
            </button>
        </div>
    </div>

    <!-- 2. Compact School Selector Banner (Khusus Super Admin Auto-Filter) -->
    <div class="mb-4 p-3 px-md-4 rounded-2xl shadow-2xs border border-blue-100 bg-white" 
         v-if="isSuperAdmin && tenantOptions.length > 0">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center flex-wrap gap-2.5">
                <div class="bg-blue-50 text-blue-600 p-2 rounded-xl d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-building fs-6"></i>
                </div>
                <div>
                    <span class="fs-8 fw-bold text-slate-800 me-2">Pilih Instansi Sekolah:</span>
                </div>
                
                <div class="my-1 my-md-0" style="min-width: 220px; max-width: 300px;">
                    <select id="sa-filter-sekolah-logs" name="filter_tenant_id" 
                            class="form-select form-select-sm bg-slate-50 border-slate-200 rounded-xl text-slate-800 fs-8 font-semibold shadow-2xs cursor-pointer focus:bg-white w-100" 
                            style="height: 38px;" 
                            v-model="selectedTenant" 
                            @change="onFilterChange()">
                        <option value="">-- Semua Sekolah / Tenant --</option>
                        <option value="system">🌐 Sistem (Super Admin / Global)</option>
                        <option v-for="t in tenantOptions" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                    </select>
                </div>
            </div>

            <div class="text-slate-500 fs-8 d-flex align-items-center gap-1.5">
                <i class="bi bi-info-circle text-blue-500"></i>
                <span>Data Aktif: <strong class="text-blue-600 fw-bold">{{ getSelectedTenantLabel() }}</strong></span>
            </div>
        </div>
    </div>

    <!-- 3. KPI Summary Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Log Sistem -->
        <div class="col-6 col-md-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="kpi-label">TOTAL LOG SISTEM</span>
                        <h4 class="kpi-value text-blue-600">{{ stats.total_logs || 0 }}</h4>
                    </div>
                    <div class="kpi-icon-box bg-blue-50 text-blue-600">
                        <i class="bi bi-database-fill-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 2: Perubahan UPDATE -->
        <div class="col-6 col-md-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="kpi-label">PERUBAHAN (UPDATE)</span>
                        <h4 class="kpi-value text-amber-600">{{ stats.total_update || 0 }}</h4>
                    </div>
                    <div class="kpi-icon-box bg-amber-50 text-amber-600">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 3: Data Baru INSERT -->
        <div class="col-6 col-md-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="kpi-label">DATA BARU (INSERT)</span>
                        <h4 class="kpi-value text-emerald-600">{{ stats.total_insert || 0 }}</h4>
                    </div>
                    <div class="kpi-icon-box bg-emerald-50 text-emerald-600">
                        <i class="bi bi-plus-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 4: Hapus Data DELETE -->
        <div class="col-6 col-md-3">
            <div class="kpi-card shadow-2xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="kpi-label">HAPUS DATA (DELETE)</span>
                        <h4 class="kpi-value text-rose-600">{{ stats.total_delete || 0 }}</h4>
                    </div>
                    <div class="kpi-icon-box bg-rose-50 text-rose-600">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Main Card Grid & Table Section -->
    <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 mb-4 animate-fade-in">
        
        <!-- Filter Lanjutan Card (Spacious & Clean Layout) -->
        <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-3.5 p-md-4 mb-4 shadow-2xs">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-slate-200/60">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-funnel-fill text-blue-600 fs-7"></i>
                    <span class="fs-8 fw-bold text-slate-800 text-uppercase tracking-wider">Penyaringan & Filter Data Log</span>
                </div>
                <button v-if="hasActiveFilters" 
                        type="button" 
                        @click="resetFilters" 
                        class="btn btn-sm btn-link text-slate-500 hover:text-rose-600 p-0 fs-8 text-decoration-none d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Semua Filter
                </button>
            </div>

            <div class="row g-3 align-items-end">
                <!-- Filter 1: Aksi -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Jenis Aksi</label>
                    <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                            v-model="selectedAction" 
                            @change="onFilterChange"
                            style="height: 38px;">
                        <option value="">-- Semua Aksi --</option>
                        <option value="INSERT">➕ INSERT (Tambah)</option>
                        <option value="UPDATE">🔄 UPDATE (Ubah)</option>
                        <option value="DELETE">🗑️ DELETE (Hapus)</option>
                        <option value="LOGIN">🔑 LOGIN</option>
                        <option value="LOGOUT">🚪 LOGOUT</option>
                        <option v-for="act in extraActions" :key="act" :value="act">{{ act }}</option>
                    </select>
                </div>

                <!-- Filter 2: Role Pengguna -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Peran / Role</label>
                    <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                            v-model="selectedRole" 
                            @change="onFilterChange"
                            style="height: 38px;">
                        <option value="">-- Semua Role --</option>
                        <option v-for="r in roleOptions" :key="r" :value="r">
                            {{ formatRoleLabel(r) }}
                        </option>
                    </select>
                </div>

                <!-- Filter 3: Modul / Tabel -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Modul / Tabel</label>
                    <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                            v-model="selectedTable" 
                            @change="onFilterChange"
                            style="height: 38px;">
                        <option value="">-- Semua Modul / Tabel --</option>
                        <option v-for="tbl in tableOptions" :key="tbl" :value="tbl">
                            {{ formatTableLabel(tbl) }}
                        </option>
                    </select>
                </div>

                <!-- Filter 4: Rentang Tanggal -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Rentang Tanggal</label>
                    <div class="d-flex align-items-center gap-1.5 bg-white p-1 rounded-xl border border-slate-200 shadow-2xs" style="height: 38px;">
                        <input type="date" v-model="startDate" @change="onFilterChange" class="form-control form-control-sm border-0 bg-transparent text-xs p-1 font-semibold text-slate-700" style="width: 110px;" title="Dari Tanggal">
                        <span class="text-slate-400 text-xs">-</span>
                        <input type="date" v-model="endDate" @change="onFilterChange" class="form-control form-control-sm border-0 bg-transparent text-xs p-1 font-semibold text-slate-700" style="width: 110px;" title="Sampai Tanggal">
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Action Toolbar (Search Box on Left, Quick Info on Right) -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="position-relative flex-grow-1" style="max-width: 360px;">
                <i class="bi bi-search position-absolute top-50 translate-middle-y text-slate-400 ms-3" style="font-size: 0.85rem;"></i>
                <input id="global_search_input" name="search" type="text" 
                       class="form-control form-control-sm ps-5 pe-5 bg-white border-slate-200 rounded-xl text-slate-800 fs-8 font-medium shadow-2xs" 
                       placeholder="Cari aksi, tabel, user, IP, data..." 
                       v-model="searchQuery" 
                       @input="onSearchInput"
                       style="height: 38px;">
                <button v-if="searchQuery" type="button" class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y text-slate-400 hover:text-slate-600 text-decoration-none p-0 me-3" @click="searchQuery = ''; onFilterChange()">
                    <i class="bi bi-x-circle-fill fs-7"></i>
                </button>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-3 py-2 fs-8 font-medium">
                    <i class="bi bi-journal-check text-blue-600 me-1"></i>Total: <strong class="text-slate-900">{{ totalLogs }}</strong> Log
                </span>
            </div>
        </div>

        <!-- 5. Modern Table Architecture -->
        <div class="table-responsive" style="margin-bottom: 1.25rem;">
            <table class="pengguna-table table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th style="width: 150px;">WAKTU & TANGGAL</th>
                        <th v-if="isSuperAdmin" style="width: 180px;">SEKOLAH / TENANT</th>
                        <th style="width: 220px;">AKTOR & PERAN</th>
                        <th style="width: 180px;">AKSI & MODUL</th>
                        <th>RINGKASAN PERUBAHAN DATA</th>
                        <th class="text-center" style="width: 90px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading State -->
                    <tr v-if="loading">
                        <td :colspan="isSuperAdmin ? 7 : 6" class="text-center py-5 text-slate-400">
                            <div class="spinner-border spinner-border-sm text-blue-600 me-2" role="status"></div>
                            <span class="font-semibold fs-8">Memuat rekam jejak audit trail...</span>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-else-if="logs.length === 0">
                        <td :colspan="isSuperAdmin ? 7 : 6" class="text-center py-5">
                            <div class="py-4">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2 shadow-2xs" style="width: 48px; height: 48px;">
                                    <i class="bi bi-journal-x"></i>
                                </div>
                                <h6 class="fw-bold text-slate-800 fs-7 mb-1">Tidak Ada Rekaman Log</h6>
                                <p class="text-slate-400 fs-8 mb-0">Tidak ada data audit aktivitas yang cocok dengan kriteria filter.</p>
                            </div>
                        </td>
                    </tr>

                    <!-- Data Rows -->
                    <tr v-else v-for="(log, idx) in logs" :key="log.id">
                        <!-- No -->
                        <td class="text-slate-400 fs-8 fw-semibold">{{ (currentPage - 1) * perPage + idx + 1 }}</td>

                        <!-- Waktu & Tanggal -->
                        <td class="text-nowrap">
                            <div class="fw-bold text-slate-800 font-monospace fs-8">
                                {{ formatTime(log.created_at) }}
                            </div>
                            <div class="fs-9 text-slate-400 font-medium">
                                {{ formatDate(log.created_at) }}
                            </div>
                        </td>

                        <!-- Sekolah / Tenant (Super Admin Only) -->
                        <td v-if="isSuperAdmin">
                            <div class="fw-bold text-slate-800 text-truncate fs-8" style="max-width: 170px;" :title="log.nama_sekolah || 'Sistem (Global)'">
                                <i class="bi bi-building text-slate-400 me-1"></i>
                                {{ log.nama_sekolah || 'Sistem (Global)' }}
                            </div>
                        </td>

                        <!-- Aktor & Peran -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle bg-light-primary shadow-2xs flex-shrink-0" :style="getAvatarBgStyle(log.user_role)">
                                    {{ getInitials(log.actor_name || log.user_role) }}
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-slate-900 fs-8 text-truncate" :title="log.actor_name || 'System'">
                                        {{ log.actor_name || 'System / Guest' }}
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-0.5 flex-wrap">
                                        <span class="badge px-1.5 py-0.5 rounded text-uppercase" style="font-size: 0.65rem;" :class="getRoleBadgeClass(log.user_role)">
                                            {{ formatRoleLabel(log.user_role) }}
                                        </span>
                                        <span class="fs-9 text-slate-400 font-monospace">IP: {{ log.ip_address || '127.0.0.1' }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Aksi & Modul/Tabel -->
                        <td>
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge px-2 py-1 rounded-pill fs-9 font-bold border d-inline-flex align-items-center gap-1 shadow-2xs font-monospace" :class="getActionBadgeClass(log.action)">
                                    <i class="bi" :class="getActionIcon(log.action)"></i>
                                    {{ log.action }}
                                </span>
                            </div>
                            <div class="fs-9 font-semibold text-slate-600 text-truncate" :title="log.table_name" style="max-width: 170px;">
                                <i class="bi bi-table text-slate-400 me-1"></i>{{ formatTableLabel(log.table_name) }}
                            </div>
                        </td>

                        <!-- Preview Perubahan Nilai Data (Diff Pills) -->
                        <td>
                            <div v-html="renderDiffPills(log)"></div>
                        </td>

                        <!-- Tombol Detail -->
                        <td class="text-center">
                            <button type="button" 
                                    class="btn btn-sm btn-primary rounded-xl px-2.5 py-1 fs-8 font-semibold d-inline-flex align-items-center gap-1 shadow-2xs hover-lift" 
                                    @click="openDetailModal(log)" 
                                    title="Lihat Detail Transaksi">
                                <i class="bi bi-eye"></i> Detail
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 6. Bottom Pagination Toolbar Standard -->
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top border-slate-100 mt-2 pt-4">
            <div class="d-flex align-items-center gap-3">
                <span class="fs-8 text-slate-500">
                    Menampilkan <strong>{{ totalLogs === 0 ? 0 : (currentPage - 1) * perPage + 1 }}</strong> s.d. <strong>{{ Math.min(currentPage * perPage, totalLogs) }}</strong> dari <strong>{{ totalLogs }}</strong> log
                </span>
                <div class="d-flex align-items-center gap-1.5 ms-2">
                    <span class="fs-9 text-slate-400 text-uppercase font-bold">Baris:</span>
                    <select id="per_page_select_logs" name="per_page" 
                            class="form-select form-select-sm perpage-select shadow-2xs cursor-pointer" 
                            v-model="perPage" 
                            @change="onPerPageChange()" 
                            title="Jumlah baris per halaman">
                        <option :value="10">10</option>
                        <option :value="15">15</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
            </div>

            <nav v-if="totalPages > 1">
                <ul class="pagination pagination-modern m-0">
                    <li class="page-item" :class="{disabled: currentPage === 1 || loading}">
                        <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)" title="Halaman Sebelumnya">&laquo;</a>
                    </li>
                    <li class="page-item" v-for="(p, pIdx) in displayedPages" :key="pIdx" 
                        :class="{active: p === currentPage, disabled: p === '...' || loading}">
                        <a class="page-link" href="#" @click.prevent="p !== '...' ? changePage(p) : null">{{ p }}</a>
                    </li>
                    <li class="page-item" :class="{disabled: currentPage === totalPages || loading}">
                        <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)" title="Halaman Selanjutnya">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         4. MODAL DETAIL AUDIT TRAIL LOG (SIDE-BY-SIDE DIFF & RAW JSON)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" :class="{'show d-block': modalDetail.show}" tabindex="-1" style="background: rgba(15, 23, 42, 0.65);" v-if="modalDetail.show">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
                
                <!-- Modal Header -->
                <div class="modal-header px-6 py-4 border-b border-slate-100 d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs">
                            <i class="bi bi-shield-fill-check"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-white text-base mb-0">Rincian Audit Trail & Log Aktivitas</h5>
                            <span class="text-white/80 text-xs">Pelacakan forensik rekaman transaksi sistem terperinci</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalDetail.show = false"></button>
                </div>

                <div class="modal-body p-6 text-slate-700 text-xs" v-if="modalDetail.item">
                    
                    <!-- Metadata Header Cards Grid -->
                    <div class="row g-2.5 mb-4">
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                <span class="text-slate-400 text-[11px] font-semibold block">Aktor Pengguna</span>
                                <span class="font-bold text-slate-900 text-xs block mt-0.5 truncate">{{ modalDetail.item.actor_name || 'System / Guest' }}</span>
                                <span class="badge px-1.5 py-0.5 rounded text-[10px] font-bold border mt-1" :class="getRoleBadgeClass(modalDetail.item.user_role)">
                                    {{ formatRoleLabel(modalDetail.item.user_role) }}
                                </span>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                <span class="text-slate-400 text-[11px] font-semibold block">Aksi & Modul</span>
                                <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                    <span class="badge px-2 py-0.5 rounded text-xs font-bold border font-monospace" :class="getActionBadgeClass(modalDetail.item.action)">
                                        {{ modalDetail.item.action }}
                                    </span>
                                </div>
                                <span class="text-slate-600 font-monospace text-[11px] block mt-1 truncate">{{ modalDetail.item.table_name }}</span>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                <span class="text-slate-400 text-[11px] font-semibold block">Waktu Transaksi</span>
                                <span class="font-bold text-slate-900 text-xs block mt-0.5">{{ formatDateTime(modalDetail.item.created_at) }}</span>
                                <span class="text-slate-500 font-monospace text-[11px] block mt-1">IP: {{ modalDetail.item.ip_address || '127.0.0.1' }}</span>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                <span class="text-slate-400 text-[11px] font-semibold block">Lingkup Sekolah / Tenant</span>
                                <span class="font-bold text-slate-900 text-xs block mt-0.5 truncate">{{ modalDetail.item.nama_sekolah || 'Sistem (Super Admin / Global)' }}</span>
                                <span class="text-slate-400 text-[10px] font-monospace block mt-1 truncate">ID: {{ modalDetail.item.id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Side-by-Side Detailed Diff Table (If UPDATE) -->
                    <div class="card rounded-2xl border border-slate-200 overflow-hidden mb-4 shadow-2xs" v-if="modalDetail.diffList && modalDetail.diffList.length > 0">
                        <div class="card-header bg-slate-100/70 px-4 py-2.5 border-b border-slate-200 d-flex align-items-center justify-content-between">
                            <h6 class="font-bold text-slate-800 text-xs mb-0 d-flex align-items-center gap-1.5">
                                <i class="bi bi-arrow-left-right text-blue-600"></i>
                                Perbandingan Komparasi Nilai Data (Before vs After)
                            </h6>
                            <span class="badge bg-blue-100 text-blue-800 font-bold px-2 py-0.5 rounded-full text-[10px]">
                                {{ modalDetail.diffList.length }} Field Berubah
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0 text-xs bg-white">
                                <thead class="table-light">
                                    <tr class="text-slate-600 font-bold">
                                        <th style="width: 220px;">Kolom / Properti Data</th>
                                        <th class="text-rose-700 bg-rose-50/50" style="width: 40%;">Nilai Sebelum (Old Value)</th>
                                        <th class="text-emerald-700 bg-emerald-50/50" style="width: 40%;">Nilai Sesudah (New Value)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(diff, dIdx) in modalDetail.diffList" :key="dIdx">
                                        <td class="font-semibold text-slate-900">
                                            <span class="text-blue-700 font-bold block">{{ diff.label }}</span>
                                            <code class="text-slate-500 font-monospace text-[10px]">({{ diff.key }})</code>
                                        </td>
                                        <td class="bg-rose-50/20 text-rose-800 font-monospace">
                                            <span v-if="diff.old === null || diff.old === ''" class="badge bg-slate-100 text-slate-500 font-normal">Kosong / NULL</span>
                                            <s v-else class="text-rose-700 font-bold">{{ diff.old }}</s>
                                        </td>
                                        <td class="bg-emerald-50/20 text-emerald-800 font-monospace font-bold">
                                            <span v-if="diff.new === null || diff.new === ''" class="badge bg-slate-100 text-slate-500 font-normal">Kosong / NULL</span>
                                            <span v-else class="text-emerald-700">{{ diff.new }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- RAW JSON Payloads Inspector -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card rounded-2xl border border-slate-200 overflow-hidden shadow-2xs h-100">
                                <div class="card-header bg-slate-50 px-4 py-2 border-b border-slate-200 d-flex align-items-center justify-content-between">
                                    <span class="font-bold text-rose-700 text-xs d-flex align-items-center gap-1.5">
                                        <i class="bi bi-file-earmark-code"></i> RAW JSON Sebelum (Old Data)
                                    </span>
                                    <button type="button" class="btn btn-xs btn-light border rounded px-2 py-0.5 text-[10px] font-bold" @click="copyToClipboard(JSON.stringify(modalDetail.item.old_data, null, 2))" title="Salin JSON">
                                        <i class="bi bi-clipboard"></i> Salin
                                    </button>
                                </div>
                                <div class="card-body p-3 bg-slate-900 text-slate-100">
                                    <pre class="mb-0 font-monospace text-emerald-400" style="max-height: 220px; overflow-y: auto; font-size: 11px; line-height: 1.45;">{{ modalDetail.item.old_data ? JSON.stringify(modalDetail.item.old_data, null, 2) : '// Tidak ada data sebelumnya (Entri Baru / INSERT)' }}</pre>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card rounded-2xl border border-slate-200 overflow-hidden shadow-2xs h-100">
                                <div class="card-header bg-slate-50 px-4 py-2 border-b border-slate-200 d-flex align-items-center justify-content-between">
                                    <span class="font-bold text-emerald-700 text-xs d-flex align-items-center gap-1.5">
                                        <i class="bi bi-file-earmark-code"></i> RAW JSON Sesudah (New Data)
                                    </span>
                                    <button type="button" class="btn btn-xs btn-light border rounded px-2 py-0.5 text-[10px] font-bold" @click="copyToClipboard(JSON.stringify(modalDetail.item.new_data, null, 2))" title="Salin JSON">
                                        <i class="bi bi-clipboard"></i> Salin
                                    </button>
                                </div>
                                <div class="card-body p-3 bg-slate-900 text-slate-100">
                                    <pre class="mb-0 font-monospace text-blue-300" style="max-height: 220px; overflow-y: auto; font-size: 11px; line-height: 1.45;">{{ modalDetail.item.new_data ? JSON.stringify(modalDetail.item.new_data, null, 2) : '// Tidak ada data sesudahnya (Hapus / DELETE)' }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer px-6 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-end bg-slate-50/50">
                    <button type="button" class="btn btn-primary rounded-xl font-bold px-5 text-xs shadow-xs" @click="modalDetail.show = false">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         5. MODAL HAPUS LOG AKTIVITAS (RETENTION PURGE)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" :class="{'show d-block': modalDelete.show}" tabindex="-1" style="background: rgba(15, 23, 42, 0.65);" v-if="modalDelete.show">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
                <div class="modal-header px-6 py-4 border-b border-slate-100 bg-rose-600 text-white d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2.5">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <h5 class="modal-title font-bold text-white text-base mb-0">Hapus Log Aktivitas</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalDelete.show = false"></button>
                </div>

                <form @submit.prevent="submitDeleteLogs()">
                    <div class="modal-body p-6 text-slate-700 text-xs">
                        <div class="p-3 bg-rose-50 rounded-2xl border border-rose-200 text-rose-800 mb-4">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            Pilih rentang tanggal untuk menghapus data log aktivitas secara permanen. Tindakan pembersihan ini akan dicatat ke dalam audit trail demi integritas sistem.
                        </div>

                        <div class="row g-3">
                            <div class="col-12" v-if="isSuperAdmin">
                                <label class="form-label font-bold text-slate-700 mb-1">Pilih Sekolah / Tenant</label>
                                <select v-model="modalDelete.tenantId" class="form-select text-xs rounded-xl border-slate-200 py-2.5 shadow-2xs font-semibold">
                                    <option value="all">Semua Sekolah & Sistem (Global Purge)</option>
                                    <option value="system">🌐 Sistem (Super Admin / Global)</option>
                                    <option v-for="t in tenantOptions" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label font-bold text-slate-700 mb-1">Dari Tanggal <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="modalDelete.startDate" required :max="maxDate" class="form-control text-xs rounded-xl border-slate-200 py-2.5 shadow-2xs font-semibold">
                            </div>

                            <div class="col-6">
                                <label class="form-label font-bold text-slate-700 mb-1">Sampai Tanggal <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="modalDelete.endDate" required :max="maxDate" class="form-control text-xs rounded-xl border-slate-200 py-2.5 shadow-2xs font-semibold">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer px-6 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                        <button type="button" class="btn btn-sm btn-light rounded-xl font-semibold px-4" @click="modalDelete.show = false">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger rounded-xl font-bold px-5 shadow-xs d-flex align-items-center gap-2" :disabled="modalDelete.loading">
                            <span v-if="modalDelete.loading" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-trash3-fill"></i>
                            <span>{{ modalDelete.loading ? 'Menghapus...' : 'Hapus Log Sekarang' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     6. VUE 3 APPLICATION SCRIPT (ZERO DATA LEAKAGE)
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
{
    const { ref, reactive, computed, onMounted, watch } = Vue;

    window.VueAppRegistry.register('#activityLogsApp', {
        setup() {
            const logs = ref([]);
            const totalLogs = ref(0);
            const currentPage = ref(1);
            const totalPages = ref(1);
            const perPage = ref(15);
            const jumpPageInput = ref('');
            const loading = ref(false);
            let searchTimeout = null;

            const isSuperAdmin = ref(<?= !empty($isSuperAdmin) ? 'true' : 'false' ?>);
            const tenantOptions = ref([]);
            const roleOptions = ref([]);
            const tableOptions = ref([]);
            const extraActions = ref([]);

            const getSelectedTenantLabel = () => {
                if (!selectedTenant.value) return 'Semua Sekolah / Tenant';
                if (selectedTenant.value === 'system') return 'Sistem (Super Admin / Global)';
                const found = tenantOptions.value.find(t => String(t.id) === String(selectedTenant.value));
                return found ? found.nama_sekolah : 'Sekolah Terpilih';
            };

            const displayedPages = computed(() => {
                const total = totalPages.value;
                const current = currentPage.value;
                if (total <= 7) {
                    return Array.from({ length: total }, (_, i) => i + 1);
                }
                if (current <= 4) {
                    return [1, 2, 3, 4, 5, '...', total];
                }
                if (current >= total - 3) {
                    return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
                }
                return [1, '...', current - 1, current, current + 1, '...', total];
            });

            const selectedTenant = ref('<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>');
            const selectedRole = ref('');
            const selectedAction = ref('');
            const selectedTable = ref('');
            const searchQuery = ref('');
            const startDate = ref('');
            const endDate = ref('');

            const stats = reactive({
                total_logs: 0,
                total_update: 0,
                total_insert: 0,
                total_delete: 0,
                total_today: 0
            });

            const modalDetail = reactive({
                show: false,
                item: null,
                diffList: []
            });

            const modalDelete = reactive({
                show: false,
                loading: false,
                tenantId: 'all',
                startDate: '',
                endDate: new Date().toISOString().split('T')[0]
            });

            const maxDate = new Date().toISOString().split('T')[0];

            // Human-friendly dictionary for database columns
            const fieldLabels = {
                nama_lengkap: 'Nama Lengkap',
                nama: 'Nama',
                judul: 'Judul',
                isi: 'Isi / Konten',
                kategori: 'Kategori',
                visibilitas: 'Sasaran Audiens',
                deskripsi: 'Deskripsi',
                lokasi: 'Lokasi / Tempat',
                penanggung_jawab: 'Penanggung Jawab',
                tanggal_mulai: 'Tanggal Mulai',
                tanggal_selesai: 'Tanggal Selesai',
                waktu_mulai: 'Waktu Mulai',
                waktu_selesai: 'Waktu Selesai',
                is_active: 'Status Aktif',
                is_pinned: 'Status Disematkan',
                jenis_kelamin: 'Jenis Kelamin',
                nik: 'NIK',
                nisn: 'NISN',
                nip: 'NIP',
                no_kk: 'No. KK',
                id_angkatan: 'Angkatan',
                id_tahun_ajaran: 'Tahun Ajaran',
                id_jenjang: 'Jenjang',
                id_jurusan: 'Jurusan',
                id_kelas: 'Kelas',
                id_pendidikan: 'Pendidikan',
                nama_wali: 'Nama Wali',
                nama_ayah: 'Nama Ayah',
                nama_ibu: 'Nama Ibu',
                current_step: 'Tahap Registrasi',
                subdomain: 'Subdomain',
                npsn: 'NPSN',
                nama_sekolah: 'Nama Sekolah',
                alamat: 'Alamat',
                email: 'Email',
                telepon: 'No. Telepon',
                no_telp: 'No. Telepon',
                status: 'Status Akses',
                paket_aktif: 'Paket Langganan',
                status_sinkronisasi: 'Status Sinkronisasi',
                tempat_lahir: 'Tempat Lahir',
                tanggal_lahir: 'Tanggal Lahir',
                agama: 'Agama',
                tenant_id: 'Sekolah',
                user_id: 'Aktor',
                role_id: 'Role/Peran',
                user_role: 'Peran Pengguna',
                diverifikasi_oleh: 'Diverifikasi Oleh'
            };

            const getFieldLabel = (key) => fieldLabels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

            const hasActiveFilters = computed(() => {
                return searchQuery.value !== '' || selectedRole.value !== '' || selectedAction.value !== '' || selectedTable.value !== '' || startDate.value !== '' || endDate.value !== '';
            });

            // ─── API CALLS ──────────────────────────────────────────
            const fetchFilters = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/activity-logs/filters');
                    if (res.data && res.data.success) {
                        const d = res.data.data || {};
                        tenantOptions.value = d.tenants || [];
                        roleOptions.value = d.roles || [];
                        tableOptions.value = d.tables || [];
                        
                        const defaultActions = ['INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'];
                        extraActions.value = (d.actions || []).filter(a => !defaultActions.includes(a));
                    }
                } catch (e) {
                    console.error('Failed to load filter options:', e);
                }
            };

            const fetchStats = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/activity-logs/stats', {
                        params: { tenant_filter: selectedTenant.value }
                    });
                    if (res.data && res.data.success) {
                        Object.assign(stats, res.data.data);
                    }
                } catch (e) {
                    console.error('Failed to load stats:', e);
                }
            };

            const fetchLogs = async () => {
                loading.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/activity-logs', {
                        params: {
                            page: currentPage.value,
                            per_page: perPage.value,
                            search: searchQuery.value,
                            tenant_filter: selectedTenant.value,
                            role_filter: selectedRole.value,
                            action_filter: selectedAction.value,
                            table_filter: selectedTable.value,
                            start_date: startDate.value,
                            end_date: endDate.value
                        }
                    });

                    if (res.data && res.data.success) {
                        logs.value = res.data.data || [];
                        const pag = res.data.pagination || {};
                        totalLogs.value = pag.total !== undefined ? pag.total : (res.data.data ? res.data.data.length : 0);
                        totalPages.value = pag.pages || 1;
                    } else {
                        throw new Error(res.data.error || 'Gagal memuat log.');
                    }
                } catch (e) {
                    console.error('Failed to load activity logs:', e);
                    logs.value = [];
                    totalLogs.value = 0;
                    totalPages.value = 1;
                } finally {
                    loading.value = false;
                }
            };

            const refreshAll = async () => {
                await Promise.all([fetchStats(), fetchLogs()]);
            };

            const onSearchInput = () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage.value = 1;
                    fetchLogs();
                }, 400);
            };

            const onFilterChange = () => {
                currentPage.value = 1;
                refreshAll();
            };

            const onPerPageChange = () => {
                currentPage.value = 1;
                fetchLogs();
            };

            const resetFilters = () => {
                searchQuery.value = '';
                selectedRole.value = '';
                selectedAction.value = '';
                selectedTable.value = '';
                startDate.value = '';
                endDate.value = '';
                currentPage.value = 1;
                refreshAll();
            };

            const changePage = (p) => {
                if (typeof p !== 'number') return;
                if (p >= 1 && p <= totalPages.value && p !== currentPage.value) {
                    currentPage.value = p;
                    fetchLogs();
                }
            };

            const handleJumpPage = () => {
                const p = parseInt(jumpPageInput.value, 10);
                if (!isNaN(p) && p >= 1 && p <= totalPages.value) {
                    changePage(p);
                    jumpPageInput.value = '';
                }
            };

            // ─── HELPER COMPARISONS & DIFF RENDERING ────────────────
            const isDifferent = (val1, val2) => {
                const str1 = val1 === null || val1 === undefined ? '' : String(val1).trim();
                const str2 = val2 === null || val2 === undefined ? '' : String(val2).trim();
                return str1 !== str2;
            };

            const escapeHtml = (text) => {
                if (text === null || text === undefined) return '';
                const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return text.toString().replace(/[&<>"']/g, m => map[m]);
            };

            const renderDiffPills = (log) => {
                const action = (log.action || '').toUpperCase();
                if (action === 'INSERT') {
                    return '<span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-1 rounded-lg text-[11px] font-bold"><i class="bi bi-plus-circle-fill me-1"></i> Entri Baru Ditambahkan</span>';
                }
                if (action === 'DELETE') {
                    return '<span class="badge bg-rose-50 text-rose-700 border border-rose-200 px-2 py-1 rounded-lg text-[11px] font-bold"><i class="bi bi-trash3-fill me-1"></i> Data Telah Dihapus</span>';
                }
                if (action === 'LOGIN') {
                    return '<span class="badge bg-blue-50 text-blue-700 border border-blue-200 px-2 py-1 rounded-lg text-[11px] font-bold"><i class="bi bi-box-arrow-in-right me-1"></i> Berhasil Otentikasi Masuk</span>';
                }
                if (action === 'LOGOUT') {
                    return '<span class="badge bg-purple-50 text-purple-700 border border-purple-200 px-2 py-1 rounded-lg text-[11px] font-bold"><i class="bi bi-box-arrow-right me-1"></i> Sesi Telah Ditutup</span>';
                }
                if (action === 'UPDATE') {
                    const oldObj = (typeof log.old_data === 'object' && log.old_data !== null) ? log.old_data : {};
                    const newObj = (typeof log.new_data === 'object' && log.new_data !== null) ? log.new_data : {};

                    const diffs = [];
                    for (const key in newObj) {
                        if (isDifferent(oldObj[key], newObj[key])) {
                            const oldVal = (oldObj[key] !== null && oldObj[key] !== undefined) ? String(oldObj[key]) : 'Kosong';
                            const newVal = (newObj[key] !== null && newObj[key] !== undefined) ? String(newObj[key]) : 'Kosong';

                            const shortOld = oldVal.length > 16 ? oldVal.substring(0, 14) + '...' : oldVal;
                            const shortNew = newVal.length > 16 ? newVal.substring(0, 14) + '...' : newVal;

                            diffs.push({ label: getFieldLabel(key), old: shortOld, new: shortNew });
                        }
                    }

                    if (diffs.length === 0) {
                        return '<span class="text-slate-400 text-xs italic font-medium">Pembaruan state rekaman</span>';
                    }

                    const maxShow = 2;
                    let html = '<div class="d-flex flex-wrap gap-1.5 align-items-center">';
                    diffs.slice(0, maxShow).forEach(d => {
                        html += `<span class="badge bg-slate-50 text-slate-800 border border-slate-200 px-2 py-1 rounded-lg text-[10px] font-monospace" style="font-weight: 500;">
                            <span class="text-blue-600 font-bold">${escapeHtml(d.label)}</span>: 
                            <span class="text-rose-600"><s>${escapeHtml(d.old)}</s></span> ➔ 
                            <span class="text-emerald-700 font-bold">${escapeHtml(d.new)}</span>
                        </span>`;
                    });

                    if (diffs.length > maxShow) {
                        html += `<span class="badge bg-blue-50 text-blue-700 border border-blue-200 px-1.5 py-0.5 rounded text-[10px] font-bold">+${diffs.length - maxShow} lainnya</span>`;
                    }
                    html += '</div>';
                    return html;
                }
                return '<span class="text-slate-400">—</span>';
            };

            // ─── MODAL DETAIL AUDIT ─────────────────────────────────
            const openDetailModal = (log) => {
                modalDetail.item = log;
                modalDetail.diffList = [];

                const oldObj = (typeof log.old_data === 'object' && log.old_data !== null) ? log.old_data : {};
                const newObj = (typeof log.new_data === 'object' && log.new_data !== null) ? log.new_data : {};

                if (log.action === 'UPDATE') {
                    for (const key in newObj) {
                        if (isDifferent(oldObj[key], newObj[key])) {
                            const oldVal = (oldObj[key] !== null && oldObj[key] !== undefined) ? String(oldObj[key]) : '';
                            const newVal = (newObj[key] !== null && newObj[key] !== undefined) ? String(newObj[key]) : '';
                            modalDetail.diffList.push({
                                key,
                                label: getFieldLabel(key),
                                old: oldVal,
                                new: newVal
                            });
                        }
                    }
                }

                modalDetail.show = true;
            };

            const copyToClipboard = (text) => {
                if (!text) return;
                navigator.clipboard.writeText(text).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersalin!',
                        text: 'RAW JSON berhasil disalin ke clipboard.',
                        timer: 1200,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-3xl' }
                    });
                }).catch(() => {
                    Swal.fire('Info', 'Gagal menyalin otomatis.', 'info');
                });
            };

            // ─── MODAL HAPUS LOG ────────────────────────────────────
            const openDeleteModal = () => {
                modalDelete.tenantId = isSuperAdmin.value ? 'all' : 'self';
                modalDelete.startDate = '';
                modalDelete.endDate = new Date().toISOString().split('T')[0];
                modalDelete.show = true;
            };

            const submitDeleteLogs = async () => {
                if (!modalDelete.startDate || !modalDelete.endDate) {
                    Swal.fire('Perhatian', 'Harap isi rentang tanggal penghapusan log.', 'warning');
                    return;
                }
                if (modalDelete.startDate > modalDelete.endDate) {
                    Swal.fire('Perhatian', 'Tanggal awal tidak boleh melebihi tanggal akhir.', 'warning');
                    return;
                }

                const confirm = await Swal.fire({
                    title: 'Hapus Log Aktivitas?',
                    text: 'Data audit log pada rentang tanggal tersebut akan dihapus secara permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus Sekarang',
                    cancelButtonText: 'Batal',
                    customClass: { popup: 'rounded-3xl' }
                });

                if (!confirm.isConfirmed) return;

                modalDelete.loading = true;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/activity-logs/delete', {
                        startDate: modalDelete.startDate,
                        endDate: modalDelete.endDate,
                        tenantId: modalDelete.tenantId
                    });

                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.data.message || 'Log aktivitas berhasil dihapus.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl' }
                        });
                        modalDelete.show = false;
                        await refreshAll();
                    } else {
                        throw new Error(res.data.error || 'Gagal menghapus log.');
                    }
                } catch (e) {
                    Swal.fire('Gagal', (e.response && e.response.data && e.response.data.error) || e.message || 'Gagal menghapus log.', 'error');
                } finally {
                    modalDelete.loading = false;
                }
            };

            // ─── FORMATTERS & BADGE STYLES ──────────────────────────
            const formatTime = (raw) => {
                if (!raw) return '—';
                try {
                    const d = new Date(raw.replace(/-/g, '/'));
                    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                } catch(e) { return raw; }
            };

            const formatDate = (raw) => {
                if (!raw) return '';
                try {
                    const d = new Date(raw.replace(/-/g, '/'));
                    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                } catch(e) { return ''; }
            };

            const formatDateTime = (raw) => {
                if (!raw) return '—';
                try {
                    const d = new Date(raw.replace(/-/g, '/'));
                    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) + ' • ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                } catch(e) { return raw; }
            };

            const formatRoleLabel = (role) => {
                if (!role) return 'System';
                const map = {
                    super_admin: 'Super Admin',
                    admin_sekolah: 'Admin Sekolah',
                    operator_sekolah: 'Operator',
                    guru: 'Guru / Pendidik',
                    siswa: 'Siswa',
                    bk: 'Guru BK',
                    humas: 'Humas',
                    kurikulum: 'Kurikulum',
                    kesiswaan: 'Kesiswaan',
                    sarpras: 'Sarpras',
                    karyawan: 'Karyawan'
                };
                return map[role.toLowerCase()] || role.replace(/_/g, ' ').toUpperCase();
            };

            const formatTableLabel = (table) => {
                if (!table) return '—';
                const map = {
                    'sistem.pengguna': 'Pengguna & Akun',
                    'core.users': 'Pengguna (Core)',
                    'core.tenants': 'Data Sekolah (Tenant)',
                    'siswa.buku_induk': 'Siswa (Buku Induk)',
                    'kesiswaan.ekskul': 'Ekstrakurikuler',
                    'sistem.pengumuman': 'Pengumuman Sekolah',
                    'sistem.agenda_sekolah': 'Agenda Kegiatan',
                    'sistem.activity_logs': 'Log Aktivitas',
                    'akademik.mata_pelajaran': 'Mata Pelajaran',
                    'akademik.jadwal_pelajaran': 'Jadwal Pelajaran',
                    'akademik.nilai_rapor': 'Nilai Rapor'
                };
                return map[table] || table;
            };

            const getInitials = (name) => {
                if (!name) return 'S';
                const parts = name.trim().split(' ');
                if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
                return name.substring(0, 2).toUpperCase();
            };

            const getAvatarBgStyle = (role) => {
                const r = (role || '').toLowerCase();
                if (r === 'super_admin') return 'background: linear-gradient(135deg, #4f46e5, #7c3aed);';
                if (r === 'admin_sekolah' || r === 'operator_sekolah') return 'background: linear-gradient(135deg, #0284c7, #2563eb);';
                if (r === 'guru') return 'background: linear-gradient(135deg, #059669, #10b981);';
                if (r === 'siswa') return 'background: linear-gradient(135deg, #d97706, #f59e0b);';
                return 'background: linear-gradient(135deg, #64748b, #475569);';
            };

            const getRoleBadgeClass = (role) => {
                const r = (role || '').toLowerCase();
                if (r === 'super_admin') return 'bg-purple-50 text-purple-700 border-purple-200';
                if (r === 'admin_sekolah' || r === 'operator_sekolah') return 'bg-blue-50 text-blue-700 border-blue-200';
                if (r === 'guru') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                if (r === 'siswa') return 'bg-amber-50 text-amber-700 border-amber-200';
                return 'bg-slate-100 text-slate-700 border-slate-200';
            };

            const getActionBadgeClass = (act) => {
                const a = (act || '').toUpperCase();
                if (a === 'INSERT') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                if (a === 'UPDATE') return 'bg-amber-50 text-amber-800 border-amber-200';
                if (a === 'DELETE') return 'bg-rose-50 text-rose-700 border-rose-200';
                if (a === 'LOGIN') return 'bg-blue-50 text-blue-700 border-blue-200';
                if (a === 'LOGOUT') return 'bg-purple-50 text-purple-700 border-purple-200';
                return 'bg-slate-100 text-slate-700 border-slate-200';
            };

            const getActionIcon = (act) => {
                const a = (act || '').toUpperCase();
                if (a === 'INSERT') return 'bi-plus-circle-fill';
                if (a === 'UPDATE') return 'bi-pencil-square';
                if (a === 'DELETE') return 'bi-trash3-fill';
                if (a === 'LOGIN') return 'bi-box-arrow-in-right';
                if (a === 'LOGOUT') return 'bi-box-arrow-right';
                return 'bi-activity';
            };

            // ─── INITIALIZATION ─────────────────────────────────────
            onMounted(() => {
                fetchFilters();
                refreshAll();
            });

            return {
                logs,
                totalLogs,
                currentPage,
                totalPages,
                perPage,
                jumpPageInput,
                displayedPages,
                loading,
                isSuperAdmin,
                tenantOptions,
                roleOptions,
                tableOptions,
                extraActions,
                selectedTenant,
                selectedRole,
                selectedAction,
                selectedTable,
                searchQuery,
                startDate,
                endDate,
                hasActiveFilters,
                stats,
                modalDetail,
                modalDelete,
                maxDate,
                fetchFilters,
                fetchStats,
                fetchLogs,
                refreshAll,
                onSearchInput,
                onFilterChange,
                onPerPageChange,
                handleJumpPage,
                resetFilters,
                changePage,
                renderDiffPills,
                openDetailModal,
                copyToClipboard,
                openDeleteModal,
                submitDeleteLogs,
                getSelectedTenantLabel,
                formatTime,
                formatDate,
                formatDateTime,
                formatRoleLabel,
                formatTableLabel,
                getInitials,
                getAvatarBgStyle,
                getRoleBadgeClass,
                getActionBadgeClass,
                getActionIcon
            };
        }
    });
}
</script>

<style>
/* Custom Table Scrollbar */
.custom-scrollbar {
    overflow-x: auto !important;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f8fafc;
}
.custom-scrollbar::-webkit-scrollbar {
    height: 6px;
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
    transition: background 0.2s ease;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}

[v-cloak] {
    display: none !important;
}
</style>
