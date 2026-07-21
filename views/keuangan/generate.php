
<div id="keuangan-generate-app" v-cloak class="container-fluid px-3 py-3 workspace-container">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h5 class="fw-bold text-slate-800 mb-0" style="font-size: 1.1rem;">
                <i class="bi bi-file-earmark-plus-fill text-blue-600 me-2"></i> Generate Tagihan Massal
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.72rem;">Terbitkan tagihan sumbangan, iuran buku, atau SPP ke banyak siswa sekaligus secara aman.</p>
        </div>
    </div>

    <div class="workspace-body flex-grow-1 min-height-0 d-flex justify-content-center">
        <!-- Card Form Generator -->
        <div class="panel-form" style="max-width: 600px; width: 100%;">
            <div class="panel-header">
                <span class="fw-bold text-slate-800" style="font-size: 0.82rem;">Parameter Tagihan Massal</span>
            </div>
            <div class="panel-content form-compact">
                <!-- Informational Alert -->
                <div class="p-2 bg-light rounded text-muted mb-3" style="font-size: 0.7rem; line-height: 1.3;">
                    <i class="bi bi-info-circle-fill text-blue-500 me-2"></i>
                    Sistem otomatis menghitung potongan beasiswa individual siswa dan mencegah pembuatan tagihan duplikat untuk periode yang sama.
                </div>

                <div v-if="successMsg" class="alert alert-success border-0 rounded p-2 d-flex align-items-center mb-3" style="font-size: 0.75rem;">
                    <i class="bi bi-check-circle-fill me-2 fs-6"></i>
                    <div>{{ successMsg }}</div>
                </div>
                <div v-if="errorMsg" class="alert alert-danger border-0 rounded p-2 d-flex align-items-center mb-3" style="font-size: 0.75rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                    <div>{{ errorMsg }}</div>
                </div>

                <form @submit.prevent="generateTagihan" class="d-flex flex-column h-100 gap-2">
                    <!-- Komponen Biaya -->
                    <div>
                        <label class="form-label">Komponen Biaya <span class="text-danger">*</span></label>
                        <select class="form-select" v-model="form.komponen_id" @change="onKomponenChange" required>
                            <option value="" disabled>-- Pilih Komponen --</option>
                            <option v-for="k in komponenList" :value="k.id">{{ k.nama_komponen }} ({{ k.tipe_periode }})</option>
                        </select>
                    </div>

                    <div class="row g-2">
                        <!-- Tahun Ajaran -->
                        <div class="col-md-6">
                            <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.tahun_ajaran_id" required>
                                <option value="" disabled>-- Pilih Tahun Ajaran --</option>
                                <option v-for="ta in listTa" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                            </select>
                        </div>

                        <!-- Bulan (Hanya jika periode = Bulanan) -->
                        <div class="col-md-6" v-if="isBulanan">
                            <label class="form-label">Pilih Bulan Tagihan <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.bulan" required>
                                <option value="" disabled>-- Pilih Bulan --</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                            </select>
                        </div>
                    </div>

                    <!-- Target Filter -->
                    <div class="border p-2 rounded bg-light-card mt-2">
                        <label class="form-label mb-1">Target Distribusi</label>
                        <div class="d-flex gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="targetDist" id="targetAll" value="all" v-model="targetType" @change="resetTargets">
                                <label class="form-check-label text-slate-700" style="font-size: 0.72rem;" for="targetAll">Semua Kelas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="targetDist" id="targetKelas" value="kelas" v-model="targetType" @change="resetTargets">
                                <label class="form-check-label text-slate-700" style="font-size: 0.72rem;" for="targetKelas">Per Kelas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="targetDist" id="targetJenjang" value="jenjang" v-model="targetType" @change="resetTargets">
                                <label class="form-check-label text-slate-700" style="font-size: 0.72rem;" for="targetJenjang">Per Jenjang</label>
                            </div>
                        </div>

                        <!-- Dropdown kelas/jenjang -->
                        <div v-if="targetType === 'kelas'">
                            <label class="form-label">Pilih Kelas Sasaran <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.kelas_id" required>
                                <option value="" disabled>-- Pilih Kelas --</option>
                                <option v-for="c in listKelas" :value="c.id">{{ c.nama_kelas }}</option>
                            </select>
                        </div>

                        <div v-if="targetType === 'jenjang'">
                            <label class="form-label">Pilih Jenjang Sasaran <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.jenjang_id" required>
                                <option value="" disabled>-- Pilih Jenjang --</option>
                                <option v-for="j in listJenjang" :value="j.id">{{ j.nama_jenjang }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-auto pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-compact w-100" style="height: 38px;" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Terbitkan Tagihan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data Injections -->
<script id="data-kelas" type="application/json">
    <?php echo json_encode($list_kelas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-jenjang" type="application/json">
    <?php echo json_encode($list_jenjang, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-ta" type="application/json">
    <?php echo json_encode($list_ta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-komponen" type="application/json">
    <?php echo json_encode($list_komponen, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

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
.bg-light-card {
    background-color: #f8fafc;
    border-color: #e2e8f0 !important;
}
.fs-7 { font-size: 0.85rem; }
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
window.VueAppRegistry.register('#keuangan-generate-app', {
    setup() {
        const listKelas = JSON.parse(document.getElementById('data-kelas').textContent || '[]');
        const listJenjang = JSON.parse(document.getElementById('data-jenjang').textContent || '[]');
        const listTa = JSON.parse(document.getElementById('data-ta').textContent || '[]');
        const komponenList = JSON.parse(document.getElementById('data-komponen').textContent || '[]');

        const loading = Vue.ref(false);
        const successMsg = Vue.ref('');
        const errorMsg = Vue.ref('');

        const isBulanan = Vue.ref(false);
        const targetType = Vue.ref('all');

        const form = Vue.ref({
            komponen_id: '',
            tahun_ajaran_id: '',
            bulan: '',
            kelas_id: '',
            jenjang_id: ''
        });

        const onKomponenChange = () => {
            const selected = komponenList.find(k => k.id == form.value.komponen_id);
            if (selected && selected.tipe_periode === 'Bulanan') {
                isBulanan.value = true;
                form.value.bulan = '';
            } else {
                isBulanan.value = false;
                form.value.bulan = null;
            }
        };

        const resetTargets = () => {
            form.value.kelas_id = '';
            form.value.jenjang_id = '';
        };

        const generateTagihan = async () => {
            loading.value = true;
            successMsg.value = '';
            errorMsg.value = '';

            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/generate-tagihan', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(form.value)
                });
                const res = await response.json();
                if (res.success) {
                    successMsg.value = `Berhasil menerbitkan ${res.count} tagihan untuk target siswa terpilih!`;
                    // Reset targets only, keep component configuration
                    resetTargets();
                } else {
                    errorMsg.value = res.error || 'Gagal menerbitkan tagihan.';
                }
            } catch (err) {
                errorMsg.value = 'Terjadi kesalahan jaringan.';
            } finally {
                loading.value = false;
            }
        };

        Vue.onMounted(() => {
            // Select active TA by default
            const activeTa = listTa.find(ta => ta.status === 'Aktif');
            if (activeTa) {
                form.value.tahun_ajaran_id = activeTa.id;
            }
        });

        return {
            listKelas,
            listJenjang,
            listTa,
            komponenList,
            loading,
            successMsg,
            errorMsg,
            isBulanan,
            targetType,
            form,
            onKomponenChange,
            resetTargets,
            generateTagihan
        };
    }
});
</script>
