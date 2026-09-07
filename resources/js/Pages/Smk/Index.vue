<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">SMK Vokasi & Prakerin PKL</h1>
      <p class="text-xs text-slate-500">Kerjasama MoU Industri Mitra DUDI dan penempatan siswa magang/PKL.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Mitra Industri (DUDI)</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="m in mitraList?.data || []" :key="m.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ m.nama_perusahaan }}</div>
              <div class="text-slate-400 text-2xs">{{ m.bidang_usaha }} | Kota: {{ m.kota || '-' }}</div>
            </div>
            <span class="px-2 py-0.5 rounded text-2xs font-bold bg-blue-50 text-blue-700">{{ m.pkl_count || 0 }} Siswa PKL</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Penempatan PKL Siswa</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="p in pklList?.data || []" :key="p.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ p.siswa?.nama_lengkap }}</div>
              <div class="text-slate-500 text-2xs">Tempat: {{ p.mitra?.nama_perusahaan }}</div>
            </div>
            <span class="px-2 py-0.5 rounded text-2xs font-bold bg-emerald-50 text-emerald-700">{{ p.status_pkl }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  mitraList: Object,
  pklList: Object,
});
</script>
