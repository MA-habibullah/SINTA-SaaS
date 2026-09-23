<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'

const props = defineProps({
  activeTab: { type: String, default: 'pemasukan' },
  pemasukanList: { type: Object, default: () => ({ data: [] }) },
  totalPemasukanNominal: { type: Number, default: 0 },
  tunggakanList: { type: Object, default: () => ({ data: [] }) },
  totalTunggakanNominal: { type: Number, default: 0 },
  posList: { type: Array, default: () => [] },
  kelasList: { type: Array, default: () => [] },
  kasList: { type: Array, default: () => [] },
  pengaturan: { type: Object, default: () => ({}) },
  isSuperAdmin: { type: Boolean, default: false },
  tenantsList: { type: Array, default: () => [] },
  selectedTenantId: { type: String, default: '' },
  allowed_tabs: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

function formatRupiah(val) {
  if (!val || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}

const allTabs = [
  { id: 'pemasukan', label: '1. Rekap Pemasukan Kas (Buku Kas Umum)', icon: 'bi-wallet2', color: 'emerald', total: () => `Rp ${formatRupiah(props.totalPemasukanNominal)}` },
  { id: 'tunggakan', label: '2. Rekap Tunggakan & Surat Tagihan Ortu', icon: 'bi-exclamation-octagon', color: 'rose', total: () => `Rp ${formatRupiah(props.totalTunggakanNominal)}` },
]

const availableTabs = computed(() => {
  if (!props.allowed_tabs || props.allowed_tabs.length === 0) return allTabs
  return allTabs.filter(t => props.allowed_tabs.includes(t.id))
})

const currentTenantId = ref(props.selectedTenantId || '')

const currentTab = ref(props.activeTab || 'pemasukan')

onMounted(() => {
  if (props.allowed_tabs && props.allowed_tabs.length > 0 && !props.allowed_tabs.includes(currentTab.value)) {
    currentTab.value = props.allowed_tabs[0]
  }
})

const tenantOptions = computed(() => [
  { value: '', label: '-- Semua Sekolah (Agregat Global) --', sublabel: 'Tampilkan seluruh tenant' },
  ...props.tenantsList.map(t => ({
    value: t.id,
    label: t.nama_sekolah,
    sublabel: 'Tenant ID: ' + t.id.substring(0, 8) + '...'
  }))
])

const onTenantChange = (tId) => {
  router.get('/keuangan/laporan', {
    tenant_id: tId || undefined,
    tab: currentTab.value,
    date_from: filterDateFrom.value || undefined,
    date_to: filterDateTo.value || undefined,
    pos_id: filterPosId.value || undefined,
    kelas_id: filterKelasId.value || undefined,
    kas_id: filterKasId.value || undefined,
    metode: filterMetode.value || undefined,
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

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Filters
const filterDateFrom = ref(props.filters.date_from || '')
const filterDateTo = ref(props.filters.date_to || '')
const filterPosId = ref(props.filters.pos_id || '')
const filterKelasId = ref(props.filters.kelas_id || '')
const filterKasId = ref(props.filters.kas_id || '')
const filterMetode = ref(props.filters.metode || '')

const posFilterOptions = computed(() => [
  { id: '', label: 'Semua Pos Biaya' },
  ...props.posList.map(p => ({ id: p.id, label: p.nama_pos }))
])

const kelasFilterOptions = computed(() => [
  { id: '', label: 'Semua Kelas' },
  ...props.kelasList.map(k => ({ id: k.id, label: k.nama_kelas }))
])

const kasFilterOptions = computed(() => [
  { id: '', label: 'Semua Akun Kas/Bank' },
  ...props.kasList.map(k => ({ id: k.id, label: k.nama_kas }))
])

const metodeFilterOptions = [
  { id: '', label: 'Semua Metode Bayar' },
  { id: 'Tunai', label: 'Tunai' },
  { id: 'Transfer Bank', label: 'Transfer Bank' },
  { id: 'QRIS', label: 'QRIS' },
  { id: 'Midtrans VA', label: 'Midtrans Virtual Account' },
]

function applyFilters() {
  router.get('/keuangan/laporan', {
    tab: currentTab.value,
    date_from: filterDateFrom.value || undefined,
    date_to: filterDateTo.value || undefined,
    pos_id: filterPosId.value || undefined,
    kelas_id: filterKelasId.value || undefined,
    kas_id: filterKasId.value || undefined,
    metode: filterMetode.value || undefined,
  }, { preserveState: true, replace: true })
}

function printReport() {
  window.print()
}

// Surat Tagihan State
const selectedSuratSiswa = ref(null)
const isSuratModalOpen = ref(false)

function openSuratTagihan(siswaItem) {
  selectedSuratSiswa.value = siswaItem
  isSuratModalOpen.value = true
}
</script>

<template>
  <AppLayout title="Laporan Keuangan & Rekapitulasi Pembukuan">
    <Head title="Laporan Keuangan & Rekapitulasi Pembukuan" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-800 tracking-tight">Laporan Keuangan & Pembukuan Kas</h1>
          <p class="text-xs text-slate-500 mt-1">Buku kas umum penerimaan harian, rekapitulasi tunggakan, dan cetak surat pemberitahuan orang tua.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-2"
            @click="printReport"
          >
            <i class="bi bi-printer"></i> Cetak Laporan
          </button>
          <button
            type="button"
            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition flex items-center gap-2"
            @click="router.visit('/keuangan/kasir')"
          >
            <i class="bi bi-cash-stack"></i> Loket Kasir
          </button>
        </div>
      </div>

      <!-- Section: Banner Filter Tenant (Khusus Super Admin) -->
      <div v-if="isSuperAdmin" class="rounded-2xl shadow-xs border border-blue-200 bg-gradient-to-r from-blue-50/90 via-indigo-50/80 to-slate-50 border-l-4 border-l-blue-600 p-4 transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs shrink-0">
              <i class="bi bi-building text-lg"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-sm">Filter Laporan Pembukuan Sekolah</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 border border-blue-200">
                  <i class="bi bi-shield-lock-fill me-1 text-blue-600"></i> Super Admin Mode
                </span>
              </div>
              <p class="text-xs text-slate-600 mt-0.5">
                Melihat pembukuan BKU & rekap tunggakan milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
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

      <!-- Modern Dynamic NavTabs -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2">
        <ul class="flex border-0 gap-2 select-none" role="tablist">
          <li v-for="t in availableTabs" :key="t.id" class="flex-1">
            <button
              type="button"
              class="w-full border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-2"
              :class="currentTab === t.id ? (t.id === 'pemasukan' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-rose-600 text-white shadow-xs') : 'text-slate-600 hover:bg-slate-100'"
              @click="currentTab = t.id; applyFilters()"
            >
              <i :class="['bi', t.icon, 'text-sm']"></i>
              <span>{{ t.label }}</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="currentTab === t.id ? (t.id === 'pemasukan' ? 'bg-emerald-700 text-white' : 'bg-rose-700 text-white') : 'bg-slate-200 text-slate-700'">
                {{ t.total() }}
              </span>
            </button>
          </li>
        </ul>
      </div>

      <!-- Filter Bar -->
      <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Dari Tanggal</label>
            <input
              type="date"
              v-model="filterDateFrom"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-hidden"
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Sampai Tanggal</label>
            <input
              type="date"
              v-model="filterDateTo"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-hidden"
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Filter Pos Biaya</label>
            <SearchableSelect
              v-model="filterPosId"
              :options="posFilterOptions"
              placeholder="Semua Pos..."
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Filter Rombel Kelas</label>
            <SearchableSelect
              v-model="filterKelasId"
              :options="kelasFilterOptions"
              placeholder="Semua Kelas..."
              @change="applyFilters"
            />
          </div>

          <div v-if="currentTab === 'pemasukan'">
            <label class="block font-bold text-slate-700 mb-1">Akun Kas / Bank</label>
            <SearchableSelect
              v-model="filterKasId"
              :options="kasFilterOptions"
              placeholder="Semua Kas..."
              @change="applyFilters"
            />
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 1: REKAP PEMASUKAN KAS -->
      <!-- ============================================================== -->
      <div v-if="currentTab === 'pemasukan'" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3.5 px-4">No. Transaksi & Tanggal</th>
                <th class="py-3.5 px-4">Nama Siswa & Kelas</th>
                <th class="py-3.5 px-4">Pos Pembayaran</th>
                <th class="py-3.5 px-4">Akun Kas / Bank</th>
                <th class="py-3.5 px-4">Metode Bayar</th>
                <th class="py-3.5 px-4">Kasir / Petugas</th>
                <th class="py-3.5 px-4 text-right">Nominal Masuk</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="!pemasukanList.data || pemasukanList.data.length === 0">
                <td colspan="7" class="py-14 text-center text-slate-400">
                  <div class="w-14 h-14 mx-auto rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-2">
                    <i class="bi bi-inbox"></i>
                  </div>
                  <p class="font-bold text-slate-700">Tidak Ada Transaksi Pemasukan</p>
                  <p class="text-xs text-slate-400">Tidak ada data transaksi pada rentang tanggal terpilih.</p>
                </td>
              </tr>

              <tr v-for="p in pemasukanList.data" :key="p.id" class="hover:bg-slate-50 transition">
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="font-mono font-bold text-blue-700">{{ p.nomor_transaksi }}</div>
                  <div class="text-[10px] text-slate-400">{{ formatDate(p.tanggal_bayar) }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800">{{ p.siswa?.nama_lengkap || '-' }}</div>
                  <div class="text-[10px] text-slate-400">{{ p.siswa?.kelas?.nama_kelas || '-' }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-800">{{ p.tagihan?.pos?.nama_pos || '-' }}</span>
                </td>
                <td class="py-3.5 px-4 text-slate-600 font-medium">
                  {{ p.kas?.nama_kas || 'Kas' }}
                </td>
                <td class="py-3.5 px-4">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 border text-slate-700">
                    {{ p.metode_pembayaran }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-slate-500">{{ p.kasir?.nama_lengkap || 'Kasir' }}</td>
                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 text-sm">
                  Rp {{ formatRupiah(p.nominal_bayar) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pemasukanList.last_page > 1" class="py-3.5 px-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <div>Halaman {{ pemasukanList.current_page }} dari {{ pemasukanList.last_page }}</div>
          <div class="flex items-center gap-1.5">
            <button
              type="button"
              class="px-3 py-1 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              :disabled="pemasukanList.current_page <= 1"
              @click="router.visit(pemasukanList.prev_page_url)"
            >
              Sebelumnya
            </button>
            <button
              type="button"
              class="px-3 py-1 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              :disabled="pemasukanList.current_page >= pemasukanList.last_page"
              @click="router.visit(pemasukanList.next_page_url)"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 2: REKAP TUNGGAKAN & SURAT TAGIHAN -->
      <!-- ============================================================== -->
      <div v-if="currentTab === 'tunggakan'" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3.5 px-4">Nama Peserta Didik</th>
                <th class="py-3.5 px-4">Rombel Kelas</th>
                <th class="py-3.5 px-4">Pos Pembayaran</th>
                <th class="py-3.5 px-4 text-right">Total Tagihan</th>
                <th class="py-3.5 px-4 text-right">Terbayar</th>
                <th class="py-3.5 px-4 text-right">Sisa Tunggakan</th>
                <th class="py-3.5 px-4 text-center">Status</th>
                <th class="py-3.5 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="!tunggakanList.data || tunggakanList.data.length === 0">
                <td colspan="8" class="py-14 text-center text-slate-400">
                  <div class="w-14 h-14 mx-auto rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-2">
                    <i class="bi bi-check-circle-fill"></i>
                  </div>
                  <p class="font-bold text-slate-800">Tidak Ada Data Tunggakan</p>
                  <p class="text-xs text-slate-400">Seluruh kewajiban pembayaran telah lunas.</p>
                </td>
              </tr>

              <tr v-for="t in tunggakanList.data" :key="t.id" class="hover:bg-slate-50 transition">
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800">{{ t.siswa?.nama_lengkap || '-' }}</div>
                  <div class="text-[10px] text-slate-400">NISN: {{ t.siswa?.nisn || '-' }} &bull; Ortu: {{ t.siswa?.nama_ayah || '-' }}</div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-700">{{ t.siswa?.kelas?.nama_kelas || '-' }}</td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-800">{{ t.pos?.nama_pos || '-' }}</span>
                  <span v-if="t.bulan" class="text-slate-400 block text-[10px]">Bulan {{ t.bulan }}/{{ t.tahun }}</span>
                </td>
                <td class="py-3.5 px-4 text-right font-mono">Rp {{ formatRupiah(t.total_tagihan) }}</td>
                <td class="py-3.5 px-4 text-right font-mono text-emerald-600 font-bold">Rp {{ formatRupiah(t.total_terbayar) }}</td>
                <td class="py-3.5 px-4 text-right font-mono text-rose-600 font-black text-sm">
                  Rp {{ formatRupiah(t.sisa_tagihan) }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    {{ t.status_pembayaran }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <button
                    type="button"
                    class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-[11px] transition shadow-xs inline-flex items-center gap-1.5"
                    @click="openSuratTagihan(t)"
                  >
                    <i class="bi bi-file-earmark-text"></i> Cetak Surat Tagihan
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="tunggakanList.last_page > 1" class="py-3.5 px-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <div>Halaman {{ tunggakanList.current_page }} dari {{ tunggakanList.last_page }}</div>
          <div class="flex items-center gap-1.5">
            <button
              type="button"
              class="px-3 py-1 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              :disabled="tunggakanList.current_page <= 1"
              @click="router.visit(tunggakanList.prev_page_url)"
            >
              Sebelumnya
            </button>
            <button
              type="button"
              class="px-3 py-1 rounded-lg border bg-white font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              :disabled="tunggakanList.current_page >= tunggakanList.last_page"
              @click="router.visit(tunggakanList.next_page_url)"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL SURAT PEMBERITAHUAN TUNGGAKAN (TELEPORT TO BODY) -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isSuratModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-2xl w-full p-6 sm:p-8 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-envelope-paper-fill text-blue-600"></i> Surat Pemberitahuan Kewajiban Keuangan
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none" @click="isSuratModalOpen = false">&times;</button>
          </div>

          <!-- Preview Lembar Surat Resmi -->
          <div id="printAreaSurat" class="p-6 bg-slate-50 rounded-2xl border border-slate-200 text-xs font-serif space-y-4">
            <div class="text-center border-b-2 border-slate-800 pb-3">
              <h4 class="font-black text-sm uppercase tracking-wider text-slate-900">SURAT PEMBERITAHUAN ADMINISTRASI KEUANGAN</h4>
              <p class="text-[10px] font-sans text-slate-500">Nomor: 421/SPP/{{ new Date().getFullYear() }} &bull; Tanggal: {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>

            <div class="space-y-1 text-slate-700 font-sans">
              <p>Kepada Yth.</p>
              <p class="font-bold">Orang Tua / Wali dari Ananda: {{ selectedSuratSiswa?.siswa?.nama_lengkap }}</p>
              <p>Kelas: {{ selectedSuratSiswa?.siswa?.kelas?.nama_kelas || '-' }} (NISN: {{ selectedSuratSiswa?.siswa?.nisn || '-' }})</p>
            </div>

            <p class="font-sans leading-relaxed text-slate-700">
              Dengan hormat, kami sampaikan informasi mengenai administrasi pembiayaan pendidikan peserta didik di sekolah kami. Berdasarkan data pembukuan kami, terdapat kewajiban pembayaran yang belum terselesaikan dengan rincian sebagai berikut:
            </p>

            <div class="bg-white p-4 rounded-xl border border-slate-200 font-sans space-y-2">
              <div class="flex justify-between font-bold border-b pb-1">
                <span>Rincian Pos Biaya</span>
                <span>Sisa Kewajiban</span>
              </div>
              <div class="flex justify-between">
                <span>{{ selectedSuratSiswa?.pos?.nama_pos }} {{ selectedSuratSiswa?.bulan ? ('(Bulan ' + selectedSuratSiswa?.bulan + '/' + selectedSuratSiswa?.tahun + ')') : '' }}</span>
                <span class="font-bold text-rose-600 font-mono">Rp {{ formatRupiah(selectedSuratSiswa?.sisa_tagihan) }}</span>
              </div>
            </div>

            <p class="font-sans leading-relaxed text-slate-700">
              Demi kelancaran proses kegiatan belajar mengajar, kami memohon kesediaan Bapak/Ibu untuk dapat melunasi kewajiban tersebut melalui loket kasir Tata Usaha sekolah atau melalui transfer online resmi.
            </p>

            <div class="flex justify-between pt-6 font-sans text-slate-700">
              <div></div>
              <div class="text-center">
                <p>Bendahara Sekolah,</p>
                <div class="h-14"></div>
                <p class="font-bold underline">{{ pengaturan?.nama_bendahara || 'Bendahara' }}</p>
                <p class="text-[10px] text-slate-400">NIP: {{ pengaturan?.nip_bendahara || '-' }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
            <button
              type="button"
              class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition flex-1"
              @click="isSuratModalOpen = false"
            >
              Tutup
            </button>
            <button
              type="button"
              class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition flex-1 flex items-center justify-center gap-1.5"
              @click="printReport"
            >
              <i class="bi bi-printer"></i> Cetak Surat Resmi
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
