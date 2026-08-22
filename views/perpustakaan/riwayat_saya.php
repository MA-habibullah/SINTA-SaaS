<?php
/**
 * View: Riwayat Peminjaman Pustaka Siswa & Guru (Riwayat Saya)
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
?>
<div id="riwayatSayaApp" v-cloak class="container-fluid px-0">
    <!-- ═══════════════════════════════════════════════════════════════════════
         HERO BANNER & MULTI-TENANT SAAS SWITCHER
         ═══════════════════════════════════════════════════════════════════════ -->
    <?php
    $heroIcon = 'bi-clock-history';
    $heroBadge = 'Koleksi & Sirkulasi Pustaka';
    $heroTitle = 'Riwayat Peminjaman Saya';
    $heroDesc = 'Histori peminjaman buku perpustakaan digital, pemantauan tenggat waktu pengembalian, dan rincian denda keterlambatan.';
    $heroButtons = '
        <a href="' . $this->getBaseUrl() . '/perpustakaan/opac" target="_blank" class="btn btn-sm rounded-xl px-3.5 py-2 text-xs font-bold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center gap-1.5 backdrop-blur-md">
            <i class="bi bi-book-half"></i> Buka Katalog OPAC
        </a>
    ';
    include __DIR__ . '/_tenant_filter.php';
    ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         HORIZONTAL NAVTABS SCROLLER (STANDAR AGENTS.MD)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-2 mb-4 position-relative">
        <div class="d-flex align-items-center position-relative">
            <!-- Left Scroll Arrow Button -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('riwayatSayaNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                    title="Geser ke Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Container Deretan Tab -->
            <div class="nav-tabs-wrapper flex-grow-1 overflow-hidden position-relative">
                <ul class="nav nav-pills border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-1.5 px-1 user-select-none" id="riwayatSayaNavTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'all'}" 
                                @click="switchTab('all')">
                            <i class="bi bi-grid me-2 fs-6"></i> Semua Riwayat
                            <span class="badge bg-slate-200 text-slate-700 ms-2 rounded-pill text-[11px]" :class="{'!bg-blue-100 !text-blue-700': activeTab === 'all'}">{{ listRiwayat.length }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'dipinjam'}" 
                                @click="switchTab('dipinjam')">
                            <i class="bi bi-book-half me-2 fs-6 text-amber-500"></i> Sedang Dipinjam
                            <span class="badge bg-amber-100 text-amber-800 ms-2 rounded-pill text-[11px] font-bold">{{ pinjamanAktifCount }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'kembali'}" 
                                @click="switchTab('kembali')">
                            <i class="bi bi-check2-circle me-2 fs-6 text-emerald-500"></i> Sudah Kembali
                            <span class="badge bg-emerald-100 text-emerald-800 ms-2 rounded-pill text-[11px]">{{ kembaliCount }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition d-inline-flex align-items-center" 
                                :class="{'active': activeTab === 'terlambat'}" 
                                @click="switchTab('terlambat')">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-6 text-rose-500"></i> Terlambat
                            <span v-if="terlambatCount > 0" class="badge bg-rose-500 text-white ms-2 rounded-pill text-[11px] font-bold">{{ terlambatCount }}</span>
                            <span v-else class="badge bg-slate-100 text-slate-500 ms-2 rounded-pill text-[11px]">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Right Scroll Arrow Button -->
            <button type="button" 
                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 d-none d-md-flex align-items-center justify-content-center flex-shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" 
                    style="width: 34px; height: 34px; z-index: 5;" 
                    onclick="document.getElementById('riwayatSayaNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                    title="Geser ke Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Tombol Segarkan Data -->
            <div class="d-none d-md-flex align-items-center ps-2 pe-1 border-s border-slate-200/80 ms-2">
                <button type="button" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl px-3 py-2 text-xs font-bold shadow-2xs d-flex align-items-center gap-1.5" @click="fetchRiwayat()" title="Segarkan Data">
                    <i class="bi bi-arrow-clockwise" :class="{'spin': loading}"></i>
                    <span>Segarkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         METRIC STATS OVERVIEW CARDS (MODERN GLASSMORPHISM & HOVER GLOW)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Dipinjam -->
        <div class="col-6 col-lg-3">
            <div class="card border border-slate-200/80 shadow-xs rounded-2xl bg-white p-3.5 h-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-md position-relative overflow-hidden cursor-pointer" @click="switchTab('all')">
                <div class="position-absolute end-0 top-0 w-24 h-24 bg-blue-50/50 rounded-full blur-xl pointer-events-none"></div>
                <div class="d-flex align-items-center justify-content-between mb-2 position-relative">
                    <span class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Dipinjam</span>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center fs-6 shadow-2xs">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1 position-relative">
                    <div class="h3 font-black text-slate-800 mb-0">{{ listRiwayat.length }}</div>
                    <span class="text-xs font-semibold text-slate-400">transaksi</span>
                </div>
                <small class="text-slate-400 text-[11px] d-block position-relative">Semua histori peminjaman pustaka</small>
            </div>
        </div>

        <!-- Card 2: Sedang Dipinjam -->
        <div class="col-6 col-lg-3">
            <div class="card border border-amber-200/60 shadow-xs rounded-2xl bg-gradient-to-br from-white to-amber-50/30 p-3.5 h-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-md position-relative overflow-hidden cursor-pointer" @click="switchTab('dipinjam')">
                <div class="position-absolute end-0 top-0 w-24 h-24 bg-amber-100/50 rounded-full blur-xl pointer-events-none"></div>
                <div class="d-flex align-items-center justify-content-between mb-2 position-relative">
                    <span class="text-amber-800 text-xs font-bold uppercase tracking-wider">Sedang Dipinjam</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 d-flex align-items-center justify-content-center fs-6 shadow-2xs">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1 position-relative">
                    <div class="h3 font-black text-amber-600 mb-0">{{ pinjamanAktifCount }}</div>
                    <span class="text-xs font-semibold text-amber-700/70">eksemplar</span>
                </div>
                <small class="text-amber-700/70 text-[11px] d-block position-relative">Buku aktif di tangan Anda</small>
            </div>
        </div>

        <!-- Card 3: Sudah Kembali -->
        <div class="col-6 col-lg-3">
            <div class="card border border-emerald-200/60 shadow-xs rounded-2xl bg-gradient-to-br from-white to-emerald-50/30 p-3.5 h-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-md position-relative overflow-hidden cursor-pointer" @click="switchTab('kembali')">
                <div class="position-absolute end-0 top-0 w-24 h-24 bg-emerald-100/50 rounded-full blur-xl pointer-events-none"></div>
                <div class="d-flex align-items-center justify-content-between mb-2 position-relative">
                    <span class="text-emerald-800 text-xs font-bold uppercase tracking-wider">Sudah Kembali</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 d-flex align-items-center justify-content-center fs-6 shadow-2xs">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1 position-relative">
                    <div class="h3 font-black text-emerald-600 mb-0">{{ kembaliCount }}</div>
                    <span class="text-xs font-semibold text-emerald-700/70">buku</span>
                </div>
                <small class="text-emerald-700/70 text-[11px] d-block position-relative">Selesai dikembalikan ke pustakawan</small>
            </div>
        </div>

        <!-- Card 4: Denda Keterlambatan -->
        <div class="col-6 col-lg-3">
            <div class="card border border-rose-200/60 shadow-xs rounded-2xl bg-gradient-to-br from-white to-rose-50/30 p-3.5 h-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-md position-relative overflow-hidden cursor-pointer" @click="switchTab('terlambat')">
                <div class="position-absolute end-0 top-0 w-24 h-24 bg-rose-100/50 rounded-full blur-xl pointer-events-none"></div>
                <div class="d-flex align-items-center justify-content-between mb-2 position-relative">
                    <span class="text-rose-800 text-xs font-bold uppercase tracking-wider">Denda Terlambat</span>
                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 d-flex align-items-center justify-content-center fs-6 shadow-2xs">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1 position-relative">
                    <div class="h3 font-black text-rose-600 mb-0">Rp {{ formatRupiah(totalDenda) }}</div>
                </div>
                <small class="text-rose-700/70 text-[11px] d-block position-relative">{{ terlambatCount > 0 ? terlambatCount + ' buku melewati batas tenggat' : 'Tidak ada tunggakan denda' }}</small>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MAIN DATA TABLE & FILTER TOOLBAR
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card border border-slate-200/80 shadow-xs rounded-2xl bg-white overflow-hidden mb-4">
        <!-- Toolbar Filter Header -->
        <div class="px-4 py-3.5 border-b border-slate-200/80 bg-slate-50/70">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap align-items-center gap-2.5 flex-grow-1">
                    <!-- Search Input Box -->
                    <div class="position-relative flex-grow-1" style="max-width: 380px; min-width: 240px;">
                        <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-slate-400 fs-7 pointer-events-none"></i>
                        <input type="text" 
                               v-model="searchQuery" 
                               class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-semibold bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 shadow-2xs transition" 
                               placeholder="Cari judul buku, barcode, pengarang..." 
                               aria-label="Cari riwayat buku">
                        <button v-if="searchQuery" 
                                type="button" 
                                class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 p-0 border-0 bg-transparent" 
                                @click="searchQuery = ''" 
                                aria-label="Reset pencarian">
                            <i class="bi bi-x-circle-fill fs-7"></i>
                        </button>
                    </div>

                    <!-- Filter Status Dropdown -->
                    <select v-model="filterStatus" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" style="width: auto; min-width: 170px;" aria-label="Filter Status">
                        <option value="">— Semua Status —</option>
                        <option value="Dipinjam">Sedang Dipinjam</option>
                        <option value="Kembali">Sudah Dikembalikan</option>
                    </select>

                    <!-- Filter Urutan Tanggal -->
                    <select v-model="sortOrder" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" style="width: auto; min-width: 160px;" aria-label="Urutkan">
                        <option value="desc">Terbaru ke Terlama</option>
                        <option value="asc">Terlama ke Terbaru</option>
                    </select>
                </div>

                <!-- Action Buttons & Badges -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="text-xs text-slate-500 font-semibold me-1 d-none d-sm-inline">
                        Ditemukan: <strong class="text-slate-800 font-bold">{{ filteredList.length }}</strong> data
                    </span>
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl font-bold px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 d-inline-flex align-items-center gap-1.5 shadow-2xs transition" @click="resetFilter()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-inline-flex align-items-center gap-1.5 shadow-2xs transition" @click="fetchRiwayat()" title="Segarkan Data">
                        <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> Segarkan
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Content Area -->
        <div class="p-0">
            <!-- Loading Skeleton Indicator -->
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border text-primary me-2 mb-2" role="status" style="width: 2rem; height: 2rem;"></div>
                <div class="font-semibold text-slate-600">Memuat riwayat peminjaman buku...</div>
                <small class="text-slate-400">Sinkronisasi data sirkulasi perpustakaan</small>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredList.length === 0" class="text-center py-5 px-3">
                <div class="w-16 h-16 rounded-2xl bg-blue-50/80 text-blue-500 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-inner">
                    <i class="bi bi-journal-bookmark"></i>
                </div>
                <div class="font-bold text-slate-800 text-base mb-1">Belum Ada Riwayat Pinjaman</div>
                <p class="text-slate-500 text-xs mb-4" style="max-width: 460px; margin: 0 auto; line-height: 1.6;">
                    Tidak ditemukan data transaksi peminjaman buku yang sesuai dengan filter pencarian aktif. Kunjungi katalog OPAC digital untuk mencari koleksi buku yang tersedia di perpustakaan.
                </p>
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <button v-if="searchQuery || filterStatus || activeTab !== 'all'" type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl font-bold px-3 py-2 text-xs text-slate-700 shadow-2xs" @click="resetFilter(); switchTab('all');">
                        <i class="bi bi-arrow-clockwise me-1"></i> Tampilkan Semua
                    </button>
                    <a href="<?= $this->getBaseUrl() ?>/perpustakaan/opac" target="_blank" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 text-xs shadow-2xs text-decoration-none d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-search"></i> Jelajahi Buku di OPAC
                    </a>
                </div>
            </div>

            <!-- Data Table -->
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle text-xs mb-0">
                    <thead class="bg-slate-50/90 border-b border-slate-200/80 text-slate-500 font-bold uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="ps-4 py-3 text-center" style="width: 50px;">No</th>
                            <th class="py-3" style="min-width: 280px;">Informasi Buku & Koleksi</th>
                            <th class="py-3" style="min-width: 140px;">Barcode Eksemplar</th>
                            <th class="py-3 text-center" style="min-width: 130px;">Tgl Pinjam</th>
                            <th class="py-3 text-center" style="min-width: 140px;">Tenggat Kembali</th>
                            <th class="py-3 text-center" style="min-width: 140px;">Tgl Pengembalian</th>
                            <th class="py-3 text-center" style="min-width: 120px;">Status</th>
                            <th class="py-3 text-end" style="min-width: 110px;">Denda</th>
                            <th class="py-3 text-center pe-4" style="width: 90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, idx) in paginatedList" :key="item.id || idx" class="hover:bg-blue-50/30 transition">
                            <!-- No -->
                            <td class="ps-4 py-3 text-center font-bold text-slate-400">
                                {{ (currentPage - 1) * perPage + idx + 1 }}
                            </td>

                            <!-- Judul & Info Buku -->
                            <td class="py-3">
                                <div class="d-flex align-items-start gap-2.5">
                                    <div class="w-8 h-10 rounded-lg bg-blue-50 text-blue-600 border border-blue-200/60 d-flex align-items-center justify-content-center flex-shrink-0 shadow-2xs fs-6 mt-0.5">
                                        <i class="bi bi-book"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 fs-7 leading-snug hover:text-blue-600 transition cursor-pointer" @click="openDetail(item)">
                                            {{ item.judul_buku || item.nama_perpus_sirkulasi || 'Judul Buku Tidak Diketahui' }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5 d-flex flex-wrap align-items-center gap-2">
                                            <span v-if="item.pengarang"><i class="bi bi-person me-1"></i>{{ item.pengarang }}</span>
                                            <span v-if="item.penerbit"><i class="bi bi-building me-1"></i>{{ item.penerbit }}</span>
                                            <span v-if="item.tahun_terbit" class="badge bg-slate-100 text-slate-600 border border-slate-200 rounded px-1.5 py-0 text-[10px]">{{ item.tahun_terbit }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Barcode -->
                            <td class="py-3">
                                <span class="badge bg-slate-100 text-slate-700 border border-slate-200 font-mono text-[11px] px-2 py-1 rounded-lg d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-upc-scan text-slate-400"></i> {{ item.barcode || item.eksemplar_id || '-' }}
                                </span>
                            </td>

                            <!-- Tgl Pinjam -->
                            <td class="py-3 text-center text-slate-600 font-medium">
                                <div class="font-semibold text-slate-700">{{ formatDateIndo(item.tanggal_pinjam || item.created_at) }}</div>
                                <small class="text-[10px] text-slate-400">{{ formatRelativeTime(item.tanggal_pinjam || item.created_at) }}</small>
                            </td>

                            <!-- Tenggat Kembali -->
                            <td class="py-3 text-center">
                                <span class="badge font-bold px-2.5 py-1 rounded-lg text-[10px] d-inline-flex align-items-center gap-1"
                                      :class="isOverdue(item) ? 'bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs' : 'bg-slate-100 text-slate-700 border border-slate-200'">
                                    <i class="bi" :class="isOverdue(item) ? 'bi-exclamation-triangle-fill text-rose-600' : 'bi-calendar-event text-slate-500'"></i>
                                    {{ formatDateIndo(item.tanggal_harus_kembali) }}
                                </span>
                                <div v-if="isOverdue(item)" class="text-[10px] text-rose-600 font-bold mt-0.5">
                                    {{ getOverdueDays(item) }} Hari Lewat
                                </div>
                            </td>

                            <!-- Tgl Dikembalikan -->
                            <td class="py-3 text-center">
                                <span v-if="item.tanggal_kembali" class="text-emerald-700 font-bold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-check-circle-fill text-emerald-500"></i> {{ formatDateIndo(item.tanggal_kembali) }}
                                </span>
                                <span v-else class="text-slate-400 italic text-[11px] d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-hourglass-split text-amber-500"></i> Belum Kembali
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3 text-center">
                                <span v-if="item.kategori === 'Kembali' || item.status === 'Kembali'" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2.5 py-1 rounded-pill text-[10px] d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-check2"></i> Selesai
                                </span>
                                <span v-else-if="isOverdue(item)" class="badge bg-rose-100 text-rose-800 border border-rose-300 font-bold px-2.5 py-1 rounded-pill text-[10px] d-inline-flex align-items-center gap-1 animate-pulse">
                                    <i class="bi bi-clock-history"></i> Terlambat
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 font-bold px-2.5 py-1 rounded-pill text-[10px] d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-book-half"></i> Dipinjam
                                </span>
                            </td>

                            <!-- Denda -->
                            <td class="py-3 text-end font-black" :class="parseFloat(item.denda || 0) > 0 ? 'text-rose-600' : 'text-slate-400'">
                                {{ parseFloat(item.denda || 0) > 0 ? 'Rp ' + formatRupiah(item.denda) : 'Rp 0' }}
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 text-center pe-4">
                                <button type="button" class="btn btn-xs btn-light border border-slate-200 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg px-2.5 py-1 shadow-2xs transition font-bold" @click="openDetail(item)" title="Lihat Detail Transaksi">
                                    <i class="bi bi-eye me-1"></i> Detail
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="filteredList.length > perPage" class="px-4 py-3 border-t border-slate-200/80 bg-slate-50/50 d-flex align-items-center justify-content-between flex-wrap gap-2 text-xs text-slate-500">
                <span>Menampilkan <strong>{{ (currentPage - 1) * perPage + 1 }}</strong> s/d <strong>{{ Math.min(currentPage * perPage, filteredList.length) }}</strong> dari <strong>{{ filteredList.length }}</strong> transaksi</span>
                <div class="d-flex align-items-center gap-1.5">
                    <button type="button" class="btn btn-xs btn-light border border-slate-200 rounded-lg px-2.5 py-1 shadow-2xs font-semibold" :disabled="currentPage === 1" @click="currentPage--">
                        <i class="bi bi-chevron-left me-1"></i> Sebelumnya
                    </button>
                    <span class="px-2 font-bold text-slate-700">{{ currentPage }} / {{ totalPages }}</span>
                    <button type="button" class="btn btn-xs btn-light border border-slate-200 rounded-lg px-2.5 py-1 shadow-2xs font-semibold" :disabled="currentPage === totalPages" @click="currentPage++">
                        Berikutnya <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL DETAIL TRANSAKSI SIRKULASI (DIGITAL LOAN SLIP)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalDetailSirkulasi" tabindex="-1" aria-labelledby="modalDetailSirkulasiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg rounded-2xl overflow-hidden" v-if="selectedItem">
                <!-- Modal Header -->
                <div class="modal-header bg-slate-900 text-white border-0 py-3.5 px-4 position-relative">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-500/20 text-blue-400 d-flex align-items-center justify-content-center fs-5">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div>
                            <h6 class="modal-title font-bold mb-0 text-white" id="modalDetailSirkulasiLabel">Rincian Slip Peminjaman</h6>
                            <small class="text-slate-400 text-[11px]">No. Barcode: {{ selectedItem.barcode || selectedItem.eksemplar_id || '-' }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4 bg-slate-50/50">
                    <!-- Book Info Card -->
                    <div class="card border border-slate-200/80 shadow-2xs rounded-xl p-3.5 bg-white mb-3">
                        <div class="d-flex gap-3">
                            <div class="w-12 h-16 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 d-flex align-items-center justify-content-center fs-4 flex-shrink-0 shadow-2xs">
                                <i class="bi bi-book"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-bold text-slate-800 text-sm mb-1 leading-snug">
                                    {{ selectedItem.judul_buku || selectedItem.nama_perpus_sirkulasi || 'Judul Buku Tidak Diketahui' }}
                                </div>
                                <div class="text-xs text-slate-500 space-y-0.5">
                                    <div v-if="selectedItem.pengarang"><i class="bi bi-person me-1.5 text-slate-400"></i><strong>Pengarang:</strong> {{ selectedItem.pengarang }}</div>
                                    <div v-if="selectedItem.penerbit"><i class="bi bi-building me-1.5 text-slate-400"></i><strong>Penerbit:</strong> {{ selectedItem.penerbit }}</div>
                                    <div v-if="selectedItem.klasifikasi"><i class="bi bi-tag me-1.5 text-slate-400"></i><strong>Klasifikasi:</strong> {{ selectedItem.klasifikasi }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loan Timeline Details -->
                    <div class="card border border-slate-200/80 shadow-2xs rounded-xl p-3.5 bg-white mb-3">
                        <h6 class="font-bold text-slate-700 text-xs mb-3 text-uppercase tracking-wider">Informasi Waktu & Sirkulasi</h6>
                        <div class="row g-2.5 text-xs">
                            <div class="col-6">
                                <span class="text-slate-400 d-block text-[11px]">Tanggal Pinjam</span>
                                <strong class="text-slate-800 font-semibold">{{ formatDateIndo(selectedItem.tanggal_pinjam || selectedItem.created_at) }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-slate-400 d-block text-[11px]">Batas Tenggat Kembali</span>
                                <strong :class="isOverdue(selectedItem) ? 'text-rose-600 font-bold' : 'text-slate-800 font-semibold'">{{ formatDateIndo(selectedItem.tanggal_harus_kembali) }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-slate-400 d-block text-[11px]">Tanggal Pengembalian</span>
                                <strong v-if="selectedItem.tanggal_kembali" class="text-emerald-700 font-bold">{{ formatDateIndo(selectedItem.tanggal_kembali) }}</strong>
                                <span v-else class="text-amber-600 font-semibold italic">Belum Dikembalikan</span>
                            </div>
                            <div class="col-6">
                                <span class="text-slate-400 d-block text-[11px]">Status Sirkulasi</span>
                                <span v-if="selectedItem.kategori === 'Kembali' || selectedItem.status === 'Kembali'" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2 py-0.5 rounded text-[10px]">
                                    Selesai Dikembalikan
                                </span>
                                <span v-else-if="isOverdue(selectedItem)" class="badge bg-rose-100 text-rose-800 border border-rose-300 font-bold px-2 py-0.5 rounded text-[10px]">
                                    Terlambat
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 font-bold px-2 py-0.5 rounded text-[10px]">
                                    Sedang Dipinjam
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Penalty Fine Information -->
                    <div class="card border shadow-2xs rounded-xl p-3.5" :class="parseFloat(selectedItem.denda || 0) > 0 ? 'bg-rose-50/50 border-rose-200' : 'bg-white border-slate-200/80'">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-xs font-bold" :class="parseFloat(selectedItem.denda || 0) > 0 ? 'text-rose-800' : 'text-slate-700'">Tagihan Denda</span>
                                <small class="text-slate-400 d-block text-[11px]">Berdasarkan keterlambatan pengembalian</small>
                            </div>
                            <div class="text-end">
                                <div class="h5 font-black mb-0" :class="parseFloat(selectedItem.denda || 0) > 0 ? 'text-rose-600' : 'text-slate-500'">
                                    Rp {{ formatRupiah(selectedItem.denda || 0) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer border-t border-slate-200/80 py-2.5 px-4 bg-white d-flex justify-content-between">
                    <small class="text-slate-400 text-[11px]">Pustaka SINTA SaaS Digital Library</small>
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl font-bold px-3 py-1.5 text-xs text-slate-700 shadow-2xs" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, computed, onMounted } = Vue;

    const riwayatSayaAppConfig = {
        setup() {
            const listRiwayat = ref([]);
            const loading = ref(false);
            const searchQuery = ref('');
            const filterStatus = ref('');
            const activeTab = ref('all');
            const sortOrder = ref('desc');
            const currentPage = ref(1);
            const perPage = ref(15);
            const selectedItem = ref(null);

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($data['active_tenant_id'] ?? ($activeTenantId ?? ''), ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const fetchRiwayat = async () => {
                loading.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/sirkulasi' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        listRiwayat.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Gagal memuat riwayat sirkulasi:', e);
                } finally {
                    loading.value = false;
                }
            };

            const switchTab = (tab) => {
                activeTab.value = tab;
                currentPage.value = 1;
                if (tab === 'dipinjam') {
                    filterStatus.value = 'Dipinjam';
                } else if (tab === 'kembali') {
                    filterStatus.value = 'Kembali';
                } else {
                    filterStatus.value = '';
                }
            };

            const pinjamanAktifCount = computed(() => {
                return listRiwayat.value.filter(item => item.kategori === 'Dipinjam' || item.status === 'Dipinjam').length;
            });

            const kembaliCount = computed(() => {
                return listRiwayat.value.filter(item => item.kategori === 'Kembali' || item.status === 'Kembali').length;
            });

            const terlambatCount = computed(() => {
                return listRiwayat.value.filter(item => isOverdue(item)).length;
            });

            const totalDenda = computed(() => {
                return listRiwayat.value.reduce((sum, item) => sum + (parseFloat(item.denda) || 0), 0);
            });

            const isOverdue = (item) => {
                if (item.kategori === 'Kembali' || item.status === 'Kembali' || !item.tanggal_harus_kembali) return false;
                const today = new Date().toISOString().split('T')[0];
                return item.tanggal_harus_kembali < today;
            };

            const getOverdueDays = (item) => {
                if (!isOverdue(item)) return 0;
                const today = new Date();
                const deadline = new Date(item.tanggal_harus_kembali);
                const diffTime = Math.abs(today - deadline);
                return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            };

            const filteredList = computed(() => {
                return listRiwayat.value.filter(item => {
                    const q = searchQuery.value.toLowerCase().trim();
                    const matchQ = !q ||
                        (item.judul_buku && item.judul_buku.toLowerCase().includes(q)) ||
                        (item.nama_perpus_sirkulasi && item.nama_perpus_sirkulasi.toLowerCase().includes(q)) ||
                        (item.barcode && item.barcode.toLowerCase().includes(q)) ||
                        (item.pengarang && item.pengarang.toLowerCase().includes(q));

                    let matchStatus = true;
                    if (activeTab.value === 'dipinjam') {
                        matchStatus = (item.kategori === 'Dipinjam' || item.status === 'Dipinjam') && !isOverdue(item);
                    } else if (activeTab.value === 'kembali') {
                        matchStatus = (item.kategori === 'Kembali' || item.status === 'Kembali');
                    } else if (activeTab.value === 'terlambat') {
                        matchStatus = isOverdue(item);
                    } else if (filterStatus.value) {
                        matchStatus = (item.kategori === filterStatus.value || item.status === filterStatus.value);
                    }

                    return matchQ && matchStatus;
                }).sort((a, b) => {
                    const dateA = new Date(a.tanggal_pinjam || a.created_at || 0).getTime();
                    const dateB = new Date(b.tanggal_pinjam || b.created_at || 0).getTime();
                    return sortOrder.value === 'asc' ? dateA - dateB : dateB - dateA;
                });
            });

            const totalPages = computed(() => {
                return Math.ceil(filteredList.value.length / perPage.value) || 1;
            });

            const paginatedList = computed(() => {
                const start = (currentPage.value - 1) * perPage.value;
                return filteredList.value.slice(start, start + perPage.value);
            });

            const resetFilter = () => {
                searchQuery.value = '';
                filterStatus.value = '';
                sortOrder.value = 'desc';
                currentPage.value = 1;
            };

            const openDetail = (item) => {
                selectedItem.value = item;
                const el = document.getElementById('modalDetailSirkulasi');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).show();
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

            const formatRelativeTime = (dateStr) => {
                if (!dateStr) return '';
                try {
                    const d = new Date(dateStr);
                    const now = new Date();
                    const diffDays = Math.floor((now - d) / (1000 * 60 * 60 * 24));
                    if (diffDays === 0) return 'Hari ini';
                    if (diffDays === 1) return 'Kemarin';
                    if (diffDays > 1) return `${diffDays} hari lalu`;
                    return '';
                } catch (e) {
                    return '';
                }
            };

            const formatRupiah = (val) => {
                const num = parseFloat(val) || 0;
                return num.toLocaleString('id-ID');
            };

            onMounted(() => {
                fetchRiwayat();
            });

            return {
                listRiwayat,
                loading,
                searchQuery,
                filterStatus,
                activeTab,
                sortOrder,
                currentPage,
                perPage,
                selectedItem,
                pinjamanAktifCount,
                kembaliCount,
                terlambatCount,
                totalDenda,
                filteredList,
                totalPages,
                paginatedList,
                isOverdue,
                getOverdueDays,
                switchTab,
                resetFilter,
                openDetail,
                formatDateIndo,
                formatRelativeTime,
                formatRupiah,
                fetchRiwayat
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#riwayatSayaApp', riwayatSayaAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(riwayatSayaAppConfig).mount('#riwayatSayaApp');
        });
    }
}
</script>

<style>
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
[v-cloak] { display: none !important; }
</style>
