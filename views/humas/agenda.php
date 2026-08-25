<?php
/**
 * SINTA SaaS - Halaman Agenda & Timeline Kegiatan Sekolah
 * Standardized Architecture: Vue 3 Dynamic SPA, Zero Data Leakage, PostgreSQL Multi-Schema & Enterprise UI/UX Standard
 */
$pageTitle = $title ?? 'Agenda & Timeline Kegiatan Sekolah';
?>

<div id="agendaApp" 
     data-is-super-admin="<?= htmlspecialchars($isSuperAdmin ? 'true' : 'false', ENT_QUOTES, 'UTF-8') ?>" 
     data-tenant-id="<?= htmlspecialchars((string)($selectedTenantId ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
     v-cloak>

    <!-- ═══════════════════════════════════════════════════════════════════════
         1. ROW HEADER & ACTION TOOLBAR (STANDAR ENTERPRISE UI/UX)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-blue-600 text-white rounded-2xl d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 48px; height: 48px;">
                <i class="bi bi-calendar2-range-fill fs-4"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold text-slate-900 fs-4 mb-0">Manajemen Agenda & Kalender</h3>
                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-1 fs-9 font-bold">
                        <i class="bi bi-calendar-event-fill text-blue-600 me-1"></i>Humas &amp; Informasi
                    </span>
                </div>
                <p class="text-slate-500 fs-8 mb-0 mt-0.5">Pusat penjadwalan terpadu kalender akademik, rapat kedinasan, asesmen ujian, dan event kesiswaan.</p>
            </div>
        </div>
        
        <!-- Action Toolbar Kanan -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button v-if="activeTab === 'kalender' || activeTab === 'daftar'" 
                    type="button" 
                    class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                    @click="openModalAgenda()">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Jadwalkan Agenda</span>
            </button>
            <button v-else-if="activeTab === 'kategori'" 
                    type="button" 
                    class="btn btn-sm btn-primary rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5" 
                    @click="openModalAgenda()">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Jadwalkan Agenda</span>
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

    <!-- ═══════════════════════════════════════════════════════════════════════
         2. COMPACT SCHOOL SELECTOR AUTO-FILTER BANNER (SUPER ADMIN)
         ═══════════════════════════════════════════════════════════════════════ -->
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
                <select id="sa-filter-sekolah-agenda" 
                        class="form-select form-select-sm bg-slate-50 border-slate-200 rounded-xl text-slate-800 fs-8 font-semibold shadow-2xs cursor-pointer focus:bg-white w-100" 
                        style="height: 38px;" 
                        v-model="filterTenantId" 
                        @change="onTenantChange()">
                    <option value="">-- Semua Sekolah / Tenant --</option>
                    <option value="global">🌐 Agenda Global (Pusat / Seluruh Sekolah)</option>
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

    <!-- ═══════════════════════════════════════════════════════════════════════
         3. KPI SUMMARY METRIC CARDS (REAL-TIME AGGREGATE)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Agenda -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-2xs h-100 d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">TOTAL AGENDA</div>
                    <div class="kpi-value">{{ stats.total_agenda || 0 }}</div>
                    <div class="text-slate-400 fs-9 mt-1"><i class="bi bi-calendar2-range me-1 text-blue-600"></i>Seluruh Kegiatan</div>
                </div>
                <div class="kpi-icon-box bg-blue-50 text-blue-600">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </div>

        <!-- Card 2: Agenda Aktif -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-2xs h-100 d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">AGENDA AKTIF</div>
                    <div class="kpi-value">{{ stats.total_aktif || 0 }}</div>
                    <div class="text-slate-400 fs-9 mt-1"><i class="bi bi-check-circle me-1 text-emerald-600"></i>Terjadwal Resmi</div>
                </div>
                <div class="kpi-icon-box bg-emerald-50 text-emerald-600">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
            </div>
        </div>

        <!-- Card 3: Kegiatan Bulan Ini -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-2xs h-100 d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">KEGIATAN BULAN INI</div>
                    <div class="kpi-value">{{ stats.total_bulan_ini || 0 }}</div>
                    <div class="text-slate-400 fs-9 mt-1"><i class="bi bi-calendar-month me-1 text-indigo-600"></i>Periode Berjalan</div>
                </div>
                <div class="kpi-icon-box bg-indigo-50 text-indigo-600">
                    <i class="bi bi-calendar-heart"></i>
                </div>
            </div>
        </div>

        <!-- Card 4: Kategori Topik -->
        <div class="col-6 col-lg-3">
            <div class="kpi-card shadow-2xs h-100 d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">KATEGORI KEGIATAN</div>
                    <div class="kpi-value">{{ stats.total_kategori || 0 }}</div>
                    <div class="text-slate-400 fs-9 mt-1"><i class="bi bi-tags me-1 text-amber-600"></i>Klasifikasi Bidang</div>
                </div>
                <div class="kpi-icon-box bg-amber-50 text-amber-600">
                    <i class="bi bi-bookmarks-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         4. HORIZONTAL NAVTABS (3-WAY SCROLLER ENGINE)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
        <div class="d-flex align-items-center position-relative">
            <!-- 1 Tombol Panah Kiri -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('agendaNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Container Deretan Tab -->
            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="agendaNavTabs" role="tablist">
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
                            <i class="bi bi-list-task me-2 fs-6"></i> Daftar Agenda &amp; Timeline
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

            <!-- 1 Tombol Panah Kanan -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('agendaNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         5. TAB 1: KALENDER INTERAKTIF (KOMPONEN ASLI 100% DIPERTAHANKAN)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'kalender'">
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
            </div><!-- /.custom-scrollbar -->
        </div><!-- /.bg-white card container Tab Kalender -->
    </div><!-- /v-show="kalender" -->

    <!-- ═══════════════════════════════════════════════════════════════════════
         6. TAB 2: DAFTAR AGENDA & TIMELINE (STANDAR ENTERPRISE TABLE)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'daftar'">
        
        <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 mb-4 animate-fade-in">
            
            <!-- Filter Lanjutan & Toolbar -->
            <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-3.5 p-md-4 mb-4 shadow-2xs">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-slate-200/60">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-funnel-fill text-blue-600 fs-7"></i>
                        <span class="fs-8 fw-bold text-slate-800 text-uppercase tracking-wider">Penyaringan &amp; Filter Agenda</span>
                    </div>
                    <button v-if="searchQuery || filterKategori || filterVisibilitas || filterMonth || filterStatus !== ''" 
                            type="button" 
                            @click="resetFilters()" 
                            class="btn btn-sm btn-link text-slate-500 hover:text-rose-600 p-0 fs-8 text-decoration-none d-flex align-items-center gap-1">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                    </button>
                </div>

                <div class="row g-3 align-items-end">
                    <!-- Filter 1: Kategori -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Kategori Kegiatan</label>
                        <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                                v-model="filterKategori" 
                                @change="fetchAgenda()"
                                style="height: 38px;">
                            <option value="">-- Semua Kategori --</option>
                            <option v-for="kat in kategoriList" :key="kat.nama_kategori" :value="kat.nama_kategori">{{ kat.nama_kategori }}</option>
                        </select>
                    </div>

                    <!-- Filter 2: Sasaran Audiens -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Sasaran Audiens</label>
                        <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                                v-model="filterVisibilitas" 
                                @change="fetchAgenda()"
                                style="height: 38px;">
                            <option value="">-- Semua Sasaran --</option>
                            <option value="public">🌐 Publik &amp; Warga Sekolah</option>
                            <option value="guru">👨‍🏫 Dewan Guru &amp; Tendik</option>
                            <option value="siswa">🎓 Peserta Didik (Siswa)</option>
                            <option value="private">🔒 Role Spesifik</option>
                        </select>
                    </div>

                    <!-- Filter 3: Periode Bulan -->
                    <div class="col-12 col-sm-6 col-lg-2">
                        <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Periode Bulan</label>
                        <input type="month" 
                               class="form-control form-control-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                               v-model="filterMonth" 
                               @change="fetchAgenda()"
                               style="height: 38px;">
                    </div>

                    <!-- Filter 4: Status -->
                    <div class="col-12 col-sm-6 col-lg-1">
                        <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Status</label>
                        <select class="form-select form-select-sm rounded-xl border-slate-200 bg-white fs-8 text-slate-800 font-medium py-2 shadow-2xs cursor-pointer" 
                                v-model="filterStatus" 
                                @change="fetchAgenda()"
                                style="height: 38px;">
                            <option value="">-- Semua --</option>
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>

                    <!-- Filter 5: Pencarian Universal -->
                    <div class="col-12 col-sm-12 col-lg-3">
                        <label class="form-label fs-9 font-bold text-slate-500 mb-1 text-uppercase tracking-wider">Pencarian Kegiatan</label>
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute top-50 translate-middle-y text-slate-400 ms-3" style="font-size: 0.85rem;"></i>
                            <input type="text" 
                                   class="form-control form-control-sm ps-5 pe-5 bg-white border-slate-200 rounded-xl text-slate-800 fs-8 font-medium shadow-2xs" 
                                   placeholder="Cari kegiatan, lokasi, panitia..." 
                                   v-model="searchQuery" 
                                   @input="debounceSearch()"
                                   style="height: 38px;">
                            <button v-if="searchQuery" type="button" class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y text-slate-400 hover:text-slate-600 text-decoration-none p-0 me-3" @click="searchQuery = ''; fetchAgenda()">
                                <i class="bi bi-x-circle-fill fs-7"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Table Architecture: Agenda -->
            <div class="table-responsive" style="margin-bottom: 1.25rem;">
                <table class="pengguna-table table table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">NO</th>
                            <th>KEGIATAN &amp; DETAIL ACARA</th>
                            <th class="text-center" style="width: 150px;">KATEGORI</th>
                            <th class="text-center" style="width: 160px;">TANGGAL &amp; WAKTU</th>
                            <th class="text-center" style="width: 120px;">AUDIENS</th>
                            <th class="text-center" style="width: 100px;">STATUS</th>
                            <th class="text-center" style="width: 110px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="loadingAgenda">
                            <td colspan="7" class="text-center py-5 text-slate-400">
                                <div class="spinner-border spinner-border-sm text-blue-600 me-2" role="status"></div>
                                <span class="font-semibold fs-8">Memuat daftar agenda...</span>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-else-if="filteredAgendaList.length === 0">
                            <td colspan="7" class="text-center py-5">
                                <div class="w-14 h-14 rounded-3xl bg-blue-50 text-blue-600 border border-blue-100/80 d-inline-flex align-items-center justify-content-center fs-2 mb-2 shadow-2xs">
                                    <i class="bi bi-calendar-x"></i>
                                </div>
                                <h6 class="fw-bold text-slate-800 fs-7 mb-1">Belum Ada Agenda Terjadwal</h6>
                                <p class="text-slate-400 fs-8 mb-3 max-w-md mx-auto">
                                    {{ searchQuery || filterKategori || filterVisibilitas || filterMonth || filterStatus !== '' ? 'Tidak ada agenda yang cocok dengan parameter filter pencarian Anda.' : 'Belum ada agenda kegiatan yang dijadwalkan.' }}
                                </p>
                                <button v-if="searchQuery || filterKategori || filterVisibilitas || filterMonth || filterStatus !== ''" 
                                        type="button" 
                                        class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-xl px-3 py-1.5 fs-8 font-semibold shadow-2xs hover:bg-slate-100" 
                                        @click="resetFilters()">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                                </button>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <tr v-else v-for="(item, index) in paginatedAgendaList" :key="item.id">
                            <!-- No -->
                            <td class="text-center font-bold text-slate-400 fs-8">
                                {{ (currentPage - 1) * perPage + index + 1 }}
                            </td>

                            <!-- Kegiatan & Detail -->
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <a href="javascript:void(0)" @click="previewAgenda(item)" class="fw-bold text-slate-800 fs-8 hover:text-blue-600 transition text-decoration-none" style="line-height: 1.35;">
                                            {{ item.judul }}
                                        </a>
                                        <span v-if="!item.tenant_id" class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold px-2 py-0.5 rounded-pill">
                                            <i class="bi bi-globe me-0.5"></i> Global
                                        </span>
                                        <span v-else-if="item.nama_sekolah" class="badge bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-medium px-2 py-0.5 rounded-pill">
                                            <i class="bi bi-building me-0.5"></i> {{ item.nama_sekolah }}
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center gap-3 fs-9 text-slate-500 font-medium flex-wrap mt-0.5">
                                        <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                                            <i class="bi bi-geo-alt-fill text-rose-500"></i> {{ item.lokasi }}
                                        </span>
                                        <span class="text-slate-300">•</span>
                                        <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                                            <i class="bi bi-person-badge text-indigo-600"></i> PJ: {{ item.penanggung_jawab }}
                                        </span>
                                    </div>
                                    
                                    <p class="fs-9 text-slate-500 mb-0 text-truncate" style="max-width: 460px; line-height: 1.5;">
                                        {{ item.deskripsi || '— Tidak ada keterangan tambahan —' }}
                                    </p>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="text-center">
                                <span class="badge px-3 py-1.5 rounded-lg fs-9 font-bold border d-inline-flex align-items-center gap-1.5 shadow-2xs" :style="getEventBadgeStyle(item.kategori)">
                                    <i class="bi bi-tag-fill"></i> {{ item.kategori || 'Umum' }}
                                </span>
                            </td>

                            <!-- Tanggal & Waktu -->
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-0.5">
                                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-1 rounded-lg fs-9 font-bold shadow-2xs">
                                        <i class="bi bi-calendar-event me-1 text-blue-600"></i> {{ formatDateRange(item.tanggal_mulai, item.tanggal_selesai) }}
                                    </span>
                                    <span class="fs-9 text-slate-400 font-semibold mt-0.5">
                                        <i class="bi bi-clock me-1"></i> {{ item.waktu_mulai }} - {{ item.waktu_selesai }}
                                    </span>
                                </div>
                            </td>

                            <!-- Audiens -->
                            <td class="text-center">
                                <span class="badge px-2.5 py-1.5 rounded-lg fs-9 font-bold border d-inline-flex align-items-center gap-1.5 shadow-2xs" :class="getVisibilitasBadgeClass(item.visibilitas)">
                                    <i class="bi" :class="getVisibilitasIcon(item.visibilitas)"></i>
                                    {{ getVisibilitasLabel(item.visibilitas) }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="text-center">
                                <button type="button" class="btn btn-sm rounded-pill px-2.5 py-1 fs-9 font-bold border shadow-2xs transition d-inline-flex align-items-center gap-1"
                                        :class="item.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'"
                                        @click="toggleStatusAgenda(item)" title="Beralih Status">
                                    <i class="bi" :class="item.is_active ? 'bi-check-circle-fill text-emerald-600' : 'bi-dash-circle text-slate-400'"></i>
                                    {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </button>
                            </td>

                            <!-- Aksi -->
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-1">
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

            <!-- Bottom Pagination Toolbar Standard -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 border-top border-slate-100 mt-2 pt-4">
                <div class="d-flex align-items-center gap-2 text-slate-600 fs-8">
                    <span>Menampilkan <strong>{{ (currentPage - 1) * perPage + 1 }}</strong> s.d. <strong>{{ Math.min(currentPage * perPage, filteredAgendaList.length) }}</strong> dari <strong>{{ filteredAgendaList.length }}</strong> agenda</span>
                    <div class="d-flex align-items-center gap-1 ms-2">
                        <select class="form-select form-select-sm perpage-select shadow-2xs" v-model="perPage" @change="currentPage = 1">
                            <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                        <span class="fs-9 text-slate-400">/ hal</span>
                    </div>
                </div>

                <nav v-if="totalPages > 1" aria-label="Navigasi Halaman Agenda">
                    <ul class="pagination pagination-modern mb-0">
                        <li class="page-item" :class="{disabled: currentPage === 1}">
                            <button class="page-link" @click.prevent="currentPage = 1" :disabled="currentPage === 1">&laquo;</button>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === 1}">
                            <button class="page-link" @click.prevent="currentPage--" :disabled="currentPage === 1">&lsaquo;</button>
                        </li>
                        <li class="page-item" v-for="page in displayedPages" :key="page" :class="{active: page === currentPage, disabled: page === '...'}">
                            <button v-if="page !== '...'" class="page-link" @click.prevent="currentPage = page">{{ page }}</button>
                            <span v-else class="page-link border-0 text-slate-400">...</span>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === totalPages}">
                            <button class="page-link" @click.prevent="currentPage++" :disabled="currentPage === totalPages">&rsaquo;</button>
                        </li>
                        <li class="page-item" :class="{disabled: currentPage === totalPages}">
                            <button class="page-link" @click.prevent="currentPage = totalPages" :disabled="currentPage === totalPages">&raquo;</button>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         7. TAB 3: KATEGORI KEGIATAN (STANDAR ENTERPRISE CARDS)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'kategori'">
        <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 mb-4 animate-fade-in">
            <div class="row g-3">
                <div v-for="kat in kategoriList" :key="kat.nama_kategori" class="col-md-6 col-lg-4">
                    <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200/80 shadow-2xs h-100 d-flex flex-column justify-between transition hover:-translate-y-0.5">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge px-3 py-1.5 rounded-lg fs-8 font-bold border shadow-2xs" :style="getEventBadgeStyle(kat.nama_kategori)">
                                    <i class="bi bi-tag-fill me-1"></i>{{ kat.nama_kategori }}
                                </span>
                                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 font-bold fs-9 rounded-pill px-2.5 py-1">
                                    {{ kat.total_agenda }} Agenda
                                </span>
                            </div>
                            <p class="fs-8 text-slate-500 mb-3">
                                Klasifikasi terpadu untuk pengelompokan kalender kegiatan akademik dan non-akademik sekolah.
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm btn-white border border-slate-200 text-blue-600 hover:bg-blue-50 font-bold fs-8 rounded-xl w-100 d-flex align-items-center justify-content-center gap-1.5 transition shadow-2xs" @click="filterByKategoriAndSwitch(kat.nama_kategori)">
                            <span>Lihat Agenda Terkait</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         8. MODAL 1: TAMBAH / EDIT AGENDA (MODERN EXECUTIVE POPUP)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-flex': modalAgenda.show}" tabindex="-1" v-if="modalAgenda.show">
        <div class="modal-dialog modal-dialog-centered modal-lg my-auto" style="width: 100%; max-width: 820px; max-height: 90vh;">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in d-flex flex-column" style="max-height: 90vh;">
                
                <!-- Modal Header Sticky -->
                <div class="modal-header sticky-top bg-white px-4 px-md-5 py-3.5 border-b border-slate-100 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 d-flex align-items-center justify-content-center fs-5 shadow-2xs flex-shrink-0">
                            <i class="bi" :class="modalAgenda.isEdit ? 'bi-pencil-square' : 'bi-calendar-plus-fill'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-slate-900 fs-5 mb-0">
                                {{ modalAgenda.isEdit ? 'Edit Agenda Kegiatan' : 'Jadwalkan Agenda Baru' }}
                            </h5>
                            <span class="text-slate-400 fs-9">Atur jadwal kalender pendidikan, rapat, asesmen, dan event sekolah</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 p-2 border-0 transition" @click="modalAgenda.show = false" title="Tutup">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>

                <!-- Modal Body Scrollable -->
                <form @submit.prevent="submitAgenda()" class="d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 0;">
                    <div class="modal-body p-4 p-md-5 text-slate-700 fs-8 bg-slate-50/40 overflow-y-auto flex-grow-1 custom-scrollbar" style="max-height: calc(90vh - 140px);">
                        <div class="row g-3 g-md-4">
                            
                            <!-- Nama Kegiatan -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-slate-800 mb-1.5 d-flex align-items-center justify-content-between">
                                    <span>Nama Kegiatan / Agenda <span class="text-rose-500">*</span></span>
                                    <span class="fs-9 text-slate-400 font-normal">Buat nama agenda yang ringkas dan jelas</span>
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                        <i class="bi bi-calendar-event fs-6"></i>
                                    </span>
                                    <input type="text" v-model="modalAgenda.form.nama_agenda_sekolah" required 
                                           placeholder="Contoh: Rapat Pleno Dewan Guru / Ujian Sumatif Akhir Semester" 
                                           class="form-control fs-8 font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                                </div>
                            </div>

                            <!-- Kategori Kegiatan -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-slate-800 mb-1.5">
                                    Kategori Kegiatan <span class="text-rose-500">*</span>
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                        <i class="bi bi-tags-fill text-indigo-500"></i>
                                    </span>
                                    <select v-model="modalAgenda.form.kategori" required 
                                            class="form-select fs-8 font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white cursor-pointer focus:ring-2 focus:ring-blue-500 transition">
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
                                <label class="form-label fw-bold text-slate-800 mb-1.5">
                                    Lingkup Sekolah / Tenant <span class="text-rose-500">*</span>
                                </label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400">
                                        <i class="bi bi-building text-blue-500"></i>
                                    </span>
                                    <select v-model="modalAgenda.form.tenant_id" class="form-select fs-8 font-semibold rounded-2xl ps-5 pe-3 py-2.5 border-slate-200 shadow-2xs bg-white cursor-pointer focus:ring-2 focus:ring-blue-500 transition">
                                        <option value="global">🌐 Agenda Global (Seluruh Sekolah/Tenant)</option>
                                        <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Interactive Audience Selection Grid (Modern Visual Segmented Cards) -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-slate-800 mb-2 d-flex align-items-center justify-content-between">
                                    <span>Sasaran Audiens (Target Peserta) <span class="text-rose-500">*</span></span>
                                    <span class="badge bg-slate-100 text-slate-600 font-medium px-2 py-0.5 rounded-pill fs-9">
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
                                            <span class="fw-bold fs-8">Semua Warga</span>
                                            <small class="fs-9 text-slate-400 mt-0.5">Publik &amp; Tamu</small>
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
                                            <span class="fw-bold fs-8">Dewan Guru</span>
                                            <small class="fs-9 text-slate-400 mt-0.5">Guru &amp; Tendik</small>
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
                                            <span class="fw-bold fs-8">Peserta Didik</span>
                                            <small class="fs-9 text-slate-400 mt-0.5">Khusus Siswa</small>
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
                                            <span class="fw-bold fs-8">Role Spesifik</span>
                                            <small class="fs-9 text-slate-400 mt-0.5">Kustom Group</small>
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
                                        <span class="fw-bold text-rose-900 fs-8">Pilih Role Khusus Penerima Agenda:</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <label v-for="r in rolesList" :key="r.id" class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-xl border bg-white cursor-pointer transition fs-8 font-semibold shadow-2xs"
                                               :class="modalAgenda.form.target_roles.includes(r.nama_role) ? 'border-rose-500 text-rose-700 bg-rose-50/60' : 'border-slate-200 text-slate-600'">
                                            <input class="form-check-input text-rose-600 cursor-pointer m-0" type="checkbox" :value="r.nama_role" v-model="modalAgenda.form.target_roles">
                                            <span>{{ r.nama_role }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Tanggal Mulai & Selesai -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-slate-800 mb-1.5">
                                    Tanggal Mulai <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" v-model="modalAgenda.form.tanggal_mulai" @change="onStartDateChange()" required 
                                       class="form-control fs-8 font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-slate-800 mb-1.5">
                                    Tanggal Selesai <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" v-model="modalAgenda.form.tanggal_selesai" required 
                                       class="form-control fs-8 font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <!-- Waktu Mulai & Selesai -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-slate-800 mb-1.5">
                                    Waktu Mulai
                                </label>
                                <input type="time" v-model="modalAgenda.form.waktu_mulai" 
                                       class="form-control fs-8 font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-slate-800 mb-1.5">
                                    Waktu Selesai
                                </label>
                                <input type="time" v-model="modalAgenda.form.waktu_selesai" 
                                       class="form-control fs-8 font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            <!-- Lokasi & Quick Chips -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-slate-800 mb-1.5">
                                    Lokasi Pelaksanaan <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" v-model="modalAgenda.form.lokasi" required placeholder="Contoh: Lapangan Utama / Aula" 
                                       class="form-control fs-8 font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition mb-1.5">
                                <div class="d-flex flex-wrap gap-1">
                                    <span v-for="loc in ['Aula Utama', 'Lapangan Sekolah', 'Ruang Rapat', 'GOR Olahraga', 'Lab Komputer']" :key="loc"
                                          @click="modalAgenda.form.lokasi = loc" 
                                          class="badge bg-white text-slate-600 border border-slate-200 rounded-pill px-2 py-0.5 cursor-pointer hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition fs-9 shadow-2xs">
                                        {{ loc }}
                                    </span>
                                </div>
                            </div>

                            <!-- Penanggung Jawab & Quick Chips -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-slate-800 mb-1.5">
                                    Penanggung Jawab / Panitia <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" v-model="modalAgenda.form.penanggung_jawab" required placeholder="Contoh: Waka Kesiswaan / Panitia Ujian" 
                                       class="form-control fs-8 font-semibold rounded-2xl py-2.5 border-slate-200 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 transition mb-1.5">
                                <div class="d-flex flex-wrap gap-1">
                                    <span v-for="pj in ['Waka Kesiswaan', 'Waka Kurikulum', 'Waka Humas', 'Pengurus OSIS', 'Guru BK']" :key="pj"
                                          @click="modalAgenda.form.penanggung_jawab = pj" 
                                          class="badge bg-white text-slate-600 border border-slate-200 rounded-pill px-2 py-0.5 cursor-pointer hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition fs-9 shadow-2xs">
                                        {{ pj }}
                                    </span>
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-slate-800 mb-1.5">
                                    Deskripsi / Petunjuk Kegiatan
                                </label>
                                <textarea v-model="modalAgenda.form.deskripsi" rows="4" 
                                          placeholder="Tuliskan keterangan detail pakaian, susunan acara, atau instruksi peserta di sini..." 
                                          class="form-control fs-8 rounded-2xl border-slate-200 p-3.5 shadow-2xs bg-white focus:ring-2 focus:ring-blue-500 font-normal leading-relaxed"></textarea>
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
                                            <span class="fw-bold text-slate-800 fs-8 block">Status Penjadwalan</span>
                                            <span class="text-slate-400 fs-9">
                                                {{ modalAgenda.form.is_active ? 'Agenda aktif akan otomatis tampil pada kalender dan beranda portal warga sekolah.' : 'Agenda disimpan sebagai draft tertunda.' }}
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

                    <!-- Modal Footer Sticky -->
                    <div class="modal-footer sticky-bottom bg-slate-50 px-4 px-md-5 py-3 border-top border-slate-100 d-flex align-items-center justify-content-between">
                        <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl font-bold px-4 py-2 fs-8 shadow-2xs transition" @click="modalAgenda.show = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-xl font-bold px-5 py-2 fs-8 shadow-sm d-flex align-items-center gap-2 hover:shadow transition" :disabled="modalAgenda.saving">
                            <span v-if="modalAgenda.saving" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-calendar-check fs-8"></i>
                            <span>{{ modalAgenda.saving ? 'Menyimpan...' : (modalAgenda.isEdit ? 'Perbarui Agenda' : 'Jadwalkan Sekarang') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         9. MODAL 2: DETAIL / PRATINJAU AGENDA (EXECUTIVE READER)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-flex': modalPreview.show}" tabindex="-1" v-if="modalPreview.show">
        <div class="modal-dialog modal-dialog-centered modal-lg my-auto" style="width: 100%; max-width: 820px; max-height: 90vh;">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in d-flex flex-column" style="max-height: 90vh;">
                <div class="modal-header sticky-top bg-white px-4 px-md-5 py-3.5 border-b border-slate-100 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 d-flex align-items-center justify-content-center fs-5 shadow-2xs flex-shrink-0">
                            <i class="bi bi-calendar2-week-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-slate-900 fs-5 mb-0">Rincian Agenda Kegiatan</h5>
                            <span class="text-slate-400 fs-9">Pratinjau jadwal terpadu kalender sekolah</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 p-2 border-0 transition" @click="modalPreview.show = false" title="Tutup">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>

                <div class="modal-body p-4 p-md-5 text-slate-700 fs-8 bg-slate-50/50 overflow-y-auto flex-grow-1 custom-scrollbar" style="max-height: calc(90vh - 140px);">
                    <div v-if="modalPreview.item" class="d-flex flex-column gap-4">
                        
                        <!-- Header Details -->
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span class="badge px-3 py-1.5 rounded-xl fs-8 font-bold border shadow-2xs d-inline-flex align-items-center gap-1.5" :style="getEventBadgeStyle(modalPreview.item.kategori)">
                                    <i class="bi bi-tag-fill"></i> {{ modalPreview.item.kategori || 'Umum' }}
                                </span>
                                <span class="badge px-3 py-1.5 rounded-xl fs-8 font-bold border shadow-2xs d-inline-flex align-items-center gap-1.5" :class="getVisibilitasBadgeClass(modalPreview.item.visibilitas)">
                                    <i class="bi" :class="getVisibilitasIcon(modalPreview.item.visibilitas)"></i>
                                    {{ getVisibilitasLabel(modalPreview.item.visibilitas) }}
                                </span>
                                <span v-if="modalPreview.item.is_active" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 fs-8 font-bold px-3 py-1.5 rounded-xl shadow-2xs d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-check-circle-fill text-emerald-600"></i> Aktif
                                </span>
                            </div>

                            <h3 class="fw-bold text-slate-900 fs-4 mb-2" style="line-height: 1.35;">
                                {{ modalPreview.item.judul }}
                            </h3>

                            <!-- Key Metrics Grid -->
                            <div class="row g-2 mt-1">
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-slate-400 fs-9 block font-semibold">Tanggal Pelaksanaan:</span>
                                        <span class="fs-8 fw-bold text-slate-800 d-inline-flex align-items-center gap-1.5 mt-0.5">
                                            <i class="bi bi-calendar-event text-blue-600"></i> {{ formatDateRange(modalPreview.item.tanggal_mulai, modalPreview.item.tanggal_selesai) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-slate-400 fs-9 block font-semibold">Waktu / Jam Acara:</span>
                                        <span class="fs-8 fw-bold text-slate-800 d-inline-flex align-items-center gap-1.5 mt-0.5">
                                            <i class="bi bi-clock-fill text-indigo-600"></i> {{ modalPreview.item.waktu_mulai }} - {{ modalPreview.item.waktu_selesai }} WIB
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-slate-400 fs-9 block font-semibold">Lokasi / Tempat:</span>
                                        <span class="fs-8 fw-bold text-slate-800 d-inline-flex align-items-center gap-1.5 mt-0.5">
                                            <i class="bi bi-geo-alt-fill text-rose-500"></i> {{ modalPreview.item.lokasi }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-slate-400 fs-9 block font-semibold">Penanggung Jawab / Panitia:</span>
                                        <span class="fs-8 fw-bold text-slate-800 d-inline-flex align-items-center gap-1.5 mt-0.5">
                                            <i class="bi bi-person-badge-fill text-amber-600"></i> {{ modalPreview.item.penanggung_jawab }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description Body -->
                        <div>
                            <span class="text-slate-400 fs-8 fw-bold block mb-1.5">Rincian / Petunjuk Kegiatan:</span>
                            <div class="bg-white p-4 p-md-5 rounded-3xl border border-slate-200/80 shadow-xs text-slate-800 fs-8 font-normal" style="line-height: 1.8; white-space: pre-wrap;">
{{ modalPreview.item.deskripsi || '— Tidak ada keterangan tambahan —' }}
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer sticky-bottom bg-slate-50 px-4 px-md-5 py-3 border-top border-slate-100 d-flex align-items-center justify-content-between">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl font-bold px-4 py-2 fs-8 shadow-2xs" @click="modalPreview.show = false">Tutup</button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-xl font-bold px-3 py-2 fs-8 shadow-2xs" @click="deleteAgenda(modalPreview.item); modalPreview.show = false">
                            <i class="bi bi-trash3 me-1"></i> Hapus
                        </button>
                        <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 fs-8 shadow-sm d-flex align-items-center gap-1.5 hover:shadow transition" @click="modalPreview.show = false; editAgenda(modalPreview.item)">
                            <i class="bi bi-pencil-square"></i> Edit Agenda Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         10. MODAL 3: DAFTAR KEGIATAN HARIAN (DAY EVENTS POPUP)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade custom-modal-backdrop" :class="{'show d-flex': modalDay.show}" tabindex="-1" v-if="modalDay.show">
        <div class="modal-dialog modal-dialog-centered modal-md my-auto" style="width: 100%; max-width: 540px; max-height: 90vh;">
            <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden modal-animate-in d-flex flex-column" style="max-height: 90vh;">
                <div class="modal-header sticky-top bg-white px-4 px-md-5 py-3.5 border-b border-slate-100 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 d-flex align-items-center justify-content-center fs-5 shadow-2xs">
                            <i class="bi bi-calendar-date-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-slate-900 fs-5 mb-0">Agenda {{ modalDay.formattedDate }}</h5>
                            <span class="text-slate-400 fs-9">{{ modalDay.events.length }} kegiatan terjadwal</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 p-2 border-0 transition" @click="modalDay.show = false" title="Tutup">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>

                <div class="modal-body p-4 p-md-5 text-slate-700 fs-8 bg-slate-50/50 overflow-y-auto flex-grow-1 custom-scrollbar" style="max-height: calc(90vh - 140px);">
                    <div class="d-flex flex-column gap-2.5">
                        <div v-for="ev in modalDay.events" :key="ev.id"
                             class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-2xs transition hover:bg-slate-50 d-flex align-items-center justify-content-between gap-2">
                            <div class="d-flex flex-column gap-0.5 overflow-hidden">
                                <span class="fw-bold text-slate-900 fs-8 truncate">{{ ev.judul }}</span>
                                <div class="d-flex align-items-center gap-2 fs-9 text-slate-500">
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

                <div class="modal-footer sticky-bottom bg-slate-50 px-4 px-md-5 py-3 border-top border-slate-100 d-flex align-items-center justify-content-between">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 rounded-xl font-bold px-4 py-2 fs-8 shadow-2xs" @click="modalDay.show = false">Tutup</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 fs-8 shadow-sm d-flex align-items-center gap-1.5 hover:shadow transition" @click="modalDay.show = false; openModalAgendaWithDate(modalDay.dateStr)">
                        <i class="bi bi-plus-lg"></i> Tambah di Hari Ini
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     11. VUE 3 CONTROLLER SETUP (DYNAMIC FETCH & ZERO DATA LEAKAGE)
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
{
    const { ref, computed, onMounted, watch } = Vue;

    window.VueAppRegistry.register('#agendaApp', {
        setup() {
            // Global State
            const rootEl = document.getElementById('agendaApp');
            const _baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '<?= htmlspecialchars($this->getBaseUrl(), ENT_QUOTES, 'UTF-8') ?>';
            const isSuperAdmin = ref(rootEl?.dataset?.isSuperAdmin === 'true');
            const tenants = ref([]);
            const currentTenantId = ref(rootEl?.dataset?.tenantId || null);

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
            const urlParams = new URLSearchParams(window.location.search);
            const urlTenantId = urlParams.get('tenant_id');
            const initialTenant = (urlTenantId !== null && urlTenantId !== '')
                ? urlTenantId
                : (currentTenantId.value && currentTenantId.value !== 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' ? currentTenantId.value : '');

            const filterTenantId = ref(initialTenant || '');
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

            const getSelectedTenantLabel = () => {
                if (!filterTenantId.value) return 'Pusat Kendali SaaS (Global)';
                if (filterTenantId.value === 'global') return '🌐 Agenda Global (Pusat)';
                const found = tenants.value.find(t => t.id === filterTenantId.value);
                return found ? found.nama_sekolah : 'Sekolah Terpilih';
            };

            // ─── API DATA FETCHERS ──────────────────────────────────
            const fetchOptionsAndStats = async () => {
                try {
                    let url = `${_baseUrl}/api/v1/agenda/options`;
                    if (filterTenantId.value) {
                        url += `?tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    }
                    const res = await axios.get(url);
                    if (res.data && res.data.success) {
                        if (res.data.data.tenants && res.data.data.tenants.length > 0) {
                            tenants.value = res.data.data.tenants;
                        }
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
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage.value = 1;
                    fetchAgenda();
                }, 300);
            };

            const resetFilters = () => {
                searchQuery.value = '';
                filterKategori.value = '';
                filterVisibilitas.value = '';
                filterMonth.value = '';
                filterStatus.value = '';
                currentPage.value = 1;
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

                // Next month padding days to fill grid cells
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
                getSelectedTenantLabel,
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

/* KPI Summary Metric Cards */
.kpi-card {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 1rem;
    padding: 1rem 1.25rem;
    transition: all 0.2s ease;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
}
.kpi-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.kpi-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin-top: 0.15rem;
}
.kpi-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

/* Modern Table Architecture */
.pengguna-table {
    border-collapse: separate;
    border-spacing: 0;
}
.pengguna-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.85rem 1rem;
    border-top: none;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.pengguna-table tbody td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.pengguna-table tbody tr:hover {
    background-color: rgba(248, 250, 252, 0.7);
}

/* Custom Scrollbars */
.table-responsive::-webkit-scrollbar,
.custom-scrollbar::-webkit-scrollbar {
    height: 6px;
    width: 6px;
}
.table-responsive::-webkit-scrollbar-track,
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 9999px;
}
.table-responsive::-webkit-scrollbar-thumb,
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
    transition: background 0.2s ease;
}
.table-responsive::-webkit-scrollbar-thumb:hover,
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Modern Pagination */
.pagination-modern {
    display: flex;
    gap: 0.25rem;
}
.pagination-modern .page-link {
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.35rem 0.65rem;
    background: #ffffff;
    transition: all 0.15s ease;
}
.pagination-modern .page-link:hover {
    background: #f1f5f9;
    color: #2563eb;
    border-color: #cbd5e1;
}
.pagination-modern .page-item.active .page-link {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.25);
}
.perpage-select {
    width: 76px !important;
    height: 32px !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    border-radius: 0.65rem !important;
    border-color: #e2e8f0 !important;
    padding: 0.25rem 0.5rem !important;
}

/* Modern Calendar Explicit CSS Grid (100% Intact) */
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
