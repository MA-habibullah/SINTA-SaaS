<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'

const props = defineProps({
  tagihanList: { type: Object, default: () => ({ data: [] }) },
  posList: { type: Array, default: () => [] },
  kelasList: { type: Array, default: () => [] },
  tahunAjaranList: { type: Array, default: () => [] },
  isSuperAdmin: { type: Boolean, default: false },
  tenantsList: { type: Array, default: () => [] },
  selectedTenantId: { type: String, default: '' },
  filters: { type: Object, default: () => ({}) },
})

const currentTenantId = ref(props.selectedTenantId || '')

const tenantOptions = computed(() => [
  { value: '', label: '-- Semua Sekolah (Agregat Global) --', sublabel: 'Tampilkan seluruh tenant' },
  ...props.tenantsList.map(t => ({
    value: t.id,
    label: t.nama_sekolah,
    sublabel: 'Tenant ID: ' + t.id.substring(0, 8) + '...'
  }))
])

const onTenantChange = (tId) => {
  router.get('/keuangan/tagihan', {
    tenant_id: tId || undefined,
    search: searchQuery.value || undefined,
    status: selectedStatus.value || undefined,
    pos_id: selectedPosId.value || undefined,
    kelas_id: selectedKelasId.value || undefined,
    bulan: selectedBulan.value || undefined,
    tahun: selectedTahun.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

const getSelectedTenantName = () => {
  if (!currentTenantId.value) return 'Semua Sekolah (Agregat Platform)'
  const found = props.tenantsList.find(t => t.id === currentTenantId.value)
  return found ? found.nama_sekolah : 'Sekolah Terpilih'
}

const toast = ref({ show: false, message: '', type: 'success' })
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
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

// Filter States
const searchQuery = ref(props.filters.search || '')
const selectedStatus = ref(props.filters.status || '')
const selectedPosId = ref(props.filters.pos_id || '')
const selectedKelasId = ref(props.filters.kelas_id || '')
const selectedBulan = ref(props.filters.bulan || '')
const selectedTahun = ref(props.filters.tahun || '')

const posFilterOptions = computed(() => [
  { id: '', label: 'Semua Pos Biaya' },
  ...props.posList.map(p => ({ id: p.id, label: p.nama_pos, subLabel: p.tipe_periode }))
])

const kelasFilterOptions = computed(() => [
  { id: '', label: 'Semua Kelas' },
  ...props.kelasList.map(k => ({ id: k.id, label: k.nama_kelas, subLabel: `Tingkat ${k.tingkat || '-'}` }))
])

const statusFilterOptions = [
  { id: '', label: 'Semua Status' },
  { id: 'Belum Bayar', label: 'Belum Bayar' },
  { id: 'Sebagian', label: 'Sebagian (Cicilan)' },
  { id: 'Lunas', label: 'Lunas' },
]

const bulanFilterOptions = [
  { id: '', label: 'Semua Bulan' },
  { id: '7', label: 'Juli' },
  { id: '8', label: 'Agustus' },
  { id: '9', label: 'September' },
  { id: '10', label: 'Oktober' },
  { id: '11', label: 'November' },
  { id: '12', label: 'Desember' },
  { id: '1', label: 'Januari' },
  { id: '2', label: 'Februari' },
  { id: '3', label: 'Maret' },
  { id: '4', label: 'April' },
  { id: '5', label: 'Mei' },
  { id: '6', label: 'Juni' },
]

function applyFilters() {
  router.get('/keuangan/tagihan', {
    search: searchQuery.value || undefined,
    status: selectedStatus.value || undefined,
    pos_id: selectedPosId.value || undefined,
    kelas_id: selectedKelasId.value || undefined,
    bulan: selectedBulan.value || undefined,
    tahun: selectedTahun.value || undefined,
  }, { preserveState: true, replace: true })
}

function resetFilters() {
  searchQuery.value = ''
  selectedStatus.value = ''
  selectedPosId.value = ''
  selectedKelasId.value = ''
  selectedBulan.value = ''
  selectedTahun.value = ''
  applyFilters()
}

// Generate Invoices Modal
const isGenerateModalOpen = ref(false)
const generateForm = useForm({
  pos_id: props.posList[0]?.id || '',
  tahun_ajaran_id: props.tahunAjaranList[0]?.id || '',
  target_tipe: 'all', // 'all', 'tingkat', 'kelas'
  tingkat: 'X',
  kelas_id: '',
  bulan: new Date().getMonth() + 1,
  tahun: new Date().getFullYear(),
  tanggal_jatuh_tempo: '',
})

const tingkatOptions = [
  { id: 'X', label: 'Tingkat X (Kelas 10 / 7 / 1)' },
  { id: 'XI', label: 'Tingkat XI (Kelas 11 / 8 / 2)' },
  { id: 'XII', label: 'Tingkat XII (Kelas 12 / 9 / 3)' },
]

function submitGenerateTagihan() {
  generateForm.post('/keuangan/tagihan/generate', {
    onSuccess: () => {
      showToast('Penerbitan tagihan massal berhasil diproses!')
      isGenerateModalOpen.value = false
    },
    onError: (err) => {
      const msg = err.message || 'Gagal menerbitkan tagihan.'
      showToast(msg, 'error')
    }
  })
}

function deleteTagihan(id, nama) {
  if (!confirm(`Hapus tagihan ${nama}? Tagihan hanya dapat dihapus jika belum ada pembayaran.`)) return
  router.delete(`/keuangan/tagihan/${id}`, {
    onSuccess: () => showToast('Tagihan berhasil dihapus.'),
    onError: (err) => showToast(err.response?.data?.message || 'Gagal menghapus tagihan.', 'error')
  })
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
          <p class="text-xs text-slate-500 mt-1">Daftar invoice penagihan SPP dan penerbitan tagihan secara massal (*batch invoicing*).</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-2"
            @click="router.visit('/keuangan/kasir')"
          >
            <i class="bi bi-cash-stack"></i> Loket Kasir
          </button>
          <button
            type="button"
            class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 active:scale-95"
            @click="isGenerateModalOpen = true"
          >
            <i class="bi bi-magic"></i> Terbitkan Tagihan Massal
          </button>
        </div>
      </div>

      <!-- Section: Banner Filter Tenant (Khusus Super Admin) -->
      <div v-if="isSuperAdmin" class="rounded-2xl shadow-xs border border-purple-200 bg-gradient-to-r from-purple-50/90 via-indigo-50/80 to-slate-50 border-l-4 border-l-purple-600 p-4 transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-xs shrink-0">
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
                Mengelola daftar tagihan & invoice milik: <strong class="text-purple-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
              </p>
            </div>
          </div>

          <div class="w-full md:w-80 shrink-0">
            <SearchableSelect
              v-model="currentTenantId"
              :options="tenantOptions"
              placeholder="Pilih atau cari sekolah..."
              searchPlaceholder="Ketik nama sekolah..."
              @update:modelValue="onTenantChange"
            />
          </div>
        </div>
      </div>

      <!-- Filter Card -->
      <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Cari Siswa / No. Invoice</label>
            <input
              type="text"
              v-model="searchQuery"
              @keyup.enter="applyFilters"
              placeholder="Nama / NISN / Invoice..."
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Pos Biaya</label>
            <SearchableSelect
              v-model="selectedPosId"
              :options="posFilterOptions"
              placeholder="Semua Pos..."
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Rombel Kelas</label>
            <SearchableSelect
              v-model="selectedKelasId"
              :options="kelasFilterOptions"
              placeholder="Semua Kelas..."
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Status Pembayaran</label>
            <SearchableSelect
              v-model="selectedStatus"
              :options="statusFilterOptions"
              placeholder="Semua Status..."
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Bulan Tagihan</label>
            <SearchableSelect
              v-model="selectedBulan"
              :options="bulanFilterOptions"
              placeholder="Semua Bulan..."
              @change="applyFilters"
            />
          </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
          <div class="text-slate-500">
            Menampilkan <strong class="text-slate-800">{{ tagihanList.total || 0 }}</strong> total tagihan terbit
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition"
              @click="resetFilters"
            >
              Reset Filter
            </button>
            <button
              type="button"
              class="px-4 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold transition shadow-xs"
              @click="applyFilters"
            >
              Terapkan
            </button>
          </div>
        </div>
      </div>

      <!-- Tabel Tagihan Siswa -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3.5 px-4">No. Invoice & Tanggal</th>
                <th class="py-3.5 px-4">Nama Peserta Didik</th>
                <th class="py-3.5 px-4">Pos Pembayaran</th>
                <th class="py-3.5 px-4 text-right">Tarif Dasar</th>
                <th class="py-3.5 px-4 text-right">Potongan</th>
                <th class="py-3.5 px-4 text-right">Total Tagihan</th>
                <th class="py-3.5 px-4 text-right">Sisa Tagihan</th>
                <th class="py-3.5 px-4 text-center">Status</th>
                <th class="py-3.5 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="!tagihanList.data || tagihanList.data.length === 0">
                <td colspan="9" class="py-14 text-center text-slate-400">
                  <div class="w-14 h-14 mx-auto rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-2">
                    <i class="bi bi-inbox"></i>
                  </div>
                  <h4 class="font-bold text-slate-700 text-sm">Tidak Ada Data Tagihan</h4>
                  <p class="text-xs text-slate-400 mt-0.5">Belum ada tagihan yang diterbitkan atau filter tidak cocok.</p>
                </td>
              </tr>

              <tr v-for="t in tagihanList.data" :key="t.id" class="hover:bg-slate-50 transition">
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="font-mono font-bold text-blue-700">{{ t.nomor_tagihan }}</div>
                  <div class="text-[10px] text-slate-400">{{ formatDate(t.created_at) }}</div>
                </td>

                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800 line-clamp-1">{{ t.siswa?.nama_lengkap || '-' }}</div>
                  <div class="text-[10px] text-slate-400">NISN: {{ t.siswa?.nisn || '-' }} &bull; {{ t.siswa?.kelas?.nama_kelas || '-' }}</div>
                </td>

                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-800">{{ t.pos?.nama_pos || '-' }}</span>
                  <span v-if="t.bulan" class="text-emerald-600 font-bold block text-[10px]">Bulan {{ t.bulan }}/{{ t.tahun }}</span>
                </td>

                <td class="py-3.5 px-4 text-right font-mono text-slate-600">Rp {{ formatRupiah(t.nominal_tarif_dasar) }}</td>
                <td class="py-3.5 px-4 text-right font-mono text-amber-600 font-bold">
                  {{ Number(t.nominal_potongan) > 0 ? ('- Rp ' + formatRupiah(t.nominal_potongan)) : '-' }}
                </td>
                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">Rp {{ formatRupiah(t.total_tagihan) }}</td>
                <td class="py-3.5 px-4 text-right font-mono font-black text-rose-600">Rp {{ formatRupiah(t.sisa_tagihan) }}</td>

                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border" :class="t.status_pembayaran === 'Lunas' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (t.status_pembayaran === 'Sebagian' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200')">
                    {{ t.status_pembayaran }}
                  </span>
                </td>

                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <button
                    v-if="t.total_terbayar <= 0"
                    type="button"
                    class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[11px] transition"
                    title="Hapus Tagihan"
                    @click="deleteTagihan(t.id, t.nomor_tagihan)"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="tagihanList.last_page > 1" class="py-3.5 px-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <div>Halaman {{ tagihanList.current_page }} dari {{ tagihanList.last_page }}</div>
          <div class="flex items-center gap-1.5">
            <button
              type="button"
              class="px-3 py-1 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              :disabled="tagihanList.current_page <= 1"
              @click="router.visit(tagihanList.prev_page_url)"
            >
              Sebelumnya
            </button>
            <button
              type="button"
              class="px-3 py-1 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              :disabled="tagihanList.current_page >= tagihanList.last_page"
              @click="router.visit(tagihanList.next_page_url)"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL GENERATE TAGIHAN MASSAL (TELEPORT TO BODY) -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isGenerateModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 sm:p-8 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="bi bi-magic text-blue-600"></i> Terbitkan Tagihan Massal
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">Generate tagihan otomatis untuk seluruh siswa sasaran.</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none" @click="isGenerateModalOpen = false">&times;</button>
          </div>

          <form @submit.prevent="submitGenerateTagihan" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Pilih Pos Biaya <span class="text-rose-500">*</span></label>
              <SearchableSelect
                v-model="generateForm.pos_id"
                :options="posFilterOptions.filter(p => p.id)"
                placeholder="-- Pilih Pos Biaya --"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Bulan Tagihan</label>
                <SearchableSelect
                  v-model="generateForm.bulan"
                  :options="bulanFilterOptions.filter(b => b.id)"
                  placeholder="Pilih Bulan..."
                />
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1">Tahun <span class="text-rose-500">*</span></label>
                <input
                  type="number"
                  v-model.number="generateForm.tahun"
                  required
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 outline-hidden transition"
                />
              </div>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Target Sasaran Distribusi</label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  type="button"
                  class="py-2 px-3 rounded-xl border font-bold text-xs transition"
                  :class="generateForm.target_tipe === 'all' ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-50 text-slate-700 border-slate-200'"
                  @click="generateForm.target_tipe = 'all'"
                >
                  Semua Siswa
                </button>
                <button
                  type="button"
                  class="py-2 px-3 rounded-xl border font-bold text-xs transition"
                  :class="generateForm.target_tipe === 'tingkat' ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-50 text-slate-700 border-slate-200'"
                  @click="generateForm.target_tipe = 'tingkat'"
                >
                  Per Tingkat
                </button>
                <button
                  type="button"
                  class="py-2 px-3 rounded-xl border font-bold text-xs transition"
                  :class="generateForm.target_tipe === 'kelas' ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-50 text-slate-700 border-slate-200'"
                  @click="generateForm.target_tipe = 'kelas'"
                >
                  Per Rombel
                </button>
              </div>
            </div>

            <div v-if="generateForm.target_tipe === 'tingkat'">
              <label class="block font-bold text-slate-700 mb-1">Pilih Tingkat Kelas</label>
              <SearchableSelect
                v-model="generateForm.tingkat"
                :options="tingkatOptions"
                placeholder="Pilih Tingkat..."
              />
            </div>

            <div v-if="generateForm.target_tipe === 'kelas'">
              <label class="block font-bold text-slate-700 mb-1">Pilih Rombel Kelas Sasaran</label>
              <SearchableSelect
                v-model="generateForm.kelas_id"
                :options="kelasFilterOptions.filter(k => k.id)"
                placeholder="Pilih Kelas..."
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tanggal Jatuh Tempo (Opsional)</label>
              <input
                type="date"
                v-model="generateForm.tanggal_jatuh_tempo"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-hidden transition"
              />
            </div>

            <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
              <button
                type="button"
                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition flex-1"
                @click="isGenerateModalOpen = false"
              >
                Batal
              </button>
              <button
                type="submit"
                class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold shadow-xs transition flex-1"
                :disabled="generateForm.processing"
              >
                {{ generateForm.processing ? 'Menerbitkan...' : 'Terbitkan Sekarang' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
