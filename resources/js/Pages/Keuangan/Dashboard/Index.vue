<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'

const props = defineProps({
  metrics: {
    type: Object,
    default: () => ({})
  },
  isSuperAdmin: {
    type: Boolean,
    default: false
  },
  tenantsList: {
    type: Array,
    default: () => []
  },
  selectedTenantId: {
    type: String,
    default: ''
  }
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
  router.get('/keuangan/dashboard', {
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

function getPercentage(terbayar, total) {
  if (!total || total <= 0) return 0
  return Math.min(100, Math.round((Number(terbayar) / Number(total)) * 100))
}
</script>

<template>
  <AppLayout title="Dashboard Keuangan & SPP">
    <Head title="Dashboard Keuangan & SPP" />

    <div class="space-y-6">
      <!-- Section 1: Banner Filter Tenant (Khusus Super Admin) -->
      <div v-if="isSuperAdmin" class="rounded-2xl shadow-xs border border-emerald-200 bg-gradient-to-r from-emerald-50/90 via-teal-50/80 to-slate-50 border-l-4 border-l-emerald-600 p-4 transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-xs shrink-0">
              <i class="bi bi-building text-lg"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-sm">Filter Tenant Sekolah</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                  <i class="bi bi-shield-lock-fill me-1 text-emerald-600"></i> Super Admin Mode
                </span>
              </div>
              <p class="text-xs text-slate-600 mt-0.5">
                Menampilkan data keuangan & arus kas milik: <strong class="text-emerald-700 font-bold ml-1">{{ getSelectedTenantName() }}</strong>
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
      <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-teal-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2 max-w-2xl">
            <div class="flex items-center gap-2">
              <span class="px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-semibold uppercase tracking-wider backdrop-blur-md">
                <i class="bi bi-wallet2 me-1.5"></i> Financial Management Suite
              </span>
              <span v-if="isSuperAdmin" class="px-2.5 py-0.5 rounded-full bg-amber-400 text-slate-950 text-xs font-bold uppercase">
                Super Admin
              </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Dashboard Keuangan & Arus Kas</h1>
            <p class="text-emerald-100 text-xs sm:text-sm leading-relaxed">
              Pantau penerimaan kas harian, realisasi pelunasan tagihan per rombel kelas, serta saldo kas & bank secara real-time.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-3 shrink-0">
            <button
              type="button"
              class="px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-600/30 transition flex items-center gap-2 active:scale-95"
              @click="router.visit('/keuangan/kasir')"
            >
              <i class="bi bi-cash-stack text-base"></i>
              <span>Loket Kasir TU</span>
            </button>
            <button
              type="button"
              class="px-5 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm border border-white/20 backdrop-blur-md transition flex items-center gap-2 active:scale-95"
              @click="router.visit('/keuangan/tagihan')"
            >
              <i class="bi bi-magic text-base text-yellow-300"></i>
              <span>Terbitkan Tagihan</span>
            </button>
          </div>
        </div>

        <div class="absolute -right-16 -top-16 w-64 h-64 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-teal-500/20 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- Quick Navigation Shortcuts -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <button
          type="button"
          class="p-3 bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-md transition text-center group flex flex-col items-center justify-center gap-1.5"
          @click="router.visit('/keuangan/dashboard')"
        >
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
            <i class="bi bi-speedometer2"></i>
          </div>
          <span class="text-xs font-bold text-slate-800">Dashboard</span>
        </button>

        <button
          type="button"
          class="p-3 bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-md transition text-center group flex flex-col items-center justify-center gap-1.5"
          @click="router.visit('/keuangan/master')"
        >
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
            <i class="bi bi-sliders"></i>
          </div>
          <span class="text-xs font-bold text-slate-800">Master Biaya</span>
        </button>

        <button
          type="button"
          class="p-3 bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-md transition text-center group flex flex-col items-center justify-center gap-1.5"
          @click="router.visit('/keuangan/kasir')"
        >
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
            <i class="bi bi-cash-coin"></i>
          </div>
          <span class="text-xs font-bold text-slate-800">Loket Kasir</span>
        </button>

        <button
          type="button"
          class="p-3 bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-md transition text-center group flex flex-col items-center justify-center gap-1.5"
          @click="router.visit('/keuangan/tagihan')"
        >
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
            <i class="bi bi-receipt"></i>
          </div>
          <span class="text-xs font-bold text-slate-800">Tagihan Siswa</span>
        </button>

        <button
          type="button"
          class="p-3 bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-md transition text-center group flex flex-col items-center justify-center gap-1.5"
          @click="router.visit('/keuangan/laporan')"
        >
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
            <i class="bi bi-file-earmark-bar-graph"></i>
          </div>
          <span class="text-xs font-bold text-slate-800">Laporan Kas</span>
        </button>

        <button
          type="button"
          class="p-3 bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-md transition text-center group flex flex-col items-center justify-center gap-1.5"
          @click="router.visit('/keuangan/tagihan-saya')"
        >
          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
            <i class="bi bi-person-vcard"></i>
          </div>
          <span class="text-xs font-bold text-slate-800">Tagihan Saya</span>
        </button>

        <button
          type="button"
          class="p-3 bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-md transition text-center group flex flex-col items-center justify-center gap-1.5"
          @click="router.visit('/keuangan/audit-log')"
        >
          <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
            <i class="bi bi-shield-check"></i>
          </div>
          <span class="text-xs font-bold text-slate-800">Audit Trail</span>
        </button>
      </div>

      <!-- KPI Summary Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Pemasukan Hari Ini -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shrink-0 shadow-inner">
            <i class="bi bi-wallet2"></i>
          </div>
          <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pemasukan Hari Ini</div>
            <div class="text-xl font-black text-slate-800 mt-0.5">Rp {{ formatRupiah(metrics.pemasukan_hari_ini) }}</div>
            <div class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1 mt-0.5">
              <i class="bi bi-clock-history"></i> Transaksi Kasir Real-time
            </div>
          </div>
        </div>

        <!-- Pemasukan Bulan Ini -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl shrink-0 shadow-inner">
            <i class="bi bi-calendar2-check"></i>
          </div>
          <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pemasukan Bulan Ini</div>
            <div class="text-xl font-black text-slate-800 mt-0.5">Rp {{ formatRupiah(metrics.pemasukan_bulan_ini) }}</div>
            <div class="text-[11px] text-blue-600 font-semibold flex items-center gap-1 mt-0.5">
              <i class="bi bi-check-circle"></i> Periode Berjalan
            </div>
          </div>
        </div>

        <!-- Total Tunggakan Siswa -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl shrink-0 shadow-inner">
            <i class="bi bi-exclamation-octagon"></i>
          </div>
          <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Sisa Tunggakan</div>
            <div class="text-xl font-black text-rose-600 mt-0.5">Rp {{ formatRupiah(metrics.total_tunggakan) }}</div>
            <div class="text-[11px] text-rose-500 font-semibold flex items-center gap-1 mt-0.5">
              <i class="bi bi-hourglass-split"></i> Menunggu Pelunasan
            </div>
          </div>
        </div>

        <!-- Saldo Kas & Bank -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl shrink-0 shadow-inner">
            <i class="bi bi-bank2"></i>
          </div>
          <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Kas & Bank</div>
            <div class="text-xl font-black text-purple-700 mt-0.5">Rp {{ formatRupiah(metrics.total_saldo_kas) }}</div>
            <div class="text-[11px] text-purple-600 font-semibold flex items-center gap-1 mt-0.5">
              <i class="bi bi-safe"></i> Saldo Kas Terkumpul
            </div>
          </div>
        </div>
      </div>

      <!-- Progres Pelunasan Kelas & Transaksi Terakhir -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Progres per Rombel Kelas (2-Cols) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="bi bi-bar-chart-line-fill text-emerald-600"></i> Progres Pelunasan Rombel Kelas
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">Tingkat kepatuhan pembayaran SPP per kelas pada tahun ajaran aktif.</p>
            </div>
            <button
              type="button"
              class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition"
              @click="router.visit('/keuangan/tagihan')"
            >
              Rincian Tagihan
            </button>
          </div>

          <div v-if="!metrics.progres_kelas || metrics.progres_kelas.length === 0" class="py-12 text-center text-slate-400 text-xs">
            <i class="bi bi-inbox text-3xl block mb-2"></i>
            Belum ada tagihan terbit pada rombel kelas.
          </div>

          <div v-else class="space-y-4 overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 uppercase tracking-wider font-bold border-b text-[11px]">
                  <th class="py-3 px-3">Nama Kelas</th>
                  <th class="py-3 px-3 text-right">Target Tagihan</th>
                  <th class="py-3 px-3 text-right">Terbayar</th>
                  <th class="py-3 px-3 text-right">Sisa Tunggakan</th>
                  <th class="py-3 px-3 text-center" style="width: 180px;">Persentase</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in metrics.progres_kelas" :key="item.nama_kelas" class="hover:bg-slate-50 transition">
                  <td class="py-3 px-3 font-bold text-slate-800">
                    {{ item.nama_kelas }}
                    <span class="text-[10px] text-slate-400 block">({{ item.jumlah_siswa }} Siswa)</span>
                  </td>
                  <td class="py-3 px-3 text-right font-mono">Rp {{ formatRupiah(item.total_tagihan) }}</td>
                  <td class="py-3 px-3 text-right font-mono font-bold text-emerald-600">Rp {{ formatRupiah(item.total_bayar) }}</td>
                  <td class="py-3 px-3 text-right font-mono text-rose-600">Rp {{ formatRupiah(item.total_tunggakan) }}</td>
                  <td class="py-3 px-3">
                    <div class="flex items-center gap-2">
                      <div class="grow bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div
                          class="h-full rounded-full transition-all duration-500"
                          :class="getPercentage(item.total_bayar, item.total_tagihan) >= 80 ? 'bg-emerald-500' : (getPercentage(item.total_bayar, item.total_tagihan) >= 50 ? 'bg-amber-500' : 'bg-rose-500')"
                          :style="{ width: getPercentage(item.total_bayar, item.total_tagihan) + '%' }"
                        ></div>
                      </div>
                      <span class="font-bold text-[11px] shrink-0 text-slate-700">
                        {{ getPercentage(item.total_bayar, item.total_tagihan) }}%
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Riwayat Transaksi Kasir Terbaru (1-Col) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="bi bi-clock-history text-blue-600"></i> Transaksi Terkini
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">8 pembayaran kasir terakhir.</p>
            </div>
            <button
              type="button"
              class="text-xs font-bold text-blue-600 hover:text-blue-700 transition"
              @click="router.visit('/keuangan/laporan')"
            >
              Semua
            </button>
          </div>

          <div v-if="!metrics.recent_transactions || metrics.recent_transactions.length === 0" class="py-12 text-center text-slate-400 text-xs">
            <i class="bi bi-receipt-cutoff text-3xl block mb-2"></i>
            Belum ada transaksi hari ini.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="trx in metrics.recent_transactions"
              :key="trx.id"
              class="p-3.5 rounded-2xl bg-slate-50/80 border border-slate-100 hover:bg-emerald-50/50 hover:border-emerald-200 transition space-y-1"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-xs text-slate-800 line-clamp-1">{{ trx.siswa?.nama_lengkap || 'Siswa' }}</span>
                <span class="font-bold text-xs text-emerald-600 font-mono">Rp {{ formatRupiah(trx.nominal_bayar) }}</span>
              </div>
              <div class="flex items-center justify-between text-[11px] text-slate-400">
                <span>{{ trx.metode_pembayaran }} &bull; {{ trx.kas?.nama_kas || 'Kas' }}</span>
                <span>{{ formatDate(trx.tanggal_bayar) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
