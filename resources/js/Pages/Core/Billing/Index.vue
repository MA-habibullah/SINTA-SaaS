<template>
  <AppLayout title="Billing & Langganan SaaS Sekolah">
    <Head title="Billing & Langganan SaaS" />

    <div class="space-y-6 pb-12">
      <!-- 1. Header Section -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 flex items-center justify-center text-white shadow-md">
              <i class="bi bi-wallet2 text-lg"></i>
            </div>
            Billing & Langganan SaaS Sekolah
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Kelola paket lisensi, pantau masa aktif langganan, unduh invoice resmi, dan lakukan perpanjangan layanan.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button @click="fetchBillingData" 
                  :disabled="loading"
                  class="px-4 py-2 text-xs font-bold bg-white text-slate-700 hover:bg-slate-50 border border-slate-200/80 rounded-xl shadow-2xs transition flex items-center gap-2 disabled:opacity-50">
            <i class="bi bi-arrow-clockwise" :class="{ 'animate-spin': loading }"></i>
            <span>Segarkan Data</span>
          </button>
        </div>
      </div>

      <!-- Loading State Skeleton -->
      <div v-if="loading && !billingData" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
        <div class="h-44 bg-slate-200 rounded-3xl col-span-2"></div>
        <div class="h-44 bg-slate-200 rounded-3xl"></div>
      </div>

      <!-- Content Area (Zero-SSR Client Hydrated) -->
      <div v-else-if="billingData" class="space-y-6">
        <!-- 2. Subscription Status Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Main Plan Card -->
          <div :class="[
            'lg:col-span-2 rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-xl flex flex-col justify-between',
            planCardThemeClass
          ]">
            <div class="absolute -right-8 -bottom-8 opacity-10 pointer-events-none text-9xl">
              <i class="bi bi-shield-check"></i>
            </div>

            <div class="relative z-10 space-y-4">
              <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2.5">
                  <span class="px-3.5 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-white/20 backdrop-blur-md border border-white/20">
                    {{ tenantInfo.paket_aktif }}
                  </span>
                  <span class="px-3 py-1 rounded-full text-xs font-bold bg-black/20 backdrop-blur-md border border-white/10">
                    Siklus: {{ tenantInfo.billing_cycle === 'annual' ? 'Tahunan (12 Bulan)' : 'Bulanan' }}
                  </span>
                </div>
                <div class="text-right">
                  <span class="text-xs text-white/70 block">Biaya Lisensi</span>
                  <span class="text-xl sm:text-2xl font-black">Rp {{ Number(tenantInfo.subscription_price).toLocaleString('id-ID') }}</span>
                  <span class="text-[10px] text-white/70"> / {{ tenantInfo.billing_cycle === 'annual' ? 'tahun' : 'bulan' }}</span>
                </div>
              </div>

              <div>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight">{{ tenantInfo.nama_sekolah }}</h2>
                <p class="text-xs sm:text-sm text-white/80 mt-1">NPSN: {{ tenantInfo.npsn || '-' }} • Lisensi Resmi Multi-Schema PostgreSQL SINTA</p>
              </div>

              <!-- Quota Chips -->
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2">
                <div class="bg-black/20 p-3 rounded-2xl backdrop-blur-md border border-white/10">
                  <span class="text-[10px] text-white/70 uppercase font-bold block">Batas Siswa</span>
                  <span class="text-sm sm:text-base font-extrabold">{{ tenantInfo.max_siswa_limit }} Siswa</span>
                </div>
                <div class="bg-black/20 p-3 rounded-2xl backdrop-blur-md border border-white/10">
                  <span class="text-[10px] text-white/70 uppercase font-bold block">Kapasitas Storage</span>
                  <span class="text-sm sm:text-base font-extrabold">{{ Math.round(tenantInfo.storage_limit_mb / 1024) }} GB Cloud</span>
                </div>
                <div class="bg-black/20 p-3 rounded-2xl backdrop-blur-md border border-white/10 col-span-2 sm:col-span-1">
                  <span class="text-[10px] text-white/70 uppercase font-bold block">Masa Aktif Hingga</span>
                  <span class="text-sm sm:text-base font-extrabold">{{ formatDate(tenantInfo.subscription_expires_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Bottom Expiry Bar -->
            <div class="relative z-10 mt-6 pt-4 border-t border-white/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
              <div class="flex items-center gap-2">
                <i class="bi bi-clock-history text-base text-amber-300"></i>
                <span>Sisa Waktu Aktif: <strong>{{ tenantInfo.remaining_days }} Hari Lagi</strong></span>
              </div>
              <span class="text-white/80">{{ tenantInfo.is_subscription_active ? '✔ Akses Sekolah Berjalan Normal' : '❌ Status: Masa Aktif Berakhir' }}</span>
            </div>
          </div>

          <!-- Countdown Widget & Quick Action Card -->
          <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between space-y-6">
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                  <i class="bi bi-stopwatch text-indigo-600"></i>
                  Hitung Mundur Masa Aktif
                </h3>
                <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase', statusBadgeClass]">
                  {{ tenantInfo.remaining_days <= 10 ? 'Kritis' : (tenantInfo.remaining_days <= 30 ? 'Peringatan' : 'Aman') }}
                </span>
              </div>

              <!-- 4 Digits Live Countdown -->
              <div class="grid grid-cols-4 gap-2 text-center">
                <div class="bg-slate-50 border border-slate-200/80 p-3 rounded-2xl">
                  <span class="text-xl font-black text-slate-800 font-mono block">{{ padZero(days) }}</span>
                  <span class="text-[9px] uppercase font-bold text-slate-400">Hari</span>
                </div>
                <div class="bg-slate-50 border border-slate-200/80 p-3 rounded-2xl">
                  <span class="text-xl font-black text-slate-800 font-mono block">{{ padZero(hours) }}</span>
                  <span class="text-[9px] uppercase font-bold text-slate-400">Jam</span>
                </div>
                <div class="bg-slate-50 border border-slate-200/80 p-3 rounded-2xl">
                  <span class="text-xl font-black text-slate-800 font-mono block">{{ padZero(minutes) }}</span>
                  <span class="text-[9px] uppercase font-bold text-slate-400">Mnt</span>
                </div>
                <div class="bg-slate-50 border border-slate-200/80 p-3 rounded-2xl">
                  <span class="text-xl font-black text-indigo-600 font-mono block">{{ padZero(seconds) }}</span>
                  <span class="text-[9px] uppercase font-bold text-slate-400">Dtk</span>
                </div>
              </div>

              <div class="bg-slate-50 border border-slate-200/70 p-3.5 rounded-2xl text-xs text-slate-600 leading-relaxed">
                <p v-if="tenantInfo.remaining_days <= 10" class="text-red-600 font-semibold flex items-center gap-1.5">
                  <i class="bi bi-exclamation-triangle-fill shrink-0"></i>
                  Tagihan perpanjangan otomatis telah terbit. Segera lakukan pembayaran untuk mencegah penguncian akun sekolah.
                </p>
                <p v-else>
                  Tagihan periode berikutnya akan diterbitkan otomatis 10 hari sebelum masa aktif berakhir.
                </p>
              </div>
            </div>

            <!-- Unpaid Invoice Quick Action -->
            <div v-if="unpaidInvoice" class="pt-4 border-t border-slate-100 space-y-3">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">Tagihan Aktif:</span>
                <span class="font-extrabold text-red-600">Rp {{ Number(unpaidInvoice.nominal).toLocaleString('id-ID') }}</span>
              </div>
              <button @click="openPaymentModal(unpaidInvoice)"
                      class="w-full py-3 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-extrabold text-xs shadow-lg shadow-red-500/20 transition flex items-center justify-center gap-2">
                <i class="bi bi-credit-card-2-front"></i>
                <span>Bayar Tagihan Sekarang</span>
              </button>
            </div>
            <div v-else class="pt-4 border-t border-slate-100">
              <span class="text-xs font-bold text-emerald-600 flex items-center justify-center gap-1.5 py-2">
                <i class="bi bi-check-circle-fill"></i>
                Seluruh Tagihan Lunas
              </span>
            </div>
          </div>
        </div>

        <!-- 3. Unpaid Invoice Alert Banner (If Any) -->
        <div v-if="unpaidInvoice" class="bg-gradient-to-r from-red-50 to-orange-50 border-2 border-red-200 p-5 rounded-3xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-red-600 text-white flex items-center justify-center font-black text-xl shrink-0 shadow-md shadow-red-600/30">
              <i class="bi bi-receipt"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-red-200 text-red-800">UNPAID</span>
                <h4 class="font-extrabold text-slate-800 text-sm">Invoice #{{ unpaidInvoice.invoice_number }}</h4>
              </div>
              <p class="text-xs text-slate-600 mt-0.5">
                {{ unpaidInvoice.periode }} • Jatuh Tempo: <strong>{{ formatDate(unpaidInvoice.due_date) }}</strong>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 w-full sm:w-auto">
            <a :href="`/sekolah/billing/invoice/${unpaidInvoice.id}/download`" 
               target="_blank"
               class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 font-bold text-xs transition flex items-center gap-2">
              <i class="bi bi-file-earmark-pdf text-red-600"></i>
              <span>Unduh Invoice (PDF)</span>
            </a>
            <button @click="openPaymentModal(unpaidInvoice)"
                    class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs shadow-md shadow-red-600/30 transition flex items-center gap-2">
              <i class="bi bi-credit-card"></i>
              <span>Bayar Sekarang</span>
            </button>
          </div>
        </div>

        <!-- 4. Invoice History Table Section -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-base font-black text-slate-800">Riwayat Tagihan & Pembayaran SaaS</h3>
              <p class="text-xs text-slate-500 mt-0.5">Daftar invoice resmi dan bukti transaksi langganan sekolah.</p>
            </div>

            <!-- Table Filters (SearchableSelect + Search Keyword) -->
            <div class="flex items-center gap-3 flex-wrap">
              <div class="w-48">
                <SearchableSelect 
                  v-model="filters.status" 
                  :options="statusFilterOptions"
                  placeholder="-- Filter Status --"
                  search-placeholder="Cari status..."
                  :allow-clear="true"
                  @change="handleFilterChange"
                />
              </div>
              <div class="relative w-48 sm:w-60">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input v-model="filters.search" 
                       @input="handleFilterChange"
                       type="text" 
                       placeholder="Cari no. invoice..."
                       class="w-full pl-9 pr-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" />
              </div>
            </div>
          </div>

          <!-- Table Content -->
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-100">
                  <th class="p-4 pl-6">No. Invoice</th>
                  <th class="p-4">Periode Langganan</th>
                  <th class="p-4">Nominal</th>
                  <th class="p-4">Jatuh Tempo</th>
                  <th class="p-4">Status</th>
                  <th class="p-4">Tanggal Lunas</th>
                  <th class="p-4 pr-6 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-if="!invoicesList || invoicesList.length === 0">
                  <td colspan="7" class="p-12 text-center text-slate-400">
                    <i class="bi bi-receipt-cutoff text-3xl block mb-2 opacity-50"></i>
                    Belum ada riwayat tagihan yang ditemukan.
                  </td>
                </tr>
                <tr v-for="inv in invoicesList" :key="inv.id" class="hover:bg-slate-50/60 transition">
                  <td class="p-4 pl-6 font-bold text-slate-800">
                    <span class="font-mono text-blue-600">{{ inv.invoice_number }}</span>
                  </td>
                  <td class="p-4 text-slate-600 font-medium">
                    {{ inv.periode }}
                  </td>
                  <td class="p-4 font-black text-slate-800">
                    Rp {{ Number(inv.nominal).toLocaleString('id-ID') }}
                  </td>
                  <td class="p-4 text-slate-600">
                    {{ formatDate(inv.due_date) }}
                  </td>
                  <td class="p-4">
                    <span :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase',
                      inv.status === 'PAID' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'
                    ]">
                      {{ inv.status === 'PAID' ? 'Lunas' : 'Belum Bayar' }}
                    </span>
                  </td>
                  <td class="p-4 text-slate-500">
                    {{ inv.paid_at ? formatDate(inv.paid_at) : '-' }}
                  </td>
                  <td class="p-4 pr-6 text-right space-x-2">
                    <a :href="`/sekolah/billing/invoice/${inv.id}/download`" 
                       target="_blank"
                       class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition inline-flex items-center gap-1">
                      <i class="bi bi-printer"></i>
                      <span>PDF</span>
                    </a>
                    <button v-if="inv.status !== 'PAID'" 
                            @click="openPaymentModal(inv)"
                            class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-[11px] transition inline-flex items-center gap-1">
                      <i class="bi bi-wallet2"></i>
                      <span>Bayar</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 5. Payment Confirmation Modal -->
    <div v-if="paymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 space-y-6 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
              <i class="bi bi-credit-card text-lg"></i>
            </div>
            <div>
              <h3 class="font-black text-slate-800 text-base">Pembayaran Tagihan SaaS</h3>
              <p class="text-xs text-slate-500">Invoice: {{ selectedInvoice?.invoice_number }}</p>
            </div>
          </div>
          <button @click="paymentModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="bg-blue-50/70 border border-blue-200/70 p-4 rounded-2xl space-y-2 text-xs text-blue-900">
          <div class="flex justify-between">
            <span class="text-blue-700">Periode Tagihan:</span>
            <span class="font-bold">{{ selectedInvoice?.periode }}</span>
          </div>
          <div class="flex justify-between text-sm pt-1 border-t border-blue-200/60">
            <span class="font-bold text-blue-800">Total Pembayaran:</span>
            <span class="font-black text-blue-900">Rp {{ Number(selectedInvoice?.nominal).toLocaleString('id-ID') }}</span>
          </div>
        </div>

        <form @submit.prevent="submitPaymentConfirmation" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Metode Pembayaran</label>
            <SearchableSelect 
              v-model="paymentForm.payment_method" 
              :options="paymentMethodOptions"
              placeholder="-- Pilih Cara Bayar --"
              search-placeholder="Cari metode pembayaran..."
            />
          </div>

          <div v-if="paymentForm.payment_method === 'TRANSFER_MANUAL'" class="bg-slate-50 border border-slate-200 p-3.5 rounded-2xl text-xs space-y-1">
            <span class="text-slate-500 block">Nomor Rekening Tujuan:</span>
            <div class="font-black text-slate-800 text-sm">BCA: 8830-1928-3900</div>
            <div class="text-slate-600">a.n PT SINTA EDUKASI TEKNOLOGI</div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Referensi Transaksi (Opsional)</label>
            <input v-model="paymentForm.transaction_reference" 
                   type="text" 
                   placeholder="Contoh: REF-BCA-98129031"
                   class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
            <textarea v-model="paymentForm.notes" 
                      rows="2" 
                      placeholder="Keterangan tambahan..."
                      class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>

          <div class="pt-4 flex items-center justify-end gap-3">
            <button type="button" @click="paymentModalOpen = false" 
                    class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
              Batal
            </button>
            <button type="submit" 
                    :disabled="submittingPayment"
                    class="px-5 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white rounded-xl shadow-md shadow-blue-500/20 transition flex items-center gap-2 disabled:opacity-50">
              <i class="bi bi-check2-circle"></i>
              <span>{{ submittingPayment ? 'Memproses...' : 'Konfirmasi Lunas' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useMemorySecurity } from '@/Utils/cryptoSecurity.js';
import axios from 'axios';

// Zero-SSR Props
const props = defineProps({
  items: Object,
  billingData: Object,
});

const loading = ref(true);
const billingData = ref(null);
const paymentModalOpen = ref(false);
const selectedInvoice = ref(null);
const submittingPayment = ref(false);

const filters = ref({
  status: '',
  search: '',
});

const paymentForm = ref({
  payment_method: 'TRANSFER_MANUAL',
  transaction_reference: '',
  notes: '',
});

useMemorySecurity([billingData, selectedInvoice]);

const statusFilterOptions = [
  { id: '', label: 'Semua Status' },
  { id: 'PAID', label: 'Lunas (PAID)' },
  { id: 'UNPAID', label: 'Belum Bayar (UNPAID)' },
];

const paymentMethodOptions = [
  { id: 'TRANSFER_MANUAL', label: 'Transfer Bank Manual (BCA)', subLabel: '8830-1928-3900 a.n SINTA' },
  { id: 'MIDTRANS_VA', label: 'Virtual Account Otomatis (Midtrans)' },
  { id: 'QRIS', label: 'QRIS Real-Time Scan' },
  { id: 'CREDIT_CARD', label: 'Kartu Kredit / Debit Visa & Mastercard' },
];

const tenantInfo = computed(() => billingData.value?.tenant || {});
const unpaidInvoice = computed(() => billingData.value?.unpaidInvoice || null);
const invoicesList = computed(() => billingData.value?.invoices?.data || []);

// Real-time Countdown timer logic
const remainingSec = ref(0);
let timerInterval = null;

const padZero = (n) => String(Math.max(0, n)).padStart(2, '0');
const days = computed(() => Math.floor(remainingSec.value / 86400));
const hours = computed(() => Math.floor((remainingSec.value % 86400) / 3600));
const minutes = computed(() => Math.floor((remainingSec.value % 3600) / 60));
const seconds = computed(() => Math.floor(remainingSec.value % 60));

const planCardThemeClass = computed(() => {
  if (days.value <= 10) return 'bg-gradient-to-br from-red-600 via-rose-700 to-amber-800';
  if (days.value <= 30) return 'bg-gradient-to-br from-amber-600 via-orange-600 to-yellow-700';
  return 'bg-gradient-to-br from-blue-700 via-indigo-700 to-slate-900';
});

const statusBadgeClass = computed(() => {
  if (days.value <= 10) return 'bg-red-100 text-red-800';
  if (days.value <= 30) return 'bg-amber-100 text-amber-800';
  return 'bg-emerald-100 text-emerald-800';
});

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  } catch (e) {
    return dateStr;
  }
};

const fetchBillingData = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/sekolah/billing?async=1', {
      params: {
        status: filters.value.status,
        search: filters.value.search,
      }
    });
    if (res.data?.success) {
      billingData.value = res.data;
      remainingSec.value = res.data.tenant?.remaining_seconds || 0;
    }
  } catch (err) {
    console.error('Gagal memuat data billing:', err);
  } finally {
    loading.value = false;
  }
};

const handleFilterChange = () => {
  fetchBillingData();
};

const openPaymentModal = (invoice) => {
  selectedInvoice.value = invoice;
  paymentForm.value = {
    payment_method: 'TRANSFER_MANUAL',
    transaction_reference: 'REF-' + Math.random().toString(36).substring(2, 10).toUpperCase(),
    notes: '',
  };
  paymentModalOpen.value = true;
};

const submitPaymentConfirmation = async () => {
  if (!selectedInvoice.value) return;
  submittingPayment.value = true;
  try {
    const res = await axios.post(`/sekolah/billing/pay/${selectedInvoice.value.id}`, paymentForm.value);
    if (res.data?.success) {
      paymentModalOpen.value = false;
      await fetchBillingData();
      alert('Pembayaran berhasil dikonfirmasi! Masa aktif sekolah telah diperpanjang.');
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal memproses pembayaran.');
  } finally {
    submittingPayment.value = false;
  }
};

onMounted(() => {
  fetchBillingData();
  timerInterval = setInterval(() => {
    if (remainingSec.value > 0) {
      remainingSec.value--;
    }
  }, 1000);
});

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval);
  }
});
</script>
