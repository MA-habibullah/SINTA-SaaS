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
    filters: {
        type: Object,
        default: () => ({ search: '', kategori: '', status: '', per_page: 12 })
    }
})

// ─────────────────────────────────────────────
// STATE FILTER, SEARCH & VIEW MODE
// ─────────────────────────────────────────────
const search = ref(props.filters.search || '')
const kategori = ref(props.filters.kategori || '')
const status = ref(props.filters.status !== undefined ? String(props.filters.status) : '')
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

const form = useForm({
    id: '',
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
    is_active: true,
})

function openCreateModal() {
    isEditMode.value = false
    selectedItem.value = null
    form.reset()
    form.clearErrors()
    form.visibilitas = 'public'
    form.is_active = true
    form.tanggal_mulai = new Date().toISOString().substring(0, 10)
    showModalForm.value = true
}

function openEditModal(item) {
    isEditMode.value = true
    selectedItem.value = item
    form.clearErrors()
    form.id = item.id
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
    showModalForm.value = true
}

function submitForm() {
    if (isEditMode.value) {
        form.put(`/cms/agenda/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showModalForm.value = false
                form.reset()
            },
        })
    } else {
        form.post('/cms/agenda', {
            preserveScroll: true,
            onSuccess: () => {
                showModalForm.value = false
                form.reset()
            },
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
</script>

<template>
    <AppLayout title="Agenda & Timeline Sekolah">
        <div class="space-y-6">
            <!-- ── 1. HEADER HALAMAN ── -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2.5">
                        <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                            <i class="bi bi-calendar3 text-lg"></i>
                        </span>
                        Agenda & Timeline Sekolah
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Manajemen kalender kegiatan akademik, rapat dewan guru, upacara, asesmen, dan timeline sekolah.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-xs transition flex items-center gap-2"
                        @click="openCreateModal"
                    >
                        <i class="bi bi-plus-lg"></i> Tambah Agenda
                    </button>
                </div>
            </div>

            <!-- ── 2. KARTU METRIK STATISTIK ── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Total Agenda</div>
                        <div class="text-xl font-bold text-slate-800">{{ stats.total }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Akan Datang</div>
                        <div class="text-xl font-bold text-blue-600">{{ stats.mendatang }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-broadcast"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Hari Ini / Berjalan</div>
                        <div class="text-xl font-bold text-emerald-600">{{ stats.hari_ini }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 text-xl font-bold">
                        <i class="bi bi-check2-all"></i>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-slate-500">Terlaksana</div>
                        <div class="text-xl font-bold text-slate-700">{{ stats.selesai }}</div>
                    </div>
                </div>
            </div>

            <!-- ── 3. BOX UTAMA: FILTER BAR + DATA (TABEL / TIMELINE) + FOOTER PAGINATION ── -->
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
                                placeholder="Cari nama agenda, lokasi, atau penanggung jawab..."
                                class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                @input="handleSearch"
                            />
                        </div>

                        <!-- Kategori Dropdown -->
                        <select
                            v-model="kategori"
                            class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition text-slate-700"
                            @change="applyFilters"
                        >
                            <option value="">Semua Kategori Agenda</option>
                            <option v-for="kat in kategoriList" :key="kat" :value="kat">
                                {{ kat }}
                            </option>
                        </select>

                        <!-- Status Dropdown -->
                        <select
                            v-model="status"
                            class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition text-slate-700"
                            @change="applyFilters"
                        >
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>

                        <!-- Reset Filter -->
                        <button
                            v-if="search || kategori || status"
                            type="button"
                            class="px-3 py-2 text-xs font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-xl transition flex items-center gap-1.5"
                            @click="resetFilters"
                        >
                            <i class="bi bi-x-circle"></i> Reset Filter
                        </button>
                    </div>

                    <!-- View Mode Switcher -->
                    <div class="flex items-center gap-2 self-end md:self-auto shrink-0">
                        <div class="flex p-0.5 rounded-xl bg-slate-200/70 border border-slate-200">
                            <button
                                type="button"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg transition flex items-center gap-1.5"
                                :class="viewMode === 'table' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-800'"
                                @click="viewMode = 'table'"
                            >
                                <i class="bi bi-table"></i> Tabel
                            </button>
                            <button
                                type="button"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg transition flex items-center gap-1.5"
                                :class="viewMode === 'timeline' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-800'"
                                @click="viewMode = 'timeline'"
                            >
                                <i class="bi bi-view-stacked"></i> Timeline Card
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3B. DATA VIEW — MODE TABEL -->
                <div v-if="viewMode === 'table'" class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 border-collapse">
                        <thead class="bg-slate-50/80 text-slate-700 uppercase text-[11px] font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4 min-w-[200px]">Tanggal & Waktu</th>
                                <th class="py-3 px-4 min-w-[260px]">Nama Agenda & Deskripsi</th>
                                <th class="py-3 px-4 min-w-[150px]">Kategori</th>
                                <th class="py-3 px-4 min-w-[150px]">Lokasi</th>
                                <th class="py-3 px-4 min-w-[150px]">Penanggung Jawab</th>
                                <th class="py-3 px-4 w-28 text-center">Status Waktu</th>
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
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-semibold text-slate-800 flex items-center gap-1.5">
                                        <i class="bi bi-calendar2-range text-indigo-500 text-xs"></i>
                                        {{ formatDateRange(item.tanggal_mulai, item.tanggal_selesai) }}
                                    </div>
                                    <div v-if="item.waktu_mulai" class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                                        <i class="bi bi-clock text-slate-400 text-[10px]"></i>
                                        {{ item.waktu_mulai }} <span v-if="item.waktu_selesai">- {{ item.waktu_selesai }}</span> WIB
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-800 text-sm group-hover:text-indigo-600 transition line-clamp-1 cursor-pointer" @click="openDetailModal(item)">
                                        {{ item.nama_agenda_sekolah }}
                                    </div>
                                    <div v-if="item.isi" class="text-slate-500 text-[11px] mt-0.5 line-clamp-2">
                                        {{ item.isi }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span v-if="item.kategori" class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ item.kategori }}
                                    </span>
                                    <span v-else class="text-slate-400 italic text-[11px]">Umum</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span v-if="item.lokasi" class="text-slate-700 font-medium flex items-center gap-1.5">
                                        <i class="bi bi-geo-alt text-red-500"></i> {{ item.lokasi }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span v-if="item.penanggung_jawab" class="text-slate-700 flex items-center gap-1.5">
                                        <i class="bi bi-person-badge text-blue-500"></i> {{ item.penanggung_jawab }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-semibold border"
                                        :class="getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).class"
                                    >
                                        <i class="bi me-1 text-[10px]" :class="getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).icon"></i>
                                        {{ getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center sticky right-0 bg-white group-hover:bg-slate-50/80 transition shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.04)]">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition flex items-center justify-center"
                                            title="Detail Agenda"
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

                            <!-- Empty State Table -->
                            <tr v-if="!agendaList.data || agendaList.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl text-slate-400">
                                        <i class="bi bi-calendar-x"></i>
                                    </div>
                                    <div class="text-sm font-semibold text-slate-700">Belum Ada Agenda Kegiatan</div>
                                    <div class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                        Data agenda kegiatan tidak ditemukan. Klik tombol "Tambah Agenda" untuk membuat jadwal baru.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 3C. DATA VIEW — MODE TIMELINE CARDS -->
                <div v-else class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="item in agendaList.data"
                            :key="item.id"
                            class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs hover:border-indigo-300 transition group flex flex-col justify-between"
                        >
                            <div>
                                <!-- Top Card Badge & Status -->
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <span v-if="item.kategori" class="px-2.5 py-0.5 rounded-lg text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ item.kategori }}
                                    </span>
                                    <span
                                        class="px-2 py-0.5 rounded-lg text-[10px] font-semibold border"
                                        :class="getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).class"
                                    >
                                        {{ getTimelineStatus(item.tanggal_mulai, item.tanggal_selesai).label }}
                                    </span>
                                </div>

                                <!-- Agenda Title -->
                                <h3 class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition mb-2 cursor-pointer" @click="openDetailModal(item)">
                                    {{ item.nama_agenda_sekolah }}
                                </h3>

                                <!-- Date & Time -->
                                <div class="space-y-1 text-xs text-slate-600 mb-3 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <i class="bi bi-calendar3 text-indigo-500"></i>
                                        <span class="font-medium">{{ formatDateRange(item.tanggal_mulai, item.tanggal_selesai) }}</span>
                                    </div>
                                    <div v-if="item.waktu_mulai" class="flex items-center gap-2 text-slate-500 text-[11px]">
                                        <i class="bi bi-clock text-slate-400"></i>
                                        <span>{{ item.waktu_mulai }} <span v-if="item.waktu_selesai">- {{ item.waktu_selesai }}</span> WIB</span>
                                    </div>
                                </div>

                                <!-- Location & PJ -->
                                <div class="space-y-1 text-[11px] text-slate-500 mb-4">
                                    <div v-if="item.lokasi" class="flex items-center gap-1.5">
                                        <i class="bi bi-geo-alt text-red-500"></i>
                                        <span>{{ item.lokasi }}</span>
                                    </div>
                                    <div v-if="item.penanggung_jawab" class="flex items-center gap-1.5">
                                        <i class="bi bi-person-badge text-blue-500"></i>
                                        <span>PJ: {{ item.penanggung_jawab }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Footer Actions -->
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="item.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition"
                                        title="Detail"
                                        @click="openDetailModal(item)"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition"
                                        title="Edit"
                                        @click="openEditModal(item)"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition"
                                        title="Hapus"
                                        @click="confirmDelete(item)"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State Cards -->
                        <div v-if="!agendaList.data || agendaList.data.length === 0" class="col-span-full py-12 text-center text-slate-400">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl text-slate-400">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <div class="text-sm font-semibold text-slate-700">Belum Ada Agenda Kegiatan</div>
                        </div>
                    </div>
                </div>

                <!-- 3D. FOOTER SMART PAGINATION -->
                <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Tampilkan:</span>
                        <select
                            v-model="perPage"
                            class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
                                link.active ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100',
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
                            <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                                <i :class="isEditMode ? 'bi bi-pencil-square' : 'bi bi-plus-lg'"></i>
                            </span>
                            <h3 class="text-base font-bold text-slate-800">
                                {{ isEditMode ? 'Ubah Agenda Kegiatan Sekolah' : 'Buat Agenda Kegiatan Baru' }}
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
                            <!-- Nama Agenda -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Nama Agenda / Kegiatan <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.nama_agenda_sekolah"
                                    type="text"
                                    placeholder="Contoh: Rapat Koordinasi & Pleno Dewan Guru..."
                                    required
                                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                />
                                <div v-if="form.errors.nama_agenda_sekolah" class="text-red-500 text-[11px] mt-1">{{ form.errors.nama_agenda_sekolah }}</div>
                            </div>

                            <!-- Kategori & Visibilitas -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Kategori Kegiatan <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.kategori"
                                        type="text"
                                        list="agendaKategoriOptions"
                                        placeholder="Ketik atau pilih kategori..."
                                        required
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                    />
                                    <datalist id="agendaKategoriOptions">
                                        <option v-for="kat in kategoriList" :key="kat" :value="kat" />
                                        <option value="Akademik & Pembelajaran" />
                                        <option value="Kedinasan & Rapat" />
                                        <option value="Kesiswaan & Ekskul" />
                                        <option value="Ujian & Asesmen" />
                                        <option value="Hari Libur & Peringatan" />
                                    </datalist>
                                    <div v-if="form.errors.kategori" class="text-red-500 text-[11px] mt-1">{{ form.errors.kategori }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Visibilitas</label>
                                    <select
                                        v-model="form.visibilitas"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                    >
                                        <option value="public">Publik (Semua Warga)</option>
                                        <option value="guru">Khusus Dewan Guru & GTK</option>
                                        <option value="siswa">Khusus Peserta Didik</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tanggal Mulai & Tanggal Selesai -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Tanggal Mulai <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.tanggal_mulai"
                                        type="date"
                                        required
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                    />
                                    <div v-if="form.errors.tanggal_mulai" class="text-red-500 text-[11px] mt-1">{{ form.errors.tanggal_mulai }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Selesai (Opsional)</label>
                                    <input
                                        v-model="form.tanggal_selesai"
                                        type="date"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                    />
                                </div>
                            </div>

                            <!-- Waktu Mulai & Waktu Selesai -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Waktu Mulai (Contoh: 08:00)</label>
                                    <input
                                        v-model="form.waktu_mulai"
                                        type="text"
                                        placeholder="08:00"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Waktu Selesai (Contoh: 12:00 / Selesai)</label>
                                    <input
                                        v-model="form.waktu_selesai"
                                        type="text"
                                        placeholder="12:00"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                    />
                                </div>
                            </div>

                            <!-- Lokasi & Penanggung Jawab -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tempat / Lokasi</label>
                                    <input
                                        v-model="form.lokasi"
                                        type="text"
                                        placeholder="Contoh: Ruang Multimedia / Lapangan Utama"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Penanggung Jawab (PJ)</label>
                                    <input
                                        v-model="form.penanggung_jawab"
                                        type="text"
                                        placeholder="Contoh: Waka Kurikulum / Pembina OSIS"
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                                    />
                                </div>
                            </div>

                            <!-- Isi / Rincian Kegiatan -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi / Rincian Agenda</label>
                                <textarea
                                    v-model="form.isi"
                                    rows="4"
                                    placeholder="Jelaskan instruksi, materi, atau rundown kegiatan..."
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition resize-y"
                                ></textarea>
                            </div>

                            <!-- Toggle Status Aktif -->
                            <div class="flex items-center justify-between p-3.5 bg-slate-50/80 rounded-xl border border-slate-200">
                                <div>
                                    <div class="text-xs font-semibold text-slate-800">Status Agenda</div>
                                    <div class="text-[11px] text-slate-500">Aktifkan agar agenda tampil di kalender pengguna</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
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
                                class="px-5 py-2 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-xs transition flex items-center gap-2"
                            >
                                <i v-if="form.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <span>{{ isEditMode ? 'Simpan Perubahan' : 'Jadwalkan Agenda' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── 5. MODAL DETAIL AGENDA (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showDetailModal && detailData"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                                <i class="bi bi-calendar-event"></i>
                            </span>
                            <h3 class="text-base font-bold text-slate-800">Detail Agenda Kegiatan</h3>
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
                            <span v-if="detailData.kategori" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ detailData.kategori }}
                            </span>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold border" :class="getTimelineStatus(detailData.tanggal_mulai, detailData.tanggal_selesai).class">
                                {{ getTimelineStatus(detailData.tanggal_mulai, detailData.tanggal_selesai).label }}
                            </span>
                        </div>

                        <h2 class="text-lg font-bold text-slate-800 leading-snug">
                            {{ detailData.nama_agenda_sekolah }}
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                            <div>
                                <span class="text-slate-400 block mb-0.5">Tanggal Pelaksanaan:</span>
                                <span class="font-semibold text-slate-700">{{ formatDateRange(detailData.tanggal_mulai, detailData.tanggal_selesai) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-0.5">Waktu:</span>
                                <span class="font-semibold text-slate-700">{{ detailData.waktu_mulai ? `${detailData.waktu_mulai} ${detailData.waktu_selesai ? '- ' + detailData.waktu_selesai : ''} WIB` : 'Tentatif' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-0.5">Tempat / Lokasi:</span>
                                <span class="font-semibold text-slate-700">{{ detailData.lokasi || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-0.5">Penanggung Jawab:</span>
                                <span class="font-semibold text-slate-700">{{ detailData.penanggung_jawab || '-' }}</span>
                            </div>
                        </div>

                        <div v-if="detailData.isi" class="p-4 bg-white rounded-xl border border-slate-200 text-xs text-slate-700 leading-relaxed whitespace-pre-wrap">
                            {{ detailData.isi }}
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

        <!-- ── 6. MODAL KONFIRMASI HAPUS (TELEPORT) ── -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal && itemToDelete"
                class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            >
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden p-6 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-2">Hapus Agenda Kegiatan?</h3>
                    <p class="text-xs text-slate-500 mb-6">
                        Agenda <span class="font-semibold text-slate-700">"{{ itemToDelete.nama_agenda_sekolah }}"</span> akan dihapus secara permanen dari sistem.
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
