<template>
  <AppLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Lembar Penilaian Siswa</h1>
        <p class="text-xs text-slate-500">Input Nilai Formatif, Sumatif Materi, dan Capaian Kompetensi Kurikulum Merdeka.</p>
      </div>
      <div v-if="siswaList?.length" class="flex items-center gap-2">
        <button @click="simpanBatchNilai" :disabled="saving"
                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 disabled:opacity-50">
          <i class="bi bi-floppy-fill"></i>
          <span>{{ saving ? 'Menyimpan...' : 'Simpan Seluruh Nilai' }}</span>
        </button>
      </div>
    </div>

    <!-- Filter Kelas & Mapel Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-2xs mb-6">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Rombongan Belajar (Kelas)</label>
          <select v-model="selectedKelas" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
            <option value="">-- Pilih Kelas --</option>
            <option v-for="k in kelasList" :key="k.id" :value="k.id">{{ k.nama_kelas }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Mata Pelajaran</label>
          <select v-model="selectedMapel" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
            <option value="">-- Pilih Mata Pelajaran --</option>
            <option v-for="m in mapelList" :key="m.id" :value="m.id">{{ m.nama_mapel }}</option>
          </select>
        </div>

        <div class="flex items-end">
          <button @click="loadDataPenilaian" class="w-full py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition shadow-xs flex items-center justify-center gap-2">
            <i class="bi bi-filter"></i> Tampilkan Lembar Nilai
          </button>
        </div>
      </div>
    </div>

    <!-- Interactive Grading Grid Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
            <tr>
              <th class="py-3.5 px-4 w-12 text-center">No</th>
              <th class="py-3.5 px-4">Nama Siswa</th>
              <th class="py-3.5 px-3 w-28 text-center">Formatif</th>
              <th class="py-3.5 px-3 w-28 text-center">Sumatif (TP)</th>
              <th class="py-3.5 px-3 w-28 text-center">SAS / Akhir</th>
              <th class="py-3.5 px-3 w-28 text-center bg-blue-50/50 text-blue-700">Nilai Akhir</th>
              <th class="py-3.5 px-4">Deskripsi / Capaian Kompetensi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="(siswa, idx) in localSiswaGrades" :key="siswa.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-4 text-center font-bold">{{ idx + 1 }}</td>
              <td class="py-3 px-4 font-bold text-slate-800">
                <div>{{ siswa.nama_lengkap }}</div>
                <div class="text-3xs text-slate-400 font-mono">NISN: {{ siswa.nisn }}</div>
              </td>
              <td class="py-2 px-3 text-center">
                <input v-model.number="siswa.nilai_formatif" @input="calculateFinal(siswa)" type="number" min="0" max="100"
                       class="w-20 px-2 py-1.5 text-center bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </td>
              <td class="py-2 px-3 text-center">
                <input v-model.number="siswa.nilai_sumatif_materi" @input="calculateFinal(siswa)" type="number" min="0" max="100"
                       class="w-20 px-2 py-1.5 text-center bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </td>
              <td class="py-2 px-3 text-center">
                <input v-model.number="siswa.nilai_sumatif_akhir_semester" @input="calculateFinal(siswa)" type="number" min="0" max="100"
                       class="w-20 px-2 py-1.5 text-center bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </td>
              <td class="py-2 px-3 text-center bg-blue-50/30">
                <span class="font-extrabold text-sm text-blue-700">{{ siswa.nilai_akhir || 0 }}</span>
              </td>
              <td class="py-2 px-4">
                <input v-model="siswa.capaian_kompetensi_tinggi" type="text" placeholder="Penguasaan kompetensi terbaik..."
                       class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none" />
              </td>
            </tr>
            <tr v-if="!localSiswaGrades.length">
              <td colspan="7" class="py-12 text-center text-slate-400">Silakan pilih rombel kelas dan mata pelajaran di atas.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  kelasList: Array,
  mapelList: Array,
  siswaList: Array,
  existingNilai: Object,
  filters: Object,
});

const selectedKelas = ref(props.filters?.kelasId || '');
const selectedMapel = ref(props.filters?.mapelId || '');
const saving = ref(false);

const localSiswaGrades = ref([]);

const initGrades = () => {
  if (!props.siswaList) return;
  localSiswaGrades.value = props.siswaList.map(s => {
    const ex = props.existingNilai?.[s.id] || {};
    return {
      id: s.id,
      nama_lengkap: s.nama_lengkap,
      nisn: s.nisn,
      nilai_formatif: ex.nilai_formatif || 0,
      nilai_sumatif_materi: ex.nilai_sumatif_materi || 0,
      nilai_sumatif_akhir_semester: ex.nilai_sumatif_akhir_semester || 0,
      nilai_akhir: ex.nilai_akhir || 0,
      capaian_kompetensi_tinggi: ex.capaian_kompetensi_tinggi || '',
    };
  });
};

watch(() => props.siswaList, initGrades, { immediate: true });

const calculateFinal = (siswa) => {
  const f = Number(siswa.nilai_formatif || 0);
  const sm = Number(siswa.nilai_sumatif_materi || 0);
  const sas = Number(siswa.nilai_sumatif_akhir_semester || 0);
  siswa.nilai_akhir = Math.round((f * 0.3) + (sm * 0.3) + (sas * 0.4));
};

const loadDataPenilaian = () => {
  router.get('/akademik/penilaian', {
    kelas_id: selectedKelas.value,
    mapel_id: selectedMapel.value,
  }, { preserveState: true });
};

const simpanBatchNilai = () => {
  saving.value = true;
  router.post('/akademik/penilaian/batch', {
    kelas_id: selectedKelas.value,
    mata_pelajaran_id: selectedMapel.value,
    semester: 'Ganjil',
    nilai_data: localSiswaGrades.value.map(g => ({
      siswa_id: g.id,
      nilai_formatif: g.nilai_formatif,
      nilai_sumatif_materi: g.nilai_sumatif_materi,
      nilai_sumatif_akhir_semester: g.nilai_sumatif_akhir_semester,
      nilai_akhir: g.nilai_akhir,
      capaian_kompetensi_tinggi: g.capaian_kompetensi_tinggi,
    })),
  }, {
    onFinish: () => { saving.value = false; }
  });
};
</script>
