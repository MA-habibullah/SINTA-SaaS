<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import axios from 'axios'

const props = defineProps({
  posList: { type: Array, default: () => [] },
  tarifList: { type: Array, default: () => [] },
  keringananList: { type: Array, default: () => [] },
  kasList: { type: Array, default: () => [] },
  pengaturan: { type: Object, default: () => ({}) },
  kelasList: { type: Array, default: () => [] },
  jurusanList: { type: Array, default: () => [] },
  tahunAjaranList: { type: Array, default: () => [] },
  isSuperAdmin: { type: Boolean, default: false },
  tenantsList: { type: Array, default: () => [] },
  selectedTenantId: { type: String, default: '' },
  allowed_tabs: { type: Array, default: () => [] },
})

const allTabs = [
  { id: 'komponen', label: '1. Pos Pembayaran (Komponen)', icon: 'bi-grid-3x3-gap-fill', color: 'blue', count: () => props.posList.length },
  { id: 'tarif', label: '2. Matriks Tarif Acuan', icon: 'bi-cash-coin', color: 'emerald', count: () => props.tarifList.length },
  { id: 'keringanan', label: '3. Keringanan & Beasiswa', icon: 'bi-award-fill', color: 'amber', count: () => props.keringananList.length },
  { id: 'kas', label: '4. Akun Kas & Bank', icon: 'bi-bank', color: 'purple', count: () => props.kasList.length },
  { id: 'pengaturan', label: '5. Pengaturan & Kuitansi', icon: 'bi-gear-fill', color: 'slate', count: () => 1 },
]

const availableTabs = computed(() => {
  if (!props.allowed_tabs || props.allowed_tabs.length === 0) return allTabs
  return allTabs.filter(t => props.allowed_tabs.includes(t.id))
})

const currentTenantId = ref(props.selectedTenantId || '')

const tenantOptions = computed(() => [
  { id: '', nama: '-- Semua Sekolah (Agregat Global) --', subLabel: 'Tampilkan seluruh tenant' },
  ...props.tenantsList.map(t => ({
    id: t.id,
    nama: t.nama_sekolah,
    subLabel: 'Tenant ID: ' + t.id.substring(0, 8) + '...'
  }))
])

const onTenantChange = (tId) => {
  router.get('/keuangan/master', {
    tenant_id: tId || undefined
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

const activeTab = ref('komponen') // 'komponen', 'tarif', 'keringanan', 'kas', 'pengaturan'
const toast = ref({ show: false, message: '', type: 'success' })

onMounted(() => {
  if (props.allowed_tabs && props.allowed_tabs.length > 0 && !props.allowed_tabs.includes(activeTab.value)) {
    activeTab.value = props.allowed_tabs[0]
  }
})

function showToast(msg, type = 'success') {
  toast.value = { show: true, message: msg, type }
  setTimeout(() => { toast.value.show = false }, 4000)
}

function formatRupiah(val) {
  if (!val || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}

// ==========================================
// 1. POS BIAYA STATE & ACTIONS
// ==========================================
const posForm = useForm({
  id: null,
  kode_pos: '',
  nama_pos: '',
  tipe_periode: 'Bulanan',
  urutan: 1,
  keterangan: '',
  is_active: true,
})
const isEditPos = ref(false)

const tipePeriodeOptions = [
  { id: 'Bulanan', label: 'Bulanan', subLabel: 'Ditagih rutin setiap bulan (SPP, Komite)' },
  { id: 'Bebas', label: 'Bebas / Insidental', subLabel: 'Sekali bayar atau dicicil (Gedung, Seragam)' },
  { id: 'Semester', label: 'Semester', subLabel: 'Ditagih setiap semester (Ekskul, Ujian)' },
  { id: 'Tahunan', label: 'Tahunan', subLabel: 'Ditagih setahun sekali (Daftar Ulang)' },
]

function editPos(item) {
  isEditPos.value = true
  posForm.id = item.id
  posForm.kode_pos = item.kode_pos
  posForm.nama_pos = item.nama_pos
  posForm.tipe_periode = item.tipe_periode
  posForm.urutan = item.urutan || 1
  posForm.keterangan = item.keterangan || ''
  posForm.is_active = item.is_active
}

function resetPosForm() {
  isEditPos.value = false
  posForm.reset()
  posForm.id = null
}

function submitPos() {
  if (isEditPos.value) {
    posForm.put(`/keuangan/pos/${posForm.id}`, {
      onSuccess: () => {
        showToast('Pos biaya berhasil diperbarui.')
        resetPosForm()
      },
      onError: () => showToast('Gagal memperbarui pos biaya.', 'error')
    })
  } else {
    posForm.post('/keuangan/pos', {
      onSuccess: () => {
        showToast('Pos biaya baru berhasil ditambahkan.')
        resetPosForm()
      },
      onError: () => showToast('Gagal menambahkan pos biaya.', 'error')
    })
  }
}

function deletePos(id, nama) {
  if (!confirm(`Hapus pos biaya '${nama}'?`)) return
  router.delete(`/keuangan/pos/${id}`, {
    onSuccess: () => showToast('Pos biaya berhasil dihapus.'),
    onError: () => showToast('Gagal menghapus pos biaya.', 'error')
  })
}

// ==========================================
// 2. TARIF PEMBAYARAN STATE & ACTIONS
// ==========================================
const tarifForm = useForm({
  pos_id: '',
  tahun_ajaran_id: '',
  tingkat: '',
  kelas_id: '',
  nominal_tarif: '',
  keterangan: '',
})

const posOptions = computed(() =>
  props.posList.map(p => ({
    id: p.id,
    label: p.nama_pos,
    subLabel: `Tipe: ${p.tipe_periode}`
  }))
)

const tingkatOptions = [
  { id: '', label: 'Semua Tingkat (Default Acuan)' },
  { id: 'X', label: 'Tingkat X (Kelas 10 / 7 / 1)' },
  { id: 'XI', label: 'Tingkat XI (Kelas 11 / 8 / 2)' },
  { id: 'XII', label: 'Tingkat XII (Kelas 12 / 9 / 3)' },
]

const kelasOptions = computed(() => [
  { id: '', label: 'Semua Kelas (Berdasarkan Tingkat)' },
  ...props.kelasList.map(k => ({
    id: k.id,
    label: k.nama_kelas,
    subLabel: `Tingkat ${k.tingkat || '-'}`
  }))
])

const tahunAjaranOptions = computed(() => [
  { id: '', label: 'Semua Tahun Ajaran' },
  ...props.tahunAjaranList.map(t => ({
    id: t.id,
    label: t.tahun_ajaran
  }))
])

function submitTarif() {
  tarifForm.post('/keuangan/tarif', {
    onSuccess: () => {
      showToast('Tarif acuan berhasil diset.')
      tarifForm.reset()
    },
    onError: () => showToast('Gagal menyimpan tarif.', 'error')
  })
}

function deleteTarif(id) {
  if (!confirm('Hapus tarif acuan ini?')) return
  router.delete(`/keuangan/tarif/${id}`, {
    onSuccess: () => showToast('Tarif berhasil dihapus.'),
    onError: () => showToast('Gagal menghapus tarif.', 'error')
  })
}

// ==========================================
// 3. KERINGANAN & BEASISWA STATE & ACTIONS
// ==========================================
const keringananForm = useForm({
  siswa_id: '',
  pos_id: '',
  tipe_potongan: 'Nominal',
  nilai_potongan: '',
  alasan: '',
})

const siswaSearchResults = ref([])
const loadingSearchSiswa = ref(false)

async function onSearchSiswa(q) {
  if (!q || q.length < 2) return
  loadingSearchSiswa.value = true
  try {
    const res = await axios.get('/keuangan/search-siswa', { params: { q } })
    if (res.data && res.data.success) {
      siswaSearchResults.value = res.data.data.map(s => ({
        id: s.id,
        label: s.nama_lengkap,
        subLabel: `NISN: ${s.nisn || '-'} | NIS: ${s.nis || '-'}`
      }))
    }
  } catch (err) {
    console.error(err)
  } finally {
    loadingSearchSiswa.value = false
  }
}

const tipePotonganOptions = [
  { id: 'Nominal', label: 'Nominal Tetap (Rp)', subLabel: 'Potongan harga dalam rupiah langsung' },
  { id: 'Persentase', label: 'Persentase (%)', subLabel: 'Potongan persen dari tarif acuan' },
]

function submitKeringanan() {
  keringananForm.post('/keuangan/keringanan', {
    onSuccess: () => {
      showToast('Data keringanan/beasiswa siswa berhasil disimpan.')
      keringananForm.reset()
    },
    onError: () => showToast('Gagal menyimpan keringanan.', 'error')
  })
}

function deleteKeringanan(id) {
  if (!confirm('Hapus keringanan siswa ini?')) return
  router.delete(`/keuangan/keringanan/${id}`, {
    onSuccess: () => showToast('Data keringanan berhasil dihapus.'),
    onError: () => showToast('Gagal menghapus keringanan.', 'error')
  })
}

// ==========================================
// 4. KAS & BANK STATE & ACTIONS
// ==========================================
const kasForm = useForm({
  id: null,
  kode_kas: '',
  nama_kas: '',
  nomor_rekening: '',
  atas_nama: '',
  saldo_awal: 0,
  is_active: true,
})
const isEditKas = ref(false)

function editKas(item) {
  isEditKas.value = true
  kasForm.id = item.id
  kasForm.kode_kas = item.kode_kas || ''
  kasForm.nama_kas = item.nama_kas
  kasForm.nomor_rekening = item.nomor_rekening || ''
  kasForm.atas_nama = item.atas_nama || ''
  kasForm.saldo_awal = item.saldo_awal || 0
  kasForm.is_active = item.is_active
}

function resetKasForm() {
  isEditKas.value = false
  kasForm.reset()
  kasForm.id = null
}

function submitKas() {
  if (isEditKas.value) {
    kasForm.put(`/keuangan/kas-bank/${kasForm.id}`, {
      onSuccess: () => {
        showToast('Akun kas/bank berhasil diperbarui.')
        resetKasForm()
      },
      onError: () => showToast('Gagal memperbarui kas.', 'error')
    })
  } else {
    kasForm.post('/keuangan/kas-bank', {
      onSuccess: () => {
        showToast('Akun kas/bank baru berhasil ditambahkan.')
        resetKasForm()
      },
      onError: () => showToast('Gagal menambahkan kas.', 'error')
    })
  }
}

function deleteKas(id, nama) {
  if (!confirm(`Hapus akun kas '${nama}'?`)) return
  router.delete(`/keuangan/kas-bank/${id}`, {
    onSuccess: () => showToast('Akun kas berhasil dihapus.'),
    onError: () => showToast('Gagal menghapus akun kas.', 'error')
  })
}

// ==========================================
// 5. PENGATURAN STATE & ACTIONS
// ==========================================
const settingForm = useForm({
  nama_modul: props.pengaturan?.nama_modul || 'Keuangan & SPP',
  istilah_tagihan: props.pengaturan?.istilah_tagihan || 'Tagihan',
  istilah_tunggakan: props.pengaturan?.istilah_tunggakan || 'Tunggakan',
  format_nomor_kuitansi: props.pengaturan?.format_nomor_kuitansi || 'KW/{Y}{m}/{NUM}',
  nama_bendahara: props.pengaturan?.nama_bendahara || 'Bendahara Sekolah',
  nip_bendahara: props.pengaturan?.nip_bendahara || '',
  catatan_kuitansi: props.pengaturan?.catatan_kuitansi || '',
  midtrans_client_key: props.pengaturan?.midtrans_client_key || '',
  midtrans_server_key: props.pengaturan?.midtrans_server_key || '',
  midtrans_is_production: props.pengaturan?.midtrans_is_production || false,
})

function submitPengaturan() {
  settingForm.post('/keuangan/pengaturan', {
    onSuccess: () => showToast('Pengaturan keuangan berhasil disimpan.'),
    onError: () => showToast('Gagal menyimpan pengaturan.', 'error')
  })
}
</script>

<template>
  <AppLayout title="Master Data & Konfigurasi Keuangan">
    <Head title="Master Data & Konfigurasi Keuangan" />

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
          <h1 class="text-2xl font-black text-slate-800 tracking-tight">Master Data & Konfigurasi Keuangan</h1>
          <p class="text-xs text-slate-500 mt-1">Kelola pos pembayaran, matriks tarif acuan, beasiswa siswa, akun rekening kas/bank, dan konfigurasi kuitansi.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-2"
            @click="router.visit('/keuangan/dashboard')"
          >
            <i class="bi bi-speedometer2"></i> Dashboard
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
                <span class="font-bold text-slate-800 text-sm">Filter Master Sekolah</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 border border-blue-200">
                  <i class="bi bi-shield-lock-fill me-1 text-blue-600"></i> Super Admin Mode
                </span>
              </div>
              <p class="text-xs text-slate-600 mt-0.5">
                Menampilkan master tarif & pos pembayaran milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
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

      <!-- Modern Pill NavTabs (5-in-1) with Horizontal Scroller -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <button
            type="button"
            class="btn btn-sm border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5"
            onclick="document.getElementById('navTabsMasterKeuangan')?.scrollBy({ left: -220, behavior: 'smooth' })"
            title="Geser ke Kiri"
          >
            <i class="bi bi-chevron-left"></i>
          </button>

          <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="navTabsMasterKeuangan" role="tablist">
              <!-- DYNAMIC NAVTABS -->
              <li v-for="t in availableTabs" :key="t.id" class="nav-item">
                <button
                  type="button"
                  class="border-0 font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2"
                  :class="activeTab === t.id ? (t.id === 'komponen' ? 'bg-blue-600 text-white shadow-xs' : (t.id === 'tarif' ? 'bg-emerald-600 text-white shadow-xs' : (t.id === 'keringanan' ? 'bg-amber-500 text-white shadow-xs' : (t.id === 'kas' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-800 text-white shadow-xs')))) : 'text-slate-600 hover:bg-slate-100'"
                  @click="activeTab = t.id"
                >
                  <i :class="['bi', t.icon, 'text-sm']"></i>
                  <span>{{ t.label }}</span>
                  <span v-if="t.id !== 'pengaturan'" class="px-1.5 py-0.2 bg-white/20 text-white text-[10px] font-bold rounded-full">
                    {{ t.count() }}
                  </span>
                </button>
              </li>
            </ul>
          </div>

          <button
            type="button"
            class="btn btn-sm border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5"
            onclick="document.getElementById('navTabsMasterKeuangan')?.scrollBy({ left: 220, behavior: 'smooth' })"
            title="Geser ke Kanan"
          >
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 1: POS BIAYA / KOMPONEN -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'komponen'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Pos Biaya (1-Col) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <h3 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-3">
            {{ isEditPos ? 'Edit Pos Biaya' : 'Tambah Pos Biaya Baru' }}
          </h3>

          <form @submit.prevent="submitPos" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kode Pos <span class="text-rose-500">*</span></label>
              <input
                type="text"
                v-model="posForm.kode_pos"
                required
                placeholder="Misal: SPP, GEDUNG, SERAGAM"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono uppercase focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Nama Pos Pembayaran <span class="text-rose-500">*</span></label>
              <input
                type="text"
                v-model="posForm.nama_pos"
                required
                placeholder="Misal: Sumbangan Pembinaan Pendidikan (SPP)"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tipe Periode Pembayaran</label>
              <SearchableSelect
                v-model="posForm.tipe_periode"
                :options="tipePeriodeOptions"
                placeholder="Pilih Tipe Periode..."
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Urutan Tampilan</label>
              <input
                type="number"
                v-model.number="posForm.urutan"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Keterangan / Deskripsi</label>
              <textarea
                v-model="posForm.keterangan"
                rows="2"
                placeholder="Keterangan opsional mengenai pos biaya ini..."
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
              ></textarea>
            </div>

            <div class="flex items-center gap-2 pt-2">
              <button
                v-if="isEditPos"
                type="button"
                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition flex-1"
                @click="resetPosForm"
              >
                Batal
              </button>
              <button
                type="submit"
                class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold shadow-xs transition flex-1"
                :disabled="posForm.processing"
              >
                {{ isEditPos ? 'Perbarui' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Tabel Pos Biaya (2-Cols) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-list-columns-reverse text-blue-600"></i> Daftar Pos Pembayaran
            </h3>
            <span class="text-xs text-slate-400">Total: {{ posList.length }} Pos</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                  <th class="py-3 px-3">Kode & Nama Pos</th>
                  <th class="py-3 px-3">Tipe Periode</th>
                  <th class="py-3 px-3">Keterangan</th>
                  <th class="py-3 px-3 text-center">Urutan</th>
                  <th class="py-3 px-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="p in posList" :key="p.id" class="hover:bg-slate-50 transition">
                  <td class="py-3 px-3">
                    <span class="font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded text-[11px] me-1.5">{{ p.kode_pos }}</span>
                    <span class="font-bold text-slate-800">{{ p.nama_pos }}</span>
                  </td>
                  <td class="py-3 px-3">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border" :class="p.tipe_periode === 'Bulanan' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200'">
                      {{ p.tipe_periode }}
                    </span>
                  </td>
                  <td class="py-3 px-3 text-slate-500 line-clamp-1">{{ p.keterangan || '-' }}</td>
                  <td class="py-3 px-3 text-center font-bold text-slate-600">{{ p.urutan || 0 }}</td>
                  <td class="py-3 px-3 text-right whitespace-nowrap">
                    <button
                      type="button"
                      class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold me-1 text-[11px] transition"
                      @click="editPos(p)"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button
                      type="button"
                      class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[11px] transition"
                      @click="deletePos(p.id, p.nama_pos)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 2: MATRIKS TARIF ACUAN -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'tarif'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Tarif (1-Col) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <h3 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-3">
            Set Tarif Pembayaran
          </h3>

          <form @submit.prevent="submitTarif" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Pilih Pos Biaya <span class="text-rose-500">*</span></label>
              <SearchableSelect
                v-model="tarifForm.pos_id"
                :options="posOptions"
                placeholder="-- Pilih Pos Biaya --"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tahun Ajaran</label>
              <SearchableSelect
                v-model="tarifForm.tahun_ajaran_id"
                :options="tahunAjaranOptions"
                placeholder="Semua Tahun Ajaran"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tingkat Kelas</label>
              <SearchableSelect
                v-model="tarifForm.tingkat"
                :options="tingkatOptions"
                placeholder="Pilih Tingkat..."
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Khusus Rombel Tertentu</label>
              <SearchableSelect
                v-model="tarifForm.kelas_id"
                :options="kelasOptions"
                placeholder="Semua Kelas"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Nominal Tarif (Rp) <span class="text-rose-500">*</span></label>
              <input
                type="number"
                v-model="tarifForm.nominal_tarif"
                required
                min="0"
                placeholder="Misal: 250000"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono text-base font-black text-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Keterangan Tambahan</label>
              <input
                type="text"
                v-model="tarifForm.keterangan"
                placeholder="Keterangan opsional..."
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <button
              type="submit"
              class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-xs transition"
              :disabled="tarifForm.processing"
            >
              Simpan Tarif Acuan
            </button>
          </form>
        </div>

        <!-- Tabel Tarif Acuan (2-Cols) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-table text-emerald-600"></i> Matriks Tarif Aktif
            </h3>
            <span class="text-xs text-slate-400">Total: {{ tarifList.length }} Tarif</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                  <th class="py-3 px-3">Pos Pembayaran</th>
                  <th class="py-3 px-3">Tingkat / Kelas</th>
                  <th class="py-3 px-3 text-right">Nominal Tarif</th>
                  <th class="py-3 px-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="t in tarifList" :key="t.id" class="hover:bg-slate-50 transition">
                  <td class="py-3 px-3 font-bold text-slate-800">
                    {{ t.pos?.nama_pos || '-' }}
                    <span class="text-[10px] text-slate-400 block font-normal">Tipe: {{ t.pos?.tipe_periode || 'Bulanan' }}</span>
                  </td>
                  <td class="py-3 px-3">
                    <span v-if="t.tingkat" class="px-2 py-0.5 rounded bg-slate-100 font-bold me-1">Tingkat {{ t.tingkat }}</span>
                    <span v-else class="text-slate-400">Semua Tingkat</span>
                  </td>
                  <td class="py-3 px-3 text-right font-mono font-bold text-emerald-600 text-sm">
                    Rp {{ formatRupiah(t.nominal_tarif) }}
                  </td>
                  <td class="py-3 px-3 text-right">
                    <button
                      type="button"
                      class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[11px] transition"
                      @click="deleteTarif(t.id)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 3: KERINGANAN & BEASISWA -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'keringanan'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Keringanan (1-Col) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <h3 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-3">
            Atur Beasiswa / Keringanan Siswa
          </h3>

          <form @submit.prevent="submitKeringanan" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Cari Peserta Didik <span class="text-rose-500">*</span></label>
              <SearchableSelect
                v-model="keringananForm.siswa_id"
                :options="siswaSearchResults"
                placeholder="-- Ketik Nama Siswa / NISN --"
                @search="onSearchSiswa"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Pilih Pos Biaya <span class="text-rose-500">*</span></label>
              <SearchableSelect
                v-model="keringananForm.pos_id"
                :options="posOptions"
                placeholder="-- Pilih Pos Biaya --"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tipe Potongan</label>
              <SearchableSelect
                v-model="keringananForm.tipe_potongan"
                :options="tipePotonganOptions"
                placeholder="Pilih Tipe..."
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">
                Besar Potongan {{ keringananForm.tipe_potongan === 'Persentase' ? '(%)' : '(Rp)' }} <span class="text-rose-500">*</span>
              </label>
              <input
                type="number"
                v-model="keringananForm.nilai_potongan"
                required
                min="0"
                placeholder="Misal: 50 atau 100000"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono text-base font-black text-amber-700 focus:ring-2 focus:ring-amber-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Alasan Beasiswa / Keringanan</label>
              <textarea
                v-model="keringananForm.alasan"
                rows="2"
                placeholder="Misal: Beasiswa Anak Guru, Siswa Yatim, Prestasi Olimpiade..."
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:bg-white outline-hidden transition"
              ></textarea>
            </div>

            <button
              type="submit"
              class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold shadow-xs transition"
              :disabled="keringananForm.processing"
            >
              Simpan Keringanan
            </button>
          </form>
        </div>

        <!-- Tabel Keringanan (2-Cols) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-award text-amber-500"></i> Daftar Siswa Penerima Keringanan
            </h3>
            <span class="text-xs text-slate-400">Total: {{ keringananList.length }} Siswa</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                  <th class="py-3 px-3">Nama Siswa & NISN</th>
                  <th class="py-3 px-3">Pos Biaya</th>
                  <th class="py-3 px-3">Besar Potongan</th>
                  <th class="py-3 px-3">Alasan</th>
                  <th class="py-3 px-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="k in keringananList" :key="k.id" class="hover:bg-slate-50 transition">
                  <td class="py-3 px-3">
                    <div class="font-bold text-slate-800">{{ k.siswa?.nama_lengkap || '-' }}</div>
                    <div class="text-[11px] text-slate-400">NISN: {{ k.siswa?.nisn || '-' }}</div>
                  </td>
                  <td class="py-3 px-3 font-semibold text-slate-700">{{ k.pos?.nama_pos || '-' }}</td>
                  <td class="py-3 px-3 font-bold text-amber-600 font-mono">
                    <span v-if="k.tipe_potongan === 'Persentase'">{{ Number(k.nilai_potongan) }}% Potongan</span>
                    <span v-else>Rp {{ formatRupiah(k.nilai_potongan) }}</span>
                  </td>
                  <td class="py-3 px-3 text-slate-500 line-clamp-1">{{ k.alasan || '-' }}</td>
                  <td class="py-3 px-3 text-right">
                    <button
                      type="button"
                      class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[11px] transition"
                      @click="deleteKeringanan(k.id)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 4: KAS & BANK -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'kas'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Kas (1-Col) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <h3 class="text-sm font-black text-slate-900 border-b border-slate-100 pb-3">
            {{ isEditKas ? 'Edit Akun Kas/Bank' : 'Tambah Akun Kas/Bank' }}
          </h3>

          <form @submit.prevent="submitKas" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kode Akun</label>
              <input
                type="text"
                v-model="kasForm.kode_kas"
                placeholder="Misal: KAS-01, BANK-BSI"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl uppercase font-mono focus:ring-2 focus:ring-purple-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Nama Kas / Rekening Bank <span class="text-rose-500">*</span></label>
              <input
                type="text"
                v-model="kasForm.nama_kas"
                required
                placeholder="Misal: Kas Tunai Bendahara"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Nomor Rekening</label>
              <input
                type="text"
                v-model="kasForm.nomor_rekening"
                placeholder="Nomor rekening jika bank..."
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-purple-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Atas Nama Rekening</label>
              <input
                type="text"
                v-model="kasForm.atas_nama"
                placeholder="Atas nama pemilik rekening..."
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div v-if="!isEditKas">
              <label class="block font-bold text-slate-700 mb-1">Saldo Awal (Rp)</label>
              <input
                type="number"
                v-model="kasForm.saldo_awal"
                min="0"
                placeholder="0"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-purple-500 focus:bg-white outline-hidden transition"
              />
            </div>

            <div class="flex items-center gap-2 pt-2">
              <button
                v-if="isEditKas"
                type="button"
                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition flex-1"
                @click="resetKasForm"
              >
                Batal
              </button>
              <button
                type="submit"
                class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-xs transition flex-1"
                :disabled="kasForm.processing"
              >
                {{ isEditKas ? 'Perbarui' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Tabel Kas (2-Cols) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-wallet-fill text-purple-600"></i> Akun Rekening Kas & Bank
            </h3>
            <span class="text-xs text-slate-400">Total: {{ kasList.length }} Akun</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                  <th class="py-3 px-3">Kode & Nama Akun</th>
                  <th class="py-3 px-3">Rekening & Atas Nama</th>
                  <th class="py-3 px-3 text-right">Saldo Saat Ini</th>
                  <th class="py-3 px-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="k in kasList" :key="k.id" class="hover:bg-slate-50 transition">
                  <td class="py-3 px-3">
                    <span class="font-mono font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded text-[11px] me-1.5">{{ k.kode_kas || '-' }}</span>
                    <span class="font-bold text-slate-800">{{ k.nama_kas }}</span>
                  </td>
                  <td class="py-3 px-3 text-slate-600">
                    <div class="font-mono font-bold">{{ k.nomor_rekening || '-' }}</div>
                    <div class="text-[11px] text-slate-400">a.n. {{ k.atas_nama || '-' }}</div>
                  </td>
                  <td class="py-3 px-3 text-right font-mono font-bold text-purple-700 text-sm">
                    Rp {{ formatRupiah(k.saldo_saat_ini) }}
                  </td>
                  <td class="py-3 px-3 text-right whitespace-nowrap">
                    <button
                      type="button"
                      class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold me-1 text-[11px] transition"
                      @click="editKas(k)"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button
                      type="button"
                      class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[11px] transition"
                      @click="deleteKas(k.id, k.nama_kas)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 5: PENGATURAN KEUANGAN & KUITANSI -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'pengaturan'" class="max-w-3xl mx-auto bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-base font-black text-slate-900">Pengaturan Format & Legalitas Kuitansi</h3>
          <p class="text-xs text-slate-500 mt-0.5">Atur istilah penagihan, format penomoran kuitansi, dan nama bendahara yang tertera pada lembar bukti pembayaran.</p>
        </div>

        <form @submit.prevent="submitPengaturan" class="space-y-4 text-xs">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Nama Modul Keuangan</label>
              <input
                type="text"
                v-model="settingForm.nama_modul"
                required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Format Nomor Kuitansi</label>
              <input
                type="text"
                v-model="settingForm.format_nomor_kuitansi"
                required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Nama Bendahara</label>
              <input
                type="text"
                v-model="settingForm.nama_bendahara"
                placeholder="Misal: Siti Aminah, S.Pd"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">NIP Bendahara</label>
              <input
                type="text"
                v-model="settingForm.nip_bendahara"
                placeholder="NIP atau tanda '-' jika non-PNS"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan Kaki Kuitansi (Footer Notice)</label>
            <textarea
              v-model="settingForm.catatan_kuitansi"
              rows="3"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-hidden transition"
            ></textarea>
          </div>

          <div class="pt-4 border-t border-slate-100 flex justify-end">
            <button
              type="submit"
              class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold shadow-xs transition"
              :disabled="settingForm.processing"
            >
              Simpan Perubahan Pengaturan
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
