
<div id="keuangan-laporan-app" v-cloak class="container-fluid px-3 py-3 workspace-container">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-2 header-section">
        <div>
            <h5 class="fw-bold text-slate-800 mb-0" style="font-size: 1.1rem;">
                <i class="bi bi-graph-up-arrow text-blue-600 me-2"></i> Laporan Keuangan Sekolah
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.72rem;">Lihat rekapitulasi uang masuk harian/bulanan serta detail data tunggakan siswa.</p>
        </div>
        <div class="d-flex gap-2">
            <button @click="exportToExcel" class="btn btn-outline-success btn-compact"><i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel</button>
            <button @click="printReport" class="btn btn-outline-primary btn-compact"><i class="bi bi-printer me-1"></i> Cetak</button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs border-bottom border-slate-200 mb-2" id="laporanTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-slate-700 py-2" id="pemasukan-tab" data-bs-toggle="tab" data-bs-target="#pemasukan-pane" type="button" role="tab" @click="setTipe('pemasukan')" style="font-size: 0.8rem;">
                <i class="bi bi-cash-stack me-2 text-emerald-600"></i> Rekap Pemasukan
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-slate-700 py-2" id="tunggakan-tab" data-bs-toggle="tab" data-bs-target="#tunggakan-pane" type="button" role="tab" @click="setTipe('tunggakan')" style="font-size: 0.8rem;">
                <i class="bi bi-exclamation-triangle-fill me-2 text-rose-600"></i> Rekap Tunggakan
            </button>
        </li>
    </ul>

    <!-- Workspace Body / Table Container -->
    <div class="panel-table flex-grow-1 min-height-0">
        <!-- Filter Form embedded in Panel Header -->
        <div class="panel-header" style="padding: 0.4rem 0.75rem;">
            <div class="row g-2 align-items-center w-100 mx-0 form-compact">
                <div class="col-12 col-md-4 px-1">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 text-nowrap" style="font-size: 0.7rem; min-width: 90px;">Komponen:</label>
                        <input type="text" class="form-control" v-model="filterText" placeholder="Cari komponen...">
                    </div>
                </div>
                <div class="col-12 col-md-4 px-1">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 text-nowrap" style="font-size: 0.7rem; min-width: 90px;">Cari Siswa:</label>
                        <input type="text" class="form-control" v-model="filterSiswa" placeholder="Nama / NISN...">
                    </div>
                </div>
                <div class="col-12 col-md-4 px-1" v-if="tipeReport === 'pemasukan'">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 text-nowrap" style="font-size: 0.7rem; min-width: 90px;">Metode:</label>
                        <select class="form-select" v-model="filterMetode">
                            <option value="">Semua Metode</option>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer">Transfer Manual</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-content p-0">
            <!-- Tab Content -->
            <div class="tab-content h-100" id="laporanTabsContent">
                
                <!-- Tab 1: Rekap Pemasukan -->
                <div class="tab-pane fade show active h-100" id="pemasukan-pane" role="tabpanel" v-if="tipeReport === 'pemasukan'">
                    <div class="table-compact-container">
                        <table class="table table-hover table-compact table-bordered" id="tableReport">
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
                                <tr v-for="p in filteredList" :key="p.id">
                                    <td><span class="badge bg-slate-100 text-slate-800 fw-bold px-2 py-1 border border-slate-200 badge-custom">{{ p.nomor_kwitansi }}</span></td>
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
                <div class="tab-pane fade show active h-100" id="tunggakan-pane" role="tabpanel" v-else>
                    <div class="table-compact-container">
                        <table class="table table-hover table-compact table-bordered" id="tableReport">
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
                                <tr v-for="t in filteredList" :key="t.id">
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

            </div>
        </div>
    </div>
</div>

<style>
.workspace-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - var(--header-height) - 1.5rem);
    overflow: hidden;
}
.panel-table {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    min-height: 0;
}
.panel-header {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
}
.panel-content {
    padding: 0;
    overflow-y: auto;
    flex-grow: 1;
    min-height: 0;
}
.form-compact .form-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.2rem;
}
.form-compact .form-control,
.form-compact .form-select {
    padding: 0.35rem 0.6rem;
    font-size: 0.8rem;
    border-radius: 6px;
    border-color: #cbd5e1;
    height: 32px;
}
.btn-compact {
    padding: 0.35rem 0.75rem;
    font-size: 0.8rem;
    border-radius: 6px;
    font-weight: 600;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.table-compact-container {
    overflow-y: auto;
    flex-grow: 1;
    min-height: 0;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}
.table-compact {
    font-size: 0.8rem;
    margin-bottom: 0;
    width: 100%;
}
.table-compact th {
    background-color: #f1f5f9;
    color: #334155;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 2px solid #cbd5e1;
    padding: 0.5rem 0.75rem;
}
.table-compact td {
    padding: 0.4rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.badge-custom {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
}
.fs-7 { font-size: 0.85rem; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }

/* Responsive Mobile Stack (HP) */
@media (max-width: 767.98px) {
    .workspace-container {
        height: auto !important;
        overflow: visible !important;
    }
    .panel-table {
        overflow: visible !important;
        margin-top: 0.5rem;
    }
    .panel-content {
        overflow: visible !important;
    }
    .table-compact-container {
        overflow-y: visible !important;
        overflow-x: auto !important;
    }
    .table-compact th {
        position: static !important;
    }
}
</style>

<script>
window.VueAppRegistry.register('#keuangan-laporan-app', {
    setup() {
        const tipeReport = Vue.ref('pemasukan');
        const rawList = Vue.ref([]);
        const filteredList = Vue.ref([]);

        // Filter state
        const filterText = Vue.ref('');
        const filterSiswa = Vue.ref('');
        const filterMetode = Vue.ref('');

        const setTipe = (tipe) => {
            tipeReport.value = tipe;
            fetchReport();
        };

        const fetchReport = async () => {
            try {
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/laporan-rekap?tipe=${tipeReport.value}`);
                const res = await response.json();
                if (res.success) {
                    rawList.value = res.data;
                    applyFilters();
                }
            } catch (err) {
                console.error(err);
            }
        };

        const applyFilters = () => {
            filteredList.value = rawList.value.filter(item => {
                const matchText = !filterText.value || (item.nama_komponen && item.nama_komponen.toLowerCase().includes(filterText.value.toLowerCase()));
                const matchSiswa = !filterSiswa.value || (
                    (item.nama_siswa && item.nama_siswa.toLowerCase().includes(filterSiswa.value.toLowerCase())) ||
                    (item.nisn && item.nisn.toLowerCase().includes(filterSiswa.value.toLowerCase()))
                );
                const matchMetode = !filterMetode.value || (item.metode_pembayaran === filterMetode.value);
                return matchText && matchSiswa && matchMetode;
            });
        };

        Vue.watch([filterText, filterSiswa, filterMetode], () => {
            applyFilters();
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

        Vue.onMounted(() => {
            fetchReport();
        });

        return {
            tipeReport,
            filteredList,
            filterText,
            filterSiswa,
            filterMetode,
            setTipe,
            exportToExcel,
            printReport,
            getBulanName,
            formatNumber,
            formatDate
        };
    }
});
</script>
