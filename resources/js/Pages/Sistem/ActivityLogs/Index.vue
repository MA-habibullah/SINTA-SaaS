<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, Link } from '@inertiajs/vue3'
import axios from 'axios'

const getSwal = () => typeof window !== 'undefined' && window.Swal ? window.Swal : null

const props = defineProps({
    logs: Object,
    stats: Object,
    tenantsList: {
        type: Array,
        default: () => []
    },
    rolesList: {
        type: Array,
        default: () => []
    },
    actionsList: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    }
})

// Reactive States
const localLogs = ref(props.logs?.data || [])
const perPage = ref(props.logs?.per_page || 20)
const pagination = ref({
    currentPage: props.logs?.current_page || 1,
    lastPage: props.logs?.last_page || 1,
    total: props.logs?.total || 0,
    from: props.logs?.from || 0,
    to: props.logs?.to || 0,
})

const localStats = ref(props.stats || {
    total_today: 0,
    total_insert: 0,
    total_update: 0,
    total_delete: 0,
})

const searchQuery = ref(props.filters?.search || '')
const filterTenant = ref(props.filters?.tenant_filter || '')
const filterRole = ref(props.filters?.role_filter || '')
const filterAction = ref(props.filters?.action_filter || '')
const startDate = ref(props.filters?.start_date || '')
const endDate = ref(props.filters?.end_date || '')
const isLoading = ref(false)
const isRefreshing = ref(false)
const autoRefresh = ref(false)
let autoRefreshTimer = null
let searchTimeout = null

// Modal States
const showDetailModal = ref(false)
const selectedLog = ref(null)

const showRetentionModal = ref(false)
const retentionForm = ref({
    startDate: '',
    endDate: '',
    tenantId: 'all',
})
const isPurging = ref(false)

// Field Labels Translator Dictionary
const fieldLabels = {
    nama_lengkap: 'Nama Lengkap',
    jenis_kelamin: 'Jenis Kelamin',
    nik: 'NIK',
    no_kk: 'No. KK',
    nisn: 'NISN',
    nip: 'NIP',
    nuptk: 'NUPTK',
    id_angkatan: 'Angkatan',
    id_tahun_ajaran: 'Tahun Ajaran',
    id_jenjang: 'Jenjang',
    id_jurusan: 'Jurusan',
    id_kelas: 'Rombel Kelas',
    id_pendidikan: 'Bentuk Pendidikan',
    nama_wali: 'Nama Wali',
    current_step: 'Tahap Registrasi',
    subdomain: 'Subdomain',
    npsn: 'NPSN',
    nama_sekolah: 'Nama Instansi',
    alamat: 'Alamat',
    email: 'Email',
    status: 'Status Akses',
    paket_aktif: 'Paket Langganan',
    status_sinkronisasi: 'Status Sinkronisasi',
    tempat_lahir: 'Tempat Lahir',
    tanggal_lahir: 'Tanggal Lahir',
    no_telp: 'No. Telepon',
    agama: 'Agama',
    nama_ibu: 'Nama Ibu',
    nama_ayah: 'Nama Ayah',
    tenant_id: 'Instansi Sekolah',
    user_id: 'User Aktor',
    id_siswa: 'Siswa Terkait',
    siswa_id: 'Siswa Terkait',
    role_id: 'Peran / Role',
    diverifikasi_oleh: 'Diverifikasi Oleh',
    id_guru_bk: 'Guru BK',
    nominal: 'Nominal Tagihan',
    keterangan: 'Keterangan',
    is_active: 'Status Aktif',
    deleted_at: 'Waktu Dihapus',
}

const getFieldLabel = (key) => fieldLabels[key] || key

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

// Smart Pagination Links Generator (AGENTS.md Standard)
const getSmartPaginationLinks = (paginationData) => {
    if (!paginationData || paginationData.total === 0) return []
    const current = paginationData.currentPage || 1
    const last = paginationData.lastPage || 1

    const result = []
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
const fetchLogs = async (page = 1, silent = false) => {
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
            tenant_filter: filterTenant.value,
            role_filter: filterRole.value,
            action_filter: filterAction.value,
            start_date: startDate.value,
            end_date: endDate.value,
        }

        const res = await axios.get('/utilitas/log-aktivitas/data', { params })
        if (res.data?.success) {
            const data = res.data.data
            localLogs.value = data.data || []
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
        console.error('Gagal mengambil data log:', err)
    } finally {
        isLoading.value = false
        isRefreshing.value = false
    }
}

const goToPage = (page) => {
    if (!page || page === pagination.value.currentPage || isLoading.value) return
    fetchLogs(page)
}

const onPerPageChange = () => {
    fetchLogs(1)
}

const applyFilters = () => {
    fetchLogs(1)
}

// Auto Refresh Handler
const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value
    if (autoRefresh.value) {
        if (autoRefreshTimer) clearInterval(autoRefreshTimer)
        autoRefreshTimer = setInterval(() => {
            fetchLogs(pagination.value.currentPage, true)
        }, 10000)
    } else {
        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer)
            autoRefreshTimer = null
        }
    }
}

onMounted(() => {
    // Optionally initiate stats sync
})

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

// Reset Filters
const resetFilters = () => {
    searchQuery.value = ''
    filterTenant.value = ''
    filterRole.value = ''
    filterAction.value = ''
    startDate.value = ''
    endDate.value = ''
    applyFilters()
}

// Action Badges Helper
const getActionBadge = (action) => {
    switch (action) {
        case 'INSERT':
            return {
                badge: 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
                icon: 'bi-plus-circle-fill'
            }
        case 'UPDATE':
            return {
                badge: 'bg-amber-50 text-amber-700 border-amber-200/80',
                icon: 'bi-pencil-square'
            }
        case 'DELETE':
            return {
                badge: 'bg-rose-50 text-rose-700 border-rose-200/80',
                icon: 'bi-trash-fill'
            }
        case 'LOGIN':
            return {
                badge: 'bg-blue-50 text-blue-700 border-blue-200/80',
                icon: 'bi-box-arrow-in-right'
            }
        case 'LOGOUT':
            return {
                badge: 'bg-slate-100 text-slate-700 border-slate-300',
                icon: 'bi-box-arrow-right'
            }
        case 'EXPORT':
        case 'PRINT':
            return {
                badge: 'bg-purple-50 text-purple-700 border-purple-200/80',
                icon: 'bi-printer-fill'
            }
        default:
            return {
                badge: 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
                icon: 'bi-activity'
            }
    }
}

// Open Detail Modal
const openDetail = (log) => {
    selectedLog.value = log
    showDetailModal.value = true
}

// Parse JSON safely
const parseJson = (val) => {
    if (!val) return null
    if (typeof val === 'object') return val
    try {
        return JSON.parse(val)
    } catch {
        return val
    }
}

// Compute differences for modal
const computedDiff = computed(() => {
    if (!selectedLog.value) return []
    const oldD = parseJson(selectedLog.value.old_data) || {}
    const newD = parseJson(selectedLog.value.new_data) || {}

    const allKeys = Array.from(new Set([...Object.keys(oldD), ...Object.keys(newD)]))
    
    return allKeys.map(key => {
        const oldVal = oldD[key]
        const newVal = newD[key]
        const isChanged = JSON.stringify(oldVal) !== JSON.stringify(newVal)
        return {
            key,
            label: getFieldLabel(key),
            oldVal: oldVal !== undefined ? oldVal : '—',
            newVal: newVal !== undefined ? newVal : '—',
            isChanged
        }
    })
})

// Open Retention / Purge Modal
const openRetentionModal = () => {
    const d = new Date()
    d.setDate(d.getDate() - 30)
    retentionForm.value = {
        startDate: d.toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0],
        tenantId: props.isSuperAdmin ? 'all' : (props.filters?.tenant_filter || ''),
    }
    showRetentionModal.value = true
}

// Execute Log Purge
const handlePurgeLogs = async () => {
    const swal = getSwal()
    if (!retentionForm.value.startDate || !retentionForm.value.endDate) {
        if (swal) {
            swal.fire({
                icon: 'warning',
                title: 'Rentang Tanggal Wajib Diisi',
                text: 'Silakan pilih tanggal mulai dan tanggal akhir untuk pembersihan log.',
                confirmButtonColor: '#4f46e5',
            })
        } else {
            alert('Silakan pilih rentang tanggal mulai dan akhir.')
        }
        return
    }

    if (swal) {
        const confirm = await swal.fire({
            title: 'Konfirmasi Pembersihan Log?',
            text: `Data log aktivitas pada rentang ${retentionForm.value.startDate} s.d. ${retentionForm.value.endDate} akan dihapus permanen untuk efisiensi ruang database. Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Bersihkan Log',
            cancelButtonText: 'Batal',
        })
        if (!confirm.isConfirmed) return
    } else {
        if (!confirm(`Konfirmasi pembersihan log aktivitas pada rentang ${retentionForm.value.startDate} s.d. ${retentionForm.value.endDate}?`)) return
    }

    isPurging.value = true
    try {
        const res = await axios.post('/utilitas/log-aktivitas/delete', retentionForm.value)
        if (res.data?.success) {
            if (swal) {
                swal.fire({
                    icon: 'success',
                    title: 'Pembersihan Berhasil!',
                    text: res.data.message || 'Log aktivitas lama berhasil dibersihkan.',
                    timer: 2000,
                    showConfirmButton: false,
                })
            } else {
                alert(res.data.message || 'Log aktivitas berhasil dibersihkan.')
            }
            showRetentionModal.value = false
            fetchLogs(1)
        }
    } catch (err) {
        if (swal) {
            swal.fire({
                icon: 'error',
                title: 'Gagal Membersihkan Log',
                text: err.response?.data?.error || 'Terjadi kesalahan sistem saat membersihkan log.',
                confirmButtonColor: '#4f46e5',
            })
        } else {
            alert(err.response?.data?.error || 'Gagal membersihkan log.')
        }
    } finally {
        isPurging.value = false
    }
}
</script>

<template>
    <AppLayout title="Audit Trail & Log Aktivitas Sistem">
        <div class="space-y-6">
            
            <!-- Header Halaman & Action Bar (Clean Toolbar Card) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 sm:gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-start sm:items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-xs shrink-0">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">Audit Trail & Log Aktivitas Sistem</h1>
                            <span v-if="isSuperAdmin" class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-bold text-[10px] tracking-wide inline-flex items-center">
                                <i class="bi bi-shield-lock-fill me-1"></i>Super Admin Only
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Memantau rekaman aktivitas mutasi data (*INSERT*, *UPDATE*, *DELETE*), riwayat login/logout, dan jejak keamanan sistem secara real-time.</p>
                    </div>
                </div>
                
                <!-- Action Buttons: Clean Horizontal Layout (h-9, whitespace-nowrap, flex items-center gap-2) -->
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

                    <!-- Clear / Retention Button -->
                    <button 
                        v-if="isSuperAdmin"
                        type="button" 
                        @click="openRetentionModal"
                        class="h-9 px-3.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer whitespace-nowrap"
                        title="Pembersihan Retensi Log Database"
                    >
                        <i class="bi bi-trash3-fill"></i>
                        <span>Bersihkan Log ({{ pagination.total || 0 }})</span>
                    </button>

                    <!-- Refresh Button -->
                    <button 
                        type="button" 
                        @click="fetchLogs(pagination.currentPage)"
                        class="h-9 px-3.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/80 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer whitespace-nowrap"
                        title="Muat Ulang Log Sekarang"
                    >
                        <i class="bi bi-arrow-clockwise" :class="{ 'animate-spin': isLoading || isRefreshing }"></i>
                        <span>Segarkan</span>
                    </button>
                </div>
            </div>

            <!-- 4 Kartu Metrik KPI Hari Ini -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                
                <!-- Card 1: Total Aktivitas Hari Ini -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Aktivitas Hari Ini</span>
                        <h3 class="text-2xl font-extrabold text-slate-800 mt-1 font-mono">{{ localStats.total_today }}</h3>
                        <span class="text-[10px] text-indigo-600 font-semibold flex items-center gap-1 mt-0.5">
                            <i class="bi bi-calendar-check"></i> 24 Jam Terakhir
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-activity"></i>
                    </div>
                </div>

                <!-- Card 2: Data Baru (INSERT) -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Data Baru (Insert)</span>
                        <h3 class="text-2xl font-extrabold text-emerald-600 mt-1 font-mono">{{ localStats.total_insert }}</h3>
                        <span class="text-[10px] text-slate-400 font-medium mt-0.5 block">Penambahan entri data</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-plus-circle-fill"></i>
                    </div>
                </div>

                <!-- Card 3: Perubahan Data (UPDATE) -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Perubahan Data (Update)</span>
                        <h3 class="text-2xl font-extrabold text-amber-600 mt-1 font-mono">{{ localStats.total_update }}</h3>
                        <span class="text-[10px] text-slate-400 font-medium mt-0.5 block">Modifikasi rekaman</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                </div>

                <!-- Card 4: Penghapusan Data (DELETE) -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Penghapusan (Delete)</span>
                        <h3 class="text-2xl font-extrabold text-rose-600 mt-1 font-mono">{{ localStats.total_delete }}</h3>
                        <span class="text-[10px] text-slate-400 font-medium mt-0.5 block">Penghapusan data sistem</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-trash-fill"></i>
                    </div>
                </div>

            </div>

            <!-- Main Content: 3-Part Unified Box Card -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                
                <!-- 1. Bagian 1: Top Filter Bar (Bilah Filter & Pencarian Atas) -->
                <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
                    <form @submit.prevent="applyFilters" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
                        
                        <!-- Filter Sekolah (Super Admin Only) -->
                        <div v-if="isSuperAdmin" class="w-48 shrink-0">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Sekolah Mitra</label>
                            <div class="relative">
                                <select 
                                    v-model="filterTenant" 
                                    class="h-9 w-full pl-3 pr-8 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition shadow-2xs cursor-pointer appearance-none"
                                >
                                    <option value="">Semua Sekolah</option>
                                    <option value="system">Sistem (Global)</option>
                                    <option v-for="t in tenantsList" :key="t.id" :value="t.id">
                                        {{ t.nama_sekolah }}
                                    </option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Filter Role -->
                        <div class="w-36 shrink-0">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Peran / Role</label>
                            <div class="relative">
                                <select 
                                    v-model="filterRole" 
                                    class="h-9 w-full pl-3 pr-8 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition shadow-2xs cursor-pointer appearance-none capitalize"
                                >
                                    <option value="">Semua Role</option>
                                    <option v-for="r in rolesList" :key="r" :value="r">
                                        {{ r }}
                                    </option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Filter Action -->
                        <div class="w-36 shrink-0">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Jenis Aksi</label>
                            <div class="relative">
                                <select 
                                    v-model="filterAction" 
                                    class="h-9 w-full pl-3 pr-8 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition shadow-2xs cursor-pointer appearance-none"
                                >
                                    <option value="">Semua Aksi</option>
                                    <option v-for="a in actionsList" :key="a" :value="a">
                                        {{ a }}
                                    </option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div class="shrink-0">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Rentang Tanggal</label>
                            <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-2.5 h-9 shadow-2xs">
                                <i class="bi bi-calendar-range text-slate-400 text-xs shrink-0"></i>
                                <input 
                                    type="date" 
                                    v-model="startDate" 
                                    class="text-xs font-medium text-slate-700 bg-transparent focus:outline-none border-none p-0 w-26 cursor-pointer"
                                    title="Tanggal Mulai"
                                />
                                <span class="text-slate-300 text-xs font-bold shrink-0">—</span>
                                <input 
                                    type="date" 
                                    v-model="endDate" 
                                    class="text-xs font-medium text-slate-700 bg-transparent focus:outline-none border-none p-0 w-26 cursor-pointer"
                                    title="Tanggal Akhir"
                                />
                            </div>
                        </div>

                        <!-- Search Input with Clear Button -->
                        <div class="w-64 sm:w-72 md:w-80 shrink-0">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Pencarian Log</label>
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input 
                                    type="text" 
                                    v-model="searchQuery" 
                                    placeholder="Cari aksi, tabel, user, IP..." 
                                    class="h-9 pl-8.5 pr-8 w-full rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition shadow-2xs"
                                />
                                <button 
                                    v-if="searchQuery" 
                                    @click="searchQuery = ''; applyFilters()" 
                                    type="button" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition text-xs cursor-pointer"
                                    title="Hapus pencarian"
                                >
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Buttons Group: Cari & Reset -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button 
                                type="submit" 
                                class="h-9 px-3.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer"
                                title="Terapkan Filter"
                            >
                                <i class="bi bi-funnel-fill text-xs"></i>
                                <span>Cari</span>
                            </button>
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

                <!-- 2. Bagian 2: Middle Data Table -->
                <div class="overflow-x-auto relative min-h-[300px]">
                    <!-- Loading Overlay -->
                    <div v-if="isLoading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                        <div class="flex items-center gap-2 text-blue-600 font-bold text-xs">
                            <i class="bi bi-arrow-repeat animate-spin text-lg"></i>
                            <span>Memuat data log aktivitas...</span>
                        </div>
                    </div>

                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="py-3 px-4 w-12 text-center text-[10px] font-black text-slate-500 uppercase tracking-wider">No</th>
                                <th scope="col" class="py-3 px-4 w-40 text-[10px] font-black text-slate-500 uppercase tracking-wider">Waktu</th>
                                <th v-if="isSuperAdmin" scope="col" class="py-3 px-4 w-48 text-[10px] font-black text-slate-500 uppercase tracking-wider">Sekolah Mitra</th>
                                <th scope="col" class="py-3 px-4 w-56 text-[10px] font-black text-slate-500 uppercase tracking-wider">Aktor & Peran</th>
                                <th scope="col" class="py-3 px-4 w-52 text-[10px] font-black text-slate-500 uppercase tracking-wider">Aksi & Tabel</th>
                                <th scope="col" class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-wider">Ringkasan Mutasi Data</th>
                                <th scope="col" class="py-3 px-4 text-center w-24 text-[10px] font-black text-slate-500 uppercase tracking-wider sticky right-0 bg-slate-50 shadow-[-4px_0_6px_rgba(15,23,42,0.04)]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="localLogs.length === 0">
                                <td :colspan="isSuperAdmin ? 7 : 6" class="text-center py-16 text-slate-400">
                                    <i class="bi bi-journal-x text-4xl block mb-2 text-slate-300"></i>
                                    <span class="text-xs font-medium">Tidak ada rekaman log aktivitas yang sesuai dengan filter atau pencarian Anda.</span>
                                </td>
                            </tr>
                            <tr v-else v-for="(log, idx) in localLogs" :key="log.id" class="group hover:bg-blue-50/40 transition border-b border-slate-100">
                                
                                <!-- No -->
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono text-[11px]">
                                    {{ (pagination.currentPage - 1) * pagination.perPage + idx + 1 }}
                                </td>

                                <!-- Waktu -->
                                <td class="py-3.5 px-4">
                                    <div class="font-mono text-xs font-bold text-slate-700">
                                        {{ formatDateTime(log.created_at) }}
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-mono block">ID: {{ log.id?.substring(0, 8) }}...</span>
                                </td>

                                <!-- Sekolah (Super Admin) -->
                                <td v-if="isSuperAdmin" class="py-3.5 px-4">
                                    <div v-if="log.nama_sekolah" class="font-bold text-slate-800 text-xs">
                                        {{ log.nama_sekolah }}
                                        <span v-if="log.subdomain" class="text-[10px] text-blue-600 font-mono block">
                                            @{{ log.subdomain }}
                                        </span>
                                    </div>
                                    <div v-else>
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">
                                            Sistem (Global)
                                        </span>
                                    </div>
                                </td>

                                <!-- Aktor & Peran -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 font-bold flex items-center justify-center text-xs shrink-0 border border-blue-100">
                                            {{ log.actor_name ? log.actor_name.substring(0, 2).toUpperCase() : 'SYS' }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-xs">
                                                {{ log.actor_name || 'System Auto' }}
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 capitalize font-mono">
                                                    {{ log.user_role || 'System' }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 font-mono">{{ log.ip_address || '::1' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi & Tabel -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-extrabold border shadow-2xs font-mono" :class="getActionBadge(log.action).badge">
                                                <i class="bi text-[10px]" :class="getActionBadge(log.action).icon"></i>
                                                {{ log.action }}
                                            </span>
                                            <span class="font-mono text-xs font-semibold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200/80">
                                                {{ log.table_name || 'sistem' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Ringkasan Mutasi Data -->
                                <td class="py-3.5 px-4">
                                    <div class="text-xs">
                                        <!-- INSERT -->
                                        <div v-if="log.action === 'INSERT'" class="flex items-center gap-1.5 text-emerald-700 font-medium">
                                            <span class="px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200/80 text-[11px] font-semibold inline-flex items-center gap-1">
                                                <i class="bi bi-plus-circle"></i> Data Baru Dibuat
                                            </span>
                                            <span v-if="parseJson(log.new_data)?.nama_lengkap || parseJson(log.new_data)?.nama_sekolah" class="text-slate-600 truncate max-w-xs">
                                                ({{ parseJson(log.new_data)?.nama_lengkap || parseJson(log.new_data)?.nama_sekolah }})
                                            </span>
                                        </div>

                                        <!-- UPDATE -->
                                        <div v-else-if="log.action === 'UPDATE'" class="flex flex-wrap items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200/80 text-[11px] font-semibold text-amber-800 inline-flex items-center gap-1">
                                                <i class="bi bi-pencil"></i> Perubahan Data
                                            </span>
                                            <span class="text-slate-500 text-[11px]">
                                                Klik tombol <strong class="text-slate-700">Detail</strong> untuk melihat perbandingan nilai.
                                            </span>
                                        </div>

                                        <!-- DELETE -->
                                        <div v-else-if="log.action === 'DELETE'" class="flex items-center gap-1.5 text-rose-700 font-medium">
                                            <span class="px-2 py-0.5 rounded-md bg-rose-50 border border-rose-200/80 text-[11px] font-semibold inline-flex items-center gap-1">
                                                <i class="bi bi-trash"></i> Data Dihapus
                                            </span>
                                        </div>

                                        <!-- LOGIN / LOGOUT -->
                                        <div v-else-if="log.action === 'LOGIN' || log.action === 'LOGOUT'" class="text-slate-600 text-xs font-mono">
                                            <span v-if="parseJson(log.new_data)?.email || parseJson(log.old_data)?.email">
                                                {{ parseJson(log.new_data)?.email || parseJson(log.old_data)?.email }}
                                            </span>
                                            <span v-else>Autentikasi Sesi Berhasil</span>
                                        </div>

                                        <!-- OTHER -->
                                        <div v-else class="text-slate-500 font-mono text-[11px] truncate max-w-sm">
                                            {{ JSON.stringify(parseJson(log.new_data) || parseJson(log.old_data) || '-') }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi Detail (Sticky Right) -->
                                <td class="py-3 px-4 text-center sticky right-0 bg-white group-hover:bg-blue-50/40 transition shadow-[-4px_0_6px_rgba(15,23,42,0.04)]">
                                    <button 
                                        type="button" 
                                        @click="openDetail(log)"
                                        class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1 mx-auto cursor-pointer shadow-2xs"
                                        title="Inspeksi Data Detail"
                                    >
                                        <i class="bi bi-eye-fill"></i> Detail
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 3. Bagian 3: Bottom Pagination Footer Bar -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                        <span>Tampilkan</span>
                        <select 
                            v-model="perPage" 
                            @change="onPerPageChange"
                            class="h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition shadow-2xs cursor-pointer"
                        >
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="20">20</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                        <span>baris per halaman</span>
                        <span class="text-slate-300 hidden sm:inline">|</span>
                        <span>Menampilkan <strong class="text-slate-800 font-mono">{{ pagination.from || 0 }}</strong> s.d. <strong class="text-slate-800 font-mono">{{ pagination.to || 0 }}</strong> dari <strong class="text-slate-800 font-mono">{{ pagination.total || 0 }}</strong> baris</span>
                    </div>

                    <!-- Smart Windowed Pagination -->
                    <div v-if="pagination.lastPage > 1" class="flex items-center gap-1">
                        <!-- First Page << -->
                        <button 
                            type="button" 
                            @click="goToPage(1)" 
                            :disabled="pagination.currentPage === 1 || isLoading"
                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-blue-50 hover:text-blue-600 disabled:opacity-40 disabled:pointer-events-none transition text-xs font-bold shadow-2xs cursor-pointer"
                            title="Halaman Pertama"
                        >
                            <i class="bi bi-chevron-double-left"></i>
                        </button>

                        <!-- Prev Page < -->
                        <button 
                            type="button" 
                            @click="goToPage(pagination.currentPage - 1)" 
                            :disabled="pagination.currentPage === 1 || isLoading"
                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-blue-50 hover:text-blue-600 disabled:opacity-40 disabled:pointer-events-none transition text-xs font-bold shadow-2xs cursor-pointer"
                            title="Halaman Sebelumnya"
                        >
                            <i class="bi bi-chevron-left"></i>
                        </button>

                        <!-- Number / Ellipsis buttons -->
                        <template v-for="(item, idx) in getSmartPaginationLinks(pagination)" :key="idx">
                            <span 
                                v-if="item.isEllipsis" 
                                class="w-8 h-8 flex items-center justify-center text-xs font-bold text-slate-400 select-none"
                            >
                                …
                            </span>
                            <button 
                                v-else 
                                type="button" 
                                @click="goToPage(item.page)" 
                                :disabled="isLoading"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-bold transition shadow-2xs cursor-pointer"
                                :class="item.page === pagination.currentPage ? 'bg-blue-600 border-blue-600 text-white shadow-xs' : 'border-slate-200 bg-white text-slate-600 hover:bg-blue-50 hover:text-blue-600'"
                            >
                                {{ item.page }}
                            </button>
                        </template>

                        <!-- Next Page > -->
                        <button 
                            type="button" 
                            @click="goToPage(pagination.currentPage + 1)" 
                            :disabled="pagination.currentPage === pagination.lastPage || isLoading"
                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-blue-50 hover:text-blue-600 disabled:opacity-40 disabled:pointer-events-none transition text-xs font-bold shadow-2xs cursor-pointer"
                            title="Halaman Berikutnya"
                        >
                            <i class="bi bi-chevron-right"></i>
                        </button>

                        <!-- Last Page >> -->
                        <button 
                            type="button" 
                            @click="goToPage(pagination.lastPage)" 
                            :disabled="pagination.currentPage === pagination.lastPage || isLoading"
                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-blue-50 hover:text-blue-600 disabled:opacity-40 disabled:pointer-events-none transition text-xs font-bold shadow-2xs cursor-pointer"
                            title="Halaman Terakhir"
                        >
                            <i class="bi bi-chevron-double-right"></i>
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <!-- MODAL DIALOG 1: Detail Inspeksi JSON Diff -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-3xl overflow-hidden animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] flex flex-col">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4 flex items-center justify-between text-white shrink-0">
                    <div>
                        <h3 class="text-base font-bold flex items-center gap-2 m-0">
                            <i class="bi bi-search"></i>
                            Inspeksi Log Audit Trail
                        </h3>
                        <p class="text-xs text-indigo-100 mb-0 mt-0.5">Rincian metadata dan perbandingan rekaman data mutasi sistem.</p>
                    </div>
                    <button type="button" @click="showDetailModal = false" class="text-white/80 hover:text-white text-xl cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div v-if="selectedLog" class="p-6 overflow-y-auto space-y-5 grow">
                    
                    <!-- Metadata Grid -->
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Waktu Aktivitas</span>
                            <span class="font-mono font-bold text-slate-800">{{ formatDateTime(selectedLog.created_at) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Aktor Pengguna</span>
                            <span class="font-bold text-slate-800">{{ selectedLog.actor_name || 'System' }} ({{ selectedLog.user_role }})</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">IP Address</span>
                            <span class="font-mono font-bold text-slate-800">{{ selectedLog.ip_address || '::1' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Jenis Aksi</span>
                            <span class="inline-flex items-center gap-1 font-bold text-indigo-600 font-mono">{{ selectedLog.action }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Tabel Target</span>
                            <span class="font-mono font-bold text-slate-800">{{ selectedLog.table_name || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Instansi Sekolah</span>
                            <span class="font-bold text-slate-800">{{ selectedLog.nama_sekolah || 'Sistem (Global)' }}</span>
                        </div>
                    </div>

                    <!-- Perbandingan Nilai Data (Diff Table) -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                            <i class="bi bi-sliders text-indigo-600"></i> Nilai Data & Perubahan Field
                        </h4>

                        <div class="border border-slate-200/80 rounded-2xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200/80 font-bold text-slate-600 text-[11px]">
                                    <tr>
                                        <th class="py-2.5 px-3.5 w-1/3">Nama Atribut / Field</th>
                                        <th class="py-2.5 px-3.5 w-1/3 text-rose-700 bg-rose-50/50">Nilai Sebelumnya (Old)</th>
                                        <th class="py-2.5 px-3.5 w-1/3 text-emerald-700 bg-emerald-50/50">Nilai Terbaru (New)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                                    <tr v-if="computedDiff.length === 0">
                                        <td colspan="3" class="text-center py-6 text-slate-400">Tidak ada payload data yang terekam.</td>
                                    </tr>
                                    <tr v-else v-for="item in computedDiff" :key="item.key" :class="{ 'bg-amber-50/40': item.isChanged }">
                                        <td class="py-2 px-3.5 font-sans font-semibold text-slate-700">
                                            {{ item.label }}
                                            <span class="block font-mono text-[10px] text-slate-400 font-normal">{{ item.key }}</span>
                                        </td>
                                        <td class="py-2 px-3.5 text-slate-600 break-all">
                                            <span :class="{ 'line-through text-rose-600 font-bold': item.isChanged }">
                                                {{ typeof item.oldVal === 'object' ? JSON.stringify(item.oldVal) : item.oldVal }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-3.5 text-slate-800 break-all">
                                            <span :class="{ 'text-emerald-700 font-bold bg-emerald-50 px-1 py-0.5 rounded': item.isChanged }">
                                                {{ typeof item.newVal === 'object' ? JSON.stringify(item.newVal) : item.newVal }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end shrink-0">
                    <button 
                        type="button" 
                        @click="showDetailModal = false" 
                        class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>

            </div>
        </div>

        <!-- MODAL DIALOG 2: Pembersihan Retensi Log (Retention Modal) -->
        <div v-if="showRetentionModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-rose-600 to-pink-600 px-6 py-4 flex items-center justify-between text-white">
                    <div>
                        <h3 class="text-base font-bold flex items-center gap-2 m-0">
                            <i class="bi bi-trash3-fill"></i>
                            Pembersihan Retensi Log Aktivitas
                        </h3>
                        <p class="text-xs text-rose-100 mb-0 mt-0.5">Menghapus riwayat log lama untuk efisiensi penyimpanan database PostgreSQL.</p>
                    </div>
                    <button type="button" @click="showRetentionModal = false" class="text-white/80 hover:text-white text-xl cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form @submit.prevent="handlePurgeLogs" class="p-6 space-y-4">
                    
                    <!-- Alert Notice -->
                    <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-start gap-2.5 text-xs text-amber-800 font-medium">
                        <i class="bi bi-exclamation-triangle-fill text-base text-amber-600 shrink-0"></i>
                        <span>
                            Tindakan ini akan <strong>menghapus permanen</strong> log audit trail pada rentang tanggal yang dipilih. Log mutasi penghapusan ini tetap akan dicatat secara otomatis.
                        </span>
                    </div>

                    <!-- Target Tenant (Super Admin Only) -->
                    <div v-if="isSuperAdmin" class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Target Lingkup Instansi</label>
                        <select 
                            v-model="retentionForm.tenantId" 
                            class="h-10 px-3 w-full rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:outline-none"
                        >
                            <option value="all">Semua Sekolah & Sistem (Global)</option>
                            <option value="system">Hanya Sistem (Super Admin)</option>
                            <option v-for="t in tenantsList" :key="t.id" :value="t.id">
                                {{ t.nama_sekolah }} ({{ t.npsn }})
                            </option>
                        </select>
                    </div>

                    <!-- Rentang Tanggal -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700">Tanggal Mulai</label>
                            <input 
                                type="date" 
                                v-model="retentionForm.startDate" 
                                required
                                class="h-10 px-3 w-full rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:outline-none"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700">Tanggal Akhir</label>
                            <input 
                                type="date" 
                                v-model="retentionForm.endDate" 
                                required
                                class="h-10 px-3 w-full rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showRetentionModal = false" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="isPurging"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50"
                        >
                            <i class="bi bi-trash3" :class="{ 'animate-spin': isPurging }"></i>
                            <span>{{ isPurging ? 'Sedang Menghapus...' : 'Eksekusi Pembersihan' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </AppLayout>
</template>
