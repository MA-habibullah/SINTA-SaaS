<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Kasir Pembayaran Real-time</h1>
        <p class="text-xs text-slate-500">Penerimaan kas pembayaran SPP dan iuran pendidikan peserta didik.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Form Pencarian Siswa -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-4 flex items-center gap-2">
          <i class="bi bi-search text-blue-600"></i> Cari Peserta Didik
        </h3>
        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">ID / NISN / Nama Siswa</label>
            <input v-model="searchQuery" type="text" placeholder="Ketik nama atau NISN..."
                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
          </div>
          <button @click="searchSiswa" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-xs flex items-center justify-center gap-2">
            <i class="bi bi-person-check-fill"></i> Muat Data Tagihan
          </button>
        </div>

        <div v-if="siswa" class="mt-6 pt-6 border-t border-slate-100">
          <div class="text-xs font-bold text-slate-500 uppercase mb-2">Profil Siswa</div>
          <div class="font-black text-slate-800 text-base">{{ siswa.nama_lengkap }}</div>
          <div class="text-xs text-slate-500 mt-0.5">NISN: {{ siswa.nisn }} | Kelas: {{ siswa.kelas_saat_ini || '-' }}</div>
        </div>
      </div>

      <!-- Daftar Tagihan Aktif Siswa -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
        <h3 class="font-extrabold text-slate-800 text-sm mb-4 flex items-center gap-2">
          <i class="bi bi-receipt text-blue-600"></i> Tagihan Belum Lunas
        </h3>

        <div v-if="tagihanList?.length" class="space-y-3">
          <div v-for="tagihan in tagihanList" :key="tagihan.id" 
               class="p-4 rounded-xl border border-slate-200 hover:border-blue-300 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition">
            <div>
              <div class="font-bold text-slate-800 text-sm">{{ tagihan.pos?.nama_pos }} (Bulan {{ tagihan.bulan }}/{{ tagihan.tahun }})</div>
              <div class="text-xs text-slate-500 mt-0.5">No. Tagihan: <span class="font-mono">{{ tagihan.nomor_tagihan }}</span></div>
              <div class="text-xs font-bold text-red-600 mt-1">Sisa Tagihan: Rp {{ Number(tagihan.sisa_tagihan).toLocaleString('id-ID') }}</div>
            </div>

            <button @click="openBayarModal(tagihan)" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-xs flex items-center gap-1.5 shrink-0">
              <i class="bi bi-cash-coin"></i> Bayar Kasir
            </button>
          </div>
        </div>

        <div v-else class="py-12 text-center text-slate-400 text-xs">
          <i class="bi bi-check2-circle text-3xl block mb-2 text-emerald-500"></i>
          Tidak ada tagihan tertunggak atau siswa belum dipilih.
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  siswa: Object,
  tagihanList: Array,
  kasList: Array,
});

const searchQuery = ref('');

const searchSiswa = () => {
  router.get('/keuangan/kasir', { siswa_id: searchQuery.value }, { preserveState: true });
};

const openBayarModal = (tagihan) => {
  const nominal = prompt(`Masukkan nominal pembayaran untuk ${tagihan.pos?.nama_pos}:`, tagihan.sisa_tagihan);
  if (nominal && !isNaN(nominal)) {
    router.post('/keuangan/kasir/bayar', {
      tagihan_id: tagihan.id,
      nominal_bayar: Number(nominal),
      metode_pembayaran: 'Tunai',
    });
  }
};
</script>
