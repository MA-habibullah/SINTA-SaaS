
<div id="keuangan-dashboard-app" v-cloak class="container-fluid px-3 py-3 workspace-container">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h5 class="fw-bold text-slate-800 mb-0" style="font-size: 1.1rem;">
                <i class="bi bi-speedometer2 text-blue-600 me-2"></i> Dashboard Keuangan
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.72rem;">Ringkasan real-time arus kas, penerimaan pembayaran, dan monitoring tunggakan siswa.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-2 mb-2">
        <!-- Card 1: Pemasukan Hari Ini -->
        <div class="col-12 col-md-4">
            <div class="card border-slate-200 shadow-sm rounded-3 p-2 px-3 bg-white d-flex flex-row align-items-center height-card">
                <div class="me-3 p-2 bg-emerald-50 text-emerald-600 rounded-3">
                    <i class="bi bi-wallet2 fs-5"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.72rem;">Pemasukan Hari Ini</div>
                    <h5 class="fw-bold text-slate-800 mb-0" style="font-size: 1.05rem;">Rp {{ formatNumber(metrics.pemasukan_hari_ini) }}</h5>
                </div>
            </div>
        </div>

        <!-- Card 2: Pemasukan Bulan Ini -->
        <div class="col-12 col-md-4">
            <div class="card border-slate-200 shadow-sm rounded-3 p-2 px-3 bg-white d-flex flex-row align-items-center height-card">
                <div class="me-3 p-2 bg-blue-50 text-blue-600 rounded-3">
                    <i class="bi bi-calendar-check fs-5"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.72rem;">Pemasukan Bulan Ini</div>
                    <h5 class="fw-bold text-slate-800 mb-0" style="font-size: 1.05rem;">Rp {{ formatNumber(metrics.pemasukan_bulan_ini) }}</h5>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Sisa Tunggakan -->
        <div class="col-12 col-md-4">
            <div class="card border-slate-200 shadow-sm rounded-3 p-2 px-3 bg-white d-flex flex-row align-items-center height-card">
                <div class="me-3 p-2 bg-rose-50 text-rose-600 rounded-3">
                    <i class="bi bi-exclamation-octagon fs-5"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.72rem;">Total Tunggakan Aktif</div>
                    <h5 class="fw-bold text-rose-600 mb-0" style="font-size: 1.05rem;">Rp {{ formatNumber(metrics.total_tunggakan) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Progres Kelas & Tunggakan Table -->
    <div class="panel-table flex-grow-1 min-height-0">
        <div class="panel-header">
            <span class="fw-bold text-slate-800" style="font-size: 0.82rem;">
                <i class="bi bi-graph-up-arrow text-blue-600 me-2"></i> Rekapitulasi Progres Pelunasan Kelas
            </span>
        </div>
        <div class="panel-content p-0">
            <div class="table-compact-container">
                <table class="table table-hover table-compact table-bordered">
                    <thead>
                        <tr>
                            <th>Nama Kelas</th>
                            <th>Total Tagihan</th>
                            <th>Total Terbayar</th>
                            <th>Sisa Tunggakan</th>
                            <th style="width: 250px;">Persentase Lunas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in metrics.progres_kelas" :key="item.nama_kelas">
                            <td class="fw-bold text-slate-800">Kelas {{ item.nama_kelas }}</td>
                            <td>Rp {{ formatNumber(item.total_tagihan) }}</td>
                            <td class="text-success fw-semibold">Rp {{ formatNumber(item.total_bayar) }}</td>
                            <td class="text-danger fw-semibold">Rp {{ formatNumber(item.total_tagihan - item.total_bayar) }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-fill me-2" style="height: 6px;">
                                        <div class="progress-bar bg-success rounded" role="progressbar" :style="{ width: getPercentage(item.total_bayar, item.total_tagihan) + '%' }"></div>
                                    </div>
                                    <span class="fw-bold" style="font-size: 0.75rem;">{{ getPercentage(item.total_bayar, item.total_tagihan) }}%</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!metrics.progres_kelas || metrics.progres_kelas.length === 0">
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data progres transaksi keuangan terbit.</td>
                        </tr>
                    </tbody>
                </table>
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
.table-compact-container {
    overflow-y: auto;
    flex-grow: 1;
    min-height: 0;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    background: #ffffff;
}
.table-compact {
    border-collapse: collapse !important;
    font-size: 0.8rem;
    margin-bottom: 0;
    width: 100%;
}
.table-compact th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.72rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 2px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.6rem 0.75rem !important;
}
.table-compact td {
    border-bottom: 1px solid #f1f5f9 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.52rem 0.75rem !important;
    vertical-align: middle;
    white-space: nowrap;
    color: #334155 !important;
    background-color: transparent !important;
}
.table-compact tbody tr {
    transition: background-color 0.15s ease;
}
.table-compact tbody tr:hover {
    background-color: #f8fafc !important;
}
.height-card {
    height: 60px;
}
.fs-7 { font-size: 0.85rem; }
.bg-emerald-50 { background-color: #ecfdf5; }
.text-emerald-600 { color: #059669; }
.bg-blue-50 { background-color: #eff6ff; }
.text-blue-600 { color: #2563eb; }
.bg-rose-50 { background-color: #fff1f2; }
.text-rose-600 { color: #e11d48; }
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
window.VueAppRegistry.register('#keuangan-dashboard-app', {
    setup() {
        const metrics = Vue.ref({
            total_tunggakan: 0,
            pemasukan_hari_ini: 0,
            pemasukan_bulan_ini: 0,
            progres_kelas: []
        });

        const fetchMetrics = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/dashboard-metrics');
                const res = await response.json();
                if (res.success) {
                    metrics.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const getPercentage = (bayar, tagihan) => {
            const t = parseFloat(tagihan);
            if (!t || t <= 0) return 0;
            return Math.round((parseFloat(bayar) / t) * 100);
        };

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num || 0);
        };

        Vue.onMounted(() => {
            fetchMetrics();
        });

        return {
            metrics,
            getPercentage,
            formatNumber
        };
    }
});
</script>
