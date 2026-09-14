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
  }, {
    preserveState: true,
    replace: true,
  })
}

const selectDdc = (kode) => {
  selectedDdc.value = selectedDdc.value === kode ? '' : kode
  search()
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
            <button v-for="d in (ddcList || [])" :key="d.id" @click="selectDdc(d.kode_ddc)" class="px-2.5 py-1 rounded-full text-[10px] font-bold transition border"
                    :class="selectedDdc === d.kode_ddc ? 'bg-blue-500 text-white border-blue-400 shadow-xs' : 'bg-white/10 text-blue-200 border-white/10 hover:bg-white/20'">
              {{ d.kode_ddc }} {{ d.nama_klasifikasi }}
            </button>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="space-y-4">
        <!-- Filter Toolbar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
          <div class="flex items-center gap-2 font-bold text-slate-700">
            <i class="bi bi-collection-fill text-blue-600"></i>
            <span>Ditemukan: <strong class="text-blue-700">{{ bukuList?.total || bukuList?.data?.length || 0 }}</strong> Judul Koleksi</span>
          </div>

          <div class="flex items-center gap-2">
            <select v-model="selectedJenisBahan" @change="search" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 font-medium">
              <option value="">Semua Jenis Media</option>
              <option value="Buku Teks / Monograf">Buku Teks / Monograf</option>
              <option value="Modul Pembelajaran">Modul Pembelajaran</option>
              <option value="Karya Tulis / Skripsi">Karya Tulis / Skripsi</option>
              <option value="Terbitan Berkala">Terbitan Berkala</option>
            </select>
          </div>
        </div>

        <!-- Book Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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

        <div v-if="!bukuList?.data?.length" class="text-center py-16 bg-white rounded-3xl border border-slate-200/80 p-8">
          <i class="bi bi-search text-4xl text-slate-300 mb-2 block"></i>
          <h3 class="font-bold text-slate-700 text-sm">Tidak ditemukan pustaka yang cocok</h3>
          <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci pencarian yang lebih umum atau pilih klasifikasi DDC lain.</p>
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
  </AppLayout>
</template>
