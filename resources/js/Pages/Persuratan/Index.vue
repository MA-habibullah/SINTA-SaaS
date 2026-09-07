<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Persuratan & Disposisi Digital</h1>
      <p class="text-xs text-slate-500">Agenda surat masuk, penerbitan surat keluar, dan lembar disposisi digital pimpinan.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Agenda Surat Masuk</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="s in suratMasukList?.data || []" :key="s.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ s.perihal }}</div>
              <div class="text-slate-400 text-2xs">Pengirim: {{ s.pengirim }} | {{ s.nomor_surat_asal }}</div>
            </div>
            <span class="px-2 py-0.5 rounded text-2xs font-bold bg-blue-50 text-blue-700">{{ s.status_disposisi }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Arsip Surat Keluar</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="s in suratKeluarList?.data || []" :key="s.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ s.perihal }}</div>
              <div class="text-slate-400 text-2xs">Tujuan: {{ s.tujuan_surat }} | {{ s.nomor_surat }}</div>
            </div>
            <span class="px-2 py-0.5 rounded text-2xs font-bold bg-emerald-50 text-emerald-700">{{ s.status_surat }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  suratMasukList: Object,
  suratKeluarList: Object,
});
</script>
