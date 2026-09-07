<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Pangkalan Data Sekolah & Siswa (PDSS SNBP)</h1>
        <p class="text-xs text-slate-500">Pemeringkatan kelayakan siswa eligible Seleksi Nasional Berdasarkan Prestasi (SNBP).</p>
      </div>
    </div>

    <!-- Summary Akreditasi & Kuota SNBP -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Akreditasi Sekolah</span>
        <div class="text-2xl font-black text-blue-600 mt-1">Akreditasi {{ rankingData?.akreditasi_sekolah || 'A' }}</div>
        <div class="text-xs text-slate-500 mt-1">Kuota SNBP: {{ rankingData?.kuota_persen || 40 }}% Terbaik</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Total Siswa Angkatan</span>
        <div class="text-2xl font-black text-slate-800 mt-1">{{ rankingData?.total_siswa || 0 }} Siswa</div>
        <div class="text-xs text-slate-500 mt-1">Basis Rombel Terdaftar</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase">Siswa Eligible SNBP</span>
        <div class="text-2xl font-black text-emerald-600 mt-1">{{ rankingData?.kuota_eligible || 0 }} Siswa</div>
        <div class="text-xs text-emerald-600 font-semibold mt-1">Siap Didaftarkan ke Portal SNPMB</div>
      </div>
    </div>

    <!-- Ranking Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4 w-16 text-center">Rank</th>
              <th class="py-3.5 px-4">Nama Siswa</th>
              <th class="py-3.5 px-4">NISN</th>
              <th class="py-3.5 px-4">Jurusan</th>
              <th class="py-3.5 px-4 text-center">Rata-rata Rapor (Smt 1-5)</th>
              <th class="py-3.5 px-4 text-center">Prestasi</th>
              <th class="py-3.5 px-4 text-center">Status SNBP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="item in rankingData?.ranking_list || []" :key="item.siswa_id" 
                class="transition" :class="item.is_eligible ? 'bg-emerald-50/30 hover:bg-emerald-50/60' : 'hover:bg-slate-50/80'">
              <td class="py-3 px-4 text-center font-black" :class="item.ranking <= 3 ? 'text-blue-600 text-sm' : 'text-slate-700'">
                #{{ item.ranking }}
              </td>
              <td class="py-3 px-4 font-bold text-slate-800">{{ item.nama_lengkap }}</td>
              <td class="py-3 px-4 font-mono">{{ item.nisn }}</td>
              <td class="py-3 px-4">{{ item.jurusan }}</td>
              <td class="py-3 px-4 text-center font-extrabold text-slate-800">{{ item.rata_rata_nilai }}</td>
              <td class="py-3 px-4 text-center">
                <span class="px-2 py-0.5 rounded-full text-2xs font-bold bg-blue-50 text-blue-600">
                  {{ item.total_prestasi }} Sertifikat
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <span class="px-3 py-1 rounded-xl text-2xs font-extrabold border"
                      :class="item.is_eligible ? 'bg-emerald-100 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-200'">
                  {{ item.status_snbp }}
                </span>
              </td>
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
  jurusanList: Array,
  rankingData: Object,
  filters: Object,
});
</script>
