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
        type: Object,
        default: () => ({ data: [] })
    },
    stats: {
        type: Object,
        default: () => ({
            totalTenants: 0,
            activeTenants: 0,
            suspendedTenants: 0,
            syncedTenants: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})

// Normalisasi Data Tenants
const localTenants = ref(
    props.tenantsList && props.tenantsList.length > 0 
        ? [...props.tenantsList] 
        : (props.tenants?.data ? [...props.tenants.data] : [])
)

// Search & Filter State
const searchQuery = ref(props.filters?.search || '')
const filterStatus = ref(props.filters?.status || '')
const filterPaket = ref(props.filters?.paket || '')
const filterSinkronisasi = ref(props.filters?.sinkronisasi || '')

// Pagination State (AGENTS.md Standard)
const perPage = ref(15)
const currentPage = ref(1)

const totalTenants = computed(() => filteredTenants.value.length)
const lastPage = computed(() => Math.ceil(totalTenants.value / perPage.value) || 1)
const paginatedTenants = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredTenants.value.slice(start, start + perPage.value)
})

const goToPage = (page) => {
    if (!page || page === currentPage.value) return
    currentPage.value = page
}

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

// Modal & Form State
const showModal = ref(false)
const isEditMode = ref(false)
const activeModalTab = ref('profile')
const isSaving = ref(false)
const errors = ref({})

const form = ref({
    id: '',
    nama_sekolah: '',
    npsn: '',
    subdomain: '',
    domain: '',
    custom_domain: '',
    bentuk_pendidikan: 'SMA',
    status_sekolah: 'Negeri',
    paket_aktif: 'Premium SaaS',
    status_sinkronisasi: 'Tersinkronisasi',
    status: 'active',
    storage_limit_mb: 1024,
    max_siswa_limit: 1000,
    max_staff_limit: 100,
    enable_bk: 1,
    enable_tracer: 1,
    enable_ppdb: 1,
    enable_perpustakaan: 1,
    enable_keuangan: 1,
    enable_pdss: 1,
    enable_smk: 0,
    enable_sarpras: 1,
    enable_persuratan: 1,
    cms_landing_enabled: true
})

// SweetAlert Notification Helper
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

const showAlert = (icon, title, text) => {
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

// Computed: Filtered Tenants
const filteredTenants = computed(() => {
    return localTenants.value.filter(t => {
        const q = searchQuery.value.toLowerCase().trim()
        const matchesSearch = !q || 
            (t.nama_sekolah && t.nama_sekolah.toLowerCase().includes(q)) ||
            (t.npsn && t.npsn.toLowerCase().includes(q)) ||
            (t.subdomain && t.subdomain.toLowerCase().includes(q)) ||
            ((t.domain || t.custom_domain) && (t.domain || t.custom_domain).toLowerCase().includes(q))

        const matchesStatus = !filterStatus.value || t.status === filterStatus.value
        const matchesPaket = !filterPaket.value || t.paket_aktif === filterPaket.value
        const matchesSync = !filterSinkronisasi.value || t.status_sinkronisasi === filterSinkronisasi.value

        return matchesSearch && matchesStatus && matchesPaket && matchesSync
    })
})

// Statistics Computed
const totalTenantsCount = computed(() => props.stats?.totalTenants || localTenants.value.length)
const activeTenantsCount = computed(() => props.stats?.activeTenants || localTenants.value.filter(t => t.status === 'active').length)
const suspendedTenantsCount = computed(() => props.stats?.suspendedTenants || localTenants.value.filter(t => t.status === 'suspended' || t.status === 'inactive').length)
const syncedTenantsCount = computed(() => props.stats?.syncedTenants || localTenants.value.filter(t => t.status_sinkronisasi === 'Tersinkronisasi').length)

// UI Helpers
const getInitials = (name) => {
    if (!name) return 'S'
    const words = name.split(' ')
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
}

const getPaketBadge = (paket) => {
    switch (paket) {
        case 'Enterprise SaaS':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200'
        case 'Premium SaaS':
            return 'bg-purple-50 text-purple-700 border-purple-200'
        case 'Pro':
            return 'bg-blue-50 text-blue-700 border-blue-200'
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200'
    }
}

const getSyncBadge = (sync) => {
    switch (sync) {
        case 'Tersinkronisasi':
            return {
                badge: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                dot: 'bg-emerald-500'
            }
        case 'Menunggu':
            return {
                badge: 'bg-amber-50 text-amber-700 border-amber-200',
                dot: 'bg-amber-500'
            }
        default:
            return {
                badge: 'bg-rose-50 text-rose-700 border-rose-200',
                dot: 'bg-rose-500'
            }
    }
}

const getStatusBadge = (status) => {
    switch (status) {
        case 'active':
            return {
                badge: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                dot: 'bg-emerald-500',
                label: 'Active'
            }
        case 'suspended':
            return {
                badge: 'bg-amber-50 text-amber-700 border-amber-200',
                dot: 'bg-amber-500',
                label: 'Suspended'
            }
        default:
            return {
                badge: 'bg-rose-50 text-rose-700 border-rose-200',
                dot: 'bg-rose-400',
                label: 'Inactive'
            }
    }
}

// Subdomain Slugify Handler
const onSubdomainInput = () => {
    form.value.subdomain = form.value.subdomain
        .toLowerCase()
        .replace(/[^a-z0-9-]/g, '-')
        .replace(/-+/g, '-')
}

// Package Defaults Auto Fill
const applyPackageDefaults = () => {
    const paket = form.value.paket_aktif
    if (paket === 'Basic') {
        form.value.storage_limit_mb = 50
        form.value.max_siswa_limit = 100
        form.value.max_staff_limit = 10
        form.value.enable_bk = 0
        form.value.enable_tracer = 0
        form.value.enable_ppdb = 0
        form.value.enable_perpustakaan = 0
        form.value.enable_keuangan = 0
        form.value.enable_pdss = 0
        form.value.enable_sarpras = 0
        form.value.enable_persuratan = 0
        form.value.cms_landing_enabled = false
    } else if (paket === 'Pro') {
        form.value.storage_limit_mb = 250
        form.value.max_siswa_limit = 500
        form.value.max_staff_limit = 50
        form.value.enable_bk = 1
        form.value.enable_tracer = 0
        form.value.enable_ppdb = 1
        form.value.enable_perpustakaan = 1
        form.value.enable_keuangan = 0
        form.value.enable_pdss = 0
        form.value.enable_sarpras = 1
        form.value.enable_persuratan = 1
        form.value.cms_landing_enabled = true
    } else if (paket === 'Premium SaaS') {
        form.value.storage_limit_mb = 1024
        form.value.max_siswa_limit = 1000
        form.value.max_staff_limit = 100
        form.value.enable_bk = 1
        form.value.enable_tracer = 1
        form.value.enable_ppdb = 1
        form.value.enable_perpustakaan = 1
        form.value.enable_keuangan = 1
        form.value.enable_pdss = 1
        form.value.enable_sarpras = 1
        form.value.enable_persuratan = 1
        form.value.cms_landing_enabled = true
    } else if (paket === 'Enterprise SaaS') {
        form.value.storage_limit_mb = 5120
        form.value.max_siswa_limit = 99999
        form.value.max_staff_limit = 999
        form.value.enable_bk = 1
        form.value.enable_tracer = 1
        form.value.enable_ppdb = 1
        form.value.enable_perpustakaan = 1
        form.value.enable_keuangan = 1
        form.value.enable_pdss = 1
        form.value.enable_sarpras = 1
        form.value.enable_persuratan = 1
        form.value.cms_landing_enabled = true
    }
}

// Open Modal: Add
const openAddModal = () => {
    isEditMode.value = false
    activeModalTab.value = 'profile'
    errors.value = {}
    form.value = {
        id: '',
        nama_sekolah: '',
        npsn: '',
        subdomain: '',
        domain: '',
        custom_domain: '',
        bentuk_pendidikan: 'SMA',
        status_sekolah: 'Negeri',
        paket_aktif: 'Premium SaaS',
        status_sinkronisasi: 'Tersinkronisasi',
        status: 'active',
        storage_limit_mb: 1024,
        max_siswa_limit: 1000,
        max_staff_limit: 100,
        enable_bk: 1,
        enable_tracer: 1,
        enable_ppdb: 1,
        enable_perpustakaan: 1,
        enable_keuangan: 1,
        enable_pdss: 1,
        enable_smk: 0,
        enable_sarpras: 1,
        enable_persuratan: 1,
        cms_landing_enabled: true
    }
    showModal.value = true
}

// Open Modal: Edit
const openEditModal = (tenant) => {
    isEditMode.value = true
    activeModalTab.value = 'profile'
    errors.value = {}
    form.value = {
        id: tenant.id,
        nama_sekolah: tenant.nama_sekolah,
        npsn: tenant.npsn,
        subdomain: tenant.subdomain,
        domain: tenant.domain || tenant.custom_domain || '',
        custom_domain: tenant.custom_domain || tenant.domain || '',
        bentuk_pendidikan: tenant.bentuk_pendidikan || 'SMA',
        status_sekolah: tenant.status_sekolah || 'Negeri',
        paket_aktif: tenant.paket_aktif || 'Premium SaaS',
        status_sinkronisasi: tenant.status_sinkronisasi || 'Tersinkronisasi',
        status: tenant.status || 'active',
        storage_limit_mb: tenant.storage_limit_mb ? parseInt(tenant.storage_limit_mb) : 1024,
        max_siswa_limit: tenant.max_siswa_limit ? parseInt(tenant.max_siswa_limit) : 1000,
        max_staff_limit: tenant.max_staff_limit ? parseInt(tenant.max_staff_limit) : 100,
        enable_bk: tenant.enable_bk !== undefined ? parseInt(tenant.enable_bk) : 1,
        enable_tracer: tenant.enable_tracer !== undefined ? parseInt(tenant.enable_tracer) : 1,
        enable_ppdb: tenant.enable_ppdb !== undefined ? parseInt(tenant.enable_ppdb) : 1,
        enable_perpustakaan: tenant.enable_perpustakaan !== undefined ? parseInt(tenant.enable_perpustakaan) : 1,
        enable_keuangan: tenant.enable_keuangan !== undefined ? parseInt(tenant.enable_keuangan) : 1,
        enable_pdss: tenant.enable_pdss !== undefined ? parseInt(tenant.enable_pdss) : 1,
        enable_smk: tenant.enable_smk !== undefined ? parseInt(tenant.enable_smk) : (tenant.bentuk_pendidikan === 'SMK' ? 1 : 0),
        enable_sarpras: tenant.enable_sarpras !== undefined ? parseInt(tenant.enable_sarpras) : 1,
        enable_persuratan: tenant.enable_persuratan !== undefined ? parseInt(tenant.enable_persuratan) : 1,
        cms_landing_enabled: tenant.cms_landing_enabled !== undefined ? Boolean(tenant.cms_landing_enabled) : true
    }
    showModal.value = true
}

// Submit Form
const submitForm = async () => {
    isSaving.value = true
    errors.value = {}

    try {
        const response = await axios.post('/super-admin/tenants/simpan', form.value)
        if (response.data && response.data.success) {
            showModal.value = false
            notify('success', 'Berhasil Disimpan', response.data.message || 'Data sekolah berhasil disimpan.')
            
            // Reload list via fetch
            fetchTenants()
        }
    } catch (err) {
        if (err.response && err.response.status === 422) {
            errors.value = err.response.data.errors || {}
            showAlert('warning', 'Validasi Gagal', err.response.data.error || 'Harap periksa kembali isian formulir Anda.')
        } else {
            const errorMsg = (err.response && err.response.data && err.response.data.error) || err.message || 'Terjadi kesalahan sistem saat menyimpan data.'
            showAlert('error', 'Penyimpanan Gagal', errorMsg)
        }
    } finally {
        isSaving.value = false
    }
}

// Fetch Tenants from API
const fetchTenants = async () => {
    try {
        const response = await axios.get('/super-admin/tenants', {
            headers: { 'Accept': 'application/json' }
        })
        if (response.data && response.data.success) {
            localTenants.value = response.data.data || []
        }
    } catch (err) {
        console.error('Failed to fetch tenants:', err)
    }
}

// Toggle Active/Inactive Status with SweetAlert Confirmation
const toggleActiveStatus = (tenant, targetStatus) => {
    const isDeactivating = targetStatus === 'inactive' || targetStatus === 'suspended'
    const actionText = targetStatus === 'active' ? 'mengaktifkan kembali' : (targetStatus === 'suspended' ? 'menangguhkan' : 'menonaktifkan')
    const confirmColor = targetStatus === 'active' ? '#10b981' : '#f59e0b'

    if (typeof window !== 'undefined' && window.Swal) {
        window.Swal.fire({
            title: `Apakah Anda yakin?`,
            html: `Anda akan <strong>${actionText}</strong> akses sekolah <strong>${tenant.nama_sekolah}</strong>.<br><br>` +
                  (isDeactivating ? `<span class="text-rose-600 font-semibold text-xs"><i class="bi bi-exclamation-triangle-fill me-1"></i> PENTING: Seluruh user (Admin, Guru, Siswa) dari sekolah ini tidak akan bisa login dan otomatis dikeluarkan dari sesi aktif!</span>` : ''),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#64748b',
            confirmButtonText: `Ya, ${targetStatus === 'active' ? 'Aktifkan' : 'Nonaktifkan'}!`,
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await axios.post('/super-admin/tenants/toggle-status', {
                        id: tenant.id,
                        status: targetStatus
                    })
                    if (response.data && response.data.success) {
                        notify('success', 'Status Diperbarui', response.data.message)
                        fetchTenants()
                    }
                } catch (err) {
                    const errorMsg = (err.response && err.response.data && err.response.data.error) || 'Gagal mengubah status akses.'
                    showAlert('error', 'Gagal Memperbarui', errorMsg)
                }
            }
        })
    }
}

// Delete Tenant with SweetAlert Confirmation
const deleteTenant = (tenant) => {
    if (tenant.id === '00000000-0000-0000-0000-000000000000') {
        showAlert('warning', 'Tidak Diizinkan', 'Tenant Pusat Kendali SaaS Global tidak dapat dihapus.')
        return
    }

    if (typeof window !== 'undefined' && window.Swal) {
        window.Swal.fire({
            title: 'Hapus Sekolah?',
            html: `Anda akan menghapus instansi sekolah <strong>${tenant.nama_sekolah}</strong>.<br><br><span class="text-rose-600 font-semibold text-xs"><i class="bi bi-exclamation-triangle-fill me-1"></i> PENTING: Seluruh pemetaan akses menu dan data terkait akan dibersihkan!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Sekolah!',
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await axios.post('/super-admin/tenants/hapus', {
                        id: tenant.id
                    })
                    if (response.data && response.data.success) {
                        notify('success', 'Sekolah Dihapus', response.data.message)
                        fetchTenants()
                    }
                } catch (err) {
                    const errorMsg = (err.response && err.response.data && err.response.data.error) || 'Gagal menghapus sekolah.'
                    showAlert('error', 'Gagal Menghapus', errorMsg)
                }
            }
        })
    }
}
</script>

<template>
    <AppLayout title="Kelola Sekolah (SaaS Tenant Management)">
        <div class="space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
                            <i class="bi bi-buildings"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Sekolah (Tenant Management)</h1>
                            <p class="text-xs text-slate-500 mb-0">Kelola instansi sekolah mitra, konfigurasi routing domain/subdomain, paket berlangganan, kuota penyimpanan, dan kontrol akses multi-tenant.</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        @click="openAddModal"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs flex items-center gap-2 transition shadow-md shadow-indigo-500/10 cursor-pointer"
                    >
                        <i class="bi bi-plus-lg"></i> Tambah Sekolah Baru
                    </button>
                </div>
            </div>

            <!-- 4 Statistics Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                
                <!-- Card 1: Total Sekolah -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Sekolah Mitra</span>
                        <h3 class="text-2xl font-black text-slate-800 font-mono m-0">{{ totalTenantsCount }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                </div>

                <!-- Card 2: Tenant Aktif -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tenant Aktif</span>
                        <h3 class="text-2xl font-black text-emerald-600 font-mono m-0">{{ activeTenantsCount }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>

                <!-- Card 3: Ditangguhkan / Nonaktif -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Nonaktif / Suspended</span>
                        <h3 class="text-2xl font-black text-rose-600 font-mono m-0">{{ suspendedTenantsCount }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>

                <!-- Card 4: Tersinkronisasi -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tersinkronisasi</span>
                        <h3 class="text-2xl font-black text-indigo-600 font-mono m-0">{{ syncedTenantsCount }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="bi bi-cloud-check-fill"></i>
                    </div>
                </div>

            </div>

            <!-- Main Content: 3-Part Unified Data Table Card (Standar Baku SINTA SaaS) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                
                <!-- 1. Bagian 1: Filter Bar Atas (Standar Baku AGENTS.md) -->
                <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
                    <form @submit.prevent="currentPage = 1" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
                        
                        <!-- Filter Paket -->
                        <div class="w-36 sm:w-44 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Paket Langganan</label>
                            <select 
                                v-model="filterPaket"
                                @change="currentPage = 1"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition font-sans"
                            >
                                <option value="">-- Semua Paket --</option>
                                <option value="Basic">Basic Edition</option>
                                <option value="Pro">Pro Edition</option>
                                <option value="Premium SaaS">Premium SaaS</option>
                                <option value="Enterprise SaaS">Enterprise SaaS</option>
                            </select>
                        </div>

                        <!-- Filter Status Akses -->
                        <div class="w-36 sm:w-40 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Status Akses</label>
                            <select 
                                v-model="filterStatus"
                                @change="currentPage = 1"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                            >
                                <option value="">-- Semua Status --</option>
                                <option value="active">Active (Aktif)</option>
                                <option value="inactive">Inactive (Nonaktif)</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>

                        <!-- Filter Status Sinkronisasi -->
                        <div class="w-36 sm:w-44 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Sinkronisasi</label>
                            <select 
                                v-model="filterSinkronisasi"
                                @change="currentPage = 1"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                            >
                                <option value="">-- Semua Sinkronisasi --</option>
                                <option value="Tersinkronisasi">Tersinkronisasi</option>
                                <option value="Menunggu">Menunggu</option>
                                <option value="Gagal">Gagal</option>
                            </select>
                        </div>

                        <!-- Search Input (Proposional w-64 s.d. w-80) -->
                        <div class="w-64 sm:w-72 md:w-80 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Pencarian</label>
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                <input 
                                    type="text" 
                                    v-model="searchQuery" 
                                    @input="currentPage = 1"
                                    placeholder="Cari nama sekolah, NPSN, subdomain..." 
                                    class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs" 
                                />
                                <button 
                                    v-if="searchQuery" 
                                    @click="searchQuery = ''; currentPage = 1" 
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
                                @click="searchQuery = ''; filterPaket = ''; filterStatus = ''; filterSinkronisasi = ''; currentPage = 1" 
                                class="h-9 px-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs transition shadow-2xs whitespace-nowrap cursor-pointer"
                            >
                                Reset
                            </button>
                        </div>

                    </form>
                </div>

                <!-- 2. Bagian 2: Tabel Data (Standar Kolom & Layout Baku) -->
                <div class="overflow-x-auto relative min-h-[300px]">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                                <th scope="col" class="py-3 px-3 w-12 text-center whitespace-nowrap">NO</th>
                                <th scope="col" class="py-3 px-4 min-w-[240px]">NAMA INSTANSI SEKOLAH</th>
                                <th scope="col" class="py-3 px-3 text-center w-28 whitespace-nowrap">NPSN</th>
                                <th scope="col" class="py-3 px-4 min-w-[200px]">SUBDOMAIN / DOMAIN</th>
                                <th scope="col" class="py-3 px-3.5 w-36 whitespace-nowrap">PAKET LANGGANAN</th>
                                <th scope="col" class="py-3 px-3.5 w-36 whitespace-nowrap">SINKRONISASI</th>
                                <th scope="col" class="py-3 px-3.5 w-28 whitespace-nowrap">STATUS AKSES</th>
                                <th scope="col" class="py-3 px-4 text-center whitespace-nowrap sticky right-0 bg-slate-50 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] w-48">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredTenants.length === 0">
                                <td colspan="8" class="text-center py-16 text-slate-400">
                                    <i class="bi bi-building-slash text-4xl block mb-2 text-slate-300"></i>
                                    <span class="text-xs font-medium">Tidak ada data instansi sekolah yang sesuai dengan pencarian atau filter Anda.</span>
                                </td>
                            </tr>
                            <tr v-else v-for="(tenant, idx) in paginatedTenants" :key="tenant.id" class="hover:bg-blue-50/40 transition border-b border-slate-100 group">
                                <td class="py-3 px-3 text-center font-mono text-slate-400 text-xs w-12 whitespace-nowrap">
                                    {{ (currentPage - 1) * perPage + idx + 1 }}
                                </td>
                                
                                <!-- School Name & Avatar -->
                                <td class="py-3 px-4 min-w-[240px]">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 font-bold flex items-center justify-center text-xs shrink-0 shadow-inner">
                                            {{ getInitials(tenant.nama_sekolah) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                                {{ tenant.nama_sekolah }}
                                                <span v-if="tenant.id === '00000000-0000-0000-0000-000000000000'" class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">
                                                    ROOT SAAS
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ tenant.id }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- NPSN -->
                                <td class="py-3 px-3 text-center whitespace-nowrap w-28">
                                    <span class="inline-block px-2 py-0.5 rounded-lg text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200/80">
                                        {{ tenant.npsn }}
                                    </span>
                                </td>

                                <!-- Subdomain & Domain -->
                                <td class="py-3 px-4 min-w-[200px]">
                                    <div class="space-y-0.5">
                                        <div class="font-mono text-xs font-semibold text-blue-600">
                                            {{ tenant.subdomain }}.sinta-saas.id
                                        </div>
                                        <div v-if="tenant.domain || tenant.custom_domain" class="text-[10px] text-slate-400 font-mono flex items-center gap-1">
                                            <i class="bi bi-globe text-slate-400"></i>
                                            <span>{{ tenant.domain || tenant.custom_domain }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Paket Langganan -->
                                <td class="py-3 px-3.5 whitespace-nowrap w-36">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-bold border shadow-2xs" :class="getPaketBadge(tenant.paket_aktif)">
                                        <i class="bi bi-gem text-xs" v-if="tenant.paket_aktif === 'Enterprise SaaS' || tenant.paket_aktif === 'Premium SaaS'"></i>
                                        <i class="bi bi-patch-check text-xs" v-else></i>
                                        {{ tenant.paket_aktif }}
                                    </span>
                                </td>

                                <!-- Sinkronisasi -->
                                <td class="py-3 px-3.5 whitespace-nowrap w-36">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold border shadow-2xs" :class="getSyncBadge(tenant.status_sinkronisasi).badge">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getSyncBadge(tenant.status_sinkronisasi).dot"></span>
                                        {{ tenant.status_sinkronisasi }}
                                    </span>
                                </td>

                                <!-- Status Akses -->
                                <td class="py-3 px-3.5 whitespace-nowrap w-28">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold border shadow-2xs" :class="getStatusBadge(tenant.status).badge">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(tenant.status).dot"></span>
                                        {{ getStatusBadge(tenant.status).label }}
                                    </span>
                                </td>

                                <!-- Aksi (Sticky Right) -->
                                <td class="py-3 px-4 text-center whitespace-nowrap sticky right-0 bg-white group-hover:bg-blue-50/40 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] transition w-48">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit Button -->
                                        <button 
                                            type="button" 
                                            @click="openEditModal(tenant)" 
                                            class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/80 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer"
                                            title="Edit Profil Sekolah"
                                        >
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>

                                        <!-- Toggle Status Button -->
                                        <button 
                                            v-if="tenant.status === 'active' && tenant.id !== '00000000-0000-0000-0000-000000000000'"
                                            type="button" 
                                            @click="toggleActiveStatus(tenant, 'inactive')" 
                                            class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200/80 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer"
                                            title="Nonaktifkan Sekolah"
                                        >
                                            <i class="bi bi-shield-slash"></i> Nonaktifkan
                                        </button>
                                        <button 
                                            v-else-if="tenant.id !== '00000000-0000-0000-0000-000000000000'"
                                            type="button" 
                                            @click="toggleActiveStatus(tenant, 'active')" 
                                            class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer"
                                            title="Aktifkan Sekolah"
                                        >
                                            <i class="bi bi-shield-check"></i> Aktifkan
                                        </button>

                                        <!-- Delete Button -->
                                        <button 
                                            v-if="tenant.id !== '00000000-0000-0000-0000-000000000000'"
                                            type="button" 
                                            @click="deleteTenant(tenant)" 
                                            class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition shadow-2xs cursor-pointer"
                                            title="Hapus Sekolah"
                                        >
                                            <i class="bi bi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 3. Bagian 3: Footer Pagination (Standar Baku AGENTS.md) -->
                <div v-if="filteredTenants.length > 0" 
                     class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
                    
                    <!-- Info Tampilkan Baris & Dropdown Per-Page -->
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
                        <span>Tampilkan</span>
                        <select 
                            v-model="perPage" 
                            @change="currentPage = 1" 
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
                            Menampilkan <span class="font-bold text-slate-800">{{ (currentPage - 1) * perPage + 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ Math.min(currentPage * perPage, filteredTenants.length) }}</span> dari <span class="font-bold text-slate-800">{{ filteredTenants.length }}</span> sekolah mitra
                        </span>
                    </div>

                    <!-- Pagination Links (Smart Windowing & Compact Chevrons) -->
                    <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
                        <template v-for="(link, i) in getSmartPaginationLinks({ currentPage, lastPage, total: filteredTenants.length })" :key="i">
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

        <!-- MODAL DIALOG: Tambah / Edit Sekolah -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200 relative z-10">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4 flex items-center justify-between text-white">
                    <div>
                        <h3 class="text-base font-bold flex items-center gap-2 m-0">
                            <i class="bi bi-building"></i>
                            {{ isEditMode ? 'Perbarui Profil Sekolah (Tenant)' : 'Pendaftaran Sekolah Baru' }}
                        </h3>
                        <p class="text-xs text-indigo-100 mb-0 mt-0.5">Konfigurasi data identitas, routing subdomain, dan kuota berlangganan SaaS.</p>
                    </div>
                    <button type="button" @click="showModal = false" class="text-white/80 hover:text-white text-xl cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Modal Tabs -->
                <div class="flex border-b border-slate-200 bg-slate-50/70 px-6 pt-3 gap-2">
                    <button 
                        type="button" 
                        @click="activeModalTab = 'profile'"
                        class="px-4 py-2 text-xs font-bold rounded-t-xl transition flex items-center gap-2 cursor-pointer border-t-2"
                        :class="activeModalTab === 'profile' ? 'bg-white text-indigo-600 border-indigo-600 border-x border-slate-200 -mb-px' : 'text-slate-500 hover:text-slate-700 border-transparent'"
                    >
                        <i class="bi bi-info-circle"></i> Profil & Routing
                    </button>
                    <button 
                        type="button" 
                        @click="activeModalTab = 'subscription'"
                        class="px-4 py-2 text-xs font-bold rounded-t-xl transition flex items-center gap-2 cursor-pointer border-t-2"
                        :class="activeModalTab === 'subscription' ? 'bg-white text-indigo-600 border-indigo-600 border-x border-slate-200 -mb-px' : 'text-slate-500 hover:text-slate-700 border-transparent'"
                    >
                        <i class="bi bi-gem"></i> Paket Langganan & Kapasitas
                    </button>
                </div>

                <!-- Modal Form Body -->
                <form @submit.prevent="submitForm">
                    <div class="p-6 max-h-[65vh] overflow-y-auto space-y-4">
                        
                        <!-- TAB 1: Profil & Routing -->
                        <div v-show="activeModalTab === 'profile'" class="space-y-4">
                            
                            <!-- Nama Sekolah -->
                            <div>
                                <label for="modal_nama_sekolah" class="block text-xs font-bold text-slate-700 mb-1">
                                    Nama Instansi Sekolah <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    id="modal_nama_sekolah" 
                                    type="text" 
                                    v-model="form.nama_sekolah" 
                                    placeholder="Contoh: SMA Negeri 1 Jakarta" 
                                    required 
                                    class="w-full h-10 px-3.5 rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                />
                                <span v-if="errors.nama_sekolah" class="text-rose-500 text-[11px] block mt-1">{{ errors.nama_sekolah[0] }}</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- NPSN -->
                                <div>
                                    <label for="modal_npsn" class="block text-xs font-bold text-slate-700 mb-1">
                                        NPSN (Nomor Pokok Sekolah) <span class="text-rose-500">*</span>
                                    </label>
                                    <input 
                                        id="modal_npsn" 
                                        type="text" 
                                        v-model="form.npsn" 
                                        placeholder="Contoh: 10203040" 
                                        required 
                                        class="w-full h-10 px-3.5 font-mono rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                    />
                                    <span v-if="errors.npsn" class="text-rose-500 text-[11px] block mt-1">{{ errors.npsn[0] }}</span>
                                </div>

                                <!-- Bentuk Pendidikan / Jenjang -->
                                <div>
                                    <label for="modal_jenjang" class="block text-xs font-bold text-slate-700 mb-1">
                                        Bentuk Pendidikan / Jenjang <span class="text-rose-500">*</span>
                                    </label>
                                    <select 
                                        id="modal_jenjang" 
                                        v-model="form.bentuk_pendidikan" 
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                    >
                                        <option value="SMA">SMA (Sekolah Menengah Atas)</option>
                                        <option value="SMK">SMK (Sekolah Menengah Kejuruan)</option>
                                        <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                                        <option value="SD">SD (Sekolah Dasar)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Subdomain -->
                                <div>
                                    <label for="modal_subdomain" class="block text-xs font-bold text-slate-700 mb-1">
                                        Subdomain Aplikasi <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="flex">
                                        <input 
                                            id="modal_subdomain" 
                                            type="text" 
                                            v-model="form.subdomain" 
                                            @input="onSubdomainInput"
                                            placeholder="sman1jkt" 
                                            required 
                                            class="w-full h-10 px-3 font-mono font-bold text-indigo-700 rounded-l-xl border border-r-0 border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                        />
                                        <span class="inline-flex items-center px-3 text-xs text-slate-500 bg-slate-100 border border-slate-200 rounded-r-xl font-mono">
                                            .sinta-saas.id
                                        </span>
                                    </div>
                                    <span v-if="errors.subdomain" class="text-rose-500 text-[11px] block mt-1">{{ errors.subdomain[0] }}</span>
                                </div>

                                <!-- Custom Domain -->
                                <div>
                                    <label for="modal_domain" class="block text-xs font-bold text-slate-700 mb-1">
                                        Domain Kustom <small class="text-slate-400 font-normal">(Opsional)</small>
                                    </label>
                                    <input 
                                        id="modal_domain" 
                                        type="text" 
                                        v-model="form.domain" 
                                        placeholder="Contoh: sman1jakarta.sch.id" 
                                        class="w-full h-10 px-3.5 font-mono rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                    />
                                    <span v-if="errors.domain" class="text-rose-500 text-[11px] block mt-1">{{ errors.domain[0] }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Status Akses -->
                                <div>
                                    <label for="modal_status" class="block text-xs font-bold text-slate-700 mb-1">
                                        Status Akses Sekolah <span class="text-rose-500">*</span>
                                    </label>
                                    <select 
                                        id="modal_status" 
                                        v-model="form.status" 
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                    >
                                        <option value="active">Active (Aktif)</option>
                                        <option value="inactive">Inactive (Nonaktif)</option>
                                        <option value="suspended">Suspended (Ditangguhkan)</option>
                                    </select>
                                </div>

                                <!-- Status Sinkronisasi -->
                                <div>
                                    <label for="modal_sync" class="block text-xs font-bold text-slate-700 mb-1">
                                        Status Sinkronisasi <span class="text-rose-500">*</span>
                                    </label>
                                    <select 
                                        id="modal_sync" 
                                        v-model="form.status_sinkronisasi" 
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                    >
                                        <option value="Tersinkronisasi">Tersinkronisasi</option>
                                        <option value="Menunggu">Menunggu</option>
                                        <option value="Gagal">Gagal</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <!-- TAB 2: Paket Langganan & Kapasitas -->
                        <div v-show="activeModalTab === 'subscription'" class="space-y-4">
                            
                            <!-- Paket Langganan Aktif -->
                            <div>
                                <label for="modal_paket" class="block text-xs font-bold text-slate-700 mb-1">
                                    Paket Langganan Aktif <span class="text-rose-500">*</span>
                                </label>
                                <select 
                                    id="modal_paket" 
                                    v-model="form.paket_aktif" 
                                    @change="applyPackageDefaults"
                                    class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                >
                                    <option value="Basic">Basic Edition (Kapasitas Minimum)</option>
                                    <option value="Pro">Pro Edition (Menengah)</option>
                                    <option value="Premium SaaS">Premium SaaS (Standar Unggulan)</option>
                                    <option value="Enterprise SaaS">Enterprise SaaS (Kapasitas Tak Terbatas)</option>
                                </select>
                                <span class="text-[11px] text-slate-400 block mt-1">
                                    <i class="bi bi-info-circle me-1"></i> Memilih paket otomatis menyesuaikan kuota penyimpanan dan batas kapasitas di bawah.
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- Kuota Storage MB -->
                                <div>
                                    <label for="modal_storage" class="block text-xs font-bold text-slate-700 mb-1">
                                        Kuota Storage (MB) <span class="text-rose-500">*</span>
                                    </label>
                                    <input 
                                        id="modal_storage" 
                                        type="number" 
                                        v-model="form.storage_limit_mb" 
                                        min="10" 
                                        required 
                                        class="w-full h-10 px-3 font-mono font-bold rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                    />
                                </div>

                                <!-- Batas Maks. Siswa -->
                                <div>
                                    <label for="modal_siswa" class="block text-xs font-bold text-slate-700 mb-1">
                                        Maks. Siswa <span class="text-rose-500">*</span>
                                    </label>
                                    <input 
                                        id="modal_siswa" 
                                        type="number" 
                                        v-model="form.max_siswa_limit" 
                                        min="0" 
                                        required 
                                        class="w-full h-10 px-3 font-mono font-bold rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                    />
                                </div>

                                <!-- Batas Maks. Staf -->
                                <div>
                                    <label for="modal_staff" class="block text-xs font-bold text-slate-700 mb-1">
                                        Maks. Guru / Staf <span class="text-rose-500">*</span>
                                    </label>
                                    <input 
                                        id="modal_staff" 
                                        type="number" 
                                        v-model="form.max_staff_limit" 
                                        min="0" 
                                        required 
                                        class="w-full h-10 px-3 font-mono font-bold rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition"
                                    />
                                </div>
                            </div>

                            <!-- Modul Switches Grid -->
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-800">Aktivasi Modul Fitur Sekolah</span>
                                        <p class="text-[11px] text-slate-500 mb-0">Aktifkan atau nonaktifkan modul add-on platform yang tersedia untuk sekolah ini.</p>
                                    </div>
                                    <Link :href="'/super-admin/tenant-menus'" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition">
                                        <i class="bi bi-shield-lock-fill"></i> Matriks 51 Menu &rarr;
                                    </Link>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                                    
                                    <!-- 1. BK -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.enable_bk ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-person-heart"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">Bimbingan Konseling (BK)</span>
                                                <span class="text-[10px] text-slate-400">Layanan & catatan kedisiplinan</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.enable_bk" :true-value="1" :false-value="0" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                    <!-- 2. Tracer Study -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.enable_tracer ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-mortarboard-fill"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">Tracer Study (Alumni)</span>
                                                <span class="text-[10px] text-slate-400">Kuesioner karier & alumni</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.enable_tracer" :true-value="1" :false-value="0" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                    <!-- 3. PPDB Online -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.enable_ppdb ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-clipboard-check-fill"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">PPDB Online</span>
                                                <span class="text-[10px] text-slate-400">Pendaftaran calon siswa baru</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.enable_ppdb" :true-value="1" :false-value="0" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                    <!-- 4. Perpustakaan -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.enable_perpustakaan ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-book-half"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">Perpustakaan Digital</span>
                                                <span class="text-[10px] text-slate-400">Katalog buku, sirkulasi & OPAC</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.enable_perpustakaan" :true-value="1" :false-value="0" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                    <!-- 5. Keuangan & SPP -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.enable_keuangan ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-cash-coin"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">Keuangan & SPP</span>
                                                <span class="text-[10px] text-slate-400">Tagihan pos tarif & payment</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.enable_keuangan" :true-value="1" :false-value="0" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                    <!-- 6. PDSS -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.enable_pdss ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-trophy-fill"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">PDSS & Seleksi Nasional</span>
                                                <span class="text-[10px] text-slate-400">Pemetaan nilai SNBP siswa</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.enable_pdss" :true-value="1" :false-value="0" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                    <!-- 7. Sarpras -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.enable_sarpras ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-box-seam-fill"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">Sarpras & Inventaris</span>
                                                <span class="text-[10px] text-slate-400">Aset barang & document scanner</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.enable_sarpras" :true-value="1" :false-value="0" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                    <!-- 8. Persuratan -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.enable_persuratan ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-envelope-paper-fill"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">Persuratan Digital</span>
                                                <span class="text-[10px] text-slate-400">Tata naskah dinas & disposisi</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.enable_persuratan" :true-value="1" :false-value="0" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                    <!-- 9. CMS Landing Page -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.cms_landing_enabled ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-globe2"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">CMS Landing Page</span>
                                                <span class="text-[10px] text-slate-400">Website profil publik & berita</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.cms_landing_enabled" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                    <!-- 10. Modul Kejuruan SMK -->
                                    <label class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer" :class="form.enable_smk ? 'bg-white border-indigo-200 shadow-2xs' : 'bg-slate-100/60 border-slate-200 opacity-60'">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs">
                                                <i class="bi bi-gear-wide-connected"></i>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">Modul SMK / PKL</span>
                                                <span class="text-[10px] text-slate-400">Prakerin & uji kompetensi kejuruan</span>
                                            </div>
                                        </div>
                                        <input type="checkbox" v-model="form.enable_smk" :true-value="1" :false-value="0" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </label>

                                </div>
                            </div>

                            <!-- Reference Card -->
                            <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 text-[11px] text-indigo-900 space-y-2">
                                <div class="font-bold flex items-center gap-1.5 text-indigo-800">
                                    <i class="bi bi-lightbulb-fill text-amber-500 text-sm"></i>
                                    Rekomendasi Kapasitas Paket Platform:
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-[10px]">
                                    <div class="bg-white/80 p-2 rounded-xl border border-indigo-100">
                                        <strong class="block text-indigo-950">Basic</strong>
                                        <span>50 MB Storage</span><br>
                                        <span>100 Siswa / 10 Staf</span>
                                    </div>
                                    <div class="bg-white/80 p-2 rounded-xl border border-indigo-100">
                                        <strong class="block text-indigo-950">Pro</strong>
                                        <span>250 MB Storage</span><br>
                                        <span>500 Siswa / 50 Staf</span>
                                    </div>
                                    <div class="bg-white/80 p-2 rounded-xl border border-indigo-100">
                                        <strong class="block text-indigo-950">Premium SaaS</strong>
                                        <span>1024 MB (1 GB)</span><br>
                                        <span>1000 Siswa / 100 Staf</span>
                                    </div>
                                    <div class="bg-white/80 p-2 rounded-xl border border-indigo-100">
                                        <strong class="block text-indigo-950">Enterprise</strong>
                                        <span>5120 MB (5 GB)</span><br>
                                        <span>Unlimited Siswa & Staf</span>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Modal Footer Actions -->
                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-2.5">
                        <button 
                            type="button" 
                            @click="showModal = false" 
                            class="px-4 py-2 border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="isSaving" 
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/10 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                        >
                            <span v-if="isSaving" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <i v-else class="bi bi-check-circle-fill"></i>
                            {{ isSaving ? 'Menyimpan...' : (isEditMode ? 'Simpan Perubahan' : 'Daftarkan Sekolah') }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
        </Teleport>

    </AppLayout>
</template>
