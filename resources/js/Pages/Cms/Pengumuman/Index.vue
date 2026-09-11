<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'

// ─────────────────────────────────────────────
// PROPS
// ─────────────────────────────────────────────
const props = defineProps({
    pengumumanList: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 15, from: 0, to: 0 })
    },
    kategoriList: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, aktif: 0, publik: 0, khusus: 0 })
    },
    tenants: {
        type: Array,
        default: () => []
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    },
    filters: {
        type: Object,
        default: () => ({ search: '', kategori_id: '', visibilitas: '', status: '', tenant_id: '', per_page: 15 })
    }
})

// ─────────────────────────────────────────────
// STATE FILTER & SEARCH
// ─────────────────────────────────────────────
const search = ref(props.filters.search || '')
const kategoriId = ref(props.filters.kategori_id || '')
const visibilitas = ref(props.filters.visibilitas || '')
const status = ref(props.filters.status !== undefined ? String(props.filters.status) : '')
const tenantId = ref(props.filters.tenant_id || '')
const perPage = ref(props.filters.per_page || 15)

let searchTimeout = null
function handleSearch() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 400)
}

function applyFilters() {
    router.get('/informasi/pengumuman', {
        search: search.value || undefined,
        kategori_id: kategoriId.value || undefined,
        visibilitas: visibilitas.value || undefined,
        status: status.value !== '' ? status.value : undefined,
        tenant_id: tenantId.value || undefined,
        per_page: perPage.value !== 15 ? perPage.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function resetFilters() {
    search.value = ''
    kategoriId.value = ''
    visibilitas.value = ''
    status.value = ''
    tenantId.value = ''
    perPage.value = 15
    applyFilters()
}

function changePage(url) {
    if (!url) return
    router.visit(url, { preserveState: true, preserveScroll: true })
}

// ─────────────────────────────────────────────
// MODAL STATE — TAMBAH / EDIT PENGUMUMAN
// ─────────────────────────────────────────────
const showModalForm = ref(false)
const isEditMode = ref(false)
const selectedItem = ref(null)
const fileInputRef = ref(null)
const selectedFile = ref(null)
const filePreviewUrl = ref(null)
const existingFile = ref(null)
const removeExistingFile = ref(false)

const form = useForm({
    id: '',
    tenant_id: '',
    judul: '',
    deskripsi: '',
    kategori_id: '',
    visibilitas: 'public',
    target_roles: [],
    lampiran: null,
    delete_lampiran: '',
    is_active: true,
})

const availableRoles = [
    { value: 'guru', label: 'Dewan Guru' },
    { value: 'siswa', label: 'Siswa / Peserta Didik' },
    { value: 'admin_sekolah', label: 'Tenaga Kependidikan / Admin' },
    { value: 'kepala_sekolah', label: 'Kepala Sekolah' },
    { value: 'keuangan', label: 'Staf Keuangan' },
    { value: 'bk', label: 'Guru BK' },
    { value: 'sarpras', label: 'Staf Sarpras' },
    { value: 'perpustakaan', label: 'Petugas Perpustakaan' },
]

function openCreateModal() {
    isEditMode.value = false
    selectedItem.value = null
    selectedFile.value = null
    filePreviewUrl.value = null
    existingFile.value = null
    removeExistingFile.value = false
    form.reset()
    form.clearErrors()
    form.visibilitas = 'public'
    form.is_active = true
    form.target_roles = []
    form.lampiran = null
    form.delete_lampiran = ''
    form.tenant_id = tenantId.value || (props.tenants.length > 0 ? props.tenants[0].id : '')
    showModalForm.value = true
}

function openEditModal(item) {
    isEditMode.value = true
    selectedItem.value = item
    selectedFile.value = null
    filePreviewUrl.value = null
    removeExistingFile.value = false
    form.clearErrors()
    form.id = item.id
    form.tenant_id = item.tenant_id || ''
    form.judul = item.judul || ''
    form.deskripsi = item.deskripsi || ''
    form.kategori_id = item.kategori_id || ''
    form.visibilitas = item.visibilitas || 'public'
    form.is_active = Boolean(item.is_active)
    form.lampiran = null
    form.delete_lampiran = ''

    if (item.lampiran_url) {
        existingFile.value = {
            url: item.lampiran_url,
            name: item.lampiran_nama || 'File Lampiran',
            size: item.lampiran_ukuran,
            type: item.lampiran_tipe,
        }
    } else {
        existingFile.value = null
    }

    let roles = []
    if (item.target_roles) {
        try {
            roles = Array.isArray(item.target_roles) ? item.target_roles : JSON.parse(item.target_roles)
        } catch (e) {
            roles = []
        }
    }
    form.target_roles = roles
    showModalForm.value = true
}

function handleFileChange(event) {
    const file = event.target.files?.[0]
    if (file) {
        selectedFile.value = file
        form.lampiran = file
        removeExistingFile.value = false
        form.delete_lampiran = ''
        if (file.type.startsWith('image/')) {
            filePreviewUrl.value = URL.createObjectURL(file)
        } else {
            filePreviewUrl.value = null
        }
    }
}

function removeSelectedFile() {
    selectedFile.value = null
    form.lampiran = null
    filePreviewUrl.value = null
    if (fileInputRef.value) {
        fileInputRef.value.value = ''
    }
}

function markDeleteExistingFile() {
    removeExistingFile.value = true
    form.delete_lampiran = '1'
    existingFile.value = null
}

function toggleRole(roleVal) {
    const idx = form.target_roles.indexOf(roleVal)
    if (idx > -1) {
        form.target_roles.splice(idx, 1)
    } else {
        form.target_roles.push(roleVal)
    }
}

function submitForm() {
    if (isEditMode.value) {
        form.post(`/cms/pengumuman/${form.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showModalForm.value = false
                form.reset()
            },
        })
    } else {
        form.post('/cms/pengumuman', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showModalForm.value = false
                form.reset()
            },
        })
    }
}

// ─────────────────────────────────────────────
// MODAL STATE — BACA / DETAIL PENGUMUMAN
// ─────────────────────────────────────────────
const showDetailModal = ref(false)
const detailData = ref(null)

function openDetailModal(item) {
    detailData.value = item
    showDetailModal.value = true
}

// ─────────────────────────────────────────────
// MODAL STATE — KELOLA KATEGORI
// ─────────────────────────────────────────────
const showKategoriModal = ref(false)
const kategoriForm = useForm({
    nama_kategori: '',
    tenant_id: '',
})

function openKategoriModal() {
    kategoriForm.reset()
    kategoriForm.clearErrors()
    kategoriForm.tenant_id = tenantId.value || (props.tenants.length > 0 ? props.tenants[0].id : '')
    showKategoriModal.value = true
}

function submitKategori() {
    kategoriForm.post('/cms/kategori-pengumuman', {
        preserveScroll: true,
        onSuccess: () => {
            kategoriForm.reset()
        }
    })
}

function deleteKategori(id, nama) {
    if (confirm(`Yakin ingin menghapus kategori "${nama}"?`)) {
        router.delete(`/cms/kategori-pengumuman/${id}`, {
            preserveScroll: true,
        })
    }
}

// ─────────────────────────────────────────────
// MODAL STATE — KONFIRMASI HAPUS PENGUMUMAN
// ─────────────────────────────────────────────
const showDeleteModal = ref(false)
const itemToDelete = ref(null)

function confirmDelete(item) {
    itemToDelete.value = item
    showDeleteModal.value = true
}

function executeDelete() {
    if (!itemToDelete.value) return
    router.delete(`/cms/pengumuman/${itemToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            itemToDelete.value = null
        }
    })
}

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function isImageFile(url) {
    if (!url) return false
    return /\.(jpeg|jpg|png|webp|svg|gif)$/i.test(url)
}

function getFileIcon(url, nama) {
    const filename = nama || url || ''
    if (/\.(pdf)$/i.test(filename)) return 'bi-file-earmark-pdf text-red-500'
    if (/\.(doc|docx)$/i.test(filename)) return 'bi-file-earmark-word text-blue-500'
    if (/\.(xls|xlsx)$/i.test(filename)) return 'bi-file-earmark-excel text-emerald-500'
    if (/\.(ppt|pptx)$/i.test(filename)) return 'bi-file-earmark-ppt text-amber-500'
    if (/\.(zip|rar|7z)$/i.test(filename)) return 'bi-file-earmark-zip text-purple-500'
    if (/\.(jpg|jpeg|png|webp|svg)$/i.test(filename)) return 'bi-file-earmark-image text-indigo-500'
    return 'bi-file-earmark-arrow-down text-slate-500'
}

function getVisibilitasBadge(val) {
    switch (val) {
        case 'public':
            return { label: 'Publik (Semua)', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' }
        case 'guru':
            return { label: 'Khusus Guru', class: 'bg-blue-50 text-blue-700 border-blue-200' }
        case 'siswa':
            return { label: 'Khusus Siswa', class: 'bg-amber-50 text-amber-700 border-amber-200' }
        case 'orang_tua':
            return { label: 'Orang Tua / Wali', class: 'bg-purple-50 text-purple-700 border-purple-200' }
        default:
            return { label: val || 'Publik', class: 'bg-slate-50 text-slate-700 border-slate-200' }
    }
}

const getSelectedTenantName = () => {
    if (!tenantId.value) {
        return 'Semua Sekolah Terdaftar (Super Admin)'
    }
    const found = (props.tenants || []).find(t => t.id === tenantId.value)
    return found ? found.nama_sekolah : 'Sekolah Terpilih'
}
</script>

<template>
    <AppLayout title="Pengumuman Sekolah">
        <div class="space-y-6">
            <!-- ── 1. HEADER HALAMAN ── -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center text-base shadow-xs">
                            <i class="bi bi-megaphone-fill text-lg"></i>
                        </span>
                        Pengumuman Sekolah
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Publikasi warta resmi, berkas edaran dinas, dan pengumuman kegiatan sekolah untuk seluruh warga sekolah.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        class="px-3.5 py-2.5 text-xs font-semibold rounded-xl border border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 shadow-2xs transition flex items-center gap-2"
                        @click="openKategoriModal"
                    >
                        <i class="bi bi-tags text-slate-500"></i> Kelola Kategori
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2"
                        @click="openCreateModal"
                    >
                        <i class="bi bi-plus-lg"></i> Tambah Pengumuman
                    </button>
                </div>
            </div>

            <!-- ── 2. FILTER SEKOLAH BANNER (STANDAR DESAIN SUPER ADMIN) ── -->
            <div v-if="isSuperAdmin" class="p-4 sm:px-5 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2.5">
                    <i class="bi bi-building text-blue-600 text-lg"></i>
                    <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
                        <i class="bi bi-funnel-fill me-1"></i> Aktif
                    </span>

                    <!-- Dropdown Filter Sekolah (Khusus Super Admin) -->
                    <div class="my-1 md:my-0">
                        <select
                            v-model="tenantId"
                            @change="applyFilters"
                            class="h-9 px-3 bg-white border border-blue-200 rounded-xl text-xs font-semibold text-slate-800 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[240px]"
                        >
                            <option value="">-- Semua Sekolah (Global) --</option>
                            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                        </select>
                    </div>
                </div>

                <!-- Informational Text -->
                <div class="text-xs text-slate-500 font-medium">
                    Menampilkan data milik: 
                    <strong class="text-blue-700 font-bold ml-1">
                        {{ getSelectedTenantName() }}
                    </strong>
                </div>
            </div>

            <!-- ── 3. KARTU METRIK STATISTIK ── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-megaphone"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Total Pengumuman</div>
                        <div class="text-xl font-bold text-slate-800">{{ stats.total }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Aktif Tayang</div>
                        <div class="text-xl font-bold text-emerald-600">{{ stats.aktif }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-globe2"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Visibilitas Publik</div>
                        <div class="text-xl font-bold text-slate-800">{{ stats.publik }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Khusus Internal</div>
                        <div class="text-xl font-bold text-slate-800">{{ stats.khusus }}</div>
                    </div>
                </div>
            </div>

            <!-- ── 4. BOX UTAMA: FILTER BAR + TABEL + FOOTER PAGINATION ── -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                <!-- 4A. FILTER BAR ATAS -->
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5 grow">
                        <!-- Search Box -->
                        <div class="relative min-w-[220px] grow md:grow-0">
                            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari judul atau isi pengumuman..."
                                class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                @input="handleSearch"
                            />
                        </div>

                        <!-- Kategori Dropdown -->
                        <select
                            v-model="kategoriId"
                            class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition text-slate-700"
                            @change="applyFilters"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="kat in kategoriList" :key="kat.id" :value="kat.id">
                                {{ kat.nama_kategori }}
                            </option>
                        </select>

                        <!-- Visibilitas Dropdown -->
                        <select
                            v-model="visibilitas"
                            class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition text-slate-700"
                            @change="applyFilters"
                        >
                            <option value="">Semua Visibilitas</option>
                            <option value="public">Publik</option>
                            <option value="guru">Khusus Guru</option>
                            <option value="siswa">Khusus Siswa</option>
                            <option value="orang_tua">Orang Tua / Wali</option>
                        </select>

                        <!-- Status Dropdown -->
                        <select
                            v-model="status"
                            class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition text-slate-700"
                            @change="applyFilters"
                        >
                            <option value="">Semua Status</option>
                            <option value="1">Aktif Tayang</option>
                            <option value="0">Nonaktif</option>
                        </select>

                        <!-- Reset Filter -->
                        <button
                            v-if="search || kategoriId || visibilitas || status || tenantId"
                            type="button"
                            class="px-3 py-2 text-xs font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-xl transition flex items-center gap-1.5"
                            @click="resetFilters"
                        >
                            <i class="bi bi-x-circle"></i> Reset Filter
                        </button>
                    </div>

                    <div class="text-xs text-slate-500 shrink-0">
                        Menampilkan <span class="font-semibold text-slate-700">{{ pengumumanList.from || 0 }}-{{ pengumumanList.to || 0 }}</span> dari <span class="font-semibold text-slate-700">{{ pengumumanList.total || 0 }}</span> data
                    </div>
                </div>

                <!-- 3B. TABEL DATA PENGUMUMAN -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 border-collapse">
                        <thead class="bg-slate-50/80 text-slate-700 uppercase text-[11px] font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th v-if="isSuperAdmin" class="py-3 px-4 min-w-[170px]">Sekolah / Unit</th>
                                <th class="py-3 px-4 min-w-[260px]">Judul & Deskripsi Ringkas</th>
                                <th class="py-3 px-4 min-w-[140px]">Kategori</th>
                                <th class="py-3 px-4 min-w-[130px]">Visibilitas</th>
                                <th class="py-3 px-4 min-w-[120px]">Tanggal Terbit</th>
                                <th class="py-3 px-4 w-28 text-center">Status</th>
                                <th class="py-3 px-4 w-32 text-center sticky right-0 bg-slate-50/90 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.04)]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, idx) in pengumumanList.data"
                                :key="item.id"
                                class="hover:bg-slate-50/80 transition group"
                            >
                                <td class="py-3.5 px-4 text-center text-slate-400 font-medium">
                                    {{ (pengumumanList.current_page - 1) * pengumumanList.per_page + idx + 1 }}
                                </td>
                                <!-- Kolom Sekolah untuk Super Admin -->
                                <td v-if="isSuperAdmin" class="py-3.5 px-4">
                                    <div v-if="item.tenant" class="font-medium text-slate-800 text-xs flex items-center gap-1.5">
                                        <i class="bi bi-building text-blue-500 text-[11px]"></i>
                                        <span class="truncate max-w-[150px] font-semibold text-blue-950" :title="item.tenant.nama_sekolah">{{ item.tenant.nama_sekolah }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic text-[11px]">Pusat / Global</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-semibold text-slate-800 text-sm group-hover:text-blue-600 transition line-clamp-1 cursor-pointer"
                                            @click="openDetailModal(item)"
                                        >
                                            {{ item.judul }}
                                        </span>
                                        <span
                                            v-if="item.lampiran_url"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-100 shrink-0"
                                            title="Memiliki Berkas Lampiran"
                                        >
                                            <i class="bi bi-paperclip"></i> Berkas
                                        </span>
                                    </div>
                                    <div class="text-slate-500 text-[11px] mt-0.5 line-clamp-2">
                                        {{ item.deskripsi }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span v-if="item.kategori" class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        <i class="bi bi-tag me-1 text-[10px]"></i> {{ item.kategori.nama_kategori }}
                                    </span>
                                    <span v-else class="text-slate-400 italic text-[11px]">Umum</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium border"
                                        :class="getVisibilitasBadge(item.visibilitas).class"
                                    >
                                        {{ getVisibilitasBadge(item.visibilitas).label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 text-[11px]">
                                    <div class="font-medium text-slate-700">{{ formatDate(item.created_at) }}</div>
                                    <div v-if="item.penulis" class="text-[10px] text-slate-400">Oleh: {{ item.penulis.nama_lengkap || item.penulis.username }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                        :class="item.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full me-1.5" :class="item.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center sticky right-0 bg-white group-hover:bg-slate-50/80 transition shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.04)]">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition flex items-center justify-center"
                                            title="Baca Lengkap & Unduh Berkas"
                                            @click="openDetailModal(item)"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition flex items-center justify-center"
                                            title="Ubah Pengumuman"
                                            @click="openEditModal(item)"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition flex items-center justify-center"
                                            title="Hapus Pengumuman"
                                            @click="confirmDelete(item)"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!pengumumanList.data || pengumumanList.data.length === 0">
                                <td :colspan="isSuperAdmin ? 8 : 7" class="py-12 text-center text-slate-400">
                                    <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl text-slate-400">
                                        <i class="bi bi-megaphone"></i>
                                    </div>
                                    <div class="text-sm font-semibold text-slate-700">Belum Ada Data Pengumuman</div>
                                    <div class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                        Data pengumuman tidak ditemukan atau belum ditambahkan. Klik tombol "Tambah Pengumuman" untuk membuat baru.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 3C. FOOTER SMART PAGINATION -->
                <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Tampilkan:</span>
                        <select
                            v-model="perPage"
                            class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            @change="applyFilters"
                        >
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                        <span>data per halaman</span>
                    </div>

                    <!-- Pagination Links -->
                    <div v-if="pengumumanList.links && pengumumanList.links.length > 3" class="flex items-center gap-1">
                        <button
                            v-for="(link, lIdx) in pengumumanList.links"
                            :key="lIdx"
                            type="button"
                            :disabled="!link.url || link.active"
                            class="px-3 py-1.5 text-xs rounded-xl font-medium transition"
                            :class="[
                                link.active ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            @click="changePage(link.url)"
                            v-html="link.label"
                        ></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── 4. MODAL CRUD FORM PENGUMUMAN (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showModalForm"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden transition-all">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                                <i :class="isEditMode ? 'bi bi-pencil-square' : 'bi bi-plus-lg'"></i>
                            </span>
                            <h3 class="text-base font-bold text-slate-800">
                                {{ isEditMode ? 'Ubah Pengumuman Sekolah' : 'Buat Pengumuman Baru' }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 transition"
                            @click="showModalForm = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitForm">
                        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                            <!-- Sekolah Target (Khusus Super Admin) -->
                            <div v-if="isSuperAdmin && tenants.length > 0">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Sekolah / Unit Tujuan <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.tenant_id"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-blue-300 bg-blue-50/50 text-blue-900 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                >
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">
                                        {{ t.nama_sekolah }} ({{ t.npsn || 'NPSN -' }})
                                    </option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Pilih sekolah target di mana pengumuman ini akan dipublikasikan.</p>
                            </div>

                            <!-- Judul Pengumuman -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Judul Pengumuman <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.judul"
                                    type="text"
                                    placeholder="Contoh: Jadwal Pelaksanaan Ujian Sumatif Akhir Semester..."
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                    :class="{ 'border-red-500': form.errors.judul }"
                                    required
                                />
                                <div v-if="form.errors.judul" class="text-red-500 text-[11px] mt-1">{{ form.errors.judul }}</div>
                            </div>

                            <!-- Grid Kategori & Visibilitas -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Kategori Pengumuman
                                    </label>
                                    <select
                                        v-model="form.kategori_id"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                    >
                                        <option value="">Pilih Kategori (Opsional)</option>
                                        <option v-for="kat in kategoriList" :key="kat.id" :value="kat.id">
                                            {{ kat.nama_kategori }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Visibilitas Publikasi <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.visibilitas"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                        required
                                    >
                                        <option value="public">Publik (Semua Warga Sekolah)</option>
                                        <option value="guru">Khusus Dewan Guru</option>
                                        <option value="siswa">Khusus Peserta Didik</option>
                                        <option value="orang_tua">Khusus Orang Tua / Wali</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Target Role Khusus (Jika Visibilitas Bukan Public) -->
                            <div v-if="form.visibilitas !== 'public'" class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                                <label class="block text-xs font-semibold text-slate-700 mb-2">
                                    Target Role Penerima Notifikasi (Opsional Multi-Role)
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <label
                                        v-for="r in availableRoles"
                                        :key="r.value"
                                        class="flex items-center gap-2 p-2 rounded-lg border text-xs cursor-pointer transition select-none"
                                        :class="form.target_roles.includes(r.value) ? 'bg-blue-50 border-blue-300 text-blue-800 font-semibold' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-100'"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="r.value"
                                            :checked="form.target_roles.includes(r.value)"
                                            class="rounded text-blue-600 focus:ring-blue-500 text-xs"
                                            @change="toggleRole(r.value)"
                                        />
                                        <span>{{ r.label }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Isi / Deskripsi Pengumuman -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Isi Pengumuman Lengkap <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.deskripsi"
                                    rows="6"
                                    placeholder="Tuliskan isi warta atau pengumuman resmi secara rinci..."
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition leading-relaxed"
                                    :class="{ 'border-red-500': form.errors.deskripsi }"
                                    required
                                ></textarea>
                                <div v-if="form.errors.deskripsi" class="text-red-500 text-[11px] mt-1">{{ form.errors.deskripsi }}</div>
                            </div>

                            <!-- UPLOAD FILE LAMPIRAN BERKAS -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Berkas / Dokumen Lampiran (Opsional)
                                </label>
                                
                                <!-- File Upload Dropzone -->
                                <div class="p-4 border-2 border-dashed border-slate-200 rounded-xl hover:border-blue-400 bg-slate-50/60 hover:bg-blue-50/30 transition text-center relative group">
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.svg,.zip,.rar"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        @change="handleFileChange"
                                    />
                                    
                                    <div v-if="!selectedFile && !existingFile" class="py-2">
                                        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <p class="text-xs font-medium text-slate-700">
                                            Klik untuk memilih berkas atau seret ke area ini
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            Mendukung PDF, Word, Excel, Gambar (JPG, PNG, WebP), atau ZIP (Maksimal 10 MB)
                                        </p>
                                    </div>

                                    <!-- Berkas Yang Baru Dipilih -->
                                    <div v-else-if="selectedFile" class="flex items-center justify-between p-2.5 bg-white rounded-xl border border-blue-200 shadow-2xs z-20 relative text-left">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center text-lg shrink-0">
                                                <i class="bi" :class="getFileIcon('', selectedFile.name)"></i>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="text-xs font-semibold text-slate-800 truncate" :title="selectedFile.name">
                                                    {{ selectedFile.name }}
                                                </div>
                                                <div class="text-[11px] text-slate-400">
                                                    {{ formatFileSize(selectedFile.size) }} • Siap Diunggah
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition shrink-0"
                                            title="Batalkan File"
                                            @click.stop="removeSelectedFile"
                                        >
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>

                                    <!-- Berkas Lama yang Tersedia di Server (Edit Mode) -->
                                    <div v-else-if="existingFile" class="flex items-center justify-between p-2.5 bg-white rounded-xl border border-slate-200 shadow-2xs z-20 relative text-left">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center text-lg shrink-0">
                                                <i class="bi" :class="getFileIcon(existingFile.url, existingFile.name)"></i>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="text-xs font-semibold text-slate-800 truncate" :title="existingFile.name">
                                                    {{ existingFile.name }}
                                                </div>
                                                <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                                    <span>{{ formatFileSize(existingFile.size) }}</span>
                                                    <a :href="existingFile.url" target="_blank" class="text-blue-600 hover:underline">Lihat Berkas</a>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="px-2.5 py-1 text-[11px] font-semibold text-red-600 hover:bg-red-50 rounded-lg border border-red-200 transition shrink-0"
                                            title="Hapus Lampiran Ini"
                                            @click.stop="markDeleteExistingFile"
                                        >
                                            <i class="bi bi-trash me-1"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle Status Aktif -->
                            <div class="flex items-center gap-3 pt-2">
                                <input
                                    id="pengumuman_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4"
                                />
                                <label for="pengumuman_active" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                    Aktifkan dan Publikasikan Langsung
                                </label>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 transition"
                                @click="showModalForm = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2 disabled:opacity-50"
                            >
                                <span v-if="form.processing" class="spinner-border spinner-border-sm"></span>
                                <i v-else class="bi bi-check-lg"></i>
                                {{ isEditMode ? 'Simpan Perubahan' : 'Publikasikan Pengumuman' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── 5. MODAL DETAIL PENGUMUMAN & LAMPIRAN (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showDetailModal && detailData"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden transition-all">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium border"
                                :class="getVisibilitasBadge(detailData.visibilitas).class"
                            >
                                {{ getVisibilitasBadge(detailData.visibilitas).label }}
                            </span>
                            <span v-if="detailData.kategori" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                <i class="bi bi-tag me-1"></i> {{ detailData.kategori.nama_kategori }}
                            </span>
                        </div>
                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 transition"
                            @click="showDetailModal = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <!-- Badge Sekolah Khusus Super Admin -->
                        <div v-if="detailData.tenant" class="p-2.5 bg-blue-50/80 rounded-xl border border-blue-200 flex items-center gap-2 text-xs text-blue-900 font-semibold">
                            <i class="bi bi-building text-blue-600 text-sm"></i>
                            <span>{{ detailData.tenant.nama_sekolah }} (NPSN: {{ detailData.tenant.npsn || '-' }})</span>
                        </div>

                        <h2 class="text-lg font-bold text-slate-800 leading-snug">
                            {{ detailData.judul }}
                        </h2>

                        <div class="flex items-center gap-4 text-xs text-slate-400 pb-3 border-b border-slate-100">
                            <span class="flex items-center gap-1.5">
                                <i class="bi bi-calendar3"></i> {{ formatDate(detailData.created_at) }}
                            </span>
                            <span v-if="detailData.penulis" class="flex items-center gap-1.5">
                                <i class="bi bi-person"></i> Oleh: {{ detailData.penulis.nama_lengkap || detailData.penulis.username }}
                            </span>
                        </div>

                        <!-- Isi Teks -->
                        <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50/40 p-4 rounded-xl border border-slate-100">
                            {{ detailData.deskripsi }}
                        </div>

                        <!-- TAMPILAN BERKAS LAMPIRAN -->
                        <div v-if="detailData.lampiran_url" class="pt-3 border-t border-slate-100">
                            <h4 class="text-xs font-bold text-slate-700 mb-2.5 flex items-center gap-1.5">
                                <i class="bi bi-paperclip text-blue-600"></i> Dokumen & Berkas Lampiran
                            </h4>

                            <!-- Preview Jika Berkas Gambar -->
                            <div v-if="isImageFile(detailData.lampiran_url)" class="mb-3 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 max-h-80 flex items-center justify-center">
                                <img :src="detailData.lampiran_url" alt="Lampiran Pengumuman" class="object-contain max-h-80 w-full" />
                            </div>

                            <!-- Download Card -->
                            <div class="flex items-center justify-between p-3.5 bg-blue-50/60 rounded-xl border border-blue-200">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-10 h-10 rounded-xl bg-white text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-2xs">
                                        <i class="bi" :class="getFileIcon(detailData.lampiran_url, detailData.lampiran_nama)"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-xs font-bold text-slate-800 truncate" :title="detailData.lampiran_nama">
                                            {{ detailData.lampiran_nama || 'Berkas Lampiran Pengumuman' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            {{ formatFileSize(detailData.lampiran_ukuran) }}
                                        </div>
                                    </div>
                                </div>
                                <a
                                    :href="detailData.lampiran_url"
                                    target="_blank"
                                    download
                                    class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-1.5 shrink-0"
                                >
                                    <i class="bi bi-download"></i> Unduh Berkas
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <div class="text-xs text-slate-500">
                            Status: <span class="font-semibold" :class="detailData.is_active ? 'text-emerald-600' : 'text-slate-500'">{{ detailData.is_active ? 'Aktif Tayang' : 'Nonaktif' }}</span>
                        </div>
                        <button
                            type="button"
                            class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-200 text-slate-700 hover:bg-slate-300 transition"
                            @click="showDetailModal = false"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── 6. MODAL KELOLA KATEGORI (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showKategoriModal"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden transition-all">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                                <i class="bi bi-tags"></i>
                            </span>
                            <h3 class="text-base font-bold text-slate-800">Kelola Kategori Pengumuman</h3>
                        </div>
                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 transition"
                            @click="showKategoriModal = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <!-- Form Tambah Kategori -->
                        <form @submit.prevent="submitKategori" class="space-y-3 pb-4 border-b border-slate-200">
                            <div v-if="isSuperAdmin && tenants.length > 0">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Target Sekolah</label>
                                <select
                                    v-model="kategoriForm.tenant_id"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                                >
                                    <option value="">Semua Sekolah (Global)</option>
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">
                                        {{ t.nama_sekolah }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori Baru</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="kategoriForm.nama_kategori"
                                        type="text"
                                        placeholder="Contoh: Edaran Kurikulum, Kegiatan OSIS..."
                                        class="grow px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                        required
                                    />
                                    <button
                                        type="submit"
                                        :disabled="kategoriForm.processing"
                                        class="px-4 py-2 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shrink-0"
                                    >
                                        Tambah
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- List Kategori yang Ada -->
                        <div>
                            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Daftar Kategori Tersedia</div>
                            <div class="space-y-1.5">
                                <div
                                    v-for="k in kategoriList"
                                    :key="k.id"
                                    class="flex items-center justify-between p-2.5 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-100/70 transition text-xs"
                                >
                                    <div class="font-medium text-slate-800 flex items-center gap-2">
                                        <i class="bi bi-tag text-blue-500"></i>
                                        <span>{{ k.nama_kategori }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="w-6 h-6 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition"
                                        title="Hapus Kategori"
                                        @click="deleteKategori(k.id, k.nama_kategori)"
                                    >
                                        <i class="bi bi-trash text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                        <button
                            type="button"
                            class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-200 text-slate-700 hover:bg-slate-300 transition"
                            @click="showKategoriModal = false"
                        >
                            Selesai
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── 7. MODAL KONFIRMASI HAPUS PENGUMUMAN (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal && itemToDelete"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden transition-all">
                    <div class="p-6 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-3 text-xl font-bold">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 mb-1">Hapus Pengumuman?</h3>
                        <p class="text-xs text-slate-500 max-w-xs mx-auto mb-4">
                            Pengumuman "<span class="font-semibold text-slate-700">{{ itemToDelete.judul }}</span>" dan seluruh berkas lampirannya akan dihapus secara permanen.
                        </p>
                        <div class="flex items-center justify-center gap-2.5">
                            <button
                                type="button"
                                class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 transition"
                                @click="showDeleteModal = false"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-red-600 text-white hover:bg-red-700 shadow-xs transition"
                                @click="executeDelete"
                            >
                                Ya, Hapus Pengumuman
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
