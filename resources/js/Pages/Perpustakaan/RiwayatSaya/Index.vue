<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  pinjamanAktif: [Object, Array],
  riwayatSelesai: Object,
  reservasiSaya: [Object, Array],
  totalDenda: Number,
  isBebasPustaka: Boolean,
  userProfile: Object,
  pengaturan: Object,
  filters: Object,
})

const activeTab = ref('aktif') // 'aktif' | 'reservasi' | 'riwayat' | 'kta_digital'

// Per-page models
const perPageAktif = ref(Number(props.filters?.per_page_aktif) || 10)
const perPageRiwayat = ref(Number(props.filters?.per_page_riwayat) || 10)
const perPageReservasi = ref(Number(props.filters?.per_page_reservasi) || 10)

// Helper computed data arrays
const getPinjamanAktifData = computed(() => {
  if (Array.isArray(props.pinjamanAktif)) return props.pinjamanAktif
  return props.pinjamanAktif?.data || []
})

const getReservasiData = computed(() => {
  if (Array.isArray(props.reservasiSaya)) return props.reservasiSaya
  return props.reservasiSaya?.data || []
})

const getRiwayatData = computed(() => {
  return props.riwayatSelesai?.data || []
})

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

// -------------------------------------------------------------
// SMART WINDOWING PAGINATION HELPER
// -------------------------------------------------------------
const goToPage = (url) => {
  if (url) {
    router.visit(url, { preserveState: true, preserveScroll: true })
  }
}

const applyAktifPageSize = () => {
  router.get('/perpustakaan/riwayat-saya', {
    per_page_aktif: perPageAktif.value,
    per_page_riwayat: perPageRiwayat.value,
    per_page_reservasi: perPageReservasi.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const applyRiwayatPageSize = () => {
  router.get('/perpustakaan/riwayat-saya', {
    per_page_aktif: perPageAktif.value,
    per_page_riwayat: perPageRiwayat.value,
    per_page_reservasi: perPageReservasi.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const applyReservasiPageSize = () => {
  router.get('/perpustakaan/riwayat-saya', {
    per_page_aktif: perPageAktif.value,
    per_page_riwayat: perPageRiwayat.value,
    per_page_reservasi: perPageReservasi.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
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
      const newUrl = urlTemplate ? urlTemplate.replace(/p_[a-z]+=\d+/, `page=${pageNum}`) : `?page=${pageNum}`
      result.push({ url: newUrl, label: String(pageNum), active: pageNum === current })
    }
    lastPushedPage = pageNum
  }

  result.push(nextLink)
  return result
}

const perpanjangMandiri = (id) => {
  if (confirm('Perpanjang masa pinjam buku ini selama 7 hari?')) {
    router.post(`/perpustakaan/sirkulasi/perpanjang/${id}`, { hari: 7 }, { preserveScroll: true })
  }
}

const cancelBooking = (id) => {
  if (confirm('Batalkan reservasi buku ini?')) {
    router.post(`/perpustakaan/reservasi/${id}/cancel`, {}, { preserveScroll: true })
  }
}
</script>

<template>
  <AppLayout title="Riwayat Pustaka Saya">
    <div class="space-y-6">
      <!-- Header User Card -->
      <div class="bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-950 rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-3xl text-blue-300 shrink-0">
              <i class="bi bi-person-badge"></i>
            </div>
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-500/30 text-blue-200 border border-blue-400/30">
                  {{ userProfile?.role || 'Pemustaka' }}
                </span>
                <span class="text-xs text-blue-200 font-mono">ID: {{ userProfile?.username }}</span>
              </div>
              <h1 class="text-2xl font-black tracking-tight">{{ userProfile?.nama_lengkap }}</h1>
              <p class="text-xs text-slate-300 mt-0.5">Portal Layanan Mandiri Pemustaka &bull; {{ props.pengaturan?.nama_perpustakaan || 'Perpustakaan SINTA' }}</p>
            </div>
          </div>

          <!-- Status Bebas Pustaka Badge -->
          <div class="flex items-center gap-3">
            <div v-if="isBebasPustaka" class="p-3 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 backdrop-blur-md flex items-center gap-3">
              <i class="bi bi-shield-fill-check text-2xl text-emerald-400"></i>
              <div>
                <div class="text-xs font-bold text-emerald-300 uppercase">Status Bebas Pustaka</div>
                <div class="text-[11px] text-emerald-100">Clear &bull; Tidak ada tanggungan pinjaman/denda</div>
              </div>
            </div>
            <div v-else class="p-3 rounded-2xl bg-rose-500/20 border border-rose-400/30 backdrop-blur-md flex items-center gap-3">
              <i class="bi bi-exclamation-triangle-fill text-2xl text-rose-400"></i>
              <div>
                <div class="text-xs font-bold text-rose-300 uppercase">Ada Tanggungan</div>
                <div class="text-[11px] text-rose-100">
                  {{ pinjamanAktif?.total || getPinjamanAktifData.length || 0 }} Buku Dipinjam &bull; Denda: Rp {{ Number(totalDenda || 0).toLocaleString('id-ID') }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Bar 4 Kolom -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Sedang Dipinjam</span>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm"><i class="bi bi-book"></i></span>
          </div>
          <div class="text-xl font-black text-blue-600 mt-2">{{ pinjamanAktif?.total || getPinjamanAktifData.length || 0 }} Buku</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Buku di Tangan Anda</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Riwayat Selesai</span>
            <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm"><i class="bi bi-journal-check"></i></span>
          </div>
          <div class="text-xl font-black text-purple-700 mt-2">{{ riwayatSelesai?.total || getRiwayatData.length || 0 }} Kali</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Total Judul Pernah Dibaca</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Reservasi Antre</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm"><i class="bi bi-calendar-event"></i></span>
          </div>
          <div class="text-xl font-black text-emerald-600 mt-2">{{ reservasiSaya?.total || getReservasiData.length || 0 }} Judul</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Booking Daring</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">Tanggungan Denda</span>
            <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm"><i class="bi bi-cash-stack"></i></span>
          </div>
          <div class="text-lg font-black text-rose-600 mt-2">Rp {{ Number(totalDenda || 0).toLocaleString('id-ID') }}</div>
          <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Harap Selesaikan di Kasir</div>
        </div>
      </div>

      <!-- Modern Horizontal NavTabs Scroller (Pill Layout) -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <!-- Tombol Panah Kiri -->
          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-riwayatsaya')?.scrollBy({ left: -220, behavior: 'smooth' })"
                  title="Geser ke Kiri">
            <i class="bi bi-chevron-left"></i>
          </button>

          <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navtabs-riwayatsaya" role="tablist">
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'aktif' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'aktif'">
                  <i class="bi bi-arrow-repeat me-2 text-sm"></i> 1. Pinjaman Sedang Berjalan
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'reservasi' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'reservasi'">
                  <i class="bi bi-calendar2-range me-2 text-sm"></i> 2. Reservasi & Booking
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'riwayat' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'riwayat'">
                  <i class="bi bi-clock-history me-2 text-sm"></i> 3. Riwayat Pustaka Selesai
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center" 
                        :class="activeTab === 'kta_digital' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeTab = 'kta_digital'">
                  <i class="bi bi-person-vcard me-2 text-sm"></i> 4. Kartu Anggota Digital
                </button>
              </li>
            </ul>
          </div>

          <!-- Tombol Panah Kanan -->
          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5" 
                  onclick="document.getElementById('navtabs-riwayatsaya')?.scrollBy({ left: 220, behavior: 'smooth' })"
                  title="Geser ke Kanan">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- TAB 1: PINJAMAN AKTIF -->
      <div v-if="activeTab === 'aktif'" class="space-y-4">
        <!-- Toolbar Baris Per Halaman -->
        <div class="flex items-center justify-between bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs text-xs">
          <div class="font-bold text-slate-700">
            Total Peminjaman Aktif: <strong class="text-blue-600">{{ pinjamanAktif?.total || getPinjamanAktifData.length || 0 }}</strong> Judul
          </div>
          <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium">Tampilkan:</span>
            <select v-model="perPageAktif" @change="applyAktifPageSize" class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50 font-medium text-xs">
              <option :value="5">5 baris</option>
              <option :value="10">10 baris</option>
              <option :value="20">20 baris</option>
              <option :value="50">50 baris</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="item in getPinjamanAktifData" :key="item.id" class="bg-white rounded-3xl border border-slate-200/80 shadow-2xs p-5 flex flex-col justify-between hover:border-blue-300 transition">
            <div>
              <div class="flex items-start gap-3.5 mb-3">
                <div class="w-14 h-20 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-slate-400">
                  <img v-if="item.buku?.cover_url" :src="item.buku.cover_url" alt="Cover" class="w-full h-full object-cover" />
                  <i v-else class="bi bi-book text-2xl text-slate-400"></i>
                </div>
                <div>
                  <h3 class="font-black text-slate-800 text-xs line-clamp-2 leading-snug">{{ item.buku?.judul_buku || 'Judul Buku' }}</h3>
                  <div class="text-[11px] text-slate-500 mt-0.5">Penulis: {{ item.buku?.pengarang || '-' }}</div>
                  <div class="text-[10px] text-blue-700 font-mono mt-1">Barcode: {{ item.eksemplar?.barcode || '-' }}</div>
                </div>
              </div>

              <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1 text-[11px]">
                <div class="flex justify-between text-slate-600">
                  <span>Tgl Pinjam:</span>
                  <span class="font-bold">{{ formatDate(item.tanggal_pinjam) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                  <span>Jatuh Tempo:</span>
                  <span class="font-bold text-rose-600">{{ formatDate(item.tanggal_harus_kembali) }}</span>
                </div>
                <div v-if="item.jumlah_perpanjangan > 0" class="flex justify-between text-indigo-600 font-bold">
                  <span>Perpanjangan:</span>
                  <span>{{ item.jumlah_perpanjangan }}x</span>
                </div>
              </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
              <span class="text-[11px] text-slate-400 font-mono">{{ item.nomor_transaksi }}</span>
              <button @click="perpanjangMandiri(item.id)" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl font-bold text-xs transition flex items-center gap-1">
                <i class="bi bi-arrow-clockwise"></i> Perpanjang Mandiri
              </button>
            </div>
          </div>
        </div>

        <div v-if="!getPinjamanAktifData.length" class="text-center py-12 bg-white rounded-3xl border border-slate-200/80 p-8 shadow-2xs">
          <i class="bi bi-check2-circle text-4xl text-emerald-400 mb-2 block"></i>
          <h3 class="font-bold text-slate-700 text-sm">Tidak ada pinjaman buku aktif</h3>
          <p class="text-xs text-slate-400 mt-1">Jelajahi portal OPAC untuk menemukan pustaka baru yang menarik.</p>
        </div>

        <!-- Pagination Footer Pinjaman Aktif -->
        <div v-if="pinjamanAktif?.links && getPinjamanAktifData.length" class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
          <div class="text-slate-500 font-medium">
            Menampilkan <strong class="text-slate-800">{{ pinjamanAktif.from || 0 }}</strong> s.d. <strong class="text-slate-800">{{ pinjamanAktif.to || 0 }}</strong> dari <strong class="text-blue-700">{{ pinjamanAktif.total || 0 }}</strong> pinjaman aktif
          </div>

          <div class="flex items-center gap-1.5 flex-wrap">
            <template v-for="(link, index) in getSmartPaginationLinks(pinjamanAktif)" :key="index">
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

      <!-- TAB 2: RESERVASI / BOOKING -->
      <div v-if="activeTab === 'reservasi'" class="space-y-4">
        <!-- Toolbar Baris Per Halaman -->
        <div class="flex items-center justify-between bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs text-xs">
          <div class="font-bold text-slate-700">
            Total Reservasi: <strong class="text-blue-600">{{ reservasiSaya?.total || getReservasiData.length || 0 }}</strong> Judul
          </div>
          <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium">Tampilkan:</span>
            <select v-model="perPageReservasi" @change="applyReservasiPageSize" class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50 font-medium text-xs">
              <option :value="5">5 baris</option>
              <option :value="10">10 baris</option>
              <option :value="20">20 baris</option>
              <option :value="50">50 baris</option>
            </select>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">Judul Buku Dipesan</th>
                  <th class="py-3.5 px-3">Tanggal Booking</th>
                  <th class="py-3.5 px-3">Batas Waktu Pengambilan</th>
                  <th class="py-3.5 px-3 text-center">Status Antrean</th>
                  <th class="py-3.5 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="res in getReservasiData" :key="res.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 font-bold text-slate-800">{{ res.buku?.judul_buku || '-' }}</td>
                  <td class="py-3 px-3 text-slate-600">{{ res.tanggal_reservasi }}</td>
                  <td class="py-3 px-3 text-slate-600 font-semibold">{{ res.tanggal_berakhir }}</td>
                  <td class="py-3 px-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border"
                          :class="res.status_reservasi === 'Menunggu' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
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
                <tr v-if="!getReservasiData.length">
                  <td colspan="5" class="py-8 text-center text-slate-400">Tidak ada antrean reservasi buku.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination Footer Reservasi -->
        <div v-if="reservasiSaya?.links && getReservasiData.length" class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
          <div class="text-slate-500 font-medium">
            Menampilkan <strong class="text-slate-800">{{ reservasiSaya.from || 0 }}</strong> s.d. <strong class="text-slate-800">{{ reservasiSaya.to || 0 }}</strong> dari <strong class="text-blue-700">{{ reservasiSaya.total || 0 }}</strong> antrean reservasi
          </div>

          <div class="flex items-center gap-1.5 flex-wrap">
            <template v-for="(link, index) in getSmartPaginationLinks(reservasiSaya)" :key="index">
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

      <!-- TAB 3: RIWAYAT SELESAI -->
      <div v-if="activeTab === 'riwayat'" class="space-y-4">
        <!-- Toolbar Baris Per Halaman -->
        <div class="flex items-center justify-between bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs text-xs">
          <div class="font-bold text-slate-700">
            Total Riwayat Selesai: <strong class="text-purple-700">{{ riwayatSelesai?.total || getRiwayatData.length || 0 }}</strong> Transaksi
          </div>
          <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium">Tampilkan:</span>
            <select v-model="perPageRiwayat" @change="applyRiwayatPageSize" class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50 font-medium text-xs">
              <option :value="5">5 baris</option>
              <option :value="10">10 baris</option>
              <option :value="20">20 baris</option>
              <option :value="50">50 baris</option>
            </select>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                  <th class="py-3.5 px-4">No. Transaksi</th>
                  <th class="py-3.5 px-3">Judul Buku</th>
                  <th class="py-3.5 px-3">Tanggal Pinjam</th>
                  <th class="py-3.5 px-3">Tanggal Kembali</th>
                  <th class="py-3.5 px-3">Kondisi Fisik</th>
                  <th class="py-3.5 px-3 text-center">Status Denda</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in getRiwayatData" :key="item.id" class="hover:bg-blue-50/30 transition">
                  <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ item.nomor_transaksi }}</td>
                  <td class="py-3 px-3 font-semibold text-slate-800">{{ item.buku?.judul_buku || '-' }}</td>
                  <td class="py-3 px-3 text-slate-600">{{ formatDate(item.tanggal_pinjam) }}</td>
                  <td class="py-3 px-3 text-slate-600">{{ formatDate(item.tanggal_kembali_aktual) }}</td>
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="item.kondisi_kembali === 'Baik' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                      {{ item.kondisi_kembali || 'Baik' }}
                    </span>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border"
                          :class="item.status_denda === 'Lunas' || item.status_denda === 'Nihil' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                      {{ item.status_denda }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!getRiwayatData.length">
                  <td colspan="6" class="py-8 text-center text-slate-400">Belum ada riwayat peminjaman yang selesai.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination Footer Riwayat Selesai -->
        <div v-if="riwayatSelesai?.links && getRiwayatData.length" class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
          <div class="text-slate-500 font-medium">
            Menampilkan <strong class="text-slate-800">{{ riwayatSelesai.from || 0 }}</strong> s.d. <strong class="text-slate-800">{{ riwayatSelesai.to || 0 }}</strong> dari <strong class="text-purple-700">{{ riwayatSelesai.total || 0 }}</strong> riwayat pustaka
          </div>

          <div class="flex items-center gap-1.5 flex-wrap">
            <template v-for="(link, index) in getSmartPaginationLinks(riwayatSelesai)" :key="index">
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

      <!-- TAB 4: KARTU ANGGOTA DIGITAL (DIGITAL ID CARD) -->
      <div v-if="activeTab === 'kta_digital'" class="space-y-4">
        <div class="max-w-md mx-auto border-2 border-slate-800 rounded-3xl p-6 bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white shadow-2xl space-y-4 relative overflow-hidden">
          <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>

          <div class="flex items-center justify-between border-b border-white/20 pb-3">
            <div>
              <h3 class="font-black text-xs uppercase tracking-wider text-blue-200">KARTU PEMUSTAKA DIGITAL</h3>
              <p class="text-[9px] text-slate-300">{{ props.pengaturan?.nama_perpustakaan || 'SINTA Digital Library' }}</p>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase bg-blue-500/30 text-blue-200 border border-blue-400/30">
              {{ userProfile?.role || 'Siswa' }}
            </span>
          </div>

          <div class="flex items-center gap-4 py-2">
            <div class="w-16 h-20 rounded-2xl bg-white/20 border border-white/30 overflow-hidden shrink-0 flex items-center justify-center text-white/50 text-3xl font-black shadow-inner">
              <i class="bi bi-person-fill"></i>
            </div>
            <div>
              <h2 class="text-base font-black text-white">{{ userProfile?.nama_lengkap }}</h2>
              <div class="text-xs text-blue-200 font-mono mt-0.5">ID: {{ userProfile?.username }}</div>
              <div class="text-[11px] text-slate-300 mt-1">Status: <strong class="text-emerald-400">Aktif Terdaftar</strong></div>
            </div>
          </div>

          <div class="border-t border-white/20 pt-3 flex items-center justify-between font-mono text-[10px] text-blue-300">
            <div>BARCODE KTA: {{ userProfile?.username }}</div>
            <div class="text-slate-400">PERPUSTAKAAN SINTA</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
