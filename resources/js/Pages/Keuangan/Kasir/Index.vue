<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js'
import axios from 'axios'

// === ZERO-SSR: Props awal hanya shell, data dimuat async ===
const props = defineProps({
  isSuperAdmin: { type: Boolean, default: false },
  kasList:      { type: Array, default: null },
  kelasList:    { type: Array, default: null },
  pengaturan:   { type: Object, default: null },
  tenantsList:  { type: Array, default: null },
})

// === State lokal reaktif (semua data dimuat async) ===
const localKasList    = ref([])
const localKelasList  = ref([])
const localTenantsList = ref([])
const localPengaturan = ref({})
const isLoadingInit   = ref(false)

// Tenant in-memory state (BUKAN dari URL query)
const activeTenantId = ref('')

// Daftarkan ke Memory Security untuk pembersihan saat unmount
useMemorySecurity([selectedSiswa, tagihanList, riwayatTransaksi])

// =============================================
// ASYNC DATA LOADING (Zero-SSR)
// =============================================
async function loadInitialData(tenantId = '') {
  isLoadingInit.value = true
  try {
    const headers = {}
    if (tenantId) headers['X-Tenant-Id'] = tenantId

    const res = await axios.get('/keuangan/kasir', {
      params: { async: 1 },
      headers,
    })
    if (res.data?.success) {
      const d = res.data.data
      localKasList.value    = d.kasList    || []
      localKelasList.value  = d.kelasList  || []
      localTenantsList.value = d.tenantsList || []
      localPengaturan.value  = d.pengaturan  || {}
      // Auto-select kas pertama
      if (!selectedKasId.value && localKasList.value.length > 0) {
        selectedKasId.value = localKasList.value[0].id
      }
    }
  } catch (err) {
    console.error('Gagal memuat data kasir:', err)
  } finally {
    isLoadingInit.value = false
  }
}

onMounted(() => {
  loadInitialData()
})

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
  // Reload data via in-memory Axios — URL TETAP BERSIH tanpa ?tenant_id=
  loadInitialData(newTenantId)
  // Reset siswa jika ganti sekolah
  resetSiswaState()
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
// HELPER FUNCTIONS
// =============================================
function formatRupiah(val) {
  if (!val || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

// =============================================
// SISWA SEARCH & SELECTION
// =============================================
const selectedSiswaId    = ref('')
const selectedSiswa      = ref(null)
const tagihanList        = ref([])
const riwayatTransaksi   = ref([])
const loadingSiswa       = ref(false)
const searchSiswaResults = ref([])
const filterKelasId      = ref('')

const kelasFilterOptions = computed(() => [
  { id: '', label: 'Semua Kelas' },
  ...localKelasList.value.map(k => ({ id: k.id, label: k.nama_kelas }))
])

async function onSearchSiswa(q) {
  if (!q || q.length < 2) return
  try {
    const headers = {}
    if (activeTenantId.value) headers['X-Tenant-Id'] = activeTenantId.value

    const res = await axios.get('/keuangan/search-siswa', {
      params: { q, kelas_id: filterKelasId.value || undefined },
      headers,
    })
    if (res.data?.success) {
      searchSiswaResults.value = res.data.data.map(s => ({
        id: s.id,
        label: s.nama_lengkap,
        subLabel: `NISN: ${s.nisn || '-'} | ${s.kelas_saat_ini || '-'}`
      }))
    }
  } catch (err) {
    console.error(err)
  }
}

async function loadSiswaTagihan(siswaId) {
  if (!siswaId) { resetSiswaState(); return }
  loadingSiswa.value = true
  try {
    const res = await axios.get(`/keuangan/kasir/siswa-tagihan/${siswaId}`)
    if (res.data?.success) {
      selectedSiswa.value     = res.data.siswa
      tagihanList.value       = (res.data.tagihanList || []).map(t => ({
        ...t,
        selected: false,
        nominal_bayar_input: Number(t.sisa_tagihan)
      }))
      riwayatTransaksi.value  = res.data.riwayatTransaksi || []
    }
  } catch (err) {
    showToast('Gagal memuat tagihan siswa.', 'error')
  } finally {
    loadingSiswa.value = false
  }
}

function resetSiswaState() {
  selectedSiswa.value    = null
  tagihanList.value      = []
  riwayatTransaksi.value = []
  selectedSiswaId.value  = ''
}

function selectAllTagihan() {
  const allSelected = tagihanList.value.every(t => t.selected)
  tagihanList.value.forEach(t => { t.selected = !allSelected })
}

// =============================================
// PAYMENT CHECKOUT STATE
// =============================================
const selectedKasId     = ref('')
const selectedMetode    = ref('Tunai')
const catatanPembayaran = ref('')
const isProcessingBayar = ref(false)

const kasOptions = computed(() =>
  localKasList.value.map(k => ({
    id: k.id,
    label: k.nama_kas,
    subLabel: `Saldo: Rp ${formatRupiah(k.saldo_saat_ini)}`
  }))
)

const selectedItems = computed(() => tagihanList.value.filter(t => t.selected))
const totalCheckout = computed(() =>
  selectedItems.value.reduce((sum, item) => sum + Number(item.nominal_bayar_input || 0), 0)
)

// =============================================
// RECEIPT MODAL STATE
// =============================================
const isKuitansiModalOpen = ref(false)
const activeKuitansi      = ref(null)

async function prosesPembayaranKasir() {
  if (!selectedSiswa.value) { showToast('Pilih siswa terlebih dahulu.', 'error'); return }
  if (selectedItems.value.length === 0) { showToast('Pilih minimal 1 tagihan untuk dibayar.', 'error'); return }
  if (!selectedKasId.value) { showToast('Pilih akun kas penerima.', 'error'); return }

  const payload = {
    siswa_id: selectedSiswa.value.id,
    metode_pembayaran: selectedMetode.value,
    kas_id: selectedKasId.value,
    catatan: catatanPembayaran.value,
    items: selectedItems.value.map(item => ({
      tagihan_id: item.id,
      nominal_bayar: Number(item.nominal_bayar_input)
    }))
  }

  isProcessingBayar.value = true
  try {
    const res = await axios.post('/keuangan/kasir/bayar', payload)
    if (res.data?.success) {
      showToast('Pembayaran kasir berhasil diproses!')
      activeKuitansi.value   = res.data.kuitansi
      isKuitansiModalOpen.value = true
      loadSiswaTagihan(selectedSiswa.value.id)
      // Reload saldo kas
      loadInitialData(activeTenantId.value)
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal memproses pembayaran.'
    showToast(msg, 'error')
  } finally {
    isProcessingBayar.value = false
  }
}

function printKuitansi() {
  window.print()
}
</script>

<template>
  <AppLayout title="Loket Kasir Pembayaran Real-time">
    <Head title="Loket Kasir Pembayaran Real-time" />

    <!-- Toast Notification -->
    <transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="toast.show" class="fixed top-5 right-5 z-50 max-w-md bg-white rounded-2xl shadow-2xl border p-4 flex items-center gap-3" :class="toast.type === 'error' ? 'border-rose-200 bg-rose-50/90 text-rose-900' : 'border-emerald-200 bg-emerald-50/90 text-emerald-900'">
        <i class="bi text-xl shrink-0" :class="toast.type === 'error' ? 'bi-exclamation-octagon-fill text-rose-600' : 'bi-check-circle-fill text-emerald-600'"></i>
        <div class="text-sm font-medium grow">{{ toast.message }}</div>
        <button type="button" class="text-slate-400 hover:text-slate-700 text-lg leading-none" @click="toast.show = false">&times;</button>
      </div>
    </transition>

    <!-- Loading Initial Overlay -->
    <div v-if="isLoadingInit" class="fixed inset-0 z-40 bg-white/80 backdrop-blur-sm flex items-center justify-center">
      <div class="text-center space-y-3">
        <div class="inline-block w-10 h-10 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-sm font-bold text-slate-600">Memuat data kasir...</p>
      </div>
    </div>

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-800 tracking-tight">Loket Kasir Pembayaran</h1>
          <p class="text-xs text-slate-500 mt-1">Pencarian siswa, pembayaran multi-tagihan, cetak kuitansi termal/A4, dan mutasi kas instan.</p>
        </div>
        <div class="flex items-center gap-2">
          <a href="/keuangan/dashboard" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-2">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
          <a href="/keuangan/tagihan" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-sm transition flex items-center gap-2">
            <i class="bi bi-receipt"></i> Daftar Tagihan
          </a>
          <a href="/keuangan/kas-bank" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-sm transition flex items-center gap-2">
            <i class="bi bi-bank"></i> Buku Kas
          </a>
        </div>
      </div>

      <!-- Section: Banner Filter Tenant (Khusus Super Admin) — In-Memory Switching -->
      <div v-if="isSuperAdmin" class="rounded-2xl shadow-sm border border-emerald-200 bg-gradient-to-r from-emerald-50/90 via-teal-50/80 to-slate-50 border-l-4 border-l-emerald-600 p-4 transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-sm shrink-0">
              <i class="bi bi-building text-lg"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-sm">Filter Loket Kasir Sekolah</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                  <i class="bi bi-shield-lock-fill me-1 text-emerald-600"></i> Super Admin Mode
                </span>
              </div>
              <p class="text-xs text-slate-600 mt-0.5">
                Mengakses kasir milik: <strong class="text-emerald-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
              </p>
            </div>
          </div>
          <div class="w-full md:w-80 shrink-0">
            <!-- Tenant switch tanpa URL reload — in-memory -->
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

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Panel Pencarian Siswa & Profil (1-Col) -->
        <div class="space-y-4">
          <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
              <i class="bi bi-person-search text-emerald-600"></i> Cari Peserta Didik
            </h3>

            <div class="space-y-3 text-xs">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Filter Rombel Kelas</label>
                <SearchableSelect
                  v-model="filterKelasId"
                  :options="kelasFilterOptions"
                  placeholder="Semua Kelas..."
                />
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1">Ketik Nama Siswa atau NISN <span class="text-rose-500">*</span></label>
                <SearchableSelect
                  v-model="selectedSiswaId"
                  :options="searchSiswaResults"
                  placeholder="-- Ketik Nama / NISN Siswa --"
                  @search="onSearchSiswa"
                  @change="loadSiswaTagihan"
                />
              </div>
            </div>

            <!-- Kartu Profil Siswa Terpilih -->
            <div v-if="selectedSiswa" class="p-4 rounded-2xl bg-gradient-to-br from-emerald-500/10 via-teal-500/5 to-transparent border border-emerald-200 space-y-2">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl font-black shrink-0 shadow-md">
                  {{ selectedSiswa.nama_lengkap?.charAt(0) }}
                </div>
                <div class="overflow-hidden">
                  <h4 class="font-black text-slate-900 text-sm truncate">{{ selectedSiswa.nama_lengkap }}</h4>
                  <div class="text-[11px] text-slate-500">NISN: <span class="font-mono font-bold">{{ selectedSiswa.nisn || '-' }}</span></div>
                  <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-bold mt-1">
                    {{ selectedSiswa.kelas?.nama_kelas || selectedSiswa.kelas_saat_ini || 'Kelas Belum Ditentukan' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Checkout Box (Form Pembayaran) -->
          <div v-if="selectedSiswa" class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
              <i class="bi bi-credit-card text-blue-600"></i> Rincian Pembayaran Kasir
            </h3>

            <div class="space-y-3 text-xs">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Akun Kas Penerima <span class="text-rose-500">*</span></label>
                <SearchableSelect
                  v-model="selectedKasId"
                  :options="kasOptions"
                  placeholder="-- Pilih Kas / Rekening --"
                />
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                <div class="grid grid-cols-2 gap-2">
                  <button v-for="metode in ['Tunai', 'Transfer Bank', 'QRIS', 'Midtrans VA']" :key="metode"
                    type="button"
                    class="py-2 px-3 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5"
                    :class="selectedMetode === metode ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                    @click="selectedMetode = metode"
                  >
                    <i :class="{ 'bi bi-cash': metode === 'Tunai', 'bi bi-bank': metode === 'Transfer Bank', 'bi bi-qr-code': metode === 'QRIS', 'bi bi-wallet2': metode === 'Midtrans VA' }"></i>
                    {{ metode === 'Midtrans VA' ? 'Gateway VA' : metode }}
                  </button>
                </div>
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                <input type="text" v-model="catatanPembayaran" placeholder="Misal: Diterima dari Ibu Ani..."
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none transition text-xs" />
              </div>

              <!-- Total Checkout Banner -->
              <div class="p-4 rounded-2xl bg-slate-900 text-white space-y-1">
                <div class="text-[11px] text-slate-400 font-bold uppercase">Total Tagihan Dipilih: ({{ selectedItems.length }} Pos)</div>
                <div class="text-2xl font-black text-emerald-400 font-mono">Rp {{ formatRupiah(totalCheckout) }}</div>
              </div>

              <button type="button"
                class="w-full py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-sm shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2 active:scale-95 disabled:opacity-50"
                :disabled="isProcessingBayar || selectedItems.length === 0 || totalCheckout <= 0"
                @click="prosesPembayaranKasir"
              >
                <i class="bi bi-printer-fill text-base"></i>
                <span>{{ isProcessingBayar ? 'Memproses Transaksi...' : 'Bayar & Terbitkan Kuitansi' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Daftar Tagihan Aktif Siswa (2-Cols) -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                  <i class="bi bi-receipt-cutoff text-emerald-600"></i> Tagihan Belum Lunas
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Centang tagihan yang ingin dibayar sekaligus (<em>multi-invoice checkout</em>).</p>
              </div>
              <button v-if="tagihanList.length > 0" type="button"
                class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition"
                @click="selectAllTagihan">
                Centang Semua
              </button>
            </div>

            <div v-if="loadingSiswa" class="py-12 text-center text-slate-400 text-xs">
              <div class="inline-block animate-spin w-8 h-8 border-2 border-emerald-600 border-t-transparent rounded-full mb-2"></div>
              <p>Memuat tagihan siswa...</p>
            </div>

            <div v-else-if="!selectedSiswa" class="py-16 text-center text-slate-400 text-xs space-y-2">
              <div class="w-14 h-14 mx-auto rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-2">
                <i class="bi bi-person-fill-gear"></i>
              </div>
              <p class="font-bold text-slate-700">Belum Ada Siswa yang Dipilih</p>
              <p class="text-slate-400 max-w-xs mx-auto">Silakan cari nama siswa atau masukkan NISN pada kolom pencarian di sebelah kiri.</p>
            </div>

            <div v-else-if="tagihanList.length === 0" class="py-16 text-center text-slate-400 text-xs space-y-2">
              <div class="w-14 h-14 mx-auto rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-2 shadow-inner">
                <i class="bi bi-check-circle-fill"></i>
              </div>
              <p class="font-bold text-slate-800 text-sm">Semua Tagihan Siswa Ini Telah Lunas!</p>
              <p class="text-slate-400">Tidak ada kewajiban pembayaran yang tertunggak.</p>
            </div>

            <div v-else class="space-y-3">
              <div v-for="t in tagihanList" :key="t.id"
                class="p-4 rounded-2xl border transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 cursor-pointer"
                :class="t.selected ? 'bg-emerald-50/60 border-emerald-300 ring-2 ring-emerald-500/20 shadow-sm' : 'bg-slate-50/50 border-slate-200 hover:border-slate-300'"
                @click="t.selected = !t.selected"
              >
                <div class="flex items-start gap-3">
                  <input type="checkbox" v-model="t.selected"
                    class="w-5 h-5 rounded-lg text-emerald-600 focus:ring-emerald-500 border-slate-300 mt-0.5 cursor-pointer"
                    @click.stop />
                  <div>
                    <div class="font-black text-slate-900 text-sm">
                      {{ t.pos?.nama_pos || 'SPP' }}
                      <span v-if="t.bulan" class="text-emerald-700 font-bold ms-1">(Bulan {{ t.bulan }}/{{ t.tahun }})</span>
                    </div>
                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">No. Invoice: {{ t.nomor_tagihan }}</div>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="px-2 py-0.5 rounded text-[10px] font-bold border" :class="t.status_pembayaran === 'Sebagian' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                        {{ t.status_pembayaran }}
                      </span>
                      <span class="text-[11px] text-slate-500">
                        Total: Rp {{ formatRupiah(t.total_tagihan) }} &bull; Sisa: <strong class="text-rose-600">Rp {{ formatRupiah(t.sisa_tagihan) }}</strong>
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Input Nominal Bayar (Cicilan Support) -->
                <div class="sm:text-right shrink-0 w-full sm:w-auto" @click.stop>
                  <label class="block text-[10px] font-bold text-slate-400 mb-1">Nominal Bayar (Rp)</label>
                  <input type="number" v-model.number="t.nominal_bayar_input"
                    :max="t.sisa_tagihan" min="1000"
                    class="w-full sm:w-36 px-3 py-1.5 bg-white border border-slate-200 rounded-xl font-mono text-xs font-bold text-slate-800 text-right focus:ring-2 focus:ring-emerald-500 outline-none" />
                </div>
              </div>
            </div>
          </div>

          <!-- Riwayat Transaksi -->
          <div v-if="selectedSiswa && riwayatTransaksi.length > 0" class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
              <i class="bi bi-clock-history text-blue-600"></i> Riwayat Pembayaran Sebelumnya
            </h3>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                    <th class="py-2.5 px-3">No. Transaksi & Tanggal</th>
                    <th class="py-2.5 px-3">Pos Pembayaran</th>
                    <th class="py-2.5 px-3">Metode & Kasir</th>
                    <th class="py-2.5 px-3 text-right">Nominal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="trx in riwayatTransaksi" :key="trx.id" class="hover:bg-slate-50 transition">
                    <td class="py-2.5 px-3">
                      <div class="font-mono font-bold text-blue-700">{{ trx.nomor_transaksi }}</div>
                      <div class="text-[10px] text-slate-400">{{ formatDate(trx.tanggal_bayar) }}</div>
                    </td>
                    <td class="py-2.5 px-3 font-semibold text-slate-800">{{ trx.tagihan?.pos?.nama_pos || '-' }}</td>
                    <td class="py-2.5 px-3 text-slate-500">
                      {{ trx.metode_pembayaran }} &bull; <span class="text-[10px]">{{ trx.kasir?.nama_lengkap || 'Kasir' }}</span>
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600">
                      Rp {{ formatRupiah(trx.nominal_bayar) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL CETAK KUITANSI -->
    <Teleport to="body">
      <div v-if="isKuitansiModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-md w-full p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-printer-fill text-emerald-600"></i> Kuitansi Pembayaran Sah
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none" @click="isKuitansiModalOpen = false">&times;</button>
          </div>

          <div id="printAreaKuitansi" class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-xs font-mono space-y-3">
            <div class="text-center border-b border-dashed border-slate-300 pb-2">
              <div class="font-black text-sm text-slate-900 uppercase tracking-wider">KUITANSI PEMBAYARAN</div>
              <div class="text-[10px] text-slate-500">{{ localPengaturan?.nama_modul || 'SINTA-SaaS Payment Gateway' }}</div>
            </div>

            <div class="space-y-1 text-[11px]">
              <div class="flex justify-between"><span>No. Kuitansi:</span><span class="font-bold text-slate-900">{{ activeKuitansi?.nomor_kuitansi }}</span></div>
              <div class="flex justify-between"><span>Tanggal:</span><span>{{ activeKuitansi?.tanggal }}</span></div>
              <div class="flex justify-between"><span>Nama Siswa:</span><span class="font-bold text-slate-900">{{ activeKuitansi?.siswa?.nama_lengkap }}</span></div>
              <div class="flex justify-between"><span>NISN / Kelas:</span><span>{{ activeKuitansi?.siswa?.nisn }} / {{ activeKuitansi?.siswa?.kelas?.nama_kelas || '-' }}</span></div>
              <div class="flex justify-between"><span>Metode Bayar:</span><span>{{ activeKuitansi?.metode_pembayaran }}</span></div>
            </div>

            <div class="border-t border-b border-dashed border-slate-300 py-2 space-y-1.5 text-[11px]">
              <div v-for="item in activeKuitansi?.items" :key="item.id" class="flex justify-between">
                <div>
                  <div class="font-bold text-slate-800">{{ item.tagihan?.pos?.nama_pos || 'Tagihan' }}</div>
                  <div v-if="item.tagihan?.bulan" class="text-[9px] text-slate-400">Bulan {{ item.tagihan?.bulan }}/{{ item.tagihan?.tahun }}</div>
                </div>
                <span class="font-bold text-slate-900">Rp {{ formatRupiah(item.nominal_bayar) }}</span>
              </div>
            </div>

            <div class="flex justify-between text-sm font-black text-slate-900 pt-1">
              <span>TOTAL BAYAR:</span>
              <span class="text-emerald-700">Rp {{ formatRupiah(activeKuitansi?.total_bayar) }}</span>
            </div>

            <div class="text-center pt-2 text-[10px] text-slate-400 border-t border-dashed border-slate-300">
              <p>{{ activeKuitansi?.catatan_kuitansi || 'Terima kasih atas pembayaran Anda.' }}</p>
              <p class="font-bold text-slate-600 mt-1">Kasir: {{ activeKuitansi?.kasir || 'Bendahara' }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2 pt-2">
            <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition flex-1" @click="isKuitansiModalOpen = false">
              Tutup
            </button>
            <button type="button" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm transition flex-1 flex items-center justify-center gap-1.5" @click="printKuitansi">
              <i class="bi bi-printer"></i> Cetak Kuitansi
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
