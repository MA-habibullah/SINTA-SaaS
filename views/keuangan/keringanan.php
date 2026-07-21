
<div id="keuangan-keringanan-app" v-cloak class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-slate-800 mb-1">
                <i class="bi bi-award-fill text-blue-600 me-2"></i> Keringanan & Beasiswa Siswa
            </h2>
            <p class="text-muted mb-0">Kelola potongan tarif (nominal maupun persentase) beasiswa untuk siswa tertentu.</p>
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
                Mengonfigurasi data potongan khusus untuk siswa terpilih pada sekolah target.
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form Keringanan -->
        <div class="col-12 col-md-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Konfigurasi Keringanan Baru</h5>
                
                <form @submit.prevent="saveKeringanan" class="d-flex flex-column gap-3">
                    <!-- Cari Siswa Autocomplete -->
                    <div class="position-relative">
                        <label class="form-label fw-semibold text-slate-700">Cari Siswa <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-slate-200"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-slate-200" v-model="siswaSearch" @input="searchSiswa" placeholder="Ketik nama/NISN siswa..." style="height: 42px;">
                        </div>
                        <ul class="dropdown-menu show w-100 shadow border-slate-200 p-0 overflow-hidden" v-if="siswaSuggestions.length > 0" style="display: block; max-height: 200px; overflow-y: auto; z-index: 1010;">
                            <li v-for="s in siswaSuggestions" :key="s.id">
                                <a href="#" class="dropdown-item py-2.5 px-3 d-flex justify-content-between align-items-center" @click.prevent="selectSiswa(s)">
                                    <div>
                                        <div class="fw-bold text-slate-800 fs-7">{{ s.nama }}</div>
                                        <small class="text-muted">NISN: {{ s.nisn }} | Kelas: {{ s.nama_kelas }}</small>
                                    </div>
                                    <i class="bi bi-plus-circle text-blue-600 fs-5"></i>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Selected Siswa Box -->
                    <div class="p-3 bg-blue-50 border border-blue-100 rounded-3 d-flex align-items-center justify-content-between" v-if="selectedSiswa">
                        <div>
                            <div class="fw-bold text-slate-800 fs-7">{{ selectedSiswa.nama }}</div>
                            <small class="text-muted">NISN: {{ selectedSiswa.nisn }} | Kelas: {{ selectedSiswa.nama_kelas }}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" @click="clearSelectedSiswa"><i class="bi bi-x-circle fs-5"></i></button>
                    </div>

                    <!-- Komponen SPP/Biaya -->
                    <div>
                        <label class="form-label fw-semibold text-slate-700">Komponen Biaya <span class="text-danger">*</span></label>
                        <select class="form-select border-slate-200" v-model="form.komponen_id" required style="height: 42px;">
                            <option value="" disabled>-- Pilih Komponen --</option>
                            <option v-for="k in komponenList" :value="k.id" :disabled="k.is_active == 0">{{ k.nama_komponen }} {{ k.is_active == 0 ? '(Non-Aktif)' : '' }}</option>
                        </select>
                    </div>

                    <!-- Tipe Keringanan -->
                    <div>
                        <label class="form-label fw-semibold text-slate-700">Tipe Keringanan</label>
                        <select class="form-select border-slate-200" v-model="form.tipe_keringanan" style="height: 42px;">
                            <option value="Nominal">Nominal (Rp)</option>
                            <option value="Persentase">Persentase (%)</option>
                        </select>
                    </div>

                    <!-- Nilai Potongan -->
                    <div>
                        <label class="form-label fw-semibold text-slate-700">Besar Potongan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-slate-200 fw-bold" v-if="form.tipe_keringanan === 'Nominal'">Rp</span>
                            <input type="number" class="form-control border-slate-200" v-model.number="form.nilai" placeholder="0" required style="height: 42px;">
                            <span class="input-group-text bg-light border-slate-200 fw-bold" v-if="form.tipe_keringanan === 'Persentase'">%</span>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="form-label fw-semibold text-slate-700">Keterangan / Alasan Beasiswa</label>
                        <textarea class="form-control border-slate-200" v-model="form.keterangan" rows="3" placeholder="Contoh: Siswa Berprestasi, Keringanan Yatim Piatu, dll."></textarea>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="btn btn-primary fw-bold w-100 py-2.5" :disabled="loading || !selectedSiswa">Simpan Keringanan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Keringanan -->
        <div class="col-12 col-md-8 mb-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h5 class="fw-bold text-slate-800 mb-4 border-bottom pb-2">Daftar Keringanan Aktif</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
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
                            <tr v-for="k in paginatedKeringanan" :key="k.id">
                                <td>
                                    <div class="fw-bold text-slate-800">{{ k.nama_siswa }}</div>
                                    <small class="text-muted">NISN: {{ k.nisn }}</small>
                                </td>
                                <td class="fw-semibold text-slate-700">{{ k.nama_komponen }}</td>
                                <td>
                                    <span class="badge rounded px-3 py-2" :class="k.tipe_keringanan === 'Nominal' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">
                                        {{ k.tipe_keringanan }}
                                    </span>
                                </td>
                                <td class="fw-bold text-slate-800">
                                    <span v-if="k.tipe_keringanan === 'Nominal'">Rp {{ formatNumber(k.nilai) }}</span>
                                    <span v-else>{{ formatNumber(k.nilai) }}%</span>
                                </td>
                                <td class="text-muted fs-7">{{ k.keterangan || '-' }}</td>
                                <td class="text-center">
                                    <button @click="deleteKeringanan(k.id)" class="btn btn-link text-danger p-0" title="Hapus">
                                        <i class="bi bi-trash fs-5"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredKeringanan.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada keringanan/beasiswa terdaftar.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Keringanan -->
                <div class="d-flex justify-content-between align-items-center mt-3" v-if="totalKeringananPages > 1">
                    <span class="text-muted fs-8">Menampilkan Halaman {{ currentPage }} dari {{ totalKeringananPages }}</span>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-end mb-0">
                            <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                <a class="page-link" href="#" @click.prevent="currentPage--">Sebelumnya</a>
                            </li>
                            <li class="page-item" v-for="p in visibleKeringananPages" :key="p" :class="{ active: currentPage === p, disabled: p === '...' }">
                                <span v-if="p === '...'" class="page-link">...</span>
                                <a v-else class="page-link" href="#" @click.prevent="currentPage = p">{{ p }}</a>
                            </li>
                            <li class="page-item" :class="{ disabled: currentPage === totalKeringananPages }">
                                <a class="page-link" href="#" @click.prevent="currentPage++">Berikutnya</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Injection -->
<script id="data-komponen" type="application/json">
    <?php echo json_encode($list_komponen, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script id="user-session" type="application/json">
    <?php echo json_encode([
        'is_super_admin' => (($_SESSION['role_name'] ?? '') === 'super_admin'),
        'tenant_id' => ($_SESSION['tenant_id'] ?? '')
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<style>
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
        const session = JSON.parse(document.getElementById('user-session').textContent || '{}');
        const isSuperAdmin = session.is_super_admin;
        const tenantsList = Vue.ref([]);
        const selectedTenantId = Vue.ref(session.tenant_id || '');

        const komponenList = Vue.ref([]);
        const initialKomponen = JSON.parse(document.getElementById('data-komponen').textContent || '[]');

        const keringananList = Vue.ref([]);
        const loading = Vue.ref(false);

        // Student selection autocomplete
        const siswaSearch = Vue.ref('');
        const siswaSuggestions = Vue.ref([]);
        const selectedSiswa = Vue.ref(null);

        // Pagination
        const currentPage = Vue.ref(1);
        const pageSize = Vue.ref(6);

        const form = Vue.ref({
            siswa_id: '',
            komponen_id: '',
            tipe_keringanan: 'Nominal',
            nilai: '',
            keterangan: ''
        });

        // Helper to append tenant query parameter for super admin
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

        // Reload components list based on selected tenant
        const fetchKomponen = async () => {
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/komponen' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    komponenList.value = res.data;
                }
            } catch (err) {
                console.error(err);
            }
        };

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
                    const tenantSuffix = isSuperAdmin && selectedTenantId.value ? `&tenant_id=${selectedTenantId.value}` : '';
                    const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/cari-siswa?q=${encodeURIComponent(siswaSearch.value)}${tenantSuffix}`);
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
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/keringanan' + getQueryParam());
                const res = await response.json();
                if (res.success) {
                    keringananList.value = res.data;
                    currentPage.value = 1;
                }
            } catch (err) {
                console.error(err);
            }
        };

        const onTenantChange = () => {
            localStorage.setItem('sinta_spp_selected_tenant_id', selectedTenantId.value);
            clearSelectedSiswa();
            fetchKomponen();
            fetchKeringanan();
        };

        const saveKeringanan = async () => {
            loading.value = true;
            try {
                const response = await fetch('/SINTA-SaaS/api/v1/keuangan/keringanan' + getQueryParam(), {
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
                } else {
                    alert(res.error || 'Gagal menyimpan beasiswa.');
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
                const response = await fetch(`/SINTA-SaaS/api/v1/keuangan/keringanan?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' }
                });
                const res = await response.json();
                if (res.success) {
                    fetchKeringanan();
                } else {
                    alert(res.error || 'Gagal menghapus beasiswa.');
                }
            } catch (err) {
                console.error(err);
            }
        };

        // Filtered and Paginated computed properties
        const filteredKeringanan = Vue.computed(() => keringananList.value);

        const paginatedKeringanan = Vue.computed(() => {
            const start = (currentPage.value - 1) * pageSize.value;
            return filteredKeringanan.value.slice(start, start + pageSize.value);
        });

        const totalKeringananPages = Vue.computed(() => {
            return Math.ceil(filteredKeringanan.value.length / pageSize.value) || 1;
        });

        const getVisiblePages = (current, total) => {
            const delta = 2;
            const left = current - delta;
            const right = current + delta + 1;
            const range = [];
            const rangeWithDots = [];
            let l;

            for (let i = 1; i <= total; i++) {
                if (i === 1 || i === total || (i >= left && i < right)) {
                    range.push(i);
                }
            }

            for (const i of range) {
                if (l) {
                    if (i - l === 2) {
                        rangeWithDots.push(l + 1);
                    } else if (i - l > 2) {
                        rangeWithDots.push('...');
                    }
                }
                rangeWithDots.push(i);
                l = i;
            }

            return rangeWithDots;
        };

        const visibleKeringananPages = Vue.computed(() => {
            return getVisiblePages(currentPage.value, totalKeringananPages.value);
        });

        const formatNumber = (num) => {
            return new Intl.NumberFormat('id-ID').format(num);
        };

        Vue.onMounted(async () => {
            if (isSuperAdmin) {
                await fetchTenants();
            } else {
                komponenList.value = initialKomponen;
            }
            await fetchKomponen();
            await fetchKeringanan();
        });

        return {
            isSuperAdmin,
            tenantsList,
            selectedTenantId,
            komponenList,
            keringananList,
            loading,
            siswaSearch,
            siswaSuggestions,
            selectedSiswa,
            currentPage,
            pageSize,
            form,
            searchSiswa,
            selectSiswa,
            clearSelectedSiswa,
            onTenantChange,
            saveKeringanan,
            deleteKeringanan,
            filteredKeringanan,
            paginatedKeringanan,
            totalKeringananPages,
            visibleKeringananPages,
            formatNumber
        };
    }
});
</script>
