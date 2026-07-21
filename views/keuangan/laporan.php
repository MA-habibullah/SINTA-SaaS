
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
            <button @click="exportToExcel" class="btn btn-outline-success fw-bold"><i class="bi bi-file-earmark-excel me-2"></i> Ekspor Excel</button>
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
        
        <!-- Filter Form -->
        <div class="row mb-4 bg-light p-3 rounded-3 mx-0">
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="form-label fw-semibold text-slate-700">Filter Komponen Biaya</label>
                <select class="form-select border-slate-200" v-model="filterText">
                    <option value="">Semua Komponen</option>
                    <option v-for="k in komponenList" :value="k.nama_komponen">{{ k.nama_komponen }}</option>
                </select>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="form-label fw-semibold text-slate-700">Pencarian Siswa</label>
                <input type="text" class="form-control border-slate-200" v-model="filterSiswa" placeholder="Nama / NISN siswa...">
            </div>
            <div class="col-md-4" v-if="tipeReport === 'pemasukan'">
                <label class="form-label fw-semibold text-slate-700">Filter Metode</label>
                <select class="form-select border-slate-200" v-model="filterMetode">
                    <option value="">Semua Metode</option>
                    <option value="Tunai">Tunai</option>
                    <option value="Transfer">Transfer Manual</option>
                </select>
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
                            <td>{{ p.nama_komponen }}</td>
                            <td class="fw-bold text-emerald-600">Rp {{ formatNumber(p.nominal_dibayar) }}</td>
                            <td>{{ p.metode_pembayaran }}</td>
                            <td>{{ formatDate(p.tanggal_bayar) }}</td>
                            <td>{{ p.nama_kasir }}</td>
                        </tr>
                        <tr v-if="filteredList.length === 0">
                            <td colspan="7" class="text-center py-4 text-muted">Tidak ada transaksi pemasukan terdeteksi.</td>
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
                            <td>{{ t.nama_komponen }}</td>
                            <td>{{ t.tahun_ajaran }}</td>
                            <td>{{ t.bulan ? getBulanName(t.bulan) : '-' }}</td>
                            <td>Rp {{ formatNumber(t.nominal_tagihan) }}</td>
                            <td class="text-success fw-semibold">Rp {{ formatNumber(t.nominal_bayar) }}</td>
                            <td class="text-danger fw-bold">Rp {{ formatNumber(t.nominal_tagihan - t.nominal_bayar) }}</td>
                        </tr>
                        <tr v-if="filteredList.length === 0">
                            <td colspan="7" class="text-center py-4 text-muted">Tidak ditemukan tunggakan aktif.</td>
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
                    <li class="page-item" v-for="p in totalPages" :key="p" :class="{ active: currentPage === p }">
                        <a class="page-link" href="#" @click.prevent="currentPage = p">{{ p }}</a>
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
/* Styling Navtabs Minimalis Datar (Gambar 1) */
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

/* Styling Tabel Modern Borderless (Gambar 1) */
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
        const komponenList = Vue.ref([]);

        // Filter state
        const filterText = Vue.ref('');
        const filterSiswa = Vue.ref('');
        const filterMetode = Vue.ref('');

        // Pagination state
        const currentPage = Vue.ref(1);
        const pageSize = Vue.ref(10);

        const setTipe = (tipe) => {
            tipeReport.value = tipe;
            fetchReport();
        };

        // Helper query param
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

        const fetchKomponen = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/komponen' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    komponenList.value = res.data;
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
                    rawList.value = res.data;
                    currentPage.value = 1; // reset page
                    applyFilters();
                }
            } catch (err) {
                console.error(err);
            }
        };

        const onTenantChange = () => {
            localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
            fetchKomponen();
            fetchReport();
        };

        const applyFilters = () => {
            filteredList.value = rawList.value.filter(item => {
                const matchText = !filterText.value || (item.nama_komponen && item.nama_komponen.toLowerCase() === filterText.value.toLowerCase());
                const matchSiswa = !filterSiswa.value || (
                    (item.nama_siswa && item.nama_siswa.toLowerCase().includes(filterSiswa.value.toLowerCase())) ||
                    (item.nisn && item.nisn.toLowerCase().includes(filterSiswa.value.toLowerCase()))
                );
                const matchMetode = !filterMetode.value || (item.metode_pembayaran === filterMetode.value);
                return matchText && matchSiswa && matchMetode;
            });
            currentPage.value = 1; // reset page on filter change
        };

        Vue.watch([filterText, filterSiswa, filterMetode], () => {
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

        // Export to Excel simple layout
        const exportToExcel = () => {
            let html = '<table>';
            const headers = document.querySelectorAll('#tableReport th');
            html += '<tr>';
            headers.forEach(h => {
                html += `<th>${h.innerText}</th>`;
            });
            html += '</tr>';

            const rows = document.querySelectorAll('#tableReport tbody tr');
            rows.forEach(r => {
                html += '<tr>';
                r.querySelectorAll('td').forEach(d => {
                    html += `<td>${d.innerText}</td>`;
                });
                html += '</tr>';
            });
            html += '</table>';

            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Laporan_${tipeReport.value}_${new Date().toISOString().slice(0,10)}.xls`;
            a.click();
        };

        // Print report
        const printReport = () => {
            window.print();
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
            await fetchKomponen();
            await fetchReport();
        });

        return {
            isSuperAdmin,
            tenantsList,
            selectedTenantId,
            komponenList,
            tipeReport,
            filteredList,
            filterText,
            filterSiswa,
            filterMetode,
            currentPage,
            pageSize,
            paginatedList,
            totalPages,
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
