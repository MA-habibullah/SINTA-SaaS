<?php
/**
 * View: Event Khusus & Peminjaman OSN / Olimpiade
 * Zero Data Leakage: Data dimuat async via Axios — tidak ada data mentah di View Source.
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">🏆 Event Khusus &amp; Peminjaman Buku OSN / Kontingen</h2>
        <p class="text-muted fs-7 mb-0">Fasilitas Peminjaman Buku Referensi Tambahan Khusus Siswa Peserta Olimpiade / Lomba.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="<?= $this->getBaseUrl() ?>/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button type="button" class="btn btn-warning btn-sm rounded-3 px-3 py-2 fs-7 text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#modalEventOSN">
            <i class="bi bi-trophy me-1"></i> Tambah Event OSN / Lomba
        </button>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<!-- Vue 3 Async App (Zero Data Leakage) -->
<div id="eventOSNApp" v-cloak>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <!-- Loading Skeleton -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-warning" role="status"></div>
            <p class="text-muted mt-2 mb-0">Memuat data event OSN secara asinkron...</p>
        </div>

        <!-- Data Table -->
        <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Sekolah / Tenant</th>
                        <th>Nama Event / Lomba</th>
                        <th>Bidang Studi / Cabang</th>
                        <th>Siswa Peserta (Kontingen)</th>
                        <th>Buku Referensi Diberikan</th>
                        <th>Batas Pengembalian</th>
                        <th>Status Event</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="eventList.length === 0">
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-trophy fs-3 d-block mb-2 text-warning"></i> Belum ada event khusus/OSN terdaftar. Klik <strong>Tambah Event OSN / Lomba</strong> untuk mendaftarkan kontingen.
                        </td>
                    </tr>
                    <tr v-for="(ev, idx) in eventList" :key="ev.id">
                        <td>{{ idx + 1 }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-building me-1 text-primary"></i>{{ ev.tenant_name || 'Sekolah Aktif' }}
                            </span>
                        </td>
                        <td><strong>{{ ev.nama_event }}</strong></td>
                        <td><span class="badge bg-warning-subtle text-dark">{{ ev.bidang || '-' }}</span></td>
                        <td>{{ ev.nama_siswa || '-' }}</td>
                        <td>{{ ev.judul_buku || '-' }}</td>
                        <td>{{ ev.tanggal_kembali_rencana || '-' }}</td>
                        <td><span class="badge bg-success">Aktif / Berjalan</span></td>
                        <td class="text-center">
                            <a href="<?= $this->getBaseUrl() ?>/perpustakaan/cetak-laporan-peminjaman" class="btn btn-outline-primary btn-sm rounded-2">
                                <i class="bi bi-file-earmark-text me-1"></i> Cetak Berita Acara
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Event OSN -->
<div class="modal fade" id="modalEventOSN" tabindex="-1" aria-labelledby="modalEventOSNLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalEventOSNLabel"><i class="bi bi-trophy me-2"></i> Pendaftaran Event OSN / Kontingen Lomba</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= htmlspecialchars($this->getBaseUrl(), ENT_QUOTES, 'UTF-8') ?>/perpustakaan/event" method="POST" data-turbo="false">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nama Event / Olimpiade <span class="text-danger">*</span></label>
                            <input type="text" name="nama_event" class="form-control rounded-3" placeholder="Contoh: OSN Fisika Tingkat Provinsi" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Bidang Studi / Subjek</label>
                            <input type="text" name="bidang" class="form-control rounded-3" placeholder="Contoh: Fisika / Matematika / Biologi">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-3 px-4 fw-semibold"><i class="bi bi-save me-1"></i> Simpan Event</button>
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
            const eventList = ref([]);
            const loading = ref(true);

            const fetchData = async () => {
                loading.value = true;
                try {
                    const res = await axios.get(`${baseUrl}/api/v1/perpustakaan/event-osn`);
                    if (res.data && res.data.success) {
                        eventList.value = res.data.data || [];
                    }
                } catch (err) {
                    console.error('Gagal memuat data event OSN:', err);
                } finally {
                    loading.value = false;
                }
            };

            onMounted(fetchData);

            return { eventList, loading };
        }
    };

    if (window.VueAppRegistry) {
        window.VueAppRegistry.register('#eventOSNApp', appConfig);
    } else {
        document.addEventListener('DOMContentLoaded', () => createApp(appConfig).mount('#eventOSNApp'));
    }
})();
</script>
