<?php

$db = \App\Config\Database::getConnection();
$tenantId = $_SESSION['tenant_id'] ?? '';
$setting = $db->query("SELECT nama_modul, istilah_tagihan, istilah_tunggakan FROM transaksi_spp_pengaturan WHERE tenant_id = '{$tenantId}'")->fetch(PDO::FETCH_ASSOC);

$customModulName = $setting['nama_modul'] ?? 'Keuangan & SPP';
$customTagihanTerm = $setting['istilah_tagihan'] ?? 'Tagihan';
$customTunggakanTerm = $setting['istilah_tunggakan'] ?? 'Tunggakan';
?>

<div id="keuangan-tagihan-saya-app" v-cloak class="container-fluid px-3 py-3 workspace-container">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h5 class="fw-bold text-slate-800 mb-0" style="font-size: 1.1rem;">
                <i class="bi bi-file-earmark-text-fill text-blue-600 me-2"></i> <?php echo htmlspecialchars($customTagihanTerm); ?> Saya
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.72rem;">Halaman profil keuangan pribadi Anda. Periksa detail kewajiban dan riwayat pembayaran.</p>
        </div>
    </div>

    <!-- Summary metrics -->
    <div class="row g-2 mb-2">
        <div class="col-12 col-md-6">
            <div class="card border-slate-200 shadow-sm rounded-3 p-2 px-3 bg-white d-flex align-items-center flex-row height-card">
                <div class="me-3 p-2 bg-rose-50 text-rose-600 rounded-3">
                    <i class="bi bi-exclamation-circle-fill fs-5"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.72rem;">Sisa <?php echo htmlspecialchars($customTunggakanTerm); ?></div>
                    <h5 class="fw-bold text-rose-600 mb-0" style="font-size: 1.05rem;">Rp {{ formatNumber(totalTunggakan) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-slate-200 shadow-sm rounded-3 p-2 px-3 bg-white d-flex align-items-center flex-row height-card">
                <div class="me-3 p-2 bg-emerald-50 text-emerald-600 rounded-3">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.72rem;">Total Terbayar</div>
                    <h5 class="fw-bold text-emerald-600 mb-0" style="font-size: 1.05rem;">Rp {{ formatNumber(totalTerbayar) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Table of Personal Invoices -->
    <div class="panel-table flex-grow-1 min-height-0">
        <div class="panel-header">
            <span class="fw-bold text-slate-800" style="font-size: 0.82rem;">Rincian <?php echo htmlspecialchars($customTagihanTerm); ?></span>
        </div>
        <div class="panel-content p-0">
            <div class="table-compact-container">
                <table class="table table-hover table-compact table-bordered">
                    <thead>
                        <tr>
                            <th>Komponen Pembayaran</th>
                            <th>Tahun Ajaran</th>
                            <th>Periode</th>
                            <th>Nominal Kewajiban</th>
                            <th>Jumlah Terbayar</th>
                            <th>Kekurangan Sisa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in tagihanList" :key="t.id">
                            <td class="fw-bold text-slate-800">{{ t.nama_komponen }}</td>
                            <td>{{ t.tahun_ajaran }}</td>
                            <td>
                                <span v-if="t.bulan">{{ getBulanName(t.bulan) }}</span>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td>Rp {{ formatNumber(t.nominal_tagihan) }}</td>
                            <td class="text-success fw-semibold">Rp {{ formatNumber(t.nominal_bayar) }}</td>
                            <td class="text-danger fw-bold">Rp {{ formatNumber(t.nominal_tagihan - t.nominal_bayar) }}</td>
                            <td>
                                <span class="badge rounded px-2 py-1 badge-custom" :class="getStatusBadgeClass(t.status_lunas)">
                                    {{ t.status_lunas }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="tagihanList.length === 0">
                            <td colspan="7" class="text-center py-4 text-muted">Tidak ada tagihan terdaftar atas nama Anda.</td>
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
.height-card {
    height: 60px;
}
.fs-7 { font-size: 0.85rem; }
.bg-rose-50 { background-color: #fff1f2; }
.text-rose-600 { color: #e11d48; }
.bg-emerald-50 { background-color: #ecfdf5; }
.text-emerald-600 { color: #059669; }
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
window.VueAppRegistry.register('#keuangan-tagihan-saya-app', {
    setup() {
        const tagihanList = Vue.ref([]);
        const totalTunggakan = Vue.ref(0);
        const totalTerbayar = Vue.ref(0);

        const fetchTagihanSaya = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/tagihan-saya');
                const res = await response.json();
                if (res.success) {
                    tagihanList.value = res.data;
                    calculateTotals();
                }
            } catch (err) {
                console.error(err);
            }
        };

        const calculateTotals = () => {
            totalTunggakan.value = tagihanList.value.reduce((sum, t) => sum + (parseFloat(t.nominal_tagihan) - parseFloat(t.nominal_bayar)), 0);
            totalTerbayar.value = tagihanList.value.reduce((sum, t) => sum + parseFloat(t.nominal_bayar), 0);
        };

        const getStatusBadgeClass = (status) => {
            switch(status) {
                case 'Lunas': return 'bg-success text-white';
                case 'Cicil': return 'bg-warning text-dark';
                default: return 'bg-danger text-white';
            }
        };

        const getBulanName = (bln) => {
            const list = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return list[bln] || '';
        };

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num);
        };

        Vue.onMounted(() => {
            fetchTagihanSaya();
        });

        return {
            tagihanList,
            totalTunggakan,
            totalTerbayar,
            getStatusBadgeClass,
            getBulanName,
            formatNumber
        };
    }
});
</script>
