<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'

// ─────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────
const props = defineProps({
    pengumumanList:     Object,
    agendaList:         Object,
    kategoriList:       Array,
    agendaKategoriList: Array,
    filters:            Object,
})

// ─────────────────────────────────────────────
// Tab Navigation
// ─────────────────────────────────────────────
const activeTab = ref('pengumuman')

// ─────────────────────────────────────────────
// Filter State — Pengumuman
// ─────────────────────────────────────────────
const searchQuery   = ref(props.filters?.search ?? '')
const filterKategori = ref(props.filters?.kategori_id ?? '')
const filterVisi    = ref(props.filters?.visibilitas ?? '')
const perPage       = ref(props.filters?.per_page ?? 15)

// ─────────────────────────────────────────────
// Filter State — Agenda
// ─────────────────────────────────────────────
const agendaSearch   = ref(props.filters?.agenda_search ?? '')
const agendaKategori = ref(props.filters?.agenda_kategori ?? '')
const agendaPerPage  = ref(props.filters?.agenda_per_page ?? 10)

// ─────────────────────────────────────────────
// Flash / Alert
// ─────────────────────────────────────────────
const flashSuccess = ref('')
const flashError   = ref('')

// ─────────────────────────────────────────────
// Modal State — Pengumuman
// ─────────────────────────────────────────────
const showPengumumanModal = ref(false)
const editingPengumuman   = ref(null)
const showDeletePModal    = ref(false)
const deletePengumumanId  = ref(null)

const pengumumanForm = useForm({
    judul:        '',
    deskripsi:    '',
    kategori_id:  '',
    visibilitas:  'public',
    target_roles: [],
    is_active:    true,
})

function openAddPengumuman() {
    editingPengumuman.value = null
    pengumumanForm.reset()
    pengumumanForm.visibilitas = 'public'
    pengumumanForm.is_active   = true
    showPengumumanModal.value  = true
}

function openEditPengumuman(item) {
    editingPengumuman.value   = item
    pengumumanForm.judul      = item.judul
    pengumumanForm.deskripsi  = item.deskripsi ?? ''
    pengumumanForm.kategori_id = item.kategori_id ?? ''
    pengumumanForm.visibilitas = item.visibilitas ?? 'public'
    pengumumanForm.target_roles = item.target_roles ?? []
    pengumumanForm.is_active   = item.is_active
    showPengumumanModal.value  = true
}

function submitPengumuman() {
    if (editingPengumuman.value) {
        pengumumanForm.put(`/cms/pengumuman/${editingPengumuman.value.id}`, {
            preserveScroll: true,
            onSuccess: () => { showPengumumanModal.value = false; flashSuccess.value = 'Pengumuman berhasil diperbarui.' },
            onError: () => { flashError.value = 'Gagal menyimpan pengumuman.' },
        })
    } else {
        pengumumanForm.post('/cms/pengumuman', {
            preserveScroll: true,
            onSuccess: () => { showPengumumanModal.value = false; flashSuccess.value = 'Pengumuman berhasil dipublikasikan.' },
            onError: () => { flashError.value = 'Gagal menyimpan pengumuman.' },
        })
    }
}

function confirmDeletePengumuman(id) {
    deletePengumumanId.value = id
    showDeletePModal.value   = true
}

function doDeletePengumuman() {
    router.delete(`/cms/pengumuman/${deletePengumumanId.value}`, {
        preserveScroll: true,
        onSuccess: () => { showDeletePModal.value = false; flashSuccess.value = 'Pengumuman berhasil dihapus.' },
    })
}

// ─────────────────────────────────────────────
// Modal State — Agenda
// ─────────────────────────────────────────────
const showAgendaModal   = ref(false)
const editingAgenda     = ref(null)
const showDeleteAModal  = ref(false)
const deleteAgendaId    = ref(null)

const agendaForm = useForm({
    nama_agenda_sekolah: '',
    kategori:            '',
    tanggal_mulai:       '',
    tanggal_selesai:     '',
    waktu_mulai:         '07:00',
    waktu_selesai:       '12:00',
    lokasi:              '',
    penanggung_jawab:    '',
    visibilitas:         'public',
    isi:                 '',
    is_active:           true,
})

function buildAgendaDeskripsi() {
    return JSON.stringify({
        isi:              agendaForm.isi,
        tanggal_mulai:    agendaForm.tanggal_mulai,
        tanggal_selesai:  agendaForm.tanggal_selesai,
        waktu_mulai:      agendaForm.waktu_mulai,
        waktu_selesai:    agendaForm.waktu_selesai,
        lokasi:           agendaForm.lokasi,
        penanggung_jawab: agendaForm.penanggung_jawab,
        visibilitas:      agendaForm.visibilitas,
        target_roles:     [],
    })
}

function openAddAgenda() {
    editingAgenda.value = null
    agendaForm.reset()
    agendaForm.visibilitas = 'public'
    agendaForm.is_active   = true
    agendaForm.waktu_mulai = '07:00'
    agendaForm.waktu_selesai = '12:00'
    showAgendaModal.value  = true
}

function openEditAgenda(item) {
    editingAgenda.value = item
    const d = item.detail ?? {}
    agendaForm.nama_agenda_sekolah = item.nama_agenda_sekolah
    agendaForm.kategori            = item.kategori ?? ''
    agendaForm.tanggal_mulai       = d.tanggal_mulai ?? ''
    agendaForm.tanggal_selesai     = d.tanggal_selesai ?? ''
    agendaForm.waktu_mulai         = d.waktu_mulai ?? '07:00'
    agendaForm.waktu_selesai       = d.waktu_selesai ?? '12:00'
    agendaForm.lokasi              = d.lokasi ?? ''
    agendaForm.penanggung_jawab    = d.penanggung_jawab ?? ''
    agendaForm.visibilitas         = d.visibilitas ?? 'public'
    agendaForm.isi                 = d.isi ?? ''
    agendaForm.is_active           = item.is_active
    showAgendaModal.value          = true
}

function submitAgenda() {
    const payload = {
        nama_agenda_sekolah: agendaForm.nama_agenda_sekolah,
        kategori:            agendaForm.kategori,
        deskripsi:           buildAgendaDeskripsi(),
        is_active:           agendaForm.is_active,
    }
    if (editingAgenda.value) {
        router.put(`/cms/agenda/${editingAgenda.value.id}`, payload, {
            preserveScroll: true,
            onSuccess: () => { showAgendaModal.value = false; flashSuccess.value = 'Agenda berhasil diperbarui.' },
        })
    } else {
        router.post('/cms/agenda', payload, {
            preserveScroll: true,
            onSuccess: () => { showAgendaModal.value = false; flashSuccess.value = 'Agenda berhasil ditambahkan.' },
        })
    }
}

function confirmDeleteAgenda(id) {
    deleteAgendaId.value  = id
    showDeleteAModal.value = true
}

function doDeleteAgenda() {
    router.delete(`/cms/agenda/${deleteAgendaId.value}`, {
        preserveScroll: true,
        onSuccess: () => { showDeleteAModal.value = false; flashSuccess.value = 'Agenda berhasil dihapus.' },
    })
}

// ─────────────────────────────────────────────
// Filter Helpers — Pengumuman
// ─────────────────────────────────────────────
function applyFilters() {
    router.get('/informasi/pengumuman', {
        search:      searchQuery.value,
        kategori_id: filterKategori.value,
        visibilitas: filterVisi.value,
        per_page:    perPage.value,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    searchQuery.value    = ''
    filterKategori.value = ''
    filterVisi.value     = ''
    perPage.value        = 15
    applyFilters()
}

// ─────────────────────────────────────────────
// Filter Helpers — Agenda
// ─────────────────────────────────────────────
function applyAgendaFilters() {
    router.get('/informasi/agenda', {
        agenda_search:   agendaSearch.value,
        agenda_kategori: agendaKategori.value,
        agenda_per_page: agendaPerPage.value,
    }, { preserveState: true, replace: true })
}

function resetAgendaFilters() {
    agendaSearch.value   = ''
    agendaKategori.value = ''
    agendaPerPage.value  = 10
    applyAgendaFilters()
}

// ─────────────────────────────────────────────
// Pagination
// ─────────────────────────────────────────────
function goToPage(url) {
    if (!url) return
    router.get(url, {}, { preserveState: true })
}

function getSmartPaginationLinks(pagination) {
    if (!pagination?.links || pagination.links.length === 0) return []
    const rawLinks = pagination.links
    const prevLink = rawLinks[0]
    const nextLink = rawLinks[rawLinks.length - 1]
    const pageLinks = rawLinks.slice(1, -1)
    const current = pagination.current_page || 1
    const last = pagination.last_page || 1
    const result = []
    result.push({ ...prevLink, isPrev: true, isNext: false })
    if (last <= 7) {
        pageLinks.forEach(l => result.push({ ...l, isPrev: false, isNext: false }))
    } else {
        const pagesToShow = new Set([1, last])
        for (let p = current - 1; p <= current + 1; p++) {
            if (p >= 1 && p <= last) pagesToShow.add(p)
        }
        const sorted = Array.from(pagesToShow).sort((a, b) => a - b)
        let prev = null
        sorted.forEach(p => {
            if (prev !== null && p - prev > 1) result.push({ label: '...', url: null, active: false, isPrev: false, isNext: false })
            const found = pageLinks.find(l => l.label == p.toString())
            result.push({ label: p.toString(), url: found?.url ?? null, active: p === current, isPrev: false, isNext: false })
            prev = p
        })
    }
    result.push({ ...nextLink, isPrev: false, isNext: true })
    return result
}

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────
const visibilitasBadge = {
    public: { label: 'Publik', cls: 'bg-emerald-50 text-emerald-700 border border-emerald-200' },
    guru:   { label: 'Guru',   cls: 'bg-blue-50 text-blue-700 border border-blue-200' },
    siswa:  { label: 'Siswa',  cls: 'bg-violet-50 text-violet-700 border border-violet-200' },
    tendik: { label: 'Tendik', cls: 'bg-amber-50 text-amber-700 border border-amber-200' },
}

function getVisibilitasBadge(v) {
    return visibilitasBadge[v] ?? { label: v, cls: 'bg-slate-100 text-slate-600' }
}

function formatDate(dt) {
    if (!dt) return '-'
    return new Date(dt).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function parseAgendaDetail(item) {
    try { return JSON.parse(item.deskripsi) } catch { return {} }
}

const agendaKategoriColors = {
    'Hari Libur & Peringatan': 'bg-rose-50 text-rose-700 border border-rose-200',
    'Kedinasan & Rapat':       'bg-blue-50 text-blue-700 border border-blue-200',
    'Ujian & Asesmen':         'bg-amber-50 text-amber-700 border border-amber-200',
    'Kegiatan Siswa':          'bg-violet-50 text-violet-700 border border-violet-200',
    'Akademik':                'bg-emerald-50 text-emerald-700 border border-emerald-200',
}
function getAgendaKategoriColor(k) {
    return agendaKategoriColors[k] ?? 'bg-slate-100 text-slate-600 border border-slate-200'
}

// drag scroll
function initDragScroll(el) {
    if (!el) return
    let isDown = false, startX = 0, scrollLeft = 0
    el.addEventListener('mousedown', e => { isDown = true; el.classList.add('cursor-grabbing'); startX = e.pageX - el.offsetLeft; scrollLeft = el.scrollLeft })
    el.addEventListener('mouseleave', () => { isDown = false; el.classList.remove('cursor-grabbing') })
    el.addEventListener('mouseup',    () => { isDown = false; el.classList.remove('cursor-grabbing') })
    el.addEventListener('mousemove',  e => { if (!isDown) return; e.preventDefault(); el.scrollLeft = scrollLeft - (e.pageX - el.offsetLeft - startX) })
    el.addEventListener('wheel', e => { e.preventDefault(); el.scrollLeft += e.deltaY * 1.5 }, { passive: false })
}
</script>

<template>
    <AppLayout title="Informasi & Pengumuman">
        <div class="space-y-5">

            <!-- Flash Alerts -->
            <div v-if="flashSuccess" class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-2xs">
                <i class="bi bi-check-circle-fill text-emerald-500 text-sm"></i>
                {{ flashSuccess }}
                <button @click="flashSuccess=''" class="ml-auto text-emerald-400 hover:text-emerald-700"><i class="bi bi-x-lg"></i></button>
            </div>
            <div v-if="flashError" class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold shadow-2xs">
                <i class="bi bi-exclamation-triangle-fill text-red-500 text-sm"></i>
                {{ flashError }}
                <button @click="flashError=''" class="ml-auto text-red-400 hover:text-red-700"><i class="bi bi-x-lg"></i></button>
            </div>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Informasi & Pengumuman</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola pengumuman resmi dan agenda kegiatan sekolah.</p>
                </div>
                <button
                    v-if="activeTab === 'pengumuman'"
                    @click="openAddPengumuman"
                    class="inline-flex items-center gap-1.5 h-9 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs shrink-0 cursor-pointer">
                    <i class="bi bi-plus-lg"></i> Tambah Pengumuman
                </button>
                <button
                    v-else
                    @click="openAddAgenda"
                    class="inline-flex items-center gap-1.5 h-9 px-4 bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white rounded-xl text-xs font-bold transition shadow-xs shrink-0 cursor-pointer">
                    <i class="bi bi-plus-lg"></i> Tambah Agenda
                </button>
            </div>

            <!-- NavTabs (3-way scroller) -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <button type="button"
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5"
                        @click="document.getElementById('cmsNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                        title="Geser ke Kiri">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul :ref="el => initDragScroll(el)"
                            class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap gap-1.5 px-1 select-none no-scrollbar"
                            id="cmsNavTabs" role="tablist"
                            style="scrollbar-width:none;">
                            <li>
                                <button
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer"
                                    :class="activeTab === 'pengumuman' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="activeTab = 'pengumuman'">
                                    <i class="bi bi-megaphone-fill text-sm"></i> Pengumuman Sekolah
                                    <span v-if="pengumumanList?.total" class="ml-1 px-1.5 py-0.5 rounded-full text-2xs font-black"
                                          :class="activeTab==='pengumuman' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-700'">
                                        {{ pengumumanList.total }}
                                    </span>
                                </button>
                            </li>
                            <li>
                                <button
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer"
                                    :class="activeTab === 'agenda' ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="activeTab = 'agenda'">
                                    <i class="bi bi-calendar-event-fill text-sm"></i> Agenda Sekolah
                                    <span v-if="agendaList?.total" class="ml-1 px-1.5 py-0.5 rounded-full text-2xs font-black"
                                          :class="activeTab==='agenda' ? 'bg-white/20 text-white' : 'bg-violet-100 text-violet-700'">
                                        {{ agendaList.total }}
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <button type="button"
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5"
                        @click="document.getElementById('cmsNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                        title="Geser ke Kanan">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════ -->
            <!-- TAB: PENGUMUMAN                   -->
            <!-- ══════════════════════════════════ -->
            <div v-show="activeTab === 'pengumuman'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">

                <!-- Filter Bar Atas -->
                <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
                    <form @submit.prevent="applyFilters" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">

                        <!-- Filter Kategori -->
                        <div class="w-44 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Kategori</label>
                            <select v-model="filterKategori" @change="applyFilters"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                                <option value="">-- Semua Kategori --</option>
                                <option v-for="k in kategoriList" :key="k.id" :value="k.id">{{ k.nama_kategori }}</option>
                            </select>
                        </div>

                        <!-- Filter Visibilitas -->
                        <div class="w-36 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Audiens</label>
                            <select v-model="filterVisi" @change="applyFilters"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                                <option value="">-- Semua --</option>
                                <option value="public">Publik</option>
                                <option value="guru">Guru</option>
                                <option value="siswa">Siswa</option>
                                <option value="tendik">Tendik</option>
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="w-64 sm:w-72 md:w-80 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Pencarian Judul</label>
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                <input type="text" v-model="searchQuery" @input="applyFilters" placeholder="Cari judul pengumuman..."
                                    class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs" />
                                <button v-if="searchQuery" @click="searchQuery=''; applyFilters()" type="button"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                    <i class="bi bi-x-circle-fill text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Tombol Cari & Reset -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="submit" class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                                <i class="bi bi-search text-xs"></i> Cari
                            </button>
                            <button type="button" @click="resetFilters"
                                class="h-9 px-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs transition shadow-2xs whitespace-nowrap cursor-pointer">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Pengumuman -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-center w-10">No</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-left">Judul Pengumuman</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-left">Kategori</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-center">Audiens</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-center">Status</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-center">Tanggal</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-3 py-3 text-center sticky right-0 bg-slate-50 shadow-[-4px_0_6px_rgba(15,23,42,0.04)]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!pengumumanList?.data?.length">
                                <td colspan="7" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-2 text-slate-400">
                                        <i class="bi bi-megaphone text-4xl opacity-30"></i>
                                        <span class="text-xs font-medium">Belum ada pengumuman</span>
                                        <button @click="openAddPengumuman" class="mt-1 text-xs text-blue-600 hover:underline font-bold cursor-pointer">+ Tambah Pengumuman Pertama</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="(item, idx) in pengumumanList?.data ?? []" :key="item.id"
                                class="border-b border-slate-100 hover:bg-blue-50/30 transition group">
                                <td class="px-4 py-3 text-center font-mono text-slate-400 w-10">
                                    {{ ((pengumumanList.current_page - 1) * pengumumanList.per_page) + idx + 1 }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-800 leading-snug line-clamp-2 max-w-xs">{{ item.judul }}</div>
                                    <div v-if="item.deskripsi" class="text-slate-400 text-2xs mt-0.5 line-clamp-1 max-w-xs">{{ item.deskripsi }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="item.kategori" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-2xs font-bold">
                                        <i class="bi bi-tag-fill text-slate-400 text-xs"></i>
                                        {{ item.kategori?.nama_kategori ?? '-' }}
                                    </span>
                                    <span v-else class="text-slate-300 text-2xs">—</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-2xs font-bold"
                                          :class="getVisibilitasBadge(item.visibilitas).cls">
                                        {{ getVisibilitasBadge(item.visibilitas).label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="item.is_active" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-2xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-2xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-slate-400 whitespace-nowrap">{{ formatDate(item.created_at) }}</td>
                                <td class="px-3 py-3 text-center sticky right-0 bg-white group-hover:bg-blue-50/30 shadow-[-4px_0_6px_rgba(15,23,42,0.04)]">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="openEditPengumuman(item)" title="Edit"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition cursor-pointer">
                                            <i class="bi bi-pencil-fill text-xs"></i>
                                        </button>
                                        <button @click="confirmDeletePengumuman(item.id)" title="Hapus"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition cursor-pointer">
                                            <i class="bi bi-trash-fill text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination Pengumuman -->
                <div v-if="pengumumanList?.total > 0"
                     class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
                        <span>Tampilkan</span>
                        <select v-model="perPage" @change="applyFilters"
                            class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span class="whitespace-nowrap">baris per halaman</span>
                        <span class="text-slate-300 hidden sm:inline">|</span>
                        <span class="whitespace-nowrap">
                            Menampilkan <span class="font-bold text-slate-800">{{ pengumumanList.from || 1 }}</span>
                            s.d. <span class="font-bold text-slate-800">{{ pengumumanList.to || pengumumanList.total }}</span>
                            dari <span class="font-bold text-slate-800">{{ pengumumanList.total }}</span> data
                        </span>
                    </div>
                    <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
                        <template v-for="(link, i) in getSmartPaginationLinks(pengumumanList)" :key="i">
                            <button v-if="link.url && !link.active" @click="goToPage(link.url)" type="button"
                                class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer">
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                                <span v-else>{{ link.label }}</span>
                            </button>
                            <span v-else-if="link.active"
                                class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-blue-600 text-white shadow-xs">
                                {{ link.label }}
                            </span>
                            <span v-else class="min-w-[32px] h-8 px-2 text-xs font-bold flex items-center justify-center text-slate-400">
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
                                <span v-else>{{ link.label }}</span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════ -->
            <!-- TAB: AGENDA SEKOLAH               -->
            <!-- ══════════════════════════════════ -->
            <div v-show="activeTab === 'agenda'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">

                <!-- Filter Bar Agenda -->
                <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
                    <form @submit.prevent="applyAgendaFilters" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
                        <!-- Kategori Agenda -->
                        <div class="w-48 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Kategori Agenda</label>
                            <select v-model="agendaKategori" @change="applyAgendaFilters"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition">
                                <option value="">-- Semua Kategori --</option>
                                <option v-for="k in agendaKategoriList" :key="k" :value="k">{{ k }}</option>
                            </select>
                        </div>
                        <!-- Search Agenda -->
                        <div class="w-64 sm:w-72 md:w-80 shrink-0">
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Pencarian</label>
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                <input type="text" v-model="agendaSearch" @input="applyAgendaFilters" placeholder="Cari nama agenda..."
                                    class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition shadow-2xs" />
                                <button v-if="agendaSearch" @click="agendaSearch=''; applyAgendaFilters()" type="button"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                    <i class="bi bi-x-circle-fill text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="submit" class="h-9 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                                <i class="bi bi-search text-xs"></i> Cari
                            </button>
                            <button type="button" @click="resetAgendaFilters"
                                class="h-9 px-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs transition shadow-2xs whitespace-nowrap cursor-pointer">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Agenda -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-center w-10">No</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-left">Nama Agenda</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-left">Kategori</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-left">Tanggal & Waktu</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-left">Lokasi</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-4 py-3 text-center">Status</th>
                                <th class="text-[10px] font-black text-slate-500 uppercase tracking-wider px-3 py-3 text-center sticky right-0 bg-slate-50 shadow-[-4px_0_6px_rgba(15,23,42,0.04)]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!agendaList?.data?.length">
                                <td colspan="7" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-2 text-slate-400">
                                        <i class="bi bi-calendar-x text-4xl opacity-30"></i>
                                        <span class="text-xs font-medium">Belum ada agenda sekolah</span>
                                        <button @click="openAddAgenda" class="mt-1 text-xs text-violet-600 hover:underline font-bold cursor-pointer">+ Tambah Agenda Pertama</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="(item, idx) in agendaList?.data ?? []" :key="item.id"
                                class="border-b border-slate-100 hover:bg-violet-50/30 transition group">
                                <td class="px-4 py-3 text-center font-mono text-slate-400 w-10">
                                    {{ ((agendaList.current_page - 1) * agendaList.per_page) + idx + 1 }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-800 leading-snug max-w-xs">{{ item.nama_agenda_sekolah }}</div>
                                    <div class="text-slate-400 text-2xs mt-0.5 line-clamp-1 max-w-xs">
                                        {{ parseAgendaDetail(item).penanggung_jawab ?? '' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-2xs font-bold"
                                          :class="getAgendaKategoriColor(item.kategori)">
                                        {{ item.kategori }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                    <div class="font-semibold">{{ parseAgendaDetail(item).tanggal_mulai ?? '-' }}</div>
                                    <div class="text-slate-400 text-2xs">
                                        {{ parseAgendaDetail(item).waktu_mulai }} – {{ parseAgendaDetail(item).waktu_selesai }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 max-w-[160px]">
                                    <div class="flex items-center gap-1">
                                        <i class="bi bi-geo-alt text-slate-400 shrink-0"></i>
                                        <span class="line-clamp-1">{{ parseAgendaDetail(item).lokasi ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="item.is_active" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-2xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-2xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center sticky right-0 bg-white group-hover:bg-violet-50/30 shadow-[-4px_0_6px_rgba(15,23,42,0.04)]">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="openEditAgenda(item)" title="Edit"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg bg-violet-50 text-violet-600 hover:bg-violet-100 transition cursor-pointer">
                                            <i class="bi bi-pencil-fill text-xs"></i>
                                        </button>
                                        <button @click="confirmDeleteAgenda(item.id)" title="Hapus"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition cursor-pointer">
                                            <i class="bi bi-trash-fill text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination Agenda -->
                <div v-if="agendaList?.total > 0"
                     class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
                        <span>Tampilkan</span>
                        <select v-model="agendaPerPage" @change="applyAgendaFilters"
                            class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none">
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                        </select>
                        <span class="whitespace-nowrap">baris per halaman</span>
                        <span class="text-slate-300 hidden sm:inline">|</span>
                        <span class="whitespace-nowrap">
                            Menampilkan <span class="font-bold text-slate-800">{{ agendaList.from || 1 }}</span>
                            s.d. <span class="font-bold text-slate-800">{{ agendaList.to || agendaList.total }}</span>
                            dari <span class="font-bold text-slate-800">{{ agendaList.total }}</span> data
                        </span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0 flex-wrap">
                        <template v-for="(link, i) in getSmartPaginationLinks(agendaList)" :key="i">
                            <button v-if="link.url && !link.active" @click="goToPage(link.url)" type="button"
                                class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer">
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                                <span v-else>{{ link.label }}</span>
                            </button>
                            <span v-else-if="link.active"
                                class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-violet-600 text-white shadow-xs">
                                {{ link.label }}
                            </span>
                            <span v-else class="min-w-[32px] h-8 px-2 text-xs flex items-center justify-center text-slate-400">
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
                                <span v-else>{{ link.label }}</span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

        </div><!-- end space-y-5 -->
    </AppLayout>


    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL: TAMBAH / EDIT PENGUMUMAN                          -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <div v-if="showPengumumanModal" class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="relative z-10 bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between text-white">
                    <div>
                        <h3 class="text-base font-bold flex items-center gap-2 m-0 text-white">
                            <i class="bi bi-megaphone-fill"></i>
                            {{ editingPengumuman ? 'Edit Pengumuman' : 'Tambah Pengumuman' }}
                        </h3>
                        <p class="text-xs text-blue-100 mb-0 mt-0.5">Publikasikan informasi resmi kepada seluruh warga sekolah.</p>
                    </div>
                    <button type="button" @click="showPengumumanModal = false" class="text-white/80 hover:text-white text-xl cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Body Form -->
                <form @submit.prevent="submitPengumuman" class="p-6 space-y-4">
                    <!-- Judul -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Judul Pengumuman <span class="text-red-500">*</span></label>
                        <input v-model="pengumumanForm.judul" type="text" placeholder="Masukkan judul pengumuman..."
                            class="w-full h-9 px-3 rounded-xl border text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                            :class="pengumumanForm.errors.judul ? 'border-red-400 bg-red-50' : 'border-slate-200'" />
                        <p v-if="pengumumanForm.errors.judul" class="text-2xs text-red-500 mt-1">{{ pengumumanForm.errors.judul }}</p>
                    </div>

                    <!-- Kategori & Visibilitas -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori</label>
                            <select v-model="pengumumanForm.kategori_id"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                                <option value="">-- Tanpa Kategori --</option>
                                <option v-for="k in kategoriList" :key="k.id" :value="k.id">{{ k.nama_kategori }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Audiens / Visibilitas <span class="text-red-500">*</span></label>
                            <select v-model="pengumumanForm.visibilitas"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                                <option value="public">🌐 Publik (Semua)</option>
                                <option value="guru">👨‍🏫 Guru & Tendik</option>
                                <option value="siswa">🎓 Siswa</option>
                                <option value="tendik">🏫 Tendik</option>
                            </select>
                        </div>
                    </div>

                    <!-- Isi / Deskripsi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Isi Pengumuman <span class="text-red-500">*</span></label>
                        <textarea v-model="pengumumanForm.deskripsi" rows="5" placeholder="Tuliskan isi pengumuman secara lengkap..."
                            class="w-full px-3 py-2.5 rounded-xl border text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition resize-none leading-relaxed"
                            :class="pengumumanForm.errors.deskripsi ? 'border-red-400 bg-red-50' : 'border-slate-200'"></textarea>
                        <p v-if="pengumumanForm.errors.deskripsi" class="text-2xs text-red-500 mt-1">{{ pengumumanForm.errors.deskripsi }}</p>
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="pengumumanForm.is_active" class="sr-only peer" />
                            <div class="w-10 h-5 bg-slate-200 peer-checked:bg-blue-600 rounded-full transition after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition after:duration-200 peer-checked:after:translate-x-5"></div>
                        </label>
                        <span class="text-xs font-semibold text-slate-700">Aktifkan / Publikasikan Pengumuman</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="showPengumumanModal = false"
                            class="h-9 px-4 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" :disabled="pengumumanForm.processing"
                            class="h-9 px-5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
                            <i class="bi" :class="pengumumanForm.processing ? 'bi-hourglass-split animate-spin' : 'bi-check2-circle'"></i>
                            <span>{{ pengumumanForm.processing ? 'Menyimpan...' : 'Simpan Pengumuman' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>


    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL: TAMBAH / EDIT AGENDA                              -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <div v-if="showAgendaModal" class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="relative z-10 bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="bg-gradient-to-r from-violet-600 to-purple-600 px-6 py-4 flex items-center justify-between text-white">
                    <div>
                        <h3 class="text-base font-bold flex items-center gap-2 m-0 text-white">
                            <i class="bi bi-calendar-event-fill"></i>
                            {{ editingAgenda ? 'Edit Agenda' : 'Tambah Agenda Sekolah' }}
                        </h3>
                        <p class="text-xs text-violet-100 mb-0 mt-0.5">Kelola jadwal kegiatan dan agenda sekolah.</p>
                    </div>
                    <button type="button" @click="showAgendaModal = false" class="text-white/80 hover:text-white text-xl cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Body Form -->
                <form @submit.prevent="submitAgenda" class="p-6 space-y-4">
                    <!-- Nama Agenda -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Agenda <span class="text-red-500">*</span></label>
                        <input v-model="agendaForm.nama_agenda_sekolah" type="text" placeholder="Nama kegiatan / agenda..."
                            class="w-full h-9 px-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition" />
                    </div>

                    <!-- Kategori & Visibilitas -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori Agenda <span class="text-red-500">*</span></label>
                            <input v-model="agendaForm.kategori" type="text" placeholder="Ujian & Asesmen, Hari Libur, dst..."
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition"
                                list="agenda-kategori-list" />
                            <datalist id="agenda-kategori-list">
                                <option v-for="k in agendaKategoriList" :key="k" :value="k" />
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Visibilitas</label>
                            <select v-model="agendaForm.visibilitas"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition">
                                <option value="public">🌐 Publik</option>
                                <option value="guru">👨‍🏫 Guru</option>
                                <option value="siswa">🎓 Siswa</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tanggal & Waktu -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Mulai</label>
                            <input v-model="agendaForm.tanggal_mulai" type="date"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Selesai</label>
                            <input v-model="agendaForm.tanggal_selesai" type="date"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Waktu Mulai</label>
                            <input v-model="agendaForm.waktu_mulai" type="time"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Waktu Selesai</label>
                            <input v-model="agendaForm.waktu_selesai" type="time"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition" />
                        </div>
                    </div>

                    <!-- Lokasi & Penanggung Jawab -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Lokasi</label>
                            <input v-model="agendaForm.lokasi" type="text" placeholder="Ruang kelas, lapangan, dll..."
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Penanggung Jawab</label>
                            <input v-model="agendaForm.penanggung_jawab" type="text" placeholder="Nama / Jabatan..."
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition" />
                        </div>
                    </div>

                    <!-- Isi / Keterangan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Keterangan / Isi Agenda</label>
                        <textarea v-model="agendaForm.isi" rows="3" placeholder="Keterangan kegiatan, ketentuan peserta, dll..."
                            class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition resize-none leading-relaxed"></textarea>
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="agendaForm.is_active" class="sr-only peer" />
                            <div class="w-10 h-5 bg-slate-200 peer-checked:bg-violet-600 rounded-full transition after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition after:duration-200 peer-checked:after:translate-x-5"></div>
                        </label>
                        <span class="text-xs font-semibold text-slate-700">Aktifkan Agenda</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="showAgendaModal = false"
                            class="h-9 px-4 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="h-9 px-5 bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                            <i class="bi bi-check2-circle"></i>
                            <span>{{ editingAgenda ? 'Perbarui Agenda' : 'Simpan Agenda' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>


    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL: KONFIRMASI HAPUS PENGUMUMAN                       -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <div v-if="showDeletePModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="relative z-10 bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="p-6 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-trash3-fill text-2xl text-red-500"></i>
                    </div>
                    <h3 class="font-black text-slate-800 text-base mb-1">Hapus Pengumuman?</h3>
                    <p class="text-xs text-slate-500">Pengumuman yang dihapus tidak dapat dipulihkan kembali.</p>
                </div>
                <div class="flex items-center gap-2 px-6 pb-6">
                    <button @click="showDeletePModal = false" class="flex-1 h-9 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button @click="doDeletePengumuman" class="flex-1 h-9 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5">
                        <i class="bi bi-trash3-fill"></i> Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </Teleport>


    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL: KONFIRMASI HAPUS AGENDA                           -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <div v-if="showDeleteAModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="relative z-10 bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="p-6 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-calendar-x-fill text-2xl text-red-500"></i>
                    </div>
                    <h3 class="font-black text-slate-800 text-base mb-1">Hapus Agenda?</h3>
                    <p class="text-xs text-slate-500">Agenda yang dihapus tidak dapat dipulihkan kembali.</p>
                </div>
                <div class="flex items-center gap-2 px-6 pb-6">
                    <button @click="showDeleteAModal = false" class="flex-1 h-9 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button @click="doDeleteAgenda" class="flex-1 h-9 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5">
                        <i class="bi bi-trash3-fill"></i> Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

</template>
