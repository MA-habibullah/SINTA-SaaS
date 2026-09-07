<template>
  <AppLayout>
    <!-- Header Page Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Buku Induk Siswa</h1>
        <p class="text-xs text-slate-500">Database induk seluruh peserta didik aktif dan terdaftar di sekolah.</p>
      </div>
      <div class="flex items-center gap-2">
        <button class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
          <i class="bi bi-person-plus-fill"></i>
          <span>Tambah Siswa</span>
        </button>
      </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">NISN / NIS</th>
              <th class="py-3.5 px-4">Nama Lengkap</th>
              <th class="py-3.5 px-4">L/P</th>
              <th class="py-3.5 px-4">Kelas</th>
              <th class="py-3.5 px-4">Jurusan</th>
              <th class="py-3.5 px-4">Status</th>
              <th class="py-3.5 px-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="item in siswaList?.data || []" :key="item.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ item.nisn }} / {{ item.nis }}</td>
              <td class="py-3 px-4 font-bold text-slate-800">{{ item.nama_lengkap }}</td>
              <td class="py-3 px-4">{{ item.jenis_kelamin }}</td>
              <td class="py-3 px-4">{{ item.kelas_saat_ini || '-' }}</td>
              <td class="py-3 px-4">{{ item.jurusan || '-' }}</td>
              <td class="py-3 px-4">
                <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-200">
                  {{ item.status_siswa || 'Aktif' }}
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <a :href="`/siswa/buku-induk/${item.id}`" class="px-3 py-1.5 bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-700 font-bold rounded-lg transition inline-flex items-center gap-1">
                  <i class="bi bi-eye"></i> Detail
                </a>
              </td>
            </tr>
            <tr v-if="!siswaList?.data?.length">
              <td colspan="7" class="py-8 text-center text-slate-400">Belum ada data siswa.</td>
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
  siswaList: Object,
  filters: Object,
});
</script>
