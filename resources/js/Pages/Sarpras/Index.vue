<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Sarana & Prasarana (Inventaris Aset)</h1>
      <p class="text-xs text-slate-500">Pencatatan aset ruangan, kondisi barang, dan penerbitan token QR Code labeling.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4">Kode Aset</th>
              <th class="py-3.5 px-4">Nama Barang</th>
              <th class="py-3.5 px-4">Kategori</th>
              <th class="py-3.5 px-4">Lokasi Ruangan</th>
              <th class="py-3.5 px-4">Jumlah</th>
              <th class="py-3.5 px-4">Kondisi</th>
              <th class="py-3.5 px-4">QR Token</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="a in asetList?.data || []" :key="a.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ a.kode_aset }}</td>
              <td class="py-3 px-4 font-bold text-slate-800">{{ a.nama_barang }}</td>
              <td class="py-3 px-4">{{ a.kategori }}</td>
              <td class="py-3 px-4">{{ a.lokasi_ruangan }}</td>
              <td class="py-3 px-4">{{ a.jumlah }} {{ a.satuan }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded-md text-2xs font-bold"
                      :class="a.kondisi === 'Baik' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                  {{ a.kondisi }}
                </span>
              </td>
              <td class="py-3 px-4 font-mono text-blue-600 font-bold">{{ a.qr_code_token }}</td>
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
  asetList: Object,
  filters: Object,
});
</script>
