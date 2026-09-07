<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Presensi Harian QR & Geolokasi</h1>
      <p class="text-xs text-slate-500">Log kehadiran peserta didik dan GTK menggunakan pemindaian QR code dan geolokasi.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">Tanggal</th>
              <th class="py-3.5 px-4">Subjek</th>
              <th class="py-3.5 px-4">ID Subjek</th>
              <th class="py-3.5 px-4">Jam Masuk</th>
              <th class="py-3.5 px-4">Metode</th>
              <th class="py-3.5 px-4 text-center">Status Kehadiran</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="p in presensiList?.data || []" :key="p.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-mono">{{ p.tanggal }}</td>
              <td class="py-3 px-4 uppercase font-bold">{{ p.subjek_type }}</td>
              <td class="py-3 px-4 font-mono text-slate-500">{{ p.subjek_id }}</td>
              <td class="py-3 px-4 font-bold text-slate-800 font-mono">{{ p.jam_masuk || '-' }}</td>
              <td class="py-3 px-4">{{ p.metode_presensi }}</td>
              <td class="py-3 px-4 text-center">
                <span class="px-2.5 py-1 rounded-lg text-2xs font-extrabold uppercase bg-emerald-50 text-emerald-600 border border-emerald-200">
                  {{ p.status_kehadiran }}
                </span>
              </td>
            </tr>
            <tr v-if="!presensiList?.data?.length">
              <td colspan="6" class="py-8 text-center text-slate-400">Belum ada data presensi hari ini.</td>
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
  presensiList: Object,
  filters: Object,
});
</script>
