<?php
/**
 * View: Pengaturan Master Kop Surat & Kode Klasifikasi Kearsipan
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
$activeMenu = 'master';
$pageTitle = 'Master Kop Surat & Klasifikasi Kearsipan';
$pageSubtitle = 'Konfigurasi identitas resmi kop naskah dinas sekolah dan daftar kode klasifikasi persuratan dinas.';
$pageIcon = 'bi-gear-wide-connected';
?>
<div id="persuratanMasterApp" v-cloak class="container-fluid px-0">
    <!-- Hero Banner Header Mandiri -->
    <?php 
    $heroBadge = 'Master Identitas & Kearsipan';
    $pageTitle = 'Klasifikasi & Pengaturan Kop Surat';
    $pageSubtitle = 'Konfigurasi identitas resmi kop naskah dinas sekolah dan daftar kode klasifikasi persuratan dinas.';
    $pageIcon = 'bi-gear-wide-connected';
    include __DIR__ . '/_hero_header.php'; 
    ?>

    <!-- Navtab Lokal Khusus Internal Halaman Master Persuratan -->
    <div class="card border-0 shadow-xs rounded-2xl mb-4 bg-white" style="border: 1px solid #e2e8f0;">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <ul class="nav nav-pills gap-1.5 border-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeSubTab === 'kop' }" 
                                @click="activeSubTab = 'kop'" type="button">
                            <i class="bi bi-card-heading me-1.5 text-primary"></i> 1. Pengaturan Kop Surat Resmi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeSubTab === 'klasifikasi' }" 
                                @click="activeSubTab = 'klasifikasi'" type="button">
                            <i class="bi bi-tags-fill me-1.5 text-emerald-500"></i> 2. Master Kode Klasifikasi Surat
                        </button>
                    </li>
                </ul>

                <button v-if="activeSubTab === 'klasifikasi'" type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalKlasifikasi()">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Kode Klasifikasi
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         SUB TAB 1: PENGATURAN KOP SURAT RESMI SEKOLAH
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeSubTab === 'kop'" class="row g-4 mb-5">
        <!-- Form Pengaturan Kop -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4">
                <h6 class="font-bold text-slate-800 fs-6 mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-fill text-blue-600"></i> Identitas Kop Surat Sekolah
                </h6>
                <form @submit.prevent="saveKopSurat" class="text-xs">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font-bold text-slate-700">Nama Instansi Atasan (Header 1)</label>
                            <input type="text" v-model="kop.nama_instansi_atas" class="form-control form-control-sm rounded-xl" placeholder="Contoh: PEMERINTAH PROVINSI JAWA TIMUR / DINAS PENDIDIKAN">
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold text-slate-700">Nama Sekolah Resmi (Header 2) <span class="text-rose-500">*</span></label>
                            <input type="text" v-model="kop.nama_sekolah" class="form-control form-control-sm rounded-xl font-bold" placeholder="Contoh: SMA NEGERI 1 SINTA TERPADU" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-bold text-slate-700">Alamat Lengkap Sekolah</label>
                            <input type="text" v-model="kop.alamat" class="form-control form-control-sm rounded-xl" placeholder="Jl. Pendidikan No. 123">
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="form-label font-bold text-slate-700">Kota / Kabupaten</label>
                            <input type="text" v-model="kop.kota_kabupaten" class="form-control form-control-sm rounded-xl" placeholder="Surabaya">
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="form-label font-bold text-slate-700">Kode Pos</label>
                            <input type="text" v-model="kop.kode_pos" class="form-control form-control-sm rounded-xl" placeholder="60111">
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="form-label font-bold text-slate-700">Nomor Telepon / Fax</label>
                            <input type="text" v-model="kop.telepon" class="form-control form-control-sm rounded-xl" placeholder="(031) 555-1234">
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="form-label font-bold text-slate-700">Alamat Email Resmi</label>
                            <input type="email" v-model="kop.email" class="form-control form-control-sm rounded-xl" placeholder="info@sekolah.sch.id">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-700">URL Logo Kiri (Pemda / Yayasan)</label>
                            <input type="text" v-model="kop.logo_kiri" class="form-control form-control-sm rounded-xl" placeholder="https://... / logo-pemda.png">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-bold text-slate-700">URL Logo Kanan (Sekolah)</label>
                            <input type="text" v-model="kop.logo_kanan" class="form-control form-control-sm rounded-xl" placeholder="https://... / logo-sekolah.png">
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span>Simpan Perubahan Kop Surat</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Live Preview Kop -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-2xs rounded-2xl bg-white p-4 h-100">
                <h6 class="font-bold text-slate-800 fs-6 mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-eye-fill text-emerald-600"></i> Pratinjau Real-time Kop Surat
                </h6>
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                    <div class="bg-white p-3 p-md-4 rounded-xl shadow-xs" style="font-family: 'Times New Roman', Times, serif; color: #000000;">
                        <div class="d-flex align-items-center justify-content-between gap-3 pb-3 mb-3" style="border-bottom: 3px double #000000;">
                            <img v-if="kop.logo_kiri" :src="kop.logo_kiri" style="width: 60px; height: 60px; object-fit: contain;">
                            <div v-else style="width: 60px; height: 60px;" class="border border-dark d-flex align-items-center justify-content-center text-muted fs-8 font-sans">LOGO 1</div>

                            <div class="text-center flex-grow-1 font-sans">
                                <h6 class="fw-bold text-uppercase mb-0 fs-8 tracking-wide">{{ kop.nama_instansi_atas || 'PEMERINTAH PROVINSI / KABUPATEN' }}</h6>
                                <h5 class="fw-black text-uppercase mb-0 fs-6">{{ kop.nama_sekolah || 'NAMA SEKOLAH TERPADU' }}</h5>
                                <p class="mb-0 text-muted" style="font-size: 8pt; font-family: sans-serif;">
                                    {{ kop.alamat || 'Alamat Sekolah Terpadu' }} | Telp: {{ kop.telepon || '-' }} | Email: {{ kop.email || '-' }}
                                </p>
                            </div>

                            <img v-if="kop.logo_kanan" :src="kop.logo_kanan" style="width: 60px; height: 60px; object-fit: contain;">
                            <div v-else style="width: 60px; height: 60px;"></div>
                        </div>
                        <div class="text-center py-4 text-slate-400 font-sans text-xs">
                            — Area Naskah Isi Surat Dinas —
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         SUB TAB 2: MASTER KODE KLASIFIKASI SURAT DINAS
         ═══════════════════════════════════════════════════════════════════════ -->
    <div v-show="activeSubTab === 'klasifikasi'" class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden mb-5">
        <div class="p-3.5 border-b border-slate-100 bg-slate-50/50 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h6 class="font-bold text-slate-800 fs-6 mb-0">Kode Klasifikasi Kearsipan Baku</h6>
                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 font-bold px-2 py-0.5 rounded-pill text-[10px]">
                    {{ klasifikasiList.length }} Klasifikasi
                </span>
            </div>
            <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3 py-1.5 text-xs shadow-2xs d-flex align-items-center gap-1.5" @click="openModalKlasifikasi()">
                <i class="bi bi-plus-circle-fill"></i> Tambah Kode
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-xs mb-0">
                <thead class="bg-slate-50 text-slate-500 font-bold">
                    <tr>
                        <th class="ps-4 py-3" style="width: 140px;">Kode Arsip</th>
                        <th class="py-3">Nama Klasifikasi Naskah</th>
                        <th class="py-3">Keterangan / Fungsi</th>
                        <th class="py-3 text-end pe-4" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="k in klasifikasiList" :key="k.id">
                        <td class="ps-4 py-3 font-mono font-bold text-blue-700">{{ k.kode_klasifikasi }}</td>
                        <td class="py-3 font-bold text-slate-800">{{ k.nama_klasifikasi }}</td>
                        <td class="py-3 text-slate-500">{{ k.keterangan || '-' }}</td>
                        <td class="py-3 text-end pe-4">
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button" class="btn btn-xs btn-outline-primary rounded-lg px-2 py-1 font-bold" @click="editKlasifikasi(k)">Edit</button>
                                <button type="button" class="btn btn-xs btn-light border text-rose-600 hover:bg-rose-50 rounded-lg p-1" @click="deleteKlasifikasi(k)"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Klasifikasi -->
    <div class="modal fade" id="modalFormKlasifikasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <h5 class="modal-title font-bold fs-6 mb-0">{{ isEditKlas ? 'Edit Kode Klasifikasi' : 'Tambah Kode Klasifikasi Baru' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitKlasifikasi">
                    <div class="modal-body p-4 bg-slate-50/50 text-xs">
                        <div class="row g-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label font-bold text-slate-700">Kode Klasifikasi <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formKlasifikasi.kode_klasifikasi" class="form-control form-control-sm rounded-xl font-mono font-bold" placeholder="421.3" required>
                            </div>
                            <div class="col-12 col-md-7">
                                <label class="form-label font-bold text-slate-700">Nama Klasifikasi <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formKlasifikasi.nama_klasifikasi" class="form-control form-control-sm rounded-xl" placeholder="Kesiswaan / Kurikulum" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Keterangan / Fungsi Arsip</label>
                                <input type="text" v-model="formKlasifikasi.keterangan" class="form-control form-control-sm rounded-xl" placeholder="Keterangan klasifikasi...">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3 font-semibold text-xs" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span>Simpan</span>
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

    const persuratanMasterAppConfig = {
        setup() {
            const activeSubTab = ref('kop');
            const loading = ref(false);
            const saving = ref(false);
            const isEditKlas = ref(false);
            const klasifikasiList = ref([]);

            const kop = ref({
                id: '',
                nama_instansi_atas: '',
                nama_sekolah: '',
                alamat: '',
                kota_kabupaten: '',
                kode_pos: '',
                telepon: '',
                email: '',
                logo_kiri: '',
                logo_kanan: ''
            });

            const formKlasifikasi = ref({
                id: '',
                kode_klasifikasi: '',
                nama_klasifikasi: '',
                keterangan: ''
            });

            let modalKlasInstance = null;

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const fetchMaster = async () => {
                loading.value = true;
                try {
                    const [resKop, resKlas] = await Promise.all([
                        axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/kop-surat' + getTenantParam('?')),
                        axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi' + getTenantParam('?'))
                    ]);
                    if (resKop.data && resKop.data.success && resKop.data.data) kop.value = resKop.data.data;
                    if (resKlas.data && resKlas.data.success) klasifikasiList.value = resKlas.data.data || [];
                } catch (e) {
                    console.error('Gagal memuat data master persuratan:', e);
                } finally {
                    loading.value = false;
                }
            };

            const saveKopSurat = async () => {
                saving.value = true;
                try {
                    const payload = { ...kop.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/kop-surat/save', payload);
                    if (res.data && res.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Kop Surat Tersimpan!',
                            text: 'Format identitas resmi kop surat berhasil diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        fetchMaster();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menyimpan kop surat.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const openModalKlasifikasi = () => {
                isEditKlas.value = false;
                formKlasifikasi.value = { id: '', kode_klasifikasi: '', nama_klasifikasi: '', keterangan: '' };
                const el = document.getElementById('modalFormKlasifikasi');
                if (el && typeof bootstrap !== 'undefined') {
                    modalKlasInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalKlasInstance.show();
                }
            };

            const editKlasifikasi = (k) => {
                isEditKlas.value = true;
                formKlasifikasi.value = { id: k.id, kode_klasifikasi: k.kode_klasifikasi, nama_klasifikasi: k.nama_klasifikasi, keterangan: k.keterangan || '' };
                const el = document.getElementById('modalFormKlasifikasi');
                if (el && typeof bootstrap !== 'undefined') {
                    modalKlasInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalKlasInstance.show();
                }
            };

            const submitKlasifikasi = async () => {
                saving.value = true;
                try {
                    const payload = { ...formKlasifikasi.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi/save', payload);
                    if (res.data && res.data.success) {
                        if (modalKlasInstance) modalKlasInstance.hide();
                        Swal.fire('Tersimpan', 'Kode klasifikasi berhasil disimpan.', 'success');
                        fetchMaster();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menyimpan kode klasifikasi.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const deleteKlasifikasi = (k) => {
                Swal.fire({
                    title: 'Hapus Kode Klasifikasi?',
                    text: `Hapus kode: ${k.kode_klasifikasi} - ${k.nama_klasifikasi}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Ya, Hapus'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi/delete', { id: k.id, tenant_id: currentTenantId });
                            Swal.fire('Terhapus', 'Kode klasifikasi telah dihapus.', 'success');
                            fetchMaster();
                        } catch (e) {
                            Swal.fire('Gagal', 'Gagal menghapus kode klasifikasi.', 'error');
                        }
                    }
                });
            };

            onMounted(() => {
                fetchMaster();
            });

            return {
                activeSubTab,
                loading,
                saving,
                isEditKlas,
                kop,
                klasifikasiList,
                formKlasifikasi,
                saveKopSurat,
                openModalKlasifikasi,
                editKlasifikasi,
                submitKlasifikasi,
                deleteKlasifikasi
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#persuratanMasterApp', persuratanMasterAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(persuratanMasterAppConfig).mount('#persuratanMasterApp');
        });
    }
}
</script>
