<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
    pelanggaranList: Object,
    masterList: Array,
    kpi: Object,
    topStudents: Array,
    monthlyTrend: Array,
    tenantInfo: Object,
    isSuperAdmin: Boolean,
    tenants: Array,
    filters: Object,
})

const page = usePage()

// Tab State
const activeTab = ref('buku_pelanggaran')

// Filter State
const search = ref(props.filters?.search || '')
const tenantId = ref(props.filters?.tenant_id || '')
const kategori = ref(props.filters?.kategori || '')
const statusPembinaan = ref(props.filters?.status_pembinaan || '')
const startDate = ref(props.filters?.start_date || '')
const endDate = ref(props.filters?.end_date || '')
const perPage = ref(props.filters?.per_page || 15)

// Modals State
const showModalRecord = ref(false)
const isEditingRecord = ref(false)
const selectedRecordId = ref(null)

const showModalDetail = ref(false)
const detailStudent = ref(null)

const showModalMaster = ref(false)
const isEditingMaster = ref(false)
const selectedMasterId = ref(null)

const showModalCetak = ref(false)
const cetakData = ref(null)

// Live Student Search inside Record Modal
const siswaSearchQuery = ref('')
const siswaSearchResults = ref([])
const isSearchingSiswa = ref(false)
const selectedSiswa = ref(null)
let searchDebounceTimer = null
let filterSearchDebounce = null

// Forms
const recordForm = useForm({
    siswa_id: '',
    pelanggaran_id: '',
    nama_pelanggaran: '',
    kategori: 'Ringan',
    poin_pelanggaran: 5,
    tanggal_kejadian: new Date().toISOString().slice(0, 10),
    petugas_pencatat: '',
    tindakan_hukuman: '',
    keterangan: '',
    status_pembinaan: 'Belum Dibina',
    foto_bukti: null,
})

const masterForm = useForm({
    nama_pelanggaran: '',
    kategori: 'Ringan',
    bobot_poin: 5,
    deskripsi: '',
})

// Quick filter handler
const applyFilters = () => {
    router.get(
        '/bk/kedisiplinan',
        {
            search: search.value || undefined,
            tenant_id: tenantId.value || undefined,
            kategori: kategori.value || undefined,
            status_pembinaan: statusPembinaan.value || undefined,
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
    kategori.value = ''
    statusPembinaan.value = ''
    startDate.value = ''
    endDate.value = ''
    perPage.value = 15
    applyFilters()
}

// Student Autocomplete Search
const onSiswaSearchInput = () => {
    clearTimeout(searchDebounceTimer)
    if (!siswaSearchQuery.value || siswaSearchQuery.value.length < 2) {
        siswaSearchResults.value = []
        return
    }
    isSearchingSiswa.value = true
    searchDebounceTimer = setTimeout(async () => {
        try {
            const tenantParam = tenantId.value ? `&tenant_id=${encodeURIComponent(tenantId.value)}` : ''
            const res = await fetch(`/bk/search-siswa?q=${encodeURIComponent(siswaSearchQuery.value)}${tenantParam}`)
            const data = await res.json()
            if (data.success) {
                siswaSearchResults.value = data.data || []
            }
        } catch (e) {
            console.error('Error searching siswa:', e)
        } finally {
            isSearchingSiswa.value = false
        }
    }, 250)
}

const chooseSiswa = (siswa) => {
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

// Auto-fill category and points when selecting from master list
const onMasterSelect = (e) => {
    const masterId = e.target.value
    if (!masterId) return
    const selected = (props.masterList || []).find((m) => m.id === masterId)
    if (selected) {
        recordForm.nama_pelanggaran = selected.nama_pelanggaran || selected.nama_master_pelanggaran
        recordForm.kategori = selected.kategori || 'Ringan'
        recordForm.poin_pelanggaran = selected.bobot_poin || 5
        if (!recordForm.keterangan) {
            recordForm.keterangan = selected.deskripsi || ''
        }
    }
}

// Open Record Modal
const openCreateRecordModal = () => {
    isEditingRecord.value = false
    selectedRecordId.value = null
    selectedSiswa.value = null
    siswaSearchQuery.value = ''
    siswaSearchResults.value = []

    recordForm.reset()
    recordForm.tanggal_kejadian = new Date().toISOString().slice(0, 10)
    recordForm.petugas_pencatat = page.props.auth?.user?.nama_lengkap || page.props.auth?.user?.name || 'Guru BK'
    recordForm.status_pembinaan = 'Belum Dibina'
    recordForm.poin_pelanggaran = 5
    recordForm.kategori = 'Ringan'

    showModalRecord.value = true
}

const openEditRecordModal = (item) => {
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
    recordForm.pelanggaran_id = item.pelanggaran_id || ''
    recordForm.nama_pelanggaran = item.nama_pelanggaran || item.nama_pelanggaran_siswa || ''
    recordForm.kategori = item.kategori || 'Ringan'
    recordForm.poin_pelanggaran = item.poin_pelanggaran || 5
    recordForm.tanggal_kejadian = item.tanggal_kejadian ? item.tanggal_kejadian.slice(0, 10) : new Date().toISOString().slice(0, 10)
    recordForm.petugas_pencatat = item.petugas_pencatat || page.props.auth?.user?.nama_lengkap || 'Guru BK'
    recordForm.tindakan_hukuman = item.tindakan_hukuman || ''
    recordForm.keterangan = item.keterangan || item.deskripsi || ''
    recordForm.status_pembinaan = item.status_pembinaan || 'Belum Dibina'
    recordForm.foto_bukti = null

    showModalRecord.value = true
}

// Handle File Input
const onFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        recordForm.foto_bukti = file
    }
}

// Submit Record
const submitRecordForm = () => {
    if (isEditingRecord.value && selectedRecordId.value) {
        recordForm.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(`/bk/pelanggaran/${selectedRecordId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                showModalRecord.value = false
                recordForm.reset()
                clearSelectedSiswa()
            },
        })
    } else {
        recordForm.post('/bk/pelanggaran', {
            preserveScroll: true,
            onSuccess: () => {
                showModalRecord.value = false
                recordForm.reset()
                clearSelectedSiswa()
            },
        })
    }
}

// Delete Record
const deleteRecord = (item) => {
    if (confirm(`Apakah Anda yakin ingin menghapus catatan pelanggaran "${item.nama_pelanggaran || item.nama_pelanggaran_siswa}"?`)) {
        router.delete(`/bk/pelanggaran/${item.id}`, {
            preserveScroll: true,
        })
    }
}

// Open Detail Student Modal
const openDetailStudentModal = (item) => {
    detailStudent.value = item
    showModalDetail.value = true
}

// Open Cetak Surat Modal
const openCetakModal = (item) => {
    const currentUser = page.props.auth?.user
    cetakData.value = {
        nama_sekolah: props.tenantInfo?.nama_sekolah || 'SMA / SMK Negeri SINTA',
        alamat_sekolah: props.tenantInfo?.alamat || 'Jl. Pendidikan No. 10',
        telepon_sekolah: props.tenantInfo?.telepon || '(021) 555-1234',
        email_sekolah: props.tenantInfo?.email || 'bk@sekolah.sch.id',
        kabupaten_provinsi: `${props.tenantInfo?.kabupaten_kota || 'KABUPATEN / KOTA'} - ${props.tenantInfo?.provinsi || 'PROVINSI'}`,
        nama_kepsek: props.tenantInfo?.nama_kepsek || 'Kepala Sekolah',
        nip_kepsek: props.tenantInfo?.nip_kepsek || '-',
        nama_guru_bk: item.petugas_pencatat || currentUser?.nama_lengkap || currentUser?.name || 'Guru Bimbingan Konseling (BK)',
        nip_guru_bk: currentUser?.nip || item.petugas_nip || '-',
        nama_siswa: item.snapshot_nama_siswa || item.siswa?.nama_lengkap || item.nama_siswa || 'Siswa',
        nisn: item.snapshot_nisn || item.siswa?.nisn || item.nisn || '-',
        nama_kelas: item.snapshot_nama_kelas || item.nama_kelas || 'Kelas Siswa',
        poin: item.total_poin || item.poin_pelanggaran || 0,
        kasus: item.nama_pelanggaran || item.nama_pelanggaran_siswa || 'Akumulasi Poin Pelanggaran Tata Tertib',
        kategori: item.kategori || 'Kedisiplinan',
        tanggal_kejadian: item.tanggal_kejadian || new Date().toISOString().slice(0, 10),
        petugas: item.petugas_pencatat || currentUser?.nama_lengkap || 'Guru BK / Tim Disiplin',
        tindakan: item.tindakan_hukuman || 'Bimbingan Konseling & Pemanggilan Orang Tua',
        tanggal_panggilan: new Date(Date.now() + 86400000 * 2).toISOString().slice(0, 10),
        waktu_panggilan: '08.30 WIB',
        tempat: 'Ruang Bimbingan & Konseling (BK)',
    }
    showModalCetak.value = true
}

// Master Rules Handlers
const openCreateMasterModal = () => {
    isEditingMaster.value = false
    selectedMasterId.value = null
    masterForm.reset()
    masterForm.kategori = 'Ringan'
    masterForm.bobot_poin = 5
    showModalMaster.value = true
}

const openEditMasterModal = (rule) => {
    isEditingMaster.value = true
    selectedMasterId.value = rule.id
    masterForm.nama_pelanggaran = rule.nama_pelanggaran || rule.nama_master_pelanggaran
    masterForm.kategori = rule.kategori || 'Ringan'
    masterForm.bobot_poin = rule.bobot_poin || 5
    masterForm.deskripsi = rule.deskripsi || ''
    showModalMaster.value = true
}

const submitMasterForm = () => {
    if (isEditingMaster.value && selectedMasterId.value) {
        masterForm.put(`/bk/master-pelanggaran/${selectedMasterId.value}`, {
            onSuccess: () => {
                showModalMaster.value = false
                masterForm.reset()
            },
        })
    } else {
        masterForm.post('/bk/master-pelanggaran', {
            onSuccess: () => {
                showModalMaster.value = false
                masterForm.reset()
            },
        })
    }
}

const deleteMasterRule = (rule) => {
    if (confirm(`Hapus aturan tata tertib "${rule.nama_pelanggaran || rule.nama_master_pelanggaran}"?`)) {
        router.delete(`/bk/master-pelanggaran/${rule.id}`, {
            preserveScroll: true,
        })
    }
}

// Helpers for badges & styles
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

const getKategoriBadge = (kat) => {
    switch (kat) {
        case 'Ringan':
            return 'bg-blue-50 text-blue-700 border-blue-200'
        case 'Sedang':
            return 'bg-amber-50 text-amber-700 border-amber-200'
        case 'Berat':
            return 'bg-rose-50 text-rose-700 border-rose-200'
        case 'Khusus':
            return 'bg-purple-50 text-purple-700 border-purple-200'
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200'
    }
}

const getStatusPembinaanBadge = (status) => {
    switch (status) {
        case 'Selesai':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case 'Dalam Pembinaan':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200'
        case 'Belum Dibina':
        default:
            return 'bg-rose-50 text-rose-700 border-rose-200'
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

// 3-Way Horizontal Scroller Setup
onMounted(() => {
    const navTabs = document.getElementById('navTabsBk')
    if (navTabs) {
        navTabs.addEventListener(
            'wheel',
            (e) => {
                if (e.deltaY !== 0) {
                    e.preventDefault()
                    navTabs.scrollLeft += e.deltaY * 0.9
                }
            },
            { passive: false }
        )

        let isDown = false
        let startX = 0
        let scrollLeft = 0

        navTabs.addEventListener('mousedown', (e) => {
            isDown = true
            startX = e.pageX - navTabs.offsetLeft
            scrollLeft = navTabs.scrollLeft
        })
        navTabs.addEventListener('mouseleave', () => { isDown = false })
        navTabs.addEventListener('mouseup', () => { isDown = false })
        navTabs.addEventListener('mousemove', (e) => {
            if (!isDown) return
            e.preventDefault()
            const x = e.pageX - navTabs.offsetLeft
            const walk = (x - startX) * 1.5
            navTabs.scrollLeft = scrollLeft - walk
        })
    }
})
</script>

<template>
    <AppLayout title="Kedisiplinan & Bimbingan Konseling">
        <div class="space-y-5">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 to-rose-400 text-white flex items-center justify-center shadow-xs">
                            <i class="bi bi-shield-shaded text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-black text-slate-800 tracking-tight">Kedisiplinan & Bimbingan Konseling</h1>
                            <p class="text-xs text-slate-500">Pencatatan pelanggaran tata tertib, monitoring akumulasi poin, dan pembinaan siswa.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openCreateRecordModal"
                        class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs hover:shadow transition flex items-center gap-2"
                    >
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Catat Pelanggaran</span>
                    </button>
                    <button
                        type="button"
                        @click="openCreateMasterModal"
                        class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition flex items-center gap-1.5"
                    >
                        <i class="bi bi-gear-fill text-slate-500"></i>
                        <span class="hidden sm:inline">Kelola Aturan</span>
                    </button>
                </div>
            </div>

            <!-- KPI Cards Monitoring Poin -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Total Kasus -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-2xs font-bold uppercase tracking-wider">Total Kasus</span>
                        <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs">
                            <i class="bi bi-journal-text"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ kpi?.total_kasus || 0 }}</div>
                        <div class="text-2xs text-slate-500 font-medium">Pelanggaran dicatat</div>
                    </div>
                </div>

                <!-- Siswa Melanggar -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-2xs font-bold uppercase tracking-wider">Siswa Terlibat</span>
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-blue-600">{{ kpi?.total_siswa_melanggar || 0 }}</div>
                        <div class="text-2xs text-slate-500 font-medium">Siswa terakumulasi</div>
                    </div>
                </div>

                <!-- Wali Kelas (1-24 Poin) -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-2xs font-bold uppercase tracking-wider">Wali Kelas</span>
                        <div class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-xs">
                            <i class="bi bi-person-badge"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-sky-600">{{ kpi?.wali_kelas || 0 }}</div>
                        <div class="text-2xs text-slate-500 font-medium">Poin 1 - 24 (Ringan)</div>
                    </div>
                </div>

                <!-- SP 1 / BK (25-49 Poin) -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-2xs font-bold uppercase tracking-wider">SP 1 (BK)</span>
                        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-amber-600">{{ kpi?.sp1_bk || 0 }}</div>
                        <div class="text-2xs text-slate-500 font-medium">Poin 25 - 49 (Sedang)</div>
                    </div>
                </div>

                <!-- SP 2 / Skorsing (50-74 Poin) -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-2xs font-bold uppercase tracking-wider">SP 2 (Skorsing)</span>
                        <div class="w-7 h-7 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs">
                            <i class="bi bi-shield-x"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-orange-600">{{ kpi?.sp2_skorsing || 0 }}</div>
                        <div class="text-2xs text-slate-500 font-medium">Poin 50 - 74 (Berat)</div>
                    </div>
                </div>

                <!-- SP 3 / DO (75+ Poin) -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-2xs font-bold uppercase tracking-wider">SP 3 / DO</span>
                        <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                            <i class="bi bi-slash-circle-fill"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-rose-600">{{ kpi?.sp3_do || 0 }}</div>
                        <div class="text-2xs text-slate-500 font-medium">Poin 75+ (Kritis)</div>
                    </div>
                </div>
            </div>

            <!-- Modern 3-Way Horizontal NavTabs -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <!-- Tombol Panah Kiri -->
                    <button
                        type="button"
                        class="btn-nav-scroll border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-rose-600 hover:bg-rose-50 transition w-[34px] h-[34px] z-5 cursor-pointer"
                        onclick="document.getElementById('navTabsBk')?.scrollBy({ left: -220, behavior: 'smooth' })"
                        title="Geser ke Kiri"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <!-- Container Deretan Tab -->
                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsBk" role="tablist">
                            <li class="nav-item">
                                <button
                                    class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2 cursor-pointer"
                                    :class="activeTab === 'buku_pelanggaran' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="activeTab = 'buku_pelanggaran'"
                                >
                                    <i class="bi bi-journal-text text-sm"></i>
                                    <span>Buku Rekam Pelanggaran</span>
                                    <span class="px-1.5 py-0.5 rounded-md text-2xs font-black" :class="activeTab === 'buku_pelanggaran' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
                                        {{ pelanggaranList?.total || 0 }}
                                    </span>
                                </button>
                            </li>

                            <li class="nav-item">
                                <button
                                    class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2 cursor-pointer"
                                    :class="activeTab === 'siswa_pembinaan' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="activeTab = 'siswa_pembinaan'"
                                >
                                    <i class="bi bi-shield-exclamation text-sm"></i>
                                    <span>Siswa Butuh Pembinaan (Risk Ranking)</span>
                                    <span class="px-1.5 py-0.5 rounded-md text-2xs font-black" :class="activeTab === 'siswa_pembinaan' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
                                        {{ topStudents?.length || 0 }}
                                    </span>
                                </button>
                            </li>

                            <li class="nav-item">
                                <button
                                    class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2 cursor-pointer"
                                    :class="activeTab === 'master_aturan' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="activeTab = 'master_aturan'"
                                >
                                    <i class="bi bi-card-checklist text-sm"></i>
                                    <span>Katalog Tata Tertib & Poin</span>
                                    <span class="px-1.5 py-0.5 rounded-md text-2xs font-black" :class="activeTab === 'master_aturan' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
                                        {{ masterList?.length || 0 }}
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tombol Panah Kanan -->
                    <button
                        type="button"
                        class="btn-nav-scroll border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-rose-600 hover:bg-rose-50 transition w-[34px] h-[34px] z-5 cursor-pointer"
                        onclick="document.getElementById('navTabsBk')?.scrollBy({ left: 220, behavior: 'smooth' })"
                        title="Geser ke Kanan"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- TAB 1: BUKU REKAM PELANGGARAN (STANDAR 3-BAGIAN TABEL DATA) -->
            <div v-show="activeTab === 'buku_pelanggaran'" class="space-y-4">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <!-- 1. FILTER BAR (BAGIAN ATAS SESUAI STANDAR BAKU AGENTS.MD) -->
                    <div class="p-3.5 bg-slate-50/70 border-b border-slate-200/80 overflow-x-auto no-scrollbar">
                        <form @submit.prevent="applyFilters" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
                            <!-- Super Admin Tenant Filter -->
                            <div class="w-48 sm:w-56 shrink-0" v-if="isSuperAdmin">
                                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">
                                    <i class="bi bi-buildings me-1 text-slate-500"></i> Sekolah (Tenant)
                                </label>
                                <select
                                    v-model="tenantId"
                                    @change="applyFilters"
                                    class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition"
                                >
                                    <option value="">-- Semua Sekolah (Global) --</option>
                                    <option v-for="t in tenants || []" :key="t.id" :value="t.id">
                                        {{ t.nama_sekolah }}
                                    </option>
                                </select>
                            </div>

                            <!-- Kategori Filter -->
                            <div class="w-36 sm:w-40 shrink-0">
                                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Kategori</label>
                                <select
                                    v-model="kategori"
                                    @change="applyFilters"
                                    class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition"
                                >
                                    <option value="">Semua Kategori</option>
                                    <option value="Ringan">Ringan (1 - 10 Poin)</option>
                                    <option value="Sedang">Sedang (11 - 25 Poin)</option>
                                    <option value="Berat">Berat (26 - 50 Poin)</option>
                                    <option value="Khusus">Khusus (51+ Poin)</option>
                                </select>
                            </div>

                            <!-- Status Pembinaan Filter -->
                            <div class="w-36 sm:w-40 shrink-0">
                                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Status Pembinaan</label>
                                <select
                                    v-model="statusPembinaan"
                                    @change="applyFilters"
                                    class="w-full h-9 px-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="Belum Dibina">Belum Dibina</option>
                                    <option value="Dalam Pembinaan">Dalam Pembinaan</option>
                                    <option value="Selesai">Selesai Dibina</option>
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
                                        class="h-9 px-2.5 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition shadow-2xs"
                                    />
                                </div>
                                <span class="text-slate-400 mb-2 font-bold">-</span>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Sampai</label>
                                    <input
                                        v-model="endDate"
                                        type="date"
                                        @change="applyFilters"
                                        class="h-9 px-2.5 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition shadow-2xs"
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
                                        placeholder="Cari siswa, NISN, pelanggaran, pelapor..."
                                        class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition shadow-2xs"
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
                                    class="h-9 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap"
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

                    <!-- 2. DATA TABLE (BAGIAN TENGAH) -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4 min-w-[200px]">Identitas Siswa</th>
                                    <th class="py-3 px-4 min-w-[220px]">Jenis Pelanggaran</th>
                                    <th class="py-3 px-4 w-28 text-center">Bobot Poin</th>
                                    <th class="py-3 px-4 min-w-[150px]">Tanggal & Pelapor</th>
                                    <th class="py-3 px-4 min-w-[180px]">Sanksi & Tindakan</th>
                                    <th class="py-3 px-4 w-32 text-center">Status</th>
                                    <th class="py-3 px-4 w-32 text-center sticky right-0 bg-slate-50 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] z-10">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(item, idx) in pelanggaranList?.data || []"
                                    :key="item.id"
                                    class="hover:bg-blue-50/40 transition group"
                                >
                                    <!-- No -->
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400">
                                        {{ ((pelanggaranList.current_page || 1) - 1) * (pelanggaranList.per_page || 15) + idx + 1 }}
                                    </td>

                                    <!-- Siswa Info -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-rose-500 to-amber-500 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0 shadow-xs">
                                                {{ (item.snapshot_nama_siswa || item.siswa?.nama_lengkap || 'S').charAt(0) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-800 flex items-center gap-1.5 truncate group-hover:text-rose-600 transition-colors">
                                                    <span>{{ item.snapshot_nama_siswa || item.siswa?.nama_lengkap || 'Siswa' }}</span>
                                                    <span v-if="item.foto_bukti" class="text-rose-500 shrink-0" title="Ada Foto Bukti">
                                                        <i class="bi bi-camera-fill text-2xs"></i>
                                                    </span>
                                                </div>
                                                <div class="text-slate-400 text-2xs flex items-center gap-1.5 flex-wrap">
                                                    <span>NISN: {{ item.snapshot_nisn || item.siswa?.nisn || '-' }}</span>
                                                    <span>•</span>
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold text-[10px]">
                                                        {{ item.snapshot_nama_kelas || 'Kelas Siswa' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Pelanggaran -->
                                    <td class="py-3.5 px-4">
                                        <div class="space-y-1 max-w-xs">
                                            <div class="font-bold text-slate-800 truncate" :title="item.nama_pelanggaran || item.nama_pelanggaran_siswa">
                                                {{ item.nama_pelanggaran || item.nama_pelanggaran_siswa }}
                                            </div>
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="px-2 py-0.5 rounded-md text-2xs font-bold border" :class="getKategoriBadge(item.kategori)">
                                                    {{ item.kategori || 'Ringan' }}
                                                </span>
                                                <span v-if="item.keterangan" class="text-slate-500 text-2xs line-clamp-1" :title="item.keterangan">
                                                    {{ item.keterangan }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Poin -->
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-xl font-black text-rose-700 bg-rose-50 border border-rose-200/80 text-xs shadow-2xs">
                                            +{{ item.poin_pelanggaran }} Poin
                                        </span>
                                    </td>

                                    <!-- Tanggal & Pelapor -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-700 flex items-center gap-1">
                                            <i class="bi bi-calendar-event text-slate-400"></i>
                                            {{ formatDate(item.tanggal_kejadian) }}
                                        </div>
                                        <div class="text-slate-400 text-2xs flex items-center gap-1 mt-0.5">
                                            <i class="bi bi-person text-slate-400"></i>
                                            <span class="truncate max-w-[140px]">{{ item.petugas_pencatat || 'Guru BK' }}</span>
                                        </div>
                                    </td>

                                    <!-- Tindakan / Sanksi -->
                                    <td class="py-3.5 px-4 max-w-xs">
                                        <div class="text-slate-700 text-xs line-clamp-2 leading-relaxed" :title="item.tindakan_hukuman || 'Belum ditentukan'">
                                            {{ item.tindakan_hukuman || '-' }}
                                        </div>
                                    </td>

                                    <!-- Status Pembinaan -->
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-xl text-2xs font-bold border" :class="getStatusPembinaanBadge(item.status_pembinaan)">
                                            {{ item.status_pembinaan || 'Belum Dibina' }}
                                        </span>
                                    </td>

                                    <!-- Action Buttons (Sticky Right Sesuai Standar Baku) -->
                                    <td class="py-3.5 px-4 text-center sticky right-0 bg-white group-hover:bg-blue-50/90 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] z-10">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                type="button"
                                                @click="openDetailStudentModal(item)"
                                                class="w-7 h-7 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition"
                                                title="Lihat Detail & Kronologi"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button
                                                type="button"
                                                @click="openCetakModal(item)"
                                                class="w-7 h-7 rounded-xl bg-purple-50 text-purple-600 hover:bg-purple-100 flex items-center justify-center transition"
                                                title="Cetak Surat Panggilan / Berita Acara"
                                            >
                                                <i class="bi bi-printer"></i>
                                            </button>
                                            <button
                                                type="button"
                                                @click="openEditRecordModal(item)"
                                                class="w-7 h-7 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition"
                                                title="Edit Catatan"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteRecord(item)"
                                                class="w-7 h-7 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition"
                                                title="Hapus Catatan"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!pelanggaranList?.data?.length">
                                    <td colspan="8" class="py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl">
                                                <i class="bi bi-shield-check"></i>
                                            </div>
                                            <div class="font-bold text-slate-700">Tidak Ada Rekam Pelanggaran</div>
                                            <p class="text-xs text-slate-400 max-w-sm">
                                                Tidak ditemukan data pelanggaran dengan filter yang diterapkan saat ini.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 3. FOOTER PAGINATION (BAGIAN BAWAH SESUAI STANDAR BAKU AGENTS.MD) -->
                    <div v-if="pelanggaranList?.total > 0" class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
                        <!-- Info Tampilkan Baris & Dropdown per_page -->
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
                            <span>Tampilkan</span>
                            <select
                                v-model="perPage"
                                @change="applyFilters"
                                class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
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
                                Menampilkan <span class="font-bold text-slate-800">{{ pelanggaranList.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ pelanggaranList.to || pelanggaranList.total }}</span> dari <span class="font-bold text-slate-800">{{ pelanggaranList.total }}</span> baris
                            </span>
                        </div>

                        <!-- Pagination Links (Smart Windowing & Compact Chevrons) -->
                        <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
                            <template v-for="(link, i) in getSmartPaginationLinks(pelanggaranList)" :key="i">
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
                                    class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-rose-600 text-white shadow-xs"
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

            <!-- TAB 2: SISWA BUTUH PEMBINAAN (RISK RANKING) -->
            <div v-show="activeTab === 'siswa_pembinaan'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm">Peringkat Akumulasi Poin Siswa (At Risk)</h3>
                            <p class="text-slate-500 text-2xs">Daftar siswa dengan akumulasi poin tertinggi yang memerlukan pendampingan intensif BK.</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-2xs font-bold bg-rose-100 text-rose-700">
                            {{ topStudents?.length || 0 }} Siswa Teridentifikasi
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-500 text-2xs uppercase tracking-wider font-bold border-b border-slate-200">
                                    <th class="py-3 px-4 w-12 text-center">Rank</th>
                                    <th class="py-3 px-4">Nama Siswa & Identitas</th>
                                    <th class="py-3 px-4 text-center">Total Poin</th>
                                    <th class="py-3 px-4 text-center">Jumlah Kasus</th>
                                    <th class="py-3 px-4">Kejadian Terakhir</th>
                                    <th class="py-3 px-4">Tingkat Penanganan</th>
                                    <th class="py-3 px-4 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(st, idx) in topStudents || []"
                                    :key="st.siswa_id"
                                    class="hover:bg-slate-50/60 transition"
                                >
                                    <td class="py-3.5 px-4 text-center font-black" :class="idx < 3 ? 'text-rose-600' : 'text-slate-400'">
                                        #{{ idx + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800">{{ st.nama_siswa }}</div>
                                        <div class="text-slate-400 text-2xs">
                                            NISN: {{ st.nisn }} • <span class="text-slate-600 font-semibold">{{ st.nama_kelas }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-lg font-black text-xs" :class="st.total_poin >= 50 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'">
                                            {{ st.total_poin }} Poin
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-700">
                                        {{ st.jumlah_kasus }} Kasus
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600">
                                        {{ formatDate(st.kejadian_terakhir) }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-md text-2xs font-bold border" :class="st.badge_color">
                                            {{ st.tingkat_sanksi }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <button
                                            type="button"
                                            @click="openCetakModal(st)"
                                            class="px-3 py-1 bg-purple-50 text-purple-700 hover:bg-purple-100 font-bold rounded-lg text-2xs transition flex items-center justify-center gap-1 mx-auto"
                                        >
                                            <i class="bi bi-printer"></i>
                                            <span>Surat SP</span>
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="!topStudents?.length">
                                    <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                        Tidak ada siswa dengan catatan pelanggaran aktif.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: KATALOG TATA TERTIB & POIN -->
            <div v-show="activeTab === 'master_aturan'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm">Katalog Aturan & Bobot Poin Tata Tertib</h3>
                            <p class="text-slate-500 text-2xs">Daftar regulasi tata tertib sekolah beserta bobot sanksi dan kategori pembinaan.</p>
                        </div>
                        <button
                            type="button"
                            @click="openCreateMasterModal"
                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 self-start sm:self-auto"
                        >
                            <i class="bi bi-plus-circle"></i>
                            <span>Tambah Aturan Baru</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-500 text-2xs uppercase tracking-wider font-bold border-b border-slate-200">
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4">Nama Aturan Pelanggaran</th>
                                    <th class="py-3 px-4">Kategori</th>
                                    <th class="py-3 px-4 text-center">Bobot Poin</th>
                                    <th class="py-3 px-4">Deskripsi / Penjelasan</th>
                                    <th class="py-3 px-4 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(rule, idx) in masterList || []"
                                    :key="rule.id"
                                    class="hover:bg-slate-50/60 transition"
                                >
                                    <td class="py-3.5 px-4 text-center font-bold text-slate-400">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-slate-800">
                                        {{ rule.nama_pelanggaran || rule.nama_master_pelanggaran }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-0.5 rounded-md text-2xs font-bold border" :class="getKategoriBadge(rule.kategori)">
                                            {{ rule.kategori || 'Ringan' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-lg font-black text-rose-700 bg-rose-50 border border-rose-200 text-2xs">
                                            +{{ rule.bobot_poin }} Poin
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600 max-w-sm">
                                        {{ rule.deskripsi || '-' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                type="button"
                                                @click="openEditMasterModal(rule)"
                                                class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition"
                                                title="Edit Aturan"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteMasterRule(rule)"
                                                class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition"
                                                title="Hapus Aturan"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!masterList?.length">
                                    <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                        Belum ada data katalog aturan tata tertib.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MODAL CATAT / EDIT PELANGGARAN -->
            <div v-if="showModalRecord" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                                <i class="bi bi-journal-plus"></i>
                            </div>
                            <h3 class="font-extrabold text-slate-800 text-base">
                                {{ isEditingRecord ? 'Edit Catatan Pelanggaran' : 'Catat Pelanggaran Siswa' }}
                            </h3>
                        </div>
                        <button type="button" @click="showModalRecord = false" class="text-slate-400 hover:text-slate-600 text-lg">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitRecordForm" class="space-y-3.5">
                        <!-- Cari & Pilih Siswa -->
                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">
                                Siswa yang Melanggar <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                                <input
                                    v-model="siswaSearchQuery"
                                    type="text"
                                    placeholder="Ketik nama atau NISN siswa..."
                                    class="w-full pl-8 pr-8 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition"
                                    @input="onSiswaSearchInput"
                                    :disabled="isEditingRecord"
                                    required
                                />
                                <button
                                    v-if="selectedSiswa && !isEditingRecord"
                                    type="button"
                                    @click="clearSelectedSiswa"
                                    class="absolute right-2.5 top-2.5 text-slate-400 hover:text-rose-600 text-xs"
                                >
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>

                            <!-- Autocomplete Dropdown List -->
                            <div
                                v-if="siswaSearchResults.length"
                                class="mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100 z-50 relative"
                            >
                                <button
                                    v-for="s in siswaSearchResults"
                                    :key="s.id"
                                    type="button"
                                    @click="chooseSiswa(s)"
                                    class="w-full p-2.5 text-left hover:bg-rose-50/50 transition flex items-center justify-between"
                                >
                                    <div>
                                        <div class="font-bold text-xs text-slate-800">{{ s.nama_lengkap }}</div>
                                        <div class="text-2xs text-slate-400">NISN: {{ s.nisn }} • {{ s.nama_kelas }}</div>
                                    </div>
                                    <span class="text-2xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded">Pilih</span>
                                </button>
                            </div>
                        </div>

                        <!-- Pilih dari Master Tata Tertib (Opsional) -->
                        <div v-if="masterList && masterList.length">
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Pilih dari Master Aturan (Opsional)</label>
                            <select
                                v-model="recordForm.pelanggaran_id"
                                @change="onMasterSelect"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                            >
                                <option value="">-- Pilih Aturan Tata Tertib Standar --</option>
                                <option v-for="m in masterList" :key="m.id" :value="m.id">
                                    [{{ m.kategori }} | +{{ m.bobot_poin }} Poin] {{ m.nama_pelanggaran || m.nama_master_pelanggaran }}
                                </option>
                            </select>
                        </div>

                        <!-- Nama Pelanggaran -->
                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">
                                Nama / Bentuk Pelanggaran <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="recordForm.nama_pelanggaran"
                                type="text"
                                placeholder="Contoh: Terlambat Masuk Sekolah"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                required
                            />
                        </div>

                        <!-- Kategori & Poin Pelanggaran -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">
                                    Kategori <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="recordForm.kategori"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                    required
                                >
                                    <option value="Ringan">Ringan (1 - 10 Poin)</option>
                                    <option value="Sedang">Sedang (11 - 25 Poin)</option>
                                    <option value="Berat">Berat (26 - 50 Poin)</option>
                                    <option value="Khusus">Khusus (51+ Poin)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">
                                    Bobot Poin <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model.number="recordForm.poin_pelanggaran"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Tanggal Kejadian & Petugas Pencatat -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">
                                    Tanggal Kejadian <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="recordForm.tanggal_kejadian"
                                    type="date"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Petugas / Pelapor</label>
                                <input
                                    v-model="recordForm.petugas_pencatat"
                                    type="text"
                                    placeholder="Nama Guru BK / Wali Kelas"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                />
                            </div>
                        </div>

                        <!-- Tindakan Hukuman / Pembinaan -->
                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Sanksi / Tindakan Pembinaan</label>
                            <input
                                v-model="recordForm.tindakan_hukuman"
                                type="text"
                                placeholder="Contoh: Bimbingan Konseling & Pembersihan Area Kelas"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                            />
                        </div>

                        <!-- Keterangan Detail -->
                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Keterangan / Kronologi Singkat</label>
                            <textarea
                                v-model="recordForm.keterangan"
                                rows="2"
                                placeholder="Tuliskan kronologi singkat pelanggaran..."
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                            ></textarea>
                        </div>

                        <!-- Status Pembinaan & Bukti Foto -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Status Pembinaan</label>
                                <select
                                    v-model="recordForm.status_pembinaan"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                >
                                    <option value="Belum Dibina">Belum Dibina</option>
                                    <option value="Dalam Pembinaan">Dalam Pembinaan</option>
                                    <option value="Selesai">Selesai Dibina</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Upload Bukti Foto</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="onFileChange"
                                    class="w-full text-2xs text-slate-500 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:text-2xs file:font-bold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 cursor-pointer"
                                />
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="showModalRecord = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="recordForm.processing"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5 disabled:opacity-50"
                            >
                                <i class="bi bi-check-circle"></i>
                                <span>{{ isEditingRecord ? 'Perbarui Pelanggaran' : 'Simpan Pelanggaran' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL KELOLA MASTER ATURAN -->
            <div v-if="showModalMaster" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-bold">
                                <i class="bi bi-gear-fill"></i>
                            </div>
                            <h3 class="font-extrabold text-slate-800 text-base">
                                {{ isEditingMaster ? 'Edit Aturan Tata Tertib' : 'Tambah Aturan Tata Tertib' }}
                            </h3>
                        </div>
                        <button type="button" @click="showModalMaster = false" class="text-slate-400 hover:text-slate-600 text-lg">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitMasterForm" class="space-y-3.5">
                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">
                                Nama Aturan Pelanggaran <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="masterForm.nama_pelanggaran"
                                type="text"
                                placeholder="Contoh: Membawa senjata tajam atau miras"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">
                                    Kategori <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="masterForm.kategori"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                    required
                                >
                                    <option value="Ringan">Ringan (1 - 10 Poin)</option>
                                    <option value="Sedang">Sedang (11 - 25 Poin)</option>
                                    <option value="Berat">Berat (26 - 50 Poin)</option>
                                    <option value="Khusus">Khusus (51+ Poin)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">
                                    Bobot Poin <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model.number="masterForm.bobot_poin"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-slate-500 uppercase mb-1">Deskripsi / Penjelasan Sanksi</label>
                            <textarea
                                v-model="masterForm.deskripsi"
                                rows="2"
                                placeholder="Tuliskan konsekuensi atau sanksi standar..."
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                            ></textarea>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="showModalMaster = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="masterForm.processing"
                                class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5 disabled:opacity-50"
                            >
                                <i class="bi bi-check-circle"></i>
                                <span>{{ isEditingMaster ? 'Perbarui Aturan' : 'Simpan Aturan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL DETAIL & KRONOLOGI PELANGGARAN -->
            <div v-if="showModalDetail && detailStudent" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                <i class="bi bi-info-circle-fill"></i>
                            </div>
                            <h3 class="font-extrabold text-slate-800 text-base">Detail & Kronologi Pelanggaran</h3>
                        </div>
                        <button type="button" @click="showModalDetail = false" class="text-slate-400 hover:text-slate-600 text-lg">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Student Info Card -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div>
                            <div class="font-extrabold text-slate-800 text-sm">
                                {{ detailStudent.snapshot_nama_siswa || detailStudent.siswa?.nama_lengkap || 'Siswa' }}
                            </div>
                            <div class="text-slate-500 text-2xs mt-0.5">
                                NISN: {{ detailStudent.snapshot_nisn || detailStudent.siswa?.nisn || '-' }} • {{ detailStudent.snapshot_nama_kelas || 'Kelas Siswa' }}
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-lg font-black text-xs text-rose-700 bg-rose-100">
                            +{{ detailStudent.poin_pelanggaran }} Poin
                        </span>
                    </div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-slate-400 text-2xs font-bold uppercase mb-0.5">Kategori Pelanggaran</div>
                            <span class="px-2 py-0.5 rounded text-2xs font-bold border" :class="getKategoriBadge(detailStudent.kategori)">
                                {{ detailStudent.kategori || 'Ringan' }}
                            </span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-slate-400 text-2xs font-bold uppercase mb-0.5">Tanggal Kejadian</div>
                            <div class="font-bold text-slate-700">{{ formatDate(detailStudent.tanggal_kejadian) }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-slate-400 text-2xs font-bold uppercase mb-0.5">Petugas / Pelapor</div>
                            <div class="font-bold text-slate-700">{{ detailStudent.petugas_pencatat || '-' }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-slate-400 text-2xs font-bold uppercase mb-0.5">Status Pembinaan</div>
                            <span class="px-2 py-0.5 rounded text-2xs font-bold border" :class="getStatusPembinaanBadge(detailStudent.status_pembinaan)">
                                {{ detailStudent.status_pembinaan || 'Belum Dibina' }}
                            </span>
                        </div>
                    </div>

                    <!-- Tindakan & Kronologi -->
                    <div class="space-y-2 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-slate-400 text-2xs font-bold uppercase mb-1">Bentuk Pelanggaran:</div>
                            <div class="font-bold text-slate-800">{{ detailStudent.nama_pelanggaran || detailStudent.nama_pelanggaran_siswa }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-slate-400 text-2xs font-bold uppercase mb-1">Sanksi / Tindakan:</div>
                            <div class="text-slate-700">{{ detailStudent.tindakan_hukuman || 'Belum ada tindakan spesifik.' }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-slate-400 text-2xs font-bold uppercase mb-1">Kronologi / Catatan:</div>
                            <div class="text-slate-700 leading-relaxed">{{ detailStudent.keterangan || detailStudent.deskripsi || 'Tidak ada catatan tambahan.' }}</div>
                        </div>
                    </div>

                    <!-- Bukti Foto -->
                    <div v-if="detailStudent.foto_bukti">
                        <div class="text-slate-400 text-2xs font-bold uppercase mb-1">Lampiran Foto Bukti:</div>
                        <img :src="detailStudent.foto_bukti" alt="Bukti" class="w-full max-h-48 object-cover rounded-xl border border-slate-200" />
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="openCetakModal(detailStudent)"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5"
                        >
                            <i class="bi bi-printer"></i>
                            <span>Cetak Surat Panggilan</span>
                        </button>
                        <button
                            type="button"
                            @click="showModalDetail = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CETAK SURAT PANGGILAN ORANG TUA -->
        <div v-if="showModalCetak" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between no-print">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-file-earmark-text-fill text-rose-600 text-lg"></i>
                        <h3 class="font-extrabold text-slate-800 text-base">Surat Panggilan Orang Tua / Wali Murid</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            onclick="window.print()"
                            class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs flex items-center gap-1.5 shadow-xs transition"
                        >
                            <i class="bi bi-printer-fill"></i>
                            <span>Print Dokumen</span>
                        </button>
                        <button type="button" @click="showModalCetak = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                            &times;
                        </button>
                    </div>
                </div>

                <div class="p-8 text-slate-800 text-xs leading-relaxed printable-area" v-if="cetakData">
                    <!-- Kop Surat Dinamis -->
                    <div class="border-b-2 border-slate-800 pb-4 mb-6 text-center">
                        <h2 class="text-xs font-black uppercase tracking-wider text-slate-600">PEMERINTAH DAERAH {{ cetakData.kabupaten_provinsi }}</h2>
                        <h1 class="text-base font-black uppercase tracking-tight text-slate-900">{{ cetakData.nama_sekolah }}</h1>
                        <h3 class="text-xs font-bold uppercase text-slate-700">UNIT PELAKSANA TEKNIS BIMBINGAN DAN KONSELING (BK)</h3>
                        <p class="text-2xs text-slate-600 mt-0.5">{{ cetakData.alamat_sekolah }} • Telp. {{ cetakData.telepon_sekolah }} • Email: {{ cetakData.email_sekolah }}</p>
                    </div>

                    <!-- Nomor Surat & Perihal -->
                    <div class="flex justify-between mb-4 text-xs">
                        <div>
                            <div>Nomor : 421.3 / <span class="font-mono font-bold">BK-SP/IX/2026</span></div>
                            <div>Lampiran : -</div>
                            <div>Perihal : <strong>Panggilan Orang Tua / Wali Siswa (Pembinaan Disiplin)</strong></div>
                        </div>
                        <div class="text-right">
                            <div>Tanggal: {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</div>
                        </div>
                    </div>

                    <!-- Isi Surat -->
                    <div class="space-y-3">
                        <p>Kepada Yth.<br /><strong>Bapak / Ibu Orang Tua / Wali Murid dari:</strong></p>

                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                            <table class="w-full text-xs">
                                <tr>
                                    <td class="py-1 font-bold w-36">Nama Siswa</td>
                                    <td class="py-1">: {{ cetakData.nama_siswa }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 font-bold">NISN / Kelas</td>
                                    <td class="py-1">: {{ cetakData.nisn }} / {{ cetakData.nama_kelas }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 font-bold">Akumulasi Poin</td>
                                    <td class="py-1 font-black text-rose-600">: {{ cetakData.poin }} Poin Pelanggaran</td>
                                </tr>
                                <tr>
                                    <td class="py-1 font-bold">Catatan Kasus</td>
                                    <td class="py-1">: {{ cetakData.kasus }}</td>
                                </tr>
                            </table>
                        </div>

                        <p>Dengan hormat,</p>
                        <p>
                            Sehubungan dengan perkembangan pembinaan tata tertib dan kedisiplinan putra/putri Bapak/Ibu di sekolah, bersama surat ini kami mengharap kehadiran Bapak/Ibu Orang Tua/Wali pada:
                        </p>

                        <div class="pl-4 space-y-1">
                            <div><strong>Hari, Tanggal</strong> : {{ cetakData.tanggal_panggilan }}</div>
                            <div><strong>Waktu</strong> : {{ cetakData.waktu_panggilan }}</div>
                            <div><strong>Tempat</strong> : {{ cetakData.tempat }}</div>
                            <div><strong>Menghadap</strong> : {{ cetakData.nama_guru_bk }} (Guru BK / Tim Disiplin)</div>
                        </div>

                        <p>
                            Mengingat pentingnya koordinasi ini demi masa depan belajar dan pembentukan karakter peserta didik, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktunya.
                        </p>
                        <p>Atas perhatian dan kerja sama yang baik, kami sampaikan terima kasih.</p>
                    </div>

                    <!-- Tanda Tangan Area Dinamis -->
                    <div class="mt-8 pt-4 flex justify-between text-center text-xs">
                        <div>
                            <p>Mengetahui,<br />Kepala Sekolah</p>
                            <div class="h-16"></div>
                            <p class="font-bold underline">{{ cetakData.nama_kepsek }}</p>
                            <p class="text-2xs text-slate-500">NIP. {{ cetakData.nip_kepsek }}</p>
                        </div>
                        <div>
                            <p>Guru Bimbingan Konseling (BK)</p>
                            <div class="h-16"></div>
                            <p class="font-bold underline">{{ cetakData.nama_guru_bk }}</p>
                            <p class="text-2xs text-slate-500">NIP. {{ cetakData.nip_guru_bk }}</p>
                        </div>
                    </div>
                </div>
            </div>
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
        margin: 0;
        padding: 20px;
    }
    .no-print {
        display: none !important;
    }
}
</style>
