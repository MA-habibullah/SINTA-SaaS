<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Portal Berita & CMS Sekolah</h1>
      <p class="text-xs text-slate-500">Publikasi pengumuman resmi dan artikel portal berita sekolah.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Pengumuman -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Pengumuman Sekolah Terkini</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="p in pengumumanList?.data || []" :key="p.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ p.judul }}</div>
              <div class="text-slate-400 text-2xs">Audiens: {{ p.target_audiens }} | {{ p.tanggal_publikasi }}</div>
            </div>
            <span class="px-2 py-0.5 rounded text-2xs font-bold bg-blue-50 text-blue-700">{{ p.kategori }}</span>
          </div>
        </div>
      </div>

      <!-- Berita -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Artikel Berita Sekolah</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="b in beritaList?.data || []" :key="b.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ b.judul_berita }}</div>
              <div class="text-slate-400 text-2xs">Penulis: {{ b.penulis_nama }}</div>
            </div>
            <span class="px-2 py-0.5 rounded text-2xs font-bold bg-emerald-50 text-emerald-700">Terbit</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  pengumumanList: Object,
  beritaList: Object,
});
</script>
