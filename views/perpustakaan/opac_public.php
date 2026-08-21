<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title'] ?? 'OPAC Publik — Katalog Perpustakaan Digital') ?></title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Vue 3 & Axios -->
    <script src="https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #2563eb 100%);
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            --card-hover-shadow: 0 20px 30px -10px rgba(37, 99, 235, 0.2);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }

        .hero-banner {
            background: var(--primary-gradient);
            color: white;
            padding: 4.5rem 1rem 5.5rem 1rem;
            border-radius: 0 0 2.5rem 2.5rem;
            position: relative;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25);
        }

        .search-card-container {
            margin-top: -3.5rem;
            z-index: 10;
            position: relative;
        }

        .book-card {
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--card-shadow);
            background: #ffffff;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--card-hover-shadow);
            border-color: #93c5fd;
        }

        .book-cover-wrap {
            height: 180px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 1rem 1rem 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 3.5rem;
            overflow: hidden;
            position: relative;
        }

        .custom-filter-pill {
            border-radius: 9999px;
            padding: 5px 14px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            cursor: pointer;
            text-decoration: none;
        }

        .custom-filter-pill:hover, .custom-filter-pill.active {
            background-color: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        [v-cloak] { display: none !important; }
    </style>
</head>
<body>

<div id="opacApp" v-cloak>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-slate-900 py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= $this->getBaseUrl() ?>/perpustakaan/opac">
                <i class="bi bi-journal-bookmark-fill text-primary fs-3"></i>
                <span class="text-white">SINTA OPAC <span class="badge bg-primary fs-9 rounded-pill ms-1">Publik</span></span>
            </a>
            <div class="d-flex gap-2">
                <a href="<?= $this->getBaseUrl() ?>/login" class="btn btn-outline-light btn-sm rounded-pill px-3.5 py-1.5 fs-7 font-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login Anggota
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Search Section -->
    <header class="hero-banner text-center">
        <div class="container" style="max-width: 800px;">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill fs-8 fw-bold mb-3 shadow-2xs">
                <i class="bi bi-search me-1"></i> Online Public Access Catalog
            </span>
            <h1 class="fw-extrabold display-6 text-white mb-2">Pencarian Koleksi Perpustakaan</h1>
            <p class="text-slate-300 fs-6 mb-0">Temukan buku teks pelajaran, novel fiksi, publikasi karya ilmiah, modul pembelajaran, dan e-book digital.</p>
        </div>
    </header>

    <!-- Main Container -->
    <main class="container mb-5">
        <!-- Floating Search Box Card -->
        <div class="row justify-content-center search-card-container mb-5">
            <div class="col-12 col-lg-10">
                <div class="card border-0 shadow-lg rounded-3xl p-4 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-9 position-relative">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-5"></i>
                            <input type="text" v-model="searchQuery" @input="filterBooks"
                                   class="form-control form-control-lg ps-5 rounded-2xl border-slate-200 bg-slate-50 fs-6 font-medium"
                                   placeholder="Ketik judul buku, pengarang, penerbit, atau kode DDC..." autofocus>
                        </div>
                        <div class="col-12 col-md-3">
                            <button @click="filterBooks" class="btn btn-primary btn-lg w-100 rounded-2xl fw-bold fs-6 shadow-2xs">
                                <i class="bi bi-search me-1"></i> Cari Buku
                            </button>
                        </div>
                    </div>

                    <!-- DDC Quick Categories Filter -->
                    <div class="d-flex flex-wrap gap-1.5 mt-3 pt-3 border-top border-slate-100 align-items-center">
                        <small class="fw-bold text-slate-500 me-2 fs-8"><i class="bi bi-tags me-1"></i> Klasifikasi DDC:</small>
                        <button class="custom-filter-pill" :class="{'active': activeDdc === ''}" @click="setDdcFilter('')">Semua</button>
                        <button class="custom-filter-pill" :class="{'active': activeDdc === '000'}" @click="setDdcFilter('000')">000 Karya Umum</button>
                        <button class="custom-filter-pill" :class="{'active': activeDdc === '300'}" @click="setDdcFilter('300')">300 Ilmu Sosial</button>
                        <button class="custom-filter-pill" :class="{'active': activeDdc === '500'}" @click="setDdcFilter('500')">500 Sains & Matematika</button>
                        <button class="custom-filter-pill" :class="{'active': activeDdc === '600'}" @click="setDdcFilter('600')">600 Teknologi</button>
                        <button class="custom-filter-pill" :class="{'active': activeDdc === '800'}" @click="setDdcFilter('800')">800 Sastra</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-slate-900 mb-0">Daftar Koleksi Tersedia</h4>
                <small class="text-muted fs-7">Menampilkan {{ filteredBooks.length }} judul buku katalog publik.</small>
            </div>
            <div v-if="searchQuery || activeDdc">
                <button @click="resetSearch" class="btn btn-sm btn-outline-secondary rounded-xl px-3 py-1.5 text-xs font-semibold">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Pencarian
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="p-5 text-center">
            <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
            <p class="text-muted fs-7 mb-0">Memuat katalog buku...</p>
        </div>

        <!-- Seamless Empty State -->
        <div v-else-if="filteredBooks.length === 0" class="card border-0 shadow-2xs rounded-3xl p-5 text-center bg-white">
            <div class="d-inline-flex p-4 rounded-3xl bg-blue-50 text-blue-600 mb-3 mx-auto shadow-2xs">
                <i class="bi bi-book fs-1"></i>
            </div>
            <h5 class="fw-bold text-slate-800 mb-1">Buku Tidak Ditemukan</h5>
            <p class="text-muted fs-7 mx-auto mb-4" style="max-width: 420px;">
                Koleksi buku dengan kata kunci "{{ searchQuery }}" tidak ditemukan di dalam sistem. Coba gunakan kata kunci lain atau pilih kategori klasifikasi DDC di atas.
            </p>
            <div>
                <button @click="resetSearch" class="btn btn-primary btn-sm rounded-xl px-4 py-2 text-xs font-bold shadow-2xs">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Tampilkan Semua Buku
                </button>
            </div>
        </div>

        <!-- Book Cards Grid -->
        <div v-else class="row g-4">
            <div v-for="b in filteredBooks" :key="b.id" class="col-12 col-sm-6 col-lg-3">
                <div class="book-card">
                    <div class="book-cover-wrap">
                        <img v-if="b.cover" :src="b.cover" class="w-100 h-100 object-fit-cover" alt="Cover">
                        <i v-else class="bi bi-journal-richtext"></i>
                        <span v-if="b.is_ebook" class="badge bg-primary position-absolute top-0 end-0 m-2.5 rounded-pill px-2 py-0.5 fs-9 font-semibold">
                            <i class="bi bi-file-earmark-pdf me-1"></i> E-Book
                        </span>
                    </div>

                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <div class="d-flex align-items-center gap-1.5 mb-1.5">
                            <span class="badge bg-sky-50 text-sky-700 border border-sky-200/80 rounded-md px-1.5 py-0.5 fs-9 font-bold">
                                DDC: {{ b.klasifikasi_ddc || '000' }}
                            </span>
                            <span class="badge bg-slate-100 text-slate-700 rounded-pill px-2 py-0.5 fs-9">
                                {{ b.kategori || b.jenis_buku || 'Umum' }}
                            </span>
                        </div>

                        <h6 class="fw-bold text-slate-900 mb-1 text-truncate-2 fs-7" :title="b.judul" style="min-height: 2.4rem;">
                            {{ b.judul }}
                        </h6>

                        <small class="text-slate-600 mb-0.5 text-truncate fw-medium">
                            <i class="bi bi-person me-1 text-muted"></i>{{ b.penulis || b.pengarang || '-' }}
                        </small>
                        <small class="text-muted mb-3 text-truncate fs-8">
                            <i class="bi bi-building me-1"></i>{{ b.penerbit || '-' }} ({{ b.tahun_terbit || '-' }})
                        </small>

                        <div class="mt-auto pt-2 border-top border-slate-100 d-flex justify-content-between align-items-center">
                            <span v-if="(b.total_tersedia || 1) > 0" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2 py-0.5 fs-9 font-semibold">
                                <i class="bi bi-check-circle me-1"></i> Tersedia ({{ b.total_tersedia || b.total_eksemplar || 1 }})
                            </span>
                            <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2 py-0.5 fs-9 font-semibold">
                                <i class="bi bi-hourglass-split me-1"></i> Dipinjam
                            </span>

                            <button @click="showDetail(b)" class="btn btn-outline-primary btn-sm rounded-lg px-2.5 py-1 text-xs font-semibold">
                                Detail
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Detail Buku -->
    <div class="modal fade" id="modalDetailBuku" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden" v-if="selectedBook">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2">
                        <i class="bi bi-journal-text text-primary"></i>
                        <span>Detail Koleksi Buku</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-slate-50/50">
                    <h5 class="fw-bold text-slate-900 mb-2">{{ selectedBook.judul }}</h5>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                            DDC: {{ selectedBook.klasifikasi_ddc || '000' }}
                        </span>
                        <span class="badge bg-slate-100 text-slate-700 rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                            ISBN: {{ selectedBook.isbn || '-' }}
                        </span>
                    </div>

                    <div class="p-3 rounded-2xl bg-white border border-slate-100 mb-3">
                        <div class="row g-2 text-xs">
                            <div class="col-4 text-slate-500 font-medium">Pengarang</div>
                            <div class="col-8 fw-bold text-slate-800">{{ selectedBook.penulis || selectedBook.pengarang || '-' }}</div>

                            <div class="col-4 text-slate-500 font-medium">Penerbit</div>
                            <div class="col-8 text-slate-800">{{ selectedBook.penerbit || '-' }} ({{ selectedBook.tahun_terbit || '-' }})</div>

                            <div class="col-4 text-slate-500 font-medium">Kategori</div>
                            <div class="col-8 text-slate-800">{{ selectedBook.kategori || selectedBook.jenis_buku || 'Umum' }}</div>

                            <div class="col-4 text-slate-500 font-medium">Status Fisik</div>
                            <div class="col-8">
                                <span v-if="(selectedBook.total_tersedia || 1) > 0" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2 py-0.5 fs-9">
                                    Tersedia di Perpustakaan ({{ selectedBook.total_tersedia || 1 }} Eksemplar)
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2 py-0.5 fs-9">
                                    Sedang Dipinjam
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top border-slate-100 p-3 px-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-xl px-4 py-2 text-xs font-semibold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const { createApp, ref, computed, onMounted } = Vue;

    createApp({
        setup() {
            const loading = ref(false);
            const books = ref([]);
            const searchQuery = ref('<?= htmlspecialchars($data['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>');
            const activeDdc = ref('');
            const selectedBook = ref(null);
            let detailModalInstance = null;

            const fetchBooks = async () => {
                loading.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/katalog');
                    if (res.data && res.data.success) {
                        books.value = res.data.data.list || [];
                    }
                } catch (e) {
                    console.error('Error load books:', e);
                } finally {
                    loading.value = false;
                }
            };

            const filteredBooks = computed(() => {
                return books.value.filter(b => {
                    const q = searchQuery.value.toLowerCase();
                    const matchQ = !q ||
                        (b.judul && b.judul.toLowerCase().includes(q)) ||
                        (b.penulis && b.penulis.toLowerCase().includes(q)) ||
                        (b.penerbit && b.penerbit.toLowerCase().includes(q)) ||
                        (b.isbn && b.isbn.toLowerCase().includes(q)) ||
                        (b.klasifikasi_ddc && b.klasifikasi_ddc.toLowerCase().includes(q));

                    const matchDdc = !activeDdc.value || (b.klasifikasi_ddc && b.klasifikasi_ddc.startsWith(activeDdc.value.substring(0, 1)));

                    return matchQ && matchDdc && (b.status_opac !== 0);
                });
            });

            const setDdcFilter = (code) => {
                activeDdc.value = code;
            };

            const resetSearch = () => {
                searchQuery.value = '';
                activeDdc.value = '';
            };

            const showDetail = (b) => {
                selectedBook.value = b;
                if (!detailModalInstance) {
                    const el = document.getElementById('modalDetailBuku');
                    if (el) detailModalInstance = new bootstrap.Modal(el);
                }
                if (detailModalInstance) detailModalInstance.show();
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
                filteredBooks,
                setDdcFilter,
                resetSearch,
                showDetail
            };
        }
    }).mount('#opacApp');
});
</script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
