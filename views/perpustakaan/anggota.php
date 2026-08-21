<?php
/**
 * View: Administrasi & Keanggotaan Perpustakaan
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL
 */
?>
<div id="anggotaPerpusApp" v-cloak class="container-fluid px-0">
    <!-- ═══════════════════════════════════════════════════════════════════════
         HERO BANNER & MULTI-TENANT SAAS SWITCHER
         ═══════════════════════════════════════════════════════════════════════ -->
    <?php
    $heroIcon = 'bi-people-fill';
    $heroBadge = 'Modul Keanggotaan & Buku Tamu';
    $heroTitle = 'Administrasi & Keanggotaan Perpustakaan';
    $heroDesc = 'Manajemen kartu anggota, verifikasi surat bebas pustaka, buku tamu pengunjung, serta pengaturan notifikasi.';
    $heroButtons = '
        <a href="' . $this->getBaseUrl() . '/perpustakaan" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 border border-white/20 shadow-2xs transition-all text-decoration-none d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    ';
    include __DIR__ . '/_tenant_filter.php';
    ?>

    <!-- Modern Navtabs Navigation (Gambar 1 Pill Standard) -->
    <div class="card border-0 shadow-2xs rounded-2xl mb-4 bg-white">
        <div class="card-body p-2">
            <div class="nav-pills-container d-flex align-items-center justify-content-between">
                <ul class="nav nav-pills custom-modern-pills flex-nowrap overflow-x-auto text-nowrap gap-1 px-1" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'anggota' }" @click="activeTab = 'anggota'" type="button">
                            <i class="bi bi-people me-1.5 text-primary"></i> 1. Daftar Anggota
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'bebas' }" @click="activeTab = 'bebas'" type="button">
                            <i class="bi bi-file-earmark-check me-1.5 text-success"></i> 2. Bebas Pustaka
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'tamu' }" @click="activeTab = 'tamu'" type="button">
                            <i class="bi bi-person-workspace me-1.5 text-warning"></i> 3. Buku Tamu / Pengunjung
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'pengaturan' }" @click="activeTab = 'pengaturan'" type="button">
                            <i class="bi bi-sliders me-1.5 text-danger"></i> 4. Pengaturan & WA Gateway
                        </button>
                    </li>
                </ul>

                <button @click="refreshCurrentTab" class="btn btn-light btn-sm text-secondary rounded-xl px-3 py-1.5 border border-slate-200/80 shadow-2xs ms-2 flex-shrink-0 d-none d-md-flex align-items-center gap-1.5">
                    <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> <span class="fs-8 fw-semibold">Segarkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: DAFTAR ANGGOTA PERPUSTAKAAN -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'anggota'" class="tab-pane-content transition-all">
        <!-- Single-Line Symmetrical Toolbar -->
        <div class="card border-0 shadow-2xs rounded-2xl mb-4 bg-white">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap flex-lg-nowrap justify-content-between align-items-center gap-3">
                    <div class="position-relative flex-grow-1" style="min-width: 260px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-7"></i>
                        <input type="text" v-model="searchQuery"
                               class="form-control form-control-sm ps-5 rounded-xl border-slate-200 shadow-2xs text-xs font-medium py-2"
                               placeholder="Cari nomor anggota, NISN/NIP, nama, atau kelas...">
                        <button v-if="searchQuery" @click="searchQuery = ''" class="btn btn-link btn-sm position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 p-0 text-decoration-none">
                            <i class="bi bi-x-circle-fill fs-7"></i>
                        </button>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                        <select v-model="filterKategori" class="form-select form-select-sm text-xs font-semibold rounded-xl border-slate-200 shadow-2xs bg-white text-slate-700 py-1.5 px-3 cursor-pointer" style="width: auto; min-width: 140px;">
                            <option value="">Semua Peran</option>
                            <option value="Siswa">Siswa</option>
                            <option value="Guru">Guru / Pendidik</option>
                            <option value="Tendik">Tendik / Staf</option>
                            <option value="Umum">Umum</option>
                        </select>

                        <button @click="syncAnggota" class="btn btn-outline-primary btn-sm rounded-xl px-3 py-1.5 fs-7 font-semibold shadow-2xs d-flex align-items-center gap-1.5" :disabled="syncing">
                            <i class="bi bi-arrow-repeat" :class="{'spin': syncing}"></i> <span>Sinkron Data Siswa</span>
                        </button>

                        <button @click="openModalTambahAnggota" class="btn btn-primary btn-sm rounded-xl px-3.5 py-1.5 fs-7 font-semibold shadow-2xs d-flex align-items-center gap-1.5">
                            <i class="bi bi-plus-lg"></i> <span>Tambah Anggota</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table / Seamless Empty State -->
        <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
            <div v-if="loading" class="p-5 text-center">
                <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                <p class="text-muted fs-7 mb-0">Memuat data keanggotaan perpustakaan...</p>
            </div>

            <div v-else-if="filteredAnggotaList.length === 0" class="p-5 text-center">
                <div class="d-inline-flex p-4 rounded-3xl bg-blue-50 text-blue-600 mb-3 shadow-2xs">
                    <i class="bi bi-people fs-1"></i>
                </div>
                <h5 class="fw-bold text-slate-800 mb-1">Belum Ada Anggota Terdaftar</h5>
                <p class="text-muted fs-7 mx-auto mb-4" style="max-width: 420px;">
                    Data anggota perpustakaan belum tersedia atau tidak cocok dengan filter pencarian Anda.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button v-if="searchQuery || filterKategori" @click="resetFilterAnggota" class="btn btn-outline-secondary btn-sm rounded-xl px-3.5 py-2 fs-7 font-medium shadow-2xs">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                    </button>
                    <button @click="openModalTambahAnggota" class="btn btn-primary btn-sm rounded-xl px-4 py-2 fs-7 font-medium shadow-2xs">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Anggota Baru
                    </button>
                </div>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light bg-slate-50/80 text-slate-600 text-uppercase fs-8 fw-semibold border-bottom border-slate-200/80">
                        <tr>
                            <th class="ps-4 py-3" style="width: 5%;">No</th>
                            <th style="width: 16%;">Nomor Anggota</th>
                            <th style="width: 26%;">Nama & NISN / NIP</th>
                            <th style="width: 14%;">Kelas / Unit</th>
                            <th style="width: 10%;">Tipe</th>
                            <th style="width: 14%;" class="text-center">Pinjaman & Denda</th>
                            <th class="text-center pe-4" style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                        <tr v-for="(item, idx) in paginatedAnggota" :key="item.id || idx" class="hover:bg-slate-50/60 transition-colors">
                            <td class="ps-4 py-3 text-slate-400 font-mono fs-8">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td class="py-3">
                                <span class="badge bg-slate-100 text-slate-800 border border-slate-200 font-mono rounded-lg px-2 py-1 font-semibold">{{ item.no_anggota }}</span>
                            </td>
                            <td class="py-3">
                                <div class="fw-bold text-slate-900 mb-0.5">{{ item.nama_lengkap }}</div>
                                <span class="font-mono fs-8 text-slate-500">{{ item.nisn || item.nip || '-' }}</span>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5 fs-9 fw-semibold">
                                    <i class="bi bi-door-open me-1"></i>{{ item.nama_kelas || '-' }}
                                </span>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-slate-100 text-slate-700 rounded-pill px-2 py-0.5 fs-8">{{ item.tipe_anggota || 'Siswa' }}</span>
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="item.pinjam_aktif > 0" class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2 py-0.5 fs-9 me-1">
                                    {{ item.pinjam_aktif }} Pinjam
                                </span>
                                <span v-else class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2 py-0.5 fs-9">
                                    Bebas
                                </span>
                            </td>
                            <td class="py-3 text-center pe-4">
                                <div class="d-inline-flex align-items-center bg-slate-50 border border-slate-200/70 rounded-xl p-1 shadow-2xs gap-0.5">
                                    <button @click="editAnggota(item)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-primary hover:bg-white transition-all p-1.5" title="Edit Anggota">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a :href="'<?= $this->getBaseUrl() ?>/perpustakaan/cetak-label-thermal?barcode=' + encodeURIComponent(item.no_anggota)" target="_blank" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-dark hover:bg-white transition-all p-1.5" title="Cetak Kartu Anggota">
                                        <i class="bi bi-qr-code"></i>
                                    </a>
                                    <button @click="konfirmasiHapusAnggota(item)" class="btn btn-sm btn-icon rounded-lg text-slate-600 hover:text-danger hover:bg-white transition-all p-1.5" title="Hapus Anggota">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="filteredAnggotaList.length > 0" class="card-footer bg-white border-top border-slate-100 p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="text-muted fs-8 font-medium">
                        Menampilkan <span class="fw-bold text-slate-800">{{ (currentPage - 1) * perPage + 1 }}</span> sampai <span class="fw-bold text-slate-800">{{ Math.min(currentPage * perPage, filteredAnggotaList.length) }}</span> dari <span class="fw-bold text-slate-800">{{ filteredAnggotaList.length }}</span> anggota
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm btn-outline-secondary rounded-lg px-2.5 py-1 text-xs" :disabled="currentPage === 1" @click="currentPage--">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="px-2 text-xs font-semibold text-slate-600">{{ currentPage }} / {{ totalPages || 1 }}</span>
                        <button class="btn btn-sm btn-outline-secondary rounded-lg px-2.5 py-1 text-xs" :disabled="currentPage >= totalPages" @click="currentPage++">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: BEBAS PUSTAKA -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'bebas'" class="tab-pane-content transition-all">
        <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4">
            <h5 class="fw-bold text-slate-800 mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-check text-success"></i> Verifikasi Surat Bebas Pustaka
            </h5>
            <p class="text-muted fs-7 mb-4">Cek status kelulusan sirkulasi untuk syarat kelulusan atau mutasi siswa.</p>

            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="p-4 rounded-3xl bg-slate-50 border border-slate-100">
                        <label class="form-label text-xs fw-bold text-slate-700">Pilih Siswa / Masukkan NISN</label>
                        <input type="text" class="form-control rounded-xl text-xs py-2 border-slate-200 mb-3" placeholder="Ketik NISN atau Nama Siswa...">
                        <button class="btn btn-success btn-sm rounded-xl px-4 py-2 text-xs font-bold shadow-2xs">
                            <i class="bi bi-search me-1"></i> Cek Tanggungan Sirkulasi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: BUKU TAMU / PENGUNJUNG -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'tamu'" class="tab-pane-content transition-all">
        <div class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden">
            <div class="p-3 border-bottom border-slate-100 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-slate-800 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-person-workspace text-warning"></i> Log Presensi Buku Tamu Harian
                    </h5>
                    <small class="text-muted fs-8">Catatan kunjungan harian perpustakaan.</small>
                </div>
            </div>

            <div v-if="visitorLogs.length === 0" class="p-5 text-center">
                <i class="bi bi-person-workspace fs-2 text-warning d-block mb-2"></i>
                <p class="text-muted fs-7 mb-0">Belum ada catatan kunjungan hari ini.</p>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light bg-slate-50/80 text-slate-600 text-uppercase fs-8 fw-semibold">
                        <tr>
                            <th class="ps-4 py-3">No</th>
                            <th>Nama Pengunjung</th>
                            <th>Tipe / Kategori</th>
                            <th>Tujuan Kunjungan</th>
                            <th>Waktu Kunjungan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 fs-7">
                        <tr v-for="(v, idx) in visitorLogs" :key="v.id">
                            <td class="ps-4 py-3 text-slate-400 font-mono fs-8">{{ idx + 1 }}</td>
                            <td class="fw-bold text-slate-900">{{ v.nama_pengunjung }}</td>
                            <td><span class="badge bg-slate-100 text-slate-700 rounded-pill px-2 py-0.5">{{ v.tipe }}</span></td>
                            <td>{{ v.tujuan || 'Membaca' }}</td>
                            <td class="font-mono fs-8 text-muted">{{ v.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: PENGATURAN & WA GATEWAY -->
    <!-- ===================================================================== -->
    <div v-show="activeTab === 'pengaturan'" class="tab-pane-content transition-all">
        <div class="card border-0 shadow-2xs rounded-3xl bg-white p-4" style="max-width: 720px;">
            <h5 class="fw-bold text-slate-800 mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-sliders text-danger"></i> Pengaturan Perpustakaan & Notifikasi WA
            </h5>
            <p class="text-muted fs-7 mb-4">Konfigurasi tarif denda harian, batas peminjaman, serta integrasi WhatsApp Gateway.</p>

            <form @submit.prevent="simpanPengaturan">
                <div class="mb-3">
                    <label class="form-label text-xs fw-bold text-slate-700">Nama Perpustakaan</label>
                    <input type="text" v-model="formPengaturan.nama_perpustakaan" class="form-control rounded-xl text-xs py-2 border-slate-200" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label text-xs fw-bold text-slate-700">Tarif Denda per Hari (Rp)</label>
                        <input type="number" v-model="formPengaturan.tarif_denda_per_hari" class="form-control rounded-xl text-xs py-2 border-slate-200">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-xs fw-bold text-slate-700">Maks Pinjam Siswa (Hari)</label>
                        <input type="number" v-model="formPengaturan.max_hari_pinjam_siswa" class="form-control rounded-xl text-xs py-2 border-slate-200">
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" v-model="formPengaturan.auto_notif_wa_aktif" id="switchWa">
                        <label class="form-check-label text-xs fw-bold text-slate-700" for="switchWa">Aktifkan Notifikasi WhatsApp Otomatis H-1 Pengembalian</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 py-2.5 text-xs font-bold shadow-2xs">
                    <i class="bi bi-save me-1"></i> Simpan Pengaturan
                </button>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL TAMBAH / EDIT ANGGOTA -->
    <!-- ===================================================================== -->
    <div class="modal fade" id="modalAnggotaForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2">
                        <i class="bi bi-person-fill-gear text-primary"></i>
                        <span>{{ formAnggota.id ? 'Edit Data Anggota' : 'Tambah Anggota Baru' }}</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="simpanAnggota">
                    <div class="modal-body p-4 bg-slate-50/50">
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" v-model="formAnggota.nama_lengkap" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Nama Lengkap Siswa/Guru" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-slate-700">NISN / NIP</label>
                            <input type="text" v-model="formAnggota.nisn" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="NISN atau NIP">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Tipe / Peran</label>
                                <select v-model="formAnggota.tipe_anggota" class="form-select rounded-xl text-xs py-2 border-slate-200">
                                    <option value="Siswa">Siswa</option>
                                    <option value="Guru">Guru</option>
                                    <option value="Tendik">Tendik</option>
                                    <option value="Umum">Umum</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs fw-bold text-slate-700">Kelas / Unit</label>
                                <input type="text" v-model="formAnggota.nama_kelas" class="form-control rounded-xl text-xs py-2 border-slate-200" placeholder="Contoh: X RPL 1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top border-slate-100 p-3 px-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-xl px-4 py-2 text-xs font-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 py-2 text-xs font-bold shadow-2xs" :disabled="savingAnggota">
                            <span v-if="savingAnggota" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-save me-1"></i> Simpan Anggota
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Vue 3 In-DOM Instance for Anggota -->
<script>
if (typeof Vue !== 'undefined') {
    const { ref, computed, onMounted } = Vue;

    const anggotaAppConfig = {
        setup() {
            const activeTab = ref('anggota');
            const loading = ref(false);
            const syncing = ref(false);
            const savingAnggota = ref(false);

            const anggotaList = ref([]);
            const visitorLogs = ref([]);
            const searchQuery = ref('');
            const filterKategori = ref('');

            const currentPage = ref(1);
            const perPage = ref(15);

            // Tenant Isolation Helper
            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($data['active_tenant_id'] ?? ($activeTenantId ?? '')) ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const formAnggota = ref({
                id: null,
                nama_lengkap: '',
                nisn: '',
                tipe_anggota: 'Siswa',
                nama_kelas: '',
                tenant_id: currentTenantId
            });

            const formPengaturan = ref({
                nama_perpustakaan: 'Perpustakaan Digital',
                tarif_denda_per_hari: 500,
                max_hari_pinjam_siswa: 7,
                auto_notif_wa_aktif: true,
                tenant_id: currentTenantId
            });

            let modalAnggotaInstance = null;

            const fetchAnggota = async () => {
                loading.value = true;
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/anggota' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        anggotaList.value = res.data.data.list || [];
                    }
                } catch (e) {
                    console.error('Error load anggota:', e);
                } finally {
                    loading.value = false;
                }
            };

            const fetchVisitorLogs = async () => {
                try {
                    const res = await axios.get('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/visitor-logs' + getTenantParam('?'));
                    if (res.data && res.data.success) {
                        visitorLogs.value = res.data.data || [];
                    }
                } catch (e) {}
            };

            const filteredAnggotaList = computed(() => {
                return anggotaList.value.filter(a => {
                    const matchQ = !searchQuery.value ||
                        (a.nama_lengkap && a.nama_lengkap.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                        (a.no_anggota && a.no_anggota.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                        (a.nisn && a.nisn.toLowerCase().includes(searchQuery.value.toLowerCase()));

                    const matchKat = !filterKategori.value || (a.tipe_anggota === filterKategori.value || a.kategori === filterKategori.value);
                    return matchQ && matchKat;
                });
            });

            const totalPages = computed(() => {
                return Math.ceil(filteredAnggotaList.value.length / perPage.value) || 1;
            });

            const paginatedAnggota = computed(() => {
                const start = (currentPage.value - 1) * perPage.value;
                return filteredAnggotaList.value.slice(start, start + perPage.value);
            });

            const resetFilterAnggota = () => {
                searchQuery.value = '';
                filterKategori.value = '';
                currentPage.value = 1;
            };

            const openModalTambahAnggota = () => {
                formAnggota.value = {
                    id: null,
                    nama_lengkap: '',
                    nisn: '',
                    tipe_anggota: 'Siswa',
                    nama_kelas: ''
                };
                if (!modalAnggotaInstance) {
                    const el = document.getElementById('modalAnggotaForm');
                    if (el) modalAnggotaInstance = new bootstrap.Modal(el);
                }
                if (modalAnggotaInstance) modalAnggotaInstance.show();
            };

            const editAnggota = (a) => {
                formAnggota.value = {
                    id: a.id,
                    nama_lengkap: a.nama_lengkap,
                    nisn: a.nisn || a.nip || '',
                    tipe_anggota: a.tipe_anggota || a.kategori || 'Siswa',
                    nama_kelas: a.nama_kelas || ''
                };
                if (!modalAnggotaInstance) {
                    const el = document.getElementById('modalAnggotaForm');
                    if (el) modalAnggotaInstance = new bootstrap.Modal(el);
                }
                if (modalAnggotaInstance) modalAnggotaInstance.show();
            };

            const simpanAnggota = async () => {
                savingAnggota.value = true;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/anggota/simpan', formAnggota.value);
                    if (res.data && res.data.success) {
                        if (modalAnggotaInstance) modalAnggotaInstance.hide();
                        await fetchAnggota();
                    } else {
                        alert(res.data.error || 'Gagal menyimpan anggota.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan koneksi.');
                } finally {
                    savingAnggota.value = false;
                }
            };

            const konfirmasiHapusAnggota = async (a) => {
                if (!confirm(`Hapus anggota "${a.nama_lengkap}"?`)) return;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/anggota/hapus', { id: a.id });
                    if (res.data && res.data.success) {
                        await fetchAnggota();
                    }
                } catch (e) {
                    alert('Gagal menghapus anggota.');
                }
            };

            const syncAnggota = async () => {
                syncing.value = true;
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/anggota/sync');
                    if (res.data && res.data.success) {
                        alert(res.data.message || 'Sinkronisasi anggota berhasil!');
                        await fetchAnggota();
                    }
                } catch (e) {
                    alert('Gagal sinkronisasi data.');
                } finally {
                    syncing.value = false;
                }
            };

            const simpanPengaturan = async () => {
                try {
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/perpustakaan/pengaturan/simpan' + getTenantParam('?'), formPengaturan.value);
                    if (res.data && res.data.success) {
                        alert('Pengaturan perpustakaan berhasil diperbarui!');
                    } else {
                        alert(res.data.message || 'Gagal menyimpan pengaturan perpustakaan.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan saat menyimpan pengaturan perpustakaan.');
                }
            };

            const refreshCurrentTab = () => {
                if (activeTab.value === 'anggota') fetchAnggota();
                if (activeTab.value === 'tamu') fetchVisitorLogs();
            };

            onMounted(() => {
                fetchAnggota();
                fetchVisitorLogs();
            });

            return {
                activeTab,
                loading,
                syncing,
                savingAnggota,
                anggotaList,
                visitorLogs,
                searchQuery,
                filterKategori,
                currentPage,
                perPage,
                totalPages,
                filteredAnggotaList,
                paginatedAnggota,
                formAnggota,
                formPengaturan,
                resetFilterAnggota,
                openModalTambahAnggota,
                editAnggota,
                simpanAnggota,
                konfirmasiHapusAnggota,
                syncAnggota,
                simpanPengaturan,
                refreshCurrentTab
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#anggotaPerpusApp', anggotaAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        const mountApp = () => {
            const el = document.querySelector('#anggotaPerpusApp');
            if (el && !el.__vue_app__) {
                Vue.createApp(anggotaAppConfig).mount('#anggotaPerpusApp');
            }
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mountApp);
        } else {
            mountApp();
        }
        document.addEventListener('turbo:load', mountApp);
    }
}
</script>

<style>
/* Modern Pill Navtabs (Gambar 1 Standard) */
.custom-modern-pills {
    display: flex;
    flex-wrap: nowrap;
    gap: 6px;
    background: transparent;
    padding: 2px;
}
.custom-modern-pills .nav-link {
    border-radius: 12px !important;
    padding: 8px 18px !important;
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    color: #475569 !important;
    background-color: transparent !important;
    border: none !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}
.custom-modern-pills .nav-link:hover:not(.active) {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
}
.custom-modern-pills .nav-link.active {
    background-color: #2563eb !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25) !important;
}
.custom-modern-pills .nav-link.active i {
    color: #ffffff !important;
}
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
[v-cloak] { display: none !important; }
</style>
