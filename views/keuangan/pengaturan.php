
<div id="keuangan-pengaturan-app" v-cloak class="container-fluid px-3 py-3 workspace-container">
    <!-- Header Halaman -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h5 class="fw-bold text-slate-800 mb-0" style="font-size: 1.1rem;">
                <i class="bi bi-gear-fill text-blue-600 me-2"></i> Pengaturan Modul Keuangan
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.72rem;">Sesuaikan terminologi modul keuangan dan visibilitas dashboard sesuai dengan kebijakan regulasi sekolah Anda.</p>
        </div>
    </div>

    <div class="workspace-body flex-grow-1 min-height-0 d-flex justify-content-center">
        <!-- Card Utama -->
        <div class="panel-form" style="max-width: 600px; width: 100%;">
            <div class="panel-header d-flex align-items-center bg-gradient-blue text-white" style="background: linear-gradient(135deg, #1e40af, #3b82f6); border-bottom: 0;">
                <div class="me-2 p-1.5 bg-white bg-opacity-20 rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-sliders text-white" style="font-size: 0.9rem;"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-white" style="font-size: 0.8rem; line-height: 1.2;">Konfigurasi Fleksibel & Regulasi</h6>
                    <p class="mb-0 text-white opacity-85" style="font-size: 0.65rem;">Hindari sanksi regulasi sekolah negeri dengan mengubah istilah SPP secara dinamis.</p>
                </div>
            </div>

            <div class="panel-content form-compact">
                <form @submit.prevent="saveSettings" class="d-flex flex-column h-100 gap-2">
                    <div v-if="successMsg" class="alert alert-success border-0 rounded p-2 d-flex align-items-center mb-2" style="font-size: 0.75rem;">
                        <i class="bi bi-check-circle-fill me-2 fs-6"></i>
                        <div>{{ successMsg }}</div>
                    </div>
                    <div v-if="errorMsg" class="alert alert-danger border-0 rounded p-2 d-flex align-items-center mb-2" style="font-size: 0.75rem;">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                        <div>{{ errorMsg }}</div>
                    </div>

                    <!-- Input 1: Nama Modul -->
                    <div>
                        <label class="form-label">Nama Modul Keuangan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.nama_modul" placeholder="Contoh: Dana Komite, Sumbangan Sukarela, Iuran Partisipasi" required>
                        <div class="form-text mt-0.5" style="font-size: 0.65rem; color: #64748b;">Nama ini akan menggantikan judul menu utama "Keuangan & Pembayaran" di sidebar menu secara global.</div>
                    </div>

                    <div class="row g-2">
                        <!-- Input 2: Istilah Tagihan -->
                        <div class="col-md-6">
                            <label class="form-label">Istilah untuk "Tagihan" <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.istilah_tagihan" placeholder="Contoh: Rincian Dana, Kontribusi" required>
                            <div class="form-text mt-0.5" style="font-size: 0.65rem; color: #64748b;">Akan menggantikan kata "Tagihan" pada lembar kuitansi dan dashboard.</div>
                        </div>

                        <!-- Input 3: Istilah Tunggakan -->
                        <div class="col-md-6">
                            <label class="form-label">Istilah untuk "Tunggakan" <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.istilah_tunggakan" placeholder="Contoh: Kekurangan Partisipasi" required>
                            <div class="form-text mt-0.5" style="font-size: 0.65rem; color: #64748b;">Menggantikan kata "Tunggakan" pada rekapitulasi pelaporan.</div>
                        </div>
                    </div>

                    <!-- Input 4: Toggle Visibilitas Siswa -->
                    <div class="p-2 bg-light rounded mt-2 border">
                        <div class="form-check form-switch d-flex align-items-center justify-content-between p-0">
                            <div>
                                <label class="form-label fw-bold text-slate-800 mb-0.5" style="cursor: pointer; font-size: 0.75rem;" for="switchVisibilitas">
                                    Visibilitas untuk Siswa & Wali Murid
                                </label>
                                <p class="text-muted mb-0" style="font-size: 0.65rem; line-height: 1.3;">Jika dinonaktifkan, siswa & wali murid tidak akan dapat melihat modul ini di menu mereka (Hanya dikelola internal Tata Usaha).</p>
                            </div>
                            <input class="form-check-input fs-5 ms-2 me-0" style="cursor: pointer;" type="checkbox" id="switchVisibilitas" v-model="form.visibilitas_siswa" :true-value="1" :false-value="0">
                        </div>
                    </div>

                    <div class="mt-auto pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-compact w-100" style="height: 38px;" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.workspace-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - var(--header-height) - 1.5rem);
    overflow: hidden;
}
.workspace-body {
    display: flex;
    flex-grow: 1;
    overflow-y: auto;
    padding: 0.5rem 0;
    min-height: 0;
}
.panel-form {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}
.panel-header {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
}
.panel-content {
    padding: 0.75rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
.form-compact .form-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.2rem;
}
.form-compact .form-control,
.form-compact .form-select {
    padding: 0.35rem 0.6rem;
    font-size: 0.8rem;
    border-radius: 6px;
    border-color: #cbd5e1;
    height: 32px;
}
.form-compact .btn-compact {
    padding: 0.35rem 0.75rem;
    font-size: 0.8rem;
    border-radius: 6px;
    font-weight: 600;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.fs-7 { font-size: 0.875rem; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }

/* Responsive Mobile Stack (HP) */
@media (max-width: 767.98px) {
    .workspace-container {
        height: auto !important;
        overflow: visible !important;
    }
    .workspace-body {
        height: auto !important;
        overflow: visible !important;
        padding: 0.25rem 0 !important;
    }
    .panel-form {
        max-width: 100% !important;
        width: 100% !important;
        border-radius: 8px !important;
    }
}
</style>

<script>
window.VueAppRegistry.register('#keuangan-pengaturan-app', {
    setup() {
        const loading = Vue.ref(false);
        const successMsg = Vue.ref('');
        const errorMsg = Vue.ref('');
        const form = Vue.ref({
            nama_modul: 'Keuangan & SPP',
            istilah_tagihan: 'Tagihan',
            istilah_tunggakan: 'Tunggakan',
            visibilitas_siswa: 1
        });

        const fetchSettings = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/pengaturan');
                const res = await response.json();
                if (res.success && res.data) {
                    form.value = res.data;
                }
            } catch (err) {
                console.error('Failed to load settings', err);
            }
        };

        const saveSettings = async () => {
            loading.value = true;
            successMsg.value = '';
            errorMsg.value = '';

            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/save-pengaturan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(form.value)
                });
                const res = await response.json();
                if (res.success) {
                    successMsg.value = 'Pengaturan berhasil diperbarui secara global!';
                    // Reload page/sidebar dynamically after delay to apply name change
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

        Vue.onMounted(() => {
            fetchSettings();
        });

        return {
            loading,
            successMsg,
            errorMsg,
            form,
            saveSettings
        };
    }
});
</script>
