<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'

// ─────────────────────────────────────────────
// PROPS
// ─────────────────────────────────────────────
const props = defineProps({
    agendaList: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 12, from: 0, to: 0 })
    },
    kategoriList: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, aktif: 0, mendatang: 0, hari_ini: 0, selesai: 0 })
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
        default: () => ({ search: '', kategori: '', status: '', tenant_id: '', per_page: 12 })
    }
})

// ─────────────────────────────────────────────
// STATE FILTER, SEARCH & VIEW MODE
// ─────────────────────────────────────────────
const search = ref(props.filters.search || '')
const kategori = ref(props.filters.kategori || '')
const status = ref(props.filters.status !== undefined ? String(props.filters.status) : '')
const tenantId = ref(props.filters.tenant_id || '')
const perPage = ref(props.filters.per_page || 12)
const viewMode = ref('table') // 'table' | 'timeline'

let searchTimeout = null
function handleSearch() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 400)
}

function applyFilters() {
    router.get('/informasi/agenda', {
        search: search.value || undefined,
        kategori: kategori.value || undefined,
        status: status.value !== '' ? status.value : undefined,
        tenant_id: tenantId.value || undefined,
        per_page: perPage.value !== 12 ? perPage.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function resetFilters() {
    search.value = ''
    kategori.value = ''
    status.value = ''
    tenantId.value = ''
    perPage.value = 12
    applyFilters()
}

function changePage(url) {
    if (!url) return
    router.visit(url, { preserveState: true, preserveScroll: true })
}

// ─────────────────────────────────────────────
// MODAL STATE — TAMBAH / EDIT AGENDA
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
    nama_agenda_sekolah: '',
    kategori: '',
    isi: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    waktu_mulai: '',
    waktu_selesai: '',
    lokasi: '',
    penanggung_jawab: '',
    visibilitas: 'public',
    lampiran: null,
    delete_lampiran: '',
    is_active: true,
})

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
    form.tanggal_mulai = new Date().toISOString().substring(0, 10)
    form.tenant_id = tenantId.value || (props.tenants.length > 0 ? props.tenants[0].id : '')
    form.lampiran = null
    form.delete_lampiran = ''
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
    form.nama_agenda_sekolah = item.nama_agenda_sekolah || ''
    form.kategori = item.kategori || ''
    form.isi = item.isi || ''
    form.tanggal_mulai = item.tanggal_mulai || ''
    form.tanggal_selesai = item.tanggal_selesai || ''
    form.waktu_mulai = item.waktu_mulai || ''
    form.waktu_selesai = item.waktu_selesai || ''
    form.lokasi = item.lokasi || ''
    form.penanggung_jawab = item.penanggung_jawab || ''
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

function submitForm() {
    if (isEditMode.value) {
        form.post(`/cms/agenda/${form.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showModalForm.value = false
                form.reset()
            },
        })
    } else {
        form.post('/cms/agenda', {
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
// MODAL STATE — KELOLA KATEGORI AGENDA
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
    kategoriForm.post('/cms/kategori-agenda', {
        preserveScroll: true,
        onSuccess: () => {
            kategoriForm.reset()
        }
    })
}

function deleteKategori(id, nama) {
    if (confirm(`Yakin ingin menghapus kategori agenda "${nama}"?`)) {
        router.delete(`/cms/kategori-agenda/${id}`, {
            preserveScroll: true,
        })
    }
}

// ─────────────────────────────────────────────
// MODAL STATE — DETAIL AGENDA
// ─────────────────────────────────────────────
const showDetailModal = ref(false)
const detailData = ref(null)

function openDetailModal(item) {
    detailData.value = item
    showDetailModal.value = true
}

// ─────────────────────────────────────────────
// MODAL STATE — KONFIRMASI HAPUS AGENDA
// ─────────────────────────────────────────────
const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const isDeleting = ref(false)

function confirmDelete(item) {
    itemToDelete.value = item
    showDeleteModal.value = true
}

function executeDelete() {
    if (!itemToDelete.value) return
    isDeleting.value = true
    router.delete(`/cms/agenda/${itemToDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false
            showDeleteModal.value = false
            itemToDelete.value = null
        },
    })
}

// ─────────────────────────────────────────────
// HELPER FORMATTING & TIMELINE STATUS
// ─────────────────────────────────────────────
function formatDateRange(mulai, selesai) {
    if (!mulai) return '-'
    const d1 = new Date(mulai)
    const formatted1 = isNaN(d1) ? mulai : d1.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    if (!selesai || selesai === mulai) return formatted1

    const d2 = new Date(selesai)
    const formatted2 = isNaN(d2) ? selesai : d2.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    return `${formatted1} - ${formatted2}`
}

function getTimelineStatus(mulai, selesai) {
    if (!mulai) return { label: 'Terjadwal', class: 'bg-blue-50 text-blue-700 border-blue-200' }
    const today = new Date().toISOString().substring(0, 10)
    const end = selesai || mulai

    if (today < mulai) {
        return { label: 'Akan Datang', class: 'bg-indigo-50 text-indigo-700 border-indigo-200', icon: 'bi-hourglass-split' }
    } else if (today >= mulai && today <= end) {
        return { label: 'Sedang Berlangsung', class: 'bg-emerald-50 text-emerald-700 border-emerald-200', icon: 'bi-broadcast' }
    } else {
        return { label: 'Selesai', class: 'bg-slate-100 text-slate-600 border-slate-200', icon: 'bi-check-all' }
    }
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

const getSelectedTenantName = () => {
    if (!tenantId.value) {
        return 'Semua Sekolah Terdaftar (Super Admin)'
    }
    const found = (props.tenants || []).find(t => t.id === tenantId.value)
    return found ? found.nama_sekolah : 'Sekolah Terpilih'
}
</script>

<template>
    <AppLayout title="Agenda & Timeline Sekolah">
        <div class="space-y-6">
            <!-- ── 1. HEADER HALAMAN ── -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center text-base shadow-xs">
                            <i class="bi bi-calendar-event-fill text-lg"></i>
                        </span>
                        Agenda & Timeline Sekolah
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kalender kegiatan akademik, jadwal ujian, rapat dewan guru, dan agenda penting sekolah lainnya.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        class="px-3.5 py-2.5 text-xs font-semibold rounded-xl border border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 shadow-2xs transition flex items-center gap-2"
                        @click="openKategoriModal"
                    >
                        <i class="bi bi-tag text-slate-500"></i> Kelola Kategori
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2"
                        @click="openCreateModal"
                    >
                        <i class="bi bi-plus-lg"></i> Tambah Agenda
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
                        <i class="bi bi-calendar4-week"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Total Agenda</div>
                        <div class="text-xl font-bold text-slate-800">{{ stats.total }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Akan Datang</div>
                        <div class="text-xl font-bold text-indigo-600">{{ stats.mendatang }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-broadcast"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Hari Ini / Berlangsung</div>
                        <div class="text-xl font-bold text-emerald-600">{{ stats.hari_ini }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-check2-all"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Selesai</div>
                        <div class="text-xl font-bold text-slate-700">{{ stats.selesai }}</div>
                    </div>
                </div>
            </div>

            <!-- ── 4. BOX UTAMA: FILTER BAR + TABEL/TIMELINE + FOOTER PAGINATION ── -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                <!-- 4A. FILTER BAR ATAS & VIEW MODE TOGGLE -->
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5 grow">
                        <!-- Search Box -->
                        <div class="relative min-w-[220px] grow md:grow-0">
                            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama agenda atau lokasi..."
                                class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                @input="handleSearch"
                            />
                        </div>

                        <!-- Kategori Dropdown -->
                        <select
                            v-model="kategori"
                            class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition text-slate-700"
                            @change="applyFilters"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="kat in kategoriList" :key="kat.id" :value="kat.nama_kategori">
                                {{ kat.nama_kategori }}
                            </option>
                        </select>

                        <!-- Status Dropdown -->
                        <select
                            v-model="status"
                            class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition text-slate-700"
                            @change="applyFilters"
                        >
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>

                        <!-- Reset Filter -->
                        <button
                            v-if="search || kategori || status || tenantId"
                            type="button"
                            class="px-3 py-2 text-xs font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-xl transition flex items-center gap-1.5"
                            @click="resetFilters"
                        >
                            <i class="bi bi-x-circle"></i> Reset Filter
                        </button>
                    </div>

                    <!-- View Switcher (Table vs Timeline) -->
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="bg-slate-200/60 p-1 rounded-xl flex items-center gap-1">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                                :class="viewMode === 'table' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                @click="viewMode = 'table'"
                            >
                                <i class="bi bi-table"></i> Tabel
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                                :class="viewMode === 'timeline' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                @click="viewMode = 'timeline'"
                            >
                                <i class="bi bi-kanban"></i> Timeline Card
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3B. MODE TABEL -->
                <div v-if="viewMode === 'table'" class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 border-collapse">
                        <thead class="bg-slate-50/80 text-slate-700 uppercase text-[11px] font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th v-if="isSuperAdmin" class="py-3 px-4 min-w-[170px]">Sekolah / Unit</th>
                                <th class="py-3 px-4 min-w-[240px]">Nama Kegiatan & Deskripsi</th>
                                <th class="py-3 px-4 min-w-[140px]">Kategori</th>
                                <th class="py-3 px-4 min-w-[180px]">Jadwal & Waktu</th>
                                <th class="py-3 px-4 min-w-[140px]">Lokasi & PIC</th>
                                <th class="py-3 px-4 w-32 text-center">Status Waktu</th>
                                <th class="py-3 px-4 w-32 text-center sticky right-0 bg-slate-50/90 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.04)]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, idx) in agendaList.data"
                                :key="item.id"
                                class="hover:bg-slate-50/80 transition group"
                            >
                                <td class="py-3.5 px-4 text-center text-slate-400 font-medium">
                                    {{ (agendaList.current_page - 1) * agendaList.per_page + idx + 1 }}
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
                                            {{ item.nama_agenda_sekolah }}
                                        </span>
                                        <span
                                            v-if="item.lampiran_url"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-100 shrink-0"
                                            title="Memiliki Berkas Lampiran"
                                        >
                                            <i class="bi bi-paperclip"></i> Berkas
                                        </span>
                                    </div>
                                    <div v-if="item.isi" class="text-slate-500 text-[11px] mt-0.5 line-clamp-2">
                                        {{ item.isi }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        <i class="bi bi-bookmark me-1 text-[10px]"></i> {{ item.kategori || 'Kegiatan Sekolah' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 text-xs">
                                    <div class="font-semibold text-slate-800 flex items-center gap-1.5">
                                        <i class="bi bi-calendar3 text-blue-500"></i>
                                        {{ formatDateRange(item.tanggal_mulai, item.tanggal_selesai) }}
                                    </div>
                                    <div v-if="item.waktu_mulai" class="text-[11px] text-slate-400 mt-0.5">
                                        Pukul: {{ item.waktu_mulai }} <span v-if="item.waktu_selesai">- {{ item.waktu_selesai }}</span> WIB
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 text-xs">
                                    <div v-if="item.lokasi" class="flex items-center gap-1.5 font-medium text-slate-700">
                                        <i class="bi bi-geo-alt text-red-500"></i> {{ item.lokasi }}
                                    </div>
                                    <div v-if="item.penanggung_jawab" class="text-[11px] text-slate-400 mt-0.5">
                                        PIC: {{ item.penanggung_jawab }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold border"
                                        :class="getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).class"
                                    >
                                        <i class="bi me-1" :class="getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).icon"></i>
                                        {{ getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center sticky right-0 bg-white group-hover:bg-slate-50/80 transition shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.04)]">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition flex items-center justify-center"
                                            title="Detail Agenda & Berkas"
                                            @click="openDetailModal(item)"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition flex items-center justify-center"
                                            title="Ubah Agenda"
                                            @click="openEditModal(item)"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition flex items-center justify-center"
                                            title="Hapus Agenda"
                                            @click="confirmDelete(item)"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!agendaList.data || agendaList.data.length === 0">
                                <td :colspan="isSuperAdmin ? 8 : 7" class="py-12 text-center text-slate-400">
                                    <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl text-slate-400">
                                        <i class="bi bi-calendar-x"></i>
                                    </div>
                                    <div class="text-sm font-semibold text-slate-700">Belum Ada Agenda Terdaftar</div>
                                    <div class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                        Tidak ada agenda yang cocok dengan filter atau belum ditambahkan. Klik tombol "Tambah Agenda" untuk membuat jadwal baru.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 3C. MODE TIMELINE CARD -->
                <div v-else class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="item in agendaList.data"
                            :key="item.id"
                            class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-md transition flex flex-col justify-between group"
                        >
                            <div>
                                <!-- Header Card -->
                                <div class="flex items-start justify-between gap-2 mb-2.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ item.kategori || 'Kegiatan' }}
                                    </span>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold border"
                                        :class="getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).class"
                                    >
                                        {{ getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).label }}
                                    </span>
                                </div>

                                <!-- Badge Sekolah Super Admin -->
                                <div v-if="isSuperAdmin && item.tenant" class="mb-2 text-[11px] font-semibold text-blue-800 bg-blue-50/60 px-2.5 py-1 rounded-lg border border-blue-100 flex items-center gap-1.5">
                                    <i class="bi bi-building"></i>
                                    <span class="truncate">{{ item.tenant.nama_sekolah }}</span>
                                </div>

                                <h3
                                    class="text-sm font-bold text-slate-800 group-hover:text-blue-600 transition line-clamp-2 cursor-pointer mb-2"
                                    @click="openDetailModal(item)"
                                >
                                    {{ item.nama_agenda_sekolah }}
                                </h3>

                                <p v-if="item.isi" class="text-xs text-slate-500 line-clamp-3 mb-3 leading-relaxed">
                                    {{ item.isi }}
                                </p>
                            </div>

                            <!-- Footer Info Card -->
                            <div class="pt-3 border-t border-slate-100 space-y-2">
                                <div class="flex items-center gap-2 text-xs text-slate-700 font-medium">
                                    <i class="bi bi-calendar3 text-blue-600"></i>
                                    <span>{{ formatDateRange(item.tanggal_mulai, item.tanggal_selesai) }}</span>
                                </div>
                                <div v-if="item.lokasi" class="flex items-center gap-2 text-xs text-slate-500 truncate">
                                    <i class="bi bi-geo-alt text-red-500"></i>
                                    <span class="truncate">{{ item.lokasi }}</span>
                                </div>
                                <div v-if="item.lampiran_url" class="flex items-center gap-2 text-xs text-blue-600 font-medium">
                                    <i class="bi bi-paperclip"></i>
                                    <span>Ada Berkas Lampiran</span>
                                </div>

                                <div class="flex items-center justify-end gap-1.5 pt-2">
                                    <button
                                        type="button"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition flex items-center gap-1"
                                        @click="openDetailModal(item)"
                                    >
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                    <button
                                        type="button"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-amber-600 hover:bg-amber-50 transition flex items-center gap-1"
                                        @click="openEditModal(item)"
                                    >
                                        <i class="bi bi-pencil"></i> Ubah
                                    </button>
                                    <button
                                        type="button"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-red-600 hover:bg-red-50 transition flex items-center gap-1"
                                        @click="confirmDelete(item)"
                                    >
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State Timeline -->
                        <div v-if="!agendaList.data || agendaList.data.length === 0" class="col-span-full py-12 text-center text-slate-400">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl text-slate-400">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <div class="text-sm font-semibold text-slate-700">Belum Ada Agenda Terdaftar</div>
                        </div>
                    </div>
                </div>

                <!-- 3D. FOOTER SMART PAGINATION -->
                <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Tampilkan:</span>
                        <select
                            v-model="perPage"
                            class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            @change="applyFilters"
                        >
                            <option :value="6">6</option>
                            <option :value="12">12</option>
                            <option :value="24">24</option>
                            <option :value="48">48</option>
                        </select>
                        <span>data per halaman</span>
                    </div>

                    <!-- Pagination Links -->
                    <div v-if="agendaList.links && agendaList.links.length > 3" class="flex items-center gap-1">
                        <button
                            v-for="(link, lIdx) in agendaList.links"
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

        <!-- ── 4. MODAL CRUD FORM AGENDA (TELEPORT) ── -->
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
                                {{ isEditMode ? 'Ubah Agenda Kegiatan Sekolah' : 'Buat Agenda Kegiatan Baru' }}
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
                            </div>

                            <!-- Nama Agenda -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Nama Agenda / Kegiatan <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.nama_agenda_sekolah"
                                    type="text"
                                    placeholder="Contoh: Rapat Evaluasi Kurikulum Semester Ganjil..."
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                    :class="{ 'border-red-500': form.errors.nama_agenda_sekolah }"
                                    required
                                />
                                <div v-if="form.errors.nama_agenda_sekolah" class="text-red-500 text-[11px] mt-1">{{ form.errors.nama_agenda_sekolah }}</div>
                            </div>

                            <!-- Grid Kategori & Visibilitas -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Kategori Kegiatan <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.kategori"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                        required
                                    >
                                        <option value="">Pilih Kategori</option>
                                        <option v-for="kat in kategoriList" :key="kat.id" :value="kat.nama_kategori">
                                            {{ kat.nama_kategori }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Visibilitas <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.visibilitas"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                        required
                                    >
                                        <option value="public">Publik (Semua Warga Sekolah)</option>
                                        <option value="guru">Khusus Dewan Guru & Tendik</option>
                                        <option value="siswa">Khusus Peserta Didik</option>
                                        <option value="internal">Internal Panitia</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Grid Tanggal Mulai & Tanggal Selesai -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Tanggal Mulai <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.tanggal_mulai"
                                        type="date"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Tanggal Selesai (Opsional)
                                    </label>
                                    <input
                                        v-model="form.tanggal_selesai"
                                        type="date"
                                        :min="form.tanggal_mulai"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                    />
                                </div>
                            </div>

                            <!-- Grid Waktu & Lokasi -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Waktu Mulai
                                    </label>
                                    <input
                                        v-model="form.waktu_mulai"
                                        type="time"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Waktu Selesai
                                    </label>
                                    <input
                                        v-model="form.waktu_selesai"
                                        type="time"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Lokasi Ruangan / Tempat
                                    </label>
                                    <input
                                        v-model="form.lokasi"
                                        type="text"
                                        placeholder="Misal: Aula Utama / Lab Komputer"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                    />
                                </div>
                            </div>

                            <!-- PIC / Penanggung Jawab -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Penanggung Jawab / PIC Kegiatan
                                </label>
                                <input
                                    v-model="form.penanggung_jawab"
                                    type="text"
                                    placeholder="Contoh: Wakasek Kurikulum / Pembina OSIS"
                                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                />
                            </div>

                            <!-- Deskripsi Lengkap -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Deskripsi / Rangkaian Acara
                                </label>
                                <textarea
                                    v-model="form.isi"
                                    rows="4"
                                    placeholder="Tuliskan petunjuk teknis, susunan acara, atau catatan penting terkait agenda ini..."
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition leading-relaxed"
                                ></textarea>
                            </div>

                            <!-- UPLOAD FILE LAMPIRAN BERKAS -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Berkas / Panduan Acara Lampiran (Opsional)
                                </label>
                                
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
                                            Klik untuk memilih berkas lampiran agenda atau seret ke area ini
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            Mendukung PDF Rundown, Panduan Word/Excel, Gambar Poster (Maksimal 10 MB)
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
                                    id="agenda_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4"
                                />
                                <label for="agenda_active" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                    Aktifkan Agenda di Kalender Sekolah
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
                                {{ isEditMode ? 'Simpan Perubahan' : 'Simpan Agenda' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── 5. MODAL DETAIL AGENDA & BERKAS (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showDetailModal && detailData"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden transition-all">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                {{ detailData.kategori || 'Kegiatan' }}
                            </span>
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                                :class="getTimelineStatus(detailData.tanggal_mulai, detailData.tanggal_selesai).class"
                            >
                                <i class="bi me-1.5" :class="getTimelineStatus(detailData.tanggal_mulai, detailData.tanggal_selesai).icon"></i>
                                {{ getTimelineStatus(detailData.tanggal_mulai, detailData.tanggal_selesai).label }}
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
                            {{ detailData.nama_agenda_sekolah }}
                        </h2>

                        <!-- Info Jadwal Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                            <div class="space-y-1.5">
                                <div class="text-slate-400 flex items-center gap-1.5">
                                    <i class="bi bi-calendar-event text-blue-500"></i> Tanggal Pelaksanaan
                                </div>
                                <div class="font-bold text-slate-800">
                                    {{ formatDateRange(detailData.tanggal_mulai, detailData.tanggal_selesai) }}
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div class="text-slate-400 flex items-center gap-1.5">
                                    <i class="bi bi-clock text-indigo-500"></i> Waktu Pelaksanaan
                                </div>
                                <div class="font-bold text-slate-800">
                                    {{ detailData.waktu_mulai ? (detailData.waktu_mulai + (detailData.waktu_selesai ? ' - ' + detailData.waktu_selesai : '') + ' WIB') : 'Sepanjang Hari / Kondisional' }}
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div class="text-slate-400 flex items-center gap-1.5">
                                    <i class="bi bi-geo-alt text-red-500"></i> Tempat / Lokasi
                                </div>
                                <div class="font-bold text-slate-800">
                                    {{ detailData.lokasi || 'Lingkungan Sekolah' }}
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div class="text-slate-400 flex items-center gap-1.5">
                                    <i class="bi bi-person-badge text-emerald-500"></i> Penanggung Jawab / PIC
                                </div>
                                <div class="font-bold text-slate-800">
                                    {{ detailData.penanggung_jawab || '-' }}
                                </div>
                            </div>
                        </div>

                        <!-- Deskripsi Lengkap -->
                        <div v-if="detailData.isi">
                            <h4 class="text-xs font-bold text-slate-700 mb-1.5">Rangkaian & Deskripsi Acara</h4>
                            <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50/40 p-4 rounded-xl border border-slate-100">
                                {{ detailData.isi }}
                            </div>
                        </div>

                        <!-- TAMPILAN BERKAS LAMPIRAN AGENDA -->
                        <div v-if="detailData.lampiran_url" class="pt-3 border-t border-slate-100">
                            <h4 class="text-xs font-bold text-slate-700 mb-2.5 flex items-center gap-1.5">
                                <i class="bi bi-paperclip text-blue-600"></i> Dokumen & Panduan Kegiatan
                            </h4>

                            <!-- Preview Jika Berkas Gambar Poster -->
                            <div v-if="isImageFile(detailData.lampiran_url)" class="mb-3 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 max-h-80 flex items-center justify-center">
                                <img :src="detailData.lampiran_url" alt="Lampiran Agenda" class="object-contain max-h-80 w-full" />
                            </div>

                            <!-- Download Card -->
                            <div class="flex items-center justify-between p-3.5 bg-blue-50/60 rounded-xl border border-blue-200">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-10 h-10 rounded-xl bg-white text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-2xs">
                                        <i class="bi" :class="getFileIcon(detailData.lampiran_url, detailData.lampiran_nama)"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-xs font-bold text-slate-800 truncate" :title="detailData.lampiran_nama">
                                            {{ detailData.lampiran_nama || 'Berkas Lampiran Agenda' }}
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
                            Status: <span class="font-semibold" :class="detailData.is_active ? 'text-emerald-600' : 'text-slate-500'">{{ detailData.is_active ? 'Aktif di Kalender' : 'Nonaktif' }}</span>
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

        <!-- ── 6. MODAL KELOLA KATEGORI AGENDA (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showKategoriModal"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden transition-all">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                                <i class="bi bi-tag"></i>
                            </span>
                            <h3 class="text-base font-bold text-slate-800">Kelola Kategori Agenda</h3>
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
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori Agenda Baru</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="kategoriForm.nama_kategori"
                                        type="text"
                                        placeholder="Contoh: Rapat Kerja, Ujian Akhir, Kegiatan OSIS..."
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
                            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Daftar Kategori Agenda Tersedia</div>
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
                                        title="Hapus Kategori Agenda"
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

        <!-- ── 7. MODAL KONFIRMASI HAPUS AGENDA (TELEPORT) ── -->
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
                        <h3 class="text-base font-bold text-slate-800 mb-1">Hapus Agenda Kegiatan?</h3>
                        <p class="text-xs text-slate-500 max-w-xs mx-auto mb-4">
                            Agenda "<span class="font-semibold text-slate-700">{{ itemToDelete.nama_agenda_sekolah }}</span>" dan seluruh berkas lampirannya akan dihapus secara permanen.
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
                                type="submit"
                                :disabled="isDeleting"
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-red-600 text-white hover:bg-red-700 shadow-xs transition disabled:opacity-50"
                                @click="executeDelete"
                            >
                                <span v-if="isDeleting" class="spinner-border spinner-border-sm"></span>
                                <span v-else>Ya, Hapus Agenda</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
