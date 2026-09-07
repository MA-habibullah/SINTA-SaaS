<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Pencetakan Rapor & Hasil Belajar</h1>
      <p class="text-xs text-slate-500">Preview lembar laporan capaian kompetensi dan cetak massal PDF via background worker.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs max-w-xl">
      <h3 class="font-extrabold text-slate-800 text-sm mb-4">Cetak Massal PDF Rapor Satu Kelas</h3>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Kelas</label>
          <select v-model="form.kelas_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
            <option value="">-- Pilih Rombel --</option>
            <option v-for="k in kelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Semester</label>
          <select v-model="form.semester" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
            <option value="Ganjil">Semester Ganjil</option>
            <option value="Genap">Semester Genap</option>
          </select>
        </div>

        <button @click="dispatchBulkJob" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold rounded-xl transition shadow-md flex items-center justify-center gap-2">
          <i class="bi bi-file-earmark-pdf-fill"></i> Jadwalkan Pencetakan PDF Massal
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  kelasList: Array,
});

const form = ref({
  kelas_id: '',
  semester: 'Ganjil',
});

const dispatchBulkJob = () => {
  if (!form.value.kelas_id) {
    alert('Silakan pilih kelas terlebih dahulu.');
    return;
  }
  router.post('/akademik/rapor/bulk-queue', {
    kelas_id: form.value.kelas_id,
    tahun_ajaran_id: 'default',
    semester: form.value.semester,
  });
};
</script>
