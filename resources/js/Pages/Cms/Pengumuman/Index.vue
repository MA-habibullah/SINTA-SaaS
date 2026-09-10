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
    filters: {
        type: Object,
        default: () => ({ search: '', kategori_id: '', visibilitas: '', status: '', per_page: 15 })
    }
})

// ─────────────────────────────────────────────
// STATE FILTER & SEARCH
// ─────────────────────────────────────────────
const search = ref(props.filters.search || '')
const kategoriId = ref(props.filters.kategori_id || '')
const visibilitas = ref(props.filters.visibilitas || '')
const status = ref(props.filters.status !== undefined ? String(props.filters.status) : '')
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

const form = useForm({
    id: '',
    judul: '',
    deskripsi: '',
    kategori_id: '',
    visibilitas: 'public',
    target_roles: [],
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
    form.reset()
    form.clearErrors()
    form.visibilitas = 'public'
    form.is_active = true
    form.target_roles = []
    showModalForm.value = true
}

function openEditModal(item) {
    isEditMode.value = true
    selectedItem.value = item
    form.clearErrors()
    form.id = item.id
    form.judul = item.judul || ''
    form.deskripsi = item.deskripsi || ''
    form.kategori_id = item.kategori_id || ''
    form.visibilitas = item.visibilitas || 'public'
    form.is_active = Boolean(item.is_active)

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
        form.put(`/cms/pengumuman/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showModalForm.value = false
                form.reset()
            },
        })
    } else {
        form.post('/cms/pengumuman', {
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
    nama_kategori: ''
})

function openKategoriModal() {
    kategoriForm.reset()
    kategoriForm.clearErrors()
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
const isDeleting = ref(false)

function confirmDelete(item) {
    itemToDelete.value = item
    showDeleteModal.value = true
}

function executeDelete() {
    if (!itemToDelete.value) return
    isDeleting.value = true
    router.delete(`/cms/pengumuman/${itemToDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false
            showDeleteModal.value = false
            itemToDelete.value = null
        },
    })
}

// ─────────────────────────────────────────────
// HELPER FORMATTING
// ─────────────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    return isNaN(d) ? dateStr : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function getVisibilitasBadge(vis) {
    switch (vis) {
        case 'public':
            return { label: 'Publik (Semua)', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' }
        case 'guru':
            return { label: 'Khusus Guru', class: 'bg-blue-50 text-blue-700 border-blue-200' }
        case 'siswa':
            return { label: 'Khusus Siswa', class: 'bg-indigo-50 text-indigo-700 border-indigo-200' }
        case 'orang_tua':
            return { label: 'Orang Tua / Wali', class: 'bg-amber-50 text-amber-700 border-amber-200' }
        default:
            return { label: vis || 'Publik', class: 'bg-slate-50 text-slate-700 border-slate-200' }
    }
}
</script>

<template>
    <AppLayout title="Pengumuman Sekolah">
        <div class="space-y-6">
            <!-- ── 1. HEADER HALAMAN ── -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2.5">
                        <span class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs">
                            <i class="bi bi-megaphone-fill text-lg"></i>
                        </span>
                        Pengumuman Sekolah
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Publikasi warta resmi, edaran dinas, dan pengumuman kegiatan sekolah untuk seluruh warga sekolah.
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

            <!-- ── 2. KARTU METRIK STATISTIK ── -->
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

            <!-- ── 3. BOX UTAMA: FILTER BAR + TABEL + FOOTER PAGINATION ── -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                <!-- 3A. FILTER BAR ATAS -->
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5 grow">
                        <!-- Search Box -->
                        <div class="relative min-w-[240px] grow md:grow-0">
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
                            v-if="search || kategoriId || visibilitas || status"
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
                                <th class="py-3 px-4 min-w-[280px]">Judul & Deskripsi Ringkas</th>
                                <th class="py-3 px-4 min-w-[150px]">Kategori</th>
                                <th class="py-3 px-4 min-w-[140px]">Visibilitas</th>
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
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-800 text-sm group-hover:text-blue-600 transition line-clamp-1 cursor-pointer" @click="openDetailModal(item)">
                                        {{ item.judul }}
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
                                            title="Baca Lengkap"
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
                                <td colspan="7" class="py-12 text-center text-slate-400">
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
                            class="text-slate-400 hover:text-slate-600 text-lg transition"
                            @click="showModalForm = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitForm">
                        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                            <!-- Judul -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Judul Pengumuman <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.judul"
                                    type="text"
                                    placeholder="Contoh: Jadwal Pelaksanaan Asesmen Sumatif Akhir Semester..."
                                    required
                                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                />
                                <div v-if="form.errors.judul" class="text-red-500 text-[11px] mt-1">{{ form.errors.judul }}</div>
                            </div>

                            <!-- Kategori & Visibilitas -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Pengumuman</label>
                                    <select
                                        v-model="form.kategori_id"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                    >
                                        <option value="">Pilih Kategori (Opsional)</option>
                                        <option v-for="kat in kategoriList" :key="kat.id" :value="kat.id">
                                            {{ kat.nama_kategori }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Target Visibilitas <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.visibilitas"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                    >
                                        <option value="public">Publik (Semua Warga Sekolah)</option>
                                        <option value="guru">Khusus Dewan Guru & GTK</option>
                                        <option value="siswa">Khusus Peserta Didik</option>
                                        <option value="orang_tua">Khusus Orang Tua / Wali</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Target Roles Checkbox (Jika bukan public) -->
                            <div v-if="form.visibilitas !== 'public'" class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <label class="block text-xs font-semibold text-slate-700 mb-2">Pilih Role Spesifik (Opsional):</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <label
                                        v-for="role in availableRoles"
                                        :key="role.value"
                                        class="flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer select-none"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="form.target_roles.includes(role.value)"
                                            class="rounded text-blue-600 focus:ring-blue-500"
                                            @change="toggleRole(role.value)"
                                        />
                                        <span>{{ role.label }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Isi / Deskripsi Pengumuman -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Isi Lengkap Pengumuman <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.deskripsi"
                                    rows="6"
                                    placeholder="Tuliskan detail pengumuman secara lengkap dan jelas..."
                                    required
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition resize-y"
                                ></textarea>
                                <div v-if="form.errors.deskripsi" class="text-red-500 text-[11px] mt-1">{{ form.errors.deskripsi }}</div>
                            </div>

                            <!-- Toggle Status Aktif -->
                            <div class="flex items-center justify-between p-3.5 bg-slate-50/80 rounded-xl border border-slate-200">
                                <div>
                                    <div class="text-xs font-semibold text-slate-800">Status Publikasi</div>
                                    <div class="text-[11px] text-slate-500">Aktifkan agar pengumuman langsung tampil di portal pengguna</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition"
                                @click="showModalForm = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2"
                            >
                                <i v-if="form.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <span>{{ isEditMode ? 'Simpan Perubahan' : 'Terbitkan Pengumuman' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── 5. MODAL DETAIL / BACA PENGUMUMAN (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showDetailModal && detailData"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                                <i class="bi bi-info-circle"></i>
                            </span>
                            <h3 class="text-base font-bold text-slate-800">Detail Pengumuman</h3>
                        </div>
                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600 text-lg transition"
                            @click="showDetailModal = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <div class="flex flex-wrap items-center gap-2">
                            <span v-if="detailData.kategori" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                {{ detailData.kategori.nama_kategori }}
                            </span>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold border" :class="getVisibilitasBadge(detailData.visibilitas).class">
                                {{ getVisibilitasBadge(detailData.visibilitas).label }}
                            </span>
                            <span class="text-xs text-slate-400 ml-auto flex items-center gap-1">
                                <i class="bi bi-calendar-event"></i> {{ formatDate(detailData.created_at) }}
                            </span>
                        </div>

                        <h2 class="text-lg font-bold text-slate-800 leading-snug">
                            {{ detailData.judul }}
                        </h2>

                        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-100 text-xs text-slate-700 leading-relaxed whitespace-pre-wrap">
                            {{ detailData.deskripsi }}
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                        <button
                            type="button"
                            class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-700 text-white hover:bg-slate-800 transition"
                            @click="showDetailModal = false"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── 6. MODAL KELOLA MASTER KATEGORI (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showKategoriModal"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                                <i class="bi bi-tags"></i>
                            </span>
                            <h3 class="text-base font-bold text-slate-800">Kelola Kategori Pengumuman</h3>
                        </div>
                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600 text-lg transition"
                            @click="showKategoriModal = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Tambah Kategori Form -->
                        <form @submit.prevent="submitKategori" class="flex gap-2">
                            <input
                                v-model="kategoriForm.nama_kategori"
                                type="text"
                                placeholder="Nama kategori baru..."
                                required
                                class="grow px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                            />
                            <button
                                type="submit"
                                :disabled="kategoriForm.processing"
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shrink-0"
                            >
                                Tambah
                            </button>
                        </form>

                        <!-- Daftar Kategori Eksisting -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100 max-h-60 overflow-y-auto">
                            <div
                                v-for="kat in kategoriList"
                                :key="kat.id"
                                class="px-4 py-2.5 flex items-center justify-between hover:bg-slate-50 transition text-xs text-slate-700"
                            >
                                <span class="font-medium flex items-center gap-2">
                                    <i class="bi bi-tag text-slate-400"></i> {{ kat.nama_kategori }}
                                </span>
                                <button
                                    type="button"
                                    class="w-6 h-6 rounded text-slate-400 hover:text-red-600 transition flex items-center justify-center"
                                    title="Hapus Kategori"
                                    @click="deleteKategori(kat.id, kat.nama_kategori)"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <div v-if="!kategoriList || kategoriList.length === 0" class="p-4 text-center text-xs text-slate-400">
                                Belum ada kategori
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                        <button
                            type="button"
                            class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-700 text-white hover:bg-slate-800 transition"
                            @click="showKategoriModal = false"
                        >
                            Selesai
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── 7. MODAL KONFIRMASI HAPUS (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal && itemToDelete"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden p-6 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-2">Hapus Pengumuman?</h3>
                    <p class="text-xs text-slate-500 mb-6">
                        Pengumuman <span class="font-semibold text-slate-700">"{{ itemToDelete.judul }}"</span> akan dihapus secara permanen dari sistem.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button
                            type="button"
                            class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 transition"
                            @click="showDeleteModal = false"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            :disabled="isDeleting"
                            class="px-5 py-2 text-xs font-semibold rounded-xl bg-red-600 text-white hover:bg-red-700 shadow-xs transition flex items-center gap-1.5"
                            @click="executeDelete"
                        >
                            <i v-if="isDeleting" class="bi bi-arrow-repeat animate-spin"></i>
                            <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
