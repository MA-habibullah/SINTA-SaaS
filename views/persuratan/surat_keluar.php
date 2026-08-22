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
        <button type="button" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-bold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all d-inline-flex align-items-center gap-1.5" onclick="window.persuratanSuratKeluarOpenBuat && window.persuratanSuratKeluarOpenBuat()">
            <i class="bi bi-plus-circle-fill"></i> Buat Surat Keluar
        </button>
    ';
    include __DIR__ . '/_hero_header.php'; 
    ?>

    <!-- Navtab Lokal Khusus Internal Halaman Surat Keluar -->
    <div class="card border-0 shadow-xs rounded-2xl mb-4 bg-white" style="border: 1px solid #e2e8f0;">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <ul class="nav nav-pills gap-1.5 border-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeTab === 'register' }" 
                                @click="activeTab = 'register'" type="button">
                            <i class="bi bi-send-check-fill me-1.5 text-success"></i> 1. Buku Register Surat Keluar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeTab === 'tte' }" 
                                @click="activeTab = 'tte'" type="button">
                            <i class="bi bi-qr-code-scan me-1.5 text-primary"></i> 2. Monitoring TTE &amp; Validasi Naskah
                        </button>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <button @click="fetchSuratKeluar" class="btn btn-light btn-sm text-secondary rounded-xl px-3 py-1.5 border border-slate-200 shadow-2xs d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> <span class="fs-8 fw-semibold">Segarkan</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalBuat()">
                        <i class="bi bi-plus-circle-fill"></i> Buat Surat Keluar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 1: BUKU REGISTER SURAT KELUAR -->
    <div v-show="activeTab === 'register'" class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden mb-5">
        <!-- Toolbar Filter & Action -->
        <div class="p-3.5 border-b border-slate-100 bg-slate-50/50">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2.5">
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <!-- Search Input -->
                    <div class="position-relative" style="min-width: 240px; max-width: 320px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-7 pointer-events-none"></i>
                        <input type="text" v-model="filter.search" @input="debounceSearch()"
                               class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-medium bg-white shadow-2xs"
                               placeholder="Cari nomor surat, tujuan, perihal...">
                        <button v-if="filter.search" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 p-0" @click="filter.search = ''; fetchSuratKeluar()">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>

                    <!-- Status Filter -->
                    <select v-model="filter.status_surat" @change="fetchSuratKeluar()" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer" style="width: auto;">
                        <option value="">— Semua Status Naskah —</option>
                        <option value="Diterbitkan">Diterbitkan (Resmi)</option>
                        <option value="Draft">Draft Naskah</option>
                    </select>

                    <button v-if="filter.search || filter.status_surat" type="button" class="btn btn-sm btn-outline-secondary rounded-xl text-xs font-bold px-2.5 py-1.5 shadow-2xs bg-white" @click="resetFilter()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-2xs bg-white text-slate-700" @click="fetchSuratKeluar()">
                        <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> Segarkan
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalBuat()">
                        <i class="bi bi-plus-circle-fill"></i> Buat Surat Keluar
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
                <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5 shadow-2xs">
                    <i class="bi bi-send"></i>
                </div>
                <div class="font-bold text-slate-700 text-base mb-1">Belum Ada Register Surat Keluar</div>
                <p class="text-slate-400 text-xs mb-3 mx-auto" style="max-width: 440px;">
                    Belum ada surat keluar yang diterbitkan. Klik tombol di bawah untuk membuat dan meng-generate nomor surat keluar baru.
                </p>
                <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-2xs" @click="openModalBuat()">
                    <i class="bi bi-plus-lg me-1"></i> Buat Surat Keluar Baru
                </button>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle text-xs mb-0">
                    <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold">
                        <tr>
                            <th class="ps-4 py-3" style="width: 80px;">No</th>
                            <th class="py-3">Nomor Surat Resmi</th>
                            <th class="py-3">Tujuan &amp; Perihal Surat</th>
                            <th class="py-3 text-center" style="width: 120px;">Tgl Terbit</th>
                            <th class="py-3" style="width: 170px;">Penandatangan</th>
                            <th class="py-3 text-center" style="width: 110px;">Status</th>
                            <th class="py-3 text-end pe-4" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(sk, idx) in listSuratKeluar" :key="sk.id" class="hover:bg-slate-50/70 transition">
                            <td class="ps-4 py-3 font-semibold text-slate-400">
                                {{ idx + 1 }}
                            </td>
                            <td class="py-3">
                                <div class="font-mono font-bold text-blue-700 fs-7 mb-0.5">{{ sk.nomor_surat }}</div>
                                <div class="d-flex align-items-center gap-1.5 text-[11px] text-slate-400">
                                    <span v-if="sk.no_agenda" class="badge bg-slate-100 text-slate-600 rounded">Agenda: {{ sk.no_agenda }}</span>
                                    <span v-if="sk.qr_token" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded font-mono text-[9px]"><i class="bi bi-qr-code me-0.5"></i>TTE Valid</span>
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="font-bold text-slate-900 fs-7 mb-0.5">
                                    <i class="bi bi-geo-alt-fill text-rose-500 me-1"></i>{{ sk.tujuan }}
                                </div>
                                <div class="text-slate-600 text-xs line-clamp-1" :title="sk.perihal">{{ sk.perihal }}</div>
                            </td>
                            <td class="py-3 text-center text-slate-600 font-medium">
                                {{ sk.tgl_surat || '-' }}
                            </td>
                            <td class="py-3">
                                <div class="font-semibold text-slate-800 text-truncate" style="max-width: 160px;">{{ sk.nama_penandatangan || 'Kepala Sekolah' }}</div>
                                <small class="text-slate-400 text-[10px] d-block">{{ sk.jabatan_penandatangan || 'Kepala Sekolah' }}</small>
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="sk.status_surat === 'Diterbitkan'" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-check2-all me-1"></i> Terbit
                                </span>
                                <span v-else class="badge bg-slate-100 text-slate-600 border border-slate-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    Draft
                                </span>
                            </td>
                            <td class="py-3 text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-xs btn-outline-warning text-amber-700 hover:bg-amber-50 border-amber-300 rounded-lg px-2 py-1 font-semibold" @click="openModalEdit(sk)" title="Edit Surat Keluar">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-xs btn-primary rounded-lg px-2.5 py-1 font-bold shadow-2xs d-inline-flex align-items-center gap-1" @click="openPratinjauCetak(sk)" title="Pratinjau & Cetak Naskah Dinas">
                                        <i class="bi bi-printer-fill"></i> Cetak
                                    </button>
                                    <button type="button" class="btn btn-xs btn-light border text-rose-600 hover:bg-rose-50 rounded-lg p-1" @click="deleteSuratKeluar(sk)" title="Hapus">
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
    <div v-show="activeTab === 'tte'" class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden mb-5">
        <div class="p-3.5 border-b border-slate-100 bg-slate-50/50 d-flex align-items-center justify-content-between">
            <div>
                <h6 class="font-bold text-slate-800 fs-6 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-qr-code-scan text-primary"></i>
                    Monitoring Penandatanganan Digital &amp; TTE QR Code
                </h6>
                <small class="text-slate-400 text-xs">Daftar naskah dinas resmi yang telah dibubuhi QR Code Verifikasi Keaslian</small>
            </div>
            <span class="badge px-3 py-1.5 rounded-pill text-xs font-bold" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                <i class="bi bi-shield-check text-blue-600 me-1"></i> Terenkripsi &amp; Terverifikasi
            </span>
        </div>

        <div class="p-0">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat data TTE...
            </div>
            <div v-else-if="listSuratKeluar.length === 0" class="text-center py-5 px-3">
                <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5 shadow-2xs">
                    <i class="bi bi-qr-code"></i>
                </div>
                <div class="font-bold text-slate-700 text-base mb-1">Belum Ada Naskah TTE</div>
                <p class="text-slate-400 text-xs mb-0 mx-auto" style="max-width: 440px;">
                    Terbitkan surat keluar resmi untuk menghasilkan QR Code TTE dan validasi digital.
                </p>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle text-xs mb-0">
                    <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold">
                        <tr>
                            <th class="ps-4 py-3" style="width: 80px;">No</th>
                            <th class="py-3">Nomor Surat</th>
                            <th class="py-3">Perihal Naskah</th>
                            <th class="py-3">Penandatangan</th>
                            <th class="py-3 text-center" style="width: 140px;">Status TTE</th>
                            <th class="py-3 text-end pe-4" style="width: 170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(sk, idx) in listSuratKeluar" :key="'tte-' + sk.id" class="hover:bg-slate-50/70 transition">
                            <td class="ps-4 py-3 text-slate-400 font-mono">{{ idx + 1 }}</td>
                            <td class="py-3 font-bold text-slate-900 font-mono">{{ sk.nomor_surat }}</td>
                            <td class="py-3 text-slate-600 font-medium">{{ sk.perihal }}</td>
                            <td class="py-3">
                                <div class="font-bold text-slate-800">{{ sk.nama_penandatangan || 'Kepala Sekolah' }}</div>
                                <small class="text-slate-400 text-[11px]">{{ sk.jabatan_penandatangan || 'Kepala Sekolah' }}</small>
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge rounded-pill px-2.5 py-1 text-xs font-bold" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                    <i class="bi bi-patch-check-fill text-emerald-600 me-1"></i> TTE Sah &amp; Valid
                                </span>
                            </td>
                            <td class="py-3 text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-xs btn-outline-warning text-amber-700 hover:bg-amber-50 border-amber-300 rounded-lg px-2 py-1 font-semibold" @click="openModalEdit(sk)" title="Edit Surat Keluar">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-xs btn-primary rounded-lg px-2.5 py-1 font-bold shadow-2xs d-inline-flex align-items-center gap-1" @click="openPratinjauCetak(sk)">
                                        <i class="bi bi-printer-fill"></i> Cetak
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
         MODAL 1: BUAT / EDIT SURAT KELUAR & GENERATE NOMOR OTOMATIS
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalBuatSuratKeluar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden">
                <div class="modal-header p-4 border-0" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important; color: #ffffff !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 d-flex align-items-center justify-content-center fs-5 shadow-xs border border-emerald-400/20">
                            <i :class="isEditSurat ? 'bi bi-pencil-square' : 'bi bi-send-plus-fill'"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0 text-white">
                                {{ isEditSurat ? 'Edit Register Surat Keluar' : 'Buat & Register Surat Keluar' }}
                            </h5>
                            <small class="text-slate-400 text-xs">
                                {{ isEditSurat ? 'Perbarui nomor, tujuan, perihal, penandatangan, atau isi naskah dinas' : 'Penerbitan surat dinas resmi dengan format penomoran baku sekolah' }}
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
                                <label class="form-label font-bold text-slate-700">Pilih Template Naskah Dinas</label>
                                <select v-model="formSuratKeluar.id_template" @change="onTemplateChange()" class="form-select form-select-sm rounded-xl">
                                    <option value="">— Template Naskah Khusus / Bebas —</option>
                                    <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">{{ tpl.nama_template_surat }}</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Klasifikasi Arsip Naskah</label>
                                <select v-model="formSuratKeluar.id_kode_klasifikasi" class="form-select form-select-sm rounded-xl">
                                    <option value="">— Pilih Klasifikasi Baku —</option>
                                    <option v-for="klas in klasifikasiList" :key="klas.id" :value="klas.id">
                                        {{ klas.kode_klasifikasi }} - {{ klas.nama_klasifikasi }}
                                    </option>
                                </select>
                            </div>

                            <!-- Nomor Surat Generator Bar -->
                            <div class="col-12">
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

                            <div class="col-12 col-md-8">
                                <label class="form-label font-bold text-slate-700">Tujuan Surat / Penerima <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratKeluar.tujuan" class="form-control form-control-sm rounded-xl" placeholder="Contoh: Orang Tua / Wali Siswa, Kepala Dinas Pendidikan..." required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label font-bold text-slate-700">Tanggal Terbit Surat <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="formSuratKeluar.tgl_surat" class="form-control form-control-sm rounded-xl" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Perihal / Hal Surat <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratKeluar.perihal" class="form-control form-control-sm rounded-xl" placeholder="Contoh: Undangan Panggilan Orang Tua Siswa / Surat Keterangan Siswa Aktif" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Nama Penandatangan <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formSuratKeluar.nama_penandatangan" class="form-control form-control-sm rounded-xl font-semibold" placeholder="Nama Lengkap Kepala Sekolah" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Jabatan Penandatangan</label>
                                <input type="text" v-model="formSuratKeluar.jabatan_penandatangan" class="form-control form-control-sm rounded-xl" placeholder="Kepala Sekolah">
                            </div>

                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Ringkasan Isi Naskah Dinas</label>
                                <textarea v-model="formSuratKeluar.ringkasan_isi" rows="3" class="form-control form-control-sm rounded-xl" placeholder="Ketik ringkasan pokok surat atau isi format pengumuman resmi..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3.5 font-semibold text-xs" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span>{{ isEditSurat ? 'Simpan Perubahan Surat' : 'Terbitkan & Simpan Register' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL 2: PRATINJAU CETAK NASKAH DINAS BERKOP SURAT & QR TTE
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalPratinjauCetak" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-2xl rounded-3xl overflow-hidden" v-if="cetakDetail">
                <div class="modal-header bg-slate-900 text-white p-4 border-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2.5">
                        <i class="bi bi-printer-fill text-blue-400 fs-5"></i>
                        <h5 class="modal-title font-bold fs-6 mb-0">Pratinjau Lembar Naskah Dinas Resmi</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm rounded-xl px-3 py-1.5 font-bold text-xs shadow-2xs" @click="printNaskah()">
                            <i class="bi bi-printer me-1"></i> Cetak / Print Dokumen
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4 p-md-5 bg-slate-100 overflow-y-auto" style="max-height: 80vh;">
                    <!-- A4 Letter Paper Simulation -->
                    <div id="printArea" class="bg-white shadow-md mx-auto p-4 p-md-5 rounded-2xl" style="max-width: 800px; min-height: 1000px; color: #000000; font-family: 'Times New Roman', Times, serif;">
                        
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
                                    <tr><td class="p-0 pe-2 fw-bold">Nomor</td><td class="p-0 pe-2">:</td><td class="p-0 font-mono fw-bold">{{ cetakDetail.surat.nomor_surat }}</td></tr>
                                    <tr><td class="p-0 pe-2 fw-bold">Lampiran</td><td class="p-0 pe-2">:</td><td class="p-0">-</td></tr>
                                    <tr><td class="p-0 pe-2 fw-bold">Perihal</td><td class="p-0 pe-2">:</td><td class="p-0 fw-bold">{{ cetakDetail.surat.perihal }}</td></tr>
                                </table>
                            </div>
                            <div class="text-end">
                                <span>{{ cetakDetail.kop?.kota_kabupaten || 'Tempat' }}, {{ cetakDetail.surat.tgl_surat }}</span>
                            </div>
                        </div>

                        <!-- Tujuan Surat -->
                        <div class="mb-4 text-xs font-sans">
                            <span class="d-block">Kepada Yth.</span>
                            <strong class="d-block fs-7">{{ cetakDetail.surat.tujuan }}</strong>
                            <span>di Tempat</span>
                        </div>

                        <!-- Isi Naskah Surat -->
                        <div class="mb-5 text-sm leading-relaxed text-justify" style="font-size: 11pt; line-height: 1.8;">
                            <p>Dengan hormat,</p>
                            <p>{{ cetakDetail.surat.ringkasan_isi || 'Sehubungan dengan agenda administrasi dan layanan kedinasan sekolah, bersama surat ini kami sampaikan permohonan / pemberitahuan resmi untuk menjadi perhatian bersama.' }}</p>
                            <p>Demikian surat dinas ini disampaikan, atas perhatian dan kerja sama yang baik kami ucapkan terima kasih.</p>
                        </div>

                        <!-- Tanda Tangan & QR Code TTE -->
                        <div class="d-flex justify-content-end text-end mt-5 pt-4 text-xs font-sans">
                            <div class="text-center" style="min-width: 220px;">
                                <span class="d-block mb-1">{{ cetakDetail.surat.jabatan_penandatangan || 'Kepala Sekolah' }}</span>
                                
                                <!-- QR Code Validation Token -->
                                <div class="my-2 p-2 d-inline-block bg-white border border-dark rounded-xl">
                                    <i class="bi bi-qr-code fs-1 d-block text-dark"></i>
                                    <small class="font-mono text-[9px] d-block fw-bold mt-1">{{ cetakDetail.surat.qr_token ? cetakDetail.surat.qr_token.substring(0, 16) + '...' : 'TTE-VERIFIED' }}</small>
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
    const { ref, onMounted } = Vue;

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
                    console.error('Gagal memuat opsi template & klasifikasi:', e);
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
                    ringkasan_isi: ''
                };
                generateNomorSurat();
                const el = document.getElementById('modalBuatSuratKeluar');
                if (el && typeof bootstrap !== 'undefined') {
                    modalBuatInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalBuatInstance.show();
                }
            };

            const openModalEdit = (sk) => {
                isEditSurat.value = true;
                formSuratKeluar.value = {
                    id: sk.id,
                    id_template: sk.id_template || '',
                    id_kode_klasifikasi: sk.id_kode_klasifikasi || '',
                    nomor_surat: sk.nomor_surat || '',
                    tujuan: sk.tujuan || '',
                    tgl_surat: sk.tgl_surat || new Date().toISOString().split('T')[0],
                    perihal: sk.perihal || '',
                    nama_penandatangan: sk.nama_penandatangan || 'Kepala Sekolah',
                    jabatan_penandatangan: sk.jabatan_penandatangan || 'Kepala Sekolah',
                    ringkasan_isi: sk.ringkasan_isi || ''
                };
                const el = document.getElementById('modalBuatSuratKeluar');
                if (el && typeof bootstrap !== 'undefined') {
                    modalBuatInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalBuatInstance.show();
                }
            };

            const onTemplateChange = () => {
                const tpl = templates.value.find(t => t.id === formSuratKeluar.value.id_template);
                if (tpl) {
                    if (tpl.perihal_default) formSuratKeluar.value.perihal = tpl.perihal_default;
                    if (tpl.konten_html) formSuratKeluar.value.ringkasan_isi = tpl.konten_html;
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
                deleteSuratKeluar
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
