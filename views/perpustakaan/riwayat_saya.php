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
        <a href="' . $this->getBaseUrl() . '/perpustakaan/opac" target="_blank" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-globe me-1"></i> Buka Katalog OPAC
        </a>
    ';
    include __DIR__ . '/_tenant_filter.php';
    ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         METRIC STATS OVERVIEW CARDS
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3.5 h-100 transition hover:-translate-y-0.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-slate-400 text-xs font-semibold">Total Dipinjam</span>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center fs-6">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                </div>
                <div class="h4 font-black text-slate-800 mb-0">{{ listRiwayat.length }}</div>
                <small class="text-slate-400 text-[11px]">Semua transaksi peminjaman</small>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3.5 h-100 transition hover:-translate-y-0.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-slate-400 text-xs font-semibold">Sedang Dipinjam</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 d-flex align-items-center justify-content-center fs-6">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <div class="h4 font-black text-amber-600 mb-0">{{ pinjamanAktifCount }}</div>
                <small class="text-slate-400 text-[11px]">Buku masih berada di tangan Anda</small>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3.5 h-100 transition hover:-translate-y-0.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-slate-400 text-xs font-semibold">Sudah Kembali</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 d-flex align-items-center justify-content-center fs-6">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <div class="h4 font-black text-emerald-600 mb-0">{{ kembaliCount }}</div>
                <small class="text-slate-400 text-[11px]">Selesai dikembalikan tepat waktu</small>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-3.5 h-100 transition hover:-translate-y-0.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-slate-400 text-xs font-semibold">Denda Keterlambatan</span>
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 d-flex align-items-center justify-content-center fs-6">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <div class="h4 font-black text-rose-600 mb-0">Rp {{ formatRupiah(totalDenda) }}</div>
                <small class="text-slate-400 text-[11px]">Tagihan denda keterlambatan</small>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MAIN DATA TABLE & FILTER TOOLBAR
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
        <!-- Toolbar Filter -->
        <div class="px-4 py-3 border-b border-slate-200/80 bg-slate-50/50">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2.5">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Search Input -->
                    <div class="position-relative" style="min-width: 260px;">
                        <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-slate-400 fs-7 pointer-events-none"></i>
                        <input type="text" v-model="searchQuery" class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-medium bg-white focus:border-blue-500 shadow-2xs" placeholder="Cari judul buku, barcode, pengarang..." aria-label="Cari riwayat buku">
                        <button v-if="searchQuery" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 hover:text-slate-600 p-0" @click="searchQuery = ''" aria-label="Reset pencarian">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>

                    <!-- Filter Status -->
                    <select v-model="filterStatus" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer" style="width: auto;" aria-label="Filter Status">
                        <option value="">— Semua Status —</option>
                        <option value="Dipinjam">Sedang Dipinjam</option>
                        <option value="Kembali">Sudah Dikembalikan</option>
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl font-bold px-3 py-1.5 text-xs d-inline-flex align-items-center gap-1.5 shadow-2xs bg-white" @click="resetFilter()">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-inline-flex align-items-center gap-1.5 shadow-2xs" @click="fetchRiwayat()">
                        <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> Segarkan
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Content Area -->
        <div class="p-0">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat riwayat peminjaman buku...
            </div>

            <div v-else-if="filteredList.length === 0" class="text-center py-5 px-3">
                <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5">
                    <i class="bi bi-journal-x"></i>
                </div>
                <div class="font-bold text-slate-700 text-base mb-1">Belum Ada Riwayat Pinjaman</div>
                <p class="text-slate-400 text-xs mb-3" style="max-width: 440px; margin: 0 auto;">
                    Tidak ditemukan data transaksi peminjaman buku yang sesuai dengan filter pencarian.
                </p>
                <a href="<?= $this->getBaseUrl() ?>/perpustakaan/opac" target="_blank" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-2xs">
                    <i class="bi bi-book me-1.5"></i> Jelajahi Buku di OPAC
                </a>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle text-xs mb-0">
                    <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold">
                        <tr>
                            <th class="ps-4 py-3" style="width: 50px;">No</th>
                            <th class="py-3">Judul Buku & Informasi Koleksi</th>
                            <th class="py-3">Barcode</th>
                            <th class="py-3 text-center">Tgl Pinjam</th>
                            <th class="py-3 text-center">Tenggat Kembali</th>
                            <th class="py-3 text-center">Tgl Dikembalikan</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="py-3 text-end pe-4">Denda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, idx) in paginatedList" :key="item.id || idx" class="hover:bg-blue-50/30 transition">
                            <td class="ps-4 py-3 font-semibold text-slate-400">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td class="py-3">
                                <div class="font-bold text-slate-800 fs-7 leading-snug">{{ item.judul_buku || item.nama_perpus_sirkulasi || 'Judul Buku Tidak Diketahui' }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    <span v-if="item.pengarang"><i class="bi bi-person me-1"></i>{{ item.pengarang }}</span>
                                    <span v-if="item.penerbit" class="ms-2"><i class="bi bi-building me-1"></i>{{ item.penerbit }}</span>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-slate-100 text-slate-700 border border-slate-200 font-mono text-[11px] px-2 py-0.5 rounded-lg">
                                    {{ item.barcode || item.eksemplar_id || '-' }}
                                </span>
                            </td>
                            <td class="py-3 text-center text-slate-600 font-medium">
                                {{ formatDateIndo(item.tanggal_pinjam || item.created_at) }}
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge font-bold px-2 py-1 rounded-lg text-[10px]"
                                      :class="isOverdue(item) ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-600'">
                                    <i class="bi me-1" :class="isOverdue(item) ? 'bi-exclamation-triangle-fill text-rose-600' : 'bi-calendar-event'"></i>
                                    {{ formatDateIndo(item.tanggal_harus_kembali) }}
                                </span>
                            </td>
                            <td class="py-3 text-center text-slate-600">
                                <span v-if="item.tanggal_kembali" class="text-emerald-700 font-semibold">
                                    <i class="bi bi-check-circle me-1"></i>{{ formatDateIndo(item.tanggal_kembali) }}
                                </span>
                                <span v-else class="text-slate-400 italic text-[11px]">Belum Kembali</span>
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="item.kategori === 'Kembali' || item.status === 'Kembali'" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2.5 py-1 rounded-pill text-[10px]">
                                    <i class="bi bi-check2 me-1"></i> Dikembalikan
                                </span>
                                <span v-else-if="isOverdue(item)" class="badge bg-rose-100 text-rose-800 border border-rose-300 font-bold px-2.5 py-1 rounded-pill text-[10px]">
                                    <i class="bi bi-clock-history me-1"></i> Terlambat
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 font-bold px-2.5 py-1 rounded-pill text-[10px]">
                                    <i class="bi bi-book-half me-1"></i> Dipinjam
                                </span>
                            </td>
                            <td class="py-3 text-end pe-4 font-bold" :class="parseFloat(item.denda || 0) > 0 ? 'text-rose-600' : 'text-slate-400'">
                                {{ parseFloat(item.denda || 0) > 0 ? 'Rp ' + formatRupiah(item.denda) : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="filteredList.length > perPage" class="p-3 border-t border-slate-100 d-flex align-items-center justify-content-between flex-wrap gap-2 text-xs text-slate-500">
                <span>Menampilkan {{ (currentPage - 1) * perPage + 1 }} s/d {{ Math.min(currentPage * perPage, filteredList.length) }} dari {{ filteredList.length }} transaksi</span>
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
            const currentPage = ref(1);
            const perPage = ref(15);

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

            const pinjamanAktifCount = computed(() => {
                return listRiwayat.value.filter(item => item.kategori === 'Dipinjam' || item.status === 'Dipinjam').length;
            });

            const kembaliCount = computed(() => {
                return listRiwayat.value.filter(item => item.kategori === 'Kembali' || item.status === 'Kembali').length;
            });

            const totalDenda = computed(() => {
                return listRiwayat.value.reduce((sum, item) => sum + (parseFloat(item.denda) || 0), 0);
            });

            const isOverdue = (item) => {
                if (item.kategori === 'Kembali' || item.status === 'Kembali' || !item.tanggal_harus_kembali) return false;
                const today = new Date().toISOString().split('T')[0];
                return item.tanggal_harus_kembali < today;
            };

            const filteredList = computed(() => {
                return listRiwayat.value.filter(item => {
                    const q = searchQuery.value.toLowerCase().trim();
                    const matchQ = !q ||
                        (item.judul_buku && item.judul_buku.toLowerCase().includes(q)) ||
                        (item.nama_perpus_sirkulasi && item.nama_perpus_sirkulasi.toLowerCase().includes(q)) ||
                        (item.barcode && item.barcode.toLowerCase().includes(q)) ||
                        (item.pengarang && item.pengarang.toLowerCase().includes(q));

                    const matchStatus = !filterStatus.value || (item.kategori === filterStatus.value || item.status === filterStatus.value);
                    return matchQ && matchStatus;
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
                currentPage.value = 1;
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
                currentPage,
                perPage,
                pinjamanAktifCount,
                kembaliCount,
                totalDenda,
                filteredList,
                totalPages,
                paginatedList,
                isOverdue,
                resetFilter,
                formatDateIndo,
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
