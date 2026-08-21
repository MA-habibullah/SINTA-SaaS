<?php
/**
 * View: Katalog & Inventori Perpustakaan Terpadu
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
?>
<div id="katalogPerpusApp" v-cloak class="container-fluid px-0">
    <!-- ═══════════════════════════════════════════════════════════════════════
         HERO BANNER & MULTI-TENANT SAAS SWITCHER
         ═══════════════════════════════════════════════════════════════════════ -->
    <?php
    $heroIcon = 'bi-journal-album';
    $heroBadge = 'Modul Perpustakaan & Katalog';
    $heroTitle = 'Katalog & Inventori Koleksi Pustaka';
    $heroDesc = 'Manajemen terpadu master bibliografi, tata letak rak, klasifikasi DDC, eksemplar barcode, dan usulan koleksi.';
    $heroButtons = '
        <a href="' . $this->getBaseUrl() . '/perpustakaan" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 border border-white/20 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <a href="' . $this->getBaseUrl() . '/perpustakaan/opac" target="_blank" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-globe me-1"></i> Buka OPAC Publik
        </a>
    ';
    include __DIR__ . '/_tenant_filter.php';
    ?>

    <!-- Mini KPI Stat Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3 h-100 transition-all hover:shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-8 fw-medium">Total Judul</span>
                    <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                        <i class="bi bi-book fs-6"></i>
                    </div>
                </div>
                <div class="h4 fw-bold text-slate-900 mb-0 font-mono">{{ katalogList.length }}</div>
                <small class="text-muted fs-9">Bibliografi terdaftar</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3 h-100 transition-all hover:shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-8 fw-medium">Total Eksemplar</span>
                    <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                        <i class="bi bi-collection fs-6"></i>
                    </div>
                </div>
                <div class="h4 fw-bold text-emerald-700 mb-0 font-mono">{{ totalEksemplarCount }}</div>
                <small class="text-muted fs-9">Fisik & Digital</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3 h-100 transition-all hover:shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-8 fw-medium">Koleksi E-Book</span>
                    <div class="p-2 rounded-xl bg-purple-50 text-purple-600">
                        <i class="bi bi-file-earmark-pdf fs-6"></i>
                    </div>
                </div>
                <div class="h4 fw-bold text-purple-700 mb-0 font-mono">{{ totalEbookCount }}</div>
                <small class="text-muted fs-9">Format PDF/EPUB</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3 h-100 transition-all hover:shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-8 fw-medium">Lokasi Rak</span>
                    <div class="p-2 rounded-xl bg-amber-50 text-amber-600">
                        <i class="bi bi-layout-sidebar fs-6"></i>
                    </div>
                </div>
                <div class="h4 fw-bold text-amber-700 mb-0 font-mono">{{ rakList.length }}</div>
                <small class="text-muted fs-9">Titik simpan fisik</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3 h-100 transition-all hover:shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-8 fw-medium">Usulan Pending</span>
                    <div class="p-2 rounded-xl bg-rose-50 text-rose-600">
                        <i class="bi bi-lightbulb fs-6"></i>
                    </div>
                </div>
                <div class="h4 fw-bold text-rose-700 mb-0 font-mono">{{ usulanPendingCount }}</div>
                <small class="text-muted fs-9">Menunggu persetujuan</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3 h-100 transition-all hover:shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-8 fw-medium">Serial Aktif</span>
                    <div class="p-2 rounded-xl bg-cyan-50 text-cyan-600">
                        <i class="bi bi-newspaper fs-6"></i>
                    </div>
                </div>
                <div class="h4 fw-bold text-cyan-700 mb-0 font-mono">{{ serialAktifCount }}</div>
                <small class="text-muted fs-9">Koran & Majalah</small>
            </div>
        </div>
    </div>

    <!-- Modern Navtabs Navigation (Gambar 1 Pill Standard) -->
    <div class="card border-0 shadow-2xs rounded-2xl mb-4 bg-white">
        <div class="card-body p-2">
            <div class="nav-pills-container d-flex align-items-center justify-content-between">
                <ul class="nav nav-pills custom-modern-pills flex-nowrap overflow-x-auto text-nowrap gap-1 px-1" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'katalog' }" @click="activeTab = 'katalog'" type="button">
                            <i class="bi bi-book me-1.5 text-primary"></i> 1. Master Bibliografi ({{ katalogList.length }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'rak' }" @click="activeTab = 'rak'" type="button">
                            <i class="bi bi-layout-sidebar me-1.5 text-warning"></i> 2. Lokasi Rak ({{ rakList.length }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'ddc' }" @click="activeTab = 'ddc'" type="button">
                            <i class="bi bi-diagram-3 me-1.5 text-info"></i> 3. Klasifikasi DDC ({{ ddcList.length }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'usulan' }" @click="activeTab = 'usulan'" type="button">
                            <i class="bi bi-lightbulb me-1.5 text-danger"></i> 4. Usulan Pengadaan ({{ usulanList.length }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'serial' }" @click="activeTab = 'serial'" type="button">
                            <i class="bi bi-newspaper me-1.5 text-indigo"></i> 5. Serial & Berkala ({{ serialList.length }})
                        </button>
                    </li>
                </ul>

                <button @click="refreshCurrentTab" class="btn btn-light btn-sm text-secondary rounded-xl px-3 py-1.5 border border-slate-200/80 shadow-2xs ms-2 flex-shrink-0 d-none d-md-flex align-items-center gap-1.5">
                    <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> <span class="fs-8 fw-semibold">Segarkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: MASTER BIBLIOGRAFI / KATALOG BUKU -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'katalog'" class="tab-pane-content transition-all">
        <!-- Single-Line Symmetrical Toolbar -->
        <div class="card border-0 shadow-2xs rounded-2xl mb-4 bg-white">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap flex-lg-nowrap justify-content-between align-items-center gap-3">
                    <!-- Search Box -->
                    <div class="position-relative flex-grow-1" style="min-width: 260px;">
                        <label for="searchQueryKatalog" class="visually-hidden">Cari Judul Buku</label>
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-7"></i>
                        <input type="text" id="searchQueryKatalog" name="search_query" v-model="searchQuery" @input="filterKatalog"
                               class="form-control form-control-sm ps-5 rounded-xl border-slate-200 shadow-2xs text-xs font-medium py-2"
                               placeholder="Cari judul buku, penulis, penerbit, DDC, atau ISBN..."
                               aria-label="Cari judul buku, penulis, penerbit, DDC, atau ISBN">
                        <button v-if="searchQuery" @click="searchQuery = ''; filterKatalog()" class="btn btn-link btn-sm position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 p-0 text-decoration-none" aria-label="Hapus kata kunci pencarian">
                            <i class="bi bi-x-circle-fill fs-7"></i>
                        </button>
                    </div>

                    <!-- Filters & Actions (Single-line row) -->
                    <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                        <label for="filterKategoriKatalog" class="visually-hidden">Filter Kategori</label>
                        <select id="filterKategoriKatalog" name="filter_kategori" v-model="filterKategori" @change="filterKatalog"
                                class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer"
                                aria-label="Filter Kategori Buku" style="width: auto; min-width: 140px;">
                            <option value="">Semua Kategori</option>
                            <option value="Umum">Umum / Non-Fiksi</option>
                            <option value="Fiksi">Fiksi / Novel</option>
                            <option value="Pelajaran">Paket Pelajaran</option>
                            <option value="Referensi">Referensi / Ensiklopedia</option>
                            <option value="Majalah">Majalah / Serial</option>
                            <option value="Agama">Agama & Moral</option>
                            <option value="Teknologi">Teknologi & Sains</option>
                        </select>

                        <label for="filterMediaKatalog" class="visually-hidden">Filter Format Media</label>
                        <select id="filterMediaKatalog" name="filter_media" v-model="filterMedia" @change="filterKatalog"
                                class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer"
                                aria-label="Filter Format Media" style="width: auto; min-width: 120px;">
                            <option value="">Semua Media</option>
                            <option value="fisik">Buku Fisik</option>
                            <option value="ebook">E-Book Digital</option>
                        </select>

                        <a href="<?= $this->getBaseUrl() ?>/perpustakaan/katalog/export-excel" class="btn btn-outline-success btn-sm rounded-xl px-3 py-1.5 fs-7 font-semibold shadow-2xs d-flex align-items-center gap-1.5">
                            <i class="bi bi-file-earmark-spreadsheet"></i> <span>Export Excel</span>
                        </a>

                        <button @click="openModalTambahBuku" class="btn btn-primary btn-sm rounded-xl px-3.5 py-1.5 fs-7 font-semibold shadow-2xs d-flex align-items-center gap-1.5">
                            <i class="bi bi-plus-lg"></i> <span>Tambah Judul</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table / Seamless Empty State -->
        <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
            <!-- Loading Indicator -->
            <div v-if="loading" class="p-5 text-center">
                <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                <p class="text-muted fs-7 mb-0">Memuat master koleksi perpustakaan...</p>
            </div>

            <!-- Seamless Empty State (No Raw Table Header) -->
            <div v-else-if="filteredKatalogList.length === 0" class="p-5 text-center">
                <div class="d-inline-flex p-4 rounded-3xl bg-blue-50 text-blue-600 mb-3 shadow-2xs">
                    <i class="bi bi-book fs-1"></i>
                </div>
                <h5 class="fw-bold text-slate-800 mb-1">Belum Ada Koleksi Buku</h5>
                <p class="text-muted fs-7 mx-auto mb-4" style="max-width: 420px;">
                    Tidak ditemukan data buku yang sesuai dengan filter pencarian. Mulai tambahkan judul buku baru atau reset filter Anda.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button v-if="searchQuery || filterKategori || filterMedia" @click="resetFilterKatalog" class="btn btn-outline-secondary btn-sm rounded-xl px-3.5 py-2 fs-7 font-medium shadow-2xs">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                    </button>
                    <button @click="openModalTambahBuku" class="btn btn-primary btn-sm rounded-xl px-4 py-2 fs-7 font-medium shadow-2xs">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Judul Baru
                    </button>
                </div>
            </div>

            <!-- Table Presentation -->
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light bg-slate-50/80 text-slate-600 text-uppercase fs-8 fw-semibold border-bottom border-slate-200/80">
                        <tr>
                            <th class="ps-4 py-3" style="width: 5%;">No</th>
                            <th style="width: 32%;">Judul Buku & Metadata</th>
                            <th style="width: 18%;">Pengarang & Penerbit</th>
                            <th style="width: 14%;">ISBN & DDC</th>
                            <th style="width: 12%;" class="text-center">Eksemplar</th>
                            <th style="width: 8%;" class="text-center">Status</th>
                            <th class="text-center pe-4" style="width: 11%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                        <tr v-for="(item, idx) in paginatedKatalog" :key="item.id || idx" class="transition-colors hover:bg-slate-50/60">
                            <td class="ps-4 py-3 text-slate-400 font-mono fs-8">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td class="py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="flex-shrink-0 book-cover-thumb rounded-xl bg-blue-50 border border-blue-100 d-flex align-items-center justify-content-center text-primary shadow-2xs" style="width: 44px; height: 56px;">
                                        <img v-if="item.cover" :src="item.cover" class="w-100 h-100 object-fit-cover rounded-xl" alt="Cover">
                                        <i v-else class="bi bi-journal-text fs-4 text-primary"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h6 class="fw-bold text-slate-900 mb-0.5 text-truncate" style="max-width: 320px;" :title="item.judul">{{ item.judul }}</h6>
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <span class="badge bg-slate-100 text-slate-700 border border-slate-200/80 rounded-pill px-2 py-0.5 fs-9 fw-semibold">
                                                <i class="bi bi-tag-fill me-1 text-primary"></i>{{ item.kategori || item.jenis_buku || 'Umum' }}
                                            </span>
                                            <span v-if="item.is_ebook" class="badge bg-purple-50 text-purple-700 border border-purple-200 rounded-pill px-2 py-0.5 fs-9 fw-semibold">
                                                <i class="bi bi-file-earmark-pdf me-1"></i> E-Book
                                            </span>
                                            <span v-if="item.lokasi_rak || item.rak" class="badge bg-amber-50 text-amber-800 border border-amber-200 rounded-pill px-2 py-0.5 fs-9 fw-semibold">
                                                <i class="bi bi-layout-sidebar me-1"></i>{{ item.lokasi_rak || item.rak }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="fw-semibold text-slate-800 text-truncate" style="max-width: 180px;">{{ item.penulis || item.pengarang || '-' }}</div>
                                <div class="text-muted fs-8 text-truncate" style="max-width: 180px;">{{ item.penerbit || '-' }} ({{ item.tahun_terbit || '-' }})</div>
                            </td>
                            <td class="py-3">
                                <div class="font-mono fs-8 text-slate-700 fw-semibold">{{ item.isbn || '-' }}</div>
                                <span class="badge bg-sky-50 text-sky-700 border border-sky-200/80 rounded-md px-1.5 py-0.5 fs-9 fw-bold">
                                    DDC: {{ item.klasifikasi_ddc || '000' }}
                                </span>
                            </td>
                            <td class="py-3 text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <button @click="openModalEksemplar(item)" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-0.5 fs-8 fw-bold d-inline-flex align-items-center gap-1" title="Kelola Eksemplar Fisik">
                                        <i class="bi bi-upc-scan"></i>
                                        <span>{{ item.total_tersedia || item.total_eksemplar || 1 }} / {{ item.total_eksemplar || 1 }} Copy</span>
                                    </button>
                                    <small class="text-muted fs-9 mt-0.5">Klik untuk Barcode</small>
                                </div>
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="(item.total_tersedia || 1) > 0" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 fs-8 fw-semibold">
                                    <i class="bi bi-check-circle me-1"></i> Tersedia
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2 py-1 fs-8 fw-semibold">
                                    <i class="bi bi-hourglass-split me-1"></i> Dipinjam
                                </span>
                            </td>
                            <td class="py-3 text-center pe-4">
                                <!-- Unified Action Pill Group -->
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                    <button @click="editBuku(item)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-primary hover:bg-white transition-all p-1.5" title="Edit Data Buku">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button @click="openModalEksemplar(item)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-indigo hover:bg-white transition-all p-1.5" title="Daftar Eksemplar & Barcode">
                                        <i class="bi bi-upc"></i>
                                    </button>
                                    <a :href="'<?= $this->getBaseUrl() ?>/perpustakaan/cetak-label-thermal?barcode=' + encodeURIComponent(item.isbn || item.id)" target="_blank" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-dark hover:bg-white transition-all p-1.5" title="Cetak Label Thermal">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a v-if="item.is_ebook" href="<?= $this->getBaseUrl() ?>/perpustakaan/baca-ebook" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-primary hover:bg-white transition-all p-1.5" title="Baca E-Book">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button @click="konfirmasiHapusBuku(item)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-danger hover:bg-white transition-all p-1.5" title="Hapus Koleksi">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer & Pagination -->
            <div v-if="filteredKatalogList.length > 0" class="card-footer bg-white border-top border-slate-100 p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="text-muted fs-8 font-medium">
                        Menampilkan <span class="fw-bold text-slate-800">{{ (currentPage - 1) * perPage + 1 }}</span> sampai <span class="fw-bold text-slate-800">{{ Math.min(currentPage * perPage, filteredKatalogList.length) }}</span> dari <span class="fw-bold text-slate-800">{{ filteredKatalogList.length }}</span> judul buku
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm btn-outline-secondary rounded-lg px-2.5 py-1 text-xs" :disabled="currentPage === 1" @click="currentPage--">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="px-2 text-xs font-semibold text-slate-600">{{ currentPage }} / {{ totalPages || 1 }}</span>
                        <button class="btn btn-sm btn-outline-secondary rounded-lg px-2.5 py-1 text-xs" :disabled="currentPage >= totalPages" @click="currentPage++">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: LOKASI RAK BUKU -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'rak'" class="tab-pane-content transition-all">
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4">
                    <h6 class="fw-bold text-slate-800 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle-fill text-warning"></i>
                        <span>{{ formRak.id ? 'Edit Data Lokasi Rak' : 'Tambah Lokasi Rak Baru' }}</span>
                    </h6>
                    <form @submit.prevent="simpanRak">
                        <div class="mb-3">
                            <label for="rak_kode" class="form-label text-xs fw-bold text-slate-600">Kode Rak <span class="text-danger">*</span></label>
                            <input type="text" id="rak_kode" name="kode" v-model="formRak.kode" class="form-control rounded-xl text-xs py-2 border-slate-200 font-mono fw-bold" placeholder="Contoh: R-01" required>
                        </div>
                        <div class="mb-3">
                            <label for="rak_nama" class="form-label text-xs fw-bold text-slate-600">Nama / Kategori Rak <span class="text-danger">*</span></label>
                            <input type="text" id="rak_nama" name="nama" v-model="formRak.nama" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Contoh: Rak Buku IPA & Sains Terapan" required>
                        </div>
                        <div class="mb-3">
                            <label for="rak_ruangan" class="form-label text-xs fw-bold text-slate-600">Ruangan / Lantai</label>
                            <input type="text" id="rak_ruangan" name="ruangan" v-model="formRak.ruangan" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Contoh: Ruang Baca Utama Lantai 1">
                        </div>
                        <div class="mb-4">
                            <label for="rak_kapasitas" class="form-label text-xs fw-bold text-slate-600">Kapasitas Maksimal (Buku)</label>
                            <input type="number" id="rak_kapasitas" name="kapasitas" v-model="formRak.kapasitas" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="50" min="1">
                        </div>
                        <div class="d-flex gap-2">
                            <button v-if="formRak.id" type="button" @click="resetFormRak" class="btn btn-outline-secondary btn-sm rounded-xl px-3 py-2 w-50 fs-7 font-semibold">Batal</button>
                            <button type="submit" class="btn btn-warning btn-sm rounded-xl px-3 py-2 w-100 fs-7 font-bold shadow-2xs" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="bi bi-save me-1"></i> Simpan Rak
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
                    <div class="p-3 border-bottom border-slate-100 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-table text-warning"></i> Daftar Tata Letak Rak Fisik
                            </h6>
                            <small class="text-muted fs-8">Struktur penyimpanan fisik buku dalam perpustakaan</small>
                        </div>
                        <span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-1 fs-8 fw-semibold">
                            Total: {{ rakList.length }} Rak
                        </span>
                    </div>

                    <div v-if="rakList.length === 0" class="p-5 text-center">
                        <i class="bi bi-layout-sidebar fs-2 text-warning d-block mb-2"></i>
                        <p class="text-muted fs-7 mb-0">Belum ada lokasi rak yang terdaftar. Gunakan formulir di sebelah kiri untuk menambah rak baru.</p>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light bg-slate-50/80 text-slate-600 text-uppercase fs-8 fw-semibold border-bottom border-slate-200/80">
                                <tr>
                                    <th class="ps-4 py-3" style="width: 15%;">Kode Rak</th>
                                    <th style="width: 35%;">Nama & Deskripsi Rak</th>
                                    <th style="width: 25%;">Ruangan</th>
                                    <th style="width: 15%;" class="text-center">Kapasitas</th>
                                    <th class="text-center pe-4" style="width: 10%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                                <tr v-for="r in rakList" :key="r.id" class="transition-colors hover:bg-slate-50/60">
                                    <td class="ps-4 py-3">
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-md px-2 py-1 font-mono fw-bold">{{ r.kode }}</span>
                                    </td>
                                    <td class="fw-bold text-slate-800">{{ r.nama }}</td>
                                    <td>
                                        <span class="text-muted fs-8"><i class="bi bi-geo-alt me-1 text-slate-400"></i>{{ r.ruangan || 'Ruang Utama' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-0.5 font-semibold font-mono">{{ r.kapasitas || 50 }} Buku</span>
                                            <div class="progress mt-1.5" style="height: 4px; width: 60px;">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: 65%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                            <button @click="editRak(r)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-warning hover:bg-white transition-all p-1.5" title="Edit Rak">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button @click="konfirmasiHapusRak(r)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-danger hover:bg-white transition-all p-1.5" title="Hapus Rak">
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
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: KLASIFIKASI DDC -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'ddc'" class="tab-pane-content transition-all">
        <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h5 class="fw-bold text-slate-800 mb-0.5 d-flex align-items-center gap-2">
                        <i class="bi bi-diagram-3 text-info"></i> Indeks Dewey Decimal Classification (DDC)
                    </h5>
                    <p class="text-muted fs-7 mb-0">Standar klasifikasi desimal perpustakaan nasional & internasional untuk pengelompokan nomor panggil.</p>
                </div>
                <div class="position-relative" style="max-width: 280px;">
                    <label for="searchDdcInput" class="visually-hidden">Cari Klasifikasi DDC</label>
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-7"></i>
                    <input type="text" id="searchDdcInput" name="search_ddc" v-model="searchDdc"
                           class="form-control form-control-sm ps-5 rounded-xl border-slate-200 text-xs py-2 shadow-2xs"
                           placeholder="Cari Kode atau Subjek DDC..."
                           aria-label="Cari Kode atau Subjek DDC">
                </div>
            </div>

            <div class="row g-3">
                <div v-for="d in filteredDdc" :key="d.kode" class="col-12 col-md-6 col-lg-4">
                    <div class="p-3.5 rounded-2xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-all card-ddc-item">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-info text-white font-mono rounded-lg px-2.5 py-1 fs-8 fw-bold">{{ d.kode }}</span>
                                <span class="badge bg-slate-200/70 text-slate-700 rounded-md px-2 py-0.5 fs-9 fw-semibold">Tingkat {{ d.tingkat || 1 }}</span>
                            </div>
                            <button @click="copyDdcCode(d.kode)" class="btn btn-light btn-sm rounded-lg p-1 text-slate-400 hover:text-slate-700" title="Salin Kode DDC">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <h6 class="fw-bold text-slate-900 mb-1 fs-7">{{ d.nama }}</h6>
                        <small class="text-muted fs-9" v-if="d.keterangan">{{ d.keterangan }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: USULAN PENGADAAN BUKU -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'usulan'" class="tab-pane-content transition-all">
        <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
            <div class="p-3 border-bottom border-slate-100 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-lightbulb text-danger"></i> Rekomendasi Usulan Pengadaan Buku
                    </h5>
                    <small class="text-muted fs-8">Aspirasi pengadaan judul buku baru dari siswa, dewan guru, dan pustakawan.</small>
                </div>
                <button @click="openModalTambahUsulan" class="btn btn-primary btn-sm rounded-xl px-3 py-1.5 fs-7 font-semibold shadow-2xs d-flex align-items-center gap-1.5">
                    <i class="bi bi-plus-lg"></i> <span>Tambah Usulan Baru</span>
                </button>
            </div>

            <div v-if="usulanList.length === 0" class="p-5 text-center">
                <i class="bi bi-lightbulb fs-2 text-danger d-block mb-2"></i>
                <h6 class="fw-bold text-slate-800 mb-1">Belum Ada Usulan Buku Diajukan</h6>
                <p class="text-muted fs-7 mx-auto mb-3" style="max-width: 400px;">
                    Pengusul buku dari civitas akademika dapat menyampaikan rekomendasi judul buku di sini.
                </p>
                <button @click="openModalTambahUsulan" class="btn btn-primary btn-sm rounded-xl px-3.5 py-2 fs-7 font-medium shadow-2xs">
                    <i class="bi bi-plus-circle me-1"></i> Buat Usulan Pertama
                </button>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light bg-slate-50/80 text-slate-600 text-uppercase fs-8 fw-semibold border-bottom border-slate-200/80">
                        <tr>
                            <th class="ps-4 py-3" style="width: 5%;">No</th>
                            <th style="width: 32%;">Judul Buku Usulan</th>
                            <th style="width: 22%;">Pengarang / Penerbit</th>
                            <th style="width: 16%;">Pengusul</th>
                            <th style="width: 10%;">Tanggal</th>
                            <th style="width: 10%;" class="text-center">Status</th>
                            <th class="text-center pe-4" style="width: 5%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                        <tr v-for="(us, idx) in usulanList" :key="us.id" class="transition-colors hover:bg-slate-50/60">
                            <td class="ps-4 py-3 text-slate-400 font-mono fs-8">{{ idx + 1 }}</td>
                            <td class="py-3">
                                <div class="fw-bold text-slate-900">{{ us.judul }}</div>
                                <small v-if="us.keterangan" class="text-muted fs-9 text-truncate d-block" style="max-width: 280px;">Ket: {{ us.keterangan }}</small>
                            </td>
                            <td>{{ us.pengarang || '-' }} / {{ us.penerbit || '-' }}</td>
                            <td>
                                <span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-0.5 font-medium">
                                    <i class="bi bi-person me-1 text-slate-500"></i>{{ us.pengusul_nama }}
                                </span>
                            </td>
                            <td class="text-muted fs-8 font-mono">{{ us.tanggal_usulan }}</td>
                            <td class="text-center">
                                <span v-if="us.status === 'Disetujui'" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 fs-8 fw-semibold">
                                    <i class="bi bi-check2-circle me-1"></i> Disetujui
                                </span>
                                <span v-else-if="us.status === 'Ditolak'" class="badge bg-rose-50 text-rose-700 border border-rose-200 rounded-pill px-2.5 py-1 fs-8 fw-semibold">
                                    <i class="bi bi-x-circle me-1"></i> Ditolak
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2.5 py-1 fs-8 fw-semibold">
                                    <i class="bi bi-hourglass-split me-1"></i> Diajukan
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                    <button v-if="us.status !== 'Disetujui'" @click="updateStatusUsulan(us, 'Disetujui')" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-emerald-600 hover:bg-white transition-all p-1.5" title="Setujui Usulan">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button v-if="us.status !== 'Ditolak'" @click="updateStatusUsulan(us, 'Ditolak')" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-amber-600 hover:bg-white transition-all p-1.5" title="Tolak Usulan">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <button @click="konfirmasiHapusUsulan(us)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-danger hover:bg-white transition-all p-1.5" title="Hapus Usulan">
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

    <!-- ===================================================================== -->
    <!-- TAB 5: SERIAL & BERKALA -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'serial'" class="tab-pane-content transition-all">
        <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
            <div class="p-3 border-bottom border-slate-100 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-newspaper text-indigo"></i> Media Surat Kabar & Terbitan Berkala
                    </h5>
                    <small class="text-muted fs-8">Manajemen langganan koran harian, tabloid, buletin ilmiah, dan majalah berkala.</small>
                </div>
                <button @click="openModalTambahSerial" class="btn btn-primary btn-sm rounded-xl px-3 py-1.5 fs-7 font-semibold shadow-2xs d-flex align-items-center gap-1.5">
                    <i class="bi bi-plus-lg"></i> <span>Tambah Media Serial</span>
                </button>
            </div>

            <div v-if="serialList.length === 0" class="p-5 text-center">
                <i class="bi bi-newspaper fs-2 text-indigo d-block mb-2"></i>
                <h6 class="fw-bold text-slate-800 mb-1">Belum Ada Media Koran / Majalah Terdaftar</h6>
                <p class="text-muted fs-7 mx-auto mb-3" style="max-width: 400px;">
                    Daftarkan langganan koran harian atau majalah berkala yang rutin diterima oleh perpustakaan sekolah.
                </p>
                <button @click="openModalTambahSerial" class="btn btn-primary btn-sm rounded-xl px-3.5 py-2 fs-7 font-medium shadow-2xs">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Media Pertama
                </button>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light bg-slate-50/80 text-slate-600 text-uppercase fs-8 fw-semibold border-bottom border-slate-200/80">
                        <tr>
                            <th class="ps-4 py-3" style="width: 5%;">No</th>
                            <th style="width: 30%;">Nama Media / Terbitan</th>
                            <th style="width: 15%;">Jenis Media</th>
                            <th style="width: 15%;">Frekuensi Terbit</th>
                            <th style="width: 15%;">ISSN</th>
                            <th style="width: 10%;">Mulai Langganan</th>
                            <th style="width: 8%;" class="text-center">Status</th>
                            <th class="text-center pe-4" style="width: 7%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                        <tr v-for="(sr, idx) in serialList" :key="sr.id" class="transition-colors hover:bg-slate-50/60">
                            <td class="ps-4 py-3 text-slate-400 font-mono fs-8">{{ idx + 1 }}</td>
                            <td class="fw-bold text-slate-900 py-3">{{ sr.nama_media }}</td>
                            <td><span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-0.5 font-medium">{{ sr.jenis }}</span></td>
                            <td>{{ sr.frekuensi }}</td>
                            <td class="font-mono fs-8">{{ sr.issn || '-' }}</td>
                            <td class="text-muted fs-8 font-mono">{{ sr.tanggal_berlangganan }}</td>
                            <td class="text-center">
                                <span v-if="sr.status_aktif" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fs-8 fw-semibold">
                                    <i class="bi bi-check-circle me-1"></i> Aktif
                                </span>
                                <span v-else class="badge bg-slate-100 text-slate-600 rounded-pill px-2.5 py-1 fs-8 fw-semibold">Non-Aktif</span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                    <button @click="editSerial(sr)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-primary hover:bg-white transition-all p-1.5" title="Edit Media Serial">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button @click="konfirmasiHapusSerial(sr)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-danger hover:bg-white transition-all p-1.5" title="Hapus Media">
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

    <!-- ===================================================================== -->
    <!-- MODAL 1: TAMBAH / EDIT BIBLIOGRAFI BUKU -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalKatalogBuku" tabindex="-1" aria-labelledby="modalKatalogBukuTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="modalKatalogBukuTitle">
                        <i class="bi bi-book-fill text-primary"></i>
                        <span>{{ formBuku.id ? 'Edit Master Bibliografi Buku' : 'Tambah Judul Buku Baru' }}</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="simpanBuku">
                    <div class="modal-body p-4 bg-slate-50/50">
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label for="buku_judul" class="form-label text-xs fw-bold text-slate-700">Judul Buku <span class="text-danger">*</span></label>
                                <input type="text" id="buku_judul" name="judul" v-model="formBuku.judul" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Contoh: Algoritma dan Struktur Data" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="buku_klasifikasi_ddc" class="form-label text-xs fw-bold text-slate-700">Kode DDC</label>
                                <input type="text" id="buku_klasifikasi_ddc" name="klasifikasi_ddc" v-model="formBuku.klasifikasi_ddc" class="form-control rounded-xl text-xs py-2 border-slate-200 font-mono" placeholder="Contoh: 005.1">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="buku_penulis" class="form-label text-xs fw-bold text-slate-700">Pengarang / Penulis</label>
                                <input type="text" id="buku_penulis" name="penulis" v-model="formBuku.penulis" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Nama Lengkap Penulis">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="buku_penerbit" class="form-label text-xs fw-bold text-slate-700">Penerbit & Tahun Terbit</label>
                                <div class="input-group">
                                    <input type="text" id="buku_penerbit" name="penerbit" v-model="formBuku.penerbit" class="form-control rounded-start-xl text-xs py-2 border-slate-200" placeholder="Nama Penerbit" aria-label="Nama Penerbit">
                                    <input type="number" id="buku_tahun_terbit" name="tahun_terbit" v-model="formBuku.tahun_terbit" class="form-control rounded-end-xl text-xs py-2 border-slate-200 font-mono" placeholder="Tahun" aria-label="Tahun Terbit" style="max-width: 90px;">
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="buku_isbn" class="form-label text-xs fw-bold text-slate-700">ISBN / ISSN</label>
                                <input type="text" id="buku_isbn" name="isbn" v-model="formBuku.isbn" class="form-control rounded-xl text-xs py-2 border-slate-200 font-mono" placeholder="978-602-...">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="buku_kategori" class="form-label text-xs fw-bold text-slate-700">Kategori / Genre</label>
                                <select id="buku_kategori" name="kategori" v-model="formBuku.kategori" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                    <option value="Umum">Umum / Non-Fiksi</option>
                                    <option value="Fiksi">Fiksi / Sastra</option>
                                    <option value="Pelajaran">Paket Pelajaran</option>
                                    <option value="Referensi">Referensi</option>
                                    <option value="Majalah">Majalah / Serial</option>
                                    <option value="Agama">Agama & Moral</option>
                                    <option value="Teknologi">Teknologi & Sains</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="buku_is_ebook" class="form-label text-xs fw-bold text-slate-700">Tipe Format Media</label>
                                <select id="buku_is_ebook" name="is_ebook" v-model="formBuku.is_ebook" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                    <option :value="0">Buku Fisik</option>
                                    <option :value="1">E-Book Digital</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="buku_lokasi_rak" class="form-label text-xs fw-bold text-slate-700">Lokasi Rak Default</label>
                                <select id="buku_lokasi_rak" name="lokasi_rak" v-model="formBuku.lokasi_rak" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                    <option value="">-- Pilih Lokasi Rak --</option>
                                    <option v-for="r in rakList" :key="r.kode" :value="r.kode">{{ r.kode }} - {{ r.nama }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="buku_total_eksemplar" class="form-label text-xs fw-bold text-slate-700">Jumlah Eksemplar Awal</label>
                                <input type="number" id="buku_total_eksemplar" name="total_eksemplar" v-model="formBuku.total_eksemplar" class="form-control rounded-xl text-xs py-2 border-slate-200 font-mono" min="1" placeholder="1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top border-slate-100 p-3 px-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-xl px-4 py-2 text-xs font-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 py-2 text-xs font-bold shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-save me-1"></i> Simpan Bibliografi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 2: DETAIL & MANAJEMEN EKSEMPLAR PER BUKU -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalDetailEksemplar" tabindex="-1" aria-labelledby="modalDetailEksemplarTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <div>
                        <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="modalDetailEksemplarTitle">
                            <i class="bi bi-upc-scan text-primary"></i>
                            <span>Manajemen Eksemplar & Barcode Fisik</span>
                        </h5>
                        <small class="text-slate-400 fs-8">{{ selectedBuku?.judul || 'Buku Terpilih' }}</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-slate-50/50">
                    <!-- Add Eksemplar Quick Bar -->
                    <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3 mb-4">
                        <h6 class="fw-bold text-slate-800 fs-7 mb-2"><i class="bi bi-plus-circle-fill text-primary me-1"></i> Daftarkan Eksemplar Fisik Baru</h6>
                        <form @submit.prevent="simpanSingleEksemplar" class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label for="eks_barcode" class="form-label text-xs fw-bold text-slate-600">Barcode / No Inventaris</label>
                                <input type="text" id="eks_barcode" name="barcode" v-model="formEksemplar.barcode" class="form-control rounded-xl text-xs py-1.5 border-slate-200 font-mono" placeholder="Auto / Contoh: BK-001" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="eks_nomor_panggil" class="form-label text-xs fw-bold text-slate-600">Nomor Panggil (Call Number)</label>
                                <input type="text" id="eks_nomor_panggil" name="nomor_panggil" v-model="formEksemplar.nomor_panggil" class="form-control rounded-xl text-xs py-1.5 border-slate-200 font-mono" placeholder="005.1 ALG u c.1">
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="eks_kondisi" class="form-label text-xs fw-bold text-slate-600">Kondisi</label>
                                <select id="eks_kondisi" name="kondisi" v-model="formEksemplar.kondisi" class="form-select rounded-xl text-xs py-1.5 border-slate-200">
                                    <option value="Baik">Baik</option>
                                    <option value="Rusak Ringan">Rusak Ringan</option>
                                    <option value="Rusak Berat">Rusak Berat</option>
                                    <option value="Hilang">Hilang</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="eks_lokasi_rak" class="form-label text-xs fw-bold text-slate-600">Lokasi Rak</label>
                                <select id="eks_lokasi_rak" name="lokasi_rak" v-model="formEksemplar.lokasi_rak" class="form-select rounded-xl text-xs py-1.5 border-slate-200">
                                    <option value="">Default</option>
                                    <option v-for="r in rakList" :key="r.kode" :value="r.kode">{{ r.kode }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm rounded-xl py-1.5 px-3 w-100 fs-7 font-bold shadow-2xs" :disabled="saving">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Eksemplar Table -->
                    <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
                        <div class="p-3 border-bottom border-slate-100 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-slate-800 fs-7">Daftar Copy Fisik Terdaftar</span>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-1 fs-8 fw-bold">Total: {{ eksemplarList.length }} Eksemplar</span>
                        </div>

                        <div v-if="loadingEksemplar" class="p-4 text-center">
                            <div class="spinner-border spinner-border-sm text-primary mb-1"></div>
                            <p class="text-muted fs-8 mb-0">Memuat data eksemplar...</p>
                        </div>

                        <div v-else-if="eksemplarList.length === 0" class="p-4 text-center">
                            <p class="text-muted fs-8 mb-0">Belum ada rincian eksemplar fisik untuk judul buku ini.</p>
                        </div>

                        <div v-else class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light bg-slate-50 text-slate-600 text-uppercase fs-9 fw-semibold">
                                    <tr>
                                        <th class="ps-3 py-2">No</th>
                                        <th>Barcode / Register</th>
                                        <th>Nomor Panggil</th>
                                        <th>Lokasi Rak</th>
                                        <th>Kondisi</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700 fs-8">
                                    <tr v-for="(ex, i) in eksemplarList" :key="ex.id || i">
                                        <td class="ps-3 py-2 text-slate-400 font-mono">{{ i + 1 }}</td>
                                        <td class="fw-bold font-mono text-slate-800">{{ ex.barcode || ex.kode_eksemplar || ('EK-' + (i+1)) }}</td>
                                        <td class="font-mono">{{ ex.nomor_panggil || '-' }}</td>
                                        <td><span class="badge bg-amber-50 text-amber-800 border border-amber-200 rounded-md px-1.5 py-0.5">{{ ex.lokasi_rak || '-' }}</span></td>
                                        <td>
                                            <span class="badge bg-slate-100 text-slate-700 rounded-pill px-2 py-0.5">{{ ex.kondisi || 'Baik' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="ex.status === 'Tersedia' || !ex.status" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5">Tersedia</span>
                                            <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2 py-0.5">{{ ex.status }}</span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="d-inline-flex gap-1">
                                                <a :href="'<?= $this->getBaseUrl() ?>/perpustakaan/cetak-label-thermal?barcode=' + encodeURIComponent(ex.barcode || ex.kode_eksemplar || ex.id)" target="_blank" class="btn btn-sm btn-outline-secondary rounded-lg p-1" title="Cetak Barcode">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                                <button @click="hapusEksemplar(ex)" class="btn btn-sm btn-outline-danger rounded-lg p-1" title="Hapus Eksemplar">
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
                <div class="modal-footer bg-white border-top border-slate-100 p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-xl px-4 py-2 text-xs font-semibold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 3: TAMBAH USULAN PENGADAAN BUKU -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalTambahUsulan" tabindex="-1" aria-labelledby="modalTambahUsulanTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="modalTambahUsulanTitle">
                        <i class="bi bi-lightbulb-fill text-danger"></i>
                        <span>Tambah Usulan Pengadaan Buku</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="simpanUsulan">
                    <div class="modal-body p-4 bg-slate-50/50">
                        <div class="mb-3">
                            <label for="usulan_judul" class="form-label text-xs fw-bold text-slate-700">Judul Buku Usulan <span class="text-danger">*</span></label>
                            <input type="text" id="usulan_judul" name="judul" v-model="formUsulan.judul" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Contoh: Kamus Lengkap Bahasa Jepang" required>
                        </div>
                        <div class="mb-3">
                            <label for="usulan_pengarang" class="form-label text-xs fw-bold text-slate-700">Pengarang / Penulis</label>
                            <input type="text" id="usulan_pengarang" name="pengarang" v-model="formUsulan.pengarang" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Nama Penulis">
                        </div>
                        <div class="mb-3">
                            <label for="usulan_penerbit" class="form-label text-xs fw-bold text-slate-700">Penerbit</label>
                            <input type="text" id="usulan_penerbit" name="penerbit" v-model="formUsulan.penerbit" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Nama Penerbit">
                        </div>
                        <div class="mb-3">
                            <label for="usulan_pengusul_nama" class="form-label text-xs fw-bold text-slate-700">Nama Pengusul <span class="text-danger">*</span></label>
                            <input type="text" id="usulan_pengusul_nama" name="pengusul_nama" v-model="formUsulan.pengusul_nama" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Nama Siswa / Guru / Pustakawan" required>
                        </div>
                        <div class="mb-3">
                            <label for="usulan_keterangan" class="form-label text-xs fw-bold text-slate-700">Alasan / Keterangan Usulan</label>
                            <textarea id="usulan_keterangan" name="keterangan" v-model="formUsulan.keterangan" class="form-control rounded-xl text-xs py-2 border-slate-200" rows="2" placeholder="Diperlukan untuk referensi mata pelajaran..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top border-slate-100 p-3 px-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-xl px-4 py-2 text-xs font-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 py-2 text-xs font-bold shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-send me-1"></i> Kirim Usulan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 4: TAMBAH / EDIT SERIAL & TERBITAN BERKALA -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalTambahSerial" tabindex="-1" aria-labelledby="modalTambahSerialTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="modalTambahSerialTitle">
                        <i class="bi bi-newspaper text-indigo"></i>
                        <span>{{ formSerial.id ? 'Edit Data Terbitan Serial' : 'Tambah Media Terbitan Berkala' }}</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="simpanSerial">
                    <div class="modal-body p-4 bg-slate-50/50">
                        <div class="mb-3">
                            <label for="serial_nama_media" class="form-label text-xs fw-bold text-slate-700">Nama Media / Majalah <span class="text-danger">*</span></label>
                            <input type="text" id="serial_nama_media" name="nama_media" v-model="formSerial.nama_media" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Contoh: Kompas Harian / Majalah Tempo" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="serial_jenis" class="form-label text-xs fw-bold text-slate-700">Jenis Media</label>
                                <select id="serial_jenis" name="jenis" v-model="formSerial.jenis" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                    <option value="Koran">Koran Harian</option>
                                    <option value="Majalah">Majalah</option>
                                    <option value="Buletin">Buletin Ilmiah</option>
                                    <option value="Tabloid">Tabloid</option>
                                    <option value="Jurnal">Jurnal Akademik</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="serial_frekuensi" class="form-label text-xs fw-bold text-slate-700">Frekuensi</label>
                                <select id="serial_frekuensi" name="frekuensi" v-model="formSerial.frekuensi" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                    <option value="Harian">Harian</option>
                                    <option value="Mingguan">Mingguan</option>
                                    <option value="Bulanan">Bulanan</option>
                                    <option value="Dwi-Bulanan">Dwi-Bulanan</option>
                                    <option value="Tahunan">Tahunan</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="serial_issn" class="form-label text-xs fw-bold text-slate-700">ISSN</label>
                                <input type="text" id="serial_issn" name="issn" v-model="formSerial.issn" class="form-control rounded-xl text-xs py-2 border-slate-200 font-mono" placeholder="Contoh: 0215-2002">
                            </div>
                            <div class="col-6">
                                <label for="serial_tanggal_berlangganan" class="form-label text-xs fw-bold text-slate-700">Mulai Langganan</label>
                                <input type="date" id="serial_tanggal_berlangganan" name="tanggal_berlangganan" v-model="formSerial.tanggal_berlangganan" class="form-control rounded-xl text-xs py-2 border-slate-200">
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input cursor-pointer" type="checkbox" id="statusAktifSerial" name="status_aktif" v-model="formSerial.status_aktif">
                                <label class="form-check-label text-xs fw-semibold text-slate-700 cursor-pointer" for="statusAktifSerial">
                                    Status Langganan Aktif
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top border-slate-100 p-3 px-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-xl px-4 py-2 text-xs font-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 py-2 text-xs font-bold shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-save me-1"></i> Simpan Media
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Vue 3 In-DOM Instance for Katalog (Integrated with SINTA VueAppRegistry) -->
<script>
{
    const { ref, computed, onMounted } = Vue;

    const katalogAppConfig = {
        setup() {
            const activeTab = ref('katalog');
            const loading = ref(false);
            const saving = ref(false);
            const loadingEksemplar = ref(false);

            const katalogList = ref([]);
            const rakList = ref([]);
            const ddcList = ref([]);
            const usulanList = ref([]);
            const serialList = ref([]);
            const eksemplarList = ref([]);
            const selectedBuku = ref(null);

            const searchQuery = ref('');
            const filterKategori = ref('');
            const filterMedia = ref('');
            const searchDdc = ref('');

            const currentPage = ref(1);
            const perPage = ref(15);

            const formBuku = ref({
                id: null,
                judul: '',
                penulis: '',
                penerbit: '',
                tahun_terbit: new Date().getFullYear(),
                isbn: '',
                klasifikasi_ddc: '000',
                kategori: 'Umum',
                is_ebook: 0,
                lokasi_rak: '',
                total_eksemplar: 1
            });

            const formRak = ref({
                id: null,
                kode: '',
                nama: '',
                ruangan: '',
                kapasitas: 50
            });

            const formUsulan = ref({
                id: null,
                judul: '',
                pengarang: '',
                penerbit: '',
                pengusul_nama: '',
                keterangan: '',
                tanggal_usulan: new Date().toISOString().split('T')[0]
            });

            const formSerial = ref({
                id: null,
                nama_media: '',
                jenis: 'Koran',
                frekuensi: 'Harian',
                issn: '',
                tanggal_berlangganan: new Date().toISOString().split('T')[0],
                status_aktif: true
            });

            const formEksemplar = ref({
                barcode: '',
                nomor_panggil: '',
                kondisi: 'Baik',
                lokasi_rak: '',
                tenant_id: null
            });

            let modalBukuInstance = null;
            let modalEksemplarInstance = null;
            let modalUsulanInstance = null;
            let modalSerialInstance = null;

            // =================================================================
            // TENANT / SCHOOL ISOLATION HELPER (MULTI-SCHOOL SAAS)
            // =================================================================
            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($data['active_tenant_id'] ?? ($activeTenantId ?? '')) ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            // =================================================================
            // API DATA FETCHING
            // =================================================================

            const fetchKatalog = async () => {
                loading.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/katalog' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        katalogList.value = res.data.data.list || [];
                    }
                } catch (e) {
                    console.error('Error load katalog:', e);
                } finally {
                    loading.value = false;
                }
            };

            const fetchRak = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/rak' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        rakList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Error load rak:', e);
                }
            };

            const fetchDdc = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/ddc-categories' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        ddcList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Error load ddc:', e);
                }
            };

            const fetchUsulan = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/usulan' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        usulanList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Error load usulan:', e);
                }
            };

            const fetchSerial = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/serial' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        serialList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Error load serial:', e);
                }
            };

            const fetchEksemplarByBuku = async (bibliografiId) => {
                loadingEksemplar.value = true;
                try {
                    const res = await axios.get(`<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/eksemplar?bibliografi_id=${bibliografiId}` + getTenantParam('&'));
                    if (res.data && res.data.success) {
                        eksemplarList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Error load eksemplar:', e);
                } finally {
                    loadingEksemplar.value = false;
                }
            };

            // =================================================================
            // COMPUTED PROPERTIES FOR STATS & FILTERING
            // =================================================================

            const totalEksemplarCount = computed(() => {
                return katalogList.value.reduce((sum, item) => sum + parseInt(item.total_eksemplar || 1), 0);
            });

            const totalEbookCount = computed(() => {
                return katalogList.value.filter(item => item.is_ebook == 1).length;
            });

            const usulanPendingCount = computed(() => {
                return usulanList.value.filter(u => u.status === 'Diajukan' || !u.status).length;
            });

            const serialAktifCount = computed(() => {
                return serialList.value.filter(s => s.status_aktif === true || s.status_aktif == 1).length;
            });

            const filteredKatalogList = computed(() => {
                return katalogList.value.filter(b => {
                    const q = searchQuery.value.toLowerCase().trim();
                    const matchQ = !q || 
                        (b.judul && b.judul.toLowerCase().includes(q)) ||
                        (b.penulis && b.penulis.toLowerCase().includes(q)) ||
                        (b.pengarang && b.pengarang.toLowerCase().includes(q)) ||
                        (b.penerbit && b.penerbit.toLowerCase().includes(q)) ||
                        (b.isbn && b.isbn.toLowerCase().includes(q)) ||
                        (b.klasifikasi_ddc && b.klasifikasi_ddc.toLowerCase().includes(q));

                    const matchKat = !filterKategori.value || (b.kategori === filterKategori.value || b.jenis_buku === filterKategori.value);
                    const matchMedia = !filterMedia.value || (filterMedia.value === 'ebook' ? b.is_ebook == 1 : (b.is_ebook == 0 || !b.is_ebook));

                    return matchQ && matchKat && matchMedia;
                });
            });

            const totalPages = computed(() => {
                return Math.ceil(filteredKatalogList.value.length / perPage.value) || 1;
            });

            const paginatedKatalog = computed(() => {
                const start = (currentPage.value - 1) * perPage.value;
                return filteredKatalogList.value.slice(start, start + perPage.value);
            });

            const filteredDdc = computed(() => {
                if (!searchDdc.value) return ddcList.value;
                const q = searchDdc.value.toLowerCase().trim();
                return ddcList.value.filter(d => (d.kode && d.kode.includes(q)) || (d.nama && d.nama.toLowerCase().includes(q)));
            });

            // =================================================================
            // ACTIONS & MODAL HANDLERS
            // =================================================================

            const filterKatalog = () => {
                currentPage.value = 1;
            };

            const resetFilterKatalog = () => {
                searchQuery.value = '';
                filterKategori.value = '';
                filterMedia.value = '';
                currentPage.value = 1;
            };

            const copyDdcCode = (kode) => {
                navigator.clipboard.writeText(kode);
            };

            // Katalog Modals
            const openModalTambahBuku = () => {
                formBuku.value = {
                    id: null,
                    judul: '',
                    penulis: '',
                    penerbit: '',
                    tahun_terbit: new Date().getFullYear(),
                    isbn: '',
                    klasifikasi_ddc: '000',
                    kategori: 'Umum',
                    is_ebook: 0,
                    lokasi_rak: '',
                    total_eksemplar: 1,
                    tenant_id: currentTenantId
                };
                const el = document.getElementById('modalKatalogBuku');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    modalBukuInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalBukuInstance.show();
                }
            };

            const editBuku = (b) => {
                formBuku.value = {
                    id: b.id,
                    judul: b.judul,
                    penulis: b.penulis || b.pengarang || '',
                    penerbit: b.penerbit || '',
                    tahun_terbit: b.tahun_terbit || new Date().getFullYear(),
                    isbn: b.isbn || '',
                    klasifikasi_ddc: b.klasifikasi_ddc || '000',
                    kategori: b.kategori || b.jenis_buku || 'Umum',
                    is_ebook: b.is_ebook ? 1 : 0,
                    lokasi_rak: b.lokasi_rak || b.rak || '',
                    total_eksemplar: b.total_eksemplar || 1,
                    tenant_id: currentTenantId
                };
                const el = document.getElementById('modalKatalogBuku');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    modalBukuInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalBukuInstance.show();
                }
            };

            const simpanBuku = async () => {
                saving.value = true;
                try {
                    const payload = { ...formBuku.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/katalog/simpan' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        if (modalBukuInstance) modalBukuInstance.hide();
                        await fetchKatalog();
                    } else {
                        alert(res.data.error || 'Gagal menyimpan data buku.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan saat menyimpan data buku.');
                } finally {
                    saving.value = false;
                }
            };

            const konfirmasiHapusBuku = async (b) => {
                if (!confirm(`Hapus koleksi "${b.judul}"? Tindakan ini tidak dapat dibatalkan.`)) return;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/katalog/hapus' + getTenantParam('?'), { id: b.id, tenant_id: currentTenantId });
                    if (res.data && res.data.success) {
                        await fetchKatalog();
                    }
                } catch (e) {
                    alert('Gagal menghapus buku.');
                }
            };

            // Rak Handlers
            const simpanRak = async () => {
                saving.value = true;
                try {
                    const payload = { ...formRak.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/rak' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        resetFormRak();
                        await fetchRak();
                    }
                } catch (e) {
                    alert('Gagal menyimpan lokasi rak.');
                } finally {
                    saving.value = false;
                }
            };

            const editRak = (r) => {
                formRak.value = { ...r };
            };

            const resetFormRak = () => {
                formRak.value = { id: null, kode: '', nama: '', ruangan: '', kapasitas: 50 };
            };

            const konfirmasiHapusRak = async (r) => {
                if (!confirm(`Hapus lokasi rak "${r.kode} - ${r.nama}"?`)) return;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/rak/hapus' + getTenantParam('?'), { id: r.id, tenant_id: currentTenantId });
                    if (res.data && res.data.success) {
                        await fetchRak();
                    }
                } catch (e) {
                    alert('Gagal menghapus lokasi rak.');
                }
            };

            // Eksemplar Handlers
            const openModalEksemplar = (buku) => {
                selectedBuku.value = buku;
                formEksemplar.value = {
                    barcode: (buku.isbn ? buku.isbn + '-01' : 'BK-' + Date.now().toString().slice(-4)),
                    nomor_panggil: (buku.klasifikasi_ddc || '000') + ' ' + (buku.penulis ? buku.penulis.slice(0, 3).toUpperCase() : 'BUK') + ' ' + (buku.judul ? buku.judul.slice(0, 1).toLowerCase() : 'b'),
                    kondisi: 'Baik',
                    lokasi_rak: buku.lokasi_rak || '',
                    tenant_id: currentTenantId
                };
                fetchEksemplarByBuku(buku.id);
                const el = document.getElementById('modalDetailEksemplar');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    modalEksemplarInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalEksemplarInstance.show();
                }
            };

            const simpanSingleEksemplar = async () => {
                if (!selectedBuku.value) return;
                saving.value = true;
                try {
                    const payload = {
                        bibliografi_id: selectedBuku.value.id,
                        barcode: formEksemplar.value.barcode,
                        nomor_panggil: formEksemplar.value.nomor_panggil,
                        kondisi: formEksemplar.value.kondisi,
                        lokasi_rak: formEksemplar.value.lokasi_rak,
                        status: 'Tersedia',
                        tenant_id: currentTenantId
                    };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/eksemplar/simpan' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        formEksemplar.value.barcode = 'BK-' + Date.now().toString().slice(-4);
                        await fetchEksemplarByBuku(selectedBuku.value.id);
                        await fetchKatalog();
                    }
                } catch (e) {
                    alert('Gagal menyimpan data eksemplar.');
                } finally {
                    saving.value = false;
                }
            };

            const hapusEksemplar = async (ex) => {
                if (!confirm(`Hapus eksemplar barcode "${ex.barcode || ex.kode_eksemplar}"?`)) return;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/eksemplar/hapus' + getTenantParam('?'), { id: ex.id, tenant_id: currentTenantId });
                    if (res.data && res.data.success) {
                        if (selectedBuku.value) await fetchEksemplarByBuku(selectedBuku.value.id);
                        await fetchKatalog();
                    }
                } catch (e) {
                    alert('Gagal menghapus eksemplar.');
                }
            };

            // Usulan Handlers
            const openModalTambahUsulan = () => {
                formUsulan.value = {
                    id: null,
                    judul: '',
                    pengarang: '',
                    penerbit: '',
                    pengusul_nama: '',
                    keterangan: '',
                    tanggal_usulan: new Date().toISOString().split('T')[0],
                    tenant_id: currentTenantId
                };
                const el = document.getElementById('modalTambahUsulan');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    modalUsulanInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalUsulanInstance.show();
                }
            };

            const simpanUsulan = async () => {
                saving.value = true;
                try {
                    const payload = { ...formUsulan.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/usulan' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        if (modalUsulanInstance) modalUsulanInstance.hide();
                        await fetchUsulan();
                    } else {
                        alert(res.data.error || 'Gagal mengirim usulan buku.');
                    }
                } catch (e) {
                    alert('Gagal mengirim usulan buku.');
                } finally {
                    saving.value = false;
                }
            };

            const updateStatusUsulan = async (us, status) => {
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/usulan/status' + getTenantParam('?'), {
                        id: us.id,
                        status: status,
                        tenant_id: currentTenantId
                    });
                    if (res.data && res.data.success) {
                        await fetchUsulan();
                    }
                } catch (e) {
                    alert('Gagal memperbarui status usulan.');
                }
            };

            const konfirmasiHapusUsulan = async (us) => {
                if (!confirm(`Hapus usulan buku "${us.judul}"?`)) return;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/usulan/hapus' + getTenantParam('?'), { id: us.id, tenant_id: currentTenantId });
                    if (res.data && res.data.success) {
                        await fetchUsulan();
                    }
                } catch (e) {
                    alert('Gagal menghapus usulan.');
                }
            };

            // Serial Handlers
            const openModalTambahSerial = () => {
                formSerial.value = {
                    id: null,
                    nama_media: '',
                    jenis: 'Koran',
                    frekuensi: 'Harian',
                    issn: '',
                    tanggal_berlangganan: new Date().toISOString().split('T')[0],
                    status_aktif: true,
                    tenant_id: currentTenantId
                };
                const el = document.getElementById('modalTambahSerial');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    modalSerialInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalSerialInstance.show();
                }
            };

            const editSerial = (sr) => {
                formSerial.value = {
                    id: sr.id,
                    nama_media: sr.nama_media,
                    jenis: sr.jenis || 'Koran',
                    frekuensi: sr.frekuensi || 'Harian',
                    issn: sr.issn || '',
                    tanggal_berlangganan: sr.tanggal_berlangganan || new Date().toISOString().split('T')[0],
                    status_aktif: sr.status_aktif === true || sr.status_aktif == 1,
                    tenant_id: currentTenantId
                };
                const el = document.getElementById('modalTambahSerial');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    modalSerialInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalSerialInstance.show();
                }
            };

            const simpanSerial = async () => {
                saving.value = true;
                try {
                    const payload = { ...formSerial.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/serial' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        if (modalSerialInstance) modalSerialInstance.hide();
                        await fetchSerial();
                    }
                } catch (e) {
                    alert('Gagal menyimpan media serial.');
                } finally {
                    saving.value = false;
                }
            };

            const konfirmasiHapusSerial = async (sr) => {
                if (!confirm(`Hapus langganan media serial "${sr.nama_media}"?`)) return;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/serial/hapus' + getTenantParam('?'), { id: sr.id, tenant_id: currentTenantId });
                    if (res.data && res.data.success) {
                        await fetchSerial();
                    }
                } catch (e) {
                    alert('Gagal menghapus serial.');
                }
            };

            const refreshCurrentTab = () => {
                if (activeTab.value === 'katalog') fetchKatalog();
                if (activeTab.value === 'rak') fetchRak();
                if (activeTab.value === 'ddc') fetchDdc();
                if (activeTab.value === 'usulan') fetchUsulan();
                if (activeTab.value === 'serial') fetchSerial();
            };

            onMounted(() => {
                fetchKatalog();
                fetchRak();
                fetchDdc();
                fetchUsulan();
                fetchSerial();
            });

            return {
                activeTab,
                loading,
                saving,
                loadingEksemplar,
                katalogList,
                rakList,
                ddcList,
                usulanList,
                serialList,
                eksemplarList,
                selectedBuku,
                searchQuery,
                filterKategori,
                filterMedia,
                searchDdc,
                currentPage,
                perPage,
                totalPages,
                totalEksemplarCount,
                totalEbookCount,
                usulanPendingCount,
                serialAktifCount,
                filteredKatalogList,
                paginatedKatalog,
                filteredDdc,
                formBuku,
                formRak,
                formUsulan,
                formSerial,
                formEksemplar,
                filterKatalog,
                resetFilterKatalog,
                copyDdcCode,
                openModalTambahBuku,
                editBuku,
                simpanBuku,
                konfirmasiHapusBuku,
                simpanRak,
                editRak,
                resetFormRak,
                konfirmasiHapusRak,
                openModalEksemplar,
                simpanSingleEksemplar,
                hapusEksemplar,
                openModalTambahUsulan,
                simpanUsulan,
                updateStatusUsulan,
                konfirmasiHapusUsulan,
                openModalTambahSerial,
                editSerial,
                simpanSerial,
                konfirmasiHapusSerial,
                refreshCurrentTab
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#katalogPerpusApp', katalogAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(katalogAppConfig).mount('#katalogPerpusApp');
        });
    }
}
</script>

<style>
/* Modern Pill Navtabs (Gambar 1 Standard) */
.custom-modern-pills {
    display: flex;
    flex-wrap: nowrap;
    gap: 6px;
    background: transparent;
    padding: 2px;
}
.custom-modern-pills .nav-link {
    border-radius: 12px !important;
    padding: 8px 18px !important;
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    color: #475569 !important;
    background-color: transparent !important;
    border: none !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}
.custom-modern-pills .nav-link:hover:not(.active) {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
}
.custom-modern-pills .nav-link.active {
    background-color: #2563eb !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25) !important;
}
.custom-modern-pills .nav-link.active i {
    color: #ffffff !important;
}
.card-ddc-item:hover {
    border-color: #cbd5e1 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
}
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
[v-cloak] { display: none !important; }
</style>
