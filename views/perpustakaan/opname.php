<?php
/**
 * View: Stock Opname Audit Koleksi Fisik
 * Zero Data Leakage: Data dimuat async via Axios — tidak ada data mentah di View Source.
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">📦 Stock Opname &amp; Audit Inventaris Buku</h2>
        <p class="text-muted fs-7 mb-0">Audit Ketersediaan Buku Fisik di Rak, Pengecekan Selisih Hilang, &amp; Penyesuaian Status Eksemplar.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="<?= $this->getBaseUrl() ?>/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-7" data-bs-toggle="modal" data-bs-target="#modalOpnameBaru">
            <i class="bi bi-qr-code-scan me-1"></i> Mulai Sesi Opname Baru
        </button>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<!-- Vue 3 Async App (Zero Data Leakage) -->
<div id="opnameApp" v-cloak>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <!-- Loading Skeleton -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0">Memuat data stock opname secara asinkron...</p>
        </div>

        <!-- Data Table -->
        <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Sekolah / Tenant</th>
                        <th>Judul Sesi Opname</th>
                        <th>Tanggal Pelaksanaan</th>
                        <th>Petugas Pustakawan</th>
                        <th>Total Buku Di-Scan</th>
                        <th>Buku Hilang / Selisih</th>
                        <th>Status Audit</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="opnameList.length === 0">
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-qr-code-scan fs-3 d-block mb-2 text-primary"></i> Belum ada sesi stock opname aktif. Klik <strong>Mulai Sesi Opname Baru</strong> untuk audit fisik rak.
                        </td>
                    </tr>
                    <tr v-for="(op, idx) in opnameList" :key="op.id">
                        <td>{{ idx + 1 }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-building me-1 text-primary"></i>{{ op.tenant_name || 'Sekolah Aktif' }}
                            </span>
                        </td>
                        <td><strong>{{ op.nama_sesi }}</strong></td>
                        <td>{{ op.tanggal || '-' }}</td>
                        <td>{{ op.petugas || '-' }}</td>
                        <td>{{ op.total_scanned || 0 }} Eksemplar</td>
                        <td><span class="badge bg-danger">{{ op.total_selisih || 0 }} Hilang</span></td>
                        <td><span class="badge bg-success">Selesai</span></td>
                        <td class="text-center">
                            <a href="<?= $this->getBaseUrl() ?>/perpustakaan/cetak-laporan-ddc" class="btn btn-outline-primary btn-sm rounded-2">
                                <i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan Audit
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Mulai Sesi Opname Baru -->
<div class="modal fade" id="modalOpnameBaru" tabindex="-1" aria-labelledby="modalOpnameBaruLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalOpnameBaruLabel"><i class="bi bi-qr-code-scan me-2"></i> Buat Sesi Audit Stock Opname</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= htmlspecialchars($this->getBaseUrl(), ENT_QUOTES, 'UTF-8') ?>/perpustakaan/opname" method="POST" data-turbo="false">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Sesi Audit Opname <span class="text-danger">*</span></label>
                        <input type="text" name="nama_sesi" class="form-control rounded-3" value="Stock Opname Semester <?= date('Y') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Petugas Pustakawan</label>
                        <input type="text" name="petugas" class="form-control rounded-3" value="Tim Pustakawan Utama">
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-play-fill me-1"></i> Mulai Sesi Audit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const { createApp, ref, onMounted } = Vue;
    const baseUrl = '<?= $this->getBaseUrl() ?>';

    const appConfig = {
        setup() {
            const opnameList = ref([]);
            const loading = ref(true);

            const fetchData = async () => {
                loading.value = true;
                try {
                    const res = await axios.get(`${baseUrl}/api/v1/perpustakaan/opname`);
                    if (res.data && res.data.success) {
                        opnameList.value = res.data.data || [];
                    }
                } catch (err) {
                    console.error('Gagal memuat data opname:', err);
                } finally {
                    loading.value = false;
                }
            };

            onMounted(fetchData);

            return { opnameList, loading };
        }
    };

    if (window.VueAppRegistry) {
        window.VueAppRegistry.register('#opnameApp', appConfig);
    } else {
        document.addEventListener('DOMContentLoaded', () => createApp(appConfig).mount('#opnameApp'));
    }
})();
</script>
