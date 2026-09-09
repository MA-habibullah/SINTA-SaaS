<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
    tenantsList: {
        type: Array,
        default: () => []
    },
    tenants: {
        type: Array,
        default: () => []
    },
    menuList: {
        type: Array,
        default: () => []
    },
    menus: {
        type: Array,
        default: () => []
    },
    selectedTenantId: {
        type: String,
        default: ''
    },
    checkedMenuIds: {
        type: Array,
        default: () => []
    }
})

// Normalized Props Data
const tenantsData = computed(() => props.tenantsList.length > 0 ? props.tenantsList : (props.tenants || []))
const menusData = computed(() => props.menuList.length > 0 ? props.menuList : (props.menus || []))

// State
const selectedTenantId = ref(props.selectedTenantId || (tenantsData.value.length > 0 ? tenantsData.value[0].id : ''))
const checkedMenuIds = ref([...props.checkedMenuIds])
const searchQuery = ref('')
const isLoading = ref(false)
const isSaving = ref(false)

// Toast / Modal Notification Helper
const notify = (icon, title, text) => {
    if (typeof window !== 'undefined' && window.Swal) {
        window.Swal.fire({
            icon,
            title,
            text,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        })
    }
}

const showModal = (icon, title, text) => {
    if (typeof window !== 'undefined' && window.Swal) {
        window.Swal.fire({
            icon,
            title,
            text,
            confirmButtonColor: '#2563eb'
        })
    } else {
        alert(`${title}: ${text}`)
    }
}

// Computed: Active selected tenant object
const selectedTenant = computed(() => {
    return tenantsData.value.find(t => t.id === selectedTenantId.value) || null
})

// Computed: Formatted & Hierarchically Ordered Menus
const formattedMenus = computed(() => {
    const parents = menusData.value.filter(m => !m.parent_id)
    const result = []
    
    parents.forEach(parent => {
        result.push({
            ...parent,
            isParent: true,
            rowStyle: 'font-semibold bg-slate-50/80',
            textClass: 'text-slate-800 font-bold',
            iconClass: (parent.icon || 'bi bi-folder-fill') + ' text-indigo-600 me-2'
        })
        
        const children = menusData.value.filter(m => m.parent_id === parent.id)
        children.forEach(child => {
            result.push({
                ...child,
                isParent: false,
                rowStyle: 'bg-white hover:bg-slate-50/50',
                textClass: 'text-slate-600 font-medium',
                iconClass: (child.icon || 'bi bi-circle') + ' text-slate-400 me-1.5'
            })
        })
    })

    if (!searchQuery.value.trim()) {
        return result
    }

    const query = searchQuery.value.toLowerCase()
    return result.filter(m => 
        m.nama_menu.toLowerCase().includes(query) || 
        (m.url && m.url.toLowerCase().includes(query))
    )
})

// Check if menu is checked
const isChecked = (menuId) => {
    return checkedMenuIds.value.includes(menuId)
}

// Fetch tenant mapping when tenant dropdown changes
const handleTenantChange = async () => {
    if (!selectedTenantId.value) {
        checkedMenuIds.value = []
        return
    }

    isLoading.value = true
    try {
        const response = await axios.get('/super-admin/tenant-menus/fetch', {
            params: { tenant_id: selectedTenantId.value }
        })
        
        if (response.data && response.data.success) {
            checkedMenuIds.value = response.data.checkedMenuIds || []
        }
    } catch (err) {
        console.error('Failed to fetch tenant menu access:', err)
        showModal('error', 'Gagal Mengambil Data', 'Terjadi kesalahan saat memuat pemetaan menu sekolah.')
    } finally {
        isLoading.value = false
    }
}

// Smart Checkbox Handler (Parent-Child Cascade Logic)
const handleCheckboxChange = (menu) => {
    const checked = checkedMenuIds.value.includes(menu.id)
    
    if (!menu.parent_id) {
        // Jika parent di-uncheck, uncheck seluruh children-nya
        const children = menusData.value.filter(m => m.parent_id === menu.id)
        if (!checked) {
            children.forEach(child => {
                const idx = checkedMenuIds.value.indexOf(child.id)
                if (idx > -1) {
                    checkedMenuIds.value.splice(idx, 1)
                }
            })
        }
    } else {
        // Jika child di-check, pastikan parent-nya ikut tercentang
        if (checked && !checkedMenuIds.value.includes(menu.parent_id)) {
            checkedMenuIds.value.push(menu.parent_id)
        }
    }
}

// Check all menus
const checkAll = () => {
    checkedMenuIds.value = menusData.value.map(m => m.id)
}

// Uncheck all menus
const uncheckAll = () => {
    checkedMenuIds.value = []
}

// Save access changes to server
const saveAccess = async () => {
    if (!selectedTenantId.value) {
        showModal('warning', 'Peringatan', 'Silakan pilih instansi sekolah (tenant) terlebih dahulu.')
        return
    }

    isSaving.value = true
    try {
        const response = await axios.post('/super-admin/tenant-menus/save', {
            tenant_id: selectedTenantId.value,
            menu_ids: checkedMenuIds.value
        })

        if (response.data && response.data.success) {
            notify('success', 'Berhasil Disimpan', response.data.message || 'Akses menu sekolah berhasil diperbarui.')
        }
    } catch (err) {
        console.error('Failed to save tenant menu access:', err)
        const errorMsg = (err.response && err.response.data && (err.response.data.error || err.response.data.message)) || err.message || 'Terjadi kesalahan sistem saat menyimpan akses fitur.'
        showModal('error', 'Gagal Menyimpan', errorMsg)
    } finally {
        isSaving.value = false
    }
}
</script>

<template>
    <AppLayout title="Akses Fitur Sekolah (Tenant)">
        <div class="space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Akses Fitur Sekolah (Tenant)</h1>
                            <p class="text-xs text-slate-500 mb-0">Atur ketersediaan menu sidebar dan akses modul fitur bagi masing-masing sekolah (Tenant) secara terpusat.</p>
                        </div>
                    </div>
                </div>
                <div>
                    <Link :href="'/dashboard'" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-xs flex items-center gap-2 transition shadow-2xs">
                        <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                    </Link>
                </div>
            </div>

            <!-- Card 1: Dropdown Pemilihan Tenant (Sekolah) -->
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200/80">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="w-full md:w-2/3">
                        <label for="tenantSelect" class="block text-xs font-bold text-slate-700 mb-2">
                            <i class="bi bi-buildings text-indigo-600 me-2"></i>Pilih Instansi Sekolah (Tenant)
                        </label>
                        <select 
                            id="tenantSelect" 
                            v-model="selectedTenantId" 
                            @change="handleTenantChange"
                            :disabled="isLoading"
                            class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                        >
                            <option value="">-- Pilih Sekolah / Tenant --</option>
                            <option v-for="t in tenantsData" :key="t.id" :value="t.id">
                                {{ t.nama_sekolah }} (NPSN: {{ t.npsn || '-' }} — Subdomain: {{ t.subdomain || '-' }})
                            </option>
                        </select>
                    </div>

                    <div v-if="selectedTenant" class="flex md:justify-end items-center gap-2">
                        <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 shadow-2xs">
                            <i class="bi bi-info-circle text-sm"></i>
                            <span>Mengedit: <strong>{{ selectedTenant.nama_sekolah }}</strong></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 shadow-2xs font-mono">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>{{ checkedMenuIds.length }} / {{ menusData.length }} Fitur Aktif</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Matriks Fitur & Menu Sidebar -->
            <div v-if="selectedTenantId" class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200/80">
                
                <!-- Card Header with Search & Quick Actions -->
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-0.5">
                            <i class="bi bi-grid-3x3-gap-fill text-indigo-600"></i> Matriks Fitur Aplikasi Sekolah
                        </h2>
                        <p class="text-xs text-slate-500 mb-0">Centang atau hilangkan centang untuk mengaktifkan atau menonaktifkan modul menu untuk sekolah terpilih.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                        <!-- Search Box -->
                        <div class="relative grow sm:grow-0">
                            <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                            <input 
                                type="text" 
                                v-model="searchQuery" 
                                placeholder="Cari nama menu..." 
                                class="h-9 pl-8 pr-3 w-full sm:w-48 rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                            />
                        </div>

                        <!-- Quick Actions -->
                        <button type="button" @click="checkAll" class="px-3 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5">
                            <i class="bi bi-check-all text-sm text-emerald-600"></i> Pilih Semua
                        </button>
                        <button type="button" @click="uncheckAll" class="px-3 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5">
                            <i class="bi bi-x-lg text-xs text-rose-600"></i> Kosongkan
                        </button>
                    </div>
                </div>

                <!-- Matriks Table -->
                <div class="overflow-x-auto border border-slate-200/80 rounded-2xl mb-6">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200/80">
                            <tr class="text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                                <th scope="col" class="py-3 px-4 w-16 text-center">No</th>
                                <th scope="col" class="py-3 px-4">Nama Menu / Fitur Sidebar</th>
                                <th scope="col" class="py-3 px-4 w-64">Endpoint URL / Path</th>
                                <th scope="col" class="py-3 px-4 w-36">Ikon</th>
                                <th scope="col" class="py-3 px-4 text-center w-36">Akses Sekolah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="isLoading">
                                <td colspan="5" class="text-center py-10 text-slate-400">
                                    <div class="inline-block w-5 h-5 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin mb-2"></div>
                                    <p class="text-xs font-semibold mb-0">Memuat matriks akses sekolah...</p>
                                </td>
                            </tr>
                            <tr v-else-if="formattedMenus.length === 0">
                                <td colspan="5" class="text-center py-10 text-slate-400">
                                    <i class="bi bi-inbox text-4xl block mb-2 text-slate-300"></i>
                                    <span class="text-xs font-medium">Tidak ada data menu yang sesuai.</span>
                                </td>
                            </tr>
                            <tr v-else v-for="(menu, idx) in formattedMenus" :key="menu.id" :class="[menu.rowStyle, 'transition']">
                                <td class="py-3 px-4 text-center text-slate-400 font-mono text-[11px]">{{ idx + 1 }}</td>
                                
                                <!-- Menu Name & Tree Indicator -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <span v-if="menu.parent_id" class="text-slate-300 ms-3 me-2 font-mono text-xs">└──</span>
                                        <span :class="menu.textClass" class="flex items-center">
                                            <i :class="menu.iconClass"></i> {{ menu.nama_menu }}
                                        </span>
                                    </div>
                                </td>

                                <!-- URL Path -->
                                <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">
                                    <span v-if="menu.url && menu.url !== '#'" class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[10px]">
                                        {{ menu.url }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>

                                <!-- Icon -->
                                <td class="py-3 px-4">
                                    <span v-if="menu.icon" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-mono bg-slate-50 text-slate-600 border border-slate-200/60 shadow-2xs">
                                        <i :class="menu.icon + ' text-indigo-600'"></i>
                                        <span>{{ menu.icon }}</span>
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>

                                <!-- Checkbox Toggle -->
                                <td class="py-3 px-4 text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            :value="menu.id" 
                                            v-model="checkedMenuIds"
                                            @change="handleCheckboxChange(menu)"
                                            class="sr-only peer"
                                        >
                                        <div :class="[
                                            'w-9 h-5 rounded-full transition-all relative after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all',
                                            isChecked(menu.id) ? 'bg-indigo-600 after:translate-x-full after:border-white' : 'bg-slate-200'
                                        ]"></div>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Action -->
                <div class="flex justify-between items-center pt-2">
                    <div class="text-xs font-semibold text-slate-500">
                        Total <strong class="text-slate-800 font-mono">{{ checkedMenuIds.length }}</strong> dari <strong class="text-slate-800 font-mono">{{ menusData.length }}</strong> fitur aktif untuk sekolah ini.
                    </div>
                    <button 
                        type="button" 
                        @click="saveAccess" 
                        :disabled="isSaving || isLoading" 
                        class="h-11 px-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/10 hover:shadow-indigo-600/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="isSaving" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <i v-else class="bi bi-shield-fill-check text-base"></i>
                        {{ isSaving ? 'Menyimpan Akses Fitur...' : 'Simpan Akses Fitur Sekolah' }}
                    </button>
                </div>

            </div>

            <!-- Card 3: Empty State (Jika belum ada tenant dipilih) -->
            <div v-else class="bg-white rounded-3xl p-12 text-center shadow-xs border border-slate-200/80">
                <div class="w-16 h-16 rounded-3xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
                    <i class="bi bi-building-lock"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800 mb-1">Sekolah Belum Dipilih</h2>
                <p class="text-xs text-slate-500 max-w-md mx-auto mb-0">Silakan pilih salah satu instansi sekolah (tenant) pada dropdown di atas untuk mengelola ketersediaan fitur menu sidebarnya.</p>
            </div>

        </div>
    </AppLayout>
</template>
