
<div id="keuangan-keringanan-app" v-cloak class="container-fluid px-3 py-3 workspace-container">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h5 class="fw-bold text-slate-800 mb-0" style="font-size: 1.1rem;">
                <i class="bi bi-award-fill text-blue-600 me-2"></i> Keringanan & Beasiswa Siswa
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.72rem;">Atur dispensasi, keringanan tarif, atau program beasiswa khusus untuk masing-masing siswa secara dinamis.</p>
        </div>
    </div>

    <div class="workspace-body flex-grow-1 min-height-0">
        <!-- Form Keringanan (Left: 30%) -->
        <div class="panel-form">
            <div class="panel-header">
                <span class="fw-bold text-slate-800" style="font-size: 0.82rem;">Terapkan Beasiswa / Potongan</span>
            </div>
            <div class="panel-content form-compact">
                <form @submit.prevent="saveKeringanan" class="d-flex flex-column h-100 gap-2">
                    
                    <!-- Autocomplete Search Siswa -->
                    <div class="position-relative">
                        <label class="form-label">Cari Siswa <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search" style="font-size: 0.75rem;"></i></span>
                            <input type="text" class="form-control" v-model="siswaSearch" @input="searchSiswa" placeholder="Ketik nama atau NISN siswa...">
                        </div>
                        <!-- Suggestions Dropdown -->
                        <ul class="dropdown-menu show w-100 shadow border-slate-200 p-0 overflow-hidden" v-if="siswaSuggestions.length > 0" style="display: block; max-height: 200px; overflow-y: auto; z-index: 1010; font-size: 0.78rem;">
                            <li v-for="s in siswaSuggestions" :key="s.id">
                                <a href="#" class="dropdown-item py-1.5 px-3 d-flex justify-content-between align-items-center" @click.prevent="selectSiswa(s)">
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
                            <div style="line-height: 1.2;">
                                <span class="fw-bold" style="font-size: 0.72rem;">{{ selectedSiswa.nama }}</span><br>
                                <span style="font-size: 0.65rem;" class="text-blue-600">NISN: {{ selectedSiswa.nisn }} | Kelas: {{ selectedSiswa.nama_kelas }}</span>
                            </div>
                            <button type="button" class="btn-close" style="font-size: 0.6rem;" @click="clearSelectedSiswa"></button>
                        </div>
                    </div>

                    <!-- Komponen Biaya -->
                    <div>
                        <label class="form-label">Komponen Biaya <span class="text-danger">*</span></label>
                        <select class="form-select" v-model="form.komponen_id" required>
                            <option value="" disabled>-- Pilih Komponen --</option>
                            <option v-for="k in komponenList" :value="k.id">{{ k.nama_komponen }}</option>
                        </select>
                    </div>

                    <!-- Tipe Keringanan -->
                    <div>
                        <label class="form-label">Tipe Potongan <span class="text-danger">*</span></label>
                        <select class="form-select" v-model="form.tipe_keringanan" required>
                            <option value="Nominal">Nominal Tetap (Rp)</option>
                            <option value="Persentase">Persentase (%)</option>
                        </select>
                    </div>

                    <!-- Nilai Potongan -->
                    <div>
                        <label class="form-label">Nilai Potongan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold" style="font-size: 0.75rem;" v-if="form.tipe_keringanan === 'Nominal'">Rp</span>
                            <input type="number" class="form-control" v-model="form.nilai" placeholder="0" required>
                            <span class="input-group-text bg-light fw-bold" style="font-size: 0.75rem;" v-if="form.tipe_keringanan === 'Persentase'">%</span>
                        </div>
                    </div>

                    <!-- Keterangan / Alasan -->
                    <div>
                        <label class="form-label">Keterangan / Alasan Beasiswa</label>
                        <textarea class="form-control" v-model="form.keterangan" rows="2" style="height: auto;" placeholder="Contoh: Siswa Berprestasi, Keringanan Yatim, dll."></textarea>
                    </div>

                    <div class="mt-auto pt-2 border-top">
                        <button type="submit" class="btn btn-primary btn-compact w-100" :disabled="loading || !selectedSiswa">Simpan Keringanan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Keringanan (Right: 70%) -->
        <div class="panel-table">
            <div class="panel-header">
                <span class="fw-bold text-slate-800" style="font-size: 0.82rem;">Daftar Keringanan Aktif</span>
            </div>
            <div class="panel-content p-0">
                <div class="table-compact-container">
                    <table class="table table-hover table-compact table-bordered">
                        <thead>
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
                                    <span class="badge rounded px-2 py-1 badge-custom" :class="k.tipe_keringanan === 'Nominal' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">
                                        {{ k.tipe_keringanan }}
                                    </span>
                                </td>
                                <td class="fw-bold text-slate-800">
                                    <span v-if="k.tipe_keringanan === 'Nominal'">Rp {{ formatNumber(k.nilai) }}</span>
                                    <span v-else>{{ formatNumber(k.nilai) }}%</span>
                                </td>
                                <td class="text-muted" style="font-size: 0.75rem;">{{ k.keterangan || '-' }}</td>
                                <td class="text-center">
                                    <button @click="deleteKeringanan(k.id)" class="btn btn-link text-danger p-0" title="Hapus">
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
.workspace-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - var(--header-height) - 1.5rem);
    overflow: hidden;
}
.workspace-body {
    display: flex;
    flex-grow: 1;
    overflow: hidden;
    gap: 0.75rem;
    min-height: 0;
}
.panel-form {
    width: 30%;
    min-width: 290px;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}
.panel-table {
    width: 70%;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    flex-grow: 1;
    min-width: 0;
}
.panel-header {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.panel-content {
    padding: 0.6rem 0.75rem;
    overflow-y: auto;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
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
.form-compact .input-group .form-control {
    height: 32px;
}
.form-compact .input-group-text {
    padding: 0.35rem 0.6rem;
    font-size: 0.8rem;
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
.table-compact-container {
    overflow-y: auto;
    flex-grow: 1;
    min-height: 0;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    background: #ffffff;
}
.table-compact {
    border-collapse: collapse !important;
    font-size: 0.8rem;
    margin-bottom: 0;
    width: 100%;
}
.table-compact th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.72rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 2px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.6rem 0.75rem !important;
}
.table-compact td {
    border-bottom: 1px solid #f1f5f9 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    padding: 0.52rem 0.75rem !important;
    vertical-align: middle;
    white-space: nowrap;
    color: #334155 !important;
    background-color: transparent !important;
}
.table-compact tbody tr {
    transition: background-color 0.15s ease;
}
.table-compact tbody tr:hover {
    background-color: #f8fafc !important;
}
.badge-custom {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
}
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

/* Responsive Mobile Stack (HP) */
@media (max-width: 767.98px) {
    .workspace-container {
        height: auto !important;
        overflow: visible !important;
    }
    .workspace-body {
        flex-direction: column !important;
        height: auto !important;
        overflow: visible !important;
    }
    .panel-form {
        width: 100% !important;
        min-width: auto !important;
        overflow: visible !important;
        margin-bottom: 1rem !important;
    }
    .panel-table {
        width: 100% !important;
        overflow: visible !important;
    }
    .table-compact-container {
        overflow-y: visible !important;
        overflow-x: auto !important;
    }
    .table-compact th {
        position: static !important;
    }
}
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
