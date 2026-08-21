<?php
/**
 * View: Denda & Billing SPP Integrasi Perpustakaan
 * Zero Data Leakage: Data dimuat async via Axios — tidak ada data mentah di View Source.
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">💰 Denda &amp; Billing Integrasi SPP</h2>
        <p class="text-muted fs-7 mb-0">Manajemen Pembayaran Denda Keterlambatan Tunai / Otomatis Masuk Tagihan SPP Keuangan Sekolah.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="<?= $this->getBaseUrl() ?>/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<!-- Vue 3 Async App (Zero Data Leakage) -->
<div id="dendaApp" v-cloak>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <!-- Loading Skeleton -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-warning" role="status"></div>
            <p class="text-muted mt-2 mb-0">Memuat data denda secara asinkron...</p>
        </div>

        <!-- Data Table -->
        <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa / Anggota</th>
                        <th>Judul Buku</th>
                        <th>Terlambat (Hari)</th>
                        <th>Nominal Denda</th>
                        <th>Status Pembayaran</th>
                        <th>Metode Bayar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="dendaList.length === 0">
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-cash-coin fs-3 d-block mb-2 text-success"></i> Tidak ada tunggakan denda perpustakaan yang belum dibayar.
                        </td>
                    </tr>
                    <tr v-for="(d, idx) in dendaList" :key="d.id">
                        <td>{{ idx + 1 }}</td>
                        <td><strong>{{ d.nama_siswa }}</strong></td>
                        <td>{{ d.judul_buku || '-' }}</td>
                        <td><span class="badge bg-warning text-dark">{{ d.hari_terlambat }} Hari</span></td>
                        <td><strong class="text-danger">Rp {{ formatRupiah(d.jumlah_denda) }}</strong></td>
                        <td>
                            <span v-if="d.status === 'Lunas'" class="badge bg-success">Lunas</span>
                            <span v-else class="badge bg-danger">Belum Lunas</span>
                        </td>
                        <td>{{ d.metode_bayar || 'Tunai / SPP' }}</td>
                        <td class="text-center">
                            <button v-if="d.status !== 'Lunas'" class="btn btn-success btn-sm rounded-2 me-1" @click="bayarDenda(d)">
                                <i class="bi bi-check-circle me-1"></i> Bayar Tunai
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(() => {
    const { createApp, ref, onMounted } = Vue;
    const baseUrl = '<?= $this->getBaseUrl() ?>';

    const appConfig = {
        setup() {
            const dendaList = ref([]);
            const loading = ref(true);

            const formatRupiah = (val) => {
                return Number(val || 0).toLocaleString('id-ID');
            };

            const fetchData = async () => {
                loading.value = true;
                try {
                    const res = await axios.get(`${baseUrl}/api/v1/perpustakaan/denda`);
                    if (res.data && res.data.success) {
                        dendaList.value = res.data.data || [];
                    }
                } catch (err) {
                    console.error('Gagal memuat data denda:', err);
                } finally {
                    loading.value = false;
                }
            };

            const bayarDenda = async (item) => {
                const confirm = await Swal.fire({
                    title: 'Konfirmasi Pembayaran',
                    html: `Tandai denda <strong>${item.nama_siswa}</strong> sebesar <strong>Rp ${formatRupiah(item.jumlah_denda)}</strong> sebagai <em>Lunas (Tunai)</em>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Bayar!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#198754'
                });
                if (!confirm.isConfirmed) return;

                try {
                    const res = await axios.post(`${baseUrl}/api/v1/perpustakaan/denda/bayar`, {
                        denda_id: item.denda_id || item.id
                    });
                    if (res.data && res.data.success) {
                        item.status = 'Lunas';
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Pembayaran denda dicatat.', timer: 1800, showConfirmButton: false });
                    }
                } catch (err) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memproses pembayaran denda.' });
                }
            };

            onMounted(fetchData);

            return { dendaList, loading, formatRupiah, bayarDenda };
        }
    };

    if (window.VueAppRegistry) {
        window.VueAppRegistry.register('#dendaApp', appConfig);
    } else {
        document.addEventListener('DOMContentLoaded', () => createApp(appConfig).mount('#dendaApp'));
    }
})();
</script>
