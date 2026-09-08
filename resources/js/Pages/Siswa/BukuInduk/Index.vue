<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
    activeTab: { type: String, default: 'buku_induk_siswa' },
    siswaList: Object,
    tenants: Array,
    kelasList: Array,
    jenjangList: Array,
    tahunAjaranList: Array,
    kurikulumList: Array,
    bankMapel: Array,
    isSuperAdmin: Boolean,
    userRole: String,
    filters: Object,
})

// Tab Navigation
const currentTab = ref(props.activeTab || 'buku_induk_siswa')
const mainTabs = [
    { id: 'buku_induk_siswa', name: 'Buku Induk Siswa', icon: 'bi-book-half' },
    { id: 'seting_kurikulum', name: 'Seting Kurikulum', icon: 'bi-gear-wide-connected' },
    { id: 'input_nilai_rapor', name: 'Input Nilai Rapor', icon: 'bi-pencil-square' },
    { id: 'cetak_buku_induk', name: 'Cetak Buku Induk', icon: 'bi-printer-fill' },
    { id: 'riwayat_kepsek', name: 'Riwayat Kepala Sekolah', icon: 'bi-clock-history' },
    { id: 'arsip_alumni', name: 'Arsip Alumni', icon: 'bi-safe2-fill' },
]

// Super Admin Filter Tenant
const selectedTenant = ref(props.filters?.tenant_id || '')

const getSelectedTenantName = () => {
    if (!selectedTenant.value) return 'Semua Sekolah Terdaftar (Super Admin)'
    const tenant = props.tenants?.find(t => t.id === selectedTenant.value)
    return tenant ? tenant.nama_sekolah : 'Semua Sekolah Terdaftar (Super Admin)'
}

const applyTenantFilter = () => {
    router.get('/buku-induk', {
        tab: currentTab.value,
        tenant_id: selectedTenant.value,
        search: search.value,
        jenjang_id: filterJenjang.value,
        kelas_id: filterKelas.value,
        status: filterStatus.value,
        per_page: perPage.value,
    }, { preserveState: true, replace: true })
}

const switchTab = (tabId) => {
    currentTab.value = tabId
    if (tabId === 'riwayat_kepsek') {
        loadRiwayatKepsek()
    } else if (tabId === 'arsip_alumni') {
        loadAlumni(1)
    } else if (tabId === 'cetak_buku_induk') {
        loadMatrixCetak()
    } else if (tabId === 'seting_kurikulum') {
        loadKurikulum()
    } else if (tabId === 'input_nilai_rapor') {
        loadNilaiRapor()
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 1: BUKU INDUK SISWA
// ═══════════════════════════════════════════════════════════════════════════════
const search = ref(props.filters?.search || '')
const filterJenjang = ref(props.filters?.jenjang_id || '')
const filterKelas = ref(props.filters?.kelas_id || '')
const filterStatus = ref(props.filters?.status || '')
const perPage = ref(props.filters?.per_page || 10)

let searchTimeout = null
const debounceSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilter(1)
    }, 400)
}

const filteredKelasList = computed(() => {
    if (!filterJenjang.value) return props.kelasList || []
    return (props.kelasList || []).filter(k => k.id_jenjang === filterJenjang.value || !k.id_jenjang)
})

const onJenjangChange = () => {
    if (filterKelas.value) {
        const stillValid = filteredKelasList.value.some(k => k.id === filterKelas.value)
        if (!stillValid) {
            filterKelas.value = ''
        }
    }
    applyFilter(1)
}

const applyFilter = (page = 1) => {
    router.get('/buku-induk', {
        tab: 'buku_induk_siswa',
        page: page,
        search: search.value,
        jenjang_id: filterJenjang.value,
        kelas_id: filterKelas.value,
        status: filterStatus.value,
        tenant_id: selectedTenant.value,
        per_page: perPage.value,
    }, { preserveState: true, replace: true })
}

const resetFilter = () => {
    search.value = ''
    filterJenjang.value = ''
    filterKelas.value = ''
    filterStatus.value = ''
    applyFilter(1)
}

const goToPage = (url) => {
    if (url) {
        router.visit(url, { preserveScroll: true, preserveState: true })
    }
}

const exportExcel = () => {
    const params = new URLSearchParams({
        tenant_id: selectedTenant.value || '',
        kelas_id: filterKelas.value || '',
        status: filterStatus.value || '',
    })
    window.open(`/buku-induk/export-excel?${params.toString()}`, '_blank')
}

const exportPdssExcel = () => {
    const params = new URLSearchParams({
        tenant_id: selectedTenant.value || '',
        kelas_id: filterKelas.value || '',
    })
    window.open(`/buku-induk/export-pdss?${params.toString()}`, '_blank')
}

const isKelas12Selected = computed(() => {
    if (!filterKelas.value) return false
    const k = props.kelasList?.find(c => c.id === filterKelas.value || c.nama_kelas === filterKelas.value)
    if (!k) return false
    const name = k.nama_kelas.toUpperCase()
    return name.includes('XII') || name.includes('12')
})

const getSmartPaginationLinks = (pagination) => {
    if (!pagination?.links || pagination.links.length === 0) return []
    const rawLinks = pagination.links
    const prevLink = rawLinks[0]
    const nextLink = rawLinks[rawLinks.length - 1]
    const pageLinks = rawLinks.slice(1, -1)
    const current = pagination.current_page || 1
    const last = pagination.last_page || (pageLinks.length ? Number(pageLinks[pageLinks.length - 1].label) || 1 : 1)

    const result = []
    result.push({
        ...prevLink,
        isPrev: true,
        isNext: false,
        label: prevLink.label,
    })

    if (last <= 7) {
        pageLinks.forEach(l => {
            result.push({
                ...l,
                isPrev: false,
                isNext: false,
                label: l.label,
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
                    isNext: false,
                })
            }
            const foundRaw = pageLinks.find(l => l.label == p.toString())
            result.push({
                label: p.toString(),
                url: foundRaw ? foundRaw.url : null,
                active: p === current,
                isPrev: false,
                isNext: false,
            })
            prevPage = p
        })
    }

    result.push({
        ...nextLink,
        isPrev: false,
        isNext: true,
        label: nextLink.label,
    })

    return result
}

// Modal Detail Siswa (6 Sub-Tab Terpadu)
const isDetailOpen = ref(false)
const detailLoading = ref(false)
const detailActiveSubTab = ref('profil_keluarga')
const activeStudent = ref(null)

const subTabs = [
    { id: 'profil_keluarga', name: 'Profil & Keluarga', icon: 'bi-person-vcard-fill' },
    { id: 'kesehatan', name: 'Fisik & Kesehatan', icon: 'bi-heart-pulse-fill' },
    { id: 'akademik', name: 'Akademik & Rapor', icon: 'bi-file-earmark-bar-graph-fill' },
    { id: 'prestasi_beasiswa', name: 'Prestasi & Beasiswa', icon: 'bi-trophy-fill' },
    { id: 'kedisiplinan', name: 'Kedisiplinan & BK', icon: 'bi-shield-fill-exclamation' },
    { id: 'tracer', name: 'Tracer Study', icon: 'bi-mortarboard-fill' },
]

const formatIndoDate = (d) => {
    if (!d) return '-'
    try {
        const cleanDate = typeof d === 'string' && d.includes('T') ? d.split('T')[0] : String(d)
        const parts = cleanDate.split('-')
        if (parts.length === 3) {
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
            const y = parseInt(parts[0], 10)
            const m = parseInt(parts[1], 10) - 1
            const day = parseInt(parts[2], 10)
            if (!isNaN(y) && !isNaN(m) && !isNaN(day) && m >= 0 && m < 12) {
                return `${day} ${months[m]} ${y}`
            }
        }
        return cleanDate
    } catch (e) {
        return d
    }
}

const openDetailModal = async (studentId, defaultSubTab = 'profil_keluarga') => {
    const validTabs = ['profil_keluarga', 'kesehatan', 'akademik', 'prestasi_beasiswa', 'kedisiplinan', 'tracer']
    const legacyMap = {
        'identitas': 'profil_keluarga',
        'ortu': 'profil_keluarga',
        'kesehatan': 'kesehatan',
        'riwayat_kelas': 'akademik',
        'nilai_rapor': 'akademik',
        'prestasi': 'prestasi_beasiswa',
        'beasiswa': 'prestasi_beasiswa',
        'pelanggaran': 'kedisiplinan',
        'tracer': 'tracer',
    }
    detailActiveSubTab.value = legacyMap[defaultSubTab] || (validTabs.includes(defaultSubTab) ? defaultSubTab : 'profil_keluarga')
    isDetailOpen.value = true
    detailLoading.value = true
    activeStudent.value = null
    try {
        const res = await axios.get(`/buku-induk/api/detail/${studentId}`)
        if (res.data && res.data.success) {
            activeStudent.value = res.data.data
        }
    } catch (err) {
        console.error('Gagal memuat detail siswa:', err)
    } finally {
        detailLoading.value = false
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// TOAST NOTIFICATION POPUP SYSTEM
// ═══════════════════════════════════════════════════════════════════════════════
const toasts = ref([])

const showToast = (type = 'success', title = '', message = '', duration = 4000) => {
    const id = Date.now() + Math.random().toString(36).substring(2, 7)
    toasts.value.push({ id, type, title, message, duration })
    setTimeout(() => {
        removeToast(id)
    }, duration)
}

const removeToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id)
}

// ═══════════════════════════════════════════════════════════════════════════════
// INTERACTIVE CONFIRMATION MODAL POPUP SYSTEM
// ═══════════════════════════════════════════════════════════════════════════════
const confirmModal = ref({
    isOpen: false,
    type: 'danger', // 'danger' | 'warning' | 'info' | 'primary'
    title: 'Konfirmasi Aksi',
    message: '',
    subMessage: '',
    confirmText: 'Ya, Lanjutkan',
    cancelText: 'Batal',
    loading: false,
    onConfirm: null,
})

const openConfirmModal = ({ title, message, subMessage = '', type = 'danger', confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', onConfirm }) => {
    confirmModal.value = {
        isOpen: true,
        type,
        title,
        message,
        subMessage,
        confirmText,
        cancelText,
        loading: false,
        onConfirm,
    }
}

const closeConfirmModal = () => {
    if (!confirmModal.value.loading) {
        confirmModal.value.isOpen = false
    }
}

const executeConfirmModalAction = async () => {
    if (confirmModal.value.onConfirm && typeof confirmModal.value.onConfirm === 'function') {
        confirmModal.value.loading = true
        try {
            await confirmModal.value.onConfirm()
            confirmModal.value.isOpen = false
        } catch (err) {
            console.error('Error in confirm action:', err)
        } finally {
            confirmModal.value.loading = false
        }
    } else {
        confirmModal.value.isOpen = false
    }
}

// Form Tambah Beasiswa di Modal
const newBeasiswa = ref({
    nama_beasiswa: '',
    penyelenggara: '',
    tahun_menerima: new Date().getFullYear().toString(),
    nominal: '',
    keterangan: '',
})
const isSavingBeasiswa = ref(false)

const submitBeasiswa = async () => {
    if (!activeStudent.value || !newBeasiswa.value.nama_beasiswa) return
    isSavingBeasiswa.value = true
    try {
        const res = await axios.post('/buku-induk/api/beasiswa', {
            siswa_id: activeStudent.value.id,
            ...newBeasiswa.value
        })
        if (res.data.success) {
            showToast('success', 'Beasiswa Berhasil Ditambahkan', 'Data riwayat beasiswa siswa telah disimpan.')
            await openDetailModal(activeStudent.value.id, 'prestasi_beasiswa')
            newBeasiswa.value = {
                nama_beasiswa: '',
                penyelenggara: '',
                tahun_menerima: new Date().getFullYear().toString(),
                nominal: '',
                keterangan: '',
            }
        }
    } catch (err) {
        showToast('error', 'Gagal Menyimpan Beasiswa', err.response?.data?.message || err.message)
    } finally {
        isSavingBeasiswa.value = false
    }
}

const deleteBeasiswa = (bId) => {
    openConfirmModal({
        title: 'Hapus Riwayat Beasiswa?',
        message: 'Apakah Anda yakin ingin menghapus catatan beasiswa ini dari buku induk siswa?',
        subMessage: 'Tindakan ini tidak dapat dibatalkan.',
        type: 'danger',
        confirmText: 'Ya, Hapus',
        onConfirm: async () => {
            try {
                await axios.delete(`/buku-induk/api/beasiswa/${bId}`)
                showToast('success', 'Beasiswa Dihapus', 'Riwayat beasiswa berhasil dihapus.')
                if (activeStudent.value) {
                    await openDetailModal(activeStudent.value.id, 'prestasi_beasiswa')
                }
            } catch (err) {
                showToast('error', 'Gagal Menghapus', 'Gagal menghapus riwayat beasiswa.')
            }
        }
    })
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 2: SETING KURIKULUM
// ═══════════════════════════════════════════════════════════════════════════════
const kurikulumParams = ref({
    tahun_ajaran: props.tahunAjaranList?.[0]?.tahun_ajaran || '2026/2027',
    semester: 'Ganjil',
    kelas_id: props.kelasList?.[0]?.id || '',
    kurikulum_id: props.kurikulumList?.[0]?.id || '',
})
const kurikulumGroups = ref([])
const loadingKurikulum = ref(false)
const isKurikulumLocked = ref(false)
const isSavingKurikulum = ref(false)

const loadKurikulum = async () => {
    if (!kurikulumParams.value.kelas_id) return
    loadingKurikulum.value = true
    try {
        const res = await axios.get('/buku-induk/api/kurikulum', {
            params: {
                kelas_id: kurikulumParams.value.kelas_id,
                tahun_ajaran: kurikulumParams.value.tahun_ajaran,
                semester: kurikulumParams.value.semester,
                tenant_id: selectedTenant.value,
            }
        })
        if (res.data.success) {
            kurikulumGroups.value = res.data.groups || []
            if (res.data.active_kurikulum_id) {
                kurikulumParams.value.kurikulum_id = res.data.active_kurikulum_id
            }
            isKurikulumLocked.value = !!res.data.is_locked
        }
    } catch (err) {
        console.error('Error load kurikulum:', err)
    } finally {
        loadingKurikulum.value = false
    }
}

const addKurikulumGroup = () => {
    const nextChar = String.fromCharCode(65 + kurikulumGroups.value.length)
    kurikulumGroups.value.push({
        kelompok_id: `Kelompok ${nextChar} (Peminatan)`,
        mapel_ids: [],
        searchQuery: '',
    })
}

const removeKurikulumGroup = (index) => {
    kurikulumGroups.value.splice(index, 1)
}

const saveKurikulumSetting = async () => {
    isSavingKurikulum.value = true
    try {
        const res = await axios.post('/buku-induk/api/kurikulum', {
            kelas_id: kurikulumParams.value.kelas_id,
            tahun_ajaran: kurikulumParams.value.tahun_ajaran,
            semester: kurikulumParams.value.semester,
            kurikulum_id: kurikulumParams.value.kurikulum_id,
            groups: kurikulumGroups.value,
            tenant_id: selectedTenant.value,
        })
        if (res.data.success) {
            showToast('success', 'Kurikulum Berhasil Disimpan', 'Konfigurasi kelompok mata pelajaran kurikulum berhasil disimpan.')
        }
    } catch (err) {
        showToast('error', 'Gagal Menyimpan Kurikulum', err.response?.data?.message || err.message)
    } finally {
        isSavingKurikulum.value = false
    }
}

const toggleKurikulumLock = () => {
    const isCurrentlyLocked = isKurikulumLocked.value
    openConfirmModal({
        title: isCurrentlyLocked ? 'Buka Kunci Kurikulum?' : 'Kunci Konfigurasi Kurikulum?',
        message: isCurrentlyLocked 
            ? 'Membuka kunci akan mengizinkan perubahan kembali pada susunan kelompok mata pelajaran kurikulum ini.'
            : 'Mengunci kurikulum akan memproteksi susunan mata pelajaran agar tidak dapat diubah oleh pengguna lain.',
        type: isCurrentlyLocked ? 'info' : 'warning',
        confirmText: isCurrentlyLocked ? 'Ya, Buka Kunci' : 'Ya, Kunci Kurikulum',
        onConfirm: async () => {
            try {
                const res = await axios.post('/buku-induk/api/toggle-lock', {
                    tipe: 'kurikulum',
                    tahun_ajaran: kurikulumParams.value.tahun_ajaran,
                    semester: kurikulumParams.value.semester,
                    tenant_id: selectedTenant.value,
                })
                if (res.data.success) {
                    isKurikulumLocked.value = res.data.is_locked
                    showToast('success', 'Status Kurikulum Diperbarui', res.data.message)
                }
            } catch (err) {
                showToast('error', 'Gagal Mengubah Kunci Kurikulum', err.response?.data?.message || err.message)
            }
        }
    })
}

// Modal Salin Kurikulum
const isCopyModalOpen = ref(false)
const copySourceKelasId = ref('')
const submitCopyKurikulum = async () => {
    if (!copySourceKelasId.value) return
    try {
        const res = await axios.post('/buku-induk/api/kurikulum/copy', {
            source_kelas_id: copySourceKelasId.value,
            target_kelas_id: kurikulumParams.value.kelas_id,
            tahun_ajaran: kurikulumParams.value.tahun_ajaran,
            semester: kurikulumParams.value.semester,
            tenant_id: selectedTenant.value,
        })
        if (res.data.success) {
            showToast('success', 'Kurikulum Berhasil Disalin', 'Seluruh kelompok mata pelajaran berhasil disalin ke kelas target.')
            isCopyModalOpen.value = false
            loadKurikulum()
        }
    } catch (err) {
        showToast('error', 'Gagal Menyalin Kurikulum', err.response?.data?.message || err.message)
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 3: INPUT NILAI RAPOR
// ═══════════════════════════════════════════════════════════════════════════════
const nilaiParams = ref({
    tahun_ajaran: props.tahunAjaranList?.[0]?.tahun_ajaran || '2026/2027',
    semester: 'Ganjil',
    kelas_id: props.kelasList?.[0]?.id || '',
})
const nilaiStudents = ref([])
const nilaiSubjects = ref([])
const isNilaiLocked = ref(false)
const loadingNilai = ref(false)
const activeKurikulumName = ref('Kurikulum Merdeka')
const nilaiSearchQuery = ref('')

const filteredNilaiStudents = computed(() => {
    if (!nilaiSearchQuery.value) return nilaiStudents.value
    const q = nilaiSearchQuery.value.toLowerCase()
    return nilaiStudents.value.filter(s => 
        (s.nama_lengkap && s.nama_lengkap.toLowerCase().includes(q)) ||
        (s.nisn && s.nisn.toLowerCase().includes(q)) ||
        (s.nis && s.nis.toLowerCase().includes(q))
    )
})

const loadNilaiRapor = async () => {
    if (!nilaiParams.value.kelas_id) return
    loadingNilai.value = true
    try {
        const res = await axios.get('/buku-induk/api/nilai-rapor', {
            params: {
                kelas_id: nilaiParams.value.kelas_id,
                tahun_ajaran: nilaiParams.value.tahun_ajaran,
                semester: nilaiParams.value.semester,
                tenant_id: selectedTenant.value,
            }
        })
        if (res.data.success) {
            nilaiStudents.value = res.data.students || []
            nilaiSubjects.value = res.data.subjects || []
            isNilaiLocked.value = !!res.data.is_locked
            activeKurikulumName.value = res.data.kurikulum_nama || 'Kurikulum Merdeka'
        }
    } catch (err) {
        console.error('Error load nilai:', err)
    } finally {
        loadingNilai.value = false
    }
}

const toggleNilaiLock = () => {
    const isCurrentlyLocked = isNilaiLocked.value
    openConfirmModal({
        title: isCurrentlyLocked ? 'Buka Kunci Nilai Rapor?' : 'Kunci Nilai Rapor Kelas?',
        message: isCurrentlyLocked 
            ? 'Membuka kunci akan mengizinkan guru/staf untuk menginput dan memperbarui nilai rapor kembali.'
            : 'Mengunci nilai akan memfinalisasi rapor dan mencegah perubahan nilai selanjutnya.',
        type: isCurrentlyLocked ? 'info' : 'warning',
        confirmText: isCurrentlyLocked ? 'Ya, Buka Kunci' : 'Ya, Kunci Nilai',
        onConfirm: async () => {
            try {
                const res = await axios.post('/buku-induk/api/toggle-lock', {
                    tipe: 'nilai',
                    tahun_ajaran: nilaiParams.value.tahun_ajaran,
                    semester: nilaiParams.value.semester,
                    tenant_id: selectedTenant.value,
                })
                if (res.data.success) {
                    isNilaiLocked.value = res.data.is_locked
                    showToast('success', 'Status Kunci Nilai Diperbarui', res.data.message)
                }
            } catch (err) {
                showToast('error', 'Gagal Mengubah Kunci Nilai', err.response?.data?.message || err.message)
            }
        }
    })
}

// Modal Edit Nilai Siswa
const isEditNilaiOpen = ref(false)
const selectedStudentNilai = ref(null)
const tempGrades = ref({})

const openEditNilaiModal = (student) => {
    selectedStudentNilai.value = student
    tempGrades.value = {}
    nilaiSubjects.value.forEach(sub => {
        const current = student.grades?.[sub.mapel_id] || {}
        tempGrades.value[sub.mapel_id] = {
            nilai_akhir: current.nilai_akhir !== undefined ? current.nilai_akhir : '',
            predikat: current.predikat || '',
            capaian: current.capaian || '',
        }
    })
    isEditNilaiOpen.value = true
}

const onNilaiInput = (mapelId) => {
    const val = parseFloat(tempGrades.value[mapelId].nilai_akhir)
    if (!isNaN(val)) {
        if (val >= 88) tempGrades.value[mapelId].predikat = 'A'
        else if (val >= 78) tempGrades.value[mapelId].predikat = 'B'
        else if (val >= 65) tempGrades.value[mapelId].predikat = 'C'
        else tempGrades.value[mapelId].predikat = 'D'
    }
}

const saveStudentGrades = async () => {
    if (!selectedStudentNilai.value) return
    try {
        const res = await axios.post('/buku-induk/api/nilai-rapor', {
            kelas_id: nilaiParams.value.kelas_id,
            tahun_ajaran: nilaiParams.value.tahun_ajaran,
            semester: nilaiParams.value.semester,
            siswa_id: selectedStudentNilai.value.id,
            grades: tempGrades.value,
            tenant_id: selectedTenant.value,
        })
        if (res.data.success) {
            showToast('success', 'Nilai Rapor Disimpan', 'Nilai siswa berhasil disimpan ke basis data.')
            isEditNilaiOpen.value = false
            loadNilaiRapor()
        }
    } catch (err) {
        showToast('error', 'Gagal Menyimpan Nilai', err.response?.data?.message || err.message)
    }
}

const deleteStudentGrades = (student) => {
    openConfirmModal({
        title: 'Hapus Seluruh Nilai Siswa?',
        message: `Apakah Anda yakin ingin menghapus seluruh rekaman nilai rapor siswa "${student.nama_lengkap}" pada semester ini?`,
        subMessage: 'Nilai seluruh mata pelajaran untuk siswa ini akan dikosongkan.',
        type: 'danger',
        confirmText: 'Ya, Hapus Nilai',
        onConfirm: async () => {
            try {
                await axios.delete('/buku-induk/api/nilai-rapor', {
                    data: {
                        kelas_id: nilaiParams.value.kelas_id,
                        tahun_ajaran: nilaiParams.value.tahun_ajaran,
                        semester: nilaiParams.value.semester,
                        siswa_id: student.id,
                        tenant_id: selectedTenant.value,
                    }
                })
                showToast('success', 'Nilai Berhasil Dihapus', `Nilai siswa ${student.nama_lengkap} telah dihapus.`)
                loadNilaiRapor()
            } catch (err) {
                showToast('error', 'Gagal Menghapus Nilai', 'Terjadi kesalahan saat menghapus nilai siswa.')
            }
        }
    })
}

// Ekspor & Impor Nilai Rapor Sesuai Kurikulum
const isImportNilaiOpen = ref(false)
const importNilaiFile = ref(null)
const isUploadingNilai = ref(false)

const downloadNilaiTemplate = () => {
    if (!nilaiParams.value.kelas_id) {
        showToast('warning', 'Pilih Kelas Fisik', 'Silakan pilih kelas fisik terlebih dahulu sebelum mengunduh format nilai.')
        return
    }
    const url = `/buku-induk/api/nilai-rapor/export?kelas_id=${nilaiParams.value.kelas_id}&tahun_ajaran=${encodeURIComponent(nilaiParams.value.tahun_ajaran)}&semester=${nilaiParams.value.semester}&tenant_id=${selectedTenant.value || ''}`
    window.open(url, '_blank')
}

const onNilaiFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        importNilaiFile.value = file
    }
}

const submitImportNilai = async () => {
    if (!importNilaiFile.value) {
        showToast('warning', 'Pilih Berkas File', 'Silakan pilih berkas format nilai (.csv / .xlsx) yang ingin diimpor.')
        return
    }
    isUploadingNilai.value = true
    const formData = new FormData()
    formData.append('file', importNilaiFile.value)
    formData.append('kelas_id', nilaiParams.value.kelas_id)
    formData.append('tahun_ajaran', nilaiParams.value.tahun_ajaran)
    formData.append('semester', nilaiParams.value.semester)
    formData.append('tenant_id', selectedTenant.value || '')

    try {
        const res = await axios.post('/buku-induk/api/nilai-rapor/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        if (res.data.success) {
            showToast('success', 'Impor Nilai Berhasil', res.data.message)
            isImportNilaiOpen.value = false
            importNilaiFile.value = null
            loadNilaiRapor()
        }
    } catch (err) {
        showToast('error', 'Gagal Mengimpor Nilai', err.response?.data?.message || err.message)
    } finally {
        isUploadingNilai.value = false
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 4: CETAK BUKU INDUK
// ═══════════════════════════════════════════════════════════════════════════════
const cetakParams = ref({
    tahun_ajaran: '',
    kelas_id: '',
    status: 'Aktif',
})
const matrixData = ref([])
const matrixMaxYears = ref(3)
const loadingMatrix = ref(false)

const loadMatrixCetak = async () => {
    loadingMatrix.value = true
    try {
        const res = await axios.get('/buku-induk/api/matrix-cetak', {
            params: {
                tahun_ajaran: cetakParams.value.tahun_ajaran,
                kelas_id: cetakParams.value.kelas_id,
                status: cetakParams.value.status,
                tenant_id: selectedTenant.value,
            }
        })
        if (res.data.success) {
            matrixData.value = res.data.matrix || []
            matrixMaxYears.value = res.data.max_years || 3
        }
    } catch (err) {
        console.error('Error load matrix cetak:', err)
    } finally {
        loadingMatrix.value = false
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 5: RIWAYAT KEPALA SEKOLAH
// ═══════════════════════════════════════════════════════════════════════════════
const riwayatKepsekList = ref([])
const loadingKepsek = ref(false)
const isModalKepsekOpen = ref(false)
const kepsekForm = ref({
    id: '',
    nama_kepsek: '',
    nip_kepsek: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    status_plt: false,
})

const loadRiwayatKepsek = async () => {
    loadingKepsek.value = true
    try {
        const res = await axios.get('/buku-induk/api/riwayat-kepsek', {
            params: { tenant_id: selectedTenant.value }
        })
        if (res.data.success) {
            riwayatKepsekList.value = res.data.data || []
        }
    } catch (err) {
        console.error('Error load kepsek:', err)
    } finally {
        loadingKepsek.value = false
    }
}

const openTambahKepsek = () => {
    kepsekForm.value = {
        id: '',
        nama_kepsek: '',
        nip_kepsek: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
        status_plt: false,
    }
    isModalKepsekOpen.value = true
}

const openEditKepsek = (item) => {
    kepsekForm.value = {
        id: item.id,
        nama_kepsek: item.nama_kepsek,
        nip_kepsek: item.nip_kepsek || '',
        tanggal_mulai: item.tanggal_mulai ? item.tanggal_mulai.substring(0, 10) : '',
        tanggal_selesai: item.tanggal_selesai ? item.tanggal_selesai.substring(0, 10) : '',
        status_plt: item.status_plt == 1,
    }
    isModalKepsekOpen.value = true
}

const saveKepsek = async () => {
    try {
        const res = await axios.post('/buku-induk/api/riwayat-kepsek', {
            ...kepsekForm.value,
            tenant_id: selectedTenant.value,
        })
        if (res.data.success) {
            showToast('success', 'Riwayat Kepsek Disimpan', 'Data masa jabatan kepala sekolah berhasil disimpan.')
            isModalKepsekOpen.value = false
            loadRiwayatKepsek()
        }
    } catch (err) {
        showToast('error', 'Gagal Menyimpan Riwayat Kepsek', err.response?.data?.message || err.message)
    }
}

const deleteKepsek = (id) => {
    openConfirmModal({
        title: 'Hapus Riwayat Kepala Sekolah?',
        message: 'Apakah Anda yakin ingin menghapus catatan riwayat masa jabatan kepala sekolah ini?',
        type: 'danger',
        confirmText: 'Ya, Hapus',
        onConfirm: async () => {
            try {
                await axios.delete(`/buku-induk/api/riwayat-kepsek/${id}`)
                showToast('success', 'Data Dihapus', 'Riwayat kepala sekolah berhasil dihapus.')
                loadRiwayatKepsek()
            } catch (err) {
                showToast('error', 'Gagal Menghapus', 'Gagal menghapus riwayat kepala sekolah.')
            }
        }
    })
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 6: ARSIP ALUMNI
// ═══════════════════════════════════════════════════════════════════════════════
const alumniList = ref([])
const alumniSearch = ref('')
const alumniPage = ref(1)
const alumniTotal = ref(0)
const alumniTotalPages = ref(1)
const loadingAlumni = ref(false)
const selectedAlumni = ref(null)
const alumniUploadForm = ref({
    jenis_dokumen: 'Ijazah',
    keterangan: '',
    berkas: null,
})
const isUploadingAlumni = ref(false)

const loadAlumni = async (page = 1) => {
    alumniPage.value = page
    loadingAlumni.value = true
    try {
        const res = await axios.get('/buku-induk/api/alumni', {
            params: {
                page: page,
                search: alumniSearch.value,
                tenant_id: selectedTenant.value,
            }
        })
        if (res.data.success) {
            alumniList.value = res.data.data || []
            alumniTotal.value = res.data.total || 0
            alumniTotalPages.value = res.data.last_page || 1
        }
    } catch (err) {
        console.error('Error load alumni:', err)
    } finally {
        loadingAlumni.value = false
    }
}

const selectAlumni = async (alumni) => {
    selectedAlumni.value = alumni
    try {
        const res = await axios.get(`/buku-induk/api/detail/${alumni.id}`)
        if (res.data.success) {
            selectedAlumni.value.dokumen = res.data.data.dokumen || []
        }
    } catch (err) {
        console.error('Error load alumni docs:', err)
    }
}

const onAlumniFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        alumniUploadForm.value.berkas = file
    }
}

const uploadAlumniDoc = async () => {
    if (!selectedAlumni.value || !alumniUploadForm.value.berkas) {
        showToast('warning', 'Berkas Belum Dipilih', 'Silakan pilih berkas dokumen (PDF/Gambar) terlebih dahulu.')
        return
    }
    isUploadingAlumni.value = true
    const formData = new FormData()
    formData.append('siswa_id', selectedAlumni.value.id)
    formData.append('jenis_dokumen', alumniUploadForm.value.jenis_dokumen)
    formData.append('keterangan', alumniUploadForm.value.keterangan || '')
    formData.append('berkas', alumniUploadForm.value.berkas)
    if (selectedTenant.value) {
        formData.append('tenant_id', selectedTenant.value)
    }

    try {
        const res = await axios.post('/buku-induk/api/alumni/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        if (res.data.success) {
            showToast('success', 'Dokumen Berhasil Diarsipkan', 'Berkas dokumen alumni telah tersimpan di brankas digital.')
            alumniUploadForm.value.berkas = null
            alumniUploadForm.value.keterangan = ''
            await selectAlumni(selectedAlumni.value)
        }
    } catch (err) {
        showToast('error', 'Gagal Mengunggah Dokumen', err.response?.data?.message || err.message)
    } finally {
        isUploadingAlumni.value = false
    }
}

const deleteAlumniDoc = (docId) => {
    openConfirmModal({
        title: 'Hapus Dokumen Arsip Alumni?',
        message: 'Apakah Anda yakin ingin menghapus berkas dokumen alumni ini dari brankas digital?',
        type: 'danger',
        confirmText: 'Ya, Hapus Dokumen',
        onConfirm: async () => {
            try {
                await axios.delete(`/buku-induk/api/alumni/${docId}`)
                showToast('success', 'Dokumen Dihapus', 'Berkas arsip alumni berhasil dihapus.')
                if (selectedAlumni.value) {
                    await selectAlumni(selectedAlumni.value)
                }
            } catch (err) {
                showToast('error', 'Gagal Menghapus Dokumen', 'Terjadi kesalahan saat menghapus berkas.')
            }
        }
    })
}

// PDF Viewer Modal
const isPdfViewerOpen = ref(false)
const activePdfUrl = ref('')
const activePdfTitle = ref('')

const openPdfViewer = (url, title) => {
    activePdfUrl.value = url
    activePdfTitle.value = title
    isPdfViewerOpen.value = true
}

// Horizontal NavTabs Scroller
const scrollNav = (distance) => {
    const el = document.getElementById('navTabsBukuInduk')
    if (el) {
        el.scrollBy({ left: distance, behavior: 'smooth' })
    }
}

onMounted(() => {
    const el = document.getElementById('navTabsBukuInduk')
    if (el) {
        el.addEventListener('wheel', (e) => {
            if (e.deltaY !== 0) {
                e.preventDefault()
                el.scrollLeft += e.deltaY
            }
        }, { passive: false })
    }

    if (currentTab.value === 'cetak_buku_induk') loadMatrixCetak()
    else if (currentTab.value === 'riwayat_kepsek') loadRiwayatKepsek()
    else if (currentTab.value === 'arsip_alumni') loadAlumni(1)
    else if (currentTab.value === 'seting_kurikulum') loadKurikulum()
    else if (currentTab.value === 'input_nilai_rapor') loadNilaiRapor()
})
</script>

<template>
    <Head title="Buku Induk Siswa" />

    <AppLayout title="Buku Induk Siswa">
        <div class="space-y-6">

            <!-- 1. Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-xs shrink-0">
                        <i class="bi bi-book-half"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Buku Induk Siswa</h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Catatan kumpulan rekam data pokok, matriks akademik, dan dokumen historis seluruh siswa.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2. Filter Sekolah Banner (Legacy Design Standard) -->
            <div 
                v-if="isSuperAdmin" 
                class="p-4 sm:px-5 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3"
            >
                <div class="flex flex-wrap items-center gap-2.5">
                    <i class="bi bi-building text-blue-600 text-lg"></i>
                    <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
                        <i class="bi bi-funnel-fill me-1"></i> Aktif
                    </span>

                    <!-- Dropdown Filter Sekolah (Khusus Super Admin) -->
                    <div class="my-1 md:my-0">
                        <select 
                            v-model="selectedTenant" 
                            @change="applyTenantFilter"
                            class="h-9 px-3 bg-white border border-blue-200 rounded-xl text-xs font-semibold text-slate-800 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[220px]"
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

            <!-- ═══ 3-WAY HORIZONTAL SCROLLER NAVTABS ═══════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <!-- Chevron Panah Kiri -->
                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            @click="scrollNav(-220)"
                            title="Geser ke Kiri">
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <!-- Container Deretan Tab -->
                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsBukuInduk" role="tablist">
                            <li v-for="tab in mainTabs" :key="tab.id" class="nav-item">
                                <button type="button"
                                        class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center shrink-0" 
                                        :class="currentTab === tab.id ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'" 
                                        @click="switchTab(tab.id)">
                                    <i :class="['bi', tab.icon, 'me-2 text-sm']"></i> {{ tab.name }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Chevron Panah Kanan -->
                    <button type="button" 
                            class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                            @click="scrollNav(220)"
                            title="Geser ke Kanan">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- TAB 1: BUKU INDUK SISWA (STANDAR BAKU 3-BAGIAN AGENTS.MD)           -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-show="currentTab === 'buku_induk_siswa'">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    
                    <!-- [1. FILTER BAR ATAS] bg-white border-b border-slate-200/80 p-4 -->
                    <div class="p-4 bg-white border-b border-slate-200/80 space-y-3.5">
                        <form @submit.prevent="applyFilter(1)" class="space-y-3.5">
                            <!-- Baris 1: Parameter Filter (3 Kolom Tanpa Per-Page Sesuai Aturan) -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Jenjang Tingkat</label>
                                    <select v-model="filterJenjang" @change="onJenjangChange" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="">🎓 Semua Jenjang</option>
                                        <option v-for="j in jenjangList" :key="j.id" :value="j.id">{{ j.nama_jenjang }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Kelas / Rombel</label>
                                    <select v-model="filterKelas" @change="applyFilter(1)" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="">🏫 Semua Kelas</option>
                                        <option v-for="k in filteredKelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Status Siswa</label>
                                    <select v-model="filterStatus" @change="applyFilter(1)" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="">📋 Semua Status</option>
                                        <option value="Aktif">Aktif</option>
                                        <option value="Lulus">Lulus</option>
                                        <option value="Pindah">Pindah</option>
                                        <option value="Keluar">Keluar</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Baris 2: Pencarian & Tombol Aksi (100% Contained & Responsive) -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2.5">
                                <div class="grow">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Pencarian Siswa</label>
                                    <div class="relative">
                                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                        <input type="text" 
                                               v-model="search" 
                                               @input="debounceSearch" 
                                               placeholder="Cari nama lengkap, NISN, atau NIS siswa..." 
                                               class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs" />
                                        <button v-if="search" 
                                                type="button" 
                                                @click="search = ''; applyFilter(1)" 
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition" 
                                                title="Hapus pencarian">
                                            <i class="bi bi-x-circle-fill text-xs"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                                    <button type="submit" class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap">
                                        <i class="bi bi-search text-xs"></i> <span>Cari</span>
                                    </button>
                                    <button type="button" @click="resetFilter" class="h-9 px-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs transition shadow-2xs whitespace-nowrap">
                                        Reset
                                    </button>
                                    <button type="button" @click="exportExcel" class="h-9 px-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap" title="Ekspor Seluruh Siswa ke File Excel (.xlsx)">
                                        <i class="bi bi-file-earmark-excel-fill"></i> Ekspor Excel (.XLSX)
                                    </button>
                                    <button v-if="isKelas12Selected" type="button" @click="exportPdssExcel" class="h-9 px-3.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold text-xs shadow-2xs transition flex items-center gap-1.5 whitespace-nowrap" title="Ekspor Data Siswa PDSS SNBP (.xlsx)">
                                        <i class="bi bi-award-fill"></i> Ekspor PDSS (.XLSX)
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- [2. TABEL DATA] overflow-x-auto & Header Kolom Standar AGENTS.md -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs whitespace-nowrap min-w-[950px]">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-black uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="py-3.5 px-3 text-center w-10 whitespace-nowrap">No</th>
                                    <th v-if="isSuperAdmin" class="py-3.5 px-4 whitespace-nowrap">Sekolah / Tenant</th>
                                    <th class="py-3.5 px-4 whitespace-nowrap">NIS / NISN</th>
                                    <th class="py-3.5 px-4 whitespace-nowrap">Nama Lengkap Siswa</th>
                                    <th class="py-3.5 px-4 text-center whitespace-nowrap">L/P</th>
                                    <th class="py-3.5 px-4 whitespace-nowrap">Jurusan / Keahlian</th>
                                    <th class="py-3.5 px-4 whitespace-nowrap min-w-[100px]">Kelas / Rombel</th>
                                    <th class="py-3.5 px-4 text-center whitespace-nowrap">Status</th>
                                    <th class="py-3.5 px-4 text-center whitespace-nowrap" style="width: 170px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                                <tr v-for="(siswa, idx) in siswaList?.data || []" :key="siswa.id" class="hover:bg-blue-50/40 transition">
                                    <td class="py-3.5 px-3 text-center font-mono text-slate-400 w-10">
                                        {{ ((siswaList.current_page - 1) * siswaList.per_page) + idx + 1 }}
                                    </td>
                                    <td v-if="isSuperAdmin" class="py-3.5 px-4 font-bold text-slate-800 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0">
                                                <i class="bi bi-building"></i>
                                            </span>
                                            <span>{{ siswa.nama_sekolah }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-mono font-bold text-slate-900">NIS: {{ siswa.nis || '-' }}</div>
                                        <div class="font-mono text-[11px] text-slate-400">NISN: {{ siswa.nisn || '-' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-extrabold text-slate-900">{{ siswa.nama_lengkap }}</div>
                                        <div class="text-[11px] text-slate-400 font-medium">
                                            {{ siswa.tempat_lahir || '-' }}, {{ siswa.tanggal_lahir_formatted || siswa.tanggal_lahir || '-' }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-extrabold" :class="siswa.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'">
                                            {{ siswa.jenis_kelamin }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-800 whitespace-nowrap">{{ siswa.nama_jurusan }}</td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-slate-100 text-slate-800 border border-slate-200 whitespace-nowrap inline-flex items-center gap-1">
                                            <i class="bi bi-door-closed text-slate-400"></i> {{ siswa.nama_kelas }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold"
                                              :class="{
                                                  'bg-emerald-50 text-emerald-700 border border-emerald-200': siswa.status === 'Aktif',
                                                  'bg-blue-50 text-blue-700 border border-blue-200': siswa.status === 'Lulus',
                                                  'bg-amber-50 text-amber-700 border border-amber-200': siswa.status === 'Pindah',
                                                  'bg-rose-50 text-rose-700 border border-rose-200': siswa.status === 'Keluar' || siswa.status === 'Non-Aktif'
                                              }">
                                            <i class="bi bi-circle-fill text-[6px]"></i> {{ siswa.status }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="openDetailModal(siswa.id, 'profil_keluarga')" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Lihat Profil & Buku Induk Lengkap">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <a :href="`/buku-induk/cetak/${siswa.id}`" target="_blank" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Cetak Lembar Buku Induk Resmi (Lengkap)">
                                                <i class="bi bi-printer-fill"></i>
                                            </a>
                                            <button @click="openDetailModal(siswa.id, 'prestasi_beasiswa')" class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Prestasi & Riwayat Beasiswa">
                                                <i class="bi bi-gift-fill"></i>
                                            </button>
                                            <Link :href="`/siswa/${siswa.id}/edit`" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Data Siswa">
                                                <i class="bi bi-pencil-square"></i>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="(siswaList?.data || []).length === 0">
                                    <td :colspan="isSuperAdmin ? 9 : 8" class="text-center py-12 text-slate-400">
                                        <i class="bi bi-journal-x text-4xl mb-2 block text-slate-300"></i>
                                        <span class="font-medium">Tidak ada data siswa ditemukan di Buku Induk.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- [3. FOOTER PAGINATION SMART WINDOWING SESUAI AGENTS.MD] -->
                    <div v-if="siswaList?.total > 0" 
                         class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
                        <!-- Info Tampilkan Baris -->
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
                            <span>Tampilkan</span>
                            <select v-model="perPage" @change="applyFilter(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option :value="10">10</option>
                                <option :value="15">15</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                            <span class="whitespace-nowrap">baris per halaman</span>
                            <span class="text-slate-300 hidden sm:inline">|</span>
                            <span class="whitespace-nowrap">
                                Menampilkan <span class="font-bold text-slate-800">{{ siswaList.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ siswaList.to || siswaList.total }}</span> dari <span class="font-bold text-slate-800">{{ siswaList.total }}</span> baris
                            </span>
                        </div>

                        <!-- Pagination Links (Smart Windowing & Compact Chevrons) -->
                        <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
                            <template v-for="(link, i) in getSmartPaginationLinks(siswaList)" :key="i">
                                <button v-if="link.url && !link.active" 
                                        type="button"
                                        @click="goToPage(link.url)"
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

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- TAB 2: SETING KURIKULUM                                             -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-show="currentTab === 'seting_kurikulum'" class="space-y-4">
                <!-- Controls Card -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3.5">
                    <!-- Baris 1: Parameter Dropdown -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Tahun Ajaran</label>
                            <select v-model="kurikulumParams.tahun_ajaran" @change="loadKurikulum" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option v-for="t in tahunAjaranList" :key="t.id" :value="t.tahun_ajaran">{{ t.tahun_ajaran }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Semester</label>
                            <select v-model="kurikulumParams.semester" @change="loadKurikulum" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                                <option value="Ujian Sekolah">Ujian Sekolah</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Kelas Fisik</label>
                            <select v-model="kurikulumParams.kelas_id" @change="loadKurikulum" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option v-for="k in kelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Kurikulum Aktif</label>
                            <select v-model="kurikulumParams.kurikulum_id" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option v-for="c in kurikulumList" :key="c.id" :value="c.id">{{ c.nama_kurikulum }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Baris 2: Tombol Aksi -->
                    <div class="flex flex-wrap items-center justify-between gap-2.5 pt-1 border-t border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 font-medium">Status Kunci:</span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold" :class="isKurikulumLocked ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                                <i :class="isKurikulumLocked ? 'bi bi-lock-fill' : 'bi bi-unlock-fill'"></i> {{ isKurikulumLocked ? 'Terkunci' : 'Terbuka' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="toggleKurikulumLock" class="h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                                <i :class="isKurikulumLocked ? 'bi bi-unlock' : 'bi bi-lock'"></i> {{ isKurikulumLocked ? 'Buka Kunci' : 'Kunci Kurikulum' }}
                            </button>
                            <button type="button" @click="isCopyModalOpen = true" class="h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-blue-600 font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                                <i class="bi bi-files"></i> Salin Kurikulum
                            </button>
                            <button type="button" @click="saveKurikulumSetting" :disabled="isSavingKurikulum || isKurikulumLocked" class="h-9 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                                <i class="bi bi-floppy"></i> {{ isSavingKurikulum ? 'Menyimpan...' : 'Simpan Pemetaan' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Group Builder Header -->
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-grid-fill text-blue-600"></i>
                        Kelompok & Pemetaan Mata Pelajaran
                    </h2>
                    <button @click="addKurikulumGroup" class="btn btn-sm btn-light border border-slate-200 text-blue-600 rounded-xl px-3 py-1.5 text-xs font-bold hover:bg-blue-50 shadow-2xs">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Kelompok
                    </button>
                </div>

                <!-- Groups Grid -->
                <div v-if="loadingKurikulum" class="text-center py-12">
                    <div class="spinner-border spinner-border-sm text-blue-600" role="status"></div>
                    <p class="text-xs text-slate-500 mt-2">Memuat pemetaan kurikulum...</p>
                </div>
                <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div v-for="(grp, gIdx) in kurikulumGroups" :key="gIdx" class="bg-white rounded-2xl border-t-4 border-t-blue-600 border border-slate-200/80 p-4 shadow-2xs">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <input v-model="grp.kelompok_id" type="text" placeholder="Nama Kelompok (cth: Kelompok A)" class="form-control form-control-sm text-xs font-extrabold text-slate-900 rounded-xl border-slate-200 w-full" />
                            <button @click="removeKurikulumGroup(gIdx)" class="btn btn-xs btn-outline-rose border-rose-200 text-rose-600 hover:bg-rose-50 rounded-lg p-1.5" title="Hapus Kelompok">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                        <!-- Search Mapel -->
                        <div class="mb-2.5">
                            <input v-model="grp.searchQuery" type="text" placeholder="Cari mata pelajaran..." class="form-control form-control-sm text-xs rounded-xl border-slate-200 w-full" />
                        </div>

                        <!-- Subjects Checklist -->
                        <div class="max-h-60 overflow-y-auto border border-slate-100 rounded-xl p-2 space-y-1 bg-slate-50/50">
                            <label v-for="m in (bankMapel || []).filter(item => !grp.searchQuery || item.nama_mapel.toLowerCase().includes(grp.searchQuery.toLowerCase()))" :key="m.id" class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition text-xs" :class="grp.mapel_ids.includes(m.id) ? 'bg-blue-50 text-blue-900 font-bold' : 'hover:bg-slate-100 text-slate-700'">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" :value="m.id" v-model="grp.mapel_ids" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                    <span>{{ m.nama_mapel }}</span>
                                </div>
                                <span class="text-[10px] font-mono text-slate-400">{{ m.kode_mapel }}</span>
                            </label>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <span>Mata pelajaran terpilih:</span>
                            <span class="badge bg-blue-600 text-white rounded-pill px-2.5 py-1 font-bold">{{ grp.mapel_ids.length }} Mapel</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- TAB 3: INPUT NILAI RAPOR                                            -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-show="currentTab === 'input_nilai_rapor'" class="space-y-4">
                <!-- Controls Card -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3.5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Tahun Ajaran</label>
                            <select v-model="nilaiParams.tahun_ajaran" @change="loadNilaiRapor" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option v-for="t in tahunAjaranList" :key="t.id" :value="t.tahun_ajaran">{{ t.tahun_ajaran }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Semester</label>
                            <select v-model="nilaiParams.semester" @change="loadNilaiRapor" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                                <option value="Ujian Sekolah">Ujian Sekolah</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Kelas Fisik</label>
                            <select v-model="nilaiParams.kelas_id" @change="loadNilaiRapor" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option v-for="k in kelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2.5 pt-1 border-t border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 font-medium">Status Kunci Nilai:</span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold" :class="isNilaiLocked ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                                <i :class="isNilaiLocked ? 'bi bi-lock-fill' : 'bi bi-unlock-fill'"></i> {{ isNilaiLocked ? 'Terkunci' : 'Terbuka' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="downloadNilaiTemplate" :disabled="!nilaiParams.kelas_id" class="h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs transition flex items-center gap-1.5 disabled:opacity-50" title="Unduh Format Template Nilai Sesuai Kurikulum (.xlsx)">
                                <i class="bi bi-file-earmark-arrow-down-fill text-emerald-600"></i> Unduh Format Nilai (.XLSX)
                            </button>
                            <button type="button" @click="isImportNilaiOpen = true" :disabled="isNilaiLocked || !nilaiParams.kelas_id" class="h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs transition flex items-center gap-1.5 disabled:opacity-50" title="Impor Nilai Siswa Massal dari File Excel (.xlsx)">
                                <i class="bi bi-file-earmark-arrow-up-fill text-blue-600"></i> Impor Nilai (.XLSX)
                            </button>
                            <button type="button" @click="toggleNilaiLock" class="h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                                <i :class="isNilaiLocked ? 'bi bi-unlock' : 'bi bi-lock'"></i> {{ isNilaiLocked ? 'Buka Kunci' : 'Kunci Nilai' }}
                            </button>
                            <button type="button" @click="loadNilaiRapor" class="h-9 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                                <i class="bi bi-arrow-repeat"></i> Muat Matriks Nilai
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Matriks Table Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="p-3.5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                        <div>
                            <h3 class="text-xs font-extrabold text-slate-900">Matriks Nilai Akhir Rapor Siswa</h3>
                            <p class="text-[11px] text-slate-500">Kurikulum Aktif: {{ activeKurikulumName }} (Rentang Nilai 0 - 100)</p>
                        </div>
                        <div class="w-full sm:w-64">
                            <input v-model="nilaiSearchQuery" type="text" placeholder="Cari nama / NISN..." class="w-full h-8 px-3 rounded-xl border border-slate-200 bg-white text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                        </div>
                    </div>

                    <div v-if="loadingNilai" class="text-center py-12">
                        <div class="spinner-border spinner-border-sm text-blue-600" role="status"></div>
                        <p class="text-xs text-slate-500 mt-2">Memuat matriks nilai...</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs whitespace-nowrap min-w-[750px]">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-black uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="py-3.5 px-3 text-center w-10">No</th>
                                    <th class="py-3.5 px-4">NISN / NIS</th>
                                    <th class="py-3.5 px-4">Nama Lengkap Siswa</th>
                                    <th class="py-3.5 px-4 text-center">Tahun Ajaran</th>
                                    <th class="py-3.5 px-4 text-center">Semester</th>
                                    <th class="py-3.5 px-4 text-center">Rata-Rata Nilai</th>
                                    <th class="py-3.5 px-4 text-center" style="width: 140px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                                <tr v-for="(stu, sIdx) in filteredNilaiStudents" :key="stu.id" class="hover:bg-blue-50/40 transition">
                                    <td class="py-3.5 px-3 text-center font-mono text-slate-400 w-10">{{ sIdx + 1 }}</td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ stu.nisn || stu.nis || '-' }}</td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900">{{ stu.nama_lengkap }}</td>
                                    <td class="py-3.5 px-4 text-center text-slate-500">{{ nilaiParams.tahun_ajaran }}</td>
                                    <td class="py-3.5 px-4 text-center text-slate-500">{{ nilaiParams.semester }}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-2xs font-extrabold" :class="stu.average !== '-' && parseFloat(stu.average) >= 75 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                                            {{ stu.average }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="openEditNilaiModal(stu)" :disabled="isNilaiLocked" class="btn btn-xs btn-primary rounded-lg px-2.5 py-1 text-[11px] font-bold shadow-2xs" title="Input / Edit Detail Nilai">
                                                <i class="bi bi-pencil-square me-1"></i> Input Nilai
                                            </button>
                                            <button @click="deleteStudentGrades(stu)" :disabled="isNilaiLocked" class="btn btn-xs btn-outline-rose border-rose-200 text-rose-600 rounded-lg px-2 py-1 text-[11px]" title="Hapus Nilai Siswa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredNilaiStudents.length === 0">
                                    <td colspan="7" class="text-center py-10 text-slate-400">
                                        <i class="bi bi-inbox text-3xl mb-1.5 block text-slate-300"></i>
                                        Tidak ada siswa terdaftar pada kelas ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- TAB 4: CETAK BUKU INDUK                                             -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-show="currentTab === 'cetak_buku_induk'" class="space-y-4">
                <!-- Controls Card -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3.5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Filter Kelas</label>
                            <select v-model="cetakParams.kelas_id" @change="loadMatrixCetak" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option value="">-- Semua Kelas --</option>
                                <option v-for="k in kelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Status Siswa</label>
                            <select v-model="cetakParams.status" @change="loadMatrixCetak" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option value="">-- Semua Status --</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Lulus">Lulus</option>
                                <option value="Pindah">Pindah</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 pt-1 border-t border-slate-100">
                        <a :href="`/cetak-rapot-kelas?kelas_id=${cetakParams.kelas_id}&semester=Ganjil`" target="_blank" class="h-9 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-blue-600 font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                            <i class="bi bi-printer"></i> Rapor Massal Rombel
                        </a>
                        <button type="button" @click="loadMatrixCetak" class="h-9 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                            <i class="bi bi-arrow-repeat"></i> Tampilkan Matriks
                        </button>
                    </div>
                </div>

                <!-- Matrix Multi-Tahun Table Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div v-if="loadingMatrix" class="text-center py-12">
                        <div class="spinner-border spinner-border-sm text-blue-600" role="status"></div>
                        <p class="text-xs text-slate-500 mt-2">Memuat matriks cetak buku induk...</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="table table-bordered table-hover align-middle mb-0 text-xs whitespace-nowrap min-w-[950px]">
                            <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px] text-center border-b border-slate-200">
                                <tr>
                                    <th rowspan="2" class="align-middle py-3 px-3 w-10">No</th>
                                    <th rowspan="2" class="align-middle py-3 px-3">NISN</th>
                                    <th rowspan="2" class="align-middle py-3 px-3 text-start">Nama Lengkap Siswa</th>
                                    <th rowspan="2" class="align-middle py-3 px-3">Tahun Masuk</th>
                                    <th rowspan="2" class="align-middle py-3 px-3">Lembar Buku Induk</th>
                                    <th rowspan="2" class="align-middle py-3 px-3">Transkrip Nilai</th>
                                    <th v-for="n in matrixMaxYears" :key="'header-year-' + n" colspan="3" class="bg-blue-600 text-white font-bold py-2 border-l border-blue-500">
                                        Tahun Ke-{{ n }}
                                    </th>
                                </tr>
                                <tr>
                                    <template v-for="n in matrixMaxYears" :key="'sub-year-' + n">
                                        <th class="bg-slate-100 text-slate-600 border-l border-slate-200 py-1.5 px-2">Kelas</th>
                                        <th class="bg-slate-100 text-slate-600 py-1.5 px-2">Semester 1</th>
                                        <th class="bg-slate-100 text-slate-600 py-1.5 px-2">Semester 2</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                                <tr v-for="(stu, idx) in matrixData" :key="stu.id" class="hover:bg-blue-50/40 transition">
                                    <td class="py-2.5 px-3 text-center font-mono text-slate-400 w-10">{{ idx + 1 }}</td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900 text-center">{{ stu.nisn || '-' }}</td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900">{{ stu.nama_lengkap }}</td>
                                    <td class="py-2.5 px-3 text-center text-slate-500">{{ stu.tahun_masuk || '-' }}</td>
                                    <td class="py-2.5 px-3 text-center">
                                        <a :href="`/buku-induk/cetak/${stu.id}`" target="_blank" class="btn btn-xs btn-outline-slate border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg px-2.5 py-1 text-[11px] font-bold">
                                            <i class="bi bi-printer me-1"></i> Cetak Buku Induk
                                        </a>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <a :href="`/cetak-rapot?id=${stu.id}&format=transkrip`" target="_blank" class="btn btn-xs btn-emerald bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg px-2.5 py-1 text-[11px] font-bold shadow-2xs">
                                            <i class="bi bi-award me-1"></i> Transkrip
                                        </a>
                                    </td>
                                    <template v-for="n in matrixMaxYears" :key="'data-year-' + n">
                                        <td class="py-2.5 px-3 text-center font-bold text-blue-700 bg-slate-50/50 border-l border-slate-200">
                                            {{ stu.years?.[n-1]?.nama_kelas || '-' }}
                                        </td>
                                        <td class="py-2.5 px-2 text-center">
                                            <a :href="`/cetak-rapot?id=${stu.id}&semester=Ganjil&ta=${encodeURIComponent(stu.years?.[n-1]?.tahun_ajaran || '')}`" target="_blank" class="btn btn-xs btn-primary rounded-lg px-2 py-1 text-[11px]" title="Cetak Rapor Semester Ganjil">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                        <td class="py-2.5 px-2 text-center">
                                            <a :href="`/cetak-rapot?id=${stu.id}&semester=Genap&ta=${encodeURIComponent(stu.years?.[n-1]?.tahun_ajaran || '')}`" target="_blank" class="btn btn-xs btn-primary rounded-lg px-2 py-1 text-[11px]" title="Cetak Rapor Semester Genap">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </template>
                                </tr>
                                <tr v-if="matrixData.length === 0">
                                    <td :colspan="6 + (matrixMaxYears * 3)" class="text-center py-10 text-slate-400">
                                        Tidak ada data siswa ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- TAB 5: RIWAYAT KEPALA SEKOLAH                                       -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-show="currentTab === 'riwayat_kepsek'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="p-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                <i class="bi bi-clock-history text-blue-600"></i>
                                Riwayat Kepala Sekolah Lintas Periode
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Mencatat nama pejabat kepala sekolah definitif dan pelaksana tugas (Plt/Pjs).</p>
                        </div>
                        <button @click="openTambahKepsek" class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 self-start sm:self-auto">
                            <i class="bi bi-plus-lg"></i> Tambah Riwayat
                        </button>
                    </div>

                    <div v-if="loadingKepsek" class="text-center py-12">
                        <div class="spinner-border spinner-border-sm text-blue-600" role="status"></div>
                        <p class="text-xs text-slate-500 mt-2">Memuat riwayat kepala sekolah...</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs whitespace-nowrap min-w-[700px]">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-black uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="py-3.5 px-3 text-center w-10">No</th>
                                    <th class="py-3.5 px-4">Sekolah</th>
                                    <th class="py-3.5 px-4">Nama Kepala Sekolah</th>
                                    <th class="py-3.5 px-4">NIP</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-center">Tanggal Mulai</th>
                                    <th class="py-3.5 px-4 text-center">Tanggal Selesai</th>
                                    <th class="py-3.5 px-4 text-center" style="width: 100px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                                <tr v-for="(k, kIdx) in riwayatKepsekList" :key="k.id" class="hover:bg-blue-50/40 transition">
                                    <td class="py-3.5 px-3 text-center font-mono text-slate-400 w-10">{{ kIdx + 1 }}</td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-600">{{ k.nama_sekolah || '-' }}</td>
                                    <td class="py-3.5 px-4 font-extrabold text-slate-900">{{ k.nama_kepsek }}</td>
                                    <td class="py-3.5 px-4 font-mono text-slate-500">{{ k.nip_kepsek || '-' }}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-extrabold" :class="k.status_plt == 1 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                                            {{ k.status_plt == 1 ? 'Plt / Pjs' : 'Definitif' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-slate-600">{{ k.tanggal_mulai }}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span v-if="k.tanggal_selesai" class="text-slate-600">{{ k.tanggal_selesai }}</span>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-extrabold bg-blue-100 text-blue-800">Masih Menjabat</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="openEditKepsek(k)" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button @click="deleteKepsek(k.id)" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="riwayatKepsekList.length === 0">
                                    <td colspan="8" class="text-center py-10 text-slate-400">
                                        Belum ada riwayat kepala sekolah tercatat.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- TAB 6: ARSIP ALUMNI                                                 -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-show="currentTab === 'arsip_alumni'" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <!-- Panel Kiri: Direktori Pencarian Alumni -->
                    <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/90 p-4 shadow-2xs flex flex-col gap-3.5">
                        <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                            <h3 class="text-xs font-extrabold text-slate-900 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs shadow-2xs">
                                    <i class="bi bi-person-badge-fill"></i>
                                </span>
                                Direktori Alumni
                            </h3>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-2xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                {{ alumniTotal }} Alumni
                            </span>
                        </div>

                        <div class="relative">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input v-model="alumniSearch" 
                                   @input="loadAlumni(1)" 
                                   type="text" 
                                   placeholder="Cari nama lengkap atau NISN..." 
                                   class="w-full h-9 pl-8 pr-8 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs" />
                            <button v-if="alumniSearch" 
                                    type="button" 
                                    @click="alumniSearch = ''; loadAlumni(1)" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition" 
                                    title="Hapus pencarian">
                                <i class="bi bi-x-circle-fill text-xs"></i>
                            </button>
                        </div>

                        <div v-if="loadingAlumni" class="text-center py-12">
                            <div class="spinner-border spinner-border-sm text-blue-600" role="status"></div>
                            <p class="text-xs text-slate-400 mt-2 font-medium">Memuat direktori alumni...</p>
                        </div>
                        <div v-else class="space-y-1.5 max-h-[520px] overflow-y-auto pr-1 no-scrollbar">
                            <button v-for="a in alumniList" 
                                    :key="a.id" 
                                    @click="selectAlumni(a)" 
                                    type="button" 
                                    class="w-full text-start p-3 rounded-2xl border transition flex items-center justify-between gap-3 group"
                                    :class="selectedAlumni?.id === a.id 
                                        ? 'bg-gradient-to-r from-blue-50/90 to-indigo-50/50 border-blue-300 shadow-xs ring-1 ring-blue-400/30' 
                                        : 'bg-slate-50/40 hover:bg-blue-50/40 border-slate-200/70 hover:border-slate-300'">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl font-black text-xs flex items-center justify-center shrink-0 shadow-2xs transition"
                                         :class="selectedAlumni?.id === a.id ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700 group-hover:bg-blue-100 group-hover:text-blue-700'">
                                        {{ a.nama_lengkap.charAt(0) }}
                                    </div>
                                    <div class="truncate">
                                        <div class="font-extrabold text-xs text-slate-900 truncate">{{ a.nama_lengkap }}</div>
                                        <div class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5 mt-0.5">
                                            <span class="font-mono text-slate-600">NISN: {{ a.nisn || '-' }}</span>
                                            <span>•</span>
                                            <span class="text-blue-700 font-bold">{{ a.nama_kelas || 'Alumni' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-xs shrink-0 transition"
                                   :class="selectedAlumni?.id === a.id ? 'text-blue-600 font-bold translate-x-0.5' : 'text-slate-400 group-hover:text-slate-600'"></i>
                            </button>
                            <div v-if="alumniList.length === 0" class="text-center py-12 text-slate-400 text-xs bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                                <i class="bi bi-people text-2xl text-slate-300 block mb-1"></i>
                                Tidak ada data alumni terdaftar.
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="alumniTotalPages > 1" class="flex justify-center items-center gap-1 pt-2 border-t border-slate-100">
                            <button v-for="p in alumniTotalPages" 
                                    :key="p" 
                                    @click="loadAlumni(p)" 
                                    class="h-7 min-w-[28px] px-2 rounded-lg text-xs font-bold transition flex items-center justify-center" 
                                    :class="alumniPage === p ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200'">
                                {{ p }}
                            </button>
                        </div>
                    </div>

                    <!-- Panel Kanan: Brankas Berkas Digital -->
                    <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-5 shadow-2xs">
                        <div v-if="!selectedAlumni" class="py-20 text-center text-slate-400 space-y-3">
                            <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-600 mx-auto flex items-center justify-center text-3xl shadow-xs border border-blue-200/80">
                                <i class="bi bi-safe2"></i>
                            </div>
                            <h4 class="text-sm font-extrabold text-slate-800">Brankas Berkas Digital Alumni</h4>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                                Pilih salah satu alumni di panel kiri untuk membuka brankas dokumen resmi, mengarsipkan Ijazah, SKHUN, Transkrip, atau Sertifikat digital.
                            </p>
                        </div>
                        <div v-else class="space-y-4">
                            <!-- 1. Header Alumni Terpilih -->
                            <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 p-4 rounded-3xl shadow-xs text-white flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md text-white font-black text-base border border-white/30 flex items-center justify-center shadow-inner">
                                        {{ selectedAlumni.nama_lengkap.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-black tracking-tight text-white">{{ selectedAlumni.nama_lengkap }}</div>
                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-2xs font-extrabold bg-white/20 text-white border border-white/30 backdrop-blur-xs">
                                                <i class="bi bi-mortarboard-fill"></i> Alumni Terverifikasi
                                            </span>
                                            <span class="text-xs text-blue-100 font-mono font-bold">
                                                NISN: {{ selectedAlumni.nisn || '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button @click="selectedAlumni = null" 
                                        type="button" 
                                        class="h-8 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-bold transition flex items-center gap-1 shadow-2xs backdrop-blur-xs">
                                    <i class="bi bi-x-lg"></i> Tutup Brankas
                                </button>
                            </div>

                            <!-- 2. Formulir Unggah Berkas Baru -->
                            <div class="bg-gradient-to-r from-slate-50 via-blue-50/20 to-slate-50 p-4 sm:p-5 rounded-3xl border border-slate-200/90 shadow-2xs space-y-4">
                                <div class="flex items-center justify-between border-b border-slate-200/70 pb-2.5">
                                    <div class="text-xs font-extrabold text-slate-900 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs shadow-2xs">
                                            <i class="bi bi-cloud-arrow-up-fill"></i>
                                        </span>
                                        Arsipkan Dokumen Digital Baru
                                    </div>
                                    <span class="text-[11px] text-slate-400 font-medium">Format: PDF, JPG, PNG (Maks 10 MB)</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Jenis Dokumen *</label>
                                        <select v-model="alumniUploadForm.jenis_dokumen" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs">
                                            <option value="Ijazah">📜 Ijazah Kelulusan Resmi</option>
                                            <option value="SKHUN">📄 SKHUN / Transkrip Nilai</option>
                                            <option value="Buku Induk">📖 Lembar Buku Induk Lengkap</option>
                                            <option value="Sertifikat/SKL">🎖️ Sertifikat / Surat Keterangan Lulus (SKL)</option>
                                            <option value="Lainnya">📁 Berkas Dokumen Lainnya</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Keterangan / Catatan Dokumen</label>
                                        <input v-model="alumniUploadForm.keterangan" 
                                               type="text" 
                                               placeholder="cth: Ijazah asli depan & belakang..." 
                                               class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Pilih Berkas Digital *</label>
                                    <input @change="onAlumniFileChange" 
                                           type="file" 
                                           accept=".pdf,.jpg,.jpeg,.png" 
                                           class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-2xl p-1.5 bg-white cursor-pointer transition shadow-2xs" />
                                    
                                    <div v-if="alumniUploadForm.berkas" class="mt-2 p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-bold text-emerald-800 flex items-center justify-between">
                                        <div class="flex items-center gap-2 truncate">
                                            <i class="bi bi-file-earmark-check-fill text-emerald-600 text-sm"></i>
                                            <span class="truncate">{{ alumniUploadForm.berkas.name }}</span>
                                        </div>
                                        <span class="text-[11px] text-emerald-600 font-mono font-normal shrink-0">
                                            ({{ (alumniUploadForm.berkas.size / 1024).toFixed(1) }} KB)
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-[11px] text-slate-400">Tersimpan di Brankas Digital Terisolasi Multi-Tenant</span>
                                    <button @click="uploadAlumniDoc" 
                                            :disabled="isUploadingAlumni || !alumniUploadForm.berkas" 
                                            type="button" 
                                            class="h-9 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 disabled:opacity-50">
                                        <span v-if="isUploadingAlumni" class="spinner-border spinner-border-sm" role="status"></span>
                                        <i v-else class="bi bi-cloud-arrow-up-fill"></i>
                                        {{ isUploadingAlumni ? 'Mengunggah...' : 'Unggah ke Brankas' }}
                                    </button>
                                </div>
                            </div>

                            <!-- 3. Daftar Dokumen Tersimpan -->
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                                    <h4 class="text-xs font-extrabold text-slate-900 flex items-center gap-2">
                                        <i class="bi bi-safe-fill text-amber-500"></i>
                                        Berkas Tersimpan di Brankas
                                    </h4>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-extrabold bg-slate-100 text-slate-700">
                                        {{ (selectedAlumni.dokumen || []).length }} Berkas
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <div v-for="doc in selectedAlumni.dokumen || []" 
                                         :key="doc.id" 
                                         class="p-3.5 bg-white border border-slate-200/90 hover:border-blue-300 hover:shadow-xs rounded-2xl transition flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-2xs"
                                                 :class="(doc.nama_file || doc.url_file || '').toLowerCase().endsWith('.pdf') ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-blue-50 text-blue-600 border border-blue-200'">
                                                <i :class="(doc.nama_file || doc.url_file || '').toLowerCase().endsWith('.pdf') ? 'bi bi-file-earmark-pdf-fill' : 'bi bi-file-earmark-image-fill'" class="text-lg"></i>
                                            </div>
                                            <div class="truncate">
                                                <div class="font-extrabold text-xs text-slate-900 truncate">{{ doc.jenis_dokumen }}</div>
                                                <div class="text-[11px] text-slate-500 truncate mt-0.5">
                                                    {{ doc.keterangan || doc.nama_file || 'Berkas Dokumen Digital' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <button @click="openPdfViewer(doc.url_file, doc.jenis_dokumen)" 
                                                    type="button" 
                                                    class="btn btn-xs bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl px-3 py-1.5 text-xs font-bold shadow-2xs transition flex items-center gap-1">
                                                <i class="bi bi-eye-fill"></i> Preview
                                            </button>
                                            <button @click="deleteAlumniDoc(doc.id)" 
                                                    type="button" 
                                                    class="w-7 h-7 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 transition flex items-center justify-center shadow-2xs" 
                                                    title="Hapus Dokumen dari Brankas">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="(selectedAlumni.dokumen || []).length === 0" class="text-center py-10 text-slate-400 text-xs bg-slate-50/50 rounded-3xl border-2 border-dashed border-slate-200/90 space-y-2">
                                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 mx-auto flex items-center justify-center text-2xl border border-amber-200/60">
                                            <i class="bi bi-folder2-open"></i>
                                        </div>
                                        <div class="font-bold text-slate-700">Brankas Dokumen Masih Kosong</div>
                                        <p class="text-[11px] text-slate-400 max-w-xs mx-auto">Belum ada dokumen yang diarsipkan untuk alumni ini. Gunakan formulir di atas untuk mengunggah berkas ijazah atau transkrip.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- MODAL DETAIL SISWA (9 SUB-TAB)                                      -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-if="isDetailOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xl w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden animate-fade-in">
                    
                    <!-- 1. Modal Header (Modern Profile Card Header) -->
                    <div class="p-4 sm:p-5 border-b border-slate-200/80 bg-gradient-to-r from-slate-50 via-white to-blue-50/30 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <!-- Avatar / Photo Thumbnail -->
                            <div class="relative shrink-0">
                                <img v-if="activeStudent?.foto_url" :src="activeStudent.foto_url" alt="Foto Siswa" class="w-12 h-12 rounded-2xl object-cover border-2 border-white shadow-xs" />
                                <div v-else class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black flex items-center justify-center text-lg shadow-xs border-2 border-white">
                                    <i :class="activeStudent?.jenis_kelamin === 'P' ? 'bi bi-person-heart' : 'bi bi-person-fill'"></i>
                                </div>
                                <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white flex items-center justify-center text-[8px]" :class="activeStudent?.is_active ? 'bg-emerald-500 text-white' : 'bg-slate-400 text-white'">
                                    <i class="bi bi-check" v-if="activeStudent?.is_active"></i>
                                </span>
                            </div>

                            <!-- Student Info & Meta Badges -->
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-black text-slate-900 truncate tracking-tight">
                                        {{ activeStudent?.nama_lengkap || 'Profil Lengkap Siswa' }}
                                    </h3>
                                    <span v-if="activeStudent?.status_siswa" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-extrabold border" :class="activeStudent.status_siswa === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="activeStudent.status_siswa === 'Aktif' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        {{ activeStudent.status_siswa }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-2 mt-1 text-xs text-slate-500 flex-wrap font-medium">
                                    <span class="font-mono text-slate-700 font-bold bg-slate-100 px-2 py-0.5 rounded-md text-[11px]">
                                        NISN: {{ activeStudent?.nisn || '-' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200/60 font-bold px-2 py-0.5 rounded-md text-[11px]">
                                        <i class="bi bi-door-open-fill text-[10px]"></i> {{ activeStudent?.nama_kelas || '-' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 bg-slate-50 text-slate-600 border border-slate-200 px-2 py-0.5 rounded-md text-[11px]">
                                        <i class="bi bi-building text-[10px]"></i> {{ activeStudent?.nama_sekolah || '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Close Button -->
                        <button type="button" @click="isDetailOpen = false" class="w-8 h-8 rounded-xl border border-slate-200/80 bg-white text-slate-500 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 flex items-center justify-center transition shadow-2xs shrink-0" title="Tutup Modal">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>

                    <!-- 2. Standard 3-Way Horizontal Scroller NavTabs Sub-Modal -->
                    <div class="bg-slate-50/70 border-b border-slate-200/80 px-2 sm:px-4 py-2 relative">
                        <div class="flex items-center relative">
                            <!-- Tombol Panah Kiri -->
                            <button type="button" 
                                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[30px] h-[30px] z-5" 
                                    onclick="document.getElementById('navTabsDetailSiswa')?.scrollBy({ left: -220, behavior: 'smooth' })"
                                    title="Geser ke Kiri">
                                <i class="bi bi-chevron-left text-xs"></i>
                            </button>

                            <!-- Container Deretan Tab -->
                            <div class="nav-tabs-wrapper grow overflow-hidden relative">
                                <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsDetailSiswa" role="tablist">
                                    <li v-for="st in subTabs" :key="st.id" class="nav-item">
                                        <button type="button"
                                                class="border-0 font-bold px-3 py-1.5 rounded-xl text-xs transition flex items-center shrink-0" 
                                                :class="detailActiveSubTab === st.id ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-white hover:text-slate-900'" 
                                                @click="detailActiveSubTab = st.id">
                                            <i :class="['bi', st.icon, 'me-1.5 text-xs']"></i> {{ st.name }}
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Tombol Panah Kanan -->
                            <button type="button" 
                                    class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[30px] h-[30px] z-5" 
                                    onclick="document.getElementById('navTabsDetailSiswa')?.scrollBy({ left: 220, behavior: 'smooth' })"
                                    title="Geser ke Kanan">
                                <i class="bi bi-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 3. Modal Body Content -->
                    <div class="p-4 sm:p-5 overflow-y-auto grow space-y-4 text-xs">
                        <!-- Loading State -->
                        <div v-if="detailLoading" class="text-center py-16">
                            <div class="spinner-border spinner-border-sm text-blue-600" role="status"></div>
                            <p class="text-xs font-semibold text-slate-500 mt-2">Memuat rincian lengkap profil siswa...</p>
                        </div>

                        <!-- Data Loaded -->
                        <div v-else-if="activeStudent">
                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <!-- TAB 1: PROFIL PRIBADI & KELUARGA (GABUNGAN IDENTITAS + ORTU)      -->
                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <div v-show="detailActiveSubTab === 'profil_keluarga'" class="space-y-4">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Kolom Kiri: Biodata Siswa & Domisili -->
                                    <div class="space-y-4">
                                        <!-- Card Data Pribadi -->
                                        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-4 space-y-3">
                                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                                <h4 class="font-black text-slate-900 flex items-center gap-2 text-xs uppercase tracking-wider">
                                                    <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold">
                                                        <i class="bi bi-person-vcard-fill"></i>
                                                    </span>
                                                    Data Pribadi Siswa
                                                </h4>
                                                <span class="text-[10px] font-bold text-slate-400 font-mono">ID: {{ activeStudent.id?.substring(0, 8) }}</span>
                                            </div>

                                            <dl class="divide-y divide-slate-100 text-xs">
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Nama Lengkap</dt><dd class="font-extrabold text-slate-900 text-right">{{ activeStudent.nama_lengkap }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Nama Panggilan</dt><dd class="font-bold text-slate-800 text-right">{{ activeStudent.nama_panggilan || '-' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">NISN / NIS</dt><dd class="font-mono font-bold text-slate-900 text-right">{{ activeStudent.nisn || '-' }} / {{ activeStudent.nis || '-' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">NIK Siswa</dt><dd class="font-mono font-bold text-slate-800 text-right">{{ activeStudent.nik || '-' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">No. Kartu Keluarga (KK)</dt><dd class="font-mono text-slate-800 text-right">{{ activeStudent.no_kk || '-' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Jenis Kelamin</dt><dd class="font-bold text-right" :class="activeStudent.jenis_kelamin === 'L' ? 'text-blue-700' : 'text-rose-700'">{{ activeStudent.jenis_kelamin === 'L' ? 'Laki-laki (L)' : 'Perempuan (P)' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Tempat, Tgl Lahir</dt><dd class="font-bold text-slate-900 text-right">{{ activeStudent.tempat_lahir || '-' }}, {{ formatIndoDate(activeStudent.tanggal_lahir) }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Agama / Kewarganegaraan</dt><dd class="font-semibold text-slate-800 text-right">{{ activeStudent.agama || '-' }} / {{ activeStudent.kewarganegaraan || 'WNI' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Bahasa Sehari-hari</dt><dd class="font-semibold text-slate-800 text-right">{{ activeStudent.bahasa_sehari_hari || 'Indonesia' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Anak Ke- / Saudara</dt><dd class="font-bold text-slate-800 text-right">Anak ke-{{ activeStudent.anak_ke || '1' }} dari {{ activeStudent.jumlah_saudara || '1' }} bersaudara</dd></div>
                                            </dl>
                                        </div>

                                        <!-- Card Alamat Domisili -->
                                        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-4 space-y-3">
                                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                                <h4 class="font-black text-slate-900 flex items-center gap-2 text-xs uppercase tracking-wider">
                                                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-bold">
                                                        <i class="bi bi-geo-alt-fill"></i>
                                                    </span>
                                                    Alamat & Wilayah Domisili
                                                </h4>
                                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">KK / Domisili</span>
                                            </div>

                                            <dl class="divide-y divide-slate-100 text-xs">
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Alamat Lengkap</dt><dd class="font-extrabold text-slate-900 text-right max-w-[60%]">{{ activeStudent.alamat || '-' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">RT / RW</dt><dd class="font-mono font-bold text-slate-800 text-right">RT {{ activeStudent.rt || '001' }} / RW {{ activeStudent.rw || '001' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Kelurahan / Desa</dt><dd class="font-bold text-slate-800 text-right">{{ activeStudent.nama_kelurahan || '-' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Kecamatan / Kab / Kota</dt><dd class="font-bold text-slate-800 text-right">{{ activeStudent.nama_kecamatan || '-' }}, {{ activeStudent.nama_kota || '-' }}</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Provinsi & Kode Pos</dt><dd class="font-bold text-slate-800 text-right">{{ activeStudent.nama_provinsi || '-' }} ({{ activeStudent.kode_pos || '-' }})</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Status Tempat Tinggal</dt><dd class="font-semibold text-slate-800 text-right">{{ activeStudent.status_tinggal || 'Rumah Sendiri' }} (Tinggal dengan: {{ activeStudent.tinggal_dengan || 'Orang Tua' }})</dd></div>
                                                <div class="flex justify-between py-2"><dt class="text-slate-500">Transportasi / Jarak</dt><dd class="font-semibold text-slate-800 text-right">{{ activeStudent.transportasi || 'Jalan Kaki' }} ({{ activeStudent.jarak_rumah || '< 1' }} km)</dd></div>
                                            </dl>
                                        </div>
                                    </div>

                                    <!-- Kolom Kanan: Orang Tua & Wali Siswa -->
                                    <div class="space-y-4">
                                        <div v-for="rel in ['Ayah', 'Ibu', 'Wali']" :key="rel" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-4 space-y-3">
                                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                                <h4 class="font-black text-slate-900 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                                                    <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold" :class="rel === 'Ayah' ? 'bg-blue-50 text-blue-600' : (rel === 'Ibu' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600')">
                                                        <i class="bi bi-people-fill"></i>
                                                    </span>
                                                    Data {{ rel }}
                                                </h4>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md" :class="rel === 'Ayah' ? 'bg-blue-50 text-blue-700' : (rel === 'Ibu' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700')">
                                                    {{ rel }}
                                                </span>
                                            </div>

                                            <template v-if="(activeStudent.orang_tua || []).find(o => o.hubungan === rel)">
                                                <div v-for="o in (activeStudent.orang_tua || []).filter(o => o.hubungan === rel)" :key="o.id" class="space-y-2 text-xs divide-y divide-slate-100">
                                                    <div class="pt-1 flex justify-between"><span class="text-slate-500">Nama Lengkap:</span><span class="font-extrabold text-slate-900 text-right">{{ o.nama_lengkap }}</span></div>
                                                    <div class="pt-2 flex justify-between"><span class="text-slate-500">NIK:</span><span class="font-mono font-bold text-slate-800 text-right">{{ o.nik || '-' }}</span></div>
                                                    <div class="pt-2 flex justify-between"><span class="text-slate-500">Pekerjaan:</span><span class="font-semibold text-slate-800 text-right">{{ o.pekerjaan || '-' }}</span></div>
                                                    <div class="pt-2 flex justify-between"><span class="text-slate-500">Pendidikan:</span><span class="font-semibold text-slate-800 text-right">{{ o.pendidikan || '-' }}</span></div>
                                                    <div class="pt-2 flex justify-between"><span class="text-slate-500">Penghasilan:</span><span class="font-bold text-emerald-700 text-right">Rp{{ Number(o.penghasilan || 0).toLocaleString('id-ID') }}</span></div>
                                                    <div class="pt-2 flex justify-between items-center">
                                                        <span class="text-slate-500">No. WhatsApp / HP:</span>
                                                        <span class="font-mono font-bold text-slate-900 text-right flex items-center gap-1">
                                                            <i v-if="o.no_hp" class="bi bi-whatsapp text-emerald-600 text-xs"></i> {{ o.no_hp || '-' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </template>
                                            <div v-else class="text-center py-4 text-slate-400 text-xs">
                                                <i class="bi bi-person-x text-lg text-slate-300 inline-block me-1"></i>
                                                Data {{ rel }} belum dilengkapi.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <!-- TAB 2: FISIK & KESEHATAN                                         -->
                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <div v-show="detailActiveSubTab === 'kesehatan'" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="bg-gradient-to-br from-rose-50 to-white p-3.5 rounded-2xl border border-rose-200/60 shadow-2xs flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-base font-black shadow-xs shrink-0">
                                            <i class="bi bi-droplet-fill"></i>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Golongan Darah</div>
                                            <div class="text-base font-black text-rose-700">{{ activeStudent.fisik?.golongan_darah || 'O' }}</div>
                                        </div>
                                    </div>

                                    <div class="bg-gradient-to-br from-blue-50 to-white p-3.5 rounded-2xl border border-blue-200/60 shadow-2xs flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-base font-black shadow-xs shrink-0">
                                            <i class="bi bi-activity"></i>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tinggi / Berat Badan</div>
                                            <div class="text-sm font-black text-slate-900">{{ activeStudent.fisik?.tinggi_badan || '-' }} cm / {{ activeStudent.fisik?.berat_badan || '-' }} kg</div>
                                        </div>
                                    </div>

                                    <div class="bg-gradient-to-br from-emerald-50 to-white p-3.5 rounded-2xl border border-emerald-200/60 shadow-2xs flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-base font-black shadow-xs shrink-0">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kelainan / Disabilitas</div>
                                            <div class="text-xs font-bold text-slate-800">{{ activeStudent.fisik?.disabilitas || 'Tidak Ada (Normal)' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 2: Pemeriksaan Berkala Kesehatan -->
                                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                                    <div class="p-3.5 bg-slate-50/80 border-b border-slate-200/80 font-black text-xs text-slate-800 uppercase tracking-wider flex items-center justify-between">
                                        <span class="flex items-center gap-1.5"><i class="bi bi-clipboard2-pulse-fill text-rose-600"></i> Pemeriksaan Berkala Kesehatan Semester 1 s.d. 6</span>
                                        <span class="text-[10px] font-bold text-slate-400 font-mono">6 Semester</span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-center text-xs whitespace-nowrap">
                                            <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider">
                                                <tr>
                                                    <th class="py-3 px-3.5 text-left">Semester</th>
                                                    <th class="py-3 px-3.5">Tinggi Badan</th>
                                                    <th class="py-3 px-3.5">Berat Badan</th>
                                                    <th class="py-3 px-3.5">Pendengaran</th>
                                                    <th class="py-3 px-3.5">Penglihatan</th>
                                                    <th class="py-3 px-3.5">Kesehatan Gigi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 font-medium">
                                                <tr v-for="sem in [1, 2, 3, 4, 5, 6]" :key="sem" class="hover:bg-blue-50/30 transition">
                                                    <td class="py-2.5 px-3.5 text-left font-bold text-slate-800">
                                                        <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 font-bold inline-flex items-center justify-center text-[11px] me-1.5">S{{ sem }}</span>
                                                        Semester {{ sem }}
                                                    </td>
                                                    <td class="py-2.5 px-3.5 font-mono font-bold text-slate-800">{{ activeStudent.kesehatan?.[sem]?.tinggi_badan ? activeStudent.kesehatan[sem].tinggi_badan + ' cm' : '-' }}</td>
                                                    <td class="py-2.5 px-3.5 font-mono font-bold text-slate-800">{{ activeStudent.kesehatan?.[sem]?.berat_badan ? activeStudent.kesehatan[sem].berat_badan + ' kg' : '-' }}</td>
                                                    <td class="py-2.5 px-3.5"><span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-2 py-0.5 rounded-md font-bold text-[10px]">{{ activeStudent.kesehatan?.[sem]?.pendengaran || 'Baik' }}</span></td>
                                                    <td class="py-2.5 px-3.5"><span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-2 py-0.5 rounded-md font-bold text-[10px]">{{ activeStudent.kesehatan?.[sem]?.pengelihatan || 'Baik' }}</span></td>
                                                    <td class="py-2.5 px-3.5"><span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-2 py-0.5 rounded-md font-bold text-[10px]">{{ activeStudent.kesehatan?.[sem]?.gigi || 'Baik' }}</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <!-- TAB 3: AKADEMIK & NILAI RAPOR (GABUNGAN KELAS + RAPOR)            -->
                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <div v-show="detailActiveSubTab === 'akademik'" class="space-y-4">
                                <!-- Card 1: Riwayat Kelas / Kenaikan Rombel -->
                                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                                    <div class="p-3.5 bg-slate-50/80 border-b border-slate-200/80 font-black text-xs text-slate-800 uppercase tracking-wider flex items-center justify-between">
                                        <span class="flex items-center gap-1.5"><i class="bi bi-diagram-3-fill text-blue-600"></i> Riwayat Kelas & Kenaikan Rombel</span>
                                        <span class="text-[10px] font-bold text-slate-400 font-mono">{{ (activeStudent.riwayat_kelas || []).length }} Entri Tercatat</span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs whitespace-nowrap">
                                            <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider">
                                                <tr>
                                                    <th class="py-3 px-3.5 text-left">Tahun Ajaran</th>
                                                    <th class="py-3 px-3.5 text-left">Kelas / Rombel</th>
                                                    <th class="py-3 px-3.5 text-left">Jenis Mutasi / Aksi</th>
                                                    <th class="py-3 px-3.5 text-left">Catatan Mutasi</th>
                                                    <th class="py-3 px-3.5 text-right">Tanggal Tercatat</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 font-medium">
                                                <tr v-for="rk in activeStudent.riwayat_kelas || []" :key="rk.id" class="hover:bg-blue-50/30 transition">
                                                    <td class="py-2.5 px-3.5 font-mono font-bold text-slate-800">{{ rk.tahun_ajaran }}</td>
                                                    <td class="py-2.5 px-3.5 font-extrabold text-blue-700">
                                                        <span class="px-2 py-0.5 rounded-lg bg-blue-50 border border-blue-100">{{ rk.ke_kelas || rk.nama_kelas_tujuan || activeStudent.nama_kelas }}</span>
                                                    </td>
                                                    <td class="py-2.5 px-3.5"><span class="bg-slate-100 text-slate-700 border border-slate-200 font-bold px-2 py-0.5 rounded-md text-[10px]">{{ rk.jenis_aksi || 'Penempatan Awal' }}</span></td>
                                                    <td class="py-2.5 px-3.5 text-slate-600">{{ rk.catatan || '-' }}</td>
                                                    <td class="py-2.5 px-3.5 font-mono text-slate-400 text-right">{{ rk.created_at ? formatIndoDate(rk.created_at) : '-' }}</td>
                                                </tr>
                                                <tr v-if="(activeStudent.riwayat_kelas || []).length === 0">
                                                    <td colspan="5" class="text-center py-8 text-slate-400">
                                                        <i class="bi bi-diagram-2 text-2xl text-slate-300 block mb-1"></i>
                                                        Belum ada riwayat mutasi kelas tercatat.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Card 2: Rincian Nilai Rapor Semesteran -->
                                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                                    <div class="p-3.5 bg-slate-50/80 border-b border-slate-200/80 font-black text-xs text-slate-800 uppercase tracking-wider flex items-center justify-between">
                                        <span class="flex items-center gap-1.5"><i class="bi bi-file-earmark-bar-graph-fill text-emerald-600"></i> Rincian Transkrip Nilai Rapor Per Semester</span>
                                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-md font-mono">{{ (activeStudent.nilai_rapor || []).length }} Nilai Mapel</span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs whitespace-nowrap">
                                            <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider">
                                                <tr>
                                                    <th class="py-3 px-3.5 text-left">Tahun Ajaran</th>
                                                    <th class="py-3 px-3.5 text-center">Semester</th>
                                                    <th class="py-3 px-3.5 text-left">Mata Pelajaran</th>
                                                    <th class="py-3 px-3.5 text-center">Nilai Akhir</th>
                                                    <th class="py-3 px-3.5 text-center">Predikat</th>
                                                    <th class="py-3 px-3.5 text-left">Capaian Kompetensi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 font-medium">
                                                <tr v-for="nr in activeStudent.nilai_rapor || []" :key="nr.id" class="hover:bg-blue-50/30 transition">
                                                    <td class="py-2.5 px-3.5 font-mono font-bold text-slate-800">{{ nr.tahun_ajaran }}</td>
                                                    <td class="py-2.5 px-3.5 text-center font-bold text-slate-700">{{ nr.semester }}</td>
                                                    <td class="py-2.5 px-3.5 font-extrabold text-slate-900">{{ nr.nama_mapel }}</td>
                                                    <td class="py-2.5 px-3.5 text-center font-mono font-black text-blue-700 text-sm">{{ nr.nilai_akhir }}</td>
                                                    <td class="py-2.5 px-3.5 text-center"><span class="bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-2.5 py-0.5 rounded-md font-black text-[11px]">{{ nr.predikat }}</span></td>
                                                    <td class="py-2.5 px-3.5 text-slate-600 max-w-xs truncate">{{ nr.deskripsi_capaian || '-' }}</td>
                                                </tr>
                                                <tr v-if="(activeStudent.nilai_rapor || []).length === 0">
                                                    <td colspan="6" class="text-center py-10 text-slate-400">
                                                        <i class="bi bi-journal-x text-3xl text-slate-300 block mb-1"></i>
                                                        Belum ada nilai rapor tercatat untuk siswa ini.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <!-- TAB 4: PRESTASI & BEASISWA (GABUNGAN PRESTASI + BEASISWA)         -->
                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <div v-show="detailActiveSubTab === 'prestasi_beasiswa'" class="space-y-4">
                                <!-- Card 1: Prestasi Siswa -->
                                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                                    <div class="p-3.5 bg-slate-50/80 border-b border-slate-200/80 font-black text-xs text-slate-800 uppercase tracking-wider flex items-center justify-between">
                                        <span class="flex items-center gap-1.5"><i class="bi bi-trophy-fill text-purple-600"></i> Rekam Jejak Prestasi & Kejuaraan</span>
                                        <span class="text-[10px] font-bold text-purple-700 bg-purple-50 border border-purple-200/80 px-2 py-0.5 rounded-md font-mono">{{ (activeStudent.prestasi || []).length }} Prestasi</span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs whitespace-nowrap">
                                            <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider">
                                                <tr>
                                                    <th class="py-3 px-3.5 text-left">Nama Lomba / Kegiatan</th>
                                                    <th class="py-3 px-3.5 text-center">Tingkat</th>
                                                    <th class="py-3 px-3.5 text-center">Juara / Capaian</th>
                                                    <th class="py-3 px-3.5 text-left">Penyelenggara</th>
                                                    <th class="py-3 px-3.5 text-right">Tahun</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 font-medium">
                                                <tr v-for="p in activeStudent.prestasi || []" :key="p.id" class="hover:bg-purple-50/30 transition">
                                                    <td class="py-3 px-3.5 font-extrabold text-slate-900">{{ p.nama_lomba }}</td>
                                                    <td class="py-3 px-3.5 text-center"><span class="bg-purple-50 text-purple-700 border border-purple-200/80 px-2.5 py-0.5 rounded-lg font-bold text-[10px]">{{ p.tingkat }}</span></td>
                                                    <td class="py-3 px-3.5 text-center font-black text-emerald-700">{{ p.juara_ke || p.capaian }}</td>
                                                    <td class="py-3 px-3.5 text-slate-600">{{ p.penyelenggara || '-' }}</td>
                                                    <td class="py-3 px-3.5 font-mono font-bold text-slate-500 text-right">{{ p.tanggal_lomba ? p.tanggal_lomba.substring(0, 4) : '-' }}</td>
                                                </tr>
                                                <tr v-if="(activeStudent.prestasi || []).length === 0">
                                                    <td colspan="5" class="text-center py-8 text-slate-400">
                                                        <div class="w-10 h-10 mx-auto rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg mb-1.5 shadow-2xs border border-purple-100">
                                                            <i class="bi bi-trophy"></i>
                                                        </div>
                                                        <div class="font-bold text-slate-600 text-xs">Belum Ada Catatan Prestasi Siswa</div>
                                                        <div class="text-[11px] text-slate-400 mt-0.5">Siswa ini belum memiliki rekam jejak kejuaraan/perlombaan.</div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Card 2: Riwayat Beasiswa -->
                                <div class="space-y-3">
                                    <!-- Form Tambah Beasiswa Modern -->
                                    <div class="bg-gradient-to-r from-blue-50/60 via-indigo-50/30 to-slate-50/60 p-4 rounded-2xl border border-blue-200/80 shadow-2xs space-y-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-extrabold text-slate-900 text-xs flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs shadow-2xs">
                                                    <i class="bi bi-gift-fill"></i>
                                                </span>
                                                Form Tambah Riwayat Beasiswa Siswa
                                            </h4>
                                            <span class="text-[10px] font-bold text-blue-700 bg-blue-100/70 border border-blue-200 px-2 py-0.5 rounded-md">Buku Induk</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Nama Beasiswa *</label>
                                                <input v-model="newBeasiswa.nama_beasiswa" type="text" placeholder="cth: PIP / KIP / Baznas" class="w-full h-9 px-3 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Penyelenggara</label>
                                                <input v-model="newBeasiswa.penyelenggara" type="text" placeholder="cth: Kemendikbud / Pemda" class="w-full h-9 px-3 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Tahun & Nominal</label>
                                                <div class="grid grid-cols-2 gap-1.5">
                                                    <input v-model="newBeasiswa.tahun_menerima" type="text" placeholder="Tahun" class="w-full h-9 px-2 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-center font-mono" />
                                                    <input v-model="newBeasiswa.nominal" type="number" placeholder="Nominal" class="w-full h-9 px-2 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 font-mono" />
                                                </div>
                                            </div>
                                            <div class="flex items-end">
                                                <button @click="submitBeasiswa" :disabled="isSavingBeasiswa || !newBeasiswa.nama_beasiswa" class="w-full h-9 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition flex items-center justify-center gap-1.5 disabled:opacity-50">
                                                    <i class="bi bi-plus-circle-fill text-xs"></i> <span>{{ isSavingBeasiswa ? 'Menyimpan...' : 'Simpan Beasiswa' }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tabel Riwayat Beasiswa Modern -->
                                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                                        <div class="p-3.5 bg-slate-50/80 border-b border-slate-200/80 font-black text-xs text-slate-800 uppercase tracking-wider flex items-center justify-between">
                                            <span class="flex items-center gap-1.5"><i class="bi bi-wallet2 text-emerald-600"></i> Daftar Penerimaan Beasiswa</span>
                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-md font-mono">{{ (activeStudent.beasiswa || []).length }} Beasiswa</span>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-xs whitespace-nowrap">
                                                <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider">
                                                    <tr>
                                                        <th class="py-3 px-3.5 text-left">Nama Beasiswa</th>
                                                        <th class="py-3 px-3.5 text-left">Penyelenggara</th>
                                                        <th class="py-3 px-3.5 text-center">Tahun</th>
                                                        <th class="py-3 px-3.5 text-right">Nominal</th>
                                                        <th class="py-3 px-3.5 text-center" style="width: 70px;">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 font-medium">
                                                    <tr v-for="b in activeStudent.beasiswa || []" :key="b.id" class="hover:bg-blue-50/30 transition">
                                                        <td class="py-3 px-3.5 font-extrabold text-slate-900">{{ b.nama_beasiswa }}</td>
                                                        <td class="py-3 px-3.5 text-slate-600">{{ b.penyelenggara || '-' }}</td>
                                                        <td class="py-3 px-3.5 font-mono text-center font-bold text-slate-700">{{ b.tahun_menerima }}</td>
                                                        <td class="py-3 px-3.5 font-mono font-extrabold text-emerald-700 text-right">Rp{{ Number(b.nominal || 0).toLocaleString('id-ID') }}</td>
                                                        <td class="py-3 px-3.5 text-center">
                                                            <button @click="deleteBeasiswa(b.id)" class="w-7 h-7 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/60 transition flex items-center justify-center shadow-2xs mx-auto" title="Hapus Riwayat Beasiswa">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="(activeStudent.beasiswa || []).length === 0">
                                                        <td colspan="5" class="text-center py-8 text-slate-400">
                                                            <div class="w-10 h-10 mx-auto rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg mb-1.5 shadow-2xs border border-blue-100">
                                                                <i class="bi bi-gift"></i>
                                                            </div>
                                                            <div class="font-bold text-slate-600 text-xs">Belum Ada Riwayat Beasiswa</div>
                                                            <div class="text-[11px] text-slate-400 mt-0.5">Gunakan formulir di atas untuk mencatat riwayat penerimaan beasiswa siswa.</div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <!-- TAB 5: KEDISIPLINAN & BK                                         -->
                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <div v-show="detailActiveSubTab === 'kedisiplinan'" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                                <div class="p-3.5 bg-slate-50/80 border-b border-slate-200/80 font-black text-xs text-slate-800 uppercase tracking-wider flex items-center justify-between">
                                    <span class="flex items-center gap-1.5"><i class="bi bi-shield-fill-exclamation text-rose-600"></i> Rekam Kedisiplinan & Catatan Pelanggaran BK</span>
                                    <span class="text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200/80 px-2 py-0.5 rounded-md font-mono">{{ (activeStudent.pelanggaran || []).length }} Kasus</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs whitespace-nowrap">
                                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider">
                                            <tr>
                                                <th class="py-3 px-3.5 text-left">Tanggal Kejadian</th>
                                                <th class="py-3 px-3.5 text-left">Nama Pelanggaran</th>
                                                <th class="py-3 px-3.5 text-center">Bobot Poin</th>
                                                <th class="py-3 px-3.5 text-left">Tindakan / Sanksi</th>
                                                <th class="py-3 px-3.5 text-left">Guru BK Pencatat</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 font-medium">
                                            <tr v-for="pl in activeStudent.pelanggaran || []" :key="pl.id" class="hover:bg-rose-50/20 transition">
                                                <td class="py-3 px-3.5 font-mono text-slate-600">{{ formatIndoDate(pl.tanggal_kejadian) }}</td>
                                                <td class="py-3 px-3.5 font-extrabold text-slate-900">{{ pl.nama_pelanggaran }}</td>
                                                <td class="py-3 px-3.5 text-center">
                                                    <span class="bg-rose-100 text-rose-800 font-mono font-black px-2.5 py-0.5 rounded-md text-[11px]">{{ pl.bobot_poin || pl.poin }} Poin</span>
                                                </td>
                                                <td class="py-3 px-3.5 text-slate-600">{{ pl.tindakan || '-' }}</td>
                                                <td class="py-3 px-3.5 text-slate-600">{{ pl.guru_pencatat || '-' }}</td>
                                            </tr>
                                            <tr v-if="(activeStudent.pelanggaran || []).length === 0">
                                                <td colspan="5" class="text-center py-10 text-slate-400">
                                                    <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-2 shadow-2xs border border-emerald-100">
                                                        <i class="bi bi-shield-check"></i>
                                                    </div>
                                                    <div class="font-extrabold text-slate-800 text-xs">Rekam Jejak Kedisiplinan Bersih</div>
                                                    <div class="text-[11px] text-slate-400 mt-0.5">Siswa ini memiliki riwayat disiplin prima (0 pelanggaran).</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <!-- TAB 6: TRACER STUDY                                              -->
                            <!-- ═══════════════════════════════════════════════════════════════ -->
                            <div v-show="detailActiveSubTab === 'tracer'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-4 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                        <h4 class="font-extrabold text-slate-900 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                                            <i class="bi bi-mortarboard-fill text-blue-600"></i> Riwayat Kuliah / Perguruan Tinggi
                                        </h4>
                                        <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">Alumni</span>
                                    </div>
                                    <div v-for="tk in activeStudent.tracer_kuliah || []" :key="tk.id" class="p-3 bg-slate-50/70 border border-slate-200/70 rounded-xl space-y-1">
                                        <div class="font-extrabold text-slate-900">{{ tk.nama_kampus }}</div>
                                        <div class="text-slate-600">Program Studi: <strong class="text-slate-800">{{ tk.program_studi }}</strong> (Jenjang: {{ tk.jenjang }})</div>
                                        <div class="text-[11px] font-mono text-slate-400">Tahun Masuk: {{ tk.tahun_masuk }}</div>
                                    </div>
                                    <div v-if="(activeStudent.tracer_kuliah || []).length === 0" class="text-center py-8 text-slate-400">
                                        <i class="bi bi-mortarboard text-2xl text-slate-300 block mb-1"></i>
                                        Belum ada rekam jejak perkuliahan.
                                    </div>
                                </div>

                                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-4 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                        <h4 class="font-extrabold text-slate-900 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                                            <i class="bi bi-briefcase-fill text-emerald-600"></i> Riwayat Pekerjaan / Karir
                                        </h4>
                                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Karir</span>
                                    </div>
                                    <div v-for="tp in activeStudent.tracer_pekerjaan || []" :key="tp.id" class="p-3 bg-slate-50/70 border border-slate-200/70 rounded-xl space-y-1">
                                        <div class="font-extrabold text-slate-900">{{ tp.nama_perusahaan }}</div>
                                        <div class="text-slate-600">Posisi / Jabatan: <strong class="text-slate-800">{{ tp.jabatan }}</strong></div>
                                        <div class="text-[11px] font-mono text-slate-400">Masa Kerja: {{ tp.tahun_mulai }} s.d. {{ tp.tahun_selesai || 'Sekarang' }}</div>
                                    </div>
                                    <div v-if="(activeStudent.tracer_pekerjaan || []).length === 0" class="text-center py-8 text-slate-400">
                                        <i class="bi bi-briefcase text-2xl text-slate-300 block mb-1"></i>
                                        Belum ada rekam jejak pekerjaan.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Modal Footer -->
                    <div class="p-3.5 sm:p-4 border-t border-slate-200/80 bg-slate-50/70 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <a v-if="activeStudent" :href="`/buku-induk/cetak/${activeStudent.id}`" target="_blank" class="btn btn-sm btn-light border border-slate-200/90 hover:bg-white text-slate-700 rounded-xl px-3.5 py-2 text-xs font-bold shadow-2xs transition flex items-center gap-1.5">
                                <i class="bi bi-printer-fill text-blue-600"></i> Cetak Lembar Buku Induk Resmi
                            </a>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link v-if="activeStudent" :href="`/siswa/${activeStudent.id}/edit`" class="btn btn-sm btn-primary rounded-xl px-4 py-2 text-xs font-bold shadow-xs flex items-center gap-1.5">
                                <i class="bi bi-pencil-square"></i> Edit Profil Siswa Lengkap
                            </Link>
                            <button type="button" @click="isDetailOpen = false" class="btn btn-sm btn-light border border-slate-200/90 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- MODAL SALIN KURIKULUM                                               -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-if="isCopyModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-md p-5 space-y-4 animate-fade-in">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                            <i class="bi bi-files text-blue-600"></i> Salin Kurikulum dari Kelas Lain
                        </h3>
                        <button @click="isCopyModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Kelas Sumber (Source)</label>
                        <select v-model="copySourceKelasId" class="form-select form-select-sm text-xs rounded-xl border-slate-200 w-full">
                            <option value="">-- Pilih Kelas Sumber --</option>
                            <option v-for="k in kelasList.filter(k => k.id !== kurikulumParams.kelas_id)" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">Seluruh kelompok & mapel kelas sumber akan disalin ke kelas target saat ini.</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button @click="isCopyModalOpen = false" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-3 text-xs">Batal</button>
                        <button @click="submitCopyKurikulum" :disabled="!copySourceKelasId" class="btn btn-sm btn-primary rounded-xl px-4 text-xs font-bold shadow-xs">Salin Sekarang</button>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- MODAL EDIT NILAI RAPOR SISWA                                        -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-if="isEditNilaiOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden animate-fade-in">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Input Nilai Rapor Siswa</h3>
                            <p class="text-[11px] text-slate-500 font-bold text-blue-700">{{ selectedStudentNilai?.nama_lengkap }} ({{ nilaiParams.tahun_ajaran }} - {{ nilaiParams.semester }})</p>
                        </div>
                        <button @click="isEditNilaiOpen = false" class="btn btn-sm btn-light border border-slate-200 rounded-xl p-2 text-slate-500"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="p-4 overflow-y-auto grow space-y-3">
                        <div v-for="sub in nilaiSubjects" :key="sub.mapel_id" class="p-3 bg-slate-50/60 rounded-xl border border-slate-100 grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                            <div class="sm:col-span-5 font-extrabold text-xs text-slate-800">{{ sub.nama_mapel }}</div>
                            <div class="sm:col-span-3">
                                <input v-model="tempGrades[sub.mapel_id].nilai_akhir" @input="onNilaiInput(sub.mapel_id)" type="number" min="0" max="100" placeholder="Nilai (0-100)" class="form-control form-control-sm text-xs rounded-xl border-slate-200 text-center font-bold" />
                            </div>
                            <div class="sm:col-span-4 flex items-center gap-1.5">
                                <span class="badge px-2 py-1 rounded-lg font-mono font-bold text-xs" :class="tempGrades[sub.mapel_id].predikat === 'A' ? 'bg-emerald-100 text-emerald-800' : (tempGrades[sub.mapel_id].predikat === 'B' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700')">
                                    {{ tempGrades[sub.mapel_id].predikat || '-' }}
                                </span>
                                <input v-model="tempGrades[sub.mapel_id].capaian" type="text" placeholder="Capaian kompetensi..." class="form-control form-control-sm text-xs rounded-xl border-slate-200 w-full" />
                            </div>
                        </div>
                    </div>
                    <div class="p-3.5 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-2">
                        <button @click="isEditNilaiOpen = false" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-4 py-2 text-xs">Batal</button>
                        <button @click="saveStudentGrades" class="btn btn-sm btn-primary rounded-xl px-4 py-2 text-xs font-bold shadow-xs">Simpan Nilai</button>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- MODAL RIWAYAT KEPALA SEKOLAH                                        -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-if="isModalKepsekOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-md p-5 space-y-4 animate-fade-in">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-sm font-extrabold text-slate-900">
                            {{ kepsekForm.id ? 'Edit Riwayat Kepala Sekolah' : 'Tambah Riwayat Kepala Sekolah' }}
                        </h3>
                        <button @click="isModalKepsekOpen = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Kepala Sekolah *</label>
                            <input v-model="kepsekForm.nama_kepsek" type="text" placeholder="Gelar & Nama Lengkap" class="form-control form-control-sm rounded-xl border-slate-200" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">NIP Kepala Sekolah</label>
                            <input v-model="kepsekForm.nip_kepsek" type="text" placeholder="NIP (opsional)" class="form-control form-control-sm rounded-xl border-slate-200 font-mono" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tanggal Mulai *</label>
                                <input v-model="kepsekForm.tanggal_mulai" type="date" class="form-control form-control-sm rounded-xl border-slate-200" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tanggal Selesai</label>
                                <input v-model="kepsekForm.tanggal_selesai" type="date" class="form-control form-control-sm rounded-xl border-slate-200" />
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer pt-1">
                            <input v-model="kepsekForm.status_plt" type="checkbox" class="rounded border-slate-300 text-blue-600" />
                            <span class="font-bold text-slate-800">Status Pejabat Sementara (Plt / Pjs)</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button @click="isModalKepsekOpen = false" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-3 text-xs">Batal</button>
                        <button @click="saveKepsek" :disabled="!kepsekForm.nama_kepsek || !kepsekForm.tanggal_mulai" class="btn btn-sm btn-primary rounded-xl px-4 text-xs font-bold shadow-xs">Simpan</button>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- MODAL IMPOR NILAI RAPOR                                              -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-if="isImportNilaiOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-lg p-5 space-y-4 animate-fade-in">
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shadow-2xs">
                                    <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                                </span>
                                Impor Nilai Rapor Massal (.XLSX)
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Kurikulum Aktif: <strong class="text-blue-600">{{ activeKurikulumName }}</strong></p>
                        </div>
                        <button @click="isImportNilaiOpen = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="p-3.5 bg-blue-50/70 border border-blue-200/80 rounded-2xl space-y-2">
                            <div class="font-bold text-blue-900 flex items-center gap-1.5">
                                <i class="bi bi-info-circle-fill text-blue-600"></i> Panduan Pengisian Format Nilai Excel:
                            </div>
                            <ol class="list-decimal list-inside space-y-1 text-slate-600 text-[11px]">
                                <li>Pastikan mengunduh template format nilai terlebih dahulu melalui tombol di bawah.</li>
                                <li>Isi kolom nilai (rentang 0–100) dan deskripsi capaian/predikat untuk setiap mata pelajaran.</li>
                                <li>Jangan mengubah kolom <strong>Siswa ID</strong>, <strong>NISN</strong>, atau header kode mapel <strong>[Nilai_ID:...]</strong>.</li>
                            </ol>
                            <button @click="downloadNilaiTemplate" type="button" class="btn btn-xs btn-white border border-blue-200 text-blue-700 hover:bg-white rounded-lg px-3 py-1.5 font-bold shadow-2xs flex items-center gap-1.5">
                                <i class="bi bi-file-earmark-excel-fill text-emerald-600"></i> Unduh Format Nilai Kelas Ini (.XLSX)
                            </button>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5 uppercase tracking-wider text-[10px]">Pilih File Excel (.xlsx / .xls / .csv) Hasil Pengisian *</label>
                            <input type="file" @change="onNilaiFileChange" accept=".xlsx, .xls, .csv" class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-2xl p-2 bg-slate-50/50 cursor-pointer" />
                            <p v-if="importNilaiFile" class="text-[11px] text-emerald-600 font-bold mt-1.5 flex items-center gap-1">
                                <i class="bi bi-check-circle-fill"></i> Berkas siap diunggah: {{ importNilaiFile.name }} ({{ (importNilaiFile.size / 1024).toFixed(1) }} KB)
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t">
                        <span class="text-[11px] text-slate-400">Periode: {{ nilaiParams.tahun_ajaran }} ({{ nilaiParams.semester }})</span>
                        <div class="flex gap-2">
                            <button @click="isImportNilaiOpen = false" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-3.5 text-xs font-bold">Batal</button>
                            <button @click="submitImportNilai" :disabled="!importNilaiFile || isUploadingNilai" class="btn btn-sm btn-primary rounded-xl px-4 text-xs font-bold shadow-xs flex items-center gap-1.5 disabled:opacity-50">
                                <span v-if="isUploadingNilai" class="spinner-border spinner-border-sm" role="status"></span>
                                <i v-else class="bi bi-cloud-arrow-up-fill"></i>
                                {{ isUploadingNilai ? 'Mengimpor Data...' : 'Mulai Impor Nilai' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- MODAL PDF VIEWER                                                    -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-if="isPdfViewerOpen" class="fixed inset-0 z-50 overflow-hidden bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden animate-fade-in">
                    <div class="p-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <h3 class="text-xs font-extrabold text-slate-900 flex items-center gap-2">
                            <i class="bi bi-file-earmark-pdf-fill text-rose-600"></i>
                            {{ activePdfTitle }}
                        </h3>
                        <button @click="isPdfViewerOpen = false" class="btn btn-xs btn-light border border-slate-200 rounded-lg px-2.5 py-1 text-xs">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="grow bg-slate-100">
                        <iframe :src="activePdfUrl" class="w-full h-full border-0"></iframe>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- INTERACTIVE CONFIRMATION MODAL POPUP                                -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[9990] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-md p-6 space-y-4 animate-fade-in text-center">
                    <!-- Icon Circle -->
                    <div class="mx-auto w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-xs"
                         :class="confirmModal.type === 'danger' ? 'bg-rose-50 text-rose-600 border border-rose-200' : (confirmModal.type === 'warning' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-blue-50 text-blue-600 border border-blue-200')">
                        <i :class="confirmModal.type === 'danger' ? 'bi bi-trash3-fill' : (confirmModal.type === 'warning' ? 'bi bi-exclamation-triangle-fill' : 'bi bi-info-circle-fill')"></i>
                    </div>

                    <div>
                        <h3 class="text-base font-black text-slate-900 tracking-tight">
                            {{ confirmModal.title }}
                        </h3>
                        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                            {{ confirmModal.message }}
                        </p>
                        <p v-if="confirmModal.subMessage" class="text-[11px] font-bold text-rose-500 mt-1">
                            {{ confirmModal.subMessage }}
                        </p>
                    </div>

                    <div class="flex items-center justify-center gap-2.5 pt-2">
                        <button type="button" 
                                @click="closeConfirmModal" 
                                :disabled="confirmModal.loading"
                                class="btn btn-sm btn-light border border-slate-200/90 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                            {{ confirmModal.cancelText }}
                        </button>
                        <button type="button" 
                                @click="executeConfirmModalAction" 
                                :disabled="confirmModal.loading"
                                class="btn btn-sm rounded-xl px-5 py-2 text-xs font-bold shadow-xs transition flex items-center gap-1.5"
                                :class="confirmModal.type === 'danger' ? 'btn-danger bg-rose-600 hover:bg-rose-700 text-white' : (confirmModal.type === 'warning' ? 'btn-warning bg-amber-500 hover:bg-amber-600 text-white' : 'btn-primary bg-blue-600 hover:bg-blue-700 text-white')">
                            <div v-if="confirmModal.loading" class="spinner-border spinner-border-sm text-white" role="status"></div>
                            <span>{{ confirmModal.loading ? 'Memproses...' : confirmModal.confirmText }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- FLOATING TOAST NOTIFICATION POPUP STACK                             -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <div class="fixed top-5 right-5 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none">
                <transition-group name="toast-fade">
                    <div v-for="toast in toasts" 
                         :key="toast.id" 
                         class="pointer-events-auto p-4 rounded-2xl border shadow-xl bg-white/95 backdrop-blur-md flex items-start gap-3 transition-all duration-300 transform"
                         :class="toast.type === 'success' ? 'border-emerald-200 text-slate-800' : (toast.type === 'error' ? 'border-rose-200 text-slate-800' : (toast.type === 'warning' ? 'border-amber-200 text-slate-800' : 'border-blue-200 text-slate-800'))">
                        
                        <!-- Icon Badge -->
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-sm font-bold shadow-2xs"
                             :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : (toast.type === 'error' ? 'bg-rose-50 text-rose-600 border border-rose-200' : (toast.type === 'warning' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-blue-50 text-blue-600 border border-blue-200'))">
                            <i :class="toast.type === 'success' ? 'bi bi-check-circle-fill' : (toast.type === 'error' ? 'bi bi-x-circle-fill' : (toast.type === 'warning' ? 'bi bi-exclamation-triangle-fill' : 'bi bi-info-circle-fill'))"></i>
                        </div>

                        <!-- Message Text -->
                        <div class="grow min-w-0">
                            <h5 class="text-xs font-black text-slate-900 leading-tight">
                                {{ toast.title || (toast.type === 'success' ? 'Berhasil' : 'Notifikasi') }}
                            </h5>
                            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug break-words">
                                {{ toast.message }}
                            </p>
                        </div>

                        <!-- Close Toast Button -->
                        <button type="button" @click="removeToast(toast.id)" class="text-slate-400 hover:text-slate-600 shrink-0 text-xs p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </transition-group>
            </div>

        </div>
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

.toast-fade-enter-active,
.toast-fade-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-fade-enter-from {
    opacity: 0;
    transform: translateY(-12px) scale(0.95);
}
.toast-fade-leave-to {
    opacity: 0;
    transform: translateX(20px) scale(0.95);
}
</style>
