<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Portofolio Prestasi & Penghargaan</h1>
        <p class="text-xs text-slate-500">Pencatatan prestasi kejuaraan akademik dan non-akademik siswa.</p>
      </div>
      <button class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
        <i class="bi bi-trophy-fill"></i> Tambah Prestasi
      </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">Nama Siswa</th>
              <th class="py-3.5 px-4">Nama Lomba / Kegiatan</th>
              <th class="py-3.5 px-4">Kategori</th>
              <th class="py-3.5 px-4">Tingkat</th>
              <th class="py-3.5 px-4">Peringkat</th>
              <th class="py-3.5 px-4">Tahun</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="p in prestasiList?.data || []" :key="p.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-bold text-slate-800">{{ p.siswa?.nama_lengkap }}</td>
              <td class="py-3 px-4 font-bold text-blue-600">{{ p.nama_kegiatan_lomba }}</td>
              <td class="py-3 px-4 capitalize">{{ p.jenis_prestasi }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded-md text-2xs font-bold bg-indigo-50 text-indigo-700 uppercase">
                  {{ p.tingkat }}
                </span>
              </td>
              <td class="py-3 px-4 font-bold text-amber-600">{{ p.peringkat_juara }}</td>
              <td class="py-3 px-4 font-mono">{{ p.tahun }}</td>
            </tr>
            <tr v-if="!prestasiList?.data?.length">
              <td colspan="6" class="py-8 text-center text-slate-400">Belum ada data prestasi tercatat.</td>
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
  prestasiList: Object,
  filters: Object,
});
</script>
