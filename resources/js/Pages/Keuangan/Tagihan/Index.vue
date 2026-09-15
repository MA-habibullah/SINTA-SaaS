<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js'
import axios from 'axios'

// === ZERO-SSR: Props awal hanya shell (semua null), data via async ===
const props = defineProps({
  isSuperAdmin: { type: Boolean, default: false },
  tagihanList:  { type: Object, default: null },
  posList:      { type: Array, default: null },
  kelasList:    { type: Array, default: null },
  tahunAjaranList: { type: Array, default: null },
  tenantsList:  { type: Array, default: null },
  filters:      { type: Object, default: null },
})

// === State lokal reaktif ===
const localTagihanList   = ref({ data: [], meta: {} })
const localPosList       = ref([])
const localKelasList     = ref([])
const localTahunAjaranList = ref([])
const localTenantsList   = ref([])
const isLoadingInit      = ref(false)
const isLoadingTagihan   = ref(false)

// Tenant in-memory (BUKAN dari URL query)
const activeTenantId     = ref('')

// Daftarkan state sensitif ke memory security
useMemorySecurity([localTagihanList])

// =============================================
// ASYNC DATA LOADING (Zero-SSR)
// =============================================
async function loadInitialData(tenantId = '') {
  isLoadingInit.value = true
  try {
    const headers = {}
    if (tenantId) headers['X-Tenant-Id'] = tenantId
    const res = await axios.get('/keuangan/tagihan', {
      params: { async: 1 },
      headers,
    })
    if (res.data?.success) {
      const d = res.data.data
      localPosList.value       = d.posList       || []
      localKelasList.value     = d.kelasList     || []
      localTahunAjaranList.value = d.tahunAjaranList || []
      localTenantsList.value   = d.tenantsList   || []
      localTagihanList.value   = d.tagihanList   || { data: [] }
    }
  } catch (err) {
    console.error('Gagal memuat data tagihan:', err)
  } finally {
    isLoadingInit.value = false
  }
}

async function loadTagihan() {
  isLoadingTagihan.value = true
  try {
    const headers = {}
    if (activeTenantId.value) headers['X-Tenant-Id'] = activeTenantId.value
    const res = await axios.get('/keuangan/tagihan', {
      params: {
        async: 1,
        search:   searchQuery.value || undefined,
        status:   selectedStatus.value || undefined,
        pos_id:   selectedPosId.value || undefined,
        kelas_id: selectedKelasId.value || undefined,
        bulan:    selectedBulan.value || undefined,
        tahun:    selectedTahun.value || undefined,
        page:     currentPage.value,
      },
      headers,
    })
    if (res.data?.success) {
      localTagihanList.value = res.data.data?.tagihanList || { data: [] }
    }
  } catch (err) {
    showToast('Gagal memuat data tagihan.', 'error')
  } finally {
    isLoadingTagihan.value = false
  }
}

onMounted(() => { loadInitialData() })

// =============================================
// TENANT SWITCHER (In-Memory, BUKAN URL query)
// =============================================
const tenantOptions = computed(() => [
  { id: '', label: '-- Semua Sekolah (Agregat Global) --', subLabel: 'Tampilkan seluruh tenant' },
  ...localTenantsList.value.map(t => ({
    id: t.id,
    label: t.nama_sekolah,
    subLabel: t.npsn ? `NPSN: ${t.npsn}` : 'Sekolah'
  }))
])

function onTenantSwitch(newTenantId) {
  activeTenantId.value = newTenantId
  resetFilters()
  loadInitialData(newTenantId)
}

function getSelectedTenantName() {
  if (!activeTenantId.value) return 'Semua Sekolah (Agregat Platform)'
  const found = localTenantsList.value.find(t => t.id === activeTenantId.value)
  return found ? found.nama_sekolah : 'Sekolah Terpilih'
}

// =============================================
// TOAST NOTIFICATION
// =============================================
const toast = ref({ show: false, message: '', type: 'success' })
function showToast(msg, type = 'success') {
  toast.value = { show: true, message: msg, type }
  setTimeout(() => { toast.value.show = false }, 4000)
}

// =============================================
// HELPERS
// =============================================
function formatRupiah(val) {
  if (!val || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}
function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

// =============================================
// FILTER STATE (In-Memory Reactive)
// =============================================
const searchQuery      = ref('')
const selectedStatus   = ref('')
const selectedPosId    = ref('')
const selectedKelasId  = ref('')
const selectedBulan    = ref('')
const selectedTahun    = ref('')
const currentPage      = ref(1)

const posFilterOptions = computed(() => [
  { id: '', label: 'Semua Pos Biaya' },
  ...localPosList.value.map(p => ({ id: p.id, label: p.nama_pos, subLabel: p.tipe_periode }))
])
const kelasFilterOptions = computed(() => [
  { id: '', label: 'Semua Kelas' },
  ...localKelasList.value.map(k => ({ id: k.id, label: k.nama_kelas }))
])
const statusFilterOptions = [
  { id: '', label: 'Semua Status' },
  { id: 'Belum Bayar', label: 'Belum Bayar' },
  { id: 'Sebagian', label: 'Sebagian (Cicilan)' },
  { id: 'Lunas', label: 'Lunas' },
]
const bulanFilterOptions = [
  { id: '', label: 'Semua Bulan' },
  { id: '7', label: 'Juli' }, { id: '8', label: 'Agustus' }, { id: '9', label: 'September' },
  { id: '10', label: 'Oktober' }, { id: '11', label: 'November' }, { id: '12', label: 'Desember' },
  { id: '1', label: 'Januari' }, { id: '2', label: 'Februari' }, { id: '3', label: 'Maret' },
  { id: '4', label: 'April' }, { id: '5', label: 'Mei' }, { id: '6', label: 'Juni' },
]

// Apply filter = Axios call in-memory (URL TETAP BERSIH)
function applyFilters() {
  currentPage.value = 1
  loadTagihan()
}
function resetFilters() {
  searchQuery.value = selectedStatus.value = selectedPosId.value = selectedKelasId.value = selectedBulan.value = selectedTahun.value = ''
  currentPage.value = 1
  loadTagihan()
}

// =============================================
// GENERATE TAGIHAN MASSAL
// =============================================
const isGenerateModalOpen = ref(false)
const generateForm = ref({
  pos_id: '',
  tahun_ajaran_id: '',
  target_tipe: 'all',
  tingkat: 'X',
  kelas_id: '',
  bulan: new Date().getMonth() + 1,
  tahun: new Date().getFullYear(),
  tanggal_jatuh_tempo: '',
})
const isGenerating = ref(false)

const tingkatOptions = [
  { id: 'X', label: 'Tingkat X (Kelas 10 / 7 / 1)' },
  { id: 'XI', label: 'Tingkat XI (Kelas 11 / 8 / 2)' },
  { id: 'XII', label: 'Tingkat XII (Kelas 12 / 9 / 3)' },
]

async function submitGenerateTagihan() {
  isGenerating.value = true
  try {
    const res = await axios.post('/keuangan/tagihan/generate', generateForm.value)
    if (res.data?.success) {
      showToast(res.data.message || 'Penerbitan tagihan massal berhasil!')
      isGenerateModalOpen.value = false
      loadTagihan()
    }
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors
      ? Object.values(err.response?.data?.errors || {}).flat().join(', ')
      : 'Gagal menerbitkan tagihan.'
    showToast(msg, 'error')
  } finally {
    isGenerating.value = false
  }
}

async function deleteTagihan(id, nama) {
  if (!confirm(`Hapus tagihan ${nama}? Tagihan hanya dapat dihapus jika belum ada pembayaran.`)) return
  try {
    const res = await axios.delete(`/keuangan/tagihan/${id}`)
    if (res.data?.success) {
      showToast('Tagihan berhasil dihapus.')
      loadTagihan()
    }
  } catch (err) {
    showToast(err.response?.data?.message || 'Gagal menghapus tagihan.', 'error')
  }
}
</script>

<template>
  <AppLayout title="Manajemen Tagihan & Invoicing Siswa">
    <Head title="Manajemen Tagihan & Invoicing Siswa" />

    <!-- Toast Notification -->
    <transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="toast.show" class="fixed top-5 right-5 z-50 max-w-md bg-white rounded-2xl shadow-2xl border p-4 flex items-center gap-3" :class="toast.type === 'error' ? 'border-rose-200 bg-rose-50/90 text-rose-900' : 'border-emerald-200 bg-emerald-50/90 text-emerald-900'">
        <i class="bi text-xl shrink-0" :class="toast.type === 'error' ? 'bi-exclamation-octagon-fill text-rose-600' : 'bi-check-circle-fill text-emerald-600'"></i>
        <div class="text-sm font-medium grow">{{ toast.message }}</div>
        <button type="button" class="text-slate-400 hover:text-slate-700 text-lg leading-none" @click="toast.show = false">&times;</button>
      </div>
    </transition>

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-800 tracking-tight">Manajemen Tagihan Siswa</h1>
          <p class="text-xs text-slate-500 mt-1">Daftar invoice penagihan SPP dan penerbitan tagihan secara massal (<em>batch invoicing</em>).</p>
        </div>
        <div class="flex items-center gap-2">
          <a href="/keuangan/kasir" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-2">
            <i class="bi bi-cash-stack"></i> Loket Kasir
          </a>
          <button type="button"
            class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-sm transition flex items-center gap-2 active:scale-95"
            @click="isGenerateModalOpen = true">
            <i class="bi bi-magic"></i> Terbitkan Tagihan Massal
          </button>
        </div>
      </div>

      <!-- Section: Banner Filter Tenant (Khusus Super Admin) — In-Memory -->
      <div v-if="isSuperAdmin" class="rounded-2xl shadow-sm border border-purple-200 bg-gradient-to-r from-purple-50/90 via-indigo-50/80 to-slate-50 border-l-4 border-l-purple-600 p-4 transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-sm shrink-0">
              <i class="bi bi-building text-lg"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-sm">Filter Tagihan Sekolah</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200">
                  <i class="bi bi-shield-lock-fill me-1 text-purple-600"></i> Super Admin Mode
                </span>
              </div>
              <p class="text-xs text-slate-600 mt-0.5">
                Mengelola tagihan milik: <strong class="text-purple-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
              </p>
            </div>
          </div>
          <div class="w-full md:w-80 shrink-0">
            <SearchableSelect
              v-model="activeTenantId"
              :options="tenantOptions"
              placeholder="Pilih atau cari sekolah..."
              searchPlaceholder="Ketik nama sekolah..."
              @change="onTenantSwitch"
            />
          </div>
        </div>
      </div>

      <!-- Filter Card (In-Memory — tanpa router.get) -->
      <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Cari Siswa / No. Invoice</label>
            <input type="text" v-model="searchQuery" @keyup.enter="applyFilters"
              placeholder="Nama / NISN / Invoice..."
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Pos Biaya</label>
            <SearchableSelect v-model="selectedPosId" :options="posFilterOptions" placeholder="Semua Pos..." @change="applyFilters" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Rombel Kelas</label>
            <SearchableSelect v-model="selectedKelasId" :options="kelasFilterOptions" placeholder="Semua Kelas..." @change="applyFilters" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Status Pembayaran</label>
            <SearchableSelect v-model="selectedStatus" :options="statusFilterOptions" placeholder="Semua Status..." @change="applyFilters" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Bulan Tagihan</label>
            <SearchableSelect v-model="selectedBulan" :options="bulanFilterOptions" placeholder="Semua Bulan..." @change="applyFilters" />
          </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
          <div class="text-slate-500">
            Total: <strong class="text-slate-800">{{ localTagihanList?.total || 0 }}</strong> tagihan ditemukan
          </div>
          <div class="flex gap-2">
            <button type="button" @click="applyFilters"
              class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold transition flex items-center gap-1.5">
              <i class="bi bi-search"></i> Terapkan Filter
            </button>
            <button type="button" @click="resetFilters"
              class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition flex items-center gap-1.5">
              <i class="bi bi-x-circle"></i> Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Tabel Tagihan -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div v-if="isLoadingInit || isLoadingTagihan" class="py-16 text-center text-slate-400 text-xs">
          <div class="inline-block animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
          <p>Memuat data tagihan...</p>
        </div>

        <div v-else-if="!localTagihanList?.data?.length" class="py-16 text-center text-slate-400 text-xs space-y-2">
          <div class="w-14 h-14 mx-auto rounded-3xl bg-slate-100 flex items-center justify-center text-2xl mb-2">
            <i class="bi bi-receipt text-slate-400"></i>
          </div>
          <p class="font-bold text-slate-700">Belum Ada Data Tagihan</p>
          <p class="text-slate-400">Gunakan tombol "Terbitkan Tagihan Massal" untuk membuat invoice SPP siswa.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3 px-4">No. Tagihan & Periode</th>
                <th class="py-3 px-4">Siswa</th>
                <th class="py-3 px-4">Pos Biaya</th>
                <th class="py-3 px-4 text-right">Total Tagihan</th>
                <th class="py-3 px-4 text-right">Terbayar</th>
                <th class="py-3 px-4 text-right">Sisa</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="t in localTagihanList.data" :key="t.id" class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4">
                  <div class="font-mono font-bold text-blue-700 text-[11px]">{{ t.nomor_tagihan }}</div>
                  <div v-if="t.bulan" class="text-[10px] text-slate-400">Bulan {{ t.bulan }}/{{ t.tahun }}</div>
                  <div v-else class="text-[10px] text-slate-400">TA: {{ t.tahun }}</div>
                </td>
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900">{{ t.siswa?.nama_lengkap || '-' }}</div>
                  <div class="text-[10px] text-slate-400">NISN: {{ t.siswa?.nisn || '-' }} | {{ t.siswa?.kelas_saat_ini || '-' }}</div>
                </td>
                <td class="py-3 px-4 font-semibold text-slate-700">{{ t.pos?.nama_pos || '-' }}</td>
                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">Rp {{ formatRupiah(t.total_tagihan) }}</td>
                <td class="py-3 px-4 text-right font-mono text-emerald-600">Rp {{ formatRupiah(t.total_terbayar) }}</td>
                <td class="py-3 px-4 text-right font-mono font-bold" :class="Number(t.sisa_tagihan) > 0 ? 'text-rose-600' : 'text-emerald-600'">
                  Rp {{ formatRupiah(t.sisa_tagihan) }}
                </td>
                <td class="py-3 px-4 text-center">
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border"
                    :class="{
                      'bg-emerald-50 text-emerald-700 border-emerald-200': t.status_pembayaran === 'Lunas',
                      'bg-amber-50 text-amber-700 border-amber-200': t.status_pembayaran === 'Sebagian',
                      'bg-rose-50 text-rose-700 border-rose-200': t.status_pembayaran === 'Belum Bayar',
                    }">
                    {{ t.status_pembayaran }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <a :href="`/keuangan/kasir`" class="p-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition text-xs" title="Bayar via Kasir">
                      <i class="bi bi-cash"></i>
                    </a>
                    <button v-if="t.total_terbayar <= 0" type="button"
                      class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition text-xs"
                      title="Hapus Tagihan"
                      @click="deleteTagihan(t.id, t.nomor_tagihan)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="localTagihanList?.last_page > 1" class="flex items-center justify-between p-4 border-t border-slate-100 text-xs text-slate-600">
          <div>
            Halaman {{ localTagihanList?.current_page }} dari {{ localTagihanList?.last_page }} &bull;
            Total {{ localTagihanList?.total }} tagihan
          </div>
          <div class="flex gap-1">
            <button v-for="p in Math.min(localTagihanList?.last_page, 7)" :key="p"
              type="button"
              class="w-8 h-8 rounded-lg font-bold transition"
              :class="p === localTagihanList?.current_page ? 'bg-blue-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
              @click="currentPage = p; loadTagihan()">
              {{ p }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MODAL GENERATE TAGIHAN MASSAL ===== -->
    <Teleport to="body">
      <div v-if="isGenerateModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-start justify-center p-4 pt-16">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-magic text-blue-600"></i> Terbitkan Tagihan SPP Massal
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none" @click="isGenerateModalOpen = false">&times;</button>
          </div>

          <div class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Pos Biaya / Komponen <span class="text-rose-500">*</span></label>
              <SearchableSelect v-model="generateForm.pos_id" :options="localPosList.map(p => ({ id: p.id, label: p.nama_pos, subLabel: p.tipe_periode }))" placeholder="-- Pilih Pos Tagihan --" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Tahun Tagihan <span class="text-rose-500">*</span></label>
                <input type="number" v-model.number="generateForm.tahun" min="2020" max="2099" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1">Bulan Tagihan</label>
                <SearchableSelect v-model="generateForm.bulan" :options="[{id:'',label:'Tidak Berlaku (Non-Bulanan)'}, {id:7,label:'Juli'},{id:8,label:'Agustus'},{id:9,label:'September'},{id:10,label:'Oktober'},{id:11,label:'November'},{id:12,label:'Desember'},{id:1,label:'Januari'},{id:2,label:'Februari'},{id:3,label:'Maret'},{id:4,label:'April'},{id:5,label:'Mei'},{id:6,label:'Juni'}]" placeholder="Pilih Bulan..." />
              </div>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Target Penerbitan <span class="text-rose-500">*</span></label>
              <div class="grid grid-cols-3 gap-2">
                <button v-for="opt in [{v:'all',l:'Semua Siswa'},{v:'tingkat',l:'Per Tingkat'},{v:'kelas',l:'Per Kelas'}]" :key="opt.v"
                  type="button"
                  class="py-2 px-3 rounded-xl border text-xs font-bold transition"
                  :class="generateForm.target_tipe === opt.v ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                  @click="generateForm.target_tipe = opt.v">
                  {{ opt.l }}
                </button>
              </div>
            </div>

            <div v-if="generateForm.target_tipe === 'tingkat'">
              <label class="block font-bold text-slate-700 mb-1">Pilih Tingkat</label>
              <SearchableSelect v-model="generateForm.tingkat" :options="tingkatOptions" placeholder="Pilih tingkat..." />
            </div>

            <div v-if="generateForm.target_tipe === 'kelas'">
              <label class="block font-bold text-slate-700 mb-1">Pilih Rombel Kelas</label>
              <SearchableSelect v-model="generateForm.kelas_id" :options="localKelasList.map(k => ({id:k.id,label:k.nama_kelas}))" placeholder="Pilih kelas..." />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tanggal Jatuh Tempo (Opsional)</label>
              <input type="date" v-model="generateForm.tanggal_jatuh_tempo" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>

          <div class="flex gap-3 pt-2 border-t border-slate-100">
            <button type="button" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition" @click="isGenerateModalOpen = false">
              Batal
            </button>
            <button type="button"
              class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-sm transition flex items-center justify-center gap-2 disabled:opacity-50"
              :disabled="isGenerating || !generateForm.pos_id"
              @click="submitGenerateTagihan">
              <i class="bi bi-magic"></i>
              {{ isGenerating ? 'Menerbitkan Tagihan...' : 'Terbitkan Sekarang' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
