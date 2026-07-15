<?php
$baseUrl  = '/SINTA-SaaS';
$tenantId = $_GET['tenant_id'] ?? ($_SESSION['tenant_id'] ?? '');
$userRoles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
$canWrite = false;
foreach ($userRoles as $r) {
    if (in_array($r, ['operator_sekolah', 'super_admin', 'guru_bk'])) {
        $canWrite = true;
        break;
    }
}
?>

<div id="kampusProdiFlatApp" v-cloak>
    
    <!-- HEADER & ACTIONS -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h4 class="font-bold text-slate-800 text-lg mb-0 flex items-center gap-2">
                <i class="bi bi-buildings text-blue-500"></i> Master Kampus, Prodi & Kuota
            </h4>
            <p class="text-xs text-slate-500 mb-0">Kelola daftar kampus, program studi, dan riwayat keketatan (kuota/pendaftar) secara langsung.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Search -->
            <div class="relative">
                <input type="text" v-model="searchQuery" placeholder="Cari prodi..." class="form-control form-control-sm rounded-xl text-xs pl-8 border-slate-200" style="width: 180px;">
                <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <!-- Filter Kampus -->
            <select v-model="filterKampusId" class="form-select form-select-sm rounded-xl text-xs border-slate-200" style="width: 200px;">
                <option value="">-- Semua Kampus --</option>
                <option v-for="k in uniqueKampusList" :key="k.id" :value="k.id">{{ k.nama_kampus }}</option>
            </select>

            <div class="dropdown">
                <button class="btn btn-sm btn-light border rounded-xl text-xs font-semibold dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-file-earmark-spreadsheet text-emerald-600 me-1"></i> Excel Daya Tampung
                </button>
                <ul class="dropdown-menu shadow-lg border-0 rounded-xl text-xs">
                    <li><a class="dropdown-item py-2 hover:bg-slate-50" :href="`${baseUrl}/api/v1/kampus/export-daya-tampung?tenant_id=${tenantId}`" target="_blank"><i class="bi bi-download text-blue-500 me-2"></i> Export (Download)</a></li>
                    <li><a class="dropdown-item py-2 hover:bg-slate-50" href="#" @click.prevent="modalImportDayaTampung.show = true"><i class="bi bi-upload text-emerald-500 me-2"></i> Import (Upload)</a></li>
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-sm btn-light border rounded-xl text-xs font-semibold dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-building text-blue-600 me-1"></i> Excel Kampus & Prodi
                </button>
                <ul class="dropdown-menu shadow-lg border-0 rounded-xl text-xs">
                    <li><a class="dropdown-item py-2 hover:bg-slate-50" :href="`${baseUrl}/api/v1/kampus/export-kampus-prodi?tenant_id=${tenantId}`" target="_blank"><i class="bi bi-download text-blue-500 me-2"></i> Export Template</a></li>
                    <li><a class="dropdown-item py-2 hover:bg-slate-50" href="#" @click.prevent="modalImportKampusProdi.show = true"><i class="bi bi-upload text-emerald-500 me-2"></i> Import / Tambah</a></li>
                </ul>
            </div>

            <button class="btn btn-sm btn-danger rounded-xl text-xs font-semibold flex items-center gap-1 shadow-sm" @click="modalBulkDelete.show = true">
                <i class="bi bi-trash"></i> Hapus Kolektif
            </button>
        </div>
    </div>

    <!-- MAIN TABLE -->
    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-borderless mb-0 w-full align-middle text-xs">
                <thead>
                    <!-- Baris 1: Kolom utama + grup tahun -->
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-center">
                        <th rowspan="2" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider align-middle text-left" style="min-width:160px;">KAMPUS</th>
                        <th rowspan="2" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider align-middle text-left" style="min-width:180px;">PROGRAM STUDI</th>
                        <th rowspan="2" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider align-middle text-center" style="min-width:70px;">JENJANG</th>
                        <th v-for="y in displayYears" :key="'hdr-'+y" colspan="2" class="px-4 py-2 text-[10px] font-bold text-slate-600 text-center border-l border-slate-100" style="min-width:160px;">
                            {{ y }}
                        </th>
                        <th v-if="canWrite" rowspan="2" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider align-middle text-center border-l border-slate-100" style="min-width:120px;">AKSI</th>
                    </tr>
                    <!-- Baris 2: sub-header daya tampung & peminat per tahun -->
                    <tr class="bg-slate-50/40 border-b border-slate-100 text-center">
                        <template v-for="y in displayYears" :key="'sub-'+y">
                            <th class="px-3 py-2 text-[9px] font-semibold text-slate-450 uppercase tracking-wider border-l border-slate-100">Daya Tampung</th>
                            <th class="px-3 py-2 text-[9px] font-semibold text-slate-450 uppercase tracking-wider">Peminat</th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td :colspan="3 + displayYears.length * 2 + (canWrite ? 1 : 0)" class="p-8 text-center text-slate-400">
                            <div class="spinner-border spinner-border-sm text-blue-500 mb-2"></div>
                            <div class="text-xs">Memuat data...</div>
                        </td>
                    </tr>
                    <tr v-else-if="!tenantId">
                        <td :colspan="3 + displayYears.length * 2 + (canWrite ? 1 : 0)" class="p-8 text-center text-slate-400 text-xs">
                            <i class="bi bi-funnel fs-3 block mb-2 opacity-50"></i>
                            Silakan pilih sekolah terlebih dahulu melalui filter di atas.
                        </td>
                    </tr>
                    <tr v-else-if="filteredData.length === 0">
                        <td :colspan="3 + displayYears.length * 2 + (canWrite ? 1 : 0)" class="p-8 text-center text-slate-400 text-xs">
                            <i class="bi bi-inbox fs-3 block mb-2 opacity-50"></i>
                            Tidak ada data kampus atau prodi.
                        </td>
                    </tr>
                    <tr v-for="row in paginatedData" :key="row.prodi_id || row.kampus_id" class="transition-colors hover:bg-slate-50/50">
                        <td class="px-4 py-3 align-middle">
                            <div class="font-bold text-slate-800 text-xs">{{ row.nama_kampus }}</div>
                            <div class="text-[10px] text-slate-500">{{ row.jenis_kampus }} · {{ row.kota_kampus }}</div>
                        </td>
                        <td class="px-4 py-3 align-middle">
                            <div v-if="row.prodi_id">
                                <div class="font-bold text-blue-700 text-xs">{{ row.program_studi }}</div>
                                <div class="text-[10px] text-slate-500">Kode: {{ row.kode_prodi || '-' }} | Fak: {{ row.fakultas || '-' }}</div>
                            </div>
                            <div v-else class="text-[10px] text-slate-450 italic">Belum ada prodi</div>
                        </td>
                        <td class="px-4 py-3 text-center align-middle">
                            <span v-if="row.jenjang" class="badge bg-slate-100 text-slate-600 border border-slate-200/60 text-[10px] px-2 py-1 rounded-md">{{ row.jenjang }}</span>
                            <span v-else class="text-slate-300">-</span>
                        </td>
                        <template v-for="y in displayYears" :key="'d-'+row.prodi_id+'-'+y">
                            <template v-if="getRiwayat(row.riwayat, y)">
                                <td class="px-3 py-3 text-center border-l border-slate-100 font-semibold text-slate-700">
                                    {{ getRiwayat(row.riwayat, y).daya_tampung }}
                                </td>
                                <td class="px-3 py-3 text-center font-semibold text-slate-500">
                                    {{ getRiwayat(row.riwayat, y).jumlah_pendaftar }}
                                </td>
                            </template>
                            <template v-else>
                                <td class="px-3 py-3 text-center border-l border-slate-100 text-slate-300 text-[10px]">-</td>
                                <td class="px-3 py-3 text-center text-slate-300 text-[10px]">-</td>
                            </template>
                        </template>
                        <td v-if="canWrite" class="px-3 py-3 text-center border-l border-slate-100">
                            <div class="d-flex flex-column gap-1 align-items-center justify-content-center">
                                <button v-if="row.prodi_id" class="btn btn-xs btn-light border text-rose-600 font-semibold px-2 py-1 rounded-lg text-[10px] w-100" @click="deleteProdi(row.prodi_id, row.program_studi)">
                                    <i class="bi bi-trash"></i> Hapus Prodi
                                </button>
                                <button class="btn btn-xs btn-light border text-slate-700 font-semibold px-2 py-1 rounded-lg text-[10px] w-100" @click="deleteKampus(row.kampus_id, row.nama_kampus)">
                                    <i class="bi bi-building-x"></i> Hapus Kampus
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50/50">
            <div class="d-flex align-items-center gap-2">
                <span class="text-xs text-slate-500 text-muted">Tampilkan:</span>
                <select class="form-select form-select-sm rounded-3 py-1 text-xs" style="width: 70px;" v-model="perPage" @change="currentPage = 1">
                    <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
                <span class="text-xs text-slate-500 text-muted" v-if="filteredData.length > 0">
                    Menampilkan {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, filteredData.length) }} dari {{ filteredData.length }} baris
                </span>
            </div>
            <nav v-if="totalPages > 1" aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm m-0 gap-1">
                    <li class="page-item" :class="{disabled: currentPage === 1}">
                        <button class="page-link rounded-3 border-slate-200 text-slate-600 px-2 py-1 text-xs" @click.prevent="currentPage = 1" :disabled="currentPage === 1">&laquo;</button>
                    </li>
                    <li class="page-item" :class="{disabled: currentPage === 1}">
                        <button class="page-link rounded-3 border-slate-200 text-slate-600 px-2.5 py-1 text-xs" @click.prevent="currentPage--" :disabled="currentPage === 1">&lsaquo;</button>
                    </li>
                    <li class="page-item" v-for="page in displayedPages" :key="page" :class="{active: page === currentPage, disabled: page === '...'}">
                        <button v-if="page !== '...'" class="page-link rounded-3 border-slate-200 px-2.5 py-1 text-xs" :class="page === currentPage ? 'bg-blue-600 border-blue-600 text-white' : 'text-slate-600'" @click.prevent="currentPage = page">{{ page }}</button>
                        <span v-else class="px-2 py-1 text-slate-400 text-xs">...</span>
                    </li>
                    <li class="page-item" :class="{disabled: currentPage === totalPages}">
                        <button class="page-link rounded-3 border-slate-200 text-slate-600 px-2.5 py-1 text-xs" @click.prevent="currentPage++" :disabled="currentPage === totalPages">&rsaquo;</button>
                    </li>
                    <li class="page-item" :class="{disabled: currentPage === totalPages}">
                        <button class="page-link rounded-3 border-slate-200 text-slate-600 px-2 py-1 text-xs" @click.prevent="currentPage = totalPages" :disabled="currentPage === totalPages">&raquo;</button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>


    <!-- MODAL IMPORT EXCEL -->
    <Teleport to="body">
    <div v-if="modalImportDayaTampung.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white border rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2">
                    <i class="bi bi-upload text-emerald-500"></i> Import Daya Tampung
                </h3>
                <button class="text-slate-400 hover:text-slate-600 text-xl font-bold bg-transparent border-0" @click="modalImportDayaTampung.show = false">&times;</button>
            </div>
            <div class="p-5 space-y-4 text-left">
                <p class="text-xs text-slate-600">
                    Upload file excel yang berisi data Daya Tampung dan Jumlah Pendaftar per tahun. Pastikan format kolom sesuai dengan yang di-download dari menu Export.
                </p>
                <form @submit.prevent="importExcelDayaTampung">
                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">File Excel (.xls, .xlsx) <span class="text-rose-500">*</span></label>
                        <input type="file" ref="fileImportDayaTampung" accept=".xlsx, .xls" required class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-xl">
                    </div>
                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-light border rounded-xl px-4 py-2 text-xs font-semibold text-slate-600" @click="modalImportDayaTampung.show = false">Batal</button>
                        <button type="submit" class="btn btn-success rounded-xl px-4 py-2 text-xs font-semibold text-white flex items-center gap-1.5 shadow-sm" :disabled="importingDayaTampung">
                            <i class="bi" :class="importingDayaTampung ? 'bi-hourglass-split' : 'bi-check-circle'"></i>
                            {{ importingDayaTampung ? 'Memproses...' : 'Import Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </Teleport>

    <!-- MODAL BULK DELETE -->
    <Teleport to="body">
    <div v-if="modalBulkDelete.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white border rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-fade-in">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2">
                    <i class="bi bi-trash text-rose-500"></i> Hapus Kolektif Riwayat
                </h3>
                <button class="text-slate-400 hover:text-slate-600 text-xl font-bold bg-transparent border-0" @click="modalBulkDelete.show = false">&times;</button>
            </div>
            <div class="p-5 space-y-4 text-left">
                <p class="text-[11px] text-slate-500 bg-rose-50 p-2 rounded-lg border border-rose-100 text-rose-700">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Aksi ini akan menghapus riwayat daya tampung dan jumlah pendaftar. Tidak bisa dibatalkan.
                </p>
                <form @submit.prevent="executeBulkDelete">
                    <div class="mb-3">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Berdasarkan Tahun</label>
                        <input type="number" v-model="modalBulkDelete.form.tahun" placeholder="Contoh: 2023" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-rose-500 outline-none">
                        <small class="text-[9px] text-slate-400">Kosongkan jika tidak memfilter tahun.</small>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Berdasarkan Kampus</label>
                        <select v-model="modalBulkDelete.form.kampus_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-rose-500 outline-none">
                            <option value="">-- Semua Kampus --</option>
                            <option v-for="k in uniqueKampusList" :key="k.id" :value="k.id">{{ k.nama_kampus }}</option>
                        </select>
                        <small class="text-[9px] text-slate-400">Kosongkan jika menghapus untuk semua kampus (berbahaya!).</small>
                    </div>
                    
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-light border rounded-xl px-4 py-2 text-xs font-semibold text-slate-600" @click="modalBulkDelete.show = false">Batal</button>
                        <button type="submit" class="btn btn-danger rounded-xl px-4 py-2 text-xs font-semibold text-white flex items-center gap-1.5 shadow-sm" :disabled="bulkDeleting || (!modalBulkDelete.form.tahun && !modalBulkDelete.form.kampus_id)">
                            <i class="bi bi-trash"></i> {{ bulkDeleting ? 'Menghapus...' : 'Hapus Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </Teleport>

    <!-- MODAL IMPORT KAMPUS & PRODI -->
    <Teleport to="body">
    <div v-if="modalImportKampusProdi.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white border rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2">
                    <i class="bi bi-building text-blue-500"></i> Import Kampus & Prodi
                </h3>
                <button class="text-slate-400 hover:text-slate-600 text-xl font-bold bg-transparent border-0" @click="modalImportKampusProdi.show = false">&times;</button>
            </div>
            <div class="p-5 text-left">
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 mb-4">
                    <p class="text-xs text-blue-700 mb-0"><i class="bi bi-info-circle me-1"></i> Upload file Excel berisi data Kampus dan Prodi. Kampus baru akan dibuat otomatis jika belum ada. Prodi yang sudah ada akan diperbarui.</p>
                </div>
                <form @submit.prevent="importKampusProdi">
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">File Excel (.xls, .xlsx) <span class="text-rose-500">*</span></label>
                        <input type="file" ref="fileImportKampusProdi" accept=".xlsx, .xls" required class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl">
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-light border rounded-xl px-4 py-2 text-xs font-semibold text-slate-600" @click="modalImportKampusProdi.show = false">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-xl px-4 py-2 text-xs font-semibold text-white flex items-center gap-1.5 shadow-sm" :disabled="importingKampusProdi">
                            <i class="bi" :class="importingKampusProdi ? 'bi-hourglass-split' : 'bi-check-circle'"></i>
                            {{ importingKampusProdi ? 'Memproses...' : 'Import Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </Teleport>

</div>

<script>
window.VueAppRegistry = window.VueAppRegistry || {};
if (window.VueAppRegistry.register) {
    window.VueAppRegistry.register('#kampusProdiFlatApp', {
        data() {
        return {
            baseUrl: '<?= $baseUrl ?>',
            tenantId: '<?= htmlspecialchars($tenantId) ?>',
            canWrite: <?= json_encode($canWrite) ?>,
            loading: false,
            dataList: [],
            searchQuery: '',
            filterKampusId: '',
            
            currentPage: 1,
            perPage: 10,
            perPageOptions: [5, 10, 25, 50, 100],

            modalImportDayaTampung: { show: false },
            importingDayaTampung: false,

            modalImportKampusProdi: { show: false },
            importingKampusProdi: false,
            
            modalBulkDelete: {
                show: false,
                form: {
                    tahun: '',
                    kampus_id: ''
                }
            },
            bulkDeleting: false
        }
    },
    watch: {
        filterKampusId() {
            this.currentPage = 1;
        },
        searchQuery() {
            this.currentPage = 1;
        }
    },
    computed: {
        displayYears() {
            // Kumpulkan semua tahun dari seluruh data, ambil 6 terbesar dengan tahun terbaru di kiri
            const years = new Set();
            this.dataList.forEach(item => {
                if (item.riwayat) item.riwayat.forEach(r => years.add(r.tahun));
            });
            const sorted = Array.from(years).sort((a, b) => b - a);
            return sorted.slice(0, 6); // 6 tahun terbaru (descending)
        },
        filteredData() {
            let data = this.dataList;
            if (this.filterKampusId) {
                data = data.filter(item => item.kampus_id === this.filterKampusId);
            }
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                data = data.filter(item =>
                    (item.program_studi && item.program_studi.toLowerCase().includes(q)) ||
                    (item.nama_kampus && item.nama_kampus.toLowerCase().includes(q))
                );
            }
            return data;
        },
        totalPages() {
            return Math.ceil(this.filteredData.length / this.perPage) || 1;
        },
        paginatedData() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredData.slice(start, start + this.perPage);
        },
        displayedPages() {
            const current = this.currentPage;
            const last = this.totalPages;
            const delta = 2;
            const left = current - delta;
            const right = current + delta + 1;
            const range = [];
            const rangeWithDots = [];
            let l;

            for (let i = 1; i <= last; i++) {
                if (i === 1 || i === last || (i >= left && i < right)) {
                    range.push(i);
                }
            }

            for (let i of range) {
                if (l) {
                    if (i - l === 2) {
                        rangeWithDots.push(l + 1);
                    } else if (i - l > 2) {
                        rangeWithDots.push('...');
                    }
                }
                rangeWithDots.push(i);
                l = i;
            }

            return rangeWithDots;
        },
        uniqueKampusList() {
            const map = new Map();
            this.dataList.forEach(item => {
                if(item.kampus_id && !map.has(item.kampus_id)) {
                    map.set(item.kampus_id, { id: item.kampus_id, nama_kampus: item.nama_kampus });
                }
            });
            return Array.from(map.values()).sort((a,b) => a.nama_kampus.localeCompare(b.nama_kampus));
        }
    },
    methods: {
        getRiwayat(riwayat, tahun) {
            if (!riwayat) return null;
            return riwayat.find(r => parseInt(r.tahun) === parseInt(tahun)) || null;
        },
        async loadData() {
            if (!this.tenantId) {
                this.loading = false;
                this.dataList = [];
                return;
            }
            this.loading = true;
            try {
                const res = await axios.get(`${this.baseUrl}/api/v1/kampus/flat-list?tenant_id=${this.tenantId}`);
                if (res.data.success) {
                    this.dataList = res.data.data;
                }
            } catch (err) {
                console.error(err);
                if (window.Swal) Swal.fire('Error', 'Gagal memuat data master kampus', 'error');
            } finally {
                this.loading = false;
            }
        },
        async importExcelDayaTampung() {
            const fileInput = this.$refs.fileImportDayaTampung;
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) return;
            
            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('file', file);
            
            this.importingDayaTampung = true;
            try {
                const res = await axios.post(`${this.baseUrl}/api/v1/kampus/import-daya-tampung?tenant_id=${this.tenantId}`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                if (res.data.success) {
                    if (window.Swal) Swal.fire('Berhasil', res.data.message, 'success');
                    this.modalImportDayaTampung.show = false;
                    this.loadData();
                } else {
                    if (window.Swal) Swal.fire('Gagal', res.data.error || 'Terjadi kesalahan', 'error');
                }
            } catch (err) {
                if (err.response?.status !== 422) {
                    console.error(err);
                }
                const msg = err.response?.data?.error || 'Terjadi kesalahan sistem';
                if (window.Swal) {
                    if (err.response?.status === 422) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Format Template Salah',
                            text: msg,
                            confirmButtonColor: '#f8bb86'
                        });
                    } else {
                        Swal.fire('Error', msg, 'error');
                    }
                } else {
                    alert(msg);
                }
            } finally {
                this.importingDayaTampung = false;
                if (fileInput) fileInput.value = '';
            }
        },
        async executeBulkDelete() {
            if (!this.modalBulkDelete.form.tahun && !this.modalBulkDelete.form.kampus_id) return;
            
            if (window.Swal) {
                const conf = await Swal.fire({
                    title: 'Yakin menghapus?',
                    text: 'Data riwayat yang dihapus tidak bisa dikembalikan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#e3342f'
                });
                if (!conf.isConfirmed) return;
            }
            
            this.bulkDeleting = true;
            try {
                const res = await axios.post(`${this.baseUrl}/api/v1/kampus/bulk-delete-riwayat?tenant_id=${this.tenantId}`, this.modalBulkDelete.form);
                if (res.data.success) {
                    if (window.Swal) Swal.fire('Terhapus', res.data.message, 'success');
                    this.modalBulkDelete.show = false;
                    this.loadData();
                }
            } catch (err) {
                console.error(err);
                if (window.Swal) Swal.fire('Error', err.response?.data?.error || 'Gagal menghapus data', 'error');
            } finally {
                this.bulkDeleting = false;
            }
        },
        async importKampusProdi() {
            const fileInput = this.$refs.fileImportKampusProdi;
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) return;

            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('file', file);

            this.importingKampusProdi = true;
            try {
                const res = await axios.post(`${this.baseUrl}/api/v1/kampus/import-kampus-prodi?tenant_id=${this.tenantId}`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                if (res.data.success) {
                    if (window.Swal) Swal.fire('Berhasil', res.data.message, 'success');
                    this.modalImportKampusProdi.show = false;
                    this.loadData();
                } else {
                    if (window.Swal) Swal.fire('Gagal', res.data.error || 'Terjadi kesalahan', 'error');
                }
            } catch (err) {
                if (err.response?.status !== 422) {
                    console.error(err);
                }
                const msg = err.response?.data?.error || 'Terjadi kesalahan sistem';
                if (window.Swal) {
                    if (err.response?.status === 422) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Format Template Salah',
                            text: msg,
                            confirmButtonColor: '#f8bb86'
                        });
                    } else {
                        Swal.fire('Error', msg, 'error');
                    }
                } else {
                    alert(msg);
                }
            } finally {
                this.importingKampusProdi = false;
                if (fileInput) fileInput.value = '';
            }
        },
        async deleteProdi(id, name) {
            if (window.Swal) {
                const conf = await Swal.fire({
                    title: 'Hapus Program Studi?',
                    text: `Anda yakin ingin menghapus prodi "${name}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#e3342f'
                });
                if (!conf.isConfirmed) return;
            } else {
                if (!confirm(`Hapus prodi "${name}"?`)) return;
            }
            
            try {
                const res = await axios.post(`${this.baseUrl}/api/v1/kampus/prodi/delete?tenant_id=${this.tenantId}`, { id });
                if (res.data.success) {
                    if (window.Swal) Swal.fire('Terhapus', 'Program studi berhasil dihapus.', 'success');
                    this.loadData();
                }
            } catch (err) {
                console.error(err);
                if (window.Swal) Swal.fire('Error', err.response?.data?.error || 'Gagal menghapus prodi', 'error');
            }
        },
        async deleteKampus(id, name) {
            if (window.Swal) {
                const conf = await Swal.fire({
                    title: 'Hapus Kampus?',
                    text: `Menghapus kampus "${name}" akan menghapus seluruh prodi dan riwayat keketatan di dalamnya secara permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus Kampus!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#e3342f'
                });
                if (!conf.isConfirmed) return;
            } else {
                if (!confirm(`Hapus kampus "${name}"? Semua prodi di dalamnya juga akan terhapus!`)) return;
            }
            
            try {
                const res = await axios.post(`${this.baseUrl}/api/v1/kampus/delete?tenant_id=${this.tenantId}`, { id });
                if (res.data.success) {
                    if (window.Swal) Swal.fire('Terhapus', 'Kampus berhasil dihapus.', 'success');
                    this.loadData();
                }
            } catch (err) {
                console.error(err);
                if (window.Swal) Swal.fire('Error', err.response?.data?.error || 'Gagal menghapus kampus', 'error');
            }
        }
    },
    mounted() {
        this.loadData();
    }
});
}
</script>
