<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const getSwal = () => typeof window !== 'undefined' && window.Swal ? window.Swal : null

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({})
    },
    tenants: {
        type: Array,
        default: () => []
    },
    networkInterfaces: {
        type: Array,
        default: () => []
    },
    timestamp: {
        type: String,
        default: ''
    },
    user_role: {
        type: String,
        default: 'super_admin'
    }
})

// Tab Navigation
const activeTab = ref('resources')

// Reactive State
const localMetrics = ref(props.metrics || {})
const localTenants = ref(props.tenants || [])
const localNetworkInterfaces = ref(props.networkInterfaces || [])
const lastUpdated = ref(props.timestamp || '--:--:--')

const isLoading = ref(false)
const autoRefresh = ref(true)
const selectedInterval = ref(1800) // Default 30 Menit (1800 detik)
const countdown = ref(1800)
let pollTimer = null
let countTimer = null

// Formatted Countdown string (e.g. 29m 55s or 45s)
const formattedCountdown = computed(() => {
    const mins = Math.floor(countdown.value / 60)
    const secs = countdown.value % 60
    if (mins > 0) {
        return `${mins}m ${secs < 10 ? '0' + secs : secs}s`
    }
    return `${secs}s`
})

// Filter & Sort State
const searchQuery = ref('')
const sortBy = ref('')
const sortDir = ref('desc')

// Update Server State
const isUpdating = ref(false)
const updateLog = ref('')

// Network Modal State
const showNetworkModal = ref(false)
const formSubmitting = ref(false)
const formNetwork = ref({
    interface: '',
    dhcp: true,
    ipv4: '',
    gateway: '',
    dns: ''
})

// Color Helpers
const getUsageColor = (pct) => {
    const p = parseFloat(pct) || 0
    if (p >= 80) return '#ef4444' // Red
    if (p >= 60) return '#f59e0b' // Amber/Yellow
    return '#10b981'             // Emerald/Green
}

const getUsageBadgeClass = (pct) => {
    const p = parseFloat(pct) || 0
    if (p >= 80) return 'bg-rose-50 text-rose-700 border-rose-200'
    if (p >= 60) return 'bg-amber-50 text-amber-700 border-amber-200'
    return 'bg-emerald-50 text-emerald-700 border-emerald-200'
}

// Filtered & Sorted Tenants
const filteredTenants = computed(() => {
    let list = localTenants.value || []

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim()
        list = list.filter(t => 
            (t.nama_sekolah && t.nama_sekolah.toLowerCase().includes(q)) ||
            (t.npsn && t.npsn.includes(q)) ||
            (t.subdomain && t.subdomain.toLowerCase().includes(q))
        )
    }

    if (sortBy.value) {
        list = [...list].sort((a, b) => {
            const va = parseFloat(a[sortBy.value]) || 0
            const vb = parseFloat(b[sortBy.value]) || 0
            return sortDir.value === 'desc' ? vb - va : va - vb
        })
    }

    return list
})

// Computed Summaries
const totalActiveSessions = computed(() => {
    return (localTenants.value || []).reduce((s, t) => s + (t.active_sessions || 0), 0)
})

const totalDiskFormatted = computed(() => {
    const mb = (localTenants.value || []).reduce((s, t) => s + parseFloat(t.disk_mb || 0), 0)
    if (mb >= 1024) {
        return (mb / 1024).toFixed(2) + ' GB'
    }
    return mb.toFixed(1) + ' MB'
})

const totalSiswa = computed(() => {
    return (localTenants.value || []).reduce((s, t) => s + (t.total_siswa || 0), 0)
})

// Toggle Sort
const toggleSort = (col) => {
    if (sortBy.value === col) {
        sortDir.value = sortDir.value === 'desc' ? 'asc' : 'desc'
    } else {
        sortBy.value = col
        sortDir.value = 'desc'
    }
}

// Fetch Data API
const fetchData = async (isManual = false) => {
    if (isLoading.value) return
    isLoading.value = true

    try {
        const res = await axios.get('/sistem/server-monitor/data')
        if (res.data.success) {
            localMetrics.value = res.data.global_metrics || {}
            localTenants.value = res.data.tenants || []
            localNetworkInterfaces.value = res.data.network_interfaces || []
            lastUpdated.value = res.data.timestamp ? new Date(res.data.timestamp.replace(/-/g, '/')).toLocaleTimeString('id-ID') : '--:--:--'
            countdown.value = selectedInterval.value
        }
    } catch (err) {
        console.error('[ServerMonitor] Fetch error:', err)
        if (isManual) {
            const swal = getSwal()
            if (swal) {
                swal.fire({
                    icon: 'error',
                    title: 'Gagal Memperbarui Data',
                    text: err.response?.data?.error || err.message || 'Terjadi kesalahan saat memuat metrik.',
                    confirmButtonColor: '#2563eb',
                })
            }
        }
    } finally {
        isLoading.value = false
    }
}

// Polling Lifecycle
const startPolling = () => {
    stopPolling()
    pollTimer = setInterval(() => {
        if (autoRefresh.value && !isLoading.value) {
            fetchData()
        }
    }, selectedInterval.value * 1000)

    countTimer = setInterval(() => {
        if (autoRefresh.value) {
            if (countdown.value > 1) {
                countdown.value--
            } else {
                countdown.value = selectedInterval.value
            }
        }
    }, 1000)
}

const stopPolling = () => {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    if (countTimer) { clearInterval(countTimer); countTimer = null; }
}

const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value
    if (autoRefresh.value) {
        countdown.value = selectedInterval.value
        startPolling()
    } else {
        stopPolling()
    }
}

const changeInterval = (seconds) => {
    selectedInterval.value = Number(seconds) || 1800
    countdown.value = selectedInterval.value
    if (autoRefresh.value) {
        startPolling()
    }
}

// Network Config Modal
const openConfigModal = (net) => {
    formNetwork.value = {
        interface: net.interface,
        dhcp: Boolean(net.dhcp),
        ipv4: net.ipv4 ? `${net.ipv4}${net.cidr ? '/' + net.cidr : ''}` : '',
        gateway: net.gateway || '',
        dns: Array.isArray(net.dns) ? net.dns.join(', ') : (net.dns || '')
    }
    showNetworkModal.value = true
}

const closeConfigModal = () => {
    showNetworkModal.value = false
    formNetwork.value = { interface: '', dhcp: true, ipv4: '', gateway: '', dns: '' }
}

const submitNetworkConfig = async () => {
    formSubmitting.value = true
    const swal = getSwal()
    try {
        const res = await axios.post('/sistem/server-monitor/save-network', formNetwork.value)
        if (res.data.success) {
            if (swal) {
                swal.fire({
                    icon: 'success',
                    title: 'Konfigurasi Tersimpan',
                    text: res.data.message || 'Konfigurasi jaringan berhasil diperbarui.',
                    confirmButtonColor: '#2563eb'
                })
            }
            closeConfigModal()
            fetchData(true)
        } else {
            if (swal) {
                swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan',
                    text: res.data.error || 'Terjadi kesalahan sistem.',
                    confirmButtonColor: '#2563eb'
                })
            }
        }
    } catch (err) {
        if (swal) {
            swal.fire({
                icon: 'error',
                title: 'Kesalahan Konfigurasi',
                text: err.response?.data?.error || err.message || 'Gagal menyimpan konfigurasi adapter.',
                confirmButtonColor: '#2563eb'
            })
        }
    } finally {
        formSubmitting.value = false
    }
}

// Update Server
const triggerUpdate = async () => {
    if (isUpdating.value) return

    const swal = getSwal()
    if (swal) {
        const result = await swal.fire({
            title: 'Jalankan Update Server?',
            text: 'Proses ini akan menjalankan script deployment otomatis, git pull terbaru, dan migrasi database.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Jalankan Update!',
            cancelButtonText: 'Batal'
        })
        if (!result.isConfirmed) return
    } else {
        if (!confirm('Jalankan Update Server? Proses ini akan menjalankan script deployment otomatis.')) return
    }

    isUpdating.value = true
    updateLog.value = '> Memulai proses update server...\n> Meminta eksekusi bash script deploy.sh... (menunggu respons server)\n'

    try {
        const res = await axios.post('/sistem/server-monitor/update-server')
        updateLog.value += '\n' + (res.data.output || 'Eksekusi selesai.') + '\n\n> Proses deployment selesai.'
        if (res.data.success) {
            if (swal) {
                swal.fire({
                    icon: 'success',
                    title: 'Deployment Berhasil',
                    text: res.data.message || 'Perintah pembaruan server selesai dijalankan.',
                    confirmButtonColor: '#2563eb'
                })
            }
        }
    } catch (err) {
        updateLog.value += '\n[ERROR] ' + (err.response?.data?.error || err.message || 'Terjadi kesalahan eksekusi.')
        if (swal) {
            swal.fire({
                icon: 'error',
                title: 'Gagal Menjalankan Update',
                text: err.response?.data?.error || 'Periksa izin eksekusi script deploy.sh di server.',
                confirmButtonColor: '#2563eb'
            })
        }
    } finally {
        isUpdating.value = false
    }
}

// Reset Filters
const resetFilters = () => {
    searchQuery.value = ''
    sortBy.value = ''
    sortDir.value = 'desc'
}

onMounted(() => {
    startPolling()
})

onUnmounted(() => {
    stopPolling()
})
</script>

<template>
    <Head title="Server & Resource Monitor" />

    <AppLayout title="Server & Resource Monitor">
        <div class="space-y-6">
            
            <!-- Page Header Card & Action Bar -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 sm:gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-inner">
                            <i class="bi bi-hdd-network-fill"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">Server & Resource Monitor</h1>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                                    Super Admin Platform
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Pemantauan telemetri kesehatan server global dan kuota penggunaan penyimpanan multi-tenant.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Controls: Live Status & Refresh -->
                <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                    
                    <!-- Interval Selector Dropdown -->
                    <div class="relative">
                        <select 
                            :value="selectedInterval" 
                            @change="changeInterval($event.target.value)"
                            class="h-9 pl-3 pr-8 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition shadow-2xs cursor-pointer appearance-none"
                            title="Pilih Interval Pembaruan Otomatis"
                        >
                            <option :value="1800">Setiap 30 Menit (Default)</option>
                            <option :value="900">Setiap 15 Menit</option>
                            <option :value="300">Setiap 5 Menit</option>
                            <option :value="60">Setiap 1 Menit</option>
                            <option :value="30">Setiap 30 Detik</option>
                            <option :value="10">Setiap 10 Detik</option>
                        </select>
                        <i class="bi bi-clock-history absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none"></i>
                    </div>

                    <!-- Live Indicator & Auto-refresh Toggle -->
                    <button 
                        type="button" 
                        @click="toggleAutoRefresh"
                        class="h-9 px-3 rounded-xl border text-xs font-bold transition shadow-2xs flex items-center gap-2 cursor-pointer select-none"
                        :class="autoRefresh ? 'bg-emerald-50/80 border-emerald-200 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-50 border-slate-200 text-slate-500 hover:bg-slate-100'"
                        :title="autoRefresh ? 'Matikan Auto-Refresh' : 'Aktifkan Auto-Refresh'"
                    >
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="autoRefresh ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                        <span v-if="autoRefresh">Auto-Refresh · {{ formattedCountdown }}</span>
                        <span v-else>Auto-Refresh: Nonaktif</span>
                    </button>

                    <!-- Manual Refresh Button -->
                    <button 
                        type="button" 
                        @click="fetchData(true)" 
                        :disabled="isLoading"
                        class="h-9 px-3.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 disabled:opacity-50 disabled:pointer-events-none cursor-pointer"
                        title="Segarkan Metrik Server Sekarang"
                    >
                        <i class="bi bi-arrow-clockwise text-sm" :class="{ 'animate-spin': isLoading }"></i>
                        <span>Segarkan</span>
                    </button>

                </div>
            </div>

            <!-- Standardized 3-Way Horizontal NavTabs (Modern Pill Layout) -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <!-- Tombol Panah Kiri -->
                    <button 
                        type="button" 
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5 cursor-pointer" 
                        onclick="document.getElementById('serverMonitorTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                        title="Geser ke Kiri"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <!-- Container Deretan Tab -->
                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="serverMonitorTabs" role="tablist">
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center cursor-pointer" 
                                    :class="activeTab === 'resources' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="activeTab = 'resources'"
                                >
                                    <i class="bi bi-cpu-fill me-2 text-sm"></i> Resource Monitor
                                </button>
                            </li>
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center cursor-pointer" 
                                    :class="activeTab === 'network' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="activeTab = 'network'"
                                >
                                    <i class="bi bi-hdd-network-fill me-2 text-sm"></i> Network Interfaces
                                </button>
                            </li>
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center cursor-pointer" 
                                    :class="activeTab === 'update' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="activeTab = 'update'"
                                >
                                    <i class="bi bi-cloud-arrow-down-fill me-2 text-sm"></i> Update & Deployment
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tombol Panah Kanan -->
                    <button 
                        type="button" 
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5 cursor-pointer" 
                        onclick="document.getElementById('serverMonitorTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                        title="Geser ke Kanan"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- TAB 1: RESOURCE MONITOR -->
            <div v-show="activeTab === 'resources'" class="space-y-6 animate-in fade-in duration-200">
                
                <!-- 4 Global Server KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Card 1: CPU Load -->
                    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">CPU Load Avg</span>
                                <h3 class="text-2xl font-black mt-1 font-mono" :style="{ color: getUsageColor(localMetrics.cpu?.usage_percent) }">
                                    {{ localMetrics.cpu?.available ? (localMetrics.cpu?.usage_percent + '%') : 'Normal' }}
                                </h3>
                            </div>
                            <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl shrink-0 shadow-inner" :style="{ background: localMetrics.cpu?.usage_percent >= 80 ? '#fef2f2' : (localMetrics.cpu?.usage_percent >= 60 ? '#fffbeb' : '#f0fdf4') }">
                                <i class="bi bi-cpu-fill" :style="{ color: getUsageColor(localMetrics.cpu?.usage_percent) }"></i>
                            </div>
                        </div>
                        <div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden mb-2">
                                <div 
                                    class="h-full rounded-full transition-all duration-500" 
                                    :style="{ width: ((localMetrics.cpu?.usage_percent || 15) + '%'), background: getUsageColor(localMetrics.cpu?.usage_percent) }"
                                ></div>
                            </div>
                            <div class="flex justify-between items-center text-[11px] text-slate-500 font-mono">
                                <span>Load: {{ localMetrics.cpu?.load_1 || '0.2' }} / {{ localMetrics.cpu?.load_5 || '0.3' }}</span>
                                <span>{{ localMetrics.cpu?.cpu_count || 4 }} Cores</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: RAM Memory -->
                    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Memory (RAM)</span>
                                <h3 class="text-2xl font-black mt-1 font-mono" :style="{ color: getUsageColor(localMetrics.ram?.usage_percent) }">
                                    {{ localMetrics.ram?.usage_percent ? (localMetrics.ram?.usage_percent + '%') : 'Normal' }}
                                </h3>
                            </div>
                            <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl shrink-0 shadow-inner" :style="{ background: localMetrics.ram?.usage_percent >= 80 ? '#fef2f2' : (localMetrics.ram?.usage_percent >= 60 ? '#fffbeb' : '#f0fdf4') }">
                                <i class="bi bi-memory" :style="{ color: getUsageColor(localMetrics.ram?.usage_percent) }"></i>
                            </div>
                        </div>
                        <div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden mb-2">
                                <div 
                                    class="h-full rounded-full transition-all duration-500" 
                                    :style="{ width: ((localMetrics.ram?.usage_percent || 20) + '%'), background: getUsageColor(localMetrics.ram?.usage_percent) }"
                                ></div>
                            </div>
                            <div class="flex justify-between items-center text-[11px] text-slate-500 font-mono">
                                <span>Terpakai: {{ localMetrics.ram?.used_gb || 1.2 }} GB</span>
                                <span>Total: {{ localMetrics.ram?.total_gb || 8.0 }} GB</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Main Disk -->
                    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Main Storage Disk</span>
                                <h3 class="text-2xl font-black mt-1 font-mono" :style="{ color: getUsageColor(localMetrics.disk?.usage_percent) }">
                                    {{ localMetrics.disk?.usage_percent ? (localMetrics.disk?.usage_percent + '%') : 'Normal' }}
                                </h3>
                            </div>
                            <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl shrink-0 shadow-inner" :style="{ background: localMetrics.disk?.usage_percent >= 80 ? '#fef2f2' : (localMetrics.disk?.usage_percent >= 60 ? '#fffbeb' : '#f0fdf4') }">
                                <i class="bi bi-hdd-fill" :style="{ color: getUsageColor(localMetrics.disk?.usage_percent) }"></i>
                            </div>
                        </div>
                        <div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden mb-2">
                                <div 
                                    class="h-full rounded-full transition-all duration-500" 
                                    :style="{ width: ((localMetrics.disk?.usage_percent || 10) + '%'), background: getUsageColor(localMetrics.disk?.usage_percent) }"
                                ></div>
                            </div>
                            <div class="flex justify-between items-center text-[11px] text-slate-500 font-mono">
                                <span>Free: {{ localMetrics.disk?.free_gb || 0 }} GB</span>
                                <span>Total: {{ localMetrics.disk?.total_gb || 0 }} GB</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Server Environment & Uptime -->
                    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-1">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Server Environment</span>
                                <h4 class="text-sm font-bold text-slate-800 mt-1 flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-mono text-[10px] font-black border border-blue-200">
                                        {{ localMetrics.os || 'Windows' }}
                                    </span>
                                    <span class="font-mono text-xs text-slate-600">{{ localMetrics.db_version || 'PostgreSQL 16' }}</span>
                                </h4>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0 shadow-inner">
                                <i class="bi bi-shield-check"></i>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-slate-100 space-y-1 text-[11px] text-slate-500 font-mono">
                            <div class="flex justify-between items-center">
                                <span>PHP / Laravel:</span>
                                <strong class="text-slate-700 font-bold">PHP {{ localMetrics.php_version?.substring(0, 6) }} (v{{ localMetrics.laravel_version }})</strong>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Server Uptime:</span>
                                <strong class="text-emerald-600 font-bold">{{ localMetrics.uptime?.human || 'Online' }}</strong>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Server IP:</span>
                                <strong class="text-slate-700">{{ localMetrics.server_ip || '127.0.0.1' }}</strong>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Main Content: Tenant Resource Table (3-Part Data Table Standard) -->
                <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                    
                    <!-- 1. Bagian 1: Top Filter Bar (Bilah Filter Atas) -->
                    <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
                        <form @submit.prevent class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
                            
                            <!-- Filter Pengurutan (Sort Context) -->
                            <div class="w-48 shrink-0">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Urutan Data</label>
                                <div class="relative">
                                    <select 
                                        v-model="sortBy" 
                                        @change="sortDir = 'desc'"
                                        class="h-9 w-full pl-3 pr-8 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition shadow-2xs cursor-pointer appearance-none"
                                    >
                                        <option value="">Urutkan Default (Nama)</option>
                                        <option value="active_sessions">Sesi Aktif Terbanyak ↓</option>
                                        <option value="disk_mb">Penyimpanan Terbesar ↓</option>
                                        <option value="total_siswa">Total Siswa Terbanyak ↓</option>
                                        <option value="quota_percent">Persentase Kuota Terbesar ↓</option>
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                                </div>
                            </div>

                            <!-- Search Input with Clear Button -->
                            <div class="w-64 sm:w-72 md:w-80 shrink-0">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Pencarian Sekolah</label>
                                <div class="relative">
                                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input 
                                        type="text" 
                                        v-model="searchQuery" 
                                        placeholder="Cari nama sekolah, NPSN, subdomain..." 
                                        class="h-9 pl-8.5 pr-8 w-full rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition shadow-2xs"
                                    />
                                    <button 
                                        v-if="searchQuery" 
                                        @click="searchQuery = ''" 
                                        type="button" 
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition text-xs cursor-pointer"
                                        title="Hapus pencarian"
                                    >
                                        <i class="bi bi-x-circle-fill"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Buttons: Reset Filter -->
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button 
                                    type="button" 
                                    @click="resetFilters" 
                                    class="h-9 px-3 border border-slate-200 bg-white hover:bg-slate-100 active:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5 cursor-pointer"
                                    title="Reset Filter"
                                >
                                    <i class="bi bi-arrow-counterclockwise text-slate-500"></i>
                                    <span>Reset</span>
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- 2. Bagian 2: Middle Datatable Table -->
                    <div class="overflow-x-auto relative min-h-[300px]">
                        <!-- Loading Overlay -->
                        <div v-if="isLoading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                            <div class="flex items-center gap-2 text-blue-600 font-bold text-xs">
                                <i class="bi bi-arrow-repeat animate-spin text-lg"></i>
                                <span>Memperbarui metrik resource...</span>
                            </div>
                        </div>

                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50/80 border-b border-slate-200">
                                <tr>
                                    <th scope="col" class="py-3 px-4 w-12 text-center text-[10px] font-black text-slate-500 uppercase tracking-wider">No</th>
                                    <th scope="col" class="py-3 px-4 min-w-[220px] text-[10px] font-black text-slate-500 uppercase tracking-wider">Nama Sekolah & NPSN</th>
                                    <th scope="col" class="py-3 px-4 text-center w-36 text-[10px] font-black text-slate-500 uppercase tracking-wider cursor-pointer hover:text-blue-600 select-none" @click="toggleSort('active_sessions')">
                                        <div class="flex items-center justify-center gap-1">
                                            <span>Sesi Aktif</span>
                                            <i class="bi" :class="sortBy === 'active_sessions' ? (sortDir === 'desc' ? 'bi-sort-down text-blue-600' : 'bi-sort-up text-blue-600') : 'bi-arrow-down-up text-slate-400'"></i>
                                        </div>
                                    </th>
                                    <th scope="col" class="py-3 px-4 text-center w-36 text-[10px] font-black text-slate-500 uppercase tracking-wider cursor-pointer hover:text-blue-600 select-none" @click="toggleSort('disk_mb')">
                                        <div class="flex items-center justify-center gap-1">
                                            <span>Penyimpanan</span>
                                            <i class="bi" :class="sortBy === 'disk_mb' ? (sortDir === 'desc' ? 'bi-sort-down text-blue-600' : 'bi-sort-up text-blue-600') : 'bi-arrow-down-up text-slate-400'"></i>
                                        </div>
                                    </th>
                                    <th scope="col" class="py-3 px-4 text-center w-40 text-[10px] font-black text-slate-500 uppercase tracking-wider cursor-pointer hover:text-blue-600 select-none" @click="toggleSort('total_users')">
                                        <div class="flex items-center justify-center gap-1">
                                            <span>Total Pengguna</span>
                                            <i class="bi" :class="sortBy === 'total_users' ? (sortDir === 'desc' ? 'bi-sort-down text-blue-600' : 'bi-sort-up text-blue-600') : 'bi-arrow-down-up text-slate-400'"></i>
                                        </div>
                                    </th>
                                    <th scope="col" class="py-3 px-4 min-w-[180px] text-[10px] font-black text-slate-500 uppercase tracking-wider">Penggunaan Kuota Storage</th>
                                    <th scope="col" class="py-3 px-4 text-center w-32 text-[10px] font-black text-slate-500 uppercase tracking-wider sticky right-0 bg-slate-50 shadow-[-4px_0_6px_rgba(15,23,42,0.04)]">Status Kuota</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="filteredTenants.length === 0">
                                    <td colspan="7" class="text-center py-16 text-slate-400">
                                        <i class="bi bi-building-slash text-4xl block mb-2 text-slate-300"></i>
                                        <span class="text-xs font-medium">Tidak ada data sekolah yang sesuai dengan pencarian.</span>
                                    </td>
                                </tr>
                                <tr v-else v-for="(t, idx) in filteredTenants" :key="t.id" class="group hover:bg-blue-50/40 transition border-b border-slate-100">
                                    
                                    <!-- No -->
                                    <td class="py-3.5 px-4 text-center text-slate-400 font-mono text-[11px]">
                                        {{ idx + 1 }}
                                    </td>

                                    <!-- Nama Sekolah & NPSN -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 font-black flex items-center justify-center text-xs shrink-0 border border-blue-100 uppercase">
                                                {{ t.nama_sekolah ? t.nama_sekolah.substring(0, 2) : 'TK' }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                                    <span>{{ t.nama_sekolah }}</span>
                                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold font-mono" :class="t.status === 'active' || t.status === 'aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                                                        {{ t.status }}
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                                    NPSN: {{ t.npsn || '-' }} · @{{ t.subdomain }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Sesi Aktif -->
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5 font-bold font-mono text-xs" :class="t.active_sessions > 0 ? 'text-emerald-600' : 'text-slate-400'">
                                            <span v-if="t.active_sessions > 0" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>{{ t.active_sessions }} user online</span>
                                        </div>
                                    </td>

                                    <!-- Penyimpanan Disk -->
                                    <td class="py-3.5 px-4 text-center font-mono">
                                        <div class="font-bold text-slate-700 text-xs">
                                            {{ t.disk_mb >= 1024 ? (t.disk_mb / 1024).toFixed(2) + ' GB' : t.disk_mb.toFixed(1) + ' MB' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            Limit: {{ t.quota_mb >= 1024 ? (t.quota_mb / 1024) + ' GB' : t.quota_mb + ' MB' }}
                                        </div>
                                    </td>

                                    <!-- Total Pengguna -->
                                    <td class="py-3.5 px-4 text-center font-mono">
                                        <div class="font-bold text-slate-800 text-xs">
                                            {{ t.total_users }} total
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ t.total_siswa }} siswa · {{ t.total_staff }} staff
                                        </div>
                                    </td>

                                    <!-- Kuota Progress -->
                                    <td class="py-3.5 px-4">
                                        <div class="space-y-1">
                                            <div class="flex justify-between items-center text-[10px] font-mono text-slate-500">
                                                <span>{{ t.quota_percent }}% terpakai</span>
                                                <span class="font-semibold text-slate-700">{{ t.paket_aktif }}</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div 
                                                    class="h-full rounded-full transition-all duration-300"
                                                    :style="{ width: t.quota_percent + '%', background: getUsageColor(t.quota_percent) }"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status Kuota (Sticky Right) -->
                                    <td class="py-3.5 px-4 text-center sticky right-0 bg-white group-hover:bg-blue-50/40 transition shadow-[-4px_0_6px_rgba(15,23,42,0.04)]">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black border" :class="getUsageBadgeClass(t.quota_percent)">
                                            <i class="bi" :class="t.quota_status === 'Kritis' ? 'bi-exclamation-octagon-fill' : (t.quota_status === 'Peringatan' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill')"></i>
                                            {{ t.quota_status }}
                                        </span>
                                    </td>

                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 3. Bagian 3: Bottom Summary Footer Bar -->
                    <div class="p-4 bg-slate-50/60 border-t border-slate-200/80 flex flex-col md:flex-row justify-between items-center text-xs font-semibold text-slate-500 gap-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <span>Total Sesi Online: <strong class="text-emerald-600 font-mono">{{ totalActiveSessions }}</strong></span>
                            <span class="text-slate-300 hidden sm:inline">|</span>
                            <span>Total Storage Digunakan: <strong class="text-blue-600 font-mono">{{ totalDiskFormatted }}</strong></span>
                            <span class="text-slate-300 hidden sm:inline">|</span>
                            <span>Total Siswa: <strong class="text-slate-800 font-mono">{{ totalSiswa.toLocaleString('id-ID') }}</strong></span>
                        </div>
                        <div class="text-[11px] text-slate-400 font-mono">
                            Polling otomatis aktif · Interval {{ selectedInterval >= 60 ? (selectedInterval / 60) + ' menit' : selectedInterval + ' detik' }} · Data terdeteksi 15 menit terakhir
                        </div>
                    </div>

                </div>

            </div>

            <!-- TAB 2: NETWORK INTERFACES -->
            <div v-show="activeTab === 'network'" class="space-y-6 animate-in fade-in duration-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div v-for="net in localNetworkInterfaces" :key="net.interface" class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-inner">
                                        <i class="bi bi-hdd-network-fill"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-800">{{ net.interface }}</h3>
                                        <span class="inline-block mt-0.5 px-2 py-0.2 rounded-full text-[10px] font-black uppercase font-mono border" :class="net.dhcp ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'">
                                            {{ net.dhcp ? 'DHCP Enabled' : 'Static IP' }}
                                        </span>
                                    </div>
                                </div>
                                <button 
                                    type="button" 
                                    @click="openConfigModal(net)"
                                    class="h-8 px-3 bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer shadow-2xs"
                                >
                                    <i class="bi bi-gear-fill"></i> Konfigurasi
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100 text-xs font-mono">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">MAC Address</span>
                                    <span class="text-slate-700 font-semibold mt-0.5 block">{{ net.mac || 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">IPv4 / CIDR</span>
                                    <span class="text-slate-800 font-bold mt-0.5 block">{{ net.ipv4 ? (net.ipv4 + (net.cidr ? '/' + net.cidr : '')) : 'N/A' }}</span>
                                </div>
                                <div class="mt-1">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Gateway</span>
                                    <span class="text-slate-700 font-semibold mt-0.5 block">{{ net.gateway || 'N/A' }}</span>
                                </div>
                                <div class="mt-1">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">DNS Servers</span>
                                    <span class="text-slate-700 font-semibold mt-0.5 block">{{ Array.isArray(net.dns) ? net.dns.join(', ') : (net.dns || 'N/A') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="localNetworkInterfaces.length === 0" class="col-span-2 text-center py-16 bg-white rounded-2xl border border-slate-200 text-slate-400">
                        <i class="bi bi-wifi-off text-4xl block mb-2 text-slate-300"></i>
                        <span class="text-xs font-medium">Tidak ada adapter jaringan yang terdeteksi.</span>
                    </div>

                </div>
            </div>

            <!-- TAB 3: UPDATE & DEPLOYMENT -->
            <div v-show="activeTab === 'update'" class="space-y-6 animate-in fade-in duration-200">
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-4">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0 shadow-inner">
                                <i class="bi bi-cloud-arrow-down-fill"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Deployment & Update Otomatis</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Menjalankan sinkronisasi kode terbaru dari repositori git dan migrasi skema database.</p>
                            </div>
                        </div>

                        <button 
                            type="button" 
                            @click="triggerUpdate" 
                            :disabled="isUpdating"
                            class="h-10 px-4 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-2 disabled:opacity-50 disabled:pointer-events-none cursor-pointer"
                        >
                            <span v-if="isUpdating" class="spinner-border spinner-border-sm animate-spin">
                                <i class="bi bi-arrow-repeat text-sm"></i>
                            </span>
                            <i v-else class="bi bi-rocket-takeoff-fill text-sm"></i>
                            <span>{{ isUpdating ? 'Sedang Memproses...' : 'Jalankan Update Sekarang' }}</span>
                        </button>
                    </div>

                    <!-- Terminal Console Output Viewer -->
                    <div class="bg-slate-900 rounded-xl p-4 font-mono text-xs text-slate-200 h-80 overflow-y-auto shadow-inner border border-slate-800">
                        <div v-if="!updateLog" class="text-slate-400 space-y-2">
                            <div class="text-emerald-400 font-bold">// Terminal Output Console — deploy.sh</div>
                            <div>Klik tombol <span class="text-rose-400 font-bold">"Jalankan Update Sekarang"</span> untuk memulai proses pembaruan live.</div>
                            <div class="p-3 bg-slate-800/80 rounded-lg border border-slate-700 mt-4 space-y-1.5 text-[11px]">
                                <span class="text-amber-400 font-bold flex items-center gap-1">
                                    <i class="bi bi-shield-lock-fill"></i> Catatan Izin Server Linux:
                                </span>
                                <div>Pastikan user web server (<code class="text-blue-300">www-data</code>) diizinkan mengeksekusi script deploy tanpa password di sudoers:</div>
                                <div class="bg-slate-950 p-2 rounded text-emerald-300 select-all font-bold">
                                    www-data ALL=(ALL) NOPASSWD: /var/www/sinta/deploy.sh
                                </div>
                            </div>
                        </div>
                        <pre v-else class="whitespace-pre-wrap text-slate-100 font-mono">{{ updateLog }}</pre>
                    </div>

                </div>
            </div>

        </div>

        <!-- MODAL CONFIGURE NETWORK INTERFACE -->
        <div v-if="showNetworkModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between text-white">
                    <div>
                        <h3 class="text-base font-bold flex items-center gap-2 m-0">
                            <i class="bi bi-sliders"></i>
                            Konfigurasi: {{ formNetwork.interface }}
                        </h3>
                        <p class="text-xs text-blue-100 mb-0 mt-0.5">Atur penugasan IP Address, Gateway, dan DNS adapter jaringan.</p>
                    </div>
                    <button type="button" @click="closeConfigModal" class="text-white/80 hover:text-white text-xl cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitNetworkConfig" class="p-6 space-y-4">
                    
                    <!-- DHCP / Static Selector -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Tipe Penugasan IP</label>
                        <select 
                            v-model="formNetwork.dhcp"
                            class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition"
                        >
                            <option :value="true">DHCP (Otomatis dari Router)</option>
                            <option :value="false">Static IP (Manual)</option>
                        </select>
                    </div>

                    <!-- DHCP Info Note -->
                    <div v-if="formNetwork.dhcp" class="p-3 bg-blue-50 text-blue-700 rounded-xl text-xs flex gap-2 items-start border border-blue-100">
                        <i class="bi bi-info-circle-fill shrink-0 mt-0.5 text-blue-600"></i>
                        <span>Server akan meminta konfigurasi IP Address, Gateway, dan DNS secara otomatis dari router/DHCP Server.</span>
                    </div>

                    <!-- Static Fields Container -->
                    <div v-else class="space-y-3.5 pt-2 border-t border-slate-100">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">IPv4 Address & CIDR Prefix</label>
                            <input 
                                type="text" 
                                v-model="formNetwork.ipv4" 
                                placeholder="Contoh: 192.168.1.100/24"
                                class="w-full h-10 px-3 rounded-xl border border-slate-200 text-xs font-mono text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition"
                                required
                            />
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Default Gateway</label>
                            <input 
                                type="text" 
                                v-model="formNetwork.gateway" 
                                placeholder="Contoh: 192.168.1.1"
                                class="w-full h-10 px-3 rounded-xl border border-slate-200 text-xs font-mono text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition"
                            />
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">DNS Servers (Dipisah Koma)</label>
                            <input 
                                type="text" 
                                v-model="formNetwork.dns" 
                                placeholder="Contoh: 8.8.8.8, 8.8.4.4"
                                class="w-full h-10 px-3 rounded-xl border border-slate-200 text-xs font-mono text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition"
                            />
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="closeConfigModal"
                            class="h-9 px-4 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="formSubmitting"
                            class="h-9 px-5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                        >
                            <i v-if="formSubmitting" class="bi bi-arrow-repeat animate-spin"></i>
                            <i v-else class="bi bi-check2-circle"></i>
                            <span>{{ formSubmitting ? 'Menerapkan...' : 'Terapkan Konfigurasi' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </AppLayout>
</template>
