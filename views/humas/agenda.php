<?php
/**
 * SINTA SaaS - Halaman Agenda & Timeline Kegiatan Sekolah
 * Standardized Architecture: Vue 3 Dynamic SPA, Zero Data Leakage & PostgreSQL Multi-Schema
 */
$pageTitle = $title ?? 'Agenda & Timeline Kegiatan Sekolah';
?>

<div id="agendaApp" v-cloak class="p-3 p-md-4 max-w-7xl mx-auto font-sans">

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
                                <i class="bi bi-calendar-event-fill text-amber-300"></i> Kalender Pendidikan & Humas
                            </span>
                        </div>
                        <h2 class="h3 font-bold text-white mb-1 tracking-tight">Agenda & Timeline Kegiatan</h2>
                        <p class="text-white/85 text-xs mb-0" style="max-width: 680px; line-height: 1.6;">
                            Pusat penjadwalan terpadu kalender akademik, rapat kedinasan, asesmen ujian, dan event kesiswaan.
                        </p>
                    </div>

                    <!-- Right Controls: Super Admin Tenant Filter & Action Button -->
                    <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
                        <div v-if="isSuperAdmin && tenants.length > 0" class="d-flex align-items-center gap-2 bg-white/15 p-2 rounded-xl border border-white/25 shadow-xs" style="backdrop-filter: blur(6px);">
                            <i class="bi bi-building text-white fs-6 ms-1.5"></i>
                            <select v-model="filterTenantId" @change="onTenantChange()" class="form-select form-select-sm border-0 text-xs font-semibold bg-white text-slate-800 rounded-lg shadow-2xs cursor-pointer" style="min-width: 220px;">
                                <option value="">Semua Sekolah / Tenant</option>
                                <option value="global">🌐 Agenda Global (Pusat)</option>
                                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-light rounded-xl px-3.5 py-2 text-xs md:text-sm font-bold text-blue-700 shadow-sm d-flex align-items-center gap-2 hover:bg-slate-50 transition" @click="openModalAgenda()">
                            <i class="bi bi-plus-circle-fill text-blue-600"></i>
                            <span>Jadwalkan Agenda</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Modern Stat Metric Cards -->
        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Total Agenda</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_agenda || 0 }}</span>
                    <span class="text-[11px] text-blue-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-calendar2-range-fill"></i> Seluruh kegiatan
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-blue-100">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Agenda Aktif</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_aktif || 0 }}</span>
                    <span class="text-[11px] text-emerald-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-check-circle-fill"></i> Terjadwal resmi
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-emerald-100">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Kegiatan Bulan Ini</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_bulan_ini || 0 }}</span>
                    <span class="text-[11px] text-indigo-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-calendar-month-fill"></i> Periode berjalan
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-indigo-100">
                    <i class="bi bi-calendar-heart"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Kategori Topik</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_kategori || 0 }}</span>
                    <span class="text-[11px] text-amber-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-tags-fill"></i> Klasifikasi bidang
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-amber-100">
                    <i class="bi bi-bookmarks-fill"></i>
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
                                :class="{active: activeTab === 'kalender'}"
                                @click="switchTab('kalender')">
                            <i class="bi bi-calendar3 me-2 fs-6"></i> Kalender Interaktif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3.5 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'daftar'}"
                                @click="switchTab('daftar')">
                            <i class="bi bi-list-task me-2 fs-6"></i> Daftar Agenda & Timeline
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3.5 py-2.5 fs-7 transition" 
                                :class="{active: activeTab === 'kategori'}"
                                @click="switchTab('kategori')">
                            <i class="bi bi-tags me-2 fs-6"></i> Kategori Kegiatan
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
         3. TAB 1: KALENDER INTERAKTIF (PERBAIKAN CSS GRID 7-KOLOM & UI PRO)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'kalender'">
        <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 p-4 md:p-5 mb-5">
            
            <!-- Calendar Month Header & Controls -->
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4 pb-3 border-b border-slate-100">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border-slate-200 text-slate-700 rounded-xl p-2 shadow-2xs hover:bg-slate-100" @click="prevMonth()" title="Bulan Sebelumnya">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="d-flex align-items-center gap-2 px-2">
                        <h3 class="text-base md:text-lg font-black text-slate-900 mb-0">
                            {{ currentMonthName }} {{ currentYear }}
                        </h3>
                        <span class="badge bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold px-2 py-0.5 rounded-full">
                            {{ currentMonthEventsCount }} Kegiatan
                        </span>
                    </div>
                    <button type="button" class="btn btn-sm btn-light border-slate-200 text-slate-700 rounded-xl p-2 shadow-2xs hover:bg-slate-100" @click="nextMonth()" title="Bulan Berikutnya">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-xl px-3 py-1.5 text-xs font-bold shadow-2xs ms-2" @click="goToToday()">
                        Hari Ini
                    </button>
                </div>

                <!-- Category Legends -->
                <div class="d-flex align-items-center gap-2 flex-wrap text-xs text-slate-500">
                    <span class="d-inline-flex align-items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: #2563eb;"></span> Akademik
                    </span>
                    <span class="d-inline-flex align-items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: #059669;"></span> Kesiswaan
                    </span>
                    <span class="d-inline-flex align-items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: #d97706;"></span> Kedinasan/Rapat
                    </span>
                    <span class="d-inline-flex align-items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: #7c3aed;"></span> Libur/Peringatan
                    </span>
                    <span class="d-inline-flex align-items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: #e11d48;"></span> Ujian/Asesmen
                    </span>
                </div>
            </div>

            <!-- Calendar 7-Column Grid Container -->
            <div class="custom-scrollbar" style="overflow-x: auto;">
                <div class="calendar-grid-container">
                    
                    <!-- Day Names Header (7 Columns) -->
                    <div class="calendar-header-grid">
                        <div class="calendar-header-cell text-rose-600">Minggu</div>
                        <div class="calendar-header-cell">Senin</div>
                        <div class="calendar-header-cell">Selasa</div>
                        <div class="calendar-header-cell">Rabu</div>
                        <div class="calendar-header-cell">Kamis</div>
                        <div class="calendar-header-cell">Jumat</div>
                        <div class="calendar-header-cell text-emerald-600">Sabtu</div>
                    </div>

                    <!-- Days Body (7 Columns Grid) -->
                    <div class="calendar-body-grid">
                        <div v-for="(day, dIdx) in calendarDays" :key="dIdx" 
                             class="calendar-cell"
                             :class="{
                                 'is-other-month': !day.isCurrentMonth,
                                 'is-today': day.isToday
                             }"
                             @dblclick="openModalAgendaWithDate(day.dateStr)">
                            
                            <!-- Top: Day Number & Event Counter -->
                            <div class="d-flex align-items-center justify-content-between mb-1" style="width: 100%; min-width: 0;">
                                <span class="d-inline-flex align-items-center justify-content-center text-xs font-bold rounded-lg px-2 py-0.5" 
                                      :class="[
                                          day.isToday ? 'bg-blue-600 text-white shadow-2xs font-extrabold' : (day.isCurrentMonth ? 'text-slate-800' : 'text-slate-400')
                                      ]">
                                    {{ day.dayNumber }}
                                </span>
                                <span v-if="day.events.length" class="badge bg-slate-100 text-slate-700 text-[10px] rounded-full font-bold px-2 py-0.5 border border-slate-200">
                                    {{ day.events.length }}
                                </span>
                            </div>

                            <!-- Middle: Events List in Day Cell -->
                            <div class="d-flex flex-column gap-1 overflow-hidden flex-grow-1 my-1" style="width: 100%; min-width: 0;">
                                <div v-for="ev in day.events.slice(0, 3)" :key="ev.id"
                                     class="calendar-chip"
                                     :style="getEventBadgeStyle(ev.kategori)"
                                     @click.stop="previewAgenda(ev)"
                                     :title="ev.judul + ' (' + ev.waktu_mulai + ' - ' + ev.lokasi + ')'">
                                    <i class="bi bi-clock me-1 opacity-75"></i><span>{{ ev.waktu_mulai }} {{ ev.judul }}</span>
                                </div>
                                <span v-if="day.events.length > 3" class="text-[10px] text-blue-600 font-bold px-1 cursor-pointer hover:underline block truncate" @click.stop="showDayEvents(day)">
                                    +{{ day.events.length - 3 }} kegiatan lainnya
                                </span>
                            </div>

                            <!-- Bottom: Quick Add Plus on Hover -->
                            <div class="d-flex justify-content-end" style="width: 100%;">
                                <button type="button" class="btn btn-sm p-0 text-slate-300 hover:text-blue-600 text-xs" @click.stop="openModalAgendaWithDate(day.dateStr)" title="Tambah Agenda di Tanggal Ini">
                                    <i class="bi bi-plus-circle-fill"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         4. TAB 2: DAFTAR AGENDA & TIMELINE TABLE
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'daftar'">
        
        <!-- Filter Toolbar (Single-Line Symmetrical SaaS Toolbar) -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-3 mb-4">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                
                <!-- Left: Live Search & Compact Dropdowns -->
                <div class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap">
                    <!-- Search Input -->
                    <div class="position-relative" style="min-width: 190px; max-width: 240px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 text-xs"></i>
                        <input type="text" v-model="searchQuery" @input="debounceSearch()" placeholder="Cari kegiatan / lokasi..." class="form-control form-control-sm text-xs rounded-xl ps-4 pe-4 border-slate-200 shadow-2xs bg-white py-1.5 font-medium focus:ring-2 focus:ring-blue-500">
                        <button v-if="searchQuery" @click="searchQuery = ''; fetchAgenda()" class="btn btn-sm p-0 position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 border-0 bg-transparent" title="Hapus Pencarian">
                            <i class="bi bi-x-circle-fill text-xs"></i>
                        </button>
                    </div>

                    <!-- Kategori Filter -->
                    <select v-model="filterKategori" @change="fetchAgenda()" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer" style="width: auto; max-width: 160px;">
                        <option value="">Semua Kategori</option>
                        <option v-for="kat in kategoriList" :key="kat.nama_kategori" :value="kat.nama_kategori">{{ kat.nama_kategori }}</option>
                    </select>

                    <!-- Visibilitas Filter -->
                    <select v-model="filterVisibilitas" @change="fetchAgenda()" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer" style="width: auto;">
                        <option value="">Semua Sasaran</option>
                        <option value="public">🌐 Publik</option>
                        <option value="guru">👨‍🏫 Guru &amp; Tendik</option>
                        <option value="siswa">🎓 Siswa</option>
                        <option value="private">🔒 Spesifik</option>
                    </select>

                    <!-- Month Filter -->
                    <input type="month" v-model="filterMonth" @change="fetchAgenda()" class="form-control form-control-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer" style="width: auto;">

                    <!-- Status Filter -->
                    <select v-model="filterStatus" @change="fetchAgenda()" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer" style="width: auto;">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-Aktif</option>
                    </select>

                    <!-- Reset Filter Button -->
                    <button v-if="searchQuery || filterKategori || filterVisibilitas || filterMonth || filterStatus" @click="resetFilters()" class="btn btn-sm btn-light border border-slate-200 text-rose-600 rounded-xl px-2.5 py-1.5 text-xs font-bold hover:bg-rose-50 shadow-2xs d-inline-flex align-items-center gap-1" title="Reset Semua Filter">
                        <i class="bi bi-x-lg"></i> Reset
                    </button>
                </div>

                <!-- Right: Counter Badge & Action Button -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 px-3 py-2 rounded-xl text-xs font-semibold">
                        Menampilkan <strong class="text-slate-900">{{ filteredAgendaList.length }}</strong> kegiatan
                    </span>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs d-flex align-items-center gap-1.5 shadow-sm hover:shadow transition" @click="openModalAgenda()">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Jadwalkan Agenda</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Data Agenda -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden mb-5">
            <!-- Loading State -->
            <div v-if="loadingAgenda" class="text-center py-5">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span class="text-xs text-slate-500 font-semibold">Memuat daftar agenda...</span>
            </div>

            <!-- Seamless Empty State (No raw floating thead) -->
            <div v-else-if="filteredAgendaList.length === 0" class="p-5 text-center">
                <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-600 border border-blue-100/80 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-2xs">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <h6 class="font-bold text-slate-800 text-sm md:text-base mb-1">Belum Ada Agenda Terjadwal</h6>
                <p class="text-slate-500 text-xs mb-4 max-w-md mx-auto leading-relaxed">
                    {{ searchQuery || filterKategori || filterVisibilitas || filterMonth || filterStatus ? 'Tidak ada agenda kegiatan yang sesuai dengan parameter filter pencarian Anda.' : 'Susun jadwal rapat dinas, ujian sekolah, peringatan hari besar, dan kegiatan akademik lainnya.' }}
                </p>
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <button v-if="searchQuery || filterKategori || filterVisibilitas || filterMonth || filterStatus" type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl px-3.5 py-2 text-xs font-bold shadow-2xs hover:bg-slate-100" @click="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl px-4 py-2 text-xs font-bold shadow-sm d-flex align-items-center gap-1.5 hover:shadow transition" @click="openModalAgenda()">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Jadwalkan Agenda Baru</span>
                    </button>
                </div>
            </div>

            <!-- Table Rows when data exists -->
            <div v-else class="custom-scrollbar" style="overflow-x: auto;">
                <table class="table table-hover align-middle mb-0 text-slate-700 text-xs w-100" style="min-width: 960px;">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-3 text-center" style="width: 55px;">NO</th>
                            <th class="py-3.5 px-4" style="min-width: 280px;">KEGIATAN &amp; DETAIL ACARA</th>
                            <th class="py-3.5 px-3 text-center" style="width: 160px;">KATEGORI</th>
                            <th class="py-3.5 px-3 text-center" style="width: 180px;">TANGGAL &amp; WAKTU</th>
                            <th class="py-3.5 px-3 text-center" style="width: 120px;">AUDIENS</th>
                            <th class="py-3.5 px-3 text-center" style="width: 110px;">STATUS</th>
                            <th class="py-3.5 px-3 text-center" style="width: 125px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, index) in paginatedAgendaList" :key="item.id" class="transition hover:bg-slate-50/70">
                            <!-- No -->
                            <td class="text-center py-3.5 px-3 font-bold text-slate-400">
                                {{ (currentPage - 1) * perPage + index + 1 }}
                            </td>
                            
                            <!-- Judul, Lokasi, PJ & Ringkasan -->
                            <td class="py-3.5 px-4">
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <a href="javascript:void(0)" @click="previewAgenda(item)" class="font-bold text-slate-800 text-[13px] hover:text-blue-600 transition text-decoration-none" style="line-height: 1.35;">
                                            {{ item.judul }}
                                        </a>
                                        <span v-if="!item.tenant_id" class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold px-2 py-0.5 rounded-pill">
                                            <i class="bi bi-globe me-0.5"></i> Global
                                        </span>
                                        <span v-else-if="item.nama_sekolah" class="badge bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-medium px-2 py-0.5 rounded-pill">
                                            <i class="bi bi-building me-0.5"></i> {{ item.nama_sekolah }}
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center gap-3 text-[11px] text-slate-500 font-medium flex-wrap mt-0.5">
                                        <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                                            <i class="bi bi-geo-alt-fill text-rose-500"></i> {{ item.lokasi }}
                                        </span>
                                        <span class="text-slate-300">•</span>
                                        <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                                            <i class="bi bi-person-badge text-indigo-600"></i> PJ: {{ item.penanggung_jawab }}
                                        </span>
                                    </div>
                                    
                                    <p class="text-xs text-slate-500 mb-0 line-clamp-2" style="line-height: 1.5;">
                                        {{ item.deskripsi || '— Tidak ada keterangan tambahan —' }}
                                    </p>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="py-3.5 px-3 text-center">
                                <span class="badge px-3 py-1.5 rounded-lg text-xs font-bold border d-inline-flex align-items-center gap-1.5" :style="getEventBadgeStyle(item.kategori)">
                                    <i class="bi bi-tag-fill"></i> {{ item.kategori || 'Umum' }}
                                </span>
                            </td>

                            <!-- Tanggal & Waktu -->
                            <td class="py-3.5 px-3 text-center">
                                <div class="d-flex flex-column align-items-center gap-0.5">
                                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-1 rounded-lg text-[11px] font-bold">
                                        <i class="bi bi-calendar-event me-1 text-blue-600"></i> {{ formatDateRange(item.tanggal_mulai, item.tanggal_selesai) }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                        <i class="bi bi-clock me-1"></i> {{ item.waktu_mulai }} - {{ item.waktu_selesai }}
                                    </span>
                                </div>
                            </td>

                            <!-- Audiens -->
                            <td class="py-3.5 px-3 text-center">
                                <span class="badge px-2.5 py-1.5 rounded-lg text-[11px] font-bold border d-inline-flex align-items-center gap-1.5" :class="getVisibilitasBadgeClass(item.visibilitas)">
                                    <i class="bi" :class="getVisibilitasIcon(item.visibilitas)"></i>
                                    {{ getVisibilitasLabel(item.visibilitas) }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-3 text-center">
                                <button type="button" class="btn btn-sm rounded-pill px-3 py-1 text-xs font-bold border shadow-2xs transition d-inline-flex align-items-center gap-1.5"
                                        :class="item.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'"
                                        @click="toggleStatusAgenda(item)" title="Beralih Status">
                                    <i class="bi" :class="item.is_active ? 'bi-check-circle-fill text-emerald-600' : 'bi-dash-circle text-slate-400'"></i>
                                    {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </button>
                            </td>

                            <!-- Aksi (Unified Action Group) -->
                            <td class="py-3.5 px-3 text-center">
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                    <button type="button" class="btn btn-sm btn-light border-0 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-1.5 transition" @click="previewAgenda(item)" title="Pratinjau Agenda">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border-0 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg p-1.5 transition" @click="editAgenda(item)" title="Edit Agenda">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border-0 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg p-1.5 transition" @click="deleteAgenda(item)" title="Hapus Agenda">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modern Pagination Footer (Daftar Agenda) -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50/50" v-if="filteredAgendaList.length > 0">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-xs text-slate-500 font-semibold">Tampilkan:</span>
                    <select class="form-select form-select-sm rounded-xl py-1 text-xs border-slate-300 bg-white shadow-2xs font-semibold" style="width: 75px;" v-model="perPage" @change="currentPage = 1">
                        <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                    <span class="text-xs text-slate-500 font-medium ms-1">
                        Menampilkan {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, filteredAgendaList.length) }} dari {{ filteredAgendaList.length }} kegiatan
                    </span>
                </div>
                <nav v-if="totalPages > 1" aria-label="Navigasi Halaman Agenda">
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
         5. TAB 3: KATEGORI KEGIATAN
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'kategori'">
        <div class="row g-3 mb-5">
            <div v-for="kat in kategoriList" :key="kat.nama_kategori" class="col-md-6 col-lg-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex flex-column justify-between transition hover:-translate-y-0.5">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge px-2.5 py-1 rounded-lg text-xs font-bold border" :style="getEventBadgeStyle(kat.nama_kategori)">
                                {{ kat.nama_kategori }}
                            </span>
                            <span class="badge bg-slate-100 text-slate-700 font-bold text-xs">
                                {{ kat.total_agenda }} Agenda
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">
                            Klasifikasi terpadu untuk pengelompokan kalender kegiatan sekolah.
                        </p>
                    </div>
                    <button type="button" class="btn btn-sm btn-light border-slate-200 text-blue-600 font-bold text-xs rounded-xl w-100 d-flex align-items-center justify-content-center gap-1.5" @click="filterByKategoriAndSwitch(kat.nama_kategori)">
                        <span>Lihat Agenda Terkait</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
          6. MODAL 1: TAMBAH / EDIT AGENDA (MODERN EXECUTIVE POPUP)
          ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-flex': modalAgenda.show}" tabindex="-1" v-if="modalAgenda.show">
        <div class="modal-dialog modal-dialog-centered modal-lg my-auto" style="width: 100%; max-width: 820px; max-height: 90vh;">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in d-flex flex-column" style="max-height: 90vh;">
                <!-- Header with Sleek Indigo-Blue Gradient & Ambient Glow -->
                <div class="modal-header flex-shrink-0 px-4 px-md-5 py-3.5 border-0 d-flex align-items-center justify-content-between text-white position-relative overflow-hidden"
                     style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #2563eb 100%);">
                    <div class="position-absolute rounded-circle" style="width: 180px; height: 180px; background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%); top: -40px; right: -30px; pointer-events: none;"></div>
                    
                    <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 2;">
                        <div class="w-10 h-10 rounded-2xl bg-white/15 text-white border border-white/20 d-flex align-items-center justify-content-center fs-5 shadow-xs flex-shrink-0" style="backdrop-filter: blur(8px);">
                            <i class="bi" :class="modalAgenda.isEdit ? 'bi-pencil-square text-amber-300' : 'bi-calendar-plus-fill text-blue-200'"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge px-2 py-0.5 rounded-pill text-[10px] font-bold text-white/90" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25);">
                                    {{ modalAgenda.isEdit ? 'Pembaruan Jadwal' : 'Agenda Baru' }}
                                </span>
                            </div>
                            <h5 class="modal-title font-black text-white text-base md:text-lg mb-0 tracking-tight mt-0.5">
                                {{ modalAgenda.isEdit ? 'Edit Agenda Kegiatan' : 'Jadwalkan Agenda Kegiatan Baru' }}
                            </h5>
                            <span class="text-white/75 text-xs font-normal">Atur jadwal kalender akademik dan timeline sekolah</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon rounded-xl text-white/80 hover:text-white hover:bg-white/10 p-2 border-0 transition" @click="modalAgenda.show = false" title="Tutup Modal">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>

                <form @submit.prevent="submitAgenda()" class="d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 0;">
                    <div class="modal-body p-4 p-md-5 text-slate-700 text-xs bg-slate-50/40 overflow-y-auto flex-grow-1 custom-scrollbar" style="max-height: calc(90vh - 140px);">
                        <div class="row g-3 g-md-4">
                            
                            <!-- Nama Kegiatan -->
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-800 mb-1.5 d-flex align-items-center justify-content-between">
                                    <span>Nama Kegiatan / Agenda <span class="text-rose-500">*</span></span>
                                    <span class="text-[11px] text-slate-400 font-normal">Buat nama agenda yang deskriptif</span>
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                        <i class="bi bi-calendar-event fs-6"></i>
                                    </span>
                                    <input type="text" v-model="modalAgenda.form.nama_agenda_sekolah" required 
                                           placeholder="Contoh: Rapat Pleno Dewan Guru / Ujian Sumatif Akhir Semester" 
                                           class="form-control text-xs font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                                </div>
                            </div>

                            <!-- Kategori Kegiatan -->
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Kategori Kegiatan <span class="text-rose-500">*</span>
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                        <i class="bi bi-tags-fill text-indigo-500"></i>
                                    </span>
                                    <select v-model="modalAgenda.form.kategori" required 
                                            class="form-select text-xs font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white cursor-pointer focus:ring-2 focus:ring-blue-500 transition">
                                        <option value="Akademik">Akademik</option>
                                        <option value="Kesiswaan & Ekskul">Kesiswaan &amp; Ekskul</option>
                                        <option value="Kedinasan & Rapat">Kedinasan &amp; Rapat</option>
                                        <option value="Hari Libur & Peringatan">Hari Libur &amp; Peringatan</option>
                                        <option value="Ujian & Asesmen">Ujian &amp; Asesmen</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Superadmin Tenant Selector -->
                            <div class="col-12 col-md-6" v-if="isSuperAdmin">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Lingkup Sekolah / Tenant <span class="text-rose-500">*</span>
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                        <i class="bi bi-building text-blue-500"></i>
                                    </span>
                                    <select v-model="modalAgenda.form.tenant_id" class="form-select text-xs font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white cursor-pointer focus:ring-2 focus:ring-blue-500 transition">
                                        <option value="global">🌐 Agenda Global (Seluruh Sekolah/Tenant)</option>
                                        <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Interactive Audience Selection Grid (Modern Visual Segmented Cards) -->
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-800 mb-2 d-flex align-items-center justify-content-between">
                                    <span>Sasaran Audiens (Target Peserta) <span class="text-rose-500">*</span></span>
                                    <span class="badge bg-slate-100 text-slate-600 font-medium px-2 py-0.5 rounded-pill text-[10px]">
                                        Hak Akses Kalender
                                    </span>
                                </label>
                                
                                <div class="row g-2.5">
                                    <!-- Public -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="modalAgenda.form.visibilitas === 'public' ? 'active bg-blue-50/80 border-blue-500 text-blue-700 shadow-xs' : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300'">
                                            <input type="radio" value="public" v-model="modalAgenda.form.visibilitas" class="d-none">
                                            <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center mb-2 fs-5"
                                                 :class="modalAgenda.form.visibilitas === 'public' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-globe2"></i>
                                            </div>
                                            <span class="font-bold text-xs">Semua Warga</span>
                                            <small class="text-[10px] text-slate-400 mt-0.5">Publik &amp; Tamu</small>
                                            <i v-if="modalAgenda.form.visibilitas === 'public'" class="bi bi-check-circle-fill text-blue-600 position-absolute top-0 end-0 m-2 fs-7"></i>
                                        </label>
                                    </div>

                                    <!-- Guru & Tendik -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="modalAgenda.form.visibilitas === 'guru' ? 'active bg-emerald-50/80 border-emerald-500 text-emerald-700 shadow-xs' : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300'">
                                            <input type="radio" value="guru" v-model="modalAgenda.form.visibilitas" class="d-none">
                                            <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center mb-2 fs-5"
                                                 :class="modalAgenda.form.visibilitas === 'guru' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-person-badge-fill"></i>
                                            </div>
                                            <span class="font-bold text-xs">Dewan Guru</span>
                                            <small class="text-[10px] text-slate-400 mt-0.5">Guru &amp; Tendik</small>
                                            <i v-if="modalAgenda.form.visibilitas === 'guru'" class="bi bi-check-circle-fill text-emerald-600 position-absolute top-0 end-0 m-2 fs-7"></i>
                                        </label>
                                    </div>

                                    <!-- Siswa -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="modalAgenda.form.visibilitas === 'siswa' ? 'active bg-purple-50/80 border-purple-500 text-purple-700 shadow-xs' : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300'">
                                            <input type="radio" value="siswa" v-model="modalAgenda.form.visibilitas" class="d-none">
                                            <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center mb-2 fs-5"
                                                 :class="modalAgenda.form.visibilitas === 'siswa' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-mortarboard-fill"></i>
                                            </div>
                                            <span class="font-bold text-xs">Peserta Didik</span>
                                            <small class="text-[10px] text-slate-400 mt-0.5">Khusus Siswa</small>
                                            <i v-if="modalAgenda.form.visibilitas === 'siswa'" class="bi bi-check-circle-fill text-purple-600 position-absolute top-0 end-0 m-2 fs-7"></i>
                                        </label>
                                    </div>

                                    <!-- Spesifik Role -->
                                    <div class="col-6 col-md-3">
                                        <label class="audience-card d-flex flex-column align-items-center text-center p-3 rounded-2xl border cursor-pointer transition h-100 position-relative"
                                               :class="modalAgenda.form.visibilitas === 'private' ? 'active bg-rose-50/80 border-rose-500 text-rose-700 shadow-xs' : 'bg-white border-slate-200/80 text-slate-600 hover:border-slate-300'">
                                            <input type="radio" value="private" v-model="modalAgenda.form.visibilitas" class="d-none">
                                            <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center mb-2 fs-5"
                                                 :class="modalAgenda.form.visibilitas === 'private' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-500'">
                                                <i class="bi bi-lock-fill"></i>
                                            </div>
                                            <span class="font-bold text-xs">Role Spesifik</span>
                                            <small class="text-[10px] text-slate-400 mt-0.5">Kustom Group</small>
                                            <i v-if="modalAgenda.form.visibilitas === 'private'" class="bi bi-check-circle-fill text-rose-600 position-absolute top-0 end-0 m-2 fs-7"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Target Roles Checkbox Group (Conditional if Private) -->
                            <div class="col-12" v-if="modalAgenda.form.visibilitas === 'private'">
                                <div class="p-3.5 bg-rose-50/50 border border-rose-200/80 rounded-2xl">
                                    <div class="d-flex align-items-center gap-1.5 mb-2.5">
                                        <i class="bi bi-shield-lock-fill text-rose-600"></i>
                                        <span class="font-bold text-rose-900 text-xs">Pilih Role Khusus Penerima Agenda:</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <label v-for="r in rolesList" :key="r.id" class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-xl border bg-white cursor-pointer transition text-xs font-semibold"
                                               :class="modalAgenda.form.target_roles.includes(r.nama_role) ? 'border-rose-500 text-rose-700 bg-rose-50/60 shadow-2xs' : 'border-slate-200 text-slate-600'">
                                            <input class="form-check-input text-rose-600 cursor-pointer m-0" type="checkbox" :value="r.nama_role" v-model="modalAgenda.form.target_roles">
                                            <span>{{ r.nama_role }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Tanggal Mulai & Selesai -->
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Tanggal Mulai <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" v-model="modalAgenda.form.tanggal_mulai" @change="onStartDateChange()" required 
                                       class="form-control text-xs font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Tanggal Selesai <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" v-model="modalAgenda.form.tanggal_selesai" required 
                                       class="form-control text-xs font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <!-- Waktu Mulai & Selesai -->
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Waktu Mulai
                                </label>
                                <input type="time" v-model="modalAgenda.form.waktu_mulai" 
                                       class="form-control text-xs font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Waktu Selesai
                                </label>
                                <input type="time" v-model="modalAgenda.form.waktu_selesai" 
                                       class="form-control text-xs font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <!-- Lokasi & Quick Chips -->
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Lokasi Pelaksanaan <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" v-model="modalAgenda.form.lokasi" required placeholder="Contoh: Lapangan Utama / Aula" 
                                       class="form-control text-xs font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition mb-1.5">
                                <div class="d-flex flex-wrap gap-1">
                                    <span v-for="loc in ['Aula Utama', 'Lapangan Sekolah', 'Ruang Rapat', 'GOR Olahraga', 'Lab Komputer']" :key="loc"
                                          @click="modalAgenda.form.lokasi = loc" 
                                          class="badge bg-white text-slate-600 border border-slate-200 rounded-pill px-2 py-0.5 cursor-pointer hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition text-[10px]">
                                        {{ loc }}
                                    </span>
                                </div>
                            </div>

                            <!-- Penanggung Jawab & Quick Chips -->
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Penanggung Jawab / Panitia <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" v-model="modalAgenda.form.penanggung_jawab" required placeholder="Contoh: Waka Kesiswaan / Panitia Ujian" 
                                       class="form-control text-xs font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition mb-1.5">
                                <div class="d-flex flex-wrap gap-1">
                                    <span v-for="pj in ['Waka Kesiswaan', 'Waka Kurikulum', 'Waka Humas', 'Pengurus OSIS', 'Guru BK']" :key="pj"
                                          @click="modalAgenda.form.penanggung_jawab = pj" 
                                          class="badge bg-white text-slate-600 border border-slate-200 rounded-pill px-2 py-0.5 cursor-pointer hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition text-[10px]">
                                        {{ pj }}
                                    </span>
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-800 mb-1.5">
                                    Deskripsi / Petunjuk Kegiatan
                                </label>
                                <textarea v-model="modalAgenda.form.deskripsi" rows="4" 
                                          placeholder="Tuliskan keterangan detail pakaian, susunan acara, atau instruksi peserta di sini..." 
                                          class="form-control text-xs rounded-2xl border-slate-200 p-3.5 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 font-normal leading-relaxed"></textarea>
                            </div>

                            <!-- Toggle Status Penjadwalan -->
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl d-flex align-items-center justify-content-center fs-5"
                                             :class="modalAgenda.form.is_active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400'">
                                            <i class="bi" :class="modalAgenda.form.is_active ? 'bi-calendar-check-fill' : 'bi-pause-circle-fill'"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-800 text-xs block">Status Penjadwalan</span>
                                            <span class="text-slate-400 text-[11px]">
                                                {{ modalAgenda.form.is_active ? 'Agenda aktif akan otomatis tampil pada kalender dan beranda portal warga sekolah.' : 'Agenda disimpan sebagai draft.' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch fs-5 mb-0">
                                        <input class="form-check-input cursor-pointer" type="checkbox" v-model="modalAgenda.form.is_active">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Footer with Clean Action Buttons (Always Docked at Bottom) -->
                    <div class="modal-footer flex-shrink-0 px-4 px-md-5 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-white">
                        <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl font-bold px-4 py-2 text-xs shadow-2xs transition" @click="modalAgenda.show = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-xl font-bold px-5 py-2 text-xs shadow-sm d-flex align-items-center gap-2 hover:shadow transition" :disabled="modalAgenda.saving">
                            <span v-if="modalAgenda.saving" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-calendar-check text-xs"></i>
                            <span>{{ modalAgenda.saving ? 'Menyimpan...' : (modalAgenda.isEdit ? 'Perbarui Agenda' : 'Jadwalkan Sekarang') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
          7. MODAL 2: DETAIL / PRATINJAU AGENDA (EXECUTIVE READER)
          ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-flex': modalPreview.show}" tabindex="-1" v-if="modalPreview.show">
        <div class="modal-dialog modal-dialog-centered modal-lg my-auto" style="width: 100%; max-width: 820px; max-height: 90vh;">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in d-flex flex-column" style="max-height: 90vh;">
                <div class="modal-header flex-shrink-0 px-4 px-md-5 py-3.5 border-0 d-flex align-items-center justify-content-between text-white position-relative overflow-hidden"
                     style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                    <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 2;">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs flex-shrink-0" style="backdrop-filter: blur(8px);">
                            <i class="bi bi-calendar2-week-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-black text-white text-base md:text-lg mb-0 tracking-tight">Rincian Agenda Kegiatan</h5>
                            <span class="text-white/80 text-xs">Pratinjau jadwal terpadu kalender sekolah</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon rounded-xl text-white/80 hover:text-white hover:bg-white/10 p-2 border-0 transition" @click="modalPreview.show = false" title="Tutup">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>

                <div class="modal-body p-4 p-md-5 text-slate-700 text-xs bg-slate-50/50 overflow-y-auto flex-grow-1 custom-scrollbar" style="max-height: calc(90vh - 140px);">
                    <div v-if="modalPreview.item" class="d-flex flex-column gap-4">
                        
                        <!-- Header Details -->
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span class="badge px-3 py-1.5 rounded-xl text-xs font-bold border shadow-2xs d-inline-flex align-items-center gap-1.5" :style="getEventBadgeStyle(modalPreview.item.kategori)">
                                    <i class="bi bi-tag-fill"></i> {{ modalPreview.item.kategori || 'Umum' }}
                                </span>
                                <span class="badge px-3 py-1.5 rounded-xl text-xs font-bold border shadow-2xs d-inline-flex align-items-center gap-1.5" :class="getVisibilitasBadgeClass(modalPreview.item.visibilitas)">
                                    <i class="bi" :class="getVisibilitasIcon(modalPreview.item.visibilitas)"></i>
                                    {{ getVisibilitasLabel(modalPreview.item.visibilitas) }}
                                </span>
                                <span v-if="modalPreview.item.is_active" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold px-3 py-1.5 rounded-xl shadow-2xs d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-check-circle-fill text-emerald-600"></i> Aktif
                                </span>
                            </div>

                            <h3 class="text-xl md:text-2xl font-black text-slate-900 mb-2" style="line-height: 1.35;">
                                {{ modalPreview.item.judul }}
                            </h3>

                            <!-- Key Metrics Grid -->
                            <div class="row g-2 mt-1">
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-slate-400 text-[11px] block font-semibold">Tanggal Pelaksanaan:</span>
                                        <span class="text-xs font-bold text-slate-800 d-inline-flex align-items-center gap-1.5 mt-0.5">
                                            <i class="bi bi-calendar-event text-blue-600"></i> {{ formatDateRange(modalPreview.item.tanggal_mulai, modalPreview.item.tanggal_selesai) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-slate-400 text-[11px] block font-semibold">Waktu / Jam Acara:</span>
                                        <span class="text-xs font-bold text-slate-800 d-inline-flex align-items-center gap-1.5 mt-0.5">
                                            <i class="bi bi-clock-fill text-indigo-600"></i> {{ modalPreview.item.waktu_mulai }} - {{ modalPreview.item.waktu_selesai }} WIB
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-slate-400 text-[11px] block font-semibold">Lokasi / Tempat:</span>
                                        <span class="text-xs font-bold text-slate-800 d-inline-flex align-items-center gap-1.5 mt-0.5">
                                            <i class="bi bi-geo-alt-fill text-rose-500"></i> {{ modalPreview.item.lokasi }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-slate-400 text-[11px] block font-semibold">Penanggung Jawab / Panitia:</span>
                                        <span class="text-xs font-bold text-slate-800 d-inline-flex align-items-center gap-1.5 mt-0.5">
                                            <i class="bi bi-person-badge-fill text-amber-600"></i> {{ modalPreview.item.penanggung_jawab }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description Body -->
                        <div>
                            <span class="text-slate-400 text-xs font-bold block mb-1.5">Rincian / Petunjuk Kegiatan:</span>
                            <div class="bg-white p-4 p-md-5 rounded-3xl border border-slate-200/80 shadow-xs text-slate-800 text-xs md:text-sm font-normal" style="line-height: 1.8; white-space: pre-wrap;">
{{ modalPreview.item.deskripsi || '— Tidak ada keterangan tambahan —' }}
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer flex-shrink-0 px-4 px-md-5 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-white">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl font-bold px-4 py-2 text-xs shadow-2xs" @click="modalPreview.show = false">Tutup</button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-xl font-bold px-3 py-2 text-xs shadow-2xs" @click="deleteAgenda(modalPreview.item); modalPreview.show = false">
                            <i class="bi bi-trash3 me-1"></i> Hapus
                        </button>
                        <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 text-xs shadow-sm d-flex align-items-center gap-1.5 hover:shadow transition" @click="modalPreview.show = false; editAgenda(modalPreview.item)">
                            <i class="bi bi-pencil-square"></i> Edit Agenda Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
          8. MODAL 3: DAFTAR KEGIATAN HARIAN (DAY EVENTS POPUP)
          ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-flex': modalDay.show}" tabindex="-1" v-if="modalDay.show">
        <div class="modal-dialog modal-dialog-centered modal-md my-auto" style="width: 100%; max-width: 540px; max-height: 90vh;">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in d-flex flex-column" style="max-height: 90vh;">
                <div class="modal-header flex-shrink-0 px-4 px-md-5 py-3.5 border-0 d-flex align-items-center justify-content-between text-white position-relative overflow-hidden"
                     style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);">
                    <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 2;">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs">
                            <i class="bi bi-calendar-date-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-black text-white text-base md:text-lg mb-0 tracking-tight">Agenda {{ modalDay.formattedDate }}</h5>
                            <span class="text-white/80 text-xs">{{ modalDay.events.length }} kegiatan terjadwal</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon rounded-xl text-white/80 hover:text-white hover:bg-white/10 p-2 border-0 transition" @click="modalDay.show = false" title="Tutup">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>

                <div class="modal-body p-4 p-md-5 text-slate-700 text-xs bg-slate-50/50 overflow-y-auto flex-grow-1 custom-scrollbar" style="max-height: calc(90vh - 140px);">
                    <div class="d-flex flex-column gap-2.5">
                        <div v-for="ev in modalDay.events" :key="ev.id"
                             class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-2xs transition hover:bg-slate-50 d-flex align-items-center justify-content-between gap-2">
                            <div class="d-flex flex-column gap-0.5 overflow-hidden">
                                <span class="font-bold text-slate-900 text-xs truncate">{{ ev.judul }}</span>
                                <div class="d-flex align-items-center gap-2 text-[11px] text-slate-500">
                                    <span><i class="bi bi-clock me-1"></i>{{ ev.waktu_mulai }} - {{ ev.waktu_selesai }}</span>
                                    <span>•</span>
                                    <span><i class="bi bi-geo-alt me-1"></i>{{ ev.lokasi }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                <button type="button" class="btn btn-sm btn-light border-slate-200 text-slate-700 rounded-xl p-2 shadow-2xs" @click="modalDay.show = false; previewAgenda(ev)" title="Pratinjau">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-light border-slate-200 text-amber-600 rounded-xl p-2 shadow-2xs" @click="modalDay.show = false; editAgenda(ev)" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer flex-shrink-0 px-4 px-md-5 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-white">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl font-bold px-4 py-2 text-xs shadow-2xs" @click="modalDay.show = false">Tutup</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 text-xs shadow-sm d-flex align-items-center gap-1.5 hover:shadow transition" @click="modalDay.show = false; openModalAgendaWithDate(modalDay.dateStr)">
                        <i class="bi bi-plus-lg"></i> Tambah di Hari Ini
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     9. VUE 3 CONTROLLER SETUP (DYNAMIC FETCH & ZERO DATA LEAKAGE)
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
{
    const { ref, computed, onMounted, watch } = Vue;

    window.VueAppRegistry.register('#agendaApp', {
        setup() {
            // Global State
            const _baseUrl = <?= json_encode($this->getBaseUrl(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const isSuperAdmin = ref(<?= json_encode($isSuperAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const tenants = ref(<?= json_encode($tenants, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const currentTenantId = ref(<?= json_encode($selectedTenantId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);

            const activeTab = ref('kalender');
            const loading = ref(false);
            const loadingAgenda = ref(false);

            // Calendar Navigation State
            const currentDate = ref(new Date());
            const currentYear = computed(() => currentDate.value.getFullYear());
            const currentMonth = computed(() => currentDate.value.getMonth());
            const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            const currentMonthName = computed(() => monthNames[currentMonth.value]);

            // Filters
            const filterTenantId = ref(currentTenantId.value || '');
            const searchQuery = ref('');
            const filterKategori = ref('');
            const filterVisibilitas = ref('');
            const filterMonth = ref('');
            const filterStatus = ref('');

            // Data Stores
            const agendaList = ref([]);
            const kategoriList = ref([]);
            const rolesList = ref([]);
            const stats = ref({
                total_agenda: 0,
                total_aktif: 0,
                total_kategori: 0,
                total_bulan_ini: 0
            });

            // Pagination State
            const perPageOptions = [10, 25, 50, 100];
            const perPage = ref(10);
            const currentPage = ref(1);

            // Modals State
            const modalAgenda = ref({
                show: false,
                isEdit: false,
                saving: false,
                form: {
                    id: '',
                    nama_agenda_sekolah: '',
                    kategori: 'Akademik',
                    visibilitas: 'public',
                    target_roles: [],
                    tanggal_mulai: '',
                    tanggal_selesai: '',
                    waktu_mulai: '07:30',
                    waktu_selesai: '15:00',
                    lokasi: 'Aula Utama',
                    penanggung_jawab: 'Waka Kesiswaan',
                    deskripsi: '',
                    is_active: true,
                    tenant_id: filterTenantId.value || 'global'
                }
            });

            const modalPreview = ref({
                show: false,
                item: null
            });

            const modalDay = ref({
                show: false,
                dateStr: '',
                formattedDate: '',
                events: []
            });

            // ─── API DATA FETCHERS ──────────────────────────────────
            const fetchOptionsAndStats = async () => {
                try {
                    let url = `${_baseUrl}/api/v1/agenda/options`;
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
                    console.error("Gagal memuat options agenda:", e);
                }
            };

            const fetchAgenda = async () => {
                loadingAgenda.value = true;
                try {
                    let params = new URLSearchParams();
                    if (searchQuery.value) params.append('search', searchQuery.value);
                    if (filterKategori.value) params.append('kategori', filterKategori.value);
                    if (filterVisibilitas.value) params.append('visibilitas', filterVisibilitas.value);
                    if (filterMonth.value) params.append('month', filterMonth.value);
                    if (filterStatus.value !== '') params.append('is_active', filterStatus.value);
                    if (filterTenantId.value) params.append('tenant_id', filterTenantId.value);

                    const res = await axios.get(`${_baseUrl}/api/v1/agenda?${params.toString()}`);
                    if (res.data && res.data.success) {
                        agendaList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error("Gagal memuat agenda:", e);
                } finally {
                    loadingAgenda.value = false;
                }
            };

            const refreshAll = async () => {
                loading.value = true;
                await Promise.all([
                    fetchOptionsAndStats(),
                    fetchAgenda()
                ]);
                loading.value = false;
            };

            const switchTab = (tab) => {
                activeTab.value = tab;
                if (tab === 'daftar' || tab === 'kalender') fetchAgenda();
            };

            const onTenantChange = async () => {
                await refreshAll();
            };

            let searchTimeout = null;
            const debounceSearch = () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchAgenda();
                }, 300);
            };

            const resetFilters = () => {
                searchQuery.value = '';
                filterKategori.value = '';
                filterVisibilitas.value = '';
                filterMonth.value = '';
                filterStatus.value = '';
                fetchAgenda();
            };

            // ─── CALENDAR DAYS COMPUTED BUILDER ─────────────────────
            const calendarDays = computed(() => {
                const year = currentYear.value;
                const month = currentMonth.value;

                const firstDayOfMonth = new Date(year, month, 1);
                const lastDayOfMonth = new Date(year, month + 1, 0);

                const startingDayOfWeek = firstDayOfMonth.getDay(); // 0 = Sunday
                const totalDays = lastDayOfMonth.getDate();

                const todayStr = new Date().toISOString().split('T')[0];

                const days = [];

                // Previous month padding days
                const prevMonthLastDay = new Date(year, month, 0).getDate();
                for (let i = startingDayOfWeek - 1; i >= 0; i--) {
                    const d = prevMonthLastDay - i;
                    const prevDate = new Date(year, month - 1, d);
                    const dateStr = prevDate.toISOString().split('T')[0];
                    days.push({
                        dayNumber: d,
                        dateStr: dateStr,
                        isCurrentMonth: false,
                        isToday: dateStr === todayStr,
                        events: []
                    });
                }

                // Current month days
                for (let i = 1; i <= totalDays; i++) {
                    const curDate = new Date(year, month, i);
                    const dateStr = curDate.toISOString().split('T')[0];
                    const eventsForDay = agendaList.value.filter(ev => {
                        const start = ev.tanggal_mulai;
                        const end = ev.tanggal_selesai || start;
                        return dateStr >= start && dateStr <= end;
                    });

                    days.push({
                        dayNumber: i,
                        dateStr: dateStr,
                        isCurrentMonth: true,
                        isToday: dateStr === todayStr,
                        events: eventsForDay
                    });
                }

                // Next month padding days to fill 35 or 42 grid cells
                const remaining = 7 - (days.length % 7);
                if (remaining < 7) {
                    for (let i = 1; i <= remaining; i++) {
                        const nextDate = new Date(year, month + 1, i);
                        const dateStr = nextDate.toISOString().split('T')[0];
                        days.push({
                            dayNumber: i,
                            dateStr: dateStr,
                            isCurrentMonth: false,
                            isToday: dateStr === todayStr,
                            events: []
                        });
                    }
                }

                return days;
            });

            const currentMonthEventsCount = computed(() => {
                const year = currentYear.value;
                const month = String(currentMonth.value + 1).padStart(2, '0');
                const ym = `${year}-${month}`;
                return agendaList.value.filter(ev => {
                    const start = (ev.tanggal_mulai || '').substring(0, 7);
                    const end = (ev.tanggal_selesai || '').substring(0, 7);
                    return start === ym || end === ym || (start <= ym && end >= ym);
                }).length;
            });

            const prevMonth = () => {
                currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
            };

            const nextMonth = () => {
                currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
            };

            const goToToday = () => {
                currentDate.value = new Date();
            };

            // ─── FILTERED & PAGINATED COMPUTED LISTS ────────────────
            const filteredAgendaList = computed(() => {
                return agendaList.value;
            });

            const totalPages = computed(() => {
                return Math.ceil(filteredAgendaList.value.length / perPage.value) || 1;
            });

            const paginatedAgendaList = computed(() => {
                const start = (currentPage.value - 1) * perPage.value;
                return filteredAgendaList.value.slice(start, start + perPage.value);
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

            const filterByKategoriAndSwitch = (kategoriName) => {
                filterKategori.value = kategoriName;
                activeTab.value = 'daftar';
                currentPage.value = 1;
                fetchAgenda();
            };

            const showDayEvents = (day) => {
                modalDay.value.dateStr = day.dateStr;
                try {
                    const d = new Date(day.dateStr);
                    modalDay.value.formattedDate = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                } catch(e) {
                    modalDay.value.formattedDate = day.dateStr;
                }
                modalDay.value.events = day.events;
                modalDay.value.show = true;
            };

            const onStartDateChange = () => {
                if (modalAgenda.value.form.tanggal_mulai) {
                    if (!modalAgenda.value.form.tanggal_selesai || modalAgenda.value.form.tanggal_selesai < modalAgenda.value.form.tanggal_mulai) {
                        modalAgenda.value.form.tanggal_selesai = modalAgenda.value.form.tanggal_mulai;
                    }
                }
            };

            // ─── ACTIONS: TAMBAH / EDIT / PREVIEW / DELETE ─────────
            const openModalAgenda = () => {
                const todayStr = new Date().toISOString().split('T')[0];
                modalAgenda.value.isEdit = false;
                modalAgenda.value.form = {
                    id: '',
                    nama_agenda_sekolah: '',
                    kategori: 'Akademik',
                    visibilitas: 'public',
                    target_roles: [],
                    tanggal_mulai: todayStr,
                    tanggal_selesai: todayStr,
                    waktu_mulai: '07:30',
                    waktu_selesai: '15:00',
                    lokasi: 'Aula Utama',
                    penanggung_jawab: 'Waka Kesiswaan',
                    deskripsi: '',
                    is_active: true,
                    tenant_id: filterTenantId.value || (isSuperAdmin.value ? 'global' : currentTenantId.value)
                };
                modalAgenda.value.show = true;
            };

            const openModalAgendaWithDate = (dateStr) => {
                openModalAgenda();
                modalAgenda.value.form.tanggal_mulai = dateStr;
                modalAgenda.value.form.tanggal_selesai = dateStr;
            };

            const editAgenda = (item) => {
                let parsedRoles = [];
                if (item.target_roles) {
                    try {
                        parsedRoles = typeof item.target_roles === 'string' ? JSON.parse(item.target_roles) : item.target_roles;
                    } catch (e) {
                        parsedRoles = [];
                    }
                }
                modalAgenda.value.isEdit = true;
                modalAgenda.value.form = {
                    id: item.id,
                    nama_agenda_sekolah: item.nama_agenda_sekolah || item.judul,
                    kategori: item.kategori || 'Akademik',
                    visibilitas: item.visibilitas || 'public',
                    target_roles: Array.isArray(parsedRoles) ? parsedRoles : [],
                    tanggal_mulai: item.tanggal_mulai,
                    tanggal_selesai: item.tanggal_selesai || item.tanggal_mulai,
                    waktu_mulai: item.waktu_mulai || '07:30',
                    waktu_selesai: item.waktu_selesai || '15:00',
                    lokasi: item.lokasi || 'Kampus Sekolah',
                    penanggung_jawab: item.penanggung_jawab || 'Panitia Acara',
                    deskripsi: item.deskripsi || '',
                    is_active: item.is_active,
                    tenant_id: item.tenant_id || 'global'
                };
                modalAgenda.value.show = true;
            };

            const previewAgenda = (item) => {
                modalPreview.value.item = item;
                modalPreview.value.show = true;
            };

            const submitAgenda = async () => {
                modalAgenda.value.saving = true;
                try {
                    const res = await axios.post(`${_baseUrl}/api/v1/agenda/save`, modalAgenda.value.form);
                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.data.message || 'Agenda berhasil dijadwalkan.',
                            timer: 1800,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl' }
                        });
                        modalAgenda.value.show = false;
                        await refreshAll();
                    } else {
                        Swal.fire('Gagal!', res.data.error || 'Terjadi kesalahan.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error!', e.response?.data?.error || 'Gagal menyimpan agenda.', 'error');
                } finally {
                    modalAgenda.value.saving = false;
                }
            };

            const toggleStatusAgenda = async (item) => {
                try {
                    const res = await axios.post(`${_baseUrl}/api/v1/agenda/toggle-status`, { id: item.id });
                    if (res.data && res.data.success) {
                        item.is_active = res.data.is_active;
                        fetchOptionsAndStats();
                        const toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1600,
                            timerProgressBar: true
                        });
                        toast.fire({
                            icon: 'success',
                            title: res.data.message
                        });
                    }
                } catch (e) {
                    Swal.fire('Error!', 'Gagal mengubah status agenda.', 'error');
                }
            };

            const deleteAgenda = (item) => {
                Swal.fire({
                    title: 'Hapus Agenda Ini?',
                    text: `Kegiatan "${item.judul}" akan dihapus dari kalender sekolah.`,
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
                            const res = await axios.post(`${_baseUrl}/api/v1/agenda/delete`, { id: item.id });
                            if (res.data && res.data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus!',
                                    text: 'Agenda telah berhasil dihapus.',
                                    timer: 1500,
                                    showConfirmButton: false,
                                    customClass: { popup: 'rounded-3xl' }
                                });
                                await refreshAll();
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Gagal menghapus agenda.', 'error');
                        }
                    }
                });
            };

            // ─── UI HELPERS & BADGE STYLES ──────────────────────────
            const getEventBadgeStyle = (kategoriName) => {
                const k = (kategoriName || '').toLowerCase();
                if (k.includes('ujian') || k.includes('asesmen')) {
                    return 'background: #fef2f2 !important; color: #991b1b !important; border: 1px solid #fee2e2 !important; border-left: 3.5px solid #ef4444 !important;';
                }
                if (k.includes('kesiswaan') || k.includes('ekskul') || k.includes('olahraga')) {
                    return 'background: #f0fdf4 !important; color: #166534 !important; border: 1px solid #dcfce7 !important; border-left: 3.5px solid #22c55e !important;';
                }
                if (k.includes('dinas') || k.includes('rapat') || k.includes('pleno')) {
                    return 'background: #fffbeb !important; color: #92400e !important; border: 1px solid #fef3c7 !important; border-left: 3.5px solid #f59e0b !important;';
                }
                if (k.includes('libur') || k.includes('peringatan') || k.includes('upacara')) {
                    return 'background: #faf5ff !important; color: #6b21a8 !important; border: 1px solid #f3e8ff !important; border-left: 3.5px solid #a855f7 !important;';
                }
                return 'background: #eff6ff !important; color: #1e40af !important; border: 1px solid #dbeafe !important; border-left: 3.5px solid #3b82f6 !important;';
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

            const formatDateRange = (startStr, endStr) => {
                if (!startStr) return '—';
                try {
                    const s = new Date(startStr);
                    const formattedStart = s.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                    if (!endStr || endStr === startStr) return formattedStart;
                    const e = new Date(endStr);
                    const formattedEnd = e.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                    return `${formattedStart} - ${formattedEnd}`;
                } catch(e) { return startStr; }
            };

            // ─── AUTO-RELOAD WATCHERS ───────────────────────────────
            watch(activeTab, (newTab) => {
                currentPage.value = 1;
                if (newTab === 'daftar' || newTab === 'kalender') fetchAgenda();
            });

            watch(filterTenantId, () => {
                currentPage.value = 1;
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
                loadingAgenda,
                currentYear,
                currentMonthName,
                currentMonthEventsCount,
                calendarDays,
                filterTenantId,
                searchQuery,
                filterKategori,
                filterVisibilitas,
                filterMonth,
                filterStatus,
                agendaList,
                kategoriList,
                rolesList,
                stats,
                modalAgenda,
                modalPreview,
                modalDay,
                filteredAgendaList,
                perPageOptions,
                perPage,
                currentPage,
                totalPages,
                displayedPages,
                paginatedAgendaList,
                fetchAgenda,
                refreshAll,
                switchTab,
                onTenantChange,
                debounceSearch,
                resetFilters,
                prevMonth,
                nextMonth,
                goToToday,
                filterByKategoriAndSwitch,
                showDayEvents,
                onStartDateChange,
                openModalAgenda,
                openModalAgendaWithDate,
                editAgenda,
                previewAgenda,
                submitAgenda,
                toggleStatusAgenda,
                deleteAgenda,
                getEventBadgeStyle,
                getVisibilitasBadgeClass,
                getVisibilitasIcon,
                getVisibilitasLabel,
                formatDateRange
            };
        }
    });
}
</script>

<style>
/* Modern Pill Tab Styling */
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
}
.nav-pills .nav-link.active {
    color: #ffffff !important;
    background: #2563eb !important;
    box-shadow: 0 1px 3px rgba(37, 99, 235, 0.35) !important;
}

/* Modern Calendar Explicit CSS Grid */
.calendar-grid-container {
    width: 100% !important;
    background: #ffffff;
    border-radius: 18px;
}
.calendar-header-grid {
    display: grid !important;
    grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
    gap: 8px !important;
    margin-bottom: 8px !important;
    width: 100% !important;
}
.calendar-header-cell {
    padding: 10px 4px !important;
    text-align: center !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-radius: 12px !important;
    background: #f8fafc !important;
    color: #475569 !important;
    border: 1px solid #f1f5f9 !important;
}
.calendar-body-grid {
    display: grid !important;
    grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
    gap: 8px !important;
    width: 100% !important;
}
.calendar-cell {
    min-width: 0 !important;
    width: 100% !important;
    min-height: 128px !important;
    padding: 8px 10px !important;
    border-radius: 14px !important;
    border: 1px solid #e2e8f0 !important;
    background: #ffffff !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
}
.calendar-cell:hover {
    border-color: #93c5fd !important;
    box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.12) !important;
    transform: translateY(-2px) !important;
}
.calendar-cell.is-other-month {
    background: #f8fafc !important;
    border-color: #f1f5f9 !important;
    opacity: 0.55 !important;
}
.calendar-cell.is-today {
    border-color: #3b82f6 !important;
    background: #f0f7ff !important;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3) !important;
}
.calendar-chip {
    min-width: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
    display: block !important;
    padding: 5px 8px !important;
    border-radius: 8px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    line-height: 1.35 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03) !important;
    margin-bottom: 3px !important;
}
.calendar-chip:hover {
    transform: scale(1.02) !important;
    filter: brightness(0.95) !important;
}

/* Modern Modal Backdrop & Animation */
.custom-modal-backdrop {
    background: rgba(15, 23, 42, 0.72) !important;
    backdrop-filter: blur(6px) !important;
    -webkit-backdrop-filter: blur(6px) !important;
    z-index: 1060 !important;
}

.modal-animate-in {
    animation: modalPopIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes modalPopIn {
    0% {
        opacity: 0;
        transform: scale(0.95) translateY(12px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.audience-card {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.audience-card:hover {
    transform: translateY(-2px);
}
.audience-card.active {
    box-shadow: 0 4px 14px -2px rgba(59, 130, 246, 0.2);
}

/* Scrollbars & Utilities */
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
