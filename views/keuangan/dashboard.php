
<div id="keuangan-dashboard-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-speedometer2 text-blue-600 me-2"></i> Dashboard Keuangan
            </h2>
            <p class="text-muted mb-0">Ringkasan real-time arus kas, penerimaan pembayaran, dan monitoring tunggakan siswa.</p>
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
                Menampilkan performa keuangan secara real-time untuk sekolah terpilih.
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <!-- Card 1: Pemasukan Hari Ini -->
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-row align-items-center">
                <div class="me-3 p-3 bg-emerald-50 text-emerald-600 rounded-3">
                    <i class="bi bi-wallet2 fs-3"></i>
                </div>
                <div>
                    <small class="text-muted fw-semibold">Pemasukan Hari Ini</small>
                    <h3 class="fw-bold text-slate-800 mb-0">Rp {{ formatNumber(metrics.pemasukan_hari_ini) }}</h3>
                </div>
            </div>
        </div>

        <!-- Card 2: Pemasukan Bulan Ini -->
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-row align-items-center">
                <div class="me-3 p-3 bg-blue-50 text-blue-600 rounded-3">
                    <i class="bi bi-calendar-check fs-3"></i>
                </div>
                <div>
                    <small class="text-muted fw-semibold">Pemasukan Bulan Ini</small>
                    <h3 class="fw-bold text-slate-800 mb-0">Rp {{ formatNumber(metrics.pemasukan_bulan_ini) }}</h3>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Sisa Tunggakan -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-row align-items-center">
                <div class="me-3 p-3 bg-rose-50 text-rose-600 rounded-3">
                    <i class="bi bi-exclamation-octagon fs-3"></i>
                </div>
                <div>
                    <small class="text-muted fw-semibold">Total Tunggakan Aktif</small>
                    <h3 class="fw-bold text-rose-600 mb-0">Rp {{ formatNumber(metrics.total_tunggakan) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Progres Kelas & Tunggakan Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">
                    <i class="bi bi-graph-up-arrow text-blue-600 me-2"></i> Rekapitulasi Progres Pelunasan Kelas
                </h5>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama Kelas</th>
                                <th>Total Tagihan</th>
                                <th>Total Terbayar</th>
                                <th>Sisa Tunggakan</th>
                                <th style="width: 250px;">Prosentase Lunas</th>
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
                                        <div class="progress flex-fill me-2" style="height: 8px;">
                                            <div class="progress-bar bg-success rounded" role="progressbar" :style="{ width: getPercentage(item.total_bayar, item.total_tagihan) + '%' }"></div>
                                        </div>
                                        <span class="fw-bold fs-7">{{ getPercentage(item.total_bayar, item.total_tagihan) }}%</span>
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
</div>

<!-- Data Injection -->
<script id="user-session" type="application/json">
    <?php echo json_encode([
        'is_super_admin' => (($_SESSION['role_name'] ?? '') === 'super_admin'),
        'tenant_id' => ($_SESSION['tenant_id'] ?? '')
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<style>
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
.bg-emerald-50 { background-color: #ecfdf5; }
.text-emerald-600 { color: #059669; }
.bg-blue-50 { background-color: #eff6ff; }
.text-blue-600 { color: #2563eb; }
.bg-rose-50 { background-color: #fff1f2; }
.text-rose-600 { color: #e11d48; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }
</style>

<script>
window.VueAppRegistry.register('#keuangan-dashboard-app', {
    setup() {
        const session = JSON.parse(document.getElementById('user-session').textContent || '{}');
        const isSuperAdmin = session.is_super_admin;
        const tenantsList = Vue.ref([]);
        const selectedTenantId = Vue.ref(session.tenant_id || '');

        const metrics = Vue.ref({
            total_tunggakan: 0,
            pemasukan_hari_ini: 0,
            pemasukan_bulan_ini: 0,
            progres_kelas: []
        });

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

        const fetchMetrics = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/dashboard-metrics' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    metrics.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const onTenantChange = () => {
            localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
            fetchMetrics();
        };

        const getPercentage = (bayar, tagihan) => {
            const t = parseFloat(tagihan);
            if (!t || t <= 0) return 0;
            return Math.round((parseFloat(bayar) / t) * 100);
        };

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num || 0);
        };

        Vue.onMounted(async () => {
            if (isSuperAdmin) {
                await fetchTenants();
            }
            await fetchMetrics();
        });

        return {
            isSuperAdmin,
            tenantsList,
            selectedTenantId,
            metrics,
            onTenantChange,
            getPercentage,
            formatNumber
        };
    }
});
</script>
