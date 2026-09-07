<template>
  <AppLayout>
    <!-- Header Details -->
    <div class="flex items-center gap-4 mb-6">
      <a href="/siswa/buku-induk" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition shadow-2xs">
        <i class="bi bi-arrow-left"></i>
      </a>
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">{{ siswa?.nama_lengkap }}</h1>
        <p class="text-xs text-slate-500">NISN: {{ siswa?.nisn }} | Kelas: {{ siswa?.kelas_saat_ini || '-' }}</p>
      </div>
    </div>

    <!-- Student Detail Profile 8-Tabs Container -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Profile Card -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs text-center">
        <div class="w-24 h-24 mx-auto rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 text-3xl mb-4 overflow-hidden">
          <img v-if="siswa?.foto_url" :src="siswa.foto_url" class="w-full h-full object-cover" />
          <i v-else class="bi bi-person"></i>
        </div>
        <h3 class="font-extrabold text-slate-800 text-base">{{ siswa?.nama_lengkap }}</h3>
        <p class="text-xs text-blue-600 font-semibold mt-0.5">{{ siswa?.jurusan || 'Jurusan Belum Diset' }}</p>
        <div class="mt-4 pt-4 border-t border-slate-100 text-left text-xs space-y-2">
          <div class="flex justify-between"><span class="text-slate-400">NISN:</span> <span class="font-bold">{{ siswa?.nisn }}</span></div>
          <div class="flex justify-between"><span class="text-slate-400">Jenis Kelamin:</span> <span class="font-bold">{{ siswa?.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span></div>
          <div class="flex justify-between"><span class="text-slate-400">Status:</span> <span class="font-bold text-emerald-600">{{ siswa?.status_siswa || 'Aktif' }}</span></div>
        </div>
      </div>

      <!-- Tab Details Card -->
      <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
        <h4 class="font-extrabold text-slate-800 text-sm mb-4 border-b border-slate-100 pb-3">Informasi Biodata Lengkap</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div><span class="text-slate-400 block mb-0.5">Tempat, Tanggal Lahir</span> <span class="font-bold text-slate-700">{{ siswa?.tempat_lahir || '-' }}, {{ siswa?.tanggal_lahir || '-' }}</span></div>
          <div><span class="text-slate-400 block mb-0.5">Agama</span> <span class="font-bold text-slate-700">{{ siswa?.agama || '-' }}</span></div>
          <div><span class="text-slate-400 block mb-0.5">Nama Ayah</span> <span class="font-bold text-slate-700">{{ siswa?.nama_ayah || '-' }}</span></div>
          <div><span class="text-slate-400 block mb-0.5">Nama Ibu</span> <span class="font-bold text-slate-700">{{ siswa?.nama_ibu || '-' }}</span></div>
          <div class="sm:col-span-2"><span class="text-slate-400 block mb-0.5">Alamat Tempat Tinggal</span> <span class="font-bold text-slate-700">{{ siswa?.alamat_tinggal || '-' }}</span></div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  siswa: Object,
});
</script>
