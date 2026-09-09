<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  roles: {
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
  accessMap: {
    type: Object,
    default: () => ({})
  },
  tenantsList: {
    type: Array,
    default: () => []
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

// Active list of menus for RBAC matrix
const availableMenuList = computed(() => {
  return props.menuList && props.menuList.length > 0 ? props.menuList : (props.menus || [])
})

// Search & Filter
const searchQuery = ref('')
const selectedRoleFilter = ref('all')
const targetTenant = ref(props.selectedTenantId || '00000000-0000-0000-0000-000000000000')

// Matrix access state: { 'roleId-menuId': boolean }
const matrix = reactive({})
const isCustomTenant = ref(false)
const isLoadingMatrix = ref(false)
const isSubmitting = ref(false)

// Toast
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

// Initialize matrix from props
const initMatrix = (mapData) => {
  // Clear old keys
  Object.keys(matrix).forEach(key => delete matrix[key])
  
  if (mapData) {
    Object.keys(mapData).forEach(key => {
      matrix[key] = !!mapData[key]
    })
  }
}

onMounted(() => {
  initMatrix(props.accessMap)
})

// Checkbox change handler with Parent-Child Cascading logic
const handleCheckboxChange = (roleId, menu) => {
  const key = `${roleId}___${menu.id}`
  const isChecked = !!matrix[key]

  if (!isChecked) {
    // If Parent is UNCHECKED -> Auto-uncheck all its children
    if (!menu.is_child) {
      availableMenuList.value.forEach(m => {
        if (m.parent_id === menu.id) {
          matrix[`${roleId}___${m.id}`] = false
        }
      })
    }
  } else {
    // If Child is CHECKED -> Auto-check its parent
    if (menu.is_child && menu.parent_id) {
      matrix[`${roleId}___${menu.parent_id}`] = true
    }
  }
}

// Bulk toggle for a specific role
const toggleAllForRole = (roleId, grantAll = true) => {
  availableMenuList.value.forEach(m => {
    matrix[`${roleId}___${m.id}`] = grantAll
  })
}

// Filtered roles based on dropdown filter
const displayedRoles = computed(() => {
  if (selectedRoleFilter.value === 'all') {
    return props.roles
  }
  return props.roles.filter(r => r.id === selectedRoleFilter.value)
})

// Filtered menus based on search
const filteredMenus = computed(() => {
  const rawList = availableMenuList.value
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

// Target Tenant change handler
const handleTenantChange = (e) => {
  const tId = e.target.value
  targetTenant.value = tId
  
  if (!tId || tId === '00000000-0000-0000-0000-000000000000') {
    router.visit('/konfigurasi/akses', { preserveScroll: true })
    return
  }

  router.visit(`/konfigurasi/akses?tenant_id=${tId}`, { preserveScroll: true })
}

// Watch for prop changes
watch(() => props.accessMap, (newMap) => {
  initMatrix(newMap)
}, { deep: true })

watch(() => props.selectedTenantId, (newTenant) => {
  if (newTenant) {
    targetTenant.value = newTenant
  }
})

// Save Matrix
const saveMatrix = () => {
  isSubmitting.value = true

  // Construct access payload: { roleId: [menuId1, menuId2, ...] }
  const accessPayload = {}
  props.roles.forEach(r => {
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

  router.post('/konfigurasi/akses', {
    target_tenant_id: targetTenant.value,
    access: accessPayload
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: (page) => {
      isSubmitting.value = false
      triggerToast('Matriks hak akses menu berhasil disimpan & diterapkan secara real-time!', 'success')
      if (page.props?.accessMap) {
        initMatrix(page.props.accessMap)
      }
    },
    onError: () => {
      isSubmitting.value = false
      triggerToast('Gagal menyimpan matriks hak akses.', 'error')
    }
  })
}

const triggerToast = (msg, type = 'success') => {
  toastMessage.value = msg
  toastType.value = type
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 4000)
}

const formatRoleName = (name) => {
  if (!name) return ''
  return name.replace(/_/g, ' ').toUpperCase()
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
          <button @click="showToast = false" class="text-white/80 hover:text-white ml-2 text-xs">
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
            Atur visibilitas menu sidebar dan hak otorisasi modul untuk masing-masing peran pengguna secara real-time.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button 
            type="button" 
            @click="saveMatrix" 
            :disabled="isSubmitting"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <span v-if="isSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <i v-else class="bi bi-floppy-fill"></i>
            <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Hak Akses' }}</span>
          </button>
        </div>
      </div>

      <!-- Super Admin Target Tenant Switcher -->
      <div v-if="isSuperAdmin && tenantsList && tenantsList.length > 0" class="bg-white rounded-3xl p-5 shadow-2xs border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0">
            <i class="bi bi-buildings"></i>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h4 class="text-sm font-bold text-slate-800">Target Otorisasi Lembaga / Sekolah</h4>
              <span v-if="isLoadingMatrix" class="text-[11px] text-blue-600 font-semibold flex items-center gap-1">
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
          <select 
            :value="targetTenant" 
            @change="handleTenantChange"
            class="w-full h-11 px-3.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white focus:bg-white text-slate-800 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 transition cursor-pointer"
          >
            <option value="00000000-0000-0000-0000-000000000000">
              ⭐ — Global Default Template (Seluruh Sekolah) —
            </option>
            <option v-for="t in tenantsList" :key="t.id" :value="t.id">
              {{ t.nama_sekolah }} (NPSN: {{ t.npsn || '-' }})
            </option>
          </select>
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

      <!-- Filter & Search Toolbar -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <!-- Search input -->
        <div class="relative w-full sm:w-80">
          <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Cari nama menu atau path URL..."
            class="w-full h-10 pl-9 pr-4 rounded-xl border border-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 focus:bg-white transition"
          />
        </div>

        <!-- Filter by Role dropdown -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <span class="text-xs text-slate-500 font-bold whitespace-nowrap">Filter Role:</span>
          <select 
            v-model="selectedRoleFilter"
            class="h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
          >
            <option value="all">Semua Peran ({{ roles.length }} Role)</option>
            <option v-for="r in roles" :key="r.id" :value="r.id">
              {{ formatRoleName(r.nama_role) }}
            </option>
          </select>
        </div>
      </div>

      <!-- RBAC Matrix Table Container -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
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
                        class="hover:text-blue-600 underline font-semibold"
                        title="Centang Semua Menu untuk Role Ini"
                      >Semua</button>
                      <span>|</span>
                      <button 
                        type="button" 
                        @click="toggleAllForRole(role.id, false)" 
                        class="hover:text-red-600 underline font-semibold"
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
                :class="!menu.is_child ? 'bg-slate-50/80 font-bold text-slate-900 border-t-2 border-slate-200/60' : 'bg-white text-slate-700'"
              >
                <!-- Row Number -->
                <td class="py-3 px-4 text-center font-mono text-slate-400 text-[11px]">
                  {{ idx + 1 }}
                </td>

                <!-- Menu Name & Indentation -->
                <td class="py-3 px-4" :class="menu.is_child ? 'pl-8' : 'pl-4'">
                  <div class="flex items-center gap-2">
                    <template v-if="menu.is_child">
                      <span class="text-slate-300 font-mono select-none">└──</span>
                      <i :class="menu.icon || 'bi bi-circle'" class="text-slate-400 text-xs"></i>
                      <span class="text-xs font-semibold text-slate-700">{{ menu.nama_menu }}</span>
                    </template>
                    <template v-else>
                      <i :class="menu.icon || 'bi bi-folder-fill'" class="text-blue-600 text-sm"></i>
                      <span class="text-xs font-black text-slate-900 uppercase tracking-tight">{{ menu.nama_menu }}</span>
                    </template>
                  </div>
                </td>

                <!-- URL Path -->
                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                  <span v-if="menu.url && menu.url !== '#'" class="bg-slate-100 px-2 py-0.5 rounded-md text-slate-600 border border-slate-200/60">
                    {{ menu.url }}
                  </span>
                  <span v-else class="text-slate-300 select-none">-</span>
                </td>

                <!-- Menu Icon -->
                <td class="py-3 px-4 text-center">
                  <span v-if="menu.icon" class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 text-slate-600 border border-slate-200/80">
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
              :disabled="isSubmitting"
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
