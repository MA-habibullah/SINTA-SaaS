<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Master Data Akademik</h1>
      <p class="text-xs text-slate-500">Tahun ajaran, kurikulum, jurusan keahlian, rombel kelas, dan mata pelajaran.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Daftar Kelas -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3 flex items-center justify-between">
          <span>Rombongan Belajar (Kelas)</span>
          <span class="text-xs text-blue-600 font-semibold">{{ kelasList?.length || 0 }} Kelas</span>
        </h3>
        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
          <div v-for="k in kelasList" :key="k.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <span class="font-bold text-slate-800">{{ k.nama_kelas }}</span>
            <span class="text-slate-500">{{ k.jurusan?.nama_jurusan || '-' }}</span>
          </div>
        </div>
      </div>

      <!-- Daftar Mata Pelajaran -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3 flex items-center justify-between">
          <span>Mata Pelajaran Aktif</span>
          <span class="text-xs text-blue-600 font-semibold">{{ mapelList?.length || 0 }} Mapel</span>
        </h3>
        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
          <div v-for="m in mapelList" :key="m.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <span class="font-bold text-slate-800">{{ m.nama_mapel }}</span>
            <span class="font-mono text-slate-400 font-bold">{{ m.kode_mapel }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  tahunAjaranList: Array,
  jurusanList: Array,
  kelasList: Array,
  mapelList: Array,
});
</script>
