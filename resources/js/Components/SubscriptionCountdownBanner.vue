<template>
  <div v-if="tenant && tenant.remaining_seconds !== undefined && !isSuperAdmin" 
       :class="[
         'w-full rounded-2xl p-5 sm:p-6 text-white shadow-md transition-all duration-300 relative overflow-hidden',
         bannerThemeClass
       ]">
    <!-- Background Watermark Pattern -->
    <div class="absolute -right-6 -bottom-6 opacity-10 pointer-events-none text-9xl">
      <i class="bi bi-clock-history"></i>
    </div>

    <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
      <!-- Left: Plan & Expiry Details -->
      <div class="space-y-1.5 max-w-xl">
        <div class="flex items-center gap-2.5 flex-wrap">
          <span class="px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-white/20 backdrop-blur-md border border-white/20">
            {{ tenant.paket_aktif || 'Paket Enterprise SaaS' }}
          </span>
          <span :class="['px-3 py-1 rounded-full text-[11px] font-bold flex items-center gap-1.5', statusPillClass]">
            <span class="w-2 h-2 rounded-full animate-ping" :class="statusDotClass"></span>
            {{ statusLabel }}
          </span>
        </div>
        <h3 class="text-lg sm:text-xl font-extrabold leading-tight">
          {{ bannerTitle }}
        </h3>
        <p class="text-xs sm:text-sm text-white/80 leading-relaxed">
          Masa aktif paket sekolah berakhir pada <strong class="text-white">{{ formattedExpiryDate }}</strong>. Pastikan pembayaran perpanjangan dilakukan tepat waktu untuk menjaga akses sistem tetap lancar.
        </p>
      </div>

      <!-- Right: Real-time Countdown Timer Boxes & Quick Action Button -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full lg:w-auto shrink-0">
        <!-- 4 Digits Countdown Boxes -->
        <div class="grid grid-cols-4 gap-2 text-center bg-black/20 p-2.5 rounded-2xl backdrop-blur-md border border-white/10 shrink-0">
          <!-- Days -->
          <div class="bg-white/15 px-3 py-2 rounded-xl min-w-[54px]">
            <div class="text-xl sm:text-2xl font-black font-mono leading-none tracking-tight">{{ padZero(days) }}</div>
            <div class="text-[9px] uppercase font-bold text-white/70 mt-1 tracking-wider">Hari</div>
          </div>
          <!-- Hours -->
          <div class="bg-white/15 px-3 py-2 rounded-xl min-w-[54px]">
            <div class="text-xl sm:text-2xl font-black font-mono leading-none tracking-tight">{{ padZero(hours) }}</div>
            <div class="text-[9px] uppercase font-bold text-white/70 mt-1 tracking-wider">Jam</div>
          </div>
          <!-- Minutes -->
          <div class="bg-white/15 px-3 py-2 rounded-xl min-w-[54px]">
            <div class="text-xl sm:text-2xl font-black font-mono leading-none tracking-tight">{{ padZero(minutes) }}</div>
            <div class="text-[9px] uppercase font-bold text-white/70 mt-1 tracking-wider">Mnt</div>
          </div>
          <!-- Seconds -->
          <div class="bg-white/15 px-3 py-2 rounded-xl min-w-[54px]">
            <div class="text-xl sm:text-2xl font-black font-mono leading-none tracking-tight text-amber-200">{{ padZero(seconds) }}</div>
            <div class="text-[9px] uppercase font-bold text-white/70 mt-1 tracking-wider">Dtk</div>
          </div>
        </div>

        <!-- Action Button -->
        <Link href="/sekolah/billing" 
              :class="[
                'px-5 py-3 rounded-xl text-xs sm:text-sm font-extrabold flex items-center justify-center gap-2 shadow-lg transition-all duration-200 shrink-0',
                actionBtnClass
              ]">
          <i class="bi bi-wallet2"></i>
          <span>{{ days <= 10 ? 'Bayar Tagihan' : 'Detail Billing' }}</span>
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  tenant: {
    type: Object,
    default: () => ({})
  },
  isSuperAdmin: {
    type: Boolean,
    default: false
  }
});

const remainingSec = ref(props.tenant?.remaining_seconds || 0);
let timerInterval = null;

const padZero = (n) => String(Math.max(0, n)).padStart(2, '0');

const days = computed(() => Math.floor(remainingSec.value / 86400));
const hours = computed(() => Math.floor((remainingSec.value % 86400) / 3600));
const minutes = computed(() => Math.floor((remainingSec.value % 3600) / 60));
const seconds = computed(() => Math.floor(remainingSec.value % 60));

// Theme & Status logic
const bannerThemeClass = computed(() => {
  if (days.value <= 10) {
    return 'bg-gradient-to-r from-red-600 via-rose-600 to-amber-700 ring-2 ring-red-400/50';
  }
  if (days.value <= 30) {
    return 'bg-gradient-to-r from-amber-600 via-orange-600 to-yellow-700';
  }
  return 'bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-700';
});

const statusPillClass = computed(() => {
  if (days.value <= 10) return 'bg-red-950/40 text-red-200 border border-red-300/30';
  if (days.value <= 30) return 'bg-amber-950/40 text-amber-200 border border-amber-300/30';
  return 'bg-emerald-950/40 text-emerald-200 border border-emerald-300/30';
});

const statusDotClass = computed(() => {
  if (days.value <= 10) return 'bg-red-400';
  if (days.value <= 30) return 'bg-amber-400';
  return 'bg-emerald-400';
});

const statusLabel = computed(() => {
  if (days.value <= 0) return 'Kedaluwarsa';
  if (days.value <= 10) return 'Kritis (Tagihan Siap Bayar)';
  if (days.value <= 30) return 'Masa Tenggang Peringatan';
  return 'Paket Aktif & Normal';
});

const bannerTitle = computed(() => {
  if (days.value <= 0) return 'Masa Aktif Langganan Telah Habis';
  if (days.value <= 10) return 'Peringatan: Tagihan Perpanjangan Telah Terbit!';
  if (days.value <= 30) return 'Masa Langganan Akan Segera Berakhir';
  return 'Status Langganan Sekolah Aktif';
});

const actionBtnClass = computed(() => {
  if (days.value <= 10) {
    return 'bg-yellow-400 hover:bg-yellow-300 text-slate-900 animate-pulse font-black';
  }
  return 'bg-white hover:bg-slate-100 text-slate-900 hover:shadow-xl';
});

const formattedExpiryDate = computed(() => {
  if (!props.tenant?.subscription_expires_at) return 'Tidak Diketahui';
  try {
    const d = new Date(props.tenant.subscription_expires_at);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' WIB';
  } catch (e) {
    return props.tenant.subscription_expires_at;
  }
});

onMounted(() => {
  if (remainingSec.value > 0) {
    timerInterval = setInterval(() => {
      if (remainingSec.value > 0) {
        remainingSec.value--;
      } else {
        clearInterval(timerInterval);
      }
    }, 1000);
  }
});

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval);
  }
});
</script>
