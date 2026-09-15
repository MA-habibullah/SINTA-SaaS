<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import axios from 'axios'


const props = defineProps({
  bukuList: Object,
  eksemplarList: Object,
  rakList: Array,
  usulanList: Array,
  serialList: Array,
  ddcList: Array,
  stats: Object,
  tenants: Array,
  isSuperAdmin: Boolean,
  activeTenantId: String,
  filters: Object,
})

const activeTab = ref('buku')
const viewMode = ref('table') // 'table' | 'grid'
const searchQuery = ref(props.filters?.search || '')
const searchEksemplarQuery = ref('')
const filterDdc = ref(props.filters?.ddc || '')
const filterJenisBahan = ref(props.filters?.jenis_bahan || '')
const selectedTenantId = ref(props.activeTenantId || '')
const isLoadingFilter = ref(false)

// State lokal data (async filter)
const localBukuList     = ref(props.bukuList     || { data: [] })
const localEksemplarList = ref(props.eksemplarList || { data: [] })

const getSelectedTenantName = () => {
  if (!selectedTenantId.value) return 'Semua Sekolah (Agregat Global)'
  const found = props.tenants?.find(t => t.id === selectedTenantId.value)
  return found ? `${found.nama_sekolah}` : 'Sekolah Terpilih'
}

const applyTenantFilter = () => { applySearch(1) }

const applySearch = async (page = 1) => {
  isLoadingFilter.value = true
  try {
    const headers = {}
    if (selectedTenantId.value) headers['X-Tenant-Id'] = selectedTenantId.value
    const res = await axios.get('/perpustakaan/katalog', {
      params: {
        async: 1,
        search: searchQuery.value || undefined,
        ddc: filterDdc.value || undefined,
        jenis_bahan: filterJenisBahan.value || undefined,
        per_page: perPageBuku.value || undefined,
        page: page > 1 ? page : undefined,
        search_eksemplar: searchEksemplarQuery.value || undefined,
        per_page_eksemplar: perPageEksemplar.value || undefined,
      },
      headers,
    })
    if (res.data?.success) {
      if (res.data.data?.bukuList)     localBukuList.value     = res.data.data.bukuList
      if (res.data.data?.eksemplarList) localEksemplarList.value = res.data.data.eksemplarList
    }
  } catch (err) {
    console.error('Gagal filter katalog:', err)
  } finally {
    isLoadingFilter.value = false
  }
}

const applyEksemplarSearch = (page = 1) => applySearch(page)

const goToPage = async (url) => {
  if (!url) return
  try {
    const urlObj = new URL(url, window.location.origin)
    const page = urlObj.searchParams.get('page') || 1
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
// MODAL TAMBAH / EDIT BUKU (MARC 21 & RDA STANDARD)
// -------------------------------------------------------------
const isModalBukuOpen = ref(false)
const isEditBuku = ref(false)
const editBukuId = ref(null)

const formBuku = useForm({
  judul_buku: '',
  anak_judul: '',
  pengarang: '',
  pengarang_tambahan: '',
  penerbit: '',
  kota_terbit: '',
  tahun_terbit: new Date().getFullYear(),
  edisi: 'Cet. 1',
  jenis_bahan: 'Buku Teks / Monograf',
  isbn: '',
  kode_buku: '',
  nomor_klasifikasi_ddc: '000',
  nomor_panggil: '',
  deskripsi_fisik: '',
  halaman: 100,
  dimensi: '21 cm',
  bahasa: 'Indonesia',
  subjek: '',
  sinopsis: '',
  kategori: 'Umum',
  lokasi_rak: 'Rak Utama',
  jumlah_eksemplar: 1,
  is_ebook: false,
  status_opac: true,
  is_quarantine: false,
  cover_file: null,
  ebook_file: null,
})

const openModalTambahBuku = () => {
  isEditBuku.value = false
  editBukuId.value = null
  formBuku.reset()
  formBuku.tahun_terbit = new Date().getFullYear()
  formBuku.edisi = 'Cet. 1'
  formBuku.jenis_bahan = 'Buku Teks / Monograf'
  formBuku.nomor_klasifikasi_ddc = '000'
  formBuku.jumlah_eksemplar = 1
  formBuku.status_opac = true
  isModalBukuOpen.value = true
}

const openModalEditBuku = (buku) => {
  isEditBuku.value = true
  editBukuId.value = buku.id
  formBuku.judul_buku = buku.judul_buku || ''
  formBuku.anak_judul = buku.anak_judul || ''
  formBuku.pengarang = buku.pengarang || ''
  formBuku.pengarang_tambahan = buku.pengarang_tambahan || ''
  formBuku.penerbit = buku.penerbit || ''
  formBuku.kota_terbit = buku.kota_terbit || ''
  formBuku.tahun_terbit = buku.tahun_terbit || new Date().getFullYear()
  formBuku.edisi = buku.edisi || 'Cet. 1'
  formBuku.jenis_bahan = buku.jenis_bahan || 'Buku Teks / Monograf'
  formBuku.isbn = buku.isbn || ''
  formBuku.kode_buku = buku.kode_buku || ''
  formBuku.nomor_klasifikasi_ddc = buku.nomor_klasifikasi_ddc || '000'
  formBuku.nomor_panggil = buku.nomor_panggil || ''
  formBuku.deskripsi_fisik = buku.deskripsi_fisik || ''
  formBuku.halaman = buku.halaman || 100
  formBuku.dimensi = buku.dimensi || '21 cm'
  formBuku.bahasa = buku.bahasa || 'Indonesia'
  formBuku.subjek = buku.subjek || ''
  formBuku.sinopsis = buku.sinopsis || ''
  formBuku.kategori = buku.kategori || 'Umum'
  formBuku.lokasi_rak = buku.lokasi_rak || 'Rak Utama'
  formBuku.jumlah_eksemplar = buku.jumlah_eksemplar || 1
  formBuku.is_ebook = Boolean(buku.is_ebook)
  formBuku.status_opac = Boolean(buku.status_opac)
  formBuku.is_quarantine = Boolean(buku.is_quarantine)
  isModalBukuOpen.value = true
}

const submitFormBuku = () => {
  if (isEditBuku.value) {
    formBuku.post(`/perpustakaan/katalog/${editBukuId.value}`, {
      onSuccess: () => {
        isModalBukuOpen.value = false
        formBuku.reset()
      }
    })
  } else {
    formBuku.post('/perpustakaan/katalog', {
      onSuccess: () => {
        isModalBukuOpen.value = false
        formBuku.reset()
      }
    })
  }
}

const confirmDeleteBuku = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus buku ini beserta seluruh data eksemplarnya?')) {
    router.delete(`/perpustakaan/katalog/${id}`)
  }
}

const toggleStatus = (id) => {
  router.post(`/perpustakaan/katalog/${id}/toggle-status`, {}, {
    preserveScroll: true
  })
}

// -------------------------------------------------------------
// MODAL TAMBAH EKSEMPLAR
// -------------------------------------------------------------
const isModalEksemplarOpen = ref(false)
const selectedBukuForEks = ref(null)

const formEksemplar = useForm({
  bibliografi_id: '',
  barcode: '',
  no_induk: '',
  nomor_panggil_item: '',
  lokasi_rak: '',
  status_kondisi: 'Tersedia',
  tipe_koleksi: 'Sirkulasi',
  sumber_perolehan: 'Pengadaan BOS',
  harga_beli: 0,
  catatan_kondisi: '',
})

const openModalEksemplar = (buku) => {
  selectedBukuForEks.value = buku
  formEksemplar.bibliografi_id = buku.id
  formEksemplar.lokasi_rak = buku.lokasi_rak || 'Rak Utama'
  formEksemplar.barcode = (buku.kode_buku || 'BK') + '-' + Math.floor(100 + Math.random() * 900)
  formEksemplar.no_induk = 'IND-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000)
  formEksemplar.nomor_panggil_item = (buku.nomor_panggil || buku.nomor_klasifikasi_ddc || '000') + ' c.' + ((buku.jumlah_eksemplar || 1) + 1)
  formEksemplar.status_kondisi = 'Tersedia'
  formEksemplar.tipe_koleksi = 'Sirkulasi'
  isModalEksemplarOpen.value = true
}

const submitEksemplar = () => {
  formEksemplar.post('/perpustakaan/eksemplar', {
    onSuccess: () => {
      isModalEksemplarOpen.value = false
      formEksemplar.reset()
    }
  })
}

const deleteEksemplar = (id) => {
  if (confirm('Hapus eksemplar fisik ini dari basis data?')) {
    router.delete(`/perpustakaan/eksemplar/${id}`, { preserveScroll: true })
  }
}

// -------------------------------------------------------------
// MODAL CETAK LABEL PUNGGUNG (SPINE LABEL) & BARCODE STIKER
// -------------------------------------------------------------
const isModalCetakLabelOpen = ref(false)
const selectedEksemplarForPrint = ref([])
const labelPrintType = ref('spine') // 'spine' | 'barcode' | 'all'

const openModalCetakLabel = (items = null) => {
  if (items) {
    selectedEksemplarForPrint.value = Array.isArray(items) ? items : [items]
  } else {
    // Ambil 10 eksemplar pertama
    selectedEksemplarForPrint.value = props.eksemplarList?.data ? props.eksemplarList.data.slice(0, 12) : []
  }
  isModalCetakLabelOpen.value = true
}

const printLabelWindow = () => {
  window.print()
}

// -------------------------------------------------------------
// MASTER RAK & DDC & USULAN & SERIAL
// -------------------------------------------------------------
const isModalRakOpen = ref(false)
const formRak = useForm({
  kode_rak: '',
  nama_rak: '',
  lantai_gedung: 'Lantai 1',
  kapasitas_buku: 100,
})

const submitRak = () => {
  formRak.post('/perpustakaan/master-rak', {
    onSuccess: () => {
      isModalRakOpen.value = false
      formRak.reset()
    }
  })
}

const deleteRak = (id) => {
  if (confirm('Hapus master rak ini?')) {
    router.delete(`/perpustakaan/master-rak/${id}`, { preserveScroll: true })
  }
}

const isModalDdcOpen = ref(false)
const formDdc = useForm({
  kode_ddc: '',
  nama_klasifikasi: '',
  warna_label: '#3b82f6',
  deskripsi_ddc: '',
})

const submitDdc = () => {
  formDdc.post('/perpustakaan/master-ddc', {
    onSuccess: () => {
      isModalDdcOpen.value = false
      formDdc.reset()
    }
  })
}

const deleteDdc = (id) => {
  if (confirm('Hapus klasifikasi DDC ini?')) {
    router.delete(`/perpustakaan/master-ddc/${id}`, { preserveScroll: true })
  }
}

const isModalUsulanOpen = ref(false)
const formUsulan = useForm({
  judul_buku: '',
  pengarang: '',
  penerbit: '',
  pengusul_nama: '',
  alasan_usulan: '',
})

const submitUsulan = () => {
  formUsulan.post('/perpustakaan/usulan-buku', {
    onSuccess: () => {
      isModalUsulanOpen.value = false
      formUsulan.reset()
    }
  })
}

const updateStatusUsulan = (id, status) => {
  router.patch(`/perpustakaan/usulan-buku/${id}`, { status_usulan: status }, { preserveScroll: true })
}

const isModalSerialOpen = ref(false)
const formSerial = useForm({
  nama_serial: '',
  jenis_serial: 'Majalah',
  issn: '',
  edisi_nomor: '',
  frekuensi_terbit: 'Bulanan',
})

const submitSerial = () => {
  formSerial.post('/perpustakaan/serial-berkala', {
    onSuccess: () => {
      isModalSerialOpen.value = false
      formSerial.reset()
    }
  })
}

const deleteSerial = (id) => {
  if (confirm('Hapus terbitan serial berkala ini?')) {
    router.delete(`/perpustakaan/serial-berkala/${id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <AppLayout title="Katalog & Inventori Perpustakaan">
    <div class="space-y-6">
      <!-- Header & Action -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Katalog & Inventori Perpustakaan</h1>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-blue-100 text-blue-700">MARC 21 & RDA Standard</span>
          </div>
          <p class="text-xs text-slate-500 mt-1">Klasifikasi DDC 000-900, nomor panggil item 3-baris, multi-eksemplar fisik, lokasi rak, dan cetak stiker barcode.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button @click="openModalCetakLabel(null)" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs">
            <i class="bi bi-printer-fill"></i>
            <span>Cetak Label & Barcode</span>
          </button>
          <button @click="openModalTambahBuku" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs shadow-blue-500/20">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Entri Katalog Baru</span>
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
                Menampilkan data katalog perpustakaan milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
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

      <!-- Quick Stats Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Total Judul</span>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm"><i class="bi bi-journal-bookmark-fill"></i></span>
          </div>
          <div class="text-xl font-black text-slate-800 mt-2">{{ stats.total_judul || 0 }}</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Judul Buku Terdaftar</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Total Eksemplar</span>
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm"><i class="bi bi-bookshelf"></i></span>
          </div>
          <div class="text-xl font-black text-indigo-700 mt-2">{{ stats.total_eksemplar || 0 }}</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Fisik Buku di Perpustakaan</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Tersedia di Rak</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm"><i class="bi bi-check-circle-fill"></i></span>
          </div>
          <div class="text-xl font-black text-emerald-600 mt-2">{{ stats.total_tersedia || 0 }}</div>
          <div class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">Siap Dipinjam</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Sedang Dipinjam</span>
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm"><i class="bi bi-arrow-repeat"></i></span>
          </div>
          <div class="text-xl font-black text-amber-600 mt-2">{{ stats.total_dipinjam || 0 }}</div>
          <div class="text-[11px] text-amber-600/80 mt-0.5 font-medium">Di Tangan Siswa/Guru</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs col-span-2 sm:col-span-1">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">E-Book Digital</span>
            <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm"><i class="bi bi-file-earmark-pdf-fill"></i></span>
          </div>
          <div class="text-xl font-black text-purple-700 mt-2">{{ stats.total_ebook || 0 }}</div>
          <div class="text-[11px] text-purple-600/80 mt-0.5 font-medium">Dapat Dibaca Online</div>
        </div>
      </div>

      <!-- Standard Modern Horizontal NavTabs Scroller (Pill Layout) -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-katalog')?.scrollBy({ left: -220, behavior: 'smooth' })"
                  title="Geser ke Kiri">
            <i class="bi bi-chevron-left"></i>
          </button>

          <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navtabs-katalog" role="tablist">
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'buku' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'buku'">
                  <i class="bi bi-book-half me-2 text-sm"></i> 1. Katalog & Bibliografi Buku
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'eksemplar' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'eksemplar'">
                  <i class="bi bi-upc-scan me-2 text-sm"></i> 2. Eksemplar & Barcode Fisik
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'ddc' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'ddc'">
                  <i class="bi bi-tag-fill me-2 text-sm"></i> 3. Klasifikasi DDC Standar
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'rak' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'rak'">
                  <i class="bi bi-grid-3x3-gap-fill me-2 text-sm"></i> 4. Master Lokasi Rak
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'usulan' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'usulan'">
                  <i class="bi bi-lightbulb-fill me-2 text-sm"></i> 5. Usulan Pengadaan Buku
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'serial' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'serial'">
                  <i class="bi bi-newspaper me-2 text-sm"></i> 6. Majalah & Serial Berkala
                </button>
              </li>
            </ul>
          </div>

          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-katalog')?.scrollBy({ left: 220, behavior: 'smooth' })"
                  title="Geser ke Kanan">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- TAB 1: DAFTAR KATALOG BUKU -->
      <div v-if="activeTab === 'buku'" class="space-y-4">
        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
            <div class="relative w-full md:w-64">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="bi bi-search"></i></span>
              <input v-model="searchQuery" @keyup.enter="applySearch" type="text" placeholder="Cari judul, penulis, ISBN, DDC..." class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
            </div>

            <select v-model="filterDdc" @change="applySearch" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
              <option value="">Semua Klasifikasi DDC</option>
              <option v-for="d in ddcList" :key="d.id" :value="d.kode_ddc">{{ d.kode_ddc }} - {{ d.nama_klasifikasi }}</option>
            </select>

            <select v-model="filterJenisBahan" @change="applySearch" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
              <option value="">Semua Jenis Bahan</option>
              <option value="Buku Teks / Monograf">Buku Teks / Monograf</option>
              <option value="Modul Pembelajaran">Modul Pembelajaran</option>
              <option value="Karya Tulis / Skripsi">Karya Tulis / Skripsi</option>
              <option value="Terbitan Berkala">Terbitan Berkala</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <button @click="viewMode = viewMode === 'table' ? 'grid' : 'table'" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
              <i class="bi" :class="viewMode === 'table' ? 'bi-grid-fill' : 'bi-table'"></i>
              <span>{{ viewMode === 'table' ? 'Tampilan Grid' : 'Tampilan Tabel' }}</span>
            </button>
          </div>
        </div>

        <!-- Mode Tabel -->
        <div v-if="viewMode === 'table'" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">Buku & Bibliografi (MARC 21)</th>
                  <th class="py-3.5 px-3">Call Number & DDC</th>
                  <th class="py-3.5 px-3">Penerbit & Tahun</th>
                  <th class="py-3.5 px-3">Lokasi Rak</th>
                  <th class="py-3.5 px-3 text-center">Stok Fisik</th>
                  <th class="py-3.5 px-3 text-center">OPAC</th>
                  <th class="py-3.5 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="buku in (bukuList?.data || [])" :key="buku.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-14 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-slate-400 shadow-2xs">
                        <img v-if="buku.cover_url" :src="buku.cover_url" alt="Cover" class="w-full h-full object-cover" />
                        <i v-else class="bi bi-book text-base text-slate-400"></i>
                      </div>
                      <div>
                        <div class="font-extrabold text-slate-800 text-xs flex items-center gap-1.5">
                          <span>{{ buku.judul_buku }}</span>
                          <span v-if="buku.is_ebook" class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-purple-100 text-purple-700">PDF</span>
                        </div>
                        <div v-if="buku.anak_judul" class="text-[11px] text-slate-600 italic">{{ buku.anak_judul }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Penulis: <strong>{{ buku.pengarang }}</strong> <span v-if="buku.pengarang_tambahan" class="text-slate-400">({{ buku.pengarang_tambahan }})</span></div>
                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">ISBN: {{ buku.isbn || '-' }} | Kode: {{ buku.kode_buku }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-mono font-bold text-blue-700 text-[11px]">{{ buku.nomor_panggil || buku.nomor_klasifikasi_ddc }}</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100 mt-0.5">
                      DDC {{ buku.nomor_klasifikasi_ddc || '000' }}
                    </span>
                    <div class="text-[10px] text-slate-400 mt-0.5">{{ buku.jenis_bahan || 'Buku Teks' }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-medium text-slate-700">{{ buku.penerbit || '-' }}</div>
                    <div class="text-[10px] text-slate-400">{{ buku.tahun_terbit || '-' }} ({{ buku.kota_terbit || '-' }}) • {{ buku.edisi || 'Cet. 1' }}</div>
                  </td>
                  <td class="py-3 px-3">
                    <span class="font-bold text-slate-700">{{ buku.lokasi_rak || '-' }}</span>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <div class="inline-flex flex-col items-center">
                      <span class="font-black text-xs" :class="buku.jumlah_tersedia > 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ buku.jumlah_tersedia }} / {{ buku.jumlah_eksemplar }}
                      </span>
                      <span class="text-[10px] text-slate-400">Tersedia</span>
                    </div>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <button @click="toggleStatus(buku.id)" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border transition"
                            :class="buku.status_opac ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'">
                      {{ buku.status_opac ? 'Publik' : 'Privat' }}
                    </button>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button @click="openModalEditBuku(buku)" class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" title="Edit Data Buku">
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button @click="openModalEksemplar(buku)" class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Kelola Eksemplar">
                        <i class="bi bi-upc"></i>
                      </button>
                      <button @click="confirmDeleteBuku(buku.id)" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus Buku">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!bukuList?.data?.length">
                  <td colspan="7" class="py-8 text-center text-slate-400">Belum ada data bibliografi buku. Klik tombol "Entri Katalog Baru" untuk menambahkan.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer Buku (Table Mode) -->
          <div v-if="bukuList?.total" class="px-4 py-3 bg-slate-50/50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span>Tampilkan</span>
              <select v-model="perPageBuku" @change="applySearch(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
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
                Menampilkan <span class="font-bold text-slate-800">{{ bukuList.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ bukuList.to || bukuList.total }}</span> dari <span class="font-bold text-slate-800">{{ bukuList.total }}</span> judul buku
              </span>
            </div>

            <!-- Smart Windowing Pagination Links -->
            <div v-if="bukuList.links && bukuList.links.length > 3" class="flex items-center gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(bukuList)" :key="i">
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

        <!-- Mode Grid Card -->
        <div v-else class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="buku in (bukuList?.data || [])" :key="buku.id" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-4 flex flex-col justify-between hover:shadow-md transition">
              <div>
                <div class="flex items-start gap-3 mb-3">
                  <div class="w-14 h-20 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-slate-400 shadow-2xs">
                    <img v-if="buku.cover_url" :src="buku.cover_url" alt="Cover" class="w-full h-full object-cover" />
                    <i v-else class="bi bi-book text-xl text-slate-400"></i>
                  </div>
                  <div class="grow">
                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-100 mb-1">
                      DDC {{ buku.nomor_klasifikasi_ddc || '000' }}
                    </span>
                    <h3 class="font-extrabold text-slate-800 text-xs line-clamp-2 leading-snug">{{ buku.judul_buku }}</h3>
                    <div class="text-[11px] text-slate-500 mt-1">Penulis: {{ buku.pengarang }}</div>
                  </div>
                </div>
                <div class="space-y-1 text-[11px] text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                  <div class="flex justify-between">
                    <span class="text-slate-400">Penerbit:</span>
                    <span class="font-medium text-slate-700">{{ buku.penerbit || '-' }} ({{ buku.tahun_terbit }})</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">Call Number:</span>
                    <span class="font-mono font-bold text-blue-700">{{ buku.nomor_panggil || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400">Lokasi Rak:</span>
                    <span class="font-bold text-slate-700">{{ buku.lokasi_rak || '-' }}</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                <span class="font-black text-xs" :class="buku.jumlah_tersedia > 0 ? 'text-emerald-600' : 'text-rose-600'">
                  {{ buku.jumlah_tersedia }} / {{ buku.jumlah_eksemplar }} Tersedia
                </span>
                <div class="flex items-center gap-1">
                  <button @click="openModalEditBuku(buku)" class="p-1.5 text-slate-400 hover:text-blue-600 transition"><i class="bi bi-pencil-square"></i></button>
                  <button @click="openModalEksemplar(buku)" class="p-1.5 text-slate-400 hover:text-indigo-600 transition"><i class="bi bi-upc"></i></button>
                  <button @click="confirmDeleteBuku(buku.id)" class="p-1.5 text-slate-400 hover:text-rose-600 transition"><i class="bi bi-trash"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination Footer Buku (Grid Mode) -->
          <div v-if="bukuList?.total" class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span>Tampilkan</span>
              <select v-model="perPageBuku" @change="applySearch(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
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
                Menampilkan <span class="font-bold text-slate-800">{{ bukuList.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ bukuList.to || bukuList.total }}</span> dari <span class="font-bold text-slate-800">{{ bukuList.total }}</span> judul buku
              </span>
            </div>

            <!-- Smart Windowing Pagination Links -->
            <div v-if="bukuList.links && bukuList.links.length > 3" class="flex items-center gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(bukuList)" :key="i">
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

      <!-- TAB 2: EKSEMPLAR & BARCODE FISIK -->
      <div v-if="activeTab === 'eksemplar'" class="space-y-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="relative w-full md:w-80">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="bi bi-search"></i></span>
            <input v-model="searchEksemplarQuery" @keyup.enter="applyEksemplarSearch(1)" type="text" placeholder="Cari barcode, nomor induk, judul..." class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
          </div>

          <div class="flex items-center gap-2">
            <button @click="applyEksemplarSearch(1)" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1">
              <i class="bi bi-search"></i> Filter
            </button>
            <button @click="openModalCetakLabel(eksemplarList?.data)" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5">
              <i class="bi bi-printer"></i>
              <span>Cetak Stiker Barcode Halaman Ini</span>
            </button>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">Barcode Eksemplar</th>
                  <th class="py-3.5 px-3">No. Induk / Register</th>
                  <th class="py-3.5 px-3">Judul Buku Master</th>
                  <th class="py-3.5 px-3">Call Number Item</th>
                  <th class="py-3.5 px-3">Lokasi Rak</th>
                  <th class="py-3.5 px-3 text-center">Status Kondisi</th>
                  <th class="py-3.5 px-3 text-center">Tipe Akses</th>
                  <th class="py-3.5 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="eks in (eksemplarList?.data || [])" :key="eks.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 font-mono font-bold text-slate-800">
                    <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-700 border border-slate-200">{{ eks.barcode }}</span>
                  </td>
                  <td class="py-3 px-3 font-mono text-slate-600">{{ eks.no_induk || '-' }}</td>
                  <td class="py-3 px-3 font-semibold text-slate-800">{{ eks.buku?.judul_buku || '-' }}</td>
                  <td class="py-3 px-3 font-mono font-bold text-blue-700">{{ eks.nomor_panggil_item || eks.buku?.nomor_panggil || '-' }}</td>
                  <td class="py-3 px-3">{{ eks.lokasi_rak || '-' }}</td>
                  <td class="py-3 px-3 text-center">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-extrabold border"
                          :class="eks.status_kondisi === 'Tersedia' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (eks.status_kondisi === 'Dipinjam' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200')">
                      {{ eks.status_kondisi }}
                    </span>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="eks.tipe_koleksi === 'Sirkulasi' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'">
                      {{ eks.tipe_koleksi || 'Sirkulasi' }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button @click="openModalCetakLabel([eks])" class="p-1.5 text-slate-400 hover:text-blue-600 transition" title="Cetak Barcode Eksemplar Ini">
                        <i class="bi bi-printer"></i>
                      </button>
                      <button @click="deleteEksemplar(eks.id)" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Hapus Eksemplar">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!eksemplarList?.data?.length">
                  <td colspan="8" class="py-8 text-center text-slate-400">Belum ada eksemplar fisik terdaftar.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer Eksemplar -->
          <div v-if="eksemplarList?.total" class="px-4 py-3 bg-slate-50/50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span>Tampilkan</span>
              <select v-model="perPageEksemplar" @change="applyEksemplarSearch(1)" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="20">20</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span>baris per halaman</span>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <span class="whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-800">{{ eksemplarList.from || 1 }}</span> s.d. <span class="font-bold text-slate-800">{{ eksemplarList.to || eksemplarList.total }}</span> dari <span class="font-bold text-slate-800">{{ eksemplarList.total }}</span> eksemplar
              </span>
            </div>

            <!-- Smart Windowing Pagination Links -->
            <div v-if="eksemplarList.links && eksemplarList.links.length > 3" class="flex items-center gap-1 shrink-0 flex-wrap">
              <template v-for="(link, i) in getSmartPaginationLinks(eksemplarList)" :key="i">
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

      <!-- TAB 3: KLASIFIKASI DDC STANDAR (DDC 000 - 900) -->
      <div v-if="activeTab === 'ddc'" class="space-y-4">
        <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div>
            <h3 class="font-bold text-slate-800 text-sm">Klasifikasi Dewey Decimal Classification (DDC)</h3>
            <p class="text-xs text-slate-500 mt-0.5">Sistem pengelompokan subjek perpustakaan internasional dengan pemetaan label warna.</p>
          </div>
          <button @click="isModalDdcOpen = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-plus-circle-fill"></i> Tambah Sub-Klasifikasi DDC
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
          <div v-for="d in ddcList" :key="d.id" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-300 transition">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl text-white font-black flex items-center justify-center text-xs shadow-xs shrink-0" :style="{ backgroundColor: d.warna_label || '#3b82f6' }">
                {{ d.kode_ddc }}
              </div>
              <div>
                <h4 class="font-extrabold text-slate-800 text-xs">{{ d.nama_klasifikasi }}</h4>
                <div class="text-[11px] text-slate-400 mt-0.5">{{ d.deskripsi_ddc || 'Kategori Standar' }}</div>
              </div>
            </div>
            <button @click="deleteDdc(d.id)" class="p-1.5 text-slate-300 hover:text-rose-600 transition"><i class="bi bi-trash"></i></button>
          </div>
        </div>
      </div>

      <!-- TAB 4: MASTER LOKASI RAK -->
      <div v-if="activeTab === 'rak'" class="space-y-4">
        <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div>
            <h3 class="font-bold text-slate-800 text-sm">Master Lokasi Rak Perpustakaan</h3>
            <p class="text-xs text-slate-500 mt-0.5">Pemetaan lemari dan rak penyimpanan buku per lantai gedung.</p>
          </div>
          <button @click="isModalRakOpen = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-plus-circle-fill"></i> Tambah Rak Baru
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
          <div v-for="rak in rakList" :key="rak.id" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 font-black flex items-center justify-center text-xs shadow-2xs shrink-0">
                <i class="bi bi-grid-3x3-gap-fill text-base"></i>
              </div>
              <div>
                <div class="font-extrabold text-slate-800 text-xs">{{ rak.nama_rak }} ({{ rak.kode_rak }})</div>
                <div class="text-[11px] text-slate-500 mt-0.5">{{ rak.lantai_gedung || 'Lantai 1' }} • Kapasitas: {{ rak.kapasitas_buku }} Buku</div>
              </div>
            </div>
            <button @click="deleteRak(rak.id)" class="p-1.5 text-slate-300 hover:text-rose-600 transition"><i class="bi bi-trash"></i></button>
          </div>
        </div>
      </div>

      <!-- TAB 5: USULAN PENGADAAN BUKU -->
      <div v-if="activeTab === 'usulan'" class="space-y-4">
        <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div>
            <h3 class="font-bold text-slate-800 text-sm">Daftar Usulan Buku Pemustaka</h3>
            <p class="text-xs text-slate-500 mt-0.5">Permohonan pengadaan judul buku baru yang diajukan oleh siswa/guru.</p>
          </div>
          <button @click="isModalUsulanOpen = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-plus-circle-fill"></i> Kirim Usulan Judul
          </button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">Judul Buku Usulan</th>
                  <th class="py-3.5 px-3">Penulis & Penerbit</th>
                  <th class="py-3.5 px-3">Pengusul</th>
                  <th class="py-3.5 px-3">Alasan Rekomendasi</th>
                  <th class="py-3.5 px-3 text-center">Status</th>
                  <th class="py-3.5 px-4 text-center">Ubah Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="u in usulanList" :key="u.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 font-bold text-slate-800">{{ u.judul_buku }}</td>
                  <td class="py-3 px-3 text-slate-600">{{ u.pengarang || '-' }} / {{ u.penerbit || '-' }}</td>
                  <td class="py-3 px-3 font-semibold text-blue-700">{{ u.pengusul_nama }}</td>
                  <td class="py-3 px-3 text-slate-500">{{ u.alasan_usulan || '-' }}</td>
                  <td class="py-3 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border"
                          :class="u.status_usulan === 'Disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (u.status_usulan === 'Terbeli' ? 'bg-blue-50 text-blue-700 border-blue-200' : (u.status_usulan === 'Ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200'))">
                      {{ u.status_usulan }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <select :value="u.status_usulan" @change="updateStatusUsulan(u.id, $event.target.value)" class="text-[11px] py-1 px-2 border border-slate-200 rounded-lg bg-slate-50 font-medium">
                      <option value="Pending">Pending</option>
                      <option value="Disetujui">Disetujui</option>
                      <option value="Terbeli">Terbeli</option>
                      <option value="Ditolak">Ditolak</option>
                    </select>
                  </td>
                </tr>
                <tr v-if="!usulanList?.length">
                  <td colspan="6" class="py-8 text-center text-slate-400">Belum ada usulan pengadaan buku.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 6: MAJALAH & SERIAL BERKALA -->
      <div v-if="activeTab === 'serial'" class="space-y-4">
        <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div>
            <h3 class="font-bold text-slate-800 text-sm">Terbitan Serial Berkala (Kardeks Majalah & Jurnal)</h3>
            <p class="text-xs text-slate-500 mt-0.5">Monitoring penerimaan majalah, jurnal ilmiah, surat kabar harian, dan buletin sekolah.</p>
          </div>
          <button @click="isModalSerialOpen = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="bi bi-plus-circle-fill"></i> Tambah Serial Baru
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
          <div v-for="s in serialList" :key="s.id" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 font-black flex items-center justify-center text-xs shadow-2xs shrink-0">
                <i class="bi bi-newspaper text-base"></i>
              </div>
              <div>
                <div class="font-extrabold text-slate-800 text-xs">{{ s.nama_serial }}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">{{ s.jenis_serial }} • ISSN: {{ s.issn || '-' }} • {{ s.frekuensi_terbit }}</div>
              </div>
            </div>
            <button @click="deleteSerial(s.id)" class="p-1.5 text-slate-300 hover:text-rose-600 transition"><i class="bi bi-trash"></i></button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL POPUP: ENTRI / EDIT BUKU STANDAR MARC 21 & RDA          -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isModalBukuOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
              <h2 class="text-base font-black text-slate-800">{{ isEditBuku ? 'Edit Bibliografi Buku' : 'Entri Katalog Bibliografi Standar MARC 21 / RDA' }}</h2>
              <p class="text-xs text-slate-500">Metadata terstruktur perpustakaan berstandar Perpustakaan Nasional RI.</p>
            </div>
            <button @click="isModalBukuOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition"><i class="bi bi-x-lg"></i></button>
          </div>

          <form @submit.prevent="submitFormBuku" class="p-6 overflow-y-auto space-y-4 text-xs grow">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="font-bold text-slate-700 block mb-1">Judul Utama Buku (MARC 245$a) *</label>
                <input v-model="formBuku.judul_buku" type="text" required placeholder="Contoh: Algoritma & Pemrograman Berorientasi Objek" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Anak Judul / Sub-Judul (MARC 245$b)</label>
                <input v-model="formBuku.anak_judul" type="text" placeholder="Contoh: Pendekatan Praktis dengan Python 3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Pengarang Utama (MARC 100$a) *</label>
                <input v-model="formBuku.pengarang" type="text" required placeholder="Contoh: Maulana Habibullah" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Pengarang Tambahan / Editor (MARC 700)</label>
                <input v-model="formBuku.pengarang_tambahan" type="text" placeholder="Contoh: Dr. Budi Santoso, M.Kom." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Jenis Bahan Pustaka (RDA Carrier Type)</label>
                <select v-model="formBuku.jenis_bahan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 font-medium">
                  <option value="Buku Teks / Monograf">Buku Teks / Monograf</option>
                  <option value="Modul Pembelajaran">Modul Pembelajaran</option>
                  <option value="Karya Tulis / Skripsi">Karya Tulis / Skripsi</option>
                  <option value="Terbitan Berkala">Terbitan Berkala</option>
                  <option value="Braille / Khusus">Braille / Khusus</option>
                </select>
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Klasifikasi DDC (MARC 082) *</label>
                <input v-model="formBuku.nomor_klasifikasi_ddc" type="text" required placeholder="Contoh: 005.133" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Nomor Panggil (Call Number)</label>
                <input v-model="formBuku.nomor_panggil" type="text" placeholder="Otomatis jika kosong (cth: 005.133 MAU a)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 font-medium font-mono" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Penerbit (MARC 260$b)</label>
                <input v-model="formBuku.penerbit" type="text" placeholder="Contoh: Informatika Bandung" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Kota Terbit & Tahun</label>
                <div class="grid grid-cols-2 gap-2">
                  <input v-model="formBuku.kota_terbit" type="text" placeholder="Bandung" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-medium" />
                  <input v-model="formBuku.tahun_terbit" type="number" placeholder="2026" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-medium" />
                </div>
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">ISBN / EAN (MARC 020)</label>
                <input v-model="formBuku.isbn" type="text" placeholder="978-602-00-0000-0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Deskripsi Fisik / Kolasi (MARC 300)</label>
                <input v-model="formBuku.deskripsi_fisik" type="text" placeholder="xx, 350 hlm. : ilus. ; 24 cm." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>

              <div>
                <label class="font-bold text-slate-700 block mb-1">Lokasi Rak Default</label>
                <input v-model="formBuku.lokasi_rak" type="text" placeholder="Rak A1 Lantai 2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>

              <div v-if="!isEditBuku">
                <label class="font-bold text-slate-700 block mb-1">Jumlah Eksemplar Fisik Awal *</label>
                <input v-model="formBuku.jumlah_eksemplar" type="number" min="1" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>

              <div class="md:col-span-2">
                <label class="font-bold text-slate-700 block mb-1">Tajuk Subjek Topikal (MARC 650)</label>
                <input v-model="formBuku.subjek" type="text" placeholder="Pemrograman Komputer -- Python -- Pembelajaran" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>

              <div class="md:col-span-2">
                <label class="font-bold text-slate-700 block mb-1">Sinopsis & Ringkasan Isi</label>
                <textarea v-model="formBuku.sinopsis" rows="2" placeholder="Ringkasan abstrak buku..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium"></textarea>
              </div>

              <div class="flex items-center gap-4 md:col-span-2 pt-2">
                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                  <input type="checkbox" v-model="formBuku.status_opac" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4" />
                  <span>Publikasikan ke OPAC Daring</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                  <input type="checkbox" v-model="formBuku.is_ebook" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4" />
                  <span>Format E-Book Digital</span>
                </label>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
              <button type="button" @click="isModalBukuOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Batal</button>
              <button type="submit" :disabled="formBuku.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-xs">
                {{ formBuku.processing ? 'Menyimpan...' : (isEditBuku ? 'Simpan Perubahan' : 'Daftarkan Katalog') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ============================================================== -->
    <!-- MODAL POPUP: TAMBAH EKSEMPLAR FISIK                           -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isModalEksemplarOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-200">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
              <h2 class="text-base font-black text-slate-800">Tambah Eksemplar Fisik</h2>
              <p class="text-xs text-slate-500">Buku: <strong>{{ selectedBukuForEks?.judul_buku }}</strong></p>
            </div>
            <button @click="isModalEksemplarOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition"><i class="bi bi-x-lg"></i></button>
          </div>

          <form @submit.prevent="submitEksemplar" class="p-6 space-y-4 text-xs">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nomor Barcode Fisik *</label>
              <input v-model="formEksemplar.barcode" type="text" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono font-bold text-blue-700" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Nomor Induk / Register</label>
                <input v-model="formEksemplar.no_induk" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono" />
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Nomor Panggil Item</label>
                <input v-model="formEksemplar.nomor_panggil_item" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono font-bold" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Lokasi Rak</label>
                <input v-model="formEksemplar.lokasi_rak" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200" />
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Tipe Koleksi Akses</label>
                <select v-model="formEksemplar.tipe_koleksi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200">
                  <option value="Sirkulasi">Sirkulasi (Boleh Dipinjam)</option>
                  <option value="Referensi / Tandon">Referensi (Baca di Tempat)</option>
                  <option value="Cadangan / Khusus">Cadangan / Khusus</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Status Kondisi</label>
                <select v-model="formEksemplar.status_kondisi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200">
                  <option value="Tersedia">Tersedia</option>
                  <option value="Dipinjam">Dipinjam</option>
                  <option value="Rusak">Rusak</option>
                  <option value="Dalam Perbaikan">Dalam Perbaikan</option>
                </select>
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Sumber Perolehan</label>
                <input v-model="formEksemplar.sumber_perolehan" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200" />
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
              <button type="button" @click="isModalEksemplarOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Batal</button>
              <button type="submit" :disabled="formEksemplar.processing" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-xs">
                Simpan Eksemplar
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ============================================================== -->
    <!-- MODAL POPUP: CETAK LABEL PUNGGUNG & STIKER BARCODE             -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isModalCetakLabelOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
              <h2 class="text-base font-black text-slate-800">Preview Cetak Label Punggung & Stiker Barcode</h2>
              <p class="text-xs text-slate-500">Format standar INLISLite / Perpusnas RI untuk ditempel pada fisik buku.</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="printLabelWindow" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="bi bi-printer-fill"></i> Cetak Dokumen
              </button>
              <button @click="isModalCetakLabelOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>

          <div class="p-6 overflow-y-auto space-y-6 text-xs grow">
            <!-- Selector Tipe Cetak -->
            <div class="flex items-center gap-2 p-1.5 bg-slate-100 rounded-xl max-w-xs">
              <button @click="labelPrintType = 'spine'" class="px-3 py-1.5 rounded-lg font-bold text-xs grow transition" :class="labelPrintType === 'spine' ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500'">Label Punggung (Spine)</button>
              <button @click="labelPrintType = 'barcode'" class="px-3 py-1.5 rounded-lg font-bold text-xs grow transition" :class="labelPrintType === 'barcode' ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500'">Stiker Barcode</button>
              <button @click="labelPrintType = 'all'" class="px-3 py-1.5 rounded-lg font-bold text-xs grow transition" :class="labelPrintType === 'all' ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500'">Kombinasi</button>
            </div>

            <!-- Grid Preview Cetak -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-4 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
              <div v-for="(eks, idx) in selectedEksemplarForPrint" :key="idx" class="bg-white border-2 border-slate-800 rounded-lg p-3 text-center flex flex-col justify-between shadow-2xs h-44">
                <!-- Header Lembaga -->
                <div class="text-[9px] font-extrabold uppercase tracking-tight text-slate-700 border-b border-slate-300 pb-1">
                  PERPUSTAKAAN SEKOLAH
                </div>

                <!-- Call Number 3-Baris Standar -->
                <div class="font-mono font-black py-2 space-y-0.5 text-slate-900">
                  <div class="text-sm tracking-wider">{{ eks.nomor_panggil_item ? eks.nomor_panggil_item.split(' ')[0] : '000' }}</div>
                  <div class="text-xs uppercase">{{ eks.nomor_panggil_item ? (eks.nomor_panggil_item.split(' ')[1] || 'XXX') : 'MAU' }}</div>
                  <div class="text-xs lowercase">{{ eks.nomor_panggil_item ? (eks.nomor_panggil_item.split(' ')[2] || 'a') : 'p' }}</div>
                </div>

                <!-- Barcode Stiker -->
                <div v-if="labelPrintType !== 'spine'" class="border-t border-slate-300 pt-1">
                  <div class="font-mono font-bold text-[10px] text-slate-800 tracking-widest">{{ eks.barcode || 'BK-001' }}</div>
                  <div class="text-[8px] text-slate-400">{{ eks.no_induk || 'IND-2026' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Tambah Master Rak -->
    <Teleport to="body">
      <div v-if="isModalRakOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Tambah Master Lokasi Rak</h3>
          <form @submit.prevent="submitRak" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Kode Rak *</label>
              <input v-model="formRak.kode_rak" type="text" required placeholder="Contoh: RAK-01" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Rak *</label>
              <input v-model="formRak.nama_rak" type="text" required placeholder="Contoh: Rak Buku Sains & Matematika" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Lantai / Gedung</label>
                <input v-model="formRak.lantai_gedung" type="text" placeholder="Lantai 1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Kapasitas (Buku)</label>
                <input v-model="formRak.kapasitas_buku" type="number" placeholder="100" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
              </div>
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalRakOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition">Simpan Rak</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Tambah DDC -->
    <Teleport to="body">
      <div v-if="isModalDdcOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Tambah Sub-Klasifikasi DDC</h3>
          <form @submit.prevent="submitDdc" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Kode DDC *</label>
              <input v-model="formDdc.kode_ddc" type="text" required placeholder="Contoh: 005.133" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono font-bold" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Klasifikasi *</label>
              <input v-model="formDdc.nama_klasifikasi" type="text" required placeholder="Contoh: Pemrograman Python" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Warna Label</label>
              <input v-model="formDdc.warna_label" type="color" class="w-full h-10 p-1 rounded-xl border border-slate-200 cursor-pointer" />
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalDdcOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition">Simpan DDC</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Usulan -->
    <Teleport to="body">
      <div v-if="isModalUsulanOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Kirim Usulan Pengadaan Buku</h3>
          <form @submit.prevent="submitUsulan" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Judul Buku *</label>
              <input v-model="formUsulan.judul_buku" type="text" required placeholder="Judul buku yang diinginkan..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Penulis / Pengarang</label>
              <input v-model="formUsulan.pengarang" type="text" placeholder="Nama penulis..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Penerbit</label>
              <input v-model="formUsulan.penerbit" type="text" placeholder="Penerbit..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Pengusul *</label>
              <input v-model="formUsulan.pengusul_nama" type="text" required placeholder="Nama Anda (Siswa / Guru)..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Alasan Pengusulan</label>
              <textarea v-model="formUsulan.alasan_usulan" rows="2" placeholder="Alasan kenapa buku ini perlu dibeli..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium"></textarea>
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalUsulanOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition">Kirim Usulan</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Serial -->
    <Teleport to="body">
      <div v-if="isModalSerialOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-4">Tambah Terbitan Serial Berkala</h3>
          <form @submit.prevent="submitSerial" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Serial / Majalah *</label>
              <input v-model="formSerial.nama_serial" type="text" required placeholder="Contoh: Majalah Bobo, Tempo, Jurnal Ilmiah" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="font-bold text-slate-700 block mb-1">Jenis Serial</label>
                <select v-model="formSerial.jenis_serial" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200">
                  <option value="Majalah">Majalah</option>
                  <option value="Jurnal">Jurnal</option>
                  <option value="Surat Kabar">Surat Kabar</option>
                  <option value="Tabloid">Tabloid</option>
                </select>
              </div>
              <div>
                <label class="font-bold text-slate-700 block mb-1">Frekuensi</label>
                <select v-model="formSerial.frekuensi_terbit" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200">
                  <option value="Harian">Harian</option>
                  <option value="Mingguan">Mingguan</option>
                  <option value="Bulanan">Bulanan</option>
                  <option value="Tahunan">Tahunan</option>
                </select>
              </div>
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">ISSN</label>
              <input v-model="formSerial.issn" type="text" placeholder="XXXX-XXXX" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Edisi / Nomor</label>
              <input v-model="formSerial.edisi_nomor" type="text" placeholder="Vol. 12 No. 3 Sept 2026" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200" />
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalSerialOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition">Simpan Serial</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
