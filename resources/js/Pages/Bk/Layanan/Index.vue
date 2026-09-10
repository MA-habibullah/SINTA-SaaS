<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
    konselingList: Object,
    kpi: Object,
    kategoriBreakdown: Array,
    monthlyTrend: Array,
    tenantInfo: Object,
    isSuperAdmin: Boolean,
    tenants: Array,
    tahunAjaranList: Array,
    selectedTahunAjaran: String,
    filters: Object,
})

const page = usePage()

// Tab State
const activeTab = ref('jurnal_konseling')

// Filter State
const search = ref(props.filters?.search || '')
const tenantId = ref(props.filters?.tenant_id || '')
const tahunAjaran = ref(props.filters?.tahun_ajaran || '')
const jenisKonseling = ref(props.filters?.jenis_konseling || '')
const statusKasus = ref(props.filters?.status_kasus || '')
const isRahasia = ref(props.filters?.is_rahasia !== undefined && props.filters?.is_rahasia !== null ? String(props.filters?.is_rahasia) : '')
const startDate = ref(props.filters?.start_date || '')
const endDate = ref(props.filters?.end_date || '')
const perPage = ref(props.filters?.per_page || 15)

// Helper: dapatkan nama sekolah yang dipilih
const getSelectedTenantName = () => {
    if (!tenantId.value) return 'Semua Sekolah (Global)'
    const t = (props.tenants || []).find(t => t.id === tenantId.value)
    return t ? t.nama_sekolah : 'Sekolah Terpilih'
}

// Modals State
const showModalRecord = ref(false)
const isEditingRecord = ref(false)
const selectedRecordId = ref(null)

const showModalDetail = ref(false)
const detailKonseling = ref(null)

const showModalCetak = ref(false)
const cetakData = ref(null)

// Live Student Search inside Record Modal
const siswaSearchQuery = ref('')
const siswaSearchResults = ref([])
const isSearchingSiswa = ref(false)
const selectedSiswa = ref(null)
let searchDebounceTimer = null
let filterSearchDebounce = null

// Form State
const recordForm = useForm({
    siswa_id: '',
    tanggal_konseling: new Date().toISOString().slice(0, 10),
    jenis_konseling: 'Pribadi',
    topik_masalah: '',
    ringkasan_konseling: '',
    solusi_tindak_lanjut: '',
    status_kasus: 'Terbuka',
    is_rahasia: false,
    guru_bk_nama: page.props.auth?.user?.name || 'Guru Bimbingan Konseling',
    foto_bukti: null,
})

// Filter actions
const applyFilters = () => {
    router.get(
        '/bk/layanan',
        {
            search: search.value || undefined,
            tenant_id: tenantId.value || undefined,
            tahun_ajaran: tahunAjaran.value || undefined,
            jenis_konseling: jenisKonseling.value || undefined,
            status_kasus: statusKasus.value || undefined,
            is_rahasia: isRahasia.value !== '' ? isRahasia.value : undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true }
    )
}

const handleSearchInput = () => {
    clearTimeout(filterSearchDebounce)
    filterSearchDebounce = setTimeout(() => {
        applyFilters()
    }, 400)
}

const resetFilters = () => {
    search.value = ''
    tenantId.value = ''
    tahunAjaran.value = ''
    jenisKonseling.value = ''
    statusKasus.value = ''
    isRahasia.value = ''
    startDate.value = ''
    endDate.value = ''
    perPage.value = 15
    applyFilters()
}

// Student Autocomplete Search
const onSiswaSearchInput = () => {
    clearTimeout(searchDebounceTimer)
    const query = siswaSearchQuery.value.trim()
    if (query.length < 2) {
        siswaSearchResults.value = []
        return
    }

    isSearchingSiswa.value = true
    searchDebounceTimer = setTimeout(async () => {
        try {
            const tenantParam = tenantId.value ? `&tenant_id=${encodeURIComponent(tenantId.value)}` : ''
            const res = await fetch(`/bk/search-siswa?q=${encodeURIComponent(query)}${tenantParam}`)
            const json = await res.json()
            if (json.success) {
                siswaSearchResults.value = json.data || []
            }
        } catch (e) {
            console.error('Gagal memuat data siswa:', e)
        } finally {
            isSearchingSiswa.value = false
        }
    }, 250)
}

const selectSiswa = (siswa) => {
    selectedSiswa.value = siswa
    recordForm.siswa_id = siswa.id
    siswaSearchQuery.value = `${siswa.nama_lengkap} (${siswa.nisn || siswa.nis || '-'})`
    siswaSearchResults.value = []
}

const clearSelectedSiswa = () => {
    selectedSiswa.value = null
    recordForm.siswa_id = ''
    siswaSearchQuery.value = ''
    siswaSearchResults.value = []
}

// Modal Openers
const openAddModal = () => {
    isEditingRecord.value = false
    selectedRecordId.value = null
    selectedSiswa.value = null
    siswaSearchQuery.value = ''
    siswaSearchResults.value = []

    recordForm.reset()
    recordForm.tanggal_konseling = new Date().toISOString().slice(0, 10)
    recordForm.jenis_konseling = 'Pribadi'
    recordForm.status_kasus = 'Terbuka'
    recordForm.is_rahasia = false
    recordForm.guru_bk_nama = page.props.auth?.user?.name || 'Guru Bimbingan Konseling'
    recordForm.foto_bukti = null

    showModalRecord.value = true
}

const openEditModal = (item) => {
    isEditingRecord.value = true
    selectedRecordId.value = item.id

    selectedSiswa.value = {
        id: item.siswa_id,
        nama_lengkap: item.snapshot_nama_siswa || item.siswa?.nama_lengkap || 'Siswa',
        nisn: item.snapshot_nisn || item.siswa?.nisn || '-',
        nis: item.snapshot_nis || item.siswa?.nis || '-',
        nama_kelas: item.snapshot_nama_kelas || 'Kelas Siswa',
    }
    siswaSearchQuery.value = `${selectedSiswa.value.nama_lengkap} (${selectedSiswa.value.nisn})`

    recordForm.siswa_id = item.siswa_id
    recordForm.tanggal_konseling = item.tanggal_konseling ? item.tanggal_konseling.slice(0, 10) : new Date().toISOString().slice(0, 10)
    recordForm.jenis_konseling = item.jenis_konseling || 'Pribadi'
    recordForm.topik_masalah = item.topik_masalah || ''
    recordForm.ringkasan_konseling = item.ringkasan_konseling || ''
    recordForm.solusi_tindak_lanjut = item.solusi_tindak_lanjut || ''
    recordForm.status_kasus = item.status_kasus || 'Terbuka'
    recordForm.is_rahasia = Boolean(item.is_rahasia)
    recordForm.guru_bk_nama = item.guru_bk_nama || page.props.auth?.user?.name || 'Guru Bimbingan Konseling'
    recordForm.foto_bukti = null

    showModalRecord.value = true
}

const openDetailModal = (item) => {
    detailKonseling.value = item
    showModalDetail.value = true
}

const openCetakModal = (item) => {
    cetakData.value = item
    showModalCetak.value = true
}

// Form Submissions
const submitRecord = () => {
    if (isEditingRecord.value) {
        recordForm.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(`/bk/konseling/${selectedRecordId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                showModalRecord.value = false
                recordForm.reset()
            },
        })
    } else {
        recordForm.post('/bk/konseling', {
            preserveScroll: true,
            onSuccess: () => {
                showModalRecord.value = false
                recordForm.reset()
            },
        })
    }
}

const deleteRecord = (item) => {
    if (confirm(`Apakah Anda yakin ingin menghapus catatan konseling topik "${item.topik_masalah}" untuk ${item.snapshot_nama_siswa || 'siswa'}?`)) {
        router.delete(`/bk/konseling/${item.id}`, {
            preserveScroll: true,
        })
    }
}

const updateStatusQuick = async (item, newStatus) => {
    try {
        const res = await fetch(`/bk/konseling/${item.id}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ status_kasus: newStatus }),
        })
        const json = await res.json()
        if (json.success) {
            item.status_kasus = newStatus
        }
    } catch (e) {
        console.error('Gagal update status:', e)
        applyFilters()
    }
}

const handleFileUpload = (e) => {
    const file = e.target.files[0]
    if (file) {
        recordForm.foto_bukti = file
    }
}

const printDocument = () => {
    window.print()
}

// Formatters & Helpers
const formatDate = (dateString) => {
    if (!dateString) return '-'
    const d = new Date(dateString)
    if (isNaN(d.getTime())) return dateString
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const formatDateLong = (dateString) => {
    if (!dateString) return '-'
    const d = new Date(dateString)
    if (isNaN(d.getTime())) return dateString
    return d.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    })
}

const getJenisBadgeClass = (jenis) => {
    switch (jenis) {
        case 'Pribadi':
            return 'bg-purple-50 text-purple-700 border-purple-200/80 ring-1 ring-purple-400/20'
        case 'Sosial':
            return 'bg-blue-50 text-blue-700 border-blue-200/80 ring-1 ring-blue-400/20'
        case 'Belajar':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200/80 ring-1 ring-emerald-400/20'
        case 'Karier':
            return 'bg-amber-50 text-amber-700 border-amber-200/80 ring-1 ring-amber-400/20'
        case 'Kedisiplinan':
            return 'bg-rose-50 text-rose-700 border-rose-200/80 ring-1 ring-rose-400/20'
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200'
    }
}

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'Terbuka':
            return 'bg-amber-50 text-amber-700 border-amber-200/80 ring-1 ring-amber-400/20'
        case 'Dalam Pendampingan':
            return 'bg-blue-50 text-blue-700 border-blue-200/80 ring-1 ring-blue-400/20'
        case 'Selesai':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200/80 ring-1 ring-emerald-400/20'
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200'
    }
}

// Smart Windowed Pagination Helper
const getSmartPaginationLinks = (paginationData) => {
    if (!paginationData || !paginationData.links) return []
    const links = paginationData.links
    const current = paginationData.current_page || 1
    const last = paginationData.last_page || 1

    return links.map((link, index) => {
        const isPrev = index === 0
        const isNext = index === links.length - 1
        return {
            ...link,
            isPrev,
            isNext,
        }
    }).filter((link, index) => {
        if (index === 0 || index === links.length - 1) return true
        const pageNum = parseInt(link.label)
        if (isNaN(pageNum)) return true
        if (pageNum === 1 || pageNum === last) return true
        if (Math.abs(pageNum - current) <= 2) return true
        return false
    })
}

const goToPage = (url) => {
    if (url) {
        router.visit(url, { preserveState: true, preserveScroll: true })
    }
}

// 3-Way Scroller NavTabs Interaction
onMounted(() => {
    const scroller = document.getElementById('bkLayananNavTabs')
    if (scroller) {
        let isDown = false
        let startX, scrollLeft

        scroller.addEventListener('wheel', (e) => {
            if (e.deltaY !== 0) {
                e.preventDefault()
                scroller.scrollLeft += e.deltaY * 0.9
            }
        }, { passive: false })

        scroller.addEventListener('mousedown', (e) => {
            isDown = true
            startX = e.pageX - scroller.offsetLeft
            scrollLeft = scroller.scrollLeft
        })
        scroller.addEventListener('mouseleave', () => { isDown = false })
        scroller.addEventListener('mouseup', () => { isDown = false })
        scroller.addEventListener('mousemove', (e) => {
            if (!isDown) return
            e.preventDefault()
            const x = e.pageX - scroller.offsetLeft
            const walk = (x - startX) * 1.5
            scroller.scrollLeft = scrollLeft - walk
        })
    }
})
</script>

<template>
    <AppLayout title="Layanan Bimbingan Konseling (BK)">
        <div class="space-y-6 pb-12">
            <!-- Header Halaman & Breadcrumb -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-5 rounded-3xl border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-white shadow-md shadow-blue-500/20">
                            <i class="bi bi-chat-heart text-xl"></i>
                        </span>
                        <div>
                            <h1 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight">
                                Layanan Bimbingan & Konseling (BK)
                            </h1>
                            <p class="text-xs md:text-sm text-slate-500 font-medium">
                                Manajemen pencatatan konseling siswa, pendampingan kepribadian, karier, belajar, dan penanganan kasus terpadu.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs md:text-sm font-semibold rounded-2xl shadow-sm shadow-blue-600/30 transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0"
                        @click="openAddModal"
                    >
                        <i class="bi bi-plus-circle text-base"></i>
                        <span>Catat Konseling Baru</span>
                    </button>
                    <a
                        href="/bk/kedisiplinan"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 text-xs md:text-sm font-semibold rounded-2xl border border-slate-200/80 shadow-2xs transition-all"
                    >
                        <i class="bi bi-shield-check text-base text-amber-500"></i>
                        <span>Tata Tertib & Poin</span>
                    </a>
                </div>
            </div>

            <!-- ===== BANNER FILTER SEKOLAH (KHUSUS SUPER ADMIN) STANDAR BAKU ===== -->
            <div v-if="isSuperAdmin" class="p-4 sm:px-5 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2.5">
                    <i class="bi bi-buildings text-blue-600 text-lg"></i>
                    <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
                        <i class="bi bi-funnel-fill me-1"></i> Aktif
                    </span>

                    <!-- Dropdown Pilih Sekolah -->
                    <div class="my-1 md:my-0">
                        <select
                            v-model="tenantId"
                            @change="applyFilters"
                            id="layanan-tenant-selector"
                            class="h-9 px-3 bg-white border border-blue-200 rounded-xl text-xs font-semibold text-slate-800 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[240px] cursor-pointer"
                        >
                            <option value="">-- Semua Sekolah (Global) --</option>
                            <option v-for="t in tenants || []" :key="t.id" :value="t.id">
                                {{ t.nama_sekolah }}
                            </option>
                        </select>
                    </div>

                    <!-- Dropdown Tahun Ajaran -->
                    <div class="my-1 md:my-0">
                        <select
                            v-model="tahunAjaran"
                            @change="applyFilters"
                            id="layanan-tahun-ajaran-selector"
                            class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer"
                        >
                            <option value="">-- Semua Tahun Ajaran --</option>
                            <option v-for="ta in tahunAjaranList || []" :key="ta.id" :value="ta.nama_tahun_ajaran">
                                {{ ta.nama_tahun_ajaran }} {{ ta.is_active ? '(Aktif)' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Tombol Reset -->
                    <button
                        v-if="tenantId || tahunAjaran"
                        type="button"
                        @click="() => { tenantId = ''; tahunAjaran = ''; applyFilters() }"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-800 text-[11px] font-semibold border border-slate-200 transition"
                        title="Reset Filter Sekolah"
                    >
                        <i class="bi bi-x-circle text-[10px]"></i> Reset
                    </button>
                </div>

                <!-- Info Text -->
                <div class="text-xs text-slate-500 font-medium whitespace-nowrap">
                    Menampilkan data milik:
                    <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
                    <span class="text-slate-400 ml-1">(Super Admin)</span>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <!-- 1 Tombol Panah Kiri -->
                    <button 
                        type="button" 
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                        onclick="document.getElementById('bkLayananNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                        title="Geser ke Kiri"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <!-- Container Deretan Tab -->
                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="bkLayananNavTabs" role="tablist">
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs md:text-sm transition flex items-center" 
                                    :class="activeTab === 'jurnal_konseling' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="activeTab = 'jurnal_konseling'"
                                >
                                    <i class="bi bi-journal-text me-2 text-base"></i> Jurnal & Sesi Konseling
                                </button>
                            </li>
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs md:text-sm transition flex items-center" 
                                    :class="activeTab === 'kategori_kasus' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="activeTab = 'kategori_kasus'"
                                >
                                    <i class="bi bi-folder2-open me-2 text-base"></i> Kategori & Kasus Masalah
                                </button>
                            </li>
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs md:text-sm transition flex items-center" 
                                    :class="activeTab === 'statistik_tren' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="activeTab = 'statistik_tren'"
                                >
                                    <i class="bi bi-graph-up-arrow me-2 text-base"></i> Statistik & Tren Layanan
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- 1 Tombol Panah Kanan -->
                    <button 
                        type="button" 
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                        onclick="document.getElementById('bkLayananNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                        title="Geser ke Kanan"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- 6 KPI Metric Cards Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
                <!-- Card 1: Total Konseling -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition-all relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 text-indigo-500/10 group-hover:text-indigo-500/15 transition-all text-5xl">
                        <i class="bi bi-chat-quote-fill"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                            <i class="bi bi-chat-dots-fill"></i>
                        </span>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Sesi</span>
                    </div>
                    <div class="text-2xl font-black text-slate-800 tracking-tight">
                        {{ kpi?.total_konseling || 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1 font-medium">Sesi Konseling Siswa</div>
                </div>

                <!-- Card 2: Kasus Terbuka -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition-all relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 text-amber-500/10 group-hover:text-amber-500/15 transition-all text-5xl">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                            <i class="bi bi-hourglass-split"></i>
                        </span>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Terbuka</span>
                    </div>
                    <div class="text-2xl font-black text-amber-600 tracking-tight">
                        {{ kpi?.kasus_terbuka || 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1 font-medium">Menunggu Tindak Lanjut</div>
                </div>

                <!-- Card 3: Dalam Pendampingan -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition-all relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 text-blue-500/10 group-hover:text-blue-500/15 transition-all text-5xl">
                        <i class="bi bi-person-walking"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </span>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Pendampingan</span>
                    </div>
                    <div class="text-2xl font-black text-blue-600 tracking-tight">
                        {{ kpi?.dalam_pendampingan || 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1 font-medium">Sedang Berjalan</div>
                </div>

                <!-- Card 4: Selesai -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition-all relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 text-emerald-500/10 group-hover:text-emerald-500/15 transition-all text-5xl">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="bi bi-patch-check-fill"></i>
                        </span>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Selesai</span>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 tracking-tight">
                        {{ kpi?.kasus_selesai || 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1 font-medium">Kasus Teratasi</div>
                </div>

                <!-- Card 5: Rahasia / Privat -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition-all relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 text-purple-500/10 group-hover:text-purple-500/15 transition-all text-5xl">
                        <i class="bi bi-incognito"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Rahasia</span>
                    </div>
                    <div class="text-2xl font-black text-purple-600 tracking-tight">
                        {{ kpi?.konseling_rahasia || 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1 font-medium">Sesi Konfidensial</div>
                </div>

                <!-- Card 6: Siswa Dibina -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition-all relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 text-sky-500/10 group-hover:text-sky-500/15 transition-all text-5xl">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm">
                            <i class="bi bi-mortarboard-fill"></i>
                        </span>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Siswa Terbina</span>
                    </div>
                    <div class="text-2xl font-black text-sky-600 tracking-tight">
                        {{ kpi?.total_siswa_dibina || 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1 font-medium">Siswa Terdata</div>
                </div>
            </div>

            <!-- TAB 1: JURNAL & SESI KONSELING (STANDARD 3-PART DATA TABLE) -->
            <div v-show="activeTab === 'jurnal_konseling'" class="space-y-4">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <!-- 1. Bagian Atas: Filter Bar Sesuai Standar Baku AGENTS.MD -->
                    <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
                        <form @submit.prevent="applyFilters" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
                            <!-- Filter Jenis Konseling -->
                            <div class="w-36 sm:w-40 shrink-0">
                                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Bidang Layanan</label>
                                <select
                                    v-model="jenisKonseling"
                                    @change="applyFilters"
                                    class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                                >
                                    <option value="">Semua Bidang</option>
                                    <option value="Pribadi">Pribadi</option>
                                    <option value="Sosial">Sosial</option>
                                    <option value="Belajar">Belajar</option>
                                    <option value="Karier">Karier</option>
                                    <option value="Kedisiplinan">Kedisiplinan</option>
                                </select>
                            </div>

                            <!-- Filter Status Kasus -->
                            <div class="w-36 sm:w-40 shrink-0">
                                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Status Kasus</label>
                                <select
                                    v-model="statusKasus"
                                    @change="applyFilters"
                                    class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="Terbuka">Terbuka</option>
                                    <option value="Dalam Pendampingan">Pendampingan</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>

                            <!-- Filter Kerahasiaan -->
                            <div class="w-36 sm:w-40 shrink-0">
                                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Sifat Kasus</label>
                                <select
                                    v-model="isRahasia"
                                    @change="applyFilters"
                                    class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                                >
                                    <option value="">Semua Sifat</option>
                                    <option value="0">Terbuka / Publik</option>
                                    <option value="1">Rahasia / Konfidensial</option>
                                </select>
                            </div>

                            <!-- Rentang Tanggal -->
                            <div class="shrink-0 flex items-end gap-1.5">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Dari Tanggal</label>
                                    <input
                                        v-model="startDate"
                                        type="date"
                                        @change="applyFilters"
                                        class="h-9 px-2.5 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs"
                                    />
                                </div>
                                <span class="text-slate-400 mb-2 font-bold">-</span>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Sampai</label>
                                    <input
                                        v-model="endDate"
                                        type="date"
                                        @change="applyFilters"
                                        class="h-9 px-2.5 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs"
                                    />
                                </div>
                            </div>

                            <!-- Search Input (Proposional w-64 s.d. w-80) -->
                            <div class="w-64 sm:w-72 md:w-80 shrink-0">
                                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Pencarian</label>
                                <div class="relative">
                                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Cari nama siswa, NISN, topik, guru BK..."
                                        class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs"
                                        @input="handleSearchInput"
                                        @keyup.enter="applyFilters"
                                    />
                                    <button
                                        v-if="search"
                                        type="button"
                                        @click="search = ''; applyFilters()"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
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
                                    class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap"
                                >
                                    <i class="bi bi-search text-xs"></i> <span>Cari</span>
                                </button>
                                <button
                                    type="button"
                                    @click="resetFilters"
                                    class="h-9 px-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs transition shadow-2xs whitespace-nowrap"
                                    title="Reset Filter"
                                >
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- 2. Bagian Tengah: Tabel Data Tabular -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4 min-w-[200px]">Identitas Siswa</th>
                                    <th class="py-3 px-4 min-w-[130px]">Waktu & Bidang</th>
                                    <th class="py-3 px-4 min-w-[220px]">Topik Masalah & Ringkasan</th>
                                    <th class="py-3 px-4 min-w-[150px]">Solusi / Rencana Lanjut</th>
                                    <th class="py-3 px-4 min-w-[130px]">Konselor BK</th>
                                    <th class="py-3 px-4 w-32 text-center">Status Kasus</th>
                                    <th class="py-3 px-4 w-32 text-center sticky right-0 bg-slate-50 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] z-10">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(item, idx) in konselingList?.data || []"
                                    :key="item.id"
                                    class="hover:bg-blue-50/40 transition-colors group"
                                >
                                    <!-- No -->
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400">
                                        {{ ((konselingList?.current_page || 1) - 1) * (konselingList?.per_page || 15) + idx + 1 }}
                                    </td>

                                    <!-- Identitas Siswa -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                                                {{ (item.snapshot_nama_siswa || item.siswa?.nama_lengkap || 'S')[0] }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-800 truncate group-hover:text-blue-600 transition-colors">
                                                    {{ item.snapshot_nama_siswa || item.siswa?.nama_lengkap || '-' }}
                                                </div>
                                                <div class="text-[11px] text-slate-400 flex items-center gap-1.5 flex-wrap">
                                                    <span>NISN: {{ item.snapshot_nisn || item.siswa?.nisn || '-' }}</span>
                                                    <span>•</span>
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold text-[10px]">
                                                        {{ item.snapshot_nama_kelas || 'Kelas Siswa' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Waktu & Bidang -->
                                    <td class="py-3.5 px-4">
                                        <div class="space-y-1">
                                            <div class="font-semibold text-slate-700 flex items-center gap-1 text-xs">
                                                <i class="bi bi-calendar-event text-slate-400"></i>
                                                {{ formatDate(item.tanggal_konseling) }}
                                            </div>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                                                :class="getJenisBadgeClass(item.jenis_konseling)"
                                            >
                                                {{ item.jenis_konseling || 'Umum' }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Topik & Ringkasan -->
                                    <td class="py-3.5 px-4">
                                        <div class="space-y-1 max-w-xs">
                                            <div class="font-bold text-slate-800 line-clamp-1 flex items-center gap-1.5">
                                                <span>{{ item.topik_masalah || '-' }}</span>
                                                <span
                                                    v-if="item.is_rahasia"
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 font-bold text-[10px] tracking-wide"
                                                    title="Sesi Rahasia / Konfidensial"
                                                >
                                                    <i class="bi bi-shield-lock-fill me-0.5"></i> RAHASIA
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                                {{ item.ringkasan_konseling || item.catatan || 'Tidak ada deskripsi detail.' }}
                                            </p>
                                        </div>
                                    </td>

                                    <!-- Solusi / Rencana Lanjut -->
                                    <td class="py-3.5 px-4">
                                        <div class="text-xs text-slate-600 line-clamp-2 leading-relaxed max-w-xs">
                                            {{ item.solusi_tindak_lanjut || item.tindak_lanjut || '-' }}
                                        </div>
                                    </td>

                                    <!-- Guru BK -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-medium text-slate-700 text-xs flex items-center gap-1.5">
                                            <i class="bi bi-person-badge text-blue-500"></i>
                                            <span class="truncate">{{ item.guru_bk_nama || 'Guru BK' }}</span>
                                        </div>
                                    </td>

                                    <!-- Status Kasus with Dropdown Quick Action -->
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <select
                                                :value="item.status_kasus"
                                                class="px-2 py-1 text-[11px] font-bold rounded-xl border focus:outline-none transition cursor-pointer"
                                                :class="getStatusBadgeClass(item.status_kasus)"
                                                @change="updateStatusQuick(item, $event.target.value)"
                                            >
                                                <option value="Terbuka">Terbuka</option>
                                                <option value="Dalam Pendampingan">Pendampingan</option>
                                                <option value="Selesai">Selesai</option>
                                            </select>
                                        </div>
                                    </td>

                                    <!-- Aksi (Sticky Right) -->
                                    <td class="py-3.5 px-4 text-center sticky right-0 bg-white group-hover:bg-blue-50/90 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] z-10">
                                        <div class="flex items-center justify-center gap-1">
                                            <!-- Detail Button -->
                                            <button
                                                type="button"
                                                class="w-7 h-7 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 flex items-center justify-center text-xs transition"
                                                title="Lihat Detail Sesi"
                                                @click="openDetailModal(item)"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            <!-- Cetak Lembar BK Button -->
                                            <button
                                                type="button"
                                                class="w-7 h-7 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-600 flex items-center justify-center text-xs transition"
                                                title="Cetak Lembar Pelayanan Konseling"
                                                @click="openCetakModal(item)"
                                            >
                                                <i class="bi bi-printer"></i>
                                            </button>

                                            <!-- Edit Button -->
                                            <button
                                                type="button"
                                                class="w-7 h-7 rounded-xl bg-slate-100 hover:bg-amber-50 text-slate-600 hover:text-amber-600 flex items-center justify-center text-xs transition"
                                                title="Edit Catatan"
                                                @click="openEditModal(item)"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button
                                                type="button"
                                                class="w-7 h-7 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 flex items-center justify-center text-xs transition"
                                                title="Hapus Catatan"
                                                @click="deleteRecord(item)"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!konselingList?.data?.length">
                                    <td colspan="8" class="py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl">
                                                <i class="bi bi-journal-x"></i>
                                            </div>
                                            <div class="font-bold text-slate-700">Belum Ada Sesi Konseling</div>
                                            <p class="text-xs text-slate-400 max-w-sm">
                                                Tidak ditemukan catatan konseling siswa dengan filter pencarian yang diterapkan saat ini.
                                            </p>
                                            <button
                                                type="button"
                                                class="mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-2xl transition shadow-xs flex items-center gap-1.5"
                                                @click="openAddModal"
                                            >
                                                <i class="bi bi-plus-circle"></i>
                                                <span>Catat Konseling Baru</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 3. Bagian Bawah: Pagination Footer Sesuai Standar Baku -->
                    <div v-if="konselingList?.total > 0" class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
                        <!-- Info Tampilkan Baris & Dropdown per_page -->
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
                            <span>Tampilkan</span>
                            <select
                                v-model="perPage"
                                @change="applyFilters"
                                class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option :value="10">10</option>
                                <option :value="15">15</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                            <span class="whitespace-nowrap">baris per halaman</span>
                            <span class="text-slate-300 hidden sm:inline">|</span>
                            <span class="whitespace-nowrap">
                                Menampilkan <span class="font-bold text-slate-800">{{ konselingList.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ konselingList.to || konselingList.total }}</span> dari <span class="font-bold text-slate-800">{{ konselingList.total }}</span> baris
                            </span>
                        </div>

                        <!-- Pagination Links (Smart Windowing & Compact Chevrons) -->
                        <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
                            <template v-for="(link, i) in getSmartPaginationLinks(konselingList)" :key="i">
                                <button
                                    v-if="link.url && !link.active"
                                    type="button"
                                    @click="goToPage(link.url)"
                                    class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs"
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

            <!-- TAB 2: KATEGORI & KASUS MASALAH -->
            <div v-show="activeTab === 'kategori_kasus'" class="space-y-6">
                <!-- Info Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6 rounded-3xl shadow-sm relative overflow-hidden">
                    <div class="relative z-10 max-w-2xl">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 text-white text-xs font-semibold backdrop-blur-md mb-2">
                            <i class="bi bi-pie-chart-fill"></i> Klasifikasi 5 Bidang BK
                        </span>
                        <h2 class="text-xl md:text-2xl font-black tracking-tight">Bidang Masalah & Kasus Siswa</h2>
                        <p class="text-xs md:text-sm text-blue-100 mt-1 font-medium leading-relaxed">
                            Distribusi layanan bimbingan konseling berdasarkan aspek pribadi, hubungan sosial, kesulitan belajar, perencanaan masa depan karier, dan kedisiplinan sekolah.
                        </p>
                    </div>
                </div>

                <!-- Grid 5 Bidang Konseling -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- 1. Pribadi -->
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                                    <i class="bi bi-person-heart"></i>
                                </span>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Bimbingan Pribadi</h3>
                                    <p class="text-[11px] text-slate-400">Emosi, kepercayaan diri, problem keluarga</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl bg-purple-100 text-purple-700 font-black text-xs">
                                {{ (kategoriBreakdown?.find(k => k.jenis_konseling === 'Pribadi')?.total) || 0 }} Kasus
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Membantu siswa memahami keunikan diri, mengelola emosi, menghadapi krisis identitas, kecemasan, serta tantangan dalam lingkungan rumah.
                        </p>
                        <button
                            type="button"
                            class="w-full py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold rounded-2xl text-xs transition flex items-center justify-center gap-1.5"
                            @click="jenisKonseling = 'Pribadi'; activeTab = 'jurnal_konseling'; applyFilters();"
                        >
                            <i class="bi bi-filter"></i> Lihat Kasus Pribadi
                        </button>
                    </div>

                    <!-- 2. Sosial -->
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                                    <i class="bi bi-people"></i>
                                </span>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Bimbingan Sosial</h3>
                                    <p class="text-[11px] text-slate-400">Penyesuaian teman, empati, anti-bullying</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl bg-blue-100 text-blue-700 font-black text-xs">
                                {{ (kategoriBreakdown?.find(k => k.jenis_konseling === 'Sosial')?.total) || 0 }} Kasus
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Fokus pada interaksi sosial positif, mediasi konflik antar-siswa, penanganan perundungan (bullying), dan adaptasi lingkungan baru.
                        </p>
                        <button
                            type="button"
                            class="w-full py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold rounded-2xl text-xs transition flex items-center justify-center gap-1.5"
                            @click="jenisKonseling = 'Sosial'; activeTab = 'jurnal_konseling'; applyFilters();"
                        >
                            <i class="bi bi-filter"></i> Lihat Kasus Sosial
                        </button>
                    </div>

                    <!-- 3. Belajar -->
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                                    <i class="bi bi-book"></i>
                                </span>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Bimbingan Belajar</h3>
                                    <p class="text-[11px] text-slate-400">Motivasi, kesulitan materi, manajemen waktu</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl bg-emerald-100 text-emerald-700 font-black text-xs">
                                {{ (kategoriBreakdown?.find(k => k.jenis_konseling === 'Belajar')?.total) || 0 }} Kasus
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Membantu siswa menemukan gaya belajar efektif, mengatasi kejenuhan belajar, menata jadwal belajar rumah, dan pemulihan nilai remedial.
                        </p>
                        <button
                            type="button"
                            class="w-full py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-2xl text-xs transition flex items-center justify-center gap-1.5"
                            @click="jenisKonseling = 'Belajar'; activeTab = 'jurnal_konseling'; applyFilters();"
                        >
                            <i class="bi bi-filter"></i> Lihat Kasus Belajar
                        </button>
                    </div>

                    <!-- 4. Karier -->
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                                    <i class="bi bi-compass"></i>
                                </span>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Bimbingan Karier</h3>
                                    <p class="text-[11px] text-slate-400">Minat bakat, SNMPTN/SNBT, dunia kerja</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl bg-amber-100 text-amber-700 font-black text-xs">
                                {{ (kategoriBreakdown?.find(k => k.jenis_konseling === 'Karier')?.total) || 0 }} Kasus
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Eksplorasi potensi jurusan kuliah, pemetaan minat karier profesional, persiapan portofolio PDSS, serta kesiapan dunia industri (SMK).
                        </p>
                        <button
                            type="button"
                            class="w-full py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold rounded-2xl text-xs transition flex items-center justify-center gap-1.5"
                            @click="jenisKonseling = 'Karier'; activeTab = 'jurnal_konseling'; applyFilters();"
                        >
                            <i class="bi bi-filter"></i> Lihat Kasus Karier
                        </button>
                    </div>

                    <!-- 5. Kedisiplinan -->
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                                    <i class="bi bi-shield-exclamation"></i>
                                </span>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Bimbingan Kedisiplinan</h3>
                                    <p class="text-[11px] text-slate-400">Tindak lanjut poin pelanggaran tata tertib</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl bg-rose-100 text-rose-700 font-black text-xs">
                                {{ (kategoriBreakdown?.find(k => k.jenis_konseling === 'Kedisiplinan')?.total) || 0 }} Kasus
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Konseling khusus restoratif bagi siswa yang melakukan pelanggaran tata tertib untuk pembinaan karakter dan pemulihan komitmen belajar.
                        </p>
                        <button
                            type="button"
                            class="w-full py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-2xl text-xs transition flex items-center justify-center gap-1.5"
                            @click="jenisKonseling = 'Kedisiplinan'; activeTab = 'jurnal_konseling'; applyFilters();"
                        >
                            <i class="bi bi-filter"></i> Lihat Kasus Disiplin
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 3: STATISTIK & TREN LAYANAN -->
            <div v-show="activeTab === 'statistik_tren'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <!-- Tren 6 Bulan Terakhir -->
                    <div class="lg:col-span-8 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Tren Sesi Konseling Bulanan</h3>
                                <p class="text-xs text-slate-400">Distribusi volume bimbingan konseling 6 bulan terakhir</p>
                            </div>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full bg-blue-50 text-blue-600">
                                <i class="bi bi-activity"></i> Aktivitas Real-time
                            </span>
                        </div>

                        <!-- Bar chart visualisation -->
                        <div class="pt-6 space-y-4">
                            <div v-for="item in monthlyTrend || []" :key="item.periode" class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                                    <span class="flex items-center gap-1.5">
                                        <i class="bi bi-calendar-check text-blue-500"></i>
                                        Periode {{ item.periode }}
                                    </span>
                                    <span class="font-black text-blue-600">{{ item.total }} Sesi</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                                    <div
                                        class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500"
                                        :style="{ width: `${Math.min(100, (item.total / (kpi?.total_konseling || 1)) * 100)}%` }"
                                    ></div>
                                </div>
                            </div>

                            <div v-if="!monthlyTrend?.length" class="text-center py-8 text-slate-400 text-xs">
                                Belum ada data tren bulanan yang tercatat.
                            </div>
                        </div>
                    </div>

                    <!-- Distribusi Status & Kerahasiaan -->
                    <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Sifat & Penyelesaian</h3>
                            <p class="text-xs text-slate-400">Ringkasan status penanganan kasus</p>
                        </div>

                        <div class="space-y-3 pt-2">
                            <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-sm">
                                        <i class="bi bi-check-lg"></i>
                                    </span>
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs">Kasus Selesai</div>
                                        <div class="text-[11px] text-emerald-600 font-semibold">Tuntas ditangani</div>
                                    </div>
                                </div>
                                <span class="text-base font-black text-emerald-700">{{ kpi?.kasus_selesai || 0 }}</span>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-xl bg-blue-500 text-white flex items-center justify-center text-sm">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </span>
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs">Pendampingan</div>
                                        <div class="text-[11px] text-blue-600 font-semibold">Dalam proses bimbingan</div>
                                    </div>
                                </div>
                                <span class="text-base font-black text-blue-700">{{ kpi?.dalam_pendampingan || 0 }}</span>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm">
                                        <i class="bi bi-exclamation"></i>
                                    </span>
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs">Kasus Terbuka</div>
                                        <div class="text-[11px] text-amber-600 font-semibold">Menunggu jadwal</div>
                                    </div>
                                </div>
                                <span class="text-base font-black text-amber-700">{{ kpi?.kasus_terbuka || 0 }}</span>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-purple-50 border border-purple-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-xl bg-purple-500 text-white flex items-center justify-center text-sm">
                                        <i class="bi bi-shield-lock"></i>
                                    </span>
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs">Sesi Konfidensial</div>
                                        <div class="text-[11px] text-purple-600 font-semibold">Terkunci & terlindungi</div>
                                    </div>
                                </div>
                                <span class="text-base font-black text-purple-700">{{ kpi?.konseling_rahasia || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL 1: CATAT / EDIT SESI KONSELING -->
            <Teleport to="body">
                <div
                    v-if="showModalRecord"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
                >
                    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 md:p-7 shadow-2xl border border-slate-100 my-8 space-y-5 animate-in fade-in zoom-in-95 duration-200 relative z-10">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                                <i class="bi bi-pencil-square"></i>
                            </span>
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">
                                    {{ isEditingRecord ? 'Edit Catatan Sesi Konseling' : 'Catat Sesi Bimbingan Konseling' }}
                                </h2>
                                <p class="text-xs text-slate-400">
                                    Lengkapi identitas siswa, bidang bimbingan, permasalahan, dan rekomendasi tindak lanjut.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="w-8 h-8 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition"
                            @click="showModalRecord = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitRecord" class="space-y-4">
                        <!-- Siswa Autocomplete Picker -->
                        <div class="space-y-1.5 relative">
                            <label class="block text-xs font-bold text-slate-700">
                                Siswa yang Berkonsultasi <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="siswaSearchQuery"
                                    type="text"
                                    placeholder="Ketik Nama Siswa atau NISN untuk mencari..."
                                    class="w-full px-3.5 py-2.5 text-xs md:text-sm bg-white border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition placeholder:text-slate-400"
                                    :disabled="isEditingRecord"
                                    @input="onSiswaSearchInput"
                                />
                                <button
                                    v-if="selectedSiswa && !isEditingRecord"
                                    type="button"
                                    class="absolute right-3 top-2.5 text-xs text-slate-400 hover:text-rose-600"
                                    @click="clearSelectedSiswa"
                                >
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>

                            <!-- Autocomplete Dropdown List -->
                            <div
                                v-if="siswaSearchResults.length"
                                class="absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-2xl shadow-xl max-h-48 overflow-y-auto divide-y divide-slate-100"
                            >
                                <button
                                    v-for="s in siswaSearchResults"
                                    :key="s.id"
                                    type="button"
                                    class="w-full p-3 text-left hover:bg-blue-50/60 transition flex items-center justify-between"
                                    @click="selectSiswa(s)"
                                >
                                    <div>
                                        <div class="font-bold text-xs text-slate-800">{{ s.nama_lengkap }}</div>
                                        <div class="text-[11px] text-slate-400">NISN: {{ s.nisn }} • NIS: {{ s.nis }}</div>
                                    </div>
                                    <span class="text-xs text-blue-600 font-semibold">Pilih</span>
                                </button>
                            </div>

                            <div v-if="recordForm.errors.siswa_id" class="text-xs text-rose-500 mt-1">
                                {{ recordForm.errors.siswa_id }}
                            </div>
                        </div>

                        <!-- Tanggal & Bidang Konseling -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    Tanggal Konseling <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="recordForm.tanggal_konseling"
                                    type="date"
                                    required
                                    class="w-full px-3.5 py-2.5 text-xs md:text-sm bg-white border border-slate-200 rounded-2xl focus:outline-none focus:border-blue-500"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    Bidang Bimbingan <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="recordForm.jenis_konseling"
                                    required
                                    class="w-full px-3.5 py-2.5 text-xs md:text-sm bg-white border border-slate-200 rounded-2xl focus:outline-none focus:border-blue-500 font-semibold"
                                >
                                    <option value="Pribadi">Pribadi (Emosi, Keluarga, Diri)</option>
                                    <option value="Sosial">Sosial (Pertemanan, Bullying)</option>
                                    <option value="Belajar">Belajar (Motivasi, Kesulitan)</option>
                                    <option value="Karier">Karier (Kuliah, Minat Kerja)</option>
                                    <option value="Kedisiplinan">Kedisiplinan (Tata Tertib)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Topik Masalah -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Topik / Pokok Masalah <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="recordForm.topik_masalah"
                                type="text"
                                required
                                placeholder="Contoh: Kesulitan Konsentrasi Belajar dan Penurunan Nilai"
                                class="w-full px-3.5 py-2.5 text-xs md:text-sm bg-white border border-slate-200 rounded-2xl focus:outline-none focus:border-blue-500"
                            />
                        </div>

                        <!-- Ringkasan Permasalahan -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Ringkasan Pembahasan & Keluhan Siswa
                            </label>
                            <textarea
                                v-model="recordForm.ringkasan_konseling"
                                rows="3"
                                placeholder="Tuliskan latar belakang masalah, faktor pemicu, dan hasil dialog bimbingan konseling..."
                                class="w-full px-3.5 py-2.5 text-xs md:text-sm bg-white border border-slate-200 rounded-2xl focus:outline-none focus:border-blue-500"
                            ></textarea>
                        </div>

                        <!-- Solusi & Tindak Lanjut -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Rekomendasi Solusi & Tindak Lanjut
                            </label>
                            <textarea
                                v-model="recordForm.solusi_tindak_lanjut"
                                rows="2"
                                placeholder="Rencana aksi siswa, jadwal pertemuan berikutnya, atau koordinasi dengan wali kelas/orang tua..."
                                class="w-full px-3.5 py-2.5 text-xs md:text-sm bg-white border border-slate-200 rounded-2xl focus:outline-none focus:border-blue-500"
                            ></textarea>
                        </div>

                        <!-- Guru BK & Status Kasus -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    Konselor / Guru BK
                                </label>
                                <input
                                    v-model="recordForm.guru_bk_nama"
                                    type="text"
                                    placeholder="Nama Guru BK yang menangani"
                                    class="w-full px-3.5 py-2.5 text-xs md:text-sm bg-white border border-slate-200 rounded-2xl focus:outline-none focus:border-blue-500"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    Status Kasus <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="recordForm.status_kasus"
                                    required
                                    class="w-full px-3.5 py-2.5 text-xs md:text-sm bg-white border border-slate-200 rounded-2xl focus:outline-none focus:border-blue-500 font-semibold"
                                >
                                    <option value="Terbuka">Terbuka (Menunggu Jadwal/Tindakan)</option>
                                    <option value="Dalam Pendampingan">Dalam Pendampingan Aktif</option>
                                    <option value="Selesai">Selesai (Kasus Teratasi)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Checkbox Sifat Rahasia & File Upload -->
                        <div class="pt-2 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input
                                    v-model="recordForm.is_rahasia"
                                    type="checkbox"
                                    class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 border-slate-300"
                                />
                                <span class="text-xs font-bold text-purple-700 flex items-center gap-1">
                                    <i class="bi bi-shield-lock-fill"></i> Kasus Bersifat Rahasia / Konfidensial
                                </span>
                            </label>

                            <div class="flex items-center gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                                    <i class="bi bi-paperclip"></i>
                                    <span>Lampirkan Foto/Dokumen</span>
                                    <input type="file" class="hidden" accept="image/*,.pdf" @change="handleFileUpload" />
                                </label>
                                <span v-if="recordForm.foto_bukti" class="text-[11px] text-emerald-600 font-bold">
                                    ✓ Berkas Dipilih
                                </span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                class="px-4 py-2.5 rounded-2xl bg-white hover:bg-slate-100 text-slate-700 font-semibold text-xs md:text-sm border border-slate-200 transition"
                                @click="showModalRecord = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="recordForm.processing"
                                class="px-5 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs md:text-sm shadow-xs transition flex items-center gap-2 disabled:opacity-50"
                            >
                                <i class="bi bi-check-circle"></i>
                                <span>{{ isEditingRecord ? 'Perbarui Sesi' : 'Simpan Sesi Konseling' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            </Teleport>

            <!-- MODAL 2: DETAIL JURNAL KONSELING -->
            <Teleport to="body">
                <div
                    v-if="showModalDetail && detailKonseling"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
                >
                    <div class="bg-white rounded-3xl max-w-xl w-full p-6 md:p-7 shadow-2xl border border-slate-100 my-8 space-y-5 animate-in fade-in zoom-in-95 duration-200 relative z-10">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                                <i class="bi bi-journal-bookmark-fill"></i>
                            </span>
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">Detail Sesi Bimbingan Konseling</h2>
                                <p class="text-xs text-slate-400">Rekam jejak dan kronologi penanganan bimbingan siswa.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="w-8 h-8 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition"
                            @click="showModalDetail = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Detail Card Content -->
                    <div class="space-y-4">
                        <!-- Siswa Header Info -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-base shadow-xs">
                                    {{ (detailKonseling.snapshot_nama_siswa || detailKonseling.siswa?.nama_lengkap || 'S')[0] }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-sm">
                                        {{ detailKonseling.snapshot_nama_siswa || detailKonseling.siswa?.nama_lengkap || '-' }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        NISN: {{ detailKonseling.snapshot_nisn || detailKonseling.siswa?.nisn || '-' }} • {{ detailKonseling.snapshot_nama_kelas || 'Kelas Siswa' }}
                                    </div>
                                </div>
                            </div>

                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold border"
                                :class="getStatusBadgeClass(detailKonseling.status_kasus)"
                            >
                                {{ detailKonseling.status_kasus }}
                            </span>
                        </div>

                        <!-- Bidang & Waktu -->
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-400 block mb-1">Tanggal Konseling</span>
                                <span class="font-bold text-slate-700">{{ formatDateLong(detailKonseling.tanggal_konseling) }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-400 block mb-1">Bidang Layanan</span>
                                <span class="font-bold text-purple-700">{{ detailKonseling.jenis_konseling || 'Umum' }}</span>
                            </div>
                        </div>

                        <!-- Topik Masalah -->
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Topik Pokok Permasalahan</span>
                            <div class="p-3 rounded-xl bg-blue-50/50 border border-blue-100 text-xs font-bold text-slate-800">
                                {{ detailKonseling.topik_masalah }}
                            </div>
                        </div>

                        <!-- Ringkasan Pembahasan -->
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ringkasan Sesi & Keluhan Siswa</span>
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                                {{ detailKonseling.ringkasan_konseling || detailKonseling.catatan || 'Tidak ada catatan ringkasan.' }}
                            </div>
                        </div>

                        <!-- Solusi Tindak Lanjut -->
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Solusi & Kesepakatan Tindak Lanjut</span>
                            <div class="p-3.5 rounded-xl bg-emerald-50/50 border border-emerald-100 text-xs text-emerald-900 leading-relaxed whitespace-pre-line">
                                {{ detailKonseling.solusi_tindak_lanjut || detailKonseling.tindak_lanjut || 'Belum ada solusi yang dicatat.' }}
                            </div>
                        </div>

                        <!-- Petugas BK & Lampiran -->
                        <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                            <span class="text-slate-500">
                                Guru BK: <strong class="text-slate-800">{{ detailKonseling.guru_bk_nama || 'Guru BK' }}</strong>
                            </span>

                            <a
                                v-if="detailKonseling.foto_panggilan"
                                :href="detailKonseling.foto_panggilan"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-blue-600 font-bold hover:underline"
                            >
                                <i class="bi bi-file-earmark-image"></i> Lihat Lampiran Dokumen
                            </a>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <button
                            type="button"
                            class="px-4 py-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                            @click="openCetakModal(detailKonseling); showModalDetail = false;"
                        >
                            <i class="bi bi-printer"></i> Cetak Lembar Konseling
                        </button>

                        <button
                            type="button"
                            class="px-4 py-2 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition"
                            @click="showModalDetail = false"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
            </Teleport>

            <!-- MODAL 3: CETAK BERITA ACARA / LEMBAR KONSELING SISWA RESMI -->
            <Teleport to="body">
                <div
                    v-if="showModalCetak && cetakData"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
                >
                    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 md:p-8 shadow-2xl border border-slate-100 my-8 space-y-6 animate-in fade-in zoom-in-95 duration-200 relative z-10">
                    <!-- Actions at top of print preview -->
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3 no-print">
                        <div class="flex items-center gap-2 text-slate-700 font-bold text-sm">
                            <i class="bi bi-printer-fill text-blue-600"></i>
                            Pratinjau Lembar Pelayanan Konseling Siswa
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="px-4 py-2 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                                @click="printDocument"
                            >
                                <i class="bi bi-printer"></i> Cetak Sekarang (Print)
                            </button>
                            <button
                                type="button"
                                class="w-8 h-8 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition"
                                @click="showModalCetak = false"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Printable Container -->
                    <div class="p-6 border border-slate-300 rounded-xl space-y-5 bg-white text-slate-900 printable-area">
                        <!-- Dynamic Kop Surat Sekolah -->
                        <div class="text-center border-b-2 border-slate-900 pb-4">
                            <h3 class="text-base md:text-lg font-black tracking-wide uppercase">
                                {{ tenantInfo?.nama_sekolah || 'SMA / SMK NEGERI SINTA' }}
                            </h3>
                            <h4 class="text-xs md:text-sm font-bold uppercase tracking-wider text-slate-700">
                                UNIT LAYANAN BIMBINGAN DAN KONSELING (BK)
                            </h4>
                            <p class="text-[11px] text-slate-600 mt-1">
                                {{ tenantInfo?.alamat || 'Jl. Pendidikan No. 10' }}, {{ tenantInfo?.kabupaten_kota || 'Kota' }} • Telp: {{ tenantInfo?.telepon || '(021) 555-1234' }} • Email: {{ tenantInfo?.email || 'bk@sekolah.sch.id' }}
                            </p>
                        </div>

                        <!-- Document Title -->
                        <div class="text-center space-y-1">
                            <h4 class="text-sm md:text-base font-black uppercase underline tracking-wide">
                                LEMBAR PELAYANAN BIMBINGAN DAN KONSELING
                            </h4>
                            <p class="text-[11px] text-slate-600 font-medium">
                                Nomor Berkas: BK/{{ new Date().getFullYear() }}/{{ cetakData.id ? cetakData.id.substring(0, 8).toUpperCase() : '001' }}
                            </p>
                        </div>

                        <!-- Identitas Siswa Table -->
                        <table class="w-full text-xs text-left border border-slate-300">
                            <tbody>
                                <tr class="border-b border-slate-200">
                                    <td class="p-2 w-1/3 bg-slate-50 font-bold">Nama Lengkap Siswa</td>
                                    <td class="p-2 font-bold">{{ cetakData.snapshot_nama_siswa || cetakData.siswa?.nama_lengkap || '-' }}</td>
                                </tr>
                                <tr class="border-b border-slate-200">
                                    <td class="p-2 bg-slate-50 font-bold">NISN / NIS</td>
                                    <td class="p-2">{{ cetakData.snapshot_nisn || cetakData.siswa?.nisn || '-' }} / {{ cetakData.snapshot_nis || cetakData.siswa?.nis || '-' }}</td>
                                </tr>
                                <tr class="border-b border-slate-200">
                                    <td class="p-2 bg-slate-50 font-bold">Kelas / Rombel</td>
                                    <td class="p-2">{{ cetakData.snapshot_nama_kelas || 'Kelas Siswa' }}</td>
                                </tr>
                                <tr class="border-b border-slate-200">
                                    <td class="p-2 bg-slate-50 font-bold">Hari / Tanggal Konseling</td>
                                    <td class="p-2">{{ formatDateLong(cetakData.tanggal_konseling) }}</td>
                                </tr>
                                <tr class="border-b border-slate-200">
                                    <td class="p-2 bg-slate-50 font-bold">Bidang Layanan</td>
                                    <td class="p-2 font-bold">{{ cetakData.jenis_konseling }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2 bg-slate-50 font-bold">Status Penanganan</td>
                                    <td class="p-2 font-bold">{{ cetakData.status_kasus }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Deskripsi Permasalahan & Solusi -->
                        <div class="space-y-3 text-xs">
                            <div class="border border-slate-300 p-3 rounded-lg">
                                <div class="font-bold text-slate-800 mb-1 uppercase tracking-wider text-[11px]">
                                    A. Pokok Permasalahan / Topik Konseling:
                                </div>
                                <p class="text-slate-700 font-semibold mb-2">{{ cetakData.topik_masalah }}</p>
                                <p class="text-slate-600 leading-relaxed whitespace-pre-line text-[11px]">
                                    {{ cetakData.ringkasan_konseling || cetakData.catatan || 'Tidak ada catatan ringkasan.' }}
                                </p>
                            </div>

                            <div class="border border-slate-300 p-3 rounded-lg">
                                <div class="font-bold text-slate-800 mb-1 uppercase tracking-wider text-[11px]">
                                    B. Kesepakatan, Solusi & Rencana Tindak Lanjut:
                                </div>
                                <p class="text-slate-700 leading-relaxed whitespace-pre-line text-[11px]">
                                    {{ cetakData.solusi_tindak_lanjut || cetakData.tindak_lanjut || 'Siswa dan guru BK menyepakati untuk melakukan evaluasi berkala secara mandiri.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Kolom Tanda Tangan Resmi Dinamis -->
                        <div class="grid grid-cols-2 gap-6 pt-6 text-xs text-center">
                            <div class="space-y-16">
                                <p>Siswa yang Bersangkutan,</p>
                                <div>
                                    <p class="font-bold underline">{{ cetakData.snapshot_nama_siswa || cetakData.siswa?.nama_lengkap || 'Nama Siswa' }}</p>
                                    <p class="text-[11px] text-slate-500">NISN: {{ cetakData.snapshot_nisn || cetakData.siswa?.nisn || '-' }}</p>
                                </div>
                            </div>

                            <div class="space-y-16">
                                <p>{{ tenantInfo?.kabupaten_kota || 'Kota' }}, {{ formatDate(cetakData.tanggal_konseling) }}<br/>Guru Bimbingan Konseling,</p>
                                <div>
                                    <p class="font-bold underline">{{ cetakData.guru_bk_nama || 'Guru BK' }}</p>
                                    <p class="text-[11px] text-slate-500">NIP. -</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mengetahui Kepala Sekolah -->
                        <div class="pt-6 text-xs text-center space-y-16 border-t border-slate-200">
                            <p>Mengetahui,<br/>Kepala Sekolah</p>
                            <div>
                                <p class="font-bold underline">{{ tenantInfo?.nama_kepsek || 'Kepala Sekolah' }}</p>
                                <p class="text-[11px] text-slate-500">NIP. {{ tenantInfo?.nip_kepsek || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </Teleport>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }
    .printable-area, .printable-area * {
        visibility: visible;
    }
    .printable-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        padding: 0 !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>
