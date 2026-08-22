<?php
/**
 * View: Master Pengaturan Persuratan (Kop Surat & Kode Klasifikasi Kearsipan)
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
$activeMenu = 'master';
$pageTitle = 'Master Tata Usaha & Persuratan';
$pageSubtitle = 'Pengaturan master kop surat resmi sekolah, logo dinas, dan katalog kode klasifikasi kearsipan naskah dinas nasional.';
$pageIcon = 'bi-gear-wide-connected';
?>
<div id="persuratanMasterApp" v-cloak class="container-fluid px-0">
    <!-- Hero Banner Header Mandiri -->
    <?php 
    $heroBadge = 'Master Persuratan';
    $pageTitle = 'Kop Surat & Kode Klasifikasi Kearsipan';
    $pageSubtitle = 'Konfigurasi identitas kop surat resmi, logo sekolah, serta kode klasifikasi kearsipan multi-tahun (Regulasi 2025).';
    $pageIcon = 'bi-gear-wide-connected';
    $heroButtons = '
        <button type="button" class="btn btn-sm rounded-xl px-3.5 py-2 text-xs font-bold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all d-inline-flex align-items-center gap-1.5 backdrop-blur-md" onclick="window.persuratanMasterOpenTambah && window.persuratanMasterOpenTambah()">
            <i class="bi bi-plus-circle-fill"></i> Tambah Klasifikasi
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
                    onclick="document.getElementById('masterPersuratanNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Container Deretan Tab -->
            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="masterPersuratanNavTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeSubTab === 'kop'}" 
                                @click="activeSubTab = 'kop'">
                            <i class="bi bi-card-heading me-2 fs-6 text-primary"></i> 1. Pengaturan Kop Surat Resmi
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeSubTab === 'klasifikasi'}" 
                                @click="activeSubTab = 'klasifikasi'">
                            <i class="bi bi-tags-fill me-2 fs-6 text-indigo-600"></i> 2. Kode Klasifikasi Kearsipan
                            <span class="badge bg-slate-100 text-slate-700 ms-2 rounded-pill text-[11px]">{{ filteredKlasifikasi.length }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- 1 Tombol Panah Kanan -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('masterPersuratanNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Tombol Aksi Tambahan -->
            <div class="d-none d-md-flex align-items-center ps-2 pe-1 border-s border-slate-200/80 ms-2 gap-2">
                <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl px-3 py-2 text-xs font-bold shadow-2xs d-flex align-items-center gap-1.5" @click="fetchMaster" title="Segarkan Data">
                    <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i>
                    <span>Segarkan</span>
                </button>
                <button v-if="activeSubTab === 'klasifikasi'" type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalKlasifikasi()" title="Tambah Kode Klasifikasi">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Tambah Klasifikasi</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         SUB TAB 1: PENGATURAN KOP SURAT SEKOLAH
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeSubTab === 'kop'" class="row g-4 mb-5">
        <!-- Form Pengaturan Kop -->
        <div class="col-12 col-lg-6">
            <div class="card border border-slate-200/80 shadow-2xs rounded-3xl bg-white p-4 h-100">
                <h6 class="font-bold text-slate-800 fs-6 mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-sliders text-primary"></i> Data Identitas Kop Surat
                </h6>
                <form @submit.prevent="saveKopSurat">
                    <div class="row g-3 text-xs">
                        <div class="col-12">
                            <label class="form-label font-bold text-slate-700">Nama Instansi Tingkat Atas</label>
                            <input type="text" v-model="kop.nama_instansi_atas" class="form-control form-control-sm rounded-xl" placeholder="Contoh: PEMERINTAH PROVINSI JAWA TIMUR / DINAS PENDIDIKAN">
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold text-slate-700">Nama Resmi Sekolah <span class="text-rose-500">*</span></label>
                            <input type="text" v-model="kop.nama_sekolah" class="form-control form-control-sm rounded-xl font-bold" placeholder="Contoh: SMA NEGERI 1 SURABAYA" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold text-slate-700">Alamat Lengkap</label>
                            <textarea v-model="kop.alamat" rows="2" class="form-control form-control-sm rounded-xl" placeholder="Jalan Raya No..., Kelurahan, Kecamatan"></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-700">Kota / Kabupaten</label>
                            <input type="text" v-model="kop.kota_kabupaten" class="form-control form-control-sm rounded-xl" placeholder="Surabaya">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-700">Kode Pos</label>
                            <input type="text" v-model="kop.kode_pos" class="form-control form-control-sm rounded-xl" placeholder="60231">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-700">Nomor Telepon</label>
                            <input type="text" v-model="kop.telepon" class="form-control form-control-sm rounded-xl" placeholder="(031) 1234567">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-700">Email Resmi</label>
                            <input type="email" v-model="kop.email" class="form-control form-control-sm rounded-xl" placeholder="info@sekolah.sch.id">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-700">URL Logo Kiri (Pemda / Yayasan)</label>
                            <input type="text" v-model="kop.logo_kiri" class="form-control form-control-sm rounded-xl" placeholder="https://... / logo-pemda.png">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-700">URL Logo Kanan (Sekolah)</label>
                            <input type="text" v-model="kop.logo_kanan" class="form-control form-control-sm rounded-xl" placeholder="https://... / logo-sekolah.png">
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span>Simpan Perubahan Kop Surat</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Live Preview Kop -->
        <div class="col-12 col-lg-6">
            <div class="card border border-slate-200/80 shadow-2xs rounded-3xl bg-white p-4 h-100">
                <h6 class="font-bold text-slate-800 fs-6 mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-eye-fill text-emerald-600"></i> Pratinjau Real-time Kop Surat
                </h6>
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                    <div class="bg-white p-3 p-md-4 rounded-xl shadow-xs" style="font-family: 'Times New Roman', Times, serif; color: #000000;">
                        <div class="d-flex align-items-center justify-content-between gap-3 pb-3 mb-3" style="border-bottom: 3px double #000000;">
                            <img v-if="kop.logo_kiri" :src="kop.logo_kiri" style="width: 60px; height: 60px; object-fit: contain;">
                            <div v-else style="width: 60px; height: 60px;" class="border border-dark d-flex align-items-center justify-content-center text-muted fs-8 font-sans">LOGO 1</div>

                            <div class="text-center flex-grow-1 font-sans">
                                <h6 class="fw-bold text-uppercase mb-0 fs-8 tracking-wide">{{ kop.nama_instansi_atas || 'PEMERINTAH PROVINSI / KABUPATEN' }}</h6>
                                <h5 class="fw-black text-uppercase mb-0 fs-6">{{ kop.nama_sekolah || 'NAMA SEKOLAH TERPADU' }}</h5>
                                <p class="mb-0 text-muted" style="font-size: 8pt; font-family: sans-serif;">
                                    {{ kop.alamat || 'Alamat Sekolah Terpadu' }} | Telp: {{ kop.telepon || '-' }} | Email: {{ kop.email || '-' }}
                                </p>
                            </div>

                            <img v-if="kop.logo_kanan" :src="kop.logo_kanan" style="width: 60px; height: 60px; object-fit: contain;">
                            <div v-else style="width: 60px; height: 60px;"></div>
                        </div>
                        <div class="text-center py-4 text-slate-400 font-sans text-xs">
                            — Area Naskah Isi Surat Dinas —
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         SUB TAB 2: MASTER KODE KLASIFIKASI SURAT DINAS (MULTI-TAHUN REGULASI)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeSubTab === 'klasifikasi'" class="card border border-slate-200/80 shadow-2xs rounded-3xl bg-white overflow-hidden mb-5">
        
        <!-- ═══ EXECUTIVE TOOLBAR: FILTER, SYNC, IMPORT, EXPORT, ACTIONS ═══ -->
        <div class="p-3.5 p-md-4 border-b border-slate-200/80 bg-slate-50/80">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <!-- Sisi Kiri: Filter Group -->
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <!-- Search Input -->
                    <div class="position-relative" style="min-width: 260px; max-width: 380px; flex-grow: 1;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3.5 text-blue-500 fs-7 pointer-events-none"></i>
                        <input type="text" v-model="searchKlas" class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-semibold bg-white shadow-2xs" placeholder="Cari kode (cth 421.3) / nama klasifikasi...">
                        <button v-if="searchKlas" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 border-0 bg-transparent p-0" @click="searchKlas = ''">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>

                    <!-- Filter Versi Tahun Regulasi -->
                    <select v-model="filterTahun" @change="fetchMaster" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer" style="width: auto; min-width: 170px;">
                        <option value="">— Semua Versi Regulasi —</option>
                        <option value="2025">Regulasi 2025 (Terbaru)</option>
                        <option value="2024">Regulasi 2024</option>
                    </select>

                    <!-- Filter Status Aktif -->
                    <select v-model="filterStatusAktif" @change="fetchMaster" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer" style="width: auto;">
                        <option value="semua">Semua Status</option>
                        <option value="aktif">Hanya Aktif</option>
                        <option value="nonaktif">Hanya Nonaktif</option>
                    </select>

                    <button v-if="searchKlas || filterTahun || filterStatusAktif !== 'semua'" type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl text-xs font-bold px-2.5 py-1.5 shadow-2xs text-slate-600 hover:bg-slate-100" @click="resetFilters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>

                <!-- Sisi Kanan: Action Buttons (Sync, Import, Export, Add) -->
                <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                    <!-- 1-Click Sync Katalog Nasional 2025 -->
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 text-indigo-700 hover:bg-indigo-50 rounded-xl px-3 py-1.5 font-bold text-xs shadow-2xs d-inline-flex align-items-center gap-1.5" @click="syncNasional" :disabled="syncing" title="Sinkronkan 2.936 Kode Klasifikasi Kearsipan Nasional 2025">
                        <span v-if="syncing" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-cloud-arrow-down-fill text-indigo-600"></i>
                        <span>Sync Katalog Nasional</span>
                    </button>

                    <!-- Export Dropdown -->
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-xl px-3 py-1.5 font-bold text-xs shadow-2xs dropdown-toggle d-inline-flex align-items-center gap-1.5" data-bs-toggle="dropdown">
                            <i class="bi bi-download text-emerald-600"></i>
                            <span>Ekspor</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg rounded-2xl border-0 p-1.5 text-xs">
                            <li><a class="dropdown-item rounded-xl py-2 font-semibold d-flex align-items-center gap-2" href="javascript:void(0)" @click="exportData('json')"><i class="bi bi-filetype-json text-amber-600 fs-6"></i> Ekspor format JSON (.json)</a></li>
                            <li><a class="dropdown-item rounded-xl py-2 font-semibold d-flex align-items-center gap-2" href="javascript:void(0)" @click="exportData('csv')"><i class="bi bi-filetype-csv text-emerald-600 fs-6"></i> Ekspor format CSV / Excel (.csv)</a></li>
                        </ul>
                    </div>

                    <!-- Import Button -->
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-xl px-3 py-1.5 font-bold text-xs shadow-2xs d-inline-flex align-items-center gap-1.5" @click="openModalImport">
                        <i class="bi bi-upload text-blue-600"></i>
                        <span>Impor</span>
                    </button>

                    <!-- Tambah Klasifikasi Button -->
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs shadow-2xs d-inline-flex align-items-center gap-1.5" @click="openModalKlasifikasi">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Tambah Kode</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ TABLE DATA KLASIFIKASI KEARSIPAN ═══ -->
        <div class="table-responsive" style="min-height: 380px;">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat basis data kode klasifikasi kearsipan...
            </div>

            <div v-else-if="filteredKlasifikasi.length === 0" class="text-center py-5 px-3">
                <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-500 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-inner">
                    <i class="bi bi-tags"></i>
                </div>
                <div class="font-bold text-slate-800 text-base mb-1">Tidak Ada Data Klasifikasi</div>
                <p class="text-slate-500 text-xs mb-3 mx-auto" style="max-width: 440px;">
                    Belum ada data kode klasifikasi yang sesuai. Anda dapat melakukan sinkronisasi instan dengan katalog resmi nasional.
                </p>
                <button type="button" class="btn btn-sm btn-indigo rounded-xl font-bold px-4 py-2 text-xs shadow-2xs text-white" style="background: #4f46e5;" @click="syncNasional">
                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> Sinkronkan Katalog Nasional 2025 (2.936 Kode)
                </button>
            </div>

            <table v-else class="table table-hover align-middle text-xs mb-0">
                <thead class="bg-slate-50/90 border-b border-slate-200/80 text-slate-500 font-bold uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="ps-4 py-3" style="width: 140px;">Kode Kearsipan</th>
                        <th class="py-3">Nama Klasifikasi Naskah</th>
                        <th class="py-3" style="width: 180px;">Kategori &amp; Level</th>
                        <th class="py-3 text-center" style="width: 160px;">Tahun Berlaku</th>
                        <th class="py-3 text-center" style="width: 120px;">Status Aktif</th>
                        <th class="py-3 text-end pe-4" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="k in paginatedKlasifikasi" :key="k.id" class="hover:bg-blue-50/30 transition">
                        <td class="ps-4 py-3">
                            <span class="badge bg-blue-50 text-blue-700 border border-blue-200/90 font-mono font-bold px-2.5 py-1 rounded-lg text-xs shadow-2xs cursor-pointer" @click="copyKode(k.kode_klasifikasi)" title="Klik untuk salin kode">
                                {{ k.kode_klasifikasi }}
                            </span>
                        </td>
                        <td class="py-3">
                            <div class="font-extrabold text-slate-900 fs-7 mb-0.5">{{ k.nama_klasifikasi }}</div>
                            <div class="text-slate-500 text-[11px] line-clamp-1" v-if="k.deskripsi">{{ k.deskripsi }}</div>
                            <div class="text-slate-400 text-[10px] mt-0.5" v-if="k.retensi_aktif_tahun">
                                <i class="bi bi-clock-history me-0.5"></i> Retensi Aktif: <strong>{{ k.retensi_aktif_tahun }} th</strong> | Inaktif: <strong>{{ k.retensi_inaktif_tahun || 5 }} th</strong>
                            </div>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-slate-100 text-slate-700 rounded-md px-2 py-1 text-[10px] font-semibold d-block mb-1 text-truncate" style="max-width: 160px;">
                                {{ k.kategori_utama || 'Umum/Organisasi' }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono" v-if="k.parent_kode">Induk: <strong>{{ k.parent_kode }}</strong> (Lvl {{ k.level_klasifikasi || 1 }})</span>
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-pill px-2.5 py-1 text-[10px] font-bold d-inline-flex align-items-center gap-1 shadow-2xs">
                                <i class="bi bi-calendar-check"></i> {{ k.tahun_berlaku_mulai || 2025 }} - {{ k.tahun_berlaku_selesai || 'Sekarang' }}
                            </span>
                        </td>
                        <td class="py-3 text-center">
                            <!-- Toggle Switch Aktif / Nonaktif -->
                            <div class="form-check form-switch d-inline-block m-0">
                                <input class="form-check-input cursor-pointer" type="checkbox" role="switch" :checked="k.is_active" @change="toggleStatus(k)">
                            </div>
                            <span class="d-block text-[10px] font-bold" :class="k.is_active ? 'text-emerald-600' : 'text-slate-400'">
                                {{ k.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="py-3 text-end pe-4">
                            <div class="d-inline-flex align-items-center gap-1.5">
                                <button type="button" class="btn btn-xs btn-outline-primary rounded-xl px-2.5 py-1 font-bold shadow-2xs d-inline-flex align-items-center gap-1" @click="editKlasifikasi(k)" title="Edit Kode">
                                    <i class="bi bi-pencil"></i>
                                    <span>Edit</span>
                                </button>
                                <button type="button" class="btn btn-xs btn-light border border-slate-200 text-rose-600 hover:bg-rose-50 hover:border-rose-200 rounded-xl p-1.5 shadow-2xs" @click="deleteKlasifikasi(k)" title="Hapus Kode">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ═══ PAGINATION & SUMMARY BAR ═══ -->
        <div class="p-3.5 border-t border-slate-200/80 bg-slate-50/70 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2.5 text-xs text-slate-500">
            <div>
                Menampilkan <strong>{{ filteredKlasifikasi.length > 0 ? (currentPage - 1) * pageSize + 1 : 0 }}</strong> - <strong>{{ Math.min(currentPage * pageSize, filteredKlasifikasi.length) }}</strong> dari <strong>{{ filteredKlasifikasi.length }}</strong> kode klasifikasi
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-xs btn-light border rounded-xl px-3 py-1.5 font-bold shadow-2xs" :disabled="currentPage <= 1" @click="currentPage--">
                    <i class="bi bi-chevron-left me-1"></i> Sebelumnya
                </button>
                <span class="px-2 font-mono font-bold text-slate-700">Hal. {{ currentPage }} / {{ totalPages || 1 }}</span>
                <button type="button" class="btn btn-xs btn-light border rounded-xl px-3 py-1.5 font-bold shadow-2xs" :disabled="currentPage >= totalPages" @click="currentPage++">
                    Selanjutnya <i class="bi bi-chevron-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL 1: FORM TAMBAH & EDIT KODE KLASIFIKASI
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalFormKlasifikasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 text-indigo-400 d-flex align-items-center justify-content-center fs-5 border border-indigo-400/30">
                            <i class="bi bi-tags-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0 text-white">{{ isEditKlas ? 'Edit Kode Klasifikasi Kearsipan' : 'Tambah Kode Klasifikasi Baru' }}</h5>
                            <small class="text-slate-400 text-xs">Standarisasi kode nomor naskah dinas dan tahun berlaku regulasi</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitKlasifikasi">
                    <div class="modal-body p-4 bg-slate-50/50 text-xs">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label font-bold text-slate-700">Kode Klasifikasi <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formKlasifikasi.kode_klasifikasi" class="form-control form-control-sm rounded-xl font-mono font-bold" placeholder="Contoh: 421.3" required>
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label font-bold text-slate-700">Nama Klasifikasi Naskah <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formKlasifikasi.nama_klasifikasi" class="form-control form-control-sm rounded-xl font-semibold" placeholder="Contoh: Kesiswaan & Ekstrakurikuler" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Kategori Utama</label>
                                <input type="text" v-model="formKlasifikasi.kategori_utama" class="form-control form-control-sm rounded-xl" placeholder="Contoh: Pendidikan/Kesiswaan">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label font-bold text-slate-700">Kode Induk (Parent)</label>
                                <input type="text" v-model="formKlasifikasi.parent_kode" class="form-control form-control-sm rounded-xl font-mono" placeholder="421">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label font-bold text-slate-700">Level</label>
                                <select v-model="formKlasifikasi.level_klasifikasi" class="form-select form-select-sm rounded-xl">
                                    <option :value="1">Level 1 (Primer)</option>
                                    <option :value="2">Level 2 (Sekunder)</option>
                                    <option :value="3">Level 3 (Tersier)</option>
                                    <option :value="4">Level 4 (Rinci)</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label font-bold text-slate-700">Tahun Berlaku Mulai <span class="text-rose-500">*</span></label>
                                <input type="number" v-model="formKlasifikasi.tahun_berlaku_mulai" class="form-control form-control-sm rounded-xl font-bold" placeholder="2025" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label font-bold text-slate-700">Tahun Berlaku Selesai</label>
                                <input type="number" v-model="formKlasifikasi.tahun_berlaku_selesai" class="form-control form-control-sm rounded-xl" placeholder="Kosongkan jika aktif">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label font-bold text-slate-700">Versi Regulasi</label>
                                <input type="text" v-model="formKlasifikasi.versi_regulasi" class="form-control form-control-sm rounded-xl" placeholder="Permendagri/Disdik 2025">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Retensi Aktif (Tahun)</label>
                                <input type="number" v-model="formKlasifikasi.retensi_aktif_tahun" class="form-control form-control-sm rounded-xl" placeholder="5">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Retensi Inaktif (Tahun)</label>
                                <input type="number" v-model="formKlasifikasi.retensi_inaktif_tahun" class="form-control form-control-sm rounded-xl" placeholder="5">
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Keterangan / Fungsi Arsip</label>
                                <textarea v-model="formKlasifikasi.deskripsi" rows="2" class="form-control form-control-sm rounded-xl" placeholder="Keterangan klasifikasi naskah..."></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" id="checkActiveKlas" v-model="formKlasifikasi.is_active">
                                    <label class="form-check-label font-bold text-slate-700 ms-2" for="checkActiveKlas">Status Klasifikasi Aktif &amp; Dapat Dipilih di Surat Keluar</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3.5 font-semibold text-xs" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span>{{ isEditKlas ? 'Simpan Perubahan' : 'Tambah Klasifikasi' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL 2: IMPOR BERKAS KLASIFIKASI KEARSIPAN (JSON / CSV)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalImportKlasifikasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-blue-500/20 text-blue-400 d-flex align-items-center justify-content-center fs-5 border border-blue-400/30">
                            <i class="bi bi-upload"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0 text-white">Impor Berkas Kode Klasifikasi</h5>
                            <small class="text-slate-400 text-xs">Unggah file JSON/CSV atau tempel data klasifikasi naskah</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitImport">
                    <div class="modal-body p-4 bg-slate-50/50 text-xs">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Tahun Regulasi Mulai Berlaku</label>
                                <input type="number" v-model="importConfig.tahun_berlaku_mulai" class="form-control form-control-sm rounded-xl font-bold" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Nama Versi Regulasi</label>
                                <input type="text" v-model="importConfig.versi_regulasi" class="form-control form-control-sm rounded-xl font-semibold" placeholder="Permendagri/Disdik 2025" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Pilih Berkas JSON / CSV</label>
                                <input type="file" @change="handleFileUpload" accept=".json,.csv" class="form-control form-control-sm rounded-xl bg-white">
                                <div class="form-text text-[11px] text-slate-400 mt-1">Mendukung file array JSON seperti <code>kode_klasifikasi_surat.json</code></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Atau Tempel Teks JSON di Sini</label>
                                <textarea v-model="importConfig.jsonText" rows="6" class="form-control form-control-sm font-mono text-xs rounded-xl" placeholder='[ {"kode_klasifikasi": "421.3", "nama_klasifikasi": "Kesiswaan"} ]'></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3.5 font-semibold text-xs" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="importing">
                            <span v-if="importing" class="spinner-border spinner-border-sm me-1"></span>
                            <span>Mulai Impor Data</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, computed, onMounted } = Vue;

    const persuratanMasterAppConfig = {
        setup() {
            const activeSubTab = ref('kop');
            const loading = ref(false);
            const saving = ref(false);
            const syncing = ref(false);
            const importing = ref(false);
            const isEditKlas = ref(false);
            const klasifikasiList = ref([]);
            const searchKlas = ref('');
            const filterTahun = ref('2025');
            const filterStatusAktif = ref('semua');
            const currentPage = ref(1);
            const pageSize = 25;

            const kop = ref({
                id: '',
                nama_instansi_atas: '',
                nama_sekolah: '',
                alamat: '',
                kota_kabupaten: '',
                kode_pos: '',
                telepon: '',
                email: '',
                logo_kiri: '',
                logo_kanan: ''
            });

            const formKlasifikasi = ref({
                id: '',
                kode_klasifikasi: '',
                nama_klasifikasi: '',
                kategori_utama: 'Pendidikan/Kesiswaan',
                parent_kode: '',
                level_klasifikasi: 1,
                retensi_aktif_tahun: 5,
                retensi_inaktif_tahun: 5,
                tahun_berlaku_mulai: 2025,
                tahun_berlaku_selesai: null,
                versi_regulasi: 'Permendagri/Disdik 2025',
                deskripsi: '',
                is_active: true
            });

            const importConfig = ref({
                tahun_berlaku_mulai: 2025,
                versi_regulasi: 'Permendagri/Disdik 2025',
                jsonText: ''
            });

            let modalKlasInstance = null;
            let modalImportInstance = null;

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const fetchMaster = async () => {
                loading.value = true;
                try {
                    let klasUrl = '<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi' + getTenantParam('?');
                    if (filterTahun.value) klasUrl += `&tahun=${encodeURIComponent(filterTahun.value)}`;
                    if (filterStatusAktif.value) klasUrl += `&status_aktif=${encodeURIComponent(filterStatusAktif.value)}`;

                    const [resKop, resKlas] = await Promise.all([
                        axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/kop-surat' + getTenantParam('?')),
                        axios.get(klasUrl)
                    ]);
                    if (resKop.data && resKop.data.success && resKop.data.data) kop.value = resKop.data.data;
                    if (resKlas.data && resKlas.data.success) klasifikasiList.value = resKlas.data.data || [];
                    currentPage.value = 1;
                } catch (e) {
                    console.error('Gagal memuat data master persuratan:', e);
                } finally {
                    loading.value = false;
                }
            };

            const resetFilters = () => {
                searchKlas.value = '';
                filterTahun.value = '';
                filterStatusAktif.value = 'semua';
                fetchMaster();
            };

            const filteredKlasifikasi = computed(() => {
                const q = searchKlas.value.toLowerCase().trim();
                if (!q) return klasifikasiList.value;
                return klasifikasiList.value.filter(k => 
                    (k.kode_klasifikasi && k.kode_klasifikasi.toLowerCase().includes(q)) ||
                    (k.nama_klasifikasi && k.nama_klasifikasi.toLowerCase().includes(q)) ||
                    (k.kategori_utama && k.kategori_utama.toLowerCase().includes(q)) ||
                    (k.deskripsi && k.deskripsi.toLowerCase().includes(q))
                );
            });

            const totalPages = computed(() => {
                return Math.ceil(filteredKlasifikasi.value.length / pageSize) || 1;
            });

            const paginatedKlasifikasi = computed(() => {
                const start = (currentPage.value - 1) * pageSize;
                return filteredKlasifikasi.value.slice(start, start + pageSize);
            });

            const saveKopSurat = async () => {
                saving.value = true;
                try {
                    const payload = { ...kop.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/kop-surat/save', payload);
                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Kop Surat Tersimpan!',
                            text: 'Format identitas resmi kop surat berhasil diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        fetchMaster();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menyimpan kop surat.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const openModalKlasifikasi = () => {
                isEditKlas.value = false;
                formKlasifikasi.value = {
                    id: '',
                    kode_klasifikasi: '',
                    nama_klasifikasi: '',
                    kategori_utama: 'Pendidikan/Kesiswaan',
                    parent_kode: '',
                    level_klasifikasi: 1,
                    retensi_aktif_tahun: 5,
                    retensi_inaktif_tahun: 5,
                    tahun_berlaku_mulai: 2025,
                    tahun_berlaku_selesai: null,
                    versi_regulasi: 'Permendagri/Disdik 2025',
                    deskripsi: '',
                    is_active: true
                };
                const el = document.getElementById('modalFormKlasifikasi');
                if (el && typeof bootstrap !== 'undefined') {
                    modalKlasInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalKlasInstance.show();
                }
            };

            const editKlasifikasi = (k) => {
                isEditKlas.value = true;
                formKlasifikasi.value = {
                    id: k.id,
                    kode_klasifikasi: k.kode_klasifikasi,
                    nama_klasifikasi: k.nama_klasifikasi,
                    kategori_utama: k.kategori_utama || 'Pendidikan/Kesiswaan',
                    parent_kode: k.parent_kode || '',
                    level_klasifikasi: k.level_klasifikasi || 1,
                    retensi_aktif_tahun: k.retensi_aktif_tahun || 5,
                    retensi_inaktif_tahun: k.retensi_inaktif_tahun || 5,
                    tahun_berlaku_mulai: k.tahun_berlaku_mulai || 2025,
                    tahun_berlaku_selesai: k.tahun_berlaku_selesai || null,
                    versi_regulasi: k.versi_regulasi || 'Permendagri/Disdik 2025',
                    deskripsi: k.deskripsi || '',
                    is_active: k.is_active !== undefined ? !!k.is_active : true
                };
                const el = document.getElementById('modalFormKlasifikasi');
                if (el && typeof bootstrap !== 'undefined') {
                    modalKlasInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalKlasInstance.show();
                }
            };

            const submitKlasifikasi = async () => {
                saving.value = true;
                try {
                    const payload = { ...formKlasifikasi.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi/save', payload);
                    if (res.data && res.data.success) {
                        if (modalKlasInstance) modalKlasInstance.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Tersimpan!',
                            text: 'Kode klasifikasi kearsipan berhasil disimpan.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        fetchMaster();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menyimpan kode klasifikasi.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const toggleStatus = async (k) => {
                const newStatus = !k.is_active;
                k.is_active = newStatus;
                try {
                    await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi/toggle-status', {
                        id: k.id,
                        is_active: newStatus,
                        tenant_id: currentTenantId
                    });
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: newStatus ? 'success' : 'info',
                        title: `Kode ${k.kode_klasifikasi} ${newStatus ? 'diaktifkan' : 'dinonaktifkan'}`
                    });
                } catch (e) {
                    k.is_active = !newStatus; // revert
                    Swal.fire('Gagal', 'Gagal mengubah status klasifikasi.', 'error');
                }
            };

            const deleteKlasifikasi = (k) => {
                Swal.fire({
                    title: 'Hapus Kode Klasifikasi?',
                    text: `Hapus kode: ${k.kode_klasifikasi} - ${k.nama_klasifikasi}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Ya, Hapus'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi/delete', { id: k.id, tenant_id: currentTenantId });
                            Swal.fire('Terhapus', 'Kode klasifikasi telah dihapus.', 'success');
                            fetchMaster();
                        } catch (e) {
                            Swal.fire('Gagal', 'Gagal menghapus kode klasifikasi.', 'error');
                        }
                    }
                });
            };

            const syncNasional = async () => {
                Swal.fire({
                    title: 'Sinkronisasi Katalog Nasional 2025?',
                    text: 'Sistem akan memuat dan memperbarui seluruh 2.936 kode klasifikasi kearsipan resmi dari database nasional.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    confirmButtonText: 'Ya, Sinkronkan Sekarang'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        syncing.value = true;
                        try {
                            const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi/sync-nasional', { tenant_id: currentTenantId });
                            if (res.data && res.data.success) {
                                Swal.fire('Sinkronisasi Sukses!', res.data.message, 'success');
                                fetchMaster();
                            }
                        } catch (e) {
                            Swal.fire('Gagal', 'Gagal menyinkronkan data katalog nasional.', 'error');
                        } finally {
                            syncing.value = false;
                        }
                    }
                });
            };

            const openModalImport = () => {
                importConfig.value = {
                    tahun_berlaku_mulai: 2025,
                    versi_regulasi: 'Permendagri/Disdik 2025',
                    jsonText: ''
                };
                const el = document.getElementById('modalImportKlasifikasi');
                if (el && typeof bootstrap !== 'undefined') {
                    modalImportInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalImportInstance.show();
                }
            };

            const handleFileUpload = (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (evt) => {
                    importConfig.value.jsonText = evt.target.result;
                };
                reader.readAsText(file);
            };

            const submitImport = async () => {
                let items = [];
                try {
                    items = JSON.parse(importConfig.value.jsonText);
                    if (!Array.isArray(items)) throw new Error('Format JSON harus berupa Array [...]');
                } catch (e) {
                    Swal.fire('Format Tidak Valid', 'Teks yang diunggah/ditempel bukan array JSON yang valid.', 'error');
                    return;
                }

                importing.value = true;
                try {
                    const payload = {
                        items: items,
                        tahun_berlaku_mulai: importConfig.value.tahun_berlaku_mulai,
                        versi_regulasi: importConfig.value.versi_regulasi,
                        tenant_id: currentTenantId
                    };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi/import', payload);
                    if (res.data && res.data.success) {
                        if (modalImportInstance) modalImportInstance.hide();
                        Swal.fire('Impor Sukses!', res.data.message, 'success');
                        fetchMaster();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal mengimpor data klasifikasi.', 'error');
                } finally {
                    importing.value = false;
                }
            };

            const exportData = (format) => {
                const data = filteredKlasifikasi.value;
                if (!data || data.length === 0) {
                    Swal.fire('Peringatan', 'Tidak ada data klasifikasi yang dapat diekspor.', 'warning');
                    return;
                }

                if (format === 'json') {
                    const jsonStr = JSON.stringify(data, null, 2);
                    const blob = new Blob([jsonStr], { type: 'application/json' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = `kode_klasifikasi_surat_${new Date().getFullYear()}.json`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else if (format === 'csv') {
                    let csv = 'Kode Klasifikasi,Nama Klasifikasi,Kategori,Parent,Level,Tahun Mulai,Tahun Selesai,Status\n';
                    data.forEach(d => {
                        csv += `"${d.kode_klasifikasi}","${(d.nama_klasifikasi || '').replace(/"/g, '""')}","${d.kategori_utama || ''}","${d.parent_kode || ''}","${d.level_klasifikasi || 1}","${d.tahun_berlaku_mulai || 2025}","${d.tahun_berlaku_selesai || ''}","${d.is_active ? 'Aktif' : 'Nonaktif'}"\n`;
                    });
                    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = `kode_klasifikasi_surat_${new Date().getFullYear()}.csv`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            };

            const copyKode = (kode) => {
                navigator.clipboard.writeText(kode).then(() => {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: `Kode ${kode} disalin!`
                    });
                });
            };

            window.persuratanMasterOpenTambah = openModalKlasifikasi;

            onMounted(() => {
                fetchMaster();
            });

            return {
                activeSubTab,
                loading,
                saving,
                syncing,
                importing,
                isEditKlas,
                kop,
                klasifikasiList,
                searchKlas,
                filterTahun,
                filterStatusAktif,
                currentPage,
                pageSize,
                totalPages,
                filteredKlasifikasi,
                paginatedKlasifikasi,
                formKlasifikasi,
                importConfig,
                fetchMaster,
                resetFilters,
                saveKopSurat,
                openModalKlasifikasi,
                editKlasifikasi,
                submitKlasifikasi,
                toggleStatus,
                deleteKlasifikasi,
                syncNasional,
                openModalImport,
                handleFileUpload,
                submitImport,
                exportData,
                copyKode
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#persuratanMasterApp', persuratanMasterAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(persuratanMasterAppConfig).mount('#persuratanMasterApp');
        });
    }
}
</script>
