<?php
/**
 * View: Log Aktivitas Sistem (Audit Trail & Tracking Perubahan Data)
 * SINTA SaaS - Standardized Vue 3 Dynamic Single Page Experience
 */
$pageTitle = $title ?? 'Audit Trail & Log Aktivitas Sistem';
$userRole = $_SESSION['role_name'] ?? $_SESSION['user']['role'] ?? '';
?>

<div id="activityLogsApp" v-cloak class="p-3 p-md-4 max-w-7xl mx-auto font-sans">

    <!-- ═══════════════════════════════════════════════════════════════════════
         1. HERO BANNER & STATISTIK RINGKASAN
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="row g-3 g-md-4 mb-4">
        <!-- Banner Title -->
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
                                <i class="bi bi-shield-check text-amber-300"></i> Audit Trail & Log Keamanan Sistem
                            </span>
                        </div>
                        <h2 class="h3 font-bold text-white mb-1 tracking-tight">Audit Trail & Log Aktivitas</h2>
                        <p class="text-white/85 text-xs mb-0" style="max-width: 680px; line-height: 1.6;">
                            Rekam jejak komprehensif seluruh transaksi data, audit perubahan nilai (INSERT, UPDATE, DELETE), riwayat login, dan pelacakan aktor secara real-time.
                        </p>
                    </div>

                    <!-- Right Controls: Super Admin Tenant Filter & Action Buttons -->
                    <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
                        <div v-if="isSuperAdmin && tenantOptions.length > 0" class="d-flex align-items-center gap-2 bg-white/15 p-2 rounded-xl border border-white/25 shadow-xs" style="backdrop-filter: blur(6px);">
                            <i class="bi bi-buildings text-white fs-6 ms-1.5"></i>
                            <select v-model="selectedTenant" @change="onFilterChange()" class="form-select form-select-sm border-0 text-xs font-semibold bg-white text-slate-800 rounded-lg shadow-2xs cursor-pointer" style="min-width: 220px;">
                                <option value="">Semua Sekolah / Tenant</option>
                                <option value="system">🌐 Sistem (Super Admin / Global)</option>
                                <option v-for="t in tenantOptions" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-danger rounded-xl px-3.5 py-2 text-xs md:text-sm font-bold shadow-sm d-flex align-items-center gap-2 hover:bg-rose-700 transition" @click="openDeleteModal()">
                            <i class="bi bi-trash3-fill"></i>
                            <span>Hapus Log</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Modern Stat Metric Cards -->
        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Total Log Sistem</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_logs || 0 }}</span>
                    <span class="text-[11px] text-blue-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-journal-text"></i> Seluruh rekaman audit
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-blue-100">
                    <i class="bi bi-database-fill-check"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Perubahan (UPDATE)</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_update || 0 }}</span>
                    <span class="text-[11px] text-amber-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-arrow-repeat"></i> Komparasi nilai data
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-amber-100">
                    <i class="bi bi-pencil-square"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Data Baru (INSERT)</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_insert || 0 }}</span>
                    <span class="text-[11px] text-emerald-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-plus-circle-fill"></i> Penambahan entri baru
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-emerald-100">
                    <i class="bi bi-plus-circle"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Hapus Data (DELETE)</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_delete || 0 }}</span>
                    <span class="text-[11px] text-rose-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-exclamation-triangle-fill"></i> Aktivitas kritis
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-rose-100">
                    <i class="bi bi-trash3"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         2. FILTER TOOLBAR (RESPONSIF 1-CARD SLEEK BAR)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-3 mb-4">
        <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
            
            <!-- Left: Search & Filter Dropdowns -->
            <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <!-- Search Input -->
                <div class="position-relative flex-grow-1 flex-md-grow-0" style="min-width: 220px; max-width: 280px;">
                    <i class="bi bi-search position-absolute text-slate-400" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px;"></i>
                    <input type="text" v-model="searchQuery" @input="onSearchInput" placeholder="Cari aksi, tabel, user, IP, data..." class="form-control form-control-sm ps-5 rounded-xl border-slate-200 text-xs shadow-2xs font-semibold py-2">
                    <button v-if="searchQuery" type="button" @click="searchQuery = ''; onFilterChange();" class="btn btn-sm position-absolute text-slate-400 hover:text-slate-600 p-0" style="right: 10px; top: 50%; transform: translateY(-50%);">
                        <i class="bi bi-x-circle-fill fs-6"></i>
                    </button>
                </div>

                <!-- Filter Aksi -->
                <div style="min-width: 140px;">
                    <select v-model="selectedAction" @change="onFilterChange" class="form-select form-select-sm rounded-xl border-slate-200 text-xs shadow-2xs font-semibold py-2">
                        <option value="">Semua Aksi</option>
                        <option value="INSERT">➕ INSERT (Tambah)</option>
                        <option value="UPDATE">🔄 UPDATE (Ubah)</option>
                        <option value="DELETE">🗑️ DELETE (Hapus)</option>
                        <option value="LOGIN">🔑 LOGIN</option>
                        <option value="LOGOUT">🚪 LOGOUT</option>
                        <option v-for="act in extraActions" :key="act" :value="act">{{ act }}</option>
                    </select>
                </div>

                <!-- Filter Role -->
                <div style="min-width: 140px;">
                    <select v-model="selectedRole" @change="onFilterChange" class="form-select form-select-sm rounded-xl border-slate-200 text-xs shadow-2xs font-semibold py-2">
                        <option value="">Semua Role</option>
                        <option v-for="r in roleOptions" :key="r" :value="r">
                            {{ formatRoleLabel(r) }}
                        </option>
                    </select>
                </div>

                <!-- Filter Modul / Tabel -->
                <div style="min-width: 160px;">
                    <select v-model="selectedTable" @change="onFilterChange" class="form-select form-select-sm rounded-xl border-slate-200 text-xs shadow-2xs font-semibold py-2">
                        <option value="">Semua Modul / Tabel</option>
                        <option v-for="tbl in tableOptions" :key="tbl" :value="tbl">
                            {{ formatTableLabel(tbl) }}
                        </option>
                    </select>
                </div>

                <!-- Filter Rentang Tanggal -->
                <div class="d-flex align-items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200 shadow-2xs">
                    <span class="text-[11px] text-slate-500 font-bold px-1.5">Tgl:</span>
                    <input type="date" v-model="startDate" @change="onFilterChange" class="form-control form-control-sm border-0 bg-transparent text-xs p-0 font-semibold text-slate-700" style="width: 110px;" title="Dari Tanggal">
                    <span class="text-slate-400 text-xs">-</span>
                    <input type="date" v-model="endDate" @change="onFilterChange" class="form-control form-control-sm border-0 bg-transparent text-xs p-0 font-semibold text-slate-700" style="width: 110px;" title="Sampai Tanggal">
                </div>

                <!-- Reset Button -->
                <button v-if="hasActiveFilters" type="button" @click="resetFilters" class="btn btn-sm btn-outline-secondary rounded-xl text-xs px-2.5 py-1.5 d-flex align-items-center gap-1 font-semibold hover:bg-slate-100">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>

            <!-- Right: Counter & Refresh -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span class="text-xs text-slate-500 font-semibold">
                    Total: <strong class="text-slate-900">{{ totalLogs }}</strong> log
                </span>
                <button type="button" class="btn btn-sm btn-light border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 shadow-2xs d-flex align-items-center gap-1.5 font-semibold" @click="refreshAll()" title="Segarkan Data">
                    <i class="bi bi-arrow-clockwise" :class="{'spin': loading}"></i>
                    <span class="d-none d-sm-inline">Segarkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         3. TABEL AUDIT TRAIL LOG AKTIVITAS
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden mb-4">
        <div class="table-responsive custom-scrollbar">
            <table class="table table-hover align-middle mb-0 text-xs">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200 text-nowrap">
                        <th class="ps-4 py-3" style="width: 160px;">Waktu & Tanggal</th>
                        <th v-if="isSuperAdmin" class="py-3" style="width: 180px;">Sekolah / Tenant</th>
                        <th class="py-3" style="width: 220px;">Aktor & Peran</th>
                        <th class="py-3" style="width: 180px;">Aksi & Modul</th>
                        <th class="py-3">Ringkasan Perubahan Nilai Data</th>
                        <th class="pe-4 py-3 text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading State -->
                    <tr v-if="loading">
                        <td :colspan="isSuperAdmin ? 6 : 5" class="text-center py-5 text-slate-400">
                            <div class="spinner-border spinner-border-sm text-blue-600 me-2" role="status"></div>
                            <span class="font-semibold text-xs">Memuat rekam jejak audit trail...</span>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-else-if="logs.length === 0">
                        <td :colspan="isSuperAdmin ? 6 : 5" class="text-center py-5">
                            <div class="py-4">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-center fs-3 mb-2 shadow-2xs">
                                    <i class="bi bi-shield-slash"></i>
                                </div>
                                <h6 class="font-bold text-slate-800 text-sm mb-1">Tidak Ada Rekaman Log</h6>
                                <p class="text-slate-400 text-xs mb-0">Tidak ada data audit aktivitas yang cocok dengan kriteria filter.</p>
                            </div>
                        </td>
                    </tr>

                    <!-- Data Rows -->
                    <tr v-else v-for="log in logs" :key="log.id" class="transition hover:bg-slate-50/70 border-b border-slate-100">
                        
                        <!-- Waktu -->
                        <td class="ps-4 py-3 text-nowrap">
                            <div class="font-bold text-slate-800 font-monospace text-xs">
                                {{ formatTime(log.created_at) }}
                            </div>
                            <div class="text-[11px] text-slate-400 font-medium">
                                {{ formatDate(log.created_at) }}
                            </div>
                        </td>

                        <!-- Sekolah / Tenant (Super Admin Only) -->
                        <td v-if="isSuperAdmin" class="py-3">
                            <div class="font-bold text-slate-800 truncate" style="max-width: 170px;" :title="log.nama_sekolah || 'Sistem (Global)'">
                                <i class="bi bi-buildings text-slate-400 me-1"></i>
                                {{ log.nama_sekolah || 'Sistem (Global)' }}
                            </div>
                        </td>

                        <!-- Aktor & Peran -->
                        <td class="py-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="w-8 h-8 rounded-full d-flex align-items-center justify-center text-xs font-bold text-white shadow-2xs flex-shrink-0" :style="getAvatarBgStyle(log.user_role)">
                                    {{ getInitials(log.actor_name || log.user_role) }}
                                </div>
                                <div class="overflow-hidden">
                                    <div class="font-bold text-slate-900 text-xs truncate" :title="log.actor_name || 'System'">
                                        {{ log.actor_name || 'System / Guest' }}
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-0.5 flex-wrap">
                                        <span class="badge px-1.5 py-0.5 rounded text-[10px] font-bold border" :class="getRoleBadgeClass(log.user_role)">
                                            {{ formatRoleLabel(log.user_role) }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-monospace">IP: {{ log.ip_address || '127.0.0.1' }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Aksi & Modul/Tabel -->
                        <td class="py-3">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge px-2 py-1 rounded-lg text-xs font-bold border d-inline-flex align-items-center gap-1 shadow-2xs font-monospace" :class="getActionBadgeClass(log.action)">
                                    <i class="bi" :class="getActionIcon(log.action)"></i>
                                    {{ log.action }}
                                </span>
                            </div>
                            <div class="text-[11px] font-semibold text-slate-600 truncate" :title="log.table_name" style="max-width: 170px;">
                                <i class="bi bi-table text-slate-400 me-1"></i>{{ formatTableLabel(log.table_name) }}
                            </div>
                        </td>

                        <!-- Preview Perubahan Nilai Data (Diff Pills) -->
                        <td class="py-3">
                            <div v-html="renderDiffPills(log)"></div>
                        </td>

                        <!-- Tombol Detail -->
                        <td class="pe-4 py-3 text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-xl px-3 py-1.5 text-xs font-bold shadow-2xs d-inline-flex align-items-center gap-1.5 hover:bg-blue-600 hover:text-white transition" @click="openDetailModal(log)">
                                <i class="bi bi-eye-fill"></i>
                                <span>Detail</span>
                            </button>
                        </td>

                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════
             PAGINATION BAR (SMART NUMBERED CONTROLS & PER-PAGE SELECTOR)
             ═══════════════════════════════════════════════════════════════════ -->
        <div class="d-flex flex-column flex-lg-row align-items-center justify-content-between gap-3 p-3.5 border-t border-slate-100 bg-slate-50/70">
            
            <!-- Left: Showing Info & Rows Per Page -->
            <div class="d-flex flex-wrap align-items-center gap-3 text-xs text-slate-500">
                <span>
                    Menampilkan <strong class="text-slate-800 font-bold">{{ totalLogs === 0 ? 0 : (currentPage - 1) * perPage + 1 }}</strong> - <strong class="text-slate-800 font-bold">{{ Math.min(currentPage * perPage, totalLogs) }}</strong> dari <strong class="text-slate-900 font-black">{{ totalLogs }}</strong> log
                </span>

                <div class="d-flex align-items-center gap-1.5 border-start border-slate-300 ps-3">
                    <span class="text-slate-400">Tampilkan:</span>
                    <select v-model="perPage" @change="onPerPageChange()" class="form-select form-select-sm text-xs font-bold rounded-lg border-slate-200 py-1 px-2 shadow-2xs text-slate-700 bg-white" style="width: 75px;">
                        <option :value="10">10</option>
                        <option :value="15">15</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <span class="text-slate-400">/ hal</span>
                </div>
            </div>

            <!-- Right: Smart Numbered Pagination Buttons & Quick Jump -->
            <div class="d-flex flex-wrap align-items-center gap-1.5" v-if="totalPages > 1">
                
                <!-- First Page Button -->
                <button type="button" class="btn btn-sm btn-light border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold shadow-2xs d-inline-flex align-items-center justify-content-center hover:bg-slate-100" 
                        :disabled="currentPage === 1 || loading" 
                        @click="changePage(1)" 
                        title="Halaman Pertama">
                    <i class="bi bi-chevron-double-left"></i>
                </button>

                <!-- Prev Page Button -->
                <button type="button" class="btn btn-sm btn-light border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold shadow-2xs d-inline-flex align-items-center justify-content-center hover:bg-slate-100" 
                        :disabled="currentPage === 1 || loading" 
                        @click="changePage(currentPage - 1)" 
                        title="Halaman Sebelumnya">
                    <i class="bi bi-chevron-left me-1"></i> <span class="d-none d-sm-inline">Sebelumnya</span>
                </button>

                <!-- Page Number Buttons -->
                <div class="d-flex align-items-center gap-1">
                    <template v-for="(p, pIdx) in displayedPages" :key="pIdx">
                        <span v-if="p === '...'" class="px-2 text-slate-400 font-bold text-xs select-none">...</span>
                        <button v-else type="button" 
                                class="btn btn-sm rounded-lg text-xs font-bold transition shadow-2xs d-inline-flex align-items-center justify-content-center"
                                :class="p === currentPage ? 'btn-primary text-white shadow-xs' : 'btn-light border-slate-200 text-slate-700 hover:bg-slate-100'"
                                style="min-width: 32px; height: 32px;"
                                :disabled="loading"
                                @click="changePage(p)">
                            {{ p }}
                        </button>
                    </template>
                </div>

                <!-- Next Page Button -->
                <button type="button" class="btn btn-sm btn-light border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold shadow-2xs d-inline-flex align-items-center justify-content-center hover:bg-slate-100" 
                        :disabled="currentPage === totalPages || loading" 
                        @click="changePage(currentPage + 1)" 
                        title="Halaman Berikutnya">
                    <span class="d-none d-sm-inline">Berikutnya</span> <i class="bi bi-chevron-right ms-1"></i>
                </button>

                <!-- Last Page Button -->
                <button type="button" class="btn btn-sm btn-light border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold shadow-2xs d-inline-flex align-items-center justify-content-center hover:bg-slate-100" 
                        :disabled="currentPage === totalPages || loading" 
                        @click="changePage(totalPages)" 
                        title="Halaman Terakhir">
                    <i class="bi bi-chevron-double-right"></i>
                </button>

                <!-- Quick Jump Input -->
                <div class="d-flex align-items-center gap-1 ms-2 border-start border-slate-300 ps-2 d-none d-md-flex">
                    <input type="number" min="1" :max="totalPages" v-model.number="jumpPageInput" @keyup.enter="handleJumpPage()" placeholder="Hal..." class="form-control form-control-sm text-xs font-bold rounded-lg border-slate-200 py-1 text-center" style="width: 55px;">
                    <button type="button" @click="handleJumpPage()" class="btn btn-sm btn-light border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-slate-700 hover:bg-slate-100">
                        Go
                    </button>
                </div>
            </div>
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

            const isSuperAdmin = ref(document.getElementById('activity-logs-app')?.dataset?.isSuperAdmin === 'true');
            const tenantOptions = ref([]);
            const roleOptions = ref([]);
            const tableOptions = ref([]);
            const extraActions = ref([]);

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
