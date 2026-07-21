
<div id="keuangan-master-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-tags-fill text-blue-600 me-2"></i> Master Tarif & Biaya Keuangan
            </h2>
            <p class="text-muted mb-0">Kelola komponen tagihan dan atur nominal tarif dasar yang berlaku di sekolah.</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" id="masterTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-slate-700 py-3" id="komponen-tab" data-bs-toggle="tab" data-bs-target="#komponen-pane" type="button" role="tab">
                <i class="bi bi-grid-3x3-gap-fill me-2 text-blue-600"></i> Komponen Biaya
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-slate-700 py-3" id="tarif-tab" data-bs-toggle="tab" data-bs-target="#tarif-pane" type="button" role="tab">
                <i class="bi bi-cash-coin me-2 text-emerald-600"></i> Tarif Acuan Default
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="masterTabsContent">
        
        <!-- Tab 1: Komponen Biaya -->
        <div class="tab-pane fade show active" id="komponen-pane" role="tabpanel">
            <div class="row">
                <!-- Form Komponen (Left: 4-cols) -->
                <div class="col-12 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">
                            {{ formKomp.id ? 'Edit Komponen' : 'Tambah Komponen Baru' }}
                        </h5>
                        <form @submit.prevent="saveKomponen" class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Nama Komponen <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-slate-200" v-model="formKomp.nama_komponen" placeholder="Contoh: SPP Bulanan, Uang Buku LKS, Kegiatan KTS" required style="height: 42px;">
                            </div>
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Tipe Periode Pembayaran</label>
                                <select class="form-select border-slate-200" v-model="formKomp.tipe_periode" style="height: 42px;">
                                    <option value="Bulanan">Bulanan</option>
                                    <option value="Semester">Semester</option>
                                    <option value="Tahunan">Tahunan (Sekali di Awal)</option>
                                    <option value="Bebas">Bebas / Insidental / Sukarela</option>
                                </select>
                            </div>
                            <div class="pt-3 d-flex gap-2">
                                <button type="button" v-if="formKomp.id" @click="resetFormKomp" class="btn btn-outline-secondary fw-semibold py-2.5 flex-fill">Batal</button>
                                <button type="submit" class="btn btn-primary fw-bold py-2.5 flex-fill" :disabled="loadingKomp">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Komponen (Right: 8-cols) -->
                <div class="col-12 col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Daftar Komponen Biaya</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Nama Komponen</th>
                                        <th>Tipe Periode</th>
                                        <th class="text-center" style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="komp in komponenList" :key="komp.id">
                                        <td class="fw-bold text-slate-800">{{ komp.nama_komponen }}</td>
                                        <td>
                                            <span class="badge rounded px-3 py-2" :class="getPeriodeBadgeClass(komp.tipe_periode)">
                                                {{ komp.tipe_periode }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button @click="editKomponen(komp)" class="btn btn-link text-primary p-0 me-3" title="Edit">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </button>
                                            <button @click="deleteKomponen(komp.id)" class="btn btn-link text-danger p-0" title="Hapus">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="komponenList.length === 0">
                                        <td colspan="3" class="text-center py-4 text-muted">Belum ada komponen biaya terdaftar.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Tarif Acuan Default -->
        <div class="tab-pane fade" id="tarif-pane" role="tabpanel">
            <div class="row">
                <!-- Form Tarif (Left: 4-cols) -->
                <div class="col-12 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Tautkan Tarif Baru</h5>
                        <form @submit.prevent="saveTarif" class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Komponen Biaya <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formTarif.komponen_id" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Komponen --</option>
                                    <option v-for="k in komponenList" :value="k.id">{{ k.nama_komponen }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formTarif.tahun_ajaran_id" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Tahun Ajaran --</option>
                                    <option v-for="ta in listTa" :value="ta.id">
                                        {{ ta.tahun_ajaran }} {{ ta.status === 'Aktif' ? '(Aktif)' : '' }}
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Filter Target -->
                            <div>
                                <label class="form-label fw-semibold text-slate-700">Target Penerapan Tarif</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="targetRadio" id="radioGeneral" value="general" v-model="tarifTargetType" @change="resetTarifTargets">
                                    <label class="form-check-label text-slate-700 fw-medium" for="radioGeneral">Seluruh Siswa (General)</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="targetRadio" id="radioKelas" value="kelas" v-model="tarifTargetType" @change="resetTarifTargets">
                                    <label class="form-check-label text-slate-700 fw-medium" for="radioKelas">Spesifik per Kelas</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="targetRadio" id="radioJenjang" value="jenjang" v-model="tarifTargetType" @change="resetTarifTargets">
                                    <label class="form-check-label text-slate-700 fw-medium" for="radioJenjang">Spesifik per Jenjang</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="targetRadio" id="radioJalur" value="jalur" v-model="tarifTargetType" @change="resetTarifTargets">
                                    <label class="form-check-label text-slate-700 fw-medium" for="radioJalur">Spesifik per Jalur Masuk</label>
                                </div>
                            </div>

                            <!-- Dropdown dinamis target -->
                            <div v-if="tarifTargetType === 'kelas'">
                                <label class="form-label fw-semibold text-slate-700">Pilih Kelas <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formTarif.kelas_id" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Kelas --</option>
                                    <option v-for="c in listKelas" :value="c.id">{{ c.nama_kelas }}</option>
                                </select>
                            </div>

                            <div v-if="tarifTargetType === 'jenjang'">
                                <label class="form-label fw-semibold text-slate-700">Pilih Jenjang <span class="text-danger">*</span></label>
                                <select class="form-select border-slate-200" v-model="formTarif.jenjang_id" required style="height: 42px;">
                                    <option value="" disabled>-- Pilih Jenjang --</option>
                                    <option v-for="j in listJenjang" :value="j.id">{{ j.nama_jenjang }}</option>
                                </select>
                            </div>

                            <div v-if="tarifTargetType === 'jalur'">
                                <label class="form-label fw-semibold text-slate-700">Jalur Masuk PPDB <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-slate-200" v-model="formTarif.jalur_masuk" placeholder="Contoh: Prestasi, KIP, Reguler" required style="height: 42px;">
                            </div>

                            <div>
                                <label class="form-label fw-semibold text-slate-700">Nominal Tarif (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-slate-200 fw-bold">Rp</span>
                                    <input type="number" class="form-control border-slate-200" v-model="formTarif.nominal" placeholder="0" required style="height: 42px;">
                                </div>
                            </div>

                            <div class="pt-3">
                                <button type="submit" class="btn btn-primary fw-bold w-100 py-2.5" :disabled="loadingTarif">Simpan Tarif</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Tarif (Right: 8-cols) -->
                <div class="col-12 col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                        <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Daftar Tarif Default</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Komponen</th>
                                        <th>Target</th>
                                        <th>Nominal</th>
                                        <th>Tahun Ajaran</th>
                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="t in tarifList" :key="t.id">
                                        <td class="fw-bold text-slate-800">{{ t.nama_komponen }}</td>
                                        <td>
                                            <span v-if="t.nama_kelas" class="badge bg-blue-100 text-blue-700 px-3 py-2">Kelas {{ t.nama_kelas }}</span>
                                            <span v-else-if="t.nama_jenjang" class="badge bg-purple-100 text-purple-700 px-3 py-2">Jenjang {{ t.nama_jenjang }}</span>
                                            <span v-else-if="t.jalur_masuk" class="badge bg-teal-100 text-teal-700 px-3 py-2">Jalur {{ t.jalur_masuk }}</span>
                                            <span v-else class="badge bg-slate-100 text-slate-700 px-3 py-2">Semua Siswa</span>
                                        </td>
                                        <td class="fw-semibold text-slate-700">Rp {{ formatNumber(t.nominal) }}</td>
                                        <td>{{ t.tahun_ajaran }}</td>
                                        <td class="text-center">
                                            <button @click="deleteTarif(t.id)" class="btn btn-link text-danger p-0" title="Hapus">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="tarifList.length === 0">
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada tarif default dikonfigurasi.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Data Injections with Anti-XSS Flag -->
<script id="data-kelas" type="application/json">
    <?php echo json_encode($list_kelas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-jenjang" type="application/json">
    <?php echo json_encode($list_jenjang, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="data-ta" type="application/json">
    <?php echo json_encode($list_ta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<style>
/* Styling Navtabs Minimalis Datar (Gambar 1) */
.nav-tabs {
    border-bottom: 1px solid #e2e8f0 !important;
}
.nav-tabs .nav-item {
    margin-bottom: -1px;
}
.nav-tabs .nav-link {
    border: none !important;
    border-bottom: 2px solid transparent !important;
    color: #64748b !important;
    font-weight: 600 !important;
    background: transparent !important;
    padding: 0.8rem 1.2rem !important;
    font-size: 0.85rem !important;
    transition: all 0.15s ease-in-out;
}
.nav-tabs .nav-link:hover {
    color: #1e293b !important;
    border-bottom-color: #cbd5e1 !important;
}
.nav-tabs .nav-link.active {
    color: #2563eb !important;
    border-bottom-color: #2563eb !important;
    background: transparent !important;
}

/* Styling Tabel Modern Borderless (Gambar 1) */
.table {
    border-collapse: collapse !important;
    width: 100%;
}
.table th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.75rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 2px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.75rem 1rem !important;
}
.table td {
    border-bottom: 1px solid #f1f5f9 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.85rem 1rem !important;
    font-size: 0.8rem !important;
    color: #334155 !important;
}
.table tbody tr {
    transition: background-color 0.15s ease;
}
.table tbody tr:hover {
    background-color: #f8fafc !important;
}

.fs-7 { font-size: 0.85rem; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }
.bg-blue-100 { background-color: #dbeafe; }
.text-blue-700 { color: #1d4ed8; }
.bg-purple-100 { background-color: #f3e8ff; }
.text-purple-700 { color: #6b21a8; }
.bg-teal-100 { background-color: #ccfbf1; }
.text-teal-700 { color: #0f766e; }
.bg-slate-100 { background-color: #f1f5f9; }
</style>

<script>
window.VueAppRegistry.register('#keuangan-master-app', {
    setup() {
        // Parsed injected list data
        const listKelas = JSON.parse(document.getElementById('data-kelas').textContent || '[]');
        const listJenjang = JSON.parse(document.getElementById('data-jenjang').textContent || '[]');
        const listTa = JSON.parse(document.getElementById('data-ta').textContent || '[]');

        // Tab 1: Komponen State
        const komponenList = Vue.ref([]);
        const loadingKomp = Vue.ref(false);
        const formKomp = Vue.ref({
            id: 0,
            nama_komponen: '',
            tipe_periode: 'Bulanan'
        });

        // Tab 2: Tarif State
        const tarifList = Vue.ref([]);
        const loadingTarif = Vue.ref(false);
        const tarifTargetType = Vue.ref('general');
        const formTarif = Vue.ref({
            komponen_id: '',
            tahun_ajaran_id: '',
            kelas_id: '',
            jenjang_id: '',
            jalur_masuk: '',
            nominal: ''
        });

        // Load all components
        const fetchKomponen = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/komponen');
                const res = await response.json();
                if (res.success) {
                    komponenList.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        // Load all tariffs
        const fetchTarif = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/tarif');
                const res = await response.json();
                if (res.success) {
                    tarifList.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        // Reset form components
        const resetFormKomp = () => {
            formKomp.value = { id: 0, nama_komponen: '', tipe_periode: 'Bulanan' };
        };

        const saveKomponen = async () => {
            loadingKomp.value = true;
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/komponen', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formKomp.value)
                });
                const res = await response.json();
                if (res.success) {
                    fetchKomponen();
                    resetFormKomp();
                }
            } catch (err) {
                console.error(err);
            } finally {
                loadingKomp.value = false;
            }
        };

        const editKomponen = (item) => {
            formKomp.value = { ...item };
        };

        const deleteKomponen = async (id) => {
            if (!confirm('Apakah Anda yakin ingin menghapus komponen biaya ini? Semua tarif terkait akan terhapus.')) return;
            try {
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/komponen?id=${id}`, { method: 'DELETE' });
                const res = await response.json();
                if (res.success) {
                    fetchKomponen();
                    fetchTarif();
                }
            } catch (err) {
                console.error(err);
            }
        };

        // Reset dynamic fields when switching target types in Tarif Form
        const resetTarifTargets = () => {
            formTarif.value.kelas_id = '';
            formTarif.value.jenjang_id = '';
            formTarif.value.jalur_masuk = '';
        };

        const saveTarif = async () => {
            loadingTarif.value = true;
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/tarif', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formTarif.value)
                });
                const res = await response.json();
                if (res.success) {
                    fetchTarif();
                    formTarif.value.nominal = '';
                    resetTarifTargets();
                }
            } catch (err) {
                console.error(err);
            } finally {
                loadingTarif.value = false;
            }
        };

        const deleteTarif = async (id) => {
            if (!confirm('Hapus tarif default ini?')) return;
            try {
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/tarif?id=${id}`, { method: 'DELETE' });
                const res = await response.json();
                if (res.success) {
                    fetchTarif();
                }
            } catch (err) {
                console.error(err);
            }
        };

        // Helpers
        const getPeriodeBadgeClass = (tipe) => {
            switch(tipe) {
                case 'Bulanan': return 'bg-primary text-white';
                case 'Semester': return 'bg-warning text-dark';
                case 'Tahunan': return 'bg-success text-white';
                default: return 'bg-secondary text-white';
            }
        };

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num);
        };

        Vue.onMounted(() => {
            fetchKomponen();
            fetchTarif();
            
            // Preselect active academic year
            const activeTa = listTa.find(ta => ta.status === 'Aktif');
            if (activeTa) {
                formTarif.value.tahun_ajaran_id = activeTa.id;
            }
        });

        return {
            listKelas,
            listJenjang,
            listTa,
            komponenList,
            loadingKomp,
            formKomp,
            tarifList,
            loadingTarif,
            tarifTargetType,
            formTarif,
            saveKomponen,
            editKomponen,
            deleteKomponen,
            resetFormKomp,
            resetTarifTargets,
            saveTarif,
            deleteTarif,
            getPeriodeBadgeClass,
            formatNumber
        };
    }
});
</script>
