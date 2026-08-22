<?php
/**
 * View: Pengajuan & Verifikasi Surat Pemanggilan BK (Bimbingan Konseling)
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
$activeMenu = 'pengajuan_bk';
$pageTitle = 'Integrasi Pengajuan Surat BK';
$pageSubtitle = 'Verifikasi dan penerbitan nomor surat dinas panggilan orang tua siswa resmi atas permohonan guru Bimbingan & Konseling.';
$pageIcon = 'bi-person-lines-fill';
?>
<div id="persuratanPengajuanBkApp" v-cloak class="container-fluid px-0">
    <!-- Hero Banner Header Mandiri -->
    <?php 
    $heroBadge = 'Layanan BK & Pemanggilan Siswa';
    $pageTitle = 'Pengajuan Surat Panggilan BK';
    $pageSubtitle = 'Verifikasi dan penerbitan nomor surat dinas pemanggilan orang tua siswa resmi atas permohonan guru Bimbingan & Konseling.';
    $pageIcon = 'bi-bell-fill';
    include __DIR__ . '/_hero_header.php'; 
    ?>

    <!-- Navtab Lokal Khusus Internal Halaman Pengajuan BK -->
    <div class="card border-0 shadow-xs rounded-2xl mb-4 bg-white" style="border: 1px solid #e2e8f0;">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <ul class="nav nav-pills gap-1.5 border-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeTab === 'antrean' }" 
                                @click="activeTab = 'antrean'" type="button">
                            <i class="bi bi-bell-fill me-1.5 text-rose-500"></i> 1. Antrean Permohonan BK
                            <span v-if="countPending > 0" class="badge bg-rose-600 text-white rounded-pill ms-1.5 px-2 py-0.5 text-[10px] font-bold">{{ countPending }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeTab === 'terbit' }" 
                                @click="activeTab = 'terbit'" type="button">
                            <i class="bi bi-check-circle-fill me-1.5 text-emerald-500"></i> 2. Riwayat Surat Panggilan Terbit
                        </button>
                    </li>
                </ul>

                <button @click="fetchPengajuanBk" class="btn btn-light btn-sm text-secondary rounded-xl px-3 py-1.5 border border-slate-200 shadow-2xs d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> <span class="fs-8 fw-semibold">Segarkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TAB 1: ANTREAN PERMOHONAN BK -->
    <div v-show="activeTab === 'antrean'" class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden mb-5">
        <!-- Toolbar Filter & Action -->
        <div class="p-3.5 border-b border-slate-100 bg-slate-50/50">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2.5">
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <!-- Search Input -->
                    <div class="position-relative" style="min-width: 240px; max-width: 320px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-7 pointer-events-none"></i>
                        <input type="text" v-model="filter.search" @input="debounceSearch()"
                               class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-medium bg-white shadow-2xs"
                               placeholder="Cari nama siswa, NISN, perihal...">
                        <button v-if="filter.search" type="button" class="btn btn-xs position-absolute top-50 end-0 translate-middle-y me-2 text-slate-400 p-0" @click="filter.search = ''; fetchPengajuanBk()">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>

                    <!-- Status Filter -->
                    <select v-model="filter.status" @change="fetchPengajuanBk()" class="form-select form-select-sm border border-slate-200 rounded-xl text-xs font-semibold bg-white text-slate-700 shadow-2xs cursor-pointer" style="width: auto;">
                        <option value="">— Semua Status Pengajuan —</option>
                        <option value="Menunggu Penerbitan">Menunggu Penerbitan</option>
                        <option value="Disetujui">Disetujui &amp; Diterbitkan</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>

                    <button v-if="filter.search || filter.status" type="button" class="btn btn-sm btn-outline-secondary rounded-xl text-xs font-bold px-2.5 py-1.5 shadow-2xs bg-white" @click="resetFilter()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-2xs bg-white text-slate-700" @click="fetchPengajuanBk()">
                        <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> Segarkan
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Area -->
        <div class="p-0">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat antrean pengajuan surat BK...
            </div>

            <div v-else-if="listPengajuan.length === 0" class="text-center py-5 px-3">
                <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5 shadow-2xs">
                    <i class="bi bi-bell-slash"></i>
                </div>
                <div class="font-bold text-slate-700 text-base mb-1">Tidak Ada Antrean Pengajuan BK</div>
                <p class="text-slate-400 text-xs mb-0 mx-auto" style="max-width: 440px;">
                    Semua permohonan surat pemanggilan orang tua dari guru Bimbingan Konseling telah diproses dan diterbitkan.
                </p>
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover align-middle text-xs mb-0">
                    <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold">
                        <tr>
                            <th class="ps-4 py-3" style="width: 80px;">No</th>
                            <th class="py-3">Siswa &amp; Kelas</th>
                            <th class="py-3">Jenis &amp; Alasan Pemanggilan</th>
                            <th class="py-3">Guru BK Pengusul</th>
                            <th class="py-3 text-center" style="width: 120px;">Tgl Pengajuan</th>
                            <th class="py-3 text-center" style="width: 140px;">Status</th>
                            <th class="py-3 text-end pe-4" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(p, idx) in listPengajuan" :key="p.id" class="hover:bg-slate-50/70 transition">
                            <td class="ps-4 py-3 font-semibold text-slate-400">
                                {{ idx + 1 }}
                            </td>
                            <td class="py-3">
                                <div class="font-bold text-slate-900 fs-7 mb-0.5">{{ p.nama_lengkap_siswa || 'Siswa' }}</div>
                                <div class="d-flex align-items-center gap-2 text-[11px] text-slate-500">
                                    <span>NISN: {{ p.nisn || '-' }}</span>
                                    <span class="badge bg-slate-100 text-slate-600 rounded px-1.5 py-0.5">Kelas: {{ p.nama_kelas || '-' }}</span>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-rose-50 text-rose-700 border border-rose-200 rounded-md font-bold px-2 py-0.5 mb-1 d-inline-block">
                                    {{ p.jenis_surat || 'Surat Panggilan Orang Tua' }}
                                </span>
                                <div class="text-slate-600 text-xs line-clamp-1" :title="p.alasan_pemanggilan">{{ p.alasan_pemanggilan || '-' }}</div>
                            </td>
                            <td class="py-3">
                                <div class="font-semibold text-slate-800">{{ p.nama_guru_bk || 'Guru BK' }}</div>
                                <small class="text-slate-400 text-[10px]">Guru Pembimbing</small>
                            </td>
                            <td class="py-3 text-center text-slate-600 font-medium">
                                {{ p.created_at ? p.created_at.substring(0, 10) : '-' }}
                            </td>
                            <td class="py-3 text-center">
                                <span v-if="p.status === 'Disetujui'" class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-check2-circle me-1"></i> Terbit Resmi
                                </span>
                                <span v-else-if="p.status === 'Ditolak'" class="badge bg-rose-50 text-rose-700 border border-rose-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-x-circle me-1"></i> Ditolak
                                </span>
                                <span v-else class="badge bg-amber-50 text-amber-700 border border-amber-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-hourglass-split me-1"></i> Menunggu TU
                                </span>
                            </td>
                            <td class="py-3 text-end pe-4">
                                <div v-if="p.status === 'Menunggu Penerbitan' || !p.status" class="d-inline-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-xs btn-primary rounded-lg px-2.5 py-1 font-bold shadow-2xs d-inline-flex align-items-center gap-1" @click="openProsesPenerbitan(p)">
                                        <i class="bi bi-send-plus-fill"></i> Terbitkan
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger rounded-lg p-1" @click="tolakPengajuan(p)" title="Tolak Pengajuan">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div v-else class="text-slate-400 text-[11px] font-medium">
                                    <span v-if="p.nomor_surat_resmi" class="font-mono text-blue-700 font-bold d-block">{{ p.nomor_surat_resmi }}</span>
                                    <span v-else>Selesai Diproses</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: RIWAYAT SURAT PANGGILAN TERBIT -->
    <div v-show="activeTab === 'terbit'" class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden mb-5">
        <div class="p-3.5 border-b border-slate-100 bg-slate-50/50 d-flex align-items-center justify-content-between">
            <div>
                <h6 class="font-bold text-slate-800 fs-6 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-check2-circle text-emerald-600"></i>
                    Riwayat Surat Panggilan Siswa / Orang Tua Terbit
                </h6>
                <small class="text-slate-400 text-xs">Arsip surat panggilan dinas yang telah disahkan dan berstatus terbit resmi</small>
            </div>
            <span class="badge px-3 py-1.5 rounded-pill text-xs font-bold" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                <i class="bi bi-file-earmark-check text-emerald-600 me-1"></i> Terbit Resmi
            </span>
        </div>

        <div class="p-0">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat riwayat surat terbit...
            </div>
            <div v-else-if="listTerbitOnly.length === 0" class="text-center py-5 px-3">
                <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5 shadow-2xs">
                    <i class="bi bi-journal-x"></i>
                </div>
                <div class="font-bold text-slate-700 text-base mb-1">Belum Ada Surat Panggilan Terbit</div>
                <p class="text-slate-400 text-xs mb-0 mx-auto" style="max-width: 440px;">
                    Riwayat surat panggilan yang disetujui akan diarsipkan dan dicatat secara otomatis di sini.
                </p>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle text-xs mb-0">
                    <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold">
                        <tr>
                            <th class="ps-4 py-3" style="width: 80px;">No</th>
                            <th class="py-3">Nomor Surat Terbit</th>
                            <th class="py-3">Siswa &amp; Kelas</th>
                            <th class="py-3">Guru BK Pengusul</th>
                            <th class="py-3 text-center" style="width: 130px;">Tgl Terbit</th>
                            <th class="py-3 text-center" style="width: 130px;">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(p, idx) in listTerbitOnly" :key="'terbit-' + p.id" class="hover:bg-slate-50/70 transition">
                            <td class="ps-4 py-3 text-slate-400 font-mono">{{ idx + 1 }}</td>
                            <td class="py-3 font-bold text-blue-700 font-mono">{{ p.nomor_surat_resmi || 'Nomor Surat Terdaftar' }}</td>
                            <td class="py-3">
                                <div class="font-bold text-slate-900">{{ p.nama_lengkap_siswa }}</div>
                                <small class="text-slate-500">Kelas {{ p.nama_kelas || '-' }} (NISN: {{ p.nisn || '-' }})</small>
                            </td>
                            <td class="py-3 font-medium text-slate-700">{{ p.nama_guru_bk || 'Guru BK' }}</td>
                            <td class="py-3 text-center text-slate-600 font-medium">{{ p.updated_at ? p.updated_at.substring(0, 10) : '-' }}</td>
                            <td class="py-3 text-center">
                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 font-bold text-[10px]">
                                    <i class="bi bi-check-circle-fill me-1"></i> Terbit Resmi
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL PROSES PENERBITAN SURAT PANGGILAN BK
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalProsesPenerbitan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden" v-if="selectedPengajuan">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 d-flex align-items-center justify-content-center fs-5">
                            <i class="bi bi-envelope-check-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0">Penerbitan Surat Panggilan Resmi</h5>
                            <small class="text-slate-400 text-xs">Generate nomor surat keluar dan naskah dinas untuk: {{ selectedPengajuan.nama_lengkap_siswa }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitPenerbitan">
                    <div class="modal-body p-4 bg-slate-50/50 text-xs">
                        <div class="p-3 bg-white rounded-2xl border border-slate-200/80 mb-3 shadow-2xs">
                            <div class="row g-2">
                                <div class="col-4 text-slate-400">Nama Siswa / Kelas:</div>
                                <div class="col-8 font-bold text-slate-800">{{ selectedPengajuan.nama_lengkap_siswa }} (Kelas {{ selectedPengajuan.nama_kelas || '-' }})</div>
                                <div class="col-4 text-slate-400">Alasan Pemanggilan:</div>
                                <div class="col-8 text-slate-700">{{ selectedPengajuan.alasan_pemanggilan }}</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Nomor Surat Keluar Resmi <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formPenerbitan.nomor_surat" class="form-control form-control-sm rounded-xl font-mono font-bold" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-bold text-slate-700">Tanggal Terbit Surat <span class="text-rose-500">*</span></label>
                                <input type="date" v-model="formPenerbitan.tgl_surat" class="form-control form-control-sm rounded-xl" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Tujuan Surat <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formPenerbitan.tujuan" class="form-control form-control-sm rounded-xl" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Perihal Surat <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formPenerbitan.perihal" class="form-control form-control-sm rounded-xl" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Catatan Petugas Tata Usaha</label>
                                <input type="text" v-model="formPenerbitan.catatan_tu" class="form-control form-control-sm rounded-xl" placeholder="Catatan opsional...">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3 font-semibold text-xs" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span>Terbitkan &amp; Register ke Surat Keluar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, onMounted } = Vue;

    const persuratanPengajuanBkAppConfig = {
        setup() {
            const activeTab = ref('antrean');
            const loading = ref(false);
            const saving = ref(false);
            const listPengajuan = ref([]);
            const selectedPengajuan = ref(null);

            const filter = ref({
                search: '',
                status: ''
            });

            const formPenerbitan = ref({
                id_pengajuan: '',
                nomor_surat: '',
                tgl_surat: new Date().toISOString().split('T')[0],
                tujuan: '',
                perihal: '',
                catatan_tu: ''
            });

            let modalProsesInstance = null;

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const fetchPengajuanBk = async () => {
                loading.value = true;
                try {
                    let url = '<?= $this->getBaseUrl() ?>/api/v1/persuratan/pengajuan-bk' + getTenantParam('?');
                    if (filter.value.search) url += `&search=${encodeURIComponent(filter.value.search)}`;
                    if (filter.value.status) url += `&status=${encodeURIComponent(filter.value.status)}`;

                    const res = await axios.get(url);
                    if (res.data && res.data.success) {
                        listPengajuan.value = res.data.data || [];
                    }
                } catch (e) {
                    console.error('Gagal memuat pengajuan BK:', e);
                } finally {
                    loading.value = false;
                }
            };

            let searchTimeout = null;
            const debounceSearch = () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchPengajuanBk();
                }, 350);
            };

            const resetFilter = () => {
                filter.value.search = '';
                filter.value.status = '';
                fetchPengajuanBk();
            };

            const openProsesPenerbitan = async (p) => {
                selectedPengajuan.value = p;
                const randNo = Math.floor(Math.random() * 900) + 100;
                const year = new Date().getFullYear();
                
                formPenerbitan.value = {
                    id_pengajuan: p.id,
                    nomor_surat: `421.7/${randNo}/SMAN1-BK/${year}`,
                    tgl_surat: new Date().toISOString().split('T')[0],
                    tujuan: `Orang Tua / Wali dari ${p.nama_lengkap_siswa}`,
                    perihal: `Surat Panggilan Orang Tua Siswa - ${p.nama_lengkap_siswa}`,
                    catatan_tu: ''
                };

                const el = document.getElementById('modalProsesPenerbitan');
                if (el && typeof bootstrap !== 'undefined') {
                    modalProsesInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalProsesInstance.show();
                }
            };

            const submitPenerbitan = async () => {
                saving.value = true;
                try {
                    const payload = { ...formPenerbitan.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/pengajuan-bk/terbitkan', payload);
                    if (res.data && res.data.success) {
                        if (modalProsesInstance) modalProsesInstance.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Surat Terbit!',
                            text: 'Surat panggilan orang tua siswa telah diterbitkan dan masuk ke register surat keluar.',
                            timer: 1800,
                            showConfirmButton: false
                        });
                        fetchPengajuanBk();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menerbitkan surat.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const tolakPengajuan = (p) => {
                Swal.fire({
                    title: 'Tolak Pengajuan BK?',
                    text: `Tolak permohonan surat pemanggilan untuk siswa: ${p.nama_lengkap_siswa}?`,
                    input: 'text',
                    inputPlaceholder: 'Ketik alasan penolakan...',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Ya, Tolak'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/pengajuan-bk/tolak', {
                                id: p.id,
                                alasan_penolakan: result.value || 'Ditolak oleh bagian Tata Usaha',
                                tenant_id: currentTenantId
                            });
                            Swal.fire('Ditolak', 'Permohonan pengajuan telah ditolak.', 'info');
                            fetchPengajuanBk();
                        } catch (e) {
                            Swal.fire('Gagal', 'Gagal menolak permohonan.', 'error');
                        }
                    }
                });
            };

            const countPending = Vue.computed(() => {
                return listPengajuan.value.filter(p => p.status === 'Menunggu Penerbitan' || !p.status).length;
            });

            const listTerbitOnly = Vue.computed(() => {
                return listPengajuan.value.filter(p => p.status === 'Disetujui');
            });

            onMounted(() => {
                fetchPengajuanBk();
            });

            return {
                activeTab,
                loading,
                saving,
                listPengajuan,
                listTerbitOnly,
                countPending,
                selectedPengajuan,
                filter,
                formPenerbitan,
                fetchPengajuanBk,
                debounceSearch,
                resetFilter,
                openProsesPenerbitan,
                submitPenerbitan,
                tolakPengajuan
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#persuratanPengajuanBkApp', persuratanPengajuanBkAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(persuratanPengajuanBkAppConfig).mount('#persuratanPengajuanBkApp');
        });
    }
}
</script>
