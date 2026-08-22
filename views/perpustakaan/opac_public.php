<?php
/**
 * View: OPAC (Online Public Access Catalog) Perpustakaan Digital
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
?>
<div id="opacPublicApp" v-cloak class="container-fluid px-0">
    <!-- ═══════════════════════════════════════════════════════════════════════
         HERO BANNER & MULTI-TENANT SAAS SWITCHER (STANDAR GAMBAR 1)
         ═══════════════════════════════════════════════════════════════════════ -->
    <?php
    $heroIcon = 'bi-search';
    $heroBadge = 'Katalog Publik Terbuka (OPAC)';
    $heroTitle = 'Pencarian Koleksi Perpustakaan Digital';
    $heroDesc = 'Telusuri koleksi buku teks pelajaran, fiksi, non-fiksi, karya ilmiah, modul ajar, dan e-book digital sekolah.';
    $heroButtons = '
        <a href="' . $this->getBaseUrl() . '/perpustakaan/katalog" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-journal-album me-1.5"></i> Kelola Katalog
        </a>
        <a href="' . $this->getBaseUrl() . '/perpustakaan/riwayat-saya" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 border border-white/20 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-clock-history me-1.5"></i> Riwayat Pinjaman Saya
        </a>
    ';
    include __DIR__ . '/_tenant_filter.php';
    ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         FLOATING SEARCH CARD & QUICK DDC FILTER PILLS
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3.5 p-md-4 mb-4">
        <!-- Main Search Bar -->
        <div class="row g-2.5 align-items-center mb-3">
            <div class="col-12 col-md-9 col-lg-10 position-relative">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3.5 text-slate-400 fs-6 pointer-events-none"></i>
                <input type="text" v-model="searchQuery" @input="debounceSearch()"
                       class="form-control form-control-lg ps-5 pe-4 rounded-xl border border-slate-200 text-sm font-medium bg-slate-50/70 focus:bg-white focus:border-blue-500 shadow-2xs transition"
                       placeholder="Cari judul buku, barcode, nama pengarang, penerbit, atau nomor panggil DDC..."
                       aria-label="Cari buku perpustakaan" autofocus>
                <button v-if="searchQuery" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-3 text-slate-400 hover:text-slate-600 p-0" @click="searchQuery = ''; fetchBooks()" aria-label="Reset pencarian">
                    <i class="bi bi-x-circle-fill fs-6"></i>
                </button>
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <button type="button" class="btn btn-primary btn-lg w-100 rounded-xl font-bold text-xs d-flex align-items-center justify-content-center gap-1.5 shadow-2xs py-2.5" @click="fetchBooks()">
                    <i class="bi bi-search"></i>
                    <span>Cari Koleksi</span>
                </button>
            </div>
        </div>

        <!-- DDC Quick Categories Filter -->
        <div class="d-flex align-items-center gap-2 pt-2.5 border-t border-slate-100 flex-wrap">
            <span class="text-slate-500 text-xs font-bold me-1 flex-shrink-0 d-inline-flex align-items-center gap-1">
                <i class="bi bi-tags-fill text-blue-600"></i> Klasifikasi DDC:
            </span>
            <div class="d-flex flex-wrap gap-1.5 align-items-center">
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('')">
                    Semua Koleksi
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '000' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('000')">
                    000 Umum &amp; Komputer
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '100' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('100')">
                    100 Filsafat
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '200' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('200')">
                    200 Agama &amp; Budi Pekerti
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '300' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('300')">
                    300 Ilmu Sosial
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '400' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('400')">
                    400 Bahasa &amp; Sastra
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '500' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('500')">
                    500 Sains &amp; Matematika
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '600' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('600')">
                    600 Teknologi &amp; Terapan
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '700' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('700')">
                    700 Kesenian &amp; Olahraga
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '800' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('800')">
                    800 Karya Sastra &amp; Fiksi
                </button>
                <button type="button" class="badge rounded-pill text-xs font-semibold px-3 py-1.5 border transition cursor-pointer"
                        :class="activeDdc === '900' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                        @click="setDdcFilter('900')">
                    900 Sejarah &amp; Geografi
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         RESULTS SUMMARY TOOLBAR & VIEW TOGGLE
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold text-slate-800 fs-6 mb-0">Daftar Koleksi Tersedia</h5>
            <span class="badge bg-blue-50 text-blue-700 border border-blue-200 font-bold px-2.5 py-1 rounded-pill text-xs">
                {{ filteredBooks.length }} Judul Buku
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button v-if="searchQuery || activeDdc" type="button" class="btn btn-sm btn-outline-secondary rounded-xl px-3 py-1.5 text-xs font-semibold shadow-2xs bg-white" @click="resetSearch()">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
            </button>
            <button type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-2xs bg-white text-slate-700" @click="fetchBooks()">
                <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> Segarkan
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MAIN BOOK GRID / SKELETON LOADING / EMPTY STATE
         ═══════════════════════════════════════════════════════════════════════ -->
    <!-- Loading State -->
    <div v-if="loading" class="card border-0 shadow-2xs rounded-2xl bg-white p-5 text-center my-3">
        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
        <span class="text-slate-500 text-xs font-semibold">Menghubungkan ke katalog perpustakaan digital...</span>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredBooks.length === 0" class="card border-0 shadow-2xs rounded-2xl bg-white p-5 text-center my-3">
        <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-2 mb-3 shadow-2xs">
            <i class="bi bi-journal-x"></i>
        </div>
        <h6 class="fw-bold text-slate-800 text-base mb-1">Koleksi Buku Tidak Ditemukan</h6>
        <p class="text-slate-400 text-xs mb-3 mx-auto" style="max-width: 440px;">
            Tidak ditemukan judul buku atau pengarang yang sesuai dengan kata kunci "{{ searchQuery || activeDdc }}".
        </p>
        <div>
            <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-4 py-2 text-xs shadow-2xs" @click="resetSearch()">
                <i class="bi bi-arrow-counterclockwise me-1.5"></i> Tampilkan Semua Koleksi
            </button>
        </div>
    </div>

    <!-- Book Grid Cards -->
    <div v-else class="row g-3 mb-5">
        <div v-for="b in paginatedBooks" :key="b.id" class="col-12 col-sm-6 col-md-4 col-xl-3">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white h-100 overflow-hidden transition hover:-translate-y-1 hover:shadow-xs d-flex flex-column">
                <!-- Cover Thumbnail Area -->
                <div class="position-relative d-flex align-items-center justify-content-center overflow-hidden"
                     style="height: 180px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #06b6d4 100%);">
                    <img v-if="b.cover" :src="b.cover" class="w-100 h-100 object-fit-cover" alt="Cover Buku">
                    <div v-else class="text-center text-white/90 p-3">
                        <i class="bi bi-book fs-1 mb-1 d-block text-white/80"></i>
                        <span class="font-bold text-xs d-block text-white/90 text-truncate px-2">{{ b.judul }}</span>
                    </div>

                    <!-- E-Book Badge -->
                    <span v-if="b.is_ebook" class="badge bg-purple-600 text-white position-absolute top-0 end-0 m-2.5 rounded-pill px-2 py-0.5 text-[10px] font-bold shadow-2xs">
                        <i class="bi bi-file-earmark-pdf me-0.5"></i> E-Book
                    </span>
                    <!-- DDC Badge -->
                    <span class="badge bg-slate-900/80 text-white backdrop-blur position-absolute bottom-0 start-0 m-2.5 rounded-lg px-2 py-1 text-[10px] font-mono font-bold shadow-2xs">
                        DDC {{ b.klasifikasi_ddc || '000' }}
                    </span>
                </div>

                <!-- Card Content Details -->
                <div class="p-3.5 d-flex flex-column flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-1.5">
                        <span class="badge bg-slate-100 text-slate-700 rounded-md px-2 py-0.5 text-[10px] font-semibold text-truncate" style="max-width: 140px;">
                            {{ b.kategori || b.jenis_buku || 'Koleksi Umum' }}
                        </span>
                        <span v-if="(parseInt(b.total_tersedia) || parseInt(b.total_eksemplar) || 1) > 0" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2 py-0.5 text-[10px] font-bold">
                            <i class="bi bi-check-circle me-0.5"></i> Tersedia ({{ b.total_tersedia || b.total_eksemplar || 1 }})
                        </span>
                        <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2 py-0.5 text-[10px] font-bold">
                            <i class="bi bi-clock-history me-0.5"></i> Dipinjam
                        </span>
                    </div>

                    <h6 class="fw-bold text-slate-900 fs-7 mb-1 leading-snug line-clamp-2" :title="b.judul" style="min-height: 2.3rem;">
                        {{ b.judul }}
                    </h6>

                    <div class="text-[11px] text-slate-500 mb-1 text-truncate">
                        <i class="bi bi-person me-1 text-slate-400"></i>{{ b.penulis || b.pengarang || 'Penulis Anonim' }}
                    </div>

                    <div class="text-[11px] text-slate-400 mb-3 text-truncate">
                        <i class="bi bi-building me-1"></i>{{ b.penerbit || '-' }} <span v-if="b.tahun_terbit">({{ b.tahun_terbit }})</span>
                    </div>

                    <div class="mt-auto pt-2.5 border-t border-slate-100 d-flex align-items-center justify-content-between gap-2">
                        <span class="text-[11px] font-mono text-slate-400 font-semibold text-truncate">
                            <i class="bi bi-upc-scan me-1"></i>{{ b.isbn || b.barcode || '-' }}
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-xl font-bold px-3 py-1 text-xs shadow-2xs" @click="showDetail(b)">
                            Detail
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination Bar -->
    <div v-if="filteredBooks.length > perPage" class="card border-0 shadow-2xs rounded-2xl bg-white p-3 mb-5 d-flex flex-wrap align-items-center justify-content-between gap-2 text-xs text-slate-500">
        <span>Menampilkan {{ (currentPage - 1) * perPage + 1 }} s/d {{ Math.min(currentPage * perPage, filteredBooks.length) }} dari {{ filteredBooks.length }} buku</span>
        <div class="d-flex align-items-center gap-1">
            <button type="button" class="btn btn-xs btn-outline-secondary rounded-lg px-2.5 py-1" :disabled="currentPage === 1" @click="currentPage--">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="px-2 font-bold">{{ currentPage }} / {{ totalPages }}</span>
            <button type="button" class="btn btn-xs btn-outline-secondary rounded-lg px-2.5 py-1" :disabled="currentPage === totalPages" @click="currentPage++">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL DETAIL KOLEKSI BUKU
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalDetailBuku" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden" v-if="selectedBook">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-500/20 text-blue-400 d-flex align-items-center justify-content-center fs-5">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0">Detail Informasi Koleksi Pustaka</h5>
                            <small class="text-slate-400 text-xs">Informasi lengkap bibliografi &amp; eksemplar buku</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-slate-50/50">
                    <div class="row g-4">
                        <!-- Left: Cover -->
                        <div class="col-12 col-md-4 text-center">
                            <div class="rounded-2xl overflow-hidden shadow-xs mb-3 mx-auto" style="max-width: 220px; height: 280px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                                <img v-if="selectedBook.cover" :src="selectedBook.cover" class="w-100 h-100 object-fit-cover" alt="Cover">
                                <div v-else class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white p-3">
                                    <i class="bi bi-book fs-1 mb-2"></i>
                                    <span class="font-bold text-xs text-white/90">{{ selectedBook.judul }}</span>
                                </div>
                            </div>
                            <span v-if="selectedBook.is_ebook" class="badge bg-purple-100 text-purple-700 border border-purple-200 rounded-pill px-3 py-1 text-xs font-bold">
                                <i class="bi bi-file-earmark-pdf me-1"></i> E-Book Tersedia
                            </span>
                        </div>

                        <!-- Right: Details -->
                        <div class="col-12 col-md-8">
                            <h5 class="font-bold text-slate-900 fs-6 mb-1">{{ selectedBook.judul }}</h5>
                            <div class="d-flex flex-wrap gap-1.5 mb-3">
                                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 rounded-pill px-2.5 py-1 text-[11px] font-bold">
                                    DDC {{ selectedBook.klasifikasi_ddc || '000' }}
                                </span>
                                <span class="badge bg-slate-100 text-slate-700 rounded-pill px-2.5 py-1 text-[11px] font-semibold">
                                    {{ selectedBook.kategori || selectedBook.jenis_buku || 'Umum' }}
                                </span>
                                <span class="badge bg-slate-100 text-slate-700 font-mono rounded-pill px-2.5 py-1 text-[11px]">
                                    ISBN: {{ selectedBook.isbn || '-' }}
                                </span>
                            </div>

                            <div class="bg-white rounded-2xl border border-slate-200/80 p-3.5 mb-3">
                                <div class="row g-2 text-xs">
                                    <div class="col-4 text-slate-400 font-medium">Pengarang / Penulis</div>
                                    <div class="col-8 font-bold text-slate-800">{{ selectedBook.penulis || selectedBook.pengarang || '-' }}</div>

                                    <div class="col-4 text-slate-400 font-medium">Penerbit &amp; Tahun</div>
                                    <div class="col-8 text-slate-800">{{ selectedBook.penerbit || '-' }} ({{ selectedBook.tahun_terbit || '-' }})</div>

                                    <div class="col-4 text-slate-400 font-medium">Tempat Terbit</div>
                                    <div class="col-8 text-slate-800">{{ selectedBook.kota_terbit || '-' }}</div>

                                    <div class="col-4 text-slate-400 font-medium">Jumlah Halaman</div>
                                    <div class="col-8 text-slate-800">{{ selectedBook.jumlah_halaman ? selectedBook.jumlah_halaman + ' hlm' : '-' }}</div>

                                    <div class="col-4 text-slate-400 font-medium">Lokasi Rak</div>
                                    <div class="col-8 font-bold text-blue-700">{{ selectedBook.nama_rak || selectedBook.lokasi_rak || 'Rak Koleksi Umum' }}</div>

                                    <div class="col-4 text-slate-400 font-medium">Ketersediaan Fisik</div>
                                    <div class="col-8">
                                        <span v-if="(parseInt(selectedBook.total_tersedia) || parseInt(selectedBook.total_eksemplar) || 1) > 0" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 text-[11px] font-bold">
                                            <i class="bi bi-check-circle me-1"></i> Tersedia di Perpustakaan ({{ selectedBook.total_tersedia || selectedBook.total_eksemplar || 1 }} Eksemplar)
                                        </span>
                                        <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2.5 py-1 text-[11px] font-bold">
                                            <i class="bi bi-hourglass-split me-1"></i> Sedang Dipinjam
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedBook.sinopsis || selectedBook.deskripsi" class="p-3 bg-white rounded-2xl border border-slate-200/80 text-xs">
                                <span class="font-bold text-slate-700 d-block mb-1">Sinopsis &amp; Ringkasan:</span>
                                <p class="text-slate-500 mb-0 leading-relaxed">{{ selectedBook.sinopsis || selectedBook.deskripsi }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top border-slate-100 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="text-slate-400 text-xs">Silakan kunjungi perpustakaan sekolah untuk peminjaman fisik.</span>
                    <button type="button" class="btn btn-secondary btn-sm rounded-xl px-4 py-2 text-xs font-semibold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, computed, onMounted } = Vue;

    const opacAppConfig = {
        setup() {
            const loading = ref(false);
            const books = ref([]);
            const searchQuery = ref('<?= htmlspecialchars($data['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>');
            const activeDdc = ref('');
            const selectedBook = ref(null);
            const currentPage = ref(1);
            const perPage = ref(16);
            let detailModalInstance = null;

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($data['active_tenant_id'] ?? ($activeTenantId ?? ''), ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const fetchBooks = async () => {
                loading.value = true;
                try {
                    let url = '<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/katalog' + getTenantParam('?');
                    if (searchQuery.value) {
                        url += `&search=${encodeURIComponent(searchQuery.value)}`;
                    }
                    const res = await axios.get(url);
                    if (res.data && res.data.success) {
                        if (Array.isArray(res.data.data)) {
                            books.value = res.data.data;
                        } else if (res.data.data && Array.isArray(res.data.data.list)) {
                            books.value = res.data.data.list;
                        } else {
                            books.value = [];
                        }
                    }
                } catch (e) {
                    console.error('Error load OPAC books:', e);
                } finally {
                    loading.value = false;
                }
            };

            let searchTimeout = null;
            const debounceSearch = () => {
                currentPage.value = 1;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchBooks();
                }, 350);
            };

            const filteredBooks = computed(() => {
                return books.value.filter(b => {
                    const q = searchQuery.value.toLowerCase().trim();
                    const matchQ = !q ||
                        (b.judul && b.judul.toLowerCase().includes(q)) ||
                        (b.penulis && b.penulis.toLowerCase().includes(q)) ||
                        (b.pengarang && b.pengarang.toLowerCase().includes(q)) ||
                        (b.penerbit && b.penerbit.toLowerCase().includes(q)) ||
                        (b.isbn && b.isbn.toLowerCase().includes(q)) ||
                        (b.klasifikasi_ddc && b.klasifikasi_ddc.toLowerCase().includes(q));

                    const ddcCode = (b.klasifikasi_ddc || '').toString().trim();
                    const matchDdc = !activeDdc.value || (ddcCode && ddcCode.startsWith(activeDdc.value.substring(0, 1)));

                    return matchQ && matchDdc && (b.status_opac !== 0 && b.status_opac !== false);
                });
            });

            const totalPages = computed(() => {
                return Math.ceil(filteredBooks.value.length / perPage.value) || 1;
            });

            const paginatedBooks = computed(() => {
                const start = (currentPage.value - 1) * perPage.value;
                return filteredBooks.value.slice(start, start + perPage.value);
            });

            const setDdcFilter = (code) => {
                activeDdc.value = code;
                currentPage.value = 1;
            };

            const resetSearch = () => {
                searchQuery.value = '';
                activeDdc.value = '';
                currentPage.value = 1;
                fetchBooks();
            };

            const showDetail = (b) => {
                selectedBook.value = b;
                const el = document.getElementById('modalDetailBuku');
                if (el && typeof bootstrap !== 'undefined') {
                    detailModalInstance = bootstrap.Modal.getOrCreateInstance(el);
                    detailModalInstance.show();
                }
            };

            onMounted(() => {
                fetchBooks();
            });

            return {
                loading,
                books,
                searchQuery,
                activeDdc,
                selectedBook,
                currentPage,
                perPage,
                totalPages,
                filteredBooks,
                paginatedBooks,
                setDdcFilter,
                resetSearch,
                debounceSearch,
                showDetail,
                fetchBooks
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#opacPublicApp', opacAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(opacAppConfig).mount('#opacPublicApp');
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
