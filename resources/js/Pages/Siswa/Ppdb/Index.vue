<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">PPDB & Calon Siswa Baru</h1>
        <p class="text-xs text-slate-500">Pendaftaran, verifikasi berkas, dan konversi ke Buku Induk Siswa.</p>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">No. Daftar</th>
              <th class="py-3.5 px-4">Nama Calon Siswa</th>
              <th class="py-3.5 px-4">Jalur</th>
              <th class="py-3.5 px-4">Pilihan Jurusan</th>
              <th class="py-3.5 px-4">Verifikasi Berkas</th>
              <th class="py-3.5 px-4">Status Seleksi</th>
              <th class="py-3.5 px-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="p in pendaftarList?.data || []" :key="p.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ p.no_pendaftaran }}</td>
              <td class="py-3 px-4 font-bold text-slate-800">{{ p.nama_lengkap }}</td>
              <td class="py-3 px-4">{{ p.jalur_pendaftaran || 'Reguler' }}</td>
              <td class="py-3 px-4">{{ p.pilihan_jurusan_1 }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded-md text-2xs font-bold bg-amber-50 text-amber-700">
                  {{ p.status_verifikasi_berkas || 'Menunggu' }}
                </span>
              </td>
              <td class="py-3 px-4">
                <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold" :class="p.is_diterima ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-500'">
                  {{ p.is_diterima ? 'DITERIMA' : 'PROSES SELEKSI' }}
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <button class="px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold rounded-lg transition text-xs">
                  Verifikasi
                </button>
              </td>
            </tr>
            <tr v-if="!pendaftarList?.data?.length">
              <td colspan="7" class="py-8 text-center text-slate-400">Belum ada data pendaftar PPDB.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  pendaftarList: Object,
  filters: Object,
});
</script>
