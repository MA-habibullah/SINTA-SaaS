<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Mutasi Siswa (Masuk & Keluar)</h1>
        <p class="text-xs text-slate-500">Pencatatan perpindahan peserta didik dan penerbitan surat mutasi.</p>
      </div>
      <button class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
        <i class="bi bi-arrow-left-right"></i> Catat Mutasi Baru
      </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">Tanggal Mutasi</th>
              <th class="py-3.5 px-4">Nama Siswa</th>
              <th class="py-3.5 px-4">Jenis</th>
              <th class="py-3.5 px-4">Sekolah Asal / Tujuan</th>
              <th class="py-3.5 px-4">No. Surat</th>
              <th class="py-3.5 px-4">Alasan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="m in mutasiList?.data || []" :key="m.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-mono">{{ m.tanggal_mutasi }}</td>
              <td class="py-3 px-4 font-bold text-slate-800">{{ m.siswa?.nama_lengkap }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded-md text-2xs font-bold uppercase" :class="m.jenis_mutasi === 'masuk' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                  {{ m.jenis_mutasi }}
                </span>
              </td>
              <td class="py-3 px-4">{{ m.sekolah_asal_tujuan }}</td>
              <td class="py-3 px-4 font-mono">{{ m.nomor_surat_mutasi || '-' }}</td>
              <td class="py-3 px-4 text-slate-500">{{ m.alasan_mutasi || '-' }}</td>
            </tr>
            <tr v-if="!mutasiList?.data?.length">
              <td colspan="6" class="py-8 text-center text-slate-400">Belum ada data mutasi siswa.</td>
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
  mutasiList: Object,
  filters: Object,
});
</script>
