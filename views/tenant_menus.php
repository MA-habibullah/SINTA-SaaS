<?php
/**
 * View: Kelola Akses Menu Per Tenant (Child View)
 * Dipanggil oleh SuperAdminController dan dimuat di dalam layout/master.php
 */
?>
<!-- Area Konten Utama Terbungkus Vue.js App -->
<div id="tenantMenusApp">

    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h2 class="fw-bold text-dark mb-1">{{ title }}</h2>
            <p class="text-muted fs-7">Atur ketersediaan menu sidebar dan akses modul fitur bagi masing-masing sekolah (Tenant) secara terpusat.</p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="/SINTA-SaaS/dashboard" class="btn btn-outline-secondary btn-sm d-flex align-items-center rounded-3 px-3 py-2 fs-7">
                <i class="bi bi-arrow-left me-2"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Dropdown Pemilihan Tenant (Sekolah) -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <label for="tenantSelect" class="form-label fw-bold text-dark mb-2">
                    <i class="bi bi-buildings text-primary me-2"></i>Pilih Instansi Sekolah (Tenant)
                </label>
                <select 
                    id="tenantSelect" 
                    class="form-select rounded-3 py-2" 
                    v-model="selectedTenantId" 
                    @change="fetchTenantData"
                    :disabled="isLoading"
                >
                    <option value="">-- Pilih Sekolah / Tenant --</option>
                    <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                        {{ tenant.nama_sekolah }} (NPSN: {{ tenant.npsn }} - Subdomain: {{ tenant.subdomain }})
                    </option>
                </select>
            </div>
            <div class="col-12 col-md-6 mt-3 mt-md-0 d-flex justify-content-md-end">
                <span class="badge bg-primary-subtle px-3 py-2 rounded-pill fs-7" style="color: #084298;" v-if="selectedTenant">
                    <i class="bi bi-info-circle me-1"></i> Mengedit Fitur: {{ selectedTenant.nama_sekolah }}
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
                axios.get('/SINTA-SaaS/api/v1/super-admin/tenant-menus/fetch')
                .then(response => {
                    this.isLoading = false;
                    if (response.data.success) {
                        this.tenants = response.data.tenants || [];
                        this.menus = response.data.menus || [];
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
                axios.get('/SINTA-SaaS/api/v1/super-admin/tenant-menus/fetch', {
                    params: { tenant_id: this.selectedTenantId }
                })
                .then(response => {
                    this.isLoading = false;
                    if (response.data.success) {
                        this.checkedMenuIds = response.data.checkedMenuIds || [];
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
                axios.post('/SINTA-SaaS/api/v1/super-admin/tenant-menus/save', {
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
