<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js'

const props = defineProps({
  roles: {
    type: Array,
    default: null
  },
  menuList: {
    type: Array,
    default: null
  },
  accessMap: {
    type: Object,
    default: null
  },
  tenantsList: {
    type: Array,
    default: null
  },
  selectedTenantId: {
    type: String,
    default: '00000000-0000-0000-0000-000000000000'
  },
  selectedTenant: {
    type: Object,
    default: null
  },
  userRole: {
    type: [String, Object],
    default: 'admin_sekolah'
  },
  isSuperAdmin: {
    type: Boolean,
    default: false
  },
  flash: {
    type: Object,
    default: () => ({})
  }
})

const page = usePage()

// Client reactive state for Zero-SSR Pure Client Hydration
const localRoles = ref(props.roles || [])
const localMenuList = ref(props.menuList || [])
const localTenantsList = ref(props.tenantsList || [])
const targetTenant = ref(props.selectedTenantId || '00000000-0000-0000-0000-000000000000')
const isLoading = ref(!props.roles)
const isSubmitting = ref(false)

// Search & Filter
const searchQuery = ref('')
const selectedRoleFilter = ref('all')

// Matrix access state: { 'roleId___menuId': boolean }
const matrix = reactive({})

// Memory security: automatic garbage collection on unmount
useMemorySecurity([matrix, localRoles, localMenuList, localTenantsList])

// Toast
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

// Format helper
const formatRoleName = (name) => {
  if (!name) return ''
  return name.replace(/_/g, ' ').toUpperCase()
}

// SearchableSelect Options for Tenants
const tenantOptions = computed(() => {
  const list = [
    {
      id: '00000000-0000-0000-0000-000000000000',
      nama: '⭐ — Global Default Template (Seluruh Sekolah) —',
      subLabel: 'Template Hak Akses Bawaan Global'
    }
  ]
  if (localTenantsList.value && localTenantsList.value.length > 0) {
    localTenantsList.value.forEach(t => {
      list.push({
        id: t.id,
        nama: t.nama_sekolah,
        subLabel: t.npsn ? `NPSN: ${t.npsn} | ${t.bentuk_pendidikan || '-'}` : (t.bentuk_pendidikan || '')
      })
    })
  }
  return list
})

// SearchableSelect Options for Role Filter
const roleFilterOptions = computed(() => {
  const options = [
    { id: 'all', nama: `Semua Peran (${localRoles.value.length} Role)`, subLabel: 'Tampilkan semua kolom role' }
  ]
  localRoles.value.forEach(r => {
    options.push({
      id: r.id,
      nama: formatRoleName(r.nama_role),
      subLabel: r.deskripsi || ''
    })
  })
  return options
})

// Super Admin computed check
const isSuperAdminComputed = computed(() => {
  if (props.isSuperAdmin) return true
  const roleName = typeof props.userRole === 'object' ? props.userRole?.nama_role : props.userRole
  return roleName === 'super_admin' || (localTenantsList.value && localTenantsList.value.length > 0)
})

// Filtered roles based on dropdown filter
const displayedRoles = computed(() => {
  if (selectedRoleFilter.value === 'all') {
    return localRoles.value
  }
  return localRoles.value.filter(r => r.id === selectedRoleFilter.value)
})

// Filtered menus based on search
const filteredMenus = computed(() => {
  const rawList = localMenuList.value
  if (!searchQuery.value.trim()) {
    return rawList
  }
  const q = searchQuery.value.toLowerCase().trim()
  
  // Find matching menu IDs
  const matchingIds = new Set()
  rawList.forEach(m => {
    if (
      (m.nama_menu && m.nama_menu.toLowerCase().includes(q)) ||
      (m.url && m.url.toLowerCase().includes(q))
    ) {
      matchingIds.add(m.id)
      if (m.parent_id) matchingIds.add(m.parent_id) // include parent
    }
  })

  // Return menus where item itself or parent matches
  return rawList.filter(m => matchingIds.has(m.id))
})

// Initialize matrix from mapData
const initMatrix = (mapData) => {
  // Clear old keys
  Object.keys(matrix).forEach(key => delete matrix[key])
  
  if (mapData) {
    Object.keys(mapData).forEach(key => {
      matrix[key] = !!mapData[key]
    })
  }
}

// On-Demand Client Fetch (Zero-SSR Data Hydration)
const loadMatrixDataAsync = async (tenantId = null) => {
  isLoading.value = true
  try {
    const activeTenantId = tenantId !== null ? tenantId : targetTenant.value
    const params = {
      async: 1,
      tenant_id: activeTenantId
    }
    const res = await axios.get('/konfigurasi/akses', { params })
    if (res.data && res.data.success) {
      localRoles.value = res.data.roles || []
      localMenuList.value = res.data.menus || res.data.menu_list || []
      if (res.data.tenants && res.data.tenants.length > 0) {
        localTenantsList.value = res.data.tenants
      }
      targetTenant.value = res.data.target_tenant_id || activeTenantId
      initMatrix(res.data.access_map || {})
    }
  } catch (err) {
    console.error('Gagal memuat matriks hak akses:', err)
    triggerToast('Gagal memuat konfigurasi hak akses dari server.', 'error')
  } finally {
    isLoading.value = false
  }
}

// In-Memory Target Tenant change handler
const handleTenantChange = (newTenantId) => {
  targetTenant.value = newTenantId || '00000000-0000-0000-0000-000000000000'
  loadMatrixDataAsync(targetTenant.value)
}

// Checkbox change handler with Multi-Level (3-Tier) Cascading logic
const handleCheckboxChange = (roleId, menu) => {
  const key = `${roleId}___${menu.id}`
  const isChecked = !!matrix[key]

  if (!isChecked) {
    // If UNCHECKED -> Auto-uncheck all descendants (children and sub-tabs)
    const uncheckDescendants = (parentId) => {
      localMenuList.value.forEach(m => {
        if (m.parent_id === parentId) {
          matrix[`${roleId}___${m.id}`] = false
          uncheckDescendants(m.id)
        }
      })
    }
    uncheckDescendants(menu.id)
  } else {
    // If CHECKED -> Auto-check all ancestors (parent page and category)
    let currParentId = menu.parent_id
    while (currParentId) {
      matrix[`${roleId}___${currParentId}`] = true
      const parentMenu = localMenuList.value.find(m => m.id === currParentId)
      currParentId = parentMenu ? parentMenu.parent_id : null
    }
  }
}

// Bulk toggle for a specific role
const toggleAllForRole = (roleId, grantAll = true) => {
  localMenuList.value.forEach(m => {
    matrix[`${roleId}___${m.id}`] = grantAll
  })
}

// Watch for prop changes
watch(() => props.accessMap, (newMap) => {
  if (newMap) initMatrix(newMap)
}, { deep: true })

onMounted(() => {
  if (!props.roles || props.roles.length === 0) {
    loadMatrixDataAsync()
  } else {
    initMatrix(props.accessMap)
    isLoading.value = false
  }
})

// Save Matrix (In-Memory Axios Submission)
const saveMatrix = async () => {
  isSubmitting.value = true

  // Construct access payload: { roleId: [menuId1, menuId2, ...] }
  const accessPayload = {}
  localRoles.value.forEach(r => {
    accessPayload[r.id] = []
  })

  Object.keys(matrix).forEach(key => {
    if (matrix[key]) {
      const parts = key.split('___')
      if (parts.length === 2) {
        const [roleId, menuId] = parts
        if (!accessPayload[roleId]) {
          accessPayload[roleId] = []
        }
        accessPayload[roleId].push(menuId)
      }
    }
  })

  try {
    const res = await axios.post('/konfigurasi/akses', {
      target_tenant_id: targetTenant.value,
      access: accessPayload
    }, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (res.data && res.data.success) {
      triggerToast(res.data.message || 'Matriks hak akses menu berhasil disimpan & diterapkan secara real-time!', 'success')
    } else {
      triggerToast('Matriks hak akses berhasil diperbarui.', 'success')
    }
  } catch (err) {
    console.error('Gagal menyimpan matriks hak akses:', err)
    triggerToast(err.response?.data?.message || 'Gagal menyimpan matriks hak akses.', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Reset Default Template state & handler
const isResetModalOpen = ref(false)
const isResetting = ref(false)

const openResetModal = () => {
  isResetModalOpen.value = true
}

const submitResetDefault = async () => {
  isResetting.value = true
  try {
    const res = await axios.post('/konfigurasi/akses/reset-default', {
      target_tenant_id: targetTenant.value
    }, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    if (res.data && res.data.success) {
      triggerToast(res.data.message || 'Hak akses berhasil dikembalikan ke Template Standar Global.', 'success')
      isResetModalOpen.value = false
      await loadMatrixDataAsync(targetTenant.value)
    }
  } catch (err) {
    console.error('Gagal mereset ke template global:', err)
    triggerToast(err.response?.data?.message || 'Gagal mereset ke template standar.', 'error')
  } finally {
    isResetting.value = false
  }
}

// Clone Role Permissions state & handler
const isCloneModalOpen = ref(false)
const isCloning = ref(false)
const cloneSourceRole = ref('')
const cloneTargetRole = ref('')

const openCloneModal = () => {
  cloneSourceRole.value = ''
  cloneTargetRole.value = ''
  isCloneModalOpen.value = true
}

const sourceRoleOptions = computed(() => {
  return localRoles.value.map(r => ({
    id: r.id,
    nama: formatRoleName(r.nama_role),
    subLabel: r.deskripsi || ''
  }))
})

const targetRoleOptions = computed(() => {
  return localRoles.value
    .filter(r => r.id !== cloneSourceRole.value)
    .map(r => ({
      id: r.id,
      nama: formatRoleName(r.nama_role),
      subLabel: r.deskripsi || ''
    }))
})

const submitCloneRole = async () => {
  if (!cloneSourceRole.value || !cloneTargetRole.value) {
    triggerToast('Pilih role asal dan role target terlebih dahulu.', 'error')
    return
  }
  isCloning.value = true
  try {
    const res = await axios.post('/konfigurasi/akses/clone-role', {
      source_role_id: cloneSourceRole.value,
      target_role_id: cloneTargetRole.value,
      target_tenant_id: targetTenant.value
    }, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    if (res.data && res.data.success) {
      triggerToast(res.data.message || 'Hak akses role berhasil disalin.', 'success')
      isCloneModalOpen.value = false
      await loadMatrixDataAsync(targetTenant.value)
    }
  } catch (err) {
    console.error('Gagal menyalin hak akses role:', err)
    triggerToast(err.response?.data?.message || 'Gagal menyalin hak akses role.', 'error')
  } finally {
    isCloning.value = false
  }
}

const triggerToast = (msg, type = 'success') => {
  toastMessage.value = msg
  toastType.value = type
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 4000)
}
</script>

<template>
  <AppLayout title="Manajemen User & Hak Akses">
    <div class="space-y-6 pb-12">
      
      <!-- Toast Notification -->
      <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div 
          v-if="showToast"
          class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-sm font-semibold backdrop-blur-md"
          :class="toastType === 'success' ? 'bg-emerald-600/95 text-white border-emerald-500 shadow-emerald-500/20' : 'bg-red-600/95 text-white border-red-500 shadow-red-500/20'"
        >
          <i class="bi" :class="toastType === 'success' ? 'bi-check-circle-fill text-lg' : 'bi-exclamation-triangle-fill text-lg'"></i>
          <span>{{ toastMessage }}</span>
          <button @click="showToast = false" class="text-white/80 hover:text-white ml-2 text-xs cursor-pointer">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </transition>

      <!-- Page Header & Action Toolbar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Manajemen User & Hak Akses</h1>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-700 font-mono">RBAC Matrix</span>
          </div>
          <p class="text-xs text-slate-500 mt-1">
            Atur visibilitas menu sidebar, sub-menu, dan navtab modul untuk masing-masing peran pengguna secara real-time.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Reset to Default Template Button -->
          <button 
            type="button" 
            @click="openResetModal" 
            :disabled="isLoading || isSubmitting"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl border border-slate-300/80 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
            title="Kembalikan konfigurasi hak akses sekolah ke Template Standar Global"
          >
            <i class="bi bi-arrow-counterclockwise text-slate-600"></i>
            <span>Reset ke Template Standar</span>
          </button>

          <!-- Clone Role Button -->
          <button 
            type="button" 
            @click="openCloneModal" 
            :disabled="isLoading || isSubmitting"
            class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold rounded-xl transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
            title="Salin seluruh hak akses dari satu role ke role lain"
          >
            <i class="bi bi-copy text-indigo-600"></i>
            <span>Salin Hak Akses Role</span>
          </button>

          <!-- Save Button -->
          <button 
            type="button" 
            @click="saveMatrix" 
            :disabled="isSubmitting || isLoading"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <span v-if="isSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <i v-else class="bi bi-floppy-fill"></i>
            <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Hak Akses' }}</span>
          </button>
        </div>
      </div>

      <!-- Modal: Reset Default Confirmation -->
      <div v-if="isResetModalOpen" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-200 space-y-4">
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl font-bold">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-800">Kembalikan ke Template Standar?</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
              Tindakan ini akan menghapus kustomisasi matriks hak akses mandiri sekolah dan mengembalikannya ke template bawaan master global platform.
            </p>
          </div>
          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button 
              type="button" 
              @click="isResetModalOpen = false" 
              class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
            >
              Batal
            </button>
            <button 
              type="button" 
              @click="submitResetDefault" 
              :disabled="isResetting"
              class="px-4 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm transition flex items-center gap-1.5"
            >
              <span v-if="isResetting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>{{ isResetting ? 'Mereset...' : 'Ya, Reset Sekarang' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Modal: Clone Role Permissions -->
      <div v-if="isCloneModalOpen" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-slate-200 space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-copy"></i>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-800">Salin Hak Akses Antar-Role</h3>
                <p class="text-xs text-slate-500">Duplikasi seluruh izin menu dan tab dari satu role ke role target</p>
              </div>
            </div>
            <button @click="isCloneModalOpen = false" class="text-slate-400 hover:text-slate-600 text-sm">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="space-y-3.5 pt-2">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">1. Pilih Role Asal (Sumber Izin):</label>
              <SearchableSelect 
                v-model="cloneSourceRole"
                :options="sourceRoleOptions"
                placeholder="-- Pilih Role Sumber --"
                search-placeholder="Cari role asal..."
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">2. Pilih Role Tujuan (Target Penerima):</label>
              <SearchableSelect 
                v-model="cloneTargetRole"
                :options="targetRoleOptions"
                placeholder="-- Pilih Role Tujuan --"
                search-placeholder="Cari role target..."
              />
            </div>

            <div class="bg-indigo-50/70 border border-indigo-200/70 rounded-2xl p-3 text-[11px] text-indigo-900 leading-relaxed">
              <i class="bi bi-info-circle-fill text-indigo-600 me-1"></i>
              Seluruh izin menu dan navtab yang dimiliki oleh role asal akan disalin dan menggantikan izin role tujuan.
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="isCloneModalOpen = false" 
              class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
            >
              Batal
            </button>
            <button 
              type="button" 
              @click="submitCloneRole" 
              :disabled="isCloning || !cloneSourceRole || !cloneTargetRole"
              class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition flex items-center gap-1.5 disabled:opacity-50"
            >
              <span v-if="isCloning" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>{{ isCloning ? 'Menyalin...' : 'Salin Hak Akses' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Super Admin Target Tenant Switcher (SearchableSelect with Live Search) -->
      <div v-if="isSuperAdminComputed && localTenantsList && localTenantsList.length > 0" class="bg-white rounded-3xl p-5 shadow-2xs border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0">
            <i class="bi bi-buildings"></i>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h4 class="text-sm font-bold text-slate-800">Target Otorisasi Lembaga / Sekolah</h4>
              <span v-if="isLoading" class="text-[11px] text-blue-600 font-semibold flex items-center gap-1">
                <span class="w-3 h-3 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                Memuat...
              </span>
            </div>
            <p class="text-xs text-slate-500">
              Pilih sekolah untuk kustomisasi hak akses spesifik, atau gunakan <em>Global Default</em> untuk semua sekolah mitra.
            </p>
          </div>
        </div>

        <div class="w-full md:w-96 shrink-0">
          <SearchableSelect
            v-model="targetTenant"
            :options="tenantOptions"
            placeholder="-- Pilih Target Otorisasi Sekolah --"
            search-placeholder="Cari nama sekolah / NPSN..."
            @change="handleTenantChange"
            custom-class="w-full"
          />
        </div>
      </div>

      <!-- Quick Instruction Banner -->
      <div class="bg-blue-50/80 border border-blue-200/80 rounded-2xl p-4 flex items-start gap-3.5 text-blue-900 text-xs">
        <i class="bi bi-shield-lock-fill text-blue-600 text-lg shrink-0 mt-0.5"></i>
        <div class="space-y-1">
          <span class="font-bold block text-blue-950">Petunjuk Konfigurasi Matriks RBAC:</span>
          <p class="text-blue-800/90 leading-relaxed">
            Tandai (centang) kotak pada tabel di bawah untuk memberikan izin akses menu sidebar kepada peran terkait. 
            <strong>Hierarki Menu:</strong> Memberikan izin pada menu sub-fitur (anak) akan otomatis mengaktifkan menu induknya. Sebaliknya, menghapus centang pada menu induk akan otomatis mencabut izin semua sub-fitur di bawahnya.
          </p>
        </div>
      </div>

      <!-- Filter & Search Toolbar (Live Keyword Search & SearchableSelect Filter) -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <!-- Live Search input for menus -->
        <div class="relative w-full sm:w-80">
          <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Cari nama menu atau path URL..."
            class="w-full h-10 pl-9 pr-4 rounded-xl border border-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 focus:bg-white transition"
          />
        </div>

        <!-- Filter by Role dropdown using SearchableSelect -->
        <div class="flex items-center gap-2.5 w-full sm:w-auto">
          <span class="text-xs text-slate-500 font-bold whitespace-nowrap">Filter Role:</span>
          <div class="w-full sm:w-64">
            <SearchableSelect
              v-model="selectedRoleFilter"
              :options="roleFilterOptions"
              placeholder="-- Pilih Role --"
              search-placeholder="Cari role..."
              custom-class="w-full"
            />
          </div>
        </div>
      </div>

      <!-- Loading Skeleton View -->
      <div v-if="isLoading" class="space-y-4 animate-pulse">
        <div class="h-14 bg-slate-200 rounded-2xl"></div>
        <div class="h-96 bg-slate-200 rounded-3xl"></div>
      </div>

      <!-- RBAC Matrix Table Container -->
      <div v-else class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto" style="max-height: 680px;">
          <table class="w-full text-left text-xs text-slate-700 border-collapse">
            <!-- Table Header -->
            <thead class="bg-slate-100/90 text-slate-700 uppercase font-bold sticky top-0 z-20 border-b border-slate-200 backdrop-blur-xs">
              <tr>
                <th class="py-3.5 px-4 w-12 text-center bg-slate-100">No</th>
                <th class="py-3.5 px-4 min-w-[260px] bg-slate-100">Nama Menu / Fitur Sidebar</th>
                <th class="py-3.5 px-4 min-w-[180px] bg-slate-100">URL / Path</th>
                <th class="py-3.5 px-4 w-24 text-center bg-slate-100">Ikon</th>
                
                <!-- Dynamic Role Headers -->
                <th 
                  v-for="role in displayedRoles" 
                  :key="role.id" 
                  class="py-3.5 px-3 text-center min-w-[130px] border-l border-slate-200/80 bg-slate-100"
                >
                  <div class="flex flex-col items-center gap-1.5">
                    <span class="px-2 py-1 rounded-lg text-[10px] font-extrabold tracking-wider bg-slate-200/80 text-slate-800 uppercase">
                      {{ formatRoleName(role.nama_role) }}
                    </span>
                    <div class="flex items-center gap-1.5 text-[10px] text-slate-500 font-normal">
                      <button 
                        type="button" 
                        @click="toggleAllForRole(role.id, true)" 
                        class="hover:text-blue-600 underline font-semibold cursor-pointer"
                        title="Centang Semua Menu untuk Role Ini"
                      >Semua</button>
                      <span>|</span>
                      <button 
                        type="button" 
                        @click="toggleAllForRole(role.id, false)" 
                        class="hover:text-red-600 underline font-semibold cursor-pointer"
                        title="Cabut Semua Izin untuk Role Ini"
                      >Batal</button>
                    </div>
                  </div>
                </th>
              </tr>
            </thead>

            <!-- Table Body -->
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr 
                v-for="(menu, idx) in filteredMenus" 
                :key="menu.id"
                class="transition hover:bg-blue-50/40"
                :class="[
                  menu.level === 1 ? 'bg-slate-100/90 font-bold text-slate-900 border-t-2 border-slate-300/70' : '',
                  menu.level === 2 ? 'bg-white text-slate-800 font-medium' : '',
                  (menu.level === 3 || menu.is_tab) ? 'bg-indigo-50/20 text-slate-600 border-l-4 border-l-indigo-400' : ''
                ]"
              >
                <!-- Row Number -->
                <td class="py-3 px-4 text-center font-mono text-slate-400 text-[11px]">
                  {{ idx + 1 }}
                </td>

                <!-- Menu Name & Indentation (3 Levels) -->
                <td class="py-2.5 px-4" :class="[
                  menu.level === 1 ? 'pl-4' : '',
                  menu.level === 2 ? 'pl-8' : '',
                  (menu.level === 3 || menu.is_tab) ? 'pl-14' : ''
                ]">
                  <div class="flex items-center gap-2">
                    <!-- Level 3 / NavTab Menu -->
                    <template v-if="menu.level === 3 || menu.is_tab">
                      <span class="text-indigo-300 font-mono select-none text-xs">└──</span>
                      <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase tracking-wider">
                        <i class="bi bi-segmented-nav text-[9px]"></i> TAB
                      </span>
                      <i :class="menu.icon || 'bi bi-tag'" class="text-indigo-500 text-xs"></i>
                      <span class="text-xs font-semibold text-slate-700">{{ menu.nama_menu.replace(/^Tab:\s*/i, '') }}</span>
                    </template>

                    <!-- Level 2 / Page Menu -->
                    <template v-else-if="menu.level === 2 || menu.is_child">
                      <span class="text-slate-300 font-mono select-none">└──</span>
                      <i :class="menu.icon || 'bi bi-file-earmark-text'" class="text-blue-500 text-xs"></i>
                      <span class="text-xs font-bold text-slate-800">{{ menu.nama_menu }}</span>
                    </template>

                    <!-- Level 1 / Parent Category -->
                    <template v-else>
                      <i :class="menu.icon || 'bi bi-folder2-open'" class="text-slate-700 text-sm"></i>
                      <span class="text-xs font-black text-slate-900 uppercase tracking-tight">{{ menu.nama_menu }}</span>
                    </template>
                  </div>
                </td>

                <!-- URL Path -->
                <td class="py-2.5 px-4 font-mono text-[11px] text-slate-500">
                  <span v-if="menu.url && menu.url !== '#'" class="px-2 py-0.5 rounded-md border text-[11px]" :class="(menu.level === 3 || menu.is_tab) ? 'bg-indigo-50/60 text-indigo-600 border-indigo-200/60' : 'bg-slate-100 text-slate-600 border-slate-200/60'">
                    {{ menu.url }}
                  </span>
                  <span v-else class="text-slate-300 select-none">-</span>
                </td>

                <!-- Menu Icon -->
                <td class="py-2.5 px-4 text-center">
                  <span v-if="menu.icon" class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-slate-100 text-slate-600 border border-slate-200/80 text-xs">
                    <i :class="menu.icon"></i>
                  </span>
                  <span v-else class="text-slate-300">-</span>
                </td>

                <!-- Checkbox per Role Column -->
                <td 
                  v-for="role in displayedRoles" 
                  :key="role.id" 
                  class="py-3 px-3 text-center border-l border-slate-100"
                >
                  <label class="inline-flex items-center justify-center p-1 rounded-lg hover:bg-blue-100/50 transition cursor-pointer">
                    <input 
                      type="checkbox" 
                      v-model="matrix[`${role.id}___${menu.id}`]"
                      @change="handleCheckboxChange(role.id, menu)"
                      class="w-4 h-4 text-blue-600 rounded-md border-slate-300 focus:ring-blue-500 focus:ring-2 transition cursor-pointer"
                    />
                  </label>
                </td>
              </tr>

              <!-- Empty State if no menu matches search -->
              <tr v-if="filteredMenus.length === 0">
                <td :colspan="4 + displayedRoles.length" class="py-12 text-center text-slate-400">
                  <i class="bi bi-search text-3xl mb-2 block"></i>
                  <span class="text-xs font-bold text-slate-600">Tidak ada menu yang cocok dengan pencarian "{{ searchQuery }}".</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Footer / Action Bar -->
        <div class="p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <i class="bi bi-info-circle text-blue-600"></i>
            <span>Perubahan hak akses akan langsung aktif untuk pengguna yang login pada role terkait.</span>
          </div>

          <div class="flex items-center gap-3 w-full sm:w-auto">
            <button 
              type="button" 
              @click="saveMatrix" 
              :disabled="isSubmitting || isLoading"
              class="w-full sm:w-auto px-8 h-11 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/25 hover:shadow-blue-600/35 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <i v-else class="bi bi-floppy-fill text-sm"></i>
              <span>{{ isSubmitting ? 'Menyimpan Perubahan...' : 'Simpan Matriks Hak Akses' }}</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
