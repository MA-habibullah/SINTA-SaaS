<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Identitas & Legalitas Sekolah</h1>
        <p class="text-xs text-slate-500">Profil resmi lembaga, NPSN, akreditasi, dan legalitas stempel/logo.</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-2xs space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Sekolah Resmi</label>
            <input :value="identitas?.nama_sekolah" disabled class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl font-bold text-slate-700" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nomor Pokok Sekolah Nasional (NPSN)</label>
            <input :value="identitas?.npsn" disabled class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl font-mono text-slate-700" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Jenjang Pendidikan</label>
            <input :value="identitas?.jenjang" disabled class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-slate-700" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Akreditasi</label>
            <input :value="identitas?.akreditasi" disabled class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl font-bold text-blue-600" />
          </div>
          <div class="sm:col-span-2">
            <label class="block font-bold text-slate-700 mb-1">Alamat Lembaga</label>
            <input :value="identitas?.alamat" disabled class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-slate-700" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Kepala Sekolah</label>
            <input :value="identitas?.nama_kepala_sekolah" disabled class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-slate-700" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">NIP Kepala Sekolah</label>
            <input :value="identitas?.nip_kepala_sekolah" disabled class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl font-mono text-slate-700" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  identitas: Object,
});
</script>
