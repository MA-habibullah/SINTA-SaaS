<template>
  <Teleport to="body">
    <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-4 md:p-6 overflow-y-auto">
      <!-- Dark Blur Backdrop (Persistent - Anti Escape jika belum tuntas) -->
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity"></div>

      <!-- Modal Card Box Container -->
      <div class="relative bg-white rounded-3xl shadow-2xl border border-slate-200/90 w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden z-10 animate-in fade-in zoom-in duration-200">
        
        <!-- 1. Header Modal -->
        <div class="p-5 sm:p-6 bg-gradient-to-r from-blue-700 via-indigo-700 to-purple-800 text-white flex items-start justify-between gap-4 shrink-0 relative overflow-hidden">
          <div class="relative z-10 flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center text-2xl text-yellow-300 shadow-inner shrink-0">
              <i class="bi bi-person-video3"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full bg-amber-400 text-slate-900 text-[10px] font-black uppercase tracking-wider shadow-xs">
                  Wajib Diisi Siswa
                </span>
                <span class="text-xs text-blue-200 font-medium">
                  Kelas: <strong class="text-white">{{ surveyData?.kelas?.nama || 'Kelas Anda' }}</strong>
                </span>
              </div>
              <h2 class="text-lg sm:text-xl font-black mt-1 tracking-tight">
                {{ surveyData?.survei?.judul_survei || 'Survei Evaluasi Kinerja Guru oleh Siswa' }}
              </h2>
              <p class="text-xs text-blue-100/90 mt-0.5 max-w-xl">
                Penilaian Anda bersifat <strong class="text-amber-300 font-bold">ANONIM & RAHASIA</strong>. Berikan evaluasi jujur untuk peningkatan mutu pembelajaran.
              </p>
            </div>
          </div>

          <!-- Progress Ring / Counter & Close Button if Complete -->
          <div class="relative z-10 shrink-0 flex items-center gap-3 text-right">
            <div>
              <div class="text-[11px] font-bold text-blue-200 uppercase tracking-wider">Progress Pengisian</div>
              <div class="text-xl sm:text-2xl font-black text-white mt-0.5">
                {{ surveyData?.total_selesai || 0 }} <span class="text-sm font-normal text-blue-200">/ {{ surveyData?.total_guru || 0 }} Guru</span>
              </div>
            </div>
            <button
              v-if="surveyData?.total_belum === 0"
              type="button"
              @click="showModal = false"
              class="w-9 h-9 rounded-xl bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition shrink-0 cursor-pointer"
              title="Tutup Modal"
            >
              <i class="bi bi-x-lg text-lg"></i>
            </button>
          </div>

          <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        </div>

        <!-- 2. Modal Body Scrollable -->
        <div class="p-5 sm:p-6 overflow-y-auto space-y-6 flex-grow" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
          
          <!-- Layar Selebrasi jika Semua Selesai -->
          <div v-if="surveyData?.total_belum === 0" class="text-center py-10 px-4 space-y-4">
            <div class="w-20 h-20 rounded-3xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-4xl mx-auto shadow-inner">
              <i class="bi bi-patch-check-fill"></i>
            </div>
            <div>
              <h3 class="text-xl font-black text-slate-800">Luar Biasa! Seluruh Survei Guru Telah Selesai</h3>
              <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                Terima kasih atas partisipasi Anda. Upan balik Anda sangat berharga untuk kemajuan guru dan proses belajar mengajar di sekolah.
              </p>
            </div>
            <button 
              type="button" 
              @click="showModal = false"
              class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition inline-flex items-center gap-2"
            >
              <i class="bi bi-arrow-right-circle"></i>
              <span>Kembali ke Dashboard Utama</span>
            </button>
          </div>

          <!-- Form Pengisian jika masih ada guru yang belum dinilai -->
          <template v-else>
            <!-- A. Baris Pemilihan Guru Pengampu Kelas -->
            <div>
              <div class="flex items-center justify-between mb-2.5">
                <label class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                  <i class="bi bi-people-fill text-blue-600"></i>
                  Pilih Guru yang Mengajar di Kelas Anda:
                </label>
                <span class="text-[11px] font-bold text-amber-600">
                  <i class="bi bi-exclamation-circle-fill me-1"></i>Sisa {{ surveyData?.total_belum }} guru belum dinilai
                </span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                <button
                  v-for="guru in surveyData?.guru_list || []"
                  :key="guru.guru_id"
                  type="button"
                  @click="selectGuru(guru)"
                  class="p-3 rounded-2xl border text-left transition flex items-center gap-3 relative overflow-hidden group"
                  :class="[
                    selectedGuru?.guru_id === guru.guru_id 
                      ? 'border-blue-600 bg-blue-50/70 ring-2 ring-blue-500/20 shadow-xs' 
                      : (guru.is_sudah ? 'border-emerald-200 bg-emerald-50/40 opacity-75' : 'border-slate-200 hover:border-blue-300 bg-white shadow-2xs')
                  ]"
                >
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shrink-0 shadow-2xs"
                       :class="guru.is_sudah ? 'bg-emerald-600 text-white' : 'bg-gradient-to-tr from-blue-600 to-indigo-600 text-white'">
                    {{ (guru.nama_guru || 'G').charAt(0).toUpperCase() }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-slate-800 truncate">{{ guru.nama_guru }}</div>
                    <div class="text-[11px] text-slate-500 truncate">{{ guru.nama_mapel }}</div>
                  </div>
                  <div class="shrink-0">
                    <span v-if="guru.is_sudah" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black flex items-center gap-1">
                      <i class="bi bi-check-circle-fill"></i> Selesai
                    </span>
                    <span v-else class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-black flex items-center gap-1">
                      <i class="bi bi-clock-fill"></i> Belum
                    </span>
                  </div>
                </button>
              </div>
            </div>

            <!-- B. Banner Keterangan Skala Likert 5 Poin -->
            <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
              <div class="flex items-center gap-2 font-bold text-slate-700">
                <i class="bi bi-info-circle-fill text-blue-600 text-sm"></i>
                <span>Panduan Skala Penilaian 5 Poin:</span>
              </div>
              <div class="flex items-center gap-1.5 flex-wrap text-[11px]">
                <span class="px-2 py-0.5 rounded-lg bg-rose-50 text-rose-700 font-bold border border-rose-200">1: Sangat Kurang</span>
                <span class="px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 font-bold border border-amber-200">2: Kurang</span>
                <span class="px-2 py-0.5 rounded-lg bg-yellow-50 text-yellow-800 font-bold border border-yellow-200">3: Cukup</span>
                <span class="px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 font-bold border border-blue-200">4: Baik</span>
                <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">5: Sangat Baik</span>
              </div>
            </div>

            <!-- C. Form Instrumen Pertanyaan untuk Guru Terpilih -->
            <div v-if="selectedGuru && !selectedGuru.is_sudah" class="space-y-4">
              <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-200 flex items-center justify-between">
                <div class="text-xs font-bold text-blue-900">
                  Menilai: <span class="font-extrabold text-blue-700">{{ selectedGuru.nama_guru }}</span> ({{ selectedGuru.nama_mapel }})
                </div>
                <div class="text-[11px] text-blue-600 font-semibold">10 Indikator Wajib Diisi</div>
              </div>

              <!-- Daftar 10 Butir Pertanyaan -->
              <div class="space-y-3">
                <div 
                  v-for="(item, idx) in surveyData?.pertanyaan || []" 
                  :key="item.id"
                  class="p-4 rounded-2xl border transition-all"
                  :class="ratings[item.id] ? 'bg-white border-blue-200 shadow-2xs' : 'bg-slate-50/50 border-slate-200'"
                >
                  <div class="flex items-start justify-between gap-3 mb-2.5">
                    <div class="flex items-start gap-2.5">
                      <span class="w-6 h-6 rounded-lg bg-slate-200 text-slate-700 font-black text-xs flex items-center justify-center shrink-0 mt-0.5">
                        {{ idx + 1 }}
                      </span>
                      <div>
                        <div class="text-xs font-bold text-slate-800">{{ item.teks_pertanyaan }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">{{ item.kategori_dimensi }} &bull; {{ item.kode_indikator }}</div>
                      </div>
                    </div>
                  </div>

                  <!-- Radio Options 1 s.d. 5 -->
                  <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 pt-1">
                    <label 
                      v-for="poin in 5" 
                      :key="poin"
                      class="flex items-center gap-2 p-2 rounded-xl border text-xs font-semibold cursor-pointer transition select-none"
                      :class="ratings[item.id] === poin 
                        ? 'bg-blue-600 text-white border-blue-600 shadow-xs font-bold' 
                        : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'"
                    >
                      <input 
                        type="radio" 
                        :name="'q_' + item.id" 
                        :value="poin" 
                        v-model="ratings[item.id]" 
                        class="sr-only"
                      />
                      <span class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                            :class="ratings[item.id] === poin ? 'border-white bg-white/20' : 'border-slate-300'">
                        <span v-if="ratings[item.id] === poin" class="w-2 h-2 rounded-full bg-white"></span>
                      </span>
                      <span class="truncate">{{ getPoinLabel(poin) }}</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- D. Umpan Balik Terbuka -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    Hal Baik / Positif dari Guru Ini:
                  </label>
                  <textarea 
                    v-model="feedbackPositif" 
                    rows="2" 
                    placeholder="Contoh: Cara mengajar sangat menyenangkan dan mudah dipahami..."
                    class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                  ></textarea>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    Saran / Area yang Perlu Ditingkatkan:
                  </label>
                  <textarea 
                    v-model="feedbackSaran" 
                    rows="2" 
                    placeholder="Contoh: Mohon penjelasannya bisa sedikit lebih pelan saat materi hitungan..."
                    class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                  ></textarea>
                </div>
              </div>
            </div>

            <div v-else-if="selectedGuru && selectedGuru.is_sudah" class="p-6 bg-emerald-50 rounded-2xl border border-emerald-200 text-center space-y-2">
              <i class="bi bi-check-circle-fill text-3xl text-emerald-600"></i>
              <div class="text-sm font-bold text-emerald-900">Guru Ini Sudah Berhasil Anda Nilai</div>
              <p class="text-xs text-emerald-700">Silakan pilih guru lain di atas yang masih berstatus belum diisi.</p>
            </div>
          </template>
        </div>

        <!-- 3. Footer Action Modal -->
        <div v-if="surveyData?.total_belum > 0" class="p-4 sm:px-6 bg-slate-50 border-t border-slate-200/80 flex items-center justify-between gap-3 shrink-0">
          <div class="text-xs text-slate-500 font-medium">
            <span v-if="isFormComplete" class="text-emerald-600 font-bold flex items-center gap-1">
              <i class="bi bi-check2-all"></i> Semua indikator telah terisi lengkap
            </span>
            <span v-else class="text-rose-600 font-semibold flex items-center gap-1">
              <i class="bi bi-info-circle"></i> Harap isi nilai untuk ke-10 pertanyaan di atas
            </span>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="submitSurvey"
              :disabled="!isFormComplete || isSubmitting || !selectedGuru || selectedGuru.is_sudah"
              class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i v-if="isSubmitting" class="bi bi-arrow-repeat animate-spin"></i>
              <i v-else class="bi bi-send-fill"></i>
              <span>{{ isSubmitting ? 'Menyimpan...' : 'Kirim Penilaian Guru Ini' }}</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const showModal = ref(false);
const surveyData = ref(null);
const selectedGuru = ref(null);
const ratings = ref({});
const feedbackPositif = ref('');
const feedbackSaran = ref('');
const isSubmitting = ref(false);

const getPoinLabel = (poin) => {
  switch (poin) {
    case 1: return '1. Sangat Kurang';
    case 2: return '2. Kurang';
    case 3: return '3. Cukup';
    case 4: return '4. Baik';
    case 5: return '5. Sangat Baik';
    default: return `${poin}`;
  }
};

const isFormComplete = computed(() => {
  if (!surveyData.value?.pertanyaan || surveyData.value.pertanyaan.length === 0) return false;
  return surveyData.value.pertanyaan.every(q => ratings.value[q.id] >= 1 && ratings.value[q.id] <= 5);
});

const selectGuru = (guru) => {
  selectedGuru.value = guru;
  ratings.value = {};
  feedbackPositif.value = '';
  feedbackSaran.value = '';
};

const checkSurveyStatus = async (forceOpen = false) => {
  try {
    const res = await axios.get('/kepala-sekolah/survei-guru/student-status');
    if (res.data?.success) {
      surveyData.value = res.data;
      if (res.data?.is_mandatory_popup || forceOpen) {
        showModal.value = true;
      }
      // Pilih otomatis guru pertama yang belum dinilai
      const firstBelum = res.data.guru_list.find(g => !g.is_sudah);
      if (firstBelum) {
        selectGuru(firstBelum);
      } else if (res.data.guru_list.length > 0) {
        selectGuru(res.data.guru_list[0]);
      }
    }
  } catch (err) {
    console.error('Gagal memuat status survei siswa:', err);
  }
};

const submitSurvey = async () => {
  if (!isFormComplete.value || !selectedGuru.value || isSubmitting.value) return;

  isSubmitting.value = true;
  try {
    const payload = {
      guru_id: selectedGuru.value.guru_id,
      kelas_id: surveyData.value?.kelas?.id,
      nama_kelas: surveyData.value?.kelas?.nama,
      mapel_id: selectedGuru.value.mapel_id,
      nama_mapel: selectedGuru.value.nama_mapel,
      is_anonim: true,
      ratings: ratings.value,
      umpan_balik_positif: feedbackPositif.value,
      area_pengembangan: feedbackSaran.value,
    };

    const res = await axios.post('/kepala-sekolah/survei-guru/submit-evaluasi-siswa', payload);
    if (res.data?.success) {
      // Refresh status survei guru
      await checkSurveyStatus();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan evaluasi guru. Silakan coba lagi.');
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  checkSurveyStatus();
  window.addEventListener('open-student-survey-modal', () => {
    checkSurveyStatus(true);
  });
});
</script>
