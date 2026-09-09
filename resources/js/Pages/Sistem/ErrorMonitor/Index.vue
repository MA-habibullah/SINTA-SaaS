<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const getSwal = () => typeof window !== 'undefined' && window.Swal ? window.Swal : null

const props = defineProps({
    errors: Object,
    stats: Object,
    tenantsList: {
        type: Array,
        default: () => []
    },
    levelsList: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    isSuperAdmin: {
        type: Boolean,
        default: true
    }
})

// Reactive States
const localErrors = ref(props.errors?.data || [])
const perPage = ref(props.errors?.per_page || 20)
const pagination = ref({
    currentPage: props.errors?.current_page || 1,
    lastPage: props.errors?.last_page || 1,
    total: props.errors?.total || 0,
    from: props.errors?.from || 0,
    to: props.errors?.to || 0,
})

const localStats = ref(props.stats || {
    total_all: 0,
    total_today: 0,
    critical_count: 0,
    warning_count: 0,
    client_count: 0,
})

const searchQuery = ref(props.filters?.search || '')
const filterLevel = ref(props.filters?.level_filter || '')
const filterTenant = ref(props.filters?.tenant_filter || '')
const startDate = ref(props.filters?.start_date || '')
const endDate = ref(props.filters?.end_date || '')

const isLoading = ref(false)
const isRefreshing = ref(false)
const autoRefresh = ref(false)
let autoRefreshTimer = null
let searchTimeout = null

// Modal States
const showTraceModal = ref(false)
const selectedError = ref(null)

const showClearModal = ref(false)
const clearForm = ref({
    startDate: '',
    endDate: '',
    tenantId: 'all',
})
const isClearing = ref(false)

// Helper: Format DateTime
const formatDateTime = (dateStr) => {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    }).replace(/\./g, ':')
}

// Helper: Shorten Path
const shortenPath = (path) => {
    if (!path) return '-'
    const normalized = path.replace(/\\/g, '/')
    const parts = normalized.split('/')
    if (parts.length > 3) {
        return '.../' + parts.slice(-3).join('/')
    }
    return normalized
}

// Helper: Level Badge Classes & Icons
const getLevelMeta = (level) => {
    const lvl = (level || '').toUpperCase()
    if (['CRITICAL', 'FATAL', 'PARSEERROR', 'PDOEXCEPTION', 'ERROR', 'TYPEERROR'].includes(lvl)) {
        return {
            badge: 'bg-rose-50 text-rose-700 border-rose-200/80',
            dot: 'bg-rose-500',
            icon: 'bi-exclamation-octagon-fill'
        }
    }
    if (['E_WARNING', 'WARNING', 'E_NOTICE', 'NOTICE', 'E_DEPRECATED', 'DEPRECATED'].includes(lvl)) {
        return {
            badge: 'bg-amber-50 text-amber-700 border-amber-200/80',
            dot: 'bg-amber-500',
            icon: 'bi-exclamation-triangle-fill'
        }
    }
    if (lvl.includes('JS') || lvl.includes('PROMISE') || lvl.includes('CLIENT')) {
        return {
            badge: 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
            dot: 'bg-indigo-500',
            icon: 'bi-filetype-js'
        }
    }
    return {
        badge: 'bg-slate-100 text-slate-700 border-slate-200',
        dot: 'bg-slate-500',
        icon: 'bi-bug-fill'
    }
}

// Smart Pagination Links Generator (AGENTS.md Standard)
const getSmartPaginationLinks = (paginationData) => {
    if (!paginationData || paginationData.total === 0) return []
    const current = paginationData.currentPage || 1
    const last = paginationData.lastPage || 1

    const result = []
    // Prev Link
    result.push({
        label: 'Previous',
        page: current > 1 ? current - 1 : null,
        url: current > 1 ? true : null,
        active: false,
        isPrev: true,
        isNext: false,
    })

    if (last <= 7) {
        for (let p = 1; p <= last; p++) {
            result.push({
                label: p.toString(),
                page: p,
                url: true,
                active: p === current,
                isPrev: false,
                isNext: false,
            })
        }
    } else {
        const pagesToShow = new Set([1, last])
        for (let p = current - 1; p <= current + 1; p++) {
            if (p >= 1 && p <= last) pagesToShow.add(p)
        }
        const sortedPages = Array.from(pagesToShow).sort((a, b) => a - b)
        let prevPage = null
        sortedPages.forEach(p => {
            if (prevPage !== null && p - prevPage > 1) {
                result.push({ label: '...', page: null, url: null, active: false, isPrev: false, isNext: false })
            }
            result.push({
                label: p.toString(),
                page: p,
                url: true,
                active: p === current,
                isPrev: false,
                isNext: false,
            })
            prevPage = p
        })
    }

    // Next Link
    result.push({
        label: 'Next',
        page: current < last ? current + 1 : null,
        url: current < last ? true : null,
        active: false,
        isPrev: false,
        isNext: true,
    })

    return result
}

// Fetch Async Data
const fetchErrors = async (page = 1, silent = false) => {
    if (!silent) {
        isLoading.value = true
    } else {
        isRefreshing.value = true
    }
    try {
        const params = {
            page,
            per_page: perPage.value,
            search: searchQuery.value,
            level_filter: filterLevel.value,
            tenant_filter: filterTenant.value,
            start_date: startDate.value,
            end_date: endDate.value,
        }

        const res = await axios.get('/utilitas/error-monitor/data', { params })
        if (res.data?.success) {
            const data = res.data.data
            localErrors.value = data.data || []
            pagination.value = {
                currentPage: data.current_page || 1,
                lastPage: data.last_page || 1,
                total: data.total || 0,
                from: data.from || 0,
                to: data.to || 0,
            }
            if (res.data.stats) {
                localStats.value = res.data.stats
            }
        }
    } catch (err) {
        console.error('Gagal mengambil data error monitor:', err)
    } finally {
        isLoading.value = false
        isRefreshing.value = false
    }
}

const goToPage = (page) => {
    if (!page || page === pagination.value.currentPage || isLoading.value) return
    fetchErrors(page)
}

const onPerPageChange = () => {
    fetchErrors(1)
}

const applyFilters = () => {
    fetchErrors(1)
}

// Toggle Auto Refresh
const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value
    if (autoRefresh.value) {
        if (autoRefreshTimer) clearInterval(autoRefreshTimer)
        autoRefreshTimer = setInterval(() => {
            fetchErrors(pagination.value.currentPage, true)
        }, 10000)
    } else {
        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer)
            autoRefreshTimer = null
        }
    }
}

onUnmounted(() => {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer)
        autoRefreshTimer = null
    }
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }
})

// Debounced Search Handler
const handleSearchDebounce = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 350)
}

const resetFilters = () => {
    searchQuery.value = ''
    filterLevel.value = ''
    filterTenant.value = ''
    startDate.value = ''
    endDate.value = ''
    applyFilters()
}

// Open Trace Detail Modal
const openTraceModal = (err) => {
    selectedError.value = err
    showTraceModal.value = true
}

// Parse Trace JSON Safely
const parsedTrace = computed(() => {
    if (!selectedError.value?.trace) return []
    const t = selectedError.value.trace
    if (Array.isArray(t)) return t
    try {
        const parsed = JSON.parse(t)
        return Array.isArray(parsed) ? parsed : []
    } catch {
        return []
    }
})

// Parse Context JSON Safely
const parsedContext = computed(() => {
    if (!selectedError.value?.context) return null
    const c = selectedError.value.context
    if (typeof c === 'object') return c
    try {
        return JSON.parse(c)
    } catch {
        return null
    }
})

// Delete Single Error
const handleDeleteSingle = async (id) => {
    const swal = getSwal()
    if (swal) {
        const confirm = await swal.fire({
            title: 'Hapus Log Error Ini?',
            text: 'Log error terpilih akan dihapus permanen dari sistem.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
        })
        if (!confirm.isConfirmed) return
    } else {
        if (!confirm('Hapus log error terpilih?')) return
    }

    try {
        const res = await axios.post('/utilitas/error-monitor/delete', { id })
        if (res.data?.success) {
            if (showTraceModal.value) showTraceModal.value = false
            fetchErrors(pagination.value.currentPage)
            if (swal) {
                swal.fire({
                    icon: 'success',
                    title: 'Berhasil Dihapus',
                    timer: 1500,
                    showConfirmButton: false,
                })
            }
        }
    } catch (err) {
        alert(err.response?.data?.error || 'Gagal menghapus log error.')
    }
}

// Open Clear All Modal
const openClearModal = () => {
    clearForm.value = {
        startDate: '',
        endDate: '',
        tenantId: 'all',
    }
    showClearModal.value = true
}

// Execute Clear Logs
const handleClearLogs = async () => {
    const swal = getSwal()
    if (swal) {
        const confirm = await swal.fire({
            title: 'Konfirmasi Bersihkan Log Error?',
            text: 'Seluruh log error sistem yang sesuai dengan kriteria akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Bersihkan',
            cancelButtonText: 'Batal',
        })
        if (!confirm.isConfirmed) return
    } else {
        if (!confirm('Bersihkan log error sistem?')) return
    }

    isClearing.value = true
    try {
        const res = await axios.post('/utilitas/error-monitor/clear', {
            start_date: clearForm.value.startDate,
            end_date: clearForm.value.endDate,
            tenant_id: clearForm.value.tenantId,
        })
        if (res.data?.success) {
            showClearModal.value = false
            fetchErrors(1)
            if (swal) {
                swal.fire({
                    icon: 'success',
                    title: 'Pembersihan Sukses',
                    text: res.data.message || 'Log error berhasil dibersihkan.',
                    timer: 2000,
                    showConfirmButton: false,
                })
            }
        }
    } catch (err) {
        alert(err.response?.data?.error || 'Gagal membersihkan log error.')
    } finally {
        isClearing.value = false
    }
}

// Copy Trace Content to Clipboard
const copyTraceText = () => {
    if (!selectedError.value) return
    const text = `Error: ${selectedError.value.error_level}\nMessage: ${selectedError.value.message}\nFile: ${selectedError.value.file}:${selectedError.value.line}\nURL: ${selectedError.value.request_url}\nTrace: ${typeof selectedError.value.trace === 'string' ? selectedError.value.trace : JSON.stringify(selectedError.value.trace, null, 2)}`
    navigator.clipboard.writeText(text)
    const swal = getSwal()
    if (swal) {
        swal.fire({
            icon: 'success',
            title: 'Disalin ke Clipboard!',
            timer: 1200,
            showConfirmButton: false,
        })
    } else {
        alert('Stack trace berhasil disalin ke clipboard.')
    }
}
</script>

<template>
    <AppLayout title="Error Monitor & System Debugger">
        <div class="space-y-6">
            
            <!-- Header Halaman (Clean Responsive Toolbar) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 sm:gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-start sm:items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-xs shrink-0">
                        <i class="bi bi-bug-fill"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">Error Monitor & System Debugger</h1>
                            <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200/80 font-bold text-[10px] tracking-wide inline-flex items-center">
                                <i class="bi bi-shield-lock-fill me-1"></i>Super Admin Only
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Memantau exception PHP, database query error, dan crash log JavaScript client secara real-time.</p>
                    </div>
                </div>
                
                <!-- Action Buttons: Clean Horizontal Layout -->
                <div class="flex items-center gap-2 shrink-0 flex-wrap">
                    <!-- Auto-Refresh Toggle -->
                    <button 
                        type="button"
                        @click="toggleAutoRefresh"
                        class="h-9 px-3.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-2xs border cursor-pointer whitespace-nowrap"
                        :class="autoRefresh 
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-300 ring-2 ring-emerald-500/20' 
                            : 'bg-white hover:bg-slate-50 text-slate-600 border-slate-200/80'"
                        :title="autoRefresh ? 'Live Auto-Refresh Aktif (Tiap 10 Detik)' : 'Aktifkan Auto-Refresh Otomatis'"
                    >
                        <span class="relative flex h-2 w-2">
                            <span v-if="autoRefresh" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2" :class="autoRefresh ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                        </span>
                        <span>{{ autoRefresh ? 'Live (10s)' : 'Auto-Refresh' }}</span>
                    </button>

                    <!-- Clear All Button -->
                    <button 
                        type="button" 
                        @click="openClearModal"
                        class="h-9 px-3.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer whitespace-nowrap"
                        title="Bersihkan Log Error Sistem"
                    >
                        <i class="bi bi-trash3-fill"></i>
                        <span>Bersihkan Log ({{ localStats.total_all }})</span>
                    </button>

                    <!-- Refresh Button -->
                    <button 
                        type="button" 
                        @click="fetchErrors(pagination.currentPage)"
                        class="h-9 px-3.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/80 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer whitespace-nowrap"
                        title="Muat Ulang Log Error"
                    >
                        <i class="bi bi-arrow-clockwise" :class="{ 'animate-spin': isLoading || isRefreshing }"></i>
                        <span>Segarkan</span>
                    </button>
                </div>
            </div>

            <!-- 4 Kartu Metrik KPI Error -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                
                <!-- Card 1: Total Error -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Error Sistem</span>
                        <h3 class="text-2xl font-extrabold text-slate-800 mt-1 font-mono">{{ localStats.total_all }}</h3>
                        <span class="text-[10px] text-indigo-600 font-semibold flex items-center gap-1 mt-0.5">
                            <i class="bi bi-calendar-check"></i> {{ localStats.total_today }} Terjadi Hari Ini
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-bug"></i>
                    </div>
                </div>

                <!-- Card 2: Fatal & Critical -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Critical & Fatal</span>
                        <h3 class="text-2xl font-extrabold text-rose-600 mt-1 font-mono">{{ localStats.critical_count }}</h3>
                        <span class="text-[10px] text-rose-500 font-medium mt-0.5 block">PDO & Core Exceptions</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                </div>

                <!-- Card 3: Warnings & Notices -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Warnings & Notices</span>
                        <h3 class="text-2xl font-extrabold text-amber-600 mt-1 font-mono">{{ localStats.warning_count }}</h3>
                        <span class="text-[10px] text-slate-400 font-medium mt-0.5 block">Peringatan PHP & Runtime</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>

                <!-- Card 4: JS & Client Exceptions -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">JS & Client Errors</span>
                        <h3 class="text-2xl font-extrabold text-purple-600 mt-1 font-mono">{{ localStats.client_count }}</h3>
                        <span class="text-[10px] text-slate-400 font-medium mt-0.5 block">Browser Frontend Tracker</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-filetype-js"></i>
                    </div>
                </div>

            </div>

            <!-- Main Content: 3-Part Unified Box Card (Standar Baku SINTA SaaS) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                
                <!-- 1. Bagian 1: Filter Bar Atas (Standar Baku AGENTS.md) -->
                <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
                    <form @submit.prevent="applyFilters" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
                        
                        <!-- Filter Level -->
                        <div class="w-36 sm:w-40 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Level Error</label>
                            <select 
                                v-model="filterLevel" 
                                @change="applyFilters" 
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition font-mono"
                            >
                                <option value="">-- Semua Level --</option>
                                <option v-for="l in levelsList" :key="l.error_level" :value="l.error_level">
                                    {{ l.error_level }} ({{ l.count }})
                                </option>
                            </select>
                        </div>

                        <!-- Filter Sekolah (Tenant) -->
                        <div class="w-44 sm:w-52 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Instansi Sekolah</label>
                            <select 
                                v-model="filterTenant" 
                                @change="applyFilters" 
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                            >
                                <option value="">-- Semua Sekolah --</option>
                                <option value="system">Sistem Pusat (Global)</option>
                                <option v-for="t in tenantsList" :key="t.id" :value="t.id">
                                    {{ t.nama_sekolah }}
                                </option>
                            </select>
                        </div>

                        <!-- Rentang Tanggal -->
                        <div class="shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Rentang Tanggal</label>
                            <div class="flex items-center gap-1.5 bg-white border border-slate-200 hover:border-slate-300 rounded-xl px-2.5 h-9 shadow-2xs">
                                <i class="bi bi-calendar-range text-slate-400 text-xs shrink-0"></i>
                                <input 
                                    type="date" 
                                    v-model="startDate" 
                                    @change="applyFilters"
                                    class="text-xs font-medium text-slate-700 bg-transparent focus:outline-none border-none p-0 w-28 cursor-pointer"
                                    title="Tanggal Mulai"
                                />
                                <span class="text-slate-300 text-xs font-bold shrink-0">—</span>
                                <input 
                                    type="date" 
                                    v-model="endDate" 
                                    @change="applyFilters"
                                    class="text-xs font-medium text-slate-700 bg-transparent focus:outline-none border-none p-0 w-28 cursor-pointer"
                                    title="Tanggal Akhir"
                                />
                            </div>
                        </div>

                        <!-- Search Input (Proposional w-64 s.d. w-80) -->
                        <div class="w-64 sm:w-72 md:w-80 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Pencarian</label>
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                <input 
                                    type="text" 
                                    v-model="searchQuery" 
                                    @input="handleSearchDebounce"
                                    placeholder="Cari pesan error, file, URL, IP..." 
                                    class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs" 
                                />
                                <button 
                                    v-if="searchQuery" 
                                    @click="searchQuery = ''; applyFilters()" 
                                    type="button" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer"
                                    title="Hapus Pencarian"
                                >
                                    <i class="bi bi-x-circle-fill text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Tombol Cari & Reset -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button 
                                type="submit" 
                                class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                            >
                                <i class="bi bi-search text-xs"></i> <span>Cari</span>
                            </button>
                            <button 
                                type="button" 
                                @click="resetFilters" 
                                class="h-9 px-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs transition shadow-2xs whitespace-nowrap cursor-pointer"
                            >
                                Reset
                            </button>
                        </div>

                    </form>
                </div>

                <!-- 2. Bagian 2: Tabel Data (Standar Kolom & Layout Baku) -->
                <div class="overflow-x-auto relative min-h-[300px]">
                    <!-- Loading Overlay -->
                    <div v-if="isLoading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                        <div class="flex items-center gap-2 text-rose-600 font-bold text-xs">
                            <i class="bi bi-arrow-repeat animate-spin text-lg"></i>
                            <span>Memuat data error sistem...</span>
                        </div>
                    </div>

                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                                <th scope="col" class="py-3 px-3 w-12 text-center whitespace-nowrap">NO</th>
                                <th scope="col" class="py-3 px-3.5 w-40 whitespace-nowrap">WAKTU</th>
                                <th scope="col" class="py-3 px-3.5 w-36 whitespace-nowrap">LEVEL</th>
                                <th scope="col" class="py-3 px-3.5 min-w-[280px]">PESAN ERROR & INSTANSI</th>
                                <th scope="col" class="py-3 px-3.5 min-w-[220px]">FILE : BARIS</th>
                                <th scope="col" class="py-3 px-3.5 w-48 whitespace-nowrap">REQUEST & IP</th>
                                <th scope="col" class="py-3 px-3.5 w-28 text-center whitespace-nowrap sticky right-0 bg-slate-50 shadow-[-4px_0_6px_rgba(15,23,42,0.04)]">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            
                            <!-- Empty State -->
                            <tr v-if="localErrors.length === 0 && !isLoading">
                                <td colspan="7" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shadow-inner">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <span class="text-sm font-bold text-emerald-800">Semua Sistem Berjalan Normal!</span>
                                        <span class="text-xs text-slate-400 max-w-sm">Tidak ada error sistem yang tertangkap sesuai filter saat ini.</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Data Rows -->
                            <tr 
                                v-for="(err, idx) in localErrors" 
                                :key="err.id"
                                class="hover:bg-blue-50/40 transition border-b border-slate-100 group"
                            >
                                <!-- Kolom NO -->
                                <td class="py-3 px-3 text-center font-mono text-slate-400 text-xs w-12 whitespace-nowrap">
                                    {{ (pagination.currentPage - 1) * perPage + idx + 1 }}
                                </td>

                                <!-- Kolom WAKTU -->
                                <td class="py-3 px-3.5 whitespace-nowrap font-mono text-xs text-slate-600 w-40">
                                    {{ formatDateTime(err.created_at) }}
                                </td>

                                <!-- Kolom LEVEL -->
                                <td class="py-3 px-3.5 whitespace-nowrap w-36">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[11px] font-bold font-mono whitespace-nowrap"
                                        :class="getLevelMeta(err.error_level).badge"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getLevelMeta(err.error_level).dot"></span>
                                        <span>{{ err.error_level }}</span>
                                    </span>
                                </td>

                                <!-- Kolom PESAN ERROR & INSTANSI -->
                                <td class="py-3 px-3.5 min-w-[280px] max-w-md">
                                    <div class="font-semibold text-slate-800 text-xs font-mono line-clamp-2 leading-relaxed" :title="err.message">
                                        {{ err.message }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-medium border border-slate-200 truncate max-w-[240px]">
                                            <i class="bi bi-building shrink-0 text-slate-400"></i>
                                            <span class="truncate">{{ err.nama_sekolah || 'Pusat Kendali SaaS (Global)' }}</span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Kolom FILE : BARIS -->
                                <td class="py-3 px-3.5 min-w-[220px] max-w-xs">
                                    <div class="font-mono text-xs text-indigo-600 font-medium truncate" :title="err.file">
                                        {{ shortenPath(err.file) }}
                                    </div>
                                    <div class="mt-0.5">
                                        <span class="inline-block font-mono text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200/80 font-bold whitespace-nowrap">
                                            Line: {{ err.line || '?' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Kolom REQUEST & IP -->
                                <td class="py-3 px-3.5 whitespace-nowrap w-48">
                                    <div class="flex items-center gap-1 mb-0.5">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold font-mono text-[10px] border border-slate-200 shrink-0">
                                            {{ err.request_method || 'GET' }}
                                        </span>
                                        <span class="font-mono text-xs text-slate-600 truncate max-w-[140px]" :title="err.request_url">
                                            {{ err.request_url || '-' }}
                                        </span>
                                    </div>
                                    <div class="font-mono text-[10px] text-slate-400">
                                        IP: {{ err.ip_address || '::1' }}
                                    </div>
                                </td>

                                <!-- Kolom AKSI (Sticky Right) -->
                                <td class="py-3 px-3.5 text-center whitespace-nowrap sticky right-0 bg-white group-hover:bg-blue-50/40 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] transition w-28">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="openTraceModal(err)"
                                            class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/80 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer whitespace-nowrap"
                                            title="Lihat Stack Trace Lengkap"
                                        >
                                            <i class="bi bi-bug-fill"></i>
                                            <span>Trace</span>
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="handleDeleteSingle(err.id)"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition shadow-2xs cursor-pointer shrink-0"
                                            title="Hapus Log Ini"
                                        >
                                            <i class="bi bi-trash3-fill text-xs"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 3. Bagian 3: Footer Pagination (Smart Windowing & Responsif Sesuai AGENTS.md) -->
                <div v-if="pagination.total > 0" 
                     class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
                    
                    <!-- Info Tampilkan Baris & Dropdown Per-Page -->
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
                        <span>Tampilkan</span>
                        <select 
                            v-model="perPage" 
                            @change="onPerPageChange" 
                            class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        >
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="20">20</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                        <span class="whitespace-nowrap">baris per halaman</span>
                        <span class="text-slate-300 hidden sm:inline">|</span>
                        <span class="whitespace-nowrap">
                            Menampilkan <span class="font-bold text-slate-800">{{ pagination.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ pagination.to || pagination.total }}</span> dari <span class="font-bold text-slate-800">{{ pagination.total }}</span> baris
                        </span>
                    </div>

                    <!-- Pagination Links (Smart Windowing & Compact Chevrons) -->
                    <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
                        <template v-for="(link, i) in getSmartPaginationLinks(pagination)" :key="i">
                            <button 
                                v-if="link.url && !link.active" 
                                type="button"
                                @click="goToPage(link.page)"
                                class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer"
                                :title="link.isPrev ? 'Halaman Sebelumnya' : (link.isNext ? 'Halaman Berikutnya' : 'Halaman ' + link.label)"
                            >
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                                <span v-else>{{ link.label }}</span>
                            </button>
                            <span 
                                v-else-if="link.active"
                                class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-blue-600 text-white shadow-xs"
                            >
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                                <span v-else>{{ link.label }}</span>
                            </span>
                            <span 
                                v-else 
                                class="min-w-[32px] h-8 px-2 text-xs font-bold flex items-center justify-center text-slate-400"
                            >
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
                                <span v-else>{{ link.label }}</span>
                            </span>
                        </template>
                    </div>

                </div>

            </div>

        </div>

        <!-- MODAL 1: Detail Stack Trace Viewer -->
        <div v-if="showTraceModal && selectedError" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-4 flex items-center justify-between text-white shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/20 border border-rose-400/30 text-rose-400 flex items-center justify-center text-xl">
                            <i class="bi bi-bug-fill"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold flex items-center gap-2 m-0 text-white">
                                Stack Trace & Crash Report
                                <span class="px-2 py-0.5 rounded-md bg-rose-500 text-white text-[10px] font-mono font-bold">
                                    {{ selectedError.error_level }}
                                </span>
                            </h3>
                            <p class="text-xs text-slate-300 font-mono mb-0 mt-0.5">
                                {{ selectedError.file }} : Line {{ selectedError.line || '?' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="showTraceModal = false" class="text-slate-400 hover:text-white text-xl cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-5">
                    
                    <!-- Message Box -->
                    <div>
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-1.5 flex items-center gap-1.5">
                            <i class="bi bi-chat-square-text-fill text-rose-600"></i> Pesan Error Lengkap:
                        </span>
                        <div class="p-3.5 bg-rose-50/50 border border-rose-200 rounded-2xl font-mono text-xs text-rose-900 break-all leading-relaxed whitespace-pre-wrap">
                            {{ selectedError.message }}
                        </div>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs space-y-1">
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Request Context</span>
                            <div class="font-mono text-slate-800 break-all">
                                <strong>{{ selectedError.request_method || 'GET' }}</strong>: {{ selectedError.request_url || '-' }}
                            </div>
                            <div class="text-[11px] text-slate-500">
                                IP: <code class="text-slate-700">{{ selectedError.ip_address }}</code> | Waktu: {{ formatDateTime(selectedError.created_at) }}
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs space-y-1">
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">User Agent & Instansi</span>
                            <div class="text-slate-800 font-semibold">
                                {{ selectedError.nama_sekolah || 'Pusat Kendali SaaS (Global)' }}
                            </div>
                            <div class="text-[10px] text-slate-500 truncate" :title="selectedError.user_agent">
                                {{ selectedError.user_agent || '-' }}
                            </div>
                        </div>
                    </div>

                    <!-- Stack Frames Table -->
                    <div>
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-2 flex items-center gap-1.5">
                            <i class="bi bi-list-nested text-indigo-600"></i> Call Stack Execution Frames ({{ parsedTrace.length }} Frames):
                        </span>

                        <div class="border border-slate-200 rounded-2xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200 font-bold text-slate-600 text-[11px]">
                                    <tr>
                                        <th class="py-2.5 px-3 w-10 text-center">#</th>
                                        <th class="py-2.5 px-3">Function / Method Call</th>
                                        <th class="py-2.5 px-3">Source File & Line</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                                    <tr v-if="parsedTrace.length === 0">
                                        <td colspan="3" class="text-center py-6 text-slate-400 font-sans">
                                            Tidak ada call stack frames yang terekam.
                                        </td>
                                    </tr>
                                    <tr v-else v-for="(frame, i) in parsedTrace" :key="i" class="hover:bg-slate-50/60">
                                        <td class="py-2 px-3 text-center text-slate-400">{{ i }}</td>
                                        <td class="py-2 px-3 text-slate-800">
                                            <span v-if="frame.class" class="text-indigo-600 font-semibold">{{ frame.class }}</span>
                                            <span v-if="frame.type" class="text-slate-400">{{ frame.type }}</span>
                                            <span class="text-amber-700 font-bold">{{ frame.function }}</span>
                                            <span class="text-slate-400">()</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-600 break-all">
                                            <span :title="frame.file">{{ shortenPath(frame.file) }}</span>
                                            <span v-if="frame.line" class="ms-1 px-1.5 py-0.2 rounded bg-amber-50 text-amber-700 border border-amber-200/80 font-bold text-[10px]">
                                                :{{ frame.line }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
                    <button 
                        type="button" 
                        @click="copyTraceText" 
                        class="px-3.5 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer"
                    >
                        <i class="bi bi-clipboard"></i>
                        <span>Salin Stack Trace</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="handleDeleteSingle(selectedError.id)" 
                            class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <i class="bi bi-trash3"></i>
                            <span>Hapus Log Ini</span>
                        </button>
                        <button 
                            type="button" 
                            @click="showTraceModal = false" 
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL 2: Pembersihan Log Error (Clear Modal) -->
        <div v-if="showClearModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-rose-600 to-pink-600 px-6 py-4 flex items-center justify-between text-white">
                    <div>
                        <h3 class="text-base font-bold flex items-center gap-2 m-0">
                            <i class="bi bi-trash3-fill"></i>
                            Pembersihan Log Error Sistem
                        </h3>
                        <p class="text-xs text-rose-100 mb-0 mt-0.5">Menghapus riwayat crash report untuk efisiensi penyimpanan database PostgreSQL.</p>
                    </div>
                    <button type="button" @click="showClearModal = false" class="text-white/80 hover:text-white text-xl cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form @submit.prevent="handleClearLogs" class="p-6 space-y-4">
                    
                    <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-start gap-2.5 text-xs text-amber-800 font-medium">
                        <i class="bi bi-exclamation-triangle-fill text-base text-amber-600 shrink-0"></i>
                        <span>
                            Tindakan ini akan <strong>menghapus permanen</strong> log error pada rentang atau instansi yang dipilih. Tindakan ini tidak dapat dibatalkan.
                        </span>
                    </div>

                    <!-- Target Tenant -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Target Lingkup Instansi</label>
                        <select 
                            v-model="clearForm.tenantId" 
                            class="h-10 px-3 w-full rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:outline-none"
                        >
                            <option value="all">Semua Sekolah & Sistem (Global)</option>
                            <option value="system">Hanya Sistem (Global)</option>
                            <option v-for="t in tenantsList" :key="t.id" :value="t.id">
                                {{ t.nama_sekolah }} ({{ t.npsn }})
                            </option>
                        </select>
                    </div>

                    <!-- Rentang Tanggal (Opsional) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700">Tanggal Mulai (Opsional)</label>
                            <input 
                                type="date" 
                                v-model="clearForm.startDate" 
                                class="h-10 px-3 w-full rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:outline-none"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700">Tanggal Akhir (Opsional)</label>
                            <input 
                                type="date" 
                                v-model="clearForm.endDate" 
                                class="h-10 px-3 w-full rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:outline-none"
                            />
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 m-0">Biarkan rentang tanggal kosong jika ingin menghapus seluruh log.</p>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showClearModal = false" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="isClearing"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50"
                        >
                            <i class="bi bi-trash3" :class="{ 'animate-spin': isClearing }"></i>
                            <span>{{ isClearing ? 'Sedang Menghapus...' : 'Eksekusi Pembersihan' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </AppLayout>
</template>
