<!-- SheetJS Library for genuine XLSX export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<div id="keuangan-laporan-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-graph-up-arrow text-blue-600 me-2"></i> Laporan Keuangan Sekolah
            </h2>
            <p class="text-muted mb-0">Lihat rekapitulasi uang masuk harian/bulanan serta detail data tunggakan siswa.</p>
        </div>
        <div class="d-flex gap-2">
            <button @click="exportToExcel" class="btn btn-outline-success fw-bold"><i class="bi bi-file-earmark-excel me-2"></i> Ekspor Excel (.xlsx)</button>
            <button @click="printReport" class="btn btn-outline-primary fw-bold"><i class="bi bi-printer me-2"></i> Cetak Laporan</button>
        </div>
    </div>

    <!-- Tenant Selector Card (Super Admin Only) -->
    <div v-if="isSuperAdmin" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="row align-items-center">
            <div class="col-md-6">
                <label class="form-label fw-bold text-slate-700"><i class="bi bi-building-gear text-blue-600 me-2"></i> Pilih Sekolah (Tenant)</label>
                <select class="form-select border-slate-200" v-model="selectedTenantId" @change="onTenantChange" style="height: 44px;">
                    <option v-for="t in tenantsList" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                </select>
            </div>
            <div class="col-md-6 mt-3 mt-md-0 text-md-end text-muted fs-7">
                Menyaring transaksi keuangan dan status tunggakan untuk sekolah terpilih.
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" id="laporanTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-slate-700 py-3" id="pemasukan-tab" data-bs-toggle="tab" data-bs-target="#pemasukan-pane" type="button" role="tab" @click="setTipe('pemasukan')">
                <i class="bi bi-cash-stack me-2 text-emerald-600"></i> Rekap Pemasukan
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-slate-700 py-3" id="tunggakan-tab" data-bs-toggle="tab" data-bs-target="#tunggakan-pane" type="button" role="tab" @click="setTipe('tunggakan')">
                <i class="bi bi-exclamation-triangle-fill me-2 text-rose-600"></i> Rekap Tunggakan
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content bg-white card border-0 shadow-sm rounded-4 p-4" id="laporanTabsContent">
        
        <!-- Filter Form Bar -->
        <div class="bg-light p-3 rounded-3 mb-4">
            <div class="row g-3">
                <!-- Filter Komponen Biaya -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Filter Komponen Biaya</label>
                    <select class="form-select border-slate-200" v-model="filterText">
                        <option value="">-- Semua Komponen --</option>
                        <option v-for="k in komponenList" :key="k.id || k.nama_komponen" :value="k.nama_komponen">{{ k.nama_komponen }}</option>
                    </select>
                </div>

                <!-- Filter Jenjang -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Filter Jenjang</label>
                    <select class="form-select border-slate-200" v-model="filterJenjang">
                        <option value="">-- Semua Jenjang --</option>
                        <option v-for="j in jenjangList" :key="j.id" :value="j.nama_jenjang">{{ j.nama_jenjang }}</option>
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Filter Kelas</label>
                    <select class="form-select border-slate-200" v-model="filterKelas">
                        <option value="">-- Semua Kelas --</option>
                        <option v-for="c in kelasList" :key="c.id" :value="c.nama_kelas">{{ c.nama_kelas }}</option>
                    </select>
                </div>

                <!-- Filter Tahun Ajaran -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Filter Tahun Ajaran</label>
                    <select class="form-select border-slate-200" v-model="filterTahunAjaran">
                        <option value="">-- Semua Tahun Ajaran --</option>
                        <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.tahun_ajaran">{{ ta.tahun_ajaran }}</option>
                    </select>
                </div>

                <!-- Baris Kedua Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Pencarian Siswa</label>
                    <input type="text" class="form-control border-slate-200" v-model="filterSiswa" placeholder="Nama / NISN siswa...">
                </div>

                <!-- Filter Tambahan untuk Rekap Pemasukan -->
                <template v-if="tipeReport === 'pemasukan'">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Dari Tanggal Bayar</label>
                        <input type="date" class="form-control border-slate-200" v-model="filterDateFrom">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Sampai Tanggal Bayar</label>
                        <input type="date" class="form-control border-slate-200" v-model="filterDateTo">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-slate-700 fs-8 mb-1">Filter Metode</label>
                        <select class="form-select border-slate-200" v-model="filterMetode">
                            <option value="">-- Semua Metode --</option>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer">Transfer Manual</option>
                            <option value="Payment Gateway">Payment Gateway</option>
                        </select>
                    </div>
                </template>
            </div>
        </div>

        <!-- Tab 1: Rekap Pemasukan -->
        <div class="tab-pane fade show active" id="pemasukan-pane" role="tabpanel" v-if="tipeReport === 'pemasukan'">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tableReport">
                    <thead>
                        <tr>
                            <th>No Kwitansi</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Tahun Ajaran</th>
                            <th>Komponen</th>
                            <th>Jumlah Bayar</th>
                            <th>Metode</th>
                            <th>Tanggal Bayar</th>
                            <th>Kasir Staf</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in paginatedList" :key="p.id">
                            <td><span class="badge bg-slate-100 text-slate-800 fw-bold px-3 py-2 border border-slate-200">{{ p.nomor_kwitansi }}</span></td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ p.nama_siswa }}</div>
                                <small class="text-muted">NISN: {{ p.nisn }}</small>
                            </td>
                            <td><span class="badge bg-blue-50 text-blue-700 border border-blue-200">{{ p.nama_kelas || '-' }}</span></td>
                            <td>{{ p.tahun_ajaran || '-' }}</td>
                            <td>{{ p.nama_komponen }}</td>
                            <td class="fw-bold text-emerald-600">Rp {{ formatNumber(p.nominal_dibayar) }}</td>
                            <td>{{ p.metode_pembayaran }}</td>
                            <td>{{ formatDate(p.tanggal_bayar) }}</td>
                            <td>{{ p.nama_kasir }}</td>
                        </tr>
                        <tr v-if="filteredList.length === 0">
                            <td colspan="9" class="text-center py-4 text-muted">Tidak ada transaksi pemasukan yang cocok dengan filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: Rekap Tunggakan -->
        <div class="tab-pane fade show active" id="tunggakan-pane" role="tabpanel" v-else>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tableReport">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Komponen Tagihan</th>
                            <th>Tahun Ajaran</th>
                            <th>Bulan</th>
                            <th>Nominal Tagihan</th>
                            <th>Sudah Dibayar</th>
                            <th>Sisa Kekurangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in paginatedList" :key="t.id">
                            <td>
                                <div class="fw-bold text-slate-800">{{ t.nama_siswa }}</div>
                                <small class="text-muted">NISN: {{ t.nisn }}</small>
                            </td>
                            <td><span class="badge bg-blue-50 text-blue-700 border border-blue-200">{{ t.nama_kelas || '-' }}</span></td>
                            <td>{{ t.nama_komponen }}</td>
                            <td>{{ t.tahun_ajaran }}</td>
                            <td>{{ t.bulan ? getBulanName(t.bulan) : '-' }}</td>
                            <td>Rp {{ formatNumber(t.nominal_tagihan) }}</td>
                            <td class="text-success fw-semibold">Rp {{ formatNumber(t.nominal_bayar) }}</td>
                            <td class="text-danger fw-bold">Rp {{ formatNumber(t.nominal_tagihan - t.nominal_bayar) }}</td>
                        </tr>
                        <tr v-if="filteredList.length === 0">
                            <td colspan="8" class="text-center py-4 text-muted">Tidak ditemukan tunggakan aktif yang cocok dengan filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Controls -->
        <div class="d-flex justify-content-between align-items-center mt-3" v-if="totalPages > 1">
            <span class="text-muted fs-8">Menampilkan Halaman {{ currentPage }} dari {{ totalPages }} (Total {{ filteredList.length }} data)</span>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm justify-content-end mb-0">
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                        <a class="page-link" href="#" @click.prevent="currentPage--">Sebelumnya</a>
                    </li>
                    <li class="page-item" v-for="p in visiblePages" :key="p" :class="{ active: currentPage === p, disabled: p === '...' }">
                        <span v-if="p === '...'" class="page-link">...</span>
                        <a v-else class="page-link" href="#" @click.prevent="currentPage = p">{{ p }}</a>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                        <a class="page-link" href="#" @click.prevent="currentPage++">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        </div>

    </div>
</div>

<!-- Data Injection -->
<script id="user-session" type="application/json">
    <?php echo json_encode([
        'is_super_admin' => (($_SESSION['role_name'] ?? '') === 'super_admin'),
        'tenant_id' => ($_SESSION['tenant_id'] ?? '')
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<style>
/* Styling Navtabs Minimalis Datar */
.nav-tabs {
    border-bottom: 1px solid #e2e8f0 !important;
}
.nav-tabs .nav-item {
    margin-bottom: -1px;
}
.nav-tabs .nav-link {
    border: none !important;
    border-bottom: 2px solid transparent !important;
    color: #64748b !important;
    font-weight: 600 !important;
    background: transparent !important;
    padding: 0.8rem 1.2rem !important;
    font-size: 0.85rem !important;
    transition: all 0.15s ease-in-out;
}
.nav-tabs .nav-link:hover {
    color: #1e293b !important;
    border-bottom-color: #cbd5e1 !important;
}
.nav-tabs .nav-link.active {
    color: #2563eb !important;
    border-bottom-color: #2563eb !important;
    background: transparent !important;
}

/* Styling Tabel Modern Borderless */
.table {
    border-collapse: collapse !important;
    width: 100%;
}
.table th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.75rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 2px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.75rem 1rem !important;
}
.table td {
    border-bottom: 1px solid #f1f5f9 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.85rem 1rem !important;
    font-size: 0.8rem !important;
    color: #334155 !important;
}
.table tbody tr {
    transition: background-color 0.15s ease;
}
.table tbody tr:hover {
    background-color: #f8fafc !important;
}

.fs-7 { font-size: 0.85rem; }
.fs-8 { font-size: 0.75rem; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }

@media print {
    body * {
        visibility: hidden;
    }
    #print-section, #print-section * {
        visibility: visible;
    }
    #print-section {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>

<script>
window.VueAppRegistry.register('#keuangan-laporan-app', {
    setup() {
        const session = JSON.parse(document.getElementById('user-session').textContent || '{}');
        const isSuperAdmin = session.is_super_admin;
        const tenantsList = Vue.ref([]);
        const selectedTenantId = Vue.ref(session.tenant_id || '');

        const tipeReport = Vue.ref('pemasukan');
        const rawList = Vue.ref([]);
        const filteredList = Vue.ref([]);

        // Master Option Lists from Backend
        const komponenList = Vue.ref([]);
        const jenjangList = Vue.ref([]);
        const kelasList = Vue.ref([]);
        const tahunAjaranList = Vue.ref([]);

        // Filter States
        const filterText = Vue.ref(''); // Komponen
        const filterJenjang = Vue.ref('');
        const filterKelas = Vue.ref('');
        const filterTahunAjaran = Vue.ref('');
        const filterSiswa = Vue.ref('');
        const filterDateFrom = Vue.ref('');
        const filterDateTo = Vue.ref('');
        const filterMetode = Vue.ref('');

        // Pagination state
        const currentPage = Vue.ref(1);
        const pageSize = Vue.ref(15);

        const setTipe = (tipe) => {
            tipeReport.value = tipe;
            fetchReport();
        };

        const getQueryParam = () => {
            return isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
        };

        const fetchTenants = async () => {
            if (!isSuperAdmin) return;
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/tenants');
                const res = await response.json();
                if (res.success) {
                    tenantsList.value = res.data;
                    const cached = localStorage.getItem('sinta_spp_selected_tenant_id');
                    if (cached && tenantsList.value.some(t => t.id === cached)) {
                        selectedTenantId.value = cached;
                    } else if (tenantsList.value.length > 0) {
                        selectedTenantId.value = tenantsList.value[0].id;
                        localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
                    }
                }
            } catch (err) {
                console.error(err);
            }
        };

        const fetchReport = async () => {
            try {
                const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/laporan-rekap?tipe=${tipeReport.value}${tenantSuffix}`);
                const res = await response.json();
                if (res.success) {
                    rawList.value = res.data || [];
                    if (res.options) {
                        komponenList.value = res.options.komponen_list || [];
                        jenjangList.value = res.options.jenjang_list || [];
                        kelasList.value = res.options.kelas_list || [];
                        tahunAjaranList.value = res.options.tahun_ajaran_list || [];
                    }
                    currentPage.value = 1;
                    applyFilters();
                }
            } catch (err) {
                console.error(err);
            }
        };

        const onTenantChange = () => {
            localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
            fetchReport();
        };

        const applyFilters = () => {
            filteredList.value = rawList.value.filter(item => {
                // 1. Filter Komponen Biaya
                const matchKomponen = !filterText.value || (item.nama_komponen && item.nama_komponen.toLowerCase() === filterText.value.toLowerCase());
                
                // 2. Filter Jenjang
                const matchJenjang = !filterJenjang.value || (item.nama_jenjang && item.nama_jenjang.toLowerCase() === filterJenjang.value.toLowerCase());
                
                // 3. Filter Kelas
                const matchKelas = !filterKelas.value || (item.nama_kelas && item.nama_kelas.toLowerCase() === filterKelas.value.toLowerCase());

                // 4. Filter Tahun Ajaran
                const matchTA = !filterTahunAjaran.value || (item.tahun_ajaran && item.tahun_ajaran.toLowerCase() === filterTahunAjaran.value.toLowerCase());

                // 5. Filter Siswa (Nama / NISN)
                const matchSiswa = !filterSiswa.value || (
                    (item.nama_siswa && item.nama_siswa.toLowerCase().includes(filterSiswa.value.toLowerCase())) ||
                    (item.nisn && item.nisn.toLowerCase().includes(filterSiswa.value.toLowerCase()))
                );

                // 6. Filter Metode (hanya pemasukan)
                const matchMetode = !filterMetode.value || (item.metode_pembayaran === filterMetode.value);

                // 7. Filter Rentang Tanggal Bayar (hanya pemasukan)
                let matchDate = true;
                if (tipeReport.value === 'pemasukan' && item.tanggal_bayar) {
                    const itemDate = item.tanggal_bayar.substring(0, 10);
                    if (filterDateFrom.value && itemDate < filterDateFrom.value) matchDate = false;
                    if (filterDateTo.value && itemDate > filterDateTo.value) matchDate = false;
                }

                return matchKomponen && matchJenjang && matchKelas && matchTA && matchSiswa && matchMetode && matchDate;
            });
            currentPage.value = 1;
        };

        Vue.watch([filterText, filterJenjang, filterKelas, filterTahunAjaran, filterSiswa, filterDateFrom, filterDateTo, filterMetode], () => {
            applyFilters();
        });

        // Paginated List
        const paginatedList = Vue.computed(() => {
            const start = (currentPage.value - 1) * pageSize.value;
            return filteredList.value.slice(start, start + pageSize.value);
        });

        const totalPages = Vue.computed(() => {
            return Math.ceil(filteredList.value.length / pageSize.value) || 1;
        });

        const getVisiblePages = (current, total) => {
            const delta = 2;
            const left = current - delta;
            const right = current + delta + 1;
            const range = [];
            const rangeWithDots = [];
            let l;

            for (let i = 1; i <= total; i++) {
                if (i === 1 || i === total || (i >= left && i < right)) {
                    range.push(i);
                }
            }

            for (const i of range) {
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
        };

        const visiblePages = Vue.computed(() => {
            return getVisiblePages(currentPage.value, totalPages.value);
        });

        // Genuine XLSX Export using SheetJS
        const exportToExcel = () => {
            if (typeof XLSX === 'undefined') {
                alert('Modul SheetJS XLSX belum siap, silakan muat ulang halaman.');
                return;
            }

            const isPemasukan = (tipeReport.value === 'pemasukan');
            const reportName = isPemasukan ? 'Rekap_Pemasukan' : 'Rekap_Tunggakan';
            const filename = `Laporan_${reportName}_${new Date().toISOString().slice(0,10)}.xlsx`;

            let excelData = [];

            if (isPemasukan) {
                excelData = filteredList.value.map((item, idx) => ({
                    'No': idx + 1,
                    'No Kwitansi': item.nomor_kwitansi || '-',
                    'Nama Siswa': item.nama_siswa || '-',
                    'NISN': item.nisn || '-',
                    'Kelas': item.nama_kelas || '-',
                    'Tahun Ajaran': item.tahun_ajaran || '-',
                    'Komponen Biaya': item.nama_komponen || '-',
                    'Jumlah Bayar (Rp)': Number(item.nominal_dibayar || 0),
                    'Metode Pembayaran': item.metode_pembayaran || '-',
                    'Tanggal Bayar': formatDate(item.tanggal_bayar),
                    'Kasir / Staf': item.nama_kasir || '-'
                }));
            } else {
                excelData = filteredList.value.map((item, idx) => ({
                    'No': idx + 1,
                    'Nama Siswa': item.nama_siswa || '-',
                    'NISN': item.nisn || '-',
                    'Kelas': item.nama_kelas || '-',
                    'Komponen Tagihan': item.nama_komponen || '-',
                    'Tahun Ajaran': item.tahun_ajaran || '-',
                    'Bulan': item.bulan ? getBulanName(item.bulan) : '-',
                    'Nominal Tagihan (Rp)': Number(item.nominal_tagihan || 0),
                    'Sudah Dibayar (Rp)': Number(item.nominal_bayar || 0),
                    'Sisa Kekurangan (Rp)': Number((item.nominal_tagihan || 0) - (item.nominal_bayar || 0))
                }));
            }

            const worksheet = XLSX.utils.json_to_sheet(excelData);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, isPemasukan ? 'Rekap Pemasukan' : 'Rekap Tunggakan');
            XLSX.writeFile(workbook, filename);
        };

        // Print Report with Header Kop & Filters
        const printReport = () => {
            const isPemasukan = (tipeReport.value === 'pemasukan');
            const titleText = isPemasukan ? 'LAPORAN REKAP PEMASUKAN KEUANGAN SEKOLAH' : 'LAPORAN REKAP TUNGGAKAN SISWA';

            let filterSummary = [];
            if (filterText.value) filterSummary.push(`Komponen: ${filterText.value}`);
            if (filterJenjang.value) filterSummary.push(`Jenjang: ${filterJenjang.value}`);
            if (filterKelas.value) filterSummary.push(`Kelas: ${filterKelas.value}`);
            if (filterTahunAjaran.value) filterSummary.push(`Tahun Ajaran: ${filterTahunAjaran.value}`);
            if (isPemasukan && filterDateFrom.value) filterSummary.push(`Dari Tanggal: ${filterDateFrom.value}`);
            if (isPemasukan && filterDateTo.value) filterSummary.push(`Sampai Tanggal: ${filterDateTo.value}`);
            if (isPemasukan && filterMetode.value) filterSummary.push(`Metode: ${filterMetode.value}`);
            
            const filterStr = filterSummary.length > 0 ? filterSummary.join(' | ') : 'Semua Data';

            let printWin = window.open('', '_blank', 'width=1000,height=800');
            let rowsHtml = '';
            let grandTotal = 0;

            if (isPemasukan) {
                filteredList.value.forEach((p, idx) => {
                    grandTotal += Number(p.nominal_dibayar || 0);
                    rowsHtml += `
                        <tr>
                            <td style="text-align: center;">${idx + 1}</td>
                            <td>${p.nomor_kwitansi}</td>
                            <td><strong>${p.nama_siswa}</strong><br><small>NISN: ${p.nisn || '-'}</small></td>
                            <td>${p.nama_kelas || '-'}</td>
                            <td>${p.tahun_ajaran || '-'}</td>
                            <td>${p.nama_komponen}</td>
                            <td style="text-align: right; font-weight: bold;">Rp ${formatNumber(p.nominal_dibayar)}</td>
                            <td style="text-align: center;">${p.metode_pembayaran}</td>
                            <td>${formatDate(p.tanggal_bayar)}</td>
                            <td>${p.nama_kasir}</td>
                        </tr>
                    `;
                });
            } else {
                filteredList.value.forEach((t, idx) => {
                    const sisa = (t.nominal_tagihan || 0) - (t.nominal_bayar || 0);
                    grandTotal += Number(sisa);
                    rowsHtml += `
                        <tr>
                            <td style="text-align: center;">${idx + 1}</td>
                            <td><strong>${t.nama_siswa}</strong><br><small>NISN: ${t.nisn || '-'}</small></td>
                            <td>${t.nama_kelas || '-'}</td>
                            <td>${t.nama_komponen}</td>
                            <td>${t.tahun_ajaran}</td>
                            <td style="text-align: center;">${t.bulan ? getBulanName(t.bulan) : '-'}</td>
                            <td style="text-align: right;">Rp ${formatNumber(t.nominal_tagihan)}</td>
                            <td style="text-align: right; color: green;">Rp ${formatNumber(t.nominal_bayar)}</td>
                            <td style="text-align: right; color: red; font-weight: bold;">Rp ${formatNumber(sisa)}</td>
                        </tr>
                    `;
                });
            }

            const printDoc = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${titleText}</title>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #1e293b; }
                        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
                        .header p { margin: 4px 0 0; font-size: 11px; color: #475569; }
                        .filter-box { background: #f8fafc; padding: 8px 12px; border-radius: 6px; margin-bottom: 15px; font-size: 11px; border: 1px solid #e2e8f0; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px; font-size: 11px; text-transform: uppercase; }
                        td { border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 11px; }
                        .footer { margin-top: 20px; text-align: right; font-weight: bold; font-size: 13px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>${titleText}</h2>
                        <p>Dicetak Pada: ${new Date().toLocaleString('id-ID')}</p>
                    </div>
                    <div class="filter-box">
                        <strong>Kriteria Filter:</strong> ${filterStr} | <strong>Total Data:</strong> ${filteredList.value.length} Rekam
                    </div>
                    <table>
                        <thead>
                            ${isPemasukan ? `
                                <tr>
                                    <th>No</th>
                                    <th>No Kwitansi</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Komponen</th>
                                    <th>Jumlah Bayar</th>
                                    <th>Metode</th>
                                    <th>Tanggal Bayar</th>
                                    <th>Kasir Staf</th>
                                </tr>
                            ` : `
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Komponen Tagihan</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Bulan</th>
                                    <th>Nominal Tagihan</th>
                                    <th>Sudah Dibayar</th>
                                    <th>Sisa Kekurangan</th>
                                </tr>
                            `}
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                    <div class="footer">
                        TOTAL ${isPemasukan ? 'PEMASUKAN' : 'SISA TUNGGAKAN'}: Rp ${formatNumber(grandTotal)}
                    </div>
                </body>
                </html>
            `;

            printWin.document.write(printDoc);
            printWin.document.close();
            printWin.onload = function() {
                printWin.print();
            };
            printWin.onafterprint = function() {
                printWin.close();
            };
        };

        // Helpers
        const getBulanName = (bln) => {
            const list = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return list[bln] || '';
        };

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num || 0);
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };

        Vue.onMounted(async () => {
            if (isSuperAdmin) {
                await fetchTenants();
            }
            await fetchReport();
        });

        return {
            isSuperAdmin,
            tenantsList,
            selectedTenantId,
            komponenList,
            jenjangList,
            kelasList,
            tahunAjaranList,
            tipeReport,
            filteredList,
            filterText,
            filterJenjang,
            filterKelas,
            filterTahunAjaran,
            filterSiswa,
            filterDateFrom,
            filterDateTo,
            filterMetode,
            currentPage,
            pageSize,
            paginatedList,
            totalPages,
            visiblePages,
            setTipe,
            onTenantChange,
            exportToExcel,
            printReport,
            getBulanName,
            formatNumber,
            formatDate
        };
    }
});
</script>
