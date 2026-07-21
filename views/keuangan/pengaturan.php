
<div id="keuangan-pengaturan-app" v-cloak class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <!-- Header Halaman -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="fw-bold text-slate-800 mb-1">
                        <i class="bi bi-gear-fill text-blue-600 me-2"></i> Pengaturan Modul Keuangan
                    </h2>
                    <p class="text-muted mb-0">Sesuaikan terminologi modul keuangan dan visibilitas dashboard sesuai dengan kebijakan regulasi sekolah Anda.</p>
                </div>
            </div>

            <!-- Tenant Selector Card (Super Admin Only) -->
            <div v-if="isSuperAdmin" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-slate-700"><i class="bi bi-building-gear text-blue-600 me-2"></i> Pilih Sekolah (Tenant)</label>
                        <select class="form-select border-slate-200" v-model="selectedTenantId" @change="onTenantChange" style="height: 44px;">
                            <option v-for="t in tenantsList" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mt-3 mt-md-0 text-md-end text-muted fs-7">
                        Menyesuaikan terminologi keuangan spesifik untuk sekolah terpilih.
                    </div>
                </div>
            </div>

            <!-- Card Utama -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 bg-gradient-blue text-white d-flex align-items-center" style="background: linear-gradient(135deg, #1e40af, #3b82f6);">
                    <div class="me-3 p-3 bg-white bg-opacity-20 rounded-3">
                        <i class="bi bi-sliders fs-3"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-white">Konfigurasi Fleksibel & Regulasi</h5>
                        <p class="mb-0 fs-7 opacity-85 text-white">Hindari sanksi regulasi sekolah negeri dengan mengubah istilah SPP secara dinamis.</p>
                    </div>
                </div>

                <form @submit.prevent="saveSettings" class="card-body p-4 bg-white">
                    <div v-if="successMsg" class="alert alert-success border-0 rounded-3 d-flex align-items-center mb-4">
                        <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
                        <div class="text-success-800 fw-semibold">{{ successMsg }}</div>
                    </div>
                    <div v-if="errorMsg" class="alert alert-danger border-0 rounded-3 d-flex align-items-center mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
                        <div class="text-danger-800 fw-semibold">{{ errorMsg }}</div>
                    </div>

                    <!-- Input 1: Nama Modul -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-slate-700">Nama Modul Keuangan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg border-slate-200" v-model="form.nama_modul" placeholder="Contoh: Dana Komite, Sumbangan Sukarela, Iuran Partisipasi" required style="height: 48px;">
                        <div class="form-text text-muted">Nama ini akan menggantikan judul menu utama "Keuangan & Pembayaran" di sidebar menu secara global.</div>
                    </div>

                    <div class="row mb-4">
                        <!-- Input 2: Istilah Tagihan -->
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-slate-700">Istilah untuk "Tagihan" <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-slate-200" v-model="form.istilah_tagihan" placeholder="Contoh: Rincian Dana, Kontribusi" required style="height: 42px;">
                            <div class="form-text text-muted">Akan menggantikan kata "Tagihan" pada lembar kuitansi and dashboard.</div>
                        </div>

                        <!-- Input 3: Istilah Tunggakan -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-slate-700">Istilah untuk "Tunggakan" <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-slate-200" v-model="form.istilah_tunggakan" placeholder="Contoh: Kekurangan Partisipasi" required style="height: 42px;">
                            <div class="form-text text-muted">Menggantikan kata "Tunggakan" pada rekapitulasi pelaporan.</div>
                        </div>
                    </div>

                    <!-- Input 4: Toggle Visibilitas Siswa -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <div class="form-check form-switch d-flex align-items-center justify-content-between p-0">
                            <div>
                                <label class="form-label fw-bold text-slate-800 mb-1" style="cursor: pointer;" for="switchVisibilitas">
                                    Visibilitas untuk Siswa & Wali Murid
                                </label>
                                <p class="text-muted mb-0 fs-7">Jika dinonaktifkan, siswa & wali murid tidak akan dapat melihat modul ini di menu mereka (Hanya dikelola internal Tata Usaha).</p>
                            </div>
                            <input class="form-check-input fs-3 ms-2 me-0" type="checkbox" id="switchVisibilitas" v-model="form.visibilitas_siswa" :true-value="1" :false-value="0">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold px-4 py-2" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data Injection -->
<script id="user-session" type="application/json">
    <?php echo json_encode([
        'is_super_admin' => (($_SESSION['role_name'] ?? '') === 'super_admin'),
        'tenant_id' => ($_SESSION['tenant_id'] ?? '')
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<style>
.fs-7 { font-size: 0.875rem; }
.fs-8 { font-size: 0.75rem; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }
</style>

<script>
window.VueAppRegistry.register('#keuangan-pengaturan-app', {
    setup() {
        const session = JSON.parse(document.getElementById('user-session').textContent || '{}');
        const isSuperAdmin = session.is_super_admin;
        const tenantsList = Vue.ref([]);
        const selectedTenantId = Vue.ref(session.tenant_id || '');

        const loading = Vue.ref(false);
        const successMsg = Vue.ref('');
        const errorMsg = Vue.ref('');
        const form = Vue.ref({
            nama_modul: 'Keuangan & SPP',
            istilah_tagihan: 'Tagihan',
            istilah_tunggakan: 'Tunggakan',
            visibilitas_siswa: 1
        });

        // Helper query param
        const getQueryParam = () => {
            return isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
        };

        const fetchTenants = async () => {
            if (!isSuperAdmin) return;
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/tenants');
                const res = await response.json();
                if (res.success) {
                    tenantsList.value = res.data;
                    const cached = localStorage.getItem('sinta_spp_selected_tenant_id');
                    if (cached && tenantsList.value.some(t => t.id === cached)) {
                        selectedTenantId.value = cached;
                    } else if (tenantsList.value.length > 0) {
                        selectedTenantId.value = tenantsList.value[0].id;
                        localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
                    }
                }
            } catch (err) {
                console.error(err);
            }
        };

        const fetchSettings = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/pengaturan' + getQueryParam());
                const res = await response.json();
                if (res.success && res.data) {
                    form.value = res.data;
                }
            } catch (err) {
                console.error('Failed to load settings', err);
            }
        };

        const onTenantChange = () => {
            localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
            fetchSettings();
        };

        const saveSettings = async () => {
            loading.value = true;
            successMsg.value = '';
            errorMsg.value = '';

            try {
                const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `?tenant_id=${selectedTenantId.value}` : '';
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/save-pengaturan' + tenantSuffix, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(form.value)
                });
                const res = await response.json();
                if (res.success) {
                    successMsg.value = 'Pengaturan berhasil diperbarui!';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    errorMsg.value = res.error || 'Gagal menyimpan pengaturan.';
                }
            } catch (err) {
                errorMsg.value = 'Terjadi kesalahan jaringan.';
            } finally {
                loading.value = false;
            }
        };

        Vue.onMounted(async () => {
            if (isSuperAdmin) {
                await fetchTenants();
            }
            await fetchSettings();
        });

        return {
            isSuperAdmin,
            tenantsList,
            selectedTenantId,
            loading,
            successMsg,
            errorMsg,
            form,
            onTenantChange,
            saveSettings
        };
    }
});
</script>
