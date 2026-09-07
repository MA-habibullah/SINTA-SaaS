<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Daftar Tagihan Siswa</h1>
        <p class="text-xs text-slate-500">Monitoring status pelunasan tagihan SPP dan iuran pendidikan peserta didik.</p>
      </div>
      <button @click="triggerGenerate" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
        <i class="bi bi-magic"></i> Generate Tagihan SPP Bulan Ini
      </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">No. Tagihan</th>
              <th class="py-3.5 px-4">Nama Siswa</th>
              <th class="py-3.5 px-4">Jenis Pos</th>
              <th class="py-3.5 px-4">Periode</th>
              <th class="py-3.5 px-4 text-right">Total Tagihan</th>
              <th class="py-3.5 px-4 text-right">Sisa Tagihan</th>
              <th class="py-3.5 px-4 text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="tag in tagihanList?.data || []" :key="tag.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ tag.nomor_tagihan }}</td>
              <td class="py-3 px-4 font-bold text-slate-800">{{ tag.siswa?.nama_lengkap }}</td>
              <td class="py-3 px-4">{{ tag.pos?.nama_pos }}</td>
              <td class="py-3 px-4">Bulan {{ tag.bulan }}/{{ tag.tahun }}</td>
              <td class="py-3 px-4 text-right font-mono">Rp {{ Number(tag.total_tagihan).toLocaleString('id-ID') }}</td>
              <td class="py-3 px-4 text-right font-mono font-bold text-red-600">Rp {{ Number(tag.sisa_tagihan).toLocaleString('id-ID') }}</td>
              <td class="py-3 px-4 text-center">
                <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold uppercase"
                      :class="tag.status_pembayaran === 'Lunas' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200'">
                  {{ tag.status_pembayaran }}
                </span>
              </td>
            </tr>
            <tr v-if="!tagihanList?.data?.length">
              <td colspan="7" class="py-8 text-center text-slate-400">Belum ada data tagihan terbit.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  tagihanList: Object,
  filters: Object,
});

const triggerGenerate = () => {
  const d = new Date();
  router.post('/keuangan/tagihan/generate-monthly', {
    bulan: d.getMonth() + 1,
    tahun: d.getFullYear(),
  });
};
</script>
