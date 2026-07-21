
<div id="keuangan-generate-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-magic text-blue-600 me-2"></i> Menerbitkan Tagihan Baru (Generate)
            </h2>
            <p class="text-muted mb-0">Terbitkan tagihan masal secara otomatis berdasarkan tarif default yang telah dikonfigurasi.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <!-- Alert Feedback -->
            <div v-if="successMsg" class="alert alert-success border-0 rounded-4 d-flex align-items-center p-3 mb-4 shadow-sm">
                <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
                <div class="fw-semibold text-success-800">{{ successMsg }}</div>
            </div>
            <div v-if="errorMsg" class="alert alert-danger border-0 rounded-4 d-flex align-items-center p-3 mb-4 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                <div class="fw-semibold text-danger-800">{{ errorMsg }}</div>
            </div>

            <!-- Card Formulir -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-5">
                <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Konfigurasi Generate Tagihan</h5>
                
                <form @submit.prevent="generateTagihan" class="d-flex flex-column gap-3">
                    <!-- Komponen Biaya -->
                    <div>
                        <label class="form-label fw-semibold text-slate-700">Komponen Biaya <span class="text-danger">*</span></label>
                        <select class="form-select border-slate-200" v-model="form.komponen_id" @change="onKomponenChange" required style="height: 42px;">
                            <option value="" disabled>-- Pilih Komponen Biaya --</option>
                            <option v-for="k in komponenList" :value="k.id">{{ k.nama_komponen }} ({{ k.tipe_periode }})</option>
                        </select>
                    </div>

                    <div class="row g-3">
                        <!-- Tahun Ajaran -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-slate-700">Tahun Ajaran <span class="text-danger">*</span></label>
                            <select class="form-select border-slate-200" v-model="form.tahun_ajaran_id" required style="height: 42px;">
                                <option value="" disabled>-- Pilih Tahun Ajaran --</option>
                                <option v-for="ta in listTa" :value="ta.id">{{ ta.tahun_ajaran }}</option>
                            </select>
                        </div>

                        <!-- Bulan (jika bulanan) -->
                        <div class="col-md-6" v-if="isBulanan">
                            <label class="form-label fw-semibold text-slate-700">Bulan Tagihan <span class="text-danger">*</span></label>
                            <select class="form-select border-slate-200" v-model="form.bulan" required style="height: 42px;">
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
                    <div>
                        <label class="form-label fw-semibold text-slate-700">Target Distribusi</label>
                        <div class="d-flex gap-4 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="targetDist" id="targetAll" value="all" v-model="targetType" @change="resetTargets">
                                <label class="form-check-label text-slate-700 fw-medium" for="targetAll">Semua Kelas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="targetDist" id="targetKelas" value="kelas" v-model="targetType" @change="resetTargets">
                                <label class="form-check-label text-slate-700 fw-medium" for="targetKelas">Per Kelas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="targetDist" id="targetJenjang" value="jenjang" v-model="targetType" @change="resetTargets">
                                <label class="form-check-label text-slate-700 fw-medium" for="targetJenjang">Per Jenjang</label>
                            </div>
                        </div>

                        <!-- Dropdown kelas/jenjang -->
                        <div class="mb-3" v-if="targetType === 'kelas'">
                            <label class="form-label fw-semibold text-slate-700">Pilih Kelas Sasaran <span class="text-danger">*</span></label>
                            <select class="form-select border-slate-200" v-model="form.kelas_id" required style="height: 42px;">
                                <option value="" disabled>-- Pilih Kelas --</option>
                                <option v-for="c in listKelas" :value="c.id">{{ c.nama_kelas }}</option>
                            </select>
                        </div>

                        <div class="mb-3" v-if="targetType === 'jenjang'">
                            <label class="form-label fw-semibold text-slate-700">Pilih Jenjang Sasaran <span class="text-danger">*</span></label>
                            <select class="form-select border-slate-200" v-model="form.jenjang_id" required style="height: 42px;">
                                <option value="" disabled>-- Pilih Jenjang --</option>
                                <option v-for="j in listJenjang" :value="j.id">{{ j.nama_jenjang }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-3">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold w-100 py-3" :disabled="loading">
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
.fs-7 { font-size: 0.85rem; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }
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
