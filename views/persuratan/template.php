<?php
/**
 * View: Manajemen Master Template Naskah Dinas Sekolah
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
$activeMenu = 'template';
$pageTitle = 'Template Naskah Dinas Sekolah';
$pageSubtitle = 'Standarisasi format surat dinas resmi, pola penomoran otomatis, editor naskah visual Word, dan placeholder variabel cerdas naskah sekolah.';
$pageIcon = 'bi-file-earmark-text-fill';
?>
<div id="persuratanTemplateApp" v-cloak class="container-fluid px-0">
    <!-- Hero Banner Header Mandiri -->
    <?php 
    $heroBadge = 'Master Template Surat';
    $pageTitle = 'Generator & Template Naskah Dinas';
    $pageSubtitle = 'Standarisasi format surat dinas resmi, pola penomoran otomatis, editor naskah visual Word, dan placeholder variabel cerdas.';
    $pageIcon = 'bi-file-earmark-richtext-fill';
    $heroButtons = '
        <button type="button" class="btn btn-sm rounded-xl px-3.5 py-2 text-xs font-bold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all d-inline-flex align-items-center gap-1.5 backdrop-blur-md" onclick="window.persuratanTemplateOpenTambah && window.persuratanTemplateOpenTambah()">
            <i class="bi bi-plus-circle-fill"></i> Tambah Template
        </button>
    ';
    include __DIR__ . '/_hero_header.php'; 
    ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         NAVIGASI TAB STANDAR SINTA SAAS (AGENTS.MD)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
        <div class="d-flex align-items-center position-relative">
            <!-- 1 Tombol Panah Kiri -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('templateNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Container Deretan Tab -->
            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="templateNavTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'katalog'}" 
                                @click="activeTab = 'katalog'">
                            <i class="bi bi-grid-fill me-2 fs-6 text-primary"></i> 1. Katalog Template Naskah
                            <span class="badge bg-slate-100 text-slate-700 ms-2 rounded-pill text-[11px]">{{ templates.length }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'placeholder'}" 
                                @click="activeTab = 'placeholder'">
                            <i class="bi bi-code-square me-2 fs-6 text-indigo-500"></i> 2. Kamus Placeholder Variabel
                        </button>
                    </li>
                </ul>
            </div>

            <!-- 1 Tombol Panah Kanan -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('templateNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Tombol Aksi Tambahan / Segarkan Data -->
            <div class="d-none d-md-flex align-items-center ps-2 pe-1 border-s border-slate-200/80 ms-2 gap-2">
                <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl px-3 py-2 text-xs font-bold shadow-2xs d-flex align-items-center gap-1.5" @click="fetchTemplates" title="Segarkan Data">
                    <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i>
                    <span>Segarkan</span>
                </button>
                <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalTemplate()" title="Tambah Template Naskah">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Tambah Template</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TAB 1: KATALOG TEMPLATE NASKAH -->
    <div v-show="activeTab === 'katalog'" class="card border border-slate-200/80 shadow-2xs rounded-3xl bg-white overflow-hidden mb-5">
        <!-- ═══ ENTERPRISE TOOLBAR FILTER & ACTIONS ═══ -->
        <div class="p-3.5 p-md-4 border-b border-slate-200/80 bg-slate-50/80">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <!-- Sisi Kiri: Search Input & Filter Klasifikasi -->
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <!-- Search Input -->
                    <div class="position-relative" style="min-width: 280px; max-width: 420px; flex-grow: 1;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3.5 text-blue-500 fs-7 pointer-events-none"></i>
                        <input type="text" v-model="search" class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-semibold bg-white shadow-2xs" placeholder="Cari judul template, perihal, atau kode...">
                        <button v-if="search" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 border-0 bg-transparent p-0" @click="search = ''">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>

                    <!-- Searchable Custom Dropdown Filter Klasifikasi -->
                    <div class="dropdown position-relative">
                        <button type="button" 
                                class="btn btn-sm btn-white border border-slate-200 rounded-xl text-xs font-semibold shadow-2xs d-inline-flex align-items-center justify-content-between gap-2 bg-white text-slate-700 hover:bg-slate-50 transition" 
                                style="min-width: 220px; max-width: 320px;"
                                id="dropdownFilterKlas" 
                                data-bs-toggle="dropdown" 
                                data-bs-auto-close="outside" 
                                aria-expanded="false">
                            <div class="d-flex align-items-center gap-1.5 text-truncate">
                                <i class="bi bi-tag-fill text-indigo-500 fs-7"></i>
                                <span v-if="selectedKlasObj" class="text-truncate font-bold text-slate-800">
                                    <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono me-1">{{ selectedKlasObj.kode_klasifikasi }}</span>
                                    {{ selectedKlasObj.nama_klasifikasi }}
                                </span>
                                <span v-else class="text-slate-500">— Semua Klasifikasi —</span>
                            </div>
                            <i class="bi bi-chevron-down text-slate-400 fs-8 ms-1"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-2xl rounded-2xl border border-slate-200 p-2 text-xs" style="width: 360px; max-width: 90vw; z-index: 1050;">
                            <!-- Search Box di dalam dropdown -->
                            <div class="position-relative mb-2">
                                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2.5 text-slate-400 fs-7"></i>
                                <input type="text" 
                                       v-model="searchFilterKlasText" 
                                       class="form-control form-control-sm ps-4 pe-4 rounded-xl border border-slate-200 text-xs font-semibold bg-slate-50 focus:bg-white" 
                                       placeholder="Cari kode (cth 421) atau nama..."
                                       @click.stop>
                                <button v-if="searchFilterKlasText" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 border-0 bg-transparent p-0" @click.stop="searchFilterKlasText = ''">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>
                            
                            <!-- List Options -->
                            <div class="overflow-y-auto" style="max-height: 250px;">
                                <div class="dropdown-item rounded-xl py-2 px-2.5 cursor-pointer d-flex align-items-center justify-content-between mb-1" 
                                     :class="{'bg-blue-50 text-blue-700 font-bold': !filterKlas}" 
                                     @click="filterKlas = ''; closeDropdown('dropdownFilterKlas')">
                                    <span>— Tampilkan Semua Klasifikasi —</span>
                                    <i v-if="!filterKlas" class="bi bi-check2 text-blue-600 fs-6"></i>
                                </div>
                                <div v-for="k in filteredKlasOptions" :key="k.id" 
                                     class="dropdown-item rounded-xl py-2 px-2.5 cursor-pointer d-flex align-items-center justify-content-between mb-1" 
                                     :class="{'bg-indigo-50 text-indigo-900 font-bold': filterKlas === k.id}" 
                                     @click="filterKlas = k.id; closeDropdown('dropdownFilterKlas')">
                                    <div class="d-flex flex-column text-truncate me-2">
                                        <div class="d-flex align-items-center gap-1.5 text-truncate">
                                            <span class="badge bg-white border border-slate-200 text-indigo-700 font-mono text-[10px]">{{ k.kode_klasifikasi }}</span>
                                            <span class="text-truncate">{{ k.nama_klasifikasi }}</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 mt-0.5" v-if="k.kategori_utama">{{ k.kategori_utama }}</span>
                                    </div>
                                    <i v-if="filterKlas === k.id" class="bi bi-check2 text-indigo-600 fs-6 flex-shrink-0"></i>
                                </div>
                                <div v-if="filteredKlasOptions.length === 0" class="text-center py-3 text-slate-400 text-xs">
                                    Tidak ditemukan kode "{{ searchFilterKlasText }}"
                                </div>
                            </div>
                        </div>
                    </div>

                    <button v-if="search || filterKlas" type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl text-xs font-bold px-3 py-1.5 shadow-2xs text-slate-600 hover:bg-slate-100 d-flex align-items-center gap-1" @click="search = ''; filterKlas = ''; searchFilterKlasText = ''">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>

                <!-- Sisi Kanan: Counter & Tombol Tambah Template -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="badge bg-white border border-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-xs shadow-2xs d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-collection-fill text-blue-600"></i>
                        <span>Total: <strong class="text-blue-700">{{ filteredTemplates.length }}</strong> Template</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-2xs d-flex align-items-center gap-1.5 transition hover:shadow-xs" @click="openModalTemplate()">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Tambah Template</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ CATALOG GRID CARDS ═══ -->
        <div class="p-4 bg-slate-50/40">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat katalog template naskah dinas...
            </div>

            <div v-else-if="filteredTemplates.length === 0" class="text-center py-5 px-3">
                <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-500 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-inner">
                    <i class="bi bi-file-earmark-richtext"></i>
                </div>
                <div class="font-bold text-slate-800 text-base mb-1">Tidak Ada Template yang Cocok</div>
                <p class="text-slate-500 text-xs mb-3 mx-auto" style="max-width: 440px;">
                    {{ search || filterKlas ? 'Tidak ada format template naskah yang sesuai dengan kriteria pencarian.' : 'Belum ada template naskah surat yang dibuat. Klik tombol di bawah untuk membuat template naskah baru.' }}
                </p>
                <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 text-xs shadow-2xs" @click="search = ''; filterKlas = ''; openModalTemplate()">
                    <i class="bi bi-plus-circle-fill me-1"></i> Buat Template Sekarang
                </button>
            </div>

            <div v-else class="row g-3.5">
                <div v-for="t in filteredTemplates" :key="t.id" class="col-12 col-md-6 col-xl-4">
                    <div class="card border border-slate-200/90 shadow-2xs rounded-3xl bg-white h-100 d-flex flex-column transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-blue-400/80 overflow-hidden">
                        
                        <!-- Top Header Accent Bar -->
                        <div class="p-3 px-3.5 bg-slate-50/90 border-b border-slate-100 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white d-flex align-items-center justify-content-center fs-6 shadow-xs flex-shrink-0">
                                    <i class="bi bi-file-earmark-word-fill"></i>
                                </div>
                                <span class="badge bg-white border border-slate-200 text-blue-700 font-mono rounded-lg px-2.5 py-1 text-[10px] font-bold shadow-2xs">
                                    {{ t.kode_klasifikasi || 'KODE-UMUM' }}
                                </span>
                            </div>
                            <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2 py-0.5 rounded-pill text-[9px] d-inline-flex align-items-center gap-1">
                                <i class="bi bi-check-circle-fill text-[8px]"></i> Ready Word
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4 d-flex flex-column flex-grow-1">
                            <!-- Template Title -->
                            <h6 class="font-extrabold text-slate-900 fs-7 mb-1 line-clamp-2" style="min-height: 38px; line-height: 1.4;">
                                {{ t.nama_template_surat }}
                            </h6>

                            <!-- Perihal Sub-info -->
                            <div class="text-[11px] text-slate-500 mb-3 d-flex align-items-center gap-1.5 line-clamp-1">
                                <i class="bi bi-bookmark-fill text-blue-500 flex-shrink-0"></i>
                                <span>Perihal: <strong class="text-slate-700 font-semibold">{{ t.perihal_default || '-' }}</strong></span>
                            </div>
                            
                            <!-- Mini Document Sheet Preview -->
                            <div class="bg-slate-50/90 border border-slate-200/80 rounded-2xl p-3 mb-3.5 flex-grow-1 position-relative overflow-hidden border-start border-4 border-blue-600">
                                <div class="d-flex align-items-center justify-content-between text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1.5 pb-1 border-b border-slate-200/60">
                                    <span><i class="bi bi-file-text me-1 text-blue-600"></i> Cuplikan Naskah Dinas</span>
                                    <span class="badge bg-white border border-slate-200 text-slate-600 font-mono text-[9px] px-1.5 py-0.5 rounded">A4 Format</span>
                                </div>
                                <div class="text-[11px] text-slate-600 font-sans leading-relaxed line-clamp-3" style="max-height: 62px;" v-html="sanitizePreview(t.konten_html)">
                                </div>
                            </div>

                            <!-- Action Toolbar Footer -->
                            <div class="mt-auto pt-3 border-t border-slate-100 d-flex align-items-center justify-content-between gap-1.5">
                                <!-- Download Word Button -->
                                <button type="button" class="btn btn-xs rounded-xl px-2.5 py-1.5 font-bold text-xs bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-600 hover:text-white transition-all shadow-2xs d-inline-flex align-items-center gap-1.5" @click="downloadWordTemplate(t)" title="Download Format Dokumen Word (.doc)">
                                    <i class="bi bi-file-earmark-word-fill"></i>
                                    <span>Word (.doc)</span>
                                </button>

                                <!-- Action Buttons (Edit & Delete) -->
                                <div class="d-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-xs btn-primary rounded-xl px-3 py-1.5 font-bold text-xs shadow-2xs d-inline-flex align-items-center gap-1" @click="editTemplate(t)" title="Edit Template Naskah">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Edit</span>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-light border border-slate-200 text-rose-600 hover:bg-rose-50 hover:border-rose-200 rounded-xl px-2 py-1.5 shadow-2xs" @click="deleteTemplate(t)" title="Hapus Template">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: KAMUS PLACEHOLDER VARIABEL DINAMIS -->
    <div v-show="activeTab === 'placeholder'" class="card border border-slate-200/80 shadow-2xs rounded-3xl bg-white overflow-hidden mb-5">
        <!-- Enterprise Header Bar -->
        <div class="p-4 border-b border-slate-200/80 bg-slate-50/80 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 text-white d-flex align-items-center justify-content-center fs-5 shadow-xs flex-shrink-0">
                    <i class="bi bi-cpu-fill"></i>
                </div>
                <div>
                    <h6 class="font-extrabold text-slate-900 fs-6 mb-0.5 d-flex align-items-center gap-2">
                        Kamus Tag &amp; Placeholder Variabel Cerdas
                        <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold px-2.5 py-0.5 rounded-pill text-[10px]">Auto-Replace Engine</span>
                    </h6>
                    <small class="text-slate-500 text-xs">Variabel cerdas yang akan otomatis dikonversi menjadi data riil saat naskah dinas diterbitkan atau diekspor ke Word</small>
                </div>
            </div>

            <!-- Search Tag Input -->
            <div class="position-relative flex-shrink-0" style="min-width: 260px; max-width: 320px;">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-7 pointer-events-none"></i>
                <input type="text" v-model="searchPlaceholder" class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-semibold bg-white shadow-2xs" placeholder="Cari variabel, cth: kepsek, nisn...">
                <button v-if="searchPlaceholder" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 border-0 bg-transparent p-0" @click="searchPlaceholder = ''">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        </div>

        <div class="p-4 bg-slate-50/30">
            <div class="row g-4">
                <!-- KATEGORI 1: IDENTITAS SEKOLAH & KOP -->
                <div class="col-12 col-xl-6">
                    <div class="card border border-slate-200/90 shadow-2xs rounded-3xl bg-white h-100 overflow-hidden">
                        <div class="p-3 px-3.5 bg-indigo-50/60 border-b border-indigo-100/80 d-flex align-items-center justify-content-between">
                            <div class="font-extrabold text-indigo-950 text-xs d-flex align-items-center gap-2">
                                <i class="bi bi-buildings-fill text-indigo-600 fs-6"></i> 1. Identitas Sekolah &amp; Kop Resmi
                            </div>
                            <span class="badge bg-white text-indigo-700 border border-indigo-200 font-bold px-2 py-0.5 rounded-lg text-[10px]">Master Kop</span>
                        </div>
                        <div class="p-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle text-xs mb-0">
                                    <thead class="bg-slate-50/60 text-slate-400 font-bold uppercase text-[10px]">
                                        <tr>
                                            <th class="ps-2 py-2" style="width: 170px;">Tag Variabel</th>
                                            <th class="py-2">Keterangan Sumber Data</th>
                                            <th class="py-2 text-end pe-2" style="width: 70px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="item in filterTags(placeholderGroups.sekolah)" :key="item.tag" class="hover:bg-indigo-50/20 transition">
                                            <td class="ps-2 py-2.5">
                                                <code class="text-indigo-700 bg-indigo-50 border border-indigo-200/80 font-bold font-mono px-2 py-1 rounded-lg text-[11px] d-inline-block">{{ item.tag }}</code>
                                            </td>
                                            <td class="py-2.5">
                                                <div class="font-bold text-slate-800 text-[11.5px]">{{ item.label }}</div>
                                                <div class="text-slate-400 text-[10px]">Contoh: <span class="font-medium text-slate-600">{{ item.example }}</span></div>
                                            </td>
                                            <td class="py-2.5 text-end pe-2">
                                                <button type="button" class="btn btn-xs btn-light border border-slate-200 text-indigo-600 hover:bg-indigo-50 rounded-lg px-2 py-1 font-bold shadow-2xs" @click="copyTag(item.tag)" title="Salin Tag ke Clipboard">
                                                    <i class="bi bi-clipboard me-0.5"></i> Salin
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KATEGORI 2: PENOMORAN & ADMINISTRASI NASKAH -->
                <div class="col-12 col-xl-6">
                    <div class="card border border-slate-200/90 shadow-2xs rounded-3xl bg-white h-100 overflow-hidden">
                        <div class="p-3 px-3.5 bg-blue-50/60 border-b border-blue-100/80 d-flex align-items-center justify-content-between">
                            <div class="font-extrabold text-blue-950 text-xs d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text-fill text-blue-600 fs-6"></i> 2. Penomoran &amp; Tanggal Terbit
                            </div>
                            <span class="badge bg-white text-blue-700 border border-blue-200 font-bold px-2 py-0.5 rounded-lg text-[10px]">Surat Keluar</span>
                        </div>
                        <div class="p-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle text-xs mb-0">
                                    <thead class="bg-slate-50/60 text-slate-400 font-bold uppercase text-[10px]">
                                        <tr>
                                            <th class="ps-2 py-2" style="width: 170px;">Tag Variabel</th>
                                            <th class="py-2">Keterangan Sumber Data</th>
                                            <th class="py-2 text-end pe-2" style="width: 70px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="item in filterTags(placeholderGroups.surat)" :key="item.tag" class="hover:bg-blue-50/20 transition">
                                            <td class="ps-2 py-2.5">
                                                <code class="text-blue-700 bg-blue-50 border border-blue-200/80 font-bold font-mono px-2 py-1 rounded-lg text-[11px] d-inline-block">{{ item.tag }}</code>
                                            </td>
                                            <td class="py-2.5">
                                                <div class="font-bold text-slate-800 text-[11.5px]">{{ item.label }}</div>
                                                <div class="text-slate-400 text-[10px]">Contoh: <span class="font-medium text-slate-600">{{ item.example }}</span></div>
                                            </td>
                                            <td class="py-2.5 text-end pe-2">
                                                <button type="button" class="btn btn-xs btn-light border border-slate-200 text-blue-600 hover:bg-blue-50 rounded-lg px-2 py-1 font-bold shadow-2xs" @click="copyTag(item.tag)" title="Salin Tag ke Clipboard">
                                                    <i class="bi bi-clipboard me-0.5"></i> Salin
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KATEGORI 3: DATA SISWA & ORANG TUA -->
                <div class="col-12 col-xl-6">
                    <div class="card border border-slate-200/90 shadow-2xs rounded-3xl bg-white h-100 overflow-hidden">
                        <div class="p-3 px-3.5 bg-emerald-50/60 border-b border-emerald-100/80 d-flex align-items-center justify-content-between">
                            <div class="font-extrabold text-emerald-950 text-xs d-flex align-items-center gap-2">
                                <i class="bi bi-person-bounding-box text-emerald-600 fs-6"></i> 3. Siswa &amp; Orang Tua / Wali
                            </div>
                            <span class="badge bg-white text-emerald-700 border border-emerald-200 font-bold px-2 py-0.5 rounded-lg text-[10px]">Biodata Siswa</span>
                        </div>
                        <div class="p-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle text-xs mb-0">
                                    <thead class="bg-slate-50/60 text-slate-400 font-bold uppercase text-[10px]">
                                        <tr>
                                            <th class="ps-2 py-2" style="width: 170px;">Tag Variabel</th>
                                            <th class="py-2">Keterangan Sumber Data</th>
                                            <th class="py-2 text-end pe-2" style="width: 70px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="item in filterTags(placeholderGroups.siswa)" :key="item.tag" class="hover:bg-emerald-50/20 transition">
                                            <td class="ps-2 py-2.5">
                                                <code class="text-emerald-800 bg-emerald-50 border border-emerald-200/80 font-bold font-mono px-2 py-1 rounded-lg text-[11px] d-inline-block">{{ item.tag }}</code>
                                            </td>
                                            <td class="py-2.5">
                                                <div class="font-bold text-slate-800 text-[11.5px]">{{ item.label }}</div>
                                                <div class="text-slate-400 text-[10px]">Contoh: <span class="font-medium text-slate-600">{{ item.example }}</span></div>
                                            </td>
                                            <td class="py-2.5 text-end pe-2">
                                                <button type="button" class="btn btn-xs btn-light border border-slate-200 text-emerald-700 hover:bg-emerald-50 rounded-lg px-2 py-1 font-bold shadow-2xs" @click="copyTag(item.tag)" title="Salin Tag ke Clipboard">
                                                    <i class="bi bi-clipboard me-0.5"></i> Salin
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KATEGORI 4: PEJABAT, GTK & VALIDASI TTE -->
                <div class="col-12 col-xl-6">
                    <div class="card border border-slate-200/90 shadow-2xs rounded-3xl bg-white h-100 overflow-hidden">
                        <div class="p-3 px-3.5 bg-amber-50/60 border-b border-amber-100/80 d-flex align-items-center justify-content-between">
                            <div class="font-extrabold text-amber-950 text-xs d-flex align-items-center gap-2">
                                <i class="bi bi-patch-check-fill text-amber-600 fs-6"></i> 4. Pejabat Penandatangan &amp; TTE
                            </div>
                            <span class="badge bg-white text-amber-700 border border-amber-200 font-bold px-2 py-0.5 rounded-lg text-[10px]">Otoritas &amp; Keabsahan</span>
                        </div>
                        <div class="p-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle text-xs mb-0">
                                    <thead class="bg-slate-50/60 text-slate-400 font-bold uppercase text-[10px]">
                                        <tr>
                                            <th class="ps-2 py-2" style="width: 170px;">Tag Variabel</th>
                                            <th class="py-2">Keterangan Sumber Data</th>
                                            <th class="py-2 text-end pe-2" style="width: 70px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="item in filterTags(placeholderGroups.pejabat)" :key="item.tag" class="hover:bg-amber-50/20 transition">
                                            <td class="ps-2 py-2.5">
                                                <code class="text-amber-800 bg-amber-50 border border-amber-200/80 font-bold font-mono px-2 py-1 rounded-lg text-[11px] d-inline-block">{{ item.tag }}</code>
                                            </td>
                                            <td class="py-2.5">
                                                <div class="font-bold text-slate-800 text-[11.5px]">{{ item.label }}</div>
                                                <div class="text-slate-400 text-[10px]">Contoh: <span class="font-medium text-slate-600">{{ item.example }}</span></div>
                                            </td>
                                            <td class="py-2.5 text-end pe-2">
                                                <button type="button" class="btn btn-xs btn-light border border-slate-200 text-amber-700 hover:bg-amber-50 rounded-lg px-2 py-1 font-bold shadow-2xs" @click="copyTag(item.tag)" title="Salin Tag ke Clipboard">
                                                    <i class="bi bi-clipboard me-0.5"></i> Salin
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL FORM TEMPLATE NASKAH DINAS (DENGAN VISUAL WORD EDITOR)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalFormTemplate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-blue-500/20 text-blue-400 d-flex align-items-center justify-content-center fs-5 border border-blue-400/30">
                            <i class="bi bi-file-earmark-word-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0 text-white">{{ isEdit ? 'Edit Template Naskah Dinas' : 'Tambah Template Naskah Baru' }}</h5>
                            <small class="text-slate-400 text-xs">Visual Word Editor naskah dinas baku dan variabel otomatis</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitTemplate">
                    <div class="modal-body p-4 bg-slate-50/50 text-xs">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label font-bold text-slate-700">Nama Template Naskah <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formTemplate.nama_template_surat" class="form-control form-control-sm rounded-xl font-semibold" placeholder="Contoh: Surat Tugas Pembina Ekskul / Surat Panggilan Siswa" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label font-bold text-slate-700">Klasifikasi Terkait</label>
                                <!-- Searchable Custom Dropdown Modal -->
                                <div class="dropdown position-relative">
                                    <button type="button" 
                                            class="btn btn-sm btn-white border border-slate-200 rounded-xl text-xs font-semibold shadow-2xs w-100 d-inline-flex align-items-center justify-content-between gap-2 bg-white text-slate-700 hover:bg-slate-50 transition" 
                                            id="dropdownModalKlas" 
                                            data-bs-toggle="dropdown" 
                                            data-bs-auto-close="outside" 
                                            aria-expanded="false">
                                        <div class="d-flex align-items-center gap-1.5 text-truncate">
                                            <i class="bi bi-tag-fill text-indigo-500 fs-7"></i>
                                            <span v-if="selectedModalKlasObj" class="text-truncate font-bold text-slate-800">
                                                <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono me-1">{{ selectedModalKlasObj.kode_klasifikasi }}</span>
                                                {{ selectedModalKlasObj.nama_klasifikasi }}
                                            </span>
                                            <span v-else class="text-slate-400">— Pilih Kode Klasifikasi —</span>
                                        </div>
                                        <i class="bi bi-chevron-down text-slate-400 fs-8 ms-1"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-start shadow-2xl rounded-2xl border border-slate-200 p-2 text-xs" style="width: 360px; max-width: 90vw; z-index: 1060;">
                                        <!-- Search Box di dalam dropdown -->
                                        <div class="position-relative mb-2">
                                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2.5 text-slate-400 fs-7"></i>
                                            <input type="text" 
                                                   v-model="searchModalKlasText" 
                                                   class="form-control form-control-sm ps-4 pe-4 rounded-xl border border-slate-200 text-xs font-semibold bg-slate-50 focus:bg-white" 
                                                   placeholder="Cari kode (cth 421) atau nama..."
                                                   @click.stop>
                                            <button v-if="searchModalKlasText" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 border-0 bg-transparent p-0" @click.stop="searchModalKlasText = ''">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- List Options -->
                                        <div class="overflow-y-auto" style="max-height: 220px;">
                                            <div class="dropdown-item rounded-xl py-2 px-2.5 cursor-pointer d-flex align-items-center justify-content-between mb-1" 
                                                 :class="{'bg-blue-50 text-blue-700 font-bold': !formTemplate.id_kode_klasifikasi}" 
                                                 @click="formTemplate.id_kode_klasifikasi = ''; closeDropdown('dropdownModalKlas')">
                                                <span>— Tanpa Klasifikasi Khusus —</span>
                                                <i v-if="!formTemplate.id_kode_klasifikasi" class="bi bi-check2 text-blue-600 fs-6"></i>
                                            </div>
                                            <div v-for="k in filteredModalKlasOptions" :key="'modal-klas-'+k.id" 
                                                 class="dropdown-item rounded-xl py-2 px-2.5 cursor-pointer d-flex align-items-center justify-content-between mb-1" 
                                                 :class="{'bg-indigo-50 text-indigo-900 font-bold': formTemplate.id_kode_klasifikasi === k.id}" 
                                                 @click="formTemplate.id_kode_klasifikasi = k.id; closeDropdown('dropdownModalKlas')">
                                                <div class="d-flex flex-column text-truncate me-2">
                                                    <div class="d-flex align-items-center gap-1.5 text-truncate">
                                                        <span class="badge bg-white border border-slate-200 text-indigo-700 font-mono text-[10px]">{{ k.kode_klasifikasi }}</span>
                                                        <span class="text-truncate">{{ k.nama_klasifikasi }}</span>
                                                    </div>
                                                    <span class="text-[10px] text-slate-400 mt-0.5" v-if="k.kategori_utama">{{ k.kategori_utama }}</span>
                                                </div>
                                                <i v-if="formTemplate.id_kode_klasifikasi === k.id" class="bi bi-check2 text-indigo-600 fs-6 flex-shrink-0"></i>
                                            </div>
                                            <div v-if="filteredModalKlasOptions.length === 0" class="text-center py-3 text-slate-400 text-xs">
                                                Tidak ditemukan kode "{{ searchModalKlasText }}"
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label font-bold text-slate-700">Perihal Default</label>
                                <input type="text" v-model="formTemplate.perihal_default" class="form-control form-control-sm rounded-xl" placeholder="Contoh: Surat Tugas Mengikuti Kegiatan...">
                            </div>
                        </div>

                        <!-- ═══ VISUAL WORD EDITOR TOOLBAR RIBBON ═══ -->
                        <div class="card border border-slate-200/90 rounded-2xl overflow-hidden bg-white shadow-2xs">
                            <div class="bg-slate-100/90 border-b border-slate-200/80 p-2 d-flex flex-wrap align-items-center justify-content-between gap-1.5">
                                <!-- Formatting Buttons -->
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2.5 fw-bold text-xs" @click="execCmd('bold')" title="Tebal (Ctrl+B)"><strong>B</strong></button>
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2.5 fst-italic text-xs" @click="execCmd('italic')" title="Miring (Ctrl+I)"><em>I</em></button>
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2.5 text-decoration-underline text-xs" @click="execCmd('underline')" title="Garis Bawah (Ctrl+U)"><u>U</u></button>
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2.5 text-decoration-line-through text-xs" @click="execCmd('strikeThrough')" title="Coret">S</button>
                                    </div>

                                    <div class="vr mx-1"></div>

                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execCmd('justifyLeft')" title="Rata Kiri"><i class="bi bi-text-left"></i></button>
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execCmd('justifyCenter')" title="Rata Tengah"><i class="bi bi-text-center"></i></button>
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execCmd('justifyRight')" title="Rata Kanan"><i class="bi bi-text-right"></i></button>
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execCmd('justifyFull')" title="Rata Kiri-Kanan"><i class="bi bi-justify"></i></button>
                                    </div>

                                    <div class="vr mx-1"></div>

                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execCmd('insertUnorderedList')" title="Bullet List"><i class="bi bi-list-ul"></i></button>
                                        <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execCmd('insertOrderedList')" title="Numbered List"><i class="bi bi-list-ol"></i></button>
                                    </div>

                                    <div class="vr mx-1"></div>

                                    <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs font-semibold" @click="insertTableSnippet()" title="Sisipkan Tabel Siswa">
                                        <i class="bi bi-table me-1"></i> Tabel Siswa
                                    </button>
                                </div>

                                <!-- Toggle Source Mode -->
                                <div>
                                    <button type="button" class="btn btn-xs rounded-lg px-2.5 py-1 font-semibold" :class="isSourceMode ? 'btn-dark' : 'btn-light border'" @click="toggleSourceMode()">
                                        <i class="bi bi-code-slash me-1"></i> {{ isSourceMode ? 'Mode Visual Word' : 'Mode Kode HTML' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Placeholder Tag Buttons -->
                            <div class="bg-blue-50/70 border-b border-blue-100 p-2 d-flex flex-wrap align-items-center gap-1">
                                <span class="text-[11px] font-bold text-blue-900 me-1"><i class="bi bi-plus-circle me-1"></i>Sisipkan Variabel:</span>
                                <button type="button" class="btn btn-xs bg-white border border-blue-200 text-blue-800 hover:bg-blue-100 rounded-md font-mono py-0.5 px-1.5 text-[10px]" @click="insertPlaceholder('{nama_siswa}')">+ {nama_siswa}</button>
                                <button type="button" class="btn btn-xs bg-white border border-blue-200 text-blue-800 hover:bg-blue-100 rounded-md font-mono py-0.5 px-1.5 text-[10px]" @click="insertPlaceholder('{nisn}')">+ {nisn}</button>
                                <button type="button" class="btn btn-xs bg-white border border-blue-200 text-blue-800 hover:bg-blue-100 rounded-md font-mono py-0.5 px-1.5 text-[10px]" @click="insertPlaceholder('{kelas}')">+ {kelas}</button>
                                <button type="button" class="btn btn-xs bg-white border border-blue-200 text-blue-800 hover:bg-blue-100 rounded-md font-mono py-0.5 px-1.5 text-[10px]" @click="insertPlaceholder('{nama_ortu}')">+ {nama_ortu}</button>
                                <button type="button" class="btn btn-xs bg-white border border-blue-200 text-blue-800 hover:bg-blue-100 rounded-md font-mono py-0.5 px-1.5 text-[10px]" @click="insertPlaceholder('{nomor_surat}')">+ {nomor_surat}</button>
                                <button type="button" class="btn btn-xs bg-white border border-blue-200 text-blue-800 hover:bg-blue-100 rounded-md font-mono py-0.5 px-1.5 text-[10px]" @click="insertPlaceholder('{tgl_surat}')">+ {tgl_surat}</button>
                                <button type="button" class="btn btn-xs bg-white border border-blue-200 text-blue-800 hover:bg-blue-100 rounded-md font-mono py-0.5 px-1.5 text-[10px]" @click="insertPlaceholder('{nama_kepsek}')">+ {nama_kepsek}</button>
                                <button type="button" class="btn btn-xs bg-white border border-blue-200 text-blue-800 hover:bg-blue-100 rounded-md font-mono py-0.5 px-1.5 text-[10px]" @click="insertPlaceholder('{nama_sekolah}')">+ {nama_sekolah}</button>
                            </div>

                            <!-- Editor Surface (Visual Word Sheet vs HTML Source) -->
                            <div class="p-3 bg-slate-200/40">
                                <!-- Mode 1: Visual Word Sheet Editor -->
                                <div v-show="!isSourceMode" 
                                     id="visualWordEditor" 
                                     contenteditable="true" 
                                     @input="onEditorInput"
                                     class="bg-white mx-auto shadow-sm p-4 p-md-5 rounded-xl text-slate-900 border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                     style="min-height: 320px; max-width: 820px; font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.6;">
                                </div>

                                <!-- Mode 2: HTML Source Editor -->
                                <textarea v-show="isSourceMode" 
                                          v-model="formTemplate.konten_html" 
                                          @input="syncEditorFromSource"
                                          rows="14" 
                                          class="form-control form-control-sm font-mono text-xs rounded-xl bg-slate-900 text-emerald-400 border-0 p-3" 
                                          placeholder="Ketik format kode HTML di sini..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-xl px-3 font-semibold text-xs" @click="downloadWordTemplate(formTemplate)" title="Download Draft Dokumen Word">
                                <i class="bi bi-file-earmark-word text-blue-600 me-1"></i> Download Word (.doc)
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-light btn-sm rounded-xl px-3 font-semibold text-xs text-slate-600" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                <span>Simpan Template Naskah</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, computed, onMounted, nextTick } = Vue;

    const persuratanTemplateAppConfig = {
        setup() {
            const activeTab = ref('katalog');
            const loading = ref(false);
            const saving = ref(false);
            const isEdit = ref(false);
            const isSourceMode = ref(false);
            const templates = ref([]);
            const klasifikasiList = ref([]);
            const search = ref('');
            const filterKlas = ref('');
            const searchFilterKlasText = ref('');
            const searchModalKlasText = ref('');
            const searchPlaceholder = ref('');

            const formTemplate = ref({
                id: '',
                nama_template_surat: '',
                id_kode_klasifikasi: '',
                perihal_default: '',
                konten_html: ''
            });

            const placeholderGroups = ref({
                sekolah: [
                    { tag: '{NAMA_SEKOLAH}', label: 'Nama Resmi Sekolah', example: 'SMA NEGERI 1 SURABAYA' },
                    { tag: '{NPSN}', label: 'Nomor Pokok Sekolah Nasional', example: '20512345' },
                    { tag: '{ALAMAT_SEKOLAH}', label: 'Alamat Lengkap Sekolah', example: 'Jl. Wijaya Kusuma No. 48' },
                    { tag: '{KOTA_KABUPATEN}', label: 'Kota / Kabupaten Sekolah', example: 'Kota Surabaya' },
                    { tag: '{TELEPON_SEKOLAH}', label: 'Nomor Telepon Kantor TU', example: '(031) 5342111' },
                    { tag: '{EMAIL_SEKOLAH}', label: 'Email Resmi Sekolah', example: 'info@sman1sby.sch.id' }
                ],
                surat: [
                    { tag: '{NOMOR_SURAT}', label: 'Nomor Surat Terbit Resmi', example: '421.3/042/SMAN1/VIII/2026' },
                    { tag: '{TANGGAL_SURAT}', label: 'Tanggal Format Surat Baku', example: '22 Agustus 2026' },
                    { tag: '{PERIHAL}', label: 'Perihal / Hal Naskah Dinas', example: 'Undangan Rapat Pleno Komite' },
                    { tag: '{TUJUAN_SURAT}', label: 'Nama Penerima / Tujuan Surat', example: 'Orang Tua / Wali Murid' },
                    { tag: '{NO_AGENDA}', label: 'Nomor Buku Agenda Keluar', example: 'AGK-2026-0042' },
                    { tag: '{TAHUN}', label: 'Tahun Anggaran / Terbit', example: '2026' }
                ],
                siswa: [
                    { tag: '{NAMA_SISWA}', label: 'Nama Lengkap Siswa Terkait', example: 'Muhammad Rizky Pratama' },
                    { tag: '{NISN}', label: 'Nomor Induk Siswa Nasional', example: '0081234567' },
                    { tag: '{NIS}', label: 'Nomor Induk Siswa Sekolah', example: '242510012' },
                    { tag: '{KELAS}', label: 'Rombel Kelas Siswa', example: 'XII-MIPA-1' },
                    { tag: '{NAMA_ORTU}', label: 'Nama Orang Tua / Wali Siswa', example: 'Bambang Sudarmono, S.T.' },
                    { tag: '{ALAMAT_SISWA}', label: 'Alamat Domisili Siswa', example: 'Jl. Dharmawangsa No. 12' }
                ],
                pejabat: [
                    { tag: '{NAMA_KEPSEK}', label: 'Nama Lengkap Kepala Sekolah', example: 'Drs. H. Suhartono, M.Pd.' },
                    { tag: '{NIP_KEPSEK}', label: 'NIP Pejabat Penandatangan', example: '19750512 199903 1 004' },
                    { tag: '{JABATAN_KEPSEK}', label: 'Jabatan Penandatangan Naskah', example: 'Kepala Sekolah' },
                    { tag: '{TTE_TOKEN}', label: 'Token QR Code Validasi TTE', example: 'TTE-2026-9F3A4E78B125' }
                ]
            });

            const filterTags = (list) => {
                const q = searchPlaceholder.value.toLowerCase().trim();
                if (!q) return list;
                return list.filter(item => 
                    item.tag.toLowerCase().includes(q) ||
                    item.label.toLowerCase().includes(q) ||
                    item.example.toLowerCase().includes(q)
                );
            };

            const copyTag = (tag) => {
                navigator.clipboard.writeText(tag).then(() => {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: `Tag ${tag} berhasil disalin!`
                    });
                }).catch(() => {
                    execCmd('insertHTML', tag);
                });
            };

            let modalInstance = null;

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const fetchTemplates = async () => {
                loading.value = true;
                try {
                    const [resTpl, resKlas] = await Promise.all([
                        axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/template' + getTenantParam('?')),
                        axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi' + getTenantParam('?'))
                    ]);
                    if (resTpl.data && resTpl.data.success) templates.value = resTpl.data.data || [];
                    if (resKlas.data && resKlas.data.success) klasifikasiList.value = resKlas.data.data || [];
                } catch (e) {
                    console.error('Gagal memuat template:', e);
                } finally {
                    loading.value = false;
                }
            };

            const selectedKlasObj = computed(() => {
                if (!filterKlas.value) return null;
                return klasifikasiList.value.find(k => k.id === filterKlas.value) || null;
            });

            const selectedModalKlasObj = computed(() => {
                if (!formTemplate.value.id_kode_klasifikasi) return null;
                return klasifikasiList.value.find(k => k.id === formTemplate.value.id_kode_klasifikasi) || null;
            });

            const filteredKlasOptions = computed(() => {
                const q = searchFilterKlasText.value.toLowerCase().trim();
                if (!q) return klasifikasiList.value.slice(0, 100);
                return klasifikasiList.value.filter(k => 
                    (k.kode_klasifikasi && k.kode_klasifikasi.toLowerCase().includes(q)) ||
                    (k.nama_klasifikasi && k.nama_klasifikasi.toLowerCase().includes(q)) ||
                    (k.kategori_utama && k.kategori_utama.toLowerCase().includes(q))
                ).slice(0, 100);
            });

            const filteredModalKlasOptions = computed(() => {
                const q = searchModalKlasText.value.toLowerCase().trim();
                if (!q) return klasifikasiList.value.slice(0, 100);
                return klasifikasiList.value.filter(k => 
                    (k.kode_klasifikasi && k.kode_klasifikasi.toLowerCase().includes(q)) ||
                    (k.nama_klasifikasi && k.nama_klasifikasi.toLowerCase().includes(q)) ||
                    (k.kategori_utama && k.kategori_utama.toLowerCase().includes(q))
                ).slice(0, 100);
            });

            const closeDropdown = (id) => {
                const el = document.getElementById(id);
                if (el && typeof bootstrap !== 'undefined') {
                    const inst = bootstrap.Dropdown.getInstance(el) || bootstrap.Dropdown.getOrCreateInstance(el);
                    if (inst) inst.hide();
                }
            };

            const filteredTemplates = computed(() => {
                const q = search.value.toLowerCase().trim();
                return templates.value.filter(t => {
                    const matchQuery = !q || 
                        (t.nama_template_surat && t.nama_template_surat.toLowerCase().includes(q)) ||
                        (t.perihal_default && t.perihal_default.toLowerCase().includes(q)) ||
                        (t.kode_klasifikasi && t.kode_klasifikasi.toLowerCase().includes(q));
                    const matchKlas = !filterKlas.value || (t.id_kode_klasifikasi === filterKlas.value);
                    return matchQuery && matchKlas;
                });
            });

            const sanitizePreview = (html) => {
                if (!html) return 'Format naskah dinas resmi...';
                return html.replace(/<[^>]*>?/gm, ' ').substring(0, 150) + '...';
            };

            const execCmd = (command, value = null) => {
                document.execCommand(command, false, value);
                onEditorInput();
            };

            const insertPlaceholder = (tag) => {
                if (isSourceMode.value) {
                    formTemplate.value.konten_html += tag;
                } else {
                    document.execCommand('insertHTML', false, `<strong>${tag}</strong> `);
                    onEditorInput();
                }
            };

            const insertTableSnippet = () => {
                const tableHtml = `
                    <table style="width: 100%; border-collapse: collapse; margin: 10px 0;">
                        <tr>
                            <td style="width: 180px; padding: 4px 0; vertical-align: top;"><strong>Nama Lengkap</strong></td>
                            <td style="padding: 4px 0; vertical-align: top;">: {nama_siswa}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px; padding: 4px 0; vertical-align: top;"><strong>NISN / Kelas</strong></td>
                            <td style="padding: 4px 0; vertical-align: top;">: {nisn} / {kelas}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px; padding: 4px 0; vertical-align: top;"><strong>Keterangan</strong></td>
                            <td style="padding: 4px 0; vertical-align: top;">: Pembinaan Kedisiplinan Sekolah</td>
                        </tr>
                    </table><p></p>
                `;
                if (isSourceMode.value) {
                    formTemplate.value.konten_html += tableHtml;
                } else {
                    document.execCommand('insertHTML', false, tableHtml);
                    onEditorInput();
                }
            };

            const onEditorInput = () => {
                const el = document.getElementById('visualWordEditor');
                if (el) {
                    formTemplate.value.konten_html = el.innerHTML;
                }
            };

            const syncEditorFromSource = () => {
                const el = document.getElementById('visualWordEditor');
                if (el) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(formTemplate.value.konten_html || '', 'text/html');
                    el.replaceChildren(...doc.body.childNodes);
                }
            };

            const toggleSourceMode = () => {
                isSourceMode.value = !isSourceMode.value;
                if (!isSourceMode.value) {
                    nextTick(() => syncEditorFromSource());
                }
            };

            const openModalTemplate = () => {
                isEdit.value = false;
                isSourceMode.value = false;
                formTemplate.value = {
                    id: '',
                    nama_template_surat: '',
                    id_kode_klasifikasi: '',
                    perihal_default: '',
                    konten_html: '<p>Dengan hormat,</p><p>Sehubungan dengan agenda kedinasan dan tata kelola administrasi sekolah, bersama ini kami sampaikan pemberitahuan / permohonan resmi kepada Bapak/Ibu:</p><table style="width: 100%; border-collapse: collapse; margin: 10px 0;"><tr><td style="width: 180px; padding: 4px 0;"><strong>Nama Siswa</strong></td><td>: {nama_siswa}</td></tr><tr><td style="padding: 4px 0;"><strong>Kelas / Rombel</strong></td><td>: {kelas}</td></tr></table><p>Demikian surat dinas ini kami sampaikan, atas perhatian dan kerja sama yang baik diucapkan terima kasih.</p>'
                };
                const el = document.getElementById('modalFormTemplate');
                if (el && typeof bootstrap !== 'undefined') {
                    modalInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalInstance.show();
                    nextTick(() => syncEditorFromSource());
                }
            };

            const editTemplate = (t) => {
                isEdit.value = true;
                isSourceMode.value = false;
                formTemplate.value = {
                    id: t.id,
                    nama_template_surat: t.nama_template_surat,
                    id_kode_klasifikasi: t.id_kode_klasifikasi || '',
                    perihal_default: t.perihal_default || '',
                    konten_html: t.konten_html || '<p>Dengan hormat,</p><p>Isi surat...</p>'
                };
                const el = document.getElementById('modalFormTemplate');
                if (el && typeof bootstrap !== 'undefined') {
                    modalInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalInstance.show();
                    nextTick(() => syncEditorFromSource());
                }
            };

            const submitTemplate = async () => {
                if (!isSourceMode.value) {
                    onEditorInput();
                }
                saving.value = true;
                try {
                    const payload = { ...formTemplate.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/template/save', payload);
                    if (res.data && res.data.success) {
                        if (modalInstance) modalInstance.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Tersimpan!',
                            text: 'Template naskah dinas berhasil disimpan.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        fetchTemplates();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menyimpan template.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const deleteTemplate = (t) => {
                Swal.fire({
                    title: 'Hapus Template?',
                    text: `Hapus template: ${t.nama_template_surat}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Ya, Hapus'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/template/delete', { id: t.id, tenant_id: currentTenantId });
                            Swal.fire('Terhapus', 'Template telah dihapus.', 'success');
                            fetchTemplates();
                        } catch (e) {
                            Swal.fire('Gagal', 'Gagal menghapus template.', 'error');
                        }
                    }
                });
            };

            // ─── EXPORT TO MICROSOFT WORD (.doc / .docx) ───
            const downloadWordTemplate = (tpl) => {
                const title = tpl.nama_template_surat || 'Naskah_Dinas';
                const htmlContent = `
                    <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                    <head>
                        <meta charset="utf-8">
                        <title>${title}</title>
                        <style>
                            @page {
                                size: A4;
                                margin: 2.5cm 2.5cm 2.5cm 2.5cm;
                            }
                            body {
                                font-family: 'Times New Roman', Times, serif;
                                font-size: 12pt;
                                line-height: 1.6;
                                color: #000000;
                            }
                            table {
                                border-collapse: collapse;
                                width: 100%;
                            }
                            p {
                                margin: 0 0 10pt 0;
                            }
                        </style>
                    </head>
                    <body>
                        <div style="text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 20px;">
                            <h3 style="margin: 0; text-transform: uppercase;">KOP SURAT RESMI SEKOLAH</h3>
                            <p style="margin: 0; font-size: 10pt;">Alamat Sekolah | Kontak &amp; Email Resmi</p>
                        </div>
                        <table style="margin-bottom: 20px;">
                            <tr><td style="width: 100px;"><strong>Nomor</strong></td><td>: {NOMOR_SURAT}</td><td style="text-align: right;">{TANGGAL_SURAT}</td></tr>
                            <tr><td><strong>Lampiran</strong></td><td>: -</td><td></td></tr>
                            <tr><td><strong>Perihal</strong></td><td>: <strong>${tpl.perihal_default || '{PERIHAL}'}</strong></td><td></td></tr>
                        </table>
                        <div style="margin-bottom: 20px;">
                            <p>Kepada Yth.<br><strong>{TUJUAN_SURAT}</strong><br>di Tempat</p>
                        </div>
                        <div style="text-align: justify;">
                            ${tpl.konten_html || '<p>Dengan hormat,</p><p>Isi naskah surat...</p>'}
                        </div>
                        <div style="margin-top: 40px; float: right; width: 250px; text-align: center;">
                            <p>Kepala Sekolah,</p>
                            <br><br><br>
                            <p><strong><u>{NAMA_KEPSEK}</u></strong><br>NIP. {NIP_KEPSEK}</p>
                        </div>
                    </body>
                    </html>
                `;

                const blob = new Blob(['\ufeff', htmlContent], {
                    type: 'application/msword'
                });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `${title.replace(/\s+/g, '_')}.doc`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };

            window.persuratanTemplateOpenTambah = openModalTemplate;

            onMounted(() => {
                fetchTemplates();
            });

            return {
                activeTab,
                loading,
                saving,
                isEdit,
                isSourceMode,
                templates,
                klasifikasiList,
                search,
                filterKlas,
                searchFilterKlasText,
                searchModalKlasText,
                selectedKlasObj,
                selectedModalKlasObj,
                filteredKlasOptions,
                filteredModalKlasOptions,
                closeDropdown,
                searchPlaceholder,
                placeholderGroups,
                filterTags,
                copyTag,
                formTemplate,
                filteredTemplates,
                fetchTemplates,
                openModalTemplate,
                editTemplate,
                submitTemplate,
                deleteTemplate,
                execCmd,
                insertPlaceholder,
                insertTableSnippet,
                onEditorInput,
                syncEditorFromSource,
                toggleSourceMode,
                downloadWordTemplate,
                sanitizePreview
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#persuratanTemplateApp', persuratanTemplateAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(persuratanTemplateAppConfig).mount('#persuratanTemplateApp');
        });
    }
}
</script>
