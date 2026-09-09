<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  rankingData: Object,
  jurusanList: Array,
  kelasList: Array,
  tahunAjaranList: Array,
  pdssMapels: Array,
  collisions: Array,
  workflowStatus: Object,
  simulasiStats: Object,
  masterKampusList: Object,
  pengunduranDiriList: Array,
  tenantInfo: Object,
  isSuperAdmin: Boolean,
  tenants: Array,
  filters: Object,
})

const page = usePage()

// ══════════════════════════════════════════════════════════════════════════════
// MODERN POPUP SYSTEMS: TOAST NOTIFICATION & CONFIRMATION MODAL
// ══════════════════════════════════════════════════════════════════════════════
const toast = ref({
  show: false,
  type: 'success', // 'success' | 'error' | 'warning' | 'info'
  title: 'Berhasil',
  message: '',
  timer: null,
})

const showToast = (message, type = 'success', title = '') => {
  if (toast.value.timer) clearTimeout(toast.value.timer)
  if (!title) {
    if (type === 'success') title = 'Berhasil Disimpan'
    else if (type === 'error') title = 'Terjadi Kesalahan'
    else if (type === 'warning') title = 'Peringatan'
    else title = 'Informasi'
  }
  toast.value = {
    show: true,
    type,
    title,
    message,
    timer: setTimeout(() => {
      toast.value.show = false
    }, 4000),
  }
}

const closeToast = () => {
  if (toast.value.timer) clearTimeout(toast.value.timer)
  toast.value.show = false
}

// Watcher untuk Flash Session Message dari Backend Laravel
watch(() => page.props.flash, (flash) => {
  if (flash?.success) showToast(flash.success, 'success', 'Berhasil')
  else if (flash?.error) showToast(flash.error, 'error', 'Perhatian')
  else if (flash?.warning) showToast(flash.warning, 'warning', 'Peringatan')
  else if (flash?.info) showToast(flash.info, 'info', 'Informasi')
}, { deep: true, immediate: true })

// Modal Konfirmasi Interaktif Modern (Pengganti confirm() bawaan browser)
const confirmModal = ref({
  show: false,
  title: '',
  message: '',
  detail: '',
  confirmText: 'Ya, Lanjutkan',
  cancelText: 'Batal',
  type: 'warning', // 'warning' | 'danger' | 'info' | 'primary'
  action: null,
  isLoading: false,
})

const openConfirmModal = ({ title, message, detail = '', confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', type = 'warning', action }) => {
  confirmModal.value = {
    show: true,
    title,
    message,
    detail,
    confirmText,
    cancelText,
    type,
    action,
    isLoading: false,
  }
}

const closeConfirmModal = () => {
  confirmModal.value.show = false
  confirmModal.value.action = null
  confirmModal.value.isLoading = false
}

const handleConfirmModalSubmit = () => {
  if (typeof confirmModal.value.action === 'function') {
    confirmModal.value.isLoading = true
    confirmModal.value.action()
  }
  confirmModal.value.show = false
}

onUnmounted(() => {
  if (toast.value.timer) clearTimeout(toast.value.timer)
})

// Tab State
const activeTab = ref('mapel')

// Filter State
const filterJurusan = ref(props.filters?.jurusan || '')
const filterTahunAjaran = ref(props.filters?.tahun_ajaran || '2026/2027')
const filterStatus = ref(props.filters?.status_eligible || '')
const searchQuery = ref(props.filters?.search || '')
const selectedTenant = ref(props.filters?.tenant_id || '')
const activeSimulasi = ref(Number(props.filters?.no_simulasi) || 1)
const perPage = ref(15)
const currentPage = ref(1)

// Mapel State & Filter Berjenjang (Langkah 1)
const filterJurusanMapel = ref(props.filters?.jurusan_mapel || '')
const filterKelasMapel = ref(props.filters?.kelas_mapel || '')
const localMapels = ref(props.pdssMapels ? JSON.parse(JSON.stringify(props.pdssMapels)) : [])
const isSavingMapels = ref(false)
const isAutoDetecting = ref(false)
const autoDetectNotice = ref('')

// Pengelompokan Kategori Mapel: Wajib Nasional vs Peminatan/Pilihan
const mapelWajibList = computed(() => {
  return localMapels.value.filter(m => {
    const kat = (m.kategori || '').toLowerCase()
    const nama = (m.nama_mata_pelajaran || '').toLowerCase()
    return kat.includes('wajib') || kat.includes('nasional') || nama.includes('wajib')
  })
})

const mapelPilihanList = computed(() => {
  return localMapels.value.filter(m => {
    const kat = (m.kategori || '').toLowerCase()
    const nama = (m.nama_mata_pelajaran || '').toLowerCase()
    return !(kat.includes('wajib') || kat.includes('nasional') || nama.includes('wajib'))
  })
})

const selectedMapelsCount = computed(() => {
  return localMapels.value.filter(m => m.sem_1 || m.sem_2 || m.sem_3 || m.sem_4 || m.sem_5).length
})

const toggleAllSemesters = (val) => {
  localMapels.value.forEach(m => {
    m.sem_1 = val
    m.sem_2 = val
    m.sem_3 = val
    m.sem_4 = val
    m.sem_5 = val
  })
  showToast(val ? 'Semua semester 1 s.d. 5 telah dicentang.' : 'Semua centang semester telah dibatalkan.', 'info', 'Pembaruan Tampilan')
}

const toggleWajibNasional = () => {
  let count = 0
  localMapels.value.forEach(m => {
    const kat = (m.kategori || '').toLowerCase()
    const nama = (m.nama_mata_pelajaran || '').toLowerCase()
    const isWajib = kat.includes('wajib') || kat.includes('nasional') || nama.includes('wajib')
    if (isWajib) {
      m.sem_1 = true
      m.sem_2 = true
      m.sem_3 = true
      m.sem_4 = true
      m.sem_5 = true
      count++
    }
  })
  showToast(`${count} Mata Pelajaran Wajib Nasional otomatis dicentang untuk 5 semester.`, 'success', 'Centang Otomatis')
}

// Filter Tenants: Hilangkan "Pusat Kendali SaaS (Global)" dari dropdown
const filteredTenants = computed(() => {
  if (!props.tenants) return []
  return props.tenants.filter(t => 
    t.npsn !== 'PLATFORM' && 
    t.id !== '00000000-0000-0000-0000-000000000000' && 
    !(t.nama_sekolah || '').toLowerCase().includes('pusat kendali')
  )
})

// Sinkronkan data saat prop berubah
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    if (newFilters.tenant_id !== undefined && newFilters.tenant_id !== '') {
      selectedTenant.value = newFilters.tenant_id
    }
    if (newFilters.tahun_ajaran) {
      filterTahunAjaran.value = newFilters.tahun_ajaran
    }
  }
}, { deep: true })

watch(() => props.pdssMapels, (newMapels) => {
  if (newMapels) {
    localMapels.value = JSON.parse(JSON.stringify(newMapels))
  }
}, { deep: true })

const autoDetectFromRapor = async () => {
  isAutoDetecting.value = true
  autoDetectNotice.value = ''
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
      || page.props.csrf_token || ''
    const res = await fetch('/pdss/auto-detect-mapel', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        kelas_id: filterKelasMapel.value,
        jurusan: filterJurusanMapel.value,
        tahun_ajaran: filterTahunAjaran.value,
        tenant_id: selectedTenant.value,
      })
    })
    const json = await res.json()
    if (json.success) {
      autoDetectNotice.value = json.message
      if (json.data?.mapels) {
        localMapels.value = json.data.mapels
      }
      showToast(json.message || 'Mata pelajaran berhasil dipindai dan disinkronkan dari nilai rapor riil.', 'success', 'Deteksi Rapor Sukses')
    } else {
      showToast(json.message || 'Gagal mendeteksi mata pelajaran dari rapor.', 'error', 'Gagal Deteksi')
    }
  } catch (err) {
    console.error(err)
    showToast('Terjadi kesalahan koneksi saat memindai database nilai rapor.', 'error', 'Kesalahan Sistem')
  } finally {
    isAutoDetecting.value = false
  }
}

const applyMapelFilter = () => {
  router.get('/bk/akademik', {
    tenant_id: selectedTenant.value,
    jurusan_mapel: filterJurusanMapel.value,
    kelas_mapel: filterKelasMapel.value,
    tahun_ajaran: filterTahunAjaran.value,
    jurusan: filterJurusan.value,
    status_eligible: filterStatus.value,
    no_simulasi: activeSimulasi.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['pdssMapels', 'filters'],
    onSuccess: (page) => {
      if (page.props.pdssMapels) {
        localMapels.value = JSON.parse(JSON.stringify(page.props.pdssMapels))
      }
    }
  })
}

const savePdssMapels = () => {
  if (!localMapels.value || localMapels.value.length === 0) {
    showToast('Daftar mata pelajaran masih kosong. Silakan gunakan tombol "Deteksi Otomatis dari Rapor" terlebih dahulu.', 'warning', 'Pilihan Mapel Kosong')
    return
  }
  isSavingMapels.value = true
  router.post('/pdss/simpan-mapel', {
    mapels: localMapels.value,
    tahun_ajaran: filterTahunAjaran.value,
    tahun_ajaran_id: props.filters?.tahun_ajaran_id || '',
    jurusan_id: filterJurusanMapel.value,
    kelas_id: filterKelasMapel.value,
    tenant_id: selectedTenant.value,
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: (page) => {
      isSavingMapels.value = false
      if (page?.props?.pdssMapels) {
        localMapels.value = JSON.parse(JSON.stringify(page.props.pdssMapels))
      }
      showToast('Konfigurasi pilihan mata pelajaran PDSS berhasil disimpan ke database!', 'success', 'Konfigurasi Tersimpan')
    },
    onError: (errors) => {
      isSavingMapels.value = false
      const errorMsg = (errors && typeof errors === 'object') ? Object.values(errors).flat().join(', ') : 'Terjadi kendala saat menyimpan konfigurasi mata pelajaran PDSS.'
      showToast(errorMsg, 'error', 'Gagal Menyimpan')
    }
  })
}

const selectStepTab = (step) => {
  if (step === 1 || step === '1') activeTab.value = 'mapel'
  else if (step === 2 || step === '2') activeTab.value = 'pemeringkatan'
  else if (step === 3 || step === '3') activeTab.value = 'pengunduran'
  else if (step === 4 || step === '4') activeTab.value = 'simulasi'
  else if (step === 5 || step === '5') activeTab.value = 'katalog'
}

// Super Admin helper
const getSelectedTenantName = () => {
  if (!selectedTenant.value) return 'Semua Sekolah (Super Admin)'
  const t = props.tenants?.find(item => item.id === selectedTenant.value)
  return t ? t.nama_sekolah : 'Semua Sekolah (Super Admin)'
}

const applyTenantFilter = () => {
  router.get('/bk/akademik', {
    tenant_id: selectedTenant.value,
    tahun_ajaran: filterTahunAjaran.value,
    jurusan: filterJurusan.value,
    status_eligible: filterStatus.value,
    no_simulasi: activeSimulasi.value,
    search: searchQuery.value,
  }, { preserveState: false, preserveScroll: true })
}

const applyFilters = () => {
  currentPage.value = 1
  router.get('/bk/akademik', {
    tenant_id: selectedTenant.value,
    tahun_ajaran: filterTahunAjaran.value,
    jurusan: filterJurusan.value,
    status_eligible: filterStatus.value,
    no_simulasi: activeSimulasi.value,
    search: searchQuery.value,
  }, { preserveState: true, preserveScroll: true })
}

const switchSimulasi = (simNo) => {
  activeSimulasi.value = simNo
  applyFilters()
}

const salinSimulasiAction = (fromSim, toSim) => {
  openConfirmModal({
    title: `Salin Pilihan Simulasi ${fromSim} ke Simulasi ${toSim}?`,
    message: `Seluruh data pilihan program studi dan kampus dari Simulasi ${fromSim} akan diduplikasi ke lembar kerja Simulasi ${toSim}.`,
    detail: `Data pilihan di Simulasi ${toSim} yang sudah ada akan diselaraskan dengan data sumber. Anda tetap berada di halaman BK Akademik setelah proses selesai.`,
    confirmText: `Ya, Salin ke Simulasi ${toSim}`,
    cancelText: 'Batal',
    type: 'info',
    action: () => {
      router.post('/pdss/salin-simulasi', {
        from_simulasi: fromSim,
        to_simulasi: toSim,
        tahun_ajaran: filterTahunAjaran.value,
        tenant_id: selectedTenant.value,
      }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          activeSimulasi.value = toSim
          showToast(`Berhasil menyalin data pilihan dari Simulasi ${fromSim} ke Simulasi ${toSim}!`, 'success', 'Sinkronisasi Berhasil')
          applyFilters()
        },
        onError: () => {
          showToast(`Gagal menyalin data dari Simulasi ${fromSim}. Pastikan data sumber telah terisi.`, 'error', 'Gagal Salin')
        }
      })
    }
  })
}

const kunciPermanenAction = () => {
  openConfirmModal({
    title: 'Kunci Simulasi 3 Secara PERMANEN?',
    message: 'PERINGATAN FINAL: Simulasi 3 merupakan pilihan definitif siswa yang akan diunggah ke Portal SNPMB PDSS.',
    detail: 'Setelah dikunci, pilihan tidak dapat diubah lagi secara bebas demi menjaga integritas data pendaftaran nasional.',
    confirmText: 'Ya, Kunci Permanen Sekarang',
    cancelText: 'Batal',
    type: 'danger',
    action: () => {
      router.post('/pdss/kunci-permanen-simulasi', {
        tahun_ajaran: filterTahunAjaran.value,
        tenant_id: selectedTenant.value,
      }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          showToast('Simulasi 3 telah berhasil dikunci secara Permanen! Data siap diproses ke Portal SNPMB.', 'success', 'Penguncian Final Berhasil')
          applyFilters()
        },
        onError: () => {
          showToast('Gagal mengunci Simulasi 3. Silakan coba kembali.', 'error', 'Gagal Mengunci')
        }
      })
    }
  })
}

let searchDebounce = null
const handleSearchDebounce = () => {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    applyFilters()
  }, 350)
}

const resetFilters = () => {
  filterJurusan.value = ''
  filterTahunAjaran.value = props.filters?.tahun_ajaran || '2026/2027'
  filterStatus.value = ''
  searchQuery.value = ''
  applyFilters()
}

// Client-side pagination for ranking table
const paginatedRankingList = computed(() => {
  const list = props.rankingData?.ranking_list || []
  const start = (currentPage.value - 1) * perPage.value
  return list.slice(start, start + perPage.value)
})

const totalPages = computed(() => {
  const total = props.rankingData?.ranking_list?.length || 0
  return Math.max(1, Math.ceil(total / perPage.value))
})

const paginationInfo = computed(() => {
  const total = props.rankingData?.ranking_list?.length || 0
  const from = total > 0 ? (currentPage.value - 1) * perPage.value + 1 : 0
  const to = Math.min(currentPage.value * perPage.value, total)
  return { total, from, to }
})

const goToClientPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}

// Smart windowing pagination links helper
const smartPageNumbers = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const result = []

  if (total <= 7) {
    for (let i = 1; i <= total; i++) result.push(i)
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) result.push(i)
      result.push('...')
      result.push(total)
    } else if (current >= total - 3) {
      result.push(1)
      result.push('...')
      for (let i = total - 4; i <= total; i++) result.push(i)
    } else {
      result.push(1)
      result.push('...')
      for (let i = current - 1; i <= current + 1; i++) result.push(i)
      result.push('...')
      result.push(total)
    }
  }
  return result
})

// Modal: Pilihan Kampus
const showModalPilihan = ref(false)
const selectedSiswaForPilihan = ref(null)
const prodiQuery = ref('')
const prodiOptions = ref([])
const loadingProdi = ref(false)
const formPilihan = useForm({
  siswa_id: '',
  no_pilihan: 1,
  kampus_id: '',
  prodi_id: '',
  no_simulasi: 1,
  tahun_ajaran: '',
})

const openModalPilihan = (siswa, noPilihan) => {
  selectedSiswaForPilihan.value = siswa
  formPilihan.siswa_id = siswa.siswa_id || siswa.id
  formPilihan.no_pilihan = noPilihan
  formPilihan.no_simulasi = activeSimulasi.value
  formPilihan.tahun_ajaran = filterTahunAjaran.value

  const existingChoice = noPilihan === 1 ? siswa.pilihan_1 : siswa.pilihan_2
  formPilihan.kampus_id = existingChoice?.kampus_id || ''
  formPilihan.prodi_id = existingChoice?.prodi_id || ''
  prodiQuery.value = existingChoice ? `${existingChoice.nama_prodi} - ${existingChoice.nama_kampus}` : ''
  prodiOptions.value = []
  showModalPilihan.value = true
}

let searchTimer = null
const searchProdiLive = () => {
  clearTimeout(searchTimer)
  if (prodiQuery.value.length < 2) {
    prodiOptions.value = []
    return
  }
  loadingProdi.value = true
  searchTimer = setTimeout(async () => {
    try {
      const res = await fetch(`/pdss/search-prodi?q=${encodeURIComponent(prodiQuery.value)}`)
      const json = await res.json()
      if (json.success) {
        prodiOptions.value = json.data
      }
    } catch (e) {
      console.error(e)
    } finally {
      loadingProdi.value = false
    }
  }, 300)
}

const selectProdiOption = (opt) => {
  formPilihan.prodi_id = opt.id
  formPilihan.kampus_id = opt.kampus_id
  prodiQuery.value = `${opt.nama_prodi} (${opt.jenjang}) - ${opt.nama_kampus}`
  prodiOptions.value = []
}

const submitPilihan = () => {
  if (!formPilihan.prodi_id || !formPilihan.kampus_id) {
    showToast('Silakan cari dan pilih salah satu program studi dari daftar pencarian terlebih dahulu!', 'warning', 'Pilihan Belum Dipilih')
    return
  }
  formPilihan.no_simulasi = activeSimulasi.value
  formPilihan.tahun_ajaran = filterTahunAjaran.value
  formPilihan.tenant_id = selectedTenant.value
  formPilihan.post('/pdss/simpan-pilihan', {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      showModalPilihan.value = false
      formPilihan.reset()
      showToast(`Pilihan ${formPilihan.no_pilihan} Simulasi ${activeSimulasi.value} berhasil disimpan!`, 'success', 'Pilihan Tersimpan')
    },
    onError: () => {
      showToast('Gagal menyimpan pilihan jurusan siswa. Silakan periksa kembali data.', 'error', 'Gagal Simpan')
    }
  })
}

// Modal: Override Eligible
const showModalOverride = ref(false)
const selectedSiswaForOverride = ref(null)
const formOverride = useForm({
  siswa_id: '',
  is_eligible: true,
  catatan: '',
  tahun_ajaran: '',
  tenant_id: '',
})

const openModalOverride = (siswa) => {
  selectedSiswaForOverride.value = siswa
  formOverride.siswa_id = siswa.siswa_id || siswa.id
  formOverride.is_eligible = (siswa.is_eligible !== false)
  formOverride.catatan = siswa.catatan_override || ''
  formOverride.tahun_ajaran = filterTahunAjaran.value
  formOverride.tenant_id = selectedTenant.value
  showModalOverride.value = true
}

const submitOverride = () => {
  formOverride.tahun_ajaran = filterTahunAjaran.value
  formOverride.tenant_id = selectedTenant.value
  formOverride.post('/pdss/override-eligible', {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      showModalOverride.value = false
      formOverride.reset()
      showToast('Status eligibilitas siswa berhasil diperbarui oleh Guru BK!', 'success', 'Eligibilitas Diperbarui')
    },
    onError: () => {
      showToast('Gagal memperbarui status eligibilitas siswa.', 'error', 'Gagal Override')
    }
  })
}

// Modal: Pengunduran Diri (Langkah 3)
const showModalPengunduran = ref(false)
const selectedSiswaForPengunduran = ref(null)
const formPengunduran = useForm({
  siswa_id: '',
  nomor_surat: 'BA-MUNDUR/PDSS/' + new Date().getFullYear() + '/' + Math.floor(1000 + Math.random() * 9000),
  tanggal_surat: new Date().toISOString().substring(0, 10),
  alasan: '',
  berkas: null,
  tahun_ajaran: '',
  tenant_id: '',
})

const openModalPengunduran = (siswa) => {
  selectedSiswaForPengunduran.value = siswa
  formPengunduran.siswa_id = siswa.siswa_id || siswa.id
  formPengunduran.tahun_ajaran = filterTahunAjaran.value
  formPengunduran.tenant_id = selectedTenant.value
  formPengunduran.nomor_surat = 'BA-MUNDUR/PDSS/' + new Date().getFullYear() + '/' + Math.floor(1000 + Math.random() * 9000)
  formPengunduran.tanggal_surat = new Date().toISOString().substring(0, 10)
  formPengunduran.alasan = ''
  formPengunduran.berkas = null
  showModalPengunduran.value = true
}

const handleFilePengunduran = (e) => {
  formPengunduran.berkas = e.target.files[0]
}

const submitPengunduran = () => {
  if (!formPengunduran.alasan) {
    showToast('Alasan pengunduran diri wajib diisi secara lengkap.', 'warning', 'Form Belum Lengkap')
    return
  }
  formPengunduran.tahun_ajaran = filterTahunAjaran.value
  formPengunduran.tenant_id = selectedTenant.value
  formPengunduran.post('/pdss/pengunduran-diri', {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      showModalPengunduran.value = false
      formPengunduran.reset()
      showToast('Pengunduran diri berhasil dicatat! Slot kuota eligible otomatis dialihkan ke peringkat cadangan.', 'success', 'Pengunduran Diri Diproses')
    },
    onError: () => {
      showToast('Gagal memproses surat pengunduran diri. Periksa format berkas atau data input.', 'error', 'Gagal Simpan')
    }
  })
}

// Lock Workflow Step dengan Modern Confirm Modal
const toggleLockStep = (stepNumber, currentLockState) => {
  const nextState = !currentLockState
  const actionName = nextState ? 'Kunci' : 'Buka Kunci'
  
  openConfirmModal({
    title: `${actionName} Tahap ${stepNumber}?`,
    message: `Apakah Anda yakin ingin ${nextState ? 'mengunci' : 'membuka kunci'} Tahap ${stepNumber} pada alur kerja PDSS SNBP T.A. ${filterTahunAjaran.value}?`,
    detail: nextState 
      ? 'Tahap yang dikunci akan membatasi perubahan data agar proses verifikasi berjalan tertib.'
      : 'Tahap yang dibuka kunci memungkinkan penyesuaian data kembali oleh Guru BK atau staf terkait.',
    confirmText: `Ya, ${actionName} Tahap ${stepNumber}`,
    cancelText: 'Batal',
    type: nextState ? 'warning' : 'info',
    action: () => {
      router.post('/pdss/lock-step', {
        step: stepNumber,
        is_locked: nextState,
        tahun_ajaran: filterTahunAjaran.value,
        tenant_id: selectedTenant.value,
      }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          showToast(`Tahap ${stepNumber} berhasil ${nextState ? 'dikunci' : 'dibuka kembali'}!`, 'success', 'Status Tahap Diperbarui')
        },
        onError: () => {
          showToast(`Gagal mengubah status penguncian Tahap ${stepNumber}.`, 'error', 'Gagal Update Status')
        }
      })
    }
  })
}
</script>

<template>
  <AppLayout title="PDSS & Kesiapan SNBP">
    <div class="space-y-6">

      <!-- ═══════════════════════════════════════════════════════════
           HEADER UTAMA & FILTER TENANT SUPER ADMIN
      ════════════════════════════════════════════════════════════ -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Kesiapan Akademik & PDSS SNBP</h1>
            <span class="badge bg-blue-600 text-white font-extrabold px-2.5 py-0.5 rounded-lg text-xs shadow-2xs">
              SNPMB T.A. {{ filterTahunAjaran }}
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Alur Terpadu 5 Langkah Seleksi Nasional Berdasarkan Prestasi: Sinkronisasi Rapor, Penetapan Kuota, Pengunduran Diri & Promosi Cadangan, Simulasi PTN 3 Tahap, dan Finalisasi.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <div class="bg-emerald-50 text-emerald-800 border border-emerald-200/80 px-3.5 py-2 rounded-2xl flex items-center gap-2 shadow-2xs">
            <i class="bi bi-patch-check-fill text-emerald-600 text-base"></i>
            <span class="text-xs font-bold">{{ tenantInfo?.nama_sekolah || 'Sekolah Mitra' }}</span>
          </div>
        </div>
      </div>

      <!-- BANNER FILTER TENANT KHUSUS SUPER ADMIN -->
      <div v-if="isSuperAdmin" class="bg-gradient-to-r from-blue-900 to-indigo-950 rounded-2xl p-4 text-white shadow-md border border-blue-800/60">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-blue-300 font-bold text-lg shrink-0">
              <i class="bi bi-buildings"></i>
            </div>
            <div>
              <div class="text-xs font-bold text-blue-200 uppercase tracking-wider">Super Admin Platform View</div>
              <div class="text-sm font-black text-white">Inspeksi Data PDSS Lintas Sekolah (Multi-Tenant)</div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <label class="text-xs text-blue-200 font-bold whitespace-nowrap">Filter Sekolah:</label>
            <select v-model="selectedTenant" @change="applyTenantFilter" class="bg-blue-950/80 text-white text-xs font-bold border border-blue-400/30 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="">Semua Sekolah (Global)</option>
              <option v-for="t in filteredTenants" :key="t.id" :value="t.id">
                {{ t.nama_sekolah }} (NPSN: {{ t.npsn }})
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════
           GLOBAL FILTER BAR: TAHUN AJARAN & RIWAYAT KELAS 12
      ════════════════════════════════════════════════════════════ -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <!-- Kolom Kiri: Pilihan Tahun Ajaran Aktif & Riwayat -->
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
              <span class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                <i class="bi bi-calendar3"></i>
              </span>
              <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider">Tahun Ajaran SNBP / Riwayat Kelas</label>
                <div class="flex items-center gap-2 mt-0.5">
                  <select v-model="filterTahunAjaran" @change="applyFilters" class="h-9 px-3.5 pr-8 rounded-xl border border-slate-300/80 bg-slate-50 text-xs font-black text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition cursor-pointer">
                    <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.nama_tahun_ajaran">
                      T.A. {{ ta.nama_tahun_ajaran }} {{ ta.is_active ? '★ (Aktif Sekarang)' : '(Arsip Riwayat)' }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Badge Cohort Info -->
            <div class="flex flex-wrap items-center gap-2 pt-2 sm:pt-0">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200/60 shadow-2xs">
                <i class="bi bi-mortarboard-fill text-blue-600"></i>
                {{ rankingData?.total_siswa || 0 }} Siswa Kelas 12 Terdata di Riwayat Kelas
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200/60 shadow-2xs">
                <i class="bi bi-shield-check text-emerald-600"></i>
                Kuota Akreditasi {{ tenantInfo?.akreditasi }} ({{ tenantInfo?.kuota_persen }}% = {{ tenantInfo?.kuota_eligible }} Siswa Eligible)
              </span>
            </div>
          </div>

          <!-- Kolom Kanan: Navigasi Status Workflow -->
          <div class="flex items-center gap-2 self-start lg:self-center">
            <span class="text-[11px] font-bold text-slate-400">Status Alur T.A. {{ filterTahunAjaran }}:</span>
            <span class="px-2.5 py-1 rounded-lg text-xs font-black"
                  :class="workflowStatus[4]?.is_locked ? 'bg-purple-100 text-purple-800 border border-purple-200' : (workflowStatus[2]?.is_locked ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200')">
              {{ workflowStatus[4]?.is_locked ? '🔒 Terkunci Permanen' : (workflowStatus[2]?.is_locked ? '⚡ Kuota Ditetapkan' : '📝 Pengisian & Simulasi') }}
            </span>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════
           5-STEP VISUAL WORKFLOW PROGRESS BAR (INTERAKTIF)
      ════════════════════════════════════════════════════════════ -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <i class="bi bi-diagram-3-fill text-blue-600"></i>
            <h2 class="text-xs font-black text-slate-700 uppercase tracking-wider">Alur Kerja 5 Langkah Baku PDSS SNBP</h2>
          </div>
          <span class="text-2xs text-slate-400 font-medium">Klik pada salah satu langkah untuk navigasi cepat</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          <!-- Step 1: Pilihan Mapel & Nilai Rapor -->
          <div @click="selectStepTab(1)" 
               class="p-3 rounded-xl border transition relative group cursor-pointer"
               :class="activeTab === 'mapel' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[1]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1">
              <span class="text-2xs font-extrabold px-2 py-0.5 rounded-md"
                    :class="activeTab === 'mapel' ? 'bg-blue-600 text-white' : (workflowStatus[1]?.is_locked ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700')">
                Langkah 1
              </span>
              <button @click.stop="toggleLockStep(1, workflowStatus[1]?.is_locked)" type="button" class="text-xs" :title="workflowStatus[1]?.is_locked ? 'Buka Kunci' : 'Kunci Tahap Ini'">
                <i v-if="workflowStatus[1]?.is_locked" class="bi bi-lock-fill text-emerald-600"></i>
                <i v-else class="bi bi-unlock text-slate-400 hover:text-blue-600"></i>
              </button>
            </div>
            <div class="text-xs font-bold text-slate-800">1. Pilihan Mapel & Rapor</div>
            <p class="text-[10px] text-slate-500 mt-0.5">Pilih mapel & nilai rapor 5 semester</p>
          </div>

          <!-- Step 2: Penetapan Kuota & Pemeringkatan -->
          <div @click="selectStepTab(2)" 
               class="p-3 rounded-xl border transition relative group cursor-pointer"
               :class="activeTab === 'pemeringkatan' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[2]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1">
              <span class="text-2xs font-extrabold px-2 py-0.5 rounded-md"
                    :class="activeTab === 'pemeringkatan' ? 'bg-blue-600 text-white' : (workflowStatus[2]?.is_locked ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700')">
                Langkah 2
              </span>
              <button @click.stop="toggleLockStep(2, workflowStatus[2]?.is_locked)" type="button" class="text-xs" :title="workflowStatus[2]?.is_locked ? 'Buka Kunci' : 'Kunci Tahap Ini'">
                <i v-if="workflowStatus[2]?.is_locked" class="bi bi-lock-fill text-emerald-600"></i>
                <i v-else class="bi bi-unlock text-slate-400 hover:text-blue-600"></i>
              </button>
            </div>
            <div class="text-xs font-bold text-slate-800">2. Kuota & Pemeringkatan</div>
            <p class="text-[10px] text-slate-500 mt-0.5">Penetapan kuota akreditasi & ranking</p>
          </div>

          <!-- Step 3: Pengunduran Diri & Kuota Pengganti (SEBELUM PEMILIHAN PTN) -->
          <div @click="selectStepTab(3)" 
               class="p-3 rounded-xl border transition relative group cursor-pointer"
               :class="activeTab === 'pengunduran' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[3]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1">
              <span class="text-2xs font-extrabold px-2 py-0.5 rounded-md"
                    :class="activeTab === 'pengunduran' ? 'bg-blue-600 text-white' : (workflowStatus[3]?.is_locked ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700')">
                Langkah 3
              </span>
              <button @click.stop="toggleLockStep(3, workflowStatus[3]?.is_locked)" type="button" class="text-xs" :title="workflowStatus[3]?.is_locked ? 'Buka Kunci' : 'Kunci Tahap Ini'">
                <i v-if="workflowStatus[3]?.is_locked" class="bi bi-lock-fill text-emerald-600"></i>
                <i v-else class="bi bi-unlock text-slate-400 hover:text-blue-600"></i>
              </button>
            </div>
            <div class="text-xs font-bold text-slate-800">3. Mundur & Cadangan</div>
            <p class="text-[10px] text-slate-500 mt-0.5">Siswa mundur & promosi kuota</p>
          </div>

          <!-- Step 4: Simulasi Pilihan PTN (Sim 1, 2, 3 Permanen) -->
          <div @click="selectStepTab(4)" 
               class="p-3 rounded-xl border transition relative group cursor-pointer"
               :class="activeTab === 'simulasi' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[4]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1">
              <span class="text-2xs font-extrabold px-2 py-0.5 rounded-md"
                    :class="activeTab === 'simulasi' ? 'bg-blue-600 text-white' : (workflowStatus[4]?.is_locked ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700')">
                Langkah 4
              </span>
              <button @click.stop="toggleLockStep(4, workflowStatus[4]?.is_locked)" type="button" class="text-xs" :title="workflowStatus[4]?.is_locked ? 'Buka Kunci' : 'Kunci Tahap Ini'">
                <i v-if="workflowStatus[4]?.is_locked" class="bi bi-lock-fill text-emerald-600"></i>
                <i v-else class="bi bi-unlock text-slate-400 hover:text-blue-600"></i>
              </button>
            </div>
            <div class="text-xs font-bold text-slate-800">4. Simulasi PTN (1, 2, 3)</div>
            <p class="text-[10px] text-slate-500 mt-0.5">Simulasi draf, rasionalisasi & permanen</p>
          </div>

          <!-- Step 5: Katalog Master PTN & Finalisasi -->
          <div @click="selectStepTab(5)" 
               class="p-3 rounded-xl border transition relative group cursor-pointer"
               :class="activeTab === 'katalog' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[5]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1">
              <span class="text-2xs font-extrabold px-2 py-0.5 rounded-md"
                    :class="activeTab === 'katalog' ? 'bg-blue-600 text-white' : (workflowStatus[5]?.is_locked ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700')">
                Langkah 5
              </span>
              <button @click.stop="toggleLockStep(5, workflowStatus[5]?.is_locked)" type="button" class="text-xs" :title="workflowStatus[5]?.is_locked ? 'Buka Kunci' : 'Kunci Tahap Ini'">
                <i v-if="workflowStatus[5]?.is_locked" class="bi bi-lock-fill text-emerald-600"></i>
                <i v-else class="bi bi-unlock text-slate-400 hover:text-blue-600"></i>
              </button>
            </div>
            <div class="text-xs font-bold text-slate-800">5. Katalog & Finalisasi</div>
            <p class="text-[10px] text-slate-500 mt-0.5">Master prodi SNPMB & ekspor</p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════
           6 KARTU KPI RINGKASAN METRIK PDSS
      ════════════════════════════════════════════════════════════ -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Akreditasi Sekolah</div>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-xl font-black text-blue-600">{{ tenantInfo?.akreditasi || 'A' }}</span>
            <span class="text-2xs font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700">Kuota {{ tenantInfo?.kuota_persen || 40 }}%</span>
          </div>
        </div>

        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Siswa Kls XII</div>
          <div class="text-xl font-black text-slate-800 mt-1">{{ tenantInfo?.total_siswa || 0 }} <span class="text-xs font-normal text-slate-400">siswa</span></div>
        </div>

        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Maks Kuota Eligible</div>
          <div class="text-xl font-black text-indigo-600 mt-1">{{ tenantInfo?.kuota_eligible || 0 }} <span class="text-xs font-normal text-slate-400">slot</span></div>
        </div>

        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Eligible Aktif</div>
          <div class="text-xl font-black text-emerald-600 mt-1">{{ tenantInfo?.active_eligible || 0 }} <span class="text-xs font-normal text-slate-400">siswa</span></div>
        </div>

        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pengunduran Diri</div>
          <div class="text-xl font-black text-amber-600 mt-1">{{ tenantInfo?.total_pengunduran || 0 }} <span class="text-xs font-normal text-slate-400">siswa</span></div>
        </div>

        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tabrakan Jurusan</div>
          <div class="text-xl font-black mt-1" :class="tenantInfo?.total_tabrakan > 0 ? 'text-rose-600' : 'text-slate-700'">
            {{ tenantInfo?.total_tabrakan || 0 }} <span class="text-xs font-normal text-slate-400">prodi</span>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════
           NAV TABS HORIZONTAL 3-WAY SCROLLER
      ════════════════════════════════════════════════════════════ -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <!-- Panah Kiri -->
          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navTabsPdss')?.scrollBy({ left: -220, behavior: 'smooth' })"
                  title="Geser ke Kiri">
            <i class="bi bi-chevron-left"></i>
          </button>

          <!-- Deretan Tab -->
          <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsPdss" role="tablist">
              <li class="nav-item">
                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeTab === 'mapel' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'mapel'">
                  <i class="bi bi-journal-bookmark-fill text-sm"></i> 1. Pilihan Mapel & Rapor (Langkah 1)
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeTab === 'pemeringkatan' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'pemeringkatan'">
                  <i class="bi bi-trophy-fill text-sm"></i> 2. Pemeringkatan SNBP & Kuota (Langkah 2)
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeTab === 'pengunduran' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'pengunduran'">
                  <i class="bi bi-person-x-fill text-sm"></i> 3. Pengunduran Diri & Kuota Pengganti (Langkah 3)
                  <span v-if="tenantInfo?.total_pengunduran > 0" class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-amber-500 text-white">
                    {{ tenantInfo?.total_pengunduran }}
                  </span>
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeTab === 'simulasi' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'simulasi'">
                  <i class="bi bi-pie-chart-fill text-sm"></i> 4. Simulasi Pilihan PTN & Tabrakan (Langkah 4)
                  <span v-if="collisions.length > 0" class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-rose-500 text-white">
                    {{ collisions.length }}
                  </span>
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeTab === 'katalog' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'katalog'">
                  <i class="bi bi-building text-sm"></i> 5. Master Katalog PTN & Finalisasi
                </button>
              </li>
            </ul>
          </div>

          <!-- Panah Kanan -->
          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navTabsPdss')?.scrollBy({ left: 220, behavior: 'smooth' })"
                  title="Geser ke Kanan">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════
           TAB 1: KONFIGURASI MATA PELAJARAN & KELAYAKAN RAPOR (LANGKAH 1)
      ════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'mapel'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden space-y-5 p-5">
        
        <!-- Header Info Langkah 1 -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl bg-gradient-to-r from-indigo-50/80 via-blue-50/40 to-slate-50 border border-indigo-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-base shadow-sm shrink-0">
              1
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-800">Langkah 1: Tentukan Mata Pelajaran Acuan PDSS & Verifikasi Nilai Rapor</h3>
              <p class="text-xs text-slate-500 mt-0.5">Atur mata pelajaran yang dihitung nilainya selama Semester 1 s.d. Semester 5/6 secara dinamis per Rombel / Jurusan (MIPA, IPS, Fase F, SMK).</p>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <button @click="autoDetectFromRapor" :disabled="isAutoDetecting || workflowStatus[1]?.is_locked" type="button" class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-white rounded-xl px-3.5 py-1.5 text-xs font-bold shadow-2xs transition flex items-center gap-1.5">
              <i v-if="isAutoDetecting" class="spinner-border spinner-border-sm me-1"></i>
              <i v-else class="bi bi-magic text-sm"></i> Deteksi Otomatis dari Rapor
            </button>
            <button @click="toggleWajibNasional" type="button" class="btn btn-sm bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200 rounded-xl px-3 py-1.5 text-xs font-bold shadow-2xs transition">
              <i class="bi bi-shield-check me-1"></i> Centang Wajib Nasional
            </button>
            <button @click="toggleAllSemesters(true)" type="button" class="btn btn-sm bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold shadow-2xs transition">
              <i class="bi bi-check-all text-slate-600 me-1"></i> Pilih Semua
            </button>
            <button @click="savePdssMapels" :disabled="isSavingMapels || workflowStatus[1]?.is_locked" type="button" class="btn btn-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-1.5 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
              <i v-if="isSavingMapels" class="spinner-border spinner-border-sm me-1"></i>
              <i v-else class="bi bi-floppy-fill me-1"></i> Simpan Pilihan Mapel
            </button>
          </div>
        </div>

        <!-- Toolbar Filter Rumpun / Rombel Mapel -->
        <div class="p-3.5 bg-slate-50/70 border border-slate-200/80 rounded-xl flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2.5 flex-wrap">
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Rumpun / Jurusan</label>
              <select v-model="filterJurusanMapel" @change="applyMapelFilter" class="form-select form-select-sm rounded-xl border-slate-200 text-xs font-bold text-slate-700 bg-white shadow-2xs focus:ring-indigo-500 py-1.5 px-3">
                <option value="">Semua Rumpun & Kurikulum</option>
                <option value="MIPA">MIPA / Saintek</option>
                <option value="IPS">IPS / Soshum</option>
              </select>
            </div>

            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Kelas / Rombel Kls XII</label>
              <select v-model="filterKelasMapel" @change="applyMapelFilter" class="form-select form-select-sm rounded-xl border-slate-200 text-xs font-bold text-slate-700 bg-white shadow-2xs focus:ring-indigo-500 py-1.5 px-3">
                <option value="">Semua Kelas (Global)</option>
                <option v-for="k in kelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
              </select>
            </div>
          </div>

          <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
            <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center gap-1.5">
              <i class="bi bi-check2-square text-sm"></i> {{ selectedMapelsCount }} Mapel Aktif
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 border border-slate-200">
              Total {{ localMapels.length }} Terdaftar
            </span>
          </div>
        </div>

        <!-- Banner Deteksi Otomatis -->
        <div v-if="autoDetectNotice" class="p-3.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-800 text-xs flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i class="bi bi-stars text-amber-600 text-base"></i>
            <span>{{ autoDetectNotice }}</span>
          </div>
          <button @click="autoDetectNotice = ''" type="button" class="text-amber-600 hover:text-amber-800 font-bold text-xs">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <!-- KPI Kelengkapan Rapor -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Mata Pelajaran Wajib</div>
              <div class="text-xl font-black text-indigo-600 mt-1">{{ mapelWajibList.length }} <span class="text-xs text-slate-400 font-normal">mapel nasional</span></div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-lg">
              <i class="bi bi-shield-check"></i>
            </div>
          </div>

          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Mapel Peminatan / Pilihan</div>
              <div class="text-xl font-black text-blue-600 mt-1">{{ mapelPilihanList.length }} <span class="text-xs text-slate-400 font-normal">mapel rumpun</span></div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg">
              <i class="bi bi-diagram-3-fill"></i>
            </div>
          </div>

          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Kunci Langkah 1</div>
              <div class="text-sm font-black mt-1" :class="workflowStatus[1]?.is_locked ? 'text-emerald-600' : 'text-slate-600'">
                {{ workflowStatus[1]?.is_locked ? 'Terkunci Aman' : 'Terbuka (Dapat Diedit)' }}
              </div>
            </div>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg" :class="workflowStatus[1]?.is_locked ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'">
              <i class="bi" :class="workflowStatus[1]?.is_locked ? 'bi-lock-fill' : 'bi-unlock'"></i>
            </div>
          </div>
        </div>

        <!-- Tabel 1: KELOMPOK A - MUATAN WAJIB / UMUM NASIONAL -->
        <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs">
          <div class="bg-indigo-50/70 px-4 py-2.5 border-b border-indigo-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
              <span class="text-xs font-black text-indigo-900 uppercase tracking-wider">A. Kelompok Muatan Umum / Wajib Nasional (Kurikulum Merdeka & K13)</span>
            </div>
            <span class="text-2xs font-bold bg-indigo-200/70 text-indigo-800 px-2 py-0.5 rounded-md">Wajib Ada di Semua Rombel</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-4 w-12 text-center">NO</th>
                  <th class="py-2.5 px-4">Nama Mata Pelajaran Kurikulum</th>
                  <th class="py-2.5 px-3 text-center">Kategori</th>
                  <th class="py-2.5 px-3 text-center bg-blue-50/50">Sem 1 (Kls X)</th>
                  <th class="py-2.5 px-3 text-center bg-blue-50/50">Sem 2 (Kls X)</th>
                  <th class="py-2.5 px-3 text-center bg-indigo-50/50">Sem 3 (Kls XI)</th>
                  <th class="py-2.5 px-3 text-center bg-indigo-50/50">Sem 4 (Kls XI)</th>
                  <th class="py-2.5 px-3 text-center bg-purple-50/50">Sem 5 (Kls XII)</th>
                  <th class="py-2.5 px-3 text-center bg-slate-100/50">Sem 6 (Opsional)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="(m, idx) in mapelWajibList" :key="m.id" class="hover:bg-indigo-50/20 transition">
                  <td class="py-2.5 px-4 text-center font-mono text-slate-400">{{ idx + 1 }}</td>
                  <td class="py-2.5 px-4 font-bold text-slate-800">
                    {{ m.nama_mata_pelajaran }}
                    <span v-if="m.deskripsi" class="block text-[10px] font-normal text-slate-400 mt-0.5">{{ m.deskripsi }}</span>
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 uppercase">
                      {{ m.kategori || 'Wajib Nasional' }}
                    </span>
                  </td>
                  <td class="py-2.5 px-3 text-center bg-blue-50/20">
                    <input type="checkbox" v-model="m.sem_1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-blue-50/20">
                    <input type="checkbox" v-model="m.sem_2" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-indigo-50/20">
                    <input type="checkbox" v-model="m.sem_3" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-indigo-50/20">
                    <input type="checkbox" v-model="m.sem_4" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-purple-50/20">
                    <input type="checkbox" v-model="m.sem_5" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-slate-100/30">
                    <input type="checkbox" v-model="m.sem_6" class="rounded border-slate-300 text-slate-600 focus:ring-slate-500 w-4 h-4 cursor-pointer" />
                  </td>
                </tr>
                <tr v-if="mapelWajibList.length === 0">
                  <td colspan="9" class="py-6 text-center text-slate-400 font-medium">Tidak ada mata pelajaran wajib nasional yang ditemukan.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tabel 2: KELOMPOK B - PEMINATAN / RUMPUN KEAHLIAN / PILIHAN FASE F -->
        <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs">
          <div class="bg-blue-50/70 px-4 py-2.5 border-b border-blue-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-blue-600"></span>
              <span class="text-xs font-black text-blue-900 uppercase tracking-wider">B. Kelompok Peminatan / Rumpun Keahlian / Mapel Pilihan (Fase F)</span>
            </div>
            <span class="text-2xs font-bold bg-blue-200/70 text-blue-800 px-2 py-0.5 rounded-md">Disesuaikan Berdasarkan Rombel Siswa</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-4 w-12 text-center">NO</th>
                  <th class="py-2.5 px-4">Nama Mata Pelajaran Kurikulum</th>
                  <th class="py-2.5 px-3 text-center">Kategori</th>
                  <th class="py-2.5 px-3 text-center bg-blue-50/50">Sem 1 (Kls X)</th>
                  <th class="py-2.5 px-3 text-center bg-blue-50/50">Sem 2 (Kls X)</th>
                  <th class="py-2.5 px-3 text-center bg-indigo-50/50">Sem 3 (Kls XI)</th>
                  <th class="py-2.5 px-3 text-center bg-indigo-50/50">Sem 4 (Kls XI)</th>
                  <th class="py-2.5 px-3 text-center bg-purple-50/50">Sem 5 (Kls XII)</th>
                  <th class="py-2.5 px-3 text-center bg-slate-100/50">Sem 6 (Opsional)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="(m, idx) in mapelPilihanList" :key="m.id" class="hover:bg-blue-50/20 transition">
                  <td class="py-2.5 px-4 text-center font-mono text-slate-400">{{ idx + 1 }}</td>
                  <td class="py-2.5 px-4 font-bold text-slate-800">
                    {{ m.nama_mata_pelajaran }}
                    <span v-if="m.deskripsi" class="block text-[10px] font-normal text-slate-400 mt-0.5">{{ m.deskripsi }}</span>
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase" :class="m.kategori?.includes('MIPA') ? 'bg-emerald-50 text-emerald-700' : (m.kategori?.includes('IPS') ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700')">
                      {{ m.kategori || 'Peminatan' }}
                    </span>
                  </td>
                  <td class="py-2.5 px-3 text-center bg-blue-50/20">
                    <input type="checkbox" v-model="m.sem_1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-blue-50/20">
                    <input type="checkbox" v-model="m.sem_2" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-indigo-50/20">
                    <input type="checkbox" v-model="m.sem_3" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-indigo-50/20">
                    <input type="checkbox" v-model="m.sem_4" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-purple-50/20">
                    <input type="checkbox" v-model="m.sem_5" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500 w-4 h-4 cursor-pointer" />
                  </td>
                  <td class="py-2.5 px-3 text-center bg-slate-100/30">
                    <input type="checkbox" v-model="m.sem_6" class="rounded border-slate-300 text-slate-600 focus:ring-slate-500 w-4 h-4 cursor-pointer" />
                  </td>
                </tr>
                <tr v-if="mapelPilihanList.length === 0">
                  <td colspan="9" class="py-6 text-center text-slate-400 font-medium">Tidak ada mata pelajaran peminatan/pilihan pada filter ini.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tombol Lanjut ke Langkah 2 -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <span class="text-xs text-slate-500 font-medium">Setelah konfigurasi mapel tersimpan, lanjutkan ke penetapan kuota & pemeringkatan di Langkah 2.</span>
          <button @click="activeTab = 'pemeringkatan'" type="button" class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
            Lanjut ke Langkah 2: Pemeringkatan Kuota <i class="bi bi-arrow-right"></i>
          </button>
        </div>

      </div>

      <!-- ═══════════════════════════════════════════════════════════
           TAB 2: PEMERINGKATAN SISWA SNBP & PENETAPAN KUOTA (LANGKAH 2)
      ════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'pemeringkatan'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden space-y-4 p-5">
        
        <!-- Header Info Langkah 2 -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl bg-gradient-to-r from-emerald-50/80 to-blue-50/40 border border-emerald-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-base shadow-sm shrink-0">
              2
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-800">Langkah 2: Atur Kuota Eligible & Pemeringkatan Nilai 5 Semester</h3>
              <p class="text-xs text-slate-500 mt-0.5">Penetapan kuota siswa eligible berdasarkan akreditasi sekolah Kemendikbud & pemeringkatan paralel nilai rapor siswa.</p>
            </div>
          </div>

          <div class="flex items-center gap-3 flex-wrap">
            <div class="bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs text-xs flex items-center gap-2">
              <span class="text-slate-500 font-semibold">Akreditasi:</span>
              <span class="badge bg-blue-600 text-white font-extrabold px-2 py-0.5 rounded-md">{{ tenantInfo?.akreditasi || 'A' }}</span>
              <span class="text-emerald-700 font-bold border-l pl-2">Kuota {{ tenantInfo?.kuota_persen || 40 }}%</span>
            </div>
          </div>
        </div>
        
        <!-- Filter Bar Atas 3-Bagian Baku -->
        <div class="p-3.5 bg-slate-50/70 border border-slate-200/80 rounded-xl overflow-x-auto no-scrollbar">
          <form @submit.prevent="applyFilters" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
            
            <!-- Filter Jurusan -->
            <div class="w-36 sm:w-44 shrink-0">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Jurusan / Peminatan</label>
              <select v-model="filterJurusan" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="">-- Semua Jurusan --</option>
                <option v-for="j in jurusanList" :key="j.id" :value="j.nama_jurusan">{{ j.nama_jurusan }}</option>
              </select>
            </div>

            <!-- Filter Tahun Ajaran SNBP -->
            <div class="w-36 sm:w-44 shrink-0">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Tahun Ajaran SNBP</label>
              <select v-model="filterTahunAjaran" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.nama_tahun_ajaran">
                  T.A. {{ ta.nama_tahun_ajaran }} {{ ta.is_active ? '(Aktif)' : '' }}
                </option>
              </select>
            </div>

            <!-- Filter Status Kelayakan -->
            <div class="w-36 sm:w-44 shrink-0">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Status Kelayakan</label>
              <select v-model="filterStatus" @change="applyFilters" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="">-- Semua Status --</option>
                <option value="eligible">Eligible Kuota SNBP</option>
                <option value="promoted">Eligible (Promosi Cadangan)</option>
                <option value="not_eligible">Tidak Eligible</option>
                <option value="resigned">Mengundurkan Diri</option>
              </select>
            </div>

            <!-- Input Pencarian Proporsional -->
            <div class="w-64 sm:w-72 md:w-80 shrink-0">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Pencarian Siswa</label>
              <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       v-model="searchQuery" 
                       @input="handleSearchDebounce"
                       placeholder="Cari nama, NISN, atau NIS..." 
                       class="w-full h-9 pl-9 pr-8 rounded-xl border border-slate-200 bg-white text-xs text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                <button v-if="searchQuery" 
                        @click="searchQuery = ''; applyFilters()" 
                        type="button" 
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                  <i class="bi bi-x-circle-fill"></i>
                </button>
              </div>
            </div>

            <!-- Tombol Cari & Reset -->
            <div class="flex items-center gap-1.5 shrink-0">
              <button type="submit" class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-1">
                <i class="bi bi-search"></i> Cari
              </button>
              <button type="button" @click="resetFilters" class="h-9 px-3 rounded-xl bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 text-xs font-bold transition" title="Reset Filter">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>

          </form>
        </div>

        <!-- Tabel Data Pemeringkatan -->
        <div class="border border-slate-200/80 rounded-xl overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="py-3 px-4 w-12 text-center">Rank</th>
                  <th class="py-3 px-4">Nama Siswa / Identitas</th>
                  <th class="py-3 px-3">Jurusan / Kelas</th>
                  <th class="py-3 px-3 text-center">Nilai Rata-rata 5 Sem</th>
                  <th class="py-3 px-3 text-center">Prestasi</th>
                  <th class="py-3 px-3 text-center">Status Kelayakan SNBP</th>
                  <th class="py-3 px-4 text-center sticky right-0 bg-slate-50 z-10 w-24">Aksi BK</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="item in paginatedRankingList" :key="item.siswa_id" 
                    class="hover:bg-blue-50/40 transition group"
                    :class="{'bg-emerald-50/20': item.is_eligible && !item.is_cadangan_promosi, 'bg-purple-50/20': item.is_cadangan_promosi, 'bg-amber-50/20': item.status_pengunduran_diri}">
                  
                  <!-- Rank Badge -->
                  <td class="py-3 px-4 text-center">
                    <span class="w-6 h-6 rounded-full inline-flex items-center justify-center font-black text-2xs"
                          :class="item.ranking_sekolah <= (tenantInfo?.kuota_eligible || 0) ? 'bg-blue-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-500'">
                      {{ item.ranking_sekolah }}
                    </span>
                  </td>

                  <!-- Siswa Info -->
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-800">{{ item.nama_lengkap }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">NISN: {{ item.nisn }} | NIS: {{ item.nis }}</div>
                  </td>

                  <!-- Jurusan / Riwayat Kelas -->
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 block mb-0.5">
                      {{ item.jurusan }}
                    </span>
                    <span class="text-[10px] text-blue-600 font-bold font-mono">
                      <i class="bi bi-door-open me-0.5"></i>{{ item.kelas_riwayat || item.kelas_saat_ini || 'XII' }}
                    </span>
                  </td>

                  <!-- Rata-rata Nilai -->
                  <td class="py-3 px-3 text-center">
                    <span class="font-black text-sm text-blue-700 font-mono">{{ Number(item.rata_rata_nilai).toFixed(2) }}</span>
                  </td>

                  <!-- Prestasi -->
                  <td class="py-3 px-3 text-center">
                    <span v-if="item.total_prestasi > 0" class="px-2 py-0.5 rounded-full text-2xs font-extrabold bg-amber-100 text-amber-800">
                      {{ item.total_prestasi }} Sertifikat
                    </span>
                    <span v-else class="text-slate-300">-</span>
                  </td>

                  <!-- Status Kelayakan Badge -->
                  <td class="py-3 px-3 text-center">
                    <span v-if="item.status_pengunduran_diri" class="px-2.5 py-1 rounded-lg text-2xs font-black bg-amber-100 text-amber-800 border border-amber-200">
                      <i class="bi bi-person-x-fill me-1"></i> Mengundurkan Diri
                    </span>
                    <span v-else-if="item.is_cadangan_promosi" class="px-2.5 py-1 rounded-lg text-2xs font-black bg-purple-100 text-purple-800 border border-purple-200">
                      <i class="bi bi-arrow-up-circle-fill me-1"></i> Eligible (Promosi Cadangan)
                    </span>
                    <span v-else-if="item.is_eligible" class="px-2.5 py-1 rounded-lg text-2xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                      <i class="bi bi-check-circle-fill me-1"></i> Eligible Kuota ({{ tenantInfo?.kuota_persen || 40 }}%)
                    </span>
                    <span v-else class="px-2.5 py-1 rounded-lg text-2xs font-semibold bg-slate-100 text-slate-500">
                      Cadangan (Rank {{ item.ranking_sekolah }})
                    </span>
                  </td>

                  <!-- Sticky Action Column -->
                  <td class="py-3 px-4 text-center sticky right-0 bg-white group-hover:bg-blue-50/90 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] border-l border-slate-100 z-10 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1">
                      <button @click="openModalOverride(item)" type="button" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs transition" title="Override Status Eligible">
                        <i class="bi bi-sliders"></i>
                      </button>
                      <button v-if="item.is_eligible && !item.status_pengunduran_diri" @click="openModalPengunduran(item)" type="button" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs transition" title="Catat Pengunduran Diri">
                        <i class="bi bi-person-x"></i>
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="paginatedRankingList.length === 0">
                  <td colspan="7" class="py-12 text-center text-slate-400">
                    <i class="bi bi-inbox text-3xl mb-2 block text-slate-300"></i>
                    Tidak ada data siswa yang cocok dengan filter.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer Pagination Dinamis -->
          <div v-if="paginationInfo.total > 0" class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
              <span>Tampilkan</span>
              <select v-model="perPage" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span class="whitespace-nowrap">baris per halaman</span>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <span class="whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-800">{{ paginationInfo.from }}</span> s.d. <span class="font-bold text-slate-800">{{ paginationInfo.to }}</span> dari <span class="font-bold text-slate-800">{{ paginationInfo.total }}</span> siswa
              </span>
            </div>

            <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
              <button type="button" @click="goToClientPage(currentPage - 1)" :disabled="currentPage === 1" class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs disabled:opacity-40">
                <i class="bi bi-chevron-left text-xs"></i>
              </button>

              <template v-for="(p, i) in smartPageNumbers" :key="i">
                <button v-if="p !== '...'" type="button" @click="goToClientPage(p)" class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center" :class="p === currentPage ? 'bg-blue-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs'">
                  {{ p }}
                </button>
                <span v-else class="min-w-[32px] h-8 text-xs font-bold flex items-center justify-center text-slate-400">...</span>
              </template>

              <button type="button" @click="goToClientPage(currentPage + 1)" :disabled="currentPage === totalPages" class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs disabled:opacity-40">
                <i class="bi bi-chevron-right text-xs"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Tombol Lanjut ke Langkah 3 -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <span class="text-xs text-slate-500 font-medium">Bila ada siswa eligible yang mundur (memilih kedinasan/swasta/LN), catat pengunduran diri di Langkah 3 sebelum simulasi PTN.</span>
          <button @click="activeTab = 'pengunduran'" type="button" class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
            Lanjut ke Langkah 3: Pengunduran Diri & Cadangan <i class="bi bi-arrow-right"></i>
          </button>
        </div>

      </div>

      <!-- ═══════════════════════════════════════════════════════════
           TAB 3: PENGUNDURAN DIRI & KUOTA PENGGANTI (LANGKAH 3)
      ════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'pengunduran'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden space-y-5 p-5">
        
        <!-- Header Info Langkah 3 -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl bg-gradient-to-r from-amber-50/80 to-purple-50/40 border border-amber-200">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center font-black text-base shadow-sm shrink-0">
              3
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-800">Langkah 3: Pengunduran Diri Siswa Eligible & Promosi Kuota Cadangan</h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Pencatatan resmi siswa eligible yang mengundurkan diri (kedinasan, kuliah luar negeri, swasta, atau bekerja). 
                <strong>Slot kuota otomatis dialihkan dan dipromosikan ke siswa cadangan peringkat berikutnya SEBELUM memulai simulasi pilihan kampus PTN.</strong>
              </p>
            </div>
          </div>
        </div>

        <!-- 3 Kartu Metrik Pengunduran & Kuota Pengganti -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Kuota SNBP</div>
              <div class="text-xl font-black text-blue-600 mt-1">{{ tenantInfo?.kuota_eligible || 0 }} <span class="text-xs text-slate-400 font-normal">siswa</span></div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg">
              <i class="bi bi-people-fill"></i>
            </div>
          </div>

          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Siswa Mengundurkan Diri</div>
              <div class="text-xl font-black text-amber-600 mt-1">{{ pengunduranDiriList?.length || 0 }} <span class="text-xs text-slate-400 font-normal">siswa</span></div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-lg">
              <i class="bi bi-person-dash-fill"></i>
            </div>
          </div>

          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Siswa Cadangan Dipromosikan</div>
              <div class="text-xl font-black text-purple-600 mt-1">{{ tenantInfo?.promoted_count || pengunduranDiriList?.length || 0 }} <span class="text-xs text-slate-400 font-normal">siswa naik</span></div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-lg">
              <i class="bi bi-arrow-up-circle-fill"></i>
            </div>
          </div>
        </div>

        <!-- Tabel Berita Acara Pengunduran Diri -->
        <div class="border border-slate-200/80 rounded-xl overflow-hidden">
          <div class="p-3.5 bg-slate-50/70 border-b border-slate-200 flex justify-between items-center">
            <h4 class="font-bold text-slate-800 text-xs flex items-center gap-2">
              <i class="bi bi-file-earmark-text-fill text-amber-600"></i>
              Daftar Berita Acara Pengunduran Diri Siswa
            </h4>
            <span class="text-2xs text-slate-500 font-medium">Setiap siswa yang mundur otomatis membebaskan slot untuk siswa cadangan</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="py-3 px-4">Nama Siswa / Identitas</th>
                  <th class="py-3 px-3">No. Berita Acara</th>
                  <th class="py-3 px-3">Tanggal Surat</th>
                  <th class="py-3 px-4">Alasan Pengunduran</th>
                  <th class="py-3 px-3 text-center">Status Verifikasi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="p in pengunduranDiriList" :key="p.id" class="hover:bg-slate-50 transition">
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-800">{{ p.siswa?.nama_lengkap ?? 'Siswa' }}</div>
                    <div class="text-[10px] text-slate-400">NISN: {{ p.siswa?.nisn || '-' }}</div>
                  </td>
                  <td class="py-3 px-3 font-mono text-xs font-semibold text-slate-700">{{ p.nomor_surat }}</td>
                  <td class="py-3 px-3">{{ p.tanggal_surat }}</td>
                  <td class="py-3 px-4 text-slate-600">{{ p.alasan }}</td>
                  <td class="py-3 px-3 text-center">
                    <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                      {{ p.status_verifikasi || 'Terverifikasi' }}
                    </span>
                  </td>
                </tr>
                <tr v-if="pengunduranDiriList?.length === 0">
                  <td colspan="5" class="py-10 text-center text-slate-400">
                    <i class="bi bi-emoji-smile text-2xl mb-1 block text-slate-300"></i>
                    Belum ada siswa eligible yang mengundurkan diri. Seluruh kuota terisi penuh oleh siswa peringkat awal.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tombol Lanjut ke Langkah 4 -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <span class="text-xs text-slate-500 font-medium">Daftar siswa eligible aktif (termasuk siswa cadangan promosi) kini siap mengikuti Simulasi Pilihan PTN di Langkah 4.</span>
          <button @click="activeTab = 'simulasi'" type="button" class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
            Lanjut ke Langkah 4: Simulasi Pilihan PTN & Tabrakan <i class="bi bi-arrow-right"></i>
          </button>
        </div>

      </div>

      <!-- ═══════════════════════════════════════════════════════════
           TAB 4: SIMULASI PILIHAN PTN & TABRAKAN (LANGKAH 4) - SIMULASI 1, 2, 3
      ════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'simulasi'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden space-y-5 p-5">
        
        <!-- Header Info Langkah 4 -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl bg-gradient-to-r from-blue-50/80 to-indigo-50/40 border border-blue-200">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-base shadow-sm shrink-0">
              4
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-800">Langkah 4: Simulasi Pilihan Kampus PTN & Deteksi Tabrakan</h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Proses simulasi bertahap: <strong>Simulasi 1</strong> (Draf Awal Siswa), <strong>Simulasi 2</strong> (Rasionalisasi & Evaluasi BK), dan <strong>Simulasi 3</strong> (Penetapan Pilihan Final & Permanen).
              </p>
            </div>
          </div>
        </div>

        <!-- SUB-STEPPER PILLS (SIMULASI 1, SIMULASI 2, SIMULASI 3 PERMANEN) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <!-- Simulasi 1 -->
          <div @click="switchSimulasi(1)"
               class="p-4 rounded-2xl border transition cursor-pointer relative"
               :class="activeSimulasi === 1 ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100/70'">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-2xs font-extrabold px-2.5 py-0.5 rounded-lg" :class="activeSimulasi === 1 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700'">
                Tahap 1
              </span>
              <span class="text-2xs font-bold text-slate-500">{{ simulasiStats?.[1]?.total_siswa_isi || 0 }} Siswa Mengisi</span>
            </div>
            <div class="font-black text-slate-800 text-sm">Simulasi 1: Draf Awal Pilihan</div>
            <p class="text-[11px] text-slate-500 mt-1">Pengisian awal pilihan PTN siswa & deteksi tabrakan pertama.</p>
          </div>

          <!-- Simulasi 2 -->
          <div @click="switchSimulasi(2)"
               class="p-4 rounded-2xl border transition cursor-pointer relative"
               :class="activeSimulasi === 2 ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100/70'">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-2xs font-extrabold px-2.5 py-0.5 rounded-lg" :class="activeSimulasi === 2 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700'">
                Tahap 2
              </span>
              <span class="text-2xs font-bold text-slate-500">{{ simulasiStats?.[2]?.total_siswa_isi || 0 }} Siswa Mengisi</span>
            </div>
            <div class="font-black text-slate-800 text-sm">Simulasi 2: Rasionalisasi BK</div>
            <p class="text-[11px] text-slate-500 mt-1">Perbaikan pilihan yang bentrok setelah bimbingan konseling.</p>
          </div>

          <!-- Simulasi 3 -->
          <div @click="switchSimulasi(3)"
               class="p-4 rounded-2xl border transition cursor-pointer relative"
               :class="activeSimulasi === 3 ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100/70'">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-2xs font-extrabold px-2.5 py-0.5 rounded-lg" :class="activeSimulasi === 3 ? 'bg-blue-600 text-white' : (simulasiStats?.[3]?.is_permanen ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700')">
                Tahap 3 Final
              </span>
              <span v-if="simulasiStats?.[3]?.is_permanen" class="text-2xs font-black text-emerald-600 flex items-center gap-1">
                <i class="bi bi-lock-fill"></i> TERKUNCI PERMANEN
              </span>
              <span v-else class="text-2xs font-bold text-slate-500">{{ simulasiStats?.[3]?.total_siswa_isi || 0 }} Siswa</span>
            </div>
            <div class="font-black text-slate-800 text-sm">Simulasi 3: Pilihan Permanen</div>
            <p class="text-[11px] text-slate-500 mt-1">Pilihan final yang dikunci permanen siap ekspor ke PDSS.</p>
          </div>
        </div>

        <!-- ACTION BAR SIMULASI (SALIN & KUNCI PERMANEN) -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <span class="badge bg-blue-600 text-white font-extrabold px-2.5 py-1 rounded-lg text-xs">
              Sedang Menampilkan: Simulasi {{ activeSimulasi }}
            </span>
            <span class="text-xs text-slate-600 font-medium">
              {{ simulasiStats?.[activeSimulasi]?.deskripsi }}
            </span>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <!-- Salin dari Sim 1 ke Sim 2 -->
            <button v-if="activeSimulasi === 2" @click="salinSimulasiAction(1, 2)" type="button" class="btn btn-sm bg-white hover:bg-slate-100 text-blue-700 border border-blue-200 rounded-xl px-3 py-1.5 text-xs font-bold shadow-2xs transition flex items-center gap-1">
              <i class="bi bi-copy"></i> Salin Pilihan dari Simulasi 1 &rarr; 2
            </button>

            <!-- Salin dari Sim 2 ke Sim 3 -->
            <button v-if="activeSimulasi === 3 && !simulasiStats?.[3]?.is_permanen" @click="salinSimulasiAction(2, 3)" type="button" class="btn btn-sm bg-white hover:bg-slate-100 text-blue-700 border border-blue-200 rounded-xl px-3 py-1.5 text-xs font-bold shadow-2xs transition flex items-center gap-1">
              <i class="bi bi-copy"></i> Salin Pilihan dari Simulasi 2 &rarr; 3
            </button>

            <!-- Kunci Permanen Simulasi 3 -->
            <button v-if="activeSimulasi === 3 && !simulasiStats?.[3]?.is_permanen" @click="kunciPermanenAction" type="button" class="btn btn-sm bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-3.5 py-1.5 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
              <i class="bi bi-lock-fill"></i> Kunci Permanen Simulasi 3
            </button>
          </div>
        </div>

        <!-- Alert Banner Tabrakan -->
        <div v-if="collisions.length > 0" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3">
          <i class="bi bi-exclamation-octagon-fill text-rose-600 text-xl shrink-0 mt-0.5"></i>
          <div>
            <div class="font-bold text-sm">Peringatan Tabrakan Pilihan (Collision Matrix) pada Simulasi {{ activeSimulasi }}</div>
            <div class="text-xs text-rose-700 mt-0.5">
              Ditemukan <strong>{{ collisions.length }} Program Studi</strong> yang dipilih oleh lebih dari 1 siswa pada pilihan yang sama di sekolah ini. 
              Gunakan sesi bimbingan rasionalisasi pada <strong>Simulasi 2</strong> untuk mengoptimalkan peluang kelulusan.
            </div>
          </div>
        </div>

        <div v-else class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
          <i class="bi bi-check-circle-fill text-emerald-600 text-xl shrink-0"></i>
          <div class="text-xs font-bold">
            Tidak terdeteksi tabrakan pilihan prodi pada Simulasi {{ activeSimulasi }}. Sebaran pilihan jurusan antar-siswa aman dan optimal!
          </div>
        </div>

        <!-- Matriks Kartu Tabrakan Jurusan -->
        <div v-if="collisions.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="col in collisions" :key="col.key" class="bg-white p-5 rounded-2xl border border-rose-200 shadow-2xs">
            <div class="flex items-start justify-between gap-2 mb-3">
              <div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-2xs font-extrabold bg-rose-100 text-rose-800 mb-1">
                  Pilihan {{ col.no_pilihan }} Bentrok ({{ col.total_bentrok }} Siswa)
                </span>
                <h4 class="font-bold text-slate-800 text-sm">{{ col.nama_prodi }} ({{ col.jenjang }})</h4>
                <div class="text-xs text-slate-500 font-semibold">{{ col.nama_kampus }} | Kuota PTN: {{ col.daya_tampung }} Mhs</div>
              </div>
            </div>

            <!-- Daftar Siswa yang Bentrok -->
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-200/80 space-y-2">
              <div v-for="(st, idx) in col.students" :key="st.siswa_id" class="flex items-center justify-between text-xs p-2 rounded-lg bg-white border border-slate-200">
                <div>
                  <span class="font-bold text-slate-800">{{ idx + 1 }}. {{ st.nama_lengkap }}</span>
                  <div class="text-[10px] text-slate-400">NISN: {{ st.nisn }} | Status: {{ st.status }}</div>
                </div>
                <button v-if="!simulasiStats?.[activeSimulasi]?.is_permanen" @click="openModalPilihan(st, col.no_pilihan)" type="button" class="btn btn-xs bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg px-2 py-1 text-2xs font-bold">
                  Ganti Pilihan
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabel Pilihan Kampus Siswa Eligible pada Simulasi Aktif -->
        <div class="border border-slate-200/80 rounded-xl overflow-hidden">
          <div class="p-3.5 bg-slate-50/70 border-b border-slate-200 flex justify-between items-center">
            <h4 class="font-bold text-slate-800 text-xs flex items-center gap-2">
              <i class="bi bi-list-check text-blue-600"></i>
              Daftar Pilihan Siswa Eligible (Simulasi {{ activeSimulasi }})
            </h4>
            <span class="text-2xs text-slate-500 font-medium">Menampilkan pilihan PTN & prodi untuk nomor simulasi aktif</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="py-3 px-4 w-12 text-center">Rank</th>
                  <th class="py-3 px-4">Nama Siswa</th>
                  <th class="py-3 px-3">Jurusan</th>
                  <th class="py-3 px-4 bg-blue-50/40">Pilihan 1 (PTN & Prodi)</th>
                  <th class="py-3 px-4 bg-indigo-50/40">Pilihan 2 (PTN & Prodi)</th>
                  <th class="py-3 px-4 text-center sticky right-0 bg-slate-50 z-10 w-24">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="item in paginatedRankingList.filter(s => s.is_eligible)" :key="item.siswa_id" class="hover:bg-blue-50/40 transition group">
                  <td class="py-3 px-4 text-center">
                    <span class="w-6 h-6 rounded-full inline-flex items-center justify-center font-black text-2xs bg-blue-600 text-white shadow-2xs">
                      {{ item.ranking_sekolah }}
                    </span>
                  </td>
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-800">{{ item.nama_lengkap }}</div>
                    <div class="text-[10px] text-slate-400">NISN: {{ item.nisn }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">{{ item.jurusan }}</span>
                  </td>

                  <!-- Pilihan 1 -->
                  <td class="py-3 px-4 bg-blue-50/10">
                    <div v-if="item.pilihan_1" class="text-xs">
                      <div class="font-bold text-blue-700">{{ item.pilihan_1.nama_kampus }}</div>
                      <div class="text-slate-500 text-2xs">{{ item.pilihan_1.nama_prodi }} ({{ item.pilihan_1.jenjang }})</div>
                    </div>
                    <button v-else-if="!simulasiStats?.[activeSimulasi]?.is_permanen" @click="openModalPilihan(item, 1)" type="button" class="text-2xs text-blue-600 font-bold hover:underline flex items-center gap-1">
                      <i class="bi bi-plus-circle"></i> Isi Pilihan 1 (Sim {{ activeSimulasi }})
                    </button>
                    <span v-else class="text-2xs text-slate-400 font-medium">Belum Diisi</span>
                  </td>

                  <!-- Pilihan 2 -->
                  <td class="py-3 px-4 bg-indigo-50/10">
                    <div v-if="item.pilihan_2" class="text-xs">
                      <div class="font-bold text-indigo-700">{{ item.pilihan_2.nama_kampus }}</div>
                      <div class="text-slate-500 text-2xs">{{ item.pilihan_2.nama_prodi }} ({{ item.pilihan_2.jenjang }})</div>
                    </div>
                    <button v-else-if="!simulasiStats?.[activeSimulasi]?.is_permanen" @click="openModalPilihan(item, 2)" type="button" class="text-2xs text-indigo-600 font-bold hover:underline flex items-center gap-1">
                      <i class="bi bi-plus-circle"></i> Isi Pilihan 2 (Sim {{ activeSimulasi }})
                    </button>
                    <span v-else class="text-2xs text-slate-400 font-medium">Belum Diisi</span>
                  </td>

                  <!-- Sticky Action -->
                  <td class="py-3 px-4 text-center sticky right-0 bg-white group-hover:bg-blue-50/90 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] border-l border-slate-100 z-10 whitespace-nowrap">
                    <button v-if="!simulasiStats?.[activeSimulasi]?.is_permanen" @click="openModalPilihan(item, 1)" type="button" class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs transition" title="Edit Pilihan Kampus">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <span v-else class="text-2xs font-bold text-emerald-600">
                      <i class="bi bi-lock-fill"></i>
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tombol Lanjut ke Langkah 5 -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <span class="text-xs text-slate-500 font-medium">Setelah pilihan Simulasi 3 dikunci permanen, lanjutkan ke Langkah 5 untuk memeriksa katalog master & finalisasi ekspor PDSS.</span>
          <button @click="activeTab = 'katalog'" type="button" class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
            Lanjut ke Langkah 5: Katalog Master PTN & Finalisasi <i class="bi bi-arrow-right"></i>
          </button>
        </div>

      </div>

      <!-- ═══════════════════════════════════════════════════════════
           TAB 5: KATALOG MASTER KAMPUS & PRODI SNPMB (LANGKAH 5)
      ════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'katalog'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3">
          <div>
            <h3 class="font-bold text-slate-800 text-sm">Direktori Master Kampus PTN & Program Studi SNPMB</h3>
            <p class="text-xs text-slate-500">Database resmi 147 PTN & 5.142 Program Studi SNPMB Seluruh Indonesia.</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="py-3 px-4">Nama Kampus PTN</th>
                <th class="py-3 px-3">Jenis</th>
                <th class="py-3 px-3 text-center">Akreditasi</th>
                <th class="py-3 px-3">Kota / Provinsi</th>
                <th class="py-3 px-3 text-center">Total Prodi</th>
                <th class="py-3 px-4">Website Resmi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="k in masterKampusList?.data || []" :key="k.id" class="hover:bg-slate-50 transition">
                <td class="py-3 px-4 font-bold text-slate-800">{{ k.nama_kampus }}</td>
                <td class="py-3 px-3"><span class="px-2 py-0.5 rounded-full text-2xs font-bold bg-slate-100 text-slate-700">{{ k.jenis || 'PTN' }}</span></td>
                <td class="py-3 px-3 text-center"><span class="font-black text-blue-600">{{ k.akreditasi || 'A' }}</span></td>
                <td class="py-3 px-3">{{ k.kota || '-' }}, {{ k.provinsi || '-' }}</td>
                <td class="py-3 px-3 text-center font-bold text-slate-700">{{ k.prodi_count || 0 }} Prodi</td>
                <td class="py-3 px-4 text-blue-600 font-mono text-2xs">{{ k.web || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: PILIH / EDIT JURUSAN SISWA
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModalPilihan" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-black text-slate-800 text-base">Atur Pilihan {{ formPilihan.no_pilihan }} (Simulasi {{ activeSimulasi }})</h3>
              <p class="text-xs text-slate-500">{{ selectedSiswaForPilihan?.nama_lengkap }} (NISN: {{ selectedSiswaForPilihan?.nisn }})</p>
            </div>
            <button @click="showModalPilihan = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form @submit.prevent="submitPilihan" class="space-y-4 pt-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Cari Program Studi & Kampus PTN <span class="text-rose-500">*</span></label>
              <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       v-model="prodiQuery" 
                       @input="searchProdiLive" 
                       placeholder="Ketik nama prodi atau kampus (contoh: Kedokteran UI)..." 
                       class="w-full h-10 pl-9 pr-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
              </div>

              <!-- Autocomplete Dropdown List -->
              <div v-if="prodiOptions.length > 0" class="mt-1 max-h-48 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-lg divide-y divide-slate-100">
                <div v-for="p in prodiOptions" :key="p.id" 
                     @click="selectProdiOption(p)" 
                     class="p-2.5 hover:bg-blue-50 cursor-pointer text-xs transition">
                  <div class="font-bold text-slate-800">{{ p.nama_prodi }} ({{ p.jenjang }})</div>
                  <div class="text-[11px] text-blue-600 font-semibold">{{ p.nama_kampus }} | Kuota: {{ p.daya_tampung }} Mhs</div>
                </div>
              </div>
              <div v-else-if="loadingProdi" class="text-2xs text-slate-400 p-2 text-center">
                Mencari program studi...
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
              <button @click="showModalPilihan = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                Batal
              </button>
              <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs">
                Simpan Pilihan (Simulasi {{ activeSimulasi }})
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: OVERRIDE STATUS ELIGIBLE
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModalOverride" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-black text-slate-800 text-base">Override Eligibilitas SNBP</h3>
              <p class="text-xs text-slate-500">{{ selectedSiswaForOverride?.nama_lengkap }} (NISN: {{ selectedSiswaForOverride?.nisn }})</p>
            </div>
            <button @click="showModalOverride = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form @submit.prevent="submitOverride" class="space-y-4 pt-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Status Kelayakan Final</label>
              <select v-model="formOverride.is_eligible" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option :value="true">Eligible (Dinyatakan Layak)</option>
                <option :value="false">Tidak Eligible (Tidak Diikutsertakan)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Pertimbangan BK</label>
              <textarea v-model="formOverride.catatan" rows="3" placeholder="Tulis alasan pertimbangan khusus override status eligible..." class="w-full p-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
              <button @click="showModalOverride = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                Batal
              </button>
              <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs">
                Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: PENGUNDURAN DIRI (LANGKAH 3)
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModalPengunduran" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-black text-rose-700 text-base">Surat Pengunduran Diri Eligible</h3>
              <p class="text-xs text-slate-500">{{ selectedSiswaForPengunduran?.nama_lengkap }} (NISN: {{ selectedSiswaForPengunduran?.nisn }})</p>
            </div>
            <button @click="showModalPengunduran = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form @submit.prevent="submitPengunduran" class="space-y-4 pt-4">
            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 text-xs">
              <i class="bi bi-info-circle-fill text-amber-600 me-1"></i>
              Setelah pengunduran diri dikonfirmasi, status siswa akan dinonaktifkan dari kuota eligible dan slot kuotanya otomatis dipromosikan ke siswa peringkat cadangan berikutnya.
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Berita Acara / Surat</label>
              <input type="text" v-model="formPengunduran.nomor_surat" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-mono font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Surat</label>
              <input type="date" v-model="formPengunduran.tanggal_surat" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Pengunduran Diri <span class="text-rose-500">*</span></label>
              <textarea v-model="formPengunduran.alasan" rows="3" placeholder="Contoh: Memilih jalur Kedinasan / Kuliah di Luar Negeri / Alasan Pribadi..." class="w-full p-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Berkas Surat Bermaterai (PDF/Foto)</label>
              <input type="file" @change="handleFilePengunduran" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
              <button @click="showModalPengunduran = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                Batal
              </button>
              <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs">
                Konfirmasi Pengunduran Diri
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODERN CONFIRMATION MODAL (PENGGANTI CONFIRM() BROWSER)
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="confirmModal.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 transform transition-all scale-100 space-y-4 relative z-10">
          <div class="flex items-start gap-4">
            <!-- Icon Badge -->
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 text-xl font-bold"
                 :class="{
                   'bg-rose-50 text-rose-600 border border-rose-100': confirmModal.type === 'danger',
                   'bg-amber-50 text-amber-600 border border-amber-100': confirmModal.type === 'warning',
                   'bg-blue-50 text-blue-600 border border-blue-100': confirmModal.type === 'info',
                   'bg-emerald-50 text-emerald-600 border border-emerald-100': confirmModal.type === 'primary'
                 }">
              <i v-if="confirmModal.type === 'danger'" class="bi bi-shield-exclamation"></i>
              <i v-else-if="confirmModal.type === 'warning'" class="bi bi-exclamation-triangle-fill"></i>
              <i v-else-if="confirmModal.type === 'info'" class="bi bi-arrow-repeat"></i>
              <i v-else class="bi bi-patch-check-fill"></i>
            </div>

            <div class="flex-1 min-w-0">
              <h3 class="text-base font-black text-slate-800 leading-tight">{{ confirmModal.title }}</h3>
              <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">{{ confirmModal.message }}</p>
              <p v-if="confirmModal.detail" class="text-[11px] text-slate-400 mt-1 italic">{{ confirmModal.detail }}</p>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button @click="closeConfirmModal" type="button" 
                    class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
              {{ confirmModal.cancelText || 'Batal' }}
            </button>
            <button @click="handleConfirmModalSubmit" type="button" 
                    class="px-5 py-2 rounded-xl font-bold text-xs shadow-xs text-white transition flex items-center gap-1.5"
                    :class="{
                      'bg-rose-600 hover:bg-rose-700': confirmModal.type === 'danger',
                      'bg-amber-600 hover:bg-amber-700': confirmModal.type === 'warning',
                      'bg-blue-600 hover:bg-blue-700': confirmModal.type === 'info' || confirmModal.type === 'primary',
                    }">
              <i class="bi bi-check-circle-fill text-xs"></i>
              {{ confirmModal.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODERN TOAST NOTIFICATION POPUP (PENGGANTI ALERT() BROWSER)
    ════════════════════════════════════════════════════════════ -->
    <div class="fixed top-5 right-5 z-50 pointer-events-none flex flex-col gap-2 max-w-sm w-full">
      <transition 
        enter-active-class="transform ease-out duration-300 transition" 
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4" 
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" 
        leave-active-class="transition ease-in duration-200" 
        leave-from-class="opacity-100" 
        leave-to-class="opacity-0">
        <div v-if="toast.show" 
             class="pointer-events-auto bg-white rounded-2xl shadow-xl border overflow-hidden p-4 relative flex items-start gap-3"
             :class="{
               'border-emerald-200/90 shadow-emerald-500/10': toast.type === 'success',
               'border-rose-200/90 shadow-rose-500/10': toast.type === 'error',
               'border-amber-200/90 shadow-amber-500/10': toast.type === 'warning',
               'border-blue-200/90 shadow-blue-500/10': toast.type === 'info'
             }">
          <!-- Icon Bulat Berwarna -->
          <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-base"
               :class="{
                 'bg-emerald-50 text-emerald-600': toast.type === 'success',
                 'bg-rose-50 text-rose-600': toast.type === 'error',
                 'bg-amber-50 text-amber-600': toast.type === 'warning',
                 'bg-blue-50 text-blue-600': toast.type === 'info'
               }">
            <i v-if="toast.type === 'success'" class="bi bi-check2-circle"></i>
            <i v-else-if="toast.type === 'error'" class="bi bi-x-circle"></i>
            <i v-else-if="toast.type === 'warning'" class="bi bi-exclamation-circle"></i>
            <i v-else class="bi bi-info-circle"></i>
          </div>

          <!-- Konten Teks -->
          <div class="flex-1 min-w-0 pr-2">
            <h4 class="text-xs font-black text-slate-800 leading-tight">{{ toast.title }}</h4>
            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">{{ toast.message }}</p>
          </div>

          <!-- Tombol Close -->
          <button @click="closeToast" type="button" class="text-slate-400 hover:text-slate-600 text-xs shrink-0 p-1 rounded-lg hover:bg-slate-100 transition">
            <i class="bi bi-x-lg"></i>
          </button>

          <!-- Animated Border Progress Indicator -->
          <div class="absolute bottom-0 left-0 right-0 h-1 bg-slate-100 overflow-hidden">
            <div class="h-full animate-progress"
                 :class="{
                   'bg-emerald-500': toast.type === 'success',
                   'bg-rose-500': toast.type === 'error',
                   'bg-amber-500': toast.type === 'warning',
                   'bg-blue-500': toast.type === 'info'
                 }"></div>
          </div>
        </div>
      </transition>
    </div>

  </AppLayout>
</template>

<style scoped>
@keyframes progress {
  from { width: 100%; }
  to { width: 0%; }
}
.animate-progress {
  animation: progress 4s linear forwards;
}
</style>
