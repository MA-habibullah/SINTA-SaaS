<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  initialStats: {
    type: Object,
    default: () => ({
      total_sessions_today: 0,
      unique_users_today: 0,
      total_logins_24h: 0,
      total_logouts_24h: 0,
    })
  },
  isSuperAdmin: {
    type: Boolean,
    default: false
  },
  tenantsList: {
    type: Array,
    default: () => []
  },
  currentTenantId: {
    type: String,
    default: '00000000-0000-0000-0000-000000000000'
  }
})

// State Management
const activeTab = ref('sessions') // 'sessions' | 'audit'
const selectedTenant = ref(props.currentTenantId)
const chartTimeframe = ref('30_days')

const onlineUsers = ref([])
const chartData = ref([])
const auditChartData = ref([])
const auditLogs = ref([])

const isLoadingSessions = ref(false)
const isLoadingAudit = ref(false)
const isCleaningRetention = ref(false)

// Filter & Pagination - Sessions Table
const sessionStartDate = ref('')
const sessionEndDate = ref('')
const sessionSearch = ref('')
const sessionPerPage = ref(15)
const sessionPage = ref(1)

// Filter & Pagination - Audit Table
const auditStartDate = ref('')
const auditEndDate = ref('')
const auditSearch = ref('')
const auditPerPage = ref(15)
const auditPage = ref(1)

// Retention Settings
const retentionDate = ref('')
const maxRetentionDate = ref('')
const showRetentionModal = ref(false)
const retentionModalType = ref('sessions') // 'sessions' | 'audit'

// Toast
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

const triggerToast = (msg, type = 'success') => {
  toastMessage.value = msg
  toastType.value = type
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 4000)
}

// Fetch Session & Chart Data
const fetchSessionData = async () => {
  isLoadingSessions.value = true
  try {
    const params = new URLSearchParams()
    params.append('timeframe', chartTimeframe.value)
    if (sessionStartDate.value) params.append('start_date', sessionStartDate.value)
    if (sessionEndDate.value) params.append('end_date', sessionEndDate.value)
    if (selectedTenant.value && selectedTenant.value !== '00000000-0000-0000-0000-000000000000') {
      params.append('tenant_id', selectedTenant.value)
    }

    const res = await fetch(`/utilitas/sesi-aktif/data?${params.toString()}`)
    const json = await res.json()
    if (json.success) {
      onlineUsers.value = json.online_users || []
      chartData.value = json.chart_data || []
      auditChartData.value = json.audit_chart_data || []
      sessionPage.value = 1
    }
  } catch (err) {
    console.error('Gagal memuat data sesi aktif:', err)
    triggerToast('Gagal memuat data sesi aktif.', 'error')
  } finally {
    isLoadingSessions.value = false
  }
}

// Fetch Audit Logs
const fetchAuditLogs = async () => {
  isLoadingAudit.value = true
  try {
    const params = new URLSearchParams()
    if (auditStartDate.value) params.append('start_date', auditStartDate.value)
    if (auditEndDate.value) params.append('end_date', auditEndDate.value)
    if (selectedTenant.value && selectedTenant.value !== '00000000-0000-0000-0000-000000000000') {
      params.append('tenant_id', selectedTenant.value)
    }

    const res = await fetch(`/utilitas/sesi-aktif/audit?${params.toString()}`)
    const json = await res.json()
    if (json.success) {
      auditLogs.value = json.audit_logs || []
      auditPage.value = 1
    }
  } catch (err) {
    console.error('Gagal memuat log audit keamanan:', err)
    triggerToast('Gagal memuat log keamanan.', 'error')
  } finally {
    isLoadingAudit.value = false
  }
}

// User Agent Human-Readable Parser
const parseUserAgent = (ua) => {
  if (!ua) return 'Peramban Tidak Dikenal'
  if (ua.includes('Firefox/')) return 'Mozilla Firefox'
  if (ua.includes('Edg/')) return 'Microsoft Edge'
  if (ua.includes('OPR/') || ua.includes('Opera')) return 'Opera Browser'
  if (ua.includes('Chrome/')) return 'Google Chrome'
  if (ua.includes('Safari/') && !ua.includes('Chrome/')) return 'Apple Safari'
  if (ua.includes('MSIE') || ua.includes('Trident/')) return 'Internet Explorer'
  return ua.length > 45 ? ua.substring(0, 42) + '...' : ua
}

// Format DateTime
const formatDateTime = (rawDateTime) => {
  if (!rawDateTime) return '-'
  const d = new Date(rawDateTime.replace(/-/g, '/'))
  if (isNaN(d.getTime())) return rawDateTime
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }) + ' • ' + d.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }) + ' WIB'
}

// Filtered & Paginated Online Users
const filteredSessions = computed(() => {
  let list = onlineUsers.value
  if (sessionSearch.value.trim()) {
    const q = sessionSearch.value.toLowerCase().trim()
    list = list.filter(u => 
      (u.nama_lengkap && u.nama_lengkap.toLowerCase().includes(q)) ||
      (u.user_role && u.user_role.toLowerCase().includes(q)) ||
      (u.ip_address && u.ip_address.includes(q)) ||
      (u.user_agent && u.user_agent.toLowerCase().includes(q)) ||
      (u.nama_sekolah && u.nama_sekolah.toLowerCase().includes(q))
    )
  }
  return list
})

const paginatedSessions = computed(() => {
  const start = (sessionPage.value - 1) * sessionPerPage.value
  return filteredSessions.value.slice(start, start + sessionPerPage.value)
})

const sessionTotalPages = computed(() => {
  return Math.ceil(filteredSessions.value.length / sessionPerPage.value) || 1
})

// Filtered & Paginated Audit Logs
const filteredAuditLogs = computed(() => {
  let list = auditLogs.value
  if (auditSearch.value.trim()) {
    const q = auditSearch.value.toLowerCase().trim()
    list = list.filter(l => 
      (l.nama_lengkap && l.nama_lengkap.toLowerCase().includes(q)) ||
      (l.action && l.action.toLowerCase().includes(q)) ||
      (l.user_role && l.user_role.toLowerCase().includes(q)) ||
      (l.ip_address && l.ip_address.includes(q)) ||
      (l.nama_sekolah && l.nama_sekolah.toLowerCase().includes(q))
    )
  }
  return list
})

const paginatedAuditLogs = computed(() => {
  const start = (auditPage.value - 1) * auditPerPage.value
  return filteredAuditLogs.value.slice(start, start + auditPerPage.value)
})

const auditTotalPages = computed(() => {
  return Math.ceil(filteredAuditLogs.value.length / auditPerPage.value) || 1
})

// Max value calculation for SVG Chart
const chartMaxVal = computed(() => {
  let max = 5
  chartData.value.forEach(d => {
    if (d.total_users > max) max = Number(d.total_users)
  })
  auditChartData.value.forEach(d => {
    if (d.total_logins > max) max = Number(d.total_logins)
    if (d.total_logouts > max) max = Number(d.total_logouts)
  })
  return Math.ceil(max * 1.2)
})

// Combined Chart Points
const chartCombinedLabels = computed(() => {
  const set = new Set()
  chartData.value.forEach(d => set.add(d.label))
  auditChartData.value.forEach(d => set.add(d.label))
  return Array.from(set).sort()
})

// Execution of Data Retention Clean
const openRetentionDialog = (type) => {
  retentionModalType.value = type
  showRetentionModal.value = true
}

const executeRetentionClean = async () => {
  if (!retentionDate.value) return
  isCleaningRetention.value = true
  
  try {
    const endpoint = retentionModalType.value === 'sessions'
      ? '/utilitas/sesi-aktif/retention'
      : '/utilitas/sesi-aktif/audit/retention'

    const res = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ date_limit: retentionDate.value })
    })

    const json = await res.json()
    if (json.success) {
      triggerToast(json.message || 'Log berhasil dibersihkan.', 'success')
      showRetentionModal.value = false
      retentionDate.value = ''
      if (retentionModalType.value === 'sessions') {
        fetchSessionData()
      } else {
        fetchAuditLogs()
      }
    } else {
      triggerToast(json.error || 'Gagal membersihkan log.', 'error')
    }
  } catch (err) {
    console.error('Gagal membersihkan log retensi:', err)
    triggerToast('Terjadi kesalahan sistem saat membersihkan log.', 'error')
  } finally {
    isCleaningRetention.value = false
  }
}

// Initial Setup
onMounted(() => {
  const yesterday = new Date()
  yesterday.setDate(yesterday.getDate() - 1)
  maxRetentionDate.value = yesterday.toISOString().split('T')[0]
  
  fetchSessionData()
  fetchAuditLogs()
})
</script>

<template>
  <AppLayout title="Monitoring Sesi Aktif & Analitik Keamanan">
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

      <!-- Header & Scope Filter -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
              <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
              Sistem & Analitik Real-time
            </span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight">Monitoring Sesi Aktif & Keamanan</h1>
          <p class="text-xs text-slate-500">Memantau lalu lintas pengguna online, riwayat login/logout, dan audit keamanan multi-tenant</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Super Admin Tenant Selector -->
          <div v-if="isSuperAdmin && tenantsList.length > 0" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200/80 shadow-2xs">
            <i class="bi bi-buildings text-slate-400 text-sm"></i>
            <span class="text-xs font-semibold text-slate-600 whitespace-nowrap">Filter Sekolah:</span>
            <select 
              v-model="selectedTenant" 
              @change="fetchSessionData(); fetchAuditLogs();"
              class="text-xs font-bold text-slate-800 bg-transparent border-0 focus:ring-0 py-0.5 pl-1 pr-7 cursor-pointer"
            >
              <option value="00000000-0000-0000-0000-000000000000">🌐 Seluruh Tenant (Global)</option>
              <option v-for="t in tenantsList" :key="t.id" :value="t.id">
                {{ t.nama_sekolah }} ({{ t.subdomain }})
              </option>
            </select>
          </div>

          <button 
            type="button" 
            @click="fetchSessionData(); fetchAuditLogs(); triggerToast('Data sesi berhasil disegarkan', 'success')" 
            class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/80 rounded-xl shadow-2xs transition"
          >
            <i class="bi bi-arrow-clockwise" :class="isLoadingSessions || isLoadingAudit ? 'animate-spin text-blue-600' : ''"></i>
            <span>Segarkan</span>
          </button>
        </div>
      </div>

      <!-- 4 Quick Statistic Metric Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Sesi Aktif Hari Ini -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between relative overflow-hidden group hover:border-blue-400 transition">
          <div class="space-y-1">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Sesi Aktif Hari Ini</span>
            <div class="text-2xl font-black text-slate-900">{{ initialStats.total_sessions_today }}</div>
            <span class="text-[10px] font-semibold text-emerald-600 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Sesi terhubung hari ini
            </span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-xs group-hover:scale-110 transition shrink-0">
            <i class="bi bi-laptop"></i>
          </div>
        </div>

        <!-- 2. Pengguna Unik -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between relative overflow-hidden group hover:border-indigo-400 transition">
          <div class="space-y-1">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Pengguna Unik Hari Ini</span>
            <div class="text-2xl font-black text-slate-900">{{ initialStats.unique_users_today }}</div>
            <span class="text-[10px] font-semibold text-indigo-600 flex items-center gap-1">
              <i class="bi bi-people-fill"></i> Akun berbeda terdeteksi
            </span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-xs group-hover:scale-110 transition shrink-0">
            <i class="bi bi-person-check-fill"></i>
          </div>
        </div>

        <!-- 3. Total Login 24h -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between relative overflow-hidden group hover:border-emerald-400 transition">
          <div class="space-y-1">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Total Login (24 Jam)</span>
            <div class="text-2xl font-black text-slate-900">{{ initialStats.total_logins_24h }}</div>
            <span class="text-[10px] font-semibold text-emerald-600 flex items-center gap-1">
              <i class="bi bi-box-arrow-in-right"></i> Berhasil login ke sistem
            </span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-xs group-hover:scale-110 transition shrink-0">
            <i class="bi bi-shield-check"></i>
          </div>
        </div>

        <!-- 4. Total Logout 24h -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between relative overflow-hidden group hover:border-amber-400 transition">
          <div class="space-y-1">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Total Logout (24 Jam)</span>
            <div class="text-2xl font-black text-slate-900">{{ initialStats.total_logouts_24h }}</div>
            <span class="text-[10px] font-semibold text-amber-600 flex items-center gap-1">
              <i class="bi bi-box-arrow-right"></i> Sesi ditutup normal/timeout
            </span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-xs group-hover:scale-110 transition shrink-0">
            <i class="bi bi-clock-history"></i>
          </div>
        </div>
      </div>

      <!-- Top Section: Interactive Trend Chart & Retention Box -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Line Chart Analitik -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex flex-col justify-between">
          <div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
              <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                  <i class="bi bi-graph-up-arrow text-blue-600"></i>
                  Tren Aktivitas Pengguna & Sesi
                </h3>
                <p class="text-[11px] text-slate-400">Analitik perbandingan pengguna unik, login baru, dan logout</p>
              </div>

              <!-- Timeframe Selector -->
              <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                <button 
                  v-for="tf in [
                    { id: '30_minutes', label: '30 Menit' },
                    { id: '1_hour', label: '1 Jam' },
                    { id: '1_day', label: '1 Hari' },
                    { id: '15_days', label: '15 Hari' },
                    { id: '30_days', label: '30 Hari' }
                  ]"
                  :key="tf.id"
                  @click="chartTimeframe = tf.id; fetchSessionData();"
                  class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition"
                  :class="chartTimeframe === tf.id ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                >
                  {{ tf.label }}
                </button>
              </div>
            </div>

            <!-- Modern SVG Chart Visualization -->
            <div class="h-64 w-full relative flex items-end pt-6 pb-2">
              <div v-if="isLoadingSessions" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                <div class="flex items-center gap-2 text-xs font-bold text-blue-600">
                  <i class="bi bi-arrow-clockwise animate-spin text-base"></i>
                  Memuat data tren...
                </div>
              </div>

              <!-- Empty State if no points -->
              <div v-else-if="chartCombinedLabels.length === 0" class="w-full h-full flex flex-col items-center justify-center text-slate-400">
                <i class="bi bi-bar-chart text-3xl mb-1"></i>
                <span class="text-xs font-semibold">Belum ada rekaman data tren pada periode ini</span>
              </div>

              <!-- Interactive Multi-Bar / Trend Representation -->
              <div v-else class="w-full h-full flex items-end justify-between gap-1.5 px-2 border-b border-slate-100">
                <div 
                  v-for="(label, idx) in chartCombinedLabels" 
                  :key="idx" 
                  class="flex-1 flex flex-col items-center gap-1 group relative h-full justify-end"
                >
                  <!-- Tooltip Hover Popup -->
                  <div class="absolute bottom-full mb-2 hidden group-hover:flex flex-col items-center z-20 pointer-events-none">
                    <div class="bg-slate-900 text-white text-[10px] rounded-xl py-1.5 px-3 shadow-xl whitespace-nowrap space-y-0.5">
                      <div class="font-extrabold text-slate-300">{{ label }}</div>
                      <div class="text-blue-400">Pengguna Unik: {{ (chartData.find(d => d.label === label)?.total_users || 0) }}</div>
                      <div class="text-emerald-400">Login: {{ (auditChartData.find(d => d.label === label)?.total_logins || 0) }}</div>
                      <div class="text-amber-400">Logout: {{ (auditChartData.find(d => d.label === label)?.total_logouts || 0) }}</div>
                    </div>
                    <div class="w-2 h-2 bg-slate-900 transform rotate-45 -mt-1"></div>
                  </div>

                  <!-- Stacked / Multi-colored bars -->
                  <div class="w-full max-w-[28px] flex items-end gap-0.5 h-full justify-center">
                    <!-- Unique Users Bar -->
                    <div 
                      class="w-2 bg-blue-500 hover:bg-blue-600 rounded-t-md transition-all duration-300"
                      :style="{ height: `${Math.max(8, ((chartData.find(d => d.label === label)?.total_users || 0) / chartMaxVal) * 100)}%` }"
                    ></div>
                    <!-- Login Bar -->
                    <div 
                      class="w-2 bg-emerald-500 hover:bg-emerald-600 rounded-t-md transition-all duration-300"
                      :style="{ height: `${Math.max(8, ((auditChartData.find(d => d.label === label)?.total_logins || 0) / chartMaxVal) * 100)}%` }"
                    ></div>
                    <!-- Logout Bar -->
                    <div 
                      class="w-2 bg-amber-400 hover:bg-amber-500 rounded-t-md transition-all duration-300"
                      :style="{ height: `${Math.max(8, ((auditChartData.find(d => d.label === label)?.total_logouts || 0) / chartMaxVal) * 100)}%` }"
                    ></div>
                  </div>

                  <!-- X-Axis Label -->
                  <span class="text-[9px] font-mono text-slate-400 truncate max-w-[40px] pt-1">
                    {{ label.includes('-') ? label.split('-').slice(1).join('/') : label }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Chart Legends -->
          <div class="flex items-center justify-center gap-6 pt-3 border-t border-slate-100 text-xs font-semibold text-slate-600">
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-md bg-blue-500"></span>
              <span>Pengguna Unik</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-md bg-emerald-500"></span>
              <span>Aktivitas Login</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-md bg-amber-400"></span>
              <span>Aktivitas Logout</span>
            </div>
          </div>
        </div>

        <!-- Right: Data Retention Box -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex flex-col justify-between space-y-4">
          <div>
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 text-slate-900 font-black text-sm">
              <i class="bi bi-shield-slash text-red-600 text-base"></i>
              <span>Retensi & Pembersihan Log</span>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed mt-3">
              Bersihkan riwayat sesi lama sebelum tanggal tertentu untuk menghemat ruang penyimpanan PostgreSQL. Sesi yang sedang aktif hari ini tetap aman dan tidak akan terhapus.
            </p>

            <div class="mt-4 space-y-3">
              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="bi bi-clock-history text-blue-600"></i> Riwayat Sesi Pengguna
                  </span>
                  <span class="text-[10px] font-mono font-bold text-slate-500">{{ onlineUsers.length }} Rekaman</span>
                </div>
                <button 
                  type="button" 
                  @click="openRetentionDialog('sessions')" 
                  class="w-full py-2 px-3 text-xs font-bold text-red-600 hover:text-white bg-red-50 hover:bg-red-600 rounded-xl transition border border-red-200/60 shadow-2xs flex items-center justify-center gap-2"
                >
                  <i class="bi bi-trash3"></i> Bersihkan Log Sesi Lama
                </button>
              </div>

              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="bi bi-shield-lock text-emerald-600"></i> Log Jejak Audit Keamanan
                  </span>
                  <span class="text-[10px] font-mono font-bold text-slate-500">{{ auditLogs.length }} Rekaman</span>
                </div>
                <button 
                  type="button" 
                  @click="openRetentionDialog('audit')" 
                  class="w-full py-2 px-3 text-xs font-bold text-amber-700 hover:text-white bg-amber-50 hover:bg-amber-600 rounded-xl transition border border-amber-200/60 shadow-2xs flex items-center justify-center gap-2"
                >
                  <i class="bi bi-eraser"></i> Bersihkan Log Audit Lama
                </button>
              </div>
            </div>
          </div>

          <div class="text-[11px] text-slate-400 text-center">
            <i class="bi bi-info-circle me-1"></i> SINTA SaaS Multi-Tenant Log Garbage Collector
          </div>
        </div>
      </div>

      <!-- Navigation Tabs (SINTA Standard Horizontal NavTabs) -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-1.5 relative">
        <div class="flex items-center gap-1.5">
          <button 
            type="button" 
            @click="activeTab = 'sessions'" 
            class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2"
            :class="activeTab === 'sessions' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
          >
            <i class="bi bi-laptop text-sm"></i>
            <span>Daftar Sesi Pengguna Aktif</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono" :class="activeTab === 'sessions' ? 'bg-blue-700 text-white' : 'bg-slate-200 text-slate-700'">
              {{ filteredSessions.length }}
            </span>
          </button>

          <button 
            type="button" 
            @click="activeTab = 'audit'" 
            class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2"
            :class="activeTab === 'audit' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
          >
            <i class="bi bi-shield-lock-fill text-sm"></i>
            <span>Log Jejak Keamanan (Login & Logout)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono" :class="activeTab === 'audit' ? 'bg-blue-700 text-white' : 'bg-slate-200 text-slate-700'">
              {{ filteredAuditLogs.length }}
            </span>
          </button>
        </div>
      </div>

      <!-- TAB 1: Tabel Riwayat Sesi Pengguna Aktif -->
      <div v-if="activeTab === 'sessions'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        
        <!-- Filter Bar Atas -->
        <div class="p-4 bg-slate-50/60 border-b border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <!-- Start Date -->
            <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs shadow-2xs">
              <span class="text-slate-400 text-[10px] font-bold">Mulai:</span>
              <input type="date" v-model="sessionStartDate" class="border-0 p-0 text-xs font-semibold text-slate-700 focus:ring-0">
            </div>
            <!-- End Date -->
            <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs shadow-2xs">
              <span class="text-slate-400 text-[10px] font-bold">Sampai:</span>
              <input type="date" v-model="sessionEndDate" class="border-0 p-0 text-xs font-semibold text-slate-700 focus:ring-0">
            </div>
            <button 
              type="button" 
              @click="fetchSessionData" 
              class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1"
            >
              <i class="bi bi-funnel"></i> Filter
            </button>
            <button 
              v-if="sessionStartDate || sessionEndDate || sessionSearch" 
              type="button" 
              @click="sessionStartDate = ''; sessionEndDate = ''; sessionSearch = ''; fetchSessionData();" 
              class="px-2.5 py-1.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition"
            >
              Reset
            </button>
          </div>

          <!-- Search Input & Per Page -->
          <div class="flex items-center gap-2">
            <div class="relative min-w-[220px]">
              <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input 
                type="text" 
                v-model="sessionSearch" 
                placeholder="Cari nama, role, IP..." 
                class="w-full pl-8 pr-3 py-1.5 text-xs bg-white rounded-xl border border-slate-200 shadow-2xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium"
              />
            </div>
            <select 
              v-model="sessionPerPage" 
              class="bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-700 shadow-2xs"
            >
              <option :value="10">10 / hal</option>
              <option :value="15">15 / hal</option>
              <option :value="25">25 / hal</option>
              <option :value="50">50 / hal</option>
            </select>
          </div>
        </div>

        <!-- Tabel Data Sesi -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-700 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3 px-4 text-center w-12">No</th>
                <th class="py-3 px-4">Pengguna</th>
                <th class="py-3 px-4">Peran</th>
                <th class="py-3 px-4">IP Address</th>
                <th class="py-3 px-4">Peramban (Browser / Agent)</th>
                <th class="py-3 px-4 text-right">Aktivitas Terakhir</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="isLoadingSessions">
                <td colspan="6" class="py-12 text-center text-slate-400">
                  <i class="bi bi-arrow-clockwise animate-spin text-2xl mb-2 block text-blue-600"></i>
                  <span>Memuat daftar sesi aktif...</span>
                </td>
              </tr>
              <tr v-else-if="filteredSessions.length === 0">
                <td colspan="6" class="py-12 text-center text-slate-400">
                  <i class="bi bi-person-x text-3xl mb-2 block"></i>
                  <span class="font-bold text-slate-600">Tidak ada sesi pengguna aktif yang terdeteksi.</span>
                </td>
              </tr>
              <tr 
                v-else 
                v-for="(session, idx) in paginatedSessions" 
                :key="session.id || idx" 
                class="hover:bg-blue-50/40 transition"
              >
                <!-- No -->
                <td class="py-3 px-4 text-center font-mono text-[11px] text-slate-400">
                  {{ (sessionPage - 1) * sessionPerPage + idx + 1 }}
                </td>

                <!-- Pengguna -->
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                      {{ (session.nama_lengkap || 'U').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-900 leading-tight">{{ session.nama_lengkap }}</div>
                      <div v-if="session.nama_sekolah" class="text-[10px] text-slate-400">
                        {{ session.nama_sekolah }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Peran -->
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200/80">
                    {{ session.user_role }}
                  </span>
                </td>

                <!-- IP Address -->
                <td class="py-3 px-4 font-mono text-[11px] text-slate-700 font-semibold">
                  <span class="bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200/60">
                    {{ session.ip_address || '127.0.0.1' }}
                  </span>
                </td>

                <!-- Browser -->
                <td class="py-3 px-4">
                  <div class="flex items-center gap-1.5 text-slate-700">
                    <i class="bi bi-browser-chrome text-blue-500 text-sm"></i>
                    <span class="font-semibold" :title="session.user_agent">
                      {{ parseUserAgent(session.user_agent) }}
                    </span>
                  </div>
                </td>

                <!-- Waktu Terakhir -->
                <td class="py-3 px-4 text-right font-mono text-[11px] text-slate-500">
                  <div class="font-bold text-slate-800">{{ formatDateTime(session.last_activity) }}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Footer -->
        <div class="p-4 bg-slate-50/60 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <div class="text-slate-500 font-medium">
            Menampilkan <span class="font-bold text-slate-800">{{ paginatedSessions.length }}</span> dari <span class="font-bold text-slate-800">{{ filteredSessions.length }}</span> sesi pengguna
          </div>
          <div class="flex items-center gap-1">
            <button 
              type="button" 
              @click="sessionPage--" 
              :disabled="sessionPage <= 1"
              class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs"
            >
              Sebelumnya
            </button>
            <span class="px-3 py-1.5 font-bold text-slate-700">
              Hal {{ sessionPage }} / {{ sessionTotalPages }}
            </span>
            <button 
              type="button" 
              @click="sessionPage++" 
              :disabled="sessionPage >= sessionTotalPages"
              class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- TAB 2: Tabel Log Jejak Keamanan (Audit Trail) -->
      <div v-if="activeTab === 'audit'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        
        <!-- Filter Bar Atas -->
        <div class="p-4 bg-slate-50/60 border-b border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <!-- Start Date -->
            <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs shadow-2xs">
              <span class="text-slate-400 text-[10px] font-bold">Mulai:</span>
              <input type="date" v-model="auditStartDate" class="border-0 p-0 text-xs font-semibold text-slate-700 focus:ring-0">
            </div>
            <!-- End Date -->
            <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs shadow-2xs">
              <span class="text-slate-400 text-[10px] font-bold">Sampai:</span>
              <input type="date" v-model="auditEndDate" class="border-0 p-0 text-xs font-semibold text-slate-700 focus:ring-0">
            </div>
            <button 
              type="button" 
              @click="fetchAuditLogs" 
              class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1"
            >
              <i class="bi bi-funnel"></i> Filter
            </button>
            <button 
              v-if="auditStartDate || auditEndDate || auditSearch" 
              type="button" 
              @click="auditStartDate = ''; auditEndDate = ''; auditSearch = ''; fetchAuditLogs();" 
              class="px-2.5 py-1.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition"
            >
              Reset
            </button>
          </div>

          <!-- Search Input & Per Page -->
          <div class="flex items-center gap-2">
            <div class="relative min-w-[220px]">
              <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input 
                type="text" 
                v-model="auditSearch" 
                placeholder="Cari aktivitas, nama, role..." 
                class="w-full pl-8 pr-3 py-1.5 text-xs bg-white rounded-xl border border-slate-200 shadow-2xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium"
              />
            </div>
            <select 
              v-model="auditPerPage" 
              class="bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-700 shadow-2xs"
            >
              <option :value="10">10 / hal</option>
              <option :value="15">15 / hal</option>
              <option :value="25">25 / hal</option>
              <option :value="50">50 / hal</option>
            </select>
          </div>
        </div>

        <!-- Tabel Data Audit -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-700 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3 px-4 text-center w-12">No</th>
                <th class="py-3 px-4">Waktu Kejadian</th>
                <th class="py-3 px-4">Aktivitas / Event</th>
                <th class="py-3 px-4">Nama Pengguna</th>
                <th class="py-3 px-4">Peran</th>
                <th class="py-3 px-4 text-right">IP Address</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="isLoadingAudit">
                <td colspan="6" class="py-12 text-center text-slate-400">
                  <i class="bi bi-arrow-clockwise animate-spin text-2xl mb-2 block text-blue-600"></i>
                  <span>Memuat log jejak keamanan...</span>
                </td>
              </tr>
              <tr v-else-if="filteredAuditLogs.length === 0">
                <td colspan="6" class="py-12 text-center text-slate-400">
                  <i class="bi bi-shield-check text-3xl mb-2 block text-slate-300"></i>
                  <span class="font-bold text-slate-600">Belum ada rekaman log audit keamanan.</span>
                </td>
              </tr>
              <tr 
                v-else 
                v-for="(log, idx) in paginatedAuditLogs" 
                :key="log.id || idx" 
                class="hover:bg-blue-50/40 transition"
              >
                <!-- No -->
                <td class="py-3 px-4 text-center font-mono text-[11px] text-slate-400">
                  {{ (auditPage - 1) * auditPerPage + idx + 1 }}
                </td>

                <!-- Waktu -->
                <td class="py-3 px-4 font-mono text-[11px] text-slate-700 font-bold">
                  {{ formatDateTime(log.created_at) }}
                </td>

                <!-- Aksi -->
                <td class="py-3 px-4">
                  <span 
                    v-if="log.action === 'LOGIN'" 
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs"
                  >
                    <i class="bi bi-box-arrow-in-right"></i> LOGIN
                  </span>
                  <span 
                    v-else-if="log.action === 'LOGOUT'" 
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-slate-200 text-slate-800 border border-slate-300 shadow-2xs"
                  >
                    <i class="bi bi-box-arrow-left"></i> LOGOUT
                  </span>
                  <span 
                    v-else 
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300 shadow-2xs"
                  >
                    <i class="bi bi-clock"></i> {{ log.action }}
                  </span>
                </td>

                <!-- Pengguna -->
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900 leading-tight">{{ log.nama_lengkap }}</div>
                  <div v-if="log.nama_sekolah" class="text-[10px] text-slate-400">
                    {{ log.nama_sekolah }}
                  </div>
                </td>

                <!-- Peran -->
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200/80">
                    {{ log.user_role || 'Pengguna' }}
                  </span>
                </td>

                <!-- IP Address -->
                <td class="py-3 px-4 text-right font-mono text-[11px] text-slate-600">
                  <span class="bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200/60 font-semibold">
                    {{ log.ip_address || '127.0.0.1' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Footer -->
        <div class="p-4 bg-slate-50/60 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <div class="text-slate-500 font-medium">
            Menampilkan <span class="font-bold text-slate-800">{{ paginatedAuditLogs.length }}</span> dari <span class="font-bold text-slate-800">{{ filteredAuditLogs.length }}</span> rekaman audit
          </div>
          <div class="flex items-center gap-1">
            <button 
              type="button" 
              @click="auditPage--" 
              :disabled="auditPage <= 1"
              class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs"
            >
              Sebelumnya
            </button>
            <span class="px-3 py-1.5 font-bold text-slate-700">
              Hal {{ auditPage }} / {{ auditTotalPages }}
            </span>
            <button 
              type="button" 
              @click="auditPage++" 
              :disabled="auditPage >= auditTotalPages"
              class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- Retention Confirmation Modal -->
      <Teleport to="body">
        <div 
          v-if="showRetentionModal" 
          class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
          <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4 relative z-10">
            <div class="flex items-center gap-3 text-red-600">
              <div class="w-10 h-10 rounded-2xl bg-red-100 flex items-center justify-center text-xl shrink-0">
                <i class="bi bi-trash3-fill"></i>
              </div>
              <div>
                <h3 class="text-base font-black text-slate-900">
                  {{ retentionModalType === 'sessions' ? 'Bersihkan Riwayat Sesi' : 'Bersihkan Log Jejak Audit' }}
                </h3>
                <p class="text-xs text-slate-500">Tindakan ini permanen dan tidak dapat dibatalkan</p>
              </div>
            </div>

            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-700">Hapus Log Sebelum / Pada Tanggal:</label>
              <input 
                type="date" 
                v-model="retentionDate" 
                :max="maxRetentionDate" 
                class="w-full text-xs font-semibold rounded-xl border border-slate-200 p-2.5 focus:ring-2 focus:ring-red-500 shadow-2xs"
              />
              <p class="text-[11px] text-slate-400">
                Maksimal tanggal batas adalah kemarin ({{ maxRetentionDate }}). Sesi aktif hari ini tetap terlindungi.
              </p>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
              <button 
                type="button" 
                @click="showRetentionModal = false" 
                class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition"
              >
                Batal
              </button>
              <button 
                type="button" 
                @click="executeRetentionClean" 
                :disabled="!retentionDate || isCleaningRetention"
                class="px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-2xs flex items-center gap-1.5 disabled:opacity-50"
              >
                <i v-if="isCleaningRetention" class="bi bi-arrow-clockwise animate-spin"></i>
                <span>{{ isCleaningRetention ? 'Membersihkan...' : 'Ya, Hapus Log' }}</span>
              </button>
            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </AppLayout>
</template>
