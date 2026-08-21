<?php
/**
 * View: Sirkulasi & Layanan Terpadu Perpustakaan
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL
 */
?>
<div id="sirkulasiPerpusApp" v-cloak class="container-fluid px-0">
    <!-- ═══════════════════════════════════════════════════════════════════════
         HERO BANNER & MULTI-TENANT SAAS SWITCHER
         ═══════════════════════════════════════════════════════════════════════ -->
    <?php
    $heroIcon = 'bi-arrow-left-right';
    $heroBadge = 'Modul Sirkulasi & Peminjaman';
    $heroTitle = 'Sirkulasi & Layanan Peminjaman';
    $heroDesc = 'Kasir sirkulasi buku reguler, pengembalian mandiri, denda keterlambatan, dan distribusi buku paket.';
    $heroButtons = '
        <a href="' . $this->getBaseUrl() . '/perpustakaan" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 border border-white/20 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <a href="' . $this->getBaseUrl() . '/perpustakaan/kios-mandiri" target="_blank" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-display me-1"></i> Buka Kios Mandiri
        </a>
    ';
    include __DIR__ . '/_tenant_filter.php';
    ?>

    <!-- Modern Navtabs Navigation (Gambar 1 Pill Standard) -->
    <div class="card border-0 shadow-2xs rounded-2xl mb-4 bg-white">
        <div class="card-body p-2">
            <div class="nav-pills-container d-flex align-items-center justify-content-between">
                <ul class="nav nav-pills custom-modern-pills flex-nowrap overflow-x-auto text-nowrap gap-1 px-1" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'kasir' }" @click="activeTab = 'kasir'" type="button">
                            <i class="bi bi-qr-code-scan me-1.5 text-primary"></i> 1. Kasir Sirkulasi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'transaksi' }" @click="activeTab = 'transaksi'" type="button">
                            <i class="bi bi-clock-history me-1.5 text-success"></i> 2. Riwayat Transaksi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'paket' }" @click="activeTab = 'paket'" type="button">
                            <i class="bi bi-box-seam me-1.5 text-warning"></i> 3. Buku Paket Pelajaran
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'event' }" @click="activeTab = 'event'" type="button">
                            <i class="bi bi-trophy me-1.5 text-indigo"></i> 4. Event Khusus / OSN
                        </button>
                    </li>
                </ul>

                <button @click="refreshSirkulasi" class="btn btn-light btn-sm text-secondary rounded-xl px-3 py-1.5 border border-slate-200/80 shadow-2xs ms-2 flex-shrink-0 d-none d-md-flex align-items-center gap-1.5">
                    <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> <span class="fs-8 fw-semibold">Segarkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: KASIR SIRKULASI (PINJAM & KEMBALI CEPAT) -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'kasir'" class="tab-pane-content transition-all">
        <div class="row g-4">
            <!-- Form Peminjaman Baru -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-2xs rounded-3xl bg-white p-4 h-100">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="p-2.5 rounded-2xl bg-blue-50 text-blue-600 shadow-2xs">
                            <i class="bi bi-box-arrow-up-right fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-slate-900 mb-0">Peminjaman Buku Baru</h5>
                            <small class="text-muted fs-8">Scan QR ID Anggota dan Barcode Buku untuk memproses pinjaman.</small>
                        </div>
                    </div>

                    <form @submit.prevent="prosesPinjam" class="mt-2">
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Nomor Anggota / NISN / Scan QR <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-50 border-slate-200 text-slate-400 rounded-start-xl"><i class="bi bi-person-badge"></i></span>
                                <input type="text" v-model="formPinjam.anggota_id" class="form-control rounded-end-xl text-xs py-2 border-slate-200" placeholder="Contoh: SIS-2026-001 / NISN Siswa" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Barcode / ISBN Eksemplar Buku <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-50 border-slate-200 text-slate-400 rounded-start-xl"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" v-model="formPinjam.eksemplar_id" class="form-control rounded-end-xl text-xs py-2 border-slate-200" placeholder="Scan Barcode di sampul buku..." required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Durasi Peminjaman <span class="text-danger">*</span></label>
                                <select v-model="formPinjam.preset_durasi" @change="onPresetDurasiChange" class="form-select rounded-xl text-xs py-2 border-slate-200 shadow-2xs font-semibold">
                                    <option value="7">7 Hari (Standar Reguler Siswa)</option>
                                    <option value="14">14 Hari (2 Minggu / Guru)</option>
                                    <option value="30">30 Hari (1 Bulan)</option>
                                    <option value="180">1 Semester (6 Bulan / 180 Hari — Buku Paket)</option>
                                    <option value="365">1 Tahun Ajaran (365 Hari — Buku Paket)</option>
                                    <option value="custom">Kustom Jumlah Hari...</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Kategori Sirkulasi</label>
                                <select v-model="formPinjam.kategori_pinjam" class="form-select rounded-xl text-xs py-2 border-slate-200 shadow-2xs font-semibold">
                                    <option value="Reguler">Buku Reguler / Referensi</option>
                                    <option value="Buku Paket">Buku Paket Pelajaran (Semester/Tahunan)</option>
                                    <option value="Event Khusus / OSN">Event Khusus / Olimpiade (OSN)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6" v-if="formPinjam.preset_durasi === 'custom'">
                                <label class="form-label text-xs fw-bold text-slate-700">Jumlah Hari Kustom</label>
                                <input type="number" v-model.number="formPinjam.durasi_hari" class="form-control rounded-xl text-xs py-2 border-slate-200" min="1" max="730">
                            </div>
                            <div :class="formPinjam.preset_durasi === 'custom' ? 'col-6' : 'col-12'">
                                <label class="form-label text-xs fw-bold text-slate-700">Estimasi Tanggal Jatuh Tempo</label>
                                <input type="text" class="form-control rounded-xl text-xs py-2 bg-slate-50 border-slate-200 text-slate-700 font-bold font-mono" :value="getEstimatedReturnDate(formPinjam.durasi_hari)" readonly>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 py-2.5 w-100 fs-7 font-bold shadow-2xs d-flex align-items-center justify-content-center gap-2" :disabled="processingPinjam">
                            <span v-if="processingPinjam" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-check2-circle fs-6"></i>
                            <span>Proses Peminjaman</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Form Pengembalian & Hitung Denda -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-2xs rounded-3xl bg-white p-4 h-100">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="p-2.5 rounded-2xl bg-emerald-50 text-emerald-600 shadow-2xs">
                            <i class="bi bi-box-arrow-in-down-left fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-slate-900 mb-0">Pengembalian & Cek Denda</h5>
                            <small class="text-muted fs-8">Scan Barcode buku untuk verifikasi pengembalian dan kalkulasi denda.</small>
                        </div>
                    </div>

                    <form @submit.prevent="prosesKembali" class="mt-2">
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Pilih ID Transaksi / Scan Barcode Buku <span class="text-danger">*</span></label>
                            <select v-model="formKembali.sirkulasi_id" class="form-select rounded-xl text-xs py-2 border-slate-200" required>
                                <option value="">-- Pilih Buku yang Sedang Dipinjam --</option>
                                <option v-for="s in activeLoans" :key="s.id" :value="s.id">
                                    {{ s.judul_buku }} ({{ s.nama_anggota }} - Batas: {{ s.tgl_harus_kembali }})
                                </option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-xs fw-bold text-slate-700">Kondisi Buku Saat Dikembalikan</label>
                            <select v-model="formKembali.kondisi" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                <option value="Baik">Kondisi Baik / Utuh</option>
                                <option value="Rusak Ringan">Rusak Ringan (Halaman Terlipat/Coretan)</option>
                                <option value="Rusak Berat">Rusak Berat (Cover Sobek/Lepas)</option>
                                <option value="Hilang">Hilang (Wajib Mengganti)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-emerald btn-sm rounded-xl px-4 py-2.5 w-100 fs-7 font-bold shadow-2xs d-flex align-items-center justify-content-center gap-2" :disabled="processingKembali || !formKembali.sirkulasi_id">
                            <span v-if="processingKembali" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-box-arrow-in-left fs-6"></i>
                            <span>Proses Pengembalian Buku</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: RIWAYAT TRANSAKSI -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'transaksi'" class="tab-pane-content transition-all">
        <!-- Toolbar & Filter -->
        <div class="card border-0 shadow-2xs rounded-2xl mb-3 bg-white">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 400px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-slate-200 text-slate-400 rounded-start-xl"><i class="bi bi-search"></i></span>
                            <input type="text" v-model="searchSirkulasi" class="form-control rounded-end-xl border-slate-200 text-xs py-1.5" placeholder="Cari judul, peminjam, atau barcode...">
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                        <select v-model="filterStatusSirkulasi" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer" style="width: auto; min-width: 150px;">
                            <option value="">Semua Status</option>
                            <option value="Dipinjam">Sedang Dipinjam</option>
                            <option value="Kembali">Sudah Dikembalikan</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table / Seamless Empty State -->
        <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
            <div v-if="filteredSirkulasiList.length === 0" class="p-5 text-center">
                <div class="d-inline-flex p-4 rounded-3xl bg-blue-50 text-blue-600 mb-3 shadow-2xs">
                    <i class="bi bi-clock-history fs-1"></i>
                </div>
                <h5 class="fw-bold text-slate-800 mb-1">Belum Ada Transaksi Sirkulasi</h5>
                <p class="text-muted fs-7 mx-auto mb-4" style="max-width: 420px;">
                    Belum ada riwayat peminjaman atau pengembalian buku yang tercatat di sistem.
                </p>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light bg-slate-50/80 text-slate-600 text-uppercase fs-8 fw-semibold border-bottom border-slate-200/80">
                        <tr>
                            <th class="ps-4 py-3" style="width: 5%;">No</th>
                            <th style="width: 25%;">Judul Buku</th>
                            <th style="width: 20%;">Peminjam</th>
                            <th style="width: 14%;">Tgl Pinjam</th>
                            <th style="width: 14%;">Batas Kembali</th>
                            <th style="width: 12%;" class="text-center">Status</th>
                            <th class="text-center pe-4" style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                        <tr v-for="(s, idx) in filteredSirkulasiList" :key="s.id || idx" class="hover:bg-slate-50/60 transition-colors">
                            <td class="ps-4 py-3 text-slate-400 font-mono fs-8">{{ idx + 1 }}</td>
                            <td class="py-3">
                                <h6 class="fw-bold text-slate-900 mb-0.5 text-truncate" style="max-width: 240px;">{{ s.judul_buku }}</h6>
                                <span class="font-mono fs-9 text-slate-500">{{ s.barcode }}</span>
                            </td>
                            <td class="py-3">
                                <div class="fw-bold text-slate-800">{{ s.nama_anggota }}</div>
                                <span class="badge bg-slate-100 text-slate-700 rounded-pill px-2 py-0.5 fs-9">{{ s.anggota_id }}</span>
                            </td>
                            <td class="py-3 font-mono fs-8 text-slate-600">{{ s.tgl_pinjam }}</td>
                            <td class="py-3 font-mono fs-8" :class="{'text-danger fw-bold': isOverdue(s.tgl_harus_kembali, s.status)}">
                                {{ s.tgl_harus_kembali }}
                                <span v-if="isOverdue(s.tgl_harus_kembali, s.status)" class="badge bg-rose-50 text-rose-700 border border-rose-200 rounded-pill ms-1 fs-9">Telat</span>
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="s.status === 'Dipinjam' || s.kategori === 'Dipinjam'" class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2.5 py-1 fs-8 fw-semibold">
                                    <i class="bi bi-hourglass-split me-1"></i> Dipinjam
                                </span>
                                <span v-else class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 fs-8 fw-semibold">
                                    <i class="bi bi-check-circle me-1"></i> Selesai
                                </span>
                            </td>
                            <td class="py-3 text-center pe-4">
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                    <button v-if="s.status === 'Dipinjam' || s.kategori === 'Dipinjam'" @click="quickReturn(s)" class="btn btn-sm btn-icon rounded-lg text-emerald-600 hover:bg-white transition-all p-1.5" title="Kembalikan Buku">
                                        <i class="bi bi-arrow-down-left-circle"></i>
                                    </button>
                                    <a :href="'<?= $this->getBaseUrl() ?>/perpustakaan/cetak-label-thermal?barcode=' + encodeURIComponent(s.barcode)" target="_blank" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:bg-white transition-all p-1.5" title="Cetak Bukti">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: BUKU PAKET PELAJARAN (DISTRIBUSI SEMESTER / 1 TAHUN) -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'paket'" class="tab-pane-content transition-all">
        <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <h5 class="fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam text-success"></i> Distribusi Buku Paket Pelajaran (Semester / Tahunan)
                    </h5>
                    <small class="text-muted fs-8">Peminjaman buku teks pelajaran jangka panjang (1 Semester / 1 Tahun Ajaran Penuh) per rombel kelas.</small>
                </div>
                <button type="button" @click="openModalTambahPaket" class="btn btn-sm btn-success rounded-xl px-3.5 py-2 font-bold fs-7 d-inline-flex align-items-center gap-1.5 shadow-2xs">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Distribusi Buku Paket
                </button>
            </div>

            <!-- List Distribusi Paket -->
            <div v-if="bukuPaketList.length === 0" class="p-5 text-center rounded-2xl bg-slate-50 border border-slate-100">
                <i class="bi bi-box-seam display-5 text-success d-block mb-2 opacity-50"></i>
                <h6 class="fw-bold text-slate-700 mb-1">Belum Ada Sesi Distribusi Buku Paket</h6>
                <p class="text-muted fs-8 mb-3" style="max-width: 440px; margin: 0 auto;">
                    Distribusikan buku paket pelajaran secara massal ke siswa satu kelas sekaligus dengan durasi 1 semester atau 1 tahun ajaran.
                </p>
                <button type="button" @click="openModalTambahPaket" class="btn btn-sm btn-outline-success rounded-xl px-3 py-1.5 font-bold fs-8">
                    <i class="bi bi-plus-lg me-1"></i> Mulai Distribusi Sekarang
                </button>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light bg-slate-50 text-slate-600 text-uppercase fs-8 fw-semibold border-bottom">
                        <tr>
                            <th class="ps-3 py-3">No</th>
                            <th class="py-3">Kelas / Rombel</th>
                            <th class="py-3">Mata Pelajaran & Judul Buku Paket</th>
                            <th class="py-3 text-center">Durasi Pinjam</th>
                            <th class="py-3 text-center">Jatuh Tempo</th>
                            <th class="py-3 text-center">Jumlah Siswa</th>
                            <th class="py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                        <tr v-for="(p, idx) in bukuPaketList" :key="idx">
                            <td class="ps-3 py-3 text-slate-400 font-mono fs-8">{{ idx + 1 }}</td>
                            <td class="py-3 font-bold text-slate-800">{{ p.nama_kelas }}</td>
                            <td class="py-3">
                                <div class="fw-bold text-slate-900">{{ p.judul_buku }}</div>
                                <small class="text-slate-400 font-mono">Kode Mapel: {{ p.kode_mapel || '-' }}</small>
                            </td>
                            <td class="py-3 text-center font-semibold text-slate-600">
                                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 rounded-pill px-2.5 py-1 text-xs">
                                    {{ p.durasi_label }}
                                </span>
                            </td>
                            <td class="py-3 text-center font-mono text-slate-700 font-bold text-xs">{{ p.tgl_jatuh_tempo }}</td>
                            <td class="py-3 text-center font-bold text-slate-800">{{ p.jumlah_eksemplar }} Buku</td>
                            <td class="py-3 text-center">
                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 text-xs">
                                    <i class="bi bi-check2-all me-1"></i> Terdistribusi
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: EVENT KHUSUS / OSN -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'event'" class="tab-pane-content transition-all">
        <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <h5 class="fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-trophy text-amber-500"></i> Peminjaman Kontingen Event Olimpiade (OSN) & Lomba
                    </h5>
                    <small class="text-muted fs-8">Peminjaman buku pengayaan, bank soal, dan referensi khusus kontingen siswa lomba (bebas denda selama masa karantina/lomba).</small>
                </div>
                <button type="button" @click="openModalTambahEvent" class="btn btn-sm btn-warning text-slate-900 rounded-xl px-3.5 py-2 font-bold fs-7 d-inline-flex align-items-center gap-1.5 shadow-2xs">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Peminjaman Event / OSN
                </button>
            </div>

            <!-- List Event Khusus -->
            <div v-if="eventList.length === 0" class="p-5 text-center rounded-2xl bg-slate-50 border border-slate-100">
                <i class="bi bi-trophy display-5 text-amber-500 d-block mb-2 opacity-50"></i>
                <h6 class="fw-bold text-slate-700 mb-1">Belum Ada Peminjaman Event / Lomba</h6>
                <p class="text-muted fs-8 mb-3" style="max-width: 440px; margin: 0 auto;">
                    Catat peminjaman koleksi referensi khusus untuk tim olimpiade sains (OSN), debat, atau lomba akademik lainnya.
                </p>
                <button type="button" @click="openModalTambahEvent" class="btn btn-sm btn-outline-warning text-slate-800 rounded-xl px-3 py-1.5 font-bold fs-8">
                    <i class="bi bi-plus-lg me-1"></i> Catat Peminjaman Event
                </button>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light bg-slate-50 text-slate-600 text-uppercase fs-8 fw-semibold border-bottom">
                        <tr>
                            <th class="ps-3 py-3">No</th>
                            <th class="py-3">Nama Event / Olimpiade</th>
                            <th class="py-3">Guru Pembina</th>
                            <th class="py-3">Kontingen Siswa</th>
                            <th class="py-3">Koleksi Referensi Dipinjam</th>
                            <th class="py-3 text-center">Batas Pengembalian</th>
                            <th class="py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                        <tr v-for="(ev, idx) in eventList" :key="idx">
                            <td class="ps-3 py-3 text-slate-400 font-mono fs-8">{{ idx + 1 }}</td>
                            <td class="py-3 font-bold text-slate-900">{{ ev.nama_event }}</td>
                            <td class="py-3 text-slate-700 font-semibold">{{ ev.guru_pembina || '-' }}</td>
                            <td class="py-3">
                                <span class="badge bg-amber-50 text-amber-800 border border-amber-200 rounded-pill px-2.5 py-1 text-xs">
                                    <i class="bi bi-people me-1"></i> {{ ev.nama_siswa }}
                                </span>
                            </td>
                            <td class="py-3 font-semibold text-slate-800">{{ ev.judul_buku }}</td>
                            <td class="py-3 text-center font-mono text-slate-700 font-bold text-xs">{{ ev.tgl_selesai_lomba }}</td>
                            <td class="py-3 text-center">
                                <span class="badge bg-purple-50 text-purple-700 border border-purple-200 rounded-pill px-2.5 py-1 text-xs">
                                    <i class="bi bi-award me-1"></i> Pembinaan Aktif
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL: DISTRIBUSI BUKU PAKET BARU (DYNAMIC DATABASE DRIVEN)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalDistribusiPaket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3xl border-0 shadow-lg p-3">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title font-bold text-slate-800 d-flex align-items-center gap-2">
                            <i class="bi bi-box-seam text-success"></i> Distribusi Buku Paket Pelajaran (Semester / Tahunan)
                        </h5>
                        <small class="text-muted fs-8">Ambil data kelas, buku pelajaran, dan guru pengampu langsung dari database sekolah.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="simpanDistribusiPaket">
                    <div class="modal-body py-3">
                        <div class="row g-3 mb-3">
                            <!-- 1. Target Kelas / Rombel dari DB -->
                            <div class="col-12">
                                <label class="form-label text-xs font-bold text-slate-700 d-flex justify-content-between">
                                    <span>Target Kelas / Rombel <span class="text-danger">*</span></span>
                                    <span class="text-muted fs-9" v-if="masterReferensi.kelas.length > 0">
                                        <i class="bi bi-database-check text-success"></i> {{ masterReferensi.kelas.length }} Kelas Terdaftar
                                    </span>
                                </label>
                                <select v-if="!formPaket.is_custom_kelas" :value="formPaket.kelas_id || formPaket.nama_kelas" @change="onKelasSelectChange" class="form-select rounded-xl text-xs py-2 border-slate-200 font-semibold" required>
                                    <option value="" disabled selected>-- Pilih Kelas / Rombel dari Database --</option>
                                    <option v-for="k in masterReferensi.kelas" :key="k.id" :value="k.id">
                                        {{ k.nama_kelas }} {{ k.kode_kelas ? '(' + k.kode_kelas + ')' : '' }}
                                    </option>
                                    <option value="__custom__">➕ [Input Manual] Kelas Lainnya di Luar Database...</option>
                                </select>
                                <div v-else class="input-group">
                                    <input type="text" v-model="formPaket.nama_kelas" class="form-control rounded-start-xl text-xs py-2 border-slate-200" placeholder="Ketik nama kelas manual (e.g. Kelas XII Agama 2)..." required>
                                    <button type="button" @click="formPaket.is_custom_kelas = false; formPaket.nama_kelas = ''; formPaket.kelas_id = ''" class="btn btn-outline-secondary text-xs rounded-end-xl px-3 font-semibold">
                                        <i class="bi bi-arrow-counterclockwise"></i> Pilih dari DB
                                    </button>
                                </div>
                            </div>

                            <!-- 2. Mata Pelajaran & Judul Buku Paket dari DB -->
                            <div class="col-12">
                                <label class="form-label text-xs font-bold text-slate-700 d-flex justify-content-between">
                                    <span>Mata Pelajaran & Judul Buku Paket <span class="text-danger">*</span></span>
                                    <span class="text-muted fs-9" v-if="masterReferensi.buku.length > 0">
                                        <i class="bi bi-book-half text-primary"></i> {{ masterReferensi.buku.length }} Koleksi Buku Terdaftar
                                    </span>
                                </label>
                                <select v-if="!formPaket.is_custom_buku" :value="formPaket.buku_id || formPaket.judul_buku" @change="onBukuSelectChange" class="form-select rounded-xl text-xs py-2 border-slate-200 font-semibold" required>
                                    <option value="" disabled selected>-- Pilih Buku Paket dari Katalog Perpustakaan --</option>
                                    <option v-for="b in masterReferensi.buku" :key="b.id" :value="b.id">
                                        {{ b.judul }} {{ b.penulis && b.penulis !== '-' ? '— ' + b.penulis : '' }} {{ b.isbn && b.isbn !== '-' ? '(ISBN: ' + b.isbn + ')' : '' }}
                                    </option>
                                    <option value="__custom__">➕ [Input Manual] Judul Buku Paket di Luar Katalog...</option>
                                </select>
                                <div v-else class="input-group">
                                    <input type="text" v-model="formPaket.judul_buku" class="form-control rounded-start-xl text-xs py-2 border-slate-200" placeholder="Ketik judul buku paket manual (e.g. Bahasa Indonesia Tingkat Lanjut Kelas XI)..." required>
                                    <button type="button" @click="formPaket.is_custom_buku = false; formPaket.judul_buku = ''; formPaket.buku_id = ''" class="btn btn-outline-secondary text-xs rounded-end-xl px-3 font-semibold">
                                        <i class="bi bi-arrow-counterclockwise"></i> Pilih dari Katalog
                                    </button>
                                </div>
                            </div>

                            <!-- 3. Guru Pengampu / Guru Mapel dari DB -->
                            <div class="col-12">
                                <label class="form-label text-xs font-bold text-slate-700 d-flex justify-content-between">
                                    <span>Guru Pengampu / Penanggung Jawab Mapel</span>
                                    <span class="text-muted fs-9" v-if="masterReferensi.guru.length > 0">
                                        <i class="bi bi-person-badge text-amber-500"></i> {{ masterReferensi.guru.length }} Guru Terdaftar
                                    </span>
                                </label>
                                <select v-if="!formPaket.is_custom_guru" :value="formPaket.guru_mapel" @change="onGuruSelectChange" class="form-select rounded-xl text-xs py-2 border-slate-200 font-semibold">
                                    <option value="">-- Pilih Guru Mapel dari Database (Opsional) --</option>
                                    <option v-for="g in masterReferensi.guru" :key="g.id" :value="g.nama">
                                        {{ g.nama }} {{ g.nuptk && g.nuptk !== '-' ? '(NUPTK: ' + g.nuptk + ')' : '' }}
                                    </option>
                                    <option value="__custom__">➕ [Input Manual] Nama Guru di Luar Database...</option>
                                </select>
                                <div v-else class="input-group">
                                    <input type="text" v-model="formPaket.guru_mapel" class="form-control rounded-start-xl text-xs py-2 border-slate-200" placeholder="Ketik nama guru pengampu manual...">
                                    <button type="button" @click="formPaket.is_custom_guru = false; formPaket.guru_mapel = ''" class="btn btn-outline-secondary text-xs rounded-end-xl px-3 font-semibold">
                                        <i class="bi bi-arrow-counterclockwise"></i> Pilih dari DB
                                    </button>
                                </div>
                            </div>

                            <!-- 4. Durasi & Jumlah Eksemplar -->
                            <div class="col-md-6">
                                <label class="form-label text-xs font-bold text-slate-700">Durasi Peminjaman Paket</label>
                                <select v-model="formPaket.durasi_hari" class="form-select rounded-xl text-xs py-2 border-slate-200 font-semibold">
                                    <option value="180">1 Semester (6 Bulan / 180 Hari)</option>
                                    <option value="365">1 Tahun Ajaran (12 Bulan / 365 Hari)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs font-bold text-slate-700">Jumlah Eksemplar / Siswa</label>
                                <input type="number" v-model.number="formPaket.jumlah_eksemplar" class="form-control rounded-xl text-xs py-2 border-slate-200 font-bold" min="1" value="36" required>
                            </div>

                            <!-- 5. Catatan Tambahan -->
                            <div class="col-12">
                                <label class="form-label text-xs font-bold text-slate-700">Catatan Nomor Seri / Lemari Simpan</label>
                                <textarea v-model="formPaket.catatan" class="form-control rounded-xl text-xs py-2 border-slate-200" rows="2" placeholder="Catatan nomor seri buku paket, barcode induk, atau lokasi rak kelas..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-xl px-3.5 py-2 text-xs font-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" :disabled="savingPaket" class="btn btn-success rounded-xl px-4 py-2 text-xs font-bold shadow-2xs d-inline-flex align-items-center gap-1.5">
                            <i v-if="savingPaket" class="bi bi-arrow-repeat spin"></i>
                            <i v-else class="bi bi-check2-circle"></i>
                            <span>{{ savingPaket ? 'Menyimpan...' : 'Simpan Distribusi Paket' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL: TAMBAH PEMINJAMAN EVENT / OSN
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalEventOsn" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3xl border-0 shadow-lg p-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-bold text-slate-800 d-flex align-items-center gap-2">
                        <i class="bi bi-trophy text-amber-500"></i> Peminjaman Buku Event / OSN
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="simpanEventOsn">
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label text-xs font-bold text-slate-700">Nama Event / Olimpiade / Lomba <span class="text-danger">*</span></label>
                            <input type="text" v-model="formEvent.nama_event" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Contoh: OSN Tingkat Provinsi Bidang Fisika 2026" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label text-xs font-bold text-slate-700">Nama Siswa / Kontingen <span class="text-danger">*</span></label>
                                <input type="text" v-model="formEvent.nama_siswa" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Nama siswa kontingen..." required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs font-bold text-slate-700">Guru Pembina</label>
                                <input type="text" v-model="formEvent.guru_pembina" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Nama guru pembina...">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs font-bold text-slate-700">Judul Buku Referensi / Kode Barcode <span class="text-danger">*</span></label>
                            <input type="text" v-model="formEvent.judul_buku" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Judul buku pengayaan / bank soal OSN..." required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-xs font-bold text-slate-700">Tanggal Selesai Event (Batas Kembali)</label>
                            <input type="date" v-model="formEvent.tgl_selesai_lomba" class="form-control rounded-xl text-xs py-2 border-slate-200 font-semibold" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-xl px-3 py-2 text-xs font-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning text-slate-900 rounded-xl px-4 py-2 text-xs font-bold shadow-2xs d-inline-flex align-items-center gap-1.5">
                            <i class="bi bi-check2-circle"></i> Catat Peminjaman Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Vue 3 In-DOM Instance for Sirkulasi -->
<script>
if (typeof Vue !== 'undefined') {
    const { ref, computed, onMounted } = Vue;

    const sirkulasiAppConfig = {
        setup() {
            const activeTab = ref('kasir');
            const loading = ref(false);
            const processingPinjam = ref(false);
            const processingKembali = ref(false);
            const savingPaket = ref(false);
            const loadingMaster = ref(false);

            const sirkulasiList = ref([]);
            const bukuPaketList = ref([]);
            const eventList = ref([]);
            const masterReferensi = ref({ kelas: [], buku: [], mapel: [], guru: [] });
            const pengaturan = ref({ tarif_denda_per_hari: 500 });
            const searchSirkulasi = ref('');
            const filterStatusSirkulasi = ref('');

            // Tenant Isolation Helper
            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($data['active_tenant_id'] ?? ($activeTenantId ?? '')) ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const formPinjam = ref({
                anggota_id: '',
                eksemplar_id: '',
                preset_durasi: '7',
                durasi_hari: 7,
                kategori_pinjam: 'Reguler',
                tenant_id: currentTenantId
            });

            const onPresetDurasiChange = () => {
                if (formPinjam.value.preset_durasi !== 'custom') {
                    formPinjam.value.durasi_hari = parseInt(formPinjam.value.preset_durasi);
                }
            };

            const formKembali = ref({
                sirkulasi_id: '',
                kondisi: 'Baik',
                tenant_id: currentTenantId
            });

            // Modal & Form State for Buku Paket (Database-Driven)
            const formPaket = ref({
                kelas_id: '',
                nama_kelas: '',
                is_custom_kelas: false,
                buku_id: '',
                judul_buku: '',
                is_custom_buku: false,
                guru_mapel: '',
                is_custom_guru: false,
                durasi_hari: '180',
                jumlah_eksemplar: 36,
                catatan: '',
                tenant_id: currentTenantId
            });

            const onKelasSelectChange = (e) => {
                const val = e.target.value;
                if (val === '__custom__') {
                    formPaket.value.is_custom_kelas = true;
                    formPaket.value.nama_kelas = '';
                    formPaket.value.kelas_id = '';
                } else {
                    formPaket.value.is_custom_kelas = false;
                    formPaket.value.kelas_id = val;
                    const found = masterReferensi.value.kelas.find(k => k.id === val);
                    if (found) {
                        formPaket.value.nama_kelas = found.nama_kelas;
                    }
                }
            };

            const onBukuSelectChange = (e) => {
                const val = e.target.value;
                if (val === '__custom__') {
                    formPaket.value.is_custom_buku = true;
                    formPaket.value.judul_buku = '';
                    formPaket.value.buku_id = '';
                } else {
                    formPaket.value.is_custom_buku = false;
                    formPaket.value.buku_id = val;
                    const found = masterReferensi.value.buku.find(b => b.id === val);
                    if (found) {
                        formPaket.value.judul_buku = found.judul;
                    }
                }
            };

            const onGuruSelectChange = (e) => {
                const val = e.target.value;
                if (val === '__custom__') {
                    formPaket.value.is_custom_guru = true;
                    formPaket.value.guru_mapel = '';
                } else {
                    formPaket.value.is_custom_guru = false;
                    formPaket.value.guru_mapel = val;
                }
            };

            // Modal & Form State for Event OSN
            const formEvent = ref({
                nama_event: '',
                nama_siswa: '',
                guru_pembina: '',
                judul_buku: '',
                tgl_selesai_lomba: '',
                tenant_id: currentTenantId
            });

            let modalPaketInst = null;
            let modalEventInst = null;

            const openModalTambahPaket = () => {
                formPaket.value = {
                    kelas_id: '',
                    nama_kelas: '',
                    is_custom_kelas: false,
                    buku_id: '',
                    judul_buku: '',
                    is_custom_buku: false,
                    guru_mapel: '',
                    is_custom_guru: false,
                    durasi_hari: '180',
                    jumlah_eksemplar: 36,
                    catatan: '',
                    tenant_id: currentTenantId
                };
                if (masterReferensi.value.kelas.length === 0) {
                    fetchMasterReferensi();
                }
                const el = document.getElementById('modalDistribusiPaket');
                if (el && typeof bootstrap !== 'undefined') {
                    modalPaketInst = new bootstrap.Modal(el);
                    modalPaketInst.show();
                }
            };

            const fetchMasterReferensi = async () => {
                loadingMaster.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/paket-buku/referensi' + getTenantParam('?'));
                    if (res.data && res.data.success && res.data.data) {
                        masterReferensi.value = res.data.data;
                    }
                } catch (e) {
                    console.error('Error load master referensi paket:', e);
                } finally {
                    loadingMaster.value = false;
                }
            };

            const fetchPaketList = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/paket-buku' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        bukuPaketList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Error load paket buku list:', e);
                }
            };

            const simpanDistribusiPaket = async () => {
                savingPaket.value = true;
                try {
                    const durasi = parseInt(formPaket.value.durasi_hari);
                    const d = new Date();
                    d.setDate(d.getDate() + durasi);
                    const jatuhTempo = d.toISOString().split('T')[0];

                    const payload = {
                        kelas_id: formPaket.value.kelas_id,
                        nama_kelas: formPaket.value.nama_kelas,
                        buku_id: formPaket.value.buku_id,
                        judul_buku: formPaket.value.judul_buku,
                        guru_mapel: formPaket.value.guru_mapel,
                        durasi_hari: durasi,
                        tgl_jatuh_tempo: jatuhTempo,
                        jumlah_eksemplar: formPaket.value.jumlah_eksemplar,
                        catatan: formPaket.value.catatan,
                        tenant_id: currentTenantId
                    };

                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/paket-buku/simpan' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        alert('Distribusi buku paket untuk ' + formPaket.value.nama_kelas + ' berhasil dicatat!');
                        if (modalPaketInst) modalPaketInst.hide();
                        await fetchPaketList();
                    } else {
                        alert(res.data.message || 'Gagal menyimpan distribusi buku paket.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan saat menyimpan distribusi buku paket.');
                } finally {
                    savingPaket.value = false;
                }
            };

            const openModalTambahEvent = () => {
                const defaultDate = new Date();
                defaultDate.setDate(defaultDate.getDate() + 30);
                formEvent.value = {
                    nama_event: '',
                    nama_siswa: '',
                    guru_pembina: '',
                    judul_buku: '',
                    tgl_selesai_lomba: defaultDate.toISOString().split('T')[0],
                    tenant_id: currentTenantId
                };
                const el = document.getElementById('modalEventOsn');
                if (el && typeof bootstrap !== 'undefined') {
                    modalEventInst = new bootstrap.Modal(el);
                    modalEventInst.show();
                }
            };

            const simpanEventOsn = () => {
                eventList.value.unshift({
                    nama_event: formEvent.value.nama_event,
                    nama_siswa: formEvent.value.nama_siswa,
                    guru_pembina: formEvent.value.guru_pembina,
                    judul_buku: formEvent.value.judul_buku,
                    tgl_selesai_lomba: formEvent.value.tgl_selesai_lomba
                });

                if (modalEventInst) modalEventInst.hide();
                alert('Peminjaman buku event ' + formEvent.value.nama_event + ' berhasil dicatat!');
            };

            const fetchSirkulasi = async () => {
                loading.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/sirkulasi' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        sirkulasiList.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Error load sirkulasi:', e);
                } finally {
                    loading.value = false;
                }
            };

            const activeLoans = computed(() => {
                return sirkulasiList.value.filter(s => s.status === 'Dipinjam' || s.kategori === 'Dipinjam');
            });

            const filteredSirkulasiList = computed(() => {
                return sirkulasiList.value.filter(s => {
                    const matchQ = !searchSirkulasi.value ||
                        (s.judul_buku && s.judul_buku.toLowerCase().includes(searchSirkulasi.value.toLowerCase())) ||
                        (s.nama_anggota && s.nama_anggota.toLowerCase().includes(searchSirkulasi.value.toLowerCase())) ||
                        (s.barcode && s.barcode.toLowerCase().includes(searchSirkulasi.value.toLowerCase()));

                    const matchStatus = !filterStatusSirkulasi.value || s.status === filterStatusSirkulasi.value || s.kategori === filterStatusSirkulasi.value;
                    return matchQ && matchStatus;
                });
            });

            const getEstimatedReturnDate = (durasi) => {
                const d = new Date();
                d.setDate(d.getDate() + parseInt(durasi || 7));
                return d.toISOString().split('T')[0];
            };

            const isOverdue = (tglHarus, status) => {
                if ((status !== 'Dipinjam' && status !== 'Terlambat') || !tglHarus) return false;
                const today = new Date().toISOString().split('T')[0];
                return today > tglHarus;
            };

            const formatNumber = (num) => {
                return new Intl.NumberFormat('id-ID').format(num || 0);
            };

            const prosesPinjam = async () => {
                processingPinjam.value = true;
                try {
                    const payload = {
                        anggota_id: formPinjam.value.anggota_id,
                        eksemplar_id: formPinjam.value.eksemplar_id,
                        durasi_hari: formPinjam.value.durasi_hari,
                        kategori_pinjam: formPinjam.value.kategori_pinjam,
                        tenant_id: currentTenantId
                    };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/sirkulasi/pinjam' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        alert(res.data.message || 'Peminjaman berhasil dicatat!');
                        formPinjam.value.anggota_id = '';
                        formPinjam.value.eksemplar_id = '';
                        await fetchSirkulasi();
                        activeTab.value = 'transaksi';
                    } else {
                        alert(res.data.message || res.data.error || 'Gagal memproses peminjaman.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan sistem saat memproses peminjaman.');
                } finally {
                    processingPinjam.value = false;
                }
            };

            const prosesKembali = async () => {
                processingKembali.value = true;
                try {
                    const payload = { ...formKembali.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/sirkulasi/kembali' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        alert(res.data.message || 'Pengembalian berhasil diproses!');
                        formKembali.value.sirkulasi_id = '';
                        await fetchSirkulasi();
                        activeTab.value = 'transaksi';
                    } else {
                        alert(res.data.message || res.data.error || 'Gagal memproses pengembalian.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan sistem saat memproses pengembalian.');
                } finally {
                    processingKembali.value = false;
                }
            };

            const quickReturn = (s) => {
                formKembali.value.sirkulasi_id = s.id;
                activeTab.value = 'kasir';
            };

            const refreshSirkulasi = () => {
                fetchSirkulasi();
                fetchPaketList();
            };

            onMounted(() => {
                fetchSirkulasi();
                fetchPaketList();
                fetchMasterReferensi();
            });

            return {
                activeTab,
                loading,
                processingPinjam,
                processingKembali,
                savingPaket,
                loadingMaster,
                sirkulasiList,
                bukuPaketList,
                eventList,
                masterReferensi,
                activeLoans,
                filteredSirkulasiList,
                pengaturan,
                searchSirkulasi,
                filterStatusSirkulasi,
                formPinjam,
                formKembali,
                formPaket,
                formEvent,
                onPresetDurasiChange,
                onKelasSelectChange,
                onBukuSelectChange,
                onGuruSelectChange,
                fetchMasterReferensi,
                fetchPaketList,
                openModalTambahPaket,
                simpanDistribusiPaket,
                openModalTambahEvent,
                simpanEventOsn,
                getEstimatedReturnDate,
                isOverdue,
                formatNumber,
                prosesPinjam,
                prosesKembali,
                quickReturn,
                refreshSirkulasi
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#sirkulasiPerpusApp', sirkulasiAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        const mountApp = () => {
            const el = document.querySelector('#sirkulasiPerpusApp');
            if (el && !el.__vue_app__) {
                Vue.createApp(sirkulasiAppConfig).mount('#sirkulasiPerpusApp');
            }
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mountApp);
        } else {
            mountApp();
        }
        document.addEventListener('turbo:load', mountApp);
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
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
[v-cloak] { display: none !important; }
</style>
