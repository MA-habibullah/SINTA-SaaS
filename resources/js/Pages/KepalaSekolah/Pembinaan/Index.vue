<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    isSuperAdmin: Boolean,
    tenants: Array,
    activeTenantId: String,
    supervisiList: Object,
    kpi: Object,
    guruSelector: Array,
    filters: Object,
})

// Super Admin Selected Tenant
const selectedTenant = ref(props.activeTenantId || '')

const onTenantChange = () => {
    router.get('/kepala-sekolah/pembinaan', {
        tenant_id: selectedTenant.value || undefined,
        search: search.value || undefined,
        status_pembinaan: statusPembinaan.value || undefined,
        jenis_supervisi: jenisSupervisi.value || undefined,
        predikat: predikat.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Tab State
const activeTab = ref('jadwal_log') // 'jadwal_log', 'matriks_kompetensi', 'tindak_lanjut'

// Search & Filter State
const search = ref(props.filters?.search || '')
const statusPembinaan = ref(props.filters?.status_pembinaan || '')
const jenisSupervisi = ref(props.filters?.jenis_supervisi || '')
const predikat = ref(props.filters?.predikat || '')

const applyFilters = () => {
    router.get('/kepala-sekolah/pembinaan', {
        tenant_id: props.isSuperAdmin ? (selectedTenant.value || undefined) : undefined,
        search: search.value || undefined,
        status_pembinaan: statusPembinaan.value || undefined,
        jenis_supervisi: jenisSupervisi.value || undefined,
        predikat: predikat.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    statusPembinaan.value = ''
    jenisSupervisi.value = ''
    predikat.value = ''
    applyFilters()
}

// Modal Form State
const isModalOpen = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

// Live Autocomplete Guru
const guruSearch = ref('')
const isGuruDropdownOpen = ref(false)
const selectedGuru = ref(null)

const filteredGuruList = computed(() => {
    if (!props.guruSelector || props.guruSelector.length === 0) return []
    const q = guruSearch.value.toLowerCase().trim()
    if (!q) return props.guruSelector.slice(0, 10)
    return props.guruSelector.filter(g => 
        (g.nama_lengkap && g.nama_lengkap.toLowerCase().includes(q)) ||
        (g.nip && g.nip.toLowerCase().includes(q)) ||
        (g.nuptk && g.nuptk.toLowerCase().includes(q))
    ).slice(0, 10)
})

const selectGuru = (g) => {
    selectedGuru.value = g
    form.guru_id = g.id
    form.nama_guru = g.nama_lengkap
    form.nip_guru = g.nip || g.nuptk || ''
    guruSearch.value = g.nama_lengkap
    isGuruDropdownOpen.value = false
}

const clearGuruSelection = () => {
    selectedGuru.value = null
    form.guru_id = null
    form.nama_guru = ''
    form.nip_guru = ''
    guruSearch.value = ''
}

// Detail View Modal
const isDetailModalOpen = ref(false)
const selectedDetail = ref(null)

const openDetail = (item) => {
    selectedDetail.value = item
    isDetailModalOpen.value = true
}

// Form Handling
const form = useForm({
    guru_id: null,
    nama_guru: '',
    nip_guru: '',
    mata_pelajaran: '',
    kelas_rombel: '',
    tanggal_supervisi: new Date().toISOString().split('T')[0],
    tahun_ajaran: '2026/2027',
    semester: 'Ganjil',
    jenis_supervisi: 'Supervisi Akademik/Kelas',
    skor_pedagogik: 85,
    skor_profesional: 85,
    skor_kepribadian: 90,
    skor_sosial: 90,
    catatan_observasi: '',
    rekomendasi_pembinaan: '',
    tindak_lanjut: '',
    status_pembinaan: 'Selesai Dibina',
})

// Auto Calculated Score in Modal
const calculatedScore = computed(() => {
    const p = Number(form.skor_pedagogik) || 0
    const pr = Number(form.skor_profesional) || 0
    const k = Number(form.skor_kepribadian) || 0
    const s = Number(form.skor_sosial) || 0
    return Math.round(((p + pr + k + s) / 4) * 10) / 10
})

const calculatedPredikat = computed(() => {
    const s = calculatedScore.value
    if (s >= 91) return { label: 'Sangat Baik', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' }
    if (s >= 76) return { label: 'Baik', color: 'bg-blue-50 text-blue-700 border-blue-200' }
    if (s >= 61) return { label: 'Cukup', color: 'bg-amber-50 text-amber-700 border-amber-200' }
    return { label: 'Perlu Pembinaan', color: 'bg-rose-50 text-rose-700 border-rose-200' }
})

const openCreateModal = () => {
    isEditing.value = false
    editingId.value = null
    form.reset()
    clearGuruSelection()
    form.tanggal_supervisi = new Date().toISOString().split('T')[0]
    form.tahun_ajaran = '2026/2027'
    form.semester = 'Ganjil'
    form.jenis_supervisi = 'Supervisi Akademik/Kelas'
    form.skor_pedagogik = 85
    form.skor_profesional = 85
    form.skor_kepribadian = 90
    form.skor_sosial = 90
    form.status_pembinaan = 'Selesai Dibina'
    isModalOpen.value = true
}

const openEditModal = (item) => {
    isEditing.value = true
    editingId.value = item.id
    form.guru_id = item.guru_id
    form.nama_guru = item.nama_guru
    form.nip_guru = item.nip_guru || ''
    form.mata_pelajaran = item.mata_pelajaran || ''
    form.kelas_rombel = item.kelas_rombel || ''
    form.tanggal_supervisi = item.tanggal_supervisi ? item.tanggal_supervisi.split('T')[0] : ''
    form.tahun_ajaran = item.tahun_ajaran || '2026/2027'
    form.semester = item.semester || 'Ganjil'
    form.jenis_supervisi = item.jenis_supervisi || 'Supervisi Akademik/Kelas'
    form.skor_pedagogik = item.skor_pedagogik || 80
    form.skor_profesional = item.skor_profesional || 80
    form.skor_kepribadian = item.skor_kepribadian || 80
    form.skor_sosial = item.skor_sosial || 80
    form.catatan_observasi = item.catatan_observasi || ''
    form.rekomendasi_pembinaan = item.rekomendasi_pembinaan || ''
    form.tindak_lanjut = item.tindak_lanjut || ''
    form.status_pembinaan = item.status_pembinaan || 'Selesai Dibina'
    guruSearch.value = item.nama_guru
    selectedGuru.value = { id: item.guru_id, nama_lengkap: item.nama_guru, nip: item.nip_guru }
    isModalOpen.value = true
}

const submitForm = () => {
    if (isEditing.value) {
        form.put(`/kepala-sekolah/pembinaan/${editingId.value}`, {
            onSuccess: () => {
                isModalOpen.value = false
                form.reset()
            }
        })
    } else {
        form.post('/kepala-sekolah/pembinaan', {
            onSuccess: () => {
                isModalOpen.value = false
                form.reset()
            }
        })
    }
}

const deleteItem = (item) => {
    if (confirm(`Hapus rekam supervisi & pembinaan untuk ${item.nama_guru}?`)) {
        router.delete(`/kepala-sekolah/pembinaan/${item.id}`, {
            preserveScroll: true
        })
    }
}

const getPredikatBadge = (pred) => {
    switch (pred) {
        case 'Sangat Baik':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case 'Baik':
            return 'bg-blue-50 text-blue-700 border-blue-200'
        case 'Cukup':
            return 'bg-amber-50 text-amber-700 border-amber-200'
        case 'Perlu Pembinaan':
            return 'bg-rose-50 text-rose-700 border-rose-200'
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200'
    }
}

const getStatusBadge = (status) => {
    switch (status) {
        case 'Selesai Dibina':
            return 'bg-emerald-100 text-emerald-800'
        case 'Dalam Proses':
            return 'bg-blue-100 text-blue-800'
        case 'Terjadwal':
            return 'bg-amber-100 text-amber-800'
        case 'Butuh Pendampingan Khusus':
            return 'bg-rose-100 text-rose-800'
        default:
            return 'bg-slate-100 text-slate-800'
    }
}
</script>

<template>
    <AppLayout title="Kepala Sekolah - Pembinaan & Supervisi GTK">
        <Head title="Pembinaan & Supervisi GTK" />

        <div class="space-y-6">
            <!-- Super Admin Multi-Tenant Filter Bar -->
            <div v-if="isSuperAdmin" class="p-4 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                        <i class="bi bi-buildings"></i>
                    </div>
                    <div>
                        <div class="font-extrabold text-xs text-slate-800 uppercase tracking-wide flex items-center gap-2">
                            <span>Mode Platform Super Admin</span>
                            <span class="px-2 py-0.5 rounded text-[10px] bg-blue-600 text-white font-black">Multi-Tenant</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Filter data supervisi & pembinaan GTK per unit sekolah:</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <select v-model="selectedTenant" @change="onTenantChange" 
                            class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 shadow-2xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 min-w-[220px]">
                        <option v-for="t in tenants || []" :key="t.id" :value="t.id">
                            {{ t.nama_sekolah }} ({{ t.npsn || 'NPSN -' }})
                        </option>
                    </select>
                </div>
            </div>
            <!-- Header Halaman & Action -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-800">
                            MANAJEMEN KEPALA SEKOLAH
                        </span>
                        <span class="text-xs text-slate-400 font-medium">T.A. 2026/2027</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight mt-1">Pembinaan & Supervisi GTK</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Supervisi akademik, observasi kelas, penilaian 4 kompetensi guru, dan rekam jejak pembinaan profesionalisme.</p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button @click="openCreateModal" 
                            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs hover:shadow-md transition-all flex items-center gap-2">
                        <i class="bi bi-plus-circle-fill text-sm"></i>
                        <span>+ Catat Supervisi Baru</span>
                    </button>
                </div>
            </div>

            <!-- KPI Dashboard Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Supervisi</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                            <i class="bi bi-journal-check"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-800">{{ kpi?.total_supervisi || 0 }}</span>
                        <span class="text-2xs font-semibold text-slate-400">sesi observasi</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rata-Rata Skor</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                            <i class="bi bi-award-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-indigo-600">{{ kpi?.rata_rata_skor || 0 }}</span>
                        <span class="text-2xs font-semibold text-emerald-600">/ 100 poin</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Selesai Dibina</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-emerald-600">{{ kpi?.selesai_dibina || 0 }}</span>
                        <span class="text-2xs font-semibold text-slate-400">guru tersupervisi</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pendampingan Khusus</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-rose-600">{{ kpi?.butuh_pendampingan || 0 }}</span>
                        <span class="text-2xs font-semibold text-slate-400">butuh tindak lanjut</span>
                    </div>
                </div>
            </div>

            <!-- Horizontal NavTabs 3-Way Scroller -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsPembinaan')?.scrollBy({ left: -220, behavior: 'smooth' })"
                            title="Geser ke Kiri">
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsPembinaan" role="tablist">
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'jadwal_log' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'jadwal_log'">
                                    <i class="bi bi-table"></i>
                                    <span>Jadwal & Log Supervisi Guru</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'matriks_kompetensi' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'matriks_kompetensi'">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                    <span>Matriks 4 Kompetensi Guru</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                                        :class="activeTab === 'tindak_lanjut' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'" 
                                        @click="activeTab = 'tindak_lanjut'">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                    <span>Program PKB & Tindak Lanjut</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            onclick="document.getElementById('navTabsPembinaan')?.scrollBy({ left: 220, behavior: 'smooth' })"
                            title="Geser ke Kanan">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- TAB 1: JADWAL & LOG SUPERVISI GURU -->
            <div v-show="activeTab === 'jadwal_log'" class="space-y-4">
                <!-- Filter Bar -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <div class="relative lg:col-span-2">
                            <input v-model="search" 
                                   @keyup.enter="applyFilters" 
                                   type="text" 
                                   placeholder="Cari nama guru, NIP, mapel, atau catatan..." 
                                   class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                            <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                        </div>

                        <div>
                            <select v-model="jenisSupervisi" @change="applyFilters" 
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                <option value="">Semua Jenis Supervisi</option>
                                <option value="Supervisi Akademik/Kelas">Supervisi Akademik / Kelas</option>
                                <option value="Supervisi Perangkat Ajar">Supervisi Perangkat Ajar (RPP/Modul)</option>
                                <option value="Pembinaan Disiplin & Etika">Pembinaan Disiplin & Etika</option>
                                <option value="Supervisi Manajerial">Supervisi Manajerial</option>
                            </select>
                        </div>

                        <div>
                            <select v-model="statusPembinaan" @change="applyFilters" 
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                <option value="">Semua Status</option>
                                <option value="Terjadwal">Terjadwal</option>
                                <option value="Dalam Proses">Dalam Proses</option>
                                <option value="Selesai Dibina">Selesai Dibina</option>
                                <option value="Butuh Pendampingan Khusus">Butuh Pendampingan Khusus</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="applyFilters" class="px-3.5 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition flex items-center gap-1.5">
                                <i class="bi bi-funnel-fill"></i> Filter
                            </button>
                            <button @click="resetFilters" class="px-3 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200 transition">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                    <th class="py-3 px-4">Guru Pendidik</th>
                                    <th class="py-3 px-4">Mapel / Rombel</th>
                                    <th class="py-3 px-4">Jenis Supervisi</th>
                                    <th class="py-3 px-4">Tgl Observasi</th>
                                    <th class="py-3 px-4 text-center">Skor & Predikat</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                                <tr v-for="item in supervisiList?.data || []" :key="item.id" class="hover:bg-blue-50/30 transition">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800">{{ item.nama_guru }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ item.nip_guru ? `NIP: ${item.nip_guru}` : 'GTK Tetap' }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-slate-700">{{ item.mata_pelajaran || 'Umum / Tematik' }}</div>
                                        <div class="text-[11px] text-slate-400">{{ item.kelas_rombel || 'Semua Tingkat' }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 rounded-lg text-2xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ item.jenis_supervisi }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-700">{{ item.tanggal_supervisi ? item.tanggal_supervisi.split('T')[0] : '-' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ item.semester }} {{ item.tahun_ajaran }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex flex-col items-center">
                                            <span class="text-sm font-black text-slate-800">{{ item.skor_total }}</span>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="getPredikatBadge(item.predikat)">
                                                {{ item.predikat }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-2xs font-bold" :class="getStatusBadge(item.status_pembinaan)">
                                            {{ item.status_pembinaan }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button @click="openDetail(item)" 
                                                    class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" 
                                                    title="Lihat Detail Observasi">
                                                <i class="bi bi-eye text-sm"></i>
                                            </button>
                                            <button @click="openEditModal(item)" 
                                                    class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition" 
                                                    title="Edit Nilai & Catatan">
                                                <i class="bi bi-pencil-square text-sm"></i>
                                            </button>
                                            <button @click="deleteItem(item)" 
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" 
                                                    title="Hapus Rekord">
                                                <i class="bi bi-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!supervisiList?.data || supervisiList.data.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <i class="bi bi-journal-x text-4xl block mb-2 text-slate-300"></i>
                                        <p class="font-semibold text-xs">Belum ada catatan supervisi & pembinaan guru.</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Klik tombol "+ Catat Supervisi Baru" untuk memulai evaluasi klinis.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: MATRIKS 4 KOMPETENSI GURU -->
            <div v-show="activeTab === 'matriks_kompetensi'" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg">1</div>
                            <div>
                                <h2 class="text-xs font-bold text-slate-800">Kompetensi Pedagogik</h2>
                                <p class="text-2xs text-slate-400">Pengelolaan pembelajaran & modul ajar</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Pemahaman Karakteristik Siswa</span>
                                <span class="font-bold text-slate-800">88%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-blue-600 h-full rounded-full" style="width: 88%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-lg">2</div>
                            <div>
                                <h2 class="text-xs font-bold text-slate-800">Kompetensi Profesional</h2>
                                <p class="text-2xs text-slate-400">Penguasaan materi & kurikulum</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Kedalaman Struktur Keilmuan</span>
                                <span class="font-bold text-slate-800">86%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-indigo-600 h-full rounded-full" style="width: 86%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg">3</div>
                            <div>
                                <h2 class="text-xs font-bold text-slate-800">Kompetensi Kepribadian</h2>
                                <p class="text-2xs text-slate-400">Keteladanan, etika & kedisiplinan</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Integritas & Kedisiplinan Waktu</span>
                                <span class="font-bold text-slate-800">92%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-600 h-full rounded-full" style="width: 92%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-lg">4</div>
                            <div>
                                <h2 class="text-xs font-bold text-slate-800">Kompetensi Sosial</h2>
                                <p class="text-2xs text-slate-400">Komunikasi rekan, siswa & ortu</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Kemitraan & Interaksi Positif</span>
                                <span class="font-bold text-slate-800">90%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-amber-600 h-full rounded-full" style="width: 90%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Nilai 4 Aspek Per Guru -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-xs font-bold text-slate-800">Rincian Nilai 4 Kompetensi Guru Terjadwal</h2>
                        <span class="text-2xs text-slate-400">Standar BSNP & Dirjen GTK Kemendikbud</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                    <th class="py-2.5 px-4">Nama Guru</th>
                                    <th class="py-2.5 px-4 text-center">Pedagogik</th>
                                    <th class="py-2.5 px-4 text-center">Profesional</th>
                                    <th class="py-2.5 px-4 text-center">Kepribadian</th>
                                    <th class="py-2.5 px-4 text-center">Sosial</th>
                                    <th class="py-2.5 px-4 text-center">Rata-Rata</th>
                                    <th class="py-2.5 px-4 text-center">Predikat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="item in supervisiList?.data || []" :key="'matriks-' + item.id" class="hover:bg-slate-50/50">
                                    <td class="py-3 px-4 font-bold text-slate-800">{{ item.nama_guru }}</td>
                                    <td class="py-3 px-4 text-center font-semibold text-blue-600">{{ item.skor_pedagogik }}</td>
                                    <td class="py-3 px-4 text-center font-semibold text-indigo-600">{{ item.skor_profesional }}</td>
                                    <td class="py-3 px-4 text-center font-semibold text-emerald-600">{{ item.skor_kepribadian }}</td>
                                    <td class="py-3 px-4 text-center font-semibold text-amber-600">{{ item.skor_sosial }}</td>
                                    <td class="py-3 px-4 text-center font-black text-slate-800">{{ item.skor_total }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border" :class="getPredikatBadge(item.predikat)">
                                            {{ item.predikat }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PROGRAM PKB & TINDAK LANJUT -->
            <div v-show="activeTab === 'tindak_lanjut'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-5 rounded-2xl text-white shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-blue-200 uppercase">Workshop Internal</span>
                            <i class="bi bi-mortarboard-fill text-xl text-blue-200"></i>
                        </div>
                        <h2 class="text-base font-bold mt-2">Pelatihan Diferensiasi Modul Ajar</h2>
                        <p class="text-xs text-blue-100 mt-1">Rekomendasi bagi guru yang membutuhkan penguatan strategi asesmen formatif.</p>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-600 to-teal-700 p-5 rounded-2xl text-white shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-200 uppercase">Mentoring Sejawat</span>
                            <i class="bi bi-people-fill text-xl text-emerald-200"></i>
                        </div>
                        <h2 class="text-base font-bold mt-2">Program Peer Coaching Guru Senior</h2>
                        <p class="text-xs text-emerald-100 mt-1">Pendampingan metode interaktif dan pemanfaatan media digital di ruang kelas.</p>
                    </div>

                    <div class="bg-gradient-to-br from-purple-600 to-pink-700 p-5 rounded-2xl text-white shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-purple-200 uppercase">Diklat Mandiri PMM</span>
                            <i class="bi bi-laptop-fill text-xl text-purple-200"></i>
                        </div>
                        <h2 class="text-base font-bold mt-2">Aksi Nyata Platform Merdeka Mengajar</h2>
                        <p class="text-xs text-purple-100 mt-1">Penyelesaian topik PMM untuk pemenuhan angka kredit dan portofolio guru.</p>
                    </div>
                </div>

                <!-- Daftar Guru Rekomendasi Tindak Lanjut -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="p-4 border-b border-slate-100">
                        <h2 class="text-xs font-bold text-slate-800">Catatan Tindak Lanjut & Rekomendasi Kepala Sekolah</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="item in supervisiList?.data || []" :key="'tl-' + item.id" class="p-4 hover:bg-slate-50/60 transition">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-slate-800">{{ item.nama_guru }}</span>
                                        <span class="px-2 py-0.5 rounded text-2xs font-bold" :class="getStatusBadge(item.status_pembinaan)">{{ item.status_pembinaan }}</span>
                                    </div>
                                    <p class="text-2xs text-slate-500 mt-0.5">{{ item.mata_pelajaran }} — {{ item.kelas_rombel }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-slate-700">Skor: {{ item.skor_total }}</span>
                                </div>
                            </div>
                            <div class="mt-3 bg-slate-50 p-3 rounded-xl border border-slate-200/70 text-xs space-y-1.5">
                                <div>
                                    <span class="font-bold text-slate-600">Catatan Observasi:</span>
                                    <p class="text-slate-600 mt-0.5">{{ item.catatan_observasi || 'Observasi pembelajaran berjalan sesuai rencana tanpa temuan kritis.' }}</p>
                                </div>
                                <div v-if="item.rekomendasi_pembinaan">
                                    <span class="font-bold text-blue-700">Rekomendasi Pembinaan:</span>
                                    <p class="text-slate-600 mt-0.5">{{ item.rekomendasi_pembinaan }}</p>
                                </div>
                                <div v-if="item.tindak_lanjut">
                                    <span class="font-bold text-emerald-700">Program Tindak Lanjut:</span>
                                    <p class="text-slate-600 mt-0.5">{{ item.tindak_lanjut }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL FORM TAMBAH / EDIT SUPERVISI (<Teleport to="body">) -->
        <Teleport to="body">
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
                <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 my-8 overflow-hidden">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-black text-slate-800">
                                {{ isEditing ? 'Edit Supervisi & Pembinaan Guru' : 'Catat Supervisi & Pembinaan Baru' }}
                            </h2>
                            <p class="text-2xs text-slate-400 mt-0.5">Instrumen evaluasi klinis dan pembinaan pedagogik guru.</p>
                        </div>
                        <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                        <!-- Pemilihan Guru (Live Autocomplete) -->
                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Guru Pendidik *</label>
                            
                            <!-- Selected Card -->
                            <div v-if="selectedGuru" class="p-3 bg-blue-50/80 border border-blue-200 rounded-xl flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
                                        {{ selectedGuru.nama_lengkap.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-xs text-blue-900">{{ selectedGuru.nama_lengkap }}</div>
                                        <div class="text-[11px] text-blue-600">{{ selectedGuru.nip ? `NIP: ${selectedGuru.nip}` : 'GTK Sekolah' }}</div>
                                    </div>
                                </div>
                                <button type="button" @click="clearGuruSelection" class="text-xs text-rose-600 font-bold hover:underline">
                                    Ganti
                                </button>
                            </div>

                            <!-- Autocomplete Search Input -->
                            <div v-else class="relative">
                                <input type="text" 
                                       v-model="guruSearch" 
                                       @focus="isGuruDropdownOpen = true"
                                       placeholder="Ketik nama atau NIP guru..." 
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                <i class="bi bi-search absolute right-3.5 top-3 text-slate-400 text-xs"></i>

                                <!-- Dropdown Results -->
                                <div v-if="isGuruDropdownOpen && filteredGuruList.length > 0" 
                                     class="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl border border-slate-200 shadow-xl max-h-48 overflow-y-auto z-50 divide-y divide-slate-100">
                                    <div v-for="g in filteredGuruList" 
                                         :key="g.id" 
                                         @click="selectGuru(g)" 
                                         class="p-2.5 hover:bg-blue-50 cursor-pointer flex items-center justify-between text-xs">
                                        <div>
                                            <div class="font-bold text-slate-800">{{ g.nama_lengkap }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ g.nip || g.nuptk || '-' }}</div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-2xs font-semibold">Pilih</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Mata Pelajaran</label>
                                <input type="text" v-model="form.mata_pelajaran" placeholder="Contoh: Bahasa Indonesia" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Kelas / Rombel</label>
                                <input type="text" v-model="form.kelas_rombel" placeholder="Contoh: X IPA 1 / XI RPL" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Tanggal Observasi *</label>
                                <input type="date" v-model="form.tanggal_supervisi" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Tahun Ajaran</label>
                                <input type="text" v-model="form.tahun_ajaran" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Semester</label>
                                <select v-model="form.semester" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Jenis Supervisi *</label>
                                <select v-model="form.jenis_supervisi" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                                    <option value="Supervisi Akademik/Kelas">Supervisi Akademik / Kelas</option>
                                    <option value="Supervisi Perangkat Ajar">Supervisi Perangkat Ajar (RPP/Modul)</option>
                                    <option value="Pembinaan Disiplin & Etika">Pembinaan Disiplin & Etika</option>
                                    <option value="Supervisi Manajerial">Supervisi Manajerial</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Status Pembinaan *</label>
                                <select v-model="form.status_pembinaan" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                                    <option value="Selesai Dibina">Selesai Dibina</option>
                                    <option value="Dalam Proses">Dalam Proses</option>
                                    <option value="Terjadwal">Terjadwal</option>
                                    <option value="Butuh Pendampingan Khusus">Butuh Pendampingan Khusus</option>
                                </select>
                            </div>
                        </div>

                        <!-- 4 Nilai Kompetensi Slider / Number -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                <span class="text-xs font-bold text-slate-700">Penilaian 4 Aspek Kompetensi Guru (0-100)</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black text-slate-800">Skor: {{ calculatedScore }}</span>
                                    <span class="px-2 py-0.5 rounded text-2xs font-bold border" :class="calculatedPredikat.color">
                                        {{ calculatedPredikat.label }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-blue-700 uppercase mb-0.5">Pedagogik</label>
                                    <input type="number" min="0" max="100" v-model.number="form.skor_pedagogik" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-center">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-indigo-700 uppercase mb-0.5">Profesional</label>
                                    <input type="number" min="0" max="100" v-model.number="form.skor_profesional" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-center">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-emerald-700 uppercase mb-0.5">Kepribadian</label>
                                    <input type="number" min="0" max="100" v-model.number="form.skor_kepribadian" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-center">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-amber-700 uppercase mb-0.5">Sosial</label>
                                    <input type="number" min="0" max="100" v-model.number="form.skor_sosial" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-center">
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Observasi & Rekomendasi -->
                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Catatan Hasil Observasi</label>
                            <textarea v-model="form.catatan_observasi" rows="2" placeholder="Temuan dan evaluasi pelaksanaan pembelajaran di kelas..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Rekomendasi Pembinaan</label>
                                <textarea v-model="form.rekomendasi_pembinaan" rows="2" placeholder="Saran perbaikan untuk guru..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs"></textarea>
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Program Tindak Lanjut</label>
                                <textarea v-model="form.tindak_lanjut" rows="2" placeholder="Pelatihan, mentoring, atau tindak lanjut..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs"></textarea>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-200 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing || !form.nama_guru" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition disabled:opacity-50">
                                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui Data' : 'Simpan Supervisi') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- DETAIL MODAL OBSERVASI (<Teleport to="body">) -->
        <Teleport to="body">
            <div v-if="isDetailModalOpen && selectedDetail" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-100">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <span class="text-2xs font-bold text-blue-600 uppercase">Lembar Hasil Supervisi</span>
                            <h2 class="text-base font-black text-slate-800">{{ selectedDetail.nama_guru }}</h2>
                        </div>
                        <button @click="isDetailModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <div class="mt-4 space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200/70">
                            <div>
                                <span class="text-slate-400 text-2xs block">Mata Pelajaran & Rombel</span>
                                <span class="font-bold text-slate-800">{{ selectedDetail.mata_pelajaran || 'Umum' }} ({{ selectedDetail.kelas_rombel || '-' }})</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-2xs block">Tanggal Observasi</span>
                                <span class="font-bold text-slate-800">{{ selectedDetail.tanggal_supervisi ? selectedDetail.tanggal_supervisi.split('T')[0] : '-' }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div class="bg-blue-50 p-2.5 rounded-xl border border-blue-100">
                                <span class="text-[10px] text-blue-600 block uppercase font-bold">Pedagogik</span>
                                <span class="text-base font-black text-blue-800">{{ selectedDetail.skor_pedagogik }}</span>
                            </div>
                            <div class="bg-indigo-50 p-2.5 rounded-xl border border-indigo-100">
                                <span class="text-[10px] text-indigo-600 block uppercase font-bold">Profesional</span>
                                <span class="text-base font-black text-indigo-800">{{ selectedDetail.skor_profesional }}</span>
                            </div>
                            <div class="bg-emerald-50 p-2.5 rounded-xl border border-emerald-100">
                                <span class="text-[10px] text-emerald-600 block uppercase font-bold">Kepribadian</span>
                                <span class="text-base font-black text-emerald-800">{{ selectedDetail.skor_kepribadian }}</span>
                            </div>
                            <div class="bg-amber-50 p-2.5 rounded-xl border border-amber-100">
                                <span class="text-[10px] text-amber-600 block uppercase font-bold">Sosial</span>
                                <span class="text-base font-black text-amber-800">{{ selectedDetail.skor_sosial }}</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div>
                                <span class="font-bold text-slate-700 block">Catatan Observasi:</span>
                                <p class="text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100">{{ selectedDetail.catatan_observasi || 'Tidak ada catatan khusus.' }}</p>
                            </div>
                            <div v-if="selectedDetail.rekomendasi_pembinaan">
                                <span class="font-bold text-blue-700 block">Rekomendasi Pembinaan:</span>
                                <p class="text-slate-600 bg-blue-50/50 p-2.5 rounded-lg border border-blue-100">{{ selectedDetail.rekomendasi_pembinaan }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-3 border-t border-slate-100 flex justify-end">
                        <button @click="isDetailModalOpen = false" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
