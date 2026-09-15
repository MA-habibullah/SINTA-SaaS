<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js'
import axios from 'axios'

const props = defineProps({
  initialStats: {
    type: Object,
    default: null
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

// Local Stats State for Zero-SSR
const localStats = ref(props.initialStats || {
  total_sessions_today: 0,
  unique_users_today: 0,
  total_logins_24h: 0,
  total_logouts_24h: 0,
})

const localTenantsList = ref(props.tenantsList && props.tenantsList.length > 0 ? [...props.tenantsList] : [])

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

// Memory Security Garbage Collector
useMemorySecurity([onlineUsers, auditLogs, chartData, auditChartData, localStats])

// Options for SearchableSelect
const tenantSelectOptions = computed(() => {
  const options = [
    { id: '00000000-0000-0000-0000-000000000000', nama: '🌐 Seluruh Tenant (Global)', subLabel: 'Semua Sekolah' }
  ]
  localTenantsList.value.forEach(t => {
    options.push({
      id: t.id,
      nama: t.nama_sekolah,
      subLabel: `${t.subdomain || ''} ${t.npsn ? '• NPSN: ' + t.npsn : ''}`.trim()
    })
  })
  return options
})

const perPageOptions = [
  { id: 10, nama: '10 / hal' },
  { id: 15, nama: '15 / hal' },
  { id: 25, nama: '25 / hal' },
  { id: 50, nama: '50 / hal' }
]

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

// Fetch Initial Stats & Metadata (Zero-SSR Hydration)
const fetchInitialSummary = async () => {
  try {
    const res = await axios.get('/utilitas/sesi-aktif?async=1')
    if (res.data && res.data.success) {
      if (res.data.stats) {
        localStats.value = res.data.stats
      }
      if (res.data.tenants_list) {
        localTenantsList.value = res.data.tenants_list
      }
    }
  } catch (err) {
    console.error('Failed to load initial session stats:', err)
  }
}

// Fetch Session & Chart Data
const fetchSessionData = async () => {
  isLoadingSessions.value = true
  try {
    const params = {
      timeframe: chartTimeframe.value
    }
    if (sessionStartDate.value) params.start_date = sessionStartDate.value
    if (sessionEndDate.value) params.end_date = sessionEndDate.value
    if (selectedTenant.value && selectedTenant.value !== '00000000-0000-0000-0000-000000000000') {
      params.tenant_id = selectedTenant.value
    }

    const res = await axios.get('/utilitas/sesi-aktif/data', { params })
    if (res.data && res.data.success) {
      onlineUsers.value = res.data.online_users || []
      chartData.value = res.data.chart_data || []
      auditChartData.value = res.data.audit_chart_data || []
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
    const params = {}
    if (auditStartDate.value) params.start_date = auditStartDate.value
    if (auditEndDate.value) params.end_date = auditEndDate.value
    if (selectedTenant.value && selectedTenant.value !== '00000000-0000-0000-0000-000000000000') {
      params.tenant_id = selectedTenant.value
    }

    const res = await axios.get('/utilitas/sesi-aktif/audit', { params })
    if (res.data && res.data.success) {
      auditLogs.value = res.data.audit_logs || []
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
  return Math.ceil(max * 1.15) || 5
})

// Combined Chart Points
const chartCombinedLabels = computed(() => {
  const set = new Set()
  chartData.value.forEach(d => set.add(d.label))
  auditChartData.value.forEach(d => set.add(d.label))
  return Array.from(set).sort()
})

// Chart Display Modes & Series Toggles
const chartDisplayMode = ref('area') // 'area' | 'bar'
const activeSeries = ref({
  users: true,
  logins: true,
  logouts: true
})
const hoveredPoint = ref(null)

// SVG Chart Geometry Constants
const svgChartW = 840
const svgChartH = 280
const svgPadL = 45
const svgPadR = 25
const svgPadT = 25
const svgPadB = 35
const svgPlotW = svgChartW - svgPadL - svgPadR
const svgPlotH = svgChartH - svgPadT - svgPadB

// Y-Axis Grid Ticks
const chartYTicks = computed(() => {
  const max = chartMaxVal.value
  const count = 4
  const ticks = []
  for (let i = 0; i <= count; i++) {
    const val = Math.round((max / count) * i)
    const y = svgPadT + svgPlotH - (val / max) * svgPlotH
    ticks.push({ val, y })
  }
  return ticks
})

// Data Points with Coordinates
const chartPoints = computed(() => {
  const labels = chartCombinedLabels.value
  if (labels.length === 0) return []

  const max = chartMaxVal.value
  const count = labels.length

  return labels.map((label, idx) => {
    const userItem = chartData.value.find(d => d.label === label)
    const auditItem = auditChartData.value.find(d => d.label === label)

    const users = userItem ? Number(userItem.total_users) : 0
    const logins = auditItem ? Number(auditItem.total_logins) : 0
    const logouts = auditItem ? Number(auditItem.total_logouts) : 0

    const x = count > 1 ? svgPadL + (idx * (svgPlotW / (count - 1))) : svgPadL + svgPlotW / 2
    const barCenter = svgPadL + (idx + 0.5) * (svgPlotW / count)
    const barWidth = Math.min(22, (svgPlotW / count) * 0.75)

    const yUsers = svgPadT + svgPlotH - (users / max) * svgPlotH
    const yLogins = svgPadT + svgPlotH - (logins / max) * svgPlotH
    const yLogouts = svgPadT + svgPlotH - (logouts / max) * svgPlotH

    let displayLabel = label
    if (label.includes('-')) {
      const parts = label.split('-')
      displayLabel = parts.slice(1).join('/')
    }

    return {
      index: idx,
      label,
      displayLabel,
      users,
      logins,
      logouts,
      x,
      barCenter,
      barWidth,
      yUsers,
      yLogins,
      yLogouts
    }
  })
})

// Helper to generate smooth cubic spline bezier curves
const getSplineD = (points, keyY) => {
  if (points.length === 0) return ''
  if (points.length === 1) return `M ${points[0].x} ${points[0][keyY]}`

  let d = `M ${points[0].x} ${points[0][keyY]}`
  for (let i = 0; i < points.length - 1; i++) {
    const p0 = points[i === 0 ? 0 : i - 1]
    const p1 = points[i]
    const p2 = points[i + 1]
    const p3 = points[i + 2 < points.length ? i + 2 : i + 1]

    const cp1x = p1.x + (p2.x - p0.x) / 6
    const cp1y = p1[keyY] + (p2[keyY] - p0[keyY]) / 6
    const cp2x = p2.x - (p3.x - p1.x) / 6
    const cp2y = p2[keyY] - (p3[keyY] - p1[keyY]) / 6

    d += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x} ${p2[keyY]}`
  }
  return d
}

// SVG Spline Paths for Line & Area
const splinePaths = computed(() => {
  const pts = chartPoints.value
  if (pts.length === 0) return { users: '', usersArea: '', logins: '', loginsArea: '', logouts: '', logoutsArea: '' }

  const groundY = svgPadT + svgPlotH
  const firstX = pts[0].x
  const lastX = pts[pts.length - 1].x

  const lineUsers = getSplineD(pts, 'yUsers')
  const areaUsers = `${lineUsers} L ${lastX} ${groundY} L ${firstX} ${groundY} Z`

  const lineLogins = getSplineD(pts, 'yLogins')
  const areaLogins = `${lineLogins} L ${lastX} ${groundY} L ${firstX} ${groundY} Z`

  const lineLogouts = getSplineD(pts, 'yLogouts')
  const areaLogouts = `${lineLogouts} L ${lastX} ${groundY} L ${firstX} ${groundY} Z`

  return {
    users: lineUsers,
    usersArea: areaUsers,
    logins: lineLogins,
    loginsArea: areaLogins,
    logouts: lineLogouts,
    logoutsArea: areaLogouts
  }
})

// Toggle active series
const toggleSeries = (s) => {
  activeSeries.value[s] = !activeSeries.value[s]
}

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
  
  if (!props.initialStats || !props.tenantsList || props.tenantsList.length === 0) {
    fetchInitialSummary()
  }
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
          <!-- Super Admin Tenant Selector via SearchableSelect -->
          <div v-if="isSuperAdmin && tenantSelectOptions.length > 0" class="min-w-[260px] sm:min-w-[300px]">
            <SearchableSelect 
              v-model="selectedTenant" 
              :options="tenantSelectOptions"
              placeholder="-- Pilih Instansi Sekolah --"
              search-placeholder="Cari sekolah atau NPSN..."
              @change="fetchSessionData(); fetchAuditLogs();"
            />
          </div>

          <button 
            type="button" 
            @click="fetchSessionData(); fetchAuditLogs(); fetchInitialSummary(); triggerToast('Data sesi berhasil disegarkan', 'success')" 
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
            <div class="text-2xl font-black text-slate-900">{{ localStats.total_sessions_today }}</div>
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
            <div class="text-2xl font-black text-slate-900">{{ localStats.unique_users_today }}</div>
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
            <div class="text-2xl font-black text-slate-900">{{ localStats.total_logins_24h }}</div>
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
            <div class="text-2xl font-black text-slate-900">{{ localStats.total_logouts_24h }}</div>
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
        
        <!-- Left: Interactive Vector Chart Analitik -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex flex-col justify-between">
          <div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
              <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                  <i class="bi bi-graph-up-arrow text-blue-600"></i>
                  Tren Aktivitas Pengguna & Sesi
                </h3>
                <p class="text-[11px] text-slate-400">Visualisasi tren kurva pengguna unik, login baru, dan logout</p>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <!-- Chart Mode Selector (Area vs Bar) -->
                <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-xl">
                  <button 
                    type="button"
                    @click="chartDisplayMode = 'area'"
                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition flex items-center gap-1.5"
                    :class="chartDisplayMode === 'area' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                    title="Tampilan Grafik Area / Kurva Halus"
                  >
                    <i class="bi bi-bezier2"></i> Area
                  </button>
                  <button 
                    type="button"
                    @click="chartDisplayMode = 'bar'"
                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition flex items-center gap-1.5"
                    :class="chartDisplayMode === 'bar' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                    title="Tampilan Grafik Batang Interaktif"
                  >
                    <i class="bi bi-bar-chart-fill"></i> Batang
                  </button>
                </div>

                <!-- Timeframe Selector -->
                <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-xl">
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
            </div>

            <!-- Modern SVG Vector Chart Area -->
            <div class="h-64 w-full relative pt-2 select-none">
              <!-- Loading Overlay -->
              <div v-if="isLoadingSessions" class="absolute inset-0 bg-white/80 backdrop-blur-xs flex items-center justify-center z-20 rounded-xl">
                <div class="flex items-center gap-2 text-xs font-bold text-blue-600">
                  <i class="bi bi-arrow-clockwise animate-spin text-base"></i>
                  Memuat visualisasi chart...
                </div>
              </div>

              <!-- Empty State -->
              <div v-else-if="chartCombinedLabels.length === 0" class="w-full h-full flex flex-col items-center justify-center text-slate-400">
                <i class="bi bi-bar-chart text-3xl mb-1 text-slate-300"></i>
                <span class="text-xs font-semibold">Belum ada rekaman aktivitas sesi pada periode ini</span>
              </div>

              <!-- Full SVG Render -->
              <svg 
                v-else 
                class="w-full h-full overflow-visible" 
                viewBox="0 0 840 280" 
                preserveAspectRatio="none"
                @mouseleave="hoveredPoint = null"
              >
                <defs>
                  <!-- Gradient for Unique Users Area -->
                  <linearGradient id="gradUsers" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.38" />
                    <stop offset="60%" stop-color="#3b82f6" stop-opacity="0.08" />
                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.0" />
                  </linearGradient>

                  <!-- Gradient for Logins Area -->
                  <linearGradient id="gradLogins" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.38" />
                    <stop offset="60%" stop-color="#10b981" stop-opacity="0.08" />
                    <stop offset="100%" stop-color="#10b981" stop-opacity="0.0" />
                  </linearGradient>

                  <!-- Gradient for Logouts Area -->
                  <linearGradient id="gradLogouts" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.38" />
                    <stop offset="60%" stop-color="#f59e0b" stop-opacity="0.08" />
                    <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.0" />
                  </linearGradient>

                  <!-- Drop shadow filter for glow dots -->
                  <filter id="glowEffect" x="-30%" y="-30%" width="160%" height="160%">
                    <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.25" />
                  </filter>
                </defs>

                <!-- Horizontal Y-Axis Grid Lines & Labels -->
                <g class="chart-grid">
                  <g v-for="(tick, idx) in chartYTicks" :key="'tick-' + idx">
                    <line 
                      :x1="svgPadL" 
                      :y1="tick.y" 
                      :x2="svgChartW - svgPadR" 
                      :y2="tick.y" 
                      stroke="#f1f5f9" 
                      stroke-width="1"
                      stroke-dasharray="4 4"
                    />
                    <text 
                      :x="svgPadL - 8" 
                      :y="tick.y + 4" 
                      fill="#94a3b8" 
                      font-size="10" 
                      font-family="sans-serif"
                      font-weight="600"
                      text-anchor="end"
                    >
                      {{ tick.val }}
                    </text>
                  </g>
                </g>

                <!-- MODE 1: AREA / SPLINE CURVES -->
                <g v-if="chartDisplayMode === 'area'" class="chart-spline-area">
                  <!-- Filled Gradient Areas -->
                  <path 
                    v-if="activeSeries.users && splinePaths.usersArea" 
                    :d="splinePaths.usersArea" 
                    fill="url(#gradUsers)" 
                    class="transition-opacity duration-300"
                  />
                  <path 
                    v-if="activeSeries.logins && splinePaths.loginsArea" 
                    :d="splinePaths.loginsArea" 
                    fill="url(#gradLogins)" 
                    class="transition-opacity duration-300"
                  />
                  <path 
                    v-if="activeSeries.logouts && splinePaths.logoutsArea" 
                    :d="splinePaths.logoutsArea" 
                    fill="url(#gradLogouts)" 
                    class="transition-opacity duration-300"
                  />

                  <!-- Smooth Spline Lines -->
                  <path 
                    v-if="activeSeries.users && splinePaths.users" 
                    :d="splinePaths.users" 
                    fill="none" 
                    stroke="#3b82f6" 
                    stroke-width="2.5" 
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path 
                    v-if="activeSeries.logins && splinePaths.logins" 
                    :d="splinePaths.logins" 
                    fill="none" 
                    stroke="#10b981" 
                    stroke-width="2.5" 
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path 
                    v-if="activeSeries.logouts && splinePaths.logouts" 
                    :d="splinePaths.logouts" 
                    fill="none" 
                    stroke="#f59e0b" 
                    stroke-width="2.5" 
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />

                  <!-- Dots on Points -->
                  <g v-for="pt in chartPoints" :key="'dots-' + pt.index">
                    <!-- Users Dot -->
                    <circle 
                      v-if="activeSeries.users" 
                      :cx="pt.x" 
                      :cy="pt.yUsers" 
                      :r="hoveredPoint?.index === pt.index ? 5.5 : 3.5" 
                      fill="#ffffff" 
                      stroke="#3b82f6" 
                      :stroke-width="hoveredPoint?.index === pt.index ? 3 : 2"
                      filter="url(#glowEffect)"
                      class="transition-all duration-200"
                    />
                    <!-- Logins Dot -->
                    <circle 
                      v-if="activeSeries.logins" 
                      :cx="pt.x" 
                      :cy="pt.yLogins" 
                      :r="hoveredPoint?.index === pt.index ? 5.5 : 3.5" 
                      fill="#ffffff" 
                      stroke="#10b981" 
                      :stroke-width="hoveredPoint?.index === pt.index ? 3 : 2"
                      filter="url(#glowEffect)"
                      class="transition-all duration-200"
                    />
                    <!-- Logouts Dot -->
                    <circle 
                      v-if="activeSeries.logouts" 
                      :cx="pt.x" 
                      :cy="pt.yLogouts" 
                      :r="hoveredPoint?.index === pt.index ? 5.5 : 3.5" 
                      fill="#ffffff" 
                      stroke="#f59e0b" 
                      :stroke-width="hoveredPoint?.index === pt.index ? 3 : 2"
                      filter="url(#glowEffect)"
                      class="transition-all duration-200"
                    />
                  </g>
                </g>

                <!-- MODE 2: MODERN BAR CHART -->
                <g v-else class="chart-bars">
                  <g v-for="pt in chartPoints" :key="'bar-group-' + pt.index">
                    <!-- Background Track on Hover -->
                    <rect 
                      :x="pt.barCenter - (pt.barWidth * 1.8)" 
                      :y="svgPadT" 
                      :width="pt.barWidth * 3.6" 
                      :height="svgPlotH" 
                      fill="#f8fafc" 
                      rx="8" 
                      :opacity="hoveredPoint?.index === pt.index ? 1 : 0" 
                      class="transition-opacity duration-200"
                    />

                    <!-- Bar 1: Users (Blue) -->
                    <rect 
                      v-if="activeSeries.users" 
                      :x="pt.barCenter - (pt.barWidth * 1.55)" 
                      :y="pt.yUsers" 
                      :width="pt.barWidth" 
                      :height="Math.max(3, (svgPadT + svgPlotH) - pt.yUsers)" 
                      fill="#3b82f6" 
                      rx="3" 
                      class="transition-all duration-300 hover:brightness-110"
                    />
                    <!-- Bar 2: Logins (Emerald) -->
                    <rect 
                      v-if="activeSeries.logins" 
                      :x="pt.barCenter - (pt.barWidth * 0.5)" 
                      :y="pt.yLogins" 
                      :width="pt.barWidth" 
                      :height="Math.max(3, (svgPadT + svgPlotH) - pt.yLogins)" 
                      fill="#10b981" 
                      rx="3" 
                      class="transition-all duration-300 hover:brightness-110"
                    />
                    <!-- Bar 3: Logouts (Amber) -->
                    <rect 
                      v-if="activeSeries.logouts" 
                      :x="pt.barCenter + (pt.barWidth * 0.55)" 
                      :y="pt.yLogouts" 
                      :width="pt.barWidth" 
                      :height="Math.max(3, (svgPadT + svgPlotH) - pt.yLogouts)" 
                      fill="#f59e0b" 
                      rx="3" 
                      class="transition-all duration-300 hover:brightness-110"
                    />
                  </g>
                </g>

                <!-- X-Axis Labels & Baseline -->
                <g class="chart-xaxis">
                  <line 
                    :x1="svgPadL" 
                    :y1="svgPadT + svgPlotH" 
                    :x2="svgChartW - svgPadR" 
                    :y2="svgPadT + svgPlotH" 
                    stroke="#cbd5e1" 
                    stroke-width="1"
                  />
                  <text 
                    v-for="pt in chartPoints" 
                    :key="'lbl-' + pt.index" 
                    :x="chartDisplayMode === 'area' ? pt.x : pt.barCenter" 
                    :y="svgPadT + svgPlotH + 18" 
                    fill="#94a3b8" 
                    font-size="10" 
                    font-family="monospace"
                    font-weight="600"
                    text-anchor="middle"
                  >
                    {{ pt.displayLabel }}
                  </text>
                </g>

                <!-- Interactive Hover Crosshair Line & Hotspots -->
                <g class="chart-interactions">
                  <!-- Vertical Hover Line -->
                  <line 
                    v-if="hoveredPoint" 
                    :x1="chartDisplayMode === 'area' ? hoveredPoint.x : hoveredPoint.barCenter" 
                    :y1="svgPadT" 
                    :x2="chartDisplayMode === 'area' ? hoveredPoint.x : hoveredPoint.barCenter" 
                    :y2="svgPadT + svgPlotH" 
                    stroke="#94a3b8" 
                    stroke-width="1.5" 
                    stroke-dasharray="4 4"
                    class="pointer-events-none"
                  />

                  <!-- Invisible Target Rectangles for Hover Discovery -->
                  <rect 
                    v-for="pt in chartPoints" 
                    :key="'hit-' + pt.index" 
                    :x="chartDisplayMode === 'area' ? (pt.x - (svgPlotW / Math.max(1, chartPoints.length)) / 2) : (pt.barCenter - (svgPlotW / chartPoints.length) / 2)" 
                    :y="svgPadT" 
                    :width="svgPlotW / Math.max(1, chartPoints.length)" 
                    :height="svgPlotH" 
                    fill="transparent" 
                    class="cursor-pointer"
                    @mouseenter="hoveredPoint = pt"
                  />
                </g>
              </svg>

              <!-- Floating Glassmorphism Tooltip -->
              <transition
                enter-active-class="transition ease-out duration-150"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
              >
                <div 
                  v-if="hoveredPoint" 
                  class="absolute pointer-events-none z-30 bg-slate-900/95 backdrop-blur-md text-white text-xs rounded-xl shadow-2xl p-3 border border-slate-700/60 min-w-[170px]"
                  :style="{
                    left: `${Math.min(75, Math.max(5, ((chartDisplayMode === 'area' ? hoveredPoint.x : hoveredPoint.barCenter) / svgChartW) * 100))}%`,
                    top: '15px'
                  }"
                >
                  <div class="flex items-center justify-between pb-1.5 mb-1.5 border-b border-slate-700/80">
                    <span class="font-extrabold text-slate-300 font-mono">{{ hoveredPoint.label }}</span>
                    <span class="text-[10px] text-slate-400 font-medium">Periode</span>
                  </div>
                  <div class="space-y-1">
                    <div class="flex items-center justify-between text-blue-300 font-semibold">
                      <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span> Pengguna Unik:
                      </span>
                      <span class="font-bold text-white font-mono">{{ hoveredPoint.users }}</span>
                    </div>
                    <div class="flex items-center justify-between text-emerald-300 font-semibold">
                      <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktivitas Login:
                      </span>
                      <span class="font-bold text-white font-mono">{{ hoveredPoint.logins }}</span>
                    </div>
                    <div class="flex items-center justify-between text-amber-300 font-semibold">
                      <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span> Aktivitas Logout:
                      </span>
                      <span class="font-bold text-white font-mono">{{ hoveredPoint.logouts }}</span>
                    </div>
                  </div>
                </div>
              </transition>
            </div>
          </div>

          <!-- Interactive Chart Legends & Series Filter Toggles -->
          <div class="flex flex-wrap items-center justify-center gap-5 pt-3 mt-2 border-t border-slate-100 text-xs font-semibold text-slate-600">
            <button 
              type="button" 
              @click="toggleSeries('users')" 
              class="flex items-center gap-2 px-2.5 py-1 rounded-lg transition hover:bg-slate-50"
              :class="{ 'opacity-40 line-through': !activeSeries.users }"
              title="Klik untuk menyembunyikan/menampilkan Pengguna Unik"
            >
              <span class="w-3.5 h-3.5 rounded-md bg-blue-500 shadow-xs flex items-center justify-center text-[10px] text-white font-bold">
                <i v-if="activeSeries.users" class="bi bi-check"></i>
              </span>
              <span>Pengguna Unik</span>
            </button>

            <button 
              type="button" 
              @click="toggleSeries('logins')" 
              class="flex items-center gap-2 px-2.5 py-1 rounded-lg transition hover:bg-slate-50"
              :class="{ 'opacity-40 line-through': !activeSeries.logins }"
              title="Klik untuk menyembunyikan/menampilkan Aktivitas Login"
            >
              <span class="w-3.5 h-3.5 rounded-md bg-emerald-500 shadow-xs flex items-center justify-center text-[10px] text-white font-bold">
                <i v-if="activeSeries.logins" class="bi bi-check"></i>
              </span>
              <span>Aktivitas Login</span>
            </button>

            <button 
              type="button" 
              @click="toggleSeries('logouts')" 
              class="flex items-center gap-2 px-2.5 py-1 rounded-lg transition hover:bg-slate-50"
              :class="{ 'opacity-40 line-through': !activeSeries.logouts }"
              title="Klik untuk menyembunyikan/menampilkan Aktivitas Logout"
            >
              <span class="w-3.5 h-3.5 rounded-md bg-amber-400 shadow-xs flex items-center justify-center text-[10px] text-white font-bold">
                <i v-if="activeSeries.logouts" class="bi bi-check"></i>
              </span>
              <span>Aktivitas Logout</span>
            </button>
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
            <i class="bi bi-info-circle me-1"></i> SINTA Multi-Tenant Log Garbage Collector
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
            <div class="w-28 shrink-0">
              <SearchableSelect 
                v-model="sessionPerPage" 
                :options="perPageOptions"
                placeholder="Per Hal"
                search-placeholder="Cari..."
              />
            </div>
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
            <div class="w-28 shrink-0">
              <SearchableSelect 
                v-model="auditPerPage" 
                :options="perPageOptions"
                placeholder="Per Hal"
                search-placeholder="Cari..."
              />
            </div>
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
