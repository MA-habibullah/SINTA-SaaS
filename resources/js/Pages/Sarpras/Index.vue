<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js'
import axios from 'axios'

// === ZERO-SSR: Props shell kosong ===
defineProps({ initialData: { type: Object, default: null } })

// =============================================
// STATE UTAMA
// =============================================
const activeTab   = ref('barang-modal')
const isLoading   = ref(false)
const toast       = ref({ show: false, message: '', type: 'success' })

// Tab: Barang Modal
const asetList     = ref({ data: [], total: 0 })
const kategoriList = ref([])
const ruanganList  = ref([])
const filterKategori = ref('')
const filterKondisi  = ref('')
const filterSearch   = ref('')

// Tab: KIR
const bangunanList      = ref([])
const selectedRuanganId = ref('')
const inventarisRuangan = ref([])

// Tab: BHP
const bhpList          = ref({ data: [], total: 0 })
const stokMinimumCount = ref(0)
const filterBhpSearch  = ref('')
const filterBhpKategori = ref('')
const showHanyaMinimum  = ref(false)

// Tab: Peminjaman
const peminjamanList   = ref({ data: [], total: 0 })
const filterPeminjaman = ref('')

// Tab: Pemeliharaan
const pemeliharaanList = ref({ data: [], total: 0 })
const filterPemeliharaan = ref('')
const totalBiayaPemeliharaan = ref(0)
const asetOptions      = ref([])

// Modal State
const isModalOpen    = ref(false)
const modalType      = ref('') // 'tambah-aset', 'tambah-bhp', 'tambah-peminjaman', 'tambah-pemeliharaan', 'stok-bhp', 'approve-peminjaman'
const modalData      = ref({})
const isSaving       = ref(false)

// Granular RBAC NavTabs Definition
const allTabs = [
  { key: 'barang-modal', icon: 'bi-box-seam', label: 'Barang Modal' },
  { key: 'kir', icon: 'bi-building', label: 'Kartu Inventaris Ruangan' },
  { key: 'bhp', icon: 'bi-bag-check', label: 'Barang Habis Pakai' },
  { key: 'peminjaman', icon: 'bi-arrow-left-right', label: 'Peminjaman Fasilitas' },
  { key: 'pemeliharaan', icon: 'bi-tools', label: 'Pemeliharaan & Service' },
]

const allowedTabs = ref([])
const availableTabs = computed(() => {
  if (!allowedTabs.value || allowedTabs.value.length === 0) return allTabs
  return allTabs.filter(t => allowedTabs.value.includes(t.key))
})

useMemorySecurity([asetList, bhpList, peminjamanList, pemeliharaanList, inventarisRuangan])

// =============================================
// HELPERS
// =============================================
function showToast(msg, type = 'success') {
  toast.value = { show: true, message: msg, type }
  setTimeout(() => { toast.value.show = false }, 4000)
}
function formatRupiah(val) {
  if (!val || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}
function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

// =============================================
// ASYNC DATA LOADING
// =============================================
async function loadTabData(tab = activeTab.value) {
  isLoading.value = true
  activeTab.value = tab
  try {
    const params = { async: 1, tab }
    if (tab === 'barang-modal') {
      if (filterSearch.value)   params.search   = filterSearch.value
      if (filterKategori.value) params.kategori  = filterKategori.value
      if (filterKondisi.value)  params.kondisi   = filterKondisi.value
    } else if (tab === 'kir') {
      if (selectedRuanganId.value) params.ruangan_id = selectedRuanganId.value
    } else if (tab === 'bhp') {
      if (filterBhpSearch.value)   params.search  = filterBhpSearch.value
      if (filterBhpKategori.value) params.kategori = filterBhpKategori.value
      if (showHanyaMinimum.value)  params.stok_minimum = 1
    } else if (tab === 'peminjaman') {
      if (filterPeminjaman.value) params.search = filterPeminjaman.value
    } else if (tab === 'pemeliharaan') {
      if (filterPemeliharaan.value) params.search = filterPemeliharaan.value
    }

    const res = await axios.get('/sarpras', { params })
    if (res.data?.success) {
      if (res.data.allowed_tabs && Array.isArray(res.data.allowed_tabs)) {
        allowedTabs.value = res.data.allowed_tabs
        if (!res.data.allowed_tabs.includes(activeTab.value) && res.data.allowed_tabs.length > 0) {
          activeTab.value = res.data.allowed_tabs[0]
          return loadTabData(activeTab.value)
        }
      }

      const d = res.data.data
      if (tab === 'barang-modal') {
        asetList.value     = d.asetList     || { data: [] }
        kategoriList.value = d.kategoriList || []
        ruanganList.value  = d.ruanganList  || []
      } else if (tab === 'kir') {
        bangunanList.value      = d.bangunanList      || []
        inventarisRuangan.value = d.inventarisRuangan || []
      } else if (tab === 'bhp') {
        bhpList.value          = d.bhpList          || { data: [] }
        stokMinimumCount.value = d.stokMinimumCount || 0
      } else if (tab === 'peminjaman') {
        peminjamanList.value = d.peminjamanList || { data: [] }
        asetOptions.value    = d.asetOptions    || []
      } else if (tab === 'pemeliharaan') {
        pemeliharaanList.value       = d.pemeliharaanList || { data: [] }
        asetOptions.value            = d.asetOptions      || []
        totalBiayaPemeliharaan.value = d.totalBiaya       || 0
      }
    }
  } catch (err) {
    showToast('Gagal memuat data sarpras.', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => { loadTabData(activeTab.value) })

// =============================================
// COMPUTED OPTIONS
// =============================================
const kategoriOptions = computed(() => [
  { id: '', label: 'Semua Kategori' },
  ...kategoriList.value.map(k => ({ id: k.nama_kategori, label: k.nama_kategori }))
])
const kondisiOptions = [
  { id: '', label: 'Semua Kondisi' },
  { id: 'Baik', label: 'Baik' },
  { id: 'Rusak Ringan', label: 'Rusak Ringan' },
  { id: 'Rusak Berat', label: 'Rusak Berat' },
]
const statusPeminjamanOptions = [
  { id: '', label: 'Semua Status' },
  { id: 'Menunggu Persetujuan', label: 'Menunggu Persetujuan' },
  { id: 'Disetujui', label: 'Disetujui' },
  { id: 'Dipinjam', label: 'Dipinjam' },
  { id: 'Dikembalikan', label: 'Dikembalikan' },
  { id: 'Ditolak', label: 'Ditolak' },
]
const ruanganOptions = computed(() => [
  { id: '', label: 'Pilih Ruangan untuk KIR...' },
  ...ruanganList.value.map(r => ({ id: r.id, label: r.nama_ruangan, subLabel: r.kode_ruangan }))
])
const asetSelectOptions = computed(() =>
  asetOptions.value.map(a => ({ id: a.id, label: a.nama_barang, subLabel: `${a.kode_aset} | ${a.lokasi_ruangan}` }))
)

// =============================================
// MODAL FORM HANDLERS
// =============================================
function openModal(type, data = {}) {
  modalType.value = type
  modalData.value = { ...data }
  isModalOpen.value = true
}

async function saveModal() {
  isSaving.value = true
  try {
    let res
    const d = modalData.value
    if (modalType.value === 'tambah-aset') {
      res = d.id ? await axios.put(`/sarpras/barang-modal/${d.id}`, d) : await axios.post('/sarpras/barang-modal', d)
    } else if (modalType.value === 'tambah-bhp') {
      res = await axios.post('/sarpras/bhp', d)
    } else if (modalType.value === 'stok-bhp') {
      res = await axios.patch(`/sarpras/bhp/${d.id}/stok`, { tipe: d.tipe, jumlah: d.jumlah, catatan: d.catatan })
    } else if (modalType.value === 'tambah-peminjaman') {
      res = await axios.post('/sarpras/peminjaman', d)
    } else if (modalType.value === 'approve-peminjaman') {
      res = await axios.patch(`/sarpras/peminjaman/${d.id}/approve`, { aksi: d.aksi, catatan: d.catatan })
    } else if (modalType.value === 'kembali-peminjaman') {
      res = await axios.patch(`/sarpras/peminjaman/${d.id}/kembali`, { catatan_pengembalian: d.catatan })
    } else if (modalType.value === 'tambah-pemeliharaan') {
      res = await axios.post('/sarpras/pemeliharaan', d)
    } else if (modalType.value === 'update-pemeliharaan') {
      res = await axios.patch(`/sarpras/pemeliharaan/${d.id}/status`, d)
    }

    if (res?.data?.success) {
      showToast(res.data.message || 'Berhasil disimpan.')
      isModalOpen.value = false
      loadTabData(activeTab.value)
    }
  } catch (err) {
    const errors = err.response?.data?.errors
    const msg = errors ? Object.values(errors).flat().join(', ') : (err.response?.data?.message || 'Gagal menyimpan data.')
    showToast(msg, 'error')
  } finally {
    isSaving.value = false
  }
}

async function deleteAset(id, nama) {
  if (!confirm(`Hapus aset "${nama}"?`)) return
  try {
    const res = await axios.delete(`/sarpras/barang-modal/${id}`)
    if (res.data?.success) { showToast('Aset berhasil dihapus.'); loadTabData('barang-modal') }
  } catch (err) { showToast(err.response?.data?.message || 'Gagal menghapus.', 'error') }
}
</script>

<template>
  <AppLayout title="Sarana & Prasarana — Inventaris Sekolah">
    <Head title="Sarana & Prasarana — Inventaris Sekolah" />

    <!-- Toast -->
    <transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="toast.show" class="fixed top-5 right-5 z-50 max-w-md bg-white rounded-2xl shadow-2xl border p-4 flex items-center gap-3" :class="toast.type === 'error' ? 'border-rose-200 bg-rose-50/90 text-rose-900' : 'border-emerald-200 bg-emerald-50/90 text-emerald-900'">
        <i class="bi text-xl shrink-0" :class="toast.type === 'error' ? 'bi-exclamation-octagon-fill text-rose-600' : 'bi-check-circle-fill text-emerald-600'"></i>
        <div class="text-sm font-medium grow">{{ toast.message }}</div>
        <button type="button" class="text-slate-400 hover:text-slate-700 text-xl leading-none" @click="toast.show = false">&times;</button>
      </div>
    </transition>

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-800 tracking-tight">Sarana & Prasarana</h1>
          <p class="text-xs text-slate-500 mt-1">Inventaris aset tetap, stok barang habis pakai, peminjaman fasilitas, dan pemeliharaan aset sekolah.</p>
        </div>
        <div class="flex items-center gap-2">
          <button v-if="activeTab === 'barang-modal'" type="button" @click="openModal('tambah-aset', { satuan:'Unit', kondisi:'Baik', jumlah:1 })"
            class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-sm transition flex items-center gap-2 active:scale-95">
            <i class="bi bi-plus-lg"></i> Tambah Aset
          </button>
          <button v-if="activeTab === 'bhp'" type="button" @click="openModal('tambah-bhp', { satuan:'Pcs', stok_saat_ini:0, stok_minimum:5 })"
            class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-sm transition flex items-center gap-2 active:scale-95">
            <i class="bi bi-plus-lg"></i> Tambah BHP
          </button>
          <button v-if="activeTab === 'peminjaman'" type="button" @click="openModal('tambah-peminjaman', { items: [{ aset_id: '', jumlah_dipinjam: 1 }] })"
            class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-sm transition flex items-center gap-2 active:scale-95">
            <i class="bi bi-box-arrow-right"></i> Ajukan Peminjaman
          </button>
          <button v-if="activeTab === 'pemeliharaan'" type="button" @click="openModal('tambah-pemeliharaan', { status:'Dilaporkan', tanggal_laporan: new Date().toISOString().split('T')[0] })"
            class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-sm transition flex items-center gap-2 active:scale-95">
            <i class="bi bi-tools"></i> Lapor Kerusakan
          </button>
        </div>
      </div>

      <!-- Stat Summary Cards -->
      <div v-if="activeTab === 'barang-modal'" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
          <div class="text-[10px] font-bold text-slate-500 uppercase">Total Aset</div>
          <div class="text-2xl font-black text-slate-800 mt-1">{{ asetList?.total || 0 }}</div>
          <div class="text-[11px] text-slate-400 mt-0.5">Unit barang modal</div>
        </div>
        <div class="bg-emerald-50 rounded-2xl border border-emerald-200 p-4 shadow-sm">
          <div class="text-[10px] font-bold text-emerald-600 uppercase">Kondisi Baik</div>
          <div class="text-2xl font-black text-emerald-700 mt-1">{{ asetList?.data?.filter(a => a.kondisi === 'Baik').length || 0 }}</div>
          <div class="text-[11px] text-emerald-400 mt-0.5">Dari halaman ini</div>
        </div>
        <div class="bg-amber-50 rounded-2xl border border-amber-200 p-4 shadow-sm">
          <div class="text-[10px] font-bold text-amber-600 uppercase">Rusak Ringan</div>
          <div class="text-2xl font-black text-amber-700 mt-1">{{ asetList?.data?.filter(a => a.kondisi === 'Rusak Ringan').length || 0 }}</div>
          <div class="text-[11px] text-amber-400 mt-0.5">Perlu perbaikan</div>
        </div>
        <div class="bg-rose-50 rounded-2xl border border-rose-200 p-4 shadow-sm">
          <div class="text-[10px] font-bold text-rose-600 uppercase">Rusak Berat</div>
          <div class="text-2xl font-black text-rose-700 mt-1">{{ asetList?.data?.filter(a => a.kondisi === 'Rusak Berat').length || 0 }}</div>
          <div class="text-[11px] text-rose-400 mt-0.5">Perlu penghapusan</div>
        </div>
      </div>

      <div v-if="activeTab === 'bhp' && stokMinimumCount > 0"
        class="flex items-center gap-3 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs">
        <i class="bi bi-exclamation-triangle-fill text-amber-600 text-lg shrink-0"></i>
        <div>
          <div class="font-black text-amber-800">Peringatan Stok Minimum!</div>
          <div class="text-amber-600 mt-0.5">{{ stokMinimumCount }} barang habis pakai telah mencapai atau di bawah batas minimum stok. Segera lakukan pengadaan ulang.</div>
        </div>
        <button type="button" class="ml-auto px-3 py-1.5 rounded-xl bg-amber-600 text-white text-xs font-bold hover:bg-amber-500 transition shrink-0" @click="showHanyaMinimum = !showHanyaMinimum; loadTabData('bhp')">
          {{ showHanyaMinimum ? 'Tampilkan Semua' : 'Lihat Kritis' }}
        </button>
      </div>

      <!-- Tab Navigation -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex overflow-x-auto border-b border-slate-200">
          <button v-for="tab in availableTabs" :key="tab.key"
            type="button"
            class="flex items-center gap-2 px-5 py-3.5 text-xs font-bold whitespace-nowrap border-b-2 transition"
            :class="activeTab === tab.key ? 'border-blue-600 text-blue-700 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
            @click="loadTabData(tab.key)"
          >
            <i :class="['bi', tab.icon]"></i>
            {{ tab.label }}
            <span v-if="tab.key === 'bhp' && stokMinimumCount > 0" class="ml-1 w-5 h-5 rounded-full bg-amber-500 text-white text-[9px] font-black flex items-center justify-center">!</span>
          </button>
        </div>

        <!-- Loading Skeleton -->
        <div v-if="isLoading" class="py-16 text-center text-slate-400 text-xs">
          <div class="inline-block animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
          <p>Memuat data...</p>
        </div>

        <!-- ===== TAB 1: BARANG MODAL ===== -->
        <div v-else-if="activeTab === 'barang-modal'" class="p-5 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Cari Barang / Kode Aset</label>
              <input type="text" v-model="filterSearch" @keyup.enter="loadTabData('barang-modal')" placeholder="Nama barang / kode..."
                class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kategori</label>
              <SearchableSelect v-model="filterKategori" :options="kategoriOptions" placeholder="Semua Kategori..." @change="loadTabData('barang-modal')" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kondisi</label>
              <SearchableSelect v-model="filterKondisi" :options="kondisiOptions" placeholder="Semua Kondisi..." @change="loadTabData('barang-modal')" />
            </div>
          </div>

          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-xs text-left">
              <thead><tr class="bg-slate-50 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3 px-4">Kode Aset</th>
                <th class="py-3 px-4">Nama Barang & Kategori</th>
                <th class="py-3 px-4">Lokasi Ruangan</th>
                <th class="py-3 px-4 text-center">Jml</th>
                <th class="py-3 px-4 text-center">Kondisi</th>
                <th class="py-3 px-4">QR Token</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr></thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-if="!asetList?.data?.length"><td colspan="7" class="py-12 text-center text-slate-400">Belum ada barang modal yang terdaftar.</td></tr>
                <tr v-for="a in asetList.data" :key="a.id" class="hover:bg-slate-50/80 transition">
                  <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ a.kode_aset }}</td>
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-900">{{ a.nama_barang }}</div>
                    <div class="text-[10px] text-slate-400">{{ a.kategori }} · {{ a.jumlah }} {{ a.satuan }}</div>
                  </td>
                  <td class="py-3 px-4 text-slate-600">{{ a.lokasi_ruangan }}</td>
                  <td class="py-3 px-4 text-center font-bold text-slate-800">{{ a.jumlah }}</td>
                  <td class="py-3 px-4 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="{ 'bg-emerald-100 text-emerald-700': a.kondisi === 'Baik', 'bg-amber-100 text-amber-700': a.kondisi === 'Rusak Ringan', 'bg-rose-100 text-rose-700': a.kondisi === 'Rusak Berat' }">
                      {{ a.kondisi }}
                    </span>
                  </td>
                  <td class="py-3 px-4 font-mono text-blue-600 text-[11px] font-bold">{{ a.qr_code_token }}</td>
                  <td class="py-3 px-4 text-center">
                    <div class="flex justify-center gap-1">
                      <button type="button" class="p-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs" title="Edit" @click="openModal('tambah-aset', { ...a })"><i class="bi bi-pencil"></i></button>
                      <button type="button" class="p-1.5 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 text-xs" title="Lapor Kerusakan" @click="openModal('tambah-pemeliharaan', { aset_id: a.id, status: 'Dilaporkan', tanggal_laporan: new Date().toISOString().split('T')[0] })"><i class="bi bi-tools"></i></button>
                      <button type="button" class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs" title="Hapus" @click="deleteAset(a.id, a.nama_barang)"><i class="bi bi-trash"></i></button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== TAB 2: KIR ===== -->
        <div v-else-if="activeTab === 'kir'" class="p-5 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Daftar Bangunan & Ruangan (Sidebar) -->
            <div class="space-y-3">
              <h3 class="text-xs font-black text-slate-800 flex items-center gap-2"><i class="bi bi-building text-blue-600"></i> Bangunan & Ruangan</h3>
              <div v-for="bangunan in bangunanList" :key="bangunan.id" class="space-y-1">
                <div class="font-bold text-[11px] text-slate-700 bg-slate-100 rounded-lg px-3 py-2">
                  <i class="bi bi-building-fill me-1 text-blue-600"></i>{{ bangunan.nama_bangunan }}
                </div>
                <button v-for="ruangan in bangunan.ruangans" :key="ruangan.id"
                  type="button"
                  class="w-full text-left px-3 py-2 rounded-lg text-[11px] transition"
                  :class="selectedRuanganId === ruangan.id ? 'bg-blue-600 text-white' : 'hover:bg-slate-100 text-slate-600'"
                  @click="selectedRuanganId = ruangan.id; loadTabData('kir')">
                  <div class="font-bold">{{ ruangan.nama_ruangan }}</div>
                  <div class="text-[9px] opacity-70">{{ ruangan.total_barang || 0 }} barang</div>
                </button>
              </div>
            </div>

            <!-- Inventaris per Ruangan -->
            <div class="md:col-span-3">
              <div v-if="!selectedRuanganId" class="py-16 text-center text-slate-400 text-xs">
                <i class="bi bi-building text-3xl mb-2 block"></i>
                <p class="font-bold">Pilih ruangan di sebelah kiri</p>
                <p>untuk melihat Kartu Inventaris Ruangan (KIR)</p>
              </div>
              <div v-else-if="!inventarisRuangan.length" class="py-16 text-center text-slate-400 text-xs">
                <i class="bi bi-box text-3xl mb-2 block"></i>
                <p class="font-bold">Belum ada barang di ruangan ini.</p>
              </div>
              <div v-else>
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-sm font-black text-slate-900">Kartu Inventaris Ruangan (KIR)</h3>
                  <span class="text-xs text-slate-500">{{ inventarisRuangan.length }} barang terdaftar</span>
                </div>
                <table class="w-full text-xs border-collapse border border-slate-200">
                  <thead><tr class="bg-slate-50 text-slate-500 uppercase font-bold text-[11px] border-b">
                    <th class="py-2.5 px-3 border border-slate-200">No</th>
                    <th class="py-2.5 px-3 border border-slate-200">Kode</th>
                    <th class="py-2.5 px-3 border border-slate-200">Nama Barang</th>
                    <th class="py-2.5 px-3 border border-slate-200">Kategori</th>
                    <th class="py-2.5 px-3 border border-slate-200 text-center">Jml</th>
                    <th class="py-2.5 px-3 border border-slate-200 text-center">Kondisi</th>
                    <th class="py-2.5 px-3 border border-slate-200">Sumber Dana</th>
                  </tr></thead>
                  <tbody>
                    <tr v-for="(a, idx) in inventarisRuangan" :key="a.id" class="border-b border-slate-100">
                      <td class="py-2.5 px-3 border border-slate-200 text-slate-400">{{ idx + 1 }}</td>
                      <td class="py-2.5 px-3 border border-slate-200 font-mono text-blue-700 font-bold">{{ a.kode_aset }}</td>
                      <td class="py-2.5 px-3 border border-slate-200 font-bold text-slate-900">{{ a.nama_barang }}</td>
                      <td class="py-2.5 px-3 border border-slate-200 text-slate-600">{{ a.kategori }}</td>
                      <td class="py-2.5 px-3 border border-slate-200 text-center font-bold">{{ a.jumlah }} {{ a.satuan }}</td>
                      <td class="py-2.5 px-3 border border-slate-200 text-center">
                        <span class="px-2 py-0.5 rounded text-[9px] font-bold" :class="{ 'bg-emerald-100 text-emerald-700': a.kondisi === 'Baik', 'bg-amber-100 text-amber-700': a.kondisi === 'Rusak Ringan', 'bg-rose-100 text-rose-700': a.kondisi === 'Rusak Berat' }">{{ a.kondisi }}</span>
                      </td>
                      <td class="py-2.5 px-3 border border-slate-200 text-slate-500">{{ a.sumber_dana || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB 3: BHP ===== -->
        <div v-else-if="activeTab === 'bhp'" class="p-5 space-y-4">
          <div class="grid grid-cols-2 gap-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Cari Barang</label>
              <input type="text" v-model="filterBhpSearch" @keyup.enter="loadTabData('bhp')" placeholder="Nama / kode BHP..."
                class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-amber-500" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kategori</label>
              <SearchableSelect v-model="filterBhpKategori"
                :options="[{ id:'', label:'Semua' }, { id:'ATK', label:'ATK' }, { id:'Alat Kebersihan', label:'Alat Kebersihan' }, { id:'Bahan Lab', label:'Bahan Lab' }, { id:'Tinta/Toner', label:'Tinta/Toner' }, { id:'Lainnya', label:'Lainnya' }]"
                placeholder="Semua Kategori..." @change="loadTabData('bhp')" />
            </div>
          </div>

          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-xs text-left">
              <thead><tr class="bg-slate-50 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3 px-4">Kode & Nama Barang</th>
                <th class="py-3 px-4">Kategori</th>
                <th class="py-3 px-4 text-center">Stok Saat Ini</th>
                <th class="py-3 px-4 text-center">Stok Min</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-right">Harga/Satuan</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr></thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-if="!bhpList?.data?.length"><td colspan="7" class="py-12 text-center text-slate-400">Belum ada barang habis pakai yang terdaftar.</td></tr>
                <tr v-for="b in bhpList.data" :key="b.id" class="hover:bg-slate-50/80 transition">
                  <td class="py-3 px-4">
                    <div class="font-mono text-[11px] text-blue-700 font-bold">{{ b.kode_bhp }}</div>
                    <div class="font-bold text-slate-900">{{ b.nama_barang }}</div>
                  </td>
                  <td class="py-3 px-4 text-slate-600">{{ b.kategori }}</td>
                  <td class="py-3 px-4 text-center">
                    <span class="font-black text-lg" :class="b.stok_saat_ini <= b.stok_minimum ? 'text-rose-600' : 'text-slate-800'">
                      {{ b.stok_saat_ini }}
                    </span>
                    <span class="text-[10px] text-slate-400 block">{{ b.satuan }}</span>
                  </td>
                  <td class="py-3 px-4 text-center text-slate-500">{{ b.stok_minimum }} {{ b.satuan }}</td>
                  <td class="py-3 px-4 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="b.stok_saat_ini <= 0 ? 'bg-rose-100 text-rose-700' : b.stok_saat_ini <= b.stok_minimum ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
                      {{ b.stok_saat_ini <= 0 ? 'HABIS' : b.stok_saat_ini <= b.stok_minimum ? 'KRITIS' : 'Normal' }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-right font-mono text-slate-700">Rp {{ formatRupiah(b.harga_satuan) }}</td>
                  <td class="py-3 px-4 text-center">
                    <div class="flex justify-center gap-1">
                      <button type="button" class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold" @click="openModal('stok-bhp', { id: b.id, nama: b.nama_barang, stok: b.stok_saat_ini, tipe:'masuk', jumlah:1 })">+ Masuk</button>
                      <button type="button" class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 text-xs font-bold" @click="openModal('stok-bhp', { id: b.id, nama: b.nama_barang, stok: b.stok_saat_ini, tipe:'keluar', jumlah:1 })">- Keluar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== TAB 4: PEMINJAMAN ===== -->
        <div v-else-if="activeTab === 'peminjaman'" class="p-5 space-y-4">
          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-xs text-left">
              <thead><tr class="bg-slate-50 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3 px-4">No. Peminjaman</th>
                <th class="py-3 px-4">Peminjam & Tujuan</th>
                <th class="py-3 px-4">Tgl Pinjam</th>
                <th class="py-3 px-4">Rencana Kembali</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr></thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-if="!peminjamanList?.data?.length"><td colspan="6" class="py-12 text-center text-slate-400">Belum ada permohonan peminjaman.</td></tr>
                <tr v-for="p in peminjamanList.data" :key="p.id" class="hover:bg-slate-50/80 transition">
                  <td class="py-3 px-4 font-mono font-bold text-blue-700">{{ p.nomor_peminjaman }}</td>
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-900">{{ p.nama_peminjam }}</div>
                    <div class="text-[10px] text-slate-400 truncate max-w-xs">{{ p.tujuan_peminjaman }}</div>
                  </td>
                  <td class="py-3 px-4 text-slate-600">{{ formatDate(p.tanggal_pinjam) }}</td>
                  <td class="py-3 px-4 text-slate-600">{{ formatDate(p.tanggal_rencana_kembali) }}</td>
                  <td class="py-3 px-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                      :class="{
                        'bg-amber-50 text-amber-700 border-amber-200': p.status === 'Menunggu Persetujuan',
                        'bg-blue-50 text-blue-700 border-blue-200': p.status === 'Disetujui',
                        'bg-purple-50 text-purple-700 border-purple-200': p.status === 'Dipinjam',
                        'bg-emerald-50 text-emerald-700 border-emerald-200': p.status === 'Dikembalikan',
                        'bg-rose-50 text-rose-700 border-rose-200': p.status === 'Ditolak',
                      }">{{ p.status }}</span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <div class="flex justify-center gap-1">
                      <button v-if="p.status === 'Menunggu Persetujuan'" type="button" class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold" @click="openModal('approve-peminjaman', { id: p.id, aksi: 'setuju' })">Setuju</button>
                      <button v-if="p.status === 'Menunggu Persetujuan'" type="button" class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-bold" @click="openModal('approve-peminjaman', { id: p.id, aksi: 'tolak' })">Tolak</button>
                      <button v-if="p.status === 'Disetujui' || p.status === 'Dipinjam'" type="button" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold" @click="openModal('kembali-peminjaman', { id: p.id })">Tandai Kembali</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ===== TAB 5: PEMELIHARAAN ===== -->
        <div v-else-if="activeTab === 'pemeliharaan'" class="p-5 space-y-4">
          <div class="flex items-center justify-between">
            <div class="text-xs text-slate-600">
              Total Biaya Pemeliharaan: <strong class="text-slate-900 font-black">Rp {{ formatRupiah(totalBiayaPemeliharaan) }}</strong>
            </div>
          </div>

          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-xs text-left">
              <thead><tr class="bg-slate-50 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3 px-4">No. & Tanggal Laporan</th>
                <th class="py-3 px-4">Aset & Kerusakan</th>
                <th class="py-3 px-4">Jenis & Teknisi</th>
                <th class="py-3 px-4 text-right">Biaya</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr></thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-if="!pemeliharaanList?.data?.length"><td colspan="6" class="py-12 text-center text-slate-400">Belum ada riwayat pemeliharaan.</td></tr>
                <tr v-for="p in pemeliharaanList.data" :key="p.id" class="hover:bg-slate-50/80 transition">
                  <td class="py-3 px-4">
                    <div class="font-mono font-bold text-blue-700">{{ p.nomor_pemeliharaan }}</div>
                    <div class="text-[10px] text-slate-400">{{ formatDate(p.tanggal_laporan) }}</div>
                  </td>
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-900">{{ p.aset?.nama_barang || '-' }}</div>
                    <div class="text-[10px] text-slate-400 truncate max-w-xs">{{ p.deskripsi_kerusakan }}</div>
                  </td>
                  <td class="py-3 px-4">
                    <div class="font-semibold text-slate-700">{{ p.jenis_pemeliharaan }}</div>
                    <div class="text-[10px] text-slate-400">{{ p.teknisi_vendor || 'Internal' }}</div>
                  </td>
                  <td class="py-3 px-4 text-right font-mono text-slate-700">Rp {{ formatRupiah(p.biaya_pemeliharaan) }}</td>
                  <td class="py-3 px-4 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                      :class="{
                        'bg-rose-50 text-rose-700 border-rose-200': p.status === 'Dilaporkan',
                        'bg-amber-50 text-amber-700 border-amber-200': p.status === 'Dalam Proses',
                        'bg-emerald-50 text-emerald-700 border-emerald-200': p.status === 'Selesai',
                        'bg-slate-50 text-slate-600 border-slate-200': p.status === 'Ditunda',
                      }">{{ p.status }}</span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <button v-if="p.status !== 'Selesai'" type="button" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold" @click="openModal('update-pemeliharaan', { ...p })">Update Status</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MODAL UNIVERSAL ===== -->
    <Teleport to="body">
      <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi" :class="{
                'bi-box-seam text-blue-600': ['tambah-aset'].includes(modalType),
                'bi-bag-check text-amber-600': ['tambah-bhp', 'stok-bhp'].includes(modalType),
                'bi-arrow-left-right text-purple-600': ['tambah-peminjaman', 'approve-peminjaman', 'kembali-peminjaman'].includes(modalType),
                'bi-tools text-rose-600': ['tambah-pemeliharaan', 'update-pemeliharaan'].includes(modalType),
              }"></i>
              <span>
                {{ { 'tambah-aset': modalData.id ? 'Edit Barang Modal' : 'Tambah Barang Modal', 'tambah-bhp': 'Tambah BHP', 'stok-bhp': `Mutasi Stok — ${modalData.nama}`, 'tambah-peminjaman': 'Ajukan Peminjaman', 'approve-peminjaman': 'Tindak Lanjut Peminjaman', 'kembali-peminjaman': 'Pengembalian Sarpras', 'tambah-pemeliharaan': 'Lapor Kerusakan / Pemeliharaan', 'update-pemeliharaan': 'Update Status Pemeliharaan' }[modalType] || '' }}
              </span>
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-700 text-xl font-bold" @click="isModalOpen = false">&times;</button>
          </div>

          <div class="space-y-3 text-xs">
            <!-- Form: Tambah/Edit Aset -->
            <template v-if="modalType === 'tambah-aset'">
              <div class="grid grid-cols-2 gap-3">
                <div><label class="block font-bold text-slate-700 mb-1">Kode Aset *</label><input v-model="modalData.kode_aset" type="text" placeholder="Misal: AST-001" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
                <div><label class="block font-bold text-slate-700 mb-1">Nama Barang *</label><input v-model="modalData.nama_barang" type="text" placeholder="Nama barang..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
                <div><label class="block font-bold text-slate-700 mb-1">Kategori *</label><input v-model="modalData.kategori" type="text" placeholder="Elektronik, Mebel..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
                <div><label class="block font-bold text-slate-700 mb-1">Lokasi Ruangan *</label><input v-model="modalData.lokasi_ruangan" type="text" placeholder="Lab Komputer 1..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
                <div><label class="block font-bold text-slate-700 mb-1">Jumlah *</label><input v-model.number="modalData.jumlah" type="number" min="1" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
                <div><label class="block font-bold text-slate-700 mb-1">Satuan *</label><input v-model="modalData.satuan" type="text" placeholder="Unit, Set, Pcs..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
              </div>
              <div><label class="block font-bold text-slate-700 mb-1">Kondisi *</label>
                <SearchableSelect v-model="modalData.kondisi" :options="kondisiOptions.filter(o=>o.id)" placeholder="Pilih kondisi..." />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div><label class="block font-bold text-slate-700 mb-1">Tanggal Pengadaan</label><input v-model="modalData.tanggal_pengadaan" type="date" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
                <div><label class="block font-bold text-slate-700 mb-1">Harga Perolehan (Rp)</label><input v-model.number="modalData.harga_perolehan" type="number" min="0" placeholder="0" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
              </div>
            </template>

            <!-- Form: Mutasi Stok BHP -->
            <template v-else-if="modalType === 'stok-bhp'">
              <div class="p-3 bg-slate-50 rounded-xl text-slate-700">Stok saat ini: <strong>{{ modalData.stok }}</strong></div>
              <div><label class="block font-bold text-slate-700 mb-1">Jenis Mutasi</label>
                <div class="grid grid-cols-2 gap-2">
                  <button type="button" @click="modalData.tipe = 'masuk'" class="py-2 rounded-xl border text-xs font-bold" :class="modalData.tipe === 'masuk' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-50 text-slate-700 border-slate-200'">+ Stok Masuk</button>
                  <button type="button" @click="modalData.tipe = 'keluar'" class="py-2 rounded-xl border text-xs font-bold" :class="modalData.tipe === 'keluar' ? 'bg-amber-600 text-white border-amber-600' : 'bg-slate-50 text-slate-700 border-slate-200'">- Stok Keluar</button>
                </div>
              </div>
              <div><label class="block font-bold text-slate-700 mb-1">Jumlah *</label><input v-model.number="modalData.jumlah" type="number" min="1" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
              <div><label class="block font-bold text-slate-700 mb-1">Catatan</label><input v-model="modalData.catatan" type="text" placeholder="Keperluan mutasi stok..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" /></div>
            </template>

            <!-- Form: Approve/Tolak Peminjaman -->
            <template v-else-if="modalType === 'approve-peminjaman'">
              <div class="p-3 rounded-xl border font-bold text-sm text-center" :class="modalData.aksi === 'setuju' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'">
                {{ modalData.aksi === 'setuju' ? 'Menyetujui Peminjaman' : 'Menolak Peminjaman' }}
              </div>
              <div><label class="block font-bold text-slate-700 mb-1">Catatan Persetujuan</label><textarea v-model="modalData.catatan" rows="3" placeholder="Catatan (opsional)..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea></div>
            </template>

            <!-- Form: Lapor Pemeliharaan -->
            <template v-else-if="['tambah-pemeliharaan', 'update-pemeliharaan'].includes(modalType)">
              <div v-if="modalType === 'tambah-pemeliharaan'"><label class="block font-bold text-slate-700 mb-1">Aset yang Rusak *</label><SearchableSelect v-model="modalData.aset_id" :options="asetSelectOptions" placeholder="Pilih aset..." /></div>
              <div class="grid grid-cols-2 gap-3">
                <div><label class="block font-bold text-slate-700 mb-1">Jenis Pemeliharaan *</label><input v-model="modalData.jenis_pemeliharaan" type="text" placeholder="Perbaikan, Rutin..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none" /></div>
                <div><label class="block font-bold text-slate-700 mb-1">Status *</label>
                  <SearchableSelect v-model="modalData.status"
                    :options="[{id:'Dilaporkan',label:'Dilaporkan'},{id:'Dalam Proses',label:'Dalam Proses'},{id:'Selesai',label:'Selesai'},{id:'Ditunda',label:'Ditunda'}]"
                    placeholder="Pilih status..." />
                </div>
              </div>
              <div v-if="modalType === 'tambah-pemeliharaan'"><label class="block font-bold text-slate-700 mb-1">Deskripsi Kerusakan *</label><textarea v-model="modalData.deskripsi_kerusakan" rows="3" placeholder="Jelaskan kerusakan yang terjadi..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none resize-none"></textarea></div>
              <div class="grid grid-cols-2 gap-3">
                <div><label class="block font-bold text-slate-700 mb-1">Teknisi/Vendor</label><input v-model="modalData.teknisi_vendor" type="text" placeholder="Nama teknisi..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none" /></div>
                <div><label class="block font-bold text-slate-700 mb-1">Biaya Pemeliharaan (Rp)</label><input v-model.number="modalData.biaya_pemeliharaan" type="number" min="0" placeholder="0" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none" /></div>
              </div>
            </template>
          </div>

          <div class="flex gap-3 pt-2 border-t border-slate-100">
            <button type="button" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition" @click="isModalOpen = false">Batal</button>
            <button type="button"
              class="flex-1 py-2.5 rounded-xl text-white font-bold text-xs shadow-sm transition flex items-center justify-center gap-2 disabled:opacity-50"
              :class="['approve-peminjaman'].includes(modalType) && modalData.aksi === 'tolak' ? 'bg-rose-600 hover:bg-rose-500' : 'bg-blue-600 hover:bg-blue-500'"
              :disabled="isSaving"
              @click="saveModal">
              <i class="bi bi-save"></i>
              {{ isSaving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
