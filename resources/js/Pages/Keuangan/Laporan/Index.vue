<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Laporan Keuangan & Arus Kas</h1>
      <p class="text-xs text-slate-500">Rekapitulasi penerimaan kas, piutang tagihan siswa, dan saldo kas/bank sekolah.</p>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Penerimaan Kas Periode Ini</span>
        <div class="text-2xl font-black text-emerald-600 mt-1">Rp {{ Number(totalPemasukan || 0).toLocaleString('id-ID') }}</div>
        <div class="text-xs text-emerald-600 font-semibold mt-1">Total Kas Masuk</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Total Piutang / Tunggakan</span>
        <div class="text-2xl font-black text-red-600 mt-1">Rp {{ Number(totalTunggakan || 0).toLocaleString('id-ID') }}</div>
        <div class="text-xs text-slate-500 mt-1">Sisa Tagihan Belum Lunas</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Saldo Kas & Bank Aktif</span>
        <div class="text-2xl font-black text-blue-600 mt-1">Rp {{ Number(totalKas || 0).toLocaleString('id-ID') }}</div>
        <div class="text-xs text-slate-500 mt-1">Total Likuiditas Sekolah</div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  totalPemasukan: Number,
  totalTunggakan: Number,
  totalKas: Number,
  kasList: Array,
  riwayatTransaksi: Object,
  filters: Object,
});
</script>
