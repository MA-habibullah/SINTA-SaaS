<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Pos & Tarif Pembayaran Keuangan</h1>
        <p class="text-xs text-slate-500">Konfigurasi jenis pos tagihan (SPP bulanan, uang gedung) dan nominal per tingkat.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div v-for="pos in posList" :key="pos.id" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <div class="flex justify-between items-start mb-3">
          <div>
            <h3 class="font-extrabold text-slate-800 text-sm">{{ pos.nama_pos }}</h3>
            <span class="text-2xs font-mono font-bold text-slate-400">KODE: {{ pos.kode_pos }}</span>
          </div>
          <span class="px-2 py-0.5 rounded-md text-2xs font-bold bg-blue-50 text-blue-700">
            {{ pos.tipe_pembayaran }}
          </span>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
          <div class="text-xs font-bold text-slate-500">Tarif per Tingkat:</div>
          <div v-for="t in pos.tarif || []" :key="t.id" class="flex justify-between text-xs font-medium text-slate-700">
            <span>Tingkat {{ t.tingkat }}</span>
            <span class="font-bold text-slate-900">Rp {{ Number(t.nominal).toLocaleString('id-ID') }}</span>
          </div>
          <div v-if="!pos.tarif?.length" class="text-2xs text-slate-400">Tarif default berlaku.</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  posList: Array,
});
</script>
