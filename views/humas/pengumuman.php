<?php
/**
 * SINTA SaaS - Halaman Manajemen Pengumuman & Informasi Sekolah
 * Standardized Architecture: Vue 3 Dynamic SPA, Zero Data Leakage & PostgreSQL Multi-Schema
 */
$pageTitle = $title ?? 'Manajemen Pengumuman & Informasi Sekolah';
?>

<div id="pengumumanApp" v-cloak class="p-3 p-md-4 max-w-7xl mx-auto font-sans">

    <!-- ═══════════════════════════════════════════════════════════════════════
         1. HERO HEADER & 4 METRIC STAT CARDS
         ═══════════════════════════════════════════════════════════════════════ -->
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
                                <i class="bi bi-megaphone-fill text-amber-300"></i> Modul Humas & Publikasi
                            </span>
                        </div>
                        <h2 class="h3 font-bold text-white mb-1 tracking-tight">Manajemen Pengumuman</h2>
                        <p class="text-white/85 text-xs mb-0" style="max-width: 680px; line-height: 1.6;">
                            Pusat penerbitan warta sekolah, jadwal agenda penting, surat edaran resmi, dan sosialisasi regulasi warga sekolah terpadu.
                        </p>
                    </div>

                    <!-- Right Controls: Super Admin Tenant Filter & Action Button -->
                    <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
                        <div v-if="isSuperAdmin && tenants.length > 0" class="d-flex align-items-center gap-2 bg-white/15 p-2 rounded-xl border border-white/25 shadow-xs" style="backdrop-filter: blur(6px);">
                            <i class="bi bi-building text-white fs-6 ms-1.5"></i>
                            <select v-model="filterTenantId" @change="onTenantChange()" class="form-select form-select-sm border-0 text-xs font-semibold bg-white text-slate-800 rounded-lg shadow-2xs cursor-pointer" style="min-width: 220px;">
                                <option value="">Semua Sekolah / Tenant</option>
                                <option value="global">🌐 Pengumuman Global (Pusat)</option>
                                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-light rounded-xl px-3.5 py-2 text-xs md:text-sm font-bold text-blue-700 shadow-sm d-flex align-items-center gap-2 hover:bg-slate-50 transition" @click="openModalPengumuman()">
                            <i class="bi bi-plus-circle-fill text-blue-600"></i>
                            <span>Buat Pengumuman</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Modern Stat Metric Cards -->
        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Total Pengumuman</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_pengumuman || 0 }}</span>
                    <span class="text-[11px] text-blue-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-megaphone-fill"></i> Seluruh arsip
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-blue-100">
                    <i class="bi bi-broadcast"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Pengumuman Aktif</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_aktif || 0 }}</span>
                    <span class="text-[11px] text-emerald-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-check-circle-fill"></i> Tampil di portal
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-emerald-100">
                    <i class="bi bi-file-earmark-check"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Kategori Informasi</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_kategori || 0 }}</span>
                    <span class="text-[11px] text-indigo-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-tags-fill"></i> Klasifikasi topik
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-indigo-100">
                    <i class="bi bi-bookmarks-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Sasaran Publik</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_public || 0 }}</span>
                    <span class="text-[11px] text-amber-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-globe2"></i> Warga sekolah
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-amber-100">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         2. NAVIGATION TAB HEADER WITH MODERN PILL STYLE (ACUAN GAMBAR 1)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-sm-nowrap">
            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3.5 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'pengumuman'}"
                                @click="switchTab('pengumuman')">
                            <i class="bi bi-megaphone-fill me-2 fs-6"></i> Daftar Pengumuman
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3.5 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'kategori'}"
                                @click="switchTab('kategori')">
                            <i class="bi bi-tags-fill me-2 fs-6"></i> Manajemen Kategori
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Right: Refresh Button -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0 pe-1">
                <button type="button" class="btn btn-sm btn-light border border-slate-200/80 text-slate-600 hover:bg-slate-100 rounded-xl px-3 py-2 text-xs font-semibold shadow-2xs d-flex align-items-center gap-1.5" @click="refreshAll()" title="Segarkan Data">
                    <i class="bi bi-arrow-clockwise" :class="{'spin': loading}"></i>
                    <span class="d-none d-sm-inline">Segarkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         3. TAB CONTENT 1: DAFTAR PENGUMUMAN
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'pengumuman'">
        
        <!-- Filter Toolbar (Single-Line Symmetrical SaaS Toolbar) -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-3 mb-4">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                
                <!-- Left: Live Search & Compact Dropdowns -->
                <div class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap">
                    <!-- Search Input -->
                    <div class="position-relative" style="min-width: 200px; max-width: 260px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 text-xs"></i>
                        <input type="text" v-model="searchQuery" @input="debounceSearch()" placeholder="Cari judul / warta..." class="form-control form-control-sm text-xs rounded-xl ps-4 pe-4 border-slate-200 shadow-2xs bg-white py-1.5 font-medium focus:ring-2 focus:ring-blue-500">
                        <button v-if="searchQuery" @click="searchQuery = ''; fetchPengumuman()" class="btn btn-sm p-0 position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 border-0 bg-transparent" title="Hapus Pencarian">
                            <i class="bi bi-x-circle-fill text-xs"></i>
                        </button>
                    </div>

                    <!-- Kategori Filter -->
                    <select v-model="filterKategoriId" @change="fetchPengumuman()" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer" style="width: auto; max-width: 170px;">
                        <option value="">Semua Kategori</option>
                        <option v-for="kat in kategoriList" :key="kat.id" :value="kat.id">{{ kat.nama_kategori }}</option>
                    </select>

                    <!-- Visibilitas Filter -->
                    <select v-model="filterVisibilitas" @change="fetchPengumuman()" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer" style="width: auto;">
                        <option value="">Semua Sasaran</option>
                        <option value="public">🌐 Publik</option>
                        <option value="guru">👨‍🏫 Guru &amp; Tendik</option>
                        <option value="siswa">🎓 Siswa</option>
                        <option value="private">🔒 Spesifik</option>
                    </select>

                    <!-- Status Filter -->
                    <select v-model="filterStatus" @change="fetchPengumuman()" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer" style="width: auto;">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-Aktif</option>
                    </select>

                    <!-- Reset Filter Button -->
                    <button v-if="searchQuery || filterKategoriId || filterVisibilitas || filterStatus" @click="resetFilters()" class="btn btn-sm btn-light border border-slate-200 text-rose-600 rounded-xl px-2.5 py-1.5 text-xs font-bold hover:bg-rose-50 shadow-2xs d-inline-flex align-items-center gap-1" title="Reset Semua Filter">
                        <i class="bi bi-x-lg"></i> Reset
                    </button>
                </div>

                <!-- Right: Counter Badge & Action Button -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 px-3 py-2 rounded-xl text-xs font-semibold">
                        Menampilkan <strong class="text-slate-900">{{ filteredPengumumanList.length }}</strong> warta
                    </span>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs d-flex align-items-center gap-1.5 shadow-sm hover:shadow transition" @click="openModalPengumuman()">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Buat Pengumuman</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Data Pengumuman -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden mb-5">
            <!-- Loading State -->
            <div v-if="loadingPengumuman" class="text-center py-5">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span class="text-xs text-slate-500 font-semibold">Memuat daftar pengumuman...</span>
            </div>

            <!-- Seamless Empty State (Clean Illustration without awkward raw header) -->
            <div v-else-if="filteredPengumumanList.length === 0" class="p-5 text-center">
                <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-600 border border-blue-100/80 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-2xs">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
                <h6 class="font-bold text-slate-800 text-sm md:text-base mb-1">Belum Ada Pengumuman Diterbitkan</h6>
                <p class="text-slate-500 text-xs mb-4 max-w-md mx-auto leading-relaxed">
                    {{ searchQuery || filterKategoriId || filterVisibilitas || filterStatus ? 'Tidak ada warta yang cocok dengan parameter filter pencarian. Silakan reset filter atau gunakan kata kunci lain.' : 'Publikasikan informasi, surat edaran resmi, agenda kegiatan, atau pengumuman penting bagi seluruh civitas sekolah.' }}
                </p>
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <button v-if="searchQuery || filterKategoriId || filterVisibilitas || filterStatus" type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl px-3.5 py-2 text-xs font-bold shadow-2xs hover:bg-slate-100" @click="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl px-4 py-2 text-xs font-bold shadow-sm d-flex align-items-center gap-1.5 hover:shadow transition" @click="openModalPengumuman()">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Buat Pengumuman Baru</span>
                    </button>
                </div>
            </div>

            <!-- Table Rows when data exists -->
            <div v-else class="custom-scrollbar" style="overflow-x: auto;">
                <table class="table table-hover align-middle mb-0 text-slate-700 text-xs w-100" style="min-width: 920px;">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-3 text-center" style="width: 55px;">NO</th>
                            <th class="py-3.5 px-4">INFORMASI &amp; WARTA PENGUMUMAN</th>
                            <th class="py-3.5 px-3 text-center" style="width: 180px;">KATEGORI</th>
                            <th class="py-3.5 px-3 text-center" style="width: 140px;">AUDIENS</th>
                            <th class="py-3.5 px-3 text-center" style="width: 120px;">STATUS</th>
                            <th class="py-3.5 px-3 text-center" style="width: 125px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, index) in paginatedPengumumanList" :key="item.id" class="transition hover:bg-slate-50/70">
                            <!-- No -->
                            <td class="text-center py-3.5 px-3 font-bold text-slate-400">
                                {{ (currentPage - 1) * perPage + index + 1 }}
                            </td>
                            
                            <!-- Judul, Ringkasan, Author & Tanggal -->
                            <td class="py-3.5 px-4">
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <a href="javascript:void(0)" @click="previewPengumuman(item)" class="font-bold text-slate-800 text-[13px] hover:text-blue-600 transition text-decoration-none" style="line-height: 1.35;">
                                            {{ item.judul }}
                                        </a>
                                        <span v-if="!item.tenant_id" class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold px-2 py-0.5 rounded-pill">
                                            <i class="bi bi-globe me-0.5"></i> Global
                                        </span>
                                        <span v-else-if="item.nama_sekolah" class="badge bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-medium px-2 py-0.5 rounded-pill">
                                            <i class="bi bi-building me-0.5"></i> {{ item.nama_sekolah }}
                                        </span>
                                    </div>
                                    
                                    <p class="text-xs text-slate-500 mb-0 line-clamp-2" style="line-height: 1.5;">
                                        {{ item.deskripsi || item.isi_pengumuman || '— Tidak ada ringkasan deskripsi —' }}
                                    </p>

                                    <div class="d-flex align-items-center gap-3 text-[11px] text-slate-400 font-medium mt-0.5 flex-wrap">
                                        <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                                            <i class="bi bi-person-fill text-slate-400"></i> {{ item.nama_pembuat || 'Admin' }}
                                        </span>
                                        <span class="text-slate-300">•</span>
                                        <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                                            <i class="bi bi-calendar3 text-slate-400"></i> {{ formatDateIndo(item.created_at) }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="py-3.5 px-3 text-center">
                                <span class="badge px-3 py-1.5 rounded-lg text-xs font-bold border d-inline-flex align-items-center gap-1.5" :style="getKategoriBadgeStyle(item.nama_kategori)">
                                    <i class="bi bi-tag-fill"></i> {{ item.nama_kategori || 'Umum' }}
                                </span>
                            </td>

                            <!-- Audiens -->
                            <td class="py-3.5 px-3 text-center">
                                <span class="badge px-2.5 py-1.5 rounded-lg text-[11px] font-bold border d-inline-flex align-items-center gap-1.5" :class="getVisibilitasBadgeClass(item.visibilitas)">
                                    <i class="bi" :class="getVisibilitasIcon(item.visibilitas)"></i>
                                    {{ getVisibilitasLabel(item.visibilitas) }}
                                </span>
                                <div v-if="item.visibilitas === 'private' && item.target_roles" class="text-[10px] text-slate-400 mt-1 font-semibold">
                                    {{ formatTargetRoles(item.target_roles) }}
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-3 text-center">
                                <button type="button" class="btn btn-sm rounded-pill px-3 py-1 text-xs font-bold border shadow-2xs transition d-inline-flex align-items-center gap-1.5"
                                        :class="item.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'"
                                        @click="toggleStatusPengumuman(item)" title="Beralih Status">
                                    <i class="bi" :class="item.is_active ? 'bi-check-circle-fill text-emerald-600' : 'bi-dash-circle text-slate-400'"></i>
                                    {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </button>
                            </td>

                            <!-- Aksi (Unified Action Group) -->
                            <td class="py-3.5 px-3 text-center">
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                    <button type="button" class="btn btn-sm btn-light border-0 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-1.5 transition" @click="previewPengumuman(item)" title="Pratinjau Pengumuman">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border-0 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg p-1.5 transition" @click="editPengumuman(item)" title="Edit Warta">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border-0 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg p-1.5 transition" @click="deletePengumuman(item)" title="Hapus Warta">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modern Pagination Footer (Daftar Pengumuman) -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50/50" v-if="filteredPengumumanList.length > 0">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-xs text-slate-500 font-semibold">Tampilkan:</span>
                    <select class="form-select form-select-sm rounded-xl py-1 text-xs border-slate-300 bg-white shadow-2xs font-semibold" style="width: 75px;" v-model="perPage" @change="currentPage = 1">
                        <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                    <span class="text-xs text-slate-500 font-medium ms-1">
                        Menampilkan {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, filteredPengumumanList.length) }} dari {{ filteredPengumumanList.length }} warta
                    </span>
                </div>
                <nav v-if="totalPages > 1" aria-label="Navigasi Halaman Pengumuman">
                    <ul class="pagination pagination-sm m-0 gap-1">
                        <li class="page-item" :class="{disabled: currentPage === 1}">
                            <button class="page-link rounded-xl border-slate-200 text-slate-600 px-2.5 py-1 text-xs font-bold" @click.prevent="currentPage = 1" :disabled="currentPage === 1">&laquo;</button>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === 1}">
                            <button class="page-link rounded-xl border-slate-200 text-slate-600 px-2.5 py-1 text-xs font-bold" @click.prevent="currentPage--" :disabled="currentPage === 1">&lsaquo;</button>
                        </li>
                        <li class="page-item" v-for="page in displayedPages" :key="page" :class="{active: page === currentPage, disabled: page === '...'}">
                            <button v-if="page !== '...'" class="page-link rounded-xl border-slate-200 px-2.5 py-1 text-xs font-bold" :class="page === currentPage ? 'bg-blue-600 border-blue-600 text-white' : 'text-slate-600'" @click.prevent="currentPage = page">{{ page }}</button>
                            <span v-else class="px-2 py-1 text-slate-400 text-xs">...</span>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === totalPages}">
                            <button class="page-link rounded-xl border-slate-200 text-slate-600 px-2.5 py-1 text-xs font-bold" @click.prevent="currentPage++" :disabled="currentPage === totalPages">&rsaquo;</button>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === totalPages}">
                            <button class="page-link rounded-xl border-slate-200 text-slate-600 px-2.5 py-1 text-xs font-bold" @click.prevent="currentPage = totalPages" :disabled="currentPage === totalPages">&raquo;</button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         4. TAB CONTENT 2: MANAJEMEN KATEGORI
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'kategori'">
        
        <!-- Filter & Action Toolbar -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-3 mb-4">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <!-- Search Box -->
                <div class="position-relative flex-grow-1 flex-md-grow-0" style="min-width: 200px; max-width: 300px;">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 text-xs"></i>
                    <input type="text" v-model="searchKategori" placeholder="Cari nama kategori..." class="form-control form-control-sm text-xs rounded-xl ps-4 pe-4 border-slate-200 shadow-2xs bg-white py-1.5 font-medium focus:ring-2 focus:ring-blue-500">
                    <button v-if="searchKategori" @click="searchKategori = ''" class="btn btn-sm p-0 position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 border-0 bg-transparent" title="Hapus Pencarian">
                        <i class="bi bi-x-circle-fill text-xs"></i>
                    </button>
                </div>

                <!-- Right: Add Button & Total Badge -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 px-3 py-2 rounded-xl text-xs font-semibold">
                        Menampilkan <strong class="text-slate-900">{{ filteredKategoriList.length }}</strong> kategori
                    </span>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 text-xs font-bold shadow-sm d-flex align-items-center gap-1.5 hover:shadow transition" @click="openModalKategori()">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Tambah Kategori</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Data Kategori -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden mb-5">
            <!-- Loading State -->
            <div v-if="loadingKategori" class="text-center py-5">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span class="text-xs text-slate-500 font-semibold">Memuat kategori...</span>
            </div>

            <!-- Seamless Empty State (No raw floating thead) -->
            <div v-else-if="filteredKategoriList.length === 0" class="p-5 text-center">
                <div class="w-16 h-16 rounded-3xl bg-indigo-50 text-indigo-600 border border-indigo-100/80 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-2xs">
                    <i class="bi bi-tags-fill"></i>
                </div>
                <h6 class="font-bold text-slate-800 text-sm md:text-base mb-1">Belum Ada Kategori Informasi</h6>
                <p class="text-slate-500 text-xs mb-4 max-w-md mx-auto leading-relaxed">
                    {{ searchKategori ? 'Tidak ditemukan kategori yang sesuai dengan kata kunci pencarian Anda.' : 'Tambahkan kategori seperti Akademik, Kegiatan, Kedisiplinan, atau Humas untuk mengorganisasi arsip pengumuman.' }}
                </p>
                <button type="button" class="btn btn-sm btn-primary rounded-xl px-4 py-2 text-xs font-bold shadow-sm d-flex align-items-center gap-1.5 mx-auto hover:shadow transition" @click="openModalKategori()">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Tambah Kategori Baru</span>
                </button>
            </div>

            <!-- Table Rows when data exists -->
            <div v-else class="custom-scrollbar" style="overflow-x: auto;">
                <table class="table table-hover align-middle mb-0 text-slate-700 text-xs w-100" style="min-width: 780px;">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-3 text-center" style="width: 55px;">NO</th>
                            <th class="py-3.5 px-4">NAMA KATEGORI INFORMASI</th>
                            <th class="py-3.5 px-3 text-center" style="width: 160px;">JUMLAH WARTA</th>
                            <th class="py-3.5 px-3 text-center" style="width: 170px;">LINGKUP SEKOLAH</th>
                            <th class="py-3.5 px-3 text-center" style="width: 110px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(kat, kIdx) in paginatedKategoriList" :key="kat.id" class="transition hover:bg-slate-50/70">
                            <td class="text-center py-3.5 px-3 font-bold text-slate-400">
                                {{ (currentPageKategori - 1) * perPageKategori + kIdx + 1 }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 d-flex align-items-center justify-content-center text-sm flex-shrink-0 shadow-2xs">
                                        <i class="bi bi-bookmark-fill"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-900 text-sm block">{{ kat.nama_kategori }}</span>
                                        <span class="text-[11px] text-slate-400">Dibuat {{ formatDateIndo(kat.created_at) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-pill font-bold text-xs">
                                    {{ kat.total_pengumuman || 0 }} Pengumuman
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <span v-if="!kat.tenant_id" class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold px-2 py-0.5 rounded-pill">
                                    <i class="bi bi-globe me-0.5"></i> Global
                                </span>
                                <span v-else class="badge bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-medium px-2 py-0.5 rounded-pill">
                                    <i class="bi bi-building me-0.5"></i> {{ kat.nama_sekolah || 'Sekolah' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                    <button type="button" class="btn btn-sm btn-light border-0 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg p-1.5 transition" @click="editKategori(kat)" title="Edit Kategori">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border-0 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg p-1.5 transition" @click="deleteKategori(kat)" title="Hapus Kategori">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modern Pagination Footer (Manajemen Kategori) -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50/50" v-if="filteredKategoriList.length > 0">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-xs text-slate-500 font-semibold">Tampilkan:</span>
                    <select class="form-select form-select-sm rounded-xl py-1 text-xs border-slate-300 bg-white shadow-2xs font-semibold" style="width: 75px;" v-model="perPageKategori" @change="currentPageKategori = 1">
                        <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                    <span class="text-xs text-slate-500 font-medium ms-1">
                        Menampilkan {{ (currentPageKategori - 1) * perPageKategori + 1 }} - {{ Math.min(currentPageKategori * perPageKategori, filteredKategoriList.length) }} dari {{ filteredKategoriList.length }} kategori
                    </span>
                </div>
                <nav v-if="totalKategoriPages > 1" aria-label="Navigasi Halaman Kategori">
                    <ul class="pagination pagination-sm m-0 gap-1">
                        <li class="page-item" :class="{disabled: currentPageKategori === 1}">
                            <button class="page-link rounded-xl border-slate-200 text-slate-600 px-2.5 py-1 text-xs font-bold" @click.prevent="currentPageKategori = 1" :disabled="currentPageKategori === 1">&laquo;</button>
                        </li>
                        <li class="page-item" :class="{disabled: currentPageKategori === 1}">
                            <button class="page-link rounded-xl border-slate-200 text-slate-600 px-2.5 py-1 text-xs font-bold" @click.prevent="currentPageKategori--" :disabled="currentPageKategori === 1">&lsaquo;</button>
                        </li>
                        <li class="page-item" v-for="page in displayedKategoriPages" :key="page" :class="{active: page === currentPageKategori, disabled: page === '...'}">
                            <button v-if="page !== '...'" class="page-link rounded-xl border-slate-200 px-2.5 py-1 text-xs font-bold" :class="page === currentPageKategori ? 'bg-blue-600 border-blue-600 text-white' : 'text-slate-600'" @click.prevent="currentPageKategori = page">{{ page }}</button>
                            <span v-else class="px-2 py-1 text-slate-400 text-xs">...</span>
                        </li>
                        <li class="page-item" :class="{disabled: currentPageKategori === totalKategoriPages}">
                            <button class="page-link rounded-xl border-slate-200 text-slate-600 px-2.5 py-1 text-xs font-bold" @click.prevent="currentPageKategori++" :disabled="currentPageKategori === totalKategoriPages">&rsaquo;</button>
                        </li>
                        <li class="page-item" :class="{disabled: currentPageKategori === totalKategoriPages}">
                            <button class="page-link rounded-xl border-slate-200 text-slate-600 px-2.5 py-1 text-xs font-bold" @click.prevent="currentPageKategori = totalKategoriPages" :disabled="currentPageKategori === totalKategoriPages">&raquo;</button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         5. MODAL 1: BUAT / EDIT PENGUMUMAN (MODERN EXECUTIVE POPUP)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-block': modalPengumuman.show}" tabindex="-1" v-if="modalPengumuman.show">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in">
                <!-- Header with Sleek Indigo-Blue Gradient & Ambient Glow -->
                <div class="modal-header px-4 px-md-5 py-4 border-0 d-flex align-items-center justify-content-between text-white position-relative overflow-hidden"
                     style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #2563eb 100%);">
                    <div class="position-absolute rounded-circle" style="width: 180px; height: 180px; background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%); top: -40px; right: -30px; pointer-events: none;"></div>
                    
                    <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 2;">
                        <div class="w-11 h-11 rounded-2xl bg-white/15 text-white border border-white/20 d-flex align-items-center justify-content-center fs-5 shadow-xs flex-shrink-0" style="backdrop-filter: blur(8px);">
                            <i class="bi" :class="modalPengumuman.isEdit ? 'bi-pencil-square text-amber-300' : 'bi-megaphone-fill text-blue-200'"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge px-2 py-0.5 rounded-pill text-[10px] font-bold text-white/90" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25);">
                                    {{ modalPengumuman.isEdit ? 'Pembaruan Data' : 'Publikasi Baru' }}
                                </span>
                            </div>
                            <h5 class="modal-title font-black text-white text-base md:text-lg mb-0 tracking-tight mt-0.5">
                                {{ modalPengumuman.isEdit ? 'Edit Warta Pengumuman' : 'Terbitkan Pengumuman Baru' }}
                            </h5>
                            <span class="text-white/75 text-xs font-normal">Lengkapi rincian berita, kategori, dan sasaran pembaca</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon rounded-xl text-white/80 hover:text-white hover:bg-white/10 p-2 border-0 transition" @click="modalPengumuman.show = false" title="Tutup Modal">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>

                <form @submit.prevent="submitPengumuman()">
                    <div class="modal-body p-4 p-md-5 text-slate-700 text-xs bg-slate-50/40">
                        <div class="row g-3 g-md-4">
                            
                            <!-- Judul Pengumuman -->
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-800 mb-1.5 d-flex align-items-center justify-content-between">
                                    <span>Judul Pengumuman <span class="text-rose-500">*</span></span>
                                    <span class="text-[11px] text-slate-400 font-normal">Buat judul yang jelas & komunikatif</span>
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                        <i class="bi bi-fonts fs-6"></i>
                                    </span>
                                    <input type="text" v-model="modalPengumuman.form.judul" required 
                                           placeholder="Contoh: Jadwal Pelaksanaan Asesmen Sumatif Akhir Semester (ASAS)" 
                                           class="form-control text-xs font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                                </div>
                            </div>

                            <!-- Kategori & Scope Tenant -->
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Kategori Informasi <span class="text-rose-500">*</span>
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                        <i class="bi bi-tags-fill text-indigo-500"></i>
                                    </span>
                                    <select v-model="modalPengumuman.form.kategori_id" required 
                                            class="form-select text-xs font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white cursor-pointer focus:ring-2 focus:ring-blue-500 transition">
                                        <option value="" disabled>-- Pilih Kategori Topik --</option>
                                        <option v-for="kat in kategoriList" :key="kat.id" :value="kat.id">
                                            {{ kat.nama_kategori }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Superadmin Tenant Scope (or Target Tenant) -->
                            <div class="col-12 col-md-6" v-if="isSuperAdmin">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Lingkup Sekolah / Tenant <span class="text-rose-500">*</span>
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                        <i class="bi bi-building text-blue-500"></i>
                                    </span>
                                    <select v-model="modalPengumuman.form.tenant_id" class="form-select text-xs font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white cursor-pointer focus:ring-2 focus:ring-blue-500 transition">
                                        <option value="global">🌐 Pengumuman Global (Seluruh Sekolah/Tenant)</option>
                                        <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }} ({{ t.npsn || 'Tenant' }})</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Interactive Audience Selection Grid (Modern Visual Segmented Cards) -->
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-800 mb-2 d-flex align-items-center justify-content-between">
                                    <span>Sasaran Audiens (Target Pembaca) <span class="text-rose-500">*</span></span>
                                    <span class="badge bg-slate-100 text-slate-600 font-medium px-2 py-0.5 rounded-pill text-[10px]">
                                        Hak Akses Portal
                                    </span>
                                </label>
                                
                                <div class="row g-2.5">
                                    <!-- Public -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="modalPengumuman.form.visibilitas === 'public' ? 'active bg-blue-50/80 border-blue-500 text-blue-700 shadow-xs' : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300'">
                                            <input type="radio" value="public" v-model="modalPengumuman.form.visibilitas" class="d-none">
                                            <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center mb-2 fs-5"
                                                 :class="modalPengumuman.form.visibilitas === 'public' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-globe2"></i>
                                            </div>
                                            <span class="font-bold text-xs">Semua Warga</span>
                                            <small class="text-[10px] text-slate-400 mt-0.5">Publik & Tamu</small>
                                            <i v-if="modalPengumuman.form.visibilitas === 'public'" class="bi bi-check-circle-fill text-blue-600 position-absolute top-0 end-0 m-2 fs-7"></i>
                                        </label>
                                    </div>

                                    <!-- Guru & Tendik -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="modalPengumuman.form.visibilitas === 'guru' ? 'active bg-emerald-50/80 border-emerald-500 text-emerald-700 shadow-xs' : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300'">
                                            <input type="radio" value="guru" v-model="modalPengumuman.form.visibilitas" class="d-none">
                                            <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center mb-2 fs-5"
                                                 :class="modalPengumuman.form.visibilitas === 'guru' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-person-badge-fill"></i>
                                            </div>
                                            <span class="font-bold text-xs">Dewan Guru</span>
                                            <small class="text-[10px] text-slate-400 mt-0.5">Guru &amp; Tendik</small>
                                            <i v-if="modalPengumuman.form.visibilitas === 'guru'" class="bi bi-check-circle-fill text-emerald-600 position-absolute top-0 end-0 m-2 fs-7"></i>
                                        </label>
                                    </div>

                                    <!-- Siswa -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="modalPengumuman.form.visibilitas === 'siswa' ? 'active bg-purple-50/80 border-purple-500 text-purple-700 shadow-xs' : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300'">
                                            <input type="radio" value="siswa" v-model="modalPengumuman.form.visibilitas" class="d-none">
                                            <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center mb-2 fs-5"
                                                 :class="modalPengumuman.form.visibilitas === 'siswa' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-mortarboard-fill"></i>
                                            </div>
                                            <span class="font-bold text-xs">Peserta Didik</span>
                                            <small class="text-[10px] text-slate-400 mt-0.5">Khusus Siswa</small>
                                            <i v-if="modalPengumuman.form.visibilitas === 'siswa'" class="bi bi-check-circle-fill text-purple-600 position-absolute top-0 end-0 m-2 fs-7"></i>
                                        </label>
                                    </div>

                                    <!-- Spesifik Role -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="modalPengumuman.form.visibilitas === 'private' ? 'active bg-rose-50/80 border-rose-500 text-rose-700 shadow-xs' : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300'">
                                            <input type="radio" value="private" v-model="modalPengumuman.form.visibilitas" class="d-none">
                                            <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center mb-2 fs-5"
                                                 :class="modalPengumuman.form.visibilitas === 'private' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-lock-fill"></i>
                                            </div>
                                            <span class="font-bold text-xs">Role Spesifik</span>
                                            <small class="text-[10px] text-slate-400 mt-0.5">Kustom Group</small>
                                            <i v-if="modalPengumuman.form.visibilitas === 'private'" class="bi bi-check-circle-fill text-rose-600 position-absolute top-0 end-0 m-2 fs-7"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Target Roles Checkbox Group (Conditional if Private) -->
                            <div class="col-12" v-if="modalPengumuman.form.visibilitas === 'private'">
                                <div class="p-3.5 bg-rose-50/50 border border-rose-200/80 rounded-2xl">
                                    <div class="d-flex align-items-center gap-1.5 mb-2.5">
                                        <i class="bi bi-shield-lock-fill text-rose-600"></i>
                                        <span class="font-bold text-rose-900 text-xs">Pilih Role Khusus Penerima Warta:</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <label v-for="r in rolesList" :key="r.id" class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-xl border bg-white cursor-pointer transition text-xs font-semibold"
                                               :class="modalPengumuman.form.target_roles.includes(r.nama_role) ? 'border-rose-500 text-rose-700 bg-rose-50/60 shadow-2xs' : 'border-slate-200 text-slate-600'">
                                            <input class="form-check-input text-rose-600 cursor-pointer m-0" type="checkbox" :value="r.nama_role" v-model="modalPengumuman.form.target_roles">
                                            <span>{{ r.nama_role }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Isi Lengkap Pengumuman -->
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mb-1.5">
                                    <label class="form-label font-bold text-slate-800 mb-0">
                                        Isi Lengkap Pengumuman <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="badge bg-slate-100 text-slate-500 font-mono px-2 py-0.5 rounded-pill text-[10px]">
                                        {{ (modalPengumuman.form.deskripsi || '').length }} karakter
                                    </span>
                                </div>
                                <textarea v-model="modalPengumuman.form.deskripsi" required rows="6" 
                                          placeholder="Tuliskan rincian lengkap instruksi, waktu pelaksanaan, lokasi kegiatan, atau lampiran ketentuan pengumuman di sini..." 
                                          class="form-control text-xs rounded-2xl border-slate-200 p-3.5 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 font-normal leading-relaxed"></textarea>
                            </div>

                            <!-- Status Penayangan Toggle Card -->
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center fs-5"
                                             :class="modalPengumuman.form.is_active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400'">
                                            <i class="bi" :class="modalPengumuman.form.is_active ? 'bi-broadcast-pin' : 'bi-pause-circle-fill'"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-800 text-xs block">Status Penayangan</span>
                                            <span class="text-slate-400 text-[11px]">
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
                    
                    <!-- Footer with Clean Action Buttons -->
                    <div class="modal-footer px-4 px-md-5 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-white">
                        <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl font-bold px-4 py-2 text-xs shadow-2xs transition" @click="modalPengumuman.show = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-xl font-bold px-5 py-2 text-xs shadow-sm d-flex align-items-center gap-2 hover:shadow transition" :disabled="modalPengumuman.saving">
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
         6. MODAL 2: DETAIL / PREVIEW PENGUMUMAN (EXECUTIVE ARTICLE READER)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-block': modalPreview.show}" tabindex="-1" v-if="modalPreview.show">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in">
                <!-- Executive Blue Header -->
                <div class="modal-header px-4 px-md-5 py-4 border-0 d-flex align-items-center justify-content-between text-white position-relative overflow-hidden"
                     style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                    <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 2;">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs flex-shrink-0" style="backdrop-filter: blur(8px);">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-black text-white text-base md:text-lg mb-0 tracking-tight">
                                Pratinjau Warta Informasi
                            </h5>
                            <span class="text-white/80 text-xs">Tampilan resmi portal pengumuman sekolah</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon rounded-xl text-white/80 hover:text-white hover:bg-white/10 p-2 border-0 transition" @click="modalPreview.show = false" title="Tutup">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>

                <div class="modal-body p-4 p-md-5 text-slate-700 text-xs bg-slate-50/50">
                    <div v-if="modalPreview.item" class="d-flex flex-column gap-3.5">
                        
                        <!-- Top Metadata Badges -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge px-3 py-1.5 rounded-xl text-xs font-bold border shadow-2xs d-inline-flex align-items-center gap-1.5" :style="getKategoriBadgeStyle(modalPreview.item.nama_kategori)">
                                <i class="bi bi-tag-fill"></i> {{ modalPreview.item.nama_kategori || 'Umum' }}
                            </span>
                            <span class="badge px-3 py-1.5 rounded-xl text-xs font-bold border shadow-2xs d-inline-flex align-items-center gap-1.5" :class="getVisibilitasBadgeClass(modalPreview.item.visibilitas)">
                                <i class="bi" :class="getVisibilitasIcon(modalPreview.item.visibilitas)"></i>
                                {{ getVisibilitasLabel(modalPreview.item.visibilitas) }}
                            </span>
                            <span v-if="modalPreview.item.is_active" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold px-3 py-1.5 rounded-xl shadow-2xs d-inline-flex align-items-center gap-1">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i> Aktif
                            </span>
                            <span v-else class="badge bg-slate-100 text-slate-500 border border-slate-200 text-xs font-bold px-3 py-1.5 rounded-xl shadow-2xs d-inline-flex align-items-center gap-1">
                                <i class="bi bi-pause-circle text-slate-400"></i> Draft
                            </span>
                        </div>

                        <!-- Article Title -->
                        <h3 class="text-lg md:text-2xl font-black text-slate-900 mb-0 tracking-tight" style="line-height: 1.35;">
                            {{ modalPreview.item.judul }}
                        </h3>

                        <!-- Publisher Card Info -->
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 border border-blue-100 d-flex align-items-center justify-content-center font-bold text-xs flex-shrink-0">
                                    {{ (modalPreview.item.nama_pembuat || 'A').substring(0, 2).toUpperCase() }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 text-xs block">{{ modalPreview.item.nama_pembuat || 'Administrator Humas' }}</span>
                                    <span class="text-slate-400 text-[11px]">Penulis &amp; Editor Warta</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 text-slate-500 text-xs font-medium pe-2">
                                <span class="d-inline-flex align-items-center gap-1.5">
                                    <i class="bi bi-calendar-event text-blue-600"></i> {{ formatDateIndo(modalPreview.item.created_at) }}
                                </span>
                            </div>
                        </div>

                        <!-- Content Body Card -->
                        <div class="bg-white p-4 p-md-5 rounded-3xl border border-slate-200/80 shadow-xs text-slate-800 text-xs md:text-sm font-normal leading-relaxed" style="white-space: pre-wrap; line-height: 1.8;">
{{ modalPreview.item.deskripsi || modalPreview.item.isi_pengumuman }}
                        </div>

                    </div>
                </div>

                <div class="modal-footer px-4 px-md-5 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-white">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl font-bold px-4 py-2 text-xs shadow-2xs" @click="modalPreview.show = false">
                        Tutup
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 text-xs shadow-sm d-flex align-items-center gap-1.5 hover:shadow transition" @click="modalPreview.show = false; editPengumuman(modalPreview.item)">
                        <i class="bi bi-pencil-square"></i>
                        <span>Edit Pengumuman Ini</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         7. MODAL 3: TAMBAH / EDIT KATEGORI (MODERN INDIGO POPUP)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-block': modalKategori.show}" tabindex="-1" v-if="modalKategori.show">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in">
                <!-- Header with Indigo Gradient -->
                <div class="modal-header px-4 px-md-5 py-4 border-0 d-flex align-items-center justify-content-between text-white position-relative overflow-hidden"
                     style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);">
                    <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 2;">
                        <div class="w-10 h-10 rounded-2xl bg-white/15 text-white border border-white/20 d-flex align-items-center justify-content-center fs-5 shadow-xs flex-shrink-0" style="backdrop-filter: blur(8px);">
                            <i class="bi bi-tags-fill text-indigo-200"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-black text-white text-base md:text-lg mb-0 tracking-tight">
                                {{ modalKategori.isEdit ? 'Edit Kategori Warta' : 'Tambah Kategori Informasi' }}
                            </h5>
                            <span class="text-white/80 text-xs">Klasifikasi topik &amp; rubrik pengumuman</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon rounded-xl text-white/80 hover:text-white hover:bg-white/10 p-2 border-0 transition" @click="modalKategori.show = false" title="Tutup">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>

                <form @submit.prevent="submitKategori()">
                    <div class="modal-body p-4 p-md-5 text-slate-700 text-xs bg-slate-50/50">
                        <div class="mb-3.5">
                            <label class="form-label font-bold text-slate-800 mb-1.5">
                                Nama Kategori Informasi <span class="text-rose-500">*</span>
                            </label>
                            <div class="position-relative">
                                <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                    <i class="bi bi-tag-fill text-indigo-500"></i>
                                </span>
                                <input type="text" v-model="modalKategori.form.nama_kategori" required 
                                       placeholder="Contoh: Akademik &amp; Ujian, Kedisiplinan, Humas" 
                                       class="form-control text-xs font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                        </div>

                        <div class="mb-0" v-if="isSuperAdmin">
                            <label class="form-label font-bold text-slate-800 mb-1.5">
                                Lingkup Sekolah / Tenant
                            </label>
                            <select v-model="modalKategori.form.tenant_id" class="form-select text-xs font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white cursor-pointer focus:ring-2 focus:ring-indigo-500 transition">
                                <option value="global">🌐 Kategori Global (Seluruh Sekolah/Tenant)</option>
                                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer px-4 px-md-5 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-white">
                        <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl font-bold px-4 py-2 text-xs shadow-2xs" @click="modalKategori.show = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-xl font-bold px-5 py-2 text-xs shadow-sm d-flex align-items-center gap-1.5 hover:shadow transition" :disabled="modalKategori.saving" style="background-color: #4338ca; border-color: #4338ca;">
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
     8. VUE 3 CONTROLLER SETUP (DYNAMIC FETCH & ZERO DATA LEAKAGE)
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
{
    const { ref, computed, onMounted, watch } = Vue;

    window.VueAppRegistry.register('#pengumumanApp', {
        setup() {
            // Global State
            const _baseUrl = <?= json_encode($this->getBaseUrl(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const isSuperAdmin = ref(<?= json_encode($isSuperAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const tenants = ref(<?= json_encode($tenants, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const currentTenantId = ref(<?= json_encode($selectedTenantId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);

            const activeTab = ref('pengumuman');
            const loading = ref(false);
            const loadingPengumuman = ref(false);
            const loadingKategori = ref(false);

            // Filters & Searches
            const filterTenantId = ref(currentTenantId.value || '');
            const searchQuery = ref('');
            const filterKategoriId = ref('');
            const filterVisibilitas = ref('');
            const filterStatus = ref('');
            const searchKategori = ref('');

            // Data Stores
            const pengumumanList = ref([]);
            const kategoriList = ref([]);
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

            // ─── API DATA FETCHERS ──────────────────────────────────
            const fetchOptionsAndStats = async () => {
                try {
                    let url = `${_baseUrl}/api/v1/pengumuman/options`;
                    if (filterTenantId.value) {
                        url += `?tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    }
                    const res = await axios.get(url);
                    if (res.data && res.data.success) {
                        kategoriList.value = res.data.data.kategori || [];
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

            // ─── FILTERED & PAGINATED COMPUTED LISTS ────────────────
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

            const filteredKategoriList = computed(() => {
                if (!searchKategori.value) return kategoriList.value;
                const q = searchKategori.value.toLowerCase();
                return kategoriList.value.filter(k => (k.nama_kategori || '').toLowerCase().includes(q));
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

            // ─── PENGUMUMAN ACTIONS ─────────────────────────────────
            const openModalPengumuman = () => {
                modalPengumuman.value.isEdit = false;
                modalPengumuman.value.form = {
                    id: '',
                    judul: '',
                    kategori_id: kategoriList.value.length ? kategoriList.value[0].id : '',
                    visibilitas: 'public',
                    target_roles: [],
                    deskripsi: '',
                    is_active: true,
                    tenant_id: filterTenantId.value || (isSuperAdmin.value ? 'global' : currentTenantId.value)
                };
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

                modalPengumuman.value.isEdit = true;
                modalPengumuman.value.form = {
                    id: item.id,
                    judul: item.judul,
                    kategori_id: item.kategori_id || '',
                    visibilitas: item.visibilitas || 'public',
                    target_roles: Array.isArray(parsedRoles) ? parsedRoles : [],
                    deskripsi: item.deskripsi || item.isi_pengumuman || '',
                    is_active: item.is_active,
                    tenant_id: item.tenant_id || 'global'
                };
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
                    title: 'Hapus Pengumuman Ini?',
                    text: `Warta "${item.judul}" akan dihapus secara permanen.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
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

            // ─── KATEGORI ACTIONS ───────────────────────────────────
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
                    title: 'Hapus Kategori Ini?',
                    text: `Kategori "${kat.nama_kategori}" akan dihapus. Pengumuman terkait akan dialihkan ke kategori umum.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
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

            // ─── UI HELPERS & BADGE STYLES ──────────────────────────
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

            // ─── AUTO-RELOAD WATCHERS ───────────────────────────────
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

            // ─── LIFECYCLE MOUNTED ──────────────────────────────────
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

<style>
/* Modern Pill Tab Styling (Gambar 1 Standard) */
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
.nav-pills .nav-link:hover {
    background: #f1f5f9 !important;
    color: #1e293b !important;
}
.nav-pills .nav-link.active {
    color: #ffffff !important;
    background: #2563eb !important;
    box-shadow: 0 1px 3px rgba(37, 99, 235, 0.35) !important;
}

/* ═══════════════════════════════════════════════════════════════════════
   PREMIUM POPUP MODAL STYLING & ANIMATIONS
   ═══════════════════════════════════════════════════════════════════════ */
.custom-modal-backdrop {
    background: rgba(15, 23, 42, 0.65) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    transition: all 0.25s ease-out;
}

.modal-animate-in {
    animation: modalScaleUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes modalScaleUp {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Interactive Audience Visual Cards */
.audience-card {
    border: 1.5px solid #e2e8f0;
    user-select: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.audience-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.audience-card.active {
    border-width: 2px !important;
}

.scrollable-tabs {
    overflow-x: auto !important;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}
.scrollable-tabs::-webkit-scrollbar {
    height: 4px;
}
.scrollable-tabs::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 9999px;
}
.scrollable-tabs::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
    transition: background 0.2s ease;
}
.scrollable-tabs::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
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
