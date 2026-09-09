<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

// Toast notification helper
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

// Modal dialog helper
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

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({ pending: 0, processing: 0, completed: 0, failed: 0, total: 0 })
    },
    tenantsList: {
        type: Array,
        default: () => []
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    },
    userRole: {
        type: String,
        default: ''
    }
})

// Metrics reactive state
const metricsState = ref({ ...props.metrics })
const jobs = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const totalCount = ref(0)
const loading = ref(false)
const autoRefresh = ref(true)

// Filters
const filters = reactive({
    status: '',
    job_type: '',
    tenant_id: ''
})

// Simulation form state
const simJobType = ref('DEMO_SYNC_SUCCESS')
const simTenantId = ref('')
const dispatching = ref(false)

// Web worker trigger state
const runningWorker = ref(false)

// Auto-refresh timer reference
let refreshTimer = null

// Format datetime helper
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
    })
}

// Fetch queue database logs & counters
const fetchData = async (page = 1) => {
    loading.value = true
    currentPage.value = page
    try {
        const response = await axios.get('/utilitas/antrean/data', {
            params: {
                page: page,
                status: filters.status,
                job_type: filters.job_type,
                tenant_id: filters.tenant_id
            }
        })
        
        if (response.data && response.data.success) {
            metricsState.value = response.data.metrics
            jobs.value = response.data.jobs
            totalPages.value = response.data.total_pages
            totalCount.value = response.data.total_count
        }
    } catch (err) {
        console.error('Failed to fetch queue data:', err)
    } finally {
        loading.value = false
    }
}

// Reset filter
const resetFilter = () => {
    filters.status = ''
    filters.job_type = ''
    filters.tenant_id = ''
    fetchData(1)
}

// Dispatch simulated job API request
const dispatchSimJob = async () => {
    dispatching.value = true
    
    let jobType = 'DEMO_SYNC'
    let payload = {}

    if (simJobType.value === 'DEMO_SYNC_SUCCESS') {
        jobType = 'DEMO_SYNC'
        payload = { force_fail: false, deskripsi: 'Sinkronisasi profil data akademik dan sarpras ke Pusdatin pusat.' }
    } else if (simJobType.value === 'DEMO_SYNC_FAIL') {
        jobType = 'DEMO_SYNC_FAIL'
        payload = { force_fail: true, deskripsi: 'Uji kegagalan sinkronisasi gateway pendaftaran siswa baru.' }
    } else if (simJobType.value === 'DEMO_EMAIL') {
        jobType = 'DEMO_EMAIL'
        payload = { subjek: 'Bukti Pembayaran Pendaftaran SPMB Terverifikasi', jumlah_penerima: 75, template: 'email_notifikasi_spmb' }
    } else if (simJobType.value === 'CLEANUP_SESSIONS') {
        jobType = 'CLEANUP_SESSIONS'
        payload = { deskripsi: 'Pembersihan token dan sesi kadaluarsa sistem otomatis.' }
    }

    try {
        const response = await axios.post('/utilitas/antrean/dispatch', {
            job_type: jobType,
            payload: payload,
            tenant_id: simTenantId.value || null
        })

        if (response.data && response.data.success) {
            notify('success', 'Tugas Ditambahkan', response.data.message || 'Tugas simulasi berhasil masuk antrean.')
            fetchData(1)
        }
    } catch (err) {
        console.error(err)
        showModal('error', 'Gagal Memasukkan Antrean', (err.response && err.response.data && (err.response.data.error || err.response.data.message)) || err.message || 'Terjadi kesalahan sistem.')
    } finally {
        dispatching.value = false
    }
}

// Run manual local worker from browser
const runWorkerOnce = async () => {
    runningWorker.value = true
    try {
        const response = await axios.post('/utilitas/antrean/run-worker')
        
        if (response.data) {
            if (response.data.success) {
                showModal('success', 'Worker Berhasil', response.data.message || 'Pekerjaan berhasil diproses di background.')
            } else {
                showModal('warning', 'Worker Menjumpai Error / Antrean Kosong', response.data.error || response.data.message || 'Pekerjaan gagal diselesaikan.')
            }
            // Refresh data
            fetchData(currentPage.value)
        }
    } catch (err) {
        console.error(err)
        showModal('error', 'Gagal Memicu Worker', (err.response && err.response.data && err.response.data.error) || err.message || 'Terjadi kesalahan sistem saat memproses antrean.')
    } finally {
        runningWorker.value = false
    }
}

// Retry failed job
const retryJob = async (jobId) => {
    try {
        const response = await axios.post('/utilitas/antrean/retry', { id: jobId })
        if (response.data && response.data.success) {
            notify('success', 'Pekerjaan Diatur Ulang', response.data.message || 'Pekerjaan siap dijalankan kembali.')
            fetchData(currentPage.value)
        }
    } catch (err) {
        console.error(err)
        showModal('error', 'Gagal Memproses Ulang', (err.response && err.response.data && err.response.data.error) || err.message || 'Terjadi kesalahan sistem.')
    }
}

// Delete job from queue
const deleteJob = (jobId) => {
    if (typeof window !== 'undefined' && window.Swal) {
        window.Swal.fire({
            title: 'Hapus Pekerjaan?',
            text: `Apakah Anda yakin ingin menghapus pekerjaan #${jobId} dari antrean sistem?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await axios.post('/utilitas/antrean/delete', { id: jobId })
                    if (response.data && response.data.success) {
                        notify('success', 'Terhapus', response.data.message || 'Pekerjaan berhasil dihapus.')
                        fetchData(currentPage.value)
                    }
                } catch (err) {
                    console.error(err)
                    showModal('error', 'Gagal Menghapus', (err.response && err.response.data && err.response.data.error) || err.message || 'Terjadi kesalahan sistem.')
                }
            }
        })
    } else {
        if (confirm(`Apakah Anda yakin ingin menghapus pekerjaan #${jobId}?`)) {
            axios.post('/utilitas/antrean/delete', { id: jobId }).then(res => {
                if (res.data && res.data.success) {
                    alert('Pekerjaan berhasil dihapus.')
                    fetchData(currentPage.value)
                }
            }).catch(err => {
                alert('Gagal menghapus pekerjaan.')
            })
        }
    }
}

// Lifecycle hooks
onMounted(() => {
    fetchData(1)
    
    // Auto-refresh every 10 seconds
    refreshTimer = setInterval(() => {
        if (autoRefresh.value && !loading.value) {
            fetchData(currentPage.value)
        }
    }, 10000)
})

onUnmounted(() => {
    if (refreshTimer) {
        clearInterval(refreshTimer)
    }
})
</script>

<template>
    <AppLayout title="Antrean Sistem & Background Jobs">
        <div class="space-y-6">
            
            <!-- Modern Header Banner -->
            <div class="relative bg-gradient-to-r from-violet-700 via-indigo-600 to-blue-700 rounded-3xl p-6 md:p-8 text-white shadow-lg overflow-hidden">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/5 rounded-full blur-xl pointer-events-none"></div>
                <div class="absolute -left-10 -bottom-10 w-60 h-60 bg-indigo-400/10 rounded-full blur-2xl pointer-events-none"></div>
                
                <div class="relative flex flex-col md:flex-row items-start md:items-center gap-6 justify-between">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 md:w-20 md:h-20 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 shadow-inner shrink-0">
                            <i class="bi bi-cpu text-3xl md:text-4xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-white mb-1">Antrean Sistem & Background Jobs</h1>
                            <p class="text-white/80 text-sm mb-0">Memantau tugas pemrosesan latar belakang secara real-time dan mengelola performa antrean multi-tenant SaaS platform.</p>
                        </div>
                    </div>

                    <!-- Auto-refresh Status Badge -->
                    <div class="flex items-center gap-2 bg-white/15 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/20 text-xs font-semibold">
                        <span class="w-2 h-2 rounded-full" :class="autoRefresh ? 'bg-emerald-400 animate-pulse' : 'bg-slate-300'"></span>
                        <span>Auto-Refresh (10d):</span>
                        <button type="button" @click="autoRefresh = !autoRefresh" class="text-white underline hover:text-indigo-200 transition">
                            {{ autoRefresh ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI Metric Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- KPI: Pending -->
                <div class="bg-white rounded-3xl p-5 shadow-xs border border-slate-200/80 border-l-4 border-l-amber-500 hover:shadow-md transition">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Menunggu (Pending)</span>
                            <h3 class="text-2xl font-bold text-slate-800 font-mono mb-0">{{ metricsState.pending }}</h3>
                        </div>
                        <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl shadow-inner">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>

                <!-- KPI: Processing -->
                <div class="bg-white rounded-3xl p-5 shadow-xs border border-slate-200/80 border-l-4 border-l-blue-500 hover:shadow-md transition">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Diproses (Processing)</span>
                            <div class="flex items-center gap-2">
                                <h3 class="text-2xl font-bold text-slate-800 font-mono mb-0">{{ metricsState.processing }}</h3>
                                <span v-if="metricsState.processing > 0" class="inline-block w-3.5 h-3.5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></span>
                            </div>
                        </div>
                        <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-xl shadow-inner">
                            <i class="bi bi-gear-wide-connected" :class="{'animate-spin': metricsState.processing > 0}"></i>
                        </div>
                    </div>
                </div>

                <!-- KPI: Completed -->
                <div class="bg-white rounded-3xl p-5 shadow-xs border border-slate-200/80 border-l-4 border-l-emerald-500 hover:shadow-md transition">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Selesai (Completed)</span>
                            <h3 class="text-2xl font-bold text-slate-800 font-mono mb-0">{{ metricsState.completed }}</h3>
                        </div>
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl shadow-inner">
                            <i class="bi bi-patch-check"></i>
                        </div>
                    </div>
                </div>

                <!-- KPI: Failed -->
                <div class="bg-white rounded-3xl p-5 shadow-xs border border-slate-200/80 border-l-4 border-l-rose-500 hover:shadow-md transition">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Gagal (Failed)</span>
                            <h3 class="text-2xl font-bold text-slate-800 font-mono mb-0">{{ metricsState.failed }}</h3>
                        </div>
                        <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shadow-inner">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Panels: Manual Worker & Simulation Forms -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Local Worker Trigger Panel -->
                <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <i class="bi bi-terminal text-blue-600 text-lg"></i>
                            <h2 class="text-sm font-bold text-slate-800 mb-0">Eksekutor Antrean Manual (Web Runner)</h2>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">
                            Jalankan satu tugas terdepan yang berstatus <span class="font-semibold text-amber-600">pending</span> langsung dari browser. Gunakan tombol ini untuk memverifikasi pengerjaan antrean secara instan.
                        </p>
                    </div>
                    
                    <button type="button" 
                            @click="runWorkerOnce" 
                            :disabled="runningWorker || metricsState.pending === 0" 
                            class="w-full h-11 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-500/10 hover:shadow-blue-600/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="runningWorker" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <i v-else class="bi bi-play-circle-fill text-base"></i>
                        {{ runningWorker ? 'Memproses Pekerjaan...' : 'Jalankan Satu Pekerjaan' }}
                    </button>
                </div>

                <!-- Simulation Dispatch Panel -->
                <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200/80 lg:col-span-2 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <i class="bi bi-plus-circle text-indigo-600 text-lg"></i>
                            <h2 class="text-sm font-bold text-slate-800 mb-0">Simulasi Penambahan Pekerjaan Antrean</h2>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">
                            Masukkan tugas tiruan ke dalam antrean sistem untuk menguji pemrosesan latar belakang, penanganan error, dan integrasi multi-tenant.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <!-- Select Simulation Type -->
                            <div>
                                <label for="simJobType" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tipe Simulasi</label>
                                <select id="simJobType" v-model="simJobType" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition">
                                    <option value="DEMO_SYNC_SUCCESS">Sinkronisasi Pusdatin (Simulasi Sukses)</option>
                                    <option value="DEMO_SYNC_FAIL">Sinkronisasi Pusdatin (Simulasi Gagal)</option>
                                    <option value="DEMO_EMAIL">Kirim Email Blast Masal SPMB</option>
                                    <option value="CLEANUP_SESSIONS">Pembersihan Sesi Kedaluwarsa</option>
                                </select>
                            </div>

                            <!-- Select School / Tenant (Super Admin Only) -->
                            <div v-if="isSuperAdmin">
                                <label for="simTenantId" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Sekolah / Tenant</label>
                                <select id="simTenantId" v-model="simTenantId" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition">
                                    <option value="">-- Sistem Global / Universal --</option>
                                    <option v-for="t in tenantsList" :key="t.id" :value="t.id">
                                        {{ t.nama_sekolah }} (NPSN: {{ t.npsn || '-' }})
                                    </option>
                                </select>
                            </div>
                            <div v-else class="flex flex-col justify-end">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Ruang Lingkup</span>
                                <div class="h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 flex items-center text-xs font-semibold text-slate-600">
                                    <i class="bi bi-building me-2 text-indigo-500"></i> Sekolah Anda (Multi-Tenant Terisolasi)
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" 
                            @click="dispatchSimJob" 
                            :disabled="dispatching" 
                            class="w-full h-11 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/10 hover:shadow-indigo-600/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                        <span v-if="dispatching" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <i v-else class="bi bi-box-arrow-in-down text-base"></i>
                        {{ dispatching ? 'Memasukkan ke Antrean...' : 'Tambahkan Tugas ke Antrean' }}
                    </button>
                </div>
            </div>

            <!-- Table Section: Job Queue Logs -->
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200/80">
                
                <!-- Table Header & Filters -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-800 mb-0.5">Daftar Pekerjaan & Antrean Terkini</h2>
                        <p class="text-xs text-slate-500 mb-0">Menampilkan seluruh status pekerjaan background yang tersimpan dalam sistem.</p>
                    </div>

                    <!-- Form Filters (Horizontal Grid) -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        
                        <!-- Status Filter -->
                        <select v-model="filters.status" @change="fetchData(1)" class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition">
                            <option value="">-- Semua Status --</option>
                            <option value="pending">Menunggu (Pending)</option>
                            <option value="processing">Diproses (Processing)</option>
                            <option value="completed">Selesai (Completed)</option>
                            <option value="failed">Gagal (Failed)</option>
                        </select>

                        <!-- Job Type Filter -->
                        <select v-model="filters.job_type" @change="fetchData(1)" class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition">
                            <option value="">-- Semua Jenis --</option>
                            <option value="DEMO_SYNC">DEMO_SYNC</option>
                            <option value="DEMO_EMAIL">DEMO_EMAIL</option>
                            <option value="CLEANUP_SESSIONS">CLEANUP_SESSIONS</option>
                        </select>

                        <!-- Tenant Filter (Super Admin Only) -->
                        <select v-if="isSuperAdmin" v-model="filters.tenant_id" @change="fetchData(1)" class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition max-w-[200px]">
                            <option value="">-- Semua Sekolah --</option>
                            <option v-for="t in tenantsList" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                        </select>

                        <!-- Refresh & Reset Button -->
                        <button type="button" 
                                @click="fetchData(1)" 
                                title="Muat Ulang" 
                                class="w-9 h-9 border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl flex items-center justify-center cursor-pointer transition shadow-2xs">
                            <i class="bi bi-arrow-clockwise" :class="{'animate-spin': loading}"></i>
                        </button>
                        <button type="button" 
                                @click="resetFilter" 
                                title="Reset Filter" 
                                class="w-9 h-9 border border-slate-200 hover:bg-slate-50 text-slate-400 hover:text-slate-600 rounded-xl flex items-center justify-center cursor-pointer transition shadow-2xs">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>

                <!-- Responsive Table -->
                <div class="overflow-x-auto border border-slate-200/80 rounded-2xl mb-6">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200/80">
                            <tr class="text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                                <th scope="col" class="py-3 px-4 w-28">ID Job</th>
                                <th scope="col" class="py-3 px-4 w-48">Sekolah / Tenant</th>
                                <th scope="col" class="py-3 px-4 w-36">Jenis Pekerjaan</th>
                                <th scope="col" class="py-3 px-4">Parameter Payload & Detail</th>
                                <th scope="col" class="py-3 px-4 w-32">Status</th>
                                <th scope="col" class="py-3 px-4 w-48">Waktu Pengerjaan</th>
                                <th scope="col" class="py-3 px-4 text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="loading && jobs.length === 0">
                                <td colspan="7" class="text-center py-10 text-slate-400">
                                    <div class="inline-block w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                                    <p class="text-xs font-semibold mb-0">Memuat log pekerjaan antrean...</p>
                                </td>
                            </tr>
                            <tr v-else-if="jobs.length === 0">
                                <td colspan="7" class="text-center py-10 text-slate-400">
                                    <i class="bi bi-folder2-open text-4xl block mb-2 text-slate-300"></i>
                                    <span class="text-xs font-medium">Tidak ada pekerjaan antrean yang ditemukan.</span>
                                </td>
                            </tr>
                            <tr v-else v-for="job in jobs" :key="job.id" class="hover:bg-slate-50/70 transition">
                                <!-- ID -->
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-600">
                                    <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded-md text-[10px]" :title="job.id">
                                        #{{ job.id.substring(0, 8) }}...
                                    </span>
                                </td>

                                <!-- Sekolah / Tenant -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ job.nama_sekolah || 'Sistem / Global' }}</div>
                                    <div v-if="job.tenant_id" class="text-[10px] text-slate-400 font-mono" :title="job.tenant_id">
                                        {{ job.tenant_id.substring(0, 13) }}...
                                    </div>
                                </td>

                                <!-- Jenis Pekerjaan -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg font-mono font-bold text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-100 shadow-2xs">
                                        {{ job.job_type }}
                                    </span>
                                </td>

                                <!-- Payload -->
                                <td class="py-3.5 px-4">
                                    <div class="bg-slate-50 rounded-xl p-2.5 border border-slate-200/60 max-h-32 overflow-y-auto">
                                        <ul v-if="job.payload && Object.keys(job.payload).length > 0" class="list-disc ps-3.5 mb-0 text-slate-600 font-medium leading-relaxed">
                                            <li v-for="(val, key) in job.payload" :key="key">
                                                <strong class="text-slate-700 font-mono">{{ key }}:</strong> 
                                                <span class="font-mono text-slate-600 ms-1">{{ val }}</span>
                                            </li>
                                        </ul>
                                        <span v-else class="text-slate-400 text-[11px] italic">Payload kosong</span>
                                        
                                        <!-- Error Message Box -->
                                        <div v-if="job.error_message" class="text-rose-600 mt-2 pt-2 border-t border-rose-100 font-semibold font-mono text-[10px] bg-rose-50/50 p-1.5 rounded">
                                            <i class="bi bi-bug me-1"></i>Error: {{ job.error_message }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4">
                                    <span v-if="job.status === 'pending'" class="bg-amber-100 text-amber-800 border border-amber-200/80 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[9px] inline-flex items-center gap-1.5 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending
                                    </span>
                                    <span v-else-if="job.status === 'processing'" class="bg-blue-100 text-blue-800 border border-blue-200/80 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[9px] inline-flex items-center gap-1.5 shadow-2xs">
                                        <span class="inline-block w-2.5 h-2.5 border border-blue-600 border-t-transparent rounded-full animate-spin"></span> Proses
                                    </span>
                                    <span v-else-if="job.status === 'completed'" class="bg-emerald-100 text-emerald-800 border border-emerald-200/80 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[9px] inline-flex items-center gap-1.5 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                                    </span>
                                    <span v-else-if="job.status === 'failed'" class="bg-rose-100 text-rose-800 border border-rose-200/80 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[9px] inline-flex items-center gap-1.5 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Gagal
                                    </span>
                                </td>

                                <!-- Timestamps -->
                                <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px] leading-relaxed">
                                    <div v-if="job.created_at" class="flex items-center gap-1.5">
                                        <i class="bi bi-plus-square text-slate-400"></i>
                                        <span>{{ formatDateTime(job.created_at) }}</span>
                                    </div>
                                    <div v-if="job.reserved_at" class="flex items-center gap-1.5 text-blue-600">
                                        <i class="bi bi-cpu"></i>
                                        <span>{{ formatDateTime(job.reserved_at) }}</span>
                                    </div>
                                    <div v-if="job.completed_at" class="flex items-center gap-1.5 text-emerald-600">
                                        <i class="bi bi-patch-check"></i>
                                        <span>{{ formatDateTime(job.completed_at) }}</span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Retry Action -->
                                        <button v-if="job.status === 'failed'" 
                                                type="button"
                                                @click="retryJob(job.id)" 
                                                title="Proses Ulang" 
                                                class="w-8 h-8 bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200/60 rounded-xl flex items-center justify-center cursor-pointer transition shadow-2xs">
                                            <i class="bi bi-arrow-repeat text-sm"></i>
                                        </button>
                                        <!-- Delete Action -->
                                        <button type="button" 
                                                @click="deleteJob(job.id)" 
                                                title="Hapus Pekerjaan" 
                                                class="w-8 h-8 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/60 rounded-xl flex items-center justify-center cursor-pointer transition shadow-2xs">
                                            <i class="bi bi-trash text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Section -->
                <div v-if="totalPages > 1" class="flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-200/60">
                    <span class="text-xs text-slate-500 font-medium">
                        Halaman <strong class="text-slate-800 font-mono">{{ currentPage }}</strong> dari <strong class="text-slate-800 font-mono">{{ totalPages }}</strong> (Total <strong class="text-slate-800 font-mono">{{ totalCount }}</strong> tugas)
                    </span>
                    
                    <div class="flex gap-2">
                        <button type="button" 
                                :disabled="currentPage === 1" 
                                @click="fetchData(currentPage - 1)" 
                                class="px-3.5 py-1.5 bg-white border border-slate-200/80 hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-xs cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed transition shadow-2xs">
                            <i class="bi bi-chevron-left me-1"></i> Sebelumnya
                        </button>
                        <button type="button" 
                                :disabled="currentPage === totalPages" 
                                @click="fetchData(currentPage + 1)" 
                                class="px-3.5 py-1.5 bg-white border border-slate-200/80 hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-xs cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed transition shadow-2xs">
                            Berikutnya <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
