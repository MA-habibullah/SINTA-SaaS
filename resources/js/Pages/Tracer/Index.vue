<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Tracer Study Alumni</h1>
      <p class="text-xs text-slate-500">Penelusuran keterserapan alumni di dunia kerja, wirausaha, dan perguruan tinggi.</p>
    </div>

    <!-- Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Total Responden</span>
        <div class="text-2xl font-black text-slate-800 mt-1">{{ totalResponden || 0 }}</div>
      </div>
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Bekerja</span>
        <div class="text-2xl font-black text-blue-600 mt-1">{{ bekerjaCount || 0 }}</div>
      </div>
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Wirausaha</span>
        <div class="text-2xl font-black text-emerald-600 mt-1">{{ wirausahaCount || 0 }}</div>
      </div>
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Kuliah</span>
        <div class="text-2xl font-black text-purple-600 mt-1">{{ kuliahCount || 0 }}</div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  alumniList: Object,
  totalResponden: Number,
  bekerjaCount: Number,
  wirausahaCount: Number,
  kuliahCount: Number,
  filters: Object,
});
</script>
