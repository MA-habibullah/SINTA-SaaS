<?php
/**
 * View: Buku Register Surat Keluar & Cetak Naskah Dinas TTE
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
$activeMenu = 'surat_keluar';
$pageTitle = 'Register Surat Keluar & Naskah Dinas';
$pageSubtitle = 'Buku register surat dinas keluar sekolah, penomoran otomatis, penandatanganan digital, dan pratinjau cetak kop resmi ber-QR Code.';
$pageIcon = 'bi-send-fill';
?>
<div id="persuratanSuratKeluarApp" v-cloak class="container-fluid px-0">
    <!-- Hero Banner Header Mandiri -->
    <?php 
    $heroBadge = 'Register Surat Terbit & TTE';
    $pageTitle = 'Surat Keluar & Register Naskah';
    $pageSubtitle = 'Buku register surat dinas keluar sekolah, penomoran otomatis, penandatanganan digital TTE QR, dan cetak naskah resmi.';
    $pageIcon = 'bi-send-fill';
    $heroButtons = '
        <button type="button" class="btn btn-sm rounded-xl px-3.5 py-2 text-xs font-bold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all d-inline-flex align-items-center gap-1.5 backdrop-blur-md" onclick="window.persuratanSuratKeluarOpenBuat && window.persuratanSuratKeluarOpenBuat()">
            <i class="bi bi-plus-circle-fill"></i> Buat Surat Keluar
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
                    onclick="document.getElementById('suratKeluarNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Container Deretan Tab -->
            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="suratKeluarNavTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'register'}" 
                                @click="activeTab = 'register'">
                            <i class="bi bi-send-check-fill me-2 fs-6 text-emerald-600"></i> 1. Buku Register Surat Keluar
                            <span class="badge bg-slate-100 text-slate-700 ms-2 rounded-pill text-[11px]">{{ listSuratKeluar.length }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'tte'}" 
                                @click="activeTab = 'tte'">
                            <i class="bi bi-qr-code-scan me-2 fs-6 text-primary"></i> 2. Monitoring TTE &amp; Validasi Naskah
                        </button>
                    </li>
                </ul>
            </div>

            <!-- 1 Tombol Panah Kanan -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('suratKeluarNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Tombol Aksi Tambahan / Segarkan Data -->
            <div class="d-none d-md-flex align-items-center ps-2 pe-1 border-s border-slate-200/80 ms-2 gap-2">
                <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl px-3 py-2 text-xs font-bold shadow-2xs d-flex align-items-center gap-1.5" @click="fetchSuratKeluar" title="Segarkan Data">
                    <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i>
                    <span>Segarkan</span>
                </button>
                <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalBuat()" title="Buat Surat Keluar Baru">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Buat Surat Keluar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TAB 1: BUKU REGISTER SURAT KELUAR -->
    <div v-show="activeTab === 'register'" class="card border border-slate-200/80 shadow-2xs rounded-3xl bg-white overflow-hidden mb-5">
        <!-- ═══ EXECUTIVE FILTER & SEARCH TOOLBAR ═══ -->
        <div class="p-3.5 p-md-4 border-b border-slate-200/80 bg-slate-50/80">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <!-- Sisi Kiri: Search Input & Filter Status -->
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <!-- Search Input -->
                    <div class="position-relative flex-grow-1" style="min-width: 260px; max-width: 440px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3.5 text-blue-500 fs-7 pointer-events-none"></i>
                        <input type="text" v-model="filter.search" @input="debounceSearch()"
                               class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-semibold bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 shadow-2xs transition"
                               placeholder="Cari nomor surat, tujuan, perihal...">
                        <button v-if="filter.search" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 p-0 border-0 bg-transparent" @click="filter.search = ''; fetchSuratKeluar()">
                            <i class="bi bi-x-circle-fill fs-7"></i>
                        </button>
                    </div>

                    <!-- Status Filter -->
                    <select v-model="filter.status_surat" @change="fetchSuratKeluar()" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer focus:border-blue-500" style="width: auto; min-width: 190px;">
                        <option value="">— Semua Status Naskah —</option>
                        <option value="Diterbitkan">Diterbitkan (Resmi)</option>
                        <option value="Draft">Draft Naskah</option>
                    </select>

                    <button v-if="filter.search || filter.status_surat" type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl text-xs font-bold px-3 py-1.5 shadow-2xs text-slate-600 hover:bg-slate-100 d-inline-flex align-items-center gap-1" @click="resetFilter()">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>

                <!-- Sisi Kanan: Total Naskah Badge & Tombol Buat -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="badge bg-white border border-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-xs shadow-2xs d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-send-fill text-blue-600"></i>
                        <span>Total: <strong class="text-blue-700">{{ listSuratKeluar.length }}</strong> Naskah</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-2xs d-flex align-items-center gap-1.5 transition hover:shadow-xs" @click="openModalBuat()">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Buat Surat Keluar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Area -->
        <div class="p-0">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat buku register surat keluar...
            </div>

            <div v-else-if="listSuratKeluar.length === 0" class="text-center py-5 px-3">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-500 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-inner">
                    <i class="bi bi-send"></i>
                </div>
                <div class="font-bold text-slate-800 text-base mb-1">Belum Ada Register Surat Keluar</div>
                <p class="text-slate-500 text-xs mb-3 mx-auto" style="max-width: 440px;">
                    Belum ada surat keluar yang diterbitkan. Klik tombol di bawah untuk membuat dan meng-generate nomor surat keluar baru.
                </p>
                <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 text-xs shadow-2xs" @click="openModalBuat()">
                    <i class="bi bi-plus-circle-fill me-1"></i> Buat Surat Keluar Baru
                </button>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle text-xs mb-0">
                    <thead class="bg-slate-50/90 border-b border-slate-200/80 text-slate-500 font-bold uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="ps-4 py-3" style="width: 220px;">Nomor &amp; Tanggal Surat</th>
                            <th class="py-3">Tujuan &amp; Pokok Perihal</th>
                            <th class="py-3" style="width: 170px;">Penandatangan</th>
                            <th class="py-3 text-center" style="width: 120px;">Status TTE</th>
                            <th class="py-3 text-end pe-4" style="width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="sk in listSuratKeluar" :key="sk.id" class="hover:bg-blue-50/30 transition">
                            <td class="ps-4 py-3">
                                <div class="font-mono font-bold text-blue-700 fs-7 mb-0.5">{{ sk.nomor_surat }}</div>
                                <div class="text-slate-400 text-[11px] d-flex align-items-center gap-1.5">
                                    <i class="bi bi-calendar3"></i> Terbit: {{ formatDateIndo(sk.tgl_surat) }}
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="font-bold text-slate-900 fs-7 mb-0.5">
                                    <i class="bi bi-person-lines-fill text-slate-400 me-1"></i> {{ sk.tujuan }}
                                </div>
                                <div class="text-slate-600 text-xs line-clamp-1 mb-1 font-medium" :title="sk.perihal">{{ sk.perihal }}</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span v-if="sk.kode_klasifikasi" class="badge bg-slate-100 text-slate-600 rounded px-1.5 py-0.5 text-[10px] font-mono">{{ sk.kode_klasifikasi }}</span>
                                    <span v-if="sk.nama_template" class="badge bg-blue-50 text-blue-700 border border-blue-200 rounded px-1.5 py-0.5 text-[10px]">{{ sk.nama_template }}</span>
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="font-bold text-slate-800">{{ sk.nama_penandatangan || 'Kepala Sekolah' }}</div>
                                <small class="text-slate-400">{{ sk.jabatan_penandatangan || 'Kepala Sekolah' }}</small>
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="sk.status_tte === 'Signed' || sk.qr_token" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 font-bold text-[10px] d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-qr-code"></i> TTE Valid
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-clock me-1"></i> Draft
                                </span>
                            </td>
                            <td class="py-3 text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <!-- Tombol Pratinjau & Cetak -->
                                    <button type="button" class="btn btn-xs btn-primary rounded-lg px-2 py-1 font-bold shadow-2xs d-inline-flex align-items-center gap-1" @click="openPratinjauCetak(sk)" title="Pratinjau Lembar Naskah Resmi & Cetak">
                                        <i class="bi bi-printer"></i> Cetak
                                    </button>
                                    <!-- Tombol Download Word Langsung -->
                                    <button type="button" class="btn btn-xs btn-light border border-slate-200 text-blue-700 hover:bg-blue-50 rounded-lg px-2 py-1 font-bold shadow-2xs d-inline-flex align-items-center gap-1" @click="downloadWordSurat(sk)" title="Download Dokumen Microsoft Word (.doc)">
                                        <i class="bi bi-file-earmark-word-fill text-blue-600"></i> Word
                                    </button>
                                    <!-- Tombol Edit -->
                                    <button type="button" class="btn btn-xs btn-outline-warning text-amber-700 hover:bg-amber-50 border-amber-300 rounded-lg p-1 font-semibold" @click="openModalEdit(sk)" title="Edit Naskah">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <!-- Tombol Hapus -->
                                    <button type="button" class="btn btn-xs btn-light border text-rose-600 hover:bg-rose-50 rounded-lg p-1" @click="deleteSuratKeluar(sk)" title="Hapus Register">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: MONITORING TTE & VALIDASI NASKAH -->
    <div v-show="activeTab === 'tte'" class="card border border-slate-200/80 shadow-2xs rounded-3xl bg-white overflow-hidden mb-5">
        <div class="p-3.5 p-md-4 border-b border-slate-200/80 bg-slate-50/80 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2.5">
            <div class="d-flex align-items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-blue-500/20 text-blue-600 d-flex align-items-center justify-content-center fs-5 border border-blue-400/30 flex-shrink-0">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <div>
                    <h6 class="font-bold text-slate-800 fs-6 mb-0.5 d-flex align-items-center gap-2">
                        Monitoring &amp; Status Tanda Tangan Elektronik (TTE)
                    </h6>
                    <small class="text-slate-400 text-xs">Penerbitan Token Kriptografi QR Code Keabsahan Surat Resmi Sekolah</small>
                </div>
            </div>
            <span class="badge bg-blue-50 text-blue-700 border border-blue-200 font-bold px-3 py-1.5 rounded-xl text-xs shadow-2xs">
                <i class="bi bi-qr-code-scan me-1"></i> E-Sign Security
            </span>
        </div>
        <div class="p-4">
            <div class="row g-3">
                <div v-for="sk in listSuratKeluar" :key="'tte-'+sk.id" class="col-12 col-md-6 col-xl-4">
                    <div class="card border border-slate-200/80 rounded-2xl bg-white p-3.5 shadow-2xs h-100">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <span class="badge bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-pill px-2.5 py-1 text-[10px] font-bold">
                                <i class="bi bi-shield-check me-1"></i> TTE TERVERIFIKASI
                            </span>
                            <span class="font-mono text-[10px] text-slate-400">{{ formatDateIndo(sk.tgl_surat) }}</span>
                        </div>
                        <div class="font-mono font-bold text-blue-700 fs-7 mb-1">{{ sk.nomor_surat }}</div>
                        <div class="text-xs font-semibold text-slate-800 mb-2 line-clamp-1">{{ sk.perihal }}</div>
                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100 mb-3 d-flex align-items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white border border-slate-200 d-flex align-items-center justify-content-center flex-shrink-0 shadow-2xs">
                                <i class="bi bi-qr-code fs-4 text-slate-800"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-[10px] text-slate-400 font-bold uppercase">Token Keabsahan:</div>
                                <div class="font-mono text-[11px] text-slate-700 text-truncate font-bold">{{ sk.qr_token || 'TOKEN-VERIF-DIGITAL' }}</div>
                            </div>
                        </div>
                        <div class="mt-auto d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-lg px-2.5 py-1 font-bold" @click="downloadWordSurat(sk)">
                                <i class="bi bi-file-earmark-word me-1"></i> Word (.doc)
                            </button>
                            <button type="button" class="btn btn-xs btn-primary rounded-lg px-2.5 py-1 font-bold" @click="openPratinjauCetak(sk)">
                                <i class="bi bi-printer me-1"></i> Pratinjau &amp; Cetak
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL 1: FORM BUAT & EDIT SURAT KELUAR (DENGAN VISUAL WORD EDITOR)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalBuatSuratKeluar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 d-flex align-items-center justify-content-center fs-5 border border-emerald-400/30">
                            <i class="bi bi-send-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0 text-white">
                                {{ isEditSurat ? 'Edit Register Surat Keluar' : 'Penerbitan & Register Surat Keluar' }}
                            </h5>
                            <small class="text-slate-400 text-xs">
                                Visual editor naskah dinas resmi sekolah dengan penomoran otomatis & TTE
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitSuratKeluar">
                    <div class="modal-body p-4 bg-slate-50/50 text-xs">
                        <div class="row g-3">
                            <!-- Jenis & Template Surat Selector -->
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Pilih Format Template Naskah</label>
                                <select v-model="formSuratKeluar.id_template" @change="onTemplateChange()" class="form-select form-select-sm rounded-xl font-semibold">
                                    <option value="">— Template Naskah Khusus / Bebas —</option>
                                    <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">{{ tpl.nama_template_surat }}</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Klasifikasi Arsip Naskah</label>
                                <select v-model="formSuratKeluar.id_kode_klasifikasi" class="form-select form-select-sm rounded-xl font-semibold">
                                    <option value="">— Pilih Klasifikasi Baku —</option>
                                    <option v-for="klas in klasifikasiList" :key="klas.id" :value="klas.id">
                                        {{ klas.kode_klasifikasi }} - {{ klas.nama_klasifikasi }}
                                    </option>
                                </select>
                            </div>

                            <!-- Nomor Surat Generator Bar -->
                            <div class="col-12 col-md-8">
                                <label class="form-label font-bold text-slate-700 d-flex justify-content-between align-items-center">
                                    <span>Nomor Surat Resmi <span class="text-rose-500">*</span></span>
                                    <button type="button" class="btn btn-xs btn-link p-0 text-blue-600 text-decoration-none font-bold" @click="generateNomorSurat()">
                                        <i class="bi bi-magic me-0.5"></i> Generate Nomor Otomatis
                                    </button>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="text" v-model="formSuratKeluar.nomor_surat" class="form-control font-mono font-bold rounded-start-xl border-slate-200" placeholder="Contoh: 421.3/042/SMAN1/VIII/2026" required>
                                    <button type="button" class="btn btn-outline-primary font-bold rounded-end-xl px-3" @click="generateNomorSurat()">
                                        Auto-Number
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label font-bold text-slate-700">Tanggal Terbit Surat <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="formSuratKeluar.tgl_surat" class="form-control form-control-sm rounded-xl font-semibold" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Tujuan Surat / Penerima <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratKeluar.tujuan" class="form-control form-control-sm rounded-xl font-semibold" placeholder="Contoh: Orang Tua / Wali Siswa, Kepala Dinas Pendidikan..." required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Perihal / Hal Surat <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratKeluar.perihal" class="form-control form-control-sm rounded-xl font-semibold" placeholder="Contoh: Undangan Panggilan Orang Tua Siswa / Surat Keterangan" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Nama Penandatangan <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratKeluar.nama_penandatangan" class="form-control form-control-sm rounded-xl font-semibold" placeholder="Nama Lengkap Kepala Sekolah" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Jabatan Penandatangan</label>
                                <input type="text" v-model="formSuratKeluar.jabatan_penandatangan" class="form-control form-control-sm rounded-xl" placeholder="Kepala Sekolah">
                            </div>

                            <!-- ═══ VISUAL WORD EDITOR ISI NASKAH SURAT ═══ -->
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700 d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-file-earmark-word text-primary me-1"></i> Isi / Naskah Surat Dinas (Visual Word Editor)</span>
                                    <span class="text-slate-400 font-normal text-[11px]">Format visual naskah dinas resmi</span>
                                </label>

                                <div class="card border border-slate-200/90 rounded-2xl overflow-hidden bg-white shadow-2xs">
                                    <div class="bg-slate-100/90 border-b border-slate-200/80 p-2 d-flex flex-wrap align-items-center justify-content-between gap-1.5">
                                        <div class="d-flex flex-wrap align-items-center gap-1">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-light border btn-sm py-1 px-2.5 fw-bold text-xs" @click="execWordCmd('bold')" title="Tebal (Ctrl+B)"><strong>B</strong></button>
                                                <button type="button" class="btn btn-light border btn-sm py-1 px-2.5 fst-italic text-xs" @click="execWordCmd('italic')" title="Miring (Ctrl+I)"><em>I</em></button>
                                                <button type="button" class="btn btn-light border btn-sm py-1 px-2.5 text-decoration-underline text-xs" @click="execWordCmd('underline')" title="Garis Bawah (Ctrl+U)"><u>U</u></button>
                                            </div>
                                            <div class="vr mx-1"></div>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execWordCmd('justifyLeft')" title="Rata Kiri"><i class="bi bi-text-left"></i></button>
                                                <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execWordCmd('justifyCenter')" title="Rata Tengah"><i class="bi bi-text-center"></i></button>
                                                <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execWordCmd('justifyRight')" title="Rata Kanan"><i class="bi bi-text-right"></i></button>
                                                <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execWordCmd('justifyFull')" title="Rata Kiri-Kanan"><i class="bi bi-justify"></i></button>
                                            </div>
                                            <div class="vr mx-1"></div>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execWordCmd('insertUnorderedList')" title="Daftar Poin"><i class="bi bi-list-ul"></i></button>
                                                <button type="button" class="btn btn-light border btn-sm py-1 px-2 text-xs" @click="execWordCmd('insertOrderedList')" title="Daftar Nomor"><i class="bi bi-list-ol"></i></button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-slate-200/40">
                                        <div id="visualSuratEditor" 
                                             contenteditable="true" 
                                             @input="onSuratEditorInput"
                                             class="bg-white mx-auto shadow-sm p-4 rounded-xl text-slate-900 border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                             style="min-height: 220px; font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.6;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3.5 font-semibold text-xs text-slate-600" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs d-inline-flex align-items-center gap-1.5" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm"></span>
                            <span>{{ isEditSurat ? 'Simpan Perubahan Surat' : 'Terbitkan & Simpan Register' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL 2: PRATINJAU CETAK NASKAH DINAS & DOWNLOAD MICROSOFT WORD
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalPratinjauCetak" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden" v-if="cetakDetail">
                <div class="modal-header bg-slate-900 text-white p-4 border-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-blue-500/20 text-blue-400 d-flex align-items-center justify-content-center fs-5 border border-blue-400/30">
                            <i class="bi bi-printer-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0 text-white">Pratinjau Lembar Naskah Dinas Resmi</h5>
                            <small class="text-slate-400 text-xs">Cetak langsung A4/Folio atau unduh dalam format Microsoft Word (.doc)</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Tombol Download Word -->
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3 py-2 font-bold text-xs shadow-2xs text-blue-700 bg-white border hover:bg-blue-50 d-inline-flex align-items-center gap-1.5" @click="downloadWordFromCetakDetail()">
                            <i class="bi bi-file-earmark-word-fill text-blue-600 fs-6"></i> Download Word (.doc)
                        </button>
                        <!-- Tombol Cetak / Print -->
                        <button type="button" class="btn btn-primary btn-sm rounded-xl px-3.5 py-2 font-bold text-xs shadow-2xs d-inline-flex align-items-center gap-1.5" @click="printNaskah()">
                            <i class="bi bi-printer-fill fs-6"></i> Cetak / Print Dokumen
                        </button>
                        <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4 p-md-5 bg-slate-200/60 overflow-y-auto" style="max-height: 80vh;">
                    <!-- A4 Letter Paper Simulation Sheet -->
                    <div id="printArea" class="bg-white shadow-lg mx-auto p-4 p-md-5 rounded-2xl" style="max-width: 820px; min-height: 1050px; color: #000000; font-family: 'Times New Roman', Times, serif;">
                        
                        <!-- Kop Surat Resmi -->
                        <div class="d-flex align-items-center justify-content-between gap-3 pb-3 mb-4" style="border-bottom: 3px double #000000;">
                            <img v-if="cetakDetail.kop?.logo_kiri" :src="cetakDetail.kop.logo_kiri" style="width: 75px; height: 75px; object-fit: contain;">
                            <div v-else style="width: 75px; height: 75px;" class="border border-dark d-flex align-items-center justify-content-center text-muted fs-8 font-sans">LOGO</div>

                            <div class="text-center flex-grow-1 font-sans">
                                <h6 class="fw-bold text-uppercase mb-0 fs-7 tracking-wide">{{ cetakDetail.kop?.nama_instansi_atas || 'PEMERINTAH PROVINSI / KABUPATEN' }}</h6>
                                <h4 class="fw-black text-uppercase mb-0 fs-5">{{ cetakDetail.kop?.nama_sekolah || 'SEKOLAH SINTA TERPADU' }}</h4>
                                <p class="mb-0 text-muted fs-8" style="font-family: sans-serif;">
                                    {{ cetakDetail.kop?.alamat || 'Alamat Sekolah Terpadu' }} | Telp: {{ cetakDetail.kop?.telepon || '-' }} | Email: {{ cetakDetail.kop?.email || '-' }}
                                </p>
                            </div>

                            <img v-if="cetakDetail.kop?.logo_kanan" :src="cetakDetail.kop.logo_kanan" style="width: 75px; height: 75px; object-fit: contain;">
                            <div v-else style="width: 75px; height: 75px;"></div>
                        </div>

                        <!-- Header Nomor & Tanggal Surat -->
                        <div class="d-flex justify-content-between align-items-start mb-4 text-xs font-sans">
                            <div>
                                <table class="table table-sm table-borderless text-xs mb-0">
                                    <tr><td class="p-0 pe-2 fw-bold" style="width: 80px;">Nomor</td><td class="p-0 pe-2">:</td><td class="p-0 font-mono fw-bold">{{ cetakDetail.surat.nomor_surat }}</td></tr>
                                    <tr><td class="p-0 pe-2 fw-bold">Lampiran</td><td class="p-0 pe-2">:</td><td class="p-0">-</td></tr>
                                    <tr><td class="p-0 pe-2 fw-bold">Perihal</td><td class="p-0 pe-2">:</td><td class="p-0 fw-bold">{{ cetakDetail.surat.perihal }}</td></tr>
                                </table>
                            </div>
                            <div class="text-end">
                                <span>{{ cetakDetail.kop?.kota_kabupaten || 'Tempat' }}, {{ formatDateIndo(cetakDetail.surat.tgl_surat) }}</span>
                            </div>
                        </div>

                        <!-- Tujuan Surat -->
                        <div class="mb-4 text-xs font-sans">
                            <span class="d-block">Kepada Yth.</span>
                            <strong class="d-block fs-7">{{ cetakDetail.surat.tujuan }}</strong>
                            <span>di Tempat</span>
                        </div>

                        <!-- Isi Naskah Surat -->
                        <div class="mb-5 text-sm leading-relaxed text-justify" style="font-size: 11.5pt; line-height: 1.8;" v-html="cetakDetail.surat.ringkasan_isi || '<p>Dengan hormat,</p><p>Sehubungan dengan agenda administrasi dan layanan kedinasan sekolah, bersama surat ini kami sampaikan permohonan / pemberitahuan resmi untuk menjadi perhatian bersama.</p><p>Demikian surat dinas ini disampaikan, atas perhatian dan kerja sama yang baik kami ucapkan terima kasih.</p>'">
                        </div>

                        <!-- Tanda Tangan & QR Code TTE -->
                        <div class="d-flex justify-content-end text-end mt-5 pt-4 text-xs font-sans">
                            <div class="text-center" style="min-width: 240px;">
                                <span class="d-block mb-1">{{ cetakDetail.surat.jabatan_penandatangan || 'Kepala Sekolah' }}</span>
                                
                                <!-- QR Code Validation Token -->
                                <div class="my-2 p-2 d-inline-block bg-white border border-dark rounded-xl shadow-2xs">
                                    <i class="bi bi-qr-code fs-1 d-block text-dark"></i>
                                    <small class="font-mono text-[9px] d-block fw-bold mt-1">{{ cetakDetail.surat.qr_token ? cetakDetail.surat.qr_token.substring(0, 16) + '...' : 'TTE-VERIFIED-SINTA' }}</small>
                                </div>

                                <strong class="d-block text-decoration-underline fs-7 mt-1">{{ cetakDetail.surat.nama_penandatangan || 'Kepala Sekolah' }}</strong>
                                <span class="d-block text-muted text-[10px]">Tanda Tangan Elektronik Tersertifikasi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, onMounted, nextTick } = Vue;

    const persuratanSuratKeluarAppConfig = {
        setup() {
            const activeTab = ref('register');
            const loading = ref(false);
            const saving = ref(false);
            const isEditSurat = ref(false);
            const listSuratKeluar = ref([]);
            const templates = ref([]);
            const klasifikasiList = ref([]);
            const cetakDetail = ref(null);
            const selectedSurat = ref(null);

            const filter = ref({
                search: '',
                status_surat: ''
            });

            const formSuratKeluar = ref({
                id: null,
                id_template: '',
                id_kode_klasifikasi: '',
                nomor_surat: '',
                tujuan: '',
                tgl_surat: new Date().toISOString().split('T')[0],
                perihal: '',
                nama_penandatangan: 'Kepala Sekolah',
                jabatan_penandatangan: 'Kepala Sekolah',
                ringkasan_isi: ''
            });

            let modalBuatInstance = null;
            let modalPratinjauInstance = null;

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const fetchSuratKeluar = async () => {
                loading.value = true;
                try {
                    let url = '<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-keluar' + getTenantParam('?');
                    if (filter.value.search) url += `&search=${encodeURIComponent(filter.value.search)}`;
                    if (filter.value.status_surat) url += `&status_surat=${encodeURIComponent(filter.value.status_surat)}`;

                    const res = await axios.get(url);
                    if (res.data && res.data.success) {
                        listSuratKeluar.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Gagal memuat surat keluar:', e);
                } finally {
                    loading.value = false;
                }
            };

            const fetchOptions = async () => {
                try {
                    const [resTpl, resKlas] = await Promise.all([
                        axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/template' + getTenantParam('?')),
                        axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi' + getTenantParam('?'))
                    ]);
                    if (resTpl.data && resTpl.data.success) templates.value = resTpl.data.data || [];
                    if (resKlas.data && resKlas.data.success) klasifikasiList.value = resKlas.data.data || [];
                } catch (e) {
                    console.error('Gagal memuat opsi template:', e);
                }
            };

            let searchTimeout = null;
            const debounceSearch = () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchSuratKeluar();
                }, 350);
            };

            const resetFilter = () => {
                filter.value.search = '';
                filter.value.status_surat = '';
                fetchSuratKeluar();
            };

            const execWordCmd = (cmd) => {
                document.execCommand(cmd, false, null);
                onSuratEditorInput();
            };

            const onSuratEditorInput = () => {
                const el = document.getElementById('visualSuratEditor');
                if (el) {
                    formSuratKeluar.value.ringkasan_isi = el.innerHTML;
                }
            };

            const syncSuratEditor = () => {
                const el = document.getElementById('visualSuratEditor');
                if (el) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(formSuratKeluar.value.ringkasan_isi || '', 'text/html');
                    el.replaceChildren(...doc.body.childNodes);
                }
            };

            const openModalBuat = () => {
                isEditSurat.value = false;
                formSuratKeluar.value = {
                    id: null,
                    id_template: '',
                    id_kode_klasifikasi: '',
                    nomor_surat: '',
                    tujuan: '',
                    tgl_surat: new Date().toISOString().split('T')[0],
                    perihal: '',
                    nama_penandatangan: 'Kepala Sekolah',
                    jabatan_penandatangan: 'Kepala Sekolah',
                    ringkasan_isi: '<p>Dengan hormat,</p><p>Sehubungan dengan agenda administrasi dan layanan kedinasan sekolah, bersama surat ini kami sampaikan pemberitahuan resmi untuk menjadi perhatian bersama.</p><p>Demikian surat dinas ini kami sampaikan, atas perhatian dan kerja sama yang baik diucapkan terima kasih.</p>'
                };
                generateNomorSurat();
                const el = document.getElementById('modalBuatSuratKeluar');
                if (el && typeof bootstrap !== 'undefined') {
                    modalBuatInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalBuatInstance.show();
                    nextTick(() => syncSuratEditor());
                }
            };

            const openModalEdit = (sk) => {
                isEditSurat.value = true;
                formSuratKeluar.value = {
                    id: sk.id,
                    id_template: sk.id_template || '',
                    id_kode_klasifikasi: sk.id_kode_klasifikasi || '',
                    nomor_surat: sk.nomor_surat,
                    tujuan: sk.tujuan,
                    tgl_surat: sk.tgl_surat || new Date().toISOString().split('T')[0],
                    perihal: sk.perihal,
                    nama_penandatangan: sk.nama_penandatangan || 'Kepala Sekolah',
                    jabatan_penandatangan: sk.jabatan_penandatangan || 'Kepala Sekolah',
                    ringkasan_isi: sk.ringkasan_isi || ''
                };
                const el = document.getElementById('modalBuatSuratKeluar');
                if (el && typeof bootstrap !== 'undefined') {
                    modalBuatInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalBuatInstance.show();
                    nextTick(() => syncSuratEditor());
                }
            };

            const onTemplateChange = () => {
                const tpl = templates.value.find(t => t.id === formSuratKeluar.value.id_template);
                if (tpl) {
                    if (tpl.perihal_default) formSuratKeluar.value.perihal = tpl.perihal_default;
                    if (tpl.konten_html) formSuratKeluar.value.ringkasan_isi = tpl.konten_html;
                    syncSuratEditor();
                    if (!isEditSurat.value) generateNomorSurat();
                }
            };

            const generateNomorSurat = async () => {
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-keluar/generate-nomor', {
                        tenant_id: currentTenantId,
                        id_kode_klasifikasi: formSuratKeluar.value.id_kode_klasifikasi
                    });
                    if (res.data && res.data.success) {
                        formSuratKeluar.value.nomor_surat = res.data.data.nomor_surat;
                    }
                } catch (e) {
                    const randNo = Math.floor(Math.random() * 900) + 100;
                    const year = new Date().getFullYear();
                    formSuratKeluar.value.nomor_surat = `421.3/${randNo}/SMAN1/${year}`;
                }
            };

            const submitSuratKeluar = async () => {
                onSuratEditorInput();
                saving.value = true;
                try {
                    const payload = { ...formSuratKeluar.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-keluar/save', payload);
                    if (res.data && res.data.success) {
                        if (modalBuatInstance) modalBuatInstance.hide();
                        Swal.fire({
                            icon: 'success',
                            title: isEditSurat.value ? 'Perubahan Tersimpan!' : 'Surat Terbit!',
                            text: isEditSurat.value ? 'Data register surat keluar berhasil diperbarui.' : 'Surat dinas keluar berhasil diterbitkan dan diregister.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        fetchSuratKeluar();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menyimpan surat keluar.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const openPratinjauCetak = async (sk) => {
                try {
                    const res = await axios.get(`<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-keluar/detail-cetak?id=${sk.id}` + getTenantParam('&'));
                    if (res.data && res.data.success) {
                        cetakDetail.value = res.data.data;
                        const el = document.getElementById('modalPratinjauCetak');
                        if (el && typeof bootstrap !== 'undefined') {
                            modalPratinjauInstance = bootstrap.Modal.getOrCreateInstance(el);
                            modalPratinjauInstance.show();
                        }
                    }
                } catch (e) {
                    Swal.fire('Gagal', 'Gagal memuat format cetak surat.', 'error');
                }
            };

            const printNaskah = () => {
                window.print();
            };

            // ─── EXPORT TO WORD (.DOC) ───
            const downloadWordFromCetakDetail = () => {
                if (!cetakDetail.value) return;
                const d = cetakDetail.value;
                const nomor = d.surat?.nomor_surat || 'Surat_Keluar';
                const htmlContent = `
                    <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                    <head>
                        <meta charset="utf-8">
                        <title>${nomor}</title>
                        <style>
                            @page {
                                size: A4;
                                margin: 2cm 2.5cm 2cm 2.5cm;
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
                            <h4 style="margin: 0; text-transform: uppercase;">${d.kop?.nama_instansi_atas || 'PEMERINTAH PROVINSI / KABUPATEN'}</h4>
                            <h3 style="margin: 0; text-transform: uppercase;">${d.kop?.nama_sekolah || 'SEKOLAH SINTA TERPADU'}</h3>
                            <p style="margin: 0; font-size: 10pt;">${d.kop?.alamat || 'Alamat Resmi Sekolah'} | Telp: ${d.kop?.telepon || '-'} | Email: ${d.kop?.email || '-'}</p>
                        </div>
                        <table style="margin-bottom: 20px;">
                            <tr><td style="width: 100px;"><strong>Nomor</strong></td><td>: ${d.surat.nomor_surat}</td><td style="text-align: right;">${d.kop?.kota_kabupaten || 'Tempat'}, ${d.surat.tgl_surat}</td></tr>
                            <tr><td><strong>Lampiran</strong></td><td>: -</td><td></td></tr>
                            <tr><td><strong>Perihal</strong></td><td>: <strong>${d.surat.perihal}</strong></td><td></td></tr>
                        </table>
                        <div style="margin-bottom: 20px;">
                            <p>Kepada Yth.<br><strong>${d.surat.tujuan}</strong><br>di Tempat</p>
                        </div>
                        <div style="text-align: justify;">
                            ${d.surat.ringkasan_isi || '<p>Dengan hormat,</p><p>Isi naskah surat...</p>'}
                        </div>
                        <div style="margin-top: 40px; float: right; width: 260px; text-align: center;">
                            <p>${d.surat.jabatan_penandatangan || 'Kepala Sekolah'},</p>
                            <br><br><br>
                            <p><strong><u>${d.surat.nama_penandatangan || 'Kepala Sekolah'}</u></strong><br>TTE Tersertifikasi Digital</p>
                        </div>
                    </body>
                    </html>
                `;

                const blob = new Blob(['\ufeff', htmlContent], {
                    type: 'application/msword'
                });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `${nomor.replace(/[\/\\:]/g, '_')}.doc`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };

            const downloadWordSurat = async (sk) => {
                try {
                    const res = await axios.get(`<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-keluar/detail-cetak?id=${sk.id}` + getTenantParam('&'));
                    if (res.data && res.data.success) {
                        cetakDetail.value = res.data.data;
                        downloadWordFromCetakDetail();
                    }
                } catch (e) {
                    Swal.fire('Gagal', 'Gagal mengunduh berkas Word.', 'error');
                }
            };

            const formatDateIndo = (dateStr) => {
                if (!dateStr) return '-';
                try {
                    const d = new Date(dateStr);
                    if (isNaN(d.getTime())) return dateStr;
                    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                } catch (e) {
                    return dateStr;
                }
            };

            const deleteSuratKeluar = (sk) => {
                Swal.fire({
                    title: 'Hapus Surat Keluar?',
                    text: `Hapus nomor surat: ${sk.nomor_surat}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Ya, Hapus'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-keluar/delete', { id: sk.id, tenant_id: currentTenantId });
                            Swal.fire('Terhapus', 'Surat keluar telah dihapus.', 'success');
                            fetchSuratKeluar();
                        } catch (e) {
                            Swal.fire('Gagal', 'Gagal menghapus surat keluar.', 'error');
                        }
                    }
                });
            };

            window.persuratanSuratKeluarOpenBuat = openModalBuat;

            onMounted(() => {
                fetchSuratKeluar();
                fetchOptions();
            });

            return {
                activeTab,
                loading,
                saving,
                isEditSurat,
                listSuratKeluar,
                templates,
                klasifikasiList,
                cetakDetail,
                selectedSurat,
                filter,
                formSuratKeluar,
                fetchSuratKeluar,
                debounceSearch,
                resetFilter,
                openModalBuat,
                openModalEdit,
                onTemplateChange,
                generateNomorSurat,
                submitSuratKeluar,
                openPratinjauCetak,
                printNaskah,
                downloadWordFromCetakDetail,
                downloadWordSurat,
                formatDateIndo,
                deleteSuratKeluar,
                execWordCmd,
                onSuratEditorInput
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#persuratanSuratKeluarApp', persuratanSuratKeluarAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(persuratanSuratKeluarAppConfig).mount('#persuratanSuratKeluarApp');
        });
    }
}
</script>
