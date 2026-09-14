<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'

const props = defineProps({
  siswa: { type: Object, default: null },
  tagihanList: { type: Array, default: () => [] },
  riwayatTransaksi: { type: Array, default: () => [] },
  totalTunggakan: { type: Number, default: 0 },
  totalTerbayar: { type: Number, default: 0 },
  pengaturan: { type: Object, default: () => ({}) },
  isSuperAdmin: { type: Boolean, default: false },
  tenantsList: { type: Array, default: () => [] },
  selectedTenantId: { type: String, default: '' },
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
  router.get('/keuangan/tagihan-saya', {
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

const activeTab = ref('tagihan') // 'tagihan', 'riwayat'

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
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Payment Online Modal
const isPayOnlineModalOpen = ref(false)
const activePayItem = ref(null)

function openPayOnline(item) {
  activePayItem.value = item
  isPayOnlineModalOpen.value = true
}

function printKuitansi() {
  window.print()
}
</script>

<template>
  <AppLayout title="Portal Tagihan Siswa & Orang Tua">
    <Head title="Portal Tagihan Siswa & Orang Tua" />

    <div class="space-y-6">
      <!-- Section: Banner Filter Tenant (Khusus Super Admin) -->
      <div v-if="isSuperAdmin" class="rounded-2xl shadow-xs border border-blue-200 bg-gradient-to-r from-blue-50/90 via-indigo-50/80 to-slate-50 border-l-4 border-l-blue-600 p-4 transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs shrink-0">
              <i class="bi bi-building text-lg"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-sm">Preview Tagihan Siswa Sekolah</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 border border-blue-200">
                  <i class="bi bi-shield-lock-fill me-1 text-blue-600"></i> Super Admin Mode
                </span>
              </div>
              <p class="text-xs text-slate-600 mt-0.5">
                Melihat kartu SPP & riwayat pembayaran siswa milik: <strong class="text-blue-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
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

      <!-- Header Hero Banner -->
      <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2 max-w-2xl">
            <span class="px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-semibold uppercase tracking-wider backdrop-blur-md">
              <i class="bi bi-person-vcard me-1.5"></i> Profil Keuangan Pribadi
            </span>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Kartu SPP & Tagihan Saya</h1>
            <p class="text-blue-200 text-xs sm:text-sm leading-relaxed">
              Informasi status kewajiban administrasi sekolah, riwayat kuitansi pembayaran yang sah, dan kemudahan pembayaran digital online.
            </p>
          </div>

          <div v-if="siswa" class="p-4 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-md shrink-0 space-y-1">
            <div class="text-xs text-blue-300 font-bold uppercase">Peserta Didik</div>
            <div class="font-black text-white text-base">{{ siswa.nama_lengkap }}</div>
            <div class="text-xs text-blue-200">NISN: {{ siswa.nisn || '-' }} &bull; Kelas: {{ siswa.kelas?.nama_kelas || '-' }}</div>
          </div>
        </div>

        <div class="absolute -right-16 -top-16 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl shrink-0 shadow-inner">
            <i class="bi bi-exclamation-circle-fill"></i>
          </div>
          <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Sisa Kewajiban Tagihan</div>
            <div class="text-2xl font-black text-rose-600 mt-0.5">Rp {{ formatRupiah(totalTunggakan) }}</div>
            <div class="text-xs text-slate-500 mt-0.5">Total tagihan yang belum terselesaikan</div>
          </div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shrink-0 shadow-inner">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Telah Terbayar</div>
            <div class="text-2xl font-black text-emerald-600 mt-0.5">Rp {{ formatRupiah(totalTerbayar) }}</div>
            <div class="text-xs text-slate-500 mt-0.5">Akumulasi pembayaran yang telah disetor</div>
          </div>
        </div>
      </div>

      <!-- NavTabs (Daftar Tagihan vs Riwayat Pembayaran) -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2">
        <ul class="flex border-0 gap-2 select-none" role="tablist">
          <li class="flex-1">
            <button
              type="button"
              class="w-full border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-2"
              :class="activeTab === 'tagihan' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
              @click="activeTab = 'tagihan'"
            >
              <i class="bi bi-receipt text-sm"></i>
              <span>Daftar Kewajiban Tagihan ({{ tagihanList.length }})</span>
            </button>
          </li>

          <li class="flex-1">
            <button
              type="button"
              class="w-full border-0 font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-2"
              :class="activeTab === 'riwayat' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
              @click="activeTab = 'riwayat'"
            >
              <i class="bi bi-clock-history text-sm"></i>
              <span>Riwayat Kuitansi Pembayaran ({{ riwayatTransaksi.length }})</span>
            </button>
          </li>
        </ul>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 1: DAFTAR TAGIHAN -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'tagihan'" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3.5 px-4">Komponen Pembayaran</th>
                <th class="py-3.5 px-4">Periode Tagihan</th>
                <th class="py-3.5 px-4 text-right">Nominal Kewajiban</th>
                <th class="py-3.5 px-4 text-right">Telah Terbayar</th>
                <th class="py-3.5 px-4 text-right">Sisa Kekurangan</th>
                <th class="py-3.5 px-4 text-center">Status</th>
                <th class="py-3.5 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="tagihanList.length === 0">
                <td colspan="7" class="py-14 text-center text-slate-400">
                  <div class="w-14 h-14 mx-auto rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-2 shadow-inner">
                    <i class="bi bi-check2-all"></i>
                  </div>
                  <p class="font-bold text-slate-800">Tidak Ada Tagihan</p>
                  <p class="text-xs text-slate-400">Tidak ada kewajiban pembayaran yang terdaftar.</p>
                </td>
              </tr>

              <tr v-for="t in tagihanList" :key="t.id" class="hover:bg-slate-50 transition">
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800 text-sm">{{ t.pos?.nama_pos || 'SPP' }}</div>
                  <div class="text-[10px] text-slate-400 font-mono mt-0.5">Invoice: {{ t.nomor_tagihan }}</div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-700">
                  <span v-if="t.bulan">Bulan {{ t.bulan }}/{{ t.tahun }}</span>
                  <span v-else class="text-slate-400">Tahun {{ t.tahun }}</span>
                </td>
                <td class="py-3.5 px-4 text-right font-mono">Rp {{ formatRupiah(t.total_tagihan) }}</td>
                <td class="py-3.5 px-4 text-right font-mono text-emerald-600 font-bold">Rp {{ formatRupiah(t.total_terbayar) }}</td>
                <td class="py-3.5 px-4 text-right font-mono font-black text-rose-600">Rp {{ formatRupiah(t.sisa_tagihan) }}</td>
                <td class="py-3.5 px-4 text-center">
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border" :class="t.status_pembayaran === 'Lunas' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                    {{ t.status_pembayaran }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button
                    v-if="t.status_pembayaran !== 'Lunas'"
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition inline-flex items-center gap-1.5 active:scale-95"
                    @click="openPayOnline(t)"
                  >
                    <i class="bi bi-qr-code-scan"></i> Bayar Online
                  </button>
                  <span v-else class="text-emerald-600 font-bold text-xs flex items-center justify-end gap-1">
                    <i class="bi bi-check-circle-fill"></i> Lunas
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 2: RIWAYAT PEMBAYARAN -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'riwayat'" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 uppercase font-bold border-b text-[11px]">
                <th class="py-3.5 px-4">No. Transaksi & Tanggal</th>
                <th class="py-3.5 px-4">Pos Pembayaran</th>
                <th class="py-3.5 px-4">Metode Pembayaran</th>
                <th class="py-3.5 px-4">Penerima Kas</th>
                <th class="py-3.5 px-4 text-right">Jumlah Terbayar</th>
                <th class="py-3.5 px-4 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="riwayatTransaksi.length === 0">
                <td colspan="6" class="py-14 text-center text-slate-400">
                  <div class="w-14 h-14 mx-auto rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-2">
                    <i class="bi bi-clock-history"></i>
                  </div>
                  <p class="font-bold text-slate-700">Belum Ada Riwayat Pembayaran</p>
                </td>
              </tr>

              <tr v-for="trx in riwayatTransaksi" :key="trx.id" class="hover:bg-slate-50 transition">
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="font-mono font-bold text-blue-700">{{ trx.nomor_transaksi }}</div>
                  <div class="text-[10px] text-slate-400">{{ formatDate(trx.tanggal_bayar) }}</div>
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-800">{{ trx.tagihan?.pos?.nama_pos || 'SPP' }}</td>
                <td class="py-3.5 px-4">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 border text-slate-700">
                    {{ trx.metode_pembayaran }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-slate-500">{{ trx.kas?.nama_kas || 'Kas' }}</td>
                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 text-sm">
                  Rp {{ formatRupiah(trx.nominal_bayar) }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Sah / Terverifikasi
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL BAYAR ONLINE QRIS / VA (TELEPORT TO BODY) -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="isPayOnlineModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-md w-full p-6 space-y-4 text-center">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <i class="bi bi-qr-code text-purple-600"></i> Pembayaran Digital Online
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none" @click="isPayOnlineModalOpen = false">&times;</button>
          </div>

          <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-100 text-xs space-y-1">
            <div class="text-slate-500">{{ activePayItem?.pos?.nama_pos }}</div>
            <div class="text-2xl font-black text-purple-900 font-mono">Rp {{ formatRupiah(activePayItem?.sisa_tagihan) }}</div>
            <div class="text-[10px] text-purple-700 font-mono">Invoice: {{ activePayItem?.nomor_tagihan }}</div>
          </div>

          <!-- Dummy QRIS / VA Display for UI Simulation -->
          <div class="p-6 bg-white border-2 border-dashed border-slate-200 rounded-2xl space-y-3">
            <div class="w-36 h-36 mx-auto bg-slate-900 text-white flex items-center justify-center rounded-2xl text-4xl shadow-inner">
              <i class="bi bi-qr-code"></i>
            </div>
            <p class="text-xs font-bold text-slate-800">Scan QRIS melalui GoPay / OVO / Dana / BCA Mobile</p>
            <p class="text-[10px] text-slate-400">Pembayaran diverifikasi secara otomatis melalui Midtrans Gateway</p>
          </div>

          <button
            type="button"
            class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition"
            @click="isPayOnlineModalOpen = false"
          >
            Selesai
          </button>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
