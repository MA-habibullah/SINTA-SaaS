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
            pendingTenants: 0,
            activeTenants: 0,
            trialTenants: 0,
            suspendedTenants: 0,
            rejectedTenants: 0,
            syncedTenants: 0
        })
    },
    menusList: {
        type: Array,
        default: () => []
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

// Active Tab Filter (Semua, Menunggu Persetujuan, Aktif, Trial, Nonaktif, Ditolak)
const activeFilterTab = ref('all')

// Search & Filter State
const searchQuery = ref(props.filters?.search || '')
const filterStatus = ref(props.filters?.status || '')
const filterPaket = ref(props.filters?.paket || '')
const filterSinkronisasi = ref(props.filters?.sinkronisasi || '')

// Pagination State (AGENTS.md Standard)
const perPage = ref(15)
const currentPage = ref(1)

// Modal & Form State for Tenant CRUD
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

// ==========================================
// APPROVAL & REJECT MODAL STATE
// ==========================================
const showApprovalModal = ref(false)
const selectedTenantForApproval = ref(null)
const isApproving = ref(false)
const approvalTrialMonths = ref(3)
const approvalCustomDate = ref('')
const approvalSubType = ref('Free Trial 3 Bulan')
const approvalAllowedMenus = ref([])
const approvalModules = ref({
    enable_bk: 1,
    enable_tracer: 1,
    enable_ppdb: 1,
    enable_perpustakaan: 1,
    enable_keuangan: 1,
    enable_pdss: 1,
    enable_smk: 0,
    enable_sarpras: 1,
    enable_persuratan: 1,
    storage_limit_mb: 1024,
    max_siswa_limit: 1000
})

const showRejectModal = ref(false)
const selectedTenantForReject = ref(null)
const rejectionReason = ref('')
const isRejecting = ref(false)

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

// Computed: Grouped Menus for Interactive Approval Matrix
const categorizedMenus = computed(() => {
    const list = props.menusList || []
    const parents = list.filter(m => !m.parent_id)
    return parents.map(p => ({
        ...p,
        children: list.filter(m => m.parent_id === p.id)
    }))
})

// Computed: Filtered Tenants with Tab + Search Logic
const filteredTenants = computed(() => {
    return localTenants.value.filter(t => {
        // Tab Filter
        if (activeFilterTab.value === 'pending') {
            const isPend = ['pending', 'pending_approval', 'menunggu'].includes(String(t.status).toLowerCase())
            if (!isPend) return false
        } else if (activeFilterTab.value === 'active') {
            const isAct = ['active', 'aktif'].includes(String(t.status).toLowerCase())
            if (!isAct) return false
        } else if (activeFilterTab.value === 'trial') {
            const isTrial = t.is_trial_active || (t.trial_ends_at && new Date(t.trial_ends_at) > new Date())
            if (!isTrial) return false
        } else if (activeFilterTab.value === 'suspended') {
            const isSusp = ['suspended', 'inactive', 'nonaktif'].includes(String(t.status).toLowerCase())
            if (!isSusp) return false
        } else if (activeFilterTab.value === 'rejected') {
            const isRej = ['rejected', 'ditolak'].includes(String(t.status).toLowerCase())
            if (!isRej) return false
        }

        const q = searchQuery.value.toLowerCase().trim()
        const matchesSearch = !q || 
            (t.nama_sekolah && t.nama_sekolah.toLowerCase().includes(q)) ||
            (t.npsn && t.npsn.toLowerCase().includes(q)) ||
            (t.subdomain && t.subdomain.toLowerCase().includes(q)) ||
            (t.pic_nama && t.pic_nama.toLowerCase().includes(q)) ||
            (t.pic_email && t.pic_email.toLowerCase().includes(q)) ||
            ((t.domain || t.custom_domain) && (t.domain || t.custom_domain).toLowerCase().includes(q))

        const matchesStatus = !filterStatus.value || t.status === filterStatus.value
        const matchesPaket = !filterPaket.value || t.paket_aktif === filterPaket.value
        const matchesSync = !filterSinkronisasi.value || t.status_sinkronisasi === filterSinkronisasi.value

        return matchesSearch && matchesStatus && matchesPaket && matchesSync
    })
})

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

// Statistics Computed
const totalTenantsCount = computed(() => props.stats?.totalTenants || localTenants.value.length)
const pendingTenantsCount = computed(() => props.stats?.pendingTenants || localTenants.value.filter(t => ['pending', 'pending_approval', 'menunggu'].includes(String(t.status).toLowerCase())).length)
const activeTenantsCount = computed(() => props.stats?.activeTenants || localTenants.value.filter(t => ['active', 'aktif'].includes(String(t.status).toLowerCase())).length)
const trialTenantsCount = computed(() => props.stats?.trialTenants || localTenants.value.filter(t => t.trial_ends_at && new Date(t.trial_ends_at) > new Date()).length)
const suspendedTenantsCount = computed(() => props.stats?.suspendedTenants || localTenants.value.filter(t => ['suspended', 'inactive', 'nonaktif'].includes(String(t.status).toLowerCase())).length)
const rejectedTenantsCount = computed(() => props.stats?.rejectedTenants || localTenants.value.filter(t => ['rejected', 'ditolak'].includes(String(t.status).toLowerCase())).length)
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
    if (!paket) return 'bg-slate-100 text-slate-700 border-slate-200'
    if (paket.includes('Free Trial') || paket.includes('Trial')) return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    if (paket === 'Enterprise SaaS') return 'bg-indigo-50 text-indigo-700 border-indigo-200'
    if (paket === 'Premium SaaS') return 'bg-purple-50 text-purple-700 border-purple-200'
    if (paket === 'Pro') return 'bg-blue-50 text-blue-700 border-blue-200'
    return 'bg-slate-100 text-slate-700 border-slate-200'
}

const getSyncBadge = (sync) => {
    switch (sync) {
        case 'Tersinkronisasi':
            return { badge: 'bg-emerald-50 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' }
        case 'Menunggu':
            return { badge: 'bg-amber-50 text-amber-700 border-amber-200', dot: 'bg-amber-500' }
        default:
            return { badge: 'bg-rose-50 text-rose-700 border-rose-200', dot: 'bg-rose-500' }
    }
}

const getStatusBadge = (status) => {
    const s = String(status).toLowerCase()
    if (s === 'pending_approval' || s === 'pending' || s === 'menunggu') {
        return { badge: 'bg-amber-100 text-amber-800 border-amber-300 font-black animate-pulse', dot: 'bg-amber-500', label: 'Menunggu Approval' }
    }
    if (s === 'active' || s === 'aktif') {
        return { badge: 'bg-emerald-50 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500', label: 'Active' }
    }
    if (s === 'rejected' || s === 'ditolak') {
        return { badge: 'bg-rose-100 text-rose-800 border-rose-300', dot: 'bg-rose-600', label: 'Ditolak' }
    }
    if (s === 'suspended') {
        return { badge: 'bg-amber-50 text-amber-700 border-amber-200', dot: 'bg-amber-500', label: 'Suspended' }
    }
    return { badge: 'bg-slate-100 text-slate-600 border-slate-200', dot: 'bg-slate-400', label: 'Inactive' }
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
        nama_sekolah: tenant.nama_sekolah || '',
        npsn: tenant.npsn || '',
        subdomain: tenant.subdomain || '',
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

// Submit Form CRUD
const submitForm = async () => {
    isSaving.value = true
    errors.value = {}

    try {
        const response = await axios.post('/super-admin/tenants/simpan', form.value)
        if (response.data && response.data.success) {
            showModal.value = false
            notify('success', 'Berhasil Disimpan', response.data.message || 'Data sekolah berhasil disimpan.')
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

// ==========================================
// APPROVAL & REJECT HANDLERS
// ==========================================
const openApprovalModal = (tenant) => {
    selectedTenantForApproval.value = tenant
    approvalTrialMonths.value = tenant.trial_duration_months || 3
    approvalCustomDate.value = ''
    approvalSubType.value = `Free Trial ${approvalTrialMonths.value} Bulan`

    // Init allowed menus: use tenant's allowed menus if any, or default to all active menus
    if (tenant.allowed_menu_ids && tenant.allowed_menu_ids.length > 0) {
        approvalAllowedMenus.value = [...tenant.allowed_menu_ids]
    } else {
        approvalAllowedMenus.value = (props.menusList || []).map(m => m.id)
    }

    approvalModules.value = {
        enable_bk: tenant.enable_bk ?? 1,
        enable_tracer: tenant.enable_tracer ?? 1,
        enable_ppdb: tenant.enable_ppdb ?? 1,
        enable_perpustakaan: tenant.enable_perpustakaan ?? 1,
        enable_keuangan: tenant.enable_keuangan ?? 1,
        enable_pdss: tenant.enable_pdss ?? 1,
        enable_smk: tenant.enable_smk ?? (tenant.bentuk_pendidikan === 'SMK' ? 1 : 0),
        enable_sarpras: tenant.enable_sarpras ?? 1,
        enable_persuratan: tenant.enable_persuratan ?? 1,
        storage_limit_mb: tenant.storage_limit_mb || 1024,
        max_siswa_limit: tenant.max_siswa_limit || 1000
    }

    showApprovalModal.value = true
}

const selectAllMenus = () => {
    approvalAllowedMenus.value = (props.menusList || []).map(m => m.id)
}

const deselectAllMenus = () => {
    approvalAllowedMenus.value = []
}

const selectCoreMenus = () => {
    // Select essential academic, siswa, keuangan menus
    const list = props.menusList || []
    approvalAllowedMenus.value = list
        .filter(m => m.url?.includes('pengguna') || m.url?.includes('buku-induk') || m.url?.includes('akademik') || m.url?.includes('keuangan'))
        .map(m => m.id)
}

const submitApproval = async () => {
    if (!selectedTenantForApproval.value) return
    isApproving.value = true

    try {
        const payload = {
            trial_duration_months: approvalTrialMonths.value,
            custom_trial_ends_at: approvalCustomDate.value || null,
            subscription_type: approvalSubType.value || `Free Trial ${approvalTrialMonths.value} Bulan`,
            allowed_menus: approvalAllowedMenus.value,
            ...approvalModules.value
        }

        const res = await axios.post(`/super-admin/tenants/${selectedTenantForApproval.value.id}/approve`, payload)
        if (res.data.success) {
            showApprovalModal.value = false
            notify('success', 'Sekolah Disetujui!', res.data.message)
            fetchTenants()
        }
    } catch (err) {
        const msg = err.response?.data?.error || err.message || 'Gagal menyetujui sekolah.'
        showAlert('error', 'Approval Gagal', msg)
    } finally {
        isApproving.value = false
    }
}

const openRejectModal = (tenant) => {
    selectedTenantForReject.value = tenant
    rejectionReason.value = ''
    showApprovalModal.value = false
    showRejectModal.value = true
}

const submitReject = async () => {
    if (!selectedTenantForReject.value) return
    if (!rejectionReason.value.trim()) {
        alert('Mohon masukkan alasan penolakan.')
        return
    }

    isRejecting.value = true
    try {
        const res = await axios.post(`/super-admin/tenants/${selectedTenantForReject.value.id}/reject`, {
            rejection_reason: rejectionReason.value
        })
        if (res.data.success) {
            showRejectModal.value = false
            notify('info', 'Pendaftaran Ditolak', res.data.message)
            fetchTenants()
        }
    } catch (err) {
        const msg = err.response?.data?.error || err.message || 'Gagal menolak pendaftaran.'
        showAlert('error', 'Gagal', msg)
    } finally {
        isRejecting.value = false
    }
}

// Toggle Active/Inactive Status
const toggleActiveStatus = (tenant, targetStatus) => {
    const isDeactivating = targetStatus === 'inactive' || targetStatus === 'suspended'
    const actionText = targetStatus === 'active' ? 'mengaktifkan kembali' : (targetStatus === 'suspended' ? 'menangguhkan' : 'menonaktifkan')
    const confirmColor = targetStatus === 'active' ? '#10b981' : '#f59e0b'

    if (typeof window !== 'undefined' && window.Swal) {
        window.Swal.fire({
            title: `Apakah Anda yakin?`,
            html: `Anda akan <strong>${actionText}</strong> akses sekolah <strong>${tenant.nama_sekolah}</strong>.<br><br>` +
                  (isDeactivating ? `<span class="text-rose-600 font-semibold text-xs"><i class="bi bi-exclamation-triangle-fill me-1"></i> PENTING: Seluruh user sekolah ini tidak akan bisa login!</span>` : ''),
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

// Delete Tenant
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
                            <p class="text-xs text-slate-500 mb-0">Kelola pendaftaran mandiri sekolah baru, verifikasi persetujuan (approval), atur masa free trial, dan konfigurasi hak akses modul.</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <Link href="/super-admin/cms-promosi" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs flex items-center gap-2 transition">
                        <i class="bi bi-megaphone-fill text-blue-600"></i> Kelola CMS Promosi
                    </Link>
                    <button 
                        type="button" 
                        @click="openAddModal"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs flex items-center gap-2 transition shadow-md shadow-indigo-500/10 cursor-pointer"
                    >
                        <i class="bi bi-plus-lg"></i> Tambah Sekolah Manual
                    </button>
                </div>
            </div>

            <!-- Statistics Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- Card 1: Total Sekolah -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Sekolah</span>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-800 font-mono m-0">{{ totalTenantsCount }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shadow-inner">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                </div>

                <!-- Card 2: Menunggu Approval (Pending) -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 border border-amber-200/90 shadow-xs flex items-center justify-between cursor-pointer hover:border-amber-400 transition"
                     @click="activeFilterTab = 'pending'">
                    <div>
                        <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block mb-1">Menunggu Approval</span>
                        <h3 class="text-xl sm:text-2xl font-black text-amber-600 font-mono m-0">{{ pendingTenantsCount }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-inner">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>

                <!-- Card 3: Tenant Aktif -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tenant Aktif</span>
                        <h3 class="text-xl sm:text-2xl font-black text-emerald-600 font-mono m-0">{{ activeTenantsCount }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shadow-inner">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>

                <!-- Card 4: Free Trial Aktif -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between cursor-pointer hover:border-blue-400 transition"
                     @click="activeFilterTab = 'trial'">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Free Trial Aktif</span>
                        <h3 class="text-xl sm:text-2xl font-black text-blue-600 font-mono m-0">{{ trialTenantsCount }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shadow-inner">
                        <i class="bi bi-gift-fill text-yellow-500"></i>
                    </div>
                </div>

                <!-- Card 5: Nonaktif / Suspended -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Nonaktif / Suspended</span>
                        <h3 class="text-xl sm:text-2xl font-black text-rose-600 font-mono m-0">{{ suspendedTenantsCount }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg shadow-inner">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
            </div>

            <!-- Horizontal NavTabs Filter Bar (Standar Baku SINTA) -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px]" 
                            onclick="document.getElementById('tenantFilterNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                            title="Geser ke Kiri">
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="tenantFilterNavTabs">
                            <li class="nav-item">
                                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeFilterTab === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeFilterTab = 'all'; currentPage = 1">
                                    <i class="bi bi-grid-fill"></i> Semua Sekolah ({{ totalTenantsCount }})
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeFilterTab === 'pending' ? 'bg-amber-600 text-white shadow-xs' : 'text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200'" 
                                        @click="activeFilterTab = 'pending'; currentPage = 1">
                                    <i class="bi bi-clock-history text-sm"></i>
                                    <span>Menunggu Approval</span>
                                    <span v-if="pendingTenantsCount > 0" class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                          :class="activeFilterTab === 'pending' ? 'bg-white text-amber-700' : 'bg-amber-600 text-white'">
                                        {{ pendingTenantsCount }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeFilterTab === 'active' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeFilterTab = 'active'; currentPage = 1">
                                    <i class="bi bi-check-circle-fill"></i> Sekolah Aktif ({{ activeTenantsCount }})
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeFilterTab === 'trial' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeFilterTab = 'trial'; currentPage = 1">
                                    <i class="bi bi-gift-fill text-yellow-500"></i> Free Trial Aktif ({{ trialTenantsCount }})
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeFilterTab === 'suspended' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeFilterTab = 'suspended'; currentPage = 1">
                                    <i class="bi bi-slash-circle"></i> Nonaktif / Suspended ({{ suspendedTenantsCount }})
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeFilterTab === 'rejected' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeFilterTab = 'rejected'; currentPage = 1">
                                    <i class="bi bi-x-circle"></i> Ditolak ({{ rejectedTenantsCount }})
                                </button>
                            </li>
                        </ul>
                    </div>

                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px]" 
                            onclick="document.getElementById('tenantFilterNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                            title="Geser ke Kanan">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Main Content: Unified Data Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                
                <!-- Filter Bar Atas -->
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
                                <option value="Free Trial 3 Bulan">Free Trial 3 Bulan</option>
                                <option value="Free Trial 1 Bulan">Free Trial 1 Bulan</option>
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
                                <option value="pending_approval">Pending Approval</option>
                                <option value="active">Active (Aktif)</option>
                                <option value="inactive">Inactive (Nonaktif)</option>
                                <option value="suspended">Suspended</option>
                                <option value="rejected">Rejected (Ditolak)</option>
                            </select>
                        </div>

                        <!-- Search Box Input -->
                        <div class="w-56 sm:w-72 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Cari Nama / NPSN / PIC</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    v-model="searchQuery" 
                                    @input="currentPage = 1"
                                    placeholder="Ketik kata kunci pencarian..." 
                                    class="w-full h-9 pl-9 pr-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                                >
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>

                        <!-- Tombol Reset Filter -->
                        <div class="shrink-0">
                            <button 
                                type="button" 
                                @click="searchQuery = ''; filterStatus = ''; filterPaket = ''; filterSinkronisasi = ''; activeFilterTab = 'all'; currentPage = 1;"
                                class="h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-600 transition flex items-center gap-1.5 shadow-2xs"
                                title="Reset Seluruh Filter"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reset</span>
                            </button>
                        </div>

                    </form>
                </div>

                <!-- Table Container -->
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3 px-4 text-center w-12">#</th>
                                <th class="py-3 px-4 min-w-[240px]">Sekolah & Kontak PIC</th>
                                <th class="py-3 px-4 min-w-[180px]">Subdomain / URL</th>
                                <th class="py-3 px-3.5 min-w-[140px]">Paket & Trial</th>
                                <th class="py-3 px-3.5 min-w-[120px]">Status Akses</th>
                                <th class="py-3 px-4 text-center sticky right-0 bg-slate-50/80 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] w-56">Aksi & Approval</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 text-xs">
                            <tr v-if="paginatedTenants.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <i class="bi bi-inbox text-4xl block mb-2 opacity-50"></i>
                                    Tidak ada data sekolah yang sesuai kriteria filter.
                                </td>
                            </tr>
                            <tr 
                                v-for="(tenant, index) in paginatedTenants" 
                                :key="tenant.id"
                                :class="['hover:bg-blue-50/30 transition group',
                                         tenant.is_pending_approval ? 'bg-amber-50/40' : '']"
                            >
                                <!-- Nomor Baris -->
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono text-[11px]">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </td>

                                <!-- Identitas Sekolah & PIC -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 mt-0.5 shadow-2xs"
                                             :class="tenant.is_pending_approval ? 'bg-amber-500 text-white' : 'bg-gradient-to-tr from-indigo-600 to-blue-600 text-white'">
                                            {{ getInitials(tenant.nama_sekolah) }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-slate-900 text-xs flex items-center gap-1.5">
                                                <span>{{ tenant.nama_sekolah }}</span>
                                                <span v-if="tenant.bentuk_pendidikan" class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                                    {{ tenant.bentuk_pendidikan }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-2">
                                                <span>NPSN: <strong class="font-mono text-slate-700">{{ tenant.npsn }}</strong></span>
                                                <span v-if="tenant.status_sekolah" class="text-slate-400">&bull; {{ tenant.status_sekolah }}</span>
                                            </div>
                                            <!-- PIC Info -->
                                            <div v-if="tenant.pic_nama" class="mt-1 text-[11px] text-slate-600 flex flex-wrap items-center gap-2 bg-slate-50 px-2 py-0.5 rounded-lg border border-slate-100 w-fit">
                                                <span class="font-bold text-slate-700"><i class="bi bi-person-fill text-blue-600"></i> {{ tenant.pic_nama }}</span>
                                                <span v-if="tenant.pic_telepon" class="text-slate-500 font-mono">({{ tenant.pic_telepon }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Subdomain / URL -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-0.5">
                                        <div class="font-mono text-xs font-bold text-blue-600">
                                            {{ tenant.subdomain }}.sinta.id
                                        </div>
                                        <div v-if="tenant.domain || tenant.custom_domain" class="text-[10px] text-slate-400 font-mono flex items-center gap-1">
                                            <i class="bi bi-globe text-slate-400"></i>
                                            <span>{{ tenant.domain || tenant.custom_domain }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Paket & Masa Trial -->
                                <td class="py-3.5 px-3.5 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-xl text-[11px] font-bold border shadow-2xs" :class="getPaketBadge(tenant.paket_aktif)">
                                            <i class="bi bi-gift-fill text-yellow-500" v-if="tenant.is_trial_active || tenant.paket_aktif?.includes('Trial')"></i>
                                            <i class="bi bi-gem text-xs" v-else></i>
                                            {{ tenant.paket_aktif }}
                                        </span>
                                        <!-- Trial Countdown Info -->
                                        <div v-if="tenant.trial_ends_at" class="text-[10px] font-semibold text-slate-500">
                                            <span v-if="tenant.remaining_trial_days > 0" class="text-emerald-600 font-bold">
                                                Tersisa {{ tenant.remaining_trial_days }} hari
                                            </span>
                                            <span v-else class="text-rose-600 font-bold">
                                                Trial Berakhir
                                            </span>
                                            <span class="text-slate-400 block font-normal">s.d. {{ tenant.trial_ends_at_human }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Akses -->
                                <td class="py-3.5 px-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold border shadow-2xs" :class="getStatusBadge(tenant.status).badge">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(tenant.status).dot"></span>
                                        {{ getStatusBadge(tenant.status).label }}
                                    </span>
                                </td>

                                <!-- Aksi & Approval Button (Sticky Right) -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap sticky right-0 bg-white group-hover:bg-blue-50/40 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] transition">
                                    <div class="flex items-center justify-center gap-1.5">
                                        
                                        <!-- Approval Button for Pending Tenants -->
                                        <button 
                                            v-if="tenant.is_pending_approval"
                                            type="button" 
                                            @click="openApprovalModal(tenant)" 
                                            class="px-3 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20 cursor-pointer animate-pulse"
                                            title="Review Pendaftaran & Setujui Hak Akses"
                                        >
                                            <i class="bi bi-shield-check"></i>
                                            <span>Review & Setujui</span>
                                        </button>

                                        <!-- Regular Action Buttons -->
                                        <template v-else>
                                            <!-- Edit Button -->
                                            <button 
                                                type="button" 
                                                @click="openEditModal(tenant)" 
                                                class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/80 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer"
                                                title="Edit Profil Sekolah"
                                            >
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>

                                            <!-- Atur Menu / Trial Preset Button -->
                                            <button 
                                                type="button" 
                                                @click="openApprovalModal(tenant)" 
                                                class="px-2 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                                title="Atur Hak Akses Menu & Perpanjang Trial"
                                            >
                                                <i class="bi bi-sliders"></i> Menu
                                            </button>

                                            <!-- Toggle Status Button -->
                                            <button 
                                                v-if="tenant.status === 'active' && tenant.id !== '00000000-0000-0000-0000-000000000000'"
                                                type="button" 
                                                @click="toggleActiveStatus(tenant, 'inactive')" 
                                                class="px-2 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200/80 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                                title="Nonaktifkan Sekolah"
                                            >
                                                <i class="bi bi-shield-slash"></i>
                                            </button>
                                            <button 
                                                v-else-if="tenant.id !== '00000000-0000-0000-0000-000000000000' && !tenant.is_rejected"
                                                type="button" 
                                                @click="toggleActiveStatus(tenant, 'active')" 
                                                class="px-2 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                                title="Aktifkan Sekolah"
                                            >
                                                <i class="bi bi-shield-check"></i>
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
                                        </template>

                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination -->
                <div v-if="filteredTenants.length > 0" 
                     class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
                    
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
                        </select>
                        <span>baris per halaman</span>
                        <span class="text-slate-300 hidden sm:inline">|</span>
                        <span>
                            Menampilkan <strong class="text-slate-800">{{ (currentPage - 1) * perPage + 1 }}</strong> s.d. <strong class="text-slate-800">{{ Math.min(currentPage * perPage, filteredTenants.length) }}</strong> dari <strong class="text-slate-800">{{ filteredTenants.length }}</strong> sekolah
                        </span>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button 
                            type="button" 
                            :disabled="currentPage === 1"
                            @click="goToPage(currentPage - 1)"
                            class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-40 transition"
                        >
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="px-3 py-1.5 bg-blue-600 text-white rounded-xl text-xs font-bold">{{ currentPage }} / {{ lastPage }}</span>
                        <button 
                            type="button" 
                            :disabled="currentPage >= lastPage"
                            @click="goToPage(currentPage + 1)"
                            class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-40 transition"
                        >
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>

                </div>

            </div>

        </div>

        <!-- ========================================== -->
        <!-- MODAL 1: REVIEW & APPROVAL SEKOLAH (TELEPORT) -->
        <!-- ========================================== -->
        <Teleport to="body">
            <div v-if="showApprovalModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="relative bg-white rounded-3xl max-w-4xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 text-slate-800 max-h-[90vh] overflow-y-auto">
                    
                    <!-- Header -->
                    <div class="flex items-start justify-between pb-4 border-b border-slate-100 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl font-bold shadow-xs">
                                <i class="bi bi-patch-check-fill"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-slate-900">
                                    {{ selectedTenantForApproval?.is_pending_approval ? 'Review & Setujui Pendaftaran Sekolah' : 'Konfigurasi Hak Akses & Masa Trial' }}
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Sekolah: <strong class="text-slate-800">{{ selectedTenantForApproval?.nama_sekolah }}</strong> (NPSN: {{ selectedTenantForApproval?.npsn }})
                                </p>
                            </div>
                        </div>
                        <button @click="showApprovalModal = false" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold">
                            &times;
                        </button>
                    </div>

                    <!-- School & PIC Profile Snippet -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 mb-6 text-xs">
                        <div>
                            <span class="text-slate-400 font-semibold block text-[11px]">Identitas Instansi</span>
                            <div class="font-bold text-slate-800 mt-0.5">{{ selectedTenantForApproval?.nama_sekolah }}</div>
                            <div class="text-slate-600 font-mono text-[11px] mt-0.5">Subdomain: {{ selectedTenantForApproval?.subdomain }}.sinta.id</div>
                            <div class="text-slate-500 mt-0.5">{{ selectedTenantForApproval?.bentuk_pendidikan }} - {{ selectedTenantForApproval?.status_sekolah }}</div>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block text-[11px]">Kontak Penanggung Jawab (PIC)</span>
                            <div class="font-bold text-slate-800 mt-0.5">{{ selectedTenantForApproval?.pic_nama || 'Belum diisi' }} ({{ selectedTenantForApproval?.pic_jabatan || '-' }})</div>
                            <div class="text-slate-600 mt-0.5"><i class="bi bi-whatsapp text-emerald-600"></i> {{ selectedTenantForApproval?.pic_telepon || '-' }}</div>
                            <div class="text-slate-600 mt-0.5"><i class="bi bi-envelope"></i> {{ selectedTenantForApproval?.pic_email || '-' }}</div>
                        </div>
                    </div>

                    <form @submit.prevent="submitApproval" class="space-y-6">
                        
                        <!-- 1. Pilihan Durasi Free Trial -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2">
                                1. Tentukan Masa Uji Coba Gratis (Free Trial Duration)
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <button type="button" 
                                        @click="approvalTrialMonths = 1; approvalSubType = 'Free Trial 1 Bulan'"
                                        :class="['p-3 rounded-2xl border-2 text-center transition font-bold text-xs',
                                                 approvalTrialMonths === 1 ? 'border-blue-600 bg-blue-50 text-blue-900 shadow-xs' : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white']">
                                    <span class="block text-base font-black">1 Bulan</span>
                                    <span class="text-[10px] font-normal text-slate-500">Uji Coba Singkat</span>
                                </button>

                                <button type="button" 
                                        @click="approvalTrialMonths = 3; approvalSubType = 'Free Trial 3 Bulan'"
                                        :class="['p-3 rounded-2xl border-2 text-center transition font-bold text-xs relative overflow-hidden',
                                                 approvalTrialMonths === 3 ? 'border-emerald-600 bg-emerald-50 text-emerald-900 shadow-xs' : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white']">
                                    <span class="absolute top-0 right-0 bg-emerald-600 text-white text-[9px] px-1.5 py-0.2 font-black rounded-bl">Populer</span>
                                    <span class="block text-base font-black">3 Bulan</span>
                                    <span class="text-[10px] font-normal text-slate-500">1 Semester Penuh</span>
                                </button>

                                <button type="button" 
                                        @click="approvalTrialMonths = 6; approvalSubType = 'Free Trial 6 Bulan'"
                                        :class="['p-3 rounded-2xl border-2 text-center transition font-bold text-xs',
                                                 approvalTrialMonths === 6 ? 'border-blue-600 bg-blue-50 text-blue-900 shadow-xs' : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white']">
                                    <span class="block text-base font-black">6 Bulan</span>
                                    <span class="text-[10px] font-normal text-slate-500">Masa Evaluasi</span>
                                </button>

                                <button type="button" 
                                        @click="approvalTrialMonths = 12; approvalSubType = 'Langganan 1 Tahun'"
                                        :class="['p-3 rounded-2xl border-2 text-center transition font-bold text-xs',
                                                 approvalTrialMonths === 12 ? 'border-indigo-600 bg-indigo-50 text-indigo-900 shadow-xs' : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white']">
                                    <span class="block text-base font-black">1 Tahun</span>
                                    <span class="text-[10px] font-normal text-slate-500">Langganan Penuh</span>
                                </button>
                            </div>
                        </div>

                        <!-- 2. Matrix Hak Akses Menu / Modul yang Diizinkan -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-800">
                                        2. Atur Hak Akses Menu & Modul yang Diizinkan
                                    </label>
                                    <span class="text-[11px] text-slate-500">Pilih modul apa saja yang boleh diakses oleh sekolah ini.</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" @click="selectAllMenus" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 font-bold text-slate-700 transition">
                                        Pilih Semua
                                    </button>
                                    <button type="button" @click="selectCoreMenus" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold transition">
                                        Paket Pokok
                                    </button>
                                    <button type="button" @click="deselectAllMenus" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 font-bold text-slate-500 transition">
                                        Kosongkan
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-72 overflow-y-auto p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <div v-for="cat in categorizedMenus" :key="cat.id" class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2">
                                    <!-- Parent Menu -->
                                    <label class="flex items-center gap-2 cursor-pointer font-bold text-xs text-slate-900">
                                        <input type="checkbox" v-model="approvalAllowedMenus" :value="cat.id" class="rounded text-blue-600 focus:ring-blue-500" />
                                        <i :class="['bi', cat.icon || 'bi-folder']" class="text-blue-600"></i>
                                        <span>{{ cat.nama_menu }}</span>
                                    </label>

                                    <!-- Children Menus -->
                                    <div v-if="cat.children && cat.children.length > 0" class="pl-6 space-y-1.5 border-l-2 border-slate-100 ml-2">
                                        <label v-for="child in cat.children" :key="child.id" class="flex items-center gap-2 cursor-pointer text-[11px] font-medium text-slate-700 hover:text-blue-600">
                                            <input type="checkbox" v-model="approvalAllowedMenus" :value="child.id" class="rounded text-blue-600 focus:ring-blue-500" />
                                            <span>{{ child.nama_menu }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-5 border-t border-slate-100">
                            <div>
                                <button 
                                    v-if="selectedTenantForApproval?.is_pending_approval"
                                    type="button" 
                                    @click="openRejectModal(selectedTenantForApproval)" 
                                    class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs transition flex items-center gap-1.5"
                                >
                                    <i class="bi bi-x-octagon-fill text-rose-600"></i>
                                    <span>Tolak Pendaftaran</span>
                                </button>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="button" @click="showApprovalModal = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
                                    Batal
                                </button>
                                <button type="submit" :disabled="isApproving"
                                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-xs shadow-lg shadow-emerald-600/30 disabled:opacity-50 flex items-center gap-2">
                                    <span v-if="isApproving">Memproses...</span>
                                    <span v-else>{{ selectedTenantForApproval?.is_pending_approval ? 'Setujui & Aktifkan Sekolah' : 'Simpan Perubahan Akses' }}</span>
                                    <i class="bi bi-check-circle-fill font-bold"></i>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ========================================== -->
        <!-- MODAL 2: REJECT PENDAFTARAN (TELEPORT)     -->
        <!-- ========================================== -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="relative bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 text-slate-800">
                    <div class="text-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-2xl mx-auto mb-2">
                            <i class="bi bi-x-octagon-fill"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-900">Tolak Pendaftaran Sekolah</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Anda akan menolak permohonan dari <strong>{{ selectedTenantForReject?.nama_sekolah }}</strong>.
                        </p>
                    </div>

                    <form @submit.prevent="submitReject" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                            <textarea v-model="rejectionReason" rows="4" required placeholder="Contoh: NPSN tidak valid / Mohon lengkapi surat pengantar resmi..."
                                      class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-rose-500 focus:outline-none focus:bg-white"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                            <button type="button" @click="showRejectModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="isRejecting" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-600/20 disabled:opacity-50">
                                {{ isRejecting ? 'Menolak...' : 'Konfirmasi Tolak' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ========================================== -->
        <!-- MODAL 3: ADD/EDIT TENANT CRUD (TELEPORT)   -->
        <!-- ========================================== -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="relative bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 text-slate-800">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <h3 class="text-lg font-black text-slate-900">{{ isEditMode ? 'Edit Profil Sekolah' : 'Tambah Sekolah Baru' }}</h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold">
                            &times;
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Sekolah <span class="text-red-500">*</span></label>
                                <input v-model="form.nama_sekolah" type="text" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-600 focus:outline-none focus:bg-white" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">NPSN <span class="text-red-500">*</span></label>
                                <input v-model="form.npsn" type="text" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-600 focus:outline-none focus:bg-white" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Subdomain <span class="text-red-500">*</span></label>
                                <input v-model="form.subdomain" @input="onSubdomainInput" type="text" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-600 focus:outline-none focus:bg-white" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Paket Langganan</label>
                                <select v-model="form.paket_aktif" @change="applyPackageDefaults" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-600 focus:outline-none focus:bg-white">
                                    <option value="Basic">Basic Edition</option>
                                    <option value="Pro">Pro Edition</option>
                                    <option value="Premium SaaS">Premium SaaS</option>
                                    <option value="Enterprise SaaS">Enterprise SaaS</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Status Akses</label>
                                <select v-model="form.status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-600 focus:outline-none focus:bg-white">
                                    <option value="active">Active (Aktif)</option>
                                    <option value="inactive">Inactive (Nonaktif)</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSaving" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 disabled:opacity-50">
                                {{ isSaving ? 'Menyimpan...' : (isEditMode ? 'Simpan Perubahan' : 'Daftarkan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>
