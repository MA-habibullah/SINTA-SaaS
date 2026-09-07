<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Manajemen Sekolah Mitra (Tenants)</h1>
        <p class="text-xs text-slate-500">Daftar lembaga pendidikan yang tergabung dalam platform SINTA-SaaS.</p>
      </div>
      <button class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
        <i class="bi bi-plus-circle-fill"></i> Tambah Sekolah
      </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">NPSN</th>
              <th class="py-3.5 px-4">Nama Lembaga</th>
              <th class="py-3.5 px-4">Jenjang / Status</th>
              <th class="py-3.5 px-4">Paket Aktif</th>
              <th class="py-3.5 px-4">Storage (Limit)</th>
              <th class="py-3.5 px-4">Status</th>
              <th class="py-3.5 px-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="t in tenants?.data || []" :key="t.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ t.npsn }}</td>
              <td class="py-3 px-4 font-bold text-slate-800">{{ t.nama_sekolah }}</td>
              <td class="py-3 px-4">{{ t.jenjang }} / {{ t.status_sekolah }}</td>
              <td class="py-3 px-4">
                <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                  {{ t.paket_aktif }}
                </span>
              </td>
              <td class="py-3 px-4 font-mono">{{ (t.storage_used_bytes / (1024*1024)).toFixed(1) }} MB / {{ t.storage_limit_mb }} MB</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded-md text-2xs font-bold" :class="t.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                  {{ t.is_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <button class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition text-xs">
                  Kelola
                </button>
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
  tenants: Object,
  filters: Object,
});
</script>
