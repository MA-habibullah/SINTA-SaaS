<?php include __DIR__ . '/../layout/header.php'; ?>

<div id="keuangan-keringanan-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-award-fill text-blue-600 me-2"></i> Keringanan & Beasiswa Siswa
            </h2>
            <p class="text-muted mb-0">Atur dispensasi, keringanan tarif, atau program beasiswa khusus untuk masing-masing siswa secara dinamis.</p>
        </div>
    </div>

    <div class="row">
        <!-- Form Keringanan -->
        <div class="col-12 col-md-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-slate-800 mb-3">Terapkan Beasiswa / Potongan</h5>
                <form @submit.prevent="saveKeringanan">
                    
                    <!-- Autocomplete Search Siswa -->
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-semibold text-slate-700">Cari Siswa <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-slate-200"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-slate-200" v-model="siswaSearch" @input="searchSiswa" placeholder="Ketik nama atau NISN siswa...">
                        </div>
                        <!-- Suggestions Dropdown -->
                        <ul class="dropdown-menu show w-100 shadow border-slate-200 p-0 overflow-hidden" v-if="siswaSuggestions.length > 0" style="display: block; max-height: 250px; overflow-y: auto; z-index: 1010;">
                            <li v-for="s in siswaSuggestions" :key="s.id">
                                <a href="#" class="dropdown-item py-2 px-3 d-flex justify-content-between align-items-center" @click.prevent="selectSiswa(s)">
                                    <div>
                                        <div class="fw-bold text-slate-800">{{ s.nama }}</div>
                                        <small class="text-muted">NISN: {{ s.nisn }} | Kelas: {{ s.nama_kelas }}</small>
                                    </div>
                                    <i class="bi bi-plus-circle text-blue-600"></i>
                                </a>
                            </li>
                        </ul>
                        <!-- Selected Student Badge -->
                        <div v-if="selectedSiswa" class="mt-2 p-2 bg-blue-50 text-blue-800 rounded border border-blue-200 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold fs-7">{{ selectedSiswa.nama }}</span><br>
                                <span class="fs-8 text-blue-600">NISN: {{ selectedSiswa.nisn }} | Kelas: {{ selectedSiswa.nama_kelas }}</span>
                            </div>
                            <button type="button" class="btn-close" @click="clearSelectedSiswa"></button>
                        </div>
                    </div>

                    <!-- Komponen Biaya -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-slate-700">Komponen Biaya <span class="text-danger">*</span></label>
                        <select class="form-select border-slate-200" v-model="form.komponen_id" required>
                            <option value="" disabled>-- Pilih Komponen --</option>
                            <option v-for="k in komponenList" :value="k.id">{{ k.nama_komponen }}</option>
                        </select>
                    </div>

                    <!-- Tipe Keringanan -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-slate-700">Tipe Potongan <span class="text-danger">*</span></label>
                        <select class="form-select border-slate-200" v-model="form.tipe_keringanan" required>
                            <option value="Nominal">Nominal Tetap (Rp)</option>
                            <option value="Persentase">Persentase (%)</option>
                        </select>
                    </div>

                    <!-- Nilai Potongan -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-slate-700">Nilai Potongan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-slate-200 fw-bold" v-if="form.tipe_keringanan === 'Nominal'">Rp</span>
                            <input type="number" class="form-control border-slate-200" v-model="form.nilai" placeholder="0" required>
                            <span class="input-group-text bg-light border-slate-200 fw-bold" v-if="form.tipe_keringanan === 'Persentase'">%</span>
                        </div>
                    </div>

                    <!-- Keterangan / Alasan -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-slate-700">Keterangan / Alasan Beasiswa</label>
                        <textarea class="form-control border-slate-200" v-model="form.keterangan" rows="3" placeholder="Contoh: Siswa Berprestasi, Keringanan Yatim Piatu, dll."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary fw-bold w-100" :disabled="loading || !selectedSiswa">Simpan Keringanan</button>
                </form>
            </div>
        </div>

        <!-- Tabel Keringanan -->
        <div class="col-12 col-md-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white p-4">
                <h5 class="fw-bold text-slate-800 mb-3">Daftar Keringanan Aktif</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light text-slate-700">
                            <tr>
                                <th>Nama Siswa</th>
                                <th>Komponen Tagihan</th>
                                <th>Tipe</th>
                                <th>Nilai Potongan</th>
                                <th>Keterangan</th>
                                <th class="text-center" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="k in keringananList" :key="k.id">
                                <td>
                                    <div class="fw-bold text-slate-800">{{ k.nama_siswa }}</div>
                                    <small class="text-muted">NISN: {{ k.nisn }}</small>
                                </td>
                                <td class="fw-semibold text-slate-700">{{ k.nama_komponen }}</td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2" :class="k.tipe_keringanan === 'Nominal' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">
                                        {{ k.tipe_keringanan }}
                                    </span>
                                </td>
                                <td class="fw-bold text-slate-800">
                                    <span v-if="k.tipe_keringanan === 'Nominal'">Rp {{ formatNumber(k.nilai) }}</span>
                                    <span v-else>{{ formatNumber(k.nilai) }}%</span>
                                </td>
                                <td class="text-muted fs-7">{{ k.keterangan || '-' }}</td>
                                <td class="text-center">
                                    <button @click="deleteKeringanan(k.id)" class="btn btn-sm btn-outline-danger border-0" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="keringananList.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada keringanan/beasiswa terdaftar.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Injection -->
<script id="data-komponen" type="application/json">
    <?php echo json_encode($list_komponen, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<style>
.fs-7 { font-size: 0.85rem; }
.fs-8 { font-size: 0.75rem; }
.bg-blue-50 { background-color: #eff6ff; }
.bg-blue-100 { background-color: #dbeafe; }
.text-blue-700 { color: #1d4ed8; }
.bg-amber-100 { background-color: #fef3c7; }
.text-amber-700 { color: #b45309; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-200 { border-color: #e2e8f0; }
</style>

<script>
window.VueAppRegistry.register('#keuangan-keringanan-app', {
    setup() {
        const komponenList = JSON.parse(document.getElementById('data-komponen').textContent || '[]');

        const keringananList = Vue.ref([]);
        const loading = Vue.ref(false);

        // Student selection autocomplete
        const siswaSearch = Vue.ref('');
        const siswaSuggestions = Vue.ref([]);
        const selectedSiswa = Vue.ref(null);

        const form = Vue.ref({
            siswa_id: '',
            komponen_id: '',
            tipe_keringanan: 'Nominal',
            nilai: '',
            keterangan: ''
        });

        // Search student dynamic lookup
        let searchTimeout = null;
        const searchSiswa = () => {
            clearTimeout(searchTimeout);
            if (siswaSearch.value.length < 2) {
                siswaSuggestions.value = [];
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/cari-siswa?q=${encodeURIComponent(siswaSearch.value)}`);
                    const res = await response.json();
                    if (res.success) {
                        siswaSuggestions.value = res.data;
                    }
                } catch (err) {
                    console.error(err);
                }
            }, 300);
        };

        const selectSiswa = (siswa) => {
            selectedSiswa.value = siswa;
            form.value.siswa_id = siswa.id;
            siswaSearch.value = '';
            siswaSuggestions.value = [];
        };

        const clearSelectedSiswa = () => {
            selectedSiswa.value = null;
            form.value.siswa_id = '';
        };

        const fetchKeringanan = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/keringanan');
                const res = await response.json();
                if (res.success) {
                    keringananList.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const saveKeringanan = async () => {
            loading.value = true;
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/keringanan', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(form.value)
                });
                const res = await response.json();
                if (res.success) {
                    fetchKeringanan();
                    // Reset form
                    form.value.komponen_id = '';
                    form.value.nilai = '';
                    form.value.keterangan = '';
                    clearSelectedSiswa();
                }
            } catch (err) {
                console.error(err);
            } finally {
                loading.value = false;
            }
        };

        const deleteKeringanan = async (id) => {
            if (!confirm('Hapus konfigurasi beasiswa siswa ini?')) return;
            try {
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/keringanan?id=${id}`, { method: 'DELETE' });
                const res = await response.json();
                if (res.success) {
                    fetchKeringanan();
                }
            } catch (err) {
                console.error(err);
            }
        };

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num);
        };

        Vue.onMounted(() => {
            fetchKeringanan();
        });

        return {
            komponenList,
            keringananList,
            loading,
            siswaSearch,
            siswaSuggestions,
            selectedSiswa,
            form,
            searchSiswa,
            selectSiswa,
            clearSelectedSiswa,
            saveKeringanan,
            deleteKeringanan,
            formatNumber
        };
    }
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
