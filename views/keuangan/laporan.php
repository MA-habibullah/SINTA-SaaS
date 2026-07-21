<?php include __DIR__ . '/../layout/header.php'; ?>

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

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs border-bottom border-slate-200 mb-4" id="laporanTabs" role="tablist">
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
                <label class="form-label fw-semibold text-slate-700">Filter Komponen</label>
                <input type="text" class="form-control border-slate-200" v-model="filterText" placeholder="Cari komponen tagihan...">
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
                    <thead class="table-light text-slate-700">
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
                    <thead class="table-light text-slate-700">
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

<style>
.fs-7 { font-size: 0.85rem; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }
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
