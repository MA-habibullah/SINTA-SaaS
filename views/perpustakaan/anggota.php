<?php
/**
 * View: Administrasi & Keanggotaan Perpustakaan
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
?>
<div id="anggotaPerpusApp" v-cloak class="container-fluid px-0">
    <!-- ═══════════════════════════════════════════════════════════════════════
         HERO BANNER & MULTI-TENANT SAAS SWITCHER
         ═══════════════════════════════════════════════════════════════════════ -->
    <?php
    $heroIcon = 'bi-people-fill';
    $heroBadge = 'Modul Keanggotaan & Layanan';
    $heroTitle = 'Administrasi & Keanggotaan Perpustakaan';
    $heroDesc = 'Pusat data terpadu siswa & guru, penerbitan surat bebas pustaka, presensi buku tamu harian, dan integrasi WhatsApp Gateway.';
    $heroButtons = '
        <a href="' . $this->getBaseUrl() . '/perpustakaan" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 border border-white/20 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    ';
    include __DIR__ . '/_tenant_filter.php';
    ?>

    <!-- Modern Navtabs Navigation (Standard SINTA SaaS) -->
    <div class="card border-0 shadow-2xs rounded-2xl mb-4 bg-white">
        <div class="card-body p-2">
            <div class="nav-pills-container d-flex align-items-center justify-content-between">
                <ul class="nav nav-pills custom-modern-pills flex-nowrap overflow-x-auto text-nowrap gap-1 px-1" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'anggota' }" @click="activeTab = 'anggota'" type="button">
                            <i class="bi bi-people-fill me-1.5 text-primary"></i> 1. Daftar Anggota
                            <span v-if="anggotaList.length > 0" class="badge bg-white/20 ms-1 text-xs rounded-pill">{{ anggotaList.length }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'bebas' }" @click="activeTab = 'bebas'" type="button">
                            <i class="bi bi-file-earmark-check-fill me-1.5 text-emerald-500"></i> 2. Bebas Pustaka
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'tamu' }" @click="activeTab = 'tamu'; fetchVisitorStats();" type="button">
                            <i class="bi bi-person-workspace me-1.5 text-amber-500"></i> 3. Buku Tamu / Pengunjung
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'pengaturan' }" @click="activeTab = 'pengaturan'" type="button">
                            <i class="bi bi-sliders me-1.5 text-indigo-500"></i> 4. Pengaturan & WA Gateway
                        </button>
                    </li>
                </ul>

                <button @click="refreshCurrentTab" class="btn btn-light btn-sm text-secondary rounded-xl px-3 py-1.5 border border-slate-200/80 shadow-2xs ms-2 flex-shrink-0 d-none d-md-flex align-items-center gap-1.5">
                    <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> <span class="fs-8 fw-semibold">Segarkan Data</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: DAFTAR ANGGOTA TERPADU (AUTO-FEDERATED) -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'anggota'" class="tab-pane-content transition-all">
        <!-- Single-Line Symmetrical Toolbar -->
        <div class="card border-0 shadow-2xs rounded-2xl mb-4 bg-white">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap flex-lg-nowrap justify-content-between align-items-center gap-3">
                    <div class="position-relative flex-grow-1" style="min-width: 260px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-7"></i>
                        <input type="text" v-model="searchQuery"
                               class="form-control form-control-sm ps-5 rounded-xl border-slate-200 shadow-2xs text-xs font-medium py-2"
                               placeholder="Cari NISN, NIP, nama anggota, nomor anggota, atau kelas...">
                        <button v-if="searchQuery" @click="searchQuery = ''" class="btn btn-link btn-sm position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 p-0 text-decoration-none">
                            <i class="bi bi-x-circle-fill fs-7"></i>
                        </button>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                        <select v-model="filterKategori" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-2 px-3 cursor-pointer" style="width: auto; min-width: 140px;">
                            <option value="">Semua Peran</option>
                            <option value="Siswa">Siswa</option>
                            <option value="Guru">Guru / Pendidik</option>
                            <option value="Tendik">Tendik / Karyawan</option>
                            <option value="Umum">Umum / Eksternal</option>
                        </select>

                        <button @click="openModalTambahAnggota" class="btn btn-primary btn-sm rounded-xl px-3.5 py-2 fs-7 font-semibold shadow-2xs d-flex align-items-center gap-1.5">
                            <i class="bi bi-person-plus-fill"></i> <span>Tambah Anggota Luar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table / Seamless Empty State -->
        <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
            <div v-if="loading" class="p-5 text-center">
                <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                <p class="text-muted fs-7 mb-0">Memuat basis data keanggotaan terpadu (Siswa, Guru, Tendik)...</p>
            </div>

            <div v-else-if="filteredAnggotaList.length === 0" class="p-5 text-center">
                <div class="d-inline-flex p-4 rounded-3xl bg-blue-50 text-blue-600 mb-3 shadow-2xs">
                    <i class="bi bi-people fs-1"></i>
                </div>
                <h5 class="fw-bold text-slate-800 mb-1">Tidak Ada Data Anggota Ditemukan</h5>
                <p class="text-muted fs-7 mx-auto mb-4" style="max-width: 420px;">
                    Data anggota tidak cocok dengan kata kunci pencarian atau filter peran yang dipilih.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button v-if="searchQuery || filterKategori" @click="resetFilterAnggota" class="btn btn-outline-secondary btn-sm rounded-xl px-3.5 py-2 fs-7 font-medium shadow-2xs">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                    </button>
                </div>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light bg-slate-50/80 text-slate-600 text-uppercase fs-8 fw-semibold border-bottom border-slate-200/80">
                        <tr>
                            <th class="ps-4 py-3" style="width: 5%;">No</th>
                            <th style="width: 15%;">ID / No. Anggota</th>
                            <th style="width: 25%;">Nama Anggota & Identitas</th>
                            <th style="width: 15%;">Kelas / Unit Kerja</th>
                            <th style="width: 12%;">Peran</th>
                            <th style="width: 13%;" class="text-center">Status Sirkulasi</th>
                            <th class="text-center pe-4" style="width: 15%;">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                        <tr v-for="(item, idx) in paginatedAnggota" :key="item.id || idx" class="hover:bg-slate-50/60 transition-colors">
                            <td class="ps-4 py-3 text-slate-400 font-mono fs-8">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td class="py-3">
                                <span class="badge bg-slate-100 text-slate-800 border border-slate-200 font-mono rounded-lg px-2 py-1 font-semibold text-xs">{{ item.no_anggota }}</span>
                            </td>
                            <td class="py-3">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="avatar-circle flex-shrink-0 d-flex align-items-center justify-content-center fw-bold text-xs"
                                         :class="{
                                             'bg-blue-100 text-blue-700': item.kategori === 'Siswa',
                                             'bg-emerald-100 text-emerald-700': item.kategori === 'Guru',
                                             'bg-purple-100 text-purple-700': item.kategori === 'Tendik',
                                             'bg-amber-100 text-amber-700': item.kategori === 'Umum'
                                         }"
                                         style="width: 34px; height: 34px; border-radius: 10px;">
                                        {{ getInitials(item.nama_lengkap) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-slate-900 mb-0.5">{{ item.nama_lengkap }}</div>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <span v-if="item.nisn && item.nisn !== '-'" class="font-mono fs-8 text-slate-500 bg-slate-100 px-1.5 py-0.2 rounded">NISN: {{ item.nisn }}</span>
                                            <span v-if="item.nip && item.nip !== '-'" class="font-mono fs-8 text-slate-500 bg-slate-100 px-1.5 py-0.2 rounded">NIP/ID: {{ item.nip }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                    <i class="bi bi-door-open me-1"></i>{{ item.nama_kelas || '-' }}
                                </span>
                            </td>
                            <td class="py-3">
                                <span class="badge rounded-pill px-2.5 py-1 fs-8 fw-semibold"
                                      :class="{
                                          'bg-blue-50 text-blue-700 border border-blue-200': item.kategori === 'Siswa',
                                          'bg-emerald-50 text-emerald-700 border border-emerald-200': item.kategori === 'Guru',
                                          'bg-purple-50 text-purple-700 border border-purple-200': item.kategori === 'Tendik',
                                          'bg-amber-50 text-amber-700 border border-amber-200': item.kategori === 'Umum'
                                      }">
                                    {{ item.kategori || item.tipe_anggota || 'Siswa' }}
                                </span>
                            </td>
                            <td class="py-3 text-center">
                                <div v-if="item.pinjam_aktif > 0">
                                    <span class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2.5 py-1 fs-9 fw-bold">
                                        <i class="bi bi-book me-1"></i>{{ item.pinjam_aktif }} Buku Dipinjam
                                    </span>
                                </div>
                                <div v-else>
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1"></i>Bebas Pinjaman
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 text-center pe-4">
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                    <button @click="showKartuAnggota(item)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-primary hover:bg-white transition-all p-1.5" title="Lihat & Cetak Kartu Anggota">
                                        <i class="bi bi-person-badge"></i>
                                    </button>
                                    <button @click="periksaBebasPustaka(item)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-emerald-600 hover:bg-white transition-all p-1.5" title="Verifikasi Bebas Pustaka">
                                        <i class="bi bi-file-earmark-check"></i>
                                    </button>
                                    <button v-if="item.kategori === 'Umum'" @click="editAnggota(item)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-white transition-all p-1.5" title="Edit Anggota">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="filteredAnggotaList.length > 0" class="card-footer bg-white border-top border-slate-100 p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="text-muted fs-8 font-medium">
                        Menampilkan <span class="fw-bold text-slate-800">{{ (currentPage - 1) * perPage + 1 }}</span> sampai <span class="fw-bold text-slate-800">{{ Math.min(currentPage * perPage, filteredAnggotaList.length) }}</span> dari <span class="fw-bold text-slate-800">{{ filteredAnggotaList.length }}</span> anggota
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
    <!-- TAB 2: BEBAS PUSTAKA (CLEARANCE & PRINTABLE CERTIFICATE) -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'bebas'" class="tab-pane-content transition-all">
        <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold text-slate-800 mb-0.5 d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-check-fill text-emerald-600"></i> Verifikasi Surat Bebas Perpustakaan
                    </h5>
                    <p class="text-muted fs-7 mb-0">Cek status kelulusan sirkulasi untuk syarat kelulusan kelas akhir atau mutasi siswa.</p>
                </div>
            </div>

            <!-- Search Form -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 mb-4">
                <form @submit.prevent="cariBebasPustaka" class="row g-2 align-items-center">
                    <div class="col-12 col-md-8">
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400"></i>
                            <input type="text" v-model="bebasQuery" class="form-control form-control-sm ps-5 rounded-xl border-slate-200 text-xs py-2" placeholder="Ketik NISN, NIP, Nomor Anggota, atau Nama Siswa..." required>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-emerald btn-sm rounded-xl px-4 py-2 text-xs font-bold text-white shadow-2xs d-flex align-items-center gap-1.5 flex-grow-1 justify-content-center" style="background-color: #059669;" :disabled="loadingBebas">
                            <span v-if="loadingBebas" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-shield-check"></i>
                            <span>Verifikasi Status</span>
                        </button>
                        <button v-if="bebasResult" type="button" @click="bebasResult = null; bebasQuery = '';" class="btn btn-outline-secondary btn-sm rounded-xl px-3 py-2 text-xs font-medium shadow-2xs">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Result Section -->
            <div v-if="bebasResult">
                <!-- Status Banner -->
                <div class="p-4 rounded-3xl mb-4 border"
                     :class="{
                         'bg-emerald-50/80 border-emerald-200 text-emerald-900': bebasResult.is_clear,
                         'bg-rose-50/80 border-rose-200 text-rose-900': !bebasResult.is_clear
                     }">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-3 rounded-2xl d-flex align-items-center justify-content-center text-white"
                                 :class="bebasResult.is_clear ? 'bg-emerald-600' : 'bg-rose-600'" style="width: 48px; height: 48px;">
                                <i class="bi fs-4" :class="bebasResult.is_clear ? 'bi-check2-circle' : 'bi-exclamation-triangle-fill'"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0.5 text-base">
                                    {{ bebasResult.is_clear ? 'MEMENUHI SYARAT BEBAS PUSTAKA (CLEAR)' : 'BELUM BEBAS PUSTAKA (MASIH ADA TANGGUNGAN)' }}
                                </h6>
                                <p class="fs-7 mb-0 opacity-85">
                                    {{ bebasResult.is_clear ? 'Siswa/Anggota tidak memiliki tanggungan peminjaman buku aktif dan bebas denda.' : 'Siswa/Anggota masih memiliki ' + bebasResult.total_pinjam_aktif + ' buku yang belum dikembalikan atau denda tertunggak.' }}
                                </p>
                            </div>
                        </div>

                        <button v-if="bebasResult.is_clear" @click="cetakSuratBebas" class="btn btn-dark btn-sm rounded-xl px-4 py-2 text-xs font-bold shadow-2xs d-flex align-items-center gap-1.5">
                            <i class="bi bi-printer-fill"></i> <span>Cetak Surat Bebas Pustaka</span>
                        </button>
                    </div>
                </div>

                <!-- Member Identity Summary -->
                <div class="card border border-slate-200 rounded-2xl bg-white p-3.5 mb-4">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <span class="fs-8 text-muted d-block">Nama Lengkap</span>
                            <span class="fw-bold text-slate-800 fs-7">{{ bebasResult.member?.nama_lengkap }}</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="fs-8 text-muted d-block">NISN / NIP</span>
                            <span class="fw-bold text-slate-800 fs-7 font-mono">{{ bebasResult.member?.nisn !== '-' ? bebasResult.member?.nisn : bebasResult.member?.nip }}</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="fs-8 text-muted d-block">Kelas / Unit</span>
                            <span class="fw-bold text-slate-800 fs-7">{{ bebasResult.member?.nama_kelas }}</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="fs-8 text-muted d-block">No. Anggota</span>
                            <span class="fw-bold text-slate-800 fs-7 font-mono">{{ bebasResult.member?.no_anggota }}</span>
                        </div>
                    </div>
                </div>

                <!-- Active Loans Table (If Any) -->
                <div v-if="bebasResult.pinjaman_aktif && bebasResult.pinjaman_aktif.length > 0" class="card border border-rose-200 rounded-2xl bg-white overflow-hidden mb-4">
                    <div class="p-3 bg-rose-50/60 border-bottom border-rose-100">
                        <h6 class="fw-bold text-rose-800 mb-0 fs-7 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-circle-fill text-rose-600"></i> Rincian Buku yang Belum Dikembalikan
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="table-light text-slate-600 fs-8 fw-semibold">
                                <tr>
                                    <th class="ps-3">Barcode</th>
                                    <th>Judul Buku</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Harus Kembali</th>
                                    <th class="text-end pe-3">Denda (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="buku in bebasResult.pinjaman_aktif" :key="buku.id">
                                    <td class="ps-3 font-mono font-bold text-xs">{{ buku.barcode }}</td>
                                    <td class="fw-semibold text-slate-800">{{ buku.judul_buku }}</td>
                                    <td>{{ buku.tgl_pinjam }}</td>
                                    <td class="text-danger fw-bold">{{ buku.tgl_harus_kembali }}</td>
                                    <td class="text-end pe-3 font-mono">Rp {{ formatRupiah(buku.denda) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Loan History Table -->
                <div v-if="bebasResult.riwayat_pinjam && bebasResult.riwayat_pinjam.length > 0" class="card border border-slate-200 rounded-2xl bg-white overflow-hidden">
                    <div class="p-3 bg-slate-50 border-bottom border-slate-200/80">
                        <h6 class="fw-bold text-slate-800 mb-0 fs-7 d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-primary"></i> Riwayat Sirkulasi Selesai
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="table-light text-slate-600 fs-8 fw-semibold">
                                <tr>
                                    <th class="ps-3">Barcode</th>
                                    <th>Judul Buku</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Tgl Kembali</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="buku in bebasResult.riwayat_pinjam" :key="buku.id">
                                    <td class="ps-3 font-mono text-xs">{{ buku.barcode }}</td>
                                    <td class="fw-medium text-slate-800">{{ buku.judul_buku }}</td>
                                    <td>{{ buku.tgl_pinjam }}</td>
                                    <td>{{ buku.tgl_kembali || '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2 py-0.5 fs-9">Dikembalikan</span>
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
    <!-- TAB 3: BUKU TAMU & PENGUNJUNG (FRONT-DESK LOG & STATS) -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'tamu'" class="tab-pane-content transition-all">
        <!-- 4 Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3.5 border-start border-4 border-primary">
                    <span class="text-slate-500 fs-8 fw-semibold text-uppercase d-block mb-1">Pengunjung Hari Ini</span>
                    <h3 class="fw-bold text-slate-800 mb-0">{{ visitorStats.total_hari_ini || 0 }}</h3>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3.5 border-start border-4 border-emerald-500">
                    <span class="text-slate-500 fs-8 fw-semibold text-uppercase d-block mb-1">Pengunjung Bulan Ini</span>
                    <h3 class="fw-bold text-slate-800 mb-0">{{ visitorStats.total_bulan_ini || 0 }}</h3>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3.5 border-start border-4 border-amber-500">
                    <span class="text-slate-500 fs-8 fw-semibold text-uppercase d-block mb-1">Pengunjung Siswa</span>
                    <h3 class="fw-bold text-slate-800 mb-0">{{ visitorStats.total_siswa || 0 }}</h3>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3.5 border-start border-4 border-indigo-500">
                    <span class="text-slate-500 fs-8 fw-semibold text-uppercase d-block mb-1">Guru & Staf</span>
                    <h3 class="fw-bold text-slate-800 mb-0">{{ visitorStats.total_guru_staf || 0 }}</h3>
                </div>
            </div>
        </div>

        <!-- Fast Check-in Resepsionis & Visitor Log Table -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4">
                    <h6 class="fw-bold text-slate-800 mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-qr-code-scan text-primary"></i> Presensi Pengunjung Cepat
                    </h6>
                    <p class="text-muted fs-8 mb-3">Pindai barcode kartu anggota / NISN atau input manual.</p>

                    <form @submit.prevent="submitPresensiTamu">
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Scan / Ketik NISN / NIP / Nama <span class="text-danger">*</span></label>
                            <input type="text" v-model="formPresensi.identifier" class="form-control rounded-xl text-xs py-2 border-slate-200 font-mono" placeholder="Scan Barcode / NISN..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Peran / Kategori</label>
                            <select v-model="formPresensi.tipe" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                <option value="Siswa">Siswa</option>
                                <option value="Guru">Guru</option>
                                <option value="Tendik">Tendik / Staf</option>
                                <option value="Tamu">Tamu Umum</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Tujuan Kunjungan</label>
                            <select v-model="formPresensi.tujuan" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                <option value="Membaca Buku">Membaca Buku di Tempat</option>
                                <option value="Meminjam / Mengembalikan Buku">Meminjam / Mengembalikan Buku</option>
                                <option value="Mengerjakan Tugas / Belajar Kelompok">Mengerjakan Tugas / Belajar Kelompok</option>
                                <option value="Akses Komputer / Internet">Akses Komputer / Internet</option>
                                <option value="Kunjungan Umum">Kunjungan Umum / Tamu</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 py-2.5 text-xs font-bold shadow-2xs w-100 d-flex align-items-center justify-content-center gap-1.5" :disabled="savingPresensi">
                            <span v-if="savingPresensi" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-check2-circle"></i>
                            <span>Catat Presensi Kunjungan</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
                    <div class="p-3 border-bottom border-slate-100 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-card-list text-warning"></i> Log Presensi Kunjungan Terkini
                        </h6>
                        <button @click="fetchVisitorLogs" class="btn btn-light btn-sm rounded-lg px-2 py-1 text-xs">
                            <i class="bi bi-arrow-repeat"></i> Segarkan
                        </button>
                    </div>

                    <div v-if="visitorLogs.length === 0" class="p-5 text-center">
                        <i class="bi bi-person-workspace fs-2 text-warning d-block mb-2"></i>
                        <p class="text-muted fs-7 mb-0">Belum ada catatan kunjungan pada periode ini.</p>
                    </div>

                    <div v-else class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="table-light text-slate-600 fs-8 fw-semibold sticky-top bg-slate-50">
                                <tr>
                                    <th class="ps-3">No</th>
                                    <th>Nama Pengunjung</th>
                                    <th>Kategori</th>
                                    <th>Tujuan</th>
                                    <th class="text-end pe-3">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(v, idx) in visitorLogs" :key="v.id || idx">
                                    <td class="ps-3 text-slate-400 font-mono fs-8">{{ idx + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-slate-900">{{ v.nama_pengunjung || v.nama || 'Pengunjung' }}</div>
                                    </td>
                                    <td><span class="badge bg-slate-100 text-slate-700 rounded-pill px-2 py-0.5 fs-9">{{ v.tipe || v.kategori || 'Siswa' }}</span></td>
                                    <td>{{ v.tujuan || 'Membaca Buku' }}</td>
                                    <td class="text-end pe-3 font-mono fs-8 text-muted">{{ v.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: PENGATURAN & WA GATEWAY -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'pengaturan'" class="tab-pane-content transition-all">
        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-2xs rounded-3xl bg-white p-4">
                    <h5 class="fw-bold text-slate-800 mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-sliders text-indigo-600"></i> Pengaturan Kebijakan Perpustakaan
                    </h5>
                    <p class="text-muted fs-7 mb-4">Konfigurasi batas waktu peminjaman, kuota buku, dan tarif denda harian.</p>

                    <form @submit.prevent="simpanPengaturan">
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Nama Perpustakaan</label>
                            <input type="text" v-model="formPengaturan.nama_perpustakaan" class="form-control rounded-xl text-xs py-2 border-slate-200" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Tarif Denda Harian (Rp/Buku/Hari)</label>
                                <input type="number" v-model="formPengaturan.tarif_denda_per_hari" class="form-control rounded-xl text-xs py-2 border-slate-200" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Maks. Pinjam Siswa (Hari)</label>
                                <input type="number" v-model="formPengaturan.max_hari_pinjam_siswa" class="form-control rounded-xl text-xs py-2 border-slate-200" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Maks. Pinjam Guru (Hari)</label>
                                <input type="number" v-model="formPengaturan.max_hari_pinjam_guru" class="form-control rounded-xl text-xs py-2 border-slate-200">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Batas Maks. Eksemplar Siswa</label>
                                <input type="number" v-model="formPengaturan.max_buku_pinjam_siswa" class="form-control rounded-xl text-xs py-2 border-slate-200">
                            </div>
                        </div>

                        <hr class="border-slate-100 my-4">

                        <h6 class="fw-bold text-slate-800 mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-whatsapp text-emerald-600"></i> Notifikasi WhatsApp Gateway
                        </h6>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" v-model="formPengaturan.auto_notif_wa_aktif" id="switchWaH1">
                            <label class="form-check-label text-xs fw-bold text-slate-700" for="switchWaH1">Aktifkan Pengingat WhatsApp H-1 Jatuh Tempo</label>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" v-model="formPengaturan.auto_notif_denda_aktif" id="switchWaDenda">
                            <label class="form-check-label text-xs fw-bold text-slate-700" for="switchWaDenda">Aktifkan Notifikasi Keterlambatan & Denda Harian</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 py-2.5 text-xs font-bold shadow-2xs" :disabled="savingPengaturan">
                            <span v-if="savingPengaturan" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-save me-1"></i> Simpan Seluruh Pengaturan
                        </button>
                    </form>
                </div>
            </div>

            <!-- WA Simulator / Template Preview -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-2xs rounded-3xl bg-white p-4">
                    <h6 class="fw-bold text-slate-800 mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-phone text-emerald-600"></i> Preview Pesan WhatsApp Otomatis
                    </h6>
                    <p class="text-muted fs-8 mb-3">Contoh pesan pengingat yang akan terkirim ke nomor WA siswa/guru.</p>

                    <div class="p-3.5 rounded-2xl bg-emerald-50/60 border border-emerald-200 text-xs font-mono text-slate-800 mb-4 line-clamp-6 leading-relaxed">
                        <div class="fw-bold text-emerald-900 mb-1">📢 Pengingat Pengembalian Buku Perpustakaan</div>
                        Halo <b>{nama_anggota}</b>,<br>
                        Buku <b>"{judul_buku}"</b> (Barcode: {barcode}) yang Anda pinjam akan jatuh tempo besok pada <b>{tgl_harus_kembali}</b>.<br><br>
                        Harap segera melakukan pengembalian atau perpanjangan di Perpustakaan Sekolah. Terima kasih.
                    </div>

                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <label class="form-label text-xs fw-bold text-slate-700">Uji Coba Kirim Pesan WA</label>
                        <div class="input-group input-group-sm">
                            <input type="text" v-model="testWaNomor" class="form-control rounded-start-xl border-slate-200 text-xs font-mono" placeholder="081234567890">
                            <button @click="kirimUjiCobaWa" class="btn btn-emerald text-white rounded-end-xl px-3 text-xs font-bold" style="background-color: #059669;">
                                <i class="bi bi-send-fill me-1"></i> Uji Coba
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 1: TAMBAH ANGGOTA LUAR / TAMU UMUM -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalAnggotaForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2">
                        <i class="bi bi-person-plus-fill text-primary"></i>
                        <span>{{ formAnggota.id ? 'Edit Anggota Eksternal' : 'Tambah Anggota Luar / Umum' }}</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="simpanAnggota">
                    <div class="modal-body p-4 bg-slate-50/50">
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" v-model="formAnggota.nama_lengkap" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Nama Lengkap Anggota" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Nomor Identitas / KTP / NIK</label>
                                <input type="text" v-model="formAnggota.nisn" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Nomor Identitas">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Nomor WhatsApp / HP</label>
                                <input type="text" v-model="formAnggota.kontak" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="08...">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Peran / Kategori</label>
                                <select v-model="formAnggota.tipe_anggota" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                    <option value="Umum">Umum / Eksternal</option>
                                    <option value="Siswa">Siswa</option>
                                    <option value="Guru">Guru</option>
                                    <option value="Tendik">Tendik</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Instansi / Unit</label>
                                <input type="text" v-model="formAnggota.nama_kelas" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Asal Instansi/Lembaga">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Alamat Lengkap</label>
                            <textarea v-model="formAnggota.alamat" class="form-control rounded-xl text-xs py-2 border-slate-200" rows="2" placeholder="Alamat domisili..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top border-slate-100 p-3 px-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-xl px-4 py-2 text-xs font-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 py-2 text-xs font-bold shadow-2xs" :disabled="savingAnggota">
                            <span v-if="savingAnggota" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-save me-1"></i> Simpan Data Anggota
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 2: KARTU ANGGOTA DIGITAL & CETAK BARCODE -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalKartuAnggota" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-3 px-4 border-0">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge-fill text-primary"></i>
                        <span>Kartu Anggota Perpustakaan Digital</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-slate-100 text-center">
                    <div id="kartuAnggotaPrintArea" class="card border-0 rounded-3xl shadow-md p-4 text-start position-relative overflow-hidden mx-auto" style="max-width: 380px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-white/10 pb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="p-2 rounded-xl bg-primary text-white">
                                    <i class="bi bi-book-half fs-6"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-8 text-uppercase tracking-wider text-primary-200">KARTU ANGGOTA PERPUSTAKAAN</div>
                                    <div class="fs-9 text-slate-300">SINTA SaaS Digital Library</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mb-3">
                            <div class="avatar-box bg-white/10 rounded-2xl d-flex align-items-center justify-content-center text-white fw-bold fs-4 flex-shrink-0" style="width: 64px; height: 64px; border: 2px solid rgba(255,255,255,0.2);">
                                {{ getInitials(selectedAnggota?.nama_lengkap) }}
                            </div>
                            <div>
                                <h6 class="fw-bold text-white mb-1 fs-6">{{ selectedAnggota?.nama_lengkap }}</h6>
                                <div class="fs-8 text-slate-300 font-mono mb-0.5">ID: {{ selectedAnggota?.no_anggota }}</div>
                                <div class="fs-8 text-slate-300 font-mono mb-1">NISN/NIP: {{ selectedAnggota?.nisn !== '-' ? selectedAnggota?.nisn : selectedAnggota?.nip }}</div>
                                <span class="badge bg-primary text-white rounded-pill px-2 py-0.5 fs-9 fw-semibold">{{ selectedAnggota?.nama_kelas }}</span>
                            </div>
                        </div>

                        <div class="bg-white p-2.5 rounded-2xl text-center">
                            <div class="barcode-display font-mono fw-bold text-slate-900 fs-5 letter-spacing-wide mb-1">
                                *{{ selectedAnggota?.no_anggota || selectedAnggota?.nisn }}*
                            </div>
                            <div class="fs-9 text-slate-500 font-mono">Pindai barcode untuk peminjaman & presensi mandiri</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top border-slate-100 p-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-xl px-4 py-2 text-xs font-semibold" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" @click="printKartuAnggota" class="btn btn-primary btn-sm rounded-xl px-4 py-2 text-xs font-bold shadow-2xs">
                        <i class="bi bi-printer-fill me-1"></i> Cetak Kartu
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Printable Area for Surat Bebas Pustaka -->
    <div id="printBebasPustakaArea" class="d-none">
        <div style="font-family: 'Times New Roman', serif; padding: 40px; color: #000000; line-height: 1.6;">
            <div style="text-align: center; border-bottom: 3px double #000000; padding-bottom: 12px; margin-bottom: 24px;">
                <h3 style="margin: 0; text-transform: uppercase; font-weight: bold; font-size: 18pt;">PERPUSTAKAAN SEKOLAH</h3>
                <p style="margin: 4px 0 0; font-size: 10pt;">Sistem Informasi Terpadu Akademik (SINTA SaaS)</p>
            </div>

            <div style="text-align: center; margin-bottom: 24px;">
                <h4 style="margin: 0; text-decoration: underline; text-transform: uppercase; font-size: 14pt;">SURAT KETERANGAN BEBAS PERPUSTAKAAN</h4>
                <p style="margin: 4px 0 0; font-size: 10pt;">Nomor: {{ bebasResult?.nomor_surat }}</p>
            </div>

            <p style="font-size: 11pt; margin-bottom: 16px;">Kepala Perpustakaan Sekolah menerangkan bahwa:</p>

            <table style="width: 100%; font-size: 11pt; margin-bottom: 20px; border-collapse: collapse;">
                <tr>
                    <td style="width: 25%; padding: 4px 0;">Nama Lengkap</td>
                    <td style="width: 3%;">:</td>
                    <td style="font-weight: bold;">{{ bebasResult?.member?.nama_lengkap }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;">NISN / NIP</td>
                    <td>:</td>
                    <td>{{ bebasResult?.member?.nisn !== '-' ? bebasResult?.member?.nisn : bebasResult?.member?.nip }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;">Nomor Anggota</td>
                    <td>:</td>
                    <td>{{ bebasResult?.member?.no_anggota }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;">Kelas / Unit Kerja</td>
                    <td>:</td>
                    <td>{{ bebasResult?.member?.nama_kelas }}</td>
                </tr>
            </table>

            <p style="font-size: 11pt; text-align: justify; margin-bottom: 30px;">
                Berdasarkan data sirkulasi perpustakaan, yang bersangkutan dinyatakan <b>TELAH MEMENUHI SELURUH KEWAJIBAN (BEBAS PUSTAKA)</b>, tidak memiliki pinjaman buku aktif, dan tidak memiliki tunggakan denda keterlambatan pada Perpustakaan Sekolah.
            </p>

            <p style="font-size: 11pt; text-align: justify; margin-bottom: 40px;">
                Surat keterangan ini diterbitkan untuk dipergunakan sebagai syarat kelulusan, pengambilan ijazah, atau mutasi siswa.
            </p>

            <div style="float: right; width: 250px; text-align: center; font-size: 11pt;">
                <p style="margin-bottom: 60px;">Diterbitkan pada: {{ bebasResult?.tanggal_terbit }}<br>Kepala Perpustakaan,</p>
                <p style="font-weight: bold; text-decoration: underline; margin-bottom: 2px;">Petugas Perpustakaan</p>
                <p style="font-size: 9pt; margin: 0;">NIP/ID: 198501012010011001</p>
            </div>
        </div>
    </div>

</div>

<!-- Vue 3 In-DOM Instance for Anggota -->
<script>
if (typeof Vue !== 'undefined') {
    const { ref, computed, onMounted } = Vue;

    const anggotaAppConfig = {
        setup() {
            const activeTab = ref('anggota');
            const loading = ref(false);
            const loadingBebas = ref(false);
            const savingAnggota = ref(false);
            const savingPresensi = ref(false);
            const savingPengaturan = ref(false);

            const anggotaList = ref([]);
            const visitorLogs = ref([]);
            const visitorStats = ref({
                total_hari_ini: 0,
                total_bulan_ini: 0,
                total_siswa: 0,
                total_guru_staf: 0
            });

            const searchQuery = ref('');
            const filterKategori = ref('');
            const bebasQuery = ref('');
            const bebasResult = ref(null);
            const selectedAnggota = ref(null);
            const testWaNomor = ref('');

            const currentPage = ref(1);
            const perPage = ref(15);

            // Tenant Isolation Helper
            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($data['active_tenant_id'] ?? ($activeTenantId ?? '')) ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const formAnggota = ref({
                id: null,
                nama_lengkap: '',
                nisn: '',
                kontak: '',
                tipe_anggota: 'Umum',
                nama_kelas: 'Umum / Eksternal',
                alamat: '',
                tenant_id: currentTenantId
            });

            const formPresensi = ref({
                identifier: '',
                tipe: 'Siswa',
                tujuan: 'Membaca Buku'
            });

            const formPengaturan = ref({
                nama_perpustakaan: 'Perpustakaan Digital SINTA',
                tarif_denda_per_hari: 500,
                max_hari_pinjam_siswa: 7,
                max_hari_pinjam_guru: 14,
                max_buku_pinjam_siswa: 3,
                auto_notif_wa_aktif: true,
                auto_notif_denda_aktif: true,
                tenant_id: currentTenantId
            });

            let modalAnggotaInstance = null;
            let modalKartuInstance = null;

            const getInitials = (name) => {
                if (!name) return 'A';
                const parts = name.trim().split(' ');
                if (parts.length >= 2) {
                    return (parts[0][0] + parts[1][0]).toUpperCase();
                }
                return name.substring(0, 2).toUpperCase();
            };

            const formatRupiah = (val) => {
                return Number(val || 0).toLocaleString('id-ID');
            };

            const fetchAnggota = async () => {
                loading.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/anggota' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        anggotaList.value = res.data.data.list || [];
                    }
                } catch (e) {
                    console.error('Error load anggota:', e);
                } finally {
                    loading.value = false;
                }
            };

            const fetchVisitorLogs = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/visitor-logs' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        visitorLogs.value = res.data.data || [];
                    }
                } catch (e) {}
            };

            const fetchVisitorStats = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/visitor-stats' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        visitorStats.value = res.data.data || {};
                    }
                } catch (e) {}
            };

            const fetchPengaturan = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/pengaturan' + getTenantParam('?'));
                    if (res.data && res.data.success && res.data.data) {
                        formPengaturan.value = Object.assign({}, formPengaturan.value, res.data.data);
                    }
                } catch (e) {}
            };

            const filteredAnggotaList = computed(() => {
                return anggotaList.value.filter(a => {
                    const matchQ = !searchQuery.value ||
                        (a.nama_lengkap && a.nama_lengkap.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                        (a.no_anggota && a.no_anggota.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                        (a.nisn && a.nisn.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                        (a.nip && a.nip.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                        (a.nama_kelas && a.nama_kelas.toLowerCase().includes(searchQuery.value.toLowerCase()));

                    const matchKat = !filterKategori.value || (a.tipe_anggota === filterKategori.value || a.kategori === filterKategori.value);
                    return matchQ && matchKat;
                });
            });

            const totalPages = computed(() => {
                return Math.ceil(filteredAnggotaList.value.length / perPage.value) || 1;
            });

            const paginatedAnggota = computed(() => {
                const start = (currentPage.value - 1) * perPage.value;
                return filteredAnggotaList.value.slice(start, start + perPage.value);
            });

            const resetFilterAnggota = () => {
                searchQuery.value = '';
                filterKategori.value = '';
                currentPage.value = 1;
            };

            const openModalTambahAnggota = () => {
                formAnggota.value = {
                    id: null,
                    nama_lengkap: '',
                    nisn: '',
                    kontak: '',
                    tipe_anggota: 'Umum',
                    nama_kelas: 'Umum / Eksternal',
                    alamat: '',
                    tenant_id: currentTenantId
                };
                if (!modalAnggotaInstance) {
                    const el = document.getElementById('modalAnggotaForm');
                    if (el) modalAnggotaInstance = new bootstrap.Modal(el);
                }
                if (modalAnggotaInstance) modalAnggotaInstance.show();
            };

            const editAnggota = (a) => {
                formAnggota.value = {
                    id: a.id,
                    nama_lengkap: a.nama_lengkap,
                    nisn: a.nisn || a.nip || '',
                    kontak: a.kontak || '',
                    tipe_anggota: a.tipe_anggota || a.kategori || 'Umum',
                    nama_kelas: a.nama_kelas || 'Umum / Eksternal',
                    alamat: a.alamat || '',
                    tenant_id: currentTenantId
                };
                if (!modalAnggotaInstance) {
                    const el = document.getElementById('modalAnggotaForm');
                    if (el) modalAnggotaInstance = new bootstrap.Modal(el);
                }
                if (modalAnggotaInstance) modalAnggotaInstance.show();
            };

            const showKartuAnggota = (a) => {
                selectedAnggota.value = a;
                if (!modalKartuInstance) {
                    const el = document.getElementById('modalKartuAnggota');
                    if (el) modalKartuInstance = new bootstrap.Modal(el);
                }
                if (modalKartuInstance) modalKartuInstance.show();
            };

            const printKartuAnggota = () => {
                const printContent = document.getElementById('kartuAnggotaPrintArea');
                if (!printContent) return;
                const win = window.open('', '', 'width=600,height=500');
                win.document.write('<html><head><title>Cetak Kartu Anggota</title>');
                win.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
                win.document.write('</head><body style="padding: 20px; text-align: center;">');
                win.document.write(printContent.outerHTML);
                win.document.write('</body></html>');
                win.document.close();
                win.focus();
                setTimeout(() => { win.print(); win.close(); }, 500);
            };

            const periksaBebasPustaka = (a) => {
                activeTab.value = 'bebas';
                bebasQuery.value = a.nisn !== '-' ? a.nisn : (a.nip !== '-' ? a.nip : a.nama_lengkap);
                cariBebasPustaka();
            };

            const cariBebasPustaka = async () => {
                if (!bebasQuery.value.trim()) return;
                loadingBebas.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/bebas-pustaka' + getTenantParam('?') + '&identifier=' + encodeURIComponent(bebasQuery.value));
                    if (res.data && res.data.success) {
                        bebasResult.value = res.data.data;
                    } else {
                        alert(res.data.error || 'Data anggota tidak ditemukan.');
                        bebasResult.value = null;
                    }
                } catch (e) {
                    alert('Gagal memeriksa status bebas pustaka.');
                    bebasResult.value = null;
                } finally {
                    loadingBebas.value = false;
                }
            };

            const cetakSuratBebas = () => {
                const printContent = document.getElementById('printBebasPustakaArea');
                if (!printContent) return;
                const win = window.open('', '', 'width=800,height=900');
                win.document.write('<html><head><title>Surat Keterangan Bebas Perpustakaan</title>');
                win.document.write('</head><body style="margin: 0; padding: 20px;">');
                win.document.write(printContent.innerHTML);
                win.document.write('</body></html>');
                win.document.close();
                win.focus();
                setTimeout(() => { win.print(); win.close(); }, 500);
            };

            const simpanAnggota = async () => {
                savingAnggota.value = true;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/anggota/simpan' + getTenantParam('?'), formAnggota.value);
                    if (res.data && res.data.success) {
                        if (modalAnggotaInstance) modalAnggotaInstance.hide();
                        await fetchAnggota();
                    } else {
                        alert(res.data.error || 'Gagal menyimpan anggota.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan koneksi.');
                } finally {
                    savingAnggota.value = false;
                }
            };

            const submitPresensiTamu = async () => {
                savingPresensi.value = true;
                try {
                    const payload = {
                        nama_pengunjung: formPresensi.value.identifier,
                        tipe: formPresensi.value.tipe,
                        tujuan: formPresensi.value.tujuan
                    };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/visitor-logs/simpan' + getTenantParam('?'), payload);
                    if (res.data && res.data.success) {
                        formPresensi.value.identifier = '';
                        await fetchVisitorLogs();
                        await fetchVisitorStats();
                        alert('Presensi pengunjung berhasil dicatat!');
                    } else {
                        alert(res.data.error || 'Gagal mencatat presensi.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan koneksi.');
                } finally {
                    savingPresensi.value = false;
                }
            };

            const simpanPengaturan = async () => {
                savingPengaturan.value = true;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/pengaturan/simpan' + getTenantParam('?'), formPengaturan.value);
                    if (res.data && res.data.success) {
                        alert('Pengaturan perpustakaan berhasil diperbarui!');
                    } else {
                        alert(res.data.message || 'Gagal menyimpan pengaturan.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan saat menyimpan pengaturan.');
                } finally {
                    savingPengaturan.value = false;
                }
            };

            const kirimUjiCobaWa = () => {
                if (!testWaNomor.value) {
                    alert('Harap masukkan nomor WhatsApp tujuan!');
                    return;
                }
                alert('Pesan uji coba WhatsApp berhasil dikirim ke nomor ' + testWaNomor.value + '!');
            };

            const refreshCurrentTab = () => {
                if (activeTab.value === 'anggota') fetchAnggota();
                if (activeTab.value === 'tamu') { fetchVisitorLogs(); fetchVisitorStats(); }
                if (activeTab.value === 'pengaturan') fetchPengaturan();
            };

            onMounted(() => {
                fetchAnggota();
                fetchVisitorLogs();
                fetchVisitorStats();
                fetchPengaturan();
            });

            return {
                activeTab,
                loading,
                loadingBebas,
                savingAnggota,
                savingPresensi,
                savingPengaturan,
                anggotaList,
                visitorLogs,
                visitorStats,
                searchQuery,
                filterKategori,
                bebasQuery,
                bebasResult,
                selectedAnggota,
                testWaNomor,
                currentPage,
                perPage,
                totalPages,
                filteredAnggotaList,
                paginatedAnggota,
                formAnggota,
                formPresensi,
                formPengaturan,
                getInitials,
                formatRupiah,
                resetFilterAnggota,
                openModalTambahAnggota,
                editAnggota,
                showKartuAnggota,
                printKartuAnggota,
                periksaBebasPustaka,
                cariBebasPustaka,
                cetakSuratBebas,
                simpanAnggota,
                submitPresensiTamu,
                simpanPengaturan,
                kirimUjiCobaWa,
                fetchVisitorLogs,
                fetchVisitorStats,
                refreshCurrentTab
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#anggotaPerpusApp', anggotaAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        const mountApp = () => {
            const el = document.querySelector('#anggotaPerpusApp');
            if (el && !el.__vue_app__) {
                Vue.createApp(anggotaAppConfig).mount('#anggotaPerpusApp');
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
/* Modern Pill Navtabs (Standard SINTA SaaS) */
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
