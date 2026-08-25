<?php
/**
 * View: Kelola Akses Menu Per Tenant (Child View)
 * Dipanggil oleh SuperAdminController dan dimuat di dalam layout/master.php
 */
?>
<!-- Area Konten Utama Terbungkus Vue.js App -->
<div id="tenantMenusApp">

    <!-- 1. Row Header & Action Toolbar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-blue-600 text-white rounded-2xl d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 48px; height: 48px;">
                <i class="bi bi-diagram-3-fill fs-4"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold text-slate-900 fs-4 mb-0">{{ title }}</h3>
                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-1 fs-9 font-bold">
                        <i class="bi bi-shield-check text-blue-600 me-1"></i>Super Admin
                    </span>
                </div>
                <p class="text-slate-500 fs-8 mb-0 mt-0.5">Atur ketersediaan menu sidebar dan akses modul fitur bagi masing-masing sekolah (Tenant) secara terpusat.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= $this->getBaseUrl() ?>/dashboard" class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>
    </div>

    <!-- 2. Compact School Selector Banner (Khusus Super Admin Auto-Filter) -->
    <div class="mb-4 p-3 px-md-4 rounded-2xl shadow-2xs border border-blue-100 bg-white">
        <div class="d-flex align-items-center flex-wrap gap-2.5">
            <div class="bg-blue-50 text-blue-600 p-2 rounded-xl d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                <i class="bi bi-buildings fs-6"></i>
            </div>
            <div>
                <span class="fs-8 fw-bold text-slate-800 me-1">Pilih Instansi Sekolah:</span>
            </div>
            
            <div class="my-1 my-md-0" style="min-width: 220px; max-width: 300px;">
                <select 
                    id="tenantSelect" 
                    class="form-select form-select-sm bg-slate-50 border-slate-200 rounded-xl text-slate-800 fs-8 font-semibold shadow-2xs cursor-pointer focus:bg-white w-100" 
                    v-model="selectedTenantId" 
                    @change="fetchTenantData"
                    :disabled="isLoading"
                    style="height: 38px;"
                >
                    <option value="">-- Pilih Sekolah / Tenant --</option>
                    <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                        {{ tenant.nama_sekolah }}{{ tenant.npsn ? ' (' + tenant.npsn + ')' : '' }}
                    </option>
                </select>
            </div>

            <!-- Badge Data Aktif Tepat di Samping Filter -->
            <div class="d-inline-flex align-items-center flex-shrink-0 ms-md-1">
                <span class="badge bg-blue-50 text-blue-700 border border-blue-200 px-3 py-2 rounded-pill fs-8 font-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs" 
                      style="max-width: 340px;" 
                      :title="'Mengedit Fitur: ' + (selectedTenant ? selectedTenant.nama_sekolah : '')" 
                      v-if="selectedTenant">
                    <i class="bi bi-check2-circle text-blue-600 flex-shrink-0"></i>
                    <span class="text-truncate d-inline-block" style="max-width: 280px;">
                        Mengedit Fitur: <strong>{{ selectedTenant.nama_sekolah }}</strong>
                    </span>
                </span>
                <span class="badge bg-slate-100 text-slate-500 border border-slate-200 px-3 py-2 rounded-pill fs-8 font-medium" v-else>
                    <i class="bi bi-info-circle text-blue-500 me-1"></i>Pilih sekolah untuk memuat fitur
                </span>
            </div>
        </div>
    </div>

    <!-- Matriks Fitur & Menu Sidebar -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" v-if="selectedTenantId">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h5 class="fw-bold m-0 text-dark">
                    <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Matriks Fitur Aplikasi Sekolah
                </h5>
                <p class="text-muted fs-8 mb-0 mt-1">Gunakan checkbox untuk mengaktifkan atau menonaktifkan menu untuk sekolah terpilih.</p>
            </div>
            
            <!-- Quick Actions -->
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light btn-sm text-muted rounded-3 px-2.5 py-1.5 fs-8 fw-semibold" @click="checkAll">
                    <i class="bi bi-check-all me-1"></i> Pilih Semua
                </button>
                <button type="button" class="btn btn-light btn-sm text-muted rounded-3 px-2.5 py-1.5 fs-8 fw-semibold" @click="uncheckAll">
                    <i class="bi bi-x me-1"></i> Kosongkan Semua
                </button>
            </div>
        </div>

        <!-- Matriks Tampilan Tabel -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-4">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Menu / Fitur Sidebar</th>
                        <th>Endpoint URL / Path</th>
                        <th style="width: 150px;">Ikon</th>
                        <th class="text-center" style="width: 150px;">Akses Sekolah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(menu, idx) in formattedMenus" :key="menu.id" :style="menu.rowStyle">
                        <td class="text-muted">{{ idx + 1 }}</td>
                        <td>
                            <!-- Indented Tree Visualizer -->
                            <span v-if="menu.parent_id" class="text-muted ms-3 me-2">└──</span>
                            <span :class="menu.textClass">
                                <i :class="menu.iconClass"></i> {{ menu.nama_menu }}
                            </span>
                        </td>
                        <td class="font-monospace fs-8 text-muted">
                            {{ menu.url && menu.url !== '#' ? menu.url : '-' }}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border" v-if="menu.icon">
                                <i :class="menu.icon + ' me-1.5 text-primary'"></i>{{ menu.icon }}
                            </span>
                            <span class="text-muted fs-8" v-else>-</span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input 
                                    class="form-check-input border-secondary cursor-pointer" 
                                    type="checkbox" 
                                    :value="menu.id" 
                                    v-model="checkedMenuIds"
                                    @change="handleCheckboxChange(menu)"
                                >
                            </div>
                        </td>
                    </tr>
                    <tr v-if="formattedMenus.length === 0">
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i> Tidak ada data menu yang tersedia.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Submit Button -->
        <div class="d-flex justify-content-end gap-2 border-top pt-4">
            <button 
                type="button" 
                class="btn btn-primary rounded-3 px-4 py-2.5 fw-semibold d-flex align-items-center gap-2"
                @click="saveAccess"
                :disabled="isSaving"
            >
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="isSaving"></span>
                <i class="bi bi-shield-fill-check" v-else></i>
                Simpan Akses Fitur Sekolah
            </button>
        </div>
    </div>

    <!-- Empty State (Jika belum pilih sekolah) -->
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center mb-4" v-else>
        <div class="py-4">
            <div class="bg-primary-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; font-size: 2.25rem; color: #084298;">
                <i class="bi bi-building-lock"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Sekolah Belum Dipilih</h5>
            <p class="text-muted fs-7 mx-auto" style="max-width: 480px;">Silakan pilih salah satu instansi sekolah (tenant) pada dropdown di atas untuk mengelola ketersediaan fitur menu sidebarnya.</p>
        </div>
    </div>

</div>

<script>
{
    window.VueAppRegistry.register('#tenantMenusApp', {
        data() {
            return {
                title: 'Akses Fitur Sekolah (Tenant)',
                tenants: [],
                menus: [],
                checkedMenuIds: [],
                selectedTenantId: '',
                isLoading: false,
                isSaving: false
            };
        },
        computed: {
            selectedTenant() {
                return this.tenants.find(t => t.id === this.selectedTenantId) || null;
            },
            formattedMenus() {
                // Urutkan menu agar Parent dan Children-nya mengelompok berurutan
                const parents = this.menus.filter(m => m.parent_id === null);
                const result = [];
                
                parents.forEach(parent => {
                    result.push({
                        ...parent,
                        rowStyle: 'font-weight: 600; background-color: #f8fafc;',
                        textClass: 'text-dark fw-bold',
                        iconClass: (parent.icon || 'bi bi-folder-fill') + ' text-primary me-2'
                    });
                    
                    const children = this.menus.filter(m => m.parent_id === parent.id);
                    children.forEach(child => {
                        result.push({
                            ...child,
                            rowStyle: 'background-color: #fafbfc;',
                            textClass: 'fw-normal text-muted fs-7',
                            iconClass: (child.icon || 'bi bi-circle') + ' me-1'
                        });
                    });
                });
                
                return result;
            }
        },
        methods: {
            fetchTenants() {
                this.isLoading = true;
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/super-admin/tenant-menus/fetch')
                .then(response => {
                    this.isLoading = false;
                    const resData = response.data;
                    if (resData && (resData.success || resData.data)) {
                        const payload = resData.data || resData;
                        this.tenants = payload.tenants || resData.tenants || [];
                        this.menus = payload.menus || resData.menus || [];
                    }
                })
                .catch(error => {
                    this.isLoading = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal mengambil data',
                        text: 'Terjadi kesalahan saat memuat data sekolah dari server.'
                    });
                });
            },
            fetchTenantData() {
                if (!this.selectedTenantId) {
                    this.checkedMenuIds = [];
                    return;
                }

                this.isLoading = true;
                axios.get('<?= $this->getBaseUrl() ?>/api/v1/super-admin/tenant-menus/fetch', {
                    params: { tenant_id: this.selectedTenantId }
                })
                .then(response => {
                    this.isLoading = false;
                    const resData = response.data;
                    if (resData && (resData.success || resData.data)) {
                        const payload = resData.data || resData;
                        this.checkedMenuIds = payload.checkedMenuIds || payload.active_menu_ids || resData.checkedMenuIds || [];
                    }
                })
                .catch(error => {
                    this.isLoading = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal mengambil data',
                        text: 'Terjadi kesalahan saat mengambil pemetaan menu sekolah.'
                    });
                });
            },
            handleCheckboxChange(menu) {
                // Logika: Jika parent di-uncheck, uncheck semua anak-anaknya
                const isChecked = this.checkedMenuIds.includes(menu.id);
                
                if (menu.parent_id === null && !isChecked) {
                    // Temukan anak-anaknya
                    const children = this.menus.filter(m => m.parent_id === menu.id);
                    children.forEach(child => {
                        const index = this.checkedMenuIds.indexOf(child.id);
                        if (index > -1) {
                            this.checkedMenuIds.splice(index, 1);
                        }
                    });
                }
                // Logika: Jika anak di-check, pastikan parent-nya ikut di-check
                if (menu.parent_id !== null && isChecked) {
                    if (!this.checkedMenuIds.includes(menu.parent_id)) {
                        this.checkedMenuIds.push(menu.parent_id);
                    }
                }
            },
            checkAll() {
                this.checkedMenuIds = this.menus.map(m => m.id);
            },
            uncheckAll() {
                this.checkedMenuIds = [];
            },
            saveAccess() {
                if (!this.selectedTenantId) return;

                this.isSaving = true;
                axios.post('<?= $this->getBaseUrl() ?>/api/v1/super-admin/tenant-menus/save', {
                    tenant_id: this.selectedTenantId,
                    menu_ids: this.checkedMenuIds
                })
                .then(response => {
                    this.isSaving = false;
                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.data.message || 'Akses menu berhasil disimpan.',
                            confirmButtonColor: '#2563eb'
                        });
                    }
                })
                .catch(error => {
                    this.isSaving = false;
                    const errorMsg = error.response && error.response.data.error 
                        ? error.response.data.error 
                        : 'Terjadi kesalahan sistem saat menyimpan akses fitur.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: errorMsg
                    });
                });
            }
        },
        mounted() {
            this.fetchTenants();
        }
    });
}
</script>
