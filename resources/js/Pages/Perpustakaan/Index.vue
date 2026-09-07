<template>
  <AppLayout>
    <div class="mb-6">
      <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Perpustakaan Digital</h1>
      <p class="text-xs text-slate-500">Katalog klasifikasi Decimal Dewey Classification (DDC) dan sirkulasi peminjaman buku.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Katalog Buku -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Katalog Buku Perpustakaan</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="b in bukuList?.data || []" :key="b.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ b.judul_buku }}</div>
              <div class="text-slate-400 text-2xs">{{ b.pengarang }} | DDC: {{ b.nomor_klasifikasi_ddc || '-' }}</div>
            </div>
            <span class="font-bold text-emerald-600">{{ b.jumlah_tersedia }} Tersedia</span>
          </div>
        </div>
      </div>

      <!-- Sirkulasi Aktif -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-3">Sirkulasi Peminjaman Aktif</h3>
        <div class="space-y-2 max-h-72 overflow-y-auto">
          <div v-for="s in sirkulasiAktif?.data || []" :key="s.id" class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
            <div>
              <div class="font-bold text-slate-800">{{ s.buku?.judul_buku }}</div>
              <div class="text-slate-500 text-2xs">Peminjam: {{ s.siswa?.nama_lengkap }} (Deadline: {{ s.tanggal_harus_kembali }})</div>
            </div>
            <span class="px-2 py-0.5 rounded text-2xs font-bold bg-amber-50 text-amber-700">{{ s.status_sirkulasi }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  bukuList: Object,
  sirkulasiAktif: Object,
});
</script>
