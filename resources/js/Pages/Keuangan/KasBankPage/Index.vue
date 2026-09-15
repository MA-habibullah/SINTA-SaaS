<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js'
import axios from 'axios'

// === ZERO-SSR: Props shell kosong ===
const props = defineProps({
  isSuperAdmin: { type: Boolean, default: false },
  kasList:      { type: Array, default: null },
  tenantsList:  { type: Array, default: null },
})

// State lokal reaktif
const localKasList     = ref([])
const localTenantsList = ref([])
const isLoadingInit    = ref(false)
const activeTenantId   = ref('')

// State Jurnal Kas
const selectedKasId    = ref('')
const jurnalList       = ref({ data: [], total: 0 })
const selectedKasData  = ref(null)
const isLoadingJurnal  = ref(false)
const filterTanggalDari = ref('')
const filterTanggalSampai = ref('')

// State Modal CRUD Kas
const isModalOpen  = ref(false)
const isEditing    = ref(false)
const kasForm      = ref({ kode_kas: '', nama_kas: '', nomor_rekening: '', atas_nama: '', saldo_awal: 0, is_active: true })
const editingKasId = ref('')
const isSaving     = ref(false)

// Toast
const toast = ref({ show: false, message: '', type: 'success' })
function showToast(msg, type = 'success') {
  toast.value = { show: true, message: msg, type }
  setTimeout(() => { toast.value.show = false }, 4000)
}

// Memory security
useMemorySecurity([jurnalList, localKasList])

// =============================================
// ASYNC DATA LOADING
// =============================================
async function loadInitialData(tenantId = '') {
  isLoadingInit.value = true
  try {
    const headers = {}
    if (tenantId) headers['X-Tenant-Id'] = tenantId
    const res = await axios.get('/keuangan/kas-bank', { params: { async: 1 }, headers })
    if (res.data?.success) {
      const d = res.data.data
      localKasList.value    = d.kasList    || []
      localTenantsList.value = d.tenantsList || []
      // Auto-select kas pertama
      if (!selectedKasId.value && localKasList.value.length > 0) {
        selectedKasId.value = localKasList.value[0].id
        loadJurnalKas(localKasList.value[0].id)
      }
    }
  } catch (err) {
    showToast('Gagal memuat data kas bank.', 'error')
  } finally {
    isLoadingInit.value = false
  }
}

async function loadJurnalKas(kasId) {
  if (!kasId) return
  isLoadingJurnal.value = true
  selectedKasId.value = kasId
  selectedKasData.value = localKasList.value.find(k => k.id === kasId)
  try {
    const res = await axios.get(`/keuangan/kas-bank/${kasId}/jurnal`, {
      params: {
        tanggal_dari:    filterTanggalDari.value || undefined,
        tanggal_sampai:  filterTanggalSampai.value || undefined,
      }
    })
    if (res.data?.success) {
      jurnalList.value   = res.data.jurnal  || { data: [], total: 0 }
    }
  } catch (err) {
    showToast('Gagal memuat jurnal kas.', 'error')
  } finally {
    isLoadingJurnal.value = false
  }
}

onMounted(() => { loadInitialData() })

// Tenant switching (in-memory)
const tenantOptions = computed(() => [
  { id: '', label: '-- Semua Sekolah --', subLabel: 'Agregat Platform' },
  ...localTenantsList.value.map(t => ({ id: t.id, label: t.nama_sekolah }))
])

function onTenantSwitch(newTenantId) {
  activeTenantId.value = newTenantId
  selectedKasId.value = ''
  jurnalList.value = { data: [] }
  loadInitialData(newTenantId)
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
  return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// =============================================
// CRUD KAS BANK
// =============================================
function openAddModal() {
  isEditing.value = false
  editingKasId.value = ''
  kasForm.value = { kode_kas: '', nama_kas: '', nomor_rekening: '', atas_nama: '', saldo_awal: 0, is_active: true }
  isModalOpen.value = true
}

function openEditModal(kas) {
  isEditing.value = true
  editingKasId.value = kas.id
  kasForm.value = { kode_kas: kas.kode_kas, nama_kas: kas.nama_kas, nomor_rekening: kas.nomor_rekening || '', atas_nama: kas.atas_nama || '', saldo_awal: kas.saldo_awal, is_active: kas.is_active }
  isModalOpen.value = true
}

async function saveKas() {
  isSaving.value = true
  try {
    let res
    if (isEditing.value) {
      res = await axios.put(`/keuangan/kas-bank/${editingKasId.value}`, kasForm.value)
    } else {
      res = await axios.post('/keuangan/kas-bank', kasForm.value)
    }
    if (res.data?.success) {
      showToast(res.data.message || 'Rekening kas berhasil disimpan.')
      isModalOpen.value = false
      loadInitialData(activeTenantId.value)
    }
  } catch (err) {
    const errors = err.response?.data?.errors
    const msg = errors ? Object.values(errors).flat().join(', ') : (err.response?.data?.message || 'Gagal menyimpan kas.')
    showToast(msg, 'error')
  } finally {
    isSaving.value = false
  }
}

async function deleteKas(kasId, namaKas) {
  if (!confirm(`Hapus rekening "${namaKas}"? Aksi ini tidak dapat dibatalkan.`)) return
  try {
    const res = await axios.delete(`/keuangan/kas-bank/${kasId}`)
    if (res.data?.success) {
      showToast('Rekening kas berhasil dihapus.')
      if (selectedKasId.value === kasId) { selectedKasId.value = ''; jurnalList.value = { data: [] } }
      loadInitialData(activeTenantId.value)
    }
  } catch (err) {
    showToast(err.response?.data?.message || 'Gagal menghapus kas.', 'error')
  }
}

const totalSaldoSemua = computed(() => localKasList.value.reduce((sum, k) => sum + Number(k.saldo_saat_ini || 0), 0))
</script>

<template>
  <AppLayout title="Buku Kas & Rekening Bank">
    <Head title="Buku Kas & Rekening Bank" />

    <!-- Toast -->
    <transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
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
          <h1 class="text-2xl font-black text-slate-800 tracking-tight">Buku Kas & Rekening Bank</h1>
          <p class="text-xs text-slate-500 mt-1">Kelola akun kas sekolah, pantau saldo real-time, dan baca jurnal transaksi masuk.</p>
        </div>
        <div class="flex items-center gap-2">
          <a href="/keuangan/kasir" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-2">
            <i class="bi bi-cash-stack"></i> Loket Kasir
          </a>
          <button type="button" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-sm transition flex items-center gap-2 active:scale-95" @click="openAddModal">
            <i class="bi bi-plus-lg"></i> Tambah Rekening Kas
          </button>
        </div>
      </div>

      <!-- Super Admin Tenant Filter -->
      <div v-if="isSuperAdmin" class="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50/90 to-slate-50 border-l-4 border-l-blue-600 p-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm shrink-0"><i class="bi bi-bank text-lg"></i></div>
            <div>
              <div class="font-bold text-slate-800 text-sm">Rekening Kas Sekolah</div>
              <p class="text-xs text-slate-500 mt-0.5">Pilih sekolah untuk melihat kas dan jurnal transaksinya.</p>
            </div>
          </div>
          <div class="w-full md:w-72 shrink-0">
            <SearchableSelect v-model="activeTenantId" :options="tenantOptions" placeholder="Pilih sekolah..." @change="onTenantSwitch" />
          </div>
        </div>
      </div>

      <!-- Stat Total Saldo -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-2xl p-5 shadow-sm col-span-full sm:col-span-1">
          <div class="text-xs font-bold opacity-80 uppercase tracking-wider">Total Saldo Semua Kas</div>
          <div class="text-2xl font-black mt-1 font-mono">Rp {{ formatRupiah(totalSaldoSemua) }}</div>
          <div class="text-[11px] opacity-60 mt-1">{{ localKasList.length }} rekening aktif</div>
        </div>

        <div v-for="kas in localKasList.slice(0, 4)" :key="kas.id"
          class="bg-white rounded-2xl border p-4 shadow-sm cursor-pointer transition-all hover:shadow-md"
          :class="selectedKasId === kas.id ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200'"
          @click="loadJurnalKas(kas.id)">
          <div class="flex items-start justify-between">
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-base shrink-0">
              <i class="bi bi-bank"></i>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="kas.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
              {{ kas.is_active ? 'Aktif' : 'Nonaktif' }}
            </span>
          </div>
          <div class="mt-3">
            <div class="font-black text-slate-900 text-sm">{{ kas.nama_kas }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">{{ kas.nomor_rekening || 'Kas Tunai' }}</div>
            <div class="text-lg font-black text-blue-700 font-mono mt-2">Rp {{ formatRupiah(kas.saldo_saat_ini) }}</div>
          </div>
          <div class="flex gap-1 mt-3 pt-2 border-t border-slate-100">
            <button type="button" class="text-xs text-blue-600 hover:underline font-bold" @click.stop="openEditModal(kas)">Edit</button>
            <span class="text-slate-300">|</span>
            <button type="button" class="text-xs text-rose-500 hover:underline font-bold" @click.stop="deleteKas(kas.id, kas.nama_kas)">Hapus</button>
          </div>
        </div>
      </div>

      <!-- Jurnal Transaksi Kas Terpilih -->
      <div v-if="selectedKasId" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-journal-text text-blue-600"></i>
              Jurnal Transaksi — {{ selectedKasData?.nama_kas || 'Kas Terpilih' }}
            </h3>
            <div class="flex items-center gap-2 text-xs">
              <input type="date" v-model="filterTanggalDari" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" placeholder="Dari" />
              <span class="text-slate-400">s/d</span>
              <input type="date" v-model="filterTanggalSampai" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" placeholder="Sampai" />
              <button type="button" class="px-3 py-2 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-500 transition flex items-center gap-1" @click="loadJurnalKas(selectedKasId)">
                <i class="bi bi-search"></i> Filter
              </button>
            </div>
          </div>
        </div>

        <div v-if="isLoadingJurnal" class="py-12 text-center text-slate-400 text-xs">
          <div class="inline-block animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
          <p>Memuat jurnal kas...</p>
        </div>

        <div v-else-if="!jurnalList?.data?.length" class="py-12 text-center text-slate-400 text-xs">
          <i class="bi bi-journal text-3xl mb-2 block"></i>
          <p class="font-bold text-slate-700">Belum Ada Transaksi</p>
          <p class="text-slate-400">Kas ini belum memiliki riwayat transaksi pembayaran.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3 px-4">No. Transaksi & Tanggal</th>
                <th class="py-3 px-4">Siswa</th>
                <th class="py-3 px-4">Pos Biaya</th>
                <th class="py-3 px-4">Metode & Kasir</th>
                <th class="py-3 px-4 text-right">Nominal Masuk</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="trx in jurnalList.data" :key="trx.id" class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4">
                  <div class="font-mono font-bold text-blue-700">{{ trx.nomor_transaksi }}</div>
                  <div class="text-[10px] text-slate-400">{{ formatDate(trx.tanggal_bayar) }}</div>
                </td>
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900">{{ trx.siswa?.nama_lengkap || '-' }}</div>
                  <div class="text-[10px] text-slate-400">NISN: {{ trx.siswa?.nisn || '-' }}</div>
                </td>
                <td class="py-3 px-4 font-semibold text-slate-700">{{ trx.tagihan?.pos?.nama_pos || '-' }}</td>
                <td class="py-3 px-4 text-slate-500">
                  {{ trx.metode_pembayaran }} &bull; <span class="text-[10px]">{{ trx.kasir?.nama_lengkap || 'Sistem' }}</span>
                </td>
                <td class="py-3 px-4 text-right font-mono font-black text-emerald-600">
                  Rp {{ formatRupiah(trx.nominal_bayar) }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-emerald-50/60 border-t-2 border-emerald-200">
                <td colspan="4" class="py-3 px-4 text-xs font-black text-slate-800 text-right">TOTAL PENERIMAAN KAS INI:</td>
                <td class="py-3 px-4 text-right font-mono font-black text-emerald-700 text-sm">
                  Rp {{ formatRupiah(selectedKasData?.saldo_saat_ini) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Tambah/Edit Rekening Kas -->
    <Teleport to="body">
      <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-md w-full p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-bank text-blue-600"></i>
              {{ isEditing ? 'Edit Rekening Kas' : 'Tambah Rekening Kas Baru' }}
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-700 text-xl font-bold" @click="isModalOpen = false">&times;</button>
          </div>

          <div class="space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Kode Kas <span class="text-rose-500">*</span></label>
                <input v-model="kasForm.kode_kas" type="text" placeholder="Misal: KAS-001" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Kas <span class="text-rose-500">*</span></label>
                <input v-model="kasForm.nama_kas" type="text" placeholder="Misal: Kas Tunai Bendahara" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Nomor Rekening (Opsional)</label>
              <input v-model="kasForm.nomor_rekening" type="text" placeholder="Nomor rekening bank..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Atas Nama (Opsional)</label>
              <input v-model="kasForm.atas_nama" type="text" placeholder="Nama pemilik rekening..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div v-if="!isEditing">
              <label class="block font-bold text-slate-700 mb-1">Saldo Awal (Rp)</label>
              <input v-model.number="kasForm.saldo_awal" type="number" min="0" placeholder="0" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="kasForm.is_active" type="checkbox" class="w-4 h-4 rounded text-blue-600" />
              <span class="font-bold text-slate-700">Rekening Aktif</span>
            </label>
          </div>

          <div class="flex gap-3 pt-2 border-t border-slate-100">
            <button type="button" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition" @click="isModalOpen = false">Batal</button>
            <button type="button"
              class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-sm transition flex items-center justify-center gap-2 disabled:opacity-50"
              :disabled="isSaving || !kasForm.kode_kas || !kasForm.nama_kas"
              @click="saveKas">
              <i class="bi bi-save"></i>
              {{ isSaving ? 'Menyimpan...' : (isEditing ? 'Perbarui Rekening' : 'Simpan Rekening') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
