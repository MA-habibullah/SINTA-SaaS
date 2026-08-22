<?php
/**
 * Views: Manajemen Ekstrakurikuler Kesiswaan (Vue 3 Single Page Experience - Clean Modern SaaS)
 * Path: views/kesiswaan/kesiswaan_ekskul.php
 */
?>
<div id="ekskulApp" class="container-fluid px-3 px-md-4 py-4" v-cloak>

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
                                <i class="bi bi-trophy-fill text-amber-300"></i> Modul Kesiswaan & Bakat Minat
                            </span>
                            <span v-if="selectedTaName" class="badge px-3 py-1.5 rounded-pill text-xs font-semibold d-inline-flex align-items-center gap-1.5" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
                                <i class="bi bi-calendar3"></i> TA: {{ selectedTaName }} (Semester {{ filterSemester }})
                            </span>
                        </div>
                        <h2 class="h3 font-bold text-white mb-1 tracking-tight">Manajemen Ekstrakurikuler</h2>
                        <p class="text-white/85 text-xs mb-0" style="max-width: 680px; line-height: 1.6;">
                            Kelola pendaftaran ekstrakurikuler, penugasan guru pembina, rekrutmen anggota siswa, agenda jurnal kegiatan, serta penginputan nilai dan deskripsi rapor siswa terpadu.
                        </p>
                    </div>

                    <!-- Super Admin Tenant Filter -->
                    <div v-if="isSuperAdmin && tenants.length > 0" class="d-flex align-items-center gap-2 bg-white/15 p-2 rounded-xl border border-white/25 shadow-xs" style="backdrop-filter: blur(6px);">
                        <i class="bi bi-building text-white fs-6 ms-1.5"></i>
                        <select v-model="currentTenantId" @change="onTenantChange()" class="form-select form-select-sm border-0 text-xs font-semibold bg-white text-slate-800 rounded-lg shadow-2xs" style="min-width: 220px;">
                            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Ekskul Aktif</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_ekskul || 0 }}</span>
                    <span class="text-[11px] text-emerald-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-check-circle-fill"></i> Terdaftar di sistem
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-blue-100">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Guru Pembina</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_pembina || 0 }}</span>
                    <span class="text-[11px] text-indigo-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-person-badge-fill"></i> Pembimbing aktif
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-indigo-100">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Total Anggota</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_anggota || 0 }}</span>
                    <span class="text-[11px] text-teal-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-person-check-fill"></i> Siswa berpartisipasi
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-teal-50 text-teal-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-teal-100">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="bg-white p-3.5 p-md-4 rounded-2xl border border-slate-200/80 shadow-xs h-100 d-flex align-items-center justify-content-between transition hover:-translate-y-0.5">
                <div>
                    <span class="text-slate-400 text-xs font-semibold block">Jurnal Kegiatan</span>
                    <span class="text-2xl font-black text-slate-800 block mt-0.5">{{ stats.total_jurnal || 0 }}</span>
                    <span class="text-[11px] text-amber-600 font-medium d-inline-flex align-items-center gap-1 mt-0.5">
                        <i class="bi bi-journal-check"></i> Agenda terlaksana
                    </span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0 border border-amber-100">
                    <i class="bi bi-journal-text"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         2. LAYOUT & HEADER NAVIGASI (STANDAR BK KEDISIPLINAN - PURE SAAS PILLS)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4">
        <div class="nav-tabs-wrapper d-flex align-items-center justify-content-between">
            <!-- 5 Nav Tabs in Clean Row with Horizontal Scroll on Mobile -->
            <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 mb-0">
                <li class="nav-item">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                            :class="{'active': activeTab === 'master'}"
                            @click="switchTab('master')">
                        <i class="bi bi-diagram-3 me-2 fs-6"></i> Master Ekskul
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                            :class="{'active': activeTab === 'pembina'}"
                            @click="switchTab('pembina')">
                        <i class="bi bi-person-badge me-2 fs-6"></i> Pembina Ekskul
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                            :class="{'active': activeTab === 'anggota'}"
                            @click="switchTab('anggota')">
                        <i class="bi bi-people me-2 fs-6"></i> Kelola Anggota
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                            :class="{'active': activeTab === 'jurnal'}"
                            @click="switchTab('jurnal')">
                        <i class="bi bi-journal-text me-2 fs-6"></i> Jurnal Kegiatan
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                            :class="{'active': activeTab === 'nilai'}"
                            @click="switchTab('nilai')">
                        <i class="bi bi-award me-2 fs-6"></i> Penilaian e-Rapor
                    </button>
                </li>
            </ul>

            <!-- Segarkan Data Button -->
            <div class="d-none d-md-flex align-items-center pe-1">
                <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl px-3 py-2 text-xs font-bold shadow-2xs d-flex align-items-center gap-1.5" @click="refreshAll()" title="Segarkan Data">
                    <i class="bi bi-arrow-clockwise" :class="{'spin': loading}"></i>
                    <span>Segarkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         3. TAB 1: MASTER EKSTRAKURIKULER (CLEAN SAAS TABLE - NO HORIZONTAL SCROLL)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'master'">
        
        <!-- 1. Dedicated Modern Toolbar Filter Card (Single-Line Horizontal Scrollable SaaS Toolbar) -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2.5 mb-3 overflow-hidden">
            <div class="d-flex align-items-center justify-content-between gap-3 overflow-x-auto flex-nowrap custom-scrollbar py-1" style="scrollbar-width: thin; -webkit-overflow-scrolling: touch;">
                
                <!-- Left: Academic Pill + Kategori + Live Search -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <!-- Academic Selector Pill (TA & Semester) -->
                    <div class="d-inline-flex align-items-center bg-slate-50/90 p-1 rounded-xl border border-slate-200 shadow-2xs gap-1 flex-shrink-0">
                        <div class="d-flex align-items-center px-2 py-0.5 text-xs font-semibold text-slate-700">
                            <i class="bi bi-calendar3 text-blue-600 me-1.5 fs-7"></i>
                            <span class="text-slate-400 me-1 font-medium">TA:</span>
                            <select id="filterTaEkskul" name="filter_ta" v-model="filterTahunAjaranId" @change="onAcademicFilterChange()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold py-0 ps-0 pe-4 text-slate-800 cursor-pointer shadow-none" style="width: auto;" aria-label="Filter Tahun Ajaran">
                                <option v-for="ta in listTahunAjaran" :key="ta.id" :value="ta.id">{{ ta.nama_tahun_ajaran }}</option>
                            </select>
                        </div>
                        <div class="vr bg-slate-200 opacity-60" style="height: 16px;"></div>
                        <div class="d-flex align-items-center px-2 py-0.5 text-xs font-semibold text-slate-700">
                            <span class="text-slate-400 me-1 font-medium">Smst:</span>
                            <select id="filterSemesterEkskul" name="filter_semester" v-model="filterSemester" @change="onAcademicFilterChange()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold py-0 ps-0 pe-4 text-slate-800 cursor-pointer shadow-none" style="width: auto;" aria-label="Filter Semester">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filter Kategori Dropdown -->
                    <select id="filterKategoriSelect" name="filter_kategori" v-model="filterKategoriEkskul" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 ps-3 pe-7 cursor-pointer hover:border-blue-300 transition flex-shrink-0" style="width: auto; min-width: 175px;" aria-label="Filter Kategori">
                        <option value="">Semua Kategori ({{ ekskulList.length }})</option>
                        <option value="Wajib">🛡️ Wajib</option>
                        <option value="Pilihan">⭐ Pilihan</option>
                        <option value="Olahraga">🏆 Olahraga</option>
                        <option value="Seni & Budaya">🎨 Seni & Budaya</option>
                        <option value="Keagamaan">🕌 Keagamaan</option>
                        <option value="Akademik / Sains">💡 Akademik / Sains</option>
                        <option value="Kepanduan & Bela Negara">🧭 Kepanduan & Bela Negara</option>
                        <option value="Teknologi & Robotik">🤖 Teknologi & Robotik</option>
                    </select>

                    <!-- Search Input -->
                    <div class="position-relative flex-shrink-0" style="width: 220px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 text-xs pointer-events-none"></i>
                        <input type="text" id="searchEkskulInput" name="search_ekskul" v-model="searchEkskul" class="form-control form-control-sm text-xs rounded-xl ps-4 pe-4 py-1.5 border-slate-200 shadow-2xs bg-white text-slate-800 font-medium focus:ring-2 focus:ring-blue-500 hover:border-blue-300 transition" placeholder="Cari nama ekskul..." aria-label="Cari nama ekskul">
                        <button v-if="searchEkskul" @click="searchEkskul = ''" class="btn btn-sm p-0 position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 border-0 bg-transparent" aria-label="Hapus kata kunci">
                            <i class="bi bi-x-circle-fill text-xs"></i>
                        </button>
                    </div>

                    <!-- Reset Filter Button -->
                    <button v-if="searchEkskul || filterKategoriEkskul" @click="searchEkskul = ''; filterKategoriEkskul = ''" class="btn btn-sm btn-light border border-slate-200 text-rose-600 rounded-xl px-2.5 py-1.5 text-xs font-bold hover:bg-rose-50 shadow-2xs d-inline-flex align-items-center gap-1 flex-shrink-0" title="Reset Filter">
                        <i class="bi bi-x-lg"></i> Reset
                    </button>
                </div>

                <!-- Right: Counter Badge & Action Button -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold px-3 py-2 rounded-xl d-inline-flex align-items-center gap-1.5 shadow-2xs flex-shrink-0">
                        <i class="bi bi-grid-fill text-blue-600"></i>
                        <span>{{ filteredEkskulList.length }} Ekskul</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs d-inline-flex align-items-center gap-1.5 shadow-sm hover:shadow transition bg-blue-600 border-0 flex-shrink-0 text-nowrap" @click="openModalEkskul()">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Tambah Ekskul Baru</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Main Table Card Container -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden mb-4">
            <div class="table-responsive custom-scrollbar">
                <table class="table table-hover align-middle mb-0" style="min-width: 960px;">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="ps-4 py-3.5">NAMA EKSTRAKURIKULER</th>
                            <th class="py-3.5">GURU PEMBINA</th>
                            <th class="py-3.5">JADWAL & LOKASI</th>
                            <th class="py-3.5 text-center" style="width: 120px;">ANGGOTA AKTIF</th>
                            <th class="py-3.5 text-center" style="width: 100px;">STATUS</th>
                            <th class="py-3.5 text-center pe-4" style="width: 120px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loadingEkskul">
                            <td colspan="6" class="text-center py-5 text-slate-400 text-xs">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Memuat daftar ekstrakurikuler...
                            </td>
                        </tr>
                        <tr v-else-if="filteredEkskulList.length === 0">
                            <td colspan="6" class="text-center py-5 text-slate-400 text-xs">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-4 mb-2">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <div class="font-bold text-slate-700 text-sm">Belum Ada Ekstrakurikuler</div>
                                <p class="text-slate-400 text-xs mb-3">Klik tombol di atas untuk menambahkan ekstrakurikuler baru.</p>
                            </td>
                        </tr>
                        <tr v-else v-for="item in filteredEkskulList" :key="item.id" class="text-xs border-b border-slate-100 transition hover:bg-slate-50/60">
                            <!-- Kolom 1: NAMA EKSTRAKURIKULER + KATEGORI BADGE -->
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl d-flex align-items-center justify-content-center text-white font-bold text-sm flex-shrink-0 shadow-2xs"
                                         :style="getEkskulIconStyle(item.kategori, item.nama_ekskul)">
                                        <i class="bi" :class="getEkskulCategoryIcon(item.kategori, item.nama_ekskul)"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="font-bold text-slate-800 text-sm">{{ item.nama_ekskul }}</span>
                                            <span class="badge text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow-2xs d-inline-flex align-items-center gap-1"
                                                  :style="getKategoriBadgeStyle(item.kategori, item.nama_ekskul)">
                                                <i class="bi" :class="getEkskulCategoryIcon(item.kategori, item.nama_ekskul)"></i>
                                                <span>{{ getKategoriDisplay(item.kategori, item.nama_ekskul) }}</span>
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-slate-400 line-clamp-1 mt-0.5" v-if="item.deskripsi">{{ item.deskripsi }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom 2: GURU PEMBINA -->
                            <td>
                                <div v-if="item.nama_pembina" class="d-flex align-items-center gap-1.5 text-slate-700 font-semibold">
                                    <i class="bi bi-person-fill text-indigo-500 fs-6"></i>
                                    <span>{{ item.nama_pembina }}</span>
                                </div>
                                <span v-else class="text-slate-400 italic text-[11px]">Belum Ditugaskan</span>
                            </td>

                            <!-- Kolom 3: JADWAL & LOKASI -->
                            <td>
                                <div v-if="item.hari_latihan" class="font-medium text-slate-700 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-calendar-event text-blue-500"></i>
                                    <span>{{ item.hari_latihan }}</span>
                                    <span v-if="item.jam_mulai" class="text-slate-400 text-[11px]">({{ item.jam_mulai }} - {{ item.jam_selesai || 'Selesai' }})</span>
                                </div>
                                <div v-if="item.tempat_latihan" class="text-[11px] text-slate-400 mt-0.5 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-geo-alt text-slate-400"></i>
                                    <span>{{ item.tempat_latihan }}</span>
                                </div>
                                <span v-if="!item.hari_latihan && !item.tempat_latihan" class="text-slate-400">—</span>
                            </td>

                            <!-- Kolom 4: ANGGOTA AKTIF -->
                            <td class="text-center">
                                <button type="button" class="badge bg-slate-100 text-slate-700 font-bold px-2.5 py-1 rounded-lg border border-slate-200 text-xs transition hover:bg-blue-50 hover:text-blue-700 hover:border-blue-200 cursor-pointer shadow-2xs" 
                                        @click="selectEkskulForTab(item.id, 'anggota')" title="Klik untuk lihat anggota">
                                    {{ item.total_anggota || 0 }} Siswa
                                </button>
                            </td>

                            <!-- Kolom 5: STATUS -->
                            <td class="text-center">
                                <button type="button" class="btn btn-xs rounded-full px-2.5 py-1 text-[10px] font-bold transition shadow-2xs"
                                        :class="item.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                                        @click="toggleStatusEkskul(item)">
                                    <i class="bi me-1" :class="item.is_active ? 'bi-check-circle-fill text-emerald-600' : 'bi-x-circle-fill text-slate-400'"></i>
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>

                            <!-- Kolom 6: AKSI (GRUP TOMBOL AKSI BERSIH) -->
                            <td class="text-center pe-4">
                                <div class="d-inline-flex align-items-center gap-1 p-1 rounded-xl bg-slate-50 border border-slate-200/80 shadow-2xs">
                                    <button type="button" class="btn btn-xs btn-white bg-white border border-slate-200 rounded-lg p-1.5 text-blue-600 hover:bg-blue-50 hover:border-blue-300 shadow-2xs" title="Kelola Anggota" @click="selectEkskulForTab(item.id, 'anggota')">
                                        <i class="bi bi-people"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-white bg-white border border-slate-200 rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-900 shadow-2xs" title="Edit Ekskul" @click="editEkskul(item)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-white bg-white border border-rose-200 rounded-lg p-1.5 text-rose-600 hover:bg-rose-50 hover:text-rose-700 shadow-2xs" title="Hapus Ekskul" @click="deleteEkskul(item)">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         4. TAB 2: PEMBINA EKSTRAKURIKULER
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'pembina'">
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden mb-4">
            <!-- Header Toolbar (Horizontal Scrollable) -->
            <div class="px-3.5 py-2.5 border-b border-slate-200/80 bg-slate-50/50">
                <div class="d-flex align-items-center justify-content-between gap-3 overflow-x-auto flex-nowrap custom-scrollbar" style="scrollbar-width: thin; -webkit-overflow-scrolling: touch;">
                    <div class="d-flex align-items-center gap-2 flex-shrink-0" style="width: 280px;">
                        <div class="position-relative w-100">
                            <label for="searchPembinaInput" class="visually-hidden">Cari nama atau kontak pembina</label>
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 text-xs pointer-events-none"></i>
                            <input type="text" id="searchPembinaInput" name="search_pembina" v-model="searchPembina" class="form-control form-control-sm text-xs rounded-xl ps-4 pe-4 py-1.5 border-slate-200 shadow-2xs bg-white text-slate-800 font-medium" placeholder="Cari nama atau kontak pembina..." aria-label="Cari nama atau kontak pembina">
                            <button v-if="searchPembina" @click="searchPembina = ''" class="btn btn-sm p-0 position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 border-0 bg-transparent" aria-label="Hapus pencarian">
                                <i class="bi bi-x-circle-fill text-xs"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                        <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs d-inline-flex align-items-center gap-1.5 shadow-sm hover:shadow transition bg-blue-600 border-0 flex-shrink-0 text-nowrap" @click="openModalPembina()">
                            <i class="bi bi-person-plus-fill"></i>
                            <span>Tambah Pembina Baru</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Pembina -->
            <div class="table-responsive custom-scrollbar">
                <table class="table table-hover align-middle mb-0" style="min-width: 900px;">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="ps-4 py-3.5">NAMA PEMBINA</th>
                            <th class="py-3.5 text-center" style="width: 140px;">KATEGORI</th>
                            <th class="py-3.5">KONTAK & EMAIL</th>
                            <th class="py-3.5 text-center" style="width: 140px;">BIMBINGAN EKSKUL</th>
                            <th class="py-3.5 text-center" style="width: 100px;">STATUS</th>
                            <th class="py-3.5 text-center pe-4" style="width: 120px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loadingPembina">
                            <td colspan="6" class="text-center py-5 text-slate-400 text-xs">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Memuat daftar pembina ekskul...
                            </td>
                        </tr>
                        <tr v-else-if="filteredPembinaList.length === 0">
                            <td colspan="6" class="text-center py-5 text-slate-400 text-xs">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-4 mb-2">
                                    <i class="bi bi-person-x"></i>
                                </div>
                                <div class="font-bold text-slate-700 text-sm">Belum Ada Data Pembina</div>
                                <p class="text-slate-400 text-xs mb-3">Tambahkan guru pembimbing atau instruktur luar.</p>
                            </td>
                        </tr>
                        <tr v-else v-for="p in filteredPembinaList" :key="p.id" class="text-xs border-b border-slate-100 transition hover:bg-slate-50/60">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 font-bold d-flex align-items-center justify-content-center text-sm flex-shrink-0 border border-indigo-100 shadow-2xs">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-800 text-sm d-block">{{ p.nama_pembina }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono" v-if="p.nip && p.nip !== '—'">NIP: {{ p.nip }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge text-[11px] font-semibold px-2.5 py-1 rounded-pill"
                                      :class="p.kategori_pembina === 'Guru Internal' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                                    {{ p.kategori_pembina || 'Guru Internal' }}
                                </span>
                            </td>
                            <td>
                                <div class="text-slate-700 font-medium d-flex align-items-center gap-1.5">
                                    <i class="bi bi-telephone text-slate-400" v-if="p.no_hp"></i> 
                                    <span>{{ p.no_hp || '—' }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5 d-flex align-items-center gap-1.5" v-if="p.email">
                                    <i class="bi bi-envelope text-slate-400"></i> {{ p.email }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-slate-100 text-slate-700 font-bold px-2.5 py-1 rounded-lg border border-slate-200">
                                    {{ p.total_bimbingan || 0 }} Ekskul
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs rounded-full px-2.5 py-1 text-[10px] font-bold transition shadow-2xs"
                                        :class="p.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                                        @click="toggleStatusPembina(p)">
                                    <i class="bi me-1" :class="p.is_active ? 'bi-check-circle-fill text-emerald-600' : 'bi-x-circle-fill text-slate-400'"></i>
                                    {{ p.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-inline-flex align-items-center gap-1 p-1 rounded-xl bg-slate-50 border border-slate-200/80 shadow-2xs">
                                    <button type="button" class="btn btn-xs btn-white bg-white border border-slate-200 rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 shadow-2xs" title="Edit Pembina" @click="editPembina(p)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-white bg-white border border-rose-200 text-rose-600 rounded-lg p-1.5 hover:bg-rose-50 shadow-2xs" title="Hapus Pembina" @click="deletePembina(p)">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         5. TAB 3: KELOLA ANGGOTA EKSKUL (CLEAN SAAS REDESIGN)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'anggota'">
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden mb-4">
            <!-- Symmetrical Modern Header Toolbar (Horizontal Scrollable) -->
            <div class="px-3.5 py-2.5 border-b border-slate-200/80 bg-slate-50/50">
                <div class="d-flex align-items-center justify-content-between gap-3 overflow-x-auto flex-nowrap custom-scrollbar" style="scrollbar-width: thin; -webkit-overflow-scrolling: touch;">
                    <!-- Left: TA + Semester + Ekskul Selector + Live Search Input -->
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <!-- Academic Filter Segment (TA & Semester) -->
                        <div class="d-inline-flex align-items-center bg-white p-1 rounded-xl border border-slate-200 shadow-2xs gap-1 flex-shrink-0">
                            <div class="d-flex align-items-center px-2 py-0.5 text-xs font-semibold text-slate-700">
                                <i class="bi bi-calendar3 text-blue-600 me-1.5 fs-7"></i>
                                <span class="text-slate-400 me-1 font-medium">TA:</span>
                                <select id="filterTaAnggota" name="filter_ta" v-model="filterTahunAjaranId" @change="onAcademicFilterChange()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold py-0 ps-0 pe-4 text-slate-800 cursor-pointer shadow-none" style="width: auto;" aria-label="Filter Tahun Ajaran">
                                    <option v-for="ta in listTahunAjaran" :key="ta.id" :value="ta.id">{{ ta.nama_tahun_ajaran }}</option>
                                </select>
                            </div>
                            <div class="vr bg-slate-200 opacity-60" style="height: 16px;"></div>
                            <div class="d-flex align-items-center px-2 py-0.5 text-xs font-semibold text-slate-700">
                                <span class="text-slate-400 me-1 font-medium">Smst:</span>
                                <select id="filterSemesterAnggota" name="filter_semester" v-model="filterSemester" @change="onAcademicFilterChange()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold py-0 ps-0 pe-4 text-slate-800 cursor-pointer shadow-none" style="width: auto;" aria-label="Filter Semester">
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                        </div>

                        <!-- Ekskul Selector -->
                        <div class="d-flex align-items-center bg-white px-3 py-1 rounded-xl border border-slate-200 shadow-2xs flex-shrink-0">
                            <i class="bi bi-diagram-3-fill text-blue-600 me-2 fs-7"></i>
                            <span class="text-slate-400 text-xs font-bold me-2">Ekskul:</span>
                            <select id="selectEkskulAnggota" name="selected_ekskul_id" v-model="selectedEkskulId" @change="fetchAnggota()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold text-slate-800 p-0 shadow-none cursor-pointer" style="min-width: 180px;" aria-label="Pilih Ekstrakurikuler">
                                <option value="" disabled>-- Pilih Ekstrakurikuler --</option>
                                <option v-for="e in ekskulList" :key="e.id" :value="e.id">{{ e.nama_ekskul }} ({{ getKategoriDisplay(e.kategori, e.nama_ekskul) }})</option>
                            </select>
                        </div>

                        <!-- Live Search -->
                        <div class="position-relative flex-shrink-0" style="width: 220px;" v-if="selectedEkskulId">
                            <label for="searchAnggotaInput" class="visually-hidden">Cari nama atau NISN</label>
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 text-xs pointer-events-none"></i>
                            <input type="text" id="searchAnggotaInput" name="search_anggota" v-model="searchAnggota" class="form-control form-control-sm text-xs rounded-xl ps-4 pe-4 py-1.5 border-slate-200 shadow-2xs bg-white text-slate-800 font-medium" placeholder="Cari nama / NISN..." aria-label="Cari nama atau NISN">
                            <button v-if="searchAnggota" @click="searchAnggota = ''" class="btn btn-sm p-0 position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 border-0 bg-transparent" aria-label="Hapus pencarian">
                                <i class="bi bi-x-circle-fill text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Right: Lock Toggle + Export Excel + Tambah Anggota -->
                    <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                        <!-- Lock Button -->
                        <button type="button" class="btn btn-sm rounded-xl font-bold px-3 py-1.5 text-xs d-inline-flex align-items-center gap-1.5 transition shadow-2xs flex-shrink-0 text-nowrap"
                                :class="currentLock.lock_anggota ? 'bg-amber-100 text-amber-900 border border-amber-300 hover:bg-amber-200' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                                @click="toggleLock('anggota')" :disabled="!selectedEkskulId">
                            <i class="bi" :class="currentLock.lock_anggota ? 'bi-lock-fill text-amber-800' : 'bi-unlock text-slate-500'"></i>
                            <span>{{ currentLock.lock_anggota ? 'Pendaftaran Terkunci' : 'Kunci Pendaftaran' }}</span>
                        </button>

                        <!-- Export Excel Button -->
                        <a :href="getExportAnggotaUrl()" target="_blank" class="btn btn-sm btn-outline-success rounded-xl font-bold px-3 py-1.5 text-xs d-inline-flex align-items-center gap-1.5 shadow-2xs bg-white hover:bg-emerald-50 flex-shrink-0 text-nowrap"
                           :class="{'disabled pointer-events-none opacity-50': !selectedEkskulId || filteredAnggotaList.length === 0}">
                            <i class="bi bi-file-earmark-excel-fill text-emerald-600"></i>
                            <span>Ekspor Excel</span>
                        </a>

                        <!-- Tambah Anggota Button -->
                        <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-inline-flex align-items-center gap-1.5 shadow-2xs hover:shadow-xs transition flex-shrink-0 text-nowrap"
                                :disabled="!selectedEkskulId || currentLock.lock_anggota" @click="openModalTambahAnggota()">
                            <i class="bi bi-person-plus-fill"></i>
                            <span>Tambah Anggota</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Area: Table / Clean Empty State -->
            <div>
                <div v-if="!selectedEkskulId" class="text-center py-5 px-3">
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 d-inline-flex align-items-center justify-content-center fs-4 mb-2">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <div class="font-bold text-slate-700 text-sm">Pilih Ekstrakurikuler</div>
                    <p class="text-slate-400 text-xs mb-0">Silakan pilih ekstrakurikuler terlebih dahulu untuk melihat daftar anggota.</p>
                </div>
                <div v-else-if="loadingAnggota" class="text-center py-5 px-3">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span class="text-slate-400 text-xs">Memuat anggota ekstrakurikuler...</span>
                </div>
                <div v-else-if="filteredAnggotaList.length === 0" class="text-center py-5 px-3">
                    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="font-bold text-slate-700 text-base mb-1">Belum Ada Anggota Terdaftar</div>
                    <p class="text-slate-400 text-xs mb-3" style="max-width: 480px; margin: 0 auto;">
                        Daftarkan siswa aktif ke ekstrakurikuler ini untuk periode {{ selectedTaName }} ({{ filterSemester }}).
                    </p>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-sm" @click="openModalTambahAnggota()" :disabled="currentLock.lock_anggota">
                        <i class="bi bi-person-plus-fill me-1.5"></i> Tambah Anggota Sekarang
                    </button>
                </div>
                <div v-else class="table-responsive custom-scrollbar">
                    <table class="table table-hover align-middle mb-0" style="min-width: 950px;">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="ps-4 py-3.5 text-center" style="width: 50px;">NO</th>
                                <th class="py-3.5">NAMA LENGKAP SISWA</th>
                                <th class="py-3.5">NISN / NIS</th>
                                <th class="py-3.5 text-center" style="width: 110px;">KELAS</th>
                                <th class="py-3.5 text-center" style="width: 130px;">JENIS KELAMIN</th>
                                <th class="py-3.5 text-center" style="width: 140px;">JABATAN</th>
                                <th class="py-3.5 text-center" style="width: 100px;">STATUS</th>
                                <th class="py-3.5 text-center pe-4" style="width: 120px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(mem, idx) in filteredAnggotaList" :key="mem.id" class="text-xs border-b border-slate-100 transition hover:bg-slate-50/60">
                                <td class="ps-4 py-3 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center font-bold text-xs flex-shrink-0 border border-blue-100 shadow-2xs">
                                            {{ (mem.nama_lengkap || 'S').charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-800 text-sm d-block">{{ mem.nama_lengkap }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 font-mono text-slate-600">{{ mem.nisn || mem.nis || '—' }}</td>
                                <td class="py-3 text-center">
                                    <span class="badge bg-slate-100 text-slate-700 font-bold px-2.5 py-1 rounded-lg border border-slate-200 text-xs">
                                        {{ mem.nama_kelas }}
                                    </span>
                                </td>
                                <td class="py-3 text-center">
                                    <span :class="mem.jenis_kelamin === 'L' ? 'text-blue-600' : 'text-pink-600'" class="font-semibold d-inline-flex align-items-center gap-1">
                                        <i class="bi" :class="mem.jenis_kelamin === 'L' ? 'bi-gender-male' : 'bi-gender-female'"></i>
                                        {{ mem.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                    </span>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="badge text-xs px-3 py-1.5 rounded-full shadow-2xs d-inline-flex align-items-center gap-1.5"
                                          :style="getJabatanBadgeStyle(mem.jabatan)">
                                        <i class="bi" :class="getJabatanIcon(mem.jabatan)"></i>
                                        {{ mem.jabatan || 'Anggota' }}
                                    </span>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-check-circle-fill text-emerald-600"></i> {{ mem.status_keanggotaan || 'Aktif' }}
                                    </span>
                                </td>
                                <td class="py-3 text-center pe-4">
                                    <button type="button" class="btn btn-xs btn-outline-danger rounded-xl px-2.5 py-1.5 font-bold transition shadow-2xs d-inline-flex align-items-center gap-1" 
                                            :disabled="currentLock.lock_anggota" title="Keluarkan Anggota" @click="removeAnggota(mem)">
                                        <i class="bi bi-person-dash"></i>
                                        <span>Keluarkan</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         6. TAB 4: JURNAL & AGENDA KEGIATAN
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'jurnal'">
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden mb-4">
            <!-- Header Toolbar (Horizontal Scrollable) -->
            <div class="px-3.5 py-2.5 border-b border-slate-200/80 bg-slate-50/50">
                <div class="d-flex align-items-center justify-content-between gap-3 overflow-x-auto flex-nowrap custom-scrollbar" style="scrollbar-width: thin; -webkit-overflow-scrolling: touch;">
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <!-- Academic Filter Segment (TA & Semester) -->
                        <div class="d-inline-flex align-items-center bg-white p-1 rounded-xl border border-slate-200 shadow-2xs gap-1 flex-shrink-0">
                            <div class="d-flex align-items-center px-2 py-0.5 text-xs font-semibold text-slate-700">
                                <i class="bi bi-calendar3 text-blue-600 me-1.5 fs-7"></i>
                                <span class="text-slate-400 me-1 font-medium">TA:</span>
                                <select id="filterTaJurnal" name="filter_ta" v-model="filterTahunAjaranId" @change="onAcademicFilterChange()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold py-0 ps-0 pe-4 text-slate-800 cursor-pointer shadow-none" style="width: auto;" aria-label="Filter Tahun Ajaran">
                                    <option v-for="ta in listTahunAjaran" :key="ta.id" :value="ta.id">{{ ta.nama_tahun_ajaran }}</option>
                                </select>
                            </div>
                            <div class="vr bg-slate-200 opacity-60" style="height: 16px;"></div>
                            <div class="d-flex align-items-center px-2 py-0.5 text-xs font-semibold text-slate-700">
                                <span class="text-slate-400 me-1 font-medium">Smst:</span>
                                <select id="filterSemesterJurnal" name="filter_semester" v-model="filterSemester" @change="onAcademicFilterChange()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold py-0 ps-0 pe-4 text-slate-800 cursor-pointer shadow-none" style="width: auto;" aria-label="Filter Semester">
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                        </div>

                        <!-- Ekskul Selector -->
                        <div class="d-flex align-items-center bg-white px-3 py-1 rounded-xl border border-slate-200 shadow-2xs flex-shrink-0">
                            <i class="bi bi-diagram-3-fill text-blue-600 me-2 fs-7"></i>
                            <span class="text-slate-400 text-xs font-bold me-2">Ekskul:</span>
                            <select id="selectEkskulJurnal" name="selected_ekskul_id" v-model="selectedEkskulId" @change="fetchJurnal()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold text-slate-800 p-0 shadow-none cursor-pointer" style="min-width: 180px;" aria-label="Pilih Ekstrakurikuler">
                                <option value="" disabled>-- Pilih Ekstrakurikuler --</option>
                                <option v-for="e in ekskulList" :key="e.id" :value="e.id">{{ e.nama_ekskul }} ({{ getKategoriDisplay(e.kategori, e.nama_ekskul) }})</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                        <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs d-inline-flex align-items-center gap-1.5 shadow-sm hover:shadow transition bg-blue-600 border-0 flex-shrink-0 text-nowrap"
                                :disabled="!selectedEkskulId" @click="openModalJurnal()">
                            <i class="bi bi-plus-circle-fill"></i>
                            <span>Catat Pertemuan Baru</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="p-4">
                <div v-if="!selectedEkskulId" class="text-center py-5 text-slate-400 text-xs">
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 d-inline-flex align-items-center justify-content-center fs-4 mb-2">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div class="font-bold text-slate-700 text-sm">Pilih Ekstrakurikuler</div>
                    <p class="text-slate-400 text-xs mb-0">Silakan pilih ekstrakurikuler terlebih dahulu untuk melihat catatan jurnal kegiatan.</p>
                </div>
                <div v-else-if="loadingJurnal" class="text-center py-5 text-slate-400 text-xs">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    Memuat catatan jurnal kegiatan...
                </div>
                <div v-else-if="jurnalList.length === 0" class="text-center py-5 text-slate-400 text-xs">
                    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <div class="font-bold text-slate-700 text-base mb-1">Belum Ada Catatan Jurnal</div>
                    <p class="text-slate-400 text-xs mb-3" style="max-width: 480px; margin: 0 auto;">
                        Belum ada agenda pertemuan yang dicatat pada periode {{ selectedTaName }} ({{ filterSemester }}).
                    </p>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-sm" @click="openModalJurnal()">
                        <i class="bi bi-plus-circle-fill me-1.5"></i> Catat Pertemuan Pertama
                    </button>
                </div>
                <div v-else class="row g-3">
                    <div v-for="(j, idx) in jurnalList" :key="j.id" class="col-12 col-md-6 col-xl-4">
                        <div class="p-3.5 p-md-4 rounded-2xl border border-slate-200/80 bg-white hover:border-blue-300 hover:shadow-md hover:-translate-y-0.5 transition-all h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2.5">
                                    <span class="badge bg-blue-50 text-blue-700 border border-blue-200 font-bold px-2.5 py-1 rounded-lg text-[10px]">
                                        <i class="bi bi-calendar-check me-1"></i> {{ formatDateIndo(j.tanggal_kegiatan) }}
                                    </span>
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-xs btn-light border border-slate-200 text-slate-500 rounded-lg p-1 shadow-2xs hover:bg-slate-100" title="Edit Jurnal" @click="editJurnal(j)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-light border border-rose-200 text-rose-500 rounded-lg p-1 shadow-2xs hover:bg-rose-50" title="Hapus Jurnal" @click="deleteJurnal(j)">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </div>
                                <h4 class="text-slate-800 font-bold text-sm mb-1.5 leading-snug">{{ j.materi_kegiatan }}</h4>
                                <div class="text-xs text-slate-500 d-flex align-items-center gap-2 mb-2 flex-wrap" v-if="j.lokasi || j.jam_mulai">
                                    <span v-if="j.jam_mulai"><i class="bi bi-clock me-1 text-slate-400"></i>{{ j.jam_mulai }} - {{ j.jam_selesai || 'Selesai' }}</span>
                                    <span v-if="j.lokasi"><i class="bi bi-geo-alt me-1 text-slate-400"></i>{{ j.lokasi }}</span>
                                </div>
                                <p class="text-slate-600 text-xs mb-2 bg-slate-50 p-2 rounded-xl border border-slate-200/60" v-if="j.catatan_evaluasi" style="font-style: italic;">
                                    "{{ j.catatan_evaluasi }}"
                                </p>
                            </div>
                            <div class="pt-2.5 border-t border-slate-100 d-flex align-items-center justify-content-between text-[11px] text-slate-500">
                                <span><i class="bi bi-person-badge text-indigo-500 me-1"></i>{{ j.nama_pembina || 'Pembina' }}</span>
                                <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                    Hadir: {{ j.jumlah_hadir || 0 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         7. TAB 5: PENILAIAN EKSKUL & E-RAPOR INTEGRATION
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'nilai'">
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden mb-4">
            <!-- Header Toolbar (Horizontal Scrollable) -->
            <div class="px-3.5 py-2.5 border-b border-slate-200/80 bg-slate-50/50">
                <div class="d-flex align-items-center justify-content-between gap-3 overflow-x-auto flex-nowrap custom-scrollbar" style="scrollbar-width: thin; -webkit-overflow-scrolling: touch;">
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <!-- Academic Filter Segment (TA & Semester) -->
                        <div class="d-inline-flex align-items-center bg-white p-1 rounded-xl border border-slate-200 shadow-2xs gap-1 flex-shrink-0">
                            <div class="d-flex align-items-center px-2 py-0.5 text-xs font-semibold text-slate-700">
                                <i class="bi bi-calendar3 text-blue-600 me-1.5 fs-7"></i>
                                <span class="text-slate-400 me-1 font-medium">TA:</span>
                                <select id="filterTaNilai" name="filter_ta" v-model="filterTahunAjaranId" @change="onAcademicFilterChange()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold py-0 ps-0 pe-4 text-slate-800 cursor-pointer shadow-none" style="width: auto;" aria-label="Filter Tahun Ajaran">
                                    <option v-for="ta in listTahunAjaran" :key="ta.id" :value="ta.id">{{ ta.nama_tahun_ajaran }}</option>
                                </select>
                            </div>
                            <div class="vr bg-slate-200 opacity-60" style="height: 16px;"></div>
                            <div class="d-flex align-items-center px-2 py-0.5 text-xs font-semibold text-slate-700">
                                <span class="text-slate-400 me-1 font-medium">Smst:</span>
                                <select id="filterSemesterNilai" name="filter_semester" v-model="filterSemester" @change="onAcademicFilterChange()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold py-0 ps-0 pe-4 text-slate-800 cursor-pointer shadow-none" style="width: auto;" aria-label="Filter Semester">
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                        </div>

                        <!-- Ekskul Selector -->
                        <div class="d-flex align-items-center bg-white px-3 py-1 rounded-xl border border-slate-200 shadow-2xs flex-shrink-0">
                            <i class="bi bi-diagram-3-fill text-blue-600 me-2 fs-7"></i>
                            <span class="text-slate-400 text-xs font-bold me-2">Ekskul:</span>
                            <select id="selectEkskulNilai" name="selected_ekskul_id" v-model="selectedEkskulId" @change="fetchNilai()" class="form-select form-select-sm border-0 bg-transparent text-xs font-bold text-slate-800 p-0 shadow-none cursor-pointer" style="min-width: 180px;" aria-label="Pilih Ekstrakurikuler">
                                <option value="" disabled>-- Pilih Ekstrakurikuler --</option>
                                <option v-for="e in ekskulList" :key="e.id" :value="e.id">{{ e.nama_ekskul }} ({{ getKategoriDisplay(e.kategori, e.nama_ekskul) }})</option>
                            </select>
                        </div>

                        <!-- Autofill Presets -->
                        <div v-if="selectedEkskulId && !currentLock.lock_nilai && nilaiList.length > 0" class="d-flex align-items-center gap-1.5 flex-shrink-0">
                            <span class="text-[11px] font-semibold text-slate-400">Autofill:</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-lg px-2.5 py-1 text-[10px] font-bold bg-white shadow-2xs" @click="autofillPredikat('A')">Semua A</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-lg px-2.5 py-1 text-[10px] font-bold bg-white shadow-2xs" @click="autofillPredikat('B')">Semua B</button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                        <!-- Lock Nilai Toggle -->
                        <button type="button" class="btn btn-xs rounded-xl font-bold px-3 py-1.5 d-inline-flex align-items-center gap-1.5 transition shadow-2xs flex-shrink-0 text-nowrap"
                                :class="currentLock.lock_nilai ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
                                @click="toggleLock('nilai')" :disabled="!selectedEkskulId">
                            <i class="bi" :class="currentLock.lock_nilai ? 'bi-lock-fill text-amber-700' : 'bi-unlock text-slate-500'"></i>
                            <span>{{ currentLock.lock_nilai ? 'Nilai Terkunci' : 'Kunci Nilai' }}</span>
                        </button>

                        <!-- Export Nilai Excel -->
                        <a :href="getExportNilaiUrl()" target="_blank" class="btn btn-sm btn-outline-success rounded-xl font-bold px-3 py-1.5 text-xs d-inline-flex align-items-center gap-1.5 shadow-2xs bg-white flex-shrink-0 text-nowrap"
                           :class="{'disabled pointer-events-none opacity-50': !selectedEkskulId || nilaiList.length === 0}">
                            <i class="bi bi-file-earmark-excel-fill"></i> Unduh Format
                        </a>

                        <!-- Import Nilai -->
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-xl font-bold px-3 py-1.5 text-xs d-inline-flex align-items-center gap-1.5 shadow-2xs bg-white flex-shrink-0 text-nowrap"
                                :disabled="!selectedEkskulId || currentLock.lock_nilai" @click="openModalImportNilai()">
                            <i class="bi bi-upload"></i> Unggah Nilai
                        </button>

                        <!-- Simpan Penilaian Batch -->
                        <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-inline-flex align-items-center gap-1.5 shadow-sm flex-shrink-0 text-nowrap"
                                :disabled="!selectedEkskulId || currentLock.lock_nilai || savingNilai" @click="saveAllNilai()">
                            <span v-if="savingNilai" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-check-all"></i>
                            <span>Simpan Nilai</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Area: Table / Clean Empty State -->
            <div>
                <div v-if="!selectedEkskulId" class="text-center py-5 px-3">
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 d-inline-flex align-items-center justify-content-center fs-4 mb-2">
                        <i class="bi bi-award"></i>
                    </div>
                    <div class="font-bold text-slate-700 text-sm">Pilih Ekstrakurikuler</div>
                    <p class="text-slate-400 text-xs mb-0">Silakan pilih ekstrakurikuler terlebih dahulu untuk menginput penilaian rapor.</p>
                </div>
                <div v-else-if="loadingNilai" class="text-center py-5 px-3">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span class="text-slate-400 text-xs">Memuat data penilaian siswa...</span>
                </div>
                <div v-else-if="nilaiList.length === 0" class="text-center py-5 px-3">
                    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5">
                        <i class="bi bi-person-x"></i>
                    </div>
                    <div class="font-bold text-slate-700 text-base mb-1">Belum Ada Anggota Terdaftar</div>
                    <p class="text-slate-400 text-xs mb-3" style="max-width: 480px; margin: 0 auto;">
                        Daftarkan siswa di tab <strong>Kelola Anggota</strong> terlebih dahulu sebelum melakukan penginputan nilai rapor.
                    </p>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-sm" @click="activeTab = 'anggota'">
                        <i class="bi bi-people me-1.5"></i> Buka Tab Kelola Anggota
                    </button>
                </div>
                <div v-else class="table-responsive custom-scrollbar">
                    <table class="table table-hover align-middle mb-0" style="min-width: 980px;">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="ps-4 py-3.5 text-center" style="width: 50px;">NO</th>
                                <th class="py-3.5">NAMA LENGKAP SISWA</th>
                                <th class="py-3.5">KELAS</th>
                                <th class="py-3.5 text-center" style="width: 160px;">PREDIKAT RAPOR</th>
                                <th class="py-3.5 text-center" style="width: 120px;">NILAI ANGKA</th>
                                <th class="py-3.5 pe-4">CATATAN PERKEMBANGAN & KETERANGAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(n, idx) in nilaiList" :key="n.siswa_id" class="text-xs border-b border-slate-100 transition hover:bg-slate-50/60">
                                <td class="ps-4 py-2.5 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                                <td>
                                    <div class="font-bold text-slate-800 text-sm">{{ n.nama_lengkap }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">NISN: {{ n.nisn || n.nis || '—' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-slate-100 text-slate-700 font-semibold px-2.5 py-1 rounded-lg border border-slate-200">
                                        {{ n.nama_kelas }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <select v-model="n.predikat" class="form-select form-select-sm text-xs font-bold rounded-xl border-slate-200 text-center shadow-2xs cursor-pointer"
                                            :class="getPredikatSelectClass(n.predikat)" :disabled="currentLock.lock_nilai">
                                        <option value="A">A (Sangat Baik)</option>
                                        <option value="B">B (Baik)</option>
                                        <option value="C">C (Cukup)</option>
                                        <option value="D">D (Kurang)</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <input type="number" v-model.number="n.nilai_angka" class="form-control form-control-sm text-xs font-bold rounded-xl border-slate-200 text-center shadow-2xs"
                                           min="0" max="100" placeholder="0-100" :disabled="currentLock.lock_nilai">
                                </td>
                                <td class="pe-4">
                                    <input type="text" v-model="n.keterangan" class="form-control form-control-sm text-xs rounded-xl border-slate-200 shadow-2xs"
                                           placeholder="Tulis deskripsi capaian rapor..." :disabled="currentLock.lock_nilai">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         8. MODAL DIALOGS
         ═══════════════════════════════════════════════════════════════════════ -->

    <!-- MODAL 1: TAMBAH / EDIT MASTER EKSKUL -->
    <div v-if="modalEkskul.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); z-index: 1060; backdrop-filter: blur(8px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
            <div class="modal-content border-0 rounded-2xl shadow-2xl overflow-hidden">
                <div class="modal-header px-6 py-4 border-b border-slate-100 d-flex align-items-center justify-content-between"
                     style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-white text-base mb-0">{{ modalEkskul.isEdit ? 'Edit Ekstrakurikuler' : 'Tambah Ekstrakurikuler Baru' }}</h5>
                            <span class="text-white/80 text-xs">Isi rincian informasi ekstrakurikuler sekolah</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalEkskul.show = false"></button>
                </div>
                <div class="modal-body p-6">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Nama Ekstrakurikuler <span class="text-rose-500">*</span></label>
                            <input type="text" v-model="modalEkskul.form.nama_ekskul" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" placeholder="Contoh: Pramuka, Futsal, Robotik, Tari Tradisional...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Kategori Ekskul <span class="text-rose-500">*</span></label>
                            <select v-model="modalEkskul.form.kategori" class="form-select text-xs rounded-xl border-slate-200 py-2 shadow-2xs font-medium cursor-pointer">
                                <option value="Wajib">Wajib</option>
                                <option value="Pilihan">Pilihan</option>
                                <option value="Olahraga">Olahraga</option>
                                <option value="Seni & Budaya">Seni & Budaya</option>
                                <option value="Keagamaan">Keagamaan</option>
                                <option value="Akademik / Sains">Akademik / Sains</option>
                                <option value="Kepanduan & Bela Negara">Kepanduan & Bela Negara</option>
                                <option value="Teknologi & Robotik">Teknologi & Robotik</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Guru / Pembina Ditugaskan</label>
                            <select v-model="modalEkskul.form.pembina_id" class="form-select text-xs rounded-xl border-slate-200 py-2 shadow-2xs font-medium cursor-pointer">
                                <option value="">— Belum Ditugaskan —</option>
                                <option v-for="p in pembinaList" :key="p.id" :value="p.id">{{ p.nama_pembina }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Hari Latihan</label>
                            <select v-model="modalEkskul.form.hari_latihan" class="form-select text-xs rounded-xl border-slate-200 py-2 shadow-2xs font-medium cursor-pointer">
                                <option value="">— Pilih Hari —</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Jam Mulai</label>
                            <input type="time" v-model="modalEkskul.form.jam_mulai" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Jam Selesai</label>
                            <input type="time" v-model="modalEkskul.form.jam_selesai" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Lokasi / Tempat Latihan</label>
                            <input type="text" v-model="modalEkskul.form.tempat_latihan" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" placeholder="Contoh: Lapangan Utama, Lab Komputer, Aula...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Kuota Maksimal</label>
                            <input type="number" v-model.number="modalEkskul.form.kuota_maksimal" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" min="0" placeholder="0 = Tak terbatas">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Deskripsi / Visi Misi Ekskul</label>
                            <textarea v-model="modalEkskul.form.deskripsi" class="form-control text-xs rounded-xl border-slate-200 shadow-2xs" rows="3" placeholder="Tulis deskripsi singkat kegiatan ekstrakurikuler..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-6 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-semibold px-4" @click="modalEkskul.show = false">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 shadow-sm" :disabled="modalEkskul.saving" @click="submitEkskul()">
                        <span v-if="modalEkskul.saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-check2-circle me-1"></i> Simpan Data Ekskul
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: TAMBAH / EDIT PEMBINA EKSKUL -->
    <div v-if="modalPembina.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); z-index: 1060; backdrop-filter: blur(8px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
            <div class="modal-content border-0 rounded-2xl shadow-2xl overflow-hidden">
                <div class="modal-header px-6 py-4 border-b border-slate-100 d-flex align-items-center justify-content-between"
                     style="background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-white text-base mb-0">{{ modalPembina.isEdit ? 'Edit Data Pembina' : 'Tambah Pembina Baru' }}</h5>
                            <span class="text-white/80 text-xs">Pilih dari master pengguna/guru sekolah atau input instruktur luar</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalPembina.show = false"></button>
                </div>
                <div class="modal-body p-6">
                    <div class="row g-3">
                        <div class="col-12" v-if="!modalPembina.isEdit && listGuruMaster.length > 0">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Pilih Cepat dari Data Guru / Staff Sekolah</label>
                            <select v-model="modalPembina.selectedGuruId" @change="onSelectGuruPembina()" class="form-select text-xs rounded-xl border-slate-200 py-2 shadow-2xs font-medium cursor-pointer">
                                <option value="">— Input Mandiri / Instruktur Luar —</option>
                                <option v-for="g in listGuruMaster" :key="g.id" :value="g.id">{{ g.nama_lengkap }} ({{ g.email || 'Guru' }})</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap Pembina <span class="text-rose-500">*</span></label>
                            <input type="text" v-model="modalPembina.form.nama_pembina" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" placeholder="Nama lengkap beserta gelar...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Kategori Pembina</label>
                            <select v-model="modalPembina.form.kategori_pembina" class="form-select text-xs rounded-xl border-slate-200 py-2 shadow-2xs font-medium cursor-pointer">
                                <option value="Guru Internal">Guru Internal</option>
                                <option value="Instruktur Luar">Instruktur Luar</option>
                                <option value="Alumni">Alumni</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">NIP / Identitas (Opsional)</label>
                            <input type="text" v-model="modalPembina.form.nip" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" placeholder="Nomor Induk Pegawai...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">No. WhatsApp / Telepon</label>
                            <input type="text" v-model="modalPembina.form.no_hp" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Email</label>
                            <input type="email" v-model="modalPembina.form.email" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" placeholder="email@sekolah.sch.id">
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-6 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-semibold px-4" @click="modalPembina.show = false">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 shadow-sm" :disabled="modalPembina.saving" @click="submitPembina()">
                        <span v-if="modalPembina.saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-check2-circle me-1"></i> Simpan Pembina
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: TAMBAH ANGGOTA EKSKUL (SEARCH & BATCH MULTI-SELECT) -->
    <div v-if="modalAnggota.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); z-index: 1060; backdrop-filter: blur(8px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 680px;">
            <div class="modal-content border-0 rounded-2xl shadow-2xl overflow-hidden">
                <div class="modal-header px-6 py-4 border-b border-slate-100 d-flex align-items-center justify-content-between"
                     style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-white text-base mb-0">Pendaftaran Anggota Ekskul</h5>
                            <span class="text-white/80 text-xs">Pilih siswa aktif berdasarkan kelas atau pencarian nama</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalAnggota.show = false"></button>
                </div>
                <div class="modal-body p-6">
                    <!-- Filters -->
                    <div class="row g-2.5 mb-3.5">
                        <div class="col-md-5">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1">Filter Kelas Siswa</label>
                            <select v-model="modalAnggota.filterKelasId" @change="searchSiswaForModal()" class="form-select form-select-sm text-xs rounded-xl border-slate-200 shadow-2xs font-medium cursor-pointer">
                                <option value="">— Semua Kelas —</option>
                                <option v-for="k in listKelasMaster" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1">Cari Nama / NISN</label>
                            <div class="position-relative">
                                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 text-xs"></i>
                                <input type="text" v-model="modalAnggota.searchQuery" @input="searchSiswaForModal()" class="form-control form-control-sm text-xs rounded-xl ps-4 border-slate-200 shadow-2xs" placeholder="Ketik nama siswa...">
                            </div>
                        </div>
                    </div>

                    <!-- Siswa Checklist List -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs" style="max-height: 280px; overflow-y: auto;">
                        <div v-if="modalAnggota.loadingSearch" class="text-center py-5 text-slate-400 text-xs">
                            <div class="spinner-border spinner-border-sm text-teal-600 me-2"></div>
                            Mencari daftar siswa...
                        </div>
                        <div v-else-if="modalAnggota.siswaResults.length === 0" class="text-center py-5 text-slate-400 text-xs">
                            Tidak ditemukan siswa aktif dengan filter ini.
                        </div>
                        <div v-else class="divide-y divide-slate-100">
                            <div v-for="stu in modalAnggota.siswaResults" :key="stu.id"
                                 class="p-2.5 px-3 d-flex align-items-center justify-content-between hover:bg-slate-50 transition cursor-pointer"
                                 :class="{'opacity-50 pointer-events-none bg-slate-50/70': stu.is_already_member}"
                                 @click="!stu.is_already_member && toggleSelectSiswa(stu.id)">
                                <div class="d-flex align-items-center gap-3">
                                    <input type="checkbox" :value="stu.id" v-model="modalAnggota.selectedSiswaIds"
                                           :disabled="stu.is_already_member" class="form-check-input rounded-md m-0 cursor-pointer" @click.stop>
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs leading-tight">{{ stu.nama_lengkap }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            Kelas: {{ stu.nama_kelas }} | NISN: {{ stu.nisn || '—' }}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span v-if="stu.is_already_member" class="badge bg-slate-100 text-slate-500 text-[10px] font-semibold">Sudah Terdaftar</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex align-items-center justify-content-between text-xs text-slate-500">
                        <span><strong>{{ modalAnggota.selectedSiswaIds.length }}</strong> siswa dipilih</span>
                        <div class="d-flex align-items-center gap-2">
                            <label class="font-semibold text-slate-700">Jabatan:</label>
                            <select v-model="modalAnggota.defaultJabatan" class="form-select form-select-sm text-xs rounded-lg py-1 border-slate-200 shadow-2xs font-medium cursor-pointer" style="width: auto;">
                                <option value="Anggota">Anggota</option>
                                <option value="Ketua">Ketua</option>
                                <option value="Wakil Ketua">Wakil Ketua</option>
                                <option value="Sekretaris">Sekretaris</option>
                                <option value="Bendahara">Bendahara</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-6 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-semibold px-4" @click="modalAnggota.show = false">Batal</button>
                    <button type="button" class="btn btn-sm text-white rounded-xl font-bold px-4 py-2 shadow-sm"
                            style="background: #0d9488;" :disabled="modalAnggota.saving || modalAnggota.selectedSiswaIds.length === 0" @click="submitAnggota()">
                        <span v-if="modalAnggota.saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-person-check-fill me-1"></i> Daftarkan Anggota Terpilih
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 4: CATAT JURNAL KEGIATAN -->
    <div v-if="modalJurnal.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); z-index: 1060; backdrop-filter: blur(8px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
            <div class="modal-content border-0 rounded-2xl shadow-2xl overflow-hidden">
                <div class="modal-header px-6 py-4 border-b border-slate-100 d-flex align-items-center justify-content-between"
                     style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs">
                            <i class="bi bi-journal-check"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-white text-base mb-0">{{ modalJurnal.isEdit ? 'Edit Jurnal Pertemuan' : 'Catat Jurnal Pertemuan Baru' }}</h5>
                            <span class="text-white/80 text-xs">Dokumentasikan agenda & materi latihan mingguan</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalJurnal.show = false"></button>
                </div>
                <div class="modal-body p-6">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Tanggal Pertemuan <span class="text-rose-500">*</span></label>
                            <input type="date" v-model="modalJurnal.form.tanggal_kegiatan" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Pembina Pendamping</label>
                            <select v-model="modalJurnal.form.pembina_id" class="form-select text-xs rounded-xl border-slate-200 py-2 shadow-2xs font-medium cursor-pointer">
                                <option value="">— Sesuai Pembina Ekskul —</option>
                                <option v-for="p in pembinaList" :key="p.id" :value="p.id">{{ p.nama_pembina }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Jam Mulai</label>
                            <input type="time" v-model="modalJurnal.form.jam_mulai" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Jam Selesai</label>
                            <input type="time" v-model="modalJurnal.form.jam_selesai" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Lokasi / Ruangan</label>
                            <input type="text" v-model="modalJurnal.form.lokasi" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" placeholder="Contoh: Lapangan Indoor...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Jumlah Siswa Hadir</label>
                            <input type="number" v-model.number="modalJurnal.form.jumlah_hadir" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Materi / Agenda Latihan <span class="text-rose-500">*</span></label>
                            <textarea v-model="modalJurnal.form.materi_kegiatan" class="form-control text-xs rounded-xl border-slate-200 shadow-2xs" rows="3" placeholder="Tuliskan ringkasan materi atau aktivitas pertemuan..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-xs font-semibold text-slate-700 mb-1.5">Catatan Evaluasi / Kendala (Opsional)</label>
                            <input type="text" v-model="modalJurnal.form.catatan_evaluasi" class="form-control text-xs rounded-xl border-slate-200 py-2 shadow-2xs" placeholder="Catatan khusus untuk pertemuan ini...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-6 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-semibold px-4" @click="modalJurnal.show = false">Batal</button>
                    <button type="button" class="btn btn-sm text-white rounded-xl font-bold px-4 py-2 shadow-sm" style="background: #d97706;" :disabled="modalJurnal.saving" @click="submitJurnal()">
                        <span v-if="modalJurnal.saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-check2-circle me-1"></i> Simpan Jurnal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 5: IMPORT NILAI DARI EXCEL/CSV -->
    <div v-if="modalImportNilai.show" class="modal fade show block" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); z-index: 1060; backdrop-filter: blur(8px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content border-0 rounded-2xl shadow-2xl overflow-hidden">
                <div class="modal-header px-6 py-4 border-b border-slate-100 d-flex align-items-center justify-content-between bg-slate-900 text-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs">
                            <i class="bi bi-file-earmark-excel-fill text-emerald-400"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold text-white text-base mb-0">Impor Nilai Ekskul (Excel)</h5>
                            <span class="text-white/80 text-xs">Unggah file Excel (.xlsx) yang telah diisi predikat & nilai</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="modalImportNilai.show = false"></button>
                </div>
                <div class="modal-body p-6">
                    <p class="text-xs text-slate-500 mb-3" style="line-height: 1.5;">
                        Pastikan Anda mengunduh format file melalui tombol <strong>Unduh Format</strong> terlebih dahulu, lalu isi kolom Predikat, Nilai Angka, dan Keterangan tanpa mengubah kolom SISWA_ID.
                    </p>
                    <input type="file" ref="fileNilaiInput" accept=".xlsx, .xls, .csv" class="form-control text-xs rounded-xl border-slate-200 py-2.5 shadow-2xs">
                </div>
                <div class="modal-footer px-6 py-3.5 border-t border-slate-100 d-flex align-items-center justify-content-between bg-slate-50/50">
                    <button type="button" class="btn btn-sm btn-light rounded-xl font-semibold px-4" @click="modalImportNilai.show = false">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 shadow-sm" :disabled="modalImportNilai.uploading" @click="submitImportNilai()">
                        <span v-if="modalImportNilai.uploading" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-cloud-arrow-up-fill me-1"></i> Mulai Unggah
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
    const { ref, computed, onMounted, watch, nextTick } = Vue;

    window.VueAppRegistry.register('#ekskulApp', {
        setup() {
            // Global App State
            const _baseUrl = <?= json_encode($this->getBaseUrl(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const isSuperAdmin = ref(<?= json_encode($isSuperAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const tenants = ref(<?= json_encode($tenants, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const currentTenantId = ref(<?= json_encode($selectedTenantId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);

            const activeTab = ref('master');
            const loading = ref(false);
            const loadingEkskul = ref(false);
            const loadingPembina = ref(false);
            const loadingAnggota = ref(false);
            const loadingJurnal = ref(false);
            const loadingNilai = ref(false);
            const savingNilai = ref(false);

            // Master Options
            const listTahunAjaran = ref([]);
            const listKelasMaster = ref([]);
            const listGuruMaster = ref([]);
            const filterTahunAjaranId = ref('');
            const filterSemester = ref('Ganjil');
            const selectedTaName = computed(() => {
                const ta = listTahunAjaran.value.find(t => t.id === filterTahunAjaranId.value);
                return ta ? ta.nama_tahun_ajaran : '';
            });

            const stats = ref({
                total_ekskul: 0,
                total_pembina: 0,
                total_anggota: 0,
                total_jurnal: 0
            });

            // Data Collections
            const ekskulList = ref([]);
            const pembinaList = ref([]);
            const anggotaList = ref([]);
            const jurnalList = ref([]);
            const nilaiList = ref([]);
            const selectedEkskulId = ref('');

            const currentLock = ref({
                lock_anggota: false,
                lock_nilai: false,
                locked_by: null,
                locked_at: null
            });

            // Filters & Searches
            const searchEkskul = ref('');
            const filterKategoriEkskul = ref('');
            const searchPembina = ref('');
            const searchAnggota = ref('');

            // Modals State
            const modalEkskul = ref({
                show: false,
                isEdit: false,
                saving: false,
                form: {
                    id: '',
                    nama_ekskul: '',
                    kategori: 'Pilihan',
                    pembina_id: '',
                    hari_latihan: '',
                    jam_mulai: '',
                    jam_selesai: '',
                    tempat_latihan: '',
                    kuota_maksimal: 0,
                    deskripsi: '',
                    is_active: true
                }
            });

            const modalPembina = ref({
                show: false,
                isEdit: false,
                saving: false,
                selectedGuruId: '',
                form: {
                    id: '',
                    nama_pembina: '',
                    guru_id: '',
                    nip: '',
                    kategori_pembina: 'Guru Internal',
                    no_hp: '',
                    email: '',
                    is_active: true
                }
            });

            const modalAnggota = ref({
                show: false,
                saving: false,
                loadingSearch: false,
                filterKelasId: '',
                searchQuery: '',
                siswaResults: [],
                selectedSiswaIds: [],
                defaultJabatan: 'Anggota'
            });

            const modalJurnal = ref({
                show: false,
                isEdit: false,
                saving: false,
                form: {
                    id: '',
                    ekskul_id: '',
                    pembina_id: '',
                    tanggal_kegiatan: new Date().toISOString().split('T')[0],
                    jam_mulai: '',
                    jam_selesai: '',
                    lokasi: '',
                    jumlah_hadir: 0,
                    materi_kegiatan: '',
                    catatan_evaluasi: ''
                }
            });

            const modalImportNilai = ref({
                show: false,
                uploading: false
            });
            const fileNilaiInput = ref(null);

            // Computed Filtered Lists
            const filteredEkskulList = computed(() => {
                let list = ekskulList.value;
                if (filterKategoriEkskul.value) {
                    list = list.filter(e => e.kategori === filterKategoriEkskul.value);
                }
                if (searchEkskul.value.trim()) {
                    const q = searchEkskul.value.toLowerCase();
                    list = list.filter(e => (e.nama_ekskul || '').toLowerCase().includes(q) || (e.nama_pembina || '').toLowerCase().includes(q));
                }
                return list;
            });

            const filteredPembinaList = computed(() => {
                let list = pembinaList.value;
                if (searchPembina.value.trim()) {
                    const q = searchPembina.value.toLowerCase();
                    list = list.filter(p => (p.nama_pembina || '').toLowerCase().includes(q) || (p.nip || '').includes(q) || (p.no_hp || '').includes(q));
                }
                return list;
            });

            const filteredAnggotaList = computed(() => {
                let list = anggotaList.value;
                if (searchAnggota.value.trim()) {
                    const q = searchAnggota.value.toLowerCase();
                    list = list.filter(a => (a.nama_lengkap || '').toLowerCase().includes(q) || (a.nisn || '').includes(q) || (a.nama_kelas || '').toLowerCase().includes(q));
                }
                return list;
            });

            // ─── API DATA FETCHING ───────────────────────────────────
            const fetchMasterOptions = async () => {
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/options`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data.success && res.data.data) {
                        listTahunAjaran.value = res.data.data.tahun_ajaran || [];
                        listKelasMaster.value = res.data.data.kelas_list || [];
                        listGuruMaster.value = res.data.data.guru_list || [];
                        stats.value = res.data.data.stats || stats.value;

                        if (!filterTahunAjaranId.value && listTahunAjaran.value.length > 0) {
                            const active = listTahunAjaran.value.find(t => t.is_active === true || t.is_active == 1 || t.is_active === 't');
                            filterTahunAjaranId.value = active ? active.id : listTahunAjaran.value[0].id;
                        }
                    }
                } catch (e) {
                    console.error('Failed fetching master options', e);
                }
            };

            const fetchEkskul = async () => {
                loadingEkskul.value = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/master?tahun_ajaran_id=${encodeURIComponent(filterTahunAjaranId.value)}&semester=${encodeURIComponent(filterSemester.value)}`;
                    if (currentTenantId.value) url += `&tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        ekskulList.value = res.data.data || [];
                        if (!selectedEkskulId.value && ekskulList.value.length > 0) {
                            selectedEkskulId.value = ekskulList.value[0].id;
                        }
                    }
                } catch (e) {
                    console.error('Failed fetching ekskul', e);
                } finally {
                    loadingEkskul.value = false;
                }
            };

            const fetchPembina = async () => {
                loadingPembina.value = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/pembina`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        pembinaList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Failed fetching pembina', e);
                } finally {
                    loadingPembina.value = false;
                }
            };

            const fetchAnggota = async () => {
                if (!selectedEkskulId.value) return;
                loadingAnggota.value = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/anggota?ekskul_id=${encodeURIComponent(selectedEkskulId.value)}&tahun_ajaran_id=${encodeURIComponent(filterTahunAjaranId.value)}&semester=${encodeURIComponent(filterSemester.value)}`;
                    if (currentTenantId.value) url += `&tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        anggotaList.value = res.data.data || [];
                        currentLock.value = res.data.lock || currentLock.value;
                    }
                } catch (e) {
                    console.error('Failed fetching anggota', e);
                } finally {
                    loadingAnggota.value = false;
                }
            };

            const fetchJurnal = async () => {
                if (!selectedEkskulId.value) return;
                loadingJurnal.value = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/jurnal?ekskul_id=${encodeURIComponent(selectedEkskulId.value)}&tahun_ajaran_id=${encodeURIComponent(filterTahunAjaranId.value)}&semester=${encodeURIComponent(filterSemester.value)}`;
                    if (currentTenantId.value) url += `&tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        jurnalList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Failed fetching jurnal', e);
                } finally {
                    loadingJurnal.value = false;
                }
            };

            const fetchNilai = async () => {
                if (!selectedEkskulId.value) return;
                loadingNilai.value = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/nilai?ekskul_id=${encodeURIComponent(selectedEkskulId.value)}&tahun_ajaran_id=${encodeURIComponent(filterTahunAjaranId.value)}&semester=${encodeURIComponent(filterSemester.value)}`;
                    if (currentTenantId.value) url += `&tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        nilaiList.value = res.data.data || [];
                        currentLock.value = res.data.lock || currentLock.value;
                    }
                } catch (e) {
                    console.error('Failed fetching nilai', e);
                } finally {
                    loadingNilai.value = false;
                }
            };

            const refreshAll = async () => {
                loading.value = true;
                await fetchMasterOptions();
                await Promise.all([
                    fetchEkskul(),
                    fetchPembina()
                ]);
                if (selectedEkskulId.value) {
                    if (activeTab.value === 'anggota') fetchAnggota();
                    else if (activeTab.value === 'jurnal') fetchJurnal();
                    else if (activeTab.value === 'nilai') fetchNilai();
                }
                loading.value = false;
            };

            const onTenantChange = async () => {
                selectedEkskulId.value = '';
                await refreshAll();
            };

            const onAcademicFilterChange = async () => {
                await fetchEkskul();
                if (activeTab.value === 'anggota') fetchAnggota();
                else if (activeTab.value === 'jurnal') fetchJurnal();
                else if (activeTab.value === 'nilai') fetchNilai();
            };

            const switchTab = (tab) => {
                activeTab.value = tab;
                if (tab === 'master') fetchEkskul();
                else if (tab === 'pembina') fetchPembina();
                else if (tab === 'anggota') fetchAnggota();
                else if (tab === 'jurnal') fetchJurnal();
                else if (tab === 'nilai') fetchNilai();
            };

            const selectEkskulForTab = (ekskulId, tab) => {
                selectedEkskulId.value = ekskulId;
                switchTab(tab);
            };

            // ─── AUTO-RELOAD WATCHERS ────────────────────────────────
            // 1. Auto reload on tab change
            watch(activeTab, (newTab) => {
                if (newTab === 'master') fetchEkskul();
                else if (newTab === 'pembina') fetchPembina();
                else if (newTab === 'anggota') fetchAnggota();
                else if (newTab === 'jurnal') fetchJurnal();
                else if (newTab === 'nilai') fetchNilai();
            });

            // 2. Auto reload on Academic Period change (Tahun Ajaran / Semester)
            watch([filterTahunAjaranId, filterSemester], () => {
                fetchEkskul();
                if (activeTab.value === 'anggota') fetchAnggota();
                else if (activeTab.value === 'jurnal') fetchJurnal();
                else if (activeTab.value === 'nilai') fetchNilai();
            });

            // 3. Auto reload on Selected Ekskul change
            watch(selectedEkskulId, (newId) => {
                if (newId) {
                    if (activeTab.value === 'anggota') fetchAnggota();
                    else if (activeTab.value === 'jurnal') fetchJurnal();
                    else if (activeTab.value === 'nilai') fetchNilai();
                }
            });

            // ─── MASTER EKSKUL ACTIONS ───────────────────────────────
            const openModalEkskul = (item = null) => {
                if (item) {
                    modalEkskul.value.isEdit = true;
                    modalEkskul.value.form = { ...item };
                } else {
                    modalEkskul.value.isEdit = false;
                    modalEkskul.value.form = {
                        id: '',
                        nama_ekskul: '',
                        kategori: 'Pilihan',
                        pembina_id: '',
                        hari_latihan: '',
                        jam_mulai: '15:00',
                        jam_selesai: '17:00',
                        tempat_latihan: '',
                        kuota_maksimal: 0,
                        deskripsi: '',
                        is_active: true
                    };
                }
                modalEkskul.value.show = true;
            };

            const editEkskul = (item) => openModalEkskul(item);

            const submitEkskul = async () => {
                if (!modalEkskul.value.form.nama_ekskul.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Nama ekstrakurikuler wajib diisi.' });
                    return;
                }
                modalEkskul.value.saving = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/master`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, modalEkskul.value.form);
                    if (res.data.success) {
                        modalEkskul.value.show = false;
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message || 'Ekstrakurikuler berhasil disimpan.' });
                        await fetchEkskul();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Terjadi kesalahan.' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                } finally {
                    modalEkskul.value.saving = false;
                }
            };

            const toggleStatusEkskul = async (item) => {
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/master/toggle-status`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, { id: item.id, is_active: !item.is_active });
                    if (res.data.success) {
                        item.is_active = !item.is_active;
                    }
                } catch (e) {
                    console.error('Toggle status error', e);
                }
            };

            const deleteEkskul = async (item) => {
                const conf = await Swal.fire({
                    icon: 'warning',
                    title: 'Nonaktifkan / Hapus Ekskul?',
                    text: `Yakin ingin menghapus ekstrakurikuler "${item.nama_ekskul}"?`,
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Ya, Hapus'
                });
                if (!conf.isConfirmed) return;

                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/master/delete`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, { id: item.id });
                    if (res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Ekskul berhasil dinonaktifkan.' });
                        await fetchEkskul();
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                }
            };

            // ─── DATA PEMBINA ACTIONS ────────────────────────────────
            const openModalPembina = (item = null) => {
                modalPembina.value.selectedGuruId = '';
                if (item) {
                    modalPembina.value.isEdit = true;
                    modalPembina.value.form = { ...item };
                } else {
                    modalPembina.value.isEdit = false;
                    modalPembina.value.form = {
                        id: '',
                        nama_pembina: '',
                        guru_id: '',
                        nip: '',
                        kategori_pembina: 'Guru Internal',
                        no_hp: '',
                        email: '',
                        is_active: true
                    };
                }
                modalPembina.value.show = true;
            };

            const onSelectGuruPembina = () => {
                const gid = modalPembina.value.selectedGuruId;
                if (!gid) return;
                const guru = listGuruMaster.value.find(g => g.id === gid);
                if (guru) {
                    modalPembina.value.form.nama_pembina = guru.nama_lengkap;
                    modalPembina.value.form.guru_id = guru.id;
                    modalPembina.value.form.nip = guru.nip !== '—' ? guru.nip : '';
                    modalPembina.value.form.no_hp = guru.no_hp || '';
                    modalPembina.value.form.email = guru.email || '';
                    modalPembina.value.form.kategori_pembina = 'Guru Internal';
                }
            };

            const editPembina = (item) => openModalPembina(item);

            const submitPembina = async () => {
                if (!modalPembina.value.form.nama_pembina.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Nama pembina wajib diisi.' });
                    return;
                }
                modalPembina.value.saving = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/pembina`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, modalPembina.value.form);
                    if (res.data.success) {
                        modalPembina.value.show = false;
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message || 'Pembina berhasil disimpan.' });
                        await fetchPembina();
                        await fetchEkskul();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Terjadi kesalahan.' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                } finally {
                    modalPembina.value.saving = false;
                }
            };

            const toggleStatusPembina = async (item) => {
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/pembina/toggle-status`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, { id: item.id, is_active: !item.is_active });
                    if (res.data.success) {
                        item.is_active = !item.is_active;
                    }
                } catch (e) {
                    console.error('Toggle pembina status error', e);
                }
            };

            const deletePembina = async (item) => {
                const conf = await Swal.fire({
                    icon: 'warning',
                    title: 'Hapus Pembina?',
                    text: `Yakin ingin menghapus pembina "${item.nama_pembina}"?`,
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Ya, Hapus'
                });
                if (!conf.isConfirmed) return;

                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/pembina/delete`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, { id: item.id });
                    if (res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Pembina berhasil dihapus.' });
                        await fetchPembina();
                        await fetchEkskul();
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                }
            };

            // ─── ANGGOTA ACTIONS ─────────────────────────────────────
            const openModalTambahAnggota = () => {
                modalAnggota.value.filterKelasId = '';
                modalAnggota.value.searchQuery = '';
                modalAnggota.value.selectedSiswaIds = [];
                modalAnggota.value.defaultJabatan = 'Anggota';
                modalAnggota.value.show = true;
                searchSiswaForModal();
            };

            const searchSiswaForModal = async () => {
                modalAnggota.value.loadingSearch = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/anggota/search-siswa?ekskul_id=${encodeURIComponent(selectedEkskulId.value)}&tahun_ajaran_id=${encodeURIComponent(filterTahunAjaranId.value)}&semester=${encodeURIComponent(filterSemester.value)}`;
                    if (modalAnggota.value.filterKelasId) url += `&kelas_id=${encodeURIComponent(modalAnggota.value.filterKelasId)}`;
                    if (modalAnggota.value.searchQuery) url += `&q=${encodeURIComponent(modalAnggota.value.searchQuery)}`;
                    if (currentTenantId.value) url += `&tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data.success) {
                        modalAnggota.value.siswaResults = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Failed searching siswa', e);
                } finally {
                    modalAnggota.value.loadingSearch = false;
                }
            };

            const toggleSelectSiswa = (siswaId) => {
                const idx = modalAnggota.value.selectedSiswaIds.indexOf(siswaId);
                if (idx > -1) {
                    modalAnggota.value.selectedSiswaIds.splice(idx, 1);
                } else {
                    modalAnggota.value.selectedSiswaIds.push(siswaId);
                }
            };

            const submitAnggota = async () => {
                if (modalAnggota.value.selectedSiswaIds.length === 0) return;
                modalAnggota.value.saving = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/anggota/tambah`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, {
                        ekskul_id: selectedEkskulId.value,
                        tahun_ajaran_id: filterTahunAjaranId.value,
                        semester: filterSemester.value,
                        siswa_ids: modalAnggota.value.selectedSiswaIds,
                        jabatan: modalAnggota.value.defaultJabatan
                    });
                    if (res.data.success) {
                        modalAnggota.value.show = false;
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message || 'Anggota berhasil ditambahkan.' });
                        await fetchAnggota();
                        await fetchEkskul();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menambahkan anggota.' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                } finally {
                    modalAnggota.value.saving = false;
                }
            };

            const removeAnggota = async (mem) => {
                const conf = await Swal.fire({
                    icon: 'warning',
                    title: 'Keluarkan Anggota?',
                    text: `Yakin ingin mengeluarkan "${mem.nama_lengkap}" dari ekstrakurikuler ini?`,
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Ya, Keluarkan'
                });
                if (!conf.isConfirmed) return;

                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/anggota/hapus`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, { id: mem.id });
                    if (res.data.success) {
                        await fetchAnggota();
                        await fetchEkskul();
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                }
            };

            const getExportAnggotaUrl = () => {
                if (!selectedEkskulId.value) return '#';
                let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/anggota/export?ekskul_id=${encodeURIComponent(selectedEkskulId.value)}&tahun_ajaran_id=${encodeURIComponent(filterTahunAjaranId.value)}&semester=${encodeURIComponent(filterSemester.value)}`;
                if (currentTenantId.value) url += `&tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                return url;
            };

            // ─── JURNAL ACTIONS ──────────────────────────────────────
            const openModalJurnal = (item = null) => {
                if (item) {
                    modalJurnal.value.isEdit = true;
                    modalJurnal.value.form = { ...item };
                } else {
                    modalJurnal.value.isEdit = false;
                    modalJurnal.value.form = {
                        id: '',
                        ekskul_id: selectedEkskulId.value,
                        pembina_id: '',
                        tahun_ajaran_id: filterTahunAjaranId.value,
                        semester: filterSemester.value,
                        tanggal_kegiatan: new Date().toISOString().split('T')[0],
                        jam_mulai: '15:00',
                        jam_selesai: '17:00',
                        lokasi: '',
                        jumlah_hadir: anggotaList.value.length,
                        materi_kegiatan: '',
                        catatan_evaluasi: ''
                    };
                }
                modalJurnal.value.show = true;
            };

            const editJurnal = (item) => openModalJurnal(item);

            const submitJurnal = async () => {
                if (!modalJurnal.value.form.materi_kegiatan.trim()) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Materi kegiatan wajib diisi.' });
                    return;
                }
                modalJurnal.value.saving = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/jurnal`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    modalJurnal.value.form.ekskul_id = selectedEkskulId.value;
                    modalJurnal.value.form.tahun_ajaran_id = filterTahunAjaranId.value;
                    modalJurnal.value.form.semester = filterSemester.value;

                    const res = await axios.post(url, modalJurnal.value.form);
                    if (res.data.success) {
                        modalJurnal.value.show = false;
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message || 'Jurnal berhasil disimpan.' });
                        await fetchJurnal();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Terjadi kesalahan.' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                } finally {
                    modalJurnal.value.saving = false;
                }
            };

            const deleteJurnal = async (item) => {
                const conf = await Swal.fire({
                    icon: 'warning',
                    title: 'Hapus Jurnal?',
                    text: 'Yakin ingin menghapus catatan pertemuan ini?',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Ya, Hapus'
                });
                if (!conf.isConfirmed) return;

                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/jurnal/delete`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, { id: item.id });
                    if (res.data.success) {
                        await fetchJurnal();
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                }
            };

            // ─── PENILAIAN ACTIONS ───────────────────────────────────
            const autofillPredikat = (predikat) => {
                nilaiList.value.forEach(n => {
                    n.predikat = predikat;
                    if (predikat === 'A' && (!n.keterangan || n.keterangan.trim() === '')) {
                        n.keterangan = 'Sangat aktif dan menunjukkan penguasaan materi dengan sangat baik.';
                    } else if (predikat === 'B' && (!n.keterangan || n.keterangan.trim() === '')) {
                        n.keterangan = 'Aktif dan mampu mempraktikkan keterampilan dengan baik.';
                    }
                });
            };

            const saveAllNilai = async () => {
                if (nilaiList.value.length === 0) return;
                savingNilai.value = true;
                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/nilai/simpan`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, {
                        ekskul_id: selectedEkskulId.value,
                        tahun_ajaran_id: filterTahunAjaranId.value,
                        semester: filterSemester.value,
                        grades: nilaiList.value
                    });
                    if (res.data.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.data.message || 'Penilaian berhasil disimpan.' });
                        await fetchNilai();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal menyimpan nilai.' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                } finally {
                    savingNilai.value = false;
                }
            };

            const toggleLock = async (type) => {
                if (!selectedEkskulId.value) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih ekstrakurikuler terlebih dahulu.' });
                    return;
                }
                if (!filterTahunAjaranId.value) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tahun ajaran belum dipilih.' });
                    return;
                }

                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/lock`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, {
                        ekskul_id: selectedEkskulId.value,
                        tahun_ajaran_id: filterTahunAjaranId.value,
                        semester: filterSemester.value,
                        type: type
                    });
                    if (res.data.success) {
                        currentLock.value = res.data.lock || currentLock.value;
                        const isLocked = type === 'anggota' ? currentLock.value.lock_anggota : currentLock.value.lock_nilai;
                        Swal.fire({
                            icon: isLocked ? 'warning' : 'success',
                            title: isLocked ? 'Status Dikunci' : 'Status Dibuka',
                            text: isLocked 
                                ? (type === 'anggota' ? 'Pendaftaran anggota berhasil dikunci. Tambah & keluarkan anggota dinonaktifkan.' : 'Penilaian e-rapor berhasil dikunci.') 
                                : (type === 'anggota' ? 'Pendaftaran anggota berhasil dibuka kembali.' : 'Penilaian e-rapor berhasil dibuka kembali.'),
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal mengubah status penguncian.' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                }
            };

            const getExportNilaiUrl = () => {
                if (!selectedEkskulId.value) return '#';
                let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/nilai/export?ekskul_id=${encodeURIComponent(selectedEkskulId.value)}&tahun_ajaran_id=${encodeURIComponent(filterTahunAjaranId.value)}&semester=${encodeURIComponent(filterSemester.value)}`;
                if (currentTenantId.value) url += `&tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                return url;
            };

            const openModalImportNilai = () => {
                if (!selectedEkskulId.value) {
                    Swal.fire({ icon: 'warning', title: 'Pilih Ekskul', text: 'Silakan pilih ekstrakurikuler terlebih dahulu.' });
                    return;
                }
                modalImportNilai.value.show = true;
            };

            const submitImportNilai = async () => {
                if (!fileNilaiInput.value || !fileNilaiInput.value.files[0]) {
                    Swal.fire({ icon: 'warning', title: 'File Kosong', text: 'Pilih berkas CSV atau Excel terlebih dahulu.' });
                    return;
                }

                modalImportNilai.value.uploading = true;
                const formData = new FormData();
                formData.append('ekskul_id', selectedEkskulId.value);
                formData.append('tahun_ajaran_id', filterTahunAjaranId.value);
                formData.append('semester', filterSemester.value);
                formData.append('file_nilai', fileNilaiInput.value.files[0]);

                try {
                    let url = `${_baseUrl}/api/v1/kesiswaan/ekskul/nilai/import`;
                    if (currentTenantId.value) url += `?tenant_id=${encodeURIComponent(currentTenantId.value)}`;
                    const res = await axios.post(url, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                    if (res.data.success) {
                        modalImportNilai.value.show = false;
                        Swal.fire({ icon: 'success', title: 'Berhasil Impor', text: res.data.message || 'Nilai siswa berhasil diimpor.' });
                        await fetchNilai();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal mengimpor nilai.' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.error || e.message });
                } finally {
                    modalImportNilai.value.uploading = false;
                }
            };

            // ─── UI HELPERS ──────────────────────────────────────────
            const getKategoriDisplay = (kat, nama = '') => {
                if (kat && typeof kat === 'string' && kat.trim() !== '') {
                    return kat.trim();
                }
                const n = (nama || '').toLowerCase();
                if (n.includes('rohis') || n.includes('islam') || n.includes('agama') || n.includes('tahsin') || n.includes('quran')) return 'Keagamaan';
                if (n.includes('paskibra') || n.includes('pramuka') || n.includes('paspara')) return 'Kepanduan & Bela Negara';
                if (n.includes('basket') || n.includes('futsal') || n.includes('voli') || n.includes('badminton') || n.includes('silat') || n.includes('taekwondo')) return 'Olahraga';
                if (n.includes('tari') || n.includes('musik') || n.includes('band') || n.includes('paduan suara') || n.includes('teater') || n.includes('lukis')) return 'Seni & Budaya';
                if (n.includes('robotik') || n.includes('coding') || n.includes('komputer') || n.includes('ai')) return 'Teknologi & Robotik';
                if (n.includes('debate') || n.includes('speech') || n.includes('english') || n.includes('sains') || n.includes('matematika') || n.includes('olimpiade')) return 'Akademik / Sains';
                return 'Pilihan';
            };

            const getEkskulCategoryIcon = (kat, nama = '') => {
                const k = getKategoriDisplay(kat, nama).toLowerCase();
                if (k.includes('wajib')) return 'bi-shield-check';
                if (k.includes('olahraga')) return 'bi-trophy-fill';
                if (k.includes('seni')) return 'bi-palette-fill';
                if (k.includes('keagamaan') || k.includes('agama') || k.includes('rohis')) return 'bi-heart-pulse-fill';
                if (k.includes('sains') || k.includes('akademik')) return 'bi-lightbulb-fill';
                if (k.includes('kepanduan') || k.includes('bela negara') || k.includes('paskibra') || k.includes('pramuka')) return 'bi-compass-fill';
                if (k.includes('teknologi') || k.includes('robotik') || k.includes('it') || k.includes('ai')) return 'bi-cpu-fill';
                return 'bi-award-fill';
            };

            const getEkskulIconStyle = (kat, nama = '') => {
                const k = getKategoriDisplay(kat, nama).toLowerCase();
                if (k.includes('wajib')) return 'background: linear-gradient(135deg, #e11d48, #f43f5e);';
                if (k.includes('olahraga')) return 'background: linear-gradient(135deg, #059669, #10b981);';
                if (k.includes('seni')) return 'background: linear-gradient(135deg, #7c3aed, #a855f7);';
                if (k.includes('keagamaan') || k.includes('agama') || k.includes('rohis')) return 'background: linear-gradient(135deg, #0284c7, #38bdf8);';
                if (k.includes('sains') || k.includes('akademik')) return 'background: linear-gradient(135deg, #4f46e5, #6366f1);';
                if (k.includes('kepanduan') || k.includes('bela negara') || k.includes('paskibra') || k.includes('pramuka')) return 'background: linear-gradient(135deg, #d97706, #f59e0b);';
                if (k.includes('teknologi') || k.includes('robotik') || k.includes('it') || k.includes('ai')) return 'background: linear-gradient(135deg, #0f766e, #14b8a6);';
                return 'background: linear-gradient(135deg, #2563eb, #3b82f6);';
            };

            const getKategoriBadgeStyle = (kat, nama = '') => {
                const display = getKategoriDisplay(kat, nama);
                const k = display.toLowerCase();
                if (k.includes('keagamaan') || k.includes('agama') || k.includes('rohis')) {
                    return 'background-color: #f0f9ff !important; color: #0369a1 !important; border: 1px solid #bae6fd !important; font-weight: 700;';
                }
                if (k.includes('sains') || k.includes('akademik')) {
                    return 'background-color: #eef2ff !important; color: #4338ca !important; border: 1px solid #c7d2fe !important; font-weight: 700;';
                }
                if (k.includes('kepanduan') || k.includes('bela negara') || k.includes('paskibra') || k.includes('pramuka')) {
                    return 'background-color: #fffbeb !important; color: #92400e !important; border: 1px solid #fde68a !important; font-weight: 700;';
                }
                if (k.includes('olahraga')) {
                    return 'background-color: #ecfdf5 !important; color: #047857 !important; border: 1px solid #a7f3d0 !important; font-weight: 700;';
                }
                if (k.includes('seni')) {
                    return 'background-color: #faf5ff !important; color: #7e22ce !important; border: 1px solid #e9d5ff !important; font-weight: 700;';
                }
                if (k.includes('teknologi') || k.includes('robotik')) {
                    return 'background-color: #f0fdfa !important; color: #0f766e !important; border: 1px solid #99f6e4 !important; font-weight: 700;';
                }
                if (k.includes('wajib')) {
                    return 'background-color: #fff1f2 !important; color: #be123c !important; border: 1px solid #fecdd3 !important; font-weight: 700;';
                }
                return 'background-color: #eff6ff !important; color: #1d4ed8 !important; border: 1px solid #bfdbfe !important; font-weight: 700;';
            };

            const getKategoriBadgeClass = (kat, nama = '') => {
                const display = getKategoriDisplay(kat, nama);
                const k = display.toLowerCase();
                if (k.includes('wajib')) return 'bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs';
                if (k.includes('olahraga')) return 'bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs';
                if (k.includes('seni')) return 'bg-purple-50 text-purple-700 border border-purple-200 shadow-2xs';
                if (k.includes('keagamaan') || k.includes('agama') || k.includes('rohis')) return 'bg-sky-50 text-sky-700 border border-sky-200 shadow-2xs';
                if (k.includes('sains') || k.includes('akademik')) return 'bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs';
                if (k.includes('kepanduan') || k.includes('bela negara') || k.includes('paskibra') || k.includes('pramuka')) return 'bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs';
                if (k.includes('teknologi') || k.includes('robotik') || k.includes('it') || k.includes('ai')) return 'bg-teal-50 text-teal-700 border border-teal-200 shadow-2xs';
                if (k.includes('pilihan')) return 'bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs';
                return 'bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs';
            };

            const getJabatanBadgeStyle = (jab) => {
                const j = (jab || '').trim().toLowerCase();
                if (j === 'ketua') {
                    return 'background-color: #ffe4e6 !important; color: #9f1239 !important; border: 1px solid #fecdd3 !important; font-weight: 700;';
                }
                if (j.includes('wakil')) {
                    return 'background-color: #fef3c7 !important; color: #92400e !important; border: 1px solid #fde68a !important; font-weight: 700;';
                }
                if (j.includes('sekretaris')) {
                    return 'background-color: #e0e7ff !important; color: #3730a3 !important; border: 1px solid #c7d2fe !important; font-weight: 700;';
                }
                if (j.includes('bendahara')) {
                    return 'background-color: #d1fae5 !important; color: #065f46 !important; border: 1px solid #a7f3d0 !important; font-weight: 700;';
                }
                return 'background-color: #f1f5f9 !important; color: #475569 !important; border: 1px solid #e2e8f0 !important; font-weight: 600;';
            };

            const getJabatanIcon = (jab) => {
                const j = (jab || '').trim().toLowerCase();
                if (j === 'ketua') return 'bi-award-fill text-rose-600';
                if (j.includes('wakil')) return 'bi-star-half text-amber-600';
                if (j.includes('sekretaris')) return 'bi-pencil-square text-indigo-600';
                if (j.includes('bendahara')) return 'bi-wallet2 text-emerald-600';
                return 'bi-person-fill text-slate-400';
            };

            const getJabatanBadgeClass = (jab) => {
                return '';
            };

            const getPredikatSelectClass = (pred) => {
                if (pred === 'A') return 'bg-emerald-50 text-emerald-800 border-emerald-300';
                if (pred === 'B') return 'bg-blue-50 text-blue-800 border-blue-300';
                if (pred === 'C') return 'bg-amber-50 text-amber-800 border-amber-300';
                if (pred === 'D') return 'bg-rose-50 text-rose-800 border-rose-300';
                return '';
            };

            const formatDateIndo = (dateStr) => {
                if (!dateStr) return '—';
                try {
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                } catch(e) { return dateStr; }
            };

            // Lifecycle Mounted
            onMounted(() => {
                refreshAll();
            });

            return {
                isSuperAdmin,
                tenants,
                currentTenantId,
                activeTab,
                loading,
                loadingEkskul,
                loadingPembina,
                loadingAnggota,
                loadingJurnal,
                loadingNilai,
                savingNilai,
                listTahunAjaran,
                listKelasMaster,
                listGuruMaster,
                filterTahunAjaranId,
                filterSemester,
                selectedTaName,
                stats,
                ekskulList,
                pembinaList,
                anggotaList,
                jurnalList,
                nilaiList,
                selectedEkskulId,
                currentLock,
                searchEkskul,
                filterKategoriEkskul,
                searchPembina,
                searchAnggota,
                modalEkskul,
                modalPembina,
                modalAnggota,
                modalJurnal,
                modalImportNilai,
                fileNilaiInput,
                filteredEkskulList,
                filteredPembinaList,
                filteredAnggotaList,
                fetchAnggota,
                fetchJurnal,
                fetchNilai,
                refreshAll,
                switchTab,
                onTenantChange,
                onAcademicFilterChange,
                selectEkskulForTab,
                openModalEkskul,
                editEkskul,
                submitEkskul,
                toggleStatusEkskul,
                deleteEkskul,
                openModalPembina,
                onSelectGuruPembina,
                editPembina,
                submitPembina,
                toggleStatusPembina,
                deletePembina,
                openModalTambahAnggota,
                searchSiswaForModal,
                toggleSelectSiswa,
                submitAnggota,
                removeAnggota,
                getExportAnggotaUrl,
                openModalJurnal,
                editJurnal,
                submitJurnal,
                deleteJurnal,
                autofillPredikat,
                saveAllNilai,
                toggleLock,
                getExportNilaiUrl,
                openModalImportNilai,
                submitImportNilai,
                getKategoriDisplay,
                getEkskulCategoryIcon,
                getEkskulIconStyle,
                getKategoriBadgeStyle,
                getKategoriBadgeClass,
                getJabatanBadgeStyle,
                getJabatanIcon,
                getJabatanBadgeClass,
                getPredikatSelectClass,
                formatDateIndo
            };
        }
    });
}
</script>

<style>
/* Navigation Tabs Styling (Identical to BK Kedisiplinan) */
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
    white-space: nowrap !important;
    display: inline-flex;
    align-items: center;
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
