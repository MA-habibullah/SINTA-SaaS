<?php

$db = \App\Config\Database::getConnection();
$tenantId = $_SESSION['tenant_id'] ?? '';
$setting = $db->query("SELECT nama_modul, istilah_tagihan, istilah_tunggakan FROM transaksi_spp_pengaturan WHERE tenant_id = '{$tenantId}'")->fetch(PDO::FETCH_ASSOC);

$customModulName = $setting['nama_modul'] ?? 'Keuangan & SPP';
$customTagihanTerm = $setting['istilah_tagihan'] ?? 'Tagihan';
$customTunggakanTerm = $setting['istilah_tunggakan'] ?? 'Tunggakan';
?>

<div id="keuangan-tagihan-saya-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-file-earmark-text-fill text-blue-600 me-2"></i> <?php echo htmlspecialchars($customTagihanTerm); ?> Saya
            </h2>
            <p class="text-muted mb-0">Halaman profil keuangan pribadi Anda. Periksa detail kewajiban dan riwayat pembayaran.</p>
        </div>
    </div>

    <!-- Summary metrics -->
    <div class="row mb-4">
        <div class="col-12 col-md-6 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white d-flex align-items-center flex-row">
                <div class="me-3 p-3 bg-rose-50 text-rose-600 rounded-3">
                    <i class="bi bi-exclamation-circle-fill fs-3"></i>
                </div>
                <div>
                    <small class="text-muted fw-semibold">Sisa <?php echo htmlspecialchars($customTunggakanTerm); ?></small>
                    <h3 class="fw-bold text-rose-600 mb-0">Rp {{ formatNumber(totalTunggakan) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white d-flex align-items-center flex-row">
                <div class="me-3 p-3 bg-emerald-50 text-emerald-600 rounded-3">
                    <i class="bi bi-check-circle-fill fs-3"></i>
                </div>
                <div>
                    <small class="text-muted fw-semibold">Total Terbayar</small>
                    <h3 class="fw-bold text-emerald-600 mb-0">Rp {{ formatNumber(totalTerbayar) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Table of Personal Invoices -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Rincian <?php echo htmlspecialchars($customTagihanTerm); ?></h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
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
                            <span class="badge rounded px-3 py-2" :class="getStatusBadgeClass(t.status_lunas)">
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
.bg-rose-50 { background-color: #fff1f2; }
.text-rose-600 { color: #e11d48; }
.bg-emerald-50 { background-color: #ecfdf5; }
.text-emerald-600 { color: #059669; }
.border-slate-200 { border-color: #e2e8f0; }
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
