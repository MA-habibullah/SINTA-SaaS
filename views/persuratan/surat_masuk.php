<?php
/**
 * View: Buku Agenda Surat Masuk & Disposisi Digital
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
$activeMenu = 'surat_masuk';
$pageTitle = 'Agenda Surat Masuk & Disposisi';
$pageSubtitle = 'Pencatatan buku agenda surat dinas masuk, pengarsipan berkas lampiran, dan lembar disposisi digital kepala sekolah.';
$pageIcon = 'bi-inbox-fill';
?>
<div id="persuratanSuratMasukApp" v-cloak class="container-fluid px-0">
    <!-- Hero Banner Header Mandiri -->
    <?php 
    $heroBadge = 'Buku Agenda & Disposisi';
    $pageTitle = 'Surat Masuk & Disposisi Digital';
    $pageSubtitle = 'Pencatatan buku agenda surat dinas masuk, pengarsipan berkas lampiran, dan lembar disposisi digital kepala sekolah.';
    $pageIcon = 'bi-inbox-fill';
    $heroButtons = '
        <button type="button" class="btn btn-sm rounded-xl px-3.5 py-2 text-xs font-bold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all d-inline-flex align-items-center gap-1.5 backdrop-blur-md" onclick="window.persuratanSuratMasukOpenCatat && window.persuratanSuratMasukOpenCatat()">
            <i class="bi bi-plus-circle-fill"></i> Catat Surat Masuk
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
                    onclick="document.getElementById('suratMasukNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Container Deretan Tab -->
            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="suratMasukNavTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'agenda'}" 
                                @click="activeTab = 'agenda'">
                            <i class="bi bi-journal-text me-2 fs-6"></i> 1. Buku Agenda Surat Masuk
                            <span class="badge bg-slate-100 text-slate-700 ms-2 rounded-pill text-[11px]">{{ listSuratMasuk.length }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'disposisi'}" 
                                @click="activeTab = 'disposisi'">
                            <i class="bi bi-hourglass-split me-2 fs-6 text-amber-500"></i> 2. Monitoring Disposisi Pimpinan
                            <span v-if="countPendingDisposisi > 0" class="badge bg-amber-500 text-white rounded-pill ms-2 px-2 py-0.5 text-[10px] font-bold">{{ countPendingDisposisi }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- 1 Tombol Panah Kanan -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('suratMasukNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Tombol Aksi Tambahan / Segarkan Data -->
            <div class="d-none d-md-flex align-items-center ps-2 pe-1 border-s border-slate-200/80 ms-2 gap-2">
                <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl px-3 py-2 text-xs font-bold shadow-2xs d-flex align-items-center gap-1.5" @click="fetchSuratMasuk" title="Segarkan Data">
                    <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i>
                    <span>Segarkan</span>
                </button>
                <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalCatat()" title="Catat Surat Masuk Baru">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Catat Surat Masuk</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TAB 1: BUKU AGENDA SURAT MASUK -->
    <div v-show="activeTab === 'agenda'" class="card border border-slate-200/80 shadow-2xs rounded-3xl bg-white overflow-hidden mb-5">
        <!-- ═══ EXECUTIVE FILTER & SEARCH TOOLBAR ═══ -->
        <div class="p-3.5 p-md-4 border-b border-slate-200/80 bg-slate-50/80">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <!-- Sisi Kiri: Search Input & Filter Status -->
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <!-- Search Input Box -->
                    <div class="position-relative flex-grow-1" style="min-width: 260px; max-width: 440px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3.5 text-blue-500 fs-7 pointer-events-none"></i>
                        <input type="text" v-model="filter.search" @input="debounceSearch()"
                               class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-semibold bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 shadow-2xs transition"
                               placeholder="Cari nomor surat, pengirim, perihal, atau no. agenda...">
                        <button v-if="filter.search" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 p-0 border-0 bg-transparent" @click="filter.search = ''; fetchSuratMasuk()">
                            <i class="bi bi-x-circle-fill fs-7"></i>
                        </button>
                    </div>

                    <!-- Status Disposisi Filter -->
                    <select v-model="filter.status_disposisi" @change="fetchSuratMasuk()" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer focus:border-blue-500" style="width: auto; min-width: 190px;">
                        <option value="">— Semua Status Disposisi —</option>
                        <option value="Menunggu Disposisi">Menunggu Disposisi</option>
                        <option value="Didisposisikan">Didisposisikan</option>
                    </select>

                    <button v-if="filter.search || filter.status_disposisi" type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl text-xs font-bold px-3 py-1.5 shadow-2xs text-slate-600 hover:bg-slate-100 d-inline-flex align-items-center gap-1" @click="resetFilter()">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>

                <!-- Sisi Kanan: Total Naskah Badge & Tombol Catat -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="badge bg-white border border-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-xs shadow-2xs d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-inbox-fill text-blue-600"></i>
                        <span>Total: <strong class="text-blue-700">{{ listSuratMasuk.length }}</strong> Naskah</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-2xs d-flex align-items-center gap-1.5 transition hover:shadow-xs" @click="openModalCatat()">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Catat Surat Masuk</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Area -->
        <div class="p-0">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat buku agenda surat masuk...
            </div>

            <div v-else-if="listSuratMasuk.length === 0" class="text-center py-5 px-3">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-500 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-inner">
                    <i class="bi bi-inbox"></i>
                </div>
                <div class="font-bold text-slate-800 text-base mb-1">Belum Ada Agenda Surat Masuk</div>
                <p class="text-slate-500 text-xs mb-3 mx-auto" style="max-width: 440px;">
                    Tidak ditemukan rekaman surat masuk yang sesuai dengan filter pencarian aktif. Klik tombol di bawah untuk mencatat surat masuk baru.
                </p>
                <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 text-xs shadow-2xs" @click="openModalCatat()">
                    <i class="bi bi-plus-circle-fill me-1"></i> Catat Surat Masuk Sekarang
                </button>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle text-xs mb-0">
                    <thead class="bg-slate-50/90 border-b border-slate-200/80 text-slate-500 font-bold uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="ps-4 py-3" style="width: 130px;">No. Agenda</th>
                            <th class="py-3" style="min-width: 280px;">Nomor &amp; Perihal Surat</th>
                            <th class="py-3" style="min-width: 200px;">Instansi / Pengirim</th>
                            <th class="py-3 text-center" style="width: 120px;">Tgl Terima</th>
                            <th class="py-3 text-center" style="width: 140px;">Status Disposisi</th>
                            <th class="py-3 text-end pe-4" style="width: 170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="sm in listSuratMasuk" :key="sm.id" class="hover:bg-blue-50/30 transition">
                            <!-- No. Agenda -->
                            <td class="ps-4 py-3 font-mono font-bold text-blue-700">
                                <span class="badge bg-blue-50 text-blue-800 border border-blue-200 rounded-lg px-2 py-1 font-mono text-[11px]">
                                    {{ sm.no_agenda || '-' }}
                                </span>
                            </td>

                            <!-- Nomor & Perihal Surat -->
                            <td class="py-3">
                                <div class="font-bold text-slate-900 fs-7 mb-0.5">{{ sm.no_surat }}</div>
                                <div class="text-slate-600 text-xs line-clamp-1 mb-1 font-medium" :title="sm.perihal">{{ sm.perihal }}</div>
                                <div class="d-flex flex-wrap align-items-center gap-2 text-[11px] text-slate-400">
                                    <span><i class="bi bi-calendar-event me-1"></i>Tgl Surat: {{ formatDateIndo(sm.tgl_surat) }}</span>
                                    <span v-if="sm.sifat_surat" class="badge bg-slate-100 text-slate-600 rounded-md px-1.5 py-0.5 text-[10px]">{{ sm.sifat_surat }}</span>
                                    
                                    <!-- File Lampiran Badge -->
                                    <a v-if="sm.file_lampiran" :href="getAttachmentUrl(sm.file_lampiran)" target="_blank" class="badge bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 rounded-md px-2 py-0.5 text-[10px] text-decoration-none d-inline-flex align-items-center gap-1 shadow-2xs" title="Buka Dokumen / Bukti Lampiran">
                                        <i class="bi bi-file-earmark-arrow-down-fill text-blue-600"></i> Berkas Lampiran
                                    </a>
                                </div>
                            </td>

                            <!-- Pengirim -->
                            <td class="py-3 font-semibold text-slate-700">
                                <div class="d-flex align-items-center gap-1.5">
                                    <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-500 d-flex align-items-center justify-content-center flex-shrink-0">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <span>{{ sm.pengirim }}</span>
                                </div>
                            </td>

                            <!-- Tgl Terima -->
                            <td class="py-3 text-center text-slate-600 font-medium">
                                {{ formatDateIndo(sm.tgl_terima) }}
                            </td>

                            <!-- Status Disposisi -->
                            <td class="py-3 text-center">
                                <span v-if="sm.status_disposisi === 'Didisposisikan'" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 font-bold text-[10px] d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-check2-circle"></i> Didisposisikan
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2.5 py-1 font-bold text-[10px] d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-hourglass-split"></i> Menunggu
                                </span>
                            </td>

                            <!-- Aksi Buttons -->
                            <td class="py-3 text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-xs btn-outline-warning text-amber-700 hover:bg-amber-50 border-amber-300 rounded-lg px-2.5 py-1 font-bold shadow-2xs" @click="openModalEdit(sm)" title="Edit Agenda Surat Masuk">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-primary rounded-lg px-2.5 py-1 font-bold shadow-2xs" @click="openModalDisposisi(sm)" title="Lembar Disposisi Digital">
                                        <i class="bi bi-pencil-square me-1"></i> Disposisi
                                    </button>
                                    <button type="button" class="btn btn-xs btn-light border border-slate-200 text-rose-600 hover:bg-rose-50 rounded-lg p-1 shadow-2xs" @click="deleteSuratMasuk(sm)" title="Hapus Surat Masuk">
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

    <!-- TAB 2: MONITORING DISPOSISI PIMPINAN -->
    <div v-show="activeTab === 'disposisi'" class="card border border-slate-200/80 shadow-2xs rounded-3xl bg-white overflow-hidden mb-5">
        <div class="p-3.5 p-md-4 border-b border-slate-200/80 bg-slate-50/80 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2.5">
            <div class="d-flex align-items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 d-flex align-items-center justify-content-center fs-5 border border-amber-400/30 flex-shrink-0">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <h6 class="font-bold text-slate-800 fs-6 mb-0.5 d-flex align-items-center gap-2">
                        Monitoring &amp; Lembar Disposisi Pimpinan
                    </h6>
                    <small class="text-slate-400 text-xs">Daftar naskah masuk yang memerlukan arahan dan instruksi tindak lanjut kepala sekolah</small>
                </div>
            </div>
            <span class="badge px-3.5 py-2 rounded-xl text-xs font-bold shadow-2xs d-inline-flex align-items-center gap-1.5" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                <i class="bi bi-bell-fill"></i>
                <span>{{ countPendingDisposisi }} Menunggu Disposisi</span>
            </span>
        </div>

        <div class="p-0">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat data disposisi...
            </div>
            <div v-else-if="listSuratMasuk.length === 0" class="text-center py-5 text-slate-400 text-xs">
                <i class="bi bi-inbox fs-1 d-block text-slate-300 mb-2"></i>
                Belum ada naskah surat masuk yang tercatat.
            </div>
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200">
                        <tr>
                            <th class="ps-4 py-3" style="width: 25%;">Nomor &amp; Pengirim</th>
                            <th class="py-3" style="width: 30%;">Pokok Perihal Surat</th>
                            <th class="py-3 text-center" style="width: 20%;">Penerima Disposisi</th>
                            <th class="py-3 text-center" style="width: 12%;">Batas Waktu</th>
                            <th class="py-3 text-end pe-4" style="width: 13%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="sm in listSuratMasuk" :key="'disp-'+sm.id" class="hover:bg-slate-50 transition">
                            <td class="ps-4 py-3">
                                <div class="font-bold text-slate-900 fs-7 mb-0.5">{{ sm.no_surat }}</div>
                                <div class="text-slate-500 text-xs">{{ sm.pengirim }}</div>
                                <div v-if="sm.file_lampiran" class="mt-1">
                                    <a :href="getAttachmentUrl(sm.file_lampiran)" target="_blank" class="badge bg-blue-50 text-blue-700 border border-blue-200 rounded text-[10px] text-decoration-none d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-paperclip"></i> Lihat Lampiran
                                    </a>
                                </div>
                            </td>
                            <td class="py-3 font-semibold text-slate-800">
                                <div class="line-clamp-2">{{ sm.perihal }}</div>
                                <span class="badge bg-slate-100 text-slate-500 rounded px-1.5 py-0.5 text-[10px] mt-1">Diterima: {{ formatDateIndo(sm.tgl_terima) }}</span>
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="sm.nama_penerima_disposisi" class="badge bg-blue-50 text-blue-700 border border-blue-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-person-fill me-1"></i> {{ sm.nama_penerima_disposisi }}
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-clock me-1"></i> Belum Didisposisi
                                </span>
                            </td>
                            <td class="py-3 text-center font-medium text-slate-600">
                                {{ sm.batas_waktu ? formatDateIndo(sm.batas_waktu) : '-' }}
                            </td>
                            <td class="py-3 text-end pe-4">
                                <button type="button" class="btn btn-xs btn-primary rounded-lg px-2.5 py-1 font-bold shadow-2xs" @click="openModalDisposisi(sm)">
                                    <i class="bi bi-pencil-square me-1"></i> {{ sm.nama_penerima_disposisi ? 'Ubah Disposisi' : 'Beri Disposisi' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL 1: CATAT & EDIT SURAT MASUK (DENGAN AUTO-COMPRESS / RESIZE UPLOAD)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalCatatSuratMasuk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-blue-500/20 text-blue-400 d-flex align-items-center justify-content-center fs-5 shadow-2xs border border-blue-400/30">
                            <i class="bi bi-inbox-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0 text-white">{{ isEditSurat ? 'Edit Agenda Surat Masuk' : 'Pencatatan Surat Masuk Baru' }}</h5>
                            <small class="text-slate-400 text-xs">Registrasi naskah dinas masuk & pengarsipan berkas dokumen digital</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitSuratMasuk">
                    <div class="modal-body p-4 bg-slate-50/50 text-xs">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Nomor Surat Masuk <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratMasuk.no_surat" class="form-control form-control-sm rounded-xl font-mono" placeholder="Contoh: 421/089/Disdik/2026" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Instansi / Pengirim Surat <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratMasuk.pengirim" class="form-control form-control-sm rounded-xl" placeholder="Contoh: Dinas Pendidikan Provinsi" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Tanggal Naskah Surat <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="formSuratMasuk.tgl_surat" class="form-control form-control-sm rounded-xl" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Tanggal Diterima Sekolah <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="formSuratMasuk.tgl_terima" class="form-control form-control-sm rounded-xl" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Perihal / Pokok Isi Surat <span class="text-rose-500">*</span></label>
                                <textarea v-model="formSuratMasuk.perihal" rows="2" class="form-control form-control-sm rounded-xl" placeholder="Ketik ringkasan pokok perihal surat..." required></textarea>
                            </div>
                            <div class="col-6 col-md-6">
                                <label class="form-label font-bold text-slate-700">Sifat Surat</label>
                                <select v-model="formSuratMasuk.sifat_surat" class="form-select form-select-sm rounded-xl">
                                    <option value="Biasa">Biasa</option>
                                    <option value="Penting">Penting</option>
                                    <option value="Segera">Segera</option>
                                    <option value="Kilat">Sangat Segera / Kilat</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-6">
                                <label class="form-label font-bold text-slate-700">Tingkat Keamanan</label>
                                <select v-model="formSuratMasuk.tingkat_keamanan" class="form-select form-select-sm rounded-xl">
                                    <option value="Biasa">Biasa</option>
                                    <option value="Terbatas">Terbatas</option>
                                    <option value="Rahasia">Rahasia</option>
                                    <option value="Sangat Rahasia">Sangat Rahasia</option>
                                </select>
                            </div>

                            <!-- UPLOAD BERKAS BUKTI / DOKUMEN SURAT MASUK (AUTO-COMPRESS) -->
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700 d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-paperclip text-primary me-1"></i> Upload Bukti Foto / Dokumen Naskah Fisik</span>
                                    <span class="text-slate-400 font-normal text-[11px]">Format: PDF, JPG, PNG, WEBP (Auto-Compress)</span>
                                </label>
                                
                                <!-- File Dropzone Area -->
                                <div class="p-3 bg-white rounded-2xl border-2 border-dashed border-slate-200 hover:border-blue-400 transition text-center position-relative">
                                    <input type="file" 
                                           id="inputLampiranSuratMasuk" 
                                           ref="fileInputRef" 
                                           @change="handleFileSelected" 
                                           accept=".pdf,.jpg,.jpeg,.png,.webp" 
                                           class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" 
                                           style="z-index: 5;">
                                    
                                    <!-- Jika belum memilih file baru -->
                                    <div v-if="!selectedFileLampiran && !formSuratMasuk.file_lampiran" class="py-2">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 d-inline-flex align-items-center justify-content-center fs-5 mb-1.5">
                                            <i class="bi bi-cloud-arrow-up-fill"></i>
                                        </div>
                                        <div class="font-bold text-slate-700 text-xs">Pilih atau Seret Berkas ke Sini</div>
                                        <small class="text-slate-400 text-[11px]">Foto hasil scan otomatis dikompresi agar hemat memori</small>
                                    </div>

                                    <!-- Jika ada file yang sudah dipilih -->
                                    <div v-else-if="selectedFileLampiran" class="py-1 d-flex align-items-center justify-content-between text-start px-2 position-relative" style="z-index: 6;">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <!-- Preview Icon / Image -->
                                            <img v-if="filePreviewUrl" :src="filePreviewUrl" class="w-12 h-12 object-cover rounded-xl border border-slate-200 shadow-2xs flex-shrink-0">
                                            <div v-else class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 d-flex align-items-center justify-content-center fs-4 flex-shrink-0">
                                                <i class="bi bi-file-earmark-pdf-fill"></i>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-xs text-truncate" style="max-width: 320px;">{{ selectedFileLampiran.name }}</div>
                                                <div class="text-[11px] text-slate-500 d-flex align-items-center gap-2">
                                                    <span>Ukuran: <strong>{{ formatFileSize(selectedFileLampiran.size) }}</strong></span>
                                                    <span v-if="compressionStats" class="badge bg-emerald-100 text-emerald-800 rounded px-1.5 py-0.5 text-[10px] font-bold">
                                                        <i class="bi bi-lightning-charge-fill"></i> {{ compressionStats }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-xs btn-light border border-slate-200 text-rose-600 hover:bg-rose-50 rounded-lg p-1.5" @click="removeSelectedFile()" title="Hapus Berkas">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>

                                    <!-- Jika sedang mode Edit dan ada file lama tersimpan -->
                                    <div v-else-if="formSuratMasuk.file_lampiran" class="py-1 d-flex align-items-center justify-content-between text-start px-2 position-relative" style="z-index: 6;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center fs-5">
                                                <i class="bi bi-file-earmark-check-fill"></i>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-xs">Berkas Lampiran Tersimpan</div>
                                                <a :href="getAttachmentUrl(formSuratMasuk.file_lampiran)" target="_blank" class="text-[11px] text-blue-600 font-semibold text-decoration-none hover:underline d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-box-arrow-up-right"></i> Buka / Unduh Berkas
                                                </a>
                                            </div>
                                        </div>
                                        <label for="inputLampiranSuratMasuk" class="btn btn-xs btn-light border border-slate-200 rounded-lg px-2.5 py-1 text-slate-600 font-bold hover:bg-slate-100 cursor-pointer">
                                            <i class="bi bi-arrow-repeat me-1"></i> Ganti Berkas
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3.5 font-semibold text-xs text-slate-600" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs d-inline-flex align-items-center gap-1.5" :disabled="saving || compressing">
                            <span v-if="saving || compressing" class="spinner-border spinner-border-sm"></span>
                            <span>{{ isEditSurat ? 'Simpan Perubahan' : 'Simpan Surat Masuk' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL 2: LEMBAR DISPOSISI DIGITAL KEPALA SEKOLAH (MODERN LUXURY UI)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalDisposisi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden" v-if="selectedSurat">
                <div class="modal-header p-4 border-0" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important; color: #ffffff !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-400/20 text-amber-300 d-flex align-items-center justify-content-center fs-5 shadow-xs border border-amber-300/30">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0 text-white">Lembar Disposisi Digital Pimpinan</h5>
                            <small class="text-blue-100 text-xs d-flex align-items-center gap-1.5 mt-0.5">
                                <span>Instruksi tindak lanjut surat masuk:</span>
                                <span class="badge bg-white/20 text-white font-mono px-2 py-0.5 rounded-md">{{ selectedSurat.no_surat }}</span>
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitDisposisi">
                    <div class="modal-body p-4 bg-slate-50/60 text-xs">
                        <!-- Summary Banner -->
                        <div class="p-3.5 bg-white rounded-2xl border border-slate-200/90 mb-3.5 shadow-2xs">
                            <div class="row g-2 align-items-center">
                                <div class="col-12 col-md-6 border-b md:border-b-0 md:border-r border-slate-100 pb-2 md:pb-0 pe-md-3">
                                    <div class="text-[11px] font-bold uppercase text-slate-400 tracking-wider mb-1">Pengirim Naskah</div>
                                    <div class="font-bold text-slate-900 fs-7 d-flex align-items-center gap-1.5">
                                        <i class="bi bi-building text-blue-600"></i>
                                        {{ selectedSurat.pengirim }}
                                    </div>
                                    <div class="text-slate-500 mt-1 text-[11px] d-flex align-items-center gap-2">
                                        <span><i class="bi bi-calendar3 me-1"></i> Diterima: {{ formatDateIndo(selectedSurat.tgl_terima) }}</span>
                                        <a v-if="selectedSurat.file_lampiran" :href="getAttachmentUrl(selectedSurat.file_lampiran)" target="_blank" class="badge bg-blue-50 text-blue-700 border border-blue-200 rounded text-[10px] text-decoration-none hover:underline">
                                            <i class="bi bi-paperclip me-0.5"></i> Buka Naskah Fisik
                                        </a>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 ps-md-3 pt-2 md:pt-0">
                                    <div class="text-[11px] font-bold uppercase text-slate-400 tracking-wider mb-1 d-flex justify-content-between align-items-center">
                                        <span>Perihal Surat</span>
                                        <span class="badge bg-blue-50 text-blue-700 border border-blue-200 rounded-md font-semibold text-[10px]">
                                            Sifat: {{ selectedSurat.sifat_surat || 'Biasa' }}
                                        </span>
                                    </div>
                                    <div class="text-slate-800 font-semibold line-clamp-2">{{ selectedSurat.perihal }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-7">
                                <label class="form-label font-bold text-slate-700 d-flex align-items-center gap-1">
                                    <i class="bi bi-person-check-fill text-blue-600"></i> Diteruskan / Disposisi Kepada <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" v-model="formDisposisi.nama_penerima_disposisi" class="form-control form-control-sm rounded-xl" placeholder="Contoh: Waka Kurikulum / Guru BK / Bendahara BOS" required>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label font-bold text-slate-700 d-flex align-items-center gap-1">
                                    <i class="bi bi-alarm-fill text-amber-500"></i> Batas Waktu Tindak Lanjut
                                </label>
                                <input type="date" v-model="formDisposisi.batas_waktu" class="form-control form-control-sm rounded-xl">
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700 d-flex align-items-center gap-1">
                                    <i class="bi bi-chat-left-text-fill text-primary"></i> Instruksi / Petunjuk Disposisi <span class="text-rose-500">*</span>
                                </label>
                                <textarea v-model="formDisposisi.instruksi_disposisi" rows="3" class="form-control form-control-sm rounded-xl" placeholder="Ketik instruksi tindak lanjut pimpinan (misal: 'Harap koordinasikan dengan tim dan siapkan laporan sebelum tanggal...')" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Catatan Tambahan Pimpinan</label>
                                <input type="text" v-model="formDisposisi.catatan" class="form-control form-control-sm rounded-xl" placeholder="Catatan khusus (opsional)...">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3.5 font-semibold text-xs" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span>Simpan Lembar Disposisi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, onMounted, computed } = Vue;

    const persuratanSuratMasukAppConfig = {
        setup() {
            const activeTab = ref('agenda');
            const loading = ref(false);
            const saving = ref(false);
            const compressing = ref(false);
            const isEditSurat = ref(false);
            const listSuratMasuk = ref([]);
            const selectedSurat = ref(null);

            const selectedFileLampiran = ref(null);
            const filePreviewUrl = ref(null);
            const compressionStats = ref('');
            const fileInputRef = ref(null);

            const filter = ref({
                search: '',
                status_disposisi: ''
            });

            const formSuratMasuk = ref({
                id: null,
                no_agenda: '',
                no_surat: '',
                pengirim: '',
                perihal: '',
                tgl_surat: new Date().toISOString().split('T')[0],
                tgl_terima: new Date().toISOString().split('T')[0],
                sifat_surat: 'Biasa',
                tingkat_keamanan: 'Biasa',
                file_lampiran: ''
            });

            const formDisposisi = ref({
                id_surat_masuk: '',
                nama_penerima_disposisi: '',
                instruksi_disposisi: '',
                batas_waktu: '',
                catatan: ''
            });

            let modalCatatInstance = null;
            let modalDisposisiInstance = null;

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            // ─── CLIENT-SIDE AUTO-COMPRESS / RESIZE IMAGE (CANVAS BASED) ───
            const compressImage = (file, maxWidth = 1600, maxHeight = 1600, quality = 0.82) => {
                return new Promise((resolve) => {
                    if (!file.type.startsWith('image/')) {
                        return resolve(file);
                    }
                    const reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = (event) => {
                        const img = new Image();
                        img.src = event.target.result;
                        img.onload = () => {
                            let width = img.width;
                            let height = img.height;
                            if (width > maxWidth || height > maxHeight) {
                                if (width > height) {
                                    height = Math.round((height * maxWidth) / width);
                                    width = maxWidth;
                                } else {
                                    width = Math.round((width * maxHeight) / height);
                                    height = maxHeight;
                                }
                            }
                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, width, height);
                            canvas.toBlob((blob) => {
                                if (blob && blob.size < file.size) {
                                    const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".webp", {
                                        type: 'image/webp',
                                        lastModified: Date.now()
                                    });
                                    resolve(compressedFile);
                                } else {
                                    resolve(file);
                                }
                            }, 'image/webp', quality);
                        };
                        img.onerror = () => resolve(file);
                    };
                    reader.onerror = () => resolve(file);
                });
            };

            const handleFileSelected = async (event) => {
                const file = event.target.files[0];
                if (!file) return;

                const origSize = file.size;
                if (file.type.startsWith('image/')) {
                    compressing.value = true;
                    try {
                        const compressed = await compressImage(file);
                        selectedFileLampiran.value = compressed;
                        filePreviewUrl.value = URL.createObjectURL(compressed);
                        if (compressed.size < origSize) {
                            const percent = Math.round((1 - compressed.size / origSize) * 100);
                            compressionStats.value = `Auto-Compress -${percent}% (${formatFileSize(compressed.size)})`;
                        } else {
                            compressionStats.value = 'Ukuran Optimal';
                        }
                    } catch (err) {
                        selectedFileLampiran.value = file;
                        filePreviewUrl.value = URL.createObjectURL(file);
                    } finally {
                        compressing.value = false;
                    }
                } else {
                    // Dokumen PDF dll
                    selectedFileLampiran.value = file;
                    filePreviewUrl.value = null;
                    compressionStats.value = 'Dokumen PDF';
                }
            };

            const removeSelectedFile = () => {
                selectedFileLampiran.value = null;
                filePreviewUrl.value = null;
                compressionStats.value = '';
                if (fileInputRef.value) fileInputRef.value.value = '';
            };

            const formatFileSize = (bytes) => {
                if (!bytes || bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            };

            const getAttachmentUrl = (path) => {
                if (!path) return '#';
                if (path.startsWith('http://') || path.startsWith('https://')) return path;
                const clean = path.replace(/^\/+/, '');
                return '<?= $this->getBaseUrl() ?>/' + clean;
            };

            const fetchSuratMasuk = async () => {
                loading.value = true;
                try {
                    let url = '<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-masuk' + getTenantParam('?');
                    if (filter.value.search) url += `&search=${encodeURIComponent(filter.value.search)}`;
                    if (filter.value.status_disposisi) url += `&status_disposisi=${encodeURIComponent(filter.value.status_disposisi)}`;

                    const res = await axios.get(url);
                    if (res.data && res.data.success) {
                        listSuratMasuk.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Gagal memuat surat masuk:', e);
                } finally {
                    loading.value = false;
                }
            };

            let searchTimeout = null;
            const debounceSearch = () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchSuratMasuk();
                }, 350);
            };

            const resetFilter = () => {
                filter.value.search = '';
                filter.value.status_disposisi = '';
                fetchSuratMasuk();
            };

            const openModalCatat = () => {
                isEditSurat.value = false;
                removeSelectedFile();
                formSuratMasuk.value = {
                    id: null,
                    no_agenda: '',
                    no_surat: '',
                    pengirim: '',
                    perihal: '',
                    tgl_surat: new Date().toISOString().split('T')[0],
                    tgl_terima: new Date().toISOString().split('T')[0],
                    sifat_surat: 'Biasa',
                    tingkat_keamanan: 'Biasa',
                    file_lampiran: ''
                };
                const el = document.getElementById('modalCatatSuratMasuk');
                if (el && typeof bootstrap !== 'undefined') {
                    modalCatatInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalCatatInstance.show();
                }
            };

            const openModalEdit = (sm) => {
                isEditSurat.value = true;
                removeSelectedFile();
                formSuratMasuk.value = {
                    id: sm.id,
                    no_agenda: sm.no_agenda || '',
                    no_surat: sm.no_surat || '',
                    pengirim: sm.pengirim || '',
                    perihal: sm.perihal || '',
                    tgl_surat: sm.tgl_surat || new Date().toISOString().split('T')[0],
                    tgl_terima: sm.tgl_terima || new Date().toISOString().split('T')[0],
                    sifat_surat: sm.sifat_surat || 'Biasa',
                    tingkat_keamanan: sm.tingkat_keamanan || 'Biasa',
                    file_lampiran: sm.file_lampiran || ''
                };
                const el = document.getElementById('modalCatatSuratMasuk');
                if (el && typeof bootstrap !== 'undefined') {
                    modalCatatInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalCatatInstance.show();
                }
            };

            const submitSuratMasuk = async () => {
                saving.value = true;
                try {
                    const formData = new FormData();
                    if (formSuratMasuk.value.id) formData.append('id', formSuratMasuk.value.id);
                    formData.append('tenant_id', currentTenantId);
                    formData.append('no_surat', formSuratMasuk.value.no_surat);
                    formData.append('pengirim', formSuratMasuk.value.pengirim);
                    formData.append('perihal', formSuratMasuk.value.perihal);
                    formData.append('tgl_surat', formSuratMasuk.value.tgl_surat);
                    formData.append('tgl_terima', formSuratMasuk.value.tgl_terima);
                    formData.append('sifat_surat', formSuratMasuk.value.sifat_surat);
                    formData.append('tingkat_keamanan', formSuratMasuk.value.tingkat_keamanan);

                    if (selectedFileLampiran.value) {
                        formData.append('file_lampiran', selectedFileLampiran.value);
                    } else if (formSuratMasuk.value.file_lampiran) {
                        formData.append('file_lampiran', formSuratMasuk.value.file_lampiran);
                    }

                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-masuk/save', formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });

                    if (res.data && res.data.success) {
                        if (modalCatatInstance) modalCatatInstance.hide();
                        Swal.fire({
                            icon: 'success',
                            title: isEditSurat.value ? 'Perubahan Tersimpan!' : 'Berhasil!',
                            text: isEditSurat.value ? 'Data surat masuk dan lampiran berhasil diperbarui.' : 'Surat masuk berhasil dicatat ke dalam buku agenda.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        fetchSuratMasuk();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menyimpan surat masuk.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const openModalDisposisi = (sm) => {
                selectedSurat.value = sm;
                formDisposisi.value = {
                    id_surat_masuk: sm.id,
                    nama_penerima_disposisi: sm.nama_penerima_disposisi || '',
                    instruksi_disposisi: sm.instruksi_disposisi || '',
                    batas_waktu: sm.batas_waktu || '',
                    catatan: sm.catatan_disposisi || ''
                };
                const el = document.getElementById('modalDisposisi');
                if (el && typeof bootstrap !== 'undefined') {
                    modalDisposisiInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalDisposisiInstance.show();
                }
            };

            const submitDisposisi = async () => {
                saving.value = true;
                try {
                    const payload = { ...formDisposisi.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/disposisi/save', payload);
                    if (res.data && res.data.success) {
                        if (modalDisposisiInstance) modalDisposisiInstance.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Disposisi Tersimpan!',
                            text: 'Lembar disposisi digital pimpinan berhasil diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        fetchSuratMasuk();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menyimpan disposisi.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const deleteSuratMasuk = (sm) => {
                Swal.fire({
                    title: 'Hapus Surat Masuk?',
                    text: `Hapus nomor agenda surat: ${sm.no_surat}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Ya, Hapus'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-masuk/delete', { id: sm.id, tenant_id: currentTenantId });
                            Swal.fire('Terhapus', 'Surat masuk telah dihapus.', 'success');
                            fetchSuratMasuk();
                        } catch (e) {
                            Swal.fire('Gagal', 'Gagal menghapus surat masuk.', 'error');
                        }
                    }
                });
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

            const countPendingDisposisi = computed(() => {
                return listSuratMasuk.value.filter(s => s.status_disposisi === 'Menunggu Disposisi' || !s.nama_penerima_disposisi).length;
            });

            window.persuratanSuratMasukOpenCatat = openModalCatat;

            onMounted(() => {
                fetchSuratMasuk();
            });

            return {
                activeTab,
                loading,
                saving,
                compressing,
                isEditSurat,
                listSuratMasuk,
                countPendingDisposisi,
                selectedSurat,
                selectedFileLampiran,
                filePreviewUrl,
                compressionStats,
                fileInputRef,
                filter,
                formSuratMasuk,
                formDisposisi,
                fetchSuratMasuk,
                debounceSearch,
                resetFilter,
                openModalCatat,
                openModalEdit,
                submitSuratMasuk,
                openModalDisposisi,
                submitDisposisi,
                deleteSuratMasuk,
                handleFileSelected,
                removeSelectedFile,
                formatFileSize,
                getAttachmentUrl,
                formatDateIndo
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#persuratanSuratMasukApp', persuratanSuratMasukAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(persuratanSuratMasukAppConfig).mount('#persuratanSuratMasukApp');
        });
    }
}
</script>
