<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'

// ─────────────────────────────────────────────
// PROPS
// ─────────────────────────────────────────────
const props = defineProps({
    ekskulList: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 15, from: 0, to: 0 })
    },
    anggotaList: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 15, from: 0, to: 0 })
    },
    pembinaList: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 15, from: 0, to: 0 })
    },
    jurnalList: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 15, from: 0, to: 0 })
    },
    nilaiList: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 15, from: 0, to: 0 })
    },
    prestasiList: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 15, from: 0, to: 0 })
    },
    allEkskul: {
        type: Array,
        default: () => []
    },
    allPembina: {
        type: Array,
        default: () => []
    },
    allKelas: {
        type: Array,
        default: () => []
    },
    allTahunAjaran: {
        type: Array,
        default: () => []
    },
    allSiswa: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({ total_ekskul: 0, total_anggota: 0, total_pembina: 0, total_jurnal: 0, total_prestasi: 0 })
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
        default: () => ({ tab: 'ekskul', search: '', tenant_id: '', tahun_ajaran_id: '', semester: 'Ganjil', per_page: 15 })
    }
})

// ─────────────────────────────────────────────
// ACTIVE TAB & PERIOD & FILTERS
// ─────────────────────────────────────────────
const activeTab = ref(props.filters.tab || 'ekskul')
const search = ref(props.filters.search || '')
const tenantId = ref(props.filters.tenant_id || '')
const perPage = ref(props.filters.per_page || 15)

// Academic Period Global State
const selectedTahunAjaranId = ref(props.filters.tahun_ajaran_id || (props.allTahunAjaran.find(t => t.is_active)?.id || props.allTahunAjaran[0]?.id || ''))
const selectedSemester = ref(props.filters.semester || 'Ganjil')

// Contextual Filter States per tab
const filterKategori = ref(props.filters.kategori || '')
const filterStatus = ref(props.filters.status !== undefined && props.filters.status !== null ? String(props.filters.status) : '')
const filterEkskulId = ref(props.filters.ekskul_id || '')
const filterJabatan = ref(props.filters.jabatan || '')
const filterPembinaId = ref(props.filters.pembina_id || '')
const filterPredikat = ref(props.filters.predikat || '')
const filterTingkat = ref(props.filters.tingkat || '')
const filterJuara = ref(props.filters.juara || '')
const filterJenisKelamin = ref(props.filters.jenis_kelamin || '')

const hasActiveFilters = computed(() => {
    return !!(
        search.value ||
        filterKategori.value ||
        filterStatus.value !== '' ||
        filterEkskulId.value ||
        filterJabatan.value ||
        filterPembinaId.value ||
        filterPredikat.value ||
        filterTingkat.value ||
        filterJuara.value ||
        filterJenisKelamin.value
    )
})

let searchTimeout = null
function handleSearch() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 400)
}

function handlePeriodChange() {
    applyFilters()
}

function switchTab(tabName) {
    activeTab.value = tabName
    search.value = ''
    filterKategori.value = ''
    filterStatus.value = ''
    filterEkskulId.value = ''
    filterJabatan.value = ''
    filterPembinaId.value = ''
    filterPredikat.value = ''
    filterTingkat.value = ''
    filterJuara.value = ''
    filterJenisKelamin.value = ''
    applyFilters()
}

function applyFilters() {
    router.get('/kesiswaan/ekskul', {
        tab: activeTab.value,
        search: search.value || undefined,
        tenant_id: tenantId.value || undefined,
        tahun_ajaran_id: selectedTahunAjaranId.value || undefined,
        semester: selectedSemester.value || undefined,
        per_page: perPage.value !== 15 ? perPage.value : undefined,
        kategori: filterKategori.value || undefined,
        status: filterStatus.value !== '' ? filterStatus.value : undefined,
        ekskul_id: filterEkskulId.value || undefined,
        jabatan: filterJabatan.value || undefined,
        pembina_id: filterPembinaId.value || undefined,
        predikat: filterPredikat.value || undefined,
        tingkat: filterTingkat.value || undefined,
        juara: filterJuara.value || undefined,
        jenis_kelamin: filterJenisKelamin.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function resetFilters() {
    search.value = ''
    filterKategori.value = ''
    filterStatus.value = ''
    filterEkskulId.value = ''
    filterJabatan.value = ''
    filterPembinaId.value = ''
    filterPredikat.value = ''
    filterTingkat.value = ''
    filterJuara.value = ''
    filterJenisKelamin.value = ''
    applyFilters()
}

function changePage(url) {
    if (!url) return
    router.visit(url, { preserveState: true, preserveScroll: true })
}

function getSelectedTenantName() {
    if (!tenantId.value || tenantId.value === 'all') {
        return 'Semua Sekolah (Global Multi-Tenant)'
    }
    const found = props.tenants.find(t => t.id === tenantId.value)
    return found ? `${found.nama_sekolah} (${found.npsn || 'NPSN -'})` : 'Sekolah Terpilih'
}

function getSelectedTahunAjaranName() {
    const found = props.allTahunAjaran.find(t => t.id === selectedTahunAjaranId.value)
    return found ? found.nama_tahun_ajaran : 'Tahun Ajaran Aktif'
}

// ─────────────────────────────────────────────
// HORIZONTAL SCROLLER DRAG & WHEEL
// ─────────────────────────────────────────────
onMounted(() => {
    const navEl = document.getElementById('navTabsEkskul')
    if (navEl) {
        let isDown = false
        let startX = 0
        let scrollLeft = 0

        navEl.addEventListener('mousedown', (e) => {
            isDown = true
            startX = e.pageX - navEl.offsetLeft
            scrollLeft = navEl.scrollLeft
        })
        navEl.addEventListener('mouseleave', () => { isDown = false })
        navEl.addEventListener('mouseup', () => { isDown = false })
        navEl.addEventListener('mousemove', (e) => {
            if (!isDown) return
            e.preventDefault()
            const x = e.pageX - navEl.offsetLeft
            const walk = (x - startX) * 1.5
            navEl.scrollLeft = scrollLeft - walk
        })
        navEl.addEventListener('wheel', (e) => {
            if (e.deltaY !== 0) {
                e.preventDefault()
                navEl.scrollLeft += e.deltaY
            }
        }, { passive: false })
    }
})

// ─────────────────────────────────────────────
// MODAL STATE: 1. MASTER EKSKUL
// ─────────────────────────────────────────────
const showEkskulModal = ref(false)
const isEditEkskul = ref(false)
const ekskulForm = useForm({
    id: '',
    tenant_id: '',
    nama_ekskul: '',
    kategori: 'Olahraga',
    deskripsi: '',
    pembina_id: '',
    hari_latihan: 'Sabtu',
    jam_mulai: '15:00',
    jam_selesai: '17:00',
    tempat_latihan: 'Lapangan Utama',
    kuota_maksimal: 50,
    is_active: true,
})

const kategoriEkskulOptions = [
    'Olahraga',
    'Seni & Budaya',
    'Keagamaan',
    'Akademik & Sains',
    'Kepemimpinan & Organisasi',
    'Teknologi & Robotik',
    'Bahasa & Sastra',
    'Pramuka & PMR',
    'Lainnya',
]

const hariOptions = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']

function openCreateEkskul() {
    isEditEkskul.value = false
    ekskulForm.reset()
    ekskulForm.clearErrors()
    ekskulForm.kategori = 'Olahraga'
    ekskulForm.hari_latihan = 'Sabtu'
    ekskulForm.jam_mulai = '15:00'
    ekskulForm.jam_selesai = '17:00'
    ekskulForm.tempat_latihan = 'Lapangan Sekolah'
    ekskulForm.kuota_maksimal = 50
    ekskulForm.is_active = true
    ekskulForm.tenant_id = tenantId.value || (props.tenants.length > 0 ? props.tenants[0].id : '')
    showEkskulModal.value = true
}

function openEditEkskul(item) {
    isEditEkskul.value = true
    ekskulForm.clearErrors()
    ekskulForm.id = item.id
    ekskulForm.tenant_id = item.tenant_id || ''
    ekskulForm.nama_ekskul = item.nama_ekskul || ''
    ekskulForm.kategori = item.kategori || 'Olahraga'
    ekskulForm.deskripsi = item.deskripsi || ''
    ekskulForm.pembina_id = item.pembina_id || ''
    ekskulForm.hari_latihan = item.hari_latihan || 'Sabtu'
    ekskulForm.jam_mulai = item.jam_mulai || '15:00'
    ekskulForm.jam_selesai = item.jam_selesai || '17:00'
    ekskulForm.tempat_latihan = item.tempat_latihan || ''
    ekskulForm.kuota_maksimal = item.kuota_maksimal || 50
    ekskulForm.is_active = item.is_active !== undefined ? !!item.is_active : true
    showEkskulModal.value = true
}

function submitEkskul() {
    if (isEditEkskul.value) {
        ekskulForm.put(`/kesiswaan/ekskul/${ekskulForm.id}`, {
            preserveScroll: true,
            onSuccess: () => { showEkskulModal.value = false; ekskulForm.reset() }
        })
    } else {
        ekskulForm.post('/kesiswaan/ekskul', {
            preserveScroll: true,
            onSuccess: () => { showEkskulModal.value = false; ekskulForm.reset() }
        })
    }
}

// ─────────────────────────────────────────────
// MODAL STATE: 2. ANGGOTA EKSKUL (SINGLE, BATCH, COPY)
// ─────────────────────────────────────────────
const showAnggotaModal = ref(false)
const isEditAnggota = ref(false)
const singleSiswaFilterKelas = ref('')

const filteredAllSiswaForSingle = computed(() => {
    if (!singleSiswaFilterKelas.value) return props.allSiswa
    return props.allSiswa.filter(s => 
        s.kelas_saat_ini === singleSiswaFilterKelas.value ||
        (s.kelas_saat_ini && typeof s.kelas_saat_ini === 'string' && s.kelas_saat_ini.toLowerCase().includes(singleSiswaFilterKelas.value.toLowerCase()))
    )
})

const anggotaForm = useForm({
    id: '',
    tenant_id: '',
    ekskul_id: '',
    siswa_id: '',
    tahun_ajaran_id: '',
    semester: 'Ganjil',
    jabatan: 'Anggota',
    nomor_anggota: '',
    tanggal_bergabung: new Date().toISOString().split('T')[0],
    status_keanggotaan: 'Aktif',
    catatan: '',
})

const jabatanOptions = ['Ketua', 'Wakil Ketua', 'Sekretaris', 'Bendahara', 'Koordinator Divisi', 'Anggota']
const statusAnggotaOptions = ['Aktif', 'Non-Aktif', 'Cuti', 'Alumni']

function openCreateAnggota() {
    isEditAnggota.value = false
    singleSiswaFilterKelas.value = ''
    anggotaForm.reset()
    anggotaForm.clearErrors()
    anggotaForm.tahun_ajaran_id = selectedTahunAjaranId.value || (props.allTahunAjaran[0]?.id || '')
    anggotaForm.semester = selectedSemester.value || 'Ganjil'
    anggotaForm.jabatan = 'Anggota'
    anggotaForm.status_keanggotaan = 'Aktif'
    anggotaForm.tanggal_bergabung = new Date().toISOString().split('T')[0]
    anggotaForm.tenant_id = tenantId.value || (props.tenants.length > 0 ? props.tenants[0].id : '')
    anggotaForm.ekskul_id = filterEkskulId.value || (props.allEkskul.length > 0 ? props.allEkskul[0].id : '')
    if (props.allSiswa.length > 0) anggotaForm.siswa_id = props.allSiswa[0].id
    showAnggotaModal.value = true
}

function openEditAnggota(item) {
    isEditAnggota.value = true
    singleSiswaFilterKelas.value = ''
    anggotaForm.clearErrors()
    anggotaForm.id = item.id
    anggotaForm.tenant_id = item.tenant_id || ''
    anggotaForm.ekskul_id = item.ekskul_id || ''
    anggotaForm.siswa_id = item.siswa_id || ''
    anggotaForm.tahun_ajaran_id = item.tahun_ajaran_id || selectedTahunAjaranId.value || ''
    anggotaForm.semester = item.semester || selectedSemester.value || 'Ganjil'
    anggotaForm.jabatan = item.jabatan || 'Anggota'
    anggotaForm.nomor_anggota = item.nomor_anggota || ''
    anggotaForm.tanggal_bergabung = item.tanggal_bergabung || ''
    anggotaForm.status_keanggotaan = item.status_keanggotaan || 'Aktif'
    anggotaForm.catatan = item.catatan || ''
    showAnggotaModal.value = true
}

function submitAnggota() {
    if (isEditAnggota.value) {
        anggotaForm.put(`/kesiswaan/ekskul/anggota/${anggotaForm.id}`, {
            preserveScroll: true,
            onSuccess: () => { showAnggotaModal.value = false; anggotaForm.reset() }
        })
    } else {
        anggotaForm.post('/kesiswaan/ekskul/anggota', {
            preserveScroll: true,
            onSuccess: () => { showAnggotaModal.value = false; anggotaForm.reset() }
        })
    }
}

// ── BATCH ANGGOTA PER KELAS ──
const showBatchAnggotaModal = ref(false)
const batchForm = useForm({
    ekskul_id: '',
    kelas_id: '',
    tahun_ajaran_id: '',
    semester: 'Ganjil',
    jabatan: 'Anggota',
    status_keanggotaan: 'Aktif',
    tanggal_bergabung: new Date().toISOString().split('T')[0],
    siswa_ids: [],
    tenant_id: '',
})
const kelasSiswaList = ref([])
const isLoadingSiswaKelas = ref(false)
const searchSiswaKelas = ref('')

const filteredEkskulForBatch = computed(() => {
    if (!props.isSuperAdmin || !batchForm.tenant_id) return props.allEkskul
    return props.allEkskul.filter(e => !e.tenant_id || e.tenant_id === batchForm.tenant_id)
})

const filteredKelasForBatch = computed(() => {
    if (!props.isSuperAdmin || !batchForm.tenant_id) return props.allKelas
    return props.allKelas.filter(k => !k.tenant_id || k.tenant_id === batchForm.tenant_id)
})

const filteredSiswaKelas = computed(() => {
    if (!searchSiswaKelas.value) return kelasSiswaList.value
    const q = searchSiswaKelas.value.toLowerCase()
    return kelasSiswaList.value.filter(s => 
        (s.nama_lengkap && s.nama_lengkap.toLowerCase().includes(q)) ||
        (s.nisn && s.nisn.toLowerCase().includes(q)) ||
        (s.nis && s.nis.toLowerCase().includes(q))
    )
})

const isAllSiswaSelected = computed(() => {
    const available = filteredSiswaKelas.value.filter(s => !s.is_already_member)
    if (available.length === 0) return false
    return available.every(s => batchForm.siswa_ids.includes(s.id))
})

async function loadSiswaByKelas() {
    if (!batchForm.kelas_id) return
    isLoadingSiswaKelas.value = true
    try {
        const targetTenant = batchForm.tenant_id || tenantId.value || ''
        const res = await fetch(`/kesiswaan/ekskul/siswa-by-kelas?kelas_id=${batchForm.kelas_id}&ekskul_id=${batchForm.ekskul_id}&tahun_ajaran_id=${batchForm.tahun_ajaran_id}&semester=${batchForm.semester}&tenant_id=${targetTenant}`, {
            headers: { 'Accept': 'application/json' }
        })
        const data = await res.json()
        if (data.success) {
            kelasSiswaList.value = data.data || []
            // Otomatis centang seluruh siswa yang belum terdaftar di ekskul ini
            batchForm.siswa_ids = (data.data || []).filter(s => !s.is_already_member).map(s => s.id)
        }
    } catch (e) {
        console.error('Error fetching siswa by kelas:', e)
    } finally {
        isLoadingSiswaKelas.value = false
    }
}

function onBatchTenantChange() {
    const availableEkskuls = filteredEkskulForBatch.value
    if (availableEkskuls.length > 0 && !availableEkskuls.some(e => e.id === batchForm.ekskul_id)) {
        batchForm.ekskul_id = availableEkskuls[0].id
    }
    const availableKelas = filteredKelasForBatch.value
    if (availableKelas.length > 0 && !availableKelas.some(k => k.id === batchForm.kelas_id)) {
        batchForm.kelas_id = availableKelas[0].id
    }
    loadSiswaByKelas()
}

function toggleSelectAllSiswa() {
    const availableIds = filteredSiswaKelas.value.filter(s => !s.is_already_member).map(s => s.id)
    if (isAllSiswaSelected.value) {
        batchForm.siswa_ids = batchForm.siswa_ids.filter(id => !availableIds.includes(id))
    } else {
        const newIds = new Set([...batchForm.siswa_ids, ...availableIds])
        batchForm.siswa_ids = Array.from(newIds)
    }
}

function openBatchAnggotaModal() {
    batchForm.reset()
    batchForm.clearErrors()
    batchForm.tenant_id = tenantId.value || (props.tenants[0]?.id || '')
    batchForm.tahun_ajaran_id = selectedTahunAjaranId.value || (props.allTahunAjaran[0]?.id || '')
    batchForm.semester = selectedSemester.value || 'Ganjil'
    
    const availableEkskuls = filteredEkskulForBatch.value
    batchForm.ekskul_id = filterEkskulId.value || (availableEkskuls[0]?.id || '')
    
    const availableKelas = filteredKelasForBatch.value
    batchForm.kelas_id = availableKelas[0]?.id || ''
    
    batchForm.tanggal_bergabung = new Date().toISOString().split('T')[0]
    searchSiswaKelas.value = ''
    loadSiswaByKelas()
    showBatchAnggotaModal.value = true
}

function submitBatchAnggota() {
    if (batchForm.siswa_ids.length === 0) {
        alert('Silakan pilih minimal 1 siswa untuk didaftarkan.')
        return
    }
    batchForm.post('/kesiswaan/ekskul/anggota/batch', {
        preserveScroll: true,
        onSuccess: () => {
            showBatchAnggotaModal.value = false
            batchForm.reset()
        }
    })
}

// ── COPY ANGGOTA DARI SEMESTER / PERIODE LALU ──
const showCopyAnggotaModal = ref(false)
const copyForm = useForm({
    ekskul_id: '',
    from_tahun_ajaran_id: '',
    from_semester: 'Ganjil',
    to_tahun_ajaran_id: '',
    to_semester: 'Ganjil',
    only_active: true,
    tenant_id: '',
})

function openCopyAnggotaModal() {
    copyForm.reset()
    copyForm.clearErrors()
    copyForm.ekskul_id = filterEkskulId.value || (props.allEkskul[0]?.id || '')
    copyForm.to_tahun_ajaran_id = selectedTahunAjaranId.value || (props.allTahunAjaran[0]?.id || '')
    copyForm.to_semester = selectedSemester.value || 'Ganjil'
    copyForm.from_semester = selectedSemester.value === 'Genap' ? 'Ganjil' : 'Genap'
    copyForm.from_tahun_ajaran_id = props.allTahunAjaran[1]?.id || props.allTahunAjaran[0]?.id || ''
    copyForm.only_active = true
    copyForm.tenant_id = tenantId.value || (props.tenants[0]?.id || '')
    showCopyAnggotaModal.value = true
}

function submitCopyAnggota() {
    copyForm.post('/kesiswaan/ekskul/anggota/copy', {
        preserveScroll: true,
        onSuccess: () => {
            showCopyAnggotaModal.value = false
            copyForm.reset()
        }
    })
}

// ─────────────────────────────────────────────
// MODAL STATE: 3. PEMBINA EKSKUL
// ─────────────────────────────────────────────
const showPembinaModal = ref(false)
const isEditPembina = ref(false)
const showPembinaPassword = ref(false)
const pembinaForm = useForm({
    id: '',
    tenant_id: '',
    nama_pembina: '',
    nip: '',
    jenis_kelamin: 'L',
    no_hp: '',
    email: '',
    kategori_pembina: 'Guru Internal',
    username: '',
    password: '',
})

const kategoriPembinaOptions = ['Guru Internal', 'Pelatih Luar / Profesional', 'Alumni', 'Instruktur Khusus']

function openCreatePembina() {
    isEditPembina.value = false
    showPembinaPassword.value = false
    pembinaForm.reset()
    pembinaForm.clearErrors()
    pembinaForm.jenis_kelamin = 'L'
    pembinaForm.kategori_pembina = 'Guru Internal'
    pembinaForm.tenant_id = tenantId.value || (props.tenants.length > 0 ? props.tenants[0].id : '')
    showPembinaModal.value = true
}

function openEditPembina(item) {
    isEditPembina.value = true
    showPembinaPassword.value = false
    pembinaForm.clearErrors()
    pembinaForm.id = item.id
    pembinaForm.tenant_id = item.tenant_id || ''
    pembinaForm.nama_pembina = item.nama_pembina || ''
    pembinaForm.nip = item.nip || ''
    pembinaForm.jenis_kelamin = item.jenis_kelamin || 'L'
    pembinaForm.no_hp = item.no_hp || ''
    pembinaForm.email = item.email || ''
    pembinaForm.kategori_pembina = item.kategori_pembina || 'Guru Internal'
    pembinaForm.username = item.guru?.username || ''
    pembinaForm.password = ''
    showPembinaModal.value = true
}

function submitPembina() {
    if (isEditPembina.value) {
        pembinaForm.put(`/kesiswaan/ekskul/pembina/${pembinaForm.id}`, {
            preserveScroll: true,
            onSuccess: () => { showPembinaModal.value = false; pembinaForm.reset() }
        })
    } else {
        pembinaForm.post('/kesiswaan/ekskul/pembina', {
            preserveScroll: true,
            onSuccess: () => { showPembinaModal.value = false; pembinaForm.reset() }
        })
    }
}

// ─────────────────────────────────────────────
// MODAL STATE: 4. JURNAL KEGIATAN
// ─────────────────────────────────────────────
const showJurnalModal = ref(false)
const isEditJurnal = ref(false)
const selectedJurnalFile = ref(null)
const jurnalPreviewUrl = ref(null)
const existingJurnalFile = ref(null)
const removeExistingJurnalFile = ref(false)

const jurnalForm = useForm({
    id: '',
    tenant_id: '',
    ekskul_id: '',
    pembina_id: '',
    tanggal_kegiatan: new Date().toISOString().split('T')[0],
    jam_mulai: '15:00',
    jam_selesai: '17:00',
    materi_kegiatan: '',
    lokasi: 'Area Latihan Sekolah',
    jumlah_hadir: 0,
    jumlah_absen: 0,
    catatan_evaluasi: '',
    foto: null,
    delete_foto: '',
})

function openCreateJurnal() {
    isEditJurnal.value = false
    selectedJurnalFile.value = null
    jurnalPreviewUrl.value = null
    existingJurnalFile.value = null
    removeExistingJurnalFile.value = false
    jurnalForm.reset()
    jurnalForm.clearErrors()
    jurnalForm.tanggal_kegiatan = new Date().toISOString().split('T')[0]
    jurnalForm.jam_mulai = '15:00'
    jurnalForm.jam_selesai = '17:00'
    jurnalForm.lokasi = 'Area Latihan Sekolah'
    jurnalForm.tenant_id = tenantId.value || (props.tenants.length > 0 ? props.tenants[0].id : '')
    if (props.allEkskul.length > 0) jurnalForm.ekskul_id = props.allEkskul[0].id
    if (props.allPembina.length > 0) jurnalForm.pembina_id = props.allPembina[0].id
    showJurnalModal.value = true
}

function openEditJurnal(item) {
    isEditJurnal.value = true
    selectedJurnalFile.value = null
    jurnalPreviewUrl.value = null
    existingJurnalFile.value = item.foto_kegiatan || null
    removeExistingJurnalFile.value = false
    jurnalForm.clearErrors()
    jurnalForm.id = item.id
    jurnalForm.tenant_id = item.tenant_id || ''
    jurnalForm.ekskul_id = item.ekskul_id || ''
    jurnalForm.pembina_id = item.pembina_id || ''
    jurnalForm.tanggal_kegiatan = item.tanggal_kegiatan || ''
    jurnalForm.jam_mulai = item.jam_mulai || '15:00'
    jurnalForm.jam_selesai = item.jam_selesai || '17:00'
    jurnalForm.materi_kegiatan = item.materi_kegiatan || ''
    jurnalForm.lokasi = item.lokasi || ''
    jurnalForm.jumlah_hadir = item.jumlah_hadir || 0
    jurnalForm.jumlah_absen = item.jumlah_absen || 0
    jurnalForm.catatan_evaluasi = item.catatan_evaluasi || ''
    jurnalForm.foto = null
    jurnalForm.delete_foto = ''
    showJurnalModal.value = true
}

function handleJurnalFileChange(e) {
    const file = e.target.files[0]
    if (!file) return
    selectedJurnalFile.value = file
    jurnalForm.foto = file
    removeExistingJurnalFile.value = false
    jurnalForm.delete_foto = ''
    if (jurnalPreviewUrl.value) URL.revokeObjectURL(jurnalPreviewUrl.value)
    jurnalPreviewUrl.value = URL.createObjectURL(file)
}

function triggerRemoveJurnalFile() {
    selectedJurnalFile.value = null
    jurnalForm.foto = null
    if (jurnalPreviewUrl.value) URL.revokeObjectURL(jurnalPreviewUrl.value)
    jurnalPreviewUrl.value = null
    if (existingJurnalFile.value) {
        removeExistingJurnalFile.value = true
        jurnalForm.delete_foto = '1'
    }
}

function submitJurnal() {
    if (isEditJurnal.value) {
        jurnalForm.post(`/kesiswaan/ekskul/jurnal/${jurnalForm.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => { showJurnalModal.value = false; jurnalForm.reset() }
        })
    } else {
        jurnalForm.post('/kesiswaan/ekskul/jurnal', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => { showJurnalModal.value = false; jurnalForm.reset() }
        })
    }
}

// ─────────────────────────────────────────────
// MODAL STATE: 5. PENILAIAN EKSKUL
// ─────────────────────────────────────────────
const showNilaiModal = ref(false)
const nilaiForm = useForm({
    id: '',
    tenant_id: '',
    ekskul_id: '',
    siswa_id: '',
    semester: 'Ganjil',
    predikat: 'A',
    nilai_angka: 90,
    keterangan: 'Sangat aktif dan berdedikasi tinggi dalam seluruh agenda latihan.',
})

const predikatOptions = [
    { val: 'A', label: 'A (Sangat Baik / Istimewa)' },
    { val: 'B', label: 'B (Baik / Terpuji)' },
    { val: 'C', label: 'C (Cukup)' },
    { val: 'D', label: 'D (Kurang)' },
]

function openCreateNilai() {
    nilaiForm.reset()
    nilaiForm.clearErrors()
    nilaiForm.semester = 'Ganjil'
    nilaiForm.predikat = 'A'
    nilaiForm.nilai_angka = 90
    nilaiForm.keterangan = 'Sangat aktif dan berdedikasi tinggi.'
    nilaiForm.tenant_id = tenantId.value || (props.tenants.length > 0 ? props.tenants[0].id : '')
    if (props.allEkskul.length > 0) nilaiForm.ekskul_id = props.allEkskul[0].id
    if (props.allSiswa.length > 0) nilaiForm.siswa_id = props.allSiswa[0].id
    showNilaiModal.value = true
}

function submitNilai() {
    nilaiForm.post('/kesiswaan/ekskul/nilai', {
        preserveScroll: true,
        onSuccess: () => { showNilaiModal.value = false; nilaiForm.reset() }
    })
}

// ─────────────────────────────────────────────
// MODAL STATE: 6. PRESTASI SISWA
// ─────────────────────────────────────────────
const showPrestasiModal = ref(false)
const isEditPrestasi = ref(false)
const selectedPrestasiFile = ref(null)
const prestasiPreviewUrl = ref(null)
const existingPrestasiFile = ref(null)
const removeExistingPrestasiFile = ref(false)

const prestasiForm = useForm({
    id: '',
    tenant_id: '',
    nama_lomba: '',
    bidang_lomba: 'Olahraga',
    juara: 'Juara 1',
    tingkat_kejuaraan: 'Tingkat Kota/Kabupaten',
    penyelenggara: 'Dinas Pendidikan & Pemuda Olahraga',
    tempat_lomba: 'GOR Utama',
    tanggal_lomba: new Date().toISOString().split('T')[0],
    guru_pendamping: '',
    poin_prestasi: 50,
    nomor_sertifikat: '',
    deskripsi: '',
    siswa_ids: [],
    foto_bukti: null,
    delete_foto: '',
})

const bidangPrestasiOptions = ['Olahraga', 'Seni Musik & Tari', 'Sains & Olimpiade', 'Robotik & IT', 'Debat & Bahasa', 'Keagamaan / MTQ', 'Pramuka & Kepanduan', 'Lainnya']
const juaraOptions = ['Juara 1 (Emas)', 'Juara 2 (Perak)', 'Juara 3 (Perunggu)', 'Juara Harapan 1', 'Juara Harapan 2', 'Best Speaker', 'Best Performance', 'Finalis']
const tingkatOptions = ['Tingkat Sekolah', 'Tingkat Kecamatan', 'Tingkat Kota/Kabupaten', 'Tingkat Provinsi', 'Tingkat Nasional', 'Tingkat Internasional']

function openCreatePrestasi() {
    isEditPrestasi.value = false
    selectedPrestasiFile.value = null
    prestasiPreviewUrl.value = null
    existingPrestasiFile.value = null
    removeExistingPrestasiFile.value = false
    prestasiForm.reset()
    prestasiForm.clearErrors()
    prestasiForm.bidang_lomba = 'Olahraga'
    prestasiForm.juara = 'Juara 1 (Emas)'
    prestasiForm.tingkat_kejuaraan = 'Tingkat Kota/Kabupaten'
    prestasiForm.penyelenggara = 'Dinas Pendidikan & Kebudayaan'
    prestasiForm.tanggal_lomba = new Date().toISOString().split('T')[0]
    prestasiForm.poin_prestasi = 50
    prestasiForm.siswa_ids = props.allSiswa.length > 0 ? [props.allSiswa[0].id] : []
    prestasiForm.tenant_id = tenantId.value || (props.tenants.length > 0 ? props.tenants[0].id : '')
    showPrestasiModal.value = true
}

function openEditPrestasi(item) {
    isEditPrestasi.value = true
    selectedPrestasiFile.value = null
    prestasiPreviewUrl.value = null
    existingPrestasiFile.value = item.foto_bukti_prestasi || null
    removeExistingPrestasiFile.value = false
    prestasiForm.clearErrors()
    prestasiForm.id = item.id
    prestasiForm.tenant_id = item.tenant_id || ''
    prestasiForm.nama_lomba = item.nama_lomba || ''
    prestasiForm.bidang_lomba = item.bidang_lomba || 'Olahraga'
    prestasiForm.juara = item.juara || 'Juara 1'
    prestasiForm.tingkat_kejuaraan = item.tingkat_kejuaraan || 'Tingkat Kota/Kabupaten'
    prestasiForm.penyelenggara = item.penyelenggara || ''
    prestasiForm.tempat_lomba = item.tempat_lomba || ''
    prestasiForm.tanggal_lomba = item.tanggal_lomba || ''
    prestasiForm.guru_pendamping = item.guru_pendamping || ''
    prestasiForm.poin_prestasi = item.poin_prestasi || 50
    prestasiForm.nomor_sertifikat = item.nomor_sertifikat || ''
    prestasiForm.deskripsi = item.deskripsi || ''
    prestasiForm.foto_bukti = null
    prestasiForm.delete_foto = ''
    showPrestasiModal.value = true
}

function handlePrestasiFileChange(e) {
    const file = e.target.files[0]
    if (!file) return
    selectedPrestasiFile.value = file
    prestasiForm.foto_bukti = file
    removeExistingPrestasiFile.value = false
    prestasiForm.delete_foto = ''
    if (prestasiPreviewUrl.value) URL.revokeObjectURL(prestasiPreviewUrl.value)
    prestasiPreviewUrl.value = URL.createObjectURL(file)
}

function triggerRemovePrestasiFile() {
    selectedPrestasiFile.value = null
    prestasiForm.foto_bukti = null
    if (prestasiPreviewUrl.value) URL.revokeObjectURL(prestasiPreviewUrl.value)
    prestasiPreviewUrl.value = null
    if (existingPrestasiFile.value) {
        removeExistingPrestasiFile.value = true
        prestasiForm.delete_foto = '1'
    }
}

function submitPrestasi() {
    if (isEditPrestasi.value) {
        prestasiForm.post(`/kesiswaan/ekskul/prestasi/${prestasiForm.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => { showPrestasiModal.value = false; prestasiForm.reset() }
        })
    } else {
        prestasiForm.post('/kesiswaan/ekskul/prestasi', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => { showPrestasiModal.value = false; prestasiForm.reset() }
        })
    }
}

// ─────────────────────────────────────────────
// GENERIC DELETE CONFIRMATION MODAL
// ─────────────────────────────────────────────
const showDeleteModal = ref(false)
const deleteTarget = ref({ type: '', id: '', name: '', endpoint: '' })

function confirmDelete(type, id, name, endpoint) {
    deleteTarget.value = { type, id, name, endpoint }
    showDeleteModal.value = true
}

function executeDelete() {
    if (!deleteTarget.value.endpoint) return
    router.delete(deleteTarget.value.endpoint, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            deleteTarget.value = { type: '', id: '', name: '', endpoint: '' }
        }
    })
}

// ─────────────────────────────────────────────
// PREVIEW IMAGE / MEDIA MODAL
// ─────────────────────────────────────────────
const showImageModal = ref(false)
const previewImageUrl = ref('')
const previewImageTitle = ref('')

function openImagePreview(url, title) {
    if (!url) return
    previewImageUrl.value = url
    previewImageTitle.value = title || 'Dokumentasi Berkas'
    showImageModal.value = true
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
        year: 'numeric'
    })
}

function getPredikatBadge(p) {
    switch (p) {
        case 'A': return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case 'B': return 'bg-blue-50 text-blue-700 border-blue-200'
        case 'C': return 'bg-amber-50 text-amber-700 border-amber-200'
        default: return 'bg-rose-50 text-rose-700 border-rose-200'
    }
}

function getJuaraBadge(j) {
    if (!j) return 'bg-slate-100 text-slate-700 border-slate-200'
    if (j.includes('1') || j.toLowerCase().includes('emas')) return 'bg-amber-50 text-amber-800 border-amber-300 font-extrabold'
    if (j.includes('2') || j.toLowerCase().includes('perak')) return 'bg-slate-100 text-slate-800 border-slate-300 font-bold'
    if (j.includes('3') || j.toLowerCase().includes('perunggu')) return 'bg-orange-50 text-orange-800 border-orange-300 font-bold'
    return 'bg-blue-50 text-blue-700 border-blue-200'
}

const getActivePagination = computed(() => {
    switch (activeTab.value) {
        case 'ekskul': return props.ekskulList
        case 'anggota': return props.anggotaList
        case 'pembina': return props.pembinaList
        case 'jurnal': return props.jurnalList
        case 'nilai': return props.nilaiList
        case 'prestasi': return props.prestasiList
        default: return props.ekskulList
    }
})

function getSmartPaginationLinks(pagination) {
    if (!pagination?.links || pagination.links.length === 0) return []
    const rawLinks = pagination.links
    const prevLink = rawLinks[0]
    const nextLink = rawLinks[rawLinks.length - 1]
    const pageLinks = rawLinks.slice(1, -1)
    const current = Number(pagination.current_page) || 1
    const last = Number(pagination.last_page) || (pageLinks.length ? Number(pageLinks[pageLinks.length - 1].label) || 1 : 1)

    const result = []
    // Tombol Previous
    result.push({
        url: prevLink ? prevLink.url : null,
        active: false,
        isPrev: true,
        isNext: false,
        label: '«'
    })

    if (last <= 7) {
        pageLinks.forEach(l => {
            result.push({
                url: l.url,
                active: !!l.active,
                isPrev: false,
                isNext: false,
                label: l.label
            })
        })
    } else {
        const pagesToShow = new Set([1, last])
        for (let p = current - 1; p <= current + 1; p++) {
            if (p >= 1 && p <= last) pagesToShow.add(p)
        }
        const sortedPages = Array.from(pagesToShow).sort((a, b) => a - b)
        let prevPage = null
        sortedPages.forEach(p => {
            if (prevPage !== null && p - prevPage > 1) {
                result.push({
                    label: '...',
                    url: null,
                    active: false,
                    isPrev: false,
                    isNext: false
                })
            }
            const foundRaw = pageLinks.find(l => String(l.label) === String(p))
            result.push({
                label: String(p),
                url: foundRaw ? foundRaw.url : null,
                active: p === current,
                isPrev: false,
                isNext: false
            })
            prevPage = p
        })
    }

    // Tombol Next
    result.push({
        url: nextLink ? nextLink.url : null,
        active: false,
        isPrev: false,
        isNext: true,
        label: '»'
    })

    return result
}
</script>

<template>
    <AppLayout title="Kesiswaan & Ekstrakurikuler">
        <div class="space-y-6">

            <!-- ── 1. HEADER HALAMAN & ACTIONS ── -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-xs">
                            <i class="bi bi-trophy text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-800">Manajemen Ekstrakurikuler & Prestasi</h1>
                            <p class="text-xs text-slate-500">
                                Kelola direktori kegiatan bakat minat, keanggotaan siswa, jurnal latihan, penilaian berkala, dan rekam portofolio prestasi.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Primary CTA Dynamic per active tab -->
                <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                    <button
                        v-if="activeTab === 'ekskul'"
                        type="button"
                        class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2"
                        @click="openCreateEkskul"
                    >
                        <i class="bi bi-plus-lg"></i> Tambah Ekskul
                    </button>
                    
                    <template v-else-if="activeTab === 'anggota'">
                        <!-- 1. Tombol Massal Per Kelas -->
                        <button
                            type="button"
                            class="px-3.5 py-2.5 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition flex items-center gap-2"
                            @click="openBatchAnggotaModal"
                            title="Input banyak siswa dari 1 kelas sekaligus"
                        >
                            <i class="bi bi-people-fill"></i> Tambah Massal (Per Kelas)
                        </button>

                        <!-- 2. Tombol Salin dari Semester Lalu -->
                        <button
                            type="button"
                            class="px-3.5 py-2.5 text-xs font-bold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-xs transition flex items-center gap-2"
                            @click="openCopyAnggotaModal"
                            title="Kloning data anggota dari semester sebelumnya"
                        >
                            <i class="bi bi-arrow-repeat"></i> Salin dari Semester Lalu
                        </button>

                        <!-- 3. Tombol Tambah Tunggal -->
                        <button
                            type="button"
                            class="px-3.5 py-2.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2"
                            @click="openCreateAnggota"
                        >
                            <i class="bi bi-person-plus"></i> Tambah Tunggal
                        </button>
                    </template>

                    <button
                        v-else-if="activeTab === 'pembina'"
                        type="button"
                        class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2"
                        @click="openCreatePembina"
                    >
                        <i class="bi bi-person-badge"></i> Tambah Pembina
                    </button>
                    <button
                        v-else-if="activeTab === 'jurnal'"
                        type="button"
                        class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2"
                        @click="openCreateJurnal"
                    >
                        <i class="bi bi-journal-plus"></i> Catat Jurnal Latihan
                    </button>
                    <button
                        v-else-if="activeTab === 'nilai'"
                        type="button"
                        class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2"
                        @click="openCreateNilai"
                    >
                        <i class="bi bi-star"></i> Input Nilai Ekskul
                    </button>
                    <button
                        v-else-if="activeTab === 'prestasi'"
                        type="button"
                        class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition flex items-center gap-2"
                        @click="openCreatePrestasi"
                    >
                        <i class="bi bi-award"></i> Catat Prestasi Siswa
                    </button>
                </div>
            </div>

            <!-- ── 2. SELEKTOR PERIODE AKADEMIK (TAHUN AJARAN & SEMESTER) ── -->
            <div class="p-4 sm:px-5 rounded-2xl shadow-2xs border border-indigo-100 bg-gradient-to-r from-indigo-50/90 via-slate-50 to-blue-50/70 border-l-4 border-l-indigo-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3.5">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                        <i class="bi bi-calendar-range"></i>
                    </div>
                    <div>
                        <div class="font-bold text-slate-800 text-sm">Periode Akademik Ekskul</div>
                        <div class="text-[11px] text-slate-500">Keanggotaan siswa, jurnal, dan rekap nilai terisolasi per semester</div>
                    </div>

                    <!-- Dropdown Tahun Ajaran -->
                    <div class="flex items-center gap-1.5 ml-0 md:ml-2">
                        <label class="text-xs font-semibold text-slate-600">Tahun Ajaran:</label>
                        <select
                            v-model="selectedTahunAjaranId"
                            @change="handlePeriodChange"
                            class="h-9 px-3 bg-white border border-indigo-200 rounded-xl text-xs font-bold text-indigo-900 shadow-2xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 min-w-[140px]"
                        >
                            <option v-for="t in allTahunAjaran" :key="t.id" :value="t.id">
                                {{ t.nama_tahun_ajaran }} {{ t.is_active ? '★' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Semester Toggle Buttons -->
                    <div class="flex items-center bg-white p-1 rounded-xl border border-indigo-200 shadow-2xs">
                        <button
                            type="button"
                            class="px-3 py-1 text-xs font-bold rounded-lg transition"
                            :class="selectedSemester === 'Ganjil' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                            @click="selectedSemester = 'Ganjil'; handlePeriodChange()"
                        >
                            Semester Ganjil
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1 text-xs font-bold rounded-lg transition"
                            :class="selectedSemester === 'Genap' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                            @click="selectedSemester = 'Genap'; handlePeriodChange()"
                        >
                            Semester Genap
                        </button>
                    </div>
                </div>

                <div class="text-xs text-indigo-900 font-medium shrink-0">
                    Menampilkan data: 
                    <strong class="text-indigo-700 font-bold ml-1">
                        {{ getSelectedTahunAjaranName() }} — Semester {{ selectedSemester }}
                    </strong>
                </div>
            </div>

            <!-- ── 3. FILTER SEKOLAH BANNER (STANDAR DESAIN SUPER ADMIN) ── -->
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
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 text-lg font-bold">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-slate-500">Total Ekskul</div>
                        <div class="text-lg font-bold text-slate-800">{{ stats.total_ekskul }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 text-lg font-bold">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-slate-500">Anggota Siswa</div>
                        <div class="text-lg font-bold text-emerald-600">{{ stats.total_anggota }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 text-lg font-bold">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-slate-500">Pembina / Pelatih</div>
                        <div class="text-lg font-bold text-indigo-600">{{ stats.total_pembina }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 text-lg font-bold">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-slate-500">Jurnal Latihan</div>
                        <div class="text-lg font-bold text-amber-600">{{ stats.total_jurnal }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex items-center gap-3 col-span-2 lg:col-span-1">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 text-lg font-bold">
                        <i class="bi bi-award"></i>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-slate-500">Prestasi Terdata</div>
                        <div class="text-lg font-bold text-purple-600">{{ stats.total_prestasi }}</div>
                    </div>
                </div>
            </div>

            <!-- ── 4. MODERN PILL NAVTABS SCROLLER (STANDAR AGENTS.MD) ── -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <!-- Tombol Panah Kiri -->
                    <button
                        type="button"
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5"
                        onclick="document.getElementById('navTabsEkskul')?.scrollBy({ left: -220, behavior: 'smooth' })"
                        title="Geser ke Kiri"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <!-- Container Deretan Tab -->
                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsEkskul" role="tablist">
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center"
                                    :class="activeTab === 'ekskul' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('ekskul')"
                                >
                                    <i class="bi bi-trophy me-2 text-sm"></i> Daftar Ekstrakurikuler
                                    <span class="ml-2 px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'ekskul' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                                        {{ ekskulList.total || 0 }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center"
                                    :class="activeTab === 'anggota' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('anggota')"
                                >
                                    <i class="bi bi-people me-2 text-sm"></i> Anggota & Peserta
                                    <span class="ml-2 px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'anggota' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                                        {{ anggotaList.total || 0 }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center"
                                    :class="activeTab === 'pembina' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('pembina')"
                                >
                                    <i class="bi bi-person-badge me-2 text-sm"></i> Data Pembina & Pelatih
                                    <span class="ml-2 px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'pembina' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                                        {{ pembinaList.total || 0 }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center"
                                    :class="activeTab === 'jurnal' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('jurnal')"
                                >
                                    <i class="bi bi-journal-check me-2 text-sm"></i> Jurnal & Dokumentasi Latihan
                                    <span class="ml-2 px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'jurnal' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                                        {{ jurnalList.total || 0 }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center"
                                    :class="activeTab === 'nilai' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('nilai')"
                                >
                                    <i class="bi bi-star me-2 text-sm"></i> Penilaian Ekskul
                                    <span class="ml-2 px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'nilai' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                                        {{ nilaiList.total || 0 }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center"
                                    :class="activeTab === 'prestasi' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    @click="switchTab('prestasi')"
                                >
                                    <i class="bi bi-award me-2 text-sm"></i> Portofolio Prestasi
                                    <span class="ml-2 px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'prestasi' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                                        {{ prestasiList.total || 0 }}
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tombol Panah Kanan -->
                    <button
                        type="button"
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5"
                        onclick="document.getElementById('navTabsEkskul')?.scrollBy({ left: 220, behavior: 'smooth' })"
                        title="Geser ke Kanan"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- ── 5. BOX UTAMA: SEARCH BAR & TABLE DATA ── -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                <!-- Search & Filters Toolbar -->
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5 grow">
                        <!-- Search input -->
                        <div class="relative min-w-[240px] grow md:grow-0">
                            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="
                                    activeTab === 'ekskul' ? 'Cari nama ekskul, kategori, tempat...' :
                                    activeTab === 'anggota' ? 'Cari nama siswa, NISN, jabatan...' :
                                    activeTab === 'pembina' ? 'Cari nama pembina, NIP, status...' :
                                    activeTab === 'jurnal' ? 'Cari materi kegiatan, lokasi...' :
                                    activeTab === 'nilai' ? 'Cari predikat, nama siswa...' :
                                    'Cari nama lomba, bidang, juara, tingkat...'
                                "
                                class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                @input="handleSearch"
                            />
                        </div>

                        <!-- ── Contextual Filter Dropdowns Per Tab ── -->
                        <!-- TAB 1: EKSKUL -->
                        <template v-if="activeTab === 'ekskul'">
                            <select
                                v-model="filterKategori"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Kategori</option>
                                <option v-for="kat in kategoriEkskulOptions" :key="kat" :value="kat">{{ kat }}</option>
                            </select>

                            <select
                                v-model="filterStatus"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Status</option>
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </template>

                        <!-- TAB 2: ANGGOTA -->
                        <template v-else-if="activeTab === 'anggota'">
                            <select
                                v-model="filterEkskulId"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[160px]"
                            >
                                <option value="">Semua Ekskul</option>
                                <option v-for="e in allEkskul" :key="e.id" :value="e.id">{{ e.nama_ekskul }}</option>
                            </select>

                            <select
                                v-model="filterJabatan"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Jabatan</option>
                                <option v-for="jab in jabatanOptions" :key="jab" :value="jab">{{ jab }}</option>
                            </select>

                            <select
                                v-model="filterStatus"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Status Keanggotaan</option>
                                <option v-for="st in statusAnggotaOptions" :key="st" :value="st">{{ st }}</option>
                            </select>
                        </template>

                        <!-- TAB 3: PEMBINA -->
                        <template v-else-if="activeTab === 'pembina'">
                            <select
                                v-model="filterKategori"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Kategori Pembina</option>
                                <option v-for="kat in kategoriPembinaOptions" :key="kat" :value="kat">{{ kat }}</option>
                            </select>

                            <select
                                v-model="filterJenisKelamin"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Gender</option>
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </template>

                        <!-- TAB 4: JURNAL -->
                        <template v-else-if="activeTab === 'jurnal'">
                            <select
                                v-model="filterEkskulId"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[160px]"
                            >
                                <option value="">Semua Ekskul</option>
                                <option v-for="e in allEkskul" :key="e.id" :value="e.id">{{ e.nama_ekskul }}</option>
                            </select>

                            <select
                                v-model="filterPembinaId"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[160px]"
                            >
                                <option value="">Semua Pembina / Instruktur</option>
                                <option v-for="p in allPembina" :key="p.id" :value="p.id">{{ p.nama_pembina }}</option>
                            </select>
                        </template>

                        <!-- TAB 5: NILAI -->
                        <template v-else-if="activeTab === 'nilai'">
                            <select
                                v-model="filterEkskulId"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[160px]"
                            >
                                <option value="">Semua Ekskul</option>
                                <option v-for="e in allEkskul" :key="e.id" :value="e.id">{{ e.nama_ekskul }}</option>
                            </select>

                            <select
                                v-model="filterSemester"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Semester</option>
                                <option value="Ganjil">Semester Ganjil</option>
                                <option value="Genap">Semester Genap</option>
                            </select>

                            <select
                                v-model="filterPredikat"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Predikat</option>
                                <option value="A">Predikat A (Sangat Baik)</option>
                                <option value="B">Predikat B (Baik)</option>
                                <option value="C">Predikat C (Cukup)</option>
                                <option value="D">Predikat D (Kurang)</option>
                            </select>
                        </template>

                        <!-- TAB 6: PRESTASI -->
                        <template v-else-if="activeTab === 'prestasi'">
                            <select
                                v-model="filterKategori"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Bidang</option>
                                <option v-for="bid in bidangPrestasiOptions" :key="bid" :value="bid">{{ bid }}</option>
                            </select>

                            <select
                                v-model="filterTingkat"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Tingkat Kejuaraan</option>
                                <option v-for="ting in tingkatOptions" :key="ting" :value="ting">{{ ting }}</option>
                            </select>

                            <select
                                v-model="filterJuara"
                                @change="applyFilters"
                                class="h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Semua Juara / Capaian</option>
                                <option v-for="j in juaraOptions" :key="j" :value="j">{{ j }}</option>
                            </select>
                        </template>

                        <!-- Reset Filter -->
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            class="px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 transition flex items-center gap-1.5"
                            @click="resetFilters"
                            title="Reset Semua Filter"
                        >
                            <i class="bi bi-x-circle text-rose-500"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- TAB 1: DAFTAR EKSTRAKURIKULER                           -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'ekskul'" class="overflow-x-auto thin-scrollbar">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] uppercase tracking-wider text-slate-500 font-bold whitespace-nowrap">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Nama Ekstrakurikuler</th>
                                <th class="py-3 px-4">Kategori</th>
                                <th class="py-3 px-4">Pembina / Pelatih</th>
                                <th class="py-3 px-4">Jadwal & Tempat</th>
                                <th class="py-3 px-4 text-center">Anggota / Kuota</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th v-if="isSuperAdmin" class="py-3 px-4">Sekolah / Asal</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in ekskulList.data" :key="item.id" class="hover:bg-slate-50/75 transition">
                                <td class="py-3 px-4 text-center font-medium text-slate-400">
                                    {{ (ekskulList.from || 1) + idx }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800 text-sm">{{ item.nama_ekskul }}</div>
                                    <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ item.deskripsi || 'Tidak ada deskripsi' }}</div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ item.kategori }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div v-if="item.pembina" class="font-medium text-slate-800">
                                        {{ item.pembina.nama_pembina }}
                                        <div v-if="item.pembina.no_hp" class="text-[11px] text-slate-400">
                                            <i class="bi bi-whatsapp text-emerald-500 me-1"></i>{{ item.pembina.no_hp }}
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400 italic text-xs">- Belum Diatur -</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-800">
                                        <i class="bi bi-calendar-event text-blue-500 me-1"></i>{{ item.hari_latihan || 'Sabtu' }} ({{ item.jam_mulai || '15:00' }} - {{ item.jam_selesai || '17:00' }})
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        <i class="bi bi-geo-alt text-rose-500 me-1"></i>{{ item.tempat_latihan || 'Area Sekolah' }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ item.anggota_count || 0 }} / {{ item.kuota_maksimal || 50 }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span v-if="item.is_active" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="bi bi-check-circle-fill me-1"></i> Aktif
                                    </span>
                                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Non-Aktif
                                    </span>
                                </td>
                                <td v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-slate-700">{{ item.tenant?.nama_sekolah || '-' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition shadow-2xs"
                                            title="Edit Ekstrakurikuler"
                                            @click="openEditEkskul(item)"
                                        >
                                            <i class="bi bi-pencil-square text-xs"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition shadow-2xs"
                                            title="Hapus Ekstrakurikuler"
                                            @click="confirmDelete('Ekskul', item.id, item.nama_ekskul, `/kesiswaan/ekskul/${item.id}`)"
                                        >
                                            <i class="bi bi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!ekskulList.data || ekskulList.data.length === 0">
                                <td :colspan="isSuperAdmin ? 9 : 8" class="py-12 text-center text-slate-400">
                                    <i class="bi bi-trophy text-4xl block mb-2 text-slate-300"></i>
                                    <div class="text-sm font-semibold">Belum Ada Data Ekstrakurikuler</div>
                                    <p class="text-xs text-slate-400 mt-1">Silakan tambahkan kegiatan ekskul baru melalui tombol Tambah di atas.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- TAB 2: ANGGOTA & PESERTA                                -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'anggota'" class="overflow-x-auto thin-scrollbar">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] uppercase tracking-wider text-slate-500 font-bold whitespace-nowrap">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4">Ekstrakurikuler</th>
                                <th class="py-3 px-4">Jabatan</th>
                                <th class="py-3 px-4">Tgl Bergabung</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th v-if="isSuperAdmin" class="py-3 px-4">Sekolah / Asal</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in anggotaList.data" :key="item.id" class="hover:bg-slate-50/75 transition">
                                <td class="py-3 px-4 text-center font-medium text-slate-400">
                                    {{ (anggotaList.from || 1) + idx }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 text-sm">{{ item.siswa?.nama_lengkap || '-' }}</div>
                                    <div class="text-[11px] text-slate-500">NISN: {{ item.siswa?.nisn || '-' }} | NIS: {{ item.siswa?.nis || '-' }}</div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ item.ekskul?.nama_ekskul || '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        {{ item.jabatan || 'Anggota' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-600 whitespace-nowrap">
                                    {{ formatDate(item.tanggal_bergabung) }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span v-if="item.status_keanggotaan === 'Aktif'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ item.status_keanggotaan || 'Non-Aktif' }}
                                    </span>
                                </td>
                                <td v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-slate-700">{{ item.tenant?.nama_sekolah || '-' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition shadow-2xs"
                                            title="Edit Anggota"
                                            @click="openEditAnggota(item)"
                                        >
                                            <i class="bi bi-pencil-square text-xs"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition shadow-2xs"
                                            title="Hapus Anggota"
                                            @click="confirmDelete('Anggota', item.id, item.siswa?.nama_lengkap || 'Anggota Ekskul', `/kesiswaan/ekskul/anggota/${item.id}`)"
                                        >
                                            <i class="bi bi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!anggotaList.data || anggotaList.data.length === 0">
                                <td :colspan="isSuperAdmin ? 8 : 7" class="py-12 text-center text-slate-400">
                                    <i class="bi bi-people text-4xl block mb-2 text-slate-300"></i>
                                    <div class="text-sm font-semibold">Belum Ada Anggota Terdaftar</div>
                                    <p class="text-xs text-slate-400 mt-1">Gunakan tombol Tambah Anggota untuk mendaftarkan siswa ke dalam ekskul.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- TAB 3: DATA PEMBINA & PELATIH                           -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'pembina'" class="overflow-x-auto thin-scrollbar">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] uppercase tracking-wider text-slate-500 font-bold whitespace-nowrap">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Nama Pembina / Pelatih</th>
                                <th class="py-3 px-4">Kategori & NIP</th>
                                <th class="py-3 px-4">Kontak & Email</th>
                                <th class="py-3 px-4">Membina Ekskul</th>
                                <th v-if="isSuperAdmin" class="py-3 px-4">Sekolah / Asal</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in pembinaList.data" :key="item.id" class="hover:bg-slate-50/75 transition">
                                <td class="py-3 px-4 text-center font-medium text-slate-400">
                                    {{ (pembinaList.from || 1) + idx }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 text-sm">{{ item.nama_pembina }}</div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[11px] text-slate-400">Gender: {{ item.jenis_kelamin === 'L' ? 'Laki-Laki' : 'Perempuan' }}</span>
                                        <span v-if="item.guru?.username" class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            <i class="bi bi-person-check-fill me-1 text-[9px]"></i>@{{ item.guru.username }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ item.kategori_pembina || 'Guru Internal' }}
                                    </span>
                                    <div class="text-[11px] text-slate-500 mt-0.5">NIP: {{ item.nip || '-' }}</div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-700">
                                        <i class="bi bi-telephone text-blue-500 me-1"></i>{{ item.no_hp || '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        <i class="bi bi-envelope text-slate-400 me-1"></i>{{ item.email || '-' }}
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div v-if="item.ekskul && item.ekskul.length > 0" class="flex flex-wrap gap-1">
                                        <span v-for="e in item.ekskul" :key="e.id" class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ e.nama_ekskul }}
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-400 italic text-xs">-</span>
                                </td>
                                <td v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-slate-700">{{ item.tenant?.nama_sekolah || '-' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition shadow-2xs"
                                            title="Edit Pembina"
                                            @click="openEditPembina(item)"
                                        >
                                            <i class="bi bi-pencil-square text-xs"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition shadow-2xs"
                                            title="Hapus Pembina"
                                            @click="confirmDelete('Pembina', item.id, item.nama_pembina, `/kesiswaan/ekskul/pembina/${item.id}`)"
                                        >
                                            <i class="bi bi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!pembinaList.data || pembinaList.data.length === 0">
                                <td :colspan="isSuperAdmin ? 7 : 6" class="py-12 text-center text-slate-400">
                                    <i class="bi bi-person-badge text-4xl block mb-2 text-slate-300"></i>
                                    <div class="text-sm font-semibold">Belum Ada Data Pembina / Pelatih</div>
                                    <p class="text-xs text-slate-400 mt-1">Tambahkan data pembina atau pelatih ekstrakurikuler melalui tombol di atas.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- TAB 4: JURNAL & DOKUMENTASI LATIHAN                     -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'jurnal'" class="overflow-x-auto thin-scrollbar">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] uppercase tracking-wider text-slate-500 font-bold whitespace-nowrap">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Tanggal & Waktu</th>
                                <th class="py-3 px-4">Ekstrakurikuler</th>
                                <th class="py-3 px-4">Materi & Lokasi</th>
                                <th class="py-3 px-4 text-center">Kehadiran</th>
                                <th class="py-3 px-4 text-center">Dokumentasi</th>
                                <th v-if="isSuperAdmin" class="py-3 px-4">Sekolah / Asal</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in jurnalList.data" :key="item.id" class="hover:bg-slate-50/75 transition">
                                <td class="py-3 px-4 text-center font-medium text-slate-400">
                                    {{ (jurnalList.from || 1) + idx }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-800">{{ formatDate(item.tanggal_kegiatan) }}</div>
                                    <div class="text-[11px] text-slate-500">{{ item.jam_mulai || '15:00' }} - {{ item.jam_selesai || '17:00' }}</div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ item.ekskul?.nama_ekskul || '-' }}
                                    </span>
                                    <div v-if="item.pembina" class="text-[11px] text-slate-500 mt-0.5">
                                        Pembina: {{ item.pembina.nama_pembina }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <div class="font-semibold text-slate-800 line-clamp-2">{{ item.materi_kegiatan }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        <i class="bi bi-geo-alt text-rose-500 me-1"></i>{{ item.lokasi || 'Area Sekolah' }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 font-bold">
                                        <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Hadir: {{ item.jumlah_hadir || 0 }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[10px] bg-rose-50 text-rose-700 border border-rose-200">
                                            Absen: {{ item.jumlah_absen || 0 }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <button
                                        v-if="item.foto_kegiatan"
                                        type="button"
                                        class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200 hover:bg-indigo-100 transition inline-flex items-center gap-1"
                                        @click="openImagePreview(item.foto_kegiatan, 'Dokumentasi Jurnal Latihan')"
                                    >
                                        <i class="bi bi-image"></i> Lihat Foto
                                    </button>
                                    <span v-else class="text-slate-400 italic text-xs">-</span>
                                </td>
                                <td v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-slate-700">{{ item.tenant?.nama_sekolah || '-' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition shadow-2xs"
                                            title="Edit Jurnal"
                                            @click="openEditJurnal(item)"
                                        >
                                            <i class="bi bi-pencil-square text-xs"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition shadow-2xs"
                                            title="Hapus Jurnal"
                                            @click="confirmDelete('Jurnal', item.id, 'Jurnal ' + formatDate(item.tanggal_kegiatan), `/kesiswaan/ekskul/jurnal/${item.id}`)"
                                        >
                                            <i class="bi bi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!jurnalList.data || jurnalList.data.length === 0">
                                <td :colspan="isSuperAdmin ? 8 : 7" class="py-12 text-center text-slate-400">
                                    <i class="bi bi-journal-x text-4xl block mb-2 text-slate-300"></i>
                                    <div class="text-sm font-semibold">Belum Ada Jurnal Kegiatan</div>
                                    <p class="text-xs text-slate-400 mt-1">Catat agenda dan materi latihan ekstrakurikuler melalui tombol di atas.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- TAB 5: PENILAIAN EKSKUL                                 -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'nilai'" class="overflow-x-auto thin-scrollbar">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] uppercase tracking-wider text-slate-500 font-bold whitespace-nowrap">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4">Ekstrakurikuler</th>
                                <th class="py-3 px-4">Semester</th>
                                <th class="py-3 px-4 text-center">Predikat</th>
                                <th class="py-3 px-4 text-center">Nilai Angka</th>
                                <th class="py-3 px-4">Keterangan / Catatan</th>
                                <th v-if="isSuperAdmin" class="py-3 px-4">Sekolah / Asal</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in nilaiList.data" :key="item.id" class="hover:bg-slate-50/75 transition">
                                <td class="py-3 px-4 text-center font-medium text-slate-400">
                                    {{ (nilaiList.from || 1) + idx }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 text-sm">{{ item.siswa?.nama_lengkap || '-' }}</div>
                                    <div class="text-[11px] text-slate-500">NISN: {{ item.siswa?.nisn || '-' }}</div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ item.ekskul?.nama_ekskul || '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-700 whitespace-nowrap">
                                    Semester {{ item.semester || 'Ganjil' }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold border" :class="getPredikatBadge(item.predikat)">
                                        {{ item.predikat }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-slate-800 whitespace-nowrap">
                                    {{ item.nilai_angka || '-' }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 max-w-xs">
                                    {{ item.keterangan || '-' }}
                                </td>
                                <td v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-slate-700">{{ item.tenant?.nama_sekolah || '-' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition shadow-2xs"
                                        title="Hapus Nilai"
                                        @click="confirmDelete('Nilai', item.id, 'Nilai Siswa', `/kesiswaan/ekskul/nilai/${item.id}`)"
                                    >
                                        <i class="bi bi-trash text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!nilaiList.data || nilaiList.data.length === 0">
                                <td :colspan="isSuperAdmin ? 9 : 8" class="py-12 text-center text-slate-400">
                                    <i class="bi bi-star text-4xl block mb-2 text-slate-300"></i>
                                    <div class="text-sm font-semibold">Belum Ada Rekap Nilai Ekskul</div>
                                    <p class="text-xs text-slate-400 mt-1">Input nilai kompetensi siswa pada semester aktif melalui tombol di atas.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- TAB 6: PORTOFOLIO PRESTASI SISWA                        -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'prestasi'" class="overflow-x-auto thin-scrollbar">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] uppercase tracking-wider text-slate-500 font-bold whitespace-nowrap">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Nama Kejuaraan / Lomba</th>
                                <th class="py-3 px-4">Bidang & Tingkat</th>
                                <th class="py-3 px-4 text-center">Capaian Juara</th>
                                <th class="py-3 px-4">Siswa Berprestasi</th>
                                <th class="py-3 px-4">Penyelenggara & Waktu</th>
                                <th class="py-3 px-4 text-center">Bukti / Sertifikat</th>
                                <th v-if="isSuperAdmin" class="py-3 px-4">Sekolah / Asal</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in prestasiList.data" :key="item.id" class="hover:bg-slate-50/75 transition">
                                <td class="py-3 px-4 text-center font-medium text-slate-400">
                                    {{ (prestasiList.from || 1) + idx }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 text-sm">{{ item.nama_lomba }}</div>
                                    <div class="text-[11px] text-slate-500">Poin: +{{ item.poin_prestasi || 50 }} | No: {{ item.nomor_sertifikat || '-' }}</div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ item.bidang_lomba }}
                                    </span>
                                    <div class="text-[11px] font-medium text-slate-600 mt-0.5">{{ item.tingkat_kejuaraan }}</div>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs border" :class="getJuaraBadge(item.juara)">
                                        <i class="bi bi-award-fill me-1"></i> {{ item.juara }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div v-if="item.anggota && item.anggota.length > 0" class="flex flex-col gap-1">
                                        <div v-for="a in item.anggota" :key="a.id" class="font-semibold text-slate-800">
                                            <i class="bi bi-person-check text-emerald-600 me-1"></i>{{ a.siswa?.nama_lengkap || '-' }}
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400 italic text-xs">-</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-800">{{ item.penyelenggara || '-' }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        <i class="bi bi-calendar-check text-blue-500 me-1"></i>{{ formatDate(item.tanggal_lomba) }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <button
                                        v-if="item.foto_bukti_prestasi"
                                        type="button"
                                        class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-purple-50 text-purple-600 border border-purple-200 hover:bg-purple-100 transition inline-flex items-center gap-1"
                                        @click="openImagePreview(item.foto_bukti_prestasi, 'Sertifikat & Bukti Prestasi: ' + item.nama_lomba)"
                                    >
                                        <i class="bi bi-file-earmark-image"></i> Sertifikat
                                    </button>
                                    <span v-else class="text-slate-400 italic text-xs">-</span>
                                </td>
                                <td v-if="isSuperAdmin" class="py-3 px-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-slate-700">{{ item.tenant?.nama_sekolah || '-' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition shadow-2xs"
                                            title="Edit Prestasi"
                                            @click="openEditPrestasi(item)"
                                        >
                                            <i class="bi bi-pencil-square text-xs"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition shadow-2xs"
                                            title="Hapus Prestasi"
                                            @click="confirmDelete('Prestasi', item.id, item.nama_lomba, `/kesiswaan/ekskul/prestasi/${item.id}`)"
                                        >
                                            <i class="bi bi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!prestasiList.data || prestasiList.data.length === 0">
                                <td :colspan="isSuperAdmin ? 9 : 8" class="py-12 text-center text-slate-400">
                                    <i class="bi bi-award text-4xl block mb-2 text-slate-300"></i>
                                    <div class="text-sm font-semibold">Belum Ada Rekam Prestasi Siswa</div>
                                    <p class="text-xs text-slate-400 mt-1">Tambahkan data penghargaan lomba dan kejuaraan siswa melalui tombol di atas.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- FOOTER PAGINATION BAR (STANDAR AGENTS.MD)                -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div v-if="getActivePagination?.total > 0" 
                     class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/70 border-t border-slate-200/80">
                    <!-- Info Tampilkan Baris -->
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
                        <span>Tampilkan</span>
                        <select v-model="perPage" @change="applyFilters" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                        <span class="whitespace-nowrap">baris per halaman</span>
                        <span class="text-slate-300 hidden sm:inline">|</span>
                        <span class="whitespace-nowrap">
                            Menampilkan <span class="font-bold text-slate-800">{{ getActivePagination.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ getActivePagination.to || getActivePagination.total }}</span> dari <span class="font-bold text-slate-800">{{ getActivePagination.total }}</span> baris
                        </span>
                    </div>

                    <!-- Pagination Links (Single Clean Horizontal Line) -->
                    <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
                        <template v-for="(link, i) in getSmartPaginationLinks(getActivePagination)" :key="i">
                            <button v-if="link.url && !link.active" 
                                    type="button"
                                    @click="changePage(link.url)"
                                    class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs"
                                    :title="link.isPrev ? 'Halaman Sebelumnya' : (link.isNext ? 'Halaman Berikutnya' : 'Halaman ' + link.label)">
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                                <span v-else>{{ link.label }}</span>
                            </button>
                            <span v-else-if="link.active"
                                  class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center bg-blue-600 text-white shadow-xs">
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs"></i>
                                <span v-else>{{ link.label }}</span>
                            </span>
                            <span v-else 
                                  class="min-w-[32px] h-8 px-2 text-xs font-bold flex items-center justify-center text-slate-400">
                                <i v-if="link.isPrev" class="bi bi-chevron-left text-xs text-slate-300"></i>
                                <i v-else-if="link.isNext" class="bi bi-chevron-right text-xs text-slate-300"></i>
                                <span v-else>{{ link.label }}</span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- POPUP MODALS WITH TELEPORT (MANDATORY AGENTS.MD RULE)            -->
        <!-- ════════════════════════════════════════════════════════════════ -->

        <!-- ── MODAL 1: TAMBAH / EDIT MASTER EKSKUL ── -->
        <Teleport to="body">
            <div v-if="showEkskulModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center">
                <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-auto max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                <i class="bi bi-trophy"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base">
                                {{ isEditEkskul ? 'Edit Data Ekstrakurikuler' : 'Tambah Ekstrakurikuler Baru' }}
                            </h3>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center" @click="showEkskulModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitEkskul" class="flex flex-col grow overflow-hidden">
                        <div class="p-6 space-y-4 text-xs overflow-y-auto thin-scrollbar grow">
                            <!-- Tenant Selector for Super Admin -->
                            <div v-if="isSuperAdmin" class="space-y-1">
                                <label class="font-bold text-slate-700">Sekolah Asal <span class="text-rose-500">*</span></label>
                                <select v-model="ekskulForm.tenant_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1 md:col-span-2">
                                    <label class="font-bold text-slate-700">Nama Ekstrakurikuler <span class="text-rose-500">*</span></label>
                                    <input v-model="ekskulForm.nama_ekskul" type="text" placeholder="Contoh: Basket, Paskibra, Robotic Club..." required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                                </div>

                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Kategori <span class="text-rose-500">*</span></label>
                                    <select v-model="ekskulForm.kategori" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option v-for="kat in kategoriEkskulOptions" :key="kat" :value="kat">{{ kat }}</option>
                                    </select>
                                </div>

                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Pembina / Pelatih</label>
                                    <select v-model="ekskulForm.pembina_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="">-- Pilih Pembina --</option>
                                        <option v-for="p in allPembina" :key="p.id" :value="p.id">{{ p.nama_pembina }}</option>
                                    </select>
                                </div>

                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Hari Latihan</label>
                                    <select v-model="ekskulForm.hari_latihan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option v-for="h in hariOptions" :key="h" :value="h">{{ h }}</option>
                                    </select>
                                </div>

                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Jam Latihan</label>
                                    <div class="flex items-center gap-2">
                                        <input v-model="ekskulForm.jam_mulai" type="text" placeholder="15:00" class="w-full px-3 py-2 rounded-xl border border-slate-200" />
                                        <span>-</span>
                                        <input v-model="ekskulForm.jam_selesai" type="text" placeholder="17:00" class="w-full px-3 py-2 rounded-xl border border-slate-200" />
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Tempat Latihan</label>
                                    <input v-model="ekskulForm.tempat_latihan" type="text" placeholder="Contoh: Lapangan Basket, Lab Komputer 2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>

                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Kuota Maksimal Anggota</label>
                                    <input v-model.number="ekskulForm.kuota_maksimal" type="number" min="1" max="500" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>

                                <div class="space-y-1 md:col-span-2">
                                    <label class="font-bold text-slate-700">Deskripsi / Visi Misi Ekskul</label>
                                    <textarea v-model="ekskulForm.deskripsi" rows="3" placeholder="Tuliskan tujuan kegiatan, program kerja, dan silabus singkat..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                                </div>

                                <div class="flex items-center gap-2 md:col-span-2">
                                    <input id="chkActiveEkskul" v-model="ekskulForm.is_active" type="checkbox" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4" />
                                    <label for="chkActiveEkskul" class="font-semibold text-slate-700">Ekstrakurikuler Aktif Beroperasi</label>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40 flex items-center justify-end gap-2.5 shrink-0">
                            <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold" @click="showEkskulModal = false">Batal</button>
                            <button type="submit" :disabled="ekskulForm.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 shadow-xs flex items-center gap-2">
                                <i v-if="ekskulForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <span>{{ isEditEkskul ? 'Simpan Perubahan' : 'Tambahkan Ekskul' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL 2: TAMBAH / EDIT ANGGOTA (SINGLE ENTRY) ── -->
        <Teleport to="body">
            <div v-if="showAnggotaModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-auto max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                <i class="bi bi-people"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">
                                    {{ isEditAnggota ? 'Edit Data Anggota Ekskul' : 'Pendaftaran Anggota Siswa Baru' }}
                                </h3>
                                <p class="text-[11px] text-slate-500">Periode: {{ getSelectedTahunAjaranName() }} (Semester {{ selectedSemester }})</p>
                            </div>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center" @click="showAnggotaModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitAnggota" class="flex flex-col grow overflow-hidden">
                        <div class="p-6 space-y-4 text-xs overflow-y-auto thin-scrollbar grow">
                            <div v-if="isSuperAdmin" class="space-y-1">
                                <label class="font-bold text-slate-700">Sekolah Asal <span class="text-rose-500">*</span></label>
                                <select v-model="anggotaForm.tenant_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Ekstrakurikuler Target <span class="text-rose-500">*</span></label>
                                <select v-model="anggotaForm.ekskul_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="e in allEkskul" :key="e.id" :value="e.id">{{ e.nama_ekskul }} ({{ e.kategori }})</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Tahun Ajaran <span class="text-rose-500">*</span></label>
                                    <select v-model="anggotaForm.tahun_ajaran_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option v-for="ta in allTahunAjaran" :key="ta.id" :value="ta.id">{{ ta.nama_tahun_ajaran || ta.tahun }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Semester <span class="text-rose-500">*</span></label>
                                    <select v-model="anggotaForm.semester" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="Ganjil">Semester Ganjil</option>
                                        <option value="Genap">Semester Genap</option>
                                    </select>
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-slate-700 flex items-center gap-1.5">
                                        <i class="bi bi-funnel text-blue-600"></i> Filter Kelas (Opsional)
                                    </label>
                                    <span class="text-[10px] text-slate-500">Pilih kelas untuk mempercepat pencarian siswa</span>
                                </div>
                                <select v-model="singleSiswaFilterKelas" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs">
                                    <option value="">Semua Kelas (Tampilkan Semua Siswa)</option>
                                    <option v-for="k in allKelas" :key="k.id" :value="k.nama_kelas">{{ k.nama_kelas }}</option>
                                </select>

                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Pilih Siswa <span class="text-rose-500">*</span></label>
                                    <select v-model="anggotaForm.siswa_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="" disabled>-- Pilih Siswa --</option>
                                        <option v-for="s in filteredAllSiswaForSingle" :key="s.id" :value="s.id">
                                            {{ s.nama_lengkap }} (NISN: {{ s.nisn || '-' }}) - Kelas: {{ s.kelas_saat_ini || '-' }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Jabatan di Ekskul</label>
                                    <select v-model="anggotaForm.jabatan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option v-for="j in jabatanOptions" :key="j" :value="j">{{ j }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Status Keanggotaan</label>
                                    <select v-model="anggotaForm.status_keanggotaan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option v-for="s in statusAnggotaOptions" :key="s" :value="s">{{ s }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Tanggal Bergabung</label>
                                <input v-model="anggotaForm.tanggal_bergabung" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40 flex items-center justify-end gap-2.5 shrink-0">
                            <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold" @click="showAnggotaModal = false">Batal</button>
                            <button type="submit" :disabled="anggotaForm.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 shadow-xs flex items-center gap-2">
                                <i v-if="anggotaForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <span>{{ isEditAnggota ? 'Simpan Perubahan' : 'Daftarkan Anggota' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL 2B: PENDAFTARAN MASSAL / BATCH SISWA PER KELAS ── -->
        <Teleport to="body">
            <div v-if="showBatchAnggotaModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center">
                <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-auto max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">
                                    Pendaftaran Massal Anggota Siswa (Per Kelas)
                                </h3>
                                <p class="text-[11px] text-slate-500">Pilih kelas/rombel untuk mendaftarkan banyak siswa sekaligus ke ekskul</p>
                            </div>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center" @click="showBatchAnggotaModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitBatchAnggota" class="flex flex-col grow overflow-hidden">
                        <div class="p-6 space-y-4 text-xs overflow-y-auto thin-scrollbar grow">
                            <div v-if="isSuperAdmin" class="space-y-1">
                                <label class="font-bold text-slate-700">Sekolah Asal <span class="text-rose-500">*</span></label>
                                <select v-model="batchForm.tenant_id" @change="onBatchTenantChange" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Ekstrakurikuler Target <span class="text-rose-500">*</span></label>
                                    <select v-model="batchForm.ekskul_id" @change="loadSiswaByKelas" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 font-semibold text-slate-800">
                                        <option v-for="e in filteredEkskulForBatch" :key="e.id" :value="e.id">{{ e.nama_ekskul }} ({{ e.kategori }})</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Pilih Rombel / Kelas <span class="text-rose-500">*</span></label>
                                    <select v-model="batchForm.kelas_id" @change="loadSiswaByKelas" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 font-semibold text-slate-800">
                                        <option value="" disabled>-- Pilih Kelas Sumber --</option>
                                        <option v-for="k in filteredKelasForBatch" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Tahun Ajaran <span class="text-rose-500">*</span></label>
                                    <select v-model="batchForm.tahun_ajaran_id" @change="loadSiswaByKelas" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                        <option v-for="ta in allTahunAjaran" :key="ta.id" :value="ta.id">{{ ta.nama_tahun_ajaran || ta.tahun }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Semester <span class="text-rose-500">*</span></label>
                                    <select v-model="batchForm.semester" @change="loadSiswaByKelas" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                        <option value="Ganjil">Semester Ganjil</option>
                                        <option value="Genap">Semester Genap</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Daftar Siswa Rombel -->
                            <div class="space-y-2 pt-2 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-700">Daftar Siswa di Kelas:</span>
                                        <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-[11px]">
                                            {{ batchForm.siswa_ids.length }} dari {{ filteredSiswaKelas.length }} siswa dipilih
                                        </span>
                                    </div>
                                    <button v-if="filteredSiswaKelas.length > 0" type="button" @click="toggleSelectAllSiswa" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <i :class="isAllSiswaSelected ? 'bi-check-square-fill' : 'bi-square'"></i>
                                        <span>{{ isAllSiswaSelected ? 'Batalkan Semua' : 'Pilih Semua Siswa' }}</span>
                                    </button>
                                </div>

                                <div class="relative">
                                    <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                                    <input v-model="searchSiswaKelas" type="text" placeholder="Cari nama siswa atau NISN..." class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white" />
                                </div>

                                <div v-if="isLoadingSiswaKelas" class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200/80">
                                    <i class="bi bi-arrow-repeat animate-spin text-2xl text-emerald-600 mb-2 inline-block"></i>
                                    <p class="text-slate-500 font-medium">Memuat data siswa kelas...</p>
                                </div>

                                <div v-else-if="filteredSiswaKelas.length === 0" class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200/80">
                                    <i class="bi bi-person-x text-3xl text-slate-300 mb-1 inline-block"></i>
                                    <p class="text-slate-600 font-semibold">{{ batchForm.kelas_id ? 'Tidak ada siswa ditemukan di kelas ini.' : 'Silakan pilih kelas terlebih dahulu untuk melihat daftar siswa.' }}</p>
                                </div>

                                <div v-else class="max-h-56 overflow-y-auto border border-slate-200 rounded-2xl divide-y divide-slate-100 bg-white thin-scrollbar">
                                    <label v-for="s in filteredSiswaKelas" :key="s.id" class="p-3 flex items-center justify-between hover:bg-slate-50/80 transition cursor-pointer" :class="{'opacity-60 bg-slate-50': s.is_already_member}">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" :value="s.id" v-model="batchForm.siswa_ids" :disabled="s.is_already_member" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4" />
                                            <div>
                                                <p class="font-bold text-slate-800">{{ s.nama_lengkap }}</p>
                                                <p class="text-[11px] text-slate-500">NISN: {{ s.nisn || '-' }} | NIS: {{ s.nis || '-' }} | JK: {{ s.jenis_kelamin || '-' }}</p>
                                            </div>
                                        </div>
                                        <span v-if="s.is_already_member" class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[10px] font-bold">
                                            Sudah Terdaftar
                                        </span>
                                        <span v-else-if="batchForm.siswa_ids.includes(s.id)" class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 text-[10px] font-bold">
                                            Terpilih
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Jabatan Default</label>
                                    <select v-model="batchForm.jabatan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option v-for="j in jabatanOptions" :key="j" :value="j">{{ j }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Tanggal Bergabung</label>
                                    <input v-model="batchForm.tanggal_bergabung" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40 flex items-center justify-end gap-2.5 shrink-0">
                            <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold" @click="showBatchAnggotaModal = false">Batal</button>
                            <button type="submit" :disabled="batchForm.siswa_ids.length === 0 || batchForm.processing" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 shadow-xs flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i v-if="batchForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <i v-else class="bi bi-check2-circle"></i>
                                <span>Daftarkan {{ batchForm.siswa_ids.length }} Siswa Sekaligus</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL 2C: SALIN ANGGOTA DARI PERIODE SEBELUMNYA ── -->
        <Teleport to="body">
            <div v-if="showCopyAnggotaModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-auto max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center font-bold">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">
                                    Salin Anggota dari Periode Sebelumnya
                                </h3>
                                <p class="text-[11px] text-slate-500">Replikasi cepat data anggota ekskul dari semester/tahun ajaran lalu</p>
                            </div>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center" @click="showCopyAnggotaModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitCopyAnggota" class="flex flex-col grow overflow-hidden">
                        <div class="p-6 space-y-4 text-xs overflow-y-auto thin-scrollbar grow">
                            <div v-if="isSuperAdmin" class="space-y-1">
                                <label class="font-bold text-slate-700">Sekolah Asal <span class="text-rose-500">*</span></label>
                                <select v-model="copyForm.tenant_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-500/20">
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Ekstrakurikuler yang Disalin</label>
                                <select v-model="copyForm.ekskul_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20 font-semibold text-slate-800">
                                    <option value="">Semua Ekstrakurikuler</option>
                                    <option v-for="e in allEkskul" :key="e.id" :value="e.id">{{ e.nama_ekskul }} ({{ e.kategori }})</option>
                                </select>
                            </div>

                            <!-- Periode Asal (Sumber) -->
                            <div class="p-3.5 bg-amber-50/60 rounded-2xl border border-amber-200/70 space-y-3">
                                <p class="font-bold text-amber-900 flex items-center gap-1.5 text-xs">
                                    <i class="bi bi-box-arrow-up-right text-amber-600"></i> 1. Periode Sumber (Data yang Akan Disalin)
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="font-semibold text-slate-700">Tahun Ajaran Sumber</label>
                                        <select v-model="copyForm.from_tahun_ajaran_id" required class="w-full px-3 py-2 rounded-xl border border-amber-200 bg-white text-xs">
                                            <option v-for="ta in allTahunAjaran" :key="ta.id" :value="ta.id">{{ ta.nama_tahun_ajaran || ta.tahun }}</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="font-semibold text-slate-700">Semester Sumber</label>
                                        <select v-model="copyForm.from_semester" required class="w-full px-3 py-2 rounded-xl border border-amber-200 bg-white text-xs">
                                            <option value="Ganjil">Semester Ganjil</option>
                                            <option value="Genap">Semester Genap</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Periode Tujuan (Target) -->
                            <div class="p-3.5 bg-violet-50/60 rounded-2xl border border-violet-200/70 space-y-3">
                                <p class="font-bold text-violet-900 flex items-center gap-1.5 text-xs">
                                    <i class="bi bi-box-arrow-in-down-right text-violet-600"></i> 2. Periode Target (Tujuan Salin)
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="font-semibold text-slate-700">Tahun Ajaran Target</label>
                                        <select v-model="copyForm.to_tahun_ajaran_id" required class="w-full px-3 py-2 rounded-xl border border-violet-200 bg-white text-xs">
                                            <option v-for="ta in allTahunAjaran" :key="ta.id" :value="ta.id">{{ ta.nama_tahun_ajaran || ta.tahun }}</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="font-semibold text-slate-700">Semester Target</label>
                                        <select v-model="copyForm.to_semester" required class="w-full px-3 py-2 rounded-xl border border-violet-200 bg-white text-xs">
                                            <option value="Ganjil">Semester Ganjil</option>
                                            <option value="Genap">Semester Genap</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/80 flex items-center gap-2.5">
                                <input type="checkbox" id="chkCopyActive" v-model="copyForm.only_active" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 w-4 h-4" />
                                <label for="chkCopyActive" class="font-semibold text-slate-700 cursor-pointer text-xs">
                                    Hanya salin anggota dengan status <span class="font-bold text-emerald-600">"Aktif"</span>
                                </label>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40 flex items-center justify-end gap-2.5 shrink-0">
                            <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold" @click="showCopyAnggotaModal = false">Batal</button>
                            <button type="submit" :disabled="copyForm.processing" class="px-5 py-2.5 rounded-xl bg-violet-600 text-white font-semibold hover:bg-violet-700 shadow-xs flex items-center gap-2 disabled:opacity-50">
                                <i v-if="copyForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <i v-else class="bi bi-copy"></i>
                                <span>Salin Anggota Sekarang</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL 3: TAMBAH / EDIT PEMBINA & AKUN LOGIN ── -->
        <Teleport to="body">
            <div v-if="showPembinaModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center">
                <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-auto max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">
                                    {{ isEditPembina ? 'Edit Data Pembina / Pelatih' : 'Tambah Pembina & Pelatih Baru' }}
                                </h3>
                                <p class="text-[11px] text-slate-500">Kelola identitas serta akses akun login pembina ekskul</p>
                            </div>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center" @click="showPembinaModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitPembina" class="flex flex-col grow overflow-hidden">
                        <div class="p-6 space-y-4 text-xs overflow-y-auto thin-scrollbar grow">
                            <div v-if="isSuperAdmin" class="space-y-1">
                                <label class="font-bold text-slate-700">Sekolah Asal <span class="text-rose-500">*</span></label>
                                <select v-model="pembinaForm.tenant_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Nama Lengkap Pembina / Pelatih <span class="text-rose-500">*</span></label>
                                <input v-model="pembinaForm.nama_pembina" type="text" placeholder="Contoh: Drs. Bambang Sutrisno, M.Pd" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Kategori Pembina</label>
                                    <select v-model="pembinaForm.kategori_pembina" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option v-for="kp in kategoriPembinaOptions" :key="kp" :value="kp">{{ kp }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">NIP / NUPTK</label>
                                    <input v-model="pembinaForm.nip" type="text" placeholder="19800512 200501 1 002" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Jenis Kelamin</label>
                                    <select v-model="pembinaForm.jenis_kelamin" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option value="L">Laki-Laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">No. HP / WhatsApp</label>
                                    <input v-model="pembinaForm.no_hp" type="text" placeholder="08123456789" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Alamat Email</label>
                                <input v-model="pembinaForm.email" type="email" placeholder="pembina@sekolah.sch.id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                            </div>

                            <!-- KELOLA AKUN LOGIN PENGGUNA (PORTAL ACCESS) -->
                            <div class="p-4 bg-indigo-50/60 rounded-2xl border border-indigo-200/70 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="font-bold text-indigo-950 flex items-center gap-2">
                                        <i class="bi bi-shield-lock-fill text-indigo-600"></i>
                                        <span>Akun Login Portal (Akses Pembina / Pelatih)</span>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-700 font-bold">Role: Pembina Ekskul</span>
                                </div>
                                <p class="text-[11px] text-indigo-800">
                                    Akun ini memungkinkan pembina/pelatih untuk login ke portal SINTA, mengelola absensi kehadiran, dan mencatat jurnal latihan ekskul.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div class="space-y-1">
                                        <label class="font-bold text-slate-700">Username Login</label>
                                        <input v-model="pembinaForm.username" type="text" placeholder="Contoh: pembina.bambang" class="w-full px-3 py-2 rounded-xl border border-indigo-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="font-bold text-slate-700">
                                            {{ isEditPembina ? 'Ubah Password (Opsional)' : 'Password Akun' }}
                                        </label>
                                        <div class="relative">
                                            <input :type="showPembinaPassword ? 'text' : 'password'" v-model="pembinaForm.password" :placeholder="isEditPembina ? 'Kosongkan jika tetap' : 'Min. 6 karakter'" class="w-full pl-3 pr-9 py-2 rounded-xl border border-indigo-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                                            <button type="button" @click="showPembinaPassword = !showPembinaPassword" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600">
                                                <i :class="showPembinaPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40 flex items-center justify-end gap-2.5 shrink-0">
                            <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold" @click="showPembinaModal = false">Batal</button>
                            <button type="submit" :disabled="pembinaForm.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 shadow-xs flex items-center gap-2">
                                <i v-if="pembinaForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <span>{{ isEditPembina ? 'Simpan Perubahan' : 'Tambahkan Pembina' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL 4: CATAT / EDIT JURNAL LATIHAN ── -->
        <Teleport to="body">
            <div v-if="showJurnalModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center">
                <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-auto max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                                <i class="bi bi-journal-check"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base">
                                {{ isEditJurnal ? 'Edit Jurnal & Dokumentasi Latihan' : 'Catat Jurnal Latihan Baru' }}
                            </h3>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center" @click="showJurnalModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitJurnal" class="flex flex-col grow overflow-hidden">
                        <div class="p-6 space-y-4 text-xs overflow-y-auto thin-scrollbar grow">
                            <div v-if="isSuperAdmin" class="space-y-1">
                                <label class="font-bold text-slate-700">Sekolah Asal <span class="text-rose-500">*</span></label>
                                <select v-model="jurnalForm.tenant_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Ekstrakurikuler <span class="text-rose-500">*</span></label>
                                    <select v-model="jurnalForm.ekskul_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option v-for="e in allEkskul" :key="e.id" :value="e.id">{{ e.nama_ekskul }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Pembina / Pelatih Pengampu</label>
                                    <select v-model="jurnalForm.pembina_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option value="">-- Pilih Pembina --</option>
                                        <option v-for="p in allPembina" :key="p.id" :value="p.id">{{ p.nama_pembina }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Tanggal Latihan <span class="text-rose-500">*</span></label>
                                    <input v-model="jurnalForm.tanggal_kegiatan" type="date" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Jam Mulai</label>
                                    <input v-model="jurnalForm.jam_mulai" type="text" placeholder="15:00" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Jam Selesai</label>
                                    <input v-model="jurnalForm.jam_selesai" type="text" placeholder="17:00" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Materi / Agenda Latihan <span class="text-rose-500">*</span></label>
                                <textarea v-model="jurnalForm.materi_kegiatan" rows="2" placeholder="Contoh: Latihan teknik dasar passing & shooting bola basket..." required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Lokasi / Area</label>
                                    <input v-model="jurnalForm.lokasi" type="text" placeholder="Lapangan Basket" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white" />
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Jumlah Hadir</label>
                                    <input v-model.number="jurnalForm.jumlah_hadir" type="number" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white" />
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Jumlah Absen</label>
                                    <input v-model.number="jurnalForm.jumlah_absen" type="number" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white" />
                                </div>
                            </div>

                            <!-- Upload Foto Dokumentasi -->
                            <div class="space-y-1.5">
                                <label class="font-bold text-slate-700">Foto Dokumentasi Kegiatan (Maks. 5 MB)</label>
                                <div v-if="existingJurnalFile && !removeExistingJurnalFile" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                                    <img :src="existingJurnalFile" alt="Dokumentasi" class="w-12 h-12 object-cover rounded-lg border border-slate-200" />
                                    <div class="grow text-xs text-slate-700 font-semibold truncate">Foto Tersimpan</div>
                                    <button type="button" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition" @click="triggerRemoveJurnalFile">
                                        <i class="bi bi-trash me-1"></i> Hapus Foto
                                    </button>
                                </div>

                                <div v-else-if="jurnalPreviewUrl" class="flex items-center gap-3 p-3 bg-blue-50/50 rounded-xl border border-blue-200">
                                    <img :src="jurnalPreviewUrl" alt="Preview" class="w-12 h-12 object-cover rounded-lg border border-blue-200" />
                                    <div class="grow text-xs text-slate-700 font-semibold truncate">{{ selectedJurnalFile?.name }}</div>
                                    <button type="button" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition" @click="triggerRemoveJurnalFile">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>

                                <input v-else type="file" accept="image/*" @change="handleJurnalFileChange" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40 flex items-center justify-end gap-2.5 shrink-0">
                            <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold" @click="showJurnalModal = false">Batal</button>
                            <button type="submit" :disabled="jurnalForm.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 shadow-xs flex items-center gap-2">
                                <i v-if="jurnalForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <span>{{ isEditJurnal ? 'Simpan Perubahan' : 'Catat Jurnal Latihan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL 5: INPUT PENILAIAN EKSKUL ── -->
        <Teleport to="body">
            <div v-if="showNilaiModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-auto max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                                <i class="bi bi-star"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base">Input Nilai Ekstrakurikuler Siswa</h3>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center" @click="showNilaiModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitNilai" class="flex flex-col grow overflow-hidden">
                        <div class="p-6 space-y-4 text-xs overflow-y-auto thin-scrollbar grow">
                            <div v-if="isSuperAdmin" class="space-y-1">
                                <label class="font-bold text-slate-700">Sekolah Asal <span class="text-rose-500">*</span></label>
                                <select v-model="nilaiForm.tenant_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Ekstrakurikuler <span class="text-rose-500">*</span></label>
                                <select v-model="nilaiForm.ekskul_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="e in allEkskul" :key="e.id" :value="e.id">{{ e.nama_ekskul }}</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Siswa Dinilai <span class="text-rose-500">*</span></label>
                                <select v-model="nilaiForm.siswa_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="s in allSiswa" :key="s.id" :value="s.id">{{ s.nama_lengkap }} (NISN: {{ s.nisn || '-' }})</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Semester</label>
                                    <select v-model="nilaiForm.semester" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                        <option value="Ganjil">Ganjil</option>
                                        <option value="Genap">Genap</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Predikat <span class="text-rose-500">*</span></label>
                                    <select v-model="nilaiForm.predikat" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white font-bold text-blue-600">
                                        <option v-for="po in predikatOptions" :key="po.val" :value="po.val">{{ po.label }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Nilai Angka</label>
                                    <input v-model.number="nilaiForm.nilai_angka" type="number" min="0" max="100" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white" />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Deskripsi / Capaian Kompetensi</label>
                                <textarea v-model="nilaiForm.keterangan" rows="3" placeholder="Tuliskan catatan kemajuan bakat, sikap kedisiplinan, dan dedikasi siswa..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white"></textarea>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40 flex items-center justify-end gap-2.5 shrink-0">
                            <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold" @click="showNilaiModal = false">Batal</button>
                            <button type="submit" :disabled="nilaiForm.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 shadow-xs flex items-center gap-2">
                                <i v-if="nilaiForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <span>Simpan Nilai Siswa</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL 6: CATAT / EDIT PRESTASI SISWA ── -->
        <Teleport to="body">
            <div v-if="showPrestasiModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center">
                <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-auto max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                <i class="bi bi-award"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base">
                                {{ isEditPrestasi ? 'Edit Rekam Prestasi Siswa' : 'Pencatatan Portofolio Prestasi & Kejuaraan' }}
                            </h3>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center" @click="showPrestasiModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitPrestasi" class="flex flex-col grow overflow-hidden">
                        <div class="p-6 space-y-4 text-xs overflow-y-auto thin-scrollbar grow">
                            <div v-if="isSuperAdmin" class="space-y-1">
                                <label class="font-bold text-slate-700">Sekolah Asal <span class="text-rose-500">*</span></label>
                                <select v-model="prestasiForm.tenant_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Nama Lomba / Event Kejuaraan <span class="text-rose-500">*</span></label>
                                <input v-model="prestasiForm.nama_lomba" type="text" placeholder="Contoh: Olimpiade Olahraga Siswa Nasional (O2SN) Tingkat Provinsi" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Bidang Lomba <span class="text-rose-500">*</span></label>
                                    <select v-model="prestasiForm.bidang_lomba" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option v-for="b in bidangPrestasiOptions" :key="b" :value="b">{{ b }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Capaian Juara <span class="text-rose-500">*</span></label>
                                    <select v-model="prestasiForm.juara" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-bold text-amber-700">
                                        <option v-for="j in juaraOptions" :key="j" :value="j">{{ j }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Tingkat Kejuaraan <span class="text-rose-500">*</span></label>
                                    <select v-model="prestasiForm.tingkat_kejuaraan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option v-for="tk in tingkatOptions" :key="tk" :value="tk">{{ tk }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Penyelenggara Event</label>
                                    <input v-model="prestasiForm.penyelenggara" type="text" placeholder="Dinas Pendidikan / Kemendikbud" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Tanggal Lomba</label>
                                    <input v-model="prestasiForm.tanggal_lomba" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Guru Pendamping</label>
                                    <input v-model="prestasiForm.guru_pendamping" type="text" placeholder="Nama Guru" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-slate-700">Poin Prestasi</label>
                                    <input v-model.number="prestasiForm.poin_prestasi" type="number" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                                </div>
                            </div>

                            <!-- Pilih Siswa (Hanya mode tambah baru) -->
                            <div v-if="!isEditPrestasi" class="space-y-1">
                                <label class="font-bold text-slate-700">Pilih Siswa Peraih Prestasi (Bisa lebih dari 1 untuk regu/tim)</label>
                                <select v-model="prestasiForm.siswa_ids" multiple class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white h-24 text-xs thin-scrollbar">
                                    <option v-for="s in allSiswa" :key="s.id" :value="s.id">{{ s.nama_lengkap }} (NISN: {{ s.nisn || '-' }})</option>
                                </select>
                                <span class="text-[10px] text-slate-400">Tahan tombol Ctrl (Windows) untuk memilih banyak siswa.</span>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Nomor Sertifikat / Piagam</label>
                                <input v-model="prestasiForm.nomor_sertifikat" type="text" placeholder="No. Sertifikat: 421/DISDIK/2026/089" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white" />
                            </div>

                            <!-- Upload Berkas Bukti / Sertifikat -->
                            <div class="space-y-1.5">
                                <label class="font-bold text-slate-700">Unggah Piagam / Sertifikat / Foto Bukti (Maks. 10 MB)</label>
                                <div v-if="existingPrestasiFile && !removeExistingPrestasiFile" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                                    <img :src="existingPrestasiFile" alt="Sertifikat" class="w-12 h-12 object-cover rounded-lg border border-slate-200" />
                                    <div class="grow text-xs text-slate-700 font-semibold truncate">Berkas Tersimpan</div>
                                    <button type="button" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition" @click="triggerRemovePrestasiFile">
                                        <i class="bi bi-trash me-1"></i> Hapus
                                    </button>
                                </div>

                                <div v-else-if="prestasiPreviewUrl" class="flex items-center gap-3 p-3 bg-blue-50/50 rounded-xl border border-blue-200">
                                    <img :src="prestasiPreviewUrl" alt="Preview" class="w-12 h-12 object-cover rounded-lg border border-blue-200" />
                                    <div class="grow text-xs text-slate-700 font-semibold truncate">{{ selectedPrestasiFile?.name }}</div>
                                    <button type="button" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition" @click="triggerRemovePrestasiFile">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>

                                <input v-else type="file" accept="image/*,.pdf" @change="handlePrestasiFileChange" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" />
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40 flex items-center justify-end gap-2.5 shrink-0">
                            <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold" @click="showPrestasiModal = false">Batal</button>
                            <button type="submit" :disabled="prestasiForm.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 shadow-xs flex items-center gap-2">
                                <i v-if="prestasiForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                <span>{{ isEditPrestasi ? 'Simpan Perubahan' : 'Catat Prestasi' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL KONFIRMASI HAPUS ── -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center">
                <div class="relative bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 text-center my-auto">
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-lg">Konfirmasi Penghapusan</h3>
                    <p class="text-xs text-slate-500 mt-2">
                        Apakah Anda yakin ingin menghapus data <strong class="text-slate-800">{{ deleteTarget.type }}</strong>:
                        <br />
                        <span class="text-rose-600 font-semibold mt-1 inline-block">"{{ deleteTarget.name }}"</span>?
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button type="button" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold" @click="showDeleteModal = false">Batal</button>
                        <button type="button" class="px-5 py-2.5 rounded-xl bg-rose-600 text-white hover:bg-rose-700 text-xs font-semibold shadow-xs flex items-center gap-1.5" @click="executeDelete">
                            <i class="bi bi-trash"></i> Ya, Hapus Data
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL PREVIEW GAMBAR & DOKUMENTASI ── -->
        <Teleport to="body">
            <div v-if="showImageModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 p-4 sm:p-6 overflow-y-auto flex min-h-full items-center justify-center" @click.self="showImageModal = false">
                <div class="relative bg-white rounded-3xl max-w-3xl w-full shadow-2xl overflow-hidden border border-slate-800 my-auto animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                        <div class="font-bold text-sm truncate flex items-center gap-2">
                            <i class="bi bi-image text-blue-400"></i> {{ previewImageTitle }}
                        </div>
                        <button type="button" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center" @click="showImageModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="p-6 flex items-center justify-center bg-slate-950 min-h-[320px] max-h-[75vh] overflow-auto">
                        <img :src="previewImageUrl" :alt="previewImageTitle" class="max-w-full max-h-[70vh] object-contain rounded-xl shadow-lg" />
                    </div>
                    <div class="px-6 py-3.5 bg-slate-900 text-right flex items-center justify-between text-xs">
                        <a :href="previewImageUrl" target="_blank" download class="text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1.5">
                            <i class="bi bi-download"></i> Unduh Berkas Asli
                        </a>
                        <button type="button" class="px-4 py-1.5 rounded-xl bg-slate-800 text-slate-200 hover:bg-slate-700 font-semibold" @click="showImageModal = false">Tutup</button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.thin-scrollbar::-webkit-scrollbar {
    height: 6px;
    width: 6px;
}
.thin-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 9999px;
}
.thin-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}
.thin-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
