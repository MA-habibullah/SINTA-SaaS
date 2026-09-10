<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'

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
const selectedKuota = ref(Number(props.filters?.kuota_persen) || Number(props.rankingData?.kuota_persen) || 40)
const useERapor = ref(Boolean(props.filters?.use_erapor))
const isRecalculating = ref(false)
const perPage = ref(15)
const currentPage = ref(1)

// Mapel State & Filter Berjenjang (Langkah 1)
const filterTingkatMapel = ref(props.filters?.tingkat_mapel || '')
const filterJurusanMapel = ref(props.filters?.jurusan_mapel || '')
const filterKelasMapel = ref(props.filters?.kelas_mapel || '')
const localMapels = ref(props.pdssMapels ? JSON.parse(JSON.stringify(props.pdssMapels)) : [])
const isSavingMapels = ref(false)
const isAutoDetecting = ref(false)
const autoDetectNotice = ref('')

// Daftar Kelas yang disesuaikan dengan Tingkat yang dipilih
const availableKelasList = computed(() => {
  if (!props.kelasList) return []
  if (!filterTingkatMapel.value || filterTingkatMapel.value === 'all') return props.kelasList
  const prefix = filterTingkatMapel.value.toUpperCase()
  return props.kelasList.filter(k => {
    const nk = (k.nama_kelas || '').toUpperCase()
    return nk.startsWith(prefix + ' ') || nk.startsWith('KELAS ' + prefix) || nk.includes(prefix)
  })
})

const handleTingkatChange = () => {
  // Reset kelas jika kelas yang dipilih tidak sesuai dengan tingkat baru
  if (filterKelasMapel.value) {
    const isStillValid = availableKelasList.value.some(k => k.id === filterKelasMapel.value || k.nama_kelas === filterKelasMapel.value)
    if (!isStillValid) {
      filterKelasMapel.value = ''
    }
  }
  applyMapelFilter()
}

const getMapelContextLabel = () => {
  const parts = []
  if (filterKelasMapel.value) {
    const k = props.kelasList?.find(item => item.id === filterKelasMapel.value || item.nama_kelas === filterKelasMapel.value)
    if (k) parts.push(`Kelas ${k.nama_kelas}`)
  } else if (filterTingkatMapel.value) {
    parts.push(`Tingkat Kelas ${filterTingkatMapel.value}`)
  }
  if (filterJurusanMapel.value) {
    parts.push(`Rumpun ${filterJurusanMapel.value}`)
  }
  if (parts.length === 0) {
    return 'Semua Rombel & Kurikulum Global'
  }
  return parts.join(' - ')
}

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
        tingkat_mapel: filterTingkatMapel.value,
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
    tingkat_mapel: filterTingkatMapel.value,
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
    tingkat_mapel: filterTingkatMapel.value,
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
    kuota_persen: selectedKuota.value,
    use_erapor: useERapor.value ? 1 : 0,
  }, { preserveState: false, preserveScroll: true })
}

const applyFilters = (showToastNotice = false) => {
  currentPage.value = 1
  router.get('/bk/akademik', {
    tenant_id: selectedTenant.value,
    tahun_ajaran: filterTahunAjaran.value,
    jurusan: filterJurusan.value,
    status_eligible: filterStatus.value,
    no_simulasi: activeSimulasi.value,
    search: searchQuery.value,
    kuota_persen: selectedKuota.value,
    use_erapor: useERapor.value ? 1 : 0,
  }, { 
    preserveState: true, 
    preserveScroll: true,
    onSuccess: () => {
      if (showToastNotice) {
        showToast('Pemeringkatan siswa eligible SNBP berhasil diperbarui & disinkronkan!', 'success', 'Generate Berhasil')
      }
    }
  })
}

const updateKuota = () => {
  applyFilters()
}

const toggleERapor = () => {
  // Jika menggunakan e-rapor dan kuota standar dipilih, otomatis tambahkan 5%
  if (useERapor.value) {
    if (selectedKuota.value === 40) selectedKuota.value = 45
    else if (selectedKuota.value === 25) selectedKuota.value = 30
    else if (selectedKuota.value === 5) selectedKuota.value = 10
  } else {
    if (selectedKuota.value === 45) selectedKuota.value = 40
    else if (selectedKuota.value === 30) selectedKuota.value = 25
    else if (selectedKuota.value === 10) selectedKuota.value = 5
  }
  applyFilters()
}

const generateEligibleAction = () => {
  isRecalculating.value = true
  setTimeout(() => {
    applyFilters(true)
    isRecalculating.value = false
  }, 400)
}

const resetAllEligibleAction = () => {
  openConfirmModal({
    title: 'Reset Seluruh Override Kelayakan?',
    message: 'Seluruh perubahan status eligible/tidak eligible manual oleh Guru BK akan dihapus.',
    detail: 'Status kelayakan seluruh siswa akan kembali dihitung secara murni berdasarkan rata-rata nilai rapor 5 semester dan kuota akreditasi sekolah.',
    confirmText: 'Ya, Reset Semua Status',
    cancelText: 'Batal',
    type: 'danger',
    action: () => {
      router.post('/pdss/reset-eligible', {
        tahun_ajaran: filterTahunAjaran.value,
        tenant_id: selectedTenant.value,
      }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          showToast('Seluruh status kelayakan siswa telah berhasil direset ke kalkulasi otomatis!', 'success', 'Reset Berhasil')
          applyFilters()
        },
        onError: () => {
          showToast('Gagal mereset status kelayakan siswa.', 'error', 'Gagal Reset')
        }
      })
    }
  })
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

// Filter & Client-side Pagination Khusus Simulasi Siswa Eligible (Langkah 4)
const searchSimulasi = ref('')
const filterJurusanSimulasi = ref('all')
const filterStatusSimulasi = ref('all')
const perPageSimulasi = ref(15)
const currentPageSimulasi = ref(1)

const filteredEligibleList = computed(() => {
  const list = props.rankingData?.ranking_list?.filter(s => s.is_eligible) || []
  return list.filter(item => {
    // Filter Jurusan
    if (filterJurusanSimulasi.value && filterJurusanSimulasi.value !== 'all') {
      const jItem = (item.jurusan || '').toLowerCase()
      const jFilter = filterJurusanSimulasi.value.toLowerCase()
      if (!jItem.includes(jFilter)) return false
    }

    // Filter Status Pengisian
    if (filterStatusSimulasi.value === 'lengkap') {
      if (!item.pilihan_1 || !item.pilihan_2) return false
    } else if (filterStatusSimulasi.value === 'sebagian') {
      if ((!item.pilihan_1 && !item.pilihan_2) || (item.pilihan_1 && item.pilihan_2)) return false
    } else if (filterStatusSimulasi.value === 'kosong') {
      if (item.pilihan_1 || item.pilihan_2) return false
    } else if (filterStatusSimulasi.value === 'bentrok') {
      if (!item.pilihan_1?.is_bentrok && !item.pilihan_2?.is_bentrok) return false
    }

    // Search Query (Nama Siswa, NISN, Kampus, Prodi)
    if (searchSimulasi.value && searchSimulasi.value.trim() !== '') {
      const q = searchSimulasi.value.toLowerCase().trim()
      const namaMatch = (item.nama_lengkap || '').toLowerCase().includes(q)
      const nisnMatch = (item.nisn || '').toLowerCase().includes(q)
      const p1KampusMatch = (item.pilihan_1?.nama_kampus || '').toLowerCase().includes(q)
      const p1ProdiMatch = (item.pilihan_1?.nama_prodi || '').toLowerCase().includes(q)
      const p2KampusMatch = (item.pilihan_2?.nama_kampus || '').toLowerCase().includes(q)
      const p2ProdiMatch = (item.pilihan_2?.nama_prodi || '').toLowerCase().includes(q)
      if (!namaMatch && !nisnMatch && !p1KampusMatch && !p1ProdiMatch && !p2KampusMatch && !p2ProdiMatch) {
        return false
      }
    }

    return true
  })
})

const totalSimulasiPages = computed(() => {
  const total = filteredEligibleList.value.length
  return Math.max(1, Math.ceil(total / perPageSimulasi.value))
})

const paginatedSimulasiList = computed(() => {
  const start = (currentPageSimulasi.value - 1) * perPageSimulasi.value
  return filteredEligibleList.value.slice(start, start + perPageSimulasi.value)
})

const simulasiPaginationInfo = computed(() => {
  const total = filteredEligibleList.value.length
  const from = total > 0 ? (currentPageSimulasi.value - 1) * perPageSimulasi.value + 1 : 0
  const to = Math.min(currentPageSimulasi.value * perPageSimulasi.value, total)
  return { total, from, to }
})

const goToSimulasiPage = (page) => {
  if (page >= 1 && page <= totalSimulasiPages.value) {
    currentPageSimulasi.value = page
  }
}

const smartSimulasiPageNumbers = computed(() => {
  const total = totalSimulasiPages.value
  const current = currentPageSimulasi.value
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

const batalPengunduranAction = (item) => {
  const namaSiswa = item.nama_lengkap || item.siswa?.nama_lengkap || 'Siswa'
  const siswaId = item.siswa_id || item.id || item.siswa?.id
  openConfirmModal({
    title: `Batalkan Pengunduran Diri ${namaSiswa}?`,
    message: `Siswa akan dipulihkan kembali ke status eligible/cadangan berdasarkan peringkat nilai rapor aslinya.`,
    detail: 'Surat pengunduran diri akan dihapus dan kuota eligible akan diselaraskan ulang secara otomatis.',
    confirmText: 'Ya, Batalkan Pengunduran',
    cancelText: 'Tutup',
    type: 'warning',
    action: () => {
      router.post('/pdss/batal-pengunduran-diri', {
        siswa_id: siswaId,
        tahun_ajaran: filterTahunAjaran.value,
        tenant_id: selectedTenant.value,
      }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          showToast(`Pengunduran diri siswa ${namaSiswa} berhasil dibatalkan dan statusnya telah dipulihkan!`, 'success', 'Pemulihan Berhasil')
          applyFilters()
        },
        onError: () => {
          showToast('Gagal membatalkan pengunduran diri siswa.', 'error', 'Gagal')
        }
      })
    }
  })
}

// Modal: Transkrip Nilai Rapor Semester 1 s.d. 5
const modalViewNilai = ref({
  show: false,
  loading: false,
  data: null,
  siswa: null,
})

const openModalViewNilai = async (siswa) => {
  const siswaId = siswa.siswa_id || siswa.id
  modalViewNilai.value = {
    show: true,
    loading: true,
    data: null,
    siswa: {
      nama_lengkap: siswa.nama_lengkap || siswa.nama_siswa || 'Siswa',
      nisn: siswa.nisn || '-',
      jurusan: siswa.jurusan || '-',
      nama_kelas: siswa.nama_kelas || siswa.kelas || '-',
    },
  }
  try {
    const res = await axios.get(`/pdss/siswa/${siswaId}/nilai-detail`)
    if (res.data?.success) {
      modalViewNilai.value.data = res.data.data
      if (res.data.data.student) {
        modalViewNilai.value.siswa = {
          ...modalViewNilai.value.siswa,
          ...res.data.data.student,
          nama_kelas: res.data.data.student.kelas || modalViewNilai.value.siswa.nama_kelas,
        }
      }
    } else {
      showToast('Gagal memuat transkrip nilai siswa.', 'error')
    }
  } catch (err) {
    showToast('Terjadi kesalahan saat memuat nilai siswa.', 'error')
  } finally {
    modalViewNilai.value.loading = false
  }
}

// Modal: Detail Analisis Bentrok / Konflik Pilihan SNBP
const modalDetailKonflik = ref({
  show: false,
  namaKampus: '',
  namaProdi: '',
  jenjang: 'S1',
  noPilihan: 1,
  dayaTampung: 0,
  totalBentrok: 0,
  students: [],
  siswa: null,
})

const openModalDetailKonflik = (siswa, slot) => {
  const pil = slot === 1 ? siswa.pilihan_1 : siswa.pilihan_2
  const conflicts = pil?.konflik_detail || []
  const prodiId = pil?.prodi_id
  
  let studentList = Array.isArray(conflicts) ? [...conflicts] : []
  let dayaTampung = 0
  if (props.collisions) {
    const colMatch = props.collisions.find(c => c.prodi_id === prodiId && Number(c.no_pilihan) === Number(slot))
    if (colMatch) {
      if (studentList.length === 0 && colMatch.students) {
        studentList = colMatch.students
      }
      dayaTampung = colMatch.daya_tampung || 0
    }
  }

  modalDetailKonflik.value = {
    show: true,
    namaKampus: pil?.nama_kampus || 'Universitas Negeri',
    namaProdi: pil?.nama_prodi || 'Program Studi',
    jenjang: pil?.jenjang || 'S1',
    noPilihan: slot,
    dayaTampung: dayaTampung || pil?.daya_tampung || 20,
    totalBentrok: pil?.total_bentrok || studentList.length || 0,
    students: studentList,
    siswa: siswa,
  }
}

// Modal: Export / Download Rekap Nilai Rapor Buku Induk
const showModalExport = ref(false)
const exportJenjang = ref('all')
const exportSemester = ref('all')
const exportKelasId = ref('all')
const exportTahunAjaran = ref(props.filters?.tahun_ajaran || '2026/2027')

const openModalExport = () => {
  exportTahunAjaran.value = filterTahunAjaran.value
  showModalExport.value = true
}

const handleExportNilai = () => {
  const params = new URLSearchParams({
    tenant_id: selectedTenant.value || '',
    tahun_ajaran: exportTahunAjaran.value || '',
    jenjang: exportJenjang.value || 'all',
    semester: exportSemester.value || 'all',
    kelas_id: exportKelasId.value || 'all',
  })
  window.open(`/pdss/export-nilai?${params.toString()}`, '_blank')
  showModalExport.value = false
  showToast('Sedang menyiapkan file rekap nilai rapor buku induk...', 'info', 'Mengunduh Data')
}

// Master Katalog Kampus & Prodi (Langkah 5)
const showModalProdiKampus = ref(false)
const selectedKampusDetail = ref(null)
const kampusProdiList = ref([])
const loadingKampusProdi = ref(false)
const searchKampusText = ref(props.filters?.search_kampus || '')
const filterJenisKampus = ref(props.filters?.jenis_kampus || 'all')

let searchKampusDebounce = null
const handleSearchKampusDebounce = () => {
  clearTimeout(searchKampusDebounce)
  searchKampusDebounce = setTimeout(() => {
    applyKampusFilter()
  }, 400)
}

// Modal: Form Kampus (Tambah / Edit)
const showModalFormKampus = ref(false)
const isEditKampus = ref(false)
const formKampus = useForm({
  id: '',
  nama_kampus: '',
  jenis: 'Negeri',
  akreditasi: 'A',
  kota: '',
  provinsi: '',
  alamat: '',
  web: '',
  kode_ptn: '',
})

const openModalCreateKampus = () => {
  isEditKampus.value = false
  formKampus.reset()
  formKampus.jenis = 'Negeri'
  formKampus.akreditasi = 'A'
  showModalFormKampus.value = true
}

const openModalEditKampus = (kampus) => {
  isEditKampus.value = true
  formKampus.id = kampus.id
  formKampus.nama_kampus = kampus.nama_kampus
  formKampus.jenis = kampus.jenis || 'Negeri'
  formKampus.akreditasi = kampus.akreditasi || 'A'
  formKampus.kota = kampus.kota || kampus.kota_kampus || ''
  formKampus.provinsi = kampus.provinsi || ''
  formKampus.alamat = kampus.alamat || kampus.alamat_kampus || ''
  formKampus.web = kampus.web || ''
  formKampus.kode_ptn = kampus.kode_ptn || ''
  showModalFormKampus.value = true
}

const submitFormKampus = () => {
  if (!formKampus.nama_kampus) {
    showToast('Nama Kampus PTN wajib diisi.', 'warning', 'Form Belum Lengkap')
    return
  }

  if (isEditKampus.value) {
    formKampus.put(`/pdss/kampus/${formKampus.id}`, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        showModalFormKampus.value = false
        showToast('Data Kampus PTN berhasil diperbarui!', 'success', 'Perubahan Disimpan')
      },
      onError: (err) => {
        showToast('Gagal memperbarui data kampus: ' + JSON.stringify(err), 'error', 'Gagal Simpan')
      }
    })
  } else {
    formKampus.post('/pdss/kampus', {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        showModalFormKampus.value = false
        formKampus.reset()
        showToast('Kampus PTN baru berhasil ditambahkan ke direktori nasional!', 'success', 'Kampus Ditambahkan')
      },
      onError: (err) => {
        showToast('Gagal menambahkan kampus: ' + JSON.stringify(err), 'error', 'Gagal Simpan')
      }
    })
  }
}

const confirmDeleteKampus = (kampus) => {
  openConfirmModal({
    title: `Hapus Kampus ${kampus.nama_kampus}?`,
    message: `PERINGATAN: Seluruh program studi dan riwayat pendaftar milik ${kampus.nama_kampus} akan ikut dihapus dari direktori master.`,
    detail: 'Tindakan ini tidak dapat dibatalkan.',
    confirmText: 'Ya, Hapus Kampus',
    cancelText: 'Batal',
    type: 'danger',
    action: () => {
      router.delete(`/pdss/kampus/${kampus.id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          showToast(`Kampus ${kampus.nama_kampus} berhasil dihapus.`, 'success', 'Kampus Dihapus')
        },
        onError: () => {
          showToast('Gagal menghapus kampus.', 'error', 'Gagal Hapus')
        }
      })
    }
  })
}

// Modal: Form Program Studi (Tambah / Edit)
const showModalFormProdi = ref(false)
const isEditProdi = ref(false)
const formProdi = useForm({
  id: '',
  kampus_id: '',
  nama_prodi: '',
  jenjang: 'S1',
  fakultas: '',
  daya_tampung_sekarang: 50,
  jenis_portofolio: 'Tidak Ada',
  kode_prodi: '',
})

const openModalCreateProdi = (kampus) => {
  isEditProdi.value = false
  formProdi.reset()
  formProdi.kampus_id = kampus?.id || selectedKampusDetail.value?.id || ''
  formProdi.jenjang = 'S1'
  formProdi.daya_tampung_sekarang = 50
  formProdi.jenis_portofolio = 'Tidak Ada'
  showModalFormProdi.value = true
}

const openModalEditProdi = (prodi) => {
  isEditProdi.value = true
  formProdi.id = prodi.id
  formProdi.kampus_id = prodi.kampus_id || selectedKampusDetail.value?.id || ''
  formProdi.nama_prodi = prodi.nama_prodi || prodi.program_studi || ''
  formProdi.jenjang = prodi.jenjang || 'S1'
  formProdi.fakultas = prodi.fakultas || ''
  formProdi.daya_tampung_sekarang = prodi.daya_tampung_sekarang || 0
  formProdi.jenis_portofolio = prodi.jenis_portofolio || 'Tidak Ada'
  formProdi.kode_prodi = prodi.kode_prodi || ''
  showModalFormProdi.value = true
}

const submitFormProdi = () => {
  if (!formProdi.nama_prodi) {
    showToast('Nama Program Studi wajib diisi.', 'warning', 'Form Belum Lengkap')
    return
  }

  if (isEditProdi.value) {
    formProdi.put(`/pdss/prodi/${formProdi.id}`, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        showModalFormProdi.value = false
        if (selectedKampusDetail.value) {
          openDetailProdi(selectedKampusDetail.value)
        }
        showToast('Data Program Studi berhasil diperbarui!', 'success', 'Perubahan Disimpan')
      },
      onError: () => {
        showToast('Gagal memperbarui program studi.', 'error', 'Gagal Simpan')
      }
    })
  } else {
    formProdi.post('/pdss/prodi', {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        showModalFormProdi.value = false
        formProdi.reset()
        if (selectedKampusDetail.value) {
          openDetailProdi(selectedKampusDetail.value)
        }
        showToast('Program studi baru berhasil ditambahkan!', 'success', 'Prodi Ditambahkan')
      },
      onError: () => {
        showToast('Gagal menambahkan program studi.', 'error', 'Gagal Simpan')
      }
    })
  }
}

const confirmDeleteProdi = (prodi) => {
  openConfirmModal({
    title: `Hapus Program Studi ${prodi.nama_prodi}?`,
    message: `PERINGATAN: Program studi ${prodi.nama_prodi} (${prodi.jenjang}) beserta data riwayat seleksinya akan dihapus permanen.`,
    confirmText: 'Ya, Hapus Prodi',
    cancelText: 'Batal',
    type: 'danger',
    action: () => {
      router.delete(`/pdss/prodi/${prodi.id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          if (selectedKampusDetail.value) {
            openDetailProdi(selectedKampusDetail.value)
          }
          showToast(`Program studi ${prodi.nama_prodi} berhasil dihapus.`, 'success', 'Prodi Dihapus')
        },
        onError: () => {
          showToast('Gagal menghapus program studi.', 'error', 'Gagal Hapus')
        }
      })
    }
  })
}

// Helper warna badge keketatan prodi
const getKeketatanBadgeClass = (val) => {
  if (!val) return 'bg-slate-50 text-slate-400 border-slate-200'
  const num = parseFloat(String(val).replace('%', '').trim())
  if (isNaN(num)) return 'bg-slate-50 text-slate-500 border-slate-200'
  if (num < 5) return 'bg-rose-50 text-rose-700 border-rose-200'
  if (num < 15) return 'bg-amber-50 text-amber-700 border-amber-200'
  return 'bg-emerald-50 text-emerald-700 border-emerald-200'
}

// Modal: Analisis Riwayat Seleksi & Keketatan 5 Tahun Terakhir (2021-2025)
const showModalRiwayatProdi = ref(false)
const selectedProdiRiwayat = ref(null)
const riwayatList = ref([])
const loadingRiwayat = ref(false)

const openDetailRiwayatProdi = async (prodi) => {
  selectedProdiRiwayat.value = prodi
  showModalRiwayatProdi.value = true
  loadingRiwayat.value = true
  riwayatList.value = []
  try {
    const res = await fetch(`/pdss/prodi/${prodi.id}/riwayat`)
    const json = await res.json()
    if (json.success) {
      riwayatList.value = json.riwayat || []
    }
  } catch (e) {
    console.error(e)
  } finally {
    loadingRiwayat.value = false
  }
}

// Modal: Impor Master Kampus & Prodi CSV
const showModalImportKampus = ref(false)
const formImportKampus = useForm({
  file_csv: null,
})

const openModalImportKampus = () => {
  formImportKampus.reset()
  showModalImportKampus.value = true
}

const handleFileImportKampus = (e) => {
  formImportKampus.file_csv = e.target.files[0]
}

const submitImportKampus = () => {
  if (!formImportKampus.file_csv) {
    showToast('Silakan pilih file CSV terlebih dahulu.', 'warning', 'File Belum Dipilih')
    return
  }

  formImportKampus.post('/pdss/import-master-kampus', {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      showModalImportKampus.value = false
      formImportKampus.reset()
      showToast('Impor direktori kampus & prodi SNPMB berhasil diproses!', 'success', 'Impor Sukses')
    },
    onError: (err) => {
      showToast('Gagal mengimpor file: ' + JSON.stringify(err), 'error', 'Gagal Impor')
    }
  })
}

const handleExportMasterKampus = () => {
  const params = new URLSearchParams({
    jenis_kampus: filterJenisKampus.value || 'all',
    search: searchKampusText.value || '',
  })
  window.open(`/pdss/export-master-kampus?${params.toString()}`, '_blank')
  showToast('Sedang menyiapkan file unduhan direktori Master Kampus & Prodi SNPMB...', 'info', 'Mengunduh Direktori')
}

const openDetailProdi = async (kampus) => {
  selectedKampusDetail.value = kampus
  showModalProdiKampus.value = true
  loadingKampusProdi.value = true
  kampusProdiList.value = []
  try {
    const res = await fetch(`/pdss/kampus/${kampus.id}/prodi`)
    const json = await res.json()
    if (json.success) {
      kampusProdiList.value = json.data
    }
  } catch (e) {
    console.error(e)
  } finally {
    loadingKampusProdi.value = false
  }
}

const applyKampusFilter = () => {
  router.get('/bk/akademik', {
    tenant_id: selectedTenant.value,
    tahun_ajaran: filterTahunAjaran.value,
    search_kampus: searchKampusText.value,
    jenis_kampus: filterJenisKampus.value,
  }, { preserveState: true, preserveScroll: true })
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

      <!-- ===== BANNER FILTER SEKOLAH (KHUSUS SUPER ADMIN) STANDAR BAKU ===== -->
      <div v-if="isSuperAdmin" class="p-4 sm:px-5 rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5">
          <i class="bi bi-buildings text-blue-600 text-lg"></i>
          <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
            <i class="bi bi-funnel-fill me-1"></i> Aktif
          </span>

          <!-- Dropdown Filter Sekolah -->
          <div class="my-1 md:my-0">
            <select
              v-model="selectedTenant"
              @change="applyTenantFilter"
              id="pdss-tenant-selector"
              class="h-9 px-3 bg-white border border-blue-200 rounded-xl text-xs font-semibold text-slate-800 shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[240px] cursor-pointer"
            >
              <option value="">-- Semua Sekolah (Global) --</option>
              <option v-for="t in filteredTenants" :key="t.id" :value="t.id">
                {{ t.nama_sekolah }} {{ t.npsn ? `(${t.npsn})` : '' }}
              </option>
            </select>
          </div>
        </div>

        <!-- Info Text -->
        <div class="text-xs text-slate-500 font-medium whitespace-nowrap">
          Menampilkan data milik:
          <strong class="text-blue-700 font-bold ml-1">
            {{ getSelectedTenantName() }}
          </strong>
          <span class="text-slate-400 ml-1">(Super Admin)</span>
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
               class="p-3.5 rounded-2xl border transition relative group cursor-pointer"
               :class="activeTab === 'mapel' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[1]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1.5">
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
               class="p-3.5 rounded-2xl border transition relative group cursor-pointer"
               :class="activeTab === 'pemeringkatan' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[2]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1.5">
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
               class="p-3.5 rounded-2xl border transition relative group cursor-pointer"
               :class="activeTab === 'pengunduran' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[3]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1.5">
              <div class="flex items-center gap-1.5">
                <span class="text-2xs font-extrabold px-2 py-0.5 rounded-md"
                      :class="activeTab === 'pengunduran' ? 'bg-blue-600 text-white' : (workflowStatus[3]?.is_locked ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700')">
                  Langkah 3
                </span>
                <span v-if="tenantInfo?.total_pengunduran > 0" class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-2xs">
                  {{ tenantInfo?.total_pengunduran }}
                </span>
              </div>
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
               class="p-3.5 rounded-2xl border transition relative group cursor-pointer"
               :class="activeTab === 'simulasi' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[4]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1.5">
              <div class="flex items-center gap-1.5">
                <span class="text-2xs font-extrabold px-2 py-0.5 rounded-md"
                      :class="activeTab === 'simulasi' ? 'bg-blue-600 text-white' : (workflowStatus[4]?.is_locked ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700')">
                  Langkah 4
                </span>
                <span v-if="collisions.length > 0" class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-rose-500 text-white shadow-2xs">
                  {{ collisions.length }}
                </span>
              </div>
              <button @click.stop="toggleLockStep(4, workflowStatus[4]?.is_locked)" type="button" class="text-xs" :title="workflowStatus[4]?.is_locked ? 'Buka Kunci' : 'Kunci Tahap Ini'">
                <i v-if="workflowStatus[4]?.is_locked" class="bi bi-lock-fill text-emerald-600"></i>
                <i v-else class="bi bi-unlock text-slate-400 hover:text-blue-600"></i>
              </button>
            </div>
            <div class="text-xs font-bold text-slate-800">4. Simulasi PTN (1, 2, Finalisasi)</div>
            <p class="text-[10px] text-slate-500 mt-0.5">Simulasi draf, rasionalisasi & finalisasi</p>
          </div>

          <!-- Step 5: Katalog Master PTN -->
          <div @click="selectStepTab(5)" 
               class="p-3.5 rounded-2xl border transition relative group cursor-pointer"
               :class="activeTab === 'katalog' ? 'bg-blue-50/90 border-blue-500 shadow-xs ring-2 ring-blue-500/20' : (workflowStatus[5]?.is_locked ? 'bg-slate-50 border-emerald-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/80')">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-2xs font-extrabold px-2 py-0.5 rounded-md"
                    :class="activeTab === 'katalog' ? 'bg-blue-600 text-white' : (workflowStatus[5]?.is_locked ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700')">
                Langkah 5
              </span>
              <button @click.stop="toggleLockStep(5, workflowStatus[5]?.is_locked)" type="button" class="text-xs" :title="workflowStatus[5]?.is_locked ? 'Buka Kunci' : 'Kunci Tahap Ini'">
                <i v-if="workflowStatus[5]?.is_locked" class="bi bi-lock-fill text-emerald-600"></i>
                <i v-else class="bi bi-unlock text-slate-400 hover:text-blue-600"></i>
              </button>
            </div>
            <div class="text-xs font-bold text-slate-800">5. Katalog PTN</div>
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
            <button @click="openModalExport" type="button" class="btn btn-sm bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-3.5 py-1.5 text-xs font-bold shadow-2xs transition flex items-center gap-1.5">
              <i class="bi bi-file-earmark-arrow-down-fill text-sm"></i> Download Nilai Buku Induk
            </button>
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
            <!-- Filter Tingkat / Jenjang -->
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Tingkat / Jenjang</label>
              <select v-model="filterTingkatMapel" @change="handleTingkatChange" class="form-select form-select-sm rounded-xl border-slate-200 text-xs font-bold text-slate-700 bg-white shadow-2xs focus:ring-indigo-500 py-1.5 px-3 min-w-[140px]">
                <option value="">Semua Tingkat (Cohort)</option>
                <option value="X">Kelas X (Sem 1 - 2)</option>
                <option value="XI">Kelas XI (Sem 3 - 4)</option>
                <option value="XII">Kelas XII (Sem 5 - 6)</option>
              </select>
            </div>

            <!-- Filter Rumpun / Jurusan -->
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Rumpun / Jurusan</label>
              <select v-model="filterJurusanMapel" @change="applyMapelFilter" class="form-select form-select-sm rounded-xl border-slate-200 text-xs font-bold text-slate-700 bg-white shadow-2xs focus:ring-indigo-500 py-1.5 px-3 min-w-[150px]">
                <option value="">Semua Rumpun & Kurikulum</option>
                <option value="MIPA">MIPA / Saintek</option>
                <option value="IPS">IPS / Soshum</option>
                <option value="SMK">Kejuruan / Vokasi (SMK)</option>
              </select>
            </div>

            <!-- Filter Kelas / Rombel -->
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Kelas / Rombel</label>
              <select v-model="filterKelasMapel" @change="applyMapelFilter" class="form-select form-select-sm rounded-xl border-slate-200 text-xs font-bold text-slate-700 bg-white shadow-2xs focus:ring-indigo-500 py-1.5 px-3 min-w-[160px]">
                <option value="">Semua Kelas</option>
                <option v-for="k in availableKelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
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

        <!-- Banner Konteks Filter Aktif -->
        <div class="px-4 py-2.5 rounded-xl bg-blue-50/60 border border-blue-200/70 text-xs text-blue-900 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <i class="bi bi-funnel-fill text-blue-600 shrink-0"></i>
            <span>
              Menampilkan <strong>{{ localMapels.length }} Mata Pelajaran</strong> untuk 
              <strong class="text-blue-700 ml-1">{{ getMapelContextLabel() }}</strong> pada Tahun Ajaran <strong>{{ filterTahunAjaran }}</strong>
            </span>
          </div>
          <span v-if="filterKelasMapel || filterTingkatMapel || filterJurusanMapel" class="text-2xs font-extrabold bg-blue-600 text-white px-2.5 py-0.5 rounded-md self-start sm:self-auto shrink-0 shadow-2xs">
            <i class="bi bi-check-circle-fill me-1"></i> Filter Dinamis Aktif
          </span>
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

        <!-- Empty State jika belum ada mapel terdaftar di database -->
        <div v-if="localMapels.length === 0" class="p-8 rounded-2xl bg-slate-50/80 border border-dashed border-slate-300 text-center space-y-3">
          <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl shadow-2xs">
            <i class="bi bi-journal-x"></i>
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-800">Belum Ada Mata Pelajaran Terdaftar</h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
              Data mata pelajaran untuk <strong>{{ tenantInfo?.nama_sekolah || 'Sekolah Ini' }}</strong> pada T.A. <strong>{{ filterTahunAjaran }}</strong> belum tersedia di database atau belum ada riwayat nilai rapor siswa kelas 12.
            </p>
          </div>
          <div class="flex items-center justify-center gap-2 pt-1">
            <button @click="autoDetectFromRapor" type="button" class="btn btn-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
              <i class="bi bi-magic"></i> Coba Deteksi dari Rapor Siswa
            </button>
          </div>
        </div>

        <template v-else>
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
        </template>

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
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-4 rounded-xl bg-gradient-to-r from-emerald-50/80 to-blue-50/40 border border-emerald-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-base shadow-sm shrink-0">
              2
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-800">Langkah 2: Atur Kuota Eligible & Pemeringkatan Nilai 5 Semester</h3>
              <p class="text-xs text-slate-500 mt-0.5">Penetapan kuota siswa eligible berdasarkan akreditasi sekolah Kemendikbud (+5% e-Rapor) & pemeringkatan paralel nilai rapor siswa.</p>
            </div>
          </div>

          <div class="flex items-center gap-3 flex-wrap">
            <!-- Akreditasi Info -->
            <div class="bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs text-xs flex items-center gap-2">
              <span class="text-slate-500 font-semibold">Akreditasi:</span>
              <span class="badge bg-blue-600 text-white font-extrabold px-2 py-0.5 rounded-md">{{ tenantInfo?.akreditasi || 'A' }}</span>
              <span class="text-slate-400 font-medium text-[11px] border-l pl-2">Standar: {{ (tenantInfo?.akreditasi || '').includes('B') ? '25%' : ((tenantInfo?.akreditasi || '').includes('C') ? '5%' : '40%') }}</span>
            </div>

            <!-- Dropdown Pengaturan Kuota & Akreditasi -->
            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs">
              <label for="kuota-snbp-select" class="text-xs font-bold text-slate-700 whitespace-nowrap">Kuota SNBP:</label>
              <select id="kuota-snbp-select" 
                      v-model="selectedKuota" 
                      @change="updateKuota" 
                      class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-800 font-extrabold focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                <option :value="40">40% (Akreditasi A - Standar)</option>
                <option :value="45">45% (Akreditasi A + e-Rapor)</option>
                <option :value="25">25% (Akreditasi B - Standar)</option>
                <option :value="30">30% (Akreditasi B + e-Rapor)</option>
                <option :value="5">5% (Akreditasi C - Standar)</option>
                <option :value="10">10% (Akreditasi C + e-Rapor)</option>
                <option :value="15">15% (Custom)</option>
                <option :value="20">20% (Custom)</option>
                <option :value="35">35% (Custom)</option>
                <option :value="50">50% (Custom Khusus)</option>
              </select>
            </div>

            <!-- Checkbox Menggunakan e-Rapor -->
            <label class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border border-slate-200 shadow-2xs text-xs font-bold text-slate-700 cursor-pointer select-none hover:bg-slate-50 transition">
              <input type="checkbox" 
                     v-model="useERapor" 
                     @change="toggleERapor" 
                     class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
              <span>Gunakan e-Rapor (+5% Kuota SNBP)</span>
            </label>
          </div>
        </div>
        
        <!-- Filter Bar Atas 3-Bagian Baku -->
        <div class="p-3.5 bg-slate-50/70 border border-slate-200/80 rounded-xl overflow-x-auto no-scrollbar">
          <form @submit.prevent="applyFilters()" class="flex flex-row items-end gap-2.5 sm:gap-3 min-w-max">
            
            <!-- Filter Jurusan -->
            <div class="w-36 sm:w-44 shrink-0">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Jurusan / Peminatan</label>
              <select v-model="filterJurusan" @change="applyFilters()" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="">-- Semua Jurusan --</option>
                <option v-for="j in jurusanList" :key="j.id" :value="j.nama_jurusan">{{ j.nama_jurusan }}</option>
              </select>
            </div>

            <!-- Filter Tahun Ajaran SNBP -->
            <div class="w-36 sm:w-44 shrink-0">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Tahun Ajaran SNBP</label>
              <select v-model="filterTahunAjaran" @change="applyFilters()" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.nama_tahun_ajaran">
                  T.A. {{ ta.nama_tahun_ajaran }} {{ ta.is_active ? '(Aktif)' : '' }}
                </option>
              </select>
            </div>

            <!-- Filter Status Kelayakan -->
            <div class="w-36 sm:w-44 shrink-0">
              <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider whitespace-nowrap">Status Kelayakan</label>
              <select v-model="filterStatus" @change="applyFilters()" class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="">-- Semua Status --</option>
                <option value="eligible">Eligible Kuota SNBP</option>
                <option value="promoted">Eligible (Promosi Cadangan)</option>
                <option value="not_eligible">Tidak Eligible</option>
                <option value="resigned">Mengundurkan Diri</option>
              </select>
            </div>

            <!-- Input Pencarian Proporsional -->
            <div class="w-56 sm:w-64 md:w-72 shrink-0">
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

            <!-- Tombol Cari, Reset, Generate, Reset Eligible, & Download Nilai -->
            <div class="flex items-center gap-1.5 shrink-0">
              <button type="submit" class="h-9 px-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-1" title="Cari Data">
                <i class="bi bi-search"></i> Cari
              </button>
              <button type="button" @click="resetFilters" class="h-9 px-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 text-xs font-bold transition" title="Reset Filter">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
              
              <!-- Tombol Generate Siswa Eligible SNBP -->
              <button type="button" 
                      @click="generateEligibleAction" 
                      :disabled="isRecalculating"
                      class="h-9 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-2xs transition flex items-center gap-1.5"
                      title="Hitung ulang & generate pemeringkatan siswa eligible berdasarkan nilai rapor 5 semester dan kuota aktif">
                <i class="bi bi-arrow-repeat" :class="{'animate-spin': isRecalculating}"></i> Generate Siswa Eligible
              </button>

              <!-- Tombol Reset Seluruh Eligible Manual -->
              <button type="button" 
                      @click="resetAllEligibleAction" 
                      class="h-9 px-3 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold shadow-2xs transition flex items-center gap-1"
                      title="Kembalikan seluruh status kelayakan siswa ke kalkulasi otomatis kuota & nilai rapor">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Eligible
              </button>

              <button type="button" @click="openModalExport" class="h-9 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs transition flex items-center gap-1" title="Download Rekap Nilai Buku Induk">
                <i class="bi bi-file-earmark-arrow-down-fill"></i> Download Nilai
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
                    <button type="button" 
                            @click="openModalViewNilai(item)" 
                            class="font-black text-sm text-blue-700 font-mono hover:text-blue-900 hover:underline inline-flex items-center gap-1 group/btn" 
                            title="Klik untuk melihat rincian nilai Semester 1 - 5">
                      {{ Number(item.rata_rata_nilai).toFixed(2) }}
                      <i class="bi bi-file-earmark-spreadsheet text-xs text-blue-400 group-hover/btn:text-blue-700"></i>
                    </button>
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
                    <span v-if="item.status_pengunduran_diri" class="px-2.5 py-1 rounded-lg text-2xs font-black bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                      <i class="bi bi-person-x-fill"></i> Mengundurkan Diri
                    </span>
                    <span v-else-if="item.is_cadangan_promosi" class="px-2.5 py-1 rounded-lg text-2xs font-black bg-purple-100 text-purple-800 border border-purple-200 inline-flex items-center gap-1">
                      <i class="bi bi-arrow-up-circle-fill"></i> Eligible (Promosi Cadangan)
                    </span>
                    <span v-else-if="item.is_eligible" class="px-2.5 py-1 rounded-lg text-2xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                      <i class="bi bi-check-circle-fill"></i> Eligible Kuota ({{ tenantInfo?.kuota_persen || 40 }}%)
                    </span>
                    <span v-else class="px-2.5 py-1 rounded-lg text-2xs font-semibold bg-slate-100 text-slate-500">
                      Cadangan (Rank {{ item.ranking_sekolah }})
                    </span>
                  </td>

                  <!-- Sticky Action Column -->
                  <td class="py-3 px-4 text-center sticky right-0 bg-white group-hover:bg-blue-50/90 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] border-l border-slate-100 z-10 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1">
                      <!-- Tombol View Nilai 5 Semester -->
                      <button @click="openModalViewNilai(item)" 
                              type="button" 
                              class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs transition shadow-2xs" 
                              title="Lihat Transkrip Nilai Semester 1 - 5">
                        <i class="bi bi-eye-fill"></i>
                      </button>

                      <!-- Tombol Override Status -->
                      <button @click="openModalOverride(item)" 
                              type="button" 
                              class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs transition" 
                              title="Override Status Eligible Manual">
                        <i class="bi bi-sliders"></i>
                      </button>

                      <!-- Tombol Pengunduran Diri -->
                      <button v-if="item.is_eligible && !item.status_pengunduran_diri" 
                              @click="openModalPengunduran(item)" 
                              type="button" 
                              class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs transition" 
                              title="Catat Pengunduran Diri">
                        <i class="bi bi-person-x"></i>
                      </button>

                      <!-- Tombol Batalkan Pengunduran Diri -->
                      <button v-if="item.status_pengunduran_diri" 
                              @click="batalPengunduranAction(item)" 
                              type="button" 
                              class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs transition" 
                              title="Batalkan Pengunduran Diri (Pulihkan Status Siswa)">
                        <i class="bi bi-arrow-counterclockwise"></i>
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="paginatedRankingList.length === 0">
                  <td colspan="7" class="py-12 text-center text-slate-400">
                    <div v-if="(rankingData?.total_siswa || 0) === 0" class="space-y-2">
                      <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl">
                        <i class="bi bi-people"></i>
                      </div>
                      <div class="font-bold text-slate-700 text-sm">Tidak Ada Siswa Kelas 12 Aktif</div>
                      <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Pada Tahun Ajaran <strong>{{ filterTahunAjaran }}</strong>, sekolah <strong>{{ tenantInfo?.nama_sekolah }}</strong> belum memiliki riwayat siswa Kelas 12 aktif di database.
                      </p>
                    </div>
                    <div v-else>
                      <i class="bi bi-inbox text-3xl mb-2 block text-slate-300"></i>
                      Tidak ada data siswa yang cocok dengan filter pencarian atau jurusan yang dipilih.
                    </div>
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
                  <th class="py-3 px-4 text-center sticky right-0 bg-slate-50 z-10 w-28">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="p in pengunduranDiriList" :key="p.id" class="hover:bg-slate-50 transition group">
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
                  <td class="py-3 px-4 text-center sticky right-0 bg-white group-hover:bg-slate-50 shadow-[-4px_0_6px_rgba(15,23,42,0.04)] border-l border-slate-100 z-10 whitespace-nowrap">
                    <button @click="batalPengunduranAction(p)" 
                            type="button" 
                            class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-2xs font-bold transition flex items-center justify-center gap-1 shadow-2xs mx-auto" 
                            title="Batalkan Pengunduran Diri (Pulihkan Siswa)">
                      <i class="bi bi-arrow-counterclockwise"></i> Batalkan
                    </button>
                  </td>
                </tr>
                <tr v-if="pengunduranDiriList?.length === 0">
                  <td colspan="6" class="py-10 text-center text-slate-400">
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



        <!-- Tabel Pilihan Kampus Siswa Eligible pada Simulasi Aktif -->
        <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs bg-white space-y-0">
          
          <!-- Header & Toolbar Filter Langkah 4 -->
          <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div>
              <h4 class="font-black text-slate-800 text-xs flex items-center gap-2">
                <i class="bi bi-list-check text-blue-600 text-sm"></i>
                Daftar Pilihan Siswa Eligible (Simulasi {{ activeSimulasi }})
              </h4>
              <p class="text-[11px] text-slate-500 mt-0.5">
                Menampilkan pilihan PTN & program studi untuk <strong>{{ filteredEligibleList.length }} siswa eligible</strong> aktif.
              </p>
            </div>

            <!-- Toolbar Filter Pencarian & Status -->
            <div class="flex items-center gap-2 flex-wrap">
              <!-- Search Box -->
              <div class="relative min-w-[200px] flex-1 sm:flex-initial">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       v-model="searchSimulasi" 
                       @input="currentPageSimulasi = 1"
                       placeholder="Cari siswa, NISN, PTN, prodi..." 
                       class="w-full h-8 pl-8 pr-7 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 shadow-2xs" />
                <button v-if="searchSimulasi" 
                        @click="searchSimulasi = ''; currentPageSimulasi = 1" 
                        type="button" 
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                  <i class="bi bi-x-circle-fill"></i>
                </button>
              </div>

              <!-- Filter Jurusan -->
              <select v-model="filterJurusanSimulasi" 
                      @change="currentPageSimulasi = 1" 
                      class="h-8 px-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 shadow-2xs">
                <option value="all">Semua Jurusan</option>
                <option v-for="j in jurusanList" :key="j.id" :value="j.nama_jurusan">{{ j.nama_jurusan }}</option>
              </select>

              <!-- Filter Status Pengisian -->
              <select v-model="filterStatusSimulasi" 
                      @change="currentPageSimulasi = 1" 
                      class="h-8 px-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 shadow-2xs">
                <option value="all">Semua Status Pilihan</option>
                <option value="lengkap">Sudah Lengkap (Pilihan 1 & 2)</option>
                <option value="sebagian">Hanya 1 Pilihan</option>
                <option value="kosong">Belum Mengisi Pilihan</option>
                <option value="bentrok">⚠️ Hanya yang Bentrok</option>
              </select>
            </div>
          </div>

          <!-- Content Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="py-3 px-4 w-12 text-center">Rank</th>
                  <th class="py-3 px-4">Nama Siswa</th>
                  <th class="py-3 px-3">Jurusan / Kelas</th>
                  <th class="py-3 px-4 bg-blue-50/40">Pilihan 1 (PTN & Prodi)</th>
                  <th class="py-3 px-4 bg-indigo-50/40">Pilihan 2 (PTN & Prodi)</th>
                  <th class="py-3 px-4 text-center sticky right-0 bg-slate-50 z-10 w-24">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="item in paginatedSimulasiList" :key="item.siswa_id" class="hover:bg-blue-50/40 transition group">
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
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">
                      {{ item.kelas_saat_ini || item.jurusan }}
                    </span>
                  </td>

                  <!-- Pilihan 1 -->
                  <td class="py-3 px-4 bg-blue-50/10">
                    <div v-if="item.pilihan_1" class="text-xs space-y-1">
                      <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="font-bold text-blue-700">{{ item.pilihan_1.nama_kampus }}</span>
                        <!-- Tombol Badge Bentrok Pilihan 1 jika ada bentrok -->
                        <button v-if="item.pilihan_1.is_bentrok" 
                                @click="openModalDetailKonflik(item, 1)" 
                                type="button" 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-extrabold bg-rose-100 text-rose-700 hover:bg-rose-200 border border-rose-300 transition shadow-2xs">
                          <i class="bi bi-exclamation-triangle-fill text-rose-600"></i> Bentrok ({{ item.pilihan_1.total_bentrok }} Siswa)
                        </button>
                      </div>
                      <div class="text-slate-500 text-2xs">{{ item.pilihan_1.nama_prodi }} ({{ item.pilihan_1.jenjang }})</div>
                    </div>
                    <button v-else-if="!simulasiStats?.[activeSimulasi]?.is_permanen" @click="openModalPilihan(item, 1)" type="button" class="text-2xs text-blue-600 font-bold hover:underline flex items-center gap-1">
                      <i class="bi bi-plus-circle"></i> Isi Pilihan 1 (Sim {{ activeSimulasi }})
                    </button>
                    <span v-else class="text-2xs text-slate-400 font-medium">Belum Diisi</span>
                  </td>

                  <!-- Pilihan 2 -->
                  <td class="py-3 px-4 bg-indigo-50/10">
                    <div v-if="item.pilihan_2" class="text-xs space-y-1">
                      <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="font-bold text-indigo-700">{{ item.pilihan_2.nama_kampus }}</span>
                        <!-- Tombol Badge Bentrok Pilihan 2 jika ada bentrok -->
                        <button v-if="item.pilihan_2.is_bentrok" 
                                @click="openModalDetailKonflik(item, 2)" 
                                type="button" 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-extrabold bg-rose-100 text-rose-700 hover:bg-rose-200 border border-rose-300 transition shadow-2xs">
                          <i class="bi bi-exclamation-triangle-fill text-rose-600"></i> Bentrok ({{ item.pilihan_2.total_bentrok }} Siswa)
                        </button>
                      </div>
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

                <tr v-if="paginatedSimulasiList.length === 0">
                  <td colspan="6" class="py-10 text-center text-slate-400 space-y-1">
                    <i class="bi bi-inbox text-3xl text-slate-300 block mb-1"></i>
                    <div class="font-bold text-slate-600 text-xs">Tidak ada data siswa eligible yang cocok dengan filter</div>
                    <p class="text-2xs text-slate-400">Silakan ubah kata kunci pencarian atau filter jurusan/status pilihan di atas.</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer Smart Pagination Windowing Langkah 4 (Standar AGENTS.md) -->
          <div v-if="simulasiPaginationInfo.total > 0" class="flex flex-col md:flex-row justify-between items-center gap-3.5 p-4 bg-slate-50/50 border-t border-slate-200/80">
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 text-xs text-slate-500 font-medium shrink-0">
              <span>Tampilkan</span>
              <select v-model="perPageSimulasi" @change="currentPageSimulasi = 1" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 shadow-2xs">
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span class="whitespace-nowrap">baris per halaman</span>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <span class="whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-800">{{ simulasiPaginationInfo.from }}</span> s.d. <span class="font-bold text-slate-800">{{ simulasiPaginationInfo.to }}</span> dari <span class="font-bold text-slate-800">{{ simulasiPaginationInfo.total }}</span> siswa eligible
              </span>
            </div>

            <div class="flex items-center justify-center md:justify-end gap-1 shrink-0 flex-wrap">
              <button type="button" @click="goToSimulasiPage(currentPageSimulasi - 1)" :disabled="currentPageSimulasi === 1" class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs disabled:opacity-40" title="Halaman Sebelumnya">
                <i class="bi bi-chevron-left text-xs"></i>
              </button>

              <template v-for="(p, i) in smartSimulasiPageNumbers" :key="i">
                <button v-if="p !== '...'" type="button" @click="goToSimulasiPage(p)" class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center" :class="p === currentPageSimulasi ? 'bg-blue-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs'">
                  {{ p }}
                </button>
                <span v-else class="min-w-[32px] h-8 text-xs font-bold flex items-center justify-center text-slate-400">...</span>
              </template>

              <button type="button" @click="goToSimulasiPage(currentPageSimulasi + 1)" :disabled="currentPageSimulasi === totalSimulasiPages" class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs disabled:opacity-40" title="Halaman Selanjutnya">
                <i class="bi bi-chevron-right text-xs"></i>
              </button>
            </div>
          </div>

        </div>

        <!-- Tombol Lanjut ke Langkah 5 -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <span class="text-xs text-slate-500 font-medium">Setelah pilihan Simulasi dikunci permanen, lanjutkan ke Langkah 5 untuk memeriksa katalog master PTN & ekspor.</span>
          <button @click="activeTab = 'katalog'" type="button" class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
            Lanjut ke Langkah 5: Katalog PTN <i class="bi bi-arrow-right"></i>
          </button>
        </div>

      </div>

      <!-- ═══════════════════════════════════════════════════════════
           TAB 5: KATALOG MASTER KAMPUS & PRODI SNPMB (LANGKAH 5)
      ════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'katalog'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden space-y-4 p-5">
        <!-- Header Info Tab 5 -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl bg-gradient-to-r from-blue-50/80 via-indigo-50/40 to-slate-50 border border-blue-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-base shadow-sm shrink-0">
              5
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-800">Direktori Master Kampus PTN & Program Studi SNPMB</h3>
              <p class="text-xs text-slate-500 mt-0.5">Database resmi 147 PTN & 5.142 Program Studi SNPMB Seluruh Indonesia (Terintegrasi 21.668 Rekaman Riwayat Peminat & Keketatan).</p>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <span class="px-3 py-1.5 rounded-xl bg-white border border-blue-200 shadow-2xs text-xs font-bold text-blue-700 flex items-center gap-1.5">
              <i class="bi bi-buildings-fill text-blue-600"></i> Total {{ masterKampusList?.total || 147 }} PTN Terdaftar
            </span>
          </div>
        </div>

        <!-- Filter & Toolbar Aksi Master Kampus -->
        <div class="p-3.5 bg-slate-50/70 border border-slate-200/80 rounded-xl flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          <!-- Search & Filter Jenis -->
          <div class="flex items-center gap-2.5 flex-1 flex-wrap">
            <!-- Search Box Kampus & Prodi -->
            <div class="relative flex-1 min-w-[280px]">
              <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
              <input type="text" 
                     v-model="searchKampusText" 
                     @input="handleSearchKampusDebounce"
                     @keyup.enter="applyKampusFilter"
                     placeholder="Cari nama kampus PTN, jurusan/prodi, fakultas, kota, atau provinsi..." 
                     class="w-full h-9 pl-9 pr-8 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
              <button v-if="searchKampusText" 
                      @click="searchKampusText = ''; applyKampusFilter()" 
                      type="button" 
                      class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs" 
                      title="Bersihkan Pencarian">
                <i class="bi bi-x-circle-fill"></i>
              </button>
            </div>

            <!-- Filter Jenis PTN -->
            <div class="w-44">
              <select v-model="filterJenisKampus" @change="applyKampusFilter" class="w-full h-9 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="all">Semua Jenis PTN</option>
                <option value="Negeri">PTN Akademik (Universitas/Institut)</option>
                <option value="Politeknik">PTN Vokasi (Politeknik Negeri)</option>
                <option value="ISBI">PTN Seni & Budaya (ISBI/ISI)</option>
              </select>
            </div>

            <button type="button" @click="applyKampusFilter" class="h-9 px-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-1">
              <i class="bi bi-search"></i> Cari
            </button>
          </div>

          <!-- Tombol Aksi Toolbar: Tambah, Export, Import -->
          <div class="flex items-center gap-2 flex-wrap shrink-0">
            <button @click="openModalCreateKampus" type="button" class="h-9 px-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5">
              <i class="bi bi-plus-circle-fill"></i> Tambah PTN Baru
            </button>
            <button @click="handleExportMasterKampus" type="button" class="h-9 px-3.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5">
              <i class="bi bi-file-earmark-excel-fill text-emerald-400"></i> Unduh Excel (.XLSX)
            </button>
            <button @click="openModalImportKampus" type="button" class="h-9 px-3.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold shadow-2xs transition flex items-center gap-1.5">
              <i class="bi bi-upload text-blue-600"></i> Impor Data
            </button>
          </div>
        </div>

        <!-- Tabel Data Master Kampus -->
        <div class="border border-slate-200/80 rounded-2xl overflow-hidden bg-white shadow-2xs">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 min-w-[1150px]">
              <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="py-3 px-4 w-12 text-center whitespace-nowrap">NO</th>
                  <th class="py-3 px-4 min-w-[320px] whitespace-nowrap">Nama Kampus PTN</th>
                  <th class="py-3 px-4 text-center min-w-[180px] whitespace-nowrap">Jenis Kampus</th>
                  <th class="py-3 px-4 text-center min-w-[140px] whitespace-nowrap">Akreditasi</th>
                  <th class="py-3 px-4 min-w-[200px] whitespace-nowrap">Kota & Provinsi</th>
                  <th class="py-3 px-4 text-center min-w-[130px] whitespace-nowrap">Total Prodi</th>
                  <th class="py-3 px-4 text-center min-w-[160px] whitespace-nowrap">Aksi Manajemen</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="(k, idx) in masterKampusList?.data || []" :key="k.id" class="hover:bg-blue-50/30 transition group">
                  <td class="py-3 px-4 text-center font-mono text-slate-400 whitespace-nowrap">
                    {{ ((masterKampusList?.current_page || 1) - 1) * (masterKampusList?.per_page || 15) + idx + 1 }}
                  </td>
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm shrink-0 border border-blue-100">
                        <i class="bi bi-building"></i>
                      </div>
                      <div>
                        <div class="font-bold text-slate-800 text-xs">{{ k.nama_kampus }}</div>
                        <div v-if="k.alamat || k.alamat_kampus" class="text-[10px] font-normal text-slate-400 mt-0.5 max-w-sm truncate">
                          {{ k.alamat || k.alamat_kampus }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold inline-flex items-center justify-center"
                          :class="k.nama_kampus?.includes('POLITEKNIK') ? 'bg-amber-50 text-amber-700 border border-amber-200' : (k.nama_kampus?.includes('ISBI') || k.nama_kampus?.includes('ISI') ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200')">
                      {{ k.nama_kampus?.includes('POLITEKNIK') ? 'PTN Vokasi (Politeknik)' : (k.nama_kampus?.includes('ISBI') || k.nama_kampus?.includes('ISI') ? 'PTN Seni (ISBI/ISI)' : 'PTN Akademik') }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span class="font-black text-xs px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center justify-center shadow-2xs">
                      {{ k.akreditasi ? (k.akreditasi.includes('Unggul') || k.akreditasi.length > 2 ? k.akreditasi : k.akreditasi + ' (Unggul)') : 'A (Unggul)' }}
                    </span>
                  </td>
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-semibold text-slate-700 text-xs">{{ k.kota || k.kota_kampus || '-' }}</div>
                    <div class="text-[10px] text-slate-400">{{ k.provinsi || 'Indonesia' }}</div>
                  </td>
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 inline-flex items-center justify-center shadow-2xs">
                      {{ k.prodi_count || 0 }} Prodi
                    </span>
                  </td>
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                      <!-- Tombol Lihat Prodi -->
                      <button @click="openDetailProdi(k)" type="button" class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-2.5 py-1.5 text-xs font-bold shadow-2xs transition flex items-center gap-1" title="Lihat Katalog Program Studi & Analisis Riwayat">
                        <i class="bi bi-list-nested"></i> Prodi
                      </button>

                      <!-- Tombol Edit Kampus -->
                      <button @click="openModalEditKampus(k)" type="button" class="p-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs transition" title="Edit Identitas Kampus PTN">
                        <i class="bi bi-pencil-square"></i>
                      </button>

                      <!-- Tombol Hapus Kampus -->
                      <button @click="confirmDeleteKampus(k)" type="button" class="p-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs transition" title="Hapus Kampus PTN">
                        <i class="bi bi-trash"></i>
                      </button>

                      <!-- Link Website Resmi -->
                      <a v-if="k.web" :href="k.web.startsWith('http') ? k.web : 'https://' + k.web" target="_blank" class="p-1.5 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 text-xs transition border border-slate-200" title="Buka Website Resmi PTN">
                        <i class="bi bi-box-arrow-up-right"></i>
                      </a>
                    </div>
                  </td>
                </tr>

                <tr v-if="(masterKampusList?.data || []).length === 0">
                  <td colspan="7" class="py-12 text-center text-slate-400">
                    <i class="bi bi-building-slash text-3xl mb-2 block text-slate-300"></i>
                    Tidak ada kampus PTN yang cocok dengan kata kunci pencarian.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Kampus Links -->
          <div v-if="masterKampusList?.links?.length > 3" class="flex flex-col sm:flex-row justify-between items-center gap-3 p-4 bg-slate-50/50 border-t border-slate-200">
            <span class="text-xs text-slate-500">
              Menampilkan <strong>{{ masterKampusList?.from || 0 }}</strong> s.d. <strong>{{ masterKampusList?.to || 0 }}</strong> dari <strong>{{ masterKampusList?.total || 0 }}</strong> Perguruan Tinggi Negeri
            </span>
            <div class="flex items-center gap-1 flex-wrap">
              <Link v-for="(link, i) in masterKampusList.links" :key="i"
                    :href="link.url || '#'"
                    v-html="link.label"
                    preserve-scroll
                    preserve-state
                    class="min-w-[32px] h-8 px-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center border"
                    :class="link.active ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : (link.url ? 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200' : 'bg-slate-50 text-slate-300 border-slate-200 pointer-events-none')">
              </Link>
            </div>
          </div>
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
         MODAL: DOWNLOAD REKAP NILAI BUKU INDUK (MULTI-FILTER)
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModalExport" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
              </div>
              <div>
                <h3 class="font-black text-slate-800 text-base">Download Rekap Nilai</h3>
                <p class="text-[11px] text-slate-400">Ekspor nilai buku induk rapor ke spreadsheet (CSV/Excel)</p>
              </div>
            </div>
            <button @click="showModalExport = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form @submit.prevent="handleExportNilai" class="space-y-3.5">
            <!-- Pilihan Tahun Ajaran -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
              <select v-model="exportTahunAjaran" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option v-for="ta in tahunAjaranList" :key="ta.id" :value="ta.nama_tahun_ajaran">
                  T.A. {{ ta.nama_tahun_ajaran }} {{ ta.is_active ? '(Aktif)' : '' }}
                </option>
              </select>
            </div>

            <!-- Pilihan Tingkat / Jenjang -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tingkat / Jenjang</label>
              <select v-model="exportJenjang" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="all">Semua Jenjang (X, XI, XII)</option>
                <option value="XII">Kelas XII (Khusus PDSS / Calon Lulusan)</option>
                <option value="XI">Kelas XI</option>
                <option value="X">Kelas X</option>
              </select>
            </div>

            <!-- Pilihan Semester -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Semester Rapor</label>
              <select v-model="exportSemester" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="all">Semua Semester (1 s.d. 5 / 6)</option>
                <option value="1">Semester 1 (Ganjil Kls X)</option>
                <option value="2">Semester 2 (Genap Kls X)</option>
                <option value="3">Semester 3 (Ganjil Kls XI)</option>
                <option value="4">Semester 4 (Genap Kls XI)</option>
                <option value="5">Semester 5 (Ganjil Kls XII)</option>
                <option value="6">Semester 6 (Genap Kls XII)</option>
              </select>
            </div>

            <!-- Pilihan Kelas / Rombel -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kelas / Rombel</label>
              <select v-model="exportKelasId" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="all">-- Semua Kelas / Rombel --</option>
                <option v-for="k in availableKelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
              </select>
            </div>

            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-900 text-xs">
              <i class="bi bi-info-circle-fill text-emerald-600 me-1"></i>
              File rekap akan diunduh dalam format <strong>CSV (UTF-8 Excel Ready)</strong> yang memuat nilai akhir seluruh mapel, predikat, dan capaian kompetensi siswa.
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
              <button @click="showModalExport = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                Batal
              </button>
              <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs flex items-center gap-1.5">
                <i class="bi bi-download"></i> Unduh File CSV
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: FORM TAMBAH / EDIT KAMPUS PTN (LANGKAH 5)
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModalFormKampus" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-black text-slate-800 text-base">{{ isEditKampus ? 'Edit Kampus PTN' : 'Tambah Kampus PTN Baru' }}</h3>
              <p class="text-xs text-slate-500">Direktori Master Perguruan Tinggi Negeri SNPMB</p>
            </div>
            <button @click="showModalFormKampus = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form @submit.prevent="submitFormKampus" class="space-y-3.5 pt-2">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kampus PTN <span class="text-rose-500">*</span></label>
              <input type="text" v-model="formKampus.nama_kampus" placeholder="Contoh: UNIVERSITAS INDONESIA" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kampus</label>
                <select v-model="formKampus.jenis" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                  <option value="Negeri">PTN Akademik (Universitas/Institut)</option>
                  <option value="Politeknik">PTN Vokasi (Politeknik Negeri)</option>
                  <option value="ISBI">PTN Seni & Budaya (ISBI/ISI)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Akreditasi</label>
                <select v-model="formKampus.akreditasi" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                  <option value="A">A (Unggul)</option>
                  <option value="B">B (Baik Sekali)</option>
                  <option value="C">C (Baik)</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kota</label>
                <input type="text" v-model="formKampus.kota" placeholder="Contoh: Kota Depok" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Provinsi</label>
                <input type="text" v-model="formKampus.provinsi" placeholder="Contoh: Jawa Barat" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Website Resmi PTN</label>
              <input type="text" v-model="formKampus.web" placeholder="https://..." class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Kampus</label>
              <textarea v-model="formKampus.alamat" rows="2" placeholder="Alamat lengkap kampus..." class="w-full p-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
              <button @click="showModalFormKampus = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                Batal
              </button>
              <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs">
                {{ isEditKampus ? 'Simpan Perubahan' : 'Tambah Kampus PTN' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: DETAIL & MANAJEMEN PROGRAM STUDI KAMPUS PTN
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModalProdiKampus" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-7xl w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10 space-y-4 max-h-[92vh] flex flex-col">
          <div class="flex items-start justify-between pb-3 border-b border-slate-100 shrink-0">
            <div>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-2xs font-extrabold bg-blue-100 text-blue-800 mb-1">
                Katalog Program Studi SNPMB & Analisis Keketatan
              </span>
              <h3 class="font-black text-slate-800 text-lg">{{ selectedKampusDetail?.nama_kampus }}</h3>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ selectedKampusDetail?.kota || selectedKampusDetail?.kota_kampus || '-' }}, {{ selectedKampusDetail?.provinsi || '-' }} | Akreditasi: <strong>{{ selectedKampusDetail?.akreditasi ? (selectedKampusDetail?.akreditasi.includes('Unggul') ? selectedKampusDetail?.akreditasi : selectedKampusDetail?.akreditasi + ' (Unggul)') : 'A (Unggul)' }}</strong>
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="openModalCreateProdi(selectedKampusDetail)" type="button" class="btn btn-sm bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-3 py-1.5 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                <i class="bi bi-plus-circle-fill"></i> Tambah Prodi Baru
              </button>
              <button @click="showModalProdiKampus = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg p-1">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loadingKampusProdi" class="py-12 text-center text-slate-400 space-y-2">
            <div class="spinner-border text-blue-600 w-8 h-8"></div>
            <div class="text-xs font-bold">Memuat daftar program studi & riwayat keketatan...</div>
          </div>

          <!-- Tabel Daftar Prodi Langsung dengan Riwayat Keketatan 2025-2021 -->
          <div v-else class="overflow-x-auto overflow-y-auto flex-1 border border-slate-200/80 rounded-2xl bg-white shadow-2xs">
            <table class="w-full text-left text-xs text-slate-600 min-w-[1320px]">
              <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200 sticky top-0 bg-slate-50 z-10">
                <tr>
                  <th class="py-3 px-3 w-10 text-center whitespace-nowrap">NO</th>
                  <th class="py-3 px-3 min-w-[240px] whitespace-nowrap">Nama Program Studi</th>
                  <th class="py-3 px-3 text-center whitespace-nowrap">Jenjang</th>
                  <th class="py-3 px-3 text-center whitespace-nowrap">Daya Tampung</th>
                  <th class="py-3 px-3 text-center whitespace-nowrap">Portofolio</th>
                  <th class="py-3 px-3 text-center min-w-[110px] whitespace-nowrap bg-blue-50 text-blue-800 border-x border-blue-100">
                    Keketatan 2025
                  </th>
                  <th class="py-3 px-3 text-center min-w-[110px] whitespace-nowrap bg-slate-100/70 border-r border-slate-200">
                    Keketatan 2024
                  </th>
                  <th class="py-3 px-3 text-center min-w-[110px] whitespace-nowrap bg-slate-100/70 border-r border-slate-200">
                    Keketatan 2023
                  </th>
                  <th class="py-3 px-3 text-center min-w-[110px] whitespace-nowrap bg-slate-100/70 border-r border-slate-200">
                    Keketatan 2022
                  </th>
                  <th class="py-3 px-3 text-center min-w-[110px] whitespace-nowrap bg-slate-100/70 border-r border-slate-200">
                    Keketatan 2021
                  </th>
                  <th class="py-3 px-3 text-center min-w-[100px] whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="(p, idx) in kampusProdiList" :key="p.id" class="hover:bg-blue-50/20 transition">
                  <td class="py-2.5 px-3 text-center font-mono text-slate-400 whitespace-nowrap">{{ idx + 1 }}</td>
                  <td class="py-2.5 px-3 whitespace-nowrap">
                    <div class="font-bold text-slate-800 text-xs">{{ p.nama_prodi || p.program_studi }}</div>
                    <div v-if="p.fakultas" class="text-[10px] font-normal text-slate-400 mt-0.5">{{ p.fakultas }}</div>
                  </td>
                  <td class="py-2.5 px-3 text-center whitespace-nowrap">
                    <span class="px-2 py-0.5 rounded-md text-2xs font-extrabold inline-flex items-center justify-center"
                          :class="p.jenjang === 'S1' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                      {{ p.jenjang || 'S1' }}
                    </span>
                  </td>
                  <td class="py-2.5 px-3 text-center whitespace-nowrap font-bold text-slate-800">
                    {{ p.daya_tampung_sekarang || '-' }} <span class="text-[10px] font-normal text-slate-400">mhs</span>
                  </td>
                  <td class="py-2.5 px-3 text-center whitespace-nowrap">
                    <span v-if="p.jenis_portofolio && p.jenis_portofolio !== 'Tidak Ada'" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center justify-center">
                      {{ p.jenis_portofolio }}
                    </span>
                    <span v-else class="text-slate-300">-</span>
                  </td>

                  <!-- Keketatan 2025 (Terbaru - Kiri) -->
                  <td class="py-2.5 px-3 text-center whitespace-nowrap bg-blue-50/20 border-x border-blue-100">
                    <div v-if="p.riwayat_map?.['2025']" class="flex flex-col items-center">
                      <span class="px-2 py-0.5 rounded-md text-[11px] font-black border shadow-2xs"
                            :class="getKeketatanBadgeClass(p.riwayat_map['2025']?.keketatan)">
                        {{ p.riwayat_map['2025']?.keketatan || '-' }}
                      </span>
                      <div class="text-[9px] text-slate-500 font-medium mt-0.5">
                        {{ (p.riwayat_map['2025']?.jumlah_pendaftar || 0).toLocaleString('id-ID') }} peminat
                      </div>
                    </div>
                    <div v-else class="text-slate-300 font-mono text-2xs">-</div>
                  </td>

                  <!-- Keketatan 2024 -->
                  <td class="py-2.5 px-3 text-center whitespace-nowrap border-r border-slate-100">
                    <div v-if="p.riwayat_map?.['2024']" class="flex flex-col items-center">
                      <span class="px-2 py-0.5 rounded-md text-[11px] font-black border shadow-2xs"
                            :class="getKeketatanBadgeClass(p.riwayat_map['2024']?.keketatan)">
                        {{ p.riwayat_map['2024']?.keketatan || '-' }}
                      </span>
                      <div class="text-[9px] text-slate-500 font-medium mt-0.5">
                        {{ (p.riwayat_map['2024']?.jumlah_pendaftar || 0).toLocaleString('id-ID') }} peminat
                      </div>
                    </div>
                    <div v-else class="text-slate-300 font-mono text-2xs">-</div>
                  </td>

                  <!-- Keketatan 2023 -->
                  <td class="py-2.5 px-3 text-center whitespace-nowrap border-r border-slate-100">
                    <div v-if="p.riwayat_map?.['2023']" class="flex flex-col items-center">
                      <span class="px-2 py-0.5 rounded-md text-[11px] font-black border shadow-2xs"
                            :class="getKeketatanBadgeClass(p.riwayat_map['2023']?.keketatan)">
                        {{ p.riwayat_map['2023']?.keketatan || '-' }}
                      </span>
                      <div class="text-[9px] text-slate-500 font-medium mt-0.5">
                        {{ (p.riwayat_map['2023']?.jumlah_pendaftar || 0).toLocaleString('id-ID') }} peminat
                      </div>
                    </div>
                    <div v-else class="text-slate-300 font-mono text-2xs">-</div>
                  </td>

                  <!-- Keketatan 2022 -->
                  <td class="py-2.5 px-3 text-center whitespace-nowrap border-r border-slate-100">
                    <div v-if="p.riwayat_map?.['2022']" class="flex flex-col items-center">
                      <span class="px-2 py-0.5 rounded-md text-[11px] font-black border shadow-2xs"
                            :class="getKeketatanBadgeClass(p.riwayat_map['2022']?.keketatan)">
                        {{ p.riwayat_map['2022']?.keketatan || '-' }}
                      </span>
                      <div class="text-[9px] text-slate-500 font-medium mt-0.5">
                        {{ (p.riwayat_map['2022']?.jumlah_pendaftar || 0).toLocaleString('id-ID') }} peminat
                      </div>
                    </div>
                    <div v-else class="text-slate-300 font-mono text-2xs">-</div>
                  </td>

                  <!-- Keketatan 2021 (Terlama - Kanan) -->
                  <td class="py-2.5 px-3 text-center whitespace-nowrap border-r border-slate-100">
                    <div v-if="p.riwayat_map?.['2021']" class="flex flex-col items-center">
                      <span class="px-2 py-0.5 rounded-md text-[11px] font-black border shadow-2xs"
                            :class="getKeketatanBadgeClass(p.riwayat_map['2021']?.keketatan)">
                        {{ p.riwayat_map['2021']?.keketatan || '-' }}
                      </span>
                      <div class="text-[9px] text-slate-500 font-medium mt-0.5">
                        {{ (p.riwayat_map['2021']?.jumlah_pendaftar || 0).toLocaleString('id-ID') }} peminat
                      </div>
                    </div>
                    <div v-else class="text-slate-300 font-mono text-2xs">-</div>
                  </td>

                  <!-- Aksi Edit & Hapus -->
                  <td class="py-2.5 px-3 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                      <!-- Tombol Edit Prodi -->
                      <button @click="openModalEditProdi(p)" type="button" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs transition" title="Edit Program Studi">
                        <i class="bi bi-pencil-square"></i>
                      </button>

                      <!-- Tombol Hapus Prodi -->
                      <button @click="confirmDeleteProdi(p)" type="button" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs transition" title="Hapus Program Studi">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="kampusProdiList.length === 0">
                  <td colspan="11" class="py-12 text-center text-slate-400">
                    <i class="bi bi-folder2-open text-3xl mb-2 block text-slate-300"></i>
                    Belum ada data program studi yang terdaftar untuk kampus ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between pt-3 border-t border-slate-100 shrink-0">
            <span class="text-xs text-slate-500 font-bold">Total {{ kampusProdiList.length }} Program Studi Terdaftar</span>
            <button @click="showModalProdiKampus = false" type="button" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: FORM TAMBAH / EDIT PROGRAM STUDI (LANGKAH 5)
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModalFormProdi" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-black text-slate-800 text-base">{{ isEditProdi ? 'Edit Program Studi' : 'Tambah Program Studi Baru' }}</h3>
              <p class="text-xs text-slate-500">{{ selectedKampusDetail?.nama_kampus }}</p>
            </div>
            <button @click="showModalFormProdi = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form @submit.prevent="submitFormProdi" class="space-y-3.5 pt-2">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Program Studi <span class="text-rose-500">*</span></label>
              <input type="text" v-model="formProdi.nama_prodi" placeholder="Contoh: TEKNIK INFORMATIKA" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jenjang <span class="text-rose-500">*</span></label>
                <select v-model="formProdi.jenjang" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                  <option value="S1">S1 (Sarjana)</option>
                  <option value="D4">D4 (Sarjana Terapan)</option>
                  <option value="D3">D3 (Diploma Tiga)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Daya Tampung Saat Ini</label>
                <input type="number" v-model="formProdi.daya_tampung_sekarang" min="0" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Fakultas / Departemen</label>
              <input type="text" v-model="formProdi.fakultas" placeholder="Contoh: Fakultas Ilmu Komputer" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Persyaratan Portofolio</label>
              <select v-model="formProdi.jenis_portofolio" class="w-full h-10 px-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="Tidak Ada">Tidak Ada Portofolio</option>
                <option value="Seni Rupa, Desain dan Kriya">Seni Rupa, Desain dan Kriya</option>
                <option value="Olahraga">Olahraga</option>
                <option value="Seni Tari">Seni Tari</option>
                <option value="Seni Musik">Seni Musik</option>
                <option value="Seni Pedalangan">Seni Pedalangan</option>
                <option value="Teater">Teater</option>
                <option value="Fotografi">Fotografi</option>
                <option value="Film dan Televisi">Film dan Televisi</option>
                <option value="Seni Karawitan">Seni Karawitan</option>
                <option value="Etnomusikologi">Etnomusikologi</option>
              </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
              <button @click="showModalFormProdi = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                Batal
              </button>
              <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs">
                {{ isEditProdi ? 'Simpan Perubahan' : 'Tambah Prodi' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: ANALISIS RIWAYAT SELEKSI & KEKETATAN 5 TAHUN (2021-2025)
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModalRiwayatProdi" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10 space-y-4">
          <div class="flex items-start justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-graph-up-arrow"></i>
              </div>
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md">
                  Analisis Riwayat Seleksi SNPMB (5 Tahun Terakhir)
                </span>
                <h3 class="font-black text-slate-800 text-base mt-1">{{ selectedProdiRiwayat?.nama_prodi }} ({{ selectedProdiRiwayat?.jenjang }})</h3>
                <p class="text-xs text-slate-500">{{ selectedKampusDetail?.nama_kampus }}</p>
              </div>
            </div>
            <button @click="showModalRiwayatProdi = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="loadingRiwayat" class="py-10 text-center text-slate-400 space-y-2">
            <div class="spinner-border text-indigo-600 w-8 h-8"></div>
            <div class="text-xs font-bold">Memuat riwayat pendaftar & keketatan...</div>
          </div>

          <!-- Content Riwayat -->
          <div v-else class="space-y-4">
            <div class="border border-slate-200/80 rounded-2xl overflow-hidden">
              <table class="w-full text-left text-xs text-slate-600">
                <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th class="py-2.5 px-4 text-center">Tahun Seleksi</th>
                    <th class="py-2.5 px-3 text-center">Daya Tampung</th>
                    <th class="py-2.5 px-3 text-center">Jumlah Peminat</th>
                    <th class="py-2.5 px-3 text-center">Diterima</th>
                    <th class="py-2.5 px-4 text-center">Rasio Keketatan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                  <tr v-for="r in riwayatList" :key="r.id" class="hover:bg-indigo-50/20 transition">
                    <td class="py-2.5 px-4 text-center font-black text-slate-800 font-mono">
                      {{ r.tahun }}
                    </td>
                    <td class="py-2.5 px-3 text-center font-bold text-slate-700">
                      {{ r.daya_tampung || 0 }} Mhs
                    </td>
                    <td class="py-2.5 px-3 text-center font-black text-indigo-700">
                      {{ Number(r.jumlah_pendaftar || 0).toLocaleString('id-ID') }} Org
                    </td>
                    <td class="py-2.5 px-3 text-center font-bold text-emerald-700">
                      {{ r.diterima || 0 }} Mhs
                    </td>
                    <td class="py-2.5 px-4 text-center">
                      <span class="px-2.5 py-1 rounded-full text-2xs font-black shadow-2xs"
                            :class="parseFloat(r.keketatan) < 5.0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : (parseFloat(r.keketatan) < 10.0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200')">
                        {{ r.keketatan || '-' }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="riwayatList.length === 0">
                    <td colspan="5" class="py-8 text-center text-slate-400">
                      Belum ada data riwayat seleksi yang tercatat untuk program studi ini.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="p-3 bg-blue-50/80 rounded-2xl border border-blue-100 text-blue-900 text-xs flex items-start gap-2">
              <i class="bi bi-info-circle-fill text-blue-600 mt-0.5 shrink-0"></i>
              <div>
                <strong>Petunjuk Konseling BK:</strong> Keketatan adalah persentase peluang penerimaan (Diterima / Pendaftar). Semakin kecil angka persentase keketatan, semakin tinggi tingkat persaingan program studi tersebut.
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end pt-3 border-t border-slate-100">
            <button @click="showModalRiwayatProdi = false" type="button" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: IMPOR MASTER KAMPUS & PRODI DARI CSV (ANTI-DUPLIKASI)
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showModalImportKampus" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-cloud-arrow-up-fill"></i>
              </div>
              <div>
                <h3 class="font-black text-slate-800 text-base">Impor Master Kampus & Prodi SNPMB</h3>
                <p class="text-[11px] text-slate-400">Dukungan Multi-Format File CSV & Smart Anti-Duplikasi</p>
              </div>
            </div>
            <button @click="showModalImportKampus = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form @submit.prevent="submitImportKampus" class="space-y-4 pt-1">
            <!-- Jaminan Anti-Duplikasi Banner -->
            <div class="p-3 bg-emerald-50/80 rounded-2xl border border-emerald-200/80 text-emerald-900 text-xs flex items-start gap-2.5 shadow-2xs">
              <i class="bi bi-shield-check text-emerald-600 text-base mt-0.5 shrink-0"></i>
              <div class="space-y-0.5">
                <div class="font-bold text-emerald-800">Proteksi Anti-Data Ganda Aktif</div>
                <p class="text-[11px] text-emerald-700 leading-relaxed">
                  Sistem menggunakan <em>Smart Upsert</em> berbasis Kode PTN & Kode Prodi. Mengunggah ulang berkas yang sama tidak akan menggandakan data, melainkan otomatis memperbarui rekaman yang ada.
                </p>
              </div>
            </div>

            <!-- Upload Input -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Berkas Excel / CSV SNPMB <span class="text-rose-500">*</span></label>
              <input type="file" @change="handleFileImportKampus" accept=".xlsx,.xls,.csv,.txt" class="w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1 bg-slate-50/50" />
            </div>

            <!-- Dukungan Format & Template Box -->
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/90 text-xs text-slate-600 space-y-2">
              <div class="font-bold text-slate-800 flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                  <i class="bi bi-file-earmark-excel text-emerald-600"></i> Format File Didukung:
                </span>
                <a href="/pdss/download-template-kampus" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:underline bg-white px-2.5 py-1 rounded-lg border border-emerald-200 shadow-2xs">
                  <i class="bi bi-download"></i> Unduh Template (.XLSX)
                </a>
              </div>
              <ul class="text-[11px] text-slate-500 space-y-1 list-disc list-inside">
                <li><strong>Excel Direktori Master (.XLSX):</strong> Memuat PTN, Prodi, dan Riwayat Keketatan 5 Tahun.</li>
                <li><strong>CSV Dataset Resmi PTN:</strong> Berkas <code>snpmb_snbp_ptn.csv</code></li>
                <li><strong>CSV Dataset Resmi Prodi:</strong> Berkas <code>snpmb_snbp_prodi.csv</code></li>
                <li><strong>CSV Riwayat Keketatan:</strong> Berkas <code>snpmb_snbp_historis_peminat.csv</code></li>
              </ul>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
              <button @click="showModalImportKampus = false" type="button" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                Batal
              </button>
              <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs flex items-center gap-1.5 transition">
                <i class="bi bi-cloud-arrow-up-fill"></i> Mulai Proses Impor
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>


    <!-- ═══════════════════════════════════════════════════════════
         MODAL: TRANSKRIP NILAI RAPOR 5 SEMESTER & OTOMATIS RATA-RATA
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="modalViewNilai.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-4xl w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10 space-y-4 max-h-[92vh] flex flex-col overflow-hidden">
          
          <!-- Header Modal -->
          <div class="flex items-start justify-between pb-3 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
              </div>
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">
                  Transkrip Nilai 5 Semester SNBP
                </span>
                <h3 class="font-black text-slate-800 text-base mt-0.5">
                  {{ modalViewNilai.siswa?.nama_lengkap || 'Transkrip Nilai Siswa' }}
                </h3>
                <p class="text-xs text-slate-500 font-medium">
                  NISN: <span class="font-mono font-bold text-slate-700">{{ modalViewNilai.siswa?.nisn || '-' }}</span> | 
                  Jurusan: <span class="font-bold text-slate-700">{{ modalViewNilai.siswa?.jurusan || '-' }}</span> | 
                  Kelas: <span class="font-bold text-slate-700">{{ modalViewNilai.siswa?.nama_kelas || '-' }}</span>
                </p>
              </div>
            </div>
            <button @click="modalViewNilai.show = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg p-1 rounded-lg hover:bg-slate-100 transition">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="modalViewNilai.loading" class="py-16 text-center text-slate-400 space-y-3 shrink-0">
            <div class="spinner-border text-blue-600 w-10 h-10 inline-block border-4 rounded-full animate-spin border-t-transparent"></div>
            <div class="text-xs font-bold text-slate-600">Mengambil detail nilai rapor semester 1 s.d. 5 dari database...</div>
          </div>

          <!-- Content Nilai Rapor -->
          <div v-else-if="modalViewNilai.data" class="space-y-4 overflow-y-auto pr-1 flex-1">
            
            <!-- Cards Rata-Rata Otomatis -->
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-2.5 shrink-0">
              <!-- Total Rerata Akumulatif -->
              <div class="col-span-2 sm:col-span-1 p-3 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-xs flex flex-col justify-between">
                <span class="text-[10px] font-black uppercase tracking-wider text-blue-100">Rerata 5 Smt</span>
                <div class="text-2xl font-black font-mono mt-1">
                  {{ modalViewNilai.data.rata_rata_akumulatif ?? modalViewNilai.data.rerata_akumulatif ?? '-' }}
                </div>
                <span class="text-[9px] text-blue-200 mt-1">Akumulasi Smt 1-5</span>
              </div>

              <!-- Smt 1 -->
              <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between">
                <span class="text-[10px] font-bold text-slate-500">Smt 1</span>
                <div class="text-lg font-black text-slate-800 font-mono mt-0.5">
                  {{ modalViewNilai.data.semester_averages?.sem_1 ?? modalViewNilai.data.rerata_per_semester?.[1] ?? '-' }}
                </div>
                <span class="text-[9px] text-slate-400">Kelas X (1)</span>
              </div>

              <!-- Smt 2 -->
              <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between">
                <span class="text-[10px] font-bold text-slate-500">Smt 2</span>
                <div class="text-lg font-black text-slate-800 font-mono mt-0.5">
                  {{ modalViewNilai.data.semester_averages?.sem_2 ?? modalViewNilai.data.rerata_per_semester?.[2] ?? '-' }}
                </div>
                <span class="text-[9px] text-slate-400">Kelas X (2)</span>
              </div>

              <!-- Smt 3 -->
              <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between">
                <span class="text-[10px] font-bold text-slate-500">Smt 3</span>
                <div class="text-lg font-black text-slate-800 font-mono mt-0.5">
                  {{ modalViewNilai.data.semester_averages?.sem_3 ?? modalViewNilai.data.rerata_per_semester?.[3] ?? '-' }}
                </div>
                <span class="text-[9px] text-slate-400">Kelas XI (1)</span>
              </div>

              <!-- Smt 4 -->
              <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between">
                <span class="text-[10px] font-bold text-slate-500">Smt 4</span>
                <div class="text-lg font-black text-slate-800 font-mono mt-0.5">
                  {{ modalViewNilai.data.semester_averages?.sem_4 ?? modalViewNilai.data.rerata_per_semester?.[4] ?? '-' }}
                </div>
                <span class="text-[9px] text-slate-400">Kelas XI (2)</span>
              </div>

              <!-- Smt 5 -->
              <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between">
                <span class="text-[10px] font-bold text-slate-500">Smt 5</span>
                <div class="text-lg font-black text-slate-800 font-mono mt-0.5">
                  {{ modalViewNilai.data.semester_averages?.sem_5 ?? modalViewNilai.data.rerata_per_semester?.[5] ?? '-' }}
                </div>
                <span class="text-[9px] text-slate-400">Kelas XII (1)</span>
              </div>
            </div>

            <!-- Tabel Transkrip Nilai Detail -->
            <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-2xs">
              <table class="w-full text-left text-xs text-slate-600">
                <thead class="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th class="py-2.5 px-3 w-10 text-center">No</th>
                    <th class="py-2.5 px-3 w-24">Kelompok</th>
                    <th class="py-2.5 px-4">Mata Pelajaran</th>
                    <th class="py-2.5 px-2.5 text-center bg-blue-50/30">Smt 1</th>
                    <th class="py-2.5 px-2.5 text-center bg-blue-50/30">Smt 2</th>
                    <th class="py-2.5 px-2.5 text-center bg-indigo-50/30">Smt 3</th>
                    <th class="py-2.5 px-2.5 text-center bg-indigo-50/30">Smt 4</th>
                    <th class="py-2.5 px-2.5 text-center bg-violet-50/30">Smt 5</th>
                    <th class="py-2.5 px-3 text-center bg-emerald-50/40 font-bold text-emerald-800">Rerata</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                  <tr v-for="(mapel, idx) in (modalViewNilai.data.mapels || modalViewNilai.data.transkrip || [])" :key="mapel.mapel_id || idx" class="hover:bg-slate-50/80 transition">
                    <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-2xs">{{ idx + 1 }}</td>
                    <td class="py-2.5 px-3">
                      <span class="px-2 py-0.5 rounded-md text-[10px] font-bold"
                            :class="mapel.kelompok === 'Peminatan' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700'">
                        {{ mapel.kelompok || 'Umum' }}
                      </span>
                    </td>
                    <td class="py-2.5 px-4 font-bold text-slate-800">
                      {{ mapel.nama_mapel }}
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-bold" :class="(mapel.sem_1 ?? mapel.semesters?.[1]) ? 'text-slate-800' : 'text-slate-300'">
                      {{ mapel.sem_1 ?? mapel.semesters?.[1] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-bold" :class="(mapel.sem_2 ?? mapel.semesters?.[2]) ? 'text-slate-800' : 'text-slate-300'">
                      {{ mapel.sem_2 ?? mapel.semesters?.[2] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-bold" :class="(mapel.sem_3 ?? mapel.semesters?.[3]) ? 'text-slate-800' : 'text-slate-300'">
                      {{ mapel.sem_3 ?? mapel.semesters?.[3] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-bold" :class="(mapel.sem_4 ?? mapel.semesters?.[4]) ? 'text-slate-800' : 'text-slate-300'">
                      {{ mapel.sem_4 ?? mapel.semesters?.[4] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-bold" :class="(mapel.sem_5 ?? mapel.semesters?.[5]) ? 'text-slate-800' : 'text-slate-300'">
                      {{ mapel.sem_5 ?? mapel.semesters?.[5] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono font-black text-emerald-700 bg-emerald-50/20">
                      {{ mapel.rata_rata_mapel ?? mapel.rerata ?? '-' }}
                    </td>
                  </tr>

                  <tr v-if="!modalViewNilai.data.mapels || modalViewNilai.data.mapels.length === 0">
                    <td colspan="9" class="py-8 text-center text-slate-400">
                      Belum ada nilai rapor yang tercatat untuk siswa ini di database akademik.
                    </td>
                  </tr>
                </tbody>
                <!-- Footer Rata-rata per Semester -->
                <tfoot class="bg-slate-50 border-t-2 border-slate-200 font-bold text-slate-700">
                  <tr>
                    <td colspan="3" class="py-2.5 px-4 text-right uppercase tracking-wider text-[10px] font-black">
                      Rata-Rata Semester:
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-black text-blue-700">
                      {{ modalViewNilai.data.semester_averages?.sem_1 ?? modalViewNilai.data.rerata_per_semester?.[1] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-black text-blue-700">
                      {{ modalViewNilai.data.semester_averages?.sem_2 ?? modalViewNilai.data.rerata_per_semester?.[2] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-black text-indigo-700">
                      {{ modalViewNilai.data.semester_averages?.sem_3 ?? modalViewNilai.data.rerata_per_semester?.[3] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-black text-indigo-700">
                      {{ modalViewNilai.data.semester_averages?.sem_4 ?? modalViewNilai.data.rerata_per_semester?.[4] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-2.5 text-center font-mono font-black text-violet-700">
                      {{ modalViewNilai.data.semester_averages?.sem_5 ?? modalViewNilai.data.rerata_per_semester?.[5] ?? '-' }}
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono font-black text-white bg-emerald-600">
                      {{ modalViewNilai.data.rata_rata_akumulatif ?? modalViewNilai.data.rerata_akumulatif ?? '-' }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Banner Keterangan Sistem -->
            <div class="p-3 bg-blue-50/80 rounded-2xl border border-blue-100 text-blue-900 text-xs flex items-start gap-2 shrink-0">
              <i class="bi bi-info-circle-fill text-blue-600 mt-0.5 shrink-0"></i>
              <div>
                <strong>Sistem Otomatisasi Nilai:</strong> Nilai transkrip diambil langsung dari database nilai rapor kurikulum sekolah (Semester 1 s.d. 5). Nilai rerata mata pelajaran dan rerata per semester dihitung secara matematis presisi untuk penentuan kuota eligible SNBP.
              </div>
            </div>

          </div>

          <!-- Footer Modal -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-100 shrink-0">
            <span class="text-2xs text-slate-400 font-medium">Data Terverifikasi Modul Akademik & PDSS SINTA SaaS</span>
            <button @click="modalViewNilai.show = false" type="button" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
              Tutup
            </button>
          </div>

        </div>
      </div>
    </Teleport>

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: DETAIL TABRAKAN / KONFLIK PILIHAN SNBP (SESUAI GAMBAR USER)
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="modalDetailKonflik.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 animate-scale-in relative z-10 space-y-4 max-h-[90vh] flex flex-col overflow-hidden">
          
          <!-- Header Modal -->
          <div class="flex items-start justify-between pb-3 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-exclamation-octagon-fill"></i>
              </div>
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-rose-700 bg-rose-100 border border-rose-200 px-2.5 py-0.5 rounded-md">
                  Pilihan {{ modalDetailKonflik.noPilihan }} Bentrok ({{ modalDetailKonflik.totalBentrok }} Siswa)
                </span>
                <h3 class="font-black text-slate-800 text-base mt-1">
                  {{ modalDetailKonflik.namaProdi }} ({{ modalDetailKonflik.jenjang }})
                </h3>
                <p class="text-xs text-slate-500 font-semibold">
                  {{ modalDetailKonflik.namaKampus }} | Kuota PTN: <span class="text-slate-800 font-bold">{{ modalDetailKonflik.dayaTampung }} Mhs</span>
                </p>
              </div>
            </div>
            <button @click="modalDetailKonflik.show = false" type="button" class="text-slate-400 hover:text-slate-600 text-lg p-1 rounded-lg hover:bg-slate-100 transition">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <!-- Alert Penjelasan Tabrakan -->
          <div class="p-3.5 bg-rose-50/90 rounded-2xl border border-rose-200 text-rose-900 text-xs flex items-start gap-2.5 shrink-0">
            <i class="bi bi-info-circle-fill text-rose-600 text-base mt-0.5 shrink-0"></i>
            <div class="space-y-1">
              <div class="font-bold text-rose-900">Peringatan Seleksi SNBP Antar-Siswa Sekolah yang Sama:</div>
              <p class="text-rose-800 text-[11px] leading-relaxed">
                Terdapat <strong>{{ modalDetailKonflik.totalBentrok }} siswa</strong> dari sekolah ini yang mendaftar ke jurusan yang sama pada Pilihan {{ modalDetailKonflik.noPilihan }}. Mayoritas PTN hanya meloloskan 1 siswa per prodi dari 1 sekolah yang sama berdasarkan urutan <strong>Peringkat Sekolah</strong>.
              </p>
            </div>
          </div>

          <!-- Daftar Siswa yang Bentrok -->
          <div class="space-y-2.5 overflow-y-auto pr-1 flex-1">
            <div v-for="(st, idx) in modalDetailKonflik.students" :key="st.siswa_id || idx" 
                 class="p-4 rounded-2xl border transition flex items-center justify-between gap-3 shadow-2xs"
                 :class="idx === 0 ? 'bg-emerald-50/70 border-emerald-300 ring-1 ring-emerald-400/30' : 'bg-slate-50/80 border-slate-200/90'">
              
              <div class="flex items-center gap-3.5 min-w-0">
                <span class="w-9 h-9 rounded-xl font-black text-xs inline-flex items-center justify-center shrink-0 shadow-2xs"
                      :class="idx === 0 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700'">
                  #{{ st.ranking_sekolah || (idx + 1) }}
                </span>
                <div class="min-w-0 space-y-1">
                  <div class="font-bold text-slate-800 text-xs truncate flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-black text-slate-900">{{ idx + 1 }}. {{ st.nama_lengkap }}</span>
                    <span v-if="idx === 0" class="px-2.5 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                      <i class="bi bi-star-fill text-amber-500"></i> Prioritas Tertinggi (Lolos Sekolah)
                    </span>
                    <span v-else class="px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                      <i class="bi bi-exclamation-circle-fill"></i> Potensi Tergeser (Rasionalisasi BK)
                    </span>
                  </div>
                  <div class="text-[11px] text-slate-500 flex items-center gap-2 flex-wrap">
                    <span>Kelas: <strong class="text-slate-800">{{ st.kelas || st.jurusan || 'Kelas XII' }}</strong></span>
                    <span class="text-slate-300">•</span>
                    <span>Peringkat Sekolah: <strong class="text-blue-700">Rank {{ st.ranking_sekolah || (idx + 1) }}</strong></span>
                    <span class="text-slate-300">•</span>
                    <span>NISN: <span class="font-mono font-medium text-slate-700">{{ st.nisn || '-' }}</span></span>
                    <span class="text-slate-300">•</span>
                    <span>Rerata 5 Smt: <strong class="font-mono text-emerald-700">{{ st.nilai_rata_rata || st.rata_rata_nilai || '-' }}</strong></span>
                  </div>
                </div>
              </div>

              <div class="shrink-0 flex items-center gap-2">
                <button v-if="!simulasiStats?.[activeSimulasi]?.is_permanen" 
                        @click="modalDetailKonflik.show = false; openModalPilihan(st, modalDetailKonflik.noPilihan)" 
                        type="button" 
                        class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-3.5 py-2 text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                  <i class="bi bi-arrow-left-right"></i> Ganti Pilihan
                </button>
              </div>
            </div>
          </div>

          <!-- Rekomendasi BK Insight Box -->
          <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/90 text-xs text-slate-600 shrink-0">
            <div class="font-bold text-slate-800 flex items-center gap-1.5 mb-1">
              <i class="bi bi-lightbulb-fill text-amber-500"></i> Rekomendasi Rasionalisasi Guru BK:
            </div>
            <p class="text-[11px] text-slate-500 leading-relaxed">
              Disarankan untuk siswa dengan peringkat di bawah peringkat 1 pemilih prodi ini untuk mengalihkan ke program studi alternatif atau PTN lain pada <strong>Simulasi 2 (Rasionalisasi BK)</strong> guna memaksimalkan peluang lolos seleksi SNBP.
            </p>
          </div>

          <!-- Footer Modal -->
          <div class="flex items-center justify-end pt-3 border-t border-slate-100 shrink-0">
            <button @click="modalDetailKonflik.show = false" type="button" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
              Tutup
            </button>
          </div>

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
