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
        <button type="button" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-bold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all d-inline-flex align-items-center gap-1.5" onclick="window.persuratanSuratMasukOpenCatat && window.persuratanSuratMasukOpenCatat()">
            <i class="bi bi-plus-circle-fill"></i> Catat Surat Masuk
        </button>
    ';
    include __DIR__ . '/_hero_header.php'; 
    ?>

    <!-- Navtab Lokal Khusus Internal Halaman Surat Masuk -->
    <div class="card border-0 shadow-xs rounded-2xl mb-4 bg-white" style="border: 1px solid #e2e8f0;">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <ul class="nav nav-pills gap-1.5 border-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeTab === 'agenda' }" 
                                @click="activeTab = 'agenda'" type="button">
                            <i class="bi bi-journal-text me-1.5 text-primary"></i> 1. Buku Agenda Surat Masuk
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeTab === 'disposisi' }" 
                                @click="activeTab = 'disposisi'" type="button">
                            <i class="bi bi-hourglass-split me-1.5 text-amber-500"></i> 2. Monitoring Disposisi Pimpinan
                            <span v-if="countPendingDisposisi > 0" class="badge bg-amber-500 text-white rounded-pill ms-1.5 px-2 py-0.5 text-[10px] font-bold">{{ countPendingDisposisi }}</span>
                        </button>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <button @click="fetchSuratMasuk" class="btn btn-light btn-sm text-secondary rounded-xl px-3 py-1.5 border border-slate-200 shadow-2xs d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> <span class="fs-8 fw-semibold">Segarkan</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalCatat()">
                        <i class="bi bi-plus-circle-fill"></i> Catat Surat Masuk
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 1: BUKU AGENDA SURAT MASUK -->
    <div v-show="activeTab === 'agenda'" class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden mb-5">
        <!-- Toolbar Filter & Action -->
        <div class="p-3.5 border-b border-slate-100 bg-slate-50/50">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2.5">
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <!-- Search Input -->
                    <div class="position-relative" style="min-width: 240px; max-width: 320px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-7 pointer-events-none"></i>
                        <input type="text" v-model="filter.search" @input="debounceSearch()"
                               class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-medium bg-white shadow-2xs"
                               placeholder="Cari nomor surat, pengirim, perihal...">
                        <button v-if="filter.search" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 p-0" @click="filter.search = ''; fetchSuratMasuk()">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>

                    <!-- Status Disposisi Filter -->
                    <select v-model="filter.status_disposisi" @change="fetchSuratMasuk()" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer" style="width: auto;">
                        <option value="">— Semua Status Disposisi —</option>
                        <option value="Menunggu Disposisi">Menunggu Disposisi</option>
                        <option value="Didisposisikan">Didisposisikan</option>
                    </select>

                    <button v-if="filter.search || filter.status_disposisi" type="button" class="btn btn-sm btn-outline-secondary rounded-xl text-xs font-bold px-2.5 py-1.5 shadow-2xs bg-white" @click="resetFilter()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-2xs bg-white text-slate-700" @click="fetchSuratMasuk()">
                        <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> Segarkan
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalCatat()">
                        <i class="bi bi-plus-circle-fill"></i> Catat Surat Masuk
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
                <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5 shadow-2xs">
                    <i class="bi bi-inbox"></i>
                </div>
                <div class="font-bold text-slate-700 text-base mb-1">Belum Ada Agenda Surat Masuk</div>
                <p class="text-slate-400 text-xs mb-3 mx-auto" style="max-width: 440px;">
                    Tidak ditemukan rekaman surat masuk yang sesuai dengan filter. Klik tombol di bawah untuk mencatat surat masuk baru.
                </p>
                <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-2xs" @click="openModalCatat()">
                    <i class="bi bi-plus-lg me-1"></i> Catat Surat Masuk Sekarang
                </button>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle text-xs mb-0">
                    <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold">
                        <tr>
                            <th class="ps-4 py-3" style="width: 100px;">No. Agenda</th>
                            <th class="py-3">Nomor &amp; Perihal Surat</th>
                            <th class="py-3">Instansi / Pengirim</th>
                            <th class="py-3 text-center" style="width: 120px;">Tgl Terima</th>
                            <th class="py-3 text-center" style="width: 140px;">Status Disposisi</th>
                            <th class="py-3 text-end pe-4" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="sm in listSuratMasuk" :key="sm.id" class="hover:bg-slate-50/70 transition">
                            <td class="ps-4 py-3 font-mono font-bold text-blue-700">
                                {{ sm.no_agenda || '-' }}
                            </td>
                            <td class="py-3">
                                <div class="font-bold text-slate-900 fs-7 mb-0.5">{{ sm.no_surat }}</div>
                                <div class="text-slate-500 text-xs line-clamp-1 mb-1" :title="sm.perihal">{{ sm.perihal }}</div>
                                <div class="d-flex align-items-center gap-2 text-[11px] text-slate-400">
                                    <span><i class="bi bi-calendar-event me-1"></i>Tgl Surat: {{ sm.tgl_surat || '-' }}</span>
                                    <span v-if="sm.sifat_surat" class="badge bg-slate-100 text-slate-600 rounded-md px-1.5 py-0.5 text-[10px]">{{ sm.sifat_surat }}</span>
                                </div>
                            </td>
                            <td class="py-3 font-semibold text-slate-700">
                                <div class="d-flex align-items-center gap-1.5">
                                    <i class="bi bi-building text-slate-400"></i>
                                    <span>{{ sm.pengirim }}</span>
                                </div>
                            </td>
                            <td class="py-3 text-center text-slate-600 font-medium">
                                {{ sm.tgl_terima || '-' }}
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="sm.status_disposisi === 'Didisposisikan'" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-check2-circle me-1"></i> Didisposisikan
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-hourglass-split me-1"></i> Menunggu
                                </span>
                            </td>
                            <td class="py-3 text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-xs btn-outline-warning text-amber-700 hover:bg-amber-50 border-amber-300 rounded-lg px-2 py-1 font-semibold" @click="openModalEdit(sm)" title="Edit Agenda Surat Masuk">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-primary rounded-lg px-2 py-1 font-semibold" @click="openModalDisposisi(sm)" title="Lembar Disposisi">
                                        <i class="bi bi-pencil-square me-1"></i> Disposisi
                                    </button>
                                    <button v-if="sm.file_lampiran" type="button" class="btn btn-xs btn-light border rounded-lg px-2 py-1 text-slate-600" @click="viewLampiran(sm.file_lampiran)" title="Buka Lampiran">
                                        <i class="bi bi-file-earmark-pdf text-rose-600"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-light border text-rose-600 hover:bg-rose-50 rounded-lg p-1" @click="deleteSuratMasuk(sm)" title="Hapus">
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
    <div v-show="activeTab === 'disposisi'" class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden mb-5">
        <div class="p-3.5 border-b border-slate-100 bg-slate-50/50 d-flex align-items-center justify-content-between">
            <div>
                <h6 class="font-bold text-slate-800 fs-6 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-hourglass-split text-amber-500"></i>
                    Monitoring &amp; Lembar Disposisi Pimpinan
                </h6>
                <small class="text-slate-400 text-xs">Daftar naskah masuk yang memerlukan arahan dan instruksi tindak lanjut pimpinan</small>
            </div>
            <span class="badge px-3 py-1.5 rounded-pill text-xs font-bold" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                {{ countPendingDisposisi }} Menunggu Disposisi
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
                    <thead class="table-light text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200">
                        <tr>
                            <th class="ps-4 py-3" style="width: 25%;">Nomor &amp; Pengirim</th>
                            <th class="py-3" style="width: 30%;">Pokok Perihal Surat</th>
                            <th class="py-3 text-center" style="width: 20%;">Penerima Disposisi</th>
                            <th class="py-3 text-center" style="width: 12%;">Batas Waktu</th>
                            <th class="py-3 text-end pe-4" style="width: 13%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="sm in listSuratMasuk" :key="'disp-'+sm.id" class="hover-bg-slate">
                            <td class="ps-4 py-3">
                                <div class="font-bold text-slate-900">{{ sm.no_surat }}</div>
                                <div class="text-slate-500 font-medium">{{ sm.pengirim }}</div>
                            </td>
                            <td class="py-3">
                                <div class="text-slate-800 line-clamp-2 font-medium">{{ sm.perihal }}</div>
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="sm.nama_penerima_disposisi" class="badge bg-blue-50 text-blue-700 border border-blue-200 font-bold px-2.5 py-1 rounded-lg">
                                    <i class="bi bi-person-fill me-1"></i> {{ sm.nama_penerima_disposisi }}
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 font-semibold px-2 py-0.5 rounded-lg">Menunggu Arahan</span>
                            </td>
                            <td class="py-3 text-center font-medium text-slate-600">
                                {{ sm.batas_waktu || '—' }}
                            </td>
                            <td class="py-3 text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-xs btn-outline-warning text-amber-700 hover:bg-amber-50 border-amber-300 rounded-lg px-2 py-1 font-semibold" @click="openModalEdit(sm)" title="Edit Surat">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-xs btn-primary rounded-lg px-2.5 py-1 font-bold shadow-2xs" @click="openModalDisposisi(sm)">
                                        <i class="bi bi-pencil-square me-1"></i> Isi Lembar
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
         MODAL 1: CATAT / EDIT SURAT MASUK
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalCatatSuratMasuk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-xl rounded-3xl overflow-hidden">
                <div class="modal-header p-4 border-0" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important; color: #ffffff !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-500/20 text-blue-400 d-flex align-items-center justify-content-center fs-5 shadow-xs border border-blue-400/20">
                            <i :class="isEditSurat ? 'bi bi-pencil-square' : 'bi bi-inbox-fill'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0 text-white">
                                {{ isEditSurat ? 'Edit Agenda Surat Masuk' : 'Catat Agenda Surat Masuk' }}
                            </h5>
                            <small class="text-slate-400 text-xs">
                                {{ isEditSurat ? 'Perbarui nomor surat, instansi pengirim, perihal, atau lampiran' : 'Form registrasi berkas naskah dinas masuk sekolah' }}
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitSuratMasuk">
                    <div class="modal-body p-4 bg-slate-50/50">
                        <div class="row g-3 text-xs">
                            <div class="col-12 col-md-4">
                                <label class="form-label font-bold text-slate-700">No. Agenda Buku</label>
                                <input type="text" v-model="formSuratMasuk.no_agenda" class="form-control form-control-sm rounded-xl font-mono" placeholder="Otomatis / manual">
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label font-bold text-slate-700">Nomor Surat Dinas <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratMasuk.no_surat" class="form-control form-control-sm rounded-xl font-semibold" placeholder="Contoh: 421/045/Disdik/2026" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Asal Instansi / Pengirim <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratMasuk.pengirim" class="form-control form-control-sm rounded-xl" placeholder="Contoh: Dinas Pendidikan Provinsi / Puskesmas" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label font-bold text-slate-700">Tanggal Surat <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="formSuratMasuk.tgl_surat" class="form-control form-control-sm rounded-xl" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label font-bold text-slate-700">Tanggal Terima <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="formSuratMasuk.tgl_terima" class="form-control form-control-sm rounded-xl" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Perihal / Pokok Isi Surat <span class="text-rose-500">*</span></label>
                                <textarea v-model="formSuratMasuk.perihal" rows="2" class="form-control form-control-sm rounded-xl" placeholder="Ketik ringkasan pokok perihal surat..." required></textarea>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label font-bold text-slate-700">Sifat Surat</label>
                                <select v-model="formSuratMasuk.sifat_surat" class="form-select form-select-sm rounded-xl">
                                    <option value="Biasa">Biasa</option>
                                    <option value="Penting">Penting</option>
                                    <option value="Segera">Segera</option>
                                    <option value="Kilat">Sangat Segera / Kilat</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label font-bold text-slate-700">Tingkat Keamanan</label>
                                <select v-model="formSuratMasuk.tingkat_keamanan" class="form-select form-select-sm rounded-xl">
                                    <option value="Biasa">Biasa</option>
                                    <option value="Terbatas">Terbatas</option>
                                    <option value="Rahasia">Rahasia</option>
                                    <option value="Sangat Rahasia">Sangat Rahasia</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label font-bold text-slate-700">File Lampiran (PDF / Scan)</label>
                                <input type="text" v-model="formSuratMasuk.file_lampiran" class="form-control form-control-sm rounded-xl" placeholder="Nama file / URL dokumen">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3 font-semibold text-xs" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
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
                                    <div class="text-slate-500 mt-1 text-[11px]">
                                        <i class="bi bi-calendar3 me-1"></i> Diterima: {{ selectedSurat.tgl_terima || '-' }}
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
    const { ref, onMounted } = Vue;

    const persuratanSuratMasukAppConfig = {
        setup() {
            const activeTab = ref('agenda');
            const loading = ref(false);
            const saving = ref(false);
            const isEditSurat = ref(false);
            const listSuratMasuk = ref([]);
            const selectedSurat = ref(null);

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
                    const payload = { ...formSuratMasuk.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/surat-masuk/save', payload);
                    if (res.data && res.data.success) {
                        if (modalCatatInstance) modalCatatInstance.hide();
                        Swal.fire({
                            icon: 'success',
                            title: isEditSurat.value ? 'Perubahan Tersimpan!' : 'Berhasil!',
                            text: isEditSurat.value ? 'Data surat masuk berhasil diperbarui.' : 'Surat masuk berhasil dicatat ke dalam buku agenda.',
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

            const viewLampiran = (fileUrl) => {
                if (!fileUrl) return;
                window.open(fileUrl, '_blank');
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

            const countPendingDisposisi = Vue.computed(() => {
                return listSuratMasuk.value.filter(s => s.status_disposisi === 'Menunggu Disposisi' || !s.nama_penerima_disposisi).length;
            });

            const listDisposisiOnly = Vue.computed(() => {
                return listSuratMasuk.value;
            });

            window.persuratanSuratMasukOpenCatat = openModalCatat;

            onMounted(() => {
                fetchSuratMasuk();
            });

            return {
                activeTab,
                loading,
                saving,
                isEditSurat,
                listSuratMasuk,
                listDisposisiOnly,
                countPendingDisposisi,
                selectedSurat,
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
                viewLampiran,
                deleteSuratMasuk
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
