<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  bukuList: Object,
  ddcList: Array,
  pengaturan: Object,
  tenants: Array,
  isSuperAdmin: Boolean,
  activeTenantId: String,
  filters: Object,
})

const searchQuery = ref(props.filters?.q || '')
const selectedDdc = ref(props.filters?.ddc || '')
const selectedJenisBahan = ref(props.filters?.jenis_bahan || '')
const selectedTenantId = ref(props.filters?.tenant_id || '')
const perPage = ref(Number(props.filters?.per_page) || 12)
const viewMode = ref('grid') // 'grid' | 'table'

const getSelectedTenantName = () => {
  if (!selectedTenantId.value) return 'Semua Sekolah (Agregat Global)'
  const found = props.tenants?.find(t => t.id === selectedTenantId.value)
  return found ? `${found.nama_sekolah} (${found.npsn})` : 'Sekolah Terpilih'
}

const applyTenantFilter = () => {
  router.get('/perpustakaan/opac', {
    q: searchQuery.value || undefined,
    ddc: selectedDdc.value || undefined,
    jenis_bahan: selectedJenisBahan.value || undefined,
    tenant_id: selectedTenantId.value || undefined,
    per_page: perPage.value !== 12 ? perPage.value : undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const search = () => {
  router.get('/perpustakaan/opac', {
    q: searchQuery.value || undefined,
    ddc: selectedDdc.value || undefined,
    jenis_bahan: selectedJenisBahan.value || undefined,
    tenant_id: selectedTenantId.value || undefined,
    per_page: perPage.value !== 12 ? perPage.value : undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}

const selectDdc = (kode) => {
  selectedDdc.value = selectedDdc.value === kode ? '' : kode
  search()
}

const uniqueDdcList = computed(() => {
  if (!props.ddcList) return []
  const seen = new Set()
  const result = []
  for (const d of props.ddcList) {
    if (!seen.has(d.kode_ddc)) {
      seen.add(d.kode_ddc)
      result.push(d)
    }
  }
  return result.slice(0, 10)
})

// -------------------------------------------------------------
// SMART WINDOWING PAGINATION HELPER
// -------------------------------------------------------------
const goToPage = (url) => {
  if (url) {
    router.visit(url, { preserveState: true, preserveScroll: true })
  }
}

const getSmartPaginationLinks = (paginator) => {
  if (!paginator || !paginator.links) return []
  const rawLinks = paginator.links
  const current = paginator.current_page || 1
  const last = paginator.last_page || 1

  const prevLink = rawLinks[0]
  const nextLink = rawLinks[rawLinks.length - 1]

  if (last <= 7) {
    return rawLinks
  }

  const result = [prevLink]
  const pagesToShow = new Set([1, last, current - 1, current, current + 1])
  if (current <= 3) {
    pagesToShow.add(2)
    pagesToShow.add(3)
    pagesToShow.add(4)
  }
  if (current >= last - 2) {
    pagesToShow.add(last - 1)
    pagesToShow.add(last - 2)
    pagesToShow.add(last - 3)
  }

  let lastPushedPage = 0
  const sortedPages = Array.from(pagesToShow).filter(p => p >= 1 && p <= last).sort((a, b) => a - b)

  for (const pageNum of sortedPages) {
    if (lastPushedPage > 0 && pageNum - lastPushedPage > 1) {
      result.push({ url: null, label: '...', active: false })
    }
    const matchingLink = rawLinks.find(l => l.label == pageNum)
    if (matchingLink) {
      result.push(matchingLink)
    } else {
      const urlTemplate = prevLink.url || nextLink.url || ''
      const newUrl = urlTemplate ? urlTemplate.replace(/page=\d+/, `page=${pageNum}`) : `?page=${pageNum}`
      result.push({ url: newUrl, label: String(pageNum), active: pageNum === current })
    }
    lastPushedPage = pageNum
  }

  result.push(nextLink)
  return result
}

// -------------------------------------------------------------
// DETAIL MODAL & CITATION GENERATOR
// -------------------------------------------------------------
const isModalDetailOpen = ref(false)
const selectedBuku = ref(null)
const citationFormat = ref('apa') // 'apa' | 'mla' | 'harvard' | 'chicago'
const copiedNotification = ref(false)

const openDetail = (buku) => {
  selectedBuku.value = buku
  isModalDetailOpen.value = true
}

const getCitationText = (buku, format) => {
  if (!buku) return ''
  const author = buku.pengarang || 'Anonim'
  const year = buku.tahun_terbit || new Date().getFullYear()
  const title = buku.judul_buku || 'Judul Buku'
  const city = buku.kota_terbit || 'Jakarta'
  const publisher = buku.penerbit || 'Penerbit Perpustakaan'

  if (format === 'apa') {
    return `${author}. (${year}). ${title}. ${city}: ${publisher}.`
  } else if (format === 'mla') {
    return `${author}. "${title}." ${publisher}, ${year}.`
  } else if (format === 'harvard') {
    return `${author}, ${year}. ${title}. ${city}: ${publisher}.`
  } else if (format === 'chicago') {
    return `${author}. ${title}. ${city}: ${publisher}, ${year}.`
  }
  return `${author} (${year}). ${title}.`
}

const copyCitation = () => {
  const text = getCitationText(selectedBuku.value, citationFormat.value)
  navigator.clipboard.writeText(text)
  copiedNotification.value = true
  setTimeout(() => {
    copiedNotification.value = false
  }, 2000)
}

// -------------------------------------------------------------
// RESERVASI / BOOKING KOLEKSI ONLINE
// -------------------------------------------------------------
const isModalBookingOpen = ref(false)
const formBooking = useForm({
  buku_id: '',
  nama_peminjam: '',
  nomor_identitas: '',
})

const openBooking = (buku) => {
  formBooking.buku_id = buku.id
  isModalBookingOpen.value = true
}

const submitBooking = () => {
  formBooking.post('/perpustakaan/reservasi', {
    onSuccess: () => {
      isModalBookingOpen.value = false
      formBooking.reset()
    }
  })
}

// -------------------------------------------------------------
// E-BOOK DIGITAL FLIPBOOK READER DENGAN DYNAMIC WATERMARK
// -------------------------------------------------------------
const isModalEbookOpen = ref(false)
const ebookZoom = ref(100)
const ebookPage = ref(1)
const ebookTotalPages = ref(24)

const openEbookReader = (buku) => {
  selectedBuku.value = buku
  ebookPage.value = 1
  ebookZoom.value = 100
  isModalEbookOpen.value = true
}

const prevEbookPage = () => {
  if (ebookPage.value > 1) ebookPage.value--
}

const nextEbookPage = () => {
  if (ebookPage.value < ebookTotalPages.value) ebookPage.value++
}
</script>

<template>
  <AppLayout title="OPAC - Online Public Access Catalog">
    <div class="space-y-6">
      <!-- Hero Search Header -->
      <div class="relative bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-950 rounded-3xl p-8 md:p-12 text-white shadow-xl overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl mx-auto text-center space-y-4">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold text-blue-200">
            <i class="bi bi-search-heart text-blue-400"></i> Katalog Akses Publik Daring (OPAC)
          </div>
          <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">
            Temukan Pengetahuan di <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-cyan-200">{{ props.pengaturan?.nama_perpustakaan || 'Perpustakaan Digital' }}</span>
          </h1>
          <p class="text-xs md:text-sm text-slate-300">
            Penelusuran cerdas berbasis klasifikasi DDC standar internasional, salin sitasi karya ilmiah (APA/MLA), dan reservasi koleksi secara online.
          </p>

          <!-- Big Search Bar -->
          <form @submit.prevent="search" class="pt-3 flex items-center max-w-2xl mx-auto gap-2">
            <div class="relative w-full">
              <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-base"></i>
              <input v-model="searchQuery" type="text" placeholder="Ketikkan judul buku, pengarang, penerbit, topik subjek, atau ISBN..." class="w-full pl-11 pr-4 py-3.5 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl text-white placeholder-slate-400 text-xs font-medium focus:ring-2 focus:ring-blue-400 focus:bg-white/20 focus:outline-none" />
            </div>
            <button type="submit" class="px-6 py-3.5 bg-blue-500 hover:bg-blue-600 text-white rounded-2xl font-black text-xs shadow-lg transition shrink-0">
              Cari Pustaka
            </button>
          </form>

          <!-- DDC Quick Categories Badges -->
          <div class="flex flex-wrap items-center justify-center gap-1.5 pt-2">
            <button type="button" @click="selectDdc('')" class="px-3 py-1 rounded-full text-[10px] font-bold transition border"
                    :class="!selectedDdc ? 'bg-blue-500 text-white border-blue-400 shadow-xs' : 'bg-white/10 text-blue-200 border-white/10 hover:bg-white/20'">
              <i class="bi bi-grid-fill me-1"></i> Semua Koleksi
            </button>
            <button v-for="d in uniqueDdcList" :key="d.kode_ddc" @click="selectDdc(d.kode_ddc)" class="px-2.5 py-1 rounded-full text-[10px] font-bold transition border"
                    :class="selectedDdc === d.kode_ddc ? 'bg-blue-500 text-white border-blue-400 shadow-xs' : 'bg-white/10 text-blue-200 border-white/10 hover:bg-white/20'"
                    :title="d.nama_klasifikasi">
              {{ d.kode_ddc }} {{ d.nama_klasifikasi }}
            </button>
          </div>
        </div>
      </div>

      <!-- Main Content Container -->
      <div class="space-y-4">
        <!-- Filter Toolbar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
          <div class="flex items-center gap-2 font-bold text-slate-700">
            <i class="bi bi-collection-fill text-blue-600"></i>
            <span>Ditemukan: <strong class="text-blue-700">{{ bukuList?.total || bukuList?.data?.length || 0 }}</strong> Judul Koleksi</span>
            <span v-if="selectedDdc" class="ms-1 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-extrabold border border-blue-100">
              Filter DDC: {{ selectedDdc }}
            </span>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Super Admin Tenant -->
            <div v-if="isSuperAdmin && (tenants || []).length > 0" class="flex items-center gap-1.5">
              <select v-model="selectedTenantId" @change="applyTenantFilter" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 font-medium text-xs">
                <option value="">Semua Sekolah (Agregat Global)</option>
                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.nama_sekolah }}</option>
              </select>
            </div>

            <!-- Filter Media -->
            <select v-model="selectedJenisBahan" @change="search" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 font-medium text-xs">
              <option value="">Semua Jenis Media</option>
              <option value="Buku Teks / Monograf">Buku Teks / Monograf</option>
              <option value="Modul Pembelajaran">Modul Pembelajaran</option>
              <option value="Karya Tulis / Skripsi">Karya Tulis / Skripsi</option>
              <option value="Terbitan Berkala">Terbitan Berkala</option>
            </select>

            <!-- Per Page Selector -->
            <select v-model="perPage" @change="search" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 font-medium text-xs">
              <option :value="8">8 per hal</option>
              <option :value="12">12 per hal</option>
              <option :value="24">24 per hal</option>
              <option :value="48">48 per hal</option>
              <option :value="96">96 per hal</option>
            </select>

            <!-- View Mode Switch -->
            <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
              <button @click="viewMode = 'grid'" class="px-2.5 py-1 rounded-lg text-xs font-bold transition" :class="viewMode === 'grid' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-500 hover:text-slate-800'">
                <i class="bi bi-grid-fill"></i>
              </button>
              <button @click="viewMode = 'table'" class="px-2.5 py-1 rounded-lg text-xs font-bold transition" :class="viewMode === 'table' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-500 hover:text-slate-800'">
                <i class="bi bi-list-ul"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- MODE 1: BOOK CARDS GRID -->
        <div v-if="viewMode === 'grid' && (bukuList?.data || []).length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="buku in (bukuList?.data || [])" :key="buku.id" class="bg-white rounded-3xl border border-slate-200/80 shadow-2xs p-4 flex flex-col justify-between hover:shadow-lg hover:border-blue-300 transition duration-200 group">
            <div>
              <div class="flex gap-3 mb-3">
                <div class="w-16 h-24 rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-slate-400 shadow-2xs group-hover:scale-105 transition duration-200">
                  <img v-if="buku.cover_url" :src="buku.cover_url" alt="Cover" class="w-full h-full object-cover" />
                  <i v-else class="bi bi-book text-2xl text-slate-400"></i>
                </div>
                <div class="grow">
                  <span class="inline-block px-2 py-0.5 rounded text-[9px] font-extrabold bg-blue-50 text-blue-700 border border-blue-100 mb-1">
                    DDC {{ buku.nomor_klasifikasi_ddc || '000' }}
                  </span>
                  <h3 class="font-black text-slate-800 text-xs line-clamp-2 leading-snug group-hover:text-blue-600 transition">{{ buku.judul_buku }}</h3>
                  <div class="text-[11px] text-slate-500 mt-1">Penulis: {{ buku.pengarang }}</div>
                </div>
              </div>

              <!-- Location & Call Number Box -->
              <div class="p-2.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1 text-[11px] text-slate-600">
                <div class="flex justify-between">
                  <span class="text-slate-400">No. Panggil:</span>
                  <span class="font-mono font-bold text-blue-700">{{ buku.nomor_panggil || '-' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400">Lokasi Rak:</span>
                  <span class="font-bold text-slate-700">{{ buku.lokasi_rak || '-' }}</span>
                </div>
              </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
              <span class="font-black text-xs" :class="buku.jumlah_tersedia > 0 ? 'text-emerald-600' : 'text-rose-600'">
                {{ buku.jumlah_tersedia }} / {{ buku.jumlah_eksemplar }} Tersedia
              </span>

              <div class="flex items-center gap-1.5">
                <button @click="openEbookReader(buku)" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-[10px] font-black transition flex items-center gap-1" title="Buka Pembaca E-Book Digital">
                  <i class="bi bi-file-earmark-pdf-fill"></i> E-Book
                </button>
                <button v-if="buku.jumlah_tersedia <= 0" @click="openBooking(buku)" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-[10px] font-black transition" title="Pesan / Booking Judul Ini">
                  Booking
                </button>
                <button @click="openDetail(buku)" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition shadow-2xs">
                  Detail
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- MODE 2: TABLE VIEW -->
        <div v-else-if="viewMode === 'table' && (bukuList?.data || []).length > 0" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3 px-4 w-12 text-center">Cover</th>
                  <th class="py-3 px-3">Judul Koleksi & Metadata</th>
                  <th class="py-3 px-3">Pengarang & Penerbit</th>
                  <th class="py-3 px-3">DDC & Rak</th>
                  <th class="py-3 px-3 text-center">Ketersediaan</th>
                  <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="buku in (bukuList?.data || [])" :key="buku.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-2.5 px-4 text-center">
                    <div class="w-10 h-14 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden mx-auto flex items-center justify-center text-slate-400">
                      <img v-if="buku.cover_url" :src="buku.cover_url" alt="Cover" class="w-full h-full object-cover" />
                      <i v-else class="bi bi-book text-sm"></i>
                    </div>
                  </td>
                  <td class="py-2.5 px-3">
                    <div class="font-bold text-slate-800 text-xs hover:text-blue-600 cursor-pointer" @click="openDetail(buku)">{{ buku.judul_buku }}</div>
                    <div class="text-[11px] text-slate-400 font-mono">ISBN: {{ buku.isbn || '-' }} &bull; Call: {{ buku.nomor_panggil || '-' }}</div>
                  </td>
                  <td class="py-2.5 px-3 text-slate-600">
                    <div class="font-semibold">{{ buku.pengarang }}</div>
                    <div class="text-[11px] text-slate-400">{{ buku.penerbit }} ({{ buku.tahun_terbit }})</div>
                  </td>
                  <td class="py-2.5 px-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">DDC {{ buku.nomor_klasifikasi_ddc || '000' }}</span>
                    <div class="text-[11px] text-slate-500 mt-0.5">Rak: {{ buku.lokasi_rak || '-' }}</div>
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-black" :class="buku.jumlah_tersedia > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                      {{ buku.jumlah_tersedia }} / {{ buku.jumlah_eksemplar }}
                    </span>
                  </td>
                  <td class="py-2.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-1">
                      <button @click="openEbookReader(buku)" class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs transition" title="Buka E-Book">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                      </button>
                      <button @click="openDetail(buku)" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition shadow-2xs">
                        Detail
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- EMPTY STATE -->
        <div v-if="!bukuList?.data?.length" class="text-center py-16 bg-white rounded-3xl border border-slate-200/80 p-8 shadow-2xs">
          <i class="bi bi-search text-4xl text-slate-300 mb-2 block"></i>
          <h3 class="font-bold text-slate-700 text-sm">Tidak ditemukan pustaka yang cocok</h3>
          <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci pencarian yang lebih umum atau pilih klasifikasi DDC lain.</p>
        </div>

        <!-- SMART WINDOWING PAGINATION FOOTER -->
        <div v-if="bukuList?.data?.length" class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
          <div class="text-slate-500 font-medium">
            Menampilkan <strong class="text-slate-800">{{ bukuList.from || 0 }}</strong> s.d. <strong class="text-slate-800">{{ bukuList.to || 0 }}</strong> dari <strong class="text-blue-700">{{ bukuList.total || 0 }}</strong> koleksi buku
          </div>

          <div class="flex items-center gap-1.5 flex-wrap">
            <template v-for="(link, index) in getSmartPaginationLinks(bukuList)" :key="index">
              <span v-if="link.label === '...'" class="px-2.5 py-1 text-slate-400 font-bold select-none text-xs">...</span>
              <button v-else-if="link.url"
                      type="button"
                      @click="goToPage(link.url)"
                      class="px-3 py-1.5 rounded-xl border text-xs font-bold transition flex items-center justify-center min-w-[34px] shadow-2xs"
                      :class="link.active ? 'bg-blue-600 border-blue-600 text-white shadow-xs' : 'bg-white border-slate-200 text-slate-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200'">
                <i v-if="link.label.includes('Previous') || link.label.includes('&laquo;') || link.label.includes('chevron-left')" class="bi bi-chevron-left"></i>
                <i v-else-if="link.label.includes('Next') || link.label.includes('&raquo;') || link.label.includes('chevron-right')" class="bi bi-chevron-right"></i>
                <span v-else>{{ link.label }}</span>
              </button>
              <span v-else
                    class="px-3 py-1.5 rounded-xl border border-slate-100 bg-slate-50 text-slate-300 text-xs font-bold flex items-center justify-center min-w-[34px]">
                <i v-if="link.label.includes('Previous') || link.label.includes('&laquo;') || link.label.includes('chevron-left')" class="bi bi-chevron-left"></i>
                <i v-else-if="link.label.includes('Next') || link.label.includes('&raquo;') || link.label.includes('chevron-right')" class="bi bi-chevron-right"></i>
                <span v-else>{{ link.label }}</span>
              </span>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL POPUP: DETAIL BIBLIOGRAFI & SITASI ILMIAH               -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isModalDetailOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200 text-xs">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
              <h2 class="text-base font-black text-slate-800">Detail Bibliografi Koleksi Pustaka</h2>
              <p class="text-xs text-slate-500">Standar Pengkatalogan Internasional MARC 21 / RDA.</p>
            </div>
            <button @click="isModalDetailOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition"><i class="bi bi-x-lg"></i></button>
          </div>

          <div class="p-6 overflow-y-auto space-y-5 grow">
            <!-- Header Buku -->
            <div class="flex items-start gap-4">
              <div class="w-20 h-28 rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-slate-400 shadow-md">
                <img v-if="selectedBuku?.cover_url" :src="selectedBuku.cover_url" alt="Cover" class="w-full h-full object-cover" />
                <i v-else class="bi bi-book text-3xl text-slate-400"></i>
              </div>
              <div class="space-y-1">
                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-100">
                  DDC {{ selectedBuku?.nomor_klasifikasi_ddc || '000' }} • {{ selectedBuku?.jenis_bahan || 'Buku Teks' }}
                </span>
                <h3 class="font-black text-slate-900 text-sm leading-snug">{{ selectedBuku?.judul_buku }}</h3>
                <div v-if="selectedBuku?.anak_judul" class="text-slate-600 italic">{{ selectedBuku.anak_judul }}</div>
                <div class="text-slate-600">Penulis: <strong>{{ selectedBuku?.pengarang }}</strong> <span v-if="selectedBuku?.pengarang_tambahan">({{ selectedBuku.pengarang_tambahan }})</span></div>
                <div class="font-mono text-slate-500 text-[10px]">ISBN: {{ selectedBuku?.isbn || '-' }} • Call No: <strong class="text-blue-700">{{ selectedBuku?.nomor_panggil || '-' }}</strong></div>
              </div>
            </div>

            <!-- Action Button Baca Digital -->
            <div v-if="selectedBuku?.is_ebook" class="flex items-center justify-between p-3 bg-emerald-50 border border-emerald-200 rounded-2xl">
              <div class="flex items-center gap-2">
                <i class="bi bi-journal-richtext text-emerald-600 text-lg"></i>
                <span class="font-bold text-emerald-900">Tersedia Versi Digital (E-Book)</span>
              </div>
              <button @click="openEbookReader(selectedBuku)" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold flex items-center gap-1.5 shadow-2xs transition">
                <i class="bi bi-book-half"></i> Buka Pembaca E-Book
              </button>
            </div>

            <!-- Rincian Metadata Tabel -->
            <div class="grid grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
              <div>
                <span class="text-slate-400 block text-[10px]">Penerbit & Tahun:</span>
                <span class="font-bold text-slate-800">{{ selectedBuku?.penerbit || '-' }} ({{ selectedBuku?.tahun_terbit }})</span>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px]">Kota Terbit & Edisi:</span>
                <span class="font-bold text-slate-800">{{ selectedBuku?.kota_terbit || '-' }} • {{ selectedBuku?.edisi || 'Cet. 1' }}</span>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px]">Deskripsi Fisik (Kolasi):</span>
                <span class="font-bold text-slate-800">{{ selectedBuku?.deskripsi_fisik || (selectedBuku?.halaman + ' hlm.') }}</span>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px]">Lokasi Rak Fisik:</span>
                <span class="font-bold text-slate-800">{{ selectedBuku?.lokasi_rak || '-' }}</span>
              </div>
            </div>

            <!-- Sinopsis -->
            <div v-if="selectedBuku?.sinopsis">
              <h4 class="font-bold text-slate-700 mb-1">Sinopsis & Ringkasan:</h4>
              <p class="text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-2xl border border-slate-100">{{ selectedBuku.sinopsis }}</p>
            </div>

            <!-- Sitasi Ilmiah Box -->
            <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 space-y-2">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <i class="bi bi-quote text-blue-700 font-black text-lg"></i>
                  <span class="font-extrabold text-slate-800">Sitasi Karya Ilmiah:</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <button @click="citationFormat = 'apa'" class="px-2 py-0.5 rounded text-[10px] font-bold" :class="citationFormat === 'apa' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600'">APA</button>
                  <button @click="citationFormat = 'mla'" class="px-2 py-0.5 rounded text-[10px] font-bold" :class="citationFormat === 'mla' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600'">MLA</button>
                  <button @click="citationFormat = 'harvard'" class="px-2 py-0.5 rounded text-[10px] font-bold" :class="citationFormat === 'harvard' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600'">Harvard</button>
                </div>
              </div>

              <div class="p-2.5 bg-white rounded-xl border border-blue-100 font-mono text-[11px] text-slate-700 select-all">
                {{ getCitationText(selectedBuku, citationFormat) }}
              </div>

              <div class="flex justify-end">
                <button @click="copyCitation" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-[11px] flex items-center gap-1 transition">
                  <i class="bi" :class="copiedNotification ? 'bi-check-lg' : 'bi-clipboard'"></i>
                  <span>{{ copiedNotification ? 'Tersalin!' : 'Salin Sitasi' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Booking Online -->
    <Teleport to="body">
      <div v-if="isModalBookingOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 text-xs">
          <h3 class="text-base font-black text-slate-800 mb-1">Booking / Reservasi Pustaka</h3>
          <p class="text-xs text-slate-500 mb-4">Pesan judul buku yang sedang dipinjam pemustaka lain.</p>

          <form @submit.prevent="submitBooking" class="space-y-3.5">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nama Lengkap *</label>
              <input v-model="formBooking.nama_peminjam" type="text" required placeholder="Nama Anda..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-medium" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Nomor Identitas (NISN / NIP)</label>
              <input v-model="formBooking.nomor_identitas" type="text" placeholder="NISN atau NIP..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono" />
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
              <button type="button" @click="isModalBookingOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold">Pesan Sekarang</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal E-Book Flipbook Reader dengan Dynamic Watermark -->
    <Teleport to="body">
      <div v-if="isModalEbookOpen" class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/90 backdrop-blur-md animate-in fade-in duration-200">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl w-full max-w-5xl h-[92vh] flex flex-col overflow-hidden text-slate-100">
          <!-- Reader Top Toolbar -->
          <div class="px-4 py-3 bg-slate-950 border-b border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center text-sm font-bold">
                <i class="bi bi-book"></i>
              </div>
              <div>
                <h3 class="text-xs font-bold text-white truncate max-w-xs sm:max-w-md">{{ selectedBuku?.judul_buku }}</h3>
                <p class="text-3xs text-slate-400">Pembaca Digital E-Book &bull; Mode Aman</p>
              </div>
            </div>

            <!-- Controls: Zoom & Page Navigation -->
            <div class="flex items-center gap-2 text-xs">
              <div class="flex items-center bg-slate-800 rounded-xl px-2 py-1 gap-1.5 border border-slate-700">
                <button @click="prevEbookPage" :disabled="ebookPage <= 1" class="w-6 h-6 rounded text-slate-300 hover:text-white disabled:opacity-40"><i class="bi bi-chevron-left"></i></button>
                <span class="font-mono text-2xs px-1 text-emerald-400">Hal {{ ebookPage }} / {{ ebookTotalPages }}</span>
                <button @click="nextEbookPage" :disabled="ebookPage >= ebookTotalPages" class="w-6 h-6 rounded text-slate-300 hover:text-white disabled:opacity-40"><i class="bi bi-chevron-right"></i></button>
              </div>

              <div class="hidden sm:flex items-center bg-slate-800 rounded-xl px-2 py-1 gap-1 border border-slate-700 text-2xs font-mono">
                <button @click="ebookZoom = Math.max(75, ebookZoom - 15)" class="px-1.5 hover:text-white">-</button>
                <span>{{ ebookZoom }}%</span>
                <button @click="ebookZoom = Math.min(150, ebookZoom + 15)" class="px-1.5 hover:text-white">+</button>
              </div>

              <button @click="isModalEbookOpen = false" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-rose-600 text-slate-300 hover:text-white flex items-center justify-center transition">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>

          <!-- Flipbook Reading Canvas with Dynamic Watermark Overlay -->
          <div class="flex-1 bg-slate-950 p-4 overflow-auto flex items-center justify-center relative select-none">
            
            <!-- Dynamic Anti-Leak Watermark Grid -->
            <div class="absolute inset-0 pointer-events-none z-20 flex flex-wrap items-center justify-around opacity-15 overflow-hidden font-mono text-2xs text-slate-400 rotate-[-25deg] leading-relaxed p-8">
              <div v-for="w in 24" :key="w" class="p-4">
                {{ $page.props.auth?.user?.nama_lengkap || 'Pemustaka SINTA' }} &bull; SINTA Digital Library
              </div>
            </div>

            <!-- Mock Page Sheet Viewer -->
            <div class="bg-white text-slate-900 rounded-xl shadow-2xl p-8 sm:p-12 transition-all max-w-2xl w-full min-h-[500px] border border-slate-200 flex flex-col justify-between"
                 :style="{ transform: `scale(${ebookZoom / 100})`, transformOrigin: 'center top' }">
              
              <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3 text-2xs text-slate-400 font-mono">
                  <span>{{ selectedBuku?.judul_buku }}</span>
                  <span>Bab I &bull; Halaman {{ ebookPage }}</span>
                </div>

                <div class="space-y-3 text-xs leading-relaxed text-slate-700">
                  <h4 class="text-base font-black text-slate-900">{{ ebookPage === 1 ? 'PENGANTAR DAN PENDAHULUAN' : `BAGIAN ${ebookPage}: PENDALAMAN MATERI LITERASI` }}</h4>
                  <p>
                    Koleksi buku digital ini diterbitkan secara resmi melalui sistem otomasi perpustakaan sekolah terintegrasi. Pemustaka dapat mempelajari materi esensial, referensi kurikulum nasional, dan pengetahuan ilmiah secara mandiri.
                  </p>
                  <p v-if="selectedBuku?.sinopsis">
                    {{ selectedBuku.sinopsis }}
                  </p>
                  <p>
                    Setiap peminjaman dan pembacaan daring dilindungi oleh hak cipta dan regulasi literasi digital sekolah. Dilarang mendistribusikan atau menggandakan naskah tanpa izin pustakawan.
                  </p>
                </div>
              </div>

              <div class="pt-6 border-t border-slate-200 flex items-center justify-between text-2xs text-slate-400 font-mono">
                <span>Perpustakaan Digital SINTA</span>
                <span>{{ ebookPage }} / {{ ebookTotalPages }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
