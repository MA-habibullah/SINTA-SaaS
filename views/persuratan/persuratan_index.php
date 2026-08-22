<?php
/**
 * View: Modul Persuratan & Tata Usaha (E-Arsip & Tata Naskah Dinas Sekolah)
 * Arsitektur: Vue 3 SPA + Bootstrap 5 + Tailwind Design Tokens (Zero Data Leakage)
 */
$title = $title ?? 'Persuratan & Tata Usaha';
$userRole = $user_role ?? ($_SESSION['role_name'] ?? '');
$userNama = $user_nama ?? ($_SESSION['nama_lengkap'] ?? 'Petugas Tata Usaha');
$tenantId = $tenant_id ?? ($_SESSION['tenant_id'] ?? '');
$isSuperAdmin = $is_super_admin ?? false;
$tenants = $tenants ?? [];
$selectedTenantId = $selected_tenant_id ?? $tenantId;
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
?>

<style>
[v-cloak] { display: none !important; }

/* ─── Persuratan Design Tokens ─── */
:root {
    --tu-primary: #0f766e;      /* Teal 700 — Karakteristik Tata Usaha & Legalitas */
    --tu-primary-hover: #115e59;
    --tu-primary-light: #ccfbf1;
    --tu-indigo: #4338ca;
    --tu-slate: #334155;
    --tu-border: #e2e8f0;
}

.tu-header-gradient {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #0284c7 100%);
    border-radius: 1rem;
    color: #ffffff;
    box-shadow: 0 10px 25px -5px rgba(15, 118, 110, 0.2);
}

.tu-nav-tabs {
    display: flex;
    gap: 0.35rem;
    border-bottom: 2px solid var(--tu-border);
    overflow-x: auto;
    padding-bottom: 2px;
}
.tu-nav-tabs::-webkit-scrollbar { height: 4px; }
.tu-nav-tabs::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.tu-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.15rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border: none;
    border-radius: 0.65rem 0.65rem 0 0;
    transition: all 0.2s ease-in-out;
    white-space: nowrap;
}
.tu-tab-btn:hover {
    color: var(--tu-primary);
    background: #f0fdfa;
}
.tu-tab-btn.active {
    color: var(--tu-primary);
    background: #ffffff;
    border-bottom: 3px solid var(--tu-primary);
    box-shadow: 0 -2px 6px rgba(15, 118, 110, 0.08);
}

.tu-card {
    background: #ffffff;
    border: 1px solid var(--tu-border);
    border-radius: 0.85rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    transition: box-shadow 0.2s ease;
}
.tu-card:hover {
    box-shadow: 0 6px 12px rgba(0,0,0,0.05);
}

.stat-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}

/* ─── Printable Document Simulation ─── */
.kop-surat-preview {
    border-bottom: 3px double #000;
    padding-bottom: 12px;
    margin-bottom: 20px;
}
@media print {
    body * { visibility: hidden !important; }
    .print-area, .print-area * { visibility: visible !important; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 20px; }
    .no-print { display: none !important; }
}
</style>

<div class="content-wrapper" id="persuratan-app" v-cloak>
    <!-- Top Header Banner -->
    <div class="p-4 mb-4 tu-header-gradient d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-white text-dark fw-bold px-2 py-1 rounded-pill fs-8">
                    <i class="bi bi-patch-check-fill text-teal me-1"></i>Tata Usaha & Persuratan
                </span>
                <span class="badge bg-emerald-700 text-white rounded-pill fs-8" v-if="pengajuanBkPendingCount > 0">
                    <i class="bi bi-bell-fill me-1"></i>{{ pengajuanBkPendingCount }} Notifikasi BK
                </span>
            </div>
            <h3 class="fw-bold mb-1">Sistem Manajemen Persuratan & E-Arsip Sekolah</h3>
            <p class="mb-0 text-white-50 fs-7">Registrasi surat masuk, penomoran naskah dinas resmi, disposisi pimpinan, dan penerbitan surat panggilan terpadu.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Filter Tenant Khusus Super Admin -->
            <div v-if="isSuperAdmin && tenants.length > 0" class="d-flex align-items-center gap-2 bg-white bg-opacity-20 p-1.5 px-2.5 rounded-3 border border-white border-opacity-25 shadow-sm" style="backdrop-filter: blur(8px);">
                <i class="bi bi-building text-white fs-6"></i>
                <select v-model="filterTenantId" @change="onTenantChange()" class="form-select form-select-sm border-0 text-xs font-semibold bg-white text-dark rounded-2 shadow-sm cursor-pointer" style="min-width: 220px;">
                    <option value="">Semua Sekolah / Tenant</option>
                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                </select>
            </div>
            <button class="btn btn-light btn-sm text-teal fw-semibold rounded-3 shadow-sm" @click="openModalSuratMasuk()">
                <i class="bi bi-plus-circle-fill me-1"></i>Input Surat Masuk
            </button>
            <button class="btn btn-warning btn-sm text-dark fw-bold rounded-3 shadow-sm" @click="openModalSuratKeluar()">
                <i class="bi bi-send-plus-fill me-1"></i>Buat Surat Keluar
            </button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="tu-nav-tabs mb-4">
        <button class="tu-tab-btn" :class="{ active: activeTab === 'dashboard' }" @click="switchTab('dashboard')">
            <i class="bi bi-speedometer2"></i> Dashboard E-Arsip
        </button>
        <button class="tu-tab-btn" :class="{ active: activeTab === 'surat_masuk' }" @click="switchTab('surat_masuk')">
            <i class="bi bi-inbox-fill"></i> Surat Masuk & Disposisi
        </button>
        <button class="tu-tab-btn" :class="{ active: activeTab === 'surat_keluar' }" @click="switchTab('surat_keluar')">
            <i class="bi bi-send-fill"></i> Surat Keluar & Register
        </button>
        <button class="tu-tab-btn position-relative" :class="{ active: activeTab === 'pengajuan_bk' }" @click="switchTab('pengajuan_bk')">
            <i class="bi bi-bell-fill text-warning"></i> Pengajuan & Notifikasi BK
            <span class="badge bg-danger rounded-pill fs-9 ms-1" v-if="pengajuanBkPendingCount > 0">{{ pengajuanBkPendingCount }}</span>
        </button>
        <button class="tu-tab-btn" :class="{ active: activeTab === 'template' }" @click="switchTab('template')">
            <i class="bi bi-file-earmark-richtext"></i> Template Naskah Dinas
        </button>
        <button class="tu-tab-btn" :class="{ active: activeTab === 'master' }" @click="switchTab('master')">
            <i class="bi bi-gear-fill"></i> Klasifikasi & Kop Surat
        </button>
        <button class="tu-tab-btn" :class="{ active: activeTab === 'verifikasi' }" @click="switchTab('verifikasi')">
            <i class="bi bi-qr-code-scan"></i> Verifikasi TTE QR
        </button>
    </div>

    <!-- Loading Spinner State -->
    <div v-if="loadingGlobal" class="text-center py-5">
        <div class="spinner-border text-teal" role="status"></div>
        <p class="text-muted mt-2 fs-7">Memuat data persuratan secara asinkron...</p>
    </div>

    <div v-else>
        <!-- ================================================================= -->
        <!-- TAB 1: DASHBOARD E-ARSIP -->
        <!-- ================================================================= -->
        <div v-show="activeTab === 'dashboard'" class="animate-fade-in">
            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="tu-card p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                            <i class="bi bi-inbox-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted fs-8 fw-medium">Total Surat Masuk</span>
                            <h4 class="fw-bold text-dark mb-0">{{ stats.total_surat_masuk || 0 }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="tu-card p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                            <i class="bi bi-send-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted fs-8 fw-medium">Total Surat Keluar</span>
                            <h4 class="fw-bold text-dark mb-0">{{ stats.total_surat_keluar || 0 }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="tu-card p-3 d-flex align-items-center gap-3">
                        <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <span class="text-muted fs-8 fw-medium">Disposisi Menunggu</span>
                            <h4 class="fw-bold text-dark mb-0">{{ stats.disposisi_pending || 0 }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="tu-card p-3 d-flex align-items-center gap-3 border-start border-warning border-3">
                        <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted fs-8 fw-medium">Pengajuan Panggilan BK</span>
                            <h4 class="fw-bold text-danger mb-0">{{ stats.pengajuan_bk_pending || 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Volume Chart & Quick Overview -->
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="tu-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-fill text-teal me-2"></i>Rekapitulasi Volume Arsip Surat (6 Bulan Terakhir)</h6>
                            <span class="badge bg-light text-muted border fs-8">Otomatis Terkini</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle fs-7 mb-0">
                                <thead>
                                    <tr class="text-muted border-bottom">
                                        <th>Periode Bulan</th>
                                        <th>Surat Masuk</th>
                                        <th>Surat Keluar</th>
                                        <th>Visualisasi Rasio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in stats.chart_data" :key="c.bulan">
                                        <td class="fw-bold text-dark">{{ c.bulan }}</td>
                                        <td><span class="badge bg-info bg-opacity-10 text-info fw-semibold">{{ c.surat_masuk }} Surat</span></td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success fw-semibold">{{ c.surat_keluar }} Surat</span></td>
                                        <td>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-info" :style="{ width: (c.surat_masuk * 10) + '%' }"></div>
                                                <div class="progress-bar bg-success" :style="{ width: (c.surat_keluar * 10) + '%' }"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="tu-card p-4 h-100">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Aksi Cepat Tata Usaha</h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-teal text-start fs-7 p-2.5 rounded-3 fw-semibold" @click="activeTab = 'pengajuan_bk'">
                                <i class="bi bi-bell-fill text-danger me-2"></i>Lihat Antrean Panggilan BK ({{ stats.pengajuan_bk_pending || 0 }})
                            </button>
                            <button class="btn btn-outline-secondary text-start fs-7 p-2.5 rounded-3 fw-semibold" @click="activeTab = 'surat_masuk'">
                                <i class="bi bi-inbox me-2"></i>Agenda Surat Masuk Baru
                            </button>
                            <button class="btn btn-outline-secondary text-start fs-7 p-2.5 rounded-3 fw-semibold" @click="activeTab = 'surat_keluar'">
                                <i class="bi bi-hash me-2"></i>Ambil Nomor Surat Keluar
                            </button>
                            <button class="btn btn-outline-secondary text-start fs-7 p-2.5 rounded-3 fw-semibold" @click="activeTab = 'master'">
                                <i class="bi bi-card-heading me-2"></i>Atur Kop Surat Sekolah
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 2: SURAT MASUK & DISPOSISI -->
        <!-- ================================================================= -->
        <div v-show="activeTab === 'surat_masuk'" class="animate-fade-in">
            <div class="tu-card p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Buku Agenda Surat Masuk</h5>
                        <p class="text-muted fs-8 mb-0">Arsip surat masuk dari dinas, instansi, atau orang tua beserta tindak lanjut disposisi pimpinan.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-teal btn-sm fw-semibold rounded-3" @click="openModalSuratMasuk()">
                            <i class="bi bi-plus-lg me-1"></i>Catat Surat Masuk
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 fs-7" v-model="filterSuratMasuk.search" @input="fetchSuratMasuk" placeholder="Cari nomor surat, pengirim, perihal...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm fs-7 rounded-3" v-model="filterSuratMasuk.status_disposisi" @change="fetchSuratMasuk">
                            <option value="">-- Semua Status Disposisi --</option>
                            <option value="Menunggu Disposisi">Menunggu Disposisi</option>
                            <option value="Didisposisikan">Didisposisikan</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-outline-secondary btn-sm w-100 rounded-3 fs-7" @click="fetchSuratMasuk">
                            <i class="bi bi-arrow-clockwise me-1"></i>Segarkan Data
                        </button>
                    </div>
                </div>

                <!-- Table Surat Masuk -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle fs-8">
                        <thead class="table-light">
                            <tr>
                                <th>No. Agenda</th>
                                <th>Nomor & Tgl Surat</th>
                                <th>Pengirim & Perihal</th>
                                <th>Tgl Terima</th>
                                <th>Status Disposisi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="listSuratMasuk.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted opacity-50"></i>
                                    Belum ada catatan surat masuk yang terdaftar.
                                </td>
                            </tr>
                            <tr v-for="sm in listSuratMasuk" :key="sm.id">
                                <td class="fw-bold text-teal text-nowrap">{{ sm.no_agenda || '-' }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ sm.no_surat }}</div>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i>{{ sm.tgl_surat || '-' }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ sm.pengirim }}</div>
                                    <div class="text-muted fs-8 text-truncate" style="max-width: 280px;">{{ sm.perihal }}</div>
                                </td>
                                <td class="text-nowrap text-muted">{{ sm.tgl_terima || '-' }}</td>
                                <td>
                                    <span class="badge" :class="sm.status_disposisi === 'Didisposisikan' ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning'">
                                        {{ sm.status_disposisi }}
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <button class="btn btn-xs btn-outline-teal rounded-pill px-2.5 py-1 me-1" @click="openModalDisposisi(sm)">
                                        <i class="bi bi-signpost-2-fill me-1"></i>Disposisi ({{ sm.total_disposisi }})
                                    </button>
                                    <button class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1" @click="hapusSuratMasuk(sm.id)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 3: SURAT KELUAR & REGISTER -->
        <!-- ================================================================= -->
        <div v-show="activeTab === 'surat_keluar'" class="animate-fade-in">
            <div class="tu-card p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Buku Register Surat Keluar</h5>
                        <p class="text-muted fs-8 mb-0">Penomoran naskah dinas resmi, auto-numbering, dan pencetakan dokumen ber-KOP resmi sekolah.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-warning btn-sm text-dark fw-bold rounded-3" @click="openModalSuratKeluar()">
                            <i class="bi bi-send-plus-fill me-1"></i>Buat Surat Keluar
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 fs-7" v-model="filterSuratKeluar.search" @input="fetchSuratKeluar" placeholder="Cari nomor surat, tujuan, perihal...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm fs-7 rounded-3" v-model="filterSuratKeluar.id_kode_klasifikasi" @change="fetchSuratKeluar">
                            <option value="">-- Semua Kode Klasifikasi --</option>
                            <option v-for="k in listKlasifikasi" :key="k.id" :value="k.id">{{ k.kode_klasifikasi }} - {{ k.nama_klasifikasi }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-outline-secondary btn-sm w-100 rounded-3 fs-7" @click="fetchSuratKeluar">
                            <i class="bi bi-arrow-clockwise me-1"></i>Segarkan Data
                        </button>
                    </div>
                </div>

                <!-- Table Surat Keluar -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle fs-8">
                        <thead class="table-light">
                            <tr>
                                <th>No. Agenda</th>
                                <th>Nomor Surat Resmi</th>
                                <th>Tujuan & Perihal</th>
                                <th>Tgl Surat</th>
                                <th>Klasifikasi</th>
                                <th class="text-center">Aksi & Dokumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="listSuratKeluar.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-send fs-2 d-block mb-2 text-muted opacity-50"></i>
                                    Belum ada surat keluar yang terdaftar.
                                </td>
                            </tr>
                            <tr v-for="sk in listSuratKeluar" :key="sk.id">
                                <td class="fw-bold text-teal text-nowrap">{{ sk.no_agenda || '-' }}</td>
                                <td>
                                    <div class="fw-bold text-dark font-monospace">{{ sk.nomor_surat }}</div>
                                    <small class="text-muted">Penandatangan: {{ sk.nama_penandatangan || 'Kepala Sekolah' }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ sk.tujuan }}</div>
                                    <div class="text-muted fs-8 text-truncate" style="max-width: 280px;">{{ sk.perihal }}</div>
                                </td>
                                <td class="text-nowrap text-muted">{{ sk.tgl_surat }}</td>
                                <td>
                                    <span class="badge bg-light text-primary border rounded-pill">
                                        {{ sk.kode_klasifikasi || '421' }}
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <button class="btn btn-xs btn-primary rounded-pill px-2.5 py-1 me-1 text-white" @click="cetakSuratResmi(sk.id)">
                                        <i class="bi bi-printer-fill me-1"></i>Cetak Resmi
                                    </button>
                                    <button class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1" @click="hapusSuratKeluar(sk.id)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 4: PENGAJUAN & NOTIFIKASI BK -->
        <!-- ================================================================= -->
        <div v-show="activeTab === 'pengajuan_bk'" class="animate-fade-in">
            <div class="tu-card p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold text-dark mb-0">Antrean Notifikasi Pemanggilan Siswa dari BK</h5>
                            <span class="badge bg-danger rounded-pill fs-8">{{ pengajuanBkPendingCount }} Menunggu Terbit</span>
                        </div>
                        <p class="text-muted fs-8 mb-0 mt-1">Daftar siswa yang dilaporkan oleh Guru BK karena mencapai threshold poin pelanggaran. Tata Usaha berwenang mengesahkan dan menerbitkan nomor surat resmi.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle fs-8">
                        <thead class="table-light">
                            <tr>
                                <th>Tgl Notifikasi</th>
                                <th>Siswa & Kelas</th>
                                <th>Poin & Jenis Pemanggilan</th>
                                <th>Rencana Jadwal Menghadap</th>
                                <th>Guru BK Pelapor</th>
                                <th>Status Penerbitan</th>
                                <th class="text-center">Tindakan Tata Usaha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="listPengajuanBk.length === 0">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check fs-2 text-success d-block mb-2"></i>
                                    Tidak ada antrean notifikasi pemanggilan orang tua dari BK.
                                </td>
                            </tr>
                            <tr v-for="p in listPengajuanBk" :key="p.id">
                                <td class="text-muted text-nowrap">{{ p.created_at ? p.created_at.substring(0, 10) : '-' }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ p.nama_siswa }}</div>
                                    <small class="text-muted">NISN: {{ p.nisn || '-' }} | Kelas: {{ p.kelas || '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-danger text-white fw-bold me-1">{{ p.total_poin }} Poin</span>
                                    <span class="fw-semibold text-dark">{{ p.jenis_panggilan }}</span>
                                    <div class="text-muted fs-9 mt-1">{{ p.alasan_pemanggilan }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><i class="bi bi-calendar-event me-1"></i>{{ p.rencana_tanggal_menghadap || '-' }}</div>
                                    <small class="text-muted">Pukul: {{ p.rencana_jam_menghadap || '09:00' }} WIB | {{ p.ruangan || 'Ruang BK' }}</small>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ p.guru_bk_pengaju || 'Guru BK' }}</span></td>
                                <td>
                                    <span class="badge" :class="p.status_pengajuan === 'Surat Resmi Telah Terbit' ? 'bg-success text-white' : 'bg-warning text-dark'">
                                        {{ p.status_pengajuan }}
                                    </span>
                                    <div v-if="p.nomor_surat_terbit" class="font-monospace fs-9 text-teal mt-1 fw-bold">
                                        {{ p.nomor_surat_terbit }}
                                    </div>
                                </td>
                                <td class="text-center text-nowrap">
                                    <button v-if="p.status_pengajuan !== 'Surat Resmi Telah Terbit'" class="btn btn-xs btn-teal rounded-pill px-3 py-1.5 fw-semibold shadow-sm text-white" @click="openModalProsesTerbitBk(p)">
                                        <i class="bi bi-check2-circle me-1"></i>Terbitkan Surat Resmi
                                    </button>
                                    <button v-else class="btn btn-xs btn-primary rounded-pill px-3 py-1.5 fw-semibold text-white" @click="cetakSuratResmi(p.id_surat_keluar)">
                                        <i class="bi bi-printer me-1"></i>Cetak Surat
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 5: TEMPLATE NASKAH DINAS -->
        <!-- ================================================================= -->
        <div v-show="activeTab === 'template'" class="animate-fade-in">
            <div class="tu-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Katalog Template Naskah Dinas Baku</h5>
                        <p class="text-muted fs-8 mb-0">Format naskah dinas standar sekolah (Surat Panggilan Orang Tua, Surat Tugas, Surat Keterangan Aktif).</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4" v-for="tpl in listTemplates" :key="tpl.id">
                        <div class="tu-card p-3 h-100 border-start border-teal border-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-teal bg-opacity-10 text-teal font-monospace">{{ tpl.kode_template }}</span>
                                <span class="badge bg-light text-muted border fs-9">Aktif</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">{{ tpl.nama_template_surat }}</h6>
                            <p class="text-muted fs-8 mb-3">{{ tpl.judul_surat }}</p>
                            <div class="bg-light p-2 rounded-2 fs-9 text-muted mb-3" style="max-height: 80px; overflow: hidden;">
                                <div v-html="tpl.konten_html"></div>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-xs btn-outline-teal rounded-pill px-3" @click="previewTemplate(tpl)">
                                    <i class="bi bi-eye me-1"></i>Pratinjau
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 6: KLASIFIKASI & KOP SURAT -->
        <!-- ================================================================= -->
        <div v-show="activeTab === 'master'" class="animate-fade-in">
            <div class="row g-4">
                <!-- Kop Surat Config -->
                <div class="col-md-6">
                    <div class="tu-card p-4 h-100">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-card-heading text-teal me-2"></i>Pengaturan Kop Surat Resmi Sekolah</h5>
                        <form @submit.prevent="saveKopSurat">
                            <div class="mb-2">
                                <label class="form-label fs-8 fw-semibold mb-1">Instansi Induk / Dinas</label>
                                <input type="text" class="form-control form-control-sm rounded-2" v-model="formKop.nama_instansi_atas" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fs-8 fw-semibold mb-1">Nama Resmi Satuan Pendidikan</label>
                                <input type="text" class="form-control form-control-sm rounded-2" v-model="formKop.nama_sekolah" required>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label fs-8 fw-semibold mb-1">NPSN</label>
                                    <input type="text" class="form-control form-control-sm rounded-2" v-model="formKop.npsn">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-8 fw-semibold mb-1">Akreditasi</label>
                                    <input type="text" class="form-control form-control-sm rounded-2" v-model="formKop.akreditasi">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fs-8 fw-semibold mb-1">Alamat Lengkap</label>
                                <textarea class="form-control form-control-sm rounded-2" rows="2" v-model="formKop.alamat"></textarea>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fs-8 fw-semibold mb-1">Telepon / Fax</label>
                                    <input type="text" class="form-control form-control-sm rounded-2" v-model="formKop.telepon">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-8 fw-semibold mb-1">Email Resmi</label>
                                    <input type="email" class="form-control form-control-sm rounded-2" v-model="formKop.email">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-teal btn-sm fw-semibold rounded-3" :disabled="savingKop">
                                <i class="bi bi-check-circle me-1"></i>Simpan Perubahan Kop
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Kode Klasifikasi Table -->
                <div class="col-md-6">
                    <div class="tu-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-tags-fill text-teal me-2"></i>Kode Klasifikasi Baku</h5>
                            <button class="btn btn-xs btn-outline-teal rounded-pill px-2.5" @click="openModalKlasifikasi()">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Kode
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle fs-8">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Klasifikasi</th>
                                        <th>Retensi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="k in listKlasifikasi" :key="k.id">
                                        <td class="fw-bold font-monospace text-teal">{{ k.kode_klasifikasi }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ k.nama_klasifikasi }}</div>
                                            <small class="text-muted">{{ k.deskripsi }}</small>
                                        </td>
                                        <td class="text-muted">{{ k.retensi_tahun }} Thn</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 7: VERIFIKASI TTE QR -->
        <!-- ================================================================= -->
        <div v-show="activeTab === 'verifikasi'" class="animate-fade-in">
            <div class="tu-card p-4 mx-auto" style="max-width: 600px;">
                <div class="text-center mb-4">
                    <div class="stat-icon-wrapper bg-teal bg-opacity-10 text-teal mx-auto mb-2">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Verifikator Keaslian Dokumen Digital</h5>
                    <p class="text-muted fs-8">Masukkan kode token unik atau pindai QR Code naskah dinas resmi sekolah.</p>
                </div>

                <div class="input-group mb-3">
                    <input type="text" class="form-control rounded-start-3 font-monospace fs-7" v-model="verifyTokenInput" placeholder="Masukkan token QR (misal: 3f8a9e...)" @keyup.enter="verifikasiToken">
                    <button class="btn btn-teal px-4 fw-semibold rounded-end-3" @click="verifikasiToken" :disabled="verifyingToken">
                        <i class="bi bi-search me-1"></i>Verifikasi
                    </button>
                </div>

                <div v-if="verifyResult" class="p-3 rounded-3 mt-3 border" :class="verifyResult.is_valid ? 'bg-success bg-opacity-10 border-success' : 'bg-danger bg-opacity-10 border-danger'">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi" :class="verifyResult.is_valid ? 'bi-patch-check-fill text-success fs-4' : 'bi-x-circle-fill text-danger fs-4'"></i>
                        <h6 class="fw-bold mb-0" :class="verifyResult.is_valid ? 'text-success' : 'text-danger'">
                            {{ verifyResult.is_valid ? 'Dokumen Terverifikasi Sah & Asli' : 'Dokumen Tidak Valid' }}
                        </h6>
                    </div>
                    <div v-if="verifyResult.is_valid" class="fs-8 text-dark">
                        <div><strong>Nomor Surat:</strong> {{ verifyResult.nomor_surat }}</div>
                        <div><strong>Perihal:</strong> {{ verifyResult.perihal }}</div>
                        <div><strong>Tujuan:</strong> {{ verifyResult.tujuan }}</div>
                        <div><strong>Penandatangan:</strong> {{ verifyResult.penandatangan }}</div>
                        <div><strong>Satuan Pendidikan:</strong> {{ verifyResult.nama_sekolah }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL: PROSES TERBITKAN SURAT PANGGILAN BK (TATA USAHA) -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalProsesTerbitBk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-teal text-white p-3">
                    <h6 class="modal-title fw-bold"><i class="bi bi-send-check-fill me-2"></i>Penerbitan Surat Panggilan Resmi Siswa (Tata Usaha)</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="submitProsesTerbitBk">
                    <div class="modal-body p-4">
                        <div class="alert alert-info d-flex align-items-center gap-2 fs-8 py-2 px-3 mb-3">
                            <i class="bi bi-info-circle-fill fs-5"></i>
                            <div>Pengajuan dari Guru BK: <strong>{{ selectedPengajuanBk?.nama_siswa }}</strong> (Kelas {{ selectedPengajuanBk?.kelas }}) — Total <strong>{{ selectedPengajuanBk?.total_poin }} Poin</strong></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Nomor Surat Keluar Resmi (Auto-Generated)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control font-monospace fw-bold" v-model="formTerbitBk.nomor_surat" required>
                                    <button class="btn btn-outline-secondary" type="button" @click="regenerateNomorBk">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Tanggal Surat</label>
                                <input type="date" class="form-control form-control-sm rounded-2" v-model="formTerbitBk.tgl_surat" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-semibold mb-1">Perihal Surat</label>
                            <input type="text" class="form-control form-control-sm rounded-2" v-model="formTerbitBk.perihal" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Nama Penandatangan</label>
                                <input type="text" class="form-control form-control-sm rounded-2" v-model="formTerbitBk.nama_penandatangan" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Jabatan</label>
                                <input type="text" class="form-control form-control-sm rounded-2" v-model="formTerbitBk.jabatan_penandatangan" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fs-8 fw-semibold mb-1">Catatan Verifikasi Tata Usaha</label>
                            <textarea class="form-control form-control-sm rounded-2" rows="2" v-model="formTerbitBk.catatan_tu" placeholder="Catatan internal TU..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-sm btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-teal fw-semibold rounded-3" :disabled="submittingTerbitBk">
                            <i class="bi bi-patch-check-fill me-1"></i>Sahkan & Terbitkan Surat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL: INPUT SURAT MASUK -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalSuratMasuk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-teal text-white p-3">
                    <h6 class="modal-title fw-bold"><i class="bi bi-inbox-fill me-2"></i>Pencatatan Surat Masuk Baru</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="submitSuratMasuk">
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Nomor Surat Pengirim</label>
                                <input type="text" class="form-control form-control-sm rounded-2" v-model="formSuratMasuk.no_surat" required placeholder="Contoh: 421/102/Disdik/2026">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Instansi / Pengirim</label>
                                <input type="text" class="form-control form-control-sm rounded-2" v-model="formSuratMasuk.pengirim" required placeholder="Contoh: Dinas Pendidikan Provinsi">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-semibold mb-1">Perihal Surat</label>
                            <input type="text" class="form-control form-control-sm rounded-2" v-model="formSuratMasuk.perihal" required placeholder="Contoh: Undangan Rapat Koordinasi Akreditasi">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Tanggal Surat</label>
                                <input type="date" class="form-control form-control-sm rounded-2" v-model="formSuratMasuk.tgl_surat" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Tanggal Diterima</label>
                                <input type="date" class="form-control form-control-sm rounded-2" v-model="formSuratMasuk.tgl_terima" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-semibold mb-1">Ringkasan / Catatan Isi Surat</label>
                            <textarea class="form-control form-control-sm rounded-2" rows="2" v-model="formSuratMasuk.ringkasan_isi" placeholder="Ringkasan singkat isi surat..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-sm btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-teal fw-semibold rounded-3" :disabled="submittingSuratMasuk">
                            <i class="bi bi-save me-1"></i>Simpan Surat Masuk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL: BUAT SURAT KELUAR -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalSuratKeluar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-warning text-dark p-3">
                    <h6 class="modal-title fw-bold"><i class="bi bi-send-fill me-2"></i>Registrasi Surat Keluar & Naskah Dinas</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="submitSuratKeluar">
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Kode Klasifikasi Surat</label>
                                <select class="form-select form-select-sm rounded-2" v-model="formSuratKeluar.id_kode_klasifikasi" @change="generateNomorSuratKeluar">
                                    <option v-for="k in listKlasifikasi" :key="k.id" :value="k.id">{{ k.kode_klasifikasi }} - {{ k.nama_klasifikasi }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Nomor Surat Resmi (Auto-Generated)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control font-monospace fw-bold" v-model="formSuratKeluar.nomor_surat" required>
                                    <button class="btn btn-outline-secondary" type="button" @click="generateNomorSuratKeluar">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Tujuan / Penerima Surat</label>
                                <input type="text" class="form-control form-control-sm rounded-2" v-model="formSuratKeluar.tujuan" required placeholder="Contoh: Kepala Dinas Pendidikan / Orang Tua Siswa">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-semibold mb-1">Tanggal Surat</label>
                                <input type="date" class="form-control form-control-sm rounded-2" v-model="formSuratKeluar.tgl_surat" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-semibold mb-1">Perihal Surat</label>
                            <input type="text" class="form-control form-control-sm rounded-2" v-model="formSuratKeluar.perihal" required placeholder="Contoh: Surat Permohonan Bantuan / Undangan Dinas">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-semibold mb-1">Ringkasan / Isi Ringkas</label>
                            <textarea class="form-control form-control-sm rounded-2" rows="2" v-model="formSuratKeluar.ringkasan_isi"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-sm btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold rounded-3" :disabled="submittingSuratKeluar">
                            <i class="bi bi-send-check-fill me-1"></i>Daftarkan Surat Keluar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL: PRATINJAU CETAK RESMI (A4 FORMAT BER-KOP) -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalCetakResmi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-dark text-white p-3 no-print">
                    <h6 class="modal-title fw-bold"><i class="bi bi-printer me-2"></i>Pratinjau Dokumen Naskah Dinas Resmi</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-5 print-area bg-white text-dark" style="min-height: 500px; font-family: 'Times New Roman', Times, serif;">
                    <!-- Kop Surat -->
                    <div class="kop-surat-preview text-center">
                        <h6 class="text-uppercase fw-bold mb-0" style="font-size: 13pt;">{{ docCetak?.kop?.nama_instansi_atas || 'PEMERINTAH DAERAH' }}</h6>
                        <h5 class="text-uppercase fw-bold mb-1" style="font-size: 15pt; letter-spacing: 0.5px;">{{ docCetak?.kop?.nama_sekolah || 'SMA NEGERI RESMI' }}</h5>
                        <p class="mb-0 fs-8" style="font-size: 9.5pt; font-family: Arial, sans-serif;">
                            {{ docCetak?.kop?.alamat || '' }} | Telp: {{ docCetak?.kop?.telepon || '-' }} | Email: {{ docCetak?.kop?.email || '-' }}
                        </p>
                    </div>

                    <!-- Judul / Meta Surat -->
                    <div class="d-flex justify-content-between mb-4" style="font-size: 11pt;">
                        <div>
                            <table>
                                <tr><td style="width: 80px;">Nomor</td><td>: <strong>{{ docCetak?.surat?.nomor_surat }}</strong></td></tr>
                                <tr><td>Sifat</td><td>: Biasa / Penting</td></tr>
                                <tr><td>Lampiran</td><td>: -</td></tr>
                                <tr><td>Perihal</td><td>: <u>{{ docCetak?.surat?.perihal }}</u></td></tr>
                            </table>
                        </div>
                        <div class="text-end">
                            <div>{{ docCetak?.kop?.kota_kabupaten || 'Surabaya' }}, {{ docCetak?.surat?.tgl_surat }}</div>
                            <div class="mt-2">Kepada Yth.</div>
                            <div class="fw-bold">{{ docCetak?.surat?.tujuan }}</div>
                            <div>di Tempat</div>
                        </div>
                    </div>

                    <!-- Isi Surat -->
                    <div class="isi-surat my-4" style="font-size: 11.5pt; line-height: 1.6; text-align: justify;">
                        <p>Dengan hormat,</p>
                        <p>{{ docCetak?.surat?.ringkasan_isi || 'Sehubungan dengan agenda kedinasan sekolah, kami mengharap kehadiran Bapak/Ibu pada waktu dan tempat yang telah ditentukan.' }}</p>
                        <p>Demikian surat ini kami sampaikan, atas perhatian dan kerja samanya kami ucapkan terima kasih.</p>
                    </div>

                    <!-- Tanda Tangan & QR TTE -->
                    <div class="row mt-5 pt-3" style="font-size: 11pt;">
                        <div class="col-7">
                            <div v-if="docCetak?.surat?.qr_token" class="text-center p-2 border rounded d-inline-block bg-light">
                                <div class="font-monospace fs-9 text-muted mb-1">Validasi TTE QR Resmi</div>
                                <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=' + encodeURIComponent('<?= $baseUrl ?>/validasi/surat?token=' + docCetak?.surat?.qr_token)" width="85" height="85" alt="QR TTE">
                            </div>
                        </div>
                        <div class="col-5 text-center">
                            <div>Mengetahui,</div>
                            <div class="fw-bold">{{ docCetak?.surat?.jabatan_penandatangan || 'Kepala Sekolah' }}</div>
                            <div style="height: 65px;"></div>
                            <div class="fw-bold text-decoration-underline">{{ docCetak?.surat?.nama_penandatangan || 'Kepala Sekolah' }}</div>
                            <div class="fs-9 text-muted">NIP. 19750101 200003 1 001</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 no-print">
                    <button type="button" class="btn btn-sm btn-secondary rounded-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-sm btn-primary fw-semibold rounded-3" onclick="window.print()">
                        <i class="bi bi-printer-fill me-1"></i>Cetak Dokumen (Print)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const { createApp, ref, computed, onMounted } = Vue;

    const appConfig = {
        setup() {
            const baseUrl = '<?= $baseUrl ?>';
            const isSuperAdmin = ref(<?= json_encode($isSuperAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const tenants = ref(<?= json_encode($tenants, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const currentTenantId = ref(<?= json_encode($selectedTenantId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            const filterTenantId = ref(currentTenantId.value || '');

            // ─── URL-to-Tab Synchronization Resolver ───
            const resolveTabFromUrl = () => {
                const path = window.location.pathname.toLowerCase();
                if (path.includes('/persuratan/surat-masuk')) return 'surat_masuk';
                if (path.includes('/persuratan/surat-keluar')) return 'surat_keluar';
                if (path.includes('/persuratan/pengajuan-bk')) return 'pengajuan_bk';
                if (path.includes('/persuratan/template')) return 'template';
                if (path.includes('/persuratan/master')) return 'master';
                if (path.includes('/persuratan/verifikasi') || path.includes('/validasi/surat')) return 'verifikasi';
                return 'dashboard';
            };

            const activeTab = ref(resolveTabFromUrl());
            const loadingGlobal = ref(false);

            const tabUrlMap = {
                'dashboard': `${baseUrl}/persuratan/dashboard`,
                'surat_masuk': `${baseUrl}/persuratan/surat-masuk`,
                'surat_keluar': `${baseUrl}/persuratan/surat-keluar`,
                'pengajuan_bk': `${baseUrl}/persuratan/pengajuan-bk`,
                'template': `${baseUrl}/persuratan/template`,
                'master': `${baseUrl}/persuratan/master`,
                'verifikasi': `${baseUrl}/persuratan/verifikasi`,
            };

            const switchTab = (tabKey) => {
                activeTab.value = tabKey;
                const targetUrl = tabUrlMap[tabKey] || `${baseUrl}/persuratan/dashboard`;
                if (window.location.pathname !== targetUrl) {
                    window.history.pushState({ tab: tabKey }, '', targetUrl);
                }
            };

            // Tangani tombol Back / Forward browser
            window.addEventListener('popstate', (e) => {
                if (e.state && e.state.tab) {
                    activeTab.value = e.state.tab;
                } else {
                    activeTab.value = resolveTabFromUrl();
                }
            });

            const stats = ref({});
            const listSuratMasuk = ref([]);
            const listSuratKeluar = ref([]);
            const listPengajuanBk = ref([]);
            const listTemplates = ref([]);
            const listKlasifikasi = ref([]);
            const formKop = ref({});
            const savingKop = ref(false);

            // Filter states
            const filterSuratMasuk = ref({ search: '', status_disposisi: '' });
            const filterSuratKeluar = ref({ search: '', id_kode_klasifikasi: '' });

            // Form states
            const formSuratMasuk = ref({ no_surat: '', pengirim: '', perihal: '', tgl_surat: new Date().toISOString().split('T')[0], tgl_terima: new Date().toISOString().split('T')[0], ringkasan_isi: '' });
            const submittingSuratMasuk = ref(false);

            const formSuratKeluar = ref({ nomor_surat: '', id_kode_klasifikasi: '', tujuan: '', perihal: '', tgl_surat: new Date().toISOString().split('T')[0], ringkasan_isi: '' });
            const submittingSuratKeluar = ref(false);

            // Proses Pengajuan BK
            const selectedPengajuanBk = ref(null);
            const formTerbitBk = ref({ nomor_surat: '', tgl_surat: new Date().toISOString().split('T')[0], perihal: '', nama_penandatangan: 'Kepala Sekolah', jabatan_penandatangan: 'Kepala Sekolah', catatan_tu: '' });
            const submittingTerbitBk = ref(false);

            // Cetak & Verifikasi
            const docCetak = ref(null);
            const verifyTokenInput = ref('');
            const verifyResult = ref(null);
            const verifyingToken = ref(false);

            const pengajuanBkPendingCount = computed(() => {
                return listPengajuanBk.value.filter(p => p.status_pengajuan !== 'Surat Resmi Telah Terbit').length;
            });

            // ─── API Fetchers ───
            const fetchStats = async () => {
                try {
                    let url = `${baseUrl}/api/v1/persuratan/dashboard/stats`;
                    if (filterTenantId.value) url += `?tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data?.success) stats.value = res.data.data || {};
                } catch (e) { console.error('fetchStats error', e); }
            };

            const fetchSuratMasuk = async () => {
                try {
                    const params = new URLSearchParams(filterSuratMasuk.value);
                    if (filterTenantId.value) params.append('tenant_id', filterTenantId.value);
                    const res = await axios.get(`${baseUrl}/api/v1/persuratan/surat-masuk?${params.toString()}`);
                    if (res.data?.success) listSuratMasuk.value = res.data.data || [];
                } catch (e) { console.error('fetchSuratMasuk error', e); }
            };

            const fetchSuratKeluar = async () => {
                try {
                    const params = new URLSearchParams(filterSuratKeluar.value);
                    if (filterTenantId.value) params.append('tenant_id', filterTenantId.value);
                    const res = await axios.get(`${baseUrl}/api/v1/persuratan/surat-keluar?${params.toString()}`);
                    if (res.data?.success) listSuratKeluar.value = res.data.data || [];
                } catch (e) { console.error('fetchSuratKeluar error', e); }
            };

            const fetchPengajuanBk = async () => {
                try {
                    let url = `${baseUrl}/api/v1/persuratan/pengajuan-bk`;
                    if (filterTenantId.value) url += `?tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data?.success) listPengajuanBk.value = res.data.data || [];
                } catch (e) { console.error('fetchPengajuanBk error', e); }
            };

            const fetchTemplates = async () => {
                try {
                    let url = `${baseUrl}/api/v1/persuratan/template`;
                    if (filterTenantId.value) url += `?tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data?.success) listTemplates.value = res.data.data || [];
                } catch (e) { console.error('fetchTemplates error', e); }
            };

            const fetchKlasifikasi = async () => {
                try {
                    let url = `${baseUrl}/api/v1/persuratan/klasifikasi`;
                    if (filterTenantId.value) url += `?tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data?.success) listKlasifikasi.value = res.data.data || [];
                } catch (e) { console.error('fetchKlasifikasi error', e); }
            };

            const fetchKopSurat = async () => {
                try {
                    let url = `${baseUrl}/api/v1/persuratan/kop-surat`;
                    if (filterTenantId.value) url += `?tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data?.success) formKop.value = res.data.data || {};
                } catch (e) { console.error('fetchKopSurat error', e); }
            };

            const refreshAllData = async () => {
                loadingGlobal.value = true;
                await Promise.all([
                    fetchStats(),
                    fetchSuratMasuk(),
                    fetchSuratKeluar(),
                    fetchPengajuanBk(),
                    fetchTemplates(),
                    fetchKlasifikasi(),
                    fetchKopSurat()
                ]);
                loadingGlobal.value = false;
            };

            const onTenantChange = async () => {
                await refreshAllData();
            };

            // ─── Actions ───
            const openModalSuratMasuk = () => {
                formSuratMasuk.value = { no_surat: '', pengirim: '', perihal: '', tgl_surat: new Date().toISOString().split('T')[0], tgl_terima: new Date().toISOString().split('T')[0], ringkasan_isi: '', tenant_id: filterTenantId.value || currentTenantId.value };
                new bootstrap.Modal(document.getElementById('modalSuratMasuk')).show();
            };

            const submitSuratMasuk = async () => {
                submittingSuratMasuk.value = true;
                try {
                    const payload = { ...formSuratMasuk.value };
                    if (filterTenantId.value) payload.tenant_id = filterTenantId.value;
                    const res = await axios.post(`${baseUrl}/api/v1/persuratan/surat-masuk/save`, payload);
                    if (res.data?.success) {
                        Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false });
                        bootstrap.Modal.getInstance(document.getElementById('modalSuratMasuk'))?.hide();
                        fetchSuratMasuk();
                        fetchStats();
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.message || 'Terjadi kesalahan.' });
                } finally {
                    submittingSuratMasuk.value = false;
                }
            };

            const hapusSuratMasuk = async (id) => {
                const conf = await Swal.fire({ title: 'Hapus Surat Masuk?', text: 'Data arsip surat masuk akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus' });
                if (!conf.isConfirmed) return;
                try {
                    const res = await axios.post(`${baseUrl}/api/v1/persuratan/surat-masuk/delete`, { id, tenant_id: filterTenantId.value });
                    if (res.data?.success) {
                        Swal.fire({ icon: 'success', title: 'Terhapus', text: res.data.message, timer: 1500, showConfirmButton: false });
                        fetchSuratMasuk();
                        fetchStats();
                    }
                } catch (e) { Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menghapus.' }); }
            };

            const openModalSuratKeluar = async () => {
                formSuratKeluar.value = {
                    nomor_surat: '',
                    id_kode_klasifikasi: listKlasifikasi.value[0]?.id || '',
                    tujuan: '',
                    perihal: '',
                    tgl_surat: new Date().toISOString().split('T')[0],
                    ringkasan_isi: '',
                    tenant_id: filterTenantId.value || currentTenantId.value
                };
                await generateNomorSuratKeluar();
                new bootstrap.Modal(document.getElementById('modalSuratKeluar')).show();
            };

            const generateNomorSuratKeluar = async () => {
                try {
                    const idKlas = formSuratKeluar.value.id_kode_klasifikasi || '';
                    let url = `${baseUrl}/api/v1/persuratan/surat-keluar/generate-nomor?id_kode_klasifikasi=${idKlas}`;
                    if (filterTenantId.value) url += `&tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data?.success) {
                        formSuratKeluar.value.nomor_surat = res.data.data.nomor_surat;
                    }
                } catch (e) {}
            };

            const submitSuratKeluar = async () => {
                submittingSuratKeluar.value = true;
                try {
                    const payload = { ...formSuratKeluar.value };
                    if (filterTenantId.value) payload.tenant_id = filterTenantId.value;
                    const res = await axios.post(`${baseUrl}/api/v1/persuratan/surat-keluar/save`, payload);
                    if (res.data?.success) {
                        Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false });
                        bootstrap.Modal.getInstance(document.getElementById('modalSuratKeluar'))?.hide();
                        fetchSuratKeluar();
                        fetchStats();
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.message || 'Gagal menyimpan surat keluar.' });
                } finally {
                    submittingSuratKeluar.value = false;
                }
            };

            const hapusSuratKeluar = async (id) => {
                const conf = await Swal.fire({ title: 'Hapus Surat Keluar?', text: 'Data register nomor surat keluar akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus' });
                if (!conf.isConfirmed) return;
                try {
                    const res = await axios.post(`${baseUrl}/api/v1/persuratan/surat-keluar/delete`, { id, tenant_id: filterTenantId.value });
                    if (res.data?.success) {
                        Swal.fire({ icon: 'success', title: 'Terhapus', text: res.data.message, timer: 1500, showConfirmButton: false });
                        fetchSuratKeluar();
                        fetchStats();
                    }
                } catch (e) { Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menghapus.' }); }
            };

            // ─── Tindak Lanjut Notifikasi BK ───
            const openModalProsesTerbitBk = async (pengajuan) => {
                selectedPengajuanBk.value = pengajuan;
                formTerbitBk.value = {
                    id_pengajuan: pengajuan.id,
                    nomor_surat: '',
                    tgl_surat: new Date().toISOString().split('T')[0],
                    perihal: `Surat Pemanggilan Orang Tua / Wali Siswa (${pengajuan.jenis_panggilan})`,
                    nama_penandatangan: 'Kepala Sekolah',
                    jabatan_penandatangan: 'Kepala Sekolah',
                    catatan_tu: 'Surat resmi telah diterbitkan oleh Tata Usaha.',
                    tenant_id: filterTenantId.value || currentTenantId.value
                };
                await regenerateNomorBk();
                new bootstrap.Modal(document.getElementById('modalProsesTerbitBk')).show();
            };

            const regenerateNomorBk = async () => {
                try {
                    let url = `${baseUrl}/api/v1/persuratan/surat-keluar/generate-nomor`;
                    if (filterTenantId.value) url += `?tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data?.success) {
                        formTerbitBk.value.nomor_surat = res.data.data.nomor_surat;
                    }
                } catch (e) {}
            };

            const submitProsesTerbitBk = async () => {
                submittingTerbitBk.value = true;
                try {
                    const payload = { ...formTerbitBk.value };
                    if (filterTenantId.value) payload.tenant_id = filterTenantId.value;
                    const res = await axios.post(`${baseUrl}/api/v1/persuratan/pengajuan-bk/proses-terbit`, payload);
                    if (res.data?.success) {
                        Swal.fire({ icon: 'success', title: 'Surat Resmi Terbit!', text: res.data.message, timer: 2000, showConfirmButton: false });
                        bootstrap.Modal.getInstance(document.getElementById('modalProsesTerbitBk'))?.hide();
                        fetchPengajuanBk();
                        fetchSuratKeluar();
                        fetchStats();
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.response?.data?.message || 'Gagal menerbitkan surat panggilan.' });
                } finally {
                    submittingTerbitBk.value = false;
                }
            };

            const cetakSuratResmi = async (idSuratKeluar) => {
                if (!idSuratKeluar) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'ID surat keluar tidak valid.' });
                    return;
                }
                try {
                    let url = `${baseUrl}/api/v1/persuratan/surat-keluar/detail-cetak?id=${idSuratKeluar}`;
                    if (filterTenantId.value) url += `&tenant_id=${encodeURIComponent(filterTenantId.value)}`;
                    const res = await axios.get(url);
                    if (res.data?.success) {
                        docCetak.value = res.data.data;
                        new bootstrap.Modal(document.getElementById('modalCetakResmi')).show();
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memuat dokumen cetak.' });
                }
            };

            const saveKopSurat = async () => {
                savingKop.value = true;
                try {
                    const payload = { ...formKop.value };
                    if (filterTenantId.value) payload.tenant_id = filterTenantId.value;
                    const res = await axios.post(`${baseUrl}/api/v1/persuratan/kop-surat/save`, payload);
                    if (res.data?.success) {
                        Swal.fire({ icon: 'success', title: 'Sukses', text: res.data.message, timer: 1500, showConfirmButton: false });
                        fetchKopSurat();
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menyimpan kop surat.' });
                } finally {
                    savingKop.value = false;
                }
            };

            const verifikasiToken = async () => {
                if (!verifyTokenInput.value.trim()) return;
                verifyingToken.value = true;
                try {
                    const res = await axios.get(`${baseUrl}/api/v1/persuratan/verify?token=${encodeURIComponent(verifyTokenInput.value.trim())}`);
                    verifyResult.value = res.data?.data || null;
                } catch (e) {
                    verifyResult.value = { is_valid: false };
                } finally {
                    verifyingToken.value = false;
                }
            };

            onMounted(async () => {
                await refreshAllData();
            });

            return {
                isSuperAdmin, tenants, filterTenantId, onTenantChange,
                activeTab, switchTab, loadingGlobal, stats, listSuratMasuk, listSuratKeluar, listPengajuanBk,
                listTemplates, listKlasifikasi, formKop, savingKop, filterSuratMasuk, filterSuratKeluar,
                formSuratMasuk, submittingSuratMasuk, formSuratKeluar, submittingSuratKeluar,
                selectedPengajuanBk, formTerbitBk, submittingTerbitBk, docCetak, verifyTokenInput,
                verifyResult, verifyingToken, pengajuanBkPendingCount,
                fetchSuratMasuk, fetchSuratKeluar, openModalSuratMasuk, submitSuratMasuk, hapusSuratMasuk,
                openModalSuratKeluar, generateNomorSuratKeluar, submitSuratKeluar, hapusSuratKeluar,
                openModalProsesTerbitBk, regenerateNomorBk, submitProsesTerbitBk, cetakSuratResmi,
                saveKopSurat, verifikasiToken
            };
        }
    };

    if (window.VueAppRegistry) {
        window.VueAppRegistry.register('#persuratan-app', appConfig);
    } else {
        document.addEventListener('DOMContentLoaded', () => createApp(appConfig).mount('#persuratan-app'));
    }
})();
</script>
