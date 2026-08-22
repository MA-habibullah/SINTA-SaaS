<?php
/**
 * View: Manajemen Master Template Naskah Dinas Sekolah
 * SINTA SaaS Platform — Modern Vue 3 Architecture & Dynamic PostgreSQL Multi-Schema
 */
$activeMenu = 'template';
$pageTitle = 'Template Naskah Dinas Sekolah';
$pageSubtitle = 'Standarisasi format surat dinas resmi, pola penomoran otomatis, dan placeholder variabel cerdas naskah sekolah.';
$pageIcon = 'bi-file-earmark-text-fill';
?>
<div id="persuratanTemplateApp" v-cloak class="container-fluid px-0">
    <!-- Hero Banner Header Mandiri -->
    <?php 
    $heroBadge = 'Master Template Surat';
    $pageTitle = 'Generator & Template Naskah Dinas';
    $pageSubtitle = 'Standarisasi format surat dinas resmi, pola penomoran otomatis, dan placeholder variabel cerdas naskah sekolah.';
    $pageIcon = 'bi-file-earmark-richtext-fill';
    $heroButtons = '
        <button type="button" class="btn btn-sm rounded-xl px-3 py-2 text-xs font-bold text-white bg-white/20 hover:bg-white/30 border border-white/25 shadow-2xs transition-all d-inline-flex align-items-center gap-1.5" onclick="window.persuratanTemplateOpenTambah && window.persuratanTemplateOpenTambah()">
            <i class="bi bi-plus-circle-fill"></i> Tambah Template
        </button>
    ';
    include __DIR__ . '/_hero_header.php'; 
    ?>

    <!-- Navtab Lokal Khusus Internal Halaman Template Surat -->
    <div class="card border-0 shadow-xs rounded-2xl mb-4 bg-white" style="border: 1px solid #e2e8f0;">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <ul class="nav nav-pills gap-1.5 border-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeTab === 'katalog' }" 
                                @click="activeTab = 'katalog'" type="button">
                            <i class="bi bi-grid-fill me-1.5 text-primary"></i> 1. Katalog Template Naskah
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link persuratan-tab-btn d-inline-flex align-items-center" 
                                :class="{ 'active': activeTab === 'placeholder' }" 
                                @click="activeTab = 'placeholder'" type="button">
                            <i class="bi bi-code-square me-1.5 text-indigo-500"></i> 2. Kamus Placeholder Variabel
                        </button>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <button @click="fetchTemplates" class="btn btn-light btn-sm text-secondary rounded-xl px-3 py-1.5 border border-slate-200 shadow-2xs d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> <span class="fs-8 fw-semibold">Segarkan</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalTemplate()">
                        <i class="bi bi-plus-circle-fill"></i> Tambah Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 1: KATALOG TEMPLATE NASKAH -->
    <div v-show="activeTab === 'katalog'" class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden mb-5">
        <!-- Toolbar Filter & Action -->
        <div class="p-3.5 border-b border-slate-100 bg-slate-50/50">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2.5">
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <div class="position-relative" style="min-width: 240px; max-width: 320px;">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-slate-400 fs-7 pointer-events-none"></i>
                        <input type="text" v-model="search" class="form-control form-control-sm ps-5 pe-4 rounded-xl border border-slate-200 text-xs font-medium bg-white shadow-2xs" placeholder="Cari template naskah...">
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-2xs bg-white text-slate-700" @click="fetchTemplates()">
                        <i class="bi bi-arrow-repeat" :class="{'spin': loading}"></i> Segarkan
                    </button>
                    <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-1.5 text-xs d-flex align-items-center gap-1.5 shadow-2xs" @click="openModalTemplate()">
                        <i class="bi bi-plus-circle-fill"></i> Tambah Template
                    </button>
                </div>
            </div>
        </div>

        <!-- Template Cards Grid -->
        <div class="p-4">
            <div v-if="loading" class="text-center py-5 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat template naskah dinas...
            </div>

            <div v-else-if="filteredTemplates.length === 0" class="text-center py-5">
                <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center fs-3 mb-2.5 shadow-2xs">
                    <i class="bi bi-file-earmark-x"></i>
                </div>
                <div class="font-bold text-slate-700 text-base mb-1">Belum Ada Template Naskah</div>
                <p class="text-slate-400 text-xs mb-3 mx-auto" style="max-width: 440px;">
                    Belum ada template naskah surat yang dibuat. Buat template baku untuk mempercepat penerbitan surat dinas.
                </p>
                <button type="button" class="btn btn-sm btn-primary rounded-xl font-bold px-3.5 py-2 text-xs shadow-2xs" @click="openModalTemplate()">
                    <i class="bi bi-plus-lg me-1"></i> Buat Template Pertama
                </button>
            </div>

            <div v-else class="row g-3">
                <div v-for="t in filteredTemplates" :key="t.id" class="col-12 col-md-6 col-xl-4">
                    <div class="card border border-slate-200/80 shadow-2xs rounded-2xl bg-white p-3.5 h-100 d-flex flex-column transition hover:-translate-y-1 hover:shadow-xs">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 d-flex align-items-center justify-content-center fs-5 flex-shrink-0">
                                <i class="bi bi-file-earmark-richtext"></i>
                            </div>
                            <span class="badge bg-slate-100 text-slate-600 font-mono rounded-pill px-2.5 py-1 text-[10px] font-bold">
                                {{ t.kode_klasifikasi || 'KODE-UMUM' }}
                            </span>
                        </div>

                        <h6 class="font-bold text-slate-900 fs-7 mb-1">{{ t.nama_template_surat }}</h6>
                        <div class="text-[11px] text-slate-400 mb-2 line-clamp-1">Perihal: {{ t.perihal_default || '-' }}</div>
                        <div class="text-[11px] text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-100 font-mono mb-3 line-clamp-2" style="font-size: 10px;">
                            {{ t.konten_html ? t.konten_html.substring(0, 90) + '...' : 'Format naskah dinas standar...' }}
                        </div>

                        <div class="mt-auto pt-2.5 border-t border-slate-100 d-flex align-items-center justify-content-between">
                            <span class="text-[10px] text-slate-400">Pola: <code class="font-mono text-blue-600 font-bold">{NO}/{KLAS}/{BLN}/{THN}</code></span>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-xs btn-outline-primary rounded-lg px-2.5 py-1 font-bold" @click="editTemplate(t)">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-xs btn-light border text-rose-600 hover:bg-rose-50 rounded-lg p-1" @click="deleteTemplate(t)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: KAMUS PLACEHOLDER VARIABEL DINAMIS -->
    <div v-show="activeTab === 'placeholder'" class="card border-0 shadow-2xs rounded-2xl bg-white overflow-hidden mb-5">
        <div class="p-3.5 border-b border-slate-100 bg-slate-50/50 d-flex align-items-center justify-content-between">
            <div>
                <h6 class="font-bold text-slate-800 fs-6 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-code-square text-indigo-600"></i>
                    Kamus Tag &amp; Placeholder Variabel Otomatis
                </h6>
                <small class="text-slate-400 text-xs">Variabel cerdas yang akan digantikan secara otomatis oleh sistem saat naskah dinas dicetak</small>
            </div>
            <span class="badge px-3 py-1.5 rounded-pill text-xs font-bold" style="background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;">
                <i class="bi bi-cpu-fill text-indigo-600 me-1"></i> Auto-Replace Engine
            </span>
        </div>

        <div class="p-4">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-3 rounded-2xl border border-slate-200/80 bg-slate-50 h-100">
                        <div class="font-bold text-indigo-900 text-xs mb-2 d-flex align-items-center gap-1.5">
                            <i class="bi bi-buildings-fill text-indigo-600"></i> 1. Identitas Sekolah &amp; Kop
                        </div>
                        <ul class="list-unstyled mb-0 text-xs space-y-1.5">
                            <li class="d-flex align-items-center justify-content-between p-1.5 bg-white rounded-lg border border-slate-100">
                                <code class="text-blue-700 font-bold font-mono">{NAMA_SEKOLAH}</code>
                                <span class="text-slate-500 text-[11px]">Nama Resmi Sekolah</span>
                            </li>
                            <li class="d-flex align-items-center justify-content-between p-1.5 bg-white rounded-lg border border-slate-100">
                                <code class="text-blue-700 font-bold font-mono">{NPSN}</code>
                                <span class="text-slate-500 text-[11px]">Nomor Pokok Sekolah</span>
                            </li>
                            <li class="d-flex align-items-center justify-content-between p-1.5 bg-white rounded-lg border border-slate-100">
                                <code class="text-blue-700 font-bold font-mono">{ALAMAT_SEKOLAH}</code>
                                <span class="text-slate-500 text-[11px]">Alamat &amp; Kontak</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-3 rounded-2xl border border-slate-200/80 bg-slate-50 h-100">
                        <div class="font-bold text-blue-900 text-xs mb-2 d-flex align-items-center gap-1.5">
                            <i class="bi bi-file-earmark-text-fill text-blue-600"></i> 2. Penomoran &amp; Tanggal
                        </div>
                        <ul class="list-unstyled mb-0 text-xs space-y-1.5">
                            <li class="d-flex align-items-center justify-content-between p-1.5 bg-white rounded-lg border border-slate-100">
                                <code class="text-blue-700 font-bold font-mono">{NOMOR_SURAT}</code>
                                <span class="text-slate-500 text-[11px]">Nomor Surat Terbit</span>
                            </li>
                            <li class="d-flex align-items-center justify-content-between p-1.5 bg-white rounded-lg border border-slate-100">
                                <code class="text-blue-700 font-bold font-mono">{TANGGAL_SURAT}</code>
                                <span class="text-slate-500 text-[11px]">Tanggal Format Baku</span>
                            </li>
                            <li class="d-flex align-items-center justify-content-between p-1.5 bg-white rounded-lg border border-slate-100">
                                <code class="text-blue-700 font-bold font-mono">{PERIHAL}</code>
                                <span class="text-slate-500 text-[11px]">Perihal Naskah</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-3 rounded-2xl border border-slate-200/80 bg-slate-50 h-100">
                        <div class="font-bold text-emerald-900 text-xs mb-2 d-flex align-items-center gap-1.5">
                            <i class="bi bi-person-badge-fill text-emerald-600"></i> 3. Siswa &amp; Pejabat
                        </div>
                        <ul class="list-unstyled mb-0 text-xs space-y-1.5">
                            <li class="d-flex align-items-center justify-content-between p-1.5 bg-white rounded-lg border border-slate-100">
                                <code class="text-blue-700 font-bold font-mono">{NAMA_SISWA}</code>
                                <span class="text-slate-500 text-[11px]">Nama Lengkap Siswa</span>
                            </li>
                            <li class="d-flex align-items-center justify-content-between p-1.5 bg-white rounded-lg border border-slate-100">
                                <code class="text-blue-700 font-bold font-mono">{NISN} / {KELAS}</code>
                                <span class="text-slate-500 text-[11px]">NISN &amp; Rombel Kelas</span>
                            </li>
                            <li class="d-flex align-items-center justify-content-between p-1.5 bg-white rounded-lg border border-slate-100">
                                <code class="text-blue-700 font-bold font-mono">{NAMA_KEPSEK}</code>
                                <span class="text-slate-500 text-[11px]">Nama Kepala Sekolah</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         MODAL FORM TEMPLATE NASKAH DINAS
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalFormTemplate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3xl overflow-hidden">
                <div class="modal-header bg-slate-900 text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-indigo-500/20 text-indigo-400 d-flex align-items-center justify-content-center fs-5">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-bold fs-6 mb-0">{{ isEdit ? 'Edit Template Naskah' : 'Tambah Template Naskah Baru' }}</h5>
                            <small class="text-slate-400 text-xs">Konfigurasi format naskah dinas baku dan variabel otomatis</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitTemplate">
                    <div class="modal-body p-4 bg-slate-50/50 text-xs">
                        <div class="row g-3">
                            <div class="col-12 col-md-7">
                                <label class="form-label font-bold text-slate-700">Nama Template Naskah <span class="text-rose-500">*</span></label>
                                <input type="text" v-model="formTemplate.nama_template_surat" class="form-control form-control-sm rounded-xl font-semibold" placeholder="Contoh: Surat Tugas Pembina Ekskul / Surat Panggilan Siswa" required>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label font-bold text-slate-700">Klasifikasi Terkait</label>
                                <select v-model="formTemplate.id_kode_klasifikasi" class="form-select form-select-sm rounded-xl">
                                    <option value="">— Pilih Klasifikasi —</option>
                                    <option v-for="k in klasifikasiList" :key="k.id" :value="k.id">{{ k.kode_klasifikasi }} - {{ k.nama_klasifikasi }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-bold text-slate-700">Perihal Default</label>
                                <input type="text" v-model="formTemplate.perihal_default" class="form-control form-control-sm rounded-xl" placeholder="Contoh: Surat Tugas Mengikuti Kegiatan Lomba...">
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label font-bold text-slate-700 mb-0">Konten Format Naskah Dinas</label>
                                    <span class="text-slate-400 text-[10px]">Placeholder: <code class="text-blue-600">{nama_siswa}</code>, <code class="text-blue-600">{kelas}</code>, <code class="text-blue-600">{tgl_surat}</code></span>
                                </div>
                                <textarea v-model="formTemplate.konten_html" rows="6" class="form-control form-control-sm rounded-xl" placeholder="Ketik format template isi surat..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-t border-slate-100 p-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-xl px-3 font-semibold text-xs" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4 font-bold text-xs shadow-2xs" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span>Simpan Template</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof Vue !== 'undefined') {
    const { ref, computed, onMounted } = Vue;

    const persuratanTemplateAppConfig = {
        setup() {
            const activeTab = ref('katalog');
            const loading = ref(false);
            const saving = ref(false);
            const isEdit = ref(false);
            const templates = ref([]);
            const klasifikasiList = ref([]);
            const search = ref('');

            const formTemplate = ref({
                id: '',
                nama_template_surat: '',
                id_kode_klasifikasi: '',
                perihal_default: '',
                konten_html: ''
            });

            let modalInstance = null;

            const urlParams = new URLSearchParams(window.location.search);
            const currentTenantId = urlParams.get('tenant_id') || '<?= htmlspecialchars($selectedTenantId ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const getTenantParam = (prefix = '?') => {
                return currentTenantId ? `${prefix}tenant_id=${encodeURIComponent(currentTenantId)}` : '';
            };

            const fetchTemplates = async () => {
                loading.value = true;
                try {
                    const [resTpl, resKlas] = await Promise.all([
                        axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/template' + getTenantParam('?')),
                        axios.get('<?= $this->getBaseUrl() ?>/api/v1/persuratan/klasifikasi' + getTenantParam('?'))
                    ]);
                    if (resTpl.data && resTpl.data.success) templates.value = resTpl.data.data || [];
                    if (resKlas.data && resKlas.data.success) klasifikasiList.value = resKlas.data.data || [];
                } catch (e) {
                    console.error('Gagal memuat template:', e);
                } finally {
                    loading.value = false;
                }
            };

            const filteredTemplates = computed(() => {
                const q = search.value.toLowerCase().trim();
                if (!q) return templates.value;
                return templates.value.filter(t => 
                    (t.nama_template_surat && t.nama_template_surat.toLowerCase().includes(q)) ||
                    (t.perihal_default && t.perihal_default.toLowerCase().includes(q))
                );
            });

            const openModalTemplate = () => {
                isEdit.value = false;
                formTemplate.value = {
                    id: '',
                    nama_template_surat: '',
                    id_kode_klasifikasi: '',
                    perihal_default: '',
                    konten_html: ''
                };
                const el = document.getElementById('modalFormTemplate');
                if (el && typeof bootstrap !== 'undefined') {
                    modalInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalInstance.show();
                }
            };

            const editTemplate = (t) => {
                isEdit.value = true;
                formTemplate.value = {
                    id: t.id,
                    nama_template_surat: t.nama_template_surat,
                    id_kode_klasifikasi: t.id_kode_klasifikasi || '',
                    perihal_default: t.perihal_default || '',
                    konten_html: t.konten_html || ''
                };
                const el = document.getElementById('modalFormTemplate');
                if (el && typeof bootstrap !== 'undefined') {
                    modalInstance = bootstrap.Modal.getOrCreateInstance(el);
                    modalInstance.show();
                }
            };

            const submitTemplate = async () => {
                saving.value = true;
                try {
                    const payload = { ...formTemplate.value, tenant_id: currentTenantId };
                    const res = await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/template/save', payload);
                    if (res.data && res.data.success) {
                        if (modalInstance) modalInstance.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Tersimpan!',
                            text: 'Template naskah dinas berhasil disimpan.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        fetchTemplates();
                    }
                } catch (e) {
                    Swal.fire('Gagal', e.response?.data?.error || 'Gagal menyimpan template.', 'error');
                } finally {
                    saving.value = false;
                }
            };

            const deleteTemplate = (t) => {
                Swal.fire({
                    title: 'Hapus Template?',
                    text: `Hapus template: ${t.nama_template_surat}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Ya, Hapus'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            await axios.post('<?= $this->getBaseUrl() ?>/api/v1/persuratan/template/delete', { id: t.id, tenant_id: currentTenantId });
                            Swal.fire('Terhapus', 'Template telah dihapus.', 'success');
                            fetchTemplates();
                        } catch (e) {
                            Swal.fire('Gagal', 'Gagal menghapus template.', 'error');
                        }
                    }
                });
            };

            window.persuratanTemplateOpenTambah = openModalTemplate;

            onMounted(() => {
                fetchTemplates();
            });

            return {
                activeTab,
                loading,
                saving,
                isEdit,
                templates,
                klasifikasiList,
                search,
                formTemplate,
                filteredTemplates,
                fetchTemplates,
                openModalTemplate,
                editTemplate,
                submitTemplate,
                deleteTemplate
            };
        }
    };

    if (window.VueAppRegistry && typeof window.VueAppRegistry.register === 'function') {
        window.VueAppRegistry.register('#persuratanTemplateApp', persuratanTemplateAppConfig);
        if (typeof window.VueAppRegistry.mountAll === 'function') {
            window.VueAppRegistry.mountAll();
        }
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            Vue.createApp(persuratanTemplateAppConfig).mount('#persuratanTemplateApp');
        });
    }
}
</script>
