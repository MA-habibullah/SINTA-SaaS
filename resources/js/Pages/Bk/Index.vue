<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Bimbingan & Konseling (BK)</h1>
      <p class="text-xs text-slate-500">Pencatatan pelanggaran disiplin dan histori sesi konseling pendampingan siswa.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Pelanggaran Terakhir -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Pelanggaran Disiplin Terkini</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="p in pelanggaranList?.data || []" :key="p.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ p.siswa?.nama_lengkap }}</div>
              <div class="text-slate-500 text-2xs">{{ p.nama_pelanggaran }} ({{ p.tanggal_kejadian }})</div>
            </div>
            <span class="px-2 py-0.5 rounded font-black text-red-600 bg-red-50 text-2xs">+{{ p.poin_pelanggaran }} Poin</span>
          </div>
        </div>
      </div>

      <!-- Sesi Konseling Terkini -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Sesi Konseling Siswa</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="k in konselingList?.data || []" :key="k.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ k.siswa?.nama_lengkap }}</div>
              <div class="text-slate-500 text-2xs">{{ k.topik_masalah }} ({{ k.jenis_konseling }})</div>
            </div>
            <span class="px-2 py-0.5 rounded text-2xs font-bold bg-blue-50 text-blue-700">{{ k.status_kasus }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  pelanggaranList: Object,
  konselingList: Object,
});
</script>
