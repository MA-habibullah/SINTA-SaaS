<?php
/**
 * View: Peminjaman Buku Paket Pelajaran per Kelas / Semester
 * Zero Data Leakage: Data dimuat async via Axios — tidak ada data mentah di View Source.
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">📦 Peminjaman Buku Paket Pelajaran</h2>
        <p class="text-muted fs-7 mb-0">Distribusi Massal 1 Set Buku Paket Pelajaran untuk Dibawa Pulang 1 Semester / 1 Tahun Ajaran.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="<?= $this->getBaseUrl() ?>/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-7" data-bs-toggle="modal" data-bs-target="#modalDistribusiPaket">
            <i class="bi bi-box-seam me-1"></i> Distribusi Paket Baru
        </button>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<!-- Info Alert -->
<div class="alert alert-info border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-3 shadow-sm">
    <i class="bi bi-info-circle-fill text-info fs-3"></i>
    <div class="fs-7">
        <strong>Fitur Buku Paket Sekolah:</strong> Sistem mencatat peminjaman paket buku teks pelajaran per kelas/siswa secara otomatis untuk durasi 1 semester/tahun ajaran. Laporan peminjaman paket per siswa dapat dicetak saat kenaikan kelas atau kelulusan.
    </div>
</div>

<!-- Data Table — Vue 3 Async Render (Zero Data Leakage) -->
<div id="bukuPaketApp" v-cloak>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <!-- Loading Skeleton -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0">Memuat data buku paket secara asinkron...</p>
        </div>

        <!-- Data Table -->
        <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Sekolah / Tenant</th>
                        <th>Nama Paket</th>
                        <th>Kelas / Tingkat</th>
                        <th>Tahun Ajaran</th>
                        <th>Total Judul</th>
                        <th>Siswa Penerima</th>
                        <th>Status Pengembalian</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="paketList.length === 0">
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-box-seam fs-3 d-block mb-2"></i> Belum ada rekaman distribusi buku paket pelajaran. Klik <strong>Distribusi Paket Baru</strong> untuk memulai.
                        </td>
                    </tr>
                    <tr v-for="(p, idx) in paketList" :key="p.id">
                        <td>{{ idx + 1 }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-building me-1 text-primary"></i>{{ p.tenant_name || 'Sekolah Aktif' }}
                            </span>
                        </td>
                        <td><strong>{{ p.nama_paket }}</strong></td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ p.kelas || '-' }}</span></td>
                        <td>{{ p.tahun_ajaran || '-' }}</td>
                        <td>{{ p.total_buku || 0 }} Judul</td>
                        <td>{{ p.total_siswa || 0 }} Siswa</td>
                        <td><span class="badge bg-success">Berjalan (Semester 1)</span></td>
                        <td class="text-center">
                            <a href="<?= $this->getBaseUrl() ?>/perpustakaan/cetak-laporan-peminjaman" class="btn btn-outline-primary btn-sm rounded-2 me-1" title="Cetak Laporan Peminjaman Per Siswa">
                                <i class="bi bi-printer me-1"></i> Cetak Laporan
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Distribusi Paket Baru -->
<div class="modal fade" id="modalDistribusiPaket" tabindex="-1" aria-labelledby="modalDistribusiPaketLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalDistribusiPaketLabel"><i class="bi bi-box-seam me-2"></i> Form Distribusi Buku Paket Pelajaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $this->getBaseUrl() ?>/perpustakaan/buku-paket" method="POST" data-turbo="false">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nama Paket Pelajaran <span class="text-danger">*</span></label>
                            <input type="text" name="nama_paket" class="form-control rounded-3" placeholder="Contoh: Paket Teks Kurikulum Merdeka Kelas X" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Target Kelas</label>
                            <select name="kelas" class="form-select rounded-3" required>
                                <option value="X-IPA-1">X IPA 1</option>
                                <option value="X-IPA-2">X IPA 2</option>
                                <option value="XI-IPA-1">XI IPA 1</option>
                                <option value="XII-IPA-1">XII IPA 1</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-check-circle me-1"></i> Simpan &amp; Distribusikan</button>
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
            const paketList = ref([]);
            const loading = ref(true);

            const fetchData = async () => {
                loading.value = true;
                try {
                    const res = await axios.get(`${baseUrl}/api/v1/perpustakaan/paket-buku`);
                    if (res.data && res.data.success) {
                        paketList.value = res.data.data || [];
                    }
                } catch (err) {
                    console.error('Gagal memuat data buku paket:', err);
                } finally {
                    loading.value = false;
                }
            };

            onMounted(fetchData);

            return { paketList, loading };
        }
    };

    if (window.VueAppRegistry) {
        window.VueAppRegistry.register('#bukuPaketApp', appConfig);
    } else {
        document.addEventListener('DOMContentLoaded', () => createApp(appConfig).mount('#bukuPaketApp'));
    }
})();
</script>
