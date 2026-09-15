<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  sirkulasiAktif: Object,
  sirkulasiRiwayat: Object,
  paketList: Array,
  dendaList: Object,
  opnameList: Array,
  bacaList: Object,
  reservasiList: Array,
  lokerList: Array,
  surveyList: Array,
  bukuTersedia: Array,
  eksemplarTersedia: Array,
  anggotaSelector: Array,
  stats: Object,
  pengaturan: Object,
  tenants: Array,
  isSuperAdmin: Boolean,
  activeTenantId: String,
  filters: Object,
})

// === ZERO-SSR: State lokal reaktif ===
const localSirkulasiAktif = ref(props.sirkulasiAktif || { data: [] })
const localSirkulasiRiwayat = ref(props.sirkulasiRiwayat || { data: [] })
const localDendaList = ref(props.dendaList || { data: [] })
const localBacaList = ref(props.bacaList || { data: [] })
const isLoadingFilter = ref(false)

// Tenant in-memory (BUKAN dari URL)
const activeTenantId = ref(props.activeTenantId || '')

const applySearch = async (page = 1) => {
  isLoadingFilter.value = true
  try {
    const headers = {}
    if (activeTenantId.value) headers['X-Tenant-Id'] = activeTenantId.value

    const res = await axios.get('/perpustakaan/sirkulasi', {
      params: {
        async: 1,
        search: searchQuery.value || undefined,
        per_page_aktif: perPageAktif.value || undefined,
        p_aktif: page > 1 ? page : undefined,
        per_page_riwayat: perPageRiwayat.value || undefined,
        per_page_denda: perPageDenda.value || undefined,
        per_page_baca: perPageBaca.value || undefined,
      },
      headers,
    })
    if (res.data?.success) {
      const d = res.data.data
      if (d.sirkulasiAktif)   localSirkulasiAktif.value   = d.sirkulasiAktif
      if (d.sirkulasiRiwayat) localSirkulasiRiwayat.value = d.sirkulasiRiwayat
      if (d.dendaList)        localDendaList.value        = d.dendaList
      if (d.bacaList)         localBacaList.value         = d.bacaList
    }
  } catch (err) {
    console.error('Gagal filter sirkulasi:', err)
  } finally {
    isLoadingFilter.value = false
  }
}

const applyTenantFilter = () => { applySearch(1) }
const applyRiwayatPage  = (page = 1) => applySearch(page)
const applyDendaPage    = (page = 1) => applySearch(page)
const applyBacaPage     = (page = 1) => applySearch(page)

const goToPage = async (url) => {
  if (!url) return
  try {
    const urlObj = new URL(url, window.location.origin)
    const page   = urlObj.searchParams.get('page') || 1
    await applySearch(parseInt(page))
  } catch (err) {
    console.error('Gagal navigasi halaman:', err)
  }
}


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

// -------------------------------------------------------------
// MODAL PEMINJAMAN BUKU BARU & LIVE AUTOCOMPLETE
// -------------------------------------------------------------
const isModalPinjamOpen = ref(false)
const memberSearchKeyword = ref('')
const isMemberDropdownOpen = ref(false)
const selectedMember = ref(null)
const isManualPeminjamMode = ref(false)

const bukuSearchKeyword = ref('')
const isBukuDropdownOpen = ref(false)
const selectedBuku = ref(null)
const selectedEksemplar = ref(null)

const formPinjam = useForm({
  buku_id: '',
  eksemplar_id: '',
  barcode: '',
  peminjam_type: 'Siswa',
  peminjam_id: '',
  nama_peminjam: '',
  nomor_identitas: '',
  kelas_unit: '',
  tanggal_pinjam: new Date().toISOString().split('T')[0],
  durasi_hari: 7,
  catatan: '',
})

// Filter Live Anggota
const filteredMembers = computed(() => {
  const list = props.anggotaSelector || []
  if (!memberSearchKeyword.value || memberSearchKeyword.value.trim() === '') {
    return list.slice(0, 15)
  }
  const q = memberSearchKeyword.value.toLowerCase().trim()
  return list.filter(m =>
    (m.nama_lengkap && m.nama_lengkap.toLowerCase().includes(q)) ||
    (m.identitas_no && m.identitas_no.toLowerCase().includes(q)) ||
    (m.no_anggota && m.no_anggota.toLowerCase().includes(q)) ||
    (m.kelas_jurusan && m.kelas_jurusan.toLowerCase().includes(q))
  ).slice(0, 20)
})

const selectMember = (m) => {
  selectedMember.value = m
  formPinjam.peminjam_id = m.id
  formPinjam.nama_peminjam = m.nama_lengkap
  formPinjam.nomor_identitas = m.identitas_no || ''
  formPinjam.peminjam_type = m.tipe_anggota || 'Siswa'
  formPinjam.kelas_unit = m.kelas_jurusan || '-'
  memberSearchKeyword.value = m.nama_lengkap
  isMemberDropdownOpen.value = false
  isManualPeminjamMode.value = false
}

const clearMemberSelection = () => {
  selectedMember.value = null
  memberSearchKeyword.value = ''
  formPinjam.peminjam_id = ''
  formPinjam.nama_peminjam = ''
  formPinjam.nomor_identitas = ''
  formPinjam.peminjam_type = 'Siswa'
  formPinjam.kelas_unit = ''
  isManualPeminjamMode.value = false
}

const toggleManualPeminjamMode = () => {
  isManualPeminjamMode.value = !isManualPeminjamMode.value
  selectedMember.value = null
  if (isManualPeminjamMode.value) {
    formPinjam.peminjam_id = 'MANUAL-' + Date.now()
    formPinjam.peminjam_type = 'Umum'
    formPinjam.kelas_unit = 'Umum / Tamu'
  } else {
    formPinjam.peminjam_id = ''
    formPinjam.peminjam_type = 'Siswa'
    formPinjam.kelas_unit = ''
  }
}

// Filter Live Buku & Eksemplar
const filteredBukuAndEksemplar = computed(() => {
  const q = bukuSearchKeyword.value ? bukuSearchKeyword.value.toLowerCase().trim() : ''
  const eksemplarList = props.eksemplarTersedia || []
  const bukuList = props.bukuTersedia || []

  if (!q) {
    return eksemplarList.length > 0 ? eksemplarList.slice(0, 15) : bukuList.slice(0, 15)
  }

  // Cari di eksemplar fisik terlebih dahulu
  const matchedEksemplar = eksemplarList.filter(e =>
    (e.barcode && e.barcode.toLowerCase().includes(q)) ||
    (e.buku?.judul_buku && e.buku.judul_buku.toLowerCase().includes(q)) ||
    (e.buku?.pengarang && e.buku.pengarang.toLowerCase().includes(q)) ||
    (e.buku?.nomor_panggil && e.buku.nomor_panggil.toLowerCase().includes(q))
  )

  if (matchedEksemplar.length > 0) {
    return matchedEksemplar.slice(0, 20)
  }

  // Fallback cari di judul buku
  return bukuList.filter(b =>
    (b.judul_buku && b.judul_buku.toLowerCase().includes(q)) ||
    (b.pengarang && b.pengarang.toLowerCase().includes(q)) ||
    (b.kode_buku && b.kode_buku.toLowerCase().includes(q)) ||
    (b.nomor_panggil && b.nomor_panggil.toLowerCase().includes(q))
  ).slice(0, 20)
})

const selectBukuOrEksemplar = (item) => {
  if (item.bibliografi_id || item.barcode) {
    // Ini item eksemplar
    selectedEksemplar.value = item
    selectedBuku.value = item.buku || null
    formPinjam.buku_id = item.bibliografi_id
    formPinjam.eksemplar_id = item.id
    formPinjam.barcode = item.barcode
    bukuSearchKeyword.value = `${item.buku?.judul_buku || 'Buku'} (${item.barcode})`
  } else {
    // Ini item buku umum
    selectedBuku.value = item
    selectedEksemplar.value = null
    formPinjam.buku_id = item.id
    formPinjam.eksemplar_id = ''
    formPinjam.barcode = ''
    bukuSearchKeyword.value = item.judul_buku
  }
  isBukuDropdownOpen.value = false
}

const clearBukuSelection = () => {
  selectedBuku.value = null
  selectedEksemplar.value = null
  bukuSearchKeyword.value = ''
  formPinjam.buku_id = ''
  formPinjam.eksemplar_id = ''
  formPinjam.barcode = ''
}

const calculateDueDate = (startDate, days) => {
  if (!startDate) return '-'
  const d = new Date(startDate)
  d.setDate(d.getDate() + Number(days || 7))
  return d.toISOString().split('T')[0]
}

const getOverdueInfo = (tglDeadline, tarif = 1000) => {
  if (!tglDeadline) return { isOverdue: false, days: 0, estDenda: 0 }
  const today = new Date().toISOString().split('T')[0]
  if (tglDeadline < today) {
    const d1 = new Date(tglDeadline)
    const d2 = new Date(today)
    const diffTime = Math.abs(d2 - d1)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return {
      isOverdue: true,
      days: diffDays,
      estDenda: diffDays * Number(tarif || 1000),
    }
  }
  return { isOverdue: false, days: 0, estDenda: 0 }
}

const openModalPinjam = () => {
  clearMemberSelection()
  clearBukuSelection()
  formPinjam.tanggal_pinjam = new Date().toISOString().split('T')[0]
  formPinjam.durasi_hari = 7
  formPinjam.catatan = ''
  isModalPinjamOpen.value = true
}

const submitPinjam = () => {
  formPinjam.post('/perpustakaan/sirkulasi/pinjam', {
    onSuccess: () => {
      isModalPinjamOpen.value = false
      clearMemberSelection()
      clearBukuSelection()
      formPinjam.reset()
      formPinjam.tanggal_pinjam = new Date().toISOString().split('T')[0]
      formPinjam.durasi_hari = 7
    }
  })
}

// -------------------------------------------------------------
// PENGEMBALIAN & DENDA KERUSAKAN / KEHILANGAN
// -------------------------------------------------------------
const isModalKembaliOpen = ref(false)
const selectedSirkulasi = ref(null)
const formKembali = useForm({
  kondisi_kembali: 'Baik',
  denda_kerusakan: 0,
  denda_kehilangan: 0,
})

const openModalKembali = (sirkulasi) => {
  selectedSirkulasi.value = sirkulasi
  formKembali.kondisi_kembali = 'Baik'
  formKembali.denda_kerusakan = 0
  formKembali.denda_kehilangan = 0
  isModalKembaliOpen.value = true
}

const submitKembali = () => {
  if (!selectedSirkulasi.value) return
  formKembali.post(`/perpustakaan/sirkulasi/kembali/${selectedSirkulasi.value.id}`, {
    onSuccess: () => {
      isModalKembaliOpen.value = false
      selectedSirkulasi.value = null
    }
  })
}

// -------------------------------------------------------------
// QUICK RETURN (PENGEMBALIAN KILAT VIA SCAN BARCODE)
// -------------------------------------------------------------
const quickReturnBarcode = ref('')
const quickReturnLoading = ref(false)
const quickReturnResult = ref(null)
const quickReturnSessionLogs = ref([])

const submitQuickReturn = () => {
  if (!quickReturnBarcode.value) return
  const scanned = quickReturnBarcode.value.trim()
  quickReturnLoading.value = true
  router.post('/perpustakaan/sirkulasi/quick-return', {
    barcode: scanned
  }, {
    preserveScroll: true,
    onSuccess: () => {
      const msg = `Eksemplar ${scanned} berhasil dikembalikan dan stok direstorasi.`
      quickReturnResult.value = {
        success: true,
        message: msg,
        time: new Date().toLocaleTimeString('id-ID')
      }
      quickReturnSessionLogs.value.unshift({
        barcode: scanned,
        status: 'Sukses (Kembali)',
        time: new Date().toLocaleTimeString('id-ID'),
        success: true
      })
      quickReturnBarcode.value = ''
      quickReturnLoading.value = false
    },
    onError: (errors) => {
      const errMsg = Object.values(errors).join(', ')
      quickReturnResult.value = {
        success: false,
        message: errMsg,
        time: new Date().toLocaleTimeString('id-ID')
      }
      quickReturnSessionLogs.value.unshift({
        barcode: scanned,
        status: 'Gagal: ' + errMsg,
        time: new Date().toLocaleTimeString('id-ID'),
        success: false
      })
      quickReturnLoading.value = false
    }
  })
}

// -------------------------------------------------------------
// PERPANJANG & BAYAR DENDA
// -------------------------------------------------------------
const perpanjangPinjam = (id) => {
  if (confirm('Perpanjang masa pinjam buku ini selama 7 hari?')) {
    router.post(`/perpustakaan/sirkulasi/perpanjang/${id}`, { hari: 7 }, { preserveScroll: true })
  }
}

const isModalBayarDendaOpen = ref(false)
const selectedDendaSirkulasi = ref(null)
const formBayarDenda = useForm({
  metode_pembayaran: 'Tunai',
})

const openModalBayarDenda = (item) => {
  selectedDendaSirkulasi.value = item
  formBayarDenda.metode_pembayaran = 'Tunai'
  isModalBayarDendaOpen.value = true
}

const submitBayarDenda = () => {
  if (!selectedDendaSirkulasi.value) return
  formBayarDenda.post(`/perpustakaan/sirkulasi/bayar-denda/${selectedDendaSirkulasi.value.id}`, {
    onSuccess: () => {
      isModalBayarDendaOpen.value = false
      selectedDendaSirkulasi.value = null
    }
  })
}

// -------------------------------------------------------------
// DISTRIBUSI BUKU PAKET
// -------------------------------------------------------------
const isModalPaketOpen = ref(false)
const formPaket = useForm({
  kelas_id: '',
  tahun_ajaran: '2026/2027',
  mata_pelajaran: '',
  buku_id: '',
  jumlah_distribusi: 30,
})

const submitPaket = () => {
  formPaket.post('/perpustakaan/sirkulasi/distribusi-paket', {
    onSuccess: () => {
      isModalPaketOpen.value = false
      formPaket.reset()
    }
  })
}

// -------------------------------------------------------------
// STOCK OPNAME SESI & SCAN RUNNER
// -------------------------------------------------------------
const isModalOpnameOpen = ref(false)
const formOpname = useForm({
  nama_sesi: 'Sensus Koleksi Semester Genap ' + new Date().getFullYear(),
  tanggal_mulai: new Date().toISOString().split('T')[0],
  keterangan: 'Audit inventarisasi fisik buku rak 000-900',
})

const submitOpname = () => {
  formOpname.post('/perpustakaan/opname', {
    onSuccess: () => {
      isModalOpnameOpen.value = false
      formOpname.reset()
    }
  })
}

const activeOpnameSession = ref(null)
const opnameScanBarcode = ref('')
const opnameScanLogs = ref([])

const selectOpnameSession = (opname) => {
  activeOpnameSession.value = opname
  opnameScanLogs.value = opname.items || []
}

const submitScanOpname = () => {
  if (!opnameScanBarcode.value || !activeOpnameSession.value) return
  router.post('/perpustakaan/opname/scan', {
    opname_id: activeOpnameSession.value.id,
    barcode: opnameScanBarcode.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      opnameScanBarcode.value = ''
    }
  })
}

const closeOpnameSession = (id) => {
  if (confirm('Tutup dan kunci sesi Stock Opname ini secara permanen?')) {
    router.post(`/perpustakaan/opname/${id}/close`, {}, { preserveScroll: true })
  }
}

// -------------------------------------------------------------
// BACA DI TEMPAT
// -------------------------------------------------------------
const isModalBacaOpen = ref(false)
const formBaca = useForm({
  barcode: '',
  nama_pembaca: 'Siswa Ruang Baca',
  tipe_pembaca: 'Siswa',
  ruang_baca: 'Ruang Baca Utama',
})

const submitBaca = () => {
  formBaca.post('/perpustakaan/baca-di-tempat', {
    onSuccess: () => {
      isModalBacaOpen.value = false
      formBaca.reset()
      formBaca.nama_pembaca = 'Siswa Ruang Baca'
      formBaca.tipe_pembaca = 'Siswa'
      formBaca.ruang_baca = 'Ruang Baca Utama'
    }
  })
}

const cancelBooking = (id) => {
  if (confirm('Batalkan reservasi buku ini?')) {
    router.post(`/perpustakaan/reservasi/${id}/cancel`, {}, { preserveScroll: true })
  }
}

// -------------------------------------------------------------
// LOKER PENITIPAN BARANG
// -------------------------------------------------------------
const isModalTambahLokerOpen = ref(false)
const formTambahLoker = useForm({
  nomor_loker: '',
  lokasi_ruangan: 'Lobi Utama Perpustakaan',
  keterangan: '',
})

const submitTambahLoker = () => {
  formTambahLoker.post('/perpustakaan/loker', {
    onSuccess: () => {
      isModalTambahLokerOpen.value = false
      formTambahLoker.reset()
    }
  })
}

const isModalPinjamLokerOpen = ref(false)
const selectedLoker = ref(null)
const formPinjamLoker = useForm({
  loker_id: '',
  nama_peminjam: '',
  identitas_jaminan: 'KTA',
  catatan: '',
})

const openPinjamLoker = (loker) => {
  selectedLoker.value = loker
  formPinjamLoker.loker_id = loker.id
  formPinjamLoker.nama_peminjam = ''
  formPinjamLoker.identitas_jaminan = 'KTA'
  formPinjamLoker.catatan = ''
  isModalPinjamLokerOpen.value = true
}

const submitPinjamLoker = () => {
  formPinjamLoker.post('/perpustakaan/loker/pinjam', {
    onSuccess: () => {
      isModalPinjamLokerOpen.value = false
      formPinjamLoker.reset()
    }
  })
}

const isModalKembaliLokerOpen = ref(false)
const formKembaliLoker = useForm({
  denda: 0,
  kunci_hilang: false,
  catatan: '',
})

const openKembaliLoker = (loker) => {
  selectedLoker.value = loker
  formKembaliLoker.denda = 0
  formKembaliLoker.kunci_hilang = false
  formKembaliLoker.catatan = ''
  isModalKembaliLokerOpen.value = true
}

const submitKembaliLoker = () => {
  if (!selectedLoker.value) return
  formKembaliLoker.post(`/perpustakaan/loker/kembali/${selectedLoker.value.id}`, {
    onSuccess: () => {
      isModalKembaliLokerOpen.value = false
      formKembaliLoker.reset()
    }
  })
}

const deleteLoker = (id) => {
  if (confirm('Hapus unit loker ini?')) {
    router.delete(`/perpustakaan/loker/${id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <AppLayout title="Sirkulasi & Layanan Perpustakaan">
    <div class="space-y-6">
      <!-- Header & Action Buttons -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Meja Sirkulasi & Layanan Pemustaka</h1>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-blue-100 text-blue-700">Otomasi Perpusnas</span>
          </div>
          <p class="text-xs text-slate-500 mt-1">Peminjaman multi-buku, pengembalian kilat (Quick Return), denda keterlambatan/kerusakan, Stock Opname, dan pencatatan baca di tempat.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <Link href="/perpustakaan/kiosk" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-display"></i>
            <span>Anjungan Kiosk (Tablet)</span>
          </Link>
          <button @click="activeTab = 'quick_return'" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-upc-scan"></i>
            <span>Quick Return (Scan Kilat)</span>
          </button>
          <button @click="openModalPinjam" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs shadow-blue-500/20">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Transaksi Pinjam Baru</span>
          </button>
        </div>
      </div>

      <!-- Section 2: Banner Filter Sekolah (Khusus Super Admin) -->
      <div v-if="isSuperAdmin" class="rounded-2xl shadow-xs border border-blue-100 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 p-4 transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs shrink-0">
              <i class="bi bi-building text-lg"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-sm">Filter Sekolah</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 border border-blue-200">
                  <i class="bi bi-funnel-fill me-1"></i> Aktif
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Menampilkan sirkulasi perpustakaan milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600 whitespace-nowrap hidden sm:inline">Pilih Sekolah:</label>
            <select v-model="selectedTenantId" @change="applyTenantFilter" class="text-xs rounded-xl border border-slate-200 bg-white py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium text-slate-700 min-w-[240px] shadow-2xs">
              <option value="">-- Semua Sekolah (Agregat Global) --</option>
              <option v-for="t in tenants" :key="t.id" :value="t.id">
                {{ t.nama_sekolah }} ({{ t.npsn }})
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Stats Bar 5 Kolom -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Pinjaman Aktif</span>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm"><i class="bi bi-journal-arrow-up"></i></span>
          </div>
          <div class="text-xl font-black text-blue-600 mt-2">{{ stats.total_pinjam_aktif || 0 }}</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Buku Sedang Dipinjam</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Terlambat</span>
            <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm"><i class="bi bi-clock-history"></i></span>
          </div>
          <div class="text-xl font-black text-rose-600 mt-2">{{ stats.total_terlambat || 0 }}</div>
          <div class="text-[11px] text-rose-600/80 mt-0.5 font-medium">Lewat Jatuh Tempo</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Tunggakan Denda</span>
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm"><i class="bi bi-cash-stack"></i></span>
          </div>
          <div class="text-lg font-black text-amber-600 mt-2">Rp {{ Number(stats.total_denda_tunggakan || 0).toLocaleString('id-ID') }}</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Belum Dibayarkan</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Baca di Tempat</span>
            <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm"><i class="bi bi-book-half"></i></span>
          </div>
          <div class="text-xl font-black text-purple-600 mt-2">{{ stats.total_baca_hari_ini || 0 }}</div>
          <div class="text-[11px] text-purple-600/80 mt-0.5 font-medium">Buku Dibaca Hari Ini</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs col-span-2 sm:col-span-1">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Antrean Booking</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm"><i class="bi bi-calendar-check"></i></span>
          </div>
          <div class="text-xl font-black text-emerald-600 mt-2">{{ stats.total_reservasi_antre || 0 }}</div>
          <div class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">Reservasi Online Menunggu</div>
        </div>
      </div>

      <!-- Modern Horizontal NavTabs Scroller (Pill Layout) -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-sirkulasi')?.scrollBy({ left: -220, behavior: 'smooth' })"
                  title="Geser ke Kiri">
            <i class="bi bi-chevron-left"></i>
          </button>

          <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navtabs-sirkulasi" role="tablist">
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'aktif' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'aktif'">
                  <i class="bi bi-arrow-repeat me-2 text-sm"></i> 1. Peminjaman Aktif
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'quick_return' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'quick_return'">
                  <i class="bi bi-upc-scan me-2 text-sm"></i> 2. Quick Return (Scan Kilat)
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'riwayat' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'riwayat'">
                  <i class="bi bi-check2-all me-2 text-sm"></i> 3. Riwayat Selesai
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'denda' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'denda'">
                  <i class="bi bi-cash-coin me-2 text-sm"></i> 4. Kas Denda & Ganti Rugi
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'opname' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'opname'">
                  <i class="bi bi-clipboard2-check-fill me-2 text-sm"></i> 5. Stock Opname (Sensus)
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'baca' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'baca'">
                  <i class="bi bi-book-half me-2 text-sm"></i> 6. Baca di Tempat
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'reservasi' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'reservasi'">
                  <i class="bi bi-calendar2-range me-2 text-sm"></i> 7. Booking / Reservasi
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'loker' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'loker'">
                  <i class="bi bi-grid-3x3-gap-fill me-2 text-sm"></i> 8. Loker Barang
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'survey' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'survey'">
                  <i class="bi bi-star-fill me-2 text-sm text-amber-400"></i> 9. Survey Kepuasan IKM
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'paket' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'paket'">
                  <i class="bi bi-collection-fill me-2 text-sm"></i> 10. Paket Pelajaran
                </button>
              </li>
            </ul>
          </div>

          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-sirkulasi')?.scrollBy({ left: 220, behavior: 'smooth' })"
                  title="Geser ke Kanan">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- TAB 1: PEMINJAMAN AKTIF -->
      <div v-if="activeTab === 'aktif'" class="space-y-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3">
          <div class="relative w-full md:w-80">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="bi bi-search"></i></span>
            <input v-model="searchQuery" @keyup.enter="applySearch(1)" type="text" placeholder="Cari nama peminjam, nomor transaksi, judul..." class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
          </div>
          <button @click="openModalPinjam" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs shrink-0">
            <i class="bi bi-plus-circle-fill"></i>
            <span>+ Catat Pinjam Baru</span>
          </button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">No. Transaksi & Tanggal</th>
                  <th class="py-3.5 px-3">Peminjam</th>
                  <th class="py-3.5 px-3">Buku & Eksemplar</th>
                  <th class="py-3.5 px-3">Jatuh Tempo</th>
                  <th class="py-3.5 px-3 text-center">Status & Estimasi Denda</th>
                  <th class="py-3.5 px-4 text-center">Aksi Sirkulasi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in (sirkulasiAktif?.data || [])" :key="item.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4">
                    <div class="font-mono font-bold text-slate-800">{{ item.nomor_transaksi }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Pinjam: {{ item.tanggal_pinjam }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-extrabold text-slate-800">{{ item.nama_peminjam }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">{{ item.peminjam_type }} • {{ item.kelas_unit }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-bold text-blue-700">{{ item.buku?.judul_buku || '-' }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">Barcode: {{ item.eksemplar?.barcode || '-' }}</div>
                  </td>
                  <td class="py-3 px-3 font-semibold text-slate-700">
                    <div class="font-bold">{{ item.tanggal_harus_kembali }}</div>
                    <div v-if="item.jumlah_perpanjangan > 0" class="text-[10px] text-indigo-600 font-bold">Perpanjang: {{ item.jumlah_perpanjangan }}x</div>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <div v-if="getOverdueInfo(item.tanggal_harus_kembali).isOverdue" class="inline-flex flex-col items-center gap-0.5">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1">
                        <i class="bi bi-clock-history"></i> Terlambat {{ getOverdueInfo(item.tanggal_harus_kembali).days }} Hari
                      </span>
                      <span class="text-[10px] font-black text-amber-700">
                        Est: Rp {{ getOverdueInfo(item.tanggal_harus_kembali, item.tarif_denda_harian || 1000).estDenda.toLocaleString('id-ID') }}
                      </span>
                    </div>
                    <div v-else class="inline-flex items-center">
                      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1">
                        <i class="bi bi-check2-circle"></i> Dipinjam (Aktif)
                      </span>
                    </div>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button @click="openModalKembali(item)" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold transition flex items-center gap-1 shadow-2xs" title="Proses Pengembalian">
                        <i class="bi bi-box-arrow-in-left"></i> Kembali
                      </button>
                      <button @click="perpanjangPinjam(item.id)" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Perpanjang 7 Hari">
                        <i class="bi bi-arrow-clockwise"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!sirkulasiAktif?.data?.length">
                  <td colspan="6" class="py-8 text-center text-slate-400">Tidak ada peminjaman aktif saat ini.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Pagination Footer Tab Aktif -->
          <div v-if="sirkulasiAktif?.total" class="px-4 py-3 bg-slate-50/50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span>Tampilkan</span>
              <select v-model="perPageAktif" @change="applySearch(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span>baris per halaman</span>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <span class="whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-800">{{ sirkulasiAktif.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ sirkulasiAktif.to || sirkulasiAktif.total }}</span> dari <span class="font-bold text-slate-800">{{ sirkulasiAktif.total }}</span> transaksi
              </span>
            </div>

            <!-- Smart Windowing Pagination Links -->
            <div v-if="sirkulasiAktif.links && sirkulasiAktif.links.length > 3" class="flex items-center gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(sirkulasiAktif)" :key="i">
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

      <!-- TAB 2: QUICK RETURN (SCAN KILAT) -->
      <div v-if="activeTab === 'quick_return'" class="space-y-4">
        <div class="bg-gradient-to-r from-slate-900 to-blue-950 rounded-3xl p-8 text-white shadow-xl max-w-2xl mx-auto text-center space-y-4">
          <div class="w-16 h-16 rounded-2xl bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-3xl text-blue-300 mx-auto">
            <i class="bi bi-upc-scan"></i>
          </div>
          <h2 class="text-2xl font-black tracking-tight">Pengembalian Cepat (Quick Return Station)</h2>
          <p class="text-xs text-slate-300 max-w-md mx-auto">Arahkan barcode scanner ke stiker buku atau ketikkan nomor barcode untuk memproses pengembalian seketika tanpa perlu memilih peminjam.</p>

          <form @submit.prevent="submitQuickReturn" class="pt-2 flex items-center gap-2 max-w-md mx-auto">
            <input v-model="quickReturnBarcode" type="text" autofocus required placeholder="Pindai / Masukkan Barcode Buku..." class="grow px-4 py-3 rounded-2xl bg-white/10 border border-white/20 text-white placeholder-slate-400 text-sm font-mono font-bold focus:ring-2 focus:ring-blue-400 focus:outline-none" />
            <button type="submit" :disabled="quickReturnLoading" class="px-5 py-3 rounded-2xl bg-blue-500 hover:bg-blue-600 text-white font-extrabold text-xs shadow transition shrink-0">
              {{ quickReturnLoading ? 'Proses...' : 'Proses Scan' }}
            </button>
          </form>

          <!-- Result Alert -->
          <div v-if="quickReturnResult" class="p-3.5 rounded-2xl text-xs font-bold mt-4" :class="quickReturnResult.success ? 'bg-emerald-500/20 text-emerald-200 border border-emerald-400/30' : 'bg-rose-500/20 text-rose-200 border border-rose-400/30'">
            <div class="flex items-center justify-center gap-2">
              <i class="bi" :class="quickReturnResult.success ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'"></i>
              <span>{{ quickReturnResult.message }} ({{ quickReturnResult.time }})</span>
            </div>
          </div>
        </div>

        <!-- Session Scan History Table -->
        <div v-if="quickReturnSessionLogs.length > 0" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-5 space-y-3">
          <div class="flex items-center justify-between">
            <h4 class="font-bold text-slate-800 text-xs flex items-center gap-2">
              <i class="bi bi-clock-history text-blue-600"></i>
              <span>Log Pemindaian Sesi Berjalan ({{ quickReturnSessionLogs.length }} Scan)</span>
            </h4>
            <button @click="quickReturnSessionLogs = []" class="text-xs text-slate-400 hover:text-rose-600 transition">
              Bersihkan Log
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-2.5 px-3">Waktu</th>
                  <th class="py-2.5 px-3">Barcode Buku</th>
                  <th class="py-2.5 px-3">Status Pengembalian</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="(log, idx) in quickReturnSessionLogs" :key="idx" class="hover:bg-slate-50">
                  <td class="py-2 px-3 text-slate-400 font-mono">{{ log.time }}</td>
                  <td class="py-2 px-3 font-mono font-bold text-slate-800">{{ log.barcode }}</td>
                  <td class="py-2 px-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="log.success ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                      {{ log.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 3: RIWAYAT SIRKULASI SELESAI -->
      <div v-if="activeTab === 'riwayat'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">No. Transaksi</th>
                  <th class="py-3.5 px-3">Peminjam</th>
                  <th class="py-3.5 px-3">Buku & Barcode</th>
                  <th class="py-3.5 px-3">Tgl Kembali Riil</th>
                  <th class="py-3.5 px-3">Kondisi Fisik</th>
                  <th class="py-3.5 px-3 text-center">Petugas Kembali</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in (sirkulasiRiwayat?.data || [])" :key="item.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ item.nomor_transaksi }}</td>
                  <td class="py-3 px-3">
                    <div class="font-bold text-slate-800">{{ item.nama_peminjam }}</div>
                    <div class="text-[10px] text-slate-400">{{ item.peminjam_type }} • {{ item.kelas_unit }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-semibold text-slate-800">{{ item.buku?.judul_buku || '-' }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">{{ item.eksemplar?.barcode || '-' }}</div>
                  </td>
                  <td class="py-3 px-3 text-slate-700 font-medium">{{ item.tanggal_kembali_aktual || '-' }}</td>
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="item.kondisi_kembali === 'Baik' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                      {{ item.kondisi_kembali || 'Baik' }}
                    </span>
                  </td>
                  <td class="py-3 px-3 text-center text-slate-600">{{ item.petugas_pengembalian || '-' }}</td>
                </tr>
                <tr v-if="!sirkulasiRiwayat?.data?.length">
                  <td colspan="6" class="py-8 text-center text-slate-400">Belum ada riwayat pengembalian.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer Tab Riwayat -->
          <div v-if="sirkulasiRiwayat?.total" class="px-4 py-3 bg-slate-50/50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span>Tampilkan</span>
              <select v-model="perPageRiwayat" @change="applyRiwayatPage(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span>baris per halaman</span>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <span class="whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-800">{{ sirkulasiRiwayat.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ sirkulasiRiwayat.to || sirkulasiRiwayat.total }}</span> dari <span class="font-bold text-slate-800">{{ sirkulasiRiwayat.total }}</span> riwayat
              </span>
            </div>

            <!-- Smart Windowing Pagination Links -->
            <div v-if="sirkulasiRiwayat.links && sirkulasiRiwayat.links.length > 3" class="flex items-center gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(sirkulasiRiwayat)" :key="i">
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

      <!-- TAB 4: KAS DENDA & GANTI RUGI -->
      <div v-if="activeTab === 'denda'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">Peminjam & Transaksi</th>
                  <th class="py-3.5 px-3">Buku Terkait</th>
                  <th class="py-3.5 px-3">Keterlambatan</th>
                  <th class="py-3.5 px-3">Denda Kerusakan / Hilang</th>
                  <th class="py-3.5 px-3">Total Denda</th>
                  <th class="py-3.5 px-3 text-center">Status Pembayaran</th>
                  <th class="py-3.5 px-4 text-center">Aksi Kasir</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="d in (dendaList?.data || [])" :key="d.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4">
                    <div class="font-extrabold text-slate-800">{{ d.nama_peminjam }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">{{ d.nomor_transaksi }}</div>
                  </td>
                  <td class="py-3 px-3 font-semibold text-slate-700">{{ d.buku?.judul_buku || '-' }}</td>
                  <td class="py-3 px-3">
                    <div>Rp {{ Number(d.denda_keterlambatan || 0).toLocaleString('id-ID') }}</div>
                    <div class="text-[10px] text-slate-400">({{ d.hari_keterlambatan }} Hari)</div>
                  </td>
                  <td class="py-3 px-3 text-rose-600 font-bold">
                    Rp {{ Number((Number(d.denda_kerusakan || 0) + Number(d.denda_kehilangan || 0))).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 font-black text-slate-900">
                    Rp {{ Number((Number(d.denda_keterlambatan || 0) + Number(d.denda_kerusakan || 0) + Number(d.denda_kehilangan || 0))).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border"
                          :class="d.status_denda === 'Lunas' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                      {{ d.status_denda }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <button v-if="d.status_denda !== 'Lunas'" @click="openModalBayarDenda(d)" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition shadow-2xs text-[11px]">
                      <i class="bi bi-wallet2"></i> Pelunasan
                    </button>
                    <span v-else class="text-[11px] text-emerald-600 font-bold">Lunas ({{ d.metode_pembayaran_denda || 'Tunai' }})</span>
                  </td>
                </tr>
                <tr v-if="!dendaList?.data?.length">
                  <td colspan="7" class="py-8 text-center text-slate-400">Nihil. Tidak ada tagihan atau tunggakan denda.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer Tab Denda -->
          <div v-if="dendaList?.total" class="px-4 py-3 bg-slate-50/50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span>Tampilkan</span>
              <select v-model="perPageDenda" @change="applyDendaPage(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span>baris per halaman</span>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <span class="whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-800">{{ dendaList.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ dendaList.to || dendaList.total }}</span> dari <span class="font-bold text-slate-800">{{ dendaList.total }}</span> catatan denda
              </span>
            </div>

            <!-- Smart Windowing Pagination Links -->
            <div v-if="dendaList.links && dendaList.links.length > 3" class="flex items-center gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(dendaList)" :key="i">
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

      <!-- TAB 5: STOCK OPNAME (SENSUS) -->
      <div v-if="activeTab === 'opname'" class="space-y-4">
        <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div>
            <h3 class="font-bold text-slate-800 text-sm">Stock Opname & Sensus Inventarisasi Fisik Buku</h3>
            <p class="text-xs text-slate-500 mt-0.5">Audit pencocokan barcode buku di rak vs database secara real-time.</p>
          </div>
          <button @click="isModalOpnameOpen = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-plus-circle-fill"></i> Mulai Sesi Opname Baru
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
          <div v-for="op in opnameList" :key="op.id" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between hover:border-blue-300 transition">
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold border" :class="op.status_opname === 'Berjalan' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                  {{ op.status_opname }}
                </span>
                <span class="text-[10px] text-slate-400">{{ op.tanggal_mulai }}</span>
              </div>
              <h4 class="font-extrabold text-slate-800 text-xs">{{ op.nama_sesi }}</h4>
              <p class="text-[11px] text-slate-500 mt-1">{{ op.keterangan || '-' }}</p>
              <div class="mt-3 p-2 bg-slate-50 rounded-xl text-[11px] font-bold text-slate-700 flex justify-between">
                <span>Total Buku Ter-scan:</span>
                <span class="text-blue-700">{{ op.items?.length || 0 }} Item</span>
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-4 pt-3 border-t border-slate-100">
              <button v-if="op.status_opname === 'Berjalan'" @click="closeOpnameSession(op.id)" class="px-2.5 py-1 text-slate-500 hover:text-rose-600 text-[11px] font-bold transition">Tutup Sesi</button>
              <button @click="selectOpnameSession(op)" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-[11px] transition shadow-2xs">
                <i class="bi bi-upc-scan"></i> Buka Runner Scan
              </button>
            </div>
          </div>
        </div>

        <!-- Scanner Runner Box if Sesi Selected -->
        <div v-if="activeOpnameSession" class="bg-white rounded-3xl border border-blue-200 shadow-lg p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="font-black text-slate-800 text-sm">Runner Pemindaian: {{ activeOpnameSession.nama_sesi }}</h3>
              <p class="text-xs text-slate-500">Arahkan scanner nirkabel ke setiap punggung buku di rak secara berurutan.</p>
            </div>
            <button @click="activeOpnameSession = null" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
          </div>

          <form @submit.prevent="submitScanOpname" class="flex items-center gap-2 max-w-lg">
            <input v-model="opnameScanBarcode" type="text" autofocus placeholder="Pindai barcode buku..." class="grow px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold focus:ring-2 focus:ring-blue-600" />
            <button type="submit" class="px-4 py-2.5 bg-blue-600 text-white rounded-xl font-bold text-xs">Scan</button>
          </form>

          <div class="overflow-x-auto max-h-60 overflow-y-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-500 font-bold sticky top-0">
                <tr>
                  <th class="py-2 px-3">Barcode</th>
                  <th class="py-2 px-3">Status Temuan</th>
                  <th class="py-2 px-3">Kondisi Fisik</th>
                  <th class="py-2 px-3">Waktu Scan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-mono">
                <tr v-for="it in (activeOpnameSession.items || [])" :key="it.id">
                  <td class="py-2 px-3 font-bold text-slate-800">{{ it.barcode }}</td>
                  <td class="py-2 px-3 text-emerald-600 font-bold">{{ it.status_temuan }}</td>
                  <td class="py-2 px-3">{{ it.kondisi_fisik }}</td>
                  <td class="py-2 px-3 text-slate-400">{{ it.scanned_at ? it.scanned_at.substring(11, 19) : '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 6: BACA DI TEMPAT -->
      <div v-if="activeTab === 'baca'" class="space-y-4">
        <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div>
            <h3 class="font-bold text-slate-800 text-sm">Pencatatan Baca di Tempat (In-House Reading Log)</h3>
            <p class="text-xs text-slate-500 mt-0.5">Pemindaian buku-buku yang dibaca pemustaka di ruang baca sebelum disortir kembali ke rak.</p>
          </div>
          <button @click="isModalBacaOpen = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-plus-circle-fill"></i> Catat Pembacaan Buku
          </button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">Waktu Baca</th>
                  <th class="py-3.5 px-3">Judul Buku Master</th>
                  <th class="py-3.5 px-3">Barcode Eksemplar</th>
                  <th class="py-3.5 px-3">Pembaca</th>
                  <th class="py-3.5 px-3">Ruangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="b in (bacaList?.data || [])" :key="b.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 text-slate-500">{{ b.waktu_baca ? b.waktu_baca.substring(0, 16).replace('T', ' ') : '-' }}</td>
                  <td class="py-3 px-3 font-bold text-slate-800">{{ b.buku?.judul_buku || '-' }}</td>
                  <td class="py-3 px-3 font-mono text-blue-700">{{ b.eksemplar?.barcode || '-' }}</td>
                  <td class="py-3 px-3">{{ b.nama_pembaca }} ({{ b.tipe_pembaca }})</td>
                  <td class="py-3 px-3 text-slate-600">{{ b.ruang_baca || 'Ruang Baca Utama' }}</td>
                </tr>
                <tr v-if="!bacaList?.data?.length">
                  <td colspan="5" class="py-8 text-center text-slate-400">Belum ada catatan aktivitas baca di tempat.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer Tab Baca -->
          <div v-if="bacaList?.total" class="px-4 py-3 bg-slate-50/50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span>Tampilkan</span>
              <select v-model="perPageBaca" @change="applyBacaPage(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span>baris per halaman</span>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <span class="whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-800">{{ bacaList.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ bacaList.to || bacaList.total }}</span> dari <span class="font-bold text-slate-800">{{ bacaList.total }}</span> pembacaan
              </span>
            </div>

            <!-- Smart Windowing Pagination Links -->
            <div v-if="bacaList.links && bacaList.links.length > 3" class="flex items-center gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(bacaList)" :key="i">
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

      <!-- TAB 7: ANTREAN RESERVASI BUKU -->
      <div v-if="activeTab === 'reservasi'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">Judul Buku Dipesan</th>
                  <th class="py-3.5 px-3">Pemesan</th>
                  <th class="py-3.5 px-3">Tgl Reservasi</th>
                  <th class="py-3.5 px-3">Tenggat Pengambilan</th>
                  <th class="py-3.5 px-3 text-center">Status</th>
                  <th class="py-3.5 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="res in (reservasiList || [])" :key="res.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 font-bold text-slate-800">{{ res.buku?.judul_buku || '-' }}</td>
                  <td class="py-3 px-3">
                    <div class="font-extrabold text-blue-700">{{ res.nama_peminjam }}</div>
                    <div class="text-[10px] text-slate-400">{{ res.nomor_identitas }}</div>
                  </td>
                  <td class="py-3 px-3 text-slate-600">{{ res.tanggal_reservasi }}</td>
                  <td class="py-3 px-3 text-slate-600 font-semibold">{{ res.tanggal_berakhir }}</td>
                  <td class="py-3 px-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border"
                          :class="res.status_reservasi === 'Menunggu' ? 'bg-amber-50 text-amber-700 border-amber-200' : (res.status_reservasi === 'Tersedia/Siap Diambil' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200')">
                      {{ res.status_reservasi }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <button v-if="res.status_reservasi === 'Menunggu'" @click="cancelBooking(res.id)" class="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold rounded-lg transition text-[11px]">
                      Batalkan
                    </button>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                </tr>
                <tr v-if="!reservasiList?.length">
                  <td colspan="6" class="py-8 text-center text-slate-400">Tidak ada antrean reservasi buku online.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 8: LOKER PENITIPAN BARANG -->
      <div v-if="activeTab === 'loker'" class="space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="font-bold text-slate-800 text-sm">Manajemen Loker Penitipan Barang Pemustaka</h3>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Tersedia: {{ stats.total_loker_tersedia || 0 }} Unit</span>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">Terisi: {{ stats.total_loker_terisi || 0 }} Unit</span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Penitipan tas, jaket, dan barang berharga siswa sebelum masuk ke ruang koleksi perpustakaan.</p>
          </div>
          <button @click="isModalTambahLokerOpen = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-plus-circle-fill"></i> Tambah Unit Loker
          </button>
        </div>

        <!-- Grid Status Loker Interaktif -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
          <div v-for="loker in lokerList" :key="loker.id" 
               class="p-4 rounded-2xl border transition-all flex flex-col justify-between relative overflow-hidden"
               :class="loker.status === 'tersedia' ? 'bg-white border-emerald-200 shadow-2xs hover:border-emerald-400' : (loker.status === 'terisi' ? 'bg-rose-50/60 border-rose-200 shadow-2xs' : 'bg-slate-100 border-slate-300')">
            
            <div class="flex items-center justify-between">
              <span class="text-xs font-black" :class="loker.status === 'tersedia' ? 'text-emerald-700' : (loker.status === 'terisi' ? 'text-rose-700' : 'text-slate-600')">
                LOKER #{{ loker.nomor_loker }}
              </span>
              <span class="w-3 h-3 rounded-full" :class="loker.status === 'tersedia' ? 'bg-emerald-500 animate-pulse' : (loker.status === 'terisi' ? 'bg-rose-500' : 'bg-slate-400')"></span>
            </div>

            <div class="my-3 space-y-1">
              <div v-if="loker.status === 'tersedia'" class="text-center py-2">
                <i class="bi bi-unlock text-2xl text-emerald-500"></i>
                <div class="text-[11px] font-bold text-emerald-700 mt-1">Siap Dipinjam</div>
              </div>
              <div v-else-if="loker.status === 'terisi'" class="space-y-0.5 text-2xs">
                <div class="font-bold text-slate-800 truncate">{{ loker.active_log?.nama_peminjam || 'Pemustaka' }}</div>
                <div class="text-slate-500 font-mono">{{ loker.active_log?.identitas_jaminan }} &bull; {{ new Date(loker.active_log?.waktu_pinjam).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</div>
              </div>
              <div v-else class="text-center py-2 text-slate-500 text-2xs">
                <i class="bi bi-slash-circle text-xl"></i>
                <div>{{ loker.status === 'kunci_hilang' ? 'Kunci Hilang' : 'Rusak' }}</div>
              </div>
            </div>

            <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between gap-1">
              <button v-if="loker.status === 'tersedia'" @click="openPinjamLoker(loker)" class="w-full py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-2xs transition">
                Pinjam Kunci
              </button>
              <button v-else-if="loker.status === 'terisi'" @click="openKembaliLoker(loker)" class="w-full py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-2xs transition">
                Kembalikan
              </button>
              <button v-else @click="deleteLoker(loker.id)" class="w-full py-1.5 rounded-lg bg-slate-300 hover:bg-slate-400 text-slate-700 font-bold text-2xs transition">
                Hapus
              </button>
            </div>
          </div>
          <div v-if="!lokerList?.length" class="col-span-full py-12 text-center bg-white rounded-2xl border border-slate-200 text-slate-400 text-xs">
            Belum ada data unit loker penyimpanan. Klik <strong>"Tambah Unit Loker"</strong> untuk menambahkan.
          </div>
        </div>
      </div>

      <!-- TAB 9: SURVEY KEPUASAN IKM -->
      <div v-if="activeTab === 'survey'" class="space-y-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="font-bold text-slate-800 text-sm">Indeks Kepuasan Pemustaka (IKM Perpustakaan)</h3>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                <i class="bi bi-star-fill me-1 text-amber-500"></i> Akreditasi Perpusnas RI
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Hasil rekapitulasi penilaian kepuasan pengunjung melalui Kiosk dan formulir mandiri.</p>
          </div>
          <Link href="/perpustakaan/kiosk" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-tablet"></i> Buka Kiosk Survei
          </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs text-center space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Skor Rata-Rata Pelayanan</div>
            <div class="text-3xl font-black text-amber-500 flex items-center justify-center gap-1">
              <span>4.8</span><span class="text-base text-slate-400 font-normal">/ 5.0</span>
            </div>
            <div class="flex justify-center text-amber-400 text-sm">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            </div>
            <p class="text-2xs text-slate-400">Predikat: <strong>Sangat Memuaskan (A)</strong></p>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs text-center space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kelengkapan Koleksi</div>
            <div class="text-3xl font-black text-blue-600 flex items-center justify-center gap-1">
              <span>4.6</span><span class="text-base text-slate-400 font-normal">/ 5.0</span>
            </div>
            <div class="flex justify-center text-blue-500 text-sm">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
            </div>
            <p class="text-2xs text-slate-400">Berdasarkan 120+ responden</p>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs text-center space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kenyamanan Ruang Baca</div>
            <div class="text-3xl font-black text-emerald-600 flex items-center justify-center gap-1">
              <span>4.9</span><span class="text-base text-slate-400 font-normal">/ 5.0</span>
            </div>
            <div class="flex justify-center text-emerald-500 text-sm">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            </div>
            <p class="text-2xs text-slate-400">AC, Pencahayaan & Kebersihan</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-5">
          <h4 class="font-bold text-slate-800 text-xs mb-3 flex items-center">
            <i class="bi bi-chat-heart-fill text-rose-500 me-2"></i> Ulasan & Saran Terkini dari Pemustaka
          </h4>
          <div class="space-y-2.5">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 text-xs space-y-1">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800">Ahmad Fauzi &bull; Kelas XI RPL 2</span>
                <span class="text-amber-500"><i class="bi bi-star-fill"></i> 5.0</span>
              </div>
              <p class="text-slate-600 text-[11px]">"Sangat nyaman membaca di perpustakaan, pencarian buku di OPAC juga sangat cepat dan akurat!"</p>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 text-xs space-y-1">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800">Siti Nurhaliza &bull; Kelas X MIPA 1</span>
                <span class="text-amber-500"><i class="bi bi-star-fill"></i> 5.0</span>
              </div>
              <p class="text-slate-600 text-[11px]">"Pelayanan petugas ramah sekali, peminjaman lewat scan barcode KTA sangat praktis."</p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 10: BUKU PAKET PELAJARAN -->
      <div v-if="activeTab === 'paket'" class="space-y-4">
        <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div>
            <h3 class="font-bold text-slate-800 text-sm">Distribusi Buku Paket Pelajaran Rombel</h3>
            <p class="text-xs text-slate-500 mt-0.5">Alokasi massal buku kurikulum tematik per rombongan belajar kelas.</p>
          </div>
          <button @click="isModalPaketOpen = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-plus-circle-fill"></i> Alokasi Paket Kelas
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
          <div v-for="p in paketList" :key="p.id" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-100">
                  Kelas {{ p.kelas_id }}
                </span>
                <span class="text-[10px] text-slate-400 font-bold">TA {{ p.tahun_ajaran }}</span>
              </div>
              <h4 class="font-extrabold text-slate-800 text-xs">{{ p.mata_pelajaran }}</h4>
              <div class="text-[11px] text-slate-500 mt-1">Buku: <strong>{{ p.buku?.judul_buku || '-' }}</strong></div>
            </div>

            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
              <span class="text-slate-500">Jumlah Distribusi:</span>
              <span class="font-black text-blue-700">{{ p.jumlah_distribusi }} Eksemplar</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL POPUP: TRANSAKSI PINJAM BUKU (LIVE SEARCH ANGGOTA & SCAN BARCODE) -->
    <Teleport to="body">
      <div v-if="isModalPinjamOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200 text-xs">
          <!-- Header Modal -->
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold shrink-0">
                <i class="bi bi-journal-plus"></i>
              </div>
              <div>
                <h3 class="text-base font-black text-slate-800">Transaksi Peminjaman Buku Sirkulasi</h3>
                <p class="text-xs text-slate-500">Perekaman peminjaman buku berbasis scan barcode eksemplar fisik dan kartu anggota.</p>
              </div>
            </div>
            <button @click="isModalPinjamOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <!-- Form Body -->
          <form @submit.prevent="submitPinjam" class="p-6 overflow-y-auto space-y-4 grow">
            <!-- SECTION 1: PEMINJAM -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3">
              <div class="flex items-center justify-between">
                <span class="font-black text-slate-800 text-xs flex items-center gap-1.5">
                  <i class="bi bi-person-badge-fill text-blue-600"></i> 1. Identitas Peminjam
                </span>
                <button type="button" @click="toggleManualPeminjamMode" class="text-blue-600 hover:underline text-[11px] font-bold">
                  {{ isManualPeminjamMode ? '← Pilih dari Database Anggota' : 'Ketik Manual / Tamu Luar →' }}
                </button>
              </div>

              <!-- Mode Database Anggota -->
              <div v-if="!isManualPeminjamMode">
                <!-- Card Peminjam Terpilih -->
                <div v-if="selectedMember" class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between gap-3">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shrink-0">
                      {{ selectedMember.nama_lengkap ? selectedMember.nama_lengkap.charAt(0).toUpperCase() : 'A' }}
                    </div>
                    <div>
                      <div class="font-black text-slate-800">{{ selectedMember.nama_lengkap }}</div>
                      <div class="text-[10px] text-slate-500 font-mono">ID: {{ selectedMember.identitas_no || selectedMember.no_anggota }} • {{ selectedMember.tipe_anggota }} • {{ selectedMember.kelas_jurusan || '-' }}</div>
                    </div>
                  </div>
                  <button type="button" @click="clearMemberSelection" class="px-2.5 py-1 text-[10px] font-bold bg-white text-rose-600 border border-rose-200 hover:bg-rose-50 rounded-lg transition shrink-0">
                    Ganti
                  </button>
                </div>

                <!-- Input Search Anggota -->
                <div v-else class="relative">
                  <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="bi bi-search"></i>
                  </span>
                  <input v-model="memberSearchKeyword" 
                         @focus="isMemberDropdownOpen = true"
                         type="text" 
                         placeholder="Ketik nama siswa, guru, NISN, atau kelas..." 
                         class="w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />

                  <!-- Dropdown Hasil Pencarian Anggota -->
                  <div v-if="isMemberDropdownOpen && filteredMembers.length > 0" 
                       class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-20 max-h-48 overflow-y-auto divide-y divide-slate-100">
                    <div v-for="m in filteredMembers" 
                         :key="m.id" 
                         @click="selectMember(m)"
                         class="p-2.5 hover:bg-blue-50/60 cursor-pointer transition flex items-center justify-between gap-2">
                      <div class="flex items-center gap-2 min-w-0">
                        <div class="w-6 h-6 rounded bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px] shrink-0">
                          {{ m.nama_lengkap ? m.nama_lengkap.charAt(0).toUpperCase() : 'A' }}
                        </div>
                        <div class="truncate">
                          <div class="font-bold text-slate-800 truncate">{{ m.nama_lengkap }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">ID: {{ m.identitas_no || '-' }} • {{ m.kelas_jurusan || '-' }}</div>
                        </div>
                      </div>
                      <span class="px-2 py-0.5 rounded text-[10px] font-bold shrink-0 bg-slate-100 text-slate-700">
                        {{ m.tipe_anggota }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mode Manual Peminjam -->
              <div v-else class="grid grid-cols-2 gap-3">
                <div>
                  <label class="font-bold text-slate-700 block mb-1">Nama Peminjam *</label>
                  <input v-model="formPinjam.nama_peminjam" type="text" required placeholder="Nama lengkap peminjam..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
                </div>
                <div>
                  <label class="font-bold text-slate-700 block mb-1">Tipe Pemustaka</label>
                  <select v-model="formPinjam.peminjam_type" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    <option value="Siswa">Siswa</option>
                    <option value="Guru">Guru</option>
                    <option value="Tendik">Tendik</option>
                    <option value="Alumni">Alumni</option>
                    <option value="Umum">Umum / Tamu</option>
                  </select>
                </div>
                <div>
                  <label class="font-bold text-slate-700 block mb-1">Nomor Identitas (NISN / NIP)</label>
                  <input v-model="formPinjam.nomor_identitas" type="text" placeholder="NISN / NIP / KTP..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-600 focus:outline-none" />
                </div>
                <div>
                  <label class="font-bold text-slate-700 block mb-1">Kelas / Unit Kerja</label>
                  <input v-model="formPinjam.kelas_unit" type="text" placeholder="Contoh: XII-RPL-1" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
                </div>
              </div>
            </div>

            <!-- SECTION 2: BUKU & EKSEMPLAR -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3">
              <span class="font-black text-slate-800 text-xs flex items-center gap-1.5">
                <i class="bi bi-book-half text-blue-600"></i> 2. Koleksi Buku / Barcode Eksemplar Fisik
              </span>

              <!-- Card Buku & Eksemplar Terpilih -->
              <div v-if="selectedBuku || selectedEksemplar" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shrink-0">
                    <i class="bi bi-journal-bookmark-fill"></i>
                  </div>
                  <div>
                    <div class="font-black text-slate-800">{{ selectedBuku?.judul_buku || selectedEksemplar?.buku?.judul_buku }}</div>
                    <div class="text-[10px] text-slate-500 font-mono flex items-center gap-2">
                      <span v-if="selectedEksemplar?.barcode" class="font-bold text-emerald-800 bg-emerald-100 px-1.5 py-0.2 rounded">Barcode: {{ selectedEksemplar.barcode }}</span>
                      <span v-if="selectedBuku?.pengarang || selectedEksemplar?.buku?.pengarang">Pengarang: {{ selectedBuku?.pengarang || selectedEksemplar?.buku?.pengarang }}</span>
                      <span v-if="selectedBuku?.nomor_panggil || selectedEksemplar?.buku?.nomor_panggil">No. Panggil: {{ selectedBuku?.nomor_panggil || selectedEksemplar?.buku?.nomor_panggil }}</span>
                    </div>
                  </div>
                </div>
                <button type="button" @click="clearBukuSelection" class="px-2.5 py-1 text-[10px] font-bold bg-white text-rose-600 border border-rose-200 hover:bg-rose-50 rounded-lg transition shrink-0">
                  Ganti
                </button>
              </div>

              <!-- Input Search Buku / Scan Barcode -->
              <div v-else class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                  <i class="bi bi-upc-scan"></i>
                </span>
                <input v-model="bukuSearchKeyword" 
                       @focus="isBukuDropdownOpen = true"
                       type="text" 
                       placeholder="Pindai barcode stiker buku atau ketik judul/pengarang..." 
                       class="w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />

                <!-- Dropdown Hasil Pencarian Buku / Eksemplar -->
                <div v-if="isBukuDropdownOpen && filteredBukuAndEksemplar.length > 0" 
                     class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-20 max-h-48 overflow-y-auto divide-y divide-slate-100">
                  <div v-for="item in filteredBukuAndEksemplar" 
                       :key="item.id" 
                       @click="selectBukuOrEksemplar(item)"
                       class="p-2.5 hover:bg-blue-50/60 cursor-pointer transition flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                      <div class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px] shrink-0">
                        <i class="bi bi-book"></i>
                      </div>
                      <div class="truncate">
                        <div class="font-bold text-slate-800 truncate">{{ item.judul_buku || item.buku?.judul_buku }}</div>
                        <div class="text-[10px] text-slate-400 font-mono">
                          <span v-if="item.barcode" class="text-blue-700 font-bold me-1.5">Barcode: {{ item.barcode }}</span>
                          <span>{{ item.pengarang || item.buku?.pengarang || '-' }}</span>
                        </div>
                      </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold shrink-0 bg-emerald-50 text-emerald-700">
                      Tersedia
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 3: TANGGAL, DURASI & JATUH TEMPO -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Tanggal Pinjam *</label>
                <input v-model="formPinjam.tanggal_pinjam" type="date" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </div>
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="font-bold text-slate-700">Durasi Pinjam (Hari) *</label>
                  <span class="text-[11px] font-extrabold text-blue-700">
                    Jatuh Tempo: {{ calculateDueDate(formPinjam.tanggal_pinjam, formPinjam.durasi_hari) }}
                  </span>
                </div>
                <input v-model="formPinjam.durasi_hari" type="number" min="1" max="60" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white font-black text-blue-700 focus:ring-2 focus:ring-blue-600 focus:outline-none mb-1.5" />
                
                <!-- Quick Duration Shortcuts -->
                <div class="flex items-center gap-1.5">
                  <button v-for="d in [3, 7, 14, 30]" 
                          :key="d" 
                          type="button" 
                          @click="formPinjam.durasi_hari = d"
                          class="flex-1 py-1 rounded-lg text-[10px] font-bold border transition text-center"
                          :class="formPinjam.durasi_hari === d ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'">
                    {{ d }} Hari
                  </button>
                </div>
              </div>
            </div>

            <!-- SECTION 4: CATATAN -->
            <div>
              <label class="font-bold text-slate-700 block mb-1">Catatan Tambahan (Opsional)</label>
              <input v-model="formPinjam.catatan" type="text" placeholder="Contoh: Keperluan lomba karya ilmiah / referensi tugas akhir..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
            </div>

            <!-- Footer Modal -->
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalPinjamOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                Batal
              </button>
              <button type="submit" 
                      :disabled="formPinjam.processing || (!formPinjam.buku_id) || (!formPinjam.nama_peminjam)"
                      class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold transition flex items-center gap-2 shadow-xs">
                <i class="bi bi-check-circle-fill"></i>
                <span>Proses Peminjaman</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ============================================================== -->
    <!-- MODAL POPUP: FORM PENGEMBALIAN & PENGECEKAN FISIK              -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isModalKembaliOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-1">Verifikasi Pengembalian Buku</h3>
          <p class="text-xs text-slate-500 mb-4">Peminjam: <strong>{{ selectedSirkulasi?.nama_peminjam }}</strong></p>

          <form @submit.prevent="submitKembali" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Kondisi Fisik Buku Saat Kembali</label>
              <select v-model="formKembali.kondisi_kembali" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium">
                <option value="Baik">Kondisi Baik (Sempurna)</option>
                <option value="Rusak Ringan">Rusak Ringan (Robek / Coretan)</option>
                <option value="Rusak Berat">Rusak Berat (Halaman Hilang / Basah)</option>
                <option value="Hilang">Buku Hilang (Ganti Baru)</option>
              </select>
            </div>

            <div v-if="formKembali.kondisi_kembali === 'Rusak Ringan' || formKembali.kondisi_kembali === 'Rusak Berat'">
              <label class="font-bold text-slate-700 block mb-1">Denda Ganti Rugi Kerusakan (Rp)</label>
              <input v-model="formKembali.denda_kerusakan" type="number" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-bold text-rose-600" />
            </div>

            <div v-if="formKembali.kondisi_kembali === 'Hilang'">
              <label class="font-bold text-slate-700 block mb-1">Denda Penggantian Buku Hilang (Rp)</label>
              <input v-model="formKembali.denda_kehilangan" type="number" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-bold text-rose-600" />
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalKembaliOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Batal</button>
              <button type="submit" :disabled="formKembali.processing" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-xs">
                Konfirmasi Pengembalian
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Pelunasan Denda -->
    <Teleport to="body">
      <div v-if="isModalBayarDendaOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-1">Pelunasan Kas Denda Perpustakaan</h3>
          <p class="text-xs text-slate-500 mb-4">Peminjam: <strong>{{ selectedDendaSirkulasi?.nama_peminjam }}</strong></p>

          <form @submit.prevent="submitBayarDenda" class="space-y-3.5">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
              <div class="flex justify-between font-bold text-slate-700">
                <span>Total Tagihan Denda:</span>
                <span class="text-rose-600 font-black">
                  Rp {{ Number((Number(selectedDendaSirkulasi?.denda_keterlambatan || 0) + Number(selectedDendaSirkulasi?.denda_kerusakan || 0) + Number(selectedDendaSirkulasi?.denda_kehilangan || 0))).toLocaleString('id-ID') }}
                </span>
              </div>
            </div>

            <div>
              <label class="font-bold text-slate-700 block mb-1">Metode Penerimaan Kas</label>
              <select v-model="formBayarDenda.metode_pembayaran" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium">
                <option value="Tunai">Tunai / Cash Kasir</option>
                <option value="Saldo Siswa">Potong Saldo Siswa</option>
                <option value="QRIS / Transfer">QRIS / Transfer Bank</option>
              </select>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalBayarDendaOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Batal</button>
              <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-xs">
                Simpan Pelunasan
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Mulai Stock Opname -->
    <Teleport to="body">
      <div v-if="isModalOpnameOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Mulai Sesi Stock Opname Baru</h3>
          <form @submit.prevent="submitOpname" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Sesi Sensus *</label>
              <input v-model="formOpname.nama_sesi" type="text" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Tanggal Mulai *</label>
              <input v-model="formOpname.tanggal_mulai" type="date" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Keterangan / Ruang Target</label>
              <textarea v-model="formOpname.keterangan" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium"></textarea>
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalOpnameOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold">Mulai Sesi</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Catat Baca di Tempat -->
    <Teleport to="body">
      <div v-if="isModalBacaOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Catat Pembacaan di Ruangan</h3>
          <form @submit.prevent="submitBaca" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Barcode Buku Fisik *</label>
              <input v-model="formBaca.barcode" type="text" autofocus required placeholder="Pindai barcode buku..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono font-bold text-blue-700" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Pembaca</label>
              <input v-model="formBaca.nama_pembaca" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Tipe Pembaca</label>
                <select v-model="formBaca.tipe_pembaca" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium">
                  <option value="Siswa">Siswa</option>
                  <option value="Guru">Guru</option>
                  <option value="Tamu">Tamu</option>
                </select>
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Ruang Baca</label>
                <input v-model="formBaca.ruang_baca" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalBacaOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold">Simpan Log</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Alokasi Paket Kelas -->
    <Teleport to="body">
      <div v-if="isModalPaketOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Alokasi Buku Paket Pelajaran</h3>
          <form @submit.prevent="submitPaket" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Rombel / Kelas Target *</label>
              <input v-model="formPaket.kelas_id" type="text" required placeholder="Contoh: VII-A, X-RPL-1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Mata Pelajaran *</label>
              <input v-model="formPaket.mata_pelajaran" type="text" required placeholder="Contoh: Matematika Kurikulum Merdeka" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Judul Buku Master *</label>
              <select v-model="formPaket.buku_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium">
                <option value="">-- Pilih Buku --</option>
                <option v-for="b in bukuTersedia" :key="b.id" :value="b.id">{{ b.judul_buku }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Tahun Ajaran</label>
                <input v-model="formPaket.tahun_ajaran" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Jumlah Distribusi</label>
                <input v-model="formPaket.jumlah_distribusi" type="number" min="1" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalPaketOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold">Simpan Paket</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal 1: Tambah Unit Loker -->
    <Teleport to="body">
      <div v-if="isModalTambahLokerOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Tambah Unit Loker Baru</h3>
          <form @submit.prevent="submitTambahLoker" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nomor / Kode Loker *</label>
              <input v-model="formTambahLoker.nomor_loker" type="text" required placeholder="Contoh: 01, A-12, LK-05" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Lokasi Ruangan</label>
              <input v-model="formTambahLoker.lokasi_ruangan" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Keterangan Tambahan</label>
              <textarea v-model="formTambahLoker.keterangan" rows="2" placeholder="Catatan kondisi loker..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium"></textarea>
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalTambahLokerOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" :disabled="formTambahLoker.processing" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold">Simpan Unit Loker</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal 2: Pinjam Kunci Loker -->
    <Teleport to="body">
      <div v-if="isModalPinjamLokerOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-1">Pinjam Kunci Loker #{{ selectedLoker?.nomor_loker }}</h3>
          <p class="text-xs text-slate-500 mb-4">Peminjaman loker penitipan barang pemustaka.</p>
          <form @submit.prevent="submitPinjamLoker" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Peminjam *</label>
              <input v-model="formPinjamLoker.nama_peminjam" type="text" required placeholder="Nama siswa / pengunjung..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Identitas Jaminan *</label>
              <select v-model="formPinjamLoker.identitas_jaminan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium">
                <option value="KTA">Kartu Anggota (KTA)</option>
                <option value="Kartu Pelajar">Kartu Pelajar</option>
                <option value="KTP">KTP / SIM</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Catatan</label>
              <input v-model="formPinjamLoker.catatan" type="text" placeholder="Keterangan barang bawaan..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalPinjamLokerOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" :disabled="formPinjamLoker.processing" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold">Serahkan Kunci</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal 3: Pengembalian Kunci Loker -->
    <Teleport to="body">
      <div v-if="isModalKembaliLokerOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-1">Pengembalian Kunci Loker #{{ selectedLoker?.nomor_loker }}</h3>
          <p class="text-xs text-slate-500 mb-4">Peminjam: <strong>{{ selectedLoker?.active_log?.nama_peminjam }}</strong></p>
          <form @submit.prevent="submitKembaliLoker" class="space-y-3.5">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
              <label class="flex items-center space-x-2 text-rose-700 font-bold cursor-pointer">
                <input type="checkbox" v-model="formKembaliLoker.kunci_hilang" class="rounded text-rose-600 focus:ring-rose-500" />
                <span>Kunci Hilang / Rusak (Denda Ganti Kunci)</span>
              </label>
            </div>
            <div v-if="formKembaliLoker.kunci_hilang">
              <label class="font-bold text-slate-700 block mb-1">Biaya Denda Ganti Kunci (Rp)</label>
              <input v-model.number="formKembaliLoker.denda" type="number" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-rose-300 font-bold text-rose-700 font-mono" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Catatan Pengembalian</label>
              <input v-model="formKembaliLoker.catatan" type="text" placeholder="Kondisi loker saat dikembalikan..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalKembaliLokerOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" :disabled="formKembaliLoker.processing" class="px-4 py-2 rounded-xl bg-rose-600 text-white font-bold">Konfirmasi Selesai</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
