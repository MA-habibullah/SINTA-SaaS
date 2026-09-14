<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'

const props = defineProps({
    isSuperAdmin: Boolean,
    selectedTenantId: String,
    tenantsList: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    activeTahunAjaran: { type: String, default: '2026/2027' },
    activeSemester: { type: String, default: 'Ganjil' },
    tahunAjaranList: { type: Array, default: () => [] },
    kelasList: { type: Array, default: () => [] },
    mapelList: { type: Array, default: () => [] },
    guruList: { type: Array, default: () => [] },
    ruangList: { type: Array, default: () => [] },
    daysList: { type: Array, default: () => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] },
    standardTimeSlots: { type: Array, default: () => [] },
    jadwalTable: { type: Object, default: () => ({ data: [] }) },
    matrixItems: { type: Array, default: () => [] },
    allPeriodJadwals: { type: Array, default: () => [] },
    bebanGuruList: { type: Array, default: () => [] },
    ruangUtilList: { type: Array, default: () => [] },
    conflictList: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

// Active Tab
const activeTab = ref(props.filters.view_mode || 'grid') // 'grid' | 'table' | 'beban_guru' | 'ruang_matrix' | 'conflict'

// Grid Focus Mode: 'kelas' | 'guru' | 'ruangan'
const gridFocus = ref('kelas')
const selectedFocusKelas = ref(props.filters.kelas_id || (props.kelasList[0]?.id || ''))
const selectedFocusGuru = ref(props.filters.guru_id || (props.guruList[0]?.id || ''))
const selectedFocusRuang = ref(props.filters.ruangan || (props.ruangList[0] || ''))

// Filter States
const currentTenantId = ref(props.selectedTenantId || '')
const currentTahunAjaran = ref(props.activeTahunAjaran)
const currentSemester = ref(props.activeSemester)
const currentKelasId = ref(props.filters.kelas_id || '')
const currentGuruId = ref(props.filters.guru_id || '')
const currentHari = ref(props.filters.hari || '')
const currentRuangan = ref(props.filters.ruangan || '')
const searchQuery = ref(props.filters.search || '')

// Modals State
const isModalFormOpen = ref(false)
const isModalImportOpen = ref(false)
const isModalCopyOpen = ref(false)
const isEditing = ref(false)
const editId = ref(null)

// Conflict checking state in form modal
const isCheckingConflict = ref(false)
const liveConflictWarnings = ref([])

// Form Input / Edit
const form = useForm({
    tenant_id: props.selectedTenantId || '',
    tahun_ajaran: props.activeTahunAjaran,
    semester: props.activeSemester,
    kelas_id: '',
    mapel_id: '',
    guru_id: '',
    hari: 'Senin',
    jam_ke: '1-2',
    jam_mulai: '07:00',
    jam_selesai: '08:30',
    ruangan: 'R. 101',
    jam_pelajaran: 2,
    kkm: 75,
    warna_label: '#3b82f6',
    catatan: '',
    force_override: false,
})

// Form Copy
const formCopy = useForm({
    tenant_id: props.selectedTenantId || '',
    from_tahun_ajaran: props.activeTahunAjaran,
    from_semester: props.activeSemester,
    to_tahun_ajaran: props.activeTahunAjaran,
    to_semester: props.activeSemester === 'Ganjil' ? 'Genap' : 'Ganjil',
})

// Import State
const importStep = ref(1) // 1 = Upload, 2 = Preview & Confirm
const importFile = ref(null)
const isUploading = ref(false)
const previewResult = ref({
    total_rows: 0,
    valid_count: 0,
    conflict_count: 0,
    error_count: 0,
    rows: [],
})
const previewTab = ref('all') // 'all' | 'valid' | 'conflict' | 'invalid'
const isCommitting = ref(false)
const skipImportConflicts = ref(true)

// Color Palette Options for Subjects
const colorPalette = [
    { label: 'Biru', value: '#3b82f6', bg: 'bg-blue-500' },
    { label: 'Hijau Emerald', value: '#10b981', bg: 'bg-emerald-500' },
    { label: 'Ungu Violet', value: '#8b5cf6', bg: 'bg-purple-500' },
    { label: 'Kuning Amber', value: '#f59e0b', bg: 'bg-amber-500' },
    { label: 'Merah Muda Pink', value: '#ec4899', bg: 'bg-pink-500' },
    { label: 'Biru Cyan', value: '#06b6d4', bg: 'bg-cyan-500' },
    { label: 'Merah Rose', value: '#f43f5e', bg: 'bg-rose-500' },
    { label: 'Abu-Abu Slate', value: '#64748b', bg: 'bg-slate-500' },
]

// Preset Time Slots Shortcut
const presetSlots = [
    { label: 'Jam 1-2 (07:00 - 08:30)', jam_ke: '1-2', mulai: '07:00', selesai: '08:30', jp: 2 },
    { label: 'Jam 3-4 (08:30 - 10:00)', jam_ke: '3-4', mulai: '08:30', selesai: '10:00', jp: 2 },
    { label: 'Jam 5-6 (10:15 - 11:45)', jam_ke: '5-6', mulai: '10:15', selesai: '11:45', jp: 2 },
    { label: 'Jam 7-8 (12:30 - 14:00)', jam_ke: '7-8', mulai: '12:30', selesai: '14:00', jp: 2 },
    { label: 'Jam 9-10 (14:00 - 15:30)', jam_ke: '9-10', mulai: '14:00', selesai: '15:30', jp: 2 },
]

// Dropdown Options Helpers
const tenantOptions = computed(() => {
    const list = [{ value: '', label: 'Semua Sekolah / Tenant Default' }]
    props.tenantsList.forEach(t => {
        list.push({ value: t.id, label: t.nama + (t.npsn ? ` (NPSN: ${t.npsn})` : '') })
    })
    return list
})

const tahunAjaranOptions = computed(() => {
    return props.tahunAjaranList.map(ta => ({ value: ta, label: `Tahun Ajaran ${ta}` }))
})

const semesterOptions = [
    { value: 'Ganjil', label: 'Semester Ganjil' },
    { value: 'Genap', label: 'Semester Genap' },
]

const kelasOptions = computed(() => {
    const list = [{ value: '', label: 'Semua Kelas (Rombel)' }]
    props.kelasList.forEach(k => {
        list.push({ value: k.id, label: k.nama_kelas + (k.tingkat ? ` (Tingkat ${k.tingkat})` : '') })
    })
    return list
})

const kelasFormOptions = computed(() => {
    return props.kelasList.map(k => ({ value: k.id, label: k.nama_kelas + (k.tingkat ? ` (Tingkat ${k.tingkat})` : '') }))
})

const mapelOptions = computed(() => {
    return props.mapelList.map(m => ({ value: m.id, label: m.nama_mata_pelajaran + (m.kategori ? ` [${m.kategori}]` : '') }))
})

const guruOptions = computed(() => {
    const list = [{ value: '', label: 'Semua Guru Pengampu' }]
    props.guruList.forEach(g => {
        list.push({ value: g.id, label: g.nama_lengkap + (g.nip ? ` - NIP. ${g.nip}` : '') })
    })
    return list
})

const guruFormOptions = computed(() => {
    const list = [{ value: '', label: '-- Belum Ditentukan / Guru Pengganti --' }]
    props.guruList.forEach(g => {
        list.push({ value: g.id, label: g.nama_lengkap + (g.nip ? ` - NIP. ${g.nip}` : '') })
    })
    return list
})

const hariOptions = [
    { value: '', label: 'Semua Hari' },
    { value: 'Senin', label: 'Senin' },
    { value: 'Selasa', label: 'Selasa' },
    { value: 'Rabu', label: 'Rabu' },
    { value: 'Kamis', label: 'Kamis' },
    { value: 'Jumat', label: 'Jumat' },
    { value: 'Sabtu', label: 'Sabtu' },
]

const hariFormOptions = [
    { value: 'Senin', label: 'Senin' },
    { value: 'Selasa', label: 'Selasa' },
    { value: 'Rabu', label: 'Rabu' },
    { value: 'Kamis', label: 'Kamis' },
    { value: 'Jumat', label: 'Jumat' },
    { value: 'Sabtu', label: 'Sabtu' },
]

const ruangOptions = computed(() => {
    const list = [{ value: '', label: 'Semua Ruangan' }]
    props.ruangList.forEach(r => {
        list.push({ value: r, label: r })
    })
    return list
})

const ruangFormOptions = computed(() => {
    return props.ruangList.map(r => ({ value: r, label: r }))
})

// Filter Handler
function applyFilter() {
    router.get('/akademik/jadwal', {
        tenant_id: currentTenantId.value || undefined,
        tahun_ajaran: currentTahunAjaran.value,
        semester: currentSemester.value,
        kelas_id: currentKelasId.value || undefined,
        guru_id: currentGuruId.value || undefined,
        hari: currentHari.value || undefined,
        ruangan: currentRuangan.value || undefined,
        search: searchQuery.value || undefined,
        view_mode: activeTab.value,
    }, { preserveState: true, preserveScroll: true })
}

function switchTenant(tId) {
    currentTenantId.value = tId
    applyFilter()
}

function switchTab(tab) {
    activeTab.value = tab
    applyFilter()
}

// Matrix Cell Data Resolution
function getCellSchedules(day, slot) {
    return props.allPeriodJadwals.filter(item => {
        if (item.hari !== day) return false

        // Match focus view
        if (gridFocus.value === 'kelas' && selectedFocusKelas.value) {
            if (item.kelas_id !== selectedFocusKelas.value) return false
        } else if (gridFocus.value === 'guru' && selectedFocusGuru.value) {
            if (item.guru_id !== selectedFocusGuru.value) return false
        } else if (gridFocus.value === 'ruangan' && selectedFocusRuang.value) {
            if (item.ruangan !== selectedFocusRuang.value) return false
        }

        // Match time slot overlap
        const itemStart = (item.jam_mulai || '').substring(0, 5)
        const itemEnd = (item.jam_selesai || '').substring(0, 5)
        const slotStart = (slot.jam_mulai || '').substring(0, 5)
        const slotEnd = (slot.jam_selesai || '').substring(0, 5)

        // Exact jam_ke match or time overlap
        if (item.jam_ke && slot.jam_ke && item.jam_ke.includes(slot.jam_ke)) {
            return true
        }

        if (itemStart && itemEnd && slotStart && slotEnd) {
            return (itemStart < slotEnd) && (itemEnd > slotStart)
        }

        return false
    })
}

// Quick Add Inline from Matrix Slot
function openCreateFromSlot(day, slot) {
    isEditing.value = false
    editId.value = null
    form.reset()
    form.tenant_id = currentTenantId.value || props.selectedTenantId || ''
    form.tahun_ajaran = currentTahunAjaran.value
    form.semester = currentSemester.value
    form.hari = day
    form.jam_ke = slot.jam_ke || '1-2'
    form.jam_mulai = slot.jam_mulai || '07:00'
    form.jam_selesai = slot.jam_selesai || '08:30'
    form.jam_pelajaran = 2
    form.kkm = 75
    form.warna_label = '#3b82f6'

    if (gridFocus.value === 'kelas' && selectedFocusKelas.value) {
        form.kelas_id = selectedFocusKelas.value
    } else if (props.kelasList.length > 0) {
        form.kelas_id = props.kelasList[0].id
    }

    if (gridFocus.value === 'guru' && selectedFocusGuru.value) {
        form.guru_id = selectedFocusGuru.value
    }

    if (gridFocus.value === 'ruangan' && selectedFocusRuang.value) {
        form.ruangan = selectedFocusRuang.value
    } else if (props.ruangList.length > 0) {
        form.ruangan = props.ruangList[0]
    }

    if (props.mapelList.length > 0) {
        form.mapel_id = props.mapelList[0].id
    }

    liveConflictWarnings.value = []
    isModalFormOpen.value = true
    checkConflictLive()
}

// Open Edit Modal
function openEditModal(item) {
    isEditing.value = true
    editId.value = item.id
    form.tenant_id = item.tenant_id || ''
    form.tahun_ajaran = item.tahun_ajaran || currentTahunAjaran.value
    form.semester = item.semester || currentSemester.value
    form.kelas_id = item.kelas_id || ''
    form.mapel_id = item.mapel_id || ''
    form.guru_id = item.guru_id || ''
    form.hari = item.hari || 'Senin'
    form.jam_ke = item.jam_ke || '1-2'
    form.jam_mulai = item.jam_mulai || '07:00'
    form.jam_selesai = item.jam_selesai || '08:30'
    form.ruangan = item.ruangan || 'R. 101'
    form.jam_pelajaran = item.jam_pelajaran || 2
    form.kkm = item.kkm || 75
    form.warna_label = item.warna_label || '#3b82f6'
    form.catatan = item.catatan || ''
    form.force_override = false

    liveConflictWarnings.value = []
    isModalFormOpen.value = true
    checkConflictLive()
}

// Open Create Modal Generic
function openCreateModal() {
    isEditing.value = false
    editId.value = null
    form.reset()
    form.tenant_id = currentTenantId.value || props.selectedTenantId || ''
    form.tahun_ajaran = currentTahunAjaran.value
    form.semester = currentSemester.value
    form.kelas_id = props.kelasList[0]?.id || ''
    form.mapel_id = props.mapelList[0]?.id || ''
    form.guru_id = props.guruList[0]?.id || ''
    form.ruangan = props.ruangList[0] || 'R. 101'
    form.hari = 'Senin'
    form.jam_ke = '1-2'
    form.jam_mulai = '07:00'
    form.jam_selesai = '08:30'
    form.jam_pelajaran = 2
    form.kkm = 75
    form.warna_label = '#3b82f6'
    form.force_override = false

    liveConflictWarnings.value = []
    isModalFormOpen.value = true
    checkConflictLive()
}

// Preset Slot Click in Form
function applyPresetSlot(slot) {
    form.jam_ke = slot.jam_ke
    form.jam_mulai = slot.mulai
    form.jam_selesai = slot.selesai
    form.jam_pelajaran = slot.jp
    checkConflictLive()
}

// Live Conflict Checker via Axios
async function checkConflictLive() {
    if (!form.tahun_ajaran || !form.semester || !form.hari || !form.jam_mulai || !form.jam_selesai || !form.kelas_id) {
        liveConflictWarnings.value = []
        return
    }

    isCheckingConflict.value = true
    try {
        const res = await window.axios.post('/akademik/jadwal/check-conflict', {
            id: isEditing.value ? editId.value : null,
            tenant_id: form.tenant_id || currentTenantId.value,
            tahun_ajaran: form.tahun_ajaran,
            semester: form.semester,
            hari: form.hari,
            jam_mulai: form.jam_mulai,
            jam_selesai: form.jam_selesai,
            kelas_id: form.kelas_id,
            guru_id: form.guru_id,
            ruangan: form.ruangan,
        })

        if (res.data?.success) {
            liveConflictWarnings.value = res.data.conflicts || []
        }
    } catch (err) {
        console.error('Error checking conflict:', err)
    } finally {
        isCheckingConflict.value = false
    }
}

// Submit Schedule Form
function submitScheduleForm() {
    if (isEditing.value && editId.value) {
        form.put(`/akademik/jadwal/${editId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isModalFormOpen.value = false
            },
        })
    } else {
        form.post('/akademik/jadwal', {
            preserveScroll: true,
            onSuccess: () => {
                isModalFormOpen.value = false
            },
        })
    }
}

// Delete Schedule
function deleteSchedule(item) {
    if (confirm(`Apakah Anda yakin ingin menghapus jadwal "${item.nama_pemetaan_mapel}" pada hari ${item.hari}?`)) {
        router.delete(`/akademik/jadwal/${item.id}`, { preserveScroll: true })
    }
}

// Submit Copy Schedule
function submitCopySchedule() {
    formCopy.post('/akademik/jadwal/copy', {
        preserveScroll: true,
        onSuccess: () => {
            isModalCopyOpen.value = false
        },
    })
}

// Handle Import File Change
function onImportFileSelected(event) {
    const files = event.target.files
    if (files && files[0]) {
        importFile.value = files[0]
    }
}

// Upload & Preview Import File
async function uploadAndPreviewImport() {
    if (!importFile.value) {
        alert('Silakan pilih berkas Excel (.xlsx / .xls / .csv) terlebih dahulu.')
        return
    }

    isUploading.value = true
    const formData = new FormData()
    formData.append('file', importFile.value)
    if (currentTenantId.value) {
        formData.append('tenant_id', currentTenantId.value)
    }

    try {
        const res = await window.axios.post('/akademik/jadwal/preview-import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })

        if (res.data?.success) {
            previewResult.value = res.data
            importStep.value = 2 // Move to preview step
            previewTab.value = 'all'
        } else {
            alert(res.data?.message || 'Gagal memproses file Excel.')
        }
    } catch (err) {
        const errorMsg = err.response?.data?.message || 'Terjadi kesalahan saat memproses file Excel.'
        alert(errorMsg)
    } finally {
        isUploading.value = false
    }
}

// Filtered Preview Rows
const filteredPreviewRows = computed(() => {
    if (!previewResult.value.rows) return []
    if (previewTab.value === 'all') return previewResult.value.rows
    return previewResult.value.rows.filter(r => r.status === previewTab.value)
})

// Commit Approved Import Rows
async function commitApprovedImport() {
    // Only import valid rows (or valid + conflict if skip_conflicts is false)
    const validRowsToSubmit = previewResult.value.rows.filter(r => r.status === 'valid' || (!skipImportConflicts.value && r.status === 'conflict'))

    if (validRowsToSubmit.length === 0) {
        alert('Tidak ada baris data jadwal valid yang siap disimpan.')
        return
    }

    isCommitting.value = true
    try {
        const res = await window.axios.post('/akademik/jadwal/import', {
            tenant_id: currentTenantId.value || undefined,
            rows: validRowsToSubmit,
            skip_conflicts: skipImportConflicts.value,
        })

        if (res.data?.success) {
            alert(res.data.message || 'Import data jadwal berhasil diselesaikan.')
            isModalImportOpen.value = false
            importStep.value = 1
            importFile.value = null
            router.reload({ preserveScroll: true })
        } else {
            alert(res.data?.message || 'Gagal menyimpan data import.')
        }
    } catch (err) {
        const msg = err.response?.data?.message || 'Gagal menyimpan data import ke database.'
        alert(msg)
    } finally {
        isCommitting.value = false
    }
}

// Download Excel Direct Link
function triggerExport() {
    const params = new URLSearchParams({
        tenant_id: currentTenantId.value || '',
        tahun_ajaran: currentTahunAjaran.value || '',
        semester: currentSemester.value || '',
        kelas_id: currentKelasId.value || '',
        guru_id: currentGuruId.value || '',
        ruangan: currentRuangan.value || '',
        hari: currentHari.value || '',
    })
    window.location.href = `/akademik/jadwal/export?${params.toString()}`
}

function triggerDownloadTemplate() {
    const params = new URLSearchParams({
        tenant_id: currentTenantId.value || '',
    })
    window.location.href = `/akademik/jadwal/template?${params.toString()}`
}
</script>

<template>
    <AppLayout title="Manajemen Jadwal Pelajaran">
        <div class="space-y-6">

            <!-- Banner Super Admin Tenant Switcher -->
            <div v-if="isSuperAdmin" class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-3xl p-5 text-white shadow-xl border border-blue-700/50">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center border border-white/20 backdrop-blur-xs shrink-0">
                            <i class="bi bi-building-gear text-2xl text-blue-300"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-0.5 rounded-full text-2xs font-bold uppercase tracking-wider bg-blue-500/30 text-blue-200 border border-blue-400/30">
                                    Mode Super Admin
                                </span>
                                <span class="text-xs text-blue-200">Multi-Tenant Timetable Scheduler</span>
                            </div>
                            <h2 class="text-lg font-bold text-white tracking-tight mt-0.5">
                                Filter Unit Sekolah / Tenant Akademik
                            </h2>
                        </div>
                    </div>
                    <div class="w-full lg:w-96">
                        <SearchableSelect
                            v-model="currentTenantId"
                            :options="tenantOptions"
                            placeholder="Pilih Sekolah / Tenant..."
                            @change="switchTenant"
                        />
                    </div>
                </div>
            </div>

            <!-- Header Halaman & Tombol Aksi Utama -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
                <div>
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 shadow-xs shrink-0">
                            <i class="bi bi-calendar3-week text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Jadwal Pelajaran</h1>
                            <p class="text-sm text-slate-500 font-medium">
                                Matriks Timetable Interaktif, Deteksi Bentrok Otomatis, & Ekspor-Import Excel
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Button Group -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition flex items-center shadow-xs cursor-pointer"
                        @click="openCreateModal"
                    >
                        <i class="bi bi-plus-circle me-2 text-sm"></i>
                        Tambah Jadwal
                    </button>

                    <button
                        type="button"
                        class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition flex items-center shadow-xs cursor-pointer"
                        @click="isModalImportOpen = true; importStep = 1; importFile = null;"
                        title="Import data jadwal dari Excel (.xlsx)"
                    >
                        <i class="bi bi-file-earmark-excel me-1.5 text-sm"></i>
                        Import Excel
                    </button>

                    <button
                        type="button"
                        class="px-3.5 py-2.5 rounded-xl bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition flex items-center shadow-2xs cursor-pointer"
                        @click="triggerExport"
                        title="Download jadwal terfilter dalam file Excel (.xlsx)"
                    >
                        <i class="bi bi-download me-1.5 text-slate-500"></i>
                        Ekspor Excel
                    </button>

                    <button
                        type="button"
                        class="px-3.5 py-2.5 rounded-xl bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition flex items-center shadow-2xs cursor-pointer"
                        @click="isModalCopyOpen = true"
                        title="Salin data jadwal dari semester sebelumnya"
                    >
                        <i class="bi bi-copy me-1.5 text-slate-500"></i>
                        Salin Periode
                    </button>
                </div>
            </div>

            <!-- 5 KPI Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- Card 1: Total Jadwal -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Jadwal</p>
                            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ stats.total_jadwal || 0 }}</h3>
                            <span class="text-2xs text-slate-400 font-medium">Sesi aktif per minggu</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-calendar-check text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Total Guru Mengajar -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Guru Terjadwal</p>
                            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ stats.total_guru || 0 }}</h3>
                            <span class="text-2xs text-slate-400 font-medium">Pengampu aktif</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-person-workspace text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Total Jam Pelajaran (JP) -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Beban JP</p>
                            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ stats.total_jp || 0 }}</h3>
                            <span class="text-2xs text-slate-400 font-medium">Jam Pelajaran / Pekan</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-clock-history text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Ruangan Terpakai -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ruang & Lab</p>
                            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ stats.total_ruang || 0 }}</h3>
                            <span class="text-2xs text-slate-400 font-medium">Ruang kelas aktif</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-door-open text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 5: Potensi Bentrok (Collision Warning) -->
                <div class="col-span-2 lg:col-span-1 rounded-2xl p-4 border shadow-2xs relative overflow-hidden transition"
                     :class="(stats.total_bentrok || 0) > 0 ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-white border-slate-200/80 text-slate-900'">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider" :class="(stats.total_bentrok || 0) > 0 ? 'text-rose-600 font-bold' : 'text-slate-500'">
                                Audit Bentrok
                            </p>
                            <h3 class="text-2xl font-black mt-1" :class="(stats.total_bentrok || 0) > 0 ? 'text-rose-700' : 'text-slate-900'">
                                {{ stats.total_bentrok || 0 }}
                            </h3>
                            <span class="text-2xs font-medium" :class="(stats.total_bentrok || 0) > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'">
                                {{ (stats.total_bentrok || 0) > 0 ? '⚠️ Butuh Perbaikan Segera' : '✅ 100% Jadwal Bersih' }}
                            </span>
                        </div>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                             :class="(stats.total_bentrok || 0) > 0 ? 'bg-rose-100 text-rose-700 animate-pulse' : 'bg-slate-100 text-slate-500'">
                            <i class="bi" :class="(stats.total_bentrok || 0) > 0 ? 'bi-exclamation-triangle-fill text-xl' : 'bi-shield-check text-xl'"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Filter Bar (TA, Semester, Kelas, Guru, Hari, Ruangan, Search) -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5">
                    <!-- Tahun Ajaran -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 mb-1">Tahun Ajaran</label>
                        <SearchableSelect
                            v-model="currentTahunAjaran"
                            :options="tahunAjaranOptions"
                            placeholder="Pilih Tahun Ajaran..."
                            @change="applyFilter"
                        />
                    </div>

                    <!-- Semester -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 mb-1">Semester</label>
                        <SearchableSelect
                            v-model="currentSemester"
                            :options="semesterOptions"
                            placeholder="Pilih Semester..."
                            @change="applyFilter"
                        />
                    </div>

                    <!-- Filter Kelas -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 mb-1">Kelas (Rombel)</label>
                        <SearchableSelect
                            v-model="currentKelasId"
                            :options="kelasOptions"
                            placeholder="Semua Kelas..."
                            @change="applyFilter"
                        />
                    </div>

                    <!-- Filter Guru -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 mb-1">Guru Pengampu</label>
                        <SearchableSelect
                            v-model="currentGuruId"
                            :options="guruOptions"
                            placeholder="Semua Guru..."
                            @change="applyFilter"
                        />
                    </div>

                    <!-- Filter Hari -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 mb-1">Hari</label>
                        <SearchableSelect
                            v-model="currentHari"
                            :options="hariOptions"
                            placeholder="Semua Hari..."
                            @change="applyFilter"
                        />
                    </div>

                    <!-- Filter Ruangan -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 mb-1">Ruangan / Lab</label>
                        <SearchableSelect
                            v-model="currentRuangan"
                            :options="ruangOptions"
                            placeholder="Semua Ruang..."
                            @change="applyFilter"
                        />
                    </div>
                </div>

                <!-- Live Keyword Search & Reset Button -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100 gap-3">
                    <div class="relative grow max-w-md">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari mapel, guru, ruangan, atau kelas..."
                            class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200/90 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-hidden font-medium"
                            @keyup.enter="applyFilter"
                        />
                    </div>

                    <div class="flex items-center space-x-2">
                        <button
                            type="button"
                            class="px-3.5 py-2 text-xs font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-xl transition cursor-pointer"
                            @click="applyFilter"
                        >
                            <i class="bi bi-funnel me-1"></i> Terapkan Filter
                        </button>
                        <button
                            type="button"
                            class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition cursor-pointer"
                            @click="currentKelasId = ''; currentGuruId = ''; currentHari = ''; currentRuangan = ''; searchQuery = ''; applyFilter();"
                            title="Reset seluruh filter"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modern Pill NavTabs Scroller (3-Way Interaction) -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <!-- Chevron Scroll Left -->
                    <button
                        type="button"
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5 cursor-pointer"
                        onclick="document.getElementById('jadwalNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                        title="Geser ke Kiri"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <!-- NavTabs Container -->
                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="jadwalNavTabs" role="tablist">
                            <!-- Tab 1: Matriks Jadwal Grid -->
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center cursor-pointer"
                                    :class="activeTab === 'grid' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('grid')"
                                >
                                    <i class="bi bi-grid-3x3 me-2 text-sm"></i>
                                    Matriks Jadwal (Timetable Grid)
                                </button>
                            </li>

                            <!-- Tab 2: Tabel Lengkap -->
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center cursor-pointer"
                                    :class="activeTab === 'table' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('table')"
                                >
                                    <i class="bi bi-table me-2 text-sm"></i>
                                    Daftar & Tabel Jadwal
                                </button>
                            </li>

                            <!-- Tab 3: Peta Beban Guru -->
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center cursor-pointer"
                                    :class="activeTab === 'beban_guru' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('beban_guru')"
                                >
                                    <i class="bi bi-person-badge me-2 text-sm"></i>
                                    Peta Beban Mengajar Guru
                                </button>
                            </li>

                            <!-- Tab 4: Peta Utilisasi Ruangan -->
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center cursor-pointer"
                                    :class="activeTab === 'ruang_matrix' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('ruang_matrix')"
                                >
                                    <i class="bi bi-door-open me-2 text-sm"></i>
                                    Utilisasi Ruang & Lab
                                </button>
                            </li>

                            <!-- Tab 5: Audit & Deteksi Bentrok -->
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center cursor-pointer"
                                    :class="activeTab === 'conflict' ? 'bg-rose-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('conflict')"
                                >
                                    <i class="bi bi-exclamation-triangle me-2 text-sm"></i>
                                    Deteksi Bentrok Jadwal
                                    <span v-if="(stats.total_bentrok || 0) > 0" class="ms-1.5 px-2 py-0.5 rounded-full text-2xs bg-white text-rose-700 font-black">
                                        {{ stats.total_bentrok }}
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Chevron Scroll Right -->
                    <button
                        type="button"
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5 cursor-pointer"
                        onclick="document.getElementById('jadwalNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                        title="Geser ke Kanan"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- TAB 1: MATRIKS JADWAL PELAJARAN (TIMETABLE GRID) -->
            <div v-if="activeTab === 'grid'" class="space-y-4">
                <!-- Focus Switcher Header -->
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tampilan Matriks:</span>
                        <div class="inline-flex rounded-xl p-1 bg-slate-100 border border-slate-200/70">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                                :class="gridFocus === 'kelas' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                @click="gridFocus = 'kelas'"
                            >
                                <i class="bi bi-people me-1"></i> Per Kelas
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                                :class="gridFocus === 'guru' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                @click="gridFocus = 'guru'"
                            >
                                <i class="bi bi-person-badge me-1"></i> Per Guru
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                                :class="gridFocus === 'ruangan' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                @click="gridFocus = 'ruangan'"
                            >
                                <i class="bi bi-door-closed me-1"></i> Per Ruangan
                            </button>
                        </div>
                    </div>

                    <!-- Target Selector based on Focus -->
                    <div class="w-full md:w-80">
                        <div v-if="gridFocus === 'kelas'">
                            <SearchableSelect
                                v-model="selectedFocusKelas"
                                :options="kelasFormOptions"
                                placeholder="Pilih Kelas Target..."
                            />
                        </div>
                        <div v-else-if="gridFocus === 'guru'">
                            <SearchableSelect
                                v-model="selectedFocusGuru"
                                :options="guruFormOptions"
                                placeholder="Pilih Guru Target..."
                            />
                        </div>
                        <div v-else-if="gridFocus === 'ruangan'">
                            <SearchableSelect
                                v-model="selectedFocusRuang"
                                :options="ruangFormOptions"
                                placeholder="Pilih Ruang Target..."
                            />
                        </div>
                    </div>
                </div>

                <!-- Timetable Matrix Visual Grid Table -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse min-w-[900px]">
                            <!-- Table Head (Days) -->
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200/80 text-slate-700">
                                    <th class="py-3.5 px-4 font-bold uppercase tracking-wider text-center w-36 border-r border-slate-200/80">
                                        Jam / Waktu
                                    </th>
                                    <th
                                        v-for="day in daysList"
                                        :key="day"
                                        class="py-3.5 px-4 font-bold uppercase tracking-wider text-center border-r border-slate-200/80 last:border-r-0"
                                        :class="day === 'Jumat' ? 'bg-blue-50/30' : ''"
                                    >
                                        {{ day }}
                                    </th>
                                </tr>
                            </thead>

                            <!-- Table Body (Time Slots Rows) -->
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="slot in standardTimeSlots" :key="slot.jam_ke" class="hover:bg-slate-50/50 transition">
                                    <!-- Time Column -->
                                    <td class="py-3 px-3 text-center bg-slate-50/70 border-r border-slate-200/80 align-top">
                                        <div class="font-extrabold text-slate-800 text-xs">
                                            Jam Ke-{{ slot.jam_ke }}
                                        </div>
                                        <div class="text-2xs font-semibold text-slate-500 mt-0.5">
                                            {{ slot.jam_mulai }} - {{ slot.jam_selesai }}
                                        </div>
                                    </td>

                                    <!-- Day Columns -->
                                    <td
                                        v-for="day in daysList"
                                        :key="day"
                                        class="p-2 border-r border-slate-100 last:border-r-0 align-top h-24 min-w-[140px]"
                                        :class="day === 'Jumat' ? 'bg-blue-50/10' : ''"
                                    >
                                        <!-- If Schedules Exist in this Cell -->
                                        <div v-if="getCellSchedules(day, slot).length > 0" class="space-y-1.5">
                                            <div
                                                v-for="jadwal in getCellSchedules(day, slot)"
                                                :key="jadwal.id"
                                                class="rounded-xl p-2.5 text-white shadow-xs relative group transition transform hover:-translate-y-0.5"
                                                :style="{ backgroundColor: jadwal.warna_label || '#3b82f6' }"
                                                :class="jadwal.has_conflict ? 'ring-2 ring-rose-500 animate-pulse' : ''"
                                            >
                                                <!-- Conflict Badge Indicator -->
                                                <div v-if="jadwal.has_conflict" class="absolute -top-1.5 -right-1.5 px-1.5 py-0.5 bg-rose-600 text-white rounded-full text-3xs font-black shadow-xs flex items-center space-x-1" title="Terdeteksi Bentrok Jadwal!">
                                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                                    <span>BENTROK</span>
                                                </div>

                                                <!-- Subject Title -->
                                                <div class="font-bold text-xs leading-snug drop-shadow-xs line-clamp-2">
                                                    {{ jadwal.mapel?.nama_mata_pelajaran || jadwal.nama_pemetaan_mapel }}
                                                </div>

                                                <!-- Teacher & Class Subtitles -->
                                                <div class="mt-1 text-3xs opacity-90 space-y-0.5 font-medium">
                                                    <div v-if="gridFocus !== 'kelas'" class="flex items-center space-x-1">
                                                        <i class="bi bi-people"></i>
                                                        <span class="font-bold truncate">{{ jadwal.kelas?.nama_kelas || '-' }}</span>
                                                    </div>
                                                    <div v-if="gridFocus !== 'guru'" class="flex items-center space-x-1">
                                                        <i class="bi bi-person"></i>
                                                        <span class="truncate">{{ jadwal.guru?.nama_lengkap || 'Guru Belum Ditentukan' }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-between text-3xs pt-1 border-t border-white/20 mt-1">
                                                        <span class="bg-black/20 px-1.5 py-0.5 rounded-md font-semibold truncate max-w-[70px]">
                                                            {{ jadwal.ruangan || 'R. 101' }}
                                                        </span>
                                                        <span class="font-bold opacity-90">
                                                            {{ jadwal.jam_pelajaran || 2 }} JP
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Hover Action Buttons -->
                                                <div class="absolute inset-0 bg-slate-900/85 rounded-xl flex items-center justify-center space-x-2 opacity-0 group-hover:opacity-100 transition backdrop-blur-3xs">
                                                    <button
                                                        type="button"
                                                        class="p-1.5 bg-white/20 hover:bg-white text-white hover:text-slate-900 rounded-lg transition text-xs cursor-pointer"
                                                        @click.stop="openEditModal(jadwal)"
                                                        title="Edit Jadwal"
                                                    >
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="p-1.5 bg-rose-500/50 hover:bg-rose-600 text-white rounded-lg transition text-xs cursor-pointer"
                                                        @click.stop="deleteSchedule(jadwal)"
                                                        title="Hapus Jadwal"
                                                    >
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Empty Slot: Inline Quick Add Button -->
                                        <div v-else class="h-full flex items-center justify-center opacity-0 hover:opacity-100 transition">
                                            <button
                                                type="button"
                                                class="w-full h-full py-3 rounded-xl border border-dashed border-slate-300 hover:border-blue-400 hover:bg-blue-50/50 text-slate-400 hover:text-blue-600 text-2xs font-semibold flex items-center justify-center space-x-1 transition cursor-pointer"
                                                @click="openCreateFromSlot(day, slot)"
                                                title="Jadwalkan mapel pada slot jam ini"
                                            >
                                                <i class="bi bi-plus-lg"></i>
                                                <span>Isi Jadwal</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: DAFTAR & TABEL JADWAL LENGKAP -->
            <div v-else-if="activeTab === 'table'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200/80 text-slate-600 font-bold uppercase tracking-wider">
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4">Hari & Jam</th>
                                    <th class="py-3 px-4">Kelas</th>
                                    <th class="py-3 px-4">Mata Pelajaran</th>
                                    <th class="py-3 px-4">Guru Pengampu</th>
                                    <th class="py-3 px-4">Ruangan</th>
                                    <th class="py-3 px-4 text-center">JP</th>
                                    <th class="py-3 px-4 text-center">KKM</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <tr v-for="(item, idx) in jadwalTable.data" :key="item.id" class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-4 text-center text-slate-400 font-bold">
                                        {{ (jadwalTable.current_page - 1) * jadwalTable.per_page + idx + 1 }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-0.5 rounded-lg text-2xs font-extrabold bg-blue-50 text-blue-700 border border-blue-100">
                                                {{ item.hari }}
                                            </span>
                                            <span class="text-slate-700 font-semibold">
                                                {{ item.jam_mulai }} - {{ item.jam_selesai }}
                                            </span>
                                        </div>
                                        <span v-if="item.jam_ke" class="text-3xs text-slate-400 font-medium block mt-0.5">
                                            Jam Ke-{{ item.jam_ke }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-900">
                                        {{ item.kelas?.nama_kelas || item.kelas_id }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-3 h-3 rounded-full shrink-0 shadow-2xs" :style="{ backgroundColor: item.warna_label || '#3b82f6' }"></div>
                                            <span class="font-bold text-slate-800">{{ item.mapel?.nama_mata_pelajaran || item.nama_pemetaan_mapel }}</span>
                                        </div>
                                        <span class="text-3xs text-slate-400 block mt-0.5">{{ item.kelompok_id || 'Kelompok A' }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-slate-800 font-semibold">{{ item.guru?.nama_lengkap || 'Belum Ditentukan' }}</span>
                                        <span v-if="item.guru?.nip" class="text-3xs text-slate-400 block">NIP. {{ item.guru.nip }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-md text-2xs font-semibold bg-slate-100 text-slate-700">
                                            {{ item.ruangan || 'R. 101' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-slate-800">
                                        {{ item.jam_pelajaran || 2 }} JP
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-slate-600">
                                        {{ item.kkm || 75 }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span v-if="item.has_conflict" class="px-2 py-0.5 rounded-full text-3xs font-extrabold bg-rose-100 text-rose-700 border border-rose-200 animate-pulse">
                                            ⚠️ Bentrok
                                        </span>
                                        <span v-else class="px-2 py-0.5 rounded-full text-3xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            ✅ Siap
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center space-x-1.5">
                                            <button
                                                type="button"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer"
                                                @click="openEditModal(item)"
                                                title="Edit Jadwal"
                                            >
                                                <i class="bi bi-pencil-square text-sm"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                                                @click="deleteSchedule(item)"
                                                title="Hapus Jadwal"
                                            >
                                                <i class="bi bi-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!jadwalTable.data || jadwalTable.data.length === 0">
                                    <td colspan="10" class="py-12 text-center text-slate-400 font-medium">
                                        <i class="bi bi-calendar-x text-3xl block mb-2 text-slate-300"></i>
                                        Tidak ada data jadwal pelajaran yang sesuai dengan filter pencarian.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div v-if="jadwalTable.links && jadwalTable.links.length > 3" class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/50">
                        <div class="text-xs text-slate-500 font-medium">
                            Menampilkan <span class="font-bold text-slate-800">{{ jadwalTable.from || 0 }}</span> s.d. <span class="font-bold text-slate-800">{{ jadwalTable.to || 0 }}</span> dari <span class="font-bold text-slate-800">{{ jadwalTable.total || 0 }}</span> jadwal
                        </div>

                        <div class="flex items-center space-x-1">
                            <template v-for="(link, lIdx) in jadwalTable.links" :key="lIdx">
                                <button
                                    v-if="link.url"
                                    type="button"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                                    :class="link.active ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'"
                                    @click="router.visit(link.url, { preserveScroll: true, preserveState: true })"
                                    v-html="link.label"
                                >
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PETA BEBAN MENGAJAR GURU -->
            <div v-else-if="activeTab === 'beban_guru'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-4 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm">Distribusi & Rekapitulasi Beban Jam Mengajar (BJM) Guru</h3>
                            <p class="text-xs text-slate-500">Standar Pemenuhan Beban Kerja Guru & Sertifikasi (Target 24 Jam Pelajaran / Pekan)</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200/80 text-slate-600 font-bold uppercase tracking-wider">
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4">Nama Lengkap & NIP</th>
                                    <th class="py-3 px-4 text-center">Total Beban JP</th>
                                    <th class="py-3 px-4">Status Pemenuhan (24 JP)</th>
                                    <th class="py-3 px-4">Rombel / Kelas Diajar</th>
                                    <th class="py-3 px-4">Mata Pelajaran</th>
                                    <th class="py-3 px-4 text-center">Audit Bentrok</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <tr v-for="(b, idx) in bebanGuruList" :key="b.guru_id" class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-4 text-center text-slate-400 font-bold">{{ idx + 1 }}</td>
                                    <td class="py-3 px-4">
                                        <span class="font-bold text-slate-900 block text-xs">{{ b.nama_guru }}</span>
                                        <span class="text-3xs text-slate-400">NIP. {{ b.nip }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-extrabold text-sm" :class="b.is_terpenuhi ? 'text-emerald-600' : 'text-amber-600'">
                                        {{ b.total_jp }} JP
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="w-full max-w-xs space-y-1">
                                            <div class="flex items-center justify-between text-3xs font-semibold">
                                                <span :class="b.is_terpenuhi ? 'text-emerald-700' : 'text-amber-700'">{{ b.status_sertifikasi }}</span>
                                                <span class="text-slate-400">{{ Math.min(100, Math.round((b.total_jp / 24) * 100)) }}%</span>
                                            </div>
                                            <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all duration-500"
                                                    :style="{ width: Math.min(100, (b.total_jp / 24) * 100) + '%' }"
                                                    :class="b.is_terpenuhi ? 'bg-emerald-500' : 'bg-amber-500'"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="k in b.list_kelas" :key="k" class="px-2 py-0.5 rounded-md text-3xs font-semibold bg-slate-100 text-slate-700">
                                                {{ k }}
                                            </span>
                                            <span v-if="b.list_kelas.length === 0" class="text-slate-400 text-2xs">-</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="m in b.list_mapel" :key="m" class="px-2 py-0.5 rounded-md text-3xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                                {{ m }}
                                            </span>
                                            <span v-if="b.list_mapel.length === 0" class="text-slate-400 text-2xs">-</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span v-if="b.has_conflict" class="px-2 py-0.5 rounded-full text-3xs font-extrabold bg-rose-100 text-rose-700 border border-rose-200">
                                            ⚠️ Ada Bentrok
                                        </span>
                                        <span v-else class="px-2 py-0.5 rounded-full text-3xs font-semibold bg-emerald-50 text-emerald-700">
                                            ✅ Bebas Bentrok
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: PETA UTILISASI RUANGAN -->
            <div v-else-if="activeTab === 'ruang_matrix'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-4 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm">Peta Utilisasi & Okupansi Ruang Kelas / Laboratorium</h3>
                            <p class="text-xs text-slate-500">Kapasitas dan intensitas pemakaian ruang per pekan</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 p-4">
                        <div
                            v-for="r in ruangUtilList"
                            :key="r.nama_ruangan"
                            class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition"
                        >
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 rounded-lg text-2xs font-extrabold bg-cyan-50 text-cyan-700 border border-cyan-100">
                                    {{ r.nama_ruangan }}
                                </span>
                                <span v-if="r.has_conflict" class="px-2 py-0.5 rounded-full text-3xs font-black bg-rose-100 text-rose-700 animate-pulse">
                                    Bentrok!
                                </span>
                            </div>

                            <div class="mt-3">
                                <div class="text-2xl font-black text-slate-900">{{ r.total_jp }} <span class="text-xs font-semibold text-slate-400">JP/Pekan</span></div>
                                <span class="text-2xs text-slate-500 font-medium">{{ r.total_sesi }} Sesi Terjadwal</span>
                            </div>

                            <div class="mt-3 pt-3 border-t border-slate-100">
                                <p class="text-3xs font-bold uppercase text-slate-400 mb-1">Rombel Pengguna:</p>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="k in r.list_kelas" :key="k" class="px-1.5 py-0.5 rounded-md text-3xs font-semibold bg-slate-100 text-slate-700">
                                        {{ k }}
                                    </span>
                                    <span v-if="r.list_kelas.length === 0" class="text-slate-400 text-3xs">Belum ada jadwal</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5: AUDIT & DETEKSI BENTROK (CONFLICT INSPECTOR) -->
            <div v-else-if="activeTab === 'conflict'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                                <i class="bi bi-shield-exclamation text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900">Audit & Deteksi Tabrakan Jadwal (Conflict Inspector)</h3>
                                <p class="text-xs text-slate-500">Mendeteksi potensi bentrok Guru, Ruangan, maupun Kelas pada periode aktif</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold"
                              :class="conflictList.length > 0 ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200'">
                            {{ conflictList.length }} Potensi Bentrok Ditemukan
                        </span>
                    </div>

                    <!-- Collision Cards List -->
                    <div v-if="conflictList.length > 0" class="space-y-3 mt-4">
                        <div
                            v-for="(cf, cIdx) in conflictList"
                            :key="cIdx"
                            class="bg-rose-50/60 rounded-2xl p-4 border border-rose-200/80 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-md text-2xs font-black uppercase tracking-wider bg-rose-600 text-white">
                                        {{ cf.type === 'guru' ? 'Bentrok Guru' : (cf.type === 'ruangan' ? 'Bentrok Ruang' : 'Bentrok Kelas') }}
                                    </span>
                                    <span class="text-xs font-bold text-rose-900">{{ cf.title }}</span>
                                </div>
                                <p class="text-xs text-rose-700 font-medium">
                                    {{ cf.description }}
                                </p>
                            </div>

                            <div class="flex items-center space-x-2 shrink-0">
                                <button
                                    v-if="cf.jadwal_a"
                                    type="button"
                                    class="px-3 py-1.5 rounded-xl bg-white border border-rose-300 hover:bg-rose-100 text-rose-700 font-semibold text-xs transition cursor-pointer"
                                    @click="openEditModal(cf.jadwal_a)"
                                >
                                    <i class="bi bi-pencil me-1"></i> Edit Jadwal A
                                </button>
                                <button
                                    v-if="cf.jadwal_b"
                                    type="button"
                                    class="px-3 py-1.5 rounded-xl bg-white border border-rose-300 hover:bg-rose-100 text-rose-700 font-semibold text-xs transition cursor-pointer"
                                    @click="openEditModal(cf.jadwal_b)"
                                >
                                    <i class="bi bi-pencil me-1"></i> Edit Jadwal B
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-16">
                        <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 text-2xl font-bold">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <h4 class="text-base font-extrabold text-slate-800">Tidak Ditemukan Bentrok Jadwal!</h4>
                        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                            Seluruh jadwal guru, alokasi ruangan kelas, dan mata pelajaran pada semester {{ activeSemester }} TA {{ activeTahunAjaran }} telah 100% tervalidasi bebas bentrok.
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL 1: FORM INPUT / EDIT JADWAL PELAJARAN (<Teleport to="body">) -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div v-if="isModalFormOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden my-8 transform transition-all">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-900 to-indigo-900 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
                                <i class="bi text-lg text-blue-300" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold">{{ isEditing ? 'Edit Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran Baru' }}</h3>
                                <p class="text-xs text-blue-200">TA {{ form.tahun_ajaran }} • Semester {{ form.semester }}</p>
                            </div>
                        </div>
                        <button type="button" class="text-white/70 hover:text-white transition cursor-pointer" @click="isModalFormOpen = false">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitScheduleForm" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">

                        <!-- Live Conflict Warning Alert Banner -->
                        <div v-if="liveConflictWarnings.length > 0" class="bg-rose-50 rounded-2xl p-4 border border-rose-200 space-y-2">
                            <div class="flex items-center space-x-2 text-rose-800 font-extrabold text-xs">
                                <i class="bi bi-exclamation-triangle-fill text-rose-600"></i>
                                <span>PERINGATAN: Terdeteksi Bentrok Jadwal!</span>
                            </div>
                            <ul class="text-xs text-rose-700 space-y-1 list-disc list-inside font-medium">
                                <li v-for="(warn, wIdx) in liveConflictWarnings" :key="wIdx">{{ warn }}</li>
                            </ul>
                            <div class="pt-2 border-t border-rose-200 flex items-center space-x-2">
                                <input type="checkbox" id="forceOverride" v-model="form.force_override" class="rounded-md border-rose-300 text-rose-600 focus:ring-rose-500">
                                <label for="forceOverride" class="text-2xs font-bold text-rose-800 cursor-pointer">
                                    Tetap simpan jadwal ini (Abaikan Bentrok / Override)
                                </label>
                            </div>
                        </div>

                        <!-- Grid Row 1: Kelas & Mapel -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kelas (Rombel) <span class="text-rose-500">*</span></label>
                                <SearchableSelect
                                    v-model="form.kelas_id"
                                    :options="kelasFormOptions"
                                    placeholder="Pilih Kelas..."
                                    @change="checkConflictLive"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Mata Pelajaran <span class="text-rose-500">*</span></label>
                                <SearchableSelect
                                    v-model="form.mapel_id"
                                    :options="mapelOptions"
                                    placeholder="Pilih Mata Pelajaran..."
                                    @change="checkConflictLive"
                                />
                            </div>
                        </div>

                        <!-- Grid Row 2: Guru Pengampu & Ruangan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Guru Pengampu</label>
                                <SearchableSelect
                                    v-model="form.guru_id"
                                    :options="guruFormOptions"
                                    placeholder="Pilih Guru..."
                                    @change="checkConflictLive"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Ruangan / Lab</label>
                                <SearchableSelect
                                    v-model="form.ruangan"
                                    :options="ruangFormOptions"
                                    placeholder="Pilih Ruang..."
                                    @change="checkConflictLive"
                                />
                            </div>
                        </div>

                        <!-- Grid Row 3: Hari & Jam Ke -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Hari Pelaksanaan <span class="text-rose-500">*</span></label>
                                <SearchableSelect
                                    v-model="form.hari"
                                    :options="hariFormOptions"
                                    placeholder="Pilih Hari..."
                                    @change="checkConflictLive"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Ke</label>
                                <input
                                    type="text"
                                    v-model="form.jam_ke"
                                    placeholder="Contoh: 1-2, 3-4, 1"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200/90 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-hidden font-medium"
                                />
                            </div>
                        </div>

                        <!-- Preset Slot Shortcuts -->
                        <div>
                            <span class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Pilihan Slot Cepat:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="(ps, pIdx) in presetSlots"
                                    :key="pIdx"
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg text-2xs font-semibold border transition cursor-pointer"
                                    :class="form.jam_mulai === ps.mulai && form.jam_selesai === ps.selesai ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                    @click="applyPresetSlot(ps)"
                                >
                                    {{ ps.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Grid Row 4: Jam Mulai, Jam Selesai, Beban JP, KKM -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai <span class="text-rose-500">*</span></label>
                                <input
                                    type="time"
                                    v-model="form.jam_mulai"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200/90 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-hidden font-bold text-slate-800"
                                    @change="checkConflictLive"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai <span class="text-rose-500">*</span></label>
                                <input
                                    type="time"
                                    v-model="form.jam_selesai"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200/90 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-hidden font-bold text-slate-800"
                                    @change="checkConflictLive"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Beban JP</label>
                                <input
                                    type="number"
                                    v-model.number="form.jam_pelajaran"
                                    min="1"
                                    max="20"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200/90 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-hidden font-bold text-slate-800"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">KKM</label>
                                <input
                                    type="number"
                                    v-model.number="form.kkm"
                                    min="0"
                                    max="100"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200/90 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-hidden font-bold text-slate-800"
                                />
                            </div>
                        </div>

                        <!-- Subject Color Tag Selector -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Warna Label Matriks</label>
                            <div class="flex items-center space-x-2">
                                <button
                                    v-for="c in colorPalette"
                                    :key="c.value"
                                    type="button"
                                    class="w-7 h-7 rounded-full transition transform hover:scale-110 flex items-center justify-center cursor-pointer border-2"
                                    :class="[c.bg, form.warna_label === c.value ? 'border-slate-900 ring-2 ring-blue-400' : 'border-white']"
                                    @click="form.warna_label = c.value"
                                    :title="c.label"
                                >
                                    <i v-if="form.warna_label === c.value" class="bi bi-check text-white font-bold text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                            <textarea
                                v-model="form.catatan"
                                rows="2"
                                placeholder="Contoh: Praktikum wajib jas lab, pembagian materi bab 1-3..."
                                class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200/90 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-hidden font-medium"
                            ></textarea>
                        </div>

                        <!-- Modal Footer -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition cursor-pointer"
                                @click="isModalFormOpen = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs cursor-pointer flex items-center"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-check-circle me-1.5"></i>
                                {{ isEditing ? 'Simpan Perubahan' : 'Simpan Jadwal' }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ========================================================================= -->
        <!-- MODAL 2: IMPORT EXCEL (.XLSX) WITH LIVE PREVIEW (<Teleport to="body">) -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div v-if="isModalImportOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xl w-full max-w-4xl overflow-hidden my-8 transform transition-all">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-emerald-800 to-teal-900 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
                                <i class="bi bi-file-earmark-excel-fill text-lg text-emerald-300"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold">Import Data Jadwal Pelajaran (.xlsx)</h3>
                                <p class="text-xs text-emerald-200">Pratinjau Data, Pemetaan Otomatis, & Anti-Bentrok Engine</p>
                            </div>
                        </div>
                        <button type="button" class="text-white/70 hover:text-white transition cursor-pointer" @click="isModalImportOpen = false">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <!-- STEP 1: UPLOAD FILE -->
                    <div v-if="importStep === 1" class="p-6 space-y-5">
                        <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-200 flex items-start space-x-3">
                            <i class="bi bi-info-circle-fill text-emerald-600 text-lg shrink-0 mt-0.5"></i>
                            <div class="text-xs text-emerald-800 space-y-1">
                                <p class="font-bold">Panduan Penggunaan Fitur Import Excel:</p>
                                <p>Pastikan nama kelas, nama mata pelajaran, dan nama guru pada file Excel sesuai dengan master data sekolah di SINTA. Gunakan template resmi di bawah ini agar format kolom valid.</p>
                            </div>
                        </div>

                        <!-- Drag & Drop Upload Zone -->
                        <div class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-8 text-center transition bg-slate-50/50">
                            <i class="bi bi-cloud-arrow-up text-4xl text-emerald-600 block mb-2"></i>
                            <p class="text-sm font-bold text-slate-800 mb-1">Pilih atau Seret Berkas Excel / CSV ke Sini</p>
                            <p class="text-xs text-slate-400 mb-4">Mendukung format .xlsx, .xls, .csv (Maksimal 5MB)</p>

                            <input
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                id="excelFileInput"
                                class="hidden"
                                @change="onImportFileSelected"
                            />

                            <label
                                for="excelFileInput"
                                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition cursor-pointer inline-flex items-center shadow-xs"
                            >
                                <i class="bi bi-folder2-open me-2"></i>
                                {{ importFile ? importFile.name : 'Telusuri File Excel' }}
                            </label>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                            <button
                                type="button"
                                class="px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 transition cursor-pointer flex items-center"
                                @click="triggerDownloadTemplate"
                            >
                                <i class="bi bi-file-earmark-arrow-down me-1.5"></i>
                                Download Template Excel (.xlsx)
                            </button>

                            <div class="flex items-center space-x-2">
                                <button
                                    type="button"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition cursor-pointer"
                                    @click="isModalImportOpen = false"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs cursor-pointer flex items-center"
                                    :disabled="!importFile || isUploading"
                                    @click="uploadAndPreviewImport"
                                >
                                    <span v-if="isUploading" class="spinner-border spinner-border-sm me-2"></span>
                                    <i v-else class="bi bi-eye me-1.5"></i>
                                    Pratinjau Data (Preview)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: PREVIEW & VALIDATION RESULTS -->
                    <div v-else-if="importStep === 2" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <!-- Summary Badges -->
                        <div class="grid grid-cols-4 gap-3">
                            <div class="bg-slate-100 rounded-xl p-3 text-center border border-slate-200">
                                <span class="text-2xs font-bold uppercase text-slate-500">Total Baris</span>
                                <div class="text-xl font-black text-slate-800">{{ previewResult.total_rows }}</div>
                            </div>
                            <div class="bg-emerald-50 rounded-xl p-3 text-center border border-emerald-200">
                                <span class="text-2xs font-bold uppercase text-emerald-700">Valid (Siap Simpan)</span>
                                <div class="text-xl font-black text-emerald-700">{{ previewResult.valid_count }}</div>
                            </div>
                            <div class="bg-amber-50 rounded-xl p-3 text-center border border-amber-200">
                                <span class="text-2xs font-bold uppercase text-amber-700">Bentrok Jadwal</span>
                                <div class="text-xl font-black text-amber-700">{{ previewResult.conflict_count }}</div>
                            </div>
                            <div class="bg-rose-50 rounded-xl p-3 text-center border border-rose-200">
                                <span class="text-2xs font-bold uppercase text-rose-700">Format Error</span>
                                <div class="text-xl font-black text-rose-700">{{ previewResult.error_count }}</div>
                            </div>
                        </div>

                        <!-- Tab Filter Preview -->
                        <div class="flex items-center space-x-1 border-b border-slate-200 pb-2">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                                :class="previewTab === 'all' ? 'bg-slate-800 text-white' : 'text-slate-600 hover:bg-slate-100'"
                                @click="previewTab = 'all'"
                            >
                                Semua ({{ previewResult.rows.length }})
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                                :class="previewTab === 'valid' ? 'bg-emerald-600 text-white' : 'text-emerald-700 hover:bg-emerald-50'"
                                @click="previewTab = 'valid'"
                            >
                                Valid ({{ previewResult.valid_count }})
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                                :class="previewTab === 'conflict' ? 'bg-amber-600 text-white' : 'text-amber-700 hover:bg-amber-50'"
                                @click="previewTab = 'conflict'"
                            >
                                Bentrok ({{ previewResult.conflict_count }})
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                                :class="previewTab === 'invalid' ? 'bg-rose-600 text-white' : 'text-rose-700 hover:bg-rose-50'"
                                @click="previewTab = 'invalid'"
                            >
                                Error ({{ previewResult.error_count }})
                            </button>
                        </div>

                        <!-- Preview Table -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden max-h-72 overflow-y-auto">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 text-slate-600 font-bold">
                                    <tr>
                                        <th class="py-2.5 px-3">Status</th>
                                        <th class="py-2.5 px-3">Baris</th>
                                        <th class="py-2.5 px-3">Hari & Jam</th>
                                        <th class="py-2.5 px-3">Kelas</th>
                                        <th class="py-2.5 px-3">Mata Pelajaran</th>
                                        <th class="py-2.5 px-3">Guru</th>
                                        <th class="py-2.5 px-3">Ruang</th>
                                        <th class="py-2.5 px-3">Keterangan / Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    <tr
                                        v-for="pr in filteredPreviewRows"
                                        :key="pr.row_index"
                                        :class="pr.status === 'valid' ? 'hover:bg-emerald-50/40' : (pr.status === 'conflict' ? 'bg-amber-50/50' : 'bg-rose-50/50')"
                                    >
                                        <td class="py-2.5 px-3">
                                            <span v-if="pr.status === 'valid'" class="px-2 py-0.5 rounded-full text-3xs font-bold bg-emerald-100 text-emerald-800">
                                                ✅ Valid
                                            </span>
                                            <span v-else-if="pr.status === 'conflict'" class="px-2 py-0.5 rounded-full text-3xs font-bold bg-amber-100 text-amber-800">
                                                ⚠️ Bentrok
                                            </span>
                                            <span v-else class="px-2 py-0.5 rounded-full text-3xs font-bold bg-rose-100 text-rose-800">
                                                ❌ Error
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500 font-bold">{{ pr.row_index }}</td>
                                        <td class="py-2.5 px-3">
                                            <span class="font-bold text-slate-800">{{ pr.hari }}</span> ({{ pr.jam_mulai }}-{{ pr.jam_selesai }})
                                        </td>
                                        <td class="py-2.5 px-3 font-semibold text-slate-800">{{ pr.raw_kelas }}</td>
                                        <td class="py-2.5 px-3 font-semibold text-slate-800">{{ pr.raw_mapel }}</td>
                                        <td class="py-2.5 px-3 text-slate-600">{{ pr.raw_guru || '-' }}</td>
                                        <td class="py-2.5 px-3 text-slate-600">{{ pr.ruangan || '-' }}</td>
                                        <td class="py-2.5 px-3 text-2xs">
                                            <div v-if="pr.errors && pr.errors.length > 0" class="text-rose-600 font-bold">
                                                {{ pr.errors.join(', ') }}
                                            </div>
                                            <div v-else-if="pr.conflicts && pr.conflicts.length > 0" class="text-amber-700 font-medium">
                                                {{ pr.conflicts.join(', ') }}
                                            </div>
                                            <div v-else class="text-emerald-700">
                                                Siap diimpor
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Import Option Switch -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center space-x-2">
                            <input type="checkbox" id="skipConflicts" v-model="skipImportConflicts" class="rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="skipConflicts" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                Lewati secara otomatis baris yang terdeteksi bentrok (Hanya simpan jadwal yang 100% valid)
                            </label>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                            <button
                                type="button"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition cursor-pointer"
                                @click="importStep = 1"
                            >
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Upload
                            </button>

                            <button
                                type="button"
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs cursor-pointer flex items-center"
                                :disabled="previewResult.valid_count === 0 || isCommitting"
                                @click="commitApprovedImport"
                            >
                                <span v-if="isCommitting" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-check2-all me-1.5"></i>
                                Konfirmasi & Simpan ({{ previewResult.valid_count }} Jadwal Valid)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ========================================================================= -->
        <!-- MODAL 3: SALIN JADWAL ANTAR-SEMESTER (<Teleport to="body">) -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div v-if="isModalCopyOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-8 transform transition-all">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-purple-900 to-indigo-900 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
                                <i class="bi bi-copy text-lg text-purple-300"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold">Salin / Kloning Jadwal Pelajaran</h3>
                                <p class="text-xs text-purple-200">Duplikasi struktur jadwal antar-semester atau tahun ajaran</p>
                            </div>
                        </div>
                        <button type="button" class="text-white/70 hover:text-white transition cursor-pointer" @click="isModalCopyOpen = false">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitCopySchedule" class="p-6 space-y-4">
                        <div class="bg-purple-50 rounded-2xl p-4 border border-purple-200 text-xs text-purple-800 space-y-1">
                            <p class="font-bold">Informasi Kloning Jadwal:</p>
                            <p>Fitur ini akan menyalin seluruh pemetaan jadwal dari periode sumber ke periode target tanpa menghapus jadwal yang telah ada sebelumnya.</p>
                        </div>

                        <!-- Periode Sumber -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <span class="text-2xs font-extrabold uppercase text-slate-500 block">Periode Sumber (Asal Data):</span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-2xs font-bold text-slate-700 mb-1">Tahun Ajaran</label>
                                    <SearchableSelect
                                        v-model="formCopy.from_tahun_ajaran"
                                        :options="tahunAjaranOptions"
                                        placeholder="Pilih TA Sumber..."
                                    />
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-slate-700 mb-1">Semester</label>
                                    <SearchableSelect
                                        v-model="formCopy.from_semester"
                                        :options="semesterOptions"
                                        placeholder="Pilih Semester..."
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Periode Target -->
                        <div class="p-4 bg-blue-50/60 rounded-2xl border border-blue-200 space-y-3">
                            <span class="text-2xs font-extrabold uppercase text-blue-700 block">Periode Target (Tujuan Salin):</span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-2xs font-bold text-slate-700 mb-1">Tahun Ajaran</label>
                                    <SearchableSelect
                                        v-model="formCopy.to_tahun_ajaran"
                                        :options="tahunAjaranOptions"
                                        placeholder="Pilih TA Target..."
                                    />
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-slate-700 mb-1">Semester</label>
                                    <SearchableSelect
                                        v-model="formCopy.to_semester"
                                        :options="semesterOptions"
                                        placeholder="Pilih Semester..."
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition cursor-pointer"
                                @click="isModalCopyOpen = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs transition shadow-xs cursor-pointer flex items-center"
                                :disabled="formCopy.processing"
                            >
                                <span v-if="formCopy.processing" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-copy me-1.5"></i>
                                Salin Seluruh Jadwal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>
