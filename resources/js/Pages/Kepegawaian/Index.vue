<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Kepegawaian (Pendidik & Tenaga Kependidikan)</h1>
      <p class="text-xs text-slate-500">Master data guru, staf tata usaha, NIP, NUPTK, dan status kepegawaian.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">Nama Lengkap</th>
              <th class="py-3.5 px-4">NIP / NUPTK</th>
              <th class="py-3.5 px-4">Jenis PTK</th>
              <th class="py-3.5 px-4">Status Kepegawaian</th>
              <th class="py-3.5 px-4">Pendidikan</th>
              <th class="py-3.5 px-4 text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="g in gtkList?.data || []" :key="g.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-bold text-slate-800">{{ g.nama_lengkap }}</td>
              <td class="py-3 px-4 font-mono">{{ g.nip || g.nuptk || '-' }}</td>
              <td class="py-3 px-4">{{ g.jenis_ptk }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded-md text-2xs font-bold bg-blue-50 text-blue-700">
                  {{ g.status_kepegawaian }}
                </span>
              </td>
              <td class="py-3 px-4">{{ g.pendidikan_terakhir || '-' }}</td>
              <td class="py-3 px-4 text-center">
                <span class="px-2 py-0.5 rounded text-2xs font-bold text-emerald-600 bg-emerald-50">
                  {{ g.is_active ? 'Aktif' : 'Non-Aktif' }}
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
  gtkList: Object,
  filters: Object,
});
</script>
